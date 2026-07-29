import '../css/app.css';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import Layout from './Layout';

createInertiaApp({
    title: (title) => (title ? `${title} · BulkApply` : 'BulkApply'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        const page = pages[`./Pages/${name}.jsx`];
        // Auth pages render bare; everything else gets the sidebar shell.
        if (page.default.layout === undefined && !name.startsWith('Auth/')) {
            page.default.layout = (pageEl) => <Layout>{pageEl}</Layout>;
        }
        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    // Disabled: Layout.jsx's own <ProgressBar /> (an ECG-style loader spanning
    // the full viewport top) replaces Inertia's default NProgress-style bar —
    // having both on screen at once during a visit was the bug being fixed here.
    progress: false,
});
