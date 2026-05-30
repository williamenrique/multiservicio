document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const form = document.getElementById('formCliente');
    const modal = document.getElementById('clientModal');
    const searchInput = document.getElementById('searchClient');
    const totalCount = document.getElementById('totalCount');

    new DataTableRefactor({
        tableId: 'clientes',
        tableBodyId: 'tableBody',
        endpoint: `${URLROOT}/clientes/listar`,
        searchInputId: 'searchClient',
        limitSelectorId: 'limitSelector',
        paginationId: 'paginationControls',
        totalId: 'totalCount',
        renderRow: (item) => {
            return `
                <tr class="hover:bg-slate-50 transition-colors group border-b border-slate-100 animate-in fade-in duration-300">
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
                </tr>`;
        }
    });

        e.preventDefault();
        e.stopImmediatePropagation();

        const btnSave = form.querySelector('button[type="submit"]');
        const originalText = btnSave.innerHTML;

        // Prevenir doble envío
        btnSave.disabled = true;
        btnSave.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i>';
        if (window.lucide) lucide.createIcons();

        const formData = {
            id: document.getElementById('clientId').value.trim().toUpperCase(),
            nombre: document.getElementById('clientName').value.trim().toUpperCase(),
            email: document.getElementById('clientEmail').value.trim().toLowerCase(),
            telefono: document.getElementById('clientPhone').value.trim(),
            direccion: document.getElementById('clientAddress').value.trim().toUpperCase()
        };

        try {
            const res = await fetch(`${URLROOT}/clientes/guardar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(formData)
            });
            const result = await res.json();
            if (result.success) {
                toggleModal(false);
                if (window.handler_clientes) window.handler_clientes.reload();
                AppUtils.showToast(result.mensaje || 'Cliente guardado');
            } else {
                AppUtils.showToast(result.error || result.mensaje, 'error');
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
            const res = await fetch(`${URLROOT}/clientes/eliminar/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
            });
            const result = await res.json();
            if (result.success) {
                AppUtils.showToast('Cliente eliminado');
                if (window.handler_clientes) window.handler_clientes.reload();
            }
        });
    };
});