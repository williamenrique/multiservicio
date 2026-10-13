/**
 * taller_nueva_orden.js
 * 
 * Maneja:
 *   1. Búsqueda y registro rápido de clientes por identificación.
 *   2. Carga de vehículos del cliente al seleccionarlo (1 o varios).
 *   3. Búsqueda de presupuestos disponibles para anexar a la OS.
 *   4. Al seleccionar un presupuesto, carga sus items en una sección dedicada
 *      y los envía al backend junto con el ID del presupuesto al guardar.
 * 
 * FIX v2.2:
 *   • desanexarPresupuesto(skipConfirm): permite limpiar sin preguntar cuando
 *     se llama programáticamente desde el submit del formulario.
 *   • Delegación de eventos para los resultados de presupuestos.
 * 
 * v2.3 (2026-10-09) — P2-09:
 *   • quickRegisterOS delega a AppUtils.openQuickClientModal().
 * 
 * v2.4 (2026-10-09) — FIX búsqueda de clientes:
 *   • ANTES: cargaba TODOS los clientes al inicio con fetch('/clientes/listar')
 *     (que por defecto devuelve solo 10) y luego filtraba en el cliente.
 *     Resultado: solo aparecían coincidencias dentro de los 10 más recientes.
 *   • AHORA: búsqueda server-side. Cada pulsación (con debounce 300ms) hace
 *     fetch('/clientes/listar?q=TERM&limit=20') y muestra hasta 20
 *     coincidencias reales de toda la base de datos.
 *   • Se eliminó la variable `allClients` (ya no se necesita).
 */

