<style>
    @page { margin: 100px 25px; }
    body { font-family: 'Helvetica', sans-serif; color: #1e293b; line-height: 1.5; }
    .header-fixed { position: fixed; top: -85px; left: 0; right: 0; }
    .footer-fixed { position: fixed; bottom: -60px; left: 0; right: 0; }
    
    .doc-number { font-size: 18px; font-weight: 900; color: #10b981; float: right; margin-top: -45px; }
    .content { margin-top: 10px; }
    
    .customer-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 25px; }
    .label-min { font-size: 8px; font-weight: bold; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px; }
    .val-bold { font-size: 11px; font-weight: bold; color: #0f172a; text-transform: uppercase; }

    .items-table { width: 100%; border-collapse: collapse; }
    .items-table th { background: #0f172a; color: white; padding: 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
    .items-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
    .text-right { text-align: right; }
    
    .total-section { margin-top: 20px; border-top: 2px solid #0f172a; padding-top: 10px; }
    .total-row { text-align: right; margin-bottom: 5px; }
    .total-label { font-weight: bold; color: #64748b; text-transform: uppercase; font-size: 10px; margin-right: 15px; }
    .total-val { font-weight: 900; font-size: 18px; color: #0f172a; }
    .grand-total { color: #10b981; font-size: 24px; }
</style>

<div class="header-fixed">
    <?php $titulo_documento = "Factura de Venta"; include(APPPATH . 'Views/pdf/inc/header.php'); ?>
    <div class="doc-number">#<?php echo $venta->id; ?></div>
</div>

<div class="footer-fixed">
    <?php include(APPPATH . 'Views/pdf/inc/footer.php'); ?>
</div>

 <div class="content">
    <div class="customer-box">
        <table width="100%">
            <tr>
                <td width="60%">
                    <span class="label-min">Cliente / Razón Social</span>
                    <span class="val-bold"><?php echo $venta->cliente_nombre; ?></span>
                </td>
                <td width="40%">
                    <span class="label-min">Vehículo - Placa</span>
                    <span class="val-bold"><?php echo $venta->modelo_vehiculo ?: 'N/A'; ?> [ <?php echo $venta->placa ?: 'N/A'; ?> ]</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th width="60px" class="text-right">Cant.</th>
                <th width="100px" class="text-right">Unitario</th>
                <th width="100px" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($venta->items as $item): ?>
            <tr>
                <td><?php echo $item->descripcion; ?></td>
                <td class="text-right"><?php echo $item->cantidad; ?></td>
                <td class="text-right">$ <?php echo number_format($item->precio_unitario, 2); ?></td>
                <td class="text-right" style="font-weight: bold;">$ <?php echo number_format($item->cantidad * $item->precio_unitario, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span class="total-label">Subtotal</span>
            <span class="total-val">$ <?php echo number_format($venta->total, 2); ?></span>
        </div>
        <div class="total-row">
            <span class="total-label" style="font-size: 12px;">Total Pagado</span>
            <span class="total-val grand-total">$ <?php echo number_format($venta->total, 2); ?></span>
        </div>
    </div>

    <?php if (isset($venta->status) && $venta->status === 'CREDITO'): ?>
    <div class="text-right" style="margin-top: 10px; font-size: 11px; color: #475569; border-top: 1px dashed #e2e8f0; padding-top: 10px;">
        <span>Monto Pagado: $<?php echo number_format($venta->pago_efectivo + $venta->pago_transferencia, 2); ?></span><br>
        <span style="color: #ef4444; font-weight: bold; font-size: 12px;">SALDO PENDIENTE: $<?php echo number_format($venta->saldo_pendiente, 2); ?></span>
    </div>
    <?php endif; ?>
</div>