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
    let deudas = [];

    const loadData = async () => {
        try {
            const res = await fetch(`${URLROOT}/proveedores/listar`);
            proveedores = await res.json();
            renderTable(proveedores);
        } catch (e) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-10 text-red-500">Error de conexión</td></tr>';
        }
    };

    const loadDeudas = async () => {
        try {
            const res = await fetch(`${URLROOT}/proveedores/listarDeudas`);
            deudas = await res.json();
            renderDeudasTable(deudas);
        } catch (e) { console.error("Error al cargar deudas", e); }
    };

    const renderTable = (data) => {
        if ($.fn.DataTable.isDataTable('#suppliersTable')) {
            $('#suppliersTable').DataTable().destroy();
        }

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
                    <button onclick="openPurchaseModal('${p.id}', '${p.nombre}')" class="p-2 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl transition-all mr-1" title="Ingresar Mercancía"><i data-lucide="shopping-bag" class="w-4 h-4"></i></button>
                    <button onclick="editProveedor('${p.id}')" class="p-2 bg-slate-100 hover:bg-neon-green rounded-xl transition-all mr-1"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                    <button onclick="deleteProveedor('${p.id}')" class="p-2 bg-slate-100 hover:bg-red-500 hover:text-white rounded-xl transition-all"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </td>
            `;
            tableBody.appendChild(row);
        });

        $('#suppliersTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            drawCallback: () => lucide.createIcons()
        });

        lucide.createIcons();
    };

    const renderDeudasTable = (data) => {
        const debtBody = document.getElementById('tableDeudasBody');
        if ($.fn.DataTable.isDataTable('#purchasesTable')) {
            $('#purchasesTable').DataTable().destroy();
        }

        debtBody.innerHTML = '';

        if (data.length === 0) {
            debtBody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-slate-400 italic">No hay cuentas por pagar activas</td></tr>';
            return;
        }

        data.forEach(d => {
            const proximoVenc = d.proximo_vencimiento ? new Date(d.proximo_vencimiento).toLocaleDateString() : 'N/A';
            const isOverdue = d.proximo_vencimiento && new Date(d.proximo_vencimiento) < new Date();

            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 transition-colors border-b border-slate-100';
            row.innerHTML = `
                <td class="px-8 py-5">
                    <div class="font-bold text-slate-700 uppercase">${d.nombre}</div>
                    <div class="text-[10px] text-slate-400">${d.telefono || ''}</div>
                </td>
                <td class="px-8 py-5 text-sm">${d.facturas_pendientes} facturas</td>
                <td class="px-8 py-5 font-mono font-bold text-red-600">${AppUtils.formatCurrency(d.saldo_pendiente)}</td>
                <td class="px-8 py-5">
                    <span class="px-2 py-1 rounded-md text-[10px] font-bold ${isOverdue ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-500'}">
                        ${proximoVenc} ${isOverdue ? '(VENCIDO)' : ''}
                    </span>
                </td>
                <td class="px-8 py-5 text-right">
                    <button onclick="viewProviderDebtsDetail('${d.id}', '${d.nombre}')" class="p-2 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl transition-all" title="Ver Detalles/Pagar"><i data-lucide="eye" class="w-4 h-4"></i></button>
                </td>
            `;
            debtBody.appendChild(row);
        });

        $('#purchasesTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            drawCallback: () => lucide.createIcons()
        });

        lucide.createIcons();
    };

    window.switchTab = (tab) => {
        const secLista = document.getElementById('sec-lista');
        const secDeudas = document.getElementById('sec-deudas');
        const tabLista = document.getElementById('tab-lista');
        const tabDeudas = document.getElementById('tab-deudas');

        if (tab === 'lista') {
            secLista.classList.remove('hidden');
            secDeudas.classList.add('hidden');
            tabLista.className = 'pb-3 px-1 border-b-2 border-neon-green font-bold text-navy-blue transition-all flex items-center gap-2 text-sm uppercase tracking-wider';
            tabDeudas.className = 'pb-3 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue font-bold transition-all flex items-center gap-2 text-sm uppercase tracking-wider';
            loadData();
        } else {
            secLista.classList.add('hidden');
            secDeudas.classList.remove('hidden');
            tabLista.className = 'pb-3 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue font-bold transition-all flex items-center gap-2 text-sm uppercase tracking-wider';
            tabDeudas.className = 'pb-3 px-1 border-b-2 border-neon-green font-bold text-navy-blue transition-all flex items-center gap-2 text-sm uppercase tracking-wider';
            loadDeudas();
        }
        lucide.createIcons();
    };

    /**
     * Muestra el detalle de facturas pendientes de un proveedor y permite pagar
     */
    window.viewProviderDebtsDetail = async (id, nombre) => {
        try {
            const res = await fetch(`${URLROOT}/proveedores/listarComprasPendientes/${id}`);
            const compras = await res.json();

            if (compras.length === 0) {
                AppUtils.showToast("No hay facturas pendientes", "info");
                loadDeudas();
                return;
            }

            Swal.fire({
                title: `<span class="text-sm uppercase text-slate-400">Facturas Pendientes:</span><br>${nombre}`,
                html: `
                    <div class="text-left mt-4 max-h-96 overflow-y-auto">
                        <table class="w-full text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b">
                                    <th class="p-2 text-left text-slate-400">FECHA</th>
                                    <th class="p-2 text-right text-slate-400">VALOR</th>
                                    <th class="p-2 text-right text-slate-400">ABONADO</th>
                                    <th class="p-2 text-right text-slate-400">PENDIENTE</th>
                                    <th class="p-2 text-center text-slate-400">DETALLE</th>
                                    <th class="p-2 text-right text-slate-400">OPCIÓN</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${compras.map(c => {
                    const saldo = c.total - c.pagado;
                    return `
                                    <tr class="border-b hover:bg-slate-50 transition-colors">
                                        <td class="p-2">
                                            <div class="font-bold">${new Date(c.fecha).toLocaleDateString()}</div>
                                            <div class="text-[9px] text-slate-400">ID: #${c.id}</div>
                                        </td>
                                        <td class="p-2 text-right font-mono text-slate-500 text-[10px]">${AppUtils.formatCurrency(c.total)}</td>
                                        <td class="p-2 text-right font-mono text-emerald-600 text-[10px]">${AppUtils.formatCurrency(c.pagado)}</td>
                                        <td class="p-2 text-right font-mono font-bold text-red-600">
                                            ${AppUtils.formatCurrency(saldo)}                                            
                                        </td>
                                        <td class="p-2 text-center">
                                            <button onclick="window.verItemsCompra(${c.id})" class="p-1.5 bg-slate-100 text-slate-400 hover:text-blue-600 rounded-lg transition-colors" title="Ver Artículos de esta Factura"><i data-lucide="eye" class="w-3.5 h-3.5"></i></button>
                                        </td>
                                        <td class="p-2 text-right">
                                            <button onclick="window.payInvoice('${c.id}', ${saldo}, '${nombre}')" class="bg-emerald-500 text-white px-3 py-1 rounded-lg text-[10px] font-black hover:bg-emerald-600 transition-colors shadow-lg shadow-emerald-500/10">ABONAR</button>
                                        </td>
                                    </tr>
                                    `;
                }).join('')}
                            </tbody>
                        </table>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cerrar Ventana',
                didOpen: () => lucide.createIcons()
            });

            window.payInvoice = async (compraId, saldo, provNombre) => {
                const { value: monto } = await Swal.fire({
                    title: 'Registrar Abono',
                    text: `Saldo pendiente: ${AppUtils.formatCurrency(saldo)}`,
                    input: 'number',
                    inputAttributes: { min: 1, max: saldo, step: 0.01 },
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar Pago',
                    confirmButtonColor: '#10b981',
                    inputValidator: (value) => {
                        if (!value || value <= 0) return 'Ingrese un monto válido';
                        if (parseFloat(value) > saldo) return 'El monto excede el saldo pendiente';
                    }
                });

                if (monto) {
                    const response = await fetch(`${URLROOT}/proveedores/registrarPago`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ compra_id: compraId, monto: parseFloat(monto) })
                    });
                    const data = await response.json();
                    if (data.success) {
                        AppUtils.showToast(data.mensaje);
                        window.viewProviderDebtsDetail(id, provNombre); // Recargar el modal
                        loadDeudas(); // Actualizar tabla principal
                    }
                }
            };
        } catch (e) { console.error(e); }
    };

    /**
     * Muestra el detalle de una compra específica (artículos, costos y deudas)
     */
    window.verItemsCompra = async (id) => {
        try {
            // Buscamos el detalle de la compra en el servidor
            const res = await fetch(`${URLROOT}/proveedores/obtenerDetalleCompra/${id}`);
            const data = await res.json();

            if (!data) return AppUtils.showToast('Detalle de ingreso no disponible', 'error');

            Swal.fire({
                title: `<span class="text-[10px] uppercase text-slate-400 font-black tracking-widest">Detalle de Mercancía</span><br><span class="text-navy-blue">INGRESO #${id}</span>`,
                html: `
                    <div class="text-left space-y-4 pt-4">
                        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Producto / Repuesto</span>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Cantidad Ingresada</span>
                            </div>
                            <div class="flex justify-between items-end">
                                <span class="font-black text-navy-blue uppercase text-sm leading-tight max-w-[70%]">${data.producto_nombre || 'Surtido General'}</span>
                                <span class="bg-navy-blue text-neon-green px-4 py-1.5 rounded-xl font-black text-xs shadow-lg shadow-navy-blue/20">${data.cantidad} UND</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-white border border-slate-100 rounded-2xl">
                                <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Costo Unitario</p>
                                <p class="font-bold text-slate-700">${AppUtils.formatCurrency(data.costo_unitario)}</p>
                            </div>
                            <div class="p-4 bg-white border border-slate-100 rounded-2xl">
                                <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Total de Compra</p>
                                <p class="font-black text-navy-blue text-lg">${AppUtils.formatCurrency(data.total)}</p>
                            </div>
                            <div class="p-4 bg-white border border-slate-100 rounded-2xl">
                                <p class="text-[9px] font-black text-emerald-500 uppercase mb-1">Total Abonado</p>
                                <p class="font-bold text-emerald-600">${AppUtils.formatCurrency(data.pagado)}</p>
                            </div>
                            <div class="p-4 bg-white border border-slate-100 rounded-2xl">
                                <p class="text-[9px] font-black text-red-400 uppercase mb-1">Saldo por Pagar</p>
                                <p class="font-black text-red-600">${AppUtils.formatCurrency(data.total - data.pagado)}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-center gap-2 p-3 bg-slate-50 rounded-xl text-[10px] text-slate-400 font-bold uppercase">
                            <i data-lucide="calendar" class="w-3 h-3"></i>
                            Fecha de Ingreso: ${new Date(data.fecha).toLocaleString()}
                        </div>
                    </div>
                `,
                showConfirmButton: true,
                confirmButtonText: 'Cerrar Detalle',
                confirmButtonColor: '#0b1120',
                didOpen: () => lucide.createIcons()
            });
        } catch (e) { console.error(e); AppUtils.showToast('Error al cargar la información', 'error'); }
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

        // Normalizar campos de texto a mayúsculas antes de enviar
        if (data.nombre) data.nombre = data.nombre.trim().toUpperCase();
        if (data.direccion) data.direccion = data.direccion.trim().toUpperCase();

        // Los campos deshabilitados no se incluyen en FormData, los recuperamos manualmente si es edición
        if (document.getElementById('provId').disabled) {
            data.id = document.getElementById('provId').value.trim().toUpperCase();
        } else if (data.id) {
            data.id = data.id.trim().toUpperCase();
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

    /**
     * Abre el modal para registrar entrada de mercancía de un proveedor específico
     */
    window.openPurchaseModal = async (proveedorId, proveedorNombre) => {
        let selectedProduct = null;

        Swal.fire({
            title: `<span class="text-sm uppercase text-slate-400">Ingreso:</span><br>${proveedorNombre}`,
            html: `
                <div class="text-left space-y-4 pt-4">
                    <div class="relative">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase">Buscar o Crear Producto</label>
                        <input id="pur-search" class="w-full p-2 border rounded-lg uppercase text-sm" placeholder="Escriba nombre del repuesto...">
                        <div id="pur-results" class="absolute w-full mt-1 max-h-40 overflow-y-auto hidden border bg-white z-50 shadow-xl rounded-lg"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase">Cantidad</label>
                            <input id="pur-qty" type="number" class="w-full p-2 border rounded-lg font-bold" value="1">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase">Costo Unit. ($)</label>
                            <input id="pur-cost" type="number" class="w-full p-2 border rounded-lg font-bold" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase">Ganancia %</label>
                            <input id="pur-markup" type="number" class="w-full p-2 border rounded-lg font-bold" value="${MARKUP_DEFAULT}">
                        </div>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-xl border border-blue-100 flex justify-between items-center">
                        <span class="text-[10px] font-black text-blue-600 uppercase">Precio Venta Sugerido:</span>
                        <span id="pur-suggested" class="text-sm font-black text-navy-blue">$ 0.00</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase">P. Venta Final</label>
                            <input id="pur-price" type="number" class="w-full p-2 border-2 border-neon-green rounded-lg font-black text-navy-blue" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase">Abono Inicial</label>
                            <input id="pur-paid" type="number" class="w-full p-2 border rounded-lg" value="0" step="0.01">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase">Fecha de Cobro</label>
                            <input id="pur-cutoff" type="date" class="w-full p-2 border rounded-lg">
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Procesar Ingreso',
            confirmButtonColor: '#39FF14',
            didOpen: () => {
                const search = document.getElementById('pur-search');
                const results = document.getElementById('pur-results');
                const costInput = document.getElementById('pur-cost');
                const markupInput = document.getElementById('pur-markup');
                const suggestedSpan = document.getElementById('pur-suggested');
                const priceInput = document.getElementById('pur-price');

                const updatePrice = () => {
                    const cost = parseFloat(costInput.value) || 0;
                    const markup = parseFloat(markupInput.value) || 0;
                    const suggested = cost * (1 + (markup / 100));
                    suggestedSpan.textContent = AppUtils.formatCurrency(suggested);
                    priceInput.value = suggested.toFixed(2);
                };

                // Escuchar cambios en costo y porcentaje para recalcular
                costInput.addEventListener('input', updatePrice);
                markupInput.addEventListener('input', updatePrice);
                // Opcional: Recalcular si cambian la cantidad (aunque no afecte al precio unitario, da feedback visual)
                document.getElementById('pur-qty').addEventListener('input', updatePrice);

                search.addEventListener('input', async (e) => {
                    const term = e.target.value.trim();
                    if (term.length < 2) { results.classList.add('hidden'); return; }

                    const res = await fetch(`${URLROOT}/inventario/listar`);
                    const items = await res.json();
                    const filtered = items.filter(i => i.nombre.toLowerCase().includes(term.toLowerCase()));

                    if (filtered.length > 0) {
                        results.innerHTML = filtered.map(i => `
                            <div class="p-2 hover:bg-slate-50 cursor-pointer text-xs border-b last:border-0" onclick="window.setPurchaseProduct(${JSON.stringify(i).replace(/"/g, '&quot;')})">
                                <b>${i.nombre}</b> <span class="text-slate-400">(Stock: ${i.stock})</span>
                            </div>
                        `).join('');
                        results.classList.remove('hidden');
                    } else {
                        results.innerHTML = '<div class="p-2 text-xs text-blue-600 italic">Producto nuevo: Se creará en el inventario</div>';
                        results.classList.remove('hidden');
                        selectedProduct = null;
                    }
                });

                window.setPurchaseProduct = (item) => {
                    selectedProduct = item;
                    search.value = item.nombre;
                    // Al seleccionar, cargamos el último costo conocido del inventario
                    costInput.value = item.ultimo_costo || 0;
                    results.classList.add('hidden');
                    updatePrice(); // ¡Calcula el precio de venta inmediatamente!
                };
            },
            preConfirm: () => {
                const qty = parseInt(document.getElementById('pur-qty').value);
                const cost = parseFloat(document.getElementById('pur-cost').value);
                const priceVenta = parseFloat(document.getElementById('pur-price').value);
                const name = document.getElementById('pur-search').value.trim();

                if (!name || isNaN(qty) || qty <= 0 || isNaN(cost) || cost <= 0 || isNaN(priceVenta) || priceVenta <= 0) {
                    Swal.showValidationMessage('Verifique los datos (Cant, Costo y Precio de Venta son requeridos)');
                    return false;
                }

                return {
                    proveedor_id: proveedorId,
                    producto_id: selectedProduct ? selectedProduct.id : null,
                    nombre: name.toUpperCase(),
                    cantidad: qty,
                    costo: cost,
                    precio_venta: priceVenta,
                    pagado: parseFloat(document.getElementById('pur-paid').value) || 0,
                    fecha_cobro: document.getElementById('pur-cutoff').value
                };
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch(`${URLROOT}/proveedores/registrarCompra`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(result.value)
                    });
                    const data = await response.json();

                    if (data.success) {
                        AppUtils.showToast(data.mensaje);
                        loadData();
                    } else {
                        AppUtils.showToast(data.mensaje, 'error');
                    }
                } catch (error) {
                    AppUtils.showToast('Error al procesar la compra', 'error');
                }
            }
        });
    };

    loadData();
});