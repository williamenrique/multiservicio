document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formInventory');
    const modal = document.getElementById('inventoryModal');
    const searchInput = document.getElementById('searchInventory');
    const fileInput = document.getElementById('fileInput');
    const imagePreview = document.getElementById('imagePreview');

    let inventoryTable;

    inventoryTable = $('#inventoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: `${URLROOT}/inventario/listar`,
            type: 'GET'
        },
        columns: [
            {
                data: 'imagen',
                render: (data, type, row) => {
                    const imgUrl = data ? (data.startsWith('http') ? data : `${URLROOT}/${data}`) : null;
                    return `
                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200 ${imgUrl ? 'cursor-zoom-in' : ''}" 
                             ${imgUrl ? `onclick="AppUtils.viewImage('${imgUrl}', '${row.nombre}')"` : ''}>
                            ${imgUrl ? `<img src="${imgUrl}" class="w-full h-full object-cover">` : `<i data-lucide="package" class="w-5 h-5 text-slate-300"></i>`}
                        </div>`;
                }
            },
            { data: 'nombre', className: 'font-bold uppercase text-slate-700' },
            { data: 'categoria', render: d => `<span class="text-[10px] font-bold bg-slate-100 px-2 py-1 rounded-md text-slate-500">${d}</span>` },
            {
                data: 'stock',
                render: (data, type, row) => {
                    const isLow = data <= (row.stock_minimo || 5);
                    return `<span class="${isLow ? 'text-red-500 font-bold' : 'text-slate-600'}">${data} uds</span>`;
                }
            },
            { data: 'precio', render: d => `<span class="font-mono font-bold text-navy-blue">${AppUtils.formatCurrency(d)}</span>` },
            {
                data: 'stock',
                render: (data, type, row) => {
                    const isLow = data <= (row.stock_minimo || 5);
                    const label = data === 0 ? 'AGOTADO' : (isLow ? 'CRÍTICO' : 'OK');
                    return `<span class="text-[9px] font-black ${isLow ? 'text-red-600' : 'text-emerald-600'}">${label}</span>`;
                }
            },
            {
                data: 'id',
                className: 'text-right',
                render: (data) => {
                    if (USER_ROLE !== 'ADMINISTRADOR') return `<span class="text-[10px] italic text-slate-400">Lectura</span>`;
                    return `
                        <div class="flex justify-end gap-2 items-center">
                            <a href="${URLROOT}/inventario/kardex/${data}" class="w-8 h-8 flex items-center justify-center bg-slate-100 hover:bg-navy-blue hover:text-neon-green rounded-lg transition-all"><i data-lucide="history" class="w-4 h-4"></i></a>
                            <button onclick="editItem(${data})" class="w-8 h-8 flex items-center justify-center bg-slate-100 hover:bg-neon-green rounded-lg transition-all"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                            <button onclick="deleteItem(${data})" class="w-8 h-8 flex items-center justify-center bg-slate-100 hover:bg-red-500 hover:text-white rounded-lg transition-all"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                        </div>`;
                }
            }
        ],
        drawCallback: () => { if (window.lucide) lucide.createIcons(); },
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
    });

    // Búsqueda optimizada con Debounce
    searchInput.addEventListener('input', AppUtils.debounce((e) => {
        inventoryTable.search(e.target.value).draw();
    }, 400));

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
            inventoryTable.ajax.reload();
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

    window.editItem = async (id) => {
        const res = await fetch(`${URLROOT}/inventario/obtener/${id}`);
        const item = await res.json();

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

            const res = await fetch(`${URLROOT}/inventario/eliminar/${id}`, { method: 'DELETE' });
            const result = await res.json();
            if (result.success) {
                AppUtils.showToast('Producto eliminado');
                inventoryTable.ajax.reload();
            }
        });
    };
});