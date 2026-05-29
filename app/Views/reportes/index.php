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
        <button onclick="switchReportTab('devoluciones')" id="tab-devoluciones" class="pb-3 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue font-bold transition-all text-sm uppercase tracking-widest flex items-center gap-2">
            <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Devoluciones
        </button>
    </div>

    <!-- SECCIÓN 1: RESUMEN CONSOLIDADO -->
    <div id="sec-resumen" class="space-y-6">
        <!-- Buscador de Flujo de Caja -->
        <div class="relative max-w-md">
            <i data-lucide="search" class="absolute left-3 top-2.5 text-slate-400 w-5 h-5"></i>
            <input type="text" id="search-report" placeholder="Buscar cliente, placa, proveedor o descripción..." 
                   class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-neon-green outline-none transition-all shadow-sm">
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
                            <th class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="report-body" class="divide-y divide-slate-50 bg-white">
                        <!-- Contenido generado por JavaScript -->
                    </tbody>
                </table>
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

    <!-- SECCIÓN 3: HISTORIAL DE DEVOLUCIONES -->
    <div id="sec-devoluciones" class="hidden animate-in fade-in duration-500">
        <div class="glass-card rounded-2xl border border-slate-100 overflow-hidden" id="devoluciones-list-container">
            <!-- La tabla se genera vía JS -->
        </div>
    </div>



    <div class="flex justify-end pt-4">
        <button onclick="window.print()" class="text-[10px] font-bold bg-slate-800 text-white px-6 py-2 rounded-xl flex items-center gap-2 hover:bg-black transition-all">
            <i data-lucide="printer" class="w-4 h-4"></i> IMPRIMIR REPORTE
        </button>
    </div>
</div>
<script>
    // Definir URLROOT para que esté disponible en los scripts de reportes
    if (typeof window.URLROOT === 'undefined') {
        window.URLROOT = "<?php echo URLROOT; ?>";
    }

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
<script src="<?php echo URLROOT; ?>/public/js/reportes.js"></script>