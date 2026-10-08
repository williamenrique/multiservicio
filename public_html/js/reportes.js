/**
 * GESTIÓN DE REPORTES - UNIFICADO
 * 
 * v2.7 (2026-10-08) — FIX:
 *   • Se agregó botón "IMPRIMIR FACTURA" en el modal de verDetalleVenta.
 *   • Se agregó botón de imprimir 🖨 por cada factura en el drawer de cartera.
 *   • Se agregó botón de imprimir 🖨 por cada factura en auditoría de trabajos.
 *   • Se restauraron las funciones que se habían perdido.
 */

let rawAuditData = { ventas: [], compras: [], gastos: [] };
let activeReportTab = 'resumen';
let drawerState = {
    clienteId: null,
    clienteNombre: '',
    data: null,
    filtro: '',
    orden: 'fecha_desc'
};

// ═══════════════════════════════════════════════════════════════════
//  RENDER DE FILA DE FLUJO DE CAJA
// ═══════════════════════════════════════════════════════════════════
window.renderFlujoRow = (m) => {
    const isIngreso = m.tipo === 'INGRESO';
    const color = m.tipo_color || (isIngreso ? 'emerald' : 'rose');
    const label = m.categoria_label || m.categoria || m.tipo;

    const cat = (m.categoria || '').toUpperCase();
    const labelUpper = (m.categoria_label || m.tipo || '').toUpperCase();
    const desc = (m.descripcion || '').toUpperCase();
    const tipo = (m.tipo || '').toUpperCase();
    const root = window.URLROOT || '';

    const refId = m.referencia_id || m.id;
    const ordenId = m.orden_id;

    let printUrl = '';
    let detailUrl = '';
    let printBtnClass = 'text-slate-400 hover:text-navy-blue';

    if (tipo === 'INGRESO' && (cat.includes('VENTA') || cat.includes('ABONO') || desc.includes('FACTURA') || labelUpper.includes('FACTURA'))) {
        printUrl = `${root}/facturacion/imprimir/${refId}`;
        detailUrl = `verDetalleVenta(${refId})`;
        printBtnClass = 'text-blue-500 hover:bg-blue-50';
    } else if (cat === 'NOMINA' || labelUpper.includes('NOMINA') || labelUpper.includes('ADELANTO')) {
        printUrl = `${root}/reportes/imprimirRecibo/${refId}`;
        detailUrl = `verDetallePagoHistorial(${refId})`;
        printBtnClass = 'text-amber-500 hover:bg-amber-50';
    } else if (tipo === 'EGRESO' || cat.includes('PROVEEDOR') || cat.includes('GASTO') || labelUpper.includes('PAGO') || desc.includes('PAGO') || labelUpper.includes('SERVICIO') || cat.includes('COMPRA')) {
        printUrl = `${root}/gastos/imprimir/${refId}`;
        detailUrl = `verDetalleCompra(${refId})`;
        printBtnClass = 'text-rose-500 hover:bg-rose-50';
    }

    let orderPrintUrl = '';
    if (ordenId && ordenId !== 'null' && ordenId !== null && ordenId !== '') {
        orderPrintUrl = `${root}/taller/imprimir/${ordenId}`;
    } else if (labelUpper.includes('O.S') || desc.includes('ORDEN') || cat.includes('ORDEN')) {
        orderPrintUrl = `${root}/taller/imprimir/${refId}`;
    }

    return `
        <tr class="hover:bg-slate-50 transition-colors border-b border-slate-100 animate-in fade-in duration-300">
            <td class="px-4 py-3 font-mono text-xs font-bold text-slate-400 text-center">#${m.id || '---'}</td>
            <td class="px-4 py-3 text-base font-bold text-slate-600 uppercase text-center">${new Date(m.fecha).toLocaleDateString()}</td>
            <td class="px-4 py-3 text-center w-48">
                <span class="px-4 py-1.5 rounded text-xs font-black bg-${color}-100 text-${color}-600 whitespace-nowrap inline-block shadow-sm">${label}</span>
            </td>
            <td class="px-4 py-3">
                <div class="flex flex-col gap-0.5">
                    <span class="text-base font-bold text-slate-800 uppercase leading-tight">${m.descripcion || 'OPERACIÓN'}</span>
                    ${m.placa && m.placa !== '---' ? `<span class="text-xs text-slate-600 font-mono font-bold uppercase">PLACA: ${m.placa}</span>` : ''} 
                    ${m.cliente_nombre ? `<span class="text-xs text-slate-600 font-bold uppercase">CLIENTE: ${m.cliente_nombre}</span>` : ''} 
                    ${m.proveedor_nombre ? `<span class="text-xs text-slate-600 font-bold uppercase">PROV: ${m.proveedor_nombre}</span>` : ''} 
                    ${m.empleado_nombre ? `<span class="text-xs text-slate-600 font-bold uppercase">EMPLEADO: ${m.empleado_nombre}</span>` : ''} 
                </div>
            </td>
            <td class="px-4 py-3 text-right w-36">
                <span class="text-lg font-black text-${color}-600 tracking-tighter">
                    ${isIngreso ? '+' : '-'}${AppUtils.formatCurrency(Math.abs(parseFloat(m.monto_pagado || 0)))}
                </span>
            </td>
            <td class="px-4 py-3 text-right" style="width: 110px; min-width: 110px;">
                <div class="flex justify-end gap-1">
                    ${orderPrintUrl ? `
                        <a href="${orderPrintUrl}" target="_blank" class="text-emerald-500 hover:bg-emerald-50 p-2 rounded-lg transition-all" title="Imprimir Orden Técnica">
                            <i data-lucide="wrench" class="w-4 h-4"></i>
                        </a>
                    ` : ''}
                    ${detailUrl ? `
                        <button onclick="${detailUrl}" class="p-2 text-slate-400 hover:text-navy-blue transition-colors" title="Ver Detalle">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    ` : ''}
                    ${printUrl ? `
                        <a href="${printUrl}" target="_blank" class="${printBtnClass} p-2 rounded-lg transition-all" title="Imprimir Comprobante">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                        </a>
                    ` : ''}
                </div>
            </td>
        </tr>`;
};

window.actualizarFiltrosFechas = () => {
    if (window.handler_reporte_flujo) window.handler_reporte_flujo.reload();
    if (window.handler_reporte_devoluciones) window.handler_reporte_devoluciones.reload();

    if (activeReportTab === 'detallado') window.cargarReporteDetallado();
    if (activeReportTab === 'rentabilidad') window.cargarRentabilidad();
    if (activeReportTab === 'nomina') window.cargarNomina();
    if (activeReportTab === 'historial_nomina') window.cargarHistorialNomina();
};

