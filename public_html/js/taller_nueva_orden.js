/**
 * Maneja la búsqueda y registro rápido de clientes por identificación.
 */
document.addEventListener('DOMContentLoaded', () => {
    const inputId = document.getElementById('cliente_id');
    const inputNombre = document.getElementById('cliente_nombre');
    const resultsContainer = document.getElementById('cliente_results');

    let searchTimeout;
    let allClients = [];

    // LIMPIEZA: Eliminar cualquier listener antiguo que pueda estar interfiriendo
    // Esto ayuda si hay scripts cargados por duplicado o en el HTML
    if (inputId) {
        const clonedInput = inputId.cloneNode(true);
        inputId.parentNode.replaceChild(clonedInput, inputId);
    }

    // Re-obtener referencias tras la clonación
    const newInputId = document.getElementById('cliente_id');
    const newInputNombre = document.getElementById('cliente_nombre');

    if (newInputId && newInputNombre) {
        // Cargar lista inicial de clientes para búsqueda rápida local
        const fetchClients = async () => {
            try {
                const res = await fetch(`${URLROOT}/clientes/listar`);
                const result = await res.json();
                allClients = result.data || result;
            } catch (e) {
                console.error("Error cargando clientes:", e);
            }
        };
        fetchClients();

        newInputId.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const term = newInputId.value.trim().toLowerCase();

            if (term.length < 2) {
                if (resultsContainer) resultsContainer.classList.add('hidden');
                if (term.length === 0) newInputNombre.value = '';
                return;
            }

            if (!Array.isArray(allClients)) return;

            searchTimeout = setTimeout(() => {
                const filtered = allClients.filter(c =>
                    (c.id && String(c.id).toLowerCase().includes(term)) ||
                    (c.nombre && String(c.nombre).toLowerCase().includes(term))
                );

                renderResults(filtered, term);
            }, 300);
        });

        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (resultsContainer && !resultsContainer.contains(e.target) && e.target !== newInputId) {
                resultsContainer.classList.add('hidden');
            }
        });

        // IMPORTANTE: Bloquear comportamientos heredados o automáticos al perder el foco
        newInputId.addEventListener('blur', (e) => {
            // Solo cerramos el contenedor con un pequeño delay para permitir clics en los resultados
            setTimeout(() => {
                if (resultsContainer) resultsContainer.classList.add('hidden');
            }, 200);
            // Detenemos cualquier otro evento que intente disparar redirecciones
            e.stopImmediatePropagation();
        });
    }

    function renderResults(clients, term) {
        if (!resultsContainer) return;

        let html = '';
        if (clients.length > 0) {
            html = clients.map(c => {
                const escapedName = c.nombre.replace(/'/g, "\\'");
                return `
                    <div class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0" 
                         onclick="window.selectClientOS('${c.id}', '${escapedName}')">
                        <p class="font-bold text-xs uppercase text-navy-blue">${c.nombre}</p>
                        <p class="text-[10px] text-slate-400 font-mono">ID: ${c.id}</p>
                    </div>`;
            }).join('');
        }

        // Verificar si el término exacto (ID) ya existe en la lista filtrada
        const exactMatch = clients.find(c => String(c.id).toLowerCase() === term);

        if (!exactMatch) {
            html += `
                <div class="p-3 border-t border-slate-100 bg-slate-50/50">
                    <p class="text-[9px] text-slate-400 uppercase font-black mb-2 px-1">Identificación no encontrada</p>
                    <button type="button" onclick="window.quickRegisterOS('${term}')" 
                            class="w-full text-left flex items-center gap-2 p-2 rounded-xl hover:bg-white hover:shadow-sm text-[10px] font-black text-blue-600 hover:text-navy-blue uppercase transition-all group">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5 group-hover:scale-110 transition-transform"></i>
                        <span>+ Registrar ID "${term}" como nuevo</span>
                    </button>
                </div>`;
        }

        resultsContainer.innerHTML = html || '<div class="p-4 text-center text-slate-400 text-xs italic">No se encontraron resultados</div>';
        resultsContainer.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    window.selectClientOS = (id, nombre) => {
        newInputId.value = id;
        newInputNombre.value = nombre;
        newInputNombre.classList.remove('bg-slate-100');
        newInputNombre.classList.add('bg-green-50');
        if (resultsContainer) resultsContainer.classList.add('hidden');
        if (window.AppUtils) AppUtils.showToast('Cliente seleccionado');
    };

    window.quickRegisterOS = (id) => {
        if (resultsContainer) resultsContainer.classList.add('hidden');
        lanzarRegistroRapido(id);
    };

    async function lanzarRegistroRapido(id) {
        const { value: formValues } = await Swal.fire({
            title: `<span class="text-[10px] uppercase text-slate-400 font-black tracking-widest">Nuevo Registro</span><br><span class="text-navy-blue">ID: ${id}</span>`,
            html: `
                <div class="text-left space-y-4 pt-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Nombre Completo</label>
                        <input id="swal-nombre" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm uppercase font-bold focus:ring-2 focus:ring-blue-500 outline-none" placeholder="EJ: JUAN PEREZ">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Correo Electrónico</label>
                            <input id="swal-email" type="email" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 outline-none" placeholder="cliente@correo.com">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-1 ml-1">Teléfono</label>
                            <input id="swal-telefono" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="04...">
                        </div>
                    </div>
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'REGISTRAR Y SELECCIONAR',
            confirmButtonColor: '#10b981',
            preConfirm: () => {
                const nombre = document.getElementById('swal-nombre').value.trim();
                if (!nombre) {
                    Swal.showValidationMessage('El nombre es obligatorio');
                    return false;
                }
                return {
                    nombre: nombre.toUpperCase(),
                    email: document.getElementById('swal-email').value.trim().toLowerCase(),
                    telefono: document.getElementById('swal-telefono').value.trim()
                };
            }
        });

        if (formValues) {
            try {
                const saveRes = await fetch(`${URLROOT}/clientes/guardar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '') },
                    body: JSON.stringify({
                        id,
                        nombre: formValues.nombre,
                        email: formValues.email,
                        telefono: formValues.telefono,
                        direccion: ''
                    })
                });
                const result = await saveRes.json();
                if (result.success) {
                    newInputId.value = id;
                    newInputNombre.value = formValues.nombre;
                    newInputNombre.classList.remove('bg-slate-100');
                    newInputNombre.classList.add('bg-green-50');
                    if (window.AppUtils) AppUtils.showToast('Cliente registrado con éxito');

                    // Actualizar el estado local para que la búsqueda lo reconozca de inmediato
                    allClients.push({
                        id,
                        nombre: formValues.nombre,
                        email: formValues.email,
                        telefono: formValues.telefono
                    });
                } else {
                    if (window.AppUtils) AppUtils.showToast(result.mensaje || 'Error al guardar', 'error');
                }
            } catch (error) {
                console.error("Error al registrar cliente:", error);
            }
        }
    }
});

