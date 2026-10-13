<div class="content">
    <p style="margin-bottom: 20px;">
        A continuación se detalla el estado de cuenta global de clientes con saldos pendientes, 
        clasificados por antigüedad de la deuda (Aging Report).
    </p>

    <table class="items-table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th class="text-right">0-15 Días</th>
                <th class="text-right">16-30 Días</th>
                <th class="text-right">+30 Días</th>
                <th class="text-right">Total Deuda</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cartera as $c): ?>
                <tr>
                    <td>
                        <strong><?php echo $c->cliente_nombre; ?></strong><br>
                        <span style="font-size: 8px; color: #64748b;"><?php echo $c->cliente_telefono; ?></span>
                    </td>
                    <td class="text-right"><?php echo number_format($c->rango_0_15, 2); ?></td>
                    <td class="text-right" style="color: #f59e0b;"><?php echo number_format($c->rango_16_30, 2); ?></td>
                    <td class="text-right" style="color: #ef4444; font-weight: bold;"><?php echo number_format($c->rango_30_mas, 2); ?></td>
                    <td class="text-right" style="background-color: #f8fafc; font-weight: bold;">
                        <?php echo number_format($c->total_deuda, 2); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-box">
        <p class="text-right" style="font-size: 14px;"><strong>Cartera Total:</strong> $<?php echo number_format(array_sum(array_column($cartera, 'total_deuda')), 2); ?></p>
    </div>
</div>