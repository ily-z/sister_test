<script setup>
import { computed, onMounted, ref } from 'vue'

import DataState from '@/components/DataState.vue'

import { api, readError } from '@/lib/api'
import { formatAngka, formatRupiah, formatRupiahRingkas } from '@/lib/format'

/*
 * ============================================================================
 *  ANALYTICS
 * ============================================================================
 *
 *  Meniru backend/resources/views/analytics/index.blade.php.
 *
 *  --- SELURUH ANGKA DI HALAMAN INI MASIH DATA DUMMY -------------------------
 *
 *  Ini bukan kelalaian pada port ini, dan bukan bawaan pemisahan tiga server.
 *  AnalyticsController::getAnalyticsData() sudah mengembalikan angka yang
 *  ditulis tangan -- total pendapatan 125 juta, 156 booking, dan seterusnya
 *  -- dan versi Blade menampilkan angka yang sama persis. Menggantinya dengan
 *  agregasi query yang sebenarnya berarti mengubah hasil bisnis, dan itu
 *  keputusan tersendiri, bukan bagian dari memindahkan monolith ke tiga server.
 *
 *  Karena itu angkanya ditampilkan apa adanya, TAPI diberi catatan di layar.
 *  Angka yang terlihat benar dan tidak ditandai bisa membuat seseorang
 *  mengambil keputusan penting berdasarkan data yang tidak pernah dihitung.
 *  Catatan lengkapnya ada di docs/KNOWN_ISSUES.md.
 *
 *  --- BULAN DAN MINGGUAN MASIH DALAM BAHASA INGGRIS ------------------------
 *
 *  Label bulan ("Jan" ... "Jul") dan label periode mingguan ("This Week" ...)
 *  datang apa adanya dari backend, masih hardcode. Format tanggal Indonesia
 *  yang dipakai di halaman lain berasal dari lib/format.js, yang menghitung
 *  sendiri nama bulan dari tanggal -- dan tidak bisa dipakai di sini karena
 *  yang ada hanyalah teks, bukan tanggal.
 *
 *  --- GRAFIK PENDAPATAN: BATAS ATAS TETAP 25 JUTA ------------------------
 *
 *  Blade membagi tinggi batang dengan 25.000.000 secara langsung, jadi batang
 *  terakhir (25 juta) persis menyentuh garis atas. Angka tersebut sengaja
 *  dipertahankan, bukan dihitung dari nilai tertinggi: kalau dihitung
 *  otomatis, sumbu ikut bergeser setiap kali datanya berubah dan grafik akan
 *  "berlompat" setiap kali ada bulan baru yang lebih tinggi.
 */

const memuat = ref(true)
const error = ref('')
const data = ref(null)

/* Pembatas tetap, bukan dihitung. Lihat catatan di kepala file. */
const BATAS_REVENUE = 25000000

async function muat() {
    memuat.value = true
    error.value = ''

    try {
        const { data: hasil } = await api.get('/analytics')

        data.value = hasil
    } catch (e) {
        error.value = readError(e).message
    } finally {
        memuat.value = false
    }
}

onMounted(muat)

/* -------------------------------------------------------------------------
 *  NILAI TURUNAN
 * ---------------------------------------------------------------------- */

const revenue = computed(() => data.value?.revenueByMonth || [])
const utilisasi = computed(() => data.value?.vehicleUtilizationData || [])
const tren = computed(() => data.value?.bookingTrends || [])
const teratas = computed(() => data.value?.topVehicles || [])

/* Tinggi batang dalam persen, memakai pembatas tetap. */
function tinggiPersen(nilai) {
    return Math.max(0, Math.min(100, (Number(nilai) / BATAS_REVENUE) * 100))
}

/* Garis bantu sumbu Y, dari 25 juta ke nol. */
const garisSumbu = computed(() =>
    [25, 20, 15, 10, 5, 0].map((juta) => ({
        label: juta === 0 ? '0' : `${juta}M`,
        posisi: (juta / 25) * 100,
        garisPutus: juta !== 0,
    }))
)

/*
 * Kartu KPI. Bentuk datanya diseragamkan supaya templat tidak perlu
 * membedakan mana angka uang, mana angka biasa, dan mana angka desimal.
 */