/* ==================== BÚSQUEDA DE PRESUPUESTOS ACTIVOS ==================== */
document.addEventListener('DOMContentLoaded', () => {
    const inputPresupuesto = document.getElementById('buscarPresupuestoActivo');
    const resultsContainer = document.getElementById('presupuesto-activo-results');
    const seleccionadoContainer = document.getElementById('presupuesto-seleccionado');
    const infoElement = document.getElementById('presupuesto-info');
    const clienteElement = document.getElementById('presupuesto-cliente');
    let presupuestoSeleccionadoId = null;
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
                        resultsContainer.innerHTML = '<div class="p-4 text-center text-slate-400 text-xs italic">No se encontraron presupuestos activos</div>';
                        resultsContainer.classList.remove('hidden');
                    }
                } catch (e) {
                    console.error("Error buscando presupuestos:", e);
                    resultsContainer.innerHTML = '<div class="p-4 text-center text-red-400 text-xs italic">Error al buscar</div>';
                    resultsContainer.classList.remove('hidden');
                }
            }, 300);
        });

        // Cerrar resultados al hacer clic fuera
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
                const estadoBadge = p.estado === 'ACTIVO'
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">ACTIVO</span>'
                    : '';

                return `
                    <div class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0" 
                         onclick="seleccionarPresupuestoActivo('${p.id}', '${p.numero}', '${p.cliente_nombre.replace(/'/g, "\\'")}', '${p.cliente_telefono || ''}', '${p.total}', '${p.fecha_activacion || p.fecha_emision}')">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-bold text-xs uppercase text-navy-blue">${p.numero} ${estadoBadge}</p>
                                <p class="text-[10px] text-slate-400 font-mono">${p.cliente_nombre}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-amber-600 font-bold font-mono">$${parseFloat(p.total).toLocaleString('es-CO', { minimumFractionDigits: 2 })}</p>
                                <p class="text-[9px] text-slate-500">${p.fecha_activacion ? new Date(p.fecha_activacion).toLocaleDateString('es-ES') : new Date(p.fecha_emision).toLocaleDateString('es-ES')}</p>
                            </div>
                        </div>`;
            }).join('');
        } else {
            html = '<div class="p-4 text-center text-slate-400 text-xs italic">No se encontraron presupuestos activos</div>';
        }

        resultsContainer.innerHTML = html;
        resultsContainer.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    window.seleccionarPresupuestoActivo = (id, numero, clienteNombre, clienteTelefono, total, fecha) => {
        presupuestoSeleccionadoId = id;
        inputPresupuesto.value = `${numero} - ${clienteNombre}`;
        if (resultsContainer) resultsContainer.classList.add('hidden');

        // Mostrar info del presupuesto seleccionado
        infoElement.textContent = `${numero} | Total: $${parseFloat(total).toLocaleString('es-CO', { minimumFractionDigits: 2 })}`;
        clienteElement.textContent = `Cliente: ${clienteNombre} | Tel: ${clienteTelefono || 'N/A'} | Activado: ${fecha ? new Date(fecha).toLocaleDateString('es-ES') : 'N/A'}`;
        seleccionadoContainer.classList.remove('hidden');

        // Guardar el ID en un campo hidden para enviarlo con el formulario
        let hiddenInput = document.getElementById('presupuesto_activo_id');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'presupuesto_activo_id';
            hiddenInput.id = 'presupuesto_activo_id';
            document.getElementById('formNuevaOrden').appendChild(hiddenInput);
        }
        hiddenInput.value = id;

        if (window.AppUtils) AppUtils.showToast(`Presupuesto ${numero} anexado correctamente`);
    };

    window.desanexarPresupuesto = () => {
        presupuestoSeleccionadoId = null;
        inputPresupuesto.value = '';
        if (resultsContainer) resultsContainer.classList.add('hidden');
        if (seleccionadoContainer) seleccionadoContainer.classList.add('hidden');

        const hiddenInput = document.getElementById('presupuesto_activo_id');
        if (hiddenInput) hiddenInput.remove();

        if (window.AppUtils) AppUtils.showToast('Presupuesto desanexado');
    };
});
