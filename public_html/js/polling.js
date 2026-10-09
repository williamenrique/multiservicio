/**
 * POLLING MANAGER + DASHBOARD CACHE
 * Sistema unificado de polling para toda la aplicación.
 * 
 * v1.0.1 (2026-10-09):
 *   • FIX: La resolución de URLROOT ahora es defensiva. Antes usaba
 *     window.URLROOT, pero header.php define URLROOT con `const`, que
 *     NO crea una propiedad en window. Ahora intenta varias fuentes.
 * 
 * v1.0 (2026-10-09):
 *   • Centraliza todos los setInterval en un solo tick maestro.
 *   • Pausa automáticamente cuando la pestaña no está visible.
 *   • Cachea respuestas de /dashboard/getStats.
 */
(function() {
    'use strict';

    const TICK_INTERVAL = 5000;

    /**
     * Resolución defensiva de la URL base de la app.
     * Intenta en este orden:
     *   1. window.URLROOT (si algún script futuro lo expone)
     *   2. Variable global URLROOT (declarada con `const` en header.php)
     *   3. Cadena vacía (fallback — usa rutas relativas)
     */
    function getBaseUrl() {
        if (typeof window.URLROOT === 'string' && window.URLROOT) return window.URLROOT;
        try {
            if (typeof URLROOT !== 'undefined' && URLROOT) return URLROOT;
        } catch (e) { /* silencioso */ }
        return '';
    }

    const PollingManager = {
        tasks: [],
        running: false,
        timerId: null,

        register(name, fn, intervalMs) {
            this.tasks = this.tasks.filter(t => t.name !== name);
            this.tasks.push({ name, fn, interval: intervalMs, lastRun: 0 });
        },

        start() {
            if (this.running) return;
            this.running = true;

            // Ejecución inicial inmediata
            this.tasks.forEach(task => {
                task.lastRun = Date.now();
                this._runTask(task);
            });

            this.timerId = setInterval(() => this._tick(), TICK_INTERVAL);
        },

        stop() {
            if (this.timerId) {
                clearInterval(this.timerId);
                this.timerId = null;
            }
            this.running = false;
        },

        _runTask(task) {
            try {
                const result = task.fn();
                if (result && typeof result.catch === 'function') {
                    result.catch(() => {});
                }
            } catch (e) {
                // Fallo silencioso
            }
        },

        _tick() {
            if (document.hidden) return;

            const now = Date.now();
            this.tasks.forEach(task => {
                if (now - task.lastRun >= task.interval) {
                    task.lastRun = now;
                    this._runTask(task);
                }
            });
        }
    };

    // ════════════════════════════════════════════════════════════════
    // CACHÉ DE /dashboard/getStats
    // ════════════════════════════════════════════════════════════════
    const DashboardCache = {
        data: null,
        timestamp: 0,
        ttl: 25000,
        pending: null,

        async get(forceRefresh = false) {
            const now = Date.now();
            if (!forceRefresh && this.data && (now - this.timestamp) < this.ttl) {
                return this.data;
            }
            if (this.pending) return this.pending;

            this.pending = (async () => {
                try {
                    const baseUrl = getBaseUrl();
                    const res = await fetch(`${baseUrl}/dashboard/getStats`);
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const ct = res.headers.get('content-type');
                    if (!ct || !ct.includes('application/json')) {
                        throw new Error('Respuesta no JSON');
                    }
                    this.data = await res.json();
                    this.timestamp = Date.now();
                    return this.data;
                } catch (e) {
                    return this.data;
                } finally {
                    this.pending = null;
                }
            })();

            return this.pending;
        },

        invalidate() {
            this.data = null;
            this.timestamp = 0;
        }
    };

    // Exponer globalmente
    window.PollingManager = PollingManager;
    window.DashboardCache = DashboardCache;
})();