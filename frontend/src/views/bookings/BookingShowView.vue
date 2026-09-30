<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import CarPhoto from '@/components/CarPhoto.vue'
import DataState from '@/components/DataState.vue'
import StatusBadge from '@/components/StatusBadge.vue'

import { api, readError } from '@/lib/api'
import { useResource } from '@/composables/useResource'
import { useToastStore } from '@/stores/toast'
import { useConfirmStore } from '@/stores/confirm'
import { useAuthStore } from '@/stores/auth'
import {
    formatAngka,
    formatRupiah,
    formatTanggal,
    formatTanggalPendek,
    formatWaktu,
} from '@/lib/format'

/*
 * ============================================================================
 *  DETAIL PESANAN
 * ============================================================================
 *
 *  Halaman tersibuk di aplikasi. Semua aksi yang bisa mengubah status ada di
 *  sini: pembayaran, biaya tambahan, odometer, kunci/buka, dan invoice.
 *
 *  --- STATUS DAN PEMBAYARAN DI SATU FORM ---------------------------------
 *
 *  Backend hanya punya satu endpoint untuk keduanya: POST /update-status,
 *  yang mewajibkan status_pembayaran DAN status_rental diisi bersamaan.
 *
 *  Tidak ada endpoint pembayaran yang berdiri sendiri. Pembayaran di sistem
 *  ini dicatat sebagai efek samping dari perubahan status, jadi tombol
 *  "Catat Pembayaran" terpisah tidak punya jalan ke server -- hanya akan
 *  berakhir dengan 404.
 *
 *  Dua form terpisah juga berarti dua sumber kebenaran untuk status yang sama.
 *  Karena itu form di sini menampilkan keduanya sekaligus dan tombol simpan
 *  mengirim keadaan lengkap.
 *
 *  --- KUNCI PESANAN ------------------------------------------------------
 *
 *  Field `locked` menentukan tombol mana yang aktif.
 *
 *  Endpoint lock() tidak sekadar mengunci: ia menetapkan status_rental =
 *  'Selesai', membebaskan kendaraan, dan mengisi returned_at. Membuka kunci
 *  mengembalikan status ke 'Dikembalikan' -- bukan ke 'Aktif', karena
 *  kendaraan sudah kembali ke pool.
 *
 *  Karena itu label di sini memakai arti yang jujur: "Tandai Selesai" dan
 *  "Buka Kunci", bukan tombol generik yang tidak menjelaskan akibatnya.
 *
 * ============================================================================
 */

const route = useRoute()
const router = useRouter()
const toast = useToastStore()
const confirm = useConfirmStore()
const auth = useAuthStore()

const bookingId = computed(() => route.params.id)

const detail = useResource(() => api.get(`/bookings/${bookingId.value}`))

const booking = computed(() => detail.data?.booking || null)
const locked = computed(() => Boolean(detail.data?.locked ?? booking.value?.locked))
const invoice = computed(() => detail.data?.invoice || null)
const lastPayment = computed(() => detail.data?.lastPayment || null)
const cars = computed(() => booking.value?.bookingCars || [])
const addons = computed(() => booking.value?.addons || [])

/* Invoice bengkel (BNG) dibuat dari halaman bengkel, bukan dari sini.
 * Yang boleh dibuat dari halaman ini hanya invoice sewa (SK). */
const invoiceSewa = computed(
    () => (invoice.value?.jenis_invoice === 'SK' ? invoice.value : null)
)

/* ============================================================================
 *  STATUS & PEMBAYARAN
 * ============================================================================
 */

/* Nilai ini harus sama persis dengan aturan di BookingController::updateStatus.
 * Kirim nilai di luar daftar itu akan dijawab 422. */
const OPSI_PEMBAYARAN = ['Belum Bayar', 'DP', 'Lunas']
const OPSI_RENTAL = ['Menunggu', 'Aktif', 'Dikembalikan', 'Selesai', 'Batal']

const formStatus = reactive({
    status_pembayaran: 'Belum Bayar',
    status_rental: 'Menunggu',
    dp_amount: 0,
    metode_bayar: 'Tunai',
})

const statusErrors = ref({})
const submitStatus = ref(false)

watch(
    () => booking.value,
    (b) => {
        if (!b) return

        formStatus.status_pembayaran = b.status_pembayaran
        formStatus.status_rental = b.status_rental
        formStatus.dp_amount = Number(b.dp_amount) || 0
        formStatus.metode_bayar = b.metode_bayar || 'Tunai'
    },
    { immediate: true }
)

