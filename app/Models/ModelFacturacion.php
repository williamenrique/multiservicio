<?php
/**
 * Modelo de Facturación
 * Maneja la persistencia de ventas y la actualización de stock.
 */
class ModelFacturacion {
    private $db;

    /**
     * Constructor: Permite inyectar una instancia de base de datos
     * para compartir transacciones con el BillingService.
     */
    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    /**
     * Busca facturas por ID, nombre de cliente o placa para el buscador global.
     * @param string $term Término de búsqueda
     */
    public function searchInvoices($term) {
        $this->db->query("SELECT v.id, v.placa, c.nombre as cliente_nombre
                          FROM table_ventas v
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE (v.id LIKE :term OR v.placa LIKE :term OR c.nombre LIKE :term)
                          AND v.status IN ('COMPLETADO', 'CREDITO') LIMIT 5");
        $this->db->bind(':term', "%$term%");
        return $this->db->resultSet();
    }

    /**
     * Busca productos o servicios disponibles en el inventario
     * @param string $termino Nombre o categoría
     */
    public function buscarItems($termino) {
        // Traemos solo columnas necesarias para el POS (excluimos imagen por peso)
        $this->db->query("SELECT i.id, i.nombre, i.categoria, i.stock, i.precio, i.ultimo_costo,
                          (i.stock - COALESCE((
                              SELECT SUM(vd.cantidad) 
                              FROM table_ventas_detalle vd 
                              JOIN table_ventas v ON vd.venta_id = v.id 
                              WHERE vd.producto_id = i.id AND v.status = 'PENDIENTE'
                          ), 0)) as stock_disponible
                          FROM table_inventario i
                          WHERE (i.nombre LIKE :term OR i.categoria LIKE :term)
                          HAVING stock_disponible > 0
                          LIMIT 15");
        $this->db->bind(':term', "%$termino%");
        return $this->db->resultSet();
    }

    public function obtenerBorradores() {
        $this->db->query("SELECT * FROM table_ventas WHERE status = 'PENDIENTE' ORDER BY fecha DESC");
        return $this->db->resultSet();
    }

    /**
     * Obtiene todos los borradores con sus respectivos items cargados
     */
    public function obtenerBorradoresCompleto() {
        $this->db->query("SELECT v.*, s.nombre as usuario_nombre, c.nombre as cliente_nombre 
                          FROM table_ventas v 
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id 
                          LEFT JOIN table_staff s ON u.staff_id = s.id 
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE v.status = 'PENDIENTE' ORDER BY v.fecha DESC");
        $ventas = $this->db->resultSet();

        foreach ($ventas as $key => $venta) {
            $this->db->query("SELECT vd.*, i.id as prod_id 
                              FROM table_ventas_detalle vd 
                              LEFT JOIN table_inventario i ON vd.producto_id = i.id
                              WHERE vd.venta_id = :vid");
            $this->db->bind(':vid', $venta->id);
            $items = $this->db->resultSet();
            
            $ventas[$key]->items = array_map(function($it) {
                return [
                    'id' => $it->producto_id,
                    'nombre' => $it->descripcion,
                    'precio' => (float)$it->precio_unitario,
                    'cantidad' => (int)$it->cantidad,
                    'tipo' => $it->producto_id ? 'PRODUCTO' : 'SERVICIO'
                ];
            }, $items);
        }
        return $ventas;
    }

    /**
     * Registra o actualiza la cabecera de una venta.
     * Los cálculos deben venir ya procesados desde el BillingService.
     * @param array $datos Datos de la venta
     * @param string $status Estado (COMPLETADO, CREDITO, PENDIENTE)
     * @param array $totales Resumen de montos
     * @return int ID de la venta
     */
    public function guardarCabeceraVenta($datos, $status, $totales) {
        try {
            $ventaId = !empty($datos['id_db']) ? $datos['id_db'] : null;
            if ($ventaId) {
                $this->db->query("UPDATE table_ventas SET
                                  cliente_id = :cid, placa = :placa, modelo_vehiculo = :modelo, 
                                  subtotal = :sub, iva_monto = :iva, total = :total, 
                                  pago_efectivo = :pef, pago_transferencia = :ptra, saldo_pendiente = :spend,
                                  status = :status, mecanico_id = :mid" .
                                  (in_array($status, ['COMPLETADO', 'CREDITO']) ? ", fecha_cierre = NOW()" : "") . " 
                                  WHERE id = :id");
                $this->db->bind(':id', $ventaId);
            } else {
                $this->db->query("INSERT INTO table_ventas (cliente_id, placa, modelo_vehiculo, subtotal, iva_monto, total, 
                                  pago_efectivo, pago_transferencia, saldo_pendiente, usuario_id, mecanico_id, status) 
                                  VALUES (:cid, :placa, :modelo, :sub, :iva, :total, :pef, :ptra, :spend, :uid, :mid, :status)");
                $this->db->bind(':uid', $_SESSION['user_id']);
            }
            $this->db->bind(':cid', !empty($datos['cliente_id']) ? $datos['cliente_id'] : null);
            $this->db->bind(':placa', !empty($datos['placa']) ? mb_strtoupper($datos['placa'], 'UTF-8') : '');
            $this->db->bind(':modelo', !empty($datos['modelo']) ? mb_strtoupper($datos['modelo'], 'UTF-8') : '');
            $this->db->bind(':sub', $totales['subtotal']);
            $this->db->bind(':iva', $totales['iva']);
            $this->db->bind(':total', $totales['total']);
            $this->db->bind(':pef', $datos['pago_efectivo']);
            $this->db->bind(':ptra', $datos['pago_transferencia']);
            $this->db->bind(':spend', $totales['saldo']);
            $this->db->bind(':mid', !empty($datos['mecanico_id']) ? $datos['mecanico_id'] : null);
            $this->db->bind(':status', $status);
            $this->db->execute();
            return $ventaId ?: $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error en guardarCabeceraVenta: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene los detalles completos de una venta para su impresión
     */
    public function obtenerVentaCompleta($id) {
        $this->db->query("SELECT v.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.email as cliente_email, s.nombre as mecanico_nombre
                          FROM table_ventas v
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_staff s ON v.mecanico_id = s.id
                          WHERE v.id = :id");
        $this->db->bind(':id', $id);
        $venta = $this->db->single();

        if ($venta) {
            $this->db->query("SELECT vd.id, vd.producto_id, vd.descripcion, vd.cantidad, vd.precio_unitario 
                              FROM table_ventas_detalle vd 
                              WHERE vd.venta_id = :vid");
            $this->db->bind(':vid', $id);
            $venta->items = $this->db->resultSet();
        }
        return $venta;
    }

    /**
     * Métodos de gestión de borradores requeridos por el controlador
     */
    public function obtenerBorradorPorId($id) {
        $this->db->query("SELECT * FROM table_ventas WHERE id = :id AND status = 'PENDIENTE'");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Elimina un borrador de factura (Venta en estado PENDIENTE).
     */
    public function eliminarBorrador($id) {
        $this->db->query("DELETE FROM table_ventas WHERE id = :id AND status = 'PENDIENTE'");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Obtiene los datos para el reporte de auditoría de trabajos.
     * Retorna el resumen de deudas (para tarjetas) y la lista de trabajos realizados.
     */
    public function obtenerAuditoriaTrabajos() {
        // 1. Resumen de Deudores (Monto total pendiente y conteo)
        $this->db->query("SELECT SUM(saldo_pendiente) as total_deuda, COUNT(*) as cantidad_deudores 
                          FROM table_ventas WHERE status = 'CREDITO'");
        $resumen = $this->db->single();

        // 2. Lista de trabajos (Ventas finalizadas y a crédito) con datos relacionados
        $this->db->query("SELECT v.*, c.nombre as cliente_nombre, u.username as vendedor_nombre 
                          FROM table_ventas v
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          WHERE v.status IN ('COMPLETADO', 'CREDITO')
                          ORDER BY v.fecha DESC");
        $lista = $this->db->resultSet();

        return [
            'resumen' => $resumen,
            'lista' => $lista
        ];
    }

    /**
     * Registra un abono a una venta con deuda.
     * Si el saldo llega a cero, la factura pasa a COMPLETADO.
     */
    public function registrarAbono($ventaId, $monto, $metodo) {
        try {
            $this->db->query("SELECT total, pago_efectivo, pago_transferencia, saldo_pendiente FROM table_ventas WHERE id = :id");
            $this->db->bind(':id', $ventaId);
            $venta = $this->db->single();

            if (!$venta) throw new AppException("Venta no encontrada para registrar el abono.");

            $monto = (float)$monto;
            $nuevoPendiente = $venta->saldo_pendiente - $monto;
            
            // 1. Insertar el registro en la tabla de abonos
            $this->db->query("INSERT INTO table_abonos_clientes (venta_id, monto, metodo_pago) VALUES (:vid, :monto, :metodo)");
            $this->db->bind(':vid', $ventaId);
            $this->db->bind(':monto', $monto);
            $this->db->bind(':metodo', $metodo);
            $this->db->execute();

            // 2. Determinar qué columna de pago actualizar
            $columnaPago = ($metodo === 'TRANSFERENCIA') ? 'pago_transferencia' : 'pago_efectivo';
            
            // 3. Actualizar la venta principal
            // Si el saldo pendiente es muy cercano a cero (por decimales), marcar como COMPLETADO
            $nuevoStatus = ($nuevoPendiente <= 0.01) ? 'COMPLETADO' : 'CREDITO';

            $this->db->query("UPDATE table_ventas SET 
                              $columnaPago = $columnaPago + :monto,
                              saldo_pendiente = :pendiente,
                              status = :status
                              WHERE id = :id");
            $this->db->bind(':monto', $monto);
            $this->db->bind(':pendiente', $nuevoPendiente > 0 ? $nuevoPendiente : 0);
            $this->db->bind(':status', $nuevoStatus);
            $this->db->bind(':id', $ventaId);

            return $this->db->execute();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Obtiene ventas a crédito con más de 15 días de antigüedad.
     * @param int $dias Límite de días para considerar vencido.
     * @return array
     */
    public function obtenerCreditosVencidos($dias = 15) {
        $this->db->query("SELECT v.id, v.fecha, v.total, v.saldo_pendiente, v.placa, v.modelo_vehiculo, COALESCE(c.nombre, 'SIN CLIENTE') as cliente_nombre 
                          FROM table_ventas v
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE v.status = 'CREDITO' 
                          AND v.saldo_pendiente > 0
                          AND DATEDIFF(CURDATE(), COALESCE(DATE(v.fecha), CURDATE())) >= :dias
                          ORDER BY v.fecha ASC");
        $this->db->bind(':dias', $dias);
        return $this->db->resultSet();
    }

    /**
     * Helper interno para calcular diferencia de días entre fechas
     */
    private function calcularDiferenciaDias($d1, $d2) {
        return round(abs(strtotime($d1) - strtotime($d2)) / 86400);
    }

    /**
     * Procesa la devolución de un ítem específico de una factura.
     * @param int $ventaId ID de la factura
     * @param int $detalleId ID de la línea de detalle
     * @param string $destino Destino del ítem (STOCK o DANADO)
     * @return bool
     */
    public function procesarDevolucion($ventaId, $detalleId, $destino) {
        try {
            // Se elimina beginTransaction de aquí. 
            // La transacción ahora es controlada por BillingService.

            // 1. Obtener datos exactos del ítem y de la factura
            $this->db->query("SELECT vd.producto_id, vd.descripcion, vd.cantidad, vd.precio_unitario, 
                                     v.fecha, v.subtotal, v.iva_monto, v.total, v.saldo_pendiente,
                                     v.pago_efectivo, v.pago_transferencia
                              FROM table_ventas_detalle vd
                              JOIN table_ventas v ON vd.venta_id = v.id
                              WHERE vd.id = :id AND v.id = :vid");
            $this->db->bind(':id', $detalleId);
            $this->db->bind(':vid', $ventaId);
            $item = $this->db->single();

            if (!$item) {
                throw new Exception("El ítem de la factura no existe.");
            }
            if ($this->calcularDiferenciaDias(date('Y-m-d'), $item->fecha) > 5) {
                throw new Exception("Plazo de devolución vencido (máximo 5 días desde la compra).");
            }

            // 2. Calcular montos proporcionales (Base + IVA)
            $montoBase = (float)$item->precio_unitario * (int)$item->cantidad;
            $factorIva = ((float)$item->subtotal > 0) ? ((float)$item->iva_monto / (float)$item->subtotal) : 0;
            $ivaDevolver = $montoBase * $factorIva;
            $totalARestar = $montoBase + $ivaDevolver;

            // 2. Si es producto y el destino es REINGRESO, sumar al inventario
            if (!empty($item->producto_id)) {
                if ($destino === 'STOCK') {
                    $this->db->query("UPDATE table_inventario SET stock = stock + :cant WHERE id = :pid");
                    $this->db->bind(':cant', $item->cantidad);
                    $this->db->bind(':pid', $item->producto_id);
                    $this->db->execute();
                    
                    // Sugerencia: Pasa la conexión de DB actual al modelo de inventario
                    $invModel = new ModelInventario($this->db);
                    $invModel->registrarMovimiento($item->producto_id, 'ENTRADA_DEVOLUCION', $item->cantidad, $ventaId, "Devolución Factura #$ventaId");
                }
            }

            // 3. Registrar en el historial de devoluciones para auditoría
            $this->db->query("INSERT INTO table_devoluciones (venta_id, producto_id, descripcion, cantidad, monto_devuelto, destino, usuario_id) 
                              VALUES (:vid, :pid, :desc, :cant, :monto, :dest, :uid)");
            $this->db->bind(':vid', $ventaId);
            $this->db->bind(':pid', $item->producto_id);
            $this->db->bind(':desc', $item->descripcion);
            $this->db->bind(':cant', $item->cantidad);
            $this->db->bind(':monto', $totalARestar);
            $this->db->bind(':dest', $destino);
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->execute();

            // 4. Ajustar la factura (Restar del total y del saldo si es crédito)
            $nuevoSubtotal = max(0, (float)$item->subtotal - $montoBase);
            $nuevoIva = max(0, (float)$item->iva_monto - $ivaDevolver);
            $nuevoTotal = max(0, (float)$item->total - $totalARestar);

            // Lógica de Devolución de Dinero:
            // 1. Primero restamos del saldo pendiente (si el cliente debía dinero)
            $saldoAReducir = min((float)$item->saldo_pendiente, $totalARestar);
            $restoParaPagos = $totalARestar - $saldoAReducir;
            
            $nuevoSaldo = (float)$item->saldo_pendiente - $saldoAReducir;
            $nuevoPagoEfe = (float)$item->pago_efectivo;
            $nuevoPagoTra = (float)$item->pago_transferencia;

            // 2. Si aún queda monto por devolver, lo restamos de lo pagado (priorizando efectivo)
            if ($restoParaPagos > 0) {
                if ($nuevoPagoEfe >= $restoParaPagos) {
                    $nuevoPagoEfe -= $restoParaPagos;
                } else {
                    $sobrante = $restoParaPagos - $nuevoPagoEfe;
                    $nuevoPagoEfe = 0;
                    $nuevoPagoTra = max(0, $nuevoPagoTra - $sobrante);
                }
            }

            $this->db->query("UPDATE table_ventas SET 
                              subtotal = :sub,
                              iva_monto = :iva,
                              total = :total, 
                              pago_efectivo = :pefe,
                              pago_transferencia = :ptra,
                              saldo_pendiente = :saldo 
                              WHERE id = :vid");
            $this->db->bind(':sub', $nuevoSubtotal);
            $this->db->bind(':iva', $nuevoIva);
            $this->db->bind(':total', $nuevoTotal);
            $this->db->bind(':pefe', $nuevoPagoEfe);
            $this->db->bind(':ptra', $nuevoPagoTra);
            $this->db->bind(':saldo', $nuevoSaldo > 0 ? $nuevoSaldo : 0);
            $this->db->bind(':vid', $ventaId);
            $this->db->execute();

            // 5. Eliminar el detalle de la factura original
            $this->db->query("DELETE FROM table_ventas_detalle WHERE id = :id");
            $this->db->bind(':id', $detalleId);
            $this->db->execute();

            return true;
        } catch (Exception $e) {
            error_log("Error Devolución: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene un reporte de utilidad bruta (Venta - Costo)
     */
    public function obtenerReporteUtilidad($desde, $hasta) {
        $this->db->query("SELECT 
                            SUM(vd.precio_unitario * vd.cantidad) as total_ventas,
                            SUM(vd.costo_unitario * vd.cantidad) as total_costos,
                            (SUM(vd.precio_unitario * vd.cantidad) - SUM(vd.costo_unitario * vd.cantidad)) as utilidad_bruta
                          FROM table_ventas_detalle vd
                          JOIN table_ventas v ON vd.venta_id = v.id
                          WHERE DATE(v.fecha) BETWEEN :desde AND :hasta 
                          AND v.status IN ('COMPLETADO', 'CREDITO')");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        return $this->db->single();
    }
}