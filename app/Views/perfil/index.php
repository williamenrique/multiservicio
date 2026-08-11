<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold">Configuración de Perfil</h1>
            <p class="text-gray-400">Gestiona tu información personal y credenciales de acceso</p>
        </div>
    </div>

    <form id="formPerfil" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Columna Izquierda: Fotos y Status -->
        <div class="md:col-span-1 space-y-6">
            <div class="glass-card p-6 rounded-2xl border border-gray-800">
                <p class="text-[10px] font-bold text-gray-500 uppercase mb-4 text-center">Gestión de Imágenes</p>
                <div class="space-y-8">
                    <!-- Foto de Usuario (Avatar) -->
                    <div class="text-center">
                        <div class="relative w-32 h-32 mx-auto mb-2 group">
                            <img id="imgPreview" 
                                 src="<?php echo !empty($usuario->foto) ? URLROOT . '/' . $usuario->foto : URL_IMG . 'default.png'; ?>" 
                                 class="w-full h-full object-cover rounded-full border-2 border-neon-green shadow-lg shadow-neon-green/20"
                                 alt="Avatar">
                            <label for="foto" class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer rounded-full">
                                <i data-lucide="camera" class="text-white w-6 h-6"></i>
                            </label>
                            <input type="file" id="foto" name="foto" class="hidden" accept="image/*">
                        </div>
                        <p class="text-[10px] font-bold text-neon-green uppercase tracking-wider">Foto de Perfil</p>
                    </div>

                    <!-- Foto de Staff (Identificación/Frente) -->
                    <div class="text-center">
                        <div class="relative w-full h-40 mx-auto mb-2 group">
                            <img id="imgFrentePreview" 
                                 src="<?php echo !empty($usuario->foto_frente) ? URLROOT . '/' . $usuario->foto_frente : URL_IMG . 'default.png'; ?>" 
                                 class="w-full h-full object-cover rounded-xl border border-gray-700 shadow-sm"
                                 alt="Foto Personal">
                            <label for="foto_frente" class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer rounded-xl">
                                <i data-lucide="upload-cloud" class="text-white w-8 h-8"></i>
                            </label>
                            <input type="file" id="foto_frente" name="foto_frente" class="hidden" accept="image/*">
                        </div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Foto Identificación (Frente)</p>
                    </div>
                </div>

                <hr class="my-6 border-gray-800">
                
                <div class="text-center">
                    <h2 class="text-xl font-bold text-white uppercase"><?php echo s($usuario->username); ?></h2>
                    <p class="text-neon-green text-xs font-bold px-3 py-1 bg-neon-green/10 rounded-full inline-block mt-1 uppercase">
                        <?php echo s($usuario->nombre_rol); ?>
                    </p>
                </div>
            </div>

            <div class="glass-card p-4 rounded-xl border border-gray-800 text-center">
                <i data-lucide="info" class="inline-block w-3 h-3 mr-1"></i>
                <span class="italic text-[10px] text-gray-500">El usuario y rol son gestionados por administración.</span>
            </div>
        </div>

        <!-- Columna Derecha: Formulario -->
        <div class="md:col-span-2 space-y-6">
            <div class="glass-card p-8 rounded-2xl border border-gray-800">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <i data-lucide="user" class="text-neon-green w-5 h-5"></i> Información Personal
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nombre Completo</label>
                        <input type="text" name="nombre" value="<?php echo s($usuario->nombre); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-lg p-3 text-white focus:border-neon-green transition-colors uppercase" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Correo Electrónico</label>
                        <input type="email" name="email" value="<?php echo s($usuario->email); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-lg p-3 text-white focus:border-neon-green transition-colors" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Teléfono</label>
                        <input type="text" name="telefono" value="<?php echo s($usuario->telefono); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-lg p-3 text-white focus:border-neon-green transition-colors">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Dirección</label>
                        <input type="text" name="direccion" value="<?php echo s($usuario->direccion); ?>" class="w-full bg-gray-900/50 border border-gray-700 rounded-lg p-3 text-white focus:border-neon-green transition-colors uppercase">
                    </div>
                </div>

                <hr class="my-8 border-gray-800">

                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <i data-lucide="lock" class="text-neon-green w-5 h-5"></i> Seguridad
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nueva Contraseña</label>
                        <input type="password" name="new_password" placeholder="Dejar en blanco para no cambiar" class="w-full bg-gray-900/50 border border-gray-700 rounded-lg p-3 text-white focus:border-neon-green transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Confirmar Contraseña</label>
                        <input type="password" name="confirm_password" placeholder="Repita la nueva contraseña" class="w-full bg-gray-900/50 border border-gray-700 rounded-lg p-3 text-white focus:border-neon-green transition-colors">
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="bg-neon-green hover:bg-neon-green/80 text-black font-bold py-3 px-8 rounded-xl transition-all flex items-center gap-2 shadow-lg shadow-neon-green/20">
                        <i data-lucide="save" class="w-5 h-5"></i>
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Cargamos el JS específico de perfil -->
<script src="<?php echo URL_JS; ?>perfil.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
    });
</script>