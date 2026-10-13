<?php
// Vista: email/orden_servicio_cambio_estado.php
// Datos esperados: $cliente_nombre, $orden_id, $id_formateado, $placa, $vehiculo, $estado_anterior, $estado_nuevo,
//                  $fecha_cambio, $comentario, $mecanico_nombre, $empresa

$titulo = 'Actualización de Orden de Servicio';
$contenido = '';

// Mapeo de estados a colores e íconos
$estadosInfo = [
    'RECIBIDO'        => ['color' => '#f59e0b', 'icono' => '📥', 'label' => 'RECIBIDO'],
    'DIAGNOSTICANDO'  => ['color' => '#8b5cf6', 'icono' => '🔍', 'label' => 'EN DIAGNÓSTICO'],
    'EN_REPARACION'   => ['color' => '#3b82f6', 'icono' => '🔧', 'label' => 'EN REPARACIÓN'],
    'LISTO'           => ['color' => '#10b981', 'icono' => '✅', 'label' => 'LISTO'],
    'ENTREGADO'       => ['color' => '#059669', 'icono' => '🚗', 'label' => 'ENTREGADO'],
    'CANCELADO'       => ['color' => '#ef4444', 'icono' => '❌', 'label' => 'CANCELADO'],
];

$infoNuevo = $estadosInfo[$estado_nuevo] ?? ['color' => '#6b7280', 'icono' => '📋', 'label' => $estado_nuevo];
$infoAnterior = $estadosInfo[$estado_anterior] ?? ['color' => '#6b7280', 'icono' => '📋', 'label' => $estado_anterior];

ob_start();
?>

<h2>¡Hola, <?= htmlspecialchars($cliente_nombre) ?>!</h2>

<p>Tu <strong>Orden de Servicio</strong> ha cambiado de estado. A continuación te mostramos la actualización:</p>

<div class="info-box">
    <p><strong>Orden de Servicio N°:</strong> <?= htmlspecialchars($id_formateado ?? 'OS-' . str_pad($orden_id, 6, '0', STR_PAD_LEFT)) ?></p>
    <p><strong>Placa:</strong> <?= htmlspecialchars($placa) ?></p>
    <p><strong>Vehículo:</strong> <?= htmlspecialchars($vehiculo) ?></p>
    <?php if (!empty($mecanico_nombre)): ?>
    <p><strong>Técnico:</strong> <?= htmlspecialchars($mecanico_nombre) ?></p>
    <?php endif; ?>
    <p><strong>Fecha de Actualización:</strong> <?= htmlspecialchars($fecha_cambio ?? date('d/m/Y h:i A')) ?></p>
</div>

<!-- Indicador visual del cambio de estado -->
<div style="text-align:center; margin:24px 0;">
    <div style="display:inline-block; background-color:<?= $infoAnterior['color'] ?>; color:white; padding:8px 16px; border-radius:20px; font-weight:600; font-size:14px; margin:0 8px;">
        <?= $infoAnterior['icono'] ?> <?= $infoAnterior['label'] ?>
    </div>
    <span style="font-size:20px; color:#6b7280;">➡️</span>
    <div style="display:inline-block; background-color:<?= $infoNuevo['color'] ?>; color:white; padding:8px 16px; border-radius:20px; font-weight:600; font-size:14px; margin:0 8px;">
        <?= $infoNuevo['icono'] ?> <?= $infoNuevo['label'] ?>
    </div>
</div>

<?php if (!empty($comentario)): ?>
<div style="background-color:#f9fafb; border:1px solid #e5e7eb; padding:16px 20px; margin:20px 0; border-radius:8px;">
    <p style="margin:0 0 6px 0; font-weight:600; color:#374151;">📝 Comentario:</p>
    <p style="margin:0; color:#6b7280;"><?= nl2br(htmlspecialchars($comentario)) ?></p>
</div>
<?php endif; ?>

<p>Te mantendremos informado sobre cualquier novedad en tu orden de servicio.</p>

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