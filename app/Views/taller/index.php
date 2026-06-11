<div class="space-y-6">
    <!-- Contenedor de Alertas Críticas -->
    <div id="alertasEntrega" class="grid grid-cols-1 md:grid-cols-2 gap-4 hidden"></div>

    <!-- Encabezado y Buscador -->
    <div class="bg-navy-blue p-6 rounded-xl border border-gray-800 shadow-lg">
        <div class="flex flex-col lg:flex-row justify-between items-center gap-6">
            <div>
                <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                    <i data-lucide="wrench" class="text-neon-green"></i> Gestión de Taller
                </h2>
                <p class="text-gray-400">Control de órdenes de servicio y hoja de vida vehicular.</p>
            </div>
            <div class="flex flex-col sm:flex-row flex-wrap gap-3 w-full lg:w-auto items-center">
                <div class="relative w-full sm:w-auto sm:min-w-[350px]">
                    <div class="relative">
                        <input type="text" id="inputBusquedaTaller" placeholder="Buscar placa, cliente, mecánico u orden..." 
                               class="w-full bg-slate-900 border border-gray-700 text-white px-10 py-2 rounded-lg focus:ring-2 focus:ring-neon-green outline-none">
                        <i data-lucide="search" class="absolute left-3 top-2.5 w-4 h-4 text-gray-500"></i>
                    </div>
                    <div id="resultadosTaller" class="absolute top-full left-0 w-full mt-2 bg-white rounded-xl shadow-2xl border border-slate-100 hidden z-50 overflow-hidden animate-in fade-in slide-in-from-top-1 duration-200"></div>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <a href="<?php echo URLROOT; ?>/taller/cerradas" class="flex-1 sm:flex-none justify-center bg-white border border-slate-200 text-navy-blue font-bold px-4 py-2 rounded-lg hover:bg-slate-50 transition-all flex items-center gap-2 uppercase text-xs">
                        <i data-lucide="archive" class="w-4 h-4"></i> Historial
                    </a>
                    <a href="<?php echo URLROOT; ?>/taller/nuevaOrden" class="flex-1 sm:flex-none justify-center bg-neon-green hover:bg-opacity-80 text-navy-blue font-bold px-4 py-2 rounded-lg transition-all flex items-center gap-2 uppercase text-xs">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Nueva O.S.
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Órdenes Activas -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-slate-700 uppercase text-sm tracking-wider flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4"></i> Vehículos en Taller
            </h3>
            <span class="bg-navy-blue text-white text-xs px-2 py-1 rounded-full"><?php echo count($ordenes); ?> Activos</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-[11px] uppercase tracking-widest">
                        <th class="px-6 py-4 font-bold">Orden #</th>
                        <th class="px-6 py-4 font-bold">Vehículo</th>
                        <th class="px-6 py-4 font-bold">Estado</th>
                        <th class="px-6 py-4 font-bold text-center">Entrega Estimada</th>
                        <th class="px-6 py-4 font-bold">Mecánico</th>
                        <th class="px-6 py-4 font-bold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($ordenes)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No hay vehículos en reparación actualmente.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach($ordenes as $o): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-mono font-bold text-navy-blue">#<?php echo $o->id; ?></td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-800"><?php echo $o->placa; ?></span>
                                <span class="text-xs text-slate-500"><?php echo "$o->marca $o->modelo"; ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <select onchange="cambiarEstado(<?php echo $o->id; ?>, this)" 
                                    class="status-select text-[10px] font-bold border rounded-lg px-2 py-1 bg-white focus:ring-2 focus:ring-neon-green outline-none transition-all <?php 
                                        echo $o->estado == 'RECIBIDO' ? 'text-indigo-600 border-indigo-200' : 
                                            ($o->estado == 'DIAGNOSTICANDO' ? 'text-amber-600 border-amber-200' : 
                                            ($o->estado == 'EN_REPARACION' ? 'text-blue-600 border-blue-200' : 
                                            ($o->estado == 'LISTO' ? 'text-emerald-600 border-emerald-400' : ''))); 
                                    ?>">
                                <option value="RECIBIDO" <?php echo $o->estado == 'RECIBIDO' ? 'selected' : ''; ?>>RECIBIDO</option>
                                <option value="DIAGNOSTICANDO" <?php echo $o->estado == 'DIAGNOSTICANDO' ? 'selected' : ''; ?>>DIAGNOSTICANDO</option>
                                <option value="EN_REPARACION" <?php echo $o->estado == 'EN_REPARACION' ? 'selected' : ''; ?>>EN REPARACIÓN</option>
                                <option value="LISTO" <?php echo $o->estado == 'LISTO' ? 'selected' : ''; ?>>LISTO PARA ENTREGA</option>
                            </select>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if($o->fecha_entrega_estimada): ?>
                                <div class="flex flex-col items-center">
                                    <span class="text-xs font-mono font-bold <?php echo $o->minutos_restantes < 0 ? 'text-rose-500' : ($o->minutos_restantes < 120 ? 'text-amber-500' : 'text-slate-600'); ?>">
                                        <?php echo date('d/m h:i A', strtotime($o->fecha_entrega_estimada)); ?>
                                    </span>
                                    <?php if($o->minutos_restantes < 0): ?>
                                        <span class="text-[9px] font-black text-rose-600 bg-rose-50 px-2 py-0.5 rounded uppercase alert-shake">¡RETRASADO!</span>
                                    <?php elseif($o->minutos_restantes < 120): ?>
                                        <span class="text-[9px] font-black text-amber-600 bg-amber-50 px-2 py-0.5 rounded uppercase animate-pulse">Cerca</span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-slate-300 italic text-xs">Sin fecha</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            <?php if (empty($o->mecanico_nombre)): ?>
                                <span class="flex items-center gap-1.5 text-rose-500 font-black animate-pulse uppercase tracking-widest text-[10px] bg-rose-50 px-2 py-1 rounded-lg border border-rose-100">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> Sin Asignar
                                </span>
                            <?php else: ?>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="user-cog" class="w-4 h-4 text-slate-400"></i>
                                    <span class="font-bold text-slate-700 uppercase"><?php echo $o->mecanico_nombre; ?></span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-1">
                                <!-- Botones de Acción Dinámicos (Reactivos al estado LISTO) -->
                                <button onclick="entregarVehiculo(<?php echo $o->id; ?>)" 
                                        class="btn-entrega text-emerald-500 hover:bg-emerald-50 p-2 rounded-lg transition-all <?php echo ($o->estado !== 'LISTO' || $o->factura_status === 'PENDIENTE') ? 'hidden' : ''; ?>" 
                                        title="Confirmar Entrega Técnica">
                                    <i data-lucide="check-square" class="w-5 h-5"></i>
                                </button>
                                
                                <a href="<?php echo URLROOT; ?>/facturacion?orden_id=<?php echo $o->id; ?>" 
                                   class="btn-facturar text-blue-500 hover:bg-blue-50 p-2 rounded-lg transition-all <?php echo ($o->estado !== 'LISTO' || empty($o->mecanico_id)) ? 'hidden' : ''; ?>" 
                                   title="Facturar Orden">
                                    <i data-lucide="receipt" class="w-5 h-5"></i>
                                </a>

                                <button onclick="AppUtils.showToast('Debe asignar un mecánico antes de facturar', 'warning')" 
                                        class="btn-no-mecanico text-slate-300 p-2 rounded-lg cursor-not-allowed <?php echo ($o->estado !== 'LISTO' || !empty($o->mecanico_id)) ? 'hidden' : ''; ?>" 
                                        title="Sin Mecánico">
                                    <i data-lucide="receipt" class="w-5 h-5"></i>
                                </button>

                                <button onclick="verDetalle(<?php echo $o->id; ?>)" class="text-navy-blue hover:bg-slate-100 p-2 rounded-lg transition-all" title="Detalles">
                                    <i data-lucide="external-link" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const inputBusqueda = document.getElementById('inputBusquedaTaller');
