<div class="space-y-6">
    <div class="bg-navy-blue p-6 rounded-xl border border-gray-800 shadow-lg flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                <i data-lucide="package" class="text-neon-green"></i> Kardex de Inventario
            </h2>
            <p class="text-gray-400">Historial de movimientos para el producto: <span class="font-bold text-white"><?php echo s($producto->nombre); ?></span></p>
        </div>
        <a href="<?php echo URLROOT; ?>/inventario" class="text-gray-400 hover:text-white transition-colors">
            <i data-lucide="x-circle" class="w-8 h-8"></i>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Información del Producto -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
            <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Detalles del Producto</h3>
            <div class="space-y-3">
                <p class="flex justify-between text-sm">
                    <span class="text-gray-500">Nombre:</span>
                    <span class="font-bold text-slate-800"><?php echo s($producto->nombre); ?></span>
                </p>
                <p class="flex justify-between text-sm">
                    <span class="text-gray-500">Categoría:</span>
                    <span class="font-bold text-slate-800"><?php echo s($producto->categoria); ?></span>
                </p>
                <p class="flex justify-between text-sm">
                    <span class="text-gray-500">Stock Actual:</span>
                    <span class="font-bold text-slate-800"><?php echo $producto->stock; ?></span>
                </p>
                <p class="flex justify-between text-sm">
                    <span class="text-gray-500">Stock Mínimo:</span>
                    <span class="font-bold text-slate-800"><?php echo $producto->stock_minimo; ?></span>
                </p>
                <p class="flex justify-between text-sm">
                    <span class="text-gray-500">Último Costo:</span>
                    <span class="font-bold text-slate-800">$<?php echo number_format($producto->ultimo_costo, 2); ?></span>
                </p>
                <p class="flex justify-between text-sm">
                    <span class="text-gray-500">Precio Venta:</span>
                    <span class="font-bold text-slate-800">$<?php echo number_format($producto->precio, 2); ?></span>
                </p>
            </div>
        </div>

        <!-- Tabla de Movimientos Kardex -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-700 uppercase text-sm tracking-wider flex items-center gap-2">
                    <i data-lucide="list-ordered" class="w-4 h-4"></i> Movimientos
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-600 text-[11px] uppercase tracking-widest">
                            <th class="px-6 py-4 font-bold">Fecha</th>
                            <th class="px-6 py-4 font-bold">Tipo</th>
                            <th class="px-6 py-4 font-bold">Cantidad</th>
                            <th class="px-6 py-4 font-bold">Stock Anterior</th>
                            <th class="px-6 py-4 font-bold">Stock Actual</th>
                            <th class="px-6 py-4 font-bold">Usuario</th>
                            <th class="px-6 py-4 font-bold">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($kardexMovimientos)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">No hay movimientos registrados para este producto.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($kardexMovimientos as $mov): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-sm"><?php echo date('d/m/Y H:i', strtotime($mov->fecha)); ?></td>
                                <td class="px-6 py-4 text-sm font-bold <?php echo (strpos($mov->tipo_movimiento, 'ENTRADA') !== false || strpos($mov->tipo_movimiento, 'DEVOLUCION') !== false) ? 'text-green-600' : 'text-red-600'; ?>"><?php echo s(str_replace('_', ' ', $mov->tipo_movimiento)); ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo $mov->cantidad; ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo $mov->stock_anterior; ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo $mov->stock_actual; ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo s($mov->usuario_nombre); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo s($mov->observaciones); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>