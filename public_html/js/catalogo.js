// public/js/script.js
$(document).ready(function() {
    // ===== AGREGAR AL CARRITO =====
    $('.add-to-cart-btn').click(function(e) {
        e.preventDefault();
        const btn = $(this);
        const id = btn.data('id');
        
        // Evitar múltiples clics
        if (btn.hasClass('loading')) return;
        btn.addClass('loading');
        
        $.ajax({
            url: '/catalogo_repuestos_mvc/carrito/agregar',
            method: 'POST',
            data: { id: id, cantidad: 1 },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Animación del botón
                    const originalHtml = btn.html();
                    btn.html('<i class="fas fa-check"></i> ¡Agregado!');
                    btn.addClass('success');
                    
                    // Actualizar badge
                    updateCartBadge();
                    
                    // Mostrar notificación
                    showNotification('¡Producto agregado al carrito!', 'success');
                    
                    setTimeout(() => {
                        btn.html(originalHtml);
                        btn.removeClass('success loading');
                    }, 2000);
                } else {
                    showNotification('Error al agregar el producto', 'error');
                    btn.removeClass('loading');
                }
            },
            error: function() {
                showNotification('Error de conexión', 'error');
                btn.removeClass('loading');
            }
        });
    });
    
    // ===== ACTUALIZAR CANTIDAD =====
    $('.qty-btn.plus').click(function() {
        const id = $(this).data('id');
        const input = $(this).closest('.quantity-control').find('.qty-input');
        let cantidad = parseInt(input.val()) + 1;
        if (cantidad > 99) cantidad = 99;
        input.val(cantidad);
        updateCartItem(id, cantidad);
    });
    
    $('.qty-btn.minus').click(function() {
        const id = $(this).data('id');
        const input = $(this).closest('.quantity-control').find('.qty-input');
        let cantidad = parseInt(input.val()) - 1;
        if (cantidad < 1) cantidad = 1;
        input.val(cantidad);
        updateCartItem(id, cantidad);
    });
    
    let timeoutId;
    $('.qty-input').on('input', function() {
        clearTimeout(timeoutId);
        const id = $(this).data('id');
        const input = $(this);
        
        timeoutId = setTimeout(function() {
            let cantidad = parseInt(input.val());
            if (isNaN(cantidad) || cantidad < 1) cantidad = 1;
            if (cantidad > 99) cantidad = 99;
            input.val(cantidad);
            updateCartItem(id, cantidad);
        }, 500);
    });
    
    // ===== ELIMINAR ITEM =====
    $('.remove-btn').click(function() {
        const id = $(this).data('id');
        const item = $(this).closest('.cart-item');
        
        if (confirm('¿Seguro que deseas eliminar este producto del carrito?')) {
            $.ajax({
                url: '/catalogo_repuestos_mvc/carrito/eliminar',
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        item.fadeOut(400, function() {
                            $(this).remove();
                            updateCartBadge();
                            // Actualizar total
                            updateTotal(response.total);
                            // Si no hay items, recargar para mostrar vacío
                            if ($('.cart-item').length === 0) {
                                location.reload();
                            }
                        });
                        showNotification('Producto eliminado', 'info');
                    }
                }
            });
        }
    });
    
    // ===== FUNCIONES AUXILIARES =====
    function updateCartItem(id, cantidad) {
        $.ajax({
            url: '/catalogo_repuestos_mvc/carrito/actualizar',
            method: 'POST',
            data: { id: id, cantidad: cantidad },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateCartBadge();
                    updateTotal(response.total);
                    // Actualizar subtotal del item
                    const item = $(`.cart-item[data-id="${id}"]`);
                    const precio = parseFloat(item.find('.item-price').text().replace('$', '').replace(',', ''));
                    const subtotal = precio * cantidad;
                    item.find('.item-subtotal').text('$' + subtotal.toFixed(2).replace('.', ','));
                }
            }
        });
    }
    
    function updateCartBadge() {
        $.ajax({
            url: '/catalogo_repuestos_mvc/carrito/contar',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                $('.cart-badge').text(response.total || 0);
            }
        });
    }
    
    function updateTotal(total) {
        $('.total-amount').text('$' + total);
    }
    
    // ===== NOTIFICACIONES =====
    function showNotification(message, type) {
        const types = {
            success: { icon: 'fa-check-circle', color: '#4caf50' },
            error: { icon: 'fa-exclamation-circle', color: '#f44336' },
            info: { icon: 'fa-info-circle', color: '#2196f3' },
            warning: { icon: 'fa-exclamation-triangle', color: '#ff9800' }
        };
        
        const config = types[type] || types.info;
        
        const notification = $(`
            <div class="notification ${type}">
                <i class="fas ${config.icon}"></i>
                <span>${message}</span>
                <button class="notification-close"><i class="fas fa-times"></i></button>
            </div>
        `);
        
        notification.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 25px',
            borderRadius: '12px',
            background: config.color,
            color: 'white',
            boxShadow: '0 4px 20px rgba(0,0,0,0.2)',
            zIndex: '9999',
            display: 'flex',
            alignItems: 'center',
            gap: '12px',
            fontFamily: "'Inter', sans-serif",
            animation: 'slideIn 0.5s ease',
            maxWidth: '400px'
        });
        
        // Botón cerrar
        notification.find('.notification-close').click(function() {
            notification.fadeOut(400, function() {
                $(this).remove();
            });
        });
        
        $('body').append(notification);
        
        // Auto-cerrar después de 4 segundos
        setTimeout(() => {
            notification.fadeOut(400, function() {
                $(this).remove();
            });
        }, 4000);
    }
    
    // ===== VALIDACIÓN DEL FORMULARIO =====
    $('#checkoutForm').submit(function(e) {
        const nombre = $('#nombre').val().trim();
        const cedula = $('#cedula').val().trim();
        const correo = $('#correo').val().trim();
        const telefono = $('#telefono').val().trim();
        
        let errors = [];
        
        if (!nombre) errors.push('Nombre completo');
        if (!cedula) errors.push('Cédula');
        if (!correo) errors.push('Correo electrónico');
        if (!telefono) errors.push('Teléfono');
        
        if (errors.length > 0) {
            e.preventDefault();
            showNotification('Por favor, completa: ' + errors.join(', '), 'error');
            return false;
        }
        
        if (!correo.includes('@') || !correo.includes('.')) {
            e.preventDefault();
            showNotification('Ingresa un correo electrónico válido', 'error');
            return false;
        }
        
        if (cedula.length < 5) {
            e.preventDefault();
            showNotification('La cédula debe tener al menos 5 dígitos', 'error');
            return false;
        }
        
        return true;
    });
    
    // ===== INICIALIZACIÓN =====
    // Actualizar badge al cargar
    updateCartBadge();
    
    // Guardar datos del cliente en sesión al cambiar campos
    $('#nombre, #cedula, #correo, #telefono').change(function() {
        // Los datos se guardan automáticamente al enviar el formulario
    });
});

// ===== ESTILOS PARA NOTIFICACIONES =====
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .notification {
        animation: slideIn 0.5s ease !important;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        font-size: 16px;
        padding: 0 5px;
        opacity: 0.7;
        transition: opacity 0.3s ease;
    }
    
    .notification-close:hover {
        opacity: 1;
    }
    
    .add-to-cart-btn.loading {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .add-to-cart-btn.success {
        background: #4caf50 !important;
    }
    
    .add-to-cart-btn.disabled {
        background: #ccc !important;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .stock-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .stock-badge.in-stock {
        background: #4caf50;
        color: white;
    }
    
    .stock-badge.out-of-stock {
        background: #f44336;
        color: white;
    }
    
    .categories-filter {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        margin: 20px 0;
    }
    
    .category-link {
        padding: 8px 20px;
        border-radius: 50px;
        background: #f5f5f5;
        color: #333;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    
    .category-link:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }
    
    .category-link.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
`;
document.head.appendChild(style);