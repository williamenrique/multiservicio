<div id="ventaMostradorApp" class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Panel de Venta -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i data-lucide="shopping-cart" class="w-5 h-5 text-blue-600"></i>
                        </div>
                        <h5 class="text-lg font-bold text-slate-800">Nueva Venta de Repuestos</h5>
                    </div>
                    <span class="px-3 py-1 bg-blue-50 text-blue-600 text-xs font-bold rounded-full uppercase tracking-wider">Administrador</span>
                </div>
                <div class="p-6">
                    <!-- Buscador de Productos -->
                    <div class="relative mb-4">
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 group-focus-within:text-blue-500 transition-colors">
                                <i data-lucide="search" class="w-5 h-5"></i>
                            </span>
                            <input type="text" id="buscarProducto" class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm" 
                                   placeholder="Escriba nombre, código o categoría del repuesto..." autocomplete="off">
                        </div>
                        <div id="resultadosBusqueda" class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-2xl border border-slate-100 hidden max-h-80 overflow-y-auto overflow-x-hidden">
                            <!-- Resultados vía AJAX -->
                        </div>
                    </div>

                    <!-- Tabla de Items -->
                    <div class="overflow-x-auto min-h-[350px]">
                        <table class="w-full text-base text-left text-slate-600" id="tablaVenta">
                            <thead class="text-sm text-slate-400 uppercase bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Repuesto</th>
                                    <th class="px-4 py-3 font-semibold text-center" width="100">Cant.</th>
                                    <th class="px-4 py-3 font-semibold text-right" width="120">Precio</th>
                                    <th class="px-4 py-3 font-semibold text-right" width="120">Subtotal</th>
                                    <th class="px-4 py-3" width="50"></th>
                                </tr>
                            </thead>
                            <tbody id="listaItems" class="divide-y divide-slate-100">
                                <!-- Items cargados dinámicamente -->
                            </tbody>
                        </table>
                        <div id="vacioPlaceholder" class="flex flex-col items-center justify-center py-20 text-slate-400">
                            <i data-lucide="package-open" class="w-16 h-16 mb-4 opacity-20"></i>
                            <p class="text-sm font-medium">No hay productos en la venta actual</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial Reciente de Mostrador -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-slate-400"></i>
                    <h6 class="text-sm font-bold text-slate-700">Últimas Ventas de Mostrador</h6>
                </div>
                
                <!-- Controles de Filtro -->
                <div class="p-6 bg-slate-50/50 border-b border-slate-100 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="relative">
                        <input type="text" id="historialSearch" class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20 outline-none" placeholder="Buscar ID o Cliente...">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                    </div>
                    <input type="date" id="historialDesde" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20 outline-none">
                    <input type="date" id="historialHasta" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20 outline-none">
                    <select id="historialLimit" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500/20 outline-none">
                        <option value="10">10 registros</option>
                        <option value="25">25 registros</option>
                        <option value="50">50 registros</option>
                    </select>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-base text-left text-slate-600">
                        <thead class="bg-slate-50/50 text-sm">
                            <tr>
                                <th class="px-6 py-3">N° Factura</th>
                                <th class="px-6 py-3">Fecha</th>
                                <th class="px-6 py-3">Cliente</th>
                                <th class="px-6 py-3">Estado</th>
                                <th class="px-6 py-3 text-right">Total</th>
                                <th class="px-6 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoHistorial" class="divide-y divide-slate-100">
                            <!-- Cargado por historial() -->
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="p-6 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-500 uppercase" id="historialInfo">Mostrando 0 de 0 ventas</p>
                    <div class="flex gap-2" id="paginationControls">
                        <button id="btnPrevHistorial" class="p-2 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600 disabled:opacity-30 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        </button>
                        <button id="btnNextHistorial" class="p-2 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600 disabled:opacity-30 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar de Totales -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
                <h6 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Resumen de Operación</h6>
                
                <!-- Selección de Cliente -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-xs font-bold text-slate-500 uppercase">Cliente</label>
                        <button type="button" onclick="abrirModalCliente()" class="text-[10px] font-black text-blue-600 hover:text-blue-800 transition-colors uppercase">+ Nuevo Cliente</button>
                    </div>
                    <div class="relative">
                        <input type="text" id="clienteSearch" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/10 text-sm" placeholder="Buscar por nombre o CC (Venta Mostrador)" autocomplete="off">
                        <input type="hidden" id="clienteId" value="">
                        <div id="clienteResultados" class="absolute z-50 w-full mt-1 bg-white rounded-lg shadow-xl border border-slate-100 hidden max-h-60 overflow-y-auto"></div>
                    </div>
                </div>

                <div class="space-y-3 py-6 border-y border-slate-100">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">Subtotal:</span>
                        <span id="txtSubtotal" class="font-bold text-slate-700">$ 0.00</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">Impuestos (IVA):</span>
                        <span id="txtIva" class="font-bold text-slate-700">$ 0.00</span>
                    </div>
                    <div class="flex justify-between items-center pt-4">
                        <span class="text-lg font-bold text-slate-800">TOTAL</span>
                        <span id="txtTotal" class="text-2xl font-black text-blue-600">$ 0.00</span>
                    </div>
                </div>

                <!-- Métodos de Pago -->
                <div class="mt-6 mb-8 space-y-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-tighter">Detalle de Pago (Crédito si el pago es parcial)</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase">Efectivo</label>
                            <input type="number" id="pagoEfectivo" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold focus:ring-2 focus:ring-blue-500/20 outline-none" value="0" step="0.01">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase">Transferencia</label>
                            <input type="number" id="pagoTransferencia" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold focus:ring-2 focus:ring-blue-500/20 outline-none" value="0" step="0.01">
                        </div>
                    </div>
                    <div id="msgSaldo" class="text-center p-2 rounded-lg bg-amber-50 text-amber-700 text-[10px] font-bold uppercase hidden">
                        Venta a Crédito: Pendiente <span id="txtSaldo"></span>
                    </div>
                </div>

                <button id="btnProcesar" class="w-full py-4 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-200 disabled:cursor-not-allowed text-white rounded-xl font-bold shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center gap-2" disabled>
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    COMPLETAR VENTA
                </button>
                
                <button class="w-full mt-4 py-2 text-slate-400 hover:text-slate-600 text-xs font-bold flex items-center justify-center gap-2 transition-colors" onclick="limpiarVenta()">
                    <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                    LIMPIAR FORMULARIO
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registro Rápido de Cliente -->
<div id="modalNuevoCliente" class="fixed inset-0 z-[60] hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h5 class="text-lg font-bold text-slate-800">Registro Rápido de Cliente</h5>
            <button onclick="cerrarModalCliente()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Cédula / NIT</label>
                <input type="text" id="new_cli_id" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none text-sm" placeholder="Ej: 12345678">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Nombre Completo</label>
                <input type="text" id="new_cli_nombre" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none text-sm" placeholder="Ej: JUAN PEREZ">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Teléfono</label>
                <input type="text" id="new_cli_tel" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none text-sm" placeholder="Ej: 3001234567">
            </div>
        </div>
        <div class="p-6 bg-slate-50 flex gap-3">
            <button onclick="cerrarModalCliente()" class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase hover:bg-slate-100 rounded-xl transition-colors">Cancelar</button>
            <button onclick="guardarClienteRapido()" class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-bold text-xs uppercase shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all">Guardar Cliente</button>
        </div>
    </div>
