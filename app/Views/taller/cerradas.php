<div class="space-y-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Historial de Órdenes</h2>
            <p class="text-slate-500">Consulta de servicios finalizados y entregados al cliente</p>
        </div>
        <a href="<?php echo URLROOT; ?>/taller" class="bg-navy-blue text-white px-6 py-2 rounded-xl font-bold flex items-center gap-2 hover:opacity-90 transition shadow-lg">
            <i data-lucide="arrow-left" class="w-5 h-5"></i> Volver al Taller
        </a>
    </div>

    <!-- Filtros y Buscador -->
    <div class="glass-card p-4 rounded-xl mb-6 flex flex-wrap gap-4 items-center">
        <div class="relative flex-1 min-w-[300px]">
            <i data-lucide="search" class="absolute left-3 top-2.5 text-slate-400 w-5 h-5"></i>
            <input type="text" id="searchCerradas" placeholder="Buscar por placa, orden o cliente..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none transition-all">
        </div>
        <div class="flex items-center gap-4">
            <select id="limitSelector" class="bg-white border border-slate-200 rounded-lg py-2 px-3 text-xs font-bold text-navy-blue outline-none focus:ring-2 focus:ring-neon-green shadow-sm cursor-pointer">
                <option value="10">10 registros</option>
                <option value="25">25 registros</option>
                <option value="50">50 registros</option>
            </select>
        </div>
    </div>

    <!-- Tabla de Órdenes Cerradas -->
    <div class="glass-card rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table id="tableCerradas" class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Orden #</th>
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Vehículo / Placa</th>
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Cliente</th>
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Fecha Entrega</th>
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Responsable</th>
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBodyCerradas">
                    <!-- Llenado dinámico -->
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <div class="px-8 py-4 bg-white border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Mostrando <span id="startIndex">0</span> - <span id="endIndex">0</span> de <span id="totalItemsDisplay">0</span> servicios cerrados
            </div>
            <div class="flex items-center gap-2" id="paginationControls"></div>
        </div>
    </div>
</div>

<script>
    // Lógica de carga dinámica (Debe ir en public/js/taller_cerradas.js si prefieres separar)
    let currentPage = 1;
    let limit = 10;

    const cargarOrdenesCerradas = async (search = '') => {
        const offset = (currentPage - 1) * limit;
        const url = `${URLROOT}/taller/listarCerradas?limit=${limit}&offset=${offset}&q=${encodeURIComponent(search)}`;
        
        try {
            const res = await fetch(url);
            const { data, total, totalFiltrados } = await res.json();
            
            const tbody = document.getElementById('tableBodyCerradas');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-slate-400 italic">No se encontraron órdenes cerradas.</td></tr>';
                return;
            }

            tbody.innerHTML = data.map(o => `
                <tr class="hover:bg-slate-50 transition-colors border-b border-slate-50">
                    <td class="px-8 py-4 font-mono font-bold text-navy-blue">#${o.id}</td>
                    <td class="px-8 py-4">
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-800">${o.placa}</span>
                            <span class="text-[10px] text-slate-500 uppercase">${o.marca} ${o.modelo}</span>
                        </div>
                    </td>
                    <td class="px-8 py-4 text-sm font-medium text-slate-600">${o.cliente_nombre}</td>
                    <td class="px-8 py-4 text-xs font-bold text-slate-500">
                        ${new Date(o.fecha_entrega_real).toLocaleDateString()}
                    </td>
                    <td class="px-8 py-4 text-xs font-bold text-navy-blue uppercase">${o.mecanico_nombre || 'S/A'}</td>
                    <td class="px-8 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <button onclick="verDetalle(${o.id})" class="p-2 hover:bg-slate-100 rounded-lg transition-colors text-slate-400" title="Ver Expediente">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button onclick="window.open('${URLROOT}/taller/imprimir/${o.id}', '_blank')" class="p-2 hover:bg-slate-100 rounded-lg transition-colors text-slate-400" title="Imprimir Orden">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            
            document.getElementById('totalItemsDisplay').textContent = totalFiltrados;
            document.getElementById('startIndex').textContent = offset + 1;
            document.getElementById('endIndex').textContent = offset + data.length;

            if(window.lucide) lucide.createIcons();
        } catch (e) { console.error("Error al cargar historial", e); }
    };

    document.getElementById('searchCerradas').addEventListener('input', (e) => {
        currentPage = 1;
        cargarOrdenesCerradas(e.target.value);
    });

    document.addEventListener('DOMContentLoaded', () => cargarOrdenesCerradas());
</script>