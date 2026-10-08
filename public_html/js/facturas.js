/**
 * Facturas - Listado con filtros rápidos, ver cliente y recordatorios
 * 
 * v2.3 (2026-10-08):
 *   • MEJORA 9: Chips de filtro por estado (Todas / Crédito / Completadas / Anuladas).
 *   • MEJORA 10: Nombre del cliente clickeable → abre drawer de cartera.
 *   • MEJORA 11: Badge "Último abono" bajo el Total cuando existe.
 *   • MEJORA 12: Botón "Recordatorio" en facturas con saldo pendiente.
 * 
 * v2.2: Columna Total muestra "total$ / deuda$" con colores.
 * v2.1: Columna Items con whitespace-nowrap.
 */

let estadoActual = '';

document.addEventListener('DOMContentLoaded', () => {
    // MEJORA 9: Manejo de chips de estado
    const chips = document.querySelectorAll('.estado-chip');
    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            chips.forEach(c => {
                c.classList.remove('active');
                c.style.backgroundColor = '';
                c.style.color = '';
                c.style.borderColor = '';
            });
            chip.classList.add('active');
            estadoActual = chip.dataset.estado || '';

            if (window.handler_facturas) {
                window.handler_facturas.state.page = 1;
                window.handler_facturas.reload();
            }
        });
    });

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
            hasta: document.getElementById('fechaHasta')?.value || '',
            estado: estadoActual
        }),
        onDataLoaded: (res) => {
            window.currentData = res.data;
            lucide.createIcons();
        },
        renderRow: (item) => {
            const fecha = new Date(item.fecha);
            const fechaFormateada = fecha.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
                                    fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

            // Badge estado
            let estadoBadge = '';
            switch (item.status) {
                case 'COMPLETADO': estadoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800"><i data-lucide="check-circle" class="w-3 h-3 mr-1"></i>COMPLETADO</span>'; break;
                case 'CREDITO': estadoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800"><i data-lucide="clock" class="w-3 h-3 mr-1"></i>CRÉDITO</span>'; break;
                case 'ANULADO': estadoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800"><i data-lucide="x-circle" class="w-3 h-3 mr-1"></i>ANULADO</span>'; break;
                default: estadoBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800">${item.status}</span>`;
            }

            // Badge tipo
            let tipoBadge = '';
            const origen = (item.origen || '').toUpperCase();
            if (origen === 'PRESUPUESTO') tipoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-violet-100 text-violet-800"><i data-lucide="file-text" class="w-3 h-3 mr-1"></i>PRESUPUESTO</span>';
            else if (origen === 'GARANTIA') tipoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800"><i data-lucide="shield-check" class="w-3 h-3 mr-1"></i>GARANTÍA</span>';
            else if (origen === 'CATALOGO') tipoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-cyan-100 text-cyan-800"><i data-lucide="shopping-bag" class="w-3 h-3 mr-1"></i>CATÁLOGO</span>';
            else {
                switch (item.tipo_procedencia) {
                    case 'OS': tipoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800"><i data-lucide="clipboard-list" class="w-3 h-3 mr-1"></i>O.S.</span>'; break;
                    case 'TALLER': tipoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800"><i data-lucide="wrench" class="w-3 h-3 mr-1"></i>TALLER</span>'; break;
                    case 'MOSTRADOR': tipoBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800"><i data-lucide="shopping-cart" class="w-3 h-3 mr-1"></i>MOSTRADOR</span>'; break;
                    default: tipoBadge = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800">${item.tipo_procedencia || 'N/A'}</span>`;
                }
            }

            // Items
            const totalItems = (parseInt(item.cant_productos) || 0) + (parseInt(item.cant_servicios) || 0);
            const itemsText = totalItems > 0
                ? `<span class="font-bold text-navy-blue whitespace-nowrap">${totalItems}</span> <span class="text-slate-400 text-xs whitespace-nowrap">(${item.cant_productos || 0}P + ${item.cant_servicios || 0}S)</span>`
                : '<span class="text-slate-400">-</span>';

            // Total / Debe
            const fmt = (n) => new Intl.NumberFormat('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n || 0);
            const saldo = parseFloat(item.saldo_pendiente) || 0;
            const totalFormateado = fmt(item.total);

            let totalDebeCell = '';
            if (item.status === 'ANULADO') {
                totalDebeCell = `<span class="text-slate-300 font-bold line-through">${totalFormateado}$</span>`;
            } else if (saldo > 0.05) {
                totalDebeCell = `<span class="font-bold text-navy-blue">${totalFormateado}$</span><span class="text-slate-400 font-bold mx-1">/</span><span class="font-black text-rose-600">${fmt(saldo)}$</span>`;
            } else {
                totalDebeCell = `<span class="font-bold text-navy-blue">${totalFormateado}$</span><span class="text-slate-400 font-bold mx-1">/</span><span class="font-bold text-slate-300">0$</span>`;
            }

            // MEJORA 11: Badge "Último abono"
            let ultimoAbonoBadge = '';
            if (item.ultimo_abono_fecha && item.ultimo_abono_monto > 0) {
                const fechaAbono = new Date(item.ultimo_abono_fecha);
                const fechaAbonoFmt = fechaAbono.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
                ultimoAbonoBadge = `
                    <div class="mt-1 flex items-center justify-end gap-1">
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100">
                            <i data-lucide="check" class="w-2.5 h-2.5"></i>
                            Últ. abono ${fechaAbonoFmt}: ${fmt(item.ultimo_abono_monto)}$
                        </span>
                    </div>
                `;
            }

            // Cliente (MEJORA 10: clickeable si tiene cliente_id)
            let clienteNombre;
            if (item.cliente_nombre && item.cliente_id) {
                const safeNombre = item.cliente_nombre.replace(/'/g, "\\'");
                clienteNombre = `
                    <button onclick="verClienteDesdeFactura('${item.cliente_id}', '${safeNombre}')" 
                            class="text-left hover:text-navy-blue hover:underline font-bold text-slate-700 transition-colors"
                            title="Ver cartera del cliente">
                        ${s(item.cliente_nombre)}
                    </button>`;
            } else {
                clienteNombre = item.cliente_nombre ? s(item.cliente_nombre) : '<span class="text-slate-400 italic">Consumidor Final</span>';
            }

            const placa = item.placa ? s(item.placa) : '<span class="text-slate-400 italic">-</span>';
            const modelo = item.modelo_vehiculo ? `<br><span class="text-xs text-slate-400">${s(item.modelo_vehiculo)}</span>` : '';
            const vendedor = item.vendedor_nombre ? s(item.vendedor_nombre) : '<span class="text-slate-400 italic">-</span>';

            // MEJORA 12: botón recordatorio (solo si tiene saldo)
            const btnRecordatorio = (saldo > 0.05 && item.status === 'CREDITO' && item.cliente_id) 
                ? `<button onclick="enviarRecordatorio(${item.id})" 
                           class="p-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-all" 
                           title="Enviar recordatorio de pago">
                       <i data-lucide="bell-ring" class="w-4 h-4"></i>
                   </button>`
                : '';

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
                    <td class="px-6 py-4 text-center whitespace-nowrap">${itemsText}</td>
                    <td class="px-6 py-4 text-center">${estadoBadge}</td>
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        ${totalDebeCell}
                        ${ultimoAbonoBadge}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            ${btnRecordatorio}
                            <button onclick="verFactura(${item.id})" class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-all" title="Ver detalle">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button onclick="imprimirFactura(${item.id})" class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-all" title="Imprimir PDF">
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

    if (fechaDesde) fechaDesde.addEventListener('change', () => { window.handler_facturas.state.page = 1; window.handler_facturas.reload(); });
    if (fechaHasta) fechaHasta.addEventListener('change', () => { window.handler_facturas.state.page = 1; window.handler_facturas.reload(); });

    if (btnLimpiarFiltros) {
        btnLimpiarFiltros.addEventListener('click', () => {
            if (fechaDesde) fechaDesde.value = '';
            if (fechaHasta) fechaHasta.value = '';
            if (window.handler_facturas.searchInput) window.handler_facturas.searchInput.value = '';
            window.handler_facturas.state.search = '';
            window.handler_facturas.state.page = 1;

            // Reset chips
            chips.forEach(c => c.classList.remove('active'));
            const todas = document.querySelector('.estado-chip[data-estado=""]');
            if (todas) todas.classList.add('active');
            estadoActual = '';

            window.handler_facturas.reload();
        });
    }

    window.verFactura = function (id) {
        window.location.href = `${URLROOT}/facturas/ver/${id}`;
    };

    window.imprimirFactura = function (id) {
        window.open(`${URLROOT}/facturas/imprimir/${id}`, '_blank');
    };

    /**
     * MEJORA 10: Abre el drawer de cartera del cliente desde la tabla de facturas.
     */
    window.verClienteDesdeFactura = function (clienteId, clienteNombre) {
        if (typeof window.verDetalleClienteCartera === 'function') {
            window.verDetalleClienteCartera(clienteId, clienteNombre);
        } else {
            // Fallback: redirigir a reportes de cartera
            window.location.href = `${URLROOT}/reportes#cartera`;
        }
    };

    /**
     * MEJORA 12: Envía un recordatorio de pago por email.
     */
    window.enviarRecordatorio = async function (facturaId) {
        const confirm = await Swal.fire({
            title: 'Enviar Recordatorio',
            text: 'Se enviará un email al cliente con el saldo pendiente.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            confirmButtonText: 'Sí, enviar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirm.isConfirmed) return;

        try {
            AppUtils.showLoading('Enviando...');
            const res = await fetch(`${URLROOT}/facturacion/enviarRecordatorio`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ factura_id: facturaId })
            });
            const data = await res.json();
            AppUtils.hideLoading();

            if (data.success) {
                AppUtils.showToast(data.mensaje || 'Recordatorio enviado');
            } else {
                AppUtils.showToast(data.mensaje || 'Error al enviar', 'error');
            }
        } catch (e) {
            AppUtils.hideLoading();
            console.error(e);
            AppUtils.showToast('Error de conexión', 'error');
        }
    };
});

if (typeof s === 'undefined') {
    function s(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    window.s = s;
}