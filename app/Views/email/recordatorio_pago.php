<?php
/**
 * Plantilla de email: RECORDATORIO DE PAGO
 * 
 * Se envía manualmente al cliente desde el botón "Recordatorio" en Facturas.
 * 
 * Variables esperadas:
 *   $empresa            (object)  Datos de la empresa
 *   $cliente_nombre     (string)
 *   $id_formateado      (string)  Ej: 'FAC-013'
 *   $factura_id         (int)
 *   $placa              (string)
 *   $modelo_vehiculo    (string)
 *   $total              (float)
 *   $saldo_pendiente    (float)
 *   $dias_atraso        (int)
 *   $fecha_emision      (string)
 */
$fmt = fn($n) => '$' . number_format((float)$n, 0, ',', '.');
$dias = (int)($dias_atraso ?? 0);

if ($dias <= 7) {
    $urgencia = 'info';
    $color = '#3b82f6';
    $colorBg = '#eff6ff';
    $colorBorder = '#bfdbfe';
    $titulo = 'Recordatorio Amable';
    $mensaje = 'Le recordamos amablemente que tiene un saldo pendiente con nosotros.';
} elseif ($dias <= 30) {
    $urgencia = 'warning';
    $color = '#f59e0b';
    $colorBg = '#fffbeb';
    $colorBorder = '#fde68a';
    $titulo = 'Atención Requerida';
    $mensaje = 'Queremos recordarle que su saldo ha estado pendiente por más de 2 semanas. Agradecemos su pronta gestión.';
} else {
    $urgencia = 'danger';
    $color = '#dc2626';
    $colorBg = '#fef2f2';
    $colorBorder = '#fecaca';
    $titulo = 'Gestión Urgente de Cobro';
    $mensaje = 'Su saldo pendiente ha superado los 30 días. Le solicitamos regularizar su situación a la brevedad posible para evitar recargos administrativos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recordatorio de Pago</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family: Arial, Helvetica, sans-serif; color:#1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f5f9; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    
                    <!-- HEADER -->
                    <tr>
                        <td style="background-color:#0f172a; padding: 30px 40px; text-align:left;">
                            <h1 style="margin:0; color:#39FF14; font-size:22px; letter-spacing: 1px; text-transform: uppercase;">
                                <?php echo htmlspecialchars($empresa->name ?? 'TALLER PRO'); ?>
                            </h1>
                            <p style="margin: 6px 0 0 0; color:#94a3b8; font-size:12px; text-transform: uppercase; letter-spacing: 2px;">
                                Recordatorio de Pago
                            </p>
                        </td>
                    </tr>

                    <!-- BANNER DE URGENCIA -->
                    <tr>
                        <td style="padding: 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: <?php echo $colorBg; ?>; border-bottom: 1px solid <?php echo $colorBorder; ?>;">
                                <tr>
                                    <td style="padding: 24px 40px;">
                                        <p style="margin:0 0 8px 0; font-size: 15px; color: <?php echo $color; ?>; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">
                                            <?php echo htmlspecialchars($titulo); ?>
                                        </p>
                                        <p style="margin:0; font-size: 13px; line-height: 1.6; color:#334155;">
                                            <?php echo htmlspecialchars($mensaje); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- BODY -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin:0 0 20px 0; font-size:16px; color:#334155;">
                                Estimado/a <strong><?php echo htmlspecialchars($cliente_nombre ?? 'Cliente'); ?></strong>,
                            </p>
                            <p style="margin:0 0 30px 0; font-size:14px; line-height:1.6; color:#475569;">
                                Según nuestros registros, usted mantiene un saldo pendiente correspondiente a la
                                factura <strong style="color:#0f172a;"><?php echo htmlspecialchars($id_formateado ?? ('FAC-' . str_pad((string)($factura_id ?? ''), 3, '0', STR_PAD_LEFT))); ?></strong>.
                            </p>

                            <!-- CAJA DE SALDO -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: <?php echo $colorBg; ?>; border: 2px solid <?php echo $colorBorder; ?>; border-radius: 12px; margin-bottom: 30px;">
                                <tr>
                                    <td align="center" style="padding: 30px 20px;">
                                        <p style="margin:0; color: <?php echo $color; ?>; font-size:12px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;">
                                            Saldo Pendiente
                                        </p>
                                        <p style="margin: 8px 0 0 0; color: <?php echo $color; ?>; font-size:42px; font-weight: 900; letter-spacing: -1px;">
                                            <?php echo $fmt($saldo_pendiente ?? 0); ?>
                                        </p>
                                        <p style="margin: 10px 0 0 0; font-size: 12px; color:#64748b;">
                                            <?php if ($dias > 0): ?>
                                                <span style="display: inline-block; padding: 4px 10px; background-color: <?php echo $colorBg; ?>; color: <?php echo $color; ?>; border-radius: 20px; font-weight: bold; text-transform: uppercase; font-size: 10px; letter-spacing: 1px;">
                                                    <?php echo $dias; ?> día<?php echo $dias === 1 ? '' : 's'; ?> desde la emisión
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- DETALLES DE LA FACTURA -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px 0; font-size: 11px; color:#94a3b8; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">
                                            Detalles de la Deuda
                                        </p>

                                        <?php if (!empty($fecha_emision)): ?>
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 8px;">
                                            <tr>
                                                <td style="font-size:13px; color:#64748b; padding: 4px 0;">Fecha de Emisión:</td>
                                                <td style="font-size:13px; color:#0f172a; font-weight:bold; text-align:right; padding: 4px 0;">
                                                    <?php echo date('d/m/Y', strtotime($fecha_emision)); ?>
                                                </td>
                                            </tr>
                                        </table>
                                        <?php endif; ?>

                                        <?php if (!empty($placa) && $placa !== '---'): ?>
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 8px;">
                                            <tr>
                                                <td style="font-size:13px; color:#64748b; padding: 4px 0;">Vehículo:</td>
                                                <td style="font-size:13px; color:#0f172a; font-weight:bold; text-align:right; padding: 4px 0;">
                                                    <?php echo htmlspecialchars(($modelo_vehiculo ?? '') . ' [' . $placa . ']'); ?>
                                                </td>
                                            </tr>
                                        </table>
                                        <?php endif; ?>

                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="font-size:13px; color:#64748b; padding: 4px 0;">Total Facturado:</td>
                                                <td style="font-size:13px; color:#334155; font-weight:bold; text-align:right; padding: 4px 0;">
                                                    <?php echo $fmt($total ?? 0); ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 30px;">
                                <tr>
                                    <td align="center">
                                        <p style="margin: 0 0 16px 0; font-size: 13px; color:#64748b;">
                                            Para coordinar su pago, contáctenos:
                                        </p>
                                        <?php if (!empty($empresa->telefono)): ?>
                                        <p style="margin: 0 0 8px 0;">
                                            <a href="tel:<?php echo htmlspecialchars($empresa->telefono); ?>" style="display: inline-block; padding: 12px 24px; background-color: #0f172a; color: #39FF14; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 14px; letter-spacing: 1px; text-transform: uppercase;">
                                                📞 <?php echo htmlspecialchars($empresa->telefono); ?>
                                            </a>
                                        </p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background-color:#f8fafc; padding: 24px 40px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <?php if (!empty($empresa->direccion)): ?>
                            <p style="margin:0 0 12px 0; font-size: 12px; color:#64748b;">
                                📍 <?php echo htmlspecialchars($empresa->direccion); ?>
                            </p>
                            <?php endif; ?>
                            <p style="margin:0; font-size: 11px; color:#94a3b8;">
                                Este es un correo automático enviado desde <?php echo htmlspecialchars($empresa->name ?? 'TALLER PRO'); ?>.
                                Si ya realizó su pago, por favor ignore este mensaje.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>