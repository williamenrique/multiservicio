/**
 * Lógica del Módulo de Gastos
 */
let currentData = [];
let state = {
    page: 1,
    limit: 10,
    search: '',
    total: 0,
    filtered: 0
};

document.addEventListener('DOMContentLoaded', async () => {
    const searchInput = document.getElementById('searchExpenses');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                state.search = e.target.value;
                state.page = 1;
                loadExpenses();
            }, 500);
        });
    }

    loadExpenses();
});

async function loadExpenses() {
    try {
        const start = (state.page - 1) * state.limit;
        const params = new URLSearchParams({
            draw: 1,
            start: start,
            length: state.limit,
            'search[value]': state.search
        });

        const response = await fetch(`${URLROOT}/gastos/listar?${params.toString()}`);
        const result = await response.json();

        // Si el backend aún no devuelve la estructura de paginación, manejamos el array simple
        if (Array.isArray(result)) {
            currentData = result.slice(start, start + state.limit);
            state.total = result.length;
            state.filtered = result.length;
        } else {
            currentData = result.data;
            state.total = result.recordsTotal;
            state.filtered = result.recordsFiltered;
        }

        renderTable();
        renderControls();
    } catch (e) {
        console.error("Error inicializando tabla de gastos:", e);
        const tbody = document.querySelector('#expensesTable tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-red-500 font-bold uppercase tracking-widest">Error al cargar los gastos</td></tr>';
    }
}

function renderTable() {
    const tbody = document.querySelector('#expensesTable tbody');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (currentData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-20 text-slate-400 italic font-bold uppercase tracking-widest">No hay gastos registrados</td></tr>';
        return;
    }

    currentData.forEach(item => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-slate-50 transition-colors group border-b border-slate-100 animate-in fade-in duration-300';
        row.innerHTML = `
            <td class="px-8 py-5 text-[11px] font-bold text-slate-500 uppercase tracking-tighter align-middle">
                ${new Date(item.fecha).toLocaleDateString('es-CO', { dateStyle: 'medium' })}
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
        `;
        tbody.appendChild(row);
    });
    if (window.lucide) lucide.createIcons();
}

function renderControls() {
    const table = document.getElementById('expensesTable');
    if (!table) return;
    const wrapper = table.closest('div');

    let top = document.getElementById('custom-top-controls');
    if (!top) {
        top = document.createElement('div');
        top.id = 'custom-top-controls';
        top.className = 'flex flex-col sm:flex-row justify-between items-center gap-4 mb-6 px-8 py-4 bg-white/50 rounded-3xl border border-slate-100 shadow-sm mx-2';
        wrapper.parentNode.insertBefore(top, wrapper);
    }

    let bottom = document.getElementById('custom-bottom-controls');
    if (!bottom) {
        bottom = document.createElement('div');
        bottom.id = 'custom-bottom-controls';
        bottom.className = 'flex flex-col sm:flex-row justify-between items-center gap-6 mt-6 px-8 py-5 bg-white/50 rounded-3xl border border-slate-100 shadow-sm mx-2';
        wrapper.parentNode.insertBefore(bottom, wrapper.nextSibling);
    }

    top.innerHTML = `
        <div class="flex items-center gap-2 bg-slate-100/80 p-1.5 rounded-2xl border border-slate-200/60 shadow-inner select-none">
            <span class="pl-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Ver</span>
            <select onchange="window.updateLimit(this.value)" 
                class="bg-white border-none rounded-xl px-3 py-1.5 text-xs font-black text-navy-blue outline-none focus:ring-2 focus:ring-neon-green/50 cursor-pointer shadow-sm h-9 min-w-[80px] text-center transition-transform active:scale-95">
                ${[5, 10, 25, 50].map(v => `<option value="${v}" ${state.limit == v ? 'selected' : ''}>${v}</option>`).join('')}
            </select>
            <span class="pr-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Registros</span>
        </div>
    `;

    const start = (state.page - 1) * state.limit + 1;
    const end = Math.min(state.page * state.limit, state.filtered);
    const totalPages = Math.ceil(state.filtered / state.limit) || 1;

    bottom.innerHTML = `
        <div class="flex items-center gap-3">
            <div class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse shadow-[0_0_8px_rgba(239,68,68,0.5)]"></div>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none">
                Mostrando <span class="text-navy-blue text-xs ml-1">${start}-${end}</span> <span class="text-slate-300 mx-2 text-lg font-thin">|</span> Total <span class="text-navy-blue text-xs ml-1">${state.filtered}</span>
            </span>
        </div>
        <div class="flex items-center gap-1.5">
            <button onclick="window.changePage(${state.page - 1})" ${state.page === 1 ? 'disabled' : ''} 
                class="flex items-center justify-center w-10 h-10 rounded-2xl transition-all ${state.page === 1 ? 'text-slate-300 bg-slate-50 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-500 hover:bg-navy-blue hover:text-neon-green hover:border-navy-blue shadow-sm cursor-pointer'}">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
            <button onclick="window.changePage(${state.page + 1})" ${state.page >= totalPages ? 'disabled' : ''} 
                class="flex items-center justify-center w-10 h-10 rounded-2xl transition-all ${state.page >= totalPages ? 'text-slate-300 bg-slate-50 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-500 hover:bg-navy-blue hover:text-neon-green hover:border-navy-blue shadow-sm cursor-pointer'}">
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>
        </div>
    `;
    if (window.lucide) lucide.createIcons();
}

window.changePage = (p) => { if (p > 0) { state.page = p; loadExpenses(); } };
window.updateLimit = (l) => { state.limit = parseInt(l); state.page = 1; loadExpenses(); };

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
            const monto = parseFloat(document.getElementById('ex-amount').value.replace(',', '.'));
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
                loadExpenses();
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
            loadExpenses();
        } else {
            AppUtils.showToast(data.error || 'Error al eliminar el gasto', 'error');
        }
    });
};