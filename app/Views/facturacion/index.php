<section id="sec-facturacion" class="content-section">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Nueva Facturación</h2>
        <div class="flex gap-2">
            <button onclick="openDraftsModal()" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:bg-slate-50 transition shadow-sm">
                <i data-lucide="folder-open"></i> Borradores
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna Izquierda: Buscador e Items -->
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-card p-6 rounded-xl">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                    <i data-lucide="search" class="text-neon-green"></i> Buscar Ítems
                </h3>
                <div class="relative">
                    <input type="text" id="pos-search" placeholder="Buscar producto o servicio..." class="w-full p-3 pl-10 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none">
                    <i data-lucide="search" class="absolute left-3 top-3.5 text-slate-400 w-5 h-5"></i>
                </div>
                <!-- Resultados de búsqueda dinámicos -->
                <div id="pos-search-results" class="mt-2 max-h-60 overflow-y-auto hidden border border-slate-200 rounded-lg shadow-lg bg-white z-20"></div>
            </div>

            <!-- Tabla de Carrito -->
            <div class="glass-card p-6 rounded-xl">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="py-3 font-bold text-slate-500 uppercase text-xs">Descripción</th>
                            <th class="py-3 font-bold text-slate-500 uppercase text-xs">Cant.</th>
                            <th class="py-3 font-bold text-slate-500 uppercase text-xs">Precio</th>
                            <th class="py-3 font-bold text-slate-500 uppercase text-xs">Subtotal</th>
                            <th class="py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="pos-cart-body">
                        <!-- Se llena mediante app.js -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Columna Derecha: Cliente y Resumen -->
        <div class="space-y-6">
            <div class="glass-card p-6 rounded-xl">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                    <i data-lucide="user" class="text-neon-green"></i> Cliente
                </h3>
                <div id="pos-selected-client" class="p-3 border border-dashed border-slate-300 rounded-lg text-center text-slate-500 cursor-pointer hover:bg-slate-50 transition" onclick="openClientSelectionModal()">
                    Seleccionar Cliente
                </div>
            </div>

            <div class="glass-card p-6 rounded-xl bg-navy-blue text-white">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Subtotal:</span>
                        <span id="pos-subtotal">$0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">IVA (<span id="pos-iva-percent">0</span>%):</span>
                        <span id="pos-iva-amount">$0.00</span>
                    </div>
                    <div class="border-t border-gray-700 pt-3 flex justify-between items-center">
                        <span class="font-bold">Total:</span>
                        <span id="pos-total" class="text-2xl font-bold text-neon-green">$0.00</span>
                    </div>
                </div>
                <button onclick="processSale()" class="w-full mt-6 bg-neon-green text-black font-bold py-3 rounded-lg hover:opacity-90 transition">
                    Finalizar Venta
                </button>
            </div>
        </div>
    </div>
</section>

<script src="<?php echo URLROOT; ?>/js/facturacion.js"></script>