<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import { api, readError } from '@/lib/api'
import { formatRupiah } from '@/lib/format'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  VERIFIKASI INVOICE (PUBLIK)
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Halaman yang dibuka ketika QR di invoice kertas dipindai. Tanpa login.
 *
 *  Ini halaman yang paling tinggi risikonya untuk salah baca, jadi ada dua
 *  hal yang dijaga ketat:
 *
 *    1. Halaman ini TIDAK pernah menampilkan "berhasil" tanpa data yang
 *       menunjangnya. Backend selalu membalas 200, termasuk saat token tidak
 *       ditemukan, dengan field `valid: false`. Halaman ini membaca `valid`
 *       itu -- bukan status HTTP. Kalau di masa depan someone mengubah
 *       backend agar membalas 404 untuk token tak dikenal, halaman ini akan
 *       menampilkan "gagal memuat", bukan "dokumen palsu". Itu jawaban yang
 *       benar: "tidak ditemukan" dan "dokumen ini palsu" adalah pernyataan
 *       berbeda.
 *
 *    2. Tidak ada tautan keluar dan tidak ada data yang bisa diklik. Orang
 *       yang memindai QR bisa jadi pihak ketiga -- calon penyewa, auditor,
 *       atau penerbank. Halaman ini tidak boleh menjadi jalan untuk
 *       menjelajah sistem lebih jauh.
 */

const route = useRoute()

const loading = ref(true)
const error = ref('')
const result = ref(null)

const token = computed(() => String(route.params.token || ''))

async function load() {
    loading.value = true
    error.value = ''
    result.value = null

    try {
        const { data } = await api.get(`/public/invoices/verify/${encodeURIComponent(token.value)}`)
        result.value = data
    } catch (e) {
        const detail = readError(e)

        /*
         * 404 berarti token-nya tidak ada di database. Itu jawaban "tidak
         * ditemukan", bukan "server bermasalah" -- jadi ditampilkan lewat
         * kartu yang sama dengan token yang tidak dikenal, bukan lewat pita
         * error.
         */
        if (detail.status === 404) {
            result.value = {
                valid: false,
                message: 'Kode QR ini tidak ditemukan dalam sistem kami.',
            }
        } else {
            error.value = detail.message
        }
    } finally {
        loading.value = false
    }
}

/*
 * Memuat ulang ketika token di URL berubah.
 *
 * Ini diperlukan karena halaman ini bisa dipakai tanpa memuat ulang
 * aplikasi: pengguna menyalin tautan verifikasi baru ke tab yang sama.
 * Tanpa watch, layar akan tetap menampilkan dokumen yang sebelumnya --
 * dan pada halaman verifikasi, itu informasi yang salah.
 */
watch(token, load)

onMounted(load)

const invoice = computed(() => result.value?.invoice || null)
const booking = computed(() => result.value?.booking || null)
const servis = computed(() => result.value?.servis || null)
const tandaTangan = computed(() => result.value?.tanda_tangan || null)
</script>

