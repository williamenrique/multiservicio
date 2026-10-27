<?php
/**
 * Modelo de Empresa
 * Gestiona la configuración general del taller en la base de datos.
 */
class ModelEmpresa {
    private $db;

    public function __construct(Database $db = null) {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Obtiene la configuración actual de la empresa.
     * Siempre debería haber una única fila con id=1.
     */
    public function obtenerConfiguracion() {
        $this->db->query("SELECT * FROM table_company_settings WHERE id = 1");
        return $this->db->single();
    }

    /**
     * Guarda o actualiza la configuración de la empresa.
     * Si no existe, la crea; si existe, la actualiza.
     */
    public function guardarConfiguracion($datos) {
        $name      = mb_strtoupper($datos['name'] ?? '', 'UTF-8');
        $nit       = mb_strtoupper($datos['nit'] ?? '', 'UTF-8');
        $iva       = $datos['iva'] ?? 0.00;
        $direccion = mb_strtoupper($datos['direccion'] ?? '', 'UTF-8');
        $telefono  = $datos['telefono'] ?? '';
        $diasGarantia = isset($datos['dias_garantia_devolucion']) && $datos['dias_garantia_devolucion'] !== ''
            ? (int)$datos['dias_garantia_devolucion'] : 5;

        $configActual = $this->obtenerConfiguracion();
        $logo = $datos['logo'] ?? ($configActual ? $configActual->logo : null);

        // v2.1 (2026-10-27) — FIX HY093:
        // Con ATTR_EMULATE_PREPARES=false, un placeholder no puede repetirse.
        // Antes cada campo aparecía 2 veces (VALUES + UPDATE) y PDO lanzaba
        // "Invalid parameter number". Se usan placeholders distintos para
        // VALUES (:x_ins) y para ON DUPLICATE KEY UPDATE (:x_upd).
        $this->db->query("INSERT INTO table_company_settings
                              (id, name, nit, iva, logo, direccion, telefono, dias_garantia_devolucion)
                          VALUES
                              (1, :name_ins, :nit_ins, :iva_ins, :logo_ins, :direccion_ins, :telefono_ins, :dias_ins)
                          ON DUPLICATE KEY UPDATE
                              name                     = :name_upd,
                              nit                      = :nit_upd,
                              iva                      = :iva_upd,
                              logo                     = :logo_upd,
                              direccion                = :direccion_upd,
                              telefono                 = :telefono_upd,
                              dias_garantia_devolucion = :dias_upd");

        $this->db->bind(':name_ins',      $name);
        $this->db->bind(':nit_ins',       $nit);
        $this->db->bind(':iva_ins',       $iva);
        $this->db->bind(':logo_ins',      $logo);
        $this->db->bind(':direccion_ins', $direccion);
        $this->db->bind(':telefono_ins',  $telefono);
        $this->db->bind(':dias_ins',      $diasGarantia);

        $this->db->bind(':name_upd',      $name);
        $this->db->bind(':nit_upd',       $nit);
        $this->db->bind(':iva_upd',       $iva);
        $this->db->bind(':logo_upd',      $logo);
        $this->db->bind(':direccion_upd', $direccion);
        $this->db->bind(':telefono_upd',  $telefono);
        $this->db->bind(':dias_upd',      $diasGarantia);

        return $this->db->execute();
    }
}