// ═══════════════════════════════════════════════════════════════════
//  CARTERA — TABLA PRINCIPAL
// ═══════════════════════════════════════════════════════════════════
window.renderCartera = (data) => {
    const tbody = document.getElementById('cartera-body');
    if (!tbody) return;

    tbody.innerHTML = (Array.isArray(data) && data.length > 0) ? data.map(c => {
        const safeNombre = (c.cliente_nombre || '').replace(/'/g, "\\'");
        const safeClienteId = (c.cliente_id || '').toString().replace(/'/g, "\\'");
        const sinId = !c.cliente_id;

        return `
        <tr class="hover:bg-slate-50 border-b border-slate-100">
            <td class="px-6 py-4 text-sm font-bold text-slate-700 uppercase">${c.cliente_nombre}</td>
            <td class="px-6 py-4 text-xs font-black text-slate-400 text-center">${AppUtils.formatCurrency(c.rango_0_15)}</td>
            <td class="px-6 py-4 text-xs font-black text-amber-500 text-center">${AppUtils.formatCurrency(c.rango_16_30)}</td>
            <td class="px-6 py-4 text-xs font-black text-rose-600 text-center">${AppUtils.formatCurrency(c.rango_30_mas)}</td>
            <td class="px-6 py-4 text-right font-black text-navy-blue text-sm">${AppUtils.formatCurrency(c.total_deuda)}</td>
            <td class="px-6 py-4 text-right">
                <button onclick="verDetalleClienteCartera('${safeClienteId}', '${safeNombre}')" ${sinId ? 'disabled' : ''}
                        class="inline-flex items-center gap-1.5 bg-navy-blue hover:bg-slate-800 disabled:bg-slate-300 text-white font-black text-[10px] uppercase px-3 py-2 rounded-lg transition-all shadow-sm">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                    Ver Detalle
                </button>
            </td>
        </tr>
        `;
    }).join('') : '<tr><td colspan="6" class="text-center py-20 text-slate-400 italic font-bold uppercase tracking-widest">Sin deudas de cartera</td></tr>';

    if (window.lucide) lucide.createIcons();
};

// ═══════════════════════════════════════════════════════════════════
//  DRAWER — CARGA Y RENDER
// ═══════════════════════════════════════════════════════════════════
window.verDetalleClienteCartera = async (clienteId, clienteNombre) => {
    if (!clienteId) return AppUtils.showToast('No se puede identificar al cliente', 'error');

    try {
        AppUtils.showLoading('Cargando facturas del cliente...');
        const res = await fetch(`${URLROOT}/facturacion/getFacturasCliente/${clienteId}`, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) { AppUtils.hideLoading(); return AppUtils.showToast('Error al consultar', 'error'); }
        const result = await res.json();
        AppUtils.hideLoading();

        if (!result.success || !result.data) return AppUtils.showToast('No se pudieron cargar las facturas', 'error');

        drawerState.clienteId = clienteId;
        drawerState.clienteNombre = clienteNombre || '';
        drawerState.data = result.data;
        drawerState.filtro = '';
        drawerState.orden = 'fecha_desc';

        let drawer = document.getElementById('cartera-drawer');
        let overlay = document.getElementById('cartera-drawer-overlay');

        if (!drawer) {
            drawer = document.createElement('div');
            drawer.id = 'cartera-drawer';
            drawer.className = 'fixed inset-y-0 right-0 w-full max-w-2xl bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-[9999] flex flex-col';
            document.body.appendChild(drawer);
        }
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'cartera-drawer-overlay';
            overlay.className = 'fixed inset-0 bg-black/50 z-[9998] hidden transition-opacity duration-300';
            overlay.onclick = window.cerrarCarteraDrawer;
            document.body.appendChild(overlay);
        }

        drawer.dataset.clienteId = clienteId;
        drawer.dataset.clienteNombre = clienteNombre || '';

        renderCarteraDrawer();

        overlay.classList.remove('hidden');
        void drawer.offsetWidth;
        drawer.classList.remove('translate-x-full');
        if (window.lucide) lucide.createIcons();
    } catch (e) {
        AppUtils.hideLoading();
        console.error(e);
        AppUtils.showToast('Error de conexión', 'error');
    }
};

function renderCarteraDrawer() {
    const drawer = document.getElementById('cartera-drawer');
    if (!drawer || !drawerState.data) return;

    const cliente = drawerState.data.cliente || {};
    const totales = drawerState.data.totales || { total_deuda: 0, cantidad_facturas: 0 };
    const fmt = (n) => AppUtils.formatCurrency(n || 0);

    let facturas = [...(drawerState.data.facturas || [])];
    if (drawerState.filtro) {
        const t = drawerState.filtro.toLowerCase();
        facturas = facturas.filter(f =>
            (f.placa || '').toLowerCase().includes(t) ||
            (f.modelo_vehiculo || '').toLowerCase().includes(t) ||
            (f.id_formateado || '').toLowerCase().includes(t) ||
            (f.observaciones || '').toLowerCase().includes(t) ||
            String(f.saldo_pendiente).includes(t) ||
            String(f.total).includes(t)
        );
    }

    const ordenadores = {
        'fecha_desc':   (a, b) => new Date(b.fecha) - new Date(a.fecha),
        'fecha_asc':    (a, b) => new Date(a.fecha) - new Date(b.fecha),
        'deuda_desc':   (a, b) => parseFloat(b.saldo_pendiente) - parseFloat(a.saldo_pendiente),
        'deuda_asc':    (a, b) => parseFloat(a.saldo_pendiente) - parseFloat(b.saldo_pendiente),
        'dias_desc':    (a, b) => parseInt(b.dias_atraso) - parseInt(a.dias_atraso),
    };
    facturas.sort(ordenadores[drawerState.orden] || ordenadores['fecha_desc']);

    const getDiasBadge = (dias) => {
        dias = parseInt(dias) || 0;
        if (dias <= 15) return 'bg-slate-100 text-slate-600';
        if (dias <= 30) return 'bg-amber-100 text-amber-700';
        return 'bg-rose-100 text-rose-700';
    };

    const facturasHtml = facturas.length === 0
        ? '<div class="text-center py-12 text-slate-400 italic font-bold uppercase tracking-widest">Sin facturas que coincidan</div>'
        : facturas.map(f => {
            const dias = parseInt(f.dias_atraso) || 0;
            const diasBadge = getDiasBadge(dias);
            const diasTexto = dias === 0 ? 'HOY' : `HACE ${dias} DÍAS`;
            const saldoFmt = parseFloat(f.saldo_pendiente || 0).toFixed(2);
            const estadoGestion = f.estado_gestion || 'NUEVO';

            const ultimoAbono = (f.ultimo_abono_fecha && parseFloat(f.ultimo_abono_monto) > 0)
                ? `<div class="text-[9px] font-bold text-emerald-600 mt-0.5">
                       <i data-lucide="check-circle-2" class="w-2.5 h-2.5 inline"></i>
                       Últ. abono: ${fmt(f.ultimo_abono_monto)} (${new Date(f.ultimo_abono_fecha).toLocaleDateString()})
                   </div>`
                : '';

            return `
            <div class="border border-slate-200 rounded-xl p-4 hover:border-navy-blue hover:shadow-md transition-all bg-white"
                 data-factura-card="${f.id}" data-saldo="${f.saldo_pendiente}" data-abonos-loaded="0">
                
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 rounded-xl bg-navy-blue text-neon-green flex flex-col items-center justify-center shadow-sm">
                            <span class="text-[8px] font-black uppercase opacity-70">FAC</span>
                            <span class="text-sm font-black leading-none">#${String(f.id).padStart(3, '0')}</span>
                        </div>
                        <div>
                            <p class="text-xs font-black text-navy-blue uppercase">${f.id_formateado || ('FAC-' + f.id)}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">
                                ${new Date(f.fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' })}
                            </p>
                            ${ultimoAbono}
                        </div>
                    </div>
                    <span class="text-[9px] font-black uppercase px-2 py-1 rounded-md ${diasBadge}">${diasTexto}</span>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-3 text-xs">
                    <div>
                        <span class="text-[9px] font-black text-slate-400 uppercase">Vehículo</span>
                        <div class="font-bold text-slate-700 uppercase">${f.marca_vehiculo || ''} ${f.modelo_vehiculo || 'N/A'}</div>
                        <span class="font-mono text-navy-blue font-black text-sm">[${f.placa || '---'}]</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[9px] font-black text-slate-400 uppercase">Origen</span>
                        <div class="font-bold text-slate-700 uppercase text-[11px]">${f.origen || 'N/A'}</div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 mb-3">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                        <p class="text-[9px] font-black text-slate-400 uppercase mb-0.5">Total</p>
                        <p class="font-black text-slate-700 text-sm">${fmt(f.total)}</p>
                    </div>
                    <div class="p-2 bg-emerald-50 rounded-lg border border-emerald-100">
                        <p class="text-[9px] font-black text-emerald-600 uppercase mb-0.5">Abonado</p>
                        <p class="font-black text-emerald-700 text-sm">${fmt(parseFloat(f.pago_efectivo || 0) + parseFloat(f.pago_transferencia || 0))}</p>
                    </div>
                    <div class="p-2 bg-rose-50 rounded-lg border border-rose-100">
                        <p class="text-[9px] font-black text-rose-600 uppercase mb-0.5">Debe</p>
                        <p class="font-black text-rose-700 text-sm">${fmt(f.saldo_pendiente)}</p>
                    </div>
                </div>

                ${f.observaciones ? `
                    <div class="p-2 bg-amber-50 rounded-lg border border-amber-100 mb-3">
                        <p class="text-[9px] font-black text-amber-700 uppercase mb-0.5">Observaciones</p>
                        <p class="text-[10px] text-amber-800 italic font-bold leading-tight">${f.observaciones}</p>
                    </div>
                ` : ''}

                <div class="mb-3">
                    <label class="text-[9px] font-black text-slate-400 uppercase flex items-center gap-1 mb-1">
                        <i data-lucide="flag" class="w-3 h-3"></i> Estado de gestión
                    </label>
                    <select class="estado-gestion-select w-full p-2 bg-slate-50 border border-slate-200 rounded-lg font-bold text-[11px] text-slate-700 focus:ring-2 focus:ring-navy-blue outline-none"
                            data-factura-id="${f.id}" data-previous-value="${estadoGestion}">
                        <option value="NUEVO" ${estadoGestion === 'NUEVO' ? 'selected' : ''}>🔵 NUEVO</option>
                        <option value="GESTIONADO" ${estadoGestion === 'GESTIONADO' ? 'selected' : ''}>📞 GESTIONADO</option>
                        <option value="PROMETIDO" ${estadoGestion === 'PROMETIDO' ? 'selected' : ''}>🤝 PROMETIDO</option>
                        <option value="ACUERDO_PAGO" ${estadoGestion === 'ACUERDO_PAGO' ? 'selected' : ''}>📄 ACUERDO DE PAGO</option>
                        <option value="JUDICIAL" ${estadoGestion === 'JUDICIAL' ? 'selected' : ''}>⚖️ JUDICIAL</option>
                    </select>
                </div>

                <div class="mb-3">
                    <button type="button" class="historial-toggle w-full flex items-center justify-between p-2 bg-slate-50 hover:bg-slate-100 rounded-lg text-[10px] font-black uppercase text-slate-500 transition-all"
                            data-factura-id="${f.id}">
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="history" class="w-3 h-3"></i>
                            Ver historial de abonos
                        </span>
                        <i data-lucide="chevron-down" class="historial-chevron w-3.5 h-3.5 transition-transform"></i>
                    </button>
                    <div class="historial-container hidden mt-2 p-3 bg-slate-50 rounded-lg border border-slate-100 text-[10px]" data-factura-id="${f.id}">
                        <div class="text-center text-slate-400 italic">Cargando...</div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 space-y-2" data-abono-form="${f.id}">
                    <p class="text-[9px] font-black text-slate-400 uppercase flex items-center gap-1.5">
                        <i data-lucide="hand-coins" class="w-3 h-3"></i> Registrar Abono
                    </p>

                    <div class="grid grid-cols-4 gap-2">
                        <div class="col-span-2">
                            <input type="number" min="0.01" step="0.01" max="${saldoFmt}" placeholder="Monto"
                                   class="abono-input w-full p-2 bg-slate-50 border border-slate-200 rounded-lg font-black text-navy-blue text-sm focus:ring-2 focus:ring-emerald-400 outline-none"
                                   data-factura-id="${f.id}" data-saldo="${f.saldo_pendiente}">
                        </div>
                        <div>
                            <button type="button" class="btn-abonar-todo w-full p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-[10px] uppercase rounded-lg transition-all"
                                    data-factura-id="${f.id}">
                                Máx
                            </button>
                        </div>
                        <div>
                            <select class="abono-metodo w-full p-2 bg-slate-50 border border-slate-200 rounded-lg font-bold text-[10px] text-slate-700 focus:ring-2 focus:ring-emerald-400 outline-none"
                                    data-factura-id="${f.id}">
                                <option value="EFECTIVO">EFE</option>
                                <option value="TRANSFERENCIA">TRA</option>
                            </select>
                        </div>
                    </div>

                    <p class="abono-error hidden text-[10px] font-black text-rose-600" data-error-for="${f.id}"></p>

                    <div class="flex justify-end gap-2">
                        <!-- 🖨 IMPRIMIR PDF DIRECTO -->
                        <button onclick="window.open('${URLROOT}/facturacion/imprimir/${f.id}', '_blank')"
                                class="p-2 text-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-all" 
                                title="Imprimir Factura PDF">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                        </button>
                        <!-- 🔗 VER FACTURA COMPLETA -->
                        <button onclick="window.open('${URLROOT}/facturas/ver/${f.id}', '_blank')"
                                class="p-2 text-slate-400 hover:text-navy-blue hover:bg-slate-50 rounded-lg transition-colors" 
                                title="Ver factura completa">
                            <i data-lucide="external-link" class="w-4 h-4"></i>
                        </button>
                        <button class="abono-btn inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-black text-[10px] uppercase px-3 py-2 rounded-lg transition-all shadow-sm disabled:shadow-none"
                                data-factura-id="${f.id}" disabled>
                            <i data-lucide="hand-coins" class="w-3.5 h-3.5"></i>
                            Abonar
                        </button>
                    </div>
                </div>
            </div>`;
        }).join('');

    drawer.innerHTML = `
        <div class="bg-navy-blue text-white p-6 flex justify-between items-start border-b border-gray-800">
            <div class="flex-1">
                <p class="text-[10px] font-black uppercase tracking-widest opacity-60 mb-1">Detalle de Cartera</p>
                <h2 class="text-xl font-black text-white uppercase leading-tight">${cliente.nombre || 'SIN CLIENTE'}</h2>
                ${cliente.telefono ? `<p class="text-xs font-bold text-neon-green mt-1 flex items-center gap-2"><i data-lucide="phone" class="w-3 h-3"></i> ${cliente.telefono}</p>` : ''}
                ${cliente.email ? `<p class="text-xs font-bold text-slate-300 flex items-center gap-2 mt-0.5"><i data-lucide="mail" class="w-3 h-3"></i> ${cliente.email}</p>` : ''}
            </div>
            <button onclick="cerrarCarteraDrawer()" class="p-2 text-slate-400 hover:text-white hover:bg-white/10 rounded-lg transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="bg-slate-50 p-4 border-b border-slate-200">
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="p-3 bg-white rounded-xl border border-slate-200">
                    <p class="text-[9px] font-black text-slate-400 uppercase">Facturas Pendientes</p>
                    <p class="text-2xl font-black text-navy-blue">${totales.cantidad_facturas}</p>
                </div>
                <div class="p-3 bg-rose-50 rounded-xl border border-rose-200">
                    <p class="text-[9px] font-black text-rose-600 uppercase">Deuda Total</p>
                    <p class="text-2xl font-black text-rose-600">${fmt(totales.total_deuda)}</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div class="col-span-2 relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"></i>
                    <input type="text" id="drawer-filtro" placeholder="Filtrar placa, monto, obs..."
                           class="w-full pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold outline-none focus:ring-2 focus:ring-navy-blue"
                           value="${drawerState.filtro}">
                </div>
                <select id="drawer-orden" class="w-full px-2 py-2 bg-white border border-slate-200 rounded-lg text-[10px] font-black uppercase text-slate-600 outline-none focus:ring-2 focus:ring-navy-blue">
                    <option value="fecha_desc" ${drawerState.orden === 'fecha_desc' ? 'selected' : ''}>Fecha ↓</option>
                    <option value="fecha_asc" ${drawerState.orden === 'fecha_asc' ? 'selected' : ''}>Fecha ↑</option>
                    <option value="deuda_desc" ${drawerState.orden === 'deuda_desc' ? 'selected' : ''}>Deuda ↓</option>
                    <option value="deuda_asc" ${drawerState.orden === 'deuda_asc' ? 'selected' : ''}>Deuda ↑</option>
                    <option value="dias_desc" ${drawerState.orden === 'dias_desc' ? 'selected' : ''}>Antigüedad</option>
                </select>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-3 bg-slate-50/50" id="cartera-drawer-facturas">
            ${facturasHtml}
        </div>

        <div class="p-4 bg-white border-t border-slate-200 flex justify-end">
            <button onclick="cerrarCarteraDrawer()" class="px-6 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-black text-xs uppercase rounded-lg transition-all">
                Cerrar
            </button>
        </div>
    `;

    bindDrawerEvents();
    if (window.lucide) lucide.createIcons();
}

// ═══════════════════════════════════════════════════════════════════
//  EVENTOS DEL DRAWER
// ═══════════════════════════════════════════════════════════════════
function bindDrawerEvents() {
    document.getElementById('drawer-filtro')?.addEventListener('input', (e) => {
        drawerState.filtro = e.target.value;
        clearTimeout(window._drawerFiltroTimeout);
        window._drawerFiltroTimeout = setTimeout(() => renderCarteraDrawer(), 250);
    });

    document.getElementById('drawer-orden')?.addEventListener('change', (e) => {
        drawerState.orden = e.target.value;
        renderCarteraDrawer();
    });

    document.querySelectorAll('#cartera-drawer .abono-input').forEach(input => {
        input.addEventListener('input', () => validarAbonoInline(input));
    });

    document.querySelectorAll('#cartera-drawer .btn-abonar-todo').forEach(btn => {
        btn.addEventListener('click', () => {
            const fid = btn.dataset.facturaId;
            const card = document.querySelector(`#cartera-drawer [data-factura-card="${fid}"]`);
            if (!card) return;
            const input = card.querySelector('.abono-input');
            input.value = parseFloat(input.max).toFixed(2);
            validarAbonoInline(input);
        });
    });

    document.querySelectorAll('#cartera-drawer .abono-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const facturaId = btn.dataset.facturaId;
            const card = document.querySelector(`#cartera-drawer [data-factura-card="${facturaId}"]`);
            if (!card) return;
            const input = card.querySelector('.abono-input');
            const metodo = card.querySelector('.abono-metodo').value;
            if (!validarAbonoInline(input)) return;
            ejecutarAbonoInline(facturaId, parseFloat(input.value), metodo, card, btn);
        });
    });

    document.querySelectorAll('#cartera-drawer .historial-toggle').forEach(btn => {
        btn.addEventListener('click', () => cargarHistorialAbonos(btn));
    });

    document.querySelectorAll('#cartera-drawer .estado-gestion-select').forEach(sel => {
        sel.addEventListener('change', () => actualizarEstadoGestionInline(sel));
    });
}

