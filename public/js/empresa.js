document.addEventListener('DOMContentLoaded', () => {
    const companyForm = document.getElementById('companyForm');

    if (companyForm) {
        companyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(companyForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch(`${URLROOT}/empresa/guardar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    AppUtils.showToast(result.mensaje, 'success');
                    // Opcional: recargar la página o actualizar elementos de la UI que dependan de la configuración
                    // Por ahora, asumimos que los cambios se reflejan automáticamente o se recargará si es necesario.
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