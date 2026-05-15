<div class="container mx-auto p-6">
    <h2 class="text-3xl font-extrabold text-navy-blue tracking-tight mb-6"><?php echo $data['titulo']; ?></h2>
    <p class="text-gray-400 mt-1 mb-8">Administra la información general y fiscal de tu taller.</p>

    <div class="max-w-2xl">
        <div class="glass-card p-8 rounded-xl">
            <form id="companyForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre del Taller</label>
                    <input type="text" name="name" id="config-name"
                        class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none"
                        value="<?php echo s($data['config']->name ?? ''); ?>" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
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
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Dirección</label>
                    <input type="text" name="address" id="config-address"
                        class="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-neon-green outline-none"
                        value="<?php echo s($data['config']->address ?? ''); ?>">
                </div>
                <button type="submit"
                    class="w-full bg-neon-green text-black font-black py-3 rounded-xl hover:scale-[1.02] uppercase text-xs">
                    Guardar Configuración
                </button>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo URLROOT; ?>/js/empresa.js"></script>