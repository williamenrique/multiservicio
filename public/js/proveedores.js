document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const tableDeudasBody = document.getElementById('tableDeudasBody');
    const formProveedor = document.getElementById('formProveedor');
    const proveedorModal = document.getElementById('proveedorModal');
    const searchInput = document.getElementById('searchProveedor');
    const totalCount = document.getElementById('totalCount');

    let currentData = [];
    let state = {
        page: 1,
        limit: 10,
        search: '',
        total: 0,
        filtered: 0
    };

    const loadProveedores = async () => {
        try {
            const start = (state.page - 1) * state.limit;
            const params = new URLSearchParams({
                draw: 1,
                start: start,
                length: state.limit,
                'search[value]': state.search
            });

            const res = await fetch(`${URLROOT}/proveedores/listar?${params.toString()}`);

            if (!res.ok) throw new Error(`Error HTTP: ${res.status}`);

            const contentType = res.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const text = await res.text();
                console.error("El servidor devolvió HTML en lugar de JSON. Contenido:", text);
                throw new Error("Respuesta del servidor no válida (No es JSON)");
            }

            const result = await res.json();

            currentData = result.data;
            state.total = result.recordsTotal;
            state.filtered = result.recordsFiltered;

            if (totalCount) totalCount.textContent = state.total;

            renderTable();
            renderControls();
        } catch (error) {
            console.error("Error cargando proveedores:", error);
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-10 text-red-500 font-bold uppercase">Error de conexión</td></tr>';
        }
    };

    const renderTable = () => {
        tableBody.innerHTML = '';
        if (currentData.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-20 text-slate-400 italic font-bold uppercase tracking-widest">No hay proveedores registrados</td></tr>';
            return;
        }

        currentData.forEach(item => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 transition-colors group border-b border-slate-100 animate-in fade-in duration-300';
            row.innerHTML = `
                <td class="px-8 py-5 font-mono text-xs font-bold text-slate-400 align-middle">${item.id}</td>
                <td class="px-8 py-5 font-bold text-slate-700 uppercase tracking-tight align-middle">${item.nombre}</td>
                <td class="px-8 py-5 align-middle">
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-slate-600">${item.telefono || '---'}</span>
                        <span class="text-[10px] text-slate-400 lowercase">${item.email || '---'}</span>
                    </div>
                </td>
                <td class="px-8 py-5 text-right align-middle">
                    <div class="flex justify-end gap-2">
                        <button onclick="window.registrarCompra('${item.id}', '${item.nombre}')" class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-blue-500 text-slate-500 hover:text-white rounded-2xl transition-all shadow-sm" title="Ingresar Mercancía">
                            <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                        </button>
                        <button onclick="editItem('${item.id}')" class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-neon-green text-slate-500 hover:text-black rounded-2xl transition-all shadow-sm">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <button onclick="deleteItem('${item.id}')" class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-red-500 text-slate-500 hover:text-white rounded-2xl transition-all shadow-sm">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
        });
        if (window.lucide) lucide.createIcons();
    };

    const renderControls = () => {
        const wrapper = document.getElementById('proveedorTable').closest('div');
        let top = document.getElementById('custom-top-controls');
        if (!top) {
            top = document.createElement('div');
            top.id = 'custom-top-controls';
            top.className = 'flex flex-col sm:flex-row justify-between items-center gap-4 mb-6 px-8 py-4 bg-white/50 rounded-3xl border border-slate-100 shadow-sm mx-2 animate-in fade-in slide-in-from-top-2 duration-500';
            wrapper.parentNode.insertBefore(top, wrapper);
        }

        let bottom = document.getElementById('custom-bottom-controls');
        if (!bottom) {
            bottom = document.createElement('div');
            bottom.id = 'custom-bottom-controls';
            bottom.className = 'flex flex-col sm:flex-row justify-between items-center gap-6 mt-6 px-8 py-5 bg-white/50 rounded-3xl border border-slate-100 shadow-sm mx-2 animate-in fade-in slide-in-from-bottom-2 duration-500';
            wrapper.parentNode.insertBefore(bottom, wrapper.nextSibling);
        }

        top.innerHTML = `
            <div class="flex items-center gap-2 bg-slate-100/80 p-1.5 rounded-2xl border border-slate-200/60 shadow-inner select-none transition-all">
                <span class="pl-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Mostrar</span>
                <select onchange="window.updateLimit(this.value)" 
                    class="bg-white border-none rounded-xl px-3 py-1.5 text-xs font-black text-navy-blue outline-none focus:ring-2 focus:ring-neon-green/50 cursor-pointer shadow-sm h-9 min-w-[80px] text-center transition-transform active:scale-95">
                    ${[5, 10, 25, 50].map(v => `<option value="${v}" ${state.limit == v ? 'selected' : ''}>${v}</option>`).join('')}
                </select>
                <span class="pr-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Proveedores</span>
            </div>
        `;

        const start = (state.page - 1) * state.limit + 1;
        const end = Math.min(state.page * state.limit, state.filtered);
        const totalPages = Math.ceil(state.filtered / state.limit);

        // Lógica para mostrar un rango de páginas (ej: 1 ... 4 5 6 ... 10)
        let pageNumbers = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pageNumbers.push(i);
        } else {
            pageNumbers.push(1);
            if (state.page > 4) pageNumbers.push('...');
            for (let i = Math.max(2, state.page - 2); i <= Math.min(totalPages - 1, state.page + 2); i++) pageNumbers.push(i);
            if (state.page < totalPages - 3) pageNumbers.push('...');
            if (totalPages > 1) pageNumbers.push(totalPages);
            pageNumbers = [...new Set(pageNumbers)].sort((a, b) => (a === '...' ? (b === '...' ? 0 : -1) : (b === '...' ? 1 : a - b)));
        }

        bottom.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-2.5 h-2.5 rounded-full bg-neon-green animate-pulse shadow-[0_0_8px_rgba(57,255,20,0.5)]"></div>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">
                    Mostrando <span class="text-navy-blue text-xs ml-1">${start}-${end}</span> <span class="text-slate-300 mx-2 text-lg font-thin">|</span> Total <span class="text-navy-blue text-xs ml-1">${state.filtered}</span>
                </span>
            </div>
            <div class="flex items-center gap-1.5 flex-wrap justify-center">
                <button onclick="window.changePage(${state.page - 1})" ${state.page === 1 ? 'disabled' : ''} 
                    class="flex items-center justify-center w-10 h-10 rounded-2xl transition-all ${state.page === 1 ? 'text-slate-300 bg-slate-50 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-500 hover:bg-navy-blue hover:text-neon-green hover:border-navy-blue shadow-sm cursor-pointer'}">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </button>
                
                ${pageNumbers.map(p => {
            if (p === '...') return `<span class="px-3 text-slate-300 font-black">...</span>`;
            return `
                        <button onclick="window.changePage(${p})" 
                            class="w-10 h-10 rounded-2xl text-[11px] font-black transition-all ${p === state.page ? 'bg-navy-blue text-neon-green shadow-lg shadow-navy-blue/20 border border-navy-blue' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-navy-blue shadow-sm cursor-pointer'}">
                            ${p}
                        </button>`;
        }).join('')}

                <button onclick="window.changePage(${state.page + 1})" ${state.page === totalPages ? 'disabled' : ''} 
                    class="flex items-center justify-center w-10 h-10 rounded-2xl transition-all ${state.page === totalPages ? 'text-slate-300 bg-slate-50 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-500 hover:bg-navy-blue hover:text-neon-green hover:border-navy-blue shadow-sm cursor-pointer'}">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </button>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
    };

    window.changePage = (p) => { if (p > 0) { state.page = p; loadProveedores(); } };
    window.updateLimit = (l) => { state.limit = parseInt(l); state.page = 1; loadProveedores(); };

    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.search = e.target.value;
            state.page = 1;
            loadProveedores();
        }, 500);
    });

    // --- Lógica del Modal de Proveedores ---

    const toggleModal = (show) => {
        if (!proveedorModal) return;
        proveedorModal.classList.toggle('hidden', !show);
        if (!show) {
            formProveedor.reset();
            document.getElementById('provId').readOnly = false;
            document.getElementById('provIdExistente').value = "";
            document.getElementById('modalTitle').textContent = "Registrar Proveedor";
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
                loadProveedores();
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
            loadProveedores();
        } else {
            secDeudas.classList.remove('hidden');
            secLista.classList.add('hidden');
            if (tabDeudas) tabDeudas.classList.add('border-neon-green', 'text-navy-blue');
            if (topControls) topControls.classList.add('hidden');
            if (bottomControls) bottomControls.classList.add('hidden');
            // Carga simple para deudas (no requiere paginación compleja)
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
                <td class="px-8 py-5 font-bold text-slate-700 align-middle uppercase">${d.nombre}</td>
                <td class="px-8 py-5 text-center font-mono">${d.facturas_pendientes}</td>
                <td class="px-8 py-5 font-black text-rose-500">${AppUtils.formatCurrency(d.saldo_pendiente)}</td>
                <td class="px-8 py-5 align-middle">
                    <span class="text-[10px] font-black bg-rose-50 text-rose-600 px-2 py-1 rounded italic">
                        ${d.proximo_vencimiento ? new Date(d.proximo_vencimiento).toLocaleDateString() : 'SIN FECHA'}
                    </span>
                </td>
                <td class="px-8 py-5 text-right align-middle">
                    <button onclick="openPaymentModal('${d.id}')" class="bg-navy-blue text-neon-green px-4 py-2 rounded-xl text-[10px] font-black uppercase shadow-md">Gestionar</button>
                </td>
            `;
            tableDeudasBody.appendChild(row);
        });
        if (window.lucide) lucide.createIcons();
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
                            <input id="compra-categoria" class="w-full p-2 bg-slate-50 border rounded-lg text-sm uppercase" value="REPUESTOS">
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
                            <div class="p-3 hover:bg-slate-50 cursor-pointer text-[11px] uppercase border-b border-slate-50 last:border-0 flex justify-between" 
                                 onclick="window.setProductoCompra('${i.id}', '${i.nombre.replace(/'/g, "\\'")}', '${i.categoria}', ${i.ultimo_costo}, ${i.precio})">
                                <span>${i.nombre}</span>
                                <span class="font-bold text-navy-blue">${AppUtils.formatCurrency(i.precio)}</span>
                            </div>`).join('').toUpperCase();
                        results.classList.remove('hidden');
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
                AppUtils.showLoading('Procesando ingreso...');
                const res = await fetch(`${URLROOT}/proveedores/registrarCompra`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify(formValues)
                });
                const result = await res.json();
                if (result.success) {
                    AppUtils.showToast(result.mensaje);
                    loadProveedores();
                } else {
                    AppUtils.showAlert('Error', result.mensaje, 'error');
                }
            } catch (error) {
                AppUtils.showAlert('Error', 'No se pudo conectar con el servidor o hubo un problema.', 'error');
            } finally {
                AppUtils.hideLoading(); // Asegurar que el cargador se oculte en todos los casos
            }
        }
    };

    window.setProductoCompra = (id, nombre, cat, costo, precio) => {
        document.getElementById('compra-producto-id').value = id;
        document.getElementById('compra-producto-nombre').value = nombre;
        document.getElementById('compra-categoria').value = cat;
        document.getElementById('compra-costo').value = costo;
        document.getElementById('compra-precio-venta').value = precio;
        document.getElementById('compra-search-results').classList.add('hidden');
    };

    window.openPaymentModal = async (proveedorId) => {
        const res = await fetch(`${URLROOT}/proveedores/listarComprasPendientes/${proveedorId}`);
        const compras = await res.json();

        const { value: formValues } = await Swal.fire({
            title: 'Registrar Abono a Proveedor',
            html: `
                <div class="text-left space-y-4 pt-4">
                    <select id="pago-compra-id" class="w-full p-2 border rounded-lg text-sm">
                        ${compras.data.map(c => `<option value="${c.id}">Factura #${c.id} - Saldo: ${AppUtils.formatCurrency(c.total - c.pagado)}</option>`).join('')}
                    </select>
                    <input id="pago-monto" type="number" class="w-full p-2 border rounded-lg text-sm" placeholder="Monto a abonar">
                </div>`,
            preConfirm: () => ({
                compra_id: document.getElementById('pago-compra-id').value,
                monto: document.getElementById('pago-monto').value
            })
        });

        if (formValues && formValues.monto > 0) {
            const resPago = await fetch(`${URLROOT}/proveedores/registrarPago`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(formValues)
            });
            const result = await resPago.json();
            if (result.success) {
                AppUtils.showToast(result.mensaje);
                window.switchTab('deudas');
            }
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
                loadProveedores();
            }
        });
    };

    // Manejo de pestaña inicial por URL (Ej: proveedores?tab=deudas)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'deudas') {
        window.switchTab('deudas');
    } else {
        loadProveedores();
    }
});