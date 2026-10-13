<?php if (!defined('URLROOT')) exit('No direct script access allowed'); ?>
<div class="container mx-auto p-6 space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-navy-blue uppercase tracking-tight flex items-center gap-3">
                <i data-lucide="shield-check" class="w-8 h-8 text-neon-green"></i>
                Garantías
            </h1>
            <p class="text-sm text-slate-500 font-medium mt-1">Gestión de garantías de servicios y repuestos · 15 días servicio (excepto lavados)</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-slate-200">
        <button id="tab-nueva" onclick="cambiarTabGarantia('nueva')"
            class="px-6 py-3 font-black text-sm uppercase tracking-wider border-b-2 border-neon-green text-navy-blue transition-all">
            <i data-lucide="plus-circle" class="w-4 h-4 inline mr-2"></i>Nueva Garantía
        </button>
        <button id="tab-historial" onclick="cambiarTabGarantia('historial')"
            class="px-6 py-3 font-black text-sm uppercase tracking-wider border-b-2 border-transparent text-slate-400 hover:text-navy-blue transition-all">
            <i data-lucide="history" class="w-4 h-4 inline mr-2"></i>Historial
        </button>
    </div>

    <!-- ==================== SECCIÓN: NUEVA GARANTÍA ==================== -->
    <section id="sec-nueva" class="space-y-6">

        <!-- Buscador de facturas -->
        <div class="glass-card rounded-2xl p-6">
            <h2 class="text-lg font-black text-navy-blue uppercase mb-4 flex items-center gap-2">
                <i data-lucide="search" class="w-5 h-5 text-neon-green"></i>Buscar Factura
            </h2>
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" id="search-factura" placeholder="Buscar por # factura o cliente..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-neon-green"
                        onkeydown="if(event.key==='Enter') cargarFacturasGarantia(1)">
                </div>
                <button onclick="cargarFacturasGarantia(1)"
                    class="bg-navy-blue text-white px-6 py-2.5 rounded-xl font-bold text-sm uppercase hover:bg-neon-green hover:text-black transition-all">
                    <i data-lucide="search" class="w-4 h-4 inline mr-1"></i>Buscar
                </button>
            </div>
        </div>

        <!-- Tabla de facturas -->
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-wider">
                            <th class="px-4 py-3 text-left"># Factura</th>
                            <th class="px-4 py-3 text-left">Cliente</th>
                            <th class="px-4 py-3 text-left">Placa</th>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-facturas" class="divide-y divide-slate-100">
                        <tr><td colspan="6" class="text-center py-10 text-slate-400 font-medium">Ingrese una búsqueda para ver facturas con garantía</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="pag-facturas" class="px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-3 border-t border-slate-200">
                <p id="info-facturas" class="text-xs text-slate-500 font-medium"></p>
                <div id="ctrl-facturas" class="flex gap-1"></div>
            </div>
        </div>

        <!-- Panel de items de la factura seleccionada -->
        <div id="panel-items" class="glass-card rounded-2xl p-6 hidden">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-black text-navy-blue uppercase flex items-center gap-2">
                    <i data-lucide="package" class="w-5 h-5 text-neon-green"></i>
                    Items de la Factura <span id="num-factura" class="text-neon-green"></span>
                </h2>
                <button onclick="cerrarPanelItems()" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-wider">
                            <th class="px-3 py-3 text-left">Descripción</th>
                            <th class="px-3 py-3 text-center">Tipo</th>
                            <th class="px-3 py-3 text-center">Cant.</th>
                            <th class="px-3 py-3 text-right">P. Unitario</th>
                            <th class="px-3 py-3 text-right">Total</th>
                            <th class="px-3 py-3 text-center">Garantía</th>
                            <th class="px-3 py-3 text-center">Estado</th>
                            <th class="px-3 py-3 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-items" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
            <p class="text-[10px] text-slate-400 font-bold mt-3 uppercase">
                <i data-lucide="info" class="w-3 h-3 inline"></i>
                Garantía servicio: 15 días (excepto lavados). Repuestos usan su propio plazo. Solo items vigentes pueden garantía.
            </p>
        </div>
    </section>

    <!-- ==================== SECCIÓN: HISTORIAL ==================== -->
    <section id="sec-historial" class="space-y-6 hidden">

        <div class="glass-card rounded-2xl p-6">
            <h2 class="text-lg font-black text-navy-blue uppercase mb-4 flex items-center gap-2">
                <i data-lucide="filter" class="w-5 h-5 text-neon-green"></i>Filtros
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" id="hist-search" placeholder="Buscar por cliente o motivo..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                </div>
                <input type="date" id="hist-desde"
                    class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                <input type="date" id="hist-hasta"
                    class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                <button onclick="cargarHistorialGarantias(1)"
                    class="bg-navy-blue text-white px-6 py-2.5 rounded-xl font-bold text-sm uppercase hover:bg-neon-green hover:text-black transition-all">
                    <i data-lucide="search" class="w-4 h-4 inline mr-1"></i>Filtrar
                </button>
            </div>
        </div>

        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-wider">
                            <th class="px-3 py-3 text-left">Fecha</th>
                            <th class="px-3 py-3 text-left"># Garantía</th>
                            <th class="px-3 py-3 text-left">Fact. Orig.</th>
                            <th class="px-3 py-3 text-left">Cliente</th>
                            <th class="px-3 py-3 text-left">Tipo</th>
                            <th class="px-3 py-3 text-right">Total</th>
                            <th class="px-3 py-3 text-left">Motivo</th>
                            <th class="px-3 py-3 text-left">Usuario</th>
                            <th class="px-3 py-3 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-historial" class="divide-y divide-slate-100">
                        <tr><td colspan="9" class="text-center py-10 text-slate-400 font-medium">Cargue el historial con el botón Filtrar</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="pag-historial" class="px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-3 border-t border-slate-200">
                <p id="info-historial" class="text-xs text-slate-500 font-medium"></p>
                <div id="ctrl-historial" class="flex gap-1"></div>
            </div>
        </div>
    </section>
