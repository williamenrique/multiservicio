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
            <div class="glass-card p-6 rounded-xl space-y-4 relative z-30">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Descripción / Vehículo</label>
                    <input type="text" id="pos-modelo" placeholder="Ej: CORSA BLANCO" value="<?php echo isset($data['orden']) ? $data['orden']->marca . ' ' . $data['orden']->modelo : ''; ?>" class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none uppercase text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Placa</label>
                    <input type="text" id="pos-placa" placeholder="EJ: ABC123" value="<?php echo $data['orden']->placa ?? ''; ?>" class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none uppercase text-sm">
                </div>
                <div class="<?php echo ($data['user_role'] === 'MECANICO') ? 'hidden' : ''; ?>">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Mecánico Responsable</label>
                    <select id="pos-mecanico-id" class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-sm font-bold bg-white" 
                        <?php echo ($data['user_role'] !== 'ADMINISTRADOR') ? 'disabled' : ''; ?>>
                        <option value="" disabled selected>-- SELECCIONAR MECÁNICO --</option>
                        <?php foreach ($data['staff'] as $m): ?>
                            <?php 
                                $isSelected = (isset($data['orden']) && $data['orden']->mecanico_id == $m->id) || 
                                              (!isset($data['orden']) && $data['user_staff_id'] == $m->id);
                            ?>
                            <option value="<?php echo $m->id; ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                <?php echo $m->nombre; ?> (<?php echo $m->cargo; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="relative w-full">
                    <div class="flex justify-between items-center mb-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block leading-none">Buscar Cliente (Nombre o Cédula)</label>
                        <button type="button" id="btn-quick-client" class="text-navy-blue hover:text-neon-green transition-colors" title="Registro Rápido">
                            <i data-lucide="user-plus" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-2.5 text-slate-300 w-4 h-4"></i>
                        <input type="text" id="pos-client-search" placeholder="Escriba para buscar..." 
                               value="<?php echo $data['orden']->cliente_nombre ?? ''; ?>"
                               class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-neon-green outline-none shadow-sm transition-all uppercase">
                    </div>
                    <div id="pos-client-results" class="absolute z-[110] left-0 right-0 mt-1 bg-white border border-slate-100 rounded-2xl shadow-2xl max-h-60 overflow-y-auto hidden"></div>
                    <select id="pos-cliente-id" class="hidden">
                        <?php if(isset($data['orden'])): ?>
                            <option value="<?php echo trim($data['orden']->cliente_id); ?>" selected><?php echo $data['orden']->cliente_nombre; ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Información de Diagnóstico de Orden (UI Inteligente) -->
                <div id="container-diag-os" class="p-3 bg-blue-50/50 border border-blue-100 rounded-lg <?php echo !isset($data['orden']) ? 'hidden' : ''; ?>">
                    <label class="block text-[9px] font-black text-blue-500 mb-1 uppercase tracking-widest">Diagnóstico de Entrada (O.S.)</label>
                    <p id="text-diag-os" class="text-[10px] text-slate-600 italic leading-tight">
                        <?php echo isset($data['orden']) ? htmlspecialchars($data['orden']->diagnostico_entrada) : ''; ?>
                    </p>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <label id="label-obs" class="block text-[10px] font-bold text-slate-400 mb-1 uppercase"><?php echo isset($data['orden']) ? 'Observaciones de Salida (Factura)' : 'Observaciones / Detalles del Trabajo'; ?></label>
                    <textarea id="pos-observaciones" rows="3" class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none uppercase text-xs font-bold text-navy-blue" 
                              placeholder="EJ: SE REALIZÓ MANTENIMIENTO..."><?php echo $data['orden']->observaciones_factura ?? ''; ?></textarea>
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
                <label class="block text-[10px] font-bold text-blue-600 mb-2 uppercase text-center font-black">Agregar Mano de Obra / Servicios</label>
                <div class="flex flex-col gap-3">
                    <div class="flex-1">
                        <input type="text" id="pos-service-name" placeholder="Ej: CAMBIO DE ACEITE O LAVADO" class="w-full p-2 border border-blue-200 rounded-lg outline-none text-xs uppercase font-bold">
                    </div>
                    <div class="flex gap-2">
                        <input type="number" id="pos-service-price" placeholder="Monto $" class="w-full p-2 border border-blue-200 rounded-lg outline-none text-xs font-black text-navy-blue">
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
                        <h3 class="text-sm font-black text-navy-blue uppercase flex items-center gap-2">
                            <i data-lucide="file-text"></i> Detalle de Factura
                        </h3>
                        <span class="text-[11px] text-slate-400 font-bold uppercase">Responsable: <span id="pos-user-name" class="text-navy-blue"><?php echo $data['orden']->mecanico_nombre ?? '---'; ?></span></span>
                    </div>
                    <span class="text-xs font-mono font-bold bg-navy-blue text-white px-2 py-1 rounded">
                        REF: <span id="pos-factura-id" data-orden-id="<?php echo $data['orden']->id ?? ''; ?>"><?php echo isset($data['orden']) ? 'ORDEN #' . $data['orden']->id : 'VENTA DIRECTA'; ?></span>
                    </span>
                </div>

                <!-- Vista previa de observaciones adicionales -->
                <div id="pos-obs-preview" class="px-4 py-2 bg-amber-50 border-b border-amber-100 text-[10px] text-amber-800 italic font-medium uppercase hidden">
                    <span class="font-black">NOTA EN FACTURA:</span> <span id="pos-obs-text"></span>
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
                            <input type="checkbox" id="pos-iva-toggle" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-neon-green"></div>
                        </label>
                    </div>

                    <!-- Desglose de Pago -->
                    <div class="grid grid-cols-2 gap-4 mb-6 pt-4 border-t border-gray-700/50">
                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase mb-1">Pago Efectivo</label>
                            <div class="relative">
                                <span class="absolute left-2 top-2 text-slate-500 text-xs">$</span>
                                <input type="number" id="pos-pago-efectivo" value="0" step="0.01" 
                                       onfocus="if(this.value == '0') this.value = ''" 
                                       onblur="if(this.value == '') this.value = '0'"
                                       class="w-full bg-slate-800 border border-slate-700 rounded-lg py-2 pl-5 pr-2 text-white font-bold text-sm focus:border-neon-green outline-none transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase mb-1">Transferencia</label>
                            <div class="relative">
                                <span class="absolute left-2 top-2 text-slate-500 text-xs">$</span>
                                <input type="number" id="pos-pago-transferencia" value="0" step="0.01" 
                                       onfocus="if(this.value == '0') this.value = ''" 
                                       onblur="if(this.value == '') this.value = '0'"
                                       class="w-full bg-slate-800 border border-slate-700 rounded-lg py-2 pl-5 pr-2 text-white font-bold text-sm focus:border-neon-green outline-none transition-all">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-slate-200">
                            <span class="text-xs uppercase font-black tracking-widest">Subtotal</span>
                            <p id="pos-subtotal" class="text-2xl font-black text-white">$0.00</p>
                        </div>
                        <div class="flex justify-between items-center text-slate-200">
                            <span class="text-xs uppercase font-black tracking-widest">IVA (<span id="pos-iva-percent-display">0</span>%)</span>
                            <p id="pos-iva" class="text-2xl font-black text-white">$0.00</p>
                        </div>
                        <div class="flex justify-between items-center text-rose-400 border-t border-rose-500/20 pt-4" id="pos-container-deuda">
                            <span class="text-xs uppercase font-black tracking-widest">Saldo Pendiente (Crédito)</span>
                            <p id="pos-saldo-pendiente" class="text-3xl font-black">$0.00</p>
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

<script>
    // Sincronizar nombre en la UI cuando cambia el mecánico (Solo para Administradores)
    const selectMecanico = document.getElementById('pos-mecanico-id');
    if (selectMecanico) {
        selectMecanico.addEventListener('change', function() {
            const name = this.options[this.selectedIndex].text.split('(')[0].trim();
            document.getElementById('pos-user-name').innerText = name;
        });
    }

    // Sincronizar observaciones con la vista previa en la factura
    const obsInput = document.getElementById('pos-observaciones');
    const obsPreview = document.getElementById('pos-obs-preview');
    const obsText = document.getElementById('pos-obs-text');

    function updateObsPreview() {
        const val = obsInput.value.trim();
        if (val) {
            obsText.innerText = val;
            obsPreview.classList.remove('hidden');
        } else {
            obsPreview.classList.add('hidden');
        }
    }

    if(obsInput) {
        obsInput.addEventListener('input', updateObsPreview);
        updateObsPreview(); // Ejecutar al cargar por si viene de O.S.
    }
</script>