<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Historial de Ventas</h2>
        <p class="text-slate-500">Consulte todas las ventas realizadas en el taller.</p>
    </div>
</div>

<!-- Filtros y Buscador -->
<div class="glass-card p-4 rounded-xl mb-6 flex flex-wrap gap-4 items-center">
    <div class="relative flex-1 min-w-[300px]">
        <i data-lucide="search" class="absolute left-3 top-2.5 text-slate-400 w-5 h-5"></i>
        <input type="text" id="searchVentas" placeholder="Buscar por ID, placa o cliente..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none transition-all">
    </div>
    <div class="px-4 py-2 bg-navy-blue text-white rounded-lg font-bold text-sm">
        Total Ventas: <span id="totalCount">0</span>
    </div>
</div>

<!-- Tabla de Ventas -->
<div class="glass-card rounded-xl overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">ID Venta</th>
                    <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Fecha</th>
                    <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Vehículo</th>
                    <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Cliente</th>
                    <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Total</th>
                    <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <!-- Se llena dinámicamente con historial.js -->
                <tr><td colspan="6" class="text-center py-20 text-slate-300 italic uppercase text-xs tracking-widest">Cargando historial de ventas...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Script del módulo -->
<script src="<?php echo URLROOT; ?>/js/historial.js"></script>