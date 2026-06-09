<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Gestión de Proveedores</h2>
        <p class="text-slate-500">Administre la lista de proveedores e ingresos de mercancía</p>
    </div>
    <button id="btnOpenModal" class="bg-neon-green text-navy-blue px-6 py-2 rounded-xl font-black flex items-center gap-2 hover:brightness-110 transition shadow-lg shadow-neon-green/20">
        <i data-lucide="plus-circle" class="w-5 h-5"></i> Nuevo Proveedor
    </button>
</div>

<!-- Pestañas de Navegación -->
<div class="flex gap-6 mb-6 border-b border-slate-200">
    <button id="tab-lista" onclick="switchTab('lista')" class="pb-3 px-1 border-b-2 border-neon-green font-bold text-navy-blue transition-all flex items-center gap-2 text-sm uppercase tracking-wider">
        <i data-lucide="list" class="w-4 h-4"></i> Directorio
    </button>
    <button id="tab-deudas" onclick="switchTab('deudas')" class="pb-3 px-1 border-b-2 border-transparent text-slate-400 hover:text-navy-blue font-bold transition-all flex items-center gap-2 text-sm uppercase tracking-wider">
        <i data-lucide="wallet" class="w-4 h-4"></i> Cuentas por Pagar
    </button>
</div>

<!-- Sección de Directorio -->
<div id="sec-lista">
    <!-- Filtros y Buscador -->
    <div class="glass-card p-4 rounded-xl mb-6 flex flex-wrap gap-4 items-center">
        <div class="relative flex-1 min-w-[300px]">
            <i data-lucide="search" class="absolute left-3 top-2.5 text-slate-400 w-5 h-5"></i>
            <input type="text" id="searchProveedor" placeholder="Buscar por nombre o NIT..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none transition-all">
        </div>
        <div class="flex items-center gap-4">
            <div class="px-4 py-2 bg-navy-blue text-white rounded-lg font-bold text-sm h-full flex items-center">
                Total: <span id="totalCount" class="ml-2">0</span>
            </div>
            <select id="limitSelector" class="bg-white border border-slate-200 rounded-lg py-2 px-3 text-xs font-bold text-navy-blue outline-none focus:ring-2 focus:ring-neon-green shadow-sm cursor-pointer">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <!-- Tabla de Proveedores -->
    <div class="glass-card rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table id="proveedorTable" class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">ID / NIT</th>
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Nombre Empresa</th>
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Contacto</th>
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <!-- Se llena dinámicamente con proveedores.js -->
                    <tr><td colspan="4" class="px-8 py-16 text-center text-slate-400 italic tracking-widest animate-pulse font-medium">CARGANDO DIRECTORIO...</td></tr>
                </tbody>
            </table>
        </div>
        <!-- Pie de Tabla Unificado -->
        <div class="px-8 py-4 bg-white border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Mostrando <span id="startIndex">0</span> - <span id="endIndex">0</span> de <span id="totalItemsDisplay">0</span> proveedores
            </div>
            <div class="flex items-center gap-2" id="paginationControls"></div>
        </div>
    </div>
</div>

<!-- Sección de Deudas -->
<div id="sec-deudas" class="hidden">
    <div class="glass-card rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Proveedor</th>
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Facturas Pend.</th>
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Saldo Pendiente</th>
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider">Próximo Vencimiento</th>
                        <th class="px-8 py-4 font-bold text-slate-400 text-[10px] uppercase tracking-wider text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableDeudasBody">
                    <tr><td colspan="5" class="text-center py-20 text-slate-300 italic uppercase text-xs tracking-widest">Cargando deudas...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Registro/Edición de Proveedor -->
