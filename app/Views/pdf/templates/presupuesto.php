<?php
    // Plantilla PDF para Presupuesto / Cotización
    // Recibe vía extract($data): $presupuesto (objeto con cabecera), $items (array de detalle), $empresa (datos empresa)

    $p = $presupuesto;

    $meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
    $fecha_dt = strtotime($p->fecha_emision);
    $fecha_elegante = date('d', $fecha_dt) . " de " . $meses[date('n', $fecha_dt)-1] . " del " . date('Y', $fecha_dt);
    
    $fecha_venc_dt = $p->fecha_vencimiento ? strtotime($p->fecha_vencimiento) : null;
    $fecha_venc_elegante = $fecha_venc_dt ? date('d', $fecha_venc_dt) . " de " . $meses[date('n', $fecha_venc_dt)-1] . " del " . date('Y', $fecha_venc_dt) : 'N/A';

    // Objeto venta simulado para compatibilidad con header.php
    $venta = new stdClass();
    $venta->id_formateado = $p->numero;
    $venta->fecha = $p->fecha_emision;
    $venta->cliente_nombre = $p->cliente_nombre;
    $venta->cliente_id = $p->cliente_cedula ?? '';
    $venta->cliente_telefono = $p->cliente_telefono ?? 'N/A';
    $venta->placa = $p->vehiculo_placa ?? '';
    $venta->modelo_vehiculo = $p->vehiculo_modelo ?? '';
    $venta->marca_vehiculo = $p->vehiculo_marca ?? '';
    $venta->vendedor_nombre = $p->usuario_nombre ?? '';
    $venta->mecanico_nombre = '';
    $venta->observaciones = $p->observaciones ?? '';

    $titulo_pestaña = "PDF - PRESUPUESTO " . $p->numero;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo strtoupper($titulo_pestaña); ?></title>
