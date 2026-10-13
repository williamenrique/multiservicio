/**
 * Lógica para la gestión del perfil de usuario.
 * 
 * v2.0 (2026-10-09) — P2-10:
 *   • Se reemplazó la función local `setupPreview` por `AppUtils.setupImagePreview`,
 *     que ahora valida tamaño (2 MB) y tipo MIME antes de previsualizar.
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formPerfil');
    const inputFoto = document.getElementById('foto');
    const inputFotoFrente = document.getElementById('foto_frente');
    const imgPreview = document.getElementById('imgPreview');
    const imgFrentePreview = document.getElementById('imgFrentePreview');

    // Configurar previsualizaciones con validación (P2-10)
    if (inputFoto && imgPreview) {
        AppUtils.setupImagePreview(inputFoto, imgPreview);
    }
    if (inputFotoFrente && imgFrentePreview) {
        AppUtils.setupImagePreview(inputFotoFrente, imgFrentePreview);
    }

    // Envío del formulario
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        // Validación de contraseñas en cliente
        const pass = formData.get('new_password');
        const confirm = formData.get('confirm_password');
        if (pass && pass !== confirm) {
            AppUtils.showToast('Las contraseñas no coinciden', 'error');
            return;
        }

        try {
            AppUtils.showLoading('Actualizando perfil...');
            const response = await fetch(`${URLROOT}/perfil/actualizar`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: formData
            });

            const result = await response.json();
            AppUtils.hideLoading();

            if (result.success) {
                AppUtils.showAlert('¡Éxito!', result.mensaje, 'success');
            } else {
                AppUtils.showAlert('Error', result.mensaje, 'error');
            }
        } catch (error) {
            AppUtils.hideLoading();
            AppUtils.showAlert('Error', 'No se pudo conectar con el servidor', 'error');
        }
    });
});