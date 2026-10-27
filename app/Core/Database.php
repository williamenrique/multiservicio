<?php
/*
 * Clase para conectarse a la base de datos y ejecutar consultas PDO
 *
 * v2.1 (2026-10-26) — PERFORMANCE:
 *   • Se agregó un singleton estático ($instance) para reutilizar la
 *     MISMA conexión PDO dentro de un request. Antes, cada `new Database()`
 *     (AuthGuard, logAction, NotificationChecker, renderView, Validator,
 *     EmailService, ModelEmpresa...) creaba una nueva instancia PDO.
 *     Con PDO::ATTR_PERSISTENT eso no lanzaba errores, pero forzaba al
 *     driver MySQL a buscar en el pool persistente en cada `new`, lo
 *     cual se notaba especialmente en el login (se creaban 6–8 instancias).
 *   • La instancia persistente se conserva ($persistent = true), pero
 *     ahora todos los modelos comparten la misma.
 *   • Se agregó `PDO::ATTR_EMULATE_PREPARES => false` para usar
 *     prepared statements reales (más rápido y más seguro).
 *   • Timeouts cortos para que una BD lenta no bloquee el login.
 */
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;

    private $dbh;
    private $stmt;
    private $error;

    /** @var Database|null Instancia compartida por request */
    private static ?Database $instance = null;

    /**
     * Devuelve la instancia compartida (singleton por request).
     * Si ya existe, la reutiliza; si no, la crea.
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';

        $options = [
            PDO::ATTR_PERSISTENT            => true,
            PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_OBJ,
            // v2.1: prepared statements reales (más rápidos y seguros)
            PDO::ATTR_EMULATE_PREPARES      => false,
            // PDO::ATTR_EMULATE_PREPARES      => true, // para compatibilidad con MySQL antiguos
            // v2.1: timeouts para no colgar el login si MySQL tarda
            PDO::ATTR_TIMEOUT               => 5,
        ];

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            // No hacer echo de credenciales/DSN en producción
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                echo "Error de conexión: " . $this->error;
            }
            throw $e;
        }
    }

    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }

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

    public function execute() {
        return $this->stmt->execute();
    }

    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }

    public function rowCount() {
        return $this->stmt->rowCount();
    }

    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    public function commit() {
        return $this->dbh->commit();
    }

    public function rollBack() {
        return $this->dbh->rollBack();
    }

    public function insert($table, $data) {
        $fields = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $this->query("INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})");
        foreach ($data as $key => $value) {
            $this->bind(":$key", $value);
        }
        return $this->execute();
    }

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

    public function delete($table, $where, $value) {
        $this->query("DELETE FROM {$table} WHERE {$where} = :val");
        $this->bind(":val", $value);
        return $this->execute();
    }
}