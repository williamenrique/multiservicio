<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo s($titulo); ?> - <?php echo SITENAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>const URLROOT = "<?php echo URLROOT; ?>";</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.js"></script>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
        .nav-blur { backdrop-filter: blur(12px); background: rgba(15, 23, 42, 0.9); }
        .btn-primary { background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; transition: all 0.2s; }
        .btn-primary:hover { background: #059669; }
        .btn-primary:disabled { background: #9ca3af; cursor: not-allowed; }
        .input-field { width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 0.75rem; transition: all 0.2s; }
        .input-field:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15); }
        .input-error { border-color: #ef4444; }
        main { flex: 1 0 auto; }
        .spinner { display: inline-block; width: 18px; height: 18px; border: 3px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <!-- NAVBAR -->
    <nav class="nav-blur border-b border-gray-700/50 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="<?php echo URLROOT; ?>/catalogo" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-white">CATÁLOGO</span>
                </a>
                <a href="<?php echo URLROOT; ?>/catalogo/carrito" class="text-sm text-gray-400 hover:text-white transition-colors">&larr; Volver al carrito</a>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Finalizar Pedido</h1>

        <!-- Errores -->
        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                <ul class="list-disc list-inside text-red-700 text-sm">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo s($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid md:grid-cols-5 gap-6">
            <!-- Formulario -->
            <div class="md:col-span-3">
                <div class="bg-white rounded-2xl shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Tus Datos</h2>
                    <form action="<?php echo URLROOT; ?>/catalogo/procesar-pedido" method="POST" id="checkoutForm">
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo *</label>
                                <input type="text" name="nombre" value="<?php echo s($formData['nombre'] ?? ''); ?>" required class="input-field" placeholder="Tu nombre completo">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cédula / NIT *</label>
                                <input type="text" name="cedula" value="<?php echo s($formData['cedula'] ?? ''); ?>" required class="input-field" placeholder="Sin puntos ni guiones">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono *</label>
                                <input type="tel" name="telefono" value="<?php echo s($formData['telefono'] ?? ''); ?>" required class="input-field" placeholder="Número de contacto">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico *</label>
                                <input type="email" name="correo" value="<?php echo s($formData['correo'] ?? ''); ?>" required class="input-field" placeholder="correo@ejemplo.com">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                                <input type="text" name="direccion" value="<?php echo s($formData['direccion'] ?? ''); ?>" class="input-field" placeholder="Dirección de entrega (opcional)">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notas del Pedido</label>
                                <textarea name="notas" rows="3" class="input-field" placeholder="Notas adicionales..."><?php echo s($formData['notas'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <button type="submit" id="btnConfirmarPedido" class="btn-primary w-full py-3 mt-6 text-base font-semibold flex items-center justify-center gap-2">
                            <span id="btnTexto">Confirmar Pedido</span>
                            <span id="btnSpinner" class="spinner hidden"></span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Resumen del pedido -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-2xl shadow-md p-6 sticky top-24">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Resumen del Pedido</h2>
                    <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                        <?php foreach ($items as $item): ?>
                            <div class="py-3 flex justify-between text-sm">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 truncate"><?php echo s($item['nombre']); ?></p>
                                    <p class="text-gray-400 text-xs"><?php echo $item['cantidad']; ?> x $<?php echo number_format($item['precio'], 2); ?></p>
                                </div>
                                <span class="font-medium text-gray-800 ml-2">$<?php echo number_format($item['subtotal'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium">$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">IVA (0%) <span class="text-xs text-gray-400">— Deshabilitado</span></span>
                            <span class="font-medium text-gray-400">$0.00</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200">
                            <span>Total</span>
                            <span class="text-emerald-600">$<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-gray-400 py-8 mt-12 w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-sm">&copy; <?php echo date('Y'); ?> <?php echo SITENAME; ?>. Todos los derechos reservados.</p>
        </div>
    </footer>

<script>
(function () {
    const form = document.getElementById('checkoutForm');
    const btn = document.getElementById('btnConfirmarPedido');
    const btnTexto = document.getElementById('btnTexto');
    const btnSpinner = document.getElementById('btnSpinner');
    if (!form || !btn) return;

    function setProcesando(on) {
        btn.disabled = on;
        btnTexto.textContent = on ? 'Procesando pedido...' : 'Confirmar Pedido';
        btnSpinner.classList.toggle('hidden', !on);
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (btn.disabled) return;

        setProcesando(true);

        const formData = new FormData(form);
        formData.append('ajax', '1');

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (data.success) {
                Toastify({
                    text: '✓ ¡Pedido confirmado! Redirigiendo...',
                    duration: 2000,
                    gravity: 'bottom',
                    position: 'right',
                    style: { background: '#10b981', borderRadius: '12px', padding: '12px 20px' }
                }).showToast();
                setTimeout(() => { window.location.href = data.redirect_url; }, 1200);
            } else {
                setProcesando(false);
                const errores = data.errores || ['Ocurrió un error al procesar el pedido'];
                errores.forEach(err => {
                    Toastify({
                        text: '✗ ' + err,
                        duration: 4000,
                        gravity: 'bottom',
                        position: 'right',
                        style: { background: '#ef4444', borderRadius: '12px', padding: '12px 20px' }
                    }).showToast();
                });
            }
        } catch (err) {
            setProcesando(false);
            Toastify({
                text: '✗ Error de conexión. Intente nuevamente.',
                duration: 3500,
                gravity: 'bottom',
                position: 'right',
                style: { background: '#ef4444', borderRadius: '12px', padding: '12px 20px' }
            }).showToast();
        }
    });
})();
</script>
</body>
</html>