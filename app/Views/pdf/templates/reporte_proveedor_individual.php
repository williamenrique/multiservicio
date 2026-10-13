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

    <div class="section-title">Historial de Compras (Facturas)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th class="text-right">Total Factura</th>
                <th class="text-right">Abonado</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($proveedor->compras)): ?>
            <?php foreach($proveedor->compras as $c): ?>
            <tr>
                <td>#<?php echo $c->id; ?></td>
                <td><?php echo date('d/m/Y', strtotime($c->fecha)); ?></td>
                <td><?php echo $c->status; ?></td>
                <td class="text-right">$<?php echo number_format($c->total ?? 0, 2); ?></td>
                <td class="text-right">$<?php echo number_format($c->pagado ?? 0, 2); ?></td>
                <td class="text-right text-bold <?php echo (($c->total ?? 0) - ($c->pagado ?? 0) > 0) ? 'text-danger' : ''; ?>">
                    $<?php echo number_format(($c->total ?? 0) - ($c->pagado ?? 0), 2); ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8; font-style: italic;">El proveedor no posee historial de facturas registradas.</td>
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