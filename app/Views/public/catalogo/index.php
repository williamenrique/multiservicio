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

<body>

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
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

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
            <a href="<?php echo URLROOT; ?>/catalogo<?php echo $busqueda ? '?busqueda='.urlencode($busqueda) : ''; ?>"
                class="category-pill px-4 py-2 rounded-full border border-gray-300 text-sm font-medium transition <?php echo !$categoriaSeleccionada ? 'active' : 'text-gray-600 hover:border-blue-400'; ?>">
                Todas
            </a>
            <?php foreach ($categorias as $cat): ?>
            <a href="<?php echo URLROOT; ?>/catalogo?categoria=<?php echo urlencode($cat->categoria); ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?>"
                class="category-pill px-4 py-2 rounded-full border text-sm font-medium transition <?php echo $categoriaSeleccionada === $cat->categoria ? 'active' : 'border-gray-300 text-gray-600 hover:border-blue-400'; ?>">
                <?php echo s($cat->categoria); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

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
                    class="block h-48 bg-gray-100 overflow-hidden">
                    <?php if (!empty($repuesto->imagen) && file_exists(APPROOT . '/../public_html/' . $repuesto->imagen)): ?>
                    <img src="<?php echo URLROOT . '/' . s($repuesto->imagen); ?>"
                        alt="<?php echo s($repuesto->nombre); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                        <i data-lucide="package" class="w-16 h-16"></i>
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
                    <p class="text-xs text-gray-400 mt-1">ID: <?php echo $repuesto->id; ?></p>

                    <div class="mt-auto pt-3 flex items-center justify-between">
                        <span
                            class="text-lg font-bold text-gray-900">$<?php echo number_format($repuesto->precio, 2); ?></span>
                        <?php if ($repuesto->stock > 0): ?>
                        <button onclick="agregarCarrito(<?php echo $repuesto->id; ?>)"
                            class="px-3 py-2 bg-blue-700 text-white text-sm font-medium rounded-lg hover:bg-blue-800 transition flex items-center gap-1">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">Agregar</span>
                        </button>
                        <?php else: ?>
                        <span class="text-xs text-red-500 font-medium">Sin stock</span>
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
            <a href="<?php echo URLROOT; ?>/catalogo?pagina=<?php echo $paginaActual - 1; ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?><?php echo $categoriaSeleccionada ? '&categoria='.urlencode($categoriaSeleccionada) : ''; ?>"
                class="pagination-btn px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:bg-blue-50 transition">
                <i data-lucide="chevron-left" class="w-4 h-4 inline"></i> Anterior
            </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="<?php echo URLROOT; ?>/catalogo?pagina=<?php echo $i; ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?><?php echo $categoriaSeleccionada ? '&categoria='.urlencode($categoriaSeleccionada) : ''; ?>"
                class="pagination-btn w-10 h-10 flex items-center justify-center border rounded-lg text-sm font-medium transition <?php echo $i === $paginaActual ? 'bg-blue-700 text-white border-blue-700' : 'border-gray-300 text-gray-600 hover:bg-blue-50'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>

            <?php if ($paginaActual < $totalPaginas): ?>
            <a href="<?php echo URLROOT; ?>/catalogo?pagina=<?php echo $paginaActual + 1; ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?><?php echo $categoriaSeleccionada ? '&categoria='.urlencode($categoriaSeleccionada) : ''; ?>"
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