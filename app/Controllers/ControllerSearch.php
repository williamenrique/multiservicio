<?php
class ControllerSearch extends Controller {
    private $facturaModel;
    private $clienteModel;
    private $vehiculoModel;

    public function __construct() {
        AuthGuard::handle();
        $this->facturaModel = $this->model('Facturacion');
        $this->clienteModel = $this->model('Cliente');
        $this->vehiculoModel = $this->model('Vehiculo');
    }

    /**
     * Método para la búsqueda global (URL: /search/global)
     */
    public function global() {
        $term = trim($_GET['term'] ?? '');
        if (strlen($term) < 3) {
            return $this->jsonResponse(['success' => true, 'results' => []]);
        }

        $results = [];

        // Buscar Facturas
        foreach ($this->facturaModel->searchInvoices($term) as $inv) {
            $results[] = [
                'type' => 'Factura',
                'title' => "Factura #{$inv->id} - {$inv->cliente_nombre} ({$inv->placa})",
                'link' => "javascript:printInvoice({$inv->id})"
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