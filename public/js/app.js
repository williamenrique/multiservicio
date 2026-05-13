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

// Variables globales de estado y datos
let users = [];
let staff = [];
let currentLoggedInUser = null;
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
    users = await AppUtils.loadData('users_db'); // Cargar usuarios
    staff = await AppUtils.loadData('staff_db'); // Cargar empleados para la info del perfil
    // Para efectos de desarrollo, asumimos que el primer admin es el usuario logueado
    const defaultAdminUser = users.find(u => u.role === 'Administrador');
    if (defaultAdminUser) currentLoggedInUser = defaultAdminUser;
    inventory = await AppUtils.loadData('inventory_db');

    // Solo sembrar datos si es la primerísima vez (verificando una bandera en company_db)
    const config = await AppUtils.loadData('company_db'); // Re-leer config después de seedInitialData
    if (config.length === 0) {
        await seedInitialData();
    }

    await initInventory();
    await initSalesHistory();
    await initClients();
    await initStaff();
    await initSuppliers();
    await initPurchases();
    await initExpenses();

    await refreshUI();
    await loadCompanySettings(); // This was already correct

    // Manejar la sección inicial basada en la URL (Deep Linking)
    handleInitialNavigation();

    // Auto-update dashboard cards every 5 seconds
    renderTopBarUserInfo(); // Cargar info del usuario en la barra superior una vez que todo esté cargado
    setInterval(renderDashboardCards, 5000);
    setInterval(renderFinancialCards, 5000);
    setInterval(renderPendingBillsDashboard, 5000);
    setInterval(renderSupplierDebtsDashboard, 5000);
    setInterval(renderExpensesDashboard, 5000);
});

// Escuchar los botones de Atrás/Adelante del navegador
window.addEventListener('popstate', (event) => {
    if (event.state && event.state.sectionId) {
        showSection(event.state.sectionId, false);
    }
});

// Reloj Digital en tiempo real
function initClock() {
    const clockElement = document.getElementById('digitalClock');
    setInterval(() => {
        const now = new Date();
        clockElement.textContent = now.toLocaleTimeString('es-CO', { hour12: true });
    }, 1000);
}

// Navegación de Secciones (SPA simple)
function showSection(sectionId, updateHistory = true) {
    const targetSection = document.getElementById(`sec-${sectionId}`);
    if (!targetSection) {
        console.warn(`La sección ${sectionId} no existe.`);
        return;
    }

    document.querySelectorAll('.content-section').forEach(section => section.classList.add('hidden'));
    targetSection.classList.remove('hidden');

    // Actualizar estado activo en sidebar
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-section') === sectionId) link.classList.add('active');
    });

    // Actualizar la URL en la barra de direcciones sin recargar
    if (updateHistory) {
        const cleanPath = sectionId === 'dashboard' ? 'dashboard' : sectionId;
        const newUrl = `${URLROOT}/${cleanPath}`;
        window.history.pushState({ sectionId }, "", newUrl);
    }

    // Resetear filtros de la tabla si se entra por el menú principal (Sidebar)
    if (sectionId === 'inventario' && inventoryTable) {
        inventoryTable.search('').columns().search('').draw();
    }
}

/**
 * Determina qué sección mostrar al cargar la página analizando la URL actual
 */
function handleInitialNavigation() {
    const fullPath = window.location.pathname;
    const validSections = ['dashboard', 'inventario', 'facturacion', 'historial', 'proveedores', 'gastos', 'clientes', 'personal', 'empresa'];
    
    // Buscamos si la URL contiene alguna de nuestras secciones
    const sectionFound = validSections.find(s => fullPath.includes(`/${s}`));
    
    // Si se encuentra una sección en la URL se muestra, de lo contrario por defecto va al dashboard
    showSection(sectionFound || 'dashboard', false);
}

async function refreshUI() {
    // Obtener datos frescos del servidor antes de renderizar
    inventory = await AppUtils.loadData('inventory_db');
    const sales = await AppUtils.loadData('sales_db');
    const clients = await AppUtils.loadData('clients_db');
    const staffData = await AppUtils.loadData('staff_db');
    const suppliers = await AppUtils.loadData('suppliers_db');
    const purchases = await AppUtils.loadData('purchases_db');
    const expenses = await AppUtils.loadData('expenses_db');
    users = await AppUtils.loadData('users_db');

    if (inventoryTable) {
        inventoryTable.clear().rows.add(inventory).draw();
    }

    // Unir datos de empleados con datos de usuario para que la tabla muestre el estado correcto
    const staffWithUsers = staffData.map(s => {
        const user = users.find(u => u.staffId === s.id);
        return {
            ...s,
            hasUser: !!user,
            username: user ? user.username : '',
            userRole: user ? user.role : ''
        };
    });

    if (salesTable) salesTable.clear().rows.add(sales).draw();
    if (clientsTable) clientsTable.clear().rows.add(clients).draw();
    if (staffTable) staffTable.clear().rows.add(staffWithUsers).draw();
    if (suppliersTable) suppliersTable.clear().rows.add(suppliers).draw();
    if (purchasesTable) purchasesTable.clear().rows.add(purchases).draw();
    if (expensesTable) expensesTable.clear().rows.add(expenses).draw();

    renderTopBarUserInfo(); // Actualizar info del usuario en la barra superior
    await renderDashboardCards();
    await renderFinancialCards();
    await renderPendingBillsDashboard();
    await renderSupplierDebtsDashboard();
    await renderExpensesDashboard();
}

