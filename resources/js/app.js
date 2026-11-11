import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy-js';

createInertiaApp({
    title: (title) => (title ? `${title} | KEI` : 'KEI'),
    // route() in templates and <script setup> (route list is injected by @routes).
    withApp(app) {
        app.use(ZiggyVue);
    },
});
