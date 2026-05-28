<?php
/**
 * BillingService
 * Centraliza la lógica de negocio de facturación, inventario y caja.
 */
class BillingService {
    private $db;
    private $facturaModel;
    private $inventarioModel;
    private $cajaModel;

    public function __construct() {
        $this->db = new Database();
        $this->facturaModel = new ModelFacturacion();
        $this->inventarioModel = new ModelInventario();
        $this->cajaModel = new ModelCaja();
    }

    /**
     * Procesa una venta completa de forma atómica.
     */
    public function procesarVentaCompleta($datos) {
        try {
            $this->db->beginTransaction();

            // 2. Validar Stock Disponible (Físico - Comprometido por otros)
            $ventaIdActual = !empty($datos['id_db']) ? $datos['id_db'] : 0;

            foreach ($datos['items'] as $item) {
                if (!empty($item['id']) && $item['tipo'] === 'PRODUCTO') {
                    // Consultamos el stock disponible real (Físico - Reservas de otros borradores)
                    $this->db->query("SELECT (i.stock - COALESCE((
                                        SELECT SUM(vd.cantidad) 
                                        FROM table_ventas_detalle vd 
                                        JOIN table_ventas v ON vd.venta_id = v.id 
                                        WHERE vd.producto_id = i.id 
                                        AND v.status = 'PENDIENTE' 
                                        AND v.id != :vid_actual
                                      ), 0)) as disponible
                                      FROM table_inventario i WHERE i.id = :pid");
                    $this->db->bind(':pid', $item['id']);
                    $this->db->bind(':vid_actual', $ventaIdActual);
                    $stock = $this->db->single();

                    if (!$stock || $stock->disponible < $item['cantidad']) {
                        throw new Exception("El producto '" . $item['nombre'] . "' ya está apartado en otra orden o no hay stock. Disponible real: " . ($stock->disponible ?? 0));
                    }
                }
            }

            // 3. Guardar la Factura (Status COMPLETADO o CREDITO)
            // El modelo ya maneja la lógica de inserción de cabecera y detalles
            $ventaId = $this->facturaModel->guardarFactura($datos, 'COMPLETADO');

            if (!$ventaId) {
                throw new Exception("Error al generar el registro de venta.");
            }

            // 4. Registrar Movimientos de Caja
            if ((float)$datos['pago_efectivo'] > 0) {
                $this->cajaModel->registrarMovimiento([
                    'sesion_id' => null,
                    'tipo' => 'INGRESO',
                    'monto' => $datos['pago_efectivo'],
                    'metodo_pago' => 'EFECTIVO',
                    'referencia_id' => $ventaId,
                    'concepto' => "Venta #$ventaId"
                ]);
            }

            if ((float)$datos['pago_transferencia'] > 0) {
                $this->cajaModel->registrarMovimiento([
                    'sesion_id' => null,
                    'tipo' => 'INGRESO',
                    'monto' => $datos['pago_transferencia'],
                    'metodo_pago' => 'TRANSFERENCIA',
                    'referencia_id' => $ventaId,
                    'concepto' => "Venta #$ventaId"
                ]);
            }

            // 5. Auditoría
            logAction('VENTA', 'FINALIZAR', "Venta #$ventaId procesada exitosamente.");

            $this->db->commit();
            return $ventaId;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("FALLO EN BillingService::procesarVentaCompleta -> " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registra un abono y asegura la entrada en caja en una sola operación.
     */
    public function registrarAbonoSeguro($ventaId, $monto, $metodo) {
        try {
            $this->db->beginTransaction();

            // Registrar abono en el modelo
            $res = $this->facturaModel->registrarAbono($ventaId, $monto, $metodo);
            
            if (!$res) throw new Exception("Error al registrar abono en la base de datos.");

            // Registrar en caja
            $this->cajaModel->registrarMovimiento([
                'sesion_id' => null,
                'tipo' => 'INGRESO',
                'monto' => $monto,
                'metodo_pago' => $metodo,
                'referencia_id' => $ventaId,
                'concepto' => "Abono a Factura #$ventaId"
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Sincroniza un borrador validando que no se reserve más de lo disponible.
     */
    public function sincronizarBorradorSeguro($datos) {
        try {
            $ventaIdActual = !empty($datos['id_db']) ? $datos['id_db'] : 0;

            // Validar stock disponible para cada item antes de actualizar el borrador
            foreach ($datos['items'] as $item) {
                if (!empty($item['id']) && $item['tipo'] === 'PRODUCTO') {
                    $this->db->query("SELECT (i.stock - COALESCE((
                        SELECT SUM(vd.cantidad) FROM table_ventas_detalle vd 
                        JOIN table_ventas v ON vd.venta_id = v.id 
                        WHERE vd.producto_id = i.id AND v.status = 'PENDIENTE' AND v.id != :vid
                    ), 0)) as disponible FROM table_inventario i WHERE i.id = :pid");
                    $this->db->bind(':pid', $item['id']);
                    $this->db->bind(':vid', $ventaIdActual);
                    $stock = $this->db->single();

                    if (!$stock || $stock->disponible < $item['cantidad']) {
                        throw new Exception("No puedes reservar {$item['cantidad']} unidades de '{$item['nombre']}'. Disponible: " . ($stock->disponible ?? 0));
                    }
                }
            }

            return $this->facturaModel->guardarFactura($datos, 'PENDIENTE');
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Procesa una devolución garantizando que el stock reingrese y la caja se ajuste.
     */
    public function procesarDevolucionSegura($datos) {
        try {
            $this->db->beginTransaction();

            $res = $this->facturaModel->procesarDevolucion($datos['venta_id'], $datos['detalle_id'], $datos['destino']);
            
            if (!$res) throw new Exception("La devolución no pudo ser procesada.");

            // Si el destino es reingreso, el ModelFacturacion ya lo hace, 
            logAction('VENTA', 'DEVOLUCION', "Devolución procesada para Venta #{$datos['venta_id']}");

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}