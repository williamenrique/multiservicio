<?php
// Vista: email/factura_directa.php
// Datos esperados: $cliente_nombre, $id_formateado, $venta_id, $placa, $modelo_vehiculo, $marca_vehiculo,
//                   $items, $subtotal, $iva_monto, $total, $pago_efectivo, $pago_transferencia, $saldo_pendiente,
//                   $status, $observaciones_factura, $vendedor_nombre, $empresa

$titulo = 'Factura de Venta — ' . SITENAME;
$contenido = '';
ob_start();
?>

<h2>¡Gracias por tu visita, <?= htmlspecialchars($cliente_nombre) ?>!</h2>

<p>Adjuntamos los detalles de la factura generada por los servicios y/o repuestos realizados.</p>

<div class="info-box">
    <p><strong>Factura N°:</strong> <?= htmlspecialchars($id_formateado ?? 'FAC-' . str_pad($venta_id ?? '', 3, '0', STR_PAD_LEFT)) ?></p>
    <?php if (!empty($placa)): ?>
    <p><strong>Vehículo:</strong> <?= htmlspecialchars($marca_vehiculo ?? '') ?> <?= htmlspecialchars($modelo_vehiculo ?? '') ?> — <?= htmlspecialchars($placa) ?></p>
    <?php endif; ?>
    <?php if (!empty($vendedor_nombre)): ?>
    <p><strong>Atendido por:</strong> <?= htmlspecialchars($vendedor_nombre) ?></p>
    <?php endif; ?>
    <p><strong>Fecha:</strong> <?= date('d/m/Y h:i A') ?></p>
</div>

<?php if (!empty($observaciones_factura)): ?>
<h2>Descripción del Trabajo Realizado</h2>
<p><?= nl2br(htmlspecialchars($observaciones_factura)) ?></p>
<?php endif; ?>

