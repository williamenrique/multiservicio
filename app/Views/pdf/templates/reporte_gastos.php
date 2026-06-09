<style>
    body { font-family: 'Helvetica', sans-serif; color: #1e293b; font-size: 11px; }
    .report-header { border-bottom: 2px solid #e11d48; padding-bottom: 10px; margin-bottom: 20px; }
    .report-title { font-size: 18px; font-weight: 900; color: #e11d48; text-transform: uppercase; }
    .table { width: 100%; border-collapse: collapse; }
    .table th { background-color: #0f172a; color: white; padding: 10px; text-align: left; text-transform: uppercase; font-size: 9px; }
    .table td { border-bottom: 1px solid #f1f5f9; padding: 10px; font-size: 10px; }
    .text-right { text-align: right; }
    .total-row { background-color: #fff1f2; font-weight: bold; color: #e11d48; }
    .summary-card { background: #0f172a; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
</style>

<div class="report-header">
    <div class="report-title">Reporte de Egresos y Gastos</div>
    <div style="color: #64748b; font-weight: bold;">Periodo: <?php echo date('d/m/Y', strtotime($data['desde'])); ?> al <?php echo date('d/m/Y', strtotime($data['hasta'])); ?></div>
</div>

<div class="summary-card">
    <table width="100%">
        <tr>
            <td>
                <span style="font-size: 9px; color: #94a3b8; text-transform: uppercase;">Total Desembolsado</span><br>
                <span style="font-size: 20px; font-weight: 900; color: #fecaca;">$ <?php echo number_format($data['totales']['egresos'] ?? 0, 2); ?></span>
            </td>
            <td class="text-right">
                <span style="font-size: 9px; color: #94a3b8; text-transform: uppercase;">Estado de Caja</span><br>
                <span style="font-size: 14px; font-weight: bold;">EGRESO CONSOLIDADO</span>
            </td>
        </tr>
    </table>
</div>

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