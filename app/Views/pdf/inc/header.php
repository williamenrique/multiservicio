<?php
// Este archivo es la cabecera compartida para los PDFs.
// Recibe las siguientes variables:
// $empresa (objeto con datos de la empresa)
// $titulo_documento (ej: 'ORDEN DE SERVICIO', 'FACTURA DE VENTA')
// $documento_numero (ej: '#4', 'FAC-001')
// $fecha_documento (fecha formateada)
// $status_documento (opcional, para Ordenes)
// $venta (objeto de venta, para factura)
// $orden (objeto de orden, para orden)
?>
<table class="header-table" width="100%">
    <tr>
        <td class="company-data" style="width: 55%;">
            <div class="company-name" style="font-size: 22px; font-weight: 900; color: #0f172a; margin-bottom: 0; letter-spacing: -1px;">
                <?php echo strtoupper($empresa->name ?: 'TALLER PROF´´´´ESIONAL'); ?>
            </div>
            <div style="width: 60px; height: 5px; background-color: #10b981; margin-bottom: 8px;"></div>
            <div style="font-size: 9px; color: #475569; line-height: 1.2;">
                <strong>NIT:</strong> <?php echo $empresa->nit ?: 'N/A'; ?><br>
                <strong>DIRECCION:</strong> <?php echo strtoupper($empresa->direccion ?: 'N/A'); ?><br>
                <strong>TELEFONO:</strong> <?php echo $empresa->telefono ?: 'N/A'; ?>
            </div>
        </td>
        <td class="invoice-data" style="width: 45%; text-align: right; vertical-align: top; padding-right: 0;">
            <div style="margin-bottom: 8px;">
                <div class="doc-type" style="color: <?php echo $doc_color ?? '#3b82f6'; ?>; font-size: 11px; font-weight: 900; margin-bottom: 1px; letter-spacing: .14em; text-transform: uppercase;"><?php echo strtoupper($titulo_documento); ?></div>
                <div class="doc-number" style="font-size: 22px; font-weight: 900; color: #0f172a; letter-spacing: -1px; line-height: 1.05;"><?php echo $documento_numero; ?></div>
            </div>
            <div style="line-height: 1.3;">
                <div style="margin-bottom: 2px;"><span class="label-min">Emision:</span> <span class="val-text" style="font-size: 9px;"><?php echo strtoupper($fecha_documento); ?></span></div>
                
                <?php if (isset($status_documento)): ?>
                    <div style="margin-bottom: 2px;"><span class="label-min">Estado:</span> <span class="val-text" style="font-size: 9px; color: #10b981;"><?php echo strtoupper($status_documento); ?></span></div>
                <?php endif; ?>

                <?php if (isset($venta) && !empty($venta->vendedor_nombre)): ?>
                    <div><span class="label-min">Vendedor:</span> <span class="val-text" style="font-size: 9px;"><?php echo strtoupper($venta->vendedor_nombre); ?></span></div>
                <?php endif; ?>

                <?php 
                    $mecanico = $venta->mecanico_nombre ?? $orden->mecanico_nombre ?? null;
                    if ($mecanico): 
                ?>
                    <div><span class="label-min">Tecnico:</span> <span class="val-text" style="font-size: 9px;"><?php echo strtoupper($mecanico); ?></span></div>
                <?php endif; ?>
            </div>
        </td>
    </tr>
</table>