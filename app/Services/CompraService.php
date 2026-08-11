<?php
class CompraService {
    private $invModel;
    private $provModel;

    public function __construct() {
        $this->invModel = new ModelInventario();
        $this->provModel = new ModelProveedor();
    }

    /**
     * Orquesta el registro de una compra, actualización de inventario y Kardex
     */
    public function procesarIngresoMercancia($datos) {
        $db = new Database();
        try {
            $db->beginTransaction();

            $productoId = $datos['producto_id'];
            $esNuevo = empty($productoId);

            // 1. Gestionar Inventario
            if ($esNuevo) {
                $this->invModel->crear($datos);
                $productoId = $db->lastInsertId();
                $tipoKardex = 'ENTRADA_COMPRA';
                $obsKardex = "Compra Inicial - Prov: " . $datos['proveedor_id'];
            } else {
                // Lógica de actualización de stock existente
                $prodActual = $this->invModel->obtenerPorId($productoId);
                $nuevosDatos = [
                    'id' => $productoId,
                    'nombre' => $datos['nombre'],
                    'categoria' => $datos['categoria'] ?? $prodActual->categoria,
                    'stock' => $prodActual->stock + $datos['cantidad'],
                    'stock_minimo' => $prodActual->stock_minimo,
                    'ultimo_costo' => $datos['costo'],
                    'precio' => $datos['precio_venta'],
                    'imagen' => $prodActual->imagen
                ];
                $this->invModel->actualizar($nuevosDatos);
                $tipoKardex = 'ENTRADA_COMPRA';
                $obsKardex = "Reposición de Stock";
            }

            // 2. Registrar Movimiento Kardex
            $this->invModel->registrarMovimiento($productoId, $tipoKardex, $datos['cantidad'], null, $obsKardex);

            // 3. Registrar Documento de Compra y Detalle
            // Aquí llamarías a métodos simples del ModelProveedor para los INSERTs
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception("Fallo en el proceso de compra: " . $e->getMessage());
        }
    }
}