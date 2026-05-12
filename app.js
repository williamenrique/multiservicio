/**
 * App Main Logic
 */

// Instancia global para control de DataTable y refresco de datos
let inventoryTable = null;
let salesChart = null;
let salesTable = null;
let clientsTable = null;
let staffTable = null;
let suppliersTable = null;
let purchasesTable = null;

// Variable global para el estado del inventario
let inventory = [];

document.addEventListener('DOMContentLoaded', async () => {
    // Limpiar localStorage para asegurar que solo se usen los datos de los archivos JSON
    localStorage.clear();

    initClock();
    initSidebar();

    // 1. Asegurar que los archivos JSON existan y sean válidos []
    await AppUtils.checkAndInitDB();

    // Cargar datos iniciales de forma asíncrona
    inventory = await AppUtils.loadData('inventory_db');

    // Solo sembrar datos si es la primerísima vez (verificando una bandera en company_db)
    const config = await AppUtils.loadData('company_db');
    if (config.length === 0) {
        await seedInitialData();
    }

    await initInventory();
    await initSalesHistory();
    await initClients();
    await initStaff();
    await initSuppliers();
    await initPurchases();

    await refreshUI();
    await loadCompanySettings(); // This was already correct

    // Auto-update dashboard cards every 5 seconds
    setInterval(renderDashboardCards, 5000);
    setInterval(renderFinancialCards, 5000);
    setInterval(renderPendingBillsDashboard, 5000);
    setInterval(renderSupplierDebtsDashboard, 5000);
});

// Reloj Digital en tiempo real
function initClock() {
    const clockElement = document.getElementById('digitalClock');
    setInterval(() => {
        const now = new Date();
        clockElement.textContent = now.toLocaleTimeString();
    }, 1000);
}

// Navegación de Secciones (SPA simple)
function showSection(sectionId) {
    const targetSection = document.getElementById(`sec-${sectionId}`);
    if (!targetSection) return;

    document.querySelectorAll('.content-section').forEach(section => section.classList.add('hidden'));
    targetSection.classList.remove('hidden');

    // Actualizar estado activo en sidebar
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-section') === sectionId) link.classList.add('active');
    });

    // Resetear filtros de la tabla si se entra por el menú principal (Sidebar)
    if (sectionId === 'inventario' && inventoryTable) {
        inventoryTable.search('').columns().search('').draw();
    }
}

/**
 * Centraliza la actualización de la UI cuando cambian los datos.
 * Debe llamarse después de cualquier operación CRUD o venta.
 */
async function refreshUI() {
    if (inventoryTable) {
        inventoryTable.clear().rows.add(inventory).draw();
    }

    const sales = await AppUtils.loadData('sales_db');
    const clients = await AppUtils.loadData('clients_db');
    const staff = await AppUtils.loadData('staff_db');
    const suppliers = await AppUtils.loadData('suppliers_db');
    const purchases = await AppUtils.loadData('purchases_db');

    if (salesTable) salesTable.clear().rows.add(sales).draw();
    if (clientsTable) clientsTable.clear().rows.add(clients).draw();
    if (staffTable) staffTable.clear().rows.add(staff).draw();
    if (suppliersTable) suppliersTable.clear().rows.add(suppliers).draw();
    if (purchasesTable) purchasesTable.clear().rows.add(purchases).draw();

    await renderDashboardCards();
    await renderFinancialCards();
    await renderPendingBillsDashboard();
    await renderSupplierDebtsDashboard();
}

/**
 * Poblar datos iniciales si no existen (Simulación de DB vacía)
 */
async function seedInitialData() {
    // Evitar duplicidad si ya existen datos
    const currentInv = await AppUtils.loadData('inventory_db');
    if (currentInv.length > 0) return;

    const defaultInventory = [
        { id: 1, name: 'ACEITE SINTÉTICO 5W30', category: 'Mecánica', stock: 12, price: 45000, image: '' },
        { id: 2, name: 'PASTILLAS FRENOS DEL.', category: 'Mecánica', stock: 3, price: 85000, image: '' }
    ];
    await AppUtils.saveData('inventory_db', defaultInventory);
    inventory = defaultInventory;

    const defaultSales = [
        { id: Date.now(), fecha: new Date().toISOString(), carModel: 'DEMO VEHÍCULO', total: 50000 }
    ];
    await AppUtils.saveData('sales_db', defaultSales);

    const defaultConfig = [{ name: 'Workshop Pro', nit: '00000000', iva: 19, address: 'Dirección taller' }];
    await AppUtils.saveData('company_db', defaultConfig);
}