</div>

<script>
/* ==================== ESTADO ==================== */
let paginaFacturas = 1, totalPaginasFacturas = 1;
let paginaHistorial = 1, totalPaginasHistorial = 1;
let facturaSeleccionada = null;
let itemsFacturaActual = [];
let factorIvaFacturaActual = 0; // iva_monto / subtotal de la factura original

/* ==================== TABS ==================== */
function cambiarTabGarantia(tab) {
    const secNueva = document.getElementById('sec-nueva');
    const secHist = document.getElementById('sec-historial');
    const tabNueva = document.getElementById('tab-nueva');
    const tabHist = document.getElementById('tab-historial');

    if (tab === 'nueva') {
        secNueva.classList.remove('hidden');
        secHist.classList.add('hidden');
        tabNueva.classList.add('border-neon-green', 'text-navy-blue');
        tabNueva.classList.remove('border-transparent', 'text-slate-400');
        tabHist.classList.add('border-transparent', 'text-slate-400');
        tabHist.classList.remove('border-neon-green', 'text-navy-blue');
    } else {
        secNueva.classList.add('hidden');
        secHist.classList.remove('hidden');
        tabHist.classList.add('border-neon-green', 'text-navy-blue');
        tabHist.classList.remove('border-transparent', 'text-slate-400');
        tabNueva.classList.add('border-transparent', 'text-slate-400');
        tabNueva.classList.remove('border-neon-green', 'text-navy-blue');
        if (document.getElementById('tbody-historial').querySelector('td')) {
            cargarHistorialGarantias(1);
        }
    }
    if (window.lucide) lucide.createIcons();
}

/* ==================== FACTURAS ==================== */
async function cargarFacturasGarantia(pagina = 1) {
    paginaFacturas = pagina;
    const search = document.getElementById('search-factura').value.trim();

    const tbody = document.getElementById('tbody-facturas');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-slate-400"><i data-lucide="loader-2" class="w-6 h-6 inline animate-spin"></i> Cargando...</td></tr>';
    if (window.lucide) lucide.createIcons();

    try {
        const res = await fetch(`${URLROOT}/garantia/listarFacturas`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ pagina, search })
        });
        const data = await res.json();

        if (!data.success) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-rose-500 font-medium">${data.mensaje || 'Error'}</td></tr>`;
            return;
        }

        const facturas = data.data || [];
        const total = data.paginacion?.total_registros || 0;
        totalPaginasFacturas = data.paginacion?.total_paginas || 1;

        if (facturas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-slate-400 font-medium">No se encontraron facturas con garantía</td></tr>';
        } else {
            tbody.innerHTML = facturas.map(f => `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 font-black text-navy-blue">#${f.id}</td>
                    <td class="px-4 py-3 text-sm font-medium text-slate-700">${escapeHtml(f.cliente || 'Consumidor Final')}</td>
                    <td class="px-4 py-3 text-sm text-slate-500 font-bold">${escapeHtml(f.placa || '-')}</td>
                    <td class="px-4 py-3 text-sm text-slate-500">${new Date(f.fecha).toLocaleDateString()}</td>
                    <td class="px-4 py-3 text-sm font-bold text-right text-slate-700">${AppUtils.formatCurrency(f.total)}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex gap-1 justify-center">
                            <button onclick="seleccionarFacturaGarantia(${f.id})"
                                class="bg-navy-blue text-white px-3 py-1.5 rounded-lg text-xs font-bold uppercase hover:bg-neon-green hover:text-black transition-all"
                                title="Ver items para procesar garantía">
                                <i data-lucide="eye" class="w-3 h-3 inline"></i> Ver Items
                            </button>
                            <button onclick="verDetalleFacturaGarantia(${f.id})"
                                class="bg-slate-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold uppercase hover:bg-neon-green hover:text-black transition-all"
                                title="Ver toda la información de la factura">
                                <i data-lucide="file-text" class="w-3 h-3 inline"></i> Ver Detalle
                            </button>
                        </div>
                    </td>
                </tr>`).join('');
        }

        document.getElementById('info-facturas').textContent = `Mostrando ${facturas.length} de ${total} facturas - Página ${paginaFacturas} de ${totalPaginasFacturas}`;
        renderPaginacion('ctrl-facturas', paginaFacturas, totalPaginasFacturas, 'cargarFacturasGarantia');
        if (window.lucide) lucide.createIcons();
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-rose-500 font-medium">Error de conexión</td></tr>';
    }
}

