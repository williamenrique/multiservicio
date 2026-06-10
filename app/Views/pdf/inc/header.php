<style>
    .main-header { 
        border-bottom: 3px solid #0f172a; 
        padding-bottom: 10px; 
        margin-bottom: 20px; 
    }
    .header-table { width: 100%; border-collapse: collapse; }
    .company-cell { vertical-align: middle; }
    .comp-name { font-size: 18px; font-weight: 900; color: #0f172a; text-transform: uppercase; margin: 0; }
    .comp-nit { font-size: 10px; color: #64748b; font-weight: bold; }
    .doc-cell { text-align: right; vertical-align: middle; }
    .doc-label { background: #0f172a; color: #10b981; padding: 4px 10px; font-weight: 900; font-size: 11px; border-radius: 4px; display: inline-block; text-transform: uppercase; }
    .logo-img { height: 45px; width: auto; margin-right: 15px; }
</style>

<div class="main-header">
    <table class="header-table">
        <tr>
            <td class="company-cell" width="70%">
                <table width="100%">
                    <tr>
                        <td width="55px"><img src="img/logo.png" class="logo-img"></td>
                        <td>
                            <h1 class="comp-name">Taller Pro Multiservicio</h1>
                            <span class="comp-nit">NIT: 900.123.456-7 | PBX: (123) 456-7890 | Calle Falsa 123</span>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="doc-cell">
                <div class="doc-label"><?php echo $titulo_documento ?? 'Documento Oficial'; ?></div>
            </td>
        </tr>
    </table>
</div>