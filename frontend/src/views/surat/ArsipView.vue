<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DataState from '@/components/DataState.vue'
import EmptyState from '@/components/EmptyState.vue'
import Pagination from '@/components/Pagination.vue'

import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

import { api, readError } from '@/lib/api'
import { formatTanggal, formatWaktu } from '@/lib/format'

/*
 * ============================================================================
 *  ARSIP SURAT
 * ============================================================================
 *
 *  Daftar invoice yang sudah diubah menjadi surat, difilter per jenis
 *  dokumen.
 *
 *  --- BUKAN DAFTAR SURAT, TAPI DAFTAR INVOICE ------------------------------
 *
 *  Arsip diquery dari tabel invoices yang punya relasi surat, bukan dari tabel
 *  surat. Satu baris di sini berarti satu invoice: nomor serinya, kode
 *  mobilnya, dan tanggal terbitnya. Nomor suratnya diambil dari relasi.
 *
 *  --- TABEL TIDAK PUNYA NOMINAL ATAU STATUS BAYAR --------------------------
 *
 *  Kolom total dan status pembayaran sengaja tidak ada di sini. Tabel
 *  invoices tidak punya kolom seperti itu -- nominalnya hidup di tabel
 *  bookings, dan pembayarannya di booking_payments. Menampilkannya berarti
 *  menjumlahkan ulang di frontend, dan angkanya bisa berbeda dari invoice
 *  yang sebenarnya ditagih. Halaman Keuangan/index yang menjadi tempat
 *  nominalnya.
 *
 *  --- SEMUA FILTER DI BAWAH KENDALI SERVER --------------------------------
 *
 *  Endpoint arsip() menerima jenis_dokumen, subkategori, bulan, search, dan
 *  page. Karena itu tidak ada filter yang disaring lebih dulu di frontend.
 *  Kalau ada, angka total dan isi tabel akan berbeda: total dihitung dari
 *  seluruh baris yang cocok, sedangkan tabel cuma menampilkan satu halaman.
 *
 *  --- RESPON INI BEDA BENTUK DARI ENDPOINT INVOICE LAINNYA ----------------
 *
 *  InvoiceController::print() mengembalikan signaturePayload() yang sudah
 *  dinormalisasi: { lengkap, missing, blok: [...] }. arsip() mengembalikan
 *  model Eloquent apa adanya, jadi relasi `signatures` di sini berupa larik
 *  mentah dengan kunci signature_url, bukan blok yang sudah dirapikan.
 *
 *  signature_url dibangun dari asset() di server, jadi nilainya menunjuk ke
 *  APP_URL -- alamat backend, bukan server frontend. Untuk <img> itu tidak
 *  masalah, karena peramban memuat gambar lintas asal tanpa asking CORS.
 *  Dicatat di docs/KNOWN_ISSUES.md.
 */

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const PER_PAGE = 15

/* Semua nilai filter disimpan di query string. Arsip sering dipakai sebagai
 * referensi: operator membagikan tautannya ke rekan kerja, dan tautan itu
 * harus membuka tampilan yang sama persis. */
const filter = ref({
    jenis_dokumen: route.query.jenis_dokumen ? String(route.query.jenis_dokumen) : '',
    subkategori: route.query.subkategori ? String(route.query.subkategori) : '',
    bulan: route.query.bulan ? String(route.query.bulan) : '',
    search: route.query.search ? String(route.query.search) : '',
    page: Number(route.query.page) || 1,
})

const memuat = ref(true)
const error = ref('')
const invoices = ref(null)
const jenisDokumenList = ref([])
const subkategorisByJenis = ref({})

/* -------------------------------------------------------------------------
 *  NILAI TURUNAN
 * ---------------------------------------------------------------------- */

const baris = computed(() => invoices.value?.data || [])
const total = computed(() => invoices.value?.total || 0)

const adaFilter = computed(() =>
    Boolean(
        filter.value.jenis_dokumen ||
            filter.value.subkategori ||
            filter.value.bulan ||
            filter.value.search
    )
)

/* Subkategori yang tampil mengikuti jenis dokumen yang sedang dipilih. Kalau
 * jenis belum dipilih, semua subkategori digabung supaya dropdown-nya tidak
 * kosong. */
const opsiSubkategori = computed(() => {
    if (!filter.value.jenis_dokumen) {
        const semua = new Set()

        Object.values(subkategorisByJenis.value).forEach((list) => {
            ;(list || []).forEach((s) => semua.add(s))
        })

        return [...semua].sort()
    }

    return subkategorisByJenis.value[filter.value.jenis_dokumen] || []
})

