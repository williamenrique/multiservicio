<?php
/**
 * Controlador de Personal
 * Maneja la gestión del staff y la vinculación de cuentas de usuario 2.0.
 */
class ControllerPersonal extends Controller {
    private $personalModel;

    public function __construct() {
        AuthGuard::handle();
        RoleGuard::isAdmin(); // Solo administradores gestionan personal
        $this->personalModel = $this->model('Personal');
    }

    public function index() {
        $data = [
            'titulo' => 'Gestión de Personal',
            'roles' => $this->personalModel->listarRoles()
        ];
        $this->view('personal/index', $data);
    }

    /**
     * Endpoint para el listado paginado del staff (AJAX)
     */
    public function listar() {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $search = $_GET['search'] ?? null;

        $data = $this->personalModel->listar($limit, $offset, $search);
        $total = $this->personalModel->contarTotal();
        $filtrados = $search ? $this->personalModel->contarFiltrados($search) : $total;

        return $this->jsonResponse([
            'success' => true,
            'data' => $data,
            'total' => $total,
            'totalFiltrados' => $filtrados
        ]);
    }

    /**
     * Guarda o actualiza un registro de personal.
     * Soluciona el error 404 y gestiona IDs dinámicos (MEC-001, STAFF-001).
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $idIngresado = trim($input['id'] ?? '');

            $v = new Validator($input);
            $v->required(['cedula', 'nombre', 'cargo']);

            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'error' => implode(' ', $v->getErrors())], 400);
            }

            // Verificar si el ID ya existe para decidir si es UPDATE o CREATE
            $existe = $this->personalModel->obtenerPorId($idIngresado);

            if ($this->personalModel->verificarCedulaUnica($input['cedula'], $existe ? $idIngresado : null)) {
                return $this->jsonResponse(['success' => false, 'error' => 'La cédula ya está registrada en el sistema.'], 400);
            }

            if ($existe) {
                // MODO ACTUALIZACIÓN
                if ($this->personalModel->actualizar($input)) {
                    // Vincular o actualizar cuenta de usuario si se enviaron datos de cuenta
                    if (!empty($input['username']) && !empty($input['role_id'])) {
                        if ($this->personalModel->verificarUsernameUnico($input['username'], $idIngresado)) {
                            return $this->jsonResponse(['success' => false, 'error' => 'El nombre de usuario ya está en uso por otro empleado.'], 400);
                        }
                        
                        if (!empty($input['password'])) {
                            $input['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
                        }
                        
                        $this->personalModel->gestionarUsuario($idIngresado, $input);
                    }
                    return $this->jsonResponse(['success' => true, 'mensaje' => 'Datos del personal actualizados.']);
                }
                return $this->jsonResponse(['success' => false, 'error' => 'No se realizaron cambios en el registro.'], 500);
            } else {
                // MODO CREACIÓN: Lógica de ID dinámico por iniciales
                $idIngresadoUpper = mb_strtoupper($idIngresado, 'UTF-8');

                // Determinamos si es un prefijo o un ID completo. 
                // Si está vacío, termina en guion o no tiene el formato de 3 dígitos finales, autogeneramos.
                if (empty($idIngresado) || substr($idIngresado, -1) === '-' || !preg_match('/-[0-9]{3,}$/', $idIngresado)) {
                    $prefix = !empty($idIngresadoUpper) ? $idIngresadoUpper : 'STAFF-';
                    
                    // Asegurar formato de prefijo (ej: MEC -> MEC-)
                    if (strpos($prefix, '-') === false) {
                        $prefix .= '-';
                    } else {
                        // Si escribió algo como "MEC-00", limpiamos para obtener solo el prefijo base
                        $parts = explode('-', $prefix);
                        $prefix = $parts[0] . '-';
                    }

                    $ultimoNum = $this->personalModel->obtenerUltimoCorrelativo($prefix);
                    $input['id'] = $prefix . str_pad($ultimoNum + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    // Si el usuario ingresó un ID completo que no existe, lo respetamos
                    $input['id'] = $idIngresadoUpper;
                }

                if ($this->personalModel->crear($input)) {
                    // Vincular cuenta de usuario si se enviaron datos de cuenta
                    if (!empty($input['username']) && !empty($input['role_id'])) {
                        if ($this->personalModel->verificarUsernameUnico($input['username'], $input['id'])) {
                            return $this->jsonResponse(['success' => true, 'mensaje' => 'Personal registrado, pero el usuario ya existe.']);
                        }

                        if (!empty($input['password'])) {
                            $input['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
                        }

                        $this->personalModel->gestionarUsuario($input['id'], $input);
                    }
                    return $this->jsonResponse(['success' => true, 'mensaje' => 'Registrado con éxito con ID: ' . $input['id']]);
                }
            }

            return $this->jsonResponse(['success' => false, 'error' => 'Error crítico al procesar la solicitud.'], 500);
        }
    }

    public function editar($id) {
        $staff = $this->personalModel->obtenerPorId($id);
        if (!$staff) throw new AppException("Empleado no encontrado", 404);
        return $this->jsonResponse(['success' => true, 'data' => $staff]);
    }

    /**
     * Vincula o actualiza el usuario de acceso para un miembro del staff
     */
    public function guardarUsuario() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validación preventiva de campos
            if (empty($input['staff_id']) || empty($input['username'])) {
                return $this->jsonResponse(['success' => false, 'error' => 'Faltan datos obligatorios para la cuenta.'], 400);
            }

            if ($this->personalModel->verificarUsernameUnico($input['username'], $input['staff_id'])) {
                return $this->jsonResponse(['success' => false, 'error' => 'El nombre de usuario ya está en uso.'], 400);
            }

            if (!empty($input['password'])) {
                $input['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            }

            if ($this->personalModel->gestionarUsuario($input['staff_id'], $input)) {
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Cuenta de acceso actualizada.']);
            }
            return $this->jsonResponse(['success' => false, 'error' => 'Error al procesar usuario.'], 500);
        }
    }

    public function eliminar($id) {
        $this->personalModel->eliminarUsuario($id);
        return $this->jsonResponse(['success' => $this->personalModel->eliminar($id)]);
    }

    /**
     * Verifica si una cédula ya existe (AJAX para validación en tiempo real)
     */
    public function verificarCedula() {
        $value = $_GET['value'] ?? null;
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
        
        $exists = $this->personalModel->verificarCedulaUnica($value, $id);
        return $this->jsonResponse(['exists' => $exists]);
    }

    /**
     * Verifica si un ID de personal ya existe (AJAX)
     */
    public function verificarId() {
        $value = $_GET['value'] ?? null;
        if (!$value) return $this->jsonResponse(['exists' => false]);
        
        $exists = $this->personalModel->obtenerPorId($value);
        return $this->jsonResponse(['exists' => !empty($exists)]);
    }

    /**
     * Verifica si un nombre de usuario ya está en uso (AJAX para validación en tiempo real)
     */
    public function verificarUsername() {
        $value = $_GET['value'] ?? null;
        $staffId = !empty($_GET['id']) ? $_GET['id'] : null;

        $exists = $this->personalModel->verificarUsernameUnico($value, $staffId);
        return $this->jsonResponse(['exists' => $exists]);
    }

    /**
     * Verifica si un email ya existe (AJAX para validación en tiempo real)
     */
    public function verificarEmail() {
        $value = $_GET['value'] ?? null;
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
        
        $exists = $this->personalModel->verificarEmailUnico($value, $id);
        return $this->jsonResponse(['exists' => $exists]);
    }
}