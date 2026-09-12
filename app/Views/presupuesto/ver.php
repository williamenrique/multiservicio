<?php if (!defined('URLROOT')) exit('No direct script access allowed'); ?>
<div class="container mx-auto p-6 space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <a href="<?php echo URLROOT; ?>/presupuesto" class="text-slate-500 hover:text-navy-blue transition-colors flex items-center gap-2 text-sm font-semibold mb-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Volver a Presupuestos
            </a>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($presupuesto->estado === 'BORRADOR'): ?>
            <button onclick="editarPresupuesto(<?php echo $presupuesto->id; ?>)" class="bg-slate-600 text-white px-5 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-slate-700 text-sm font-semibold shadow-sm">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Editar
            </button>
            <button onclick="enviarPresupuestoEmail(<?php echo $presupuesto->id; ?>)" class="bg-blue-600 text-white px-5 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-blue-700 text-sm font-semibold shadow-sm">
                <i data-lucide="mail" class="w-4 h-4"></i> Enviar
            </button>
            <?php endif; ?>
            <button onclick="generarPDFPresupuesto(<?php echo $presupuesto->id; ?>)" class="bg-purple-600 text-white px-5 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-purple-700 text-sm font-semibold shadow-sm">
                <i data-lucide="file-text" class="w-4 h-4"></i> PDF
            </button>
            <?php if ($presupuesto->estado === 'BORRADOR'): ?>
            <button onclick="guardarYEnviarPresupuesto(<?php echo $presupuesto->id; ?>)" class="bg-neon-green text-navy-blue px-5 py-3 rounded-xl flex items-center gap-2 transition-all hover:brightness-110 text-sm font-semibold shadow-lg shadow-neon-green/20">
                <i data-lucide="send" class="w-4 h-4"></i> Guardar y Enviar
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Header del Presupuesto -->
    <div class="bg-gradient-to-r from-navy-blue to-slate-800 text-white rounded-3xl p-6 sm:p-8 shadow-xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="bg-white/20 backdrop-blur-sm px-4 py-1.5 rounded-full text-sm font-black uppercase tracking-wider">
                        <?php echo s($presupuesto->numero); ?>
                    </span>
                    <span class="px-3 py-1.5 rounded-full text-sm font-black uppercase tracking-wider <?php 
                        $estadoColors = [
                            'BORRADOR' => 'bg-slate-200 text-slate-700',
                            'ENVIADO' => 'bg-amber-200 text-amber-800',
                            'ACEPTADO' => 'bg-emerald-200 text-emerald-800',
                            'RECHAZADO' => 'bg-red-200 text-red-800',
                            'EXPIRADO' => 'bg-red-200 text-red-800',
                            'CONVERTIDO' => 'bg-purple-200 text-purple-800'
                        ];
                        echo $estadoColors[$presupuesto->estado] ?? 'bg-slate-200 text-slate-700';
                    ?>">
                        <?php echo s($presupuesto->estado); ?>
                    </span>
                    <?php 
                    $vencido = $presupuesto->fecha_vencimiento && new DateTime($presupuesto->fecha_vencimiento) < new DateTime() && $presupuesto->estado === 'ENVIADO';
                    if ($vencido): ?>
                    <span class="px-3 py-1.5 rounded-full text-sm font-black uppercase tracking-wider bg-red-200 text-red-800">VENCIDO</span>
                    <?php endif; ?>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight mb-2">Presupuesto / Cotización</h2>
                <p class="text-slate-200 text-sm">Creado el <?php echo date('d/m/Y', strtotime($presupuesto->fecha_creacion)); ?> por <?php echo s($presupuesto->usuario_nombre ?? 'Sistema'); ?></p>
            </div>
            <div class="text-right md:text-left">
                <div class="text-4xl font-extrabold text-neon-green">$<?php echo number_format($presupuesto->total, 2, ',', '.'); ?></div>
                <p class="text-slate-300 text-sm mt-1">Total con IVA</p>
            </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-white/20">
            <div>
                <p class="text-[10px] font-black text-slate-300 uppercase tracking-wider">Fecha Emisión</p>
                <p class="font-bold"><?php echo date('d/m/Y', strtotime($presupuesto->fecha_emision)); ?></p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-300 uppercase tracking-wider">Vencimiento</p>
                <p class="font-bold <?php echo $vencido ? 'text-red-300' : ''; ?>">
                    <?php echo $presupuesto->fecha_vencimiento ? date('d/m/Y', strtotime($presupuesto->fecha_vencimiento)) : 'N/A'; ?>
                    <?php if ($vencido): ?><span class="ml-1 text-red-300 text-xs">(VENCIDO)</span><?php endif; ?>
                </p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-300 uppercase tracking-wider">Validez</p>
                <p class="font-bold"><?php echo $presupuesto->validez_dias; ?> días</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-300 uppercase tracking-wider">IVA</p>
                <p class="font-bold"><?php echo $presupuesto->iva_activo ? $presupuesto->tasa_iva . '%' : 'No aplica'; ?></p>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Cliente y Vehículo -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Cliente -->
            <div class="glass-card rounded-2xl p-6 shadow-xl border border-slate-100">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4 text-neon-green"></i> Cliente
                </h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Nombre</p>
                        <p class="font-bold text-slate-700 text-lg"><?php echo s($presupuesto->cliente_nombre); ?></p>
                    </div>
                    <?php if ($presupuesto->cliente_cedula): ?>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Cédula / NIT</p>
                        <p class="font-mono text-slate-600"><?php echo s($presupuesto->cliente_cedula); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($presupuesto->cliente_telefono): ?>
                    <div class="flex items-center gap-2">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Teléfono</p>
                        <a href="tel:<?php echo s($presupuesto->cliente_telefono); ?>" class="font-semibold text-slate-600 hover:text-neon-green transition-colors"><?php echo s($presupuesto->cliente_telefono); ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if ($presupuesto->cliente_email): ?>
                    <div class="flex items-center gap-2">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Email</p>
                        <a href="mailto:<?php echo s($presupuesto->cliente_email); ?>" class="font-semibold text-slate-600 hover:text-neon-green transition-colors"><?php echo s($presupuesto->cliente_email); ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if ($presupuesto->cliente_direccion): ?>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Dirección</p>
                        <p class="text-slate-600"><?php echo s($presupuesto->cliente_direccion); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vehículo -->
            <?php if ($presupuesto->vehiculo_placa || $presupuesto->vehiculo_marca || $presupuesto->vehiculo_modelo): ?>
            <div class="glass-card rounded-2xl p-6 shadow-xl border border-slate-100">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="car" class="w-4 h-4 text-neon-green"></i> Vehículo
                </h3>
                <div class="space-y-3">
                    <?php if ($presupuesto->vehiculo_placa): ?>
                    <div class="flex items-center gap-2">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Placa</p>
                        <p class="font-bold text-slate-700 text-lg font-mono"><?php echo s($presupuesto->vehiculo_placa); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($presupuesto->vehiculo_marca || $presupuesto->vehiculo_modelo): ?>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Marca / Modelo</p>
                        <p class="font-bold text-slate-700"><?php echo s(trim($presupuesto->vehiculo_marca . ' ' . $presupuesto->vehiculo_modelo)); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($presupuesto->vehiculo_anio): ?>
                    <div class="flex items-center gap-2">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Año</p>
                        <p class="font-semibold text-slate-600"><?php echo $presupuesto->vehiculo_anio; ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($presupuesto->vehiculo_color): ?>
                    <div class="flex items-center gap-2">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Color</p>
                        <p class="font-semibold text-slate-600 capitalize"><?php echo s($presupuesto->vehiculo_color); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Info del Presupuesto -->
            <div class="glass-card rounded-2xl p-6 shadow-xl border border-slate-100">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-neon-green"></i> Detalles del Presupuesto
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Estado</span>
                        <span class="font-bold px-3 py-1 rounded-full text-xs uppercase tracking-wider <?php 
                            $estadoColors = [
                                'BORRADOR' => 'bg-slate-100 text-slate-700',
                                'ENVIADO' => 'bg-amber-100 text-amber-800',
                                'ACEPTADO' => 'bg-emerald-100 text-emerald-800',
                                'RECHAZADO' => 'bg-red-100 text-red-800',
                                'EXPIRADO' => 'bg-red-100 text-red-800',
                                'CONVERTIDO' => 'bg-purple-100 text-purple-800'
                            ];
                            echo $estadoColors[$presupuesto->estado] ?? 'bg-slate-100 text-slate-700';
                        ?>">
                            <?php echo s($presupuesto->estado); ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Fecha Emisión</span>
                        <span class="font-bold"><?php echo date('d/m/Y', strtotime($presupuesto->fecha_emision)); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Vencimiento</span>
                        <span class="font-bold <?php echo $vencido ? 'text-red-600' : ''; ?>">
                            <?php echo $presupuesto->fecha_vencimiento ? date('d/m/Y', strtotime($presupuesto->fecha_vencimiento)) : 'N/A'; ?>
                            <?php if ($vencido): ?><span class="text-red-500 ml-1">(VENCIDO)</span><?php endif; ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Validez</span>
                        <span class="font-bold"><?php echo $presupuesto->validez_dias; ?> días</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">IVA</span>
                        <span class="font-bold"><?php echo $presupuesto->iva_activo ? $presupuesto->tasa_iva . '%' : 'No aplica'; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Creado por</span>
                        <span class="font-bold"><?php echo s($presupuesto->usuario_nombre ?? 'Sistema'); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Fecha Creación</span>
                        <span class="font-bold"><?php echo date('d/m/Y H:i', strtotime($presupuesto->fecha_creacion)); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items y Totales -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Items -->
            <div class="glass-card rounded-2xl overflow-hidden shadow-xl border border-slate-100">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="package" class="w-4 h-4 text-neon-green"></i> Items del Presupuesto
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                                <th class="px-6 py-4">#</th>
                                <th class="px-6 py-4">Tipo</th>
                                <th class="px-6 py-4">Descripción</th>
                                <th class="px-6 py-4 text-center">Cant.</th>
                                <th class="px-6 py-4 text-right">P. Unitario</th>
                                <th class="px-6 py-4 text-center">Desc. %</th>
                                <th class="px-6 py-4 text-right">Subtotal</th>
                                <th class="px-6 py-4 text-center">IVA %</th>
                                <th class="px-6 py-4 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($detalles as $index => $detalle): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-center text-xs font-bold text-slate-500"><?php echo $index + 1; ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase <?php echo $detalle->tipo_item === 'SERVICIO' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'; ?>">
                                        <?php echo s($detalle->tipo_item); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-700"><?php echo s($detalle->descripcion); ?></p>
                                    <?php if ($detalle->notas): ?>
                                    <p class="text-[10px] text-slate-400 italic mt-1"><?php echo s($detalle->notas); ?></p>
                                    <?php endif; ?>
                                    <?php if ($detalle->producto_id): ?>
                                    <p class="text-[10px] text-slate-400 font-mono">Ref: <?php echo s($detalle->producto_codigo ?? 'ID:' . $detalle->producto_id); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-slate-700"><?php echo $detalle->cantidad; ?></td>
                                <td class="px-6 py-4 text-right font-bold text-slate-700">$<?php echo number_format($detalle->precio_unitario, 2, ',', '.'); ?></td>
                                <td class="px-6 py-4 text-center text-xs font-bold text-slate-600"><?php echo $detalle->descuento_porcentaje > 0 ? $detalle->descuento_porcentaje . '%' : '-'; ?></td>
                                <td class="px-6 py-4 text-right font-bold text-slate-700">$<?php echo number_format($detalle->subtotal, 2, ',', '.'); ?></td>
                                <td class="px-6 py-4 text-center text-xs font-bold text-slate-600"><?php echo $detalle->iva_porcentaje > 0 ? $detalle->iva_porcentaje . '%' : '-'; ?></td>
                                <td class="px-6 py-4 text-right font-extrabold text-navy-blue">$<?php echo number_format($detalle->total, 2, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totales -->
            <div class="glass-card rounded-2xl overflow-hidden shadow-xl border border-slate-100">
                <div class="p-6 bg-gradient-to-r from-navy-blue to-slate-800 text-white rounded-t-2xl">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="text-center">
                            <p class="text-[10px] font-black uppercase tracking-wider opacity-70">Subtotal</p>
                            <p class="text-2xl font-extrabold">$<?php echo number_format($presupuesto->subtotal, 2, ',', '.'); ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] font-black uppercase tracking-wider opacity-70">Descuentos</p>
                            <p class="text-2xl font-extrabold">-$<?php echo number_format(array_sum(array_column($detalles, 'descuento_monto')), 2, ',', '.'); ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] font-black uppercase tracking-wider opacity-70">IVA (<?php echo $presupuesto->tasa_iva; ?>%)</p>
                            <p class="text-2xl font-extrabold">$<?php echo number_format($presupuesto->iva_monto, 2, ',', '.'); ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] font-black uppercase tracking-wider opacity-70">Total</p>
                            <p class="text-3xl font-extrabold text-neon-green">$<?php echo number_format($presupuesto->total, 2, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-slate-50/50 rounded-b-2xl">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php if ($presupuesto->observaciones): ?>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Observaciones</p>
                            <p class="text-slate-600"><?php echo s($presupuesto->observaciones); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($presupuesto->condiciones): ?>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Términos y Condiciones</p>
                            <p class="text-slate-600"><?php echo s($presupuesto->condiciones); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="flex flex-wrap gap-3 justify-end">
                <?php if ($presupuesto->estado === 'BORRADOR'): ?>
                <button onclick="editarPresupuesto(<?php echo $presupuesto->id; ?>)" class="bg-slate-600 text-white px-6 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-slate-700 font-semibold shadow-sm">
                    <i data-lucide="edit-3" class="w-5 h-5"></i> Editar
                </button>
                <button onclick="enviarPresupuestoEmail(<?php echo $presupuesto->id; ?>)" class="bg-blue-600 text-white px-6 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-blue-700 font-semibold shadow-sm">
                    <i data-lucide="mail" class="w-5 h-5"></i> Enviar por Email
                </button>
                <button onclick="guardarYEnviarPresupuesto(<?php echo $presupuesto->id; ?>)" class="bg-neon-green text-navy-blue px-6 py-3 rounded-xl flex items-center gap-2 transition-all hover:brightness-110 font-semibold shadow-lg shadow-neon-green/20">
                    <i data-lucide="send" class="w-5 h-5"></i> Guardar y Enviar
                </button>
                <?php endif; ?>
                <button onclick="generarPDFPresupuesto(<?php echo $presupuesto->id; ?>)" class="bg-purple-600 text-white px-6 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-purple-700 font-semibold shadow-sm">
                    <i data-lucide="download" class="w-5 h-5"></i> Descargar PDF
                </button>
                <button onclick="imprimirPresupuesto(<?php echo $presupuesto->id; ?>)" class="bg-slate-600 text-white px-6 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-slate-700 font-semibold shadow-sm">
                    <i data-lucide="printer" class="w-5 h-5"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/* ==================== ACCIONES ==================== */
function editarPresupuesto(id) {
    window.location.href = `${URLROOT}/presupuesto/editar/${id}`;
}

async function enviarPresupuestoEmail(id) {
    const { value: formValues } = await Swal.fire({
        title: 'Enviar Presupuesto por Email',
        html: `
            <div class="text-left space-y-4 pt-2">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Email Destinatario *</label>
                    <input type="email" id="email-destinatario" class="swal2-input w-full m-0 text-sm" placeholder="cliente@ejemplo.com" required>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Nombre Destinatario</label>
                    <input type="text" id="email-nombre" class="swal2-input w-full m-0 text-sm" placeholder="Nombre del cliente">
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'ENVIAR',
        confirmButtonColor: '#3b82f6',
        preConfirm: () => {
            const email = document.getElementById('email-destinatario').value;
            const nombre = document.getElementById('email-nombre').value;
            if (!email) {
                Swal.showValidationMessage('El email es obligatorio');
                return false;
            }
            return { email, nombre };
        }
    });
    
    if (formValues) {
        AppUtils.showLoading('Enviando...');
        try {
            const res = await fetch(`${URLROOT}/presupuesto/enviarEmail/${<?php echo $presupuesto->id; ?>}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({
                    destinatario_email: formValues.email,
                    destinatario_nombre: formValues.nombre
                })
            });
            const result = await res.json();
            AppUtils.hideLoading();
            
            if (result.success) {
                AppUtils.showToast(result.mensaje, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                AppUtils.showToast(result.mensaje || 'Error al enviar', 'error');
            }
        } catch (e) {
            AppUtils.hideLoading();
            AppUtils.showToast('Error de conexión', 'error');
        }
    }
}