/* -------------------------------------------------------------------------
 *  BANTUAN BARIS
 * ---------------------------------------------------------------------- */

/*
 * Plat mobil. Urutan fallback-nya mengikuti Blade: kode mobil dulu
 * (yang selalu ada di database tapi belum tentu terisi -- lihat
 * docs/KNOWN_ISSUES.md soal cars.kode_mobil), lalu mobil servis, lalu mobil
 * dari booking.
 */
function platMobil(invoice) {
    if (invoice.kode_mobil && invoice.kode_mobil !== 'XXX') return invoice.kode_mobil

    if (invoice.service_log?.car?.plat_nomor) return invoice.service_log.car.plat_nomor

    const dariBooking = invoice.booking?.booking_cars?.find((bc) => bc.car)?.car

    return dariBooking?.plat_nomor || '-'
}

function namaMobil(invoice) {
    if (invoice.service_log?.car?.merk) return invoice.service_log.car.merk

    const dariBooking = invoice.booking?.booking_cars?.find((bc) => bc.car)?.car

    return dariBooking?.merk || '-'
}

/* Label blok tanda tangan menyesuaikan jenis invoice, sama seperti
 * Invoice::signatureTitles(). Invoice bengkel menulis "Penerima", bukan
 * "Pelanggan". */
function judulBlok(invoice, role) {
    if (role !== 'pelanggan') return 'Hormat Kami,'

    return invoice.jenis_invoice === 'BNG' ? 'Penerima' : 'Pelanggan'
}

/* Tanda tangan dikumpulkan per role, bukan per indeks, supaya urutan tampil
 * selalu pelanggan lalu perusahaan walaupun urutan baris di database berubah. */
function blokTtd(invoice) {
    const olehRole = new Map((invoice.signatures || []).map((s) => [s.role, s]))

    return [
        { role: 'pelanggan', signature: olehRole.get('pelanggan') || null },
        { role: 'finance', signature: olehRole.get('finance') || null },
    ].map((b) => ({
        ...b,
        judul: judulBlok(invoice, b.role),
        ada: Boolean(b.signature && b.signature.signature_path),
    }))
}

/* Kedua blok harus terisi sebelum PDF boleh diunduh. Dihitung ulang di
 * frontend supaya tombol di baris bisa langsung disabled tanpa request. */
function sudahLengkap(invoice) {
    return blokTtd(invoice).every((b) => b.ada)
}

function namaLampiran(invoice) {
    return invoice.surat?.nomor_surat || invoice.nomor_seri || 'invoice'
}

/* -------------------------------------------------------------------------
 *  MUAT
 * ---------------------------------------------------------------------- */

async function muat() {
    memuat.value = true
    error.value = ''

    const params = {}

    if (filter.value.jenis_dokumen) params.jenis_dokumen = filter.value.jenis_dokumen
    if (filter.value.subkategori) params.subkategori = filter.value.subkategori
    if (filter.value.bulan) params.bulan = filter.value.bulan
    if (filter.value.search) params.search = filter.value.search
    if (filter.value.page > 1) params.page = filter.value.page

    try {
        const { data } = await api.get('/surat-menyurat/arsip', { params })

        invoices.value = data.invoices
        jenisDokumenList.value = data.jenisDokumenList || []
        subkategorisByJenis.value = data.subkategorisByJenis || {}
    } catch (e) {
        error.value = readError(e).message
    } finally {
        memuat.value = false
    }
}

/* Filter ditulis ke URL, lalu URL itu yang memicu muat ulang. Efeknya satu
 * sumber kebenaran: tidak perlu menyalin nilai filter ke state kedua yang
 * harus dijaga sinkron. */
watch(
    filter,
    (nilai) => {
        const query = {}

        if (nilai.jenis_dokumen) query.jenis_dokumen = nilai.jenis_dokumen
        if (nilai.subkategori) query.subkategori = nilai.subkategori
        if (nilai.bulan) query.bulan = nilai.bulan
        if (nilai.search) query.search = nilai.search
        if (nilai.page > 1) query.page = nilai.page

        router.replace({ query })
        muat()
    },
    { deep: true }
)

/*
 * Mengubah filter apa pun mengembalikan halaman ke 1. Nomor halaman lama bisa
 * sudah melewati jumlah halaman hasil filter baru, dan paginate() akan
 * mengembalikan halaman kosong.
 */
