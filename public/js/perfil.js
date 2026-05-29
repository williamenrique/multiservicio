/**
 * Lógica para la gestión del perfil de usuario
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formPerfil');
    const inputFoto = document.getElementById('foto');
    const inputFotoFrente = document.getElementById('foto_frente');
    const imgPreview = document.getElementById('imgPreview');
    const imgFrentePreview = document.getElementById('imgFrentePreview');

    // Función genérica de vista previa
    const setupPreview = (input, preview) => {
        input.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => preview.src = e.target.result;
                reader.readAsDataURL(file);
            }
        });
    };

    setupPreview(inputFoto, imgPreview);
    setupPreview(inputFotoFrente, imgFrentePreview);

    // Envío del formulario
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
        } catch (error) {
            AppUtils.showAlert('Error', 'No se pudo conectar con el servidor', 'error');
        } finally {
            AppUtils.hideLoading();
            if (typeof result !== 'undefined') {
                if (result.success) AppUtils.showAlert('¡Éxito!', result.mensaje, 'success');
                else AppUtils.showAlert('Error', result.mensaje, 'error');
            }
        }
    });
});