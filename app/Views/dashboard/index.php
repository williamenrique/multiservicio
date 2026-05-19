                <!-- Dashboard Section -->
                <section id="sec-dashboard" class="content-section">
                    <h2 class="text-2xl font-bold mb-6">Resumen del Taller</h2>

                    <h3 class="text-lg font-semibold text-slate-600 mb-4 flex items-center gap-2">
                        <i data-lucide="activity"></i> Resumen Financiero
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" id="financial-status-cards">
                        <!-- Tarjetas financieras generadas via JS -->
                    </div>

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
                                    <th>Items</th>
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
                                    <th>Acceso</th>
                                    <th>Rol Sistema</th>
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
<script src="<?= URLROOT ?>/public/js/dashboard.js"></script>     