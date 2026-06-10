/**
 * APP CORE - UNIFICADO Y LEGIBLE
 * Este archivo centraliza las utilidades, la gestión de sesión y la lógica principal de la UI.
 * Reemplaza a app.js y consolida las funciones de soporte del sistema.
 */

// =============================================================================
// 1. UTILIDADES DEL NÚCLEO (AppUtils)
// =============================================================================
const AppUtils = {
    /**
     * Muestra una alerta informativa o de éxito usando SweetAlert2.
     */
    showAlert: (title, text, icon = "success") =>
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            background: "#000000",
            color: "#ffffff",
            confirmButtonColor: "#39FF14",
            confirmButtonText: '<span style="color: #000; font-weight: 900; text-transform: uppercase;">Aceptar</span>',
            customClass: {
                popup: 'rounded-3xl border border-slate-800 shadow-[0_0_20px_rgba(57,255,20,0.2)]',
                title: 'text-white'
            }
        }),

    /**
     * Muestra una notificación rápida (Toast) en la parte superior derecha.
     */
    showToast: (msg, type = "success") => {
        if (typeof Toastify === 'function') {
            Toastify({
                text: msg,
                duration: 3000,
                gravity: "top",
                position: "right",
                style: {
                    background: "#000000",
                    color: "#ffffff",
                    borderRadius: "12px",
                    fontWeight: "900",
                    fontSize: "13px",
                    boxShadow: "0 0 20px rgba(57, 255, 20, 0.4)",
                    border: "1px solid rgba(57, 255, 20, 0.3)",
                    textTransform: "uppercase"
                },
            }).showToast();
        } else {
            // Fallback a SweetAlert2
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: type,
                title: msg,
                background: "#000000",
                color: "#ffffff",
                didOpen: (toast) => {
                    toast.style.borderRadius = "12px";
                    toast.style.boxShadow = "0 0 20px rgba(57, 255, 20, 0.4)";
                    toast.style.border = "1px solid rgba(57, 255, 20, 0.2)";
                }
            });
        }
    },

    /**
     * Muestra un cuadro de diálogo de confirmación antes de ejecutar una acción.
     */
    confirmAction: (title, text, onConfirm, icon = "warning", confirmText = "Sí, continuar", confirmColor = "#ef4444", cancelText = "Cancelar") =>
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: !0,
            background: "#000000",
            color: "#ffffff",
            confirmButtonColor: confirmColor || "#ef4444",
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            customClass: {
                popup: 'rounded-3xl border border-slate-800 shadow-[0_0_20px_rgba(57,255,20,0.2)]'
            }
        }).then((result) => {
            result.isConfirmed && onConfirm();
        }),

    /**
     * Formatea un número como moneda colombiana (COP).
     */
    formatCurrency: (amount) =>
        new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 2 }).format(amount),

    /**
     * Abre un visor de imagen a pantalla completa usando SweetAlert2.
     */
    viewImage: (url, title) => {
        Swal.fire({
            title: title,
            imageUrl: url,
            imageAlt: title,
            showCloseButton: !0,
            showConfirmButton: !1,
            background: "#000000",
            color: "#ffffff",
            customClass: {
                popup: 'rounded-3xl border border-slate-800 shadow-2xl'
            }
        });
    },

    /**
     * Muestra una pantalla de carga bloqueante.
     */
    showLoading: (msg = "Cargando...") => {
        Swal.fire({
            title: msg,
            background: "#000000",
            color: "#ffffff",
            allowOutsideClick: !1,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });
        // Failsafe: Si después de 20 segundos sigue cargando, cerrar por seguridad
        setTimeout(() => { if (Swal.isVisible() && Swal.isLoading()) Swal.close(); }, 20000);
    },

    hideLoading: () => {
        if (Swal.isVisible()) {
            Swal.close();
        }
    },
};

// =============================================================================
// 2. ESTADO GLOBAL DE LA APP
// =============================================================================
window.currentLoggedInUser = null;

// =============================================================================
// 3. INICIALIZACIÓN Y EVENTOS PRINCIPALES
// =============================================================================

document.addEventListener("DOMContentLoaded", async () => {
    initClock();
    initSidebar();
    initUserDropdown();
    initGlobalSearch();


    // Inyectar Estilos de Tooltips Personalizados (Negro y Blanco)
    const style = document.createElement('style');
    style.textContent = `
        .tippy-box[data-theme~='taller-dark'] {
            background-color: #000000 !important;
            color: #ffffff !important;
            border-radius: 8px !important;
            font-size: 11px !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
            border: 1px solid #334155 !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5) !important;
            opacity: 1 !important;
            text-align: center !important;
        }
        .tippy-box[data-theme~='taller-dark'] .tippy-content {
            padding: 6px 10px !important;
            opacity: 1 !important;
            color: #ffffff !important;
        }
        .tippy-box[data-theme~='taller-dark'] .tippy-arrow {
            color: #000000 !important;
            display: block !important;
        }
        @keyframes alert-shake {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(1.5deg); }
            50% { transform: rotate(0deg); }
            75% { transform: rotate(-1.5deg); }
            100% { transform: rotate(0deg); }
        }
        .alert-shake {
            display: inline-block !important;
            animation: alert-shake 0.4s ease-in-out infinite !important;
        }
    `;
    document.head.appendChild(style);

    await fetchLoggedInUserFromDB();

    // Notificar a otros módulos (como dashboard.js) que el usuario ya está cargado
    document.dispatchEvent(new CustomEvent("userLoaded", { detail: window.currentLoggedInUser }));

    // Inicializar funciones exclusivas de administrador
    const user = window.currentLoggedInUser;
    if (user && (parseInt(user.roleId) === 1 || user.role.toUpperCase() === "ADMINISTRADOR")) {
        initRecoveryNotifications();
        setInterval(initRecoveryNotifications, 30000); // Polling cada 30 segundos
        initWorkshopAlerts();
        setInterval(initWorkshopAlerts, 60000); // Chequeo de taller cada minuto
        initCreditNotifications();
        setInterval(initCreditNotifications, 60000); // Chequeo de cartera cada minuto
        initDebtorsCard();
        setInterval(initDebtorsCard, 300000); // Polling cada 5 minutos para la tarjeta de deudores
        initLowStockNotifications();
        setInterval(initLowStockNotifications, 120000); // Chequeo de stock cada 2 minutos
        initProfitabilityCard();
        setInterval(initProfitabilityCard, 600000); // Polling cada 10 minutos
    }

    if (user && user.role.toUpperCase() === "MECANICO") {
        initWorkshopAlerts();
        setInterval(initWorkshopAlerts, 60000); // Chequeo de taller cada minuto
    }

    renderTopBarUserInfo(); // Actualiza nombre y rol en la UI
    window.initGlobalTooltips(); // Inicialización inicial
});

