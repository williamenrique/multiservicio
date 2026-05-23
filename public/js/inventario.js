document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const form = document.getElementById('formInventory');
    const modal = document.getElementById('inventoryModal');
    const searchInput = document.getElementById('searchInventory');
    const totalCount = document.getElementById('totalCount');
    const fileInput = document.getElementById('fileInput');
    const imagePreview = document.getElementById('imagePreview');

    let inventarioTable;

    // Función para inicializar DataTable
    const initializeDataTable = () => {
        inventarioTable = $('#inventoryTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: `${URLROOT}/inventario/listar`,
                type: 'GET'
            },
            responsive: true,
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            columns: [
                {
                    data: 'imagen',
                    orderable: false,
                    render: function (data, type, row) {
                        const cleanPath = data ? data.trim() : null;
                        const isRemote = cleanPath && (cleanPath.toLowerCase().startsWith('http') || cleanPath.toLowerCase().startsWith('data:'));
                        const imgUrl = isRemote ? cleanPath : (cleanPath ? `${URLROOT}/${cleanPath}` : null);

                        return `
                            <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200 ${imgUrl ? 'cursor-zoom-in hover:opacity-80 transition-all' : ''}" 
                                 ${imgUrl ? `onclick="AppUtils.viewImage('${imgUrl}', '${row.nombre}')"` : ''}>
                                ${imgUrl ? `<img src="${imgUrl}" class="w-full h-full object-cover">` : `<i data-lucide="package" class="w-5 h-5 text-slate-300"></i>`}
                            </div>`;
                    }
                },
                { data: 'nombre', className: 'font-bold text-slate-700 uppercase' },
                {
                    data: 'categoria',
                    render: data => `<span class="text-[10px] font-bold bg-slate-100 px-2 py-1 rounded-md text-slate-500">${data}</span>`
                },
                {
                    data: 'stock',
                    render: (data, type, row) => {
                        const isLowStock = parseInt(data) <= (parseInt(row.stock_minimo) || 5);
                        const color = isLowStock ? 'text-red-500 font-bold' : 'text-slate-600';
                        return `<span class="${color}">${data} uds</span>`;
                    }
                },
                {
                    data: 'precio',
                    className: 'font-mono font-bold text-navy-blue',
                    render: data => AppUtils.formatCurrency(data)
                },
                {
                    data: 'stock',
                    render: (data, type, row) => {
                        const isLowStock = parseInt(data) <= (parseInt(row.stock_minimo) || 5);
                        const label = isLowStock ? (parseInt(data) === 0 ? 'AGOTADO' : 'CRÍTICO') : 'OK';
                        const color = isLowStock ? 'text-red-600' : 'text-emerald-600';
                        return `<span class="text-[9px] font-black ${color}">${label}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-right',
                    render: function (data, type, row) {
                        if (USER_ROLE !== 'ADMINISTRADOR') return `<span class="text-[10px] italic text-slate-400">Lectura</span>`;
                        return `
                            <div class="flex justify-end gap-2 items-center">
                                <a href="${URLROOT}/inventario/kardex/${row.id}" class="w-8 h-8 flex items-center justify-center bg-slate-100 hover:bg-navy-blue hover:text-neon-green rounded-lg transition-all" title="Ver Kardex"><i data-lucide="history" class="w-4 h-4"></i></a>
                                <button onclick="editItem(${row.id})" class="w-8 h-8 flex items-center justify-center bg-slate-100 hover:bg-neon-green rounded-lg transition-all"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                                <button onclick="deleteItem(${row.id})" class="w-8 h-8 flex items-center justify-center bg-slate-100 hover:bg-red-500 hover:text-white rounded-lg transition-all"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>`;
                    }
                }
            ],
            drawCallback: function (settings) {
                if (window.lucide) lucide.createIcons();

                // Actualizar el contador de productos total en la interfaz
                if (totalCount) {
                    totalCount.textContent = settings._iRecordsTotal;
                }
            }
        });
    };

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
            inventarioTable.ajax.reload(null, false);
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
        // Obtenemos los datos directamente de la tabla actual
        const item = inventarioTable.rows().data().toArray().find(i => i.id == id);
        if (!item) return;
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
                inventarioTable.ajax.reload(null, false);
            }
        });
    };

    // Inicializamos la tabla en lugar de llamar a loadData
    initializeDataTable();

    // Vinculamos el buscador externo con la lógica de Server-Side
    searchInput?.addEventListener('input', function () {
        inventarioTable.search(this.value).draw();
    });
});