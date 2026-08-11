<div class="space-y-6">
    <div class="flex flex-col lg:flex-row justify-between items-center bg-white p-6 rounded-2xl shadow-sm border border-slate-100 gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Historial de Órdenes</h2>
            <p class="text-slate-500">Consulta de servicios finalizados y entregados al cliente</p>
        </div>
        <div class="flex gap-2 w-full lg:w-auto">
            <a href="<?php echo URLROOT; ?>/taller" class="flex-1 lg:flex-none justify-center bg-white border border-slate-200 text-navy-blue font-bold px-4 py-2 rounded-xl hover:bg-slate-50 transition-all flex items-center gap-2 uppercase text-xs">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Taller Activo
            </a>
            <a href="<?php echo URLROOT; ?>/taller/nuevaOrden" class="flex-1 lg:flex-none justify-center bg-neon-green text-navy-blue font-black px-4 py-2 rounded-xl hover:brightness-110 transition-all flex items-center gap-2 uppercase text-xs">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Nueva O.S.
            </a>
        </div>
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

<!-- Modal para Detalles de Orden Cerrada -->
<div id="detalleOrdenCerradaModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center hidden p-4">
    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 id="modalDetalleTitle" class="text-lg font-black text-navy-blue uppercase tracking-tighter">Detalle de Orden #<span id="detalleOrdenId"></span></h3>
            <button type="button" id="btnCloseDetalleModal" class="text-slate-400 hover:text-red-500 transition-colors">
                <i data-lucide="x-circle"></i>
            </button>
        </div>
        
        <div class="p-6 space-y-6 max-h-[80vh] overflow-y-auto">
            <!-- Sección de Información General -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="glass-card p-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Vehículo</p>
                    <p class="text-sm font-bold text-navy-blue uppercase" id="detallePlaca"></p>
                    <p class="text-xs text-slate-500 uppercase" id="detalleMarcaModelo"></p>
                    <p class="text-xs text-slate-500 uppercase" id="detalleAnioColor"></p>
                </div>
                <div class="glass-card p-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Cliente</p>
                    <p class="text-sm font-bold text-navy-blue uppercase" id="detalleClienteNombre"></p>
                    <p class="text-xs text-slate-500" id="detalleClienteTelefono"></p>
                </div>
                <div class="glass-card p-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Mecánico Asignado</p>
                    <p class="text-sm font-bold text-navy-blue uppercase" id="detalleMecanico"></p>
                </div>
                <div class="glass-card p-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Fechas</p>
                    <p class="text-xs text-slate-500">Ingreso: <span class="font-bold" id="detalleFechaIngreso"></span></p>
                    <p class="text-xs text-slate-500">Entrega Estimada: <span class="font-bold" id="detalleFechaEstimada"></span></p>
                    <p class="text-xs text-slate-500">Entrega Real: <span class="font-bold" id="detalleFechaReal"></span></p>
                </div>
            </div>

            <!-- Sección de Diagnóstico y Observaciones -->
            <div class="glass-card p-4">
                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Diagnóstico de Entrada / Observaciones</p>
                <p class="text-sm text-slate-700" id="detalleDiagnostico"></p>
            </div>

            <!-- Sección de Checklist -->
            <div class="glass-card p-4">
                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Checklist de Entrada</p>
                <ul id="detalleChecklist" class="list-disc list-inside text-sm text-slate-700 space-y-1"></ul>
            </div>

            <!-- Sección de Historial de Estados -->
            <div class="glass-card p-4">
                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Historial de Estados</p>
                <div id="detalleLogs" class="space-y-2"></div>
            </div>
        </div>

        <div class="p-6 border-t border-slate-100 flex justify-end gap-3 bg-slate-50/50">
            <a id="btnIrHistorialVehiculo" href="#" class="bg-navy-blue text-white px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:opacity-90 transition shadow-lg uppercase text-xs">
                <i data-lucide="car" class="w-4 h-4"></i> Ir a Historial del Vehículo
            </a>
            <button type="button" id="btnCerrarDetalleModal" class="px-4 py-2 border border-slate-200 text-slate-500 font-bold rounded-lg hover:bg-slate-50 transition-all uppercase text-xs">Cerrar</button>
        </div>
    </div>
</div>

