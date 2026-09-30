import { useAuthStore } from '@/stores/auth'

/**
 * ══════════════════════════════════════════════════════════════════════════
 *  STRUKTUR MENU
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Daftar menu dipisah dari komponennya supaya bisa dibaca tanpa membuka
 *  file .vue, dan supaya additions item baru hanya perlu satu tempat.
 *
 *  Bentuk tiap grup:
 *
 *    {
 *      key:      'bookings',          // untuk pencocokan sub-menu aktif
 *      label:    'Penyewaan',
 *      icon:     'event_note',
 *      to:       { name: 'bookings.index' },   // klik pada judul grup
 *      anyOf:    ['bookings.view'],   // izin untuk seluruh grup
 *      children: [ ... ],             // opsional
 *    }
 *
 *  ── SOAL PERMISSION DI SINI ────────────────────────────────────────────────
 *
 *  `anyOf` / `permission` menentukan APAKAH menu ditampilkan. Yang menentukan
 *  APAKAH aksi benar-benar boleh dilakukan tetap middleware `permission:` di
 *  backend.
 *
 *  Ini bukan sekadar catatan: kalau permission tidak dipasang di sini,
 *  menu akan tetap terlihat, operator akan menekan, backend akan membalas 403,
 *  dan gejalanya adalah "aplikasi error". Sebaliknya, kalau permission dipasang
 *  di sini tapi lupa di backend, menu terlihat tapi setiap aksi ditolak.
 *  Karena itu daftar di file ini dan daftar di backend/routes/api.php harus
 *  diperiksa bersama-sama setiap kali ada modul baru.
 */

export const NAVIGATION = [
    {
        key: 'dashboard',
        label: 'Dashboard',
        icon: 'grid_view',
        to: { name: 'dashboard' },
        // Tanpa permission: dashboard bisa dibuka semua role. Ini mengikuti
        // aplikasi Blade yang sekarang, dan sudah ada testnya di backend
        // (PermissionEnforcementTest). Jangan ditambahkan permission di sini
        // tanpa mengubah backend beserta testnya.
    },

    {
        key: 'bookings',
        label: 'Penyewaan',
        icon: 'event_note',
        to: { name: 'bookings.index' },
        anyOf: ['bookings.view'],
        activeNames: ['bookings.index', 'bookings.show', 'bookings.edit', 'bookings.create', 'bookings.calendar'],
        children: [
            { label: 'Daftar Penyewaan', to: { name: 'bookings.index' }, names: ['bookings.index', 'bookings.show', 'bookings.edit'] },
            { label: 'Buat Pesanan', to: { name: 'bookings.create' }, permission: 'bookings.create', names: ['bookings.create'] },
            { label: 'Kalender Penyewaan', to: { name: 'bookings.calendar' }, names: ['bookings.calendar'] },
        ],
    },

    {
        key: 'armada',
        label: 'Manajemen Armada',
        icon: 'directions_car',
        to: { name: 'armada.index' },
        anyOf: ['fleet.view', 'service.view'],
        activeNames: ['armada.index', 'armada.show', 'armada.edit', 'armada.create', 'armada.bengkel', 'armada.sale-template'],
        children: [
            { label: 'Daftar Kendaraan', to: { name: 'armada.index' }, names: ['armada.index', 'armada.show', 'armada.edit'] },
            { label: 'Tambah Mobil', to: { name: 'armada.create' }, permission: 'fleet.create', names: ['armada.create'] },
            { label: 'Servis & Bengkel', to: { name: 'armada.bengkel' }, permission: 'service.view', names: ['armada.bengkel'] },
        ],
    },

    {
        key: 'keuangan',
        label: 'Keuangan',
        icon: 'payments',
        to: { name: 'keuangan.index' },
        anyOf: ['finance.view'],
        activeNames: ['keuangan.index', 'keuangan.per-mobil', 'keuangan.pengeluaran-kantor'],
        children: [
            { label: 'Laporan Performa Bisnis', to: { name: 'keuangan.index' }, names: ['keuangan.index'] },
            { label: 'Laporan Keuangan per Mobil', to: { name: 'keuangan.per-mobil' }, names: ['keuangan.per-mobil'] },
            { label: 'Pengeluaran Kantor', to: { name: 'keuangan.pengeluaran-kantor' }, names: ['keuangan.pengeluaran-kantor'] },
        ],
    },

    {
        key: 'surat',
        label: 'Surat Menyurat',
        icon: 'description',
        to: { name: 'surat.index' },
        anyOf: ['bookings.view'],
        activeNames: ['surat.index', 'surat.arsip', 'surat.template', 'surat.create'],
        children: [
            { label: 'Nomor Surat', to: { name: 'surat.index' }, names: ['surat.index', 'surat.create'] },
            { label: 'Arsip', to: { name: 'surat.arsip' }, names: ['surat.arsip'] },
            { label: 'Template Surat', to: { name: 'surat.template' }, names: ['surat.template'] },
        ],
    },

    {
        key: 'users',
        label: 'Manajemen User',
        icon: 'person',
        to: { name: 'users.index' },
        // Tanpa permission eksplisit: route /api/users/* dilindungi oleh
        // App\Policies\UserPolicy di backend, bukan middleware permission.
        // Menu di sini tidak disembunyikan supaya.policy yang menentukan.
        // Kalau nanti decided untuk menyembunyikannya, permission yang cocok
        // adalah "users.view" -- tapi pastikan policy di backend ikut berubah.
    },

    {
        key: 'permissions',
        label: 'Permission & Role',
        icon: 'admin_panel_settings',
        to: { name: 'permissions.index' },
        permission: 'manage_permissions',
        activeNames: ['permissions.index'],
    },

    {
        key: 'separator',
        separator: true,
    },

    {
        key: 'qr',
        label: 'QR Scanner',
        icon: 'qr_code_scanner',
        to: { name: 'qr-scanner' },
        activeNames: ['qr-scanner'],
        emphasized: true,
    },
]

