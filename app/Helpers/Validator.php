<?php
/**
 * Clase Validator
 * Proporciona un motor de validación para entradas de datos.
 */
class Validator {
    private $data;
    private $errors = [];

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Verifica campos obligatorios.
     */
    public function required($fields) {
        $fields = is_array($fields) ? $fields : [$fields];
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || (is_string($this->data[$field]) && trim($this->data[$field]) === '')) {
                $this->errors[$field] = "Este campo es requerido.";
            }
        }
        return $this;
    }

    /**
     * Valida formato de correo electrónico.
     */
    public function email($field) {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "El formato de correo no es válido.";
        }
        return $this;
    }

    /**
     * Valida que un valor sea numérico y mayor o igual a cero.
     */
    public function positiveNumber($field) {
        if (isset($this->data[$field]) && (!is_numeric($this->data[$field]) || $this->data[$field] < 0)) {
            $this->errors[$field] = "Debe ser un número positivo.";
        }
        return $this;
    }

    /**
     * Verifica si un ID o valor existe en una tabla específica de la BD.
     */
    public function exists($field, $table, $column) {
        if (!empty($this->data[$field])) {
            $db = new Database();
            $db->query("SELECT COUNT(*) as count FROM $table WHERE $column = :val");
            $db->bind(':val', $this->data[$field]);
            $res = $db->single();
            if ($res->count == 0) {
                $this->errors[$field] = "El registro seleccionado no es válido o no existe.";
            }
        }
        return $this;
    }

    /**
     * Retorna si la validación fue exitosa.
     */
    public function success() {
        return empty($this->errors);
    }

    /**
     * Retorna el primer error de cada campo.
     */
    public function getErrors() {
        return $this->errors;
    }
}