function validarAbonoInline(input) {
    const facturaId = input.dataset.facturaId;
    const saldo = parseFloat(input.dataset.saldo) || 0;
    const valor = parseFloat(input.value);
    const esNumero = !isNaN(valor);
    const mayorCero = esNumero && valor > 0;
    const menorIgualSaldo = esNumero && valor <= (saldo + 0.001);
    const valido = esNumero && mayorCero && menorIgualSaldo;

    const card = input.closest('[data-factura-card]');
    const btn = card?.querySelector('.abono-btn');
    const errorEl = card?.querySelector(`.abono-error[data-error-for="${facturaId}"]`);

    if (btn) btn.disabled = !valido;

    if (errorEl) {
        if (!input.value) { errorEl.classList.add('hidden'); input.classList.remove('border-rose-400'); }
        else if (!esNumero || !mayorCero) { errorEl.textContent = 'El monto debe ser mayor a 0.'; errorEl.classList.remove('hidden'); input.classList.add('border-rose-400'); }
        else if (!menorIgualSaldo) { errorEl.textContent = 'El monto no puede superar el saldo (' + AppUtils.formatCurrency(saldo) + ').'; errorEl.classList.remove('hidden'); input.classList.add('border-rose-400'); }
        else { errorEl.classList.add('hidden'); input.classList.remove('border-rose-400'); }
    }
    return valido;
}

async function ejecutarAbonoInline(facturaId, monto, metodo, card, btn) {
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Procesando...';
    if (window.lucide) lucide.createIcons();

    try {
        const res = await fetch(`${URLROOT}/facturacion/registrarAbono`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ venta_id: parseInt(facturaId), monto, metodo })
        });
        const data = await res.json();

        if (data.success) {
            AppUtils.showToast(data.mensaje || 'Abono registrado');
            if (drawerState.clienteId) {
                window.verDetalleClienteCartera(drawerState.clienteId, drawerState.clienteNombre);
            }
            if (typeof window.cargarCartera === 'function') window.cargarCartera();
            if (typeof initCreditNotifications === 'function') initCreditNotifications();
        } else {
            AppUtils.showToast(data.mensaje || 'Error', 'error');
            btn.disabled = false; btn.innerHTML = originalHtml;
            if (window.lucide) lucide.createIcons();
        }
    } catch (e) {
        console.error(e);
        AppUtils.showToast('Error de conexión', 'error');
        btn.disabled = false; btn.innerHTML = originalHtml;
        if (window.lucide) lucide.createIcons();
    }
}

async function cargarHistorialAbonos(toggleBtn) {
    const facturaId = toggleBtn.dataset.facturaId;
    const card = toggleBtn.closest('[data-factura-card]');
    const container = card.querySelector(`.historial-container[data-factura-id="${facturaId}"]`);
    const chevron = toggleBtn.querySelector('.historial-chevron');

    const isHidden = container.classList.contains('hidden');
    if (!isHidden) {
        container.classList.add('hidden');
        chevron.style.transform = '';
        return;
    }

    container.classList.remove('hidden');
    chevron.style.transform = 'rotate(180deg)';

    if (card.dataset.abonosLoaded === '1') return;
    container.innerHTML = '<div class="text-center text-slate-400 italic">Cargando...</div>';

    try {
        const res = await fetch(`${URLROOT}/facturacion/getAbonosFactura/${facturaId}`);
        const data = await res.json();

        if (!data.success || !data.data || data.data.length === 0) {
            container.innerHTML = '<div class="text-center text-slate-400 italic py-2">Sin abonos registrados</div>';
            card.dataset.abonosLoaded = '1';
            return;
        }

        const fmt = (n) => AppUtils.formatCurrency(n || 0);
        container.innerHTML = `
            <table class="w-full">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-200">
                        <th class="text-left py-1 text-[9px] font-black uppercase">Fecha</th>
                        <th class="text-left py-1 text-[9px] font-black uppercase">Método</th>
                        <th class="text-left py-1 text-[9px] font-black uppercase">Por</th>
                        <th class="text-right py-1 text-[9px] font-black uppercase">Monto</th>
                        <th class="text-right py-1 text-[9px] font-black uppercase">PDF</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.data.map(a => `
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="py-1.5 text-[10px] text-slate-600">${new Date(a.fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: '2-digit' })}</td>
                            <td class="py-1.5">
                                <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-black ${a.metodo_pago === 'EFECTIVO' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'}">
                                    ${a.metodo_pago === 'EFECTIVO' ? 'EFE' : 'TRA'}
                                </span>
                            </td>
                            <td class="py-1.5 text-[10px] text-slate-500 uppercase truncate max-w-[80px]">${a.usuario_nombre || 'SISTEMA'}</td>
                            <td class="py-1.5 text-right font-black text-emerald-700">${fmt(a.monto)}</td>
                            <td class="py-1.5 text-right">
                                <a href="${URLROOT}/facturacion/imprimirReciboAbono/${a.id}" target="_blank"
                                   class="inline-flex p-1 text-blue-500 hover:bg-blue-50 rounded transition-all" title="Recibo PDF">
                                    <i data-lucide="printer" class="w-3 h-3"></i>
                                </a>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        card.dataset.abonosLoaded = '1';
        if (window.lucide) lucide.createIcons();
    } catch (e) {
        console.error(e);
        container.innerHTML = '<div class="text-center text-rose-500 italic py-2">Error al cargar</div>';
    }
}

async function actualizarEstadoGestionInline(selectEl) {
    const facturaId = selectEl.dataset.facturaId;
    const estado = selectEl.value;
    const originalValue = selectEl.dataset.previousValue || 'NUEVO';

    selectEl.disabled = true;
    try {
        const res = await fetch(`${URLROOT}/facturacion/actualizarEstadoGestion`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ factura_id: parseInt(facturaId), estado })
        });
        const data = await res.json();

        if (data.success) {
            AppUtils.showToast('Estado de gestión actualizado', 'success');
            selectEl.dataset.previousValue = estado;
        } else {
            AppUtils.showToast(data.mensaje || 'Error al actualizar', 'error');
            selectEl.value = originalValue;
        }
    } catch (e) {
        console.error(e);
        AppUtils.showToast('Error de conexión', 'error');
        selectEl.value = originalValue;
    } finally {
        selectEl.disabled = false;
    }
}

