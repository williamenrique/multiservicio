document.addEventListener('DOMContentLoaded', function () {
    const formLogin = document.getElementById('formLogin');
    const alertError = document.getElementById('alert-error');
    const btnSubmit = document.getElementById('btnSubmit');

    if (formLogin) {
        formLogin.addEventListener('submit', async function (e) {
            e.preventDefault(); // Evita el envío tradicional y la recarga de página

            // Bloquear botón para evitar múltiples clics
            btnSubmit.disabled = true;
            btnSubmit.textContent = "Verificando...";
            alertError.style.display = 'none';

            // Preparar los datos en un objeto estructurado
            const data = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                csrf_token: document.getElementById('csrf_token').value
            };

            try {
                // Realizar la petición asíncrona hacia el controlador
                const response = await fetch(`${URLROOT}auth/login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                // Validar que la respuesta del servidor sea correcta a nivel HTTP
                if (!response.ok) throw new Error('Error en la comunicación con el servidor.');

                const result = await response.json();

                if (result.success) {
                    // Si las credenciales son válidas, JavaScript redirecciona al Dashboard de inmediato
                    window.location.href = result.redirect;
                } else {
                    // Si falló, desbloquea la interfaz y muestra el error dinámicamente
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = "Ingresar al Sistema";
                    alertError.textContent = result.error;
                    alertError.style.display = 'block';
                }

            } catch (error) {
                btnSubmit.disabled = false;
                btnSubmit.textContent = "Ingresar al Sistema";
                alertError.textContent = 'Ocurrió un error inesperado. Intente de nuevo.';
                alertError.style.display = 'block';
            }
        });
    }
});

