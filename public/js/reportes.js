document.addEventListener('DOMContentLoaded', () => {
    cargarReporte(); // Carga el resumen por defecto

    // Vincular buscador de auditoría
    document.getElementById('search-audit')?.addEventListener('input', (e) => filtrarAuditoria(e.target.value));

    // Vincular buscador de flujo de caja
    document.getElementById('search-report')?.addEventListener('input', (e) => filtrarReporte(e.target.value));
});

let activeReportTab = 'resumen';

window.switchReportTab = (tab) => {
    activeReportTab = tab;
    const secResumen = document.getElementById('sec-resumen');
    const secDetallado = document.getElementById('sec-detallado');
    const secDevoluciones = document.getElementById('sec-devoluciones');
    const secCartera = document.getElementById('sec-cartera');
    const secRentabilidad = document.getElementById('sec-rentabilidad');

    const tabResumen = document.getElementById('tab-resumen');
    const tabDetallado = document.getElementById('tab-detallado');
    const tabDevoluciones = document.getElementById('tab-devoluciones');
    const tabCartera = document.getElementById('tab-cartera');
    const tabRentabilidad = document.getElementById('tab-rentabilidad');

    // Ocultar todas las secciones
    if (secResumen) secResumen.classList.add('hidden');
    if (secDetallado) secDetallado.classList.add('hidden');
    if (secDevoluciones) secDevoluciones.classList.add('hidden');
    if (secCartera) secCartera.classList.add('hidden');
    if (secRentabilidad) secRentabilidad.classList.add('hidden');

    // Resetear estilos de pestañas
    [tabResumen, tabDetallado, tabDevoluciones, tabCartera, tabRentabilidad].forEach(t => {
        if (t) {
            t.classList.remove('border-neon-green', 'text-navy-blue');
            t.classList.add('border-transparent', 'text-slate-400');
        }
    });

    if (tab === 'resumen') {
        if (secResumen) secResumen.classList.remove('hidden');
        if (tabResumen) tabResumen.classList.add('border-neon-green', 'text-navy-blue');
        cargarReporte();
    } else if (tab === 'detallado') {
        if (secDetallado) secDetallado.classList.remove('hidden');
        if (tabDetallado) tabDetallado.classList.add('border-neon-green', 'text-navy-blue');
        cargarReporteDetallado();
    } else if (tab === 'devoluciones') {
        if (secDevoluciones) secDevoluciones.classList.remove('hidden');
        if (tabDevoluciones) tabDevoluciones.classList.add('border-neon-green', 'text-navy-blue');
        cargarHistorialDevoluciones();
    } else if (tab === 'cartera') {
        if (secCartera) secCartera.classList.remove('hidden');
        if (tabCartera) tabCartera.classList.add('border-neon-green', 'text-navy-blue');
        cargarCartera();
    } else if (tab === 'rentabilidad') {
        if (secRentabilidad) secRentabilidad.classList.remove('hidden');
        if (tabRentabilidad) tabRentabilidad.classList.add('border-neon-green', 'text-navy-blue');
        cargarRentabilidad();
    }

    lucide.createIcons();
};

let rawAuditData = null; // Para filtrar sin volver al servidor
let rawReportData = []; // Datos del flujo de caja
let filteredReportData = []; // Datos filtrados para el flujo de caja
let state = {
    page: 1,
    limit: 10,
    total: 0,
    filtered: 0
};

window.cargarReporte = async function () {
    const desde = document.getElementById('rep-desde').value;
    const hasta = document.getElementById('rep-hasta').value;
    const tbody = document.getElementById('report-body');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center py-16 text-slate-400 italic animate-pulse">GENERANDO BALANCE...</td></tr>';

    try {
        const res = await fetch(`${URLROOT}/reportes/generar?desde=${desde}&hasta=${hasta}`);

        const data = await res.json();

        // Actualizar Totales
        document.getElementById('total-repuestos').textContent = AppUtils.formatCurrency(data.totales.ingreso_repuestos || 0);
        document.getElementById('total-servicios').textContent = AppUtils.formatCurrency(data.totales.ingreso_servicios || 0);
        document.getElementById('total-egresos').textContent = AppUtils.formatCurrency(data.totales.egresos || 0);
        document.getElementById('total-deuda').textContent = AppUtils.formatCurrency(data.totales.deuda);
        document.getElementById('total-balance').textContent = AppUtils.formatCurrency(data.totales.balance);

        rawReportData = data.movimientos || [];
        filteredReportData = [...rawReportData];
        state.total = rawReportData.length;
        state.filtered = filteredReportData.length;
        state.page = 1; // Reiniciar página al cargar nuevas fechas

        // Recargar la tabla activa para que los filtros de fecha surtan efecto de inmediato
        if (activeReportTab === 'resumen') renderReportTable();
        else if (activeReportTab === 'detallado') cargarReporteDetallado();
        else if (activeReportTab === 'devoluciones') cargarHistorialDevoluciones();
        else if (activeReportTab === 'cartera') cargarCartera();
        else if (activeReportTab === 'rentabilidad') cargarRentabilidad();

    } catch (e) {
        console.error("Error cargando reporte:", e);
        AppUtils.showToast("Error al generar el reporte", "error");
    }
}

