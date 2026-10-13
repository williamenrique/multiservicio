/**
 * carrito.js - Funcionalidad del carrito de compras (catálogo público)
 * 
 * v2.0 (2026-10-09) — P2-04:
 *   • Se eliminaron las llamadas inline a Toastify. Ahora se usa
 *     AppUtils.showToast(msg, type, 'light') — variante blanca del catálogo público.
 * 
 * v2.1 (2026-10-09) — P3-10:
 *   • Se añade el header `X-CSRF-TOKEN` a todos los fetch POST, además del
 *     `csrf_token` en FormData (que ya se enviaba). Esto alinea el catálogo
 *     público con el resto del sistema (que usa el header) sin romper la
 *     compatibilidad con el backend actual.
 * 
 * Dependencias: AppUtils (utils.js), URLROOT global, csrfToken global
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
        AppUtils.showToast('Solo hay ' + maxStock + ' unidades disponibles en stock', 'warning', 'light');
        const inputEl = document.querySelector('.qty-input[data-id="' + id + '"]');
        if (inputEl) inputEl.value = maxStock;
        actualizarEstadoBotonesItem(id, maxStock);
        return;
    }

    // Optimistic update: actualizar la UI inmediatamente
    const cantEl = document.getElementById('cant-' + id);
    if (cantEl) cantEl.textContent = cantidad;

    actualizarEstadoBotonesItem(id, cantidad);

    const formData = new FormData();
    formData.append('id', id);
    formData.append('cantidad', cantidad);
    formData.append('csrf_token', csrfToken);

    fetch(URLROOT + '/catalogo/actualizar-carrito', {
        method: 'POST',
        headers: {
            // FIX P3-10: header CSRF (además del FormData)
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const subtotalEl = document.getElementById('subtotal-' + id);
                if (subtotalEl && data.subtotal_item) {
                    subtotalEl.textContent = '$' + parseFloat(data.subtotal_item).toFixed(2);
                }

                if (data.subtotal) document.getElementById('subtotalText').textContent = '$' + parseFloat(data.subtotal).toFixed(2);
                if (data.iva) document.getElementById('ivaText').textContent = '$' + parseFloat(data.iva).toFixed(2);
                if (data.total) document.getElementById('totalText').textContent = '$' + parseFloat(data.total).toFixed(2);

                const badge = document.getElementById('cart-count');
                if (badge && data.total_items !== undefined) badge.textContent = data.total_items;
            } else {
                if (cantEl) cantEl.textContent = cantidad - 1;
                if (data.mensaje) {
                    AppUtils.showToast(data.mensaje, 'error', 'light');
                }
            }
        })
        .catch(() => {
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
        headers: {
            // FIX P3-10: header CSRF
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById('item-' + id);
                if (row) {
                    row.style.transition = 'opacity 0.3s, transform 0.3s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => row.remove(), 300);
                }

                if (data.subtotal) document.getElementById('subtotalText').textContent = '$' + parseFloat(data.subtotal).toFixed(2);
                if (data.iva) document.getElementById('ivaText').textContent = '$' + parseFloat(data.iva).toFixed(2);
                if (data.total) document.getElementById('totalText').textContent = '$' + parseFloat(data.total).toFixed(2);

                const badge = document.getElementById('cart-count');
                if (badge && data.total_items !== undefined) badge.textContent = data.total_items;

                if (data.total_items === 0) {
                    mostrarCarritoVacio();
                }

                AppUtils.showToast('Producto eliminado', 'success', 'light');
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
        headers: {
            // FIX P3-10: header CSRF
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mostrarCarritoVacio();
                const badge = document.getElementById('cart-count');
                if (badge) badge.textContent = '0';
                AppUtils.showToast('Carrito vaciado', 'success', 'light');
            }
        })
        .catch(() => { });
}

function mostrarCarritoVacio() {
    const container = document.querySelector('.max-w-4xl.mx-auto');
    if (!container) return;

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

    document.querySelectorAll('.qty-btn.plus').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const input = document.querySelector('.qty-input[data-id="' + id + '"]');
            if (!input) return;

            const stockEl = document.getElementById('stock-' + id);
            const maxStock = stockEl ? parseInt(stockEl.dataset.stock) : 999;
            const nuevaCantidad = parseInt(input.value) + 1;

            if (nuevaCantidad > maxStock) {
                AppUtils.showToast('Solo hay ' + maxStock + ' unidades disponibles en stock', 'warning', 'light');
                return;
            }

            input.value = nuevaCantidad;
            actualizarCantidad(id, nuevaCantidad);
        });
    });

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

            if (cantidad > maxStock) {
                cantidad = maxStock;
                this.value = maxStock;
                AppUtils.showToast('Solo hay ' + maxStock + ' unidades disponibles en stock', 'warning', 'light');
            }

            actualizarCantidad(id, cantidad);
        });
    });

    document.querySelectorAll('.qty-input').forEach(input => {
        const id = input.dataset.id;
        const cantidad = parseInt(input.value) || 1;
        actualizarEstadoBotonesItem(id, cantidad);
    });

    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            if (confirm('¿Eliminar este producto del carrito?')) {
                eliminarItem(id);
            }
        });
    });
});