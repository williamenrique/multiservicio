document.addEventListener('DOMContentLoaded', function () {
    const formLogin = document.getElementById('formLogin');
    const alertError = document.getElementById('alert-error');
    const btnSubmit = document.getElementById('btnSubmit');

    if (formLogin) {
        formLogin.addEventListener('submit', async function (e) {
            e.preventDefault(); // Evita el envío tradicional y la recarga de página

            const usuario = document.getElementById('usuario').value;
            const password = document.getElementById('password').value;
            const csrf_token = document.getElementById('csrf_token').value;

            realizarLogin(usuario, password, csrf_token);
        });
    }

    async function realizarLogin(usuario, password, csrf_token, force = false) {
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
                    usuario,
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

                // Inyectamos el mensaje y un botón dentro del div de alerta de error
                alertError.innerHTML = `
                    <div class="flex flex-col gap-3">
                        <p class="font-medium">${result.error}</p>
                        <button type="button" id="btnForceLogin" class="bg-white/20 hover:bg-white/30 text-white border border-white/40 py-2 px-3 rounded-lg text-xs font-bold transition-all uppercase">
                            Cerrar sesión remota y entrar aquí
                        </button>
                    </div>
                `;
                alertError.style.display = 'block';

                // Escuchar el clic del nuevo botón generado dinámicamente
                document.getElementById('btnForceLogin').addEventListener('click', () => {
                    realizarLogin(usuario, password, csrf_token, true);
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
