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
                        <td colspan="6" class="px-8 py-16 text-center text-slate-400 italic animate-pulse">SINCRONIZANDO INVENTARIO...</td>
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
    // --- CONFIGURACIÓN Y ESTADO GLOBAL ---
    window.USER_ROLE = "<?php echo $_SESSION['user_role'] ?? 'MECANICO'; ?>";
    window.URLROOT = "<?php echo URLROOT; ?>";

    let inventoryState = {
        page: 1,
        limit: parseInt(document.getElementById('limitSelector')?.value || 10),
        search: ''
    };

    // --- FUNCIONES DE INTERFAZ ---
    const closeInventoryModal = () => {
        const modal = document.getElementById('inventoryModal');
        if (modal) modal.classList.add('hidden');
        document.getElementById('formInventory').reset();
        document.getElementById('imagePreview').innerHTML = '<i data-lucide="image" class="w-8 h-8 text-slate-300"></i>';
        if (window.lucide) window.lucide.createIcons();
    };

    window.openInventoryModal = () => {
        document.getElementById('modalTitle').textContent = 'Registrar Producto';
        document.getElementById('prodId').value = '';
        document.getElementById('formInventory').reset();
        document.getElementById('inventoryModal').classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
    };

    // --- LÓGICA DE DATOS ---
    window.fetchInventory = async () => {
        try {
            const tableBody = document.getElementById('tableBody');
            if (!tableBody) return;

            const offset = (parseInt(inventoryState.page) - 1) * parseInt(inventoryState.limit);
            const response = await fetch(`${window.URLROOT}/inventario/listar?q=${inventoryState.search}&limit=${inventoryState.limit}&offset=${offset}`);
            const result = await response.json();

            if (result.success) {
                renderInventoryTable(result.data || []);
                updatePaginationUI(result.total || 0, result.totalFiltrados || 0);
            } else {
                tableBody.innerHTML = '<tr><td colspan="7" class="px-8 py-16 text-center text-red-400 font-bold uppercase">Error al obtener datos</td></tr>';
            }
        } catch (err) {
            console.error("Error cargando inventario:", err);
        }
    };

    const renderInventoryTable = (items) => {
        const tableBody = document.getElementById('tableBody');
        if (!tableBody) return;

        if (items.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="px-8 py-16 text-center text-slate-400 italic font-medium uppercase tracking-widest">No se encontraron productos</td></tr>';
            return;
        }

        tableBody.innerHTML = items.map(item => {
            const stock = parseInt(item.stock ?? 0);
            const min = parseInt(item.stock_minimo ?? 0);
            const isCritical = stock <= min;
            const statusClass = isCritical ? 'bg-red-50 text-red-600 border-red-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100';
            const precio = parseFloat(item.precio ?? 0);
            
            // Imagen alusiva: Si no hay foto, mostramos un contenedor con un icono de caja
            const imageContent = item.imagen 
                ? `<img src="${window.URLROOT}/${item.imagen}" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shadow-sm" onerror="this.parentElement.innerHTML='<div class=\'w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-400\'><i data-lucide=\'package\' class=\'w-5 h-5\'></i></div>'">`
                : `<div class="w-10 h-10 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-center text-slate-300">
                    <i data-lucide="package" class="w-5 h-5"></i>
                   </div>`;
            
            return `
                <tr class="hover:bg-slate-50 transition-colors group">
                    <td class="px-8 py-4">
                        <div class="w-10 h-10">
                            ${imageContent}
                        </div>
                    </td>
                    <td class="px-8 py-4">
                        <span class="block font-black text-navy-blue uppercase text-xs">${item.nombre}</span>
                        <span class="text-[9px] text-slate-400 font-mono font-bold">#SKU-${String(item.id).padStart(4, '0')}</span>
                    </td>
                    <td class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">${item.categoria}</td>
                    <td class="px-8 py-4 font-black ${isCritical ? 'text-red-500' : 'text-slate-600'} text-sm">${stock}</td>
                    <td class="px-8 py-4 font-black text-navy-blue text-sm">$${precio.toLocaleString('es-CO')}</td>
                    <td class="px-8 py-4">
                        <span class="px-3 py-1 rounded-full text-[9px] font-black border ${statusClass}">${isCritical ? 'CRÍTICO' : 'DISPONIBLE'}</span>
                    </td>
                    <td class="px-8 py-4 text-right">
                        <div class="flex justify-end gap-2 md:opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="${window.URLROOT}/inventario/kardex/${item.id}" class="p-2 text-slate-400 hover:text-navy-blue hover:bg-slate-100 rounded-lg transition-all" title="Kardex"><i data-lucide="activity" class="w-4 h-4"></i></a>
                            <button onclick="window.editItem(${item.id})" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-all" title="Editar"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                            ${window.USER_ROLE === 'ADMINISTRADOR' ? `<button onclick="window.deleteItem(${item.id})" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"><i data-lucide="trash-2" class="w-4 h-4"></i></button>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
        
        if (typeof lucide !== 'undefined') lucide.createIcons();
    };

    const updatePaginationUI = (total, totalFiltrados) => {
        const t = Math.max(0, parseInt(total) || 0);
        const tf = Math.max(0, parseInt(totalFiltrados) || 0);
        const p = Math.max(1, parseInt(inventoryState.page) || 1);
        const l = Math.max(1, parseInt(inventoryState.limit) || 10);

        const start = tf === 0 ? 0 : ((p - 1) * l) + 1;
        const end = Math.min(p * l, tf);
        
        if (document.getElementById('startIndex')) document.getElementById('startIndex').textContent = start;
        if (document.getElementById('endIndex')) document.getElementById('endIndex').textContent = end;
        if (document.getElementById('totalItemsDisplay')) document.getElementById('totalItemsDisplay').textContent = tf;
        
        const countDisplay = document.getElementById('totalCount');
        if (countDisplay) countDisplay.textContent = t;

        const totalPages = Math.ceil(tf / l) || 1;
        const controls = document.getElementById('paginationControls');
        if (!controls) return;
        
        let html = '';
        if (totalPages > 1) {
            html += `
                <button onclick="changePage(${p - 1})" ${p === 1 ? 'disabled' : ''} class="p-2 border border-slate-200 rounded-lg bg-white hover:bg-slate-50 disabled:opacity-30 transition-all shadow-sm">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </button>
                <div class="px-4 py-2 text-[10px] font-black text-navy-blue bg-white border border-slate-200 rounded-lg uppercase tracking-tighter shadow-sm">
                    PÁGINA ${p} DE ${totalPages}
                </div>
                <button onclick="changePage(${p + 1})" ${p === totalPages ? 'disabled' : ''} class="p-2 border border-slate-200 rounded-lg bg-white hover:bg-slate-50 disabled:opacity-30 transition-all shadow-sm">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>
            `;
        }
        controls.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    };

    window.changePage = (newPage) => {
        if (newPage < 1) return;
        inventoryState.page = newPage;
        window.fetchInventory();
    };

    // Selector de registros por página
    document.getElementById('limitSelector')?.addEventListener('change', (e) => {
        inventoryState.limit = parseInt(e.target.value);
        inventoryState.page = 1;
        window.fetchInventory();
    });

    // --- EVENTOS ---
    document.getElementById('searchInventory')?.addEventListener('input', (e) => {
        inventoryState.search = e.target.value.trim();
        inventoryState.page = 1;
        clearTimeout(window.searchTimer);
        window.searchTimer = setTimeout(window.fetchInventory, 300);
    });

    document.getElementById('formInventory')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopImmediatePropagation(); // Evita que otros scripts guarden el producto de nuevo

        const btnSubmit = e.target.querySelector('button[type="submit"]');
        if (btnSubmit.disabled) return;

        const formData = new FormData(e.target);
        try {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="w-4 h-4 animate-spin" data-lucide="loader-2"></i>';
            if (typeof lucide !== 'undefined') lucide.createIcons();

            const response = await fetch(`${window.URLROOT}/inventario/guardar`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '<?php echo $_SESSION['csrf_token'] ?? ''; ?>' },
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                AppUtils.showToast(result.mensaje || 'Operación exitosa');
                closeInventoryModal();
                window.fetchInventory();
            } else {
                AppUtils.showToast(result.mensaje || 'Error', 'error');
            }
        } catch (err) {
            AppUtils.showToast('Error de conexión', 'error');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Guardar Item';
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('btnOpenModal')?.addEventListener('click', window.openInventoryModal);
        document.getElementById('btnCloseModal')?.addEventListener('click', closeInventoryModal);
        document.getElementById('btnCancel')?.addEventListener('click', closeInventoryModal);
        window.fetchInventory();
    });
</script>
<script src="<?php echo URLROOT; ?>/js/inventario.js"></script>