/* Backend mengembalikan unchanged:true kalau keadaan tidak berubah. Menjalankan
 * request tanpa perubahan hanya membebani database dan menambah baris activity
 * yang tidak berarti, jadi tombol dinonaktifkan lebih dulu. */
const statusBerubah = computed(
    () =>
        formStatus.status_pembayaran !== booking.value?.status_pembayaran ||
        formStatus.status_rental !== booking.value?.status_rental ||
        (formStatus.status_pembayaran === 'DP' &&
            Number(formStatus.dp_amount) !== Number(booking.value?.dp_amount || 0))
)

/* Saat status pembayaran Lunas, backend memaksa dp_amount = total_harga.
 * Jadi field DP harus disembunyikan supaya operator tidak mengira nominal
 * yang diketik dipakai. */
const dpDipakai = computed(() => formStatus.status_pembayaran === 'DP')

const totalHarga = computed(() => Number(booking.value?.total_harga) || 0)
const totalAddons = computed(() => Number(booking.value?.total_addons) || 0)

/* Tidak ada kolom subtotal di tabel bookings. Yang disimpan backend hanya
 * total_addons dan total_harga, jadi selisihnya yang disebut subtotal. */
const subtotal = computed(() => Math.max(0, totalHarga.value - totalAddons.value))
const sudahDibayar = computed(() => Number(booking.value?.dp_amount) || 0)
const sisaTagihan = computed(() => Math.max(0, totalHarga.value - sudahDibayar.value))

async function simpanStatus() {
    submitStatus.value = true
    statusErrors.value = {}

    try {
        const { data } = await api.post(`/bookings/${bookingId.value}/update-status`, {
            status_pembayaran: formStatus.status_pembayaran,
            status_rental: formStatus.status_rental,
            dp_amount: dpDipakai.value ? Number(formStatus.dp_amount) : 0,
            metode_bayar: formStatus.metode_bayar,
        })

        if (data.unchanged) {
            toast.success('Tidak ada perubahan yang disimpan.')
        } else {
            toast.success(data.message || 'Status pesanan diperbarui.')
        }

        await detail.reloadQuietly()
    } catch (e) {
        const parsed = readError(e)
        statusErrors.value = parsed.fieldErrors
        toast.error(parsed.message)
    } finally {
        submitStatus.value = false
    }
}

/* ============================================================================
 *  KUNCI / BUKA
 * ============================================================================ */

async function tandaiSelesai() {
    const ok = await confirm.ask(
        'Tandai pesanan ini selesai dan kunci? Kendaraan dibebaskan, tanggal kembali diisi otomatis, dan data tidak bisa diubah lagi sampai kunci dibuka.',
        { confirmLabel: 'Ya, tandai selesai', tone: 'danger' }
    )

    if (!ok) return

    try {
        const { data } = await api.post(`/bookings/${bookingId.value}/lock`)
        toast.success(data.message || 'Pesanan ditandai selesai.')
        await detail.reloadQuietly()
    } catch (e) {
        toast.error(readError(e).message)
    }
}

async function bukaKunci() {
    const ok = await confirm.ask(
        'Buka kunci pesanan ini untuk revisi? Status rental menjadi "Dikembalikan", bukan "Aktif", karena kendaraan sudah kembali.',
        { confirmLabel: 'Ya, buka kunci' }
    )

    if (!ok) return

    try {
        const { data } = await api.post(`/bookings/${bookingId.value}/unlock`)
        toast.success(data.message || 'Kunci pesanan dibuka.')
        await detail.reloadQuietly()
    } catch (e) {
        toast.error(readError(e).message)
    }
}

/* ============================================================================
 *  BIAYA TAMBAHAN
 * ============================================================================ */

const modalAddon = ref(false)
const addon = reactive({ addon_nama: '', addon_nominal: 0 })
const addonErrors = ref({})
const submitAddon = ref(false)

function bukaModalAddon() {
    addon.addon_nama = ''
    addon.addon_nominal = 0
    addonErrors.value = {}
    modalAddon.value = true
}

