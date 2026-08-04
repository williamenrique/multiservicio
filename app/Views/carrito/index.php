<!-- app/views/carrito/index.php -->
<?php $title = 'Carrito de Compras'; ?>

<div class="cart-main">
    <h1><i class="fas fa-shopping-cart"></i> Tu Carrito</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($erroresStock)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo implode('<br>', $erroresStock); ?>
        </div>
    <?php endif; ?>

    <div class="cart-content">
        <div class="cart-items">
            <?php if (empty($items)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart fa-4x"></i>
                    <h3>Tu carrito está vacío</h3>
                    <p>Explora nuestro catálogo y encuentra lo que necesitas</p>
                    <a href="<?php echo url(''); ?>" class="btn-primary">Ver catálogo</a>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <div class="cart-item" data-id="<?php echo $item['id']; ?>">
                        <div class="item-image">
                            <?php if (!empty($item['imagen']) && file_exists(__DIR__ . '/../../public/uploads/' . $item['imagen'])): ?>
                                <img src="/catalogo_repuestos_mvc/uploads/<?php echo $item['imagen']; ?>" 
                                     alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                            <?php else: ?>
                                <div class="image-placeholder small">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="item-details">
                            <span class="item-code"><?php echo htmlspecialchars($item['codigo']); ?></span>
                            <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                            <span class="item-marca"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['marca']); ?></span>
                            <span class="item-price">$<?php echo number_format($item['precio'], 2); ?></span>
                            <span id="stock-<?php echo $item['id']; ?>" data-stock="<?php echo $item['stock']; ?>" class="stock-info">Stock: <?php echo $item['stock']; ?> uds.</span>
                        </div>
                        <div class="item-actions">
                            <div class="quantity-control">
                                <button class="qty-btn minus" data-id="<?php echo $item['id']; ?>">−</button>
                                <input type="number" class="qty-input" value="<?php echo $item['cantidad']; ?>" 
                                       min="1" max="99" data-id="<?php echo $item['id']; ?>">
                                <button class="qty-btn plus" data-id="<?php echo $item['id']; ?>">+</button>
                            </div>
                            <button class="remove-btn" data-id="<?php echo $item['id']; ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <span class="item-subtotal">
                                $<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($items)): ?>
            <div class="cart-summary">
                <h3>Resumen del pedido</h3>
                <div class="summary-items">
                    <span>Productos:</span>
                    <span><?php echo $count; ?> artículos</span>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span class="total-amount">$<?php echo number_format($total, 2); ?></span>
                </div>
                
                <form method="POST" action="<?php echo url('pedido/finalizar'); ?>" class="checkout-form" id="checkoutForm">
                    <div class="form-group">
                        <label for="nombre">Nombre completo *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               placeholder="Ej: Juan Pérez" value="<?php echo $_SESSION['cliente_nombre'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="cedula">Cédula *</label>
                        <input type="text" id="cedula" name="cedula" required 
                               placeholder="Ej: 12345678" value="<?php echo $_SESSION['cliente_cedula'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="correo">Correo electrónico *</label>
                        <input type="email" id="correo" name="correo" required 
                               placeholder="ejemplo@correo.com" value="<?php echo $_SESSION['cliente_correo'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono *</label>
                        <input type="tel" id="telefono" name="telefono" required 
                               placeholder="Ej: 0412-1234567" value="<?php echo $_SESSION['cliente_telefono'] ?? ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn-primary btn-large">
                        <i class="fas fa-check"></i> Confirmar pedido
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>