/**
 * Tambahkan ikon Material Symbols yang dipakai di aplikasi ini ke dalam
 * build.
 *
 * Material Symbols adalah font ligatur: nama ikon yang diketik berubah
 * menjadi glyph. Vite memindai file .vue untuk menentukan kelas Tailwind mana
 * yang harus disimpan -- tapi dia tidak bisa melihat nama ikon di dalam
 * interpolasi {{ }}. akibatnya ikon yang namanya muncul sebagai variabel akan
 * hilang dari hasil build.
 *
 * Daftar ini menyelesaikan masalah itu: nama ikon dideklarasikan sebagai
 * string literal dalam file ini, sehingga semua ikon yang dipakai ikut
 * terbawa. Kalau sebuah ikon tidak muncul di layar, cek dulu file ini
 * sebelum menyalahkan font.
 */
export const ICONS_IN_USE = [
    'grid_view', 'event_note', 'directions_car', 'payments', 'person',
    'admin_panel_settings', 'qr_code_scanner', 'description', 'settings',
    'help', 'add', 'menu', 'search', 'close', 'check_circle', 'error',
    'warning', 'info', 'cloud_off', 'logout', 'expand_more', 'calendar_today',
    'edit', 'delete', 'visibility', 'download', 'print', 'save', 'filter_list',
    'arrow_back', 'arrow_forward', 'more_vert', 'content_copy', 'refresh',
    'speed', 'build', 'receipt_long', 'groups', 'trending_up', 'trending_down',
    'chevron_left', 'chevron_right', 'keyboard_arrow_down', 'open_in_new',
    'upload', 'image', 'lock', 'email', 'badge', 'inventory_2', 'history',
    'check', 'schedule', 'do_not_disturb_on', 'edit_note', 'storefront',
]

/**
 * Saring daftar menu sesuai izin pengguna.
 *
 * Dipanggil Sidebar setiap kali state auth berubah, bukan hanya saat mount.
 * Kalau dihitung sekali di awal, menu tidak akan ikut berubah setelah user
 * logout lalu login sebagai akun dengan izin berbeda di tab yang sama.
 *
 * @returns {Array} grup yang sudah disaring, dengan `children` ikut disaring.
 */
export function visibleNavigation(auth = useAuthStore()) {
    return NAVIGATION.map((group) => {
        if (group.separator) return group

        // Grup tanpa permission: selalu tampil.
        if (!group.anyOf && !group.permission) {
            return withChildren(group, auth)
        }

        const boleh = group.anyOf
            ? auth.canAny(group.anyOf)
            : auth.can(group.permission)

        return boleh ? withChildren(group, auth) : null
    }).filter(Boolean)
}

function withChildren(group, auth) {
    if (!group.children) return group

    const children = group.children.filter(
        (child) => !child.permission || auth.can(child.permission)
    )

    return { ...group, children }
}