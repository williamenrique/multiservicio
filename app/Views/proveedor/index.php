<div class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Directorio de aliados y proveedores de insumos.</p>
        </div>
        <button id="btnOpenModal" class="bg-neon-green text-black font-black px-6 py-3 rounded-xl flex items-center gap-2 transition-all transform hover:scale-[1.05] active:scale-95 shadow-lg shadow-neon-green/40 uppercase tracking-widest text-xs">
            <i data-lucide="truck"></i>
            NUEVO PROVEEDOR
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="md:col-span-2 relative">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500"></i>
            <input type="text" id="searchProveedor" placeholder="Buscar por nombre, NIT o correo..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-4 pl-12 pr-4 text-slate-700 placeholder-slate-400 outline-none focus:border-neon-green transition-all shadow-sm">
        </div>
        <div class="flex items-center justify-between text-slate-500 text-sm bg-white border border-slate-200 rounded-xl px-4 py-3 shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="building-2" class="w-4 h-4 text-slate-400"></i>
                <span>Total Proveedores:</span>
            </div>
            <strong id="totalCount" class="text-navy-blue text-lg">0</strong>
        </div>
    </div>

    <div class="glass-card rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                        <th class="px-8 py-6">NIT / ID</th>
                        <th class="px-8 py-6">Razón Social</th>
                        <th class="px-8 py-6">Contacto</th>
                        <th class="px-8 py-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-slate-100 text-sm text-slate-600">
                    <tr id="loadingRow">
                        <td colspan="4" class="px-8 py-16 text-center text-slate-400 italic animate-pulse font-medium">CONECTANDO CON EL SERVIDOR...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="proveedorModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h2 id="modalTitle" class="text-xl font-bold text-navy-blue uppercase tracking-wider">Registrar Proveedor</h2>
            <button id="btnCloseModal" class="text-gray-500 hover:text-navy-blue">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <form id="formProveedor" class="p-6 space-y-4">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">NIT / Identificación</label>
                    <input type="text" name="id" id="provId" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nombre de la Empresa</label>
                    <input type="text" name="nombre" id="provNombre" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all uppercase">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Teléfono</label>
                    <input type="text" name="telefono" id="provTelefono" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Email</label>
                    <input type="email" name="email" id="provEmail" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Dirección Fiscal</label>
                <textarea name="direccion" id="provDireccion" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all resize-none"></textarea>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" id="btnCancel" class="flex-1 bg-slate-100 text-slate-600 font-bold py-3 rounded-xl hover:bg-slate-200 uppercase text-xs">Cancelar</button>
                <button type="submit" class="flex-1 bg-neon-green text-black font-black py-3 rounded-xl hover:scale-[1.02] uppercase text-xs">Guardar Proveedor</button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo URLROOT; ?>/js/proveedores.js"></script>