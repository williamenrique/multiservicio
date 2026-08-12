<?php
/**
 * Modelo de Garantías
 * Gestiona el ciclo completo de garantías de servicios y repuestos:
 *  - Listado de facturas concretadas/pendientes con derecho a garantía
 *  - Validación de garantía de servicio (15 días excepto lavados) y de repuesto
 *  - Procesamiento: anula factura original, genera factura de garantía,
 *    retorno opcional de stock, ajuste de mano de obra y cuadre en caja.
 *  - Historial de garantías
 *
 * REGLAS:
 *  - 15 días de garantía por servicio (mano de obra) excepto lavados (categoria = 'LAVADO').
 *  - Repuestos usan dias_garantia propio → global (dias_garantia_devolucion) → 5.
 *  - Datos guardados en MAYÚSCULAS (mb_strtoupper UTF-8).
 */
class ModelGarantia {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /* =====================================================================
     * CONFIGURACIÓN DE GARANTÍAS
     * ===================================================================== */

    /**
     * Días de garantía de servicio (mano de obra) configurados globalmente.
     * @return int
     */
    public function obtenerDiasGarantiaServicio() {
        $this->db->query("SELECT dias_garantia_servicio FROM table_company_settings WHERE id = 1");
        $cfg = $this->db->single();
        if ($cfg && $cfg->dias_garantia_servicio !== null && (int)$cfg->dias_garantia_servicio > 0) {
            return (int)$cfg->dias_garantia_servicio;
        }
        return 15;
    }

