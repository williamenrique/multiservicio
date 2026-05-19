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