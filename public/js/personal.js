document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const formStaff = document.getElementById('formStaff');
    const modal = document.getElementById('staffModal');
    const btnOpen = document.getElementById('btnOpenModal');
    const btnClose = document.getElementById('btnCloseModal');
    const btnCancel = document.getElementById('btnCancel');
    const totalCount = document.getElementById('totalCount');
    const searchInput = document.getElementById('searchStaff');

    let personal = [];

    const loadData = async () => {
        try {
            const res = await fetch(`${URLROOT}/personal/listar`);
            personal = await res.json();
            renderTable(personal);
        } catch (e) {
            console.error("Error al cargar personal:", e); // Corregido 'error' a 'e'
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-red-500">Error de conexión con la DB</td></tr>';
        }
    };

    const renderTable = (data) => {
        tableBody.innerHTML = '';
        totalCount.textContent = data.length;

        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-slate-400 italic">No hay personal registrado</td></tr>';
            return;
        }

        data.forEach(p => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 transition-colors border-b border-slate-100';

            const imgUrl = p.foto ? `${URLROOT}/${p.foto}` : `${URL_IMG}default.png`;

            row.innerHTML = `
                <td class="px-8 py-5 text-center">
                    <div class="flex flex-col items-center justify-center gap-1">
                        <img src="${imgUrl}" 
                             onclick="AppUtils.viewImage('${imgUrl}', '${p.nombre}')"
                             class="w-10 h-10 rounded-full object-cover border border-slate-200 cursor-zoom-in hover:opacity-80 transition-all shadow-sm" 
                             alt="Foto de ${p.nombre}">
                        <span class="font-mono text-[10px] text-slate-400 font-bold uppercase tracking-tighter leading-none">${p.id}</span>
                    </div>
                </td>
                <td class="px-8 py-5 font-bold text-slate-600 tracking-tighter">${p.cedula}</td>
                <td class="px-8 py-5 font-bold text-slate-700 uppercase">${p.nombre}</td>
                <td class="px-8 py-5"><span class="px-2 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold border border-blue-100">${p.cargo}</span></td>
                <td class="px-8 py-5">${p.username ? `<span class="text-neon-green flex items-center gap-1 text-xs font-bold"><i data-lucide="shield-check" class="w-3 h-3"></i> ${p.system_role}</span>` : '<span class="text-slate-300 text-xs">Sin acceso</span>'}</td>
                <td class="px-8 py-5">
                    <div class="text-slate-700 text-xs">${p.telefono || 'N/A'}</div>
                    <div class="text-slate-400 text-[10px]">${p.email || ''}</div>
                </td>
                <td class="px-8 py-5 text-right">
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
        lucide.createIcons();
    };

    document.getElementById('hasSystemAccess').addEventListener('change', (e) => {
        document.getElementById('userFields').classList.toggle('hidden', !e.target.checked);
    });

    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        const filtered = personal.filter(p =>
            p.nombre.toLowerCase().includes(term) ||
            p.cargo.toLowerCase().includes(term) ||
            p.id.toLowerCase().includes(term) ||
            (p.cedula && p.cedula.toLowerCase().includes(term))
        );
        renderTable(filtered);
    });

    formStaff.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(formStaff);
        const data = Object.fromEntries(formData.entries());
        data.has_system_access = document.getElementById('hasSystemAccess').checked;

        // Los campos deshabilitados no se incluyen en FormData, los recuperamos manualmente si es edición
        if (document.getElementById('staffId').disabled) {
            data.id = document.getElementById('staffId').value;
        }

        const res = await fetch(`${URLROOT}/personal/guardar`, {
            method: 'POST',
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            toggleModal(false);
            loadData();
            AppUtils.showToast(result.mensaje, 'success');
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
        const p = personal.find(x => x.id === id);
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
            await fetch(`${URLROOT}/personal/eliminar/${id}`, { method: 'DELETE' });
            loadData();
        });
    };
    loadData();
});