/* ==================== ITEMS DE FACTURA ==================== */
async function seleccionarFacturaGarantia(facturaId) {
    facturaSeleccionada = facturaId;
    const panel = document.getElementById('panel-items');
    const tbody = document.getElementById('tbody-items');
    document.getElementById('num-factura').textContent = `#${facturaId}`;
    panel.classList.remove('hidden');
    panel.scrollIntoView({ behavior: 'smooth' });

    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-10 text-slate-400"><i data-lucide="loader-2" class="w-6 h-6 inline animate-spin"></i> Cargando items...</td></tr>';
    if (window.lucide) lucide.createIcons();

    try {
        const res = await fetch(`${URLROOT}/garantia/getDetalleFactura/${facturaId}`);
        const data = await res.json();

        if (!data.success || !data.items || data.items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-10 text-slate-400 font-medium">Esta factura no tiene items aptos para garantía</td></tr>';
            itemsFacturaActual = [];
            factorIvaFacturaActual = 0;
            return;
        }

        itemsFacturaActual = data.items;

        // Calcular factor IVA de la factura original
        if (data.factura) {
            const subtotal = parseFloat(data.factura.subtotal) || 0;
            const ivaMonto = parseFloat(data.factura.iva_monto) || 0;
            factorIvaFacturaActual = (subtotal > 0) ? (ivaMonto / subtotal) : 0;
        }

        const hayVigentes = data.items.some(it => it.garantia_vigente);

        tbody.innerHTML = data.items.map(it => {
            const vigente = it.garantia_vigente;
            const tipoItem = it.tipo_item === 'REPUESTO' ? 'REPUESTO' : 'SERVICIO';
            const tipoBadge = it.tipo_item === 'REPUESTO'
                ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-700">${tipoItem}</span>`
                : `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-700">${tipoItem}</span>`;
            const lavadoBadge = it.es_lavado
                ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-700">LAVADO</span>`
                : '';
            const badgeGarantia = vigente
                ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">${it.dias_restantes} días restantes</span>`
                : `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700">Vencida</span>`;
            const estadoGarantia = `${it.dias_transcurridos}/${it.dias_garantia_aplicado} días`;

            // Total con IVA del item
            const baseItem = (parseFloat(it.precio_unitario) || 0) * (parseInt(it.cantidad) || 0);
            const ivaItem = baseItem * factorIvaFacturaActual;
            const totalItem = baseItem + ivaItem;

            const btn = vigente
                ? `<button onclick="abrirModalGarantia(${it.detalle_id})"
                    class="bg-neon-green text-black px-3 py-1.5 rounded-lg text-xs font-bold uppercase hover:bg-emerald-600 transition-all">
                    <i data-lucide="shield-check" class="w-3 h-3 inline"></i> Garantía
                  </button>`
                : `<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-600">
                    <i data-lucide="shield-x" class="w-3 h-3"></i> No posee
                  </span>`;
            return `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-3 py-3 text-sm font-medium text-slate-700">${escapeHtml(it.descripcion)}</td>
                    <td class="px-3 py-3 text-center">${tipoBadge} ${lavadoBadge}</td>
                    <td class="px-3 py-3 text-center text-sm font-bold text-slate-700">${it.cantidad}</td>
                    <td class="px-3 py-3 text-right text-sm text-slate-700">${AppUtils.formatCurrency(it.precio_unitario)}</td>
                    <td class="px-3 py-3 text-right text-sm font-black text-emerald-700">${AppUtils.formatCurrency(totalItem)}</td>
                    <td class="px-3 py-3 text-center text-xs font-bold text-slate-600">${estadoGarantia}</td>
                    <td class="px-3 py-3 text-center">${badgeGarantia}</td>
                    <td class="px-3 py-3 text-center">${btn}</td>
                </tr>`;
        }).join('');

        if (hayVigentes) {
            tbody.innerHTML += `
                <tr class="bg-slate-50">
                    <td colspan="8" class="px-3 py-4 text-center">
                        <button onclick="abrirModalGarantiaTodos()"
                            class="bg-navy-blue text-white px-6 py-2.5 rounded-xl font-bold text-sm uppercase hover:bg-neon-green hover:text-black transition-all">
                            <i data-lucide="shield-check" class="w-4 h-4 inline mr-1"></i>Procesar Garantía (Todos los items vigentes)
                        </button>
                    </td>
                </tr>`;
        } else {
            tbody.innerHTML += `
                <tr class="bg-slate-50">
                    <td colspan="8" class="px-3 py-4 text-center">
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-black uppercase bg-rose-100 text-rose-600">
                            <i data-lucide="shield-x" class="w-4 h-4"></i> No posee para garantía
                        </span>
                    </td>
                </tr>`;
        }

        if (window.lucide) lucide.createIcons();
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-10 text-rose-500 font-medium">Error de conexión</td></tr>';
    }
}

function cerrarPanelItems() {
    document.getElementById('panel-items').classList.add('hidden');
    facturaSeleccionada = null;
    itemsFacturaActual = [];
    factorIvaFacturaActual = 0;
}

/* ==================== MODAL GARANTÍA v3.0 ==================== */

/**
 * Construye el HTML del bloque de items dentro del modal.
 * Cada item muestra: descripción, tipo, cant, precio, subtotal, IVA, total,
 * y los selects de acción/destino según tipo de item.
 */
