<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm p-5 bg-white rounded">
                <div class="card-body text-center">
                    <h1 class="display-4">¡Bienvenido al Sistema!</h1>
                    <hr class="my-4">
                    <p class="lead">
                        Hola, <strong><?php echo s($data['nombre_usuario']); ?></strong>. 
                        Has ingresado con el perfil de: <span class="badge bg-primary"><?php echo strtoupper(s($data['rol_usuario'])); ?></span>
                    </p>
                    <p class="text-muted">
                        Actualmente el sistema está inicializado. Puedes comenzar a navegar usando el menú lateral.
                    </p>
                    <div class="mt-4">
                        <small class="text-secondary">Fecha del servidor: <?php echo formatDate(date('Y-m-d')); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
