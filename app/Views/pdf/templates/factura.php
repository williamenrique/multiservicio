 <div class="content">
    <div style="margin-bottom: 15px;">
        <strong>CLIENTE:</strong> <?php echo $venta->cliente_nombre; ?><br>
        <strong>PLACA:</strong> <?php echo $venta->placa ?: 'N/A'; ?> | <strong>VEHÍCULO:</strong> <?php echo $venta->modelo_vehiculo ?: 'N/A'; ?><br>
        <strong>FECHA DE EMISIÓN:</strong> <?php echo date('d/m/Y - h:i A', strtotime($venta->fecha)); ?>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th width="80px">Cantidad</th>
                <th width="100px">Precio Unit.</th>
                <th width="100px">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($venta->items as $item): ?>
            <tr>
                <td><?php echo $item->descripcion; ?></td>
                <td><?php echo $item->cantidad; ?></td>
                <td class="text-right">$<?php echo number_format($item->precio_unitario, 2); ?></td>
                <td class="text-right">$<?php echo number_format($item->cantidad * $item->precio_unitario, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-box text-right">
        <span style="font-size: 16px;"><strong>TOTAL A PAGAR: $<?php echo number_format($venta->total, 2); ?></strong></span>
    </div>
</div>