<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import ChartBars from '@/components/ChartBars.vue'
import ChartDonut from '@/components/ChartDonut.vue'
import DataState from '@/components/DataState.vue'

import { api } from '@/lib/api'
import { formatRupiah, formatRupiahRingkas } from '@/lib/format'

/*
 * ============================================================================
 *  LAPORAN PERFORMA BISNIS
 * ============================================================================
 *
 *  Meniru backend/resources/views/keuangan/index.blade.php.
 *
 *  --- FILTER TIDAK DISIMPAN DI URL SECARA OTOMATIS -----------------------
 *
 *  Endpoint menerima period, month, dan expense_filter sebagai query string,
 *  dan mengembalikan nilai yang sama di respons (period, month, expenseFilter).
 *  Status filter dibaca dari situ, bukan dari state lokal, supaya hasil
 *  refresh halaman tidak melompat kembali ke default.
 *
 *  month dikirim sebagai null, bukan string kosong. Backend memanggil
 *  validMonth() pada nilainya, dan string kosong akan ditolak.
 *
 *  --- month MENANG ATAS period ------------------------------------------
 *
 *  buildLaporanReport() mengecek bulan lebih dulu: kalau month terisi, filter
 *  period diabaikan sepenuhnya. UI Pun disembunyikan saat bulan dipilih, biar
 *  tidak terlihat seperti dua filter yang sama-sama berlaku padahal tidak.
 *
 *  --- LABEL BULAN DIHITUNG DI FRONTEND -----------------------------------
 *
 *  Backend mengirim monthLabel hasil Carbon::translatedFormat(). Karena
 *  APP_LOCALE sengaja en, label itu berbahasa Inggris, sementara halaman
 *  lain memakai nama bulan Indonesia. formatBulanTahun() dipakai di sini
 *  supaya konsisten.
 */

const route = useRoute()
const router = useRouter()

/* Nilai default harus sama dengan resolveFilters() di backend. */
const period = ref(String(route.query.period || '6m'))
const month = ref(route.query.month ? String(route.query.month) : '')
const expenseFilter = ref(String(route.query.expense_filter || 'all'))

const memuat = ref(true)
const error = ref(null)
const laporan = ref(null)

const OPSI_PERIOD = [
    { nilai: '1w', label: '1 Minggu' },
    { nilai: '1m', label: '1 Bulan' },
    { nilai: '6m', label: '6 Bulan' },
    { nilai: 'all', label: 'Semua' },
]

const OPSI_EXPENSE = [
    { nilai: 'all', label: 'Semua' },
    { nilai: 'kantor', label: 'Kantor' },
    { nilai: 'non-kantor', label: 'Non-Kantor' },
]

async function muat() {
    memuat.value = true
    error.value = null

    const params = { period: period.value, expense_filter: expenseFilter.value }

    if (month.value) params.month = month.value

    try {
        const { data } = await api.get('/keuangan/data', { params })

        laporan.value = data
    } catch (e) {
        /* Pesan diambil apa adanya dari respons, sudah satu bahasa dengan halaman lain. */
        error.value = e?.response?.data?.message || 'Gagal memuat laporan keuangan.'
    } finally {
        memuat.value = false
    }
}

/*
 * Query string ditulis ulang setiap filter berubah. Perubahan di sini memicu muat()
 * lewat watcher, jadi muat() tidak perlu dipanggil dua kali.
 */
watch([period, month, expenseFilter], () => {
    const query = { period: period.value, expense_filter: expenseFilter.value }

    if (month.value) query.month = month.value
    else delete query.month

    router.replace({ query })
})

watch(
    () => route.query,
    (q) => {
        period.value = String(q.period || '6m')
        expenseFilter.value = String(q.expense_filter || 'all')

        const bulan = q.month ? String(q.month) : ''
        if (bulan !== month.value) month.value = bulan
    }
)

/* ============================================================================
 *  NILAI
 * ============================================================================ */

const totalPemasukan = computed(() => Number(laporan.value?.totalPemasukan) || 0)
const totalPengeluaran = computed(() => Number(laporan.value?.totalPengeluaran) || 0)
const labaRugi = computed(() => Number(laporan.value?.labaRugi) || 0)
const labaBersih = computed(() => Number(laporan.value?.labaBersih) || 0)
const growthRate = computed(() => Number(laporan.value?.growthRate) || 0)
const profitMargin = computed(() => Number(laporan.value?.profitMargin) || 0)
const cashRunway = computed(() => Number(laporan.value?.cashRunway) || 0)
const burnRate = computed(() => Number(laporan.value?.burnRate) || 0)