async function initInventory() {
    inventoryTable = $('#inventoryTable').DataTable({
        data: inventory,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i data-lucide="file-spreadsheet" class="w-4 h-4"></i> EXCEL',
                className: 'btn-export',
                exportOptions: { columns: [1, 2, 3, 4, 5] }
            },
            {
                extend: 'pdfHtml5',
                text: '<i data-lucide="file-text" class="w-4 h-4"></i> PDF',
                className: 'btn-export',
                exportOptions: { columns: [1, 2, 3, 4, 5] }
            }
        ],
        width: '100%',
        columns: [
            {
                data: 'image',
                render: (data, type, row) => {
                    const imgUrl = data || 'https://placehold.co/100?text=SIN+FOTO';
                    return `<img src="${imgUrl}" onclick="AppUtils.viewImage('${imgUrl}', '${row.name}')" class="w-12 h-12 object-cover rounded-lg shadow-sm border border-slate-200 cursor-zoom-in hover:opacity-80 transition-all" alt="Producto">`;
                },
                orderable: false
            },
            { data: 'name' },
            { data: 'category' },
            { data: 'stock' },
            {
                data: 'price',
                render: (data) => AppUtils.formatCurrency(data)
            },
            {
                data: 'stock',
                render: (data) => {
                    if (data > 5) return `<span class="px-3 py-1 bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-full text-xs font-bold">Stock OK</span>`;
                    if (data > 0) return `<span class="px-3 py-1 bg-amber-100 text-amber-700 border border-amber-200 rounded-full text-xs font-bold">Bajo Stock</span>`;
                    return `<span class="px-3 py-1 bg-rose-100 text-rose-700 border border-rose-200 rounded-full text-xs font-bold">Agotado</span>`;
                }
            },
            {
                data: null,
                render: (data, type, row) => `
                    <div class="flex gap-2">
                        <button onclick="editProduct(${row.id})" class="p-1 text-slate-400 hover:text-blue-600 transition-colors"><i data-lucide="edit-2" class="w-4 h-4"></i></button>
                        <button onclick="genericDelete(${row.id}, 'inventory_db', 'Producto')" class="p-1 text-slate-400 hover:text-red-500 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                `
            }
        ],
        responsive: true,
        language: {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sSearch": "Buscar:",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            }
        },
        drawCallback: () => lucide.createIcons()
    });
}

/**
 * Inicializa la tabla de historial de ventas
 */
async function initSalesHistory() {
    const sales = await AppUtils.loadData('sales_db');
    salesTable = $('#salesTable').DataTable({
        data: sales,
        order: [[1, 'desc']],
        columns: [
            { data: 'id', render: (data) => `<span class="font-mono">#${data.toString().slice(-6)}</span>` },
            {
                data: 'fecha',
                render: (data) => new Date(data).toLocaleString('es-CO', { dateStyle: 'medium', timeStyle: 'short' })
            },
            { data: 'carModel', render: (data) => data || 'N/A' },
            {
                data: 'total',
                render: (data) => `<span class="font-bold text-navy-blue">${AppUtils.formatCurrency(data)}</span>`
            },
            {
                data: null,
                render: (data, type, row) => `
                    <div class="flex gap-2">
                        <button onclick="AppUtils.showToast('Función de reimpresión en desarrollo', 'info')" 
                                class="p-1 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Ver Detalle">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                        <button onclick="genericDelete(${row.id}, 'sales_db', 'Venta del Historial')" 
                                class="p-1 text-slate-400 hover:text-red-500 transition-colors" title="Eliminar del Historial">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                `
            }
        ],
        responsive: true,
        language: { search: "Buscar venta:" },
        drawCallback: () => lucide.createIcons()
    });
}

async function renderDashboardCards() {
    const container = document.getElementById('dashboard-cards');
    const drafts = await AppUtils.loadData('drafts_db');

    // Calcular cuántos repuestos hay "reservados" en borradores
    const reservedUnits = drafts.reduce((total, draft) => {
        return total + draft.items.reduce((sum, item) => sum + (item.isService ? 0 : item.quantity), 0);
    }, 0);

    const stats = [
        { label: 'Productos OK', value: inventory.filter(p => p.stock > 5).length, color: 'text-neon-green', border: 'border-neon-green', icon: 'check-circle', filter: 'Stock OK' },
        { label: 'Stock Crítico', value: inventory.filter(p => p.stock <= 5 && p.stock > 0).length, color: 'text-cat-yellow', border: 'border-cat-yellow', icon: 'alert-triangle', filter: 'Bajo Stock' },
        { label: 'Agotados', value: inventory.filter(p => p.stock === 0).length, color: 'text-error-red', border: 'border-error-red', icon: 'alert-circle', filter: 'Agotado' },
        { label: 'En Servicio', value: reservedUnits, color: 'text-blue-500', border: 'border-blue-500', icon: 'clock', section: 'facturacion' }
    ];

    container.innerHTML = stats.map(s => `
        <div onclick="${s.filter ? `navigateToInventoryFilter('${s.filter}')` : `showSection('${s.section}')`}" 
             class="glass-card p-6 rounded-xl flex items-center justify-between border-l-4 ${s.border} cursor-pointer hover:shadow-lg hover:scale-[1.02] transition-all group">
            <div class="pointer-events-none">
                <p class="text-gray-400 text-sm">${s.label}</p>
                <h3 class="text-3xl font-bold ${s.color}">${s.value}</h3>
            </div>
            <i data-lucide="${s.icon}" class="${s.color} w-10 h-10 opacity-30"></i>
        </div>
    `).join('');
    lucide.createIcons();
}

/**
 * Navega al inventario y aplica un filtro específico por estado (Columna 5)
 */
function navigateToInventoryFilter(filterText) {
    showSection('inventario');
    if (inventoryTable) {
        // Columna 5 es donde están los badges de estado (Stock OK, Bajo Stock, etc)
        inventoryTable.columns(5).search(filterText).draw();
    }
}

/**
 * Limpia todos los filtros activos en la tabla de inventario
 */
