<?php
// Vista: email/proveedor_vencimiento.php
// Datos esperados: $proveedores (array con: nombre, saldo_pendiente, proximo_vencimiento, facturas_pendientes, telefono),
//                   $dias_limite, $empresa

$titulo = 'Alertas de Vencimiento — Proveedores';
$contenido = '';
ob_start();
?>

<h2>🔔 Alertas de Vencimiento de Proveedores</h2>

<p>Se han detectado <strong><?= count($proveedores) ?></strong> proveedor(es) con facturas próximas a vencer en los próximos <strong><?= (int)($dias_limite ?? 7) ?> días</strong>.</p>

<p>A continuación se detallan los proveedores con saldos pendientes y sus fechas de vencimiento más próximas:</p>

<table class="items">
    <thead>
        <tr>
            <th>Proveedor</th>
            <th>Facturas Pend.</th>
            <th>Saldo Pendiente</th>
            <th>Próximo Vencimiento</th>
            <th>Días Restantes</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($proveedores as $prov): ?>
        <?php
            $prov = (array) $prov;
            $nombre = htmlspecialchars($prov['nombre'] ?? '—');
            $facturasPend = (int)($prov['facturas_pendientes'] ?? 0);
            $saldo = (float)($prov['saldo_pendiente'] ?? 0);
            $fechaVenc = $prov['proximo_vencimiento'] ?? null;
            $telefono = htmlspecialchars($prov['telefono'] ?? '');

            // Calcular días restantes
            $diasRestantes = '—';
            $claseDias = '';
            if ($fechaVenc) {
                $fechaObj = new DateTime($fechaVenc);
                $hoy = new DateTime();
                $diff = $hoy->diff($fechaObj);
                $diasRestantes = (int)$diff->format('%r%a');

                if ($diasRestantes < 0) {
                    $diasRestantes = 'VENCIDO (' . abs($diasRestantes) . ' días)';
                    $claseDias = 'vencido';
                } elseif ($diasRestantes == 0) {
                    $diasRestantes = 'HOY';
                    $claseDias = 'hoy';
                } elseif ($diasRestantes <= 2) {
                    $claseDias = 'urgente';
                } elseif ($diasRestantes <= 5) {
                    $claseDias = 'pronto';
                }

                $fechaVencFormateada = date('d/m/Y', strtotime($fechaVenc));
            } else {
                $fechaVencFormateada = '—';
            }
        ?>
        <tr>
            <td>
                <strong><?= $nombre ?></strong>
                <?php if (!empty($telefono)): ?>
                <br><small style="color:#6b7280;">Tel: <?= $telefono ?></small>
                <?php endif; ?>
            </td>
            <td style="text-align:center;"><?= $facturasPend ?></td>
            <td style="text-align:right; font-weight:600;">$<?= number_format($saldo, 2) ?></td>
            <td style="text-align:center;"><?= $fechaVencFormateada ?></td>
            <td style="text-align:center; font-weight:700; 
                <?php if ($claseDias === 'vencido'): ?>color:#ef4444;
                <?php elseif ($claseDias === 'hoy' || $claseDias === 'urgente'): ?>color:#f59e0b;
                <?php elseif ($claseDias === 'pronto'): ?>color:#3b82f6;
                <?php endif; ?>">
                <?= $diasRestantes ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="info-box" style="border-left-color: #f59e0b;">
    <p style="font-weight:600; margin-bottom:8px;">📌 Recomendaciones</p>
    <p style="font-size:13px; margin:2px 0;">• Revise los vencimientos marcados en <span style="color:#ef4444;font-weight:600;">rojo</span> (vencidos) con prioridad.</p>
    <p style="font-size:13px; margin:2px 0;">• Programe los pagos de los proveedores con vencimiento <span style="color:#f59e0b;font-weight:600;">próximo</span> para evitar retrasos.</p>
    <p style="font-size:13px; margin:2px 0;">• Ingrese al sistema para registrar abonos o pagos completos desde el módulo de Proveedores.</p>
</div>

<p style="text-align:center; margin-top:24px;">
    <a href="<?= URLROOT ?>/proveedores/listarDeudas" class="btn">Ir a Proveedores</a>
</p>

<?php
$contenido .= ob_get_clean();
include APPROOT . '/Views/email/layout.php';