document.addEventListener('DOMContentLoaded', function () {
    const formLogin = document.getElementById('formLogin');
    const alertError = document.getElementById('alert-error');
    const btnSubmit = document.getElementById('btnSubmit');

    if (formLogin) {
        formLogin.addEventListener('submit', async function (e) {
            e.preventDefault(); // Evita el envío tradicional y la recarga de página

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const csrf_token = document.getElementById('csrf_token').value;

            realizarLogin(email, password, csrf_token);
        });
    }

    async function realizarLogin(email, password, csrf_token, force = false) {
        // Bloquear botón para evitar múltiples clics
        btnSubmit.disabled = true;
        btnSubmit.textContent = "Verificando...";
        alertError.style.display = 'none';

        try {
            // Realizar la petición asíncrona hacia el controlador
            const response = await fetch(`${URLROOT}/auth/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email,
                    password,
                    csrf_token,
                    force
                })
            });

            // Validar que la respuesta del servidor sea correcta a nivel HTTP
            if (!response.ok) throw new Error('Error en la comunicación con el servidor.');

            const result = await response.json();

            if (result.success) {
                // Si las credenciales son válidas, JavaScript redirecciona al Dashboard de inmediato
                window.location.href = result.redirect;
            } else if (result.session_exists) {
                // Caso especial: Sesión abierta detectada
                btnSubmit.disabled = false;
                btnSubmit.textContent = "Ingresar al Sistema";

                Swal.fire({
                    title: 'Sesión Activa Detectada',
                    text: 'Parece que ya tienes una sesión abierta en otro dispositivo o navegador. ¿Deseas cerrarla e iniciar sesión aquí?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#39FF14',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '<span style="color: #000">Sí, cerrar la otra sesión</span>',
                    cancelButtonText: 'No, cancelar'
                }).then((swalResult) => {
                    if (swalResult.isConfirmed) {
                        // Reintento de login con el flag de "force"
                        realizarLogin(email, password, csrf_token, true);
                    }
                });
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
    }
});
