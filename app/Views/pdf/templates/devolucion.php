<?php
    // Plantilla PDF para Devolución de Repuestos
    // Recibe vía extract($data): $devolucion (objeto con cabecera), $empresa (datos empresa)

    $d = $devolucion;

    $meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
    $fecha_dt = strtotime($d->fecha);
    $fecha_elegante = date('d', $fecha_dt) . " de " . $meses[date('n', $fecha_dt)-1] . " del " . date('Y', $fecha_dt);

    // Objeto venta simulado para compatibilidad con header.php
    $venta = new stdClass();
    $venta->id_formateado = 'DEV-' . str_pad($d->id, 4, '0', STR_PAD_LEFT);
    $venta->fecha = $d->fecha;
    $venta->cliente_nombre = $d->cliente ?? 'N/A';
    $venta->cliente_id = $d->cliente_id ?? '';
    $venta->cliente_telefono = $d->cliente_telefono ?? 'N/A';
    $venta->placa = $d->placa ?? '';
    $venta->modelo_vehiculo = $d->modelo_vehiculo ?? '';
    $venta->marca_vehiculo = $d->marca_vehiculo ?? '';
    $venta->vendedor_nombre = $d->usuario_nombre ?? '';
    $venta->mecanico_nombre = '';
    $venta->observaciones = $d->motivo ?? '';

    $titulo_pestaña = "PDF - DEVOLUCIÓN " . $venta->id_formateado;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo strtoupper($titulo_pestaña); ?></title>
    <style>
    @page {
        margin: 15px 20px;
    }

    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #1e293b;
        font-size: 10px;
        line-height: 1.5;
    }

    /* Estilos de Bloques */
    .header-table {
        width: 100%;
        border-bottom: 2px solid #0f172a;
        margin-bottom: 8px;
        padding-bottom: 5px;
    }

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
        padding: 4px 8px;
        margin: 0 0 6px 0;
        background: #f0f4ff;
        border-radius: 2px 2px 0 0;
    }

    .section-content {
        padding: 6px;
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

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 5px;
    }

    .items-table th {
        background: #0f172a;
        color: white;
        padding: 5px;
        text-align: left;
        font-size: 8px;
        text-transform: uppercase;
    }

    .items-table td {
        padding: 6px 5px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 10px;
    }

    .obs-box {
        background: #f8fafc;
        padding: 8px;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        font-size: 9px;
        line-height: 1.4;
        color: #334155;
    }

    /* Badge devolución */
    .badge-devolucion {
        display: inline-block;
        background: #e11d48;
        color: #fff;
        padding: 3px 10px;
        border-radius: 3px;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .badge-stock {
        display: inline-block;
        background: #10b981;
        color: #fff;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 9px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .badge-danado {
        display: inline-block;
        background: #b91c1c;
        color: #fff;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 9px;
        font-weight: 900;
        text-transform: uppercase;
    }

    /* Motivo destacado */
    .motivo-box {
        background: #fef2f2;
        border: 1px solid #e11d48;
        border-left: 4px solid #e11d48;
        padding: 8px 10px;
        border-radius: 4px;
        font-size: 10px;
        line-height: 1.4;
        color: #7f1d1d;
    }

    /* Totales */
    .total-label {
        font-weight: bold;
        color: #4b5563;
        text-transform: uppercase;
        font-size: 9px;
        margin-right: 10px;
    }

    .total-val {
        font-weight: bold;
        font-size: 11px;
    }

    .grand-total {
        font-size: 14px;
        border-top: 2px solid #0f172a;
        padding-top: 4px;
        color: #0f172a;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    /* Badges pequeños en tabla */
    .pill {
        display: inline-block;
        padding: 1px 6px;
        border-radius: 8px;
        font-size: 8px;
        font-weight: 900;
        text-transform: uppercase;
        color: #fff;
    }

    .pill-repuesto {
        background: #3b82f6;
    }

    .pill-stock {
        background: #10b981;
    }

    .pill-danado {
        background: #b91c1c;
    }
    </style>
</head>

<body>
    <!-- Inclusión de Cabecera Compartida -->
    <?php if(file_exists(APPROOT . '/Views/pdf/inc/header.php')): ?>
    <?php
            $titulo_documento = 'DEVOLUCIÓN DE REPUESTO';
            $documento_numero = $venta->id_formateado;
            $fecha_documento  = $fecha_elegante;
            $status_documento = 'COMPLETADO';
            $doc_color = '#e11d48';
            require APPROOT . '/Views/pdf/inc/header.php';
        ?>
    <?php endif; ?>

    <!-- Banner de Devolución -->
    <div style="text-align: center; margin-bottom: 10px;">
        <span class="badge-devolucion">DOCUMENTO DE DEVOLUCIÓN</span>
        <span style="margin-left: 8px; font-size: 8px; color: #64748b;">Devolución de repuesto con validación de
            garantía</span>
    </div>

    <!-- Referencia a factura original -->
    <div class="section-box" style="border-color: #3b82f6; background: #eff6ff;">
        <div class="section-title" style="background: #dbeafe; color: #1e40af; border-bottom-color: #bfdbfe;">Factura
            Original</div>
        <div class="section-content" style="background: #eff6ff;">
            <table width="100%">
                <tr>
                    <td width="50%">
                        <div class="label-min">Factura Original</div>
                        <div class="val-text" style="color: #1e40af;">#
                            <?php echo str_pad($d->factura_id, 4, '0', STR_PAD_LEFT); ?></div>
                    </td>
                    <td width="50%" style="text-align: right;">
                        <div class="label-min">Fecha Factura</div>
                        <div class="val-text"><?php echo date('d/m/Y', strtotime($d->fecha_factura)); ?></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Datos del Cliente y Vehículo -->
    <div class="section-box">
        <div class="section-title">Datos del Cliente y Vehículo</div>
        <div class="section-content">
            <table width="100%">
                <tr>
                    <td width="50%">
                        <div class="label-min">Propietario / Cliente</div>
                        <div class="val-text"><?php echo strtoupper($d->cliente ?? 'N/A'); ?></div>
                        <div style="font-size: 8px; color: #64748b;">CÉDULA:
                            <?php echo strtoupper($d->cliente_id ?? ''); ?> | TEL:
                            <?php echo strtoupper($d->cliente_telefono ?? 'N/A'); ?></div>
                    </td>
                    <td width="50%" style="text-align: right;">
                        <div class="label-min">Vehículo</div>
                        <div class="val-text">
                            <?php echo strtoupper(trim(($d->marca_vehiculo ?? '') . ' ' . ($d->modelo_vehiculo ?? ''))); ?>
                        </div>
                        <div class="val-text" style="color: #3b82f6;">PLACA:
                            <?php echo strtoupper($d->placa ?? 'N/A'); ?></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Motivo de la devolución -->
    <div class="section-box">
        <div class="section-title" style="background: #fef2f2; color: #7f1d1d; border-bottom-color: #fecaca;">Motivo de
            la Devolución</div>
        <div class="section-content">
            <div class="motivo-box">
                <strong style="font-size: 8px; letter-spacing: .1em;">MOTIVO:</strong><br>
                <?php echo nl2br(htmlspecialchars(strtoupper($d->motivo ?? ''))); ?>
            </div>
            <table width="100%" style="margin-top: 6px;">
                <tr>
                    <td width="33%">
                        <div class="label-min">Destino</div>
                        <div class="val-text">
                            <?php if ($d->destino === 'STOCK'): ?>
                            <span class="badge-stock">REINGRESADO A STOCK</span>
                            <?php else: ?>
                            <span class="badge-danado">MARCADO COMO DAÑADO</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td width="33%">
                        <div class="label-min">Días Garantía Aplicada</div>
                        <div class="val-text"><?php echo $d->dias_garantia_aplicado ?? 0; ?> días</div>
                    </td>
                    <td width="33%" style="text-align: right;">
                        <div class="label-min">Días Transcurridos</div>
                        <div class="val-text"><?php echo $d->dias_transcurridos ?? 0; ?> días</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Detalle del item devuelto -->
    <div class="section-title" style="background: none; border: none; padding-left: 0; margin-bottom: 2px;">Detalle del
        Ítem Devuelto</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="40%">Descripción</th>
                <th width="10%">Tipo</th>
                <th width="10%" class="text-center">Cant.</th>
                <th width="15%" class="text-center">Destino</th>
                <th width="12%" class="text-right">Unitario</th>
                <th width="13%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-transform: uppercase;"><?php echo strtoupper($d->descripcion); ?></td>
                <td class="text-center"><span class="pill pill-repuesto">REPUESTO</span></td>
                <td class="text-center" style="font-weight: bold;">x<?php echo $d->cantidad; ?></td>
                <td class="text-center">
                    <?php if ($d->destino === 'STOCK'): ?>
                    <span class="pill pill-stock">STOCK</span>
                    <?php else: ?>
                    <span class="pill pill-danado">DAÑADO</span>
                    <?php endif; ?>
                </td>
                <td class="text-right">$ <?php echo number_format($d->precio_unitario ?? 0, 2); ?></td>
                <td class="text-right" style="font-weight: bold;">$
                    <?php echo number_format($d->monto_devuelto ?? 0, 2); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Totales -->
    <table width="100%" style="margin-top: 10px;">
        <tr>
            <td width="55%" style="vertical-align: top; padding-right: 15px;">
                <div class="label-min" style="margin-bottom: 4px;">Notas:</div>
                <div class="obs-box">
                    <strong style="color: #0f172a;">DEVOLUCIÓN PROCESADA SOBRE FACTURA
                        #<?php echo str_pad($d->factura_id, 4, '0', STR_PAD_LEFT); ?>.</strong><br>
                    <?php if ($d->destino === 'STOCK'): ?>
                    El repuesto ha sido reingresado al inventario y se ha registrado el movimiento en el Kardex (tipo
                    DEVOLUCION).
                    <?php else: ?>
                    El repuesto ha sido marcado como DAÑADO y NO se reingresa al inventario. Se descarta físicamente.
                    <?php endif; ?>
                    <?php if (!empty($d->motivo)): ?>
                    <br><br><strong style="color: #0f172a;">MOTIVO:</strong> <?php echo strtoupper($d->motivo); ?>.
                    <?php endif; ?>
                </div>
            </td>
            <td width="45%" style="vertical-align: top;">
                <table width="100%" style="border-collapse: collapse;">
                    <tr>
                        <td class="total-label">Monto Base:</td>
                        <td class="total-val text-right">$
                            <?php echo number_format(($d->monto_devuelto ?? 0) / (1 + (($d->iva_monto ?? 0) / max(1, ($d->subtotal ?? 1)))), 2); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="total-label">IVA Devuelto:</td>
                        <td class="total-val text-right">$ <?php echo number_format($d->iva_monto ?? 0, 2); ?></td>
                    </tr>
                    <tr>
                        <td class="total-label grand-total">TOTAL DEVUELTO:</td>
                        <td class="total-val grand-total text-right">$
                            <?php echo number_format($d->monto_devuelto ?? 0, 2); ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Firmas -->
    <table width="100%" style="margin-top: 40px;">
        <tr>
            <td width="50%" style="text-align: center;">
                <div
                    style="border-top: 1px solid #0f172a; padding-top: 4px; margin: 0 30px; font-size: 8px; font-weight: bold; text-transform: uppercase;">
                    Cliente</div>
            </td>
            <td width="50%" style="text-align: center;">
                <div
                    style="border-top: 1px solid #0f172a; padding-top: 4px; margin: 0 30px; font-size: 8px; font-weight: bold; text-transform: uppercase;">
                    Autorizado por: <?php echo strtoupper($d->usuario_nombre ?? ''); ?></div>
            </td>
        </tr>
    </table>

    <div
        style="margin-top: 20px; border-top: 1px solid #cbd5e1; padding-top: 8px; text-align: center; color: #64748b; font-size: 7.5px;">
        Documento de devolución generado por <strong>Taller Pro 2.0</strong> | La factura original ha sido ajustada
        restando el ítem devuelto.
    </div>
</body>

</html>