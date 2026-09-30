import { defineStore } from 'pinia'
import { api, readToken, writeToken, onUnauthorized } from '@/lib/api'

const USER_KEY = 'pf.user'

function readCachedUser() {
    try {
        const raw = window.localStorage.getItem(USER_KEY)
        return raw ? JSON.parse(raw) : null
    } catch {
        return null
    }
}

function writeCachedUser(user) {
    try {
        if (user) {
            window.localStorage.setItem(USER_KEY, JSON.stringify(user))
        } else {
            window.localStorage.removeItem(USER_KEY)
        }
    } catch {
        // Diabaikan dengan sengaja, lihat writeToken() di lib/api.js.
    }
}

/**
 * ══════════════════════════════════════════════════════════════════════════
 *  STORE AUTH
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Menyimpan dua hal: token, dan profil user beserta daftar permission-nya.
 *
 *  PENTING: permission di frontend ini hanya untuk MEMBANGUN TAMPILAN.
 *  Menyembunyikan tombol "Hapus Armada" bukan mekanisme keamanan. Yang
 *  menentukan boleh atau tidak tetap middleware `permission:` di backend.
 *
 *  Frontend tetap harus menyembunyikan menu yang tidak boleh diakses,
 *  bukan karena untuk keamanan, tapi supaya operator tidak melihat tombol
 *  yang pasti ditolak backend -- dan lebih penting, supaya bug di backend
 *  tidak langsung berarti user salah klik dan kehilangan datanya.
 */
