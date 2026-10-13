<?php
// Vista: email/pedido_catalogo_admin.php
// Datos esperados: $cliente_nombre, $cliente_email, $cliente_telefono, $cliente_cedula,
//                  $venta_formateado, $fecha, $items, $subtotal, $iva, $total, $empresa

$titulo = 'Nuevo pedido de catálogo';
$contenido = '';
ob_start();
?>

<h2>🔔 Nuevo pedido recibido desde el catálogo</h2>

<p>Se ha registrado un nuevo pedido de repuestos a través del catálogo público. Revisa los detalles:</p>

<div class="info-box">
    <p><strong>Factura N°:</strong> <?= htmlspecialchars($venta_formateado) ?></p>
    <p><strong>Pedido N°:</strong> <?= htmlspecialchars($id_formateado ?? 'N/A') ?></p>
    <p><strong>Fecha:</strong> <?= htmlspecialchars($fecha ?? date('d/m/Y h:i A')) ?></p>
    <p><strong>Origen:</strong> CATÁLOGO PÚBLICO</p>
</div>

<h2>Datos del cliente</h2>

<div class="info-box">
    <p><strong>Nombre:</strong> <?= htmlspecialchars($cliente_nombre) ?></p>
    <p><strong>Cédula:</strong> <?= htmlspecialchars($cliente_cedula ?? 'No registrada') ?></p>
    <p><strong>Correo:</strong> <?= htmlspecialchars($cliente_email) ?></p>
    <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente_telefono ?? 'No registrado') ?></p>
    <?php if (!empty($cliente_direccion)): ?>
    <p><strong>Dirección:</strong> <?= htmlspecialchars($cliente_direccion) ?></p>
    <?php endif; ?>
</div>

<h2>Productos solicitados</h2>

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

<p style="text-align:center;margin-top:20px;">
    <a href="<?= URLROOT ?>/facturacion/ver/<?= htmlspecialchars($venta_id ?? '') ?>" class="btn">Ver factura en el sistema</a>
</p>

<?php
$contenido = ob_get_clean();
include __DIR__ . '/layout.php';