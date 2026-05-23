<?php
/*
 * Clase para conectarse a la base de datos y ejecutar consultas PDO
 */
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;

    private $dbh; // Database Handler
    private $stmt;
    private $error;

    public function __construct() {
        // Configurar el DSN (Data Source Name)
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        
        $options = [
            PDO::ATTR_PERSISTENT => true, // Conexión persistente para mejor rendimiento
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Lanzar excepciones en errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ // Retornar resultados como objetos por defecto
        ];

        // Crear instancia de PDO
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            echo "Error de conexión: " . $this->error;
        }
    }

    // Preparamos la consulta SQL
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }

    // Vinculamos la consulta con bind (Seguridad contra SQL Injection)
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):  $type = PDO::PARAM_INT; break;
                case is_bool($value): $type = PDO::PARAM_BOOL; break;
                case is_null($value): $type = PDO::PARAM_NULL; break;
                default:              $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    // Ejecuta la consulta preparada
    public function execute() {
        return $this->stmt->execute();
    }

    // Obtener todos los registros (Result Set)
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    // Obtener un solo registro
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }

    // Obtener el número de filas (útil para login o validaciones)
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    // Obtener el último ID insertado (útil para Facturas/Órdenes)
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

    // Métodos para transacciones (Seguridad de datos)
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    public function commit() {
        return $this->dbh->commit();
    }

    public function rollBack() {
        return $this->dbh->rollBack();
    }

    /**
     * Inserta un registro de forma sencilla (Mini Query Builder)
     * @param string $table Nombre de la tabla
     * @param array $data Arreglo asociativo ['columna' => 'valor']
     */
    public function insert($table, $data) {
        $fields = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));

        $this->query("INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})");

        foreach ($data as $key => $value) {
            $this->bind(":$key", $value);
        }

        return $this->execute();
    }

    /**
     * Actualiza registros basado en una condición simple
     * @param string $table Nombre de la tabla
     * @param array $data Arreglo asociativo de cambios
     * @param string $where Columna para el filtro
     * @param mixed $whereValue Valor del filtro
     */
    public function update($table, $data, $where, $whereValue) {
        $sets = "";
        foreach ($data as $key => $value) {
            $sets .= "{$key} = :{$key}, ";
        }
        $sets = rtrim($sets, ", ");

        $this->query("UPDATE {$table} SET {$sets} WHERE {$where} = :whereVal");

        foreach ($data as $key => $value) {
            $this->bind(":$key", $value);
        }
        $this->bind(":whereVal", $whereValue);

        return $this->execute();
    }

    /**
     * Elimina registros basado en una condición simple
     * @param string $table Nombre de la tabla
     * @param string $where Columna para el filtro
     * @param mixed $value Valor del filtro
     */
    public function delete($table, $where, $value) {
        $this->query("DELETE FROM {$table} WHERE {$where} = :val");
        $this->bind(":val", $value);
        return $this->execute();
    }
}
