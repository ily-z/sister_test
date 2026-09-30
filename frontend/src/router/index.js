import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  PETA RUTE
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Jalur di sini adalah KONTRAK dengan backend. Backend mengembalikan
 *  `redirect_path` yang persis berupa string seperti "/bookings/12" -- nilai
 *  itu dipakai sebagai tujuan navigasi setelah create / update / delete.
 *
 *  Konsekuensinya: jangan mengganti format jalur tanpa ikut mengubah nilai
 *  redirect_path di controller backend. Kalau satu sisi berubah dan sisi lain
 *  tidak, hasilnya adalah redirect ke 404 yang sulit ditelusuri.
 *
 *  Setiap route boleh punya `permission`. Field ini HANYA untuk menyembunyikan
 *  menu dan mencegah klik yang pasti ditolak. Penegakan yang sebenarnya ada di
 *  middleware `permission:` di backend -- kalau permission tidak dipasang di
 *  sini, pengguna tetap melihat halaman kosong berisi JSON 403.
 */

const routes = [
    /* ── Publik ─────────────────────────────────────────────────────── */

    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/auth/LoginView.vue'),
        meta: { layout: 'blank', guestOnly: true },
    },

    /*
     * Verifikasi invoice untuk umum.
     *
     * Case ini butuh route tanpa akun: QR yang tercetak di invoice kertas
     * dipindai calon penyewa yang belum punya akun sistem.
     *
     * Route-nya sengaja ada di FRONTEND, bukan di backend. Kalau tetap
     * Blade di backend, halaman itu akan hidup di server #2 dan QR di
     * invoice harus menunjuk ke server #2 -- pemisahan server jadi sia-sia,
     * dan tampilan frontend ikut terbawa ke backend.
     *
     * Karena itu backend/.env punya FRONTEND_URL: nilai itu yang dipakai
     * App\Http\Controllers\Api\InvoiceController untuk membangun tautan QR.
     * Mengubah FRONTEND_URL berarti semua invoice yang dicetak ulang
     * memakai tautan baru; invoice yang sudah tercetak tetap menunjuk ke
     * alamat lamanya.
     */
    {
        path: '/verify/:token',
        name: 'verify-invoice',
        component: () => import('@/views/public/VerifyInvoiceView.vue'),
        meta: { layout: 'blank', public: true },
    },

    /* ── Dengan AppLayout ───────────────────────────────────────────── */

    {
        path: '/',
        component: () => import('@/layouts/AppLayout.vue'),
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('@/views/dashboard/DashboardView.vue'),
                // Dashboard is NOT guarded by permission on purpose.
                // The original app also opened the dashboard for every role
                // (see the PermissionEnforcement test and the
                // commit "dashboard now dont need role permission").
                // Putting a permission here would make reception unable to
                // enter the application at all.
                meta: { title: 'Dashboard' },
            },

            /* Armada */
            {
                path: 'armada',
                name: 'armada.index',
                component: () => import('@/views/armada/ArmadaIndexView.vue'),
                meta: { title: 'Armada', permission: 'fleet.view' },
            },
            {
                path: 'armada/create',
                name: 'armada.create',
                component: () => import('@/views/armada/ArmadaFormView.vue'),
                meta: { title: 'Tambah Kendaraan', permission: 'fleet.create' },
            },
            {
                path: 'armada/:id(\\d+)',
                name: 'armada.show',
                component: () => import('@/views/armada/ArmadaShowView.vue'),
                meta: { title: 'Detail Kendaraan', permission: 'fleet.view' },
            },
            {
                path: 'armada/:id(\\d+)/edit',
                name: 'armada.edit',
                component: () => import('@/views/armada/ArmadaFormView.vue'),
                meta: { title: 'Ubah Kendaraan', permission: 'fleet.edit' },
            },
            {
                path: 'armada/bengkel',
                name: 'armada.bengkel',
                component: () => import('@/views/armada/BengkelView.vue'),
                meta: { title: 'Bengkel', permission: 'service.view' },
            },
            /*
             * Jalur ini bukan pilihan: ArmadaController::storeBengkel()
             * mengembalikan redirect_path ke "/armada/{id}/bengkel/create"
             * setiap kali servis disimpan dengan status open. Itu yang membuat
             * operator bisa melanjutkan pengisian rincian tanpa menekan tombol
             * lagi. Kalau rutenya hilang, save sukses tapi operator mendarat di
             * 404.
             */
            {
                path: 'armada/:id(\\d+)/bengkel/create',
                name: 'armada.bengkel.create',
                component: () => import('@/views/armada/BengkelFormView.vue'),
                meta: { title: 'Catat Servis', permission: 'service.create' },
            },
            {
                path: 'armada/sale-template',
                name: 'armada.sale-template',
                component: () => import('@/views/armada/SaleTemplateView.vue'),
                meta: { title: 'Template Penjualan', permission: 'fleet.view' },
            },

            /* Penyewaan */
            {
                path: 'bookings',
                name: 'bookings.index',
                component: () => import('@/views/bookings/BookingIndexView.vue'),
                meta: { title: 'Pesanan', permission: 'bookings.view' },
            },
            {
                path: 'bookings/create',
                name: 'bookings.create',
                component: () => import('@/views/bookings/BookingFormView.vue'),
                meta: { title: 'Buat Pesanan', permission: 'bookings.create' },
            },
            {
                path: 'bookings/calendar',
                name: 'bookings.calendar',
                component: () => import('@/views/bookings/BookingCalendarView.vue'),
                meta: { title: 'Kalender Sewa', permission: 'bookings.view' },
            },
            {
                path: 'bookings/:id(\\d+)',
                name: 'bookings.show',
                component: () => import('@/views/bookings/BookingShowView.vue'),
                meta: { title: 'Detail Pesanan', permission: 'bookings.view' },
            },
            {
                path: 'bookings/:id(\\d+)/edit',
                name: 'bookings.edit',
                component: () => import('@/views/bookings/BookingFormView.vue'),
                meta: { title: 'Ubah Pesanan', permission: 'bookings.edit' },
            },

            /* Keuangan */
            {
                path: 'keuangan',
                name: 'keuangan.index',
                component: () => import('@/views/keuangan/KeuanganIndexView.vue'),
                meta: { title: 'Keuangan', permission: 'finance.view' },
            },
            {
                path: 'keuangan/per-mobil',
                name: 'keuangan.per-mobil',
                component: () => import('@/views/keuangan/PerMobilView.vue'),
                meta: { title: 'Keuangan per Mobil', permission: 'finance.view' },
            },
            {
                path: 'keuangan/pengeluaran-kantor',
                name: 'keuangan.pengeluaran-kantor',
                component: () => import('@/views/keuangan/PengeluaranKantorView.vue'),
                meta: { title: 'Pengeluaran Kantor', permission: 'finance.view' },
            },

            /* Surat menyurat */
            {
                path: 'surat-menyurat',
                name: 'surat.index',
                component: () => import('@/views/surat/SuratIndexView.vue'),
                meta: { title: 'Surat Menyurat', permission: 'bookings.view' },
            },
            {
                path: 'surat-menyurat/create',
                name: 'surat.create',
                component: () => import('@/views/surat/SuratFormView.vue'),
                meta: { title: 'Buat Surat', permission: 'bookings.edit' },
            },
            {
                path: 'surat-menyurat/arsip',
                name: 'surat.arsip',
                component: () => import('@/views/surat/ArsipView.vue'),
                meta: { title: 'Arsip Surat', permission: 'bookings.view' },
            },
            {
                path: 'surat-menyurat/template',
                name: 'surat.template',
                component: () => import('@/views/surat/TemplateSuratView.vue'),
                meta: { title: 'Template Surat', permission: 'bookings.view' },
            },

            /* Laporan */
            {
                path: 'analytics',
                name: 'analytics',
                component: () => import('@/views/dashboard/AnalyticsView.vue'),
                meta: { title: 'Analytics', permission: 'analytics.view' },
            },
            {
                path: 'activity',
                name: 'activity',
                component: () => import('@/views/dashboard/ActivityView.vue'),
                meta: { title: 'Aktivitas', permission: 'analytics.view' },
            },
            {
                path: 'qr-scanner',
                name: 'qr-scanner',
                component: () => import('@/views/tools/QrScannerView.vue'),
                meta: { title: 'Pindai QR' },
            },

            /* Admin */
            {
                path: 'users',
                name: 'users.index',
                component: () => import('@/views/admin/UserListView.vue'),
                meta: { title: 'Pengguna' },
            },
            {
                path: 'permissions',
                name: 'permissions.index',
                component: () => import('@/views/admin/PermissionView.vue'),
                meta: { title: 'Permission & Role', permission: 'manage_permissions' },
            },
            {
                path: 'account',
                name: 'account',
                component: () => import('@/views/account/AccountView.vue'),
                meta: { title: 'Akun Saya' },
            },
        ],
    },

    /* ── Cetak invoice ─────────────────────────────────────────────── */

    /*
     * Di luar AppLayout, jadi tidak ada sidebar dan topbar.
     *
     * Halaman cetak butuh ruang lebar penuh: format A4-nya 210mm, dan
     * sidebar 260px akan membuat pratinjau harus di-scroll hanya untuk
     * melihat kolom tanda tangan.
     *
     * `layout: 'blank'` tanpa `public: true` supaya tetap butuh login --
     * InvoiceController::print memakai middleware permission:finance.view.
     */
    {
        path: '/invoices/:id(\\d+)/print',
        name: 'invoice.print',
        component: () => import('@/views/invoice/InvoicePrintView.vue'),
        meta: { layout: 'blank', title: 'Cetak Invoice', permission: 'finance.view' },
    },
    {
        path: '/invoices/:id(\\d+)/print-bengkel',
        name: 'invoice.print-bengkel',
        component: () => import('@/views/invoice/InvoicePrintBengkelView.vue'),
        meta: {
            layout: 'blank',
            title: 'Cetak Invoice Bengkel',
            permission: 'finance.view',
        },
    },

    /* ── Error ──────────────────────────────────────────────────────── */

    {
        path: '/forbidden',
        name: 'forbidden',
        component: () => import('@/views/error/ForbiddenView.vue'),
        meta: { layout: 'blank', public: true },
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/views/error/NotFoundView.vue'),
        meta: { layout: 'blank', public: true },
    },
]

