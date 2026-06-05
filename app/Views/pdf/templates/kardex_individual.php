<div style="font-family: sans-serif; color: #1e293b; padding: 20px;">
    <h2 style="text-transform: uppercase; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 30px;">
        Detalle del Movimiento #<?php echo $mov->id; ?>
    </h2>

    <div style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div style="margin-bottom: 20px;">
            <strong style="font-size: 10px; color: #64748b; text-transform: uppercase;">Producto:</strong>
            <div style="font-size: 18px; font-weight: bold; color: #0f172a;"><?php echo s($mov->producto_nombre); ?></div>
            <div style="font-size: 12px; color: #64748b;"><?php echo s($mov->categoria); ?></div>
        </div>

        <div style="display: block; margin-bottom: 15px;">
            <strong style="font-size: 10px; color: #64748b; text-transform: uppercase;">Fecha y Hora:</strong>
            <div style="font-size: 14px;"><?php echo date('d/m/Y h:i A', strtotime($mov->fecha)); ?></div>
        </div>

        <div style="display: block; margin-bottom: 15px;">
            <strong style="font-size: 10px; color: #64748b; text-transform: uppercase;">Tipo de Operación:</strong>
            <div style="font-size: 14px; font-weight: bold; color: <?php echo strpos($mov->tipo_movimiento, 'ENTRADA') !== false ? '#16a34a' : '#dc2626'; ?>;">
                <?php echo str_replace('_', ' ', $mov->tipo_movimiento); ?>
            </div>
        </div>

        <div style="display: block; margin-bottom: 15px;">
            <strong style="font-size: 10px; color: #64748b; text-transform: uppercase;">Cantidad Operada:</strong>
            <div style="font-size: 16px; font-weight: bold;"><?php echo number_format($mov->cantidad); ?> unidades</div>
        </div>

        <div style="display: block; margin-bottom: 15px;">
            <strong style="font-size: 10px; color: #64748b; text-transform: uppercase;">Trazabilidad de Stock:</strong>
            <div style="font-size: 13px;">Stock Anterior: <b><?php echo number_format($mov->stock_anterior); ?></b> → Stock Actual: <b><?php echo number_format($mov->stock_actual); ?></b></div>
        </div>

        <div style="display: block; margin-bottom: 15px;">
            <strong style="font-size: 10px; color: #64748b; text-transform: uppercase;">Referencia de Documento (ID Venta/Compra):</strong>
            <div style="font-size: 14px; font-weight: bold;">#<?php echo $mov->referencia_id ?: 'N/A'; ?></div>
        </div>

        <div style="display: block; margin-bottom: 15px;">
            <strong style="font-size: 10px; color: #64748b; text-transform: uppercase;">Observaciones Detalladas:</strong>
            <div style="font-size: 13px; font-style: italic; color: #475569;"><?php echo s($mov->observaciones ?: 'Sin observaciones adicionales registradas'); ?></div>
        </div>

        <div style="margin-top: 30px; padding-top: 15px; border-top: 1px dashed #cbd5e1;">
            <strong style="font-size: 10px; color: #64748b; text-transform: uppercase;">Responsable del Registro:</strong>
            <div style="font-size: 12px; font-weight: bold;"><?php echo s($mov->usuario_nombre ?: $mov->username); ?></div>
        </div>
    </div>
</div>