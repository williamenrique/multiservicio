<div class="space-y-6">
    <?php if (!$vehiculo && !$entidad): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-xl shadow-sm flex items-center gap-4 text-red-700">
            <i data-lucide="alert-circle" class="w-8 h-8"></i>
            <div>
                <h3 class="font-bold">Información no encontrada</h3>
                <p>No se encontraron registros para la consulta realizada.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Info Vehículo -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden h-fit">
                <div class="bg-navy-blue p-6 text-center">
                    <?php if($vehiculo): ?>
                    <h2 class="text-3xl font-black text-neon-green tracking-tighter"><?php echo $vehiculo->placa; ?></h2>
                    <p class="text-gray-400 text-sm"><?php echo "$vehiculo->marca $vehiculo->modelo"; ?></p>
                    <?php else: ?>
                    <h2 class="text-xl font-black text-neon-green tracking-tighter uppercase"><?php echo $entidad->nombre; ?></h2>
                    <p class="text-gray-400 text-sm italic"><?php echo $tipo === 'MECANICO' ? 'Técnico Especialista' : 'Cliente Registrado'; ?></p>
                    <?php endif; ?>
                </div>
                <div class="p-6 space-y-4">
                    <?php if($vehiculo): ?>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500 text-sm">Propietario</span>
                        <span class="font-bold text-slate-800"><?php echo $vehiculo->cliente_nombre; ?></span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500 text-sm">Color</span>
                        <span class="font-bold text-slate-800"><?php echo $vehiculo->color; ?></span>
                    </div>
                    <?php else: ?>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500 text-sm">Identificación</span>
                        <span class="font-bold text-slate-800"><?php echo $entidad->cedula ?? $entidad->id; ?></span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500 text-sm">Total Trabajos</span>
                        <span class="font-bold text-navy-blue"><?php echo count($historial); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Línea de Tiempo -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i data-lucide="history" class="text-navy-blue"></i> Hoja de Vida Vehicular
                </h3>
                <div class="relative space-y-8 before:absolute before:inset-0 before:ml-5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-300 before:to-transparent">
                    <?php foreach($historial as $h): ?>
                        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full border border-white bg-slate-200 group-hover:bg-neon-green text-slate-500 group-hover:text-navy-blue shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 transition-colors">
                                <i data-lucide="check-circle" class="w-5 h-5"></i>
                            </div>
                            <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:border-navy-blue transition-all">
                                <div class="flex items-center justify-between space-x-2 mb-1">
                                    <div class="font-bold text-slate-800">Orden #<?php echo $h->id; ?></div>
                                    <time class="font-mono text-xs text-navy-blue font-bold"><?php echo date('d/m/Y', strtotime($h->fecha_ingreso)); ?></time>
                                </div>
                                <div class="text-slate-500 text-xs mb-2">
                                    <?php if($tipo !== 'PLACA'): ?> <span class="font-black text-navy-blue">[<?php echo $h->placa; ?>]</span> | <?php endif; ?>
                                    KM: <span class="font-bold"><?php echo is_numeric($h->kilometraje) ? number_format($h->kilometraje) : $h->kilometraje; ?></span> | 
                                    Estado: <span class="uppercase font-bold text-neon-green bg-navy-blue px-2 py-0.5 rounded text-[10px]"><?php echo $h->estado; ?></span>
                                </div>
                                <div class="text-slate-600 text-sm italic">"<?php echo $h->diagnostico_entrada; ?>"</div>
                                <?php if($tipo !== 'MECANICO' && !empty($h->mecanico_nombre)): ?>
                                <p class="text-[9px] text-slate-400 mt-2 font-bold uppercase">Atendido por: <?php echo $h->mecanico_nombre; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>