// ============================================================================
// 1. BÚSQUEDA Y REGISTRO RÁPIDO DE CLIENTES + CARGA DE VEHÍCULOS
// ============================================================================
document.addEventListener('DOMContentLoaded', () => {
    const inputId = document.getElementById('cliente_id');
    const inputNombre = document.getElementById('cliente_nombre');
    const resultsContainer = document.getElementById('cliente_results');

    let searchTimeout;

    // LIMPIEZA: Eliminar cualquier listener antiguo que pueda estar interfiriendo
    if (inputId) {
        const clonedInput = inputId.cloneNode(true);
        inputId.parentNode.replaceChild(clonedInput, inputId);
    }

    const newInputId = document.getElementById('cliente_id');
    const newInputNombre = document.getElementById('cliente_nombre');

    if (newInputId && newInputNombre) {
        /**
         * FIX v2.4: Búsqueda server-side. Cada pulsación (con debounce)
         * consulta al servidor con el término ingresado y recibe hasta 20
         * coincidencias reales (por id, nombre o teléfono).
         */
        newInputId.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const term = newInputId.value.trim();

            if (term.length < 2) {
                if (resultsContainer) resultsContainer.classList.add('hidden');
                if (term.length === 0) newInputNombre.value = '';
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const res = await fetch(`${URLROOT}/clientes/listar?q=${encodeURIComponent(term)}&limit=20&offset=0`);
                    if (!res.ok) return;
                    const result = await res.json();
                    const clientes = result.data || [];

                    renderResults(clientes, term.toLowerCase());
                } catch (e) {
                    console.error('Error buscando clientes:', e);
                    if (resultsContainer) {
                        resultsContainer.innerHTML = '<div class="p-4 text-center text-rose-500 text-xs font-bold">Error al buscar clientes</div>';
                        resultsContainer.classList.remove('hidden');
                    }
                }
            }, 300);
        });

        document.addEventListener('click', (e) => {
            if (resultsContainer && !resultsContainer.contains(e.target) && e.target !== newInputId) {
                resultsContainer.classList.add('hidden');
            }
        });

        newInputId.addEventListener('blur', (e) => {
            setTimeout(() => {
                if (resultsContainer) resultsContainer.classList.add('hidden');
            }, 200);
            e.stopImmediatePropagation();
        });
    }

    function renderResults(clients, term) {
        if (!resultsContainer) return;

        let html = '';
        if (clients.length > 0) {
            html = clients.map(c => {
                const escapedName = (c.nombre || '').replace(/'/g, "\\'");
                const telefono = c.telefono ? ` · Tel: ${c.telefono}` : '';
                return `
                    <div class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0" 
                         onclick="window.selectClientOS('${c.id}', '${escapedName}')">
                        <p class="font-bold text-xs uppercase text-navy-blue">${c.nombre}</p>
                        <p class="text-[10px] text-slate-400 font-mono">ID: ${c.id}${telefono}</p>
                    </div>`;
            }).join('');
        }

        // ¿Hay coincidencia EXACTA con el ID buscado?
        const exactMatch = clients.find(c => String(c.id).toLowerCase() === term);

        if (!exactMatch) {
            const termUpper = term.toUpperCase();
            html += `
                <div class="p-3 border-t border-slate-100 bg-slate-50/50">
                    <p class="text-[9px] text-slate-400 uppercase font-black mb-2 px-1">Identificación no encontrada</p>
                    <button type="button" onclick="window.quickRegisterOS('${termUpper}')" 
                            class="w-full text-left flex items-center gap-2 p-2 rounded-xl hover:bg-white hover:shadow-sm text-[10px] font-black text-blue-600 hover:text-navy-blue uppercase transition-all group">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5 group-hover:scale-110 transition-transform"></i>
                        <span>+ Registrar ID "${termUpper}" como nuevo</span>
                    </button>
                </div>`;
        }

        resultsContainer.innerHTML = html || '<div class="p-4 text-center text-slate-400 text-xs italic">No se encontraron resultados</div>';
        resultsContainer.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    window.selectClientOS = async (id, nombre) => {
        newInputId.value = id;
        newInputNombre.value = nombre;
        newInputNombre.classList.remove('bg-slate-100');
        newInputNombre.classList.add('bg-green-50');
        if (resultsContainer) resultsContainer.classList.add('hidden');
        if (window.AppUtils) AppUtils.showToast('Cliente seleccionado');

        try {
            const res = await fetch(`${URLROOT}/clientes/vehiculos/${id}`);
            const data = await res.json();

            if (!data.success || !data.data || data.data.length === 0) {
                return;
            }

            const vehiculos = data.data;

            if (vehiculos.length === 1) {
                aplicarVehiculo(vehiculos[0]);
                if (window.AppUtils) AppUtils.showToast(`Vehículo ${vehiculos[0].placa} cargado`, 'info');
            } else {
                seleccionarVehiculoDeLista(vehiculos);
            }
        } catch (e) {
            console.error("Error cargando vehículos del cliente:", e);
        }
    };

    function aplicarVehiculo(v) {
        const inputPlaca = document.getElementById('inputPlaca');
        const inputMarca = document.querySelector('[name="marca"]');
        const inputModelo = document.querySelector('[name="modelo"]');
        const inputAnio = document.querySelector('[name="anio"]');
        const inputColor = document.querySelector('[name="color"]');

        if (inputPlaca && v.placa) {
            inputPlaca.value = v.placa;
            inputPlaca.classList.add('bg-green-50', 'border-green-300');
        }
        if (inputMarca && v.marca) {
            inputMarca.value = v.marca;
            inputMarca.classList.add('bg-green-50', 'border-green-300');
        }
        if (inputModelo && v.modelo) {
            inputModelo.value = v.modelo;
            inputModelo.classList.add('bg-green-50', 'border-green-300');
        }
        if (inputAnio && v.anio) {
            inputAnio.value = v.anio;
            inputAnio.classList.add('bg-green-50', 'border-green-300');
        }
        if (inputColor && v.color) {
            inputColor.value = v.color;
            inputColor.classList.add('bg-green-50', 'border-green-300');
        }

        if (inputPlaca && inputPlaca.value) {
            setTimeout(() => {
                inputPlaca.dispatchEvent(new Event('blur'));
            }, 300);
        }
    }

    function seleccionarVehiculoDeLista(vehiculos) {
        const listaHtml = vehiculos.map((v, i) => `
            <div class="p-3 hover:bg-slate-50 cursor-pointer border border-slate-200 rounded-lg transition-all" 
                 onclick="window.elegirVehiculo(${i})">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-black text-sm text-navy-blue uppercase">${v.placa}</p>
                        <p class="text-[11px] text-slate-500 uppercase">${v.marca || ''} ${v.modelo || ''} ${v.anio ? '· ' + v.anio : ''} ${v.color ? '· ' + v.color : ''}</p>
                    </div>
                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300"></i>
                </div>
            </div>
        `).join('');

        window._vehiculosCliente = vehiculos;

        Swal.fire({
            title: '<span class="text-[10px] uppercase text-slate-400 font-black tracking-widest">Seleccionar Vehículo</span><br><span class="text-navy-blue">' + vehiculos.length + ' encontrados</span>',
            html: `<div class="text-left space-y-2 pt-2">${listaHtml}</div>`,
            showCancelButton: true,
            showConfirmButton: false,
            cancelButtonText: 'CANCELAR',
            cancelButtonColor: '#64748b',
            didOpen: () => {
                if (window.lucide) lucide.createIcons();
            }
        });
    }

    window.elegirVehiculo = (index) => {
        const v = window._vehiculosCliente[index];
        if (v) {
            aplicarVehiculo(v);
            Swal.close();
            if (window.AppUtils) AppUtils.showToast(`Vehículo ${v.placa} cargado`, 'success');
        }
    };

    /**
     * FIX P2-09: delega al helper unificado.
     * FIX v2.4: ya no hace push a allClients (eliminada).
     */
    window.quickRegisterOS = (id) => {
        if (resultsContainer) resultsContainer.classList.add('hidden');

        AppUtils.openQuickClientModal({
            presetId: id,
            title: 'NUEVO REGISTRO',
            confirmText: 'REGISTRAR Y SELECCIONAR',
            onSuccess: (cliente) => {
                newInputId.value = cliente.id;
                newInputNombre.value = cliente.nombre;
                newInputNombre.classList.remove('bg-slate-100');
                newInputNombre.classList.add('bg-green-50');
                AppUtils.showToast('Cliente registrado con éxito');
            }
        });
    };
});