function ubahJenis(nilai) {
    filter.value.jenis_dokumen = nilai
    filter.value.subkategori = ''
    filter.value.page = 1
}

function ubahSubkategori(nilai) {
    filter.value.subkategori = nilai
    filter.value.page = 1
}

function ubahBulan(nilai) {
    filter.value.bulan = nilai
    filter.value.page = 1
}

/* Pencarian dikirim saat tombol ditekan atau Enter ditekan, bukan setiap
 * ketikan. Tanpa penundaan, tiap huruf akan memicu satu request. */
const cariDraft = ref(filter.value.search)

function jalankanCari() {
    filter.value.search = cariDraft.value.trim()
    filter.value.page = 1
}

function resetFilter() {
    filter.value = {
        jenis_dokumen: '',
        subkategori: '',
        bulan: '',
        search: '',
        page: 1,
    }

    cariDraft.value = ''
}

/* -------------------------------------------------------------------------
 *  UNDUH PDF
 * ---------------------------------------------------------------------- */

/*
 * Endpoint download() mengembalikan { url, filename } dengan url berupa signed
 * URL berumur pendek. Membuka lewat <a download> dipakai supaya nama berkas
 * ikut hilang dari daftar unduhan peramban.
 */
const mengunduh = ref(0)

async function unduh(invoice) {
    if (mengunduh.value) return

    mengunduh.value = invoice.id

    try {
        const { data } = await api.get(`/invoices/${invoice.id}/download`)

        const a = document.createElement('a')

        a.href = data.url
        a.download = data.filename || `${namaLampiran(invoice)}.pdf`
        a.rel = 'noopener'
        document.body.appendChild(a)
        a.click()
        a.remove()
    } catch (e) {
        toast.error(readError(e).message)
    } finally {
        mengunduh.value = 0
    }
}

const bisaBuatTtd = computed(() => auth.can('finance.create'))

