import { ref, shallowRef, onMounted } from 'vue'

import { api, readError } from '@/lib/api'
import { useToastStore } from '@/stores/toast'

/**
 * ══════════════════════════════════════════════════════════════════════════
 *  useResource
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Pola yang sama muncul di hampir semua halaman: ambil data, tampilkan
 *  spinner, lalu tampilkan isi atau pesan gagal.
 *
 *  Fungsi ini membungkus pola itu supaya setiap halaman cukup menulis:
 *
 *      const bookings = useResource(() => api.get('/bookings'))
 *
 *      onMounted(() => bookings.load())
 *
 *  dan di template memakai `bookings.data`, `bookings.loading`,
 *  `bookings.error`.
 *
 *  ── MENGAPA BUKAN useFetch DARI LIBRARY LUAR ───────────────────────────────
 *
 *  Karena tidak ada satu pun endpoint yang cukup untuk satu request.
 *  Halaman-halaman di aplikasi ini melakukan beberapa request sekaligus,
 *  membutuhkannya pada urutan tertentu, dan sebagian perlu di-refresh
 *  setelah mutasi. Menyembunyikan semua itu di balik satu helper yang serba
 *  otomatis justru menyembunyikan bagian yang paling perlu dipikirkan --
 *  yaitu kapan data harus diambil ulang.
 *
 *  Isi data memakai shallowRef, bukan ref. Daftar pesanan bisa ratusan
 *  baris; kalau dipakai ref, Vue akan membuat setiap objek di dalamnya
 *  reactive tanpa perlu: datanya hanya dibaca utuh lalu dikirim ke
 *  template, bukan dimodifikasi di tempat.
 *
 * @param {() => Promise<import('axios').AxiosResponse>} loader
 *        Fungsi yang mengembalikan promise axios.
 * @param {{ immediate?: boolean, showToast?: boolean }} options
 *        immediate: mulai memuat begitu komponen dibuat (default true).
 *        showToast: tampilkan toast saat gagal (default true).
 */
export function useResource(loader, options = {}) {
    const { immediate = true, showToast = true } = options

    const toast = useToastStore()

    const data = shallowRef(null)
    const loading = ref(false)
    const error = ref('')
    const fieldErrors = ref({})
    const loadedAt = ref(null)

    async function load() {
        loading.value = true
        error.value = ''
        fieldErrors.value = {}

        try {
            const response = await loader()
            data.value = response.data
            loadedAt.value = new Date()
        } catch (e) {
            const detail = readError(e)

            error.value = detail.message
            fieldErrors.value = detail.fieldErrors

            if (showToast) {
                /*
                 * 403 tidak diberi toast.
                 *
                 * 403 biasanya muncul karena pengguna menekan tombol yang
                 * memang tidak diizinkan. Toast di sini hanya menambah
                 * suara; halaman sudah menampilkan pesannya sendiri.
                 */
                if (detail.status !== 403) {
                    toast.error(detail.message)
                }
            }

            return null
        } finally {
            loading.value = false
        }

        return data.value
    }

    /*
     * Muat ulang tanpa menampilkan toast.
     *
     * Dipakai untuk refresh diam-diam setelah mutasi berhasil, atau saat
     * pengguna memutar ulang filter. Kalau pemanggil sudah menampilkan
     * toast sukses, toast kedua untuk aksi yang sama hanya membingungkan.
     */
    function reloadQuietly() {
        const sebelumnya = showToast

        showToast = false
        try {
            return load()
        } finally {
            showToast = sebelumnya
        }
    }

    /**
     * Jalankan mutasi dengan penanganan error yang seragam.
     *
     * Pola yang paling sering dipakai: kirim form, kalau gagal tempel
     * pesan per-field di bawah input, kalau berhasil tampilkan toast dan
     * muat ulang data.
     *
     * @param {() => Promise<import('axios').AxiosResponse>} action
     * @param {{ success?: string, redirect?: string, reload?: boolean }} opts
     * @returns {Promise<boolean>} true kalau berhasil.
     */
    async function submit(action, opts = {}) {
        const { success, redirect, reload = true } = opts

        loading.value = true
        error.value = ''
        fieldErrors.value = {}

        try {
            const response = await action()

            if (success) toast.success(success)

            if (reload) await load()
            else loadedAt.value = new Date()

            /*
             * redirect dibaca dari response.data.redirect_path kalau ada,
             * supaya frontend ikut memakai keputusan backend.
             *
             * Penting: nilai itu berupa string path (mis. "/bookings/12"),
             * bukan nama route. Kalau dipush sebagai string ke router, vue-router
             * memperlakukannya sebagai URL dan tidak akan mencocokkan dengan
             * route mana pun -- hasilnya 404. Karena itu prepend dengan '/'
             * agar router memperlakukannya sebagai path.
             */
            const tujuan = redirect ?? response?.data?.redirect_path

            if (tujuan) {
                window.location.assign(tujuan)
            }

            return true
        } catch (e) {
            const detail = readError(e)

            error.value = detail.message
            fieldErrors.value = detail.fieldErrors

            if (detail.status === 422) {
                // Validasi: pesan per-field sudah ditempel di form, jadi
                // toast tambahan hanya menambah suara tanpa informasi baru.
            } else if (detail.status === 401) {
                // Ditangani interceptor: pengguna akan diarahkan ke login.
            } else {
                toast.error(detail.message)
            }

            return false
        } finally {
            loading.value = false
        }
    }

    function reset() {
        data.value = null
        error.value = ''
        fieldErrors.value = {}
        loadedAt.value = null
    }

    if (immediate) {
        onMounted(load)
    }

    return {
        data,
        loading,
        error,
        fieldErrors,
        loadedAt,
        load,
        reloadQuietly,
        submit,
        reset,
    }
}