const resultadosContainer = document.getElementById('resultadosTaller');
let searchTimer;

inputBusqueda.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const term = inputBusqueda.value.trim();

    if (term.length < 2) {
        resultadosContainer.classList.add('hidden');
        return;
    }

    searchTimer = setTimeout(async () => {
        try {
            const resp = await fetch(`${URLROOT}/taller/buscar?q=${encodeURIComponent(term)}`);
            const data = await resp.json();
            
            if (data.success && data.results.length > 0) {
                resultadosContainer.innerHTML = data.results.map(res => `
                    <div onclick="window.location.href='${URLROOT}/taller/historial/${res.tipo}/${res.id}'" class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0 flex items-center gap-3 group">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-navy-blue group-hover:bg-neon-green transition-colors">
                            <i data-lucide="${res.icon || 'circle'}" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-navy-blue uppercase">${res.title}</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">${res.subtitle}</p>
                        </div>
                    </div>
                `).join('');
                resultadosContainer.classList.remove('hidden');
                if(window.lucide) lucide.createIcons();
            } else {
                resultadosContainer.innerHTML = '<div class="p-4 text-center text-slate-400 text-xs italic">No se encontraron coincidencias</div>';
                resultadosContainer.classList.remove('hidden');
            }
        } catch (e) { console.error("Search error", e); }
    }, 400);
});

