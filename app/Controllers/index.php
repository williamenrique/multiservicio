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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card p-6 rounded-2xl border-l-4 border-blue-500">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Ingresos (Ventas)</p>
            <h2 id="total-ingresos" class="text-3xl font-black text-blue-600">$0.00</h2>
        </div>
        <div class="glass-card p-6 rounded-2xl border-l-4 border-red-500">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Egresos (Gastos)</p>
            <h2 id="total-egresos" class="text-3xl font-black text-red-600">$0.00</h2>
        </div>
        <div class="glass-card p-6 rounded-2xl border-l-4 border-neon-green">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Utilidad Neta</p>
            <h2 id="total-balance" class="text-3xl font-black text-navy-blue">$0.00</h2>
        </div>
    </div>

    <div class="glass-card rounded-2xl overflow-hidden shadow-xl border border-slate-100">
        <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Detalle Cronológico de Movimientos</h3>
            <button onclick="exportarExcel()" class="text-[10px] font-bold bg-emerald-500 text-white px-3 py-1.5 rounded-lg flex items-center gap-2">
                <i data-lucide="download" class="w-3 h-3"></i> EXPORTAR
            </button>
        </div>
        <div class="p-6">
            <table id="reportTable" class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase border-b border-slate-100">
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3 text-right">Monto</th>
                    </tr>
                </thead>
                <tbody id="report-body" class="text-sm">
                    <!-- Dinámico -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="<?php echo URLROOT; ?>/public/js/reportes.js"></script>