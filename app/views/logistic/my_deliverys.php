<div class="container mt-4">
    <h3>Entregas Pendientes</h3>

    <?php if (empty($data)): ?>
        <div class="alert alert-success">
            No hay entregas pendientes.
        </div>
    <?php else: ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Cliente</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['system_number']) ?></td>
                    <td><?= htmlspecialchars($row['name_order']) ?></td>
                    <td><?= htmlspecialchars($row['description_product']) ?></td>
                    <td><?= htmlspecialchars($row['quantity']) ?></td>
                    <td>
                        <form method="POST" action="index.php?page=mark_delivery">
                            <input type="hidden" name="item_id" value="<?= $row['item_id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm">
                                Marcar Entrega
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>
</div>