function clearInventoryFilters() {
    if (inventoryTable) {
        inventoryTable.search('').columns().search('').draw();
        AppUtils.showToast('Mostrando todo el inventario', 'info');
    }
}

/**
 * Inicialización y control del Sidebar
 */
function initSidebar() {
    const btn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    btn.addEventListener('click', () => {
        sidebar.classList.toggle('w-64');
        sidebar.classList.toggle('w-20');
    });
}

/**
 * Abre el modal para registrar un nuevo producto y actualiza la tabla dinámicamente
 */
function openInventoryModal() {
    Swal.fire({
        title: '<span class="text-slate-800">Registrar Nuevo Producto</span>',
        html: `
            <div class="text-left mt-4 px-2">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Imagen del Producto</label>
                <div class="flex gap-2 mb-2">
                    <input id="swal-image" class="flex-1 p-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-neon-green transition-all" placeholder="URL de la imagen...">
                    <input type="file" id="swal-file-input" class="hidden" accept="image/*">
                    <button type="button" onclick="document.getElementById('swal-file-input').click()" class="bg-slate-100 p-2 rounded-lg hover:bg-slate-200 transition" title="Cargar desde el equipo">
                        <i data-lucide="upload" class="w-4 h-4 text-slate-600"></i>
                    </button>
                </div>
                
                <div id="image-preview-container" class="relative w-full h-40 bg-slate-100 rounded-lg border-2 border-dashed border-slate-200 mb-4 flex items-center justify-center overflow-hidden p-2">
                    <img id="swal-preview" src="" class="hidden max-w-full max-h-full object-contain mx-auto shadow-sm">
                    <div id="preview-placeholder" class="text-slate-400 text-[10px] flex flex-col items-center">
                        <i data-lucide="image" class="w-6 h-6 mb-1 opacity-20"></i>
                        <span>VISTA PREVIA</span>
                    </div>
                    <button id="clear-image" type="button" class="hidden absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full hover:bg-red-600 transition shadow-lg">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                </div>

                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre del Producto</label>
                <input id="swal-name" class="w-full p-2 border border-slate-300 rounded-lg mb-4 focus:ring-2 focus:ring-neon-green outline-none transition-all uppercase" placeholder="EJ: AMORTIGUADOR TRASERO">
                
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Categoría</label>
                <select id="swal-category" class="w-full p-2 border border-slate-300 rounded-lg mb-4 focus:ring-2 focus:ring-neon-green outline-none transition-all text-slate-600">
                    <option value="Mecánica">Mecánica</option>
                    <option value="Electricidad">Electricidad</option>
                    <option value="Latonería">Latonería</option>
                </select>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Precio Unitario</label>
                        <input id="swal-price" type="number" class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none transition-all" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Stock Inicial</label>
                        <input id="swal-stock" type="number" class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none transition-all" placeholder="0">
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<span class="text-black font-bold">Guardar Producto</span>',
        confirmButtonColor: '#39FF14',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const input = document.getElementById('swal-image');
            const preview = document.getElementById('swal-preview');
            const placeholder = document.getElementById('preview-placeholder');
            const clearBtn = document.getElementById('clear-image');
            const fileInput = document.getElementById('swal-file-input');

            const updatePreview = (url) => {
                if (url && url.trim() !== '') {
                    preview.src = url;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    clearBtn.classList.remove('hidden');
                } else {
                    preview.src = '';
                    preview.classList.add('hidden');
                    placeholder.classList.remove('hidden');
                    clearBtn.classList.add('hidden');
                }
            };

            input.addEventListener('input', (e) => updatePreview(e.target.value));

            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const base64 = event.target.result;
                        input.value = base64; // Colocamos el base64 en el input de texto
                        updatePreview(base64);
                    };
                    reader.readAsDataURL(file);
                }
            });

            clearBtn.addEventListener('click', () => {
                input.value = '';
                fileInput.value = '';
                updatePreview('');
            });

            lucide.createIcons();
        },
        preConfirm: () => {
            const image = document.getElementById('swal-image').value.trim(); // This was already correct
            const name = document.getElementById('swal-name').value.trim().toUpperCase(); // This was already correct
            const category = document.getElementById('swal-category').value; // This was already correct
            const price = parseFloat(document.getElementById('swal-price').value); // This was already correct
            const stock = parseInt(document.getElementById('swal-stock').value); // This was already correct

            if (!name || isNaN(price) || isNaN(stock)) {
                Swal.showValidationMessage(`Por favor, completa todos los campos correctamente`);
                return false;
            }
            return { name, category, price, stock, image };
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            // Generar nuevo ID único
            const newId = inventory.length > 0 ? Math.max(...inventory.map(p => p.id)) + 1 : 1;

            const newProduct = {
                id: newId,
                ...result.value
            };

            // Actualizar arreglo global y persistencia
            inventory.push(newProduct);
            await AppUtils.saveData('inventory_db', inventory); // Await save

            // Refrescar la tabla y los indicadores del dashboard sin recargar la página
            await refreshUI(); // Await refreshUI

            AppUtils.showToast('Producto agregado al inventario', 'success');
        }
    });
}

/** 
 * GESTIÓN DE CLIENTES 
 */
async function initClients() {
    const clients = await AppUtils.loadData('clients_db');
    clientsTable = $('#clientsTable').DataTable({
        data: clients,
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'phone' },
            { data: 'email' },
            {
                data: null,
                render: (data, type, row) => `
                    <div class="flex gap-2">
                        <button onclick="openClientModal(${row.id})" class="p-1 text-slate-400 hover:text-blue-600 transition-colors"><i data-lucide="edit-2" class="w-4 h-4"></i></button>
                        <button onclick="genericDelete(${row.id}, 'clients_db', 'Cliente')" class="p-1 text-slate-400 hover:text-red-500 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                `
            }
        ],
        responsive: true,
        drawCallback: () => lucide.createIcons()
    });
}

