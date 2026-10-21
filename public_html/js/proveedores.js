/**
 * Función Global para asignar un producto seleccionado al modal de compra
 */
/**
 * FIX v3.27: Al seleccionar un producto existente, consulta al backend
 * si este proveedor ya le ha vendido ese producto antes. Si sí, usa el
 * último costo pactado con ESE proveedor. Si no, usa el ultimo_costo
 * general del inventario.
 */
window.setProductoCompra = async (id, nombre, cat, costo, precio) => {
    const modal = document.querySelector('.swal2-container');
    if (!modal) return;

    // Valores por defecto (del inventario)
    let costoFinal = parseFloat(costo) || 0;
    let precioFinal = parseFloat(precio) || 0;
    let origenCosto = 'inventario';

    // Consultar al backend el último costo de ESTE proveedor para ESTE producto
    const proveedorId = window._proveedorIdActualCompra;
    if (proveedorId) {
        try {
            const res = await fetch(`${URLROOT}/proveedores/costoProveedorProducto/${proveedorId}/${id}`);
            const data = await res.json();
            if (data.success && data.tiene_historial && data.costo !== null) {
                costoFinal = parseFloat(data.costo);
                origenCosto = 'proveedor';
            }
        } catch (e) {
            console.error('Error consultando costo del proveedor:', e);
        }
    }

    modal.querySelector('#compra-producto-id').value = id;
    modal.querySelector('#compra-producto-nombre').value = nombre;
    modal.querySelector('#compra-categoria').value = cat;
    modal.querySelector('#compra-costo').value = costoFinal.toFixed(2);
    modal.querySelector('#compra-precio-venta').value = precioFinal.toFixed(2);
    modal.querySelector('#compra-search-results').classList.add('hidden');

    // Actualizar el badge para indicar de dónde viene el costo
    const statusDiv = document.getElementById('compra-vinculo-status');
    if (statusDiv) {
        const stockTxt = '';
        if (origenCosto === 'proveedor') {
            statusDiv.innerHTML = `<span class="text-emerald-600">🔗 VINCULADO · ÚLTIMO COSTO DE ESTE PROVEEDOR: $${costoFinal.toFixed(2)}</span>`;
        } else {
            statusDiv.innerHTML = `<span class="text-blue-600">🔗 VINCULADO · COSTO DEL INVENTARIO: $${costoFinal.toFixed(2)} (sin historial con este proveedor)</span>`;
        }
    }
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

        const btnSave = formProveedor.querySelector('button[type="submit"]');
        if (btnSave) btnSave.disabled = invalidFields.size > 0;
    };

    provEmailInput?.addEventListener('blur', () => checkUniqueness(provEmailInput, 'verificarEmail', 'correo electrónico'));
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
                            <button onclick="window.cargarArticulosProveedor('${item.id}', '${item.nombre}')" class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-purple-500 text-slate-500 hover:text-white rounded-2xl transition-all shadow-sm" title="Ver Artículos">
                                <i data-lucide="package" class="w-4 h-4"></i>
                            </button>
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
        const secArticulos = document.getElementById('sec-articulos');
        const tabLista = document.getElementById('tab-lista');
        const tabDeudas = document.getElementById('tab-deudas');
        const tabArticulos = document.getElementById('tab-articulos');
        const topControls = document.getElementById('custom-top-controls');
        const bottomControls = document.getElementById('custom-bottom-controls');

        [tabLista, tabDeudas, tabArticulos].forEach(t => {
            if (t) {
                t.classList.remove('border-neon-green', 'text-navy-blue');
                t.classList.add('border-transparent', 'text-slate-400');
            }
        });

        if (tab === 'lista') {
            secLista.classList.remove('hidden');
            secDeudas.classList.add('hidden');
            secArticulos.classList.add('hidden');
            if (tabLista) tabLista.classList.add('border-neon-green', 'text-navy-blue');
            if (topControls) topControls.classList.remove('hidden');
            if (bottomControls) bottomControls.classList.remove('hidden');
            if (window.handler_proveedores) window.handler_proveedores.reload();
        } else if (tab === 'deudas') {
            secDeudas.classList.remove('hidden');
            secLista.classList.add('hidden');
            secArticulos.classList.add('hidden');
            if (tabDeudas) tabDeudas.classList.add('border-neon-green', 'text-navy-blue');
            if (topControls) topControls.classList.add('hidden');
            if (bottomControls) bottomControls.classList.add('hidden');

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

            // FIX v3.29: Cargar alerta visual de vencimientos
            cargarAlertaVencimientosProveedores();

            fetch(`${URLROOT}/proveedores/listarDeudas`)
                .then(r => r.json())
                .then(res => renderDeudas(res.data || []));
        } else if (tab === 'articulos') {
            secArticulos.classList.remove('hidden');
            secLista.classList.add('hidden');
            secDeudas.classList.add('hidden');
            if (tabArticulos) tabArticulos.classList.add('border-neon-green', 'text-navy-blue');
            if (topControls) topControls.classList.add('hidden');
            if (bottomControls) bottomControls.classList.add('hidden');
            if (window.handler_articulos) {
                window.handler_articulos.reload();
            }
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
     * FIX v3.25: Modal de ingreso de mercancía con buscador flotante mejorado.
     * 
     * Cambios clave:
     *   1. Al hacer FOCO en el buscador, muestra TODOS los productos del
     *      inventario (no requiere escribir 2+ caracteres).
     *   2. Cada resultado muestra el STOCK ACTUAL del producto para que el
     *      usuario decida si es el mismo artículo que ya tiene.
     *   3. Cuando el usuario SELECCIONA un producto existente, aparece un
     *      indicador verde "🔗 VINCULADO AL INVENTARIO (ID #X)" y el sistema
     *      ACTUALIZARÁ el stock en vez de crear un duplicado.
     *   4. Cuando el usuario ESCRIBE un nombre sin seleccionar, el indicador
     *      muestra "📝 Se creará como NUEVO artículo" para que sepa que va
     *      a duplicar el inventario.
     *   5. Se cerró el dropdown al hacer clic fuera.
     * 
     * El backend ya hace lo correcto: si `producto_id` viene con valor,
     * actualiza el producto existente (recalcula CPP y suma stock). Si viene
     * vacío, crea uno nuevo. La deuda con el proveedor se registra SIEMPRE
     * por separado en `table_compras` sin importar si el producto es nuevo
     * o existente.
     */
    window.registrarCompra = async (proveedorId, proveedorNombre) => {
        // FIX v3.27: Guardar el proveedorId globalmente para que
        // setProductoCompra pueda consultar el último costo de ese
        // proveedor para el producto seleccionado.
        window._proveedorIdActualCompra = proveedorId;

        const { value: formValues } = await Swal.fire({
            title: `<span class="text-xs uppercase text-slate-400 font-black">Ingreso de Mercancía</span><br><span class="text-navy-blue">${proveedorNombre}</span>`,
            html: `
                <div class="text-left space-y-3 pt-4 relative">
                    <input type="hidden" id="compra-producto-id">

                    <div class="relative">
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">
                            Buscar Artículo Existente o Escribir Nuevo
                        </label>
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                            <input id="compra-producto-nombre"
                                   class="w-full p-2 pl-9 bg-slate-50 border rounded-lg text-sm uppercase"
                                   placeholder="Buscar por nombre, código, marca..."
                                   autocomplete="off">
                        </div>
                        <div id="compra-search-results"
                             class="hidden absolute z-50 w-full bg-white shadow-xl rounded-lg border border-slate-100 max-h-56 overflow-y-auto mt-1">
                        </div>
                        <div id="compra-vinculo-status" class="mt-2 text-[10px] font-black uppercase tracking-wider">
                            <span class="text-slate-400">📝 Se creará como NUEVO artículo en inventario</span>
                        </div>
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
                            <input id="compra-cantidad" type="number" class="w-full p-2 bg-slate-50 border rounded-lg text-sm" value="1" min="1">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Costo Unitario</label>
                            <input id="compra-costo" type="number" class="w-full p-2 bg-slate-50 border rounded-lg text-sm" placeholder="0" step="0.01">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Precio Venta Sugerido</label>
                            <input id="compra-precio-venta" type="number" class="w-full p-2 bg-slate-50 border rounded-lg text-sm" placeholder="0" step="0.01">
                        </div>
                    </div>

                    <hr class="border-slate-100">

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Monto Abonado Hoy</label>
                            <input id="compra-pagado" type="number" class="w-full p-2 bg-emerald-50 border-emerald-100 rounded-lg text-sm font-bold" value="0" step="0.01">
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
            cancelButtonText: 'CANCELAR',
            cancelButtonColor: '#64748b',
            width: '560px',
            didOpen: () => {
                if (window.lucide) lucide.createIcons();

                const input = document.getElementById('compra-producto-nombre');
                const results = document.getElementById('compra-search-results');
                const statusDiv = document.getElementById('compra-vinculo-status');
                const hiddenId = document.getElementById('compra-producto-id');

                if (!input || !results || !statusDiv || !hiddenId) return;

                let searchTimeout = null;

                const actualizarStatus = (producto) => {
                    if (!producto) {
                        statusDiv.innerHTML = '<span class="text-slate-400">📝 Se creará como NUEVO artículo en inventario</span>';
                    } else {
                        const stockTxt = (producto.stock !== undefined && producto.stock !== null)
                            ? ` · Stock actual: ${producto.stock}`
                            : '';
                        statusDiv.innerHTML = `<span class="text-emerald-600">🔗 VINCULADO AL INVENTARIO (ID #${producto.id})${stockTxt}</span>`;
                    }
                };

                const renderResultados = (items) => {
                    if (!items || items.length === 0) {
                        results.innerHTML = '<div class="p-3 text-center text-slate-400 text-xs italic">Sin resultados. Escriba un nombre nuevo para crearlo.</div>';
                        results.classList.remove('hidden');
                        return;
                    }

                    results.innerHTML = items.map(i => {
                        const stock = parseFloat(i.stock_disponible ?? i.stock ?? 0);
                        const stockColor = stock <= 0
                            ? 'text-rose-500'
                            : (stock <= 5 ? 'text-amber-500' : 'text-emerald-600');
                        const precio = parseFloat(i.precio || 0);

                        return `<div class="p-3 hover:bg-emerald-50 cursor-pointer text-[11px] uppercase border-b border-slate-50 last:border-0 flex justify-between item-selection"
                                     data-id='${i.id}'
                                     data-nombre='${(i.nombre || '').replace(/'/g, "&apos;")}'
                                     data-cat='${i.categoria || 'REPUESTOS'}'
                                     data-costo="${i.ultimo_costo ?? i.costo_promedio ?? 0}"
                                     data-precio="${precio}"
                                     data-stock="${stock}">
                                    <div class="flex flex-col min-w-0 flex-1">
                                        <span class="uppercase font-bold truncate">${i.nombre}</span>
                                        <span class="text-[9px] text-slate-400 font-mono">
                                            ${i.categoria || ''} · Stock:
                                            <span class="${stockColor} font-black">${stock}</span>
                                        </span>
                                    </div>
                                    <span class="font-bold text-navy-blue flex-shrink-0 ml-2">${AppUtils.formatCurrency(precio)}</span>
                                </div>`;
                    }).join('');
                    results.classList.remove('hidden');
                };

                const buscarProductos = async (term) => {
                    try {
                        const url = `${URLROOT}/facturacion/buscarItems?term=${encodeURIComponent(term || '')}`;
                        const res = await fetch(url);
                        if (!res.ok) return;
                        const items = await res.json();
                        renderResultados(items);
                    } catch (e) {
                        console.error('Error buscando productos:', e);
                    }
                };

                // Al hacer foco: mostrar TODOS los productos
                input.addEventListener('focus', () => {
                    buscarProductos(input.value.trim());
                });

                // Al escribir: desvincular el producto anterior y buscar
                input.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    // Desvincular ID anterior (ahora es "nuevo")
                    hiddenId.value = '';
                    actualizarStatus(null);

                    searchTimeout = setTimeout(() => {
                        buscarProductos(input.value.trim());
                    }, 250);
                });

                // Al seleccionar un resultado: vincular al inventario
                // FIX v3.26: NO llamar AppUtils.showToast aquí (cerraría el modal)
                results.addEventListener('click', (e) => {
                    const target = e.target.closest('.item-selection');
                    if (!target) return;
                    const d = target.dataset;
                    window.setProductoCompra(d.id, d.nombre, d.cat, d.costo, d.precio);
                    actualizarStatus({ id: d.id, stock: d.stock });
                });

                // Cerrar el dropdown al hacer clic fuera
                // FIX v3.26: Guardamos la referencia para poder limpiarla
                const fueraHandler = (evt) => {
                    if (!results.contains(evt.target) && evt.target !== input) {
                        results.classList.add('hidden');
                    }
                };
                document.addEventListener('click', fueraHandler);

                // Cleanup: limpiar el listener cuando SweetAlert cierre
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    const observer = new MutationObserver(() => {
                        if (!document.body.contains(swalContainer)) {
                            document.removeEventListener('click', fueraHandler);
                            observer.disconnect();
                        }
                    });
                    observer.observe(document.body, { childList: true, subtree: false });
                }
            },
            preConfirm: () => {
                const nombre = document.getElementById('compra-producto-nombre').value.trim();
                const cantidad = parseFloat(document.getElementById('compra-cantidad').value);
                const costo = parseFloat(document.getElementById('compra-costo').value);

                if (!nombre) {
                    Swal.showValidationMessage('Debe indicar el nombre del artículo');
                    return false;
                }
                if (isNaN(cantidad) || cantidad <= 0) {
                    Swal.showValidationMessage('La cantidad debe ser mayor a cero');
                    return false;
                }
                if (isNaN(costo) || costo < 0) {
                    Swal.showValidationMessage('El costo no puede ser negativo');
                    return false;
                }

                const productoId = document.getElementById('compra-producto-id').value;

                return {
                    proveedor_id: proveedorId,
                    producto_id: productoId || null,
                    nombre: nombre.toUpperCase(),
                    categoria: (document.getElementById('compra-categoria').value || 'REPUESTOS').trim().toUpperCase(),
                    cantidad: cantidad,
                    costo: costo,
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

    const articulosConfig = {
        tableId: 'articulos',
        tableBodyId: 'tableArticulosBody',
        endpoint: '',
        searchInputId: 'searchArticulos',
        limitSelectorId: 'limitSelectorArticulos',
        paginationId: 'paginationControlsArticulos',
        totalId: 'totalArticulosCount',
        startId: 'startIndexArticulos',
        endId: 'endIndexArticulos',
        renderRow: (item) => {
            const estadoClass = item.estado === 'ACTIVO' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700';
            const costoProveedor = item.costo_unitario_proveedor ?? item.costo_promedio ?? 0;
            return `
                <tr class="hover:bg-slate-50 transition-colors group border-b border-slate-100 animate-in fade-in duration-300">
                    <td class="px-4 py-3 font-mono text-xs font-black text-slate-400 align-middle">${item.codigo || '---'}</td>
                    <td class="px-4 py-3 font-black text-sm text-slate-700 uppercase tracking-tight align-middle">${item.producto_nombre}</td>
                    <td class="px-4 py-3 align-middle">
                        <span class="text-xs font-bold bg-slate-100 text-slate-500 px-2 py-1 rounded-lg uppercase tracking-wider">${item.marca || '---'}</span>
                    </td>
                    <td class="px-4 py-3 align-middle">
                        <span class="text-xs font-bold bg-blue-50 text-blue-600 px-2 py-1 rounded-lg uppercase tracking-wider">${item.categoria || '---'}</span>
                    </td>
                    <td class="px-4 py-3 text-center align-middle font-mono text-sm font-bold ${item.stock_actual <= 5 ? 'text-rose-600' : 'text-slate-700'}">${item.stock_actual}</td>
                    <td class="px-4 py-3 text-right align-middle font-mono text-sm font-bold text-amber-600">${AppUtils.formatCurrency(costoProveedor)}</td>
                    <td class="px-4 py-3 text-right align-middle font-mono text-sm font-bold text-navy-blue">${AppUtils.formatCurrency(item.precio_venta)}</td>
                    <td class="px-4 py-3 text-right align-middle font-mono text-sm font-bold text-slate-600">${item.total_comprado}</td>
                    <td class="px-4 py-3 text-right align-middle font-mono text-sm font-bold text-slate-600">${item.num_facturas}</td>
                    <td class="px-4 py-3 text-center align-middle text-xs font-bold text-slate-500">${item.ultima_compra ? new Date(item.ultima_compra).toLocaleDateString() : '---'}</td>
                    <td class="px-4 py-3 text-center align-middle">
                        <span class="text-[10px] font-black ${estadoClass} px-2 py-1 rounded uppercase tracking-wider">${item.estado}</span>
                    </td>
                </tr>`;
        }
    };

    window.cargarArticulosProveedor = (proveedorId, proveedorNombre) => {
        if (!window.handler_articulos) {
            articulosConfig.endpoint = `${URLROOT}/proveedores/articulos/${proveedorId}`;
            window.handler_articulos = new DataTableRefactor(articulosConfig);
        } else {
            window.handler_articulos.endpoint = `${URLROOT}/proveedores/articulos/${proveedorId}`;
            window.handler_articulos.reload();
        }
        window.switchTab('articulos');
        const nombreEl = document.getElementById('articulosProveedorNombre');
        if (nombreEl) {
            nombreEl.textContent = proveedorNombre;
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

        /**
     * FIX v3.29: Carga la alerta visual de facturas próximas a vencer
     * o vencidas al tope de la sección "Cuentas por Pagar".
     */
    async function cargarAlertaVencimientosProveedores() {
        const contenedor = document.getElementById('alertas-vencimiento-proveedores');
        if (!contenedor) return;

        try {
            const res = await fetch(`${URLROOT}/proveedores/alertasVencimiento?dias=7`);
            const result = await res.json();

            if (!result.success || !result.data || result.data.total_alertas === 0) {
                contenedor.classList.add('hidden');
                contenedor.innerHTML = '';
                return;
            }

            const { vencidas, proximas } = result.data;

            let html = '';

            if (vencidas.length > 0) {
                const deudaVencida = vencidas.reduce((s, v) => s + parseFloat(v.saldo), 0);
                html += `
                    <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-xl shadow-sm mb-3">
                        <div class="flex items-start gap-4">
                            <div class="p-2 bg-rose-500 text-white rounded-lg">
                                <i data-lucide="alert-octagon" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-rose-800 font-black uppercase text-xs tracking-tight">⚠ ${vencidas.length} Factura(s) Vencida(s)</h4>
                                <p class="text-rose-700 text-[11px] font-bold mt-1">
                                    Deuda vencida total: <span class="text-rose-900">${AppUtils.formatCurrency(deudaVencida)}</span>
                                </p>
                                <div class="mt-2 space-y-1">
                                    ${vencidas.slice(0, 3).map(v => `
                                        <div class="text-[10px] flex justify-between bg-white/60 px-2 py-1 rounded">
                                            <span class="font-bold text-rose-900 uppercase">${v.proveedor_nombre} — Factura #${v.compra_id}</span>
                                            <span class="text-rose-700 font-black">Hace ${v.dias_vencido}d · ${AppUtils.formatCurrency(v.saldo)}</span>
                                        </div>`).join('')}
                                    ${vencidas.length > 3 ? `<p class="text-[10px] text-rose-600 italic mt-1">+ ${vencidas.length - 3} más...</p>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>`;
            }

            if (proximas.length > 0) {
                const deudaProxima = proximas.reduce((s, p) => s + parseFloat(p.saldo), 0);
                html += `
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-xl shadow-sm">
                        <div class="flex items-start gap-4">
                            <div class="p-2 bg-amber-500 text-white rounded-lg">
                                <i data-lucide="clock" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-amber-800 font-black uppercase text-xs tracking-tight">⏰ ${proximas.length} Factura(s) por Vencer (7 días)</h4>
                                <p class="text-amber-700 text-[11px] font-bold mt-1">
                                    Monto próximo a vencer: <span class="text-amber-900">${AppUtils.formatCurrency(deudaProxima)}</span>
                                </p>
                                <div class="mt-2 space-y-1">
                                    ${proximas.slice(0, 3).map(p => `
                                        <div class="text-[10px] flex justify-between bg-white/60 px-2 py-1 rounded">
                                            <span class="font-bold text-amber-900 uppercase">${p.proveedor_nombre} — Factura #${p.compra_id}</span>
                                            <span class="text-amber-700 font-black">En ${p.dias_restantes}d · ${AppUtils.formatCurrency(p.saldo)}</span>
                                        </div>`).join('')}
                                    ${proximas.length > 3 ? `<p class="text-[10px] text-amber-600 italic mt-1">+ ${proximas.length - 3} más...</p>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>`;
            }

            contenedor.innerHTML = html;
            contenedor.classList.remove('hidden');

            if (window.lucide) lucide.createIcons();
        } catch (e) {
            console.error('Error cargando alertas de vencimiento:', e);
            contenedor.classList.add('hidden');
        }
    }
});