<style>
    @page { margin: 15px 20px; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1e293b; font-size: 10px; line-height: 1.5; }

    /* Estilos de Bloques */
    .header-table { width: 100%; border-bottom: 2px solid #0f172a; margin-bottom: 8px; padding-bottom: 5px; }
    .section-box { margin: 6px 0 8px 0; padding: 6px 4px; border-radius: 2px; background: #fafbfc; }
    .section-title { font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #1e40af; border-bottom: 1.5px solid #dbeafe; padding: 4px 8px; margin: 0 0 6px 0; background: #f0f4ff; border-radius: 2px 2px 0 0; }
    .section-content { padding: 6px; }

    .label-min { font-size: 8px; color: #6b7280; text-transform: uppercase; font-weight: 700; letter-spacing: 0.3px; }
    .val-text { font-size: 11px; font-weight: 700; color: #111827; }

    .items-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
    .items-table th { background: #0f172a; color: white; padding: 5px; text-align: left; font-size: 8px; text-transform: uppercase; }
    .items-table td { padding: 6px 5px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }

    .obs-box { background: #f8fafc; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 9px; line-height: 1.4; color: #334155; }

    /* Badge presupuesto */
    .badge-presupuesto { display: inline-block; background: #3b82f6; color: #fff; padding: 3px 10px; border-radius: 3px; font-size: 10px; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
    .badge-estado { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: 900; text-transform: uppercase; }
    .badge-borrador { background: #e5e7eb; color: #374151; }
    .badge-enviado { background: #fef3c7; color: #92400e; }
    .badge-aceptado { background: #d1fae5; color: #065f46; }
    .badge-rechazado { background: #fee2e2; color: #991b1b; }
    .badge-expirado { background: #fee2e2; color: #991b1b; }
    .badge-convertido { background: #ede9fe; color: #5b21b6; }

    /* Totales */
    .total-label { font-weight: bold; color: #4b5563; text-transform: uppercase; font-size: 9px; margin-right: 10px; }
    .total-val { font-weight: bold; font-size: 11px; }
    .grand-total { font-size: 14px; border-top: 2px solid #0f172a; padding-top: 4px; color: #0f172a; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }

    /* Badges pequeños en tabla */
    .pill { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8px; font-weight: 900; text-transform: uppercase; color: #fff; }
    .pill-producto { background: #10b981; }
    .pill-servicio { background: #3b82f6; }
</style>
</head>
<body>
    <!-- Inclusión de Cabecera Compartida -->
    <?php if(file_exists(APPROOT . '/Views/pdf/inc/header.php')): ?>
        <?php
            $titulo_documento = 'PRESUPUESTO / COTIZACIÓN';
            $documento_numero = $p->numero;
            $fecha_documento  = $fecha_elegante;
            $status_documento = $p->estado;
            $doc_color = '#3b82f6';
            require APPROOT . '/Views/pdf/inc/header.php';
        ?>
    <?php endif; ?>

    <!-- Banner de Presupuesto -->
    <div style="text-align: center; margin-bottom: 10px;">
        <span class="badge-presupuesto">DOCUMENTO DE PRESUPUESTO / COTIZACIÓN</span>
        <span style="margin-left: 8px; font-size: 8px; color: #64748b;">Válido por <?php echo $p->validez_dias; ?> días - <?php echo $p->iva_activo ? 'Con IVA (' . $p->tasa_iva . '%)' : 'Sin IVA'; ?></span>
    </div>

    <!-- Datos del Cliente y Vehículo -->
    <div class="section-box">
        <div class="section-title">Datos del Cliente y Vehículo</div>
        <div class="section-content">
            <table width="100%">
                <tr>
                    <td width="50%">
                        <div class="label-min">Cliente</div>
                        <div class="val-text"><?php echo strtoupper($p->cliente_nombre ?? 'N/A'); ?></div>
                        <div style="font-size: 8px; color: #64748b;">CÉDULA: <?php echo strtoupper($p->cliente_cedula ?? ''); ?> | TEL: <?php echo strtoupper($p->cliente_telefono ?? 'N/A'); ?></div>
                        <?php if ($p->cliente_email): ?>
                        <div style="font-size: 8px; color: #64748b;">EMAIL: <?php echo strtoupper($p->cliente_email); ?></div>
                        <?php endif; ?>
                        <?php if ($p->cliente_direccion): ?>
                        <div style="font-size: 8px; color: #64748b;">DIR: <?php echo strtoupper($p->cliente_direccion); ?></div>
                        <?php endif; ?>
                    </td>
                    <td width="50%" style="text-align: right;">
                        <div class="label-min">Vehículo</div>
                        <div class="val-text"><?php echo strtoupper(trim(($p->vehiculo_marca ?? '') . ' ' . ($p->vehiculo_modelo ?? ''))); ?></div>
                        <div class="val-text" style="color: #3b82f6;">PLACA: <?php echo strtoupper($p->vehiculo_placa ?? 'N/A'); ?></div>
                        <?php if ($p->vehiculo_anio): ?>
                        <div style="font-size: 8px; color: #64748b;">AÑO: <?php echo $p->vehiculo_anio; ?> | COLOR: <?php echo strtoupper($p->vehiculo_color ?? ''); ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Información del Presupuesto -->
    <div class="section-box">
        <div class="section-title">Información del Presupuesto</div>
        <div class="section-content">
            <table width="100%">
                <tr>
                    <td width="25%">
                        <div class="label-min">Fecha Emisión</div>
                        <div class="val-text"><?php echo date('d/m/Y', strtotime($p->fecha_emision)); ?></div>
                    </td>
                    <td width="25%">
                        <div class="label-min">Vencimiento</div>
                        <div class="val-text"><?php echo $fecha_venc_elegante; ?></div>
                    </td>
                    <td width="25%">
                        <div class="label-min">Validez</div>
                        <div class="val-text"><?php echo $p->validez_dias; ?> días</div>
                    </td>
                    <td width="25%" style="text-align: right;">
                        <div class="label-min">IVA</div>
                        <div class="val-text"><?php echo $p->iva_activo ? $p->tasa_iva . '%' : 'No aplica'; ?></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="label-min">Estado</div>
                        <?php
                            $estadoColors = [
                                'BORRADOR' => '#9ca3af',
                                'ENVIADO' => '#f59e0b',
                                'ACEPTADO' => '#10b981',
                                'RECHAZADO' => '#ef4444',
                                'EXPIRADO' => '#ef4444',
                                'CONVERTIDO' => '#8b5cf6'
                            ];
                            $estadoColor = $estadoColors[$p->estado] ?? '#9ca3af';
                        ?>
                        <div class="val-text" style="color: <?php echo $estadoColor; ?>;"><?php echo strtoupper($p->estado); ?></div>
                    </td>
                    <td>
                        <div class="label-min">Creado por</div>
                        <div class="val-text"><?php echo strtoupper($p->usuario_nombre ?? 'Sistema'); ?></div>
                    </td>
                    <td style="text-align: right;">
                        <div class="label-min">Fecha Creación</div>
                        <div class="val-text"><?php echo date('d/m/Y H:i', strtotime($p->fecha_creacion)); ?></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Detalle de items del presupuesto -->
    <div class="section-title" style="background: none; border: none; padding-left: 0; margin-bottom: 2px;">Detalle de Items</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="10%">Tipo</th>
                <th width="35%">Descripción</th>
                <th width="7%" class="text-center">Cant.</th>
                <th width="11%" class="text-right">P. Unitario</th>
                <th width="7%" class="text-center">Desc. %</th>
                <th width="11%" class="text-right">Subtotal</th>
                <th width="7%" class="text-center">IVA %</th>
                <th width="12%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $index => $item): ?>
                <?php
                    $pill_tipo = $item->tipo_item === 'SERVICIO' ? 'pill-servicio' : 'pill-producto';
                ?>
                <tr>
                    <td class="text-center" style="font-weight: bold;"><?php echo $index + 1; ?></td>
                    <td class="text-center"><span class="pill <?php echo $pill_tipo; ?>"><?php echo strtoupper($item->tipo_item); ?></span></td>
                    <td style="text-transform: uppercase;"><?php echo strtoupper($item->descripcion); ?>
                        <?php if (!empty($item->notas)): ?>
                            <br><span style="font-size: 8px; color: #64748b; font-style: italic;"><?php echo strtoupper($item->notas); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($item->producto_codigo)): ?>
                            <br><span style="font-size: 8px; color: #64748b; font-family: monospace;">Ref: <?php echo strtoupper($item->producto_codigo); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center" style="font-weight: bold;">x<?php echo $item->cantidad; ?></td>
                    <td class="text-right">$ <?php echo number_format($item->precio_unitario, 2); ?></td>
                    <td class="text-center"><?php echo $item->descuento_porcentaje > 0 ? $item->descuento_porcentaje . '%' : '-'; ?></td>
                    <td class="text-right">$ <?php echo number_format($item->subtotal, 2); ?></td>
                    <td class="text-center"><?php echo $item->iva_porcentaje > 0 ? $item->iva_porcentaje . '%' : '-'; ?></td>
                    <td class="text-right" style="font-weight: bold;">$ <?php echo number_format($item->total, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totales -->
    <table width="100%" style="margin-top: 10px;">
        <tr>
            <td width="55%" style="vertical-align: top; padding-right: 15px;">
                <div class="label-min" style="margin-bottom: 4px;">Observaciones:</div>
                <div class="obs-box">
                    <?php if (!empty($p->observaciones)): ?>
                        <strong style="color: #0f172a;">OBSERVACIONES:</strong><br>
                        <?php echo nl2br(htmlspecialchars(strtoupper($p->observaciones))); ?>
                    <?php else: ?>
                        <em style="color: #94a3b8;">Sin observaciones</em>
                    <?php endif; ?>
                </div>
                <?php if (!empty($p->condiciones)): ?>
                <div class="label-min" style="margin: 8px 0 4px 0;">Términos y Condiciones:</div>
                <div class="obs-box" style="background: #fef3c7; border-color: #f59e0b;">
                    <strong style="color: #0f172a;">CONDICIONES:</strong><br>
                    <?php echo nl2br(htmlspecialchars(strtoupper($p->condiciones))); ?>
                </div>
                <?php endif; ?>
            </td>
            <td width="45%" style="vertical-align: top;">
                <table width="100%" style="border-collapse: collapse;">
                    <tr><td class="total-label">Subtotal:</td><td class="total-val text-right">$ <?php echo number_format($p->subtotal, 2); ?></td></tr>
                    <?php 
                    $totalDescuentos = 0;
                    foreach($items as $item) {
                        $descuento = ($item->precio_unitario * $item->cantidad) * ($item->descuento_porcentaje / 100);
                        $totalDescuentos += $descuento;
                    }
                    if ($totalDescuentos > 0): ?>
                    <tr><td class="total-label" style="color: #ef4444;">Descuentos:</td><td class="total-val text-right" style="color: #ef4444;">- $ <?php echo number_format($totalDescuentos, 2); ?></td></tr>
                    <?php endif; ?>
                    <tr><td class="total-label">IVA (<?php echo $p->tasa_iva; ?>%):</td><td class="total-val text-right">$ <?php echo number_format($p->iva_monto, 2); ?></td></tr>
                    <tr><td class="total-label grand-total">TOTAL:</td><td class="total-val grand-total text-right">$ <?php echo number_format($p->total, 2); ?></td></tr>
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
                <div style="border-top: 1px solid #0f172a; padding-top: 4px; margin: 0 30px; font-size: 8px; font-weight: bold; text-transform: uppercase;">Autorizado por: <?php echo strtoupper($p->usuario_nombre ?? ''); ?></div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px; border-top: 1px solid #cbd5e1; padding-top: 8px; text-align: center; color: #64748b; font-size: 7.5px;">
        Presupuesto generado por <strong>Taller Pro 2.0</strong> | Válido por <?php echo $p->validez_dias; ?> días desde la fecha de emisión | <?php echo $p->iva_activo ? 'Incluye IVA (' . $p->tasa_iva . '%)' : 'No incluye IVA'; ?>
    </div>
</body>
</html>