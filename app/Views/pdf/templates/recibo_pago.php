<style>
    @page { margin: 30px 40px 45px; }
    body { font-family: 'Helvetica', sans-serif; color: #0f172a; font-size: 10px; line-height: 1.35; }
    .section-title { font-size: 11px; font-weight: 900; text-transform: uppercase; color: #1e293b; margin-bottom: 6px; letter-spacing: .12em; }
    .panel { border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff; padding: 12px; box-shadow: 0 2px 8px rgba(15,23,42,.04); }
    .panel-muted { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); }
    .label-min { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: .16em; font-weight: 900; }
    .value-text { font-size: 10px; color: #0f172a; font-weight: 700; }
    .pill { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; }
    .pill-warning { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .items-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    .items-table th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 8px; text-align: left; font-size: 9px; text-transform: uppercase; color: #475569; letter-spacing: .08em; }
    .items-table td { padding: 8px; border-bottom: 1px solid #f1f5f9; font-size: 10px; color: #0f172a; }
    .text-right { text-align: right; }
    .summary-grid { display: table; width: 100%; border-collapse: collapse; }
    .summary-grid td { vertical-align: top; padding: 0 6px 6px 0; }
    .stat-box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; background: #ffffff; }
    .stat-box strong { display: block; font-size: 14px; color: #111827; margin-top: 3px; }
</style>

<div style="margin-top: 8px;">
    <div class="section-title">Resumen del comprobante</div>
    <table class="summary-grid" style="margin-bottom: 10px;">
        <tr>
            <td width="33%">
                <div class="stat-box panel-muted">
                    <span class="label-min">Beneficiario</span>
                    <strong style="text-transform: uppercase; font-size: 13px;"><?php echo htmlspecialchars($pago->staff_nombre); ?></strong>
                    <div class="value-text" style="margin-top: 2px;">Cédula: <?php echo htmlspecialchars($pago->staff_cedula); ?></div>
                    <div class="value-text">Cargo: <?php echo htmlspecialchars($pago->staff_cargo ?: 'Sin cargo'); ?></div>
                </div>
            </td>
            <td width="33%">
                <div class="stat-box panel-muted">
                    <span class="label-min">Comprobante</span>
                    <strong>RECIBO</strong>
                    <div class="value-text">Fecha: <?php echo date('d/m/Y', strtotime($pago->fecha)); ?></div>
                    <div class="value-text">Método: <?php echo htmlspecialchars($pago->metodo_pago ?: 'SIN DATO'); ?></div>
                </div>
            </td>
            <td width="34%">
                <div class="stat-box panel-muted">
                    <span class="label-min">Pagado por</span>
                    <strong style="color: #0f172a; text-transform: uppercase;"><?php echo htmlspecialchars($pago->pagador_nombre ?: 'SISTEMA'); ?></strong>
                    <div class="value-text">Método: <?php echo htmlspecialchars($pago->metodo_pago ?: 'SIN DATO'); ?></div>
                    <div class="value-text">Teléfono: <?php echo htmlspecialchars($pago->pagador_telefono ?: 'SIN TELÉFONO'); ?></div>
                </div>
            </td>
        </tr>
    </table>

    <div class="panel">
        <div class="section-title">Detalle de trabajos liquidados</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Trabajo / Servicio</th>
                    <th>Vehículo</th>
                    <th>Procedencia</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pago->trabajos)): ?>
                    <?php foreach ($pago->trabajos as $t): ?>
                        <tr>
                            <td style="text-transform: uppercase; font-weight: 700;"><?php echo htmlspecialchars($t->descripcion); ?></td>
                            <td style="color: #475569; text-transform: uppercase;"><?php echo htmlspecialchars($t->placa ?: 'MOSTRADOR'); ?><?php echo !empty($t->modelo_vehiculo) ? ' / ' . htmlspecialchars($t->modelo_vehiculo) : ''; ?></td>
                            <td><span class="pill <?php echo ($t->tipo_procedencia === 'OS') ? 'pill-warning' : ''; ?>"><?php echo htmlspecialchars($t->tipo_procedencia ?: 'MOSTRADOR'); ?></span></td>
                            <td class="text-right" style="font-weight: 900;">$<?php echo number_format((float)$t->precio_unitario, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #94a3b8; font-style: italic; padding: 14px 0;">No hay trabajos asociados a este pago.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 10px;">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td width="55%" style="padding-right: 8px; vertical-align: top;">
                    <div class="panel">
                        <div class="section-title">Cálculo y liquidación</div>
                        <table style="width: 100%;">
                            <tr>
                                <td class="label-min" style="padding: 4px 0;">Monto base</td>
                                <td class="text-right" style="font-weight: 900;">$<?php echo number_format((float)$pago->monto_base, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="label-min" style="padding: 4px 0;">Modo de cálculo</td>
                                <td class="text-right" style="font-weight: 700;"><?php echo isset($pago->modo_calculo) ? htmlspecialchars($pago->modo_calculo) : 'FIJO'; ?></td>
                            </tr>
                            <tr>
                                <td class="label-min" style="padding: 4px 0;">Factor aplicado</td>
                                <td class="text-right" style="font-weight: 700;">
                                    <?php
                                        $modo = isset($pago->modo_calculo) ? strtoupper((string)$pago->modo_calculo) : 'FIJO';
                                        $factor = isset($pago->factor_calculo) ? (float)$pago->factor_calculo : 0;
                                        echo $modo === 'PORCENTAJE' ? number_format($factor, 2) . '%' : '$' . number_format($factor, 2);
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-min" style="padding: 8px 0 0;">Total percibido</td>
                                <td class="text-right" style="font-size: 14px; font-weight: 900; color: #047857;">$<?php echo number_format((float)$pago->monto, 2); ?></td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td width="45%" style="vertical-align: top;">
                    <div class="panel panel-muted">
                        <div class="section-title">Observaciones</div>
                        <div class="value-text" style="min-height: 62px;">
                            <?php echo !empty($pago->notas) ? nl2br(htmlspecialchars($pago->notas)) : '<span style="color:#94a3b8; font-style: italic;">Sin observaciones adicionales.</span>'; ?>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 8px; color: #64748b; font-size: 8px; text-transform: uppercase; letter-spacing: .08em;">
        Este comprobante registra la liquidación de la nómina correspondiente a los servicios y trabajos asociados al empleado.
    </div>

    <div style="margin-top: 18px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 45%; border-top: 1px solid #0f172a; text-align: center; font-size: 9px; color: #475569; padding-top: 6px;">FIRMA EMPLEADO</td>
                <td style="width: 10%;"></td>
                <td style="width: 45%; border-top: 1px solid #0f172a; text-align: center; font-size: 9px; color: #475569; padding-top: 6px;">FIRMA ADMINISTRACIÓN</td>
            </tr>
        </table>
    </div>
</div>