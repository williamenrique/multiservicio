<div class="p-6 space-y-6">
    <!-- Encabezado con estadísticas rápidas o título -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase"><?php echo $data['titulo']; ?></h1>
            <p class="text-slate-500 text-sm">Control de recuperación de usuario y/o clave.</p>
        </div>
    </div>

    <!-- Contenedor para la lista de solicitudes -->
    <div id="recovery-list-container" class="space-y-4 max-w-4xl mx-auto mt-8">
        <!-- Se carga dinámicamente desde app.js -> cargarTablaRecuperacion() -->
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof cargarTablaRecuperacion === 'function') cargarTablaRecuperacion();
    });
</script>