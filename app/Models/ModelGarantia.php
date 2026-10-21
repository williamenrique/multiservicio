<?php
/**
 * Modelo de Garantías
 * Gestiona el ciclo completo de garantías de servicios y repuestos.
 * 
 * v3.1 (2026-10-21) — CONDICIÓN USADO = 5 DÍAS:
 *   • obtenerDiasGarantiaRepuesto() ahora respeta la condición del producto:
 *       - Si condicion = 'USADO' → SIEMPRE 5 días (ignora dias_garantia del
 *         producto y el global de empresa).
 *       - Si condicion = 'NUEVO' → dias_garantia del producto → global → 5.
 *   • Motivo: el repuesto usado siempre tiene 5 días de garantía por política
 *     del taller, sin importar lo que el admin tenga cargado en el producto.
 */
class ModelGarantia {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /* =====================================================================
     * CONFIGURACIÓN DE GARANTÍAS
     * ===================================================================== */

    public function obtenerDiasGarantiaServicio() {
        $this->db->query("SELECT dias_garantia_servicio FROM table_company_settings WHERE id = 1");
        $cfg = $this->db->single();
        if ($cfg && $cfg->dias_garantia_servicio !== null && (int)$cfg->dias_garantia_servicio > 0) {
            return (int)$cfg->dias_garantia_servicio;
        }
        return 15;
    }

    /**
     * Obtiene los días de garantía de un repuesto.
     * 
     * v3.1: Si el repuesto es USADO → 5 días fijos (política del taller).
     *       Si es NUEVO → dias_garantia del producto → global → 5.
     */
    public function obtenerDiasGarantiaRepuesto($productoId) {
        $this->db->query("SELECT dias_garantia, condicion FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $productoId);
        $row = $this->db->single();

        if (!$row) {
            // Producto inexistente → fallback global
            return $this->obtenerGarantiaGlobalRepuesto();
        }

        // v3.1: USADO = 5 días fijos
        $condicion = strtoupper(trim((string)($row->condicion ?? 'NUEVO')));
        if ($condicion === 'USADO') {
            return 5;
        }

        // NUEVO → dias_garantia del producto si tiene
        if ($row->dias_garantia !== null && (int)$row->dias_garantia > 0) {
            return (int)$row->dias_garantia;
        }

        // Fallback global de empresa
        return $this->obtenerGarantiaGlobalRepuesto();
    }

    private function obtenerGarantiaGlobalRepuesto() {
        $this->db->query("SELECT dias_garantia_devolucion FROM table_company_settings WHERE id = 1");
        $cfg = $this->db->single();
        if ($cfg && $cfg->dias_garantia_devolucion !== null && (int)$cfg->dias_garantia_devolucion > 0) {
            return (int)$cfg->dias_garantia_devolucion;
        }
        return 5;
    }

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

    public function calcularDiferenciaDias($fecha1, $fecha2) {
        return (int)round(abs(strtotime($fecha1) - strtotime($fecha2)) / 86400);
    }

    /* =====================================================================
     * LISTADO DE FACTURAS CON DERECHO A GARANTÍA
     * ===================================================================== */

    public function listarFacturasConGarantia($limit, $offset, $search = null) {
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
        $sql .= " ORDER BY f.fecha DESC LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        if ($search) {
            $this->db->bind(':search', "%{$search}%");
        }
        $this->db->bind(':limit', (int)$limit, PDO::PARAM_INT);
        $this->db->bind(':offset', (int)$offset, PDO::PARAM_INT);
        return $this->db->resultset();
    }

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

