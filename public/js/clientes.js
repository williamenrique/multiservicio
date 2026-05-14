document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const formCliente = document.getElementById('formCliente');
    const clientModal = document.getElementById('clientModal');
    const btnOpenModal = document.getElementById('btnOpenModal');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const btnCancel = document.getElementById('btnCancel');
    const totalCount = document.getElementById('totalCount');
    const searchClient = document.getElementById('searchClient');

    let clientes = [];

    const loadClientes = async () => {
        try {
            const response = await fetch(`${URLROOT}/clientes/listar`);
            clientes = await response.json();
            renderTable(clientes);
        } catch (error) {
            console.error("Error al cargar clientes:", error);
            tableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-10 text-center text-red-500">Error al conectar con la base de datos.</td></tr>';
        }
    };

    const renderTable = (data) => {
        tableBody.innerHTML = '';
        totalCount.textContent = data.length;

        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">No se encontraron clientes registrados.</td></tr>';
            return;
        }

        data.forEach(cliente => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-white/5 transition-colors group border-b border-white/10';
            row.innerHTML = `
                <td class="px-6 py-4 font-mono text-xs text-neon-green">${cliente.id}</td>
                <td class="px-6 py-4 font-bold text-white">${cliente.nombre}</td>
                <td class="px-6 py-4">
                    <div class="flex flex-col">
                        <span class="text-white">${cliente.telefono}</span>
                        <span class="text-gray-500 text-xs">${cliente.email || 'Sin correo'}</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-gray-400">${cliente.direccion || 'N/A'}</td>
                <td class="px-6 py-4 text-right">
                    <button onclick="editCliente('${cliente.id}')" class="p-2 bg-white/5 hover:bg-neon-green/20 text-white hover:text-neon-green rounded-lg transition-all mr-1">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                    </button>
                    <button onclick="deleteCliente('${cliente.id}')" class="p-2 bg-white/5 hover:bg-red-500/20 text-white hover:text-red-500 rounded-lg transition-all">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
        });
        lucide.createIcons();
    };

    // Filtro de búsqueda en tiempo real
    searchClient.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        const filtered = clientes.filter(c =>
            c.nombre.toLowerCase().includes(term) ||
            c.id.toLowerCase().includes(term) ||
            c.telefono.includes(term)
        );
        renderTable(filtered);
    });

    formCliente.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(formCliente);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(`${URLROOT}/clientes/guardar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                toggleModal(false);
                loadClientes();
                formCliente.reset();
            } else {
                alert(result.mensaje);
            }
        } catch (error) {
            console.error("Error al guardar:", error);
        }
    });

    const toggleModal = (show) => {
        clientModal.classList.toggle('hidden', !show);
        if (!show) {
            formCliente.reset();
            document.getElementById('clientId').disabled = false;
            document.getElementById('modalTitle').textContent = "Registrar Cliente";
        }
    };

    btnOpenModal.addEventListener('click', () => toggleModal(true));
    btnCloseModal.addEventListener('click', () => toggleModal(false));
    btnCancel.addEventListener('click', () => toggleModal(false));

    window.editCliente = (id) => {
        const cliente = clientes.find(c => c.id === id);
        if (cliente) {
            document.getElementById('clientId').value = cliente.id;
            document.getElementById('clientId').disabled = true; // No permitir cambiar ID en edición
            document.getElementById('clientName').value = cliente.nombre;
            document.getElementById('clientPhone').value = cliente.telefono;
            document.getElementById('clientEmail').value = cliente.email;
            document.getElementById('clientAddress').value = cliente.direccion;
            document.getElementById('modalTitle').textContent = "Editar Cliente";
            toggleModal(true);
        }
    };

    window.deleteCliente = (id) => {
        AppUtils.confirmAction('¿Eliminar cliente?', 'Esta acción borrará al cliente de la base de datos permanentemente.', async () => {
            try {
                const response = await fetch(`${URLROOT}/clientes/eliminar/${id}`, { method: 'DELETE' });
                const result = await response.json();
                if (result.success) {
                    AppUtils.showToast('Cliente eliminado');
                    loadClientes();
                }
            } catch (error) {
                AppUtils.showToast('Error al eliminar', 'error');
            }
        });
    };

    loadClientes();
});