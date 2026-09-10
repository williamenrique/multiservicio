/**
 * Facturas - Manejo de tabla con DataTableRefactor
 * Listado de todas las facturas realizadas con paginación, búsqueda y filtros de fecha
 */

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar DataTableRefactor para la tabla de facturas
    window.handler_facturas = new DataTableRefactor({
        tableId: 'facturas',
        tableBodyId: 'tableBody',
        endpoint: `${URLROOT}/facturas/listar`,
        searchInputId: 'searchFacturas',
        limitSelectorId: 'limitSelector',
        paginationId: 'paginationControls',
        totalId: 'totalCount',
        startId: 'startIndex',
        endId: 'endIndex',
        noDataMessage: 'No se encontraron facturas',
        getExtraParams: () => ({
            desde: document.getElementById('fechaDesde')?.value || '',
            hasta: document.getElementById('fechaHasta')?.value || ''
        }),
        onDataLoaded: (res) => {
            window.currentData = res.data;
            lucide.createIcons();
        },
        renderRow: (item) => {
            // Formatear fecha
            const fecha = new Date(item.fecha);
            const fechaFormateada = fecha.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }) + ' ' + fecha.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });

            // Badge de estado
            let estadoBadge = '';
            switch (item.status) {
                case 'COMPLETADO':
                    estadoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800"><i data-lucide="check-circle" class="w-3 h-3 mr-1"></i>COMPLETADO</span>';
                    break;
                case 'CREDITO':
                    estadoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800"><i data-lucide="clock" class="w-3 h-3 mr-1"></i>CRÉDITO</span>';
                    break;
                case 'ANULADO':
                    estadoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800"><i data-lucide="x-circle" class="w-3 h-3 mr-1"></i>ANULADO</span>';
                    break;
                default:
                    estadoBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800">${item.status}</span>`;
            }

            // Badge de tipo de procedencia
            let tipoBadge = '';
            switch (item.tipo_procedencia) {
                case 'OS':
                    tipoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800"><i data-lucide="clipboard-list" class="w-3 h-3 mr-1"></i>O.S.</span>';
                    break;
                case 'TALLER':
                    tipoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800"><i data-lucide="wrench" class="w-3 h-3 mr-1"></i>TALLER</span>';
                    break;
                case 'MOSTRADOR':
                    tipoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800"><i data-lucide="shopping-cart" class="w-3 h-3 mr-1"></i>MOSTRADOR</span>';
                    break;
                case 'GARANTIA':
                    tipoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800"><i data-lucide="shield-check" class="w-3 h-3 mr-1"></i>GARANTÍA</span>';
                    break;
                default:
                    tipoBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800">${item.tipo_procedencia}</span>`;
            }

            // Total de items (productos + servicios)
            const totalItems = (parseInt(item.cant_productos) || 0) + (parseInt(item.cant_servicios) || 0);
            const itemsText = totalItems > 0
                ? `<span class="font-bold text-navy-blue">${totalItems}</span> <span class="text-slate-400 text-xs">(${item.cant_productos || 0}P + ${item.cant_servicios || 0}S)</span>`
                : '<span class="text-slate-400">-</span>';

            // Formatear total
            const totalFormateado = new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(item.total);

            // Cliente
            const clienteNombre = item.cliente_nombre ? s(item.cliente_nombre) : '<span class="text-slate-400 italic">Consumidor Final</span>';

            // Placa/Vehículo
            const placa = item.placa ? s(item.placa) : '<span class="text-slate-400 italic">-</span>';
            const modelo = item.modelo_vehiculo ? `<br><span class="text-xs text-slate-400">${s(item.modelo_vehiculo)}</span>` : '';

            // Vendedor
            const vendedor = item.vendedor_nombre ? s(item.vendedor_nombre) : '<span class="text-slate-400 italic">-</span>';

            return `
                <tr class="hover:bg-slate-50/50 transition-colors" data-id="${item.id}">
                    <td class="px-6 py-4">
                        <span class="font-mono font-bold text-navy-blue text-sm">${item.id_formateado}</span>
                    </td>
                    <td class="px-6 py-4 text-slate-600 whitespace-nowrap">${fechaFormateada}</td>
                    <td class="px-6 py-4">${clienteNombre}</td>
                    <td class="px-6 py-4">${placa}${modelo}</td>
                    <td class="px-6 py-4">${tipoBadge}</td>
                    <td class="px-6 py-4">${vendedor}</td>
                    <td class="px-6 py-4 text-center">${itemsText}</td>
                    <td class="px-6 py-4 text-center">${estadoBadge}</td>
                    <td class="px-6 py-4 text-right font-bold text-navy-blue">${totalFormateado}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="verFactura(${item.id})" 
                                class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-all" 
                                title="Ver detalle">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button onclick="imprimirFactura(${item.id})" 
                                class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-all" 
                                title="Imprimir PDF">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
    });

    // Event listeners para filtros de fecha
    const fechaDesde = document.getElementById('fechaDesde');
    const fechaHasta = document.getElementById('fechaHasta');
    const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');

    if (fechaDesde) {
        fechaDesde.addEventListener('change', () => {
            window.handler_facturas.state.page = 1;
            window.handler_facturas.reload();
        });
    }

    if (fechaHasta) {
        fechaHasta.addEventListener('change', () => {
            window.handler_facturas.state.page = 1;
            window.handler_facturas.reload();
        });
    }

    if (btnLimpiarFiltros) {
        btnLimpiarFiltros.addEventListener('click', () => {
            if (fechaDesde) fechaDesde.value = '';
            if (fechaHasta) fechaHasta.value = '';
            if (window.handler_facturas.searchInput) window.handler_facturas.searchInput.value = '';
            window.handler_facturas.state.search = '';
            window.handler_facturas.state.page = 1;
            window.handler_facturas.reload();
        });
    }

    // Funciones globales para botones de acción
    window.verFactura = function (id) {
        window.location.href = `${URLROOT}/facturas/ver/${id}`;
    };

    window.imprimirFactura = function (id) {
        window.open(`${URLROOT}/facturas/imprimir/${id}`, '_blank');
    };
});

// Función helper para escapar HTML (si no existe globalmente)
if (typeof s === 'undefined') {
    function s(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&')
            .replace(/</g, '<')
            .replace(/>/g, '>')
            .replace(/"/g, '"')
            .replace(/'/g, '&#039;');
    }
    window.s = s;
}