import Alpine from 'alpinejs';
import ajax from '@imacrayon/alpine-ajax';
import focus from '@alpinejs/focus'
import intersect from '@alpinejs/intersect'

window.Alpine = Alpine;
Alpine.plugin(ajax);
Alpine.plugin(focus)
Alpine.plugin(intersect)

Alpine.data('turnstileGuard', () => ({
    init() {
        const root = this.$el;
        const form = root.closest('form');
        const submit = form ? form.querySelector('button[type="submit"]') : null;

        const setReady = (ready) => {
            if (submit) submit.disabled = !ready;
        };

        // If the widget can't render or verify, never trap the form:
        // the server still validates the token on submit
        const allowFallback = () => setReady(true);

        setReady(false);

        // api.js auto-renders .cf-turnstile elements on load; forms swapped
        // back in after an AJAX error need an explicit render instead
        const render = () => {
            if (!window.turnstile) {
                setTimeout(render, 200);
                return;
            }
            if (root.querySelector('input, iframe')) return; // already rendered
            try {
                window.turnstile.render(root, {
                    sitekey: root.dataset.sitekey,
                    theme: root.dataset.theme || 'auto',
                    size: root.dataset.size || 'normal',
                });
            } catch (e) { /* already rendered */ }
        };

        // The verified token lands in the hidden response input; poll for it
        const pollToken = () => {
            const input = form ? form.querySelector('[name="cf-turnstile-response"]') : null;
            setReady(!!(input && input.value));
            setTimeout(pollToken, 300);
        };

        const start = () => {
            if (window.__turnstileLoaded) {
                render();
                pollToken();
            } else if (window.__turnstileLoading) {
                setTimeout(start, 200);
            } else {
                window.__turnstileLoading = true;
                const s = document.createElement('script');
                s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
                s.async = true;
                s.onload = () => {
                    window.__turnstileLoaded = true;
                    render();
                    pollToken();
                };
                s.onerror = () => allowFallback();
                document.head.appendChild(s);
            }
        };

        start();
        setTimeout(allowFallback, 15000);
    }
}));

Alpine.start();
