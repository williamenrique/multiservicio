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

        <!-- Filtros historial -->
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

        <!-- Tabla historial -->
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
    lucide.createIcons();
}

/* ==================== FACTURAS ==================== */
async function cargarFacturasGarantia(pagina = 1) {
    paginaFacturas = pagina;
    const search = document.getElementById('search-factura').value.trim();

    const tbody = document.getElementById('tbody-facturas');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-slate-400"><i data-lucide="loader-2" class="w-6 h-6 inline animate-spin"></i> Cargando...</td></tr>';
    lucide.createIcons();

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
        lucide.createIcons();
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

    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-slate-400"><i data-lucide="loader-2" class="w-6 h-6 inline animate-spin"></i> Cargando items...</td></tr>';
    lucide.createIcons();

    try {
        const res = await fetch(`${URLROOT}/garantia/getDetalleFactura/${facturaId}`);
        const data = await res.json();

        if (!data.success || !data.items || data.items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-slate-400 font-medium">Esta factura no tiene items aptos para garantía</td></tr>';
            itemsFacturaActual = [];
            return;
        }

        itemsFacturaActual = data.items;
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
                    <td class="px-3 py-3 text-center text-xs font-bold text-slate-600">${estadoGarantia}</td>
                    <td class="px-3 py-3 text-center">${badgeGarantia}</td>
                    <td class="px-3 py-3 text-center">${btn}</td>
                </tr>`;
        }).join('');

        if (hayVigentes) {
            tbody.innerHTML += `
                <tr class="bg-slate-50">
                    <td colspan="7" class="px-3 py-4 text-center">
                        <button onclick="abrirModalGarantiaTodos()"
                            class="bg-navy-blue text-white px-6 py-2.5 rounded-xl font-bold text-sm uppercase hover:bg-neon-green hover:text-black transition-all">
                            <i data-lucide="shield-check" class="w-4 h-4 inline mr-1"></i>Procesar Garantía (Todos los items vigentes)
                        </button>
                    </td>
                </tr>`;
        } else {
            tbody.innerHTML += `
                <tr class="bg-slate-50">
                    <td colspan="7" class="px-3 py-4 text-center">
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-black uppercase bg-rose-100 text-rose-600">
                            <i data-lucide="shield-x" class="w-4 h-4"></i> No posee para garantía
                        </span>
                    </td>
                </tr>`;
        }

        lucide.createIcons();
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-rose-500 font-medium">Error de conexión</td></tr>';
    }
}

function cerrarPanelItems() {
    document.getElementById('panel-items').classList.add('hidden');
    facturaSeleccionada = null;
    itemsFacturaActual = [];
}

/* ==================== MODAL GARANTÍA ==================== */
function construirItemsHtml() {
    const vigentes = itemsFacturaActual.filter(it => it.garantia_vigente);
    return vigentes.map(it => {
        const esRepuesto = it.tipo_item === 'REPUESTO';
        const accionOpts = esRepuesto
            ? `<option value="REEMPLAZAR">REEMPLAZAR (Entregar nuevo)</option>`
            : `<option value="DEVOLVER">DEVOLVER (Reembolsar mano de obra)</option>
               <option value="AUMENTAR">AUMENTAR (Trabajo adicional - cobra extra)</option>`;
        const destinoOpts = esRepuesto
            ? `<option value="STOCK">STOCK (Reingresar al inventario)</option>
               <option value="DANADO">DAÑADO (No vuelve al stock)</option>`
            : `<option value="N/A">N/A (Servicio - no aplica stock)</option>`;
        return `
            <div class="border border-slate-200 rounded-xl p-3 bg-white">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="text-sm font-bold text-navy-blue">${escapeHtml(it.descripcion)}</p>
                        <p class="text-[10px] text-slate-500 uppercase font-bold">${it.tipo_item} · Cant: ${it.cantidad} · ${AppUtils.formatCurrency(it.precio_unitario)}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">${it.dias_restantes}d restantes</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Acción</label>
                        <select id="accion-${it.detalle_id}" class="swal2-input w-full m-0 text-xs py-1.5">
                            ${accionOpts}
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Destino Repuesto</label>
                        <select id="destino-${it.detalle_id}" class="swal2-input w-full m-0 text-xs py-1.5" ${!esRepuesto ? 'disabled' : ''}>
                            ${destinoOpts}
                        </select>
                    </div>
                </div>
            </div>`;
    }).join('');
}

function recopilarItemsModal() {
    const vigentes = itemsFacturaActual.filter(it => it.garantia_vigente);
    return vigentes.map(it => {
        const accion = document.getElementById(`accion-${it.detalle_id}`).value;
        const destinoEl = document.getElementById(`destino-${it.detalle_id}`);
        const destino = destinoEl && !destinoEl.disabled ? destinoEl.value : 'N/A';
        return {
            detalle_id: it.detalle_id,
            producto_id: it.producto_id || null,
            descripcion: it.descripcion,
            cantidad: it.cantidad,
            precio_unitario: it.precio_unitario,
            tipo_item: it.tipo_item,
            accion,
            destino
        };
    });
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
    const item = itemsFacturaActual.find(it => it.detalle_id === detalleId);
    if (!item || !item.garantia_vigente) {
        AppUtils.showToast('Item no vigente para garantía', 'warning');
        return;
    }
    // Filtrar solo este item
    const itemsBackup = itemsFacturaActual;
    itemsFacturaActual = itemsFacturaActual.filter(it => it.detalle_id === detalleId || !it.garantia_vigente);
    // En realidad solo queremos este item vigente
    itemsFacturaActual = itemsBackup.filter(it => it.detalle_id === detalleId);
    await mostrarModalGarantia();
    itemsFacturaActual = itemsBackup;
}

async function mostrarModalGarantia() {
    const itemsHtml = construirItemsHtml();
    const { value: formValues } = await Swal.fire({
        title: 'PROCESAR GARANTÍA',
        html: `
            <div class="text-left space-y-4 pt-2">
                <div class="bg-slate-50 rounded-xl p-3">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Factura #${facturaSeleccionada}</p>
                    <p class="text-xs text-slate-500 mt-1">Se anulará la factura original y se generará una nueva de garantía</p>
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
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        ${itemsHtml}
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Motivo de la Garantía</label>
                    <textarea id="motivo-input" rows="3" placeholder="Describa el motivo de la garantía..."
                        class="swal2-input w-full m-0 text-sm resize-none uppercase"
                        oninput="this.value = this.value.toUpperCase();"></textarea>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                    <label class="block text-[10px] font-black text-amber-600 uppercase mb-1">Monto a Cobrar (Incremento Adicional)</label>
                    <p class="text-[10px] text-amber-600 mb-2">Por defecto es 0.00 (la garantía NO se cobra: mano de obra ya cobrada, repuesto reemplazado). Ingrese un monto SOLO si hay cobro adicional por trabajo/repuesto extra.</p>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-black text-amber-600">Bs.</span>
                        <input type="number" id="monto-a-cobrar-input" value="0.00" step="0.01" min="0"
                            class="swal2-input flex-1 m-0 text-sm font-black text-right"
                            placeholder="0.00">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Si el monto es 0.00, la garantía NO afecta caja ni transacciones. Si es mayor a 0, ese monto se registra como ingreso.</p>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'PROCESAR GARANTÍA',
        confirmButtonColor: '#0f766e',
        width: '650px',
        didOpen: () => {
            // El monto a cobrar es manual: el cajero decide si hay cobro adicional o no.
            // No hay cálculo automático de total (la garantía por defecto no se cobra).
        },
        preConfirm: () => {
            const tipoGarantia = document.getElementById('tipo-garantia-select').value;
            const motivo = document.getElementById('motivo-input').value.trim().toUpperCase();
            const montoACobrar = parseFloat(document.getElementById('monto-a-cobrar-input').value) || 0;
            if (!motivo) {
                Swal.showValidationMessage('Debe ingresar el motivo de la garantía');
                return false;
            }
            if (montoACobrar < 0) {
                Swal.showValidationMessage('El monto a cobrar no puede ser negativo');
                return false;
            }
            const items = recopilarItemsModal();
            if (items.length === 0) {
                Swal.showValidationMessage('No hay items seleccionados');
                return false;
            }
            return { factura_id: facturaSeleccionada, tipo_garantia: tipoGarantia, motivo, items, monto_a_cobrar: montoACobrar };
        }
    });

    if (formValues) {
        AppUtils.showLoading();
        try {
            const res = await fetch(`${URLROOT}/garantia/procesar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify(formValues)
            });
            const result = await res.json();
            AppUtils.hideLoading();

            if (result.success) {
                AppUtils.showToast(result.mensaje || 'Garantía procesada correctamente', 'success');
                cerrarPanelItems();
                cargarFacturasGarantia(paginaFacturas);
                if (typeof updateDashboard === 'function') updateDashboard();
            } else {
                AppUtils.showAlert('Error', result.mensaje || 'No se pudo procesar', 'error');
            }
        } catch (e) {
            AppUtils.hideLoading();
            console.error(e);
            AppUtils.showAlert('Error', 'Error de conexión', 'error');
        }
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
    lucide.createIcons();

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
        lucide.createIcons();
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-10 text-rose-500 font-medium">Error de conexión</td></tr>';
    }
}

/* ==================== DETALLE FACTURA COMPLETO ==================== */
async function verDetalleFacturaGarantia(facturaId) {
    try {
        AppUtils.showLoading('Cargando detalle de factura...');
        const res = await fetch(`${URLROOT}/garantia/getDetalleFactura/${facturaId}`);
        const data = await res.json();
        AppUtils.hideLoading();

        if (!data.success || !data.factura) {
            AppUtils.showToast(data.mensaje || 'No se pudo cargar el detalle de la factura', 'error');
            return;
        }

        const f = data.factura;
        const items = data.items || [];

        // Construir tabla de items
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

        // Sección de Orden de Servicio (si existe)
        const seccionOS = f.os_id ? `
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                <p class="text-[11px] font-black text-slate-600 uppercase mb-2">Orden de Servicio #${f.os_id} - ${escapeHtml(f.os_estado || '-')}</p>
                <div class="grid grid-cols-2 gap-2">
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Mecánico Asignado</p><p class="font-bold">${escapeHtml(f.os_mecanico_nombre || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Kilometraje</p><p class="font-bold">${escapeHtml(f.os_kilometraje || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Combustible</p><p class="font-bold">${escapeHtml(f.os_combustible || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha Ingreso</p><p class="font-bold">${f.os_fecha_ingreso ? new Date(f.os_fecha_ingreso).toLocaleString() : '-'}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha Entrega</p><p class="font-bold">${f.os_fecha_entrega ? new Date(f.os_fecha_entrega).toLocaleString() : '-'}</p></div>
                </div>
                ${f.os_diag_entrada ? `<div class="mt-2"><p class="text-[10px] font-black text-slate-400 uppercase">Diagnóstico Entrada</p><p class="text-xs text-slate-700">${escapeHtml(f.os_diag_entrada)}</p></div>` : ''}
                ${f.os_diag_salida ? `<div class="mt-2"><p class="text-[10px] font-black text-slate-400 uppercase">Diagnóstico Salida</p><p class="text-xs text-slate-700">${escapeHtml(f.os_diag_salida)}</p></div>` : ''}
                ${f.os_observaciones ? `<div class="mt-2"><p class="text-[10px] font-black text-slate-400 uppercase">Observaciones OS</p><p class="text-xs text-slate-700">${escapeHtml(f.os_observaciones)}</p></div>` : ''}
            </div>` : '';

        Swal.fire({
            title: `DETALLE FACTURA #${f.factura_id}`,
            html: `
                <div class="text-left space-y-3 text-sm max-h-[60vh] overflow-y-auto pr-1">
                    <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                        <p class="text-[11px] font-black text-blue-700 uppercase mb-2">Información de la Factura - ${escapeHtml(f.status || '')}</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Cliente</p><p class="font-bold text-navy-blue">${escapeHtml(f.cliente_nombre || 'Consumidor Final')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Cédula</p><p class="font-bold">${escapeHtml(f.cliente_cedula || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Teléfono</p><p class="font-bold">${escapeHtml(f.cliente_telefono || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Placa / Modelo</p><p class="font-bold">${escapeHtml(f.placa || '-')} / ${escapeHtml(f.modelo_vehiculo || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha Factura</p><p class="font-bold">${f.fecha ? new Date(f.fecha).toLocaleString() : '-'}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Cobrado Por</p><p class="font-bold">${escapeHtml(f.usuario_cobro_nombre || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Atendido Por</p><p class="font-bold">${escapeHtml(f.os_mecanico_nombre || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Origen</p><p class="font-bold">${escapeHtml(f.origen || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Total Factura</p><p class="font-bold text-blue-700">${AppUtils.formatCurrency(f.total)}</p></div>
                        </div>
                        ${f.observaciones ? `<div class="mt-2"><p class="text-[10px] font-black text-slate-400 uppercase">Observaciones</p><p class="text-xs text-slate-700">${escapeHtml(f.observaciones)}</p></div>` : ''}
                    </div>
                    ${seccionOS}
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
        const fItems = fo ? (fo.items || []) : [];

        // Items de la factura original (repuestos y servicios con mecánico)
        const itemsFacturaHtml = fItems.map(it => {
            const esRepuesto = it.producto_id != null && it.producto_id !== '';
            const tipo = esRepuesto ? 'REPUESTO' : 'SERVICIO';
            const nombre = esRepuesto ? (it.producto_nombre || 'N/A') : (it.descripcion || 'Servicio');
            const desc = esRepuesto ? (it.descripcion || '') : '';
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

        // Detalle de la garantía (items procesados)
        const detalle = (g.detalle || []).map(d => `
            <tr class="border-t border-slate-100">
                <td class="px-2 py-2 text-xs text-slate-700">${escapeHtml(d.descripcion)}</td>
                <td class="px-2 py-2 text-center text-xs font-bold text-slate-600">${d.tipo_item}</td>
                <td class="px-2 py-2 text-center text-xs text-slate-600">${d.cantidad}</td>
                <td class="px-2 py-2 text-center text-xs text-slate-600">${d.accion}</td>
                <td class="px-2 py-2 text-center text-xs text-slate-600">${d.destino}</td>
                <td class="px-2 py-2 text-right text-xs font-bold text-slate-700">${AppUtils.formatCurrency(d.monto_total)}</td>
            </tr>`).join('');

        // Sección factura original (si existe)
        const seccionFactura = f ? `
            <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                <p class="text-[11px] font-black text-blue-700 uppercase mb-2">Factura Original #${f.factura_id} - ${f.status || ''}</p>
                <div class="grid grid-cols-2 gap-2">
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Cliente</p><p class="font-bold text-navy-blue">${escapeHtml(f.cliente_nombre || 'Consumidor Final')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Cédula</p><p class="font-bold">${escapeHtml(f.cliente_cedula || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Teléfono</p><p class="font-bold">${escapeHtml(f.cliente_telefono || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Placa / Modelo</p><p class="font-bold">${escapeHtml(f.placa || '-')} / ${escapeHtml(f.modelo_vehiculo || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha Factura</p><p class="font-bold">${f.fecha ? new Date(f.fecha).toLocaleString() : '-'}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Cobrado Por</p><p class="font-bold">${escapeHtml(f.usuario_cobro_nombre || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Origen</p><p class="font-bold">${escapeHtml(f.origen || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Total Factura</p><p class="font-bold text-blue-700">${AppUtils.formatCurrency(f.total)}</p></div>
                </div>
                ${f.observaciones ? `<div class="mt-2"><p class="text-[10px] font-black text-slate-400 uppercase">Observaciones Factura</p><p class="text-xs text-slate-700">${escapeHtml(f.observaciones)}</p></div>` : ''}
            </div>
            ${f.os_id ? `
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                <p class="text-[11px] font-black text-slate-600 uppercase mb-2">Orden de Servicio #${f.os_id} - ${escapeHtml(f.os_estado || '-')}</p>
                <div class="grid grid-cols-2 gap-2">
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Mecánico Asignado</p><p class="font-bold">${escapeHtml(f.os_mecanico_nombre || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Kilometraje</p><p class="font-bold">${escapeHtml(f.os_kilometraje || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Combustible</p><p class="font-bold">${escapeHtml(f.os_combustible || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha Ingreso</p><p class="font-bold">${f.os_fecha_ingreso ? new Date(f.os_fecha_ingreso).toLocaleString() : '-'}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha Entrega</p><p class="font-bold">${f.os_fecha_entrega ? new Date(f.os_fecha_entrega).toLocaleString() : '-'}</p></div>
                </div>
                ${f.os_diag_entrada ? `<div class="mt-2"><p class="text-[10px] font-black text-slate-400 uppercase">Diagnóstico Entrada</p><p class="text-xs text-slate-700">${escapeHtml(f.os_diag_entrada)}</p></div>` : ''}
                ${f.os_diag_salida ? `<div class="mt-2"><p class="text-[10px] font-black text-slate-400 uppercase">Diagnóstico Salida</p><p class="text-xs text-slate-700">${escapeHtml(f.os_diag_salida)}</p></div>` : ''}
                ${f.os_observaciones ? `<div class="mt-2"><p class="text-[10px] font-black text-slate-400 uppercase">Observaciones OS</p><p class="text-xs text-slate-700">${escapeHtml(f.os_observaciones)}</p></div>` : ''}
            </div>` : ''}
            ${fItems.length ? `
            <div class="overflow-x-auto">
                <p class="text-[11px] font-black text-slate-500 uppercase mb-1">Items de la Factura Original</p>
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
                    <tbody>${itemsFacturaHtml}</tbody>
                </table>
            </div>` : ''}` : '';

        Swal.fire({
            title: `GARANTÍA #${g.id}`,
            html: `
                <div class="text-left space-y-3 text-sm max-h-[60vh] overflow-y-auto pr-1">
                    <div class="grid grid-cols-2 gap-2 bg-slate-50 rounded-xl p-3">
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Cliente</p><p class="font-bold text-navy-blue">${escapeHtml(g.cliente || 'Consumidor Final')}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Cédula</p><p class="font-bold">${escapeHtml(g.cliente_cedula || '-')}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Teléfono</p><p class="font-bold">${escapeHtml(g.cliente_telefono || '-')}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Placa</p><p class="font-bold">${escapeHtml(g.placa || '-')}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Factura Original</p><p class="font-bold">#${g.factura_original_id} (ANULADA)</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Factura Garantía</p><p class="font-bold text-neon-green">#${g.factura_garantia_id || '-'}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Tipo</p><p class="font-bold">${g.tipo_garantia}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha Garantía</p><p class="font-bold">${new Date(g.fecha).toLocaleString()}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Procesado Por</p><p class="font-bold">${escapeHtml(g.usuario_nombre || '-')}</p></div>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase">Motivo</p>
                        <p class="text-sm font-medium text-slate-700">${escapeHtml(g.motivo || '-')}</p>
                    </div>
                    ${seccionFactura}
                    <div class="overflow-x-auto">
                        <p class="text-[11px] font-black text-slate-500 uppercase mb-1">Items Procesados en Garantía</p>
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
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Mano Obra</p><p class="font-bold">${AppUtils.formatCurrency(g.monto_mano_obra)}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Repuestos</p><p class="font-bold">${AppUtils.formatCurrency(g.monto_repuesto)}</p></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Total</p><p class="font-bold text-neon-green">${AppUtils.formatCurrency(g.monto_total)}</p></div>
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
    return String(str).replace(/[&<>"']/g, c => ({ '&': '&', '<': '<', '>': '>', '"': '"', "'": '&#39;' }[c]));
}
function escapeAttr(str) {
    return escapeHtml(str).replace(/'/g, '&#39;');
}

/* ==================== INIT ==================== */
document.addEventListener('DOMContentLoaded', () => {
    cargarFacturasGarantia(1);
    lucide.createIcons();
});
</script>
