<?php if (!defined('URLROOT')) exit('No direct script access allowed'); ?>
<div class="container mx-auto p-6 space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Gestión de presupuestos y cotizaciones del taller</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <button onclick="abrirModalCrear()"
                class="bg-neon-green text-navy-blue px-5 py-3 rounded-xl font-black flex items-center gap-2 transition-all hover:brightness-110 shadow-lg shadow-neon-green/20">
                <i data-lucide="plus-circle" class="w-5 h-5"></i> Nuevo Presupuesto
            </button>
            <button onclick="abrirModalRepuestos()"
                class="bg-blue-600 text-white px-5 py-3 rounded-xl font-black flex items-center gap-2 transition-all hover:bg-blue-700 shadow-lg shadow-blue-600/20">
                <i data-lucide="package" class="w-5 h-5"></i> Presupuesto Repuestos
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-8 gap-4">
        <div class="glass-card p-4 rounded-2xl border-l-4 border-blue-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Total</p>
            <h2 id="stat-total" class="text-xl font-black text-blue-600"><?php echo $data['stats']->total ?? 0; ?></h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-slate-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Borradores</p>
            <h2 id="stat-borradores" class="text-xl font-black text-slate-600">
                <?php echo $data['stats']->borradores ?? 0; ?></h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-amber-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Enviados</p>
            <h2 id="stat-enviados" class="text-xl font-black text-amber-600">
                <?php echo $data['stats']->enviados ?? 0; ?></h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-emerald-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Activos</p>
            <h2 id="stat-activos" class="text-xl font-black text-emerald-600">
                <?php echo $data['stats']->activos ?? 0; ?></h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-blue-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">En Proceso</p>
            <h2 id="stat-en-proceso" class="text-xl font-black text-blue-600">
                <?php echo $data['stats']->en_proceso ?? 0; ?></h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-emerald-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Aceptados</p>
            <h2 id="stat-aceptados" class="text-xl font-black text-emerald-600">
                <?php echo $data['stats']->aceptados ?? 0; ?></h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-red-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Rechazados</p>
            <h2 id="stat-rechazados" class="text-xl font-black text-red-600">
                <?php echo $data['stats']->rechazados ?? 0; ?></h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-purple-500 shadow-sm bg-purple-50/30">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Monto Aceptado</p>
            <h2 id="stat-monto" class="text-xl font-black text-purple-600">
                <?php echo number_format($data['stats']->monto_aceptado ?? 0, 2, ',', '.'); ?></h2>
        </div>
    </div>

    <!-- Filtros -->
    <div class="glass-card p-4 rounded-2xl shadow-sm border border-slate-100">
        <form id="presupuesto-filters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Estado</label>
                <select name="estado" id="filter-estado"
                    class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                    <option value="">Todos</option>
                    <option value="BORRADOR">Borrador</option>
                    <option value="ENVIADO">Enviado</option>
                    <option value="ACTIVO">Activo</option>
                    <option value="EN_PROCESO">En Proceso</option>
                    <option value="ACEPTADO">Aceptado</option>
                    <option value="RECHAZADO">Rechazado</option>
                    <option value="EXPIRADO">Expirado</option>
                    <option value="CONVERTIDO">Convertido</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Desde</label>
                <input type="date" name="desde" id="filter-desde"
                    class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Hasta</label>
                <input type="date" name="hasta" id="filter-hasta"
                    class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
            </div>
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Buscar</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                    <input type="text" name="search" id="filter-search"
                        placeholder="Buscar por número, cliente, cédula..."
                        class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                </div>
            </div>
            <div class="lg:col-span-2 flex items-end gap-2">
                <button type="button" onclick="cargarPresupuestos(1)"
                    class="bg-neon-green text-black px-4 py-2 rounded-lg font-black text-sm uppercase hover:opacity-90 transition flex items-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4"></i> Filtrar
                </button>
                <button type="button" onclick="limpiarFiltrosPresupuesto()"
                    class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg font-black text-sm uppercase hover:bg-slate-50 transition flex items-center gap-2">
                    <i data-lucide="filter-x" class="w-4 h-4"></i> Limpiar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de Presupuestos -->
    <div class="glass-card rounded-2xl overflow-hidden shadow-xl border border-slate-100">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                        <th class="px-6 py-4">Número</th>
                        <th class="px-6 py-4">Cliente</th>
                        <th class="px-6 py-4">Fecha</th>
                        <th class="px-6 py-4">Vencimiento</th>
                        <th class="px-6 py-4 text-right">Total</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="presupuestos-body" class="divide-y divide-slate-50">
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center text-slate-400">
                            <div class="flex flex-col items-center gap-2">
                                <div
                                    class="w-8 h-8 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin">
                                </div>
                                <p class="text-xs font-bold uppercase tracking-widest">Cargando presupuestos...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div id="presupuestos-pagination"
            class="px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-3 border-t border-slate-100 hidden">
            <p id="presupuestos-info" class="text-xs text-slate-500 font-medium"></p>
            <div id="presupuestos-pagination-controls" class="flex gap-1"></div>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar Presupuesto -->
