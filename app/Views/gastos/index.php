<div class="p-6 space-y-6">
    <!-- Encabezado con estadísticas rápidas o título -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase"><?php echo $data['titulo']; ?></h1>
            <p class="text-slate-500 text-sm">Control detallado de egresos operativos y servicios del taller.</p>
        </div>
        
        <div class="flex flex-col items-end gap-2">
            <button onclick="openExpenseModal()" class="flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-red-200 group">
                <i data-lucide="plus-circle" class="w-5 h-5 group-hover:rotate-90 transition-transform"></i>
                REGISTRAR GASTO
            </button>
            <button onclick="imprimirGastosActuales()" class="flex items-center gap-2 bg-navy-blue text-neon-green px-4 py-2 rounded-xl text-[10px] font-black uppercase shadow-md hover:scale-105 transition-all">
                <i data-lucide="printer" class="w-4 h-4"></i>
                Imprimir Reporte
            </button>
        </div>
    </div>

    <!-- Buscador -->
    <div class="flex flex-col md:flex-row gap-4 mb-6">
        <div class="relative flex-1 max-w-md">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
            <input type="text" id="searchExpenses" placeholder="Buscar por descripción o categoría..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-3 pl-12 pr-4 text-slate-700 outline-none focus:border-red-500 transition-all shadow-sm">
        </div>
        
        <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-xl border border-slate-200 shadow-sm">
            <input type="date" id="exp-desde" class="text-xs font-bold border-none outline-none text-slate-600" value="<?php echo date('Y-m-01'); ?>">
            <span class="text-slate-300 font-black">/</span>
            <input type="date" id="exp-hasta" class="text-xs font-bold border-none outline-none text-slate-600" value="<?php echo date('Y-m-d'); ?>">
            <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
        </div>
    </div>

    <!-- Contenedor Principal (Tabla) -->
    <div class="glass-card rounded-2xl overflow-hidden border border-slate-200/60 shadow-xl">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-red-500"></i>
                Historial de Movimientos
            </h2>
            <div class="flex items-center gap-4">
                <div class="px-4 py-2 bg-white border border-slate-200 rounded-xl font-bold text-xs text-slate-500 shadow-sm">
                    Total: <span id="totalCount" class="text-navy-blue">0</span>
                </div>
                <select id="limitSelector" class="bg-white border border-slate-200 rounded-xl py-2 px-4 text-xs font-bold text-navy-blue outline-none focus:border-neon-green shadow-sm cursor-pointer">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        <div class="p-6 overflow-x-auto">
            <table id="expensesTable" class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-400 text-[10px] uppercase font-black tracking-widest border-b border-slate-100">
                        <th class="px-4 py-4">Fecha</th>
                        <th class="px-4 py-4">Descripción del Gasto</th>
                        <th class="px-4 py-4">Categoría</th>
                        <th class="px-4 py-4">Monto</th>
                        <th class="px-4 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="expensesBody" class="text-sm text-slate-600 divide-y divide-slate-50 min-h-[200px]">
                    <!-- Cargado dinámicamente mediante gastos.js -->
                </tbody>
            </table>
        </div>
        
        <!-- Pie de Tabla Armonizado -->
        <div class="px-8 py-4 bg-slate-50/50 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Mostrando <span id="startIndex">0</span> - <span id="endIndex">0</span> de <span id="totalItemsDisplay">0</span> gastos registrados
            </div>
            <div class="flex items-center gap-2" id="paginationControls"></div>
        </div>
    </div>
</div>
<script src="<?php echo URLROOT; ?>/js/gastos.js"></script>