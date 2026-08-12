<?php
/**
 * Modelo de Devoluciones
 * Gestiona el ciclo completo de devoluciones de repuestos:
 *  - Listado de facturas con repuestos devolvibles
 *  - Validación de garantía (por repuesto → global)
 *  - Procesamiento de devolución (STOCK / DANADO)
 *  - Historial de devoluciones
 */
class ModelDevoluciones {
    private $db;

    public function __construct(Database $db = null) {
        $this->db = $db ?? new Database();
    }

    /**
     * Obtiene los días de garantía aplicables a un repuesto.
     * Prioridad: dias_garantia del repuesto → dias_garantia_devolucion global → 5 por defecto.
     * @param int $productoId
     * @return int
     */
    public function obtenerDiasGarantia($productoId) {
        // 1. Garantía específica del repuesto
        $this->db->query("SELECT dias_garantia FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $productoId);
        $row = $this->db->single();
        if ($row && $row->dias_garantia !== null && (int)$row->dias_garantia > 0) {
            return (int)$row->dias_garantia;
        }

        // 2. Garantía global de la empresa
        $this->db->query("SELECT dias_garantia_devolucion FROM table_company_settings WHERE id = 1");
        $cfg = $this->db->single();
        if ($cfg && $cfg->dias_garantia_devolucion !== null && (int)$cfg->dias_garantia_devolucion > 0) {
            return (int)$cfg->dias_garantia_devolucion;
        }

        // 3. Valor por defecto
        return 5;
    }

    /**
     * Obtiene la garantía global configurada por el administrador.
     * @return int
     */
    public function obtenerGarantiaGlobal() {
        $this->db->query("SELECT dias_garantia_devolucion FROM table_company_settings WHERE id = 1");
        $cfg = $this->db->single();
        if ($cfg && $cfg->dias_garantia_devolucion !== null) {
            return (int)$cfg->dias_garantia_devolucion;
        }
        return 5;
    }

    /**
     * Lista las facturas que contienen repuestos (devolvibles) con paginación y búsqueda.
     * @param int $limit
     * @param int $offset
     * @param string|null $search
     * @return array ['data' => array, 'total' => int]
     */
    public function listarFacturasConRepuestos($limit = 10, $offset = 0, $search = null) {
        $where = "WHERE vd.producto_id IS NOT NULL";
        if ($search) {
            $where .= " AND (v.id::text LIKE :search OR v.placa LIKE :search
                        OR c.nombre LIKE :search)";
        }

        // Contar total de facturas distintas
        $this->db->query("SELECT COUNT(DISTINCT v.id) as total 
                          FROM table_facturas v
                          JOIN table_facturas_detalle vd ON vd.factura_id = v.id
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          $where");
        if ($search) $this->db->bind(':search', "%$search%");
        $total = (int)$this->db->single()->total;

        // Obtener datos (DISTINCT en lugar de GROUP BY para PG)
        $this->db->query("SELECT DISTINCT v.id, v.fecha, v.total, v.saldo_pendiente, v.status,
                                 COALESCE(c.nombre,'Consumidor Final') as cliente,
                                 v.placa,
                                 (SELECT COUNT(*) FROM table_facturas_detalle vd2 
                                  WHERE vd2.factura_id = v.id AND vd2.producto_id IS NOT NULL) as items_repuestos
                          FROM table_facturas v
                          JOIN table_facturas_detalle vd ON vd.factura_id = v.id
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          $where
                          ORDER BY v.fecha DESC
                          LIMIT :limit OFFSET :offset");
        if ($search) $this->db->bind(':search', "%$search%");
        $this->db->bind(':limit', (int)$limit);
        $this->db->bind(':offset', (int)$offset);

        return ['data' => $this->db->resultSet(), 'total' => $total];
    }

    /**
     * Obtiene los items (repuestos) de una factura específica aptos para devolución.
     * Incluye cálculo de días transcurridos y estado de garantía.
     * @param int $facturaId
     * @return array
     */
    public function obtenerItemsFactura($facturaId) {
        $this->db->query("SELECT vd.id as detalle_id, vd.producto_id, vd.descripcion, vd.cantidad, 
                                 vd.precio_unitario, vd.costo_unitario,
                                 v.fecha, v.id as factura_id,
                                 i.nombre as producto_nombre, i.dias_garantia
                          FROM table_facturas_detalle vd
                          JOIN table_facturas v ON vd.factura_id = v.id
                          LEFT JOIN table_inventario i ON vd.producto_id = i.id
                          WHERE vd.factura_id = :fid AND vd.producto_id IS NOT NULL
                          ORDER BY vd.id");
        $this->db->bind(':fid', $facturaId);
        $items = $this->db->resultSet();

        // Enriquecer cada item con info de garantía
        $resultado = [];
        foreach ($items as $it) {
            $diasGarantia = $this->obtenerDiasGarantia($it->producto_id);
            $diasTranscurridos = $this->calcularDiferenciaDias(date('Y-m-d'), $it->fecha);
            $it->dias_garantia_aplicado = $diasGarantia;
            $it->dias_transcurridos = $diasTranscurridos;
            $it->garantia_vigente = ($diasTranscurridos <= $diasGarantia);
            $it->dias_restantes = max(0, $diasGarantia - $diasTranscurridos);
            $resultado[] = $it;
        }
        return $resultado;
    }

    /**
     * Procesa la devolución de un ítem específico de una factura.
     * @param int $facturaId ID de la factura
     * @param int $detalleId ID de la línea de detalle
     * @param string $destino STOCK o DANADO
     * @param string $motivo Motivo de la devolución
     * @return bool
     * @throws Exception
     */
    public function procesarDevolucion($facturaId, $detalleId, $destino, $motivo = '') {
        try {
            // 1. Obtener datos del ítem y la factura
            $this->db->query("SELECT vd.producto_id, vd.descripcion, vd.cantidad, vd.precio_unitario, 
                                     v.fecha, v.subtotal, v.iva_monto, v.total, v.saldo_pendiente,
                                     v.pago_efectivo, v.pago_transferencia
                              FROM table_facturas_detalle vd
                              JOIN table_facturas v ON vd.factura_id = v.id
                              WHERE vd.id = :id AND v.id = :vid");
            $this->db->bind(':id', $detalleId);
            $this->db->bind(':vid', $facturaId);
            $item = $this->db->single();

            if (!$item) {
                throw new Exception("El ítem de la factura no existe.");
            }

            // 2. Validar garantía configurable
            $diasGarantia = $this->obtenerDiasGarantia($item->producto_id);
            $diasTranscurridos = $this->calcularDiferenciaDias(date('Y-m-d'), $item->fecha);
            if ($diasTranscurridos > $diasGarantia) {
                throw new Exception("Plazo de devolución vencido. La garantía es de {$diasGarantia} día(s) y han transcurrido {$diasTranscurridos} día(s).");
            }

            // 3. Calcular montos proporcionales (Base + IVA)
            $montoBase = (float)$item->precio_unitario * (int)$item->cantidad;
            $factorIva = ((float)$item->subtotal > 0) ? ((float)$item->iva_monto / (float)$item->subtotal) : 0;
            $ivaDevolver = $montoBase * $factorIva;
            $totalARestar = $montoBase + $ivaDevolver;

            // 4. Si es producto y destino STOCK, reingresar al inventario
            if (!empty($item->producto_id)) {
                if ($destino === 'STOCK') {
                    $this->db->query("UPDATE table_inventario SET stock = stock + :cant, updated_at = CURRENT_TIMESTAMP WHERE id = :pid");
                    $this->db->bind(':cant', $item->cantidad);
                    $this->db->bind(':pid', $item->producto_id);
                    $this->db->execute();

                    // Registrar movimiento en Kardex (tipo DEVOLUCION = entrada)
                    $invModel = new ModelInventario($this->db);
                    $invModel->registrarMovimiento(
                        $item->producto_id, 'DEVOLUCION', $item->cantidad, $facturaId,
                        "Devolución Factura #$facturaId" . ($motivo ? " - $motivo" : '')
                    );
                }
                // Si es DANADO, NO se reingresa al stock (se descarta)
            }

            // 5. Registrar en el historial de devoluciones
            $this->db->query("INSERT INTO table_devoluciones 
                              (factura_id, producto_id, descripcion, cantidad, monto_devuelto, destino, motivo, dias_garantia_aplicado, dias_transcurridos, usuario_id) 
                              VALUES (:vid, :pid, :desc, :cant, :monto, :dest, :motivo, :dg, :dt, :uid)");
            $this->db->bind(':vid', $facturaId);
            $this->db->bind(':pid', $item->producto_id);
            $this->db->bind(':desc', mb_strtoupper($item->descripcion, 'UTF-8'));
            $this->db->bind(':cant', $item->cantidad);
            $this->db->bind(':monto', $totalARestar);
            $this->db->bind(':dest', $destino);
            $this->db->bind(':motivo', mb_strtoupper($motivo, 'UTF-8'));
            $this->db->bind(':dg', $diasGarantia);
            $this->db->bind(':dt', $diasTranscurridos);
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->execute();

            // 6. Ajustar la factura (restar del total y del saldo si es crédito)
            $nuevoSubtotal = max(0, (float)$item->subtotal - $montoBase);
            $nuevoIva = max(0, (float)$item->iva_monto - $ivaDevolver);
            $nuevoTotal = max(0, (float)$item->total - $totalARestar);

            // Lógica de devolución de dinero:
            // 1. Primero restamos del saldo pendiente (si el cliente debía)
            $saldoAReducir = min((float)$item->saldo_pendiente, $totalARestar);
            $restoParaPagos = $totalARestar - $saldoAReducir;

            $nuevoSaldo = (float)$item->saldo_pendiente - $saldoAReducir;
            $nuevoPagoEfe = (float)$item->pago_efectivo;
            $nuevoPagoTra = (float)$item->pago_transferencia;

            // 2. Si queda monto por devolver, se resta de lo pagado (prioriza efectivo)
            if ($restoParaPagos > 0) {
                if ($nuevoPagoEfe >= $restoParaPagos) {
                    $nuevoPagoEfe -= $restoParaPagos;
                } else {
                    $sobrante = $restoParaPagos - $nuevoPagoEfe;
                    $nuevoPagoEfe = 0;
                    $nuevoPagoTra = max(0, $nuevoPagoTra - $sobrante);
                }
            }

            $this->db->query("UPDATE table_facturas SET 
                              subtotal = :sub, iva_monto = :iva, total = :total, 
                              pago_efectivo = :pefe, pago_transferencia = :ptra, saldo_pendiente = :saldo 
                              WHERE id = :vid");
            $this->db->bind(':sub', $nuevoSubtotal);
            $this->db->bind(':iva', $nuevoIva);
            $this->db->bind(':total', $nuevoTotal);
            $this->db->bind(':pefe', $nuevoPagoEfe);
            $this->db->bind(':ptra', $nuevoPagoTra);
            $this->db->bind(':saldo', $nuevoSaldo > 0 ? $nuevoSaldo : 0);
            $this->db->bind(':vid', $facturaId);
            $this->db->execute();

            // 7. Registrar EGRESO por devolución en el libro mayor
            $descTransaccion = "DEVOLUCION ITEM: " . mb_strtoupper($item->descripcion, 'UTF-8');
            if ($motivo) $descTransaccion .= " | MOTIVO: " . mb_strtoupper($motivo, 'UTF-8');

            $this->db->query("INSERT INTO table_transacciones (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id) 
                              VALUES (1, 'EGRESO', 'DEVOLUCION', :monto, :ref, :desc, :uid)");
            $this->db->bind(':monto', $totalARestar);
            $this->db->bind(':ref', $facturaId);
            $this->db->bind(':desc', $descTransaccion);
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->execute();

            // 8. Eliminar el detalle de la factura original
            $this->db->query("DELETE FROM table_facturas_detalle WHERE id = :id");
            $this->db->bind(':id', $detalleId);
            $this->db->execute();

            return true;
        } catch (Exception $e) {
            error_log("Error Devolución (ModelDevoluciones): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lista el historial de devoluciones con paginación, búsqueda y filtro de fechas.
     * @param int $limit
     * @param int $offset
     * @param string|null $search
     * @param string|null $desde
     * @param string|null $hasta
     * @return array ['data' => array, 'total' => int]
     */
    public function listarDevoluciones($limit = 10, $offset = 0, $search = null, $desde = null, $hasta = null) {
        $where = "WHERE 1=1";
        if ($search) {
            $where .= " AND (d.id::text LIKE :search OR v.id::text LIKE :search OR v.placa LIKE :search
                        OR d.descripcion LIKE :search OR d.motivo LIKE :search
                        OR i.nombre LIKE :search)";
        }
        if ($desde) $where .= " AND d.fecha::date >= :desde::date";
        if ($hasta) $where .= " AND d.fecha::date <= :hasta::date";

        // Contar total
        $this->db->query("SELECT COUNT(*) as total 
                          FROM table_devoluciones d
                          LEFT JOIN table_facturas v ON d.factura_id = v.id
                          LEFT JOIN table_inventario i ON d.producto_id = i.id
                          $where");
        if ($search) $this->db->bind(':search', "%$search%");
        if ($desde) $this->db->bind(':desde', $desde);
        if ($hasta) $this->db->bind(':hasta', $hasta);
        $total = (int)$this->db->single()->total;

        // Obtener datos
        $this->db->query("SELECT d.*, v.placa,
                                 i.nombre as producto_nombre, i.codigo as producto_codigo,
                                 COALESCE(c.nombre,'Consumidor Final') as cliente,
                                 COALESCE(s.nombre,u.username) as usuario_nombre,
                                 u.username
                          FROM table_devoluciones d
                          LEFT JOIN table_facturas v ON d.factura_id = v.id
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_inventario i ON d.producto_id = i.id
                          LEFT JOIN table_usuarios u ON d.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          $where
                          ORDER BY d.fecha DESC
                          LIMIT :limit OFFSET :offset");
        if ($search) $this->db->bind(':search', "%$search%");
        if ($desde) $this->db->bind(':desde', $desde);
        if ($hasta) $this->db->bind(':hasta', $hasta);
        $this->db->bind(':limit', (int)$limit);
        $this->db->bind(':offset', (int)$offset);

        return ['data' => $this->db->resultSet(), 'total' => $total];
    }

    /**
     * Obtiene una devolución específica por ID (para vista de detalle).
     * @param int $id
     * @return object|null
     */
    public function obtenerDevolucion($id) {
        $this->db->query("SELECT d.*, v.placa, v.fecha as fecha_factura,
                                 i.nombre as producto_nombre, i.codigo as producto_codigo,
                                 COALESCE(c.nombre,'Consumidor Final') as cliente,
                                 COALESCE(s.nombre,u.username) as usuario_nombre,
                                 u.username
                          FROM table_devoluciones d
                          LEFT JOIN table_facturas v ON d.factura_id = v.id
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_inventario i ON d.producto_id = i.id
                          LEFT JOIN table_usuarios u ON d.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE d.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Helper interno para calcular diferencia de días entre fechas.
     * @param string $d1
     * @param string $d2
     * @return int
     */
    private function calcularDiferenciaDias($d1, $d2) {
        return round(abs(strtotime($d1) - strtotime($d2)) / 86400);
    }
}
