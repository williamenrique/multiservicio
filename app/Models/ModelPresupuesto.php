<?php
/**
 * Modelo de Presupuestos
 * Gestiona la creación, edición y seguimiento de presupuestos/cotizaciones.
 */
class ModelPresupuesto {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    private function generarNumero() {
        $year = date('Y');
        $this->db->query("SELECT COUNT(*) as total FROM table_presupuestos WHERE numero LIKE :pattern");
        $this->db->bind(':pattern', "PRES-$year-%");
        $count = (int)$this->db->single()->total;
        $next = $count + 1;
        return sprintf("PRES-%s-%04d", $year, $next);
    }

    public function listar($limit = 20, $offset = 0, $filters = []) {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['estado'])) {
            $where .= " AND p.estado = :estado";
            $params[':estado'] = $filters['estado'];
        }
        if (!empty($filters['cliente_id'])) {
            $where .= " AND p.cliente_id = :cliente_id";
            $params[':cliente_id'] = $filters['cliente_id'];
        }
        if (!empty($filters['desde'])) {
            $where .= " AND p.fecha_emision >= :desde";
            $params[':desde'] = $filters['desde'];
        }
        if (!empty($filters['hasta'])) {
            $where .= " AND p.fecha_emision <= :hasta";
            $params[':hasta'] = $filters['hasta'];
        }
        if (!empty($filters['search'])) {
            $where .= " AND (p.numero LIKE :search OR p.cliente_nombre LIKE :search OR p.cliente_cedula LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }

        $this->db->query("SELECT COUNT(*) as total FROM table_presupuestos p $where");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        $total = (int)$this->db->single()->total;

        $this->db->query("SELECT p.*, u.username as usuario_nombre, s.email as usuario_email
                          FROM table_presupuestos p
                          LEFT JOIN table_usuarios u ON p.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          $where
                          ORDER BY p.fecha_creacion DESC
                          LIMIT :limit OFFSET :offset");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        $this->db->bind(':limit', (int)$limit);
        $this->db->bind(':offset', (int)$offset);

        return ['data' => $this->db->resultSet(), 'total' => $total];
    }

    public function obtenerCompleto($id) {
        $this->db->query("SELECT p.*, u.username as usuario_nombre, s.nombre as staff_nombre, s.email as usuario_email
                          FROM table_presupuestos p
                          LEFT JOIN table_usuarios u ON p.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE p.id = :id");
        $this->db->bind(':id', (int)$id);
        $presupuesto = $this->db->single();

        if (!$presupuesto) return null;

        $this->db->query("SELECT pd.*, i.nombre as producto_nombre, i.codigo as producto_codigo, i.imagen as producto_imagen
                          FROM table_presupuestos_detalle pd
                          LEFT JOIN table_inventario i ON pd.producto_id = i.id
                          WHERE pd.presupuesto_id = :id
                          ORDER BY pd.orden_visual, pd.id");
        $this->db->bind(':id', (int)$id);
        $presupuesto->items = $this->db->resultSet();

        return $presupuesto;
    }

    /**
     * Normaliza la descripción de un item.
     * Prioridad: descripcion enviada → nombre del producto en inventario → 'ITEM'.
     */
    private function normalizarDescripcionItem($item, $productoId = null) {
        $descripcion = isset($item['descripcion']) ? trim((string)$item['descripcion']) : '';

        if ($descripcion === '' && !empty($productoId)) {
            $this->db->query("SELECT nombre FROM table_inventario WHERE id = :id");
            $this->db->bind(':id', $productoId);
            $prodRow = $this->db->single();
            if ($prodRow && !empty($prodRow->nombre)) {
                $descripcion = trim((string)$prodRow->nombre);
            }
        }

        if ($descripcion === '') {
            $descripcion = 'ITEM';
        }

        return mb_strtoupper($descripcion, 'UTF-8');
    }

    public function crear($data) {
        try {
            $this->db->beginTransaction();

            $numero = $this->generarNumero();

            $fechaEmision = $data['fecha_emision'] ?? date('Y-m-d');
            $validezDias = $data['validez_dias'] ?? 30;
            $fechaVencimiento = date('Y-m-d', strtotime("$fechaEmision + $validezDias days"));

            $this->db->query("INSERT INTO table_presupuestos 
                              (numero, cliente_id, cliente_nombre, cliente_cedula, cliente_telefono, 
                               cliente_email, cliente_direccion, vehiculo_placa, vehiculo_marca, 
                               vehiculo_modelo, vehiculo_anio, vehiculo_color,
                               subtotal, iva_monto, total, iva_activo, tasa_iva,
                               estado, validez_dias, fecha_emision, fecha_vencimiento,
                               observaciones, condiciones, usuario_id)
                              VALUES 
                              (:numero, :cliente_id, :cliente_nombre, :cliente_cedula, :cliente_telefono,
                               :cliente_email, :cliente_direccion, :vehiculo_placa, :vehiculo_marca,
                               :vehiculo_modelo, :vehiculo_anio, :vehiculo_color,
                               :subtotal, :iva_monto, :total, :iva_activo, :tasa_iva,
                               :estado, :validez_dias, :fecha_emision, :fecha_vencimiento,
                               :observaciones, :condiciones, :usuario_id)");

            $this->db->bind(':numero', mb_strtoupper($numero, 'UTF-8'));
            $this->db->bind(':cliente_id', $data['cliente_id'] ?? null);
            $this->db->bind(':cliente_nombre', mb_strtoupper($data['cliente_nombre'] ?? '', 'UTF-8'));
            $this->db->bind(':cliente_cedula', !empty($data['cliente_cedula']) ? mb_strtoupper($data['cliente_cedula'], 'UTF-8') : null);
            $this->db->bind(':cliente_telefono', $data['cliente_telefono'] ?? null);
            $this->db->bind(':cliente_email', !empty($data['cliente_email']) ? mb_strtolower($data['cliente_email'], 'UTF-8') : null);
            $this->db->bind(':cliente_direccion', !empty($data['cliente_direccion']) ? mb_strtoupper($data['cliente_direccion'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_placa', !empty($data['vehiculo_placa']) ? mb_strtoupper($data['vehiculo_placa'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_marca', !empty($data['vehiculo_marca']) ? mb_strtoupper($data['vehiculo_marca'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_modelo', !empty($data['vehiculo_modelo']) ? mb_strtoupper($data['vehiculo_modelo'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_anio', $data['vehiculo_anio'] ?? null);
            $this->db->bind(':vehiculo_color', !empty($data['vehiculo_color']) ? mb_strtoupper($data['vehiculo_color'], 'UTF-8') : null);
            $this->db->bind(':subtotal', $data['subtotal'] ?? 0);
            $this->db->bind(':iva_monto', $data['iva_monto'] ?? 0);
            $this->db->bind(':total', $data['total'] ?? 0);
            $this->db->bind(':iva_activo', $data['iva_activo'] ?? 1);
            $this->db->bind(':tasa_iva', $data['tasa_iva'] ?? 19.00);
            $this->db->bind(':estado', mb_strtoupper($data['estado'] ?? 'BORRADOR', 'UTF-8'));
            $this->db->bind(':validez_dias', $data['validez_dias'] ?? 30);
            $this->db->bind(':fecha_emision', $data['fecha_emision'] ?? date('Y-m-d'));
            $this->db->bind(':fecha_vencimiento', $data['fecha_vencimiento'] ?? date('Y-m-d', strtotime("+30 days")));
            $this->db->bind(':observaciones', !empty($data['observaciones']) ? mb_strtoupper($data['observaciones'], 'UTF-8') : null);
            $this->db->bind(':condiciones', !empty($data['condiciones']) ? mb_strtoupper($data['condiciones'], 'UTF-8') : null);
            $this->db->bind(':usuario_id', $data['usuario_id']);

            if (!$this->db->execute()) {
                throw new Exception("Error al crear la cabecera del presupuesto");
            }

            $presupuestoId = $this->db->lastInsertId();

            if (!empty($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    $item = (array)$item;

                    $tipoItem = mb_strtoupper($item['tipo_item'] ?? 'PRODUCTO', 'UTF-8');

                    $productoId = null;
                    if ($tipoItem === 'PRODUCTO') {
                        $productoId = !empty($item['producto_id']) ? $item['producto_id'] : null;
                    }

                    $descripcion = $this->normalizarDescripcionItem($item, $productoId);

                    $this->db->query("INSERT INTO table_presupuestos_detalle 
                                      (presupuesto_id, producto_id, tipo_item, descripcion, cantidad, 
                                       precio_unitario, descuento_porcentaje, descuento_monto, subtotal,
                                       iva_porcentaje, iva_monto, total, orden_visual, notas)
                                      VALUES 
                                      (:pid, :producto_id, :tipo_item, :descripcion, :cantidad,
                                       :precio_unitario, :descuento_porcentaje, :descuento_monto, :subtotal,
                                       :iva_porcentaje, :iva_monto, :total, :orden_visual, :notas)");

                    $this->db->bind(':pid', $presupuestoId);
                    $this->db->bind(':producto_id', $productoId);
                    $this->db->bind(':tipo_item', $tipoItem);
                    $this->db->bind(':descripcion', $descripcion);
                    $this->db->bind(':cantidad', $item['cantidad'] ?? 1);
                    $this->db->bind(':precio_unitario', $item['precio_unitario'] ?? 0);
                    $this->db->bind(':descuento_porcentaje', $item['descuento_porcentaje'] ?? 0);
                    $this->db->bind(':descuento_monto', $item['descuento_monto'] ?? 0);
                    $this->db->bind(':subtotal', $item['subtotal'] ?? 0);
                    $this->db->bind(':iva_porcentaje', $item['iva_porcentaje'] ?? 0);
                    $this->db->bind(':iva_monto', $item['iva_monto'] ?? 0);
                    $this->db->bind(':total', $item['total'] ?? 0);
                    $this->db->bind(':orden_visual', $item['orden_visual'] ?? $index);
                    $this->db->bind(':notas', !empty($item['notas']) ? mb_strtoupper($item['notas'], 'UTF-8') : null);

                    if (!$this->db->execute()) {
                        throw new Exception("Error al insertar item del presupuesto");
                    }
                }
            }

            $this->db->commit();
            return $presupuestoId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function actualizar($id, $data) {
        try {
            $this->db->beginTransaction();

            $this->db->query("UPDATE table_presupuestos SET
                              cliente_id = :cliente_id,
                              cliente_nombre = :cliente_nombre,
                              cliente_cedula = :cliente_cedula,
                              cliente_telefono = :cliente_telefono,
                              cliente_email = :cliente_email,
                              cliente_direccion = :cliente_direccion,
                              vehiculo_placa = :vehiculo_placa,
                              vehiculo_marca = :vehiculo_marca,
                              vehiculo_modelo = :vehiculo_modelo,
                              vehiculo_anio = :vehiculo_anio,
                              vehiculo_color = :vehiculo_color,
                              subtotal = :subtotal,
                              iva_monto = :iva_monto,
                              total = :total,
                              iva_activo = :iva_activo,
                              tasa_iva = :tasa_iva,
                              estado = :estado,
                              validez_dias = :validez_dias,
                              fecha_emision = :fecha_emision,
                              fecha_vencimiento = :fecha_vencimiento,
                              observaciones = :observaciones,
                              condiciones = :condiciones
                              WHERE id = :id");

            $this->db->bind(':cliente_id', $data['cliente_id'] ?? null);
            $this->db->bind(':cliente_nombre', mb_strtoupper($data['cliente_nombre'] ?? '', 'UTF-8'));
            $this->db->bind(':cliente_cedula', !empty($data['cliente_cedula']) ? mb_strtoupper($data['cliente_cedula'], 'UTF-8') : null);
            $this->db->bind(':cliente_telefono', $data['cliente_telefono'] ?? null);
            $this->db->bind(':cliente_email', !empty($data['cliente_email']) ? mb_strtolower($data['cliente_email'], 'UTF-8') : null);
            $this->db->bind(':cliente_direccion', !empty($data['cliente_direccion']) ? mb_strtoupper($data['cliente_direccion'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_placa', !empty($data['vehiculo_placa']) ? mb_strtoupper($data['vehiculo_placa'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_marca', !empty($data['vehiculo_marca']) ? mb_strtoupper($data['vehiculo_marca'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_modelo', !empty($data['vehiculo_modelo']) ? mb_strtoupper($data['vehiculo_modelo'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_anio', $data['vehiculo_anio'] ?? null);
            $this->db->bind(':vehiculo_color', !empty($data['vehiculo_color']) ? mb_strtoupper($data['vehiculo_color'], 'UTF-8') : null);
            $this->db->bind(':subtotal', $data['subtotal'] ?? 0);
            $this->db->bind(':iva_monto', $data['iva_monto'] ?? 0);
            $this->db->bind(':total', $data['total'] ?? 0);
            $this->db->bind(':iva_activo', $data['iva_activo'] ?? 1);
            $this->db->bind(':tasa_iva', $data['tasa_iva'] ?? 19.00);
            $this->db->bind(':estado', mb_strtoupper($data['estado'] ?? 'BORRADOR', 'UTF-8'));
            $this->db->bind(':validez_dias', $data['validez_dias'] ?? 30);
            $this->db->bind(':fecha_emision', $data['fecha_emision'] ?? date('Y-m-d'));
            $this->db->bind(':fecha_vencimiento', $data['fecha_vencimiento'] ?? null);
            $this->db->bind(':observaciones', !empty($data['observaciones']) ? mb_strtoupper($data['observaciones'], 'UTF-8') : null);
            $this->db->bind(':condiciones', !empty($data['condiciones']) ? mb_strtoupper($data['condiciones'], 'UTF-8') : null);
            $this->db->bind(':id', (int)$id);

            if (!$this->db->execute()) {
                throw new Exception("Error al actualizar la cabecera del presupuesto");
            }

            $this->db->query("DELETE FROM table_presupuestos_detalle WHERE presupuesto_id = :id");
            $this->db->bind(':id', (int)$id);
            $this->db->execute();

            if (!empty($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    $item = (array)$item;

                    $tipoItem = mb_strtoupper($item['tipo_item'] ?? 'PRODUCTO', 'UTF-8');

                    $productoId = null;
                    if ($tipoItem === 'PRODUCTO') {
                        $productoId = !empty($item['producto_id']) ? $item['producto_id'] : null;
                    }

                    $descripcion = $this->normalizarDescripcionItem($item, $productoId);

                    $this->db->query("INSERT INTO table_presupuestos_detalle 
                                      (presupuesto_id, producto_id, tipo_item, descripcion, cantidad, 
                                       precio_unitario, descuento_porcentaje, descuento_monto, subtotal,
                                       iva_porcentaje, iva_monto, total, orden_visual, notas)
                                      VALUES 
                                      (:pid, :producto_id, :tipo_item, :descripcion, :cantidad,
                                       :precio_unitario, :descuento_porcentaje, :descuento_monto, :subtotal,
                                       :iva_porcentaje, :iva_monto, :total, :orden_visual, :notas)");

                    $this->db->bind(':pid', (int)$id);
                    $this->db->bind(':producto_id', $productoId);
                    $this->db->bind(':tipo_item', $tipoItem);
                    $this->db->bind(':descripcion', $descripcion);
                    $this->db->bind(':cantidad', $item['cantidad'] ?? 1);
                    $this->db->bind(':precio_unitario', $item['precio_unitario'] ?? 0);
                    $this->db->bind(':descuento_porcentaje', $item['descuento_porcentaje'] ?? 0);
                    $this->db->bind(':descuento_monto', $item['descuento_monto'] ?? 0);
                    $this->db->bind(':subtotal', $item['subtotal'] ?? 0);
                    $this->db->bind(':iva_porcentaje', $item['iva_porcentaje'] ?? 0);
                    $this->db->bind(':iva_monto', $item['iva_monto'] ?? 0);
                    $this->db->bind(':total', $item['total'] ?? 0);
                    $this->db->bind(':orden_visual', $item['orden_visual'] ?? $index);
                    $this->db->bind(':notas', !empty($item['notas']) ? mb_strtoupper($item['notas'], 'UTF-8') : null);

                    if (!$this->db->execute()) {
                        throw new Exception("Error al insertar item del presupuesto");
                    }
                }
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function cambiarEstado($id, $estado) {
        $estadosValidos = ['BORRADOR', 'ENVIADO', 'ACTIVO', 'EN_PROCESO', 'ACEPTADO', 'RECHAZADO', 'EXPIRADO', 'CONVERTIDO'];
        $estado = mb_strtoupper($estado, 'UTF-8');
        if (!in_array($estado, $estadosValidos)) {
            throw new Exception("Estado no válido");
        }

        $this->db->query("UPDATE table_presupuestos SET estado = :estado WHERE id = :id");
        $this->db->bind(':estado', $estado);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function eliminar($id) {
        $this->db->query("SELECT estado FROM table_presupuestos WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        $presupuesto = $this->db->single();

        if (!$presupuesto) {
            throw new Exception("Presupuesto no encontrado");
        }

        if ($presupuesto->estado !== 'BORRADOR') {
            throw new Exception("Solo se pueden eliminar presupuestos en estado BORRADOR");
        }

        $this->db->query("DELETE FROM table_presupuestos WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function obtenerEstadisticas($desde = null, $hasta = null) {
        $where = "WHERE 1=1";
        $params = [];
        
        if ($desde) {
            $where .= " AND fecha_emision >= :desde";
            $params[':desde'] = $desde;
        }
        if ($hasta) {
            $where .= " AND fecha_emision <= :hasta";
            $params[':hasta'] = $hasta;
        }
        
        $this->db->query("SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN estado = 'BORRADOR' THEN 1 ELSE 0 END) as borradores,
                            SUM(CASE WHEN estado = 'ENVIADO' THEN 1 ELSE 0 END) as enviados,
                            SUM(CASE WHEN estado = 'ACTIVO' THEN 1 ELSE 0 END) as activos,
                            SUM(CASE WHEN estado = 'EN_PROCESO' THEN 1 ELSE 0 END) as en_proceso,
                            SUM(CASE WHEN estado = 'ACEPTADO' THEN 1 ELSE 0 END) as aceptados,
                            SUM(CASE WHEN estado = 'RECHAZADO' THEN 1 ELSE 0 END) as rechazados,
                            SUM(CASE WHEN estado = 'EXPIRADO' THEN 1 ELSE 0 END) as expirados,
                            SUM(CASE WHEN estado = 'CONVERTIDO' THEN 1 ELSE 0 END) as convertidos,
                            COALESCE(SUM(CASE WHEN estado IN ('ACEPTADO','CONVERTIDO') THEN total ELSE 0 END), 0) as monto_aceptado
                          FROM table_presupuestos $where");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->single();
    }

    public function obtenerProductosInventario($search = null) {
        $sql = "SELECT i.id, i.codigo, i.nombre, i.marca, i.precio, i.costo_promedio, i.stock,
                       i.oferta_activa, i.oferta_porcentaje, i.oferta_fecha_inicio, i.oferta_fecha_fin,
                       CASE 
                           WHEN i.oferta_activa = 1 
                                AND i.oferta_porcentaje > 0 
                                AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                                AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())
                           THEN ROUND(i.precio * (1 - i.oferta_porcentaje / 100), 2)
                           ELSE i.precio
                       END as precio_final,
                       CASE 
                           WHEN i.oferta_activa = 1 
                                AND i.oferta_porcentaje > 0 
                                AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                                AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())
                           THEN 1
                           ELSE 0
                       END as en_oferta_vigente
                FROM table_inventario i
                WHERE i.estado = 'ACTIVO'";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (i.nombre LIKE :search OR i.codigo LIKE :search OR i.categoria LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $sql .= " ORDER BY i.nombre ASC LIMIT 50";
        
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->resultSet();
    }

    public function obtenerClientes($search = null) {
        $sql = "SELECT c.id, c.nombre, c.telefono, c.email, c.direccion,
                       v.placa, v.marca, v.modelo, v.anio, v.color
                FROM table_clientes c
                LEFT JOIN (
                    SELECT cliente_id, placa, marca, modelo, anio, color
                    FROM table_vehiculos
                    WHERE placa IN (
                        SELECT MIN(placa) FROM table_vehiculos GROUP BY cliente_id
                    )
                ) v ON c.id = v.cliente_id
                WHERE 1=1";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (c.nombre LIKE :search OR c.id LIKE :search OR c.telefono LIKE :search OR c.email LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $sql .= " ORDER BY c.nombre ASC LIMIT 50";
        
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->resultSet();
    }

    public function activar($id, $usuarioId) {
        try {
            $this->db->beginTransaction();

            $this->db->query("SELECT estado FROM table_presupuestos WHERE id = :id");
            $this->db->bind(':id', (int)$id);
            $presupuesto = $this->db->single();

            if (!$presupuesto) {
                throw new Exception("Presupuesto no encontrado");
            }

            $estadosValidosParaActivar = ['BORRADOR', 'ENVIADO'];
            if (!in_array($presupuesto->estado, $estadosValidosParaActivar)) {
                throw new Exception("Solo se pueden activar presupuestos en estado BORRADOR o ENVIADO");
            }

            $this->db->query("SELECT pd.*, i.stock, i.nombre 
                              FROM table_presupuestos_detalle pd
                              LEFT JOIN table_inventario i ON pd.producto_id = i.id
                              WHERE pd.presupuesto_id = :id AND pd.tipo_item = 'PRODUCTO' AND pd.producto_id IS NOT NULL");
            $this->db->bind(':id', (int)$id);
            $items = $this->db->resultSet();

            foreach ($items as $item) {
                $stockDisponible = $item->stock;
                if ($stockDisponible < $item->cantidad) {
                    throw new Exception("Stock insuficiente para '{$item->nombre}'. Disponible: {$stockDisponible}, Requerido: {$item->cantidad}");
                }
            }

            $this->db->query("UPDATE table_presupuestos SET 
                              estado = 'ACTIVO', 
                              fecha_activacion = NOW(), 
                              usuario_activacion_id = :usuarioId 
                              WHERE id = :id");
            $this->db->bind(':id', (int)$id);
            $this->db->bind(':usuarioId', (int)$usuarioId);
            $this->db->execute();

            foreach ($items as $item) {
                $this->db->query("UPDATE table_inventario SET stock = stock - :cant WHERE id = :pid");
                $this->db->bind(':cant', $item->cantidad);
                $this->db->bind(':pid', $item->producto_id);
                $this->db->execute();

                $this->db->query("INSERT INTO table_presupuestos_reservas 
                                  (presupuesto_id, producto_id, cantidad_reservada, estado) 
                                  VALUES (:pid, :producto_id, :cant, 'RESERVADA')");
                $this->db->bind(':pid', (int)$id);
                $this->db->bind(':producto_id', $item->producto_id);
                $this->db->bind(':cant', $item->cantidad);
                $this->db->execute();

                $invModel = new ModelInventario($this->db);
                $invModel->registrarMovimiento(
                    $item->producto_id, 
                    'RESERVA_PRESUPUESTO', 
                    $item->cantidad, 
                    $id, 
                    "RESERVA POR PRESUPUESTO #{$id}"
                );
            }

            $this->db->query("INSERT INTO table_audit_logs (usuario_id, modulo, accion, descripcion, ip_address, fecha) 
                              VALUES (:uid, 'PRESUPUESTO', 'ACTIVAR', :desc, :ip, NOW())");
            $this->db->bind(':uid', $usuarioId);
            $this->db->bind(':desc', mb_strtoupper("Presupuesto #{$id} activado y stock reservado", 'UTF-8'));
            $this->db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            $this->db->execute();

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function liberarInventario($id, $usuarioId, $motivo = 'CANCELACION') {
        try {
            $this->db->beginTransaction();

            $this->db->query("SELECT * FROM table_presupuestos_reservas 
                              WHERE presupuesto_id = :id AND estado = 'RESERVADA'");
            $this->db->bind(':id', (int)$id);
            $reservas = $this->db->resultSet();

            foreach ($reservas as $reserva) {
                $this->db->query("UPDATE table_inventario SET stock = stock + :cant WHERE id = :pid");
                $this->db->bind(':cant', $reserva->cantidad_reservada);
                $this->db->bind(':pid', $reserva->producto_id);
                $this->db->execute();

                $this->db->query("UPDATE table_presupuestos_reservas SET 
                                  estado = 'LIBERADA', 
                                  cantidad_liberada = cantidad_reservada,
                                  fecha_liberacion = NOW(),
                                  usuario_liberacion_id = :uid
                                  WHERE id = :rid");
                $this->db->bind(':uid', (int)$usuarioId);
                $this->db->bind(':rid', $reserva->id);
                $this->db->execute();

                $invModel = new ModelInventario($this->db);
                $invModel->registrarMovimiento(
                    $reserva->producto_id, 
                    'LIBERACION_PRESUPUESTO', 
                    $reserva->cantidad_reservada, 
                    $id, 
                    "LIBERACION RESERVA PRESUPUESTO #{$id}: " . mb_strtoupper($motivo, 'UTF-8')
                );
            }

            $this->db->query("UPDATE table_presupuestos SET estado = 'BORRADOR' WHERE id = :id AND estado = 'ACTIVO'");
            $this->db->bind(':id', (int)$id);
            $this->db->execute();

            $this->db->query("INSERT INTO table_audit_logs (usuario_id, modulo, accion, descripcion, ip_address, fecha) 
                              VALUES (:uid, 'PRESUPUESTO', 'LIBERAR_INVENTARIO', :desc, :ip, NOW())");
            $this->db->bind(':uid', $usuarioId);
            $this->db->bind(':desc', mb_strtoupper("Inventario liberado para Presupuesto #{$id}: {$motivo}", 'UTF-8'));
            $this->db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            $this->db->execute();

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function liberarInventarioPorFacturacion($id, $usuarioId) {
        try {
            $this->db->beginTransaction();

            $this->db->query("SELECT * FROM table_presupuestos_reservas 
                              WHERE presupuesto_id = :id AND estado = 'RESERVADA'");
            $this->db->bind(':id', (int)$id);
            $reservas = $this->db->resultSet();

            foreach ($reservas as $reserva) {
                $this->db->query("UPDATE table_presupuestos_reservas SET 
                                  estado = 'FACTURADA', 
                                  cantidad_liberada = cantidad_reservada,
                                  fecha_liberacion = NOW(),
                                  usuario_liberacion_id = :uid
                                  WHERE id = :rid");
                $this->db->bind(':uid', (int)$usuarioId);
                $this->db->bind(':rid', $reserva->id);
                $this->db->execute();

                $invModel = new ModelInventario($this->db);
                $invModel->registrarMovimiento(
                    $reserva->producto_id, 
                    'FACTURACION_PRESUPUESTO', 
                    $reserva->cantidad_reservada, 
                    $id, 
                    "FACTURACION DE PRESUPUESTO #{$id}"
                );
            }

            $this->db->query("UPDATE table_presupuestos SET estado = 'CONVERTIDO' WHERE id = :id AND estado = 'ACTIVO'");
            $this->db->bind(':id', (int)$id);
            $this->db->execute();

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function buscarActivos($search = null) {
        $sql = "SELECT p.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.email as cliente_email
                FROM table_presupuestos p
                LEFT JOIN table_clientes c ON p.cliente_id = c.id
                WHERE p.estado = 'ACTIVO'";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (p.numero LIKE :search OR p.cliente_nombre LIKE :search OR p.cliente_cedula LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $sql .= " ORDER BY p.fecha_activacion DESC LIMIT 50";
        
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->resultSet();
    }

    public function obtenerActivoCompleto($id) {
        $presupuesto = $this->obtenerCompleto($id);
        
        if (!$presupuesto || $presupuesto->estado !== 'ACTIVO') {
            return null;
        }

        $this->db->query("SELECT pr.*, i.nombre as producto_nombre, i.codigo as producto_codigo, i.stock as stock_actual
                          FROM table_presupuestos_reservas pr
                          LEFT JOIN table_inventario i ON pr.producto_id = i.id
                          WHERE pr.presupuesto_id = :id AND pr.estado = 'RESERVADA'");
        $this->db->bind(':id', (int)$id);
        $presupuesto->reservas = $this->db->resultSet();

        return $presupuesto;
    }

    public function iniciarProceso($id) {
        $this->db->query("UPDATE table_presupuestos SET estado = 'EN_PROCESO' WHERE id = :id AND estado = 'ACTIVO'");
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function aceptar($id, $usuarioId) {
        try {
            $this->db->beginTransaction();

            $this->db->query("SELECT estado FROM table_presupuestos WHERE id = :id");
            $this->db->bind(':id', (int)$id);
            $presupuesto = $this->db->single();

            if (!$presupuesto) {
                throw new Exception("Presupuesto no encontrado");
            }

            $estadosValidosParaAceptar = ['BORRADOR', 'ENVIADO', 'ACTIVO'];
            if (!in_array($presupuesto->estado, $estadosValidosParaAceptar)) {
                throw new Exception("Solo se pueden aceptar presupuestos en estado BORRADOR, ENVIADO o ACTIVO");
            }

            $this->db->query("SELECT pd.*, i.stock, i.nombre 
                              FROM table_presupuestos_detalle pd
                              LEFT JOIN table_inventario i ON pd.producto_id = i.id
                              WHERE pd.presupuesto_id = :id AND pd.tipo_item = 'PRODUCTO' AND pd.producto_id IS NOT NULL");
            $this->db->bind(':id', (int)$id);
            $items = $this->db->resultSet();

            foreach ($items as $item) {
                $stockDisponible = $item->stock;
                if ($stockDisponible < $item->cantidad) {
                    throw new Exception("Stock insuficiente para '{$item->nombre}'. Disponible: {$stockDisponible}, Requerido: {$item->cantidad}");
                }
            }

            $this->db->query("UPDATE table_presupuestos SET 
                              estado = 'ACEPTADO', 
                              fecha_activacion = NOW(), 
                              usuario_activacion_id = :usuarioId 
                              WHERE id = :id");
            $this->db->bind(':id', (int)$id);
            $this->db->bind(':usuarioId', (int)$usuarioId);
            $this->db->execute();

            foreach ($items as $item) {
                $this->db->query("UPDATE table_inventario SET stock = stock - :cant WHERE id = :pid");
                $this->db->bind(':cant', $item->cantidad);
                $this->db->bind(':pid', $item->producto_id);
                $this->db->execute();

                $this->db->query("INSERT INTO table_presupuestos_reservas 
                                  (presupuesto_id, producto_id, cantidad_reservada, estado) 
                                  VALUES (:pid, :producto_id, :cant, 'RESERVADA')");
                $this->db->bind(':pid', (int)$id);
                $this->db->bind(':producto_id', $item->producto_id);
                $this->db->bind(':cant', $item->cantidad);
                $this->db->execute();

                $invModel = new ModelInventario($this->db);
                $invModel->registrarMovimiento(
                    $item->producto_id, 
                    'RESERVA_PRESUPUESTO', 
                    $item->cantidad, 
                    $id, 
                    "RESERVA POR PRESUPUESTO ACEPTADO #{$id}"
                );
            }

            $this->db->query("INSERT INTO table_audit_logs (usuario_id, modulo, accion, descripcion, ip_address, fecha) 
                              VALUES (:uid, 'PRESUPUESTO', 'ACEPTAR', :desc, :ip, NOW())");
            $this->db->bind(':uid', $usuarioId);
            $this->db->bind(':desc', mb_strtoupper("Presupuesto #{$id} aceptado y stock reservado", 'UTF-8'));
            $this->db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            $this->db->execute();

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function convertirAVenta($id, $usuarioId, $datosPago = []) {
        try {
            $this->db->beginTransaction();

            $presupuesto = $this->obtenerCompleto((int)$id);
            if (!$presupuesto) {
                throw new Exception("Presupuesto no encontrado");
            }

            $estadosValidosParaConvertir = ['BORRADOR', 'ENVIADO', 'ACTIVO', 'ACEPTADO'];
            if (!in_array($presupuesto->estado, $estadosValidosParaConvertir)) {
                throw new Exception("Solo se pueden convertir presupuestos en estado BORRADOR, ENVIADO, ACTIVO o ACEPTADO");
            }

            $this->db->query("SELECT pd.*, i.nombre as producto_nombre, i.costo_promedio
                              FROM table_presupuestos_detalle pd
                              LEFT JOIN table_inventario i ON pd.producto_id = i.id
                              WHERE pd.presupuesto_id = :id
                              ORDER BY pd.orden_visual, pd.id");
            $this->db->bind(':id', (int)$id);
            $items = $this->db->resultSet();

            if (empty($items)) {
                throw new Exception("El presupuesto no tiene items para convertir a venta");
            }

            foreach ($items as $item) {
                if ($item->tipo_item === 'PRODUCTO' && $item->producto_id) {
                    $this->db->query("SELECT stock FROM table_inventario WHERE id = :pid");
                    $this->db->bind(':pid', $item->producto_id);
                    $producto = $this->db->single();
                    if ($producto && $producto->stock < $item->cantidad) {
                        throw new Exception("Stock insuficiente para '{$item->producto_nombre}'. Disponible: {$producto->stock}, Requerido: {$item->cantidad}");
                    }
                }
            }

            $pagoEfectivo = $datosPago['pago_efectivo'] ?? $presupuesto->total;
            $pagoTransferencia = $datosPago['pago_transferencia'] ?? 0;
            $saldoPendiente = max(0, $presupuesto->total - ($pagoEfectivo + $pagoTransferencia));
            $status = $saldoPendiente > 0 ? 'CREDITO' : 'COMPLETADO';

            $this->db->query("INSERT INTO table_facturas 
                              (cliente_id, placa, modelo_vehiculo, subtotal, iva_monto, total, 
                               pago_efectivo, pago_transferencia, saldo_pendiente, usuario_id, status, origen, observaciones) 
                              VALUES 
                              (:cid, :placa, :modelo, :sub, :iva, :total, :pef, :ptra, :spend, :uid, :status, :origen, :obs)");

            $this->db->bind(':cid', $presupuesto->cliente_id);
            $this->db->bind(':placa', !empty($presupuesto->vehiculo_placa) ? mb_strtoupper($presupuesto->vehiculo_placa, 'UTF-8') : null);
            $this->db->bind(':modelo', mb_strtoupper(trim(($presupuesto->vehiculo_marca ?? '') . ' ' . ($presupuesto->vehiculo_modelo ?? '')), 'UTF-8'));
            $this->db->bind(':sub', $presupuesto->subtotal);
            $this->db->bind(':iva', $presupuesto->iva_monto);
            $this->db->bind(':total', $presupuesto->total);
            $this->db->bind(':pef', $pagoEfectivo);
            $this->db->bind(':ptra', $pagoTransferencia);
            $this->db->bind(':spend', $saldoPendiente);
            $this->db->bind(':uid', $usuarioId);
            $this->db->bind(':status', $status);
            $this->db->bind(':origen', 'PRESUPUESTO');
            $this->db->bind(':obs', mb_strtoupper("Venta generada desde Presupuesto #{$presupuesto->numero}. " . ($presupuesto->observaciones ?? ''), 'UTF-8'));
            $this->db->execute();

            $ventaId = $this->db->lastInsertId();

            foreach ($items as $item) {
                $this->db->query("INSERT INTO table_facturas_detalle 
                                  (factura_id, producto_id, descripcion, cantidad, precio_unitario, costo_unitario) 
                                  VALUES 
                                  (:fid, :pid, :desc, :cant, :pre, :costo)");

                $this->db->bind(':fid', $ventaId);
                $this->db->bind(':pid', $item->tipo_item === 'PRODUCTO' ? $item->producto_id : null);
                $this->db->bind(':desc', mb_strtoupper($item->descripcion ?? 'ITEM', 'UTF-8'));
                $this->db->bind(':cant', $item->cantidad);
                $this->db->bind(':pre', $item->precio_unitario);
                $this->db->bind(':costo', $item->tipo_item === 'PRODUCTO' ? ($item->costo_promedio ?? 0) : 0);
                $this->db->execute();

                if ($item->tipo_item === 'PRODUCTO' && $item->producto_id) {
                    $this->db->query("UPDATE table_inventario SET stock = stock - :cant WHERE id = :pid");
                    $this->db->bind(':cant', $item->cantidad);
                    $this->db->bind(':pid', $item->producto_id);
                    $this->db->execute();

                    $invModel = new ModelInventario($this->db);
                    $invModel->registrarMovimiento(
                        $item->producto_id, 
                        'VENTA_PRESUPUESTO', 
                        $item->cantidad, 
                        $ventaId, 
                        "VENTA GENERADA DESDE PRESUPUESTO #{$presupuesto->numero} (FACTURA #{$ventaId})"
                    );
                }
            }

            $this->db->query("UPDATE table_presupuestos_reservas 
                              SET estado = 'FACTURADA', 
                                  cantidad_liberada = cantidad_reservada,
                                  fecha_liberacion = NOW(),
                                  usuario_liberacion_id = :uid
                              WHERE presupuesto_id = :pid AND estado = 'RESERVADA'");
            $this->db->bind(':pid', (int)$id);
            $this->db->bind(':uid', $usuarioId);
            $this->db->execute();

            $this->db->query("UPDATE table_presupuestos SET estado = 'CONVERTIDO' WHERE id = :id");
            $this->db->bind(':id', (int)$id);
            $this->db->execute();

            $this->db->query("INSERT INTO table_audit_logs (usuario_id, modulo, accion, descripcion, ip_address, fecha) 
                              VALUES (:uid, 'PRESUPUESTO', 'CONVERTIR_A_VENTA', :desc, :ip, NOW())");
            $this->db->bind(':uid', $usuarioId);
            $this->db->bind(':desc', mb_strtoupper("Presupuesto #{$id} convertido a Venta #{$ventaId}", 'UTF-8'));
            $this->db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            $this->db->execute();

            $this->db->commit();
            return ['venta_id' => $ventaId, 'status' => $status];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function obtenerReservas($id) {
        $this->db->query("SELECT pr.*, i.nombre as producto_nombre, i.codigo as producto_codigo, i.stock as stock_actual
                          FROM table_presupuestos_reservas pr
                          LEFT JOIN table_inventario i ON pr.producto_id = i.id
                          WHERE pr.presupuesto_id = :id
                          ORDER BY pr.fecha_reserva");
        $this->db->bind(':id', (int)$id);
        return $this->db->resultSet();
    }

    public function puedeAnexar($id) {
        $this->db->query("SELECT estado FROM table_presupuestos WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        $presupuesto = $this->db->single();
        return $presupuesto && $presupuesto->estado === 'ACTIVO';
    }
}