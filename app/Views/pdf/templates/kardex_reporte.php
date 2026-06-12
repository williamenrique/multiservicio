<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo mb_strtoupper($titulo_pestaña ?? '', 'UTF-8'); ?></title>
<style>
    @page { margin: 18px 20px 28px; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 11px; line-height: 1.5; background-color: #fff; }
    
    /* Contenedores */
    .section-box { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; margin-bottom: 15px; }
    .section-title { background: #f8fafc; color: #0f172a; padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; border-bottom: 1px solid #e2e8f0; }
    .section-content { padding: 10px; }
    
    /* Badges refinados */
    .badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 7.5px; font-weight: 700; text-transform: uppercase; }
    .badge-entrada { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .badge-salida { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }
    
    /* Tipografía y Layout */
    .grid-2 { width: 100%; border-collapse: collapse; }
    .grid-2 td { vertical-align: top; padding: 4px 0; }
    .label-min { font-size: 8px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 2px; }
    .value-text { font-size: 10px; color: #111827; font-weight: 700; }
    .muted { color: #94a3b8; font-size: 8px; }
    .highlight { font-size: 18px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
    
    /* Tarjetas de Resumen */
    .summary-card { background: #f1f5f9; border-radius: 8px; padding: 10px; border-left: 4px solid #0f172a; }
    .hero { border-bottom: 2px solid #0f172a; padding: 10px 0 20px; margin-bottom: 20px; }
    .big { font-size: 22px; font-weight: 900; color: #0f172a; text-transform: uppercase; }
    
    /* Tabla de Items */
    .items-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    .items-table th { background: #0f172a; color: #fff; text-align: left; padding: 10px 12px; font-size: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
    .items-table td { border-bottom: 1px solid #f1f5f9; padding: 10px 12px; color: #334155; vertical-align: middle; }
    .items-table tr:nth-child(even) { background-color: #f8fafc; }
</style>
</head>
<body>
<div style="padding: 0;">
    <div class="hero">
        <div class="label-min" style="color: #0f172a;">Reporte de Inventario</div>
        <div class="big"><?php echo mb_strtoupper($titulo_documento ?? '', 'UTF-8'); ?></div>
        <div class="muted">Documento de trazabilidad de movimientos y control de existencias actualizado.</div>
    </div>

    <div style="margin-bottom: 25px;">
            <table class="grid-2" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="width: 60%;">
                        <div class="highlight"><?php echo s($producto->nombre); ?></div>
                        <div class="muted">Categoria: <?php echo s($producto->categoria ?: 'Sin categoria'); ?></div>
                    </td>
                    <td style="width: 42%; text-align: right;">
                        <div class="summary-card" style="display: inline-block; min-width: 140px; text-align: left;">
                            <div class="label-min">Movimientos registrados</div>
                            <div class="highlight"><?php echo count($movimientos); ?></div>
                            <div class="muted">Stock actual estimado: <?php echo number_format(!empty($movimientos) ? $movimientos[0]->stock_actual : 0); ?> und.</div>
                        </div>
                    </td>
                </tr>
            </table>
    </div>

    <div class="section-box">
        <div class="section-title">Historial Cronologico de Movimientos</div>
        <div class="section-content">
            <table class="items-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th width="8%">#</th>
                        <th width="18%">Fecha</th>
                        <th width="22%">Operacion</th>
                        <th width="10%">Cant.</th>
                        <th width="12%" style="text-align: right;">Anterior</th>
                        <th width="12%" style="text-align: right;">Actual</th>
                        <th width="12%" style="text-align: right;">Ref.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $m): ?>
                    <tr>
                        <td><?php echo $m->id; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($m->fecha)); ?></td>
                        <td>
                            <span class="badge <?php echo strpos($m->tipo_movimiento, 'ENTRADA') !== false ? 'badge-entrada' : 'badge-salida'; ?>">
                                <?php echo str_replace('_', ' ', $m->tipo_movimiento); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($m->cantidad); ?></td>
                        <td style="text-align: right; color: #64748b;"><?php echo number_format($m->stock_anterior); ?></td>
                        <td style="text-align: right; font-weight: 700;"><?php echo number_format($m->stock_actual); ?></td>
                        <td style="text-align: right;">#<?php echo $m->referencia_id ?: 'N/A'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 20px; text-align: center; color: #94a3b8; font-size: 8px;">
        Este reporte es un documento contable generado por el sistema Taller Pro 2.0.<br>
        Fecha de generacion: <?php echo date('d/m/Y h:i A'); ?>
    </div>
</div>
</body>
</html>