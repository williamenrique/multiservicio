<script>
    // Definir URLROOT solo si no ha sido definida por el header para evitar SyntaxError
    if (typeof URLROOT === 'undefined') {
        window.URLROOT = '<?php echo URLROOT; ?>';
    }
</script>

<div class="space-y-6">
    <div class="bg-navy-blue p-6 rounded-xl border border-gray-800 shadow-lg flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4">
            <a href="<?php echo URLROOT; ?>/taller" class="text-gray-400 hover:text-white transition-colors" title="Volver">
                <i data-lucide="arrow-left" class="w-8 h-8"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                    <i data-lucide="file-plus" class="text-neon-green"></i> Registro de Ingreso
                </h2>
                <p class="text-gray-400">Complete los datos para generar la nueva orden de servicio.</p>
            </div>
        </div>
        <div class="flex gap-2 w-full md:w-auto">
            <a href="<?php echo URLROOT; ?>/taller" class="flex-1 md:flex-none justify-center bg-white/10 border border-white/20 text-white font-bold px-4 py-2 rounded-lg hover:bg-white/20 transition-all flex items-center gap-2 uppercase text-[10px]">
                <i data-lucide="layout-dashboard" class="w-4 h-4 text-neon-green"></i> Taller Activo
            </a>
            <a href="<?php echo URLROOT; ?>/taller/cerradas" class="flex-1 sm:flex-none justify-center bg-white border border-slate-200 text-navy-blue font-bold px-4 py-2 rounded-lg hover:bg-slate-50 transition-all flex items-center gap-2 uppercase text-xs">
                <i data-lucide="archive" class="w-4 h-4"></i> Historial
            </a>
            <a href="<?php echo URLROOT; ?>/taller/cerradas" class="flex-1 md:flex-none justify-center bg-white/10 border border-white/20 text-white font-bold px-4 py-2 rounded-lg hover:bg-white/20 transition-all flex items-center gap-2 uppercase text-[10px]">
                <i data-lucide="archive" class="w-4 h-4"></i> Ver Cerradas
            </a>
        </div>
    </div>

    <form id="formNuevaOrden" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Información General -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <?php if(isset($data['vehiculo']) && $data['vehiculo']): ?>
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-xs font-bold text-green-800 uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="car" class="w-4 h-4"></i> VEHÍCULO ENCONTRADO DESDE HISTORIAL QR
                        </p>
                        <p class="text-sm text-green-700 mt-1">Los datos del vehículo han sido pre-cargados automáticamente.</p>
                    </div>
                <?php endif; ?>
                
                <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Información del Vehículo</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="relative">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Placa *</label>
                        <input type="text" name="placa" id="inputPlaca" required class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none font-bold text-navy-blue" placeholder="ABC-123" 
                               value="<?php echo isset($data['vehiculo']) && $data['vehiculo'] ? htmlspecialchars($data['vehiculo']->placa) : ''; ?>">
                        <div id="placa_results" class="absolute w-full mt-1 max-h-60 overflow-y-auto hidden border border-slate-200 rounded-xl shadow-2xl bg-white z-[100] py-1"></div>
                    </div>
                    <div class="relative">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Identificación Cliente *</label>
                        <input type="text" name="cliente_id" id="cliente_id" required placeholder="Cédula o NIT" autocomplete="off" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none"
                               value="<?php echo isset($data['vehiculo']) && $data['vehiculo'] && isset($data['vehiculo']->cliente_id) ? htmlspecialchars($data['vehiculo']->cliente_id) : ''; ?>">
                        <div id="cliente_results" class="absolute w-full mt-1 max-h-60 overflow-y-auto hidden border border-slate-200 rounded-xl shadow-2xl bg-white z-[100] py-1"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre del Cliente</label>
                        <input type="text" id="cliente_nombre" readonly class="w-full bg-slate-100 border border-gray-200 rounded-lg px-4 py-2 outline-none font-bold text-navy-blue italic" placeholder="Ingrese ID para buscar..."
                               value="<?php echo isset($data['cliente']) && $data['cliente'] ? htmlspecialchars($data['cliente']->nombre) : (isset($data['vehiculo']) && $data['vehiculo'] && isset($data['vehiculo']->cliente_nombre) ? htmlspecialchars($data['vehiculo']->cliente_nombre) : ''); ?>">
                        <?php if(isset($data['vehiculo']) && $data['vehiculo'] && isset($data['vehiculo']->cliente_id)): ?>
                            <div class="mt-1">
                                <?php if(isset($data['cliente']) && $data['cliente']): ?>
                                    <span class="text-xs text-green-600 font-medium flex items-center gap-1">
                                        <i data-lucide="check-circle" class="w-3 h-3"></i> Cliente verificado
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-yellow-600 font-medium flex items-center gap-1">
                                        <i data-lucide="alert-circle" class="w-3 h-3"></i> Cliente encontrado en BD
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Mecánico Asignado</label>
                        <select name="mecanico_id" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none font-bold text-navy-blue">
                            <option value="">-- ASIGNAR MÁS TARDE --</option>
                            <?php foreach($data['staff'] as $s): ?>
                                <option value="<?php echo $s->id; ?>"><?php echo $s->nombre; ?> (<?php echo $s->cargo; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Marca *</label>
                        <input type="text" name="marca" required placeholder="Ej: Toyota" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none"
                               value="<?php echo isset($data['vehiculo']) && $data['vehiculo'] ? htmlspecialchars($data['vehiculo']->marca) : ''; ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Modelo *</label>
                        <input type="text" name="modelo" required placeholder="Ej: Corolla" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none"
                               value="<?php echo isset($data['vehiculo']) && $data['vehiculo'] ? htmlspecialchars($data['vehiculo']->modelo) : ''; ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Año / Color</label>
                        <div class="flex gap-2">
                            <input type="number" name="anio" placeholder="Año" class="w-1/2 bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none"
                                   value="<?php echo isset($data['vehiculo']) && $data['vehiculo'] ? htmlspecialchars($data['vehiculo']->anio) : ''; ?>">
                            <input type="text" name="color" placeholder="Color" class="w-1/2 bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none"
                                   value="<?php echo isset($data['vehiculo']) && $data['vehiculo'] ? htmlspecialchars($data['vehiculo']->color) : ''; ?>">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Kilometraje *</label>
                        <input type="number" name="kilometraje" required class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nivel de Combustible</label>
                        <select name="nivel_combustible" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none">
                            <option value="E">Vacío (E)</option>
                            <option value="1/4">1/4</option>
                            <option value="1/2">1/2</option>
                            <option value="3/4">3/4</option>
                            <option value="F">Lleno (F)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Fecha de Entrega Estimada</label>
                        <input type="datetime-local" name="fecha_entrega" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none">
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Motivo de Ingreso / Observaciones</label>
                    <textarea name="observaciones_entrada" rows="4" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none" placeholder="Describa el problema o el servicio solicitado..."></textarea>
                </div>
            </div>
        </div>

        <!-- Checklist y Guardado -->
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="text-lg font-bold text-slate-800">Checklist</h3>
                    <button type="button" onclick="agregarFilaChecklist()" class="text-[10px] bg-navy-blue text-neon-green px-2 py-1 rounded-lg font-black uppercase hover:scale-105 transition-all">
                        + Agregar
                    </button>
                </div>
                <div id="checklist-container" class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                    <!-- Las filas se insertan aquí -->
                    <div class="text-center py-4 text-slate-400 text-xs italic" id="empty-checklist-msg">
                        No hay items agregados
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-neon-green text-navy-blue font-black py-4 rounded-xl shadow-lg hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2 uppercase tracking-widest">
                <i data-lucide="save"></i> Crear Orden de Servicio
            </button>
        </div>
    </form>
</div>

<script src="<?php echo URLROOT; ?>/js/taller_nueva_orden.js"></script>

<script>
// Lógica para auto-completar datos si existen en la BD y sugerir registro
document.addEventListener('DOMContentLoaded', () => {
    const inputPlaca = document.getElementById('inputPlaca');
    const placaResults = document.getElementById('placa_results');
    const inputClienteId = document.getElementById('cliente_id');
    const clienteResults = document.getElementById('cliente_results');
    const inputClienteNombre = document.getElementById('cliente_nombre');
    let searchTimerPlaca;
    let searchTimerCliente;

    // Auto-cargar datos del vehículo cuando la página se carga con placa pre-cargada (desde historial QR)
    if (inputPlaca && inputPlaca.value.trim().length >= 3) {
        // Aplicar estilos visuales para indicar que los datos son pre-cargados
        inputPlaca.classList.add('bg-green-50', 'border-green-300');
        
        // Marcar campos del vehículo como pre-cargados
        document.querySelectorAll('[name="marca"], [name="modelo"], [name="anio"], [name="color"]').forEach(input => {
            if (input.value) {
                input.classList.add('bg-green-50', 'border-green-300');
            }
        });
        
        if (inputClienteNombre && inputClienteNombre.value) {
            inputClienteNombre.classList.add('bg-green-50', 'border-green-300');
            inputClienteId.classList.add('bg-green-50', 'border-green-300');
        }
        
        // Simular el evento blur para buscar el último kilometraje automáticamente
        setTimeout(() => {
            if (inputPlaca.value.trim()) {
                const event = new Event('blur');
                inputPlaca.dispatchEvent(event);
            }
        }, 800);
    }

    // Buscar por Placa
    inputPlaca?.addEventListener('blur', async function() {
        const placa = this.value.trim();
        if (placa.length < 3) {
            // Clear vehicle fields
            document.querySelector('[name="marca"]').value = '';
            document.querySelector('[name="modelo"]').value = '';
            document.querySelector('[name="anio"]').value = '';
            document.querySelector('[name="color"]').value = '';
            // Clear client fields
            inputClienteId.value = '';
            inputClienteNombre.value = '';
            inputClienteNombre.classList.remove('bg-green-50');
            return;
        }

        try {
            const resp = await fetch(`${URLROOT}/taller/obtenerVehiculoPorPlaca/${placa}`);
            const res = await resp.json();
            
            if (res.success && res.data) {
                const v = res.data;
                // Llenar campos del vehículo
                document.querySelector('[name="marca"]').value = v.marca || '';
                document.querySelector('[name="modelo"]').value = v.modelo || '';
                document.querySelector('[name="anio"]').value = v.anio || '';
                document.querySelector('[name="color"]').value = v.color || '';
                
                // Llenar datos del cliente asociado
                if (v.cliente_id) {
                    inputClienteId.value = v.cliente_id;
                    inputClienteNombre.value = v.cliente_nombre || '';
                    inputClienteNombre.classList.add('bg-green-50');
                } else {
                    // If vehicle found but no client_id linked (shouldn't happen if DB is consistent)
                    inputClienteId.value = '';
                    inputClienteNombre.value = '';
                    inputClienteNombre.classList.remove('bg-green-50');
                }

                // NEW: Populate last known mileage
                const inputKilometraje = document.querySelector('[name="kilometraje"]');
                if (inputKilometraje && res.ultimo_kilometraje) {
                    inputKilometraje.value = res.ultimo_kilometraje;
                    AppUtils.showToast(`Vehículo encontrado. Último kilometraje: ${res.ultimo_kilometraje}`, 'success');
                } else {
                    AppUtils.showToast('Vehículo encontrado: Datos cargados', 'success');
                }            } else {
                // Vehicle not found, clear fields and show toast
                document.querySelector('[name="marca"]').value = '';
                document.querySelector('[name="modelo"]').value = '';
                document.querySelector('[name="anio"]').value = '';
                document.querySelector('[name="color"]').value = '';
                inputClienteId.value = '';
                inputClienteNombre.value = '';
                inputClienteNombre.classList.remove('bg-green-50');
                AppUtils.showToast(`Vehículo con placa "${placa}" no encontrado.`, 'warning');
            }
        } catch (e) {
            console.error("Error buscando placa:", e);
            // Clear fields on error
            document.querySelector('[name="marca"]').value = '';
            document.querySelector('[name="modelo"]').value = '';
            document.querySelector('[name="anio"]').value = '';
            document.querySelector('[name="color"]').value = '';
            inputClienteId.value = '';
            inputClienteNombre.value = '';
            inputClienteNombre.classList.remove('bg-green-50');
            AppUtils.showToast('Error de conexión al buscar vehículo.', 'error');
        }
    });

    // Búsqueda en tiempo real para Placas
    inputPlaca?.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
        const term = this.value.trim();

        clearTimeout(searchTimerPlaca);
        if (term.length < 2) {
            placaResults.classList.add('hidden');
            return;
        }

        searchTimerPlaca = setTimeout(async () => {
            try {
                const resp = await fetch(`${URLROOT}/taller/buscar?q=${encodeURIComponent(term)}`);
                const data = await resp.json();
                
                if (data.success && data.results) {
                    const vehicles = data.results.filter(r => r.tipo === 'placa');
                    if (vehicles.length > 0) {
                        placaResults.innerHTML = vehicles.map(v => `
                            <div class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0 flex items-center gap-3 group" onclick="document.getElementById('inputPlaca').value='${v.id}'; document.getElementById('placa_results').classList.add('hidden'); document.getElementById('inputPlaca').dispatchEvent(new Event('blur'));">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-navy-blue group-hover:bg-neon-green transition-colors">
                                    <i data-lucide="truck" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-black text-navy-blue uppercase">${v.title}</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">${v.subtitle}</p>
                                </div>
                            </div>
                        `).join('');
                        placaResults.classList.remove('hidden');
                        if(window.lucide) lucide.createIcons();
                    } else { placaResults.classList.add('hidden'); }
                }
            } catch (e) { console.error("Error searching plates:", e); }
        }, 300);
    });

    // Cerrar resultados al hacer click fuera
    document.addEventListener('click', (e) => {
        if (!placaResults.contains(e.target) && e.target !== inputPlaca) placaResults.classList.add('hidden');
        if (!clienteResults.contains(e.target) && e.target !== inputClienteId) clienteResults.classList.add('hidden');
    });
});

