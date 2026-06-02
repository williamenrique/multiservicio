<?php
/**
 * BillingService
 * Orquestador de lógica de negocio para facturación, abonos y devoluciones.
 */
class BillingService {
    private $db;
    private $facturaModel;
    private $invModel;
    private $cajaModel;

    public function __construct() {
        $this->db = new Database();
        require_once APPROOT . '/Models/ModelFacturacion.php';
        require_once APPROOT . '/Models/ModelInventario.php';
        require_once APPROOT . '/Models/ModelCaja.php';
        
        $this->facturaModel = new ModelFacturacion($this->db);
        $this->invModel = new ModelInventario($this->db);
        $this->cajaModel = new ModelCaja($this->db);
    }

    /**
     * Procesa una venta completa (Cabecera, Detalles, Stock, Caja)
     */
    public function procesarVentaCompleta($datos, $usuarioId) {
        try {
            $this->db->beginTransaction();

            // 1. Cálculos de totales
            $subtotal = 0;
            foreach($datos['items'] as $it) $subtotal += ($it['precio'] * $it['cantidad']);
            
            $tasaIva = (float)($datos['tasa_iva'] ?? 16); // O usar la config global
            $iva = ($datos['aplicar_iva'] ?? false || !empty($datos['iva_activo'])) ? ($subtotal * ($tasaIva / 100)) : 0;
            $totalVenta = $subtotal + $iva;
            
            $pagoEfe = (float)($datos['pago_efectivo'] ?? 0);
            $pagoTra = (float)($datos['pago_transferencia'] ?? 0);
            $saldoPendiente = $totalVenta - ($pagoEfe + $pagoTra);
            
            $status = ($saldoPendiente > 0.05) ? 'CREDITO' : 'COMPLETADO';
            $totales = [
                'subtotal' => $subtotal, 
                'iva' => $iva, 
                'total' => $totalVenta, 
                'saldo' => max(0, $saldoPendiente)
            ];

            // 2. Guardar Cabecera
            $ventaId = $this->facturaModel->guardarCabeceraVenta($datos, $status, $totales, $usuarioId);

            // 3. Limpiar y registrar detalles
            $this->db->query("DELETE FROM table_ventas_detalle WHERE venta_id = :vid");
            $this->db->bind(':vid', $ventaId);
            $this->db->execute();

            foreach ($datos['items'] as $item) {
                $this->db->query("INSERT INTO table_ventas_detalle (venta_id, producto_id, descripcion, cantidad, precio_unitario, costo_unitario) 
                                VALUES (:vid, :pid, :desc, :cant, :pre, :costo)");
                $this->db->bind(':vid', $ventaId);
                $this->db->bind(':pid', $item['tipo'] === 'PRODUCTO' ? $item['id'] : null);
                $this->db->bind(':desc', mb_strtoupper($item['nombre'], 'UTF-8'));
                $this->db->bind(':cant', $item['cantidad']);
                $this->db->bind(':pre', $item['precio']);
                $this->db->bind(':costo', $item['ultimo_costo'] ?? 0);
                $this->db->execute();

                if ($item['tipo'] === 'PRODUCTO' && $status !== 'PENDIENTE') {
                    // Descontar Stock
                    $this->db->query("UPDATE table_inventario SET stock = stock - :cant WHERE id = :pid");
                    $this->db->bind(':cant', $item['cantidad']);
                    $this->db->bind(':pid', $item['id']);
                    $this->db->execute();
                    
                    // Kardex
                    $this->invModel->registrarMovimiento($item['id'], 'SALIDA_VENTA', $item['cantidad'], $ventaId, "Venta Factura #$ventaId");
                }
            }

            // 4. Registrar movimiento en caja
            if ($pagoEfe > 0 && $status !== 'PENDIENTE') {
                $this->cajaModel->registrarMovimiento([
                    'tipo' => 'INGRESO', 
                    'monto' => $pagoEfe, 
                    'metodo_pago' => 'EFECTIVO',
                    'referencia_id' => $ventaId, 
                    'concepto' => "VENTA FACTURA #$ventaId"
                ]);
            }

            $this->db->commit();
            return $ventaId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
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