function construirItemsHtml() {
    const vigentes = itemsFacturaActual.filter(it => it.garantia_vigente);

    return vigentes.map(it => {
        const esRepuesto = it.tipo_item === 'REPUESTO';

        // Acciones disponibles por tipo de item
        const accionOpts = esRepuesto
            ? `<option value="REEMPLAZAR">REEMPLAZAR (Entregar repuesto nuevo, sin cobro)</option>
               <option value="DEVOLVER">DEVOLVER (Reembolsar dinero al cliente)</option>
               <option value="DANADO">DANADO (Descartar, no reingresa al stock)</option>`
            : `<option value="REHACER">REHACER (Rehacer el trabajo, sin cobro)</option>
               <option value="DEVOLVER">DEVOLVER (Reembolsar mano de obra)</option>
               <option value="AUMENTAR">AUMENTAR (Trabajo adicional, se cobra)</option>`;

        const destinoOpts = `<option value="STOCK">STOCK (Reingresar al inventario)</option>
                             <option value="DANADO">DAÑADO (No vuelve al stock)</option>`;

        // Cálculos financieros del item
        const cantidad = parseInt(it.cantidad) || 0;
        const precioUnit = parseFloat(it.precio_unitario) || 0;
        const baseItem = precioUnit * cantidad;
        const ivaItem = baseItem * factorIvaFacturaActual;
        const totalItem = baseItem + ivaItem;

        const tipoBadge = esRepuesto
            ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-700">REPUESTO</span>`
            : `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-700">SERVICIO</span>`;
        const lavadoBadge = it.es_lavado
            ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-700">LAVADO</span>`
            : '';

        return `
            <div class="border border-slate-200 rounded-xl p-4 bg-white" data-item-block="${it.detalle_id}">
                <div class="flex justify-between items-start mb-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-black text-navy-blue uppercase truncate">${escapeHtml(it.descripcion)}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            ${tipoBadge} ${lavadoBadge}
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Cant: ${cantidad}</span>
                            <span class="text-[10px] font-bold text-slate-500">·</span>
                            <span class="text-[10px] font-bold text-slate-500">P.Unit: ${AppUtils.formatCurrency(precioUnit)}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-[11px]">
                            <span class="font-bold text-slate-600">Subtotal: <span class="text-slate-800">${AppUtils.formatCurrency(baseItem)}</span></span>
                            <span class="font-bold text-slate-600">IVA: <span class="text-slate-800">${AppUtils.formatCurrency(ivaItem)}</span></span>
                            <span class="font-black text-emerald-700">Total: ${AppUtils.formatCurrency(totalItem)}</span>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700 whitespace-nowrap ml-2">${it.dias_restantes}d restantes</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Acción</label>
                        <select id="accion-${it.detalle_id}"
                                class="accion-select swal2-input w-full m-0 text-xs py-1.5"
                                data-detalle-id="${it.detalle_id}"
                                data-es-repuesto="${esRepuesto ? '1' : '0'}"
                                data-total-con-iva="${totalItem.toFixed(2)}"
                                onchange="onAccionChange(${it.detalle_id})">
                            ${accionOpts}
                        </select>
                    </div>
                    <div id="destino-wrapper-${it.detalle_id}">
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Destino del repuesto</label>
                        <select id="destino-${it.detalle_id}" class="swal2-input w-full m-0 text-xs py-1.5">
                            ${destinoOpts}
                        </select>
                    </div>
                </div>

                <!-- Monto adicional (solo visible si acción = AUMENTAR) -->
                <div id="monto-extra-wrapper-${it.detalle_id}" class="mt-3 hidden">
                    <label class="block text-[10px] font-black text-amber-600 uppercase mb-1">Monto adicional a cobrar (con IVA)</label>
                    <input type="number" id="monto-extra-${it.detalle_id}"
                           class="swal2-input w-full m-0 text-sm font-black text-navy-blue text-right"
                           min="0" step="0.01" value="0.00" placeholder="0.00"
                           oninput="actualizarResumenGarantia()">
                    <p class="text-[10px] text-slate-400 mt-1">Este monto se cobrará al cliente como trabajo/repuesto adicional.</p>
                </div>

                <!-- Preview del impacto por item -->
                <div id="impacto-${it.detalle_id}" class="mt-3 p-2 bg-slate-50 rounded-lg border border-slate-100 text-[10px] font-bold uppercase text-slate-500"></div>
            </div>`;
    }).join('');
}

/**
 * Se llama al cambiar la acción de un item. Ajusta visibilidad del destino,
 * del monto adicional y actualiza el preview de impacto.
 */
function onAccionChange(detalleId) {
    const sel = document.getElementById(`accion-${detalleId}`);
    if (!sel) return;
    const accion = sel.value;
    const esRepuesto = sel.dataset.esRepuesto === '1';

    const destinoWrapper = document.getElementById(`destino-wrapper-${detalleId}`);
    const montoWrapper = document.getElementById(`monto-extra-wrapper-${detalleId}`);

    // Destino solo aplica para REEMPLAZAR y DEVOLVER en repuestos
    if (esRepuesto && (accion === 'REEMPLAZAR' || accion === 'DEVOLVER')) {
        destinoWrapper.classList.remove('hidden');
    } else {
        destinoWrapper.classList.add('hidden');
    }

    // Monto adicional solo aplica en AUMENTAR
    if (accion === 'AUMENTAR') {
        montoWrapper.classList.remove('hidden');
    } else {
        montoWrapper.classList.add('hidden');
        const input = document.getElementById(`monto-extra-${detalleId}`);
        if (input) input.value = '0.00';
    }

    actualizarImpactoItem(detalleId);
    actualizarResumenGarantia();
}

/**
 * Actualiza el preview de impacto de un item específico según su acción.
 */
function actualizarImpactoItem(detalleId) {
    const sel = document.getElementById(`accion-${detalleId}`);
    const impacto = document.getElementById(`impacto-${detalleId}`);
    if (!sel || !impacto) return;

    const accion = sel.value;
    const esRepuesto = sel.dataset.esRepuesto === '1';
    const totalConIva = parseFloat(sel.dataset.totalConIva) || 0;
    const it = itemsFacturaActual.find(x => x.detalle_id == detalleId);
    const cantidad = it ? parseInt(it.cantidad) : 0;

    let html = '';
    const badge = (txt, color) => `<span class="px-2 py-0.5 rounded text-[9px] font-black ${color} mr-1">${txt}</span>`;

    if (accion === 'REEMPLAZAR') {
        html = `${badge('SIN COBRO','bg-slate-200 text-slate-700')} Se entrega repuesto nuevo. ${badge('STOCK','bg-emerald-100 text-emerald-700')} +${cantidad} unidades reingresan si el destino es STOCK.`;
    } else if (accion === 'DEVOLVER') {
        html = `${badge('EGRESO','bg-rose-100 text-rose-700')} Se devolverán <span class="text-rose-700">${AppUtils.formatCurrency(totalConIva)}</span> al cliente (con IVA). Se registra en Devoluciones.`;
    } else if (accion === 'DANADO') {
        html = `${badge('DESCARTADO','bg-rose-100 text-rose-700')} No reingresa al stock. Se registra en Kardex con observación.`;
    } else if (accion === 'REHACER') {
        html = `${badge('SIN COBRO','bg-slate-200 text-slate-700')} Se rehace el trabajo. No afecta caja ni stock.`;
    } else if (accion === 'AUMENTAR') {
        html = `${badge('INGRESO','bg-emerald-100 text-emerald-700')} Se cobrará al cliente el monto adicional especificado.`;
    }
    impacto.innerHTML = html;
}