<script>
    // Lógica de carga dinámica (Debe ir en public/js/taller_cerradas.js si prefieres separar)
    let currentPage = 1;
    let limit = 10;
    let totalPages = 0;

    // Function to open the detail modal for a closed order
    const openDetalleOrdenCerradaModal = async (id) => {
        const modal = document.getElementById('detalleOrdenCerradaModal');
        const modalTitle = document.getElementById('modalDetalleTitle');
        const detalleOrdenId = document.getElementById('detalleOrdenId');
        const detallePlaca = document.getElementById('detallePlaca');
        const detalleMarcaModelo = document.getElementById('detalleMarcaModelo');
        const detalleAnioColor = document.getElementById('detalleAnioColor');
        const detalleClienteNombre = document.getElementById('detalleClienteNombre');
        const detalleClienteTelefono = document.getElementById('detalleClienteTelefono');
        const detalleMecanico = document.getElementById('detalleMecanico');
        const detalleFechaIngreso = document.getElementById('detalleFechaIngreso');
        const detalleFechaEstimada = document.getElementById('detalleFechaEstimada');
        const detalleFechaReal = document.getElementById('detalleFechaReal');
        const detalleDiagnostico = document.getElementById('detalleDiagnostico');
        const detalleChecklist = document.getElementById('detalleChecklist');
        const detalleLogs = document.getElementById('detalleLogs');
        const btnIrHistorialVehiculo = document.getElementById('btnIrHistorialVehiculo');

        AppUtils.showLoading('Cargando detalles de la orden...');

        try {
            const res = await fetch(`${URLROOT}/taller/obtenerDetalle/${id}`);
            const result = await res.json();
            AppUtils.hideLoading();

            if (result.success) {
                const o = result.data;
                const logs = result.logs;
                const checklist = result.checklist;

                detalleOrdenId.textContent = o.id;
                detallePlaca.textContent = o.placa;
                detalleMarcaModelo.textContent = `${o.marca} ${o.modelo}`;
                detalleAnioColor.textContent = `${o.anio || 'N/A'} / ${o.color || 'N/A'}`;
                detalleClienteNombre.textContent = o.cliente_nombre;
                detalleClienteTelefono.textContent = o.cliente_telefono;
                detalleMecanico.textContent = o.mecanico_nombre || 'Sin Asignar';
                detalleFechaIngreso.textContent = new Date(o.fecha_ingreso).toLocaleString();
                detalleFechaEstimada.textContent = o.fecha_entrega_estimada ? new Date(o.fecha_entrega_estimada).toLocaleString() : 'N/A';
                detalleFechaReal.textContent = o.fecha_entrega_real ? new Date(o.fecha_entrega_real).toLocaleString() : 'N/A';
                detalleDiagnostico.textContent = o.diagnostico_entrada || 'Sin diagnóstico';

                // Populate checklist
                detalleChecklist.innerHTML = '';
                if (checklist && checklist.length > 0) {
                    checklist.forEach(item => {
                        const li = document.createElement('li');
                        li.className = 'flex items-center gap-2';
                        li.innerHTML = `<i data-lucide="${item.estado == 1 ? 'check-circle' : 'circle'}" class="w-4 h-4 ${item.estado == 1 ? 'text-emerald-500' : 'text-slate-400'}"></i>
                                        <span>${item.item} ${item.observacion ? `(${item.observacion})` : ''}</span>`;
                        detalleChecklist.appendChild(li);
                    });
                } else {
                    detalleChecklist.innerHTML = '<li class="text-slate-400 italic">No se registró checklist.</li>';
                }

                // Populate logs
                detalleLogs.innerHTML = '';
                if (logs && logs.length > 0) {
                    logs.forEach(log => {
                        const div = document.createElement('div');
                        div.className = 'text-xs text-slate-600';
                        div.innerHTML = `<span class="font-bold">${new Date(log.fecha).toLocaleString()}</span>: 
                                         ${log.usuario_nombre || 'Sistema'} cambió de <span class="font-medium">${log.estado_anterior}</span> a <span class="font-medium">${log.estado_nuevo}</span>.
                                         ${log.comentario ? `(${log.comentario})` : ''}`;
                        detalleLogs.appendChild(div);
                    });
                } else {
                    detalleLogs.innerHTML = '<div class="text-xs text-slate-400 italic">No hay historial de estados.</div>';
                }

                // Update "Ir a Historial del Vehículo" button
                btnIrHistorialVehiculo.href = `${URLROOT}/taller/historial/placa/${o.placa}`;

                modal.classList.remove('hidden');
                if(window.lucide) lucide.createIcons();
            } else {
                AppUtils.showToast(result.error || 'Error al cargar detalles de la orden.', 'error');
            }
        } catch (e) {
            AppUtils.hideLoading();
            AppUtils.showToast('Error de conexión al obtener detalles.', 'error');
            console.error("Error fetching order details:", e);
        }
    };

    window.verDetalle = openDetalleOrdenCerradaModal; // Hacemos la función globalmente accesible

    const cargarOrdenesCerradas = async (search = '') => {
        const offset = (currentPage - 1) * limit;
        const url = `${URLROOT}/taller/listarCerradas?limit=${limit}&offset=${offset}&q=${encodeURIComponent(search)}`;
        
        try {
            const res = await fetch(url);
            const { data, total, totalFiltrados } = await res.json(); // total is total records, totalFiltrados is after search
            
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
                            <a href="${URLROOT}/taller/historial/placa/${o.placa}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors text-slate-400" title="Ver Hoja de Vida">
                                <i data-lucide="history" class="w-4 h-4"></i>
                            </a>
                            <button onclick="window.verDetalle(${o.id})" class="p-2 hover:bg-slate-100 rounded-lg transition-colors text-slate-400" title="Ver Expediente">
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

    // Event listeners for closing the modal
    document.getElementById('btnCloseDetalleModal')?.addEventListener('click', () => {
        document.getElementById('detalleOrdenCerradaModal').classList.add('hidden');
    });
    document.getElementById('btnCerrarDetalleModal')?.addEventListener('click', () => {
        document.getElementById('detalleOrdenCerradaModal').classList.add('hidden');
    });


    document.addEventListener('DOMContentLoaded', () => cargarOrdenesCerradas());
</script>