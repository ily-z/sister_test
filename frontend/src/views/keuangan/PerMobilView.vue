<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DataState from '@/components/DataState.vue'
import EmptyState from '@/components/EmptyState.vue'

import { api } from '@/lib/api'
import { formatBulanTahun, formatRupiah } from '@/lib/format'

/*
 * ============================================================================
 *  KEUANGAN PER MOBIL
 * ============================================================================
 *
 *  Meniru backend/resources/views/keuangan/per-mobil.blade.php.
 *
 *  --- BULAN DI NAVIGASIKAN DI SINI, BUKAN DI HEADER ------------------------
 *
 *  Endpoint mengembalikan month, prevMonth, nextMonth, dan canGoNext. Semua
 *  dipakai apa adanya: input bulan bawaan tidak dipakai karena nilainya bisa
 *  berbeda dari canGoNext. Backend membolehkan lompat ke bulan mana pun,
 *  sedangkan halaman ini memakai prev/next supaya bulan kosong tidak
 *  muncul tanpa disengaja.
 *
 *  canGoNext bernilai true kalau month yang sedang tampil masih di bulan
 *  sekarang atau sebelumnya. Month depan yang belum tiba tidak ditampilkan
 *  sebagai tombol, bukan ditampilkan lalu ditolak server.
 */

const route = useRoute()
const router = useRouter()

const month = ref(String(route.query.month || bulananSekarang()))
const memuat = ref(true)
const error = ref(null)
const laporan = ref(null)

/*
 * Bulan berjalan dihitung dari waktu lokal peramban, bukan dari tanggal
 * server. Selisih beberapa menit tidak berarti apa-apa untuk memilih bulan
 * laporan, dan cara ini tidak butuh satu request tambahan.
 */