<div id="presupuestoModal"
    class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden overflow-y-auto">
    <div class="bg-white w-full max-w-6xl rounded-3xl shadow-2xl overflow-hidden my-auto max-h-[95vh] flex flex-col">
        <div
            class="p-4 sm:p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50 sticky top-0 z-10">
            <h2 id="presupuestoModalTitle" class="text-lg sm:text-xl font-bold text-navy-blue uppercase tracking-wider">
                Nuevo Presupuesto</h2>
            <button id="btnClosePresupuestoModal" class="text-gray-500 hover:text-navy-blue"><i data-lucide="x"
                    class="w-6 h-6"></i></button>
        </div>

        <form id="formPresupuesto" class="p-4 sm:p-6 space-y-5 overflow-y-auto flex-1" enctype="multipart/form-data">
            <input type="hidden" name="id" id="presupuesto-id">
            <input type="hidden" name="estado" id="presupuesto-estado" value="BORRADOR">

            <!-- Sección Cliente y Vehículo -->
            <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-100">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4 text-neon-green"></i> Información del Cliente y Vehículo
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Cliente *</label>
                        <div class="relative">
                            <input type="text" name="cliente_nombre" id="presupuesto-cliente-nombre" required
                                placeholder="Nombre del cliente"
                                class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all uppercase">
                            <div id="cliente-suggestions"
                                class="absolute z-20 w-full bg-white border border-slate-200 rounded-xl shadow-xl mt-1 hidden max-h-40 overflow-y-auto">
                            </div>
                        </div>
                        <input type="hidden" name="cliente_id" id="presupuesto-cliente-id">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Cédula / NIT</label>
                        <input type="text" name="cliente_cedula" id="presupuesto-cliente-cedula"
                            placeholder="Sin puntos ni guiones"
                            class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Teléfono</label>
                        <input type="text" name="cliente_telefono" id="presupuesto-cliente-telefono"
                            placeholder="0412..."
                            class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Email</label>
                        <input type="email" name="cliente_email" id="presupuesto-cliente-email"
                            placeholder="correo@ejemplo.com"
                            class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Dirección</label>
                        <input type="text" name="cliente_direccion" id="presupuesto-cliente-direccion"
                            placeholder="Dirección de entrega"
                            class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all uppercase">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Placa</label>
                        <input type="text" name="vehiculo_placa" id="presupuesto-vehiculo-placa" placeholder="ABC123"
                            class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all uppercase">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Marca</label>
                        <input type="text" name="vehiculo_marca" id="presupuesto-vehiculo-marca" placeholder="Toyota"
                            class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all uppercase">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Modelo</label>
                        <input type="text" name="vehiculo_modelo" id="presupuesto-vehiculo-modelo" placeholder="Corolla"
                            class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all uppercase">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Año</label>
                        <input type="number" name="vehiculo_anio" id="presupuesto-vehiculo-anio" placeholder="2020"
                            min="1990" max="<?php echo date('Y') + 1; ?>"
                            class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Color</label>
                        <input type="text" name="vehiculo_color" id="presupuesto-vehiculo-color" placeholder="Blanco"
                            class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all uppercase">
                    </div>
                </div>
            </div>

            <!-- Sección Items -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="package" class="w-4 h-4 text-neon-green"></i> Items del Presupuesto
                    </h3>
                    <button type="button" onclick="agregarItemPresupuesto()"
                        class="bg-neon-green text-navy-blue px-3 py-2 rounded-lg font-black text-xs uppercase flex items-center gap-1 hover:opacity-90 transition">
                        <i data-lucide="plus" class="w-3 h-3"></i> Agregar Item
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-slate-50 text-slate-500 text-[10px] font-black uppercase tracking-widest border-b border-slate-100">
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Descripción</th>
                                <th class="px-4 py-3 text-center">Cant.</th>
                                <th class="px-4 py-3 text-right">P. Unitario</th>
                                <th class="px-4 py-3 text-center">Desc. %</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                                <th class="px-4 py-3 text-center">IVA %</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="presupuesto-items-body" class="divide-y divide-slate-100">
                            <tr id="empty-items-row">
                                <td colspan="10" class="px-4 py-16 text-center text-slate-400 italic">No hay items
                                    agregados</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totales y Configuración -->
            <div class="grid lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 bg-slate-50/50 rounded-2xl p-5 border border-slate-100">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-4">Observaciones y
                        Condiciones</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label
                                class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Observaciones</label>
                            <textarea name="observaciones" id="presupuesto-observaciones" rows="3"
                                placeholder="Observaciones internas o notas para el cliente..."
                                class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all resize-none uppercase"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Términos y
                                Condiciones</label>
                            <textarea name="condiciones" id="presupuesto-condiciones" rows="3"
                                placeholder="Condiciones de pago, validez, garantías, etc."
                                class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all resize-none uppercase"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-4">Configuración</h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Fecha
                                    Emisión</label>
                                <input type="date" name="fecha_emision" id="presupuesto-fecha-emision"
                                    class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Días de
                                    Validez</label>
                                <input type="number" name="validez_dias" id="presupuesto-validez-dias" value="30"
                                    min="1" max="365"
                                    class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="iva_activo" id="presupuesto-iva-activo" value="1"
                                    class="w-4 h-4 text-neon-green border-slate-300 rounded focus:ring-neon-green">
                                <span class="text-sm font-medium text-slate-700">Aplicar IVA</span>
                            </label>
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Tasa IVA
                                    (%)</label>
                                <input type="number" name="tasa_iva" id="presupuesto-tasa-iva"
                                    value="<?php echo $config_iva; ?>" step="0.01" min="0" max="100"
                                    class="w-full bg-white border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totales Resumen -->
            <div class="bg-gradient-to-r from-navy-blue to-slate-800 text-white rounded-2xl p-5 shadow-lg">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wider opacity-70">Subtotal</p>
                        <p id="presupuesto-subtotal-display" class="text-2xl font-extrabold">$0.00</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wider opacity-70">IVA</p>
                        <p id="presupuesto-iva-display" class="text-2xl font-extrabold">$0.00</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wider opacity-70">Total</p>
                        <p id="presupuesto-total-display" class="text-3xl font-extrabold">$0.00</p>
                    </div>
                </div>
            </div>

            <!-- Campos ocultos para totales del presupuesto -->
            <input type="hidden" name="subtotal" id="presupuesto-subtotal" value="0">
            <input type="hidden" name="iva_monto" id="presupuesto-iva-monto" value="0">
            <input type="hidden" name="total" id="presupuesto-total" value="0">

            <!-- Botones -->
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="cerrarModalPresupuesto()"
                    class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-xl font-black text-sm uppercase hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="button" onclick="guardarPresupuesto('BORRADOR')"
                    class="bg-slate-600 text-white px-6 py-3 rounded-xl font-black text-sm uppercase hover:bg-slate-700 transition flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Guardar Borrador
                </button>
                <button type="button" onclick="guardarPresupuesto('ENVIADO')"
                    class="bg-neon-green text-navy-blue px-6 py-3 rounded-xl font-black text-sm uppercase hover:brightness-110 transition shadow-lg shadow-neon-green/20 flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i> Guardar y Enviar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let presupuestosPage = 1;
let presupuestosLimit = 20;
let presupuestosTotalPages = 1;
let itemCounter = 0;

