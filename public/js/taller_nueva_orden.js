/**
 * Maneja la búsqueda y registro rápido de clientes por identificación.
 */
document.addEventListener('DOMContentLoaded', () => {
    const inputId = document.getElementById('cliente_id');
    const inputNombre = document.getElementById('cliente_nombre');
    const resultsContainer = document.getElementById('cliente_results');

    let searchTimeout;
    let allClients = [];

    if (inputId && inputNombre) {
        // Cargar lista inicial de clientes para búsqueda rápida local
        const fetchClients = async () => {
            try {
                const res = await fetch(`${URLROOT}/clientes/listar`);
                allClients = await res.json();
            } catch (e) {
                console.error("Error cargando clientes:", e);
            }
        };
        fetchClients();

        inputId.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const term = inputId.value.trim().toLowerCase();

            if (term.length < 2) {
                if (resultsContainer) resultsContainer.classList.add('hidden');
                if (term.length === 0) inputNombre.value = '';
                return;
            }

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
            if (resultsContainer && !resultsContainer.contains(e.target) && e.target !== inputId) {
                resultsContainer.classList.add('hidden');
            }
        });
    }

    function renderResults(clients, term) {
        if (!resultsContainer) return;

        if (clients.length > 0) {
            resultsContainer.innerHTML = clients.map(c => {
                const escapedName = c.nombre.replace(/'/g, "\\'");
                return `
                    <div class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0" 
                         onclick="window.selectClientOS('${c.id}', '${escapedName}')">
                        <p class="font-bold text-xs uppercase text-navy-blue">${c.nombre}</p>
                        <p class="text-[10px] text-slate-400 font-mono">ID: ${c.id}</p>
                    </div>`;
            }).join('');
        } else {
            resultsContainer.innerHTML = `
                <div class="p-3 text-center">
                    <p class="text-xs text-slate-500 uppercase mb-2">No se encontró el cliente</p>
                    <button type="button" onclick="window.quickRegisterOS('${inputId.value.trim()}')" 
                            class="text-[10px] font-black text-blue-600 hover:underline uppercase">
                        + Registrar como nuevo
                    </button>
                </div>`;
        }
        resultsContainer.classList.remove('hidden');
    }

    window.selectClientOS = (id, nombre) => {
        inputId.value = id;
        inputNombre.value = nombre;
        inputNombre.classList.remove('bg-slate-100');
        inputNombre.classList.add('bg-green-50');
        if (resultsContainer) resultsContainer.classList.add('hidden');
        if (window.AppUtils) AppUtils.showToast('Cliente seleccionado');
    };

    window.quickRegisterOS = (id) => {
        if (resultsContainer) resultsContainer.classList.add('hidden');
        lanzarRegistroRapido(id);
    };

    async function lanzarRegistroRapido(id) {
        const { value: nombre } = await Swal.fire({
            title: 'REGISTRO RÁPIDO',
            text: `La identificación ${id} no existe. ¿Deseas registrar al cliente?`,
            input: 'text',
            inputPlaceholder: 'Nombre completo...',
            showCancelButton: true,
            confirmButtonText: 'Registrar',
            confirmButtonColor: '#10b981',
            inputValidator: (value) => { if (!value) return 'El nombre es obligatorio'; }
        });

        if (nombre) {
            try {
                const saveRes = await fetch(`${URLROOT}/clientes/guardar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, nombre: nombre.toUpperCase(), email: '', telefono: '', direccion: '' })
                });
                const result = await saveRes.json();
                if (result.success) {
                    inputNombre.value = nombre.toUpperCase();
                    inputNombre.classList.add('bg-green-50');
                    if (window.AppUtils) AppUtils.showToast('Cliente registrado con éxito');
                    // Recargar lista local para futuras búsquedas
                    const res = await fetch(`${URLROOT}/clientes/listar`);
                    allClients = await res.json();
                }
            } catch (error) {
                console.error("Error al registrar cliente:", error);
            }
        }
    }
});
