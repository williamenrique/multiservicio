document.addEventListener('DOMContentLoaded', async () => {
    // Instancia unificada de la tabla de gastos
    window.handler_gastos = new DataTableRefactor({
        tableId: 'expensesTable',
        tableBodyId: 'expensesBody',
        endpoint: `${URLROOT}/gastos/listar`,
        searchInputId: 'searchExpenses',
        limitSelectorId: 'limitSelector',
        paginationId: 'paginationControls',
        totalId: 'totalCount',
        noDataMessage: 'No hay gastos registrados en esta fecha',
        getExtraParams: () => ({
            desde: document.getElementById('exp-desde')?.value,
            hasta: document.getElementById('exp-hasta')?.value
        }),
        renderRow: (item) => {
            // Evitar desfase de zona horaria reemplazando '-' por '/'
            const localDate = new Date(item.fecha.replace(/-/g, '\/'));
            return `
                <tr class="hover:bg-slate-50 transition-colors group border-b border-slate-100 animate-in fade-in duration-300">
                    <td class="px-8 py-5 text-[11px] font-bold text-slate-500 uppercase tracking-tighter align-middle">
                        ${localDate.toLocaleDateString('es-CO', { dateStyle: 'medium' })}
                    </td>
                    <td class="px-8 py-5 font-bold text-slate-700 uppercase tracking-tight align-middle">${item.descripcion}</td>
                    <td class="px-8 py-5 align-middle">
                        <span class="text-[10px] font-black bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg uppercase tracking-wider">${item.categoria}</span>
                    </td>
                    <td class="px-8 py-5 align-middle font-black text-rose-600">${AppUtils.formatCurrency(item.monto)}</td>
                    <td class="px-8 py-5 text-right align-middle">
                        <button onclick="eliminarGasto('${item.id}')" class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-red-500 text-slate-400 hover:text-white rounded-2xl transition-all shadow-sm ml-auto">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>`;
        }
    });

    // Listeners para actualizar la tabla automáticamente al cambiar el rango de fechas
    document.getElementById('exp-desde')?.addEventListener('change', () => window.handler_gastos.reload());
    document.getElementById('exp-hasta')?.addEventListener('change', () => window.handler_gastos.reload());
});

window.openExpenseModal = async function () { // Hacer la función asíncrona
    Swal.fire({
        title: 'Registrar Gasto del Taller',
        html: `
            <div class="text-left space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha</label>
                    <input id="ex-date" type="date" class="w-full p-2 border rounded-lg" value="${new Date().toISOString().split('T')[0]}">
                </div>
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
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Método de Pago</label>
                    <select id="ex-method" class="w-full p-2 border rounded-lg">
                        <option value="EFECTIVO">EFECTIVO (AFECTA CAJA)</option>
                        <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                    </select>
                </div>
            </div>`,
        confirmButtonColor: '#ff4444',
        confirmButtonText: '<span class="font-bold">Registrar Gasto</span>',
        showCancelButton: true,
        preConfirm: () => {
            const descripcion = document.getElementById('ex-desc').value.trim().toUpperCase();
            const monto = parseFloat(document.getElementById('ex-amount').value.replace(',', '.'));
            if (!descripcion || isNaN(monto) || monto <= 0) {
                return Swal.showValidationMessage('Complete todos los campos correctamente');
            }
            return {
                descripcion,
                monto,
                categoria: document.getElementById('ex-cat').value,
                metodo_pago: document.getElementById('ex-method').value,
                fecha: document.getElementById('ex-date').value
            };
        }
    }).then(async result => {
        if (result.isConfirmed) {
            AppUtils.showLoading('Procesando registro...');
            const response = await fetch(`${URLROOT}/gastos/guardar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(result.value)
            });
            const data = await response.json();
            AppUtils.hideLoading();
            if (data.success) {
                AppUtils.showToast('Gasto registrado');
                if (window.handler_gastos) window.handler_gastos.reload();
            } else {
                AppUtils.showToast(data.error || 'Error al registrar el gasto', 'error');
            }
        }
    });
};

window.imprimirGastosActuales = () => {
    const desde = document.getElementById('exp-desde')?.value || '';
    const hasta = document.getElementById('exp-hasta')?.value || '';
    const q = document.getElementById('searchExpenses')?.value || '';
    window.open(`${URLROOT}/reportes/imprimirGastos?desde=${desde}&hasta=${hasta}&q=${q}`, '_blank');
};

window.eliminarGasto = (id) => {
    AppUtils.confirmAction('¿Eliminar gasto?', 'Esta acción no se puede deshacer.', async () => {
        const response = await fetch(`${URLROOT}/gastos/eliminar/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await response.json();
        if (data.success) {
            AppUtils.showToast('Gasto eliminado');
            if (window.handler_gastos) handler_gastos.reload();
        } else {
            AppUtils.showToast(data.error || 'Error al eliminar el gasto', 'error');
        }
    });
};