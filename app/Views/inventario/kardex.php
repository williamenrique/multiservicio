<div class="container mx-auto p-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-navy-blue">Kardex de: <span class="text-neon-green"><?php echo s($producto->nombre); ?></span></h2>
        <a href="<?php echo URLROOT; ?>/inventario" class="bg-slate-200 text-slate-700 px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:bg-slate-300 transition shadow-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver al Inventario
        </a>
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
        <h3 class="text-lg font-semibold text-slate-600 mb-4">Movimientos de Kardex</h3>
        <?php if (!empty($kardexMovimientos)): ?>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Fecha</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tipo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Cantidad</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Stock Anterior</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Stock Actual</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Referencia</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php foreach ($kardexMovimientos as $mov): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo s(date('d/m/Y H:i', strtotime($mov->fecha))); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo ($mov->tipo_movimiento === 'ENTRADA_COMPRA' || $mov->tipo_movimiento === 'DEVOLUCION') ? 'text-emerald-600' : 'text-rose-600'; ?>"><?php echo s(str_replace('_', ' ', $mov->tipo_movimiento)); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo s($mov->cantidad); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo s($mov->stock_anterior); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo s($mov->stock_actual); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo s($mov->referencia_id); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo s($mov->observaciones); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-10 text-slate-400 italic font-bold uppercase tracking-widest">
                No hay movimientos de kardex para este producto.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($costHistory)): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const costHistoryData = <?php echo json_encode($costHistory); ?>;
        const ctx = document.getElementById('costHistoryChart').getContext('2d');

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
    });
</script>
<?php endif; ?>