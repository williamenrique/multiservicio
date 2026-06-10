<div class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Gestión de empleados, cargos y datos de contacto.</p>
        </div>
        <button id="btnOpenModal" class="bg-neon-green text-black font-black px-6 py-3 rounded-xl flex items-center gap-2 transition-all transform hover:scale-[1.05] active:scale-95 shadow-lg shadow-neon-green/40 uppercase tracking-widest text-xs">
            <i data-lucide="user-plus"></i>
            NUEVO EMPLEADO
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="md:col-span-2 relative">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500"></i>
            <input type="text" id="searchStaff" placeholder="Buscar por nombre, cargo o documento..." 
                class="w-full bg-white border border-slate-200 rounded-xl py-4 pl-12 pr-4 text-slate-700 placeholder-slate-400 outline-none focus:border-neon-green transition-all shadow-sm">
        </div>
        <div class="flex items-center gap-4">
            <div class="flex-1 flex items-center justify-between text-slate-500 text-sm bg-white border border-slate-200 rounded-xl px-4 py-3 shadow-sm h-full">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-slate-400"></i>
                    <span>Total:</span>
                </div>
                <strong id="totalCount" class="text-navy-blue text-lg">0</strong>
            </div>
            <select id="limitSelector" class="bg-white border border-slate-200 rounded-xl py-3 px-4 text-xs font-bold text-navy-blue outline-none focus:border-neon-green shadow-sm cursor-pointer">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <div class="glass-card rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                        <th class="px-8 py-6">ID Interno</th>
                        <th class="px-8 py-6">Cédula</th>
                        <th class="px-8 py-6">Empleado</th>
                        <th class="px-8 py-6">Cargo / Especialidad</th>
                        <th class="px-8 py-6">Acceso</th>
                        <th class="px-8 py-6">Contacto</th>
                        <th class="px-8 py-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-slate-100 text-sm text-slate-600">
                    <tr id="loadingRow">
                        <td colspan="7" class="px-8 py-16 text-center text-slate-400 italic animate-pulse font-medium">SINCRONIZANDO PERSONAL...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pie de Tabla Unificado -->
        <div class="px-8 py-4 bg-white border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Mostrando <span id="startIndex">0</span> - <span id="endIndex">0</span> de <span id="totalItemsDisplay">0</span> empleados
            </div>
            <div class="flex items-center gap-2" id="paginationControls"></div>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="staffModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h2 id="modalTitle" class="text-xl font-bold text-navy-blue uppercase tracking-wider">Registrar Empleado</h2>
            <button id="btnCloseModal" class="text-gray-500 hover:text-navy-blue">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <form id="formStaff" class="p-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">ID Interno</label>
                    <input type="text" name="id" id="staffId" required placeholder="STAFF-01" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Cédula</label>
                    <input type="text" name="cedula" id="staffCedula" required placeholder="V-123456" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Cargo</label>
                    <select name="cargo" id="staffCargo" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                        <option value="ADMINISTRADOR">ADMINISTRADOR</option>
                        <option value="MECANICO">MECANICO</option>
                        <option value="AYUDANTE">AYUDANTE</option>
                        <option value="VENDEDOR">VENDEDOR</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nombre Completo</label>
                <input type="text" name="nombre" id="staffNombre" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all uppercase">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Dirección de Residencia</label>
                <textarea name="direccion" id="staffDireccion" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all resize-none uppercase"></textarea>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-4">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="has_system_access" id="hasSystemAccess" class="w-4 h-4 text-neon-green border-slate-300 rounded focus:ring-neon-green">
                    <label for="hasSystemAccess" class="text-sm font-bold text-navy-blue">Habilitar Acceso al Sistema</label>
                </div>
                
                <div id="userFields" class="hidden grid grid-cols-2 gap-4 pt-2 border-t border-slate-200">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Usuario</label>
                        <input type="text" name="username" id="staffUser" class="w-full bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:border-neon-green">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Rol Sistema</label>
                        <select name="role_id" id="staffRoleId" class="w-full bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:border-neon-green">
                            <?php foreach($data['roles'] as $rol): ?>
                                <option value="<?php echo $rol->id; ?>"><?php echo s($rol->nombre_rol); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Contraseña (Opcional en edición)</label>
                        <input type="password" name="password" id="staffPass" class="w-full bg-white border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:border-neon-green">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Teléfono</label>
                    <input type="text" name="telefono" id="staffTelefono" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Email</label>
                    <input type="email" name="email" id="staffEmail" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 outline-none focus:border-neon-green transition-all">
                </div>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" id="btnCancel" class="flex-1 bg-slate-100 text-slate-600 font-bold py-3 rounded-xl hover:bg-slate-200 uppercase text-xs">Cancelar</button>
                <button type="submit" id="btnSaveStaff" class="flex-1 bg-neon-green text-black font-black py-3 rounded-xl hover:scale-[1.02] uppercase text-xs flex items-center justify-center gap-2">Guardar Empleado</button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo URLROOT; ?>/js/personal.js"></script>
