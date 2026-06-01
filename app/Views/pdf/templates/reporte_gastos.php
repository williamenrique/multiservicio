<style>
    .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 10px; }
    .table th { background-color: #fef2f2; text-transform: uppercase; font-weight: bold; color: #b91c1c; }
    .text-right { text-align: right; }
    .period { font-size: 11px; margin-bottom: 20px; color: #64748b; font-weight: bold; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
    .total-row { background-color: #fef2f2; font-weight: bold; }
</style>

<div class="period">PERIODO: <?php echo date('d/m/Y', strtotime($data['desde'])); ?> AL <?php echo date('d/m/Y', strtotime($data['hasta'])); ?></div>

<table class="table">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Categoría</th>
            <th>Descripción</th>
            <th class="text-right">Monto</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if(!empty($data['gastos'])):
            foreach ($data['gastos'] as $g): 
        ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($g->fecha)); ?></td>
                <td><?php echo $g->tipo; ?></td>
                <td><?php echo $g->categoria; ?></td>
                <td><?php echo $g->descripcion ?? '---'; ?></td>
                <td class="text-right">$ <?php echo number_format($g->monto_pagado, 2, ',', '.'); ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="5" style="text-align:center">No hay egresos registrados en este periodo.</td></tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="4" class="text-right">TOTAL EGRESOS (GASTOS + COMPRAS):</td>
            <td class="text-right">$ <?php echo number_format($data['totales']['egresos'] ?? 0, 2, ',', '.'); ?></td>
        </tr>
    </tfoot>
</table>