// Cerrar resultados al click fuera
document.addEventListener('click', (e) => {
    if (!resultadosContainer.contains(e.target) && e.target !== inputBusqueda) {
        resultadosContainer.classList.add('hidden');
    }
});

/**
 * Actualiza el estado de la orden en la base de datos
 */
async function cambiarEstado(id, selectEl) {
    const estado = selectEl.value;
    try {
        const response = await fetch(`${URLROOT}/taller/cambiarEstado`, {
            method: 'POST', // Aseguramos que sea POST
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN // Añadimos el token CSRF aquí
            },
            body: JSON.stringify({ id, estado })
        });
        const result = await response.json();
        
        if (result.success) {
            AppUtils.showToast(result.mensaje, 'success');
            
            // Actualizar colores dinámicamente
            selectEl.classList.remove('text-slate-500', 'text-indigo-600', 'text-amber-600', 'text-blue-600', 'text-emerald-600', 'border-slate-200', 'border-indigo-200', 'border-amber-200', 'border-blue-200', 'border-emerald-400');
            
            const colors = {
                'RECIBIDO': ['text-indigo-600', 'border-indigo-200'],
                'DIAGNOSTICANDO': ['text-amber-600', 'border-amber-200'],
                'EN_REPARACION': ['text-blue-600', 'border-blue-200'],
                'LISTO': ['text-emerald-600', 'border-emerald-400']
            };
            
            if (colors[estado]) selectEl.classList.add(...colors[estado]);

            // Actualizar visibilidad de botones de acción en tiempo real
            const row = selectEl.closest('tr');
            const btnEntrega = row.querySelector('.btn-entrega');
            const btnFacturar = row.querySelector('.btn-facturar');
            const btnNoMec = row.querySelector('.btn-no-mecanico');

            if (estado === 'LISTO') {
                btnEntrega?.classList.remove('hidden');
                const hasMecanico = !row.querySelector('.animate-pulse'); // El label "Sin Asignar" tiene animate-pulse
                if (hasMecanico) {
                    btnFacturar?.classList.remove('hidden');
                    btnNoMec?.classList.add('hidden');
                } else {
                    btnFacturar?.classList.add('hidden');
                    btnNoMec?.classList.remove('hidden');
                }
            } else {
                btnEntrega?.classList.add('hidden');
                btnFacturar?.classList.add('hidden');
                btnNoMec?.classList.add('hidden');
            }
        } else {
            AppUtils.showToast(result.mensaje || 'Error al actualizar', 'error');
        }
    } catch (error) {
        AppUtils.showToast('Error de comunicación con el servidor', 'error');
    }
}

/**
 * Procesa la entrega final del vehículo
 */
async function entregarVehiculo(id) {
    const { value: nota } = await Swal.fire({
        title: 'ENTREGA DE VEHÍCULO',
        input: 'textarea',
        inputLabel: 'Nota de entrega o conformidad del cliente',
        inputPlaceholder: 'Ej: Se entrega vehículo lavado, cliente satisfecho...',
        showCancelButton: true,
        confirmButtonText: 'CONFIRMAR ENTREGA',
        confirmButtonColor: '#10b981'
    });

    if (nota !== undefined) {
        AppUtils.showLoading('Procesando salida...');
        const res = await fetch(`${URLROOT}/taller/entregarOrden`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ id, comentario: nota })
        });
        const result = await res.json();
        AppUtils.hideLoading();

        if (result.success) {
            AppUtils.showToast('Vehículo entregado. Orden finalizada.');
            setTimeout(() => location.reload(), 1000); // Recarga para limpiar la tabla
        }
    }
}

async function verDetalle(id) {
    // Si existe la función en app.min.js la llamamos, si no, usamos el fetch manual
    if (typeof window.abrirModalDetalleOrden === 'function') {
        window.abrirModalDetalleOrden(id);
    } else {
        AppUtils.showToast('Cargando detalles técnicos de la Orden #' + id, 'info');
        // El script app.min.js debería estar escuchando este evento o tener una función global
    }
}

function imprimirOrden(id) {
    window.open(`${URLROOT}/taller/imprimir/${id}`, '_blank');
}
</script>