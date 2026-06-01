<style>
    .period { font-size: 11px; margin-bottom: 20px; color: #64748b; font-weight: bold; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
    
    .work-block { margin-bottom: 20px; border: 1px solid #f1f5f9; border-radius: 5px; overflow: hidden; page-break-inside: avoid; }
    .work-header { background-color: #f8fafc; padding: 8px 12px; border-bottom: 1px solid #f1f5f9; }
    .work-info { width: 100%; font-size: 10px; }
    .work-title { font-weight: 900; color: #0f172a; font-size: 12px; text-transform: uppercase; }
    
    .items-list { width: 100%; border-collapse: collapse; }
    .items-list td { padding: 4px 12px; font-size: 10px; color: #475569; border-bottom: 1px dotted #f1f5f9; }
    .item-desc { text-transform: uppercase; }
    .item-price { text-align: right; font-weight: bold; }
    
    .work-footer { padding: 5px 12px; background-color: #fff; text-align: right; font-size: 10px; font-weight: bold; color: #0f172a; }
    .text-right { text-align: right; }
    
    .grand-total-box { margin-top: 30px; padding: 15px; border: 2px solid #0f172a; border-radius: 8px; text-align: right; }
    .grand-total-label { font-size: 10px; color: #64748b; font-weight: bold; display: block; text-transform: uppercase; }
    .grand-total-amount { font-size: 20px; font-weight: 900; color: #10b981; }
</style>

<div class="period">PERIODO DE CONSULTA: <?php echo date('d/m/Y', strtotime($data['desde'])); ?> AL <?php echo date('d/m/Y', strtotime($data['hasta'])); ?></div>

<?php 
$totalGeneral = 0;
if(!empty($data['ventas'])):
    // Agrupación manual de items por Orden/Venta
    $ventasAgrupadas = [];
    foreach ($data['ventas'] as $v) {
        if (!isset($ventasAgrupadas[$v->id])) {
            $ventasAgrupadas[$v->id] = (object)[
                'id' => $v->id,
                'fecha' => $v->fecha,
                'placa' => $v->placa,
                'modelo_vehiculo' => $v->modelo_vehiculo,
                'cliente_nombre' => $v->cliente_nombre,
                'total_orden' => 0,
                'items' => []
            ];
        }
        $ventasAgrupadas[$v->id]->items[] = $v;
        $ventasAgrupadas[$v->id]->total_orden += (float)$v->subtotal_item;
        $totalGeneral += (float)$v->subtotal_item;
    }

    foreach ($ventasAgrupadas as $orden):
?>
    <div class="work-block">
        <div class="work-header">
            <table class="work-info">
                <tr>
                    <td class="work-title" width="30%">ORDEN #<?php echo $orden->id; ?></td>
                    <td width="35%"><strong>FECHA:</strong> <?php echo date('d/m/Y', strtotime($orden->fecha)); ?></td>
                    <td width="35%" class="text-right"><strong>PLACA:</strong> <?php echo $orden->placa; ?></td>
                </tr>
                <tr>
                    <td colspan="2"><strong>CLIENTE:</strong> <?php echo $orden->cliente_nombre; ?></td>
                    <td class="text-right"><strong>VEHÍCULO:</strong> <?php echo $orden->modelo_vehiculo; ?></td>
                </tr>
            </table>
        </div>
        <table class="items-list">
            <?php foreach ($orden->items as $item): ?>
                <tr>
                    <td class="item-desc" width="80%"><?php echo $item->descripcion; ?></td>
                    <td class="item-price" width="20%">$ <?php echo number_format($item->subtotal_item, 2, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="work-footer">
            SUBTOTAL ORDEN: $ <?php echo number_format($orden->total_orden, 2, ',', '.'); ?>
        </div>
    </div>
<?php endforeach; else: ?>
    <p style="text-align:center; margin-top: 50px; color: #94a3b8; font-style: italic;">No hay registros para mostrar en el rango seleccionado.</p>
<?php endif; ?>

<div class="grand-total-box">
    <table width="100%">
        <tr>
            <td style="text-align: left; vertical-align: middle;">
                <span style="font-weight: bold; text-transform: uppercase; font-size: 11px;">Consolidado Auditoría</span>
            </td>
            <td>
                <span class="grand-total-label">Inversión / Recaudo Total</span>
                <span class="grand-total-amount">$ <?php echo number_format($totalGeneral, 2, ',', '.'); ?></span>
            </td>
        </tr>
    </table>
</div>