/* ==================== CARGA DE PRESUPUESTOS ==================== */
async function cargarPresupuestos(page = 1) {
    presupuestosPage = page;
    const tbody = document.getElementById('presupuestos-body');

    const filters = {
        limit: presupuestosLimit,
        page: presupuestosPage,
        estado: document.getElementById('filter-estado').value || null,
        desde: document.getElementById('filter-desde').value || null,
        hasta: document.getElementById('filter-hasta').value || null,
        search: document.getElementById('filter-search').value.trim() || null
    };

    try {
        const res = await fetch(`${URLROOT}/presupuesto/listar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify(filters)
        });
        const result = await res.json();

        if (!result.success || result.data.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="7" class="px-6 py-16 text-center text-slate-400 uppercase text-xs font-bold tracking-widest">No hay presupuestos registrados</td></tr>';
            document.getElementById('presupuestos-pagination').classList.add('hidden');
            return;
        }

        tbody.innerHTML = result.data.map(p => {
            const estadoColors = {
                'BORRADOR': 'bg-slate-100 text-slate-600',
                'ENVIADO': 'bg-amber-100 text-amber-700',
                'ACTIVO': 'bg-emerald-100 text-emerald-700',
                'EN_PROCESO': 'bg-blue-100 text-blue-700',
                'ACEPTADO': 'bg-emerald-100 text-emerald-700',
                'RECHAZADO': 'bg-red-100 text-red-700',
                'EXPIRADO': 'bg-red-100 text-red-700',
                'CONVERTIDO': 'bg-purple-100 text-purple-700'
            };
            const estadoColor = estadoColors[p.estado] || 'bg-slate-100 text-slate-600';

            const vencido = p.fecha_vencimiento && new Date(p.fecha_vencimiento) < new Date() && p
                .estado === 'ENVIADO';
            const vencidoBadge = vencido ?
                '<span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-black bg-red-100 text-red-700">VENCIDO</span>' :
                '';

            return `
                <tr class="hover:bg-slate-50 transition-colors" data-id="${p.id}">
                    <td class="px-6 py-4 font-black text-navy-blue">${p.numero}</td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-xs font-black text-slate-700 uppercase">${p.cliente_nombre}</span>
                            <span class="text-[10px] text-slate-400">${p.cliente_cedula || ''}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs font-bold text-navy-blue">${new Date(p.fecha_emision).toLocaleDateString()}</td>
                    <td class="px-6 py-4 text-xs text-slate-500">${p.fecha_vencimiento ? new Date(p.fecha_vencimiento).toLocaleDateString() : '-'} ${vencidoBadge}</td>
                    <td class="px-6 py-4 text-right font-bold text-slate-700">$${parseFloat(p.total).toLocaleString('es-CO', {minimumFractionDigits: 2})}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black ${estadoColor}">${p.estado}</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex gap-1 justify-end">
                            ${p.estado === 'BORRADOR' ? `
                                <button onclick="editarPresupuesto(${p.id})" class="p-2 bg-slate-100 hover:bg-neon-green hover:text-black text-slate-500 rounded-lg transition-all" title="Editar">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
                                <button onclick="enviarPresupuestoEmail(${p.id})" class="p-2 bg-blue-100 hover:bg-blue-500 hover:text-black text-blue-600 rounded-lg transition-all" title="Enviar por Email">
                                    <i data-lucide="mail" class="w-4 h-4"></i>
                                </button>
                                <button onclick="generarPDFPresupuesto(${p.id})" class="p-2 bg-purple-100 hover:bg-purple-500 hover:text-black text-purple-600 rounded-lg transition-all" title="PDF">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                </button>
                                <button onclick="eliminarPresupuesto(${p.id})" class="p-2 bg-slate-100 hover:bg-red-500 hover:text-black text-slate-500 rounded-lg transition-all" title="Eliminar">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            ` : `
                                ${p.estado === 'ENVIADO' ? `
                                    <button onclick="cambiarEstadoPresupuesto(${p.id}, 'ACEPTADO')" class="p-2 bg-emerald-100 hover:bg-emerald-500 hover:text-black text-emerald-600 rounded-lg transition-all" title="Marcar Aceptado">
                                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="cambiarEstadoPresupuesto(${p.id}, 'RECHAZADO')" class="p-2 bg-red-100 hover:bg-red-500 hover:text-black text-red-600 rounded-lg transition-all" title="Marcar Rechazado">
                                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                                    </button>
                                ` : ''}
                                ${p.estado === 'ACTIVO' ? `
                                    <button onclick="activarPresupuestoDesdeTabla(${p.id})" class="p-2 bg-emerald-100 hover:bg-emerald-500 hover:text-black text-emerald-600 rounded-lg transition-all" title="Ver Reservas">
                                        <i data-lucide="package" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="iniciarProcesoDesdeTabla(${p.id})" class="p-2 bg-blue-100 hover:bg-blue-500 hover:text-black text-blue-600 rounded-lg transition-all" title="Iniciar Proceso">
                                        <i data-lucide="play-circle" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="liberarInventarioDesdeTabla(${p.id})" class="p-2 bg-amber-100 hover:bg-amber-500 hover:text-black text-amber-600 rounded-lg transition-all" title="Liberar Inventario">
                                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                    </button>
                                ` : ''}
                                ${p.estado === 'EN_PROCESO' ? `
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 text-[10px] font-black rounded-lg">En Proceso</span>
                                ` : ''}
                                ${p.estado === 'ACEPTADO' ? `
                                    <button onclick="convertirPresupuestoAVenta(${p.id})" class="p-2 bg-purple-100 hover:bg-purple-500 hover:text-black text-purple-600 rounded-lg transition-all" title="Convertir a Venta">
                                        <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="iniciarProcesoDesdeTabla(${p.id})" class="p-2 bg-blue-100 hover:bg-blue-500 hover:text-black text-blue-600 rounded-lg transition-all" title="Iniciar Proceso (OS/Facturación/Venta)">
                                        <i data-lucide="play-circle" class="w-4 h-4"></i>
                                    </button>
                                ` : ''}
                                <a href="${URLROOT}/presupuesto/ver/${p.id}" class="p-2 bg-slate-100 hover:bg-neon-green hover:text-black text-slate-500 rounded-lg transition-all" title="Ver">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <button onclick="generarPDFPresupuesto(${p.id})" class="p-2 bg-purple-100 hover:bg-purple-500 hover:text-black text-purple-600 rounded-lg transition-all" title="PDF">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                </button>
                            `}
                        </div>
                    </td>
                </tr>`;
        }).join('');

        presupuestosTotalPages = result.total_paginas || 1;
        const total = result.total || 0;
        const start = (presupuestosPage - 1) * presupuestosLimit + 1;
        const end = Math.min(presupuestosPage * presupuestosLimit, total);

        document.getElementById('presupuestos-info').textContent =
            `Mostrando ${start} a ${end} de ${total} presupuestos - Página ${presupuestosPage} de ${presupuestosTotalPages}`;
        renderPresupuestosPagination(presupuestosPage, presupuestosTotalPages);
        document.getElementById('presupuestos-pagination').classList.remove('hidden');

        if (window.lucide) lucide.createIcons();
    } catch (e) {
        tbody.innerHTML =
            '<tr><td colspan="7" class="px-6 py-16 text-center text-red-400 uppercase text-xs font-bold tracking-widest">Error al cargar los datos</td></tr>';
        document.getElementById('presupuestos-pagination').classList.add('hidden');
    }
}

function renderPresupuestosPagination(page, totalPages) {
    const ctrl = document.getElementById('presupuestos-pagination-controls');
    if (totalPages <= 1) {
        ctrl.innerHTML = '';
        return;
    }
    let html = '';
    html +=
        `<button onclick="cargarPresupuestos(${Math.max(1, page - 1)})" class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 hover:bg-slate-100 ${page === 1 ? 'opacity-40 cursor-not-allowed' : ''}" ${page === 1 ? 'disabled' : ''}><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
            html +=
                `<button onclick="cargarPresupuestos(${i})" class="px-3 py-1.5 rounded-lg text-xs font-bold border ${i === page ? 'bg-navy-blue text-white border-navy-blue' : 'border-slate-200 hover:bg-slate-100'}">${i}</button>`;
        } else if (i === page - 2 || i === page + 2) {
            html += `<span class="px-2 text-slate-400">...</span>`;
        }
    }
    html +=
        `<button onclick="cargarPresupuestos(${Math.min(totalPages, page + 1)})" class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 hover:bg-slate-100 ${page === totalPages ? 'opacity-40 cursor-not-allowed' : ''}" ${page === totalPages ? 'disabled' : ''}><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
    ctrl.innerHTML = html;
    if (window.lucide) lucide.createIcons();
}

function limpiarFiltrosPresupuesto() {
    document.getElementById('filter-estado').value = '';
    document.getElementById('filter-desde').value = '';
    document.getElementById('filter-hasta').value = '';
    document.getElementById('filter-search').value = '';
    cargarPresupuestos(1);
}

/* ==================== MODAL PRESUPUESTO ==================== */
function abrirModalCrear() {
    document.getElementById('presupuestoModal').classList.remove('hidden');
    document.getElementById('formPresupuesto').reset();
    document.getElementById('presupuestoModalTitle').textContent = 'Nuevo Presupuesto';
    document.getElementById('presupuesto-id').value = '';
    document.getElementById('presupuesto-estado').value = 'BORRADOR';
    document.getElementById('presupuesto-fecha-emision').value = new Date().toISOString().split('T')[0];
    document.getElementById('presupuesto-validez-dias').value = 30;
    document.getElementById('presupuesto-iva-activo').checked = false;
    document.getElementById('presupuesto-tasa-iva').value = '<?php echo $config_iva; ?>';
    document.getElementById('presupuesto-items-body').innerHTML =
        '<tr id="empty-items-row"><td colspan="10" class="px-4 py-16 text-center text-slate-400 italic">No hay items agregados</td></tr>';
    itemCounter = 0;
    actualizarTotalesPresupuesto();
    lucide.createIcons();

    // Mostrar campos de vehículo (presupuesto normal)
    ocultarCamposVehiculo(false);

    // Cargar sugerencias de clientes
    cargarSugerenciasClientes();
}

function editarPresupuesto(id) {
    window.location.href = `${URLROOT}/presupuesto/editar/${id}`;
}

function cerrarModalPresupuesto() {
    document.getElementById('presupuestoModal').classList.add('hidden');
}

/* ==================== SUGERENCIAS CLIENTES ==================== */
async function cargarSugerenciasClientes() {
    try {
        const res = await fetch(`${URLROOT}/presupuesto/buscarClientes`);
        const data = await res.json();
        if (data.success) {
            window.clientesPresupuesto = data.data;
        }
    } catch (e) {
        console.error(e);
    }
}

function mostrarSugerenciasCliente(input) {
    const suggestions = document.getElementById('cliente-suggestions');
    const value = input.value.toLowerCase();

    if (!value || !window.clientesPresupuesto) {
        suggestions.classList.add('hidden');
        return;
    }

    const matches = window.clientesPresupuesto.filter(c =>
        (c.nombre && c.nombre.toLowerCase().includes(value)) ||
        (c.id && c.id.toLowerCase().includes(value)) ||
        (c.telefono && c.telefono.includes(value)) ||
        (c.email && c.email.toLowerCase().includes(value))
    ).slice(0, 10);

    if (matches.length === 0) {
        suggestions.classList.add('hidden');
        return;
    }

    suggestions.innerHTML = matches.map(c => `
        <div class="px-4 py-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0" 
             onclick="seleccionarClientePresupuesto('${c.id}', '${c.nombre.replace(/'/g, "\\'")}', '${c.telefono || ''}', '${c.email || ''}', '${c.direccion || ''}', '${c.placa || ''}', '${c.marca || ''}', '${c.modelo || ''}', '${c.anio || ''}', '${c.color || ''}')">
            <div class="font-medium text-slate-700">${c.nombre}</div>
            <div class="text-xs text-slate-500">${c.id} · ${c.telefono || ''} · ${c.email || ''}</div>
            ${c.placa ? `<div class="text-xs text-blue-600">${c.placa} · ${c.marca} ${c.modelo} (${c.anio}) · ${c.color}</div>` : ''}
        </div>
    `).join('');
    suggestions.classList.remove('hidden');
}

function seleccionarClientePresupuesto(id, nombre, telefono, email, direccion, placa, marca, modelo, anio, color) {
    document.getElementById('presupuesto-cliente-id').value = id;
    document.getElementById('presupuesto-cliente-cedula').value = id;
    document.getElementById('presupuesto-cliente-nombre').value = nombre;
    document.getElementById('presupuesto-cliente-telefono').value = telefono || '';
    document.getElementById('presupuesto-cliente-email').value = email || '';
    document.getElementById('presupuesto-cliente-direccion').value = direccion || '';
    document.getElementById('presupuesto-vehiculo-placa').value = placa || '';
    document.getElementById('presupuesto-vehiculo-marca').value = marca || '';
    document.getElementById('presupuesto-vehiculo-modelo').value = modelo || '';
    document.getElementById('presupuesto-vehiculo-anio').value = anio || '';
    document.getElementById('presupuesto-vehiculo-color').value = color || '';
    document.getElementById('cliente-suggestions').classList.add('hidden');
}

document.getElementById('presupuesto-cliente-nombre')?.addEventListener('input', function() {
    mostrarSugerenciasCliente(this);
});

document.getElementById('presupuesto-cliente-nombre')?.addEventListener('focus', function() {
    mostrarSugerenciasCliente(this);
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('#presupuesto-cliente-nombre') && !e.target.closest('#cliente-suggestions')) {
        document.getElementById('cliente-suggestions').classList.add('hidden');
    }
});

/* ==================== GESTIÓN DE ITEMS ==================== */
async function agregarItemPresupuesto(item = null) {
    itemCounter++;
    const tbody = document.getElementById('presupuesto-items-body');

    // Ocultar fila vacía
    const emptyRow = document.getElementById('empty-items-row');
    if (emptyRow) emptyRow.remove();

    // Cargar productos para el selector
    let productosOptions = '<option value="">-- Seleccionar producto/servicio --</option>';
    try {
        const res = await fetch(`${URLROOT}/presupuesto/buscarProductos`);
        const data = await res.json();
        if (data.success) {
            data.data.forEach(p => {
                const precioRegular = p.precio;
                const precioOferta = p.en_oferta_vigente ? p.precio_final : p.precio;
                const ofertaBadge = p.en_oferta_vigente ? ` (OFERTA ${p.oferta_porcentaje}%)` : '';
                // Guardamos ambos precios: precio regular y precio oferta
                productosOptions +=
                    `<option value="${p.id}" data-precio="${precioRegular}" data-precio-oferta="${precioOferta}" data-tipo="PRODUCTO" data-stock="${p.stock}" data-costo="${p.costo_promedio}">${p.nombre} ${ofertaBadge} - $${parseFloat(precioRegular).toLocaleString('es-CO', {minimumFractionDigits: 2})} (Stock: ${p.stock})</option>`;
            });
        }
    } catch (e) {
        console.error(e);
    }

    const row = document.createElement('tr');
    row.className = 'hover:bg-slate-50 transition-colors';
    row.dataset.itemId = itemCounter;
    row.innerHTML = `
        <td class="px-4 py-3 text-center text-xs font-bold text-slate-500">${itemCounter}</td>
        <td class="px-4 py-3">
            <select name="items[${itemCounter}][tipo_item]" class="w-full bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:border-neon-green" onchange="actualizarTipoItem(this, ${itemCounter})">
                <option value="PRODUCTO">Producto</option>
                <option value="SERVICIO">Servicio</option>
            </select>
        </td>
        <td class="px-4 py-3">
            <div class="relative">
                <select name="items[${itemCounter}][producto_id]" id="item-producto-${itemCounter}" class="w-full bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:border-neon-green" onchange="seleccionarProductoPresupuesto(this, ${itemCounter})">
                    ${productosOptions}
                </select>
                <input type="text" name="items[${itemCounter}][descripcion]" id="item-descripcion-${itemCounter}" placeholder="Descripción manual" class="w-full bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:border-neon-green hidden mt-1 uppercase" oninput="actualizarSubtotalItem(${itemCounter})">
            </div>
        </td>
        <td class="px-4 py-3 text-center">
            <input type="number" name="items[${itemCounter}][cantidad]" value="${item?.cantidad || 1}" min="1" class="w-20 bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm text-center outline-none focus:border-neon-green" oninput="actualizarSubtotalItem(${itemCounter})">
        </td>
        <td class="px-4 py-3 text-right">
            <input type="number" name="items[${itemCounter}][precio_unitario]" id="item-precio-${itemCounter}" value="${item?.precio_unitario || 0}" step="0.01" min="0" class="w-28 bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm text-right outline-none focus:border-neon-green" oninput="actualizarSubtotalItem(${itemCounter})">
        </td>
        <td class="px-4 py-3 text-center">
            <input type="number" name="items[${itemCounter}][descuento_porcentaje]" value="${item?.descuento_porcentaje || 0}" step="0.01" min="0" max="100" class="w-20 bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm text-center outline-none focus:border-neon-green" oninput="actualizarSubtotalItem(${itemCounter})">
        </td>
        <td class="px-4 py-3 text-right font-bold text-slate-700" id="item-subtotal-${itemCounter}">$0.00</td>
        <td class="px-4 py-3 text-center">
            <input type="number" name="items[${itemCounter}][iva_porcentaje]" id="item-iva-${itemCounter}" value="0" step="0.01" min="0" max="100" class="w-20 bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm text-center outline-none focus:border-neon-green" oninput="actualizarSubtotalItem(${itemCounter})">
        </td>
        <td class="px-4 py-3 text-right font-bold text-slate-700" id="item-total-${itemCounter}">$0.00</td>
        <td class="px-4 py-3 text-center">
            <button type="button" onclick="eliminarItemPresupuesto(${itemCounter})" class="p-2 bg-red-100 hover:bg-red-500 hover:text-black text-red-600 rounded-lg transition-all" title="Eliminar">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
        </td>
    `;

    tbody.insertAdjacentHTML('beforeend', row.outerHTML);

    if (item) {
        // Llenar datos del item existente
        document.querySelector(`select[name="items[${itemCounter}][tipo_item]"]`).value = item.tipo_item;
        document.getElementById(`item-producto-${itemCounter}`).value = item.producto_id || '';
        document.getElementById(`item-descripcion-${itemCounter}`).value = item.descripcion;
        document.querySelector(`input[name="items[${itemCounter}][cantidad]"]`).value = item.cantidad;
        document.getElementById(`item-precio-${itemCounter}`).value = item.precio_unitario;
        document.querySelector(`input[name="items[${itemCounter}][descuento_porcentaje]"]`).value = item
            .descuento_porcentaje || 0;
        document.getElementById(`item-iva-${itemCounter}`).value = item.iva_porcentaje || 0;
        actualizarTipoItem(document.querySelector(`select[name="items[${itemCounter}][tipo_item]"]`), itemCounter);
        actualizarSubtotalItem(itemCounter);
    } else {
        // Para items nuevos, inicializar cálculos
        actualizarSubtotalItem(itemCounter);
    }

    if (window.lucide) lucide.createIcons();
}

function actualizarTipoItem(select, itemId) {
    const tipo = select.value;
    const productoSelect = document.getElementById(`item-producto-${itemId}`);
    const descripcionInput = document.getElementById(`item-descripcion-${itemId}`);
    const precioInput = document.getElementById(`item-precio-${itemId}`);

    if (tipo === 'SERVICIO') {
        productoSelect.classList.add('hidden');
        descripcionInput.classList.remove('hidden');
        descripcionInput.required = true;
        // Para servicios, el precio es editable
        precioInput.readOnly = false;
        precioInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
        // Limpiar producto_id para servicios (no debe enviarse a la BD)
        productoSelect.value = '';
    } else {
        productoSelect.classList.remove('hidden');
        descripcionInput.classList.add('hidden');
        descripcionInput.required = false;
        // Para productos, el precio NO es editable (viene de la BD)
        precioInput.readOnly = true;
        precioInput.classList.add('bg-slate-100', 'cursor-not-allowed');
    }
    actualizarSubtotalItem(itemId);
}

async function seleccionarProductoPresupuesto(select, itemId) {
    const option = select.options[select.selectedIndex];
    if (!option.value) return;

    const tipo = option.dataset.tipo;
    const stock = option.dataset.stock;

    // Para productos, usar el precio regular (no el de oferta)
    // Para servicios, el precio es editable
    let precio;
    if (tipo === 'PRODUCTO') {
        precio = option.dataset.precio; // precio regular del inventario
    } else {
        precio = option.dataset.precioOferta || option.dataset.precio; // para servicios, usar el precio disponible
    }

    document.getElementById(`item-precio-${itemId}`).value = precio;
    document.getElementById(`item-descripcion-${itemId}`).value = option.text.split(' - ')[0];

    // Actualizar tipo si es producto
    const tipoSelect = document.querySelector(`select[name="items[${itemId}][tipo_item]"]`);
    if (tipoSelect) tipoSelect.value = tipo;
    actualizarTipoItem(tipoSelect, itemId);

    actualizarSubtotalItem(itemId);
}

function actualizarSubtotalItem(itemId) {
    const cantidad = parseFloat(document.querySelector(`input[name="items[${itemId}][cantidad]"]`).value) || 0;
    const precio = parseFloat(document.getElementById(`item-precio-${itemId}`).value) || 0;
    const descuentoPct = parseFloat(document.querySelector(`input[name="items[${itemId}][descuento_porcentaje]"]`)
        .value) || 0;
    const ivaPct = parseFloat(document.getElementById(`item-iva-${itemId}`).value) || 0;

    const subtotalBruto = cantidad * precio;
    const descuentoMonto = subtotalBruto * (descuentoPct / 100);
    const subtotal = subtotalBruto - descuentoMonto;
    const ivaMonto = subtotal * (ivaPct / 100);
    const total = subtotal + ivaMonto;

    document.getElementById(`item-subtotal-${itemId}`).textContent = '$' + subtotal.toLocaleString('es-CO', {
        minimumFractionDigits: 2
    });
    document.getElementById(`item-total-${itemId}`).textContent = '$' + total.toLocaleString('es-CO', {
        minimumFractionDigits: 2
    });

    // Actualizar campos hidden
    document.querySelector(`input[name="items[${itemId}][subtotal]"]`)?.remove();
    document.querySelector(`input[name="items[${itemId}][iva_monto]"]`)?.remove();
    document.querySelector(`input[name="items[${itemId}][total]"]`)?.remove();
    document.querySelector(`input[name="items[${itemId}][descuento_monto]"]`)?.remove();

    const form = document.getElementById('formPresupuesto');
    const hiddenSubtotal = document.createElement('input');
    hiddenSubtotal.type = 'hidden';
    hiddenSubtotal.name = `items[${itemId}][subtotal]`;
    hiddenSubtotal.value = subtotal.toFixed(2);
    form.appendChild(hiddenSubtotal);

    const hiddenIva = document.createElement('input');
    hiddenIva.type = 'hidden';
    hiddenIva.name = `items[${itemId}][iva_monto]`;
    hiddenIva.value = ivaMonto.toFixed(2);
    form.appendChild(hiddenIva);

    const hiddenTotal = document.createElement('input');
    hiddenTotal.type = 'hidden';
    hiddenTotal.name = `items[${itemId}][total]`;
    hiddenTotal.value = total.toFixed(2);
    form.appendChild(hiddenTotal);

    const hiddenDesc = document.createElement('input');
    hiddenDesc.type = 'hidden';
    hiddenDesc.name = `items[${itemId}][descuento_monto]`;
    hiddenDesc.value = descuentoMonto.toFixed(2);
    form.appendChild(hiddenDesc);

    actualizarTotalesPresupuesto();
}

function actualizarTotalesPresupuesto() {
    let subtotal = 0;
    let iva = 0;
    let total = 0;

    document.querySelectorAll('#presupuesto-items-body tr[data-item-id]').forEach(row => {
        const itemId = row.dataset.itemId;
        // Parsear formato es-CO: "1.234,56" -> 1234.56
        // Quitar $, quitar puntos (miles), cambiar coma (decimal) por punto
        const parseEsCO = (text) => {
            return parseFloat(text.replace('$', '').replace(/\./g, '').replace(',', '.')) || 0;
        };
        
        const subtotalItem = parseEsCO(document.getElementById(`item-subtotal-${itemId}`).textContent);
        const totalItem = parseEsCO(document.getElementById(`item-total-${itemId}`).textContent);
        const ivaItem = totalItem - subtotalItem;

        subtotal += subtotalItem;
        total += totalItem;
        iva += ivaItem;
    });

    document.getElementById('presupuesto-subtotal-display').textContent = '$' + subtotal.toLocaleString('es-CO', {
        minimumFractionDigits: 2
    });
    document.getElementById('presupuesto-iva-display').textContent = '$' + iva.toLocaleString('es-CO', {
        minimumFractionDigits: 2
    });
    document.getElementById('presupuesto-total-display').textContent = '$' + total.toLocaleString('es-CO', {
        minimumFractionDigits: 2
    });

    // Actualizar campos ocultos para el envío al servidor
    document.getElementById('presupuesto-subtotal').value = subtotal.toFixed(2);
    document.getElementById('presupuesto-iva-monto').value = iva.toFixed(2);
    document.getElementById('presupuesto-total').value = total.toFixed(2);
}

function eliminarItemPresupuesto(itemId) {
    const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
    if (row) row.remove();

    // Re-numerar items
    document.querySelectorAll('#presupuesto-items-body tr[data-item-id]').forEach((row, index) => {
        const newId = index + 1;
        row.dataset.itemId = newId;
        row.cells[0].textContent = newId;

        // Actualizar names e ids de todos los inputs
        row.querySelectorAll('input, select').forEach(input => {
            const name = input.name;
            if (name) {
                input.name = name.replace(/items\[\d+\]/, `items[${newId}]`);
            }
            const id = input.id;
            if (id) {
                input.id = id.replace(/\d+$/, newId);
            }
        });
        row.dataset.itemId = newId;
        row.cells[0].textContent = newId;
    });

    itemCounter = document.querySelectorAll('#presupuesto-items-body tr[data-item-id]').length;

    // Mostrar fila vacía si no hay items
    if (itemCounter === 0) {
        const tbody = document.getElementById('presupuesto-items-body');
        tbody.innerHTML =
            '<tr id="empty-items-row"><td colspan="10" class="px-4 py-16 text-center text-slate-400 italic">No hay items agregados</td></tr>';
    }

    actualizarTotalesPresupuesto();
}

/* ==================== GUARDAR PRESUPUESTO ==================== */
async function guardarPresupuesto(estado) {
    // Asegurar que los totales estén actualizados antes de enviar
    actualizarTotalesPresupuesto();
    
    const form = document.getElementById('formPresupuesto');
    const formData = new FormData(form);
    formData.set('estado', estado);

    // Validar items
    const items = document.querySelectorAll('#presupuesto-items-body tr[data-item-id]');
    if (items.length === 0) {
        AppUtils.showToast('Debe agregar al menos un item', 'error');
        return;
    }

    const btn = form.querySelector(`button[onclick="guardarPresupuesto('${estado}')"]`);
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Guardando...';
    if (window.lucide) lucide.createIcons();

    try {
        const res = await fetch(
            `${URLROOT}/presupuesto/${document.getElementById('presupuesto-id').value ? 'actualizar' : 'guardar'}/${document.getElementById('presupuesto-id').value || ''}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: formData
            });
        const result = await res.json();

        if (result.success) {
            AppUtils.showToast(result.mensaje, 'success');
            cerrarModalPresupuesto();
            if (result.redirect) {
                window.location.href = result.redirect;
            } else {
                cargarPresupuestos(1);
            }
        } else {
            AppUtils.showToast(result.mensaje || 'Error al guardar', 'error');
        }
    } catch (e) {
        AppUtils.showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (window.lucide) lucide.createIcons();
    }
}

