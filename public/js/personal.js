document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const formStaff = document.getElementById('formStaff');
    const modal = document.getElementById('staffModal');
    const btnOpen = document.getElementById('btnOpenModal');
    const btnClose = document.getElementById('btnCloseModal');
    const btnCancel = document.getElementById('btnCancel');
    const totalCount = document.getElementById('totalCount');
    const searchInput = document.getElementById('searchStaff');

    let currentData = [];
    let state = {
        page: 1,
        limit: 10,
        search: '',
        total: 0,
        filtered: 0
    };

    const loadData = async () => {
        try {
            const start = (state.page - 1) * state.limit;
            const params = new URLSearchParams({
                draw: 1,
                start: start,
                length: state.limit,
                'search[value]': state.search
            });

            const res = await fetch(`${URLROOT}/personal/listar?${params.toString()}`);
            const result = await res.json();

            // Soporte para respuesta paginada o array simple (fallback)
            if (Array.isArray(result)) {
                currentData = result;
                state.total = result.length;
                state.filtered = result.length;
            } else {
                currentData = result.data;
                state.total = result.recordsTotal;
                state.filtered = result.recordsFiltered;
            }

            if (totalCount) totalCount.textContent = state.total;

            renderTable();
            renderControls();
        } catch (e) {
            console.error("Error al cargar personal:", e);
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-red-500 font-bold uppercase">Error de conexión con la DB</td></tr>';
        }
    };

    const renderTable = () => {
        tableBody.innerHTML = '';
        if (currentData.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-20 text-slate-400 italic font-bold uppercase tracking-widest">No hay personal que coincida</td></tr>';
            return;
        }

        currentData.forEach(p => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 transition-colors group border-b border-slate-100 animate-in fade-in duration-300';
            const imgUrl = p.foto ? `${URLROOT}/${p.foto}` : `${URL_IMG}default.png`;

            row.innerHTML = `
                <td class="px-8 py-5 text-center align-middle">
                    <div class="flex flex-col items-center justify-center gap-1">
                        <img src="${imgUrl}" 
                             onclick="AppUtils.viewImage('${imgUrl}', '${p.nombre}')"
                             class="w-10 h-10 rounded-full object-cover border border-slate-200 cursor-zoom-in hover:opacity-80 transition-all shadow-sm" 
                             alt="Foto de ${p.nombre}">
                        <span class="font-mono text-[10px] text-slate-400 font-bold uppercase tracking-tighter leading-none">${p.username || 'N/A'}</span>
                    </div>
                </td>
                <td class="px-8 py-5 font-bold text-slate-600 tracking-tighter align-middle">${p.cedula}</td>
                <td class="px-8 py-5 font-bold text-slate-700 uppercase align-middle">${p.nombre}</td>
                <td class="px-8 py-5 align-middle"><span class="px-2 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold border border-blue-100">${p.cargo}</span></td>
                <td class="px-8 py-5 align-middle">${p.username ? `<span class="text-emerald-500 flex items-center gap-1 text-xs font-bold"><i data-lucide="shield-check" class="w-3 h-3"></i> ${p.system_role}</span>` : '<span class="text-slate-300 text-xs">Sin acceso</span>'}</td>
                <td class="px-8 py-5 align-middle">
                    <div class="text-slate-700 text-xs font-bold">${p.telefono || 'N/A'}</div>
                    <div class="text-slate-400 text-[10px]">${p.email || ''}</div>
                </td>
                <td class="px-8 py-5 text-right align-middle">
                    <div class="flex justify-end gap-2">
                        <button onclick="editStaff('${p.id}')" class="flex items-center justify-center w-9 h-9 bg-slate-100 hover:bg-neon-green text-slate-500 hover:text-black rounded-xl transition-all shadow-sm">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <button onclick="deleteStaff('${p.id}')" class="flex items-center justify-center w-9 h-9 bg-slate-100 hover:bg-red-500 text-slate-500 hover:text-white rounded-xl transition-all shadow-sm">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
        });
        if (window.lucide) lucide.createIcons();
    };

    const renderControls = () => {
        const table = document.getElementById('staffTable');
        if (!table) return;
        const wrapper = table.closest('div');

        let top = document.getElementById('custom-top-controls');
        if (!top) {
            top = document.createElement('div');
            top.id = 'custom-top-controls';
            top.className = 'flex flex-col sm:flex-row justify-between items-center gap-4 mb-6 px-8 py-4 bg-white/50 rounded-3xl border border-slate-100 shadow-sm mx-2';
            wrapper.parentNode.insertBefore(top, wrapper);
        }

        let bottom = document.getElementById('custom-bottom-controls');
        if (!bottom) {
            bottom = document.createElement('div');
            bottom.id = 'custom-bottom-controls';
            bottom.className = 'flex flex-col sm:flex-row justify-between items-center gap-6 mt-6 px-8 py-5 bg-white/50 rounded-3xl border border-slate-100 shadow-sm mx-2';
            wrapper.parentNode.insertBefore(bottom, wrapper.nextSibling);
        }

        top.innerHTML = `
            <div class="flex items-center gap-2 bg-slate-100/80 p-1.5 rounded-2xl border border-slate-200/60 shadow-inner select-none">
                <span class="pl-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Ver</span>
                <select onchange="window.updateLimit(this.value)" 
                    class="bg-white border-none rounded-xl px-3 py-1.5 text-xs font-black text-navy-blue outline-none focus:ring-2 focus:ring-neon-green/50 cursor-pointer shadow-sm h-9 min-w-[70px] text-center transition-transform active:scale-95">
                    ${[5, 10, 25, 50].map(v => `<option value="${v}" ${state.limit == v ? 'selected' : ''}>${v}</option>`).join('')}
                </select>
                <span class="pr-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Empleados</span>
            </div>
        `;

        const start = (state.page - 1) * state.limit + 1;
        const end = Math.min(state.page * state.limit, state.filtered);
        const totalPages = Math.ceil(state.filtered / state.limit) || 1;

        let pageNumbers = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pageNumbers.push(i);
        } else {
            pageNumbers.push(1);
            if (state.page > 4) pageNumbers.push('...');
            for (let i = Math.max(2, state.page - 2); i <= Math.min(totalPages - 1, state.page + 2); i++) pageNumbers.push(i);
            if (state.page < totalPages - 3) pageNumbers.push('...');
            if (totalPages > 1) pageNumbers.push(totalPages);
            pageNumbers = [...new Set(pageNumbers)].sort((a, b) => (a === '...' ? (b === '...' ? 0 : -1) : (b === '...' ? 1 : a - b)));
        }

        bottom.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-2.5 h-2.5 rounded-full bg-neon-green animate-pulse shadow-[0_0_8px_rgba(57,255,20,0.5)]"></div>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">
                    Mostrando <span class="text-navy-blue text-xs ml-1">${start}-${end}</span> <span class="text-slate-300 mx-2 text-lg font-thin">|</span> Total <span class="text-navy-blue text-xs ml-1">${state.filtered}</span>
                </span>
            </div>
            <div class="flex items-center gap-1.5 flex-wrap justify-center">
                <button onclick="window.changePage(${state.page - 1})" ${state.page === 1 ? 'disabled' : ''} 
                    class="flex items-center justify-center w-10 h-10 rounded-2xl transition-all ${state.page === 1 ? 'text-slate-300 bg-slate-50 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-500 hover:bg-navy-blue hover:text-neon-green hover:border-navy-blue shadow-sm cursor-pointer'}">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </button>
                ${pageNumbers.map(p => {
            if (p === '...') return `<span class="px-3 text-slate-300 font-black">...</span>`;
            return `<button onclick="window.changePage(${p})" 
                        class="w-10 h-10 rounded-2xl text-[11px] font-black transition-all ${p === state.page ? 'bg-navy-blue text-neon-green shadow-lg shadow-navy-blue/20 border border-navy-blue' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-navy-blue shadow-sm cursor-pointer'}">${p}</button>`;
        }).join('')}
                <button onclick="window.changePage(${state.page + 1})" ${state.page >= totalPages ? 'disabled' : ''} 
                    class="flex items-center justify-center w-10 h-10 rounded-2xl transition-all ${state.page >= totalPages ? 'text-slate-300 bg-slate-50 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-500 hover:bg-navy-blue hover:text-neon-green hover:border-navy-blue shadow-sm cursor-pointer'}">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </button>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
    };

    window.changePage = (p) => { if (p > 0 && p <= Math.ceil(state.filtered / state.limit)) { state.page = p; loadData(); } };
    window.updateLimit = (l) => { state.limit = parseInt(l); state.page = 1; loadData(); };

    document.getElementById('hasSystemAccess').addEventListener('change', (e) => {
        document.getElementById('userFields').classList.toggle('hidden', !e.target.checked);
    });

    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.search = e.target.value;
            state.page = 1;
            loadData();
        }, 500);
    });

    formStaff.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();

        const btnSave = formStaff.querySelector('button[type="submit"]');
        const originalText = btnSave.innerHTML;

        // Prevenir doble envío y mostrar carga
        btnSave.disabled = true;
        btnSave.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i>';
        if (window.lucide) lucide.createIcons();

        const formData = new FormData(formStaff);
        const data = Object.fromEntries(formData.entries());
        data.has_system_access = document.getElementById('hasSystemAccess').checked;

        // Normalización de datos a MAYÚSCULAS y limpieza
        data.nombre = (data.nombre || '').trim().toUpperCase();
        data.cargo = (data.cargo || '').trim().toUpperCase();
        data.direccion = (data.direccion || '').trim().toUpperCase();
        data.email = (data.email || '').trim().toLowerCase();

        // Los campos deshabilitados no se incluyen en FormData, los recuperamos manualmente si es edición
        if (document.getElementById('staffId').disabled) {
            data.id = document.getElementById('staffId').value;
        }

        try {
            const res = await fetch(`${URLROOT}/personal/guardar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                toggleModal(false);
                loadData();
                AppUtils.showToast(result.mensaje || 'Personal guardado correctamente', 'success');
            } else {
                AppUtils.showToast(result.error || result.mensaje, 'error');
            }
        } catch (error) {
            AppUtils.showToast('Error de conexión con el servidor', 'error');
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = originalText;
            if (window.lucide) lucide.createIcons();
        }
    });

    const toggleModal = (show) => {
        modal.classList.toggle('hidden', !show);
        if (!show) {
            formStaff.reset();
            document.getElementById('staffId').disabled = false;
            document.getElementById('userFields').classList.add('hidden');
            document.getElementById('modalTitle').textContent = "Registrar Empleado";
        }
    };

    btnOpen.addEventListener('click', () => toggleModal(true));
    btnClose.addEventListener('click', () => toggleModal(false));
    btnCancel.addEventListener('click', () => toggleModal(false));

    window.editStaff = (id) => {
        const p = currentData.find(x => x.id === id);
        document.getElementById('staffId').value = p.id;
        document.getElementById('staffId').disabled = true;
        document.getElementById('staffCedula').value = p.cedula;
        document.getElementById('staffNombre').value = p.nombre;
        document.getElementById('staffCargo').value = p.cargo;
        document.getElementById('staffTelefono').value = p.telefono;
        document.getElementById('staffEmail').value = p.email;
        document.getElementById('staffDireccion').value = p.direccion || '';

        if (p.username) {
            document.getElementById('hasSystemAccess').checked = true;
            document.getElementById('userFields').classList.remove('hidden');
            document.getElementById('staffUser').value = p.username;
            document.getElementById('staffRoleId').value = p.role_id;
        } else {
            document.getElementById('hasSystemAccess').checked = false;
            document.getElementById('userFields').classList.add('hidden');
        }

        document.getElementById('modalTitle').textContent = "Editar Empleado";
        toggleModal(true);
    };

    window.deleteStaff = (id) => {
        AppUtils.confirmAction('¿Eliminar empleado?', 'Esta acción borrará al empleado permanentemente.', async () => {
            await fetch(`${URLROOT}/personal/eliminar/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
            });
            loadData();
        });
    };
    loadData();
});