const kpi = computed(() => {
    if (!data.value) return []

    return [
        {
            key: 'revenue',
            label: 'Total Pendapatan',
            nilai: formatRupiah(data.value.totalRevenue),
            ikon: 'attach_money',
            warna: 'bg-emerald-50 text-emerald-600',
            catatan: 'Akumulasi pendapatan',
        },
        {
            key: 'bookings',
            label: 'Total Booking',
            nilai: formatAngka(data.value.totalBookings),
            ikon: 'assignment',
            warna: 'bg-blue-50 text-blue-600',
            catatan: 'Booking sepanjang masa',
        },
        {
            key: 'durasi',
            label: 'Rata-rata Durasi',
            nilai: `${String(data.value.avgBookingDuration).replace('.', ',')} hari`,
            ikon: 'schedule',
            warna: 'bg-amber-50 text-amber-600',
            catatan: 'Rata-rata masa sewa',
        },
        {
            key: 'utilisasi',
            label: 'Utilisasi',
            nilai: `${String(data.value.vehicleUtilization).replace('.', ',')}%`,
            ikon: 'trending_up',
            warna: 'bg-purple-50 text-purple-600',
            catatan: 'Tingkat pemakaian armada',
        },
    ]
})

/* Jumlah booking pada periode terbaik, untuk memberi konteks pada tabel
 * tren -- tanpa itu, angka 23 terlihat tanpa pembanding. */
