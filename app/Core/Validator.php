<?php
/**
 * Clase centralizada para validación de datos
 * 
 * v2.0 (2026-10-09):
 *   • FIX P0-04b: Se añadieron métodos que el código ya invocaba pero
 *     no existían, causando errores silenciosos:
 *       - email()          → usado en ControllerEmail y ControllerPresupuesto
 *       - positiveNumber() → validación de montos > 0
 *       - exists()         → validación de FK contra tabla
 */
class Validator {
    private $data;
    private $errors = [];

    public function __construct($data) {
        $this->data = $data ?? [];
    }

    /**
     * Verifica que los campos existan y no estén vacíos
     */
    public function required($fields) {
        $fields = is_array($fields) ? $fields : [$fields];
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
        $fields = is_array($fields) ? $fields : [$fields];
        foreach ($fields as $field) {
            if (isset($this->data[$field]) && $this->data[$field] !== '' && !is_numeric($this->data[$field])) {
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
        if (isset($this->data[$field]) && !in_array($this->data[$field], $options, true)) {
            $this->addError($field, "El valor de {$field} no es válido.");
        }
        return $this;
    }

    /**
     * Valida formato de correo electrónico (FIX P0-04b).
     * Solo valida si el campo tiene valor; combinarlo con required()
     * si el campo es obligatorio.
     */
    public function email($field) {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "El formato de correo no es válido.");
        }
        return $this;
    }

    /**
     * Valida que un valor sea numérico y mayor o igual a cero (FIX P0-04b).
     */
    public function positiveNumber($field) {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $val = $this->data[$field];
            if (!is_numeric($val) || (float)$val < 0) {
                $this->addError($field, "El campo {$field} debe ser un número positivo.");
            }
        }
        return $this;
    }

    /**
     * Verifica que el valor exista en una tabla de la BD (FIX P0-04b).
     * 
     * ⚠️ IMPORTANTE: $table y $column deben ser valores HARDCODED en el
     * controlador, NUNCA provenientes del input del usuario. Este método
     * interpola directo en SQL y es vulnerable a inyección si se pasa
     * input sin filtrar.
     */
    public function exists($field, $table, $column) {
        if (empty($this->data[$field])) {
            return $this;
        }
        try {
            $db = new Database();
            $db->query("SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :val LIMIT 1");
            $db->bind(':val', $this->data[$field]);
            $res = $db->single();
            if (!$res || (int)$res->count === 0) {
                $this->addError($field, "El registro seleccionado no existe o no es válido.");
            }
        } catch (Throwable $e) {
            error_log("Validator::exists falló: " . $e->getMessage());
            $this->addError($field, "No se pudo validar el registro.");
        }
        return $this;
    }

    /**
     * Agrega un error al contenedor (solo el primero por campo)
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
     * Retorna el array de errores
     */
    public function getErrors() {
        return $this->errors;
    }
}