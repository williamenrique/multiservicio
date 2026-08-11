<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo mb_strtoupper($titulo_pestaña ?? 'AUDITORIA', 'UTF-8'); ?></title>
<style>
    @page { margin: 18px 20px 28px; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 10px; line-height: 1.4; background-color: #fff; }
    
    .hero { border-bottom: 3px solid #f59e0b; padding: 10px 0 20px; margin-bottom: 20px; }
    .big { font-size: 22px; font-weight: 900; color: #0f172a; text-transform: uppercase; }
    .muted { color: #94a3b8; font-size: 9px; }
    
    .work-block { margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; page-break-inside: avoid; }
    .work-header { background-color: #f8fafc; padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
    .work-info { width: 100%; font-size: 10px; }
    .work-title { font-weight: 900; color: #0f172a; font-size: 11px; text-transform: uppercase; }
    
    .items-list { width: 100%; border-collapse: collapse; }
    .items-list td { padding: 6px 12px; font-size: 9.5px; color: #475569; border-bottom: 1px solid #f1f5f9; }
    .item-desc { text-transform: uppercase; font-weight: 500; }
    .item-price { text-align: right; font-weight: bold; }
    
    .work-footer { padding: 8px 12px; background-color: #fff; text-align: right; font-size: 10px; font-weight: 900; color: #0f172a; border-top: 1px solid #f1f5f9; }
    
    .grand-total-card { margin-top: 30px; padding: 20px; background: #0f172a; color: white; border-radius: 12px; text-align: right; border-left: 6px solid #f59e0b; }
    .stat-label { font-size: 8px; color: #94a3b8; text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em; margin-bottom: 5px; }
    .stat-value { font-size: 22px; font-weight: 900; color: #fbbf24; }
    
    .label-min { font-size: 7px; color: #64748b; text-transform: uppercase; font-weight: 700; }
    .val-text { font-size: 9px; font-weight: 800; color: #0f172a; }
</style>
</head>
<body>
<div style="padding: 0;">
    <?php if(file_exists(APPROOT . '/Views/pdf/inc/header.php')): ?>
        <?php require_once APPROOT . '/Views/pdf/inc/header.php'; ?>
    <?php endif; ?>

    <div class="hero">
        <div class="big">AUDITORIA OPERATIVA DE TRABAJOS</div>
        <div class="muted">Listado detallado de servicios y productos facturados en el taller.</div>
    </div>

    <div style="margin-bottom: 20px;">
        <table width="100%">
            <tr>
                <td width="50%">
                    <div class="label-min">Desde</div>
                    <div class="val-text"><?php echo date('d/m/Y', strtotime($desde)); ?></div>
                </td>
                <td width="50%" style="text-align: right;">
                    <div class="label-min">Hasta</div>
                    <div class="val-text"><?php echo date('d/m/Y', strtotime($hasta)); ?></div>
                </td>
            </tr>
        </table>
    </div>

<?php 
$totalGeneral = 0;
if(!empty($ventas)):
    // Agrupación manual de items por Orden/Venta
    $ventasAgrupadas = [];
    foreach ($ventas as $v) {
        if (!isset($ventasAgrupadas[$v->id])) {
            $ventasAgrupadas[$v->id] = (object)[
                'id' => $v->id,
                'fecha' => $v->fecha,
                'orden_id' => $v->orden_id ?? null,
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
                    <td class="work-title" width="30%">
                        <?php echo $orden->orden_id ? "ORDEN #$orden->orden_id" : "VENTA #$orden->id"; ?>
                    </td>
                    <td width="35%"><strong>EMISION:</strong> <?php echo date('d/m/Y', strtotime($orden->fecha)); ?></td>
                    <td width="35%" class="text-right"><strong>PLACA:</strong> <?php echo $orden->placa; ?></td>
                </tr>
                <tr>
                    <td colspan="2"><strong>CLIENTE:</strong> <?php echo $orden->cliente_nombre; ?></td>
                    <td class="text-right"><strong>VEHICULO:</strong> <?php echo $orden->modelo_vehiculo; ?></td>
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

<div class="grand-total-card">
    <table width="100%">
        <tr>
            <td style="text-align: left; vertical-align: middle;">
                <div class="stat-label">Estado de Auditoria</div>
                <div style="font-size: 12px; font-weight: 800;">REPORTE CONSOLIDADO</div>
            </td>
            <td>
                <div class="stat-label">Inversión / Recaudo Total</div>
                <div class="stat-value">$ <?php echo number_format($totalGeneral, 2, ',', '.'); ?></div>
            </td>
        </tr>
    </table>
</div>

<div style="margin-top: 30px; text-align: center; color: #94a3b8; font-size: 8px;">
    Documento generado electronicamente para fines de auditoria tecnica y financiera.<br>
    Sistema Taller Pro 2.0 - <?php echo date('d/m/Y h:i A'); ?>
</div>
</div>
</body>
</html>