<div class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Gestión centralizada de la base de datos de clientes.</p>
        </div>
        <button id="btnOpenModal" class="bg-neon-green text-black font-black px-6 py-3 rounded-xl flex items-center gap-2 transition-all transform hover:scale-[1.02] active:scale-95 shadow-lg shadow-neon-green/20 uppercase tracking-widest text-xs">
            <i data-lucide="user-plus" class="w-5 h-5"></i>
            NUEVO CLIENTE
        </button>
    </div>

    <!-- Buscador con estilo similar al Login -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="md:col-span-2 relative">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500"></i>
            <input type="text" id="searchClient" placeholder="Buscar por nombre, identificación o teléfono..." 
                class="w-full bg-[#0b1120]/40 border border-white/10 rounded-xl py-3 pl-12 pr-4 text-white placeholder-gray-500 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all shadow-xl backdrop-blur-sm">
        </div>
        <div class="flex items-center justify-between text-gray-400 text-sm bg-[#0b1120]/40 border border-white/10 rounded-xl px-4 py-3 shadow-xl backdrop-blur-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4 text-neon-green"></i>
                <span>Clientes:</span>
            </div>
            <strong id="totalCount" class="text-white text-lg">0</strong>
        </div>
    </div>

    <!-- Tabla con Glassmorphism consistente -->
    <div class="bg-[#0b1120]/40 border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/5 text-gray-400 text-[10px] font-black uppercase tracking-widest border-b border-white/10">
                        <th class="px-6 py-5">ID / Identificación</th>
                        <th class="px-6 py-5">Nombre Completo</th>
                        <th class="px-6 py-5">Contacto</th>
                        <th class="px-6 py-5">Ubicación</th>
                        <th class="px-6 py-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-white/10 text-sm text-gray-400">
                    <tr id="loadingRow">
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">Cargando base de datos...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Registro -->
<div id="clientModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-[#0b1120] border border-white/10 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden shadow-neon-green/5">
        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-white/5">
            <h2 id="modalTitle" class="text-xl font-bold text-white uppercase tracking-wider">Registrar Cliente</h2>
            <button id="btnCloseModal" class="text-gray-500 hover:text-white transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <form id="formCliente" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">ID Fiscal / Cédula</label>
                    <input type="text" name="id" id="clientId" required placeholder="Ej: V-123456"
                        class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Teléfono</label>
                    <input type="text" name="telefono" id="clientPhone" required placeholder="0412..."
                        class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Nombre o Razón Social</label>
                <input type="text" name="nombre" id="clientName" required placeholder="Nombre completo"
                    class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all uppercase">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Email</label>
                <input type="email" name="email" id="clientEmail" placeholder="correo@ejemplo.com"
                    class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Dirección Corta</label>
                <textarea name="direccion" id="clientAddress" rows="2" placeholder="Ciudad o dirección específica"
                    class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all resize-none"></textarea>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" id="btnCancel" class="flex-1 bg-white/10 text-white font-bold py-3 rounded-xl hover:bg-white/20 transition-all uppercase text-xs tracking-widest">
                    Cancelar
                </button>
                <button type="submit" id="btnSave" class="flex-1 bg-neon-green text-black font-black py-3 rounded-xl hover:scale-[1.02] active:scale-95 transition-all uppercase text-xs tracking-widest">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Inyección de Scripts -->
<script src="<?php echo URLROOT; ?>/js/clientes.js"></script>