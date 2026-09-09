import './bootstrap';

document.addEventListener('click', (event) => {
  const el = event.target.closest('[data-confirm]');
  if (el && !window.confirm(el.dataset.confirm)) event.preventDefault();
});

window.flashCellSaved = (input) => {
  input.classList.add('ring-2', 'ring-neutral-300');
  window.setTimeout(() => input.classList.remove('ring-2', 'ring-neutral-300'), 500);
};
