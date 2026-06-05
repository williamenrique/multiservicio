<div class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Gestión centralizada de la base de datos de clientes.</p>
        </div>
        <button id="btnOpenModal" class="bg-neon-green text-black font-black px-6 py-3 rounded-xl flex items-center gap-2 transition-all transform hover:scale-[1.05] active:scale-95 shadow-lg shadow-neon-green/40 uppercase tracking-widest text-xs <?php echo (s($data['user_role']) !== 'ADMINISTRADOR') ? 'hidden' : ''; ?>">
            <i data-lucide="user-plus" class="w-5 h-5"></i>
            NUEVO CLIENTE
        </button>
    </div>

    <!-- Barra de búsqueda Estilo Claro -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="md:col-span-2 relative">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500"></i>
            <input type="text" id="searchClient" placeholder="Buscar por nombre, identificación o teléfono..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-4 pl-12 pr-4 text-slate-700 placeholder-slate-400 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all shadow-sm">
        </div>
        <div class="flex items-center gap-4">
            <div class="flex-1 flex items-center justify-between text-slate-500 text-sm bg-white border border-slate-200 rounded-xl px-4 py-3 shadow-sm h-full">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-slate-400"></i>
                    <span>Total:</span>
                </div>
                <strong id="totalCount" class="text-navy-blue text-lg">0</strong>
            </div>
            <select id="limitSelector" class="bg-white border border-slate-200 rounded-xl py-3 px-4 text-xs font-bold text-navy-blue outline-none focus:border-neon-green shadow-sm">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <!-- Contenedor de Tabla -->
    <div class="glass-card rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                        <th class="px-8 py-6">ID / Identificación</th>
                        <th class="px-8 py-6">Nombre Completo</th>
                        <th class="px-8 py-6">Contacto</th>
                        <th class="px-8 py-6">Ubicación</th>
                        <th class="px-8 py-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-slate-100 text-sm text-slate-600">
                    <tr id="loadingRow">
                        <td colspan="5" class="px-8 py-16 text-center text-slate-400 italic tracking-widest animate-pulse font-medium">CARGANDO BASE DE DATOS...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pie de Tabla Armonizado -->
        <div class="px-8 py-4 bg-slate-50/50 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Mostrando <span id="startIndex">0</span> - <span id="endIndex">0</span> de <span id="totalItemsDisplay">0</span> clientes registrados
            </div>
            <div class="flex items-center gap-2" id="paginationControls"></div>
        </div>
    </div>
</div>

<!-- Modal de Registro -->
<div id="clientModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h2 id="modalTitle" class="text-xl font-bold text-white uppercase tracking-wider">Registrar Cliente</h2>
            <button id="btnCloseModal" class="text-gray-500 hover:text-white transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <form id="formCliente" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">ID Fiscal / Cédula</label>
                    <input type="text" name="id" id="clientId" required placeholder="Ej: 12345678"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Teléfono</label>
                    <input type="text" name="telefono" id="clientPhone" required placeholder="0412..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Nombre o Razón Social</label>
                <input type="text" name="nombre" id="clientName" required placeholder="Nombre completo"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all uppercase">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Email</label>
                <input type="email" name="email" id="clientEmail" placeholder="correo@ejemplo.com"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Dirección Corta</label>
                <textarea name="direccion" id="clientAddress" rows="2" placeholder="Ciudad o dirección específica"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all resize-none"></textarea>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" id="btnCancel" class="flex-1 bg-slate-100 text-slate-600 font-bold py-3 rounded-xl hover:bg-slate-200 transition-all uppercase text-xs tracking-widest">
                    Cancelar
                </button>
                <button type="submit" id="btnSave" class="flex-1 bg-neon-green text-black font-black py-3 rounded-xl hover:scale-[1.02] active:scale-95 transition-all uppercase text-xs tracking-widest flex items-center justify-center gap-2">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo URLROOT; ?>/js/clientes.js"></script>