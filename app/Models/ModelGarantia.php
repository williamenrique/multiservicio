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
     * Determina si un item es lavado o aspirado (no aplica garantía de servicio).
     * @param int $productoId
     * @return bool
     */
    public function esLavado($productoId) {
        if (!$productoId) return false;
        $this->db->query("SELECT categoria FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $productoId);
        $row = $this->db->single();
        if ($row) {
            $cat = strtoupper(trim($row->categoria));
            return in_array($cat, ['LAVADO', 'ASPIRADO']);
        }
        return false;
    }

    /**
     * Determina si una descripción corresponde a un lavado o aspirado.
     * Útil para servicios puros (sin producto_id) que no tienen categoría.
     * @param string $descripcion
     * @return bool
     */
    public function esLavadoOAspiradoPorDescripcion($descripcion) {
        if (!$descripcion) return false;
        $desc = mb_strtoupper(trim($descripcion), 'UTF-8');
        $palabras = ['LAVADO', 'ASPIRADO', 'LAV', 'ASPIR', 'ASPIRADA'];
        foreach ($palabras as $p) {
            if (strpos($desc, $p) !== false) {
                return true;
            }
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
        // Trae TODAS las facturas COMPLETADAS/CRÉDITO (no anuladas, no de garantía)
        // que tengan al menos un item de servicio (mano de obra) que NO sea lavado ni aspirado,
        // o que tengan repuestos (que tienen su propia garantía).
        $sql = "SELECT f.id, f.placa, f.modelo_vehiculo, f.total, f.status, f.origen, f.fecha,
                       c.id AS cliente_id, c.nombre AS cliente
                FROM table_facturas f
                LEFT JOIN table_clientes c ON f.cliente_id = c.id
                WHERE f.status IN ('COMPLETADO','CREDITO')
                  AND f.origen != 'GARANTIA'
                  AND EXISTS (
                      SELECT 1 FROM table_facturas_detalle vd
                      LEFT JOIN table_inventario i ON vd.producto_id = i.id
                      WHERE vd.factura_id = f.id
                      AND (
                          -- Servicios puros (mano de obra sin repuesto) que NO sean lavado/aspirado por descripción
                          (vd.producto_id IS NULL
                           AND UPPER(IFNULL(vd.descripcion,'')) NOT LIKE '%LAVADO%'
                           AND UPPER(IFNULL(vd.descripcion,'')) NOT LIKE '%ASPIRADO%'
                           AND UPPER(IFNULL(vd.descripcion,'')) NOT LIKE '%ASPIR%')
                          OR
                          -- Repuestos con categoría que NO sea LAVADO ni ASPIRADO
                          (vd.producto_id IS NOT NULL
                           AND UPPER(IFNULL(i.categoria,'')) NOT IN ('LAVADO','ASPIRADO'))
                      )
                  )";

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
                  AND f.origen != 'GARANTIA'
                  AND EXISTS (
                      SELECT 1 FROM table_facturas_detalle vd
                      LEFT JOIN table_inventario i ON vd.producto_id = i.id
                      WHERE vd.factura_id = f.id
                      AND (
                          (vd.producto_id IS NULL
                           AND UPPER(IFNULL(vd.descripcion,'')) NOT LIKE '%LAVADO%'
                           AND UPPER(IFNULL(vd.descripcion,'')) NOT LIKE '%ASPIRADO%'
                           AND UPPER(IFNULL(vd.descripcion,'')) NOT LIKE '%ASPIR%')
                          OR
                          (vd.producto_id IS NOT NULL
                           AND UPPER(IFNULL(i.categoria,'')) NOT IN ('LAVADO','ASPIRADO'))
                      )
                  )";
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
                $cat = strtoupper(trim($it->producto_categoria));
                $esLavado = in_array($cat, ['LAVADO', 'ASPIRADO']);
                $it->es_lavado = $esLavado;
                // Repuesto: garantía por repuesto. Lavados/aspirados no tienen garantía de servicio.
                $diasGarantia = $this->obtenerDiasGarantiaRepuesto($it->producto_id);
                $it->dias_garantia_aplicado = $diasGarantia;
                $it->dias_garantia_servicio = $esLavado ? 0 : $diasServicioGlobal;
            } else {
                // Servicio puro (mano de obra sin repuesto): verificar si es lavado/aspirado por descripción.
                $esLavado = $this->esLavadoOAspiradoPorDescripcion($it->descripcion);
                $it->es_lavado = $esLavado;
                $it->dias_garantia_aplicado = $esLavado ? 0 : $diasServicioGlobal;
                $it->dias_garantia_servicio = $esLavado ? 0 : $diasServicioGlobal;
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
        // Monto a cobrar ajustable manualmente (mano de obra no se cobra en garantía por defecto = 0)
        $montoACobrarManual = isset($datos['monto_a_cobrar']) ? (float)$datos['monto_a_cobrar'] : null;

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
                // LÓGICA: La garantía NO genera cobro (ya se cobró en la factura original).
                // Los items se registran para trazabilidad pero NO suman al total de la factura de garantía.
                // Solo el monto_a_cobrar manual (incremento adicional) determina el total a cobrar.
                // if ($accion === 'AUMENTAR') { ... }  ← ELIMINADO: no se cobra de nuevo

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

            $totalGarantia = round($totalGarantia, 2); // 0 por defecto (garantía sin cobro)
            $ivaGarantia = round($ivaGarantia, 2);
            $subtotalGarantia = round($subtotalGarantia, 2);
            $montoTotalGarantia = round($montoManoObra + $montoRepuesto, 2); // valor referencial de lo atendido

            // AJUSTE MANUAL DEL MONTO A COBRAR (sistema inteligente):
            // LÓGICA DE NEGOCIO:
            // - Por defecto la garantía NO se cobra (totalGarantia = 0): la mano de obra ya se cobró en la
            //   factura original y los repuestos se reemplazan sin cobro adicional.
            // - Si el cajero indica un monto_a_cobrar > 0, ese es el ÚNICO monto que se cobra al cliente
            //   (incremento adicional por trabajo/repuesto extra). Ese monto sí genera ingreso en caja.
            // - Si monto_a_cobrar = 0 o no se indica, la garantía se atiende sin afectar caja ni transacciones.
            $ajusteManualAplicado = false;
            if ($montoACobrarManual !== null && $montoACobrarManual >= 0) {
                $totalGarantia = round($montoACobrarManual, 2);
                // Recalcular subtotal e IVA proporcionalmente
                if ($factorIva > 0) {
                    $subtotalGarantia = round($totalGarantia / (1 + $factorIva), 2);
                    $ivaGarantia = round($totalGarantia - $subtotalGarantia, 2);
                } else {
                    $subtotalGarantia = $totalGarantia;
                    $ivaGarantia = 0;
                }
                $montoTotalGarantia = $totalGarantia;
                $ajusteManualAplicado = true;
            }

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
            // LÓGICA DE NEGOCIO:
            // - La mano de obra en garantía YA se cobró en la factura original → NO genera nuevo ingreso.
            // - Los repuestos en garantía se REEMPLAZAN, no se cobran otra vez → NO generan ingreso.
            // - La garantía NO debe afectar caja ni transacciones como doble cobro.
            // - SOLO se registra INGRESO si el cajero indica un monto a cobrar ADICIONAL (incremento extra).
            //   Ese monto adicional sí es un nuevo ingreso legítimo (trabajo/repuesto extra cobrado al cliente).
            // - La acción DEVOLVER en mano de obra NO genera egreso de dinero (no se devuelve dinero al cliente,
            //   solo se registra que se atendió la garantía del servicio ya cobrado).
            //
            // Si se necesita devolver dinero al cliente, se usa el módulo de DEVOLUCIONES, no GARANTÍA.

            // El único ingreso legítimo en garantía es el monto adicional ajustado manualmente por el cajero
            // (cuando se cobra algo extra por cubrir la garantía). Por defecto es 0 (no se cobra nada extra).
            $montoIngresoAdicional = 0.00;
            if ($montoACobrarManual !== null && $montoACobrarManual > 0) {
                $montoIngresoAdicional = round($montoACobrarManual, 2);
            }

            if ($montoIngresoAdicional > 0) {
                $this->db->query("INSERT INTO table_transacciones (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id)
                                  VALUES (:cid, 'INGRESO', 'GARANTIA', :monto, :ref, :desc, :uid)");
                $this->db->bind(':cid', 1);
                $this->db->bind(':monto', $montoIngresoAdicional);
                $this->db->bind(':ref', $garantiaId);
                $this->db->bind(':desc', mb_strtoupper('INGRESO ADICIONAL POR GARANTIA - FACTURA #' . $facturaGarantiaId, 'UTF-8'));
                $this->db->bind(':uid', $usuarioId);
                $this->db->execute();
            }
            // Si montoIngresoAdicional == 0 → NO se registra ninguna transacción.
            // La garantía se atiende sin afectar caja (ya se cobró en la factura original).

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

    /**
     * Obtiene la información COMPLETA de la factura original asociada a una garantía:
     * datos de la factura, cliente, vehículo, usuario que cobró, orden de servicio
     * (diagnósticos entrada/salida, observaciones, mecánico asignado, kilometraje,
     * combustible, fechas) y los items con mecánico que ejecutó cada uno.
     * @param int $facturaId
     * @return array|null  ['factura' => object, 'items' => array]
     */
    public function obtenerFacturaOriginalCompleta($facturaId) {
        // 1) Factura + cliente + usuario que cobró (vía staff) + orden de servicio
        $this->db->query("SELECT f.id AS factura_id, f.orden_id, f.cliente_id, f.placa, f.modelo_vehiculo,
                                 f.usuario_id, f.subtotal, f.iva_monto, f.total, f.status, f.origen,
                                 f.observaciones, f.fecha,
                                 c.id AS cliente_cedula, c.nombre AS cliente_nombre, c.telefono AS cliente_telefono,
                                 sCobro.nombre AS usuario_cobro_nombre,
                                 os.id AS os_id, os.mecanico_id AS os_mecanico_id,
                                 os.kilometraje AS os_kilometraje, os.nivel_combustible AS os_combustible,
                                 os.diagnostico_entrada AS os_diag_entrada,
                                 os.diagnostico_salida AS os_diag_salida,
                                 os.observaciones AS os_observaciones,
                                 os.estado AS os_estado, os.fecha_ingreso AS os_fecha_ingreso,
                                 os.fecha_entrega_real AS os_fecha_entrega,
                                 sMec.nombre AS os_mecanico_nombre
                          FROM table_facturas f
                          LEFT JOIN table_clientes c ON f.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON f.usuario_id = u.id
                          LEFT JOIN table_staff sCobro ON u.staff_id = sCobro.id
                          LEFT JOIN table_ordenes_servicio os ON f.orden_id = os.id
                          LEFT JOIN table_staff sMec ON os.mecanico_id = sMec.id
                          WHERE f.id = :id");
        $this->db->bind(':id', $facturaId);
        $factura = $this->db->single();
        if (!$factura) return null;

        // 2) Items de la factura con mecánico que ejecutó cada uno
        $this->db->query("SELECT vd.id AS detalle_id, vd.producto_id, vd.mecanico_id, vd.descripcion,
                                 vd.cantidad, vd.precio_unitario, vd.costo_unitario,
                                 i.nombre AS producto_nombre, i.categoria AS producto_categoria,
                                 s.nombre AS mecanico_nombre
                          FROM table_facturas_detalle vd
                          LEFT JOIN table_inventario i ON vd.producto_id = i.id
                          LEFT JOIN table_staff s ON vd.mecanico_id = s.id
                          WHERE vd.factura_id = :fid
                          ORDER BY vd.id");
        $this->db->bind(':fid', $facturaId);
        $items = $this->db->resultset();

        return ['factura' => $factura, 'items' => $items];
    }
}
