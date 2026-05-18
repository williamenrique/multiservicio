/**
 * Lógica Reestructurada: Gestión Multi-factura y Punto de Venta
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
    const queueContainer = document.getElementById('pos-queue-list');
    const btnProcessSale = document.getElementById('btn-process-sale');

    const IVA_PERCENT = parseFloat(document.getElementById('pos-iva-percent').textContent) || 0;

    // Estado de la factura actual
    let activeInvoice = {
        id: null,
        placa: '',
        modelo: '',
        cliente_id: '',
        items: []
    };

    let selectedItemFromSearch = null;
    let lastSearchResults = []; // Almacén temporal para evitar errores de sintaxis en HTML

    /**
     * Gestión de Colas
     */
    const initNewInvoice = () => {
        activeInvoice = {
            id: 'FAC-' + Date.now().toString().slice(-6),
            placa: '',
            modelo: '',
            cliente_id: '',
            items: []
        };
        renderInvoice();
    };

    const saveToQueue = async () => {
        activeInvoice.placa = inputPlaca.value.toUpperCase();
        activeInvoice.modelo = inputModelo.value.toUpperCase();
        activeInvoice.cliente_id = inputCliente.value;

        let drafts = await AppUtils.loadData('drafts_db');
        const index = drafts.findIndex(d => d.id === activeInvoice.id);
        if (index !== -1) drafts[index] = activeInvoice;
        else drafts.push(activeInvoice);

        await AppUtils.saveData('drafts_db', drafts);
        updateQueueUI();
        initNewInvoice();
        AppUtils.showToast('Factura movida a la cola');
    };

    const updateQueueUI = async () => {
        const drafts = await AppUtils.loadData('drafts_db');
        queueContainer.innerHTML = drafts.map(d => `
            <div onclick="loadFromQueue('${d.id}')" class="p-3 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-neon-green transition-all shadow-sm">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-[10px] font-bold text-navy-blue">#${d.id}</span>
                    <span class="text-[10px] px-1.5 py-0.5 bg-slate-100 rounded text-slate-500">${d.placa || 'S/P'}</span>
                </div>
                <p class="text-xs text-slate-600 truncate font-medium">${d.modelo || 'Sin descripción'}</p>
            </div>
        `).join('');
    };

    window.loadFromQueue = async (id) => {
        const drafts = await AppUtils.loadData('drafts_db');
        const found = drafts.find(d => d.id === id);
        if (found) {
            activeInvoice = JSON.parse(JSON.stringify(found));
            // Eliminar de la cola al cargarla para evitar duplicados al guardar
            const updated = drafts.filter(d => d.id !== id);
            await AppUtils.saveData('drafts_db', updated);
            renderInvoice();
            updateQueueUI();
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
        if (!selectedItemFromSearch) return AppUtils.showToast('Busque un artículo primero', 'warning');
        const qty = parseInt(inputQty.value);
        if (qty <= 0 || qty > selectedItemFromSearch.stock) return AppUtils.showToast('Stock insuficiente', 'error');

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
        renderInvoice();
    });

    btnAddService.addEventListener('click', () => {
        const nombre = inputServicioNombre.value.trim();
        const precio = parseFloat(inputServicioPrecio.value);
        if (!nombre || isNaN(precio) || precio <= 0) return AppUtils.showToast('Datos de servicio inválidos', 'warning');

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
    });

    window.removeItem = (index) => {
        activeInvoice.items.splice(index, 1);
        renderInvoice();
    };

    const renderInvoice = () => {
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
            initNewInvoice();
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
        const items = await res.json();
        
        // Cruzar con lo que está en cola para mostrar stock real "en proceso"
        const drafts = await AppUtils.loadData('drafts_db');
        lastSearchResults = items.map(item => {
            const inQueue = drafts.reduce((sum, d) => {
                return sum + d.items.filter(i => i.id === item.id).reduce((s, i) => s + i.cantidad, 0);
            }, 0);
            return { ...item, real_stock: item.stock - inQueue };
        });

        if (lastSearchResults.length > 0) {
            const html = lastSearchResults.map((item, index) => {
                const isAgotado = item.real_stock <= 0;
                return `<div class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 flex justify-between items-center ${isAgotado ? 'opacity-50 pointer-events-none' : ''}" onclick="selectItemFromResults('${index}')">
                            <div>
                                <p class="font-bold text-sm uppercase">${item.nombre}</p>
                                <p class="text-[10px] ${item.real_stock <= 5 ? 'text-red-500 font-bold' : 'text-slate-400'} uppercase">Disponible: ${item.real_stock} ${isAgotado ? '(EN PROCESO)' : ''}</p>
                            </div>
                            <span class="font-bold text-navy-blue text-sm">${AppUtils.formatCurrency(item.precio)}</span>
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
        AppUtils.confirmAction('¿Nueva factura?', 'Se perderán los cambios no encolados.', () => initNewInvoice());
    });
    document.getElementById('btn-save-draft').addEventListener('click', saveToQueue);

    document.addEventListener('click', (e) => {
        if (!searchResults.contains(e.target) && e.target !== searchInput) searchResults.classList.add('hidden');
    });

    initNewInvoice();
    updateQueueUI();
});