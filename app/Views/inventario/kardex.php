<div class="container mx-auto p-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-navy-blue">Kardex de: <span class="text-neon-green"><?php echo s($producto->nombre); ?></span></h2>
        <div class="flex gap-3">
            <button id="btnPrintAll" class="bg-navy-blue text-white px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:bg-slate-800 transition shadow-sm">
                <i data-lucide="printer" class="w-4 h-4"></i> IMPRIMIR HISTORIAL
            </button>
            <a href="<?php echo URLROOT; ?>/inventario" class="bg-slate-200 text-slate-700 px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:bg-slate-300 transition shadow-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-1 glass-card p-6 rounded-xl border border-slate-100 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-600 mb-4">Detalles del Producto</h3>
            <div class="flex items-center gap-4 mb-4">
                <?php if (!empty($producto->imagen)): ?>
                    <img src="<?php echo URLROOT . '/' . s($producto->imagen); ?>" class="w-20 h-20 object-cover rounded-lg border border-slate-200" alt="Imagen Producto">
                <?php else: ?>
                    <div class="w-20 h-20 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400">
                        <i data-lucide="image-off" class="w-10 h-10"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <p class="text-xl font-black text-navy-blue uppercase"><?php echo s($producto->nombre); ?></p>
                    <p class="text-sm text-slate-500">Categoría: <span class="font-bold"><?php echo s($producto->categoria); ?></span></p>
                </div>
            </div>
            <div class="space-y-2">
                <p class="text-sm text-slate-600">Stock Actual: <span class="font-bold text-navy-blue"><?php echo s($producto->stock); ?></span></p>
                <p class="text-sm text-slate-600">Stock Mínimo: <span class="font-bold text-rose-500"><?php echo s($producto->stock_minimo); ?></span></p>
                <p class="text-sm text-slate-600">Último Costo: <span class="font-bold text-emerald-600"><?php echo '$ ' . number_format((float)$producto->ultimo_costo, 2, ',', '.'); ?></span></p>
                <p class="text-sm text-slate-600">Precio Venta: <span class="font-bold text-blue-600"><?php echo '$ ' . number_format((float)$producto->precio, 2, ',', '.'); ?></span></p>
            </div>
        </div>

        <div class="lg:col-span-2 glass-card p-6 rounded-xl border border-slate-100 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-600 mb-4">Historial de Costos (Gráfico)</h3>
            <?php if (!empty($costHistory)): ?>
                <div class="relative h-64 md:h-80 w-full">
                    <canvas id="costHistoryChart"></canvas>
                </div>
            <?php else: ?>
                <div class="text-center py-10 text-slate-400 italic font-bold uppercase tracking-widest">
                    No hay datos de compras para mostrar el historial de costos.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="glass-card p-6 rounded-xl w-full">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h3 class="text-lg font-semibold text-slate-600">Movimientos de Kardex</h3>
            <div class="flex gap-4 w-full md:w-auto">
                <div class="relative flex-1 md:min-w-[300px]">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                    <input type="text" id="searchKardex" placeholder="Filtrar movimientos..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-neon-green outline-none">
                </div>
                <select id="limitSelector" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="10">10 registros</option>
                    <option value="25">25 registros</option>
                    <option value="50">50 registros</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                        <th class="px-6 py-4 text-left">ID</th>
                        <th class="px-6 py-4 text-left">Fecha</th>
                        <th class="px-6 py-4 text-left">Tipo</th>
                        <th class="px-6 py-4 text-left">Cantidad</th>
                        <th class="px-6 py-4 text-left">Flujo Stock</th>
                        <th class="px-6 py-4 text-center">Referencia</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBodyKardex" class="bg-white divide-y divide-slate-100 text-sm text-slate-600">
                    <!-- Contenido dinámico -->
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <p id="paginationInfo" class="text-xs font-bold text-slate-400 uppercase tracking-widest">Mostrando movimientos...</p>
            <div id="paginationControls" class="flex gap-2"></div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', () => {
        // 1. Gráfico de Costos
        <?php if (!empty($costHistory)): ?>
        const costHistoryData = <?php echo json_encode($costHistory); ?>;
        const canvas = document.getElementById('costHistoryChart');
        if (canvas) {
            const ctx = canvas.getContext('2d');
        const labels = costHistoryData.map(item => new Date(item.fecha).toLocaleDateString());
        const data = costHistoryData.map(item => parseFloat(item.costo_unitario));

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Costo Unitario',
                    data: data,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Permite que el gráfico se ajuste al contenedor
                plugins: {
                    title: {
                        display: true,
                        text: 'Fluctuación del Costo Unitario a lo largo del tiempo'
                    }
                },
                scales: {
                    x: {
                        type: 'category', // Usa 'category' para cadenas de fecha
                        title: {
                            display: true,
                            text: 'Fecha de Compra'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Costo Unitario (COP)'
                        },
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return AppUtils.formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
        }
        <?php endif; ?>

        // 2. Tabla Dinámica
        if (typeof DataTableRefactor === 'undefined') {
            console.error('DataTableRefactor no está cargado. Verifique footer.php');
            return;
        }

    const kardexTable = new DataTableRefactor({
        tableBodyId: 'tableBodyKardex',
        endpoint: `${URLROOT}/inventario/kardexData/<?php echo $producto->id; ?>`,
        limitSelectorId: 'limitSelector',
        searchInputId: 'searchKardex',
        paginationId: 'paginationControls',
            totalId: 'paginationInfo',
        renderRow: (m) => {
                const esEntrada = m.tipo_movimiento.includes('ENTRADA') || m.tipo_movimiento.includes('DEVOLUCION');
            return `
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-mono font-bold text-slate-400">#${m.id}</td>
                        <td class="px-6 py-4 text-xs font-bold text-slate-500">${m.fecha}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-[10px] font-black ${esEntrada ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                ${m.tipo_movimiento.replace('_', ' ')}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-black text-slate-800">${m.cantidad}</td>
                        <td class="px-6 py-4 text-xs text-slate-500">${m.stock_anterior} → <b class="text-navy-blue">${m.stock_actual}</b></td>
                        <td class="px-6 py-4 text-center font-bold text-slate-400">#${m.referencia_id || '---'}</td>
                        <td class="px-6 py-4 text-right">
                            <button onclick="window.open('${URLROOT}/inventario/imprimirMovimiento/${m.id}', '_blank')" class="p-2 bg-slate-100 rounded-lg hover:bg-blue-600 hover:text-white transition-all" title="Detalle PDF">
                                <i data-lucide="file-text" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>`;
            }
        });

        // 3. Botón de Impresión General
        const btnPrint = document.getElementById('btnPrintAll');
        if (btnPrint) {
            btnPrint.onclick = () => window.open(`${URLROOT}/inventario/imprimirKardexCompleto/<?php echo $producto->id; ?>`, '_blank');
        }

        if (window.lucide) lucide.createIcons();
    });
</script>