/**
 * Recalcula el resumen en vivo del modal.
 * Muestra: total devolución, total cobro adicional, unidades a stock, unidades descartadas.
 */
function actualizarResumenGarantia() {
    const resumenEl = document.getElementById('resumen-garantia');
    if (!resumenEl) return;

    const selects = document.querySelectorAll('.accion-select');
    let totalDevolucion = 0;
    let totalAdicional = 0;
    let unidadesStock = 0;
    let unidadesDescartadas = 0;
    let itemsSeleccionados = 0;

    selects.forEach(sel => {
        const detalleId = sel.dataset.detalleId;
        const accion = sel.value;
        const esRepuesto = sel.dataset.esRepuesto === '1';
        const totalConIva = parseFloat(sel.dataset.totalConIva) || 0;
        const it = itemsFacturaActual.find(x => x.detalle_id == detalleId);
        const cantidad = it ? parseInt(it.cantidad) : 0;

        itemsSeleccionados++;

        if (accion === 'DEVOLVER') {
            totalDevolucion += totalConIva;
        }
        if (accion === 'AUMENTAR') {
            const input = document.getElementById(`monto-extra-${detalleId}`);
            const monto = input ? parseFloat(input.value) || 0 : 0;
            totalAdicional += monto;
        }
        if (accion === 'REEMPLAZAR') {
            const destinoSel = document.getElementById(`destino-${detalleId}`);
            const destino = destinoSel ? destinoSel.value : 'STOCK';
            if (destino === 'STOCK') unidadesStock += cantidad;
            else unidadesDescartadas += cantidad;
        }
        if (accion === 'DEVOLVER') {
            const destinoSel = document.getElementById(`destino-${detalleId}`);
            const destino = destinoSel ? destinoSel.value : 'STOCK';
            if (destino === 'STOCK') unidadesStock += cantidad;
            else unidadesDescartadas += cantidad;
        }
        if (accion === 'DANADO') {
            unidadesDescartadas += cantidad;
        }
    });

    const badgeLine = (label, value, color) =>
        `<div class="flex justify-between items-center py-1">
            <span class="text-[10px] font-black text-slate-500 uppercase">${label}</span>
            <span class="text-sm font-black ${color}">${value}</span>
        </div>`;

    resumenEl.innerHTML = `
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1">
            ${badgeLine('Items a procesar', itemsSeleccionados, 'text-navy-blue')}
            ${badgeLine('Reingresos a stock', unidadesStock + ' uds', 'text-emerald-600')}
            ${badgeLine('Descartados', unidadesDescartadas + ' uds', 'text-rose-600')}
            ${badgeLine('Devolución al cliente', AppUtils.formatCurrency(totalDevolucion), totalDevolucion > 0 ? 'text-rose-600' : 'text-slate-400')}
            ${badgeLine('Cobro adicional', AppUtils.formatCurrency(totalAdicional), totalAdicional > 0 ? 'text-emerald-600' : 'text-slate-400')}
        </div>
    `;
}

/**
 * Recopila los items del modal al estado interno que espera el backend.
 */
function recopilarItemsModal() {
    const selects = document.querySelectorAll('.accion-select');
    const items = [];

    selects.forEach(sel => {
        const detalleId = parseInt(sel.dataset.detalleId);
        const accion = sel.value;
        const esRepuesto = sel.dataset.esRepuesto === '1';
        const it = itemsFacturaActual.find(x => x.detalle_id == detalleId);
        if (!it) return;

        let destino = 'N/A';
        if (esRepuesto && (accion === 'REEMPLAZAR' || accion === 'DEVOLVER')) {
            const destinoSel = document.getElementById(`destino-${detalleId}`);
            destino = destinoSel ? destinoSel.value : 'STOCK';
        }

        let montoAdicional = 0;
        if (accion === 'AUMENTAR') {
            const input = document.getElementById(`monto-extra-${detalleId}`);
            montoAdicional = input ? parseFloat(input.value) || 0 : 0;
        }

        items.push({
            detalle_id: detalleId,
            accion,
            destino,
            monto_adicional: montoAdicional
        });
    });

    return items;
}

async function abrirModalGarantiaTodos() {
    const vigentes = itemsFacturaActual.filter(it => it.garantia_vigente);
    if (vigentes.length === 0) {
        AppUtils.showToast('No hay items vigentes para garantía', 'warning');
        return;
    }
    await mostrarModalGarantia();
}

async function abrirModalGarantia(detalleId) {
    const item = itemsFacturaActual.find(it => it.detalle_id == detalleId);
    if (!item || !item.garantia_vigente) {
        AppUtils.showToast('Item no vigente para garantía', 'warning');
        return;
    }
    // Filtrar solo este item
    const itemsBackup = itemsFacturaActual;
    itemsFacturaActual = [item];
    await mostrarModalGarantia();
    itemsFacturaActual = itemsBackup;
}

