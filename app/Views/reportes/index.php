<div class="p-6 space-y-6">
    <div class="flex justify-between items-end gap-4">
        <div>
            <h1 class="text-2xl font-black text-navy-blue uppercase tracking-tight">Reportes Contables</h1>
            <p class="text-slate-500 text-sm">Balance consolidado de ingresos y gastos operativos.</p>
        </div>
        
        <div class="flex items-center gap-2 bg-white p-2 rounded-xl border border-slate-200 shadow-sm">
            <input type="date" id="rep-desde" class="text-xs font-bold border-none outline-none" value="<?php echo date('Y-m-01'); ?>">
            <span class="text-slate-300 font-black">/</span>
            <input type="date" id="rep-hasta" class="text-xs font-bold border-none outline-none" value="<?php echo date('Y-m-d'); ?>">
            <button onclick="cargarReporte()" class="bg-navy-blue text-white p-2 rounded-lg hover:bg-neon-green hover:text-black transition-all">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- Totales Rápidos -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="glass-card p-4 rounded-2xl border-l-4 border-blue-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Ingreso Neto Repuestos</p>
            <h2 id="total-repuestos" class="text-xl font-black text-blue-600">$0.00</h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-indigo-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Ingreso Neto Servicios</p>
            <h2 id="total-servicios" class="text-xl font-black text-indigo-600">$0.00</h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-red-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Gastos y Compras</p>
            <h2 id="total-egresos" class="text-xl font-black text-red-600">$0.00</h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-amber-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Deuda Proveedores</p>
            <h2 id="total-deuda" class="text-xl font-black text-amber-600">$0.00</h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-emerald-500 shadow-sm bg-emerald-50/30">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Utilidad Real (Caja)</p>
            <h2 id="total-balance" class="text-xl font-black text-emerald-600">$0.00</h2>
        </div>
    </div>

    <!-- Tabs de Navegación -->
    <div class="flex gap-6 border-b border-slate-200">
        <button onclick="switchReportTab('resumen')" id="tab-resumen" class="pb-3 px-1 border-b-2 border-neon-green font-bold text-navy-blue transition-all text-sm uppercase tracking-widest flex items-center gap-2">
            <i data-lucide="pie-chart" class="w-4 h-4"></i> Flujo de Caja
        </button>
        <button onclick="switchReportTab('detallado')" id="tab-detallado" class="pb-3 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue font-bold transition-all text-sm uppercase tracking-widest flex items-center gap-2">
            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Auditoría de Trabajos
        </button>
        <button onclick="switchReportTab('cartera')" id="tab-cartera" class="pb-3 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue font-bold transition-all text-sm uppercase tracking-widest flex items-center gap-2">
            <i data-lucide="calendar-clock" class="w-4 h-4"></i> Cartera por Edades
        </button>
        <button onclick="switchReportTab('rentabilidad')" id="tab-rentabilidad" class="pb-3 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue font-bold transition-all text-sm uppercase tracking-widest flex items-center gap-2">
            <i data-lucide="trending-up" class="w-4 h-4"></i> Análisis Rentabilidad
        </button>
        <button onclick="switchReportTab('nomina')" id="tab-nomina" class="pb-3 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue font-bold transition-all text-sm uppercase tracking-widest flex items-center gap-2">
            <i data-lucide="users" class="w-4 h-4"></i> Nómina y Pagos
        </button>
        <button onclick="switchReportTab('devoluciones')" id="tab-devoluciones" class="pb-3 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue font-bold transition-all text-sm uppercase tracking-widest flex items-center gap-2">
            <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Devoluciones
        </button>
        <button onclick="switchReportTab('historial_nomina')" id="tab-historial-nomina" class="pb-3 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue font-bold transition-all text-sm uppercase tracking-widest flex items-center gap-2">
            <i data-lucide="history" class="w-4 h-4"></i> Historial Nómina
        </button>
    </div>

    <!-- SECCIÓN 1: RESUMEN CONSOLIDADO -->
    <div id="sec-resumen" class="space-y-6">
        <!-- Controles Superiores: Búsqueda y Límite -->
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="relative w-full md:w-96">
                <i data-lucide="search" class="absolute left-3 top-2.5 text-slate-400 w-5 h-5"></i>
                <input type="text" id="search-report" placeholder="Buscar en flujo de caja..." 
                       class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-neon-green outline-none transition-all shadow-sm text-sm">
            </div>
            <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-xl border border-slate-100 shadow-sm">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Mostrar</span>
                <select id="limitSelector" class="bg-transparent border-none text-xs font-black text-navy-blue focus:ring-0 cursor-pointer">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Registros</span>
            </div>
        </div>

        <div class="glass-card rounded-2xl overflow-hidden shadow-xl border border-slate-100">
            <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Cronología de Movimientos</h3>
            </div>
            <div class="p-6 overflow-x-auto">
                <table id="reportTable" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100">
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-left">ID</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-left">FECHA</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-center">TIPO</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-left">DESCRIPCIÓN</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">TOTAL</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right" style="width: 110px; min-width: 110px;">ACCIONES</th> 
                        </tr>
                    </thead>
                    <tbody id="report-body" class="divide-y divide-slate-50 bg-white">
                        <tr><td colspan="6" class="px-8 py-16 text-center text-slate-400 italic font-medium uppercase tracking-widest animate-pulse">Cargando movimientos de caja...</td></tr>
                    </tbody>
                </table>
            </div>
            <!-- Footer de Tabla: Información y Paginación -->
            <div class="p-4 bg-slate-50/50 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    Mostrando <span id="startIndex" class="text-navy-blue">0</span> - <span id="endIndex" class="text-navy-blue">0</span> 
                    de <span id="totalCount" class="text-navy-blue">0</span> movimientos
                </div>
                <div id="custom-bottom-controls" class="flex items-center gap-2">
                    <!-- Botones generados por JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: AUDITORÍA DETALLADA -->
    <div id="sec-detallado" class="space-y-8 hidden">
        <!-- Buscador de Auditoría -->
        <div class="relative max-w-md">
            <i data-lucide="search" class="absolute left-3 top-2.5 text-slate-400 w-5 h-5"></i>
            <input type="text" id="search-audit" placeholder="Buscar vehículo, placa o repuesto..." 
                   class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-neon-green outline-none transition-all shadow-sm">
        </div>
        
        <!-- Contenedor para la tarjeta de deudores -->
        <div id="debtors-summary-container" class="hidden"></div>

        <!-- Contenedor de la lista agrupada con scroll interno -->
        <div class="max-h-[75vh] overflow-y-auto pr-4 rounded-3xl border border-slate-100 shadow-sm bg-white custom-scrollbar" id="audit-scroll-area">
            <div id="audit-list-container" class="p-8">
                <!-- Dinámico desde JS -->
                <div class="text-center py-20 text-slate-400 italic">Cargando desglose de trabajos...</div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 3: CARTERA POR EDADES -->
    <div id="sec-cartera" class="hidden space-y-6">
        <div class="glass-card rounded-2xl overflow-hidden shadow-xl border border-slate-100">
            <div class="p-6 border-b border-slate-50 bg-amber-50/20">
                <h3 class="text-xs font-black text-amber-600 uppercase tracking-widest">Distribución de Deuda Pendiente</h3>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100">
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Cliente</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">0 - 15 Días</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">16 - 30 Días</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right text-rose-500">+30 Días</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Total Deuda</th>
                        </tr>
                    </thead>
                    <tbody id="cartera-body" class="divide-y divide-slate-50 bg-white">
                        <!-- Dinámico JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 4: ANÁLISIS DE RENTABILIDAD -->
    <div id="sec-rentabilidad" class="hidden space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div id="rentabilidad-cards" class="contents">
                <!-- Aquí se cargarán las tarjetas de resumen de Rentabilidad -->
            </div>
        </div>
        <div class="glass-card rounded-2xl overflow-hidden shadow-xl border border-slate-100">
            <div class="p-6 border-b border-slate-50 bg-emerald-50/20">
                <h3 class="text-xs font-black text-emerald-600 uppercase tracking-widest">Detalle de Margen por Operación</h3>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100">
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Tipo de Item</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-center">Operaciones</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Ingresos</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Costos</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Utilidad</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Margen %</th>
                        </tr>
                    </thead>
                    <tbody id="rentabilidad-body" class="divide-y divide-slate-50 bg-white">
                        <!-- Dinámico JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 5: NÓMINA Y PAGOS DE EMPLEADOS -->
    <div id="sec-nomina" class="hidden space-y-6">
        <div class="flex flex-wrap items-center gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Seleccionar Empleado</label>
                <select id="staff-selector" onchange="cargarNomina()" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm font-bold text-navy-blue outline-none focus:ring-2 focus:ring-neon-green">
                    <option value="">-- SELECCIONE UN EMPLEADO --</option>
                </select>
            </div>
            <button onclick="openModalPago()" class="bg-navy-blue text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-neon-green hover:text-black transition-all shadow-lg shadow-navy-blue/20 flex items-center gap-2">
                <i data-lucide="hand-coins" class="w-4 h-4"></i> Registrar Adelanto / Pago
            </button>
        </div>

        <!-- Resumen de Nómina -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="glass-card p-6 rounded-2xl border-l-4 border-emerald-500 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Total Trabajos (Mano de Obra)</p>
                <h2 id="nomina-total-trabajos" class="text-2xl font-black text-emerald-600">$0.00</h2>
            </div>
            <div class="glass-card p-6 rounded-2xl border-l-4 border-amber-500 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Total Adelantos Semanales</p>
                <h2 id="nomina-total-adelantos" class="text-2xl font-black text-amber-600">$0.00</h2>
            </div>
            <div class="glass-card p-6 rounded-2xl border-l-4 border-navy-blue shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Saldo a Liquidar (Pendiente)</p>
                <h2 id="nomina-total-pendiente" class="text-2xl font-black text-navy-blue">$0.00</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Tabla de Trabajos -->
            <div class="glass-card rounded-2xl overflow-hidden shadow-xl border border-slate-100">
                <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Trabajos Realizados (Servicios)</h3>
                </div>
                <div class="max-h-[400px] overflow-y-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-white z-10">
                            <tr class="bg-slate-50/80 border-b border-slate-100">
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase">Fecha</th>
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase">Servicio / Placa</th>
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody id="nomina-trabajos-body" class="divide-y divide-slate-50 bg-white text-xs">
                            <tr><td colspan="3" class="p-8 text-center text-slate-400 italic">Seleccione un empleado</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de Pagos -->
            <div class="glass-card rounded-2xl overflow-hidden shadow-xl border border-slate-100">
                <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Historial de Adelantos y Pagos</h3>
                </div>
                <div class="max-h-[400px] overflow-y-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-white z-10">
                            <tr class="bg-slate-50/80 border-b border-slate-100">
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase">Fecha</th>
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody id="nomina-pagos-body" class="divide-y divide-slate-50 bg-white text-xs">
                            <tr><td colspan="3" class="p-8 text-center text-slate-400 italic">Seleccione un empleado</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 3: HISTORIAL DE DEVOLUCIONES -->
    <div id="sec-devoluciones" class="hidden animate-in fade-in duration-500">
        <!-- Controles Superiores para Devoluciones -->
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
            <div class="relative w-full md:w-96">
                <i data-lucide="search" class="absolute left-3 top-2.5 text-slate-400 w-5 h-5"></i>
                <input type="text" id="search-devoluciones" placeholder="Buscar devoluciones..." 
                       class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-neon-green outline-none transition-all shadow-sm text-sm">
            </div>
            <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-xl border border-slate-100 shadow-sm">
                <select id="limitSelector-devoluciones" class="bg-transparent border-none text-xs font-black text-navy-blue focus:ring-0 cursor-pointer">
                    <option value="10">10 Registros</option>
                    <option value="25">25 Registros</option>
                </select>
            </div>
        </div>

        <div class="glass-card rounded-2xl border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Historial de Devoluciones de Clientes</h3>
            </div>
            <div class="p-6 overflow-x-auto">
                <table id="devolucionesTable" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100">
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-left">ID</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-left">FECHA</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-left">CLIENTE / PLACA</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-left">DESCRIPCIÓN</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">MONTO</th>
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-center">DESTINO</th>
                        </tr>
                    </thead>
                    <tbody id="devoluciones-body" class="divide-y divide-slate-50 bg-white">
                        <tr><td colspan="6" class="px-8 py-16 text-center text-slate-400 italic font-medium uppercase tracking-widest animate-pulse">Cargando historial de devoluciones...</td></tr>
                    </tbody>
                </table>
            </div>
            <!-- Footer de Tabla para Devoluciones -->
            <div class="p-4 bg-slate-50/50 border-t border-slate-100 flex justify-between items-center">
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    Total: <span id="totalCount-devoluciones" class="text-navy-blue">0</span> registros encontrados
                </div>
                <div id="pagination-devoluciones" class="flex items-center gap-2">
                    <!-- Botones generados por JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN: HISTORIAL DE NÓMINA (CANCELADOS) -->
    <div id="sec-historial-nomina" class="hidden animate-in fade-in slide-in-from-bottom-4 duration-500">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            
            <!-- Encabezado con Títulos Claros -->
            <div class="p-8 border-b border-slate-50 flex flex-wrap justify-between items-center bg-slate-50/50 gap-4">
                <div>
                    <h3 class="text-2xl font-black text-navy-blue uppercase tracking-tight leading-none mb-2">Pagos de Nómina Procesados</h3>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-wider">Registro histórico de liquidaciones y adelantos</p>
                </div>
            </div>

            <!-- Tabla con Fuentes Legibles -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100">
                            <th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest text-center w-24">Recibo</th>
                            <th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest">Fecha de Pago</th>
                            <th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest">Empleado</th>
                            <th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest text-center">Tipo</th>
                            <th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest text-right">Monto Total</th>
                            <th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="historial-nomina-body" class="divide-y divide-slate-100 bg-white">
                        <tr>
                            <td colspan="6" class="px-8 py-24 text-center text-slate-400 italic font-bold uppercase tracking-widest animate-pulse">
                                <div class="flex flex-col items-center gap-4">
                                    <i data-lucide="loader" class="w-10 h-10 animate-spin text-slate-200"></i>
                                    <span>Cargando historial de pagos...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof window.URLROOT === 'undefined') {
        window.URLROOT = "<?php echo URLROOT; ?>";
    }
    /**
     * Renderiza la tabla de Devoluciones con manejo de estado vacío.
     */
    window.renderDevoluciones = (data) => {
        const body = document.getElementById('devoluciones-body');
        if (!body) return;

        if (!data || data.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="6" class="px-8 py-16 text-center text-slate-400 italic font-medium uppercase tracking-widest">
                        <div class="flex flex-col items-center gap-2">
                            <i data-lucide="info" class="w-8 h-8 text-slate-300"></i>
                            <span>No hay registros de devoluciones para este periodo</span>
                        </div>
                    </td>
                </tr>`;
            if (window.lucide) lucide.createIcons();
            return;
        }

        body.innerHTML = data.map(item => `
            <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-4 font-black text-navy-blue text-xs uppercase">#${item.id}</td>
                <td class="px-4 py-4 text-xs font-bold text-slate-500">${item.fecha}</td>
                <td class="px-4 py-4">
                    <div class="text-xs font-black text-navy-blue uppercase">${item.cliente_nombre || 'N/A'}</div>
                    <div class="text-[10px] text-slate-400 font-bold">${item.placa || 'SIN PLACA'}</div>
                </td>
                <td class="px-4 py-4 text-xs text-slate-600 uppercase font-medium">${item.descripcion}</td>
                <td class="px-4 py-4 text-right font-black text-rose-500">$${parseFloat(item.monto_devuelto).toLocaleString('es-CO')}</td>
                <td class="px-4 py-4 text-center">
                    <span class="px-2 py-1 ${item.destino === 'STOCK' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'} rounded text-[9px] font-black border border-current opacity-80">
                        ${item.destino}
                    </span>
                </td>
            </tr>
        `).join('');
        if (window.lucide) lucide.createIcons();
    };

    /**
     * Definimos editItem de forma global para evitar el error de referencia.
     * En la vista de reportes, redirigimos al usuario al módulo de proveedores.
     */
    window.editItem = (id) => {
        window.location.href = `${window.URLROOT}/proveedores`;
    };

    /**
     * Renderiza la tabla de Rentabilidad con manejo de estado vacío.
     */
    window.renderRentabilidad = (data) => {
        const body = document.getElementById('rentabilidad-body');
        const cardsContainer = document.getElementById('rentabilidad-cards');
        if (!body) return;

        if (!data || data.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="6" class="px-8 py-16 text-center text-slate-400 italic font-medium uppercase tracking-widest">
                        <div class="flex flex-col items-center gap-2">
                            <i data-lucide="info" class="w-8 h-8 text-slate-300"></i>
                            <span>No hay registros de rentabilidad en este periodo</span>
                        </div>
                    </td>
                </tr>`;
            if (cardsContainer) {
                cardsContainer.innerHTML = `
                    <div class="col-span-full bg-slate-50 border border-dashed border-slate-200 p-8 rounded-2xl text-center text-slate-400 text-xs font-bold uppercase tracking-widest">
                        Sin operaciones registradas para el resumen
                    </div>`;
            }
            if (window.lucide) lucide.createIcons();
            return;
        }

        let html = '';
        data.forEach(item => {
            const utility = parseFloat(item.utilidad_bruta) || 0;
            const income = parseFloat(item.ingreso_total) || 0;
            const cost = parseFloat(item.costo_total) || 0;
            const margin = income > 0 ? ((utility / income) * 100).toFixed(1) : 0;
            
            html += `
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-4 py-4 font-black text-navy-blue text-xs uppercase">${item.tipo}</td>
                    <td class="px-4 py-4 text-center font-bold text-slate-500">${item.cantidad_operaciones}</td>
                    <td class="px-4 py-4 text-right font-bold text-slate-600">$${income.toLocaleString('es-CO')}</td>
                    <td class="px-4 py-4 text-right font-bold text-slate-400">$${cost.toLocaleString('es-CO')}</td>
                    <td class="px-4 py-4 text-right font-black text-emerald-600">$${utility.toLocaleString('es-CO')}</td>
                    <td class="px-4 py-4 text-right">
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-[10px] font-black border border-emerald-100">${margin}%</span>
                    </td>
                </tr>`;
        });

        body.innerHTML = html;
        if (window.lucide) lucide.createIcons();
    };

    /**
     * Renderiza la tabla de Cartera por Edades con manejo de estado vacío.
     */
    window.renderCartera = (data) => {
        const body = document.getElementById('cartera-body');
        if (!body) return;

        if (!data || data.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="5" class="px-8 py-16 text-center text-slate-400 italic font-medium uppercase tracking-widest">
                        <div class="flex flex-col items-center gap-2">
                            <i data-lucide="smile" class="w-8 h-8 text-emerald-300"></i>
                            <span>No se encontraron clientes con deudas pendientes actualmente</span>
                        </div>
                    </td>
                </tr>`;
            if (window.lucide) lucide.createIcons();
            return;
        }

        body.innerHTML = data.map(item => `
            <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-4 font-black text-navy-blue text-xs uppercase">${item.cliente_nombre}</td>
                <td class="px-4 py-4 text-right font-bold text-slate-500">$${parseFloat(item.rango_0_15).toLocaleString('es-CO')}</td>
                <td class="px-4 py-4 text-right font-bold text-slate-500">$${parseFloat(item.rango_16_30).toLocaleString('es-CO')}</td>
                <td class="px-4 py-4 text-right font-black text-rose-500">$${parseFloat(item.rango_30_mas).toLocaleString('es-CO')}</td>
                <td class="px-4 py-4 text-right font-black text-navy-blue">$${parseFloat(item.total_deuda).toLocaleString('es-CO')}</td>
            </tr>
        `).join('');
        if (window.lucide) lucide.createIcons();
    };
</script>
<script src="<?php echo URLROOT; ?>/js/reportes.js"></script>