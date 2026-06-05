<?php
/**
 * Modelo de Personal
 * Gestiona los datos de los empleados en la base de datos.
 */
class ModelPersonal {
    private $db;

    /**
     * Constructor del modelo
     * @param Database|null $db Instancia de base de datos compartida
     */
    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    /**
     * Lista el personal con filtros y paginación
     * @param int|null $limit Límite de registros
     * @param int|null $offset Desplazamiento
     * @param string|null $search Término de búsqueda
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

    public function crear($datos) {
        $this->db->query("INSERT INTO table_staff (id, cedula, nombre, cargo, telefono, email, direccion, foto, foto_frente) 
                          VALUES (:id, :cedula, :nombre, :cargo, :telefono, :email, :direccion, :foto, :foto_frente)");
        $this->db->bind(':id', mb_strtoupper($datos['id'] ?? '', 'UTF-8'));
        $this->db->bind(':cedula', mb_strtoupper($datos['cedula'] ?? '', 'UTF-8'));
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'] ?? '', 'UTF-8'));
        $this->db->bind(':cargo', mb_strtoupper($datos['cargo'] ?? '', 'UTF-8'));
        $this->db->bind(':telefono', $datos['telefono'] ?? '');
        $this->db->bind(':email', mb_strtolower($datos['email'] ?? '', 'UTF-8'));
        $this->db->bind(':direccion', mb_strtoupper($datos['direccion'] ?? '', 'UTF-8'));
        $this->db->bind(':foto', 'img/default.png');
        $this->db->bind(':foto_frente', 'img/default.png');
        
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

    public function gestionarUsuario($staffId, $userData) {
        $this->db->query("SELECT id FROM table_usuarios WHERE staff_id = :sid");
        $this->db->bind(':sid', $staffId);
        $existe = $this->db->single();

        if ($existe) {
            $sql = "UPDATE table_usuarios SET username = :un, role_id = :rid";
            if (!empty($userData['password'])) $sql .= ", password = :pass";
            $sql .= " WHERE staff_id = :sid";
            
            $this->db->query($sql);
            if (!empty($userData['password'])) $this->db->bind(':pass', $userData['password']);
        } else {
            $this->db->query("INSERT INTO table_usuarios (staff_id, username, password, role_id) 
                              VALUES (:sid, :un, :pass, :rid)");
            $this->db->bind(':pass', $userData['password']);
        }
        
        $this->db->bind(':sid', mb_strtoupper($staffId, 'UTF-8'));
        $this->db->bind(':un', mb_strtoupper($userData['username'], 'UTF-8'));
        $this->db->bind(':rid', $userData['role_id']);
        return $this->db->execute();
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
}