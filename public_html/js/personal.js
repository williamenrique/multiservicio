document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const formStaff = document.getElementById('formStaff');
    const modal = document.getElementById('staffModal');
    const btnOpen = document.getElementById('btnOpenModal');
    const btnClose = document.getElementById('btnCloseModal');
    const btnCancel = document.getElementById('btnCancel');
    const totalCount = document.getElementById('totalCount');
    const searchInput = document.getElementById('searchStaff');

    const invalidFields = new Set();

    new DataTableRefactor({
        tableId: 'personal',
        tableBodyId: 'tableBody',
        endpoint: `${URLROOT}/personal/listar`,
        searchInputId: 'searchStaff',
        limitSelectorId: 'limitSelector',
        paginationId: 'paginationControls',
        totalId: 'totalCount',
        onDataLoaded: (res) => {
            window.currentData = res.data;
        },
        renderRow: (p) => {
            const imgUrl = p.foto ? `${URLROOT}/${p.foto}` : `${URL_IMG}default.png`;
            return `
                <tr class="hover:bg-slate-50 transition-colors group border-b border-slate-100 animate-in fade-in duration-300">
                    <td class="px-8 py-5 text-center align-middle">
                        <div class="flex flex-col items-center justify-center gap-1">
                            <img src="${imgUrl}" 
                                 onclick="AppUtils.viewImage('${imgUrl}', '${p.nombre}')"
                                 class="w-10 h-10 rounded-full object-cover border border-slate-200 cursor-zoom-in hover:opacity-80 transition-all shadow-sm" 
                                 alt="Foto de ${p.nombre}">
                            <span class="font-mono text-[10px] text-slate-400 font-bold uppercase tracking-tighter leading-none">${p.username || 'N/A'}</span>
                        </div>
                    </td>
                    <td class="px-8 py-5 font-bold text-slate-600 tracking-tighter align-middle">${p.cedula}</td>
                    <td class="px-8 py-5 font-bold text-slate-700 uppercase align-middle">${p.nombre}</td>
                    <td class="px-8 py-5 align-middle"><span class="px-2 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold border border-blue-100">${p.cargo}</span></td>
                    <td class="px-8 py-5 align-middle">${p.username ? `<span class="text-emerald-500 flex items-center gap-1 text-xs font-bold"><i data-lucide="shield-check" class="w-3 h-3"></i> ${p.system_role}</span>` : '<span class="text-slate-300 text-xs">Sin acceso</span>'}</td>
                    <td class="px-8 py-5 align-middle">
                        <div class="text-slate-700 text-xs font-bold">${p.telefono || 'N/A'}</div>
                        <div class="text-slate-400 text-[10px]">${p.email || ''}</div>
                    </td>
                    <td class="px-8 py-5 text-right align-middle">
                        <div class="flex justify-end gap-2">
                            <button onclick="editStaff('${p.id}')" class="flex items-center justify-center w-9 h-9 bg-slate-100 hover:bg-neon-green text-slate-500 hover:text-black rounded-xl transition-all shadow-sm">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteStaff('${p.id}')" class="flex items-center justify-center w-9 h-9 bg-slate-100 hover:bg-red-500 text-slate-500 hover:text-white rounded-xl transition-all shadow-sm">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        }
    });

    // Validaciones dinámicas de unicidad (Cédula y Usuario)
    const staffCedulaInput = document.getElementById('staffCedula');
    const staffUserInput = document.getElementById('staffUser');
    const staffEmailInput = document.getElementById('staffEmail');
    const staffIdInput = document.getElementById('staffId');

    const setValidationIcon = (inputElement, status) => {
        let iconContainer = inputElement.parentElement.querySelector('.validation-icon-container');
        if (!iconContainer) {
            iconContainer = document.createElement('div');
            // Posicionamiento absoluto a la derecha del input, asumiendo estructura de label + input
            iconContainer.className = 'validation-icon-container absolute right-3 top-[38px] flex items-center pointer-events-none';
            inputElement.parentElement.classList.add('relative');
            inputElement.parentElement.appendChild(iconContainer);
        }

        if (status === 'error') {
            iconContainer.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 text-red-500 animate-in zoom-in duration-300"></i>';
        } else if (status === 'success') {
            iconContainer.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 animate-in zoom-in duration-300"></i>';
        } else {
            iconContainer.innerHTML = '';
        }
        if (window.lucide) lucide.createIcons();
    };

    const checkUniqueness = async (inputElement, endpoint, fieldLabel) => {
        const value = inputElement.value.trim();
        if (!value) {
            inputElement.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
            setValidationIcon(inputElement, 'none');
            return;
        }
        const currentId = staffIdInput.value;
        try {
            const res = await fetch(`${URLROOT}/personal/${endpoint}?value=${encodeURIComponent(value)}&id=${currentId}`);
            const result = await res.json();
            if (result.exists) {
                AppUtils.showToast(`Atención: El ${fieldLabel} ya se encuentra registrado`, 'error');
                inputElement.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                inputElement.classList.remove('border-slate-200');
                setValidationIcon(inputElement, 'error');
                invalidFields.add(inputElement.id);
                return false;
            } else {
                inputElement.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                inputElement.classList.add('border-slate-200');
                setValidationIcon(inputElement, 'success');
                invalidFields.delete(inputElement.id);
            }
        } catch (e) { console.error(e); }

        // Deshabilitar botón de guardado si hay errores
        const btnSave = formStaff.querySelector('button[type="submit"]');
        if (btnSave) btnSave.disabled = invalidFields.size > 0;

        return true;
    };

    staffCedulaInput?.addEventListener('blur', () => checkUniqueness(staffCedulaInput, 'verificarCedula', 'número de identificación'));
    staffUserInput?.addEventListener('blur', () => checkUniqueness(staffUserInput, 'verificarUsername', 'nombre de usuario'));
    staffEmailInput?.addEventListener('blur', () => checkUniqueness(staffEmailInput, 'verificarEmail', 'correo electrónico'));

    document.getElementById('hasSystemAccess').addEventListener('change', (e) => {
        document.getElementById('userFields').classList.toggle('hidden', !e.target.checked);
    });

    formStaff.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();

        if (invalidFields.size > 0) {
            AppUtils.showToast('No se puede guardar: Algunos datos (Cédula, Usuario o Email) ya están en uso.', 'error');
            return;
        }

        const btnSave = formStaff.querySelector('button[type="submit"]');
        const originalText = btnSave.innerHTML;

        // Prevenir doble envío y mostrar carga
        btnSave.disabled = true;
        btnSave.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i>';
        if (window.lucide) lucide.createIcons();

        const formData = new FormData(formStaff);
        const data = Object.fromEntries(formData.entries());
        data.has_system_access = document.getElementById('hasSystemAccess').checked;

        // Normalización de datos a MAYÚSCULAS y limpieza
        data.nombre = (data.nombre || '').trim().toUpperCase();
        data.cargo = (data.cargo || '').trim().toUpperCase();
        data.direccion = (data.direccion || '').trim().toUpperCase();
        data.email = (data.email || '').trim().toLowerCase();

        // Los campos deshabilitados no se incluyen en FormData, los recuperamos manualmente si es edición
        if (document.getElementById('staffId').disabled) {
            data.id = document.getElementById('staffId').value;
        }

        try {
            const res = await fetch(`${URLROOT}/personal/guardar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                toggleModal(false);
                if (window.handler_personal) window.handler_personal.reload();
                AppUtils.showToast(result.mensaje || 'Personal guardado correctamente', 'success');
            } else {
                AppUtils.showToast(result.error || result.mensaje, 'error');
            }
        } catch (error) {
            AppUtils.showToast('Error de conexión con el servidor', 'error');
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = originalText;
            if (window.lucide) lucide.createIcons();
        }
    });

    const toggleModal = (show) => {
        modal.classList.toggle('hidden', !show);
        if (!show) {
            formStaff.reset();
            invalidFields.clear();
            // Limpiar estilos de validación visual al cerrar
            [staffCedulaInput, staffUserInput, staffEmailInput].forEach(el => {
                if (!el) return;
                el.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                el.classList.add('border-slate-200');
                setValidationIcon(el, 'none');
            });
            document.getElementById('staffId').disabled = false;
            document.getElementById('userFields').classList.add('hidden');
            document.getElementById('modalTitle').textContent = "Registrar Empleado";
        }
    };

    btnOpen.addEventListener('click', () => toggleModal(true));
    btnClose.addEventListener('click', () => toggleModal(false));
    btnCancel.addEventListener('click', () => toggleModal(false));

    window.editStaff = (id) => {
        const p = window.currentData.find(x => x.id === id);
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
            await fetch(`${URLROOT}/personal/eliminar/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
            });
            if (window.handler_personal) window.handler_personal.reload();
        });
    };
});