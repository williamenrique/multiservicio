<?php
/**
 * Clase centralizada para validación de datos
 */
class Validator {
    private $data;
    private $errors = [];

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Verifica que los campos existan y no estén vacíos
     */
    public function required($fields) {
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || (is_string($this->data[$field]) && trim($this->data[$field]) === '')) {
                $this->addError($field, "El campo {$field} es obligatorio.");
            }
        }
        return $this;
    }

    /**
     * Verifica que los campos sean numéricos
     */
    public function numeric($fields) {
        foreach ($fields as $field) {
            if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
                $this->addError($field, "El campo {$field} debe ser un valor numérico.");
            }
        }
        return $this;
    }

    /**
     * Verifica que el campo sea un array y no esté vacío
     */
    public function array($field, $allowEmpty = false) {
        if (!isset($this->data[$field]) || !is_array($this->data[$field])) {
            $this->addError($field, "El campo {$field} debe ser un listado válido.");
        } elseif (!$allowEmpty && empty($this->data[$field])) {
            $this->addError($field, "El listado de {$field} no puede estar vacío.");
        }
        return $this;
    }

    /**
     * Verifica que el valor esté dentro de un conjunto de opciones
     */
    public function in($field, $options) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $options)) {
            $this->addError($field, "El valor de {$field} no es válido.");
        }
        return $this;
    }

    /**
     * Agrega un error al contenedor
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Indica si la validación fue exitosa
     */
    public function success() {
        return empty($this->errors);
    }

    /**
     * Retorna el primer error encontrado o el array completo
     */
    public function getErrors() {
        return $this->errors;
    }
}