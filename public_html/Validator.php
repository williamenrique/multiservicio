<?php
/**
 * Clase Validator
 * Proporciona un motor simple para validar entradas de usuario.
 */
class Validator {
    private $data;
    private $errors = [];
    private $db;

    public function __construct($data, Database $db = null) {
        $this->data = $data;
        $this->db = $db ?? new Database();
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

    /**
     * Verifica que el valor esté dentro de un conjunto de opciones
     */
    public function in($field, $options) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $options)) {
            $this->errors[$field] = "El valor seleccionado para " . str_replace('_', ' ', $field) . " no es válido.";
        }
        return $this;
    }

    /**
     * Verifica que el campo sea un arreglo
     */
    public function array($field, $allowEmpty = false) {
        if (!isset($this->data[$field]) || !is_array($this->data[$field])) {
            $this->errors[$field] = "El campo " . str_replace('_', ' ', $field) . " debe ser una lista.";
        } elseif (!$allowEmpty && empty($this->data[$field])) {
            $this->errors[$field] = "La lista de " . str_replace('_', ' ', $field) . " no puede estar vacía.";
        }
        return $this;
    }

    /**
     * Verifica que los campos sean numéricos
     */
    public function numeric($fields) {
        foreach ($fields as $field) {
            if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
                $this->errors[$field] = "El campo " . str_replace('_', ' ', $field) . " debe ser un número.";
            }
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