async function simpanAddon() {
    submitAddon.value = true
    addonErrors.value = {}

    try {
        const { data } = await api.post(`/bookings/${bookingId.value}/addon`, {
            addon_nama: addon.addon_nama,
            addon_nominal: Number(addon.addon_nominal),
        })

        toast.success(data.message || 'Biaya tambahan ditambahkan.')
        modalAddon.value = false

        await detail.reloadQuietly()
    } catch (e) {
        const parsed = readError(e)
        addonErrors.value = parsed.fieldErrors
        toast.error(parsed.message)
    } finally {
        submitAddon.value = false
    }
}

async function hapusAddon(item) {
    const ok = await confirm.ask(
        `Hapus biaya "${item.nama_biaya}" sebesar ${formatRupiah(item.nominal)}? Total pesanan ikut dikoreksi.`,
        { confirmLabel: 'Ya, hapus', tone: 'danger' }
    )

    if (!ok) return

    try {
        const { data } = await api.delete(
            `/bookings/${bookingId.value}/addon/${item.id}`
        )
        toast.success(data.message || 'Biaya tambahan dihapus.')
        await detail.reloadQuietly()
    } catch (e) {
        toast.error(readError(e).message)
    }
}

/* ============================================================================
 *  ODOMETER PELEPASAN
 * ============================================================================
 */

/*
 * Endpoint ini menulis ke cars.km_saat_ini, bukan ke tabel booking_car, dan
 * menolak nilai yang lebih kecil dari odometer sekarang dengan pesan
 * "tidak boleh mundur".
 *
 * Tidak ada kolom kilometer keluar per pesanan. Riwayat pencatatan odometer
 * disimpan di tabel odometer_logs, satu baris per kali ada update.
 *
 * Field error-nya memakai kunci "km_kembali.{id_kendaraan}", jadi error
 * ditambahkan ke object dengan key berupa angka.
 */
const modalOdo = ref(false)
const odoPerKendaraan = reactive({})
const odoErrors = ref({})
const submitOdo = ref(false)

function bukaModalOdo() {
    odoErrors.value = {}

    cars.value.forEach((bc) => {
        odoPerKendaraan[bc.car_id] = bc.car?.km_saat_ini ?? 0
    })

    modalOdo.value = true
}

async function simpanOdo() {
    submitOdo.value = true
    odoErrors.value = {}

    const kmKembali = {}

    for (const bc of cars.value) {
        kmKembali[bc.car_id] = Number(odoPerKendaraan[bc.car_id]) || 0
    }

    try {
        const { data } = await api.post(`/bookings/${bookingId.value}/update-odometer`, {
            km_kembali: kmKembali,
        })

        toast.success(data.message || 'Odometer pelepasan tersimpan.')
        modalOdo.value = false

        await detail.reloadQuietly()
    } catch (e) {
        const parsed = readError(e)

        /* Kunci "km_kembali.12" dirapikan jadi "12" supaya bisa dipasang
         * ke input yang tepat. */
        for (const [key, value] of Object.entries(parsed.fieldErrors)) {
            const match = key.match(/^km_kembali\.(.+)$/)

            odoErrors.value[match ? match[1] : key] = value
        }

        if (!Object.keys(odoErrors.value).length) {
            toast.error(parsed.message)
        }
    } finally {
        submitOdo.value = false
    }
}

/* ============================================================================
 *  INVOICE
 * ============================================================================
 */

/*
 * Yang dirender di server adalah PDF-nya (dompdf + template Blade), bukan
 * halaman web. Tapi endpoint generate mengembalikan print_path yang mengarah
 * ke route frontend, jadi dua-duanya dipakai: router.push membuka halaman
 * cetak, dan halaman itu punya tombol unduh yang memanggil /download.
 *
 * Tidak ada URL PDF langsung di respons create, jadi halaman ini tidak
 * membuka PDF di tab baru. Yang dilakukan adalah pindah ke route cetakan,
 * dan pengunduhan PDF ditangani di sana.
 */
const buatInvoice = ref(false)

async function generateInvoice() {
    buatInvoice.value = true

    try {
        const { data } = await api.post(`/invoices/generate/${bookingId.value}/SK`)

        toast.success(data.message || `Invoice ${data.nomor_seri} dibuat.`)

        router.push(data.print_path || `/invoices/${data.invoice_id}/print`)
    } catch (e) {
        toast.error(readError(e).message)
    } finally {
        buatInvoice.value = false
    }
}

/* ============================================================================
 *  HAPUS
 * ============================================================================ */

