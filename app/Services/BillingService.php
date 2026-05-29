<?php
/**
 * Servicio de Facturación
 * Centraliza la lógica de negocio, cálculos financieros y transacciones.
 */
class BillingService {
    private $db;
    private $facturaModel;
    private $invModel;
    private $empresaModel;

    public function __construct() {
        $this->db = new Database();
        $this->facturaModel = new ModelFacturacion();
        $this->invModel = new ModelInventario();
        $this->empresaModel = new ModelEmpresa();
    }

    public function procesarVentaCompleta($datos) {
        try {
            $this->db->beginTransaction();

            // 1. Cálculos de Totales
            $totales = $this->calcularTotales($datos);
            
            // 2. Determinar estado (COMPLETADO o CREDITO)
            $status = ($totales['saldo'] > 0.05) ? 'CREDITO' : 'COMPLETADO';

            // 3. Guardar Cabecera
            $ventaId = $this->facturaModel->guardarCabeceraVenta($datos, $status, $totales);

            // 4. Procesar Detalles y Stock
            $this->procesarDetallesYStock($ventaId, $datos['items'], $status);

            $this->db->commit();
            return $ventaId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function calcularTotales($datos) {
        $config = $this->empresaModel->obtenerConfiguracion();
        $ivaPorcentaje = ($config->iva ?? 0) / 100;
        
        $subtotal = 0;
        foreach ($datos['items'] as $item) {
            $subtotal += ($item['precio'] * $item['cantidad']);
        }

        $ivaMonto = ($datos['iva_activo'] ?? false) ? ($subtotal * $ivaPorcentaje) : 0;
        $total = $subtotal + $ivaMonto;
        $pagado = (float)($datos['pago_efectivo'] ?? 0) + (float)($datos['pago_transferencia'] ?? 0);

        return [
            'subtotal' => $subtotal,
            'iva' => $ivaMonto,
            'total' => $total,
            'saldo' => max(0, $total - $pagado)
        ];
    }

    private function procesarDetallesYStock($ventaId, $items, $status) {
        // Si es actualización, el modelo debería limpiar detalles previos
        // Por ahora, el modelo se encarga de la persistencia pura.
        
        foreach ($items as $item) {
            // Obtener costo para Kardex
            $prodInfo = !empty($item['id']) ? $this->invModel->obtenerPorId($item['id']) : null;
            $costo = $prodInfo ? (float)$prodInfo->costo_promedio : 0;

            // Inserción de detalle vía el DB directamente
            $this->db->query("INSERT INTO table_ventas_detalle (venta_id, producto_id, descripcion, cantidad, precio_unitario, costo_unitario) 
                              VALUES (:vid, :pid, :desc, :cant, :precio, :costo)");
            $this->db->bind(':vid', $ventaId);
            $this->db->bind(':pid', $item['id'] ?? null);
            $this->db->bind(':desc', $item['nombre']);
            $this->db->bind(':cant', $item['cantidad']);
            $this->db->bind(':precio', $item['precio']);
            $this->db->bind(':costo', $costo);
            $this->db->execute();

            // Manejo de Inventario Físico
            if ($status !== 'PENDIENTE' && !empty($item['id']) && ($item['tipo'] ?? '') === 'PRODUCTO') {
                $this->actualizarInventarioSeguro($item, $ventaId, $status);
            }
        }
    }

    private function actualizarInventarioSeguro($item, $ventaId, $status) {
        $this->db->query("SELECT stock FROM table_inventario WHERE id = :id FOR UPDATE");
        $this->db->bind(':id', $item['id']);
        $actual = $this->db->single();

        if (!$actual || $actual->stock < $item['cantidad']) {
            throw new StockException("No hay suficiente stock de '{$item['nombre']}'.");
        }

        // Registrar Kardex
        $this->invModel->registrarMovimiento(
            $item['id'], 
            'SALIDA_VENTA', 
            $item['cantidad'], 
            $ventaId, 
            "Venta Finalizada ($status)"
        );

        $this->db->query("UPDATE table_inventario SET stock = stock - :cant WHERE id = :pid");
        $this->db->bind(':cant', $item['cantidad']);
        $this->db->bind(':pid', $item['id']);
        $this->db->execute();
    }
}