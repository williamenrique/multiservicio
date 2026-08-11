<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo mb_strtoupper($titulo_pestaña ?? '', 'UTF-8'); ?></title>
<style>
    @page { margin: 18px 20px 28px; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 11px; line-height: 1.6; }
    
    .hero { background: #0f172a; color: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; border-bottom: 5px solid #3b82f6; }
    .big { font-size: 22px; font-weight: 900; color: #fff; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.02em; }
    
    .section-box { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; margin-bottom: 15px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    .section-title { background: #f8fafc; color: #475569; padding: 8px 14px; font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; border-bottom: 1px solid #e2e8f0; }
    .section-content { padding: 15px; }
    
    .badge { display: inline-block; padding: 5px 12px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
    .badge-entrada { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .badge-salida { background: #fee2e2; color: #991b1b; border: 1px solid #fecdd3; }
    
    .grid-2 { width: 100%; border-collapse: collapse; }
    .grid-2 td { vertical-align: top; padding: 8px 0; }
    
    .label-min { font-size: 8px; color: #94a3b8; text-transform: uppercase; font-weight: 800; letter-spacing: 0.1em; margin-bottom: 2px; }
    .value-text { font-size: 12px; color: #0f172a; font-weight: 700; }
    .muted { color: #94a3b8; font-size: 9px; }
    .highlight { font-size: 20px; font-weight: 900; color: #0f172a; }
    .chip { display: inline-block; background: #3b82f6; color: #fff; border-radius: 6px; padding: 5px 12px; font-size: 11px; font-weight: 800; }
</style>
</head>
<body>

<div style="padding: 0;">
    <div class="hero">
        <div class="big"><?php echo mb_strtoupper($titulo_documento ?? '', 'UTF-8'); ?></div>
        <div style="font-size: 10px; opacity: 0.8;">Certificado de auditoria de inventario - Registro electronico oficial de trazabilidad.</div>
    </div>

    <div class="section-box">
        <div class="section-title">Informacion Principal</div>
        <div class="section-content">
            <table class="grid-2" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="width: 58%;">
                        <div class="label-min">Producto</div>
                        <div class="highlight"><?php echo s($mov->producto_nombre); ?></div>
                        <div class="muted">Categoria: <?php echo s($mov->categoria ?: 'Sin categoria'); ?></div>
                    </td>
                    <td style="width: 42%; text-align: right;">
                        <div class="label-min">ID Operacion</div>
                        <div class="chip"># <?php echo str_pad($mov->id, 6, '0', STR_PAD_LEFT); ?></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section-box">
        <div class="section-title">Detalle Operativo</div>
        <div class="section-content">
            <table class="grid-2" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="width: 50%;">
                        <div class="label-min">Tipo de Operacion</div>
                        <div class="value-text">
                            <span class="badge <?php echo strpos($mov->tipo_movimiento, 'ENTRADA') !== false ? 'badge-entrada' : 'badge-salida'; ?>">
                                <?php echo str_replace('_', ' ', $mov->tipo_movimiento); ?>
                            </span>
                        </div>
                    </td>
                    <td style="width: 50%;">
                        <div class="label-min">Cantidad Operada</div>
                        <div class="value-text" style="font-size: 16px; color: #0f172a;"><?php echo number_format($mov->cantidad); ?> Unidades</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="label-min">Stock Anterior</div>
                        <div class="value-text"><?php echo number_format($mov->stock_anterior); ?></div>
                    </td>
                    <td>
                        <div class="label-min">Stock Resultante</div>
                        <div class="value-text" style="color: #3b82f6; font-size: 14px;"><?php echo number_format($mov->stock_actual); ?></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="label-min">Referencia</div>
                        <div class="value-text">#<?php echo $mov->referencia_id ?: 'N/A'; ?></div>
                    </td>
                    <td>
                        <div class="label-min">Responsable</div>
                        <div class="value-text" style="text-transform: uppercase;"><?php echo s($mov->usuario_nombre ?: $mov->username ?: 'Sistema'); ?></div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                         <div class="label-min">Fecha y Hora del Registro</div>
                         <div class="value-text"><?php echo date('d/m/Y h:i:s A', strtotime($mov->fecha)); ?></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <?php
        $observacion = trim((string)($mov->observacion ?? $mov->observaciones ?? ''));
        $textoObservacion = $observacion !== '' ? $observacion : 'Sin observaciones adicionales registradas.';
    ?>
    <div class="section-box">
        <div class="section-title">Bitácora / Comentarios</div>
        <div class="section-content" style="color: #475569; font-style: italic;">
            <?php echo nl2br(s($textoObservacion)); ?>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; color: #94a3b8; font-size: 9px;">
        Este documento es un comprobante oficial generado por el sistema <strong>Taller Pro 2.0</strong>.<br>
        Fecha de generacion: <?php echo date('d/m/Y h:i A'); ?>
    </div>
</div>
</body>
</html>