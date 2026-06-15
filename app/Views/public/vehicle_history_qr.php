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
            background: radial-gradient(circle at top right, #0f172a, #020617);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .glass-card {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        .text-neon-green { color: #39FF14; }
        .bg-neon-green { background-color: #39FF14; }
        .text-navy-blue { color: #0f172a; }
        .bg-navy-blue { background-color: #0f172a; }
    </style>
</head>
<body class="font-sans">

    <div class="w-full max-w-4xl mx-auto glass-card rounded-3xl shadow-2xl p-8 space-y-6">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-navy-blue rounded-2xl border border-gray-800 mb-4 shadow-lg">
                <i data-lucide="wrench" class="text-neon-green w-8 h-8"></i>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-wider">TALLER<span class="text-neon-green">PRO</span></h1>
            <p class="text-gray-400 text-sm mt-2">Historial de Servicio Vehicular</p>
        </div>

        <!-- Botón para mostrar el QR (Útil para que el mecánico lo muestre al cliente) -->
        <div class="flex justify-center mb-6">
            <button onclick="toggleModal('qrModal')" class="flex items-center gap-2 bg-gray-800 hover:bg-gray-700 text-white px-5 py-2.5 rounded-2xl text-xs font-black transition-all border border-gray-700 shadow-xl group">
                <i data-lucide="qr-code" class="w-4 h-4 text-neon-green group-hover:scale-110 transition-transform"></i> MOSTRAR QR DE ACCESO
            </button>
        </div>

        <?php if ($vehiculo): ?>
            <div class="bg-navy-blue p-6 rounded-xl border border-gray-800 shadow-lg text-center">
                <h2 class="text-4xl font-black text-white tracking-tighter mb-1"><?php echo s($vehiculo->placa); ?></h2>
                <span class="bg-neon-green text-navy-blue text-[10px] font-black px-3 py-1 rounded-full uppercase"><?php echo s($vehiculo->marca); ?></span>
                <p class="text-gray-400 text-sm mt-2"><?php echo s($vehiculo->modelo); ?> — <?php echo s($vehiculo->anio ?? 'N/A'); ?> — <?php echo s($vehiculo->color); ?></p>
            </div>

            <div class="space-y-4">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i data-lucide="clock-3" class="text-neon-green w-5 h-5"></i> Línea de Tiempo de Servicios
                </h3>

                <?php if(empty($historial)): ?>
                    <div class="bg-gray-800 p-12 rounded-3xl text-center border border-dashed border-gray-700">
                        <i data-lucide="layers" class="w-12 h-12 text-gray-700 mx-auto mb-4"></i>
                        <p class="text-gray-500 font-bold uppercase text-xs">No hay registros históricos para mostrar</p>
                    </div>
                <?php endif; ?>

                <?php foreach($historial as $h): 
                    $statusColors = [
                        'RECIBIDO' => 'bg-indigo-600',
                        'DIAGNOSTICANDO' => 'bg-amber-600',
                        'EN_REPARACION' => 'bg-blue-600',
                        'LISTO' => 'bg-emerald-600',
                        'ENTREGADO' => 'bg-neon-green',
                        'CANCELADO' => 'bg-rose-600'
                    ];
                    $bgStatus = $statusColors[$h->estado] ?? 'bg-gray-600';
                ?>
                <div class="bg-gray-900 p-5 rounded-2xl border border-gray-800 shadow-md space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-black text-white bg-gray-700 px-3 py-1 rounded-lg border border-gray-600">ORDEN #<?php echo s($h->id); ?></span>
                        <span class="text-[10px] font-black uppercase px-3 py-1 rounded-lg <?php echo $bgStatus; ?> text-navy-blue tracking-tighter">
                            <?php echo s($h->estado); ?>
                        </span>
                        <span class="text-[10px] text-gray-400 font-bold"><i data-lucide="calendar" class="w-3 h-3 inline"></i> <?php echo date('d M, Y', strtotime($h->fecha_ingreso)); ?></span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-2 border-y border-gray-700">
                        <div>
                            <p class="text-[9px] font-black text-gray-500 uppercase">Kilometraje</p>
                            <p class="text-xs font-bold text-white"><?php echo is_numeric($h->kilometraje) ? number_format($h->kilometraje) : s($h->kilometraje); ?> KM</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-gray-500 uppercase">Técnico</p>
                            <p class="text-xs font-bold text-white uppercase"><?php echo s($h->mecanico_nombre) ?: 'Sin asignar'; ?></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[9px] font-black text-gray-500 uppercase">Diagnóstico / Motivo</p>
                            <p class="text-xs text-gray-300 italic leading-snug">"<?php echo s($h->diagnostico_entrada); ?>"</p>
                        </div>
                    </div>

                    <?php if(!empty($h->checklist_data)): ?>
                    <div class="pt-3 border-t border-gray-800">
                        <p class="text-[9px] font-black text-gray-500 uppercase mb-2">Checklist de Entrada</p>
                        <div class="flex flex-wrap gap-1">
                            <?php foreach($h->checklist_data as $chk): ?>
                                <span class="text-[9px] px-2 py-0.5 rounded-full border border-gray-700 bg-gray-800 text-gray-400 flex items-center gap-1">
                                    <i data-lucide="<?php echo $chk->estado == 1 ? 'check-circle' : 'circle'; ?>" class="w-2.5 h-2.5 <?php echo $chk->estado == 1 ? 'text-emerald-500' : 'text-gray-500'; ?>"></i>
                                    <?php echo s($chk->item); ?> <?php echo !empty($chk->observacion) ? "(".s($chk->observacion).")" : ""; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-gray-800 p-12 rounded-3xl text-center border border-dashed border-gray-700">
                <i data-lucide="car-off" class="w-16 h-16 text-gray-700 mx-auto mb-4"></i>
                <p class="text-gray-500 font-bold uppercase text-sm">Vehículo no encontrado o placa inválida.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal del Código QR -->
    <div id="qrModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md">
        <div class="glass-card w-full max-w-sm rounded-3xl p-10 border border-gray-700 shadow-2xl text-center relative animate-in fade-in zoom-in duration-300">
            <button onclick="toggleModal('qrModal')" class="absolute top-6 right-6 text-gray-500 hover:text-white transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            
            <h3 class="text-2xl font-black text-white uppercase tracking-tighter mb-2">Código QR</h3>
            <p class="text-[10px] text-gray-500 uppercase font-black tracking-[0.2em] mb-8">Historial: <span class="text-neon-green"><?php echo s($vehiculo->placa); ?></span></p>
            
            <div class="bg-white p-5 rounded-3xl inline-block shadow-2xl mb-8">
                <!-- Llamada al controlador para generar y mostrar la imagen -->
                <img src="<?php echo URLROOT; ?>/public/generateVehicleQr/<?php echo $vehiculo->placa; ?>" 
                     alt="QR Historial" class="w-52 h-52 mx-auto"
                     onerror="this.src='https://placehold.co/200x200?text=Error+QR'; console.error('Error al cargar la imagen QR. Verifique extensión GD en PHP y carpeta Vendor.');">
            </div>
            
            <p class="text-xs text-gray-400 font-bold uppercase tracking-tight leading-relaxed">
                Escanee este código para ver el historial<br>público del vehículo.
            </p>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function toggleModal(id) {
            document.getElementById(id).classList.toggle('hidden');
        }
    </script>
</body>
</html>