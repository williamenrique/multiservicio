/**
 * detalle.js - Funcionalidad de la página de detalle de producto
 * 
 * v2.0 (2026-10-09) — P2-04:
 *   • Se eliminaron las llamadas inline a Toastify. Ahora se usa
 *     AppUtils.showToast(msg, type, 'light') — variante blanca del catálogo público.
 *   • Requiere que utils.js esté cargado ANTES que detalle.js en la vista.
 * 
 * Dependencias: AppUtils (utils.js), URLROOT global, maxStock global (definido en PHP)
 */

let cantidad = 1;
const precioUnitario = parseFloat(document.getElementById('subtotalDetalle')?.dataset?.precio || 0);
const btnMenos = document.querySelector('button[onclick="cambiarCantidad(-1)"]');
const btnMas = document.querySelector('button[onclick="cambiarCantidad(1)"]');
const inputCantidad = document.getElementById('cantidad');

function actualizarEstadoBotones() {
    if (btnMenos) btnMenos.disabled = (cantidad <= 1);
    if (btnMas) btnMas.disabled = (cantidad >= maxStock);
    if (btnMenos) btnMenos.style.opacity = cantidad <= 1 ? '0.4' : '1';
    if (btnMas) btnMas.style.opacity = cantidad >= maxStock ? '0.4' : '1';
}

function cambiarCantidad(delta) {
    const nuevaCantidad = cantidad + delta;

    if (nuevaCantidad < 1) return;

    if (nuevaCantidad > maxStock) {
        AppUtils.showToast('Solo hay ' + maxStock + ' unidades disponibles en stock', 'warning', 'light');
        return;
    }

    cantidad = nuevaCantidad;
    if (inputCantidad) inputCantidad.value = cantidad;
    actualizarSubtotal();
    actualizarEstadoBotones();
}

function actualizarSubtotal() {
    const subtotalEl = document.getElementById('subtotalDetalle');
    if (subtotalEl && precioUnitario) {
        const subtotal = precioUnitario * cantidad;
        subtotalEl.textContent = '$' + subtotal.toFixed(2);
    }
}

function agregarCarrito(id) {
    if (cantidad > maxStock) {
        AppUtils.showToast('No hay suficiente stock. Máximo: ' + maxStock + ' unidades.', 'warning', 'light');
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
                AppUtils.showToast(data.mensaje || 'Producto agregado', 'success', 'light');
                const badge = document.getElementById('cartCount');
                if (badge) {
                    badge.textContent = data.total_items;
                    badge.classList.remove('hidden');
                }
            } else {
                AppUtils.showToast(data.mensaje || 'No se pudo agregar', 'error', 'light');
            }
        })
        .catch(() => {
            AppUtils.showToast('Error al conectar', 'error', 'light');
        });
}

// Cargar conteo del carrito
document.addEventListener('DOMContentLoaded', function () {
    fetch(URLROOT + '/catalogo/contar-carrito')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('cartCount');
            if (data.total_items > 0 && badge) {
                badge.textContent = data.total_items;
                badge.classList.remove('hidden');
            }
        })
        .catch(() => { });
});