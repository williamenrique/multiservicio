<div class="space-y-6">
    <div class="flex flex-col lg:flex-row justify-between items-center bg-white p-6 rounded-2xl shadow-sm border border-slate-100 gap-4 mb-6">
        <div class="flex items-center gap-4">
            <a href="<?php echo URLROOT; ?>/taller" class="p-2 hover:bg-slate-100 rounded-xl transition-colors">
                <i data-lucide="arrow-left" class="text-slate-400"></i>
            </a>
            <h2 class="text-xl font-black text-navy-blue uppercase tracking-tighter">Expediente Técnico</h2>
        </div>
        <div class="flex gap-2 w-full lg:w-auto">
            <a href="<?php echo URLROOT; ?>/taller" class="flex-1 lg:flex-none justify-center bg-white border border-slate-200 text-navy-blue font-bold px-4 py-2 rounded-xl hover:bg-slate-50 transition-all flex items-center gap-2 uppercase text-xs">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Taller Activo
            </a>
            <a href="<?php echo URLROOT; ?>/taller/cerradas" class="flex-1 lg:flex-none justify-center bg-white border border-slate-200 text-navy-blue font-bold px-4 py-2 rounded-xl hover:bg-slate-50 transition-all flex items-center gap-2 uppercase text-xs">
                <i data-lucide="archive" class="w-4 h-4"></i> Historial Cerradas
            </a>
            <a href="<?php echo URLROOT; ?>/taller/nuevaOrden<?php echo $vehiculo ? '?placa='.$vehiculo->placa : ''; ?>" class="flex-1 lg:flex-none justify-center bg-neon-green text-navy-blue font-black px-4 py-2 rounded-xl hover:brightness-110 transition-all flex items-center gap-2 uppercase text-xs">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Nueva O.S.
            </a>
            <?php if($vehiculo): ?>
            <button onclick="generarQrVehiculo('<?php echo $vehiculo->placa; ?>')" class="flex-1 lg:flex-none justify-center bg-blue-600 text-white font-black px-4 py-2 rounded-xl hover:bg-blue-700 transition-all flex items-center gap-2 uppercase text-xs">
                <i data-lucide="qr-code" class="w-4 h-4"></i> Generar QR
            </button>
            <?php endif; ?>
        </div>
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

                    <!-- Sección de QR con botón de descarga -->
                    <div class="flex flex-col items-center gap-2 p-4 border border-slate-200 rounded-lg bg-white shadow-sm mt-4">
                        <img src="<?php echo URLROOT; ?>/consultas/generateVehicleQr/<?php echo $vehiculo->placa; ?>" alt="QR Code del Vehículo" class="w-32 h-32 object-contain">
                        <a href="<?php echo URLROOT; ?>/consultas/generateVehicleQr/<?php echo $vehiculo->placa; ?>" 
                           download="QR_<?php echo $vehiculo->placa; ?>.png" 
                           class="bg-navy-blue text-white text-xs font-bold px-3 py-1.5 rounded-md hover:bg-neon-green hover:text-black transition-colors flex items-center gap-1 w-full justify-center">
                            <i data-lucide="download" class="w-4 h-4"></i> Descargar QR
                        </a>
                        <p class="text-[9px] text-slate-400 text-center uppercase font-bold">Historial Público</p>
                    </div>
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
                        'RECIBIDO' => 'bg-indigo-100 text-indigo-700',
                        'DIAGNOSTICANDO' => 'bg-amber-100 text-amber-600',
                        'EN_REPARACION' => 'bg-blue-100 text-blue-600',
                        'LISTO' => 'bg-emerald-100 text-emerald-600',
                        'ENTREGADO' => 'bg-navy-blue text-white',
                        'CANCELADO' => 'bg-rose-100 text-rose-600'
                    ];
                    $bgStatus = $statusColors[$h->estado] ?? 'bg-slate-100 text-slate-500';

                    // Clases para resaltar el contenedor principal de la orden
                    $cardBaseClasses = 'rounded-2xl p-5 hover:shadow-md transition-shadow group';
                    $cardSpecificClasses = 'bg-white border border-slate-100 shadow-sm'; // Default para estados finalizados
                    $orderBadgeClasses = 'text-xs font-black text-navy-blue bg-slate-50 px-3 py-1 rounded-lg border border-slate-100';

                    switch ($h->estado) {
                        case 'RECIBIDO':
                            $cardSpecificClasses = 'bg-indigo-50/30 border-l-4 border-indigo-500 shadow-md';
                            $orderBadgeClasses = 'text-xs font-black text-indigo-700 bg-indigo-100 px-3 py-1 rounded-lg border border-indigo-200';
                            break;
                        case 'DIAGNOSTICANDO':
                            $cardSpecificClasses = 'bg-amber-50/50 border-l-4 border-amber-500 shadow-md';
                            $orderBadgeClasses = 'text-xs font-black text-amber-700 bg-amber-100 px-3 py-1 rounded-lg border border-amber-200';
                            break;
                        case 'EN_REPARACION':
                            $cardSpecificClasses = 'bg-blue-50/50 border-l-4 border-blue-500 shadow-md';
                            $orderBadgeClasses = 'text-xs font-black text-blue-700 bg-blue-100 px-3 py-1 rounded-lg border border-blue-200';
                            break;
                        // Para LISTO, ENTREGADO, CANCELADO, se mantienen las clases por defecto
                    }
                ?>
                <div class="<?php echo $cardBaseClasses; ?> <?php echo $cardSpecificClasses; ?>">
                    <div class="flex flex-col md:flex-row justify-between gap-4">
                        <div class="flex-1 space-y-3">
                            <div class="flex items-center gap-3">
                                <span class="<?php echo $orderBadgeClasses; ?>">ORDEN #<?php echo $h->id; ?></span>
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

                            <!-- Nueva sección: Checklist e Items facturados -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-3 pt-3 border-t border-slate-50">
                                <?php if(!empty($h->checklist_data)): ?>
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-2">Checklist de Entrada</p>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach($h->checklist_data as $chk): ?>
                                            <span class="text-[9px] px-2 py-0.5 rounded-full border border-slate-100 bg-slate-50 text-slate-500 flex items-center gap-1">
                                                <i data-lucide="<?php echo $chk->estado == 1 ? 'check-circle' : 'circle'; ?>" class="w-2.5 h-2.5 <?php echo $chk->estado == 1 ? 'text-emerald-500' : 'text-slate-300'; ?>"></i>
                                                <?php echo $chk->item; ?> <?php echo !empty($chk->observacion) ? "({$chk->observacion})" : ""; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if(!empty($h->items_facturados)): ?>
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-2">Trabajos y Repuestos Realizados</p>
                                    <div class="space-y-1">
                                        <?php foreach($h->items_facturados as $it): ?>
                                            <div class="flex justify-between items-center text-[10px] bg-blue-50/50 p-1.5 rounded-lg border border-blue-100/50">
                                                <span class="font-bold text-blue-700 uppercase truncate max-w-[180px]"><?php echo $it->descripcion; ?></span>
                                                <span class="bg-blue-600 text-white px-1.5 rounded font-black">x<?php echo $it->cantidad; ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
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

