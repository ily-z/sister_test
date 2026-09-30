import axios from 'axios'

/**
 * ══════════════════════════════════════════════════════════════════════════
 *  KLIEN HTTP
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Semua request ke backend melewati objek ini. Ada satu alasan kuat kenapa
 *  konfigurasi CORS di backend dibuat longgar: file ini memakai
 *  `withCredentials: false`. Tanpa baris itu, axios akan mengirim cookie
 *  dan browser akan menolak response karena header
 *  `Access-Control-Allow-Credentials` tidak diizinkan bersama origin `*`.
 *
 *  ___________________ CARA KERJA DI DUA MODE ___________________
 *
 *  MODE A -- VITE_API_BASE_URL kosong (REKOMENDASI)
 *      baseURL = '' sehingga request menjadi /api/... pada origin yang sama.
 *      Nginx di server frontend meneruskannya ke backend.
 *      Tidak ada CORS, tidak ada preflight, IP backend tidak terekspos.
 *
 *  MODE B -- VITE_API_BASE_URL diisi
 *      Request lintas origin. Backend menjawab preflight OPTIONS, lalu
 *      request asli dikirim. Tetap jalan, tapi setiap response wajib punya
 *      header CORS yang benar -- termasuk saat terjadi error 4xx/5xx.
 *      Kalau tidak, browser menyembunyikan response dan axios melaporkan
 *      "Network Error" sehingga pesan aslinya hilang.
 */

/*
 * Token disimpan di localStorage, bukan cookie.
 *
 * Cookie lintas origin membutuhkan SameSite=None + Secure, dan Secure hanya
 * berlaku di HTTPS. Aplikasi ini sengaja harus jalan di HTTP biasa karena
 * akan diakses dari IP address seadanya. Token yang hanya dibaca JavaScript
 * tidak punya batasan tersebut.
 *
 * Konsekuensi yang harus disadari: localStorage bisa dibaca script mana pun
 * yang berjalan di origin yang sama. Ini alasan backend memakai Sanctum
 * personal access token (bisa dicabut, ada masa berlaku, token di-hash di
 * database) dan bukan sekadar session ID yang dikasih cookie longgar.
 */

const TOKEN_KEY = 'pf.token'

export function readToken() {
    try {
        return window.localStorage.getItem(TOKEN_KEY) || null
    } catch {
        // Browser dalam mode private / storage diblokir policy.
        // Lebih baik aplikasi tetap jalan (tanpa persistensi login)
        // daripada gagal total.
        return null
    }
}

export function writeToken(token) {
    try {
        if (token) {
            window.localStorage.setItem(TOKEN_KEY, token)
        } else {
            window.localStorage.removeItem(TOKEN_KEY)
        }
    } catch {
        // Diabaikan dengan sengaja, lihat readToken().
    }
}

/* Nilai ini dibakar Vite saat build (lihat vite.config.js). */
const BASE_URL = typeof __API_BASE_URL__ !== 'undefined' ? __API_BASE_URL__ : ''

export const api = axios.create({
    baseURL: BASE_URL,

    /*
     * Timeout 30 detik.
     *
     * Tanpa timeout, request yang menggantung -- misalnya backend sedang
     * macet karena deadlock, atau jaringan putus tanpa RST -- akan menggantung
     * selamanya dan spinner di layar tidak pernah berhenti.
     */
    timeout: 30000,

    /*
     * Penting: nilai yang benar adalah false, dan itu disengaja. Lihat
     * catatan MODE B di atas.
     */
    withCredentials: false,

    headers: {
        Accept: 'application/json',
    },
})

/* ── Request: sisipkan bearer token ─────────────────────────────────────── */

api.interceptors.request.use((config) => {
    const token = readToken()

    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }

    /*
     * Jangan set Content-Type untuk FormData.
     *
     * Kalau dipaksa menjadi 'application/json', axios akan membungkus objek
     * FormData menjadi string "[object FormData]" dan file tidak pernah
     * sampai ke server. Browser yang akan mengisi Content-Type sendiri,
     * termasuk boundary multipart yang benar.
     */
    if (config.data instanceof FormData) {
        delete config.headers['Content-Type']
    }

    return config
})

