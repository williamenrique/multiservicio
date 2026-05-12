/**
 * Billing Module - Lógica específica de ventas
 */

let currentCart = [];
let activeBillId = null;
let carModel = "";

// Obtener IVA dinámicamente de la configuración
async function getIvaRate() {
    const config = await AppUtils.loadData('company_db');
    if (config.length > 0) return config[0].iva / 100;
    return 0.19; // Default 19%
}

async function initBilling() {
    const factSection = document.getElementById('sec-facturacion');
    if (!factSection) return;

    const drafts = await AppUtils.loadData('drafts_db');

    // 1. Si no hay ID activo, intentar recuperar el primero de la lista de borradores
    if (!activeBillId && drafts.length > 0) {
        activeBillId = drafts[0].id;
    }

    // 2. Si sigue sin haber ID activo (lista vacía), mostrar un estado inicial en lugar de crear una automáticamente
    if (!activeBillId) {
        factSection.innerHTML = `
            <div class="flex flex-col items-center justify-center py-20 bg-white rounded-xl shadow-sm border border-slate-100 animate-fadeIn">
                <div class="bg-blue-50 p-6 rounded-full mb-6">
                    <i data-lucide="receipt" class="w-16 h-16 text-blue-500"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Módulo de Facturación</h2>
                <p class="text-slate-500 mb-8 text-center max-w-md">No tienes facturas activas en este momento. Inicia una nueva venta para comenzar a registrar servicios y repuestos.</p>
                <button onclick="createNewBill()" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold flex items-center gap-2 hover:bg-blue-700 transition shadow-lg transform hover:scale-105">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i> INICIAR NUEVA FACTURA
                </button>
            </div>
        `;
        lucide.createIcons();
        showSection('facturacion');
        return;
    }

    // 3. Buscar los datos del borrador activo
    const current = drafts.find(d => String(d.id) === String(activeBillId));
    if (current) {
        currentCart = current.items;
        carModel = current.carModel;
    } else {
        // Si el ID activo ya no existe (fue eliminado), reseteamos y reintentamos inicializar
        activeBillId = null;
        return initBilling();
    }

    factSection.innerHTML = `
        <!-- Barra de Gestión de Facturas Pendientes -->
        <div class="glass-card p-4 rounded-xl mb-6 flex flex-wrap items-center justify-between gap-4 border-b-2 border-navy-blue relative z-20">
            <div class="flex items-center gap-4">
                <div class="bg-navy-blue text-white px-3 py-1 rounded-lg font-mono">
                    #${activeBillId}
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase">Modelo del Vehículo</label>
                    <input type="text" id="carModelInput" value="${carModel}" onchange="updateCarModel(this.value.toUpperCase())" 
                        class="bg-transparent border-b border-slate-300 focus:border-navy-blue outline-none font-bold text-slate-800 uppercase" 
                        placeholder="EJ: TOYOTA HILUX 2023">
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <div class="relative group">
                    <button class="bg-slate-100 px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-slate-200 transition">
                        <i data-lucide="layers" class="w-4 h-4"></i> Facturas Pendientes (${drafts.length})
                    </button>
                    <div class="hidden group-hover:block absolute right-0 w-64 bg-white border border-slate-200 rounded-xl shadow-2xl z-[60] mt-1">
                        <div class="p-2 max-h-60 overflow-y-auto">
                            ${drafts.map(d => `
                                <div class="flex justify-between items-center p-2 hover:bg-slate-50 rounded-lg border-b last:border-0 group/item">
                                    <div onclick="switchBill('${d.id}')" class="flex-1 cursor-pointer">
                                        <p class="text-xs font-bold text-navy-blue">FACTURA #${d.id}</p>
                                        <p class="text-[10px] text-slate-500">${d.carModel || 'SIN MODELO'}</p>
                                        <p class="text-[10px] text-emerald-600 font-bold">${AppUtils.formatCurrency(d.items.reduce((sum, i) => sum + (i.price * i.quantity), 0) * 1.19)}</p>
                                    </div>
                                    <button onclick="deleteBill('${d.id}')" class="text-slate-300 hover:text-red-500 transition-colors p-1" title="Eliminar Borrador">
                                        <i data-lucide="x-circle" class="w-5 h-5"></i>
                                    </button>
                                </div>
                            `).join('')}
                            ${drafts.length === 0 ? '<p class="text-xs text-center p-2 text-slate-400 italic">No hay borradores</p>' : ''}
                        </div>
                    </div>
                </div>
                
                <button onclick="deleteActiveBill()" class="bg-red-50 text-red-600 px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-red-600 hover:text-white transition">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Eliminar
                </button>

                <button onclick="createNewBill()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-blue-700">
                    <i data-lucide="plus" class="w-4 h-4"></i> Nueva Factura
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Panel Izquierdo: Selección -->
            <div class="glass-card p-6 rounded-xl">
                <!-- Buscador de Inventario -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-slate-800 uppercase tracking-tight">
                        <i data-lucide="package" class="text-navy-blue"></i> Repuestos e Inventario
                    </h3>
                    <div class="flex flex-col sm:flex-row gap-2 w-full relative">
                        <div class="relative flex-[3] min-w-0">
                            <input type="text" id="productSearch" oninput="handleProductSearch(this.value)" onfocus="handleProductSearch(this.value)" class="w-full bg-white border border-gray-300 p-2 rounded text-slate-800 focus:ring-2 focus:ring-navy-blue outline-none" placeholder="Escriba para buscar repuesto...">
                            <input type="hidden" id="selectedProductId">
                            <div id="searchResults" class="hidden absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-2xl max-h-64 overflow-y-auto"></div>
                        </div>
                        <input type="number" id="productQuantity" value="1" min="1" class="flex-1 min-w-[70px] bg-white border border-gray-300 p-2 rounded text-slate-800 focus:ring-2 focus:ring-navy-blue outline-none" placeholder="Cant.">
                        <button onclick="addToBill()" class="whitespace-nowrap flex-shrink-0 bg-navy-blue text-white px-4 py-2 rounded hover:bg-slate-800 transition flex items-center justify-center gap-2 shadow-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i> Agregar
                        </button>
                    </div>
                </div>

                <!-- Servicios Manuales -->
                <div class="pt-6 border-t border-slate-100">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-slate-800 uppercase tracking-tight">
                        <i data-lucide="wrench" class="text-blue-600"></i> Mano de Obra / Servicios
                    </h3>
                    <div class="space-y-3 no-print">
                        <input id="serviceName" class="w-full bg-white border border-gray-300 p-2 rounded text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none uppercase" placeholder="DESCRIPCIÓN DEL SERVICIO (EJ: MANO DE OBRA)">
                        <div class="flex flex-col sm:flex-row gap-2 w-full">
                            <input id="servicePrice" type="number" class="flex-1 min-w-0 bg-white border border-gray-300 p-2 rounded text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Precio">
                            <button onclick="addServiceToBill()" class="whitespace-nowrap flex-shrink-0 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition flex items-center justify-center gap-2 shadow-sm">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i> Añadir Servicio
                            </button>
                        </div>
                    </div>
                </div>  
            </div>

            <!-- Panel Derecho: Preview -->
            <div class="glass-card p-6 rounded-xl border-t-4 border-neon-green">
                <h3 class="text-xl font-bold mb-4 text-slate-800 flex justify-between">Resumen <span class="text-xs text-slate-400">#${activeBillId}</span></h3>
                <div id="billPreview" class="min-h-[200px] border-b border-slate-200 mb-4 overflow-y-auto max-h-[400px]">
                    <!-- Items de factura -->
                </div>
                <div class="space-y-2 text-right">
                    <p>Subtotal: <span id="subtotal">$ 0</span></p>
                    <p>IVA (19%): <span id="iva">$ 0</span></p>
                    <p class="text-2xl font-bold text-neon-green">Total: <span id="total">$ 0</span></p>
                </div>
                <button onclick="processSale()" class="w-full mt-6 bg-neon-green text-black font-black py-4 rounded-xl shadow-lg hover:scale-[1.02] transition-transform">
                    FINALIZAR VENTA Y DESCONTAR STOCK
                </button>
            </div>
        </div>
    `;
    lucide.createIcons();
    renderCart();
    showSection('facturacion'); // Activa el enlace de la barra lateral para Facturación
}

