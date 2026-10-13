<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 11px; margin: 0; padding: 0; }
        table.cartera-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.cartera-table th { background: #0f172a; color: white; padding: 10px 5px; text-align: center; font-size: 9px; text-transform: uppercase; }
        table.cartera-table td { padding: 10px 5px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .total-row { background: #f1f5f9; font-weight: bold; font-size: 12px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; }
        .report-subtitle { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 20px; background: #f8fafc; padding: 10px; border: 1px solid #e2e8f0; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="report-subtitle">
        Resumen de Cartera de Proveedores por Edades (Cuentas por Pagar)
    </div>

    <table class="cartera-table">
        <thead>
            <tr>
                <th align="left">Proveedor / Contacto</th>
                <th>0 - 15 Días</th>
                <th>16 - 30 Días</th>
                <th>Más de 30 Días</th>
                <th class="text-right">Total Deuda</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_0_15 = 0;
            $total_16_30 = 0;
            $total_30_mas = 0;
            $gran_total = 0;
            
            foreach($cartera as $c): 
                $total_0_15 += $c->rango_0_15;
                $total_16_30 += $c->rango_16_30;
                $total_30_mas += $c->rango_30_mas;
                $gran_total += $c->total_deuda;
            ?>
            <tr>
                <td>
                    <span class="text-bold"><?php echo $c->proveedor_nombre; ?></span><br>
                    <span style="color: #64748b; font-size: 9px;"><?php echo $c->proveedor_telefono; ?></span>
                </td>
                <td class="text-center">$<?php echo number_format($c->rango_0_15, 2); ?></td>
                <td class="text-center">$<?php echo number_format($c->rango_16_30, 2); ?></td>
                <td class="text-center" style="color: #e11d48;">$<?php echo number_format($c->rango_30_mas, 2); ?></td>
                <td class="text-right text-bold">$<?php echo number_format($c->total_deuda, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td class="text-right">TOTALES GENERALES:</td>
                <td class="text-center">$<?php echo number_format($total_0_15, 2); ?></td>
                <td class="text-center">$<?php echo number_format($total_16_30, 2); ?></td>
                <td class="text-center">$<?php echo number_format($total_30_mas, 2); ?></td>
                <td class="text-right">$<?php echo number_format($gran_total, 2); ?></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px; border: 1px solid #e2e8f0; padding: 15px; background: #fffbeb;">
        <span class="text-bold" style="color: #92400e;">Nota de Auditoría:</span><br>
        <p style="margin-top: 5px; color: #b45309; font-size: 10px;">
            Este reporte muestra el dinero adeudado a proveedores basado en las facturas de compra registradas con saldo pendiente. 
            Las edades se calculan a partir de la fecha de registro de la factura hasta la fecha actual.
        </p>
    </div>

    <div class="footer">
        Reporte interno de gestión financiera - Página 1 de 1
    </div>
</body>
</html>