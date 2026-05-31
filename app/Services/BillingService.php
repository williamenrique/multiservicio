<?php
/**
 * BillingService
 * Orquestador de lógica de negocio para facturación, abonos y devoluciones.
 */
class BillingService {
    private $db;
    private $facturaModel;

    public function __construct() {
        $this->db = new Database();
        // Cargamos el modelo inyectando la base de datos para la transacción
        require_once APPROOT . '/Models/ModelFacturacion.php';
        $this->facturaModel = new ModelFacturacion($this->db);
    }

    /**
     * Registra un abono a una factura a crédito garantizando la transacción.
     */
    public function registrarAbonoSeguro($ventaId, $monto, $metodo) {
        try {
            $this->db->beginTransaction();
            $res = $this->facturaModel->registrarAbono($ventaId, $monto, $metodo);
            if (!$res) throw new Exception("No se pudo registrar el abono.");
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * SOLUCIÓN AL ERROR: Procesa la devolución de un ítem de factura.
     * Se encarga de la transacción para asegurar que el stock y el dinero se ajusten juntos.
     */
    public function procesarDevolucionSegura($input) {
        try {
            $this->db->beginTransaction();
            
            // Llamamos al modelo para ejecutar la lógica de resta de totales y suma de stock
            $res = $this->facturaModel->procesarDevolucion($input['venta_id'], $input['detalle_id'], $input['destino']);
            
            if (!$res) throw new Exception("Error interno al procesar la devolución en el modelo.");

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en BillingService::procesarDevolucionSegura: " . $e->getMessage());
            throw $e;
        }
    }
}