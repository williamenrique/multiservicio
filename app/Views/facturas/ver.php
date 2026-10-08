<div class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Detalle completo de la factura.</p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.history.back()" class="bg-slate-100 text-slate-600 font-bold px-6 py-3 rounded-xl hover:bg-slate-200 uppercase text-xs flex items-center gap-2 transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver
            </button>
            <button onclick="imprimirFactura()" class="bg-green-600 text-white font-bold px-6 py-3 rounded-xl hover:bg-green-700 uppercase text-xs flex items-center gap-2 transition-all">
                <i data-lucide="printer" class="w-4 h-4"></i> Imprimir PDF
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Info Principal -->
        <div class="lg:col-span-2 glass-card rounded-2xl p-6 shadow-xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">N° Factura</p>
                    <p class="font-mono font-bold text-2xl text-navy-blue"><?php echo $data['factura']->id_formateado; ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Fecha</p>
                    <p class="font-bold text-slate-700">
                        <?php $fecha = new DateTime($data['factura']->fecha); echo $fecha->format('d/m/Y H:i:s'); ?>
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Estado</p>
                    <p class="font-bold text-slate-700">
                        <?php 
                            $status = $data['factura']->status;
                            $badgeClass = ''; $icon = '';
                            switch ($status) {
                                case 'COMPLETADO': $badgeClass = 'bg-green-100 text-green-800'; $icon = 'check-circle'; break;
                                case 'CREDITO': $badgeClass = 'bg-amber-100 text-amber-800'; $icon = 'clock'; break;
                                case 'ANULADO': $badgeClass = 'bg-red-100 text-red-800'; $icon = 'x-circle'; break;
                                default: $badgeClass = 'bg-slate-100 text-slate-800'; $icon = 'help-circle';
                            }
                        ?>
                        <span id="estadoBadge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?php echo $badgeClass; ?>">
                            <i data-lucide="<?php echo $icon; ?>" class="w-3 h-3 mr-1"></i><?php echo $status; ?>
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Tipo / Procedencia</p>
                    <p class="font-bold text-slate-700">
                        <?php 
                            $origen = $data['factura']->origen ?? '';
                            $tipo = $data['factura']->tipo_procedencia;
                            $badgeClass = ''; $icon = '';
                            if ($origen === 'PRESUPUESTO') { $badgeClass = 'bg-violet-100 text-violet-800'; $icon = 'file-text'; $tipo = 'PRESUPUESTO'; }
                            else {
                                switch ($tipo) {
                                    case 'OS': $badgeClass = 'bg-blue-100 text-blue-800'; $icon = 'clipboard-list'; break;
                                    case 'TALLER': $badgeClass = 'bg-purple-100 text-purple-800'; $icon = 'wrench'; break;
                                    case 'MOSTRADOR': $badgeClass = 'bg-green-100 text-green-800'; $icon = 'shopping-cart'; break;
                                    case 'GARANTIA': $badgeClass = 'bg-indigo-100 text-indigo-800'; $icon = 'shield-check'; break;
                                    default: $badgeClass = 'bg-slate-100 text-slate-800'; $icon = 'help-circle';
                                }
                            }
                        ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?php echo $badgeClass; ?>">
                            <i data-lucide="<?php echo $icon; ?>" class="w-3 h-3 mr-1"></i><?php echo $tipo; ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Cliente y Vehículo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 bg-slate-50 rounded-xl border border-slate-100">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Cliente</p>
                    <p class="font-bold text-slate-700 text-lg"><?php echo s($data['factura']->cliente_nombre ?? 'Consumidor Final'); ?></p>
                    <?php if (!empty($data['factura']->cliente_telefono)): ?>
                    <p class="text-sm text-slate-500"><i data-lucide="phone" class="w-4 h-4 inline mr-1"></i><?php echo s($data['factura']->cliente_telefono); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($data['factura']->cliente_email)): ?>
                    <p class="text-sm text-slate-500"><i data-lucide="mail" class="w-4 h-4 inline mr-1"></i><?php echo s($data['factura']->cliente_email); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Vehículo</p>
                    <?php if (!empty($data['factura']->placa)): ?>
                    <p class="font-bold text-slate-700 text-lg"><?php echo s($data['factura']->placa); ?></p>
                    <?php if (!empty($data['factura']->marca_vehiculo)): ?>
                    <p class="text-sm text-slate-500"><?php echo s($data['factura']->marca_vehiculo); ?> <?php echo s($data['factura']->modelo_vehiculo ?? ''); ?></p>
                    <?php endif; ?>
                    <?php else: ?>
                    <p class="text-slate-400 italic">Sin vehículo asociado</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vendedor y Mecánico -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 bg-slate-50 rounded-xl border border-slate-100">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Vendedor / Cajero</p>
                    <p class="font-bold text-slate-700"><?php echo s($data['factura']->vendedor_nombre ?? 'SISTEMA'); ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Mecánico Responsable</p>
                    <p class="font-bold text-slate-700"><?php echo s($data['factura']->mecanico_nombre ?? 'N/A'); ?></p>
                </div>
            </div>

            <!-- Observaciones -->
            <?php if (!empty($data['factura']->observaciones) || !empty($data['factura']->diagnostico_entrada) || !empty($data['factura']->diagnostico_salida)): ?>
            <div class="space-y-4">
                <?php if (!empty($data['factura']->diagnostico_entrada)): ?>
                <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl">
                    <p class="text-[10px] font-bold text-blue-800 uppercase mb-2">Diagnóstico de Entrada</p>
                    <p class="text-slate-700 whitespace-pre-wrap"><?php echo s($data['factura']->diagnostico_entrada); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($data['factura']->observaciones)): ?>
                <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl">
                    <p class="text-[10px] font-bold text-amber-800 uppercase mb-2">Observaciones</p>
                    <p class="text-slate-700 whitespace-pre-wrap"><?php echo s($data['factura']->observaciones); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Resumen Financiero -->
        <div class="glass-card rounded-2xl p-6 shadow-xl sticky top-24 h-fit">
            <h3 class="text-lg font-bold text-navy-blue uppercase tracking-wider mb-4 border-b border-slate-100 pb-3 flex items-center gap-2">
                <i data-lucide="calculator" class="w-5 h-5"></i> Resumen Financiero
            </h3>
            
            <div class="space-y-3 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Subtotal</span>
                    <span class="font-bold text-slate-700" id="montoSubtotal"><?php echo number_format($data['factura']->subtotal, 0, ',', '.'); ?></span>
                </div>
                <?php if ($data['factura']->iva_monto > 0): ?>
                <div class="flex justify-between text-sm" id="rowIva">
                    <span class="text-slate-500">IVA (19%)</span>
                    <span class="font-bold text-slate-700" id="montoIva"><?php echo number_format($data['factura']->iva_monto, 0, ',', '.'); ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between text-lg font-bold text-navy-blue border-t border-slate-100 pt-3">
                    <span>TOTAL</span>
                    <span id="montoTotal"><?php echo number_format($data['factura']->total, 0, ',', '.'); ?></span>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4 space-y-3">
                <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Forma de Pago</p>
                <div class="space-y-2" id="formasPago">
                    <div class="flex justify-between text-sm" id="rowEfectivo" style="<?php echo $data['factura']->pago_efectivo > 0 ? '' : 'display:none;'; ?>">
                        <span class="text-slate-500 flex items-center gap-2"><i data-lucide="dollar-sign" class="w-4 h-4"></i> Efectivo</span>
                        <span class="font-bold text-green-700" id="montoEfectivo"><?php echo number_format($data['factura']->pago_efectivo, 0, ',', '.'); ?></span>
                    </div>
                    <div class="flex justify-between text-sm" id="rowTransferencia" style="<?php echo $data['factura']->pago_transferencia > 0 ? '' : 'display:none;'; ?>">
                        <span class="text-slate-500 flex items-center gap-2"><i data-lucide="credit-card" class="w-4 h-4"></i> Transferencia</span>
                        <span class="font-bold text-blue-700" id="montoTransferencia"><?php echo number_format($data['factura']->pago_transferencia, 0, ',', '.'); ?></span>
                    </div>
                    <div class="flex justify-between text-sm text-red-600 font-bold" id="rowSaldo" style="<?php echo ($data['factura']->saldo_pendiente ?? 0) > 0 ? '' : 'display:none;'; ?>">
                        <span class="flex items-center gap-2"><i data-lucide="alert-triangle" class="w-4 h-4"></i> Saldo Pendiente</span>
                        <span id="montoSaldo"><?php echo number_format($data['factura']->saldo_pendiente ?? 0, 0, ',', '.'); ?></span>
                    </div>
                </div>

                <?php if (($data['factura']->saldo_pendiente ?? 0) > 0.05 && $data['factura']->status === 'CREDITO'): ?>
                <div class="mt-4 pt-4 border-t border-slate-100 space-y-3"
                     id="abonoForm"
                     data-factura-id="<?php echo (int)$data['factura']->id; ?>"
                     data-saldo="<?php echo (float)$data['factura']->saldo_pendiente; ?>"
                     data-pago-efectivo="<?php echo (float)$data['factura']->pago_efectivo; ?>"
                     data-pago-transferencia="<?php echo (float)$data['factura']->pago_transferencia; ?>"
                     data-total="<?php echo (float)$data['factura']->total; ?>">

                    <p class="text-[10px] font-bold text-gray-400 uppercase flex items-center gap-2">
                        <i data-lucide="hand-coins" class="w-4 h-4"></i> Registrar Abono
                    </p>

                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Monto</label>
                            <input type="number" id="abonoMonto" min="0.01" step="0.01"
                                   max="<?php echo (float)$data['factura']->saldo_pendiente; ?>"
                                   placeholder="0.00"
                                   class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-navy-blue text-sm focus:ring-2 focus:ring-emerald-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Método</label>
                            <select id="abonoMetodo" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-sm text-slate-700 focus:ring-2 focus:ring-emerald-400 outline-none">
                                <option value="EFECTIVO">EFECTIVO</option>
                                <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                            </select>
                        </div>
                    </div>

                    <p id="abonoError" class="hidden text-[11px] text-rose-600 font-bold"></p>

                    <button type="button" id="btnRegistrarAbono" disabled
                            class="w-full bg-emerald-500 hover:bg-emerald-600 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-black py-3 rounded-xl uppercase text-xs flex items-center justify-center gap-2 transition-all">
                        <i data-lucide="hand-coins" class="w-4 h-4"></i> Registrar Abono
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MEJORA 3: Historial de Abonos -->
    <?php if (!empty($data['factura']->abonos)): ?>
    <div class="glass-card rounded-2xl overflow-hidden shadow-xl mb-6">
        <div class="p-6 border-b border-slate-100">
            <h3 class="text-lg font-bold text-navy-blue uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="history" class="w-5 h-5"></i> Historial de Abonos (<?php echo count($data['factura']->abonos); ?>)
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                        <th class="px-6 py-4">Fecha</th>
                        <th class="px-6 py-4">Método</th>
                        <th class="px-6 py-4">Registrado por</th>
                        <th class="px-6 py-4 text-right">Monto</th>
                        <th class="px-6 py-4 text-right">Recibo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                    <?php foreach ($data['factura']->abonos as $abono): ?>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4"><?php echo date('d/m/Y H:i', strtotime($abono->fecha)); ?></td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?php echo $abono->metodo_pago === 'EFECTIVO' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo $abono->metodo_pago; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4"><?php echo s($abono->usuario_nombre ?? 'SISTEMA'); ?></td>
                        <td class="px-6 py-4 text-right font-black text-emerald-600">
                            $<?php echo number_format($abono->monto, 0, ',', '.'); ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="<?php echo URLROOT; ?>/facturacion/imprimirReciboAbono/<?php echo $abono->id; ?>" 
                               target="_blank"
                               class="inline-flex items-center gap-1 text-blue-600 hover:bg-blue-50 px-2 py-1 rounded-lg transition-all">
                                <i data-lucide="printer" class="w-4 h-4"></i> PDF
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Items de la Factura -->
    <div class="glass-card rounded-2xl overflow-hidden shadow-xl">
        <div class="p-6 border-b border-slate-100">
            <h3 class="text-lg font-bold text-navy-blue uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="list" class="w-5 h-5"></i> Detalle de Items (<?php echo count($data['factura']->items ?? []); ?>)
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                        <th class="px-6 py-4">#</th>
                        <th class="px-6 py-4">Descripción</th>
                        <th class="px-6 py-4 text-center">Tipo</th>
                        <th class="px-6 py-4 text-center">Cant.</th>
                        <th class="px-6 py-4 text-right">Precio Unit.</th>
                        <th class="px-6 py-4 text-right">Subtotal</th>
                        <th class="px-6 py-4 text-center">Mecánico</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                    <?php if (!empty($data['factura']->items)): ?>
                        <?php $index = 1; foreach ($data['factura']->items as $item): ?>
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4 text-center font-mono text-slate-400"><?php echo $index++; ?></td>
                            <td class="px-6 py-4 font-medium text-slate-700"><?php echo s($item->descripcion); ?></td>
                            <td class="px-6 py-4 text-center">
                                <?php $tipo = $item->producto_id ? 'PRODUCTO' : 'SERVICIO';
                                      $badgeClass = $tipo === 'PRODUCTO' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800';
                                      $icon = $tipo === 'PRODUCTO' ? 'package' : 'wrench'; ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $badgeClass; ?>">
                                    <i data-lucide="<?php echo $icon; ?>" class="w-3 h-3 mr-1"></i><?php echo $tipo; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-navy-blue"><?php echo (int)$item->cantidad; ?></td>
                            <td class="px-6 py-4 text-right font-mono"><?php echo number_format($item->precio_unitario, 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 text-right font-bold text-navy-blue"><?php echo number_format($item->precio_unitario * $item->cantidad, 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 text-center text-slate-500"><?php echo s($item->mecanico_nombre ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="px-8 py-16 text-center text-slate-400 italic">No hay items en esta factura</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function imprimirFactura() {
        window.open('<?php echo URLROOT; ?>/facturas/imprimir/<?php echo $data['factura']->id; ?>', '_blank');
    }

    function fmtNum(n) {
        return new Intl.NumberFormat('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n || 0);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('abonoForm');
        if (!form) { if (window.lucide) lucide.createIcons(); return; }

        const facturaId = parseInt(form.dataset.facturaId, 10);
        let   pagoEfectivo = parseFloat(form.dataset.pagoEfectivo) || 0;
        let   pagoTransfer = parseFloat(form.dataset.pagoTransferencia) || 0;
        let   saldoActual  = parseFloat(form.dataset.saldo) || 0;
        const totalFactura = parseFloat(form.dataset.total) || 0;

        const inputMonto = document.getElementById('abonoMonto');
        const selectMetodo = document.getElementById('abonoMetodo');
        const btnAbono = document.getElementById('btnRegistrarAbono');
        const errorMsg = document.getElementById('abonoError');

        function setError(msg) {
            if (!msg) {
                errorMsg.classList.add('hidden'); errorMsg.textContent = '';
                inputMonto.classList.remove('border-rose-400');
            } else {
                errorMsg.textContent = msg; errorMsg.classList.remove('hidden');
                inputMonto.classList.add('border-rose-400');
            }
        }

        function validarMonto() {
            const valor = parseFloat(inputMonto.value);
            const esNumero = !isNaN(valor);
            const mayorCero = esNumero && valor > 0;
            const menorIgualSaldo = esNumero && valor <= (saldoActual + 0.001);
            const valido = esNumero && mayorCero && menorIgualSaldo;
            btnAbono.disabled = !valido;

            if (!inputMonto.value) setError('');
            else if (!esNumero || !mayorCero) setError('El monto debe ser mayor a 0.');
            else if (!menorIgualSaldo) setError('El monto no puede superar el saldo (' + fmtNum(saldoActual) + ').');
            else setError('');
            return valido;
        }

        function setLoading(on) {
            if (on) {
                btnAbono.disabled = true;
                btnAbono.dataset.originalHtml = btnAbono.innerHTML;
                btnAbono.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Procesando...';
                if (window.lucide) lucide.createIcons();
            } else {
                if (btnAbono.dataset.originalHtml) btnAbono.innerHTML = btnAbono.dataset.originalHtml;
                if (window.lucide) lucide.createIcons();
            }
        }

        function refrescarResumen(monto, metodo) {
            if (metodo === 'EFECTIVO') pagoEfectivo += monto; else pagoTransfer += monto;
            saldoActual = Math.max(0, totalFactura - pagoEfectivo - pagoTransfer);
            form.dataset.pagoEfectivo = pagoEfectivo;
            form.dataset.pagoTransferencia = pagoTransfer;
            form.dataset.saldo = saldoActual;

            if (pagoEfectivo > 0) {
                document.getElementById('montoEfectivo').textContent = fmtNum(pagoEfectivo);
                document.getElementById('rowEfectivo').style.display = '';
            }
            if (pagoTransfer > 0) {
                document.getElementById('montoTransferencia').textContent = fmtNum(pagoTransfer);
                document.getElementById('rowTransferencia').style.display = '';
            }
            if (saldoActual > 0.05) {
                document.getElementById('montoSaldo').textContent = fmtNum(saldoActual);
                document.getElementById('rowSaldo').style.display = '';
            } else {
                document.getElementById('rowSaldo').style.display = 'none';
                const badge = document.getElementById('estadoBadge');
                if (badge) {
                    badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800';
                    badge.innerHTML = '<i data-lucide="check-circle" class="w-3 h-3 mr-1"></i>COMPLETADO';
                }
                form.style.display = 'none';
            }
            if (saldoActual > 0.05) {
                inputMonto.max = saldoActual.toFixed(2);
                inputMonto.value = '';
                validarMonto();
            }
            if (window.lucide) lucide.createIcons();
        }

        inputMonto.addEventListener('input', validarMonto);
        selectMetodo.addEventListener('change', validarMonto);

        btnAbono.addEventListener('click', async () => {
            if (!validarMonto()) return;
            const monto = parseFloat(inputMonto.value);
            const metodo = selectMetodo.value;
            setLoading(true);

            try {
                const res = await fetch(`${URLROOT}/facturacion/registrarAbono`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({ venta_id: facturaId, monto, metodo })
                });
                const data = await res.json();
                setLoading(false);

                if (data.success) {
                    AppUtils.showToast(data.mensaje || 'Pago registrado');
                    refrescarResumen(monto, metodo);
                    // Recargar la página tras un pequeño delay para ver el historial actualizado
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    AppUtils.showToast(data.mensaje || 'Error', 'error');
                    validarMonto();
                }
            } catch (e) {
                setLoading(false); validarMonto();
                console.error(e);
                AppUtils.showToast('Error de conexión', 'error');
            }
        });

        validarMonto();
        if (window.lucide) lucide.createIcons();
    });
</script>