/**
 * Core App Utilities
 * Centraliza funciones comunes para mantener el código DRY.
 */
const AppUtils = {
    // Wrapper para SweetAlert2
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

    // Wrapper para Toastify
    showToast: (msg, type = 'success') => {
        const colors = {
            success: '#39FF14',
            warning: '#FFCD00',
            error: '#ff4444',
            info: '#0b1120'
        };

        Toastify({
            text: msg,
            duration: 3000,
            gravity: "top",
            position: "right",
            style: {
                background: colors[type],
                color: type === 'success' || type === 'warning' ? '#000' : '#fff',
                borderRadius: '8px',
                fontWeight: 'bold'
            }
        }).showToast();
    },

    // Formateador de moneda
    formatCurrency: (amount) => {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0
        }).format(amount);
    },

    // LocalStorage Helpers
    saveData: (key, data) => localStorage.setItem(key, JSON.stringify(data)),

    loadData: (key) => JSON.parse(localStorage.getItem(key)) || [],

    // Visualizador de imágenes
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
    }
};