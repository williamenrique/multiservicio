/**
 * Función Global para asignar un producto seleccionado al modal de compra
 */
window.setProductoCompra = (id, nombre, cat, costo, precio) => {
    const modal = document.querySelector('.swal2-container');
    if (!modal) return;

    modal.querySelector('#compra-producto-id').value = id;
    modal.querySelector('#compra-producto-nombre').value = nombre;
    modal.querySelector('#compra-categoria').value = cat;
    modal.querySelector('#compra-costo').value = costo;
    modal.querySelector('#compra-precio-venta').value = precio;
    modal.querySelector('#compra-search-results').classList.add('hidden');
    AppUtils.showToast('Artículo vinculado del inventario');
};

document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const tableDeudasBody = document.getElementById('tableDeudasBody');
    const formProveedor = document.getElementById('formProveedor');
    const proveedorModal = document.getElementById('proveedorModal');
    const searchInput = document.getElementById('searchProveedor');
    const totalCount = document.getElementById('totalCount');

    const invalidFields = new Set();

    // Validaciones dinámicas de unicidad (Email)
    const provEmailInput = document.getElementById('provEmail');
    const provIdInput = document.getElementById('provId');

    const setValidationIcon = (inputElement, status) => {
        let iconContainer = inputElement.parentElement.querySelector('.validation-icon-container');
        if (!iconContainer) {
            iconContainer = document.createElement('div');
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
        // Usamos el ID original (id_existente) para excluirlo de la validación al editar
        const currentId = document.getElementById('provIdExistente').value;
        try {
            const res = await fetch(`${URLROOT}/proveedores/${endpoint}?value=${encodeURIComponent(value)}&id=${currentId}`);
            const result = await res.json();
            if (result.exists) {
                AppUtils.showToast(`Atención: El ${fieldLabel} ya se encuentra registrado`, 'error');
                inputElement.classList.add('border-red-500', 'ring-2', 'ring-red-500/20');
                setValidationIcon(inputElement, 'error');
                invalidFields.add(inputElement.id);
            } else {
                inputElement.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                setValidationIcon(inputElement, 'success');
                invalidFields.delete(inputElement.id);
            }
        } catch (e) { console.error(e); }

        // Bloqueo visual del botón
        const btnSave = formProveedor.querySelector('button[type="submit"]');
        if (btnSave) btnSave.disabled = invalidFields.size > 0;
    };

    provEmailInput?.addEventListener('blur', () => checkUniqueness(provEmailInput, 'verificarEmail', 'correo electrónico'));
    // Agregamos la validación dinámica para el ID/NIT
    provIdInput?.addEventListener('blur', () => checkUniqueness(provIdInput, 'verificarId', 'identificación (NIT/Cédula)'));

    window.handler_proveedores = new DataTableRefactor({
        tableId: 'proveedores',
        tableBodyId: 'tableBody',
        endpoint: `${URLROOT}/proveedores/listar`,
        searchInputId: 'searchProveedor',
        limitSelectorId: 'limitSelector',
        paginationId: 'paginationControls',
        totalId: 'totalCount',
        renderRow: (item) => {
            return `
                <tr class="hover:bg-slate-50 transition-colors group border-b border-slate-100 animate-in fade-in duration-300">
                    <td class="px-8 py-5 font-mono text-sm font-black text-slate-400 align-middle">${item.id}</td>
                    <td class="px-8 py-5 font-black text-base text-slate-700 uppercase tracking-tight align-middle">${item.nombre}</td>
                    <td class="px-8 py-5 align-middle">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-600">${item.telefono || '---'}</span>
                            <span class="text-[10px] text-slate-400 lowercase">${item.email || '---'}</span>
                        </div>
                    </td>
                    <td class="px-8 py-5 text-right align-middle">
                        <div class="flex justify-end gap-2">
                            <button onclick="window.registrarCompra('${item.id}', '${item.nombre}')" class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-blue-500 text-slate-500 hover:text-white rounded-2xl transition-all shadow-sm" title="Ingresar Mercancía">
                                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                            </button>
                            <button onclick="window.imprimirReporteProveedorIndividual('${item.id}')" class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-navy-blue text-slate-500 hover:text-neon-green rounded-2xl transition-all shadow-sm" title="Imprimir Estado de Cuenta">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                            </button>
                            <button onclick="editItem('${item.id}')" class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-neon-green text-slate-500 hover:text-black rounded-2xl transition-all shadow-sm">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteItem('${item.id}')" class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-red-500 text-slate-500 hover:text-white rounded-2xl transition-all shadow-sm">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        }
    });

    // --- Lógica del Modal de Proveedores ---

    const toggleModal = (show) => {
        if (!proveedorModal) return;
        proveedorModal.classList.toggle('hidden', !show);
        if (!show) {
            formProveedor.reset();
            invalidFields.clear();
            document.getElementById('provId').readOnly = false;
            document.getElementById('provIdExistente').value = "";
            document.getElementById('modalTitle').textContent = "Registrar Proveedor";
            // Limpiar estilos de validación
            if (provEmailInput) {
                provEmailInput.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
                setValidationIcon(provEmailInput, 'none');
            }
        }
    };

    document.getElementById('btnOpenModal')?.addEventListener('click', () => toggleModal(true));
    document.getElementById('btnCloseModal')?.addEventListener('click', () => toggleModal(false));
    document.getElementById('btnCancel')?.addEventListener('click', () => toggleModal(false));

    window.editItem = async (id) => {
        try {
            const res = await fetch(`${URLROOT}/proveedores/obtener/${id}`);
            const item = await res.json();

            document.getElementById('provId').value = item.id;
            document.getElementById('provId').readOnly = true;
            document.getElementById('provIdExistente').value = item.id;
            document.getElementById('provNombre').value = item.nombre;
            document.getElementById('provEmail').value = item.email;
            document.getElementById('provTelefono').value = item.telefono;
            document.getElementById('provDireccion').value = item.direccion;

            document.getElementById('modalTitle').textContent = "Editar Proveedor";
            toggleModal(true);
        } catch (error) {
            AppUtils.showToast('Error al obtener datos del proveedor', 'error');
        }
    };

    formProveedor?.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();

        if (invalidFields.size > 0) {
            AppUtils.showToast('El correo electrónico ya está registrado por otro proveedor.', 'error');
            return;
        }

        const btnSave = formProveedor.querySelector('button[type="submit"]');
        const originalText = btnSave.innerHTML;

        // Evitar doble envío y aplicar mayúsculas
        btnSave.disabled = true;
        btnSave.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i>';
        if (window.lucide) lucide.createIcons();

        const formData = {
            id: document.getElementById('provId').value.trim().toUpperCase(),
            id_existente: document.getElementById('provIdExistente').value,
            nombre: document.getElementById('provNombre').value.trim().toUpperCase(),
            email: document.getElementById('provEmail').value.trim().toLowerCase(),
            telefono: document.getElementById('provTelefono').value.trim(),
            direccion: document.getElementById('provDireccion').value.trim().toUpperCase()
        };

        try {
            const res = await fetch(`${URLROOT}/proveedores/guardar`, {
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
                if (window.handler_proveedores) window.handler_proveedores.reload();
                AppUtils.showToast('Proveedor guardado correctamente');
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

    window.switchProveedorTab = window.switchTab = (tab) => {
        const secLista = document.getElementById('sec-lista');
        const secDeudas = document.getElementById('sec-deudas');
        const tabLista = document.getElementById('tab-lista');
        const tabDeudas = document.getElementById('tab-deudas');
        const topControls = document.getElementById('custom-top-controls');
        const bottomControls = document.getElementById('custom-bottom-controls');

        // Resetear estilos de pestañas (quitar resaltado verde)
        [tabLista, tabDeudas].forEach(t => {
            if (t) {
                t.classList.remove('border-neon-green', 'text-navy-blue');
                t.classList.add('border-transparent', 'text-slate-400');
            }
        });

        if (tab === 'lista') {
            secLista.classList.remove('hidden');
            secDeudas.classList.add('hidden');
            if (tabLista) tabLista.classList.add('border-neon-green', 'text-navy-blue');
            if (topControls) topControls.classList.remove('hidden');
            if (bottomControls) bottomControls.classList.remove('hidden');
            if (window.handler_proveedores) window.handler_proveedores.reload();
        } else {
            secDeudas.classList.remove('hidden');
            secLista.classList.add('hidden');
            if (tabDeudas) tabDeudas.classList.add('border-neon-green', 'text-navy-blue');
            if (topControls) topControls.classList.add('hidden');
            if (bottomControls) bottomControls.classList.add('hidden');
            // Carga simple para deudas (no requiere paginación compleja)

            // Inyectar botón de reporte global de deudas si no existe
            if (!document.getElementById('btn-print-global-debts')) {
                const container = document.createElement('div');
                container.className = "flex justify-end mb-4 px-8";
                container.innerHTML = `
                    <button id="btn-print-global-debts" onclick="window.exportarCarteraProveedoresPdf()" 
                            class="flex items-center gap-2 bg-navy-blue text-neon-green px-4 py-2 rounded-xl text-xs font-black uppercase shadow-lg hover:scale-105 transition-all">
                        <i data-lucide="printer" class="w-4 h-4"></i> Reporte Global de Cuentas por Pagar
                    </button>
                `;
                secDeudas.prepend(container);
                if (window.lucide) lucide.createIcons();
            }

            fetch(`${URLROOT}/proveedores/listarDeudas`)
                .then(r => r.json())
                .then(res => renderDeudas(res.data || []));
        }
    };

    const renderDeudas = (deudas) => {
        tableDeudasBody.innerHTML = '';

        if (!deudas || deudas.length === 0) {
            tableDeudasBody.innerHTML = `
                <tr><td colspan="5" class="text-center py-20 text-slate-400 italic font-bold uppercase tracking-widest">No hay deudas pendientes con proveedores</td></tr>`;
            return;
        }

        deudas.forEach(d => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 border-b border-slate-100';
            row.innerHTML = `
                <td class="px-8 py-5 font-black text-base text-slate-700 align-middle uppercase">${d.nombre}</td>
                <td class="px-8 py-5 text-center font-mono text-base font-bold">${d.facturas_pendientes}</td>
                <td class="px-8 py-5 font-black text-lg text-rose-600">${AppUtils.formatCurrency(d.saldo_pendiente)}</td>
                <td class="px-8 py-5 align-middle">
                    <span class="text-[10px] font-black bg-rose-50 text-rose-600 px-2 py-1 rounded italic">
                        ${d.proximo_vencimiento ? new Date(d.proximo_vencimiento).toLocaleDateString() : 'SIN FECHA'}
                    </span>
                </td>
                <td class="px-8 py-5 text-right align-middle">
                    <div class="flex justify-end gap-2">
                        <button onclick="window.imprimirReporteProveedorIndividual('${d.id}')" class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-navy-blue text-slate-500 hover:text-neon-green rounded-2xl transition-all shadow-sm" title="Imprimir Estado de Cuenta">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                        </button>
                        <button onclick="openPaymentModal('${d.id}')" class="bg-navy-blue text-neon-green px-4 py-2 rounded-xl text-[10px] font-black uppercase shadow-md">Gestionar</button>
                    </div>
                </td>
            `;
            tableDeudasBody.appendChild(row);
        });
        if (window.lucide) lucide.createIcons();
    };

    /**
     * Funciones de impresión (Disponibles localmente para el módulo)
     */
    window.exportarCarteraProveedoresPdf = function () {
        AppUtils.showToast("Generando reporte global de proveedores...", "info");
        window.open(`${URLROOT}/reportes/imprimirCarteraProveedores`, '_blank');
    };

    window.imprimirReporteProveedorIndividual = function (id) {
        if (!id) return;
        AppUtils.showToast("Generando estado de cuenta...", "info");
        window.open(`${URLROOT}/reportes/imprimirReporteProveedor/${id}`, '_blank');
    };

    /**
     * Abre el modal para registrar ingreso de mercancía (Compra)
     */
    window.registrarCompra = async (proveedorId, proveedorNombre) => {
        const { value: formValues } = await Swal.fire({
            title: `<span class="text-xs uppercase text-slate-400 font-black">Ingreso de Mercancía</span><br><span class="text-navy-blue">${proveedorNombre}</span>`,
            html: `
                <div class="text-left space-y-3 pt-4 relative">
                    <input type="hidden" id="compra-producto-id">
                    <div class="relative">
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Buscar o Escribir Producto</label>
                        <input id="compra-producto-nombre" class="w-full p-2 bg-slate-50 border rounded-lg text-sm uppercase" placeholder="Ej: ACEITE 20W50">
                        <div id="compra-search-results" class="hidden absolute z-50 w-full bg-white shadow-xl rounded-lg border border-slate-100 max-h-40 overflow-y-auto mt-1"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Categoría</label>
                            <select id="compra-categoria" class="w-full p-2 bg-slate-50 border rounded-lg text-sm uppercase font-bold text-navy-blue">
                                <option value="MECANICA">MECÁNICA</option>
                                <option value="REPUESTOS">REPUESTOS</option>
                                <option value="LUBRICANTES">LUBRICANTES</option>
                                <option value="ELECTRICIDAD">ELECTRICIDAD</option>
                                <option value="OTROS">OTROS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Cantidad</label>
                            <input id="compra-cantidad" type="number" class="w-full p-2 bg-slate-50 border rounded-lg text-sm" value="1">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Costo Unitario</label>
                            <input id="compra-costo" type="number" class="w-full p-2 bg-slate-50 border rounded-lg text-sm" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Precio Venta Sugerido</label>
                            <input id="compra-precio-venta" type="number" class="w-full p-2 bg-slate-50 border rounded-lg text-sm" placeholder="0">
                        </div>
                    </div>
                    <hr class="border-slate-100">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Monto Abonado Hoy</label>
                            <input id="compra-pagado" type="number" class="w-full p-2 bg-emerald-50 border-emerald-100 rounded-lg text-sm font-bold" value="0">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Fecha de Cobro (Límite)</label>
                            <input id="compra-fecha-cobro" type="date" class="w-full p-2 bg-slate-50 border rounded-lg text-sm">
                        </div>
                    </div>
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'REGISTRAR INGRESO',
            confirmButtonColor: '#10b981',
            didOpen: () => {
                const input = document.getElementById('compra-producto-nombre');
                const results = document.getElementById('compra-search-results');
                input.addEventListener('input', async () => {
                    const term = input.value.trim();
                    if (term.length < 2) { results.classList.add('hidden'); return; }
                    const res = await fetch(`${URLROOT}/facturacion/buscarItems?term=${term}`);
                    const items = await res.json();
                    if (items.length > 0) {
                        results.innerHTML = items.map(i => `
                            <div class="p-3 hover:bg-slate-50 cursor-pointer text-[11px] uppercase border-b border-slate-50 last:border-0 flex justify-between item-selection" 
                                 data-id='${i.id}' 
                                 data-nombre='${i.nombre.replace(/'/g, "&apos;")}' 
                                 data-cat='${i.categoria}' 
                                 data-costo="${i.ultimo_costo}" 
                                 data-precio="${i.precio}">
                                <span class="uppercase">${i.nombre}</span>
                                <span class="font-bold text-navy-blue">${AppUtils.formatCurrency(i.precio)}</span>
                            </div>`).join('');
                        results.classList.remove('hidden');
                    }
                });

                // Delegación de eventos para la selección
                results.addEventListener('click', (e) => {
                    const target = e.target.closest('.item-selection');
                    if (target) {
                        const d = target.dataset;
                        window.setProductoCompra(d.id, d.nombre, d.cat, d.costo, d.precio);
                    }
                });
            },
            preConfirm: () => {
                const nombre = document.getElementById('compra-producto-nombre').value;
                const cantidad = parseFloat(document.getElementById('compra-cantidad').value);
                const costo = parseFloat(document.getElementById('compra-costo').value);
                if (!nombre || isNaN(cantidad) || isNaN(costo) || cantidad <= 0 || costo < 0) {
                    Swal.showValidationMessage('Complete nombre, cantidad y costo correctamente');
                    return false;
                }
                return {
                    proveedor_id: proveedorId,
                    producto_id: document.getElementById('compra-producto-id').value,
                    nombre: nombre.toUpperCase(),
                    categoria: (document.getElementById('compra-categoria').value || 'REPUESTOS').trim().toUpperCase(),
                    cantidad,
                    costo,
                    precio_venta: parseFloat(document.getElementById('compra-precio-venta').value) || 0,
                    pagado: parseFloat(document.getElementById('compra-pagado').value) || 0,
                    fecha_cobro: document.getElementById('compra-fecha-cobro').value
                };
            }
        });

        if (formValues) {
            try {
                AppUtils.showLoading('Registrando ingreso...');
                const res = await fetch(`${URLROOT}/proveedores/registrarCompra`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify(formValues)
                });
                const result = await res.json();
                if (result.success) {
                    AppUtils.showToast(result.mensaje || 'Mercancía ingresada');
                    if (window.handler_proveedores) window.handler_proveedores.reload();
                } else {
                    AppUtils.showAlert('Error', result.mensaje || 'No se pudo registrar', 'error');
                }
            } catch (error) {
                AppUtils.showToast('Error de conexión', 'error');
            } finally {
                AppUtils.hideLoading();
            }
        }
    };

    /**
     * Abre el modal para registrar un abono a una factura específica de un proveedor
     */
    window.openPaymentModal = async (proveedorId) => {
        try {
            AppUtils.showLoading('Cargando facturas pendientes...');
            const res = await fetch(`${URLROOT}/proveedores/listarComprasPendientes/${proveedorId}`);
            const result = await res.json();
            const compras = result.data || [];
            AppUtils.hideLoading();

            if (compras.length === 0) {
                return AppUtils.showToast('No hay facturas pendientes', 'info');
            }

            const { value: paymentValues } = await Swal.fire({
                title: 'Registrar Abono a Proveedor',
                html: `
                    <div class="text-left space-y-4 pt-4">
                        <label class="block text-[10px] font-black text-slate-400 uppercase">Seleccione Factura</label>
                        <select id="pago-compra-id" class="w-full p-3 bg-slate-50 border rounded-xl text-sm font-bold">
                            ${compras.map(c => `<option value="${c.id}">FACTURA #${c.id} - SALDO: ${AppUtils.formatCurrency(c.total - c.pagado)}</option>`).join('')}
                        </select>
                        <label class="block text-[10px] font-black text-slate-400 uppercase">Monto a abonar ($)</label>
                        <input id="pago-monto" type="number" step="0.01" class="w-full p-3 bg-slate-50 border rounded-xl font-black text-navy-blue" placeholder="0.00">
                    </div>`,
                showCancelButton: true,
                confirmButtonText: 'CONFIRMAR ABONO',
                confirmButtonColor: '#10b981',
                preConfirm: () => {
                    const monto = parseFloat(document.getElementById('pago-monto').value);
                    if (isNaN(monto) || monto <= 0) {
                        Swal.showValidationMessage('Ingrese un monto válido');
                        return false;
                    }
                    return {
                        compra_id: document.getElementById('pago-compra-id').value,
                        monto: monto
                    };
                }
            });

            if (paymentValues) {
                AppUtils.showLoading('Procesando pago...');
                const resPago = await fetch(`${URLROOT}/proveedores/registrarPago`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify(paymentValues)
                });
                const result = await resPago.json();
                AppUtils.hideLoading();

                if (result.success) {
                    AppUtils.showToast('Pago registrado correctamente');
                    window.switchTab('deudas');
                } else {
                    AppUtils.showAlert('Error', result.mensaje, 'error');
                }
            }
        } catch (e) {
            AppUtils.hideLoading();
            AppUtils.showToast('Error al procesar el pago', 'error');
        }
    };

    window.deleteItem = (id) => {
        AppUtils.confirmAction('¿Eliminar proveedor?', 'Esta acción no se puede deshacer.', async () => {
            const res = await fetch(`${URLROOT}/proveedores/eliminar/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
            });
            if ((await res.json()).success) {
                AppUtils.showToast('Proveedor eliminado');
                if (window.handler_proveedores) window.handler_proveedores.reload();
            }
        });
    };
});