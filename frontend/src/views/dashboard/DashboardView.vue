<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import DataState from '@/components/DataState.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { api } from '@/lib/api'
import { useResource } from '@/composables/useResource'
import {
    formatRupiah,
    formatRupiahRingkas,
    formatTanggalPendek,
} from '@/lib/format'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  DASHBOARD
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Halaman pertama setiap role, jadi tidak punya permission. Kartu
 *  financials yang muncul di sini sengaja dibatasi pada angka yang benar-benar
 *  berasal dari database.
 *
 *  ── YANG SENGAJA TIDAK DITAMPILKAN ──────────────────────────────────────────
 *
 *  Endpoint /api/dashboard juga mengembalikan `analyticsData`, dan isinya
 *  MASIH DATA PALSU yang ditulis langsung di controller: total pendapatan
 *  125 juta, 156 booking, "Toyota Innova" 85% terpakai, dan seterusnya.
 *  Angka-angka itu tidak berasal dari tabel mana pun.
 *
 *  Field itu tidak dirender di sini, walaupun backend mengirimkannya,
 *  karena dashboard adalah halaman yang paling dipercaya penggunanya.
 *  Menampilkan pendapatan Rp 125 juta yang tidak ada di database akan membuat
 *  keputusan diambil berdasarkan angka palsu -- dan lebih buruk lagi, tidak
 *  ada satu pun tanda bahwa angka itu palsu.
 *
 *  Di backend, method getAnalyticsData() sudah diberi komentar bahwa ini
 *  pekerjaan yang belum selesai. Lihat docs/KNOWN_ISSUES.md.
 */

const router = useRouter()

const dashboard = useResource(() => api.get('/dashboard'))

const stats = computed(() => dashboard.data?.stats || {})
const revenueChart = computed(() => dashboard.data?.revenueChart || [])
const latestBookings = computed(() => dashboard.data?.latestBookings || [])
const serviceAlerts = computed(() => dashboard.data?.serviceAlerts || [])

/*
 * Kartu statistik.
 *
 * `tone` menentukan warna ikon. Merah hanya dipakai untuk "Masuk Bengkel",
 * karena itu satu-satunya angka di kartu ini yang berarti ada pekerjaan yang
 * tertunda dan perlu ditindak.
 */
const CARDS = computed(() => [
    {
        key: 'totalMobil',
        label: 'Total Kendaraan',
        nilai: stats.value.totalMobil ?? 0,
        icon: 'directions_car',
        tone: 'bg-brand-50 text-brand-600',
        to: { name: 'armada.index' },
    },
    {
        key: 'tersedia',
        label: 'Tersedia',
        nilai: stats.value.tersedia ?? 0,
        icon: 'check_circle',
        tone: 'bg-emerald-50 text-emerald-600',
        to: { name: 'armada.index', query: { status: 'Tersedia' } },
    },
    {
        key: 'jumlahDisewa',
        label: 'Sedang Disewa',
        nilai: stats.value.jumlahDisewa ?? 0,
        icon: 'event_note',
        tone: 'bg-violet-50 text-violet-600',
        to: { name: 'armada.index', query: { status: 'Sedang Disewa' } },
    },
    {
        key: 'bengkel',
        label: 'Masuk Bengkel',
        nilai: stats.value.bengkel ?? 0,
        icon: 'build',
        tone: 'bg-orange-50 text-orange-600',
        to: { name: 'armada.bengkel' },
    },
])

/* Pendapatan tertinggi, dipakai untuk menghitung tinggi batang grafik. */
const maxRevenue = computed(() =>
    Math.max(1, ...revenueChart.value.map((row) => Number(row.total) || 0))
)

const adaRevenue = computed(() => revenueChart.value.some((row) => Number(row.total) > 0))

