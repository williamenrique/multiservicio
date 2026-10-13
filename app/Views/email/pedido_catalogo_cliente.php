<?php
// Vista: email/pedido_catalogo_cliente.php
// Datos esperados: $cliente_nombre, $id_formateado, $venta_formateado, $fecha, $items, $subtotal, $iva, $total, $empresa

$titulo = '¡Gracias por tu pedido!';
$contenido = '';
ob_start();
?>

<h2>¡Gracias por tu pedido, <?= htmlspecialchars($cliente_nombre) ?>!</h2>

<p>Hemos recibido tu pedido de repuestos correctamente. A continuación te mostramos el resumen:</p>

<div class="info-box">
    <p><strong>Pedido N°:</strong> <?= htmlspecialchars($id_formateado ?? 'PED-' . $venta_formateado) ?></p>
    <p><strong>Factura N°:</strong> <?= htmlspecialchars($venta_formateado) ?></p>
    <p><strong>Fecha:</strong> <?= htmlspecialchars($fecha ?? date('d/m/Y h:i A')) ?></p>
    <p><strong>Estado:</strong> <span class="highlight">PENDIENTE</span></p>
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
            <td>$<?= number_format(($item['precio_unitario'] ?? $item['precio'] ?? 0) * ($item['cantidad'] ?? 1), 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right;">Subtotal:</td>
            <td>$<?= number_format($subtotal ?? $total ?? 0, 2) ?></td>
        </tr>
        <?php if (!empty($iva) && $iva > 0): ?>
        <tr>
            <td colspan="3" style="text-align:right;">IVA (<?= number_format(($iva_tasa ?? 19), 0) ?>%):</td>
            <td>$<?= number_format($iva, 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="total-row">
            <td colspan="3" style="text-align:right;">TOTAL:</td>
            <td>$<?= number_format($total ?? 0, 2) ?></td>
        </tr>
    </tfoot>
</table>

<p>Nos pondremos en contacto contigo pronto para coordinar la entrega de tu pedido.</p>

<p>Si tienes alguna pregunta, no dudes en contactarnos a través de nuestros canales de atención.</p>

<p style="text-align:center;margin-top:20px;">
    <a href="<?= URLROOT ?>/catalogo" class="btn">Seguir comprando</a>
</p>

<?php
$contenido = ob_get_clean();
include __DIR__ . '/layout.php';