// ============================================================================
// 2. BÚSQUEDA Y CARGA DE PRESUPUESTOS PARA ANEXAR A LA OS
// ============================================================================
document.addEventListener('DOMContentLoaded', () => {
    const inputPresupuesto = document.getElementById('buscarPresupuestoActivo');
    const resultsContainer = document.getElementById('presupuesto-activo-results');
    const seleccionadoContainer = document.getElementById('presupuesto-seleccionado');
    const infoElement = document.getElementById('presupuesto-info');
    const clienteElement = document.getElementById('presupuesto-cliente');

    window.presupuestoSeleccionadoId = null;
    window.presupuestoItems = [];

    let searchTimeoutPresupuesto = null;

    if (inputPresupuesto && resultsContainer) {
        inputPresupuesto.addEventListener('input', () => {
            clearTimeout(searchTimeoutPresupuesto);
            const term = inputPresupuesto.value.trim().toLowerCase();

            if (term.length < 2) {
                if (resultsContainer) resultsContainer.classList.add('hidden');
                return;
            }

            searchTimeoutPresupuesto = setTimeout(async () => {
                try {
                    const res = await fetch(`${URLROOT}/presupuesto/buscarActivos?q=${encodeURIComponent(term)}`);
                    const result = await res.json();

                    if (result.success && result.data) {
                        renderPresupuestoResults(result.data, term);
                    } else {
                        resultsContainer.innerHTML = '<div class="p-4 text-center text-slate-400 text-xs italic">No se encontraron presupuestos disponibles</div>';
                        resultsContainer.classList.remove('hidden');
                    }
                } catch (e) {
                    console.error("Error buscando presupuestos:", e);
                    resultsContainer.innerHTML = '<div class="p-4 text-center text-red-400 text-xs italic">Error al buscar</div>';
                    resultsContainer.classList.remove('hidden');
                }
            }, 300);
        });

        // ─── DELEGACIÓN DE EVENTOS ───
        resultsContainer.addEventListener('click', (e) => {
            const item = e.target.closest('[data-presupuesto-id]');
            if (!item) return;

            e.preventDefault();
            e.stopPropagation();

            const id = item.dataset.presupuestoId;
            const numero = item.dataset.presupuestoNumero;
            const clienteNombre = item.dataset.clienteNombre;
            const clienteTelefono = item.dataset.clienteTelefono || '';
            const total = item.dataset.total;
            const fecha = item.dataset.fecha;

            window.seleccionarPresupuestoActivo(id, numero, clienteNombre, clienteTelefono, total, fecha);
        });

        document.addEventListener('click', (e) => {
            if (resultsContainer && !resultsContainer.contains(e.target) && e.target !== inputPresupuesto) {
                resultsContainer.classList.add('hidden');
            }
        });
    }

    function renderPresupuestoResults(presupuestos, term) {
        if (!resultsContainer) return;

        let html = '';
        if (presupuestos.length > 0) {
            html = presupuestos.map(p => {
                const estadoBadge = p.estado === 'ENVIADO'
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-700">ENVIADO</span>'
                    : (p.estado === 'ACEPTADO'
                        ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">ACEPTADO</span>'
                        : '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-600">' + p.estado + '</span>');

                const safeClienteNombre = (p.cliente_nombre || '').replace(/"/g, '&quot;');
                const safeClienteTelefono = (p.cliente_telefono || '').replace(/"/g, '&quot;');
                const safeNumero = (p.numero || '').replace(/"/g, '&quot;');
                const safeFecha = (p.fecha_emision || '').replace(/"/g, '&quot;');

                return `
                    <div class="p-3 hover:bg-blue-50 cursor-pointer border-b border-slate-100 last:border-0 transition-colors"
                         data-presupuesto-id="${p.id}"
                         data-presupuesto-numero="${safeNumero}"
                         data-cliente-nombre="${safeClienteNombre}"
                         data-cliente-telefono="${safeClienteTelefono}"
                         data-total="${p.total}"
                         data-fecha="${safeFecha}">
                        <div class="flex justify-between items-start">
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-xs uppercase text-navy-blue truncate">${p.numero} ${estadoBadge}</p>
                                <p class="text-[10px] text-slate-400 font-mono truncate">${p.cliente_nombre}</p>
                            </div>
                            <div class="text-right flex-shrink-0 ml-2">
                                <p class="text-[10px] text-amber-600 font-bold font-mono">$${parseFloat(p.total).toLocaleString('es-CO', { minimumFractionDigits: 2 })}</p>
                                <p class="text-[9px] text-slate-500">${new Date(p.fecha_emision).toLocaleDateString('es-ES')}</p>
                            </div>
                        </div>
                    </div>`;
            }).join('');
        } else {
            html = '<div class="p-4 text-center text-slate-400 text-xs italic">No se encontraron presupuestos disponibles</div>';
        }

        resultsContainer.innerHTML = html;
        resultsContainer.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    window.seleccionarPresupuestoActivo = async (id, numero, clienteNombre, clienteTelefono, total, fecha) => {
        console.log('[PRESUPUESTO] Seleccionando:', { id, numero, clienteNombre });

        if (!id || id === 'undefined' || id === 'null') {
            if (window.AppUtils) AppUtils.showToast('ID de presupuesto inválido', 'error');
            return;
        }

        if (window.presupuestoSeleccionadoId && window.presupuestoSeleccionadoId !== id) {
            const confirm = await Swal.fire({
                title: '¿Reemplazar presupuesto?',
                text: `Actualmente hay anexado el presupuesto #${window.presupuestoSeleccionadoId}. ¿Deseas reemplazarlo por #${id}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sí, reemplazar',
                cancelButtonText: 'Cancelar'
            });
            if (!confirm.isConfirmed) {
                if (resultsContainer) resultsContainer.classList.add('hidden');
                return;
            }
        }

        window.presupuestoSeleccionadoId = id;
        inputPresupuesto.value = `${numero} - ${clienteNombre}`;
        if (resultsContainer) resultsContainer.classList.add('hidden');

        infoElement.textContent = `${numero} | Total: $${parseFloat(total).toLocaleString('es-CO', { minimumFractionDigits: 2 })}`;
        clienteElement.textContent = `Cliente: ${clienteNombre} | Tel: ${clienteTelefono || 'N/A'} | Emitido: ${fecha ? new Date(fecha).toLocaleDateString('es-ES') : 'N/A'}`;
        seleccionadoContainer.classList.remove('hidden');

        try {
            AppUtils.showLoading('Cargando items del presupuesto...');
            const res = await fetch(`${URLROOT}/presupuesto/obtenerParaAnexar/${id}`);
            const data = await res.json();
            AppUtils.hideLoading();

            if (!data.success || !data.items) {
                AppUtils.showToast(data.mensaje || 'No se pudieron cargar los items', 'error');
                window.presupuestoSeleccionadoId = null;
                window.presupuestoItems = [];
                inputPresupuesto.value = '';
                seleccionadoContainer.classList.add('hidden');
                return;
            }

            window.presupuestoItems = data.items;
            renderizarItemsPresupuesto(data.items, numero);

            const inputClienteId = document.getElementById('cliente_id');
            const inputClienteNombre = document.getElementById('cliente_nombre');
            const inputPlaca = document.getElementById('inputPlaca');
            const inputMarca = document.querySelector('[name="marca"]');
            const inputModelo = document.querySelector('[name="modelo"]');

            if (inputClienteId && !inputClienteId.value && data.data.cliente_id) {
                inputClienteId.value = data.data.cliente_id;
                inputClienteNombre.value = data.data.cliente_nombre || '';
                inputClienteNombre.classList.add('bg-green-50');
            }
            if (inputPlaca && !inputPlaca.value && data.data.vehiculo_placa) {
                inputPlaca.value = data.data.vehiculo_placa;
            }
            if (inputMarca && !inputMarca.value && data.data.vehiculo_marca) {
                inputMarca.value = data.data.vehiculo_marca;
            }
            if (inputModelo && !inputModelo.value && data.data.vehiculo_modelo) {
                inputModelo.value = data.data.vehiculo_modelo;
            }

            AppUtils.showToast(`Presupuesto ${numero} listo para anexar (${data.items.length} item(s))`, 'success');
        } catch (e) {
            AppUtils.hideLoading();
            console.error("Error cargando items del presupuesto:", e);
            AppUtils.showToast('Error al cargar los items del presupuesto', 'error');
            window.presupuestoSeleccionadoId = null;
            window.presupuestoItems = [];
            inputPresupuesto.value = '';
            seleccionadoContainer.classList.add('hidden');
        }
    };

    function renderizarItemsPresupuesto(items, numeroPresupuesto) {
        let section = document.getElementById('presupuesto-items-section');

        if (!section) {
            section = document.createElement('div');
            section.id = 'presupuesto-items-section';
            section.className = 'mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl';
            seleccionadoContainer.parentNode.insertBefore(section, seleccionadoContainer.nextSibling);
        }

        const totalItems = items.length;
        const subtotal = items.reduce((acc, it) => acc + (parseFloat(it.precio) * parseInt(it.cantidad)), 0);

        let rowsHtml = '';
        items.forEach((item) => {
            const subtotalItem = parseFloat(item.precio) * parseInt(item.cantidad);
            const tipoBadge = item.tipo === 'SERVICIO'
                ? '<span class="text-[8px] bg-blue-100 text-blue-600 px-1 rounded font-black">SRV</span>'
                : '<span class="text-[8px] bg-amber-100 text-amber-700 px-1 rounded font-black">PROD</span>';

            rowsHtml += `
                <tr class="border-b border-emerald-100 last:border-0">
                    <td class="py-2 pr-2 text-xs font-medium text-slate-700">
                        ${tipoBadge} ${item.nombre}
                    </td>
                    <td class="py-2 px-2 text-center text-xs font-bold">${item.cantidad}</td>
                    <td class="py-2 px-2 text-right text-xs text-slate-500">$${parseFloat(item.precio).toFixed(2)}</td>
                    <td class="py-2 pl-2 text-right text-xs font-black text-emerald-700">$${subtotalItem.toFixed(2)}</td>
                </tr>`;
        });

        section.innerHTML = `
            <div class="flex justify-between items-center mb-3 pb-2 border-b border-emerald-200">
                <h3 class="text-sm font-bold text-emerald-800 flex items-center gap-2">
                    <i data-lucide="package-check" class="w-4 h-4"></i>
                    Items del Presupuesto ${numeroPresupuesto}
                </h3>
                <span class="text-[10px] font-black bg-emerald-600 text-white px-2 py-1 rounded-full">${totalItems} item(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-[9px] font-black text-emerald-700 uppercase tracking-wider border-b border-emerald-200">
                            <th class="text-left py-2 pr-2">Descripción</th>
                            <th class="text-center py-2 px-2 w-12">Cant</th>
                            <th class="text-right py-2 px-2 w-20">Precio</th>
                            <th class="text-right py-2 pl-2 w-20">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                    <tfoot>
                        <tr class="border-t-2 border-emerald-300">
                            <td colspan="3" class="py-2 pr-2 text-right text-xs font-black text-emerald-800 uppercase">Total:</td>
                            <td class="py-2 pl-2 text-right text-sm font-black text-emerald-700">$${subtotal.toFixed(2)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="text-[10px] text-emerald-600 mt-2 italic">
                * Estos items se agregarán automáticamente a la Orden de Servicio y al borrador de factura.
            </p>
        `;

        if (window.lucide) lucide.createIcons();
    }

    window.desanexarPresupuesto = async (skipConfirm = false) => {
        if (!window.presupuestoSeleccionadoId) {
            inputPresupuesto.value = '';
            if (resultsContainer) resultsContainer.classList.add('hidden');
            if (seleccionadoContainer) seleccionadoContainer.classList.add('hidden');
            const section = document.getElementById('presupuesto-items-section');
            if (section) section.remove();
            window.presupuestoItems = [];
            return;
        }

        if (!skipConfirm) {
            const confirm = await Swal.fire({
                title: '¿Desanexar presupuesto?',
                text: 'Se quitará el presupuesto del formulario. El presupuesto en sí NO se modifica hasta que guardes la OS.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sí, desanexar',
                cancelButtonText: 'Cancelar'
            });

            if (!confirm.isConfirmed) return;
        }

        window.presupuestoSeleccionadoId = null;
        window.presupuestoItems = [];
        inputPresupuesto.value = '';

        if (resultsContainer) resultsContainer.classList.add('hidden');
        if (seleccionadoContainer) seleccionadoContainer.classList.add('hidden');

        const section = document.getElementById('presupuesto-items-section');
        if (section) section.remove();

        if (!skipConfirm && window.AppUtils) {
            AppUtils.showToast('Presupuesto desanexado. Puedes anexar otro.', 'info');
        }
    };
});