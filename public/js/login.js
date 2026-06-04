/**
 * Manejo del formulario de Login mediante Fetch API.
 */
document.addEventListener('DOMContentLoaded', function () {
    const formLogin = document.getElementById('formLogin');
    const alertError = document.getElementById('alert-error');
    const btnSubmit = document.getElementById('btnSubmit');

    if (formLogin) {
        formLogin.addEventListener('submit', async function (e) {
            e.preventDefault();

            const usuario = document.getElementById('usuario').value;
            const password = document.getElementById('password').value;
            const csrf_token = document.getElementById('csrf_token').value;

            realizarLogin(usuario, password, csrf_token); // Llamada inicial
        });
    }

    // Botón "Olvidé mi contraseña" o similar
    const btnOlvidar = document.getElementById('btnForgotPassword');
    if (btnOlvidar) {
        btnOlvidar.addEventListener('click', (e) => {
            e.preventDefault();
            abrirModalRecuperacion();
        });
    }

    async function abrirModalRecuperacion() {
        const { value: identificador } = await Swal.fire({
            title: 'Recuperar Acceso',
            text: 'Ingrese su Usuario, Correo o Cédula para validar su identidad.',
            input: 'text',
            inputPlaceholder: 'Identificador...',
            showCancelButton: true,
            confirmButtonText: 'Enviar Solicitud',
            confirmButtonColor: '#39FF14',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value) return 'Debe ingresar un dato válido';
            }
        });

        if (identificador) {
            try {
                AppUtils.showLoading('Validando...');
                const csrfToken = document.getElementById('csrf_token')?.value || '';
                const res = await fetch(`${URLROOT}/auth/solicitarRecuperacion`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ identificador })
                });

                AppUtils.hideLoading();
                const result = await res.json();

                if (result.success) {
                    AppUtils.showToast(result.mensaje || 'Solicitud enviada al administrador', 'success');
                } else {
                    AppUtils.showToast(result.error || 'No se pudo procesar la solicitud', 'error');
                }
            } catch (error) {
                AppUtils.hideLoading();
                AppUtils.showToast('Error de comunicación con el servidor', 'error');
            }
        }
    }

    /**
     * Realiza la petición asíncrona al servidor.
     * @param {boolean} force - Si es true, el servidor cerrará cualquier sesión previa.
     */
    async function realizarLogin(usuario, password, csrf_token, force = false) {
        btnSubmit.disabled = true;
        btnSubmit.textContent = "Verificando...";
        alertError.style.display = 'none';

        try {
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

            const result = await response.json();

            if (!response.ok && !result.session_exists) {
                throw new Error(result.error || 'Error en la autenticación');
            }

            if (response.ok && result.success) {
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
            alertError.textContent = error.message;
            alertError.style.display = 'block';
        }
    }
});
