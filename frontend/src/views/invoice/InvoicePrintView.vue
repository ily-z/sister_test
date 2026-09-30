<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import InvoiceSignatureModal from '@/components/InvoiceSignatureModal.vue'
import { api, readError } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import {
    formatRupiah,
    formatTanggalLengkap,
    formatTanggalWaktu,
} from '@/lib/format'

/*
 * ============================================================================
 *  CETAK INVOICE SEWA
 * ============================================================================
 *
 *  Meniru resources/views/invoices/print.blade.php. Mengganti Blade dengan
 *  Vue di sini bukan pilihan gaya: halaman ini harus hidup di server
 *  frontend supaya QR invoice di kertas tetap menunjuk ke satu alamat yang
 *  sudah diketahui pelanggan.
 *
 *  PDF tetap dibuat di backend oleh dompdf. Halaman ini hanya menampilkan
 *  pratinjau yang bisa dicetak lewat dialog cetak browser, lalu tombol
 *  unduh mengambil PDF server yang sudah ditandatangani lengkap.
 *
 *  Backend memblokir unduh sampai kedua blok tanda tangan ada. Itu disengaja:
 *  PDF bertanda tangan yang sudah distribution-safe tidak boleh bisa
 *  diunduh setengah jadi lalu dicetak ulang tanpa tanda tangan.
 */

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const invoiceId = computed(() => route.params.id)

const memuat = ref(true)
const error = ref(null)
const data = ref(null)

const invoice = computed(() => data.value?.invoice || null)
const booking = computed(() => data.value?.booking || null)
const signatures = computed(() => data.value?.signatures || null)

/* Nama surat lebih dipakai daripada nomor seri karena sudah format resmi,
 * misalnya "JRB/SK/09/2026/0007". Nomor seri hanya dipakai kalau surat
 * belum dibuat. */
const nomorDokumen = computed(
    () => invoice.value?.surat?.nomor_surat || invoice.value?.nomor_seri || '-'
)

const JENIS_LABEL = {
    SH: 'SEWA HARIAN',
    SK: 'SEWA KONTRAK',
    AK: 'ASURANSI KERUSAKAN',
}

const jenisLabel = computed(
    () => JENIS_LABEL[invoice.value?.jenis_invoice] || 'INVOICE'
)

/* Dipakai sebagai nilai <img src> langsung. Endpoint ini mengembalikan SVG,
 * jadi tidak perlu fetch terpisah di script. */
const qrUrl = computed(
    () => `${import.meta.env.VITE_API_BASE_URL}/invoices/${invoiceId.value}/qr`
)

/* Dipakai sebagai href untuk mendownload PDF server. */
const apiBase = import.meta.env.VITE_API_BASE_URL

/* Durasi hari: dipakai untuk mengalikan harga harian pada mode harian.
 * Samanya dengan diffInDays di template Blade. */
const durasiHari = computed(() => {
    if (!booking.value) return 0

    const mulai = new Date(booking.value.tanggal_mulai)
    const selesai = new Date(booking.value.tanggal_selesai)

    return Math.max(0, Math.round((selesai - mulai) / 86400000))
})

const tipeSewa = computed(() => booking.value?.tipe_sewa || 'harian')

function hitungSubtotalKendaraan() {
    if (!booking.value) return 0

    return (booking.value.bookingCars || []).reduce((total, bc) => {
        const harga = Number(bc.harga_dasar_per_hari) || 0

        return total + (tipeSewa.value === 'harian' ? harga * durasiHari.value : harga)
    }, 0)
}

const subtotalKendaraan = computed(() => hitungSubtotalKendaraan())
const totalAddons = computed(() => Number(booking.value?.total_addons) || 0)
const totalHarga = computed(() => Number(booking.value?.total_harga) || 0)

/* ============================================================================
 *  MUAT
 * ============================================================================ */

async function muat() {
    memuat.value = true
    error.value = null

    try {
        const { data: payload } = await api.get(`/invoices/${invoiceId.value}/print`)
        data.value = payload
    } catch (e) {
        error.value = readError(e).message
    } finally {
        memuat.value = false
    }
}

/* ============================================================================
 *  UNDUH PDF
 * ============================================================================ */

const mengunduh = ref(false)

