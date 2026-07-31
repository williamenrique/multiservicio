/**
 * detalle.js - Funcionalidad de la página de detalle de producto
 * Dependencias: Toastify-js, URLROOT global
 */

let cantidad = 1;
let maxStock = 0;

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