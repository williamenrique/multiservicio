<?php
// Vista: email/orden_servicio_creada.php
// Datos esperados: $cliente_nombre, $orden_id, $placa, $vehiculo, $kilometraje, $nivel_combustible,
//                   $mecanico_nombre, $fecha_ingreso, $fecha_entrega_estimada, $observaciones, $items, $empresa

$titulo = '¡Orden de Servicio Creada!';
$contenido = '';
ob_start();
?>

<h2>¡Hola, <?= htmlspecialchars($cliente_nombre) ?>!</h2>

<p>Hemos creado una <strong>Orden de Servicio</strong> para tu vehículo. A continuación te mostramos los detalles:</p>

<div class="info-box">
    <p><strong>Orden de Servicio N°:</strong> <?= htmlspecialchars($id_formateado ?? 'OS-' . str_pad($orden_id, 6, '0', STR_PAD_LEFT)) ?></p>
    <p><strong>Placa:</strong> <?= htmlspecialchars($placa) ?></p>
    <p><strong>Vehículo:</strong> <?= htmlspecialchars($vehiculo) ?></p>
    <p><strong>Kilometraje:</strong> <?= htmlspecialchars($kilometraje ?? 'N/A') ?></p>
    <p><strong>Nivel de Combustible:</strong> <?= htmlspecialchars($nivel_combustible ?? 'N/A') ?></p>
    <p><strong>Fecha de Ingreso:</strong> <?= htmlspecialchars($fecha_ingreso ?? date('d/m/Y h:i A')) ?></p>
    <?php if (!empty($fecha_entrega_estimada)): ?>
    <p><strong>Fecha Estimada de Entrega:</strong> <?= htmlspecialchars($fecha_entrega_estimada) ?></p>
    <?php endif; ?>
    <?php if (!empty($mecanico_nombre)): ?>
    <p><strong>Técnico Asignado:</strong> <?= htmlspecialchars($mecanico_nombre) ?></p>
    <?php endif; ?>
    <p><strong>Estado:</strong> <span class="highlight" style="background:#f59e0b;color:white;padding:4px 12px;border-radius:20px;">RECIBIDO</span></p>
</div>

<?php if (!empty($observaciones)): ?>
<h2>Observaciones de Entrada</h2>
<p><?= nl2br(htmlspecialchars($observaciones)) ?></p>
<?php endif; ?>

<?php if (!empty($items) && is_array($items)): ?>
<h2>Servicios / Repuestos Solicitados</h2>
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
            <td colspan="3" style="text-align:right;">TOTAL ESTIMADO:</td>
            <td>$<?= number_format($total ?? 0, 2) ?></td>
        </tr>
    </tfoot>
</table>
<?php endif; ?>

<p>Te mantendremos informado sobre el avance de tu orden de servicio. Cualquier cambio de estado te será notificado por este medio.</p>

<?php
$telefono = $empresa->telefono ?? '';
$telefonoWhatsApp = $telefono ? preg_replace('/[^0-9]/', '', $telefono) : '';
if ($telefonoWhatsApp && strlen($telefonoWhatsApp) < 11) {
    $telefonoWhatsApp = '58' . $telefonoWhatsApp;
}
?>
<?php if (!empty($empresa->name) || !empty($telefonoWhatsApp)): ?>
<div style="background-color:#f0fdf4; border-left:4px solid #10b981; padding:16px 20px; margin:20px 0; border-radius:0 8px 8px 0;">
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

<?php
$contenido = ob_get_clean();
include __DIR__ . '/layout.php';