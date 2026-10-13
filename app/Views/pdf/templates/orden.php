<?php
    $meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
    $fecha_dt = strtotime($orden->fecha_ingreso);
    $fecha_elegante = date('d', $fecha_dt) . " de " . $meses[date('n', $fecha_dt)-1] . " del " . date('Y', $fecha_dt);
    $hora_elegante = date('h:i A', $fecha_dt);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>PDF - ORDEN DE SERVICIO #<?php echo $orden->id; ?> - <?php echo strtoupper($orden->placa); ?></title>
    <style>
    @page {
        margin: 15px 20px 15px 20px;
    }

    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #1e293b;
        font-size: 10px;
        line-height: 1.5;
        background: #ffffff;
        padding: 0;
        margin: 0;
    }

    /* ===== CONTENEDOR PRINCIPAL CON MÁRGENES ===== */
    .page-content {
        width: 100%;
        box-sizing: border-box;
        position: relative;
        min-height: 100vh;
        padding-bottom: 100px;
        /* Espacio para firmas y footer */
    }

    /* ===== CABECERA ===== */
    .header-table {
        width: 100%;
        border-bottom: 2px solid #0f172a;
        margin: 0 0 8px 0;
        padding: 0;
        border-collapse: collapse;
    }

    .company-data {
        width: 50%;
        vertical-align: top;
        text-align: left;
    }

    .invoice-data {
        width: 50%;
        vertical-align: top;
        text-align: right;
    }

    .doc-type {
        font-size: 16px;
        font-weight: 900;
        letter-spacing: 0.5px;
        color: #0f172a;
    }

    .label-min {
        font-size: 8px;
        color: #6b7280;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    .val-text {
        font-size: 11px;
        font-weight: 700;
        color: #111827;
    }

    /* ===== SECCIONES ===== */
    .section-box {
        margin: 6px 0 8px 0;
        padding: 6px 4px;
        border-radius: 2px;
        background: #fafbfc;
    }

    .section-title {
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #1e40af;
        border-bottom: 1.5px solid #dbeafe;
        padding: 0 0 3px 0;
        margin: 0 0 6px 0;
        background: #f0f4ff;
        padding: 4px 8px;
        border-radius: 2px 2px 0 0;
    }

    /* ===== TABLA INFORMATIVA ===== */
    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
    }

    .info-table td {
        padding: 4px 6px;
        vertical-align: top;
    }

    .info-table .label-min {
        display: block;
        margin-bottom: 2px;
    }

    .info-table .val-text {
        display: inline-block;
        background: #f8fafc;
        padding: 2px 6px;
        border-radius: 2px;
    }

    /* ===== CHECKLIST ===== */
    .checklist-list {
        padding: 4px 6px;
        background: #ffffff;
        border-radius: 2px;
    }

    .checklist-item {
        padding: 3px 0;
        border-bottom: 1px dashed #e9edf2;
        font-size: 9px;
    }

    .checklist-item:last-child {
        border-bottom: none;
    }

    .checklist-item strong {
        color: #0f172a;
    }

    /* ===== TABLA DE SERVICIOS ===== */
    .servicios-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 3px;
        font-size: 9px;
        background: #ffffff;
        border-radius: 2px;
        overflow: hidden;
    }

    .servicios-table th {
        background: #eff6ff;
        border-bottom: 2px solid #3b82f6;
        padding: 6px 8px;
        text-align: left;
        font-size: 8px;
        text-transform: uppercase;
        color: #1e40af;
        letter-spacing: 0.3px;
        font-weight: 800;
    }

    .servicios-table td {
        padding: 6px 8px;
        border-bottom: 1px solid #eef2f6;
        font-size: 9px;
        vertical-align: middle;
    }

    .servicios-table tr:hover {
        background: #fafcff;
    }

    .servicios-table tr:last-child td {
        border-bottom: none;
    }

    /* ===== BADGES DE ESTADO ===== */
    .estado-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        min-width: 60px;
        text-align: center;
    }

    .estado-pendiente {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .estado-en_proceso {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }

    .estado-completado {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #6ee7b7;
    }

    .estado-cancelado {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }

    /* ===== DIAGNÓSTICO ===== */
    .diagnostico-box {
        border: 1px solid #e2e8f0;
        padding: 8px 10px;
        margin: 0;
        font-size: 9px;
        background: #ffffff;
        border-radius: 2px;
        min-height: 30px;
    }

    .diagnostico-box .label-min {
        display: block;
        margin-bottom: 6px;
        color: #475569;
        font-weight: 700;
    }

    /* ===== TABLA DE ÍTEMS ===== */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 3px;
        background: #ffffff;
        border-radius: 2px;
        overflow: hidden;
    }

    .items-table th {
        border-bottom: 2px solid #0f172a;
        padding: 6px 8px;
        text-align: left;
        font-size: 9px;
        text-transform: uppercase;
        color: #0f172a;
        background: #f1f5f9;
        font-weight: 800;
        letter-spacing: 0.3px;
    }

    .items-table td {
        padding: 6px 8px;
        border-bottom: 1px solid #eef2f6;
        font-size: 10px;
        vertical-align: middle;
    }

    .items-table tr:last-child td {
        border-bottom: none;
    }

    .items-table .text-right {
        text-align: right;
        font-weight: 600;
        color: #0f172a;
    }

    /* ===== FIRMAS ===== */
    .signature-section {
        position: absolute;
        bottom: 60px;
        left: 20px;
        right: 20px;
        padding-top: 10px;
        border-top: 1px solid #e2e8f0;
    }

    .signature-table {
        width: 100%;
        border-collapse: collapse;
    }

    .signature-table td {
        padding: 6px 0;
        vertical-align: bottom;
    }

    .signature-line {
        border-top: 1.5px solid #0f172a;
        padding-top: 8px;
        text-align: center;
        width: 40%;
        font-weight: 700;
        font-size: 9px;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    /* ===== FOOTER ===== */
    .main-footer {
        width: 100%;
        text-align: center;
        font-size: 8px;
        color: #94a3b8;
        border-top: 1px solid #e2e8f0;
        padding-top: 6px;
        margin-top: 12px;
    }

    /* ===== UTILIDADES ===== */
    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .text-uppercase {
        text-transform: uppercase;
    }

    .font-bold {
        font-weight: 700;
    }

    .color-primary {
        color: #0f172a;
    }

    .color-muted {
        color: #64748b;
    }

    .mt-1 {
        margin-top: 4px;
    }

    .mb-1 {
        margin-bottom: 4px;
    }

    .p-1 {
        padding: 4px;
    }

    /* ===== RESPONSIVE PARA IMPRESIÓN ===== */
    @media print {
        body {
            font-size: 10px;
            background: #ffffff;
        }

        .page-content {
            padding: 0;
        }

        .section-box {
            background: #ffffff;
            page-break-inside: avoid;
        }

        .servicios-table th,
        .items-table th {
            background: #f1f5f9 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .estado-badge {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
    </style>
</head>

<body>
    <!-- CONTENEDOR PRINCIPAL CON MÁRGENES -->
    <div class="page-content">

        <!-- CABECERA (INCLUIDA DESDE ARCHIVO EXTERNO) -->
        <?php if(file_exists(APPROOT . '/Views/pdf/inc/header.php')): ?>
        <?php 
                // Variables para la cabecera compartida
                $titulo_documento = 'ORDEN DE SERVICIO';
                $documento_numero = '#' . $orden->id;
                $fecha_documento = $fecha_elegante . ' - ' . $hora_elegante;
                $doc_color = "#3b82f6";
                $status_documento = $orden->estado;
                require_once APPROOT . '/Views/pdf/inc/header.php'; 
            ?>
        <?php else: ?>
        <div style="color:red; border:1px solid red; padding:10px;">Error: No se encontró la cabecera</div>
        <?php endif; ?>

        <!-- CUERPO PRINCIPAL -->
        <div>
            <div class="section-box">
                <div class="section-title">Datos del Vehículo y Cliente</div>
                <table width="100%" class="info-table">
                    <tr>
                        <td width="50%">
                            <span class="label-min">Marca y Modelo:</span><br>
                            <span class="val-text"><?php echo $orden->marca . ' ' . $orden->modelo; ?></span>
                        </td>
                        <td width="25%">
                            <span class="label-min">Placa:</span><br>
                            <span class="val-text"><?php echo $orden->placa; ?></span>
                        </td>
                        <td width="25%" style="text-align: right;">
                            <span class="label-min">Kilometraje:</span><br>
                            <span class="val-text"><?php echo number_format($orden->kilometraje); ?> KM</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top: 10px;">
                            <span class="label-min">Propietario:</span><br>
                            <span class="val-text"><?php echo $orden->cliente_nombre ?? 'N/A'; ?></span>
                        </td>
                        <td style="padding-top: 10px;">
                            <span class="label-min">Combustible:</span><br>
                            <span class="val-text"><?php echo $orden->nivel_combustible; ?></span>
                        </td>
                        <td></td>
                    </tr>
                </table>
            </div>

            <?php if(!empty($orden->checklist)): ?>
            <div class="section-box">
                <div class="section-title">Inventario de Recepción (Checklist)</div>
                <div class="section-content" style="padding-top: 2px; padding-bottom: 2px;">
                    <div class="checklist-list">
                        <?php foreach($orden->checklist as $chk): ?>
                        <div class="checklist-item">
                            <span style="color: #10b981; font-weight: bold;">[✓]</span>
                            <strong style="color: #0f172a;"><?php echo strtoupper($chk->item); ?></strong>
                            <?php if($chk->observacion): ?> — <span style="color: #64748b;">Obs:
                                <?php echo htmlspecialchars($chk->observacion); ?></span><?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if(!empty($orden->servicios)): ?>
            <div class="section-box">
                <div class="section-title">Servicios / Revisiones Programados</div>
                <table class="servicios-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="70%">Descripción del Servicio / Revisión</th>
                            <th width="25%">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orden->servicios as $index => $serv): ?>
                        <tr>
                            <td style="text-align: center; font-weight: bold;"><?php echo $index + 1; ?></td>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($serv->descripcion); ?></td>
                            <td style="text-align: center;">
                                <span class="estado-badge estado-<?php echo strtolower($serv->estado); ?>">
                                    <?php echo $serv->estado; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="section-box">
                <div class="section-title">Motivo de Ingreso y Diagnóstico Técnico</div>
                <div class="diagnostico-box" style="min-height: 10px;">
                    <div class="label-min" style="margin-bottom: 5px;">OBSERVACIÓN DE ENTRADA:</div>
                    <?php echo nl2br(htmlspecialchars($orden->observaciones_entrada)); ?>
                </div>
            </div>

            <?php if(!empty($orden->items)): ?>
            <div class="section-box">
                <div class="section-title">Repuestos y Servicios Requeridos</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th width="70%">Descripción</th>
                            <th width="10%" style="text-align: center;">Cant.</th>
                            <th width="20%" class="text-right">Precio Ref.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orden->items as $item): ?>
                        <tr>
                            <td style="text-transform: uppercase;"><?php echo $item->descripcion ?? $item->nombre; ?>
                            </td>
                            <td style="text-align: center;"><?php echo $item->cantidad; ?></td>
                            <td class="text-right">$
                                <?php echo number_format($item->precio_unitario ?? $item->precio, 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="signature-section">
                <table class="signature-table">
                    <tr>
                        <td class="signature-line">Firma del Técnico Responsable</td>
                        <td width="20%"></td>
                        <td class="signature-line">Firma de Conformidad Cliente</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="main-footer">
            Taller Pro 2.0 - Gestión Inteligente | Generado el: <?php echo date('d/m/Y h:i A'); ?> | Soporte: @tallerpro
        </div>

    </div>
    <!-- FIN CONTENEDOR PRINCIPAL -->
</body>

</html>