<script>
/**
 * Abre el PDF de la orden en una nueva pestaña
 */
function imprimirOrden(id) {
    window.open(`<?php echo URLROOT; ?>/taller/imprimir/${id}`, '_blank');
}

/**
 * Abre el modal de detalles técnicos (consumiendo la lógica global)
 */
function verDetalle(id) {
    if (typeof window.abrirModalDetalleOrden === 'function') {
        window.abrirModalDetalleOrden(id);
    } else {
        // Fallback en caso de que app.min.js no esté cargado o la función cambie
        AppUtils.showToast('Cargando expediente de la Orden #' + id, 'info');
    }
}

/**
 * Genera el código QR para el historial del vehículo y lo muestra en un modal.
 */
async function generarQrVehiculo(placa) {
    AppUtils.showLoading('Generando código QR...');
    try {
        const response = await fetch(`${URLROOT}/consultas/generateVehicleQr/${placa}`);
        if (!response.ok) {
            throw new Error('Error al generar el QR.');
        }
        const blob = await response.blob();
        const imageUrl = URL.createObjectURL(blob);
        AppUtils.hideLoading();
        Swal.fire({
            title: `QR para Historial de ${placa}`,
            imageUrl: imageUrl,
            imageAlt: 'Código QR del historial del vehículo',
            showCloseButton: true,
            showConfirmButton: false,
            html: `<p class="text-sm text-slate-500 mt-2">Escanee este código para ver el historial público del vehículo.</p>`
        });
    } catch (error) {
        AppUtils.hideLoading();
        AppUtils.showToast(error.message || 'Error desconocido al generar QR.', 'error');
        console.error("Error generando QR:", error);
    }
}
</script>