async function openClientModal(id = null) {
    const clients = await AppUtils.loadData('clients_db');
    const client = id ? clients.find(c => c.id == id) : { id: '', name: '', phone: '', email: '', address: '' };

    Swal.fire({
        title: id ? 'Editar Cliente' : 'Nuevo Cliente',
        html: `
            <div class="text-left space-y-4">
                <input id="c-id" class="w-full p-2 border rounded-lg" placeholder="Documento/NIT" value="${client.id}">
                <input id="c-name" class="w-full p-2 border rounded-lg uppercase" placeholder="Nombre completo" value="${client.name}">
                <input id="c-phone" class="w-full p-2 border rounded-lg" placeholder="Teléfono" value="${client.phone}">
                <input id="c-email" class="w-full p-2 border rounded-lg" placeholder="Correo electrónico" value="${client.email}">
            </div>
        `,
        confirmButtonColor: '#39FF14',
        confirmButtonText: '<span class="text-black font-bold">Guardar</span>',
        showCancelButton: true,
        preConfirm: () => {
            return {
                id: document.getElementById('c-id').value,
                name: document.getElementById('c-name').value.toUpperCase(),
                phone: document.getElementById('c-phone').value,
                email: document.getElementById('c-email').value
            }
        }
    }).then(async result => {
        if (result.isConfirmed) {
            let updatedClients = id ? clients.filter(c => c.id != id) : clients;
            updatedClients.push(result.value);
            await AppUtils.saveData('clients_db', updatedClients);
            await refreshUI();
        }
    });
}

/** 
 * GESTIÓN DE PERSONAL 
 */
async function initStaff() {
    const staff = await AppUtils.loadData('staff_db');
    staffTable = $('#staffTable').DataTable({
        data: staff,
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'role' },
            { data: 'phone' },
            {
                data: null,
                render: (data, type, row) => `
                    <div class="flex gap-2">
                        <button onclick="openStaffModal('${row.id}')" class="p-1 text-slate-400 hover:text-blue-600 transition-colors"><i data-lucide="edit-2" class="w-4 h-4"></i></button>
                        <button onclick="genericDelete('${row.id}', 'staff_db', 'Empleado')" class="p-1 text-slate-400 hover:text-red-500 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                `
            }
        ],
        responsive: true,
        drawCallback: () => lucide.createIcons()
    });
}

async function openStaffModal(id = null) {
    const staff = await AppUtils.loadData('staff_db');
    const member = id ? staff.find(s => s.id == id) : { id: '', name: '', role: 'Mecánico', phone: '' };

    Swal.fire({
        title: id ? 'Editar Empleado' : 'Nuevo Empleado',
        html: `
            <div class="text-left space-y-4">
                <input id="s-id" class="w-full p-2 border rounded-lg" placeholder="Documento de Identidad" value="${member.id}">
                <input id="s-name" class="w-full p-2 border rounded-lg uppercase" placeholder="Nombre completo" value="${member.name}">
                <select id="s-role" class="w-full p-2 border rounded-lg">
                    <option value="Mecánico" ${member.role == 'Mecánico' ? 'selected' : ''}>Mecánico</option>
                    <option value="Administrador" ${member.role == 'Administrador' ? 'selected' : ''}>Administrador</option>
                    <option value="Ayudante" ${member.role == 'Ayudante' ? 'selected' : ''}>Ayudante</option>
                </select>
                <input id="s-phone" class="w-full p-2 border rounded-lg" placeholder="Teléfono" value="${member.phone}">
            </div>
        `,
        confirmButtonColor: '#39FF14',
        confirmButtonText: '<span class="text-black font-bold">Guardar</span>',
        showCancelButton: true,
        preConfirm: () => {
            return {
                id: document.getElementById('s-id').value,
                name: document.getElementById('s-name').value.toUpperCase(),
                role: document.getElementById('s-role').value,
                phone: document.getElementById('s-phone').value
            }
        }
    }).then(async result => {
        if (result.isConfirmed) {
            let updatedStaff = id ? staff.filter(s => s.id != id) : staff;
            updatedStaff.push(result.value);
            await AppUtils.saveData('staff_db', updatedStaff);
            await refreshUI();
        }
    });
}

/** 
 * CONFIGURACIÓN DE EMPRESA 
 */
async function loadCompanySettings() {
    const config = await AppUtils.loadData('company_db');
    if (config.length === 0) {
        const defaultConfig = { name: 'Workshop Pro', nit: '00000000', iva: 19, address: 'Dirección taller' };
        await AppUtils.saveData('company_db', [defaultConfig]);
        setFormConfig(defaultConfig);
    } else {
        setFormConfig(config[0]);
    }
}

function setFormConfig(config) {
    document.getElementById('config-name').value = config.name;
    document.getElementById('config-nit').value = config.nit;
    document.getElementById('config-iva').value = config.iva;
    document.getElementById('config-address').value = config.address;
}

