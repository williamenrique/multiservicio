document.addEventListener('DOMContentLoaded', () => {
    const companyForm = document.getElementById('companyForm');
    const logoInput = document.getElementById('logoInput');
    const logoPreview = document.getElementById('logoPreview');

    // Lógica de vista previa de imagen
    if (logoInput && logoPreview) {
        logoInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    logoPreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (companyForm) {
        companyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Usamos FormData directamente para soportar la subida del archivo (logo)
            const formData = new FormData(companyForm);

            try {
                const response = await fetch(`${URLROOT}/empresa/guardar`, {
                    method: 'POST',
                    // Importante: No establecer Content-Type manualmente al usar FormData con archivos
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    AppUtils.showToast(result.mensaje, 'success');
                    setTimeout(() => window.location.reload(), 1500); // Recargar para actualizar header y pestaña
                } else {
                    AppUtils.showToast(result.mensaje, 'error');
                }
            } catch (error) {
                console.error("Error al guardar la configuración de la empresa:", error);
                AppUtils.showToast('Error de conexión al guardar la configuración.', 'error');
            }
        });
    }
});