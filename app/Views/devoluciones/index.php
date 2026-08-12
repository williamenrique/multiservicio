<section id="sec-devoluciones" class="content-section">
    <!-- Encabezado unificado -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase"><?php echo $data['titulo']; ?></h1>
            <p class="text-slate-500 text-sm">Gestiona devoluciones: procesa nuevas o consulta el historial.</p>
        </div>
    </div>

    <!-- Barra de Tabs -->
    <div class="flex gap-2 mb-6 border-b border-slate-200">
        <button id="tabProcesar"
            class="flex items-center gap-2 px-6 py-3 text-xs font-black uppercase tracking-widest border-b-2 border-neon-green text-navy-blue bg-slate-50/50 rounded-t-xl transition-all">
            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
            Procesar Devolución
        </button>
        <button id="tabHistorial"
            class="flex items-center gap-2 px-6 py-3 text-xs font-black uppercase tracking-widest border-b-2 border-transparent text-slate-400 hover:text-navy-blue rounded-t-xl transition-all">
            <i data-lucide="history" class="w-4 h-4"></i>
            Historial
        </button>
    </div>

    <!-- ===== PANEL PROCESAR ===== -->
    <div id="panelProcesar">
    <!-- Buscador -->
    <div class="flex flex-col md:flex-row gap-4 mb-6">
        <div class="relative flex-1 max-w-md">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
            <input type="text" id="searchFacturasDev" placeholder="Buscar por # factura, placa o cliente..."
                class="w-full bg-white border border-slate-200 rounded-xl py-3 pl-12 pr-4 text-slate-700 outline-none focus:border-neon-green transition-all shadow-sm">
        </div>
    </div>

    <!-- Tabla de facturas con repuestos -->
    <div class="glass-card rounded-2xl overflow-hidden border border-slate-200/60 shadow-xl">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i data-lucide="receipt" class="w-4 h-4 text-navy-blue"></i>
                Facturas con Repuestos Devolvibles
            </h2>
            <div class="flex items-center gap-4">
                <div
                    class="px-4 py-2 bg-white border border-slate-200 rounded-xl font-bold text-xs text-slate-500 shadow-sm">
                    Total: <span id="totalCount" class="text-navy-blue">0</span>
                </div>
                <select id="limitSelector"
                    class="bg-white border border-slate-200 rounded-xl py-2 px-4 text-xs font-bold text-navy-blue outline-none focus:border-neon-green shadow-sm cursor-pointer">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        <div class="p-6 overflow-x-auto">
            <table id="facturasDevTable" class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="text-slate-400 text-[10px] uppercase font-black tracking-widest border-b border-slate-100">
                        <th class="px-4 py-4">Factura #</th>
                        <th class="px-4 py-4">Fecha</th>
                        <th class="px-4 py-4">Cliente</th>
                        <th class="px-4 py-4">Placa</th>
                        <th class="px-4 py-4 text-center">Items</th>
                        <th class="px-4 py-4 text-right">Total</th>
                        <th class="px-4 py-4 text-center">Estado</th>
                        <th class="px-4 py-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="8" class="text-center py-10 text-slate-400 text-sm">Cargando facturas...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="px-8 py-4 bg-white border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Mostrando <span id="startIndex">0</span> - <span id="endIndex">0</span> de <span id="totalItemsDisplay">0</span> facturas
            </div>
            <div class="flex items-center gap-2" id="paginationControls">
                <!-- Los botones de navegación se generan dinámicamente -->
            </div>
        </div>
    </div>
</div>
<!-- ===== FIN PANEL PROCESAR ===== -->