/**
 * Inicializa tooltips de Tippy.js con el tema Taller Dark
 */
window.initGlobalTooltips = function () {
    if (typeof tippy === 'function') {
        // Seleccionamos todos los elementos con atributo title que no hayan sido inicializados
        const elements = document.querySelectorAll('[title]:not([data-tippy-content])');
        if (elements.length > 0) {
            tippy(elements, {
                theme: 'taller-dark',
                arrow: false,
                animation: 'scale',
                touch: false,
                inertia: true
            });
            elements.forEach(el => el.removeAttribute('title'));
        }
    }
};

/**
 * Escuchar los botones de navegación del navegador (Atrás/Adelante).
 */
window.addEventListener("popstate", () => {
    window.location.reload();
});

// =============================================================================
// 4. FUNCIONES DEL NÚCLEO
// =============================================================================

/**
 * Inicializa el reloj digital de la barra superior.
 */
function initClock() {
    const clockElement = document.getElementById("digitalClock");
    if (clockElement) {
        setInterval(() => {
            const now = new Date();
            clockElement.textContent = now.toLocaleTimeString("es-CO", { hour12: !0 });
        }, 1000);
    }
}

/**
 * Obtiene la información del usuario autenticado desde el servidor.
 */
async function fetchLoggedInUserFromDB() {
    try {
        const response = await fetch(`${URLROOT}/auth/getLoggedInUser`);
        if (response.ok) {
            const result = await response.json();
            if (result.success) window.currentLoggedInUser = result.user;
        }
    } catch (error) {
        console.error("Error al obtener sesión del usuario:", error);
        window.currentLoggedInUser = null;
    }
}

/**
 * Inicializa el menú desplegable del usuario y el manejador de logout.
 */
function initUserDropdown() {
    const trigger = document.getElementById("userDropdownTrigger");
    const menu = document.getElementById("userDropdownMenu");

    if (trigger && menu) {
        trigger.addEventListener("click", (e) => {
            e.stopPropagation();
            const isHidden = menu.classList.contains("hidden");
            menu.classList.toggle("hidden", !isHidden);
            isHidden && window.lucide && lucide.createIcons();
        });

        document.addEventListener("click", (e) => {
            if (!menu.contains(e.target) && !trigger.contains(e.target)) {
                menu.classList.add("hidden");
            }
        });

        // Manejo de Logout con confirmación delegada
        document.addEventListener("click", (e) => {
            const logoutBtn = e.target.closest(".logout");
            if (logoutBtn) {
                e.preventDefault();
                const url = logoutBtn.href;
                AppUtils.confirmAction(
                    "¿Cerrar Sesión?",
                    "Tu sesión actual terminará.",
                    () => (window.location.href = url),
                    "question",
                    "Sí, salir",
                    "#ef4444"
                );
            }
        });
    }
}

/**
 * Renderiza la información del usuario en la barra superior.
 */
async function renderTopBarUserInfo() {
    const topbarUsername = document.getElementById("topbar-username");
    const topbarUserrole = document.getElementById("topbar-userrole");

    if (topbarUsername && topbarUserrole) {
        const user = window.currentLoggedInUser;
        if (user) {
            topbarUsername.textContent = (user.staffName || user.username || "Usuario").toUpperCase();
            topbarUserrole.textContent = (user.role || "Sin Rol").toUpperCase();
        } else {
            topbarUsername.textContent = "Invitado";
            topbarUserrole.textContent = "Sin Sesión";
        }
        window.lucide && lucide.createIcons();
    }
}

/**
 * Controla el comportamiento de apertura/cierre del Sidebar.
 */
function initSidebar() {
    const btn = document.getElementById("toggleSidebar");
    const sidebar = document.getElementById("sidebar");

    if (btn && sidebar) {
        btn.addEventListener("click", () => {
            if (window.innerWidth < 1024) {
                sidebar.classList.toggle("-translate-x-full");
            } else {
                sidebar.classList.toggle("w-64");
                sidebar.classList.toggle("w-20");
            }
        });

        document.addEventListener("mousedown", (e) => {
            if (window.innerWidth < 1024 && !sidebar.contains(e.target) && !btn.contains(e.target)) {
                sidebar.classList.add("-translate-x-full");
            }
        });
    }
}

// =============================================================================
// 5. NOTIFICACIONES Y GESTIÓN DE ACCESO (ADMIN)
// =============================================================================

/**
 * Gestiona las notificaciones de la campana para el administrador.
 */
async function initRecoveryNotifications() {
    const bellContainer = document.getElementById("recovery-bell-container");
    if (bellContainer)
        try {
            const response = await fetch(`${URLROOT}/auth/getSolicitudes`);
            if (!response.ok) return;
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) return;

            const result = await response.json();
            if (result.success && result.data.length > 0) {
                bellContainer.classList.remove('hidden');
                bellContainer.innerHTML = `
                <div class="relative group">
                    <button onclick="window.location.href='${URLROOT}/recuperar'" class="p-2 bg-amber-500/10 text-amber-500 rounded-lg alert-shake border border-amber-500/20">
                        <i data-lucide="bell-ring" class="w-5 h-5"></i>
                        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-black px-1.5 rounded-full border-2 border-white">${result.data.length}</span>
                    </button>
                    <div class="hidden group-hover:block absolute top-full right-0 w-64 pt-2 z-50">
                        <div class="bg-black shadow-2xl rounded-xl p-3 border border-slate-700">
                            <p class="text-[10px] font-bold text-white uppercase mb-2">Solicitudes Pendientes</p>
                            <div class="space-y-2">
                                ${result.data.slice(0, 3).map(s => `
                                    <div class="text-xs border-b border-slate-700 pb-1">
                                        <p class="font-bold text-white uppercase">${s.username}</p>
                                        <p class="text-white text-[10px] opacity-80">${s.tipo} - ${new Date(s.fecha).toLocaleTimeString()}</p>
                                    </div>`).join("")}
                            </div>
                            <a href="${URLROOT}/recuperar" class="block text-center text-[10px] font-bold text-blue-600 mt-2 uppercase hover:underline">Ver todas</a>
                        </div>
                    </div>
                </div>`;
                window.lucide && lucide.createIcons();
            } else {
                bellContainer.innerHTML = "";
                bellContainer.classList.add('hidden');
            }
        } catch (error) {
            console.error("Error en notificaciones:", error);
        }
}

