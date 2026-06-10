<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>PDF - ORDEN DE SERVICIO #<?php echo $orden->id; ?> - <?php echo strtoupper($orden->placa); ?></title>
    <style>
        @page { margin: 30px 40px; }
        body { font-family: 'Helvetica', sans-serif; color: #1f2937; font-size: 10px; line-height: 1.3; }
        
        /* Estilo 2.0 consistente con Factura */
        .header-table { width: 100%; border-bottom: 1px solid #111827; margin-bottom: 20px; padding-bottom: 10px; }
        .company-data { width: 60%; vertical-align: top; }
        .invoice-data { width: 40%; vertical-align: top; text-align: right; }
        
        .company-name { font-size: 18px; font-weight: bold; color: #111827; margin-bottom: 2px; }
        .doc-type { font-size: 14px; font-weight: bold; color: #3b82f6; text-transform: uppercase; }
        .doc-number { font-size: 16px; font-weight: bold; margin-left: 8px; color: #111827; }
        .doc-header-line { white-space: nowrap; margin-bottom: 5px; } 

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
        .checklist-container { margin-bottom: 15px; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
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
            // Las variables $titulo_documento, $documento_numero, etc. 
            // ya son pasadas por el ControllerTaller->imprimir()
            require_once APPROOT . '/Views/pdf/inc/header.php'; 
        ?>
    <?php else: ?>
        <div style="color:red; border:1px solid red; padding:10px;">
            Error Crítico: No se encontró la cabecera en Views/pdf/inc/header.php
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
        <div class="checklist-container">
            <?php foreach($orden->checklist as $chk): ?>
                <div class="checklist-item">
                    • <strong><?php echo strtoupper($chk->item); ?></strong> <?php echo $chk->observacion ? "(".htmlspecialchars($chk->observacion).")" : ""; ?>
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