/**
 * Lógica de Facturación con Gestión de Colas
 * 
 * v2.7 (2026-10-09) — FIX cliente no persistente:
 *   • FIX: Al seleccionar un cliente desde el buscador, ahora se fuerza
 *     un sync inmediato (syncActiveInvoice(true)) en vez de esperar 1s
 *     al debounce. Antes, si algún render intermedio pisaba el estado,
 *     el cliente no se persistía.
 *   • FIX: El registro rápido de cliente también dispara el sync antes
 *     de hacer renderQueue + renderInvoice (evita race condition).
 *   • Toast actualizado: "Cliente vinculado y guardado" en vez de solo
 *     "Cliente vinculado".
 * 
 * v2.6 (P1-11): Polling de facturas vía PollingManager (30s).
 * v2.5: FIX CSRF (X-CSRF-TOKEN en todas las peticiones POST).
 * 
 * v2.8 (2026-10-09) — P2-09:
 *   • El botón "Registro Rápido de Cliente" (#btnQuickClient) ahora delega
 *     al helper unificado AppUtils.openQuickClientModal(), que también usa
 *     taller_nueva_orden.js → quickRegisterOS. Se eliminó el Swal.fire
 *     duplicado y el fetch propio del listener original (~60 líneas).
 *   • La lógica post-guardado (crear option en select oculto, vincular a
 *     factura activa, sync inmediato, renderQueue, renderInvoice) se
 *     conserva intacta dentro del callback onSuccess.
 * 
 * DEPENDENCIAS: polling.js, utils.js, DataTableRefactor.js, app.js.
 */
