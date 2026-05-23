<?php
/**
 * Modelo Base
 * Proporciona la conexión a la base de datos a todos los modelos descendientes.
 */
class Model {
    protected $db;

    public function __construct() {
        // Instancia la clase de conexión para que esté disponible en los hijos
        $this->db = new Database();
    }
}