const pengeluaranKantor = computed(
    () => Number(laporan.value?.pengeluaranKantor) || 0
)
const pengeluaranNonKantor = computed(
    () => Number(laporan.value?.pengeluaranNonKantor) || 0
)

const adaFilterBulan = computed(() => Boolean(month.value))
const periodLabel = computed(() =>
    adaFilterBulan.value
        ? `Bulan ${month.value}`
        : (laporan.value?.periodLabel || '6 Bulan Terakhir')
)
const expenseFilterLabel = computed(() => laporan.value?.expenseFilterLabel || 'Semua')

/* Filter kategori yang sedang berlaku. Baris yang tidak lolos filter
 * disembunyikan, bukan hanya diredupkan, supaya donut tidak menampilkan
 * potongan yang tidak ikut dihitung. */
const tampilKantor = computed(
    () => expenseFilter.value === 'all' || expenseFilter.value === 'kantor'
)
const tampilNonKantor = computed(
    () => expenseFilter.value === 'all' || expenseFilter.value === 'non-kantor'
)

/* ============================================================================
 *  GRAFIK
 * ============================================================================ */

const segmenDonut = computed(() => {
    const hasil = []

    if (tampilKantor.value && pengeluaranKantor.value > 0) {
        hasil.push({
            label: 'Pengeluaran kantor',
            value: pengeluaranKantor.value,
            color: '#f43f5e',
            format: 'rp',
        })
    }

    if (tampilNonKantor.value && pengeluaranNonKantor.value > 0) {
        hasil.push({
            label: 'Pengeluaran non-kantor',
            value: pengeluaranNonKantor.value,
            color: '#fb923c',
            format: 'rp',
        })
    }

    if (labaRugi.value > 0) {
        hasil.push({
            label: 'Laba',
            value: labaRugi.value,
            color: '#10b981',
            format: 'rp',
        })
    }

    /* Filter non-kantor bisa menyisakan data kosong kalau tidak ada transaksi
     * berlabel "kantor". Donut akan tampil kosong tanpa penjelasan kalau hal itu
     * terjadi, jadi yang ditampilkan adalah catatan filter yang terlalu ketat. */
    if (!hasil.length) {
        hasil.push({
            label: 'Tidak ada data',
            value: 1,
            color: '#e2e8f0',
            format: 'rp',
        })
    }

    return hasil
})

const donutKosong = computed(() =>
    expenseFilterLabel.value !== 'Semua Pengeluaran' &&
    totalPengeluaran.value === 0
)

/*
 * cashFlow datang sebagai objek: kunci "2026-01" berisi array baris
 * { bulan, tipe, total }. Diubah jadi label + dua seri terpisah supaya
 * ChartBars tidak perlu tahu bentuk aslinya.
 */
const cashFlow = computed(() => {
    const sumber = laporan.value?.cashFlow || {}
    const kunci = Object.keys(sumber).sort()

    return {
        labels: kunci,
        pemasukan: kunci.map((k) => nilaiCashFlow(k, 'Pemasukan')),
        pengeluaran: kunci.map((k) => nilaiCashFlow(k, 'Pengeluaran')),
        harian: kunci.some((k) => String(k).length > 7),
    }
})

function nilaiCashFlow(kunci, tipe) {
    const baris = (laporan.value?.cashFlow || {})[kunci] || []
    const ketemu = Array.isArray(baris) ? baris.find((b) => b.tipe === tipe) : null

    return ketemu ? Number(ketemu.total) || 0 : 0
}

const seriCashFlow = computed(() => [
    { name: 'Pemasukan', color: '#10b981', values: cashFlow.value.pemasukan },
    { name: 'Pengeluaran', color: '#f43f5e', values: cashFlow.value.pengeluaran },
])

const kantorPerMonth = computed(() => {
    const baris = laporan.value?.pengeluaranKantorPerMonth || []

    const urut = [...baris].sort((a, b) => String(a.bulan).localeCompare(String(b.bulan)))

    return {
        labels: urut.map((b) => String(b.bulan)),
        values: urut.map((b) => Number(b.total) || 0),
    }
})

/* ============================================================================
 *  AKSI
 * ============================================================================ */

function lihatBulan() {
    if (month.value) {
        muat()
    }
}

function resetBulan() {
    month.value = ''
}

function ringkasNilai(nilai) {
    return formatRupiahRingkas(nilai)
}

