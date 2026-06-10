<?php
// Este archivo es la cabecera compartida para los PDFs.
// Recibe las siguientes variables:
// $empresa (objeto con datos de la empresa)
// $titulo_documento (ej: 'ORDEN DE SERVICIO', 'FACTURA DE VENTA')
// $documento_numero (ej: '#4', 'FAC-001')
// $fecha_documento (fecha formateada)
// $status_documento (opcional, para órdenes)
// $venta (objeto de venta, para factura)
// $orden (objeto de orden, para orden)
?>
<table class="header-table">
    <tr>
        <td class="company-data">
            <div class="company-name"><?php echo $empresa->name ?: 'TALLER PROFESIONAL'; ?></div>
            NIT: <?php echo $empresa->nit ?: 'N/A'; ?><br>
            Dirección: <?php echo $empresa->direccion ?: 'N/A'; ?><br>
            Teléfono: <?php echo $empresa->telefono ?: 'N/A'; ?>
        </td>
        <td class="invoice-data">
            <div class="doc-header-line">
                <span class="doc-type"><?php echo $titulo_documento; ?></span>
                <span class="doc-number"><?php echo $documento_numero; ?></span>
            </div>
            <div style="margin-top: 10px;">
                <span class="label-min">Emisión:</span> <?php echo $fecha_documento; ?><br>
                <?php if (isset($status_documento)): // Solo para Orden de Servicio ?>
                    <span class="label-min">Estado:</span> <?php echo $status_documento; ?><br>
                <?php endif; ?>
                <?php if (isset($venta) && isset($venta->vendedor_nombre)): ?>
                    <span class="label-min">Vendedor:</span> <?php echo $venta->vendedor_nombre ?: 'SISTEMA'; ?><br>
                <?php endif; ?>
                <?php if (isset($venta) && (isset($venta->mecanico_nombre) && !empty($venta->mecanico_nombre))): ?>
                    <span class="label-min">Técnico:</span> <?php echo $venta->mecanico_nombre ?: 'N/A'; ?>
                <?php endif; ?>
            </div>
        </td>
    </tr>
</table>