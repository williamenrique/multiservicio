<?php
/**
 * Modelo Base
 * Proporciona la conexión a la base de datos a todos los modelos descendientes.
 */
class Model {
    protected $db;

    /**
     * Permite inyectar una instancia de Database. 
     * Si no se provee una, crea una nueva por defecto.
     * Esto facilita enormemente las pruebas unitarias (Mocking).
     */
    public function __construct(Database $db = null) {
        $this->db = $db ?? new Database();
    }
}