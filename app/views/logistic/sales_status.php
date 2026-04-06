<div class="container mt-4">
    <h3>Estado de Mis Pedidos</h3>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Cliente</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $row): ?>

            <?php
                if ($row['all_delivered'] == 1) {
                    $estado = "<span class='badge bg-success'>Entregado</span>";
                } elseif ($row['any_pickup'] == 1) {
                    $estado = "<span class='badge bg-warning'>En tránsito</span>";
                } else {
                    $estado = "<span class='badge bg-danger'>Pendiente</span>";
                }
            ?>

            <tr>
                <td><?= $row['system_number'] ?></td>
                <td><?= $row['name_order'] ?></td>
                <td><?= $estado ?></td>
            </tr>

        <?php endforeach; ?>
        </tbody>
    </table>
</div>