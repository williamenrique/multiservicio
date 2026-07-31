<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo s($titulo); ?> - <?php echo SITENAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script>
    const URLROOT = "<?php echo URLROOT; ?>";
    </script>
    <style>
    body {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .nav-blur {
        backdrop-filter: blur(12px);
        background: rgba(15, 23, 42, 0.9);
    }

    .btn-primary {
        background: #10b981;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }

    .btn-primary:hover {
        background: #059669;
        transform: scale(1.02);
    }

    .btn-outline {
        border: 2px solid #10b981;
        color: #10b981;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }

    .btn-outline:hover {
        background: #10b981;
        color: white;
    }

    .stock-badge {
        font-size: 0.7rem;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        font-weight: 600;
    }
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-white">CATALOGO</span>
                </a>
                <div class="flex items-center gap-4">
                    <a href="<?php echo URLROOT; ?>/catalogo/carrito"
                        class="relative p-2 text-gray-300 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                        <span id="cartCount"
                            class="absolute -top-1 -right-1 bg-emerald-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center hidden">0</span>
                    </a>
                    <a href="<?php echo URLROOT; ?>/catalogo"
                        class="text-sm text-gray-400 hover:text-white transition-colors">&larr; Volver</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- PRODUCT DETAIL -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-2xl shadow-md overflow-hidden">
            <div class="grid md:grid-cols-2 gap-0">
                <!-- Image -->
                <div class="bg-gray-100 p-8 flex items-center justify-center min-h-[300px]">
                    <?php if ($repuesto->imagen && file_exists(APPROOT . '/../public_html/' . $repuesto->imagen)): ?>
                    <img src="<?php echo URLROOT . '/' . $repuesto->imagen; ?>"
                        alt="<?php echo s($repuesto->nombre); ?>" class="max-w-full max-h-80 object-contain">
                    <?php else: ?>
                    <svg class="w-32 h-32 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <?php endif; ?>
                </div>
                <!-- Info -->
                <div class="p-6 md:p-8 flex flex-col">
                    <?php if ($repuesto->codigo): ?>
                    <span class="text-sm text-gray-400 font-mono">Código: <?php echo s($repuesto->codigo); ?></span>
                    <?php endif; ?>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mt-2"><?php echo s($repuesto->nombre); ?>
                    </h1>
                    <?php if ($repuesto->marca): ?>
                    <p class="text-gray-500 mt-1">Marca: <span
                            class="font-medium"><?php echo s($repuesto->marca); ?></span></p>
                    <?php endif; ?>
                    <p class="text-sm text-gray-400 mt-1">Categoría: <?php echo s($repuesto->categoria); ?></p>

                    <?php if ($repuesto->descripcion): ?>
                    <p class="text-gray-600 mt-4 leading-relaxed"><?php echo nl2br(s($repuesto->descripcion)); ?></p>
                    <?php endif; ?>

                    <div class="mt-6 flex items-center gap-3">
                        <?php if ($repuesto->stock > 0): ?>
                        <span class="stock-badge bg-emerald-100 text-emerald-700 text-sm px-3 py-1">
                            <?php echo $repuesto->stock > 10 ? 'En stock (' . $repuesto->stock . ' uds.)' : 'Últimas ' . $repuesto->stock . ' unidades'; ?>
                        </span>
                        <?php else: ?>
                        <span class="stock-badge bg-red-100 text-red-600 text-sm px-3 py-1">Agotado</span>
                        <?php endif; ?>
                    </div>

                    <div class="mt-auto pt-6 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <span
                                class="text-3xl font-bold text-emerald-600">$<?php echo number_format($repuesto->precio, 2); ?></span>
                        </div>
                        <?php if ($repuesto->stock > 0): ?>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center border border-gray-300 rounded-lg">
                                <button onclick="cambiarCantidad(-1)"
                                    class="px-3 py-2 text-gray-600 hover:bg-gray-100 transition-colors">-</button>
                                <input type="number" id="cantidad" value="1" min="1"
                                    max="<?php echo $repuesto->stock; ?>"
                                    class="w-16 text-center border-x border-gray-300 py-2 text-sm focus:outline-none"
                                    readonly>
                                <button onclick="cambiarCantidad(1)"
                                    class="px-3 py-2 text-gray-600 hover:bg-gray-100 transition-colors">+</button>
                            </div>
                            <button onclick="agregarCarrito(<?php echo $repuesto->id; ?>)"
                                class="btn-primary flex-1 text-center py-3 text-base font-semibold flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                                </svg>
                                Agregar al Carrito
                            </button>
                        </div>
                        <?php else: ?>
                        <button disabled
                            class="w-full bg-gray-200 text-gray-400 py-3 rounded-lg text-base cursor-not-allowed">Producto
                            agotado</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-gray-400 py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-sm">&copy; <?php echo date('Y'); ?> <?php echo SITENAME; ?>. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
    let cantidad = 1;
    const maxStock = <?php echo $repuesto->stock; ?>;

    function cambiarCantidad(delta) {
        cantidad = Math.max(1, Math.min(maxStock, cantidad + delta));
        document.getElementById('cantidad').value = cantidad;
    }

    function agregarCarrito(id) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('cantidad', cantidad);

        fetch(URLROOT + '/catalogo/agregar-carrito', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Toastify({
                        text: '✓ ' + data.mensaje,
                        duration: 3000,
                        gravity: 'bottom',
                        position: 'right',
                        style: {
                            background: '#10b981',
                            borderRadius: '12px',
                            padding: '12px 20px'
                        }
                    }).showToast();
                    const badge = document.getElementById('cartCount');
                    badge.textContent = data.total_items;
                    badge.classList.remove('hidden');
                } else {
                    Toastify({
                        text: '✗ ' + data.mensaje,
                        duration: 3000,
                        gravity: 'bottom',
                        position: 'right',
                        style: {
                            background: '#ef4444',
                            borderRadius: '12px',
                            padding: '12px 20px'
                        }
                    }).showToast();
                }
            })
            .catch(() => {
                Toastify({
                    text: '✗ Error al conectar',
                    duration: 3000,
                    gravity: 'bottom',
                    position: 'right',
                    style: {
                        background: '#ef4444',
                        borderRadius: '12px',
                        padding: '12px 20px'
                    }
                }).showToast();
            });
    }

    // Cargar conteo del carrito
    document.addEventListener('DOMContentLoaded', function() {
        fetch(URLROOT + '/catalogo/contar-carrito')
            .then(r => r.json())
            .then(data => {
                const badge = document.getElementById('cartCount');
                if (data.total_items > 0) {
                    badge.textContent = data.total_items;
                    badge.classList.remove('hidden');
                }
            })
            .catch(() => {});
    });
    </script>
</body>

</html>