<div class="content">
    <div style="background-color: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #0f172a;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <span style="font-size: 10px; color: #64748b; font-weight: bold; text-transform: uppercase;">Beneficiario</span><br>
                    <strong style="font-size: 14px; text-transform: uppercase;"><?php echo $pago->staff_nombre; ?></strong><br>
                    <span style="font-size: 10px; color: #475569;">ID: <?php echo $pago->staff_cedula; ?> | Cargo: <?php echo $pago->staff_cargo; ?></span>
                </td>
                <td style="width: 50%; text-align: right;">
                    <span style="font-size: 10px; color: #64748b; font-weight: bold; text-transform: uppercase;">Detalle del Pago</span><br>
                    <strong>Tipo: <?php echo $pago->tipo; ?></strong><br>
                    <strong>Fecha: <?php echo date('d/m/Y', strtotime($pago->fecha)); ?></strong>
                </td>
            </tr>
        </table>
    </div>

    <p style="font-size: 11px; margin-bottom: 10px; font-weight: bold; text-transform: uppercase; color: #1e293b;">Resumen de Liquidación</p>
    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción del Trabajo / Placa</th>
                <th class="text-right">Monto Base</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($pago->trabajos)): ?>
                <?php foreach ($pago->trabajos as $t): ?>
                    <tr>
                        <td style="text-transform: uppercase;">
                            <?php echo $t->descripcion; ?> <span style="color: #94a3b8;">[<?php echo $t->placa; ?>]</span>
                        </td>
                        <td class="text-right"><?php echo number_format($t->precio_unitario, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" style="text-align: center; color: #94a3b8; font-style: italic;">Pago registrado como adelanto o sin detalles específicos.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="total-box">
        <table style="width: 100%;">
            <tr>
                <td style="font-size: 10px; color: #64748b; text-transform: uppercase;">Base Imponible (Mano de Obra):</td>
                <td style="text-align: right;">$<?php echo number_format($pago->monto_base, 2); ?></td>
            </tr>
            <tr>
                <td style="font-size: 10px; color: #64748b; text-transform: uppercase;">Modo de Cálculo:</td>
                <td style="text-align: right;"><?php echo $pago->modo_calculo; ?> (<?php echo ($pago->modo_calculo == 'PORCENTAJE') ? $pago->factor_calculo . '%' : '$' . number_format($pago->factor_calculo, 2); ?>)</td>
            </tr>
            <tr style="font-size: 16px; font-weight: bold;">
                <td style="padding-top: 10px; text-transform: uppercase;">Total Percibido:</td>
                <td style="text-align: right; padding-top: 10px; color: #10b981;">$<?php echo number_format($pago->monto, 2); ?></td>
            </tr>
        </table>
    </div>

    <?php if(!empty($pago->notas)): ?>
        <div style="margin-top: 20px; font-size: 10px; color: #475569; border: 1px dashed #cbd5e1; padding: 10px; border-radius: 5px;">
            <strong>OBSERVACIONES:</strong> <?php echo strtoupper($pago->notas); ?>
        </div>
    <?php endif; ?>

    <div style="margin-top: 100px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 45%; border-top: 1px solid #000; text-align: center; font-size: 10px;"><br>FIRMA EMPLEADO</td>
                <td style="width: 10%;"></td>
                <td style="width: 45%; border-top: 1px solid #000; text-align: center; font-size: 10px;"><br>FIRMA ADMINISTRACIÓN</td>
            </tr>
        </table>
    </div>
</div>