<section id="sec-facturacion" class="content-section">
    <!-- Barra de Facturas en Espera (Cola) -->
    <div class="mb-6 overflow-x-auto pb-2">
        <div class="flex gap-3" id="pos-queue-container">
            <!-- Botón para nueva factura siempre visible -->
            <button id="btn-new-invoice" class="flex-shrink-0 bg-navy-blue text-white px-4 py-2 rounded-lg font-bold flex items-center gap-2 border border-slate-500 hover:border-neon-green transition-all group">
                <i data-lucide="plus" class="w-4 h-4 group-hover:rotate-90 transition-transform"></i>
                <span class="text-[10px] uppercase">Nueva Factura</span>
            </button>
            <div id="pos-active-drafts" class="flex gap-3 items-center">
                <!-- Aquí se cargan las facturas o el mensaje de vacío -->
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-2">
        <!-- Columna Izquierda: Entradas y Búsqueda (4 de 12) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Metadatos del Vehículo -->
            <div class="glass-card p-6 rounded-xl space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Descripción / Vehículo</label>
                    <input type="text" id="pos-modelo" placeholder="Ej: CORSA BLANCO" class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none uppercase text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Placa</label>
                    <input type="text" id="pos-placa" placeholder="EJ: ABC123" class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none uppercase text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Cliente</label>
                    <select id="pos-cliente-id" class="w-full p-2 border border-slate-300 rounded-lg outline-none text-sm">
                        <option value="">Cliente</option>
                    </select>
                </div>
            </div>

            <!-- Buscador Estilo Select -->
            <!-- Sección de Búsqueda Mejorada -->
            <div class="glass-card p-6 rounded-xl space-y-4 overflow-visible relative z-20">
                <div class="relative">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Buscador de Repuestos</label>
                    <div class="relative">
                        <input type="text" id="pos-search" placeholder="Escriba nombre o categoría..." class="w-full p-3 pl-10 border border-slate-300 rounded-xl focus:ring-2 focus:ring-neon-green outline-none text-sm">
                        <i data-lucide="search" class="absolute left-3 top-3.5 text-slate-400 w-4 h-4"></i>
                        <div id="pos-search-results" class="absolute w-full mt-2 max-h-96 overflow-y-auto hidden border border-slate-200 rounded-xl shadow-2xl bg-white z-[100] py-1"></div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Cantidad</label>
                        <input type="number" id="pos-qty" value="1" min="1" class="w-full p-2 border border-slate-300 rounded-lg text-center font-bold">
                    </div>
                    <button id="btn-add-item" class="mt-5 bg-navy-blue text-white px-6 rounded-lg hover:bg-slate-800 transition flex items-center gap-2 font-bold text-xs">
                        <i data-lucide="shopping-cart" class="w-4 h-4"></i> AGREGAR
                    </button>
                </div>
            </div>

            <!-- Servicios Manuales -->
            <div class="glass-card p-5 rounded-xl border-l-4 border-l-blue-500 bg-blue-50/10">
                <label class="block text-[10px] font-bold text-blue-600 mb-2 uppercase text-center font-black">Servicio Externo / Mano de Obra</label>
                <div class="flex flex-col gap-3">
                    <div class="flex-1">
                        <input type="text" id="pos-service-name" placeholder="Descripción..." class="w-full p-2 border border-blue-200 rounded-lg outline-none text-xs uppercase">
                    </div>
                    <div class="flex gap-2">
                        <input type="number" id="pos-service-price" placeholder="Precio $" class="w-full p-2 border border-blue-200 rounded-lg outline-none text-xs font-bold">
                        <button id="btn-add-service" class="bg-blue-500 text-white px-4 rounded-lg hover:bg-blue-600 flex items-center justify-center transition-transform active:scale-95">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Detalle de Factura (8 de 12) -->
        <div class="lg:col-span-8 space-y-6">
            <div class="glass-card rounded-xl border-t-4 border-neon-green flex flex-col h-full min-h-[600px] shadow-xl">
                <!-- Cabecera de la Factura -->
                <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-xl">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-xs font-black text-navy-blue uppercase flex items-center gap-2">
                            <i data-lucide="file-text"></i> Detalle de Factura
                        </h3>
                        <span class="text-[9px] text-slate-400 font-bold uppercase">Responsable: <span id="pos-user-name" class="text-navy-blue">---</span></span>
                    </div>
                    <span class="text-[10px] font-mono font-bold bg-navy-blue text-white px-2 py-1 rounded">
                        ID: <span id="pos-factura-id">---</span>
                    </span>
                </div>

                <!-- Cuerpo: Lista de Items -->
                <div class="flex-1 overflow-y-auto p-4 bg-white">
                    <table class="w-full">
                        <tbody id="pos-cart-body" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>

                <!-- Pie: Totales y Botón de Cierre -->
                <div class="p-6 bg-navy-blue text-white rounded-b-xl">
                    <!-- Interruptor para Activar/Desactivar IVA -->
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-700/50">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-neon-green uppercase tracking-widest">Impuesto al Valor Agregado</span>
                            <span class="text-[9px] text-gray-400 uppercase font-bold">Aplicar tarifa del <?php echo $data['iva_defecto']; ?>%</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="pos-iva-toggle" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-neon-green"></div>
                        </label>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-gray-400">
                            <span class="text-[10px] uppercase font-bold">Subtotal</span>
                            <p id="pos-subtotal" class="text-xl font-bold text-white">$0.00</p>
                        </div>
                        <div class="flex justify-between items-center text-gray-400">
                            <span class="text-[10px] uppercase font-bold">IVA (<span id="pos-iva-percent-display">0</span>%)</span>
                            <p id="pos-iva" class="text-xl font-bold text-white">$0.00</p>
                        </div>
                        <div class="flex justify-between items-end pt-4 border-t border-gray-700">
                            <span class="text-[10px] uppercase font-bold tracking-widest text-neon-green">Total Facturado</span>
                            <p id="pos-total" class="text-5xl font-black text-neon-green">$0.00</p>
                        </div>
                    </div>
                    <button id="btn-process-sale" class="w-full mt-6 bg-neon-green text-navy-blue font-black py-4 rounded-xl hover:brightness-110 transition flex items-center justify-center gap-3 uppercase text-lg shadow-lg">
                        <i data-lucide="check-circle" class="w-6 h-6"></i> Cerrar y Procesar
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="<?php echo URLROOT; ?>/js/facturacion.js"></script>