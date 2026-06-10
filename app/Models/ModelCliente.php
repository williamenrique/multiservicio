<?php
/**
 * Modelo de Cliente
 * Gestiona la persistencia de datos de clientes en la base de datos.
 */
class ModelCliente {
    private $db;

    /**
     * Constructor del modelo
     * @param Database|null $db Instancia de base de datos compartida
     */
    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    /**
     * Lista clientes con soporte para búsqueda y paginación
     */
    public function listar($limit = null, $offset = null, $search = null) {
        $sql = "SELECT * FROM table_clientes";
        
        if ($search) {
            $sql .= " WHERE nombre LIKE :search OR id LIKE :search OR telefono LIKE :search";
        }

        $sql .= " ORDER BY fecha_registro DESC";
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        
        $this->db->query($sql);
        
        if ($search) {
            $this->db->bind(':search', "%$search%");
        }
        
        if ($limit !== null && $offset !== null) {
            $this->db->bind(':limit', (int)$limit);
            $this->db->bind(':offset', (int)$offset);
        }

        return $this->db->resultSet();
    }

    public function contarTotal() {
        $this->db->query("SELECT COUNT(*) as total FROM table_clientes");
        return (int)$this->db->single()->total;
    }

    public function contarFiltrados($search) {
        $this->db->query("SELECT COUNT(*) as total FROM table_clientes 
                          WHERE nombre LIKE :search 
                          OR id LIKE :search 
                          OR telefono LIKE :search");
        $this->db->bind(':search', "%$search%");
        return (int)$this->db->single()->total;
    }

    /**
     * Buscar un cliente por su ID
     */
    public function obtenerPorId($id) {
        $this->db->query("SELECT * FROM table_clientes WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Registrar un nuevo cliente
     */
    public function crear($datos) {
        $this->db->query("INSERT INTO table_clientes (id, nombre, email, telefono, direccion) 
                          VALUES (:id, :nombre, :email, :telefono, :direccion)");
        
        $this->db->bind(':id', mb_strtoupper($datos['id'], 'UTF-8'));
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'], 'UTF-8'));
        $this->db->bind(':email', mb_strtolower($datos['email'], 'UTF-8'));
        $this->db->bind(':telefono', $datos['telefono']);
        $this->db->bind(':direccion', mb_strtoupper($datos['direccion'], 'UTF-8'));

        return $this->db->execute();
    }

    /**
     * Actualizar datos de un cliente existente
     */
    public function actualizar($datos) {
        $this->db->query("UPDATE table_clientes 
                          SET nombre = :nombre, 
                              email = :email, 
                              telefono = :telefono, 
                              direccion = :direccion 
                          WHERE id = :id");
        
        $this->db->bind(':id', mb_strtoupper($datos['id'], 'UTF-8'));
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'], 'UTF-8'));
        $this->db->bind(':email', mb_strtolower($datos['email'], 'UTF-8'));
        $this->db->bind(':telefono', $datos['telefono']);
        $this->db->bind(':direccion', mb_strtoupper($datos['direccion'], 'UTF-8'));

        return $this->db->execute();
    }

    /**
     * Eliminar un cliente
     */
    public function eliminar($id) {
        $this->db->query("DELETE FROM table_clientes WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Obtiene todos los vehículos registrados a nombre de un cliente
     * @param string $clienteId ID/Cédula del cliente
     */
    public function obtenerVehiculos($clienteId) {
        $this->db->query("SELECT * FROM table_vehiculos WHERE cliente_id = :id ORDER BY marca, modelo ASC");
        $this->db->bind(':id', $clienteId);
        return $this->db->resultSet();
    }

    /**
     * Busca clientes por ID, nombre o teléfono para el buscador global.
     */
    public function searchClients($term) {
        $this->db->query("SELECT id, nombre, telefono FROM table_clientes
                          WHERE id LIKE :term OR nombre LIKE :term OR telefono LIKE :term LIMIT 5");
        $this->db->bind(':term', "%$term%");
        return $this->db->resultSet();
    }

    public function verificarIdUnico($id) {
        $this->db->query("SELECT COUNT(*) as total FROM table_clientes WHERE id = :id");
        $this->db->bind(':id', mb_strtoupper(trim($id), 'UTF-8'));
        $result = $this->db->single();
        return (int)$result->total > 0;
    }
}