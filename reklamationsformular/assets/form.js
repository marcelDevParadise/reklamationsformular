(() => {
  'use strict';
  document.querySelectorAll('.rf-app').forEach(app => {
  const instance = app.dataset.instance;
  const config = JSON.parse(app.dataset.config);
  const form = app.querySelector('form');
  const status = app.querySelector('.rf-status');
  const itemsRoot = app.querySelector('[data-items]');
  const template = app.querySelector('[data-item-template]');
  const submit = app.querySelector('.rf-submit[type=submit]');
  let session = '', busy = false, finished = false;

  const rgb = config.accent.slice(1).match(/../g).map(value => parseInt(value, 16) / 255).map(value => value <= .04045 ? value / 12.92 : ((value + .055) / 1.055) ** 2.4);
  app.style.setProperty('--rf-button-ink', .2126 * rgb[0] + .7152 * rgb[1] + .0722 * rgb[2] > .179 ? '#14202c' : '#fff');
  function message(text = '', isError = false) { status.textContent = text; status.classList.toggle('rf-status-error', isError); }
  function fieldError(name, text) {
    const output = app.querySelector(`[data-error-for="${name}"]`); if (output) output.textContent = text;
    app.querySelector(`[data-field="${name}"]`)?.querySelectorAll('input,select').forEach(input => input.setAttribute('aria-invalid', text ? 'true' : 'false'));
  }
  function clearErrors() { app.querySelectorAll('.rf-error').forEach(el => { el.textContent = ''; }); app.querySelectorAll('[aria-invalid]').forEach(el => el.removeAttribute('aria-invalid')); }
  function renumber() {
    [...itemsRoot.children].forEach((row, index) => {
      row.dataset.index = String(index);
      const number = index + 1;
      const article = row.querySelector('[data-article]'); const quantity = row.querySelector('[data-quantity]');
      article.name = `item_${index}_article`; article.id = `${instance}-item-${index}-article`; article.previousElementSibling.htmlFor = article.id; article.previousElementSibling.firstChild.textContent = `Artikel ${number} `;
      quantity.name = `item_${index}_quantity`; quantity.id = `${instance}-item-${index}-quantity`; quantity.previousElementSibling.htmlFor = quantity.id;
      row.querySelector('[data-remove-item]').hidden = itemsRoot.children.length === 1;
    });
    app.querySelector('[data-add-item]').disabled = itemsRoot.children.length >= 20;
  }
  function addItem() { if (itemsRoot.children.length >= 20) return; itemsRoot.append(template.content.cloneNode(true)); renumber(); }
  app.querySelector('[data-add-item]').addEventListener('click', addItem);
  itemsRoot.addEventListener('click', event => { if (!event.target.matches('[data-remove-item]') || itemsRoot.children.length === 1) return; event.target.closest('[data-item]').remove(); renumber(); });
  itemsRoot.addEventListener('input', event => { event.target.removeAttribute('aria-invalid'); const output = event.target.closest('.rf-field')?.querySelector('.rf-error'); if (output) output.textContent = ''; });
  addItem();

  function validate() {
    clearErrors(); let first = null;
    form.querySelectorAll('input,select').forEach(input => {
      if (!input.checkValidity()) {
        const row = input.closest('[data-item]');
        if (row) { const output = input.closest('.rf-field').querySelector('.rf-error'); output.textContent = input.validity.valueMissing ? 'Bitte dieses Feld ausfüllen.' : input.validationMessage; input.setAttribute('aria-invalid', 'true'); }
        else {
          let text = input.validity.valueMissing ? 'Bitte dieses Feld ausfüllen oder eine Option auswählen.' : input.validationMessage;
          if (input.validity.patternMismatch && input.name === 'order_number') text = 'Die Auftragsnummer muss mit PS, A-PV, AST oder RE beginnen.';
          if (input.validity.patternMismatch && input.name === 'customer_number') text = 'Die Kundennummer muss mit KN oder K beginnen.';
          fieldError(input.name, text);
        }
        first ||= input;
      }
    });
    if (first) { first.focus(); first.scrollIntoView({block:'center'}); return false; }
    return true;
  }
  async function api(path, body) {
    const response = await fetch(config.api + path, {method:'POST', credentials:'omit', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body), cache:'no-store', referrerPolicy:'same-origin'});
    let data; try { data = await response.json(); } catch { throw new Error('Der Server hat nicht wie erwartet geantwortet. Bitte versuche es erneut.'); }
    if (!response.ok) { const error = new Error(data.message || 'Die Übermittlung ist fehlgeschlagen.'); error.details = data; throw error; }
    return data;
  }
  async function newSession() { const result = await api('session', {}); session = result.session; }
  function setBusy(on) { busy = on; form.setAttribute('aria-busy', String(on)); form.querySelectorAll('button,input,select').forEach(el => { el.disabled = on; }); submit.textContent = on ? 'Reklamation wird gespeichert …' : 'Reklamation absenden'; if (!on) renumber(); }
  function payload() {
    const data = Object.fromEntries(new FormData(form));
    data.items = [...itemsRoot.querySelectorAll('[data-item]')].map(row => ({article: row.querySelector('[data-article]').value, quantity: Number(row.querySelector('[data-quantity]').value)}));
    data.privacy_confirmed = form.elements.namedItem('privacy_confirmed').checked; data.session = session; delete data.website;
    data.website = form.elements.namedItem('website').value; Object.keys(data).filter(key => key.startsWith('item_')).forEach(key => delete data[key]); return data;
  }
  function showServerErrors(fields) {
    Object.entries(fields || {}).forEach(([name, text]) => {
      const match = name.match(/^item_(\d+)_(article|quantity)$/);
      if (match) { const row = itemsRoot.children[Number(match[1])]; const input = row?.querySelector(match[2] === 'article' ? '[data-article]' : '[data-quantity]'); if (input) { input.setAttribute('aria-invalid', 'true'); input.closest('.rf-field').querySelector('.rf-error').textContent = text; } }
      else fieldError(name, text);
    });
    const first = app.querySelector('[aria-invalid=true]'); if (first) { first.focus(); first.scrollIntoView({block:'center'}); }
  }
  form.addEventListener('input', event => { if (busy) return; fieldError(event.target.name, ''); });
  form.addEventListener('submit', async event => {
    event.preventDefault(); if (busy || finished || !validate()) return;
    const body = payload();
    setBusy(true); message('Deine Reklamation wird erstellt. Bitte lasse diese Seite geöffnet.');
    try {
      const result = await api('complaints', body); finished = true; form.hidden = true; message();
      const success = app.querySelector('.rf-success'); success.hidden = false; app.querySelector('.rf-reference').textContent = `Vorgangsnummer: ${result.reference}`;
      app.querySelector('.rf-mail-message').textContent = result.customer_mail === 'handed_off' ? 'Deine PDF-Kopie wurde an den E-Mail-Versand übergeben. Bitte prüfe auch deinen Spam-Ordner.' : 'Die E-Mail-Kopie konnte noch nicht an den Versand übergeben werden. Bitte lade das PDF hier herunter.';
      const link = app.querySelector('.rf-download'); link.hidden = !result.download; if (result.download) link.href = result.download;
      app.querySelector('.rf-expiry').textContent = result.download ? `Der Download ist bis ${new Date(result.expires * 1000).toLocaleTimeString('de-DE', {hour:'2-digit', minute:'2-digit'})} Uhr verfügbar.` : '';
      success.focus();
    } catch (failure) {
      message(failure.message || 'Die Verbindung wurde unterbrochen. Deine Angaben bleiben erhalten. Bitte erneut absenden.', true); showServerErrors(failure.details?.data?.fields);
      if (failure.details?.code === 'expired_session') { try { await newSession(); } catch { message('Die Verbindung ist unterbrochen. Bitte versuche es später erneut.', true); } }
    } finally { setBusy(false); }
  });
  window.addEventListener('beforeunload', event => { if (!finished && (busy || form.elements.namedItem('order_number').value)) { event.preventDefault(); event.returnValue = ''; } });
  newSession().then(() => { form.hidden = false; message(); }).catch(() => message('Das Formular konnte nicht geladen werden. Bitte lade die Seite erneut.', true));
  });
})();
