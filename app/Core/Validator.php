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
 * 
 * v2.1 (2026-10-09) — P3-04:
 *   • FIX seguridad: `exists()` ahora usa una WHITELIST de tablas y
 *     columnas permitidas antes de interpolar en SQL. Si se pasa una
 *     tabla/columna no autorizada, agrega un error de validación en vez
 *     de ejecutar el query.
 *   • Antes: `exists($field, $table, $column)` interpolaba directo.
 *     Aunque el código actual siempre pasa valores hardcoded, un refactor
 *     futuro podría pasar input del usuario y crear SQL Injection.
 *   • Nueva whitelist estática accesible vía `Validator::tablasPermitidas()`.
 */
class Validator {
    private $data;
    private $errors = [];

    /**
     * Whitelist de tablas y columnas permitidas en exists().
     * Estructura: 'tabla' => ['columna1', 'columna2', ...]
     * 
     * Agregar aquí cualquier tabla/columna nueva que se necesite validar.
     * NUNCA aceptar strings del usuario como nombre de tabla/columna.
     */
    private const TABLAS_PERMITIDAS = [
        'table_clientes'             => ['id', 'nombre', 'telefono', 'email'],
        'table_vehiculos'            => ['placa', 'cliente_id'],
        'table_proveedores'          => ['id', 'nombre', 'email'],
        'table_inventario'           => ['id', 'codigo', 'nombre'],
        'table_staff'                => ['id', 'cedula', 'email'],
        'table_usuarios'             => ['id', 'username', 'staff_id', 'role_id'],
        'table_roles'                => ['id', 'nombre_rol'],
        'table_ordenes_servicio'     => ['id', 'placa', 'cliente_id', 'mecanico_id'],
        'table_facturas'             => ['id', 'cliente_id', 'orden_id'],
        'table_compras'              => ['id', 'proveedor_id'],
        'table_presupuestos'         => ['id', 'cliente_id', 'numero'],
        'table_cuentas_pago'         => ['id'],
        'table_company_settings'     => ['id'],
    ];

    public function __construct($data) {
        $this->data = $data ?? [];
    }

    /**
     * Retorna la whitelist completa (para diagnóstico).
     */
    public static function tablasPermitidas(): array {
        return self::TABLAS_PERMITIDAS;
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
     * Valida formato de correo electrónico.
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
     * Valida que un valor sea numérico y mayor o igual a cero.
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
     * Verifica que el valor exista en una tabla de la BD.
     * 
     * ⚠️ SEGURIDAD (FIX P3-04):
     *   $table y $column se validan contra una whitelist antes de interpolarlos
     *   en SQL. Si no están autorizados, se agrega un error de validación y
     *   NO se ejecuta la consulta. Esto previene SQL Injection si algún día
     *   se llama con input del usuario.
     * 
     * @param string $field   Campo de $this->data a validar.
     * @param string $table   Nombre de la tabla (debe estar en la whitelist).
     * @param string $column  Nombre de la columna (debe estar en la whitelist para esa tabla).
     */
    public function exists($field, $table, $column) {
        if (empty($this->data[$field])) {
            return $this;
        }

        // ─── Whitelist check ───
        if (!isset(self::TABLAS_PERMITIDAS[$table])) {
            error_log("Validator::exists: tabla '{$table}' NO está en la whitelist. Campo: {$field}");
            $this->addError($field, "No se pudo validar el registro (tabla no autorizada).");
            return $this;
        }
        if (!in_array($column, self::TABLAS_PERMITIDAS[$table], true)) {
            error_log("Validator::exists: columna '{$column}' NO está en la whitelist para tabla '{$table}'. Campo: {$field}");
            $this->addError($field, "No se pudo validar el registro (columna no autorizada).");
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