export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: readCachedUser(),
        token: readToken(),

        /**
         * Status bootstrap. values:
         *   'idle'  -> aplikasi baru dimuat, belum ada yang dicek
         *   'ready' -> sudah diketahui: login sah atau tidak
         *
         * selama nilainya 'idle', router guard menunggu. Ini mencegah
         * kilat halaman login pada pengguna yang sebenarnya masih login --
         * kondisi yang sangat mudah terjadi kalau guard langsung
         * memutuskan "tidak ada user, arahkan ke /login" tanpa menunggu
         * respons /api/auth/me dulu.
         */
        status: 'idle',

        loginError: null,
        loggingIn: false,
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.token && state.user),

        /** Nama tampilan, dengan fallback supaya header tidak jadi kosong. */
        displayName: (state) => state.user?.nama || 'Pengguna',

        roleLabel: (state) => state.user?.role_name || state.user?.role || '-',

        /**
         * Daftar permission milik user ini, sebagai Set supaya pengecekan
         * di template tetap cepat kalau Sidebar merender dozens item.
         */
        permissionSet: (state) => new Set(state.user?.permissions || []),

        /**
         * Cek satu permission.
         *
         * Owner selalu lolos apa pun isi daftar permission-nya. Ini
         * mencerminkan App\Models\User::isOwner() di backend -- kalau
         * aturan berubah di sana, ubah juga di sini.
         */
        can(/** @type {string} */ permission) {
            if (!this.user) return false
            if (this.user.is_owner) return true
            return this.permissionSet.has(permission)
        },

        /**
         * Cek beberapa permission sekaligus (butuh salah satu).
         *
         * Dipakai untuk menu yang punya beberapa wali, mis. "Keuangan"
         * bisa dibuka lewat finance.view atau lewat manage_permissions.
         */
        canAny(/** @type {string[]} */ permissions = []) {
            if (!this.user) return false
            if (this.user.is_owner) return true
            return permissions.some((p) => this.permissionSet.has(p))
        },

        isOwner: (state) => Boolean(state.user?.is_owner),
    },

    actions: {
        /**
         * Cek apakah token yang tersimpan masih sah.
         *
         * Dipanggil sekali saat aplikasi dimuat. Ini satu-satunya cara
         * untuk tahu apakah token masih hidup, karena token yang sudah
         * kedaluwarsa tetap terbaca dari localStorage.
         */
        async bootstrap() {
            if (this.status !== 'idle') return

            if (!this.token) {
                this.status = 'ready'
                return
            }

            try {
                const { data } = await api.get('/auth/me')

                this.user = data.user
                this.status = 'ready'
                writeCachedUser(data.user)
            } catch {
                // 401, token kedaluwarsa, atau backend tidak bisa dihubungi.
                // Apa pun penyebabnya, dari sisi pengguna hasil akhirnya
                // sama saja: harus masuk ulang.
                this.clear()
                this.status = 'ready'
            }
        },

        /**
         * POST /api/auth/login
         *
         * Backend sengaja tidak memakai Auth::attempt() supaya API tetap
         * stateless. Konsekuensinya di sini: nama field yang dikirim adalah
         * "nama" (bukan "email"), dan password tidak pernah disimpan.
         */
        async login({ nama, password }) {
            this.loggingIn = true
            this.loginError = null

            try {
                const { data } = await api.post('/auth/login', {
                    nama,
                    password,
                    device_name: describeDevice(),
                })

                this.token = data.token
                this.user = data.user
                this.status = 'ready'

                writeToken(data.token)
                writeCachedUser(data.user)

                return data.user
            } catch (error) {
                // Pesan 422 dari backend sudah persis dalam bahasa yang
                // expedient untuk ditampilkan apa adanya.
                this.loginError =
                    error?.response?.data?.message ||
                    'Gagal masuk. Periksa nama dan password Anda.'

                throw error
            } finally {
                this.loggingIn = false
            }
        },

        /**
         * POST /api/auth/logout
         *
         * Token dicabut di server, bukan sekadar dihapus di browser.
         * Kalau ini dilewati, token lama tetap hidup di database sampai
         * kedaluwarsa -- dan siapa pun yang menyalin localStorage punya
         * akses penuh ke akun tersebut.
         */
        async logout() {
            const token = this.token

            // Bersihkan state lokal DULU, baru kabari server.
            //
            // Urutan ini disengaja: kalau server tidak bisa dihubungi,
            // pengguna tetap harus keluar dari antarmukanya. Menahan
            // navigasi hanya demi satu request yang mungkin gagal akan
            // membuat pengguna terjebak di halaman yang sudah tidak bisa
            // dipakai.
            this.clear()

            if (!token) return

            try {
                await api.post('/auth/logout')
            } catch {
                // Sengaja diabaikan. see catatan di atas.
            }
        },

        /** POST /api/auth/logout-all -- cabut token di semua perangkat. */
        async logoutAll() {
            this.clear()

            try {
                await api.post('/auth/logout-all')
            } catch {
                // Diabaikan dengan sengaja.
            }
        },

        /**
         * Dipanggil dari interceptor HTTP ketika backend membalas 401.
         *
         * Sengaja sinkron dan tanpa request: ini dipanggil dari dalam
         * request yang sedang gagal, jadi memanggil API lagi hanya akan
         * menghasilkan request lain yang gagal.
         */
        handleUnauthorized() {
            this.clear()
        },

        /**
         * Ambil ulang profil dari server.
         *
         * Berbeda dengan bootstrap(), aksi ini tidak mengabaikan status dan selalu
         * memanggil /auth/me. Dipakai setelah peran atau status akun berubah
         * di tengah sesi -- paling sering setelah owner mentransfer
         * kepemilikan ke orang lain, sehingga daftar permission di cache
         * lokal harus diganti dengan yang baru. Kalau tidak, menu dan
         * pemblokiran route masih memakai izin lama sampai halaman dimuat ulang.
         *
         * Kalau server membalas 401, token-nya memang sudah tidak berlaku,
         * jadi keadaannya dibersihkan -- sama seperti yang dilakukan
         * interceptor HTTP.
         */
        async refresh() {
            if (!this.token) return

            try {
                const { data } = await api.get('/auth/me')

                this.user = data.user
                this.status = 'ready'
                writeCachedUser(data.user)
            } catch {
                this.clear()
            }
        },

        /** Minta pengguna memasukkan password lama lalu password baru. */
        async changePassword(current_password, password, password_confirmation) {
            const { data } = await api.post('/auth/password', {
                current_password,
                password,
                password_confirmation,
            })

            return data.message
        },

        clear() {
            this.user = null
            this.token = null
            this.status = 'ready'
            this.loginError = null

            writeToken(null)
            writeCachedUser(null)
        },
    },
})

/**
 * Nama token per perangkat.
 *
 * Tujuannya: kalau user login ulang di browser yang sama, token lama
 * yang tidak terpakai ikut terhapus oleh backend (backend menghapus token
 * dengan nama yang sama sebelum menerbitkan yang baru). Tanpa ini,
 * tabel personal_access_tokens akan menumpuk token mati.
 *
 * Kalau string ini terlalu panjang atau tidak mengandung huruf/angka,
 * backend akan memakai fallback 'spa'.
 */
function describeDevice() {
    const raw = window.navigator.userAgent || 'browser'

    // Buang karakter yang tidak aman untuk nama token.
    const cleaned = raw.replace(/[^a-zA-Z0-9 ._\-()]/g, '').slice(0, 50)

    return cleaned || 'browser'
}

/*
 * Interceptor 401 -&gt; store auth.
 *
 * Dipasang di sini, bukan di router, supaya setiap request yang ditolak
 * backend langsung membersihkan state tanpa harus lewat halaman.
 */
onUnauthorized(() => {
    useAuthStore().handleUnauthorized()
})