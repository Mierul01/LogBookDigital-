import './bootstrap';
import SignaturePad from 'signature_pad';
import TomSelect from 'tom-select';
import flatpickr from 'flatpickr';
import { Alert } from './alerts';

const escapeHtml = (text) => text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const checkIcon = '<svg class="option-check" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>';

// Styled dropdowns. A disabled <option> can carry a data-badge label
// (e.g. "Logged") that is shown as a pill in the menu.
document.querySelectorAll('select[data-select]').forEach((select) => {
    const badges = Object.fromEntries([...select.options].map((o) => [o.value, o.dataset.badge]));

    new TomSelect(select, {
        controlInput: null,
        // Render the menu on <body> so animated cards (which create their own stacking context) can't cover it.
        dropdownParent: 'body',
        maxOptions: null,
        allowEmptyOption: true,
        render: {
            option: (data, escape) => {
                const badge = badges[data.value];
                return `<div>${escape(data.text)}${badge ? `<span class="option-badge">${escapeHtml(badge)}</span>` : checkIcon}</div>`;
            },
            item: (data, escape) => `<div>${escape(data.text)}</div>`,
        },
        onInitialize() {
            // Tom Select copies the <select>'s classes onto its wrapper; the
            // field look comes from .ts-control instead, so drop .input.
            this.wrapper.classList.remove('input');
        },
    });
});

// Calendar date picker. The form still submits Y-m-d; the user sees a friendly date.
document.querySelectorAll('input[data-datepicker]').forEach((input) => {
    flatpickr(input, {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'l, j F Y',
        disableMobile: true,
        monthSelectorType: 'static',
        prevArrow: '<svg viewBox="0 0 24 24"><path d="M15.4 5.4 14 4l-8 8 8 8 1.4-1.4L8.8 12z"/></svg>',
        nextArrow: '<svg viewBox="0 0 24 24"><path d="M8.6 18.6 10 20l8-8-8-8-1.4 1.4 6.6 6.6z"/></svg>',
        onReady(_, __, fp) {
            const footer = document.createElement('div');
            footer.className = 'flatpickr-footer';
            footer.innerHTML = '<button type="button" class="text-zinc-500 hover:bg-zinc-100" data-fp-clear>Clear</button>'
                + '<button type="button" class="bg-brand-600 text-white hover:bg-brand-700" data-fp-today>Today</button>';
            footer.querySelector('[data-fp-today]').addEventListener('click', () => { fp.setDate(new Date(), true); fp.close(); });
            footer.querySelector('[data-fp-clear]').addEventListener('click', () => fp.clear());
            fp.calendarContainer.appendChild(footer);
        },
    });
});

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

    form?.addEventListener('submit', (event) => {
        if (pad.isEmpty()) {
            // Stop here so the "Approve & sign?" confirmation doesn't open for an empty pad.
            event.preventDefault();
            event.stopImmediatePropagation();
            Alert.fire({ icon: 'info', title: 'Signature needed', text: 'Please sign in the signature box before approving this entry.', confirmButtonText: 'OK' });
            return;
        }
        input.value = pad.toDataURL('image/png');
    });
});

// Eye button that shows or hides a password field.
document.querySelectorAll('[data-password-toggle]').forEach((wrapper) => {
    const input = wrapper.querySelector('input');
    const button = wrapper.querySelector('[data-password-toggle-button]');

    button.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(show));
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        wrapper.querySelector('[data-icon-show]').classList.toggle('hidden', show);
        wrapper.querySelector('[data-icon-hide]').classList.toggle('hidden', !show);
    });
});

// Mobile sidebar toggle.
document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        document.querySelector('[data-sidebar]')?.classList.toggle('-translate-x-full');
        document.querySelector('[data-sidebar-backdrop]')?.classList.toggle('hidden');
    });
});

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Count a number up from 0 to its final value.
const countUp = (el) => {
    const target = Number(el.dataset.count);
    if (reducedMotion || !target) {
        el.textContent = target;
        return;
    }
    const duration = 900;
    const start = performance.now();
    const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(target * eased);
        if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
};

// Grow a progress bar from 0 to its final width.
const fillBar = (el) => {
    requestAnimationFrame(() => {
        el.style.width = `${el.dataset.progress}%`;
    });
};

// Reveal elements as they scroll into view. Siblings inside a
// [data-reveal-group] get a small staggered delay.
document.querySelectorAll('[data-reveal-group]').forEach((group) => {
    group.querySelectorAll(':scope > [data-reveal]').forEach((el, i) => {
        el.style.setProperty('--reveal-delay', `${i * 70}ms`);
    });
});

const reveal = (el) => {
    if (el.classList.contains('is-visible')) return;
    el.classList.add('is-visible');
    el.querySelectorAll('[data-count]').forEach(countUp);
    el.querySelectorAll('[data-progress]').forEach(fillBar);
    observer.unobserve(el);
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => entry.isIntersecting && reveal(entry.target));
}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

document.querySelectorAll('[data-reveal]').forEach((el) => {
    observer.observe(el);
    // Anything already on screen at load is revealed right away, even if the
    // observer is slow to report (e.g. the tab opened in the background).
    if (el.getBoundingClientRect().top < window.innerHeight) reveal(el);
});

// Numbers and bars that sit outside a revealed block animate straight away.
document.querySelectorAll('[data-count]:not([data-reveal] [data-count])').forEach(countUp);
document.querySelectorAll('[data-progress]:not([data-reveal] [data-progress])').forEach(fillBar);

// Draw progress rings (SVG circles) from empty to their value.
document.querySelectorAll('[data-ring]').forEach((circle) => {
    setTimeout(() => circle.setAttribute('stroke-dasharray', `${circle.dataset.ring} 100`), 150);
});

// Give the sticky top bar a shadow once the page is scrolled.
const topbar = document.querySelector('[data-topbar]');
if (topbar) {
    const onScroll = () => topbar.classList.toggle('shadow-[0_4px_20px_-8px_rgba(16,24,40,0.12)]', window.scrollY > 4);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
}
