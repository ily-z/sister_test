/**
 * Palet dan token desain.
 *
 * Nilainya sengaja disalin PERSIS dari <script>tailwind.config di
 * backend/resources/views/layouts/app.blade.php supaya tampilan SPA
 * sama dengan tampilan Blade yang sekarang masih jalan. Kalau salah
 * satu diubah, ubah keduanya.
 */
export default {
    content: ['./index.html', './src/**/*.{vue,js}'],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
            colors: {
                brand: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    900: '#1e3a8a',
                },
            },
            /*
             * Sidebar punya dua mode (lebar 16rem dan sempit 4.5rem).
             * Lebarnya diubah lewat inline style di AppLayout, tapi
             * padding kontennya dipakai sebagai kelas, jadi nilai
             * default-nya didefinisikan di sini supaya tidak ada angka
             * yang tercecer di beberapa tempat.
             */
            spacing: {
                sidebar: '16rem',
                'sidebar-collapsed': '4.5rem',
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/container-queries'),
    ],
}