/**
 * Crea una nueva factura en blanco
 */
async function createNewBill() {
    const drafts = await AppUtils.loadData('drafts_db'); // Await loadData
    const newId = Date.now().toString().slice(-6); // ID de 6 dígitos basado en tiempo

    const newDraft = {
        id: newId,
        carModel: "",
        items: [],
        date: new Date().toISOString()
    };

    drafts.push(newDraft);
    await AppUtils.saveData('drafts_db', drafts); // Await save
    activeBillId = newId;
    await initBilling();
    AppUtils.showToast('Nueva factura creada');
}

/**
 * Cambia entre facturas existentes
 */
async function switchBill(id) {
    await saveCurrentDraft(); // Await saveCurrentDraft
    activeBillId = id.toString();
    await initBilling();
}

/**
 * Actualiza el modelo del carro en el estado
 */
async function updateCarModel(val) { // Mark as async
    carModel = val; // Already converted to uppercase in the input's onchange
    await saveCurrentDraft();
}


/**
 * Filtra el inventario en tiempo real y muestra los resultados
 */
async function handleProductSearch(query) {
    const resultsDiv = document.getElementById('searchResults');
    const searchTerm = query.toLowerCase().trim();

    const filtered = inventory.filter(p =>
        p.stock > 0 && p.name.toLowerCase().includes(searchTerm)
    );

    if (filtered.length === 0) {
        resultsDiv.innerHTML = '<div class="p-3 text-slate-400 italic">No se encontraron resultados</div>';
    } else {
        resultsDiv.innerHTML = filtered.map(p => `
            <div onclick="selectProduct(${p.id}, '${p.name.replace(/'/g, "\\'")}')" 
                 class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0 flex justify-between items-center transition-colors">
                <div class="min-w-0">
                    <span class="font-medium text-slate-800 block">${p.name}</span>
                    <p class="text-[10px] text-slate-400 uppercase font-bold">${p.category}</p>
                </div>
                <div class="text-right flex-shrink-0 ml-4">
                    <span class="font-bold text-navy-blue block text-sm">${AppUtils.formatCurrency(p.price)}</span>
                    <span class="text-[10px] px-1.5 py-0.5 bg-slate-100 rounded text-slate-600">Disp: ${p.stock}</span>
                </div>
            </div>
        `).join('');
    }
    resultsDiv.classList.remove('hidden');
}

