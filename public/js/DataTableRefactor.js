/**
 * DataTableRefactor - Motor unificado para tablas dinámicas segmentadas.
 * Reemplaza la dependencia de DataTables con un enfoque de alto rendimiento.
 */
class DataTableRefactor {
    constructor(config) {
        this.tableId = config.tableId;
        this.tableBody = document.getElementById(config.tableBodyId);
        this.endpoint = config.endpoint;
        this.renderRow = config.renderRow;
        this.limitSelector = document.getElementById(config.limitSelectorId) || document.getElementById('limitSelector');
        this.searchInput = document.getElementById(config.searchInputId) || document.getElementById('searchTable');
        this.paginationContainer = document.getElementById(config.paginationId) || document.getElementById('paginationControls');

        this.displayTotal = document.getElementById(config.totalId) || document.getElementById('totalItemsDisplay');
        this.displayStart = document.getElementById(config.startId) || document.getElementById('startIndex');
        this.displayEnd = document.getElementById(config.endId) || document.getElementById('endIndex');

        this.noDataMessage = config.noDataMessage || 'No se encontraron registros';
        this.getExtraParams = config.getExtraParams || null;

        this.state = {
            page: 1,
            limit: parseInt(this.limitSelector?.value) || 10,
            search: ''
        };

        if (!this.tableBody) {
            console.warn(`DataTableRefactor: No se encontró el cuerpo de tabla #${config.tableBodyId}`);
            return;
        }

        this.onDataLoaded = config.onDataLoaded || null;

        this.searchTimer = null;
        window[`handler_${this.tableId}`] = this; // Referencia global para eventos HTML
        this.init();
    }

    init() {
        this.limitSelector?.addEventListener('change', (e) => {
            this.state.limit = parseInt(e.target.value);
            this.state.page = 1;
            this.reload();
        });

        this.searchInput?.addEventListener('input', (e) => {
            this.state.search = e.target.value.trim();
            this.state.page = 1;
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => this.reload(), 400);
        });

        this.reload();
    }

    async reload() {
        if (!this.tableBody) return;
        this.tableBody.innerHTML = `<tr><td colspan="20" class="px-8 py-12 text-center text-slate-400 italic animate-pulse">CARGANDO...</td></tr>`;

        const offset = (this.state.page - 1) * this.state.limit;
        const dynamicParams = typeof this.getExtraParams === 'function' ? this.getExtraParams() : {};

        const params = new URLSearchParams({
            q: this.state.search,
            limit: this.state.limit,
            offset: offset,
            ...dynamicParams
        });

        try {
            const response = await fetch(`${this.endpoint}?${params.toString()}`);
            const result = await response.json();

            if (result.success) {
                this.render(result.data || []);
                this.updatePaginationUI(result.total || 0, result.totalFiltrados || 0);
                if (this.onDataLoaded) this.onDataLoaded(result);
            }
        } catch (error) {
            console.error(`Error en tabla ${this.tableId}:`, error);
            this.tableBody.innerHTML = `<tr><td colspan="20" class="text-center py-8 text-red-500 font-bold">Error de conexión</td></tr>`;
        }
    }

    render(data) {
        this.tableBody.innerHTML = data.length === 0
            ? `<tr><td colspan="100%" class="px-8 py-24 text-center text-slate-400 italic font-bold uppercase tracking-widest bg-slate-50/30 border-none">${this.noDataMessage}</td></tr>`
            : data.map(item => this.renderRow(item)).join('');
        if (window.lucide) lucide.createIcons();
        if (window.initGlobalTooltips) window.initGlobalTooltips();
    }

    updatePaginationUI(total, filtered) {
        const totalPages = Math.ceil(filtered / this.state.limit) || 1;
        const start = filtered === 0 ? 0 : (this.state.page - 1) * this.state.limit + 1;
        const end = Math.min(this.state.page * this.state.limit, filtered);

        if (this.displayTotal) this.displayTotal.textContent = filtered;
        if (this.displayStart) this.displayStart.textContent = start;
        if (this.displayEnd) this.displayEnd.textContent = end;

        if (this.paginationContainer) {
            this.paginationContainer.innerHTML = totalPages > 1 ? `
                <button onclick="handler_${this.tableId}.changePage(${this.state.page - 1})" ${this.state.page === 1 ? 'disabled' : ''} class="p-2 border rounded-lg hover:bg-slate-50 disabled:opacity-30"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                <span class="px-4 text-[10px] font-black text-navy-blue uppercase">Página ${this.state.page} / ${totalPages}</span>
                <button onclick="handler_${this.tableId}.changePage(${this.state.page + 1})" ${this.state.page === totalPages ? 'disabled' : ''} class="p-2 border rounded-lg hover:bg-slate-50 disabled:opacity-30"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
            ` : '';
            if (window.lucide) lucide.createIcons();
        }
    }

    changePage(page) { this.state.page = page; this.reload(); }
}