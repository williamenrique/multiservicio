/**
 * App Main Logic
 */

// Instancia global para control de DataTable y refresco de datos
// Variable global para el usuario logueado (se cargará de la DB)
let currentLoggedInUser = null;

document.addEventListener('DOMContentLoaded', async () => {
    initClock();
    initSidebar();
    initUserDropdown();

    // Cargar la información del usuario logueado directamente desde la base de datos
    await fetchLoggedInUserFromDB();

    renderTopBarUserInfo(); // Cargar info del usuario en la barra superior una vez que todo esté cargado

    // Solo activar intervalos si estamos en la vista de Dashboard
    if (document.getElementById('salesChart')) {
        await renderDashboardCards(); // Renderizado inicial
        await renderExpensesDashboard(); // Cargar mini-resumen de gastos
        // await renderPendingBillsDashboard(); // COMENTADO: Evita peticiones api?key=drafts_db
        setInterval(refreshUI, 10000); // Refresco global cada 10 segundos
    }

    // Inicializar tabla de gastos si estamos en la vista de gastos
    if (document.getElementById('expensesTable')) {
        await initExpenses();
    }
});

// Escuchar los botones de Atrás/Adelante del navegador
window.addEventListener('popstate', (event) => {
    // En un enfoque MVC tradicional, popstate generalmente recarga la página
    // o se maneja a nivel de servidor. Para este sistema, simplemente recargamos.
    window.location.reload();
});

/**
 * Inicializa el reloj digital de la barra superior con actualización cada segundo.
 */
function initClock() {
    const clockElement = document.getElementById('digitalClock');
    setInterval(() => {
        const now = new Date();
        clockElement.textContent = now.toLocaleTimeString('es-CO', { hour12: true });
    }, 1000);
}

/**
 * Obtiene la información del usuario actualmente autenticado desde el servidor y la almacena globalmente.
 */
// Se asume que esta función se llama al inicio para cargar currentLoggedInUser
async function fetchLoggedInUserFromDB() {
    try {
        const response = await fetch(`${URLROOT}/auth/getLoggedInUser`);
        if (response.ok) {
            const result = await response.json(); // La respuesta ya incluye un objeto 'user'
            if (result.success) currentLoggedInUser = result.user;
        }
    } catch (error) {
        console.error("Error al obtener sesión del usuario:", error);
        currentLoggedInUser = null;
    }
}

/**
 * Refresca la interfaz de usuario global, enfocándose en elementos que requieren actualización frecuente.
 * Esta función sirve como punto central para disparar refrescos en el Dashboard.
 */
