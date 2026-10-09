/**
 * Core App Utilities
 * Centraliza funciones comunes para mantener el código DRY.
 * 
 * v2.0 (2026-10-09) — P2-10:
 *   • Utilidades para subida de archivos (validateImageFile, previewImage,
 *     setupImagePreview) que eliminan duplicación en inventario.js,
 *     perfil.js y empresa.js.
 * 
 * v2.1 (2026-10-09) — P2-09:
 *   • Helper `openQuickClientModal(options)` que unifica los 2 modales Swal
 *     gemelos de registro rápido de cliente (facturacion.js + taller_nueva_orden.js).
 * 
 * v2.2 (2026-10-09) — P2-04:
 *   • `showToast(msg, type, variant)` ahora usa SIEMPRE SweetAlert2 con dos
 *     variantes visuales:
 *       - variant = 'dark'  → fondo negro, verde neón (dashboard)
 *       - variant = 'light' → fondo blanco, texto oscuro (catálogo público)
 *     Retrocompatible: si no se pasa variant, se usa 'dark'.
 *   • Toastify ya NO se usa desde AppUtils. Se puede eliminar del CDN en
 *     una limpieza posterior (P3-03).
 */
const AppUtils = {

    // ═══════════════════════════════════════════════════════════════
    //  SUBIDA DE IMÁGENES (P2-10)
    // ═══════════════════════════════════════════════════════════════

    MAX_FILE_SIZE: 2 * 1024 * 1024,

    ALLOWED_IMAGE_TYPES: [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif'
    ],

    validateImageFile: (file) => {
        if (!file) {
            return { ok: false, error: 'No se seleccionó ningún archivo.' };
        }
        if (!AppUtils.ALLOWED_IMAGE_TYPES.includes(file.type)) {
            return { ok: false, error: 'Formato no permitido. Usa JPG, PNG, WEBP o GIF.' };
        }
        if (file.size > AppUtils.MAX_FILE_SIZE) {
            const mb = (AppUtils.MAX_FILE_SIZE / 1024 / 1024).toFixed(1);
            return { ok: false, error: `El archivo supera el límite de ${mb} MB.` };
        }
        return { ok: true };
    },

    previewImage: (file, preview) => {
        return new Promise((resolve, reject) => {
            if (!file || !preview) return reject(new Error('Parámetros inválidos'));
            const reader = new FileReader();
            reader.onload = (e) => {
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                }
                resolve();
            };
            reader.onerror = () => reject(reader.error);
            reader.readAsDataURL(file);
        });
    },

    setupImagePreview: (input, preview, options = {}) => {
        if (!input || !preview) return;
        input.addEventListener('change', async function () {
            const file = this.files[0];
            const validation = AppUtils.validateImageFile(file);
            if (!validation.ok) {
                if (!options.silentOnError) {
                    AppUtils.showToast(validation.error, 'error');
                }
                this.value = '';
                return;
            }
            try {
                await AppUtils.previewImage(file, preview);
                if (typeof options.onSuccess === 'function') {
                    options.onSuccess(file);
                }
            } catch (e) {
                if (!options.silentOnError) {
                    AppUtils.showToast('Error al previsualizar la imagen.', 'error');
                }
                this.value = '';
            }
        });
    },

    // ═══════════════════════════════════════════════════════════════
    //  MODAL DE REGISTRO RÁPIDO DE CLIENTE (P2-09)
    // ═══════════════════════════════════════════════════════════════

    openQuickClientModal: async (options = {}) => {
        const {
            presetId = '',
            presetNombre = '',
            title = 'Registro Rápido de Cliente',
            confirmText = 'REGISTRAR Y SELECCIONAR',
            onSuccess = null
        } = options;

        const requiereId = !presetId;
        const csrf = (typeof CSRF_TOKEN !== 'undefined' && CSRF_TOKEN) ? CSRF_TOKEN : '';

        const { value: formValues } = await Swal.fire({
            title: `<span class="text-[10px] uppercase text-slate-400 font-black tracking-widest">${title}</span>` +
                   (presetId ? `<br><span class="text-navy-blue">ID: ${presetId}</span>` : ''),
            html: `
                <div class="text-left space-y-4 pt-4">
                    ${requiereId ? `
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Cédula / NIT *</label>
                        <input id="swal-cli-id" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none" placeholder="EJ: 12345678">
                    </div>` : ''}
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Nombre Completo *</label>
                        <input id="swal-cli-nombre" value="${presetNombre}" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm uppercase font-bold focus:ring-2 focus:ring-blue-500 outline-none" placeholder="EJ: JUAN PEREZ">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Correo Electrónico</label>
                            <input id="swal-cli-email" type="email" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 outline-none" placeholder="cliente@correo.com">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Teléfono</label>
                            <input id="swal-cli-telefono" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="04...">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Dirección</label>
                        <input id="swal-cli-direccion" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm uppercase focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Opcional">
                    </div>
                </div>`,
            showCancelButton: true,
            confirmButtonText: confirmText,
            confirmButtonColor: '#10b981',
            cancelButtonText: 'CANCELAR',
            cancelButtonColor: '#64748b',
            preConfirm: () => {
                const id = requiereId
                    ? document.getElementById('swal-cli-id').value.trim()
                    : presetId;
                const nombre = document.getElementById('swal-cli-nombre').value.trim();

                if (!id) { Swal.showValidationMessage('La cédula/NIT es obligatoria'); return false; }
                if (!nombre) { Swal.showValidationMessage('El nombre es obligatorio'); return false; }

                return {
                    id: id.toUpperCase(),
                    nombre: nombre.toUpperCase(),
                    email: document.getElementById('swal-cli-email').value.trim().toLowerCase(),
                    telefono: document.getElementById('swal-cli-telefono').value.trim(),
                    direccion: document.getElementById('swal-cli-direccion').value.trim().toUpperCase()
                };
            }
        });

        if (!formValues) return { cancelled: true, cliente: null };

        try {
            AppUtils.showLoading('Registrando cliente...');
            const res = await fetch(`${URLROOT}/clientes/guardar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(formValues)
            });
            AppUtils.hideLoading();

            if (res.status === 403) {
                AppUtils.showAlert('Sesión expirada', 'El token de seguridad no es válido. Recargue la página e intente de nuevo.', 'error');
                return { cancelled: true, cliente: null };
            }

            const data = await res.json();
            if (data.success) {
                if (typeof onSuccess === 'function') {
                    onSuccess(formValues);
                }
                return { cancelled: false, cliente: formValues };
            } else {
                AppUtils.showToast(data.mensaje || data.error || 'Error al guardar cliente', 'error');
                return { cancelled: true, cliente: null };
            }
        } catch (e) {
            AppUtils.hideLoading();
            console.error('Error registrando cliente:', e);
            AppUtils.showToast('Error de conexión al registrar cliente', 'error');
            return { cancelled: true, cliente: null };
        }
    },

    // ═══════════════════════════════════════════════════════════════
    //  ALERTAS Y NOTIFICACIONES
    // ═══════════════════════════════════════════════════════════════

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
     * Muestra una notificación rápida (Toast) usando SweetAlert2.
     * 
     * @param {string} msg           Mensaje a mostrar.
     * @param {string} type          'success' | 'error' | 'warning' | 'info'
     * @param {string} variant       'dark' (default, dashboard) | 'light' (catálogo público)
     */
    showToast: (msg, type = 'success', variant = 'dark') => {
        const isLight = variant === 'light';

        // Mapear tipos no estándar al icono de SweetAlert2
        const iconMap = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
        const icon = iconMap[type] || 'info';

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            icon,
            title: msg,
            background: isLight ? '#ffffff' : '#000000',
            color: isLight ? '#1e293b' : '#ffffff',
            didOpen: (toast) => {
                toast.style.borderRadius = '12px';
                toast.style.fontWeight = isLight ? '600' : '900';
                toast.style.fontSize = '13px';
                if (isLight) {
                    toast.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.08)';
                    toast.style.border = '1px solid #e2e8f0';
                } else {
                    toast.style.boxShadow = '0 0 20px rgba(57, 255, 20, 0.4)';
                    toast.style.border = '1px solid rgba(57, 255, 20, 0.3)';
                    toast.style.textTransform = 'uppercase';
                }
            }
        });
    },

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

    formatCurrency: (amount) => {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 2
        }).format(amount);
    },

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

    _loadingTimeoutId: null,

    showLoading: (msg = 'Cargando...') => {
        if (AppUtils._loadingTimeoutId) {
            clearTimeout(AppUtils._loadingTimeoutId);
            AppUtils._loadingTimeoutId = null;
        }
        Swal.fire({
            title: msg,
            background: '#000000',
            color: '#ffffff',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });
        AppUtils._loadingTimeoutId = setTimeout(() => {
            if (Swal.isVisible() && Swal.isLoading()) Swal.close();
            AppUtils._loadingTimeoutId = null;
        }, 20000);
    },

    hideLoading: () => {
        if (AppUtils._loadingTimeoutId) {
            clearTimeout(AppUtils._loadingTimeoutId);
            AppUtils._loadingTimeoutId = null;
        }
        if (Swal.isVisible()) Swal.close();
    }
};