const contextMenu = document.getElementById('rowContextMenu');
let selectedRowId = null;

// Evitar menú por defecto
document.querySelectorAll('.order-row').forEach(row => {
  row.addEventListener('contextmenu', e => {
    e.preventDefault();
    selectedRowId = row.dataset.id;

    // Posicionar menú donde se hizo click
    contextMenu.style.top = `${e.pageY}px`;
    contextMenu.style.left = `${e.pageX}px`;
    contextMenu.style.display = 'block';
  });

  // Doble click abre el pedido en modo "view" por defecto
  row.addEventListener('dblclick', () => {
    window.location.href = `index.php?page=create_order&system_order=${selectedRowId}&mode=view`;
  });
});

// Ocultar menú al hacer click fuera
document.addEventListener('click', () => {
  contextMenu.style.display = 'none';
});

// Acciones del menú
document.getElementById('viewOrder').addEventListener('click', () => {
  window.location.href = `index.php?page=create_order&system_order=${selectedRowId}&mode=view`;
});

document.getElementById('editOrder').addEventListener('click', () => {
  window.location.href = `index.php?page=create_order&system_order=${selectedRowId}&mode=edit`;
});

document.getElementById('deleteOrder').addEventListener('click', () => {
  if (confirm('¿Estás seguro de eliminar este pedido?')) {
    window.location.href = `index.php?page=delete_order&id=${selectedRowId}`;
  }
});



document.querySelectorAll('.order-row').forEach(row => {
  row.addEventListener('dblclick', () => {
    const systemOrderId = row.dataset.id;
    // Redirigir al controlador createOrder con el parámetro system_order
    window.location.href = `index.php?page=create_order&system_order=${systemOrderId}`;
  });
});