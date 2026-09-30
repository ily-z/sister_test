<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import InvoiceSignatureModal from '@/components/InvoiceSignatureModal.vue'
import { api, readError } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import {
    formatAngka,
    formatRupiah,
    formatTanggalLengkap,
    formatTanggalWaktu,
} from '@/lib/format'

/*
 * ============================================================================
 *  CETAK INVOICE BENGKEL
 * ============================================================================
 *
 *  Mirrors backend/resources/views/invoices/print-bengkel.blade.php. Aspek
 *  cetak memakai kelas yang sama dengan InvoicePrintView, keduanya di
 *  src/assets/invoice-print.css.
 *
 *  Bentuk datanya berbeda dari invoice sewa: tidak ada booking, melainkan
 *  serviceLog beserta detail tindakannya. Endpoint yang dipakai
 *  /invoices/{id}/print-bengkel, bukan /print, karena yang terakhir
 *  mengembalikan booking.
 *
 *  Kolom kode mobil diambil dari invoices.kode_mobil, bukan dari cars. Kolom
 *  kode_mobil tidak pernah ada di cars, jadi kalau pdf.template memakai
 *  mobil sebagai sumber, kode akan selalu kosong.
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
const serviceLog = computed(() => data.value?.serviceLog || null)
const signatures = computed(() => data.value?.signatures || null)
const car = computed(() => serviceLog.value?.car || null)
const details = computed(() => serviceLog.value?.details || [])

const nomorDokumen = computed(
    () => invoice.value?.surat?.nomor_surat || invoice.value?.nomor_seri || '-'
)

const selesai = computed(() => serviceLog.value?.status_bengkel === 'closed')

const totalBiaya = computed(() =>
    details.value.reduce((total, d) => total + (Number(d.biaya) || 0), 0)
)

const qrUrl = computed(
    () => `${import.meta.env.VITE_API_BASE_URL}/invoices/${invoiceId.value}/qr`
)

const blokTtd = computed(() => signatures.value?.blok || [])

const sudahLengkap = computed(() => Boolean(signatures.value?.lengkap))

const NAMA_BAWAAN = { pelanggan: '', finance: 'PT Jaya Royal Bersama' }

/* ============================================================================
 *  MUAT
 * ============================================================================ */

async function muat() {
    memuat.value = true
    error.value = null

    try {
        const { data: payload } = await api.get(
            `/invoices/${invoiceId.value}/print-bengkel`
        )
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
        const { data } = await api.get(`/invoices/${invoiceId.value}/download`)

        const a = document.createElement('a')
        a.href = data.url
        a.download = data.filename || 'invoice-bengkel.pdf'
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
 *  TANDA TANGAN & CETAK
 * ============================================================================ */

const modalTtd = ref(false)

function cetak() {
    window.print()
}

onMounted(muat)
</script>

<template>
    <div class="inv-body">
        <!-- Panel kendali -->
        <div class="inv-toolbar inv-hide-print">
            <div>
                <div class="inv-toolbar-title">{{ nomorDokumen }}</div>
                <div class="inv-toolbar-sub">
                    Invoice Bengkel
                    <template v-if="car">
                        &middot; {{ car.merk }} {{ car.model }} ({{ car.plat_nomor }})
                    </template>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" class="inv-btn inv-btn-ghost" @click="router.back()">
                    <span class="material-symbols-outlined ms-icon text-[16px]">arrow_back</span>
                    Kembali
                </button>

                <button
                    v-if="auth.can('finance.create')"
                    type="button"
                    class="inv-btn inv-btn-ghost"
                    @click="modalTtd = true"
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
            kosong.
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
        <div v-else-if="invoice && serviceLog" class="inv-sheet inv-page">
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

            <div class="inv-title">INVOICE BENGKEL</div>
            <div class="inv-subtitle">PERAWATAN &amp; PERBAIKAN KENDARAAN</div>

            <!-- Kendaraan & servis -->
            <div class="inv-info-grid">
                <div class="inv-info-box">
                    <div class="inv-info-box-header">Data Kendaraan</div>
                    <table class="inv-info-table">
                        <tr>
                            <td>Merk/Model</td>
                            <td>: <strong>{{ car?.merk }} {{ car?.model }}</strong></td>
                        </tr>
                        <tr>
                            <td>Plat Nomor</td>
                            <td>: {{ car?.plat_nomor }}</td>
                        </tr>
                        <tr>
                            <td>Kode Mobil</td>
                            <td>: {{ invoice.kode_mobil || '-' }}</td>
                        </tr>
                        <tr>
                            <td>KM Saat Ini</td>
                            <td>: {{ formatAngka(car?.km_saat_ini) }} km</td>
                        </tr>
                    </table>
                </div>

                <div class="inv-info-box">
                    <div class="inv-info-box-header">Detail Servis</div>
                    <table class="inv-info-table">
                        <tr>
                            <td>Tanggal Masuk</td>
                            <td>: {{ formatTanggalLengkap(serviceLog.tanggal_masuk) }}</td>
                        </tr>
                        <tr>
                            <td>Tanggal Keluar</td>
                            <td>
                                : {{ serviceLog.tanggal_keluar ? formatTanggalLengkap(serviceLog.tanggal_keluar) : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                : <span class="inv-badge" :class="selesai ? 'inv-badge-done' : 'inv-badge-wip'">
                                    {{ selesai ? 'Selesai' : 'Proses' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Bengkel</td>
                            <td>: {{ serviceLog.nama_bengkel || '-' }}</td>
                        </tr>
                        <tr v-if="serviceLog.catatan">
                            <td>Catatan</td>
                            <td>: {{ serviceLog.catatan }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Item -->
            <table class="inv-items">
                <thead>
                    <tr>
                        <th style="width: 8%">No</th>
                        <th style="width: 60%">Tindakan / Jasa Servis</th>
                        <th style="width: 32%" class="inv-right">Biaya</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(d, i) in details" :key="d.id ?? i">
                        <td>{{ i + 1 }}</td>
                        <td>{{ d.deskripsi_tindakan || '-' }}</td>
                        <td class="inv-right">{{ formatRupiah(d.biaya) }}</td>
                    </tr>

                    <tr v-if="!details.length">
                        <td colspan="3" class="inv-center inv-empty">Tidak ada detail servis</td>
                    </tr>
                </tbody>
            </table>

            <!-- Total -->
            <div class="inv-totals">
                <div class="inv-grand-total">
                    <div class="inv-total-row">
                        <span class="inv-total-label">TOTAL:</span>
                        <span class="inv-total-value">{{ formatRupiah(totalBiaya) }}</span>
                    </div>
                </div>
            </div>

            <!-- Kaki -->
            <div class="inv-footer">
                <div class="inv-qr">
                    <img
                        :src="qrUrl"
                        alt="QR verifikasi invoice"
                        style="width: 70px; height: 70px;"
                    />
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
                                ( {{ NAMA_BAWAAN[b.role] || '_________________________' }} )
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