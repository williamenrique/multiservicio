<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo s($titulo ?? 'Catálogo de Repuestos'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    body {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background: #f8fafc;
    }

    .product-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    }

    .cart-badge {
        position: absolute;
        top: -6px;
        right: -8px;
        background: #ef4444;
        color: white;
        font-size: 11px;
        font-weight: 700;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .category-pill.active {
        background: #1e40af;
        color: white;
        border-color: #1e40af;
    }

    .pagination-btn {
        transition: all 0.15s;
    }

    .pagination-btn:hover:not(.disabled) {
        background: #dbeafe;
    }

    .toast-success {
        background: #16a34a !important;
    }

    .toast-error {
        background: #dc2626 !important;
    }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-gray-50">

    <!-- ===== HEADER PÚBLICO ===== -->
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="<?php echo URLROOT; ?>/catalogo"
                    class="flex items-center gap-2 text-blue-900 font-bold text-xl">
                    <i data-lucide="car" class="w-7 h-7"></i>
                    <span>AutoRepuestos</span>
                </a>

                <!-- Acciones -->
                <div class="flex items-center gap-4">
                    <a href="<?php echo URLROOT; ?>/login"
                        class="text-sm text-gray-500 hover:text-blue-700 transition flex items-center gap-1">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">Staff</span>
                    </a>
                    <button onclick="irAlCarrito()"
                        class="relative p-2 text-gray-600 hover:text-blue-700 transition" title="Carrito">
                        <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                        <span id="cart-count-header" class="cart-badge <?php echo ($carrito_count ?? 0) > 0 ? '' : 'hidden'; ?>"><?php echo $carrito_count ?? 0; ?></span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">

        <!-- Título -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Catalogo de Repuestos</h1>
            <p class="text-gray-500 mt-1">Encuentra los repuestos que necesitas para tu vehículo</p>
        </div>

        <!-- Barra de búsqueda -->
        <form method="GET" action="<?php echo URLROOT; ?>/catalogo" class="mb-6">
            <div class="flex gap-3">
                <div class="flex-1 relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input type="text" name="busqueda" value="<?php echo s($busqueda); ?>"
                        placeholder="Buscar por nombre, código, marca..."
                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <button type="submit"
                    class="px-6 py-3 bg-blue-700 text-white font-semibold rounded-lg hover:bg-blue-800 transition flex items-center gap-2">
                    <i data-lucide="search" class="w-5 h-5"></i>
                    <span class="hidden sm:inline">Buscar</span>
                </button>
                <?php if ($busqueda): ?>
                <a href="<?php echo URLROOT; ?>/catalogo"
                    class="px-4 py-3 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition flex items-center">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Filtro de categorías -->
        <?php if (!empty($categorias)): ?>
        <div class="flex flex-wrap gap-2 mb-8">
            <a href="<?php echo URLROOT; ?>/catalogo<?php echo $busqueda ? '?busqueda='.urlencode($busqueda) : ''; ?><?php echo $ofertaSeleccionada ? '&oferta=1' : ''; ?>"
                class="category-pill px-4 py-2 rounded-full border border-gray-300 text-sm font-medium transition <?php echo !$categoriaSeleccionada && !$ofertaSeleccionada ? 'active' : 'text-gray-600 hover:border-blue-400'; ?>">
                Todas
            </a>
            <?php foreach ($categorias as $cat): ?>
            <a href="<?php echo URLROOT; ?>/catalogo?categoria=<?php echo urlencode($cat->categoria); ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?><?php echo $ofertaSeleccionada ? '&oferta=1' : ''; ?>"
                class="category-pill px-4 py-2 rounded-full border text-sm font-medium transition <?php echo $categoriaSeleccionada === $cat->categoria ? 'active' : 'border-gray-300 text-gray-600 hover:border-blue-400'; ?>">
                <?php echo s($cat->categoria); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Botón Flotante de Ofertas -->
        <a href="<?php echo URLROOT; ?>/catalogo<?php echo $busqueda ? '?busqueda='.urlencode($busqueda) : '?'; ?><?php echo $categoriaSeleccionada ? '&categoria='.urlencode($categoriaSeleccionada) : ''; ?><?php echo $ofertaSeleccionada ? '' : '&oferta=1'; ?>"
            id="btnOfertasFlotante"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-2 px-5 py-3 rounded-full shadow-xl transition-all duration-300 
                <?php echo $ofertaSeleccionada 
                    ? 'bg-navy-blue text-[#00ff00] ring-4 ring-navy-blue/20' 
                    : 'bg-[#00ff00] text-navy-blue hover:bg-[#00e600] hover:scale-105'; ?>
                animate-<?php echo $ofertaSeleccionada ? 'pulse' : 'bounce'; ?>">
            <i data-lucide="tag" class="w-5 h-5 flex-shrink-0"></i>
            <span class="font-black text-sm uppercase tracking-wider hidden sm:inline">Ofertas</span>
            <?php if ($ofertaSeleccionada): ?>
            <i data-lucide="check" class="w-4 h-4 flex-shrink-0 animate-pop"></i>
            <?php endif; ?>
        </a>

        <style>
        @keyframes pop {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        .animate-pop { animation: pop 0.3s ease-out; }
        @keyframes bounce-subtle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }
        .animate-bounce { animation: bounce-subtle 2s ease-in-out infinite; }
        @keyframes pulse-subtle {
            0%, 100% { box-shadow: 0 0 0 0 rgba(0, 255, 0, 0.4); }
            50% { box-shadow: 0 0 0 12px rgba(0, 255, 0, 0); }
        }
        .animate-pulse { animation: pulse-subtle 2s ease-in-out infinite; }
        </style>

        <!-- Resultados -->
        <?php if (!empty($repuestos)): ?>
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">
                <span class="font-semibold text-gray-700"><?php echo $total; ?></span> producto(s) encontrado(s)
            </p>
        </div>

        <!-- Grid de productos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($repuestos as $repuesto): ?>
            <div class="product-card bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col">
                <!-- Imagen -->
                <a href="<?php echo URLROOT; ?>/catalogo/detalle/<?php echo $repuesto->id; ?>"
                    class="block h-48 bg-gray-100 overflow-hidden relative">
                    <?php if (!empty($repuesto->imagen) && file_exists(APPROOT . '/../public_html/' . $repuesto->imagen)): ?>
                    <img src="<?php echo URLROOT . '/' . s($repuesto->imagen); ?>"
                        alt="<?php echo s($repuesto->nombre); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                        <i data-lucide="package" class="w-16 h-16"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Badge de Oferta en la imagen -->
                    <?php if (!empty($repuesto->en_oferta_vigente) && $repuesto->en_oferta_vigente): ?>
                    <div class="absolute top-3 left-3 z-10">
                        <span class="bg-amber-500 text-white text-xs font-bold px-2 py-1 rounded-full flex items-center gap-1 animate-pulse shadow-lg">
                            <i data-lucide="tag" class="w-3 h-3"></i>
                            <?php echo (int)$repuesto->oferta_porcentaje; ?>% OFF
                        </span>
                    </div>
                    <?php endif; ?>
                </a>

                <!-- Info -->
                <div class="p-4 flex flex-col flex-1">
                    <h3 class="mt-1 font-semibold text-gray-900 leading-tight">
                        <a href="<?php echo URLROOT; ?>/catalogo/detalle/<?php echo $repuesto->id; ?>"
                            class="hover:text-blue-700 transition">
                            <?php echo s($repuesto->nombre); ?>
                        </a>
                    </h3>
                    <?php if (!empty($repuesto->marca)): ?>
                        <p class="text-xs text-blue-600 font-bold uppercase mt-0.5"><?php echo s($repuesto->marca); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($repuesto->descripcion)): ?>
                        <p class="text-xs text-gray-400 mt-1 line-clamp-2"><?php echo s($repuesto->descripcion); ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-400 mt-1">Código: <?php echo s($repuesto->codigo); ?></p>

                    <div class="mt-auto pt-3 flex items-center justify-between">
                        <div>
                            <?php if (!empty($repuesto->en_oferta_vigente) && $repuesto->en_oferta_vigente): ?>
                            <!-- Precio con oferta -->
                            <div class="flex flex-col items-start">
                                <span class="text-gray-400 line-through text-sm">$<?php echo number_format($repuesto->precio, 2); ?></span>
                                <span class="text-lg font-bold text-amber-600">$<?php echo number_format($repuesto->precio_final, 2); ?></span>
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold uppercase mt-0.5">Oferta <?php echo (int)$repuesto->oferta_porcentaje; ?>% OFF</span>
                            </div>
                            <?php else: ?>
                            <!-- Precio normal -->
                            <span class="text-lg font-bold text-gray-900">$<?php echo number_format($repuesto->precio, 2); ?></span>
                            <?php endif; ?>
                            <p class="text-xs <?php echo $repuesto->stock > 0 ? 'text-emerald-600' : 'text-red-500'; ?> font-medium mt-1">
                                <?php if ($repuesto->stock > 0): ?>
                                    <i data-lucide="package-check" class="w-3 h-3 inline"></i> <?php echo $repuesto->stock; ?> disponible(s)
                                <?php else: ?>
                                    Sin stock
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($repuesto->stock > 0): ?>
                        <button onclick="agregarCarrito(<?php echo $repuesto->id; ?>)"
                            class="px-3 py-2 bg-blue-700 text-white text-sm font-medium rounded-lg hover:bg-blue-800 transition flex items-center gap-1">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">Agregar</span>
                        </button>
                        <?php else: ?>
                        <span class="text-xs text-red-500 font-medium">Agotado</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
        <div class="flex justify-center items-center gap-2 mt-10">
            <?php if ($paginaActual > 1): ?>
            <a href="<?php echo URLROOT; ?>/catalogo?pagina=<?php echo $paginaActual - 1; ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?><?php echo $categoriaSeleccionada ? '&categoria='.urlencode($categoriaSeleccionada) : ''; ?><?php echo $ofertaSeleccionada ? '&oferta=1' : ''; ?>"
                class="pagination-btn px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-blue-50 transition">
                <i data-lucide="chevron-left" class="w-4 h-4 inline"></i> Anterior
            </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="<?php echo URLROOT; ?>/catalogo?pagina=<?php echo $i; ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?><?php echo $categoriaSeleccionada ? '&categoria='.urlencode($categoriaSeleccionada) : ''; ?><?php echo $ofertaSeleccionada ? '&oferta=1' : ''; ?>"
                class="pagination-btn w-10 h-10 flex items-center justify-center border rounded-lg text-sm font-medium transition <?php echo $i === $paginaActual ? 'bg-blue-700 text-white border-blue-700' : 'border-gray-300 text-gray-600 hover:bg-blue-50'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>

            <?php if ($paginaActual < $totalPaginas): ?>
            <a href="<?php echo URLROOT; ?>/catalogo?pagina=<?php echo $paginaActual + 1; ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?><?php echo $categoriaSeleccionada ? '&categoria='.urlencode($categoriaSeleccionada) : ''; ?><?php echo $ofertaSeleccionada ? '&oferta=1' : ''; ?>"
                class="pagination-btn px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-blue-50 transition">
                Siguiente <i data-lucide="chevron-right" class="w-4 h-4 inline"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Sin resultados -->
        <div class="text-center py-20">
            <i data-lucide="package-x" class="w-20 h-20 mx-auto text-gray-300 mb-4"></i>
            <h2 class="text-2xl font-semibold text-gray-600">No se encontraron productos</h2>
            <p class="text-gray-400 mt-2">Intenta con otros términos de búsqueda o categoría.</p>
            <a href="<?php echo URLROOT; ?>/catalogo"
                class="inline-block mt-6 px-6 py-3 bg-blue-700 text-white font-medium rounded-lg hover:bg-blue-800 transition">
                Ver todos los productos
            </a>
        </div>
        <?php endif; ?>

    </main>

    <!-- ===== FOOTER PÚBLICO ===== -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-500">&copy; <?php echo date('Y'); ?> AutoRepuestos - Todos los derechos
                    reservados</p>
                <div class="flex items-center gap-4 text-sm text-gray-400">
                    <a href="<?php echo URLROOT; ?>/catalogo" class="hover:text-blue-600 transition">Inicio</a>
                    <a href="<?php echo URLROOT; ?>/catalogo/carrito" class="hover:text-blue-600 transition">Carrito</a>
                </div>
            </div>
        </div>
    </footer>

    <script>const URLROOT = "<?php echo URLROOT; ?>";</script>
    <script>const csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';</script>
    <script src="<?php echo URLROOT; ?>/js/catalogo-publico.js"></script>
</body>

</html>