function cerrarModalPresupuesto() {
    document.getElementById('presupuestoModal').classList.add('hidden');
}

/* ==================== ACCIONES PRESUPUESTO ==================== */
async function enviarPresupuestoEmail(id) {
    const {
        value: formValues
    } = await Swal.fire({
        title: 'Enviar Presupuesto por Email',
        html: `
            <div class="text-left space-y-4 pt-2">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Email Destinatario *</label>
                    <input type="email" id="email-destinatario" class="swal2-input w-full m-0 text-sm" placeholder="cliente@ejemplo.com" required>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Nombre Destinatario</label>
                    <input type="text" id="email-nombre" class="swal2-input w-full m-0 text-sm" placeholder="Nombre del cliente">
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'ENVIAR',
        confirmButtonColor: '#3b82f6',
        preConfirm: () => {
            const email = document.getElementById('email-destinatario').value;
            const nombre = document.getElementById('email-nombre').value;
            if (!email) {
                Swal.showValidationMessage('El email es obligatorio');
                return false;
            }
            return {
                email,
                nombre
            };
        }
    });

    if (formValues) {
        AppUtils.showLoading('Enviando...');
        try {
            const res = await fetch(`${URLROOT}/presupuesto/enviarEmail/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    destinatario_email: formValues.email,
                    destinatario_nombre: formValues.nombre
                })
            });
            const result = await res.json();
            AppUtils.hideLoading();

            if (result.success) {
                AppUtils.showToast(result.mensaje, 'success');
                cargarPresupuestos(presupuestosPage);
            } else {
                AppUtils.showToast(result.mensaje || 'Error al enviar', 'error');
            }
        } catch (e) {
            AppUtils.hideLoading();
            AppUtils.showToast('Error de conexión', 'error');
        }
    }
}

