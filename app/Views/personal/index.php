<div class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Gestión de empleados, cargos y datos de contacto.</p>
        </div>
        <button id="btnOpenModal" class="bg-neon-green text-black font-black px-6 py-3 rounded-xl flex items-center gap-2 transition-all transform hover:scale-[1.05] active:scale-95 shadow-lg shadow-neon-green/40 uppercase tracking-widest text-xs">
            <i data-lucide="user-plus"></i>
            NUEVO EMPLEADO
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="md:col-span-2 relative">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500"></i>
            <input type="text" id="searchStaff" placeholder="Buscar por nombre, cargo o documento..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-4 pl-12 pr-4 text-slate-700 placeholder-slate-400 outline-none focus:border-neon-green transition-all shadow-sm">
        </div>
        <div class="flex items-center gap-4">
            <div class="flex-1 flex items-center justify-between text-slate-500 text-sm bg-white border border-slate-200 rounded-xl px-4 py-3 shadow-sm h-full">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-slate-400"></i>
                    <span>Total:</span>
                </div>
                <strong id="totalCount" class="text-navy-blue text-lg">0</strong>
            </div>
            <select id="limitSelector" class="bg-white border border-slate-200 rounded-xl py-3 px-4 text-xs font-bold text-navy-blue outline-none focus:border-neon-green shadow-sm cursor-pointer">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <div class="glass-card rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                        <th class="px-8 py-6">ID Interno</th>
                        <th class="px-8 py-6">Cédula</th>
                        <th class="px-8 py-6">Empleado</th>
                        <th class="px-8 py-6">Cargo / Especialidad</th>
                        <th class="px-8 py-6">Acceso</th>
                        <th class="px-8 py-6">Contacto</th>
                        <th class="px-8 py-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-slate-100 text-sm text-slate-600">
                    <tr id="loadingRow">
                        <td colspan="7" class="px-8 py-16 text-center text-slate-400 italic animate-pulse font-medium">SINCRONIZANDO PERSONAL...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pie de Tabla Unificado -->
        <div class="px-8 py-4 bg-white border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Mostrando <span id="startIndex">0</span> - <span id="endIndex">0</span> de <span id="totalItemsDisplay">0</span> empleados
            </div>
            <div class="flex items-center gap-2" id="paginationControls"></div>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="staffModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h2 id="modalTitle" class="text-xl font-bold text-navy-blue uppercase tracking-wider">Registrar Empleado</h2>
            <button id="btnCloseModal" class="text-gray-500 hover:text-navy-blue">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <form id="formStaff" class="p-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">ID Interno</label>
                    <input type="text" name="id" id="staffId" required placeholder="STAFF-01" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Cédula</label>
                    <input type="text" name="cedula" id="staffCedula" required placeholder="V-123456" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Cargo</label>
                    <select name="cargo" id="staffCargo" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                        <option value="ADMINISTRADOR">ADMINISTRADOR</option>
                        <option value="MECANICO">MECANICO</option>
                        <option value="AYUDANTE">AYUDANTE</option>
                        <option value="VENDEDOR">VENDEDOR</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nombre Completo</label>
                <input type="text" name="nombre" id="staffNombre" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all uppercase">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Dirección de Residencia</label>
                <textarea name="direccion" id="staffDireccion" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all resize-none uppercase"></textarea>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-4">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="has_system_access" id="hasSystemAccess" class="w-4 h-4 text-neon-green border-slate-300 rounded focus:ring-neon-green">
                    <label for="hasSystemAccess" class="text-sm font-bold text-navy-blue">Habilitar Acceso al Sistema</label>
                </div>
                
                <div id="userFields" class="hidden grid grid-cols-2 gap-4 pt-2 border-t border-slate-200">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Usuario</label>
                        <input type="text" name="username" id="staffUser" class="w-full bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:border-neon-green">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Rol Sistema</label>
                        <select name="role_id" id="staffRoleId" class="w-full bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:border-neon-green">
                            <?php foreach($data['roles'] as $rol): ?>
                                <option value="<?php echo $rol->id; ?>"><?php echo s($rol->nombre_rol); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Contraseña (Opcional en edición)</label>
                        <input type="password" name="password" id="staffPass" class="w-full bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:border-neon-green">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Teléfono</label>
                    <input type="text" name="telefono" id="staffTelefono" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Email</label>
                    <input type="email" name="email" id="staffEmail" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" id="btnCancel" class="flex-1 bg-slate-100 text-slate-600 font-bold py-3 rounded-xl hover:bg-slate-200 uppercase text-xs">Cancelar</button>
                <button type="submit" id="btnSaveStaff" class="flex-1 bg-neon-green text-black font-black py-3 rounded-xl hover:scale-[1.02] uppercase text-xs flex items-center justify-center gap-2">Guardar Empleado</button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo URLROOT; ?>/js/personal.js"></script>

