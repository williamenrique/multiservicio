<script>
    // Definir URLROOT solo si no ha sido definida por el header para evitar SyntaxError
    if (typeof URLROOT === 'undefined') {
        window.URLROOT = '<?php echo URLROOT; ?>';
    }
</script>

<div class="space-y-6">
    <div class="bg-navy-blue p-6 rounded-xl border border-gray-800 shadow-lg flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                <i data-lucide="file-plus" class="text-neon-green"></i> Registro de Ingreso
            </h2>
            <p class="text-gray-400">Complete los datos para generar la nueva orden de servicio.</p>
        </div>
        <a href="<?php echo URLROOT; ?>/taller" class="text-gray-400 hover:text-white transition-colors">
            <i data-lucide="x-circle" class="w-8 h-8"></i>
        </a>
    </div>

    <form id="formNuevaOrden" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Información General -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Información del Vehículo</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Placa *</label>
                        <input type="text" name="placa" id="inputPlaca" required class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none font-bold text-navy-blue" placeholder="ABC-123">
                    </div>
                    <div class="relative">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Identificación Cliente *</label>
                        <input type="text" name="cliente_id" id="cliente_id" required placeholder="Cédula o NIT" autocomplete="off" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none">
                        <div id="cliente_results" class="absolute w-full mt-1 max-h-60 overflow-y-auto hidden border border-slate-200 rounded-xl shadow-2xl bg-white z-[100] py-1"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre del Cliente</label>
                        <input type="text" id="cliente_nombre" readonly class="w-full bg-slate-100 border border-gray-200 rounded-lg px-4 py-2 outline-none font-bold text-navy-blue italic" placeholder="Ingrese ID para buscar...">
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
                        <input type="text" name="marca" required placeholder="Ej: Toyota" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Modelo *</label>
                        <input type="text" name="modelo" required placeholder="Ej: Corolla" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Año / Color</label>
                        <div class="flex gap-2">
                            <input type="number" name="anio" placeholder="Año" class="w-1/2 bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none">
                            <input type="text" name="color" placeholder="Color" class="w-1/2 bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none">
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
