<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Multiservicios</title> 
    <link rel="shortcut icon" href="img/logo1.png" type="image/x-icon"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="<?php echo URL_CSS; ?>styles.css">
    <!-- Iconos -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-main-dark text-slate-800 font-sans">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-navy-blue border-r border-gray-800 transition-all duration-300">
            <div class="px-5 py-6 flex items-center gap-4 border-b border-gray-800/50 min-h-[64px]">
                <i data-lucide="wrench" class="text-neon-green flex-shrink-0"></i>
                <span class="text-xl font-bold tracking-wider whitespace-nowrap">TALLER<span
                        class="text-neon-green">PRO</span></span>
            </div>
            <nav class="mt-6 px-4">
                <a href="#" onclick="showSection('dashboard')" class="nav-link active" data-section="dashboard">
                    <i data-lucide="layout-dashboard"></i> <span>Dashboard</span>
                </a>
                <div class="mt-4">
                    <p class="text-xs uppercase text-gray-500 px-3 mb-2">Gestión</p>
                    <a href="#" onclick="showSection('inventario')" class="nav-link" data-section="inventario">
                        <i data-lucide="package"></i> <span>Inventario</span>
                    </a>
                    <a href="#" onclick="showSection('facturacion')" class="nav-link" data-section="facturacion">
                        <i data-lucide="receipt"></i> <span>Facturación</span>
                    </a>
                    <a href="#" onclick="showSection('historial')" class="nav-link" data-section="historial">
                        <i data-lucide="history"></i> <span>Historial de Ventas</span>
                    </a>
                    <a href="#" onclick="showSection('proveedores')" class="nav-link" data-section="proveedores">
                        <i data-lucide="truck"></i> <span>Proveedores</span>
                    </a>
                    <a href="#" onclick="showSection('gastos')" class="nav-link" data-section="gastos">
                        <i data-lucide="wallet"></i> <span>Gastos del Taller</span>
                    </a>
                    <p class="text-xs uppercase text-gray-500 px-3 mt-4 mb-2">Administración</p>
                    <a href="#" onclick="showSection('clientes')" class="nav-link" data-section="clientes">
                        <i data-lucide="users"></i> <span>Clientes</span>
                    </a>
                    <a href="#" onclick="showSection('personal')" class="nav-link" data-section="personal">
                        <i data-lucide="user-cog"></i> <span>Personal</span>
                    </a>
                    <a href="#" onclick="showSection('empresa')" class="nav-link" data-section="empresa">
                        <i data-lucide="settings"></i> <span>Configuración</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-y-auto">
            <!-- Top Bar -->
            <header
                class="h-16 bg-navy-blue border-b border-gray-800 flex items-center justify-between px-8 sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <button id="toggleSidebar" class="p-2 text-white hover:bg-gray-800 rounded-lg">
                        <i data-lucide="menu"></i>
                    </button>
                    <div id="digitalClock" class="text-neon-green font-mono text-lg flex-shrink-0 mr-4">00:00:00</div>
                </div>
                <!-- Área de Notificaciones -->
                <div id="notifications-area" class="flex justify-end items-center ml-auto px-4"></div>

                <div class="relative group">
                    <button id="userDropdownTrigger" class="flex items-center gap-3 p-2 bg-gray-800 rounded-full text-white hover:bg-gray-700 transition-colors">
                        <i data-lucide="user-circle" class="w-6 h-6 text-neon-green"></i>
                        <div class="text-right hidden md:block">
                            <p id="topbar-username" class="text-sm font-bold text-white">Cargando...</p>
                            <p id="topbar-userrole" class="text-xs text-gray-400">Cargando...</p>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 hidden md:block"></i>
                    </button>
                    <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-navy-blue border border-gray-700 rounded-lg shadow-xl z-50 overflow-hidden">
                        <a href="#" onclick="openUserProfileModal()" class="block px-4 py-2 text-sm text-white hover:bg-gray-800 flex items-center gap-2">
                            <i data-lucide="settings-2" class="w-4 h-4"></i> Mi Perfil
                        </a>
                        <hr class="border-gray-700">
                        <a href="#" onclick="AppUtils.showToast('Funcionalidad de Logout en desarrollo', 'info')" class="block px-4 py-2 text-sm text-red-400 hover:bg-gray-800 flex items-center gap-2">
                            <i data-lucide="log-out" class="w-4 h-4"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </header>
            <!-- Sections Content -->
            <div id="content-area" class="p-8">

