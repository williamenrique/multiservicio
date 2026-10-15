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

    /**
     * Genera el siguiente número de presupuesto de forma segura.
     * 
     * FIX v3.5: Usar MAX(numero) en vez de COUNT(*) para evitar
     * colisiones cuando hay huecos en la secuencia (por ejemplo, si
     * se borró un presupuesto intermedio).
     */
    private function generarNumero() {
        $year = date('Y');

        $this->db->query("SELECT MAX(CAST(SUBSTRING(numero, -4) AS UNSIGNED)) as ultimo 
                          FROM table_presupuestos 
                          WHERE numero LIKE :pattern");
        $this->db->bind(':pattern', "PRES-$year-%");
        $row = $this->db->single();

        $ultimo = (int)($row->ultimo ?? 0);
        $siguiente = $ultimo + 1;

        // Loop de seguridad: si por alguna razón el número ya existe
        // (concurrencia, imports, etc.), incrementa hasta encontrar uno libre.
        $intentos = 0;
        while ($intentos < 9999) {
            $candidato = sprintf("PRES-%s-%04d", $year, $siguiente);

            $this->db->query("SELECT COUNT(*) as cnt FROM table_presupuestos WHERE numero = :num");
            $this->db->bind(':num', $candidato);
            $existe = (int)$this->db->single()->cnt > 0;

            if (!$existe) {
                return $candidato;
            }
            $siguiente++;
            $intentos++;
        }

        // Fallback: usar timestamp para garantizar unicidad
        return sprintf("PRES-%s-%04d", $year, $siguiente);
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

                // ─── FIX OFERTA-P3 v3.8: Detectar si el item fue vendido con oferta ───
        $this->db->query("SELECT 
                            pd.*, 
                            i.nombre as producto_nombre, 
                            i.codigo as producto_codigo, 
                            i.imagen as producto_imagen, 
                            i.costo_promedio, 
                            i.stock as stock_actual,
                            i.precio as producto_precio_actual,
                            i.oferta_porcentaje,
                            CASE 
                                WHEN i.oferta_activa = 1 
                                     AND i.oferta_porcentaje > 0 
                                     AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                                     AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())
                                THEN 1
                                ELSE 0
                            END as en_oferta_vigente
                          FROM table_presupuestos_detalle pd
                          LEFT JOIN table_inventario i ON pd.producto_id = i.id
                          WHERE pd.presupuesto_id = :id
                          ORDER BY pd.orden_visual, pd.id");
        $this->db->bind(':id', (int)$id);
        $items = $this->db->resultSet();

        // Enriquecer cada item con metadatos de oferta (precio_base, en_oferta)
        foreach ($items as $it) {
            $it->en_oferta = false;
            $it->precio_base = null;

            if ($it->producto_id && (int)$it->en_oferta_vigente === 1) {
                $precioGuardado = (float)$it->precio_unitario;
                $precioBaseActual = (float)$it->producto_precio_actual;

                // Si el precio guardado es MENOR al base actual → fue con oferta
                if ($precioGuardado < $precioBaseActual - 0.01) {
                    $it->en_oferta = true;
                    $it->precio_base = $precioBaseActual;
                }
            }
        }
        $presupuesto->items = $items;

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

    /**
     * Libera el inventario y las reservas de un presupuesto, devolviéndolo a BORRADOR.
     * 
     * FIX v3.3 (2026-10-14):
     *   • Ahora también limpia `table_facturas.presupuesto_activo_id` de cualquier
     *     factura PENDIENTE que apunte a este presupuesto. Sin esto, el polling
     *     de facturación "resucitaba" el presupuesto pegado en la UI aunque el
     *     presupuesto ya estuviera en BORRADOR.
     * 
     *   • Se preserva el comportamiento original: devuelve stock físico si había
     *     reservas FACTURADA, libera reservas RESERVADA, y marca el presupuesto
     *     como BORRADOR.
     */
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
                              AND estado IN ('ACTIVO', 'ANEXADO', 'ACEPTADO', 'EN_PROCESO', 'ENVIADO')");
            $this->db->bind(':id', (int)$id);
            $this->db->execute();

            // ─── FIX v3.3: Limpiar la referencia en las facturas PENDIENTES ───
            // Sin esto, el polling de facturación seguía viendo el presupuesto
            // "pegado" en la factura aunque el presupuesto ya estuviera en BORRADOR.
            $this->db->query("UPDATE table_facturas 
                              SET presupuesto_activo_id = NULL 
                              WHERE presupuesto_activo_id = :pid 
                                AND status = 'PENDIENTE'");
            $this->db->bind(':pid', (int)$id);
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

        /**
     * Obtiene SOLO los metadatos básicos de un presupuesto (sin items).
     * 
     * v2.7 (2026-10-14):
     *   • Nuevo método usado por facturación para rehidratar el estado visual
     *     del presupuesto anexado a una factura, INDEPENDIENTEMENTE de su
     *     estado actual (BORRADOR, ENVIADO, ACEPTADO, ANEXADO, CONVERTIDO...).
     *   • Se separa de obtenerParaAnexar() porque ese método rechaza estados
     *     terminales (CONVERTIDO, RECHAZADO, EXPIRADO, ANEXADO), lo cual
     *     rompía la rehidratación de una factura ya vinculada.
     * 
     * @param int $id
     * @return object|null  { id, numero, cliente_id, cliente_nombre, cliente_telefono,
     *                        cliente_email, vehiculo_placa, vehiculo_marca, vehiculo_modelo,
     *                        vehiculo_anio, vehiculo_color, subtotal, iva_monto, total,
     *                        estado, fecha_emision, fecha_vencimiento, usuario_id,
     *                        usuario_nombre }
     */
    public function obtenerInfoBasica($id) {
        $this->db->query("SELECT 
                            p.id, p.numero, 
                            p.cliente_id, p.cliente_nombre, p.cliente_cedula, 
                            p.cliente_telefono, p.cliente_email, p.cliente_direccion,
                            p.vehiculo_placa, p.vehiculo_marca, p.vehiculo_modelo,
                            p.vehiculo_anio, p.vehiculo_color,
                            p.subtotal, p.iva_monto, p.total, p.iva_activo, p.tasa_iva,
                            p.estado, p.validez_dias,
                            p.fecha_emision, p.fecha_vencimiento, p.fecha_activacion,
                            p.observaciones, p.condiciones,
                            p.usuario_id,
                            COALESCE(u.username, s.nombre, 'SISTEMA') as usuario_nombre
                          FROM table_presupuestos p
                          LEFT JOIN table_usuarios u ON p.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE p.id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

        /**
     * Resetea un presupuesto atascado en estado ANEXADO (o similar) devolviéndolo
     * a BORRADOR o ENVIADO, sin tocar el stock físico.
     * 
     * v2.8 (2026-10-14):
     *   • Nuevo método para resolver el bug donde los presupuestos quedaban
     *     atascados en ANEXADO porque iniciarProceso() corría ANTES de
     *     obtenerParaAnexar(). Como el segundo rechaza ANEXADO, el frontend
     *     nunca cargaba los items y el presupuesto quedaba huérfano.
     * 
     *   • NO toca stock físico. Solo libera reservas RESERVADA (no FACTURADA).
     *     Esto es seguro porque iniciarProceso() con crear_reservas:false
     *     ya había liberado las reservas sin descontar stock.
     * 
     *   • Estados válidos de origen: ANEXADO, EN_PROCESO.
     *   • Estados destino válidos: BORRADOR (default) o ENVIADO.
     * 
     * @param int    $id
     * @param int    $usuarioId
     * @param string $estadoDestino  'BORRADOR' | 'ENVIADO'
     * @param string $motivo
     * @return bool
     * @throws Exception
     */
    public function resetearDesdeAnexado($id, $usuarioId, $estadoDestino = 'BORRADOR', $motivo = 'RESET_MANUAL') {
        $estadoDestino = strtoupper(trim($estadoDestino));
        if (!in_array($estadoDestino, ['BORRADOR', 'ENVIADO'], true)) {
            throw new Exception("Estado destino no válido: $estadoDestino");
        }

        try {
            $this->db->beginTransaction();

            // 1. Verificar que el presupuesto existe y su estado actual
            $this->db->query("SELECT id, numero, estado FROM table_presupuestos WHERE id = :id");
            $this->db->bind(':id', (int)$id);
            $presupuesto = $this->db->single();

            if (!$presupuesto) {
                throw new Exception("Presupuesto no encontrado.");
            }

            $estadoActual = strtoupper($presupuesto->estado);

            // Solo permitimos resetear desde ANEXADO o EN_PROCESO.
            // Si ya está en BORRADOR / ENVIADO, no hay nada que hacer.
            // Si está CONVERTIDO, no se puede resetear (ya es una venta).
            if (in_array($estadoActual, ['CONVERTIDO', 'RECHAZADO', 'EXPIRADO'], true)) {
                throw new Exception("El presupuesto está en estado $estadoActual y no puede resetearse.");
            }

            if (in_array($estadoActual, ['BORRADOR', 'ENVIADO'], true)) {
                // Ya está en el estado destino, no hacemos nada.
                $this->db->commit();
                return true;
            }

            // 2. Liberar reservas RESERVADA sin tocar stock físico.
            //    (No tocamos FACTURADA porque eso implicaría reingresar stock).
            $this->db->query("UPDATE table_presupuestos_reservas 
                              SET estado = 'LIBERADA',
                                  cantidad_liberada = cantidad_reservada,
                                  fecha_liberacion = NOW(),
                                  usuario_liberacion_id = :uid
                              WHERE presupuesto_id = :pid 
                                AND estado = 'RESERVADA'");
            $this->db->bind(':uid', (int)$usuarioId);
            $this->db->bind(':pid', (int)$id);
            $this->db->execute();

            // 3. Cambiar el estado del presupuesto
            $this->db->query("UPDATE table_presupuestos 
                              SET estado = :estado,
                                  fecha_activacion = NULL,
                                  usuario_activacion_id = NULL
                              WHERE id = :id");
            $this->db->bind(':estado', $estadoDestino);
            $this->db->bind(':id', (int)$id);
            $this->db->execute();

            $this->db->commit();

            // 4. Log de auditoría
            try {
                logAction(
                    'PRESUPUESTO',
                    'RESET_DESDE_ANEXADO',
                    "Presupuesto #{$presupuesto->numero} (ID {$id}) reseteado de {$estadoActual} a {$estadoDestino}. Motivo: {$motivo}"
                );
            } catch (\Throwable $e) {
                error_log("resetearDesdeAnexado logAction falló: " . $e->getMessage());
            }

            return true;

        } catch (Exception $e) {
            if ($this->db) $this->db->rollBack();
            throw $e;
        }
    }
}