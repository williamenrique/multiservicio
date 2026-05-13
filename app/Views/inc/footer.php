            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <!-- Librería de Iconos Lucide (Cargada con versión específica para estabilidad) -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.344.0/dist/umd/lucide.min.js"></script>

    <script src="<?php echo URL_JS; ?>utils.js"></script>
    <script src="<?php echo URL_JS; ?>app.js"></script>
    <script src="<?php echo URL_JS; ?>notifications.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicialización general de iconos
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Lógica para el Dropdown de Usuario (Top Bar)
            const trigger = document.getElementById('userDropdownTrigger');
            const menu = document.getElementById('userDropdownMenu');

            if (trigger && menu) {
                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    menu.classList.toggle('hidden');
                    // Refrescamos iconos específicamente al abrir para asegurar visibilidad
                    lucide.createIcons();
                });

                // Cerrar el menú si se hace clic en cualquier otra parte de la pantalla
                document.addEventListener('click', function(e) {
                    if (!trigger.contains(e.target) && !menu.contains(e.target)) {
                        menu.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</body>

</html>
