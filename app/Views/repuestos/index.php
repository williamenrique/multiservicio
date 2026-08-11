<!-- app/views/repuestos/index.php -->
<?php $title = 'Catálogo de Repuestos'; ?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1>Encuentra el repuesto perfecto</h1>
        <p>Calidad y confianza para tu vehículo</p>
    </div>
</section>

<!-- Barra de búsqueda y filtros -->
<div class="search-section">
    <div class="search-container">
        <form method="GET" action="<?php echo url(''); ?>" class="search-form">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" 
                       name="busqueda" 
                       placeholder="Buscar por nombre, código o marca..." 
                       value="<?php echo htmlspecialchars($busqueda ?? ''); ?>"
                       class="search-input">
                <button type="submit" class="search-btn">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Filtros por categoría -->
    <?php if (!empty($categorias)): ?>
    <div class="categories-filter">
        <a href="<?php echo url(''); ?>" class="category-link <?php echo empty($categoria_id) ? 'active' : ''; ?>">
            Todos
        </a>
        <?php foreach ($categorias as $categoria): ?>
            <a href="<?php echo url('?categoria=' . $categoria['id']); ?>" 
               class="category-link <?php echo ($categoria_id == $categoria['id']) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($categoria['nombre']); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Grid de productos -->
<div class="catalog-section">
    <div class="catalog-header">
        <h2><i class="fas fa-th-large"></i> Catálogo de Repuestos</h2>
        <span class="count"><?php echo count($repuestos); ?> productos</span>
    </div>

    <div class="products-grid">
        <?php if (count($repuestos) > 0): ?>
            <?php foreach ($repuestos as $repuesto): ?>
                <div class="product-card" data-id="<?php echo $repuesto['id']; ?>">
                    <div class="product-image">
                        <?php if (!empty($repuesto['imagen']) && file_exists(__DIR__ . '/../../public/uploads/' . $repuesto['imagen'])): ?>
                            <img src="/catalogo_repuestos_mvc/uploads/<?php echo $repuesto['imagen']; ?>" 
                                 alt="<?php echo htmlspecialchars($repuesto['nombre']); ?>">
                        <?php else: ?>
                            <div class="image-placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                        <?php if ($repuesto['stock'] > 0): ?>
                            <span class="stock-badge in-stock">En stock</span>
                        <?php else: ?>
                            <span class="stock-badge out-of-stock">Sin stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <span class="product-code"><?php echo htmlspecialchars($repuesto['codigo']); ?></span>
                        <h3><?php echo htmlspecialchars($repuesto['nombre']); ?></h3>
                        <span class="product-brand">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($repuesto['marca']); ?>
                        </span>
                        <?php if (isset($repuesto['categoria_nombre'])): ?>
                            <span class="product-category">
                                <i class="fas fa-folder"></i> <?php echo htmlspecialchars($repuesto['categoria_nombre']); ?>
                            </span>
                        <?php endif; ?>
                        <p class="product-description">
                            <?php echo htmlspecialchars(substr($repuesto['descripcion'] ?? '', 0, 80)) . '...'; ?>
                        </p>
                        <div class="product-footer">
                            <span class="product-price">$<?php echo number_format($repuesto['precio'], 2); ?></span>
                            <?php if ($repuesto['stock'] > 0): ?>
                                <button class="add-to-cart-btn" data-id="<?php echo $repuesto['id']; ?>">
                                    <i class="fas fa-plus"></i> Agregar
                                </button>
                            <?php else: ?>
                                <button class="add-to-cart-btn disabled" disabled>
                                    <i class="fas fa-times"></i> Agotado
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search fa-3x"></i>
                <h3>No se encontraron repuestos</h3>
                <p>Intenta con otros términos de búsqueda</p>
                <a href="<?php echo url(''); ?>" class="btn-primary">Ver todos los productos</a>
            </div>
        <?php endif; ?>
    </div>
</div>