<template>
    <div class="min-h-screen bg-slate-100 py-8 px-4">
        <div class="max-w-lg mx-auto">
            <!-- Brand -->
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-brand-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <span class="material-symbols-outlined text-white text-[22px]">verified</span>
                </div>
                <h1 class="text-xl font-bold text-slate-800">Verifikasi Dokumen</h1>
                <p class="text-sm text-slate-500 mt-1">Precision Fleet - Enterprise ERP</p>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="pf-card p-6">
                <div class="flex items-center justify-center gap-3 py-8 text-slate-500">
                    <span class="material-symbols-outlined ms-icon text-[20px] animate-spin">
                        progress_activity
                    </span>
                    <span class="text-sm">Memeriksa kode...</span>
                </div>
            </div>

            <!-- Gagal dijangkau -->
            <div v-else-if="error" class="pf-card p-6">
                <div class="text-center py-4">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-[26px]">cloud_off</span>
                    </div>
                    <h2 class="text-base font-semibold text-slate-700">Tidak dapat menghubungi server</h2>
                    <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">{{ error }}</p>
                    <button type="button" class="btn-secondary mt-5" @click="load">
                        <span class="material-symbols-outlined ms-icon text-[18px]">refresh</span>
                        Coba lagi
                    </button>
                </div>
            </div>

            <!--
                Token tidak dikenal.

                Ditampilkan sebagai "tidak ditemukan", bukan sebagai dokumen
                palsu. Keduanya berbeda: dokumen palsu berarti ada dokumen
                yang dimanipulasi, sedangkan token yang tidak ada bisa berarti
                QR-nya rusak atau salah baca.
            -->
            <div v-else-if="!result?.valid" class="pf-card p-6">
                <div class="text-center py-4">
                    <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-[30px]">gpp_bad</span>
                    </div>
                    <h2 class="text-lg font-bold text-slate-800">Dokumen tidak ditemukan</h2>
                    <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                        {{ result?.message || 'Kode QR ini tidak ditemukan dalam sistem kami.' }}
                    </p>
                    <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                        Pastikan QR tidak terpotong atau tergores. Hubungi Precision Fleet
                        bila Anda yakin kode ini asli.
                    </p>
                </div>
            </div>

            <!-- Dokumen valid -->
            <template v-else>
                <div class="pf-card overflow-hidden">
                    <!-- Kepala -->
                    <div class="bg-emerald-50 border-b border-emerald-100 px-6 py-5 flex items-start gap-3">
                        <span class="material-symbols-outlined ms-icon text-emerald-500 text-[26px] shrink-0">
                            verified
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-bold text-emerald-800">Dokumen Asli</h2>
                            <p class="text-sm text-emerald-700 mt-0.5 leading-relaxed">
                                {{ result.message }}
                            </p>
                        </div>
                    </div>

                    <!-- Nomor dokumen -->
                    <dl class="divide-y divide-slate-100 text-sm">
                        <div class="flex justify-between gap-4 px-6 py-3">
                            <dt class="text-slate-500 shrink-0">Nomor Surat</dt>
                            <dd class="font-semibold text-slate-800 text-right break-all">
                                {{ invoice?.nomor_surat || '-' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 px-6 py-3">
                            <dt class="text-slate-500 shrink-0">Nomor Seri</dt>
                            <dd class="font-semibold text-slate-800 text-right break-all">
                                {{ invoice?.nomor_seri || '-' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 px-6 py-3">
                            <dt class="text-slate-500 shrink-0">Jenis</dt>
                            <dd class="font-semibold text-slate-800 text-right">
                                {{ invoice?.jenis_label || '-' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 px-6 py-3">
                            <dt class="text-slate-500 shrink-0">Tanggal Terbit</dt>
                            <dd class="font-semibold text-slate-800 text-right">
                                {{ invoice?.issued_at || '-' }}
                            </dd>
                        </div>
                    </dl>

                    <!-- Data penyewaan -->
                    <div v-if="booking" class="border-t border-slate-100 px-6 py-4">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                            Data Penyewaan
                        </h3>
                        <dl class="space-y-2.5 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500 shrink-0">Nomor Pesanan</dt>
                                <dd class="font-semibold text-slate-800 text-right">
                                    {{ booking.nomor_pesanan }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500 shrink-0">Pelanggan</dt>
                                <dd class="font-semibold text-slate-800 text-right break-words">
                                    {{ booking.nama_pelanggan }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500 shrink-0">Kendaraan</dt>
                                <dd class="font-semibold text-slate-800 text-right break-words">
                                    {{ booking.kendaraan || '-' }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4 pt-2.5 border-t border-slate-100">
                                <dt class="text-slate-500 shrink-0">Nilai</dt>
                                <dd class="font-bold text-brand-600 text-right">
                                    {{ formatRupiah(booking.total_harga) }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Data servis -->
                    <div v-if="servis" class="border-t border-slate-100 px-6 py-4">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                            Data Servis
                        </h3>
                        <dl class="space-y-2.5 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500 shrink-0">Bengkel</dt>
                                <dd class="font-semibold text-slate-800 text-right break-words">
                                    {{ servis.nama_bengkel }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500 shrink-0">Kendaraan</dt>
                                <dd class="font-semibold text-slate-800 text-right break-words">
                                    {{ servis.kendaraan || '-' }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4 pt-2.5 border-t border-slate-100">
                                <dt class="text-slate-500 shrink-0">Total Biaya</dt>
                                <dd class="font-bold text-brand-600 text-right">
                                    {{ formatRupiah(servis.total_biaya) }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Tanda tangan -->
                    <div v-if="tandaTangan" class="border-t border-slate-100 px-6 py-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                Tanda Tangan
                            </h3>
                            <span
                                class="px-2 py-1 rounded-md text-[10px] font-bold uppercase"
                                :class="
                                    tandaTangan.lengkap
                                        ? 'bg-emerald-50 text-emerald-600'
                                        : 'bg-amber-50 text-amber-600'
                                "
                            >
                                {{ tandaTangan.lengkap ? 'Lengkap' : 'Belum Lengkap' }}
                            </span>
                        </div>

                        <ul class="space-y-2">
                            <li
                                v-for="row in tandaTangan.blok"
                                :key="row.role"
                                class="flex items-center justify-between gap-3 text-sm"
                            >
                                <span class="flex items-center gap-2 text-slate-600 min-w-0">
                                    <span
                                        class="material-symbols-outlined ms-icon text-[16px] shrink-0"
                                        :class="row.ada ? 'text-emerald-500' : 'text-slate-300'"
                                    >
                                        {{ row.ada ? 'check_circle' : 'radio_button_unchecked' }}
                                    </span>
                                    <span class="truncate">{{ row.judul }}</span>
                                </span>

                                <span
                                    class="text-xs text-right shrink-0"
                                    :class="row.ada ? 'text-slate-500' : 'text-slate-400'"
                                >
                                    {{ row.ada ? row.nama : 'belum ditandatangani' }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <!-- Jejak waktu -->
                    <div class="border-t border-slate-100 px-6 py-3.5 bg-slate-50">
                        <p class="text-xs text-slate-500 leading-relaxed">
                            <template v-if="result.pertama_dipescan">
                                Kode QR ini pertama kali diperiksa pada
                                <strong class="font-semibold text-slate-600">
                                    {{ result.pertama_dipescan }}
                                </strong>.
                            </template>
                            <template v-else>
                                Kode QR ini belum pernah diperiksa sebelumnya.
                            </template>
                        </p>
                    </div>
                </div>

                <p class="text-center text-xs text-slate-400 mt-5 leading-relaxed">
                    Halaman ini menampilkan informasi yang tercatat di sistem Precision Fleet.
                    Hubungi kami bila menemukan ketidaksesuaian.
                </p>
            </template>
        </div>
    </div>
</template>