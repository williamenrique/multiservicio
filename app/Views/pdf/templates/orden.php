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
        @page { margin: 20px 25px; }
        body { font-family: 'Helvetica', sans-serif; color: #1e293b; font-size: 8.5px; line-height: 1.2; }
        
        /* Estilos de Cabecera */
        .header-table { width: 100%; border-bottom: 3px solid #0f172a; margin-bottom: 15px; padding-bottom: 10px; }
        .company-data { width: 50%; vertical-align: top; text-align: left; }
        .invoice-data { width: 50%; vertical-align: top; text-align: right; }
        .doc-type { font-size: 12px; font-weight: 900; }

        .label-min { font-size: 8px; color: #6b7280; text-transform: uppercase; font-weight: bold; }
        .val-text { font-size: 10px; font-weight: bold; color: #111827; }

        .section-box { margin-bottom: 15px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        
        .diagnostico-box { 
            border: 1px solid #e5e7eb; 
            padding: 15px; 
            border-radius: 8px; 
            min-height: 250px; 
            background-color: #f9fafb;
            margin-top: 5px;
            font-size: 11px;
        }
        
        .section-title { 
            font-size: 9px; 
            font-weight: bold; 
            text-transform: uppercase; 
            color: #4b5563; 
            border-bottom: 1px solid #e5e7eb; 
            padding-bottom: 5px; 
            margin-bottom: 10px; 
        }

        /* Estilo para Checklist en Orden */
        .checklist-list { margin: 0; padding: 0; list-style: none; }
        .checklist-item { display: block; width: 100%; font-size: 8px; padding: 2px 0; border-bottom: 1px dotted #e2e8f0; }
        .checklist-item { display: inline-block; width: 32%; font-size: 9px; color: #475569; margin-bottom: 3px; }

        /* Tabla de Ítems para la Orden */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { border-bottom: 2px solid #111827; padding: 6px 4px; text-align: left; font-size: 8px; text-transform: uppercase; }
        .items-table td { padding: 8px 4px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
        .text-right { text-align: right; }

        .signature-section { margin-top: 60px; }
        .signature-table { width: 100%; }
        .signature-line { border-top: 1px solid #111827; padding-top: 5px; text-align: center; width: 40%; font-weight: bold; font-size: 9px; }
    </style>
</head>
<body>
    <!-- Inclusión de Cabecera Compartida -->
    <?php if(file_exists(APPROOT . '/Views/pdf/inc/header.php')): ?>
        <?php 
            // Variables para la cabecera compartida
            $titulo_documento = 'ORDEN DE SERVICIO';
            $documento_numero = '#' . $orden->id;
            $fecha_documento = $fecha_elegante . ' - ' . $hora_elegante;
            $doc_color = "#3b82f6";
            $status_documento = $orden->estado;
            // No pasamos $venta aquí, ya que es una orden, no una factura.
            // Si se necesita el vendedor/técnico, se puede adaptar el header.php para usar $orden.
            require_once APPROOT . '/Views/pdf/inc/header.php'; 
        ?>
    <?php else: // Fallback si la cabecera compartida no se encuentra ?>
        <div style="color:red; border:1px solid red; padding:10px;">Error Crítico: No se encontró la cabecera en Views/pdf/inc/header.php</div>
        </div>
    <?php endif; ?>

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
                <td style="padding-top: 8px;">
                    <span class="label-min">Propietario:</span><br>
                    <span class="val-text"><?php echo $orden->cliente_nombre ?? 'N/A'; ?></span>
                </td>
                <td style="padding-top: 8px;">
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
                    <?php if($chk->observacion): ?> — <span style="color: #64748b;">Obs: <?php echo htmlspecialchars($chk->observacion); ?></span><?php endif; ?>
                </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="section-box">
        <div class="section-title">Motivo de Ingreso y Diagnóstico Técnico</div>
        <div class="diagnostico-box" style="min-height: 150px;">
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
                    <td style="text-transform: uppercase;"><?php echo $item->descripcion ?? $item->nombre; ?></td>
                    <td style="text-align: center;"><?php echo $item->cantidad; ?></td>
                    <td class="text-right">$ <?php echo number_format($item->precio_unitario ?? $item->precio, 2); ?></td>
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
</body>
</html>