<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo mb_strtoupper($titulo_pestaña ?? 'REPORTE', 'UTF-8'); ?></title>
<style>
    @page { margin: 18px 20px 28px; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 11px; line-height: 1.5; background-color: #fff; }
    
    /* Encabezado Principal */
    .hero { border-bottom: 3px solid #e11d48; padding: 10px 0 20px; margin-bottom: 20px; }
    .big { font-size: 24px; font-weight: 900; color: #0f172a; text-transform: uppercase; letter-spacing: -0.01em; }
    
    .label-min { font-size: 8px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.1em; margin-bottom: 2px; }
    .muted { color: #94a3b8; font-size: 9px; }
    
    /* Tarjeta de Resumen Consolidado */
    .summary-card { background: #0f172a; border-radius: 12px; padding: 20px; color: white; margin-bottom: 25px; border-left: 6px solid #e11d48; }
    .stat-label { font-size: 8px; color: #94a3b8; text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em; margin-bottom: 4px; }
    .stat-value { font-size: 22px; font-weight: 900; color: #fecaca; }
    
    /* Contenedores de Sección */
    .section-box { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; margin-bottom: 15px; }
    .section-title { background: #f8fafc; color: #0f172a; padding: 10px 14px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; border-bottom: 1px solid #e2e8f0; }
    .section-content { padding: 0; } /* La tabla ocupa todo el espacio */
    
    /* Tabla de Egresos */
    .items-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .items-table th { background: #0f172a; color: #fff; text-align: left; padding: 12px 10px; font-size: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
    .items-table td { border-bottom: 1px solid #f1f5f9; padding: 12px 10px; color: #334155; vertical-align: middle; }
    .items-table tr:nth-child(even) { background-color: #f8fafc; }
    
    .text-right { text-align: right; }
    .font-bold { font-weight: 900; }
    
    /* Badges de Estado */
    .badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 7.5px; font-weight: 700; text-transform: uppercase; }
    .badge-egreso { background: #fff1f2; color: #e11d48; border: 1px solid #fecaca; }
</style>
</head>
<body>
<div style="padding: 0;">
    <?php if(file_exists(APPROOT . '/Views/pdf/inc/header.php')): ?>
        <?php require_once APPROOT . '/Views/pdf/inc/header.php'; ?>
    <?php endif; ?>

    <div class="hero">
        <div class="label-min" style="color: #e11d48;">Contabilidad y Egresos</div>
        <div class="big"><?php echo mb_strtoupper($titulo_documento ?? '', 'UTF-8'); ?></div>
        <div class="muted">Periodo de consulta: <?php echo date('d/m/Y', strtotime($desde)); ?> al <?php echo date('d/m/Y', strtotime($hasta)); ?></div>
    </div>

    <div class="summary-card">
        <table width="100%">
            <tr>
                <td width="60%">
                    <div class="stat-label">Total Egresos Consolidados</div>
                    <div class="stat-value">$ <?php echo number_format($totales['egresos'] ?? 0, 2, ',', '.'); ?></div>
                    <div style="font-size: 8px; color: #94a3b8; margin-top: 5px;">Incluye Gastos Operativos, Compras a Proveedores y Nomina.</div>
                </td>
                <td width="40%" class="text-right" style="vertical-align: middle;">
                    <div class="stat-label">Estado del Reporte</div>
                    <div style="font-size: 12px; font-weight: 900; color: #fff; margin-top: 4px;">EGRESO CONSOLIDADO</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-box">
        <div class="section-title">Detalle Cronologico de Egresos</div>
        <div class="section-content">
            <table class="items-table">
                <thead>
                    <tr>
                        <th width="15%">Fecha</th>
                        <th width="12%">Tipo</th>
                        <th width="20%">Categoria</th>
                        <th width="33%">Descripcion</th>
                        <th width="20%" class="text-right">Monto Pagado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($gastos)): ?>
                        <?php foreach ($gastos as $g): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($g->fecha)); ?></td>
                                <td><span class="badge badge-egreso"><?php echo $g->tipo; ?></span></td>
                                <td class="font-bold"><?php echo $g->categoria_label ?? $g->categoria; ?></td>
                                <td style="font-size: 9px; color: #64748b;"><?php echo $g->descripcion ?? '---'; ?></td>
                                <td class="text-right font-bold" style="color: #e11d48;">$ <?php echo number_format($g->monto_pagado, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 25px; color: #94a3b8; font-style: italic;">No hay egresos registrados en este periodo.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="background-color: #fff1f2;">
                        <td colspan="4" class="text-right font-bold" style="padding: 15px; color: #e11d48; text-transform: uppercase; font-size: 9px;">Total Egresos en el Periodo:</td>
                        <td class="text-right font-bold" style="padding: 15px; font-size: 14px; color: #e11d48;">$ <?php echo number_format($totales['egresos'] ?? 0, 2, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; color: #94a3b8; font-size: 9px;">
        Este reporte es un documento contable oficial generado por el sistema <strong>Taller Pro 2.0</strong>.<br>
        Fecha de generacion: <?php echo date('d/m/Y h:i A'); ?>
    </div>
</div>
</body>
</html>