<?php require APPROOT . '/Views/inc/header.php'; ?>

<div style="text-align: center; padding: 100px 20px;">
    <h1 style="font-size: 100px; color: #dc3545;">404</h1>
    <h2><?php echo $data['titulo']; ?></h2>
    <p><?php echo $data['mensaje']; ?></p>
    <hr>
    <a href="<?php echo URLROOT; ?>/dashboard" class="btn">Volver al Panel Principal</a>
</div>

<?php require APPROOT . '/Views/inc/footer.php'; ?>

