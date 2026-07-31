<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo s($titulo); ?> - <?php echo SITENAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script>const URLROOT = "<?php echo URLROOT; ?>";</script>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .nav-blur { backdrop-filter: blur(12px); background: rgba(15, 23, 42, 0.9); }
        .btn-primary { background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; transition: all 0.2s; }
        .btn-primary:hover { background: #059669; transform: scale(1.02); }
        .btn-outline { border: 2px solid #10b981; color: #10b981; padding: 0.5rem 1rem; border-radius: 0.5rem; transition: all 0.2s; }
        .btn-outline:hover { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; transition: all 0.2s; }
        .btn-danger:hover { background: #dc2626; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <!-- NAVBAR -->
    <nav class="nav-blur border-b border-gray-700/50 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="<?php echo URLROOT; ?>/catalogo" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-white">CATÁLOGO</span>
                </a>
                <a href="<?php echo URLROOT; ?>/catalogo" class="text-sm text-gray-400 hover:text-white transition-colors">&larr; Seguir comprando</a>
            </div>
        </div>
    </nav>

    <!-- CART CONTENT -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Carrito de Compras</h1>

        <?php if (empty($items)): ?>
            <div class="text-center py-20">
                <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-500 mb-2">Tu carrito está vacío</h2>
                <p class="text-gray-400 mb-6">Agrega productos desde nuestro catálogo.</p>
                <a href="<?php echo URLROOT; ?>/catalogo" class="btn-primary inline-block text-base px-8 py-3">Ver Catálogo</a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                <div class="divide-y divide-gray-100">
                    <?php foreach ($items as $item): ?>
                        <div class="p-4 md:p-6 flex items-center gap-4" id="item-<?php echo $item['id']; ?>">
                            <!-- Image -->
                            <div class="w-20 h-20 bg-gray-100 rounded-xl flex-shrink-0 flex items-center justify-center overflow-hidden">
                                <?php if ($item['imagen'] && file_exists(APPROOT . '/../public_html/' . $item['imagen'])): ?>
                                    <img src="<?php echo URLROOT . '/' . $item['imagen']; ?>" alt="<?php echo s($item['nombre']); ?>" class="w-full h-full object-contain p-2">
                                <?php else: ?>
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-800 truncate"><?php echo s($item['nombre']); ?></h3>
                                <p class="text-sm text-gray-500 mt-1">$<?php echo number_format($item['precio'], 2); ?> c/u</p>
                            </div>
                            <!-- Quantity controls -->
                            <div class="flex items-center border border-gray-300 rounded-lg">
                                <button onclick="actualizarCantidad(<?php echo $item['id']; ?>, parseInt(document.getElementById('cant-<?php echo $item['id']; ?>').textContent) - 1)" class="px-3 py-2 text-gray-600 hover:bg-gray-100 transition-colors">-</button>
                                <span class="px-4 py-2 text-sm font-medium border-x border-gray-300 min-w-[3rem] text-center" id="cant-<?php echo $item['id']; ?>"><?php echo $item['cantidad']; ?></span>
                                <button onclick="actualizarCantidad(<?php echo $item['id']; ?>, parseInt(document.getElementById('cant-<?php echo $item['id']; ?>').textContent) + 1)" class="px-3 py-2 text-gray-600 hover:bg-gray-100 transition-colors">+</button>
                            </div>
                            <!-- Subtotal -->
                            <div class="text-right min-w-[100px]">
                                <p class="font-semibold text-gray-800" id="subtotal-<?php echo $item['id']; ?>">$<?php echo number_format($item['subtotal'], 2); ?></p>
                            </div>
                            <!-- Remove -->
                            <button onclick="eliminarItem(<?php echo $item['id']; ?>)" class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Summary -->
            <div class="mt-6 bg-white rounded-2xl shadow-md p-6">
                <div class="flex justify-between text-lg">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-semibold" id="subtotalText">$<?php echo number_format($total, 2); ?></span>
                </div>
                <div class="flex justify-between text-lg mt-2">
                    <span class="text-gray-600">IVA (13%)</span>
                    <span class="font-semibold" id="ivaText">$<?php echo number_format($total * 0.13, 2); ?></span>
                </div>
                <div class="flex justify-between text-xl font-bold mt-3 pt-3 border-t border-gray-200">
                    <span>Total</span>
                    <span class="text-emerald-600" id="totalText">$<?php echo number_format($total * 1.13, 2); ?></span>
                </div>
                <div class="flex gap-3 mt-6">
                    <button onclick="limpiarCarrito()" class="btn-danger flex-1 text-center py-3 text-base font-semibold">
                        <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Limpiar Carrito
                    </button>
                    <a href="<?php echo URLROOT; ?>/catalogo/checkout" class="btn-primary flex-1 text-center py-3 text-base font-semibold block">
                        Proceder al Pago
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-gray-400 py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-sm">&copy; <?php echo date('Y'); ?> <?php echo SITENAME; ?>. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';

        function actualizarCantidad(id, cantidad) {
            if (cantidad <= 0) {
                eliminarItem(id);
                return;
            }

            // Optimistic update: actualizar la UI inmediatamente
            const cantEl = document.getElementById('cant-' + id);
            if (cantEl) cantEl.textContent = cantidad;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('cantidad', cantidad);
            formData.append('csrf_token', csrfToken);

            fetch(URLROOT + '/catalogo/actualizar-carrito', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Actualizar subtotal del item
                    const subtotalEl = document.getElementById('subtotal-' + id);
                    if (subtotalEl && data.subtotal_item) {
                        subtotalEl.textContent = '$' + parseFloat(data.subtotal_item).toFixed(2);
                    }

                    // Actualizar totales del carrito
                    if (data.subtotal) document.getElementById('subtotalText').textContent = '$' + parseFloat(data.subtotal).toFixed(2);
                    if (data.iva) document.getElementById('ivaText').textContent = '$' + parseFloat(data.iva).toFixed(2);
                    if (data.total) document.getElementById('totalText').textContent = '$' + parseFloat(data.total).toFixed(2);

                    // Actualizar badge del carrito en navbar si existe
                    const badge = document.getElementById('cart-count');
                    if (badge && data.total_items !== undefined) badge.textContent = data.total_items;
                } else {
                    // Revertir el optimistic update si falló
                    if (cantEl) cantEl.textContent = cantidad - 1;
                }
            })
            .catch(() => {
                // Revertir el optimistic update si hay error de red
                if (cantEl) cantEl.textContent = cantidad - 1;
            });
        }

        function eliminarItem(id) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('csrf_token', csrfToken);

            fetch(URLROOT + '/catalogo/eliminar-carrito', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Eliminar la fila del item con animación
                    const row = document.getElementById('item-' + id);
                    if (row) {
                        row.style.transition = 'opacity 0.3s, transform 0.3s';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(20px)';
                        setTimeout(() => row.remove(), 300);
                    }

                    // Actualizar totales
                    if (data.subtotal) document.getElementById('subtotalText').textContent = '$' + parseFloat(data.subtotal).toFixed(2);
                    if (data.iva) document.getElementById('ivaText').textContent = '$' + parseFloat(data.iva).toFixed(2);
                    if (data.total) document.getElementById('totalText').textContent = '$' + parseFloat(data.total).toFixed(2);

                    // Actualizar badge
                    const badge = document.getElementById('cart-count');
                    if (badge && data.total_items !== undefined) badge.textContent = data.total_items;

                    // Si el carrito quedó vacío, mostrar estado vacío dinámicamente
                    if (data.total_items === 0) {
                        mostrarCarritoVacio();
                    }

                    Toastify({ text: 'Producto eliminado', duration: 1500, gravity: 'bottom', position: 'right', style: { background: '#ef4444' } }).showToast();
                }
            })
            .catch(() => {});
        }

        function limpiarCarrito() {
            if (!confirm('¿Estás seguro de vaciar el carrito?')) return;

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);

            fetch(URLROOT + '/catalogo/limpiar-carrito', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    mostrarCarritoVacio();
                    const badge = document.getElementById('cart-count');
                    if (badge) badge.textContent = '0';
                    Toastify({ text: 'Carrito vaciado', duration: 1500, gravity: 'bottom', position: 'right', style: { background: '#ef4444' } }).showToast();
                }
            })
            .catch(() => {});
        }

        function mostrarCarritoVacio() {
            const container = document.querySelector('.max-w-4xl.mx-auto');
            if (!container) return;

            // Reemplazar todo el contenido del contenedor con el estado vacío
            container.innerHTML = `
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Carrito de Compras</h1>
                <div class="text-center py-20">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                    <h2 class="text-xl font-semibold text-gray-500 mb-2">Tu carrito está vacío</h2>
                    <p class="text-gray-400 mb-6">Agrega productos desde nuestro catálogo.</p>
                    <a href="${URLROOT}/catalogo" class="btn-primary inline-block text-base px-8 py-3">Ver Catálogo</a>
                </div>
            `;
        }
    </script>
</body>
</html>