async function saveCompanySettings(e) {
    e.preventDefault();
    const newConfig = {
        name: document.getElementById('config-name').value,
        nit: document.getElementById('config-nit').value,
        iva: parseFloat(document.getElementById('config-iva').value),
        address: document.getElementById('config-address').value
    };
    await AppUtils.saveData('company_db', [newConfig]);
    AppUtils.showToast('Configuración guardada correctamente');

    // Si el módulo de facturación está cargado, actualizar la tasa de IVA
    if (typeof initBilling === 'function') {
        setTimeout(initBilling, 500);
    }
}

/**
 * Renderiza una vista rápida de las facturas pendientes en el dashboard
 */
async function renderPendingBillsDashboard() {
    const container = document.getElementById('pending-bills-dashboard');
    if (!container) return;

    const drafts = await AppUtils.loadData('drafts_db');

    if (drafts.length === 0) {
        container.innerHTML = `
            <div class="col-span-full glass-card p-8 rounded-xl text-center text-slate-400">
                <i data-lucide="check-circle" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                <p class="italic font-medium">No hay facturas pendientes en este momento.</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }

    container.innerHTML = drafts.map(d => {
        const subtotal = d.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const total = subtotal * 1.19; // IVA incluido para visualización
        return `
            <div onclick="resumeBillFromDashboard('${d.id}')" class="glass-card p-5 rounded-xl border-l-4 border-blue-500 hover:shadow-xl transition-all cursor-pointer group">
                <div class="flex justify-between items-start mb-3">
                    <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-1 rounded">#${d.id}</span>
                    <span class="text-[10px] text-slate-400 font-medium">${new Date(d.date).toLocaleDateString()}</span>
                </div>
                <h4 class="font-bold text-slate-800 uppercase truncate group-hover:text-blue-600 transition-colors">${d.carModel || 'SIN MODELO'}</h4>
                <div class="flex justify-between items-center mt-4 pt-3 border-t border-slate-100">
                    <div class="flex items-center gap-1 text-slate-500">
                        <i data-lucide="shopping-cart" class="w-3 h-3"></i>
                        <span class="text-xs">${d.items.length} items</span>
                    </div>
                    <span class="font-bold text-navy-blue text-lg">${AppUtils.formatCurrency(total)}</span>
                </div>
            </div>
        `;
    }).join('');
    lucide.createIcons();
}

/**
 * Función puente para retomar una factura directamente desde el dashboard
 */
async function resumeBillFromDashboard(id) {
    if (typeof activeBillId !== 'undefined') {
        activeBillId = id.toString();
        showSection('facturacion');
        if (typeof initBilling === 'function') await initBilling(); // Await initBilling
    }
}

/**
 * Calcula y renderiza las estadísticas financieras en el dashboard
 */
async function renderFinancialCards() {
    const container = document.getElementById('financial-cards');
    if (!container) return;

    const sales = await AppUtils.loadData('sales_db');
    const totalFacturado = sales.reduce((sum, sale) => sum + (sale.total || 0), 0);
    const totalVentas = sales.length;
    const ticketPromedio = totalVentas > 0 ? totalFacturado / totalVentas : 0;

    const stats = [
        { label: 'Total Facturado', value: AppUtils.formatCurrency(totalFacturado), color: 'text-blue-600', border: 'border-blue-600', icon: 'dollar-sign' },
        { label: 'Ventas Realizadas', value: totalVentas, color: 'text-purple-600', border: 'border-purple-600', icon: 'shopping-bag' },
        { label: 'Ticket Promedio', value: AppUtils.formatCurrency(ticketPromedio), color: 'text-emerald-600', border: 'border-emerald-600', icon: 'pie-chart' }
    ];

    container.innerHTML = stats.map(s => `
        <div class="glass-card p-4 rounded-xl flex items-center justify-between border-l-4 ${s.border}">
            <div>
                <p class="text-gray-400 text-sm">${s.label}</p>
                <h3 class="text-2xl font-bold ${s.color}">${s.value}</h3>
            </div>
            <i data-lucide="${s.icon}" class="${s.color} w-10 h-10 opacity-20"></i>
        </div>
    `).join('');
    lucide.createIcons();
    updateSalesChart(sales);
}

/**
 * Función genérica para eliminar registros de cualquier base de datos JSON
 * @param {string|number} id - ID del registro a eliminar
 * @param {string} dbKey - Llave del archivo JSON (ej: 'inventory_db')
 * @param {string} label - Nombre legible para el mensaje de confirmación
 */
async function genericDelete(id, dbKey, label) {
    AppUtils.confirmAction(
        `¿Eliminar ${label}?`,
        "Esta acción no se puede deshacer y afectará los registros actuales.",
        async () => {
            const data = await AppUtils.loadData(dbKey);
            const filteredData = data.filter(item => (item.id || item.nit) != id);

            // Si eliminamos del inventario, actualizamos la variable global
            if (dbKey === 'inventory_db') inventory = filteredData;

            await AppUtils.saveData(dbKey, filteredData);
            await refreshUI();
            AppUtils.showToast(`${label} eliminado correctamente`, 'success');
        }
    );
}

/**
 * Actualiza o inicializa el gráfico de tendencia de ventas
 */
function updateSalesChart(sales) {
    const ctx = document.getElementById('salesChart');
    if (!ctx) return;

    // Agrupar ventas por fecha (últimos 7 registros)
    const lastSales = sales.slice(-7);
    const labels = lastSales.map(s => new Date(s.fecha).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
    const data = lastSales.map(s => s.total);

    if (salesChart) {
        salesChart.data.labels = labels;
        salesChart.data.datasets[0].data = data;
        salesChart.update();
    } else {
        salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ventas Recientes (COP)',
                    data: data,
                    borderColor: '#39FF14',
                    backgroundColor: 'rgba(57, 255, 20, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
}

/**
 * Elimina un producto del inventario con confirmación
 */

/**
 * Calcula y renderiza el resumen de deudas con proveedores en el dashboard
 */
async function renderSupplierDebtsDashboard() {
    const container = document.getElementById('supplier-debts-dashboard');
    if (!container) return;

    const purchases = await AppUtils.loadData('purchases_db');
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const activeDebts = purchases.filter(p => (p.total - p.paid) > 0);

    const totalOwed = activeDebts.reduce((sum, p) => sum + (p.total - p.paid), 0);

    const overdue = activeDebts.filter(p => p.cutoff && new Date(p.cutoff) < today);
    const totalOverdue = overdue.reduce((sum, p) => sum + (p.total - p.paid), 0);

    const dueSoon = activeDebts.filter(p => {
        if (!p.cutoff) return false;
        const cutoffDate = new Date(p.cutoff);
        const diffTime = cutoffDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays >= 0 && diffDays <= 7;
    });
    const totalDueSoon = dueSoon.reduce((sum, p) => sum + (p.total - p.paid), 0);

    const stats = [
        { label: 'Deuda Total Proveedores', value: AppUtils.formatCurrency(totalOwed), color: 'text-slate-800', border: 'border-slate-800', icon: 'wallet' },
        { label: 'Saldos Vencidos', value: AppUtils.formatCurrency(totalOverdue), color: 'text-error-red', border: 'border-error-red', icon: 'alert-octagon' },
        { label: 'Vencen en 7 días', value: AppUtils.formatCurrency(totalDueSoon), color: 'text-amber-600', border: 'border-amber-600', icon: 'calendar-clock' }
    ];

    container.innerHTML = stats.map(s => `
        <div class="glass-card p-4 rounded-xl flex items-center justify-between border-l-4 ${s.border}">
            <div>
                <p class="text-gray-400 text-sm">${s.label}</p>
                <h3 class="text-2xl font-bold ${s.color}">${s.value}</h3>
            </div>
            <i data-lucide="${s.icon}" class="${s.color} w-8 h-8 opacity-20"></i>
        </div>
    `).join('');
    lucide.createIcons();
}

/** GESTIÓN DE PROVEEDORES */
async function initSuppliers() {
    const suppliers = await AppUtils.loadData('suppliers_db'); // This was already correct
    suppliersTable = $('#suppliersTable').DataTable({
        data: suppliers,
        columns: [
            { data: 'id' }, { data: 'name' }, { data: 'phone' }, { data: 'email' },
            {
                data: null,
                render: (data, type, row) => `
                    <div class="flex gap-2">
                        <button onclick="openSupplierModal('${row.id}')" class="p-1 text-slate-400 hover:text-blue-600 transition-colors"><i data-lucide="edit-2" class="w-4 h-4"></i></button>
                        <button onclick="genericDelete('${row.id}', 'suppliers_db', 'Proveedor')" class="p-1 text-slate-400 hover:text-red-500 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                `
            }
        ],
        responsive: true,
        drawCallback: () => lucide.createIcons()
    });
}

