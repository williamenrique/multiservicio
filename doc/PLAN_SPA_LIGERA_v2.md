# Manual de Proyecto: Implementación de SPA Ligera (v2.0)
Documento de planificación — Revisado tras auditoría completa del código (2026-10-09)

Propósito: Guía para implementar navegación SPA ligera en BusYaracuy/TallerPro.
Ubicación: doc/PLAN_SPA_LIGERA_v2.md
Basado en: auditoría real de 83 archivos (app.js, DataTableRefactor.js, todos los
controladores, todos los modelos, y las vistas críticas con scripts inline).

Reemplaza a: doc/PLAN_SPA_LIGERA.md (v1.0, genérica).

---

## ⚠️ CAMBIOS IMPORTANTES RESPECTO A v1.0

La v1.0 estaba escrita como si el proyecto fuera "un MVC típico". Tras la
auditoría real, se identificaron 6 HECHOS que cambian sustancialmente el plan:

1. **`app.js` recarga la página en cada `popstate`.** Esta sola línea hace
   que HTMX + history API no funcione sin antes removerla. Es BLOQUEANTE.

2. **`DataTableRefactor.js` NO tiene método `destroy()`.** Cada instancia
   queda huérfana al navegar. Memory leak garantizado.

3. **`PollingManager` NO tiene `pause()/resume()`.** Solo `start()/stop()`.
   Sin esto, el polling sigue durante toda la navegación.

4. **16 vistas tienen scripts inline masivos** (presupuesto: 81 KB de JS,
   garantía: 54 KB, venta: 50 KB, email: 44 KB, devoluciones: 40 KB,
   taller/nueva_orden: 34 KB, reportes: 32 KB). Esto NO es un "caso borde":
   es el estado normal del proyecto.

5. **`Controller::view()` no distingue fragmentos.** Todos los controladores
   responden HTML completo con header + footer.

6. **Las estimaciones de la v1.0 (15h) eran irreales.** Con esta estructura,
   el plan completo de los 17 módulos toma 60-90h. La v2.0 lo refleja.

---

## 📋 ÍNDICE

1. Contexto y realidad del proyecto
2. Alcance revisado
3. Decisión tecnológica
4. Fase 0 — Auditoría (ya hecha parcialmente — ver AUDITORIA_SPA.md)
5. Fase 0.5 — Refactor de scripts inline (NUEVA)
6. Fase 1 — Preparación del motor SPA
7. Fase 2 — Adaptación del backend (con HX-Request)
8. Fase 3 — Adaptación del frontend base
9. Fase 4 — Refactor de módulos (init/destroy)
10. Fase 5 — Testing
11. Fase 6 — Despliegue
12. Criterios de éxito y rollback
13. Recomendaciones finales

---

## 1. Contexto y realidad del proyecto

### Cómo funciona hoy
- Arquitectura MVC tradicional. Cada clic en el sidebar ejecuta una recarga
  completa del navegador.
- El polling global vive en `public_html/js/polling.js` (PollingManager +
  DashboardCache) y se registra desde `app.js` en `DOMContentLoaded`.
- `DataTableRefactor.js` es el motor único de tablas dinámicas. Se instancia
  desde cada módulo (clientes.js, dashboard.js, proveedores.js, etc.) y
  crea `window.handler_<tableId>` como referencia global.
- Los módulos JS de cada vista se cargan mediante `<script src="...">` al
  final del HTML del módulo. Algunos módulos tienen además un bloque
  `<script>` inline grande (ver §4).

### Estado actual de la navegación
- Sidebar con 17 links (dashboard, venta, inventario, facturación, facturas,
  devoluciones, garantía, taller, proveedores, gastos, pedidos-clientes,
  presupuestos, emails, reportes, clientes, personal, empresa).
- Login y logout hacen `window.location.href` completo. No deben entrar en
  la SPA.
- Catálogo público (`/public/catalogo/*`) es un sitio aparte. Queda fuera.

