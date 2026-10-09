import Alpine from 'alpinejs';

window.Alpine = Alpine;

const store = (key, value) => {
    try {
        if (value === undefined) {
            return localStorage.getItem(key);
        }
        localStorage.setItem(key, value);
    } catch {
        return null;
    }
};

// Light/dark toggle. The initial theme is set by an inline script before first paint.
Alpine.data('themeToggle', () => ({
    dark: document.documentElement.dataset.theme === 'dark',
    toggle() {
        this.dark = !this.dark;
        const theme = this.dark ? 'dark' : 'light';
        document.documentElement.dataset.theme = theme;
        store('theme', theme);
    },
}));

// Sticky header that shrinks after a little scroll, plus the mobile menu.
Alpine.data('siteHeader', () => ({
    scrolled: false,
    open: false,
    init() {
        this.onScroll();
    },
    onScroll() {
        this.scrolled = window.scrollY > 24;
    },
    close() {
        this.open = false;
    },
}));

// Expandable timeline node.
Alpine.data('expandable', (open = false) => ({
    open,
    toggle() {
        this.open = !this.open;
    },
}));

// Fade-up on scroll. Elements are only hidden by CSS when motion is allowed.
const reveal = () => {
    const nodes = document.querySelectorAll('[data-reveal]');
    if (!('IntersectionObserver' in window)) {
        nodes.forEach((node) => node.classList.add('is-visible'));
        return;
    }
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
    );
    nodes.forEach((node) => observer.observe(node));
};

document.addEventListener('DOMContentLoaded', reveal);

Alpine.start();
