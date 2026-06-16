<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo s($titulo); ?> | Taller Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?php echo URL_CSS; ?>styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
        }
        .text-primary { color: #4f46e5; }
        .bg-primary { background-color: #4f46e5; }
        .text-secondary { color: #7c3aed; }
        .bg-secondary { background-color: #7c3aed; }
        .text-accent { color: #10b981; }
        .bg-accent { background-color: #10b981; }
    </style>
</head>
<body class="font-sans text-gray-800">

    <div class="w-full max-w-5xl mx-auto glass-card rounded-2xl shadow-2xl p-8 space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl mb-4 shadow-lg">
                <i data-lucide="car" class="text-white w-10 h-10"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">TALLER<span class="text-primary">PRO</span></h1>
            <p class="text-gray-600 text-sm mt-2">Historial Completo del Vehículo</p>
        </div>

        <?php if ($vehiculo): ?>
            <!-- Información del Vehículo -->
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-6 rounded-xl border border-indigo-100 shadow-sm">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-center md:text-left">
                        <div class="flex items-center justify-center md:justify-start gap-3 mb-2">
                            <h2 class="text-4xl font-black text-gray-900"><?php echo s($vehiculo->placa); ?></h2>
                            <span class="bg-accent text-white text-xs font-bold px-3 py-1 rounded-full uppercase">
                                <?php echo s($vehiculo->marca); ?>
                            </span>
                        </div>
                        <p class="text-gray-700">
                            <span class="font-medium"><?php echo s($vehiculo->modelo); ?></span> • 
                            <?php echo s($vehiculo->anio ?? 'N/A'); ?> • 
                            <span class="font-medium"><?php echo s($vehiculo->color); ?></span>
                        </p>
                        
                        <?php if(isset($vehiculo->cliente_id) && isset($vehiculo->cliente_nombre)): ?>
                            <div class="mt-4 pt-4 border-t border-indigo-100">
                                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-1">Propietario</p>
                                <p class="text-sm text-gray-800 font-medium"><?php echo s($vehiculo->cliente_nombre); ?></p>
                                <?php if(isset($vehiculo->cliente_telefono)): ?>
                                    <p class="text-xs text-gray-600 mt-1 flex items-center gap-1">
                                        <i data-lucide="phone" class="w-3 h-3"></i> <?php echo s($vehiculo->cliente_telefono); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Botón para administrador -->
                    <?php if(isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ADMINISTRADOR'): ?>
                        <div class="flex-shrink-0">
                            <a href="<?php echo URLROOT; ?>/taller/nueva_orden?placa=<?php echo urlencode($vehiculo->placa); ?>" 
                               class="flex items-center gap-2 bg-primary hover:bg-indigo-700 text-white font-semibold px-5 py-3 rounded-xl transition-all shadow-lg hover:shadow-xl group">
                                <i data-lucide="plus-circle" class="w-5 h-5 group-hover:scale-110 transition-transform"></i> 
                                CREAR NUEVA ORDEN
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historial de Órdenes -->
            <div class="space-y-6">
                <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="history" class="text-primary w-6 h-6"></i> Historial de Servicios
                    </h3>
                    <button onclick="toggleModal('qrModal')" class="flex items-center gap-2 bg-white hover:bg-gray-50 text-primary border border-primary font-medium px-4 py-2 rounded-lg transition-all shadow-sm">
                        <i data-lucide="qr-code" class="w-4 h-4"></i> VER QR
                    </button>
                </div>

                <?php if(empty($historial)): ?>
                    <div class="bg-gray-50 p-12 rounded-xl text-center border border-dashed border-gray-300">
                        <i data-lucide="calendar-x" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                        <p class="text-gray-500 font-semibold">No hay registros históricos para mostrar</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-5">
                        <?php foreach($historial as $h): 
                            $statusColors = [
                                'RECIBIDO' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'DIAGNOSTICANDO' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'EN_REPARACION' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                'LISTO' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'ENTREGADO' => 'bg-green-100 text-green-800 border-green-200',
                                'CANCELADO' => 'bg-rose-100 text-rose-800 border-rose-200'
                            ];
                            $bgStatus = $statusColors[$h->estado] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                        ?>
                        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                            <!-- Cabecera de la Orden -->
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-primary/10 p-2 rounded-lg">
                                        <i data-lucide="file-text" class="text-primary w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900">Orden #<?php echo s($h->id); ?></h4>
                                        <p class="text-xs text-gray-500 flex items-center gap-1">
                                            <i data-lucide="calendar" class="w-3 h-3"></i> 
                                            <?php echo date('d/m/Y', strtotime($h->fecha_ingreso)); ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs font-bold px-3 py-1.5 rounded-full border <?php echo $bgStatus; ?>">
                                    <?php echo s($h->estado); ?>
                                </span>
                            </div>

                            <!-- Información Técnica -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Kilometraje</p>
                                    <p class="font-bold text-gray-800"><?php echo is_numeric($h->kilometraje) ? number_format($h->kilometraje) : s($h->kilometraje); ?> KM</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Técnico</p>
                                    <p class="font-bold text-gray-800"><?php echo s($h->mecanico_nombre) ?: 'Sin asignar'; ?></p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Diagnóstico</p>
                                    <p class="text-sm text-gray-700 italic">"<?php echo s($h->diagnostico_entrada); ?>"</p>
                                </div>
                            </div>

                            <!-- Servicios y Repuestos -->
                            <?php if(!empty($h->items_facturados)): 
                                // Calcular total de la orden
                                $totalOrden = 0;
                                foreach($h->items_facturados as $item) {
                                    $totalOrden += $item->precio_unitario * $item->cantidad;
                                }
                            ?>
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <div class="flex justify-between items-center mb-3">
                                    <h5 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                                        <i data-lucide="package" class="w-4 h-4 text-accent"></i> Servicios y Repuestos
                                    </h5>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-accent">
                                            Total Orden: Bs. <?php echo number_format($totalOrden, 2, ',', '.'); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">Suma de todos los montos</p>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <?php foreach($h->items_facturados as $item): 
                                        $itemTotal = $item->precio_unitario * $item->cantidad;
                                    ?>
                                    <div class="flex justify-between items-center bg-gray-50 hover:bg-gray-100 p-3 rounded-lg transition-colors">
                                        <div>
                                            <p class="font-medium text-gray-800"><?php echo s($item->descripcion); ?></p>
                                            <p class="text-xs text-gray-500">Cantidad: <?php echo s($item->cantidad); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-gray-900">Bs. <?php echo number_format($item->precio_unitario, 2, ',', '.'); ?></p>
                                            <p class="text-xs text-gray-500">Total: Bs. <?php echo number_format($itemTotal, 2, ',', '.'); ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Checklist -->
                            <?php if(!empty($h->checklist_data)): ?>
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <h5 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                    <i data-lucide="check-square" class="w-4 h-4 text-secondary"></i> Checklist de Entrada
                                </h5>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach($h->checklist_data as $chk): ?>
                                        <span class="text-xs px-3 py-1.5 rounded-full border <?php echo $chk->estado == 1 ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-600 border-gray-200'; ?> flex items-center gap-1.5">
                                            <i data-lucide="<?php echo $chk->estado == 1 ? 'check-circle' : 'circle'; ?>" class="w-3 h-3 <?php echo $chk->estado == 1 ? 'text-green-500' : 'text-gray-400'; ?>"></i>
                                            <?php echo s($chk->item); ?>
                                            <?php if(!empty($chk->observacion)): ?>
                                                <span class="text-gray-500">(<?php echo s($chk->observacion); ?>)</span>
                                            <?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Vehículo no encontrado -->
            <div class="bg-gray-50 p-12 rounded-xl text-center border border-dashed border-gray-300">
                <i data-lucide="car-off" class="w-20 h-20 text-gray-400 mx-auto mb-4"></i>
                <p class="text-gray-600 font-semibold text-lg mb-2">Vehículo no encontrado</p>
                <p class="text-gray-500">La placa proporcionada no existe en nuestro sistema.</p>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="pt-6 border-t border-gray-200 text-center">
            <p class="text-sm text-gray-500">© <?php echo date('Y'); ?> Taller Pro. Todos los derechos reservados.</p>
            <p class="text-xs text-gray-400 mt-1">Esta es una vista pública del historial del vehículo.</p>
        </div>
    </div>

    <!-- Modal del QR -->
    <div id="qrModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="bg-white w-full max-w-sm rounded-2xl p-8 border border-gray-200 shadow-2xl text-center relative">
            <button onclick="toggleModal('qrModal')" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            
            <h3 class="text-xl font-bold text-gray-900 mb-2">Código QR</h3>
            <p class="text-sm text-gray-600 mb-6">Escanea para acceder a este historial</p>
            
            <div class="bg-white p-6 rounded-xl border border-gray-200 inline-block shadow-lg mb-6">
                <img src="<?php echo URLROOT; ?>/consultas/generateVehicleQr/<?php echo $vehiculo->placa; ?>" 
                     alt="QR Historial" class="w-48 h-48 mx-auto"
                     onerror="this.src='https://placehold.co/200x200?text=Error+QR';">
            </div>
            
            <p class="text-xs text-gray-500 font-medium">Placa: <span class="font-bold text-primary"><?php echo s($vehiculo->placa); ?></span></p>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function toggleModal(id) {
            document.getElementById(id).classList.toggle('hidden');
        }

        // Cerrar modal al hacer click fuera
        document.addEventListener('click', (e) => {
            const modal = document.getElementById('qrModal');
            if (modal && !modal.contains(e.target) && e.target.closest('[onclick*="toggleModal"]') === null) {
                modal.classList.add('hidden');
            }
        });
    </script>
</body>
</html>