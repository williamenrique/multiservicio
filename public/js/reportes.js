/**
 * GESTIÓN DE REPORTES - UNIFICADO
 */

// Variables de estado global para filtros y auditoría
let rawAuditData = { ventas: [], compras: [], gastos: [] };
let activeReportTab = 'resumen';

/**
 * Renderiza una fila del Flujo de Caja (6 columnas)
 */
window.renderFlujoRow = (m) => {
    const isIngreso = m.tipo === 'INGRESO';
    const color = m.tipo_color || (isIngreso ? 'emerald' : 'rose');
    const label = m.categoria_label || m.categoria || m.tipo;

    // Normalizamos los textos para buscar palabras clave
    const cat = (m.categoria || '').toUpperCase();
    const labelUpper = (m.categoria_label || m.tipo || '').toUpperCase();
    const desc = (m.descripcion || '').toUpperCase();
    const tipo = (m.tipo || '').toUpperCase();
    const root = window.URLROOT || '';

    // Usamos el referencia_id como prioridad, si no existe usamos el ID del movimiento
    const refId = m.referencia_id || m.id;
    const ordenId = m.orden_id;

    let printUrl = '';
    let detailUrl = '';
    let printBtnClass = 'text-slate-400 hover:text-navy-blue'; // Default for print button

    // 1. DETECCIÓN DE COMPROBANTE (Factura, Gasto o Nómina)
    if (tipo === 'INGRESO' && (cat.includes('VENTA') || cat.includes('ABONO') || desc.includes('FACTURA') || labelUpper.includes('FACTURA'))) {
        printUrl = `${root}/facturacion/imprimir/${refId}`;
        detailUrl = `javascript:verDetalleVenta(${refId})`;
        printBtnClass = 'text-blue-500 hover:bg-blue-50';
    } else if (cat === 'NOMINA' || labelUpper.includes('NOMINA') || labelUpper.includes('ADELANTO')) {
        printUrl = `${root}/reportes/imprimirRecibo/${refId}`;
        detailUrl = `javascript:verDetallePagoHistorial(${refId})`;
        printBtnClass = 'text-amber-500 hover:bg-amber-50';
    } else if (tipo === 'EGRESO' || cat.includes('PROVEEDOR') || cat.includes('GASTO') || labelUpper.includes('PAGO') || desc.includes('PAGO') || labelUpper.includes('SERVICIO') || cat.includes('COMPRA')) {
        printUrl = `${root}/gastos/imprimir/${refId}`;
        detailUrl = `javascript:verDetalleCompra(${refId})`; // Assuming verDetalleCompra handles general expenses too
        printBtnClass = 'text-rose-500 hover:bg-rose-50';
    }

    // 2. DETECCIÓN DE ORDEN TÉCNICA (Icono de Llave)
    let orderPrintUrl = '';
    // Si hay un orden_id válido o si la descripción menciona explícitamente una O.S
    if (ordenId && ordenId !== 'null' && ordenId !== null && ordenId !== '') {
        orderPrintUrl = `${root}/taller/imprimir/${ordenId}`;
    } else if (labelUpper.includes('O.S') || desc.includes('ORDEN') || cat.includes('ORDEN')) {
        // Si no hay ordenId pero la referencia es a una orden de taller
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

/**
 * Actualiza filtros de fecha para todos los manejadores activos
 */
window.actualizarFiltrosFechas = () => {
    if (window.handler_reporte_flujo) {
        window.handler_reporte_flujo.reload();
    }
    if (window.handler_reporte_devoluciones) {
        window.handler_reporte_devoluciones.reload();
    }

    // Si estamos en la pestaña de nómina o detallado, recargarlos manualmente
    if (activeReportTab === 'detallado') window.cargarReporteDetallado();
    if (activeReportTab === 'cartera') window.cargarCartera();
    if (activeReportTab === 'rentabilidad') window.cargarRentabilidad();
    if (activeReportTab === 'nomina') window.cargarNomina();
    if (activeReportTab === 'historial_nomina') window.cargarHistorialNomina();
};

/**
 * Carga Auditoría de Trabajos (Reporte Detallado)
 */
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

        // Normalizar respuesta: Acepta tanto {success:true, data:{...}} como el objeto directo
        const responseData = result.success ? result.data : result;

        if (responseData && responseData.ventas) {
            rawAuditData = responseData; // Guardar para búsqueda local
            renderAuditoriaLista(rawAuditData.ventas);

            // Renderizar Compras
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

            // Renderizar Gastos
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

window.renderCartera = (data) => {
    const tbody = document.getElementById('cartera-body');
    if (!tbody) return;

    tbody.innerHTML = (Array.isArray(data) && data.length > 0) ? data.map(c => `
        <tr class="hover:bg-slate-50 border-b border-slate-100">
            <td class="px-6 py-4 text-sm font-bold text-slate-700 uppercase">${c.cliente_nombre}</td>
            <td class="px-6 py-4 text-xs font-black text-slate-400 text-center">${AppUtils.formatCurrency(c.rango_0_15)}</td>
            <td class="px-6 py-4 text-xs font-black text-amber-500 text-center">${AppUtils.formatCurrency(c.rango_16_30)}</td>
            <td class="px-6 py-4 text-xs font-black text-rose-600 text-center">${AppUtils.formatCurrency(c.rango_30_mas)}</td>
            <td class="px-6 py-4 text-right font-black text-navy-blue text-sm">${AppUtils.formatCurrency(c.total_deuda)}</td>
        </tr>
    `).join('') : '<tr><td colspan="5" class="text-center py-20 text-slate-400 italic font-bold uppercase tracking-widest">Sin deudas de cartera</td></tr>';
    if (window.lucide) lucide.createIcons();
};

document.addEventListener('DOMContentLoaded', () => {
    // Vincular buscador de auditoría
    document.getElementById('search-audit')?.addEventListener('input', (e) => filtrarAuditoria(e.target.value));

    // Inyectar botón de impresión al lado del buscador de flujo de caja (Gastos)
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

    // Inyectar botón de impresión al lado del buscador de auditoría
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

    // Instancia para el Flujo de Caja (Resumen)
    window.handler_reporte_flujo = new DataTableRefactor({
        tableId: 'reportTable', // Corregido para sincronizar con el HTML
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
            // Manejo de estado vacío
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

    // Instancia para Historial de Devoluciones
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
        renderRow: (d) => {
            return `
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
                </tr>`;
        }
    });

    // Vincular filtros de fecha
    document.getElementById('rep-desde')?.addEventListener('change', window.actualizarFiltrosFechas);
    document.getElementById('rep-hasta')?.addEventListener('change', window.actualizarFiltrosFechas);
});

// Alias para el botón de refrescar en la cabecera del reporte
window.cargarReporte = window.actualizarFiltrosFechas;

window.switchReportTab = (tab) => {
    activeReportTab = tab;
    const secResumen = document.getElementById('sec-resumen');
    const secDetallado = document.getElementById('sec-detallado');
    const secDevoluciones = document.getElementById('sec-devoluciones');
    const secNomina = document.getElementById('sec-nomina');
    const secHistorialNomina = document.getElementById('sec-historial-nomina');

    const tabResumen = document.getElementById('tab-resumen');
    const tabDetallado = document.getElementById('tab-detallado');
    const tabDevoluciones = document.getElementById('tab-devoluciones');
    const tabCartera = document.getElementById('tab-cartera');
    const tabRentabilidad = document.getElementById('tab-rentabilidad');
    const tabNomina = document.getElementById('tab-nomina');
    const tabHistorialNomina = document.getElementById('tab-historial-nomina');

    // Ocultar todas las secciones
    if (secResumen) secResumen.classList.add('hidden');
    if (secDetallado) secDetallado.classList.add('hidden');
    if (secDevoluciones) secDevoluciones.classList.add('hidden');
    if (document.getElementById('sec-cartera')) document.getElementById('sec-cartera').classList.add('hidden');
    if (document.getElementById('sec-rentabilidad')) document.getElementById('sec-rentabilidad').classList.add('hidden');
    if (secNomina) secNomina.classList.add('hidden');
    if (secHistorialNomina) secHistorialNomina.classList.add('hidden');

    // Resetear estilos de pestañas
    [tabResumen, tabDetallado, tabDevoluciones, tabCartera, tabRentabilidad, tabNomina, tabHistorialNomina].forEach(t => {
        if (t) {
            t.classList.remove('border-neon-green', 'text-navy-blue');
            t.classList.add('border-transparent', 'text-slate-400');
        }
    });

    if (tab === 'resumen') {
        if (secResumen) secResumen.classList.remove('hidden');
        if (tabResumen) tabResumen.classList.add('border-neon-green', 'text-navy-blue');
        if (window.handler_reporte_flujo) window.handler_reporte_flujo.reload();
    } else if (tab === 'detallado') {
        if (secDetallado) secDetallado.classList.remove('hidden');
        if (tabDetallado) tabDetallado.classList.add('border-neon-green', 'text-navy-blue');
        window.cargarReporteDetallado();
    } else if (tab === 'devoluciones') {
        if (secDevoluciones) secDevoluciones.classList.remove('hidden');
        if (tabDevoluciones) tabDevoluciones.classList.add('border-neon-green', 'text-navy-blue');
        if (window.handler_reporte_devoluciones) window.handler_reporte_devoluciones.reload();
    } else if (tab === 'cartera') {
        if (document.getElementById('sec-cartera')) document.getElementById('sec-cartera').classList.remove('hidden');
        if (tabCartera) tabCartera.classList.add('border-neon-green', 'text-navy-blue');
        window.cargarCartera();
    } else if (tab === 'rentabilidad') {
        if (document.getElementById('sec-rentabilidad')) document.getElementById('sec-rentabilidad').classList.remove('hidden');
        if (tabRentabilidad) tabRentabilidad.classList.add('border-neon-green', 'text-navy-blue');
        window.cargarRentabilidad();
    } else if (tab === 'nomina') {
        if (secNomina) secNomina.classList.remove('hidden');
        if (tabNomina) tabNomina.classList.add('border-neon-green', 'text-navy-blue');
        cargarNomina();
    } else if (tab === 'historial_nomina') {
        if (secHistorialNomina) secHistorialNomina.classList.remove('hidden');
        if (tabHistorialNomina) tabHistorialNomina.classList.add('border-neon-green', 'text-navy-blue');
        window.cargarHistorialNomina();
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
};

/**
 * Carga Reporte de Cartera
 */
window.cargarCartera = async function () {
    const desde = document.getElementById('rep-desde')?.value || '';
    const hasta = document.getElementById('rep-hasta')?.value || '';
    const tbody = document.getElementById('cartera-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-16 text-slate-400 italic animate-pulse font-bold uppercase tracking-widest">GENERANDO REPORTE DE CARTERA...</td></tr>';

    try {
        const res = await fetch(`${URLROOT}/reportes/cartera?desde=${desde}&hasta=${hasta}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const result = await res.json();
        if (result.success) window.renderCartera(result.data);
    } catch (e) {
        console.error("Error al cargar cartera:", e);
        AppUtils.showToast("Error al cargar cartera", "error");
    }
};

/**
  * Dispara la impresión del reporte de cartera de proveedores (Global)
 */
window.exportarCarteraProveedoresPdf = function () {
    AppUtils.showToast("Generando reporte de proveedores...", "info");
    window.open(`${URLROOT}/reportes/imprimirCarteraProveedores`, '_blank');
};

/**
 * Dispara la impresión del reporte individual de un proveedor
 */
window.imprimirReporteProveedorIndividual = function (id) {
    if (!id) return;
    AppUtils.showToast("Generando estado de cuenta...", "info");
    window.open(`${URLROOT}/reportes/imprimirReporteProveedor/${id}`, '_blank');
};

/**
 * Exporta la cartera actual a Excel (CSV)
 */
window.exportarCarteraExcel = function () {
    // Simplemente redirigimos a la URL que genera el CSV
    window.location.href = `${URLROOT}/reportes/exportarCarteraExcel`;
};

/**
 * Genera y descarga el PDF de la cartera
 */
window.exportarCarteraPdf = async function () {
    AppUtils.showToast("Generando PDF de Cartera...", "info");
    try {
        const res = await fetch(`${URLROOT}/reportes/exportarCarteraPdf`);
        if (!res.ok) throw new Error("Error en la respuesta del servidor");

        const result = await res.json();
        if (result.success) {
            // Abrimos el PDF generado en una nueva pestaña
            window.open(result.pdf_url, '_blank');
        } else {
            AppUtils.showToast(result.mensaje || "No se pudo generar el PDF", "error");
        }
    } catch (e) {
        console.error("Error al exportar PDF:", e);
        AppUtils.showToast("Error de conexión al generar PDF", "error");
    }
};

/**
 * Lógica para cargar Análisis de Rentabilidad
 */
window.cargarRentabilidad = async function () {
    const desde = document.getElementById('rep-desde')?.value || '';
    const hasta = document.getElementById('rep-hasta')?.value || '';
    const tbody = document.getElementById('rentabilidad-body');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-slate-400 italic animate-pulse uppercase font-black">Analizando Rentabilidad...</td></tr>';

    try {
        const res = await fetch(`${URLROOT}/reportes/rentabilidad?desde=${desde}&hasta=${hasta}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const result = await res.json();
        if (result.success && result.data) {
            window.renderRentabilidad(result.data);
        }
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

function renderAuditoriaLista(items) {
    const container = document.getElementById('audit-list-container');
    if (!container || !Array.isArray(items)) {
        container.innerHTML = '<div class="py-20 text-center text-slate-400 italic">Datos de trabajos inválidos</div>';
        return;
    }

    // 1. Agrupar por Mes y Año para los encabezados sticky
    const meses = ["ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO", "JULIO", "AGOSTO", "SEPTIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE"];

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
                    cliente_telefono: current.cliente_telefono || '',
                    usuario: current.mecanico_nombre || current.usuario_nombre || 'SISTEMA',
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

                    <div class="flex items-center gap-10">
                        <div class="text-right">
                            <p class="text-sm font-black text-slate-400 uppercase tracking-widest mb-1 leading-none">Técnico: <span class="text-navy-blue font-black">${f.usuario}</span></p>
                            <p class="text-sm font-black text-slate-400 uppercase tracking-widest mb-1 leading-none">Total Factura: <span class="text-slate-600">${AppUtils.formatCurrency(totalFactura)}</span></p>
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
    // Limpiar el ID si viene con prefijo (ej: TKT-123 -> 123)
    const idLimpio = String(ventaId).replace(/\D/g, '');

    try {
        const res = await fetch(`${URLROOT}/historial/detalle/${idLimpio}`);
        const result = await res.json();

        // Extraer la venta: manejamos si viene envuelta en data o directa
        const venta = (result.success && result.data) ? result.data : result;

        if (!venta || (!venta.id && !venta.venta_id)) {
            return AppUtils.showToast('No se encontró el detalle de la venta #' + idLimpio, 'error');
        }

        Swal.fire({
            title: `<span class="text-sm uppercase text-slate-400 font-black tracking-widest">Detalle de Operación</span><br><span class="text-navy-blue text-2xl">FACTURA #${venta.id || idLimpio}</span>`,
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
 * Genera el reporte PDF de la Auditoría de Trabajos (Listado completo)
 */
window.imprimirAuditoriaCompleta = () => {
    const desde = document.getElementById('rep-desde')?.value || '';
    const hasta = document.getElementById('rep-hasta')?.value || '';
    const search = document.getElementById('search-audit')?.value || '';

    AppUtils.showToast("Generando reporte de auditoría...", "info");
    window.open(`${URLROOT}/reportes/imprimirAuditoria?desde=${desde}&hasta=${hasta}&q=${search}`, '_blank');
};

/**
 * Genera el reporte PDF de Gastos (Listado filtrado)
 */
window.imprimirGastosCompleto = () => {
    const desde = document.getElementById('rep-desde')?.value || '';
    const hasta = document.getElementById('rep-hasta')?.value || '';
    const search = document.getElementById('search-report')?.value || '';

    AppUtils.showToast("Generando reporte de gastos...", "info");
    window.open(`${URLROOT}/reportes/imprimirGastos?desde=${desde}&hasta=${hasta}&q=${search}`, '_blank');
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
            if (window.lucide) lucide.createIcons();
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

window.cargarNomina = async function () {
    const staffId = document.getElementById('staff-selector')?.value;
    const desde = document.getElementById('rep-desde')?.value;
    const hasta = document.getElementById('rep-hasta')?.value;

    if (!staffId || staffId === "") {
        // Cargar lista de empleados si el selector está vacío
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

            // Guardar total pendiente actual para cálculos del modal
            window.currentNominaPendiente = saldoNetoReal;
        }
        if (window.lucide) lucide.createIcons();
    } catch (e) { console.error(e); }
};

/**
 * Recalcula el monto pendiente según los trabajos marcados manualmente
 */
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

            // Recopilar IDs de trabajos seleccionados
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
                window.cargarNomina(); // Recargar la vista actual de nómina
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

/**
 * Realiza el cálculo en tiempo real dentro del modal de pago
 */
window.recalcularVistaPreviaPago = function () {
    const base = window.currentNominaPendiente || 0;
    const factor = parseFloat(document.getElementById('pago-factor')?.value) || 0;
    const isPorcentaje = document.getElementById('pago-modo-switch')?.checked;
    const previewEl = document.getElementById('pago-total-preview');

    let total = isPorcentaje ? (base * (factor / 100)) : factor;

    if (previewEl) {
        previewEl.innerText = AppUtils.formatCurrency(total);
    }
};

/**
 * Maneja el cambio de etiquetas en el modal según el switch
 */
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

/**
 * Genera el PDF del recibo de pago
 */
window.imprimirReciboPago = function (pagoId) {
    AppUtils.showToast("Abriendo comprobante...", "info");
    window.open(`${URLROOT}/reportes/imprimirRecibo/${pagoId}`, '_blank');
};

/**
 * Carga el historial de pagos de nómina realizados
 */
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

window.reimprimirPagoNomina = function (id) {
    // Uniformidad: Abrimos el endpoint de impresión directa para copias históricas
    AppUtils.showToast("Abriendo copia del recibo...", "info");
    window.open(`${URLROOT}/reportes/imprimirRecibo/${id}`, '_blank');
};