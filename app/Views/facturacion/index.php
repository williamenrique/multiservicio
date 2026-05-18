<section id="sec-facturacion" class="content-section">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold">Punto de Venta</h2>
            <p class="text-slate-400 text-sm">ID Factura: <span id="pos-factura-id" class="font-mono font-bold text-navy-blue">---</span></p>
        </div>
        <div class="flex gap-2">
            <button id="btn-new-invoice" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:bg-slate-50 transition shadow-sm">
                <i data-lucide="plus-circle"></i> Nueva Factura
            </button>
            <button id="btn-save-draft" class="bg-navy-blue text-white px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:opacity-90 transition shadow-sm">
                <i data-lucide="save"></i> Encolar
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
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
                        <option value="">Consumidor Final</option>
                    </select>
                </div>
            </div>

            <!-- Buscador Estilo Select -->
            <div class="glass-card p-6 rounded-xl relative">
                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Añadir del Inventario</label>
                <div class="relative">
                    <input type="text" id="pos-search" placeholder="Buscar producto..." class="w-full p-2 pl-9 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-sm">
                    <i data-lucide="search" class="absolute left-3 top-2.5 text-slate-400 w-4 h-4"></i>
                    <div id="pos-search-results" class="absolute w-full mt-1 max-h-60 overflow-y-auto hidden border border-slate-200 rounded-lg shadow-2xl bg-white z-[100]"></div>
                </div>
                <div class="flex gap-2 mt-3">
                    <input type="number" id="pos-qty" value="1" min="1" class="w-20 p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-center text-sm font-bold">
                    <button id="btn-add-item" class="flex-1 bg-navy-blue text-white p-2 rounded-lg hover:bg-slate-800 transition text-xs font-bold flex items-center justify-center gap-2">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> AGREGAR
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
                    <h3 class="text-xs font-black text-navy-blue uppercase flex items-center gap-2">
                        <i data-lucide="file-text"></i> Detalle de Factura
                    </h3>
                    <div id="pos-queue-list" class="flex gap-2 max-w-lg overflow-x-auto py-1"></div>
                </div>

                <!-- Cuerpo: Lista de Items -->
                <div class="flex-1 overflow-y-auto p-4 bg-white">
                    <table class="w-full">
                        <tbody id="pos-cart-body" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>

                <!-- Pie: Totales y Botón de Cierre -->
                <div class="p-6 bg-navy-blue text-white rounded-b-xl">
                    <div class="space-y-3">
                        <div class="flex justify-between items-end">
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase font-bold">Subtotal</span>
                                <p id="pos-subtotal" class="text-xl">$0.00</p>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-gray-400 uppercase font-bold tracking-widest text-neon-green">Total Facturado</span>
                                <p id="pos-total" class="text-5xl font-black text-neon-green">$0.00</p>
                            </div>
                        </div>
                        <span id="pos-iva-percent" class="hidden"><?php echo $data['iva_defecto']; ?></span>
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