async function initPurchases() { // This was already correct
    const purchases = await AppUtils.loadData('purchases_db'); // This was already correct
    purchasesTable = $('#purchasesTable').DataTable({
        data: purchases,
        order: [[0, 'desc']],
        columns: [
            { data: 'date', render: d => new Date(d).toLocaleDateString() },
            { data: 'supplierName' },
            { data: 'total', render: d => AppUtils.formatCurrency(d) },
            { data: 'paid', render: d => AppUtils.formatCurrency(d) },
            {
                data: null,
                render: row => `<span class="font-bold ${row.total - row.paid > 0 ? 'text-red-500' : 'text-emerald-500'}">${AppUtils.formatCurrency(row.total - row.paid)}</span>`
            },
            { data: 'cutoff', render: d => d ? new Date(d).toLocaleDateString() : 'N/A' },
            {
                data: null,
                render: (data, type, row) => `
                    <button onclick="viewPurchaseDetail('${row.id}')" class="p-1 text-slate-400 hover:text-blue-600 transition-colors"><i data-lucide="eye" class="w-4 h-4"></i></button>
                `
            }
        ],
        responsive: true,
        drawCallback: () => lucide.createIcons()
    });
}

function switchProveedorTab(tab) {
    const isLista = tab === 'lista';
    document.getElementById('prov-lista-content').classList.toggle('hidden', !isLista);
    document.getElementById('prov-deudas-content').classList.toggle('hidden', isLista);
    document.getElementById('tab-prov-lista').className = isLista ? 'pb-2 px-1 border-b-2 border-neon-green font-bold text-navy-blue' : 'pb-2 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue';
    document.getElementById('tab-prov-deudas').className = !isLista ? 'pb-2 px-1 border-b-2 border-neon-green font-bold text-navy-blue' : 'pb-2 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue';
}

