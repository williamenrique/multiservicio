<?php
class ControllerCaja extends Controller {
    private $cajaModel;

    public function __construct() {
        AuthGuard::handle();
        $this->cajaModel = $this->model('Caja');
    }

    public function estado() {
        $sesion = $this->cajaModel->obtenerSesionActiva();
        $this->jsonResponse([
            'success' => true,
            'abierta' => (bool)$sesion,
            'sesion' => $sesion
        ]);
    }

    public function abrir() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Restricción de seguridad: Solo el Rol 1 puede abrir la caja
            if ((int)$_SESSION['user_role_id'] !== 1) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'No tiene permisos para abrir la caja. Solicite al administrador.'], 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($this->cajaModel->obtenerSesionActiva()) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'Ya existe una sesión abierta.'], 400);
            }

            $id = $this->cajaModel->abrirSesion([
                'usuario_id' => $_SESSION['user_id'],
                'monto_inicial' => $input['monto_inicial'] ?? 0
            ]);

            if ($id) {
                logAction('CAJA', 'APERTURA', "Caja abierta con monto: " . ($input['monto_inicial'] ?? 0));
                $this->jsonResponse(['success' => true, 'mensaje' => 'Caja abierta con éxito.']);
            }
        }
    }

    public function cerrar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Restricción de seguridad: Solo el Rol 1 puede cerrar la caja
            if ((int)$_SESSION['user_role_id'] !== 1) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'No tiene permisos para realizar el arqueo. Solicite al administrador.'], 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $sesion = $this->cajaModel->obtenerSesionActiva();

            if (!$sesion) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'No hay sesión activa para cerrar.'], 400);
            }

            $montoVentasEfectivo = $this->cajaModel->obtenerTotalesSesion($sesion->id);
            $montoEsperado = (float)$sesion->monto_inicial + $montoVentasEfectivo;
            $montoReal = (float)$input['monto_real'];

            $res = $this->cajaModel->cerrarSesion($sesion->id, $montoReal, $montoEsperado);

            if ($res) {
                logAction('CAJA', 'CIERRE', "Caja cerrada. Esperado: $montoEsperado, Real: $montoReal, Dif: " . ($montoReal - $montoEsperado));
                $this->jsonResponse(['success' => true, 'mensaje' => 'Caja cerrada correctamente.']);
            }
        }
    }
}