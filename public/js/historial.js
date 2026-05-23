document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchVentas');

    const salesTable = $('#salesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: `${URLROOT}/historial/listar`,
            type: 'GET'
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'id', className: 'font-mono text-xs text-slate-500' },
            {
                data: 'fecha',
                render: d => new Date(d).toLocaleDateString('es-CO', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
            },
            {
                data: null,
                render: (data, type, row) => `
                    <div class="font-bold text-slate-700 uppercase">${row.modelo_vehiculo || 'N/A'}</div>
                    <div class="text-[10px] text-slate-400">${row.placa || 'Sin Placa'}</div>`
            },
            { data: 'cliente_nombre', defaultContent: 'Sin Cliente' },
            { data: 'total', render: d => `<span class="font-bold text-navy-blue">${AppUtils.formatCurrency(d)}</span>` },
            {
                data: 'id',
                className: 'text-right',
                render: d => `<button onclick="openSaleDetailModal(${d})" class="p-2 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl transition-all"><i data-lucide="eye" class="w-4 h-4"></i></button>`
            }
        ],
        drawCallback: () => { if (window.lucide) lucide.createIcons(); },
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
    });

    searchInput.addEventListener('input', AppUtils.debounce((e) => {
        salesTable.search(e.target.value).draw();
    }, 400));

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

            Swal.fire({
                title: `<span class="text-sm uppercase text-slate-400">Detalle de Venta:</span><br>#${venta.id}`,
                html: `
                    <div class="text-left space-y-4 pt-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-slate-500 font-bold">Fecha:</p>
                                <p>${new Date(venta.fecha).toLocaleString('es-CO')}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 font-bold">Responsable:</p>
                                <p>${venta.usuario_nombre || 'N/A'} (${venta.usuario_cargo || 'N/A'})</p>
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

                        <div class="flex justify-between text-sm font-bold">
                            <p>Subtotal:</p>
                            <p>${AppUtils.formatCurrency(venta.subtotal)}</p>
                        </div>
                        <div class="flex justify-between text-sm font-bold">
                            <p>IVA (${(venta.iva_monto / venta.subtotal * 100).toFixed(0)}%):</p>
                            <p>${AppUtils.formatCurrency(venta.iva_monto)}</p>
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