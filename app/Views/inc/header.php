<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($data['titulo']) ? s($data['titulo']) . ' | ' . SITENAME : SITENAME; ?></title>
    <!-- Usamos URLROOT para que los assets carguen siempre bien -->
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/style.css">
</head>
<body>
    <nav>
        <!-- Tu menú aquí -->
        <a href="<?php echo URLROOT; ?>/dashboard">Inicio</a>
        <a href="<?php echo URLROOT; ?>/auth/logout">Cerrar Sesión</a>
    </nav>
    <main class="container">

