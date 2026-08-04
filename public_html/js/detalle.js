/**
 * detalle.js - Funcionalidad de la página de detalle de producto
 * Dependencias: Toastify-js, URLROOT global, maxStock global (definido en PHP)
 */

let cantidad = 1;
const precioUnitario = parseFloat(document.getElementById('subtotalDetalle')?.dataset?.precio || 0);

function cambiarCantidad(delta) {
    const nuevaCantidad = cantidad + delta;

    // No bajar de 1, no subir más allá del stock disponible
    if (nuevaCantidad < 1) return;
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

    cantidad = nuevaCantidad;
    document.getElementById('cantidad').value = cantidad;
    actualizarSubtotal();
}

function actualizarSubtotal() {
    const subtotalEl = document.getElementById('subtotalDetalle');
    if (subtotalEl && precioUnitario) {
        const subtotal = precioUnitario * cantidad;
        subtotalEl.textContent = '$' + subtotal.toFixed(2);
    }
}

function agregarCarrito(id) {
    // Validar que no exceda el stock antes de enviar
    if (cantidad > maxStock) {
        Toastify({
            text: '⚠ No hay suficiente stock. Máximo: ' + maxStock + ' unidades.',
            duration: 3000,
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
document.addEventListener('DOMContentLoaded', function () {
    fetch(URLROOT + '/catalogo/contar-carrito')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('cartCount');
            if (data.total_items > 0) {
                badge.textContent = data.total_items;
                badge.classList.remove('hidden');
            }
        })
        .catch(() => { });
});