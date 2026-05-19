document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const form = document.getElementById('formInventory');
    const modal = document.getElementById('inventoryModal');
    const searchInput = document.getElementById('searchInventory');
    const totalCount = document.getElementById('totalCount');
    const fileInput = document.getElementById('fileInput');
    const imagePreview = document.getElementById('imagePreview');

    let items = [];

    const loadData = async () => {
        try {
            const res = await fetch(`${URLROOT}/inventario/listar`);
            items = await res.json();
            renderTable(items);
        } catch (e) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-red-500">Error de comunicación.</td></tr>';
        }
    };

    const renderTable = (data) => {
        if ($.fn.DataTable.isDataTable('#inventoryTable')) {
            $('#inventoryTable').DataTable().destroy();
        }

        tableBody.innerHTML = '';
        totalCount.textContent = data.length;

        data.forEach(item => {
            const isLowStock = item.stock <= 5;
            const stockColor = isLowStock ? 'text-red-500 font-bold' : 'text-slate-600';
            const statusLabel = isLowStock ? (item.stock === 0 ? 'AGOTADO' : 'CRÍTICO') : 'OK';

            // Limpiar y validar la URL de la imagen
            const cleanPath = item.imagen ? item.imagen.trim() : null;
            // Normalizamos a minúsculas antes de comparar protocolos
            const isRemote = cleanPath && (cleanPath.toLowerCase().startsWith('http') || cleanPath.toLowerCase().startsWith('data:'));
            const imgUrl = isRemote
                ? cleanPath
                : (cleanPath ? `${URLROOT}/${cleanPath}` : null);

            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 transition-colors border-b border-slate-100';
            row.innerHTML = `
                <td class="px-8 py-4">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200 ${imgUrl ? 'cursor-zoom-in hover:opacity-80 transition-all' : ''}" 
                         ${imgUrl ? `onclick="AppUtils.viewImage('${imgUrl}', '${item.nombre}')"` : ''}>
                        ${imgUrl ? `<img src="${imgUrl}" class="w-full h-full object-cover">` : `<i data-lucide="package" class="w-5 h-5 text-slate-300"></i>`}
                    </div>
                </td>
                <td class="px-8 py-4 font-bold text-slate-700 uppercase">${item.nombre}</td>
                <td class="px-8 py-4"><span class="text-[10px] font-bold bg-slate-100 px-2 py-1 rounded-md text-slate-500">${item.categoria}</span></td>
                <td class="px-8 py-4 ${stockColor}">${item.stock} uds</td>
                <td class="px-8 py-4 font-mono font-bold text-navy-blue">${AppUtils.formatCurrency(item.precio)}</td>
                <td class="px-8 py-4"><span class="text-[9px] font-black ${isLowStock ? 'text-red-600' : 'text-emerald-600'}">${statusLabel}</span></td>
                <td class="px-8 py-4 text-right">
                    <div class="flex justify-end gap-2">
                        ${USER_ROLE === 'ADMINISTRADOR' ? `
                            <button onclick="editItem(${item.id})" class="w-8 h-8 flex items-center justify-center bg-slate-100 hover:bg-neon-green rounded-lg transition-all"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                            <button onclick="deleteItem(${item.id})" class="w-8 h-8 flex items-center justify-center bg-slate-100 hover:bg-red-500 hover:text-white rounded-lg transition-all"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                        ` : `<span class="text-[10px] italic text-slate-400">Lectura</span>`}
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
        });

        $('#inventoryTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            drawCallback: () => lucide.createIcons()
        });

        lucide.createIcons();
    };

    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        renderTable(items.filter(i => i.nombre.toLowerCase().includes(term) || i.categoria.toLowerCase().includes(term)));
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
        const formData = new FormData(form);

        const res = await fetch(`${URLROOT}/inventario/guardar`, {
            method: 'POST',
            body: formData // Enviamos FormData directamente
        });
        const result = await res.json();
        if (result.success) {
            toggleModal(false);
            loadData();
            AppUtils.showToast(result.mensaje);
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
        const item = items.find(i => i.id == id);
        document.getElementById('prodId').value = item.id;
        document.getElementById('prodNombre').value = item.nombre;
        document.getElementById('prodCategoria').value = item.categoria;
        document.getElementById('prodStock').value = item.stock;
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

            const res = await fetch(`${URLROOT}/inventario/eliminar/${id}`, { method: 'DELETE' });
            const result = await res.json();
            if (result.success) {
                AppUtils.showToast('Producto eliminado');
                loadData();
            }
        });
    };

    loadData();
});