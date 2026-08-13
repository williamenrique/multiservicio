<div class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Gestiona los pedidos realizados por los clientes desde el catálogo público.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?php echo URLROOT; ?>/catalogo" target="_blank" class="bg-white border border-slate-200 text-slate-700 px-5 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-slate-50 text-sm font-semibold shadow-sm">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                Ver Catálogo
            </a>
        </div>
    </div>

    <div id="empty-state" class="glass-card rounded-2xl p-16 text-center shadow-xl <?php echo empty($data['pedidos']) ? '' : 'hidden'; ?>">
        <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="package-check" class="w-10 h-10 text-emerald-600"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-700 mb-2">No hay pedidos pendientes</h2>
        <p class="text-slate-400">Todos los pedidos han sido procesados. Los nuevos pedidos de clientes aparecerán aquí.</p>
    </div>

    <div id="lista-pedidos" class="grid gap-4 <?php echo empty($data['pedidos']) ? 'hidden' : ''; ?>">
        <?php foreach ($data['pedidos'] as $pedido): ?>
            <div id="pedido-card-<?php echo $pedido->id; ?>" class="glass-card rounded-2xl p-6 shadow-xl hover:shadow-2xl transition-all">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="bg-amber-100 text-amber-700 text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider">
                                    #<?php echo $pedido->id; ?>
                                </span>
                                <span class="bg-yellow-100 text-yellow-700 text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider">
                                    <?php echo s($pedido->estado); ?>
                                </span>
                                <span class="text-xs text-slate-400">
                                    <?php echo date('d/m/Y h:i A', strtotime($pedido->fecha_pedido)); ?>
                                </span>
                            </div>
                            <h3 class="text-lg font-bold text-navy-blue truncate"><?php echo s($pedido->nombre_cliente); ?></h3>
                            <div class="flex flex-wrap items-center gap-4 mt-1 text-sm text-slate-500">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                    <?php echo s($pedido->telefono); ?>
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                                    <?php echo s($pedido->correo); ?>
                                </span>
                                <span class="flex items-center gap-1 font-semibold text-emerald-600">
                                    <i data-lucide="dollar-sign" class="w-3.5 h-3.5"></i>
                                    $<?php echo number_format($pedido->total, 2); ?>
                                </span>
                            </div>
                        </div>
                        <!-- Actions -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a href="<?php echo URLROOT; ?>/catalogo/ver-pedido/<?php echo $pedido->id; ?>" 
                               class="bg-white border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl flex items-center gap-2 transition-all hover:bg-slate-50 text-sm font-semibold">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                Ver Detalle
                            </a>
                            <button onclick="procesarPedido(<?php echo $pedido->id; ?>)" 
                                    class="bg-emerald-500 text-white px-4 py-2.5 rounded-xl flex items-center gap-2 transition-all hover:bg-emerald-600 text-sm font-semibold shadow-lg shadow-emerald-500/30">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                Procesar
                            </button>
                            <button onclick="cancelarPedido(<?php echo $pedido->id; ?>)" 
                                    class="bg-red-500 text-white px-4 py-2.5 rounded-xl flex items-center gap-2 transition-all hover:bg-red-600 text-sm font-semibold shadow-lg shadow-red-500/30">
                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Elimina una tarjeta con animación y muestra el estado vacío si no quedan pedidos
function removerTarjetaPedido(id) {
    const card = document.getElementById('pedido-card-' + id);
    if (!card) return;
    card.style.transition = 'opacity .35s ease, transform .35s ease';
    card.style.opacity = '0';
    card.style.transform = 'translateX(40px)';
    setTimeout(() => {
        card.remove();
        const lista = document.getElementById('lista-pedidos');
        if (lista && lista.querySelectorAll('[id^="pedido-card-"]').length === 0) {
            lista.classList.add('hidden');
            document.getElementById('empty-state').classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        }
    }, 350);
}

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
                    AppUtils.showToast(data.mensaje, 'success');
                    removerTarjetaPedido(id);
                } else {
                    AppUtils.showToast(data.mensaje || 'Error al procesar.', 'error');
                }
            })
            .catch(() => {
                AppUtils.showToast('Error de conexión.', 'error');
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
                    AppUtils.showToast(data.mensaje, 'success');
                    removerTarjetaPedido(id);
                } else {
                    AppUtils.showToast(data.mensaje || 'Error al cancelar.', 'error');
                }
            })
            .catch(() => {
                AppUtils.showToast('Error de conexión.', 'error');
            });
        }
    });
}
</script>