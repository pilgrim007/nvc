'use strict';
const form = document.getElementById('contact-form');
const status = document.getElementById('form-status');
const button = form.querySelector('button[type="submit"]');
async function prepare() {
  button.disabled = true;
  try {
    if (location.origin !== 'https://test.nvc.ee') throw new Error('host');
    const response = await fetch('send.php?action=token', {credentials: 'same-origin', cache: 'no-store'});
    const data = await response.json();
    if (!response.ok || !data.token) throw new Error('token');
    document.getElementById('contact-token').value = data.token;
    status.textContent = 'Täida väljad ja saada päring.';
    button.disabled = false;
  } catch {
    status.textContent = 'Vorm pole praegu saadaval. Kirjuta info@nvc.ee või helista +372 50 33 697.';
  }
}
form.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (!form.reportValidity() || button.disabled) return;
  button.disabled = true;
  status.textContent = 'Päringu saatmine…';
  try {
    const response = await fetch(form.action, {
      method: 'POST', body: new URLSearchParams(new FormData(form)),
      credentials: 'same-origin', headers: {'Accept': 'application/json'}
    });
    const data = await response.json();
    status.textContent = data.message || 'Saatmine ebaõnnestus. Proovi hiljem uuesti.';
    if (response.ok) {
      form.reset();
      status.textContent = data.message;
    } else {
      button.disabled = false;
    }
    // A consumed token is never silently reused; preserve fields after errors.
    if (data.refresh) {
      const savedMessage = status.textContent;
      await prepare();
      if (!button.disabled) status.textContent = savedMessage;
    }
  } catch {
    status.textContent = 'Saatmise tulemus jäi kinnitamata. Enne uuesti saatmist võta ühendust info@nvc.ee või +372 50 33 697.';
    // Do not automatically retry an uncertain delivery.
  }
});
prepare();