async function generarPDFPresupuesto(id) {
    try {
        AppUtils.showLoading('Generando PDF...');
        const res = await fetch(`${URLROOT}/presupuesto/pdf/${id}`);
        const result = await res.json();
        AppUtils.hideLoading();

        if (result.success) {
            window.open(result.pdf_url, '_blank');
        } else {
            AppUtils.showToast(result.mensaje || 'Error al generar PDF', 'error');
        }
    } catch (e) {
        AppUtils.hideLoading();
        AppUtils.showToast('Error de conexión', 'error');
    }
}

/**
 * Actualiza una fila de la tabla dinámicamente sin recargar la página
 * @param {number} id - ID del presupuesto
 * @param {Object} data - Datos actualizados del presupuesto
 */
function actualizarFilaPresupuesto(id, data) {
    const row = document.querySelector(`#presupuestos-body tr[data-id="${id}"]`);
    if (!row) return;

    // Actualizar estado badge
    const estadoColors = {
        'BORRADOR': 'bg-slate-100 text-slate-600',
        'ENVIADO': 'bg-amber-100 text-amber-700',
        'ACTIVO': 'bg-emerald-100 text-emerald-700',
        'EN_PROCESO': 'bg-blue-100 text-blue-700',
        'ACEPTADO': 'bg-emerald-100 text-emerald-700',
        'RECHAZADO': 'bg-red-100 text-red-700',
        'EXPIRADO': 'bg-red-100 text-red-700',
        'CONVERTIDO': 'bg-purple-100 text-purple-700'
    };
    const estadoColor = estadoColors[data.estado] || 'bg-slate-100 text-slate-600';
    
    const estadoCell = row.querySelector('td:nth-child(6) span');
    if (estadoCell) {
        estadoCell.className = `px-2 py-0.5 rounded-full text-[10px] font-black ${estadoColor}`;
        estadoCell.textContent = data.estado;
    }

    // Actualizar botones de acciones según el nuevo estado
    const actionsCell = row.querySelector('td:last-child .flex');
    if (actionsCell && data.estado) {
        const estado = data.estado;
        let actionsHtml = '';
        
        if (estado === 'BORRADOR') {
            actionsHtml = `
                <button onclick="editarPresupuesto(${id})" class="p-2 bg-slate-100 hover:bg-neon-green hover:text-black text-slate-500 rounded-lg transition-all" title="Editar">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                </button>
                <button onclick="enviarPresupuestoEmail(${id})" class="p-2 bg-blue-100 hover:bg-blue-500 hover:text-black text-blue-600 rounded-lg transition-all" title="Enviar por Email">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </button>
                <button onclick="generarPDFPresupuesto(${id})" class="p-2 bg-purple-100 hover:bg-purple-500 hover:text-black text-purple-600 rounded-lg transition-all" title="PDF">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </button>
                <button onclick="eliminarPresupuesto(${id})" class="p-2 bg-slate-100 hover:bg-red-500 hover:text-black text-slate-500 rounded-lg transition-all" title="Eliminar">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            `;
        } else if (estado === 'ENVIADO') {
            actionsHtml = `
                <button onclick="editarPresupuesto(${id})" class="p-2 bg-slate-100 hover:bg-neon-green hover:text-black text-slate-500 rounded-lg transition-all" title="Editar Presupuesto">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                </button>
                <button onclick="cambiarEstadoPresupuesto(${id}, 'ACEPTADO')" class="p-2 bg-emerald-100 hover:bg-emerald-500 hover:text-black text-emerald-600 rounded-lg transition-all" title="Marcar Aceptado">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                </button>
                <button onclick="cambiarEstadoPresupuesto(${id}, 'RECHAZADO')" class="p-2 bg-red-100 hover:bg-red-500 hover:text-black text-red-600 rounded-lg transition-all" title="Marcar Rechazado">
                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                </button>
                <a href="${URLROOT}/presupuesto/ver/${id}" class="p-2 bg-slate-100 hover:bg-neon-green hover:text-black text-slate-500 rounded-lg transition-all" title="Ver">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                </a>
                <button onclick="generarPDFPresupuesto(${id})" class="p-2 bg-purple-100 hover:bg-purple-500 hover:text-black text-purple-600 rounded-lg transition-all" title="PDF">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </button>
            `;
        } else if (estado === 'ACTIVO') {
            actionsHtml = `
                <button onclick="activarPresupuestoDesdeTabla(${id})" class="p-2 bg-emerald-100 hover:bg-emerald-500 hover:text-black text-emerald-600 rounded-lg transition-all" title="Ver Reservas">
                    <i data-lucide="package" class="w-4 h-4"></i>
                </button>
                <button onclick="iniciarProcesoDesdeTabla(${id})" class="p-2 bg-blue-100 hover:bg-blue-500 hover:text-black text-blue-600 rounded-lg transition-all" title="Iniciar Proceso">
                    <i data-lucide="play-circle" class="w-4 h-4"></i>
                </button>
                <button onclick="liberarInventarioDesdeTabla(${id})" class="p-2 bg-amber-100 hover:bg-amber-500 hover:text-black text-amber-600 rounded-lg transition-all" title="Liberar Inventario">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </button>
                <a href="${URLROOT}/presupuesto/ver/${id}" class="p-2 bg-slate-100 hover:bg-neon-green hover:text-black text-slate-500 rounded-lg transition-all" title="Ver">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                </a>
                <button onclick="generarPDFPresupuesto(${id})" class="p-2 bg-purple-100 hover:bg-purple-500 hover:text-black text-purple-600 rounded-lg transition-all" title="PDF">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </button>
            `;
        } else if (estado === 'ACEPTADO') {
            actionsHtml = `
                <button onclick="convertirPresupuestoAVenta(${id})" class="p-2 bg-purple-100 hover:bg-purple-500 hover:text-black text-purple-600 rounded-lg transition-all" title="Convertir a Venta">
                    <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                </button>
                <button onclick="iniciarProcesoDesdeTabla(${id})" class="p-2 bg-blue-100 hover:bg-blue-500 hover:text-black text-blue-600 rounded-lg transition-all" title="Iniciar Proceso (OS/Facturación/Venta)">
                    <i data-lucide="play-circle" class="w-4 h-4"></i>
                </button>
                <a href="${URLROOT}/presupuesto/ver/${id}" class="p-2 bg-slate-100 hover:bg-neon-green hover:text-black text-slate-500 rounded-lg transition-all" title="Ver">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                </a>
                <button onclick="generarPDFPresupuesto(${id})" class="p-2 bg-purple-100 hover:bg-purple-500 hover:text-black text-purple-600 rounded-lg transition-all" title="PDF">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </button>
            `;
        } else if (estado === 'EN_PROCESO') {
            actionsHtml = `
                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-[10px] font-black rounded-lg">En Proceso</span>
                <a href="${URLROOT}/presupuesto/ver/${id}" class="p-2 bg-slate-100 hover:bg-neon-green hover:text-black text-slate-500 rounded-lg transition-all" title="Ver">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                </a>
                <button onclick="generarPDFPresupuesto(${id})" class="p-2 bg-purple-100 hover:bg-purple-500 hover:text-black text-purple-600 rounded-lg transition-all" title="PDF">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </button>
            `;
        } else {
            actionsHtml = `
                <a href="${URLROOT}/presupuesto/ver/${id}" class="p-2 bg-slate-100 hover:bg-neon-green hover:text-black text-slate-500 rounded-lg transition-all" title="Ver">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                </a>
                <button onclick="generarPDFPresupuesto(${id})" class="p-2 bg-purple-100 hover:bg-purple-500 hover:text-black text-purple-600 rounded-lg transition-all" title="PDF">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </button>
            `;
        }
        
        actionsCell.innerHTML = actionsHtml;
        if (window.lucide) lucide.createIcons();
    }

    // Actualizar data-id si cambió
    if (data.id) {
        row.dataset.id = data.id;
    }
}