### Deuda técnica relevante para la SPA
| Archivo | Tamaño | Problema |
|---|---|---|
| `app.js` | 78.5 KB | `popstate` recarga; listeners globales; `setInterval` de reloj |
| `reportes.js` | 81.6 KB | Registra 2 DataTableRefactor + muchas funciones `window.*` |
| `facturacion.js` | 61.3 KB | Estado local (`openInvoices`, `activeInvoiceId`) |
| `dashboard.js` | 33.4 KB | Instancia Chart.js global (`performanceChart`) |
| `DataTableRefactor.js` | 5.3 KB | Sin destroy; guarda `handler_<id>` global |
| `polling.js` | 4.5 KB | Sin pause/resume |

### Deuda técnica en vistas (scripts inline)
| Vista | KB del bloque inline (aprox) |
|---|---|
| `presupuesto/index.php` | ~70 KB de HTML+JS inline |
| `garantia/index.php` | ~45 KB |
| `venta/index.php` | ~40 KB |
| `email/index.php` | ~38 KB |
| `devoluciones/index.php` | ~35 KB |
| `taller/nueva_orden.php` | ~30 KB |
| `reportes/index.php` | ~25 KB |
| `facturas/ver.php` | ~5 KB |
| `taller/index.php`, `taller/cerradas.php`, `taller/historial.php` | 3-6 KB cada una |
| `empresa/index.php`, `public/catalogo/*`, `Login.php`, `vehicle_history_qr.php` | 2-5 KB cada una |

**Conclusión**: sin extraer estos bloques a archivos `.js` externos, HTMX no
puede re-inicializarlos correctamente. Esto es un prerrequisito, no un
"nice to have".

---

## 2. Alcance revisado

### Incluido en la SPA (13 módulos viables)
Solo módulos con JS externo y sin estado local complejo:

| Módulo | JS | Riesgo |
|---|---|---|
| Dashboard | `dashboard.js` | Medio (Chart.js) |
| Inventario | `inventario.js` | Medio (2 dropdowns custom) |
| Clientes | `clientes.js` | Bajo |
| Personal | `personal.js` | Bajo |
| Proveedores | `proveedores.js` | Medio |
| Gastos | `gastos.js` | Bajo |
| Facturas | `facturas.js` | Bajo |
| Historial | `historial.js` | Bajo |
| Perfil | `perfil.js` | Bajo |
| Empresa | `empresa.js` | Bajo |
| Presupuestos | `presupuesto/index.php` (inline) | **Alto — requiere Fase 0.5** |
| Garantías | `garantia/index.php` (inline) | **Alto — requiere Fase 0.5** |
| Taller (índice) | `taller/index.php` (inline) | Alto |

### EXCLUIDO explícitamente de la SPA
Justificación en cada caso:

- **Facturación (POS)** — `facturacion.js` (61 KB) mantiene `openInvoices`,
  `activeInvoiceId`, `selectedItemFromSearch`, sincronización debounced
  contra `/facturacion/sincronizarBorrador`, y polling de borradores.
  Refactorizarlo al patrón init/destroy es una reescritura, no un ajuste.
- **Venta de mostrador** — Estado del carrito local + DataTableRefactor +
  modal de cliente. Riesgo comparable al POS.
- **Reportes contables** — 81 KB de JS, dos DataTableRefactor, decenas de
  handlers globales. Solo lectura; recargarlo no molesta.
- **Emails** — 38 KB de inline + un compositor de plantillas.
- **Devoluciones** — 35 KB de inline + paginación propia.
- **Catálogo público** — Sitio aparte, sin sidebar.
- **Login/Logout** — Recarga completa por seguridad.
- **Todas las impresiones de PDF** — Abren en `_blank`, no cambian contexto.

**Total: 13 módulos en SPA + 4 excluidos por complejidad + 4 excluidos por
diseño.** Esto reduce el esfuerzo estimado de ~80h a ~25-30h.

---

## 3. Decisión tecnológica

Se mantiene la recomendación de HTMX (Opción A). Motivos actualizados tras
auditoría:

- HTMX **sí re-ejecuta `<script>` inline** al inyectar HTML. Esto permite
  una Fase 0.5 menos agresiva: en lugar de extraer TODOS los scripts inline,
  podemos dejar los pequeños y controlar la doble ejecución con flags.
