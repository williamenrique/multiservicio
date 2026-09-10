<div class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Detalle completo de la factura.</p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.history.back()" class="bg-slate-100 text-slate-600 font-bold px-6 py-3 rounded-xl hover:bg-slate-200 uppercase text-xs flex items-center gap-2 transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver
            </button>
            <button onclick="imprimirFactura()" class="bg-green-600 text-white font-bold px-6 py-3 rounded-xl hover:bg-green-700 uppercase text-xs flex items-center gap-2 transition-all">
                <i data-lucide="printer" class="w-4 h-4"></i> Imprimir PDF
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Info Principal -->
        <div class="lg:col-span-2 glass-card rounded-2xl p-6 shadow-xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">N° Factura</p>
                    <p class="font-mono font-bold text-2xl text-navy-blue"><?php echo $data['factura']->id_formateado; ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Fecha</p>
                    <p class="font-bold text-slate-700">
                        <?php 
                            $fecha = new DateTime($data['factura']->fecha);
                            echo $fecha->format('d/m/Y H:i:s');
                        ?>
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Estado</p>
                    <p class="font-bold text-slate-700">
                        <?php 
                            $status = $data['factura']->status;
                            $badgeClass = '';
                            $icon = '';
                            switch ($status) {
                                case 'COMPLETADO': $badgeClass = 'bg-green-100 text-green-800'; $icon = 'check-circle'; break;
                                case 'CREDITO': $badgeClass = 'bg-amber-100 text-amber-800'; $icon = 'clock'; break;
                                case 'ANULADO': $badgeClass = 'bg-red-100 text-red-800'; $icon = 'x-circle'; break;
                                default: $badgeClass = 'bg-slate-100 text-slate-800'; $icon = 'help-circle';
                            }
                        ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?php echo $badgeClass; ?>">
                            <i data-lucide="<?php echo $icon; ?>" class="w-3 h-3 mr-1"></i><?php echo $status; ?>
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Tipo / Procedencia</p>
                    <p class="font-bold text-slate-700">
                        <?php 
                            $tipo = $data['factura']->tipo_procedencia;
                            $badgeClass = '';
                            $icon = '';
                            switch ($tipo) {
                                case 'OS': $badgeClass = 'bg-blue-100 text-blue-800'; $icon = 'clipboard-list'; break;
                                case 'TALLER': $badgeClass = 'bg-purple-100 text-purple-800'; $icon = 'wrench'; break;
                                case 'MOSTRADOR': $badgeClass = 'bg-green-100 text-green-800'; $icon = 'shopping-cart'; break;
                                case 'GARANTIA': $badgeClass = 'bg-indigo-100 text-indigo-800'; $icon = 'shield-check'; break;
                                default: $badgeClass = 'bg-slate-100 text-slate-800'; $icon = 'help-circle';
                            }
                        ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?php echo $badgeClass; ?>">
                            <i data-lucide="<?php echo $icon; ?>" class="w-3 h-3 mr-1"></i><?php echo $tipo; ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Cliente y Vehículo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 bg-slate-50 rounded-xl border border-slate-100">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Cliente</p>
                    <p class="font-bold text-slate-700 text-lg"><?php echo s($data['factura']->cliente_nombre ?? 'Consumidor Final'); ?></p>
                    <?php if (!empty($data['factura']->cliente_telefono)): ?>
                    <p class="text-sm text-slate-500"><i data-lucide="phone" class="w-4 h-4 inline mr-1"></i><?php echo s($data['factura']->cliente_telefono); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($data['factura']->cliente_email)): ?>
                    <p class="text-sm text-slate-500"><i data-lucide="mail" class="w-4 h-4 inline mr-1"></i><?php echo s($data['factura']->cliente_email); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Vehículo</p>
                    <?php if (!empty($data['factura']->placa)): ?>
                    <p class="font-bold text-slate-700 text-lg"><?php echo s($data['factura']->placa); ?></p>
                    <?php if (!empty($data['factura']->marca_vehiculo)): ?>
                    <p class="text-sm text-slate-500"><?php echo s($data['factura']->marca_vehiculo); ?> <?php echo s($data['factura']->modelo_vehiculo ?? ''); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($data['factura']->kilometraje)): ?>
                    <p class="text-sm text-slate-500"><i data-lucide="gauge" class="w-4 h-4 inline mr-1"></i>Km: <?php echo s($data['factura']->kilometraje); ?></p>
                    <?php endif; ?>
                    <?php else: ?>
                    <p class="text-slate-400 italic">Sin vehículo asociado (Venta mostrador)</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vendedor y Mecánico -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 bg-slate-50 rounded-xl border border-slate-100">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Vendedor / Cajero</p>
                    <p class="font-bold text-slate-700"><?php echo s($data['factura']->vendedor_nombre ?? 'SISTEMA'); ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Mecánico Responsable</p>
                    <p class="font-bold text-slate-700"><?php echo s($data['factura']->mecanico_nombre ?? 'N/A'); ?></p>
                </div>
            </div>

            <!-- Observaciones -->
            <?php if (!empty($data['factura']->observaciones) || !empty($data['factura']->diagnostico_entrada) || !empty($data['factura']->observaciones_orden) || !empty($data['factura']->diagnostico_salida)): ?>
            <div class="space-y-4">
                <?php if (!empty($data['factura']->diagnostico_entrada)): ?>
                <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl">
                    <p class="text-[10px] font-bold text-blue-800 uppercase mb-2 flex items-center gap-2"><i data-lucide="stethoscope" class="w-4 h-4"></i> Diagnóstico de Entrada</p>
                    <p class="text-slate-700 whitespace-pre-wrap"><?php echo s($data['factura']->diagnostico_entrada); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($data['factura']->diagnostico_salida)): ?>
                <div class="p-4 bg-green-50 border border-green-100 rounded-xl">
                    <p class="text-[10px] font-bold text-green-800 uppercase mb-2 flex items-center gap-2"><i data-lucide="check-circle-2" class="w-4 h-4"></i> Diagnóstico de Salida</p>
                    <p class="text-slate-700 whitespace-pre-wrap"><?php echo s($data['factura']->diagnostico_salida); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($data['factura']->observaciones_orden)): ?>
                <div class="p-4 bg-purple-50 border border-purple-100 rounded-xl">
                    <p class="text-[10px] font-bold text-purple-800 uppercase mb-2 flex items-center gap-2"><i data-lucide="clipboard-list" class="w-4 h-4"></i> Observaciones Orden</p>
                    <p class="text-slate-700 whitespace-pre-wrap"><?php echo s($data['factura']->observaciones_orden); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($data['factura']->observaciones)): ?>
                <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl">
                    <p class="text-[10px] font-bold text-amber-800 uppercase mb-2 flex items-center gap-2"><i data-lucide="message-square" class="w-4 h-4"></i> Observaciones Factura</p>
                    <p class="text-slate-700 whitespace-pre-wrap"><?php echo s($data['factura']->observaciones); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Resumen Financiero -->
        <div class="glass-card rounded-2xl p-6 shadow-xl sticky top-24 h-fit">
            <h3 class="text-lg font-bold text-navy-blue uppercase tracking-wider mb-4 border-b border-slate-100 pb-3 flex items-center gap-2">
                <i data-lucide="calculator" class="w-5 h-5"></i> Resumen Financiero
            </h3>
            
            <div class="space-y-3 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Subtotal</span>
                    <span class="font-bold text-slate-700"><?php echo number_format($data['factura']->subtotal, 0, ',', '.'); ?></span>
                </div>
                <?php if ($data['factura']->iva_monto > 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">IVA (<?php echo $data['factura']->iva_monto > 0 ? '19%' : '0%'; ?>)</span>
                    <span class="font-bold text-slate-700"><?php echo number_format($data['factura']->iva_monto, 0, ',', '.'); ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between text-lg font-bold text-navy-blue border-t border-slate-100 pt-3">
                    <span>TOTAL</span>
                    <span><?php echo number_format($data['factura']->total, 0, ',', '.'); ?></span>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4 space-y-3">
                <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Forma de Pago</p>
                <div class="space-y-2">
                    <?php if ($data['factura']->pago_efectivo > 0): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500 flex items-center gap-2"><i data-lucide="dollar-sign" class="w-4 h-4"></i> Efectivo</span>
                        <span class="font-bold text-green-700"><?php echo number_format($data['factura']->pago_efectivo, 0, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($data['factura']->pago_transferencia > 0): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500 flex items-center gap-2"><i data-lucide="credit-card" class="w-4 h-4"></i> Transferencia</span>
                        <span class="font-bold text-blue-700"><?php echo number_format($data['factura']->pago_transferencia, 0, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($data['factura']->saldo_pendiente > 0): ?>
                    <div class="flex justify-between text-sm text-red-600 font-bold">
                        <span class="flex items-center gap-2"><i data-lucide="alert-triangle" class="w-4 h-4"></i> Saldo Pendiente</span>
                        <span><?php echo number_format($data['factura']->saldo_pendiente, 0, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Items de la Factura -->
    <div class="glass-card rounded-2xl overflow-hidden shadow-xl">
        <div class="p-6 border-b border-slate-100">
            <h3 class="text-lg font-bold text-navy-blue uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="list" class="w-5 h-5"></i> Detalle de Items (<?php echo count($data['factura']->items ?? []); ?>)
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                        <th class="px-6 py-4">#</th>
                        <th class="px-6 py-4">Descripción</th>
                        <th class="px-6 py-4 text-center">Tipo</th>
                        <th class="px-6 py-4 text-center">Cant.</th>
                        <th class="px-6 py-4 text-right">Precio Unit.</th>
                        <th class="px-6 py-4 text-right">Costo Unit.</th>
                        <th class="px-6 py-4 text-right">Subtotal</th>
                        <th class="px-6 py-4 text-center">Mecánico</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                    <?php if (!empty($data['factura']->items)): ?>
                        <?php $index = 1; foreach ($data['factura']->items as $item): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-center font-mono text-slate-400"><?php echo $index++; ?></td>
                            <td class="px-6 py-4 font-medium text-slate-700"><?php echo s($item->descripcion); ?></td>
                            <td class="px-6 py-4 text-center">
                                <?php 
                                    $tipo = $item->producto_id ? 'PRODUCTO' : 'SERVICIO';
                                    $badgeClass = $tipo === 'PRODUCTO' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800';
                                    $icon = $tipo === 'PRODUCTO' ? 'package' : 'wrench';
                                ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $badgeClass; ?>">
                                    <i data-lucide="<?php echo $icon; ?>" class="w-3 h-3 mr-1"></i><?php echo $tipo; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-navy-blue"><?php echo (int)$item->cantidad; ?></td>
                            <td class="px-6 py-4 text-right font-mono text-slate-700"><?php echo number_format($item->precio_unitario, 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 text-right font-mono text-slate-500"><?php echo number_format($item->costo_unitario ?? 0, 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 text-right font-bold text-navy-blue"><?php echo number_format($item->precio_unitario * $item->cantidad, 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 text-center text-slate-500"><?php echo s($item->mecanico_nombre ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-8 py-16 text-center text-slate-400 italic">No hay items en esta factura</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Checklist (si viene de Orden de Servicio) -->
    <?php if (!empty($data['factura']->checklist)): ?>
    <div class="glass-card rounded-2xl overflow-hidden shadow-xl mt-6">
        <div class="p-6 border-b border-slate-100">
            <h3 class="text-lg font-bold text-navy-blue uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="clipboard-check" class="w-5 h-5"></i> Checklist de Entrada
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?php foreach ($data['factura']->checklist as $check): ?>
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                    <i data-lucide="<?php echo $check->estado ? 'check-circle' : 'x-circle'; ?>" class="w-5 h-5 <?php echo $check->estado ? 'text-green-500' : 'text-red-500'; ?> flex-shrink-0"></i>
                    <span class="text-sm text-slate-700"><?php echo s($check->item); ?></span>
                    <?php if (!empty($check->observacion)): ?>
                    <span class="text-xs text-slate-400 italic ml-auto"><?php echo s($check->observacion); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    function imprimirFactura() {
        window.open('<?php echo URLROOT; ?>/facturas/imprimir/<?php echo $data['factura']->id; ?>', '_blank');
    }
    
    // Inicializar iconos Lucide
    if (window.lucide) lucide.createIcons();
</script>