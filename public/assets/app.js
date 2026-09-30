document.querySelectorAll('[data-open]').forEach(button => button.addEventListener('click', () => document.getElementById(button.dataset.open)?.showModal()));
document.querySelectorAll('[data-password-toggle]').forEach(button => button.addEventListener('click', () => {
  const input = document.getElementById(button.dataset.passwordToggle);
  const visible = input.type === 'password';
  input.type = visible ? 'text' : 'password';
  button.setAttribute('aria-pressed', String(visible));
  button.setAttribute('aria-label', visible ? 'Ocultar senha' : 'Mostrar senha');
  button.title = visible ? 'Ocultar senha' : 'Mostrar senha';
  button.querySelector('img').src = 'public/assets/icons/' + (visible ? 'eye-off' : 'eye') + '.svg';
}));
document.querySelectorAll('[data-dismiss]').forEach(button => button.addEventListener('click', () => button.closest('.alert')?.remove()));
document.querySelectorAll('[data-toggle]').forEach(button => button.addEventListener('click', () => {
  const panel = document.getElementById(button.dataset.toggle);
  panel.hidden = !panel.hidden;
  button.setAttribute('aria-expanded', String(!panel.hidden));
}));
document.querySelectorAll('[data-autosubmit]').forEach(input => input.addEventListener('change', () => input.form.requestSubmit()));
const menu = document.querySelector('.mobile-menu');
menu?.addEventListener('click', () => {
  const open = document.body.classList.toggle('menu-open');
  menu.setAttribute('aria-expanded', String(open));
  menu.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
});
document.addEventListener('keydown', e => { if(e.key === 'Escape') { document.body.classList.remove('menu-open'); menu?.setAttribute('aria-expanded','false'); } });
document.querySelectorAll('input:not([type="password"]), textarea').forEach(input => {
  input.addEventListener('input', () => input.setCustomValidity(''));
  input.addEventListener('change', () => {
    if(input.required && typeof input.value === 'string' && !input.value.trim()) input.setCustomValidity('Preencha este campo.');
  });
});
const dialog = document.getElementById('confirm-dialog');
let pendingForm;
document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', e => {
  if(form.dataset.confirmed === 'true') return;
  e.preventDefault(); pendingForm = form;
  document.getElementById('confirm-message').textContent = form.dataset.confirm;
  dialog.showModal();
}));
dialog?.addEventListener('close', () => {
  if(dialog.returnValue === 'confirm' && pendingForm) {
    pendingForm.dataset.confirmed = 'true'; pendingForm.requestSubmit();
  }
  pendingForm = null; dialog.returnValue = '';
});
document.querySelectorAll('[data-demo]').forEach(button => button.addEventListener('click', () => {
  document.getElementById('email').value = button.dataset.demo === 'admin' ? 'maria.santos@escola.edu.br' : 'ana.lima@escola.edu.br';
  document.getElementById('password').value = 'SalaHub@2026';
  document.getElementById('email').focus();
}));
document.addEventListener('click', e => {
  document.querySelectorAll('details.notifications[open]').forEach(details => { if(!details.contains(e.target)) details.open = false; });
});
