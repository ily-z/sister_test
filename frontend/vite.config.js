import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '')

    /*
     * VITE_API_BASE_URL dibakar ke dalam hasil build.
     *
     * PENTING: nilai ini HARUS kosong saat build produksi. Kalau diisi
     * (mis. http://10.0.0.2:8000), SPA akan memakai URL absolut dan
     * seluruh request harus lolos CORS. Kalau kosong, axios memakai
     * path relatif (/api/...) dan request jadi same-origin -- lebih
     * cepat, tanpa preflight, dan tidak bergantung pada konfigurasi
     * CORS backend sama sekali.
     *
     * Trade-off: butuh proxy /api di nginx server frontend (lihat
     * docs/DEPLOY.md). Itulah alasan mode ini ada.
     */
    const apiBaseUrl = env.VITE_API_BASE_URL ?? ''

    if (!apiBaseUrl) {
        // Hanya peringatan, bukan error: mode produksi memang begitu,
        // asalkan nginx sudah meng-proxy /api (lihat docs/DEPLOY.md).
        if (mode === 'production') {
            console.warn(
                '\n[vite] VITE_API_BASE_URL kosong -> SPA memakai path relatif /api/*.\n' +
                '       Pastikan nginx di server frontend meng-proxy /api ke backend.\n' +
                '       Lihat docs/DEPLOY.md bagian "Server #1 — Frontend" (langkah nginx).\n'
            )
        }
    }

    return {
        plugins: [vue()],

        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./src', import.meta.url)),
            },
        },

        define: {
            __API_BASE_URL__: JSON.stringify(apiBaseUrl),
        },

        server: {
            host: true,
            port: 5173,
            /*
             * Proxy ini hanya untuk mode `npm run dev`. Backend dan
             * frontend ada di IP yang berbeda, jadi tanpa proxy ini
             * setiap request dari browser akan kena CORS preflight.
             * Dengan proxy, browser selalu melihat origin yang sama dan
             * CORS tidak pernah tersentuh.
             *
             * Di produksi tidak ada Vite; perannya digantikan proxy
             * nginx (lihat docs/DEPLOY.md).
             */
            proxy: {
                '/api': {
                    target: env.VITE_DEV_PROXY_TARGET || 'http://127.0.0.1:8000',
                    changeOrigin: true,
                },
            },
        },

        build: {
            outDir: 'dist',
            sourcemap: mode !== 'production',
            chunkSizeWarningLimit: 800,
            rollupOptions: {
                output: {
                    /*
                     * Pisahkan vendor supaya file yang seldom berubah
                     * (Vue, axios) tidak ikut ter-cache ulang setiap kali
                     * ada perubahan kode aplikasi.
                     */
                    manualChunks: {
                        vendor: ['vue', 'vue-router', 'pinia'],
                        http: ['axios'],
                    },
                },
            },
        },
    }
})
