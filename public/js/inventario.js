document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const form = document.getElementById('formInventory');
    const modal = document.getElementById('inventoryModal');
    const searchInput = document.getElementById('searchInventory');
    const totalCount = document.getElementById('totalCount');
    const fileInput = document.getElementById('fileInput');
    const imagePreview = document.getElementById('imagePreview');

    // Asignamos la instancia a window para que el manejador del formulario pueda llamar a .reload()
    window.handler_inventario = new DataTableRefactor({
        tableId: 'inventario',
        tableBodyId: 'tableBody',
        endpoint: `${URLROOT}/inventario/listar`,
        searchInputId: 'searchInventory',
        limitSelectorId: 'limitSelector',
        paginationId: 'paginationControls',
        totalId: 'totalCount',
        onDataLoaded: (res) => {
            window.currentData = res.data;
            if (window.lucide) lucide.createIcons();
        },
        renderRow: (item) => {
            const isLow = item.stock <= (item.stock_minimo || 5);

            // LÓGICA DE RENDERIZADO DE IMAGEN
            const cleanPath = item.imagen ? item.imagen.trim() : null;
            const isDataUri = cleanPath && cleanPath.toLowerCase().startsWith('data:');
            const isRemote = cleanPath && cleanPath.toLowerCase().startsWith('http');
            const imgUrl = (isDataUri || isRemote) ? cleanPath : (cleanPath ? `${URLROOT}/${cleanPath}` : null);

            const stockFisico = parseFloat(item.stock);
            const stockDisponible = parseFloat(item.stock_disponible ?? item.stock);
            const reservado = stockFisico - stockDisponible;

            return `
                <tr class="hover:bg-slate-50 transition-colors group border-b border-slate-100 animate-in fade-in duration-300">
                    <td class="px-8 py-5 align-middle">
                        <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200 ${imgUrl ? 'cursor-zoom-in hover:opacity-80 transition-all shadow-sm' : ''}" 
                             ${imgUrl ? `onclick="AppUtils.viewImage(this.querySelector('img').src, '${item.nombre.replace(/'/g, "\\'")}')"` : ''}>
                            ${imgUrl ? `<img src="${imgUrl}" class="w-full h-full object-cover">` : `<i data-lucide="image" class="w-5 h-5 text-slate-400"></i>`}
                        </div>
                    </td>
                    <td class="px-8 py-5 font-bold text-base text-slate-700 uppercase tracking-tight align-middle">${item.nombre}</td>
                    <td class="px-8 py-5 align-middle">
                        <span class="text-xs font-black bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg uppercase tracking-wider">${item.categoria}</span>
                    </td>
                    <td class="px-8 py-5 align-middle font-mono text-base">
                        <span class="${isLow ? 'text-red-500 font-black' : 'text-slate-700 font-bold'}">${item.stock} uds</span>
                        ${reservado > 0 ? `<div class="text-xs text-orange-500 font-black uppercase tracking-tighter">Reservado: ${reservado}</div>` : ''}
                    </td>
                    <td class="px-8 py-5 align-middle font-black text-lg text-navy-blue">${AppUtils.formatCurrency(item.precio)}</td>
                    <td class="px-8 py-5 align-middle">
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase ${isLow ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'}">
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
                </tr>`;
        }
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

            if (!res.ok) throw new Error('Error en la comunicación con el servidor');
            const contentType = res.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) throw new Error('El servidor devolvió una respuesta inválida (HTML)');

            const result = await res.json();
            if (result.success) {
                toggleModal(false);
                if (window.handler_inventario) window.handler_inventario.reload();
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
        const item = window.currentData.find(i => i.id == id);

        document.getElementById('prodId').value = item.id;
        document.getElementById('prodNombre').value = item.nombre;
        document.getElementById('prodCategoria').value = item.categoria;
        document.getElementById('prodStock').value = item.stock;
        document.getElementById('prodStockMin').value = item.stock_minimo; // Cargar stock mínimo
        document.getElementById('prodPrecio').value = item.precio;
        document.getElementById('prodImagen').value = item.imagen || '';

        // Lógica de previsualización en edición
        if (item.imagen) {
            const cleanPath = item.imagen.trim();
            const isDataUri = cleanPath.toLowerCase().startsWith('data:');
            const isRemote = cleanPath.toLowerCase().startsWith('http');
            const imgUrl = (isDataUri || isRemote) ? cleanPath : `${URLROOT}/${cleanPath}`;
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
                if (window.handler_inventario) window.handler_inventario.reload();
            }
        });
    };
});