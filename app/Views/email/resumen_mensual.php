<?php
// Vista: email/resumen_mensual.php
// Datos esperados:
//   $mes          - string nombre del mes en español
//   $anio         - string año
//   $ventas       - object {total, cantidad}
//   $gastos       - object {total, cantidad}
//   $utilidad     - object {total_ventas, total_costos, total_servicios, ganancia_repuestos, utilidad_bruta}
//   $clientes     - object {cantidad}
//   $ordenes      - object {cantidad}
//   $topProductos - array of {nombre, total_vendido}
//   $inventario   - object {total_productos, criticos, agotados}
//   $empresa      - object company data

$titulo = "Resumen Mensual — {$mes} {$anio}";
$contenido = '';
ob_start();
?>

<h2>📊 Resumen de Actividad — <?= $mes ?> <?= $anio ?></h2>

<p>Estimado administrador, a continuación se presenta un resumen detallado de la actividad del sistema durante el mes de <strong><?= $mes ?> de <?= $anio ?></strong>.</p>

<!-- ─── VENTAS ─── -->
<h3 style="color:#1a56db; border-bottom:2px solid #e5e7eb; padding-bottom:8px; margin-top:28px;">💰 Ventas</h3>
<table class="items">
    <tbody>
        <tr>
            <td><strong>Total Ventas</strong></td>
            <td style="text-align:right; font-weight:700; color:#059669;">$<?= number_format((float)($ventas->total ?? 0), 2) ?></td>
        </tr>
        <tr>
            <td>Facturas emitidas</td>
            <td style="text-align:right;"><?= (int)($ventas->cantidad ?? 0) ?></td>
        </tr>
        <tr>
            <td><strong>Utilidad Bruta</strong></td>
            <td style="text-align:right; font-weight:700; color:#1a56db;">$<?= number_format((float)($utilidad->utilidad_bruta ?? 0), 2) ?></td>
        </tr>
        <tr>
            <td style="padding-left:20px; font-size:13px; color:#6b7280;">— Ganancia en repuestos</td>
            <td style="text-align:right; font-size:13px; color:#6b7280;">$<?= number_format((float)($utilidad->ganancia_repuestos ?? 0), 2) ?></td>
        </tr>
        <tr>
            <td style="padding-left:20px; font-size:13px; color:#6b7280;">— Servicios</td>
            <td style="text-align:right; font-size:13px; color:#6b7280;">$<?= number_format((float)($utilidad->total_servicios ?? 0), 2) ?></td>
        </tr>
    </tbody>
</table>

<!-- ─── GASTOS ─── -->
<h3 style="color:#1a56db; border-bottom:2px solid #e5e7eb; padding-bottom:8px; margin-top:28px;">📉 Gastos</h3>
<table class="items">
    <tbody>
        <tr>
            <td><strong>Total Gastos</strong></td>
            <td style="text-align:right; font-weight:700; color:#ef4444;">$<?= number_format((float)($gastos->total ?? 0), 2) ?></td>
        </tr>
        <tr>
            <td>Registros de gastos</td>
            <td style="text-align:right;"><?= (int)($gastos->cantidad ?? 0) ?></td>
        </tr>
    </tbody>
</table>

<!-- ─── CLIENTES ─── -->
<h3 style="color:#1a56db; border-bottom:2px solid #e5e7eb; padding-bottom:8px; margin-top:28px;">👥 Clientes</h3>
<table class="items">
    <tbody>
        <tr>
            <td>Nuevos clientes registrados</td>
            <td style="text-align:right; font-weight:600;"><?= (int)($clientes->cantidad ?? 0) ?></td>
        </tr>
    </tbody>
</table>

<!-- ─── TALLER ─── -->
<h3 style="color:#1a56db; border-bottom:2px solid #e5e7eb; padding-bottom:8px; margin-top:28px;">🔧 Taller</h3>
<table class="items">
    <tbody>
        <tr>
            <td>Órdenes de servicio completadas</td>
            <td style="text-align:right; font-weight:600;"><?= (int)($ordenes->cantidad ?? 0) ?></td>
        </tr>
    </tbody>
</table>

<!-- ─── PRODUCTOS MÁS VENDIDOS ─── -->
<?php if (!empty($topProductos)): ?>
<h3 style="color:#1a56db; border-bottom:2px solid #e5e7eb; padding-bottom:8px; margin-top:28px;">🏆 Productos Más Vendidos</h3>
<table class="items">
    <thead>
        <tr>
            <th>Producto</th>
            <th style="text-align:right;">Cantidad</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($topProductos as $prod): ?>
        <tr>
            <td><?= htmlspecialchars($prod->nombre ?? '—') ?></td>
            <td style="text-align:right;"><?= (int)($prod->total_vendido ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- ─── INVENTARIO ─── -->
<h3 style="color:#1a56db; border-bottom:2px solid #e5e7eb; padding-bottom:8px; margin-top:28px;">📦 Inventario</h3>
<table class="items">
    <tbody>
        <tr>
            <td>Total de productos</td>
            <td style="text-align:right;"><?= (int)($inventario->total_productos ?? 0) ?></td>
        </tr>
        <tr>
            <td style="color:#f59e0b;">⚠️ Productos críticos (stock bajo)</td>
            <td style="text-align:right; color:#f59e0b; font-weight:600;"><?= (int)($inventario->criticos ?? 0) ?></td>
        </tr>
        <tr>
            <td style="color:#ef4444;">🚫 Productos agotados</td>
            <td style="text-align:right; color:#ef4444; font-weight:600;"><?= (int)($inventario->agotados ?? 0) ?></td>
        </tr>
    </tbody>
</table>

<div class="info-box" style="border-left-color: #1a56db;">
    <p style="font-weight:600; margin-bottom:8px;">📌 Resumen</p>
    <p style="font-size:13px; margin:2px 0;">
        • Ingresos: <strong>$<?= number_format((float)($ventas->total ?? 0), 2) ?></strong>
        | Gastos: <strong>$<?= number_format((float)($gastos->total ?? 0), 2) ?></strong>
        | Utilidad: <strong>$<?= number_format((float)($utilidad->utilidad_bruta ?? 0), 2) ?></strong>
    </p>
    <p style="font-size:13px; margin:2px 0;">
        • <?= (int)($clientes->cantidad ?? 0) ?> nuevo(s) cliente(s) &middot;
        <?= (int)($ordenes->cantidad ?? 0) ?> orden(es) completada(s) &middot;
        <?= (int)($inventario->criticos ?? 0) ?> producto(s) crítico(s)
    </p>
</div>

<p style="text-align:center; margin-top:24px;">
    <a href="<?= URLROOT ?>/dashboard" class="btn">Ir al Dashboard</a>
</p>

<?php
$contenido .= ob_get_clean();
include APPROOT . '/Views/email/layout.php';