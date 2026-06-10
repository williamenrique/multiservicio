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
            background: '#000000',
            color: '#ffffff',
            confirmButtonColor: '#39FF14',
            confirmButtonText: '<span style="color: #000; font-weight: 900; text-transform: uppercase;">Aceptar</span>',
            customClass: {
                popup: 'rounded-3xl border border-slate-800 shadow-[0_0_20px_rgba(57,255,20,0.2)]',
                title: 'text-white'
            }
        });
    },

    /**
     * Muestra una notificación rápida (Toast) en la parte superior derecha.
     * @param {string} msg Mensaje a mostrar.
     * @param {string} type Tipo de notificación (success, warning, error, info).
     */
    showToast: (msg, type = 'success') => {
        if (typeof Toastify === 'function') {
            Toastify({
                text: msg,
                duration: 3000,
                gravity: "top",
                position: "right",
                style: {
                    background: '#000000',
                    color: '#ffffff',
                    borderRadius: '12px',
                    fontWeight: '900',
                    fontSize: '13px',
                    boxShadow: '0 0 20px rgba(57, 255, 20, 0.4)',
                    border: '1px solid rgba(57, 255, 20, 0.3)',
                    textTransform: 'uppercase'
                }
            }).showToast();
        } else {
            // Fallback a SweetAlert2 si Toastify no está cargado
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: type,
                title: msg,
                background: '#000000',
                color: '#ffffff',
                didOpen: (toast) => {
                    toast.style.borderRadius = "12px";
                    toast.style.boxShadow = "0 0 20px rgba(57, 255, 20, 0.4)";
                    toast.style.border = "1px solid rgba(57, 255, 20, 0.2)";
                }
            });
        }
    },

    /**
     * Muestra un cuadro de diálogo de confirmación antes de ejecutar una acción.
     * @param {string} title Título de la pregunta.
     * @param {string} text Advertencia o detalle adicional.
     * @param {function} onConfirm Función a ejecutar si el usuario acepta.
     * @param {string} icon Icono a mostrar.
     * @param {string} confirmText Texto del botón de confirmación.
     * @param {string} confirmColor Color hexadecimal del botón.
     * @param {string} cancelText Texto del botón de cancelación.
     */
    confirmAction: (title, text, onConfirm, icon = 'warning', confirmText = 'Sí, continuar', confirmColor = '#ef4444', cancelText = 'Cancelar') => {
        return Swal.fire({
            title,
            text,
            icon,
            background: '#000000',
            color: '#ffffff',
            showCancelButton: true,
            confirmButtonColor: confirmColor || '#ef4444',
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            customClass: {
                popup: 'rounded-3xl border border-slate-800 shadow-[0_0_20px_rgba(57,255,20,0.2)]'
            }
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
            background: '#000000',
            color: '#ffffff',
            customClass: {
                popup: 'rounded-3xl border border-slate-800 shadow-2xl'
            }
        });
    },

    /**
     * Muestra una pantalla de carga bloqueante
     */
    showLoading: (msg = 'Cargando...') => {
        Swal.fire({
            title: msg,
            background: '#000000',
            color: '#ffffff',
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