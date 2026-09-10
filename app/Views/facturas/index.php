<div class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Historial de todas las facturas realizadas en el sistema.</p>
        </div>
    </div>

    <!-- Filtros: Búsqueda, Rango de fechas -->
    <div class="flex flex-col md:flex-row gap-4 mb-6">
        <div class="flex-1 relative min-w-0">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500"></i>
            <input type="text" id="searchFacturas" placeholder="Filtrar por N° factura, cliente, placa..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-4 pl-12 pr-4 text-slate-700 outline-none focus:border-neon-green transition-all shadow-sm">
        </div>
        <div class="relative w-full md:w-48">
            <label class="sr-only">Desde</label>
            <input type="date" id="fechaDesde" placeholder="Desde" class="w-full bg-white border border-slate-200 rounded-xl py-4 px-4 text-sm outline-none focus:border-neon-green transition-all shadow-sm">
        </div>
        <div class="relative w-full md:w-48">
            <label class="sr-only">Hasta</label>
            <input type="date" id="fechaHasta" placeholder="Hasta" class="w-full bg-white border border-slate-200 rounded-xl py-4 px-4 text-sm outline-none focus:border-neon-green transition-all shadow-sm">
        </div>
    </div>

    <div class="flex flex-col md:flex-row items-center justify-end gap-4 mb-6">
        <div class="hidden md:flex items-center gap-2 text-slate-500 text-xs bg-white border border-slate-200 rounded-xl px-3 py-2.5 shadow-sm">
            <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-400"></i>
            <span class="font-medium">Total:</span>
            <strong id="totalCount" class="text-navy-blue text-base ml-1"><?php echo $data['total_items'] ?? 0; ?></strong>
        </div>
        <div class="relative">
            <select id="limitSelector" class="appearance-none bg-white border border-slate-200 rounded-xl py-4 px-10 text-sm font-bold text-navy-blue outline-none focus:border-neon-green shadow-sm cursor-pointer pr-10">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
        </div>
        <div class="flex items-center">
            <button id="btnLimpiarFiltros" class="bg-slate-100 text-slate-600 font-bold px-4 py-4 rounded-xl hover:bg-slate-200 uppercase text-xs flex items-center gap-2 transition-all">
                <i data-lucide="x-circle" class="w-4 h-4"></i> Limpiar
            </button>
        </div>
    </div>

    <div class="glass-card rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table id="facturasTable" class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                        <th class="px-6 py-4">N° Factura</th>
                        <th class="px-6 py-4">Fecha</th>
                        <th class="px-6 py-4">Cliente</th>
                        <th class="px-6 py-4">Placa / Vehículo</th>
                        <th class="px-6 py-4">Tipo</th>
                        <th class="px-6 py-4">Vendedor</th>
                        <th class="px-6 py-4">Items</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Total</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-slate-100 text-sm text-slate-600">
                    <tr>
                        <td colspan="10" class="px-8 py-16 text-center text-slate-400 italic animate-pulse">CARGANDO FACTURAS...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Paginación Manual -->
        <div class="px-8 py-4 bg-white border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Mostrando <span id="startIndex">0</span> - <span id="endIndex">0</span> de <span id="totalItemsDisplay">0</span> facturas
            </div>
            <div class="flex items-center gap-2" id="paginationControls">
                <!-- Los botones de navegación se generan dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
    // Previene el error de currentData is not defined antes de cargar el JS de facturas
    window.currentData = [];
</script>
<script src="<?php echo URLROOT; ?>/js/facturas.js"></script>