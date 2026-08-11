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
        recentSalesTable: document.getElementById('salesBody'),
        draftsContainer: document.getElementById('pending-bills-dashboard'),
        supplierDebts: document.getElementById('supplier-debts-dashboard'),
        financialStatusCards: document.getElementById('financial-status-cards'),
        debtorsCard: document.getElementById('dashboard-debtors-card-container'),
        workshopStatus: document.getElementById('workshop-status-container'),
        topProducts: document.getElementById('top-products-container')
    };

    let performanceChart = null;

    // Vincular botón de exportación si existe
    const btnExport = document.getElementById('btnExportInventory');
    if (btnExport) {
        btnExport.innerHTML = '<i data-lucide="file-spreadsheet" class="w-4 h-4"></i> EXCEL';
        btnExport.addEventListener('click', () => exportInventoryToExcel());
    }

    /**
     * Permite retomar un borrador desde el dashboard
     */
    window.continuarVenta = (id_db) => {
        // Pasamos el ID por URL para no depender de LocalStorage
        window.location.href = `${URLROOT}/facturacion?id=${id_db}`;
    };

    /**
     * Carga las estadísticas desde el servidor
     */
    window.updateDashboard = async () => {
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
     * Verifica si el usuario actual tiene permisos de administrador.
     * Según tu definición: Rol 1 es Admin, Rol 2 es Mecánico.
     */
    const isAdmin = () => {
        const user = window.currentLoggedInUser || null;
        if (!user) return false;
        // Verificación robusta por ID (prioritario) o por nombre
        return parseInt(user.roleId) === 1 || user.role.toUpperCase() === 'ADMINISTRADOR';
    };

    /**
     * Oculta visualmente los contenedores financieros para usuarios no autorizados
     */
    const setupRoleVisibility = () => {
        const isUserAdmin = isAdmin();
        const financialElements = [
            statsElements.financialStatusCards,
            statsElements.expensesDashboard,
            statsElements.supplierDebts,
            document.getElementById('salesChart')?.closest('.glass-card'),
            statsElements.financialContainer,
            statsElements.debtorsCard,
            document.getElementById('financial-summary-heading'),
            document.getElementById('supplier-debts-heading'),
            document.getElementById('expenses-month-heading'),
            document.getElementById('financial-performance-block')
        ];

        financialElements.forEach(el => {
            if (el) {
                // IMPORTANTE: Ahora si es admin, quitamos 'hidden'. Si no, lo ponemos.
                if (isUserAdmin) {
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            }
        });
    };

    /**
     * Actualiza el DOM con los nuevos datos
     */
    const renderDashboard = (data) => {
        setupRoleVisibility();

        // 1. Renderizar Tarjetas Financieras (Ventas, Órdenes, Gastos)
        if (statsElements.financialStatusCards && isAdmin()) {
            const ingresos = parseFloat(data.ingresosHoy) || 0;
            const gastos = parseFloat(data.gastosMes) || 0;
            const ordenesActivas = data.drafts ? data.drafts.length : 0;

            const finCards = [
                {
                    label: 'Ventas Hoy',
                    value: AppUtils.formatCurrency(ingresos),
                    color: 'text-blue-600',
                    border: 'border-blue-600',
                    icon: 'trending-up'
                },
                {
                    label: 'Órdenes Activas',
                    value: ordenesActivas,
                    color: 'text-amber-600',
                    border: 'border-amber-600',
                    icon: 'clock'
                },
                {
                    label: 'Gastos del Mes',
                    value: AppUtils.formatCurrency(gastos),
                    color: 'text-rose-600',
                    border: 'border-rose-600',
                    icon: 'trending-down'
                }
            ];

            statsElements.financialStatusCards.innerHTML = finCards.map(c => `
                <div class="glass-card p-6 rounded-xl flex items-center justify-between border-l-4 ${c.border} transition-all">
                    <div class="pointer-events-none">
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">${c.label}</p>
                        <h3 class="text-3xl font-black ${c.color}">${c.value}</h3>
                    </div>
                    <i data-lucide="${c.icon}" class="${c.color} w-8 h-8 opacity-30"></i>
                </div>
            `).join('');
        }

        // 2. Renderizar Tarjetas de Inventario
        if (statsElements.inventoryContainer) {
            const invCards = [
                { label: 'Productos OK', value: data.inventory?.ok || 0, color: 'text-emerald-500', border: 'border-emerald-500', icon: 'check-circle', link: `${URLROOT}/inventario` },
                { label: 'Stock Crítico', value: data.inventory?.critico || 0, color: 'text-amber-500', border: 'border-amber-500', icon: 'alert-triangle', link: `${URLROOT}/inventario` },
                { label: 'Agotados', value: data.inventory?.agotado || 0, color: 'text-rose-500', border: 'border-rose-500', icon: 'alert-circle', link: `${URLROOT}/inventario` }
            ];

            statsElements.inventoryContainer.innerHTML = invCards.map(c => `
                <div onclick="window.location.href='${c.link}'" 
                     class="glass-card p-6 rounded-xl flex items-center justify-between border-l-4 ${c.border} cursor-pointer hover:shadow-lg hover:scale-[1.02] transition-all group">
                    <div class="pointer-events-none">
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">${c.label}</p>
                        <h3 class="text-3xl font-black ${c.color}">${c.value}</h3>
                    </div>
                    <i data-lucide="${c.icon}" class="${c.color} w-8 h-8 opacity-30"></i>
                </div>
            `).join('');
        }

        // 2.1 Renderizar Estado Operativo del Taller (Órdenes de Servicio)
        if (statsElements.workshopStatus) {
            const ws = data.workshopStatus || { recibidos: 0, reparacion: 0, listos: 0 };
            const totalActivas = (parseInt(ws.recibidos) || 0) + (parseInt(ws.reparacion) || 0) + (parseInt(ws.listos) || 0);

            statsElements.workshopStatus.innerHTML = `
                <div class="glass-card p-6 rounded-2xl border border-slate-100 h-full flex flex-col">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-navy-blue text-white rounded-lg"><i data-lucide="wrench" class="w-4 h-4"></i></div>
                        <h3 class="text-base font-black text-navy-blue uppercase tracking-tight">Estado del Taller</h3>
                    </div>
                    <div class="grid grid-cols-3 gap-4 flex-1">
                        <div class="text-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <p class="text-2xl font-black text-slate-700">${ws.recibidos || 0}</p>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Recibidos</p>
                        </div>
                        <div class="text-center p-3 bg-blue-50 rounded-xl border border-blue-100">
                            <p class="text-2xl font-black text-blue-600">${ws.reparacion || 0}</p>
                            <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest mt-1">En Proceso</p>
                        </div>
                        <div class="text-center p-3 bg-emerald-50 rounded-xl border border-emerald-100">
                            <p class="text-2xl font-black text-emerald-600">${ws.listos || 0}</p>
                            <p class="text-[9px] font-black text-emerald-400 uppercase tracking-widest mt-1">Listos</p>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">Total Vehículos:</span>
                        <span class="text-sm font-black text-navy-blue">${totalActivas} EN PATIO</span>
                    </div>
                </div>`;
        }

        // 2.2 Renderizar Ranking de Repuestos más vendidos
        if (statsElements.topProducts) {
            const topP = data.topProducts || [];
            statsElements.topProducts.innerHTML = `
                <div class="glass-card p-6 rounded-2xl border border-slate-100 h-full">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-amber-500 text-white rounded-lg"><i data-lucide="trophy" class="w-4 h-4"></i></div>
                        <h3 class="text-base font-black text-navy-blue uppercase tracking-tight">Top Ventas Repuestos</h3>
                    </div>
                    <div class="space-y-4">
                        ${topP.length === 0 ? '<p class="text-center text-slate-400 text-xs py-10 uppercase font-bold">Sin ventas este mes</p>' :
                    topP.map((p, idx) => `
                            <div class="flex items-center justify-between group">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <span class="text-xs font-black text-slate-300 w-4">${idx + 1}</span>
                                    <div class="overflow-hidden">
                                        <p class="text-sm font-bold text-slate-700 uppercase truncate group-hover:text-amber-600 transition-colors">${p.nombre}</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">${p.categoria}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-navy-blue">${p.total_vendido}</p>
                                    <p class="text-[9px] font-bold text-slate-300 uppercase">Unid.</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>`;
        }

        // 3. Limpiar el contenedor financiero duplicado (el que está al lado de la gráfica)
        if (statsElements.financialContainer && statsElements.financialContainer !== statsElements.inventoryContainer) {
            statsElements.financialContainer.innerHTML = '';
        }

        // 3. Renderizar Lista de Gastos Detallados (Tarjetas individuales)
        if (statsElements.expensesDashboard && data.recentExpenses && isAdmin()) {
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
                            <p class="text-xs text-slate-400 font-bold uppercase">${e.categoria}</p>
                            <h4 class="font-bold text-slate-800 uppercase text-base truncate group-hover:text-rose-600 transition-colors">${e.descripcion}</h4>
                            <p class="text-xs text-slate-400">${new Date(e.fecha).toLocaleDateString()}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="font-bold text-rose-600 text-xl">-${AppUtils.formatCurrency(parseFloat(e.monto) || 0)}</span>
                        </div>
                    </div>`).join('');
            }
        }

        // Gráfica de Rendimiento
        if (data.history && isAdmin()) renderChart(data.history);

        // Renderizar Borradores (Drafts)
        if (statsElements.draftsContainer && data.drafts) {
            if (data.drafts.length === 0) {
                statsElements.draftsContainer.innerHTML = `
                    <div class="col-span-full glass-card p-8 rounded-xl text-center text-slate-400">
                        <i data-lucide="clipboard-list" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                        <p class="italic font-medium">No hay facturas en proceso (borradores).</p>
                        <button onclick="window.location.href='${URLROOT}/facturacion'" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition-colors">
                            Crear Nueva Factura
                        </button>
                    </div>`;
            } else {
                statsElements.draftsContainer.innerHTML = data.drafts.map(draft => `
                <div class="glass-card p-4 rounded-xl border-l-4 border-amber-400">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex flex-col">
                            <span class="text-xs font-black bg-amber-100 text-amber-700 px-2 py-0.5 rounded w-fit mb-1">BORRADOR</span>
                            <span class="text-xs text-slate-400">${new Date(draft.fecha).toLocaleDateString()}</span>
                        </div>
                        <span class="text-sm text-slate-400">#${draft.id}</span>
                    </div>
                    <p class="font-bold text-slate-700 text-base truncate">${draft.cliente_nombre || 'Sin Cliente'}</p>
                    <div class="flex items-center gap-1 text-xs font-bold text-blue-600 uppercase mb-2">
                        <i data-lucide="user" class="w-3 h-3"></i>
                        <span>${draft.responsable_nombre || 'No asignado'}</span>
                    </div>
                    <p class="text-sm text-slate-500 mb-3">${draft.placa || 'Sin placa'} - ${draft.modelo_vehiculo || 'N/A'}</p>
                    <div class="flex justify-between items-center border-t pt-2">
                        <span class="text-base font-black text-navy-blue">${AppUtils.formatCurrency(draft.total)}</span>
                        <button onclick="continuarVenta(${draft.id})" class="text-xs font-bold text-blue-600 hover:underline">Continuar</button>
                    </div>
                </div>
            `).join('');
            }
        }

        // Renderizar Deudas Proveedores
        if (statsElements.supplierDebts && data.supplierDebts && isAdmin()) {
            if (data.supplierDebts.length === 0) {
                statsElements.supplierDebts.innerHTML = `
                    <div class="col-span-full glass-card p-8 rounded-2xl text-center text-slate-400 border-2 border-dashed border-slate-100">
                        <i data-lucide="check-circle" class="w-10 h-10 mx-auto mb-2 opacity-20 text-emerald-500"></i>
                        <p class="text-[10px] font-bold uppercase tracking-widest">Al día con proveedores</p>
                    </div>`;
            } else {
                const totalCarteraProv = data.supplierDebts.reduce((acc, d) => acc + parseFloat(d.saldo_pendiente), 0);

                statsElements.supplierDebts.innerHTML = `
                    <div class="glass-card rounded-2xl border border-slate-100 shadow-sm overflow-hidden col-span-full animate-in fade-in slide-in-from-bottom-2 duration-500">
                        <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-rose-600 text-white rounded-lg shadow-sm">
                                    <i data-lucide="truck" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-navy-blue uppercase tracking-tight">Cuentas por Pagar (Proveedores)</h3>
                                    <p class="text-sm text-slate-400 font-bold uppercase">${data.supplierDebts.length} Proveedores con saldo pendiente</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-black text-rose-600 leading-none">${AppUtils.formatCurrency(totalCarteraProv)}</p>
                                <p class="text-xs font-bold text-rose-400 uppercase tracking-widest mt-1">Total Deuda a Proveedores</p>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/30 text-xs font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                        <th class="px-6 py-3">Proveedor</th>
                                        <th class="px-6 py-3 text-center">Facturas</th>
                                        <th class="px-6 py-3 text-center">Próximo Vencimiento</th>
                                        <th class="px-6 py-3 text-right">Saldo Total</th>
                                        <th class="px-6 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    ${data.supplierDebts.map(d => {
                    const fechaRaw = d.proximo_vencimiento || d.vencimiento;
                    const vencimiento = fechaRaw ? new Date(fechaRaw.replace(/-/g, '\/')) : null;
                    const hoy = new Date();
                    const diasRestantes = vencimiento ? Math.ceil((vencimiento - hoy) / (1000 * 60 * 60 * 24)) : null;
                    const isCritical = diasRestantes !== null && diasRestantes <= 3;
                    const numFacturas = d.facturas_pendientes || d.cantidad_facturas || 1;

                    return `
                                        <tr class="hover:bg-slate-50/80 transition-colors group">
                                            <td class="px-6 py-4">
                                                <p class="text-base font-black text-slate-700 uppercase group-hover:text-rose-600 transition-colors">${d.nombre}</p>
                                                <p class="text-sm text-slate-400 font-bold uppercase tracking-tighter">${d.telefono || 'Sin teléfono'}</p>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="px-2 py-1 rounded bg-slate-100 text-slate-600 text-xs font-black">${numFacturas} FACT.</span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="text-sm font-bold ${isCritical ? 'text-rose-600' : 'text-slate-600'}">
                                                    ${vencimiento ? vencimiento.toLocaleDateString() : '---'}
                                                </span>
                                                <p class="text-[11px] font-black uppercase ${isCritical ? 'text-rose-400' : 'text-slate-300'}">
                                                    ${diasRestantes !== null ? (diasRestantes <= 0 ? 'Vencido' : `En ${diasRestantes} días`) : ''}
                                                </p>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <span class="text-base font-black text-rose-600">${AppUtils.formatCurrency(d.saldo_pendiente)}</span>
                                            </td>
                                            <td class="px-6 py-4 text-right w-20">
                                                <button onclick="window.location.href='${URLROOT}/proveedores?tab=deudas'" class="p-2 text-slate-300 hover:text-navy-blue transition-colors">
                                                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                                </button>
                                            </td>
                                        </tr>`;
                }).join('')}
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-3 bg-slate-50/50 border-t border-slate-100 text-center">
                            <a href="${URLROOT}/proveedores?tab=deudas" class="text-sm font-black text-blue-600 hover:underline uppercase tracking-widest">
                                Gestionar Cartera de Proveedores
                            </a>
                        </div>
                    </div>
                `;
            }
        }

        // Tabla de Actividad Reciente (Ventas)
        if (statsElements.recentSalesTable && data.recentSales) {
            statsElements.recentSalesTable.innerHTML = data.recentSales.map(sale => `
                <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50 transition-colors">
                    <td class="py-3 px-4 font-mono text-xs text-slate-400">#${sale.id}</td>
                    <td class="py-3 px-4 text-sm font-medium text-slate-700">${sale.cliente_nombre || 'Sin Cliente'}</td>
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
                        <button onclick="iniciarDevolucion(${sale.id}, '${sale.fecha}')" class="ml-2 p-1 text-slate-400 hover:text-rose-600 transition-colors" title="Devolución">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </button>
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
                        tooltip: { backgroundColor: '#000000', titleFont: { size: 13 }, bodyFont: { size: 12 }, padding: 12, cornerRadius: 10 }
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

    /**
     * Exporta el inventario actual a formato CSV (Excel compatible)
     */
    window.exportInventoryToExcel = async () => {
        const res = await fetch(`${URLROOT}/inventario/listar`);
        const data = await res.json();
        let csv = "\uFEFFID;Nombre;Categoria;Stock;Precio\n"; // BOM para acentos en Excel
        data.forEach(i => {
            csv += `${i.id};${i.nombre};${i.categoria};${i.stock};${i.precio}\n`;
        });
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.setAttribute("download", "Inventario_TallerPro.csv");
        link.click();
        AppUtils.showToast('Inventario exportado');
    };

    // Inicializar y configurar refresco automático cada 60 segundos
    updateDashboard();
    setInterval(updateDashboard, 60000);

    // Re-renderizar si la información del usuario llega después de la carga inicial (Race condition)
    document.addEventListener('userLoaded', () => {
        updateDashboard();
    });
});