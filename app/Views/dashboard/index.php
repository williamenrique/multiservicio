<!-- Dashboard Section -->
<section id="sec-dashboard" class="content-section">
    <h2 class="text-2xl font-bold mb-6">Resumen del Taller</h2>

    <!-- Contenedores para Alertas Críticas (Stock y Cartera) -->
    <div id="dashboard-overdue-alert"></div>
    <div id="dashboard-stock-alert"></div>

    <!-- ═══════════════════════════════════════════════════════════════
         QUICK ACTIONS (v3.0)
         Accesos directos a las 4 acciones más frecuentes del admin.
         ═══════════════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6" id="quick-actions">
        <a href="<?php echo URLROOT; ?>/taller/nuevaOrden" 
           class="group bg-white hover:bg-navy-blue text-navy-blue hover:text-neon-green rounded-xl p-4 border border-slate-200 hover:border-navy-blue transition-all shadow-sm flex items-center gap-3">
            <div class="p-2 bg-neon-green/10 group-hover:bg-neon-green/20 rounded-lg">
                <i data-lucide="clipboard-plus" class="w-5 h-5"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Nueva</p>
                <p class="text-sm font-bold uppercase tracking-tight">Orden Servicio</p>
            </div>
        </a>

        <a href="<?php echo URLROOT; ?>/facturacion" 
           class="group bg-white hover:bg-navy-blue text-navy-blue hover:text-neon-green rounded-xl p-4 border border-slate-200 hover:border-navy-blue transition-all shadow-sm flex items-center gap-3">
            <div class="p-2 bg-neon-green/10 group-hover:bg-neon-green/20 rounded-lg">
                <i data-lucide="receipt" class="w-5 h-5"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Nueva</p>
                <p class="text-sm font-bold uppercase tracking-tight">Factura</p>
            </div>
        </a>

        <a href="<?php echo URLROOT; ?>/clientes" 
           class="group bg-white hover:bg-navy-blue text-navy-blue hover:text-neon-green rounded-xl p-4 border border-slate-200 hover:border-navy-blue transition-all shadow-sm flex items-center gap-3">
            <div class="p-2 bg-neon-green/10 group-hover:bg-neon-green/20 rounded-lg">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Nuevo</p>
                <p class="text-sm font-bold uppercase tracking-tight">Cliente</p>
            </div>
        </a>

        <a href="<?php echo URLROOT; ?>/gastos" 
           class="group bg-white hover:bg-navy-blue text-navy-blue hover:text-neon-green rounded-xl p-4 border border-slate-200 hover:border-navy-blue transition-all shadow-sm flex items-center gap-3">
            <div class="p-2 bg-neon-green/10 group-hover:bg-neon-green/20 rounded-lg">
                <i data-lucide="wallet" class="w-5 h-5"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Registrar</p>
                <p class="text-sm font-bold uppercase tracking-tight">Gasto</p>
            </div>
        </a>
    </div>

    <h3 class="text-lg font-semibold text-slate-600 mb-4 flex items-center gap-2"
        id="financial-summary-heading">
        <i data-lucide="activity"></i> Resumen Financiero
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6" id="financial-status-cards">
        <!-- Tarjetas financieras generadas via JS (Ventas Hoy / OS Activas / Gastos Mes / Utilidad) -->
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         WIDGETS OPERATIVOS (v3.0)
         OS sin mecánico asignado + Entregas próximas 48h
         ═══════════════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div id="dashboard-os-sin-mecanico" class="h-full"></div>
        <div id="dashboard-entregas-proximas" class="h-full"></div>
    </div>

    <!-- Sección de Rentabilidad y Cartera en la misma fila -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div id="dashboard-profitability-container" class="h-full"></div>
        <div id="dashboard-debtors-card-container" class="h-full"></div>
    </div>

    <h3 class="text-lg font-semibold text-slate-600 mb-4 flex items-center gap-2"><i
            data-lucide="package"></i> Estado de Inventario</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" id="dashboard-cards">
        <!-- Cards generated via JS -->
    </div>

    <!-- Sección de Estado del Taller -->
    <h3 class="text-lg font-semibold text-slate-600 my-6 flex items-center gap-2">
        <i data-lucide="wrench" class="text-blue-500"></i> Estado Operativo del Taller
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6" id="workshop-status-container">
        <!-- Estado del taller generado via JS -->
    </div>

    <!-- Sección de Productos en Oferta -->
    <h3 class="text-lg font-semibold text-slate-600 my-6 flex items-center gap-2">
        <i data-lucide="tag" class="text-amber-500"></i> Productos en Oferta
    </h3>
    <div class="glass-card rounded-xl overflow-hidden">
        <div class="p-4 border-b border-slate-100">
            <div class="flex items-center justify-between">
                <span class="text-sm text-slate-500">Productos con oferta activa y vigente</span>
                <span id="offer-count"
                    class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">0</span>
            </div>
        </div>
        <div id="products-on-offer-list" class="max-h-64 overflow-y-auto divide-y divide-slate-100">
            <!-- Productos en oferta generados via JS -->
            <div class="p-4 text-center text-slate-400 text-sm">
                <i data-lucide="loader" class="w-5 h-5 mx-auto animate-spin mb-2"></i>
                Cargando ofertas...
            </div>
        </div>
    </div>

    <h3 class="text-lg font-semibold text-slate-600 my-6 flex items-center gap-2">
        <i data-lucide="clock" id="pending-bills-icon"></i> Facturas en Proceso (Borradores)
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="pending-bills-dashboard">
        <!-- Pending bills cards generated via JS -->
    </div>

    <h3 class="text-lg font-semibold text-slate-600 my-6 flex items-center gap-2"
        id="supplier-debts-heading">
        <i data-lucide="truck"></i> Cuentas por Pagar (Proveedores)
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6" id="supplier-debts-dashboard"></div>

    <h3 class="text-lg font-semibold text-slate-600 my-6 flex items-center gap-2"
        id="expenses-month-heading">
        <i data-lucide="trending-down"></i> Gastos del Mes en Curso
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" id="expenses-dashboard">
    </div>

    <!-- Sección de Top Productos -->
    <h3 class="text-lg font-semibold text-slate-600 my-6 flex items-center gap-2">
        <i data-lucide="trophy" class="text-amber-500"></i> Ranking de Productos Más Vendidos
    </h3>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6" id="top-products-container">
        <!-- Top productos generado via JS -->
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         GRÁFICA DE RENDIMIENTO (v3.0)
         Con filtro de rango: 7d / 30d / mes
         ═══════════════════════════════════════════════════════════════ -->
    <div class="flex flex-wrap justify-between items-center my-6 gap-3" id="financial-performance-block">
        <h3 class="text-lg font-semibold text-slate-600 flex items-center gap-2">
            <i data-lucide="trending-up"></i> Rendimiento Financiero
        </h3>
        <div class="flex items-center gap-1 bg-white rounded-xl border border-slate-200 p-1 shadow-sm" id="chart-range-filter">
            <button data-range="7d" class="chart-range-btn px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider transition-all bg-navy-blue text-neon-green">
                7 DÍAS
            </button>
            <button data-range="30d" class="chart-range-btn px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider transition-all text-slate-500 hover:bg-slate-50">
                30 DÍAS
            </button>
            <button data-range="mes" class="chart-range-btn px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider transition-all text-slate-500 hover:bg-slate-50">
                MES ACTUAL
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 glass-card p-6 rounded-xl">
            <canvas id="salesChart" height="150"></canvas>
        </div>
        <div class="grid grid-cols-1 gap-4" id="financial-cards">
            <!-- Tarjetas laterales generadas via JS -->
        </div>
    </div>
</section>
<script src="<?php echo URLROOT; ?>/js/dashboard.js"></script>