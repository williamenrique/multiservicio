<div class="container mx-auto p-6">
    <h2 class="text-3xl font-extrabold text-navy-blue tracking-tight mb-6"><?php echo $data['titulo']; ?></h2>
    <p class="text-gray-400 mt-1 mb-8">Administra la información general y fiscal de tu taller.</p>

   <div class="w-full max-w-2xl">
        <div class="glass-card p-8 rounded-xl">
            <form id="formConfigEmpresa" class="space-y-4">
                <!-- Sección del Logo -->
                <div class="flex flex-col items-center mb-6 p-4 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Logo Distintivo del Taller</p>
                    <div class="relative group">
                        <img id="logo-preview" 
                             src="<?php echo !empty($data['config']->logo) ? URLROOT . '/' . $data['config']->logo : URL_IMG . 'default.png'; ?>" 
                             alt="Vista previa" 
                             class="w-32 h-32 object-contain rounded-lg shadow-md bg-white p-2">
                        <label for="logoInput" class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer rounded-lg">
                            <span class="text-white text-[10px] font-bold uppercase">Cambiar</span>
                        </label>
                    </div>
                    <input type="file" name="logo" id="logoInput" class="hidden" accept="image/*">
                    <p class="text-[9px] text-slate-400 mt-2 italic text-center">Formatos sugeridos: PNG o JPG (Fondo transparente preferiblemente)</p>
                </div>

                <!-- Datos Generales -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre del Taller</label>
                    <input type="text" name="name" id="config-name"
                        class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none"
                        value="<?php echo s($data['config']->name ?? ''); ?>" required>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">NIT / Documento</label>
                        <input type="text" name="nit" id="config-nit"
                            class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none"
                            value="<?php echo s($data['config']->nit ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Porcentaje IVA (%)</label>
                        <input type="number" name="iva" id="config-iva" step="0.01"
                            class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none"
                            value="<?php echo s($data['config']->iva ?? 0); ?>" required>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Teléfono de Contacto</label>
                    <input type="text" name="telefono" id="config-telefono"
                        class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none"
                        value="<?php echo s($data['config']->telefono ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Dirección</label>
                    <input type="text" name="direccion" id="config-direccion"
                        class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none"
                        value="<?php echo s($data['config']->direccion ?? ''); ?>">
                </div>
                <button type="submit"
                    class="w-full bg-neon-green text-black font-black py-3 rounded-xl hover:scale-[1.02] uppercase text-xs">
                    Guardar Configuración
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Previsualización local del logo antes de subirlo
document.getElementById('logoInput').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logo-preview').src = e.target.result;
        }
        reader.readAsDataURL(this.files[0]);
    }
});

// Manejo del envío del formulario vía AJAX
document.getElementById('formConfigEmpresa').addEventListener('submit', function(e) {
    e.preventDefault(); 

    const formData = new FormData(this);
    const btnSave = this.querySelector('button[type="submit"]');
    
    // Feedback visual en el botón
    const originalText = btnSave.innerHTML;
    btnSave.disabled = true;
    btnSave.innerHTML = '<i class="animate-spin inline-block mr-2" data-lucide="loader-2"></i> Guardando...';
    if(window.lucide) lucide.createIcons();

    fetch(`${URLROOT}/empresa/guardar`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Toastify({
                text: "✅ " + data.mensaje,
                duration: 3000,
                gravity: "top",
                position: "right",
                style: {
                    background: "#39FF14",
                    color: "#0b1120",
                    fontWeight: "bold",
                    borderRadius: "10px"
                }
            }).showToast();

            // Actualizar el logo en la vista previa y opcionalmente en el sidebar si existe
            if (data.new_logo_url) {
                const newPath = `${URLROOT}/${data.new_logo_url}`;
                document.getElementById('logo-preview').src = newPath;
                // Si tienes un logo en el sidebar, puedes actualizarlo aquí también
                const sidebarLogo = document.querySelector('aside img');
                if(sidebarLogo) sidebarLogo.src = newPath;
            }
        } else {
            throw new Error(data.mensaje || 'Error desconocido');
        }
    })
    .catch(error => {
        Toastify({
            text: "❌ " + error.message,
            duration: 4000,
            style: { background: "#ff4444" }
        }).showToast();
    })
    .finally(() => {
        btnSave.disabled = false;
        btnSave.innerHTML = originalText;
        if(window.lucide) lucide.createIcons();
    });
});
</script>