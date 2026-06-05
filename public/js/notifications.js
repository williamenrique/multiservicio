/**
 * Notification System - Gestión de alertas globales
 */
const AppNotifications = {
    init: () => {
        // AppNotifications.checkSupplierDebts();
        // setInterval(AppNotifications.checkSupplierDebts, 30000);
        //console.log("Sistema de notificaciones en espera de migración SQL...");
    },

    checkSupplierDebts: async () => {
        const purchases = await AppUtils.loadData('purchases_db'); // Await loadData
        const today = new Date(); // Declare today
        today.setHours(0, 0, 0, 0);

        // Filtrar deudas vencidas
        const overdue = purchases.filter(p => {
            const saldo = p.total - p.paid;
            return saldo > 0 && p.cutoff && new Date(p.cutoff) < today;
        });

        const container = document.getElementById('notifications-area');
        if (!container) return;

        if (overdue.length > 0) {
            const totalOverdue = overdue.reduce((sum, p) => sum + (p.total - p.paid), 0);

            container.innerHTML = `
                <div class="relative group flex items-center">
                    <!-- Punto de pulso vibrante -->
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                    
                    <!-- Icono de Campana Vibrante -->
                    <button onclick="showSection('proveedores'); AppNotifications.navigateToDebts();" 
                            class="p-2 bg-gray-800 rounded-lg text-error-red border border-red-900/30 hover:bg-red-900/20 transition-all alert-shake"
                            title="Click para ir a Cuentas por Pagar">
                        <i data-lucide="bell-ring" class="w-5 h-5"></i>
                    </button>

                    <!-- Tooltip / Información breve -->
                    <div class="hidden group-hover:block absolute top-full right-0 mt-2 w-64 bg-white shadow-2xl rounded-xl p-4 text-xs border border-red-100 z-[100] pointer-events-none">
                        <div class="flex items-center gap-2 mb-2 text-error-red font-bold">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                            <span>PAGOS VENCIDOS</span>
                        </div>
                        <p class="text-slate-600 mb-1">Tienes <b>${overdue.length}</b> facturas de proveedores con fecha de corte expirada.</p>
                        <p class="font-bold text-navy-blue">Total: ${AppUtils.formatCurrency(totalOverdue)}</p>
                        <div class="mt-2 text-[10px] text-slate-400 italic font-medium">Click para gestionar deudas</div>
                    </div>
                </div>
            `;
        } else {
            container.innerHTML = '';
        }
        lucide.createIcons();
    }
    ,
    navigateToDebts: () => {
        switchProveedorTab('deudas');
    }
};

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', AppNotifications.init);