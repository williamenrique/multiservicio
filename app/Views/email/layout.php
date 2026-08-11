<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? SITENAME ?></title>
    <style>
        /* Reset y estilos base inline para máxima compatibilidad con clientes de correo */
        body { margin: 0; padding: 0; background-color: #f4f6f9; font-family: 'Segoe UI', Arial, Helvetica, sans-serif; }
        .email-wrapper { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .email-header { background: linear-gradient(135deg, #1a56db 0%, #1e40af 100%); padding: 30px 40px; text-align: center; }
        .email-header img { max-height: 50px; margin-bottom: 8px; }
        .email-header h1 { color: #ffffff; font-size: 20px; margin: 8px 0 0 0; font-weight: 600; }
        .email-body { padding: 30px 40px; color: #333333; font-size: 15px; line-height: 1.6; }
        .email-body h2 { color: #1a56db; font-size: 18px; margin: 0 0 16px 0; }
        .email-body p { margin: 0 0 14px 0; }
        .info-box { background-color: #f0f7ff; border-left: 4px solid #1a56db; padding: 16px 20px; margin: 20px 0; border-radius: 0 8px 8px 0; }
        .info-box p { margin: 4px 0; font-size: 14px; }
        .info-box strong { color: #1a56db; }
        table.items { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; }
        table.items thead th { background-color: #1a56db; color: #ffffff; padding: 12px 14px; text-align: left; font-weight: 600; }
        table.items thead th:last-child, table.items thead th:nth-last-child(2) { text-align: right; }
        table.items tbody td { padding: 10px 14px; border-bottom: 1px solid #e5e7eb; }
        table.items tbody td:last-child, table.items tbody td:nth-last-child(2) { text-align: right; }
        table.items tbody tr:last-child td { border-bottom: none; }
        table.items tfoot td { padding: 12px 14px; font-weight: 700; font-size: 16px; }
        table.items tfoot td:last-child { text-align: right; color: #1a56db; }
        .total-row { background-color: #f0f7ff; }
        .email-footer { background-color: #f9fafb; padding: 20px 40px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
        .email-footer p { margin: 4px 0; }
        .btn { display: inline-block; background-color: #1a56db; color: #ffffff !important; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 15px; margin: 10px 0; }
        .highlight { color: #1a56db; font-weight: 600; }
        @media only screen and (max-width: 480px) {
            .email-header { padding: 20px 20px; }
            .email-body { padding: 20px 20px; }
            .email-footer { padding: 16px 20px; }
            table.items { font-size: 12px; }
            table.items thead th, table.items tbody td { padding: 8px 10px; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9;padding:20px 0;">
        <tr>
            <td align="center">
                <div class="email-wrapper">
                    <!-- HEADER -->
                    <div class="email-header">
                        <?php if (!empty($empresa->logo)): ?>
                            <img src="<?= URLROOT . '/uploads/' . htmlspecialchars($empresa->logo) ?>" alt="<?= htmlspecialchars($empresa->name ?? SITENAME) ?>" style="max-width:180px;height:auto;">
                        <?php endif; ?>
                        <h1><?= htmlspecialchars($empresa->name ?? SITENAME) ?></h1>
                    </div>

                    <!-- BODY -->
                    <div class="email-body">
                        <?= $contenido ?? '' ?>
                    </div>

                    <!-- FOOTER -->
                    <div class="email-footer">
                        <p><strong><?= htmlspecialchars($empresa->name ?? SITENAME) ?></strong></p>
                        <?php if (!empty($empresa->direccion)): ?>
                            <p><?= htmlspecialchars($empresa->direccion) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($empresa->telefono)): ?>
                            <p>Tel: <?= htmlspecialchars($empresa->telefono) ?></p>
                        <?php endif; ?>
                        <p style="margin-top:8px;">Este es un correo automático, por favor no responda a este mensaje.</p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>