- HTMX maneja `pushState` y el botón atrás automáticamente.
- HTMX es `hx-boost` — se puede activar con `<body hx-boost="true">` en el
  header y desactivar con `hx-boost="false"` en login/logout y PDFs. **Esto
  reduce la Fase 3 de "adaptar 17 links" a "adaptar el body + 4 excepciones".**
- La librería pesa ~14 KB. Compatible con la política de compilar localmente
  (ya se hace con Tailwind).

Descartadas:
- **SPA casera**: el `popstate` con reload de `app.js` ya bloquea cualquier
  implementación casera sin tocarlo.
- **Turbo**: más pesado, sin ventajas sobre HTMX en este caso.

---

## 4. Fase 0 — Auditoría (completada)

**Ver `doc/AUDITORIA_SPA.md`** para el detalle completo de la auditoría. Los
hallazgos críticos que bloquean el proyecto hoy son:

1. `app.js` línea ~75: `window.addEventListener("popstate", () => window.location.reload())`.
   **Bloqueante**. Sin quitar esto, la SPA no funciona.
2. `polling.js`: no tiene `pause()` ni `resume()`. Solo `start()/stop()`.
3. `DataTableRefactor.js`: no tiene `destroy()`. Crea `window.handler_<id>`.
4. `app.js`: registra `setInterval` para el reloj digital que nunca se limpia.
5. `app.js`: registra listeners globales en `document` (dropdown de usuario,
   cierre por click externo, sidebar responsive).
6. `dashboard.js`: instancia global `performanceChart` que no se destruye.
7. Controllers: `Controller::view()` no distingue fragmentos.

---

## 5. Fase 0.5 — Refactor de scripts inline (NUEVA FASE)

**Esfuerzo: 8-12 horas.**
**Bloqueante para la Fase 4.**

### Objetivo
Antes de tocar la navegación, hay que extraer los scripts inline de las
vistas que van a entrar en la SPA. Sin esto, HTMX re-ejecuta el mismo
bloque en cada navegación y duplica listeners.

### Tareas

**Tarea 0.5.1 — Extraer inline de `presupuesto/index.php`**
- Mover ~70 KB de JS inline a `public_html/js/presupuesto.js`.
- Mantener el `window.PRESUPUESTO_CONFIG` inline (es solo un objeto de
  configuración, se puede dejar).
- El archivo resultante debe exponer sus funciones al `window` explícitamente
  (`window.cargarPresupuestos = ...`, `window.abrirModalCrear = ...`).

**Tarea 0.5.2 — Extraer inline de `garantia/index.php`**
- Mover a `public_html/js/garantia.js`.
- Exponer `cambiarTabGarantia`, `cargarFacturasGarantia`, etc.

**Tarea 0.5.3 — Extraer inline de `venta/index.php`**
- Mover a `public_html/js/venta.js`.
- **Nota**: este módulo está EXCLUIDO de la SPA. La extracción se hace por
  consistencia, no porque sea necesario para la SPA.

**Tarea 0.5.4 — Extraer inline de `email/index.php`, `devoluciones/index.php`,
`taller/nueva_orden.php`, `reportes/index.php`, `facturas/ver.php`**
- Mismos pasos. Cada archivo nuevo se registra en `header.php` con `defer`
  solo si es necesario cargarlo globalmente; si no, se carga en la vista.

**Tarea 0.5.5 — Verificar que cada módulo sigue funcionando SIN HTMX**
- Antes de avanzar a Fase 1, todo debe funcionar exactamente igual que antes.
- Es la prueba de que la extracción no rompió nada.

**Resultado esperado**: todas las vistas incluyen su JS externo mediante
`<script src="<?php echo URLROOT; ?>/js/modulo.js"></script>` al final,
sin bloques inline grandes.

---

## 6. Fase 1 — Preparación del motor SPA

**Esfuerzo: 1-2 horas.**
**Requiere**: Fase 0.5 completa.

### Tareas

**Tarea 1.1 — Descargar HTMX**
- Descargar `htmx.min.js` (~14 KB) a `public_html/js/htmx.min.js`.
- NO usar CDN.

**Tarea 1.2 — Registrar HTMX en `header.php`**
- Añadir antes de `app.js`, después de `polling.js`:
  ```html
  <script defer src="<?php echo URLROOT; ?>/js/htmx.min.js"></script>