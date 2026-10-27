<?php
/**
 * Helper de verificación de notificaciones automáticas al iniciar sesión.
 *
 * v2.2 (2026-10-26) — PERFORMANCE:
 *   • Las marcas "ya enviado hoy / este mes" ahora se persisten en
 *     `table_system_flags` (clave/valor) en vez de solo en $_SESSION.
 *     Antes, cada nuevo login de un admin reenviaba correos SMTP
 *     (proveedores + resumen mensual) aunque ya se hubieran enviado,
 *     lo que hacía lento el login.
 *   • Si la tabla `table_system_flags` no existe, cae en fallback a
 *     $_SESSION para no romper.
 *   • Se agregó timeout corto a PHPMailer vía EmailService (ver
 *     EmailService). Aquí solo se encola y protege.
 */
class NotificationChecker
{
    /**
     * Ejecuta todas las verificaciones de notificaciones para el usuario autenticado.
     * Solo actúa si el usuario es administrador.
     */
    public static function verificarYNotificar(): void
    {
        if (!RoleGuard::is_admin_check()) {
            return;
        }

        self::verificarProveedores();
        self::verificarResumenMensual();
    }

    // ──────────────────────────────────────────────
    //  Alerta de vencimiento de proveedores
    // ──────────────────────────────────────────────

    private static function verificarProveedores(): void
    {
        $hoy = date('Y-m-d');
        $flagKey = 'notif_proveedores_' . $hoy;

        if (self::flagYaEnviado($flagKey)) {
            return;
        }

        try {
            $db = new Database();
            $proveedorModel = new ModelProveedor($db);
            $proveedores = $proveedorModel->listarDeudas();

            if (empty($proveedores)) {
                self::marcarFlag($flagKey);
                return;
            }

            $diasLimite = 7;
            $hoyObj = new DateTime();
            $alertas = [];

            foreach ($proveedores as $p) {
                if (empty($p->proximo_vencimiento)) {
                    continue;
                }
                $fechaObj = new DateTime($p->proximo_vencimiento);
                $diff = $hoyObj->diff($fechaObj);
                $diasRestantes = (int)$diff->format('%r%a');
                if ($diasRestantes <= $diasLimite) {
                    $alertas[] = $p;
                }
            }

            if (!empty($alertas)) {
                $emailService = new \App\Services\EmailService();
                $emailService->notificarProveedoresVencimiento($alertas, $diasLimite);
            }
        } catch (Throwable $e) {
            error_log("NotificationChecker: Error en verificación de proveedores: " . $e->getMessage());
        }

        self::marcarFlag($flagKey);
    }

    // ──────────────────────────────────────────────
    //  Resumen mensual de actividad
    // ──────────────────────────────────────────────

    private static function verificarResumenMensual(): void
    {
        $marcaActual = date('Y-m');
        $flagKey = 'notif_resumen_' . $marcaActual;

        if (self::flagYaEnviado($flagKey)) {
            return;
        }

        try {
            $db = new Database();
            $dashboardModel = new ModelDashboard($db);
            $facturacionModel = new ModelFacturacion($db);

            $primerDiaMesAnterior = date('Y-m-01', strtotime('first day of last month'));
            $ultimoDiaMesAnterior  = date('Y-m-t', strtotime('last day of last month'));

            $ventas       = $dashboardModel->getVentasMesAnterior();
            $gastos       = $dashboardModel->getGastosMesAnterior();
            $clientes     = $dashboardModel->getClientesMesAnterior();
            $ordenes      = $dashboardModel->getOrdenesMesAnterior();
            $topProductos = $dashboardModel->getTopProductosMesAnterior(5);
            $inventario   = $dashboardModel->getResumenInventario();
            $utilidad     = $facturacionModel->obtenerReporteUtilidad($primerDiaMesAnterior, $ultimoDiaMesAnterior);

            $meses = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];
            $mesAnteriorNum = (int)date('m', strtotime('last month'));
            $anioAnterior   = date('Y', strtotime('last month'));
            $nombreMes = $meses[$mesAnteriorNum] ?? 'Desconocido';

            $ventas   = $ventas   ?: (object)['total' => 0, 'cantidad' => 0];
            $gastos   = $gastos   ?: (object)['total' => 0, 'cantidad' => 0];
            $clientes = $clientes ?: (object)['cantidad' => 0];
            $ordenes  = $ordenes  ?: (object)['cantidad' => 0];
            $utilidad = $utilidad ?: (object)[
                'total_ventas' => 0, 'total_costos' => 0,
                'total_servicios' => 0, 'ganancia_repuestos' => 0, 'utilidad_bruta' => 0
            ];
            $inventario = $inventario ?: (object)['total_productos' => 0, 'criticos' => 0, 'agotados' => 0];
            $topProductos = $topProductos ?: [];

            $emailService = new \App\Services\EmailService();
            $emailService->notificarResumenMensual(
                $ventas, $gastos, $utilidad, $clientes, $ordenes,
                $topProductos, $inventario, $nombreMes, $anioAnterior
            );
        } catch (Throwable $e) {
            error_log("NotificationChecker: Error en resumen mensual: " . $e->getMessage());
        }

        self::marcarFlag($flagKey);
    }

    // ──────────────────────────────────────────────
    //  Persistencia de flags (BD con fallback a sesión)
    // ──────────────────────────────────────────────

    /**
     * Devuelve true si el flag ya fue marcado (envío ya hecho).
     * Intenta leer de `table_system_flags`; si no existe, usa $_SESSION.
     */
    private static function flagYaEnviado(string $key): bool
    {
        // Fallback rápido en sesión para no consultar BD en cada request
        if (isset($_SESSION['_sysflags'][$key])) {
            return true;
        }

        try {
            $db = new Database();
            $db->query("SELECT valor FROM table_system_flags WHERE clave = :k LIMIT 1");
            $db->bind(':k', $key);
            $row = $db->single();
            if ($row && !empty($row->valor)) {
                $_SESSION['_sysflags'][$key] = $row->valor;
                return true;
            }
        } catch (Throwable $e) {
            // Tabla inexistente u otro error: usar solo $_SESSION
        }

        return false;
    }

    /**
     * Marca el flag como enviado. Escribe en BD (INSERT ... ON DUPLICATE KEY)
     * y también en $_SESSION como cache. Si la tabla no existe, ignora el error.
     *
     * v2.3 (2026-10-27) — FIX HY093:
     *   PDO con ATTR_EMULATE_PREPARES=false no permite reutilizar el mismo
     *   placeholder. Antes :v aparecía 2 veces (VALUES y ON DUPLICATE) y
     *   lanzaba "Invalid parameter number". Se separó en :v_ins y :v_upd.
     */
    private static function marcarFlag(string $key): void {
        $_SESSION['_sysflags'][$key] = date('Y-m-d H:i:s');

        try {
            $db = Database::getInstance();
            $db->query("INSERT INTO table_system_flags (clave, valor, actualizado_en)
                        VALUES (:k, :v_ins, NOW())
                        ON DUPLICATE KEY UPDATE valor = :v_upd, actualizado_en = NOW()");
            $db->bind(':k', $key);
            $db->bind(':v_ins', date('Y-m-d H:i:s'));
            $db->bind(':v_upd', date('Y-m-d H:i:s'));
            $db->execute();
        } catch (Throwable $e) {
            error_log('NotificationChecker::marcarFlag falló: ' . $e->getMessage());
        }
    }
}