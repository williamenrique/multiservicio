            </div>
        </main>
    </div>

    <!-- Scripts -->
    <!-- Librerías de terceros agrupadas (jQuery, DataTables, SweetAlert2, Toastify, Lucide) -->
    <script src="<?php echo URL_JS; ?>vendor.min.js"></script>

    <!-- Script principal de la aplicación -->
    <script src="<?php echo URL_JS; ?>app.min.js"></script>
    
    <!-- Scripts cargados bajo demanda (Lazy Loading) -->
    <?php if (isset($extra_scripts) && is_array($extra_scripts)): ?>
        <?php foreach ($extra_scripts as $script): ?>
            <?php 
                $src = (strpos($script, 'http') === 0) ? $script : URL_JS . $script; 
            ?>
            <script src="<?php echo $src; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>

</html>
