(() => {
  'use strict';
  const archive = document.querySelector('[data-archive-form]');
  if (archive) {
    const boxes = [...archive.querySelectorAll('input[name="ids[]"]')]; const all = archive.querySelector('[data-select-all]');
    function update() { const count = boxes.filter(box => box.checked).length; archive.querySelector('[data-selection-count]').textContent = `${count} ausgewählt`; all.checked = count > 0 && count === boxes.length; all.indeterminate = count > 0 && count < boxes.length; archive.querySelector('.rf-delete').disabled = count === 0; }
    all.addEventListener('change', () => { boxes.forEach(box => { box.checked = all.checked; }); update(); }); boxes.forEach(box => box.addEventListener('change', update));
    archive.addEventListener('submit', event => { const count = boxes.filter(box => box.checked).length; if (!count || !window.confirm(`${count} Reklamation(en) einschließlich PDF endgültig löschen?`)) event.preventDefault(); }); update();
  }
  document.querySelector('[data-logo-select]')?.addEventListener('click', () => { const picker = wp.media({title:'Logo auswählen',button:{text:'Logo verwenden'},library:{type:['image/png','image/jpeg']},multiple:false}); picker.on('select', () => { const item = picker.state().get('selection').first().toJSON(); document.querySelector('#rf-logo-id').value = item.id; const img = document.createElement('img'); img.src = item.sizes?.thumbnail?.url || item.url; img.alt = ''; document.querySelector('#rf-logo-preview').replaceChildren(img); }); picker.open(); });
  document.querySelector('[data-logo-remove]')?.addEventListener('click', () => { document.querySelector('#rf-logo-id').value = '0'; document.querySelector('#rf-logo-preview').replaceChildren(); });
})();