async function cambiarEstadoPresupuesto(id, estado) {
    const estadoLabels = {
        'ACEPTADO': 'Aceptado',
        'RECHAZADO': 'Rechazado'
    };

    const result = await Swal.fire({
        title: `¿Marcar como ${estadoLabels[estado]}?`,
        text: `El presupuesto pasará a estado "${estadoLabels[estado]}"`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: estado === 'ACEPTADO' ? '#10b981' : '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: `Sí, marcar ${estadoLabels[estado]}`,
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const res = await fetch(`${URLROOT}/presupuesto/cambiarEstado/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    estado
                })
            });
            const result = await res.json();

            if (result.success) {
                AppUtils.showToast(result.mensaje, 'success');
                // Actualizar fila dinámicamente sin recargar
                actualizarFilaPresupuesto(id, { ...result.data, estado });
            } else {
                AppUtils.showToast(result.mensaje || 'Error al cambiar estado', 'error');
            }
        } catch (e) {
            AppUtils.showToast('Error de conexión', 'error');
        }
    }
}

async function eliminarPresupuesto(id) {
    const result = await Swal.fire({
        title: '¿Eliminar presupuesto?',
        text: 'Solo se pueden eliminar presupuestos en estado BORRADOR. Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const res = await fetch(`${URLROOT}/presupuesto/eliminar/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                }
            });
            const data = await res.json();

            if (data.success) {
                AppUtils.showToast('Presupuesto eliminado', 'success');
                // Eliminar fila dinámicamente sin recargar
                const row = document.querySelector(`#presupuestos-body tr[data-id="${id}"]`);
                if (row) {
                    row.style.transition = 'opacity 0.3s ease';
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 300);
                }
            } else {
                AppUtils.showToast(data.mensaje || 'Error al eliminar', 'error');
            }
        } catch (e) {
            AppUtils.showToast('Error de conexión', 'error');
        }
    }
}

