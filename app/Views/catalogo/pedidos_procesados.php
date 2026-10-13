<div class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Historial de pedidos procesados y cancelados del catálogo público.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?php echo URLROOT; ?>/catalogo/pedidos-pendientes" class="bg-amber-50 border border-amber-200 text-amber-700 px-5 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-amber-100 text-sm font-semibold shadow-sm">
                <i data-lucide="clock" class="w-4 h-4"></i>
                Ver Pendientes
            </a>
            <a href="<?php echo URLROOT; ?>/catalogo" target="_blank" class="bg-white border border-slate-200 text-slate-700 px-5 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-slate-50 text-sm font-semibold shadow-sm">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                Ver Catálogo
            </a>
        </div>
    </div>

    <?php if (empty($data['pedidos'])): ?>
        <div class="glass-card rounded-2xl p-16 text-center shadow-xl">
            <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="archive" class="w-10 h-10 text-slate-400"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-700 mb-2">No hay pedidos procesados</h2>
            <p class="text-slate-400">Aún no se ha procesado ningún pedido. Los pedidos procesados aparecerán aquí.</p>
        </div>
    <?php else: ?>
        <div class="grid gap-4">
            <?php foreach ($data['pedidos'] as $pedido): ?>
                <?php
                $esProcesado = $pedido->estado === 'PROCESADO';
                $badgeColor = $esProcesado ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700';
                $iconEstado = $esProcesado ? 'check-circle' : 'x-circle';
                ?>
                <div class="glass-card rounded-2xl p-6 shadow-xl hover:shadow-2xl transition-all">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="bg-slate-100 text-slate-700 text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider">
                                    #<?php echo $pedido->id; ?>
                                </span>
                                <span class="<?php echo $badgeColor; ?> text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider flex items-center gap-1">
                                    <i data-lucide="<?php echo $iconEstado; ?>" class="w-3 h-3"></i>
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
                                <?php if ($esProcesado && !empty($pedido->nombre_usuario)): ?>
                                <span class="flex items-center gap-1 text-slate-400">
                                    <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                                    Procesado por: <?php echo s($pedido->nombre_usuario); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($esProcesado && !empty($pedido->fecha_procesado)): ?>
                                <span class="flex items-center gap-1 text-slate-400">
                                    <i data-lucide="calendar-check" class="w-3.5 h-3.5"></i>
                                    <?php echo date('d/m/Y h:i A', strtotime($pedido->fecha_procesado)); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Actions -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a href="<?php echo URLROOT; ?>/catalogo/ver-pedido/<?php echo $pedido->id; ?>" 
                               class="bg-white border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl flex items-center gap-2 transition-all hover:bg-slate-50 text-sm font-semibold">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                Ver Detalle
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>