function agregarFilaChecklist() {
    const container = document.getElementById('checklist-container');
    const emptyMsg = document.getElementById('empty-checklist-msg');
    if(emptyMsg) emptyMsg.remove();

    const div = document.createElement('div');
    div.className = "flex flex-col gap-2 p-3 bg-slate-50 rounded-xl border border-slate-100 animate-in slide-in-from-right-2 duration-200";
    div.innerHTML = `
        <div class="flex justify-between items-center">
            <input type="text" placeholder="¿Qué recibe? (Ej: Llaves)" class="text-xs font-black text-navy-blue uppercase bg-transparent outline-none flex-1 checklist-item-name">
            <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
        </div>
        <input type="text" placeholder="Observación o estado..." class="text-[10px] w-full border-b border-slate-200 focus:border-neon-green outline-none bg-transparent checklist-item-note uppercase">
    `;
    container.appendChild(div);
    if(window.lucide) lucide.createIcons();
    
    // Autofocus al input de nombre del item para escritura rápida
    div.querySelector('.checklist-item-name').focus();
    
    // Hacer scroll al final para ver el nuevo item
    container.scrollTop = container.scrollHeight;
}

document.getElementById('formNuevaOrden').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalContent = submitBtn.innerHTML;

    // Bloquear el botón para evitar doble clic
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i data-lucide="loader" class="animate-spin w-5 h-5 mr-2"></i> Procesando...';
    if(window.lucide) lucide.createIcons();

    const formData = new FormData(this);
    const checklist = [];
    
    document.querySelectorAll('#checklist-container > div').forEach((row) => {
        // Validación de seguridad: verificamos que el input exista antes de leer su valor
        const itemInput = row.querySelector('.checklist-item-name');
        if (itemInput && itemInput.value.trim()) {
            const item = itemInput.value.trim();
            const noteInput = row.querySelector('.checklist-item-note');
            checklist.push({
                item: item,
                nota: noteInput ? noteInput.value.trim() : ''
            });
        }
    });

    const data = {
        placa: formData.get('placa'),
        cliente_id: formData.get('cliente_id'),
        mecanico_id: formData.get('mecanico_id'),
        marca: formData.get('marca'),
        modelo: formData.get('modelo'),
        anio: formData.get('anio'),
        color: formData.get('color'),
        fecha_entrega: formData.get('fecha_entrega'),
        kilometraje: formData.get('kilometraje'),
        nivel_combustible: formData.get('nivel_combustible'),
        observaciones_entrada: formData.get('observaciones_entrada'),
        checklist: checklist,
        items: checklist // Sincronizamos con items para que el controlador procese los detalles de la orden
    };

    try {
        const resp = await fetch(`${URLROOT}/taller/guardarOrden`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?php echo $_SESSION['csrf_token']; ?>'
            },
            body: JSON.stringify(data)
        });
        const res = await resp.json();
        
        if (res.success) {
            AppUtils.showToast(res.mensaje, 'success');
            
            // Mejora: Limpiar formulario y resetear interfaz sin redirigir
            this.reset();
            
            // Limpiar checklist
            const container = document.getElementById('checklist-container');
            if(container) container.innerHTML = '<div class="text-center py-4 text-slate-400 text-xs italic" id="empty-checklist-msg">No hay items agregados</div>';
            
            // Resetear estilos de cliente
            const inputNombre = document.getElementById('cliente_nombre');
            if(inputNombre) {
                inputNombre.value = '';
                inputNombre.classList.remove('bg-green-50');
                inputNombre.classList.add('bg-slate-100');
            }

            // Re-habilitar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        } else {
            AppUtils.showToast(res.error || 'Error al guardar', 'error');
            // Re-habilitar el botón en caso de error del servidor
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
            if(window.lucide) lucide.createIcons();
        }
    } catch (err) { 
        AppUtils.showToast('Error de conexión', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalContent;
        if(window.lucide) lucide.createIcons();
    }
});
</script>
