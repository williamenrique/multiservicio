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
     * Guarda datos en un archivo JSON en el servidor (Legacy).
     * @param {string} key Nombre del archivo.
     * @param {any} data Datos a serializar.
     */
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

    /**
     * Carga datos desde un archivo JSON en el servidor (Legacy).
     * @param {string} key Nombre del archivo.
     * @returns {Array|Object} Datos parseados.
     */
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
    }
};