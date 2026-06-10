<?php
    // Configuración de visualización inteligente
    $meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
    $fecha_dt = strtotime($venta->fecha);
    $fecha_elegante = date('d', $fecha_dt) . " de " . $meses[date('n', $fecha_dt)-1] . " del " . date('Y', $fecha_dt);
    
    $es_os = !empty($venta->orden_id);
    $es_taller = !empty($venta->placa);

    // Título dinámico para la pestaña del navegador indicando que es un PDF
    $titulo_pestaña = "PDF - FACTURA " . ($venta->id_formateado ?: "#" . $venta->id) . ($es_os ? " [OS #".$venta->orden_id."]" : "") . " - " . ($venta->placa ?: 'MOSTRADOR');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $titulo_pestaña; ?></title>
<style>
    @page { margin: 30px 40px; }
    body { font-family: 'Helvetica', sans-serif; color: #1f2937; font-size: 10px; line-height: 1.3; }
    
    /* Encabezado Profesional */
    .header-table { width: 100%; border-bottom: 1px solid #111827; margin-bottom: 20px; padding-bottom: 10px; }
    .company-data { width: 60%; vertical-align: top; }
    .invoice-data { width: 40%; vertical-align: top; text-align: right; }
    
    .company-name { font-size: 18px; font-weight: bold; color: #111827; margin-bottom: 2px; }
    .doc-type { font-size: 14px; font-weight: bold; color: #059669; }
    .doc-number { font-size: 16px; font-weight: bold; margin-left: 5px; }
    .doc-header-line { display: flex; align-items: baseline; } /* Para mantenerlos en una línea y alineados */

    .label-min { font-size: 8px; color: #6b7280; text-transform: uppercase; font-weight: bold; }
    .val-text { font-size: 10px; font-weight: bold; color: #111827; }

    /* Secciones */
    .section-box { margin-bottom: 15px; }
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .items-table { width: 100%; border-collapse: collapse; }
    .items-table th { border-bottom: 2px solid #111827; padding: 8px 4px; text-align: left; font-size: 9px; text-transform: uppercase; }
    .items-table td { padding: 10px 4px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    
    /* Totales y Pagos */
    .summary-container { width: 100%; margin-top: 10px; }
    .payments-box { width: 55%; float: left; border: 1px solid #e5e7eb; padding: 10px; border-radius: 4px; }
    .total-section { width: 35%; float: right; }
    .total-row { text-align: right; margin-bottom: 5px; }
    .total-label { font-weight: bold; color: #4b5563; text-transform: uppercase; font-size: 9px; margin-right: 10px; }
    .total-val { font-weight: bold; font-size: 11px; }
    .grand-total { font-size: 16px; border-top: 1px solid #111827; padding-top: 5px; color: #111827; }

    .obs-box { clear: both; margin-top: 25px; border-top: 1px dashed #9ca3af; padding-top: 10px; }
</style>
</head>
<body>
    <!-- Inclusión de Cabecera Compartida -->
    <?php if(file_exists(APPROOT . '/Views/pdf/inc/header.php')): ?>
        <?php 
            // Estandarización de variables para que el header compartido funcione en Factura
            $titulo_documento = 'FACTURA DE VENTA';
            $documento_numero = $venta->id_formateado ?: 'N/A';
            $fecha_documento  = $fecha_elegante;
            require APPROOT . '/Views/pdf/inc/header.php'; 
        ?>
    <?php else: // Manejo de error si la cabecera no se encuentra ?>
        <div style="color:red; border:1px solid red; padding:10px;">
            Error Crítico: No se encontró la cabecera en Views/pdf/inc/header.php
        </div>
    <?php endif; ?>

    <div class="section-box">
        <table width="100%" class="info-table">
            <tr>
                <td width="55%">
                    <div class="label-min">Información del Cliente</div>
                    <span class="val-text"><?php echo $venta->cliente_nombre ?: 'CLIENTE MOSTRADOR'; ?></span><br>
                    ID: <?php echo $venta->cliente_id ?: 'N/A'; ?> | Tel: <?php echo $venta->cliente_telefono ?: 'N/A'; ?>
                </td>
                <td width="45%" style="text-align: right;">
                    <?php if ($es_os || $es_taller): ?>
                        <div class="label-min">Información del Vehículo</div>
                        <span class="val-text"><?php echo $venta->placa ?: 'N/A'; ?> - <?php echo $venta->modelo_vehiculo ?: 'N/A'; ?></span><br>
                        <?php if($es_os): ?>
                            KM: <?php echo number_format($venta->kilometraje ?: 0); ?> | Combustible: <?php echo $venta->nivel_combustible ?: 'N/A'; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="55%">Descripción del Producto / Servicio</th>
                <th class="text-center" width="10%">Cant.</th>
                <th class="text-right" width="15%">Unitario</th>
                <th class="text-right" width="20%">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($venta->items as $item): ?>
            <tr>
                <td style="text-transform: uppercase;"><?php echo $item->descripcion; ?></td>
                <td class="text-center"><?php echo $item->cantidad; ?></td>
                <td class="text-right">$ <?php echo number_format($item->precio_unitario, 2); ?></td>
                <td class="text-right">$ <?php echo number_format($item->cantidad * $item->precio_unitario, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary-container">
        <div class="payments-box">
            <div class="label-min" style="margin-bottom: 5px;">Resumen de Pagos</div>
            <table width="100%">
                <tr><td>Efectivo:</td><td class="text-right">$ <?php echo number_format($venta->pago_efectivo, 2); ?></td></tr>
                <tr><td>Transferencia:</td><td class="text-right">$ <?php echo number_format($venta->pago_transferencia, 2); ?></td></tr>
                <?php if ($venta->status === 'CREDITO'): ?>
                <tr style="color: #dc2626; font-weight: bold;">
                    <td>SALDO PENDIENTE:</td><td class="text-right">$ <?php echo number_format($venta->saldo_pendiente, 2); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="total-section">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span class="total-val">$ <?php echo number_format($venta->subtotal, 2); ?></span>
            </div>
            <?php if($venta->iva_monto > 0): ?>
            <div class="total-row">
                <span class="total-label">IVA:</span>
                <span class="total-val">$ <?php echo number_format($venta->iva_monto, 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <span class="total-label" style="color: #111827;">TOTAL NETO:</span>
                <span class="total-val">$ <?php echo number_format($venta->total, 2); ?></span>
            </div>
        </div>
    </div>

    <?php if (!empty($venta->observaciones) || ($es_os && !empty($venta->diagnostico_entrada))): ?>
    <?php 
        $obs_entrada = $es_os ? trim($venta->diagnostico_entrada ?? '') : '';
        $obs_salida = trim($venta->observaciones ?? '');
        $identicas = (mb_strtoupper($obs_entrada) === mb_strtoupper($obs_salida));
    ?>
    <div class="obs-box">
        <div class="label-min">Información Técnica y Observaciones:</div>
        <div style="font-size: 9px; margin-top: 5px; line-height: 1.4;">
            <?php 
                if($es_os && !empty($obs_entrada)) {
                    echo "<div style='margin-bottom:5px;'><strong>OBSERVACIÓN DE ENTRADA (DIAGNÓSTICO):</strong><br>" . nl2br(htmlspecialchars($obs_entrada)) . "</div>";
                }

                if(!empty($obs_salida) && !$identicas) {
                    if($es_os && !empty($obs_entrada)) echo "<div style='margin-top:4px;'></div>";
                    echo "<div><strong>OBSERVACIÓN DE SALIDA (NOTAS FINALES):</strong><br>" . nl2br(htmlspecialchars($obs_salida)) . "</div>";
                }
            ?>
        </div>
    </div>
    <?php endif; ?>

    <div style="margin-top: 50px; text-align: center; color: #9ca3af; font-size: 8px;">
        Gracias por confiar en nuestros servicios técnicos. Esta factura ha sido generada por el sistema <?php echo $empresa->name ?: 'Taller Profesional'; ?>.
    </div>
</body>
</html>