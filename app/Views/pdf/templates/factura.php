<?php
    // Detección de tipo de documento
    $es_os = !empty($venta->orden_id);
    $es_taller = !empty($venta->placa);

    // Determinación inteligente del tipo de factura
    if ($es_os) {
        $tipo_doc_label = "FACTURA DE ORDEN DE SERVICIO";
        $doc_color = "#3b82f6"; // Azul
        $id_doc_label = "OS #".$venta->orden_id;
    } elseif ($es_taller) {
        $tipo_doc_label = "FACTURA DE TALLER";
        $doc_color = "#10b981"; // Verde
        $id_doc_label = "PLACA: ".$venta->placa;
    } else {
        $tipo_doc_label = "VENTA DE REPUESTOS";
        $doc_color = "#6366f1"; // Índigo
        $id_doc_label = "MOSTRADOR";
    }

    $meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
    $fecha_dt = strtotime($venta->fecha);
    $fecha_elegante = date('d', $fecha_dt) . " de " . $meses[date('n', $fecha_dt)-1] . " del " . date('Y', $fecha_dt);
    
    $titulo_pestaña = "PDF - " . $tipo_doc_label . " " . ($venta->id_formateado ?: "#" . $venta->id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo strtoupper($titulo_pestaña); ?></title>
<style>
    @page { margin: 20px 25px; }
    body { font-family: 'Helvetica', sans-serif; color: #1e293b; font-size: 8.5px; line-height: 1.2; }
    
    /* Estilos de Bloques */
    .header-table { width: 100%; border-bottom: 3px solid #0f172a; margin-bottom: 15px; padding-bottom: 10px; }
    .section-box { margin-bottom: 10px; border: 1px solid #cbd5e1; border-radius: 4px; overflow: hidden; }
    .section-title { background: #f1f5f9; padding: 4px 8px; font-size: 7.5px; font-weight: 900; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; color: #334155; }
    .section-content { padding: 8px; }

    .label-min { font-size: 7px; color: #64748b; text-transform: uppercase; font-weight: bold; margin-bottom: 1px; line-height: 1; }
    .val-text { font-size: 9px; font-weight: bold; color: #0f172a; text-transform: uppercase; line-height: 1.2; }

    .items-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
    .items-table th { background: #0f172a; color: white; padding: 5px; text-align: left; font-size: 7.5px; text-transform: uppercase; }
    .items-table td { padding: 6px 5px; border-bottom: 1px solid #e2e8f0; font-size: 8.5px; }

    .checklist-list { margin: 0; padding: 0; list-style: none; }
    .checklist-item { display: block; width: 100%; font-size: 8px; padding: 2px 0; border-bottom: 1px dotted #e2e8f0; }
    .checklist-item:last-child { border-bottom: none; }

    .obs-box { background: #f8fafc; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 8px; line-height: 1.4; color: #334155; }
    
    /* Totales */
    .total-label { font-weight: bold; color: #4b5563; text-transform: uppercase; font-size: 9px; margin-right: 10px; }
    .total-val { font-weight: bold; font-size: 9.5px; }
    .grand-total { font-size: 13px; border-top: 2px solid #0f172a; padding-top: 4px; color: #0f172a; }
    .text-right { text-align: right; }
</style>
</head>
<body>
    <!-- Inclusión de Cabecera Compartida -->
    <?php if(file_exists(APPROOT . '/Views/pdf/inc/header.php')): ?>
        <?php 
            $titulo_documento = $tipo_doc_label;
            $documento_numero = $venta->id_formateado ?: 'N/A';
            $fecha_documento  = $fecha_elegante;
            require APPROOT . '/Views/pdf/inc/header.php'; 
        ?>
    <?php endif; ?>

    <div class="section-box">
        <div class="section-title">Datos del Cliente y Vehículo</div>
        <div class="section-content">
            <table width="100%">
                <tr>
                    <td width="40%">
                        <div class="label-min">Propietario / Cliente</div>
                        <div class="val-text"><?php echo $venta->cliente_nombre; ?></div>
                        <div style="font-size: 8px; color: #64748b;">ID: <?php echo $venta->cliente_id; ?> | Tel: <?php echo $venta->cliente_telefono; ?></div>
                    </td>
                    <?php if($es_taller || $es_os): ?>
                    <td width="30%">
                        <div class="label-min">Vehículo</div>
                        <div class="val-text"><?php echo ($venta->marca_vehiculo ?? '') . " " . $venta->modelo_vehiculo; ?></div>
                        <div class="val-text" style="color: #3b82f6;">PLACA: <?php echo $venta->placa; ?></div>
                    </td>
                    <td width="30%" style="text-align: right;">
                        <div class="label-min">Datos Técnicos</div>
                        <?php if($es_os && !empty($venta->kilometraje)): ?>
                            <div class="val-text">KM: <?php echo is_numeric($venta->kilometraje) ? number_format($venta->kilometraje) : $venta->kilometraje; ?></div>
                        <?php endif; ?>
                        <?php if($es_os && !empty($venta->nivel_combustible) && $venta->nivel_combustible !== 'N/A'): ?>
                            <div class="val-text">COMB: <?php echo $venta->nivel_combustible; ?></div>
                        <?php endif; ?>
                        <div class="val-text" style="font-size: 8px;">TÉC: <?php echo $venta->mecanico_nombre ?: 'S/A'; ?></div>
                    </td>
                    <?php endif; ?>
                </tr>
            </table>
        </div>
    </div>

    <?php if ($es_os && !empty($venta->checklist)): ?>
    <div class="section-box">
        <div class="section-title">Inventario de Recepción (Checklist)</div>
        <div class="section-content" style="padding-top: 2px; padding-bottom: 2px;">
            <?php foreach($venta->checklist as $chk): ?>
                <div class="checklist-item">
                    <span style="color: #10b981; font-weight: bold;">[✓]</span> 
                    <strong style="color: #0f172a;"><?php echo strtoupper($chk->item); ?></strong> 
                    <?php if($chk->observacion): ?> — <span style="color: #64748b;">Obs: <?php echo htmlspecialchars($chk->observacion); ?></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="section-title" style="background: none; border: none; padding-left: 0; margin-bottom: 2px;">Detalle de Trabajos y Repuestos</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="65%">Descripción del Servicio / Repuesto</th>
                <th width="10%" class="text-right">Cant.</th>
                <th width="12%" class="text-right">Unitario</th>
                <th width="13%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($venta->items as $item): ?>
            <tr>
                <td style="text-transform: uppercase;"><?php echo $item->descripcion; ?></td>
                <td class="text-right" style="font-weight: bold;">x<?php echo $item->cantidad; ?></td>
                <td class="text-right">$ <?php echo number_format($item->precio_unitario, 2); ?></td>
                <td class="text-right" style="font-weight: bold;">$ <?php echo number_format($item->cantidad * $item->precio_unitario, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table width="100%" style="margin-top: 10px;">
        <tr>
            <td width="55%" style="vertical-align: top; padding-right: 15px;">
                <?php
                    $diag_os = trim((string) ($venta->diagnostico_entrada ?? ''));
                    $obs_salida = trim((string) ($venta->observaciones ?? ''));
                    $has_diag_os = $es_os && $diag_os !== '';
                    $has_obs_salida = $obs_salida !== '';
                ?>
                <?php if ($has_diag_os || $has_obs_salida): ?>
                    <div class="label-min" style="margin-bottom: 4px;">Trazabilidad de Observaciones:</div>
                    <div class="obs-box">
                        <?php if($has_diag_os): ?>
                            <strong style="color: #0f172a;">[INGRESO] MOTIVO / DIAGNÓSTICO:</strong><br>
                            <?php echo nl2br(htmlspecialchars($diag_os)); ?>
                            <?php if($has_obs_salida) echo '<br><br>'; ?>
                        <?php endif; ?>
                        <?php if($has_obs_salida): ?>
                            <strong style="color: #0f172a;">[SALIDA] NOTAS FINALES:</strong><br>
                            <?php echo nl2br(htmlspecialchars($obs_salida)); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </td>
            <td width="45%" style="vertical-align: top;">
                <table width="100%" style="border-collapse: collapse;">
                    <tr><td class="total-label">Subtotal:</td><td class="total-val text-right">$ <?php echo number_format($venta->subtotal, 2); ?></td></tr>
                    <?php if($venta->iva_monto > 0): ?>
                        <tr><td class="total-label">IVA:</td><td class="total-val text-right">$ <?php echo number_format($venta->iva_monto, 2); ?></td></tr>
                    <?php endif; ?>
                    <tr><td class="total-label grand-total">TOTAL NETO:</td><td class="total-val grand-total text-right">$ <?php echo number_format($venta->total, 2); ?></td></tr>
                    <tr><td colspan="2" style="padding-top: 10px;"></td></tr>
                    <tr><td class="label-min">Abonado:</td><td class="text-right" style="font-size: 9px;">$ <?php echo number_format($venta->pago_efectivo + $venta->pago_transferencia, 2); ?></td></tr>
                    <?php if($venta->saldo_pendiente > 0): ?>
                        <tr><td class="label-min" style="color: #e11d48; font-weight: 900;">Saldo Pendiente:</td><td class="text-right" style="font-size: 11px; font-weight: 900; color: #e11d48;">$ <?php echo number_format($venta->saldo_pendiente, 2); ?></td></tr>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px; border-top: 1px solid #cbd5e1; padding-top: 8px; text-align: center; color: #64748b; font-size: 7.5px;">
        Esta factura representa el soporte técnico y contable de los servicios prestados. | Gracias por confiar en <strong>Taller Pro 2.0</strong>.
    </div>
</body>
</html>