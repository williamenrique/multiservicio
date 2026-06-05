<style>
    .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .table th, .table td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; font-size: 10px; }
    .table th { background-color: #f8fafc; font-weight: bold; text-transform: uppercase; color: #1e293b; }
    .text-right { text-align: right; }
    .period { font-size: 11px; margin-bottom: 20px; font-weight: bold; color: #475569; }
    .utilidad { color: #10b981; font-weight: bold; }
</style>

<div class="period">
    REPORTE DE VENTAS DE REPUESTOS (ADMINISTRACIÓN)<br>
    DESDE: <?php echo date('d/m/Y', strtotime($data['desde'])); ?> - HASTA: <?php echo date('d/m/Y', strtotime($data['hasta'])); ?>
</div>

<table class="table">
    <thead>
        <tr>
            <th>ID Venta</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th class="text-right">Total Venta</th>
            <th class="text-right">Costo Mercancía</th>
            <th class="text-right">Utilidad Bruta</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $tVenta = 0; $tCosto = 0; $tUtilidad = 0;
        foreach ($data['datos'] as $row): 
            $tVenta += $row->total_venta;
            $tCosto += $row->total_costo;
            $tUtilidad += $row->utilidad;
        ?>
            <tr>
                <td>#<?php echo $row->id; ?></td>
                <td><?php echo date('d/m/Y', strtotime($row->fecha)); ?></td>
                <td><?php echo $row->cliente ?: 'MOSTRADOR'; ?></td>
                <td class="text-right">$ <?php echo number_format($row->total_venta, 2); ?></td>
                <td class="text-right">$ <?php echo number_format($row->total_costo, 2); ?></td>
                <td class="text-right utilidad">$ <?php echo number_format($row->utilidad, 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td colspan="3" class="text-right">TOTALES CONSOLIDADOS:</td>
            <td class="text-right">$ <?php echo number_format($tVenta, 2); ?></td>
            <td class="text-right">$ <?php echo number_format($tCosto, 2); ?></td>
            <td class="text-right utilidad">$ <?php echo number_format($tUtilidad, 2); ?></td>
        </tr>
    </tfoot>
</table>