async function mostrarModalGarantia() {
    const itemsHtml = construirItemsHtml();

    const { value: formValues } = await Swal.fire({
        title: 'PROCESAR GARANTÍA',
        html: `
            <div class="text-left space-y-4 pt-2">
                <div class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Factura #${facturaSeleccionada}</p>
                    <p class="text-xs text-slate-500 mt-1">La factura original será <strong>ANULADA</strong> y se generará una nueva factura de garantía con los items a <strong>$0</strong> (solo los cobros adicionales suman al total).</p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Tipo de Garantía</label>
                    <select id="tipo-garantia-select" class="swal2-input w-full m-0 text-sm">
                        <option value="SERVICIO">SERVICIO (Solo mano de obra)</option>
                        <option value="REPUESTO">REPUESTO (Solo repuestos)</option>
                        <option value="MIXTO">MIXTO (Servicio + Repuestos)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Items a Garantizar</label>
                    <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                        ${itemsHtml}
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Motivo de la Garantía *</label>
                    <textarea id="motivo-input" rows="3" placeholder="Describa el motivo de la garantía..."
                        class="swal2-input w-full m-0 text-sm resize-none uppercase"
                        oninput="this.value = this.value.toUpperCase();"></textarea>
                </div>

                <div id="resumen-garantia" class="bg-slate-900 text-white rounded-xl p-4 border border-slate-700"></div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'PROCESAR GARANTÍA',
        confirmButtonColor: '#0f766e',
        cancelButtonColor: '#64748b',
        width: '780px',
        didOpen: () => {
            // Inicializar estados
            document.querySelectorAll('.accion-select').forEach(sel => {
                onAccionChange(sel.dataset.detalleId);
            });
            actualizarResumenGarantia();
        },
        preConfirm: () => {
            const tipoGarantia = document.getElementById('tipo-garantia-select').value;
            const motivo = document.getElementById('motivo-input').value.trim().toUpperCase();

            if (!motivo) {
                Swal.showValidationMessage('Debe ingresar el motivo de la garantía');
                return false;
            }

            const items = recopilarItemsModal();
            if (items.length === 0) {
                Swal.showValidationMessage('No hay items seleccionados');
                return false;
            }

            // Validación: si algún item AUMENTAR tiene monto 0, avisar
            const aumentarSinMonto = items.some(it => it.accion === 'AUMENTAR' && it.monto_adicional <= 0);
            if (aumentarSinMonto) {
                Swal.showValidationMessage('Los items con acción AUMENTAR deben tener un monto mayor a 0');
                return false;
            }

            return {
                factura_id: facturaSeleccionada,
                tipo_garantia: tipoGarantia,
                motivo: motivo,
                items: items
            };
        }
    });

    if (!formValues) return;

    AppUtils.showLoading('Procesando garantía...');
    try {
        const res = await fetch(`${URLROOT}/garantia/procesar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(formValues)
        });
        const result = await res.json();
        AppUtils.hideLoading();

        if (result.success) {
            const r = result.resumen || {};
            const resumenMsg = [
                'GARANTÍA PROCESADA CORRECTAMENTE.',
                r.egreso_devolucion > 0 ? `Devolución: ${AppUtils.formatCurrency(r.egreso_devolucion)}.` : '',
                r.ingreso_adicional > 0 ? `Cobro adicional: ${AppUtils.formatCurrency(r.ingreso_adicional)}.` : '',
                r.items_devueltos_stock > 0 ? `Reingresos a stock: ${r.items_devueltos_stock} uds.` : '',
                r.items_descartados > 0 ? `Descartados: ${r.items_descartados} uds.` : ''
            ].filter(Boolean).join(' ');

            await AppUtils.showAlert('Garantía Procesada', resumenMsg, 'success');

            cerrarPanelItems();
            cargarFacturasGarantia(paginaFacturas);
            if (typeof updateDashboard === 'function') updateDashboard();
        } else {
            AppUtils.showAlert('Error', result.mensaje || 'No se pudo procesar', 'error');
        }
    } catch (e) {
        AppUtils.hideLoading();
        console.error(e);
        AppUtils.showAlert('Error', 'Error de conexión al procesar la garantía', 'error');
    }
}

