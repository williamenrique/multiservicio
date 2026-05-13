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
        const response = await fetch(`api.php?key=${key}`, {
            method: 'POST', // O 'PUT'
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });
        if (!response.ok) {
            throw new Error(`Failed to save data for ${key}: ${response.statusText}`);
        }
        try {
            return await response.json();
        } catch (e) {
            const errorText = await response.text();
            console.error(`Error parsing JSON response for ${key}:`, errorText, e);
            throw new Error(`Received non-JSON response when saving data for ${key}. Response: ${errorText.substring(0, 200)}`);
        }
    },

    loadData: async (key) => {
        // Usar fetch para cargar desde archivos JSON a través de PHP
        const response = await fetch(`api.php?key=${key}`, {
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
            return text ? JSON.parse(text) : [];
        } catch (e) {
            console.warn(`Archivo JSON para ${key} está vacío o corrupto. Devolviendo [].`);
            return [];
        }
    },

    // Verificar e inicializar archivos JSON en el servidor
    checkAndInitDB: async () => {
        try {
            await fetch('api.php?action=init');
        } catch (e) {
            console.error("Error al inicializar la base de datos JSON:", e);
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