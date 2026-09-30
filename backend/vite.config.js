import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/calendar.js',
                'resources/js/booking-form.js',
                'resources/js/booking-show.js',
                'resources/js/qr-scanner.js',
                'resources/js/quill-init.js',
                'resources/js/invoice-signature.js',
            ],
            refresh: true,
        }),
    ],
});
