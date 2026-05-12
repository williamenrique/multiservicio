<?php require_once 'constants.php'; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Multiservicios</title>
    <link rel="shortcut icon" href="<?php echo IMG_DIR; ?>logo1.png" type="image/x-icon">
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

                <div class="flex items-center gap-4">
                    <div class="text-right hidden md:block">
                        <p class="text-sm font-bold text-white">Admin User</p>
                        <p class="text-xs text-gray-400">Senior Developer</p>
                    </div>
                    <button class="p-2 bg-gray-800 rounded-full text-neon-green hover:text-red-500 transition-colors">
                        <i data-lucide="log-out"></i>
                    </button>
                </div>
            </header>

            <!-- Sections Content -->
            <div id="content-area" class="p-8">

                <!-- Dashboard Section -->
                <section id="sec-dashboard" class="content-section">
                    <h2 class="text-2xl font-bold mb-6">Resumen del Taller</h2>

                    <h3 class="text-lg font-semibold text-slate-600 mb-4 flex items-center gap-2"><i
                            data-lucide="package"></i> Estado de Inventario</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" id="dashboard-cards">
                        <!-- Cards generated via JS -->
                    </div>

                    <h3 class="text-lg font-semibold text-slate-600 my-6 flex items-center gap-2">
                        <i data-lucide="clock" id="pending-bills-icon"></i> Facturas en Proceso (Borradores)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="pending-bills-dashboard">
                        <!-- Pending bills cards generated via JS -->
                    </div>

                    <h3 class="text-lg font-semibold text-slate-600 my-6 flex items-center gap-2">
                        <i data-lucide="truck"></i> Cuentas por Pagar (Proveedores)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6" id="supplier-debts-dashboard"></div>

                    <h3 class="text-lg font-semibold text-slate-600 my-6 flex items-center gap-2">
                        <i data-lucide="trending-down"></i> Gastos del Mes en Curso
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" id="expenses-dashboard"></div>

                    <div class="flex justify-between items-center my-6">
                        <h3 class="text-lg font-semibold text-slate-600 flex items-center gap-2">
                            <i data-lucide="trending-up"></i> Rendimiento Financiero
                        </h3>
                        <button onclick="downloadBackup()"
                            class="text-xs bg-slate-200 hover:bg-slate-300 px-3 py-1 rounded-full font-bold transition">
                            Descargar Respaldo (JSON)
                        </button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 glass-card p-6 rounded-xl">
                            <canvas id="salesChart" height="150"></canvas>
                        </div>
                        <div class="grid grid-cols-1 gap-4" id="financial-cards"></div>
                    </div>
                </section>

                <!-- Inventario Section -->
                <section id="sec-inventario" class="content-section hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Control de Inventario</h2>
                        <div class="flex gap-2">
                            <button onclick="clearInventoryFilters()"
                                class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:bg-slate-50 transition shadow-sm">
                                <i data-lucide="filter-x"></i> Mostrar Todo
                            </button>
                            <button onclick="openInventoryModal()"
                                class="bg-neon-green text-black px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:opacity-90 transition shadow-sm">
                                <i data-lucide="plus-circle"></i> Nuevo Producto
                            </button>
                        </div>
                    </div>
                    <div class="glass-card p-6 rounded-xl w-full">
                        <table id="inventoryTable" class="display w-full">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Stock</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryBody"></tbody>
                        </table>
                    </div>
                </section>

                <!-- Facturación Section -->
                <section id="sec-facturacion" class="content-section hidden">
                    <!-- Contenido de Facturación -->
                </section>

                <!-- Historial Section -->
                <section id="sec-historial" class="content-section hidden">
                    <h2 class="text-2xl font-bold mb-6">Historial de Transacciones</h2>
                    <div class="glass-card p-6 rounded-xl w-full">
                        <table id="salesTable" class="display w-full">
                            <thead>
                                <tr>
                                    <th>ID Factura</th>
                                    <th>Fecha</th>
                                    <th>Vehículo</th>
                                    <th>Total (con IVA)</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="salesBody"></tbody>
                        </table>
                    </div>
                </section>

                <!-- Proveedores Section -->
                <section id="sec-proveedores" class="content-section hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Gestión de Proveedores</h2>
                        <div class="flex gap-2">
                            <button onclick="openPurchaseModal()"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:bg-blue-700 transition shadow-sm">
                                <i data-lucide="box"></i> Ingresar Mercancía
                            </button>
                            <button onclick="openSupplierModal()"
                                class="bg-neon-green text-black px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:opacity-90 transition shadow-sm">
                                <i data-lucide="user-plus"></i> Nuevo Proveedor
                            </button>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="flex gap-4 mb-6 border-b border-slate-200">
                        <button onclick="switchProveedorTab('lista')" id="tab-prov-lista"
                            class="pb-2 px-1 border-b-2 border-neon-green font-bold text-navy-blue">Lista de
                            Proveedores</button>
                        <button onclick="switchProveedorTab('deudas')" id="tab-prov-deudas"
                            class="pb-2 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue">Cuentas
                            por Pagar</button>
                    </div>

                    <div id="prov-lista-content" class="glass-card p-6 rounded-xl w-full">
                        <table id="suppliersTable" class="display w-full">
                            <thead>
                                <tr>
                                    <th>NIT/ID</th>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th>Email</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div id="prov-deudas-content" class="glass-card p-6 rounded-xl w-full hidden">
                        <table id="purchasesTable" class="display w-full">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Total</th>
                                    <th>Abonado</th>
                                    <th>Saldo</th>
                                    <th>Corte</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>

                <!-- Gastos Section -->
                <section id="sec-gastos" class="content-section hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Gastos del Taller</h2>
                        <button onclick="openExpenseModal()"
                            class="bg-red-500 text-white px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:bg-red-600 transition shadow-sm">
                            <i data-lucide="minus-circle"></i> Registrar Gasto
                        </button>
                    </div>
                    <div class="glass-card p-6 rounded-xl w-full">
                        <table id="expensesTable" class="display w-full">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                    <th>Categoría</th>
                                    <th>Monto</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="expensesBody"></tbody>
                        </table>
                    </div>
                </section>

                <!-- Clientes Section -->
                <section id="sec-clientes" class="content-section hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Gestión de Clientes</h2>
                        <button onclick="openClientModal()"
                            class="bg-neon-green text-black px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:opacity-90 transition shadow-sm">
                            <i data-lucide="user-plus"></i> Nuevo Cliente
                        </button>
                    </div>
                    <div class="glass-card p-6 rounded-xl w-full">
                        <table id="clientsTable" class="display w-full">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th>Correo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="clientsBody"></tbody>
                        </table>
                    </div>
                </section>

                <!-- Personal Section -->
                <section id="sec-personal" class="content-section hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Gestión de Personal</h2>
                        <button onclick="openStaffModal()"
                            class="bg-neon-green text-black px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:opacity-90 transition shadow-sm">
                            <i data-lucide="user-check"></i> Nuevo Empleado
                        </button>
                    </div>
                    <div class="glass-card p-6 rounded-xl w-full">
                        <table id="staffTable" class="display w-full">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Cargo</th>
                                    <th>Teléfono</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="staffBody"></tbody>
                        </table>
                    </div>
                </section>

                <!-- Empresa Section -->
                <section id="sec-empresa" class="content-section hidden">
                    <h2 class="text-2xl font-bold mb-6">Configuración de la Empresa</h2>
                    <div class="max-w-2xl">
                        <div class="glass-card p-8 rounded-xl">
                            <form id="companyForm" onsubmit="saveCompanySettings(event)" class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre del
                                        Taller</label>
                                    <input type="text" id="config-name"
                                        class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none"
                                        required>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">NIT /
                                            Documento</label>
                                        <input type="text" id="config-nit"
                                            class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Porcentaje
                                            IVA (%)</label>
                                        <input type="number" id="config-iva" step="0.01"
                                            class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none"
                                            required>
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase mb-1">Dirección</label>
                                    <input type="text" id="config-address"
                                        class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none">
                                </div>
                                <button type="submit"
                                    class="w-full bg-navy-blue text-white font-bold py-3 rounded-lg hover:bg-slate-800 transition">
                                    Guardar Configuración
                                </button>
                            </form>
                        </div>
                    </div>
                </section>

            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <script src="<?php echo URL_JS; ?>utils.js"></script>
    <script src="<?php echo URL_JS; ?>app.js"></script>
    <script src="<?php echo URL_JS; ?>facturacion.js"></script>
    <script src="<?php echo URL_JS; ?>notifications.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>

</html>