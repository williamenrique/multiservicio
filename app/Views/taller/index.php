<div class="space-y-6">
    <!-- Contenedor de Alertas Críticas -->
    <div id="alertasEntrega" class="grid grid-cols-1 md:grid-cols-2 gap-4 hidden"></div>

    <!-- Encabezado y Buscador -->
    <div class="bg-navy-blue p-6 rounded-xl border border-gray-800 shadow-lg">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                    <i data-lucide="wrench" class="text-neon-green"></i> Gestión de Taller
                </h2>
                <p class="text-gray-400">Control de órdenes de servicio y hoja de vida vehicular.</p>
            </div>
            <div class="flex flex-wrap gap-3 w-full md:w-auto">
                <div class="relative flex-1 md:min-w-[300px]">
                    <input type="text" id="busquedaPlaca" placeholder="Placa del vehículo..." 
                           class="w-full bg-slate-900 border border-gray-700 text-white px-4 py-2 rounded-lg focus:ring-2 focus:ring-neon-green outline-none">
                </div>
                <button onclick="buscarHistorial()" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i> Consultar
                </button>
                <a href="<?php echo URLROOT; ?>/taller/nuevaOrden" class="bg-neon-green hover:bg-opacity-80 text-navy-blue font-bold px-4 py-2 rounded-lg transition-all flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i> NUEVA O.S.
                </a>
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
                            <select onchange="cambiarEstado(<?php echo $o->id; ?>, this.value)" 
                                    class="text-xs border rounded-lg px-2 py-1 bg-white focus:ring-2 focus:ring-neon-green outline-none">
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
                                        <?php echo date('d/m H:i', strtotime($o->fecha_entrega_estimada)); ?>
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
                            <button onclick="imprimirOrden(<?php echo $o->id; ?>)" class="text-slate-400 hover:text-blue-600 p-2 transition-colors" title="Imprimir Orden">
                                <i data-lucide="printer" class="w-5 h-5"></i>
                            </button>
                            <button onclick="verDetalle(<?php echo $o->id; ?>)" class="text-navy-blue hover:text-neon-green p-2 transition-colors" title="Ver Detalles">
                                <i data-lucide="external-link" class="w-5 h-5"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function buscarHistorial() {
    const placa = document.getElementById('busquedaPlaca').value;
    if(placa) window.location.href = `<?php echo URLROOT; ?>/taller/historial/${placa}`;
    else AppUtils.showToast('Ingresa una placa', 'warning');
}

/**
 * Actualiza el estado de la orden en la base de datos
 */
async function cambiarEstado(id, estado) {
    try {
        const response = await fetch(`${URLROOT}/taller/cambiarEstado`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, estado })
        });
        const result = await response.json();
        
        if (result.success) {
            AppUtils.showToast(result.mensaje, 'success');
        } else {
            AppUtils.showToast(result.mensaje || 'Error al actualizar', 'error');
        }
    } catch (error) {
        AppUtils.showToast('Error de comunicación con el servidor', 'error');
    }
}

function verDetalle(id) {
    // Por ahora redirigimos al flujo de trabajo o mostramos un aviso informativo
    AppUtils.showToast('Cargando detalles técnicos de la Orden #' + id, 'info');
}

function imprimirOrden(id) {
    window.open(`${URLROOT}/taller/imprimir/${id}`, '_blank');
}
</script>