const router = createRouter({
    /*
     * createWebHistory, BUKAN createWebHashHistory.
     *
     * Mode history berarti URL-nya bersih (/armada/12) dan bisa dibagikan.
     * Konsekuensinya WAJIB ada aturan "try_files ... /index.html" di nginx
     * server frontend -- kalau tidak, memuat /armada/12 langsung dari server
     * akan membalas 404 dan hanya refresh yang menyembuhkan.
     *
     * Mode hash (/armada/12 -> /#/armada/12) tidak butuh aturan tersebut,
     * tapi URL-nya jelek dan tidak cocok dengan redirect_path dari backend.
     *
     * Aturan nginx-nya ada di docs/DEPLOY.md.
     */
    history: createWebHistory(import.meta.env.BASE_URL),

    routes,

    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition

        // Jangan scroll ke atas kalau hanya pindah query string
        // (mis. ganti filter pada halaman daftar).
        if (to.path === from.path) return {}

        return { top: 0 }
    },
})

/* ── Guard ──────────────────────────────────────────────────────────── */

router.beforeEach(async (to) => {
    const auth = useAuthStore()

    /*
     * Tunggu bootstrap SEBELUM memutuskan apa pun.
     *
     * Tanpa await di sini, guard akan melihat localStorage yang belum
     * sempat diperbarui, lalu mengarahkan pengguna yang sebenarnya masih
     * login ke halaman login. Gejalanya: setiap reload memunculkan
     * halaman login sesaat sebelum masuk ke aplikasi.
     */
    await auth.bootstrap()

    /* Route publik (login, verifikasi invoice, halaman error). */
    if (to.meta.public || to.meta.guestOnly) {
        if (to.meta.guestOnly && auth.isAuthenticated) {
            return { name: 'dashboard' }
        }

        return true
    }

    /* Semua sisanya butuh login. */
    if (!auth.isAuthenticated) {
        /*
         * Simpan tujuan supaya setelah login berhasil pengguna kembali
         * ke halaman yang tadi dicoba, bukan ke dashboard.
         *
         * `redirect` disimpan di query, bukan state Pinia: kalau pengguna
         * membuka tautan di tab baru, tujuannya harus ikut terbawa.
         */
        return { name: 'login', query: { redirect: to.fullPath } }
    }

    /*
     * Permission di sini hanya untuk tampilan, bukan untuk keamanan.
     *
     * Backend memeriksa ulang setiap request. Guard ini ada supaya
     * pengguna tidak mendarat di halaman kosong berisi JSON 403, dan
     * supaya menu dan halaman yang diizinkan tetap konsisten.
     */
    if (to.meta.permission && !auth.can(to.meta.permission)) {
        return { name: 'forbidden' }
    }

    return true
})

/*
 * Judul tab.
 *
 * Pattern "Halaman - Precision Fleet" dipakai supaya di tab browser yang
 * banyak terbuka, operator tahu yang aktif tanpa perlu melihat isinya.
 * Judul ini juga yang muncul di riwayat peramban.
 */
router.afterEach((to) => {
    const judul = to.meta.title

    document.title = judul ? `${judul} - Precision Fleet` : 'Precision Fleet'
})

export default router