async function hapusPesanan() {
    const ok = await confirm.ask(
        'Hapus pesanan ini secara permanen? Berkas foto, transaksi keuangan, dan data terkait ikut terhapus.',
        { confirmLabel: 'Ya, hapus permanen', tone: 'danger' }
    )

    if (!ok) return

    try {
        const { data } = await api.delete(`/bookings/${bookingId.value}`)
        toast.success(data.message || 'Pesanan dihapus.')
        router.push(data.redirect_path || '/bookings')
    } catch (e) {
        toast.error(readError(e).message)
    }
}
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <RouterLink
            :to="{ name: 'bookings.index' }"
            class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-brand-600 transition-colors mb-1.5"
        >
            <span class="material-symbols-outlined ms-icon text-[16px]">arrow_back</span>
            Kembali ke daftar pesanan
        </RouterLink>

        <DataState
            :loading="detail.loading"
            :error="detail.error"
            :is-empty="false"
            @retry="detail.load()"
        >
            <template v-if="booking">
                <!-- Kepala -->
                <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-4 mb-5">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-2xl font-bold text-slate-900">
                                {{ booking.nomor_pesanan }}
                            </h1>

                            <StatusBadge :label="booking.status_pembayaran" size="md" />
                            <StatusBadge :label="booking.status_rental" size="md" />
                        </div>

                        <p class="text-sm text-slate-500 mt-1.5">
                            {{ booking.nama_pelanggan }} &middot;
                            {{ formatTanggalPendek(booking.tanggal_mulai) }}
                            &ndash;
                            {{ formatTanggalPendek(booking.tanggal_selesai) }}
                            <span class="uppercase text-xs ml-1">({{ booking.tipe_sewa }})</span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <button
                            v-if="auth.can('finance.view') && !invoiceSewa"
                            type="button"
                            class="btn-secondary"
                            :disabled="buatInvoice"
                            @click="generateInvoice"
                        >
                            <span class="material-symbols-outlined ms-icon text-[18px]">
                                receipt_long
                            </span>
                            {{ buatInvoice ? 'Membuat...' : 'Buat Invoice' }}
                        </button>

                        <RouterLink
                            v-else-if="invoiceSewa"
                            :to="`/invoices/${invoiceSewa.id}/print`"
                            class="btn-secondary"
                        >
                            <span class="material-symbols-outlined ms-icon text-[18px]">print</span>
                            Lihat Invoice
                        </RouterLink>

                        <button
                            v-if="auth.can('bookings.edit') && !locked"
                            type="button"
                            class="btn-secondary"
                            @click="bukaModalAddon"
                        >
                            <span class="material-symbols-outlined ms-icon text-[18px]">
                                add_shopping_cart
                            </span>
                            Biaya
                        </button>

                        <button
                            v-if="auth.can('bookings.edit')"
                            type="button"
                            class="btn-secondary"
                            @click="locked ? bukaKunci() : tandaiSelesai()"
                        >
                            <span class="material-symbols-outlined ms-icon text-[18px]">
                                {{ locked ? 'lock_open' : 'lock' }}
                            </span>
                            {{ locked ? 'Buka Kunci' : 'Tandai Selesai' }}
                        </button>

                        <RouterLink
                            v-if="auth.can('bookings.edit') && !locked"
                            :to="`/bookings/${booking.id}/edit`"
                            class="btn-primary"
                        >
                            <span class="material-symbols-outlined ms-icon text-[18px]">edit</span>
                            Ubah
                        </RouterLink>
                    </div>
                </div>

                <!-- Peringatan terkunci -->
                <div
                    v-if="locked"
                    class="pf-card px-4 py-3 mb-5 border-amber-200 bg-amber-50/70 flex items-center gap-3"
                >
                    <span
                        class="material-symbols-outlined ms-icon text-amber-600 text-[20px] shrink-0"
                    >
                        lock
                    </span>
                    <p class="text-sm text-amber-900">
                        Pesanan ini selesai dan terkunci. Data tidak bisa diubah sampai kunci
                        dibuka. Membuka kunci akan mengembalikan status rental menjadi
                        &quot;Dikembalikan&quot;.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <!-- Kolom kiri -->
                    <div class="lg:col-span-2 space-y-5">
                        <!-- Data penyewa -->
                        <section class="pf-card p-5">
                            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                                Data Penyewa
                            </h2>

                            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3.5 text-sm">
                                <div>
                                    <dt class="text-xs text-slate-400">Nama</dt>
                                    <dd class="font-medium text-slate-800 mt-0.5">
                                        {{ booking.nama_pelanggan }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-400">Kontak</dt>
                                    <dd class="font-medium text-slate-800 mt-0.5">
                                        {{ booking.kontak_pelanggan }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-400">Email</dt>
                                    <dd class="font-medium text-slate-800 mt-0.5 break-all">
                                        {{ booking.email || '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-400">Lokasi Jemput</dt>
                                    <dd class="font-medium text-slate-800 mt-0.5">
                                        {{ booking.lokasi_jemput || '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-400">Lokasi Antar</dt>
                                    <dd class="font-medium text-slate-800 mt-0.5">
                                        {{ booking.lokasi_antar || '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-400">Dibuat</dt>
                                    <dd class="font-medium text-slate-800 mt-0.5">
                                        {{ formatTanggal(booking.created_at) }}
                                    </dd>
                                </div>
                            </dl>

                            <div v-if="booking.catatan" class="mt-4 pt-4 border-t border-slate-100">
                                <p class="text-xs text-slate-400">Catatan</p>
                                <p class="text-sm text-slate-700 mt-1 whitespace-pre-line">
                                    {{ booking.catatan }}
                                </p>
                            </div>
                        </section>

                        <!-- Kendaraan -->
                        <section class="pf-card overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                                    Kendaraan ({{ cars.length }})
                                </h2>
                            </div>

                            <ul class="divide-y divide-slate-50">
                                <li
                                    v-for="bc in cars"
                                    :key="bc.id"
                                    class="p-5 flex gap-4"
                                >
                                    <div class="w-24 h-16 rounded-lg overflow-hidden bg-slate-100 shrink-0">
                                        <CarPhoto
                                            :url="bc.car_foto_url"
                                            :alt="`${bc.car?.merk} ${bc.car?.model}`"
                                        />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-slate-800 truncate">
                                                    {{ bc.car?.merk }} {{ bc.car?.model }}
                                                </p>
                                                <p class="text-xs text-slate-500 font-mono">
                                                    {{ bc.car?.plat_nomor }}
                                                </p>
                                            </div>

                                            <p class="text-sm font-semibold text-slate-800 tabular-nums shrink-0">
                                                {{ formatRupiah(bc.harga_dasar_per_hari) }}
                                                <span class="text-[10px] text-slate-400 font-normal">
                                                    /hari
                                                </span>
                                            </p>
                                        </div>

                                        <p
                                            v-if="bc.catatan_harga"
                                            class="text-xs text-slate-500 mt-1.5"
                                        >
                                            {{ bc.catatan_harga }}
                                        </p>

                                        <!-- Foto kondisi -->
                                        <div
                                            v-if="bc.foto_kondisi_urls?.length"
                                            class="flex gap-1.5 mt-2.5 flex-wrap"
                                        >
                                            <a
                                                v-for="(url, i) in bc.foto_kondisi_urls"
                                                :key="i"
                                                :href="url"
                                                target="_blank"
                                                rel="noopener"
                                                class="w-12 h-12 rounded overflow-hidden bg-slate-100 shrink-0 hover:opacity-80 transition-opacity"
                                            >
                                                <img
                                                    :src="url"
                                                    alt="Foto kondisi kendaraan"
                                                    class="w-full h-full object-cover"
                                                />
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </section>

                        <!-- Biaya tambahan -->
                        <section class="pf-card overflow-hidden">
                            <div
                                class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3"
                            >
                                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                                    Biaya Tambahan
                                </h2>

                                <button
                                    v-if="auth.can('bookings.edit') && !locked"
                                    type="button"
                                    class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition-colors"
                                    @click="bukaModalAddon"
                                >
                                    + Tambah
                                </button>
                            </div>

                            <p
                                v-if="!addons.length"
                                class="text-sm text-slate-400 py-8 text-center"
                            >
                                Tidak ada biaya tambahan.
                            </p>

                            <ul v-else class="divide-y divide-slate-50">
                                <li
                                    v-for="item in addons"
                                    :key="item.id"
                                    class="px-5 py-3 flex items-center justify-between gap-3"
                                >
                                    <span class="text-sm text-slate-700">
                                        {{ item.nama_biaya }}
                                    </span>

                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-semibold text-slate-800 tabular-nums">
                                            {{ formatRupiah(item.nominal) }}
                                        </span>

                                        <button
                                            v-if="auth.can('bookings.edit') && !locked"
                                            type="button"
                                            class="p-1 rounded text-slate-300 hover:text-rose-600 transition-colors"
                                            :aria-label="`Hapus biaya ${item.nama_biaya}`"
                                            @click="hapusAddon(item)"
                                        >
                                            <span class="material-symbols-outlined ms-icon text-[16px]">
                                                delete
                                            </span>
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </section>

                        <!-- Dokumen penyewa -->
                        <section
                            v-if="booking.foto_ktp_urls?.length || booking.foto_penyewa_urls?.length"
                            class="pf-card p-5"
                        >
                            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                                Dokumen Penyewa
                            </h2>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div v-if="booking.foto_ktp_urls?.length">
                                    <p class="text-xs text-slate-400 mb-2">Foto KTP</p>
                                    <div class="flex gap-2 flex-wrap">
                                        <a
                                            v-for="(url, i) in booking.foto_ktp_urls"
                                            :key="i"
                                            :href="url"
                                            target="_blank"
                                            rel="noopener"
                                            class="w-16 h-16 rounded-lg overflow-hidden bg-slate-100 hover:opacity-80 transition-opacity"
                                        >
                                            <img
                                                :src="url"
                                                alt="Foto KTP penyewa"
                                                class="w-full h-full object-cover"
                                            />
                                        </a>
                                    </div>
                                </div>

                                <div v-if="booking.foto_penyewa_urls?.length">
                                    <p class="text-xs text-slate-400 mb-2">Foto Penyewa</p>
                                    <div class="flex gap-2 flex-wrap">
                                        <a
                                            v-for="(url, i) in booking.foto_penyewa_urls"
                                            :key="i"
                                            :href="url"
                                            target="_blank"
                                            rel="noopener"
                                            class="w-16 h-16 rounded-lg overflow-hidden bg-slate-100 hover:opacity-80 transition-opacity"
                                        >
                                            <img
                                                :src="url"
                                                alt="Foto penyewa"
                                                class="w-full h-full object-cover"
                                            />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Kolom kanan -->
                    <div class="space-y-5">
                        <!-- Ringkasan biaya -->
                        <section class="pf-card p-5">
                            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                                Ringkasan Biaya
                            </h2>

                            <dl class="space-y-2.5 text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Subtotal sewa</dt>
                                    <dd class="font-medium text-slate-700 tabular-nums">
                                        {{ formatRupiah(subtotal) }}
                                    </dd>
                                </div>

                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Biaya tambahan</dt>
                                    <dd class="font-medium text-slate-700 tabular-nums">
                                        {{ formatRupiah(totalAddons) }}
                                    </dd>
                                </div>

                                <div
                                    class="pt-3 border-t border-slate-100 flex justify-between gap-3 items-baseline"
                                >
                                    <dt class="text-sm font-semibold text-slate-700">Total</dt>
                                    <dd class="text-lg font-bold text-brand-600 tabular-nums">
                                        {{ formatRupiah(totalHarga) }}
                                    </dd>
                                </div>

                                <div v-if="sudahDibayar > 0" class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Sudah dibayar</dt>
                                    <dd class="font-semibold text-emerald-600 tabular-nums">
                                        {{ formatRupiah(sudahDibayar) }}
                                    </dd>
                                </div>

                                <div v-if="sisaTagihan > 0" class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Sisa tagihan</dt>
                                    <dd class="font-semibold text-rose-600 tabular-nums">
                                        {{ formatRupiah(sisaTagihan) }}
                                    </dd>
                                </div>
                            </dl>

                            <div v-if="lastPayment" class="mt-4 pt-4 border-t border-slate-100">
                                <p class="text-xs text-slate-400 mb-1">Pembayaran terakhir</p>
                                <p class="text-sm text-slate-700 font-medium tabular-nums">
                                    {{ formatRupiah(lastPayment.jumlah) }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ lastPayment.metode_bayar || '-' }} &middot;
                                    {{ formatTanggal(lastPayment.created_at) }}
                                    {{ formatWaktu(lastPayment.created_at) }}
                                </p>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ lastPayment.status_sebelum }} &rarr;
                                    {{ lastPayment.status_sesudah }}
                                </p>
                            </div>
                        </section>

                        <!-- Status & pembayaran -->
                        <section
                            v-if="auth.can('bookings.edit') || auth.can('finance.edit')"
                            class="pf-card p-5"
                        >
                            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-1">
                                Status &amp; Pembayaran
                            </h2>
                            <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                                Keduanya disimpan bersama. Backend mewajibkan keduanya diisi
                                sekali jalan, jadi tidak ada cara menyimpan satu tanpa yang
                                lain.
                            </p>

                            <div class="space-y-3">
                                <div>
                                    <label class="form-label" for="status_pembayaran">
                                        Status Pembayaran
                                    </label>
                                    <select
                                        id="status_pembayaran"
                                        v-model="formStatus.status_pembayaran"
                                        class="form-input"
                                        :class="{ 'form-input-error': statusErrors.status_pembayaran }"
                                        :disabled="locked"
                                    >
                                        <option v-for="s in OPSI_PEMBAYARAN" :key="s" :value="s">
                                            {{ s }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="statusErrors.status_pembayaran"
                                        class="text-xs text-rose-600 mt-1"
                                    >
                                        {{ statusErrors.status_pembayaran }}
                                    </p>
                                </div>

                                <div v-if="dpDipakai">
                                    <label class="form-label" for="dp_amount">
                                        Nominal DP (Rp)
                                    </label>
                                    <input
                                        id="dp_amount"
                                        v-model.number="formStatus.dp_amount"
                                        type="number"
                                        min="0"
                                        step="1000"
                                        class="form-input text-right tabular-nums"
                                        :class="{ 'form-input-error': statusErrors.dp_amount }"
                                        :disabled="locked"
                                    />
                                    <p
                                        v-if="statusErrors.dp_amount"
                                        class="text-xs text-rose-600 mt-1"
                                    >
                                        {{ statusErrors.dp_amount }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-1">
                                        Sisa tagihan setelah DP ini:
                                        {{
                                            formatRupiah(
                                                Math.max(0, totalHarga - Number(formStatus.dp_amount || 0))
                                            )
                                        }}
                                    </p>
                                </div>

                                <div v-else-if="formStatus.status_pembayaran === 'Lunas'">
                                    <div
                                        class="rounded-lg bg-emerald-50 border border-emerald-100 px-3 py-2.5"
                                    >
                                        <p class="text-xs text-emerald-800">
                                            Backend akan memakai total pesanan
                                            ({{ formatRupiah(totalHarga) }}) sebagai nilai
                                            yang sudah dibayar.
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label" for="metode_bayar">
                                        Metode Pembayaran
                                    </label>
                                    <select
                                        id="metode_bayar"
                                        v-model="formStatus.metode_bayar"
                                        class="form-input"
                                        :disabled="locked"
                                    >
                                        <option>Tunai</option>
                                        <option>Transfer</option>
                                        <option>Kartu Debit</option>
                                        <option>Kartu Kredit</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label" for="status_rental">
                                        Status Rental
                                    </label>
                                    <select
                                        id="status_rental"
                                        v-model="formStatus.status_rental"
                                        class="form-input"
                                        :class="{ 'form-input-error': statusErrors.status_rental }"
                                        :disabled="locked"
                                    >
                                        <option v-for="s in OPSI_RENTAL" :key="s" :value="s">
                                            {{ s }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="statusErrors.status_rental"
                                        class="text-xs text-rose-600 mt-1"
                                    >
                                        {{ statusErrors.status_rental }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-1">
                                        Mengubah ke Aktif, Dikembalikan, Selesai, atau Batal
                                        ikut mengubah status kendaraan yang dipakai.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="btn-primary w-full"
                                    :disabled="locked || !statusBerubah || submitStatus"
                                    @click="simpanStatus"
                                >
                                    <span
                                        v-if="submitStatus"
                                        class="material-symbols-outlined ms-icon text-[18px] animate-spin"
                                    >
                                        progress_activity
                                    </span>
                                    Simpan Status
                                </button>
                            </div>
                        </section>

                        <!-- Pelepasan -->
                        <section v-if="auth.can('bookings.edit')" class="pf-card p-5">
                            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-1">
                                Pelepasan Kendaraan
                            </h2>
                            <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                                Angka di sini menulis ke odometer kendaraan. Nilai yang lebih
                                kecil dari odometer sekarang akan ditolak.
                            </p>

                            <button
                                type="button"
                                class="btn-secondary w-full"
                                :disabled="locked"
                                @click="bukaModalOdo"
                            >
                                <span class="material-symbols-outlined ms-icon text-[18px]">
                                    speed
                                </span>
                                Input Odometer
                            </button>
                        </section>

                        <!-- Aksi berbahaya -->
                        <section
                            v-if="auth.can('bookings.delete')"
                            class="pf-card p-5 border-rose-100"
                        >
                            <button type="button" class="btn-danger w-full" @click="hapusPesanan">
                                <span class="material-symbols-outlined ms-icon text-[18px]">
                                    delete
                                </span>
                                Hapus Pesanan
                            </button>
                            <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                                Penghapusan tidak dapat dibatalkan.
                            </p>
                        </section>
                    </div>
                </div>
            </template>
        </DataState>

        <!-- Modal biaya tambahan -->
        <Teleport to="body">
            <div
                v-if="modalAddon"
                class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
                @click.self="modalAddon = false"
            >
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">
                    <h3 class="text-base font-bold text-slate-800">Tambah Biaya</h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                        Nominal ini ditambahkan ke total pesanan.
                    </p>

                    <div class="space-y-4 mt-4">
                        <div>
                            <label class="form-label" for="addon_nama">Nama</label>
                            <input
                                id="addon_nama"
                                v-model="addon.addon_nama"
                                type="text"
                                class="form-input"
                                :class="{ 'form-input-error': addonErrors.addon_nama }"
                                placeholder="BBM, Parkir Tol, ..."
                            />
                            <p
                                v-if="addonErrors.addon_nama"
                                class="text-xs text-rose-600 mt-1"
                            >
                                {{ addonErrors.addon_nama }}
                            </p>
                        </div>

                        <div>
                            <label class="form-label" for="addon_nominal">Nominal (Rp)</label>
                            <input
                                id="addon_nominal"
                                v-model.number="addon.addon_nominal"
                                type="number"
                                min="0"
                                step="1000"
                                class="form-input text-right tabular-nums"
                                :class="{ 'form-input-error': addonErrors.addon_nominal }"
                            />
                            <p
                                v-if="addonErrors.addon_nominal"
                                class="text-xs text-rose-600 mt-1"
                            >
                                {{ addonErrors.addon_nominal }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-5">
                        <button type="button" class="btn-secondary" @click="modalAddon = false">
                            Batal
                        </button>
                        <button
                            type="button"
                            class="btn-primary"
                            :disabled="submitAddon"
                            @click="simpanAddon"
                        >
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal odometer -->
        <Teleport to="body">
            <div
                v-if="modalOdo"
                class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
                @click.self="modalOdo = false"
            >
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6">
                    <h3 class="text-base font-bold text-slate-800">Odometer Pelepasan</h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                        Angka tidak boleh lebih kecil dari odometer saat kendaraan
                        diterima. Kosongkan bila belum dikembalikan.
                    </p>

                    <div class="space-y-3 mt-4">
                        <div v-for="bc in cars" :key="bc.id" class="flex items-center gap-3">
                            <label
                                :for="`odo-${bc.car_id}`"
                                class="text-sm text-slate-700 flex-1 min-w-0 truncate"
                            >
                                {{ bc.car?.merk }} {{ bc.car?.model }}
                                <span class="text-xs text-slate-400 font-mono ml-1">
                                    {{ bc.car?.plat_nomor }}
                                </span>
                                <span class="block text-[11px] text-slate-400">
                                    Sekarang {{ formatAngka(bc.car?.km_saat_ini) }} km
                                </span>
                            </label>

                            <div>
                                <input
                                    :id="`odo-${bc.car_id}`"
                                    v-model="odoPerKendaraan[bc.car_id]"
                                    type="number"
                                    min="0"
                                    class="form-input w-36 text-right tabular-nums"
                                    :class="{
                                        'form-input-error': odoErrors[bc.car_id],
                                    }"
                                />
                                <p
                                    v-if="odoErrors[bc.car_id]"
                                    class="text-xs text-rose-600 mt-1"
                                >
                                    {{ odoErrors[bc.car_id] }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-5">
                        <button type="button" class="btn-secondary" @click="modalOdo = false">
                            Batal
                        </button>
                        <button
                            type="button"
                            class="btn-primary"
                            :disabled="submitOdo"
                            @click="simpanOdo"
                        >
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>