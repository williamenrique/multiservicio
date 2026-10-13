<?php
/**
 * Modelo de Personal
 * Gestiona los datos de los empleados en la base de datos.
 * 
 * v2.1 (2026-10-09) — P3-08:
 *   • `gestionarUsuario()` refactorizado para arreglar el manejo del bind :pass:
 *       - En UPDATE: password solo se incluye en el SET si viene con valor.
 *       - En INSERT: password es obligatorio; si no viene, se retorna false
 *         y se loguea el intento fallido en vez de crear un usuario inválido.
 *       - Antes: en UPDATE se bindeaba :pass ANTES del query (que no lo
 *         incluía); en INSERT se bindeaba :pass ANTES del query también
 *         (mismo bug). El rebind posterior de :sid/:un/:rid podía pisar :pass.
 */
class ModelPersonal {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    /**
     * Lista el personal con filtros y paginación
     */
    public function listar($limit = null, $offset = null, $search = null) {
        $sql = "SELECT s.*, u.username, u.role_id, r.nombre_rol as system_role 
                FROM table_staff s 
                LEFT JOIN table_usuarios u ON s.id = u.staff_id 
                LEFT JOIN table_roles r ON u.role_id = r.id";
        
        if ($search) {
            $sql .= " WHERE s.nombre LIKE :search OR s.id LIKE :search OR s.cedula LIKE :search";
        }

        $sql .= " ORDER BY s.nombre ASC";
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $this->db->query($sql);
        if ($search) $this->db->bind(':search', "%$search%");
        if ($limit !== null && $offset !== null) {
            $this->db->bind(':limit', (int)$limit);
            $this->db->bind(':offset', (int)$offset);
        }
        return $this->db->resultSet();
    }

    public function contarTotal() {
        $this->db->query("SELECT COUNT(*) as total FROM table_staff");
        return (int)$this->db->single()->total;
    }

    public function contarFiltrados($search) {
        $this->db->query("SELECT COUNT(*) as total FROM table_staff WHERE nombre LIKE :search OR id LIKE :search OR cedula LIKE :search");
        $this->db->bind(':search', "%$search%");
        return (int)$this->db->single()->total;
    }

    public function obtenerPorId($id) {
        $this->db->query("SELECT * FROM table_staff WHERE id = :id");
        $this->db->bind(':id', mb_strtoupper($id, 'UTF-8'));
        return $this->db->single();
    }

    public function listarRoles() {
        $this->db->query("SELECT * FROM table_roles ORDER BY id ASC");
        return $this->db->resultSet();
    }

