<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $titulo_pestaña ?? 'Recibo de Abono'; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1e293b; padding: 20px; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #0f172a; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #0f172a; text-transform: uppercase; }
        .header .doc-title { text-align: right; }
        .header .doc-title h2 { color: #10b981; font-size: 18px; }
        .header .doc-title .doc-num { font-size: 14px; color: #64748b; font-weight: bold; }
        .section { margin-bottom: 15px; }
        .section-title { font-size: 10px; font-weight: 900; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; }
        .grid-2 { display: table; width: 100%; }
        .grid-2 > div { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
        .info-label { font-size: 9px; color: #94a3b8; text-transform: uppercase; font-weight: bold; }
        .info-value { font-size: 12px; font-weight: bold; color: #0f172a; }
        .amount-box { background: #10b981; color: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .amount-box .label { font-size: 10px; text-transform: uppercase; opacity: 0.85; }
        .amount-box .amount { font-size: 32px; font-weight: 900; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th { background: #0f172a; color: white; padding: 8px; font-size: 10px; text-transform: uppercase; text-align: left; }
        table td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        table tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 9px; font-weight: 900; text-transform: uppercase; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }
        .signature { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature > div { width: 45%; border-top: 1px solid #0f172a; padding-top: 5px; text-align: center; font-size: 10px; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <div>
            <h1><?php echo $empresa->name ?? 'TALLER PRO'; ?></h1>
            <?php if (!empty($empresa->nit)): ?><p style="font-size: 10px; color: #64748b;">NIT: <?php echo $empresa->nit; ?></p><?php endif; ?>
            <?php if (!empty($empresa->direccion)): ?><p style="font-size: 10px; color: #64748b;"><?php echo $empresa->direccion; ?></p><?php endif; ?>
            <?php if (!empty($empresa->telefono)): ?><p style="font-size: 10px; color: #64748b;">Tel: <?php echo $empresa->telefono; ?></p><?php endif; ?>
        </div>
        <div class="doc-title">
            <h2>RECIBO DE ABONO</h2>
            <p class="doc-num">N° <?php echo str_pad($abono->abono_id, 5, '0', STR_PAD_LEFT); ?></p>
            <p style="font-size: 10px; color: #64748b; margin-top: 4px;">
                <?php echo date('d/m/Y H:i A', strtotime($abono->abono_fecha)); ?>
            </p>
        </div>
    </div>

    <!-- CLIENTE + FACTURA -->
    <div class="section">
        <div class="grid-2">
            <div>
                <p class="section-title">Cliente</p>
                <div class="info-box">
                    <p class="info-value"><?php echo htmlspecialchars($abono->cliente_nombre); ?></p>
                    <?php if (!empty($abono->cliente_telefono)): ?><p style="font-size: 10px; color: #64748b; margin-top: 3px;">Tel: <?php echo $abono->cliente_telefono; ?></p><?php endif; ?>
                    <?php if (!empty($abono->cliente_email)): ?><p style="font-size: 10px; color: #64748b;"><?php echo $abono->cliente_email; ?></p><?php endif; ?>
                    <?php if (!empty($abono->cliente_direccion)): ?><p style="font-size: 10px; color: #64748b; margin-top: 3px;"><?php echo $abono->cliente_direccion; ?></p><?php endif; ?>
                </div>
            </div>
            <div>
                <p class="section-title">Factura Afectada</p>
                <div class="info-box">
                    <p class="info-value"><?php echo $abono->factura_formateada; ?></p>
                    <p style="font-size: 10px; color: #64748b; margin-top: 3px;">
                        Emitida: <?php echo date('d/m/Y', strtotime($abono->factura_fecha)); ?>
                    </p>
                    <?php if (!empty($abono->placa) && $abono->placa !== '---'): ?>
                        <p style="font-size: 10px; color: #64748b;">Vehículo: <?php echo $abono->placa; ?> <?php echo $abono->modelo_vehiculo; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- MONTO ABONADO (destacado) -->
    <div class="amount-box">
        <p class="label">Monto Abonado</p>
        <p class="amount">$<?php echo number_format($abono->abono_monto, 0, ',', '.'); ?></p>
        <p style="font-size: 11px; margin-top: 6px; opacity: 0.95;">
            <span class="badge" style="background: rgba(255,255,255,0.25); color: white;">
                <?php echo $abono->abono_metodo; ?>
            </span>
        </p>
    </div>

    <!-- ESTADO DE LA FACTURA -->
    <div class="section">
        <p class="section-title">Estado de la Factura</p>
        <table>
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Original</td>
                    <td class="text-right">$<?php echo number_format($abono->factura_total, 0, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td>Pago Efectivo Acumulado</td>
                    <td class="text-right">$<?php echo number_format($abono->pago_efectivo, 0, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td>Pago Transferencia Acumulado</td>
                    <td class="text-right">$<?php echo number_format($abono->pago_transferencia, 0, ',', '.'); ?></td>
                </tr>
                <tr style="background: #fef3c7;">
                    <td><strong>SALDO PENDIENTE</strong></td>
                    <td class="text-right"><strong style="color: #dc2626;">$<?php echo number_format($abono->saldo_pendiente, 0, ',', '.'); ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- FIRMAS -->
    <div class="signature">
        <div>
            <p>Firma del Cliente</p>
        </div>
        <div>
            <p>Registrado por: <?php echo htmlspecialchars($abono->usuario_nombre ?? 'SISTEMA'); ?></p>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <p>Recibo generado automáticamente por el sistema <?php echo $empresa->name ?? 'TALLER PRO'; ?></p>
        <p>Documento válido como comprobante de pago parcial. Conserve este recibo.</p>
        <p style="margin-top: 5px; font-size: 8px;">Recibo #<?php echo $abono->abono_id; ?> — Generado el <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

</body>
</html>