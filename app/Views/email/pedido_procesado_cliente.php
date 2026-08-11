<?php
// Vista: email/pedido_procesado_cliente.php
// Datos esperados: $cliente_nombre, $id_formateado, $fecha, $items, $subtotal, $total, $empresa

$titulo = '¡Tu pedido ha sido procesado!';
$contenido = '';
ob_start();
?>

<h2>¡Tu pedido ha sido procesado, <?= htmlspecialchars($cliente_nombre) ?>!</h2>

<p>Tu pedido de repuestos ha sido. <strong>procesado exitosamente</strong> A continuación te mostramos el resumen:</p>

<div class="info-box">
    <p><strong>Pedido N°:</strong> <?= htmlspecialchars($id_formateado) ?></p>
    <p><strong>Fecha de procesamiento:</strong> <?= htmlspecialchars($fecha ?? date('d/m/Y h:i A')) ?></p>
    <p><strong>Estado:</strong> <span class="highlight"
            style="background:#10b981;color:white;padding:4px 12px;border-radius:20px;">PROCESADO</span></p>
</div>

<h2>Detalle de tu pedido</h2>

<table class="items">
    <thead>
        <tr>
            <th>Producto</th>
            <th>Cant.</th>
            <th>Precio</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['descripcion'] ?? $item['nombre'] ?? 'Producto') ?></td>
            <td><?= (int)($item['cantidad'] ?? 1) ?></td>
            <td>$<?= number_format($item['precio_unitario'] ?? $item['precio'] ?? 0, 2) ?></td>
            <td>$<?= number_format(($item['precio_unitario'] ?? $item['precio'] ?? 0) * ($item['cantidad'] ?? 1), 2) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right;">Subtotal:</td>
            <td>$<?= number_format($subtotal ?? $total ?? 0, 2) ?></td>
        </tr>
        <tr class="total-row">
            <td colspan="3" style="text-align:right;">TOTAL:</td>
            <td>$<?= number_format($total ?? 0, 2) ?></td>
        </tr>
    </tfoot>
</table>

<p>Tu pedido ya está siendo preparado. Nos pondremos en contacto contigo para coordinar la entrega.</p>

<p>Si tienes alguna pregunta, no dudes en contactarnos a través de nuestros canales de atención.</p>

<?php
$telefono = $empresa->telefono ?? '';
$telefonoWhatsApp = $telefono ? preg_replace('/[^0-9]/', '', $telefono) : '';
// Asegurar código de país (58 para Venezuela por defecto)
if ($telefonoWhatsApp && strlen($telefonoWhatsApp) < 11) {
    $telefonoWhatsApp = '58' . $telefonoWhatsApp;
}
?>
<?php if (!empty($empresa->name) || !empty($telefonoWhatsApp)): ?>
<div
    style="background-color:#f0fdf4; border-left:4px solid #10b981; padding:16px 20px; margin:20px 0; border-radius:0 8px 8px 0;">
    <p style="margin:0 0 8px 0; font-weight:600; color:#10b981; font-size:15px;">📞 ¿Necesitas ayuda?</p>
    <?php if (!empty($empresa->name)): ?>
    <p style="margin:4px 0; font-size:14px;">Contacta a <strong><?= htmlspecialchars($empresa->name) ?></strong></p>
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

<p style="text-align:center;margin-top:20px;">
    <a href="<?= URLROOT ?>/catalogo" class="btn">Seguir comprando</a>
</p>

<?php
$contenido = ob_get_clean();
include __DIR__ . '/layout.php';