/**
 * Gestiona las notificaciones de créditos vencidos (+15 días).
 */
async function initCreditNotifications() {
    const container = document.getElementById("credit-notifications-container");
    if (!container) return;
    try {
        const response = await fetch(`${URLROOT}/facturacion/alertasCredito`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) throw new Error("Error en el servidor");

        const result = await response.json();
        if (result.success && result.data.length > 0) {
            container.classList.remove('hidden');
            container.innerHTML = `
            <div class="relative group">
                <button class="p-2 bg-rose-500/10 text-rose-500 rounded-lg alert-shake border border-rose-500/20" title="Créditos Vencidos">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <span class="absolute -top-1 -right-1 bg-rose-600 text-white text-[10px] font-black px-1.5 rounded-full border-2 border-white">${result.data.length}</span>
                </button>
                <div class="hidden group-hover:block absolute top-full right-0 w-72 pt-2 z-50">
                    <div class="bg-black shadow-2xl rounded-xl p-3 border border-slate-700">
                        <p class="text-[10px] font-bold text-white uppercase mb-2">Cartera Vencida (+15 días)</p>
                        <div class="max-h-60 overflow-y-auto space-y-2 custom-scrollbar">
                            ${result.data.map(v => `
                                <div class="text-xs border-b border-slate-700 pb-2">
                                    <div class="flex justify-between items-start">
                                        <span class="font-bold text-white uppercase truncate w-40">${v.cliente_nombre || 'Sin Nombre'}</span>
                                        <span class="text-rose-600 font-black">${AppUtils.formatCurrency(v.saldo_pendiente)}</span>
                                    </div>
                                    <p class="text-white opacity-70 text-[10px] flex justify-between mt-0.5">
                                        <span>Placa: ${v.placa}</span>
                                        <span>Hace ${Math.floor((new Date() - new Date(v.fecha)) / (1000 * 60 * 60 * 24))} días</span>
                                    </p>
                                </div>`).join("")}
                        </div>
                        <a href="${URLROOT}/reportes" class="block text-center text-[10px] font-bold text-blue-600 mt-2 uppercase hover:underline">Ver Reporte de Cartera</a>
                    </div>
                </div>
            </div>`;

            // Inyectar alerta visual en el dashboard si existe el contenedor
            const dashContainer = document.getElementById("dashboard-overdue-alert");
            if (dashContainer) {
                dashContainer.innerHTML = `
                <div class="bg-rose-50 border-l-4 border-rose-500 p-4 mb-4 rounded-r-xl shadow-sm animate-in fade-in slide-in-from-left duration-700">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-rose-500 text-white rounded-lg">
                            <i data-lucide="alert-octagon" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="text-rose-800 font-black uppercase text-xs tracking-tight">Alerta de Cartera Crítica</h4>
                            <p class="text-rose-700 text-[11px] font-bold">Se detectaron ${result.data.length} créditos que superan los 15 días. Se recomienda iniciar gestión de cobranza.</p>
                        </div>
                    </div>
                </div>`;
            }
            window.lucide && lucide.createIcons();
        } else {
            container.innerHTML = "";
            container.classList.add('hidden');
            const dashContainer = document.getElementById("dashboard-overdue-alert");
            if (dashContainer) dashContainer.innerHTML = "";
        }
    } catch (error) {
        console.error("Error en notificaciones de crédito:", error);
    }
}

/**
 * Gestiona las alertas de stock mínimo (Header y Dashboard).
 */
async function initLowStockNotifications() {
    const container = document.getElementById("low-stock-notifications-container");
    const dashContainer = document.getElementById("dashboard-stock-alert");
    if (!container && !dashContainer) return;

    try {
        const response = await fetch(`${URLROOT}/dashboard/getStats`);
        if (!response.ok) return;
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) return;

        const result = await response.json();
        const lowStock = result.lowStock || [];

        if (container && window.currentLoggedInUser && (parseInt(window.currentLoggedInUser.roleId) === 1 || window.currentLoggedInUser.role.toUpperCase() === "ADMINISTRADOR")) {
            if (lowStock.length > 0) {
                container.classList.remove('hidden');
                container.innerHTML = `
                <div class="relative group">
                    <button class="p-2 bg-amber-500/10 text-amber-500 rounded-lg alert-shake border border-amber-500/20" title="Stock Bajo">
                        <i data-lucide="package-search" class="w-5 h-5"></i>
                        <span class="absolute -top-1 -right-1 bg-amber-600 text-white text-[10px] font-black px-1.5 rounded-full border-2 border-white">${lowStock.length}</span>
                    </button>
                    <div class="hidden group-hover:block absolute top-full right-0 w-64 pt-2 z-50">
                        <div class="bg-black shadow-2xl rounded-xl p-3 border border-slate-700 text-left">
                            <p class="text-[10px] font-bold text-white uppercase mb-2 tracking-widest">Stock Crítico</p>
                            <div class="max-h-60 overflow-y-auto space-y-2">
                                ${lowStock.map(p => `<div class="text-xs border-b border-slate-700 pb-1"><p class="font-bold text-white uppercase leading-tight">${p.nombre}</p><p class="text-white opacity-70 text-[10px]">Stock: <span class="text-rose-500 font-black">${p.stock}</span> / Mín: ${p.stock_minimo}</p></div>`).join("")}
                            </div>
                            <a href="${URLROOT}/inventario" class="block text-center text-[10px] font-black text-blue-600 mt-2 uppercase hover:underline">Ver Inventario</a>
                        </div>
                    </div>
                </div>`;
            } else {
                container.innerHTML = "";
                container.classList.add('hidden');
            }
        }

        if (dashContainer) {
            if (lowStock.length > 0) {
                dashContainer.innerHTML = `
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-4 rounded-r-xl shadow-sm animate-in fade-in slide-in-from-left duration-500">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-amber-500 text-white rounded-lg"><i data-lucide="alert-triangle" class="w-5 h-5"></i></div>
                        <div>
                            <h4 class="text-amber-800 font-black uppercase text-xs tracking-tight">Atención: Reposición de Inventario</h4>
                            <p class="text-amber-700 text-[11px] font-bold">Tienes ${lowStock.length} productos por debajo del límite de seguridad.</p>
                        </div>
                    </div>
                </div>`;
            } else dashContainer.innerHTML = "";
        }
        window.lucide && lucide.createIcons();
    } catch (e) { console.error(e); }
}

