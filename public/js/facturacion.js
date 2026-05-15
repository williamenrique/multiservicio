/**
 * Lógica del Punto de Venta (POS)
 */
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('pos-search');
    const searchResults = document.getElementById('pos-search-results');
    const cartBody = document.getElementById('pos-cart-body');

    let cart = [];
    let selectedClient = null;
    const IVA_PERCENT = parseFloat(document.getElementById('pos-iva-percent').textContent) || 0;

    // 1. Buscador de Items
    searchInput.addEventListener('input', async (e) => {
        const term = e.target.value.trim();
        if (term.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }

        const res = await fetch(`${URLROOT}/facturacion/buscarItems?term=${term}`);
        const items = await res.json();

        if (items.length > 0) {
            searchResults.innerHTML = items.map(item => `
                <div class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 flex justify-between items-center" 
                     onclick="addToCart(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                    <div>
                        <p class="font-bold text-sm uppercase">${item.nombre}</p>
                        <p class="text-[10px] text-slate-400">${item.categoria} | Stock: ${item.stock}</p>
                    </div>
                    <span class="font-bold text-navy-blue">${AppUtils.formatCurrency(item.precio)}</span>
                </div>
            `).join('');
            searchResults.classList.remove('hidden');
        } else {
            searchResults.innerHTML = '<p class="p-3 text-center text-slate-400 text-xs">Sin resultados</p>';
            searchResults.classList.remove('hidden');
        }
    });

    // 2. Gestión del Carrito
    window.addToCart = (item) => {
        const exists = cart.find(i => i.id === item.id);
        if (exists) {
            if (exists.cantidad < item.stock || item.categoria === 'SERVICIO') {
                exists.cantidad++;
            } else {
                AppUtils.showToast('Stock máximo alcanzado', 'error');
            }
        } else {
            cart.push({ ...item, cantidad: 1 });
        }
        searchInput.value = '';
        searchResults.classList.add('hidden');
        renderCart();
    };

    window.removeFromCart = (index) => {
        cart.splice(index, 1);
        renderCart();
    };

    window.updateQty = (index, qty) => {
        const item = cart[index];
        if (qty > 0 && (qty <= item.stock || item.categoria === 'SERVICIO')) {
            item.cantidad = parseInt(qty);
        }
        renderCart();
    };

    const renderCart = () => {
        cartBody.innerHTML = cart.length === 0
            ? '<tr><td colspan="5" class="py-10 text-center text-slate-400 italic">El carrito está vacío</td></tr>'
            : cart.map((item, index) => `
                <tr class="border-b border-slate-50 group">
                    <td class="py-4">
                        <p class="font-bold text-slate-700 text-sm uppercase">${item.nombre}</p>
                        <p class="text-[10px] text-slate-400">${item.categoria}</p>
                    </td>
                    <td class="py-4">
                        <input type="number" value="${item.cantidad}" onchange="updateQty(${index}, this.value)" 
                               class="w-16 p-1 border rounded text-center text-sm focus:border-neon-green outline-none">
                    </td>
                    <td class="py-4 text-sm">${AppUtils.formatCurrency(item.precio)}</td>
                    <td class="py-4 font-bold text-navy-blue">${AppUtils.formatCurrency(item.precio * item.cantidad)}</td>
                    <td class="py-4 text-right">
                        <button onclick="removeFromCart(${index})" class="text-slate-300 hover:text-red-500 transition-colors">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

        calculateTotals();
        lucide.createIcons();
    };

    const calculateTotals = () => {
        const subtotal = cart.reduce((sum, i) => sum + (i.precio * i.cantidad), 0);
        const ivaAmount = subtotal * (IVA_PERCENT / 100);
        const total = subtotal + ivaAmount;

        document.getElementById('pos-subtotal').textContent = AppUtils.formatCurrency(subtotal);
        document.getElementById('pos-iva-amount').textContent = AppUtils.formatCurrency(ivaAmount);
        document.getElementById('pos-total').textContent = AppUtils.formatCurrency(total);
    };

    // 3. Finalizar Venta
    window.processSale = async () => {
        if (cart.length === 0) return AppUtils.showToast('Agregue productos al carrito', 'error');

        const subtotal = cart.reduce((sum, i) => sum + (i.precio * i.cantidad), 0);
        const iva_monto = subtotal * (IVA_PERCENT / 100);

        const saleData = {
            cliente_id: selectedClient ? selectedClient.id : null,
            placa: '', // Se podría pedir en un modal previo
            modelo: '',
            subtotal: subtotal,
            iva_monto: iva_monto,
            total: subtotal + iva_monto,
            items: cart
        };

        AppUtils.confirmAction('¿Finalizar Venta?', 'Se registrará el pago y se descontará el stock.', async () => {
            try {
                const res = await fetch(`${URLROOT}/facturacion/procesar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saleData)
                });
                const result = await res.json();

                if (result.success) {
                    AppUtils.showAlert('¡Venta Exitosa!', `Comprobante #${result.venta_id} generado.`, 'success');
                    cart = [];
                    renderCart();
                } else {
                    AppUtils.showToast(result.mensaje, 'error');
                }
            } catch (e) {
                AppUtils.showToast('Error de comunicación', 'error');
            }
        });
    };

    // Cerrar resultados de búsqueda al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!searchResults.contains(e.target) && e.target !== searchInput) searchResults.classList.add('hidden');
    });
});