<?php
/**
 * Helper de verificación de notificaciones automáticas al iniciar sesión.
 * 
 * Centraliza la lógica de:
 *  - Alerta de vencimiento de proveedores (admin, una vez al día)
 *  - Resumen mensual de actividad (admin, al cambiar de mes)
 *
 * Se llama desde ControllerAuth::login() después de autenticar.
 */

class NotificationChecker
{
    /**
     * Ejecuta todas las verificaciones de notificaciones para el usuario autenticado.
     * Solo actúa si el usuario es administrador.
     */
    public static function verificarYNotificar(): void
    {
        // Solo para administradores
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

        // Solo una vez al día
        if (isset($_SESSION['ultima_verificacion_proveedores']) && $_SESSION['ultima_verificacion_proveedores'] === $hoy) {
            return;
        }

        try {
            $proveedorModel = new ModelProveedor();
            $proveedores = $proveedorModel->listarDeudas();

            if (empty($proveedores)) {
                $_SESSION['ultima_verificacion_proveedores'] = $hoy;
                return;
            }

            // Filtrar proveedores con vencimiento dentro de 7 días o ya vencidos
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
        } catch (Exception $e) {
            error_log("RequirementChecker: Error en verificación de proveedores: " . $e->getMessage());
        }

        $_SESSION['ultima_verificacion_proveedores'] = $hoy;
    }

    // ──────────────────────────────────────────────
    //  Resumen mensual de actividad
    // ──────────────────────────────────────────────

    private static function verificarResumenMensual(): void
    {
        $marcaActual = date('Y-m');

        // Solo se envía una vez por mes
        if (isset($_SESSION['ultimo_resumen_enviado']) && $_SESSION['ultimo_resumen_enviado'] === $marcaActual) {
            return;
        }

        // Solo enviamos si ya pasó el primer día del mes (estamos en un mes nuevo)
        // y hay un mes anterior que resumir
        $diaActual = (int)date('d');
        if ($diaActual < 1) {
            return; // nunca ocurre, pero por seguridad
        }

        try {
            $dashboardModel = new ModelDashboard();
            $facturacionModel = new ModelFacturacion();

            // Calcular rango del mes anterior
            $primerDiaMesAnterior = date('Y-m-01', strtotime('first day of last month'));
            $ultimoDiaMesAnterior  = date('Y-m-t', strtotime('last day of last month'));

            // Obtener datos
            $ventas       = $dashboardModel->getVentasMesAnterior();
            $gastos       = $dashboardModel->getGastosMesAnterior();
            $clientes     = $dashboardModel->getClientesMesAnterior();
            $ordenes      = $dashboardModel->getOrdenesMesAnterior();
            $topProductos = $dashboardModel->getTopProductosMesAnterior(5);
            $inventario   = $dashboardModel->getResumenInventario();
            $utilidad     = $facturacionModel->obtenerReporteUtilidad($primerDiaMesAnterior, $ultimoDiaMesAnterior);

            // Nombres de meses en español
            $meses = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];
            $mesAnteriorNum = (int)date('m', strtotime('last month'));
            $anioAnterior   = date('Y', strtotime('last month'));
            $nombreMes = $meses[$mesAnteriorNum] ?? 'Desconocido';

            // Asegurar que todos los valores sean objetos (no null)
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
        } catch (Exception $e) {
            error_log("RequirementChecker: Error en resumen mensual: " . $e->getMessage());
        }

        $_SESSION['ultimo_resumen_enviado'] = $marcaActual;
    }
}