/**
 * Renderiza el nombre y rol del usuario logueado en la barra superior.
 * También configura el menú desplegable del usuario.
 */
async function renderTopBarUserInfo() {
    const topbarUsername = document.getElementById('topbar-username');
    const topbarUserrole = document.getElementById('topbar-userrole');
    if (!topbarUsername || !topbarUserrole) return;

    const userDropdownTrigger = document.getElementById('userDropdownTrigger');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (currentLoggedInUser) {
        // Obtener los detalles del staff (nombre) asociados al usuario logueado
        const staffMember = staff.find(s => s.id === currentLoggedInUser.staffId);
        if (staffMember) {
            topbarUsername.textContent = staffMember.name;
            topbarUserrole.textContent = currentLoggedInUser.role;
        } else {
            topbarUsername.textContent = currentLoggedInUser.username;
            topbarUserrole.textContent = currentLoggedInUser.role;
        }
    } else {
        topbarUsername.textContent = 'Invitado';
        topbarUserrole.textContent = 'Sin Sesión';
    }

    // Toggle del menú desplegable
    if (userDropdownTrigger && userDropdownMenu) {
        userDropdownTrigger.addEventListener('click', (event) => {
            event.stopPropagation(); // Evita que se cierre inmediatamente por el document click
            userDropdownMenu.classList.toggle('hidden');
            lucide.createIcons(); // Para cualquier ícono nuevo en el menú desplegable
        });
        document.addEventListener('click', (event) => {
            if (!userDropdownMenu.contains(event.target) && !userDropdownTrigger.contains(event.target)) {
                userDropdownMenu.classList.add('hidden');
            }
        });
    }
    lucide.createIcons(); // Asegurar que los íconos se rendericen
}

/**
 * Abre un modal para que el usuario logueado gestione su perfil.
 */
async function openUserProfileModal() {
    if (!currentLoggedInUser) {
        AppUtils.showAlert('Acceso Denegado', 'No hay un usuario logueado para editar su perfil.', 'error');
        return;
    }
    const staffMember = staff.find(s => s.id === currentLoggedInUser.staffId);
    const user = currentLoggedInUser;

    Swal.fire({
        title: `Mi Perfil (${user.username})`,
        html: `
            <div class="text-left space-y-4">
                <p class="text-xs text-slate-500 uppercase">Información Personal</p>
                <input id="profile-name" class="w-full p-2 border rounded-lg uppercase" placeholder="Nombre completo" value="${staffMember?.name || ''}">
                <input id="profile-phone" class="w-full p-2 border rounded-lg" placeholder="Teléfono" value="${staffMember?.phone || ''}">
                <input id="profile-email" type="email" class="w-full p-2 border rounded-lg" placeholder="Correo electrónico" value="${staffMember?.email || ''}">
                <input id="profile-address" class="w-full p-2 border rounded-lg" placeholder="Dirección" value="${staffMember?.address || ''}">

                <hr class="my-4 border-t border-slate-200">
                <p class="text-xs text-slate-500 uppercase">Credenciales de Acceso</p>
                <input id="profile-username" class="w-full p-2 border rounded-lg" placeholder="Nombre de usuario" value="${user.username}" readonly>
                <input id="profile-current-password" type="password" class="w-full p-2 border rounded-lg" placeholder="Contraseña actual (solo para cambiar)">
                <input id="profile-new-password" type="password" class="w-full p-2 border rounded-lg" placeholder="Nueva Contraseña">
                <input id="profile-confirm-new-password" type="password" class="w-full p-2 border rounded-lg" placeholder="Confirmar Nueva Contraseña">
            </div>
        `,
        confirmButtonColor: '#39FF14',
        confirmButtonText: '<span class="text-black font-bold">Guardar Cambios</span>',
        showCancelButton: true,
        didOpen: () => {
            // Ocultar el menú desplegable al abrir el modal
            document.getElementById('userDropdownMenu')?.classList.add('hidden');
        },
        preConfirm: async () => {
            const name = document.getElementById('profile-name').value.toUpperCase();
            const phone = document.getElementById('profile-phone').value;
            const email = document.getElementById('profile-email').value;
            const address = document.getElementById('profile-address').value;
            const currentPassword = document.getElementById('profile-current-password').value;
            const newPassword = document.getElementById('profile-new-password').value;
            const confirmNewPassword = document.getElementById('profile-confirm-new-password').value;

            // Validaciones básicas
            if (!name || !phone || !email) {
                Swal.showValidationMessage('Por favor, complete los campos obligatorios de información personal.');
                return false;
            }

            if (newPassword && newPassword !== confirmNewPassword) {
                Swal.showValidationMessage('La nueva contraseña y su confirmación no coinciden.');
                return false;
            }
            // En un sistema real, aquí se debería verificar currentPassword con el hash almacenado
            // Por ahora, solo se guarda si se proporcionan las nuevas contraseñas.

            return { name, phone, email, address, currentPassword, newPassword };
        }
    }).then(async result => saveUserProfile(result));
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
    // Añadir campos de email y address a los empleados por defecto
    const defaultStaff = [
        { id: 'STAFF-1', name: 'ADMINISTRADOR PRINCIPAL', role: 'Administrador', phone: '3001234567', email: 'admin@tallerpro.com', address: 'Calle 10 #20-30, Medellín' },
        { id: 'STAFF-2', name: 'MECÁNICO JEFE', role: 'Mecánico', phone: '3019876543', email: 'mecanico@tallerpro.com', address: 'Avenida Siempre Viva 742, Bogotá' }
    ];
    await AppUtils.saveData('inventory_db', defaultInventory);
    inventory = defaultInventory;

    const defaultSales = [
        // Añadir una venta de ejemplo con placa y modelo

        { id: Date.now(), fecha: new Date().toISOString(), carModel: 'DEMO VEHÍCULO', total: 50000 }
    ];
    await AppUtils.saveData('sales_db', defaultSales);

    const defaultConfig = [{ name: 'Workshop Pro', nit: '00000000', iva: 19, address: 'Dirección taller' }];
    await AppUtils.saveData('company_db', defaultConfig);

    // Añadir un usuario administrador por defecto para el empleado por defecto
    await AppUtils.saveData('staff_db', defaultStaff); // Guardar staff por defecto
    const defaultUsers = [ // El usuario por defecto debe coincidir con el staff por defecto
        { id: 'USR-1', staffId: 'STAFF-1', username: 'admin', password: 'password123', role: 'Administrador' }
    ];
    await AppUtils.saveData('users_db', defaultUsers);
}

