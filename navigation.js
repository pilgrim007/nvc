'use strict';
(() => {
  const button = document.querySelector('.nav-button');
  const nav = document.getElementById('main-nav');
  if (!button || !nav) return;
  const mobile = matchMedia('(max-width: 1180px)');
  function close(returnFocus = false) {
    if (returnFocus) button.focus();
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-label', 'Ava menüü');
  }
  nav.dataset.enhanced = 'true';
  button.hidden = false;
  button.addEventListener('click', () => {
    const open = button.getAttribute('aria-expanded') !== 'true';
    button.setAttribute('aria-expanded', String(open));
    button.setAttribute('aria-label', open ? 'Sulge menüü' : 'Ava menüü');
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') close(true);
  });
  document.addEventListener('click', event => {
    if (!event.target.closest('.header__inner')) close();
  });
  nav.addEventListener('click', event => {
    if (event.target.closest('a')) close(mobile.matches);
  });
  document.addEventListener('focusin', event => {
    if (!event.target.closest('.header__inner')) close();
  });
  mobile.addEventListener('change', () => {
    close(mobile.matches && nav.contains(document.activeElement));
  });
  window.addEventListener('pageshow', () => close());
  for (const link of nav.querySelectorAll('a:not(.nav-cta)')) {
    if (new URL(link.href).pathname === location.pathname ||
        (location.pathname === '/' && link.getAttribute('href') === 'index.html')) {
      link.setAttribute('aria-current', 'page');
    }
  }
})();
