/**
 * carrito.js - Funcionalidad del carrito de compras
 * Dependencias: Toastify-js, URLROOT global, csrfToken global
 */

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