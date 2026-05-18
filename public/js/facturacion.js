/**
 * Lógica de Facturación con Gestión de Colas
 */
document.addEventListener('DOMContentLoaded', () => {
    const inputPlaca = document.getElementById('pos-placa');
    const inputModelo = document.getElementById('pos-modelo');
    const inputCliente = document.getElementById('pos-cliente-id');
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

    const IVA_PERCENT = parseFloat(document.getElementById('pos-iva-percent').textContent) || 0;

    let syncTimeout = null;

    // Estado de la factura actual
    let openInvoices = [];
    let activeInvoiceId = null; // Usamos ID en lugar de índice para evitar saltos de datos

    let selectedItemFromSearch = null;
    let lastSearchResults = []; // Almacén temporal para evitar errores de sintaxis en HTML

    /**
     * Persistencia: Guardar el ID activo para que no se pierda al recargar
     */
    const saveInvoicesToLocal = () => {
        localStorage.setItem('pos_active_invoice_id', activeInvoiceId);
    };

    const loadInvoicesFromServer = async () => {
        try {
            const res = await fetch(`${URLROOT}/facturacion/listarBorradores`);
            const drafts = await res.json();

            if (drafts.length > 0) {
                // Preservar facturas locales que tienen cambios pero aún no tienen id_db
                // Ahora protegemos si tiene items O si el usuario empezó a escribir placa/modelo
                const localOnly = openInvoices.filter(inv => !inv.id_db && (inv.items.length > 0 || inv.placa.trim() !== '' || inv.modelo.trim() !== ''));

                openInvoices = drafts.map(d => ({
                    id: 'TKT-' + d.id,
                    id_db: d.id,
                    placa: d.placa || '',
                    modelo: d.modelo_vehiculo || '',
                    cliente_id: d.cliente_id || '',
                    items: d.items || []
                })).concat(localOnly);

                // Restaurar la factura activa por ID
                const savedId = localStorage.getItem('pos_active_invoice_id');
                if (savedId && openInvoices.find(inv => inv.id === savedId)) {
                    activeInvoiceId = savedId;
                } else if (openInvoices.length > 0) {
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
            const clientes = await res.json();

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

    const initNewInvoice = async (forceSave = false) => {
        // 1. Limpiar inputs físicamente ANTES de crear el objeto para no heredar datos
        inputPlaca.value = '';
        inputModelo.value = '';
        inputCliente.value = '';
        selectedItemFromSearch = null;
        searchInput.value = '';

        const newInvoice = {
            id: 'PROV-' + Math.floor(Math.random() * 9000 + 1000),
            id_db: null, // Guardará el ID real de la DB para actualizar el borrador
            placa: '',
            modelo: '',
            cliente_id: '',
            items: []
        };

        openInvoices.push(newInvoice);
        activeInvoiceId = newInvoice.id;

        // Si se invoca manualmente (clic en botón), forzamos la creación en DB
        if (forceSave) {
            await syncActiveInvoice(true);
        }

        saveInvoicesToLocal();
        renderQueue();
        renderInvoice();
    };

    // Listeners para guardar metadatos en tiempo real
    inputPlaca.addEventListener('input', (e) => {
        updateActiveData('placa', e.target.value.toUpperCase());
        renderQueue(); 
    });

    inputModelo.addEventListener('input', (e) => {
        updateActiveData('modelo', e.target.value.toUpperCase());
        renderQueue();
    });

    inputCliente.addEventListener('change', (e) => {
        updateActiveData('cliente_id', e.target.value);
    });

    const updateActiveData = (field, value) => {
        const inv = openInvoices.find(i => i.id === activeInvoiceId);
        if (inv) {
            inv[field] = value;
            saveInvoicesToLocal();
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

        container.innerHTML = openInvoices.map((inv, index) => `
            <div onclick="switchInvoice('${inv.id}')" class="flex-shrink-0 px-3 py-1.5 rounded-lg border-2 transition-all cursor-pointer flex items-center gap-3 ${inv.id === activeInvoiceId ? 'border-neon-green bg-white shadow-sm' : 'border-transparent bg-slate-100 opacity-60 hover:opacity-100'}">
                <div class="flex flex-col">
                    <span class="text-[9px] font-black text-navy-blue">${inv.id}</span>
                    <span class="text-[10px] font-bold uppercase truncate max-w-[80px]">${inv.modelo || 'SIN DESC.'}</span>
                </div>
                <button onclick="closeInvoice(${index}, event)" class="text-slate-400 hover:text-red-500"><i data-lucide="x" class="w-3 h-3"></i></button>
            </div>
        `).join('');
        lucide.createIcons();
    };

    const debounceSync = () => {
        clearTimeout(syncTimeout);
        syncTimeout = setTimeout(syncActiveInvoice, 1000);
    };

    window.switchInvoice = (id) => {
        activeInvoiceId = id;
        saveInvoicesToLocal();
        renderQueue();
        renderInvoice();
    };

    window.closeInvoice = (index, event) => {
        event.stopPropagation();
        const idToDelete = openInvoices[index].id;
        openInvoices.splice(index, 1);

        if (openInvoices.length === 0) {
            activeInvoiceId = null;
            clearInputs();
        } else if (activeInvoiceId === idToDelete) {
            activeInvoiceId = openInvoices[0].id;
        }

        saveInvoicesToLocal();
        renderQueue();
        renderInvoice();
    };

    const clearInputs = () => {
        displayFacturaId.textContent = "---";
        inputPlaca.value = "";
        inputModelo.value = "";
        inputCliente.value = "";
        cartBody.innerHTML = '<tr><td class="py-32 text-center text-slate-300 uppercase text-xs font-bold tracking-widest opacity-50"><i data-lucide="shopping-cart" class="w-16 h-16 mx-auto mb-4"></i> No hay factura activa</td></tr>';
        document.getElementById('pos-subtotal').textContent = "$0.00";
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
            inv.cliente_id = inputCliente.value;
        }

        // Evitar sincronizar facturas que no tienen contenido relevante (evita filas vacías en DB)
        const hasContent = inv.items.length > 0 || inv.placa !== '' || inv.modelo !== '' || inv.cliente_id !== '';
        if (!hasContent && !inv.id_db && !force) {
            return;
        }

        try {
            const res = await fetch(`${URLROOT}/facturacion/sincronizarBorrador`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
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
                inv.id_db = data.venta_id; // Actualizamos el ID de la base de datos
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
            cantidad: qty,
            tipo: 'PRODUCTO'
        });

        selectedItemFromSearch = null;
        searchInput.value = '';
        inputQty.value = 1;
        searchResults.classList.add('hidden');
        renderInvoice();
        saveInvoicesToLocal();
        syncActiveInvoice(); // Sincronizar tras añadir item
    });

    btnAddService.addEventListener('click', () => {
        if (!activeInvoiceId) return AppUtils.showToast('Cree una factura primero', 'warning');
        const nombre = inputServicioNombre.value.trim();
        const precio = parseFloat(inputServicioPrecio.value);
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
        saveInvoicesToLocal();
        syncActiveInvoice(); // Sincronizar tras añadir servicio
    });

    window.removeItem = (index) => {
        const activeInvoice = openInvoices.find(i => i.id === activeInvoiceId);
        if (activeInvoice) {
            activeInvoice.items.splice(index, 1);
            renderInvoice();
            saveInvoicesToLocal();
            syncActiveInvoice();
        }
    };

    const renderInvoice = () => {
        const activeInvoice = openInvoices.find(i => i.id === activeInvoiceId);
        if (!activeInvoice) return;

        displayFacturaId.textContent = activeInvoice.id;
        inputPlaca.value = activeInvoice.placa;
        inputModelo.value = activeInvoice.modelo;
        inputCliente.value = activeInvoice.cliente_id;

        cartBody.innerHTML = activeInvoice.items.length === 0
            ? '<tr><td class="py-32 text-center text-slate-300 uppercase text-xs font-bold tracking-widest opacity-50"><i data-lucide="shopping-cart" class="w-16 h-16 mx-auto mb-4"></i> No hay items en esta factura</td></tr>'
            : activeInvoice.items.map((item, i) => `
                <tr class="group hover:bg-slate-50 transition-colors">
                    <td class="py-3 pr-4">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-800 uppercase leading-none mb-1">${item.nombre}</span>
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
        const total = subtotal * (1 + (IVA_PERCENT / 100));

        document.getElementById('pos-subtotal').textContent = AppUtils.formatCurrency(subtotal);
        document.getElementById('pos-total').textContent = AppUtils.formatCurrency(total);
        lucide.createIcons();
    };

    btnProcessSale.addEventListener('click', async () => {
        const activeInvoice = openInvoices.find(i => i.id === activeInvoiceId);
        if (!activeInvoice) return;
        if (activeInvoice.items.length === 0) return AppUtils.showToast('La factura está vacía', 'warning');

        activeInvoice.placa = inputPlaca.value;
        activeInvoice.modelo = inputModelo.value;
        activeInvoice.cliente_id = inputCliente.value;

        const res = await fetch(`${URLROOT}/facturacion/procesar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(activeInvoice)
        });
        const data = await res.json();
        if (data.success) {
            AppUtils.showAlert('Éxito', 'Factura procesada correctamente', 'success');
            
            const index = openInvoices.findIndex(inv => inv.id === activeInvoiceId);
            openInvoices.splice(index, 1);
            
            activeInvoiceId = openInvoices.length > 0 ? openInvoices[0].id : null;
            
            if (!activeInvoiceId) clearInputs();
            saveInvoicesToLocal();
            // Forzamos recarga del servidor para limpiar borradores
            loadInvoicesFromServer();
        } else {
            AppUtils.showToast(data.mensaje, 'error');
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
        if (!searchResults.contains(e.target) && e.target !== searchInput) searchResults.classList.add('hidden');
    });

    loadClients();
    loadInvoicesFromServer();

    // Polling: Actualizar cola de facturas cada 10 segundos para ver lo de otros usuarios
    setInterval(loadInvoicesFromServer, 10000);
});