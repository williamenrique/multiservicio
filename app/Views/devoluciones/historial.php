<section id="sec-devoluciones-historial" class="content-section">
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-16 h-16 rounded-full bg-navy-blue/10 flex items-center justify-center mb-4">
            <i data-lucide="rotate-ccw" class="w-8 h-8 text-navy-blue"></i>
        </div>
        <h1 class="text-xl font-black text-slate-800 uppercase mb-2">Vista Integrada</h1>
        <p class="text-slate-500 text-sm max-w-md mb-6">El historial de devoluciones se ha integrado en la vista principal de devoluciones como una pestaña. Serás redirigido automáticamente.</p>
        <a href="<?php echo URLROOT; ?>/devoluciones" class="flex items-center gap-2 bg-navy-blue text-neon-green px-6 py-3 rounded-xl text-xs font-black uppercase shadow-md hover:scale-105 transition-all">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Ir a Devoluciones
        </a>
    </div>
</section>

<script>
    // Redirección automática tras 1.5s por si alguien accede directo
    setTimeout(function() {
        window.location.href = '<?php echo URLROOT; ?>/devoluciones';
    }, 1500);
    if (window.lucide) lucide.createIcons();
</script>