async function unduhPdf() {
    mengunduh.value = true

    try {
        const { data: hasil } = await api.get(`/invoices/${invoiceId.value}/download`)

        /* Backend membalas { url, filename } dengan url berupa signed URL
         * berumur pendek. Membuka lewat <a download> dipakai supaya nama
         * berkas ikut hilang dari daftar unduhan peramban. */
        const a = document.createElement('a')
        a.href = hasil.url
        a.download = hasil.filename || 'invoice.pdf'
        a.rel = 'noopener'
        document.body.appendChild(a)
        a.click()
        a.remove()
    } catch (e) {
        toast.error(readError(e).message)
    } finally {
        mengunduh.value = false
    }
}

/* ============================================================================
 *  TANDA TANGAN
 * ============================================================================
 *
 *  Logikanya ada di InvoiceSignatureModal -- dipakai juga oleh halaman cetak
 *  invoice bengkel, yang aturannya sama persis.
 */

const modalTtd = ref(false)

const blokTtd = computed(() => signatures.value?.blok || [])

function cariBlok(role) {
    return blokTtd.value.find((b) => b.role === role) || null
}

const sudahLengkap = computed(() => Boolean(signatures.value?.lengkap))

/*
 * Nama yang dicetak di garis tanda tangan untuk blok yang belum terisi.
 * Pelanggan memakai nama pesanan; pihak perusahaan memakai nama badan usaha.
 */
const NAMA_BAWAAN = computed(() => ({
    pelanggan: booking.value?.nama_pelanggan || '',
    finance: 'PT Jaya Royal Bersama',
}))

function namaBawaan(role) {
    return NAMA_BAWAAN.value[role] || ''
}


/* ============================================================================
 *  CETAK
 * ============================================================================ */

/*
 * Dialog cetak tidak dibuka otomatis.
 *
 * Versi Blade memanggil window.print() 500 ms setelah halaman dimuat. Di
 * SPA, print dipanggil sebelum Vue selesai menggambar, sehingga hasilnya bisa
 * keluar sebelum halaman benar-benar tampil.
 * Pemanggilan otomatis juga bertabrakan dengan Android Chrome yang
 * memblokir window.print() di luar event pengguna.
 */
function cetak() {
    window.print()
}

onMounted(muat)
</script>

