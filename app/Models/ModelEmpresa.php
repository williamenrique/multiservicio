<?php
/**
 * Modelo de Empresa
 * Gestiona la configuración general del taller en la base de datos.
 */
class ModelEmpresa {
    private $db;

    public function __construct() {
        $this->db = new Database();
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
        // Aseguramos que los datos importantes estén en mayúsculas
        $name = mb_strtoupper($datos['name'] ?? '', 'UTF-8');
        $nit = mb_strtoupper($datos['nit'] ?? '', 'UTF-8');
        $iva = $datos['iva'] ?? 0.00;
        $direccion = mb_strtoupper($datos['direccion'] ?? '', 'UTF-8');
        $telefono = $datos['telefono'] ?? '';
        
        // Si no se envía un logo nuevo en los datos, intentamos mantener el que ya existe en la DB
        $configActual = $this->obtenerConfiguracion();
        $logo = $datos['logo'] ?? ($configActual ? $configActual->logo : null);

        // Intentar actualizar, si no existe, insertar
        $this->db->query("INSERT INTO table_company_settings (id, name, nit, iva, logo, direccion, telefono) 
                          VALUES (1, :name, :nit, :iva, :logo, :direccion, :telefono)
                          ON DUPLICATE KEY UPDATE
                              name = :name,
                              nit = :nit,
                              iva = :iva,
                              logo = :logo,
                              direccion = :direccion,
                              telefono = :telefono");
        
        $this->db->bind(':name', $name);
        $this->db->bind(':nit', $nit);
        $this->db->bind(':iva', $iva);
        $this->db->bind(':logo', $logo);
        $this->db->bind(':direccion', $direccion);
        $this->db->bind(':telefono', $telefono);

        return $this->db->execute();
    }
}