<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-black text-navy-blue uppercase tracking-wider">Bitácora de Auditoría</h1>
            <p class="text-slate-500 text-sm font-medium">Historial de acciones y seguridad del sistema</p>
        </div>
        <div class="flex gap-2">
            <button onclick="cargarLogs()" class="bg-white border border-slate-200 p-2 rounded-xl hover:bg-slate-50 transition-all shadow-sm">
                <i data-lucide="refresh-cw" class="w-5 h-5 text-slate-600"></i>
            </button>
        </div>
    </div>

    <!-- Tabla de Logs -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha y Hora</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Usuario</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Módulo</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Acción</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Descripción</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">IP</th>
                    </tr>
                </thead>
                <tbody id="logs-body" class="divide-y divide-slate-50">
                    <!-- Se carga mediante JS -->
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-8 h-8 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin"></div>
                                <p class="text-xs font-bold uppercase tracking-widest">Cargando registros...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function cargarLogs() {
    const tbody = document.getElementById('logs-body');
    try {
        const res = await fetch(`${URLROOT}/audit/listar`);
        const data = await res.json();

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-slate-400 uppercase text-xs font-bold tracking-widest">No hay registros encontrados</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(log => {
            const badgeColor = log.accion === 'DELETE' ? 'bg-red-100 text-red-600' : 
                               log.accion === 'CREATE' ? 'bg-green-100 text-green-600' : 
                               log.accion === 'LOGIN' ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-600';
            
            return `
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 text-xs font-bold text-navy-blue">${new Date(log.fecha).toLocaleString()}</td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-xs font-black text-slate-700 uppercase">${log.username || 'Sistema'}</span>
                            <span class="text-[10px] text-slate-400">${log.staff_name || ''}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4"><span class="text-[10px] font-black bg-slate-100 text-slate-500 px-2 py-1 rounded-md uppercase">${log.modulo}</span></td>
                    <td class="px-6 py-4"><span class="text-[10px] font-black ${badgeColor} px-2 py-1 rounded-md uppercase">${log.accion}</span></td>
                    <td class="px-6 py-4 text-xs text-slate-600 font-medium">${log.descripcion}</td>
                    <td class="px-6 py-4 text-[10px] font-mono text-slate-400">${log.ip_address}</td>
                </tr>
            `;
        }).join('');
        
        if (window.lucide) lucide.createIcons();
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-red-400 uppercase text-xs font-bold tracking-widest">Error al cargar los datos</td></tr>';
    }
}

// Inicializar
document.addEventListener('DOMContentLoaded', cargarLogs);
</script>