const trenTertinggi = computed(() =>
    tren.value.reduce((maks, t) => Math.max(maks, Number(t.bookings) || 0), 0)
)
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Kepala -->
        <div
            class="flex flex-col md:flex-row md:justify-between md:items-end mb-8 gap-4"
        >
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Analytics</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Metrik kinerja dan ringkasan operasional.
                </p>
            </div>

            <div
                class="flex items-center gap-2 self-start rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm text-slate-600"
            >
                <span class="material-symbols-outlined ms-icon text-[18px]">
                    calendar_today
                </span>
                <span class="font-medium">7 bulan terakhir</span>
            </div>
        </div>

        <DataState :loading="memuat" :error="error" :is-empty="false" @retry="muat">
            <!--
                Data dummy. Ditandai di layar, bukan hanya di dokumentasi:
                angka di bawah terlihat benar, dan orang yang tidak tahu
                asalnya akan memakainya untuk mengambil keputusan.
            -->
            <div
                class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3"
            >
                <span
                    class="material-symbols-outlined ms-icon text-amber-600 shrink-0"
                >
                    warning
                </span>

                <p class="text-sm text-amber-800">
                    <strong>Angka di halaman ini belum dihitung dari data
                    asli.</strong>
                    Nilainya masih contoh yang ditulis langsung di
                    <code>AnalyticsController</code>, sama seperti versi
                    Blade-nya. Jangan dipakai untuk pengambilan keputusan
                    sampai modul analitiknya dikerjakan. Lihat
                    <code>docs/KNOWN_ISSUES.md</code>.
                </p>
            </div>

            <!-- KPI -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div
                    v-for="k in kpi"
                    :key="k.key"
                    class="pf-card p-6 h-36 flex flex-col justify-between relative overflow-hidden"
                >
                    <div class="flex justify-between items-start">
                        <div class="min-w-0">
                            <p
                                class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2"
                            >
                                {{ k.label }}
                            </p>
                            <h3
                                class="text-2xl font-bold text-slate-900 tracking-tight truncate"
                            >
                                {{ k.nilai }}
                            </h3>
                        </div>

                        <div
                            class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                            :class="k.warna"
                        >
                            <span class="material-symbols-outlined ms-icon">
                                {{ k.ikon }}
                            </span>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 mt-4">{{ k.catatan }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Pendapatan per bulan -->
                <section class="pf-card p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2
                                class="text-lg font-bold text-slate-900 tracking-tight"
                            >
                                Pendapatan per Bulan
                            </h2>
                            <p class="text-sm text-slate-500 mt-1">
                                Tren pendapatan bulanan
                            </p>
                        </div>
                    </div>

                    <div class="h-64 overflow-x-auto">
                        <div
                            class="h-full flex items-end justify-around relative pl-8 pb-6 min-w-[420px]"
                        >
                            <!-- Label sumbu Y -->
                            <div
                                class="absolute left-0 top-0 bottom-6 flex flex-col justify-between text-[11px] font-medium text-slate-400"
                            >
                                <span
                                    v-for="g in garisSumbu"
                                    :key="g.label"
                                >
                                    {{ g.label }}
                                </span>
                            </div>

                            <!-- Garis bantu -->
                            <div
                                class="absolute left-8 right-0 top-2 bottom-6 flex flex-col justify-between pointer-events-none"
                            >
                                <div
                                    v-for="g in garisSumbu"
                                    :key="`garis-${g.label}`"
                                    class="w-full border-t"
                                    :class="
                                        g.garisPutus
                                            ? 'border-slate-100 border-dashed'
                                            : 'border-slate-100'
                                    "
                                ></div>
                            </div>

                            <!-- Batang -->
                            <div
                                v-for="r in revenue"
                                :key="r.month"
                                class="w-8 bg-blue-600 rounded-t-md relative z-10 flex-shrink-0"
                                :style="`height: ${tinggiPersen(r.revenue)}%`"
                                :title="`${r.month}: ${formatRupiah(r.revenue)}`"
                            ></div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <div
                            class="flex justify-around pl-8 mt-2 text-[11px] font-semibold text-slate-500 uppercase min-w-[420px]"
                        >
                            <span v-for="r in revenue" :key="r.month">
                                {{ r.month }}
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Utilisasi kendaraan -->
                <section class="pf-card p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2
                                class="text-lg font-bold text-slate-900 tracking-tight"
                            >
                                Utilisasi Kendaraan
                            </h2>
                            <p class="text-sm text-slate-500 mt-1">
                                5 kendaraan dengan utilisasi tertinggi
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="k in utilisasi"
                            :key="k.vehicle"
                            class="flex items-center gap-4"
                        >
                            <div
                                class="w-32 text-sm font-medium text-slate-700 truncate"
                                :title="k.vehicle"
                            >
                                {{ k.vehicle }}
                            </div>

                            <div class="flex-1">
                                <div class="w-full bg-slate-200 rounded-full h-2">
                                    <div
                                        class="bg-purple-600 h-2 rounded-full transition-[width] duration-300"
                                        :style="`width: ${k.utilization}%`"
                                    ></div>
                                </div>
                            </div>

                            <div
                                class="w-12 text-sm font-bold text-slate-900 text-right"
                            >
                                {{ k.utilization }}%
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
                <!-- Tren booking -->
                <section class="pf-card overflow-hidden">
                    <div class="p-6 border-b border-slate-50">
                        <h2
                            class="text-lg font-bold text-slate-900 tracking-tight"
                        >
                            Tren Booking
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Perbandingan booking mingguan
                        </p>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4">
                            <div
                                v-for="t in tren"
                                :key="t.period"
                                class="flex items-center justify-between"
                            >
                                <span class="text-sm font-medium text-slate-700">
                                    {{ t.period }}
                                </span>

                                <span
                                    class="text-sm font-bold text-slate-900"
                                >
                                    {{ formatAngka(t.bookings) }} booking
                                </span>
                            </div>
                        </div>

                        <p
                            v-if="trenTertinggi"
                            class="mt-5 pt-4 border-t border-slate-100 text-xs text-slate-500"
                        >
                            Puncak pada periode ini:
                            <strong class="text-slate-700">
                                {{ formatAngka(trenTertinggi) }} booking
                            </strong>
                            .
                        </p>
                    </div>
                </section>

                <!-- Kendaraan teratas -->
                <section class="pf-card overflow-hidden">
                    <div class="p-6 border-b border-slate-50">
                        <h2
                            class="text-lg font-bold text-slate-900 tracking-tight"
                        >
                            Kendaraan Teratas
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Berdasarkan pendapatan yang dihasilkan
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr
                                    class="text-slate-400 font-semibold border-b border-slate-50"
                                >
                                    <th class="px-6 py-4">Kendaraan</th>
                                    <th class="px-6 py-4">Booking</th>
                                    <th class="px-6 py-4 text-right">Pendapatan</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-50">
                                <tr
                                    v-for="k in teratas"
                                    :key="k.name"
                                    class="hover:bg-slate-50/50 transition-colors"
                                >
                                    <td
                                        class="px-6 py-4 font-medium text-slate-900"
                                    >
                                        {{ k.name }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">
                                        {{ formatAngka(k.bookings) }}
                                    </td>
                                    <td
                                        class="px-6 py-4 font-bold text-emerald-600 text-right"
                                    >
                                        {{ formatRupiahRingkas(k.revenue) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </DataState>
    </div>
</template>
