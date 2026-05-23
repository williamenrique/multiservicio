/**
 * App Main Logic
 */

// Variable global para el usuario logueado (se cargará de la DB)
let currentLoggedInUser = null;

document.addEventListener('DOMContentLoaded', async () => {
    initClock();
    initSidebar();
    initUserDropdown();

    // Cargar la información del usuario logueado directamente desde la base de datos
    await fetchLoggedInUserFromDB();

    // Notificar a otros módulos que el usuario ya está cargado
    document.dispatchEvent(new CustomEvent('userLoaded', { detail: currentLoggedInUser }));

    if (currentLoggedInUser && currentLoggedInUser.role.toUpperCase() === 'ADMINISTRADOR') {
        initRecoveryNotifications();
        setInterval(initRecoveryNotifications, 30000); // Poll cada 30 segundos
    }

    renderTopBarUserInfo(); // Cargar info del usuario en la barra superior una vez que todo esté cargado
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
 * Gestiona las notificaciones de recuperación para el administrador.
 */
async function initRecoveryNotifications() {
    const bellContainer = document.getElementById('recovery-bell-container');
    if (!bellContainer) return;

    try {
        const res = await fetch(`${URLROOT}/auth/getSolicitudes`);
        const result = await res.json();

        if (result.success && result.data.length > 0) {
            const count = result.data.length;
            bellContainer.innerHTML = `
                <div class="relative group">
                    <button onclick="window.location.href='${URLROOT}/auth/solicitudes'" 
                            class="p-2 bg-amber-500/10 text-amber-500 rounded-lg alert-shake border border-amber-500/20">
                        <i data-lucide="bell-ring" class="w-5 h-5"></i>
                        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-black px-1.5 rounded-full border-2 border-white">${count}</span>
                    </button>
                    <div class="hidden group-hover:block absolute top-full right-0 mt-2 w-64 bg-white shadow-2xl rounded-xl p-3 border border-slate-100 z-50">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Solicitudes Pendientes</p>
                        <div class="space-y-2">
                            ${result.data.slice(0, 3).map(s => `
                                <div class="text-xs border-b border-slate-50 pb-1">
                                    <p class="font-bold text-navy-blue uppercase">${s.username}</p>
                                    <p class="text-slate-400 text-[10px]">${s.tipo} - ${new Date(s.fecha).toLocaleTimeString()}</p>
                                </div>
                            `).join('')}
                        </div>
                        <a href="${URLROOT}/auth/solicitudes" class="block text-center text-[10px] font-bold text-blue-600 mt-2 uppercase hover:underline">Ver todas</a>
                    </div>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
        } else {
            bellContainer.innerHTML = ''; // Limpiar si no hay solicitudes
        }
    } catch (e) { console.error("Error en notificaciones:", e); }
}

/**
 * Lógica para la vista /auth/solicitudes (Administrador)
 */
window.cargarTablaRecuperacion = async function () {
    const container = document.getElementById('recovery-list-container');
    if (!container) return;

    const res = await fetch(`${URLROOT}/auth/getSolicitudes`);
    const result = await res.json();

    if (result.success && result.data.length > 0) {
        container.innerHTML = result.data.map(s => `
            <div class="glass-card p-5 rounded-2xl border-l-4 border-neon-green flex justify-between items-center group transition-all hover:scale-[1.01]">
                <div>
                    <span class="text-[10px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded uppercase">${s.tipo}</span>
                    <h3 class="text-lg font-black text-navy-blue mt-1 uppercase">${s.nombre}</h3>
                    <p class="text-sm text-slate-500 font-medium">Usuario: <span class="text-blue-600">${s.username}</span> | Cédula: ${s.cedula}</p>
                    <p class="text-[10px] text-slate-400 font-bold mt-1"><i data-lucide="calendar" class="w-3 h-3 inline"></i> ${new Date(s.fecha).toLocaleString()}</p>
                    
                    <!-- Campo de Contraseña Oculta -->
                    <div class="mt-3 flex items-center gap-2 bg-slate-50 border border-slate-200 p-2 rounded-xl w-fit">
                        <span class="text-[9px] font-black text-slate-400 uppercase ml-1">Clave actual:</span>
                        <input type="password" value="${s.password}" readonly class="bg-transparent border-none text-xs font-bold text-navy-blue w-32 outline-none p-0 h-auto" id="pass-${s.id}">
                        <button onclick="togglePassVisibility(${s.id})" class="p-1 hover:bg-slate-200 rounded-md transition-colors text-slate-500">
                            <i data-lucide="eye" class="w-4 h-4" id="icon-${s.id}"></i>
                        </button>
                    </div>
                </div>
                <button onclick="confirmarSolicitud(${s.id})" class="flex items-center gap-2 bg-navy-blue text-white px-4 py-2 rounded-xl font-bold text-xs hover:bg-neon-green hover:text-black transition-all shadow-lg shadow-navy-blue/20">
                    <i data-lucide="check-circle" class="w-4 h-4"></i> COMPROBADO
                </button>
            </div>
        `).join('');
    } else {
        container.innerHTML = `<div class="text-center py-20 opacity-40"><i data-lucide="shield-check" class="w-20 h-20 mx-auto mb-4"></i><p class="font-bold uppercase tracking-widest">No hay solicitudes pendientes</p></div>`;
    }
    if (window.lucide) lucide.createIcons();
};

/**
 * Alterna la visibilidad de la contraseña en las tarjetas de recuperación
 */
window.togglePassVisibility = function (id) {
    const input = document.getElementById(`pass-${id}`);
    const icon = document.getElementById(`icon-${id}`);
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    if (window.lucide) lucide.createIcons();
};

window.confirmarSolicitud = async function (id) {
    const res = await fetch(`${URLROOT}/auth/eliminarSolicitud/${id}`, { method: 'POST' });
    const result = await res.json();
    if (result.success) {
        AppUtils.showToast('Solicitud marcada como procesada');
        cargarTablaRecuperacion();
        initRecoveryNotifications();
    }
};

/**
 * Inicialización y control del Sidebar
 */
function initSidebar() {
    const btn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    if (!btn || !sidebar) return;

    btn.addEventListener('click', () => {
        if (window.innerWidth < 1024) {
            // Comportamiento en móviles: mostrar/ocultar el sidebar flotante
            sidebar.classList.toggle('-translate-x-full');
        } else {
            // Comportamiento en desktop: colapsar/expandir el sidebar lateral
            sidebar.classList.toggle('w-64');
            sidebar.classList.toggle('w-20');
        }
    });

    // Cerrar el sidebar al hacer clic fuera (solo en móviles)
    document.addEventListener('mousedown', (e) => {
        if (window.innerWidth < 1024 && !sidebar.contains(e.target) && !btn.contains(e.target)) {
            sidebar.classList.add('-translate-x-full');
        }
    });
}