<?php if (!empty($items) && is_array($items)): ?>
<h2>Servicios / Repuestos</h2>
<table class="items">
    <thead>
        <tr>
            <th>Descripción</th>
            <th>Cant.</th>
            <th>Precio</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
        <?php
            $item = (array) $item;
            $descripcion = htmlspecialchars($item['descripcion'] ?? $item['nombre'] ?? 'Ítem');
            $cantidad = (int)($item['cantidad'] ?? 1);
            $precio = (float)($item['precio_unitario'] ?? $item['precio'] ?? 0);
            $subtotalItem = $precio * $cantidad;
        ?>
        <tr>
            <td><?= $descripcion ?></td>
            <td><?= $cantidad ?></td>
            <td>$<?= number_format($precio, 2) ?></td>
            <td>$<?= number_format($subtotalItem, 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right; font-weight:600;">SUBTOTAL:</td>
            <td style="text-align:right;">$<?= number_format((float)($subtotal ?? 0), 2) ?></td>
        </tr>
        <?php if ((float)($iva_monto ?? 0) > 0): ?>
        <tr>
            <td colspan="3" style="text-align:right; font-weight:600;">IVA:</td>
            <td style="text-align:right;">$<?= number_format((float)$iva_monto, 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="total-row">
            <td colspan="3" style="text-align:right; font-weight:700; font-size:16px;">TOTAL:</td>
            <td style="text-align:right; font-weight:700; font-size:16px; color:#1a56db;">$<?= number_format((float)($total ?? 0), 2) ?></td>
        </tr>
    </tfoot>
</table>
<?php endif; ?>

<?php
// Determinar el estado del pago
$pagoEfectivo = (float)($pago_efectivo ?? 0);
$pagoTransferencia = (float)($pago_transferencia ?? 0);
$totalPagado = $pagoEfectivo + $pagoTransferencia;
$saldo = (float)($saldo_pendiente ?? 0);
$totalFactura = (float)($total ?? 0);

if ($saldo <= 0.05 && $totalPagado > 0):
    $estadoPago = 'CANCELADO';
    $colorPago = '#10b981';
    $iconoPago = '✅';
elseif ($saldo > 0.05 && $totalPagado > 0):
    $estadoPago = 'ABONO (' . number_format($saldo, 2) . ' PENDIENTE)';
    $colorPago = '#f59e0b';
    $iconoPago = '🟡';
else:
    $estadoPago = 'PENDIENTE DE PAGO';
    $colorPago = '#ef4444';
    $iconoPago = '🔴';
endif;
?>

<h2>Resumen de Pago</h2>
<table class="items">
    <tbody>
        <?php if ($pagoEfectivo > 0): ?>
        <tr>
            <td>Pago en Efectivo</td>
            <td style="text-align:right;">$<?= number_format($pagoEfectivo, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($pagoTransferencia > 0): ?>
        <tr>
            <td>Pago por Transferencia</td>
            <td style="text-align:right;">$<?= number_format($pagoTransferencia, 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td style="font-weight:700; font-size:15px;">Estado del Pago</td>
            <td style="text-align:right;">
                <span style="background:<?= $colorPago ?>; color:white; padding:4px 14px; border-radius:20px; font-weight:600; font-size:14px;">
                    <?= $iconoPago ?> <?= $estadoPago ?>
                </span>
            </td>
        </tr>
        <?php if ($saldo > 0.05): ?>
        <tr>
            <td style="font-weight:600; color:#ef4444;">Saldo Pendiente</td>
            <td style="text-align:right; font-weight:700; color:#ef4444; font-size:15px;">$<?= number_format($saldo, 2) ?></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if ($saldo > 0.05): ?>
<div style="background-color:#fef2f2; border-left:4px solid #ef4444; padding:16px 20px; margin:20px 0; border-radius:0 8px 8px 0;">
    <p style="margin:0; font-size:14px; color:#991b1b;">
        <strong>📋 Recuerda:</strong> Tu factura presenta un saldo pendiente de <strong>$<?= number_format($saldo, 2) ?></strong>. 
        Puedes cancelarlo en nuestra próxima visita o mediante transferencia bancaria.
    </p>
</div>
<?php endif; ?>

<?php
$telefono = $empresa->telefono ?? '';
$telefonoWhatsApp = $telefono ? preg_replace('/[^0-9]/', '', $telefono) : '';
if ($telefonoWhatsApp && strlen($telefonoWhatsApp) < 11) {
    $telefonoWhatsApp = '58' . $telefonoWhatsApp;
}
?>
<?php if (!empty($empresa->name) || !empty($telefonoWhatsApp)): ?>
<div style="background-color:#f0fdf4; border-left:4px solid #10b981; padding:16px 20px; margin:20px 0; border-radius:0 8px 8px 0;">
    <p style="margin:0 0 8px 0; font-weight:600; color:#10b981; font-size:15px;">📞 ¿Tienes dudas sobre tu factura?</p>
    <?php if (!empty($empresa->name)): ?>
    <p style="margin:4px 0; font-size:14px;">Contáctanos a través de <strong><?= htmlspecialchars($empresa->name) ?></strong></p>
    <?php endif; ?>
    <?php if (!empty($telefonoWhatsApp)): ?>
    <p style="margin:4px 0; font-size:14px;">
        <a href="https://wa.me/<?= $telefonoWhatsApp ?>" style="color:#10b981; text-decoration:none; font-weight:600;">
            💬 <?= htmlspecialchars($empresa->telefono) ?> (WhatsApp)
        </a>
    </p>
    <?php endif; ?>
</div>
<?php endif; ?>

<p style="text-align:center; margin-top:24px; font-size:13px; color:#6b7280;">
    ¡Gracias por confiar en nosotros!<br>
    <?= htmlspecialchars($empresa->name ?? SITENAME) ?>
</p>

<?php
$contenido = ob_get_clean();
include __DIR__ . '/layout.php';