<script>
    /**
     * Manejo del envío del formulario de personal
     * Se integra aquí para corregir la posición del spinner y manejar el token CSRF
     */
    document.getElementById('formStaff')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopImmediatePropagation(); // Evita conflictos si hay otros listeners en personal.js

        const btnSave = document.getElementById('btnSaveStaff');
        if (btnSave.disabled) return;

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        
        // Capturar explícitamente el estado del acceso al sistema
        data.has_system_access = document.getElementById('hasSystemAccess').checked;

        try {
            btnSave.disabled = true;
            // Las clases flex items-center justify-center en el botón aseguran el centrado
            btnSave.innerHTML = '<i class="animate-spin w-4 h-4" data-lucide="loader-2"></i> PROCESANDO...';
            if (typeof lucide !== 'undefined') lucide.createIcons();

            const response = await fetch(`${URLROOT}/personal/guardar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                AppUtils.showToast(result.mensaje || 'Empleado guardado correctamente');
                const modal = document.getElementById('staffModal');
                if (modal) modal.classList.add('hidden');
                if (typeof window.loadStaff === 'function') window.loadStaff();
            } else {
                AppUtils.showToast(result.mensaje || 'Error al procesar la solicitud', 'error');
            }
        } catch (error) {
            AppUtils.showToast('Error de conexión con el servidor', 'error');
        } finally {
            btnSave.disabled = false;
            btnSave.textContent = 'Guardar Empleado';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    });
</script>

<script>
    // --- LOGICA DE PERSONAL UNIFICADA ---
    let staffState = { page: 1, limit: 10, search: '' };

    window.fetchStaff = async () => {
        const offset = (staffState.page - 1) * staffState.limit;
        try {
            const response = await fetch(`${URLROOT}/personal/listar?q=${staffState.search}&limit=${staffState.limit}&offset=${offset}`);
            const result = await response.json();
            if (result.success) {
                renderStaffTable(result.data);
                updateStaffUI(result.total, result.totalFiltrados || result.total);
            }
        } catch (e) { console.error("Error personal:", e); }
    };

    const renderStaffTable = (items) => {
        const body = document.getElementById('tableBody');
        if (!items || items.length === 0) {
            body.innerHTML = '<tr><td colspan="7" class="px-8 py-16 text-center text-slate-400 italic uppercase">No hay personal registrado</td></tr>';
            return;
        }
        body.innerHTML = items.map(item => `
            <tr class="hover:bg-slate-50 transition-colors group">
                <td class="px-8 py-4 font-mono font-bold text-xs text-slate-500">${item.id}</td>
                <td class="px-8 py-4 text-xs font-bold text-navy-blue">${item.cedula}</td>
                <td class="px-8 py-4 font-black text-navy-blue uppercase text-xs">${item.nombre}</td>
                <td class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">${item.cargo}</td>
                <td class="px-8 py-4">
                    <span class="px-2 py-1 rounded-md text-[9px] font-bold ${item.username ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-50 text-slate-400 border border-slate-100'}">
                        ${item.username ? item.system_role : 'SIN ACCESO'}
                    </span>
                </td>
                <td class="px-8 py-4 text-xs font-medium text-slate-500">${item.telefono}</td>
                <td class="px-8 py-4 text-right">
                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick="editStaff('${item.id}')" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                        <button onclick="deleteStaff('${item.id}')" class="p-2 text-red-500 hover:bg-red-50 rounded-lg"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
        if (window.lucide) lucide.createIcons();
    };

    const updateStaffUI = (total, filtered) => {
        const t = parseInt(total) || 0;
        const tf = parseInt(filtered) || 0;
        const p = staffState.page;
        const l = staffState.limit;

        const start = tf === 0 ? 0 : (p - 1) * l + 1;
        const end = Math.min(p * l, tf);

        document.getElementById('startIndex').textContent = start;
        document.getElementById('endIndex').textContent = end;
        document.getElementById('totalItemsDisplay').textContent = tf;
        document.getElementById('totalCount').textContent = t;

        const totalPages = Math.ceil(tf / l) || 1;
        const controls = document.getElementById('paginationControls');
        let html = '';
        if (totalPages > 1) {
            html = `
                <button onclick="changeStaffPage(${p - 1})" ${p === 1 ? 'disabled' : ''} class="p-2 border border-slate-200 rounded-lg bg-white disabled:opacity-30"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                <span class="px-4 text-[10px] font-black text-navy-blue uppercase">Página ${p} / ${totalPages}</span>
                <button onclick="changeStaffPage(${p + 1})" ${p === totalPages ? 'disabled' : ''} class="p-2 border border-slate-200 rounded-lg bg-white disabled:opacity-30"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
            `;
        }
        controls.innerHTML = html;
        if (window.lucide) lucide.createIcons();
    };

    window.changeStaffPage = (p) => { staffState.page = p; window.fetchStaff(); };

    document.getElementById('limitSelector')?.addEventListener('change', (e) => {
        staffState.limit = parseInt(e.target.value);
        staffState.page = 1;
        window.fetchStaff();
    });

    document.getElementById('searchStaff')?.addEventListener('input', (e) => {
        staffState.search = e.target.value.trim();
        staffState.page = 1;
        clearTimeout(window.stT);
        window.stT = setTimeout(window.fetchStaff, 300);
    });

    document.addEventListener('DOMContentLoaded', window.fetchStaff);
</script>
