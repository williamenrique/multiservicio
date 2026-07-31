<div class="container mx-auto p-6">
    <div class="mb-6">
        <a href="<?php echo URLROOT; ?>/catalogo/pedidos-pendientes" class="text-slate-500 hover:text-navy-blue transition-colors flex items-center gap-2 text-sm font-semibold">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Volver a Pedidos Pendientes
        </a>
    </div>

    <div class="glass-card rounded-2xl overflow-hidden shadow-xl">
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 text-white px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight">Pedido #<?php echo $data['pedido']->id; ?></h1>
                    <p class="text-emerald-100 text-sm mt-1">Realizado el <?php echo date('d/m/Y h:i A', strtotime($data['pedido']->fecha_pedido)); ?></p>
                </div>
                <span class="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full text-sm font-black uppercase tracking-wider">
                    <?php echo s($data['pedido']->estado); ?>
                </span>
            </div>
        </div>

        <div class="p-8">
            <!-- Customer Info -->
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 p-6 bg-slate-50 rounded-2xl">
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wider font-bold mb-1">Cliente</p>
                    <p class="font-bold text-navy-blue text-lg"><?php echo s($data['pedido']->nombre_cliente); ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wider font-bold mb-1">Cédula / NIT</p>
                    <p class="font-semibold text-slate-700"><?php echo s($data['pedido']->cedula); ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wider font-bold mb-1">Teléfono</p>
                    <p class="font-semibold text-slate-700"><?php echo s($data['pedido']->telefono); ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wider font-bold mb-1">Correo</p>
                    <p class="font-semibold text-slate-700"><?php echo s($data['pedido']->correo); ?></p>
                </div>
                <?php if ($data['pedido']->direccion): ?>
                    <div class="md:col-span-2">
                        <p class="text-xs text-slate-400 uppercase tracking-wider font-bold mb-1">Dirección</p>
                        <p class="font-semibold text-slate-700"><?php echo s($data['pedido']->direccion); ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($data['pedido']->notas): ?>
                    <div class="md:col-span-2">
                        <p class="text-xs text-slate-400 uppercase tracking-wider font-bold mb-1">Notas del pedido</p>
                        <p class="font-semibold text-slate-700"><?php echo s($data['pedido']->notas); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Items Table -->
            <h2 class="text-lg font-extrabold text-navy-blue mb-4">Productos</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                            <th class="px-6 py-4">Producto</th>
                            <th class="px-6 py-4">Cantidad</th>
                            <th class="px-6 py-4">Precio Unit.</th>
                            <th class="px-6 py-4 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['detalles'] as $detalle): ?>
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-slate-800"><?php echo s($detalle->nombre ?? 'Producto #' . $detalle->producto_id); ?></span>
                                </td>
                                <td class="px-6 py-4 text-slate-600"><?php echo $detalle->cantidad; ?></td>
                                <td class="px-6 py-4 text-slate-600">$<?php echo number_format($detalle->precio_unitario, 2); ?></td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-800">$<?php echo number_format($detalle->subtotal, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="mt-6 ml-auto max-w-xs">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Subtotal</span>
                        <span class="font-semibold">$<?php echo number_format($data['pedido']->subtotal, 2); ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">IVA (19%)</span>
                        <span class="font-semibold">$<?php echo number_format($data['pedido']->iva, 2); ?></span>
                    </div>
                    <div class="flex justify-between text-lg font-extrabold pt-3 border-t border-slate-200">
                        <span class="text-navy-blue">Total</span>
                        <span class="text-emerald-600">$<?php echo number_format($data['pedido']->total, 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <?php if ($data['pedido']->estado === 'PENDIENTE'): ?>
                <div class="mt-8 pt-6 border-t border-slate-200 flex flex-wrap gap-3">
                    <button onclick="procesarPedido(<?php echo $data['pedido']->id; ?>)" 
                            class="bg-emerald-500 text-white px-6 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-emerald-600 font-bold shadow-lg shadow-emerald-500/30">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        Procesar Pedido (Descontar Stock)
                    </button>
                    <button onclick="cancelarPedido(<?php echo $data['pedido']->id; ?>)" 
                            class="bg-red-500 text-white px-6 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-red-600 font-bold shadow-lg shadow-red-500/30">
                        <i data-lucide="x-circle" class="w-5 h-5"></i>
                        Cancelar Pedido
                    </button>
                </div>
            <?php elseif ($data['pedido']->estado === 'PROCESADO' && $data['pedido']->usuario_procesa): ?>
                <div class="mt-8 pt-6 border-t border-slate-200 p-4 bg-emerald-50 rounded-xl">
                    <p class="text-sm text-emerald-700 flex items-center gap-2">
                        <i data-lucide="user-check" class="w-4 h-4"></i>
                        Procesado por usuario ID: <?php echo $data['pedido']->usuario_procesa; ?>
                        <?php if ($data['pedido']->fecha_procesado): ?>
                            el <?php echo date('d/m/Y h:i A', strtotime($data['pedido']->fecha_procesado)); ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function procesarPedido(id) {
    Swal.fire({
        title: '¿Procesar pedido?',
        text: 'Se descontará el stock del inventario y se registrará en kardex.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('pedido_id', id);

            fetch(URLROOT + '/catalogo/procesar-pedido-staff', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Procesado', data.mensaje, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.mensaje, 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error de conexión.', 'error');
            });
        }
    });
}

function cancelarPedido(id) {
    Swal.fire({
        title: '¿Cancelar pedido?',
        text: 'El pedido será marcado como cancelado.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'Volver'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('pedido_id', id);

            fetch(URLROOT + '/catalogo/cancelar-pedido-staff', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Cancelado', data.mensaje, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.mensaje, 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error de conexión.', 'error');
            });
        }
    });
}
</script>