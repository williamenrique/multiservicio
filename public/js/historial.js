document.addEventListener('DOMContentLoaded', () => {
    const formatearFechaHora = (value) => {
        if (!value) return 'N/A';
        return new Date(value).toLocaleString('es-CO', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    };

    new DataTableRefactor({
        tableId: 'historial',
        tableBodyId: 'tableBody',
        endpoint: `${URLROOT}/historial/listar`,
        searchInputId: 'searchVentas',
        limitSelectorId: 'limitSelector',
        paginationId: 'paginationControls',
        totalId: 'totalCount',
        renderRow: (venta) => {
            const isCredit = venta.status === 'CREDITO';
            return `
                <tr class="hover:bg-slate-50 transition-colors border-b border-slate-100 ${isCredit ? 'bg-red-50/50' : ''}">
                    <td class="px-8 py-5 font-mono text-xs text-slate-500">#${venta.id}</td>
                    <td class="px-8 py-5">${formatearFechaHora(venta.fecha)}</td>
                    <td class="px-8 py-5 font-mono text-sm font-bold text-slate-400 text-center">#${venta.id}</td>
                    <td class="px-8 py-5 text-base font-bold text-slate-600 uppercase text-center">${new Date(venta.fecha).toLocaleDateString('es-CO', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
                    <td class="px-8 py-5">
                        <div class="font-bold text-slate-700 uppercase flex items-center gap-2">
                        <div class="font-bold text-base text-slate-800 uppercase flex items-center gap-2 leading-tight">
                            ${venta.modelo_vehiculo || 'N/A'}
                            ${isCredit ? '<span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>' : ''}
                        </div>
                        <div class="text-[10px] font-black ${venta.placa ? 'text-slate-400' : 'text-blue-500'}">${venta.placa ? 'PLACA: ' + venta.placa : 'VENTA MOSTRADOR'}</div>
                        <div class="text-xs font-black ${venta.placa ? 'text-slate-500' : 'text-blue-600'} uppercase tracking-tight">${venta.placa ? 'PLACA: ' + venta.placa : 'VENTA MOSTRADOR'}</div>
                    </td>
                    <td class="px-8 py-5">${venta.cliente_nombre || 'Sin Cliente'}</td>
                    <td class="px-8 py-5 font-bold text-navy-blue">${AppUtils.formatCurrency(venta.total)}</td>
                    <td class="px-8 py-5 text-base font-bold text-slate-700 uppercase">${venta.cliente_nombre || 'Sin Cliente'}</td>
                    <td class="px-8 py-5 font-black text-lg text-navy-blue">${AppUtils.formatCurrency(venta.total)}</td>
                    <td class="px-8 py-5 text-right">
                        <button onclick="openSaleDetailModal(${venta.id})" class="p-2 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl transition-all" title="Ver Detalles"><i data-lucide="eye" class="w-4 h-4"></i></button>
                    </td>
                </tr>`;
        }
    });

    /**
     * Abre un modal con los detalles de una venta específica.
     * @param {number} ventaId ID de la venta a mostrar.
     */
    window.openSaleDetailModal = async (ventaId) => {
        try {
            // Llama al método detalle($id) en ControllerHistorial
            const res = await fetch(`${URLROOT}/historial/detalle/${ventaId}`);
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const venta = await res.json();

            if (!venta) {
                AppUtils.showToast('Detalle de venta no encontrado.', 'error');
                return;
            }

            const statusBadge = venta.status === 'CREDITO'
                ? '<span class="px-3 py-1 rounded-full bg-red-100 text-red-600 text-[10px] font-black uppercase border border-red-200">Crédito Pendiente</span>'
                : '<span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-600 text-[10px] font-black uppercase border border-emerald-200">Pago Completado</span>';

            Swal.fire({
                title: `
                    <div class="flex justify-between items-center w-full pr-6">
                        <div class="text-left">
                            <span class="text-sm uppercase text-slate-400">Detalle de Trabajo:</span><br>#${venta.id}
                        </div>
                        ${statusBadge}
                    </div>`,
                html: `
                    <div class="text-left space-y-4 pt-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-slate-500 font-bold">Fecha:</p>
                                <p>${formatearFechaHora(venta.fecha)}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 font-bold">Técnico Asignado:</p>
                                <p class="font-black text-navy-blue uppercase">${venta.mecanico_nombre || venta.usuario_nombre || 'N/A'}</p>
                                ${venta.mecanico_nombre && venta.mecanico_nombre !== venta.usuario_nombre ? `<p class="text-[10px] text-slate-400 uppercase">Facturado por: ${venta.usuario_nombre}</p>` : ''}
                            </div>
                            <div>
                                <p class="text-slate-500 font-bold">Cliente:</p>
                                <p>${venta.cliente_nombre || 'Sin Cliente'}</p>
                                ${venta.cliente_telefono ? `<p class="text-xs text-slate-400">${venta.cliente_telefono}</p>` : ''}
                            </div>
                            <div>
                                <p class="text-slate-500 font-bold">Vehículo:</p>
                                <p>${venta.modelo_vehiculo || 'N/A'}</p>
                                ${venta.placa ? `<p class="text-xs text-slate-400">Placa: ${venta.placa}</p>` : ''}
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

                        <hr class="my-4 border-t border-slate-200">

                        <p class="text-xs text-slate-500 uppercase font-bold mb-2">Items Vendidos:</p>
                        <div class="max-h-60 overflow-y-auto border border-slate-200 rounded-lg p-2">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-slate-400">
                                        <th class="text-left p-1">Descripción</th>
                                        <th class="p-1">Cant.</th>
                                        <th class="text-right p-1">P. Unit.</th>
                                        <th class="text-right p-1">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${venta.items.map(item => `
                                        <tr class="border-b border-slate-100 last:border-b-0">
                                            <td class="text-left p-1">${item.descripcion}</td>
                                            <td class="text-center p-1">${item.cantidad}</td>
                                            <td class="text-right p-1">${AppUtils.formatCurrency(item.precio_unitario)}</td>
                                            <td class="text-right p-1 font-bold">${AppUtils.formatCurrency(item.cantidad * item.precio_unitario)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-4 border-t border-slate-200">

                        <div class="grid grid-cols-3 gap-2 text-[10px] mb-4">
                            <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                                <p class="text-slate-400 font-bold uppercase mb-1">Efectivo</p>
                                <p class="font-black text-slate-700 text-sm">${AppUtils.formatCurrency(venta.pago_efectivo)}</p>
                            </div>
                            <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                                <p class="text-slate-400 font-bold uppercase mb-1">Transferencia</p>
                                <p class="font-black text-slate-700 text-sm">${AppUtils.formatCurrency(venta.pago_transferencia)}</p>
                            </div>
                            <div class="p-2 ${venta.saldo_pendiente > 0 ? 'bg-red-50 border-red-100' : 'bg-slate-50 border-slate-100'} rounded-xl border">
                                <p class="${venta.saldo_pendiente > 0 ? 'text-red-400' : 'text-slate-400'} font-bold uppercase mb-1">Deuda</p>
                                <p class="font-black ${venta.saldo_pendiente > 0 ? 'text-red-600' : 'text-slate-700'} text-sm">${AppUtils.formatCurrency(venta.saldo_pendiente)}</p>
                            </div>
                        </div>

                        <div class="flex justify-between text-sm font-bold">
                            <p>Subtotal:</p>
                            <p>${AppUtils.formatCurrency(venta.subtotal)}</p>
                        </div>
                        <div class="flex justify-between text-sm font-bold">
                            <p>IVA (${venta.subtotal > 0 ? (venta.iva_monto / venta.subtotal * 100).toFixed(0) : 0}%):</p>
                            <p>${AppUtils.formatCurrency(venta.iva_monto)}</p>
                        </div>
                        <div class="flex justify-between text-sm font-bold text-emerald-600">
                            <p>Total Abonado:</p>
                            <p>${AppUtils.formatCurrency(parseFloat(venta.pago_efectivo) + parseFloat(venta.pago_transferencia))}</p>
                        </div>
                        <div class="flex justify-between text-lg font-black text-navy-blue">
                            <p>TOTAL:</p>
                            <p>${AppUtils.formatCurrency(venta.total)}</p>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cerrar',
                width: '600px'
            });
        } catch (e) {
            console.error("Error al obtener detalle de venta:", e);
            AppUtils.showToast('Error al cargar el detalle de la venta.', 'error');
        }
    };
});