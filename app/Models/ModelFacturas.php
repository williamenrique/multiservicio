<?php
/**
 * Modelo de Facturas
 * 
 * v2.1 (2026-10-08):
 *   • listar() acepta filtro por `estado` y `cliente_id`.
 *   • obtenerPorId() incluye `estado_gestion` y datos del último abono.
 * 
 * v3.1 (2026-10-21) — CONDICIÓN + GARANTÍA EN VISTA HTML:
 *   • obtenerPorId() ahora hace JOIN con table_inventario para traer
 *     `condicion_item`, `producto_condicion_actual` y `producto_dias_garantia`.
 *   • Resuelve `dias_garantia_aplicado` por item con la MISMA regla que
 *     ModelFacturacion::obtenerVentaCompleta():
 *       - SERVICIO → dias_garantia_servicio global (15 por defecto).
 *       - PRODUCTO USADO → 5 días fijos.
 *       - PRODUCTO NUEVO → dias_garantia del producto → global → 15.
 *   • Fallbacks duros para que NUNCA quede en 0 por datos viejos.
 *   • Motivo: la vista facturas/ver.php no mostraba condición ni garantía
 *     porque el modelo no las devolvía.
 */
class ModelFacturas {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    public function listar($limit = null, $offset = null, $search = null, $desde = null, $hasta = null, $estado = null, $clienteId = null) {
        $sql = "SELECT v.*, 
                       v.origen,
                       v.estado_gestion,
                       CONCAT('FAC-', LPAD(v.id, 3, '0')) as id_formateado,
                       c.nombre as cliente_nombre, 
                       COALESCE(sv.nombre, u.username, 'SISTEMA') as vendedor_nombre,
                       COALESCE(vh.placa, v.placa) as placa,
                       COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo,
                       CASE 
                           WHEN v.orden_id IS NOT NULL THEN 'OS' 
                           WHEN (v.placa IS NOT NULL AND v.placa != '') THEN 'TALLER' 
                           ELSE 'MOSTRADOR' 
                       END as tipo_procedencia,
                       (SELECT COUNT(*) FROM table_facturas_detalle WHERE factura_id = v.id AND producto_id IS NOT NULL) as cant_productos,
                       (SELECT COUNT(*) FROM table_facturas_detalle WHERE factura_id = v.id AND producto_id IS NULL) as cant_servicios,
                       (SELECT MAX(a.fecha) FROM table_abonos_clientes a WHERE a.factura_id = v.id) as ultimo_abono_fecha,
                       (SELECT a.monto FROM table_abonos_clientes a WHERE a.factura_id = v.id ORDER BY a.fecha DESC LIMIT 1) as ultimo_abono_monto
                FROM table_facturas v
                LEFT JOIN table_clientes c ON v.cliente_id = c.id
                LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                LEFT JOIN table_staff sv ON u.staff_id = sv.id
                LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                WHERE v.status IN ('COMPLETADO', 'CREDITO', 'ANULADO')";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (v.id LIKE :search_id 
                        OR c.nombre LIKE :search_cli 
                        OR vh.placa LIKE :search_placa_h 
                        OR v.placa LIKE :search_placa_v)";
            $like = "%$search%";
            $params[':search_id']      = $like;
            $params[':search_cli']     = $like;
            $params[':search_placa_h'] = $like;
            $params[':search_placa_v'] = $like;
        }
        
        if ($desde) {
            $sql .= " AND DATE(v.fecha) >= :desde";
            $params[':desde'] = $desde;
        }
        
        if ($hasta) {
            $sql .= " AND DATE(v.fecha) <= :hasta";
            $params[':hasta'] = $hasta;
        }

        if ($estado && in_array(strtoupper($estado), ['COMPLETADO', 'CREDITO', 'ANULADO'], true)) {
            $sql .= " AND v.status = :estado";
            $params[':estado'] = strtoupper($estado);
        }

        if ($clienteId) {
            $sql .= " AND v.cliente_id = :cliente_id";
            $params[':cliente_id'] = $clienteId;
        }

        $sql .= " ORDER BY v.fecha DESC, v.id DESC";
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit']  = (int)$limit;
            $params[':offset'] = (int)$offset;
        }
        
        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return $this->db->resultSet();
    }

    public function contarTotal() {
        $this->db->query("SELECT COUNT(*) as total FROM table_facturas WHERE status IN ('COMPLETADO', 'CREDITO')");
        return (int)$this->db->single()->total;
    }

    public function contarFiltrados($search = null, $desde = null, $hasta = null, $estado = null, $clienteId = null) {
        $sql = "SELECT COUNT(*) as total FROM table_facturas v
                LEFT JOIN table_clientes c ON v.cliente_id = c.id
                LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                WHERE v.status IN ('COMPLETADO', 'CREDITO', 'ANULADO')";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (v.id LIKE :search_id 
                        OR c.nombre LIKE :search_cli 
                        OR vh.placa LIKE :search_placa_h 
                        OR v.placa LIKE :search_placa_v)";
            $like = "%$search%";
            $params[':search_id']      = $like;
            $params[':search_cli']     = $like;
            $params[':search_placa_h'] = $like;
            $params[':search_placa_v'] = $like;
        }
        if ($desde) {
            $sql .= " AND DATE(v.fecha) >= :desde";
            $params[':desde'] = $desde;
        }
        if ($hasta) {
            $sql .= " AND DATE(v.fecha) <= :hasta";
            $params[':hasta'] = $hasta;
        }
        if ($estado && in_array(strtoupper($estado), ['COMPLETADO', 'CREDITO', 'ANULADO'], true)) {
            $sql .= " AND v.status = :estado";
            $params[':estado'] = strtoupper($estado);
        }
        if ($clienteId) {
            $sql .= " AND v.cliente_id = :cliente_id";
            $params[':cliente_id'] = $clienteId;
        }

        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return (int)$this->db->single()->total;
    }

    /**
     * Obtiene una factura por ID con todos sus detalles.
     * 
     * v3.1: ahora resuelve condicion_item y dias_garantia_aplicado por item.
     */
    public function obtenerPorId($id) {
        $this->db->query("SELECT v.*, 
                                 v.origen,
                                 v.estado_gestion,
                                 CONCAT('FAC-', LPAD(v.id, 3, '0')) as id_formateado,
                                 c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.email as cliente_email, 
                                 COALESCE(vh.placa, v.placa) as placa, 
                                 COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo,
                                 vh.marca as marca_vehiculo,
                                 COALESCE(st_m.nombre, (SELECT s2.nombre FROM table_facturas_detalle vd2 JOIN table_staff s2 ON vd2.mecanico_id = s2.id WHERE vd2.factura_id = v.id AND vd2.mecanico_id IS NOT NULL LIMIT 1)) as mecanico_nombre,
                                 sv.nombre as vendedor_nombre,
                                 os.kilometraje, os.nivel_combustible, os.diagnostico_entrada as diagnostico_entrada, os.observaciones as observaciones_orden,
                                 CASE 
                                     WHEN v.orden_id IS NOT NULL THEN 'OS' 
                                     WHEN (v.placa IS NOT NULL AND v.placa != '') THEN 'TALLER' 
                                     ELSE 'MOSTRADOR' 
                                 END as tipo_procedencia
                          FROM table_facturas v
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_staff st_m ON os.mecanico_id = st_m.id
                          LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff sv ON u.staff_id = sv.id
                          WHERE v.id = :id");
        $this->db->bind(':id', $id);
        $venta = $this->db->single();

        if ($venta) {
            // ─── v3.1: JOIN con inventario para traer datos de garantía ───
            $this->db->query("SELECT vd.*, s.nombre as mecanico_nombre,
                                     i.condicion as producto_condicion_actual,
                                     i.dias_garantia as producto_dias_garantia,
                                     i.categoria as producto_categoria
                              FROM table_facturas_detalle vd
                              LEFT JOIN table_staff s ON vd.mecanico_id = s.id 
                              LEFT JOIN table_inventario i ON vd.producto_id = i.id
                              WHERE vd.factura_id = :vid");
            $this->db->bind(':vid', $id);
            $venta->items = $this->db->resultSet();

            // ─── v3.1: resolver condicion_item y dias_garantia_aplicado ───
            $diasServicioGlobal = 15;
            $diasGlobalRepuesto = 5;

            $this->db->query("SELECT dias_garantia_servicio, dias_garantia_devolucion FROM table_company_settings WHERE id = 1");
            $cfg = $this->db->single();
            if ($cfg) {
                $dServ = isset($cfg->dias_garantia_servicio) ? (int)$cfg->dias_garantia_servicio : 0;
                if ($dServ > 0) $diasServicioGlobal = $dServ;

                $dRep = isset($cfg->dias_garantia_devolucion) ? (int)$cfg->dias_garantia_devolucion : 0;
                if ($dRep > 0) $diasGlobalRepuesto = $dRep;
            }

            foreach ($venta->items as $item) {
                // Resolver condición: snapshot > condicion actual del producto > inferencia
                $cond = strtoupper(trim((string)($item->condicion_item ?? '')));
                if ($cond === '') {
                    $cond = strtoupper(trim((string)($item->producto_condicion_actual ?? '')));
                }
                if ($cond === '' && empty($item->producto_id)) {
                    $cond = 'SERVICIO';
                }
                if ($cond === '') {
                    $cond = 'NUEVO';
                }

                // Calcular días con fallback duro
                if ($cond === 'SERVICIO') {
                    $dias = $diasServicioGlobal;
                    if ($dias <= 0) $dias = 15;
                } elseif ($cond === 'USADO') {
                    $dias = 5; // Regla fija
                } else {
                    // NUEVO
                    $dProd = isset($item->producto_dias_garantia) ? (int)$item->producto_dias_garantia : 0;
                    $dias = $dProd > 0 ? $dProd : $diasGlobalRepuesto;
                    if ($dias <= 0) $dias = 15;
                }

                $item->dias_garantia_aplicado = $dias;
                $item->condicion_item = $cond;
            }

            // Historial de abonos
            $this->db->query("SELECT a.*, COALESCE(s.nombre, u.username, 'SISTEMA') as usuario_nombre
                              FROM table_abonos_clientes a
                              LEFT JOIN table_usuarios u ON a.usuario_id = u.id
                              LEFT JOIN table_staff s ON u.staff_id = s.id
                              WHERE a.factura_id = :vid
                              ORDER BY a.fecha DESC");
            $this->db->bind(':vid', $id);
            $venta->abonos = $this->db->resultSet();
        }

        if ($venta && $venta->orden_id) {
            $this->db->query("SELECT item, observacion FROM table_orden_checklist WHERE orden_id = :oid");
            $this->db->bind(':oid', $venta->orden_id);
            $venta->checklist = $this->db->resultSet();
        }

        return $venta;
    }

    public function obtenerVentaCompleta($id) {
        return $this->obtenerPorId($id);
    }
}