window.cerrarCarteraDrawer = () => {
    const drawer = document.getElementById('cartera-drawer');
    const overlay = document.getElementById('cartera-drawer-overlay');
    if (drawer) drawer.classList.add('translate-x-full');
    if (overlay) overlay.classList.add('hidden');
};

// ═══════════════════════════════════════════════════════════════════
//  CARTERA — CARGA
// ═══════════════════════════════════════════════════════════════════
window.cargarCartera = async function () {
    const tbody = document.getElementById('cartera-body');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-16 text-slate-400 italic animate-pulse font-bold uppercase tracking-widest">GENERANDO REPORTE DE CARTERA...</td></tr>';

    try {
        const res = await fetch(`${URLROOT}/reportes/cartera`);
        const result = await res.json();
        if (result.success) window.renderCartera(result.data);
    } catch (e) {
        console.error(e);
        AppUtils.showToast("Error al cargar cartera", "error");
    }
};

window.abonarDesdeCartera = async (clienteNombre) => {
    try {
        AppUtils.showLoading('Consultando facturas pendientes...');

        const res = await fetch(`${URLROOT}/facturacion/getDeudoresSummary`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!res.ok) {
            AppUtils.hideLoading();
            AppUtils.showToast('No se pudo consultar las facturas pendientes', 'error');
            return;
        }

        const result = await res.json();
        AppUtils.hideLoading();

        if (!result.success || !result.data || !Array.isArray(result.data.lista)) {
            AppUtils.showToast('No hay facturas pendientes para este cliente', 'info');
            return;
        }

        const facturasCliente = result.data.lista.filter(
            f => String(f.cliente_nombre || '').toUpperCase() === String(clienteNombre || '').toUpperCase()
                && parseFloat(f.saldo_pendiente) > 0.05
        );

        if (facturasCliente.length === 0) {
            AppUtils.showToast('No se encontraron facturas pendientes para este cliente', 'info');
            return;
        }

        if (facturasCliente.length === 1) {
            const f = facturasCliente[0];
            return window.registrarAbonoCliente(f.id, parseFloat(f.saldo_pendiente));
        }

        const opcionesHtml = facturasCliente.map((f, i) => `
            <div class="p-3 border-b border-slate-100 last:border-0 hover:bg-slate-50 cursor-pointer transition-colors"
                 onclick="window._abonarFacturaDesdeLista(${i})">
                <div class="flex justify-between items-center">
                    <div class="flex flex-col">
                        <span class="font-black text-navy-blue text-sm">FACTURA #${f.id}</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase">
                            ${f.placa || '---'} · ${f.modelo_vehiculo || 'N/A'}
                        </span>
                        <span class="text-[10px] text-slate-400 font-mono">
                            ${f.fecha ? new Date(f.fecha).toLocaleDateString() : ''}
                        </span>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-black text-rose-600">${AppUtils.formatCurrency(f.saldo_pendiente)}</p>
                        <p class="text-[9px] font-black text-rose-400 uppercase">Saldo</p>
                    </div>
                </div>
            </div>
        `).join('');

        window._facturasClienteCartera = facturasCliente;

        Swal.fire({
            title: `<span class="text-[10px] uppercase text-slate-400 font-black tracking-widest">Seleccionar Factura</span><br>
                    <span class="text-navy-blue">${clienteNombre}</span>`,
            html: `
                <p class="text-xs text-slate-500 mb-3 uppercase font-bold">
                    Este cliente tiene ${facturasCliente.length} facturas pendientes. Seleccione una para abonar:
                </p>
                <div class="max-h-80 overflow-y-auto border border-slate-200 rounded-xl bg-white text-left">
                    ${opcionesHtml}
                </div>
            `,
            showCancelButton: true,
            showConfirmButton: false,
            cancelButtonText: 'CANCELAR',
            cancelButtonColor: '#64748b',
            didOpen: () => {
                if (window.lucide) lucide.createIcons();
            }
        });
    } catch (e) {
        AppUtils.hideLoading();
        console.error('Error en abonarDesdeCartera:', e);
        AppUtils.showToast('Error de conexión', 'error');
    }
};

window._abonarFacturaDesdeLista = (index) => {
    const factura = window._facturasClienteCartera?.[index];
    if (!factura) return;
    Swal.close();
    setTimeout(() => {
        window.registrarAbonoCliente(factura.id, parseFloat(factura.saldo_pendiente));
    }, 200);
};

// ═══════════════════════════════════════════════════════════════════
//  REPORTE DETALLADO (Auditoría) — CON BOTÓN IMPRIMIR POR FACTURA
// ═══════════════════════════════════════════════════════════════════
window.cargarReporteDetallado = async () => {
    const desde = document.getElementById('rep-desde')?.value || '1970-01-01';
    const hasta = document.getElementById('rep-hasta')?.value || '2099-12-31';
    const auditContainer = document.getElementById('audit-list-container');
    const contCompras = document.getElementById('det-compras-body');
    const contGastos = document.getElementById('det-gastos-body');

    if (auditContainer) auditContainer.innerHTML = '<div class="py-20 text-center animate-pulse text-slate-400 font-bold uppercase tracking-widest">Generando Auditoría de Trabajos...</div>';
    if (contCompras) contCompras.innerHTML = '<tr><td colspan="6" class="p-8 text-center animate-pulse">Cargando compras...</td></tr>';
    if (contGastos) contGastos.innerHTML = '<tr><td colspan="5" class="p-8 text-center animate-pulse">Cargando gastos...</td></tr>';

    try {
        const res = await fetch(`${URLROOT}/reportes/detallado?desde=${desde}&hasta=${hasta}`);
        if (!res.ok) throw new Error(`Error del servidor: ${res.status}`);
        const result = await res.json();

        const responseData = result.success ? result.data : result;

        if (responseData && responseData.ventas) {
            rawAuditData = responseData;
            renderAuditoriaLista(rawAuditData.ventas);

            if (contCompras && rawAuditData.compras) {
                contCompras.innerHTML = (rawAuditData.compras || []).length ? rawAuditData.compras.map(c => `
                    <tr class="hover:bg-slate-50 border-b border-slate-100">
                        <td class="p-3 text-xs font-bold text-slate-400 uppercase">${new Date(c.fecha).toLocaleDateString()}</td>
                        <td class="p-3 text-sm font-black text-rose-600 uppercase">${c.proveedor}</td>
                        <td class="p-3 text-sm font-bold text-slate-600 uppercase">${c.descripcion}</td>
                        <td class="p-3 text-center text-sm font-bold text-slate-500">${c.cantidad}</td>
                        <td class="p-3 text-right text-sm font-bold text-slate-500">${AppUtils.formatCurrency(c.costo_unitario)}</td>
                        <td class="p-3 text-right text-base font-black text-rose-600">${AppUtils.formatCurrency(c.cantidad * c.costo_unitario)}</td>
                    </tr>`).join('') : '<tr><td colspan="6" class="p-8 text-center text-slate-400 italic">No hay compras registradas</td></tr>';
            }

            if (contGastos && rawAuditData.gastos) {
                contGastos.innerHTML = (rawAuditData.gastos || []).length ? rawAuditData.gastos.map(g => `
                    <tr class="hover:bg-slate-50 border-b border-slate-100">
                        <td class="p-3 text-xs font-bold text-slate-400 uppercase">${new Date(g.fecha).toLocaleDateString()}</td>
                        <td class="p-3"><span class="px-2 py-0.5 rounded text-[9px] font-black bg-slate-100 text-slate-500 uppercase">${g.categoria}</span></td>
                        <td class="p-3 text-sm font-bold text-slate-700 uppercase">${g.descripcion}</td>
                        <td class="p-3 text-sm font-bold text-slate-600 uppercase">${g.metodo_pago || 'EFECTIVO'}</td>
                        <td class="p-3 text-right text-base font-black text-rose-600">${AppUtils.formatCurrency(g.monto)}</td>
                    </tr>`).join('') : '<tr><td colspan="5" class="p-8 text-center text-slate-400 italic">No hay gastos registrados</td></tr>';
            }
        } else {
            if (auditContainer) auditContainer.innerHTML = '<div class="py-20 text-center text-slate-400 font-bold uppercase tracking-widest">Error al procesar los datos del servidor</div>';
        }

        if (window.lucide) lucide.createIcons();
    } catch (e) {
        console.error(e);
        if (auditContainer) auditContainer.innerHTML = '<div class="py-20 text-center text-rose-500 font-bold uppercase tracking-widest">Error de conexión con el servidor</div>';
    }
};

