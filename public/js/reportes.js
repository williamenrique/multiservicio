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
        document.getElementById('total-balance').textContent = AppUtils.formatCurrency(data.totales.balance);

        // Renderizar Tabla
        const tbody = document.getElementById('report-body');
        if ($.fn.DataTable.isDataTable('#reportTable')) $('#reportTable').DataTable().destroy();

        tbody.innerHTML = data.movimientos.map(m => `
            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-3 text-xs font-bold text-slate-500">${new Date(m.fecha).toLocaleDateString()}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded text-[9px] font-black ${m.tipo === 'VENTA' ? 'bg-blue-100 text-blue-600' : 'bg-red-100 text-red-600'}">
                        ${m.tipo}
                    </span>
                </td>
                <td class="px-4 py-3 text-slate-600 font-medium uppercase text-xs">${m.descripcion || 'Sin descripción'}</td>
                <td class="px-4 py-3 text-right font-black ${m.tipo === 'VENTA' ? 'text-blue-600' : 'text-red-600'}">
                    ${m.tipo === 'VENTA' ? '+' : '-'} ${AppUtils.formatCurrency(m.monto)}
                </td>
            </tr>
        `).join('');

        $('#reportTable').DataTable({
            responsive: true,
            order: [[0, 'desc']],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
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

        renderAuditoriaLista(data.ventas);
        lucide.createIcons();
    } catch (e) {
        console.error("Error cargando reporte detallado:", e);
        AppUtils.showToast("Error al generar auditoría", "error");
    }
}

function renderAuditoriaLista(ventas) {
    const container = document.getElementById('audit-list-container');

    // Agrupar ventas por ID de factura
    const agrupado = ventas.reduce((acc, current) => {
        if (!acc[current.id]) {
            acc[current.id] = {
                id: current.id,
                fecha: current.fecha,
                vehiculo: current.modelo_vehiculo,
                placa: current.placa,
                items: []
            };
        }
        acc[current.id].items.push(current);
        return acc;
    }, {});

    const facturas = Object.values(agrupado);

    if (facturas.length === 0) {
        container.innerHTML = '<div class="text-center py-20 text-slate-400 italic font-bold uppercase tracking-widest">No se encontraron trabajos en este rango</div>';
        return;
    }

    container.innerHTML = facturas.map(f => {
        const totalFactura = f.items.reduce((sum, i) => sum + parseFloat(i.subtotal_item), 0);

        return `
        <div class="glass-card overflow-hidden rounded-2xl border border-slate-100 hover:shadow-lg transition-all">
            <div class="bg-slate-50/80 p-4 border-b border-slate-100 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="bg-navy-blue text-white p-2 rounded-xl">
                        <i data-lucide="wrench" class="w-5 h-5 text-neon-green"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-navy-blue uppercase text-sm">${f.vehiculo} <span class="text-slate-400 font-mono ml-2">[${f.placa}]</span></h4>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">Factura #${f.id} • ${new Date(f.fecha).toLocaleString()}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-black text-slate-400 uppercase">Total Trabajo</p>
                    <p class="text-lg font-black text-blue-600">${AppUtils.formatCurrency(totalFactura)}</p>
                </div>
            </div>
            <div class="p-4 space-y-2 bg-white">
                ${f.items.map(i => `
                    <div class="flex justify-between items-center text-xs py-1 border-b border-slate-50 last:border-0 group">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 flex items-center justify-center bg-slate-100 rounded-md font-bold text-slate-500">${i.cantidad}</span>
                            <span class="font-bold text-slate-600 uppercase">${i.descripcion}</span>
                        </div>
                        <span class="font-black text-slate-400 group-hover:text-navy-blue transition-colors">${AppUtils.formatCurrency(i.subtotal_item)}</span>
                    </div>
                `).join('')}
            </div>
        </div>
    `}).join('');
    lucide.createIcons();
}

function filtrarAuditoria(term) {
    if (!rawAuditData) return;
    const t = term.toLowerCase();
    const filtrados = rawAuditData.ventas.filter(v =>
        v.modelo_vehiculo.toLowerCase().includes(t) ||
        v.placa.toLowerCase().includes(t) ||
        v.descripcion.toLowerCase().includes(t) ||
        String(v.id).includes(t)
    );
    renderAuditoriaLista(filtrados);
}