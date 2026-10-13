/**
 * DataTableRefactor - Motor unificado para tablas dinámicas segmentadas.
 * Reemplaza la dependencia de DataTables con un enfoque de alto rendimiento.
 * 
 * v2.0 (2026-10-09) — FIX bloque P2 (P2-05 + P2-06):
 *   • NUEVO: AbortController para cancelar requests obsoletos.
 *     Si el usuario teclea rápido en el buscador, la respuesta vieja ya no
 *     puede sobrescribir la nueva (evita race conditions).
 *   • NUEVO: Cache de ventana corta (2s) basada en hash del estado. Si se
 *     dispara reload() dos veces con el mismo estado, se reutiliza la
 *     respuesta en vez de refetchear.
 *   • NUEVO: reload(forceRefresh = true) para forzar bypass de caché.
 *   • La API pública permanece idéntica — ningún otro archivo requiere cambios.
 * 
 * P2-06 aclaración: El código ya usaba `innerHTML = data.map().join('')`
 * (una sola asignación). NO usaba `innerHTML +=` en bucle. No aplica cambio.
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

        // ─── FIX P2-05: cache de ventana corta + AbortController ───
        this._abortController = null;
        this._lastStateKey = '';
        this._lastResponse = null;
        this._lastResponseTime = 0;
        this._cacheWindow = 2000; // ms

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

    /**
     * Recarga los datos.
     * 
     * @param {boolean} forceRefresh - Si true, ignora la caché interna.
     *                                 Útil tras operaciones CRUD.
     */
    async reload(forceRefresh = false) {
        if (!this.tableBody) return;

        const offset = (this.state.page - 1) * this.state.limit;
        const dynamicParams = typeof this.getExtraParams === 'function' ? this.getExtraParams() : {};

        const params = new URLSearchParams({
            q: this.state.search,
            limit: this.state.limit,
            offset: offset,
            ...dynamicParams
        });

        // ─── FIX P2-05: Reusar cache si el estado es idéntico y reciente ───
        const stateKey = `${this.state.page}|${this.state.limit}|${this.state.search}|${JSON.stringify(dynamicParams)}`;
        if (!forceRefresh
            && stateKey === this._lastStateKey
            && this._lastResponse
            && (Date.now() - this._lastResponseTime) < this._cacheWindow) {
            this.render(this._lastResponse.data || []);
            this.updatePaginationUI(this._lastResponse.total || 0, this._lastResponse.totalFiltrados || 0);
            if (this.onDataLoaded) this.onDataLoaded(this._lastResponse);
            return;
        }

        // ─── FIX P2-05: Cancelar request anterior si sigue en vuelo ───
        if (this._abortController) {
            this._abortController.abort();
        }
        this._abortController = new AbortController();

        // Placeholder visual
        this.tableBody.innerHTML = `<tr><td colspan="20" class="px-8 py-12 text-center text-slate-400 italic animate-pulse">CARGANDO...</td></tr>`;

        try {
            const response = await fetch(`${this.endpoint}?${params.toString()}`, {
                signal: this._abortController.signal
            });
            const result = await response.json();

            if (result.success) {
                // Guardar en cache
                this._lastStateKey = stateKey;
                this._lastResponse = result;
                this._lastResponseTime = Date.now();

                this.render(result.data || []);
                this.updatePaginationUI(result.total || 0, result.totalFiltrados || 0);
                if (this.onDataLoaded) this.onDataLoaded(result);
            }
        } catch (error) {
            // ─── FIX P2-05: AbortError es esperado al cancelar; no mostrar error ───
            if (error && error.name === 'AbortError') {
                return;
            }
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