const formatDateLong = (dateStr) => {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return 'Fecha inválida';
    const day = d.getDate();
    const meses = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
    const month = meses[d.getMonth()];
    const year = d.getFullYear();
    return `${day} de ${month} de ${year}.`;
};

function renderReportTable() {
    const tbody = document.getElementById('report-body');
    if (!tbody) return;

    const start = (state.page - 1) * state.limit;
    const paginatedItems = filteredReportData.slice(start, start + state.limit);

    if (filteredReportData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-8 py-16 text-center text-slate-400 italic font-medium uppercase tracking-widest">
                    <div class="flex flex-col items-center gap-2">
                        <i data-lucide="info" class="w-8 h-8 text-slate-300"></i>
                        <span>No hay registros de movimientos en este periodo</span>
                    </div>
                </td>
            </tr>`;
        renderReportControls();
        if (window.lucide) lucide.createIcons();
        return;
    }

    tbody.innerHTML = paginatedItems.map(m => {
        const isVenta = (m.tipo === 'VENTA');
        const isProveedor = (!!m.proveedor_nombre || m.tipo === 'COMPRA');

        let descriptionContent = '';

        if (isVenta) {
            descriptionContent = `
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-black text-navy-blue uppercase tracking-tight">${m.modelo_vehiculo || 'VEHÍCULO GENERAL'}</span>
                        <span class="text-xs text-slate-400 font-mono tracking-tighter border px-1.5 rounded bg-slate-50 border-slate-100">${m.placa || 'SIN PLACA'}</span>
                    </div>
                    <div class="text-xs text-slate-500 font-bold uppercase leading-tight">Cliente: ${m.cliente_nombre || m.entidad || 'VENTA RÁPIDA'}</div>
                    <div class="flex items-center gap-1.5 mt-0.5 text-[11px] font-black text-blue-600 uppercase">
                        <i data-lucide="package" class="w-3 h-3"></i> ${m.cantidad_items || m.total_productos || 0} SERVICIOS/ARTÍCULOS
                    </div>
                </div>`;
        } else if (isProveedor) {
            descriptionContent = `
                <div class="flex flex-col gap-1">
                    <div class="text-sm font-black text-slate-700 uppercase tracking-tight">PAGO / ABONO PROVEEDOR</div>
                    <div class="text-xs text-navy-blue font-bold uppercase leading-tight">Proveedor: ${m.proveedor_nombre || m.entidad}</div>
                    ${(m.saldo_pendiente > 0) ? `<div class="text-[9px] bg-rose-50 text-rose-600 px-2 py-0.5 rounded-full font-black border border-rose-100 italic w-fit mt-1">DEUDA: ${AppUtils.formatCurrency(m.saldo_pendiente)}</div>` : ''}
                </div>`;
        } else {
            descriptionContent = `
                <div class="flex flex-col gap-1">
                    <div class="text-sm font-black text-slate-700 uppercase tracking-tight">${m.descripcion || 'GASTO OPERATIVO'}</div>
                    <div class="text-xs text-slate-400 font-bold uppercase leading-tight">${m.categoria || 'GENERAL'}</div>
                </div>`;
        }

        return `
            <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors group">
                <td class="px-4 py-5 font-mono text-sm text-slate-400 align-middle">#${m.id_db || m.id}</td>
                <td class="px-4 py-5 text-xs font-bold text-slate-500 uppercase tracking-tighter whitespace-nowrap align-middle">${formatDateLong(m.fecha)}</td>
                <td class="px-4 py-5 text-center align-middle">
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase ${m.tipo === 'VENTA' ? 'bg-blue-100 text-blue-600' : 'bg-rose-100 text-rose-600'}">
                        ${m.tipo}
                    </span>
                </td>
                <td class="px-4 py-5">${descriptionContent}</td>
                <td class="px-4 py-5 text-right font-black text-base ${m.tipo === 'VENTA' ? 'text-blue-600' : 'text-rose-600'} align-middle">
                    ${m.tipo === 'VENTA' ? '+' : '-'} ${AppUtils.formatCurrency(m.monto)}
                </td>
                <td class="px-4 py-5 text-right align-middle">
                    <div class="flex items-center justify-end gap-2">
                        ${isProveedor ? `
                            <button onclick="verDetalleCompra(${m.id})" class="p-2 bg-slate-100 text-slate-400 hover:text-rose-600 rounded-xl transition-colors shadow-sm" title="Ver Detalle de Compra">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>` : isVenta ? `
                            <button onclick="verDetalleVenta(${m.id})" class="p-2 bg-slate-100 text-slate-400 hover:text-blue-600 rounded-xl transition-colors shadow-sm" title="Ver Detalle de Venta">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button onclick="iniciarDevolucion(${m.id}, '${m.fecha}')" class="p-2 bg-slate-100 text-slate-400 hover:text-rose-600 rounded-xl transition-colors shadow-sm ml-1" title="Devolución">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            </button>` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    renderReportControls();
    if (window.lucide) lucide.createIcons();
}

function renderReportControls() {
    const table = document.getElementById('reportTable');
    if (!table) return;
    const wrapper = table.closest('div');

    let bottom = document.getElementById('custom-bottom-controls');
    if (!bottom) {
        bottom = document.createElement('div');
        bottom.id = 'custom-bottom-controls';
        bottom.className = 'flex flex-col sm:flex-row justify-between items-center gap-6 mt-6 px-8 py-5 bg-white/50 rounded-3xl border border-slate-100 shadow-sm mx-2 animate-in fade-in slide-in-from-bottom-2 duration-500';
        wrapper.parentNode.insertBefore(bottom, wrapper.nextSibling);
    }

    const start = state.filtered === 0 ? 0 : (state.page - 1) * state.limit + 1;
    const end = Math.min(state.page * state.limit, state.filtered);
    const totalPages = Math.ceil(state.filtered / state.limit) || 1;

    bottom.innerHTML = `
        <div class="flex items-center gap-3">
            <div class="w-2.5 h-2.5 rounded-full bg-neon-green animate-pulse shadow-[0_0_8px_rgba(57,255,20,0.5)]"></div>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">
                Mostrando <span class="text-navy-blue text-xs ml-1">${start}-${end}</span> <span class="text-slate-300 mx-2 text-lg font-thin">|</span> Total <span class="text-navy-blue text-xs ml-1">${state.filtered}</span>
            </span>
        </div>
        <div class="flex items-center gap-1.5">
            <button onclick="window.changeReportPage(${state.page - 1})" ${state.page === 1 ? 'disabled' : ''} 
                class="flex items-center justify-center w-10 h-10 rounded-2xl transition-all ${state.page === 1 ? 'text-slate-300 bg-slate-50 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-500 hover:bg-navy-blue hover:text-neon-green hover:border-navy-blue shadow-sm cursor-pointer'}">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
            <button onclick="window.changeReportPage(${state.page + 1})" ${state.page >= totalPages ? 'disabled' : ''} 
                class="flex items-center justify-center w-10 h-10 rounded-2xl transition-all ${state.page >= totalPages ? 'text-slate-300 bg-slate-50 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-500 hover:bg-navy-blue hover:text-neon-green hover:border-navy-blue shadow-sm cursor-pointer'}">
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>
        </div>
    `;
    if (window.lucide) lucide.createIcons();
}

window.changeReportPage = (p) => {
    if (p > 0 && p <= Math.ceil(state.filtered / state.limit)) {
        state.page = p;
        renderReportTable();
    }
};

window.cargarReporteDetallado = async function () {
    const desde = document.getElementById('rep-desde').value;
    const hasta = document.getElementById('rep-hasta').value;

    try {
        const res = await fetch(`${URLROOT}/reportes/detallado?desde=${desde}&hasta=${hasta}`);
        const data = await res.json();
        rawAuditData = data; // Guardamos para el buscador

        // Solo mostramos los trabajos realizados (VENTAS) en esta pestaña
        const auditItems = (data.ventas || []).map(v => ({ ...v, tipo: 'VENTA' }));

        renderAuditoriaLista(auditItems);
    } catch (e) {
        console.error("Error cargando reporte detallado:", e);
        AppUtils.showToast("Error al generar auditoría", "error");
    }
}

function renderAuditoriaLista(items) {
    const container = document.getElementById('audit-list-container');
    if (!container) return;

    // 1. Agrupar por Mes y Año para los encabezados sticky
    const meses = ["ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO", "JULIO", "AGOSTO", "SEPTIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE"];

    const groupedByMonth = items.reduce((acc, current) => {
        const d = new Date(current.fecha);
        const monthKey = `${meses[d.getMonth()]} ${d.getFullYear()}`;
        if (!acc[monthKey]) acc[monthKey] = [];
        acc[monthKey].push(current);
        return acc;
    }, {});

    if (Object.keys(groupedByMonth).length === 0) {
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-20 text-slate-400 italic font-medium uppercase tracking-widest gap-2">
                <i data-lucide="info" class="w-10 h-10 text-slate-200"></i>
                <span>No hay registros de trabajos en este periodo</span>
            </div>`;
        if (window.lucide) lucide.createIcons();
        return;
    }

    let html = '';
    let debtorsSummary = {}; // Para agrupar deudores por cliente

    for (const [month, monthItems] of Object.entries(groupedByMonth)) {
        // 2. Agrupar por Factura dentro del mes
        const invoices = monthItems.reduce((acc, current) => {
            const key = `V-${current.id}`;
            if (!acc[key]) {
                acc[key] = {
                    id: current.id,
                    fecha: current.fecha,
                    vehiculo: current.modelo_vehiculo || 'GENERAL',
                    placa: current.placa || '---',
                    cliente: current.cliente_nombre || 'VENTA RÁPIDA',
                    usuario: current.usuario_nombre || 'N/A',
                    iva: parseFloat(current.iva_monto || 0),
                    subtotal: parseFloat(current.subtotal || 0),
                    total: parseFloat(current.total || 0),
                    status: current.status,
                    pago_efectivo: parseFloat(current.pago_efectivo || 0),
                    pago_transferencia: parseFloat(current.pago_transferencia || 0),
                    saldo_pendiente: parseFloat(current.saldo_pendiente || 0), // <-- Asegurarse de que este campo venga del backend
                    items: []
                };
            }
            acc[key].items.push(current);
            return acc;
        }, {});

        const totalInvoices = Object.keys(invoices).length;

        // Renderizar Encabezado del Mes (Sticky) con el conteo real de órdenes
        html += `
            <div class="sticky top-0 z-20 bg-slate-50/95 backdrop-blur-md py-4 px-6 border-b border-slate-200 flex justify-between items-center shadow-sm mb-4">
                <h3 class="font-black text-navy-blue text-base uppercase tracking-[0.2em] flex items-center gap-3">
                    <i data-lucide="calendar" class="w-4 h-4 text-neon-green"></i>
                    ${month}
                </h3>
                <span class="text-xs font-black text-slate-400 bg-white border border-slate-100 px-3 py-1 rounded-full uppercase">
                    ${totalInvoices} TRABAJOS REGISTRADOS
                </span>
            </div>
        `;

        html += Object.values(invoices).map(f => {
            // Asegurar el total: usar el del servidor o calcularlo si viene en 0
            const totalFactura = f.total > 0 ? f.total : f.items.reduce((sum, item) => sum + (item.cantidad * item.precio_unitario), 0);

            // Detección robusta: Si el saldo pendiente es > 0 O si la suma de pagos es menor al total
            const isCredit = f.saldo_pendiente > 0 || (totalFactura > (f.pago_efectivo + f.pago_transferencia) + 0.01);

            if (isCredit) {
                if (!debtorsSummary[f.cliente]) {
                    debtorsSummary[f.cliente] = { total: 0, count: 0 };
                }
                debtorsSummary[f.cliente].total += f.saldo_pendiente;
                debtorsSummary[f.cliente].count++;
            }

            return `
            <div class="border-b border-slate-100 py-8 last:border-0 group animate-in fade-in slide-in-from-bottom-2 duration-300 ${isCredit ? 'bg-rose-50/30 -mx-6 px-6 border-l-4 border-l-rose-500' : ''}">
                <!-- Cabecera de Entrada (Libro Contable) -->
                <div class="flex flex-wrap justify-between items-start gap-6 mb-5">
                    <div class="flex items-center gap-6">
                        <div class="h-14 w-14 rounded-2xl ${isCredit ? 'bg-amber-500 text-white' : 'bg-navy-blue text-neon-green'} flex flex-col items-center justify-center shadow-lg shadow-navy-blue/10">
                            <span class="text-xs font-black uppercase opacity-60 leading-none mb-0.5">ORD</span>
                            <span class="text-lg font-black tracking-tighter leading-none">#${f.id}</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <h4 class="font-black text-navy-blue uppercase text-lg tracking-tight">${f.vehiculo}</h4>
                                <span class="bg-slate-50 border border-slate-200 text-slate-500 font-mono text-sm px-2 py-0.5 rounded font-black">${f.placa}</span>
                            </div>
                            <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">
                                <span class="text-slate-600">${f.cliente}</span> 
                                <span class="text-slate-200 mx-2">|</span> 
                                ${new Date(f.fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' })}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-10">
                        <div class="text-right">
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1 leading-none">Atendió: <span class="text-slate-600">${f.usuario}</span></p>
                            <div class="flex items-center justify-end gap-3">
                                <span class="text-sm font-black text-slate-300 uppercase tracking-tighter">${isCredit ? 'SALDO DEUDOR' : 'TOTAL TRABAJO'}</span>
                                <span class="text-3xl font-black ${isCredit ? 'text-rose-600' : 'text-emerald-600'} tracking-tighter">${AppUtils.formatCurrency(isCredit ? f.saldo_pendiente : totalFactura)}</span>
                                ${isCredit ? `<span class="text-[10px] font-black bg-rose-100 text-rose-600 px-2 py-0.5 rounded-full uppercase tracking-tighter border border-rose-200">En Crédito</span>` : ''}
                            </div>
                        </div>
                        ${isCredit ? `
                            <button onclick="registrarAbonoCliente(${f.id}, ${f.saldo_pendiente})" class="p-3 rounded-xl bg-rose-500 text-white hover:bg-rose-600 transition-all shadow-md flex items-center gap-2 group/btn" title="Registrar Pago">
                                <i data-lucide="hand-coins" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                <span class="text-[10px] font-black uppercase">Abonar</span>
                            </button>
                        ` : ''}
                        <button onclick="verDetalleVenta(${f.id})" class="p-3 rounded-xl bg-white border border-slate-100 text-slate-400 hover:text-navy-blue hover:border-navy-blue hover:bg-slate-50 transition-all shadow-sm">
                            <i data-lucide="maximize-2" class="w-4 h-4"></i>
                        </button>
                        <button onclick="iniciarDevolucion(${f.id}, '${f.fecha}')" class="p-3 rounded-xl bg-white border border-slate-100 text-slate-400 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm ml-2" title="Devolución">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Desglose de Servicios/Repuestos (Formato Ledger) -->
                <div class="pl-[80px]">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-50">
                                <th class="pb-2 text-sm font-black text-slate-300 uppercase tracking-widest">Cant.</th>
                                <th class="pb-2 text-sm font-black text-slate-300 uppercase tracking-widest">Descripción detallada</th>
                                <th class="pb-2 text-sm font-black text-slate-300 uppercase tracking-widest text-right">P. Unitario</th>
                                <th class="pb-2 text-sm font-black text-slate-300 uppercase tracking-widest text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            ${f.items.map(i => `
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3 text-base font-bold text-slate-400">${i.cantidad}</td>
                                    <td class="py-3">
                                        <span class="text-base font-bold text-slate-700 uppercase tracking-tight">${i.descripcion}</span>
                                    </td>
                                    <td class="py-3 text-right text-base font-medium text-slate-500">${AppUtils.formatCurrency(i.precio_unitario)}</td>
                                    <td class="py-3 text-right text-sm font-black text-slate-600">${AppUtils.formatCurrency(i.cantidad * i.precio_unitario)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            `;
        }).join('');
    }

    // Renderizar tarjeta de deudores
    const debtorsContainer = document.getElementById('debtors-summary-container');
    if (debtorsContainer) {
        const debtorsArray = Object.entries(debtorsSummary).map(([cliente, data]) => ({ cliente, ...data }));
        if (debtorsArray.length > 0) {
            debtorsContainer.innerHTML = `
                <div class="glass-card p-6 rounded-xl border-l-4 border-rose-500 shadow-sm mb-8 animate-in fade-in slide-in-from-top-2 duration-500">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-black text-rose-600 uppercase flex items-center gap-2">
                            <i data-lucide="user-x" class="w-5 h-5"></i> Clientes con Crédito
                        </h3>
                        <span class="text-xs font-black text-slate-400 bg-white border border-slate-100 px-3 py-1 rounded-full uppercase">
                            ${debtorsArray.length} DEUDORES
                        </span>
                    </div>
                    <div class="space-y-3">
                        ${debtorsArray.map(d => `
                            <div class="flex justify-between items-center border-b border-rose-50/50 pb-2 last:border-0">
                                <p class="text-sm font-bold text-slate-700">${d.cliente}</p>
                                <span class="text-base font-black text-rose-600">${AppUtils.formatCurrency(d.total)}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            lucide.createIcons();
            debtorsContainer.classList.remove('hidden');
        } else {
            debtorsContainer.classList.add('hidden');
        }
    }
    container.innerHTML = html;
    if (window.lucide) lucide.createIcons();
}

/**
 * Muestra el modal detallado de una venta (Vista previa similar a historial)
 */
window.verDetalleVenta = async (ventaId) => {
    try {
        const res = await fetch(`${URLROOT}/historial/detalle/${ventaId}`);
        const venta = await res.json();

        if (!venta) return AppUtils.showToast('No se encontró el detalle', 'error');

        Swal.fire({
            title: `<span class="text-[10px] uppercase text-slate-400 font-black tracking-widest">Vista Previa Operación</span><br><span class="text-navy-blue">FACTURA #${venta.id}</span>`,
            html: `
                <div class="text-left space-y-6 pt-4">
                    <div class="grid grid-cols-2 gap-6 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-slate-400 uppercase">Fecha Realizada</p>
                            <p class="text-xs font-bold text-slate-700">${new Date(venta.fecha).toLocaleString('es-CO')}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-slate-400 uppercase">Atendido Por</p>
                            <p class="text-xs font-bold text-slate-700">${venta.usuario_nombre || 'SISTEMA'} <span class="text-[9px] text-slate-400">(${venta.usuario_cargo || 'N/A'})</span></p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-slate-400 uppercase">Propietario</p>
                            <p class="text-xs font-bold text-slate-700">${venta.cliente_nombre || 'VENTA RÁPIDA'} ${venta.cliente_telefono ? `<br><span class="text-[9px] text-slate-400">TEL: ${venta.cliente_telefono}</span>` : ''}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-slate-400 uppercase">Vehículo Identificado</p>
                            <p class="text-xs font-bold text-slate-700 uppercase">${venta.modelo_vehiculo || 'N/A'} <span class="text-blue-500 font-mono">[${venta.placa || '---'}]</span></p>
                        </div>
                    </div>

                    <div class="max-h-60 overflow-y-auto border border-slate-200 rounded-lg p-2 bg-white shadow-inner">
                        <table class="w-full text-[11px] border-collapse">
                            <thead>
                                <tr class="text-slate-400 border-b">
                                    <th class="text-left p-2 uppercase tracking-tighter">Descripción</th>
                                    <th class="text-center p-2 uppercase tracking-tighter">Cant.</th>
                                    <th class="text-right p-2 uppercase tracking-tighter">P. Unit.</th>
                                    <th class="text-right p-2 uppercase tracking-tighter">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                ${venta.items.map(i => `
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-2 text-slate-700 font-medium uppercase">${i.descripcion}</td>
                                        <td class="p-2 text-center font-bold text-slate-500">${i.cantidad}</td>
                                        <td class="p-2 text-right text-slate-500">${AppUtils.formatCurrency(i.precio_unitario)}</td>
                                        <td class="p-2 text-right font-black text-navy-blue">${AppUtils.formatCurrency(i.cantidad * i.precio_unitario)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-[10px]">
                        <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                            <p class="text-slate-400 font-bold uppercase mb-1">Efectivo</p>
                            <p class="font-black text-slate-700 text-sm">${AppUtils.formatCurrency(venta.pago_efectivo)}</p>
                        </div>
                        <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                            <p class="text-slate-400 font-bold uppercase mb-1">Transf.</p>
                            <p class="font-black text-slate-700 text-sm">${AppUtils.formatCurrency(venta.pago_transferencia)}</p>
                        </div>
                        <div class="p-2 ${venta.saldo_pendiente > 0 ? 'bg-rose-50 border-rose-100' : 'bg-slate-50 border-slate-100'} rounded-xl border">
                            <p class="${venta.saldo_pendiente > 0 ? 'text-rose-400' : 'text-slate-400'} font-bold uppercase mb-1">Deuda</p>
                            <p class="font-black ${venta.saldo_pendiente > 0 ? 'text-rose-600' : 'text-slate-700'} text-sm">${AppUtils.formatCurrency(venta.saldo_pendiente)}</p>
                        </div>
                    </div>

                    <div class="bg-navy-blue p-5 rounded-2xl space-y-3 text-white">
                        <div class="flex justify-between items-center text-xs opacity-70">
                            <span class="font-bold uppercase">Subtotal Neto</span>
                            <span class="font-bold">${AppUtils.formatCurrency(venta.subtotal)}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs opacity-70">
                            <span class="font-bold uppercase">Impuestos (IVA)</span>
                            <span class="font-bold">${AppUtils.formatCurrency(venta.iva_monto)}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs text-emerald-400 pt-1 border-t border-white/5">
                            <span class="font-bold uppercase">Total Abonado</span>
                            <span class="font-bold">${AppUtils.formatCurrency(parseFloat(venta.pago_efectivo) + parseFloat(venta.pago_transferencia))}</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-white/10">
                            <span class="font-black uppercase text-xs tracking-widest text-neon-green">Total de la Venta</span>
                            <span class="text-2xl font-black">${AppUtils.formatCurrency(venta.total)}</span>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <button onclick="window.printVenta(${venta.id})" class="flex-1 py-4 bg-slate-100 text-slate-600 rounded-xl font-black text-[10px] uppercase flex items-center justify-center gap-2 hover:bg-slate-200 transition-all">
                            <i data-lucide="printer" class="w-4 h-4"></i> Imprimir Comprobante
                        </button>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'Cerrar Detalle',
            width: '500px',
            didOpen: () => lucide.createIcons()
        });
    } catch (e) {
        console.error(e);
        AppUtils.showToast('Error al conectar con el servidor', 'error');
    }
};

/**
 * Muestra el detalle de una compra/gasto (Vista previa financiera)
 */
window.verDetalleCompra = async (id) => {
    try {
        const res = await fetch(`${URLROOT}/proveedores/obtenerDetalleCompra/${id}`);
        const data = await res.json();

        if (!data) return AppUtils.showToast('Detalle de ingreso no disponible', 'error');

        Swal.fire({
            title: `<span class="text-[10px] uppercase text-slate-400 font-black tracking-widest">Vista Previa Egreso</span><br><span class="text-rose-600">COMPRA #${data.id}</span>`,
            html: `
                <div class="text-left space-y-6 pt-4">
                    <div class="grid grid-cols-2 gap-6 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-slate-400 uppercase">Fecha Registro</p>
                            <p class="text-xs font-bold text-slate-700">${new Date(data.fecha).toLocaleString('es-CO')}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-slate-400 uppercase">Registrado Por</p>
                            <p class="text-xs font-bold text-slate-700">${data.usuario_nombre || 'SISTEMA'}</p>
                        </div>
                        <div class="space-y-1 col-span-2">
                            <p class="text-[9px] font-black text-slate-400 uppercase">Proveedor</p>
                            <p class="text-xs font-bold text-slate-700 uppercase">${data.proveedor_nombre} <span class="text-slate-400 font-mono text-[10px] ml-2">${data.proveedor_telefono || ''}</span></p>
                        </div>
                    </div>

                    <div class="max-h-60 overflow-y-auto border border-slate-200 rounded-lg p-2 bg-white shadow-inner">
                        <table class="w-full text-[11px] border-collapse">
                            <thead>
                                <tr class="text-slate-400 border-b">
                                    <th class="text-left p-2 uppercase tracking-tighter">Descripción</th>
                                    <th class="text-center p-2 uppercase tracking-tighter">Cant.</th>
                                    <th class="text-right p-2 uppercase tracking-tighter">Costo Unit.</th>
                                    <th class="text-right p-2 uppercase tracking-tighter">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                ${data.items.map(i => `
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-2 text-slate-700 font-medium uppercase">${i.descripcion || i.producto_nombre}</td>
                                        <td class="p-2 text-center font-bold text-slate-500">${i.cantidad}</td>
                                        <td class="p-2 text-right text-slate-500">${AppUtils.formatCurrency(i.costo_unitario)}</td>
                                        <td class="p-2 text-right font-black text-rose-600">${AppUtils.formatCurrency(i.cantidad * i.costo_unitario)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-navy-blue p-5 rounded-2xl space-y-3 text-white">
                        <div class="flex justify-between items-center text-xs opacity-70">
                            <span class="font-bold uppercase">Total Facturado</span>
                            <span class="font-bold">${AppUtils.formatCurrency(data.total)}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs text-emerald-400">
                            <span class="font-bold uppercase">Total Abonado</span>
                            <span class="font-bold">${AppUtils.formatCurrency(data.pagado)}</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-white/10">
                            <span class="font-black uppercase text-xs tracking-widest text-rose-400">Saldo Pendiente</span>
                            <span class="text-2xl font-black">${AppUtils.formatCurrency(data.total - data.pagado)}</span>
                        </div>
                    </div>
                    
                    ${data.fecha_vencimiento ? `
                        <div class="flex items-center justify-center gap-2 p-3 bg-rose-50 rounded-xl text-[10px] text-rose-600 font-bold uppercase border border-rose-100">
                            <i data-lucide="calendar" class="w-3 h-3"></i>
                            Fecha de Cobro: ${new Date(data.fecha_vencimiento).toLocaleDateString()}
                        </div>
                    ` : ''}
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'Cerrar Detalle',
            width: '500px',
            didOpen: () => lucide.createIcons()
        });
    } catch (e) { console.error(e); }
};

/**
 * Abre el endpoint de impresión
 */
window.printVenta = (id) => {
    AppUtils.showToast('Generando documento...', 'info');
    window.open(`${URLROOT}/facturacion/imprimir/${id}`, '_blank');
};

/**
 * Abre el modal para registrar un abono a una deuda
 */
window.registrarAbonoCliente = async (ventaId, saldoPendiente) => {
    const { value: formValues } = await Swal.fire({
        title: `<span class="text-xs uppercase text-slate-400 font-black">Registrar Pago</span><br>ORDEN #${ventaId}`,
        html: `
            <div class="text-left space-y-4 pt-4">
                <div class="p-3 bg-rose-50 rounded-xl border border-rose-100 flex justify-between items-center">
                    <span class="text-[10px] font-black text-rose-600 uppercase">Saldo Actual:</span>
                    <span class="text-lg font-black text-rose-600">${AppUtils.formatCurrency(saldoPendiente)}</span>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Monto a Pagar</label>
                    <input id="pay-amount" type="text" class="w-full p-3 bg-slate-50 border rounded-xl font-black text-navy-blue" value="${saldoPendiente.toFixed(2)}">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Método de Pago</label>
                    <select id="pay-method" class="w-full p-3 bg-slate-50 border rounded-xl font-bold text-sm">
                        <option value="EFECTIVO">EFECTIVO</option>
                        <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                    </select>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'CONFIRMAR PAGO',
        confirmButtonColor: '#10b981',
        preConfirm: () => {
            const monto = parseFloat(document.getElementById('pay-amount').value.replace(',', '.'));
            if (isNaN(monto) || monto <= 0 || monto > (saldoPendiente + 0.01)) {
                Swal.showValidationMessage('Monto inválido o superior a la deuda');
                return false;
            }
            return {
                venta_id: ventaId,
                monto: monto,
                metodo: document.getElementById('pay-method').value
            };
        }
    });

    if (formValues) {
        try {
            const res = await fetch(`${URLROOT}/facturacion/registrarAbono`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formValues)
            });
            const data = await res.json();
            if (data.success) {
                AppUtils.showToast('Pago registrado correctamente');
                cargarReporteDetallado(); // Recargar la lista
            } else {
                AppUtils.showToast(data.mensaje, 'error');
            }
        } catch (e) { AppUtils.showToast('Error de conexión', 'error'); }
    }
};

function filtrarAuditoria(term) {
    if (!rawAuditData) return;
    const t = term.toLowerCase();

    // Filtrar sobre los trabajos realizados (Ventas)
    const filtrados = (rawAuditData.ventas || []).filter(v =>
        (v.modelo_vehiculo && v.modelo_vehiculo.toLowerCase().includes(t)) ||
        (v.placa && v.placa.toLowerCase().includes(t)) ||
        (v.descripcion && v.descripcion.toLowerCase().includes(t)) ||
        (v.cliente_nombre && v.cliente_nombre.toLowerCase().includes(t)) ||
        (String(v.id).includes(t))
    ).map(v => ({ ...v, tipo: 'VENTA' }));

    renderAuditoriaLista(filtrados);
}

/**
 * Filtra los datos del Flujo de Caja (Resumen) en tiempo real
 */
function filtrarReporte(term) {
    const t = term.toLowerCase();

    filteredReportData = rawReportData.filter(m =>
        (m.descripcion && m.descripcion.toLowerCase().includes(t)) ||
        (m.entidad && m.entidad.toLowerCase().includes(t)) ||
        (m.proveedor_nombre && m.proveedor_nombre.toLowerCase().includes(t)) ||
        (m.cliente_nombre && m.cliente_nombre.toLowerCase().includes(t)) ||
        (m.placa && m.placa.toLowerCase().includes(t)) ||
        (m.modelo_vehiculo && m.modelo_vehiculo.toLowerCase().includes(t)) ||
        (m.categoria && m.categoria.toLowerCase().includes(t)) ||
        (String(m.id_db || m.id).includes(t))
    );

    state.filtered = filteredReportData.length;
    state.page = 1;
    renderReportTable();
}

/**
 * Carga y renderiza el historial de devoluciones
 */
async function cargarHistorialDevoluciones() {
    const desde = document.getElementById('rep-desde').value;
    const hasta = document.getElementById('rep-hasta').value;
    const container = document.getElementById('devoluciones-list-container'); // Asumimos que existe este contenedor
    if (!container) return;

    try {
        const res = await fetch(`${URLROOT}/facturacion/listarDevoluciones?desde=${desde}&hasta=${hasta}`);
        const result = await res.json();

        if (!result.data || result.data.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center py-16 text-slate-400 italic font-medium uppercase tracking-widest gap-2">
                    <i data-lucide="rotate-ccw" class="w-8 h-8 text-slate-300"></i>
                    <span>No hay devoluciones registradas en este periodo</span>
                </div>`;
            if (window.lucide) lucide.createIcons();
            return;
        }

        container.innerHTML = `
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-black text-slate-400 uppercase border-b border-slate-100">
                        <th class="p-4">Fecha</th>
                        <th class="p-4">Artículo / Factura</th>
                        <th class="p-4">Estado/Destino</th>
                        <th class="p-4 text-right">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    ${result.data.map(d => `
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 text-xs font-bold text-slate-500">${new Date(d.fecha).toLocaleString()}</td>
                            <td class="p-4">
                                <p class="text-sm font-black text-navy-blue uppercase">${d.descripcion}</p>
                                <p class="text-xs text-slate-400 font-bold uppercase">Factura #${d.venta_id} • Cliente: ${d.cliente_nombre || 'N/A'}</p>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-black uppercase ${d.destino === 'STOCK' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'}">
                                    ${d.destino === 'STOCK' ? 'Reingreso Stock (Bueno)' : 'Garantía/Dañado (Malo)'}
                                </span>
                            </td>
                            <td class="p-4 text-right font-black text-rose-600 text-base">${AppUtils.formatCurrency(d.monto_devuelto)}</td>
                        </tr>`).join('')}
                </tbody>
            </table>`;
    } catch (e) { console.error(e); }
}

/**
 * Lógica para cargar Cartera por Edades
 */
window.cargarCartera = async function () {
    const tbody = document.getElementById('cartera-body');
    if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-16 text-slate-400 italic animate-pulse">GENERANDO REPORTE DE CARTERA...</td></tr>';

    try {
        const res = await fetch(`${URLROOT}/reportes/cartera`);
        const result = await res.json();
        if (result.success) {
            window.renderCartera(result.data);
        }
    } catch (e) {
        console.error(e);
        AppUtils.showToast("Error al cargar cartera", "error");
    }
};

/**
 * Lógica para cargar Análisis de Rentabilidad
 */
window.cargarRentabilidad = async function () {
    const desde = document.getElementById('rep-desde').value;
    const hasta = document.getElementById('rep-hasta').value;
    const tbody = document.getElementById('rentabilidad-body');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center py-16 text-slate-400 italic animate-pulse">ANALIZANDO RENTABILIDAD...</td></tr>';

    try {
        const res = await fetch(`${URLROOT}/reportes/rentabilidad?desde=${desde}&hasta=${hasta}`);
        const result = await res.json();
        if (result.success) {
            window.renderRentabilidad(result.data);
        }
    } catch (e) {
        console.error(e);
        AppUtils.showToast("Error al cargar rentabilidad", "error");
    }
};