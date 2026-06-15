/**
 * Lógica de Facturación con Gestión de Colas
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
    const inputClienteNombre = document.getElementById('cliente_nombre'); // Asegurarse de que este input exista en el HTML
    const clientSearchInput = document.getElementById('pos-client-search');
    const clientSearchResults = document.getElementById('pos-client-results');
    const posObservaciones = document.getElementById('pos-observaciones');
    const inputPagoEfectivo = document.getElementById('pos-pago-efectivo');
    const inputPagoTransferencia = document.getElementById('pos-pago-transferencia');
    const displaySaldoPendiente = document.getElementById('pos-saldo-pendiente');

    // Usamos la constante global IVA_RATE inyectada desde el header (SQL)
    const IVA_PERCENT = (typeof IVA_RATE !== 'undefined') ? (IVA_RATE * 100) : 0;

    let syncTimeout = null;

    // Estado de la factura actual
    let openInvoices = [];
    let activeInvoiceId = null; // Usamos ID en lugar de índice para evitar saltos de datos

    let selectedItemFromSearch = null;
    let lastSearchResults = []; // Almacén temporal para evitar errores de sintaxis en HTML
    let lastClientResults = [];

    // Escuchar cuando el usuario global esté cargado para refrescar permisos
    document.addEventListener('userLoaded', () => {
        renderQueue();
        renderInvoice();
    });

    const loadInvoicesFromServer = async () => {
        try {
            const res = await fetch(`${URLROOT}/facturacion/listarBorradores`);
            const drafts = await res.json();

            // Preservar facturas locales que aún no tienen id_db (no se han guardado en el servidor)
            // Esto evita que el refresco automático borre lo que el usuario está empezando a escribir
            const localInvoices = openInvoices.filter(inv => !inv.id_db);

            const serverInvoices = drafts.map(d => {
                // Intentar preservar el mecánico local si el servidor lo envía vacío (borradores sin ítems)
                const existingInv = openInvoices.find(inv => inv.id_db === d.id);
                const preservedMecanicoId = (existingInv && !d.mecanico_id) ? existingInv.mecanico_id : d.mecanico_id;

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
                    observaciones: d.observaciones || ''
                };
            });

            openInvoices = [...serverInvoices, ...localInvoices];

            // Validar si la factura activa fue cerrada o eliminada por otro usuario
            if (activeInvoiceId && activeInvoiceId.startsWith('TKT-')) {
                const stillExists = openInvoices.some(inv => inv.id === activeInvoiceId);
                if (!stillExists) {
                    activeInvoiceId = null;
                    clearInputs();
                }
            }

            if (!activeInvoiceId && openInvoices.length > 0) {
                // Intentar capturar ID desde la URL (si viene del dashboard)
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

    /**
     * Carga la lista de clientes desde la API y llena el select
     */
    const loadClients = async () => {
        try {
            const res = await fetch(`${URLROOT}/clientes/listar`);
            if (!res.ok) return;
            const result = await res.json();
            const clientes = result.data || [];

            // Limpiar y establecer la opción por defecto como "SIN CLIENTE"
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

    /**
     * Registro rápido de cliente desde la pantalla de facturación
     */
    btnQuickClient.addEventListener('click', async () => {
        const { value: formValues } = await Swal.fire({
            title: 'REGISTRO RÁPIDO DE CLIENTE',
            html:
                '<input id="swal-input1" class="swal2-input" placeholder="NIT / CÉDULA">' +
                '<input id="swal-input2" class="swal2-input" placeholder="NOMBRE COMPLETO">',
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'REGISTRAR',
            confirmButtonColor: '#10b981',
            preConfirm: () => {
                return [
                    document.getElementById('swal-input1').value.trim(),
                    document.getElementById('swal-input2').value.trim()
                ]
            }
        });

        if (formValues && formValues[0] && formValues[1]) {
            const res = await fetch(`${URLROOT}/clientes/guardar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: formValues[0],
                    nombre: formValues[1],
                    email: '', telefono: '', direccion: ''
                })
            });
            const data = await res.json();
            if (data.success) {
                // Tras registro rápido, actualizamos el buscador
                inputCliente.value = formValues[0]; // Seleccionar automáticamente
                // 1. Añadir el nuevo cliente como una opción al select oculto
                const newOption = document.createElement('option');
                newOption.value = formValues[0]; // ID del cliente
                newOption.textContent = formValues[1]; // Nombre del cliente
                inputCliente.appendChild(newOption);

                // 2. Seleccionar automáticamente el nuevo cliente en el select oculto
                inputCliente.value = formValues[0];
                // 3. Actualizar el input de búsqueda visible con el nombre del cliente
                if (clientSearchInput) clientSearchInput.value = formValues[1];
                updateActiveData('cliente_id', formValues[0]);
                // 4. Actualizar el borrador activo con el nuevo cliente y forzar sincronización
                updateActiveData('cliente_id', formValues[0]); // Esto también llama a debounceSync
                openInvoices.find(i => i.id === activeInvoiceId).cliente_nombre = formValues[1]; // Actualizar nombre en el objeto local
                AppUtils.showToast('Cliente registrado');
            } else {
                AppUtils.showToast(data.mensaje, 'error');
            }
        }
    });

    const initNewInvoice = async (forceSave = false) => {
        // Detectar datos inyectados por PHP (desde Orden de Servicio) antes de cualquier limpieza
        const domPlaca = inputPlaca.value;
        const domModelo = inputModelo.value;
        const domClienteId = inputCliente.value;
        const domClienteNombre = clientSearchInput ? clientSearchInput.value : '';
        const domMecanicoId = inputMecanico.value;
        const domObservaciones = document.getElementById('pos-observaciones')?.value || '';

        // 1. Solo limpiar inputs físicamente si es una factura nueva manual (clic en "Nueva Factura")
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

        // Detectar si hay una Orden de Servicio cargada en el DOM por PHP
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
            observaciones: forceSave ? '' : domObservaciones
        };

        // Si no es un guardado forzado (clic en "Nueva Factura"), solo creamos el objeto localmente.
        // Esto evita llenar la base de datos con borradores vacíos al solo entrar a la vista.
        if (!forceSave) {
            // Solo añadir si no hay ya una factura local vacía para evitar duplicidad de pestañas "limpias"
            if (!openInvoices.some(inv => !inv.id_db)) {
                openInvoices.push(invData);
            }
            activeInvoiceId = invData.id;
            renderQueue();
            renderInvoice();
            return;
        }

        // Enviar a DB inmediatamente para obtener ID real y evitar LocalStorage
        try {
            const res = await fetch(`${URLROOT}/facturacion/sincronizarBorrador`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(invData)
            });
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

    // Listeners para guardar metadatos en tiempo real
    inputPlaca.addEventListener('input', (e) => {
        const val = e.target.value.toUpperCase();
        updateActiveData('placa', val);

        // Cambio dinámico de procedencia: Si hay placa, es Taller. Si no, Mostrador.
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

    // Sincronización inteligente de observaciones (Salida/Taller)
    if (posObservaciones) {
        posObservaciones.addEventListener('input', (e) => {
            // Actualiza el objeto local y activa el debounce para guardar en DB mientras escribes
            updateActiveData('observaciones', e.target.value.toUpperCase());
        });

        posObservaciones.addEventListener('blur', () => {
            // Al perder el foco del campo, forzamos el guardado inmediato en DB
            syncActiveInvoice();
        });
    }

    inputMecanico.addEventListener('change', (e) => {
        updateActiveData('mecanico_id', e.target.value);
        renderQueue();
        renderInvoice();
    });

    // Listeners para captura de pagos
    inputPagoEfectivo?.addEventListener('input', (e) => {
        updateActiveData('pago_efectivo', parseFloat(e.target.value.replace(',', '.')) || 0);
        renderInvoice();
    });

    inputPagoTransferencia?.addEventListener('input', (e) => {
        updateActiveData('pago_transferencia', parseFloat(e.target.value.replace(',', '.')) || 0);
        renderInvoice();
    });

    /**
     * Buscador de clientes en tiempo real
     */
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
                clientSearchResults.innerHTML = lastClientResults.map((c, i) => {
                    return `
                    <div class="p-4 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0 flex justify-between items-center group transition-colors" 
                         onclick="selectClientFromResults('${i}')">
                        <div>
                            <p class="font-black text-xs uppercase text-navy-blue leading-none mb-1 group-hover:text-black">${c.nombre}</p>
                            <p class="text-[10px] text-slate-400 font-mono italic font-bold">CC/NIT: ${c.id}</p>
                        </div>
                        <i data-lucide="user-plus" class="w-4 h-4 text-slate-300 group-hover:text-neon-green"></i>
                    </div>`;
                }).join('');
                clientSearchResults.classList.remove('hidden');
                if (window.lucide) lucide.createIcons();
            } else {
                clientSearchResults.innerHTML = '<p class="p-3 text-center text-slate-400 text-xs uppercase">No encontrado</p>';
                clientSearchResults.classList.remove('hidden');
            }
        });
    }

    window.selectClientFromResults = (index) => {
        const client = lastClientResults[index];
        if (client) {
            // Asegurar que el ID existe en el select oculto para que el valor se mantenga
            let option = inputCliente.querySelector(`option[value="${client.id}"]`);
            if (!option) {
                option = document.createElement('option');
                option.value = client.id;
                option.textContent = client.nombre;
                inputCliente.appendChild(option);
            }

            inputCliente.value = client.id;
            if (clientSearchInput) clientSearchInput.value = client.nombre;
            clientSearchResults.classList.add('hidden');
            const inv = openInvoices.find(i => i.id === activeInvoiceId);
            if (inv) inv.cliente_nombre = client.nombre;
            updateActiveData('cliente_id', client.id);
            AppUtils.showToast('Cliente vinculado');
        }
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
            // Definir color y etiqueta según procedencia
            let badgeClass = 'bg-amber-100 text-amber-700 border-amber-200'; // Default: MOSTRADOR
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

        // Si la factura ya existe en el servidor, pedir confirmación y borrar en DB
        if (inv.id_db) {
            AppUtils.confirmAction('¿Eliminar borrador?', 'Esta acción cancelará la orden y liberará el stock.', async () => {
                const res = await fetch(`${URLROOT}/facturacion/eliminarBorrador/${inv.id_db}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
                });
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
        if (inputClienteNombre) inputClienteNombre.value = ""; // Limpiar el campo de nombre del cliente
        if (clientSearchInput) clientSearchInput.value = "";

        const obsField = document.getElementById('pos-observaciones');
        if (obsField) obsField.value = "";

        const obsPreview = document.getElementById('pos-obs-preview');
        if (obsPreview) obsPreview.classList.add('hidden');

        // --- Limpiar campos de observación inteligente ---
        const containerDiagOs = document.getElementById('container-diag-os');
        const textDiagOs = document.getElementById('text-diag-os');
        const labelObs = document.getElementById('label-obs');
        if (containerDiagOs) containerDiagOs.classList.add('hidden');
        if (textDiagOs) textDiagOs.textContent = '';
        if (labelObs) labelObs.textContent = 'Observaciones / Detalles del Trabajo'; // Reset a texto por defecto

        // Limpiar campos de pago y saldos (Vista Previa)
        if (inputPagoEfectivo) inputPagoEfectivo.value = 0;
        if (inputPagoTransferencia) inputPagoTransferencia.value = 0;
        if (displaySaldoPendiente) displaySaldoPendiente.textContent = "$0.00";

        if (inputMecanico && !inputMecanico.disabled) inputMecanico.value = "";

        cartBody.innerHTML = '<tr><td class="py-32 text-center text-slate-300 uppercase text-xs font-bold tracking-widest opacity-50"><i data-lucide="shopping-cart" class="w-16 h-16 mx-auto mb-4"></i> No hay factura activa</td></tr>';
        document.getElementById('pos-subtotal').textContent = "$0.00";
        document.getElementById('pos-iva').textContent = "$0.00";
        document.getElementById('pos-total').textContent = "$0.00";
        lucide.createIcons();
    };

    /**
     * Sincroniza la factura activa con la base de datos (Borrador/PENDIENTE)
     * Esto reserva el stock para que otros usuarios no puedan vender lo mismo.
     */
    const syncActiveInvoice = async (force = false) => {
        if (!activeInvoiceId) return;
        const inv = openInvoices.find(i => i.id === activeInvoiceId);
        if (!inv) return;

        // Solo leer de los inputs si NO es un guardado forzado (nueva factura)
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

        // Calcular totales para asegurar persistencia de IVA 0 si el switch está apagado
        const subtotal = inv.items.reduce((acc, item) => acc + (item.precio * item.cantidad), 0);
        const isIvaEnabled = inv.iva_activo === true;
        const ivaMonto = isIvaEnabled ? (subtotal * (IVA_PERCENT / 100)) : 0;
        const total = subtotal + ivaMonto;

        // Calcular saldo pendiente
        const pef = parseFloat(inv.pago_efectivo || 0);
        const ptra = parseFloat(inv.pago_transferencia || 0);
        const pendiente = total - (pef + ptra);

        inv.subtotal = subtotal;
        inv.iva_monto = ivaMonto;
        inv.total = total;
        inv.saldo_pendiente = pendiente > 0 ? pendiente : 0;

        // Evitar sincronizar facturas que no tienen contenido relevante (evita filas vacías en DB)
        const hasContent = inv.items.length > 0 || inv.placa !== '' || inv.modelo !== '' || inv.cliente_id !== '' || inv.mecanico_id !== '' || pef > 0 || ptra > 0;
        if (!hasContent && !inv.id_db && !force) {
            return;
        }

        try {
            const res = await fetch(`${URLROOT}/facturacion/sincronizarBorrador`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(inv)
            });

            if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);

            // Validar que la respuesta sea JSON antes de parsear
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

                // Si es la primera vez que se guarda en DB, convertimos el ID temporal PROV- en un ID TKT- real
                if (isFirstSync) {
                    const oldId = inv.id;
                    inv.id = 'FAC-' + String(data.venta_id).padStart(3, '0');
                    if (activeInvoiceId === oldId) activeInvoiceId = inv.id;

                    renderQueue();
                    renderInvoice(); // Actualiza el número de factura visible en el encabezado
                }
            }
        } catch (error) {
            console.error("Error sincronizando con el servidor:", error);
        }
    };

    /**
     * Lógica de Artículos y Servicios
     */
    window.selectItemForAdd = (item) => {
        if (!item) return;
        selectedItemFromSearch = item;
        searchInput.value = item.nombre;
        searchResults.classList.add('hidden');
        inputQty.focus();
    };

    window.selectItemFromResults = (index) => {
        const item = lastSearchResults[parseInt(index)]; // Aseguramos que el índice sea un número
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
        syncActiveInvoice(); // Sincronizar tras añadir item
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
        syncActiveInvoice(); // Sincronizar tras añadir servicio
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
        displayFacturaId.dataset.ordenId = activeInvoice.orden_id || ''; // Actualizar el data attribute
        inputPlaca.value = activeInvoice.placa;
        inputModelo.value = activeInvoice.modelo;
        inputMecanico.value = activeInvoice.mecanico_id || '';

        // Priorizar el nombre del mecánico seleccionado en el encabezado
        const selectedMecanicoText = inputMecanico.options[inputMecanico.selectedIndex]?.text;
        const mecanicoName = (activeInvoice.mecanico_id && selectedMecanicoText) ? selectedMecanicoText.split('(')[0].trim() : null;
        document.getElementById('pos-user-name').textContent = mecanicoName || activeInvoice.usuario_nombre || '---';

        // Si hay un cliente_id pero no está en el select, agregamos la opción temporalmente
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

        // Sincronizar siempre el nombre del cliente con el borrador activo para evitar residuos visuales
        if (clientSearchInput) clientSearchInput.value = activeInvoice.cliente_nombre || '';

        if (inputIvaToggle) {
            inputIvaToggle.checked = (activeInvoice.iva_activo !== false);
        }

        // Cargar valores de pago en los inputs (evitar sobrescribir mientras el usuario escribe la coma)
        if (inputPagoEfectivo && document.activeElement !== inputPagoEfectivo) {
            inputPagoEfectivo.value = activeInvoice.pago_efectivo || 0;
        }
        if (inputPagoTransferencia && document.activeElement !== inputPagoTransferencia) {
            inputPagoTransferencia.value = activeInvoice.pago_transferencia || 0;
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

        // Verificación de estado del IVA (si es null o true, se cobra. Si es false, no)
        const isIvaEnabled = activeInvoice.iva_activo !== false;
        const currentIvaRate = isIvaEnabled ? (IVA_PERCENT / 100) : 0;

        const ivaMonto = subtotal * currentIvaRate;
        const total = subtotal + ivaMonto;

        document.getElementById('pos-subtotal').textContent = AppUtils.formatCurrency(subtotal);

        // Forzar actualización del monto del IVA
        const ivaDisplay = document.getElementById('pos-iva');
        if (ivaDisplay) ivaDisplay.textContent = AppUtils.formatCurrency(ivaMonto);

        // Actualizar el porcentaje visual (ej: "19" o "0")
        const ivaPercentLabel = document.getElementById('pos-iva-percent-display');
        if (ivaPercentLabel) ivaPercentLabel.textContent = isIvaEnabled ? IVA_PERCENT.toFixed(0) : "0";

        document.getElementById('pos-total').textContent = AppUtils.formatCurrency(total);

        // Actualizar visualización de deuda (Saldo Pendiente)
        const saldoPendiente = total - (parseFloat(activeInvoice.pago_efectivo || 0) + parseFloat(activeInvoice.pago_transferencia || 0));
        if (displaySaldoPendiente) {
            displaySaldoPendiente.textContent = AppUtils.formatCurrency(saldoPendiente > 0 ? saldoPendiente : 0);

            const containerDeuda = document.getElementById('pos-container-deuda');
            if (containerDeuda) {
                if (saldoPendiente > 0) containerDeuda.classList.remove('opacity-40');
                else containerDeuda.classList.add('opacity-40');
            }
        }

        // --- Lógica de Observaciones Inteligente ---
        const containerDiagOs = document.getElementById('container-diag-os');
        const textDiagOs = document.getElementById('text-diag-os');
        const labelObs = document.getElementById('label-obs');

        if (activeInvoice.tipo_procedencia === 'OS' || activeInvoice.tipo_procedencia === 'TALLER') {
            // Si es una OS o Taller, mostrar el diagnóstico de entrada si existe
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
        // Solo actualizar el campo de observaciones si el usuario NO está escribiendo en él
        if (posObservaciones && document.activeElement !== posObservaciones) {
            posObservaciones.value = activeInvoice.observaciones || '';
        }
        updateObsPreview(); // Actualizar la vista previa de observaciones
        lucide.createIcons();
    };

    btnProcessSale.addEventListener('click', async () => {
        const activeInvoice = openInvoices.find(i => i.id === activeInvoiceId);
        if (!activeInvoice) return;
        if (activeInvoice.items.length === 0) return AppUtils.showToast('La factura está vacía', 'warning');

        // 1. Guardar el contenido original para restaurarlo en caso de error
        const originalContent = btnProcessSale.innerHTML;

        // 2. Deshabilitar el botón y mostrar el Spinner
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

            // En Venta de Repuestos (sin placa), el mecánico es opcional
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

            // Asegurar que los montos de pago se capturen incluso si no hubo evento 'input'
            activeInvoice.pago_efectivo = parseFloat(inputPagoEfectivo.value.replace(',', '.')) || 0;
            activeInvoice.pago_transferencia = parseFloat(inputPagoTransferencia.value.replace(',', '.')) || 0;

            // Recalcular finales antes de procesar el cierre
            const subtotal = activeInvoice.items.reduce((acc, item) => acc + (item.precio * item.cantidad), 0);
            const isIvaEnabled = activeInvoice.iva_activo === true;
            const ivaMonto = isIvaEnabled ? (subtotal * (IVA_PERCENT / 100)) : 0;

            activeInvoice.subtotal = subtotal;
            activeInvoice.iva_monto = ivaMonto;
            activeInvoice.total = subtotal + ivaMonto;
            activeInvoice.saldo_pendiente = activeInvoice.total - (activeInvoice.pago_efectivo + activeInvoice.pago_transferencia);

            const res = await fetch(`${URLROOT}/facturacion/procesar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(activeInvoice)
            });
            const data = await res.json();

            if (data.success) {
                // Preguntar si desea imprimir después del éxito
                AppUtils.confirmAction(
                    '¡Venta Exitosa!',
                    '¿Desea imprimir el comprobante de pago ahora?',
                    () => window.open(`${URLROOT}/facturacion/imprimir/${data.venta_id}`, '_blank'),
                    'success',
                    'Sí, Imprimir',
                    '#10b981',
                    'Cerrar'
                ).then(() => {
                    // Rehabilitar el botón y restaurar el contenido original independientemente de la elección
                    btnProcessSale.disabled = false;
                    btnProcessSale.innerHTML = originalContent;
                    if (window.lucide) lucide.createIcons();

                    const index = openInvoices.findIndex(inv => inv.id === activeInvoiceId);
                    openInvoices.splice(index, 1);

                    // Limpiar SIEMPRE el formulario para evitar residuos visuales de OS
                    clearInputs();

                    activeInvoiceId = openInvoices.length > 0 ? openInvoices[0].id : null;
                    if (activeInvoiceId) renderInvoice();

                    // Limpiar parámetros de la URL para evitar que se recargue la O.S. al refrescar
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

    /**
     * Buscador en tiempo real
     */
    searchInput.addEventListener('input', async (e) => {
        const term = e.target.value.trim();
        if (term.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }

        const res = await fetch(`${URLROOT}/facturacion/buscarItems?term=${term}`);

        // 1. Verificar si la respuesta fue exitosa (código 200-299)
        if (!res.ok) {
            AppUtils.showToast('Error al buscar items: ' + res.statusText, 'error');
            return;
        }
        // 2. Verificar si el Content-Type es JSON
        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            AppUtils.showToast('Respuesta inesperada del servidor al buscar items. Verifique la consola para más detalles.', 'error');
            console.error('Respuesta del servidor no es JSON:', await res.text()); // Imprime la respuesta HTML en consola
            return;
        }
        const items = await res.json(); // Ahora esto solo se ejecutará si es JSON válido

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
        // Al hacer clic, forzamos que se guarde en la base de datos como pendiente
        initNewInvoice(true);
    });

    document.addEventListener('click', (e) => {
        if (searchResults && !searchResults.contains(e.target) && e.target !== searchInput)
            searchResults.classList.add('hidden');
        if (clientSearchResults && !clientSearchResults.contains(e.target) && e.target !== clientSearchInput)
            clientSearchResults.classList.add('hidden');
    });

    loadInvoicesFromServer();

    // Polling: Actualizar cola de facturas cada 10 segundos para ver lo de otros usuarios
    setInterval(loadInvoicesFromServer, 10000);

    /**
     * Verifica si se ha pasado un ID de orden por URL para cargarla automáticamente
     */
    async function verificarOrdenInicial() {
        const urlParams = new URLSearchParams(window.location.search);
        const ordenId = urlParams.get('orden_id') || displayFacturaId.dataset.ordenId;

        if (ordenId) {
            // Notificamos inmediatamente que la integración con el taller fue exitosa
            AppUtils.showToast('ORDEN LISTA PARA FACTURAR', 'success');

            try {
                const resp = await fetch(`${URLROOT}/facturacion/obtenerPorOrden/${ordenId}`);

                // Si el servidor responde 404 o error, significa que no hay borrador previo.
                // En este caso, no hacemos nada y dejamos que initNewInvoice use los datos del DOM.
                if (!resp.ok) return;

                const res = await resp.json();

                if (res.success && res.data) {
                    // Al detectar la orden, seleccionamos automáticamente su borrador en el POS
                    activeInvoiceId = 'FAC-' + String(res.data.id).padStart(3, '0');
                    await loadInvoicesFromServer(); // Sincronizar para asegurar visibilidad inmediata
                }
            } catch (e) {
                console.error("Error al cargar orden inicial:", e);
            }
        }
    }

    verificarOrdenInicial();
});