<style>
    .report-title { text-align: center; margin-bottom: 20px; text-transform: uppercase; font-weight: bold; font-size: 16px; border-bottom: 2px solid #000; padding-bottom: 5px; }
    .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 10px; }
    .table th { background-color: #f2f2f2; text-transform: uppercase; font-weight: bold; }
    .text-right { text-align: right; }
    .period { font-size: 11px; margin-bottom: 10px; color: #555; font-weight: bold; }
    .total-row { background-color: #eee; font-weight: bold; }
</style>

<div class="report-title"><?php echo $data['titulo_documento']; ?></div>
<div class="period">PERIODO: <?php echo date('d/m/Y', strtotime($data['desde'])); ?> AL <?php echo date('d/m/Y', strtotime($data['hasta'])); ?></div>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Placa</th>
            <th>Vehículo</th>
            <th>Cliente</th>
            <th>Responsable</th>
            <th class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $totalGeneral = 0;
        if(!empty($data['ventas'])):
            foreach ($data['ventas'] as $v): 
                $totalGeneral += (float)($v->subtotal_item ?? 0);
        ?>
            <tr>
                <td>#<?php echo $v->id; ?></td>
                <td><?php echo date('d/m/Y', strtotime($v->fecha)); ?></td>
                <td><?php echo $v->placa; ?></td>
                <td><?php echo $v->modelo_vehiculo; ?></td>
                <td><?php echo $v->cliente_nombre; ?></td>
                <td><?php echo $v->descripcion; ?></td>
                <td class="text-right">$ <?php echo number_format($v->subtotal_item, 2, ',', '.'); ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="7" style="text-align:center">No se encontraron registros en este periodo.</td></tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="6" class="text-right">TOTAL GENERAL:</td>
            <td class="text-right">$ <?php echo number_format($totalGeneral, 2, ',', '.'); ?></td>
        </tr>
    </tfoot>
</table>