async function openSupplierModal(id = null) {
    const suppliers = await AppUtils.loadData('suppliers_db'); // Await loadData
    const sup = id ? suppliers.find(s => s.id == id) : { id: '', name: '', phone: '', email: '' };
    Swal.fire({
        title: id ? 'Editar Proveedor' : 'Nuevo Proveedor',
        html: `<div class="text-left space-y-4">
                <input id="p-id" class="w-full p-2 border rounded-lg" placeholder="NIT o Cédula" value="${sup.id}">
                <input id="p-name" class="w-full p-2 border rounded-lg uppercase" placeholder="Nombre de la empresa" value="${sup.name}">
                <input id="p-phone" class="w-full p-2 border rounded-lg" placeholder="Teléfono de contacto" value="${sup.phone}">
                <input id="p-email" class="w-full p-2 border rounded-lg" placeholder="Correo electrónico" value="${sup.email}">
            </div>`,
        confirmButtonColor: '#39FF14',
        confirmButtonText: '<span class="text-black font-bold">Guardar</span>',
        showCancelButton: true,
        preConfirm: () => ({
            id: document.getElementById('p-id').value,
            name: document.getElementById('p-name').value.toUpperCase(),
            phone: document.getElementById('p-phone').value,
            email: document.getElementById('p-email').value
        })
    }).then(async result => { // Mark callback as async
        if (result.isConfirmed) {
            let data = id ? suppliers.filter(s => s.id != id) : suppliers;
            data.push(result.value);
            await AppUtils.saveData('suppliers_db', data); // Await save
            await refreshUI(); // Await refreshUI
        }
    });
}

async function openPurchaseModal() {
    const suppliers = await AppUtils.loadData('suppliers_db');
    if (suppliers.length === 0) return AppUtils.showAlert('Atención', 'Registre un proveedor primero', 'warning');
    const prodOptions = inventory.map(p => `<option value="${p.id}">${p.name} (Disp: ${p.stock})</option>`).join('');
    const supOptions = suppliers.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
    Swal.fire({
        title: 'Ingreso de Mercancía',
        html: `<div class="text-left space-y-3">
                <label class="block text-xs font-bold text-slate-500 uppercase">Proveedor y Producto</label>
                <select id="pur-sup" class="w-full p-2 border rounded-lg">${supOptions}</select>
                <select id="pur-prod" class="w-full p-2 border rounded-lg">${prodOptions}</select>
                <div class="grid grid-cols-2 gap-4">
                    <input type="number" id="pur-qty" class="p-2 border rounded-lg" placeholder="Cantidad">
                    <input type="number" id="pur-cost" class="p-2 border rounded-lg" placeholder="Costo Unitario">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <input type="number" id="pur-paid" class="p-2 border rounded-lg" value="0" placeholder="Abono">
                    <input type="date" id="pur-cutoff" class="p-2 border rounded-lg" title="Fecha de Pago/Corte">
                </div>
            </div>`,
        confirmButtonColor: '#39FF14', confirmButtonText: '<span class="text-black font-bold">Procesar</span>', showCancelButton: true,
        preConfirm: () => {
            const qty = parseInt(document.getElementById('pur-qty').value);
            const cost = parseFloat(document.getElementById('pur-cost').value);
            if (!qty || !cost) return Swal.showValidationMessage('Complete cantidad y costo');
            return { supplierId: document.getElementById('pur-sup').value, productId: parseInt(document.getElementById('pur-prod').value), qty, cost, paid: parseFloat(document.getElementById('pur-paid').value), cutoff: document.getElementById('pur-cutoff').value };
        }
    }).then(async result => { // Mark callback as async
        if (result.isConfirmed) {
            const val = result.value;
            const product = inventory.find(p => p.id == val.productId);
            product.stock += val.qty;
            await AppUtils.saveData('inventory_db', inventory); // Await save
            const purchases = await AppUtils.loadData('purchases_db'); // Await loadData
            purchases.push({ id: Date.now(), date: new Date().toISOString(), supplierId: val.supplierId, supplierName: suppliers.find(s => s.id == val.supplierId).name, productId: val.productId, productName: product.name, qty: val.qty, total: val.qty * val.cost, paid: val.paid, cutoff: val.cutoff });
            await AppUtils.saveData('purchases_db', purchases); // Await save
            await refreshUI(); // Await refreshUI
            AppUtils.showToast('Stock actualizado y deuda registrada');
        }
    });
}

async function viewPurchaseDetail(id) {
    const purchases = await AppUtils.loadData('purchases_db');
    const purchase = purchases.find(p => p.id == id);
    if (!purchase) return;
    Swal.fire({
        title: 'Detalle de Ingreso',
        html: `<div class="text-left text-sm space-y-2">
                <p><b>Proveedor:</b> ${purchase.supplierName}</p>
                <p><b>Producto:</b> ${purchase.productName} (x${purchase.qty})</p>
                <p><b>Total Factura:</b> ${AppUtils.formatCurrency(purchase.total)}</p>
                <p><b>Abonado:</b> ${AppUtils.formatCurrency(purchase.paid)}</p>
                <p class="text-red-600 font-bold"><b>Saldo:</b> ${AppUtils.formatCurrency(purchase.total - purchase.paid)}</p>
                <p><b>Corte:</b> ${purchase.cutoff || 'No definida'}</p>
            </div>`,
        confirmButtonColor: '#0b1120'
    });
}