/*
 * ─────────────────────────────────────────────────────────────────────────
 *  KANAL ERROR
 * ─────────────────────────────────────────────────────────────────────────
 *
 *  Perbedaan penting yang sering bikin debug makan waktu:
 *
 *    403 -> backend menolak hak akses. Pesannya bisa ditampilkan apa adanya.
 *    422 -> validasi gagal. Isinya array "errors" per-field, untuk
 *            ditempel di bawah input yang relevan.
 *    401 -> token hilang, kedaluwarsa, atau dicabut. Wajib mengeluarkan
 *            pengguna dari aplikasi.
 *    4xx/5xx lain -> tampilkan pesan dari backend apa adanya.
 *
 *  Response yang benar-benar tidak sampai ke axios (DNS gagal, CORS
 *  ditolak, timeout) berubah jadi error dengan pesan generik. Untuk kasus
 *  itu, detail teknisnya disimpan di networkDiagnostics.last supaya bisa
 *  dibaca dari halaman diagnostik, bukan langsung dibuang.
 */

let authHandler = null
let networkHandler = null

/**
 * Dipanggil oleh router/bootstrap supaya lapisan HTTP bisa memberi tahu
 * store auth saat token ditolak, tanpa membuat import siklik antara
 * lib/api.js dan stores/auth.js.
 */
export function onUnauthorized(handler) {
    authHandler = handler
}

export function onNetworkError(handler) {
    networkHandler = handler
}

/** Pesan error terakhir yang tidak bisa sampai ke browser. */
export const networkDiagnostics = {
    last: null,
}

api.interceptors.response.use(
    (response) => response,

    async (error) => {
        const { response, config, message, code } = error

        if (!response) {
            // ── Error tanpa response ──────────────────────────────────────
            networkDiagnostics.last = {
                code: code || 'ERR_NETWORK',
                message: message || 'Network Error',
                url: config?.url || '(tidak diketahui)',
                at: new Date().toISOString(),
            }

            if (networkHandler) networkHandler(networkDiagnostics.last)

            return Promise.reject(error)
        }

        if (response.status === 401) {
            /*
             * Jangan proses 401 dari permintaan yang memang butuh jadi
             * gagal, misalnya /api/auth/me yang dipanggil saat aplikasi
             * baru dibuka dan memang tidak ada token.
             */
            const isAuthProbe = (config?.url || '').includes('/auth/me')

            if (!isAuthProbe && authHandler) {
                authHandler()
            }
        }

        return Promise.reject(error)
    }
)

/**
 * Ubah error axios menjadi bentuk yang enak dibaca komponen.
 *
 * Hampir semua halaman memakai pola yang sama:
 *
 *     try {
 *         await api.post('/armada', form)
 *     } catch (e) {
 *         handleError(e)
 *     }
 *
 * Fungsi ini sudah mengurus pemetaan:
 *   - 422  -> { fieldErrors: {...}, message }
 *   - 403  -> { message }
 *   - 401  -> { message }
 *   - lain  -> { message }
 */
export function readError(error) {
    const response = error?.response
    const status = response?.status ?? 0
    const data = response?.data

    if (status === 422 && data?.errors) {
        const fieldErrors = {}

        for (const [field, messages] of Object.entries(data.errors)) {
            fieldErrors[field] = Array.isArray(messages) ? messages[0] : messages
        }

        return {
            status,
            fieldErrors,
            message: data.message || 'Data yang dikirim tidak valid.',
        }
    }

    if (status === 403) {
        return {
            status,
            fieldErrors: {},
            message: data?.message || 'Anda tidak punya hak akses untuk aksi ini.',
        }
    }

    if (status === 401) {
        return {
            status,
            fieldErrors: {},
            message: data?.message || 'Sesi Anda sudah berakhir. Silakan masuk lagi.',
        }
    }

    if (status === 404) {
        return {
            status,
            fieldErrors: {},
            message: data?.message || 'Data yang diminta tidak ditemukan.',
        }
    }

    if (status === 419) {
        return {
            status,
            fieldErrors: {},
            message: 'Sesi Anda sudah berakhir. Silakan muat ulang halaman.',
        }
    }

    if (!status) {
        return {
            status,
            fieldErrors: {},
            message:
                'Tidak dapat terhubung ke server. Periksa jaringan, lalu pastikan ' +
                'alamat backend di frontend masih benar.',
            network: true,
        }
    }

    if (status >= 500) {
        return {
            status,
            fieldErrors: {},
            message: data?.message || 'Terjadi kesalahan di server. Coba lagi beberapa saat.',
        }
    }

    return {
        status,
        fieldErrors: {},
        message: data?.message || error?.message || 'Terjadi kesalahan yang tidak diketahui.',
    }
}