<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo s($titulo); ?> - <?php echo SITENAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    body {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .nav-blur {
        backdrop-filter: blur(12px);
        background: rgba(15, 23, 42, 0.9);
    }

    .btn-primary {
        background: #10b981;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }

    .btn-primary:hover {
        background: #059669;
    }

    main { flex: 1 0 auto; }
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-white">CATALOGO</span>
                </a>
                <a href="<?php echo URLROOT; ?>/catalogo"
                    class="text-sm text-gray-400 hover:text-white transition-colors">&larr; Seguir comprando</a>
            </div>
        </div>
    </nav>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">
        <!-- Success message -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">¡Pedido Confirmado!</h1>
            <p class="text-gray-500 mt-2">Tu pedido ha sido registrado exitosamente. Pronto nos pondremos en contacto
                contigo.</p>
        </div>

        <!-- Order details -->
        <?php if ($venta): ?>
        <!-- FORMATO FACTURA -->
        <div class="bg-white rounded-2xl shadow-md overflow-hidden">
            <div class="bg-emerald-600 text-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-80">Factura #<?php echo $venta->id_formateado ?? 'FAC-' . str_pad($venta->id, 3, '0', STR_PAD_LEFT); ?></p>
                        <p class="text-lg font-semibold"><?php echo s($venta->cliente_nombre); ?></p>
                    </div>
                    <span class="bg-emerald-500 px-3 py-1 rounded-full text-sm font-medium"><?php echo s($venta->status); ?></span>
                </div>
            </div>
            <div class="p-6">
                <!-- Customer info -->
                <div class="grid sm:grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Cédula / NIT</p>
                        <p class="font-medium text-gray-800"><?php echo s($venta->cliente_id); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Teléfono</p>
                        <p class="font-medium text-gray-800"><?php echo s($venta->cliente_telefono); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Correo</p>
                        <p class="font-medium text-gray-800"><?php echo s($venta->cliente_email); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Fecha</p>
                        <p class="font-medium text-gray-800"><?php echo date('d/m/Y h:i A', strtotime($venta->fecha)); ?></p>
                    </div>
                    <?php if ($venta->observaciones_factura): ?>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Observaciones</p>
                        <p class="font-medium text-gray-800"><?php echo s($venta->observaciones_factura); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Items -->
                <h3 class="font-semibold text-gray-800 mb-3">Productos</h3>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($detalles as $detalle): ?>
                    <div class="py-3 flex justify-between text-sm">
                        <div>
                            <p class="font-medium text-gray-800"><?php echo s($detalle->descripcion); ?></p>
                            <p class="text-gray-400 text-xs"><?php echo $detalle->cantidad; ?> x $<?php echo number_format($detalle->precio_unitario, 2); ?></p>
                        </div>
                        <span class="font-medium text-gray-800">$<?php echo number_format($detalle->cantidad * $detalle->precio_unitario, 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Totals -->
                <div class="mt-4 pt-4 border-t border-gray-200 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium">$<?php echo number_format($venta->subtotal, 2); ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">IVA (0%) <span class="text-xs text-gray-400">— Deshabilitado</span></span>
                        <span class="font-medium text-gray-400">$0.00</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200">
                        <span>Total</span>
                        <span class="text-emerald-600">$<?php echo number_format($venta->total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif ($pedido): ?>
        <!-- ========== FORMATO PEDIDO LEGACY ========== -->
        <div class="bg-white rounded-2xl shadow-md overflow-hidden">
            <div class="bg-emerald-600 text-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-80">Pedido #<?php echo $pedido->id; ?></p>
                        <p class="text-lg font-semibold"><?php echo s($pedido->nombre_cliente); ?></p>
                    </div>
                    <span class="bg-emerald-500 px-3 py-1 rounded-full text-sm font-medium"><?php echo s($pedido->estado); ?></span>
                </div>
            </div>
            <div class="p-6">
                <!-- Customer info -->
                <div class="grid sm:grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Cédula / NIT</p>
                        <p class="font-medium text-gray-800"><?php echo s($pedido->cedula); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Teléfono</p>
                        <p class="font-medium text-gray-800"><?php echo s($pedido->telefono); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Correo</p>
                        <p class="font-medium text-gray-800"><?php echo s($pedido->correo); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Fecha</p>
                        <p class="font-medium text-gray-800"><?php echo date('d/m/Y h:i A', strtotime($pedido->fecha_pedido)); ?></p>
                    </div>
                    <?php if ($pedido->direccion): ?>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Dirección</p>
                        <p class="font-medium text-gray-800"><?php echo s($pedido->direccion); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Items -->
                <h3 class="font-semibold text-gray-800 mb-3">Productos</h3>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($detalles as $detalle): ?>
                    <div class="py-3 flex justify-between text-sm">
                        <div>
                            <p class="font-medium text-gray-800"><?php echo s($detalle->nombre ?? 'Producto #' . $detalle->producto_id); ?></p>
                            <p class="text-gray-400 text-xs"><?php echo $detalle->cantidad; ?> x $<?php echo number_format($detalle->precio_unitario, 2); ?></p>
                        </div>
                        <span class="font-medium text-gray-800">$<?php echo number_format($detalle->subtotal, 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Totals -->
                <div class="mt-4 pt-4 border-t border-gray-200 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium">$<?php echo number_format($pedido->subtotal, 2); ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">IVA (0%) <span class="text-xs text-gray-400">— Deshabilitado</span></span>
                        <span class="font-medium text-gray-400">$0.00</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200">
                        <span>Total</span>
                        <span class="text-emerald-600">$<?php echo number_format($pedido->total, 2); ?></span>
                    </div>
                </div>

                <?php if ($pedido->notas): ?>
                <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Notas del pedido</p>
                    <p class="text-sm text-gray-700"><?php echo s($pedido->notas); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="text-center mt-8">
            <a href="<?php echo URLROOT; ?>/catalogo"
                class="btn-primary inline-block px-8 py-3 text-base font-semibold">
                Seguir Comprando
            </a>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-gray-400 py-8 mt-12 w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-sm">&copy; <?php echo date('Y'); ?> <?php echo SITENAME; ?>. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <?php if (!empty($whatsapp_warning)): ?>
    <!-- Toastify CSS/JS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
    Toastify({
        text: "<?php echo addslashes($whatsapp_warning); ?>",
        duration: 6000,
        gravity: "top",
        position: "right",
        style: {
            background: "#f59e0b",
            color: "#1e293b",
            borderRadius: "12px",
            fontWeight: "700",
            fontSize: "13px",
            boxShadow: "0 0 20px rgba(245, 158, 11, 0.4)",
            border: "1px solid rgba(245, 158, 11, 0.3)",
            textTransform: "none"
        },
    }).showToast();
    </script>
    <?php endif; ?>
</body>

</html>