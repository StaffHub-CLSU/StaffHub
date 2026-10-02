import '../css/app.css';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const pages = import.meta.glob('./Pages/**/*.jsx');

const appBaseUrl = document.querySelector('meta[name="app-base-url"]')?.content || '';
const loginUrl = `${appBaseUrl}/login`;

window.addEventListener('storage', (event) => {
    if (event.key === 'staffhub:logout') {
        window.location.assign(loginUrl);
    }
});

createInertiaApp({
    resolve: (name) => resolvePageComponent('./Pages/' + name + '.jsx', pages),
    setup: ({ el, App, props }) => {
        router.on('httpException', (event) => {
            if (event.detail.response.status === 419) {
                event.preventDefault();
                window.location.assign(loginUrl);
            }
        });

        createRoot(el).render(<App {...props} />);
    },
});
