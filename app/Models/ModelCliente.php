<?php
/**
 * Modelo de Cliente
 * Gestiona la persistencia de datos de clientes en la base de datos.
 * 
 * v3.0 (2026-10-13):
 *   • `searchClients()` mejorado:
 *       - Ahora busca también por email (antes solo id/nombre/teléfono).
 *       - Parámetro `$limit` configurable (default 20).
 *       - ORDER BY inteligente: coincidencia exacta por id → empieza con el
 *         término → contiene el término → resto. Desempate por nombre ASC.
 *       - Índice del ORDER BY usa collation utf8mb4_general_ci (case-insensitive).
 * 
 * v2.0: métodos listar/contar/obtenerPorId/crear/actualizar/eliminar/
 *       obtenerVehiculos/verificarIdUnico sin cambios.
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
     * Lista clientes con soporte para búsqueda y paginación.
     */
    public function listar($limit = null, $offset = null, $search = null) {
        $sql = "SELECT * FROM table_clientes";
        $params = [];
        
        if ($search) {
            $sql .= " WHERE nombre LIKE :search_nom 
                        OR id LIKE :search_id 
                        OR telefono LIKE :search_tel 
                        OR email LIKE :search_email";
            $like = "%$search%";
            $params[':search_nom']   = $like;
            $params[':search_id']    = $like;
            $params[':search_tel']   = $like;
            $params[':search_email'] = $like;
        }

        $sql .= " ORDER BY fecha_registro DESC";
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit']  = (int)$limit;
            $params[':offset'] = (int)$offset;
        }
        
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);

        return $this->db->resultSet();
    }

    public function contarTotal() {
        $this->db->query("SELECT COUNT(*) as total FROM table_clientes");
        return (int)$this->db->single()->total;
    }

    public function contarFiltrados($search) {
        $like = "%$search%";
        $this->db->query("SELECT COUNT(*) as total FROM table_clientes 
                          WHERE nombre LIKE :search_nom 
                             OR id LIKE :search_id 
                             OR telefono LIKE :search_tel
                             OR email LIKE :search_email");
        $this->db->bind(':search_nom',   $like);
        $this->db->bind(':search_id',    $like);
        $this->db->bind(':search_tel',   $like);
        $this->db->bind(':search_email', $like);
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
     * Busca clientes por término para el autocompletado global.
     * 
     * v3.0: 
     *   • Busca por id, nombre, teléfono Y email.
     *   • Parámetro $limit configurable (default 20).
     *   • Orden inteligente: exacta por id → empieza con término → contiene → resto.
     * 
     * @param string $term  Término a buscar
     * @param int    $limit Máximo de resultados (default 20)
     * @return array
     */
    public function searchClients($term, $limit = 20) {
        $term = trim((string)$term);
        if ($term === '') {
            return [];
        }

        $like      = '%' . $term . '%';
        $startLike = $term . '%';

        $this->db->query("
            SELECT id, nombre, telefono, email, direccion
            FROM table_clientes
            WHERE id LIKE :term_id
               OR nombre LIKE :term_nom
               OR telefono LIKE :term_tel
               OR email LIKE :term_email
            ORDER BY 
                CASE
                    WHEN id = :term_exact THEN 1
                    WHEN nombre LIKE :term_start THEN 2
                    WHEN nombre LIKE :term_contains THEN 3
                    ELSE 4
                END,
                nombre ASC
            LIMIT :limit
        ");

        $this->db->bind(':term_id',       $like);
        $this->db->bind(':term_nom',      $like);
        $this->db->bind(':term_tel',      $like);
        $this->db->bind(':term_email',    $like);
        $this->db->bind(':term_exact',    $term);
        $this->db->bind(':term_start',    $startLike);
        $this->db->bind(':term_contains', $like);
        $this->db->bind(':limit',         (int)$limit);

        return $this->db->resultSet();
    }

    public function verificarIdUnico($id) {
        $this->db->query("SELECT COUNT(*) as total FROM table_clientes WHERE id = :id");
        $this->db->bind(':id', mb_strtoupper(trim($id), 'UTF-8'));
        $result = $this->db->single();
        return (int)$result->total > 0;
    }

    /**
     * Búsqueda general para el módulo de Reportes/Auditoría (searchClients).
     * Mantiene compatibilidad con el nombre `buscar` por si algún módulo lo usa.
     * 
     * @deprecated Usar searchClients(). Mantenido por retrocompatibilidad.
     */
    public function buscar($term, $limit = 20) {
        return $this->searchClients($term, $limit);
    }
}