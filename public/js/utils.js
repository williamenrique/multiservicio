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

    // Confirmación genérica con SweetAlert2
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

    // Formateador de moneda
    formatCurrency: (amount) => {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0
        }).format(amount);
    },

    // LocalStorage Helpers
    saveData: async (key, data) => {
        // Usar fetch para guardar en archivos JSON a través de PHP
        const response = await fetch(`${URLROOT}/dashboard/api?key=${key}`, {
            method: 'POST', // O 'PUT'
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });

        const responseText = await response.text();

        if (!response.ok) {
            throw new Error(`Failed to save data for ${key}: ${response.statusText}. Respuesta: ${responseText}`);
        }

        try {
            return JSON.parse(responseText);
        } catch (e) {
            console.error(`Error interpretando JSON para ${key}:`, responseText, e);
            throw new Error(`Respuesta del servidor no es JSON válido para ${key}.`);
        }
    },

    loadData: async (key) => {
        // Usar fetch para cargar desde archivos JSON a través de PHP
        const response = await fetch(`${URLROOT}/dashboard/api?key=${key}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        });
        if (!response.ok) {
            // Si el archivo no existe o hay un error, devolver un array vacío para evitar que la app se rompa
            console.error(`Failed to load data for ${key}: ${response.statusText}`);
            return [];
        }

        const text = await response.text();
        try {
            return (text && text.trim() !== "") ? JSON.parse(text) : [];
        } catch (e) {
            console.warn(`Archivo JSON para ${key} está vacío o corrupto. Contenido: ${text.substring(0, 100)}. Devolviendo [].`);
            return [];
        }
    },

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