async function refreshUI() {
    renderTopBarUserInfo(); // Actualizar info del usuario en la barra superior
    await renderDashboardCards();
    await renderFinancialCards();
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
 * Abre un modal de SweetAlert2 para que el usuario logueado pueda ver y gestionar su perfil.
 * Permite editar información personal y cambiar la contraseña.
 * (Nota: La lógica de guardado de perfil requeriría un endpoint API en el backend.)
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

/**
 * Renderiza las tarjetas de resumen del Dashboard (ej: Stock OK, Stock Crítico, Agotados, En Servicio).
 * Nota: Actualmente, estas estadísticas aún consumen de archivos JSON. Deberían migrarse a un Controller Dashboard en el futuro.
 */
async function renderDashboardCards() {
    const container = document.getElementById('dashboard-cards');
    if (!container) return;

    let statsData = { inventory: { ok: 0, critico: 0, agotado: 0 }, ingresosHoy: 0 };
    try {
        const response = await fetch(`${URLROOT}/dashboard/getStats`);
        if (response.ok) {
            const result = await response.json();
            statsData = result;
        }
    } catch (e) {
        console.error("Error al obtener estadísticas:", e);
    }

    const stats = [
        { label: 'Productos OK', value: statsData.inventory.ok, color: 'text-neon-green', border: 'border-neon-green', icon: 'check-circle', filter: 'Stock OK' },
        { label: 'Stock Crítico', value: statsData.inventory.critico, color: 'text-cat-yellow', border: 'border-cat-yellow', icon: 'alert-triangle', filter: 'Bajo Stock' },
        { label: 'Agotados', value: statsData.inventory.agotado, color: 'text-error-red', border: 'border-error-red', icon: 'alert-circle', filter: 'Agotado' },
        { label: 'Ingresos Hoy', value: AppUtils.formatCurrency(statsData.ingresosHoy), color: 'text-blue-500', border: 'border-blue-500', icon: 'trending-up', section: 'historial' }
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
/** renderPendingBillsDashboard ha sido desactivado temporalmente para migración SQL */

/**
 * Calcula y renderiza las estadísticas financieras en el dashboard
 */
async function renderFinancialCards() {
    const container = document.getElementById('financial-cards');
    if (!container) return;

    let statsData = { ingresosHoy: 0, gastosMes: 0 };
    try {
        const response = await fetch(`${URLROOT}/dashboard/getStats`);
        if (response.ok) statsData = await response.json();
    } catch (e) {
        console.error("Error cargando gastos para resumen:", e);
    }

    // Nota: Aquí podrías añadir una consulta para ingresos totales del mes si lo deseas.
    // Por ahora usaremos los datos que ya trae getStats.
    
    const monthlyExpenses = parseFloat(statsData.gastosMes) || 0;
    const totalFacturado = 0; // Aquí deberías sumar las ventas del mes en el controlador
    const balanceNeto = totalFacturado - monthlyExpenses;

    const stats = [
        { label: 'Ventas de Hoy', value: AppUtils.formatCurrency(statsData.ingresosHoy), color: 'text-blue-600', border: 'border-blue-600', icon: 'trending-up' },
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
}

/** GESTIÓN DE GASTOS */
async function initExpenses() {
    const tableEl = document.getElementById('expensesTable');
    if (!tableEl) return;

    try {
        const response = await fetch(`${URLROOT}/gastos/listar`);
        const expenses = await response.json();

        expensesTable = $(tableEl).DataTable({
            data: expenses,
            order: [[0, 'desc']],
            columns: [
                {
                    data: 'fecha',
                    render: d => new Date(d).toLocaleDateString('es-CO', { dateStyle: 'medium' })
                },
                { data: 'descripcion' },
                { data: 'categoria' },
                {
                    data: 'monto',
                    render: d => `<span class="text-red-600 font-bold">${AppUtils.formatCurrency(d)}</span>`
                },
                {
                    data: null,
                    render: (data, type, row) => `
                        <button onclick="eliminarGasto('${row.id}')" class="p-1 text-slate-400 hover:text-red-500 transition-colors">
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
    } catch (e) {
        console.error("Error inicializando tabla de gastos:", e);
    }
}

/**
 * Abre el modal para registrar un nuevo gasto operativo del taller.
 */
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
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Método de Pago</label>
                    <select id="ex-method" class="w-full p-2 border rounded-lg">
                        <option value="EFECTIVO">Efectivo</option>
                        <option value="TRANSFERENCIA">Transferencia</option>
                        <option value="TARJETA">Tarjeta</option>
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
            </div>`,
        confirmButtonColor: '#ff4444',
        confirmButtonText: '<span class="font-bold">Registrar Gasto</span>',
        showCancelButton: true,
        preConfirm: () => {
            const descripcion = document.getElementById('ex-desc').value.trim().toUpperCase();
            const monto = parseFloat(document.getElementById('ex-amount').value);
            const fecha = document.getElementById('ex-date').value;
            const categoria = document.getElementById('ex-cat').value;
            const metodo_pago = document.getElementById('ex-method').value;

            if (!descripcion || isNaN(monto) || monto <= 0) {
                return Swal.showValidationMessage('Complete todos los campos correctamente');
            }
            return { fecha, descripcion, categoria, monto, metodo_pago };
        }
    }).then(async result => {
        if (result.isConfirmed) {
            const response = await fetch(`${URLROOT}/gastos/guardar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(result.value)
            });
            const data = await response.json();
            if (data.success) {
                AppUtils.showToast('Gasto registrado');
                await refreshUI();
                if (typeof expensesTable !== 'undefined') {
                    const res = await fetch(`${URLROOT}/gastos/listar`);
                    const newList = await res.json();
                    expensesTable.clear().rows.add(newList).draw();
                }
            }
        }
    });
}

/**
 * Renderiza la vista previa de gastos del mes actual en el dashboard
 */
async function renderExpensesDashboard() {
    const container = document.getElementById('expenses-dashboard');
    if (!container) return;

    let expenses = [];
    try {
        const response = await fetch(`${URLROOT}/gastos/listar`);
        if (response.ok) expenses = await response.json();
    } catch (e) {
        console.error("Error en mini-dashboard de gastos:", e);
    }

    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();

    // Filtrar y ordenar por fecha (más recientes primero)
    const monthlyExpenses = expenses.filter(e => {
        const d = new Date(e.fecha);
        return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
    }).sort((a, b) => new Date(b.fecha) - new Date(a.fecha));

    if (monthlyExpenses.length === 0) {
        container.innerHTML = `
            <div class="col-span-full glass-card p-8 rounded-xl text-center text-slate-400">
                <i data-lucide="wallet" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                <p class="italic font-medium">No se han registrado gastos para el mes de ${now.toLocaleString('es-ES', { month: 'long' }).toUpperCase()}.</p>
            </div>`;
        lucide.createIcons();
        return;
    }

    container.innerHTML = monthlyExpenses.slice(0, 6).map(e => `
        <div class="glass-card p-4 rounded-xl border-l-4 border-red-500 flex justify-between items-center group hover:scale-[1.02] transition-transform cursor-default">
            <div class="truncate mr-4">
                <p class="text-[10px] text-slate-400 font-bold uppercase">${e.categoria}</p>
                <h4 class="font-bold text-slate-800 uppercase text-sm truncate group-hover:text-red-600 transition-colors">${e.descripcion}</h4>
                <p class="text-[10px] text-slate-400">${new Date(e.fecha).toLocaleDateString()}</p>
            </div>
            <div class="text-right flex-shrink-0">
                <span class="font-bold text-red-600 text-lg">-${AppUtils.formatCurrency(parseFloat(e.monto) || 0)}</span>
            </div>
        </div>
    `).join('');
    lucide.createIcons();
}

/**
 * Elimina un gasto de la base de datos
 */
window.eliminarGasto = (id) => {
    AppUtils.confirmAction('¿Eliminar gasto?', 'Esta acción no se puede deshacer.', async () => {
        const response = await fetch(`${URLROOT}/gastos/eliminar/${id}`, { method: 'DELETE' });
        const data = await response.json();
        if (data.success) {
            AppUtils.showToast('Gasto eliminado');
            await refreshUI();
            if (typeof expensesTable !== 'undefined') {
                const res = await fetch(`${URLROOT}/gastos/listar`);
                const newList = await res.json();
                expensesTable.clear().rows.add(newList).draw();
            }
        }
    });
};