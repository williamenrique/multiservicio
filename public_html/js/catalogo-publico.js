/**
 * catalogo-publico.js - Funcionalidad del catálogo público
 * Dependencias: jQuery, SweetAlert2, Toastify-js, Lucide, URLROOT global
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
        data: {
            id: productoId,
            cantidad: 1,
            csrf_token: csrfToken
        },
        dataType: 'json',
        success: function (res) {
            if (res.success) {
                // Actualizar badge inmediatamente con el total devuelto
                const badge = $('#cart-count-header');
                badge.text(res.total_items).removeClass('hidden');

                Swal.fire({
                    icon: 'success',
                    title: '¡Agregado!',
                    text: 'Producto agregado al carrito',
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.error || 'No se pudo agregar'
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión'
            });
        }
    });
}

// Verificar carrito antes de ir
function irAlCarrito() {
    $.get(URLROOT + '/catalogo/contar-carrito', function (res) {
        if (res.total_items > 0) {
            window.location.href = URLROOT + '/catalogo/carrito';
        } else {
            Toastify({
                text: 'No hay repuestos seleccionados',
                duration: 2500,
                gravity: 'bottom',
                position: 'right',
                style: { background: '#f59e0b' }
            }).showToast();
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