<template>
    <div class="inv-body">
        <!-- Panel kendali. Tidak ikut tercetak. -->
        <div class="inv-toolbar inv-hide-print">
            <div>
                <div class="inv-toolbar-title">{{ nomorDokumen }}</div>
                <div class="inv-toolbar-sub">
                    <span v-if="invoice">{{ jenisLabel }}</span>
                    <span v-if="booking">&middot; {{ booking.nama_pelanggan }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inv-btn inv-btn-ghost"
                    @click="router.back()"
                >
                    <span class="material-symbols-outlined ms-icon text-[16px]">arrow_back</span>
                    Kembali
                </button>

                <button
                    v-if="auth.can('finance.create')"
                    type="button"
                    class="inv-btn inv-btn-ghost"
                    @click="bukaModalTtd"
                >
                    <span class="material-symbols-outlined ms-icon text-[16px]">
                        {{ sudahLengkap ? 'edit' : 'draw' }}
                    </span>
                    {{ sudahLengkap ? 'Ganti Tanda Tangan' : 'Tanda Tangani' }}
                </button>

                <button
                    v-if="sudahLengkap"
                    type="button"
                    class="inv-btn inv-btn-primary"
                    :disabled="mengunduh"
                    @click="unduhPdf"
                >
                    <span
                        class="material-symbols-outlined ms-icon text-[16px]"
                        :class="{ 'animate-spin': mengunduh }"
                    >
                        {{ mengunduh ? 'progress_activity' : 'download' }}
                    </span>
                    {{ mengunduh ? 'Menyiapkan PDF...' : 'Unduh PDF' }}
                </button>

                <button type="button" class="inv-btn inv-btn-primary" @click="cetak">
                    <span class="material-symbols-outlined ms-icon text-[16px]">print</span>
                    Cetak
                </button>
            </div>
        </div>

        <!-- Status tanda tangan -->
        <div
            v-if="!memuat && !error && !sudahLengkap"
            class="inv-alert inv-alert-warn inv-hide-print"
            style="max-width: 210mm; margin: 16px auto 0;"
        >
            Dokumen belum bisa diunduh. Kedua blok tanda tangan --
            {{ signatures?.missing?.join(' dan ') || 'belum lengkap' }} -- masih
            kosong. Pratinjau di bawah tetap boleh dicetak untuk checking.
        </div>

        <!-- Memuat -->
        <div
            v-if="memuat"
            class="flex flex-col items-center justify-center gap-3 py-32 text-slate-500"
        >
            <span
                class="material-symbols-outlined ms-icon text-[36px] animate-spin text-brand-600"
            >
                progress_activity
            </span>
            <p class="text-sm">Memuat dokumen...</p>
        </div>

        <!-- Gagal -->
        <div
            v-else-if="error"
            class="flex flex-col items-center justify-center gap-4 py-32 px-4"
        >
            <span class="material-symbols-outlined ms-icon text-[40px] text-rose-500">
                error
            </span>
            <p class="text-sm text-slate-600 text-center max-w-md">{{ error }}</p>
            <button type="button" class="inv-btn inv-btn-primary" @click="muat">Coba lagi</button>
        </div>

        <!-- Kertas -->
        <div v-else-if="invoice" class="inv-sheet inv-page">
            <!-- Kepala -->
            <div class="inv-header">
                <div class="inv-logo">JRB</div>

                <div class="inv-header-info">
                    <div class="inv-company">PT JAYA ROYAL BERSAMA</div>
                    <div class="inv-company-sub">
                        Jasa Rental &amp; Transportasi Kendaraan
                    </div>
                    <div class="inv-company-addr">
                        Jl. Teuku Umar No. 18B, Bangkalan, Jawa Timur
                    </div>
                </div>

                <div class="inv-doc">
                    <div class="inv-doc-label">No. Dokumen</div>
                    <div class="inv-doc-value">{{ nomorDokumen }}</div>
                    <div class="inv-doc-date">
                        {{ formatTanggalLengkap(invoice.issued_at) }}
                    </div>
                </div>
            </div>

            <div class="inv-divider"></div>

            <div class="inv-title">INVOICE</div>
            <div class="inv-subtitle">{{ jenisLabel }}</div>

            <!-- Pelanggan & jadwal -->
            <div v-if="booking" class="inv-info-grid">
                <div class="inv-info-box">
                    <div class="inv-info-box-header">Pelanggan</div>
                    <table class="inv-info-table">
                        <tr>
                            <td>Nama</td>
                            <td>: <strong>{{ booking.nama_pelanggan }}</strong></td>
                        </tr>
                        <tr>
                            <td>Telepon</td>
                            <td>: {{ booking.kontak_pelanggan }}</td>
                        </tr>
                        <tr v-if="booking.email">
                            <td>Email</td>
                            <td>: {{ booking.email }}</td>
                        </tr>
                        <tr>
                            <td>No. Pesanan</td>
                            <td>: {{ booking.nomor_pesanan }}</td>
                        </tr>
                    </table>
                </div>

                <div class="inv-info-box">
                    <div class="inv-info-box-header">Jadwal Sewa</div>
                    <table class="inv-info-table">
                        <tr>
                            <td>Tipe Sewa</td>
                            <td>: {{ tipeSewa }}</td>
                        </tr>
                        <tr>
                            <td>Mulai</td>
                            <td>: {{ formatTanggalLengkap(booking.tanggal_mulai) }}</td>
                        </tr>
                        <tr>
                            <td>Selesai</td>
                            <td>: {{ formatTanggalLengkap(booking.tanggal_selesai) }}</td>
                        </tr>
                        <tr>
                            <td>Durasi</td>
                            <td>: {{ durasiHari }} hari</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Keterangan invoice kerusakan -->
            <div v-if="invoice.jenis_invoice === 'AK'" class="inv-note">
                <strong>Keterangan:</strong> Invoice ini merupakan tagihan atas biaya
                perbaikan kerusakan kendaraan selama masa sewa.
            </div>

            <!-- Item -->
            <table class="inv-items">
                <thead>
                    <tr>
                        <th style="width: 12%">Kode</th>
                        <th style="width: 38%">Keterangan</th>
                        <th style="width: 18%" class="inv-right">Harga</th>
                        <th style="width: 12%" class="inv-center">Qty</th>
                        <th style="width: 20%" class="inv-right">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="bc in booking?.bookingCars || []" :key="bc.id">
                        <td>{{ bc.car?.kode_mobil || bc.car?.plat_nomor }}</td>
                        <td>
                            {{ bc.car?.merk }} {{ bc.car?.model }}
                            ({{ bc.car?.plat_nomor }})
                        </td>
                        <td class="inv-right">
                            {{ formatRupiah(bc.harga_dasar_per_hari) }}
                        </td>
                        <td class="inv-center">
                            {{ tipeSewa === 'harian' ? `${durasiHari} hari` : '1' }}
                        </td>
                        <td class="inv-right">
                            {{
                                formatRupiah(
                                    tipeSewa === 'harian'
                                        ? (Number(bc.harga_dasar_per_hari) || 0) * durasiHari
                                        : Number(bc.harga_dasar_per_hari) || 0
                                )
                            }}
                        </td>
                    </tr>

                    <tr v-for="addon in booking?.addons || []" :key="addon.id">
                        <td></td>
                        <td>{{ addon.nama_biaya }}</td>
                        <td class="inv-right">{{ formatRupiah(addon.nominal) }}</td>
                        <td class="inv-center">1</td>
                        <td class="inv-right">{{ formatRupiah(addon.nominal) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Total -->
            <div class="inv-totals">
                <div class="inv-total-row">
                    <span class="inv-total-label">Subtotal Kendaraan:</span>
                    <span class="inv-total-value">{{ formatRupiah(subtotalKendaraan) }}</span>
                </div>

                <div v-if="totalAddons > 0" class="inv-total-row">
                    <span class="inv-total-label">Biaya Tambahan:</span>
                    <span class="inv-total-value">{{ formatRupiah(totalAddons) }}</span>
                </div>

                <div class="inv-grand-total">
                    <div class="inv-total-row">
                        <span class="inv-total-label">TOTAL:</span>
                        <span class="inv-total-value">{{ formatRupiah(totalHarga) }}</span>
                    </div>
                </div>
            </div>

            <!--
              Terbilang dihilangkan dari pratinjau.
              Backend menghitungnya dengan NumberFormatter('id', SPELLOUT)
              yang hanya ada di ekstensi intl PHP. Menghitungnya ulang di
              browser akan menghasilkan teks berbeda dari PDF -- dan yang
              dicetak ke kertas adalah PDF, bukan pratinjau ini.
            -->

            <!-- Kaki -->
            <div class="inv-footer">
                <div class="inv-qr">
                    <img
                        :src="qrUrl"
                        alt="QR verifikasi invoice"
                        style="width: 70px; height: 70px;"
                    />
                    <div class="inv-qr-label">Scan untuk Verifikasi</div>
                </div>

                <div class="inv-ttd">
                    <div v-for="b in blokTtd" :key="b.role" class="inv-ttd-block">
                        <div v-if="b.role === 'pelanggan'" class="inv-ttd-kota">
                            Bangkalan, {{ formatTanggalLengkap(invoice.issued_at) }}
                        </div>
                        <div v-else class="inv-ttd-kota">&nbsp;</div>

                        <div class="inv-ttd-label">{{ b.judul }}</div>

                        <template v-if="b.ada">
                            <div class="inv-ttd-sign">
                                <img :src="b.image_url" :alt="`Tanda tangan ${b.signer_name}`" />
                            </div>
                            <div class="inv-ttd-line">( {{ b.signer_name }} )</div>
                            <div class="inv-ttd-meta">
                                Ditandatangani {{ formatTanggalWaktu(b.signed_at) }}
                            </div>
                        </template>

                        <template v-else>
                            <div class="inv-ttd-space"></div>
                            <div class="inv-ttd-line">
                                ( {{ namaBawaan(b.role) || '_________________' }} )
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal tanda tangan -->
        <InvoiceSignatureModal
            v-if="modalTtd"
            :invoice-id="invoiceId"
            :signatures="signatures"
            :default-names="NAMA_BAWAAN"
            @saved="muat"
            @close="modalTtd = false"
        />
    </div>
</template>