    public function obtenerFacturaCompleta($facturaId) {
        $this->db->query("SELECT f.*, f.id AS factura_id,
                                 c.id AS cliente_cedula, c.nombre AS cliente_nombre, c.telefono AS cliente_telefono,
                                 u.username AS usuario_cobro_username, s.nombre AS usuario_cobro_nombre,
                                 os.id AS os_id, os.estado AS os_estado, os.kilometraje AS os_kilometraje,
                                 os.nivel_combustible AS os_combustible, os.diagnostico_entrada AS os_diag_entrada,
                                 os.diagnostico_salida AS os_diag_salida, os.observaciones AS os_observaciones,
                                 os.fecha_ingreso AS os_fecha_ingreso, os.fecha_entrega_estimada AS os_fecha_estimada,
                                 os.fecha_entrega_real AS os_fecha_entrega,
                                 osm.nombre AS os_mecanico_nombre
                          FROM table_facturas f
                          LEFT JOIN table_clientes c ON f.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON f.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          LEFT JOIN table_ordenes_servicio os ON f.orden_id = os.id
                          LEFT JOIN table_staff osm ON os.mecanico_id = osm.id
                          WHERE f.id = :id");
        $this->db->bind(':id', $facturaId);
        return $this->db->single();
    }

    public function obtenerItemsConGarantia($facturaId) {
        $this->db->query("SELECT vd.id AS detalle_id, vd.producto_id, vd.mecanico_id, vd.descripcion,
                                 vd.cantidad, vd.precio_unitario, vd.costo_unitario, vd.condicion_item,
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
                $diasGarantia = $this->obtenerDiasGarantiaRepuesto($it->producto_id);
                $it->dias_garantia_aplicado = $diasGarantia;
                $it->dias_garantia_servicio = $esLavado ? 0 : $diasServicioGlobal;
            } else {
                $esLavado = $this->esLavadoOAspiradoPorDescripcion($it->descripcion);
                $it->es_lavado = $esLavado;
                $it->dias_garantia_aplicado = $esLavado ? 0 : $diasServicioGlobal;
                $it->dias_garantia_servicio = $esLavado ? 0 : $diasServicioGlobal;
            }

            $it->dias_transcurridos = $this->calcularDiferenciaDias($factura->fecha, date('Y-m-d H:i:s'));
            $it->garantia_vigente = ($it->dias_transcurridos <= $it->dias_garantia_aplicado);
            $it->dias_restantes = $it->dias_garantia_aplicado - $it->dias_transcurridos;
        }

        return $items;
    }

    /* =====================================================================
     * PROCESAMIENTO DE GARANTÍA
     * ===================================================================== */

