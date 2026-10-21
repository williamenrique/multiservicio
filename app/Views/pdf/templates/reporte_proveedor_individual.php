<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 12px; margin: 0; padding: 0; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 20px; }
        .company-name { font-size: 20px; font-weight: bold; color: #0f172a; text-transform: uppercase; }
        .report-title { text-align: center; font-size: 16px; font-weight: bold; margin: 20px 0; background: #f1f5f9; padding: 10px; text-transform: uppercase; }
        .section-title { background: #0f172a; color: white; padding: 5px 10px; font-weight: bold; text-transform: uppercase; font-size: 11px; margin-top: 20px; }
        .info-grid { width: 100%; margin-bottom: 20px; }
        .info-grid td { padding: 5px; vertical-align: top; }
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .summary-box td { border: 1px solid #e2e8f0; padding: 10px; text-align: center; }
        .summary-label { display: block; font-size: 9px; color: #64748b; font-weight: bold; text-transform: uppercase; }
        .summary-value { display: block; font-size: 14px; font-weight: bold; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        table.data-table td { padding: 8px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .text-danger { color: #e11d48; }
        .text-success { color: #10b981; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="section-title">Información del Proveedor</div>
    <table class="info-grid">
        <tr>
            <td width="50%">
                <span class="text-bold">Nombre/Razón Social:</span> <?php echo $proveedor->nombre; ?><br>
                <span class="text-bold">NIT/ID:</span> <?php echo $proveedor->id; ?><br>
                <span class="text-bold">Teléfono:</span> <?php echo $proveedor->telefono; ?>
            </td>
            <td width="50%">
                <span class="text-bold">Email:</span> <?php echo $proveedor->email; ?><br>
                <span class="text-bold">Dirección:</span> <?php echo $proveedor->direccion; ?>
            </td>
        </tr>
    </table>

       <?php
    // ─── FIX v3.28: Calcular próxima factura a vencer y facturas vencidas ───
    $proximaVencer = null;
    $facturasVencidas = 0;
    $deudaVencida = 0;
    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0);

    if (!empty($proveedor->compras)) {
        foreach ($proveedor->compras as $c) {
            $saldo = (float)($c->total ?? 0) - (float)($c->pagado ?? 0);
            if ($saldo <= 0.01) continue; // Solo facturas con saldo

            if (!empty($c->fecha_vencimiento)) {
                $fechaVenc = new DateTime($c->fecha_vencimiento);
                $fechaVenc->setTime(0, 0, 0);

                // Detectar vencidas
                if ($fechaVenc < $hoy) {
                    $facturasVencidas++;
                    $deudaVencida += $saldo;
                }

                // Detectar la más próxima a vencer (solo futuras)
                if ($fechaVenc >= $hoy) {
                    if ($proximaVencer === null || $fechaVenc < $proximaVencer['fecha']) {
                        $proximaVencer = [
                            'fecha' => $fechaVenc,
                            'factura_id' => $c->id,
                            'saldo' => $saldo,
                            'dias' => (int)$hoy->diff($fechaVenc)->days
                        ];
                    }
                }
            }
        }
    }
    ?>

    <div class="section-title">Resumen de Estado de Cuenta</div>
    <table class="summary-box">
        <tr>
            <td>
                <span class="summary-label">Total en Compras</span>
                <span class="summary-value">$<?php echo number_format($proveedor->resumen->total_compras ?? 0, 2); ?></span>
            </td>
            <td>
                <span class="summary-label">Total Pagado</span>
                <span class="summary-value text-success">$<?php echo number_format($proveedor->resumen->total_pagado ?? 0, 2); ?></span>
            </td>
            <td style="background: #fff1f2;">
                <span class="summary-label text-danger">Saldo Pendiente</span>
                <span class="summary-value text-danger">$<?php echo number_format($proveedor->resumen->saldo_pendiente ?? 0, 2); ?></span>
            </td>
        </tr>
    </table>

    <?php if ($proximaVencer !== null || $facturasVencidas > 0): ?>
    <div class="section-title" style="background: <?php echo ($facturasVencidas > 0) ? '#e11d48' : '#f59e0b'; ?>;">
        Alerta de Vencimientos
    </div>
    <table class="summary-box">
        <tr>
            <?php if ($proximaVencer !== null): ?>
            <td style="background: #fffbeb;">
                <span class="summary-label" style="color: #b45309;">Próxima Factura a Vencer</span>
                <span class="summary-value" style="color: #b45309; font-size: 13px;">
                    Compra #<?php echo $proximaVencer['factura_id']; ?>
                </span>
                <div style="font-size: 10px; margin-top: 4px; color: #92400e;">
                    <strong><?php echo $proximaVencer['fecha']->format('d/m/Y'); ?></strong>
                    — Vence en <strong><?php echo $proximaVencer['dias']; ?> día(s)</strong>
                </div>
                <div style="font-size: 10px; margin-top: 2px;">
                    Saldo: <strong style="color: #e11d48;">$<?php echo number_format($proximaVencer['saldo'], 2); ?></strong>
                </div>
            </td>
            <?php endif; ?>

            <?php if ($facturasVencidas > 0): ?>
            <td style="background: #fee2e2;">
                <span class="summary-label text-danger">⚠ Facturas Vencidas</span>
                <span class="summary-value text-danger" style="font-size: 13px;">
                    <?php echo $facturasVencidas; ?> factura(s)
                </span>
                <div style="font-size: 10px; margin-top: 4px; color: #991b1b;">
                    Deuda vencida: <strong>$<?php echo number_format($deudaVencida, 2); ?></strong>
                </div>
            </td>
            <?php else: ?>
            <td style="background: #dcfce7;">
                <span class="summary-label" style="color: #15803d;">✓ Sin Facturas Vencidas</span>
                <span class="summary-value" style="color: #15803d; font-size: 12px;">
                    Todas al día
                </span>
            </td>
            <?php endif; ?>
        </tr>
    </table>
    <?php endif; ?>

    <div class="section-title">Historial de Compras (Facturas)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Vence</th>
                <th>Estado</th>
                <th class="text-right">Total Factura</th>
                <th class="text-right">Abonado</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($proveedor->compras)): ?>
            <?php foreach($proveedor->compras as $c): ?>
            <?php
                // ─── FIX v3.28: Estilos y texto de vencimiento por factura ───
                $saldoFactura = (float)($c->total ?? 0) - (float)($c->pagado ?? 0);
                $vencimientoTxt = '---';
                $vencimientoStyle = 'color: #94a3b8; font-size: 9px;';

                if (!empty($c->fecha_vencimiento) && $saldoFactura > 0.01) {
                    $fechaVenc = new DateTime($c->fecha_vencimiento);
                    $fechaVenc->setTime(0, 0, 0);
                    $dias = (int)$hoy->diff($fechaVenc)->days;
                    $vencimientoTxt = $fechaVenc->format('d/m/Y');

                    if ($fechaVenc < $hoy) {
                        $vencimientoTxt .= ' · VENCIDA (' . $dias . 'd)';
                        $vencimientoStyle = 'background: #fee2e2; color: #991b1b; font-weight: bold; padding: 2px 6px; border-radius: 4px; font-size: 9px;';
                    } elseif ($dias <= 7) {
                        $vencimientoTxt .= ' · ' . $dias . 'd';
                        $vencimientoStyle = 'background: #fef3c7; color: #92400e; font-weight: bold; padding: 2px 6px; border-radius: 4px; font-size: 9px;';
                    } else {
                        $vencimientoTxt .= ' · ' . $dias . 'd';
                        $vencimientoStyle = 'color: #15803d; font-size: 9px; font-weight: bold;';
                    }
                } elseif ($saldoFactura <= 0.01) {
                    $vencimientoStyle = 'color: #94a3b8; font-size: 9px;';
                }
            ?>
            <tr>
                <td>#<?php echo $c->id; ?></td>
                <td><?php echo date('d/m/Y', strtotime($c->fecha)); ?></td>
                <td style="<?php echo $vencimientoStyle; ?>"><?php echo $vencimientoTxt; ?></td>
                <td><?php echo $c->status; ?></td>
                <td class="text-right">$<?php echo number_format($c->total ?? 0, 2); ?></td>
                <td class="text-right">$<?php echo number_format($c->pagado ?? 0, 2); ?></td>
                <td class="text-right text-bold <?php echo ($saldoFactura > 0) ? 'text-danger' : ''; ?>">
                    $<?php echo number_format($saldoFactura, 2); ?>
                </td>
            </tr>
            <?php if (!empty($c->items)): ?>
            <tr>
                <td colspan="7" style="padding: 0; background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <table style="width: 100%; border-collapse: collapse; margin: 0;">
                        <thead>
                            <tr>
                                <th style="padding: 5px 8px; font-size: 8px; text-align: left; color: #64748b; text-transform: uppercase; background: #f1f5f9; border-bottom: 1px solid #e2e8f0;">Código</th>
                                <th style="padding: 5px 8px; font-size: 8px; text-align: left; color: #64748b; text-transform: uppercase; background: #f1f5f9; border-bottom: 1px solid #e2e8f0;">Producto</th>
                                <th style="padding: 5px 8px; font-size: 8px; text-align: center; color: #64748b; text-transform: uppercase; background: #f1f5f9; border-bottom: 1px solid #e2e8f0;">Cant.</th>
                                <th style="padding: 5px 8px; font-size: 8px; text-align: right; color: #64748b; text-transform: uppercase; background: #f1f5f9; border-bottom: 1px solid #e2e8f0;">Costo Unit.</th>
                                <th style="padding: 5px 8px; font-size: 8px; text-align: right; color: #64748b; text-transform: uppercase; background: #f1f5f9; border-bottom: 1px solid #e2e8f0;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($c->items as $it): 
                                $subtotalItem = (float)$it->cantidad * (float)$it->costo_unitario;
                            ?>
                            <tr>
                                <td style="padding: 4px 8px; font-size: 9px; font-family: monospace; color: #94a3b8;"><?php echo htmlspecialchars($it->producto_codigo ?? '---'); ?></td>
                                <td style="padding: 4px 8px; font-size: 9px; font-weight: bold; color: #334155;">
                                    <?php echo htmlspecialchars($it->producto_nombre ?? $it->descripcion ?? '---'); ?>
                                    <?php if (!empty($it->producto_marca)): ?>
                                        <span style="color: #94a3b8; font-weight: normal; font-size: 8px;"> · <?php echo htmlspecialchars($it->producto_marca); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 4px 8px; font-size: 9px; text-align: center; font-weight: bold; color: #0f172a;"><?php echo (int)$it->cantidad; ?></td>
                                <td style="padding: 4px 8px; font-size: 9px; text-align: right; color: #64748b;">$<?php echo number_format((float)$it->costo_unitario, 2); ?></td>
                                <td style="padding: 4px 8px; font-size: 9px; text-align: right; font-weight: bold; color: #0f172a;">$<?php echo number_format($subtotalItem, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8; font-style: italic;">El proveedor no posee historial de facturas registradas.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if(!empty($proveedor->abonos)): ?>
    <div class="section-title">Historial de Pagos y Abonos</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha de Pago</th>
                <th>Factura Ref.</th>
                <th>Método</th>
                <th class="text-right">Monto Pagado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($proveedor->abonos as $a): ?>
            <tr>
                <td><?php echo date('d/m/Y h:i A', strtotime($a->fecha)); ?></td>
                <td>Compra #<?php echo $a->compra_id; ?></td>
                <td>PAGO REGISTRADO</td>
                <td class="text-right text-bold text-success">$<?php echo number_format($a->monto ?? 0, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="footer">
        Documento generado automáticamente por el sistema de gestión Taller Pro.<br>
        Página 1 de 1
    </div>
</body>
</html>