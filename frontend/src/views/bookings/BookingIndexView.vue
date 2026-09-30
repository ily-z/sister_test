<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DataState from '@/components/DataState.vue'
import Pagination from '@/components/Pagination.vue'
import StatusBadge from '@/components/StatusBadge.vue'

import { api } from '@/lib/api'
import { useResource } from '@/composables/useResource'
import { formatRupiah, formatTanggalPendek } from '@/lib/format'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  DAFTAR PESANAN
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Filter di query string, dengan alasan yang sama seperti di halaman
 *  armada: tautan halaman harus bisa dibagikan, dan tombol "kembali" peramban
 *  harus mengembalikan keadaan yang sama.
 */

const route = useRoute()
const router = useRouter()

const searchInput = ref(String(route.query.search || ''))

const STATUS_PEMBAYARAN = [
    'Belum Bayar',
    'DP',
    'Belum Lunas',
    'Lunas',
    'Refund Pending',
    'Refunded',
]

const filters = reactive({
    search: String(route.query.search || ''),
    status_pembayaran: String(route.query.status_pembayaran || ''),
    tipe_sewa: String(route.query.tipe_sewa || ''),
    page: Number(route.query.page || 1),
})

const bookings = useResource(() =>
    api.get('/bookings', { params: cleanParams({ ...filters, per_page: 10 }) })
)

const list = computed(() => bookings.data?.bookings?.data || [])
const total = computed(() => bookings.data?.bookings?.total || 0)

function cleanParams(params) {
    const result = {}

    for (const [key, value] of Object.entries(params)) {
        if (value === '' || value === null || value === undefined) continue
        result[key] = value
    }

    return result
}

function applyFilters() {
    filters.search = searchInput.value.trim()
    filters.page = 1
    syncUrl()
}

function clearFilters() {
    searchInput.value = ''
    filters.search = ''
    filters.status_pembayaran = ''
    filters.tipe_sewa = ''
    filters.page = 1
    syncUrl()
}

function syncUrl() {
    router.push({ query: cleanParams(filters) })
}

function goToPage(page) {
    filters.page = page
    syncUrl()
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const adaFilter = computed(
    () => Boolean(filters.search) || Boolean(filters.status_pembayaran) || Boolean(filters.tipe_sewa)
)

function carsOf(booking) {
    return (booking.booking_cars || []).map((bc) => bc.car).filter(Boolean)
}

function ringkasMobil(booking) {
    const cars = carsOf(booking)

    if (!cars.length) return '-'

    const pertama = cars[0]
    const nama = `${pertama.merk || ''} ${pertama.model || ''}`.trim() || pertama.plat_nomor

    return cars.length > 1 ? `${nama} +${cars.length - 1}` : nama
}

watch(() => JSON.stringify(filters), () => bookings.load())
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <div class="flex flex-col md:flex-row md:justify-between md:items-end mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Penyewaan</h1>
                <p class="text-sm text-slate-500 mt-1">Semua pesanan sewa kendaraan.</p>
            </div>

            <RouterLink :to="{ name: 'bookings.create' }" class="btn-primary shrink-0">
                <span class="material-symbols-outlined ms-icon text-[18px]">add</span>
                Buat Pesanan
            </RouterLink>
        </div>

        <!-- Filter -->
        <div class="pf-card p-4 mb-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="lg:col-span-2">
                    <label class="sr-only" for="cari-pesanan">Cari pesanan</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none"
                        >
                            search
                        </span>
                        <input
                            id="cari-pesanan"
                            v-model="searchInput"
                            type="search"
                            class="form-input pl-9"
                            placeholder="Nomor pesanan, nama, atau kontak..."
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>

                <div>
                    <label class="sr-only" for="filter-pembayaran">Status pembayaran</label>
                    <select
                        id="filter-pembayaran"
                        v-model="filters.status_pembayaran"
                        class="form-input"
                        @change="filters.page = 1; syncUrl()"
                    >
                        <option value="">Semua status pembayaran</option>
                        <option v-for="s in STATUS_PEMBAYARAN" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <select
                        v-model="filters.tipe_sewa"
                        class="form-input min-w-0"
                        aria-label="Tipe sewa"
                        @change="filters.page = 1; syncUrl()"
                    >
                        <option value="">Semua tipe</option>
                        <option value="harian">Harian</option>
                        <option value="kontrak">Kontrak</option>
                    </select>

                    <button
                        type="button"
                        class="btn-secondary px-3 shrink-0"
                        :disabled="!adaFilter"
                        title="Hapus semua filter"
                        @click="clearFilters"
                    >
                        <span class="material-symbols-outlined ms-icon text-[18px]">filter_alt_off</span>
                    </button>
                </div>
            </div>
        </div>

        <DataState
            :loading="bookings.loading"
            :error="bookings.error"
            :is-empty="list.length === 0"
            empty-icon="event_note"
            :empty-title="adaFilter ? 'Tidak ada pesanan yang cocok' : 'Belum ada pesanan'"
            :empty-description="
                adaFilter
                    ? 'Coba ubah kata kunci atau status pembayaran yang dipilih.'
                    : 'Pesanan yang dibuat akan muncul di daftar ini.'
            "
            @retry="bookings.load()"
        >
            <template #empty-action>
                <RouterLink v-if="!adaFilter" :to="{ name: 'bookings.create' }" class="btn-primary">
                    <span class="material-symbols-outlined ms-icon text-[18px]">add</span>
                    Buat Pesanan
                </RouterLink>
                <button v-else type="button" class="btn-secondary" @click="clearFilters">
                    Hapus semua filter
                </button>
            </template>

            <div class="pf-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[52rem]">
                        <thead>
                            <tr class="text-left text-xs text-slate-400 border-b border-slate-100 bg-slate-50/60">
                                <th class="px-5 py-3 font-semibold">No. Pesanan</th>
                                <th class="px-5 py-3 font-semibold">Pelanggan</th>
                                <th class="px-5 py-3 font-semibold">Kendaraan</th>
                                <th class="px-5 py-3 font-semibold">Periode</th>
                                <th class="px-5 py-3 font-semibold text-right">Total</th>
                                <th class="px-5 py-3 font-semibold">Pembayaran</th>
                                <th class="px-5 py-3 font-semibold">Rental</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-50">
                            <tr
                                v-for="booking in list"
                                :key="booking.id"
                                class="hover:bg-slate-50 transition-colors"
                            >
                                <td class="px-5 py-3.5">
                                    <RouterLink
                                        :to="{ name: 'bookings.show', params: { id: booking.id } }"
                                        class="table-link whitespace-nowrap"
                                    >
                                        {{ booking.nomor_pesanan }}
                                    </RouterLink>
                                </td>

                                <td class="px-5 py-3.5">
                                    <p class="text-slate-800 font-medium truncate max-w-[12rem]">
                                        {{ booking.nama_pelanggan }}
                                    </p>
                                    <p class="text-xs text-slate-400">{{ booking.kontak_pelanggan }}</p>
                                </td>

                                <td class="px-5 py-3.5 text-slate-600 max-w-[10rem] truncate">
                                    {{ ringkasMobil(booking) }}
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

                                <td class="px-5 py-3.5">
                                    <StatusBadge :label="booking.status_rental" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    :current-page="filters.page"
                    :total="total"
                    :per-page="10"
                    @change="goToPage"
                />
            </div>
        </DataState>
    </div>
</template>