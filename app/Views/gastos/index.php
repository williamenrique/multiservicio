<div class="p-6 space-y-6">
    <!-- Encabezado con estadísticas rápidas o título -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase"><?php echo $data['titulo']; ?></h1>
            <p class="text-slate-500 text-sm">Control detallado de egresos operativos y servicios del taller.</p>
        </div>
        
        <button onclick="openExpenseModal()" class="flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-red-200 group">
            <i data-lucide="plus-circle" class="w-5 h-5 group-hover:rotate-90 transition-transform"></i>
            REGISTRAR GASTO
        </button>
    </div>

    <!-- Contenedor Principal (Tabla) -->
    <div class="glass-card rounded-2xl overflow-hidden border border-slate-200/60 shadow-xl">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-red-500"></i>
                Historial de Movimientos
            </h2>
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
                <tbody class="text-sm text-slate-600 divide-y divide-slate-50">
                    <!-- Cargado dinámicamente mediante gastos.js -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="<?= URLROOT ?>/public/js/gastos.js"></script>