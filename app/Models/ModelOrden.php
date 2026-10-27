<?php
/**
 * Modelo de Órdenes de Servicio
 * 
 * v2.4 (2026-10-13) — Edición de fecha de entrega:
 *   • `actualizarOrden()` ahora valida que `fecha_entrega_estimada` sea
 *     ESTRICTAMENTE MAYOR a la fecha/hora actual (no solo a fecha_ingreso).
 *   • Se acepta formato `Y-m-d\TH:i` o `Y-m-d H:i:s` y se normaliza a
 *     `Y-m-d H:i:s` (mismo formato que se usa al crear la orden).
 *   • Se devuelven los valores efectivamente persistidos para que el
 *     controlador pueda exponerlos al frontend sin re-consultar.
 */
class ModelOrden {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    public function crear($data) {
        $this->db->query("INSERT INTO table_ordenes_servicio (cliente_id, placa, mecanico_id, kilometraje, nivel_combustible, diagnostico_entrada, diagnostico_salida, observaciones, estado, fecha_entrega_estimada) 
                          VALUES (:cid, :placa, :mid, :km, :comb, :diag, :diag_salida, :obs, 'RECIBIDO', :f_entrega)");
        $this->db->bind(':cid', $data['cliente_id']);
        $this->db->bind(':placa', mb_strtoupper($data['placa'], 'UTF-8'));
        $this->db->bind(':mid', !empty($data['mecanico_id']) ? $data['mecanico_id'] : null);
        $this->db->bind(':km', $data['kilometraje']);
        $this->db->bind(':comb', mb_strtoupper($data['nivel_combustible'], 'UTF-8'));
        $this->db->bind(':diag', mb_strtoupper($data['observaciones_entrada'] ?? '', 'UTF-8'));
        $this->db->bind(':diag_salida', null);
        $this->db->bind(':obs', mb_strtoupper($data['observaciones'] ?? '', 'UTF-8'));
        $this->db->bind(':f_entrega', !empty($data['fecha_entrega']) ? $data['fecha_entrega'] : null);
        
        if($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function guardarChecklist($ordenId, $items) {
        foreach ($items as $item) {
            $this->db->query("INSERT INTO table_orden_checklist (orden_id, item, estado, observacion) 
                              VALUES (:oid, :item, :estado, :obs)");
            $this->db->bind(':oid', $ordenId);
            $this->db->bind(':item', mb_strtoupper($item['item'], 'UTF-8'));
            $this->db->bind(':estado', 1); 
            $this->db->bind(':obs', mb_strtoupper($item['nota'] ?? '', 'UTF-8'));
            $this->db->execute();
        }
        return true;
    }

    public function obtenerChecklist($ordenId) {
        $this->db->query("SELECT * FROM table_orden_checklist WHERE orden_id = :oid");
        $this->db->bind(':oid', $ordenId);
        return $this->db->resultSet();
    }

    public function obtenerResumenTaller() {
        $this->db->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'RECIBIDO' THEN 1 ELSE 0 END) as recibidos,
            SUM(CASE WHEN estado IN ('DIAGNOSTICANDO', 'EN_REPARACION') THEN 1 ELSE 0 END) as reparacion,
            SUM(CASE WHEN estado = 'LISTO' THEN 1 ELSE 0 END) as listos,
            SUM(CASE WHEN mecanico_id IS NULL THEN 1 ELSE 0 END) as sin_mecanico,
            SUM(CASE WHEN fecha_entrega_estimada < NOW() AND estado NOT IN ('LISTO', 'ENTREGADO') THEN 1 ELSE 0 END) as vencidas
            FROM table_ordenes_servicio 
            WHERE estado NOT IN ('ENTREGADO', 'ANULADO')");
        return $this->db->single();
    }

    public function actualizarEstado($id, $nuevoEstado, $comentario = '') {
        try {
            $this->db->beginTransaction();
            
            $this->db->query("SELECT estado FROM table_ordenes_servicio WHERE id = :id");
            $this->db->bind(':id', $id);
            $anterior = $this->db->single()->estado;

            $this->db->query("UPDATE table_ordenes_servicio SET estado = :estado WHERE id = :id");
            $this->db->bind(':estado', mb_strtoupper($nuevoEstado, 'UTF-8'));
            $this->db->bind(':id', $id);
            $this->db->execute();

            $this->db->query("INSERT INTO table_orden_estados_log (orden_id, estado_anterior, estado_nuevo, usuario_id, comentario) 
                              VALUES (:id, :ant, :nue, :uid, :com)");
            $this->db->bind(':id', $id);
            $this->db->bind(':ant', mb_strtoupper($anterior, 'UTF-8'));
            $this->db->bind(':nue', mb_strtoupper($nuevoEstado, 'UTF-8'));
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->bind(':com', mb_strtoupper($comentario, 'UTF-8'));
            $this->db->execute();

            return $this->db->commit();
        } catch (Exception $e) { $this->db->rollBack(); return false; }
    }

    public function obtenerLogsEstado($orden_id) {
        $this->db->query("SELECT l.*, s.nombre as usuario_nombre 
                          FROM table_orden_estados_log l
                          LEFT JOIN table_usuarios u ON l.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE l.orden_id = :id 
                          ORDER BY l.fecha ASC");
        $this->db->bind(':id', $orden_id);
        return $this->db->resultSet();
    }

    public function obtenerOrdenesActivas() {
        $this->db->query("SELECT os.*, v.placa, v.marca, v.modelo, s.nombre as mecanico_nombre,
                          c.nombre as cliente_nombre, c.telefono as cliente_telefono,
                          TIMESTAMPDIFF(MINUTE, NOW(), os.fecha_entrega_estimada) as minutos_restantes,
                          (SELECT status FROM table_facturas WHERE orden_id = os.id AND status != 'ANULADO' ORDER BY id DESC LIMIT 1) as factura_status
                          FROM table_ordenes_servicio os
                          INNER JOIN table_vehiculos v ON os.placa = v.placa
                          LEFT JOIN table_clientes c ON os.cliente_id = c.id
                          LEFT JOIN table_staff s ON os.mecanico_id = s.id
                          WHERE os.estado NOT IN ('ENTREGADO')
                          ORDER BY os.fecha_ingreso DESC");
        return $this->db->resultSet();
    }

    public function obtenerDetalleOrden($id) {
        $this->db->query("SELECT os.*, v.placa, v.marca, v.modelo, v.color, v.anio, 
                          c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.email as cliente_email,
                          s.nombre as mecanico_nombre
                          FROM table_ordenes_servicio os
                          INNER JOIN table_vehiculos v ON os.placa = v.placa
                          INNER JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_staff s ON os.mecanico_id = s.id
                          WHERE os.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function obtenerHistorialExtendido($tipo, $valor) {
        $sql = "SELECT os.*, v.marca, v.modelo, s.nombre as mecanico_nombre, c.nombre as cliente_nombre
                FROM table_ordenes_servicio os
                INNER JOIN table_vehiculos v ON os.placa = v.placa
                INNER JOIN table_clientes c ON v.cliente_id = c.id
                LEFT JOIN table_staff s ON os.mecanico_id = s.id ";
        
        if (strtoupper($tipo) === 'MECANICO') {
            $sql .= "WHERE os.mecanico_id = :val ";
        } elseif (strtoupper($tipo) === 'CLIENTE') {
            $sql .= "WHERE os.cliente_id = :val ";
        } else {
            $sql .= "WHERE os.placa = :val ";
        }
        
        $sql .= "ORDER BY os.fecha_ingreso DESC";
        $this->db->query($sql);
        $this->db->bind(':val', $valor);
        return $this->db->resultSet();
    }

    public function obtenerOrdenesCerradas($limit = 10, $offset = 0, $search = null) {
        $sql = "SELECT os.*, v.marca, v.modelo, s.nombre as mecanico_nombre, c.nombre as cliente_nombre
                FROM table_ordenes_servicio os
                INNER JOIN table_vehiculos v ON os.placa = v.placa
                INNER JOIN table_clientes c ON os.cliente_id = c.id
                LEFT JOIN table_staff s ON os.mecanico_id = s.id
                WHERE os.estado = 'ENTREGADO'";
        
        $params = [];
        if ($search) {
            $sql .= " AND (os.id LIKE :search_id 
                        OR os.placa LIKE :search_placa 
                        OR c.nombre LIKE :search_cli 
                        OR s.nombre LIKE :search_mec)";
            $like = "%$search%";
            $params[':search_id']    = $like;
            $params[':search_placa'] = $like;
            $params[':search_cli']   = $like;
            $params[':search_mec']   = $like;
        }

        $sql .= " ORDER BY os.fecha_entrega_real DESC LIMIT :limit OFFSET :offset";
        $params[':limit']  = (int)$limit;
        $params[':offset'] = (int)$offset;
        
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        
        return $this->db->resultSet();
    }

    public function contarCerradas($search = null) {
        $sql = "SELECT COUNT(*) as total FROM table_ordenes_servicio os
                INNER JOIN table_clientes c ON os.cliente_id = c.id
                LEFT JOIN table_staff s ON os.mecanico_id = s.id
                WHERE os.estado = 'ENTREGADO'";
        
        $params = [];
        if ($search) {
            $sql .= " AND (os.id LIKE :search_id 
                        OR os.placa LIKE :search_placa 
                        OR c.nombre LIKE :search_cli 
                        OR s.nombre LIKE :search_mec)";
            $like = "%$search%";
            $params[':search_id']    = $like;
            $params[':search_placa'] = $like;
            $params[':search_cli']   = $like;
            $params[':search_mec']   = $like;
        }
        
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return (int)$this->db->single()->total;
    }

    public function obtenerUltimoKilometrajePorPlaca($placa) {
        $this->db->query("SELECT kilometraje FROM table_ordenes_servicio WHERE placa = :placa ORDER BY fecha_ingreso DESC LIMIT 1");
        $this->db->bind(':placa', mb_strtoupper($placa, 'UTF-8'));
        return $this->db->single();
    }

    public function guardarServicios($ordenId, $servicios, $reemplazar = true) {
        if ($reemplazar) {
            $this->db->query("DELETE FROM table_orden_servicios WHERE orden_id = :oid");
            $this->db->bind(':oid', $ordenId);
            $this->db->execute();
        }

        foreach ($servicios as $index => $servicio) {
            $descripcion = trim($servicio['descripcion'] ?? '');
            if (empty($descripcion)) continue;

            $this->db->query("INSERT INTO table_orden_servicios (orden_id, descripcion, estado, orden_visual) 
                              VALUES (:oid, :desc, :estado, :orden)");
            $this->db->bind(':oid', $ordenId);
            $this->db->bind(':desc', mb_strtoupper($descripcion, 'UTF-8'));
            $this->db->bind(':estado', mb_strtoupper($servicio['estado'] ?? 'PENDIENTE', 'UTF-8'));
            $this->db->bind(':orden', $servicio['orden_visual'] ?? ($index + 1));
            $this->db->execute();
        }
        return true;
    }

    public function agregarServicio($ordenId, $servicio) {
        $this->db->query("SELECT COALESCE(MAX(orden_visual), 0) as max_orden FROM table_orden_servicios WHERE orden_id = :oid");
        $this->db->bind(':oid', $ordenId);
        $result = $this->db->single();
        $nuevoOrden = ($result->max_orden ?? 0) + 1;

        $descripcion = trim($servicio['descripcion'] ?? '');
        if (empty($descripcion)) return false;

        $this->db->query("INSERT INTO table_orden_servicios (orden_id, descripcion, estado, orden_visual) 
                          VALUES (:oid, :desc, :estado, :orden)");
        $this->db->bind(':oid', $ordenId);
        $this->db->bind(':desc', mb_strtoupper($descripcion, 'UTF-8'));
        $this->db->bind(':estado', mb_strtoupper($servicio['estado'] ?? 'PENDIENTE', 'UTF-8'));
        $this->db->bind(':orden', $nuevoOrden);
        return $this->db->execute();
    }

    public function obtenerServicios($ordenId) {
        $this->db->query("SELECT * FROM table_orden_servicios WHERE orden_id = :oid ORDER BY orden_visual ASC, id ASC");
        $this->db->bind(':oid', $ordenId);
        return $this->db->resultSet();
    }

    public function actualizarEstadoServicio($servicioId, $estado) {
        $estadosValidos = ['PENDIENTE', 'EN_PROCESO', 'COMPLETADO', 'CANCELADO'];
        $estado = mb_strtoupper($estado, 'UTF-8');
        if (!in_array($estado, $estadosValidos)) {
            return false;
        }

        $this->db->query("UPDATE table_orden_servicios SET estado = :estado WHERE id = :id");
        $this->db->bind(':estado', $estado);
        $this->db->bind(':id', $servicioId);
        return $this->db->execute();
    }

    public function completarServiciosPendientes($ordenId) {
        $this->db->query("UPDATE table_orden_servicios 
                          SET estado = 'COMPLETADO' 
                          WHERE orden_id = :oid AND estado IN ('PENDIENTE', 'EN_PROCESO')");
        $this->db->bind(':oid', $ordenId);
        return $this->db->execute();
    }

    public function eliminarServicio($servicioId) {
        $this->db->query("DELETE FROM table_orden_servicios WHERE id = :id");
        $this->db->bind(':id', $servicioId);
        return $this->db->execute();
    }

    public function obtenerItemsOrden($ordenId) {
        $this->db->query("SELECT fd.* FROM table_facturas_detalle fd
                          INNER JOIN table_facturas f ON fd.factura_id = f.id
                          WHERE f.orden_id = :oid AND f.status = 'PENDIENTE'
                          ORDER BY fd.id ASC");
        $this->db->bind(':oid', $ordenId);
        return $this->db->resultSet();
    }

    public function tieneFacturaProcesada($ordenId) {
        $this->db->query("SELECT COUNT(*) as total 
                          FROM table_facturas 
                          WHERE orden_id = :oid 
                            AND status IN ('COMPLETADO', 'CREDITO')");
        $this->db->bind(':oid', (int)$ordenId);
        return (int)$this->db->single()->total > 0;
    }

    public function eliminarOrdenCompleta($ordenId, $motivo = '') {
        $ordenId = (int)$ordenId;

        try {
            $this->db->beginTransaction();

            $this->db->query("SELECT id, estado FROM table_ordenes_servicio WHERE id = :id");
            $this->db->bind(':id', $ordenId);
            $orden = $this->db->single();

            if (!$orden) {
                throw new Exception("La orden #{$ordenId} no existe.");
            }

            if ($orden->estado === 'ENTREGADO') {
                throw new Exception("No se puede eliminar una orden ENTREGADA. Use el historial de órdenes cerradas.");
            }

            if ($this->tieneFacturaProcesada($ordenId)) {
                throw new Exception("No se puede eliminar: la orden ya tiene una factura emitida (COMPLETADO o CREDITO).");
            }

            $this->db->query("SELECT id, presupuesto_activo_id 
                              FROM table_facturas 
                              WHERE orden_id = :oid AND status = 'PENDIENTE' 
                              LIMIT 1");
            $this->db->bind(':oid', $ordenId);
            $borrador = $this->db->single();

            if ($borrador && !empty($borrador->presupuesto_activo_id)) {
                try {
                    $pid = (int)$borrador->presupuesto_activo_id;

                    $this->db->query("SELECT COUNT(*) as total FROM table_presupuestos WHERE id = :pid");
                    $this->db->bind(':pid', $pid);
                    if ((int)$this->db->single()->total > 0) {
                        $this->db->query("UPDATE table_presupuestos_reservas 
                                          SET estado = 'LIBERADA',
                                              cantidad_liberada = cantidad_reservada,
                                              fecha_liberacion = NOW(),
                                              usuario_liberacion_id = :uid
                                          WHERE presupuesto_id = :pid AND estado = 'RESERVADA'");
                        $this->db->bind(':uid', $_SESSION['user_id'] ?? null);
                        $this->db->bind(':pid', $pid);
                        $this->db->execute();

                        $this->db->query("UPDATE table_presupuestos 
                                          SET estado = 'BORRADOR' 
                                          WHERE id = :pid 
                                            AND estado IN ('ANEXADO', 'EN_PROCESO', 'ACTIVO', 'ACEPTADO')");
                        $this->db->bind(':pid', $pid);
                        $this->db->execute();
                    }
                } catch (Throwable $e) {
                    error_log("eliminarOrdenCompleta: error liberando presupuesto: " . $e->getMessage());
                }
            }

            if ($borrador) {
                $this->db->query("DELETE FROM table_facturas WHERE id = :fid");
                $this->db->bind(':fid', (int)$borrador->id);
                $this->db->execute();
            }

            $this->db->query("DELETE FROM table_ordenes_servicio WHERE id = :id");
            $this->db->bind(':id', $ordenId);
            $this->db->execute();

            $this->db->commit();

            $desc = "Orden #{$ordenId} eliminada (estado previo: {$orden->estado}). Motivo: " . ($motivo ?: 'NO ESPECIFICADO');
            logAction('TALLER', 'DELETE_OS', $desc);

            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("eliminarOrdenCompleta falló: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza campos editables de la orden de servicio.
     * 
     * v2.4 (2026-10-13):
     *   • `fecha_entrega_estimada` debe ser ESTRICTAMENTE MAYOR a la fecha/hora
     *     actual (no solo a fecha_ingreso). Se normaliza a `Y-m-d H:i:s`.
     *   • Se devuelven los valores efectivamente persistidos para que el
     *     controlador pueda exponerlos al frontend.
     * 
     * @param int   $ordenId
     * @param array $datos  Campos a actualizar: mecanico_id, fecha_entrega_estimada,
     *                      observaciones, diagnostico_salida
     * @return array Datos persistidos: ['mecanico_id' => ..., 'fecha_entrega_estimada' => ...]
     */
    public function actualizarOrden($ordenId, $datos) {
        $ordenId = (int)$ordenId;

        $this->db->query("SELECT id, estado, fecha_ingreso, fecha_entrega_estimada FROM table_ordenes_servicio WHERE id = :id");
        $this->db->bind(':id', $ordenId);
        $orden = $this->db->single();

        if (!$orden) {
            throw new Exception("La orden #{$ordenId} no existe.");
        }

        if (in_array($orden->estado, ['ENTREGADO', 'CANCELADO'], true)) {
            throw new Exception("No se puede editar una orden en estado {$orden->estado}.");
        }

        $sets = [];
        $binds = [':id' => $ordenId];
        $resultado = [
            'mecanico_id' => $orden->mecanico_id ?? null,
            'fecha_entrega_estimada' => $orden->fecha_entrega_estimada,
        ];

        // ─── mecanico_id ───
        if (array_key_exists('mecanico_id', $datos)) {
            $mid = !empty($datos['mecanico_id']) ? $datos['mecanico_id'] : null;
            $sets[] = "mecanico_id = :mid";
            $binds[':mid'] = $mid;
            $resultado['mecanico_id'] = $mid;
        }

        // ─── fecha_entrega_estimada (v2.4: > ahora, formato Y-m-d H:i:s) ───
        if (array_key_exists('fecha_entrega_estimada', $datos)) {
            $fecha = trim((string)$datos['fecha_entrega_estimada']);

            if ($fecha === '') {
                $binds[':fed'] = null;
                $sets[] = "fecha_entrega_estimada = :fed";
                $resultado['fecha_entrega_estimada'] = null;
            } else {
                // Normalizar: aceptar 'Y-m-d\TH:i' o 'Y-m-d H:i:s'
                $fecha = str_replace('T', ' ', $fecha);
                // Si viene 'YYYY-MM-DD HH:MM' (sin segundos), agregar ':00'
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $fecha)) {
                    $fecha .= ':00';
                }

                $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $fecha);
                if (!$dt) {
                    throw new Exception("Formato de fecha inválido. Use Y-m-d H:i:s.");
                }

                // Validación: fecha > AHORA (no solo > fecha_ingreso)
                $ahora = new \DateTime();
                if ($dt <= $ahora) {
                    throw new Exception("La fecha de entrega debe ser mayor a la fecha y hora actual.");
                }

                // Validación adicional: fecha > fecha_ingreso
                if ($dt->format('Y-m-d H:i:s') < $orden->fecha_ingreso) {
                    throw new Exception("La fecha de entrega no puede ser anterior a la fecha de ingreso.");
                }

                $fechaNormalizada = $dt->format('Y-m-d H:i:s');
                $binds[':fed'] = $fechaNormalizada;
                $sets[] = "fecha_entrega_estimada = :fed";
                $resultado['fecha_entrega_estimada'] = $fechaNormalizada;
            }
        }

        // ─── observaciones ───
        if (array_key_exists('observaciones', $datos)) {
            $obs = mb_strtoupper(trim((string)$datos['observaciones']), 'UTF-8');
            $sets[] = "observaciones = :obs";
            $binds[':obs'] = $obs;
        }

        // ─── diagnostico_salida ───
        if (array_key_exists('diagnostico_salida', $datos)) {
            $ds = mb_strtoupper(trim((string)$datos['diagnostico_salida']), 'UTF-8');
            $sets[] = "diagnostico_salida = :ds";
            $binds[':ds'] = $ds;
        }

        if (empty($sets)) {
            return $resultado;
        }

        $sql = "UPDATE table_ordenes_servicio SET " . implode(', ', $sets) . " WHERE id = :id";
        $this->db->query($sql);
        foreach ($binds as $key => $val) {
            $this->db->bind($key, $val);
        }

        $this->db->execute();
        return $resultado;
    }
}