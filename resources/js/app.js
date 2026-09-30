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
