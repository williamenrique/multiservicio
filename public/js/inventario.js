document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const form = document.getElementById('formInventory');
    const modal = document.getElementById('inventoryModal');
    const searchInput = document.getElementById('searchInventory');
    const totalCount = document.getElementById('totalCount');

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
        tableBody.innerHTML = '';
        totalCount.textContent = data.length;

        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="px-8 py-10 text-center text-slate-400 italic">No hay productos registrados en el inventario.</td></tr>';
            return;
        }

        data.forEach(item => {
            const stockColor = item.stock <= 5 ? 'text-red-500 font-bold' : 'text-slate-600';
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 transition-colors border-b border-slate-100';
            row.innerHTML = `
                <td class="px-8 py-4">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200">
                        ${item.imagen ? `<img src="${item.imagen}" class="w-full h-full object-cover">` : `<i data-lucide="package" class="w-5 h-5 text-slate-300"></i>`}
                    </div>
                </td>
                <td class="px-8 py-4 font-bold text-slate-700 uppercase">${item.nombre}</td>
                <td class="px-8 py-4"><span class="text-[10px] font-bold bg-slate-100 px-2 py-1 rounded-md text-slate-500">${item.categoria}</span></td>
                <td class="px-8 py-4 ${stockColor}">${item.stock} uds</td>
                <td class="px-8 py-4 font-mono font-bold text-navy-blue">${AppUtils.formatCurrency(item.precio)}</td>
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
        lucide.createIcons();
    };

    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        renderTable(items.filter(i => i.nombre.toLowerCase().includes(term) || i.categoria.toLowerCase().includes(term)));
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form).entries());
        const res = await fetch(`${URLROOT}/inventario/guardar`, {
            method: 'POST',
            body: JSON.stringify(data)
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
            document.getElementById('modalTitle').textContent = "Registrar Producto";
        }
    };

    document.getElementById('btnOpenModal')?.addEventListener('click', () => toggleModal(true));
    document.getElementById('btnCloseModal').addEventListener('click', () => toggleModal(false));
    document.getElementById('btnCancel').addEventListener('click', () => toggleModal(false));

    window.editItem = (id) => {
        const item = items.find(i => i.id == id);
        document.getElementById('prodId').value = item.id;
        document.getElementById('prodNombre').value = item.nombre;
        document.getElementById('prodCategoria').value = item.categoria;
        document.getElementById('prodStock').value = item.stock;
        document.getElementById('prodPrecio').value = item.precio;
        document.getElementById('prodImagen').value = item.imagen || '';
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