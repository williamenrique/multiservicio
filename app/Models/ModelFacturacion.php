<?php
/**
 * Modelo de Facturación
 * Maneja la persistencia de ventas y la actualización de stock.
 */
class ModelFacturacion {
    private $db;

    /**
     * Constructor: Permite inyectar una instancia de base de datos
     * para compartir transacciones con el BillingService.
     */
    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    /**
     * Busca facturas por ID, nombre de cliente o placa para el buscador global.
     * @param string $term Término de búsqueda
     */
    public function searchInvoices($term) {
        $this->db->query("SELECT v.id, CONCAT('FAC-', LPAD(v.id, 3, '0')) as id_formateado, COALESCE(vh.placa, v.placa) as placa, c.nombre as cliente_nombre
                          FROM table_facturas v
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE (v.id LIKE :term OR os.id LIKE :term OR vh.placa LIKE :term OR c.nombre LIKE :term OR v.placa LIKE :term)
                          AND v.status IN ('COMPLETADO', 'CREDITO') LIMIT 5");
        $this->db->bind(':term', "%$term%");
        return $this->db->resultSet();
    }

    /**
     * Busca productos o servicios disponibles en el inventario
     * @param string $termino Nombre o categoría
     */
    public function buscarItems($termino) {
        // Traemos solo columnas necesarias para el POS (excluimos imagen por peso)
        $this->db->query("SELECT i.id, i.nombre, i.categoria, i.stock, i.precio, i.costo_promedio,
                          (i.stock - COALESCE((
                              SELECT SUM(vd.cantidad) 
                              FROM table_facturas_detalle vd 
                              JOIN table_facturas v ON vd.factura_id = v.id 
                              WHERE vd.producto_id = i.id AND v.status != 'ANULADO'
                          ), 0)) as stock_disponible
                          FROM table_inventario i
                          WHERE (i.nombre LIKE :term OR i.categoria LIKE :term OR i.id LIKE :term) AND i.estado = 'ACTIVO'
                          HAVING stock_disponible > 0
                          LIMIT 15");
        $this->db->bind(':term', "%$termino%");
        return $this->db->resultSet();
    }

    public function obtenerBorradores() {
        $this->db->query("SELECT * FROM table_facturas WHERE status = 'PENDIENTE' ORDER BY fecha DESC");
        return $this->db->resultSet();
    }

    /**
     * Obtiene todos los borradores con sus respectivos items cargados
     */
    public function obtenerBorradoresCompleto() {
        $this->db->query("SELECT v.*, v.observaciones as observaciones, 
                                 os.diagnostico_entrada as diagnostico_entrada, os.observaciones as observaciones_orden,
                                 CONCAT('FAC-', LPAD(v.id, 3, '0')) as id_formateado,
                                 COALESCE(sm.id, (SELECT vd.mecanico_id FROM table_facturas_detalle vd WHERE vd.factura_id = v.id AND vd.mecanico_id IS NOT NULL LIMIT 1)) as mecanico_id,
                                 COALESCE(sm.nombre, (SELECT s2.nombre FROM table_facturas_detalle vd2 JOIN table_staff s2 ON vd2.mecanico_id = s2.id WHERE vd2.factura_id = v.id AND vd2.mecanico_id IS NOT NULL LIMIT 1), su.nombre, u.username) as usuario_nombre, 
                                 c.nombre as cliente_nombre, COALESCE(vh.placa, v.placa) as placa, 
                                 COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo,
                                 CASE WHEN v.orden_id IS NOT NULL THEN 'OS' WHEN (v.placa IS NOT NULL AND v.placa != '') THEN 'TALLER' ELSE 'MOSTRADOR' END as tipo_procedencia
                          FROM table_facturas v 
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id 
                          LEFT JOIN table_staff su ON u.staff_id = su.id 
                          LEFT JOIN table_staff sm ON os.mecanico_id = sm.id
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE v.status = 'PENDIENTE' ORDER BY v.fecha DESC");
        $ventas = $this->db->resultSet();

        foreach ($ventas as $key => $venta) {
            $this->db->query("SELECT vd.*, i.id as prod_id
                              FROM table_facturas_detalle vd 
                              LEFT JOIN table_inventario i ON vd.producto_id = i.id
                              WHERE vd.factura_id = :vid");
            $this->db->bind(':vid', $venta->id);
            $items = $this->db->resultSet();
            
            $ventas[$key]->items = array_map(function($it) {
                return [
                    'id' => $it->producto_id,
                    'nombre' => $it->descripcion,
                    'precio' => (float)$it->precio_unitario,
                    'costo_promedio' => (float)($it->costo_unitario ?? 0),
                    'cantidad' => (int)$it->cantidad,
                    'tipo' => $it->producto_id ? 'PRODUCTO' : 'SERVICIO'
                ];
            }, $items);
        }
        return $ventas;
    }

    /**
     * Busca un borrador pendiente vinculado a una Orden de Servicio específica.
     */
    public function obtenerBorradorPorOrden($ordenId) {
        $this->db->query("SELECT v.*, v.observaciones as observaciones, 
                                 os.diagnostico_entrada as diagnostico_entrada, os.observaciones as observaciones_orden,
                                 c.nombre as cliente_nombre, COALESCE(vh.placa, v.placa) as placa, 
                                 COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo,
                                 os.mecanico_id,
                                 'OS' as tipo_procedencia
                          FROM table_facturas v 
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE v.orden_id = :oid AND v.status = 'PENDIENTE' LIMIT 1");
        $this->db->bind(':oid', $ordenId);
        $venta = $this->db->single();

        if ($venta) {
            $this->db->query("SELECT vd.*, i.id as prod_id
                              FROM table_facturas_detalle vd 
                              LEFT JOIN table_inventario i ON vd.producto_id = i.id
                              WHERE vd.factura_id = :vid");
            $this->db->bind(':vid', $venta->id);
            $items = $this->db->resultSet();
            
            // Mapeo al formato que el POS de facturacion.js requiere
            $venta->items = array_map(function($it) {
                return [
                    'id' => $it->producto_id,
                    'nombre' => $it->descripcion,
                    'precio' => (float)$it->precio_unitario,
                    'costo_promedio' => (float)($it->costo_unitario ?? 0),
                    'cantidad' => (int)$it->cantidad,
                    'tipo' => $it->producto_id ? 'PRODUCTO' : 'SERVICIO',
                    'temp_id' => bin2hex(random_bytes(4)) // ID único para el manejo en el DOM del POS
                ];
            }, $items);
        }
        return $venta;
    }

    /**
     * Registra o actualiza la cabecera de una venta.
     * Los cálculos deben venir ya procesados desde el BillingService.
     * @param array $datos Datos de la venta
     * @param string $status Estado (COMPLETADO, CREDITO, PENDIENTE)
     * @param array $totales Resumen de montos
     * @param int $usuarioId ID del usuario que procesa
     * @return int ID de la venta
     */
    public function guardarCabeceraVenta($datos, $status, $totales, $usuarioId) {
        try {
            $ventaId = !empty($datos['id_db']) ? $datos['id_db'] : null;
            $ordenIdPersist = !empty($datos['orden_id']) ? (int)$datos['orden_id'] : null;

            if ($ventaId && $ordenIdPersist === null) {
                $this->db->query("SELECT orden_id FROM table_facturas WHERE id = :id");
                $this->db->bind(':id', $ventaId);
                $facturaActual = $this->db->single();
                if ($facturaActual) {
                    $ordenIdPersist = !empty($facturaActual->orden_id) ? (int)$facturaActual->orden_id : null;
                }
            }

            if ($ventaId) {
                $this->db->query("UPDATE table_facturas SET
                                  cliente_id = :cid, orden_id = :oid, placa = :placa, modelo_vehiculo = :modelo,
                                  subtotal = :sub, iva_monto = :iva, total = :total, 
                                  pago_efectivo = :pef, pago_transferencia = :ptra, saldo_pendiente = :spend,
                                  status = :status, observaciones = :obs
                                  WHERE id = :id");
                $this->db->bind(':id', $ventaId);
            } else {
                $this->db->query("INSERT INTO table_facturas (cliente_id, orden_id, placa, modelo_vehiculo, subtotal, iva_monto, total, 
                                  pago_efectivo, pago_transferencia, saldo_pendiente, usuario_id, status, observaciones) 
                                  VALUES (:cid, :oid, :placa, :modelo, :sub, :iva, :total, :pef, :ptra, :spend, :uid, :status, :obs)");
                $this->db->bind(':uid', $usuarioId);
            }
            $this->db->bind(':cid', !empty($datos['cliente_id']) ? $datos['cliente_id'] : null);
            $this->db->bind(':oid', $ordenIdPersist);
            $this->db->bind(':placa', !empty($datos['placa']) ? $datos['placa'] : null);
            $this->db->bind(':modelo', !empty($datos['modelo']) ? $datos['modelo'] : null);
            $this->db->bind(':sub', $totales['subtotal']);
            $this->db->bind(':iva', $totales['iva']);
            $this->db->bind(':total', $totales['total']);
            $this->db->bind(':pef', $datos['pago_efectivo']);
            $this->db->bind(':ptra', $datos['pago_transferencia']);
            $this->db->bind(':spend', $totales['saldo']);
            $this->db->bind(':status', $status);
            $this->db->bind(':obs', mb_strtoupper($datos['observaciones'] ?? '', 'UTF-8'));
            $this->db->execute();

            // CIERRE AUTOMÁTICO DE ORDEN (Reemplaza al Trigger tg_actualizar_orden_al_facturar)
            // Solo actualizamos la orden si la factura está vinculada a una Orden de Servicio real.
            $esFacturaOrdenServicio = !empty($ordenIdPersist);
            if ($esFacturaOrdenServicio && in_array($status, ['COMPLETADO', 'CREDITO'], true)) {
                $this->sincronizarOrdenServicio(
                    $ordenIdPersist,
                    $status,
                    trim((string)($datos['observaciones'] ?? $datos['diagnostico_salida'] ?? '')),
                    trim((string)($datos['diagnostico_salida'] ?? $datos['observaciones'] ?? '')),
                    $usuarioId,
                    'FACTURACIÓN FINALIZADA'
                );
            }

            return $ventaId ?: $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error en guardarCabeceraVenta: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sincroniza una orden de servicio con el cierre de la factura.
     * Marca la orden como ENTREGADO cuando la factura pasa a COMPLETADO o CREDITO.
     */
    private function sincronizarOrdenServicio($ordenId, $statusFactura, $observacionesFactura, $diagnosticoSalidaFactura, $usuarioId, $motivo) {
        if (!$ordenId || !in_array($statusFactura, ['COMPLETADO', 'CREDITO'], true)) {
            return;
        }

        $this->db->query("SELECT id, estado, diagnostico_salida, observaciones FROM table_ordenes_servicio WHERE id = :oid");
        $this->db->bind(':oid', $ordenId);
        $ordenActual = $this->db->single();

        if (!$ordenActual) {
            return;
        }

        $estadoPrevio = $ordenActual->estado ?? 'DESCONOCIDO';
        $diagnosticoSalida = trim((string)($diagnosticoSalidaFactura !== '' ? $diagnosticoSalidaFactura : ($ordenActual->diagnostico_salida ?? '')));
        $observacionesFactura = trim((string)$observacionesFactura);

        if ($diagnosticoSalida === '' && $observacionesFactura !== '') {
            $diagnosticoSalida = $observacionesFactura;
        }

        $observacionesOrden = trim((string)($ordenActual->observaciones ?? ''));
        if ($observacionesFactura !== '' && stripos($observacionesOrden, $observacionesFactura) === false) {
            $observacionesOrden = $observacionesOrden === ''
                ? $observacionesFactura
                : $observacionesOrden . "\n" . $observacionesFactura;
        }

        $this->db->query("UPDATE table_ordenes_servicio
                          SET estado = 'ENTREGADO',
                              fecha_entrega_real = NOW(),
                              diagnostico_salida = :diag,
                              observaciones = :obs
                          WHERE id = :oid");
        $this->db->bind(':diag', $diagnosticoSalida !== '' ? $diagnosticoSalida : null);
        $this->db->bind(':obs', $observacionesOrden !== '' ? $observacionesOrden : null);
        $this->db->bind(':oid', $ordenId);
        $this->db->execute();

        $this->db->query("INSERT INTO table_orden_estados_log (orden_id, estado_anterior, estado_nuevo, usuario_id, comentario)
                          VALUES (:oid, :ant, 'ENTREGADO', :uid, :txt)");
        $this->db->bind(':oid', $ordenId);
        $this->db->bind(':ant', $estadoPrevio);
        $this->db->bind(':uid', $usuarioId);
        $this->db->bind(':txt', trim($motivo . ' (' . $statusFactura . ')'));
        $this->db->execute();
    }

    /**
     * Obtiene los detalles completos de una venta para su impresión
     */
    public function obtenerVentaCompleta($id) {
        $this->db->query("SELECT v.*, v.observaciones as observaciones_factura, CONCAT('FAC-', LPAD(v.id, 3, '0')) as id_formateado,
                                 c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.email as cliente_email, 
                                 COALESCE(vh.placa, v.placa) as placa, 
                                 COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo,
                                 vh.marca as marca_vehiculo,
                                 COALESCE(st_m.nombre, (SELECT s2.nombre FROM table_facturas_detalle vd2 JOIN table_staff s2 ON vd2.mecanico_id = s2.id WHERE vd2.factura_id = v.id AND vd2.mecanico_id IS NOT NULL LIMIT 1)) as mecanico_nombre,
                                 sv.nombre as vendedor_nombre,
                                 os.kilometraje, os.nivel_combustible, os.diagnostico_entrada as diagnostico_entrada, os.observaciones as observaciones_orden
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
            $this->db->query("SELECT vd.*, s.nombre as mecanico_nombre 
                              FROM table_facturas_detalle vd
                              LEFT JOIN table_staff s ON vd.mecanico_id = s.id 
                              WHERE vd.factura_id = :vid");
            $this->db->bind(':vid', $id);
            $venta->items = $this->db->resultSet();
        }

        // Cargar Checklist si la factura proviene de una Orden de Servicio
        if ($venta && $venta->orden_id) {
            $this->db->query("SELECT item, observacion FROM table_orden_checklist WHERE orden_id = :oid");
            $this->db->bind(':oid', $venta->orden_id);
            $venta->checklist = $this->db->resultSet();
        }

        return $venta;
    }

    /**
     * Lista las ventas realizadas por mostrador (sin placa vinculada)
     * para el historial específico de repuestos.
     */
    public function obtenerVentasMostrador($limit = 10, $offset = 0, $search = null, $desde = null, $hasta = null) {
        $where = "WHERE v.orden_id IS NULL AND (v.placa IS NULL OR v.placa = '') AND v.status IN ('COMPLETADO', 'CREDITO')";
        
        if ($search) {
            $where .= " AND (v.id LIKE :search OR c.nombre LIKE :search)";
        }
        if ($desde) {
            $where .= " AND DATE(v.fecha) >= :desde";
        }
        if ($hasta) {
            $where .= " AND DATE(v.fecha) <= :hasta";
        }

        // Obtener total de registros filtrados
        $this->db->query("SELECT COUNT(*) as total FROM table_facturas v LEFT JOIN table_clientes c ON v.cliente_id = c.id $where");
        if ($search) $this->db->bind(':search', "%$search%");
        if ($desde) $this->db->bind(':desde', $desde);
        if ($hasta) $this->db->bind(':hasta', $hasta);
        $total = (int)$this->db->single()->total;

        // Obtener los datos paginados
        $this->db->query("SELECT v.*, c.nombre as cliente_nombre, 
                          COALESCE(sv.nombre, u.username, 'SISTEMA') as vendedor_nombre, 
                          (SELECT COUNT(*) FROM table_facturas_detalle WHERE factura_id = v.id AND producto_id IS NOT NULL) as cant_productos
                          FROM table_facturas v
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff sv ON u.staff_id = sv.id
                          $where
                          ORDER BY v.fecha DESC 
                          LIMIT :limit OFFSET :offset");
        if ($search) $this->db->bind(':search', "%$search%");
        if ($desde) $this->db->bind(':desde', $desde);
        if ($hasta) $this->db->bind(':hasta', $hasta);
        $this->db->bind(':limit', (int)$limit);
        $this->db->bind(':offset', (int)$offset);
        
        return ['data' => $this->db->resultSet(), 'total' => $total];
    }

    /**
     * Métodos de gestión de borradores requeridos por el controlador
     */
    public function obtenerBorradorPorId($id) {
        $this->db->query("SELECT * FROM table_facturas WHERE id = :id AND status = 'PENDIENTE'");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Elimina un borrador de factura (Venta en estado PENDIENTE).
     * Elimina primero el detalle para evitar errores de integridad referencial (FK).
     */
    public function eliminarBorrador($id) {
        // 1. Eliminar el detalle asociado a la factura
        $this->db->query("DELETE FROM table_facturas_detalle WHERE factura_id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();

        // 2. Eliminar la cabecera de la factura
        $this->db->query("DELETE FROM table_facturas WHERE id = :id AND status = 'PENDIENTE'");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Obtiene los datos para el reporte de auditoría de trabajos.
     * Retorna el resumen de deudas (para tarjetas) y la lista de trabajos realizados.
     * Se agrega paginación y corrección en el conteo de deudores (Clientes únicos).
     */
    public function obtenerAuditoriaTrabajos($limit = 10, $offset = 0) {
        // 1. Resumen de Deudores (Monto total y cantidad de CLIENTES únicos con saldo pendiente)
        $this->db->query("SELECT SUM(saldo_pendiente) as total_deuda, COUNT(DISTINCT cliente_id) as cantidad_deudores 
                          FROM table_facturas WHERE status = 'CREDITO' AND saldo_pendiente > 0.05 AND (orden_id IS NOT NULL OR placa IS NOT NULL)");
        $resumen = $this->db->single();

        // 2. Conteo total para paginación
        $this->db->query("SELECT COUNT(*) as total FROM table_facturas WHERE status IN ('COMPLETADO', 'CREDITO', 'PENDIENTE') AND (orden_id IS NOT NULL OR placa IS NOT NULL)");
        $total = $this->db->single()->total;

        // 3. Lista de trabajos con paginación
        $this->db->query("SELECT v.id, CONCAT('FAC-', LPAD(v.id, 3, '0')) as id_formateado,
                                 v.fecha, v.total, v.saldo_pendiente, v.status,
                                 c.nombre as cliente_nombre, c.telefono as cliente_telefono, sv.nombre as vendedor_nombre, 
                                 COALESCE(
                                     sm.nombre, 
                                     (SELECT st2.nombre FROM table_facturas_detalle vd2 JOIN table_staff st2 ON vd2.mecanico_id = st2.id WHERE vd2.factura_id = v.id AND vd2.mecanico_id IS NOT NULL LIMIT 1),
                                     sv.nombre, 
                                     'ADMIN') as responsable_nombre,
                                 COALESCE(vh.placa, v.placa) as placa, 
                                 COALESCE(vh.modelo, v.modelo_vehiculo, 'N/A') as modelo_vehiculo,
                                 CASE WHEN v.orden_id IS NOT NULL THEN 'OS' WHEN v.placa IS NOT NULL THEN 'TALLER' ELSE 'MOSTRADOR' END as tipo_procedencia
                          FROM table_facturas v
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff sv ON u.staff_id = sv.id
                          LEFT JOIN table_staff sm ON os.mecanico_id = sm.id
                          WHERE v.status IN ('COMPLETADO', 'CREDITO', 'PENDIENTE') AND (v.orden_id IS NOT NULL OR v.placa IS NOT NULL)
                          ORDER BY v.fecha DESC
                          LIMIT :limit OFFSET :offset");
        $this->db->bind(':limit', (int)$limit);
        $this->db->bind(':offset', (int)$offset);
        $lista = $this->db->resultSet();

        return [
            'resumen' => $resumen,
            'lista' => $lista,
            'total' => $total
        ];
    }

    /**
     * Registra un abono a una venta con deuda.
     * Si el saldo llega a cero, la factura pasa a COMPLETADO.
     */
    public function registrarAbono($ventaId, $monto, $metodo) {
        try {
            $this->db->query("SELECT id, orden_id, observaciones, total, pago_efectivo, pago_transferencia, saldo_pendiente FROM table_facturas WHERE id = :id");
            $this->db->bind(':id', $ventaId);
            $venta = $this->db->single();

            if (!$venta) throw new AppException("Venta no encontrada para registrar el abono.");

            $monto = (float)$monto;
            $nuevoPendiente = $venta->saldo_pendiente - $monto;
            
            // 1. Insertar el registro en la tabla de abonos
            $this->db->query("INSERT INTO table_abonos_clientes (factura_id, monto, metodo_pago) VALUES (:vid, :monto, :metodo)");
            $this->db->bind(':vid', $ventaId);
            $this->db->bind(':monto', $monto);
            $this->db->bind(':metodo', $metodo);
            $this->db->execute();

            // 2. Determinar qué columna de pago actualizar
            $columnaPago = ($metodo === 'TRANSFERENCIA') ? 'pago_transferencia' : 'pago_efectivo';
            
            // 3. Actualizar la venta principal
            // Si el saldo pendiente es muy cercano a cero (por decimales), marcar como COMPLETADO
            $nuevoStatus = ($nuevoPendiente <= 0.01) ? 'COMPLETADO' : 'CREDITO';

            $this->db->query("UPDATE table_facturas SET 
                              $columnaPago = $columnaPago + :monto,
                              saldo_pendiente = :pendiente,
                              status = :status
                              WHERE id = :id");
            $this->db->bind(':monto', $monto);
            $this->db->bind(':pendiente', $nuevoPendiente > 0 ? $nuevoPendiente : 0);
            $this->db->bind(':status', $nuevoStatus);
            $this->db->bind(':id', $ventaId);

            $this->db->execute();

            // 4. Si el abono deja la factura saldada, sincronizar la orden de servicio asociada.
            $nuevoStatus = ($nuevoPendiente <= 0.01) ? 'COMPLETADO' : 'CREDITO';
            if ($venta->orden_id && in_array($nuevoStatus, ['COMPLETADO', 'CREDITO'], true)) {
                $this->sincronizarOrdenServicio(
                    (int)$venta->orden_id,
                    $nuevoStatus,
                    trim((string)($venta->observaciones ?? '')),
                    '',
                    $_SESSION['user_id'] ?? null,
                    'ABONO A FACTURA'
                );
            }

            // 5. Registrar el abono como un ingreso en table_transacciones
            // Esto asegura que el flujo de caja refleje el dinero recibido.
            $this->db->query("INSERT INTO table_transacciones (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id) 
                              VALUES (1, 'INGRESO', 'ABONO_CLIENTE', :monto_abono, :ref_id, :desc_abono, :uid)");
            $this->db->bind(':monto_abono', $monto);
            $this->db->bind(':ref_id', $ventaId);
            $this->db->bind(':desc_abono', "ABONO FACTURA #" . $ventaId . " (" . $metodo . ")");
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->execute();

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Obtiene ventas a crédito con más de 15 días de antigüedad.
     * @param int $dias Límite de días para considerar vencido.
     * @return array
     */
    public function obtenerCreditosVencidos($dias = 15) {
        $this->db->query("SELECT v.id, v.fecha, v.total, v.saldo_pendiente, COALESCE(vh.placa, v.placa) as placa, COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo, COALESCE(c.nombre, 'SIN CLIENTE') as cliente_nombre 
                          FROM table_facturas v
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE v.status = 'CREDITO'
                          AND v.saldo_pendiente > 0
                          AND DATEDIFF(CURDATE(), COALESCE(DATE(v.fecha), CURDATE())) >= :dias
                          ORDER BY v.fecha ASC");
        $this->db->bind(':dias', $dias);
        return $this->db->resultSet();
    }

    /**
     * Helper interno para calcular diferencia de días entre fechas
     */
    private function calcularDiferenciaDias($d1, $d2) {
        return round(abs(strtotime($d1) - strtotime($d2)) / 86400);
    }

    /**
     * Procesa la devolución de un ítem específico de una factura.
     * @param int $ventaId ID de la factura
     * @param int $detalleId ID de la línea de detalle
     * @param string $destino Destino del ítem (STOCK o DANADO)
     * @return bool
     */
    public function procesarDevolucion($ventaId, $detalleId, $destino) {
        try {
            // Se elimina beginTransaction de aquí. 
            // La transacción ahora es controlada por BillingService.

            // 1. Obtener datos exactos del ítem y de la factura
            $this->db->query("SELECT vd.producto_id, vd.descripcion, vd.cantidad, vd.precio_unitario, 
                                     v.fecha, v.subtotal, v.iva_monto, v.total, v.saldo_pendiente,
                                     v.pago_efectivo, v.pago_transferencia
                              FROM table_facturas_detalle vd
                              JOIN table_facturas v ON vd.factura_id = v.id
                              WHERE vd.id = :id AND v.id = :vid");
            $this->db->bind(':id', $detalleId);
            $this->db->bind(':vid', $ventaId);
            $item = $this->db->single();

            if (!$item) {
                throw new Exception("El ítem de la factura no existe.");
            }
            if ($this->calcularDiferenciaDias(date('Y-m-d'), $item->fecha) > 5) {
                throw new Exception("Plazo de devolución vencido (máximo 5 días desde la compra).");
            }

            // 2. Calcular montos proporcionales (Base + IVA)
            $montoBase = (float)$item->precio_unitario * (int)$item->cantidad;
            $factorIva = ((float)$item->subtotal > 0) ? ((float)$item->iva_monto / (float)$item->subtotal) : 0;
            $ivaDevolver = $montoBase * $factorIva;
            $totalARestar = $montoBase + $ivaDevolver;

            // 2. Si es producto y el destino es REINGRESO, sumar al inventario
            if (!empty($item->producto_id)) {
                if ($destino === 'STOCK') {
                    $this->db->query("UPDATE table_inventario SET stock = stock + :cant, updated_at = NOW() WHERE id = :pid");
                    $this->db->bind(':cant', $item->cantidad);
                    $this->db->bind(':pid', $item->producto_id);
                    $this->db->execute();
                    
                    // Sugerencia: Pasa la conexión de DB actual al modelo de inventario
                    $invModel = new ModelInventario($this->db);
                    $invModel->registrarMovimiento($item->producto_id, 'ENTRADA_DEVOLUCION', $item->cantidad, $ventaId, "Devolución Factura #$ventaId");
                }
            }

            // 3. Registrar en el historial de devoluciones para auditoría
            $this->db->query("INSERT INTO table_devoluciones (factura_id, producto_id, descripcion, cantidad, monto_devuelto, destino, usuario_id) 
                              VALUES (:vid, :pid, :desc, :cant, :monto, :dest, :uid)");
            $this->db->bind(':vid', $ventaId);
            $this->db->bind(':pid', $item->producto_id);
            $this->db->bind(':desc', $item->descripcion);
            $this->db->bind(':cant', $item->cantidad);
            $this->db->bind(':monto', $totalARestar);
            $this->db->bind(':dest', $destino);
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->execute();

            // 4. Ajustar la factura (Restar del total y del saldo si es crédito)
            $nuevoSubtotal = max(0, (float)$item->subtotal - $montoBase);
            $nuevoIva = max(0, (float)$item->iva_monto - $ivaDevolver);
            $nuevoTotal = max(0, (float)$item->total - $totalARestar);

            // Lógica de Devolución de Dinero:
            // 1. Primero restamos del saldo pendiente (si el cliente debía dinero)
            $saldoAReducir = min((float)$item->saldo_pendiente, $totalARestar);
            $restoParaPagos = $totalARestar - $saldoAReducir;
            
            $nuevoSaldo = (float)$item->saldo_pendiente - $saldoAReducir;
            $nuevoPagoEfe = (float)$item->pago_efectivo;
            $nuevoPagoTra = (float)$item->pago_transferencia;

            // 2. Si aún queda monto por devolver, lo restamos de lo pagado (priorizando efectivo)
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
                              subtotal = :sub,
                              iva_monto = :iva,
                              total = :total, 
                              pago_efectivo = :pefe,
                              pago_transferencia = :ptra,
                              saldo_pendiente = :saldo 
                              WHERE id = :vid");
            $this->db->bind(':sub', $nuevoSubtotal);
            $this->db->bind(':iva', $nuevoIva);
            $this->db->bind(':total', $nuevoTotal);
            $this->db->bind(':pefe', $nuevoPagoEfe);
            $this->db->bind(':ptra', $nuevoPagoTra);
            $this->db->bind(':saldo', $nuevoSaldo > 0 ? $nuevoSaldo : 0);
            $this->db->bind(':vid', $ventaId);
            $this->db->execute();

            // REGISTRAR EN LIBRO MAYOR (EGRESO POR DEVOLUCIÓN)
            $this->db->query("INSERT INTO table_transacciones (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id) 
                              VALUES (1, 'EGRESO', 'DEVOLUCION', :monto, :ref, :desc, :uid)");
            $this->db->bind(':monto', $totalARestar);
            $this->db->bind(':ref', $ventaId);
            $this->db->bind(':desc', "DEVOLUCION ITEM: " . mb_strtoupper($item->descripcion, 'UTF-8'));
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->execute();

            // 5. Eliminar el detalle de la factura original
            $this->db->query("DELETE FROM table_facturas_detalle WHERE id = :id");
            $this->db->bind(':id', $detalleId);
            $this->db->execute();

            return true;
        } catch (Exception $e) {
            error_log("Error Devolución: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene un reporte de utilidad bruta (Venta - Costo)
     */
    public function obtenerReporteUtilidad($desde, $hasta) {
        $this->db->query("SELECT 
                            SUM(vd.precio_unitario * vd.cantidad) as total_ventas,
                            SUM(vd.costo_unitario * vd.cantidad) as total_costos,
                            SUM(CASE WHEN vd.producto_id IS NULL THEN (vd.precio_unitario * vd.cantidad) ELSE 0 END) as total_servicios,
                            SUM(CASE WHEN vd.producto_id IS NOT NULL THEN (vd.precio_unitario - vd.costo_unitario) * vd.cantidad ELSE 0 END) as ganancia_repuestos,
                            SUM((vd.precio_unitario - vd.costo_unitario) * vd.cantidad) as utilidad_bruta
                          FROM table_facturas_detalle vd
                          JOIN table_facturas v ON vd.factura_id = v.id
                          WHERE v.status IN ('COMPLETADO', 'CREDITO')
                          AND DATE(v.fecha) BETWEEN :desde AND :hasta");
        
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        
        return $this->db->single();
    }
}