function bulananSekarang() {
    const d = new Date()

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

async function muat() {
    memuat.value = true
    error.value = null

    try {
        const { data } = await api.get('/keuangan/per-mobil/data', {
            params: { month: month.value },
        })

        laporan.value = data

        /* Backend bisa mengembalikan month yang berbeda kalau yang diminta
         * tidak valid. Ikuti nilai server supaya judul dan isi tidak
         * berbeda, jadi judul dan isi tabel tetap sinkron. */
        if (data.month && data.month !== month.value) {
            month.value = data.month
        }
    } catch (e) {
        error.value =
            e?.response?.data?.message || 'Gagal memuat laporan keuangan per mobil.'
    } finally {
        memuat.value = false
    }
}

watch(month, (nilai) => {
    router.replace({ query: { month: nilai } })
    muat()
})

watch(
    () => route.query.month,
    (nilai) => {
        if (nilai && String(nilai) !== month.value) month.value = String(nilai)
    }
)

/* ============================================================================
 *  NILAI
 * ============================================================================ */

const report = computed(() => laporan.value?.report || [])
const bisaMajukan = computed(() => Boolean(laporan.value?.canGoNext))
const bulanSebelumnya = computed(() => laporan.value?.prevMonth || null)
const bulanDepan = computed(() => laporan.value?.nextMonth || null)

const monthLabel = computed(() => formatBulanTahun(month.value))

/* Urut dari laba terbesar, supaya mobil yang paling profitable langsung
 * terlihat tanpa perlu digulir. Yang rugi tetap tampil, hanya di bawah. */
const urut = computed(() =>
    [...report.value].sort((a, b) => {
        if (b.laba !== a.laba) return b.laba - a.laba

        return String(a.plat_nomor).localeCompare(String(b.plat_nomor))
    })
)

const totalPemasukan = computed(() =>
    report.value.reduce((t, r) => t + (Number(r.pemasukan) || 0), 0)
)
const totalPengeluaran = computed(() =>
    report.value.reduce((t, r) => t + (Number(r.pengeluaran) || 0), 0)
)
const totalLaba = computed(() => totalPemasukan.value - totalPengeluaran.value)

/* Mobil rugi ditandai. Ambangnya nol, bukan persentase: laporan ini dipakai
 * untuk memutuskan mobil mana yang perlu diturunkan, jadi semua yang laba
 * di bawah nol wajib terlihat. */
const mobilRugi = computed(() => report.value.filter((r) => Number(r.laba) < 0).length)

function keBulanLalu() {
    if (bulanSebelumnya.value) month.value = bulanSebelumnya.value
}

function keBulanDepan() {
    if (bulanDepan.value && bisaMajukan.value) {
        month.value = bulanDepan.value
    }
}

muat()
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Kepala -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Keuangan per Mobil</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Pemasukan dan pengeluaran yang dikaitkan ke satu kendaraan.
                </p>
            </div>

            <!-- Navigasi bulan -->
            <div class="flex items-center gap-1 shrink-0">
                <button
                    type="button"
                    class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-brand-600 hover:border-brand-300 transition-colors flex items-center justify-center disabled:opacity-40 disabled:hover:text-slate-500 disabled:hover:border-slate-200"
                    :disabled="!bulanSebelumnya"
                    aria-label="Bulan sebelumnya"
                    @click="keBulanLalu"
                >
                    <span class="material-symbols-outlined ms-icon text-[18px]">chevron_left</span>
                </button>

                <span
                    class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 min-w-[130px] text-center"
                >
                    {{ monthLabel }}
                </span>

                <button
                    type="button"
                    class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-brand-600 hover:border-brand-300 transition-colors flex items-center justify-center disabled:opacity-40 disabled:hover:text-slate-500 disabled:hover:border-slate-200"
                    :disabled="!bisaMajukan"
                    aria-label="Bulan berikutnya"
                    @click="keBulanDepan"
                >
                    <span class="material-symbols-outlined ms-icon text-[18px]">chevron_right</span>
                </button>
            </div>
        </div>

        <DataState
            :loading="memuat"
            :error="error"
            :is-empty="false"
            @retry="muat"
        >
            <template v-if="laporan">
                <!-- Ringkasan -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                    <div class="pf-card p-3.5">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Total Pemasukan
                        </p>
                        <p class="text-base font-bold text-emerald-600 mt-1 tabular-nums">
                            {{ formatRupiah(totalPemasukan) }}
                        </p>
                    </div>

                    <div class="pf-card p-3.5">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Total Pengeluaran
                        </p>
                        <p class="text-base font-bold text-rose-600 mt-1 tabular-nums">
                            {{ formatRupiah(totalPengeluaran) }}
                        </p>
                    </div>

                    <div class="pf-card p-3.5">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Total Laba
                        </p>
                        <p
                            class="text-base font-bold mt-1 tabular-nums"
                            :class="totalLaba >= 0 ? 'text-brand-600' : 'text-rose-600'"
                        >
                            {{ formatRupiah(totalLaba) }}
                        </p>
                    </div>

                    <div class="pf-card p-3.5">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Kendaraan Rugi
                        </p>
                        <p class="text-base font-bold text-slate-800 mt-1 tabular-nums">
                            {{ mobilRugi }}
                        </p>
                    </div>
                </div>

                <!-- Peringatan rugi -->
                <div
                    v-if="mobilRugi > 0"
                    class="pf-card px-4 py-3 mb-4 border-amber-200 bg-amber-50/70 flex items-start gap-3"
                >
                    <span
                        class="material-symbols-outlined ms-icon text-amber-600 text-[20px] shrink-0 mt-0.5"
                    >
                        warning
                    </span>
                    <p class="text-sm text-amber-900">
                        {{ mobilRugi }} kendaraan tercatat rugi pada {{ monthLabel }}. Pengeluaran
                        dihitung dari transaksi yang punya car_id, jadi mobil yang tidak punya
                        catatan biaya sama sekali tidak muncul di daftar ini.
                    </p>
                </div>

                <!-- Tabel -->
                <section class="pf-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[640px]">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th
                                        class="px-5 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider"
                                    >
                                        Kendaraan
                                    </th>
                                    <th
                                        class="px-5 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider"
                                    >
                                        Pemasukan
                                    </th>
                                    <th
                                        class="px-5 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider"
                                    >
                                        Pengeluaran
                                    </th>
                                    <th
                                        class="px-5 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider"
                                    >
                                        Laba
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-50">
                                <tr
                                    v-for="r in urut"
                                    :key="r.car_id"
                                    class="hover:bg-slate-50/60 transition-colors"
                                >
                                    <td class="px-5 py-3">
                                        <RouterLink
                                            :to="r.detail_path"
                                            class="font-semibold text-slate-800 hover:text-brand-600 transition-colors"
                                        >
                                            {{ r.nama_mobil }}
                                        </RouterLink>
                                        <p class="text-xs text-slate-500 font-mono">
                                            {{ r.plat_nomor }}
                                        </p>
                                    </td>

                                    <td
                                        class="px-5 py-3 text-right text-emerald-600 tabular-nums"
                                    >
                                        {{ formatRupiah(r.pemasukan) }}
                                    </td>

                                    <td
                                        class="px-5 py-3 text-right text-rose-600 tabular-nums"
                                    >
                                        {{ formatRupiah(r.pengeluaran) }}
                                    </td>

                                    <td
                                        class="px-5 py-3 text-right font-semibold tabular-nums"
                                        :class="Number(r.laba) >= 0 ? 'text-slate-800' : 'text-rose-600'"
                                    >
                                        {{ formatRupiah(r.laba) }}
                                    </td>
                                </tr>
                            </tbody>

                            <tfoot
                                v-if="report.length"
                                class="border-t border-slate-200 bg-slate-50"
                            >
                                <tr>
                                    <td
                                        class="px-5 py-3 text-xs font-bold text-slate-600 uppercase tracking-wider"
                                    >
                                        Total ({{ report.length }} kendaraan)
                                    </td>
                                    <td
                                        class="px-5 py-3 text-right font-bold text-emerald-600 tabular-nums"
                                    >
                                        {{ formatRupiah(totalPemasukan) }}
                                    </td>
                                    <td
                                        class="px-5 py-3 text-right font-bold text-rose-600 tabular-nums"
                                    >
                                        {{ formatRupiah(totalPengeluaran) }}
                                    </td>
                                    <td
                                        class="px-5 py-3 text-right font-bold tabular-nums"
                                        :class="totalLaba >= 0 ? 'text-brand-600' : 'text-rose-600'"
                                    >
                                        {{ formatRupiah(totalLaba) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <EmptyState
                        v-if="!report.length"
                        icon="directions_car"
                        title="Tidak ada transaksi di bulan ini"
                        :description="`Belum ada transaksi yang dikaitkan ke kendaraan pada ${monthLabel}.`"
                    />
                </section>
            </template>
        </DataState>
    </div>
</template>
