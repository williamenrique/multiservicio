document.addEventListener('DOMContentLoaded', () => {
    cargarReporte(); // Carga el resumen por defecto

    // Vincular buscador de auditoría
    document.getElementById('search-audit')?.addEventListener('input', (e) => filtrarAuditoria(e.target.value));
});

window.switchReportTab = (tab) => {
    const secResumen = document.getElementById('sec-resumen');
    const secDetallado = document.getElementById('sec-detallado');
    const tabResumen = document.getElementById('tab-resumen');
    const tabDetallado = document.getElementById('tab-detallado');

    if (tab === 'resumen') {
        secResumen.classList.remove('hidden');
        secDetallado.classList.add('hidden');
        tabResumen.classList.add('border-neon-green', 'text-navy-blue');
        tabResumen.classList.remove('border-transparent', 'text-slate-400');
        tabDetallado.classList.remove('border-neon-green', 'text-navy-blue');
        tabDetallado.classList.add('border-transparent', 'text-slate-400');
        cargarReporte();
    } else {
        secResumen.classList.add('hidden');
        secDetallado.classList.remove('hidden');
        tabDetallado.classList.add('border-neon-green', 'text-navy-blue');
        tabDetallado.classList.remove('border-transparent', 'text-slate-400');
        tabResumen.classList.remove('border-neon-green', 'text-navy-blue');
        tabResumen.classList.add('border-transparent', 'text-slate-400');
        cargarReporteDetallado();
    }
    lucide.createIcons();
};

let rawAuditData = null; // Para filtrar sin volver al servidor

