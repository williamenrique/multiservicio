<?php
/**
 * Modelo Base
 * Proporciona la conexión a la base de datos a todos los modelos descendientes.
 *
 * v2.1 (2026-10-26) — PERFORMANCE:
 *   • Usa Database::getInstance() en vez de `new Database()`.
 *     Todos los modelos ahora comparten la MISMA conexión PDO dentro
 *     del request. Antes cada modelo abría una nueva.
 */
class Model {
    protected $db;

    public function __construct(Database $db = null) {
        $this->db = $db ?? Database::getInstance();
    }
}