/* ==================== ACCIONES DESDE TABLA (NUEVOS ESTADOS) ==================== */
async function activarPresupuestoDesdeTabla(id) {
    const result = await Swal.fire({
        title: '¿Activar Presupuesto?',
        text: 'Se reservará el stock de los productos en inventario. El presupuesto pasará a estado ACTIVO.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, activar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        AppUtils.showLoading('Activando presupuesto y reservando stock...');
        try {
            const res = await fetch(`${URLROOT}/presupuesto/activar/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                }
            });
            const result = await res.json();
            AppUtils.hideLoading();

            if (result.success) {
                AppUtils.showToast(result.mensaje, 'success');
                // Actualizar fila dinámicamente sin recargar
                actualizarFilaPresupuesto(id, { ...result.data, estado: 'ACTIVO' });
            } else {
                AppUtils.showToast(result.mensaje || 'Error al activar', 'error');
            }
        } catch (e) {
            AppUtils.hideLoading();
            AppUtils.showToast('Error de conexión', 'error');
        }
    }
}

async function iniciarProcesoDesdeTabla(id) {
    const {
        value: formValues
    } = await Swal.fire({
        title: 'Iniciar Proceso',
        html: `
            <div class="text-left space-y-4 pt-2">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Módulo *</label>
                    <select id="proceso-modulo" class="swal2-input w-full m-0 text-sm">
                        <option value="OS">Orden de Servicio</option>
                        <option value="FACTURACION">Facturación</option>
                        <option value="VENTA">Venta Repuestos</option>
                        <option value="OTRO">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">ID de Referencia (opcional)</label>
                    <input type="text" id="proceso-referencia" class="swal2-input w-full m-0 text-sm" placeholder="ID de la OS, Factura o Venta">
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'INICIAR PROCESO',
        confirmButtonColor: '#3b82f6',
        preConfirm: () => {
            const modulo = document.getElementById('proceso-modulo').value;
            const referenciaId = document.getElementById('proceso-referencia').value;
            return {
                modulo,
                referencia_id: referenciaId || null
            };
        }
    });

    if (formValues) {
        AppUtils.showLoading('Iniciando proceso...');
        try {
            const res = await fetch(`${URLROOT}/presupuesto/iniciarProceso/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(formValues)
            });
            const result = await res.json();
            AppUtils.hideLoading();

            if (result.success) {
                AppUtils.showToast(result.mensaje, 'success');
                // Actualizar fila dinámicamente sin recargar
                actualizarFilaPresupuesto(id, { ...result.data, estado: 'EN_PROCESO' });
            } else {
                AppUtils.showToast(result.mensaje || 'Error al iniciar proceso', 'error');
            }
        } catch (e) {
            AppUtils.hideLoading();
            AppUtils.showToast('Error de conexión', 'error');
        }
    }
}

async function liberarInventarioDesdeTabla(id) {
    const {
        value: formValues
    } = await Swal.fire({
        title: 'Liberar Inventario',
        html: `
            <div class="text-left space-y-4 pt-2">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Motivo *</label>
                    <select id="liberar-motivo" class="swal2-input w-full m-0 text-sm">
                        <option value="CANCELACION_CLIENTE">Cancelación por Cliente</option>
                        <option value="ERROR_STOCK">Error en Stock</option>
                        <option value="CAMBIO_PRESUPUESTO">Cambio de Presupuesto</option>
                        <option value="OTRO">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Detalles</label>
                    <textarea id="liberar-detalles" rows="3" class="swal2-input w-full m-0 text-sm" placeholder="Detalles adicionales..."></textarea>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'LIBERAR INVENTARIO',
        confirmButtonColor: '#f59e0b',
        preConfirm: () => {
            const motivo = document.getElementById('liberar-motivo').value;
            const detalles = document.getElementById('liberar-detalles').value;
            return {
                motivo: `${motivo}${detalles ? ' - ' + detalles : ''}`
            };
        }
    });

    if (formValues) {
        AppUtils.showLoading('Liberando inventario...');
        try {
            const res = await fetch(`${URLROOT}/presupuesto/liberarInventario/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    motivo: formValues.motivo
                })
            });
            const result = await res.json();
            AppUtils.hideLoading();

            if (result.success) {
                AppUtils.showToast(result.mensaje, 'success');
                // Actualizar fila dinámicamente sin recargar
                actualizarFilaPresupuesto(id, { ...result.data, estado: 'BORRADOR' });
            } else {
                AppUtils.showToast(result.mensaje || 'Error al liberar', 'error');
            }
        } catch (e) {
            AppUtils.hideLoading();
            AppUtils.showToast('Error de conexión', 'error');
        }
    }
}

/**
 * Convierte un presupuesto en una venta (factura)
 */
async function convertirPresupuestoAVenta(id) {
    const { value: formValues } = await Swal.fire({
        title: 'Convertir a Venta',
        html: `
            <div class="text-left space-y-4 pt-2">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Pago en Efectivo</label>
                    <input type="number" id="venta-efectivo" class="swal2-input w-full m-0 text-sm" step="0.01" min="0" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Pago por Transferencia</label>
                    <input type="number" id="venta-transferencia" class="swal2-input w-full m-0 text-sm" step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="bg-slate-50 p-3 rounded-lg">
                    <p class="text-xs font-bold text-slate-600">Total del presupuesto: <span id="venta-total-display" class="text-neon-green"></span></p>
                    <p class="text-xs font-bold text-slate-600">Saldo pendiente: <span id="venta-saldo-display" class="text-red-600"></span></p>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'CONVERTIR A VENTA',
        confirmButtonColor: '#7c3aed',
        didOpen: () => {
            // Obtener el total del presupuesto desde la fila
            const row = document.querySelector(`#presupuestos-body tr[data-id="${id}"]`);
            const totalText = row.querySelector('td:nth-child(5)').textContent;
            // Parsear formato es-CO: "1.000,00" -> 1000.00
            const parseEsCO = (text) => parseFloat(text.replace('$', '').replace(/\./g, '').replace(',', '.')) || 0;
            const total = parseEsCO(totalText);
            
            document.getElementById('venta-total-display').textContent = '$' + total.toLocaleString('es-CO', {minimumFractionDigits: 2});
            document.getElementById('venta-efectivo').value = total.toFixed(2);
            
            const updateSaldo = () => {
                const efectivo = parseFloat(document.getElementById('venta-efectivo').value) || 0;
                const transferencia = parseFloat(document.getElementById('venta-transferencia').value) || 0;
                const total = parseEsCO(document.getElementById('venta-total-display').textContent);
                const saldo = Math.max(0, total - (efectivo + transferencia));
                document.getElementById('venta-saldo-display').textContent = '$' + saldo.toLocaleString('es-CO', {minimumFractionDigits: 2});
            };
            
            document.getElementById('venta-efectivo').addEventListener('input', updateSaldo);
            document.getElementById('venta-transferencia').addEventListener('input', updateSaldo);
            updateSaldo();
        },
        preConfirm: () => {
            const efectivo = parseFloat(document.getElementById('venta-efectivo').value) || 0;
            const transferencia = parseFloat(document.getElementById('venta-transferencia').value) || 0;
            const parseEsCO = (text) => parseFloat(text.replace('$', '').replace(/\./g, '').replace(',', '.')) || 0;
            const total = parseEsCO(document.getElementById('venta-total-display').textContent);
            
            if (efectivo + transferencia > total) {
                Swal.showValidationMessage('El pago total no puede exceder el monto del presupuesto');
                return false;
            }
            
            return { 
                pago_efectivo: efectivo, 
                pago_transferencia: transferencia 
            };
        }
    });
    
    if (formValues) {
        AppUtils.showLoading('Convirtiendo presupuesto a venta...');
        try {
            const res = await fetch(`${URLROOT}/presupuesto/convertirAVenta/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ datos_pago: formValues })
            });
            const result = await res.json();
            AppUtils.hideLoading();
            
            if (result.success) {
                AppUtils.showToast(result.mensaje, 'success');
                if (result.redirect) {
                    setTimeout(() => window.location.href = result.redirect, 1500);
                } else {
                    // Actualizar fila dinámicamente
                    actualizarFilaPresupuesto(id, { ...result.data, estado: 'CONVERTIDO' });
                }
            } else {
                AppUtils.showToast(result.mensaje || 'Error al convertir a venta', 'error');
            }
        } catch (e) {
            AppUtils.hideLoading();
            AppUtils.showToast('Error de conexión', 'error');
        }
    }
}

/* ==================== INIT ==================== */
document.addEventListener('DOMContentLoaded', () => {
    cargarPresupuestos(1);
    lucide.createIcons();
    
    // Verificar si hay parámetro de edición en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit');
    if (editId) {
        // Limpiar el parámetro de la URL sin recargar
        window.history.replaceState({}, document.title, window.location.pathname);
        // Cargar y editar el presupuesto
        cargarPresupuestoParaEditar(editId);
    }
});

/* ==================== EDICIÓN DE PRESUPUESTO EXISTENTE ==================== */
async function cargarPresupuestoParaEditar(id) {
    try {
        const res = await fetch(`${URLROOT}/presupuesto/obtener/${id}`);
        const result = await res.json();
        
        if (result.success && result.data) {
            abrirModalEditar(result.data);
        } else {
            AppUtils.showToast('Error al cargar el presupuesto', 'error');
        }
    } catch (e) {
        AppUtils.showToast('Error de conexión', 'error');
    }
}

function abrirModalEditar(presupuesto) {
    document.getElementById('presupuestoModal').classList.remove('hidden');
    document.getElementById('presupuestoModalTitle').textContent = 'Editar Presupuesto';
    document.getElementById('presupuesto-id').value = presupuesto.id;
    document.getElementById('presupuesto-estado').value = presupuesto.estado;
    
    // Datos del cliente
    document.getElementById('presupuesto-cliente-id').value = presupuesto.cliente_id || '';
    document.getElementById('presupuesto-cliente-cedula').value = presupuesto.cliente_cedula || '';
    document.getElementById('presupuesto-cliente-nombre').value = presupuesto.cliente_nombre || '';
    document.getElementById('presupuesto-cliente-telefono').value = presupuesto.cliente_telefono || '';
    document.getElementById('presupuesto-cliente-email').value = presupuesto.cliente_email || '';
    document.getElementById('presupuesto-cliente-direccion').value = presupuesto.cliente_direccion || '';
    
    // Datos del vehículo
    document.getElementById('presupuesto-vehiculo-placa').value = presupuesto.vehiculo_placa || '';
    document.getElementById('presupuesto-vehiculo-marca').value = presupuesto.vehiculo_marca || '';
    document.getElementById('presupuesto-vehiculo-modelo').value = presupuesto.vehiculo_modelo || '';
    document.getElementById('presupuesto-vehiculo-anio').value = presupuesto.vehiculo_anio || '';
    document.getElementById('presupuesto-vehiculo-color').value = presupuesto.vehiculo_color || '';
    
    // Configuración
    document.getElementById('presupuesto-fecha-emision').value = presupuesto.fecha_emision || new Date().toISOString().split('T')[0];
    document.getElementById('presupuesto-validez-dias').value = presupuesto.validez_dias || 30;
    document.getElementById('presupuesto-iva-activo').checked = presupuesto.iva_activo == 1;
    document.getElementById('presupuesto-tasa-iva').value = presupuesto.tasa_iva || '<?php echo $config_iva; ?>';
    
    // Observaciones y condiciones
    document.getElementById('presupuesto-observaciones').value = presupuesto.observaciones || '';
    document.getElementById('presupuesto-condiciones').value = presupuesto.condiciones || '';
    
    // Cargar items
    const tbody = document.getElementById('presupuesto-items-body');
    tbody.innerHTML = '';
    itemCounter = 0;
    
    if (presupuesto.items && presupuesto.items.length > 0) {
        presupuesto.items.forEach(item => {
            agregarItemPresupuesto(item);
        });
    } else {
        tbody.innerHTML = '<tr id="empty-items-row"><td colspan="10" class="px-4 py-16 text-center text-slate-400 italic">No hay items agregados</td></tr>';
    }
    
    actualizarTotalesPresupuesto();
    lucide.createIcons();
    
    // Cargar sugerencias de clientes
    cargarSugerenciasClientes();
}

/* ==================== SEGUNDO TIPO DE PRESUPUESTO (SIN VEHÍCULO) ==================== */
function abrirModalRepuestos() {
    document.getElementById('presupuestoModal').classList.remove('hidden');
    document.getElementById('formPresupuesto').reset();
    document.getElementById('presupuestoModalTitle').textContent = 'Nuevo Presupuesto de Repuestos';
    document.getElementById('presupuesto-id').value = '';
    document.getElementById('presupuesto-estado').value = 'BORRADOR';
    document.getElementById('presupuesto-fecha-emision').value = new Date().toISOString().split('T')[0];
    document.getElementById('presupuesto-validez-dias').value = 30;
    document.getElementById('presupuesto-iva-activo').checked = false;
    document.getElementById('presupuesto-tasa-iva').value = '<?php echo $config_iva; ?>';
    document.getElementById('presupuesto-items-body').innerHTML =
        '<tr id="empty-items-row"><td colspan="10" class="px-4 py-16 text-center text-slate-400 italic">No hay items agregados</td></tr>';
    itemCounter = 0;
    actualizarTotalesPresupuesto();
    lucide.createIcons();
    
    // Ocultar campos de vehículo
    ocultarCamposVehiculo(true);
    
    // Cargar sugerencias de clientes
    cargarSugerenciasClientes();
}

function ocultarCamposVehiculo(ocultar) {
    const camposVehiculo = [
        'presupuesto-vehiculo-placa',
        'presupuesto-vehiculo-marca',
        'presupuesto-vehiculo-modelo',
        'presupuesto-vehiculo-anio',
        'presupuesto-vehiculo-color'
    ];
    
    camposVehiculo.forEach(id => {
        const input = document.getElementById(id);
        const label = input?.previousElementSibling;
        const container = input?.parentElement;
        if (container) {
            container.style.display = ocultar ? 'none' : '';
        }
    });
    
    // También ocultar los labels si están en contenedores separados
    const vehicleSection = document.querySelector('label[for="presupuesto-vehiculo-placa"]')?.closest('.grid');
    if (vehicleSection) {
        // Ocultar toda la fila de vehículo si existe
        const vehicleInputs = vehicleSection.querySelectorAll('input[id^="presupuesto-vehiculo-"]');
        vehicleInputs.forEach(input => {
            const container = input.parentElement;
            if (container) container.style.display = ocultar ? 'none' : '';
        });
    }
}
</script>