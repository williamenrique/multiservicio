/**
 * Lógica del Módulo de Gastos
 */
let expensesTable;

document.addEventListener('DOMContentLoaded', async () => {
    const tableEl = document.getElementById('expensesTable');
    if (tableEl) {
        await initExpenses(tableEl);
    }
});

async function initExpenses(tableElement) { // Renombrado para claridad
    try {
        // Destruir instancia previa si existe para permitir la recarga dinámica
        if ($.fn.DataTable.isDataTable(tableElement)) {
            $(tableElement).DataTable().destroy();
        }

        const response = await fetch(`${URLROOT}/gastos/listar`);
        const expenses = await response.json();

        expensesTable = $(tableElement).DataTable({
            data: expenses,
            order: [[0, 'desc']],
            pageLength: 10, // Mantener la paginación
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            columns: [
                {
                    data: 'fecha',
                    render: d => new Date(d).toLocaleDateString('es-CO', { dateStyle: 'medium' })
                },
                { data: 'descripcion' },
                { data: 'categoria' },
                {
                    data: 'monto',
                    render: d => `<span class="text-red-600 font-bold">${AppUtils.formatCurrency(d)}</span>`
                },
                {
                    data: null,
                    render: (data, type, row) => `
                        <button onclick="eliminarGasto('${row.id}')" class="p-1 text-slate-400 hover:text-red-500 transition-colors">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>`
                }
            ],
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' // Usar URL para idioma completo
            },
            drawCallback: () => lucide.createIcons()
        });
    } catch (e) {
        console.error("Error inicializando tabla de gastos:", e);
        // Mostrar un mensaje de error en la tabla si falla la carga
        $(tableElement).find('tbody').html('<tr><td colspan="5" class="text-center py-10 text-red-500">Error al cargar los gastos.</td></tr>');
    }
}

window.openExpenseModal = async function () { // Hacer la función asíncrona
    Swal.fire({
        title: 'Registrar Gasto del Taller',
        html: `
            <div class="text-left space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Descripción del Gasto</label>
                    <input id="ex-desc" class="w-full p-2 border rounded-lg uppercase" placeholder="EJ: PAGO SERVICIO LUZ">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Categoría</label>
                    <select id="ex-cat" class="w-full p-2 border rounded-lg">
                        <option value="Servicios">Servicios Públicos</option>
                        <option value="Arriendo">Arriendo</option>
                        <option value="Nómina">Nómina</option>
                        <option value="Insumos">Insumos Taller</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Monto (COP)</label>
                    <input id="ex-amount" type="number" class="w-full p-2 border rounded-lg" placeholder="0">
                </div>
            </div>`,
        confirmButtonColor: '#ff4444',
        confirmButtonText: '<span class="font-bold">Registrar Gasto</span>',
        showCancelButton: true,
        preConfirm: () => {
            const descripcion = document.getElementById('ex-desc').value.trim().toUpperCase();
            const monto = parseFloat(document.getElementById('ex-amount').value);
            if (!descripcion || isNaN(monto) || monto <= 0) {
                return Swal.showValidationMessage('Complete todos los campos correctamente');
            }
            return { descripcion, monto, categoria: document.getElementById('ex-cat').value, fecha: new Date().toISOString().split('T')[0] };
        }
    }).then(async result => {
        if (result.isConfirmed) {
            const response = await fetch(`${URLROOT}/gastos/guardar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(result.value)
            });
            const data = await response.json();
            if (data.success) {
                AppUtils.showToast('Gasto registrado');
                // Obtener el elemento de la tabla y recargarla dinámicamente
                const tableEl = document.getElementById('expensesTable');
                await initExpenses(tableEl);
            } else {
                AppUtils.showToast(data.error || 'Error al registrar el gasto', 'error');
            }
        }
    });
};

window.eliminarGasto = (id) => {
    AppUtils.confirmAction('¿Eliminar gasto?', 'Esta acción no se puede deshacer.', async () => {
        const response = await fetch(`${URLROOT}/gastos/eliminar/${id}`, { method: 'DELETE' });
        const data = await response.json();
        if (data.success) {
            AppUtils.showToast('Gasto eliminado');
            const tableEl = document.getElementById('expensesTable');
            await initExpenses(tableEl);
        } else {
            AppUtils.showToast(data.error || 'Error al eliminar el gasto', 'error');
        }
    });
};