/* ==================== HISTORIAL ==================== */
async function cargarHistorialGarantias(pagina = 1) {
    paginaHistorial = pagina;
    const search = document.getElementById('hist-search').value.trim();
    const desde = document.getElementById('hist-desde').value;
    const hasta = document.getElementById('hist-hasta').value;

    const tbody = document.getElementById('tbody-historial');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-10 text-slate-400"><i data-lucide="loader-2" class="w-6 h-6 inline animate-spin"></i> Cargando...</td></tr>';
    if (window.lucide) lucide.createIcons();

    try {
        const res = await fetch(`${URLROOT}/garantia/historial`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ pagina, search, desde, hasta })
        });
        const data = await res.json();

        if (!data.success) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-10 text-rose-500 font-medium">${data.mensaje || 'Error'}</td></tr>`;
            return;
        }

        const lista = data.data || [];
        const total = data.paginacion?.total_registros || 0;
        totalPaginasHistorial = data.paginacion?.total_paginas || 1;

        if (lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-10 text-slate-400 font-medium">No hay garantías registradas</td></tr>';
        } else {
            tbody.innerHTML = lista.map(g => {
                const tipoBadge = {
                    'SERVICIO': 'bg-purple-100 text-purple-700',
                    'REPUESTO': 'bg-blue-100 text-blue-700',
                    'MIXTO': 'bg-indigo-100 text-indigo-700'
                }[g.tipo_garantia] || 'bg-slate-100 text-slate-700';
                return `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-3 py-3 text-xs text-slate-500">${new Date(g.fecha).toLocaleDateString()}</td>
                    <td class="px-3 py-3 text-xs font-black text-navy-blue">#${g.id}</td>
                    <td class="px-3 py-3 text-xs font-bold text-slate-600">#${g.factura_original_id}</td>
                    <td class="px-3 py-3 text-xs font-medium text-slate-700">${escapeHtml(g.cliente || 'Consumidor Final')}</td>
                    <td class="px-3 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-black ${tipoBadge}">${g.tipo_garantia}</span></td>
                    <td class="px-3 py-3 text-right text-xs font-bold text-slate-700">${AppUtils.formatCurrency(g.monto_total)}</td>
                    <td class="px-3 py-3 text-xs text-slate-600 max-w-[200px] truncate" title="${escapeAttr(g.motivo || '')}">${escapeHtml(g.motivo || '-')}</td>
                    <td class="px-3 py-3 text-xs text-slate-500">${escapeHtml(g.usuario_nombre || '-')}</td>
                    <td class="px-3 py-3 text-center">
                        <button onclick="verDetalleGarantia(${g.id})"
                            class="bg-navy-blue text-white px-2 py-1 rounded-lg text-[10px] font-bold uppercase hover:bg-neon-green hover:text-black transition-all">
                            <i data-lucide="eye" class="w-3 h-3 inline"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
        }

        document.getElementById('info-historial').textContent = `Mostrando ${lista.length} de ${total} garantías - Página ${paginaHistorial} de ${totalPaginasHistorial}`;
        renderPaginacion('ctrl-historial', paginaHistorial, totalPaginasHistorial, 'cargarHistorialGarantias');
        if (window.lucide) lucide.createIcons();
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-10 text-rose-500 font-medium">Error de conexión</td></tr>';
    }
}

/* ==================== DETALLE FACTURA ==================== */
async function verDetalleFacturaGarantia(facturaId) {
    try {
        AppUtils.showLoading('Cargando detalle de factura...');
        const res = await fetch(`${URLROOT}/garantia/getDetalleFactura/${facturaId}`);
        const data = await res.json();
        AppUtils.hideLoading();

        if (!data.success || !data.factura) {
            AppUtils.showToast(data.mensaje || 'No se pudo cargar el detalle', 'error');
            return;
        }

        const f = data.factura;
        const items = data.items || [];

        const itemsHtml = items.map(it => {
            const esRepuesto = it.producto_id != null && it.producto_id !== '';
            const tipo = esRepuesto ? 'REPUESTO' : 'SERVICIO';
            const nombre = esRepuesto ? (it.producto_nombre || 'N/A') : (it.descripcion || 'Servicio');
            const monto = (parseFloat(it.cantidad) || 0) * (parseFloat(it.precio_unitario) || 0);
            return `
            <tr class="border-t border-slate-100">
                <td class="px-2 py-2 text-xs text-slate-700">${escapeHtml(nombre)}</td>
                <td class="px-2 py-2 text-center text-xs font-bold ${esRepuesto ? 'text-indigo-600' : 'text-emerald-600'}">${tipo}</td>
                <td class="px-2 py-2 text-center text-xs text-slate-600">${it.cantidad}</td>
                <td class="px-2 py-2 text-center text-xs text-slate-600">${escapeHtml(it.mecanico_nombre || '-')}</td>
                <td class="px-2 py-2 text-right text-xs font-bold text-slate-700">${AppUtils.formatCurrency(monto)}</td>
            </tr>`;
        }).join('');

        Swal.fire({
            title: `DETALLE FACTURA #${f.factura_id}`,
            html: `
                <div class="text-left space-y-3 text-sm max-h-[60vh] overflow-y-auto pr-1">
                    <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                        <p class="text-[11px] font-black text-blue-700 uppercase mb-2">Información - ${escapeHtml(f.status || '')}</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Cliente</p><p class="font-bold text-navy-blue">${escapeHtml(f.cliente_nombre || 'Consumidor Final')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Cédula</p><p class="font-bold">${escapeHtml(f.cliente_cedula || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Teléfono</p><p class="font-bold">${escapeHtml(f.cliente_telefono || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Placa / Modelo</p><p class="font-bold">${escapeHtml(f.placa || '-')} / ${escapeHtml(f.modelo_vehiculo || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha Factura</p><p class="font-bold">${f.fecha ? new Date(f.fecha).toLocaleString() : '-'}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Total Factura</p><p class="font-bold text-blue-700">${AppUtils.formatCurrency(f.total)}</p></div>
                        </div>
                    </div>
                    ${items.length ? `
                    <div class="overflow-x-auto">
                        <p class="text-[11px] font-black text-slate-500 uppercase mb-1">Items de la Factura</p>
                        <table class="w-full">
                            <thead class="bg-slate-100">
                                <tr class="text-[10px] font-black text-slate-500 uppercase">
                                    <th class="px-2 py-2 text-left">Descripción</th>
                                    <th class="px-2 py-2 text-center">Tipo</th>
                                    <th class="px-2 py-2 text-center">Cant.</th>
                                    <th class="px-2 py-2 text-center">Mecánico</th>
                                    <th class="px-2 py-2 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>${itemsHtml}</tbody>
                        </table>
                    </div>` : '<p class="text-center text-slate-400 text-xs py-4">Esta factura no tiene items registrados.</p>'}
                </div>`,
            showCancelButton: true,
            confirmButtonText: '<i data-lucide="printer" class="w-4 h-4 inline mr-1"></i> IMPRIMIR',
            cancelButtonText: 'CERRAR',
            confirmButtonColor: '#1d4ed8',
            cancelButtonColor: '#0f766e',
            width: '700px'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open(`${URLROOT}/facturacion/imprimir/${f.factura_id}`, '_blank');
            }
        });
    } catch (e) {
        AppUtils.hideLoading();
        console.error(e);
        AppUtils.showToast('Error de conexión', 'error');
    }
}