    /**
     * Días de garantía para un repuesto.
     * Prioridad: dias_garantia del repuesto → dias_garantia_devolucion global → 5.
     * @param int $productoId
     * @return int
     */
    public function obtenerDiasGarantiaRepuesto($productoId) {
        $this->db->query("SELECT dias_garantia FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $productoId);
        $row = $this->db->single();
        if ($row && $row->dias_garantia !== null && (int)$row->dias_garantia > 0) {
            return (int)$row->dias_garantia;
        }

        $this->db->query("SELECT dias_garantia_devolucion FROM table_company_settings WHERE id = 1");
        $cfg = $this->db->single();
        if ($cfg && $cfg->dias_garantia_devolucion !== null && (int)$cfg->dias_garantia_devolucion > 0) {
            return (int)$cfg->dias_garantia_devolucion;
        }
        return 5;
    }

    /**
     * Determina si un item es un lavado (no aplica garantía de servicio).
     * @param int $productoId
     * @return bool
     */
    public function esLavado($productoId) {
        if (!$productoId) return false;
        $this->db->query("SELECT categoria FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $productoId);
        $row = $this->db->single();
        if ($row && strtoupper(trim($row->categoria)) === 'LAVADO') {
            return true;
        }
        return false;
    }

    /**
     * Calcula la diferencia en días entre dos fechas.
     * @param string $fecha1
     * @param string $fecha2
     * @return int
     */
    public function calcularDiferenciaDias($fecha1, $fecha2) {
        return (int)round(abs(strtotime($fecha1) - strtotime($fecha2)) / 86400);
    }

    /* =====================================================================
     * LISTADO DE FACTURAS CON DERECHO A GARANTÍA
     * ===================================================================== */

    /**
     * Lista facturas COMPLETADAS o CRÉDITO (concretadas/pendientes de pago)
     * con paginación y búsqueda. Solo facturas no anuladas.
     * @param int $limit
     * @param int $offset
     * @param string|null $search
     * @return array
     */
    public function listarFacturasConGarantia($limit, $offset, $search = null) {
        $sql = "SELECT f.id, f.placa, f.modelo_vehiculo, f.total, f.status, f.origen, f.fecha,
                       c.id AS cliente_id, c.nombre AS cliente
                FROM table_facturas f
                LEFT JOIN table_clientes c ON f.cliente_id = c.id
                WHERE f.status IN ('COMPLETADO','CREDITO')
                  AND f.origen != 'GARANTIA'";

        if ($search) {
            $sql .= " AND (f.id LIKE :search OR f.placa LIKE :search OR c.nombre LIKE :search)";
        }
        $sql .= " ORDER BY f.fecha DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        if ($search) {
            $this->db->bind(':search', "%{$search}%");
        }
        $this->db->bind(':limit', (int)$limit, PDO::PARAM_INT);
        $this->db->bind(':offset', (int)$offset, PDO::PARAM_INT);
        return $this->db->resultset();
    }

    /**
     * Cuenta el total de facturas con derecho a garantía.
     * @param string|null $search
     * @return int
     */
    public function contarFacturasConGarantia($search = null) {
        $sql = "SELECT COUNT(DISTINCT f.id) AS total
                FROM table_facturas f
                LEFT JOIN table_clientes c ON f.cliente_id = c.id
                WHERE f.status IN ('COMPLETADO','CREDITO')
                  AND f.origen != 'GARANTIA'";
        if ($search) {
            $sql .= " AND (f.id LIKE :search OR f.placa LIKE :search OR c.nombre LIKE :search)";
        }
        $this->db->query($sql);
        if ($search) {
            $this->db->bind(':search', "%{$search}%");
        }
        $row = $this->db->single();
        return $row ? (int)$row->total : 0;
    }

    /* =====================================================================
     * DETALLE DE FACTURA PARA GARANTÍA
     * ===================================================================== */

    /**
     * Obtiene la cabecera completa de una factura.
     * @param int $facturaId
     * @return object|null
     */
    public function obtenerFacturaCompleta($facturaId) {
        $this->db->query("SELECT f.*, c.id AS cliente_cedula, c.nombre AS cliente_nombre, c.telefono AS cliente_telefono
                          FROM table_facturas f
                          LEFT JOIN table_clientes c ON f.cliente_id = c.id
                          WHERE f.id = :id");
        $this->db->bind(':id', $facturaId);
        return $this->db->single();
    }

    /**
     * Obtiene los items (detalle) de una factura enriquecidos con info de garantía.
     * Cada item indica: tipo (SERVICIO/REPUESTO), días de garantía aplicados,
     * días transcurridos, si la garantía está vigente y si es lavado.
     * @param int $facturaId
     * @return array
     */
    public function obtenerItemsConGarantia($facturaId) {
        $this->db->query("SELECT vd.id AS detalle_id, vd.producto_id, vd.mecanico_id, vd.descripcion,
                                 vd.cantidad, vd.precio_unitario, vd.costo_unitario,
                                 i.nombre AS producto_nombre, i.categoria AS producto_categoria,
                                 s.nombre AS mecanico_nombre
                          FROM table_facturas_detalle vd
                          LEFT JOIN table_inventario i ON vd.producto_id = i.id
                          LEFT JOIN table_staff s ON vd.mecanico_id = s.id
                          WHERE vd.factura_id = :fid");
        $this->db->bind(':fid', $facturaId);
        $items = $this->db->resultset();

        $factura = $this->obtenerFacturaCompleta($facturaId);
        $fechaFactura = $factura ? $factura->fecha : date('Y-m-d H:i:s');
        $diasServicioGlobal = $this->obtenerDiasGarantiaServicio();

        foreach ($items as $it) {
            $esRepuesto = !empty($it->producto_id);
            $it->tipo_item = $esRepuesto ? 'REPUESTO' : 'SERVICIO';

            if ($esRepuesto) {
                $esLavado = (strtoupper(trim($it->producto_categoria)) === 'LAVADO');
                $it->es_lavado = $esLavado;
                // Repuesto: garantía por repuesto. Lavados no tienen garantía de servicio.
                $diasGarantia = $this->obtenerDiasGarantiaRepuesto($it->producto_id);
                $it->dias_garantia_aplicado = $diasGarantia;
                $it->dias_garantia_servicio = $esLavado ? 0 : $diasServicioGlobal;
            } else {
                // Servicio puro (mano de obra sin repuesto): garantía de servicio.
                $it->es_lavado = false;
                $it->dias_garantia_aplicado = $diasServicioGlobal;
                $it->dias_garantia_servicio = $diasServicioGlobal;
            }

            $it->dias_transcurridos = $this->calcularDiferenciaDias($fechaFactura, date('Y-m-d H:i:s'));
            $it->garantia_vigente = ($it->dias_transcurridos <= $it->dias_garantia_aplicado);
            $it->dias_restantes = $it->dias_garantia_aplicado - $it->dias_transcurridos;
        }

        return $items;
    }

    /* =====================================================================
     * PROCESAMIENTO DE GARANTÍA
     * ===================================================================== */

    /**
     * Procesa una garantía completa:
     *  1. Anula la factura original (status = ANULADO).
     *  2. Genera una nueva factura de garantía (origen = GARANTIA).
     *  3. Reingresa stock si el repuesto vuelve al inventario.
     *  4. Ajusta mano de obra (devolver EGRESO / aumentar INGRESO).
     *  5. Registra en table_garantias y table_garantias_detalle.
     *  6. Cuadra en table_transacciones (categoria GARANTIA).
     *
     * @param array $datos {factura_id, tipo_garantia, motivo, items[], destino_repuesto}
     * @return array {success, mensaje, garantia_id}
     */
    public function procesarGarantia($datos) {
        $facturaId = (int)$datos['factura_id'];
        $tipoGarantia = mb_strtoupper(trim($datos['tipo_garantia']), 'UTF-8');
        $motivo = mb_strtoupper(trim($datos['motivo']), 'UTF-8');
        $destinoRepuesto = mb_strtoupper(trim($datos['destino_repuesto'] ?? 'N/A'), 'UTF-8');
        $items = $datos['items'] ?? [];
        $usuarioId = $_SESSION['user_id'] ?? null;

        if (!$facturaId || !$motivo || empty($items)) {
            return ['success' => false, 'mensaje' => 'DATOS INCOMPLETOS PARA PROCESAR LA GARANTÍA'];
        }

        $factura = $this->obtenerFacturaCompleta($facturaId);
        if (!$factura) {
            return ['success' => false, 'mensaje' => 'LA FACTURA NO EXISTE'];
        }
        if ($factura->status === 'ANULADO') {
            return ['success' => false, 'mensaje' => 'LA FACTURA YA ESTÁ ANULADA'];
        }

        // Validar que al menos un item tenga garantía vigente
        $itemsFull = $this->obtenerItemsConGarantia($facturaId);
        $itemsValidos = [];
        foreach ($items as $req) {
            $detalleId = (int)$req['detalle_id'];
            foreach ($itemsFull as $itf) {
                if ((int)$itf->detalle_id === $detalleId) {
                    if (!$itf->garantia_vigente) {
                        return ['success' => false, 'mensaje' => "EL ITEM '{$itf->descripcion}' YA NO TIENE GARANTÍA VIGENTE"];
                    }
                    $itemsValidos[] = ['req' => $req, 'item' => $itf];
                    break;
                }
            }
        }
        if (empty($itemsValidos)) {
            return ['success' => false, 'mensaje' => 'NO HAY ITEMS VÁLIDOS PARA GARANTÍA'];
        }

        try {
            $this->db->beginTransaction();

            // Factor IVA proporcional de la factura original
            $factorIva = $factura->subtotal > 0 ? ($factura->iva_monto / $factura->subtotal) : 0;

            $montoManoObra = 0.00;
            $montoRepuesto = 0.00;
            $subtotalGarantia = 0.00;
            $ivaGarantia = 0.00;
            $totalGarantia = 0.00;
            $detalleInsert = [];

            foreach ($itemsValidos as $iv) {
                $req = $iv['req'];
                $item = $iv['item'];

                $accion = mb_strtoupper(trim($req['accion'] ?? 'DEVOLVER'), 'UTF-8');
                $destino = mb_strtoupper(trim($req['destino'] ?? 'N/A'), 'UTF-8');
                $cantidad = (int)$item->cantidad;
                $precioUnit = (float)$item->precio_unitario;
                $baseItem = $precioUnit * $cantidad;
                $ivaItem = $baseItem * $factorIva;
                $totalItem = $baseItem + $ivaItem;

                $esRepuesto = !empty($item->producto_id);
                $tipoItem = $esRepuesto ? 'REPUESTO' : 'SERVICIO';

                if ($esRepuesto) {
                    $montoRepuesto += $totalItem;
                    // Reingreso de stock si el destino es STOCK
                    if ($destino === 'STOCK') {
                        $this->db->query("UPDATE table_inventario SET stock = stock + :cant WHERE id = :pid");
                        $this->db->bind(':cant', $cantidad);
                        $this->db->bind(':pid', $item->producto_id);
                        $this->db->execute();

                        // Kardex
                        $this->db->query("INSERT INTO table_kardex (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, referencia_id, usuario_id, observacion)
                                          VALUES (:pid, 'GARANTIA', :cant, :ant, :act, :ref, :uid, :obs)");
                        $this->db->bind(':pid', $item->producto_id);
                        $this->db->bind(':cant', $cantidad);
                        $this->db->bind(':ant', 0);
                        $this->db->bind(':act', 0);
                        $this->db->bind(':ref', $facturaId);
                        $this->db->bind(':uid', $usuarioId);
                        $this->db->bind(':obs', mb_strtoupper('REINGRESO POR GARANTIA - FACTURA #' . $facturaId, 'UTF-8'));
                        $this->db->execute();
                    }
                } else {
                    $montoManoObra += $totalItem;
                }

                // Acumular totales de la factura de garantía
                if ($accion === 'AUMENTAR') {
                    $subtotalGarantia += $baseItem;
                    $ivaGarantia += $ivaItem;
                    $totalGarantia += $totalItem;
                }

                $detalleInsert[] = [
                    'factura_detalle_id' => (int)$item->detalle_id,
                    'producto_id' => $item->producto_id,
                    'descripcion' => mb_strtoupper($item->descripcion, 'UTF-8'),
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnit,
                    'monto_base' => $baseItem,
                    'monto_iva' => $ivaItem,
                    'monto_total' => $totalItem,
                    'tipo_item' => $tipoItem,
                    'accion' => $accion,
                    'destino' => $destino,
                ];
            }

            $totalGarantia = round($totalGarantia, 2);
            $ivaGarantia = round($ivaGarantia, 2);
            $subtotalGarantia = round($subtotalGarantia, 2);
            $montoTotalGarantia = round($montoManoObra + $montoRepuesto, 2);

            // 1. ANULAR FACTURA ORIGINAL
            $this->db->query("UPDATE table_facturas SET status = 'ANULADO', observaciones = CONCAT(IFNULL(observaciones,''), ' | ANULADA POR GARANTIA') WHERE id = :id");
            $this->db->bind(':id', $facturaId);
            $this->db->execute();

            // 2. GENERAR FACTURA DE GARANTÍA (origen = GARANTIA)
            $this->db->query("INSERT INTO table_facturas (cliente_id, orden_id, placa, modelo_vehiculo, usuario_id,
                              subtotal, iva_monto, total, pago_efectivo, pago_transferencia, saldo_pendiente,
                              status, origen, observaciones)
                              VALUES (:cid, :oid, :placa, :modelo, :uid, :sub, :iva, :total, 0, 0, 0,
                              'COMPLETADO', 'GARANTIA', :obs)");
            $this->db->bind(':cid', $factura->cliente_id);
            $this->db->bind(':oid', $factura->orden_id);
            $this->db->bind(':placa', $factura->placa);
            $this->db->bind(':modelo', $factura->modelo_vehiculo);
            $this->db->bind(':uid', $usuarioId);
            $this->db->bind(':sub', $subtotalGarantia);
            $this->db->bind(':iva', $ivaGarantia);
            $this->db->bind(':total', $totalGarantia);
            $this->db->bind(':obs', mb_strtoupper('FACTURA DE GARANTIA - ANULA FACTURA #' . $facturaId . ' - ' . $motivo, 'UTF-8'));
            $this->db->execute();
            $facturaGarantiaId = $this->db->lastInsertId();

            // Detalle de la factura de garantía (solo items AUMENTAR generan líneas)
            foreach ($detalleInsert as $d) {
                if ($d['accion'] === 'AUMENTAR') {
                    $this->db->query("INSERT INTO table_facturas_detalle (factura_id, producto_id, mecanico_id, descripcion, cantidad, precio_unitario, costo_unitario)
                                      VALUES (:fid, :pid, :mid, :desc, :cant, :pu, :cu)");
                    $this->db->bind(':fid', $facturaGarantiaId);
                    $this->db->bind(':pid', $d['producto_id']);
                    $this->db->bind(':mid', null);
                    $this->db->bind(':desc', $d['descripcion']);
                    $this->db->bind(':cant', $d['cantidad']);
                    $this->db->bind(':pu', $d['precio_unitario']);
                    $this->db->bind(':cu', 0);
                    $this->db->execute();
                }
            }

            // 3. REGISTRAR CABECERA DE GARANTÍA
            $diasServicioGlobal = $this->obtenerDiasGarantiaServicio();
            $diasTranscurridos = $this->calcularDiferenciaDias($factura->fecha, date('Y-m-d H:i:s'));

            $this->db->query("INSERT INTO table_garantias (factura_original_id, factura_garantia_id, cliente_id, placa, modelo_vehiculo,
                              tipo_garantia, motivo, monto_mano_obra, monto_repuesto, monto_total, destino_repuesto,
                              dias_garantia_servicio, dias_garantia_repuesto, dias_transcurridos, usuario_id)
                              VALUES (:forig, :fgar, :cid, :placa, :modelo, :tipo, :motivo, :mmo, :mrep, :mtot, :dest,
                              :dgs, :dgr, :dt, :uid)");
            $this->db->bind(':forig', $facturaId);
            $this->db->bind(':fgar', $facturaGarantiaId);
            $this->db->bind(':cid', $factura->cliente_id);
            $this->db->bind(':placa', $factura->placa);
            $this->db->bind(':modelo', $factura->modelo_vehiculo);
            $this->db->bind(':tipo', $tipoGarantia);
            $this->db->bind(':motivo', $motivo);
            $this->db->bind(':mmo', $montoManoObra);
            $this->db->bind(':mrep', $montoRepuesto);
            $this->db->bind(':mtot', $montoTotalGarantia);
            $this->db->bind(':dest', $destinoRepuesto);
            $this->db->bind(':dgs', $diasServicioGlobal);
            $this->db->bind(':dgr', null);
            $this->db->bind(':dt', $diasTranscurridos);
            $this->db->bind(':uid', $usuarioId);
            $this->db->execute();
            $garantiaId = $this->db->lastInsertId();

            // 4. DETALLE DE GARANTÍA
            foreach ($detalleInsert as $d) {
                $this->db->query("INSERT INTO table_garantias_detalle (garantia_id, factura_detalle_id, producto_id, descripcion,
                                  cantidad, precio_unitario, monto_base, monto_iva, monto_total, tipo_item, accion, destino)
                                  VALUES (:gid, :fdid, :pid, :desc, :cant, :pu, :mb, :miva, :mt, :ti, :acc, :dest)");
                $this->db->bind(':gid', $garantiaId);
                $this->db->bind(':fdid', $d['factura_detalle_id']);
                $this->db->bind(':pid', $d['producto_id']);
                $this->db->bind(':desc', $d['descripcion']);
                $this->db->bind(':cant', $d['cantidad']);
                $this->db->bind(':pu', $d['precio_unitario']);
                $this->db->bind(':mb', $d['monto_base']);
                $this->db->bind(':miva', $d['monto_iva']);
                $this->db->bind(':mt', $d['monto_total']);
                $this->db->bind(':ti', $d['tipo_item']);
                $this->db->bind(':acc', $d['accion']);
                $this->db->bind(':dest', $d['destino']);
                $this->db->execute();
            }

            // 5. CUADRE EN CAJA (table_transacciones)
            // Si hay devolución de dinero (DEVOLVER) → EGRESO
            $montoDevolver = 0.00;
            $montoAumentar = 0.00;
            foreach ($detalleInsert as $d) {
                if ($d['accion'] === 'DEVOLVER') {
                    $montoDevolver += $d['monto_total'];
                } elseif ($d['accion'] === 'AUMENTAR') {
                    $montoAumentar += $d['monto_total'];
                }
            }

            if ($montoDevolver > 0) {
                $this->db->query("INSERT INTO table_transacciones (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id)
                                  VALUES (:cid, 'EGRESO', 'GARANTIA', :monto, :ref, :desc, :uid)");
                $this->db->bind(':cid', 1);
                $this->db->bind(':monto', $montoDevolver);
                $this->db->bind(':ref', $garantiaId);
                $this->db->bind(':desc', mb_strtoupper('EGRESO POR GARANTIA - FACTURA #' . $facturaId, 'UTF-8'));
                $this->db->bind(':uid', $usuarioId);
                $this->db->execute();
            }

            if ($montoAumentar > 0) {
                $this->db->query("INSERT INTO table_transacciones (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id)
                                  VALUES (:cid, 'INGRESO', 'GARANTIA', :monto, :ref, :desc, :uid)");
                $this->db->bind(':cid', 1);
                $this->db->bind(':monto', $montoAumentar);
                $this->db->bind(':ref', $garantiaId);
                $this->db->bind(':desc', mb_strtoupper('INGRESO POR GARANTIA - FACTURA #' . $facturaGarantiaId, 'UTF-8'));
                $this->db->bind(':uid', $usuarioId);
                $this->db->execute();
            }

            $this->db->commit();
            return [
                'success' => true,
                'mensaje' => 'GARANTÍA PROCESADA CORRECTAMENTE',
                'garantia_id' => $garantiaId,
                'factura_garantia_id' => $facturaGarantiaId,
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Error procesarGarantia: ' . $e->getMessage());
            return ['success' => false, 'mensaje' => 'ERROR INTERNO AL PROCESAR LA GARANTÍA'];
        }
    }

    /* =====================================================================
     * HISTORIAL DE GARANTÍAS
     * ===================================================================== */

    /**
     * Lista el historial de garantías con paginación, búsqueda y filtros de fecha.
     * @param int $limit
     * @param int $offset
     * @param string|null $search
     * @param string|null $desde
     * @param string|null $hasta
     * @return array
     */
    public function listarGarantias($limit, $offset, $search = null, $desde = null, $hasta = null) {
        $sql = "SELECT g.id, g.factura_original_id, g.factura_garantia_id, g.tipo_garantia, g.motivo,
                       g.monto_mano_obra, g.monto_repuesto, g.monto_total, g.destino_repuesto,
                       g.dias_garantia_servicio, g.dias_transcurridos, g.fecha,
                       c.nombre AS cliente,
                       g.placa, s.nombre AS usuario_nombre
                FROM table_garantias g
                LEFT JOIN table_clientes c ON g.cliente_id = c.id
                LEFT JOIN table_usuarios u ON g.usuario_id = u.id
                LEFT JOIN table_staff s ON u.staff_id = s.id
                WHERE 1=1";
        if ($search) {
            $sql .= " AND (g.id LIKE :search OR g.placa LIKE :search OR c.nombre LIKE :search OR g.motivo LIKE :search)";
        }
        if ($desde) {
            $sql .= " AND DATE(g.fecha) >= :desde";
        }
        if ($hasta) {
            $sql .= " AND DATE(g.fecha) <= :hasta";
        }
        $sql .= " ORDER BY g.fecha DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        if ($search) $this->db->bind(':search', "%{$search}%");
        if ($desde) $this->db->bind(':desde', $desde);
        if ($hasta) $this->db->bind(':hasta', $hasta);
        $this->db->bind(':limit', (int)$limit, PDO::PARAM_INT);
        $this->db->bind(':offset', (int)$offset, PDO::PARAM_INT);
        return $this->db->resultset();
    }

    /**
     * Cuenta el total de garantías según filtros.
     */
    public function contarGarantias($search = null, $desde = null, $hasta = null) {
        $sql = "SELECT COUNT(g.id) AS total FROM table_garantias g
                LEFT JOIN table_clientes c ON g.cliente_id = c.id WHERE 1=1";
        if ($search) {
            $sql .= " AND (g.id LIKE :search OR g.placa LIKE :search OR c.nombre LIKE :search OR g.motivo LIKE :search)";
        }
        if ($desde) $sql .= " AND DATE(g.fecha) >= :desde";
        if ($hasta) $sql .= " AND DATE(g.fecha) <= :hasta";
        $this->db->query($sql);
        if ($search) $this->db->bind(':search', "%{$search}%");
        if ($desde) $this->db->bind(':desde', $desde);
        if ($hasta) $this->db->bind(':hasta', $hasta);
        $row = $this->db->single();
        return $row ? (int)$row->total : 0;
    }

    /**
     * Obtiene una garantía completa con su detalle.
     * @param int $id
     * @return array|null
     */
    public function obtenerGarantia($id) {
        $this->db->query("SELECT g.*, c.id AS cliente_cedula, c.nombre AS cliente, c.telefono AS cliente_telefono,
                                 s.nombre AS usuario_nombre
                          FROM table_garantias g
                          LEFT JOIN table_clientes c ON g.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON g.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE g.id = :id");
        $this->db->bind(':id', $id);
        $garantia = $this->db->single();
        if (!$garantia) return null;

        $this->db->query("SELECT gd.*, i.nombre AS producto_nombre
                          FROM table_garantias_detalle gd
                          LEFT JOIN table_inventario i ON gd.producto_id = i.id
                          WHERE gd.garantia_id = :gid
                          ORDER BY gd.id");
        $this->db->bind(':gid', $id);
        $garantia->detalle = $this->db->resultset();
        return $garantia;
    }
}
