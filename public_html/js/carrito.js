/**
 * carrito.js - Funcionalidad del carrito de compras
 * Dependencias: Toastify-js, URLROOT global, csrfToken global
 */

function actualizarCantidad(id, cantidad) {
    if (cantidad <= 0) {
        eliminarItem(id);
        return;
    }

    // Validar contra el stock disponible
    const stockEl = document.getElementById('stock-' + id);
    const maxStock = stockEl ? parseInt(stockEl.dataset.stock) : 999;

    if (cantidad > maxStock) {
        Toastify({
            text: '⚠ Solo hay ' + maxStock + ' unidades disponibles en stock',
            duration: 2500,
            gravity: 'bottom',
            position: 'right',
            style: {
                background: '#f59e0b',
                borderRadius: '12px',
                padding: '12px 20px'
            }
        }).showToast();
        // Revertir el input al stock máximo y actualizar estado de botones
        const inputEl = document.querySelector('.qty-input[data-id="' + id + '"]');
        if (inputEl) inputEl.value = maxStock;
        actualizarEstadoBotonesItem(id, maxStock);
        return;
    }

    // Optimistic update: actualizar la UI inmediatamente
    const cantEl = document.getElementById('cant-' + id);
    if (cantEl) cantEl.textContent = cantidad;

    // Actualizar estado de botones según la nueva cantidad
    actualizarEstadoBotonesItem(id, cantidad);

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
                if (data.mensaje) {
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
            }
        })
        .catch(() => {
            // Revertir el optimistic update si hay error de red
            if (cantEl) cantEl.textContent = cantidad - 1;
        });
}

/**
 * Habilita/deshabilita los botones +/- de un item según su stock
 */
function actualizarEstadoBotonesItem(id, cantidad) {
    const stockEl = document.getElementById('stock-' + id);
    const maxStock = stockEl ? parseInt(stockEl.dataset.stock) : 999;
    const btnMenos = document.querySelector('.qty-btn.minus[data-id="' + id + '"]');
    const btnMas = document.querySelector('.qty-btn.plus[data-id="' + id + '"]');

    if (btnMenos) {
        btnMenos.disabled = (cantidad <= 1);
        btnMenos.style.opacity = cantidad <= 1 ? '0.4' : '1';
        btnMenos.style.cursor = cantidad <= 1 ? 'not-allowed' : 'pointer';
    }
    if (btnMas) {
        btnMas.disabled = (cantidad >= maxStock);
        btnMas.style.opacity = cantidad >= maxStock ? '0.4' : '1';
        btnMas.style.cursor = cantidad >= maxStock ? 'not-allowed' : 'pointer';
    }
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
        .catch(() => { });
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
        .catch(() => { });
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

// Event listeners para los botones +/- y eliminar
document.addEventListener('DOMContentLoaded', function () {
    // Botones de cantidad: menos (-)
    document.querySelectorAll('.qty-btn.minus').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const input = document.querySelector('.qty-input[data-id="' + id + '"]');
            if (input) {
                const nuevaCantidad = parseInt(input.value) - 1;
                if (nuevaCantidad >= 1) {
                    input.value = nuevaCantidad;
                    actualizarCantidad(id, nuevaCantidad);
                }
            }
        });
    });

    // Botones de cantidad: más (+)
    document.querySelectorAll('.qty-btn.plus').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const input = document.querySelector('.qty-input[data-id="' + id + '"]');
            if (!input) return;

            const stockEl = document.getElementById('stock-' + id);
            const maxStock = stockEl ? parseInt(stockEl.dataset.stock) : 999;
            const nuevaCantidad = parseInt(input.value) + 1;

            // No permitir exceder el stock
            if (nuevaCantidad > maxStock) {
                Toastify({
                    text: '⚠ Solo hay ' + maxStock + ' unidades disponibles en stock',
                    duration: 2500,
                    gravity: 'bottom',
                    position: 'right',
                    style: {
                        background: '#f59e0b',
                        borderRadius: '12px',
                        padding: '12px 20px'
                    }
                }).showToast();
                return;
            }

            input.value = nuevaCantidad;
            actualizarCantidad(id, nuevaCantidad);
        });
    });

    // Inputs de cantidad: cambio manual
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function () {
            const id = this.dataset.id;
            const stockEl = document.getElementById('stock-' + id);
            const maxStock = stockEl ? parseInt(stockEl.dataset.stock) : 999;
            let cantidad = parseInt(this.value);

            if (isNaN(cantidad) || cantidad < 1) {
                cantidad = 1;
                this.value = 1;
            }

            // Limitar al stock máximo
            if (cantidad > maxStock) {
                cantidad = maxStock;
                this.value = maxStock;
                Toastify({
                    text: '⚠ Solo hay ' + maxStock + ' unidades disponibles en stock',
                    duration: 2500,
                    gravity: 'bottom',
                    position: 'right',
                    style: {
                        background: '#f59e0b',
                        borderRadius: '12px',
                        padding: '12px 20px'
                    }
                }).showToast();
            }

            actualizarCantidad(id, cantidad);
        });
    });

    // Inicializar estado de botones al cargar la página
    document.querySelectorAll('.qty-input').forEach(input => {
        const id = input.dataset.id;
        const cantidad = parseInt(input.value) || 1;
        actualizarEstadoBotonesItem(id, cantidad);
    });

    // Botones de eliminar
    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            if (confirm('¿Eliminar este producto del carrito?')) {
                eliminarItem(id);
            }
        });
    });
});