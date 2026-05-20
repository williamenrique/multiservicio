document.addEventListener('DOMContentLoaded', () => {
    cargarReporte();
});

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