document.addEventListener('DOMContentLoaded', () => {
    const inputPlaca = document.getElementById('pos-placa');
    const inputModelo = document.getElementById('pos-modelo');
    const inputCliente = document.getElementById('pos-cliente-id');
    const inputMecanico = document.getElementById('pos-mecanico-id');
    const displayFacturaId = document.getElementById('pos-factura-id');
    const searchInput = document.getElementById('pos-search');
    const searchResults = document.getElementById('pos-search-results');
    const inputQty = document.getElementById('pos-qty');
    const btnAddItem = document.getElementById('btn-add-item');
    const inputServicioNombre = document.getElementById('pos-service-name');
    const inputServicioPrecio = document.getElementById('pos-service-price');
    const btnAddService = document.getElementById('btn-add-service');
    const cartBody = document.getElementById('pos-cart-body');
    const btnProcessSale = document.getElementById('btn-process-sale');
    const inputIvaToggle = document.getElementById('pos-iva-toggle');
    const btnQuickClient = document.getElementById('btn-quick-client');
    const inputClienteNombre = document.getElementById('cliente_nombre');
    const clientSearchInput = document.getElementById('pos-client-search');
    const clientSearchResults = document.getElementById('pos-client-results');
    const posObservaciones = document.getElementById('pos-observaciones');
    const inputPagoEfectivo = document.getElementById('pos-pago-efectivo');
    const inputPagoTransferencia = document.getElementById('pos-pago-transferencia');
    const displaySaldoPendiente = document.getElementById('pos-saldo-pendiente');

    const IVA_PERCENT = (typeof IVA_RATE !== 'undefined') ? (IVA_RATE * 100) : 0;
    const CSRF = (typeof CSRF_TOKEN !== 'undefined' && CSRF_TOKEN) ? CSRF_TOKEN : '';

    let syncTimeout = null;
    let openInvoices = [];
    let activeInvoiceId = null;
    let selectedItemFromSearch = null;
    let lastSearchResults = [];
    let lastClientResults = [];

    const presupuestosDetallesCache = new Set();

    document.addEventListener('userLoaded', () => {
        renderQueue();
        renderInvoice();
    });

    const postJSON = async (url, body) => {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: JSON.stringify(body)
        });
    };

    const loadInvoicesFromServer = async () => {
        try {
            const res = await fetch(`${URLROOT}/facturacion/listarBorradores`);
            const drafts = await res.json();

            const localInvoices = openInvoices.filter(inv => !inv.id_db);

            const serverInvoices = drafts.map(d => {
                const existingInv = openInvoices.find(inv => inv.id_db === d.id);
                const preservedMecanicoId = (existingInv && !d.mecanico_id) ? existingInv.mecanico_id : d.mecanico_id;
                const preservedPresupuestoId = existingInv ? existingInv.presupuesto_activo_id : null;

                return {
                    id: 'FAC-' + String(d.id).padStart(3, '0'),
                    id_db: d.id,
                    placa: d.placa || '',
                    modelo: d.modelo_vehiculo || '',
                    cliente_id: d.cliente_id || '',
                    mecanico_id: preservedMecanicoId || '',
                    iva_activo: (parseFloat(d.iva_monto) > 0),
                    pago_efectivo: parseFloat(d.pago_efectivo || 0),
                    pago_transferencia: parseFloat(d.pago_transferencia || 0),
                    saldo_pendiente: parseFloat(d.saldo_pendiente || 0),
                    items: d.items || [],
                    usuario_id: d.usuario_id,
                    usuario_nombre: d.usuario_nombre,
                    cliente_nombre: d.cliente_nombre || '',
                    orden_id: d.orden_id || null,
                    tipo_procedencia: d.tipo_procedencia || 'MOSTRADOR',
                    diagnostico_entrada: d.diagnostico_entrada || '',
                    diagnostico_salida: d.diagnostico_salida || d.observaciones || '',
                    observaciones: d.observaciones || '',
                    presupuesto_activo_id: d.presupuesto_activo_id || preservedPresupuestoId || null
                };
            });

            openInvoices = [...serverInvoices, ...localInvoices];

            if (activeInvoiceId && activeInvoiceId.startsWith('TKT-')) {
                const stillExists = openInvoices.some(inv => inv.id === activeInvoiceId);
                if (!stillExists) {
                    activeInvoiceId = null;
                    clearInputs();
                }
            }

            if (!activeInvoiceId && openInvoices.length > 0) {
                const urlId = new URLSearchParams(window.location.search).get('id');
                const found = openInvoices.find(inv => String(inv.id_db) === String(urlId));
                if (urlId && found) {
                    activeInvoiceId = 'FAC-' + String(urlId).padStart(3, '0');
                } else {
                    activeInvoiceId = openInvoices[0].id;
                }
            }

            if (openInvoices.length === 0) {
                initNewInvoice();
            }
            renderQueue();
            renderInvoice();
        } catch (e) {
            console.error("Error cargando facturas del servidor", e);
        }
    };

    const loadClients = async () => {
        try {
            const res = await fetch(`${URLROOT}/clientes/listar`);
            if (!res.ok) return;
            const result = await res.json();
            const clientes = result.data || [];

            inputCliente.innerHTML = '<option value="">SIN CLIENTE (VENTA RÁPIDA)</option>';

            clientes.forEach(c => {
                const option = document.createElement('option');
                option.value = c.id;
                option.textContent = `${c.nombre} (${c.id})`;
                inputCliente.appendChild(option);
            });
        } catch (e) {
            console.error("Error al cargar clientes:", e);
        }
    };

    // ───────────────────────────────────────────────────────────────────────
    // REGISTRO RÁPIDO DE CLIENTE (FIX P2-09: helper unificado)
    // ───────────────────────────────────────────────────────────────────────
    btnQuickClient.addEventListener('click', async () => {
        await AppUtils.openQuickClientModal({
            title: 'REGISTRO DE CLIENTE',
            confirmText: 'REGISTRAR',
            onSuccess: async (cliente) => {
                // 1. Crear/actualizar la opción en el select oculto
                let option = inputCliente.querySelector(`option[value="${cliente.id}"]`);
                if (!option) {
                    option = document.createElement('option');
                    option.value = cliente.id;
                    option.textContent = cliente.nombre;
                    inputCliente.appendChild(option);
                } else {
                    option.textContent = cliente.nombre;
                }
                inputCliente.value = cliente.id;
                if (clientSearchInput) clientSearchInput.value = cliente.nombre;

                // 2. Vincular a la factura activa (o crear una nueva)
                let activeInv = openInvoices.find(i => i.id === activeInvoiceId);
                if (!activeInv) {
                    await initNewInvoice(true);
                    activeInv = openInvoices.find(i => i.id === activeInvoiceId);
                }
                if (activeInv) {
                    activeInv.cliente_id = cliente.id;
                    activeInv.cliente_nombre = cliente.nombre;

                    // FIX: Sync inmediato antes de cualquier render
                    await syncActiveInvoice(true);
                }

                // 3. Render final
                renderQueue();
                renderInvoice();
                AppUtils.showToast('Cliente registrado y vinculado');
            }
        });
    });

    const initNewInvoice = async (forceSave = false) => {
        const domPlaca = inputPlaca.value;
        const domModelo = inputModelo.value;
        const domClienteId = inputCliente.value;
        const domClienteNombre = clientSearchInput ? clientSearchInput.value : '';
        const domMecanicoId = inputMecanico.value;
        const domObservaciones = document.getElementById('pos-observaciones')?.value || '';

        if (forceSave) {
            inputPlaca.value = '';
            inputModelo.value = '';
            inputCliente.value = '';
            if (clientSearchInput) clientSearchInput.value = '';
            const obsField = document.getElementById('pos-observaciones');
            if (obsField) obsField.value = '';
        }

        selectedItemFromSearch = null;
        if (searchInput) searchInput.value = '';

        const userName = currentLoggedInUser ? currentLoggedInUser.staffName : '---';
        const isMechanic = currentLoggedInUser && (parseInt(currentLoggedInUser.roleId) === 2 || currentLoggedInUser.role.toUpperCase() === 'MECANICO');
        const staffId = currentLoggedInUser ? (currentLoggedInUser.staffId || currentLoggedInUser.staff_id) : '';
        const ordenIdFromDom = displayFacturaId.dataset.ordenId || null;

        const invData = {
            id: 'PROV-' + Math.floor(Math.random() * 9000 + 1000),
            id_db: null,
            orden_id: forceSave ? null : ordenIdFromDom,
            placa: forceSave ? '' : domPlaca,
            modelo: forceSave ? '' : domModelo,
            cliente_id: forceSave ? '' : domClienteId,
            mecanico_id: forceSave ? (isMechanic ? staffId : '') : (domMecanicoId || (isMechanic ? staffId : '')),
            iva_activo: false,
            pago_efectivo: 0,
            pago_transferencia: 0,
            saldo_pendiente: 0,
            items: [],
            usuario_id: currentLoggedInUser ? currentLoggedInUser.id : null,
            usuario_nombre: userName,
            cliente_nombre: forceSave ? '' : domClienteNombre,
            tipo_procedencia: (forceSave || !ordenIdFromDom) ? 'MOSTRADOR' : 'TALLER',
            observaciones: forceSave ? '' : domObservaciones,
            presupuesto_activo_id: null
        };

        if (!forceSave) {
            if (!openInvoices.some(inv => !inv.id_db)) {
                openInvoices.push(invData);
            }
            activeInvoiceId = invData.id;
            renderQueue();
            renderInvoice();
            return;
        }

        try {
            const res = await postJSON(`${URLROOT}/facturacion/sincronizarBorrador`, invData);
            const result = await res.json();

            if (result.success) {
                invData.id = 'FAC-' + String(result.venta_id).padStart(3, '0');
                invData.id_db = result.venta_id;
                openInvoices.push(invData);
                activeInvoiceId = invData.id;
                renderQueue();
                renderInvoice();
            }
        } catch (e) {
            console.error("Error al crear borrador en DB:", e);
        }
    };

    inputPlaca.addEventListener('input', (e) => {
        const val = e.target.value.toUpperCase();
        updateActiveData('placa', val);
        if (val.trim() !== '') {
            updateActiveData('tipo_procedencia', 'TALLER');
        } else {
            updateActiveData('tipo_procedencia', 'MOSTRADOR');
        }
        renderQueue();
        renderInvoice();
    });

    inputModelo.addEventListener('input', (e) => {
        updateActiveData('modelo', e.target.value.toUpperCase());
        renderQueue();
        renderInvoice();
    });

    inputCliente.addEventListener('change', (e) => {
        updateActiveData('cliente_id', e.target.value);
        renderInvoice();
    });

    if (posObservaciones) {
        posObservaciones.addEventListener('input', (e) => {
            updateActiveData('observaciones', e.target.value.toUpperCase());
        });
        posObservaciones.addEventListener('blur', () => {
            syncActiveInvoice();
        });
    }

    inputMecanico.addEventListener('change', (e) => {
        updateActiveData('mecanico_id', e.target.value);
        renderQueue();
        renderInvoice();
    });

    inputPagoEfectivo?.addEventListener('input', (e) => {
        updateActiveData('pago_efectivo', parseFloat(e.target.value.replace(',', '.')) || 0);
        renderInvoice();
    });

    inputPagoTransferencia?.addEventListener('input', (e) => {
        updateActiveData('pago_transferencia', parseFloat(e.target.value.replace(',', '.')) || 0);
        renderInvoice();
    });

    // ───────────────────────────────────────────────────────────────────────
    // BÚSQUEDA DE CLIENTES (con fix de persistencia)
    // ───────────────────────────────────────────────────────────────────────
    if (clientSearchInput) {
        clientSearchInput.addEventListener('input', async (e) => {
            const term = e.target.value.trim();
            if (term.length < 2) {
                clientSearchResults.classList.add('hidden');
                if (term.length === 0) {
                    inputCliente.value = '';
                    updateActiveData('cliente_id', '');
                }
                return;
            }

            const res = await fetch(`${URLROOT}/clientes/listar?q=${term}&limit=5&offset=0`);
            const data = await res.json();
            lastClientResults = data.data || [];

            if (lastClientResults.length > 0) {
                clientSearchResults.innerHTML = lastClientResults.map((c, i) => `
                    <div class="p-4 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0 flex justify-between items-center group transition-colors" 
                         onclick="selectClientFromResults('${i}')">
                        <div>
                            <p class="font-black text-xs uppercase text-navy-blue leading-none mb-1 group-hover:text-black">${c.nombre}</p>
                            <p class="text-[10px] text-slate-400 font-mono italic font-bold">CC/NIT: ${c.id}</p>
                        </div>
                        <i data-lucide="user-plus" class="w-4 h-4 text-slate-300 group-hover:text-neon-green"></i>
                    </div>`).join('');
                clientSearchResults.classList.remove('hidden');
                if (window.lucide) lucide.createIcons();
            } else {
                clientSearchResults.innerHTML = '<p class="p-3 text-center text-slate-400 text-xs uppercase">No encontrado</p>';
                clientSearchResults.classList.remove('hidden');
            }
        });
    }

    /**
     * FIX: Maneja la selección de un cliente desde el buscador.
     * 
     * Cambios respecto a la versión anterior:
     *   1. Actualiza la factura en memoria ANTES de cualquier render.
     *   2. Fuerza un sync INMEDIATO (syncActiveInvoice(true)) en vez de
     *      esperar 1s al debounce.
     *   3. Renderiza la UI DESPUÉS del sync, para que no haya race conditions.
     */
    window.selectClientFromResults = async (index) => {
        const client = lastClientResults[index];
        if (!client) return;

        // 1. Crear la opción en el select oculto si no existe
        let option = inputCliente.querySelector(`option[value="${client.id}"]`);
        if (!option) {
            option = document.createElement('option');
            option.value = client.id;
            option.textContent = client.nombre;
            inputCliente.appendChild(option);
        }

        // 2. Actualizar los inputs visuales
        inputCliente.value = client.id;
        if (clientSearchInput) clientSearchInput.value = client.nombre;
        clientSearchResults.classList.add('hidden');

        // 3. Actualizar la factura activa EN MEMORIA (antes de cualquier render)
        const inv = openInvoices.find(i => i.id === activeInvoiceId);
        if (inv) {
            inv.cliente_id = client.id;
            inv.cliente_nombre = client.nombre;

            // 4. FIX: Sync INMEDIATO (sin esperar al debounce de 1s)
            await syncActiveInvoice(true);
        }

        // 5. Renderizar la UI DESPUÉS del sync
        renderQueue();
        renderInvoice();
        AppUtils.showToast('Cliente vinculado y guardado');
    };

    /* ==================== BÚSQUEDA DE PRESUPUESTOS PARA ANEXAR ==================== */
    const inputPresupuestoFacturacion = document.getElementById('buscarPresupuestoActivoFacturacion');
    const resultsContainerPresupuesto = document.getElementById('presupuesto-activo-results-facturacion');
    const seleccionadoContainerPresupuesto = document.getElementById('presupuesto-seleccionado-facturacion');
    const infoElementPresupuesto = document.getElementById('presupuesto-info-facturacion');
    const clienteElementPresupuesto = document.getElementById('presupuesto-cliente-facturacion');
    let presupuestoSeleccionadoIdFacturacion = null;
    let searchTimeoutPresupuestoFacturacion = null;

    async function cargarDetallesPresupuestoAnexado(presupuestoId) {
        if (!presupuestoId) return;
        if (presupuestosDetallesCache.has(String(presupuestoId))) return;

        try {
            presupuestosDetallesCache.add(String(presupuestoId));
            const res = await fetch(`${URLROOT}/presupuesto/obtener/${presupuestoId}`);
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success || !data.data) return;

            const p = data.data;

            if (infoElementPresupuesto) {
                const totalFmt = parseFloat(p.total || 0).toLocaleString('es-CO', { minimumFractionDigits: 2 });
                infoElementPresupuesto.textContent = `${p.numero} | Total: $${totalFmt}`;
            }
            if (clienteElementPresupuesto) {
                clienteElementPresupuesto.textContent = `Cliente: ${p.cliente_nombre} | Tel: ${p.cliente_telefono || 'N/A'} | Estado: ${p.estado}`;
            }
            if (inputPresupuestoFacturacion) {
                inputPresupuestoFacturacion.value = `${p.numero} - ${p.cliente_nombre}`;
            }

            if (window.lucide) lucide.createIcons();
        } catch (e) {
            presupuestosDetallesCache.delete(String(presupuestoId));
        }
    }

    if (inputPresupuestoFacturacion && resultsContainerPresupuesto) {
        inputPresupuestoFacturacion.addEventListener('input', () => {
            clearTimeout(searchTimeoutPresupuestoFacturacion);
            const term = inputPresupuestoFacturacion.value.trim().toLowerCase();

            if (term.length < 2) {
                if (resultsContainerPresupuesto) resultsContainerPresupuesto.classList.add('hidden');
                return;
            }

            searchTimeoutPresupuestoFacturacion = setTimeout(async () => {
                try {
                    const res = await fetch(`${URLROOT}/presupuesto/buscarActivos?q=${encodeURIComponent(term)}`);
                    const result = await res.json();

                    if (result.success && result.data) {
                        renderPresupuestoResultsFacturacion(result.data, term);
                    } else {
                        resultsContainerPresupuesto.innerHTML = '<div class="p-4 text-center text-slate-400 text-xs italic">No se encontraron presupuestos disponibles</div>';
                        resultsContainerPresupuesto.classList.remove('hidden');
                    }
                } catch (e) {
                    resultsContainerPresupuesto.innerHTML = '<div class="p-4 text-center text-red-400 text-xs italic">Error al buscar</div>';
                    resultsContainerPresupuesto.classList.remove('hidden');
                }
            }, 300);
        });

        document.addEventListener('click', (e) => {
            if (resultsContainerPresupuesto && !resultsContainerPresupuesto.contains(e.target) && e.target !== inputPresupuestoFacturacion) {
                resultsContainerPresupuesto.classList.add('hidden');
            }
        });

        resultsContainerPresupuesto.addEventListener('click', (e) => {
            const item = e.target.closest('[data-presupuesto-id]');
            if (!item) return;
            e.preventDefault();
            e.stopPropagation();

            window.seleccionarPresupuestoActivoFacturacion(
                item.dataset.presupuestoId,
                item.dataset.presupuestoNumero,
                item.dataset.clienteNombre,
                item.dataset.clienteTelefono,
                item.dataset.total,
                item.dataset.fecha
            );
        });
    }

    function renderPresupuestoResultsFacturacion(presupuestos, term) {
        if (!resultsContainerPresupuesto) return;

        let html = '';
        if (presupuestos.length > 0) {
            html = presupuestos.map(p => {
                const estadoBadge = p.estado === 'ENVIADO'
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-700">ENVIADO</span>'
                    : (p.estado === 'ACEPTADO'
                        ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">ACEPTADO</span>'
                        : '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">' + p.estado + '</span>');

                const safeClienteNombre = (p.cliente_nombre || '').replace(/"/g, '&quot;');
                const safeClienteTelefono = (p.cliente_telefono || '').replace(/"/g, '&quot;');
                const safeNumero = (p.numero || '').replace(/"/g, '&quot;');
                const safeFecha = (p.fecha_emision || '').replace(/"/g, '&quot;');

                return `
                    <div class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0"
                         data-presupuesto-id="${p.id}"
                         data-presupuesto-numero="${safeNumero}"
                         data-cliente-nombre="${safeClienteNombre}"
                         data-cliente-telefono="${safeClienteTelefono}"
                         data-total="${p.total}"
                         data-fecha="${safeFecha}">
                        <div class="flex justify-between items-start">
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-xs uppercase text-navy-blue truncate">${p.numero} ${estadoBadge}</p>
                                <p class="text-[10px] text-slate-400 font-mono truncate">${p.cliente_nombre}</p>
                            </div>
                            <div class="text-right flex-shrink-0 ml-2">
                                <p class="text-[10px] text-amber-600 font-mono font-bold">$${parseFloat(p.total).toLocaleString('es-CO', { minimumFractionDigits: 2 })}</p>
                                <p class="text-[9px] text-slate-500">${new Date(p.fecha_emision).toLocaleDateString('es-ES')}</p>
                            </div>
                        </div>
                    </div>`;
            }).join('');
        } else {
            html = '<div class="p-4 text-center text-slate-400 text-xs italic">No se encontraron presupuestos disponibles</div>';
        }

        resultsContainerPresupuesto.innerHTML = html;
        resultsContainerPresupuesto.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    window.seleccionarPresupuestoActivoFacturacion = async (id, numero, clienteNombre, clienteTelefono, total, fecha) => {
        const activeInvoice = openInvoices.find(i => i.id === activeInvoiceId);
        if (!activeInvoice) {
            AppUtils.showToast('No hay factura activa. Cree una primero.', 'warning');
            return;
        }

        presupuestoSeleccionadoIdFacturacion = id;
        inputPresupuestoFacturacion.value = `${numero} - ${clienteNombre}`;
        if (resultsContainerPresupuesto) resultsContainerPresupuesto.classList.add('hidden');

        infoElementPresupuesto.textContent = `${numero} | Total: $${parseFloat(total).toLocaleString('es-CO', { minimumFractionDigits: 2 })}`;
        clienteElementPresupuesto.textContent = `Cliente: ${clienteNombre} | Tel: ${clienteTelefono || 'N/A'} | Emitido: ${fecha ? new Date(fecha).toLocaleDateString('es-ES') : 'N/A'}`;
        seleccionadoContainerPresupuesto.classList.remove('hidden');

        try {
            AppUtils.showLoading('Anexando presupuesto...');
            const resEstado = await postJSON(`${URLROOT}/presupuesto/iniciarProceso/${id}`, {
                modulo: 'FACTURACION',
                crear_reservas: false
            });
            const resultEstado = await resEstado.json();
            AppUtils.hideLoading();

            if (!resultEstado.success) {
                presupuestoSeleccionadoIdFacturacion = null;
                inputPresupuestoFacturacion.value = '';
                if (seleccionadoContainerPresupuesto) seleccionadoContainerPresupuesto.classList.add('hidden');
                AppUtils.showToast(resultEstado.mensaje || 'No se pudo anexar el presupuesto', 'error');
                return;
            }
        } catch (e) {
            AppUtils.hideLoading();
            presupuestoSeleccionadoIdFacturacion = null;
            inputPresupuestoFacturacion.value = '';
            if (seleccionadoContainerPresupuesto) seleccionadoContainerPresupuesto.classList.add('hidden');
            AppUtils.showToast('Error de conexión al anexar presupuesto', 'error');
            return;
        }

        activeInvoice.presupuesto_activo_id = id;
        presupuestosDetallesCache.add(String(id));

        try {
            AppUtils.showLoading('Cargando items del presupuesto...');
            const res = await fetch(`${URLROOT}/presupuesto/obtenerParaAnexar/${id}`);
            const data = await res.json();
            AppUtils.hideLoading();

            if (!data.success || !data.items) {
                AppUtils.showToast(data.mensaje || 'No se pudieron cargar los items', 'error');
                return;
            }

            data.items.forEach(item => {
                activeInvoice.items.push({
                    id: item.producto_id,
                    nombre: item.nombre,
                    precio: parseFloat(item.precio),
                    costo_promedio: parseFloat(item.costo_promedio || 0),
                    cantidad: parseInt(item.cantidad),
                    tipo: item.tipo
                });
            });

            if (!activeInvoice.cliente_id && data.data.cliente_id) {
                let option = inputCliente.querySelector(`option[value="${data.data.cliente_id}"]`);
                if (!option) {
                    option = document.createElement('option');
                    option.value = data.data.cliente_id;
                    option.textContent = data.data.cliente_nombre || data.data.cliente_id;
                    inputCliente.appendChild(option);
                }
                inputCliente.value = data.data.cliente_id;
                activeInvoice.cliente_id = data.data.cliente_id;
                activeInvoice.cliente_nombre = data.data.cliente_nombre || '';
                if (clientSearchInput) clientSearchInput.value = activeInvoice.cliente_nombre;
            }

            if (!activeInvoice.placa && data.data.vehiculo_placa) {
                activeInvoice.placa = data.data.vehiculo_placa;
                inputPlaca.value = data.data.vehiculo_placa;
            }
            if (!activeInvoice.modelo && data.data.vehiculo_modelo) {
                activeInvoice.modelo = (data.data.vehiculo_marca || '') + ' ' + (data.data.vehiculo_modelo || '');
                inputModelo.value = activeInvoice.modelo;
            }

            renderInvoice();
            syncActiveInvoice();

            AppUtils.showToast(`Presupuesto ${numero} anexado con ${data.items.length} item(s).`, 'success');
        } catch (e) {
            AppUtils.hideLoading();
            AppUtils.showToast('Error al cargar los items del presupuesto', 'error');
        }
    };

    window.desanexarPresupuestoFacturacion = async () => {
        const activeInvoice = openInvoices.find(i => i.id === activeInvoiceId);
        const presupuestoId = activeInvoice?.presupuesto_activo_id || presupuestoSeleccionadoIdFacturacion;

        if (presupuestoId) {
            try {
                AppUtils.showLoading('Liberando presupuesto...');
                const res = await postJSON(`${URLROOT}/presupuesto/liberarInventario/${presupuestoId}`, {
                    motivo: 'DESANEXADO_DESDE_POS'
                });
                const result = await res.json();
                AppUtils.hideLoading();

                if (!result.success) {
                    console.warn('No se pudo liberar el presupuesto:', result.mensaje);
                }
            } catch (e) {
                AppUtils.hideLoading();
            }
        }

        presupuestoSeleccionadoIdFacturacion = null;
        inputPresupuestoFacturacion.value = '';
        if (resultsContainerPresupuesto) resultsContainerPresupuesto.classList.add('hidden');
        if (seleccionadoContainerPresupuesto) seleccionadoContainerPresupuesto.classList.add('hidden');

        if (activeInvoice) {
            delete activeInvoice.presupuesto_activo_id;
            presupuestosDetallesCache.delete(String(presupuestoId));
        }

        if (window.AppUtils) AppUtils.showToast('Presupuesto desanexado. Vuelve a estar disponible (items no eliminados).');
    };

    if (inputIvaToggle) {
        inputIvaToggle.addEventListener('change', (e) => {
            updateActiveData('iva_activo', e.target.checked);
            renderInvoice();
        });
    }

    const updateActiveData = (field, value) => {
        const inv = openInvoices.find(i => i.id === activeInvoiceId);
        if (inv) {
            inv[field] = value;
            debounceSync();
        }
    };

    const renderQueue = () => {
        const container = document.getElementById('pos-active-drafts');
        if (openInvoices.length === 0) {
            container.innerHTML = `<span class="text-[10px] font-bold text-amber-500 bg-amber-50 px-3 py-1 rounded-full border border-amber-200 flex items-center gap-1 uppercase">
                <i data-lucide="alert-circle" class="w-3 h-3"></i> No hay facturas abiertas
            </span>`;
            lucide.createIcons();
            return;
        }

        container.innerHTML = openInvoices.map((inv, index) => {
            let badgeClass = 'bg-amber-100 text-amber-700 border-amber-200';
            if (inv.tipo_procedencia === 'OS') {
                badgeClass = 'bg-emerald-100 text-emerald-700 border-emerald-200';
            } else if (inv.tipo_procedencia === 'TALLER') {
                badgeClass = 'bg-blue-100 text-blue-700 border-blue-200';
            }

            const htmlBadge = `
                <span class="px-1.5 py-0.5 rounded text-[7px] font-bold border ${badgeClass}">
                    ${inv.tipo_procedencia || 'MOSTRADOR'}
                </span>
            `;

            return `
            <div onclick="switchInvoice('${inv.id}')" class="flex-shrink-0 px-3 py-1.5 rounded-lg border-2 transition-all cursor-pointer flex items-center gap-3 
                ${inv.id === activeInvoiceId ? 'border-neon-green bg-white shadow-sm' : 'border-transparent bg-slate-100 opacity-60 hover:opacity-100'}">
                <div class="flex flex-col">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[9px] font-black text-navy-blue">${inv.id}</span>
                        ${htmlBadge}
                    </div>
                    <span class="text-[10px] font-bold uppercase truncate max-w-[80px]">${inv.modelo || 'SIN DESC.'}</span>
                </div>
                <button onclick="closeInvoice(${index}, event)" class="text-slate-400 hover:text-red-500 transition-colors" title="Cerrar Factura">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>
        `}).join('');
        lucide.createIcons();
    };

    const debounceSync = () => {
        clearTimeout(syncTimeout);
        syncTimeout = setTimeout(syncActiveInvoice, 1000);
    };

    window.switchInvoice = (id) => {
        activeInvoiceId = id;
        renderQueue();
        renderInvoice();
    };

    window.closeInvoice = async (index, event) => {
        event.stopPropagation();
        const inv = openInvoices[index];

        const proceedWithClosing = () => {
            const idToDelete = inv.id;
            openInvoices.splice(index, 1);

            if (openInvoices.length === 0) {
                activeInvoiceId = null;
                clearInputs();
            } else if (activeInvoiceId === idToDelete) {
                activeInvoiceId = openInvoices[0].id;
            }

            renderQueue();
            renderInvoice();
        };

        if (inv.presupuesto_activo_id) {
            try {
                await postJSON(`${URLROOT}/presupuesto/liberarInventario/${inv.presupuesto_activo_id}`, {
                    motivo: 'BORRADOR_CANCELADO'
                });
            } catch (e) {
                console.error('Error liberando presupuesto al cerrar factura:', e);
            }
        }

        if (inv.id_db) {
            AppUtils.confirmAction('¿Eliminar borrador?', 'Esta acción cancelará la orden y liberará el stock.', async () => {
                const res = await postJSON(`${URLROOT}/facturacion/eliminarBorrador/${inv.id_db}`, {});
                const data = await res.json();
                if (data.success) {
                    proceedWithClosing();
                } else {
                    AppUtils.showToast(data.mensaje || 'Error al eliminar', 'error');
                }
            });
        } else {
            proceedWithClosing();
        }
    };

    const clearInputs = () => {
        displayFacturaId.textContent = "---";
        displayFacturaId.dataset.ordenId = "";
        inputPlaca.value = "";
        inputModelo.value = "";
        inputCliente.value = "";
        if (inputClienteNombre) inputClienteNombre.value = "";
        if (clientSearchInput) clientSearchInput.value = "";

        const obsField = document.getElementById('pos-observaciones');
        if (obsField) obsField.value = "";

        const obsPreview = document.getElementById('pos-obs-preview');
        if (obsPreview) obsPreview.classList.add('hidden');

        const containerDiagOs = document.getElementById('container-diag-os');
        const textDiagOs = document.getElementById('text-diag-os');
        const labelObs = document.getElementById('label-obs');
        if (containerDiagOs) containerDiagOs.classList.add('hidden');
        if (textDiagOs) textDiagOs.textContent = '';
        if (labelObs) labelObs.textContent = 'Observaciones / Detalles del Trabajo';

        if (inputPagoEfectivo) inputPagoEfectivo.value = 0;
        if (inputPagoTransferencia) inputPagoTransferencia.value = 0;
        if (displaySaldoPendiente) displaySaldoPendiente.textContent = "$0.00";

        if (inputMecanico && !inputMecanico.disabled) inputMecanico.value = "";

        presupuestoSeleccionadoIdFacturacion = null;
        if (inputPresupuestoFacturacion) inputPresupuestoFacturacion.value = '';
        if (seleccionadoContainerPresupuesto) seleccionadoContainerPresupuesto.classList.add('hidden');

        cartBody.innerHTML = '<tr><td class="py-32 text-center text-slate-300 uppercase text-xs font-bold tracking-widest opacity-50"><i data-lucide="shopping-cart" class="w-16 h-16 mx-auto mb-4"></i> No hay factura activa</td></tr>';
        document.getElementById('pos-subtotal').textContent = "$0.00";
        document.getElementById('pos-iva').textContent = "$0.00";
        document.getElementById('pos-total').textContent = "$0.00";
        lucide.createIcons();
    };

    const syncActiveInvoice = async (force = false) => {
        if (!activeInvoiceId) return;
        const inv = openInvoices.find(i => i.id === activeInvoiceId);
        if (!inv) return;

        if (force === false) {
            inv.placa = inputPlaca.value.trim();
            inv.modelo = inputModelo.value.trim();
            inv.mecanico_id = inputMecanico.value;
            inv.cliente_id = inputCliente.value;
            inv.cliente_nombre = clientSearchInput ? clientSearchInput.value : '';
            inv.observaciones = posObservaciones?.value || '';
            inv.diagnostico_salida = posObservaciones?.value || '';
            inv.orden_id = inv.orden_id || displayFacturaId.dataset.ordenId || null;
        }

        const subtotal = inv.items.reduce((acc, item) => acc + (item.precio * item.cantidad), 0);
        const isIvaEnabled = inv.iva_activo === true;
        const ivaMonto = isIvaEnabled ? (subtotal * (IVA_PERCENT / 100)) : 0;
        const total = subtotal + ivaMonto;

        const pef = parseFloat(inv.pago_efectivo || 0);
        const ptra = parseFloat(inv.pago_transferencia || 0);
        const pendiente = total - (pef + ptra);

        inv.subtotal = subtotal;
        inv.iva_monto = ivaMonto;
        inv.total = total;
        inv.saldo_pendiente = pendiente > 0 ? pendiente : 0;

        const hasContent = inv.items.length > 0 || inv.placa !== '' || inv.modelo !== '' || inv.cliente_id !== '' || inv.mecanico_id !== '' || pef > 0 || ptra > 0 || inv.presupuesto_activo_id;
        if (!hasContent && !inv.id_db && !force) {
            return;
        }

        try {
            const res = await postJSON(`${URLROOT}/facturacion/sincronizarBorrador`, inv);

            if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);

            const contentType = res.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const errorHtml = await res.text();
                console.error("Respuesta no válida del servidor (HTML):", errorHtml);
                AppUtils.showToast('Error del servidor. Revisa la consola (F12) para detalles.', 'error');
                return;
            }

            const data = await res.json();
            if (data.success) {
                const isFirstSync = !inv.id_db;
                inv.id_db = data.venta_id;

                if (isFirstSync) {
                    const oldId = inv.id;
                    inv.id = 'FAC-' + String(data.venta_id).padStart(3, '0');
                    if (activeInvoiceId === oldId) activeInvoiceId = inv.id;

                    renderQueue();
                    renderInvoice();
                }
            }
        } catch (error) {
            console.error("Error sincronizando con el servidor:", error);
        }
    };

    window.selectItemForAdd = (item) => {
        if (!item) return;
        selectedItemFromSearch = item;
        searchInput.value = item.nombre;
        searchResults.classList.add('hidden');
        inputQty.focus();
    };

    window.selectItemFromResults = (index) => {
        const item = lastSearchResults[parseInt(index)];
        if (item) window.selectItemForAdd(item);
    };

    btnAddItem.addEventListener('click', () => {
        if (!activeInvoiceId) return AppUtils.showToast('Cree una factura primero', 'warning');
        if (!selectedItemFromSearch) return AppUtils.showToast('Busque un artículo primero', 'warning');
        const qty = parseInt(inputQty.value);
        if (qty <= 0 || qty > selectedItemFromSearch.stock_disponible) return AppUtils.showToast('Stock insuficiente', 'error');

        const activeInvoice = openInvoices.find(i => i.id === activeInvoiceId);
        activeInvoice.items.push({
            id: selectedItemFromSearch.id,
            nombre: selectedItemFromSearch.nombre,
            precio: parseFloat(selectedItemFromSearch.precio),
            costo_promedio: parseFloat(selectedItemFromSearch.costo_promedio || 0),
            cantidad: qty,
            tipo: 'PRODUCTO'
        });

        selectedItemFromSearch = null;
        searchInput.value = '';
        inputQty.value = 1;
        searchResults.classList.add('hidden');
        renderInvoice();
        syncActiveInvoice();
    });

    btnAddService.addEventListener('click', () => {
        if (!activeInvoiceId) return AppUtils.showToast('Cree una factura primero', 'warning');
        const nombre = inputServicioNombre.value.trim();
        const precio = parseFloat(inputServicioPrecio.value.replace(',', '.')) || 0;
        if (!nombre || isNaN(precio) || precio <= 0) return AppUtils.showToast('Datos de servicio inválidos', 'warning');

        const activeInvoice = openInvoices.find(i => i.id === activeInvoiceId);
        activeInvoice.items.push({
            id: null,
            nombre: nombre.toUpperCase(),
            precio: precio,
            cantidad: 1,
            tipo: 'SERVICIO'
        });

        inputServicioNombre.value = '';
        inputServicioPrecio.value = '';
        renderInvoice();
        syncActiveInvoice();
    });

    window.removeItem = (index) => {
        const activeInvoice = openInvoices.find(i => i.id === activeInvoiceId);
        if (activeInvoice) {
            activeInvoice.items.splice(index, 1);
            renderInvoice();
            syncActiveInvoice();
        }
    };

    const renderInvoice = () => {
        const activeInvoice = openInvoices.find(i => i.id === activeInvoiceId);
        if (!activeInvoice) return;

        displayFacturaId.textContent = activeInvoice.id;
        displayFacturaId.dataset.ordenId = activeInvoice.orden_id || '';
        inputPlaca.value = activeInvoice.placa;
        inputModelo.value = activeInvoice.modelo;
        inputMecanico.value = activeInvoice.mecanico_id || '';

        const selectedMecanicoText = inputMecanico.options[inputMecanico.selectedIndex]?.text;
        const mecanicoName = (activeInvoice.mecanico_id && selectedMecanicoText) ? selectedMecanicoText.split('(')[0].trim() : null;
        document.getElementById('pos-user-name').textContent = mecanicoName || activeInvoice.usuario_nombre || '---';

        if (activeInvoice.cliente_id) {
            let option = inputCliente.querySelector(`option[value="${activeInvoice.cliente_id}"]`);
            if (!option) {
                option = document.createElement('option');
                option.value = activeInvoice.cliente_id;
                option.textContent = activeInvoice.cliente_nombre || activeInvoice.cliente_id;
                inputCliente.appendChild(option);
            }
        }
        inputCliente.value = activeInvoice.cliente_id || '';

        if (clientSearchInput) clientSearchInput.value = activeInvoice.cliente_nombre || '';

        if (inputIvaToggle) {
            inputIvaToggle.checked = (activeInvoice.iva_activo !== false);
        }

        if (inputPagoEfectivo && document.activeElement !== inputPagoEfectivo) {
            inputPagoEfectivo.value = activeInvoice.pago_efectivo || 0;
        }
        if (inputPagoTransferencia && document.activeElement !== inputPagoTransferencia) {
            inputPagoTransferencia.value = activeInvoice.pago_transferencia || 0;
        }

        if (activeInvoice.presupuesto_activo_id && inputPresupuestoFacturacion && seleccionadoContainerPresupuesto) {
            const pid = activeInvoice.presupuesto_activo_id;
            presupuestoSeleccionadoIdFacturacion = pid;

            if (!inputPresupuestoFacturacion.value) {
                inputPresupuestoFacturacion.value = 'Presupuesto #' + pid + (activeInvoice.orden_id ? ' (desde O.S.)' : '');
            }

            if (infoElementPresupuesto && !presupuestosDetallesCache.has(String(pid))) {
                infoElementPresupuesto.textContent = 'Presupuesto #' + pid + ' anexado';
            }
            if (clienteElementPresupuesto && activeInvoice.cliente_nombre && !presupuestosDetallesCache.has(String(pid))) {
                clienteElementPresupuesto.textContent = 'Cliente: ' + activeInvoice.cliente_nombre;
            }

            seleccionadoContainerPresupuesto.classList.remove('hidden');

            if (!presupuestosDetallesCache.has(String(pid))) {
                cargarDetallesPresupuestoAnexado(pid);
            }
        } else if (seleccionadoContainerPresupuesto) {
            if (!presupuestoSeleccionadoIdFacturacion) {
                seleccionadoContainerPresupuesto.classList.add('hidden');
            }
        }

        cartBody.innerHTML = activeInvoice.items.length === 0
            ? '<tr><td class="py-32 text-center text-slate-300 uppercase text-xs font-bold tracking-widest opacity-50"><i data-lucide="shopping-cart" class="w-16 h-16 mx-auto mb-4"></i> No hay items en esta factura</td></tr>'
            : activeInvoice.items.map((item, i) => `
                <tr class="group hover:bg-slate-50 transition-colors">
                    <td class="py-3 pr-4">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-slate-800 uppercase leading-none mb-1">${item.nombre}</span>
                                    ${item.tipo === 'SERVICIO' ? '<span class="text-[8px] bg-blue-100 text-blue-600 px-1 rounded font-black">SRV</span>' : ''}
                                </div>
                                <span class="text-[10px] text-slate-400 font-bold">${item.cantidad} x ${AppUtils.formatCurrency(item.precio)}</span>
                            </div>
                            <div class="flex items-center gap-6">
                                <span class="text-sm font-black text-navy-blue">${AppUtils.formatCurrency(item.precio * item.cantidad)}</span>
                                <button onclick="removeItem(${i})" class="text-slate-300 hover:text-red-500 transition-colors">
                                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            `).join('');

        const subtotal = activeInvoice.items.reduce((acc, item) => acc + (item.precio * item.cantidad), 0);
        const isIvaEnabled = activeInvoice.iva_activo !== false;
        const currentIvaRate = isIvaEnabled ? (IVA_PERCENT / 100) : 0;
        const ivaMonto = subtotal * currentIvaRate;
        const total = subtotal + ivaMonto;

        document.getElementById('pos-subtotal').textContent = AppUtils.formatCurrency(subtotal);

        const ivaDisplay = document.getElementById('pos-iva');
        if (ivaDisplay) ivaDisplay.textContent = AppUtils.formatCurrency(ivaMonto);

        const ivaPercentLabel = document.getElementById('pos-iva-percent-display');
        if (ivaPercentLabel) ivaPercentLabel.textContent = isIvaEnabled ? IVA_PERCENT.toFixed(0) : "0";

        document.getElementById('pos-total').textContent = AppUtils.formatCurrency(total);

        const saldoPendiente = total - (parseFloat(activeInvoice.pago_efectivo || 0) + parseFloat(activeInvoice.pago_transferencia || 0));
        if (displaySaldoPendiente) {
            displaySaldoPendiente.textContent = AppUtils.formatCurrency(saldoPendiente > 0 ? saldoPendiente : 0);

            const containerDeuda = document.getElementById('pos-container-deuda');
            if (containerDeuda) {
                if (saldoPendiente > 0) containerDeuda.classList.remove('opacity-40');
                else containerDeuda.classList.add('opacity-40');
            }
        }

        const containerDiagOs = document.getElementById('container-diag-os');
        const textDiagOs = document.getElementById('text-diag-os');
        const labelObs = document.getElementById('label-obs');

        if (activeInvoice.tipo_procedencia === 'OS' || activeInvoice.tipo_procedencia === 'TALLER') {
            if (activeInvoice.diagnostico_entrada) {
                containerDiagOs.classList.remove('hidden');
                textDiagOs.textContent = activeInvoice.diagnostico_entrada;
            } else {
                containerDiagOs.classList.add('hidden');
                textDiagOs.textContent = '';
            }
            labelObs.textContent = 'Observaciones de Salida (Factura)';
        } else {
            containerDiagOs.classList.add('hidden');
            textDiagOs.textContent = '';
            labelObs.textContent = 'Observaciones / Detalles del Trabajo';
        }

        if (posObservaciones && document.activeElement !== posObservaciones) {
            posObservaciones.value = activeInvoice.observaciones || '';
        }
        if (typeof updateObsPreview === 'function') updateObsPreview();
        lucide.createIcons();
    };

    btnProcessSale.addEventListener('click', async () => {
        const activeInvoice = openInvoices.find(i => i.id === activeInvoiceId);
        if (!activeInvoice) return;
        if (activeInvoice.items.length === 0) return AppUtils.showToast('La factura está vacía', 'warning');

        const originalContent = btnProcessSale.innerHTML;

        btnProcessSale.disabled = true;
        btnProcessSale.innerHTML = `
            <i data-lucide="loader" class="w-6 h-6 animate-spin"></i>
            <span>PROCESANDO VENTA...</span>
        `;
        if (window.lucide) lucide.createIcons();

        try {
            activeInvoice.placa = inputPlaca.value;
            activeInvoice.modelo = inputModelo.value;
            activeInvoice.cliente_id = inputCliente.value;
            activeInvoice.cliente_nombre = clientSearchInput ? clientSearchInput.value : '';
            activeInvoice.mecanico_id = inputMecanico.value;
            activeInvoice.observaciones = document.getElementById('pos-observaciones')?.value || '';
            activeInvoice.diagnostico_salida = activeInvoice.observaciones;
            activeInvoice.orden_id = activeInvoice.orden_id || displayFacturaId.dataset.ordenId || null;

            if (activeInvoice.placa && activeInvoice.placa.trim() !== "") {
                if (!activeInvoice.mecanico_id || activeInvoice.mecanico_id === "") {
                    AppUtils.showToast('Para órdenes de taller debe seleccionar un mecánico', 'warning');
                    btnProcessSale.disabled = false;
                    btnProcessSale.innerHTML = originalContent;
                    if (window.lucide) lucide.createIcons();
                    inputMecanico.focus();
                    return;
                }
            }

            activeInvoice.pago_efectivo = parseFloat(inputPagoEfectivo.value.replace(',', '.')) || 0;
            activeInvoice.pago_transferencia = parseFloat(inputPagoTransferencia.value.replace(',', '.')) || 0;

            const subtotal = activeInvoice.items.reduce((acc, item) => acc + (item.precio * item.cantidad), 0);
            const isIvaEnabled = activeInvoice.iva_activo === true;
            const ivaMonto = isIvaEnabled ? (subtotal * (IVA_PERCENT / 100)) : 0;

            activeInvoice.subtotal = subtotal;
            activeInvoice.iva_monto = ivaMonto;
            activeInvoice.total = subtotal + ivaMonto;
            activeInvoice.saldo_pendiente = activeInvoice.total - (activeInvoice.pago_efectivo + activeInvoice.pago_transferencia);

            const res = await postJSON(`${URLROOT}/facturacion/procesar`, activeInvoice);
            const data = await res.json();

            if (data.success) {
                AppUtils.confirmAction(
                    '¡Venta Exitosa!',
                    '¿Desea imprimir el comprobante de pago ahora?',
                    () => window.open(`${URLROOT}/facturacion/imprimir/${data.venta_id}`, '_blank'),
                    'success',
                    'Sí, Imprimir',
                    '#10b981',
                    'Cerrar'
                ).then(() => {
                    btnProcessSale.disabled = false;
                    btnProcessSale.innerHTML = originalContent;
                    if (window.lucide) lucide.createIcons();

                    const index = openInvoices.findIndex(inv => inv.id === activeInvoiceId);
                    openInvoices.splice(index, 1);

                    clearInputs();

                    activeInvoiceId = openInvoices.length > 0 ? openInvoices[0].id : null;
                    if (activeInvoiceId) renderInvoice();

                    const url = new URL(window.location);
                    url.searchParams.delete('orden_id');
                    window.history.replaceState({}, '', url);

                    loadInvoicesFromServer();
                });
            } else {
                throw new Error(data.mensaje || 'Error al procesar la venta');
            }
        } catch (error) {
            AppUtils.showToast(error.message, 'error');
            btnProcessSale.disabled = false;
            btnProcessSale.innerHTML = originalContent;
            if (window.lucide) lucide.createIcons();
        }
    });

    searchInput.addEventListener('input', async (e) => {
        const term = e.target.value.trim();
        if (term.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }

        const res = await fetch(`${URLROOT}/facturacion/buscarItems?term=${term}`);

        if (!res.ok) {
            AppUtils.showToast('Error al buscar items: ' + res.statusText, 'error');
            return;
        }
        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            AppUtils.showToast('Respuesta inesperada del servidor al buscar items. Verifique la consola para más detalles.', 'error');
            console.error('Respuesta del servidor no es JSON:', await res.text());
            return;
        }
        const items = await res.json();

        lastSearchResults = items;

        if (lastSearchResults.length > 0) {
            const html = lastSearchResults.map((item, index) => {
                const isAgotado = item.stock_disponible <= 0;
                return `<div class="p-4 hover:bg-slate-50 cursor-pointer border-b border-slate-100 flex justify-between items-center last:border-0 ${isAgotado ? 'opacity-50 pointer-events-none' : ''}" onclick="selectItemFromResults('${index}')">
                            <div>
                                <p class="font-bold text-sm uppercase">${item.nombre}</p>
                                <p class="text-[10px] ${item.stock_disponible <= 5 && item.stock_disponible > 0 ? 'text-cat-yellow font-black' : (item.stock_disponible <= 0 ? 'text-error-red font-black' : 'text-slate-400')} uppercase">
                                    Disponible: ${item.stock_disponible} unidades
                                </p>
                            </div>
                            <span class="font-bold text-navy-blue text-sm">${AppUtils.formatCurrency(parseFloat(item.precio))}</span>
                        </div>`;
            }).join('');

            searchResults.innerHTML = html;
            searchResults.classList.remove('hidden');
        } else {
            searchResults.innerHTML = '<p class="p-3 text-center text-slate-400 text-xs uppercase">Sin stock disponible</p>';
            searchResults.classList.remove('hidden');
        }
    });

    document.getElementById('btn-new-invoice').addEventListener('click', () => {
        initNewInvoice(true);
    });

    document.addEventListener('click', (e) => {
        if (searchResults && !searchResults.contains(e.target) && e.target !== searchInput)
            searchResults.classList.add('hidden');
        if (clientSearchResults && !clientSearchResults.contains(e.target) && e.target !== clientSearchInput)
            clientSearchResults.classList.add('hidden');
    });

    if (typeof PollingManager !== 'undefined') {
        PollingManager.register('facturacion-drafts', loadInvoicesFromServer, 30000);
    } else {
        loadInvoicesFromServer();
        setInterval(loadInvoicesFromServer, 30000);
    }

    loadClients();

    async function verificarOrdenInicial() {
        const urlParams = new URLSearchParams(window.location.search);
        const ordenId = urlParams.get('orden_id') || displayFacturaId.dataset.ordenId;

        if (ordenId) {
            AppUtils.showToast('ORDEN LISTA PARA FACTURAR', 'success');

            try {
                const resp = await fetch(`${URLROOT}/facturacion/obtenerPorOrden/${ordenId}`);
                if (!resp.ok) return;

                const res = await resp.json();
                if (res.success && res.data) {
                    activeInvoiceId = 'FAC-' + String(res.data.id).padStart(3, '0');
                    await loadInvoicesFromServer();
                }
            } catch (e) {
                console.error("Error al cargar orden inicial:", e);
            }
        }
    }

    verificarOrdenInicial();
});