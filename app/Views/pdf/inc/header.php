<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php 
        // Lógica para detectar el responsable según el tipo de documento
        $responsable = $venta->vendedor_nombre 
                    ?? $pago->registrado_por 
                    ?? $mov->usuario_nombre 
                    ?? $orden->usuario_nombre 
                    ?? $usuario_actual
                    ?? 'Taller Pro Sistema';
    ?>
    <title><?php echo ($titulo_documento ?? 'Documento') . (isset($documento_id) ? ' - ' . $documento_id : ''); ?></title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 0; }
        .header-table { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 15px; margin-bottom: 20px; }
        .company-name { font-size: 20px; font-weight: bold; color: #0f172a; text-transform: uppercase; }
        .company-info { color: #64748b; line-height: 1.4; }
        .doc-details { text-align: right; }
        .doc-title { font-size: 16px; font-weight: bold; color: #0f172a; text-transform: uppercase; margin-bottom: 5px; }
        .doc-number { font-size: 14px; color: #ef4444; font-weight: bold; }
        .content { width: 100%; }
        table.items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.items-table th { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 8px; text-align: left; text-transform: uppercase; font-size: 10px; }
        table.items-table td { border: 1px solid #e2e8f0; padding: 8px; }
        .text-right { text-align: right; }
        .total-box { margin-top: 20px; border-top: 2px solid #0f172a; padding-top: 10px; }
    </style>
</head>
<body>
<table class="header-table">
    <tr>
        <td width="60%">
            <div class="company-name"><?php echo $empresa->name; ?></div>
            <div class="company-info">
                NIT: <?php echo $empresa->nit; ?><br>
                Dirección: <?php echo $empresa->address; ?><br>
                
            </div>
        </td>
        <td width="40%" class="doc-details">
            <div class="doc-title"><?php echo $titulo_documento ?? 'Documento'; ?></div>
            <div class="doc-number"><?php echo $documento_id ?? '000'; ?></div>
            <?php if (isset($venta->status)): ?>
                <div style="margin-top: 5px;">
                    <span style="font-size: 9px; padding: 2px 8px; border-radius: 3px; font-weight: bold; text-transform: uppercase; 
                        <?php echo $venta->status === 'COMPLETADO' ? 'background-color: #dcfce7; color: #166534; border: 1px solid #166534;' : 'background-color: #fef3c7; color: #92400e; border: 1px solid #92400e;'; ?>">
                        <?php echo $venta->status === 'COMPLETADO' ? 'CANCELADA' : 'CRÉDITO'; ?>
                    </span>
                </div>
            <?php endif; ?>
            <div style="margin-top: 5px;"><strong>Generado por:</strong> <?php echo $responsable; ?></div>
            <div style="font-size: 9px; color: #64748b;">Fecha: <?php echo date('d/m/Y - h:i A'); ?></div>
        </td>
    </tr>
</table>