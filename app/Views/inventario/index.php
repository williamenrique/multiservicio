<section id="sec-inventario" class="content-section">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Control de Inventario</h2>
        <button onclick="openInventoryModal()"
            class="bg-neon-green text-black px-4 py-2 rounded-lg font-bold flex items-center gap-2 hover:opacity-90 transition shadow-sm">
            <i data-lucide="plus-circle"></i> Nuevo Producto
        </button>
    </div>
    <div class="glass-card p-6 rounded-xl w-full">
        <table id="inventoryTable" class="display w-full">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Stock</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="inventoryBody"></tbody>
        </table>
    </div>
</section>