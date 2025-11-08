document.querySelectorAll('.btn-mark').forEach(btn => {
  btn.addEventListener('click', async () => {
    const id = btn.dataset.id;
    const res = await fetch('index.php?page=api_logistic_update_status', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ supplier_order_id: id, id_status: 3 })
    });
    const json = await res.json();
    if (json.success) {
      alert('Recolección marcada como completada');
      location.reload();
    } else {
      alert('Error al actualizar el estado');
    }
  });
});