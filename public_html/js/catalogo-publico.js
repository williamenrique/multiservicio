/**
 * catalogo-publico.js - Funcionalidad del catálogo público
 * 
 * v2.0 (2026-10-09) — P2-04:
 *   • Se eliminaron Toastify y los Swal.fire({toast: true}) inline. Ahora
 *     se usa AppUtils.showToast(msg, type, 'light').
 * 
 * v2.1 (2026-10-09) — P3-10:
 *   • Header `X-CSRF-TOKEN` añadido al AJAX de agregar-carrito (además del
 *     `csrf_token` en el body, que ya se enviaba).
 * 
 * Dependencias: jQuery, SweetAlert2, AppUtils (utils.js), Lucide, URLROOT global
 */

// Inicializar iconos Lucide
lucide.createIcons();

// Cargar contador del carrito al inicio
$(document).ready(function () {
    actualizarContadorCarrito();
});

// Función para agregar al carrito vía AJAX
function agregarCarrito(productoId) {
    $.ajax({
        url: URLROOT + '/catalogo/agregar-carrito',
        method: 'POST',
        headers: {
            // FIX P3-10: header CSRF
            'X-CSRF-TOKEN': (typeof csrfToken !== 'undefined' ? csrfToken : '')
        },
        data: {
            id: productoId,
            cantidad: 1,
            csrf_token: csrfToken
        },
        dataType: 'json',
        success: function (res) {
            if (res.success) {
                const badge = $('#cart-count-header');
                badge.text(res.total_items).removeClass('hidden');
                AppUtils.showToast('Producto agregado al carrito', 'success', 'light');
            } else {
                AppUtils.showToast(res.error || 'No se pudo agregar', 'error', 'light');
            }
        },
        error: function () {
            AppUtils.showToast('Error de conexión', 'error', 'light');
        }
    });
}

// Verificar carrito antes de ir
function irAlCarrito() {
    $.get(URLROOT + '/catalogo/contar-carrito', function (res) {
        if (res.total_items > 0) {
            window.location.href = URLROOT + '/catalogo/carrito';
        } else {
            AppUtils.showToast('No hay repuestos seleccionados', 'warning', 'light');
        }
    });
}

// Actualizar contador del carrito
function actualizarContadorCarrito() {
    $.get(URLROOT + '/catalogo/contar-carrito', function (res) {
        if (res.total_items !== undefined) {
            const badge = $('#cart-count-header');
            badge.text(res.total_items).removeClass('hidden');
        }
    });
}