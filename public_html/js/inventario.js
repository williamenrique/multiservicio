/**
 * inventario.js
 * 
 * v3.0 (2026-10-21) — CONDICIÓN + VISIBILIDAD CATÁLOGO.
 * 
 * v3.2 (2026-10-21) — COMPACTACIÓN MODERADA.
 * 
 * v3.3 (2026-10-21) — AJUSTE FINAL:
 *   • Fuente un escalón arriba (base text-sm, headers text-[11px]).
 *   • Thumbnail w-10 → w-12.
 *   • Switch de catálogo con feedback visual claro:
 *       - ACTIVO: verde neón con glow + ícono eye abierto.
 *       - INACTIVO: gris apagado + ícono eye-off.
 *   • Toggle envuelto en un pill con label "VISIBLE" / "OCULTO" para lectura rápida.
 *   • Se mantienen las 12 columnas, nada se fusiona ni desaparece.
 */
document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('tableBody');
    const form = document.getElementById('formInventory');
    const modal = document.getElementById('inventoryModal');
    const searchInput = document.getElementById('searchInventory');
    const totalCount = document.getElementById('totalCount');
    const fileInput = document.getElementById('fileInput');
    const imagePreview = document.getElementById('imagePreview');

    // ─── FIX P2-07: Lazy load de imágenes en la tabla ───
    // ─── FIX P2-10: Validación unificada de subida (AppUtils) ───

    window.handler_inventario = new DataTableRefactor({
        tableId: 'inventario',
        tableBodyId: 'tableBody',
        endpoint: `${URLROOT}/inventario/listar`,
        searchInputId: 'searchInventory',
        limitSelectorId: 'limitSelector',
        paginationId: 'paginationControls',
        totalId: 'totalCount',
        onDataLoaded: (res) => {
            window.currentData = res.data;
            if (window.lucide) lucide.createIcons();
        },
        renderRow: (item) => {
            const isLow = item.stock <= (item.stock_minimo || 5);

            const cleanPath = item.imagen ? item.imagen.trim() : null;
            const isDataUri = cleanPath && cleanPath.toLowerCase().startsWith('data:');
            const isRemote = cleanPath && cleanPath.toLowerCase().startsWith('http');
            const imgUrl = (isDataUri || isRemote) ? cleanPath : (cleanPath ? `${URLROOT}/${cleanPath}` : null);

            const stockDisponible = parseFloat(item.stock_disponible ?? item.stock) || 0;
            const stockFisico = parseFloat(item.stock) || 0;
            const reservado = Math.max(0, stockFisico - stockDisponible);

            const stockMinimo = parseFloat(item.stock_minimo) || 5;
            let disponibleColorClass;
            if (stockDisponible <= 0) {
                disponibleColorClass = 'text-red-600 font-black';
            } else if (stockDisponible <= stockMinimo) {
                disponibleColorClass = 'text-amber-600 font-black';
            } else {
                disponibleColorClass = 'text-slate-700 font-bold';
            }

            const bloqueReserva = reservado > 0
                ? `<span class="text-[9px] text-orange-500 font-black uppercase tracking-tight">+${reservado} res.</span>`
                : '';

            const tieneOferta = item.oferta_activa && item.oferta_porcentaje > 0;
            const ofertaVigente = tieneOferta &&
                (!item.oferta_fecha_inicio || new Date(item.oferta_fecha_inicio) <= new Date()) &&
                (!item.oferta_fecha_fin || new Date(item.oferta_fecha_fin) >= new Date());
            const precioFinal = ofertaVigente
                ? Math.round(item.precio * (1 - item.oferta_porcentaje / 100) * 100) / 100
                : item.precio;

            // ─── v3.0: condición del producto ───
            const condicion = (item.condicion || 'NUEVO').toUpperCase();
            const condClass = condicion === 'USADO'
                ? 'bg-amber-100 text-amber-700'
                : 'bg-emerald-100 text-emerald-700';
            const condBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase ${condClass}">${condicion}</span>`;

            // ─── v3.3: toggle de visibilidad con feedback visual claro ───
            const visible = parseInt(item.visible_catalogo) === 1;
            const canEdit = (typeof USER_ROLE !== 'undefined' && USER_ROLE === 'ADMINISTRADOR');

            let toggleVisibilidad;
            if (canEdit) {
                const trackClass = visible
                    ? 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.6)]'
                    : 'bg-slate-300';
                const thumbClass = visible ? 'translate-x-[18px]' : 'translate-x-[2px]';
                const pillLabel = visible
                    ? '<span class="text-[9px] font-black text-emerald-600 uppercase tracking-tight">VISIBLE</span>'
                    : '<span class="text-[9px] font-black text-slate-400 uppercase tracking-tight">OCULTO</span>';
                const iconVisible = visible
                    ? '<i data-lucide="eye" class="w-3 h-3 text-emerald-600"></i>'
                    : '<i data-lucide="eye-off" class="w-3 h-3 text-slate-400"></i>';

                toggleVisibilidad = `
                    <div class="flex flex-col items-center gap-1">
                        <button type="button"
                                onclick="toggleVisibilidadCatalogo(${item.id}, this)"
                                data-visible="${visible ? 1 : 0}"
                                class="relative inline-flex items-center w-10 h-5 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-emerald-400 ${trackClass}"
                                title="${visible ? 'Ocultar del catálogo público' : 'Publicar en catálogo público'}">
                            <span class="absolute top-[2px] left-0 w-4 h-4 bg-white rounded-full shadow-md transition-transform duration-300 ${thumbClass}"></span>
                        </button>
                        <div class="flex items-center gap-1">${iconVisible}${pillLabel}</div>
                    </div>
                `;
            } else {
                toggleVisibilidad = `
                    <div class="flex flex-col items-center gap-1">
                        ${visible
                            ? '<i data-lucide="eye" class="w-4 h-4 text-emerald-600"></i>'
                            : '<i data-lucide="eye-off" class="w-4 h-4 text-slate-400"></i>'}
                        <span class="text-[9px] font-black uppercase ${visible ? 'text-emerald-600' : 'text-slate-400'}">${visible ? 'VISIBLE' : 'OCULTO'}</span>
                    </div>
                `;
            }

            // ─── FIX P2-07: imagen con lazy loading ───
            return `
                <tr class="hover:bg-slate-50 transition-colors group border-b border-slate-100 animate-in fade-in duration-300 ${ofertaVigente ? 'bg-amber-50/50' : ''}">
                    <td class="px-3 py-3 align-middle">
                        <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200 ${imgUrl ? 'cursor-zoom-in hover:opacity-80 transition-all shadow-sm' : ''}" 
                             ${imgUrl ? `onclick="AppUtils.viewImage(this.querySelector('img').src, '${item.nombre.replace(/'/g, "\\'")}')"` : ''}>
                            ${imgUrl ? `<img src="${imgUrl}" loading="lazy" decoding="async" class="w-full h-full object-cover">` : `<i data-lucide="image" class="w-5 h-5 text-slate-400"></i>`}
                        </div>
                    </td>
                    <td class="px-3 py-3 font-mono text-xs font-bold text-slate-500 uppercase tracking-tight align-middle">${item.codigo || '-'}</td>
                    <td class="px-3 py-3 font-bold text-sm text-slate-700 uppercase tracking-tight align-middle">${item.nombre}</td>
                    <td class="px-3 py-3 align-middle">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-tight">${item.marca || '-'}</span>
                    </td>
                    <td class="px-3 py-3 align-middle">
                        <span class="text-[10px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded-md uppercase tracking-wider">${item.categoria}</span>
                    </td>
                    <td class="px-3 py-3 align-middle">${condBadge}</td>
                    <td class="px-3 py-3 align-middle font-mono text-sm">
                        <div class="flex flex-col gap-0.5" style="width: 90px;">
                            <span class="${disponibleColorClass}">${stockDisponible} UND</span>
                            ${bloqueReserva}
                        </div>
                    </td>
                    <td class="px-3 py-3 align-middle font-black text-sm text-right">
                        ${ofertaVigente ? `
                            <div class="flex flex-col items-end">
                                <span class="text-navy-blue line-through text-[11px]">${AppUtils.formatCurrency(item.precio)}</span>
                                <span class="text-amber-600 font-extrabold text-sm">${AppUtils.formatCurrency(precioFinal)}</span>
                                <span class="text-[9px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-black uppercase">${item.oferta_porcentaje}% OFF</span>
                            </div>
                        ` : `
                            <span class="text-navy-blue">${AppUtils.formatCurrency(item.precio)}</span>
                        `}
                    </td>
                    <td class="px-3 py-3 align-middle text-center">${toggleVisibilidad}</td>
                    <td class="px-3 py-3 align-middle text-center">
                        ${ofertaVigente ? `
                            <div class="flex flex-col items-center gap-0.5">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-amber-100 text-amber-800 animate-pulse">
                                    <i data-lucide="tag" class="w-2.5 h-2.5 inline mr-0.5"></i> OFERTA
                                </span>
                                <span class="text-[9px] text-amber-700 font-bold leading-tight">
                                    ${item.oferta_fecha_inicio ? `${new Date(item.oferta_fecha_inicio).toLocaleDateString('es-ES')}` : 'Activa'}
                                    ${item.oferta_fecha_fin ? `→${new Date(item.oferta_fecha_fin).toLocaleDateString('es-ES')}` : ''}
                                </span>
                            </div>
                        ` : `
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-slate-100 text-slate-500">
                                SIN OFERTA
                            </span>
                        `}
                    </td>
                    <td class="px-3 py-3 align-middle text-center">
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase ${isLow ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'}">
                            ${isLow ? (item.stock == 0 ? 'AGOTADO' : 'CRÍTICO') : 'OK'}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-right align-middle">
                        ${canEdit ? `
                            <div class="flex justify-end gap-1">
                                <a href="${URLROOT}/inventario/kardex/${item.id}" class="flex items-center justify-center w-8 h-8 bg-slate-100 hover:bg-navy-blue text-slate-500 hover:text-neon-green rounded-lg transition-all shadow-sm" title="Ver Kardex">
                                    <i data-lucide="history" class="w-3.5 h-3.5"></i>
                                </a>
                                <button onclick="editItem(${item.id})" class="flex items-center justify-center w-8 h-8 bg-slate-100 hover:bg-neon-green text-slate-500 hover:text-black rounded-lg transition-all shadow-sm" title="Editar Producto">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                </button>
                                <button onclick="openOfertaModal(${item.id})" class="flex items-center justify-center w-8 h-8 bg-slate-100 hover:bg-amber-500 text-slate-500 hover:text-white rounded-lg transition-all shadow-sm ${item.stock <= 1 ? 'opacity-50 cursor-not-allowed' : ''}" title="${item.stock <= 1 ? 'Stock insuficiente para oferta (mín. 2 unidades)' : 'Configurar Oferta'}">
                                    <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                                </button>
                                <button onclick="deleteItem(${item.id})" class="flex items-center justify-center w-8 h-8 bg-slate-100 hover:bg-red-500 text-slate-500 hover:text-white rounded-lg transition-all shadow-sm" title="Eliminar Producto">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        ` : '<span class="text-[9px] italic text-slate-400 font-bold uppercase tracking-widest">Solo lectura</span>'}
                    </td>
                </tr>`;
        }
    });

    // ─── v3.3: Toggle de visibilidad con feedback visual claro ───
    // Recibe el botón (no el checkbox) y actualiza los estilos en tiempo real
    // si la operación tiene éxito, o revierte si falla.
    window.toggleVisibilidadCatalogo = async (id, btnEl) => {
        const visibleActual = parseInt(btnEl.dataset.visible) === 1;
        const nuevoEstado = visibleActual ? 0 : 1;
        btnEl.disabled = true;
        btnEl.classList.add('opacity-60', 'cursor-wait');

        try {
            const res = await fetch(`${URLROOT}/inventario/actualizar-visibilidad`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({ id: id, visible_catalogo: nuevoEstado })
            });

            const contentType = res.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error('Respuesta inválida del servidor');
            }

            const result = await res.json();
            if (result.success) {
                // Actualizar memoria
                const item = (window.currentData || []).find(i => parseInt(i.id) === parseInt(id));
                if (item) item.visible_catalogo = nuevoEstado;

                // Re-renderizar la fila completa para que el switch muestre el nuevo estado
                if (window.handler_inventario && typeof window.handler_inventario.reload === 'function') {
                    window.handler_inventario.reload();
                }
                AppUtils.showToast(result.mensaje || (nuevoEstado ? 'Publicado en catálogo' : 'Oculto del catálogo'));
            } else {
                AppUtils.showToast(result.mensaje || 'No se pudo actualizar la visibilidad', 'error');
            }
        } catch (e) {
            AppUtils.showToast('Error de conexión al actualizar visibilidad', 'error');
        } finally {
            btnEl.disabled = false;
            btnEl.classList.remove('opacity-60', 'cursor-wait');
        }
    };

    // ─── FIX P2-10: Previsualización de imagen validada ───
    if (fileInput && imagePreview) {
        AppUtils.setupImagePreview(fileInput, imagePreview);
    }

    document.getElementById('prodImagen').addEventListener('input', (e) => {
        const url = e.target.value.trim();
        const isRemote = url.toLowerCase().startsWith('http') || url.toLowerCase().startsWith('data:');
        if (isRemote) {
            imagePreview.innerHTML = `<img src="${url}" class="w-full h-full object-cover">`;
        } else if (url === '') {
            imagePreview.innerHTML = '<i data-lucide="image" class="w-8 h-8 text-slate-300"></i>';
            lucide.createIcons();
        }
    });

    // Submit del formulario
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();

        const btnSave = form.querySelector('button[type="submit"]');
        const originalText = btnSave.innerHTML;

        btnSave.disabled = true;
        btnSave.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i>';
        if (window.lucide) lucide.createIcons();

        const formData = new FormData(form);

        formData.set('codigo', document.getElementById('prodCodigo').value.trim().toUpperCase());
        formData.set('nombre', document.getElementById('prodNombre').value.trim().toUpperCase());
        formData.set('marca', document.getElementById('prodMarca').value.trim().toUpperCase());
        formData.set('descripcion', document.getElementById('prodDescripcion').value.trim().toUpperCase());
        formData.set('categoria', document.getElementById('prodCategoria').value.trim().toUpperCase());
        formData.set('condicion', (document.getElementById('prodCondicion').value || 'NUEVO').toUpperCase());

        if (!formData.has('visible_catalogo')) {
            formData.set('visible_catalogo', '0');
        } else {
            formData.set('visible_catalogo', '1');
        }

        formData.append('csrf_token', CSRF_TOKEN);

        try {
            const res = await fetch(`${URLROOT}/inventario/guardar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: formData
            });

            if (!res.ok) throw new Error('Error en la comunicación con el servidor');
            const contentType = res.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) throw new Error('El servidor devolvió una respuesta inválida (HTML)');

            const result = await res.json();
            if (result.success) {
                toggleModal(false);
                if (window.handler_inventario) window.handler_inventario.reload();
                AppUtils.showToast(result.mensaje);
            } else {
                AppUtils.showToast(result.mensaje || result.error || 'Error al guardar', 'error');
            }
        } catch (error) {
            AppUtils.showToast('Error de conexión', 'error');
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = originalText;
            if (window.lucide) lucide.createIcons();
        }
    });

    const toggleModal = (show) => {
        modal.classList.toggle('hidden', !show);
        if (!show) {
            form.reset();
            document.getElementById('prodId').value = "";
            document.getElementById('prodCondicion').value = 'NUEVO';
            document.getElementById('prodVisibleCatalogo').checked = false;
            imagePreview.innerHTML = '<i data-lucide="image" class="w-8 h-8 text-slate-300"></i>';
            document.getElementById('modalTitle').textContent = "Registrar Producto";
            lucide.createIcons();
        }
    };

    // ===== CÓDIGO DROPDOWN PERSONALIZADO =====
    const codigoInput = document.getElementById('prodCodigo');
    const codigoDropdown = document.getElementById('codigoDropdown');
    const codigoDropdownList = document.getElementById('codigoDropdownList');
    const codigoDropdownEmpty = document.getElementById('codigoDropdownEmpty');
    let codigosCache = [];

    async function cargarCodigos() {
        try {
            const res = await fetch(`${URLROOT}/inventario/codigos`);
            const data = await res.json();
            if (data.success) {
                codigosCache = data.codigos;
                renderCodigosDropdown(codigosCache);
            }
        } catch (e) {
            console.warn('Error al cargar códigos:', e);
        }
    }

    function renderCodigosDropdown(lista) {
        if (!codigoDropdownList) return;
        if (lista.length === 0) {
            codigoDropdownList.innerHTML = '';
            codigoDropdownEmpty.classList.remove('hidden');
            return;
        }
        codigoDropdownEmpty.classList.add('hidden');
        codigoDropdownList.innerHTML = lista.map(c =>
            `<div class="px-4 py-2.5 text-sm font-mono text-slate-700 uppercase cursor-pointer hover:bg-neon-green/10 hover:text-neon-green border-b border-slate-100 last:border-b-0 transition-all" data-codigo="${c}">${c}</div>`
        ).join('');

        codigoDropdownList.querySelectorAll('[data-codigo]').forEach(el => {
            el.addEventListener('click', () => {
                codigoInput.value = el.dataset.codigo;
                codigoDropdown.classList.add('hidden');
                codigoInput.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    }

    codigoInput.addEventListener('focus', () => {
        if (codigosCache.length > 0) {
            renderCodigosDropdown(codigosCache.filter(c =>
                c.includes(codigoInput.value.toUpperCase())
            ));
            codigoDropdown.classList.remove('hidden');
        }
    });

    codigoInput.addEventListener('input', (e) => {
        const val = e.target.value.toUpperCase();
        if (val.length > 0 && codigosCache.length > 0) {
            const filtrados = codigosCache.filter(c => c.includes(val));
            renderCodigosDropdown(filtrados);
            codigoDropdown.classList.remove('hidden');
        } else if (val.length === 0) {
            renderCodigosDropdown(codigosCache);
            codigoDropdown.classList.remove('hidden');
        }
    });

    document.addEventListener('click', (e) => {
        if (codigoDropdown && !e.target.closest('#prodCodigo') && !e.target.closest('#codigoDropdown')) {
            codigoDropdown.classList.add('hidden');
        }
    });

    document.getElementById('prodCodigo').addEventListener('input', (e) => {
        const start = e.target.selectionStart;
        const end = e.target.selectionEnd;
        e.target.value = e.target.value.toUpperCase();
        e.target.setSelectionRange(start, end);
    });

    document.getElementById('prodCodigo').addEventListener('input', async (e) => {
        const val = e.target.value.trim();
        const sugerenciaEl = document.getElementById('codigoSugerencia');

        const match = val.match(/^([A-Za-z0-9]+-)$/);
        if (match) {
            try {
                const res = await fetch(`${URLROOT}/inventario/codigos?prefijo=${encodeURIComponent(match[1])}`);
                const data = await res.json();
                if (data.success && data.sugerido) {
                    sugerenciaEl.textContent = `Sugerido: ${data.sugerido}`;
                    sugerenciaEl.classList.remove('hidden');
                } else {
                    sugerenciaEl.classList.add('hidden');
                }
            } catch (e) {
                sugerenciaEl.classList.add('hidden');
            }
        } else {
            sugerenciaEl.classList.add('hidden');
        }
    });

    // ===== MARCA DROPDOWN PERSONALIZADO =====
    const marcaInput = document.getElementById('prodMarca');
    const marcaDropdown = document.getElementById('marcaDropdown');
    const marcaDropdownList = document.getElementById('marcaDropdownList');
    const marcaDropdownEmpty = document.getElementById('marcaDropdownEmpty');
    let marcasCache = [];

    async function cargarMarcas() {
        try {
            const res = await fetch(`${URLROOT}/inventario/marcas`);
            const data = await res.json();
            if (data.success) {
                marcasCache = data.marcas;
                renderMarcasDropdown(marcasCache);
            }
        } catch (e) {
            console.warn('Error al cargar marcas:', e);
        }
    }

    function renderMarcasDropdown(lista) {
        if (!marcaDropdownList) return;
        if (lista.length === 0) {
            marcaDropdownList.innerHTML = '';
            marcaDropdownEmpty.classList.remove('hidden');
            return;
        }
        marcaDropdownEmpty.classList.add('hidden');
        marcaDropdownList.innerHTML = lista.map(m =>
            `<div class="px-4 py-2.5 text-sm font-bold text-slate-700 uppercase cursor-pointer hover:bg-neon-green/10 hover:text-neon-green border-b border-slate-100 last:border-b-0 transition-all" data-marca="${m}">${m}</div>`
        ).join('');

        marcaDropdownList.querySelectorAll('[data-marca]').forEach(el => {
            el.addEventListener('click', () => {
                marcaInput.value = el.dataset.marca;
                marcaDropdown.classList.add('hidden');
            });
        });
    }

    if (marcaInput) {
        marcaInput.addEventListener('focus', () => {
            if (marcasCache.length > 0) {
                renderMarcasDropdown(marcasCache.filter(m =>
                    m.includes(marcaInput.value.toUpperCase())
                ));
                marcaDropdown.classList.remove('hidden');
            }
        });

        marcaInput.addEventListener('input', (e) => {
            const val = e.target.value.toUpperCase();
            if (val.length > 0 && marcasCache.length > 0) {
                const filtrados = marcasCache.filter(m => m.includes(val));
                renderMarcasDropdown(filtrados);
                marcaDropdown.classList.remove('hidden');
            } else if (val.length === 0) {
                renderMarcasDropdown(marcasCache);
                marcaDropdown.classList.remove('hidden');
            }
        });

        marcaInput.addEventListener('input', (e) => {
            const start = e.target.selectionStart;
            const end = e.target.selectionEnd;
            e.target.value = e.target.value.toUpperCase();
            e.target.setSelectionRange(start, end);
        });
    }

    document.addEventListener('click', (e) => {
        if (marcaDropdown && !e.target.closest('#prodMarca') && !e.target.closest('#marcaDropdown')) {
            marcaDropdown.classList.add('hidden');
        }
    });

    document.getElementById('btnOpenModal')?.addEventListener('click', () => {
        cargarCodigos();
        cargarMarcas();
        toggleModal(true);
    });
    document.getElementById('btnCloseModal')?.addEventListener('click', () => toggleModal(false));
    document.getElementById('btnCancel')?.addEventListener('click', () => toggleModal(false));

    window.editItem = (id) => {
        const item = window.currentData.find(i => i.id == id);

        cargarCodigos();
        cargarMarcas();
        document.getElementById('prodId').value = item.id;
        document.getElementById('prodCodigo').value = item.codigo || '';
        document.getElementById('prodNombre').value = item.nombre;
        document.getElementById('prodMarca').value = item.marca || '';
        document.getElementById('prodDescripcion').value = item.descripcion || '';
        document.getElementById('prodCategoria').value = item.categoria;
        document.getElementById('prodCondicion').value = (item.condicion || 'NUEVO').toUpperCase();
        document.getElementById('prodVisibleCatalogo').checked = parseInt(item.visible_catalogo) === 1;
        document.getElementById('prodStock').value = item.stock;
        document.getElementById('prodStockMin').value = item.stock_minimo;
        document.getElementById('prodPrecio').value = item.precio;
        document.getElementById('prodDiasGarantia').value = item.dias_garantia || '';
        document.getElementById('prodImagen').value = item.imagen || '';

        if (item.imagen) {
            const cleanPath = item.imagen.trim();
            const isDataUri = cleanPath.toLowerCase().startsWith('data:');
            const isRemote = cleanPath.toLowerCase().startsWith('http');
            const imgUrl = (isDataUri || isRemote) ? cleanPath : `${URLROOT}/${cleanPath}`;
            imagePreview.innerHTML = `<img src="${imgUrl}" class="w-full h-full object-cover">`;
        }

        document.getElementById('modalTitle').textContent = "Editar Producto";
        toggleModal(true);
    };

    window.deleteItem = (id) => {
        AppUtils.confirmAction('¿Eliminar producto?', 'Esta acción no se puede deshacer.', async () => {

            const res = await fetch(`${URLROOT}/inventario/eliminar/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
            });
            const result = await res.json();
            if (result.success) {
                AppUtils.showToast('Producto eliminado');
                if (window.handler_inventario) window.handler_inventario.reload();
            }
        });
    };

    // ===== OFERTA MODAL LOGIC =====
    const ofertaModal = document.getElementById('ofertaModal');
    const formOferta = document.getElementById('formOferta');
    const btnCloseOfertaModal = document.getElementById('btnCloseOfertaModal');
    const btnCancelOferta = document.getElementById('btnCancelOferta');
    const btnEliminarOferta = document.getElementById('btnEliminarOferta');

    const toggleOfertaModal = (show) => {
        ofertaModal.classList.toggle('hidden', !show);
        if (!show) {
            formOferta.reset();
            document.getElementById('ofertaProdId').value = '';
            document.getElementById('ofertaActiva').value = '1';
            document.getElementById('ofertaPorcentaje').value = '';
            document.getElementById('ofertaFechaInicio').value = '';
            document.getElementById('ofertaFechaFin').value = '';
            document.getElementById('ofertaProdNombre').textContent = '-';
            document.getElementById('ofertaProdStock').textContent = 'Stock: -';
            document.getElementById('ofertaProdPrecio').textContent = 'Precio: -';
            if (btnEliminarOferta) btnEliminarOferta.classList.add('hidden');
            if (window.lucide) lucide.createIcons();
        }
    };

    window.openOfertaModal = (id) => {
        const item = window.currentData.find(i => i.id == id);
        if (!item) return;

        if (item.stock <= 1) {
            AppUtils.showToast('El producto debe tener stock mayor a 1 unidad para poder ponerlo en oferta', 'error');
            return;
        }

        document.getElementById('ofertaProdId').value = item.id;
        document.getElementById('ofertaProdNombre').textContent = item.nombre;
        document.getElementById('ofertaProdStock').textContent = `Stock: ${item.stock} unidades`;
        document.getElementById('ofertaProdPrecio').textContent = `Precio: ${AppUtils.formatCurrency(item.precio)}`;

        if (item.oferta_activa && item.oferta_porcentaje > 0) {
            document.getElementById('ofertaPorcentaje').value = item.oferta_porcentaje;
            document.getElementById('ofertaFechaInicio').value = item.oferta_fecha_inicio || '';
            document.getElementById('ofertaFechaFin').value = item.oferta_fecha_fin || '';
            document.getElementById('ofertaModalTitle').textContent = 'Editar Oferta';
            if (btnEliminarOferta) btnEliminarOferta.classList.remove('hidden');
        } else {
            document.getElementById('ofertaPorcentaje').value = '';
            document.getElementById('ofertaFechaInicio').value = '';
            document.getElementById('ofertaFechaFin').value = '';
            document.getElementById('ofertaModalTitle').textContent = 'Configurar Oferta';
            if (btnEliminarOferta) btnEliminarOferta.classList.add('hidden');
        }

        toggleOfertaModal(true);
    };

    btnCloseOfertaModal?.addEventListener('click', () => toggleOfertaModal(false));
    btnCancelOferta?.addEventListener('click', () => toggleOfertaModal(false));

    formOferta.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();

        const btnSave = formOferta.querySelector('button[type="submit"]');
        const originalText = btnSave.innerHTML;

        btnSave.disabled = true;
        btnSave.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i>';
        if (window.lucide) lucide.createIcons();

        const formData = new FormData(formOferta);
        formData.append('csrf_token', CSRF_TOKEN);

        try {
            const res = await fetch(`${URLROOT}/inventario/oferta`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: formData
            });

            if (!res.ok) throw new Error('Error en la comunicación con el servidor');
            const contentType = res.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) throw new Error('El servidor devolvió una respuesta inválida (HTML)');

            const result = await res.json();
            if (result.success) {
                toggleOfertaModal(false);
                if (window.handler_inventario) window.handler_inventario.reload();
                AppUtils.showToast(result.mensaje);
            } else {
                AppUtils.showToast(result.mensaje || 'Error al guardar la oferta', 'error');
            }
        } catch (error) {
            AppUtils.showToast('Error de conexión', 'error');
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = originalText;
            if (window.lucide) lucide.createIcons();
        }
    });

    btnEliminarOferta?.addEventListener('click', async () => {
        const id = document.getElementById('ofertaProdId').value;
        if (!id) return;

        AppUtils.confirmAction('¿Eliminar oferta?', 'Esta acción eliminará la oferta del producto permanentemente.', async () => {
            try {
                const res = await fetch(`${URLROOT}/inventario/eliminar-oferta/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
                });
                const result = await res.json();
                if (result.success) {
                    AppUtils.showToast('Oferta eliminada correctamente');
                    toggleOfertaModal(false);
                    if (window.handler_inventario) window.handler_inventario.reload();
                } else {
                    AppUtils.showToast(result.mensaje || 'Error al eliminar la oferta', 'error');
                }
            } catch (error) {
                AppUtils.showToast('Error de conexión', 'error');
            }
        });
    });

    ofertaModal?.addEventListener('click', (e) => {
        if (e.target === ofertaModal) {
            toggleOfertaModal(false);
        }
    });
});