    /**
     * Obtiene el último correlativo numérico de los IDs según el prefijo (ej: MEC-, STAFF-)
     */
    public function obtenerUltimoCorrelativo($prefix = 'STAFF-') {
        $this->db->query("SELECT id FROM table_staff 
                          WHERE id LIKE :prefix 
                          ORDER BY CAST(SUBSTRING_INDEX(id, '-', -1) AS UNSIGNED) DESC 
                          LIMIT 1");
        $this->db->bind(':prefix', $prefix . '%');
        $result = $this->db->single();
        if ($result) {
            $parts = explode('-', $result->id);
            return (int) end($parts);
        }
        return 0;
    }

    public function crear($datos) {
        $this->db->query("INSERT INTO table_staff (id, cedula, nombre, cargo, telefono, email, direccion, foto) 
                          VALUES (:id, :cedula, :nombre, :cargo, :telefono, :email, :direccion, :foto)");
        $this->db->bind(':id', mb_strtoupper($datos['id'] ?? '', 'UTF-8'));
        $this->db->bind(':cedula', mb_strtoupper($datos['cedula'] ?? '', 'UTF-8'));
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'] ?? '', 'UTF-8'));
        $this->db->bind(':cargo', mb_strtoupper($datos['cargo'] ?? '', 'UTF-8'));
        $this->db->bind(':telefono', $datos['telefono'] ?? '');
        $this->db->bind(':email', mb_strtolower($datos['email'] ?? '', 'UTF-8'));
        $this->db->bind(':direccion', mb_strtoupper($datos['direccion'] ?? '', 'UTF-8'));
        $this->db->bind(':foto', 'img/default.png');
        
        logAction('PERSONAL', 'CREATE', "Se registró nuevo personal: " . ($datos['nombre'] ?? 'Desconocido'));
        
        return $this->db->execute();
    }

    public function actualizar($datos) {
        $this->db->query("UPDATE table_staff 
                          SET cedula = :cedula, nombre = :nombre, cargo = :cargo, telefono = :telefono, 
                              email = :email, direccion = :direccion 
                          WHERE id = :id");
        $this->db->bind(':id', mb_strtoupper($datos['id'] ?? '', 'UTF-8'));
        $this->db->bind(':cedula', mb_strtoupper($datos['cedula'] ?? '', 'UTF-8'));
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'] ?? '', 'UTF-8'));
        $this->db->bind(':cargo', mb_strtoupper($datos['cargo'] ?? '', 'UTF-8'));
        $this->db->bind(':telefono', $datos['telefono'] ?? '');
        $this->db->bind(':email', mb_strtolower($datos['email'] ?? '', 'UTF-8'));
        $this->db->bind(':direccion', mb_strtoupper($datos['direccion'] ?? '', 'UTF-8'));
        
        logAction('PERSONAL', 'UPDATE', "Se actualizaron datos del personal ID: " . $datos['id']);
        
        return $this->db->execute();
    }

    /**
     * Vincula o actualiza la cuenta de usuario de un miembro del staff.
     * 
     * FIX P3-08:
     *   • UPDATE: si `$userData['password']` viene vacío, NO se toca la columna password.
     *   • INSERT: si `$userData['password']` viene vacío, se retorna false y se loguea
     *     el intento. No se crea un usuario con password vacío.
     *   • Los binds se hacen en el orden correcto (después del query).
     */
    public function gestionarUsuario($staffId, $userData) {
        $staffIdUpper = mb_strtoupper($staffId, 'UTF-8');
        $usernameUpper = mb_strtoupper($userData['username'] ?? '', 'UTF-8');
        $roleId = (int)($userData['role_id'] ?? 0);
        $password = !empty($userData['password']) ? $userData['password'] : null;

        // Verificar si ya existe un usuario vinculado a este staff
        $this->db->query("SELECT id FROM table_usuarios WHERE staff_id = :sid");
        $this->db->bind(':sid', $staffIdUpper);
        $existe = $this->db->single();

        if ($existe) {
            // ─── UPDATE ───
            if ($password !== null) {
                $this->db->query("UPDATE table_usuarios 
                                  SET username = :un, role_id = :rid, password = :pass 
                                  WHERE staff_id = :sid");
                $this->db->bind(':pass', $password);
            } else {
                $this->db->query("UPDATE table_usuarios 
                                  SET username = :un, role_id = :rid 
                                  WHERE staff_id = :sid");
            }
            $this->db->bind(':sid', $staffIdUpper);
            $this->db->bind(':un', $usernameUpper);
            $this->db->bind(':rid', $roleId);
            return $this->db->execute();
        } else {
            // ─── INSERT ───
            if ($password === null) {
                error_log("ModelPersonal::gestionarUsuario: intento de crear usuario sin password para staff {$staffIdUpper}");
                return false;
            }
            $this->db->query("INSERT INTO table_usuarios (staff_id, username, password, role_id) 
                              VALUES (:sid, :un, :pass, :rid)");
            $this->db->bind(':sid', $staffIdUpper);
            $this->db->bind(':un', $usernameUpper);
            $this->db->bind(':pass', $password);
            $this->db->bind(':rid', $roleId);
            return $this->db->execute();
        }
    }

    public function eliminarUsuario($staffId) {
        $this->db->query("DELETE FROM table_usuarios WHERE staff_id = :sid");
        $this->db->bind(':sid', mb_strtoupper($staffId, 'UTF-8'));
        return $this->db->execute();
    }

    public function eliminar($id) {
        $this->db->query("DELETE FROM table_staff WHERE id = :id");
        $this->db->bind(':id', mb_strtoupper($id, 'UTF-8'));
        logAction('PERSONAL', 'DELETE', "Se eliminó al personal con ID: " . $id);
        return $this->db->execute();
    }

    public function verificarCedulaUnica($cedula, $id = null) {
        $sql = "SELECT COUNT(*) as total FROM table_staff WHERE cedula = :cedula";
        if ($id) {
            $sql .= " AND id != :id";
        }
        $this->db->query($sql);
        $this->db->bind(':cedula', mb_strtoupper(trim($cedula), 'UTF-8'));
        if ($id) {
            $this->db->bind(':id', mb_strtoupper($id, 'UTF-8'));
        }
        return (int)$this->db->single()->total > 0;
    }

    public function verificarUsernameUnico($username, $staffId = null) {
        $sql = "SELECT COUNT(*) as total FROM table_usuarios WHERE username = :un";
        if ($staffId) {
            $sql .= " AND staff_id != :sid";
        }
        $this->db->query($sql);
        $this->db->bind(':un', mb_strtoupper(trim($username), 'UTF-8'));
        if ($staffId) {
            $this->db->bind(':sid', mb_strtoupper($staffId, 'UTF-8'));
        }
        return (int)$this->db->single()->total > 0;
    }

    public function verificarEmailUnico($email, $id = null) {
        $sql = "SELECT COUNT(*) as total FROM table_staff WHERE email = :email";
        if ($id) {
            $sql .= " AND id != :id";
        }
        $this->db->query($sql);
        $this->db->bind(':email', mb_strtolower(trim($email), 'UTF-8'));
        if ($id) {
            $this->db->bind(':id', mb_strtoupper($id, 'UTF-8'));
        }
        return (int)$this->db->single()->total > 0;
    }
}