function renderAuditoriaLista(items) {
    const container = document.getElementById('audit-list-container');
    if (!container || !Array.isArray(items)) {
        container.innerHTML = '<div class="py-20 text-center text-slate-400 italic">Datos de trabajos inválidos</div>';
        return;
    }

    const meses = ["ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE"];

    const groupedByMonth = items.reduce((acc, current) => {
        if (!current.fecha) return acc;
        const d = new Date(current.fecha.replace(' ', 'T'));
        const monthKey = `${meses[d.getMonth()]} ${d.getFullYear()}`;
        if (!acc[monthKey]) acc[monthKey] = [];
        acc[monthKey].push(current);
        return acc;
    }, {});

    if (Object.keys(groupedByMonth).length === 0) {
        container.innerHTML = `
            <div class="text-center py-20 text-slate-400 italic font-medium uppercase tracking-widest flex flex-col items-center gap-2">
                <i data-lucide="info" class="w-10 h-10 text-slate-200 mb-4"></i>
                <span>No hay registros de trabajos en este periodo</span>
            </div>`;
        if (window.lucide) lucide.createIcons();
        return;
    }

    let html = '';
    let debtorsSummary = {};

    for (const [month, monthItems] of Object.entries(groupedByMonth)) {
        const invoices = monthItems.reduce((acc, current) => {
            const key = `V-${current.id}`;
            if (!acc[key]) {
                acc[key] = {
                    id: current.id,
                    fecha: current.fecha,
                    vehiculo: current.modelo_vehiculo || 'GENERAL',
                    placa: current.placa || '---',
                    cliente: current.cliente_nombre || 'VENTA RÁPIDA',
                    cliente_telefono: current.cliente_telefono || '',
                    usuario: current.mecanico_nombre || current.usuario_nombre || 'SISTEMA',
                    iva: parseFloat(current.iva_monto || 0),
                    subtotal: parseFloat(current.subtotal || 0),
                    total: parseFloat(current.total || 0),
                    status: current.status,
                    pago_efectivo: parseFloat(current.pago_efectivo || 0),
                    pago_transferencia: parseFloat(current.pago_transferencia || 0),
                    saldo_pendiente: parseFloat(current.saldo_pendiente || 0),
                    items: []
                };
            }
            acc[key].items.push(current);
            return acc;
        }, {});

        const totalInvoices = Object.keys(invoices).length;

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
            const totalFactura = f.total > 0 ? f.total : f.items.reduce((sum, item) => sum + (item.cantidad * item.precio_unitario), 0);
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
                <div class="flex flex-wrap justify-between items-start gap-6 mb-5 w-full">
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
                        <p class="text-base font-bold text-slate-400 uppercase tracking-widest">
                                <span class="text-slate-600">${f.cliente}</span>
                                ${f.cliente_telefono ? `<span class="ml-2 text-xs font-black text-navy-blue/40 font-mono">[TEL: ${f.cliente_telefono}]</span>` : ''}
                                <span class="text-slate-200 mx-2">|</span>
                                ${new Date(f.fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' })}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-sm font-black text-slate-400 uppercase tracking-widest mb-1 leading-none">Técnico: <span class="text-navy-blue font-black">${f.usuario}</span></p>
                            <p class="text-sm font-black text-slate-400 uppercase tracking-widest mb-1 leading-none">Total Factura: <span class="text-slate-600">${AppUtils.formatCurrency(totalFactura)}</span></p>
                            <div class="flex items-center justify-end gap-3">
                                <span class="text-sm font-black text-slate-300 uppercase tracking-tighter">${isCredit ? 'SALDO DEUDOR' : 'TOTAL TRABAJO'}</span>
                                <span class="text-3xl font-black ${isCredit ? 'text-rose-600' : 'text-emerald-600'} tracking-tighter">${AppUtils.formatCurrency(isCredit ? f.saldo_pendiente : totalFactura)}</span>
                                ${isCredit ? `<span class="text-[10px] font-black bg-rose-100 text-rose-600 px-2 py-0.5 rounded-full uppercase tracking-tighter border border-rose-200">En Crédito</span>` : ''}
                            </div>
                        </div>

                        <!-- 🖨 IMPRIMIR FACTURA -->
                        <button onclick="window.open('${URLROOT}/facturacion/imprimir/${f.id}', '_blank')" 
                                class="p-3 rounded-xl bg-white border border-slate-100 text-slate-400 hover:text-emerald-600 hover:border-emerald-200 hover:bg-emerald-50 transition-all shadow-sm" 
                                title="Imprimir Factura PDF">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                        </button>
                        <!-- 👁 VER DETALLE -->
                        <button onclick="verDetalleVenta(${f.id})" 
                                class="p-3 rounded-xl bg-white border border-slate-100 text-slate-400 hover:text-navy-blue hover:border-navy-blue hover:bg-slate-50 transition-all shadow-sm"
                                title="Ver detalle">
                            <i data-lucide="maximize-2" class="w-4 h-4"></i>
                        </button>
                        <!-- ↩️ DEVOLUCIÓN -->
                        <button onclick="iniciarDevolucion(${f.id}, '${f.fecha}')" 
                                class="p-3 rounded-xl bg-white border border-slate-100 text-slate-400 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm" 
                                title="Devolución">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </button>
                        <!-- 💰 ABONAR (solo si tiene crédito) -->
                        ${isCredit ? `
                            <button onclick="registrarAbonoCliente(${f.id}, ${f.saldo_pendiente})" 
                                    class="p-3 rounded-xl bg-rose-500 text-white hover:bg-rose-600 transition-all shadow-md flex items-center gap-2 group/btn" 
                                    title="Registrar Pago">
                                <i data-lucide="hand-coins" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                <span class="text-[10px] font-black uppercase">Abonar</span>
                            </button>
                        ` : ''}
                    </div>
                </div>

                <div class="pl-[80px]">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-50">
                                <th class="pb-2 text-base font-black text-slate-300 uppercase tracking-widest">Cant.</th>
                                <th class="pb-2 text-base font-black text-slate-300 uppercase tracking-widest">Descripción detallada</th>
                                <th class="pb-2 text-base font-black text-slate-300 uppercase tracking-widest text-right">P. Unitario</th>
                                <th class="pb-2 text-base font-black text-slate-300 uppercase tracking-widest text-right">Subtotal</th>
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
            if (window.lucide) lucide.createIcons();
            debtorsContainer.classList.remove('hidden');
        } else {
            debtorsContainer.classList.add('hidden');
        }
    }
    container.innerHTML = html;
    if (window.lucide) lucide.createIcons();
}

// ═══════════════════════════════════════════════════════════════════
//  MODAL DE DETALLE — CON BOTÓN IMPRIMIR FACTURA
// ═══════════════════════════════════════════════════════════════════

window.verDetalleVenta = async (ventaId) => {
    const idLimpio = String(ventaId).replace(/\D/g, '');

    try {
        const res = await fetch(`${URLROOT}/historial/detalle/${idLimpio}`);
        const result = await res.json();

        const venta = (result.success && result.data) ? result.data : result;

        if (!venta || (!venta.id && !venta.venta_id)) {
            return AppUtils.showToast('No se encontró el detalle de la venta #' + idLimpio, 'error');
        }

        const ventaIdFinal = venta.id || idLimpio;

        Swal.fire({
            title: `<span class="text-sm uppercase text-slate-400 font-black tracking-widest">Detalle de Operación</span><br><span class="text-navy-blue text-2xl">FACTURA #${ventaIdFinal}</span>`,
            html: `
                <div class="text-left space-y-6 pt-4">
                    <div class="grid grid-cols-2 gap-6 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <div class="space-y-1">
                            <p class="text-xs font-black text-slate-400 uppercase">Fecha Realizada</p>
                            <p class="text-sm font-bold text-slate-700">${venta.fecha ? new Date(venta.fecha).toLocaleString('es-CO') : 'N/A'}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-black text-slate-400 uppercase">Personal que Atendió</p>
                            <p class="text-base font-black text-navy-blue uppercase">${venta.mecanico_nombre || venta.usuario_nombre || 'SISTEMA'}</p>
                            ${venta.mecanico_nombre && venta.mecanico_nombre !== venta.usuario_nombre ? `<p class="text-[8px] text-slate-400 font-bold uppercase">Facturó: ${venta.usuario_nombre}</p>` : ''}
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-black text-slate-400 uppercase">Cliente / Propietario</p>
                            <p class="text-sm font-bold text-slate-700 uppercase">${venta.cliente_nombre || 'VENTA RÁPIDA'}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-black text-slate-400 uppercase">Vehículo</p>
                            <p class="text-sm font-bold text-slate-700 uppercase">${venta.modelo_vehiculo || 'N/A'} <span class="text-blue-500 font-mono font-black">[${venta.placa || '---'}]</span></p>
                        </div>
                    </div>

                    ${(venta.observaciones || venta.diagnostico_entrada) ? `
                        <div class="space-y-1">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Observaciones / Detalles Técnicos</p>
                            <div class="p-4 bg-amber-50 border border-amber-100 rounded-2xl text-xs font-bold text-amber-800 italic uppercase leading-relaxed shadow-sm">
                                ${venta.diagnostico_entrada ? `<div><span class="text-[9px] opacity-60">INGRESO:</span> ${venta.diagnostico_entrada}</div>` : ''}
                                ${venta.observaciones && (venta.observaciones !== venta.diagnostico_entrada) ? `
                                    <div class="${venta.diagnostico_entrada ? 'mt-2 pt-2 border-t border-amber-200/50' : ''}">
                                        <span class="text-[9px] opacity-60">SALIDA:</span> ${venta.observaciones}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    ` : ''}

                    <div class="max-h-60 overflow-y-auto border border-slate-200 rounded-lg p-2 bg-white shadow-inner">
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="text-slate-400 border-b">
                                    <th class="text-left p-2 uppercase tracking-tighter">Descripción</th>
                                    <th class="text-center p-2 uppercase tracking-tighter">Cant.</th>
                                    <th class="text-right p-2 uppercase tracking-tighter">P. Unit.</th>
                                    <th class="text-right p-2 uppercase tracking-tighter">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                ${(venta.items || []).map(i => `
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-2 text-slate-800 font-bold uppercase">${i.descripcion}</td>
                                        <td class="p-2 text-center font-bold text-slate-500">${i.cantidad}</td>
                                        <td class="p-2 text-right text-slate-500">${AppUtils.formatCurrency(i.precio_unitario)}</td>
                                        <td class="p-2 text-right font-black text-slate-800">${AppUtils.formatCurrency(i.cantidad * i.precio_unitario)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                            <p class="text-slate-400 font-bold uppercase mb-1">Efectivo</p>
                            <p class="font-black text-slate-700 text-base">${AppUtils.formatCurrency(venta.pago_efectivo || 0)}</p>
                        </div>
                        <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                            <p class="text-slate-400 font-bold uppercase mb-1">Transf.</p>
                            <p class="font-black text-slate-700 text-base">${AppUtils.formatCurrency(venta.pago_transferencia || 0)}</p>
                        </div>
                        <div class="p-2 ${(parseFloat(venta.saldo_pendiente) > 0) ? 'bg-rose-50 border-rose-100' : 'bg-slate-50 border-slate-100'} rounded-xl border">
                            <p class="${(parseFloat(venta.saldo_pendiente) > 0) ? 'text-rose-400' : 'text-slate-400'} font-bold uppercase mb-1">Deuda</p>
                            <p class="font-black ${(parseFloat(venta.saldo_pendiente) > 0) ? 'text-rose-600' : 'text-slate-700'} text-base">${AppUtils.formatCurrency(venta.saldo_pendiente || 0)}</p>
                        </div>
                    </div>

                    <div class="bg-navy-blue p-5 rounded-2xl space-y-3 text-white">
                        <div class="flex justify-between items-center text-sm opacity-70">
                            <span class="font-bold uppercase">Subtotal Neto</span>
                            <span class="font-bold">${AppUtils.formatCurrency(venta.subtotal || 0)}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm opacity-70">
                            <span class="font-bold uppercase">Impuestos (IVA)</span>
                            <span class="font-bold">${AppUtils.formatCurrency(venta.iva_monto || 0)}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm text-emerald-400 pt-1 border-t border-white/5">
                            <span class="font-bold uppercase">Total Abonado</span>
                            <span class="font-bold">${AppUtils.formatCurrency(parseFloat(venta.pago_efectivo || 0) + parseFloat(venta.pago_transferencia || 0))}</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-white/10">
                            <span class="font-black uppercase text-sm tracking-widest text-neon-green">Total Final de la Venta</span>
                            <span class="text-2xl font-black">${AppUtils.formatCurrency(venta.total || 0)}</span>
                        </div>
                    </div>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'IMPRIMIR FACTURA',
            confirmButtonColor: '#10b981',
            showCancelButton: true,
            cancelButtonText: 'CERRAR',
            cancelButtonColor: '#64748b',
            width: '520px',
            didOpen: () => lucide.createIcons()
        }).then((result) => {
            if (result.isConfirmed) {
                window.open(`${URLROOT}/facturacion/imprimir/${ventaIdFinal}`, '_blank');
            }
        });
    } catch (e) {
        console.error(e);
        AppUtils.showToast('Error al conectar con el servidor', 'error');
    }
};

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
                                ${(data.items || []).map(i => `
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

window.verDetallePagoHistorial = async (id) => {
    try {
        const res = await fetch(`${URLROOT}/reportes/detallePagoNomina/${id}`);
        const result = await res.json();

        if (result.success && result.data) {
            const p = result.data;
            Swal.fire({
                title: `<span class="text-[10px] uppercase text-slate-400 font-black tracking-widest">Resumen de Pago</span><br><span class="text-navy-blue">RECIBO #${p.id}</span>`,
                html: `
                    <div class="text-left space-y-4 pt-4">
                        <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl text-xs">
                            <div><p class="text-slate-400 font-bold uppercase">Empleado:</p><p class="font-black text-navy-blue">${p.staff_nombre}</p></div>
                            <div><p class="text-slate-400 font-bold uppercase">Fecha:</p><p class="font-black text-slate-700">${new Date(p.fecha).toLocaleString()}</p></div>
                            <div><p class="text-slate-400 font-bold uppercase">Tipo:</p><p class="font-black text-slate-700">${p.tipo}</p></div>
                            <div><p class="text-slate-400 font-bold uppercase">Método:</p><p class="font-black text-slate-700">${p.metodo_pago}</p></div>
                        </div>
                        ${p.trabajos && p.trabajos.length > 0 ? `
                        <div class="border rounded-lg overflow-hidden">
                            <table class="w-full text-[10px]">
                                <thead class="bg-slate-50"><tr><th class="p-2 text-left">Trabajo (Factura)</th><th class="p-2 text-right">Monto</th></tr></thead>
                                <tbody class="divide-y">
                                    ${p.trabajos.map(t => {
                    const vehicleDetails = [];
                    if (t.placa) vehicleDetails.push(t.placa);
                    if (t.modelo_vehiculo && t.modelo_vehiculo !== 'N/A') vehicleDetails.push(t.modelo_vehiculo);
                    const vehicleDisplay = vehicleDetails.length > 0 ? `(${vehicleDetails.join(' - ')})` : '';
                    return `<tr><td class="p-2">${t.descripcion} <span class="text-[9px] text-slate-400 font-bold">${vehicleDisplay}</span> <span class="font-mono text-navy-blue">#${t.venta_id}</span></td><td class="p-2 text-right font-bold">${AppUtils.formatCurrency(t.precio_unitario)}</td></tr>`;
                }).join('')}
                                </tbody>
                            </table>
                        </div>` : ''}
                        <div class="bg-navy-blue p-4 rounded-xl text-white flex justify-between items-center">
                            <span class="text-xs font-bold uppercase">Total Cancelado:</span>
                            <span class="text-2xl font-black text-neon-green">${AppUtils.formatCurrency(p.monto)}</span>
                        </div>
                        ${p.notas ? `<p class="text-[10px] italic text-slate-500">Nota: ${p.notas}</p>` : ''}
                    </div>`,
                showCloseButton: true,
                showConfirmButton: false
            });
        }
    } catch (e) { console.error(e); }
};

// ═══════════════════════════════════════════════════════════════════
//  IMPRIMIR (AUDITORÍA / GASTOS)
// ═══════════════════════════════════════════════════════════════════
window.imprimirAuditoriaCompleta = () => {
    const desde = document.getElementById('rep-desde')?.value || '';
    const hasta = document.getElementById('rep-hasta')?.value || '';
    const search = document.getElementById('search-audit')?.value || '';

    AppUtils.showToast("Generando reporte de auditoría...", "info");
    window.open(`${URLROOT}/reportes/imprimirAuditoria?desde=${desde}&hasta=${hasta}&q=${search}`, '_blank');
};

window.imprimirGastosCompleto = () => {
    const desde = document.getElementById('rep-desde')?.value || '';
    const hasta = document.getElementById('rep-hasta')?.value || '';
    const search = document.getElementById('search-report')?.value || '';

    AppUtils.showToast("Generando reporte de gastos...", "info");
    window.open(`${URLROOT}/reportes/imprimirGastos?desde=${desde}&hasta=${hasta}&q=${search}`, '_blank');
};

window.printVenta = (id) => {
    AppUtils.showToast('Generando documento...', 'info');
    window.open(`${URLROOT}/facturacion/imprimir/${id}`, '_blank');
};

window.imprimirReciboPago = function (pagoId) {
    AppUtils.showToast("Abriendo comprobante...", "info");
    window.open(`${URLROOT}/reportes/imprimirRecibo/${pagoId}`, '_blank');
};

window.reimprimirPagoNomina = function (id) {
    AppUtils.showToast("Abriendo copia del recibo...", "info");
    window.open(`${URLROOT}/reportes/imprimirRecibo/${id}`, '_blank');
};

// ═══════════════════════════════════════════════════════════════════
//  MODAL DE ABONO
// ═══════════════════════════════════════════════════════════════════
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
                    <input id="pay-amount" type="text" class="w-full p-3 bg-slate-50 border rounded-xl font-black text-navy-blue" value="${parseFloat(saldoPendiente).toFixed(2)}">
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
            AppUtils.showLoading('Registrando pago...');

            const res = await fetch(`${URLROOT}/facturacion/registrarAbono`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(formValues)
            });

            let data;
            try {
                data = await res.json();
            } catch (parseErr) {
                const raw = await res.text();
                console.error('Respuesta no JSON del servidor:', raw);
                AppUtils.hideLoading();
                AppUtils.showToast('Respuesta inválida del servidor. Revisa la consola (F12).', 'error');
                return;
            }

            AppUtils.hideLoading();

            if (data.success) {
                AppUtils.showToast(data.mensaje || 'Pago registrado correctamente');
                if (activeReportTab === 'detallado') cargarReporteDetallado();
                else if (activeReportTab === 'resumen') { if (window.handler_reporte_flujo) window.handler_reporte_flujo.reload(); }
                else if (activeReportTab === 'cartera') window.cargarCartera();

                if (typeof initCreditNotifications === 'function') {
                    initCreditNotifications();
                }
            } else {
                AppUtils.showToast(data.mensaje || 'Error al registrar el pago', 'error');
            }
        } catch (e) {
            AppUtils.hideLoading();
            console.error('Error al registrar abono:', e);
            AppUtils.showToast('Error de conexión', 'error');
        }
    }
};

// ═══════════════════════════════════════════════════════════════════
//  RENTABILIDAD
// ═══════════════════════════════════════════════════════════════════
window.cargarRentabilidad = async function () {
    const desde = document.getElementById('rep-desde')?.value || '';
    const hasta = document.getElementById('rep-hasta')?.value || '';
    const tbody = document.getElementById('rentabilidad-body');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-slate-400 italic animate-pulse uppercase font-black">Analizando Rentabilidad...</td></tr>';

    try {
        const res = await fetch(`${URLROOT}/reportes/rentabilidad?desde=${desde}&hasta=${hasta}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const result = await res.json();
        if (result.success && result.data) window.renderRentabilidad(result.data);
    } catch (e) { console.error(e); }
};

window.renderRentabilidad = (data) => {
    const tbody = document.getElementById('rentabilidad-body');
    if (!tbody) return;
    tbody.innerHTML = (Array.isArray(data) && data.length > 0) ? data.map(r => {
        const margen = r.ingreso_total > 0 ? ((r.utilidad_bruta / r.ingreso_total) * 100).toFixed(2) : 0;
        return `
            <tr class="hover:bg-slate-50 border-b border-slate-100 transition-colors">
                <td class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">${r.tipo}</td>
                <td class="px-6 py-4 text-sm font-bold text-slate-700 text-center">${r.cantidad_operaciones}</td>
                <td class="px-6 py-4 text-sm font-bold text-slate-600 text-right">${AppUtils.formatCurrency(r.ingreso_total)}</td>
                <td class="px-6 py-4 text-sm font-bold text-slate-400 text-right">${AppUtils.formatCurrency(r.costo_total)}</td>
                <td class="px-6 py-4 text-sm font-black text-emerald-600 text-right">${AppUtils.formatCurrency(r.utilidad_bruta)}</td>
                <td class="px-6 py-4 text-right">
                    <span class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-600 font-black text-xs">${margen}%</span>
                </td>
            </tr>`;
    }).join('') : '<tr><td colspan="6" class="text-center py-20 text-slate-400 italic font-bold uppercase">Sin datos de rentabilidad</td></tr>';
    if (window.lucide) lucide.createIcons();
};

// ═══════════════════════════════════════════════════════════════════
//  FILTRO AUDITORÍA
// ═══════════════════════════════════════════════════════════════════
function filtrarAuditoria(term) {
    if (!rawAuditData) return;
    const t = term.toLowerCase();

    const filtrados = (rawAuditData.ventas || []).filter(v =>
        (v.modelo_vehiculo && v.modelo_vehiculo.toLowerCase().includes(t)) ||
        (v.placa && v.placa.toLowerCase().includes(t)) ||
        (v.descripcion && v.descripcion.toLowerCase().includes(t)) ||
        (v.cliente_nombre && v.cliente_nombre.toLowerCase().includes(t)) ||
        (String(v.id).includes(t))
    ).map(v => ({ ...v, tipo: 'VENTA' }));

    renderAuditoriaLista(filtrados);
}

// ═══════════════════════════════════════════════════════════════════
//  NÓMINA
// ═══════════════════════════════════════════════════════════════════
window.cargarNomina = async function () {
    const staffId = document.getElementById('staff-selector')?.value;
    const desde = document.getElementById('rep-desde')?.value;
    const hasta = document.getElementById('rep-hasta')?.value;

    if (!staffId || staffId === "") {
        const selector = document.getElementById('staff-selector');
        if (!selector) return;
        try {
            const res = await fetch(`${URLROOT}/reportes/simple_staff`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const result = await res.json();
            if (result && result.data) {
                selector.innerHTML = '<option value="">-- SELECCIONE UN EMPLEADO --</option>' +
                    result.data.map(s => `<option value="${s.id}">${s.nombre} (${s.cargo})</option>`).join('');
            }
        } catch (e) {
            console.error("Error al cargar lista de personal:", e);
            selector.innerHTML = '<option value="">-- ERROR AL CARGAR PERSONAL --</option>';
        }
        return;
    }

    try {
        const res = await fetch(`${URLROOT}/reportes/nomina?staff_id=${staffId}&desde=${desde}&hasta=${hasta}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const result = await res.json();

        if (result.success && result.data) {
            const trabajos = result.data.trabajos || [];
            const pagos = result.data.pagos || [];

            const tBody = document.getElementById('nomina-trabajos-body');
            let totalGeneral = 0;
            let totalPendiente = 0;

            tBody.innerHTML = trabajos.length > 0 ? trabajos.map(t => {
                const isPaid = t.pago_nomina_id !== null;
                const monto = parseFloat(t.monto_trabajo);

                if (!isPaid) totalPendiente += monto;
                totalGeneral += monto;

                return `
                <tr class="${isPaid ? 'opacity-40 grayscale bg-slate-50' : 'hover:bg-slate-50'} transition-all border-b border-slate-100">
                    <td class="px-4 py-3 text-center w-10">
                        ${!isPaid ? `<input type="checkbox" class="work-checkbox w-4 h-4 rounded border-slate-300 text-navy-blue focus:ring-neon-green" value="${t.detalle_id}" data-monto="${monto}" checked onchange="window.recalcularSeleccionNomina()">` : `<i data-lucide="check-circle-2" class="w-4 h-4 text-slate-400 mx-auto"></i>`}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-col">
                            <span class="text-sm md:text-lg font-black ${isPaid ? 'text-slate-500' : 'text-navy-blue'} uppercase tracking-tight">${t.descripcion}</span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase">
                                <span class="font-mono text-slate-500">#${t.venta_id}</span> | ${new Date(t.fecha).toLocaleDateString()} | ${t.placa} - ${t.modelo_vehiculo}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right font-black ${isPaid ? 'text-slate-400' : 'text-emerald-600'} text-lg md:text-2xl w-32">
                        ${AppUtils.formatCurrency(monto)}
                    </td>
                </tr>`;
            }).join('') : '<tr><td colspan="4" class="p-12 text-center text-slate-400 italic font-bold uppercase tracking-widest">Sin trabajos registrados</td></tr>';

            const pBody = document.getElementById('nomina-pagos-body');
            let totalPagos = 0;
            let totalAdelantos = 0;
            pBody.innerHTML = pagos.length > 0 ? pagos.map(p => {
                const monto = parseFloat(p.monto);
                totalPagos += monto;
                if (p.tipo === 'ADELANTO') totalAdelantos += monto;

                return `<tr>
                    <td class="px-4 py-3 font-mono">${new Date(p.fecha).toLocaleDateString()}</td>
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-[9px] font-black ${p.tipo === 'ADELANTO' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'} uppercase">${p.tipo}</span></td>
                    <td class="px-4 py-3 text-right font-black text-rose-600 text-lg md:text-2xl">${AppUtils.formatCurrency(p.monto)}</td>
                    <td class="px-4 py-3 text-right">
                        <button onclick="window.imprimirReciboPago(${p.id})" class="p-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-navy-blue hover:text-white transition-colors" title="Ver Recibo"><i data-lucide="printer" class="w-4 h-4"></i></button>
                    </td>
                </tr>`;
            }).join('') : '<tr><td colspan="4" class="p-8 text-center text-slate-400 italic font-bold">Sin pagos registrados</td></tr>';

            const elTrabajos = document.getElementById('nomina-total-trabajos');
            const elAdelantos = document.getElementById('nomina-total-adelantos');
            const elPendiente = document.getElementById('nomina-total-pendiente');

            const saldoNetoReal = totalPendiente - totalAdelantos;

            if (elTrabajos) { elTrabajos.textContent = AppUtils.formatCurrency(totalGeneral); elTrabajos.classList.add('text-3xl', 'md:text-5xl', 'font-black', 'text-navy-blue', 'tracking-tighter'); }
            if (elAdelantos) { elAdelantos.textContent = AppUtils.formatCurrency(totalAdelantos); elAdelantos.classList.add('text-3xl', 'md:text-5xl', 'font-black', 'text-rose-600', 'tracking-tighter'); }
            if (elPendiente) { elPendiente.textContent = AppUtils.formatCurrency(saldoNetoReal > 0 ? saldoNetoReal : 0); elPendiente.classList.add('text-4xl', 'md:text-6xl', 'font-black', 'text-neon-green', 'tracking-tighter'); }

            window.currentNominaPendiente = saldoNetoReal;
        }
        if (window.lucide) lucide.createIcons();
    } catch (e) { console.error(e); }
};

window.recalcularSeleccionNomina = function () {
    const checkboxes = document.querySelectorAll('.work-checkbox:checked');
    let total = 0;
    checkboxes.forEach(cb => total += parseFloat(cb.dataset.monto));

    const elPendiente = document.getElementById('nomina-total-pendiente');
    if (elPendiente) elPendiente.textContent = AppUtils.formatCurrency(total);

    const elTrabajos = document.getElementById('nomina-total-trabajos');
    if (elTrabajos) elTrabajos.textContent = AppUtils.formatCurrency(total);

    window.currentNominaPendiente = total;
};

window.openModalPago = async function () {
    const staffId = document.getElementById('staff-selector').value;
    if (!staffId) return AppUtils.showToast("Seleccione un empleado primero", "warning");

    const { value: formValues } = await Swal.fire({
        title: `<span class="text-xs uppercase text-slate-400 font-black">REGISTRAR PAGO</span>`,
        html: `
            <div class="text-left space-y-4 pt-4">
                <div class="p-4 bg-slate-900 rounded-2xl border-l-4 border-neon-green shadow-inner">
                    <span class="text-[10px] font-black text-neon-green uppercase tracking-widest block mb-1">Base de Mano de Obra (Pendiente)</span>
                    <span class="text-3xl font-black text-white">${AppUtils.formatCurrency(window.currentNominaPendiente)}</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-slate-400 uppercase">Modo de Cálculo</span>
                        <span id="label-modo" class="text-xs font-bold text-navy-blue uppercase">Monto Fijo</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="pago-modo-switch" class="sr-only peer" onchange="window.toggleModoPago(this)">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-neon-green"></div>
                    </label>
                </div>

                <div>
                    <label id="label-factor" class="block text-[10px] font-black text-slate-400 uppercase mb-1">Valor a Ingresar</label>
                    <input id="pago-factor" type="number" step="0.01" class="w-full p-3 bg-white border border-slate-300 rounded-xl font-black text-navy-blue text-lg focus:ring-2 focus:ring-neon-green outline-none" placeholder="0.00" oninput="window.recalcularVistaPreviaPago()">
                </div>

                <div class="p-3 bg-slate-100 rounded-xl border border-dashed border-slate-300 flex justify-between items-center">
                    <span class="text-[10px] font-black text-slate-500 uppercase">Total a Entregar:</span>
                    <span id="pago-total-preview" class="text-xl font-black text-navy-blue">$0.00</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Tipo</label>
                        <select id="pago-tipo" class="w-full p-3 bg-slate-50 border rounded-xl font-bold text-xs uppercase">
                            <option value="PAGO_NOMINA">PAGO NÓMINA</option>
                            <option value="ADELANTO">ADELANTO</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Método</label>
                        <select id="pago-metodo" class="w-full p-3 bg-slate-50 border rounded-xl font-bold text-xs uppercase">
                            <option value="EFECTIVO">EFECTIVO</option>
                            <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Notas / Observaciones</label>
                    <textarea id="pago-notas" class="w-full p-2 bg-slate-50 border rounded-lg text-xs uppercase" rows="2" placeholder="Ej: Pago semana 4..."></textarea>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'PROCESAR PAGO',
        confirmButtonColor: '#10b981',
        didOpen: () => {
            window.recalcularVistaPreviaPago();
        },
        preConfirm: () => {
            const factor = parseFloat(document.getElementById('pago-factor').value);
            const modo = document.getElementById('pago-modo-switch').checked ? 'PORCENTAJE' : 'FIJO';

            if (isNaN(factor) || factor <= 0) return Swal.showValidationMessage('Ingrese un valor válido');

            const detallesIds = Array.from(document.querySelectorAll('.work-checkbox:checked')).map(cb => cb.value);

            return {
                staff_id: staffId,
                monto_base: window.currentNominaPendiente,
                modo_calculo: modo,
                factor_calculo: factor,
                detalles_ids: detallesIds,
                tipo: document.getElementById('pago-tipo').value,
                metodo_pago: document.getElementById('pago-metodo').value,
                notas: document.getElementById('pago-notas').value.trim().toUpperCase()
            };
        }
    });

    if (formValues) {
        try {
            AppUtils.showLoading('Procesando pago...');
            const res = await fetch(`${URLROOT}/reportes/registrarPagoNomina`, {
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
                AppUtils.showToast('Pago registrado correctamente');
                window.cargarNomina();
                if (activeReportTab === 'historial_nomina') window.cargarHistorialNomina();
            } else {
                AppUtils.showToast(result.mensaje || 'Error al procesar el pago', 'error');
            }
        } catch (error) {
            AppUtils.hideLoading();
            AppUtils.showToast('Error de conexión', 'error');
        }
    }
};

window.recalcularVistaPreviaPago = function () {
    const base = window.currentNominaPendiente || 0;
    const factor = parseFloat(document.getElementById('pago-factor')?.value) || 0;
    const isPorcentaje = document.getElementById('pago-modo-switch')?.checked;
    const previewEl = document.getElementById('pago-total-preview');

    let total = isPorcentaje ? (base * (factor / 100)) : factor;

    if (previewEl) previewEl.innerText = AppUtils.formatCurrency(total);
};

window.toggleModoPago = function (el) {
    const labelModo = document.getElementById('label-modo');
    const labelFactor = document.getElementById('label-factor');
    const inputFactor = document.getElementById('pago-factor');

    if (el.checked) {
        if (labelModo) labelModo.innerText = "Porcentaje (%)";
        if (labelFactor) labelFactor.innerText = "Porcentaje a aplicar (%)";
        if (inputFactor) inputFactor.placeholder = "Ej: 30";
    } else {
        if (labelModo) labelModo.innerText = "Monto Fijo";
        if (labelFactor) labelFactor.innerText = "Monto a Cancelar ($)";
        if (inputFactor) inputFactor.placeholder = "0.00";
    }
    window.recalcularVistaPreviaPago();
};

// ═══════════════════════════════════════════════════════════════════
//  HISTORIAL DE NÓMINA
// ═══════════════════════════════════════════════════════════════════
window.cargarHistorialNomina = async () => {
    const desde = document.getElementById('rep-desde')?.value || '';
    const hasta = document.getElementById('rep-hasta')?.value || '';
    const tbody = document.getElementById('historial-nomina-body');

    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center animate-pulse">Cargando historial de pagos...</td></tr>';

    try {
        const res = await fetch(`${URLROOT}/reportes/historialPagosNomina?desde=${desde}&hasta=${hasta}`);
        const result = await res.json();

        if (result.success && result.data) {
            tbody.innerHTML = result.data.length > 0 ? result.data.map(p => `
            <tr class="hover:bg-slate-50 border-b border-slate-100 transition-colors">
                <td class="px-4 py-4 font-mono text-xs text-slate-500">#${p.id}</td>
                <td class="px-4 py-4 text-sm font-bold text-slate-600">${new Date(p.fecha).toLocaleDateString()}</td>
                <td class="px-4 py-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-black text-navy-blue uppercase">${p.staff_nombre}</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase">${p.staff_cargo}</span>
                    </div>
                </td>
                <td class="px-4 py-4">
                    <span class="px-2 py-0.5 rounded text-[9px] font-black ${p.tipo === 'ADELANTO' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'} uppercase">${p.tipo}</span>
                </td>
                <td class="px-4 py-4 text-right font-black text-navy-blue">${AppUtils.formatCurrency(p.monto)}</td>
                <td class="px-4 py-4 text-right">
                    <div class="flex justify-end gap-2">
                        <button onclick="verDetallePagoHistorial(${p.id})" class="p-2 text-slate-400 hover:text-navy-blue transition-colors" title="Ver Resumen"><i data-lucide="eye" class="w-4 h-4"></i></button>
                        <button onclick="reimprimirPagoNomina(${p.id})" class="p-2 text-slate-400 hover:text-rose-600 transition-colors" title="Reimprimir Copia"><i data-lucide="printer" class="w-4 h-4"></i></button>
                    </div>
                </td>
            </tr>
        `).join('') : '<tr><td colspan="6" class="p-12 text-center text-slate-400 italic font-bold uppercase tracking-widest">No hay pagos registrados en este periodo</td></tr>';

            if (window.lucide) lucide.createIcons();
        }
    } catch (e) {
        console.error("Error al cargar historial nómina:", e);
    }
};

// ═══════════════════════════════════════════════════════════════════
//  EXPORTACIONES DE CARTERA
// ═══════════════════════════════════════════════════════════════════
window.exportarCarteraProveedoresPdf = function () {
    AppUtils.showToast("Generando reporte de proveedores...", "info");
    window.open(`${URLROOT}/reportes/imprimirCarteraProveedores`, '_blank');
};

window.imprimirReporteProveedorIndividual = function (id) {
    if (!id) return;
    AppUtils.showToast("Generando estado de cuenta...", "info");
    window.open(`${URLROOT}/reportes/imprimirReporteProveedor/${id}`, '_blank');
};

window.exportarCarteraExcel = function () {
    window.location.href = `${URLROOT}/reportes/exportarCarteraExcel`;
};

window.exportarCarteraPdf = async function () {
    AppUtils.showToast("Generando PDF de Cartera...", "info");
    try {
        const res = await fetch(`${URLROOT}/reportes/exportarCarteraPdf`);
        if (!res.ok) throw new Error("Error en la respuesta del servidor");

        const result = await res.json();
        if (result.success) {
            window.open(result.pdf_url, '_blank');
        } else {
            AppUtils.showToast(result.mensaje || "No se pudo generar el PDF", "error");
        }
    } catch (e) {
        console.error("Error al exportar PDF:", e);
        AppUtils.showToast("Error de conexión al generar PDF", "error");
    }
};

// ═══════════════════════════════════════════════════════════════════
//  INIT Y SWITCH DE TABS
// ═══════════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('search-audit')?.addEventListener('input', (e) => filtrarAuditoria(e.target.value));

    const searchReport = document.getElementById('search-report');
    if (searchReport) {
        const wrapper = searchReport.parentElement;
        if (wrapper && !document.getElementById('btn-print-expenses-bulk')) {
            const btn = document.createElement('button');
            btn.id = 'btn-print-expenses-bulk';
            btn.type = 'button';
            btn.onclick = window.imprimirGastosCompleto;
            btn.className = "p-2.5 bg-navy-blue text-neon-green rounded-xl hover:bg-slate-800 transition-all shadow-sm flex items-center justify-center group flex-shrink-0";
            btn.title = "Imprimir Reporte de Gastos";
            btn.innerHTML = '<i data-lucide="printer" class="w-5 h-5"></i>';
            wrapper.classList.add('flex', 'items-center', 'gap-2');
            wrapper.appendChild(btn);
        }
    }

    const searchAudit = document.getElementById('search-audit');
    if (searchAudit) {
        const wrapper = searchAudit.parentElement;
        if (wrapper && !document.getElementById('btn-print-audit-bulk')) {
            const btn = document.createElement('button');
            btn.id = 'btn-print-audit-bulk';
            btn.type = 'button';
            btn.onclick = window.imprimirAuditoriaCompleta;
            btn.className = "p-2.5 bg-navy-blue text-neon-green rounded-xl hover:bg-slate-800 transition-all shadow-sm flex items-center justify-center group flex-shrink-0";
            btn.title = "Imprimir Reporte de Auditoría";
            btn.innerHTML = '<i data-lucide="printer" class="w-5 h-5"></i>';
            wrapper.classList.add('flex', 'items-center', 'gap-2');
            wrapper.appendChild(btn);
            if (window.lucide) lucide.createIcons();
        }
    }

    window.handler_reporte_flujo = new DataTableRefactor({
        tableId: 'reportTable',
        tableBodyId: 'report-body',
        endpoint: `${URLROOT}/reportes/generar`,
        searchInputId: 'search-report',
        limitSelectorId: 'limitSelector',
        paginationId: 'custom-bottom-controls',
        totalId: 'totalCount',
        getExtraParams: () => ({
            desde: document.getElementById('rep-desde')?.value || '',
            hasta: document.getElementById('rep-hasta')?.value || ''
        }),
        onDataLoaded: (result) => {
            if (result.totales) {
                document.getElementById('total-repuestos').textContent = AppUtils.formatCurrency(result.totales.ingreso_repuestos || 0);
                document.getElementById('total-servicios').textContent = AppUtils.formatCurrency(result.totales.ingreso_servicios || 0);
                document.getElementById('total-egresos').textContent = AppUtils.formatCurrency(result.totales.egresos || 0);
                document.getElementById('total-deuda').textContent = AppUtils.formatCurrency(result.totales.deuda || 0);
                document.getElementById('total-balance').textContent = AppUtils.formatCurrency(result.totales.balance || 0);
            }
            const body = document.getElementById('report-body');
            if (result.data && result.data.length === 0) {
                body.innerHTML = `<tr><td colspan="6" class="px-8 py-16 text-center text-slate-400 italic font-medium uppercase tracking-widest">
                    <div class="flex flex-col items-center gap-2">
                        <i data-lucide="info" class="w-8 h-8 text-slate-300"></i> 
                        <span>No se encontraron movimientos en este periodo</span>
                    </div>
                </td></tr>`;
                if (window.lucide) lucide.createIcons();
            }
        },
        renderRow: (m) => window.renderFlujoRow(m)
    });

    window.handler_reporte_devoluciones = new DataTableRefactor({
        tableId: 'devolucionesTable',
        tableBodyId: 'devoluciones-body',
        endpoint: `${URLROOT}/reportes/devoluciones`,
        searchInputId: 'search-devoluciones',
        limitSelectorId: 'limitSelector-devoluciones',
        paginationId: 'pagination-devoluciones',
        totalId: 'totalCount-devoluciones',
        getExtraParams: () => ({
            desde: document.getElementById('rep-desde')?.value || new Date().toISOString().split('T')[0].substring(0, 8) + '01',
            hasta: document.getElementById('rep-hasta')?.value || new Date().toISOString().split('T')[0]
        }),
        onDataLoaded: (result) => {
            const body = document.getElementById('devoluciones-body');
            if (result.data && result.data.length === 0) {
                body.innerHTML = `<tr><td colspan="6" class="px-8 py-16 text-center text-slate-400 italic font-medium uppercase tracking-widest">
                    <div class="flex flex-col items-center gap-2">
                        <i data-lucide="info" class="w-8 h-8 text-slate-300"></i> 
                        <span>No hay registros de devoluciones para mostrar</span>
                    </div>
                </td></tr>`;
                if (window.lucide) lucide.createIcons();
            }
        },
        renderRow: (d) => `
            <tr class="hover:bg-slate-50/50 transition-colors border-b border-slate-50">
                <td class="px-4 py-4 font-black text-navy-blue text-sm uppercase">#${d.id}</td>
                <td class="px-4 py-4 text-sm font-bold text-slate-500">${new Date(d.fecha).toLocaleDateString()}</td>
                <td class="px-4 py-4 text-sm font-black text-navy-blue uppercase">${d.cliente_nombre || 'N/A'}<br><span class="text-[10px] text-slate-400 font-bold">${d.placa || '---'}</span></td>
                <td class="px-4 py-4 text-sm text-slate-600 uppercase font-medium">${d.descripcion}</td>
                <td class="px-4 py-4 text-right font-black text-rose-500">${AppUtils.formatCurrency(d.monto_devuelto)}</td>
                <td class="px-4 py-4 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-black uppercase ${d.destino === 'STOCK' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'}">
                        ${d.destino}
                    </span>
                </td>
            </tr>`
    });

    document.getElementById('rep-desde')?.addEventListener('change', window.actualizarFiltrosFechas);
    document.getElementById('rep-hasta')?.addEventListener('change', window.actualizarFiltrosFechas);
});

window.cargarReporte = window.actualizarFiltrosFechas;

window.switchReportTab = (tab) => {
    activeReportTab = tab;

    ['resumen','detallado','devoluciones','cartera','rentabilidad','nomina','historial_nomina'].forEach(t => {
        const el = document.getElementById(`sec-${t}`);
        if (el) el.classList.add('hidden');
        const tb = document.getElementById(`tab-${t}`);
        if (tb) { tb.classList.remove('border-neon-green', 'text-navy-blue'); tb.classList.add('border-transparent', 'text-slate-400'); }
    });

    const sec = document.getElementById(`sec-${tab}`);
    const tb = document.getElementById(`tab-${tab}`);
    if (sec) sec.classList.remove('hidden');
    if (tb) { tb.classList.add('border-neon-green', 'text-navy-blue'); tb.classList.remove('border-transparent', 'text-slate-400'); }

    if (tab === 'resumen') {
        if (window.handler_reporte_flujo) window.handler_reporte_flujo.reload();
    } else if (tab === 'detallado') {
        window.cargarReporteDetallado();
    } else if (tab === 'devoluciones') {
        if (window.handler_reporte_devoluciones) window.handler_reporte_devoluciones.reload();
    } else if (tab === 'cartera') {
        window.cargarCartera();
    } else if (tab === 'rentabilidad') {
        window.cargarRentabilidad();
    } else if (tab === 'nomina') {
        window.cargarNomina();
    } else if (tab === 'historial_nomina') {
        window.cargarHistorialNomina();
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
};