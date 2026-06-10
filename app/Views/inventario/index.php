<div class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Control de stock, repuestos y servicios del taller.</p>
        </div>
        <?php if($_SESSION['user_role'] === 'ADMINISTRADOR'): ?>
        <button id="btnOpenModal" class="bg-neon-green text-black font-black px-6 py-3 rounded-xl flex items-center gap-2 transition-all transform hover:scale-[1.05] active:scale-95 shadow-lg shadow-neon-green/40 uppercase tracking-widest text-xs">
            <i data-lucide="plus-circle"></i>
            NUEVO PRODUCTO
        </button>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="md:col-span-2 relative">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500"></i>
            <input type="text" id="searchInventory" placeholder="Filtrar por nombre, categoría o SKU..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-4 pl-12 pr-4 text-slate-700 outline-none focus:border-neon-green transition-all shadow-sm">
        </div>
        <div class="flex items-center gap-4">
            <div class="flex-1 hidden md:flex items-center justify-between text-slate-500 text-sm bg-white border border-slate-200 rounded-xl px-4 py-3 shadow-sm h-full">
                <div class="flex items-center gap-2">
                    <i data-lucide="box" class="w-4 h-4 text-slate-400"></i>
                    <span>Total:</span>
                </div>
                <strong id="totalCount" class="text-navy-blue text-lg"><?php echo $data['total_items'] ?? 0; ?></strong>
            </div>
            <div class="relative">
                <select id="limitSelector" class="appearance-none bg-white border border-slate-200 rounded-xl py-4 px-6 text-sm font-bold text-navy-blue outline-none focus:border-neon-green shadow-sm cursor-pointer">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
            </div>
        </div>
    </div>

    <div class="glass-card rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table id="inventoryTable" class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                        <th class="px-8 py-6">Imagen</th>
                        <th class="px-8 py-6">Producto</th>
                        <th class="px-8 py-6">Categoría</th>
                        <th class="px-8 py-6">Stock</th>
                        <th class="px-8 py-6">Precio Unitario</th>
                        <th class="px-8 py-6">Estado</th>
                        <th class="px-8 py-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-slate-100 text-sm text-slate-600">
                    <tr>
                        <td colspan="7" class="px-8 py-16 text-center text-slate-400 italic animate-pulse">SINCRONIZANDO INVENTARIO...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Paginación Manual (Acomodado tras eliminar DataTables) -->
        <div class="px-8 py-4 bg-white border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Mostrando <span id="startIndex">0</span> - <span id="endIndex">0</span> de <span id="totalItemsDisplay">0</span> productos
            </div>
            <div class="flex items-center gap-2" id="paginationControls">
                <!-- Los botones de navegación se generan dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="inventoryModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h2 id="modalTitle" class="text-xl font-bold text-navy-blue uppercase tracking-wider">Registrar Producto</h2>
            <button id="btnCloseModal" class="text-gray-500 hover:text-navy-blue"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
        
        <form id="formInventory" class="p-6 space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="id" id="prodId">
            
            <div class="flex flex-col items-center gap-4 mb-4">
                <div class="relative group cursor-pointer" onclick="document.getElementById('fileInput').click()">
                    <div id="imagePreview" class="w-32 h-32 bg-slate-100 rounded-2xl border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden group-hover:border-neon-green transition-all">
                        <i data-lucide="image" class="w-8 h-8 text-slate-300"></i>
                    </div>
                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl">
                        <i data-lucide="upload" class="text-white w-6 h-6"></i>
                    </div>
                    <input type="file" name="imagen_archivo" id="fileInput" class="hidden" accept="image/*">
                </div>
                <div class="w-full">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">O pega una URL de imagen externa</label>
                    <input type="text" name="imagen" id="prodImagen" placeholder="https://ejemplo.com/imagen.jpg" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-4 text-xs outline-none focus:border-neon-green transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nombre del Producto / Servicio</label>
                <input type="text" name="nombre" id="prodNombre" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green uppercase transition-all">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Categoría</label>
                    <select name="categoria" id="prodCategoria" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                        <option value="MECANICA">MECÁNICA</option>
                        <option value="REPUESTOS">REPUESTOS</option>
                        <option value="LUBRICANTES">LUBRICANTES</option>
                        <option value="ELECTRICIDAD">ELECTRICIDAD</option>
                        <option value="OTROS">OTROS</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Existencia (Stock)</label>
                    <input type="number" name="stock" id="prodStock" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Mínimo (Alerta)</label>
                    <input type="number" name="stock_minimo" id="prodStockMin" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Precio de Venta</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                    <input type="number" step="0.01" name="precio" id="prodPrecio" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 pl-8 pr-4 outline-none focus:border-neon-green transition-all">
                </div>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" id="btnCancel" class="flex-1 bg-slate-100 text-slate-600 font-bold py-3 rounded-xl hover:bg-slate-200 uppercase text-xs">Cancelar</button>
                <button type="submit" class="flex-1 bg-neon-green text-black font-black py-3 rounded-xl hover:scale-[1.02] uppercase text-xs flex items-center justify-center gap-2">Guardar Item</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Previene el error de currentData is not defined antes de cargar el JS de inventario
    window.currentData = [];
</script>
<script src="<?php echo URLROOT; ?>/js/inventario.js"></script>