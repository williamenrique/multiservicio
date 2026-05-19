/**
 * Lógica del Dashboard en Tiempo Real
 */
document.addEventListener('DOMContentLoaded', () => {
    // GUARD: Si no estamos en la sección de dashboard, no ejecutar nada.
    // Esto evita que el setInterval y las peticiones corran en otras páginas.
    if (!document.getElementById('sec-dashboard')) return;

    const statsElements = {
        financialContainer: document.getElementById('financial-cards'),
        inventoryContainer: document.getElementById('dashboard-cards'),
        expensesDashboard: document.getElementById('expenses-dashboard'),
        stockOk: document.getElementById('stockOk'),
        stockCritico: document.getElementById('stockCritico'),
        stockAgotado: document.getElementById('stockAgotado'),
        recentSalesTable: document.getElementById('salesBody'),
        draftsContainer: document.getElementById('pending-bills-dashboard'),
        supplierDebts: document.getElementById('supplier-debts-dashboard')
    };

    let performanceChart = null;

    /**
     * Carga las estadísticas desde el servidor
     */
    const updateDashboard = async () => {
        try {
            const response = await fetch(`${URLROOT}/dashboard/getStats`);
            if (!response.ok) throw new Error('Error al obtener datos');

            const data = await response.json();
            renderDashboard(data);
        } catch (error) {
            console.error('Error actualizando Dashboard:', error);
        }
    };

    /**
     * Actualiza el DOM con los nuevos datos
     */
    const renderDashboard = (data) => {
        // 1. Actualizar Tarjetas de Inventario (Si existen en el DOM)
        if (statsElements.stockOk) statsElements.stockOk.textContent = data.inventory.ok || 0;
        if (statsElements.stockCritico) statsElements.stockCritico.textContent = data.inventory.critico || 0;
        if (statsElements.stockAgotado) statsElements.stockAgotado.textContent = data.inventory.agotado || 0;

        // 2. Renderizar Cuadrícula Financiera (Ingresos, Gastos, Balance)
        if (statsElements.financialContainer) {
            const ingresos = parseFloat(data.ingresosHoy) || 0;
            const gastos = parseFloat(data.gastosMes) || 0;
            const balance = ingresos - gastos;

            const cards = [
                {
                    label: 'Ingresos de Hoy',
                    value: AppUtils.formatCurrency(ingresos),
                    color: 'text-blue-600',
                    border: 'border-blue-600',
                    icon: 'trending-up'
                },
                {
                    label: 'Gastos del Mes',
                    value: AppUtils.formatCurrency(gastos),
                    color: 'text-rose-600',
                    border: 'border-rose-600',
                    icon: 'trending-down'
                },
                {
                    label: 'Balance Neto',
                    value: AppUtils.formatCurrency(balance),
                    color: balance >= 0 ? 'text-emerald-600' : 'text-rose-700',
                    border: balance >= 0 ? 'border-emerald-600' : 'border-rose-700',
                    icon: 'wallet'
                }
            ];

            statsElements.financialContainer.innerHTML = cards.map(c => `
                <div class="glass-card p-4 rounded-xl flex items-center justify-between border-l-4 ${c.border}">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">${c.label}</p>
                        <h3 class="text-xl font-black ${c.color}">${c.value}</h3>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg">
                        <i data-lucide="${c.icon}" class="${c.color} w-6 h-6 opacity-70"></i>
                    </div>
                </div>
            `).join('');
        }

        // 3. Renderizar Lista de Gastos Detallados (Tarjetas individuales)
        if (statsElements.expensesDashboard && data.recentExpenses) {
            if (data.recentExpenses.length === 0) {
                statsElements.expensesDashboard.innerHTML = `
                    <div class="col-span-full glass-card p-8 rounded-xl text-center text-slate-400">
                        <i data-lucide="wallet" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                        <p class="italic font-medium">No hay gastos registrados este mes.</p>
                    </div>`;
            } else {
                statsElements.expensesDashboard.innerHTML = data.recentExpenses.map(e => `
                    <div class="glass-card p-4 rounded-xl border-l-4 border-rose-500 flex justify-between items-center group hover:scale-[1.02] transition-transform cursor-default">
                        <div class="truncate mr-4">
                            <p class="text-[10px] text-slate-400 font-bold uppercase">${e.categoria}</p>
                            <h4 class="font-bold text-slate-800 uppercase text-sm truncate group-hover:text-rose-600 transition-colors">${e.descripcion}</h4>
                            <p class="text-[10px] text-slate-400">${new Date(e.fecha).toLocaleDateString()}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="font-bold text-rose-600 text-lg">-${AppUtils.formatCurrency(parseFloat(e.monto) || 0)}</span>
                        </div>
                    </div>`).join('');
            }
        }

        // Gráfica de Rendimiento
        if (data.history) renderChart(data.history);

        // Renderizar Borradores (Drafts)
        if (statsElements.draftsContainer && data.drafts) {
            statsElements.draftsContainer.innerHTML = data.drafts.map(draft => `
                <div class="glass-card p-4 rounded-xl border-l-4 border-amber-400">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-[10px] font-black bg-amber-100 text-amber-700 px-2 py-0.5 rounded">BORRADOR</span>
                        <span class="text-xs text-slate-400">#${draft.id}</span>
                    </div>
                    <p class="font-bold text-slate-700 text-sm truncate">${draft.cliente_nombre || 'Consumidor Final'}</p>
                    <p class="text-xs text-slate-500 mb-3">${draft.placa || 'Sin placa'} - ${draft.modelo_vehiculo || 'N/A'}</p>
                    <div class="flex justify-between items-center border-t pt-2">
                        <span class="text-sm font-black text-navy-blue">${AppUtils.formatCurrency(draft.total)}</span>
                        <button onclick="continuarVenta(${draft.id})" class="text-xs font-bold text-blue-600 hover:underline">Continuar</button>
                    </div>
                </div>
            `).join('');
        }

        // Renderizar Deudas Proveedores
        if (statsElements.supplierDebts && data.supplierDebts) {
            statsElements.supplierDebts.innerHTML = data.supplierDebts.map(debt => `
                <div class="glass-card p-4 rounded-xl">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">${debt.nombre}</p>
                    <p class="text-lg font-black text-rose-500">${AppUtils.formatCurrency(debt.saldo_pendiente)}</p>
                </div>
            `).join('');
        }

        // Tabla de Actividad Reciente (Ventas)
        if (statsElements.recentSalesTable && data.recentSales) {
            statsElements.recentSalesTable.innerHTML = data.recentSales.map(sale => `
                <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50 transition-colors">
                    <td class="py-3 px-4 font-mono text-xs text-slate-400">#${sale.id}</td>
                    <td class="py-3 px-4 text-sm font-medium text-slate-700">${sale.cliente_nombre || 'Consumidor Final'}</td>
                    <td class="py-3 px-4">
                        <span class="text-xs font-bold text-slate-600 block">${sale.modelo_vehiculo || 'N/A'}</span>
                        <span class="text-[10px] text-slate-400">${sale.placa || '---'}</span>
                    </td>
                    <td class="py-3 px-4 text-right">
                        <span class="text-sm font-black text-navy-blue">${AppUtils.formatCurrency(sale.total)}</span>
                    </td>
                    <td class="py-3 px-4 text-right">
                        <span class="px-2 py-1 rounded-full text-[10px] font-bold ${getStatusClass(sale.status)}">
                            ${sale.status}
                        </span>
                    </td>
                </tr>
            `).join('');
        }

        // Reinicializar iconos de Lucide para los elementos inyectados
        if (window.lucide) lucide.createIcons();
    };

    /**
     * Lógica para la gráfica de Rendimiento Financiero
     */
    const renderChart = (history) => {
        const canvas = document.getElementById('salesChart');
        if (!canvas) return;

        const container = canvas.parentElement;
        let noDataMsg = document.getElementById('chart-no-data');

        // Verificar si hay algún dato significativo en el periodo
        const hasData = history.some(d => d.income > 0 || d.expenses > 0);

        if (!hasData) {
            canvas.style.display = 'none';
            if (!noDataMsg) {
                noDataMsg = document.createElement('div');
                noDataMsg.id = 'chart-no-data';
                noDataMsg.className = 'flex flex-col items-center justify-center h-64 text-slate-400 bg-slate-50/50 rounded-xl border-2 border-dashed border-slate-200';
                noDataMsg.innerHTML = `
                    <i data-lucide="bar-chart-3" class="w-12 h-12 mb-2 opacity-10"></i>
                    <p class="font-bold text-xs uppercase tracking-widest text-center px-4">Sin actividad financiera en los últimos 7 días</p>
                `;
                container.appendChild(noDataMsg);
                if (window.lucide) lucide.createIcons();
            } else {
                noDataMsg.classList.remove('hidden');
            }
            return;
        }

        canvas.style.display = 'block';
        if (noDataMsg) noDataMsg.classList.add('hidden');

        const labels = history.map(d => d.day);
        const incomeData = history.map(d => d.income);
        const expensesData = history.map(d => d.expenses);

        if (performanceChart) {
            performanceChart.data.labels = labels;
            performanceChart.data.datasets[0].data = incomeData;
            performanceChart.data.datasets[1].data = expensesData;
            performanceChart.update();
        } else if (window.Chart) {
            performanceChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Ingresos', data: incomeData, borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: true, tension: 0.4, borderWidth: 3, pointRadius: 4 },
                        { label: 'Egresos', data: expensesData, borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', fill: true, tension: 0.4, borderWidth: 3, pointRadius: 4 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11, weight: '600' }, padding: 20 } },
                        tooltip: { backgroundColor: '#1e293b', titleFont: { size: 13 }, bodyFont: { size: 12 }, padding: 12, cornerRadius: 10 }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.03)' },
                            ticks: {
                                callback: value => AppUtils.formatCurrency(value).replace(',00', ''),
                                font: { size: 10, weight: '500' },
                                color: '#94a3b8'
                            }
                        },
                        x: { grid: { display: false }, ticks: { font: { size: 10, weight: '500' }, color: '#94a3b8' } }
                    }
                }
            });
        }
    };

    const getStatusClass = (status) => {
        const classes = {
            'COMPLETADO': 'bg-emerald-100 text-emerald-600',
            'PENDIENTE': 'bg-amber-100 text-amber-600',
            'CANCELADO': 'bg-rose-100 text-rose-600'
        };
        return classes[status] || 'bg-slate-100 text-slate-600';
    };

    // Inicializar y configurar refresco automático cada 60 segundos
    updateDashboard();
    setInterval(updateDashboard, 60000);
});