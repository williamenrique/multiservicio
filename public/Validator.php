<?php
/**
 * Clase Validator
 * Proporciona un motor simple para validar entradas de usuario.
 */
class Validator {
    private $data;
    private $errors = [];
    private $db;

    public function __construct($data) {
        $this->data = $data;
        $this->db = new Database();
    }

    /**
     * Verifica campos obligatorios
     */
    public function required($fields) {
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
                $this->errors[$field] = "El campo " . str_replace('_', ' ', $field) . " es obligatorio.";
            }
        }
        return $this;
    }

    /**
     * Verifica que un valor sea único en la base de datos
     */
    public function unique($field, $table, $column, $exceptId = null) {
        if (!isset($this->data[$field])) return $this;

        $sql = "SELECT id FROM {$table} WHERE {$column} = :val";
        if ($exceptId) $sql .= " AND id != :id";

        $this->db->query($sql);
        $this->db->bind(':val', $this->data[$field]);
        if ($exceptId) $this->db->bind(':id', $exceptId);

        if ($this->db->single()) {
            $this->errors[$field] = "Este " . str_replace('_', ' ', $field) . " ya se encuentra registrado.";
        }
        return $this;
    }

    /**
     * Verifica formato de email
     */
    public function email($field) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "El formato de correo no es válido.";
        }
        return $this;
    }

    public function success() {
        return empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }
}