function selectProduct(id, name) {
    document.getElementById('selectedProductId').value = id;
    document.getElementById('productSearch').value = name;
    document.getElementById('searchResults').classList.add('hidden');
}

/**
 * Agrega un producto seleccionado al carrito actual
 */
async function addToBill() {
    const productId = parseInt(document.getElementById('selectedProductId')?.value);
    const quantityInput = document.getElementById('productQuantity');
    const quantity = parseInt(quantityInput.value);

    if (!productId) {
        AppUtils.showToast('Busque y seleccione un producto de la lista', 'warning');
        return;
    }

    if (isNaN(quantity) || quantity <= 0) {
        AppUtils.showToast('Ingrese una cantidad válida', 'warning');
        return;
    }

    const product = inventory.find(p => p.id === productId);

    if (product) {
        if (quantity > product.stock) {
            AppUtils.showToast(`Solo quedan ${product.stock} unidades`, 'error');
            return;
        }

        // Descontar del inventario real de inmediato (Reserva)
        product.stock -= quantity;
        await AppUtils.saveData('inventory_db', inventory); // Await save
        if (typeof refreshUI === 'function') refreshUI();

        const existingItem = currentCart.find(item => item.id === productId);
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            currentCart.push({ ...product, quantity: quantity });
        }

        renderCart();
        await saveCurrentDraft();
        AppUtils.showToast(`${product.name} agregado`);
        document.getElementById('productSearch').value = '';
        document.getElementById('selectedProductId').value = '';
        quantityInput.value = '1';
    }
}

/**
 * Agrega un servicio manual (mano de obra, etc) al carrito
 */
async function addServiceToBill() {
    const nameInput = document.getElementById('serviceName');
    const priceInput = document.getElementById('servicePrice');
    const name = nameInput.value.trim();
    const price = parseFloat(priceInput.value);

    if (!name || isNaN(price) || price <= 0) {
        AppUtils.showToast('Ingrese descripción y precio válidos', 'warning');
        return;
    }

    const serviceItem = {
        id: `SERV-${Date.now()}`, // ID único para evitar conflictos con inventario, ahora en mayúsculas
        name: `${name.toUpperCase()}`,
        price: price,
        quantity: 1,
        isService: true
    };

    currentCart.push(serviceItem);
    await renderCart(); // Await renderCart
    await saveCurrentDraft();
    AppUtils.showToast('Servicio añadido');

    // Limpiar campos
    nameInput.value = '';
    priceInput.value = '';
}

/**
 * Persiste el estado actual de la factura en los borradores
 */
async function saveCurrentDraft() {
    if (!activeBillId) return;
    const drafts = await AppUtils.loadData('drafts_db');
    const index = drafts.findIndex(d => d.id === activeBillId);
    if (index !== -1) {
        drafts[index].items = currentCart;
        drafts[index].carModel = carModel;
        await AppUtils.saveData('drafts_db', drafts);
    }
}

/**
 * Renderiza la lista de productos en el preview de la factura
 */
