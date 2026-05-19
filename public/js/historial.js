document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const searchInput = document.getElementById('searchVentas');
    const totalCount = document.getElementById('totalCount');

    let ventas = [];

    /**
     * Carga los datos del historial de ventas desde el servidor.
     */
    const loadData = async () => {
        try {
            // Llama al método listar() en ControllerHistorial
            const res = await fetch(`${URLROOT}/historial/listar`);
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            ventas = await res.json();
            renderTable(ventas);
        } catch (e) {
            console.error("Error al cargar el historial de ventas:", e);
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-red-500">Error de conexión o al cargar datos.</td></tr>';
        }
    };

    /**
     * Renderiza la tabla de ventas con los datos proporcionados.
     * @param {Array} data Array de objetos de venta.
     */
    const renderTable = (data) => {
        tableBody.innerHTML = '';
        totalCount.textContent = data.length;

        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-slate-400 italic">No hay ventas registradas.</td></tr>';
            return;
        }

        data.forEach(venta => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 transition-colors border-b border-slate-100';
            row.innerHTML = `
                <td class="px-8 py-5 font-mono text-xs text-slate-500">#${venta.id}</td>
                <td class="px-8 py-5">${new Date(venta.fecha).toLocaleDateString('es-CO', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
                <td class="px-8 py-5">
                    <div class="font-bold text-slate-700 uppercase">${venta.modelo_vehiculo || 'N/A'}</div>
                    <div class="text-[10px] text-slate-400">${venta.placa || 'Sin Placa'}</div>
                </td>
                <td class="px-8 py-5">${venta.cliente_nombre || 'Sin Cliente'}</td>
                <td class="px-8 py-5 font-bold text-navy-blue">${AppUtils.formatCurrency(venta.total)}</td>
                <td class="px-8 py-5 text-right">
                    <button onclick="openSaleDetailModal(${venta.id})" class="p-2 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl transition-all" title="Ver Detalles"><i data-lucide="eye" class="w-4 h-4"></i></button>
                </td>
            `;
            tableBody.appendChild(row);
        });
        lucide.createIcons();
    };

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

    /**
     * Maneja la búsqueda en tiempo real en la tabla de ventas.
     */
    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        const filtered = ventas.filter(v =>
            String(v.id).includes(term) ||
            (v.placa && v.placa.toLowerCase().includes(term)) ||
            (v.modelo_vehiculo && v.modelo_vehiculo.toLowerCase().includes(term)) ||
            (v.cliente_nombre && v.cliente_nombre.toLowerCase().includes(term))
        );
        renderTable(filtered);
    });

    // Cargar datos al iniciar la página
    loadData();
});