<!-- ===== PANEL HISTORIAL ===== -->
<div id="panelHistorial" class="hidden">
    <!-- Filtros -->
    <div class="flex flex-col md:flex-row gap-4 mb-6">
        <div class="relative flex-1 max-w-md">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
            <input type="text" id="searchDevHist" placeholder="Buscar por # devolución, factura, placa, producto..."
                class="w-full bg-white border border-slate-200 rounded-xl py-3 pl-12 pr-4 text-slate-700 outline-none focus:border-neon-green transition-all shadow-sm">
        </div>

        <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-xl border border-slate-200 shadow-sm">
            <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
            <input type="date" id="devHist-desde" class="text-xs font-bold border-none outline-none text-slate-600">
            <span class="text-slate-300 font-black">/</span>
            <input type="date" id="devHist-hasta" class="text-xs font-bold border-none outline-none text-slate-600">
        </div>
    </div>

    <!-- Tabla de devoluciones -->
    <div class="glass-card rounded-2xl overflow-hidden border border-slate-200/60 shadow-xl">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i data-lucide="history" class="w-4 h-4 text-navy-blue"></i>
                Historial de Devoluciones
            </h2>
            <div class="flex items-center gap-4">
                <div class="px-4 py-2 bg-white border border-slate-200 rounded-xl font-bold text-xs text-slate-500 shadow-sm">
                    Total: <span id="totalCountHist" class="text-navy-blue">0</span>
                </div>
                <select id="limitSelectorHist" class="bg-white border border-slate-200 rounded-xl py-2 px-4 text-xs font-bold text-navy-blue outline-none focus:border-neon-green shadow-sm cursor-pointer">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        <div class="p-6 overflow-x-auto">
            <table id="devHistTable" class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-400 text-[10px] uppercase font-black tracking-widest border-b border-slate-100">
                        <th class="px-4 py-4">Devolución #</th>
                        <th class="px-4 py-4">Fecha</th>
                        <th class="px-4 py-4">Factura</th>
                        <th class="px-4 py-4">Placa</th>
                        <th class="px-4 py-4">Producto</th>
                        <th class="px-4 py-4 text-center">Cant.</th>
                        <th class="px-4 py-4 text-right">Monto</th>
                        <th class="px-4 py-4 text-center">Destino</th>
                        <th class="px-4 py-4 text-center">Garantía</th>
                        <th class="px-4 py-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody id="tableBodyHist">
                    <tr><td colspan="10" class="text-center py-10 text-slate-400 text-sm">Cargando devoluciones...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="px-8 py-4 bg-white border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Mostrando <span id="startIndexHist">0</span> - <span id="endIndexHist">0</span> de <span id="totalItemsDisplayHist">0</span> devoluciones
            </div>
            <div class="flex items-center gap-2" id="paginationControlsHist">
                <!-- Los botones de navegación se generan dinámicamente -->
            </div>
        </div>
    </div>
</div>
<!-- ===== FIN PANEL HISTORIAL ===== -->

<!-- Modal: Items de la factura para devolución -->
<div id="modalItemsDev"
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <div>
                <h3 class="text-lg font-black text-navy-blue uppercase">Items de la Factura #<span
                        id="modalFacturaId"></span></h3>
                <p class="text-xs text-slate-500">Selecciona el repuesto a devolver y el destino.</p>
            </div>
            <button onclick="cerrarModalItems()" class="text-slate-400 hover:text-red-500 transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <div id="itemsDevContainer" class="space-y-3">
                <p class="text-center text-slate-400 py-8">Cargando items...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Confirmar devolución -->
<div id="modalProcesarDev"
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[210] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-red-50">
            <h3 class="text-lg font-black text-red-600 uppercase">Confirmar Devolución</h3>
        </div>
        <div class="p-6 space-y-4">
            <div id="infoItemDev" class="bg-slate-50 rounded-xl p-4 text-sm space-y-1"></div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Destino del Ítem</label>
                <select id="destinoDev"
                    class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-sm font-bold">
                    <option value="STOCK">Reingresar al Inventario (Stock)</option>
                    <option value="DANADO">Marcar como Dañado (No reingresa)</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Motivo de la Devolución</label>
                <textarea id="motivoDev" rows="3" placeholder="Ej: Producto defectuoso, error de venta..."
                    class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none text-xs font-bold uppercase"></textarea>
            </div>
            <div id="alertaGarantiaDev" class="hidden p-3 rounded-lg text-xs font-bold"></div>
        </div>
        <div class="p-6 border-t border-slate-100 flex gap-3 justify-end">
            <button onclick="cerrarModalProcesar()"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-600 transition-all">Cancelar</button>
            <button id="btnConfirmarDev"
                class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition-all shadow-lg">
                <i data-lucide="rotate-ccw" class="w-4 h-4 inline mr-1"></i> Procesar Devolución
            </button>
        </div>
    </div>
</div>

<!-- Modal: Detalle de devolución -->
<div id="modalDetalleDev" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <div>
                <h3 class="text-lg font-black text-navy-blue uppercase">Detalle de Devolución #<span id="detalleDevId"></span></h3>
                <p class="text-xs text-slate-500">Información completa de la devolución.</p>
            </div>
            <button onclick="cerrarModalDetalle()" class="text-slate-400 hover:text-red-500 transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <div id="detalleDevContainer" class="space-y-4">
                <p class="text-center text-slate-400 py-8">Cargando detalle...</p>
            </div>
        </div>
    </div>
