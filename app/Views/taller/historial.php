<div class="space-y-6">
    <div class="flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex items-center gap-4">
            <a href="<?php echo URLROOT; ?>/taller" class="p-2 hover:bg-slate-100 rounded-xl transition-colors">
                <i data-lucide="arrow-left" class="text-slate-400"></i>
            </a>
            <h2 class="text-xl font-black text-navy-blue uppercase tracking-tighter">Expediente Técnico</h2>
        </div>
        <?php if($vehiculo): ?>
            <a href="<?php echo URLROOT; ?>/taller/nuevaOrden?placa=<?php echo $vehiculo->placa; ?>" class="bg-navy-blue text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-neon-green hover:text-black transition-all flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i> NUEVO INGRESO
            </a>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Columna de Información Fija -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="bg-navy-blue p-8 text-center relative">
                    <div class="absolute top-4 right-4">
                         <i data-lucide="shield-check" class="text-neon-green w-5 h-5 opacity-50"></i>
                    </div>
                    <?php if($vehiculo): ?>
                        <h1 class="text-4xl font-black text-white tracking-tighter mb-1"><?php echo $vehiculo->placa; ?></h1>
                        <span class="bg-neon-green text-black text-[10px] font-black px-3 py-1 rounded-full uppercase"><?php echo $vehiculo->marca; ?></span>
                    <?php else: ?>
                        <h1 class="text-xl font-black text-white tracking-tighter uppercase leading-tight"><?php echo $entidad->nombre; ?></h1>
                        <p class="text-slate-400 text-xs mt-2 uppercase font-bold"><?php echo $tipo; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="p-6 space-y-5">
                    <?php if($vehiculo): ?>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Modelo / Año</p>
                            <p class="text-sm font-bold text-navy-blue uppercase"><?php echo $vehiculo->modelo; ?> — <?php echo $vehiculo->anio ?? 'N/A'; ?></p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Color del Vehículo</p>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full border border-slate-300" style="background-color: <?php echo $vehiculo->color; ?>"></div>
                                <p class="text-sm font-bold text-navy-blue uppercase"><?php echo $vehiculo->color; ?></p>
                            </div>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Propietario</p>
                            <p class="text-sm font-bold text-navy-blue uppercase"><?php echo $vehiculo->cliente_nombre; ?></p>
                            <p class="text-[10px] text-slate-500 font-medium mt-1"><i data-lucide="phone" class="w-3 h-3 inline"></i> <?php echo $vehiculo->cliente_telefono; ?></p>
                        </div>
                    <?php else: ?>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 text-center">
                            <p class="text-3xl font-black text-navy-blue"><?php echo count($historial); ?></p>
                            <p class="text-[10px] font-black text-slate-400 uppercase">Órdenes Totales</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna Cronológica (Historial) -->
        <div class="lg:col-span-3 space-y-4">
            <div class="flex items-center justify-between mb-2 px-2">
                <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                    <i data-lucide="clock-3" class="w-4 h-4"></i> Línea de Tiempo de Servicios
                </h3>
            </div>

            <?php if(empty($historial)): ?>
                <div class="bg-white p-12 rounded-3xl text-center border border-dashed border-slate-200">
                    <i data-lucide="layers" class="w-12 h-12 text-slate-200 mx-auto mb-4"></i>
                    <p class="text-slate-400 font-bold uppercase text-xs">No hay registros históricos para mostrar</p>
                </div>
            <?php endif; ?>

            <div class="space-y-4">
                <?php foreach($historial as $h): 
                    $statusColors = [
                        'RECIBIDO' => 'bg-slate-100 text-slate-500',
                        'DIAGNOSTICANDO' => 'bg-amber-100 text-amber-600',
                        'EN_REPARACION' => 'bg-blue-100 text-blue-600',
                        'LISTO' => 'bg-emerald-100 text-emerald-600',
                        'ENTREGADO' => 'bg-navy-blue text-white',
                        'CANCELADO' => 'bg-rose-100 text-rose-600'
                    ];
                    $bgStatus = $statusColors[$h->estado] ?? 'bg-slate-100';
                ?>
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:shadow-md transition-shadow group">
                    <div class="flex flex-col md:flex-row justify-between gap-4">
                        <div class="flex-1 space-y-3">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-black text-navy-blue bg-slate-50 px-3 py-1 rounded-lg border border-slate-100">ORDEN #<?php echo $h->id; ?></span>
                                <span class="text-[10px] font-black uppercase px-3 py-1 rounded-lg <?php echo $bgStatus; ?> tracking-tighter">
                                    <?php echo $h->estado; ?>
                                </span>
                                <span class="text-[10px] text-slate-400 font-bold"><i data-lucide="calendar" class="w-3 h-3 inline"></i> <?php echo date('d M, Y', strtotime($h->fecha_ingreso)); ?></span>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 py-2 border-y border-slate-50">
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase">Kilometraje</p>
                                    <p class="text-xs font-bold text-slate-700"><?php echo is_numeric($h->kilometraje) ? number_format($h->kilometraje) : $h->kilometraje; ?> KM</p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase">Técnico</p>
                                    <p class="text-xs font-bold text-slate-700 uppercase"><?php echo $h->mecanico_nombre ?: 'Sin asignar'; ?></p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-[9px] font-black text-slate-400 uppercase">Diagnóstico / Motivo</p>
                                    <p class="text-xs text-slate-600 italic leading-snug">"<?php echo $h->diagnostico_entrada; ?>"</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex md:flex-col justify-end gap-2 border-t md:border-t-0 md:border-l border-slate-100 pt-3 md:pt-0 md:pl-5">
                            <button onclick="verDetalle(<?php echo $h->id; ?>)" class="flex-1 md:flex-none bg-slate-50 text-navy-blue p-2 rounded-xl hover:bg-navy-blue hover:text-white transition-all text-center" title="Ver Detalles Técnicos">
                                <i data-lucide="eye" class="w-4 h-4 mx-auto"></i>
                            </button>
                            <?php if($h->estado === 'LISTO'): ?>
                                <?php if (!empty($h->mecanico_id)): ?>
                                    <a href="<?php echo URLROOT; ?>/facturacion?orden_id=<?php echo $h->id; ?>" class="flex-1 md:flex-none bg-emerald-50 text-emerald-600 p-2 rounded-xl hover:bg-emerald-600 hover:text-white transition-all text-center" title="Facturar y Cobrar">
                                        <i data-lucide="receipt" class="w-4 h-4 mx-auto"></i>
                                    </a>
                                <?php else: ?>
                                    <button onclick="AppUtils.showToast('Debe asignar un mecánico antes de facturar', 'warning')" class="flex-1 md:flex-none bg-slate-50 text-slate-300 p-2 rounded-xl cursor-not-allowed" title="Sin Mecánico">
                                        <i data-lucide="receipt" class="w-4 h-4 mx-auto"></i>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <button onclick="imprimirOrden(<?php echo $h->id; ?>)" class="flex-1 md:flex-none bg-slate-50 text-slate-400 p-2 rounded-xl hover:text-blue-600 transition-all text-center">
                                <i data-lucide="printer" class="w-4 h-4 mx-auto"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>