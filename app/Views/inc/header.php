<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo) ? s($titulo) : SITENAME; ?></title>
    <link rel="shortcut icon" href="<?php echo !empty($company->logo) ? URLROOT . '/' . $company->logo : URL_IMG . 'logo.png'; ?>" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"> -->
    <!-- <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css"> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" href="<?php echo URL_CSS; ?>styles.css">
    <script>
        // Definimos la constante global para que todos los JS la usen
        const URLROOT = "<?php echo URLROOT; ?>";
        const URL_IMG = "<?php echo URL_IMG; ?>";
        const IVA_RATE = <?php echo (float)($company->iva ?? 0) / 100; ?>;
        const CSRF_TOKEN = "<?php echo $_SESSION['csrf_token'] ?? ''; ?>";
        window.USER_ROLE = "<?php echo $user_role ?? ($_SESSION['user_role'] ?? 'INVITADO'); ?>";
        window.USER_ROLE_ID = <?php echo (int)($user_role_id ?? ($_SESSION['user_role_id'] ?? 0)); ?>;
    </script>
</head>
<body class="bg-main-dark text-slate-800 font-sans">

    <div class="flex h-screen overflow-hidden">
        <!-- Overlay para Sidebar en móviles (Se activa vía JS cuando el sidebar está abierto) -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300 opacity-0"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-navy-blue border-r border-gray-800 transition-all duration-300 transform -translate-x-full lg:translate-x-0 shadow-2xl lg:shadow-none">
            <div class="px-5 py-6 flex items-center gap-4 border-b border-gray-800/50 min-h-[64px]">
                <?php if(!empty($company->logo)): ?>
                    <img src="<?php echo URLROOT . '/' . $company->logo; ?>" class="w-8 h-8 object-contain" alt="Logo">
                <?php else: ?>
                    <i data-lucide="wrench" class="text-neon-green flex-shrink-0"></i>
                <?php endif; ?>
                <span class="text-xl font-bold tracking-wider whitespace-nowrap uppercase text-neon-green"><?php echo s($company->name); ?></span>
            </div>
            <nav class="mt-6 px-4">
                <a href="<?php echo URLROOT; ?>/dashboard" class="nav-link <?php echo (strpos($_GET['url'] ?? '', 'dashboard') !== false) ? 'active' : ''; ?>" data-section="dashboard">
                    <i data-lucide="layout-dashboard"></i> <span>Dashboard</span>
                </a>
                <div class="mt-4">
                    <p class="text-xs uppercase text-gray-500 px-3 mb-2">Gestión</p>
                    <?php if($_SESSION['user_role'] === 'ADMINISTRADOR'): ?>
                    <a href="<?php echo URLROOT; ?>/venta" class="nav-link <?php echo (strpos($_GET['url'] ?? '', 'venta') === 0) ? 'active' : ''; ?>" data-section="venta">
                        <i data-lucide="shopping-cart"></i> <span>Venta Repuestos</span>
                    </a>
                    <a href="<?php echo URLROOT; ?>/inventario" class="nav-link <?php echo (strpos($_GET['url'] ?? '', 'inventario') !== false) ? 'active' : ''; ?>" data-section="inventario">
                        <i data-lucide="package"></i> <span>Inventario</span>
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo URLROOT; ?>/facturacion" class="nav-link <?php echo (strpos($_GET['url'] ?? '', 'facturacion') !== false) ? 'active' : ''; ?>" data-section="facturacion">
                        <i data-lucide="receipt"></i> <span>Facturación</span>
                    </a>
                    <a href="<?php echo URLROOT; ?>/taller" class="nav-link <?php echo (strpos($_GET['url'] ?? '', 'taller') !== false) ? 'active' : ''; ?>" data-section="taller">
                        <i data-lucide="wrench"></i> <span>Taller</span>
                    </a>
                    <a href="<?php echo URLROOT; ?>/proveedores" class="nav-link <?php echo (strpos($_GET['url'] ?? '', 'proveedores') !== false) ? 'active' : ''; ?>" data-section="proveedores">
                        <i data-lucide="truck"></i> <span>Proveedores</span>
                    </a>
                    <a href="<?php echo URLROOT; ?>/gastos" class="nav-link <?php echo (strpos($_GET['url'] ?? '', 'gastos') !== false) ? 'active' : ''; ?>" data-section="gastos">
                        <i data-lucide="wallet"></i> <span>Gastos del Taller</span>
                    </a>
                    <?php if($_SESSION['user_role'] === 'ADMINISTRADOR'): ?>
                    <a href="<?php echo URLROOT; ?>/reportes" class="nav-link <?php echo (strpos($_GET['url'] ?? '', 'reportes') !== false) ? 'active' : ''; ?>" data-section="reportes">
                        <i data-lucide="bar-chart-big"></i> <span>Reportes Contables</span>
                    </a>
                    <?php endif; ?>
                    <p class="text-xs uppercase text-gray-500 px-3 mt-4 mb-2">Administración</p>
                    <a href="<?php echo URLROOT; ?>/clientes" class="nav-link <?php echo (strpos($_GET['url'] ?? '', 'clientes') !== false) ? 'active' : ''; ?>" data-section="clientes">
                        <i data-lucide="users"></i> <span>Clientes</span>
                    </a>
                    <?php if($_SESSION['user_role'] === 'ADMINISTRADOR'): ?>
                    <a href="<?php echo URLROOT; ?>/personal" class="nav-link <?php echo (strpos($_GET['url'] ?? '', 'personal') !== false) ? 'active' : ''; ?>" data-section="personal">
                        <i data-lucide="user-cog"></i> <span>Personal</span>
                    </a>
                    <a href="<?php echo URLROOT; ?>/empresa" class="nav-link <?php echo (strpos($_GET['url'] ?? '', 'empresa') !== false) ? 'active' : ''; ?>" data-section="empresa">
                        <i data-lucide="settings"></i> <span>Configuración</span>
                    </a>
                    <?php endif; ?>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-y-auto">
            <!-- Top Bar -->
            <header 
                class="h-16 bg-navy-blue border-b border-gray-800 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-50">
                <div class="flex items-center gap-4">
                    <button id="toggleSidebar" class="p-2 text-white hover:bg-gray-800 rounded-lg">
                        <i data-lucide="menu"></i>
                    </button>
                    <div id="digitalClock" class="text-neon-green font-mono text-sm md:text-lg flex-shrink-0">00:00:00</div>
                </div>

                <div class="flex items-center gap-2 md:gap-4 ml-auto px-2 md:px-4">
                    <!-- Buscador Global -->
                    <div class="relative group hidden md:block">
                        <input type="text" id="globalSearchInput" placeholder="Buscar factura, placa, cliente..." class="w-64 bg-gray-800 border border-gray-700 text-white text-xs px-4 py-2 rounded-lg focus:outline-none focus:border-neon-green transition-all">
                        <div id="globalSearchResults" class="absolute top-full right-0 mt-2 w-80 bg-white shadow-2xl rounded-xl border border-slate-100 hidden z-[100] overflow-hidden"></div>
                    </div>
                    <!-- Contenedor dinámico para la campana de recuperación -->
                    <div id="recovery-bell-container" class="hidden"></div>
                    <div id="low-stock-notifications-container" class="hidden"></div>
                    <div id="credit-notifications-container" class="hidden"></div>
                    <div id="notifications-area" class="hidden"></div>
                    <!-- Notificaciones de Taller (Icono de Llave) -->
                    <div id="workshop-bell-container" class="relative group hidden">
                        <button id="btn-notificaciones-taller" class="p-2 bg-slate-800/50 text-slate-400 rounded-xl hover:bg-slate-700 hover:text-neon-green transition-all relative">
                            <i data-lucide="wrench" class="w-5 h-5"></i>
                            <!-- El badge se muestra automáticamente cuando hay órdenes pendientes -->
                            <span id="taller-notif-badge" class="absolute -top-1 -right-1 bg-rose-600 text-white text-[10px] font-black px-1.5 rounded-full border-2 border-navy-blue hidden">0</span>
                        </button>
                        
                        <!-- Dropdown Desplegable -->
                        <div class="absolute right-0 top-full pt-2 w-80 hidden group-hover:block z-50 animate-in fade-in slide-in-from-top-2 duration-200">
                            <div class="bg-black rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.5)] border border-slate-800 overflow-hidden">
                                <div class="p-4 border-b border-slate-800 bg-slate-900/50">
                                    <h3 class="text-xs font-black text-white uppercase tracking-widest flex items-center gap-2">
                                        <i data-lucide="wrench" class="w-4 h-4 text-neon-green"></i> Monitor de Taller
                                    </h3>
                                </div>
                                
                                <!-- Lista de Alertas (Se llena vía app.js) -->
                                <div id="taller-notif-list" class="max-h-96 overflow-y-auto custom-scrollbar bg-black">
                                    <div class="p-8 text-center text-slate-500 italic text-xs uppercase font-bold tracking-widest">
                                        Sincronizando...
                                    </div>
                                </div>
                                
                                <div class="p-3 bg-slate-900/80 border-t border-slate-800">
                                    <a href="<?php echo URLROOT; ?>/taller" class="block text-center text-[10px] font-black text-neon-green uppercase hover:underline tracking-tighter">
                                        Gestionar Todas las Órdenes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button id="userDropdownTrigger" class="flex items-center gap-2 md:gap-3 p-1.5 md:p-2 bg-gray-800 rounded-full text-white hover:bg-gray-700 transition-colors">
                        <?php if(!empty($_SESSION['user_foto'])): ?>
                            <img src="<?php echo URLROOT . '/' . $_SESSION['user_foto']; ?>" class="w-7 h-7 rounded-full object-cover border border-neon-green" alt="Avatar">
                        <?php else: ?>
                            <i data-lucide="user-circle" class="w-6 h-6 text-neon-green"></i>
                        <?php endif; ?>
                        
                        <div class="text-right hidden md:block">
                            <p id="topbar-username" class="text-sm font-bold text-white"><?php echo s($_SESSION['user_nombre'] ?? 'Invitado'); ?></p>
                            <p id="topbar-userrole" class="text-xs text-gray-400"><?php echo s($_SESSION['user_role'] ?? 'Sin Rol'); ?></p>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 hidden md:block"></i>
                    </button>
                    <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-navy-blue border border-gray-700 rounded-lg shadow-xl z-50 overflow-hidden">
                        <a href="<?php echo URLROOT; ?>/perfil" class="block px-4 py-2 text-sm text-white hover:bg-gray-800 flex items-center gap-2">
                            <i data-lucide="settings-2" class="w-4 h-4"></i> Mi Perfil
                        </a>
                        <hr class="border-gray-700">
                        <a href="<?php echo URLROOT; ?>/auth/logout" class="block px-4 py-2 text-sm text-red-400 hover:bg-gray-800 flex items-center gap-2">
                            <i data-lucide="log-out" class="w-4 h-4 flex-shrink-0"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </header>
            <!-- Sections Content -->
            <div id="content-area" class="p-4 md:p-8">
