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

    <!-- Totales Rápidos (Siempre Visibles) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card p-6 rounded-2xl border-l-4 border-blue-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Ingresos (Ventas)</p>
            <h2 id="total-ingresos" class="text-3xl font-black text-blue-600">$0.00</h2>
        </div>
        <div class="glass-card p-6 rounded-2xl border-l-4 border-red-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Egresos (Gastos)</p>
            <h2 id="total-egresos" class="text-3xl font-black text-red-600">$0.00</h2>
        </div>
        <div class="glass-card p-6 rounded-2xl border-l-4 border-neon-green shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Utilidad Neta</p>
            <h2 id="total-balance" class="text-3xl font-black text-navy-blue">$0.00</h2>
        </div>
    </div>

    <!-- Tabs de Navegación -->
    <div class="flex gap-6 border-b border-slate-200">
        <button onclick="switchReportTab('resumen')" id="tab-resumen" class="pb-3 px-1 border-b-2 border-neon-green font-bold text-navy-blue transition-all text-xs uppercase tracking-widest flex items-center gap-2">
            <i data-lucide="pie-chart" class="w-4 h-4"></i> Flujo de Caja
        </button>
        <button onclick="switchReportTab('detallado')" id="tab-detallado" class="pb-3 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue font-bold transition-all text-xs uppercase tracking-widest flex items-center gap-2">
            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Auditoría de Trabajos
        </button>
    </div>

    <!-- SECCIÓN 1: RESUMEN CONSOLIDADO -->
    <div id="sec-resumen" class="space-y-6">
        <div class="glass-card rounded-2xl overflow-hidden shadow-xl border border-slate-100">
            <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Cronología de Movimientos</h3>
            </div>
            <div class="p-6">
                <table id="reportTable" class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] font-black text-slate-400 uppercase border-b border-slate-100">
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Descripcion</th>
                            <th class="px-4 py-3 text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody id="report-body" class="text-sm"></tbody>
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

        <!-- Contenedor de la lista agrupada -->
        <div id="audit-list-container" class="grid grid-cols-1 gap-4">
            <!-- Dinámico desde JS -->
            <div class="text-center py-20 text-slate-400 italic">Cargando desglose de trabajos...</div>
        </div>
    </div>

    <div class="flex justify-end pt-4">
        <button onclick="window.print()" class="text-[10px] font-bold bg-slate-800 text-white px-6 py-2 rounded-xl flex items-center gap-2 hover:bg-black transition-all">
            <i data-lucide="printer" class="w-4 h-4"></i> IMPRIMIR REPORTE
        </button>
    </div>
</div>
<script src="<?php echo URLROOT; ?>/public/js/reportes.js"></script>