<?php
class ControllerSearch extends Controller {
    private $facturaModel;
    private $clienteModel;
    private $vehiculoModel;
    private $inventarioModel;
    private $proveedorModel;

    public function __construct() {
        AuthGuard::handle();
        $this->facturaModel = $this->model('Facturacion');
        $this->clienteModel = $this->model('Cliente');
        $this->vehiculoModel = $this->model('Vehiculo');
        $this->inventarioModel = $this->model('Inventario');
        $this->proveedorModel = $this->model('Proveedor');
    }

    /**
     * Método para la búsqueda global (URL: /search/global)
     * Busca en: Facturas, Clientes, Vehículos, Repuestos (Inventario) y Proveedores.
     */
    public function global() {
        $term = trim($_GET['term'] ?? '');
        if (strlen($term) < 2) {
            return $this->jsonResponse(['success' => true, 'results' => []]);
        }

        $results = [];

        // Buscar Facturas (por número de factura, cliente o placa)
        foreach ($this->facturaModel->searchInvoices($term) as $inv) {
            $numFactura = $inv->id_formateado ?? ('FAC-' . str_pad($inv->id, 3, '0', STR_PAD_LEFT));
            $estado = $inv->status ?? '';
            $origen = $inv->origen ?? 'MOSTRADOR';
            $total = isset($inv->total) ? '$' . number_format($inv->total, 0) : '';

            // Etiqueta de estado
            $etiquetaEstado = match($estado) {
                'COMPLETADO' => '✓ COMPLETADA',
                'CREDITO'    => '◐ CRÉDITO',
                'PENDIENTE'  => '⏸ BORRADOR',
                default       => $estado
            };

            // Etiqueta de origen
            $etiquetaOrigen = match($origen) {
                'GARANTIA'  => 'GARANTÍA',
                'CATALOGO'  => 'CATÁLOGO',
                'TALLER'    => 'TALLER',
                'MOSTRADOR' => 'MOSTRADOR',
                default      => $origen
            };

            // Etiquetas adicionales (devolución / garantía)
            $etiquetasExtra = [];
            if (!empty($inv->tiene_devolucion)) {
                $etiquetasExtra[] = '↩ DEVOLUCIÓN';
            }
            if (!empty($inv->tiene_garantia)) {
                $etiquetasExtra[] = '🛡 GARANTÍA';
            }

            // Construir subtítulo con etiquetas
            $subtitulo = $etiquetaEstado . ' · ' . $etiquetaOrigen;
            if (!empty($etiquetasExtra)) {
                $subtitulo .= ' · ' . implode(' · ', $etiquetasExtra);
            }
            if (!empty($total)) {
                $subtitulo .= ' · ' . $total;
            }

            // Enlace según estado
            if ($estado === 'PENDIENTE') {
                $link = URLROOT . "/facturacion?search=" . urlencode($numFactura);
            } elseif ($origen === 'GARANTIA') {
                $link = URLROOT . "/garantia?search=" . urlencode($numFactura);
            } else {
                $link = "javascript:printInvoice({$inv->id})";
            }

            $results[] = [
                'type' => 'Factura',
                'title' => "{$numFactura} — {$inv->cliente_nombre} ({$inv->placa})",
                'subtitle' => $subtitulo,
                'link' => $link
            ];
        }

        // Buscar Repuestos del Inventario (por código, nombre o categoría)
        foreach ($this->inventarioModel->searchRepuestos($term) as $rep) {
            $codigo = !empty($rep->codigo) ? "[{$rep->codigo}] " : '';
            $results[] = [
                'type' => 'Repuesto',
                'title' => "{$codigo}{$rep->nombre} - {$rep->categoria} (Stock: {$rep->stock})",
                'link' => URLROOT . "/inventario?search=" . urlencode($rep->nombre)
            ];
        }

        // Buscar Proveedores (por ID, nombre, teléfono o email)
        foreach ($this->proveedorModel->searchProveedores($term) as $prov) {
            $results[] = [
                'type' => 'Proveedor',
                'title' => "{$prov->nombre} (ID: {$prov->id})" . (!empty($prov->telefono) ? " - {$prov->telefono}" : ''),
                'link' => URLROOT . "/proveedores?search=" . urlencode($prov->nombre)
            ];
        }

        // Buscar Clientes
        foreach ($this->clienteModel->searchClients($term) as $cli) {
            $results[] = [
                'type' => 'Cliente',
                'title' => "Cliente: {$cli->nombre} (ID: {$cli->id})",
                'link' => URLROOT . "/clientes/index?search={$cli->id}"
            ];
        }

        // Buscar Vehículos
        foreach ($this->vehiculoModel->searchVehicles($term) as $veh) {
            $results[] = [
                'type' => 'Vehículo',
                'title' => "Placa: {$veh->placa} - {$veh->marca} {$veh->modelo}",
                'link' => URLROOT . "/taller/historial/{$veh->placa}"
            ];
        }

        $this->jsonResponse(['success' => true, 'results' => $results]);
    }
}