</div>

<!-- Modal Venta Exitosa -->
<div id="modalVentaExitosa" class="fixed inset-0 z-[70] hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-in fade-in zoom-in duration-200 text-center p-8">
        <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <i data-lucide="check-circle" class="w-10 h-10"></i>
        </div>
        <h4 class="text-xl font-black text-slate-800 mb-2 uppercase tracking-tight">¡Venta Completada!</h4>
        <p class="text-slate-500 text-sm mb-8 font-medium">La transacción se procesó correctamente.</p>
        
        <div class="flex flex-col gap-3">
            <button id="btnImprimirFacturaExito" class="w-full py-4 bg-blue-600 text-white rounded-xl font-bold shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                <i data-lucide="printer" class="w-5 h-5"></i>
                IMPRIMIR FACTURA
            </button>
            <button onclick="cerrarModalExito()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition-all uppercase text-xs tracking-widest">
                Cerrar
            </button>
        </div>
    </div>
</div>

<style>
    .payment-method.active { border-color: #2563eb; background-color: #eff6ff; color: #1e40af; }
    .payment-method:not(.active) { border-color: #f1f5f9; }
    .payment-method:not(.active):hover { border-color: #e2e8f0; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide) lucide.createIcons();

    let carrito = [];
    let ultimaVentaId = null;
    let lastClientResults = [];
    const inputCliSearch = document.getElementById('clienteSearch');
    const divCliResultados = document.getElementById('clienteResultados');
    const hiddenCliId = document.getElementById('clienteId');
    const inputBusqueda = document.getElementById('buscarProducto');
    const resultados = document.getElementById('resultadosBusqueda');
    const inputPagoEfectivo = document.getElementById('pagoEfectivo');
    const inputPagoTransferencia = document.getElementById('pagoTransferencia');

    // 1. Inicializar fechas con el mes cursante ANTES de crear la tabla
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const today = `${year}-${month}-${day}`;
    
    const inputDesde = document.getElementById('historialDesde');
    const inputHasta = document.getElementById('historialHasta');
    
    if (inputDesde) inputDesde.value = `${year}-${month}-01`;
    if (inputHasta) inputHasta.value = today;

    // Inicialización del Motor de Tablas centralizado (Se mueve arriba para estar disponible)
    const historialTable = new DataTableRefactor({
        tableBodyId: 'cuerpoHistorial',
        endpoint: `${URLROOT}/venta/historial`,
        limitSelectorId: 'historialLimit',
        searchInputId: 'historialSearch',
        paginationId: 'paginationControls', // Asegúrate de que este ID exista en tu HTML o usa los botones específicos
        totalId: 'historialInfo',
        getExtraParams: () => ({
            desde: document.getElementById('historialDesde')?.value,
            hasta: document.getElementById('historialHasta')?.value
        }),
        renderRow: (v) => {
            const statusClass = v.status === 'CREDITO' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700';
            return `
                <tr class="hover:bg-slate-50/50 transition-colors border-b border-slate-100">
                    <td class="px-6 py-4 font-black text-slate-800 text-base">#${v.id}</td>
                    <td class="px-6 py-4 text-slate-500 text-sm font-bold uppercase">${v.fecha}</td>
                    <td class="px-6 py-4 text-slate-700 font-bold text-sm uppercase">${v.cliente_nombre || 'CLIENTE MOSTRADOR'}</td>
                    <td class="px-6 py-4">
                        <span class="text-[10px] font-black px-2 py-1 rounded-full ${statusClass} uppercase tracking-tighter">${v.status}</span>
                    </td>
                    <td class="px-6 py-4 text-right font-black text-slate-800 text-base">${AppUtils.formatCurrency(v.total)}</td>
                    <td class="px-6 py-4 text-center">
                        <button class="p-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-all" 
                                onclick="window.open('${URLROOT}/venta/imprimirFactura/${v.id}', '_blank')" title="Reimprimir Factura">
                            <i data-lucide="printer" class="w-5 h-5"></i>
                        </button>
                    </td>
                </tr>
                `;
        }
    });

    // Forzar la carga inicial de datos
    historialTable.reload();

    // 2. Escuchar cambios en fechas para actualizar historial dinámicamente
    if (inputDesde) inputDesde.addEventListener('change', () => historialTable.reload());
    if (inputHasta) inputHasta.addEventListener('change', () => historialTable.reload());

    // Manejo de botones de pago
    document.querySelectorAll('.payment-method').forEach(btn => {
        btn.onclick = function() {
            document.querySelectorAll('.payment-method').forEach(b => b.classList.remove('active', 'text-blue-700', 'bg-blue-50', 'border-blue-600'));
            this.classList.add('active');
        }
    });

    // Búsqueda AJAX de productos
    inputBusqueda.addEventListener('input', async (e) => {
        const term = e.target.value;
        if (term.length < 2) {
            resultados.classList.add('d-none');
            return;
        }

        const res = await fetch(`<?php echo URLROOT; ?>/facturacion/buscarItems?term=${term}`);
        const items = await res.json();
        
        resultados.innerHTML = '';
        if (items.length > 0) {
            items.forEach(item => {
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-4 hover:bg-slate-50 cursor-pointer border-b border-slate-50 transition-colors last:border-0';
                div.innerHTML = `
                    <div class="flex-1">
                        <div class="text-sm font-bold text-slate-700 uppercase">${item.nombre}</div>
                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">STOCK: ${item.stock_disponible} | ${item.categoria}</div>
                    </div>
                    <div class="text-sm font-black text-blue-600">$ ${item.precio}</div>
                `;
                div.onclick = () => {
                    agregarAlCarrito(item);
                    resultados.classList.add('hidden');
                };
                resultados.appendChild(div);
            });
            resultados.classList.remove('hidden');
        } else {
            resultados.classList.add('hidden');
        }
    });

    // Buscador de Clientes en tiempo real (Igual a Facturación)
    inputCliSearch.addEventListener('input', async (e) => {
        const term = e.target.value.trim();
        if (term.length < 2) {
            divCliResultados.classList.add('hidden');
            if (term.length === 0) hiddenCliId.value = '';
            return;
        }

        const res = await fetch(`<?php echo URLROOT; ?>/clientes/listar?search[value]=${term}&length=5`);
        const data = await res.json();
        lastClientResults = data.data || [];

        if (lastClientResults.length > 0) {
            divCliResultados.innerHTML = lastClientResults.map((c, i) => `
                <div class="p-4 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0 flex justify-between items-center group transition-colors" 
                     onclick="seleccionarCliente('${i}')">
                    <div>
                        <p class="font-black text-xs uppercase text-slate-800 leading-none mb-1 group-hover:text-blue-600">${c.nombre}</p>
                        <p class="text-[10px] text-slate-400 font-mono italic font-bold">CC/NIT: ${c.id}</p>
                    </div>
                    <i data-lucide="user-plus" class="w-4 h-4 text-slate-300 group-hover:text-blue-500"></i>
                </div>`).join('');
            divCliResultados.classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        } else {
            divCliResultados.innerHTML = '<p class="p-3 text-center text-slate-400 text-xs uppercase">No encontrado. Use "+ Nuevo Cliente"</p>';
            divCliResultados.classList.remove('hidden');
        }
    });

    window.seleccionarCliente = (index) => {
        const cliente = lastClientResults[index];
        if (cliente) {
            hiddenCliId.value = cliente.id;
            inputCliSearch.value = cliente.nombre;
            divCliResultados.classList.add('hidden');
            AppUtils.showToast('Cliente vinculado');
        }
    };

    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!divCliResultados.contains(e.target) && e.target !== inputCliSearch) divCliResultados.classList.add('hidden');
        if (!resultados.contains(e.target) && e.target !== inputBusqueda) resultados.classList.add('hidden');
    });

    function agregarAlCarrito(item) {
        const existe = carrito.find(i => i.id === item.id);
        if (existe) {
            if (existe.cantidad < item.stock_disponible) existe.cantidad++;
        } else {
            carrito.push({...item, cantidad: 1, tipo: 'PRODUCTO'});
        }
        inputBusqueda.value = '';
        resultados.classList.add('hidden');
        renderizar();
    }

    function renderizar() {
        const lista = document.getElementById('listaItems');
        const placeholder = document.getElementById('vacioPlaceholder');
        lista.innerHTML = '';
        
        let subtotal = 0;
        carrito.forEach((item, index) => {
            const st = item.precio * item.cantidad;
            subtotal += st;
            lista.innerHTML += `
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-4 py-5">
                        <div class="text-base font-bold text-slate-700 uppercase">${item.nombre}</div>
                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">${item.categoria}</div>
                    </td>
                    <td class="px-4 py-5 text-center">
                        <input type="number" class="w-16 text-center py-1 bg-white border border-slate-200 rounded text-base font-bold focus:border-blue-500 focus:outline-none" value="${item.cantidad}" 
                               onchange="actualizarCant(${index}, this.value)">
                    </td>
                    <td class="px-4 py-5 text-right font-medium text-slate-500">$ ${item.precio}</td>
                    <td class="px-4 py-5 text-right font-black text-slate-800">$ ${st.toFixed(2)}</td>
                    <td class="px-4 py-5 text-center">
                        <button class="p-2 text-slate-300 hover:text-red-500 transition-colors" onclick="eliminarItem(${index})">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        if (window.lucide) lucide.createIcons();

        placeholder.style.display = carrito.length > 0 ? 'none' : 'flex';
        document.getElementById('btnProcesar').disabled = carrito.length === 0;
        
        const totalValue = subtotal.toFixed(2);
        document.getElementById('txtSubtotal').innerText = `$ ${subtotal.toFixed(2)}`;
        document.getElementById('txtTotal').innerText = `$ ${subtotal.toFixed(2)}`;
        
        // Auto-completar el pago en efectivo por defecto si es una venta nueva
        if (carrito.length > 0 && parseFloat(inputPagoEfectivo.value || 0) === 0 && parseFloat(inputPagoTransferencia.value || 0) === 0) {
            inputPagoEfectivo.value = totalValue;
        }
        actualizarSaldo();
    }

    function actualizarSaldo() {
        const total = parseFloat(document.getElementById('txtTotal').innerText.replace('$ ', '')) || 0;
        const pagado = parseFloat(inputPagoEfectivo.value || 0) + parseFloat(inputPagoTransferencia.value || 0);
        const saldo = total - pagado;
        const msgSaldo = document.getElementById('msgSaldo');
        
        if (saldo > 0.05) {
            msgSaldo.classList.remove('hidden');
            document.getElementById('txtSaldo').innerText = AppUtils.formatCurrency(saldo);
        } else {
            msgSaldo.classList.add('hidden');
        }
    }

    function resetFormularioVenta() {
        carrito = [];
        hiddenCliId.value = '';
        inputCliSearch.value = '';
        inputBusqueda.value = '';
        inputPagoEfectivo.value = '';
        inputPagoTransferencia.value = '';
        document.getElementById('msgSaldo').classList.add('hidden');
        document.getElementById('txtSaldo').innerText = '';
        renderizar();
        historialTable.reload();
    }

    inputPagoEfectivo.addEventListener('input', (e) => {
        const valor = e.target.value;
        if (valor !== '' && parseFloat(valor) > 0) {
            inputPagoTransferencia.value = '';
        }
        actualizarSaldo();
    });

    inputPagoTransferencia.addEventListener('input', (e) => {
        const valor = e.target.value;
        if (valor !== '' && parseFloat(valor) > 0) {
            inputPagoEfectivo.value = '';
        }
        actualizarSaldo();
    });

    window.actualizarCant = (index, val) => {
        if(val < 1) return;
        carrito[index].cantidad = parseInt(val);
        renderizar();
    };

    window.eliminarItem = (index) => {
        carrito.splice(index, 1);
        renderizar();
    };

    window.limpiarVenta = () => {
        resetFormularioVenta();
    };

    window.cerrarModalExito = () => {
        document.getElementById('modalVentaExitosa').classList.add('hidden');
    };

    document.getElementById('btnImprimirFacturaExito').onclick = () => {
        if (ultimaVentaId) {
            window.open(`<?php echo URLROOT; ?>/venta/imprimirFactura/${ultimaVentaId}`, '_blank');
        }
    };

    // Funciones para Registro Rápido de Cliente
    window.abrirModalCliente = () => {
        document.getElementById('modalNuevoCliente').classList.remove('hidden');
        document.getElementById('new_cli_id').focus();
    };

    window.cerrarModalCliente = () => {
        document.getElementById('modalNuevoCliente').classList.add('hidden');
        document.getElementById('new_cli_id').value = '';
        document.getElementById('new_cli_nombre').value = '';
        document.getElementById('new_cli_tel').value = '';
    };

    window.guardarClienteRapido = async () => {
        const id = document.getElementById('new_cli_id').value.trim();
        const nombre = document.getElementById('new_cli_nombre').value.trim();
        const telefono = document.getElementById('new_cli_tel').value.trim();

        if (!id || !nombre) {
            AppUtils.showToast('Cédula y Nombre son obligatorios', 'error');
            return;
        }

        const res = await fetch('<?php echo URLROOT; ?>/clientes/guardar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nombre, telefono, email: '', direccion: '' })
        });
        const data = await res.json();

        if (data.success) {
            hiddenCliId.value = id;
            inputCliSearch.value = nombre;
            cerrarModalCliente();
            divCliResultados.classList.add('hidden');
            AppUtils.showToast('Cliente registrado y seleccionado');
        } else {
            AppUtils.showToast(data.mensaje || 'Error al guardar', 'error');
        }
    };

    // Procesar Venta
    document.getElementById('btnProcesar').onclick = async function() {
        const btn = this;
        const originalContent = btn.innerHTML;

        const payload = {
            items: carrito,
            cliente_id: document.getElementById('clienteId').value,
            pago_efectivo: parseFloat(document.getElementById('pagoEfectivo').value || 0),
            pago_transferencia: parseFloat(document.getElementById('pagoTransferencia').value || 0),
            mecanico_id: null,
            placa: '',
            iva_activo: false // Por defecto en mostrador no se aplica IVA a menos que se implemente el switch
        };

        try {
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader" class="w-5 h-5 animate-spin"></i> PROCESANDO...';
            if(window.lucide) lucide.createIcons();

            const res = await fetch('<?php echo URLROOT; ?>/facturacion/procesar', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
                },
                body: JSON.stringify(payload)
            });
            
            if (!res.ok) {
                const errorData = await res.json();
                throw new Error(errorData.mensaje || 'Error interno del servidor');
            }

            const data = await res.json();
            
            if (!data.success) throw new Error(data.mensaje);

            ultimaVentaId = data.venta_id;
            
            // Mostrar modal de éxito en lugar de abrir pestaña directamente
            document.getElementById('modalVentaExitosa').classList.remove('hidden');
            if (window.lucide) lucide.createIcons();

            // Limpiar app y refrescar tabla sin recargar página
            resetFormularioVenta();
        } catch (error) {
            AppUtils.showToast(error.message, 'error');
            console.error("Error en venta:", error);
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalContent;
            if(window.lucide) lucide.createIcons();
        }
    };
});
</script>