<?php
// Vista: email/orden_servicio_lista.php
// Datos esperados: $cliente_nombre, $orden_id, $id_formateado, $placa, $vehiculo, $fecha_entrega,
// $mecanico_nombre, $items, $total, $empresa

$titulo = '¡Tu vehículo está listo!';
$contenido = '';
ob_start();
?>

<h2>¡Excelentes noticias, <?= htmlspecialchars($cliente_nombre) ?>!</h2>

<p>Tu <strong>Orden de Servicio</strong> ha sido completada y tu vehículo está <strong>LISTO para ser retirado</strong>.</p>

<div class="info-box">
    <p><strong>Orden de Servicio N°:</strong> <?= htmlspecialchars($id_formateado ?? 'OS-' . str_pad($orden_id, 6, '0', STR_PAD_LEFT)) ?></p>
    <p><strong>Placa:</strong> <?= htmlspecialchars($placa) ?></p>
    <p><strong>Vehículo:</strong> <?= htmlspecialchars($vehiculo) ?></p>
    <?php if (!empty($mecanico_nombre)): ?>
    <p><strong>Técnico Responsable:</strong> <?= htmlspecialchars($mecanico_nombre) ?></p>
    <?php endif; ?>
    <p><strong>Fecha de Finalización:</strong> <?= htmlspecialchars($fecha_entrega ?? date('d/m/Y h:i A')) ?></p>
    <p><strong>Estado:</strong> <span class="highlight" style="background:#10b981;color:white;padding:4px 12px;border-radius:20px;">✅ LISTO PARA ENTREGAR</span></p>
</div>

<?php if (!empty($items) && is_array($items)): ?>
<h2>Resumen de Servicios Realizados</h2>
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
        <tr>
            <td><?= htmlspecialchars($item['descripcion'] ?? $item['nombre'] ?? 'Ítem') ?></td>
            <td><?= (int)($item['cantidad'] ?? 1) ?></td>
            <td>$<?= number_format($item['precio_unitario'] ?? $item['precio'] ?? 0, 2) ?></td>
            <td>$<?= number_format(($item['precio_unitario'] ?? $item['precio'] ?? 0) * ($item['cantidad'] ?? 1), 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="3" style="text-align:right;">TOTAL:</td>
            <td>$<?= number_format($total ?? 0, 2) ?></td>
        </tr>
    </tfoot>
</table>
<?php endif; ?>

<div style="background-color:#fef3c7; border-left:4px solid #f59e0b; padding:16px 20px; margin:20px 0; border-radius:0 8px 8px 0;">
    <p style="margin:0; font-weight:600; color:#d97706; font-size:15px;">🚗 ¡Importante!</p>
    <p style="margin:8px 0 0 0; font-size:14px;">Por favor, acércate a nuestro taller para retirar tu vehículo. Recuerda traer tu documento de identidad.</p>
</div>

<?php
$telefono = $empresa->telefono ?? '';
$telefonoWhatsApp = $telefono ? preg_replace('/[^0-9]/', '', $telefono) : '';
if ($telefonoWhatsApp && strlen($telefonoWhatsApp) < 11) {
    $telefonoWhatsApp = '58' . $telefonoWhatsApp;
}
?>
<?php if (!empty($empresa->name) || !empty($telefonoWhatsApp)): ?>
<div style="background-color:#f0fdf4; border-left:4px solid #10b981; padding:16px 20px; margin:20px 0; border-radius:0 8px 8px 0;">
    <p style="margin:0 0 8px 0; font-weight:600; color:#10b981; font-size:15px;">📞 ¿Tienes dudas?</p>
    <?php if (!empty($empresa->name)): ?>
    <p style="margin:4px 0; font-size:14px;">Contacta a <strong><?= htmlspecialchars($empresa->name) ?></strong></p>
    <?php endif; ?>
    <?php if (!empty($empresa->direccion)): ?>
    <p style="margin:4px 0; font-size:14px;">📍 <?= htmlspecialchars($empresa->direccion) ?></p>
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

<?php
$contenido = ob_get_clean();
include __DIR__ . '/layout.php';