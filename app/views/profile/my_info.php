<!-- app/views/profile/my_info.php -->
<div class="container mt-4">
    <h2>Mi Perfil</h2>
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <h5 class="card-title"><?= $_SESSION['user']['fullname'] ?></h5>
            <p class="card-text"><strong>Usuario:</strong> <?= $_SESSION['user']['username'] ?></p>
            <p class="card-text"><strong>Email:</strong> <?= $_SESSION['user']['email'] ?></p>
            <p class="card-text"><strong>Departamento:</strong> <?= $_SESSION['user']['department_id'] ?></p>
            <p class="card-text"><strong>ID Position:</strong> <?= $_SESSION['user']['id_position'] ?></p>
            
            <!-- Botón para editar datos (puede abrir un modal o ir a otra vista) -->
            <a href="index.php?page=app&view=profile/edit_info" class="btn btn-primary mt-3">Editar Datos</a>
        </div>
    </div>
</div>
