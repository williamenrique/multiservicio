<div class="content">
    <div style="background: #f8fafc; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <table width="100%">
            <tr>
                <td><strong>VEHÍCULO:</strong> <?php echo $orden->marca . ' ' . $orden->modelo; ?></td>
                <td><strong>PLACA:</strong> <?php echo $orden->placa; ?></td>
            </tr>
            <tr>
                <td><strong>KILOMETRAJE:</strong> <?php echo number_format($orden->kilometraje); ?> KM</td>
                <td><strong>COMBUSTIBLE:</strong> <?php echo $orden->nivel_combustible; ?></td>
            </tr>
        </table>
    </div>

    <h4 style="border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">MOTIVO DE INGRESO Y DIAGNÓSTICO</h4>
    <div style="min-height: 150px; padding: 10px; border: 1px solid #e2e8f0;">
        <?php echo nl2br($orden->observaciones_entrada); ?>
    </div>

    <div style="margin-top: 50px;">
        <table width="100%">
            <tr>
                <td width="50%" style="text-align: center; border-top: 1px solid #000; padding-top: 5px;">Firma del Técnico</td>
                <td width="50%" style="text-align: center; border-top: 1px solid #000; padding-top: 5px;">Firma del Cliente</td>
            </tr>
        </table>
    </div>
</div>