'use strict';
document.querySelectorAll('[data-reveal]').forEach(button => {
  button.addEventListener('click', () => {
    const field = document.getElementById(button.dataset.reveal);
    const show = field.type === 'password';
    field.type = show ? 'text' : 'password';
    button.textContent = show ? 'Ocultar' : 'Mostrar';
    button.setAttribute('aria-pressed', String(show));
  });
});
const help = document.getElementById('help-form');
if (help) help.addEventListener('submit', event => {
  event.preventDefault();
  const body = ['Olá, administrador.', '', 'Preciso de ajuda para acessar a Calculadora Progress.',
    'Nome: ' + document.getElementById('help-name').value.trim(),
    'E-mail do cadastro: ' + document.getElementById('help-email').value.trim(), '',
    'Descrição:', document.getElementById('help-message').value.trim()].join('\r\n');
  window.location.href = 'mailto:' + help.dataset.admin + '?subject=' + encodeURIComponent('Ajuda de acesso — Calculadora Progress') + '&body=' + encodeURIComponent(body);
});