/**
 * Gestiona la tarjeta de resumen de deudores para el dashboard.
 */
async function initDebtorsCard() {
    const container = document.getElementById("dashboard-debtors-card-container");
    if (!container) return;

    try {
        const response = await fetch(`${URLROOT}/facturacion/getDeudoresSummary`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) return;
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) return;

        const result = await response.json();

        if (result.success && result.data.resumen && parseFloat(result.data.resumen.total_deuda) > 0) {
            const totalDeuda = parseFloat(result.data.resumen.total_deuda);
            const cantidadDeudores = parseInt(result.data.resumen.cantidad_deudores);
            const deudores = result.data.lista.filter(v => parseFloat(v.saldo_pendiente) > 0);

            container.innerHTML = `
                <div class="glass-card rounded-2xl border border-rose-100 shadow-sm overflow-hidden animate-in fade-in slide-in-from-top-2 duration-500 h-full flex flex-col">
                    <div class="bg-rose-50/50 px-6 py-4 border-b border-rose-100 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-rose-500 text-white rounded-lg shadow-sm">
                                <i data-lucide="wallet-cards" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-navy-blue uppercase tracking-tight">Cuentas por Cobrar (Clientes)</h3>
                                <p class="text-xs text-slate-400 font-bold uppercase">${cantidadDeudores} clientes con saldo pendiente</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-black text-rose-600 leading-none">${AppUtils.formatCurrency(totalDeuda)}</p>
                            <p class="text-[11px] font-bold text-rose-400 uppercase tracking-widest mt-1">Total Cartera</p>
                        </div>
                    </div>

                    <div class="flex-1 max-h-[300px] overflow-y-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <tbody class="divide-y divide-slate-50">
                                ${deudores.map(v => `
                                    <tr class="hover:bg-slate-50/80 transition-colors group">
                                        <td class="px-6 py-3">
                                            <p class="text-sm font-black ${parseFloat(v.saldo_pendiente) >= 150000 ? 'text-red-600' : (v.cliente_nombre ? 'text-slate-700' : 'text-slate-500')} uppercase group-hover:text-rose-600 transition-colors">${v.cliente_nombre || 'Sin Nombre'}</p>
                                            <p class="text-xs text-slate-400 font-mono font-bold uppercase tracking-tighter">${v.placa || '---'} • ${v.modelo_vehiculo || 'N/A'}</p>
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <span class="text-sm font-black ${parseFloat(v.saldo_pendiente) >= 150000 ? 'text-red-600' : 'text-slate-600'}">${AppUtils.formatCurrency(v.saldo_pendiente)}</span>
                                            <p class="text-[11px] text-slate-300 font-bold uppercase">Saldo</p>
                                        </td>
                                        <td class="px-6 py-3 text-right w-20">
                                            <button onclick="window.location.href='${URLROOT}/reportes'" class="p-2 text-slate-300 hover:text-navy-blue transition-colors">
                                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 bg-slate-50/50 border-t border-slate-100 text-center">
                        <a href="${URLROOT}/reportes" class="text-xs font-black text-blue-600 hover:underline uppercase tracking-widest">
                            Abrir Módulo de Cartera Detallado
                        </a>
                    </div>
                </div>
            `;
            window.lucide && lucide.createIcons();
        } else { // No hay deudas pendientes
            container.innerHTML = `
                <div class="glass-card rounded-2xl border border-green-100 shadow-sm overflow-hidden animate-in fade-in slide-in-from-top-2 duration-500 h-full flex flex-col">
                    <div class="bg-green-50/50 px-6 py-4 border-b border-green-100 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-500 text-white rounded-lg shadow-sm">
                                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-navy-blue uppercase tracking-tight">Cuentas por Cobrar (Clientes)</h3>
                                <p class="text-xs text-slate-400 font-bold uppercase">Todo al día</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-black text-green-600 leading-none">${AppUtils.formatCurrency(0)}</p>
                            <p class="text-[11px] font-bold text-green-400 uppercase tracking-widest mt-1">Total Cartera</p>
                        </div>
                    </div>
                    <div class="p-6 text-center flex flex-col items-center justify-center flex-1 min-h-[150px]">
                        <i data-lucide="thumbs-up" class="w-12 h-12 text-green-400 mb-4"></i>
                        <p class="text-sm text-gray-600 font-bold">¡Excelente! No hay deudas pendientes de clientes.</p>
                        <a href="${URLROOT}/reportes" class="mt-4 inline-block text-green-600 hover:underline text-sm font-medium">Ver Reporte de Cartera</a>
                    </div>
                </div>`;
            window.lucide && lucide.createIcons();
        }
    } catch (error) {
        console.error("Error al cargar la tarjeta de deudores:", error);
        container.innerHTML = `<div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 text-center text-gray-500">Error al cargar datos de deudores.</div>`;
    }
}

/**
 * Gestiona la tarjeta de utilidad bruta (Rentabilidad) en el dashboard.
 */
