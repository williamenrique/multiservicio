document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const formProveedor = document.getElementById('formProveedor');
    const modal = document.getElementById('proveedorModal');
    const btnOpen = document.getElementById('btnOpenModal');
    const btnClose = document.getElementById('btnCloseModal');
    const btnCancel = document.getElementById('btnCancel');
    const totalCount = document.getElementById('totalCount');
    const searchInput = document.getElementById('searchProveedor');

    let proveedores = [];

    const loadData = async () => {
        try {
            const res = await fetch(`${URLROOT}/proveedores/listar`);
            proveedores = await res.json();
            renderTable(proveedores);
        } catch (e) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-10 text-red-500">Error de conexión</td></tr>';
        }
    };

    const renderTable = (data) => {
        tableBody.innerHTML = '';
        totalCount.textContent = data.length;

        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-10 text-slate-400 italic">No hay proveedores registrados</td></tr>';
            return;
        }

        data.forEach(p => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 transition-colors border-b border-slate-100';
            row.innerHTML = `
                <td class="px-8 py-5 font-mono text-xs text-slate-500">${p.id}</td>
                <td class="px-8 py-5 font-bold text-slate-700 uppercase">${p.nombre}</td>
                <td class="px-8 py-5">
                    <div class="text-slate-700">${p.telefono || 'N/A'}</div>
                    <div class="text-slate-400 text-xs">${p.email || ''}</div>
                </td>
                <td class="px-8 py-5 text-right">
                    <button onclick="editProveedor('${p.id}')" class="p-2 bg-slate-100 hover:bg-neon-green rounded-xl transition-all mr-1"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                    <button onclick="deleteProveedor('${p.id}')" class="p-2 bg-slate-100 hover:bg-red-500 hover:text-white rounded-xl transition-all"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </td>
            `;
            tableBody.appendChild(row);
        });
        lucide.createIcons();
    };

    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        const filtered = proveedores.filter(p => p.nombre.toLowerCase().includes(term) || p.id.includes(term));
        renderTable(filtered);
    });

    formProveedor.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(formProveedor);
        const data = Object.fromEntries(formData.entries());

        // Los campos deshabilitados no se incluyen en FormData, los recuperamos manualmente si es edición
        if (document.getElementById('provId').disabled) {
            data.id = document.getElementById('provId').value;
        }

        const res = await fetch(`${URLROOT}/proveedores/guardar`, {
            method: 'POST',
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            toggleModal(false);
            loadData();
        }
    });

    const toggleModal = (show) => {
        modal.classList.toggle('hidden', !show);
        if (!show) {
            formProveedor.reset();
            document.getElementById('provId').disabled = false;
            document.getElementById('modalTitle').textContent = "Registrar Proveedor";
        }
    };

    btnOpen.addEventListener('click', () => toggleModal(true));
    btnClose.addEventListener('click', () => toggleModal(false));
    btnCancel.addEventListener('click', () => toggleModal(false));

    window.editProveedor = (id) => {
        const p = proveedores.find(x => x.id === id);
        document.getElementById('provId').value = p.id;
        document.getElementById('provId').disabled = true;
        document.getElementById('provNombre').value = p.nombre;
        document.getElementById('provTelefono').value = p.telefono;
        document.getElementById('provEmail').value = p.email;
        document.getElementById('provDireccion').value = p.direccion;
        document.getElementById('modalTitle').textContent = "Editar Proveedor";
        toggleModal(true);
    };

    window.deleteProveedor = (id) => {
        AppUtils.confirmAction('¿Eliminar?', 'Esta acción borrará al proveedor.', async () => {
            await fetch(`${URLROOT}/proveedores/eliminar/${id}`, { method: 'DELETE' });
            loadData();
        });
    };

    loadData();
});