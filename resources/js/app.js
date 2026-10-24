const menuToggle = document.querySelector('#menu-toggle');
const sidebar = document.querySelector('#sidebar');
const closeSidebar = () => {
    document.body.classList.remove('sidebar-open');
    menuToggle?.setAttribute('aria-expanded', 'false');
    if (window.innerWidth <= 760 && sidebar) sidebar.inert = true;
};
if (sidebar && window.innerWidth <= 760) sidebar.inert = true;
menuToggle?.addEventListener('click', () => {
    sidebar.inert = false;
    document.body.classList.add('sidebar-open');
    menuToggle.setAttribute('aria-expanded', 'true');
    sidebar.querySelector('button')?.focus();
});
document.querySelectorAll('[data-close-sidebar]').forEach(button => button.addEventListener('click', () => { closeSidebar(); menuToggle?.focus(); }));
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) { closeSidebar(); menuToggle?.focus(); }
    if (event.key === 'Tab' && document.body.classList.contains('sidebar-open')) {
        const focusable = [...sidebar.querySelectorAll('a,button,summary')].filter(element => element.getClientRects().length);
        const first = focusable[0], last = focusable.at(-1);
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
});
window.matchMedia('(min-width: 761px)').addEventListener('change', event => { closeSidebar(); if (sidebar) sidebar.inert = !event.matches; });
const navigationGroups = [...document.querySelectorAll('.nav-group')];
navigationGroups.forEach(group => group.addEventListener('toggle', () => {
    if (group.open) navigationGroups.filter(other => other !== group).forEach(other => { other.open = false; });
}));
document.addEventListener('click', event => {
    if (!event.target.closest('.nav-group')) navigationGroups.forEach(group => { group.open = false; });
    if (!event.target.closest('.account-menu')) document.querySelector('.account-menu')?.removeAttribute('open');
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        const activeGroup = navigationGroups.find(group => group.open);
        activeGroup?.querySelector('summary')?.focus();
        navigationGroups.forEach(group => { group.open = false; });
        document.querySelector('.account-menu')?.removeAttribute('open');
    }
});
document.querySelectorAll('[data-dismiss]').forEach(button => button.addEventListener('click', () => button.closest('.alert').remove()));
document.querySelector('[data-password-toggle]')?.addEventListener('click', event => {
    const input = document.querySelector('#password');
    const showing = input.type === 'password';
    input.type = showing ? 'text' : 'password';
    event.currentTarget.setAttribute('aria-label', showing ? 'Ocultar contraseña' : 'Mostrar contraseña');
    event.currentTarget.setAttribute('aria-pressed', String(showing));
});
const dialog = document.querySelector('#confirm-dialog');
let pendingForm;
document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (form.dataset.confirmed === 'true') return;
    event.preventDefault(); pendingForm = form;
    document.querySelector('#confirm-message').textContent = form.dataset.confirm;
    dialog.returnValue = 'cancel';
    dialog.showModal();
}));
dialog?.addEventListener('cancel', () => { dialog.returnValue = 'cancel'; });
dialog?.addEventListener('close', () => {
    if (dialog.returnValue === 'confirm' && pendingForm) { pendingForm.dataset.confirmed = 'true'; pendingForm.requestSubmit(); }
    pendingForm = null;
});
const gradeForm = document.querySelector('[data-grade-form]');
let hasUnsavedGrades = false;
gradeForm?.addEventListener('input', () => {
    hasUnsavedGrades = true;
    document.querySelector('[data-save-status]').textContent = 'Tienes cambios sin guardar';
});
window.addEventListener('beforeunload', event => { if (hasUnsavedGrades) { event.preventDefault(); event.returnValue = ''; } });
gradeForm?.addEventListener('submit', () => { hasUnsavedGrades = false; });
document.querySelectorAll('form[method="POST"]').forEach(form => form.addEventListener('submit', event => {
    if (event.defaultPrevented) return;
    const button = event.submitter;
    if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
}));
document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
document.querySelector('.alert-error')?.focus();