</div>

</section>

<script>
window.addEventListener('load', function() {
    // === Estado Tabs ===
    let currentTab = 'procesar';
    let historialLoaded = false;

    const tabProcesar = document.getElementById('tabProcesar');
    const tabHistorial = document.getElementById('tabHistorial');
    const panelProcesar = document.getElementById('panelProcesar');
    const panelHistorial = document.getElementById('panelHistorial');

    function switchTab(tab) {
        currentTab = tab;
        if (tab === 'procesar') {
            panelProcesar.classList.remove('hidden');
            panelHistorial.classList.add('hidden');
            tabProcesar.classList.add('border-neon-green', 'text-navy-blue', 'bg-slate-50/50');
            tabProcesar.classList.remove('border-transparent', 'text-slate-400');
            tabHistorial.classList.add('border-transparent', 'text-slate-400');
            tabHistorial.classList.remove('border-neon-green', 'text-navy-blue', 'bg-slate-50/50');
        } else {
            panelProcesar.classList.add('hidden');
            panelHistorial.classList.remove('hidden');
            tabHistorial.classList.add('border-neon-green', 'text-navy-blue', 'bg-slate-50/50');
            tabHistorial.classList.remove('border-transparent', 'text-slate-400');
            tabProcesar.classList.add('border-transparent', 'text-slate-400');
            tabProcesar.classList.remove('border-neon-green', 'text-navy-blue', 'bg-slate-50/50');
            if (!historialLoaded) {
                historialLoaded = true;
                window.handler_devolucionesHist.reload();
            }
        }
        if (window.lucide) lucide.createIcons();
    }

    tabProcesar.addEventListener('click', () => switchTab('procesar'));
    tabHistorial.addEventListener('click', () => switchTab('historial'));

    // === Estado Tab Procesar ===
    let selectedItem = null;
    let currentFacturaId = null;
    let itemsCache = [];

    function formatFecha(f) {
        if (!f) return '-';
        const d = new Date(f);
        return d.toLocaleDateString('es-EC') + ' ' + d.toLocaleTimeString('es-EC', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function escapeHtml(s) {
        if (s == null) return '';
        return String(s).replace(/[&<>"']/g, c => ({
            '&': '&',
            '<': '<',
            '>': '>',
            '"': '"',
            "'": '&#39;'
        } [c]));
    }

    // === Tabla Facturas (Procesar) con DataTableRefactor ===
    window.handler_devolucionesFacturas = new DataTableRefactor({
        tableId: 'facturasDevTable',
        tableBodyId: 'tableBody',
        endpoint: `${URLROOT}/devoluciones/listar-facturas`,
        searchInputId: 'searchFacturasDev',
        limitSelectorId: 'limitSelector',
        paginationId: 'paginationControls',
        totalId: 'totalItemsDisplay',
        startId: 'startIndex',
        endId: 'endIndex',
        renderRow: (f) => {
            const estadoColor = {
                'COMPLETADO': 'bg-green-100 text-green-700',
                'CREDITO': 'bg-yellow-100 text-yellow-700',
                'ANULADO': 'bg-red-100 text-red-700',
                'PENDIENTE': 'bg-blue-100 text-blue-700'
            } [f.status] || 'bg-slate-100 text-slate-700';
            return `<tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-3 font-black text-navy-blue">#${String(f.id).padStart(6, '0')}</td>
                <td class="px-4 py-3 text-xs text-slate-600">${formatFecha(f.fecha)}</td>
                <td class="px-4 py-3 text-xs font-bold text-slate-700 uppercase">${escapeHtml(f.cliente)}</td>
                <td class="px-4 py-3 text-xs font-bold text-slate-600 uppercase">${escapeHtml(f.placa || '-')}</td>
                <td class="px-4 py-3 text-center"><span class="bg-navy-blue text-white px-2 py-1 rounded-lg text-[10px] font-black">${f.items_repuestos}</span></td>
                <td class="px-4 py-3 text-right font-black text-slate-700">$${parseFloat(f.total).toFixed(2)}</td>
                <td class="px-4 py-3 text-center"><span class="px-2 py-1 rounded-lg text-[10px] font-black ${estadoColor}">${f.status}</span></td>
                <td class="px-4 py-3 text-center">
                    <button onclick="abrirModalItems(${f.id})" class="bg-neon-green text-navy-blue px-3 py-1.5 rounded-lg text-[10px] font-black hover:scale-105 transition-all">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 inline"></i> Devolver
                    </button>
                </td>
            </tr>`;
        }
    });

    // === Modales ===
    window.abrirModalItems = function(facturaId) {
        currentFacturaId = facturaId;
        document.getElementById('modalFacturaId').textContent = String(facturaId).padStart(6, '0');
        const modal = document.getElementById('modalItemsDev');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        const container = document.getElementById('itemsDevContainer');
        container.innerHTML = '<p class="text-center text-slate-400 py-8">Cargando items...</p>';

        fetch(`${URLROOT}/devoluciones/get-items/${facturaId}`)
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    container.innerHTML =
                        `<p class="text-center text-red-500 py-8">${res.mensaje || res.error || 'Error'}</p>`;
                    return;
                }
                renderItems(res.items || []);
            })
            .catch(() => {
                container.innerHTML = '<p class="text-center text-red-500 py-8">Error de conexión</p>';
            });
    };

    function renderItems(items) {
        itemsCache = [];
        const container = document.getElementById('itemsDevContainer');
        if (!items.length) {
            container.innerHTML =
                '<p class="text-center text-slate-400 py-8">Esta factura no tiene repuestos devolvibles.</p>';
            return;
        }
        container.innerHTML = items.map(it => {
            const vigente = it.garantia_vigente;
            const diasRestantes = parseInt(it.dias_restantes, 10);
            const badge = vigente ?
                `<span class="bg-green-100 text-green-700 px-2 py-1 rounded-lg text-[10px] font-black">VIGENTE · ${diasRestantes}d</span>` :
                `<span class="bg-red-100 text-red-700 px-2 py-1 rounded-lg text-[10px] font-black">VENCIDA</span>`;
            const idx = itemsCache.length;
            itemsCache.push(it);
            return `<div class="border border-slate-200 rounded-xl p-4 hover:border-neon-green transition-all ${vigente ? '' : 'opacity-60'}">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <p class="font-black text-slate-800 text-sm uppercase">${escapeHtml(it.descripcion)}</p>
                        <p class="text-xs text-slate-500 mt-1">Cant: <b>${it.cantidad}</b> | Precio: $${parseFloat(it.precio_unitario).toFixed(2)}</p>
                        <p class="text-[10px] text-slate-400 mt-1">Garantía: ${it.dias_garantia_aplicado} días | Transcurridos: ${it.dias_transcurridos} | Restan: ${it.dias_restantes}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        ${badge}
                        <button onclick="abrirModalProcesar(${idx})" 
                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-[10px] font-black transition-all ${vigente ? '' : 'cursor-not-allowed opacity-50'}"
                            ${vigente ? '' : 'disabled'}>
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 inline"></i> Devolver
                        </button>
                    </div>
                </div>
            </div>`;
        }).join('');
        if (window.lucide) lucide.createIcons();
    }

    window.abrirModalProcesar = function(idx) {
        const item = itemsCache[idx];
        if (!item) return;
        selectedItem = {
            detalleId: item.detalle_id,
            item
        };
        const info = document.getElementById('infoItemDev');
        info.innerHTML = `
            <p><b class="text-slate-500">Producto:</b> ${escapeHtml(item.descripcion)}</p>
            <p><b class="text-slate-500">Cantidad:</b> ${item.cantidad}</p>
            <p><b class="text-slate-500">Precio Unit:</b> $${parseFloat(item.precio_unitario).toFixed(2)}</p>
            <p><b class="text-slate-500">Garantía:</b> ${item.dias_garantia_aplicado} días (Transcurridos: ${item.dias_transcurridos} | Restan: ${item.dias_restantes})</p>
        `;
        const alerta = document.getElementById('alertaGarantiaDev');
        if (!item.garantia_vigente) {
            alerta.className = 'p-3 rounded-lg text-xs font-bold bg-red-100 text-red-700';
            alerta.textContent = '⚠ La garantía está vencida. No se puede procesar la devolución.';
            alerta.classList.remove('hidden');
            document.getElementById('btnConfirmarDev').disabled = true;
        } else {
            alerta.className = 'p-3 rounded-lg text-xs font-bold bg-green-100 text-green-700';
            alerta.textContent = '✓ Garantía vigente (' + item.dias_restantes +
                ' días restantes). Puede procesar la devolución.';
            alerta.classList.remove('hidden');
            document.getElementById('btnConfirmarDev').disabled = false;
        }
        document.getElementById('destinoDev').value = 'STOCK';
        document.getElementById('motivoDev').value = '';
        const modal = document.getElementById('modalProcesarDev');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide) lucide.createIcons();
    };

    window.cerrarModalItems = function() {
        const m = document.getElementById('modalItemsDev');
        m.classList.add('hidden');
        m.classList.remove('flex');
    };

    window.cerrarModalProcesar = function() {
        const m = document.getElementById('modalProcesarDev');
        m.classList.add('hidden');
        m.classList.remove('flex');
        selectedItem = null;
    };

    document.getElementById('btnConfirmarDev').addEventListener('click', function() {
        if (!selectedItem) return;
        const destino = document.getElementById('destinoDev').value;
        const motivo = document.getElementById('motivoDev').value.trim();
        this.disabled = true;
        this.innerHTML =
            '<i data-lucide="loader-2" class="w-4 h-4 inline mr-1 animate-spin"></i> Procesando...';

        fetch(`${URLROOT}/devoluciones/procesar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '')
                },
                body: JSON.stringify({
                    factura_id: currentFacturaId,
                    detalle_id: selectedItem.detalleId,
                    destino: destino,
                    motivo: motivo
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    AppUtils.showToast(res.mensaje || 'Devolución procesada correctamente', 'success');
                    cerrarModalProcesar();
                    cerrarModalItems();
                    window.handler_devolucionesFacturas.reload();
                    historialLoaded = false; // invalidar historial para recarga lazy
                } else {
                    AppUtils.showToast(res.mensaje || res.error || 'Error al procesar la devolución', 'error');
                }
            })
            .catch(err => AppUtils.showToast('Error de conexión', 'error'))
            .finally(() => {
                this.disabled = false;
                this.innerHTML =
                    '<i data-lucide="rotate-ccw" class="w-4 h-4 inline mr-1"></i> Procesar Devolución';
                if (window.lucide) lucide.createIcons();
            });
    });

    // === Estado Tab Historial ===
    window.handler_devolucionesHist = new DataTableRefactor({
        tableId: 'devolucionesHist',
        tableBodyId: 'tableBodyHist',
        endpoint: `${URLROOT}/devoluciones/historial`,
        searchInputId: 'searchDevHist',
        limitSelectorId: 'limitSelectorHist',
        paginationId: 'paginationControlsHist',
        totalId: 'totalItemsDisplayHist',
        startId: 'startIndexHist',
        endId: 'endIndexHist',
        getExtraParams: () => ({
            desde: document.getElementById('devHist-desde').value,
            hasta: document.getElementById('devHist-hasta').value
        }),
        renderRow: (d) => `
            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-3 font-bold text-navy-blue text-sm">#${d.id}</td>
                <td class="px-4 py-3 text-xs text-slate-600">${formatFecha(d.fecha)}</td>
                <td class="px-4 py-3 text-xs text-slate-600">#${d.factura_id || '-'}</td>
                <td class="px-4 py-3 text-xs font-bold text-slate-700">${d.placa || '-'}</td>
                <td class="px-4 py-3 text-xs text-slate-600 max-w-[200px] truncate" title="${(d.producto_nombre || '').replace(/"/g, '')}">${d.producto_nombre || '-'}</td>
                <td class="px-4 py-3 text-center text-xs font-bold text-slate-700">${d.cantidad || 0}</td>
                <td class="px-4 py-3 text-right text-xs font-bold text-navy-blue">$${(d.monto_devuelto || 0).toLocaleString('es-CO', {minimumFractionDigits: 0})}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 rounded-lg text-[10px] font-black border ${destinoColor(d.destino)}">${d.destino || '-'}</span>
                </td>
                <td class="px-4 py-3 text-center">${garantiaBadge(d.garantia_vigente, d.dias_restantes)}</td>
                <td class="px-4 py-3 text-center">
                    <button onclick="abrirModalDetalle(${d.id})" class="px-3 py-1.5 bg-navy-blue text-white rounded-lg text-[10px] font-bold hover:bg-opacity-80 transition-all">
                        <i data-lucide="eye" class="w-3 h-3 inline"></i> Ver
                    </button>
                </td>
            </tr>
        `
    });

    // Filtros de fecha del historial: recargar al cambiar
    document.getElementById('devHist-desde').addEventListener('change', () => window.handler_devolucionesHist.reload());
    document.getElementById('devHist-hasta').addEventListener('change', () => window.handler_devolucionesHist.reload());

    function destinoColor(destino) {
        const map = {
            'STOCK': 'bg-blue-100 text-blue-700 border-blue-200',
            'PROVEEDOR': 'bg-amber-100 text-amber-700 border-amber-200',
            'DESHOJE': 'bg-red-100 text-red-700 border-red-200'
        };
        return map[destino] || 'bg-slate-100 text-slate-700 border-slate-200';
    }

    function garantiaBadge(vigente, diasRestantes) {
        if (vigente) {
            return `<span class="px-2 py-1 rounded-lg text-[10px] font-black border bg-green-100 text-green-700 border-green-200">VIGENTE (${diasRestantes}d)</span>`;
        }
        return `<span class="px-2 py-1 rounded-lg text-[10px] font-black border bg-red-100 text-red-700 border-red-200">VENCIDA</span>`;
    }

    window.abrirModalDetalle = function(id) {
        const modal = document.getElementById('modalDetalleDev');
        document.getElementById('detalleDevId').textContent = id;
        document.getElementById('detalleDevContainer').innerHTML =
            '<p class="text-center text-slate-400 py-8">Cargando detalle...</p>';
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        fetch(`${URLROOT}/devoluciones/detalle/${id}`)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    renderDetalle(res.data || {});
                } else {
                    document.getElementById('detalleDevContainer').innerHTML =
                        `<p class="text-center text-red-400 py-8">${res.error || 'Error al cargar'}</p>`;
                }
            })
            .catch(err => {
                document.getElementById('detalleDevContainer').innerHTML =
                    '<p class="text-center text-red-400 py-8">Error de conexión</p>';
            });
    };

    window.cerrarModalDetalle = function() {
        const modal = document.getElementById('modalDetalleDev');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    function renderDetalle(d) {
        const container = document.getElementById('detalleDevContainer');
        const destinoClass = destinoColor(d.destino);
        container.innerHTML = `
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Devolución</p>
                    <p class="text-lg font-black text-navy-blue">#${d.id || '-'}</p>
                    <p class="text-xs text-slate-500">${formatFecha(d.fecha) || '-'}</p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Factura</p>
                    <p class="text-lg font-black text-navy-blue">#${d.factura_id || '-'}</p>
                    <p class="text-xs text-slate-500">${d.fecha_factura || '-'}</p>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-4">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Producto</p>
                <p class="text-sm font-bold text-slate-700">${d.producto_nombre || '-'}</p>
                <p class="text-xs text-slate-500">Código: ${d.producto_codigo || '-'}</p>
            </div>
            <div class="grid grid-cols-3 gap-4 border-t border-slate-100 pt-4">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Cantidad</p>
                    <p class="text-sm font-bold text-slate-700">${d.cantidad || 0}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Monto Total</p>
                    <p class="text-sm font-bold text-navy-blue">$${(d.monto_devuelto || 0).toLocaleString('es-CO', {minimumFractionDigits: 0})}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Destino</p>
                    <span class="px-2 py-1 rounded-lg text-[10px] font-black border ${destinoClass}">${d.destino || '-'}</span>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-4">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Garantía</p>
                ${garantiaBadge(d.garantia_vigente, d.dias_restantes)}
                <p class="text-xs text-slate-500 mt-1">Días transcurridos: ${d.dias_transcurridos ?? '-'} / Aplicado: ${d.dias_garantia_aplicado ?? '-'}</p>
            </div>
            <div class="border-t border-slate-100 pt-4">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Motivo</p>
                <p class="text-sm text-slate-700">${d.motivo || '-'}</p>
            </div>
            <div class="border-t border-slate-100 pt-4 grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Procesado por</p>
                    <p class="text-sm font-bold text-slate-700">${d.usuario_nombre || '-'}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Cliente</p>
                    <p class="text-sm font-bold text-slate-700">${d.cliente || '-'}</p>
                </div>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
    }

    // Init
    window.handler_devolucionesFacturas.reload();
    if (window.lucide) lucide.createIcons();
});
</script>