    public function procesarGarantia($datos) {
        $facturaId = (int)$datos['factura_id'];
        $tipoGarantia = mb_strtoupper(trim($datos['tipo_garantia']), 'UTF-8');
        $motivo = mb_strtoupper(trim($datos['motivo']), 'UTF-8');
        $itemsInput = $datos['items'] ?? [];
        $usuarioId = $_SESSION['user_id'] ?? null;

        if (!$facturaId || !$motivo || empty($itemsInput)) {
            return ['success' => false, 'mensaje' => 'DATOS INCOMPLETOS PARA PROCESAR LA GARANTÍA'];
        }

        $factura = $this->obtenerFacturaCompleta($facturaId);
        if (!$factura) {
            return ['success' => false, 'mensaje' => 'LA FACTURA NO EXISTE'];
        }
        if ($factura->status === 'ANULADO') {
            return ['success' => false, 'mensaje' => 'LA FACTURA YA ESTÁ ANULADA'];
        }

        $itemsFull = $this->obtenerItemsConGarantia($facturaId);
        $itemsFullMap = [];
        foreach ($itemsFull as $itf) {
            $itemsFullMap[(int)$itf->detalle_id] = $itf;
        }

        $itemsValidos = [];
        foreach ($itemsInput as $req) {
            $detalleId = (int)($req['detalle_id'] ?? 0);
            if (!isset($itemsFullMap[$detalleId])) {
                return ['success' => false, 'mensaje' => "EL ITEM #{$detalleId} NO PERTENECE A LA FACTURA"];
            }
            $itf = $itemsFullMap[$detalleId];
            if (!$itf->garantia_vigente) {
                return ['success' => false, 'mensaje' => "EL ITEM '{$itf->descripcion}' YA NO TIENE GARANTÍA VIGENTE"];
            }

            $accion = mb_strtoupper(trim($req['accion'] ?? ''), 'UTF-8');
            $destino = mb_strtoupper(trim($req['destino'] ?? 'N/A'), 'UTF-8');
            $montoAdicional = isset($req['monto_adicional']) ? (float)$req['monto_adicional'] : 0.0;

            $esRepuesto = ($itf->tipo_item === 'REPUESTO');
            $accionesValidas = $esRepuesto
                ? ['REEMPLAZAR', 'DEVOLVER', 'DANADO']
                : ['REHACER', 'DEVOLVER', 'AUMENTAR'];

            if (!in_array($accion, $accionesValidas, true)) {
                return ['success' => false, 'mensaje' => "ACCIÓN '{$accion}' NO VÁLIDA PARA EL ITEM '{$itf->descripcion}'"];
            }

            if (in_array($accion, ['REEMPLAZAR', 'DEVOLVER'], true) && $esRepuesto) {
                if (!in_array($destino, ['STOCK', 'DANADO'], true)) {
                    $destino = 'STOCK';
                }
            } else {
                $destino = 'N/A';
            }

            if ($accion === 'AUMENTAR' && $montoAdicional < 0) {
                return ['success' => false, 'mensaje' => 'EL MONTO ADICIONAL NO PUEDE SER NEGATIVO'];
            }

            $itemsValidos[] = [
                'item' => $itf,
                'accion' => $accion,
                'destino' => $destino,
                'monto_adicional' => $montoAdicional,
            ];
        }

        if (empty($itemsValidos)) {
            return ['success' => false, 'mensaje' => 'NO HAY ITEMS VÁLIDOS PARA GARANTÍA'];
        }

        try {
            $this->db->beginTransaction();

            $factorIva = ($factura->subtotal > 0) ? ((float)$factura->iva_monto / (float)$factura->subtotal) : 0.0;

            $montoManoObraRef = 0.00;
            $montoRepuestoRef = 0.00;
            $egresoDevolucion = 0.00;
            $ingresoAdicional = 0.00;
            $itemsDevueltosStock = 0;
            $itemsDescartados = 0;
            $itemsReemplazadosStock = 0;

            foreach ($itemsValidos as $iv) {
                $it = $iv['item'];
                $accion = $iv['accion'];
                $destino = $iv['destino'];
                $montoAdicional = $iv['monto_adicional'];

                $cantidad = (int)$it->cantidad;
                $precioUnit = (float)$it->precio_unitario;
                $baseItem = $precioUnit * $cantidad;
                $ivaItem = $baseItem * $factorIva;
                $totalItemConIva = $baseItem + $ivaItem;

                $esRepuesto = ($it->tipo_item === 'REPUESTO');

                if ($accion === 'DEVOLVER') {
                    if ($esRepuesto && $destino === 'STOCK') {
                        $this->reingresarStock($it->producto_id, $cantidad, $facturaId, $usuarioId,
                            'REINGRESO POR GARANTIA (DEVOLUCION) - FACTURA #' . $facturaId);
                        $itemsDevueltosStock += $cantidad;
                    } elseif ($esRepuesto && $destino === 'DANADO') {
                        $this->registrarKardexDescarte($it->producto_id, $cantidad, $facturaId, $usuarioId,
                            'DESCARTADO POR GARANTIA (DEVOLUCION) - FACTURA #' . $facturaId);
                        $itemsDescartados += $cantidad;
                    }

                    $this->db->query("INSERT INTO table_devoluciones
                        (factura_id, producto_id, descripcion, cantidad, monto_devuelto, destino, motivo,
                         dias_garantia_aplicado, dias_transcurridos, usuario_id)
                        VALUES (:fid, :pid, :desc, :cant, :monto, :dest, :motivo, :dga, :dt, :uid)");
                    $this->db->bind(':fid', $facturaId);
                    $this->db->bind(':pid', $esRepuesto ? $it->producto_id : null);
                    $this->db->bind(':desc', mb_strtoupper($it->descripcion, 'UTF-8'));
                    $this->db->bind(':cant', $cantidad);
                    $this->db->bind(':monto', $totalItemConIva);
                    $this->db->bind(':dest', $esRepuesto ? $destino : 'N/A');
                    $this->db->bind(':motivo', mb_strtoupper('GARANTIA - ' . $motivo, 'UTF-8'));
                    $this->db->bind(':dga', (int)$it->dias_garantia_aplicado);
                    $this->db->bind(':dt', (int)$it->dias_transcurridos);
                    $this->db->bind(':uid', $usuarioId);
                    $this->db->execute();

                    $egresoDevolucion += $totalItemConIva;

                    if ($esRepuesto) {
                        $montoRepuestoRef += $totalItemConIva;
                    } else {
                        $montoManoObraRef += $totalItemConIva;
                    }
                }
                elseif ($accion === 'REEMPLAZAR') {
                    if ($destino === 'STOCK') {
                        $this->reingresarStock($it->producto_id, $cantidad, $facturaId, $usuarioId,
                            'REINGRESO POR GARANTIA (REEMPLAZO) - FACTURA #' . $facturaId);
                        $itemsReemplazadosStock += $cantidad;
                        $itemsDevueltosStock += $cantidad;
                    } else {
                        $this->registrarKardexDescarte($it->producto_id, $cantidad, $facturaId, $usuarioId,
                            'DESCARTADO POR GARANTIA (REEMPLAZO) - FACTURA #' . $facturaId);
                        $itemsDescartados += $cantidad;
                    }
                    $montoRepuestoRef += $totalItemConIva;
                }
                elseif ($accion === 'DANADO') {
                    $this->registrarKardexDescarte($it->producto_id, $cantidad, $facturaId, $usuarioId,
                        'DESCARTADO POR GARANTIA - FACTURA #' . $facturaId);
                    $itemsDescartados += $cantidad;
                    $montoRepuestoRef += $totalItemConIva;
                }
                elseif ($accion === 'REHACER') {
                    $montoManoObraRef += $totalItemConIva;
                }
                elseif ($accion === 'AUMENTAR') {
                    $ingresoAdicional += $montoAdicional;
                }
            }

            $this->db->query("UPDATE table_facturas
                              SET status = 'ANULADO',
                                  observaciones = CONCAT(IFNULL(observaciones,''), ' | ANULADA POR GARANTIA')
                              WHERE id = :id");
            $this->db->bind(':id', $facturaId);
            $this->db->execute();

            $subtotalGarantia = 0.00;
            $ivaGarantia = 0.00;
            $totalGarantia = 0.00;

            if ($ingresoAdicional > 0) {
                if ($factorIva > 0) {
                    $subtotalGarantia = round($ingresoAdicional / (1 + $factorIva), 2);
                    $ivaGarantia = round($ingresoAdicional - $subtotalGarantia, 2);
                } else {
                    $subtotalGarantia = round($ingresoAdicional, 2);
                    $ivaGarantia = 0.00;
                }
                $totalGarantia = round($ingresoAdicional, 2);
            }

            $obsFacturaGarantia = mb_strtoupper(
                "FACTURA DE GARANTIA - ANULA FACTURA #{$facturaId} - {$motivo}" .
                " | ITEMS GARANTIZADOS: " . count($itemsValidos) .
                " | EGRESO DEVOLUCION: $" . number_format($egresoDevolucion, 2) .
                " | INGRESO ADICIONAL: $" . number_format($ingresoAdicional, 2),
                'UTF-8'
            );

            $this->db->query("INSERT INTO table_facturas
                (cliente_id, orden_id, placa, modelo_vehiculo, usuario_id,
                 subtotal, iva_monto, total, pago_efectivo, pago_transferencia, saldo_pendiente,
                 status, origen, observaciones)
                VALUES (:cid, :oid, :placa, :modelo, :uid,
                        :sub, :iva, :total, 0, 0, 0,
                        'COMPLETADO', 'GARANTIA', :obs)");
            $this->db->bind(':cid', $factura->cliente_id);
            $this->db->bind(':oid', $factura->orden_id);
            $this->db->bind(':placa', $factura->placa);
            $this->db->bind(':modelo', $factura->modelo_vehiculo);
            $this->db->bind(':uid', $usuarioId);
            $this->db->bind(':sub', $subtotalGarantia);
            $this->db->bind(':iva', $ivaGarantia);
            $this->db->bind(':total', $totalGarantia);
            $this->db->bind(':obs', $obsFacturaGarantia);
            $this->db->execute();
            $facturaGarantiaId = $this->db->lastInsertId();

            foreach ($itemsValidos as $iv) {
                $it = $iv['item'];
                $accion = $iv['accion'];
                $montoAdicional = $iv['monto_adicional'];

                if ($accion === 'AUMENTAR' && $montoAdicional > 0) {
                    $precioFactura = $montoAdicional;
                    $descFactura = mb_strtoupper($it->descripcion . ' [TRABAJO ADICIONAL]', 'UTF-8');
                } else {
                    $precioFactura = 0.00;
                    $descFactura = mb_strtoupper($it->descripcion . ' [GARANTIA]', 'UTF-8');
                }

                $this->db->query("INSERT INTO table_facturas_detalle
                    (factura_id, producto_id, mecanico_id, descripcion, cantidad, precio_unitario, costo_unitario)
                    VALUES (:fid, :pid, :mid, :desc, :cant, :pu, :cu)");
                $this->db->bind(':fid', $facturaGarantiaId);
                $this->db->bind(':pid', ($it->tipo_item === 'REPUESTO' && $accion === 'AUMENTAR') ? $it->producto_id : null);
                $this->db->bind(':mid', null);
                $this->db->bind(':desc', $descFactura);
                $this->db->bind(':cant', (int)$it->cantidad);
                $this->db->bind(':pu', $precioFactura);
                $this->db->bind(':cu', 0.00);
                $this->db->execute();
            }

            $diasServicioGlobal = $this->obtenerDiasGarantiaServicio();
            $diasTranscurridos = $this->calcularDiferenciaDias($factura->fecha, date('Y-m-d H:i:s'));

            $this->db->query("INSERT INTO table_garantias
                (factura_original_id, factura_garantia_id, cliente_id, placa, modelo_vehiculo,
                 tipo_garantia, motivo, monto_mano_obra, monto_repuesto, monto_total, destino_repuesto,
                 dias_garantia_servicio, dias_garantia_repuesto, dias_transcurridos, usuario_id)
                VALUES (:forig, :fgar, :cid, :placa, :modelo,
                        :tipo, :motivo, :mmo, :mrep, :mtot, :dest,
                        :dgs, :dgr, :dt, :uid)");
            $this->db->bind(':forig', $facturaId);
            $this->db->bind(':fgar', $facturaGarantiaId);
            $this->db->bind(':cid', $factura->cliente_id);
            $this->db->bind(':placa', $factura->placa);
            $this->db->bind(':modelo', $factura->modelo_vehiculo);
            $this->db->bind(':tipo', $tipoGarantia);
            $this->db->bind(':motivo', $motivo);
            $this->db->bind(':mmo', round($montoManoObraRef, 2));
            $this->db->bind(':mrep', round($montoRepuestoRef, 2));
            $this->db->bind(':mtot', round($montoManoObraRef + $montoRepuestoRef, 2));
            $this->db->bind(':dest', 'N/A');
            $this->db->bind(':dgs', $diasServicioGlobal);
            $this->db->bind(':dgr', null);
            $this->db->bind(':dt', $diasTranscurridos);
            $this->db->bind(':uid', $usuarioId);
            $this->db->execute();
            $garantiaId = $this->db->lastInsertId();

            foreach ($itemsValidos as $iv) {
                $it = $iv['item'];
                $accion = $iv['accion'];
                $destino = $iv['destino'];
                $montoAdicional = $iv['monto_adicional'];

                $cantidad = (int)$it->cantidad;
                $precioUnit = (float)$it->precio_unitario;
                $baseItem = $precioUnit * $cantidad;
                $ivaItem = $baseItem * $factorIva;
                $totalItemConIva = $baseItem + $ivaItem;

                $this->db->query("INSERT INTO table_garantias_detalle
                    (garantia_id, factura_detalle_id, producto_id, descripcion,
                     cantidad, precio_unitario, monto_base, monto_iva, monto_total,
                     tipo_item, accion, destino)
                    VALUES (:gid, :fdid, :pid, :desc, :cant, :pu, :mb, :miva, :mt, :ti, :acc, :dest)");
                $this->db->bind(':gid', $garantiaId);
                $this->db->bind(':fdid', (int)$it->detalle_id);
                $this->db->bind(':pid', $it->producto_id);
                $this->db->bind(':desc', mb_strtoupper($it->descripcion, 'UTF-8'));
                $this->db->bind(':cant', $cantidad);
                $this->db->bind(':pu', $precioUnit);
                $this->db->bind(':mb', $baseItem);
                $this->db->bind(':miva', $ivaItem);
                $this->db->bind(':mt', $totalItemConIva);
                $this->db->bind(':ti', $it->tipo_item);
                $this->db->bind(':acc', $accion);
                $this->db->bind(':dest', $destino);
                $this->db->execute();
            }

            if ($egresoDevolucion > 0.005) {
                $this->db->query("INSERT INTO table_transacciones
                    (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id)
                    VALUES (1, 'EGRESO', 'DEVOLUCION', :monto, :ref, :desc, :uid)");
                $this->db->bind(':monto', round($egresoDevolucion, 2));
                $this->db->bind(':ref', $facturaId);
                $this->db->bind(':desc', mb_strtoupper(
                    "DEVOLUCION POR GARANTIA - FACTURA ORIGINAL #{$facturaId} - NUEVA FACTURA #{$facturaGarantiaId}",
                    'UTF-8'
                ));
                $this->db->bind(':uid', $usuarioId);
                $this->db->execute();
            }

            if ($ingresoAdicional > 0.005) {
                $this->db->query("INSERT INTO table_transacciones
                    (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id)
                    VALUES (1, 'INGRESO', 'GARANTIA', :monto, :ref, :desc, :uid)");
                $this->db->bind(':monto', round($ingresoAdicional, 2));
                $this->db->bind(':ref', $facturaGarantiaId);
                $this->db->bind(':desc', mb_strtoupper(
                    "INGRESO ADICIONAL POR GARANTIA - FACTURA #{$facturaGarantiaId}",
                    'UTF-8'
                ));
                $this->db->bind(':uid', $usuarioId);
                $this->db->execute();
            }

            $this->db->commit();

            return [
                'success' => true,
                'mensaje' => 'GARANTÍA PROCESADA CORRECTAMENTE',
                'garantia_id' => $garantiaId,
                'factura_garantia_id' => $facturaGarantiaId,
                'resumen' => [
                    'egreso_devolucion' => round($egresoDevolucion, 2),
                    'ingreso_adicional' => round($ingresoAdicional, 2),
                    'items_devueltos_stock' => $itemsDevueltosStock,
                    'items_descartados' => $itemsDescartados,
                    'items_reemplazados_stock' => $itemsReemplazadosStock,
                ],
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Error procesarGarantia: ' . $e->getMessage());
            return ['success' => false, 'mensaje' => 'ERROR INTERNO AL PROCESAR LA GARANTÍA: ' . $e->getMessage()];
        }
    }

    private function reingresarStock($productoId, $cantidad, $facturaId, $usuarioId, $observacion) {
        $this->db->query("SELECT stock FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $productoId);
        $row = $this->db->single();
        if (!$row) {
            throw new Exception("PRODUCTO #{$productoId} NO EXISTE EN EL INVENTARIO");
        }
        $stockAnterior = (int)$row->stock;
        $stockActual = $stockAnterior + (int)$cantidad;

        $this->db->query("UPDATE table_inventario SET stock = stock + :cant WHERE id = :pid");
        $this->db->bind(':cant', $cantidad);
        $this->db->bind(':pid', $productoId);
        $this->db->execute();

        $this->db->query("INSERT INTO table_kardex
            (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual,
             referencia_id, usuario_id, observacion)
            VALUES (:pid, 'GARANTIA', :cant, :ant, :act, :ref, :uid, :obs)");
        $this->db->bind(':pid', $productoId);
        $this->db->bind(':cant', (int)$cantidad);
        $this->db->bind(':ant', $stockAnterior);
        $this->db->bind(':act', $stockActual);
        $this->db->bind(':ref', $facturaId);
        $this->db->bind(':uid', $usuarioId);
        $this->db->bind(':obs', mb_strtoupper($observacion, 'UTF-8'));
        $this->db->execute();
    }

    private function registrarKardexDescarte($productoId, $cantidad, $facturaId, $usuarioId, $observacion) {
        if (!$productoId) return;

        $this->db->query("SELECT stock FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $productoId);
        $row = $this->db->single();
        if (!$row) return;

        $stockActual = (int)$row->stock;

        $this->db->query("INSERT INTO table_kardex
            (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual,
             referencia_id, usuario_id, observacion)
            VALUES (:pid, 'GARANTIA', 0, :ant, :act, :ref, :uid, :obs)");
        $this->db->bind(':pid', $productoId);
        $this->db->bind(':ant', $stockActual);
        $this->db->bind(':act', $stockActual);
        $this->db->bind(':ref', $facturaId);
        $this->db->bind(':uid', $usuarioId);
        $this->db->bind(':obs', mb_strtoupper($observacion . " - CANT DESCARTADA: {$cantidad}", 'UTF-8'));
        $this->db->execute();
    }

    /* =====================================================================
     * HISTORIAL DE GARANTÍAS
     * ===================================================================== */

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

    public function obtenerFacturaOriginalCompleta($facturaId) {
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

        $this->db->query("SELECT vd.id AS detalle_id, vd.producto_id, vd.mecanico_id, vd.descripcion,
                                 vd.cantidad, vd.precio_unitario, vd.costo_unitario, vd.condicion_item,
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