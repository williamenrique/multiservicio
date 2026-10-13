<?php
/**
 * Modelo de Presupuestos
 * Gestiona la creación, edición y seguimiento de presupuestos/cotizaciones.
 * 
 * FLUJO DE ESTADOS:
 *   BORRADOR → ENVIADO → ACEPTADO → ANEXADO → CONVERTIDO
 *                    ↘  RECHAZADO / EXPIRADO
 * 
 * REGLAS DE STOCK:
 *   ┌──────────────┬─────────────────┬────────────────────┬──────────────────┐
 *   │ Estado       │ Stock Físico    │ Reserva            │ Disponible       │
 *   ├──────────────┼─────────────────┼────────────────────┼──────────────────┤
 *   │ BORRADOR     │ No toca         │ No reserva         │ Normal           │
 *   │ ENVIADO      │ No toca         │ No reserva         │ Normal           │
 *   │ ACEPTADO     │ No toca         │ ✅ RESERVADA       │ Bloqueado        │
 *   │ ANEXADO      │ No toca         │ LIBERADA*          │ Bloqueado*       │
 *   │ CONVERTIDO   │ ✅ DESCUENTA    │ FACTURADA          │ Ya salió         │
 *   └──────────────┴─────────────────┴────────────────────┴──────────────────┘
 * 
 * v2.6 (2026-10-08):
 *   • Conflictos de Git merge resueltos.
 *   • Mantiene: marcarComoConvertido(), INSERT table_transacciones en
 *     convertirAVenta(), y todos los fixes anteriores.
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

    /**
     * Calcula la fecha de vencimiento a partir de fecha_emision + validez_dias.
     * Si ya viene una fecha_vencimiento explícita en $data, la respeta.
     */
    private function calcularFechaVencimiento($data) {
        if (!empty($data['fecha_vencimiento'])) {
            return $data['fecha_vencimiento'];
        }
        $fechaEmision = !empty($data['fecha_emision']) ? $data['fecha_emision'] : date('Y-m-d');
        $validezDias = (int)($data['validez_dias'] ?? 30);
        if ($validezDias <= 0) $validezDias = 30;
        return date('Y-m-d', strtotime("$fechaEmision + $validezDias days"));
    }

    /**
     * Normaliza un item para asegurar que todos los campos requeridos existan.
     */
    private function normalizarItem($item, $index) {
        $tipo = strtoupper($item['tipo_item'] ?? 'PRODUCTO');
        $productoId = ($tipo === 'PRODUCTO') ? ($item['producto_id'] ?? null) : null;
        if (empty($productoId)) $productoId = null;

        $descripcion = trim((string)($item['descripcion'] ?? $item['nombre'] ?? ''));
        if ($descripcion === '') {
            $descripcion = 'ITEM SIN DESCRIPCIÓN';
        }

        return [
            'producto_id'           => $productoId,
            'tipo_item'             => $tipo,
            'descripcion'           => $descripcion,
            'cantidad'              => max(1, (int)($item['cantidad'] ?? 1)),
            'precio_unitario'       => (float)($item['precio_unitario'] ?? 0),
            'descuento_porcentaje'  => (float)($item['descuento_porcentaje'] ?? 0),
            'descuento_monto'       => (float)($item['descuento_monto'] ?? 0),
            'subtotal'              => (float)($item['subtotal'] ?? 0),
            'iva_porcentaje'        => (float)($item['iva_porcentaje'] ?? 0),
            'iva_monto'             => (float)($item['iva_monto'] ?? 0),
            'total'                 => (float)($item['total'] ?? 0),
            'orden_visual'          => (int)($item['orden_visual'] ?? $index),
            'notas'                 => $item['notas'] ?? null,
        ];
    }

    /**
     * Calcula el stock_disponible real de un producto (para validaciones).
     */
    private function stockDisponibleProducto($productoId) {
        $this->db->query("SELECT 
                          (i.stock 
                           - COALESCE((
                                SELECT SUM(vd.cantidad) 
                                FROM table_facturas_detalle vd 
                                JOIN table_facturas v ON vd.factura_id = v.id 
                                WHERE vd.producto_id = i.id AND v.status = 'PENDIENTE'
                             ), 0)
                           - COALESCE((
                                SELECT SUM(pr.cantidad_reservada) 
                                FROM table_presupuestos_reservas pr
                                JOIN table_presupuestos p ON pr.presupuesto_id = p.id
                                WHERE pr.producto_id = i.id 
                                  AND pr.estado = 'RESERVADA' 
                                  AND p.estado IN ('ACEPTADO', 'ACTIVO')
                             ), 0)
                          ) as stock_disponible,
                          i.nombre
                          FROM table_inventario i WHERE i.id = :id");
        $this->db->bind(':id', $productoId);
        return $this->db->single();
    }

    /**
     * Crea reservas de stock (RESERVADA) SIN tocar el stock físico.
     */
    private function reservarStockPresupuesto($id) {
        $this->db->query("SELECT COUNT(*) as cnt FROM table_presupuestos_reservas 
                          WHERE presupuesto_id = :id AND estado IN ('RESERVADA', 'FACTURADA')");
        $this->db->bind(':id', (int)$id);
        if ((int)$this->db->single()->cnt > 0) {
            return true;
        }

        $this->db->query("SELECT pd.producto_id, pd.cantidad, i.nombre
                          FROM table_presupuestos_detalle pd
                          INNER JOIN table_inventario i ON pd.producto_id = i.id
                          WHERE pd.presupuesto_id = :id AND pd.tipo_item = 'PRODUCTO'");
        $this->db->bind(':id', (int)$id);
        $items = $this->db->resultSet();

        foreach ($items as $item) {
            $stockInfo = $this->stockDisponibleProducto($item->producto_id);
            if (!$stockInfo || $stockInfo->stock_disponible < $item->cantidad) {
                $disponible = $stockInfo ? $stockInfo->stock_disponible : 0;
                throw new Exception("Stock insuficiente para '{$item->nombre}'. Disponible: {$disponible}, Requerido: {$item->cantidad}");
            }
        }

        foreach ($items as $item) {
            $this->db->query("INSERT INTO table_presupuestos_reservas 
                              (presupuesto_id, producto_id, cantidad_reservada, estado) 
                              VALUES (:pid, :producto_id, :cant, 'RESERVADA')");
            $this->db->bind(':pid', (int)$id);
            $this->db->bind(':producto_id', $item->producto_id);
            $this->db->bind(':cant', $item->cantidad);
            $this->db->execute();
        }

        return true;
    }

    /**
     * Libera las reservas RESERVADA de un presupuesto (sin devolver stock físico).
     */
    private function liberarReservasSinStock($id) {
        $this->db->query("UPDATE table_presupuestos_reservas 
                          SET estado = 'LIBERADA',
                              cantidad_liberada = cantidad_reservada,
                              fecha_liberacion = NOW(),
                              usuario_liberacion_id = :uid
                          WHERE presupuesto_id = :pid AND estado = 'RESERVADA'");
        $this->db->bind(':pid', (int)$id);
        $this->db->bind(':uid', $_SESSION['user_id'] ?? null);
        $this->db->execute();
    }

    /**
     * Descuenta el stock físico real de los productos de un presupuesto.
     */
    private function descontarStockFisicoReal($id, $referencia = null) {
        $this->db->query("SELECT pd.producto_id, pd.cantidad, i.nombre, i.stock
                          FROM table_presupuestos_detalle pd
                          INNER JOIN table_inventario i ON pd.producto_id = i.id
                          WHERE pd.presupuesto_id = :id AND pd.tipo_item = 'PRODUCTO'");
        $this->db->bind(':id', (int)$id);
        $items = $this->db->resultSet();

        foreach ($items as $item) {
            $stockAnterior = (int)$item->stock;
            $stockActual = $stockAnterior - (int)$item->cantidad;

            $this->db->query("UPDATE table_inventario SET stock = stock - :cant WHERE id = :pid");
            $this->db->bind(':cant', $item->cantidad);
            $this->db->bind(':pid', $item->producto_id);
            $this->db->execute();

            $this->db->query("INSERT INTO table_kardex 
                              (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, referencia_id, usuario_id, observacion)
                              VALUES (:pid, 'SALIDA_VENTA', :cant, :ant, :act, :ref, :uid, :obs)");
            $this->db->bind(':pid', $item->producto_id);
            $this->db->bind(':cant', $item->cantidad);
            $this->db->bind(':ant', $stockAnterior);
            $this->db->bind(':act', $stockActual);
            $this->db->bind(':ref', $referencia ?? $id);
            $this->db->bind(':uid', $_SESSION['user_id'] ?? null);
            $this->db->bind(':obs', mb_strtoupper("PRESUPUESTO #$id - SALIDA DE STOCK", 'UTF-8'));
            $this->db->execute();
        }

        $this->db->query("UPDATE table_presupuestos_reservas 
                          SET estado = 'FACTURADA',
                              cantidad_liberada = cantidad_reservada,
                              fecha_liberacion = NOW(),
                              usuario_liberacion_id = :uid
                          WHERE presupuesto_id = :pid AND estado = 'RESERVADA'");
        $this->db->bind(':pid', (int)$id);
        $this->db->bind(':uid', $_SESSION['user_id'] ?? null);
        $this->db->execute();
    }

    /**
     * Devuelve stock físico de reservas FACTURADA.
     */
    private function devolverStockFisico($id) {
        $this->db->query("SELECT pr.id as reserva_id, pr.producto_id, pr.cantidad_reservada
                          FROM table_presupuestos_reservas pr
                          WHERE pr.presupuesto_id = :id AND pr.estado = 'FACTURADA'");
        $this->db->bind(':id', (int)$id);
        $reservas = $this->db->resultSet();

        foreach ($reservas as $r) {
            $this->db->query("UPDATE table_inventario SET stock = stock + :cant WHERE id = :pid");
            $this->db->bind(':cant', $r->cantidad_reservada);
            $this->db->bind(':pid', $r->producto_id);
            $this->db->execute();

            $this->db->query("SELECT stock FROM table_inventario WHERE id = :id");
            $this->db->bind(':id', $r->producto_id);
            $stockActual = (int)$this->db->single()->stock;

            $this->db->query("INSERT INTO table_kardex 
                              (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, referencia_id, usuario_id, observacion)
                              VALUES (:pid, 'DEVOLUCION', :cant, :ant, :act, :ref, :uid, :obs)");
            $this->db->bind(':pid', $r->producto_id);
            $this->db->bind(':cant', $r->cantidad_reservada);
            $this->db->bind(':ant', $stockActual - (int)$r->cantidad_reservada);
            $this->db->bind(':act', $stockActual);
            $this->db->bind(':ref', $id);
            $this->db->bind(':uid', $_SESSION['user_id'] ?? null);
            $this->db->bind(':obs', mb_strtoupper("LIBERACIÓN PRESUPUESTO #$id - REINGRESO DE STOCK", 'UTF-8'));
            $this->db->execute();

            $this->db->query("UPDATE table_presupuestos_reservas 
                              SET estado = 'LIBERADA', fecha_liberacion = NOW(), usuario_liberacion_id = :uid
                              WHERE id = :rid");
            $this->db->bind(':uid', $_SESSION['user_id'] ?? null);
            $this->db->bind(':rid', $r->reserva_id);
            $this->db->execute();
        }
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

        $this->db->query("SELECT pd.*, i.nombre as producto_nombre, i.codigo as producto_codigo, 
                                 i.imagen as producto_imagen, i.costo_promedio, i.stock as stock_actual
                          FROM table_presupuestos_detalle pd
                          LEFT JOIN table_inventario i ON pd.producto_id = i.id
                          WHERE pd.presupuesto_id = :id
                          ORDER BY pd.orden_visual, pd.id");
        $this->db->bind(':id', (int)$id);
        $presupuesto->items = $this->db->resultSet();

        return $presupuesto;
    }

    public function crear($data) {
        try {
            $this->db->beginTransaction();

            $numero = $this->generarNumero();
            $fechaEmision = $data['fecha_emision'] ?? date('Y-m-d');
            $validezDias = (int)($data['validez_dias'] ?? 30);
            if ($validezDias <= 0) $validezDias = 30;
            $fechaVencimiento = $this->calcularFechaVencimiento($data);

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
            $this->db->bind(':cliente_nombre', mb_strtoupper($data['cliente_nombre'] ?? '', 'UTF-8'));
            $this->db->bind(':cliente_cedula', !empty($data['cliente_cedula']) ? mb_strtoupper($data['cliente_cedula'], 'UTF-8') : null);
            $this->db->bind(':cliente_telefono', $data['cliente_telefono'] ?? null);
            $this->db->bind(':cliente_email', !empty($data['cliente_email']) ? mb_strtolower($data['cliente_email'], 'UTF-8') : null);
            $this->db->bind(':cliente_direccion', !empty($data['cliente_direccion']) ? mb_strtoupper($data['cliente_direccion'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_placa', !empty($data['vehiculo_placa']) ? mb_strtoupper(trim($data['vehiculo_placa']), 'UTF-8') : null);
            $this->db->bind(':vehiculo_marca', !empty($data['vehiculo_marca']) ? mb_strtoupper($data['vehiculo_marca'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_modelo', !empty($data['vehiculo_modelo']) ? mb_strtoupper($data['vehiculo_modelo'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_anio', !empty($data['vehiculo_anio']) ? (int)$data['vehiculo_anio'] : null);
            $this->db->bind(':vehiculo_color', !empty($data['vehiculo_color']) ? mb_strtoupper($data['vehiculo_color'], 'UTF-8') : null);
            $this->db->bind(':subtotal', $data['subtotal'] ?? 0);
            $this->db->bind(':iva_monto', $data['iva_monto'] ?? 0);
            $this->db->bind(':total', $data['total'] ?? 0);
            $this->db->bind(':iva_activo', $data['iva_activo'] ?? 1);
            $this->db->bind(':tasa_iva', $data['tasa_iva'] ?? 19.00);
            $this->db->bind(':estado', $data['estado'] ?? 'BORRADOR');
            $this->db->bind(':validez_dias', $validezDias);
            $this->db->bind(':fecha_emision', $fechaEmision);
            $this->db->bind(':fecha_vencimiento', $fechaVencimiento);
            $this->db->bind(':observaciones', !empty($data['observaciones']) ? mb_strtoupper($data['observaciones'], 'UTF-8') : null);
            $this->db->bind(':condiciones', !empty($data['condiciones']) ? mb_strtoupper($data['condiciones'], 'UTF-8') : null);
            $this->db->bind(':usuario_id', $data['usuario_id']);

            if (!$this->db->execute()) {
                throw new Exception("Error al crear la cabecera del presupuesto");
            }

            $presupuestoId = $this->db->lastInsertId();

            if (!empty($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    $it = $this->normalizarItem($item, $index);

                    $this->db->query("INSERT INTO table_presupuestos_detalle 
                                      (presupuesto_id, producto_id, tipo_item, descripcion, cantidad, 
                                       precio_unitario, descuento_porcentaje, descuento_monto, subtotal,
                                       iva_porcentaje, iva_monto, total, orden_visual, notas)
                                      VALUES 
                                      (:pid, :producto_id, :tipo_item, :descripcion, :cantidad,
                                       :precio_unitario, :descuento_porcentaje, :descuento_monto, :subtotal,
                                       :iva_porcentaje, :iva_monto, :total, :orden_visual, :notas)");

                    $this->db->bind(':pid', $presupuestoId);
                    $this->db->bind(':producto_id', $it['producto_id']);
                    $this->db->bind(':tipo_item', $it['tipo_item']);
                    $this->db->bind(':descripcion', mb_strtoupper($it['descripcion'], 'UTF-8'));
                    $this->db->bind(':cantidad', $it['cantidad']);
                    $this->db->bind(':precio_unitario', $it['precio_unitario']);
                    $this->db->bind(':descuento_porcentaje', $it['descuento_porcentaje']);
                    $this->db->bind(':descuento_monto', $it['descuento_monto']);
                    $this->db->bind(':subtotal', $it['subtotal']);
                    $this->db->bind(':iva_porcentaje', $it['iva_porcentaje']);
                    $this->db->bind(':iva_monto', $it['iva_monto']);
                    $this->db->bind(':total', $it['total']);
                    $this->db->bind(':orden_visual', $it['orden_visual']);
                    $this->db->bind(':notas', !empty($it['notas']) ? mb_strtoupper($it['notas'], 'UTF-8') : null);

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

            $fechaEmision = $data['fecha_emision'] ?? date('Y-m-d');
            $validezDias = (int)($data['validez_dias'] ?? 30);
            if ($validezDias <= 0) $validezDias = 30;
            $fechaVencimiento = $this->calcularFechaVencimiento($data);

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
            $this->db->bind(':vehiculo_placa', !empty($data['vehiculo_placa']) ? mb_strtoupper(trim($data['vehiculo_placa']), 'UTF-8') : null);
            $this->db->bind(':vehiculo_marca', !empty($data['vehiculo_marca']) ? mb_strtoupper($data['vehiculo_marca'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_modelo', !empty($data['vehiculo_modelo']) ? mb_strtoupper($data['vehiculo_modelo'], 'UTF-8') : null);
            $this->db->bind(':vehiculo_anio', !empty($data['vehiculo_anio']) ? (int)$data['vehiculo_anio'] : null);
            $this->db->bind(':vehiculo_color', !empty($data['vehiculo_color']) ? mb_strtoupper($data['vehiculo_color'], 'UTF-8') : null);
            $this->db->bind(':subtotal', $data['subtotal'] ?? 0);
            $this->db->bind(':iva_monto', $data['iva_monto'] ?? 0);
            $this->db->bind(':total', $data['total'] ?? 0);
            $this->db->bind(':iva_activo', $data['iva_activo'] ?? 1);
            $this->db->bind(':tasa_iva', $data['tasa_iva'] ?? 19.00);
            $this->db->bind(':estado', $data['estado'] ?? 'BORRADOR');
            $this->db->bind(':validez_dias', $validezDias);
            $this->db->bind(':fecha_emision', $fechaEmision);
            $this->db->bind(':fecha_vencimiento', $fechaVencimiento);
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
                    $it = $this->normalizarItem($item, $index);

                    $this->db->query("INSERT INTO table_presupuestos_detalle 
                                      (presupuesto_id, producto_id, tipo_item, descripcion, cantidad, 
                                       precio_unitario, descuento_porcentaje, descuento_monto, subtotal,
                                       iva_porcentaje, iva_monto, total, orden_visual, notas)
                                      VALUES 
                                      (:pid, :producto_id, :tipo_item, :descripcion, :cantidad,
                                       :precio_unitario, :descuento_porcentaje, :descuento_monto, :subtotal,
                                       :iva_porcentaje, :iva_monto, :total, :orden_visual, :notas)");

                    $this->db->bind(':pid', (int)$id);
                    $this->db->bind(':producto_id', $it['producto_id']);
                    $this->db->bind(':tipo_item', $it['tipo_item']);
                    $this->db->bind(':descripcion', mb_strtoupper($it['descripcion'], 'UTF-8'));
                    $this->db->bind(':cantidad', $it['cantidad']);
                    $this->db->bind(':precio_unitario', $it['precio_unitario']);
                    $this->db->bind(':descuento_porcentaje', $it['descuento_porcentaje']);
                    $this->db->bind(':descuento_monto', $it['descuento_monto']);
                    $this->db->bind(':subtotal', $it['subtotal']);
                    $this->db->bind(':iva_porcentaje', $it['iva_porcentaje']);
                    $this->db->bind(':iva_monto', $it['iva_monto']);
                    $this->db->bind(':total', $it['total']);
                    $this->db->bind(':orden_visual', $it['orden_visual']);
                    $this->db->bind(':notas', !empty($it['notas']) ? mb_strtoupper($it['notas'], 'UTF-8') : null);

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
        $estadosValidos = ['BORRADOR', 'ENVIADO', 'ACTIVO', 'EN_PROCESO', 'ANEXADO', 'ACEPTADO', 'RECHAZADO', 'EXPIRADO', 'CONVERTIDO'];
        if (!in_array($estado, $estadosValidos)) {
            throw new Exception("Estado no válido: $estado");
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
                            SUM(CASE WHEN estado IN ('ANEXADO', 'EN_PROCESO') THEN 1 ELSE 0 END) as en_proceso,
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

            $this->reservarStockPresupuesto($id);

            $this->db->query("UPDATE table_presupuestos 
                              SET estado = 'ACTIVO', 
                                  fecha_activacion = NOW(), 
                                  usuario_activacion_id = :uid 
                              WHERE id = :id AND estado IN ('BORRADOR', 'ENVIADO')");
            $this->db->bind(':id', (int)$id);
            $this->db->bind(':uid', (int)$usuarioId);
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

            $this->devolverStockFisico($id);

            $this->db->query("UPDATE table_presupuestos_reservas 
                              SET estado = 'LIBERADA',
                                  cantidad_liberada = cantidad_reservada,
                                  fecha_liberacion = NOW(),
                                  usuario_liberacion_id = :uid
                              WHERE presupuesto_id = :id AND estado = 'RESERVADA'");
            $this->db->bind(':uid', (int)$usuarioId);
            $this->db->bind(':id', (int)$id);
            $this->db->execute();

            $this->db->query("UPDATE table_presupuestos SET estado = 'BORRADOR' 
                              WHERE id = :id 
                              AND estado IN ('ACTIVO', 'ANEXADO', 'ACEPTADO', 'EN_PROCESO')");
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
                WHERE p.estado NOT IN ('CONVERTIDO', 'RECHAZADO', 'EXPIRADO', 'ANEXADO')";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (p.numero LIKE :search OR p.cliente_nombre LIKE :search OR p.cliente_cedula LIKE :search OR p.vehiculo_placa LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $sql .= " ORDER BY p.fecha_emision DESC, p.id DESC LIMIT 50";
        
        $this->db->query($sql);
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->resultSet();
    }

    public function obtenerParaAnexar($id) {
        $presupuesto = $this->obtenerCompleto($id);
        
        if (!$presupuesto) {
            return null;
        }

        if (in_array($presupuesto->estado, ['CONVERTIDO', 'RECHAZADO', 'EXPIRADO', 'ANEXADO'])) {
            return null;
        }

        $itemsNormalizados = [];
        foreach ($presupuesto->items as $item) {
            $itemsNormalizados[] = [
                'id'             => $item->producto_id,
                'nombre'         => $item->descripcion,
                'descripcion'    => $item->descripcion,
                'precio'         => (float)$item->precio_unitario,
                'precio_unitario'=> (float)$item->precio_unitario,
                'cantidad'       => (int)$item->cantidad,
                'tipo'           => $item->tipo_item === 'SERVICIO' ? 'SERVICIO' : 'PRODUCTO',
                'tipo_item'      => $item->tipo_item,
                'producto_id'    => $item->producto_id,
                'costo_promedio' => (float)($item->costo_promedio ?? 0),
                'notas'          => $item->notas,
            ];
        }

        $presupuesto->items_normalizados = $itemsNormalizados;

        return $presupuesto;
    }

    public function obtenerActivoCompleto($id) {
        return $this->obtenerParaAnexar($id);
    }

    public function iniciarProceso($id, $crearReservas = true) {
        try {
            $this->db->beginTransaction();

            $this->db->query("SELECT estado FROM table_presupuestos WHERE id = :id");
            $this->db->bind(':id', (int)$id);
            $presupuesto = $this->db->single();

            if (!$presupuesto) {
                throw new Exception("Presupuesto no encontrado");
            }

            if (in_array($presupuesto->estado, ['CONVERTIDO', 'RECHAZADO', 'EXPIRADO'])) {
                throw new Exception("El presupuesto está {$presupuesto->estado} y no puede anexarse");
            }

            if ($presupuesto->estado === 'ANEXADO') {
                $this->db->commit();
                return true;
            }

            $this->liberarReservasSinStock($id);

            $this->db->query("UPDATE table_presupuestos SET estado = 'ANEXADO' WHERE id = :id");
            $this->db->bind(':id', (int)$id);
            $this->db->execute();

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function puedeAnexar($id) {
        $this->db->query("SELECT estado FROM table_presupuestos WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        $presupuesto = $this->db->single();
        if (!$presupuesto) return false;
        return !in_array($presupuesto->estado, ['CONVERTIDO', 'RECHAZADO', 'EXPIRADO', 'ANEXADO']);
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

            if (in_array($presupuesto->estado, ['CONVERTIDO', 'RECHAZADO', 'EXPIRADO'])) {
                throw new Exception("El presupuesto está {$presupuesto->estado} y no puede aceptarse");
            }

            if ($presupuesto->estado === 'ACEPTADO') {
                $this->db->commit();
                return true;
            }

            $this->reservarStockPresupuesto($id);

            $this->db->query("UPDATE table_presupuestos 
                              SET estado = 'ACEPTADO', 
                                  fecha_activacion = NOW(), 
                                  usuario_activacion_id = :uid 
                              WHERE id = :id");
            $this->db->bind(':id', (int)$id);
            $this->db->bind(':uid', (int)$usuarioId);
            $this->db->execute();

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Convierte un presupuesto a venta (crea la factura).
     * 
     * ⚠️ SIEMPRE descuenta stock físico + kardex.
     * Marca las reservas RESERVADA (si las hay) como FACTURADA.
     * 
     * v2.5: También inserta el registro en table_transacciones para que
     * las ventas por presupuesto aparezcan en el libro mayor.
     */
    public function convertirAVenta($id, $usuarioId, $datosPago = []) {
        try {
            $this->db->beginTransaction();

            $presupuesto = $this->obtenerCompleto((int)$id);
            if (!$presupuesto) {
                throw new Exception("Presupuesto no encontrado");
            }

            $estadosValidos = ['BORRADOR', 'ENVIADO', 'ACTIVO', 'ANEXADO', 'ACEPTADO', 'EN_PROCESO'];
            if (!in_array($presupuesto->estado, $estadosValidos)) {
                throw new Exception("El presupuesto no puede convertirse desde el estado {$presupuesto->estado}");
            }

            foreach ($presupuesto->items as $item) {
                if ($item->tipo_item === 'PRODUCTO' && $item->producto_id) {
                    if ((int)$item->stock_actual < (int)$item->cantidad) {
                        throw new Exception("Stock físico insuficiente para '{$item->producto_nombre}'. Disponible: {$item->stock_actual}, Requerido: {$item->cantidad}");
                    }
                }
            }

            $this->descontarStockFisicoReal($id, $id);

            $pagoEfectivo = (float)($datosPago['pago_efectivo'] ?? $presupuesto->total);
            $pagoTransferencia = (float)($datosPago['pago_transferencia'] ?? 0);
            $saldoPendiente = max(0, $presupuesto->total - ($pagoEfectivo + $pagoTransferencia));
            $status = $saldoPendiente > 0.01 ? 'CREDITO' : 'COMPLETADO';

            // 1. Crear la factura
            $this->db->query("INSERT INTO table_facturas 
                              (cliente_id, placa, modelo_vehiculo, subtotal, iva_monto, total, 
                               pago_efectivo, pago_transferencia, saldo_pendiente, usuario_id, status, origen, observaciones) 
                              VALUES 
                              (:cid, :placa, :modelo, :sub, :iva, :total, :pef, :ptra, :spend, :uid, :status, :origen, :obs)");

            $this->db->bind(':cid', $presupuesto->cliente_id);
            $this->db->bind(':placa', $presupuesto->vehiculo_placa);
            $this->db->bind(':modelo', trim(($presupuesto->vehiculo_marca ?? '') . ' ' . ($presupuesto->vehiculo_modelo ?? '')));
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

            // 2. Detalle de la factura
            foreach ($presupuesto->items as $item) {
                $this->db->query("INSERT INTO table_facturas_detalle 
                                  (factura_id, producto_id, descripcion, cantidad, precio_unitario, costo_unitario) 
                                  VALUES 
                                  (:fid, :pid, :desc, :cant, :pre, :costo)");

                $this->db->bind(':fid', $ventaId);
                $this->db->bind(':pid', $item->tipo_item === 'PRODUCTO' ? $item->producto_id : null);
                $this->db->bind(':desc', mb_strtoupper($item->descripcion, 'UTF-8'));
                $this->db->bind(':cant', $item->cantidad);
                $this->db->bind(':pre', $item->precio_unitario);
                $this->db->bind(':costo', $item->tipo_item === 'PRODUCTO' ? ($item->costo_promedio ?? 0) : 0);
                $this->db->execute();
            }

            // 3. Registrar ingreso en el libro mayor
            $totalPagadoHoy = $pagoEfectivo + $pagoTransferencia;
            if ($totalPagadoHoy > 0.005) {
                $this->db->query("INSERT INTO table_transacciones 
                                  (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id) 
                                  VALUES (1, 'INGRESO', 'VENTA', :monto, :ref, :desc, :uid)");
                $this->db->bind(':monto', $totalPagadoHoy);
                $this->db->bind(':ref', $ventaId);
                $this->db->bind(':desc', "VENTA POR PRESUPUESTO #{$presupuesto->numero} (EFE: $pagoEfectivo, TRA: $pagoTransferencia)");
                $this->db->bind(':uid', $usuarioId);
                $this->db->execute();
            }

            // 4. Cambiar estado del presupuesto a CONVERTIDO
            $this->db->query("UPDATE table_presupuestos SET estado = 'CONVERTIDO' WHERE id = :id");
            $this->db->bind(':id', (int)$id);
            $this->db->execute();

            $this->db->commit();
            return ['venta_id' => $ventaId, 'status' => $status];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function marcarComoConvertido($id, $ventaId = null, $usuarioId = null) {
        try {
            $this->db->beginTransaction();

            $this->db->query("UPDATE table_presupuestos_reservas 
                              SET estado = 'FACTURADA',
                                  cantidad_liberada = cantidad_reservada,
                                  fecha_liberacion = NOW(),
                                  usuario_liberacion_id = :uid
                              WHERE presupuesto_id = :pid 
                                AND estado IN ('RESERVADA', 'FACTURADA')");
            $this->db->bind(':pid', (int)$id);
            $this->db->bind(':uid', $usuarioId ?? ($_SESSION['user_id'] ?? null));
            $this->db->execute();

            $this->db->query("UPDATE table_presupuestos 
                              SET estado = 'CONVERTIDO' 
                              WHERE id = :id 
                                AND estado IN ('ANEXADO', 'ACTIVO', 'ACEPTADO', 'EN_PROCESO', 'ENVIADO')");
            $this->db->bind(':id', (int)$id);
            $this->db->execute();

            $this->db->commit();
            return true;
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
}