muat()
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Kepala -->
        <div
            class="flex flex-col md:flex-row md:justify-between md:items-end gap-3 mb-5"
        >
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-slate-900">Arsip Surat</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Invoice yang sudah dikonversi menjadi surat.
                </p>
            </div>

            <RouterLink to="/surat-menyurat" class="btn-secondary text-xs shrink-0">
                <span class="material-symbols-outlined ms-icon text-[16px]">arrow_back</span>
                Kembali ke Daftar Surat
            </RouterLink>
        </div>

        <!-- Filter -->
        <div class="pf-card px-4 py-3 mb-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="fJenis" class="form-label">Jenis Dokumen</label>
                    <select
                        id="fJenis"
                        :value="filter.jenis_dokumen"
                        class="form-input py-2"
                        @change="ubahJenis($event.target.value)"
                    >
                        <option value="">Semua Jenis</option>
                        <option v-for="j in jenisDokumenList" :key="j" :value="j">{{ j }}</option>
                    </select>
                </div>

                <div>
                    <label for="fSub" class="form-label">Subkategori</label>
                    <select
                        id="fSub"
                        :value="filter.subkategori"
                        class="form-input py-2"
                        @change="ubahSubkategori($event.target.value)"
                    >
                        <option value="">Semua Subkategori</option>
                        <option v-for="s in opsiSubkategori" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>

                <div>
                    <label for="fBulan" class="form-label">Bulan</label>
                    <input
                        id="fBulan"
                        :value="filter.bulan"
                        type="month"
                        class="form-input py-2"
                        @change="ubahBulan($event.target.value)"
                    />
                </div>

                <div>
                    <label for="fCari" class="form-label">Cari</label>
                    <div class="flex gap-1.5">
                        <input
                            id="fCari"
                            v-model="cariDraft"
                            type="search"
                            class="form-input py-2"
                            placeholder="Nomor seri, nomor surat, pesanan..."
                            @keyup.enter="jalankanCari"
                        />

                        <button
                            type="button"
                            class="btn-primary text-xs px-3 shrink-0"
                            aria-label="Cari"
                            @click="jalankanCari"
                        >
                            <span class="material-symbols-outlined ms-icon text-[16px]">search</span>
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="adaFilter"
                class="flex items-center justify-between gap-3 mt-3 pt-3 border-t border-slate-100"
            >
                <span class="text-xs text-slate-500">
                    <template v-if="filter.jenis_dokumen">
                        Jenis <strong class="text-slate-700">{{ filter.jenis_dokumen }}</strong>
                        <template v-if="filter.subkategori">
                            &middot;
                            <strong class="text-slate-700">{{ filter.subkategori }}</strong>
                        </template>
                        &middot;
                    </template>
                    <template v-if="filter.bulan">Bulan {{ filter.bulan }}</template>
                    <template v-if="filter.search">cari &quot;{{ filter.search }}&quot;</template>
                </span>

                <button
                    type="button"
                    class="text-xs text-slate-500 hover:text-rose-600 transition-colors"
                    @click="resetFilter"
                >
                    Reset filter
                </button>
            </div>
        </div>

        <DataState
            :loading="memuat"
            :error="error"
            :is-empty="!memuat && !error && total === 0"
            empty-icon="inventory_2"
            empty-title="Arsip kosong"
            :empty-description="
                adaFilter
                    ? 'Tidak ada invoice yang cocok dengan filter ini.'
                    : 'Dokumen akan muncul di sini setelah invoice diterbitkan.'
            "
            @retry="muat"
        >
            <!-- Tabel -->
            <section v-if="total" class="pf-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[1080px]">
                        <thead class="bg-slate-50">
                            <tr class="text-left">
                                <th
                                    class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                >
                                    Nomor Seri
                                </th>
                                <th
                                    class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                >
                                    Nomor Surat
                                </th>
                                <th
                                    class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                >
                                    Jenis Dokumen
                                </th>
                                <th
                                    class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                >
                                    Kendaraan
                                </th>
                                <th
                                    class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                >
                                    Pesanan
                                </th>
                                <th
                                    class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                >
                                    Tanggal
                                </th>
                                <th
                                    class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                >
                                    Tanda Tangan
                                </th>
                                <th
                                    class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="inv in baris"
                                :key="inv.id"
                                class="hover:bg-slate-50 transition-colors"
                            >
                                <!-- Nomor seri -->
                                <td class="px-5 py-4">
                                    <span class="font-mono text-xs font-semibold text-slate-800">
                                        {{ inv.nomor_seri }}
                                    </span>
                                </td>

                                <!-- Nomor surat -->
                                <td class="px-5 py-4">
                                    <span
                                        v-if="inv.surat?.nomor_surat"
                                        class="font-mono text-xs text-slate-600"
                                    >
                                        {{ inv.surat.nomor_surat }}
                                    </span>
                                    <span v-else class="text-xs text-slate-400 italic">-</span>
                                </td>

                                <!-- Jenis dokumen + subkategori -->
                                <td class="px-5 py-4">
                                    <span
                                        v-if="inv.surat"
                                        class="text-xs font-medium text-slate-700"
                                    >
                                        {{ inv.surat.jenis_dokumen }}
                                    </span>
                                    <span v-else class="text-xs text-slate-400 italic">-</span>

                                    <span
                                        v-if="inv.surat?.subkategori"
                                        class="inline-flex items-center px-2 py-0.5 mt-1 rounded-full text-[10px] font-medium bg-blue-50 text-blue-700"
                                    >
                                        {{ inv.surat.subkategori }}
                                    </span>
                                </td>

                                <!-- Kendaraan -->
                                <td class="px-5 py-4">
                                    <span class="font-mono text-xs font-semibold text-slate-700">
                                        {{ platMobil(inv) }}
                                    </span>

                                    <p class="text-[10px] text-slate-400 mt-0.5">
                                        {{ namaMobil(inv) }}
                                    </p>
                                </td>

                                <!-- Pesanan asal -->
                                <td class="px-5 py-4">
                                    <RouterLink
                                        v-if="inv.booking"
                                        :to="`/bookings/${inv.booking.id}`"
                                        class="text-xs font-medium text-brand-600 hover:text-brand-700 transition-colors"
                                    >
                                        #{{ inv.booking.nomor_pesanan }}
                                    </RouterLink>

                                    <span
                                        v-else-if="inv.service_log"
                                        class="text-xs text-slate-500"
                                    >
                                        Servis #{{ inv.service_log.id }}
                                    </span>

                                    <span v-else class="text-xs text-slate-400 italic">-</span>
                                </td>

                                <!-- Tanggal terbit -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="text-xs text-slate-600">
                                        {{ inv.issued_at ? formatTanggal(inv.issued_at) : '-' }}
                                    </span>
                                </td>

                                <!-- Tanda tangan -->
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-1.5 min-w-[190px]">
                                        <div
                                            v-for="b in blokTtd(inv)"
                                            :key="b.role"
                                            class="flex items-center gap-2"
                                        >
                                            <!-- Sudah ada tanda tangan -->
                                            <template v-if="b.ada">
                                                <img
                                                    :src="b.signature.signature_url"
                                                    :alt="`TTD ${b.signature.signer_name}`"
                                                    class="h-7 w-16 object-contain object-left bg-white border border-slate-200 rounded px-1 shrink-0"
                                                />

                                                <div class="min-w-0">
                                                    <p class="text-[11px] font-semibold text-slate-700 truncate">
                                                        {{ b.judul }}
                                                    </p>
                                                    <p class="text-[10px] text-slate-400 truncate">
                                                        {{ b.signature.signer_name }}
                                                        <template v-if="b.signature.signed_at">
                                                            &middot;
                                                            {{
                                                                formatWaktu(
                                                                    b.signature.signed_at
                                                                )
                                                            }}
                                                        </template>
                                                    </p>
                                                </div>
                                            </template>

                                            <!-- Belum ada -->
                                            <template v-else>
                                                <span
                                                    class="h-7 w-16 inline-flex items-center justify-center border border-dashed border-slate-300 rounded text-slate-300 shrink-0"
                                                >
                                                    <span class="material-symbols-outlined ms-icon text-[14px]">
                                                        draw
                                                    </span>
                                                </span>

                                                <p class="text-[11px] text-slate-400">
                                                    Belum TTD {{ b.judul }}
                                                </p>
                                            </template>
                                        </div>

                                        <span
                                            v-if="sudahLengkap(inv)"
                                            class="inline-flex items-center gap-1 self-start px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700"
                                        >
                                            <span class="material-symbols-outlined ms-icon text-[12px]">
                                                verified
                                            </span>
                                            Lengkap &middot; siap diunduh
                                        </span>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            v-if="sudahLengkap(inv)"
                                            type="button"
                                            class="p-2 rounded-lg text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 transition-colors disabled:opacity-50"
                                            :disabled="mengunduh === inv.id"
                                            title="Unduh PDF bertanda tangan"
                                            @click="unduh(inv)"
                                        >
                                            <span
                                                class="material-symbols-outlined ms-icon text-[18px]"
                                                :class="mengunduh === inv.id ? 'animate-spin' : ''"
                                            >
                                                {{
                                                    mengunduh === inv.id
                                                        ? 'progress_activity'
                                                        : 'download'
                                                }}
                                            </span>
                                        </button>

                                        <RouterLink
                                            v-else
                                            :to="`/invoices/${inv.id}/print`"
                                            class="p-2 rounded-lg text-slate-300 cursor-not-allowed"
                                            title="Tanda tangan kedua blok dulu, lalu buka halaman cetak"
                                            aria-disabled="true"
                                        >
                                            <span class="material-symbols-outlined ms-icon text-[18px]">
                                                lock
                                            </span>
                                        </RouterLink>

                                        <RouterLink
                                            :to="
                                                inv.jenis_invoice === 'BNG'
                                                    ? `/invoices/${inv.id}/print-bengkel`
                                                    : `/invoices/${inv.id}/print`
                                            "
                                            class="p-2 rounded-lg text-slate-500 hover:text-brand-600 hover:bg-slate-50 transition-colors"
                                            title="Cetak Invoice"
                                        >
                                            <span class="material-symbols-outlined ms-icon text-[18px]">
                                                print
                                            </span>
                                        </RouterLink>

                                        <RouterLink
                                            v-if="!sudahLengkap(inv) && bisaBuatTtd"
                                            :to="`/invoices/${inv.id}/print`"
                                            class="p-2 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-slate-50 transition-colors"
                                            title="Buka halaman cetak untuk menandatangani"
                                        >
                                            <span class="material-symbols-outlined ms-icon text-[18px]">
                                                edit
                                            </span>
                                        </RouterLink>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginasi -->
                <div
                    v-if="invoices?.last_page > 1"
                    class="px-5 py-4 flex flex-col sm:flex-row gap-3 items-center justify-between border-t border-slate-200"
                >
                    <p class="text-sm text-slate-500">
                        Menampilkan
                        <span class="font-medium text-slate-900">
                            {{ invoices.from ?? 0 }}
                        </span>
                        sampai
                        <span class="font-medium text-slate-900">
                            {{ invoices.to ?? 0 }}
                        </span>
                        dari
                        <span class="font-medium text-slate-900">{{ total }}</span>
                        dokumen
                    </p>

                    <Pagination
                        :current-page="invoices.current_page"
                        :total="total"
                        :per-page="PER_PAGE"
                        @change="(p) => (filter.page = p)"
                    />
                </div>
            </section>
        </DataState>
    </div>
</template>