function penuhNilai(nilai) {
    return formatRupiah(nilai)
}
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Kepala -->
        <div
            class="flex flex-col md:flex-row md:justify-between md:items-center mb-3 gap-2"
        >
            <div>
                <h1 class="text-xl font-bold text-slate-900">Laporan Performa Bisnis</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Ringkasan keuangan untuk pengambilan keputusan.
                </p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <span
                    class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-xs text-slate-600 shadow-sm"
                >
                    <span
                        class="material-symbols-outlined ms-icon text-[14px] text-slate-400"
                    >
                        calendar_today
                    </span>
                    {{ periodLabel }}
                </span>

                <span
                    v-if="expenseFilter !== 'all'"
                    class="flex items-center gap-1.5 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 text-xs text-amber-700 shadow-sm"
                >
                    <span
                        class="material-symbols-outlined ms-icon text-[14px] text-amber-400"
                    >
                        filter_alt
                    </span>
                    {{ expenseFilterLabel }}
                </span>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white border border-slate-200 rounded-xl px-3 py-3 shadow-sm mb-4">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                    Periode
                </span>

                <!--_period disembunyikan saat bulan dipilih: backend mengabaikan
                     filter periode kalau month terisi, jadi menampilkannya
                     hanya menyesatkan. -->
                <div
                    v-if="!adaFilterBulan"
                    class="flex bg-slate-100 rounded-lg p-0.5 gap-0.5"
                >
                    <button
                        v-for="p in OPSI_PERIOD"
                        :key="p.nilai"
                        type="button"
                        class="px-3 py-1 text-xs rounded-md font-medium transition-all"
                        :class="
                            period === p.nilai
                                ? 'bg-white text-slate-900 shadow-sm'
                                : 'text-slate-500 hover:text-slate-800'
                        "
                        @click="period = p.nilai"
                    >
                        {{ p.label }}
                    </button>
                </div>

                <span v-else class="text-[11px] text-slate-400 italic">
                    Diabaikan selama bulan dipilih
                </span>

                <div class="flex items-center gap-1.5">
                    <label for="bulanInput" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        Bulan
                    </label>

                    <input
                        id="bulanInput"
                        v-model="month"
                        type="month"
                        class="px-2 py-1 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-400"
                        @change="lihatBulan"
                    />

                    <button
                        v-if="month"
                        type="button"
                        class="px-2.5 py-1 text-xs font-medium text-slate-500 bg-slate-100 rounded-lg hover:text-slate-800 hover:bg-slate-200 transition-all"
                        @click="resetBulan"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <div class="border-t border-slate-100 my-2.5"></div>

            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                    Pengeluaran
                </span>

                <div class="flex bg-slate-100 rounded-lg p-0.5 gap-0.5">
                    <button
                        v-for="e in OPSI_EXPENSE"
                        :key="e.nilai"
                        type="button"
                        class="px-3 py-1 text-xs rounded-md font-medium transition-all"
                        :class="
                            expenseFilter === e.nilai
                                ? 'bg-white text-slate-900 shadow-sm'
                                : 'text-slate-500 hover:text-slate-800'
                        "
                        @click="expenseFilter = e.nilai"
                    >
                        {{ e.label }}
                    </button>
                </div>

                <span class="text-[10px] text-slate-400">
                    Berlaku untuk semua angka dan grafik di halaman ini
                </span>
            </div>
        </div>

        <DataState
            :loading="memuat"
            :error="error"
            :is-empty="false"
            @retry="muat"
        >
            <template v-if="laporan">
                <!-- Filter terlalu ketat -->
                <div
                    v-if="donutKosong"
                    class="pf-card px-4 py-3 mb-4 border-amber-200 bg-amber-50/70"
                >
                    <p class="text-sm text-amber-900">
                        Tidak ada pengeluaran berlabel
                        &quot;{{ expenseFilterLabel }}&quot; pada periode ini, jadi tidak ada
                        yang bisa dihitung. Coba filter lain.
                    </p>
                </div>

                <!-- Komposisi + angka utama -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-3 mb-4">
                    <div
                        class="lg:col-span-2 bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-center"
                    >
                        <div class="text-center mb-2">
                            <h2 class="text-sm font-bold text-slate-900 tracking-tight">
                                Komposisi Keuangan
                            </h2>
                            <p class="text-[11px] text-slate-500">
                                Proporsi pemasukan, pengeluaran &amp; laba
                            </p>
                        </div>

                        <ChartDonut
                            :segments="segmenDonut"
                            label-total="Selisih"
                        />
                    </div>

                    <div class="lg:col-span-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 h-full">
                            <!-- Pemasukan -->
                            <div
                                class="bg-white p-3.5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                            Total Pemasukan
                                        </p>
                                        <p
                                            class="text-sm font-bold text-emerald-600 tracking-tight mt-0.5 tabular-nums"
                                        >
                                            {{ penuhNilai(totalPemasukan) }}
                                        </p>
                                    </div>
                                    <div
                                        class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"
                                    >
                                        <span class="material-symbols-outlined ms-icon text-[16px]">
                                            trending_up
                                        </span>
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-500 mt-1">
                                    Total pemasukan {{ periodLabel.toLowerCase() }}.
                                </p>
                            </div>

                            <!-- Pengeluaran -->
                            <div
                                class="bg-white p-3.5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                            Total Pengeluaran
                                        </p>
                                        <p
                                            class="text-sm font-bold text-rose-600 tracking-tight mt-0.5 tabular-nums"
                                        >
                                            {{ penuhNilai(totalPengeluaran) }}
                                        </p>
                                    </div>
                                    <div
                                        class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0"
                                    >
                                        <span class="material-symbols-outlined ms-icon text-[16px]">
                                            trending_down
                                        </span>
                                    </div>
                                </div>

                                <p class="text-[10px] text-slate-500 mt-1 leading-4">
                                    <template v-if="expenseFilter === 'all'">
                                        Mencakup pengeluaran kantor &amp; non-kantor.
                                    </template>
                                    <template v-else-if="expenseFilter === 'kantor'">
                                        Hanya pengeluaran <strong>kantor</strong>.
                                    </template>
                                    <template v-else>
                                        Hanya pengeluaran <strong>non-kantor</strong>.
                                    </template>
                                </p>

                                <div class="mt-2 space-y-1">
                                    <div
                                        v-if="tampilKantor"
                                        class="flex items-center justify-between text-[10px]"
                                    >
                                        <span class="flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block"></span>
                                            <span class="text-slate-500">Kantor</span>
                                        </span>
                                        <span class="font-semibold text-slate-700 tabular-nums">
                                            {{ penuhNilai(pengeluaranKantor) }}
                                        </span>
                                    </div>

                                    <div
                                        v-if="tampilNonKantor"
                                        class="flex items-center justify-between text-[10px]"
                                    >
                                        <span class="flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-400 inline-block"></span>
                                            <span class="text-slate-500">Non-kantor</span>
                                        </span>
                                        <span class="font-semibold text-slate-700 tabular-nums">
                                            {{ penuhNilai(pengeluaranNonKantor) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Laba / Rugi -->
                            <div
                                class="bg-white p-3.5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                            Laba / Rugi
                                        </p>
                                        <p
                                            class="text-sm font-bold tracking-tight mt-0.5 tabular-nums"
                                            :class="labaRugi >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                                        >
                                            {{ penuhNilai(Math.abs(labaRugi)) }}
                                        </p>
                                    </div>
                                    <div
                                        class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                                        :class="
                                            labaRugi >= 0
                                                ? 'bg-emerald-50 text-emerald-600'
                                                : 'bg-rose-50 text-rose-600'
                                        "
                                    >
                                        <span class="material-symbols-outlined ms-icon text-[16px]">
                                            account_balance
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center text-[10px] mt-1">
                                    <span
                                        class="font-bold px-1.5 py-0.5 rounded mr-1"
                                        :class="
                                            labaRugi < 0
                                                ? 'bg-rose-100 text-rose-700'
                                                : 'bg-emerald-100 text-emerald-700'
                                        "
                                    >
                                        {{ labaRugi < 0 ? 'Rugi' : 'Laba' }}
                                    </span>
                                    <span class="text-slate-500">
                                        Selisih pemasukan &amp; pengeluaran
                                    </span>
                                </div>

                                <p
                                    v-if="expenseFilter !== 'all'"
                                    class="text-[9px] text-amber-600 mt-1 bg-amber-50 rounded px-1.5 py-0.5"
                                >
                                    Dihitung dari pengeluaran {{ expenseFilterLabel }} saja
                                </p>
                            </div>

                            <!-- Growth -->
                            <div
                                class="bg-white p-3.5 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                            Growth Rate
                                        </p>
                                        <p
                                            class="text-sm font-bold tracking-tight mt-0.5 tabular-nums"
                                            :class="growthRate >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                                        >
                                            {{ growthRate >= 0 ? '+' : '' }}{{ growthRate }}%
                                        </p>
                                    </div>
                                    <div
                                        class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                                        :class="
                                            growthRate >= 0
                                                ? 'bg-brand-50 text-brand-600'
                                                : 'bg-rose-50 text-rose-600'
                                        "
                                    >
                                        <span class="material-symbols-outlined ms-icon text-[16px]">
                                            {{ growthRate >= 0 ? 'arrow_upward' : 'arrow_downward' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center text-[10px] mt-1">
                                    <span
                                        class="font-bold px-1.5 py-0.5 rounded mr-1"
                                        :class="
                                            growthRate >= 0
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-rose-100 text-rose-700'
                                        "
                                    >
                                        {{ growthRate >= 0 ? 'Naik' : 'Turun' }}
                                    </span>
                                    <span class="text-slate-500">Dibanding bulan lalu</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grafik -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
                    <div class="bg-white p-3.5 rounded-xl border border-slate-100 shadow-sm">
                        <div class="mb-2">
                            <h2 class="text-xs font-bold text-slate-900 tracking-tight">
                                Cash Flow
                            </h2>
                            <p class="text-[10px] text-slate-500">
                                {{ periodLabel }}
                                <span
                                    v-if="expenseFilter !== 'all'"
                                    class="text-amber-600 font-medium"
                                >
                                    &middot; {{ expenseFilterLabel }}
                                </span>
                            </p>
                        </div>

                        <ChartBars
                            :labels="cashFlow.labels"
                            :series="seriCashFlow"
                            mode="bar"
                        />
                    </div>

                    <div class="bg-white p-3.5 rounded-xl border border-slate-100 shadow-sm">
                        <div class="mb-2">
                            <h2 class="text-xs font-bold text-slate-900 tracking-tight">
                                Pengeluaran Kantor per Bulan
                            </h2>
                            <p class="text-[10px] text-slate-500">
                                Biaya operasional kantor bulanan
                            </p>
                        </div>

                        <ChartBars
                            :labels="kantorPerMonth.labels"
                            :series="[
                                { name: 'Kantor', color: '#f43f5e', values: kantorPerMonth.values },
                            ]"
                            mode="line"
                        />
                    </div>
                </div>

                <!-- Ringkasan akhir -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                    <div
                        class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between"
                    >
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                Profit Margin
                            </p>
                            <p class="text-lg font-bold text-slate-900 tracking-tight mt-0.5 tabular-nums">
                                {{ profitMargin }}%
                            </p>
                            <p class="text-[10px] text-slate-500 mt-0.5">
                                Persentase laba terhadap pemasukan
                            </p>
                            <p
                                v-if="expenseFilter !== 'all'"
                                class="text-[9px] text-amber-600 mt-0.5"
                            >
                                Dihitung dari {{ expenseFilterLabel }} saja
                            </p>
                        </div>
                        <div
                            class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0"
                        >
                            <span class="material-symbols-outlined ms-icon text-[18px]">pie_chart</span>
                        </div>
                    </div>

                    <div
                        class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between"
                    >
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                Laba Bersih
                            </p>
                            <p
                                class="text-lg font-bold tracking-tight mt-0.5 tabular-nums"
                                :class="labaBersih >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                            >
                                {{ penuhNilai(labaBersih) }}
                            </p>
                            <p class="text-[10px] text-slate-500 mt-0.5">
                                Pemasukan dikurangi pengeluaran kantor
                            </p>
                            <p
                                v-if="labaBersih < 0"
                                class="text-[9px] text-rose-600 mt-0.5 bg-rose-50 rounded px-1.5 py-0.5"
                            >
                                Rugi bersih setelah biaya kantor
                            </p>
                        </div>
                        <div
                            class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"
                        >
                            <span class="material-symbols-outlined ms-icon text-[18px]">
                                account_balance
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between"
                    >
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                Cash Runway
                            </p>
                            <p class="text-lg font-bold text-slate-900 tracking-tight mt-0.5 tabular-nums">
                                {{ cashRunway }} hari
                            </p>
                            <p class="text-[10px] text-slate-500 mt-0.5">
                                Burn rate {{ ringkasNilai(burnRate) }} / bulan
                            </p>
                            <p
                                v-if="expenseFilter !== 'all'"
                                class="text-[9px] text-amber-600 mt-0.5"
                            >
                                Dihitung dari {{ expenseFilterLabel }} saja
                            </p>
                        </div>
                        <div
                            class="w-9 h-9 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0"
                        >
                            <span class="material-symbols-outlined ms-icon text-[18px]">timer</span>
                        </div>
                    </div>
                </div>
            </template>
        </DataState>
    </div>
</template>
