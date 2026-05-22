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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Placa *</label>
                        <input type="text" name="placa" id="inputPlaca" required class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none font-bold text-navy-blue" placeholder="ABC-123">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">ID Cliente (Cédula/RUC) *</label>
                        <input type="text" name="cliente_id" required placeholder="ID del Propietario" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none">
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
                        <select name="nivel_combustible" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 outline-none">
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
                <div class="mt-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Motivo de Ingreso / Observaciones</label>
                    <textarea name="observaciones_entrada" rows="4" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-neon-green outline-none" placeholder="Describa el problema o el servicio solicitado..."></textarea>
                </div>
            </div>
        </div>

        <!-- Checklist y Guardado -->
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Checklist de Entrada</h3>
                <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                    <?php 
                    $items = ['Llaves', 'Gato/Herramientas', 'Llave de Rueda', 'Repuesto', 'Radio', 'Antena', 'Espejos', 'Encendedor', 'Alfombras'];
                    foreach($items as $item): ?>
                    <div class="flex items-center justify-between p-2 hover:bg-slate-50 rounded-lg border border-transparent hover:border-slate-100 transition-all">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" class="w-4 h-4 text-neon-green rounded border-gray-300 focus:ring-neon-green checklist-item" value="<?php echo $item; ?>">
                            <span class="text-sm text-slate-700"><?php echo $item; ?></span>
                        </label>
                        <input type="text" placeholder="Nota" class="text-[10px] w-24 border-b border-gray-200 focus:border-neon-green outline-none bg-transparent checklist-note">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="w-full bg-neon-green text-navy-blue font-black py-4 rounded-xl shadow-lg hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2 uppercase tracking-widest">
                <i data-lucide="save"></i> Crear Orden de Servicio
            </button>
        </div>
    </form>
</div>

<script>
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
    
    document.querySelectorAll('.checklist-item').forEach((check, index) => {
        if (check.checked) {
            checklist.push({
                item: check.value,
                nota: document.querySelectorAll('.checklist-note')[index].value
            });
        }
    });

    const data = {
        placa: formData.get('placa'),
        cliente_id: formData.get('cliente_id'),
        marca: formData.get('marca'),
        modelo: formData.get('modelo'),
        anio: formData.get('anio'),
        color: formData.get('color'),
        fecha_entrega: formData.get('fecha_entrega'),
        kilometraje: formData.get('kilometraje'),
        nivel_combustible: formData.get('nivel_combustible'),
        observaciones_entrada: formData.get('observaciones_entrada'),
        checklist: checklist
    };

    try {
        const resp = await fetch(`${URLROOT}/taller/guardarOrden`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const res = await resp.json();
        
        if (res.success) {
            AppUtils.showToast(res.mensaje, 'success');
            setTimeout(() => window.location.href = `${URLROOT}/taller`, 1500);
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