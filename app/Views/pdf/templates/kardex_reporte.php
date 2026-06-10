<div style="font-family: sans-serif; color: #0f172a; padding: 10px;">
    <div style="margin-bottom: 25px; border-bottom: 3px solid #0f172a; padding-bottom: 10px;">
        <h2 style="margin: 0; text-transform: uppercase;">Historial de Movimientos de Inventario</h2>
        <h3 style="margin: 5px 0 0 0; color: #475569;"><?php echo s($producto->nombre); ?></h3>
        <p style="font-size: 12px; color: #64748b; margin-top: 5px;">Categoría: <?php echo s($producto->categoria); ?> | Generado el: <?php echo date('d/m/Y h:i A'); ?></p>
    </div>

    <div style="margin-top: 20px;">
        <?php foreach ($movimientos as $m): ?>
            <div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                <div style="background: #f1f5f9; padding: 8px 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
                    <span style="font-size: 11px; font-weight: bold;">MOVIMIENTO #<?php echo $m->id; ?></span>
                    <span style="font-size: 11px; color: #64748b;"><?php echo date('d/m/Y H:i', strtotime($m->fecha)); ?></span>
                </div>
                <div style="padding: 12px 15px;">
                    <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                        <tr>
                            <td style="width: 25%; color: #64748b; padding-bottom: 5px;">Operación:</td>
                            <td style="font-weight: bold; color: <?php echo strpos($m->tipo_movimiento, 'ENTRADA') !== false ? '#16a34a' : '#dc2626'; ?>;">
                                <?php echo str_replace('_', ' ', $m->tipo_movimiento); ?>
                            </td>
                            <td style="width: 20%; color: #64748b; padding-bottom: 5px;">Cantidad:</td>
                            <td style="font-weight: bold;"><?php echo number_format($m->cantidad); ?> und.</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b; padding-bottom: 5px;">Stock Flujo:</td>
                            <td><?php echo number_format($m->stock_anterior); ?> → <b><?php echo number_format($m->stock_actual); ?></b></td>
                            <td style="color: #64748b; padding-bottom: 5px;">Referencia:</td>
                            <td style="font-weight: bold;">#<?php echo $m->referencia_id ?: 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td style="color: #64748b; vertical-align: top;">Observación:</td>
                            <td colspan="3" style="font-style: italic; color: #475569;">
                                <?php echo s($m->observaciones ?: 'Ninguna'); ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>