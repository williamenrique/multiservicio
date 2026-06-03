/**
 * Core App Utilities
 * Centraliza funciones comunes para mantener el código DRY.
 */
const AppUtils = {
    /**
     * Muestra una alerta informativa o de éxito usando SweetAlert2.
     * @param {string} title Título de la alerta.
     * @param {string} text Mensaje descriptivo.
     * @param {string} icon Tipo de icono (success, error, warning, info).
     */
    showAlert: (title, text, icon = 'success') => {
        return Swal.fire({
            title,
            text,
            icon,
            background: '#ffffff',
            color: '#1e293b',
            confirmButtonColor: '#39FF14',
            confirmButtonText: '<span style="color: #000">Aceptar</span>'
        });
    },

    /**
     * Muestra una notificación rápida (Toast) en la parte superior derecha.
     * @param {string} msg Mensaje a mostrar.
     * @param {string} type Tipo de notificación (success, warning, error, info).
     */
    showToast: (msg, type = 'success') => {
        Toastify({
            text: msg,
            duration: 3000,
            gravity: "top",
            position: "right",
            style: {
                background: '#000000',
                color: '#ffffff',
                borderRadius: '8px',
                fontWeight: 'bold'
            }
        }).showToast();
    },

    /**
     * Muestra un cuadro de diálogo de confirmación antes de ejecutar una acción.
     * @param {string} title Título de la pregunta.
     * @param {string} text Advertencia o detalle adicional.
     * @param {function} onConfirm Función a ejecutar si el usuario acepta.
     * @param {string} icon Icono a mostrar.
     * @param {string} confirmText Texto del botón de confirmación.
     * @param {string} confirmColor Color hexadecimal del botón.
     */
    confirmAction: (title, text, onConfirm, icon = 'warning', confirmText = 'Sí, continuar', confirmColor = '#ef4444') => {
        return Swal.fire({
            title,
            text,
            icon,
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) onConfirm();
        });
    },

    /**
     * Formatea un número como moneda colombiana (COP).
     * @param {number} amount Monto a formatear.
     * @returns {string} String formateado (ej: $ 1.000).
     */
    formatCurrency: (amount) => {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 2
        }).format(amount);
    },

    /**
     * Abre un visor de imagen a pantalla completa usando SweetAlert2.
     * @param {string} url Ruta de la imagen.
     * @param {string} title Título para el visor.
     */
    viewImage: (url, title) => {
        Swal.fire({
            title: title,
            imageUrl: url,
            imageAlt: title,
            showCloseButton: true,
            showConfirmButton: false,
            background: '#ffffff',
            color: '#1e293b'
        });
    },

    /**
     * Muestra una pantalla de carga bloqueante
     */
    showLoading: (msg = 'Cargando...') => {
        Swal.fire({
            title: msg,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });
        setTimeout(() => { if (Swal.isVisible() && Swal.isLoading()) Swal.close(); }, 20000);
    },

    /**
     * Oculta la pantalla de carga
     */
    hideLoading: () => {
        if (Swal.isVisible()) Swal.close();
    }
};