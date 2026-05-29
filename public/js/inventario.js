document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const form = document.getElementById('formInventory');
    const modal = document.getElementById('inventoryModal');
    const searchInput = document.getElementById('searchInventory');
    const totalCount = document.getElementById('totalCount');
    const fileInput = document.getElementById('fileInput');
    const imagePreview = document.getElementById('imagePreview');

    let currentData = [];
    let state = {
        page: 1,
        limit: 10,
        search: '',
        total: 0,
        filtered: 0
    };

    /**
     * Carga de datos manual (Reemplaza AJAX de DataTables)
     */
    const loadInventory = async () => {
        try {
            const start = (state.page - 1) * state.limit;
            const params = new URLSearchParams({
                draw: 1,
                start: start,
                length: state.limit,
                'search[value]': state.search
            });

            const res = await fetch(`${URLROOT}/inventario/listar?${params.toString()}`);
            const result = await res.json();

            currentData = result.data;
            state.total = result.recordsTotal;
            state.filtered = result.recordsFiltered;

            if (totalCount) totalCount.textContent = state.total;

            renderTable();
            renderControls();
        } catch (error) {
            console.error("Error cargando inventario:", error);
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-red-500 font-bold uppercase">Error de conexión</td></tr>';
        }
    };

    const renderTable = () => {
        tableBody.innerHTML = '';
        if (currentData.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-20 text-slate-400 italic font-bold uppercase tracking-widest">No hay productos que coincidan</td></tr>';
            return;
        }

        currentData.forEach(item => {
            const isLow = item.stock <= (item.stock_minimo || 5);
            const cleanPath = item.imagen ? item.imagen.trim() : null;
            const isRemote = cleanPath && (cleanPath.toLowerCase().startsWith('http') || cleanPath.toLowerCase().startsWith('data:'));
            const imgUrl = isRemote ? cleanPath : (cleanPath ? `${URLROOT}/${cleanPath}` : null);

            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 transition-colors group border-b border-slate-100 animate-in fade-in duration-300';
            row.innerHTML = `
                <td class="px-8 py-5 align-middle">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200 ${imgUrl ? 'cursor-zoom-in hover:opacity-80 transition-all shadow-sm' : ''}" 
                         ${imgUrl ? `onclick="AppUtils.viewImage('${imgUrl}', '${item.nombre}')"` : ''}>
                        ${imgUrl ? `<img src="${imgUrl}" class="w-full h-full object-cover">` : `<i data-lucide="package" class="w-5 h-5 text-slate-300"></i>`}
                    </div>
                </td>
                <td class="px-8 py-5 font-bold text-slate-700 uppercase tracking-tight align-middle">${item.nombre}</td>
                <td class="px-8 py-5 align-middle">
                    <span class="text-[10px] font-black bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg uppercase tracking-wider">${item.categoria}</span>
                </td>
                <td class="px-8 py-5 align-middle font-mono text-sm">
                    <span class="${isLow ? 'text-red-500 font-bold' : 'text-slate-600'}">${item.stock} uds</span>
                </td>
                <td class="px-8 py-5 align-middle font-black text-navy-blue">${AppUtils.formatCurrency(item.precio)}</td>
                <td class="px-8 py-5 align-middle">
                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase ${isLow ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'}">
                        ${isLow ? (item.stock == 0 ? 'AGOTADO' : 'CRÍTICO') : 'OK'}
                    </span>
                </td>
                <td class="px-8 py-5 text-right align-middle">
                    ${USER_ROLE === 'ADMINISTRADOR' ? `
                        <div class="flex justify-end gap-2">
                            <a href="${URLROOT}/inventario/kardex/${item.id}" class="flex items-center justify-center w-9 h-9 bg-slate-100 hover:bg-navy-blue text-slate-500 hover:text-neon-green rounded-xl transition-all shadow-sm" title="Ver Kardex">
                                <i data-lucide="history" class="w-4 h-4"></i>
                            </a>
                            <button onclick="editItem(${item.id})" class="flex items-center justify-center w-9 h-9 bg-slate-100 hover:bg-neon-green text-slate-500 hover:text-black rounded-xl transition-all shadow-sm">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteItem(${item.id})" class="flex items-center justify-center w-9 h-9 bg-slate-100 hover:bg-red-500 text-slate-500 hover:text-white rounded-xl transition-all shadow-sm">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    ` : '<span class="text-[10px] italic text-slate-400 font-bold uppercase tracking-widest">Solo lectura</span>'}
                </td>
            `;
            tableBody.appendChild(row);
        });
        if (window.lucide) lucide.createIcons();
    };

    const renderControls = () => {
        const wrapper = document.getElementById('inventoryTable').closest('div');

        // 1. Asegurar o crear contenedores de controles
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

        // 2. Renderizar Selector de Cantidad
        top.innerHTML = `
            <div class="flex items-center gap-2 bg-slate-100/80 p-1.5 rounded-2xl border border-slate-200/60 shadow-inner select-none">
                <span class="pl-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Ver</span>
                <select onchange="window.updateLimit(this.value)" 
                    class="bg-white border-none rounded-xl px-3 py-1.5 text-xs font-black text-navy-blue outline-none focus:ring-2 focus:ring-neon-green/50 cursor-pointer shadow-sm h-9 min-w-[70px] text-center transition-transform active:scale-95">
                    ${[5, 10, 25, 50].map(v => `<option value="${v}" ${state.limit == v ? 'selected' : ''}>${v}</option>`).join('')}
                </select>
                <span class="pr-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Artículos</span>
            </div>
        `;

        // 3. Renderizar Leyenda y Paginación
        const start = (state.page - 1) * state.limit + 1;
        const end = Math.min(state.page * state.limit, state.filtered);
        const totalPages = Math.ceil(state.filtered / state.limit);

        // Lógica para mostrar un rango de páginas (ej: 1 ... 4 5 6 ... 10)
        let pageNumbers = [];
        if (totalPages <= 7) { // Si hay pocas páginas, mostrar todas
            for (let i = 1; i <= totalPages; i++) pageNumbers.push(i);
        } else { // Si hay muchas páginas, mostrar un rango
            pageNumbers.push(1);
            if (state.page > 4) pageNumbers.push('...');
            for (let i = Math.max(2, state.page - 2); i <= Math.min(totalPages - 1, state.page + 2); i++) pageNumbers.push(i);
            if (state.page < totalPages - 3) pageNumbers.push('...');
            if (totalPages > 1) pageNumbers.push(totalPages);
            // Eliminar duplicados y ordenar (necesario por la lógica de '...')
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
                            class="w-10 h-10 rounded-2xl text-[11px] font-black transition-all ${p === state.page ? 'bg-navy-blue text-neon-green shadow-lg shadow-navy-blue/20 border border-navy-blue' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-navy-blue shadow-sm cursor-pointer'}">
                            ${p}
                        </button>`;
        }).join('')}

                <button onclick="window.changePage(${state.page + 1})" ${state.page === totalPages ? 'disabled' : ''} 
                    class="flex items-center justify-center w-10 h-10 rounded-2xl transition-all ${state.page === totalPages ? 'text-slate-300 bg-slate-50 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-500 hover:bg-navy-blue hover:text-neon-green hover:border-navy-blue shadow-sm cursor-pointer'}">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </button>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
    };

    window.changePage = (p) => { if (p > 0) { state.page = p; loadInventory(); } };
    window.updateLimit = (l) => { state.limit = parseInt(l); state.page = 1; loadInventory(); };

    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.search = e.target.value;
            state.page = 1;
            loadInventory();
        }, 500);
    });

    // Previsualización de imagen (Local)
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                imagePreview.innerHTML = `<img src="${event.target.result}" class="w-full h-full object-cover">`;
            };
            reader.readAsDataURL(file);
        }
    });

    // Previsualización de imagen (URL)
    document.getElementById('prodImagen').addEventListener('input', (e) => {
        const url = e.target.value.trim();
        const isRemote = url.toLowerCase().startsWith('http') || url.toLowerCase().startsWith('data:');
        if (isRemote) {
            imagePreview.innerHTML = `<img src="${url}" class="w-full h-full object-cover">`;
        } else if (url === '') {
            imagePreview.innerHTML = '<i data-lucide="image" class="w-8 h-8 text-slate-300"></i>';
            lucide.createIcons();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();

        const btnSave = form.querySelector('button[type="submit"]');
        const originalText = btnSave.innerHTML;

        btnSave.disabled = true;
        btnSave.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i>';
        if (window.lucide) lucide.createIcons();

        const formData = new FormData(form);

        // Normalizar textos a MAYÚSCULAS en el FormData
        formData.set('nombre', document.getElementById('prodNombre').value.trim().toUpperCase());
        formData.set('categoria', document.getElementById('prodCategoria').value.trim().toUpperCase());

        // Adjuntar token CSRF
        formData.append('csrf_token', CSRF_TOKEN);

        try {
            const res = await fetch(`${URLROOT}/inventario/guardar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: formData
            });
            const result = await res.json();
            if (result.success) {
                toggleModal(false);
                loadInventory();
                AppUtils.showToast(result.mensaje);
            } else {
                AppUtils.showToast(result.error || 'Error al guardar', 'error');
            }
        } catch (error) {
            AppUtils.showToast('Error de conexión', 'error');
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = originalText;
            if (window.lucide) lucide.createIcons();
        }
    });

    const toggleModal = (show) => {
        modal.classList.toggle('hidden', !show);
        if (!show) {
            form.reset();
            document.getElementById('prodId').value = "";
            imagePreview.innerHTML = '<i data-lucide="image" class="w-8 h-8 text-slate-300"></i>';
            document.getElementById('modalTitle').textContent = "Registrar Producto";
            lucide.createIcons();
        }
    };

    document.getElementById('btnOpenModal')?.addEventListener('click', () => toggleModal(true));
    document.getElementById('btnCloseModal')?.addEventListener('click', () => toggleModal(false));
    document.getElementById('btnCancel')?.addEventListener('click', () => toggleModal(false));

    window.editItem = (id) => {
        const item = currentData.find(i => i.id == id);

        document.getElementById('prodId').value = item.id;
        document.getElementById('prodNombre').value = item.nombre;
        document.getElementById('prodCategoria').value = item.categoria;
        document.getElementById('prodStock').value = item.stock;
        document.getElementById('prodStockMin').value = item.stock_minimo; // Cargar stock mínimo
        document.getElementById('prodPrecio').value = item.precio;
        document.getElementById('prodImagen').value = item.imagen || '';

        if (item.imagen) {
            const cleanPath = item.imagen.trim();
            const isRemote = cleanPath.toLowerCase().startsWith('http') || cleanPath.toLowerCase().startsWith('data:');
            const imgUrl = isRemote
                ? cleanPath
                : `${URLROOT}/${cleanPath}`;
            imagePreview.innerHTML = `<img src="${imgUrl}" class="w-full h-full object-cover">`;
        }

        document.getElementById('modalTitle').textContent = "Editar Producto";
        toggleModal(true);
    };

    window.deleteItem = (id) => {
        AppUtils.confirmAction('¿Eliminar producto?', 'Esta acción no se puede deshacer.', async () => {

            const res = await fetch(`${URLROOT}/inventario/eliminar/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
            });
            const result = await res.json();
            if (result.success) {
                AppUtils.showToast('Producto eliminado');
                loadInventory();
            }
        });
    };

    loadInventory();
});