async function cargarReporte() {
    const desde = document.getElementById('rep-desde').value;
    const hasta = document.getElementById('rep-hasta').value;

    try {
        const res = await fetch(`${URLROOT}/reportes/generar?desde=${desde}&hasta=${hasta}`);

        // Verificar si la respuesta es realmente JSON
        const contentType = res.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            const text = await res.text();
            console.error("Respuesta no válida del servidor:", text);
            throw new Error("El servidor no devolvió JSON. Revisa la consola.");
        }

        const data = await res.json();

        // Actualizar Totales
        document.getElementById('total-ingresos').textContent = AppUtils.formatCurrency(data.totales.ingresos);
        document.getElementById('total-egresos').textContent = AppUtils.formatCurrency(data.totales.egresos);
        document.getElementById('total-deuda').textContent = AppUtils.formatCurrency(data.totales.deuda);
        document.getElementById('total-balance').textContent = AppUtils.formatCurrency(data.totales.balance);

        // Renderizar Tabla
        const tbody = document.getElementById('report-body');

        // Limpieza de DataTable para evitar el error mData (desajuste de columnas)
        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().clear().destroy();
        }
        tbody.innerHTML = '';

        const formatDateLong = (dateStr) => {
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return 'Fecha inválida';
            const day = d.getDate();
            const meses = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
            const month = meses[d.getMonth()];
            const year = d.getFullYear();
            return `${day} de ${month} de ${year}.`;
        };

        tbody.innerHTML = data.movimientos.map(m => {
            const isVenta = (m.tipo === 'VENTA');
            const isProveedor = (!!m.proveedor_nombre || m.tipo === 'COMPRA');

            let descriptionContent = '';

            if (isVenta) {
                descriptionContent = `
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black text-navy-blue uppercase tracking-tight">${m.modelo_vehiculo || 'VEHÍCULO GENERAL'}</span>
                            <span class="text-[10px] text-slate-400 font-mono tracking-tighter border px-1.5 rounded bg-slate-50 border-slate-100">${m.placa || 'SIN PLACA'}</span>
                        </div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase leading-tight">Cliente: ${m.cliente_nombre || m.entidad || 'VENTA RÁPIDA'}</div>
                        <div class="flex items-center gap-1.5 mt-0.5 text-[9px] font-black text-blue-600 uppercase">
                            <i data-lucide="package" class="w-3 h-3"></i> ${m.cantidad_items || m.total_productos || 0} SERVICIOS/ARTÍCULOS
                        </div>
                    </div>`;
            } else if (isProveedor) {
                descriptionContent = `
                    <div class="flex flex-col gap-1">
                        <div class="text-xs font-black text-slate-700 uppercase tracking-tight">PAGO / ABONO PROVEEDOR</div>
                        <div class="text-[10px] text-navy-blue font-bold uppercase leading-tight">Proveedor: ${m.proveedor_nombre || m.entidad}</div>
                        ${(m.saldo_pendiente > 0) ? `<div class="text-[9px] bg-rose-50 text-rose-600 px-2 py-0.5 rounded-full font-black border border-rose-100 italic w-fit mt-1">DEUDA: ${AppUtils.formatCurrency(m.saldo_pendiente)}</div>` : ''}
                    </div>`;
            } else {
                descriptionContent = `
                    <div class="flex flex-col gap-1">
                        <div class="text-xs font-black text-slate-700 uppercase tracking-tight">${m.descripcion || 'GASTO OPERATIVO'}</div>
                        <div class="text-[10px] text-slate-400 font-bold uppercase leading-tight">${m.categoria || 'GENERAL'}</div>
                    </div>`;
            }

            return `
                <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors group">
                    <td class="px-4 py-5 font-mono text-xs text-slate-400 align-middle">#${m.id_db || m.id}</td>
                    <td class="px-4 py-5 text-[11px] font-bold text-slate-500 uppercase tracking-tighter whitespace-nowrap align-middle">${formatDateLong(m.fecha)}</td>
                    <td class="px-4 py-5 text-center align-middle">
                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase ${m.tipo === 'VENTA' ? 'bg-blue-100 text-blue-600' : 'bg-rose-100 text-rose-600'}">
                            ${m.tipo}
                        </span>
                    </td>
                    <td class="px-4 py-5">${descriptionContent}</td>
                    <td class="px-4 py-5 text-right font-black text-sm ${m.tipo === 'VENTA' ? 'text-blue-600' : 'text-rose-600'} align-middle">
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
                                </button>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        $('#reportTable').DataTable({
            responsive: true,
            order: [[0, 'desc']],
            columns: [
                { orderable: true }, // ID
                { orderable: true }, // FECHA
                { orderable: false }, // TIPO
                { orderable: true }, // DESCRIPCIÓN
                { orderable: true }, // TOTAL
                { orderable: false } // ACCIONES
            ],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            drawCallback: () => lucide.createIcons()
        });

        lucide.createIcons();
    } catch (e) {
        console.error("Error cargando reporte:", e);
        AppUtils.showToast("Error al generar el reporte", "error");
    }
}

async function cargarReporteDetallado() {
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

    // Agrupar ventas por ID de factura
    const agrupado = items.reduce((acc, current) => {
        const key = `${current.tipo}-${current.id}`;
        if (!acc[key]) {
            acc[key] = {
                id: current.id,
                tipo: current.tipo,
                fecha: current.fecha,
                vehiculo: current.modelo_vehiculo,
                placa: current.placa,
                cliente: current.cliente_nombre || 'VENTA RÁPIDA',
                total_final: 0,
                usuario: current.usuario_nombre || 'N/A',
                pagado: parseFloat(current.pagado || 0),
                items: []
            };
        }
        acc[key].items.push(current);
        return acc;
    }, {});

    const registros = Object.values(agrupado);

    if (registros.length === 0) {
        container.innerHTML = '<div class="text-center py-20 text-slate-400 italic font-bold uppercase tracking-widest">No hay registros en este periodo</div>';
        return;
    }

    const formatDateForAudit = (d) => new Date(d).toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    }).replace('.', '');

    container.innerHTML = registros.map(f => {
        const totalFactura = f.items.reduce((sum, i) => sum + parseFloat(i.subtotal_item || 0), 0);
        const colorClass = f.tipo === 'VENTA' ? 'text-blue-600' : 'text-rose-600';
        const badgeClass = f.tipo === 'VENTA' ? 'bg-blue-100 text-blue-600' : 'bg-rose-100 text-rose-600';

        return `
        <div class="glass-card overflow-hidden rounded-2xl border border-slate-100 hover:shadow-lg transition-all mb-4">
            <div class="bg-slate-50/80 p-5 border-b border-slate-100 flex justify-between items-center">
                <div class="flex items-center gap-5">
                    <button onclick="verDetalleVenta(${f.id})" 
                            class="bg-navy-blue text-white p-3 rounded-2xl hover:scale-110 transition-all shadow-lg shadow-navy-blue/20 group" 
                            title="Ver Detalle Completo">
                        <i data-lucide="eye" class="w-5 h-5 text-neon-green group-hover:rotate-12 transition-transform"></i>
                    </button>
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h4 class="font-black text-navy-blue uppercase text-sm tracking-tight">${f.vehiculo}</h4>
                            <span class="text-slate-400 font-mono text-[10px] bg-white px-2 py-0.5 rounded border border-slate-100">${f.placa || '---'}</span>
                            <span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase ${badgeClass}">${f.tipo}</span>
                        </div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">
                            Factura #${f.id} • ${formatDateForAudit(f.fecha)} • <span class="text-slate-600">${f.cliente}</span>
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Total Operación</p>
                    <p class="text-xl font-black ${colorClass}">${AppUtils.formatCurrency(totalFactura)}</p>
                </div>
            </div>
            <div class="p-5 space-y-3 bg-white">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${f.items.map(i => `
                        <div class="flex justify-between items-center p-3 bg-slate-50/50 rounded-xl border border-transparent hover:border-slate-100 transition-all group">
                            <div class="flex items-center gap-4">
                                <span class="w-8 h-8 flex items-center justify-center bg-white rounded-lg font-black text-xs text-slate-500 shadow-sm">${i.cantidad}</span>
                                <div>
                                    <p class="font-bold text-slate-700 uppercase text-xs">${i.descripcion || 'Servicio/Articulo'}</p>
                                    <p class="text-[9px] text-slate-400 font-bold">P. Unit: ${AppUtils.formatCurrency(i.precio_unitario || (i.subtotal_item / i.cantidad))}</p>
                                </div>
                            </div>
                            <span class="font-black text-slate-500 text-xs">${AppUtils.formatCurrency(i.subtotal_item)}</span>
                        </div>
                    `).join('')}
                </div>
                <div class="flex justify-between items-center pt-4 border-t border-slate-50">
                    <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase">
                        <i data-lucide="user" class="w-3 h-3"></i>
                        Responsable: <span class="text-navy-blue">${f.usuario}</span>
                    </div>
                    <div class="text-[10px] font-black text-slate-300 uppercase tracking-widest">
                        ${f.items.length} Servicios registrados
                    </div>
                </div>
            </div>
        </div>
        `
    }).join('');

    lucide.createIcons();
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

                    <div class="bg-navy-blue p-5 rounded-2xl space-y-3 text-white">
                        <div class="flex justify-between items-center text-xs opacity-70">
                            <span class="font-bold uppercase">Subtotal Neto</span>
                            <span class="font-bold">${AppUtils.formatCurrency(venta.subtotal)}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs opacity-70">
                            <span class="font-bold uppercase">Impuestos (IVA)</span>
                            <span class="font-bold">${AppUtils.formatCurrency(venta.iva_monto)}</span>
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