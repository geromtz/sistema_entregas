(function(){
  const $ = (s)=>document.querySelector(s);
  const $$ = (s)=>Array.from(document.querySelectorAll(s));

  const apiList = 'index.php?page=api_logistic_fetch_wholesaler';
  const apiAssign = 'index.php?page=api_logistic_assign_bulk';

  // seleccionar todos
  $('#checkAll')?.addEventListener('change', (e)=>{
    $$('.row-check').forEach(ch => ch.checked = e.target.checked);
  });

  // asignar en bulk
  $('#btnAssignSelected')?.addEventListener('click', async ()=>{
    const assigned_to = $('#driver')?.value || '';
    const type = $('#assignType')?.value || 'delivery';
    const scheduled_date = $('#schedule')?.value || '';
    const notes = ($('#notes')?.value || '').trim();

    const ids = $$('.row-check').filter(ch => ch.checked).map(ch => parseInt(ch.dataset.id,10));
    if (!assigned_to) { alert('Selecciona un chofer'); return; }
    if (ids.length === 0) { alert('Selecciona al menos un registro'); return; }

    const items = ids.map(id => ({ tracking_id: id, type, scheduled_date }));

    try {
      const res = await fetch(apiAssign, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ items, assigned_to: parseInt(assigned_to,10), notes })
      });
      const json = await res.json();
      if (json.success) {
        alert('Asignación realizada');
        location.reload();
      } else {
        alert(json.message || 'No se pudo asignar');
      }
    } catch (err) {
      console.error(err);
      alert('Error de red');
    }
  });

  // (Opcional) recarga por AJAX
  // Puedes llamar a apiList para refrescar tabla sin recargar:
  // fetch(apiList).then(r=>r.json()).then(({data}) => { ...re-render... });

})();