async function generarPDFPresupuesto(id) {
    try {
        AppUtils.showLoading('Generando PDF...');
        const res = await fetch(`${URLROOT}/presupuesto/pdf/${id}`);
        const result = await res.json();
        AppUtils.hideLoading();
        
        if (result.success) {
            window.open(result.pdf_url, '_blank');
        } else {
            AppUtils.showToast(result.mensaje || 'Error al generar PDF', 'error');
        }
    } catch (e) {
        AppUtils.hideLoading();
        AppUtils.showToast('Error de conexión', 'error');
    }
}

function imprimirPresupuesto(id) {
    window.open(`${URLROOT}/presupuesto/imprimir/${id}`, '_blank');
}

async function guardarYEnviarPresupuesto(id) {
    const { value: formValues } = await Swal.fire({
        title: 'Guardar y Enviar Presupuesto',
        html: `
            <div class="text-left space-y-4 pt-2">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Email Destinatario *</label>
                    <input type="email" id="email-destinatario" class="swal2-input w-full m-0 text-sm" placeholder="cliente@ejemplo.com" required>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Nombre Destinatario</label>
                    <input type="text" id="email-nombre" class="swal2-input w-full m-0 text-sm" placeholder="Nombre del cliente">
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'GUARDAR Y ENVIAR',
        confirmButtonColor: '#10b981',
        preConfirm: () => {
            const email = document.getElementById('email-destinatario').value;
            const nombre = document.getElementById('email-nombre').value;
            if (!email) {
                Swal.showValidationMessage('El email es obligatorio');
                return false;
            }
            return { email, nombre };
        }
    });
    
    if (formValues) {
        AppUtils.showLoading('Guardando y enviando...');
        try {
            // Primero cambiar estado a ENVIADO
            const resEstado = await fetch(`${URLROOT}/presupuesto/cambiarEstado/${<?php echo $presupuesto->id; ?>}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ estado: 'ENVIADO' })
            });
            const resultEstado = await resEstado.json();
            
            if (!resultEstado.success) throw new Error(resultEstado.mensaje);
            
            // Luego enviar email
            const resEmail = await fetch(`${URLROOT}/presupuesto/enviarEmail/${<?php echo $presupuesto->id; ?>}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({
                    destinatario_email: formValues.email,
                    destinatario_nombre: formValues.nombre
                })
            });
            const resultEmail = await resEmail.json();
            AppUtils.hideLoading();
            
            if (resultEmail.success) {
                AppUtils.showToast('Presupuesto guardado y enviado', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                AppUtils.showToast(resultEmail.mensaje || 'Error al enviar', 'error');
            }
        } catch (e) {
            AppUtils.hideLoading();
            AppUtils.showToast('Error de conexión', 'error');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});
</script>