<div id="proveedorModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center hidden p-4">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 id="modalTitle" class="text-lg font-black text-navy-blue uppercase tracking-tighter">Registrar Proveedor</h3>
            <button id="btnCloseModal" class="text-slate-400 hover:text-red-500 transition-colors">
                <i data-lucide="x-circle"></i>
            </button>
        </div>
        
        <form id="formProveedor" class="p-6 space-y-4">
            <input type="hidden" name="id_existente" id="provIdExistente">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">NIT o Identificación</label>
                    <input type="text" name="id" id="provId" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none uppercase text-sm font-bold">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nombre de la Empresa</label>
                    <input type="text" name="nombre" id="provNombre" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none uppercase text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Teléfono</label>
                        <input type="text" name="telefono" id="provTelefono" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Email</label>
                        <input type="email" name="email" id="provEmail" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-sm lowercase">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Dirección Física</label>
                    <textarea name="direccion" id="provDireccion" rows="2" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-sm uppercase"></textarea>
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button type="button" id="btnCancel" class="flex-1 px-4 py-2.5 border border-slate-200 text-slate-500 font-bold rounded-lg hover:bg-slate-50 transition-all uppercase text-xs">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2.5 bg-navy-blue text-white font-bold rounded-lg hover:opacity-90 transition-all uppercase text-xs shadow-lg shadow-navy-blue/20 flex items-center justify-center gap-2">Guardar Proveedor</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Ingreso de Mercancía (Compra con Precio de Reposición) -->
<div id="compraModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center hidden p-4">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <div>
                <h3 class="text-lg font-black text-navy-blue uppercase tracking-tighter">Ingreso de Mercancía</h3>
                <p class="text-[10px] text-slate-400 font-bold uppercase" id="compraProvNombre">Proveedor: ---</p>
            </div>
            <button id="btnCloseCompraModal" class="text-slate-400 hover:text-red-500 transition-colors">
                <i data-lucide="x-circle"></i>
            </button>
        </div>
        
        <form id="formCompra" class="p-6 space-y-4">
            <input type="hidden" name="proveedor_id" id="compraProvId">
            <input type="hidden" name="producto_id" id="compraProdId">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nombre del Artículo / Repuesto</label>
                    <input type="text" name="nombre" id="compraNombre" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none uppercase text-sm font-bold">
                </div>
                
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Cantidad</label>
                    <input type="number" name="cantidad" id="compraCantidad" value="1" min="1" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-sm font-bold">
                </div>
                
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Costo Unitario ($)</label>
                    <input type="number" step="0.01" name="costo" id="compraCosto" value="0.00" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-sm font-bold">
                </div>

                <div class="p-4 bg-blue-50 rounded-xl border border-blue-100 md:col-span-2">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[10px] font-black text-blue-600 uppercase">Margen de Ganancia (%)</span>
                        <input type="number" id="compraMarkup" value="<?php echo $data['markup_default'] ?? 30; ?>" class="w-16 p-1 text-xs font-bold bg-white border border-blue-200 rounded text-center">
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-black text-slate-500 uppercase">Precio Venta Sugerido:</span>
                        <span id="compraSugerido" class="text-sm font-black text-navy-blue">$ 0.00</span>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Precio de Venta Final ($)</label>
                    <input type="number" step="0.01" name="precio_venta" id="compraPrecioVenta" required class="w-full p-2.5 bg-neon-green/10 border border-neon-green/30 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-sm font-black text-navy-blue">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Abono Inicial</label>
                    <input type="number" step="0.01" name="pagado" id="compraPagado" value="0" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-sm">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Fecha de Cobro (Si queda deuda)</label>
                    <input type="date" name="fecha_cobro" id="compraFechaCobro" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-sm">
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button type="button" id="btnCancelCompra" class="flex-1 px-4 py-2.5 border border-slate-200 text-slate-500 font-bold rounded-lg hover:bg-slate-50 transition-all uppercase text-xs">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2.5 bg-navy-blue text-white font-bold rounded-lg hover:opacity-90 transition-all uppercase text-xs shadow-lg shadow-navy-blue/20 flex items-center justify-center gap-2">Registrar Ingreso</button>
            </div>
        </form>
    </div>
</div>

<!-- Variables de Configuración para JS -->
<script>
    const MARKUP_DEFAULT = <?php echo $data['markup_default'] ?? 30; ?>;

    const closeModal = () => {
        const modal = document.getElementById('proveedorModal');
        if (modal) modal.classList.add('hidden');
        document.getElementById('formProveedor').reset();
        document.getElementById('provIdExistente').value = '';
        document.getElementById('provId').readOnly = false;
    };

    /**
     * Función global para editar un proveedor.
     * Se encarga de buscar los datos y llenar el modal.
     */
    window.editItem = async (id) => {
        try {
            const res = await fetch(`${URLROOT}/proveedores/obtener/${id}`);
            const data = await res.json();

            if (data) {
                // Cambiar título y preparar campos
                document.getElementById('modalTitle').textContent = 'Editar Proveedor';
                document.getElementById('provId').value = data.id;
                document.getElementById('provId').readOnly = true; // El ID/NIT no suele cambiarse
                document.getElementById('provIdExistente').value = data.id; // Marca para el controlador
                
                document.getElementById('provNombre').value = data.nombre;
                document.getElementById('provTelefono').value = data.telefono;
                document.getElementById('provEmail').value = data.email;
                document.getElementById('provDireccion').value = data.direccion;

                // Mostrar el modal
                document.getElementById('proveedorModal').classList.remove('hidden');
            }
        } catch (error) {
            console.error("Error al cargar datos del proveedor:", error);
        }
    };

    // Resetear el formulario cuando se abre para un nuevo proveedor
    document.getElementById('btnOpenModal')?.addEventListener('click', () => {
        document.getElementById('formProveedor').reset();
        document.getElementById('provIdExistente').value = '';
        document.getElementById('provId').readOnly = false;
        document.getElementById('modalTitle').textContent = 'Registrar Proveedor';
    });

    // Manejadores para cerrar el modal (Icono X y botón Cancelar)
    document.getElementById('btnCloseModal')?.addEventListener('click', closeModal);
    document.getElementById('btnCancel')?.addEventListener('click', closeModal);

    // Manejador para cerrar el modal de Compras
    const closeCompraModal = () => {
        const modal = document.getElementById('compraModal');
        if (modal) modal.classList.add('hidden');
        document.getElementById('formCompra').reset();
    };

    document.getElementById('btnCloseCompraModal')?.addEventListener('click', closeCompraModal);
    document.getElementById('btnCancelCompra')?.addEventListener('click', closeCompraModal);
</script>

<!-- Script específico del módulo -->
<script src="<?php echo URLROOT; ?>/js/proveedores.js"></script>