async function renderCart() {
    const preview = document.getElementById('billPreview');
    if (!preview) return;

    const subtotalEl = document.getElementById('subtotal');
    const ivaEl = document.getElementById('iva');
    const totalEl = document.getElementById('total');

    if (currentCart.length === 0) {
        preview.innerHTML = '<p class="text-gray-500 italic text-center py-10">No hay items en la factura</p>';
    } else {
        preview.innerHTML = currentCart.map((item, index) => `
            <div class="flex justify-between items-center mb-2 bg-slate-50 p-3 rounded-lg border border-slate-200">
                <div class="flex flex-col">
                    <span class="font-semibold text-slate-800">${item.name}</span>
                    <span class="text-xs text-slate-500">${item.quantity} x ${AppUtils.formatCurrency(item.price)}</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="font-bold text-slate-900">${AppUtils.formatCurrency(item.price * item.quantity)}</span>
                    <button onclick="removeFromCart(${index})" class="text-red-500 hover:bg-red-500/10 p-1 rounded transition">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        `).join('');
        lucide.createIcons();
    }

    const subtotal = currentCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const ivaRate = await getIvaRate(); // Await getIvaRate
    const iva = subtotal * ivaRate;
    const total = subtotal + iva;

    subtotalEl.textContent = AppUtils.formatCurrency(subtotal);
    ivaEl.textContent = AppUtils.formatCurrency(iva);
    totalEl.textContent = AppUtils.formatCurrency(total);
}

/**
 * Elimina la factura activa con confirmación
 */
async function deleteActiveBill() { // Mark as async
    if (!activeBillId) return;
    await deleteBill(activeBillId);
}

/**
 * Elimina una factura específica y devuelve el stock
 */
async function deleteBill(id) { // This was already correct
    const idToDelete = String(id);
    Swal.fire({
        title: '¿Eliminar factura?', // This was already correct
        text: "Los productos agregados serán devueltos al inventario global.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) { // This was already correct
            const drafts = await AppUtils.loadData('drafts_db'); // This was already correct
            const draftToDelete = drafts.find(d => String(d.id) === idToDelete);

            if (draftToDelete) {
                // Devolver stock al inventario
                draftToDelete.items.forEach(item => {
                    if (!item.isService) {
                        const product = inventory.find(p => String(p.id) === String(item.id));
                        if (product) product.stock += item.quantity;
                    }
                });
                await AppUtils.saveData('inventory_db', inventory); // Await save
            }

            const filteredDrafts = drafts.filter(d => String(d.id) !== idToDelete);
            await AppUtils.saveData('drafts_db', filteredDrafts); // Await save

            if (String(activeBillId) === idToDelete) {
                activeBillId = null;
                currentCart = [];
                carModel = "";
            }

            if (typeof refreshUI === 'function') await refreshUI(); // Await refreshUI
            await initBilling(); // Await initBilling
            AppUtils.showToast('Factura eliminada y stock devuelto');
        }
    });
}

/**
 * Elimina un item del carrito
 */
async function removeFromCart(index) {
    const item = currentCart[index];

    // Devolver stock al inventario real si no es un servicio
    if (!item.isService) {
        const product = inventory.find(p => p.id === item.id);
        if (product) product.stock += item.quantity;
        await AppUtils.saveData('inventory_db', inventory);
        if (typeof refreshUI === 'function') await refreshUI(); // Await refreshUI
    }

    currentCart.splice(index, 1);
    await renderCart();
    await saveCurrentDraft();
}

/**
 * Procesa la venta: descuenta stock, guarda y limpia la UI
 */
async function processSale() { // This was already correct
    if (currentCart.length === 0) {
        AppUtils.showAlert('Carrito vacío', 'Agregue productos antes de procesar la venta', 'warning'); // This was already correct
        return;
    }

    // Registrar la venta en el historial para estadísticas financieras
    const subtotal = currentCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const total = subtotal * (1 + await getIvaRate()); // Await getIvaRate
    const sales = await AppUtils.loadData('sales_db'); // Await loadData
    sales.push({
        id: Date.now(),
        fecha: new Date().toISOString(),
        carModel: carModel,
        total: total
    });
    await AppUtils.saveData('sales_db', sales); // Await save

    // Persistir cambios
    await AppUtils.saveData('inventory_db', inventory); // Await save

    // Eliminar de borradores
    const drafts = await AppUtils.loadData('drafts_db'); // Await loadData
    const filteredDrafts = drafts.filter(d => d.id !== activeBillId);
    await AppUtils.saveData('drafts_db', filteredDrafts); // Await save

    // Notificar y limpiar
    AppUtils.showAlert('Venta Exitosa', 'El stock ha sido actualizado y la factura procesada.', 'success');

    currentCart = [];
    activeBillId = null;
    carModel = "";

    // Refrescar UI global (Dashboard y Tabla de Inventario)
    if (typeof refreshUI === 'function') await refreshUI(); // Await refreshUI

    // Reiniciar módulo de facturación para actualizar selectores de stock
    await initBilling(); // Await initBilling
}

// Escuchar el click en el menú para inicializar el módulo
document.addEventListener('click', (e) => {
    if (e.target.closest('[onclick*="facturacion"]')) initBilling();

    // Cerrar resultados de búsqueda al hacer clic fuera
    const searchContainer = document.getElementById('searchResults')?.parentElement;
    if (searchContainer && !searchContainer.contains(e.target)) {
        document.getElementById('searchResults').classList.add('hidden');
    }
});