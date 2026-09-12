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

    /**
     * Genera el siguiente número de presupuesto (PRES-YYYY-XXXX)
     */
    private function generarNumero() {
        $year = date('Y');
        $this->db->query("SELECT COUNT(*) as total FROM table_presupuestos WHERE numero LIKE :pattern");
        $this->db->bind(':pattern', "PRES-$year-%");
        $count = (int)$this->db->single()->total;
        $next = $count + 1;
        return sprintf("PRES-%s-%04d", $year, $next);
    }

    /**
     * Lista presupuestos con paginación y filtros
     */
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

        // Contar total
        $this->db->query("SELECT COUNT(*) as total FROM table_presupuestos p $where");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        $total = (int)$this->db->single()->total;

        // Obtener datos
        $this->db->query("SELECT p.*, u.username as usuario_nombre, u.email as usuario_email
                          FROM table_presupuestos p
                          LEFT JOIN table_usuarios u ON p.usuario_id = u.id
                          $where
                          ORDER BY p.fecha_creacion DESC
                          LIMIT :limit OFFSET :offset");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        $this->db->bind(':limit', (int)$limit);
        $this->db->bind(':offset', (int)$offset);

        return ['data' => $this->db->resultSet(), 'total' => $total];
    }

    /**
     * Obtiene un presupuesto completo con sus items
     */
    public function obtenerCompleto($id) {
        $this->db->query("SELECT p.*, u.username as usuario_nombre, u.email as usuario_email
                          FROM table_presupuestos p
                          LEFT JOIN table_usuarios u ON p.usuario_id = u.id
                          WHERE p.id = :id");
        $this->db->bind(':id', (int)$id);
        $presupuesto = $this->db->single();

        if (!$presupuesto) return null;

        // Obtener items
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
     * Crea un nuevo presupuesto
     */
    public function crear($data) {
        try {
            $this->db->beginTransaction();

            // Generar número
            $numero = $this->generarNumero();

            // Calcular fecha de vencimiento
            $fechaEmision = $data['fecha_emision'] ?? date('Y-m-d');
            $validezDias = $data['validez_dias'] ?? 30;
            $fechaVencimiento = date('Y-m-d', strtotime("$fechaEmision + $validezDias days"));

            // Insertar cabecera
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

            $this->db->bind(':numero', $numero);
            $this->db->bind(':cliente_id', $data['cliente_id'] ?? null);
            $this->db->bind(':cliente_nombre', $data['cliente_nombre']);
            $this->db->bind(':cliente_cedula', $data['cliente_cedula'] ?? null);
            $this->db->bind(':cliente_telefono', $data['cliente_telefono'] ?? null);
            $this->db->bind(':cliente_email', $data['cliente_email'] ?? null);
            $this->db->bind(':cliente_direccion', $data['cliente_direccion'] ?? null);
            $this->db->bind(':vehiculo_placa', $data['vehiculo_placa'] ?? null);
            $this->db->bind(':vehiculo_marca', $data['vehiculo_marca'] ?? null);
            $this->db->bind(':vehiculo_modelo', $data['vehiculo_modelo'] ?? null);
            $this->db->bind(':vehiculo_anio', $data['vehiculo_anio'] ?? null);
            $this->db->bind(':vehiculo_color', $data['vehiculo_color'] ?? null);
            $this->db->bind(':subtotal', $data['subtotal'] ?? 0);
            $this->db->bind(':iva_monto', $data['iva_monto'] ?? 0);
            $this->db->bind(':total', $data['total'] ?? 0);
            $this->db->bind(':iva_activo', $data['iva_activo'] ?? 1);
            $this->db->bind(':tasa_iva', $data['tasa_iva'] ?? 19.00);
            $this->db->bind(':estado', $data['estado'] ?? 'BORRADOR');
            $this->db->bind(':validez_dias', $data['validez_dias'] ?? 30);
            $this->db->bind(':fecha_emision', $data['fecha_emision'] ?? date('Y-m-d'));
            $this->db->bind(':fecha_vencimiento', $data['fecha_vencimiento'] ?? date('Y-m-d', strtotime("+30 days")));
            $this->db->bind(':observaciones', $data['observaciones'] ?? null);
            $this->db->bind(':condiciones', $data['condiciones'] ?? null);
            $this->db->bind(':usuario_id', $data['usuario_id']);

            if (!$this->db->execute()) {
                throw new Exception("Error al crear la cabecera del presupuesto");
            }

            $presupuestoId = $this->db->lastInsertId();

            // Insertar items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    $this->db->query("INSERT INTO table_presupuestos_detalle 
                                      (presupuesto_id, producto_id, tipo_item, descripcion, cantidad, 
                                       precio_unitario, descuento_porcentaje, descuento_monto, subtotal,
                                       iva_porcentaje, iva_monto, total, orden_visual, notas)
                                      VALUES 
                                      (:pid, :producto_id, :tipo_item, :descripcion, :cantidad,
                                       :precio_unitario, :descuento_porcentaje, :descuento_monto, :subtotal,
                                       :iva_porcentaje, :iva_monto, :total, :orden_visual, :notas)");

                    $this->db->bind(':pid', $presupuestoId);
                    $this->db->bind(':producto_id', $item['producto_id'] ?? null);
                    $this->db->bind(':tipo_item', $item['tipo_item'] ?? 'PRODUCTO');
                    $this->db->bind(':descripcion', $item['descripcion']);
                    $this->db->bind(':cantidad', $item['cantidad'] ?? 1);
                    $this->db->bind(':precio_unitario', $item['precio_unitario'] ?? 0);
                    $this->db->bind(':descuento_porcentaje', $item['descuento_porcentaje'] ?? 0);
                    $this->db->bind(':descuento_monto', $item['descuento_monto'] ?? 0);
                    $this->db->bind(':subtotal', $item['subtotal'] ?? 0);
                    $this->db->bind(':iva_porcentaje', $item['iva_porcentaje'] ?? 0);
                    $this->db->bind(':iva_monto', $item['iva_monto'] ?? 0);
                    $this->db->bind(':total', $item['total'] ?? 0);
                    $this->db->bind(':orden_visual', $item['orden_visual'] ?? $index);
                    $this->db->bind(':notas', $item['notas'] ?? null);

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

    /**
     * Actualiza un presupuesto existente
     */
    public function actualizar($id, $data) {
        try {
            $this->db->beginTransaction();

            // Actualizar cabecera
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
            $this->db->bind(':cliente_nombre', $data['cliente_nombre']);
            $this->db->bind(':cliente_cedula', $data['cliente_cedula'] ?? null);
            $this->db->bind(':cliente_telefono', $data['cliente_telefono'] ?? null);
            $this->db->bind(':cliente_email', $data['cliente_email'] ?? null);
            $this->db->bind(':cliente_direccion', $data['cliente_direccion'] ?? null);
            $this->db->bind(':vehiculo_placa', $data['vehiculo_placa'] ?? null);
            $this->db->bind(':vehiculo_marca', $data['vehiculo_marca'] ?? null);
            $this->db->bind(':vehiculo_modelo', $data['vehiculo_modelo'] ?? null);
            $this->db->bind(':vehiculo_anio', $data['vehiculo_anio'] ?? null);
            $this->db->bind(':vehiculo_color', $data['vehiculo_color'] ?? null);
            $this->db->bind(':subtotal', $data['subtotal'] ?? 0);
            $this->db->bind(':iva_monto', $data['iva_monto'] ?? 0);
            $this->db->bind(':total', $data['total'] ?? 0);
            $this->db->bind(':iva_activo', $data['iva_activo'] ?? 1);
            $this->db->bind(':tasa_iva', $data['tasa_iva'] ?? 19.00);
            $this->db->bind(':estado', $data['estado'] ?? 'BORRADOR');
            $this->db->bind(':validez_dias', $data['validez_dias'] ?? 30);
            $this->db->bind(':fecha_emision', $data['fecha_emision'] ?? date('Y-m-d'));
            $this->db->bind(':fecha_vencimiento', $data['fecha_vencimiento'] ?? null);
            $this->db->bind(':observaciones', $data['observaciones'] ?? null);
            $this->db->bind(':condiciones', $data['condiciones'] ?? null);
            $this->db->bind(':id', (int)$id);

            if (!$this->db->execute()) {
                throw new Exception("Error al actualizar la cabecera del presupuesto");
            }

            // Eliminar items existentes y volver a insertar
            $this->db->query("DELETE FROM table_presupuestos_detalle WHERE presupuesto_id = :id");
            $this->db->bind(':id', (int)$id);
            $this->db->execute();

            // Insertar nuevos items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    $this->db->query("INSERT INTO table_presupuestos_detalle 
                                      (presupuesto_id, producto_id, tipo_item, descripcion, cantidad, 
                                       precio_unitario, descuento_porcentaje, descuento_monto, subtotal,
                                       iva_porcentaje, iva_monto, total, orden_visual, notas)
                                      VALUES 
                                      (:pid, :producto_id, :tipo_item, :descripcion, :cantidad,
                                       :precio_unitario, :descuento_porcentaje, :descuento_monto, :subtotal,
                                       :iva_porcentaje, :iva_monto, :total, :orden_visual, :notas)");

                    $this->db->bind(':pid', (int)$id);
                    $this->db->bind(':producto_id', $item['producto_id'] ?? null);
                    $this->db->bind(':tipo_item', $item['tipo_item'] ?? 'PRODUCTO');
                    $this->db->bind(':descripcion', $item['descripcion']);
                    $this->db->bind(':cantidad', $item['cantidad'] ?? 1);
                    $this->db->bind(':precio_unitario', $item['precio_unitario'] ?? 0);
                    $this->db->bind(':descuento_porcentaje', $item['descuento_porcentaje'] ?? 0);
                    $this->db->bind(':descuento_monto', $item['descuento_monto'] ?? 0);
                    $this->db->bind(':subtotal', $item['subtotal'] ?? 0);
                    $this->db->bind(':iva_porcentaje', $item['iva_porcentaje'] ?? 0);
                    $this->db->bind(':iva_monto', $item['iva_monto'] ?? 0);
                    $this->db->bind(':total', $item['total'] ?? 0);
                    $this->db->bind(':orden_visual', $item['orden_visual'] ?? $index);
                    $this->db->bind(':notas', $item['notas'] ?? null);

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

    /**
     * Cambia el estado de un presupuesto
     */
    public function cambiarEstado($id, $estado) {
        $estadosValidos = ['BORRADOR', 'ENVIADO', 'ACEPTADO', 'RECHAZADO', 'EXPIRADO', 'CONVERTIDO'];
        if (!in_array($estado, $estadosValidos)) {
            throw new Exception("Estado no válido");
        }

        $this->db->query("UPDATE table_presupuestos SET estado = :estado WHERE id = :id");
        $this->db->bind(':estado', $estado);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    /**
     * Elimina un presupuesto (solo si está en BORRADOR)
     */
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

    /**
     * Obtiene estadísticas de presupuestos
     */
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
                            SUM(CASE WHEN estado = 'ACEPTADO' THEN 1 ELSE 0 END) as aceptados,
                            SUM(CASE WHEN estado = 'RECHAZADO' THEN 1 ELSE 0 END) as rechazados,
                            SUM(CASE WHEN estado = 'EXPIRADO' THEN 1 ELSE 0 END) as expirados,
                            SUM(CASE WHEN estado = 'CONVERTIDO' THEN 1 ELSE 0 END) as convertidos,
                            COALESCE(SUM(CASE WHEN estado IN ('ACEPTADO','CONVERTIDO') THEN total ELSE 0 END), 0) as monto_aceptado
                          FROM table_presupuestos $where");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->single();
    }

    /**
     * Obtiene productos del inventario para el selector
     */
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

    /**
     * Obtiene clientes para el selector
     */
    public function obtenerClientes($search = null) {
        $sql = "SELECT id, nombre, cedula, telefono, email, direccion 
                FROM table_clientes 
                WHERE 1=1";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (nombre LIKE :search OR id LIKE :search OR telefono LIKE :search OR email LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $sql .= " ORDER BY nombre ASC LIMIT 50";
        
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->resultSet();
    }
}