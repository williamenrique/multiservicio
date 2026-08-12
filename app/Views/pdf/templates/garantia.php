<?php
    // Plantilla PDF para Factura de Garantía
    // Recibe vía extract($data): $garantia (objeto con cabecera), $items (array de detalle), $empresa (datos empresa)

    $g = $garantia;

    // Tipo de garantía
    $tipos_garantia = [
        'SERVICIO'  => ['label' => 'GARANTÍA DE SERVICIO',  'color' => '#f59e0b'],
        'REPUESTO'  => ['label' => 'GARANTÍA DE REPUESTO',  'color' => '#3b82f6'],
        'MIXTO'     => ['label' => 'GARANTÍA MIXTA',         'color' => '#8b5cf6'],
    ];
    $tipo_info = $tipos_garantia[$g->tipo_garantia] ?? ['label' => 'GARANTÍA', 'color' => '#f59e0b'];
    $tipo_doc_label = $tipo_info['label'];
    $doc_color = $tipo_info['color'];

    $meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
    $fecha_dt = strtotime($g->fecha);
    $fecha_elegante = date('d', $fecha_dt) . " de " . $meses[date('n', $fecha_dt)-1] . " del " . date('Y', $fecha_dt);

    // Objeto venta simulado para compatibilidad con header.php
    $venta = new stdClass();
    $venta->id_formateado = 'GAR-' . str_pad($g->id, 4, '0', STR_PAD_LEFT);
    $venta->fecha = $g->fecha;
    $venta->cliente_nombre = $g->cliente ?? 'N/A';
    $venta->cliente_id = $g->cliente_id ?? '';
    $venta->cliente_telefono = $g->cliente_telefono ?? 'N/A';
    $venta->placa = $g->placa ?? '';
    $venta->modelo_vehiculo = $g->modelo_vehiculo ?? '';
    $venta->marca_vehiculo = $g->marca_vehiculo ?? '';
    $venta->vendedor_nombre = $g->usuario_nombre ?? '';
    $venta->mecanico_nombre = '';
    $venta->observaciones = $g->motivo ?? '';

    $titulo_pestaña = "PDF - " . $tipo_doc_label . " " . $venta->id_formateado;
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

    .obs-box { background: #f8fafc; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 8px; line-height: 1.4; color: #334155; }

    /* Badge garantía */
    .badge-garantia { display: inline-block; background: #f59e0b; color: #fff; padding: 3px 10px; border-radius: 3px; font-size: 9px; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
    .badge-anulada { display: inline-block; background: #e11d48; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 8px; font-weight: 900; text-transform: uppercase; }

    /* Motivo destacado */
    .motivo-box { background: #fef3c7; border: 1px solid #f59e0b; border-left: 4px solid #f59e0b; padding: 8px 10px; border-radius: 4px; font-size: 9px; line-height: 1.4; color: #78350f; }

    /* Totales */
    .total-label { font-weight: bold; color: #4b5563; text-transform: uppercase; font-size: 9px; margin-right: 10px; }
    .total-val { font-weight: bold; font-size: 9.5px; }
    .grand-total { font-size: 13px; border-top: 2px solid #0f172a; padding-top: 4px; color: #0f172a; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }

    /* Badges pequeños en tabla */
    .pill { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 7px; font-weight: 900; text-transform: uppercase; color: #fff; }
    .pill-servicio { background: #f59e0b; }
    .pill-repuesto { background: #3b82f6; }
    .pill-devolver { background: #e11d48; }
    .pill-aumentar { background: #10b981; }
    .pill-reemplazar { background: #8b5cf6; }
    .pill-stock { background: #0f766e; }
    .pill-danado { background: #b91c1c; }
    .pill-na { background: #64748b; }
</style>
</head>
<body>
    <!-- Inclusión de Cabecera Compartida -->
    <?php if(file_exists(APPROOT . '/Views/pdf/inc/header.php')): ?>
        <?php
            $titulo_documento = $tipo_doc_label;
            $documento_numero = $venta->id_formateado;
            $fecha_documento  = $fecha_elegante;
            $status_documento = 'COMPLETADO';
            require APPROOT . '/Views/pdf/inc/header.php';
        ?>
    <?php endif; ?>

    <!-- Banner de Garantía -->
    <div style="text-align: center; margin-bottom: 10px;">
        <span class="badge-garantia">DOCUMENTO DE GARANTÍA</span>
        <span style="margin-left: 8px; font-size: 8px; color: #64748b;">Generada por reemplazo/reparación de servicios o repuestos</span>
    </div>

    <!-- Referencia a factura original anulada -->
    <div class="section-box" style="border-color: #e11d48; background: #fef2f2;">
        <div class="section-title" style="background: #fee2e2; color: #991b1b; border-bottom-color: #fecaca;">Factura Original Anulada</div>
        <div class="section-content" style="background: #fef2f2;">
            <table width="100%">
                <tr>
                    <td width="50%">
                        <div class="label-min">Factura Original</div>
                        <div class="val-text" style="color: #991b1b;"># <?php echo str_pad($g->factura_original_id, 4, '0', STR_PAD_LEFT); ?></div>
                    </td>
                    <td width="50%" style="text-align: right;">
                        <div class="label-min">Estado</div>
                        <span class="badge-anulada">ANULADA</span>
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
                        <div class="val-text"><?php echo strtoupper($g->cliente ?? 'N/A'); ?></div>
                        <div style="font-size: 8px; color: #64748b;">CÉDULA: <?php echo strtoupper($g->cliente_cedula ?? $g->cliente_id ?? ''); ?> | TEL: <?php echo strtoupper($g->cliente_telefono ?? 'N/A'); ?></div>
                    </td>
                    <td width="50%" style="text-align: right;">
                        <div class="label-min">Vehículo</div>
                        <div class="val-text"><?php echo strtoupper(trim(($g->marca_vehiculo ?? '') . ' ' . ($g->modelo_vehiculo ?? ''))); ?></div>
                        <div class="val-text" style="color: #3b82f6;">PLACA: <?php echo strtoupper($g->placa ?? 'N/A'); ?></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Motivo de la garantía -->
    <div class="section-box">
        <div class="section-title" style="background: #fef3c7; color: #78350f; border-bottom-color: #f59e0b;">Motivo de la Garantía</div>
        <div class="section-content">
            <div class="motivo-box">
                <strong style="font-size: 8px; letter-spacing: .1em;">MOTIVO:</strong><br>
                <?php echo nl2br(htmlspecialchars(strtoupper($g->motivo ?? ''))); ?>
            </div>
            <table width="100%" style="margin-top: 6px;">
                <tr>
                    <td width="33%">
                        <div class="label-min">Tipo de Garantía</div>
                        <div class="val-text" style="color: <?php echo $doc_color; ?>;"><?php echo strtoupper($g->tipo_garantia ?? ''); ?></div>
                    </td>
                    <td width="33%">
                        <div class="label-min">Días Garantía Servicio</div>
                        <div class="val-text"><?php echo $g->dias_garantia_servicio ?? 0; ?> días</div>
                    </td>
                    <td width="33%" style="text-align: right;">
                        <div class="label-min">Días Transcurridos</div>
                        <div class="val-text"><?php echo $g->dias_transcurridos ?? 0; ?> días</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Detalle de items de garantía -->
    <div class="section-title" style="background: none; border: none; padding-left: 0; margin-bottom: 2px;">Detalle de Items en Garantía</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="38%">Descripción</th>
                <th width="10%">Tipo</th>
                <th width="7%" class="text-center">Cant.</th>
                <th width="11%" class="text-center">Acción</th>
                <th width="11%" class="text-center">Destino</th>
                <th width="11%" class="text-right">Unitario</th>
                <th width="12%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
                <?php
                    $pill_tipo = $item->tipo_item === 'SERVICIO' ? 'pill-servicio' : 'pill-repuesto';
                    $pill_accion = 'pill-' . strtolower($item->accion);
                    $pill_destino = 'pill-' . strtolower($item->destino);
                ?>
                <tr>
                    <td style="text-transform: uppercase;"><?php echo strtoupper($item->descripcion); ?></td>
                    <td class="text-center"><span class="pill <?php echo $pill_tipo; ?>"><?php echo strtoupper($item->tipo_item); ?></span></td>
                    <td class="text-center" style="font-weight: bold;">x<?php echo $item->cantidad; ?></td>
                    <td class="text-center"><span class="pill <?php echo $pill_accion; ?>"><?php echo strtoupper($item->accion); ?></span></td>
                    <td class="text-center"><span class="pill <?php echo $pill_destino; ?>"><?php echo strtoupper($item->destino); ?></span></td>
                    <td class="text-right">$ <?php echo number_format($item->precio_unitario, 2); ?></td>
                    <td class="text-right" style="font-weight: bold;">$ <?php echo number_format($item->monto_total, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totales -->
    <table width="100%" style="margin-top: 10px;">
        <tr>
            <td width="55%" style="vertical-align: top; padding-right: 15px;">
                <div class="label-min" style="margin-bottom: 4px;">Notas:</div>
                <div class="obs-box">
                    <strong style="color: #0f172a;">FACTURA ORIGINAL #<?php echo str_pad($g->factura_original_id, 4, '0', STR_PAD_LEFT); ?> ANULADA.</strong><br>
                    Esta factura de garantía reemplaza los servicios/repuestos indicados según el motivo descrito. Los items con acción <strong>DEVOLVER</strong> generan egreso (reembolso de mano de obra) y los items con acción <strong>AUMENTAR</strong> generan ingreso por el nuevo servicio/repuesto prestado.
                    <?php if (!empty($g->destino_repuesto) && $g->destino_repuesto !== 'N/A'): ?>
                        <br><br><strong style="color: #0f172a;">DESTINO DE REPUESTOS:</strong> <?php echo strtoupper($g->destino_repuesto); ?>.
                    <?php endif; ?>
                </div>
            </td>
            <td width="45%" style="vertical-align: top;">
                <table width="100%" style="border-collapse: collapse;">
                    <tr><td class="total-label">Monto Mano de Obra:</td><td class="total-val text-right">$ <?php echo number_format($g->monto_mano_obra, 2); ?></td></tr>
                    <tr><td class="total-label">Monto Repuesto:</td><td class="total-val text-right">$ <?php echo number_format($g->monto_repuesto, 2); ?></td></tr>
                    <tr><td class="total-label grand-total">TOTAL GARANTÍA:</td><td class="total-val grand-total text-right">$ <?php echo number_format($g->monto_total, 2); ?></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Firmas -->
    <table width="100%" style="margin-top: 40px;">
        <tr>
            <td width="50%" style="text-align: center;">
                <div style="border-top: 1px solid #0f172a; padding-top: 4px; margin: 0 30px; font-size: 8px; font-weight: bold; text-transform: uppercase;">Cliente</div>
            </td>
            <td width="50%" style="text-align: center;">
                <div style="border-top: 1px solid #0f172a; padding-top: 4px; margin: 0 30px; font-size: 8px; font-weight: bold; text-transform: uppercase;">Autorizado por: <?php echo strtoupper($g->usuario_nombre ?? ''); ?></div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px; border-top: 1px solid #cbd5e1; padding-top: 8px; text-align: center; color: #64748b; font-size: 7.5px;">
        Documento de garantía generado por <strong>Taller Pro 2.0</strong> | La factura original ha sido anulada y reemplazada por este documento.
    </div>
</body>
</html>