async function initProfitabilityCard() {
    const container = document.getElementById("dashboard-profitability-container");
    if (!container) return;

    try {
        const response = await fetch(`${URLROOT}/dashboard/getStats`);
        if (!response.ok) return;
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) return;

        const result = await response.json();

        if (result.profitability) {
            const p = result.profitability;
            const gastosOperativos = parseFloat(result.gastosMes || 0);
            const totalVentas = parseFloat(p.total_ventas || 0);
            const utilidadBruta = parseFloat(p.utilidad_bruta || 0);
            const utilidadNeta = utilidadBruta - gastosOperativos;

            const margen = totalVentas > 0 ? ((utilidadBruta / totalVentas) * 100).toFixed(1) : 0;

            container.innerHTML = `
                <div class="glass-card rounded-2xl border border-emerald-100 shadow-sm overflow-hidden animate-in fade-in slide-in-from-right-2 duration-500 h-full flex flex-col">
                    <div class="bg-emerald-50/50 px-6 py-4 border-b border-emerald-100 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-emerald-500 text-white rounded-lg shadow-sm">
                                <i data-lucide="trending-up" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-navy-blue uppercase tracking-tight">Balance de Utilidad Neta</h3>
                                <p class="text-sm text-slate-400 font-bold uppercase">Rendimiento Real del Taller</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-4xl font-black ${utilidadNeta >= 0 ? 'text-emerald-600' : 'text-rose-600'} leading-none">${AppUtils.formatCurrency(utilidadNeta)}</p>
                            <p class="text-xs font-bold ${utilidadNeta >= 0 ? 'text-emerald-400' : 'text-rose-400'} uppercase tracking-widest mt-1">Utilidad Neta Real</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-2 flex-1">
                            <span class="text-xs font-black text-slate-400 uppercase">Margen Operativo Bruto</span>
                            <span class="text-base font-black text-emerald-600">${margen}%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-full transition-all duration-1000" style="width: ${margen}%"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-4 mt-6 pt-4 border-t border-slate-50">
                            <div class="border-b border-slate-100/50 pb-2">
                                <p class="text-xs font-black text-slate-400 uppercase leading-none mb-1">Total Facturado</p>
                                <p class="text-lg font-bold text-navy-blue">${AppUtils.formatCurrency(totalVentas)}</p>
                            </div>
                            <div class="border-b border-slate-100/50 pb-2">
                                <p class="text-xs font-black text-slate-400 uppercase leading-none mb-1">Costo Repuestos</p>
                                <p class="text-lg font-bold text-rose-500">${AppUtils.formatCurrency(p.total_costos)}</p>
                            </div>
                            <div class="border-b border-slate-100/50 pb-2 col-span-full">
                                <p class="text-xs font-black text-rose-400 uppercase leading-none mb-1">Gastos Operativos (Fijos/Variables)</p>
                                <p class="text-xl font-black text-rose-600">${AppUtils.formatCurrency(gastosOperativos)}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-blue-400 uppercase leading-none mb-1">Ingresos Servicios</p>
                                <p class="text-lg font-bold text-blue-600">${AppUtils.formatCurrency(p.total_servicios)}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-emerald-400 uppercase leading-none mb-1">Ganancia Repuestos</p>
                                <p class="text-lg font-bold text-emerald-600">${AppUtils.formatCurrency(p.ganancia_repuestos)}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
        } else { // No hay datos de rentabilidad
            container.innerHTML = `
                <div class="glass-card rounded-2xl border border-slate-100 shadow-sm overflow-hidden animate-in fade-in slide-in-from-right-2 duration-500 h-full flex flex-col">
                    <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-slate-400 text-white rounded-lg shadow-sm">
                                <i data-lucide="trending-up" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-navy-blue uppercase tracking-tight">Utilidad Bruta</h3>
                                <p class="text-xs text-slate-400 font-bold uppercase">Rendimiento de Ventas del Mes</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-black text-slate-600 leading-none">${AppUtils.formatCurrency(0)}</p>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-1">Sin Datos</p>
                        </div>
                    </div>
                    <div class="p-6 text-center flex flex-col items-center justify-center flex-1 min-h-[150px]">
                        <i data-lucide="bar-chart-2" class="w-12 h-12 text-slate-300 mb-4"></i>
                        <p class="text-sm text-gray-600 font-bold">No hay datos de ventas para calcular la utilidad este mes.</p>
                    </div>
                </div>`;
        }
    } catch (error) { console.error("Error en tarjeta rentabilidad:", error); }
}

/**
 * Abre el flujo de devolución para una factura (Solo repuestos, máx 5 días)
 */
window.iniciarDevolucion = async (ventaId, fecha) => {
    const dias = Math.floor((new Date() - new Date(fecha)) / (1000 * 60 * 60 * 24));
    if (dias > 5) {
        return AppUtils.showAlert("Plazo Vencido", `Solo se permiten devoluciones en los primeros 5 días. (Pasaron ${dias} días)`, "error");
    }

    try {
        const res = await fetch(`${URLROOT}/facturacion/getItemsDevolucion/${ventaId}`);
        const data = await res.json();

        if (!data.success || !data.items || data.items.length === 0) {
            return AppUtils.showAlert("Sin Repuestos", "Esta factura no tiene repuestos aptos para devolución (solo se devuelven productos, no servicios).", "info");
        }

        const { value: formValues } = await Swal.fire({
            title: 'Procesar Devolución',
            html: `
                <div class="text-left space-y-4 pt-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Factura #${ventaId}</p>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Repuesto a Devolver</label>
                        <select id="item-select" class="swal2-input w-full m-0 text-sm">
                            ${data.items.map(it => `<option value="${it.id}">${it.descripcion} (${AppUtils.formatCurrency(it.precio_unitario)})</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Acción con el Repuesto</label>
                        <select id="destino-select" class="swal2-input w-full m-0 text-sm">
                            <option value="STOCK">Reingresar al Inventario (Buen estado)</option>
                            <option value="DANADO">Garantía / Dañado (No vuelve al stock)</option>
                        </select>
                    </div>
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'PROCESAR DEVOLUCIÓN',
            confirmButtonColor: '#e11d48',
            preConfirm: () => {
                return {
                    detalle_id: document.getElementById('item-select').value,
                    destino: document.getElementById('destino-select').value
                }
            }
        });

        if (formValues) {
            AppUtils.showLoading();
            const response = await fetch(`${URLROOT}/facturacion/procesarDevolucion`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({ ...formValues, venta_id: ventaId })
            });
            const result = await response.json();
            AppUtils.hideLoading();

            if (result.success) {
                AppUtils.showToast(result.mensaje);
                if (typeof updateDashboard === 'function') updateDashboard();
                if (typeof cargarReporte === 'function') cargarReporte();
                if (typeof cargarReporteDetallado === 'function') cargarReporteDetallado();
                if (typeof cargarHistorialDevoluciones === 'function') cargarHistorialDevoluciones();
            } else {
                AppUtils.showAlert("Error", result.mensaje, "error");
            }
        }
    } catch (e) {
        AppUtils.hideLoading(); // Asegurar que el cargador se oculte en caso de error
        console.error(e);
    }
};

/**
 * Carga la tabla de solicitudes de recuperación (Administrador).
 */
window.cargarTablaRecuperacion = async function () {
    const container = document.getElementById("recovery-list-container");
    if (!container) return;
    const response = await fetch(`${URLROOT}/auth/getSolicitudes`);
    const result = await response.json();
    if (result.success && result.data.length > 0) {
        container.innerHTML = result.data.map(s => {
            const isHash = s.password.startsWith('$');
            return `
            <div class="glass-card p-5 rounded-2xl border-l-4 border-neon-green flex justify-between items-center group transition-all hover:scale-[1.01]">
                <div>
                    <span class="text-[10px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded uppercase">${s.tipo}</span>
                    <h3 class="text-lg font-black text-navy-blue mt-1 uppercase">${s.nombre}</h3>
                    <p class="text-sm text-slate-500 font-medium">Usuario: <span class="text-blue-600">${s.username}</span> | Cédula: ${s.cedula}</p>
                    <p class="text-[10px] text-slate-400 font-bold mt-1"><i data-lucide="calendar" class="w-3 h-3 inline"></i> ${new Date(s.fecha).toLocaleString()}</p>
                    <div class="mt-3 flex flex-col gap-2">
                        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 p-2 rounded-xl w-fit">
                            <span class="text-[9px] font-black text-slate-400 uppercase ml-1">${isHash ? 'Estado:' : 'Clave actual:'}</span>
                            <input type="${isHash ? 'text' : 'password'}" value="${isHash ? 'RESETEAR CLAVE' : s.password}" readonly class="bg-transparent border-none text-xs font-bold ${isHash ? 'text-rose-500' : 'text-navy-blue'} w-32 outline-none p-0 h-auto" id="pass-${s.id}">
                            ${!isHash ? `
                                <button onclick="togglePassVisibility(${s.id})" class="p-1 hover:bg-slate-200 rounded-md transition-colors text-slate-500">
                                    <i data-lucide="eye" class="w-4 h-4" id="icon-${s.id}"></i>
                                </button>` : ''}
                        </div>
                        <button onclick="resetearClaveSolicitud(${s.user_id}, ${s.id})" class="text-[10px] font-black text-blue-600 uppercase hover:underline flex items-center gap-1 ml-1">
                            <i data-lucide="key-round" class="w-3 h-3"></i> Asignar nueva clave temporal
                        </button>
                    </div>
                </div>
                <button onclick="confirmarSolicitud(${s.id})" class="flex items-center gap-2 bg-navy-blue text-white px-4 py-2 rounded-xl font-bold text-xs hover:bg-neon-green hover:text-black transition-all shadow-lg shadow-navy-blue/20">
                    <i data-lucide="check-circle" class="w-4 h-4"></i> COMPROBADO
                </button>
            </div>`}).join("");
    } else {
        container.innerHTML = '<div class="text-center py-20 opacity-40"><i data-lucide="shield-check" class="w-20 h-20 mx-auto mb-4"></i><p class="font-bold uppercase tracking-widest">No hay solicitudes pendientes</p></div>';
    }
    window.lucide && lucide.createIcons();
};

/**
 * Alterna la visibilidad de las contraseñas en la tabla de recuperación.
 */
window.togglePassVisibility = function (id) {
    const input = document.getElementById(`pass-${id}`);
    const icon = document.getElementById(`icon-${id}`);
    const isPass = input.type === "password";
    input.type = isPass ? "text" : "password";
    icon.setAttribute("data-lucide", isPass ? "eye-off" : "eye");
    window.lucide && lucide.createIcons();
};

/**
 * Permite al administrador asignar una nueva clave a través de un prompt seguro.
 */
window.resetearClaveSolicitud = async function (userId, solicitudId) {
    const { value: newPassword } = await Swal.fire({
        title: 'Asignar Nueva Clave',
        input: 'text',
        inputLabel: 'Escribe la nueva contraseña para el usuario',
        inputPlaceholder: 'Ej: Taller2024*',
        showCancelButton: true,
        confirmButtonText: 'Cambiar y Notificar',
        confirmButtonColor: '#10b981',
        inputValidator: (value) => {
            if (!value) return 'Debes ingresar una contraseña';
        }
    });

    if (newPassword) {
        try {
            AppUtils.showLoading('Actualizando...');
            const response = await fetch(`${URLROOT}/auth/resetearClaveAdmin`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": CSRF_TOKEN
                },
                body: JSON.stringify({ user_id: userId, password: newPassword })
            });
            const result = await response.json();
            AppUtils.hideLoading();

            if (result.success) {
                await AppUtils.showAlert("¡Hecho!", `La clave ha sido cambiada a: ${newPassword}. Entrégala al usuario.`, "success");
                // Una vez cambiada, marcamos la solicitud como comprobada automáticamente
                confirmarSolicitud(solicitudId);
            } else {
                AppUtils.showAlert("Error", result.error || "No se pudo cambiar la clave", "error");
            }
        } catch (e) {
            AppUtils.hideLoading(); // Asegurar que el cargador se oculte en caso de error
            console.error(e);
        }
    }
};

/**
 * Procesa la eliminación de una solicitud confirmada.
 */
window.confirmarSolicitud = async function (id) {
    const response = await fetch(`${URLROOT}/auth/eliminarSolicitud/${id}`, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": CSRF_TOKEN }
    });
    if ((await response.json()).success) {
        AppUtils.showToast("Solicitud procesada");
        cargarTablaRecuperacion();
        initRecoveryNotifications();
    }
};

/**
 * Inicializa la búsqueda global en el header con debounce.
 */
function initGlobalSearch() {
    const input = document.getElementById('globalSearchInput');
    const results = document.getElementById('globalSearchResults');
    let timeout = null;

    if (!input) return;

    input.addEventListener('input', (e) => {
        clearTimeout(timeout);
        const term = e.target.value.trim();

        if (term.length < 3) {
            results.classList.add('hidden');
            return;
        }

        timeout = setTimeout(async () => {
            try {
                const res = await fetch(`${URLROOT}/search/global?term=${encodeURIComponent(term)}`);
                if (!res.ok) throw new Error('Respuesta no válida');

                const contentType = res.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) throw new Error('No es JSON');

                const data = await res.json();
                if (data.results && data.results.length > 0) {
                    results.innerHTML = data.results.map(item => `
                        <a href="${item.link}" class="block p-3 hover:bg-slate-50 border-b border-slate-50 last:border-0 transition-colors">
                            <p class="text-[10px] font-black text-neon-green uppercase tracking-widest">${item.type}</p>
                            <p class="text-xs font-bold text-navy-blue">${item.title}</p>
                        </a>
                    `).join('');
                    results.classList.remove('hidden');
                } else {
                    results.innerHTML = '<div class="p-4 text-xs text-slate-400 text-center">Sin resultados</div>';
                    results.classList.remove('hidden');
                }
            } catch (err) {
                results.innerHTML = '<div class="p-4 text-xs text-rose-500 text-center font-bold italic uppercase tracking-tighter">Error en el buscador global</div>';
                results.classList.remove('hidden');
                console.error("Global search error:", err);
            }
        }, 400);
    });
}

/**
 * Genera una factura de forma asíncrona y la abre en una nueva pestaña.
 */
window.printInvoice = async (ventaId) => {
    try {
        AppUtils.showLoading('Generando Documento...');
        const res = await fetch(`${URLROOT}/facturacion/generarPdfAjax/${ventaId}`);
        const data = await res.json();
        if (data.success) {
            window.open(data.pdf_url, '_blank');
        } else {
            AppUtils.showAlert("Error", data.mensaje, "error");
        }
    } catch (e) {
        AppUtils.showAlert("Error", "No se pudo conectar con el servidor para generar el PDF", "error");
    } finally {
        AppUtils.hideLoading();
    }
};

// =============================================================================
// 6. NOTIFICACIONES GLOBALES (AppNotifications)
// =============================================================================
const AppNotifications = {
    /* Verifica deudas vencidas y actualiza el área de notificaciones.
     */
    checkSupplierDebts: async () => {
        const response = await fetch(`${URLROOT}/proveedores/listarDeudas`);
        const result = await response.json();
        const container = document.getElementById("notifications-area");
        if (container && result.success && result.data && result.data.length > 0) {
            const deudas = result.data;
            const totalDeuda = deudas.reduce((sum, d) => sum + parseFloat(d.saldo_pendiente), 0);
            container.innerHTML = `
                <div class="relative group flex items-center">
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                    <button onclick="window.location.href='${URLROOT}/proveedores?tab=deudas'" 
                            class="p-2 bg-gray-800 rounded-lg text-error-red border border-red-900/30 hover:bg-red-900/20 transition-all alert-shake">
                        <i data-lucide="bell-ring" class="w-5 h-5"></i>
                    </button>
                    <div class="hidden group-hover:block absolute top-full right-0 w-64 pt-2 z-[100]">
                        <div class="bg-white shadow-2xl rounded-xl p-4 text-xs border border-red-100">
                            <div class="flex items-center gap-2 mb-2 text-error-red font-bold">
                                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                                <span>PAGOS VENCIDOS</span>
                            </div>
                            <p class="text-slate-600 mb-1">Hay facturas pendientes de proveedores.</p>
                            <p class="font-bold text-navy-blue">Total: ${AppUtils.formatCurrency(totalDeuda)}</p>
                        </div>
                    </div>
                </div>`;
            window.lucide && lucide.createIcons();
        } else if (container) {
            container.innerHTML = "";
        }
    },
    navigateToDebts: () => {
        if (window.switchProveedorTab) switchProveedorTab("deudas");
    },
};

/**
 * Gestiona las notificaciones de entregas próximas o atrasadas en el taller.
 */
async function initWorkshopAlerts() {
    const btnLlave = document.getElementById('btn-notificaciones-taller');
    const containerBell = document.getElementById('workshop-bell-container');
    const badge = document.getElementById('taller-notif-badge');
    const lista = document.getElementById('taller-notif-list');
    const containerIndex = document.getElementById('alertasEntrega');

    if (!containerBell && !containerIndex) return;

    try {
        const res = await fetch(`${URLROOT}/taller/obtenerAlertas`);
        if (!res.ok) return;
        const result = await res.json();

        if (result.success) {
            const data = result.data || [];

            // 1. Mostrar contenedor y actualizar Badge
            if (containerBell) {
                if (result.total > 0) {
                    containerBell.classList.remove('hidden');
                } else {
                    containerBell.classList.add('hidden');
                }
            }

            if (badge) {
                if (result.total > 0) {
                    badge.textContent = result.total;
                    badge.classList.remove('hidden');
                    if (btnLlave) btnLlave.classList.add('animate-pulse');
                } else {
                    badge.classList.add('hidden');
                    if (btnLlave) btnLlave.classList.remove('animate-pulse');
                }
            }

            // 2. Llenar lista desplegable del Header
            if (lista) {
                lista.innerHTML = data.length === 0
                    ? '<div class="p-8 text-center text-slate-600 italic text-xs">No hay alertas activas</div>'
                    : data.map(item => {
                        const colorCls = item.tipo_alerta === 'SIN_MECANICO' ? 'bg-rose-500' : (item.tipo_alerta === 'VENCIDA' ? 'bg-amber-500' : 'bg-blue-500');
                        return `
                            <a href="${URLROOT}/taller" class="flex items-center gap-4 p-4 hover:bg-slate-900 border-b border-slate-800/50 transition-colors">
                                <div class="w-1.5 h-10 ${colorCls} rounded-full shadow-[0_0_10px_rgba(0,0,0,0.5)]"></div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center">
                                        <span class="font-black text-white text-xs tracking-tighter">${item.placa}</span>
                                        <span class="text-[10px] text-gray-400 font-mono">${item.estado}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-300 font-bold leading-tight mt-1 uppercase">${item.descripcion_alerta}</p>
                                    <p class="text-[9px] text-slate-500 mt-0.5 font-medium">${item.marca} ${item.modelo}</p>
                                </div>
                            </a>`;
                    }).join('');
            }

            // 3. Renderizado en el Index de Taller (Píldoras críticas)
            if (containerIndex) {
                containerIndex.classList.remove('hidden');
                containerIndex.innerHTML = `
                    <div class="col-span-full flex flex-wrap gap-3 mb-6">
                        ${data.filter(a => a.tipo_alerta !== 'PENDIENTE').map(a => `
                            <div class="w-fit min-w-[240px] ${a.tipo_alerta === 'VENCIDA' ? 'bg-rose-600' : 'bg-slate-900'} text-white px-4 py-2.5 rounded-xl shadow-lg flex items-center justify-between border border-white/10 animate-in fade-in zoom-in duration-300">
                                <div class="flex items-center gap-3">
                                    <div class="p-1.5 bg-white/10 rounded-lg">
                                        <i data-lucide="${a.tipo_alerta === 'SIN_MECANICO' ? 'user-x' : 'alert-circle'}" class="w-4 h-4 text-neon-green"></i>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[11px] font-black uppercase tracking-tighter text-white/60 leading-none mb-1">${a.descripcion_alerta}</span>
                                        <span class="text-sm font-black uppercase tracking-tight">${a.placa}</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <span class="text-[10px] font-bold bg-black/20 px-2 py-1 rounded-md border border-white/5 uppercase">#${a.id}</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>`;
            }
        }
        if (window.lucide) lucide.createIcons();
    } catch (e) { console.error("Error workshop alerts:", e); }
}

/**
 * Abre el modal de gestión para una orden de servicio en el taller.
 * Permite ver detalles y asignar mecánico (solo Admin).
 */
window.verDetalleOrdenTaller = async (id) => {
    try {
        AppUtils.showLoading('Cargando hoja de ruta...');
        const res = await fetch(`${URLROOT}/taller/obtenerDetalle/${id}`);

        if (!res.ok) {
            const errData = await res.json().catch(() => ({ error: 'Error de servidor' }));
            throw new Error(errData.error || `Error ${res.status}`);
        }

        const result = await res.json();
        AppUtils.hideLoading();

        if (!result.success || !result.data) return AppUtils.showToast(result.error || 'No se encontró la información', 'error');

        const orden = result.data;
        const staff = result.staff || [];
        // Usar variables globales del header para mayor rapidez y fiabilidad
        const isAdmin = (parseInt(window.USER_ROLE_ID || 0) === 1 || (window.USER_ROLE || "").toUpperCase() === 'ADMINISTRADOR');

        // Mapeo de colores para el badge de estado en el detalle
        const statusColors = {
            'RECIBIDO': 'bg-slate-500/10 text-slate-400 border-slate-500/20',
            'DIAGNOSTICANDO': 'bg-amber-500/10 text-amber-500 border-amber-500/20',
            'EN_REPARACION': 'bg-blue-500/10 text-blue-400 border-blue-500/20',
            'LISTO': 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            'ENTREGADO': 'bg-navy-blue/10 text-navy-blue border-navy-blue/20'
        };
        const statusClass = statusColors[orden.estado] || 'bg-slate-500/10 text-slate-400 border-slate-500/20';

        const { value: formValues } = await Swal.fire({
            title: `<span class="text-[10px] uppercase text-slate-400 font-black tracking-widest">Gestión Operativa</span><br><span class="text-white">ORDEN #${orden.id}</span>`,
            html: `
                <div class="text-left space-y-5 pt-4">
                    <div class="grid grid-cols-2 gap-4 bg-slate-900/50 p-4 rounded-2xl border border-slate-800">
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-slate-500 uppercase">Vehículo / Placa</p>
                            <p class="text-sm font-bold text-white uppercase">${orden.marca || ''} ${orden.modelo || ''} <span class="text-neon-green font-mono">[${orden.placa}]</span></p>
                        </div>
                        <div class="space-y-1 text-right">
                            <p class="text-[9px] font-black text-slate-500 uppercase">Estado</p>
                            <span class="px-2 py-1 rounded text-[10px] font-black border uppercase ${statusClass}">${orden.estado}</span>
                        </div>
                        <div class="col-span-2 space-y-1">
                            <p class="text-[9px] font-black text-slate-500 uppercase">Cliente / Propietario</p>
                            <p class="text-sm font-bold text-slate-200 uppercase">${orden.cliente_nombre}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Asignar Mecánico Responsable</label>
                        <select id="swal-mecanico-id" class="w-full p-3 bg-black border border-slate-800 rounded-xl text-white font-bold text-sm focus:ring-2 focus:ring-neon-green outline-none transition-all ${!isAdmin ? 'opacity-50 pointer-events-none' : ''}">
                            <option value="">-- SELECCIONE UN TÉCNICO --</option>
                            ${staff.map(s => `<option value="${s.id}" ${orden.mecanico_id === s.id ? 'selected' : ''}>${s.nombre} (${s.cargo})</option>`).join('')}
                        </select>
                        ${!isAdmin ? '<p class="text-[9px] text-amber-500 font-bold italic ml-1">* Solo el administrador puede reasignar mecánicos</p>' : ''}
                    </div>

                    <div class="space-y-2">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Diagnóstico de Entrada</p>
                        <div class="p-4 bg-slate-900/80 rounded-2xl border border-slate-800 text-xs text-slate-400 leading-relaxed italic">
                            "${orden.diagnostico_entrada || 'No se registró un diagnóstico detallado al ingresar el vehículo.'}"
                        </div>
                    </div>
                </div>`,
            showCancelButton: true,
            showConfirmButton: isAdmin,
            confirmButtonText: 'GUARDAR CAMBIOS',
            confirmButtonColor: '#39FF14',
            cancelButtonText: 'CERRAR',
            background: '#000000',
            color: '#ffffff',
            customClass: {
                popup: 'rounded-3xl border border-slate-800 shadow-[0_0_50px_rgba(0,0,0,0.9)]',
                confirmButton: 'text-black font-black uppercase text-xs tracking-widest px-6 py-3 rounded-xl'
            },
            preConfirm: () => {
                const mecanicoId = document.getElementById('swal-mecanico-id').value;
                return { id: orden.id, mecanico_id: mecanicoId };
            }
        });

        if (formValues && isAdmin) {
            AppUtils.showLoading('Sincronizando...');
            const response = await fetch(`${URLROOT}/taller/asignarMecanico`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN || ''
                },
                body: JSON.stringify(formValues)
            });
            const saveResult = await response.json();
            AppUtils.hideLoading();

            if (saveResult.success) {
                AppUtils.showToast(saveResult.mensaje);
                setTimeout(() => location.reload(), 800);
            } else {
                AppUtils.showToast(saveResult.error || 'Error al actualizar', 'error');
            }
        }
    } catch (err) {
        console.error("Workshop detail error:", err);
        AppUtils.showToast('Error de comunicación', 'error');
    }
};

/**
 * Alias global para disparar el detalle desde cualquier tabla (Ej: taller/index.php)
 */
window.abrirModalDetalleOrden = window.verDetalleOrdenTaller;
