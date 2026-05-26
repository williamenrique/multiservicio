<?php
/**
 * Modelo de Personal
 * Gestiona los datos de los empleados en la base de datos.
 */
class ModelPlantilla {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    
}