<div class="login-wrapper" style="max-width: 400px; margin: 80px auto; padding: 20px;">
    <div class="card shadow-sm p-4 bg-white rounded">
        <div class="card-body">
            <h2 class="text-center mb-4"><?php echo s($data['titulo']); ?></h2>
            
            <!-- Contenedor dinámico de errores manejado por JS -->
            <div id="alert-error" class="alert alert-danger" style="display: none; color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 15px;"></div>

            <form id="formLogin">
                <input type="hidden" id="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="email" style="display: block; margin-bottom: 5px;">Correo Electrónico:</label>
                    <input type="email" id="email" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required autocomplete="email">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="password" style="display: block; margin-bottom: 5px;">Contraseña:</label>
                    <input type="password" id="password" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                </div>

                <button type="submit" id="btnSubmit" class="btn btn-primary" style="width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Ingresar al Sistema
                </button>
            </form>
        </div>
    </div>
</div>
<?php echo JS_DIR; ?>
<!-- Inyectamos la URLROOT de PHP para que JavaScript sepa exactamente a dónde disparar -->
<script>
    const URLROOT = "<?php echo JS_DIR; ?>";
</script>
<script src="<?php echo JS_DIR; ?>login.js"></script>