function bukaBooking(booking) {
    router.push({ name: 'bookings.show', params: { id: booking.id } })
}
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Judul -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-end mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
                <p class="text-sm text-slate-500 mt-1">Ringkasan operasional armada dan penyewaan.</p>
            </div>
        </div>

        <DataState
            :loading="dashboard.loading"
            :error="dashboard.error"
            :is-empty="false"
            @retry="dashboard.load()"
        >
            <!-- Kartu statistik -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <component
                    :is="card.to ? 'RouterLink' : 'div'"
                    v-for="card in CARDS"
                    :key="card.key"
                    :to="card.to"
                    class="pf-card p-4 sm:p-6 flex items-start gap-4 transition-shadow hover:shadow-md"
                >
                    <div
                        class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                        :class="card.tone"
                    >
                        <span class="material-symbols-outlined ms-icon text-[22px]">
                            {{ card.icon }}
                        </span>
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            {{ card.label }}
                        </p>
                        <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">
                            {{ card.nilai }}
                        </p>
                    </div>
                </component>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Pendapatan -->
                <div class="pf-card p-5 lg:col-span-2">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                            Pendapatan Penyewaan
                        </h2>
                        <span class="text-xs text-slate-400">Januari - Juni</span>
                    </div>

                    <div
                        v-if="!adaRevenue"
                        class="text-center py-10 text-slate-400 text-sm"
                    >
                        Belum ada transaksi pada tahun ini.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="row in revenueChart"
                            :key="row.label"
                            class="flex items-center gap-3"
                        >
                            <span class="w-10 text-xs font-semibold text-slate-500 shrink-0">
                                {{ row.label }}
                            </span>

                            <div class="flex-1 h-7 bg-slate-50 rounded-md overflow-hidden min-w-0">
                                <div
                                    class="h-full bg-brand-500/85 rounded-md transition-[width] duration-500"
                                    :style="{ width: Math.max(2, row.pct) + '%' }"
                                />
                            </div>

                            <span class="w-28 text-right text-xs font-semibold text-slate-600 tabular-nums shrink-0">
                                {{ formatRupiahRingkas(row.total) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Peringatan servis -->
                <div class="pf-card p-5">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                        Peringatan Servis
                    </h2>

                    <p v-if="!serviceAlerts.length" class="text-sm text-slate-400 py-6 text-center">
                        Tidak ada komponen yang nearing batas servis.
                    </p>

                    <ul v-else class="space-y-3">
                        <li
                            v-for="(alert, i) in serviceAlerts"
                            :key="`${alert.name}-${i}`"
                            class="flex items-start gap-3"
                        >
                            <span
                                class="material-symbols-outlined ms-icon text-[18px] shrink-0 mt-px"
                                :class="
                                    alert.level === 'critical'
                                        ? 'text-rose-500'
                                        : 'text-amber-500'
                                "
                            >
                                {{ alert.level === 'critical' ? 'error' : 'warning' }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-700 truncate">
                                    {{ alert.name }}
                                </p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ alert.level === 'critical' ? 'Melampaui' : 'Tersisa' }}
                                    {{ formatRupiahRingkas(alert.diff).replace('Rp ', '') }} km
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Pesanan terbaru -->
            <div class="pf-card overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                        Pesanan Terbaru
                    </h2>

                    <RouterLink
                        :to="{ name: 'bookings.index' }"
                        class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition-colors"
                    >
                        Lihat semua
                    </RouterLink>
                </div>

                <p
                    v-if="!latestBookings.length"
                    class="text-sm text-slate-400 py-10 text-center"
                >
                    Belum ada pesanan yang tercatat.
                </p>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[42rem]">
                        <thead>
                            <tr class="text-left text-xs text-slate-400 border-b border-slate-100">
                                <th class="px-5 py-3 font-semibold">No. Pesanan</th>
                                <th class="px-5 py-3 font-semibold">Pelanggan</th>
                                <th class="px-5 py-3 font-semibold">Periode</th>
                                <th class="px-5 py-3 font-semibold text-right">Total</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-50">
                            <tr
                                v-for="booking in latestBookings"
                                :key="booking.id"
                                class="hover:bg-slate-50 cursor-pointer transition-colors"
                                @click="bukaBooking(booking)"
                            >
                                <td class="px-5 py-3.5 font-semibold text-brand-600 whitespace-nowrap">
                                    {{ booking.nomor_pesanan }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-700">
                                    {{ booking.nama_pelanggan }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-500 text-xs whitespace-nowrap">
                                    {{ formatTanggalPendek(booking.tanggal_mulai) }}
                                    &ndash;
                                    {{ formatTanggalPendek(booking.tanggal_selesai) }}
                                </td>
                                <td class="px-5 py-3.5 text-right font-semibold text-slate-800 tabular-nums whitespace-nowrap">
                                    {{ formatRupiah(booking.total_harga) }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <StatusBadge :label="booking.status_pembayaran" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </DataState>
    </div>
</template>