/**
 * Descarga todos los datos del taller en un archivo JSON
 */
async function downloadBackup() {
    const data = {
        inventory: await AppUtils.loadData('inventory_db'),
        sales: await AppUtils.loadData('sales_db'),
        drafts: await AppUtils.loadData('drafts_db'),
        exportDate: new Date().toISOString()
    };
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `workshop_pro_backup_${new Date().toISOString().slice(0, 10)}.json`;
    a.click();
}

/**
 * Busca un producto por ID y abre el modal de edición con los datos cargados
 */
async function editProduct(id) {
    const product = inventory.find(p => p.id === id);
    if (!product) return;

    Swal.fire({
        title: `<span class="text-slate-800">Editar: ${product.name}</span>`,
        html: `
            <div class="text-left mt-4 px-2">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Imagen del Producto</label>
                <div class="flex gap-2 mb-2">
                    <input id="swal-image" class="flex-1 p-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-neon-green transition-all" value="${product.image || ''}" placeholder="URL de la imagen...">
                    <input type="file" id="swal-file-input" class="hidden" accept="image/*">
                    <button type="button" onclick="document.getElementById('swal-file-input').click()" class="bg-slate-100 p-2 rounded-lg hover:bg-slate-200 transition" title="Cargar desde el equipo">
                        <i data-lucide="upload" class="w-4 h-4 text-slate-600"></i>
                    </button>
                </div>

                <div id="image-preview-container" class="relative w-full h-40 bg-slate-100 rounded-lg border-2 border-dashed border-slate-200 mb-4 flex items-center justify-center overflow-hidden p-2">
                    <img id="swal-preview" src="${product.image || ''}" class="${product.image ? '' : 'hidden'} max-w-full max-h-full object-contain mx-auto shadow-sm">
                    <div id="preview-placeholder" class="${product.image ? 'hidden' : ''} text-slate-400 text-[10px] flex flex-col items-center">
                        <i data-lucide="image" class="w-6 h-6 mb-1 opacity-20"></i>
                        <span>VISTA PREVIA</span>
                    </div>
                    <button id="clear-image" type="button" class="${product.image ? '' : 'hidden'} absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full hover:bg-red-600 transition shadow-lg">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                </div>

                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre del Producto</label>
                <input id="swal-name" class="w-full p-2 border border-slate-300 rounded-lg mb-4 outline-none focus:ring-2 focus:ring-neon-green uppercase" value="${product.name}">
                
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Categoría</label>
                <select id="swal-category" class="w-full p-2 border border-slate-300 rounded-lg mb-4 outline-none focus:ring-2 focus:ring-neon-green">
                    <option value="Mecánica" ${product.category === 'Mecánica' ? 'selected' : ''}>Mecánica</option>
                    <option value="Electricidad" ${product.category === 'Electricidad' ? 'selected' : ''}>Electricidad</option>
                    <option value="Latonería" ${product.category === 'Latonería' ? 'selected' : ''}>Latonería</option>
                </select>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Precio Unitario</label>
                        <input id="swal-price" type="number" class="w-full p-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-neon-green" value="${product.price}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Stock</label>
                        <input id="swal-stock" type="number" class="w-full p-2 border border-slate-300 rounded-lg outline-none focus:ring-2 focus:ring-neon-green" value="${product.stock}">
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<span class="text-black font-bold">Actualizar Cambios</span>',
        confirmButtonColor: '#39FF14',
        didOpen: () => {
            const input = document.getElementById('swal-image');
            const preview = document.getElementById('swal-preview');
            const placeholder = document.getElementById('preview-placeholder');
            const clearBtn = document.getElementById('clear-image');
            const fileInput = document.getElementById('swal-file-input');

            const updatePreview = (url) => {
                if (url && url.trim() !== '') {
                    preview.src = url;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    clearBtn.classList.remove('hidden');
                } else {
                    preview.src = '';
                    preview.classList.add('hidden');
                    placeholder.classList.remove('hidden');
                    clearBtn.classList.add('hidden');
                }
            };

            input.addEventListener('input', (e) => updatePreview(e.target.value));

            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const base64 = event.target.result;
                        input.value = base64;
                        updatePreview(base64);
                    };
                    reader.readAsDataURL(file);
                }
            });

            clearBtn.addEventListener('click', () => {
                input.value = '';
                fileInput.value = '';
                updatePreview('');
            });

            lucide.createIcons();
        },
        preConfirm: () => {
            const image = document.getElementById('swal-image').value.trim();
            const name = document.getElementById('swal-name').value.trim().toUpperCase();
            const category = document.getElementById('swal-category').value;
            const price = parseFloat(document.getElementById('swal-price').value);
            const stock = parseInt(document.getElementById('swal-stock').value);

            if (!name || isNaN(price) || isNaN(stock)) {
                Swal.showValidationMessage(`Por favor, completa todos los campos correctamente`);
                return false;
            }
            return { name, category, price, stock, image };
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const index = inventory.findIndex(p => p.id === id);
            inventory[index] = { ...inventory[index], ...result.value };
            await AppUtils.saveData('inventory_db', inventory);
            await refreshUI();
            AppUtils.showToast('Producto actualizado correctamente');
        }
    });
}