<?php
/**
 * Plantilla de email: ABONO REGISTRADO
 * 
 * Se envía al cliente cuando se registra un pago parcial o total a su factura.
 * 
 * Variables esperadas:
 *   $empresa            (object)  Datos de la empresa
 *   $cliente_nombre     (string)
 *   $id_formateado      (string)  Ej: 'FAC-013'
 *   $factura_id         (int)
 *   $placa              (string)
 *   $modelo_vehiculo    (string)
 *   $monto_abono        (float)
 *   $metodo_pago        (string)
 *   $total              (float)
 *   $pago_efectivo      (float)
 *   $pago_transferencia (float)
 *   $saldo_pendiente    (float)
 */
$fmt = fn($n) => '$' . number_format((float)$n, 0, ',', '.');
$metodoLabel = ($metodo_pago ?? '') === 'TRANSFERENCIA' ? 'Transferencia' : 'Efectivo';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Abono Registrado</title>
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
                                Confirmación de Pago
                            </p>
                        </td>
                    </tr>

                    <!-- BODY -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin:0 0 20px 0; font-size:16px; color:#334155;">
                                Estimado/a <strong><?php echo htmlspecialchars($cliente_nombre ?? 'Cliente'); ?></strong>,
                            </p>
                            <p style="margin:0 0 30px 0; font-size:14px; line-height:1.6; color:#475569;">
                                Le confirmamos que hemos recibido su abono correspondiente a la
                                factura <strong style="color:#0f172a;"><?php echo htmlspecialchars($id_formateado ?? ('FAC-' . str_pad((string)($factura_id ?? ''), 3, '0', STR_PAD_LEFT))); ?></strong>.
                                Gracias por su pago.
                            </p>

                            <!-- CAJA DEL MONTO -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; margin-bottom: 30px;">
                                <tr>
                                    <td align="center" style="padding: 30px 20px;">
                                        <p style="margin:0; color:#d1fae5; font-size:12px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;">
                                            Monto Abonado
                                        </p>
                                        <p style="margin: 8px 0 0 0; color:#ffffff; font-size:38px; font-weight: 900; letter-spacing: -1px;">
                                            <?php echo $fmt($monto_abono ?? 0); ?>
                                        </p>
                                        <p style="margin: 6px 0 0 0;">
                                            <span style="display: inline-block; padding: 4px 12px; background-color: rgba(255,255,255,0.2); color:#ffffff; border-radius: 20px; font-size:11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">
                                                <?php echo htmlspecialchars($metodoLabel); ?>
                                            </span>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- DATOS DE LA FACTURA -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px 0; font-size: 11px; color:#94a3b8; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">
                                            Detalles de la Factura
                                        </p>
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
                                                <td style="font-size:13px; color:#64748b; padding: 4px 0;">Total de la Factura:</td>
                                                <td style="font-size:13px; color:#334155; font-weight:bold; text-align:right; padding: 4px 0;">
                                                    <?php echo $fmt($total ?? 0); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:13px; color:#64748b; padding: 4px 0;">Total Pagado (acumulado):</td>
                                                <td style="font-size:13px; color:#059669; font-weight:bold; text-align:right; padding: 4px 0;">
                                                    <?php echo $fmt(($pago_efectivo ?? 0) + ($pago_transferencia ?? 0)); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0 0 0; border-top: 1px solid #e2e8f0; font-size:14px; color:#0f172a; font-weight: bold;">
                                                    Saldo Pendiente:
                                                </td>
                                                <td style="padding: 12px 0 0 0; border-top: 1px solid #e2e8f0; font-size:20px; color:<?php echo ($saldo_pendiente ?? 0) > 0.05 ? '#dc2626' : '#059669'; ?>; font-weight: 900; text-align:right;">
                                                    <?php echo $fmt($saldo_pendiente ?? 0); ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <?php if (($saldo_pendiente ?? 0) <= 0.05): ?>
                            <!-- MENSAJE DE FACTURA SALDADA -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ecfdf5; border-left: 4px solid #10b981; border-radius: 8px; margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <p style="margin:0; font-size:14px; color:#065f46; font-weight: bold;">
                                            ✅ ¡Factura pagada en su totalidad!
                                        </p>
                                        <p style="margin: 6px 0 0 0; font-size: 12px; color:#047857;">
                                            Esta factura ha quedado completamente saldada. Gracias por su preferencia.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <?php else: ?>
                            <p style="margin: 20px 0 0 0; font-size: 13px; color:#64748b; line-height: 1.6;">
                                Aún tiene un saldo pendiente. Puede acercarse a nuestras oficinas
                                o comunicarse con nosotros para coordinar su próximo pago.
                            </p>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background-color:#f8fafc; padding: 24px 40px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <?php if (!empty($empresa->telefono)): ?>
                            <p style="margin:0 0 6px 0; font-size: 12px; color:#64748b;">
                                📞 <?php echo htmlspecialchars($empresa->telefono); ?>
                            </p>
                            <?php endif; ?>
                            <?php if (!empty($empresa->direccion)): ?>
                            <p style="margin:0 0 12px 0; font-size: 12px; color:#64748b;">
                                📍 <?php echo htmlspecialchars($empresa->direccion); ?>
                            </p>
                            <?php endif; ?>
                            <p style="margin:0; font-size: 11px; color:#94a3b8;">
                                Este es un correo automático. Por favor no responda directamente.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>