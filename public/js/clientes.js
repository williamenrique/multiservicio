document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const form = document.getElementById('formCliente');
    const modal = document.getElementById('clientModal');
    const searchInput = document.getElementById('searchClient');
    const totalCount = document.getElementById('totalCount');

    let currentData = [];
    let state = {
        page: 1,
        limit: 10,
        search: '',
        total: 0,
        filtered: 0
    };

    const loadClients = async () => {
        try {
            const start = (state.page - 1) * state.limit;
            const params = new URLSearchParams({
                draw: 1,
                start: start,
                length: state.limit,
                'search[value]': state.search
            });

            const res = await fetch(`${URLROOT}/clientes/listar?${params.toString()}`);
            const result = await res.json();

            currentData = result.data;
            state.total = result.recordsTotal;
            state.filtered = result.recordsFiltered;

            if (totalCount) totalCount.textContent = state.total;

            renderTable();
            renderControls();
        } catch (error) {
            console.error("Error cargando clientes:", error);
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-red-500 font-bold uppercase">Error de conexión</td></tr>';
        }
    };

    const renderTable = () => {
        tableBody.innerHTML = '';
        if (currentData.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-20 text-slate-400 italic font-bold uppercase tracking-widest">No hay clientes que coincidan</td></tr>';
            return;
        }

        currentData.forEach(item => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 transition-colors group border-b border-slate-100 animate-in fade-in duration-300';
            row.innerHTML = `
                <td class="px-8 py-5 font-mono text-xs font-bold text-slate-400 align-middle">${item.id}</td>
                <td class="px-8 py-5 font-bold text-slate-700 uppercase tracking-tight align-middle">${item.nombre}</td>
                <td class="px-8 py-5 align-middle">
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-slate-600">${item.telefono || '---'}</span>
                        <span class="text-[10px] text-slate-400 lowercase">${item.email || '---'}</span>
                    </div>
                </td>
                <td class="px-8 py-5 align-middle">
                    <span class="text-[10px] font-black bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg uppercase tracking-wider">${item.direccion || 'N/A'}</span>
                </td>
                <td class="px-8 py-5 text-right align-middle">
                    <div class="flex justify-end gap-2">
                        <button onclick="editItem('${item.id}')" class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-neon-green text-slate-500 hover:text-black rounded-2xl transition-all shadow-sm">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        ${USER_ROLE === 'ADMINISTRADOR' ? `
                            <button onclick="deleteItem('${item.id}')" class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-red-500 text-slate-500 hover:text-white rounded-2xl transition-all shadow-sm">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
        });
        if (window.lucide) lucide.createIcons();
    };

    const renderControls = () => {
        const wrapper = tableBody.closest('div');
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
                <span class="pr-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Clientes</span>
            </div>
        `;

        const start = (state.page - 1) * state.limit + 1;
        const end = Math.min(state.page * state.limit, state.filtered);
        const totalPages = Math.ceil(state.filtered / state.limit);

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
            return `
                        <button onclick="window.changePage(${p})" 
                            class="w-10 h-10 rounded-2xl text-[11px] font-black transition-all ${p === state.page ? 'bg-navy-blue text-neon-green shadow-lg shadow-navy-blue/20 border border-navy-blue' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-navy-blue shadow-sm cursor-pointer'}">${p}</button>`;
        }).join('')}
                <button onclick="window.changePage(${state.page + 1})" ${state.page === totalPages ? 'disabled' : ''} 
                    class="flex items-center justify-center w-10 h-10 rounded-2xl transition-all ${state.page === totalPages ? 'text-slate-300 bg-slate-50 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-500 hover:bg-navy-blue hover:text-neon-green hover:border-navy-blue shadow-sm cursor-pointer'}">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </button>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
    };

    window.changePage = (p) => { if (p > 0) { state.page = p; loadClients(); } };
    window.updateLimit = (l) => { state.limit = parseInt(l); state.page = 1; loadClients(); };

    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.search = e.target.value;
            state.page = 1;
            loadClients();
        }, 500);
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = {
            id: document.getElementById('clientId').value,
            nombre: document.getElementById('clientName').value,
            email: document.getElementById('clientEmail').value,
            telefono: document.getElementById('clientPhone').value,
            direccion: document.getElementById('clientAddress').value
        };

        const res = await fetch(`${URLROOT}/clientes/guardar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        const result = await res.json();
        if (result.success) {
            toggleModal(false);
            loadClients();
            AppUtils.showToast(result.mensaje);
        }
    });

    const toggleModal = (show) => {
        modal.classList.toggle('hidden', !show);
        if (!show) {
            form.reset();
            document.getElementById('clientId').readOnly = false;
            document.getElementById('modalTitle').textContent = "Registrar Cliente";
        }
    };

    document.getElementById('btnOpenModal')?.addEventListener('click', () => toggleModal(true));
    document.getElementById('btnCloseModal')?.addEventListener('click', () => toggleModal(false));
    document.getElementById('btnCancel')?.addEventListener('click', () => toggleModal(false));

    window.editItem = async (id) => {
        const res = await fetch(`${URLROOT}/clientes/obtener/${id}`);
        const item = await res.json();

        document.getElementById('clientId').value = item.id;
        document.getElementById('clientId').readOnly = true;
        document.getElementById('clientName').value = item.nombre;
        document.getElementById('clientEmail').value = item.email;
        document.getElementById('clientPhone').value = item.telefono;
        document.getElementById('clientAddress').value = item.direccion;

        document.getElementById('modalTitle').textContent = "Editar Cliente";
        toggleModal(true);
    };

    window.deleteItem = (id) => {
        AppUtils.confirmAction('¿Eliminar cliente?', 'Esta acción no se puede deshacer.', async () => {
            const res = await fetch(`${URLROOT}/clientes/eliminar/${id}`, { method: 'DELETE' });
            const result = await res.json();
            if (result.success) {
                AppUtils.showToast('Cliente eliminado');
                loadClients();
            }
        });
    };

    loadClients();
});