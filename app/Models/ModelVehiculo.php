<?php
class ModelVehiculo {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function buscarPorPlaca($placa) {
        $this->db->query("SELECT v.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono 
                          FROM table_vehiculos v 
                          INNER JOIN table_clientes c ON v.cliente_id = c.id 
                          WHERE v.placa = :placa");
        $this->db->bind(':placa', $placa);
        return $this->db->single();
    }

    public function registrar($data) {
        $this->db->query("INSERT INTO table_vehiculos (placa, marca, modelo, anio, color, cliente_id) 
                          VALUES (:placa, :marca, :modelo, :anio, :color, :cliente_id)");
        $this->db->bind(':placa', strtoupper($data['placa']));
        $this->db->bind(':marca', mb_strtoupper($data['marca'], 'UTF-8'));
        $this->db->bind(':modelo', mb_strtoupper($data['modelo'], 'UTF-8'));
        $this->db->bind(':anio', $data['anio']);
        $this->db->bind(':color', mb_strtoupper($data['color'], 'UTF-8'));
        $this->db->bind(':cliente_id', $data['cliente_id']);
        return $this->db->execute();
    }

    public function obtenerHistorial($placa) {
        $this->db->query("SELECT os.*, s.nombre as mecanico_nombre 
                          FROM table_ordenes_servicio os
                          LEFT JOIN table_staff s ON os.mecanico_id = s.id
                          WHERE os.placa = :placa 
                          ORDER BY os.fecha_ingreso DESC");
        $this->db->bind(':placa', $placa);
        return $this->db->resultSet();
    }

    /**
     * Busca vehículos por placa, marca o modelo para el buscador global.
     */
    public function searchVehicles($term) {
        $this->db->query("SELECT placa, marca, modelo FROM table_vehiculos
                          WHERE placa LIKE :term OR marca LIKE :term OR modelo LIKE :term LIMIT 5");
        $this->db->bind(':term', "%$term%");
        return $this->db->resultSet();
    }
}