/* ==================== DETALLE GARANTÍA ==================== */
async function verDetalleGarantia(id) {
    try {
        const res = await fetch(`${URLROOT}/garantia/detalle/${id}`);
        const data = await res.json();

        if (!data.success || !data.garantia) {
            AppUtils.showToast('No se pudo cargar el detalle', 'error');
            return;
        }

        const g = data.garantia;
        const fo = data.factura_original || null;
        const f = fo ? fo.factura : null;

        const detalle = (g.detalle || []).map(d => `
            <tr class="border-t border-slate-100">
                <td class="px-2 py-2 text-xs text-slate-700">${escapeHtml(d.descripcion)}</td>
                <td class="px-2 py-2 text-center text-xs font-bold text-slate-600">${d.tipo_item}</td>
                <td class="px-2 py-2 text-center text-xs text-slate-600">${d.cantidad}</td>
                <td class="px-2 py-2 text-center text-xs font-bold ${d.accion === 'DEVOLVER' ? 'text-rose-600' : (d.accion === 'AUMENTAR' ? 'text-emerald-600' : 'text-slate-600')}">${d.accion}</td>
                <td class="px-2 py-2 text-center text-xs text-slate-600">${d.destino}</td>
                <td class="px-2 py-2 text-right text-xs font-bold text-slate-700">${AppUtils.formatCurrency(d.monto_total)}</td>
            </tr>`).join('');

        const seccionFactura = f ? `
            <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                <p class="text-[11px] font-black text-blue-700 uppercase mb-2">Factura Original #${f.factura_id} - ${f.status || ''}</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Cliente</p><p class="font-bold text-navy-blue">${escapeHtml(f.cliente_nombre || 'Consumidor Final')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Placa</p><p class="font-bold">${escapeHtml(f.placa || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha</p><p class="font-bold">${f.fecha ? new Date(f.fecha).toLocaleString() : '-'}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Total</p><p class="font-bold text-blue-700">${AppUtils.formatCurrency(f.total)}</p></div>
                </div>
            </div>` : '';

        Swal.fire({
            title: `GARANTÍA #${g.id}`,
            html: `
                <div class="text-left space-y-3 text-sm max-h-[60vh] overflow-y-auto pr-1">
                    <div class="grid grid-cols-2 gap-2 bg-slate-50 rounded-xl p-3">
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Cliente</p><p class="font-bold text-navy-blue">${escapeHtml(g.cliente || 'Consumidor Final')}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Cédula</p><p class="font-bold">${escapeHtml(g.cliente_cedula || '-')}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Factura Original</p><p class="font-bold">#${g.factura_original_id} (ANULADA)</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Factura Garantía</p><p class="font-bold text-neon-green">#${g.factura_garantia_id || '-'}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Tipo</p><p class="font-bold">${g.tipo_garantia}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha</p><p class="font-bold">${new Date(g.fecha).toLocaleString()}</p></div>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase">Motivo</p>
                        <p class="text-sm font-medium text-slate-700">${escapeHtml(g.motivo || '-')}</p>
                    </div>
                    ${seccionFactura}
                    <div class="overflow-x-auto">
                        <p class="text-[11px] font-black text-slate-500 uppercase mb-1">Items Procesados</p>
                        <table class="w-full">
                            <thead class="bg-slate-100">
                                <tr class="text-[10px] font-black text-slate-500 uppercase">
                                    <th class="px-2 py-2 text-left">Descripción</th>
                                    <th class="px-2 py-2 text-center">Tipo</th>
                                    <th class="px-2 py-2 text-center">Cant.</th>
                                    <th class="px-2 py-2 text-center">Acción</th>
                                    <th class="px-2 py-2 text-center">Destino</th>
                                    <th class="px-2 py-2 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>${detalle}</tbody>
                        </table>
                    </div>
                    <div class="grid grid-cols-3 gap-2 bg-slate-50 rounded-xl p-3 text-center">
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Mano Obra Ref.</p><p class="font-bold">${AppUtils.formatCurrency(g.monto_mano_obra)}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Repuestos Ref.</p><p class="font-bold">${AppUtils.formatCurrency(g.monto_repuesto)}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Total Ref.</p><p class="font-bold text-neon-green">${AppUtils.formatCurrency(g.monto_total)}</p></div>
                    </div>
                </div>`,
            confirmButtonText: 'CERRAR',
            confirmButtonColor: '#0f766e',
            showDenyButton: true,
            denyButtonText: 'DESCARGAR PDF',
            denyButtonColor: '#1e3a8a',
            width: '700px'
        }).then((result) => {
            if (result.isDenied) {
                window.open(`${URLROOT}/garantia/imprimir/${g.id}`, '_blank');
            }
        });
    } catch (e) {
        console.error(e);
        AppUtils.showToast('Error de conexión', 'error');
    }
}

/* ==================== HELPERS ==================== */
function renderPaginacion(containerId, pagina, totalPaginas, fnName) {
    const ctrl = document.getElementById(containerId);
    if (totalPaginas <= 1) { ctrl.innerHTML = ''; return; }
    let html = '';
    html += `<button onclick="${fnName}(${Math.max(1, pagina - 1)})" class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 hover:bg-slate-100 ${pagina === 1 ? 'opacity-40 cursor-not-allowed' : ''}" ${pagina === 1 ? 'disabled' : ''}>«</button>`;
    for (let i = 1; i <= totalPaginas; i++) {
        if (i === 1 || i === totalPaginas || (i >= pagina - 1 && i <= pagina + 1)) {
            html += `<button onclick="${fnName}(${i})" class="px-3 py-1.5 rounded-lg text-xs font-bold border ${i === pagina ? 'bg-navy-blue text-white border-navy-blue' : 'border-slate-200 hover:bg-slate-100'}">${i}</button>`;
        } else if (i === pagina - 2 || i === pagina + 2) {
            html += `<span class="px-2 text-slate-400">...</span>`;
        }
    }
    html += `<button onclick="${fnName}(${Math.min(totalPaginas, pagina + 1)})" class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 hover:bg-slate-100 ${pagina === totalPaginas ? 'opacity-40 cursor-not-allowed' : ''}" ${pagina === totalPaginas ? 'disabled' : ''}>»</button>`;
    ctrl.innerHTML = html;
}

function escapeHtml(str) {
    if (str == null) return '';
    return String(str).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
function escapeAttr(str) {
    return escapeHtml(str).replace(/'/g, '&#39;');
}

/* ==================== INIT ==================== */
document.addEventListener('DOMContentLoaded', () => {
    cargarFacturasGarantia(1);
    if (window.lucide) lucide.createIcons();
});
</script>