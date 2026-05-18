/**
 * App Main Logic
 */

// Instancia global para control de DataTable y refresco de datos
// Variable global para el usuario logueado (se cargará de la DB)
let currentLoggedInUser = null;

document.addEventListener('DOMContentLoaded', async () => {
    initClock();
    initSidebar();
    initUserDropdown(); // Inicializar el dropdown solo una vez

    // Cargar la información del usuario logueado directamente desde la base de datos
    await fetchLoggedInUserFromDB();

    renderTopBarUserInfo(); // Cargar info del usuario en la barra superior una vez que todo esté cargado

    // Solo activar intervalos si estamos en la vista de Dashboard
    if (document.getElementById('dashboard-cards')) {
        await renderDashboardCards(); // Renderizado inicial
        setInterval(renderDashboardCards, 5000);
        setInterval(renderFinancialCards, 5000);
        setInterval(renderPendingBillsDashboard, 5000);
        setInterval(renderSupplierDebtsDashboard, 5000);
        setInterval(renderExpensesDashboard, 5000);
    }
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

/**
 * Obtiene la información del usuario actualmente autenticado desde el servidor.
 */
async function fetchLoggedInUserFromDB() {
    try {
        const response = await fetch(`${URLROOT}/auth/getLoggedInUser`);
        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                currentLoggedInUser = result.user;
            }
        }
    } catch (error) {
        console.error("Error al obtener sesión del usuario:", error);
        currentLoggedInUser = null;
    }
}

async function refreshUI() {
    // This function should now only trigger dashboard-specific refreshes
    // as module-specific data loading is handled by their own JS files.
    // The dashboard cards will fetch their own data.

    renderTopBarUserInfo(); // Actualizar info del usuario en la barra superior
    await renderDashboardCards();
    await renderFinancialCards();
    await renderPendingBillsDashboard();
    await renderSupplierDebtsDashboard();
    await renderExpensesDashboard();
}

/**
 * Inicializa el comportamiento del menú desplegable del usuario una sola vez.
 */
/**
 * Inicializa el menú desplegable del usuario con lógica mejorada.
 */
function initUserDropdown() {
    const trigger = document.getElementById('userDropdownTrigger');
    const menu = document.getElementById('userDropdownMenu');

    if (!trigger || !menu) return;

    // Toggle al hacer clic en el botón
    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = menu.classList.contains('hidden');

        // Cerrar todos los demás dropdowns si los hubiera
        menu.classList.toggle('hidden', !isHidden);

        if (isHidden && window.lucide) {
            lucide.createIcons();
        }
    });

    // Cerrar al hacer clic fuera del menú o del botón
    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && !trigger.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });

    // Manejo de Logout con confirmación (usando la clase solicitada)
    document.addEventListener('click', (e) => {
        const logoutBtn = e.target.closest('.logout');
        if (logoutBtn) {
            e.preventDefault();
            const url = logoutBtn.href;
            AppUtils.confirmAction(
                '¿Cerrar Sesión?',
                'Tu sesión actual terminará.',
                () => window.location.href = url,
                'question',
                'Sí, salir',
                '#ef4444'
            );
        }
    });
}

/**
 * Renderiza el nombre y rol del usuario logueado en la barra superior.
 */
async function renderTopBarUserInfo() {
    const topbarUsername = document.getElementById('topbar-username');
    const topbarUserrole = document.getElementById('topbar-userrole');
    if (!topbarUsername || !topbarUserrole) return;

    if (currentLoggedInUser) {
        // Usar staffName si está disponible (viene de la DB), de lo contrario el username
        if (currentLoggedInUser.staffName) {
            topbarUsername.textContent = currentLoggedInUser.staffName;
        } else {
            topbarUsername.textContent = currentLoggedInUser.username;
        }
        topbarUserrole.textContent = currentLoggedInUser.role; // Rol del sistema
    } else {
        topbarUsername.textContent = 'Invitado';
        topbarUserrole.textContent = 'Sin Sesión';
    }
    if (window.lucide) lucide.createIcons(); // Asegurar que los íconos se rendericen
}

/**
 * Abre un modal para que el usuario logueado gestione su perfil.
 */
async function openUserProfileModal() {
    if (!currentLoggedInUser) {
        AppUtils.showAlert('Acceso Denegado', 'No hay un usuario logueado para editar su perfil.', 'error');
        return;
    }

    Swal.fire({
        title: `Mi Perfil (${currentLoggedInUser.username})`,
        html: `
            <div class="text-left space-y-4">
                <p class="text-xs text-slate-500 uppercase">Información Personal</p>
                <input id="profile-name" class="w-full p-2 border rounded-lg uppercase" placeholder="Nombre completo" value="${currentLoggedInUser.staffName || ''}">
                <input id="profile-phone" class="w-full p-2 border rounded-lg" placeholder="Teléfono" value="">
                <input id="profile-email" type="email" class="w-full p-2 border rounded-lg" placeholder="Correo electrónico" value="">
                <input id="profile-address" class="w-full p-2 border rounded-lg" placeholder="Dirección" value="">

                <hr class="my-4 border-t border-slate-200">
                <p class="text-xs text-slate-500 uppercase">Credenciales de Acceso</p>
                <input id="profile-username" class="w-full p-2 border rounded-lg" placeholder="Nombre de usuario" value="${currentLoggedInUser.username}" readonly>
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

async function renderDashboardCards() {
    const container = document.getElementById('dashboard-cards');
    if (!container) return;

    // Nota: Estas estadísticas aún consumen de JSON, deberían migrarse a un Controller Dashboard en el futuro
    const drafts = await AppUtils.loadData('drafts_db');
    const inventoryData = await AppUtils.loadData('inventory_db');

    // Calcular cuántos repuestos hay "reservados" en borradores
    const reservedUnits = drafts.reduce((total, draft) => {
        return total + draft.items.reduce((sum, item) => sum + (item.isService ? 0 : item.quantity), 0);
    }, 0);

    const stats = [
        { label: 'Productos OK', value: inventoryData.filter(p => p.stock > 5).length, color: 'text-neon-green', border: 'border-neon-green', icon: 'check-circle', filter: 'Stock OK' },
        { label: 'Stock Crítico', value: inventoryData.filter(p => p.stock <= 5 && p.stock > 0).length, color: 'text-cat-yellow', border: 'border-cat-yellow', icon: 'alert-triangle', filter: 'Bajo Stock' },
        { label: 'Agotados', value: inventoryData.filter(p => p.stock === 0).length, color: 'text-error-red', border: 'border-error-red', icon: 'alert-circle', filter: 'Agotado' },
        { label: 'En Servicio', value: reservedUnits, color: 'text-blue-500', border: 'border-blue-500', icon: 'clock', section: 'facturacion' }
    ];

    container.innerHTML = stats.map(s => `
        <div onclick="${s.filter ? `window.location.href='${URLROOT}/inventario'` : `window.location.href='${URLROOT}/${s.section}'`}" 
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
            <i data-lucide="${s.icon}" class="${s.color} w-10 h-10 opacity-20"></i>
        </div>
    `).join('');
    lucide.createIcons();
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
                    </button>`
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