async function initInventory() {
    const tableEl = document.getElementById('inventoryTable');
    if (!tableEl) return; // No inicializar si no estamos en la vista de inventario

    inventoryTable = $(tableEl).DataTable({
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
    const tableEl = document.getElementById('salesTable');
    if (!tableEl) return;

    const sales = await AppUtils.loadData('sales_db');
    salesTable = $(tableEl).DataTable({
        data: sales,
        order: [[1, 'desc']],
        columns: [
            { data: 'id', render: (data) => `<span class="font-mono">#${data.toString().slice(-6)}</span>` },
            {
                data: 'fecha',
                render: (data) => new Date(data).toLocaleString('es-CO', { dateStyle: 'medium', timeStyle: 'short', hour12: true })
            },
            {
                data: null,
                render: (row) => `
                    <div class="flex flex-col">
                        <span class="font-bold text-xs">${row.placa || 'SIN PLACA'}</span>
                        <span class="text-[10px] text-slate-400 uppercase">${row.carModel || 'N/A'}</span>
                    </div>
                `
            },
            {
                data: 'items',
                render: (data) => data ? data.length : 0
            },
            {
                data: 'total',
                render: (data) => `<span class="font-bold text-navy-blue">${AppUtils.formatCurrency(data)}</span>`
            },
            {
                data: null,
                render: (data, type, row) => `
                    <div class="flex gap-2">
                        <button onclick="viewSaleDetail(${row.id})" 
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

/**
 * Muestra un modal con el detalle de los productos/servicios de una venta
 */
async function viewSaleDetail(id) {
    const sales = await AppUtils.loadData('sales_db');
    const sale = sales.find(s => String(s.id) === String(id));
    if (!sale) return;

    const itemsHtml = sale.items && sale.items.length > 0
        ? sale.items.map(item => `
            <div class="flex justify-between items-center py-2 border-b border-slate-100 last:border-0">
                <div class="text-left">
                    <p class="font-bold text-slate-800 text-xs">${item.name}</p>
                    <p class="text-[10px] text-slate-500">${item.quantity} x ${AppUtils.formatCurrency(item.price)}</p>
                </div>
                <span class="font-bold text-navy-blue text-xs">${AppUtils.formatCurrency(item.price * item.quantity)}</span>
            </div>
        `).join('')
        : '<p class="text-slate-400 italic text-center py-4">No hay detalles disponibles para esta venta.</p>';

    Swal.fire({
        title: `Detalle de Venta #${String(id).slice(-6)}`,
        html: `
            <div class="text-left mb-4 bg-slate-50 p-3 rounded-lg border border-slate-200">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Vehículo</p>
                <p class="font-bold text-navy-blue text-sm">${sale.placa || 'SIN PLACA'} - ${sale.carModel || 'N/A'}</p>
                <p class="text-[10px] text-slate-400 mt-1">${new Date(sale.fecha).toLocaleString('es-CO', { hour12: true })}</p>
            </div>
            <div class="max-h-60 overflow-y-auto pr-2">
                ${itemsHtml}
            </div>
            <div class="mt-4 pt-3 border-t-2 border-slate-100 text-right">
                <p class="text-lg font-bold text-navy-blue">Total: ${AppUtils.formatCurrency(sale.total)}</p>
            </div>
        `,
        confirmButtonColor: '#0b1120',
        confirmButtonText: 'Cerrar'
    });
}

async function renderDashboardCards() {
    const container = document.getElementById('dashboard-cards');
    if (!container) return;

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
            const name = document.getElementById('swal-name').value.trim().toUpperCase();
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
    const tableEl = document.getElementById('clientsTable');
    if (!tableEl) return;

    const clients = await AppUtils.loadData('clients_db');
    clientsTable = $(tableEl).DataTable({
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
            return { // All text inputs are converted to uppercase
                id: document.getElementById('c-id').value.toUpperCase(),
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
    const tableEl = document.getElementById('staffTable');
    if (!tableEl) return;

    const staff = await AppUtils.loadData('staff_db'); // Cargar datos de empleados
    const users = await AppUtils.loadData('users_db'); // Cargar datos de usuarios

    // Unir datos de empleados con datos de usuario
    const staffWithUsers = staff.map(s => {
        const user = users.find(u => u.staffId === s.id);
        return {
            ...s,
            hasUser: !!user, // Booleano: true si tiene usuario, false si no
            username: user ? user.username : '',
            userRole: user ? user.role : ''
        };
    });

    staffTable = $(tableEl).DataTable({
        data: staffWithUsers, // Usar los datos enriquecidos
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'role' }, // Rol de trabajo del empleado
            { data: 'phone' },
            { data: 'hasUser', render: (data) => data ? '<span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Sí</span>' : '<span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-xs font-bold">No</span>' },
            { data: 'userRole', render: (data) => data || 'N/A' }, // Rol de acceso al sistema
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
    const staff = await AppUtils.loadData('staff_db'); // Cargar empleados
    const users = await AppUtils.loadData('users_db'); // Cargar usuarios

    const member = id ? staff.find(s => s.id === id) : { id: '', name: '', role: 'Mecánico', phone: '' };
    const user = id ? users.find(u => u.staffId === member.id) : null;

    Swal.fire({
        title: id ? 'Editar Empleado' : 'Nuevo Empleado',
        html: `
            <div class="text-left space-y-4">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Documento de Identidad</label>
                <input id="s-id" class="w-full p-2 border rounded-lg" placeholder="Documento de Identidad" value="${member.id}" ${id ? 'readonly' : ''}>
                
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre completo</label>
                <input id="s-name" class="w-full p-2 border rounded-lg uppercase" placeholder="Nombre completo" value="${member.name}">
                
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cargo del Empleado</label>
                <select id="s-role" class="w-full p-2 border rounded-lg">
                    <option value="Mecánico" ${member.role == 'Mecánico' ? 'selected' : ''}>Mecánico</option>
                    <option value="Administrador" ${member.role == 'Administrador' ? 'selected' : ''}>Administrador</option>
                    <option value="Ayudante" ${member.role == 'Ayudante' ? 'selected' : ''}>Ayudante</option>
                </select>
                
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Teléfono</label>
                <input id="s-phone" class="w-full p-2 border rounded-lg" placeholder="Teléfono" value="${member.phone}">

                <hr class="my-4 border-t border-slate-200">
                <h4 class="font-bold text-slate-700 mb-2">Gestión de Usuario (Acceso al Sistema)</h4>
                <div class="flex items-center mb-2">
            <input type="checkbox" id="s-has-user" class="mr-2 h-4 w-4 text-navy-blue border-gray-300 rounded focus:ring-navy-blue" ${user ? 'checked' : ''}>
                    <label for="s-has-user" class="text-sm text-slate-600">Crear/Gestionar Usuario para este empleado</label>
                </div>

                <div id="user-fields" class="space-y-3 ${user ? '' : 'hidden'}">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre de Usuario</label>
                        <input id="s-username" class="w-full p-2 border rounded-lg" placeholder="ej: jdoe" value="${user ? user.username : ''}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Contraseña ${user ? '(Dejar vacío para no cambiar)' : ''}</label>
                        <input id="s-password" type="password" class="w-full p-2 border rounded-lg" placeholder="********">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Confirmar Contraseña</label>
                        <input id="s-confirm-password" type="password" class="w-full p-2 border rounded-lg" placeholder="********">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Rol de Acceso</label>
                        <select id="s-user-role" class="w-full p-2 border rounded-lg">
                            <option value="Administrador" ${user && user.role === 'Administrador' ? 'selected' : ''}>Administrador</option>
                            <option value="Mecánico" ${user && user.role === 'Mecánico' ? 'selected' : ''}>Mecánico</option>
                            <option value="Empleado" ${user && user.role === 'Empleado' ? 'selected' : ''}>Empleado</option>
                        </select>
                    </div>
                </div>
            </div>
        `,
        confirmButtonColor: '#39FF14',
        confirmButtonText: '<span class="text-black font-bold">Guardar</span>',
        showCancelButton: true,
        didOpen: () => {
            const hasUserCheckbox = document.getElementById('s-has-user');
            const userFieldsDiv = document.getElementById('user-fields');
            const toggleUserFields = () => {
                userFieldsDiv.classList.toggle('hidden', !hasUserCheckbox.checked);
            };
            hasUserCheckbox.addEventListener('change', toggleUserFields);
        },
        preConfirm: async () => {
            const staffId = document.getElementById('s-id').value.toUpperCase();
            const staffName = document.getElementById('s-name').value.toUpperCase();
            const staffRole = document.getElementById('s-role').value; // Rol de trabajo del empleado
            const staffPhone = document.getElementById('s-phone').value;
            const hasUser = document.getElementById('s-has-user').checked;

            if (!staffId || !staffName || !staffRole || !staffPhone) {
                Swal.showValidationMessage('Por favor, complete todos los campos del empleado.');
                return false;
            }

            // Verificar si el ID de empleado ya existe al crear uno nuevo
            if (!id && staff.some(s => s.id === staffId)) {
                Swal.showValidationMessage('Ya existe un empleado con este Documento de Identidad.');
                return false;
            }

            let userData = null;
            let existingUser = users.find(u => u.staffId === staffId);

            if (hasUser) {
                const username = document.getElementById('s-username').value.trim();
                const password = document.getElementById('s-password').value;
                const confirmPassword = document.getElementById('s-confirm-password').value;
                const userAccessRole = document.getElementById('s-user-role').value; // Rol de acceso al sistema

                if (!username || !userAccessRole) {
                    Swal.showValidationMessage('Por favor, complete el nombre de usuario y el rol de acceso.');
                    return false;
                }

                // Verificar nombre de usuario duplicado (excluyendo al usuario actual si se está editando)
                if (users.some(u => u.username === username && u.staffId !== staffId)) {
                    Swal.showValidationMessage('Este nombre de usuario ya está en uso.');
                    return false;
                }

                if (!existingUser) { // Si se está creando un nuevo usuario
                    if (!password || !confirmPassword) {
                        Swal.showValidationMessage('Por favor, ingrese y confirme la contraseña para el nuevo usuario.');
                        return false;
                    }
                }

                if (password !== confirmPassword) {
                    Swal.showValidationMessage('Las contraseñas no coinciden.');
                    return false;
                }

                userData = {
                    id: existingUser ? existingUser.id : `USR-${Date.now()}`, // Mantener ID existente o generar uno nuevo
                    staffId: staffId,
                    username: username,
                    role: userAccessRole
                };
                if (password) { // Solo actualizar contraseña si se proporcionó una nueva
                    userData.password = password; // NOTA: En una aplicación real, ¡esto debería ser un hash!
                } else if (existingUser) { // Si se está editando un usuario existente y la contraseña está vacía, mantener la antigua
                    userData.password = existingUser.password;
                } else { // Nuevo usuario, la contraseña es obligatoria
                    Swal.showValidationMessage('Por favor, ingrese una contraseña para el nuevo usuario.');
                    return false;
                }
            } else if (existingUser) {
                // Si el checkbox está desmarcado pero el empleado tiene un usuario, preguntar si se desea eliminar el usuario
                const confirmDeleteUser = await AppUtils.confirmAction(
                    '¿Eliminar Usuario?',
                    `El empleado ${staffName} tiene un usuario asociado. ¿Desea eliminar también su cuenta de acceso al sistema?`,
                    () => { }, // No hay acción directa aquí, el resultado se maneja en el .then
                    'warning',
                    'Sí, eliminar usuario',
                    '#ef4444'
                );
                if (!confirmDeleteUser.isConfirmed) {
                    return false; // El usuario canceló la eliminación de la cuenta de usuario
                }
                // Si se confirma, userData seguirá siendo null, lo que indicará la eliminación
            }

            return { staff: { id: staffId, name: staffName, role: staffRole, phone: staffPhone }, user: userData };
        }
    }).then(async result => {
        if (result.isConfirmed) {
            const { staff: newStaffData, user: newUserData } = result.value;

            // Actualizar staff_db
            let updatedStaff = staff.filter(s => s.id !== newStaffData.id);
            updatedStaff.push(newStaffData);
            await AppUtils.saveData('staff_db', updatedStaff);

            // Actualizar users_db
            let updatedUsers = users.filter(u => u.staffId !== newStaffData.id); // Eliminar datos de usuario antiguos para este staffId
            if (newUserData) { // Si se proporcionaron datos de usuario (checkbox marcado)
                updatedUsers.push(newUserData);
            }
            await AppUtils.saveData('users_db', updatedUsers);
            this.users = updatedUsers; // Actualizar la variable global de usuarios

            await refreshUI();
            AppUtils.showToast('Empleado y usuario actualizados correctamente', 'success');
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
    const nameEl = document.getElementById('config-name');
    if (!nameEl) return; // Salir si no estamos en la vista de configuración

    nameEl.value = config.name || '';
    if (document.getElementById('config-nit')) document.getElementById('config-nit').value = config.nit || '';
    if (document.getElementById('config-iva')) document.getElementById('config-iva').value = config.iva || 0;
    if (document.getElementById('config-address')) document.getElementById('config-address').value = config.address || '';
}

async function saveCompanySettings(e) {
    e.preventDefault();
    const newConfig = {
        name: document.getElementById('config-name').value,
        nit: document.getElementById('config-nit').value.toUpperCase(),
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
    const pendingIcon = document.getElementById('pending-bills-icon');
    const ordersActiveEl = document.getElementById('dash-orders-active');
    if (!container) return;

    const drafts = await AppUtils.loadData('drafts_db');

    // Actualizar contador de órdenes activas (borradores) en el resumen superior
    if (ordersActiveEl) ordersActiveEl.textContent = drafts.length;

    // Verificar si hay facturas con más de 2 horas de antigüedad
    const now = new Date();
    const twoHoursInMs = 2 * 60 * 60 * 1000;
    const hasUrgentDrafts = drafts.some(d => d.date && (now - new Date(d.date)) > twoHoursInMs);

    // Lógica para activar/desactivar la vibración neón en el icono del dashboard
    if (pendingIcon) {
        pendingIcon.classList.remove('clock-pending-alert', 'clock-pending-urgent');
        if (drafts.length > 0) {
            const alertClass = hasUrgentDrafts ? 'clock-pending-urgent' : 'clock-pending-alert';
            pendingIcon.classList.add(alertClass);
        }
    }

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
        const draftDate = new Date(d.date);
        const diffMs = now - draftDate;
        const isUrgent = d.date && diffMs > twoHoursInMs;

        // Colores dinámicos según la antigüedad
        const borderColor = isUrgent ? 'border-red-500' : 'border-blue-500';
        const bgHighlight = isUrgent ? 'bg-red-50/50' : '';
        const badgeColor = isUrgent ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700';

        // Mapeo de estados a etiquetas visuales
        const statusMap = {
            'RECEPCION': { label: 'RECEPCIÓN', color: 'bg-slate-100 text-slate-600' },
            'DIAGNOSTICO': { label: 'DIAGNÓSTICO', color: 'bg-amber-100 text-amber-700' },
            'REPARACION': { label: 'REPARACIÓN', color: 'bg-orange-100 text-orange-700' },
            'ESPERA_REPUESTOS': { label: 'ESPERA REP.', color: 'bg-rose-100 text-rose-700' },
            'LISTO': { label: 'LISTO', color: 'bg-emerald-100 text-emerald-700' }
        };
        const statusInfo = statusMap[d.status] || statusMap['RECEPCION'];

        // Cálculo de tiempo transcurrido para la etiqueta
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        let timeLabel = "";
        if (diffDays >= 1) {
            timeLabel = `${diffDays}d ${diffHours % 24}h`;
        } else if (diffHours >= 1) {
            timeLabel = `${diffHours}h ${diffMins % 60}m`;
        } else {
            timeLabel = `${diffMins}m`;
        }

        const subtotal = d.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const total = subtotal * 1.19; // IVA incluido para visualización
        return `
            <div onclick="resumeBillFromDashboard('${d.id}')" 
                 class="glass-card p-5 rounded-xl border-l-4 ${borderColor} ${bgHighlight} hover:shadow-xl transition-all cursor-pointer group relative overflow-hidden">
                ${isUrgent ? `<div class="absolute top-0 right-0 bg-red-500 text-white text-[7px] font-bold px-1.5 py-0.5 rounded-bl-lg animate-pulse">URGENTE</div>` : ''}
                <div class="flex justify-between items-start mb-3">
                    <div class="flex flex-col gap-1">
                        <span class="${badgeColor} text-[10px] font-bold px-2 py-1 rounded w-fit">#${d.id}</span>
                        <span class="${statusInfo.color} text-[8px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter border border-black/5">${statusInfo.label}</span>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-[10px] text-slate-400 font-medium">${draftDate.toLocaleDateString()}</span>
                        <span class="text-[9px] ${isUrgent ? 'text-red-600 font-bold' : 'text-slate-400'} flex items-center gap-1">
                            <i data-lucide="clock" class="w-2.5 h-2.5"></i> Hace ${timeLabel}
                        </span>
                    </div>
                </div>
                <h4 class="font-bold text-slate-800 uppercase truncate group-hover:${isUrgent ? 'text-red-600' : 'text-blue-600'} transition-colors">
                    ${d.placa ? `<span class="bg-navy-blue text-white px-1.5 py-0.5 rounded text-[10px] mr-1">${d.placa}</span>` : ''}
                    ${d.carModel || 'SIN MODELO'}
                </h4>
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
    const expenses = await AppUtils.loadData('expenses_db');

    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();

    const monthlyExpenses = expenses.filter(e => {
        const d = new Date(e.date);
        return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
    }).reduce((sum, e) => sum + (e.amount || 0), 0);

    const totalFacturado = sales.reduce((sum, sale) => sum + (sale.total || 0), 0);
    const balanceNeto = totalFacturado - monthlyExpenses;
    const totalVentas = sales.length;
    const ticketPromedio = totalVentas > 0 ? totalFacturado / totalVentas : 0;

    const stats = [
        { label: 'Ingresos Totales', value: AppUtils.formatCurrency(totalFacturado), color: 'text-blue-600', border: 'border-blue-600', icon: 'trending-up' },
        { label: 'Gastos de este Mes', value: AppUtils.formatCurrency(monthlyExpenses), color: 'text-red-600', border: 'border-red-600', icon: 'trending-down' },
        { label: 'Balance Neto (Mes)', value: AppUtils.formatCurrency(balanceNeto), color: 'text-emerald-600', border: 'border-emerald-600', icon: 'wallet' }
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
    updateSummaryCards(sales);
}

/**
 * Actualiza los contadores de resumen en la parte superior del dashboard
 */
function updateSummaryCards(sales) {
    const salesTodayEl = document.getElementById('dash-sales-today');
    if (!salesTodayEl) return;

    const todayStr = new Date().toISOString().split('T')[0];
    const todaySales = sales.filter(s => s.fecha && s.fecha.startsWith(todayStr))
        .reduce((sum, s) => sum + (s.total || 0), 0);

    salesTodayEl.textContent = AppUtils.formatCurrency(todaySales);
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
    const labels = lastSales.map(s => new Date(s.fecha).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', hour12: true }));
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
    const tableEl = document.getElementById('suppliersTable');
    if (!tableEl) return;

    const suppliers = await AppUtils.loadData('suppliers_db'); // This was already correct
    suppliersTable = $(tableEl).DataTable({
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
    const tableEl = document.getElementById('purchasesTable');
    if (!tableEl) return;

    const purchases = await AppUtils.loadData('purchases_db'); // This was already correct
    purchasesTable = $(tableEl).DataTable({
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
                    ${(row.total - row.paid) > 0 ? `<button onclick="openRecordPaymentModal('${row.id}')" class="p-1 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Registrar Pago"><i data-lucide="dollar-sign" class="w-4 h-4"></i></button>` : ''}
                    <button onclick="genericDelete('${row.id}', 'purchases_db', 'Compra')" class="p-1 text-slate-400 hover:text-red-500 transition-colors" title="Eliminar Compra"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                `
            }
        ],
        responsive: true,
        drawCallback: () => lucide.createIcons()
    });
}

/** GESTIÓN DE GASTOS */
async function initExpenses() {
    const tableEl = document.getElementById('expensesTable');
    if (!tableEl) return;

    const expenses = await AppUtils.loadData('expenses_db');
    expensesTable = $(tableEl).DataTable({
        data: expenses,
        order: [[0, 'desc']],
        columns: [
            {
                data: 'date',
                render: d => new Date(d).toLocaleDateString('es-CO', { dateStyle: 'medium' })
            },
            { data: 'description' },
            { data: 'category' },
            {
                data: 'amount',
                render: d => `<span class="text-red-600 font-bold">${AppUtils.formatCurrency(d)}</span>`
            },
            {
                data: null,
                render: (data, type, row) => `
                    <button onclick="genericDelete('${row.id}', 'expenses_db', 'Gasto')" class="p-1 text-slate-400 hover:text-red-500 transition-colors">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                `
            }
        ],
        responsive: true,
        language: {
            search: "Buscar gasto:",
            emptyTable: "No hay gastos registrados en el taller",
            zeroRecords: "No se encontraron coincidencias"
        },
        drawCallback: () => lucide.createIcons()
    });
}

async function openExpenseModal() {
    Swal.fire({
        title: 'Registrar Gasto del Taller',
        html: `
            <div class="text-left space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Descripción del Gasto</label>
                    <input id="ex-desc" class="w-full p-2 border rounded-lg uppercase" placeholder="EJ: PAGO SERVICIO LUZ">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Categoría</label>
                    <select id="ex-cat" class="w-full p-2 border rounded-lg">
                        <option value="Servicios">Servicios Públicos</option>
                        <option value="Arriendo">Arriendo</option>
                        <option value="Nómina">Nómina</option>
                        <option value="Insumos">Insumos Taller</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Monto (COP)</label>
                    <input id="ex-amount" type="number" class="w-full p-2 border rounded-lg" placeholder="0">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha</label>
                    <input id="ex-date" type="date" class="w-full p-2 border rounded-lg" value="${new Date().toISOString().split('T')[0]}">
                </div>
            </div>
        `,
        confirmButtonColor: '#ff4444',
        confirmButtonText: 'Registrar Gasto',
        showCancelButton: true,
        preConfirm: () => {
            const description = document.getElementById('ex-desc').value.trim().toUpperCase();
            const amount = parseFloat(document.getElementById('ex-amount').value);
            if (!description || isNaN(amount) || amount <= 0) {
                return Swal.showValidationMessage('Complete todos los campos correctamente');
            }
            return { id: Date.now(), date: document.getElementById('ex-date').value, description, category: document.getElementById('ex-cat').value, amount };
        }
    }).then(async result => {
        if (result.isConfirmed) {
            const expenses = await AppUtils.loadData('expenses_db');
            expenses.push(result.value);
            await AppUtils.saveData('expenses_db', expenses);
            await refreshUI();
            AppUtils.showToast('Gasto registrado correctamente');
        }
    });
}

/**
 * Renderiza la vista previa de gastos del mes actual en el dashboard
 */
async function renderExpensesDashboard() {
    const container = document.getElementById('expenses-dashboard');
    if (!container) return;

    const expenses = await AppUtils.loadData('expenses_db');
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();

    // Filtrar y ordenar por fecha (más recientes primero)
    const monthlyExpenses = expenses.filter(e => {
        const d = new Date(e.date);
        return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
    }).sort((a, b) => new Date(b.date) - new Date(a.date));

    if (monthlyExpenses.length === 0) {
        container.innerHTML = `
            <div class="col-span-full glass-card p-8 rounded-xl text-center text-slate-400">
                <i data-lucide="wallet" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                <p class="italic font-medium">No se han registrado gastos para el mes de ${now.toLocaleString('es-ES', { month: 'long' }).toUpperCase()}.</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }

    container.innerHTML = monthlyExpenses.slice(0, 6).map(e => `
        <div class="glass-card p-4 rounded-xl border-l-4 border-red-500 flex justify-between items-center group hover:scale-[1.02] transition-transform cursor-default">
            <div class="truncate mr-4">
                <p class="text-[10px] text-slate-400 font-bold uppercase">${e.category}</p>
                <h4 class="font-bold text-slate-800 uppercase text-sm truncate group-hover:text-red-600 transition-colors">${e.description}</h4>
                <p class="text-[10px] text-slate-400">${new Date(e.date).toLocaleDateString()}</p>
            </div>
            <div class="text-right flex-shrink-0">
                <span class="font-bold text-red-600 text-lg">-${AppUtils.formatCurrency(e.amount)}</span>
            </div>
        </div>
    `).join('');
    lucide.createIcons();
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
            id: document.getElementById('p-id').value.toUpperCase(),
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
 * Abre un modal para registrar un pago a una compra de proveedor.
 * Permite abonar o pagar la totalidad del saldo pendiente.
 * @param {string|number} purchaseId - ID de la compra a la que se le registrará el pago.
 */
async function openRecordPaymentModal(purchaseId) {
    const purchases = await AppUtils.loadData('purchases_db');
    const purchase = purchases.find(p => String(p.id) === String(purchaseId));

    if (!purchase) {
        AppUtils.showAlert('Error', 'Compra no encontrada.', 'error');
        return;
    }

    const remainingBalance = purchase.total - purchase.paid;

    Swal.fire({
        title: `Registrar Pago para Compra #${purchaseId.toString().slice(-6)}`,
        html: `
            <div class="text-left space-y-4">
                <p class="text-sm text-slate-600">Proveedor: <b>${purchase.supplierName}</b></p>
                <p class="text-sm text-slate-600">Producto: <b>${purchase.productName}</b></p>
                <p class="text-sm text-slate-600">Total: <b>${AppUtils.formatCurrency(purchase.total)}</b></p>
                <p class="text-sm text-slate-600">Pagado: <b>${AppUtils.formatCurrency(purchase.paid)}</b></p>
                <p class="text-lg font-bold text-navy-blue">Saldo Pendiente: <b>${AppUtils.formatCurrency(remainingBalance)}</b></p>
                <hr class="my-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Monto a Pagar</label>
                    <input id="payment-amount" type="number" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="0.00" value="${remainingBalance}">
                </div>
            </div>
        `,
        confirmButtonColor: '#39FF14',
        confirmButtonText: '<span class="text-black font-bold">Registrar Pago</span>',
        showCancelButton: true,
        preConfirm: () => {
            const amount = parseFloat(document.getElementById('payment-amount').value);
            if (isNaN(amount) || amount <= 0) {
                Swal.showValidationMessage('Ingrese un monto válido mayor a cero.');
                return false;
            }
            if (amount > remainingBalance) {
                Swal.showValidationMessage(`El monto a pagar no puede exceder el saldo pendiente (${AppUtils.formatCurrency(remainingBalance)}).`);
                return false;
            }
            return amount;
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const paymentAmount = result.value;
            purchase.paid += paymentAmount;

            const index = purchases.findIndex(p => String(p.id) === String(purchaseId));
            if (index !== -1) {
                purchases[index] = purchase;
            }

            await AppUtils.saveData('purchases_db', purchases);
            await refreshUI();
            AppUtils.showToast('Pago registrado correctamente', 'success');
        }
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
            const name = document.getElementById('swal-name').value.trim().toUpperCase(); // Converted to uppercase
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