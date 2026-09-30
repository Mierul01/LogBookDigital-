import './bootstrap';
import SignaturePad from 'signature_pad';

// Supervisor signature box on the logbook review form.
document.querySelectorAll('[data-signature-pad]').forEach((wrapper) => {
    const canvas = wrapper.querySelector('canvas');
    const input = wrapper.querySelector('input[type="hidden"]');
    const clear = wrapper.querySelector('[data-signature-clear]');
    const form = wrapper.closest('form');
    const pad = new SignaturePad(canvas, { penColor: '#18181b' });

    const resize = () => {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const data = pad.toData();
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        pad.clear();
        pad.fromData(data);
    };

    window.addEventListener('resize', resize);
    resize();

    clear?.addEventListener('click', () => pad.clear());

    form?.addEventListener('submit', () => {
        input.value = pad.isEmpty() ? '' : pad.toDataURL('image/png');
    });
});

// Ask before any destructive form is submitted.
document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

// Mobile sidebar toggle.
document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        document.querySelector('[data-sidebar]')?.classList.toggle('-translate-x-full');
        document.querySelector('[data-sidebar-backdrop]')?.classList.toggle('hidden');
    });
});
