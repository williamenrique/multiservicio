/**
 * Lógica para la configuración de la empresa.
 * 
 * v2.0 (2026-10-09) — P2-10:
 *   • Se reemplazó el listener inline del logo por `AppUtils.setupImagePreview`,
 *     que ahora valida tamaño (2 MB) y tipo MIME antes de previsualizar.
 */
document.addEventListener('DOMContentLoaded', () => {
    const companyForm = document.getElementById('companyForm');
    const logoInput = document.getElementById('logoInput');
    const logoPreview = document.getElementById('logoPreview');

    // Previsualización del logo con validación (P2-10)
    if (logoInput && logoPreview) {
        AppUtils.setupImagePreview(logoInput, logoPreview);
    }

    // Envío del formulario
    if (!companyForm) return;

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
});