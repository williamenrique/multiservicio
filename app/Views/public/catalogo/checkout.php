<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo s($titulo); ?> - <?php echo SITENAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>const URLROOT = "<?php echo URLROOT; ?>";</script>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .nav-blur { backdrop-filter: blur(12px); background: rgba(15, 23, 42, 0.9); }
        .btn-primary { background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; transition: all 0.2s; }
        .btn-primary:hover { background: #059669; }
        .input-field { width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 0.75rem; transition: all 0.2s; }
        .input-field:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15); }
        .input-error { border-color: #ef4444; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

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

    <main class="flex-1 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
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
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
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
                        <button type="submit" id="btnConfirmarPedido" class="btn-primary w-full py-3 mt-6 text-base font-semibold flex items-center justify-center gap-2" disabled>
                            <span id="btnText">Confirmar Pedido</span>
                            <svg id="btnSpinner" class="animate-spin h-5 w-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
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
                                    <?php if (!empty($item['en_oferta']) && $item['en_oferta']): ?>
                                    <div class="flex flex-col items-start gap-0.5 mt-0.5">
                                        <span class="text-gray-400 line-through text-xs"><?php echo $item['cantidad']; ?> x $<?php echo number_format($item['precio_original'], 2); ?></span>
                                        <span class="text-xs font-bold text-amber-600"><?php echo $item['cantidad']; ?> x $<?php echo number_format($item['precio'], 2); ?></span>
                                        <span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-bold uppercase">Oferta <?php echo (int)$item['oferta_porcentaje']; ?>% OFF</span>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-gray-400 text-xs"><?php echo $item['cantidad']; ?> x $<?php echo number_format($item['precio'], 2); ?></p>
                                    <?php endif; ?>
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
    <footer class="bg-slate-900 text-gray-400 py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-sm">&copy; <?php echo date('Y'); ?> <?php echo SITENAME; ?>. Todos los derechos reservados.</p>
        </div>
    </footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('checkoutForm');
    const btn = document.getElementById('btnConfirmarPedido');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    
    // Habilitar el botón si el formulario es válido
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    
    function checkFormValidity() {
        let isValid = true;
        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
            }
        });
        
        // Validar email
        const emailInput = form.querySelector('input[name="correo"]');
        if (emailInput && emailInput.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value)) {
                isValid = false;
            }
        }
        
        btn.disabled = !isValid;
        btn.classList.toggle('opacity-50', !isValid);
        btn.classList.toggle('cursor-not-allowed', !isValid);
    }
    
    // Verificar al cargar y en cada input
    checkFormValidity();
    inputs.forEach(input => {
        input.addEventListener('input', checkFormValidity);
        input.addEventListener('change', checkFormValidity);
    });
    
    // Manejar envío del formulario
    form.addEventListener('submit', function(e) {
        // Validar una vez más antes de enviar
        if (!form.checkValidity()) {
            return;
        }
        
        // Deshabilitar botón y mostrar spinner
        btn.disabled = true;
        btnText.textContent = 'Procesando...';
        btnSpinner.classList.remove('hidden');
        
        // El formulario se envía normalmente (POST)
        // Si hay error, el servidor redirigirá de vuelta y el botón se restaurará
    });
});
</script>

</body>
</html>