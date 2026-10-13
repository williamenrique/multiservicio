<?php
/**
 * Plantilla HTML por defecto para el cuerpo del compose.
 * Se prellena en el textarea "Cuerpo (HTML)" del modal "Nuevo Email".
 * 
 * Este archivo es la fuente única del HTML por defecto. Puede editarse
 * libremente — los datos del taller se imprimen aquí como literales (no
 * como variables), porque el compose genérico NO resuelve variables.
 * 
 * Si el usuario borra el contenido y hace clic en "Restaurar por defecto",
 * se vuelve a cargar este HTML.
 */
?>
<h2>Estimado/a cliente,</h2>

<p>Reciba un cordial saludo de parte de <strong><?php echo htmlspecialchars($empresa->name ?? 'TALLER PRO'); ?></strong>.</p>

<p>Nos complace atenderle. A continuación le compartimos la información:</p>

<div class="info-box">
    <p><strong>Empresa:</strong> <?php echo htmlspecialchars($empresa->name ?? 'TALLER PRO'); ?></p>
    <?php if (!empty($empresa->nit)): ?>
    <p><strong>NIT:</strong> <?php echo htmlspecialchars($empresa->nit); ?></p>
    <?php endif; ?>
    <?php if (!empty($empresa->direccion)): ?>
    <p><strong>Dirección:</strong> <?php echo htmlspecialchars($empresa->direccion); ?></p>
    <?php endif; ?>
    <?php if (!empty($empresa->telefono)): ?>
    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($empresa->telefono); ?></p>
    <?php endif; ?>
    <?php if (!empty($empresa->email)): ?>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($empresa->email); ?></p>
    <?php endif; ?>
</div>

<p>Quedamos a su disposición para cualquier consulta.</p>

<p>Cordialmente,<br>
<strong><?php echo htmlspecialchars($empresa->name ?? 'TALLER PRO'); ?></strong></p>