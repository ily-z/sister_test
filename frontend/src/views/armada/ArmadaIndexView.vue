<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import CarPhoto from '@/components/CarPhoto.vue'
import Pagination from '@/components/Pagination.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import DataState from '@/components/DataState.vue'

import { api } from '@/lib/api'
import { useResource } from '@/composables/useResource'
import { formatAngka, formatRupiah } from '@/lib/format'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  DAFTAR KENDARAAN
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Filter disimpan di query string, bukan di state komponen.
 *
 *  Alasannya bukan sekadar supaya URL bisa dibagikan: filter di sini dibaca
 *  Dashboard lewat query yang sama ("Tersedia", "Sedang Disewa"), jadi format
 *  query HARUS sama dengan yang dikirim dashboard. Kalau halaman ini membaca
 *  state lokal, klik kartu statistik di dashboard akan mendarat di halaman
 *  yang belum terfilter sama sekali.
 */

const route = useRoute()
const router = useRouter()

const searchInput = ref(String(route.query.search || ''))

const filters = reactive({
    search: String(route.query.search || ''),
    status: String(route.query.status || ''),
    merk: String(route.query.merk || ''),
    tahun: String(route.query.tahun || ''),
    servis_due: String(route.query.servis_due || ''),
    page: Number(route.query.page || 1),
})

const armada = useResource(() =>
    api.get('/armada', { params: cleanParams({ ...filters, per_page: 9 }) })
)

const cars = computed(() => armada.data?.cars?.data || [])
const total = computed(() => armada.data?.cars?.total || 0)
const filterOptions = computed(() => armada.data?.filterOptions || {})

/*
 * Buang parameter kosong sebelum dikirim.
 *
 * Tanpa ini, URL jadi ?search=&status=&merk=... setiap kali difilter. Itu
 * bukan hanya soal kebersihan: router memakai perbedaan query untuk
 * memutuskan whether memuat ulang komponen, jadi string kosong yang ikut
 * berubah bisa memicu navigasi yang tidak perlu.
 */
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
    filters.status = ''
    filters.merk = ''
    filters.tahun = ''
    filters.servis_due = ''
    filters.page = 1
    syncUrl()
}

/*
 * Tulis filter ke query string, lalu biarkan router yang memanggil
 * load() lewat watch di bawah.
 *
 * Satu sumber kebenaran: query string. Kalau syncUrl sekaligus memanggil
 * load() secara langsung, akan ada dua request untuk perubahan yang sama.
 */
function syncUrl() {
    router.push({ query: cleanParams(filters) })
}

function goToPage(page) {
    filters.page = page
    syncUrl()
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const adaFilter = computed(
    () =>
        Boolean(filters.search) ||
        Boolean(filters.status) ||
        Boolean(filters.merk) ||
        Boolean(filters.tahun) ||
        Boolean(filters.servis_due)
)

const STATISTIK = computed(() => [
    { label: 'Total', nilai: armada.data?.total ?? 0, aktif: !filters.status },
    { label: 'Tersedia', nilai: armada.data?.tersedia ?? 0, status: 'Tersedia' },
    { label: 'Disewa', nilai: armada.data?.disewa ?? 0, status: 'Sedang Disewa' },
    { label: 'Bengkel', nilai: armada.data?.bengkel ?? 0, status: 'Masuk Bengkel' },
    { label: 'Nonaktif', nilai: armada.data?.nonaktif ?? 0, status: 'Nonaktif' },
])

function pilihStatus(status) {
    filters.status = filters.status === status ? '' : (status || '')
    filters.page = 1
    syncUrl()
}

/*
 * Satu watch untuk semua perubahan filter.
 *
 * Dipisah per-field akan menghasilkan beberapa request kalau pengguna
 * mengubah beberapa filter sekaligus. Itu tidak menjadi masalah di sini:
 * request-nya ringan, dan backend sudah memakai withQueryString() supaya
 * paginasi tidak ikut hilang.
 */
watch(
    () => JSON.stringify(filters),
    () => armada.load()
)
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Judul -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-end mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Manajemen Armada</h1>
                <p class="text-sm text-slate-500 mt-1">Daftar kendaraan yang tersedia untuk disewakan.</p>
            </div>

            <RouterLink :to="{ name: 'armada.create' }" class="btn-primary shrink-0">
                <span class="material-symbols-outlined ms-icon text-[18px]">add</span>
                Tambah Mobil
            </RouterLink>
        </div>

        <!-- Statistik (dapat diklik sebagai filter) -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
            <button
                v-for="item in STATISTIK"
                :key="item.label"
                type="button"
                class="pf-card px-4 py-3 text-left transition-all"
                :class="
                    item.aktif || filters.status === item.status
                        ? 'ring-2 ring-brand-500 border-brand-200'
                        : 'hover:shadow-md'
                "
                @click="pilihStatus(item.status || '')"
            >
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">
                    {{ item.label }}
                </p>
                <p class="text-xl font-bold text-slate-900 mt-0.5 tabular-nums">
                    {{ item.nilai }}
                </p>
            </button>
        </div>

        <!-- Filter -->
        <div class="pf-card p-4 mb-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div class="lg:col-span-2">
                    <label class="sr-only" for="cari-kendaraan">Cari kendaraan</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none"
                        >
                            search
                        </span>
                        <input
                            id="cari-kendaraan"
                            v-model="searchInput"
                            type="search"
                            class="form-input pl-9"
                            placeholder="Plat nomor, merk, atau model..."
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>

                <div>
                    <label class="sr-only" for="filter-status">Status</label>
                    <select
                        id="filter-status"
                        v-model="filters.status"
                        class="form-input"
                        @change="filters.page = 1; syncUrl()"
                    >
                        <option value="">Semua status</option>
                        <option v-for="s in filterOptions.status || []" :key="s" :value="s">
                            {{ s }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="sr-only" for="filter-merk">Merk</label>
                    <select
                        id="filter-merk"
                        v-model="filters.merk"
                        class="form-input"
                        @change="filters.page = 1; syncUrl()"
                    >
                        <option value="">Semua merk</option>
                        <option v-for="m in filterOptions.merk || []" :key="m" :value="m">
                            {{ m }}
                        </option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <select
                        v-model="filters.tahun"
                        class="form-input min-w-0"
                        aria-label="Tahun"
                        @change="filters.page = 1; syncUrl()"
                    >
                        <option value="">Semua tahun</option>
                        <option v-for="t in filterOptions.tahun || []" :key="t" :value="t">
                            {{ t }}
                        </option>
                    </select>

                    <button
                        type="button"
                        class="btn-secondary px-3 shrink-0"
                        :disabled="!adaFilter"
                        :title="adaFilter ? 'Hapus semua filter' : 'Tidak ada filter aktif'"
                        @click="clearFilters"
                    >
                        <span class="material-symbols-outlined ms-icon text-[18px]">filter_alt_off</span>
                    </button>
                </div>
            </div>

            <!-- Filter servis jatuh tempo -->
            <label class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100 cursor-pointer select-none">
                <input
                    v-model="filters.servis_due"
                    type="checkbox"
                    true-value="1"
                    false-value=""
                    class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                    @change="filters.page = 1; syncUrl()"
                />
                <span class="text-sm text-slate-600">Hanya yang jatuh tempo servis</span>
            </label>
        </div>

        <!-- Kartu kendaraan -->
        <DataState
            :loading="armada.loading"
            :error="armada.error"
            :is-empty="cars.length === 0"
            empty-icon="directions_car"
            :empty-title="adaFilter ? 'Tidak ada kendaraan yang cocok' : 'Belum ada kendaraan'"
            :empty-description="
                adaFilter
                    ? 'Coba longgarkan filter, atau hapus semua filter untuk melihat seluruh armada.'
                    : 'Tambahkan kendaraan pertama untuk mulai menerima pesanan sewa.'
            "
            @retry="armada.load()"
        >
            <template #empty-action>
                <RouterLink v-if="!adaFilter" :to="{ name: 'armada.create' }" class="btn-primary">
                    <span class="material-symbols-outlined ms-icon text-[18px]">add</span>
                    Tambah Mobil
                </RouterLink>
                <button v-else type="button" class="btn-secondary" @click="clearFilters">
                    <span class="material-symbols-outlined ms-icon text-[18px]">filter_alt_off</span>
                    Hapus semua filter
                </button>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <RouterLink
                    v-for="car in cars"
                    :key="car.id"
                    :to="{ name: 'armada.show', params: { id: car.id } }"
                    class="pf-card overflow-hidden hover:shadow-md transition-shadow group"
                >
                    <!-- Foto -->
                    <div class="h-44 overflow-hidden bg-slate-100 relative">
                        <CarPhoto :url="car.foto_url" :alt="`${car.merk} ${car.model}`" />

                        <div class="absolute top-3 left-3">
                            <StatusBadge :label="car.status" />
                        </div>
                    </div>

                    <!-- Isi -->
                    <div class="p-4">
                        <h3
                            class="font-semibold text-slate-800 truncate group-hover:text-brand-700 transition-colors"
                        >
                            {{ car.merk }} {{ car.model }}
                        </h3>

                        <p class="text-sm text-slate-500 mt-0.5 tabular-nums">{{ car.plat_nomor }}</p>

                        <dl class="mt-3 pt-3 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <dt class="text-slate-400">Kapasitas</dt>
                                <dd class="font-semibold text-slate-700">{{ car.kapasitas }} orang</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Odometer</dt>
                                <dd class="font-semibold text-slate-700">
                                    {{ formatAngka(car.km_saat_ini) }} km
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <p class="text-[11px] text-slate-400">Harga harian</p>
                            <p class="text-sm font-bold text-brand-600">
                                {{ formatRupiah(car.harga_default) }}
                            </p>
                        </div>
                    </div>
                </RouterLink>
            </div>
        </DataState>

        <div class="pf-card mt-5 overflow-hidden">
            <Pagination
                :current-page="filters.page"
                :total="total"
                :per-page="9"
                @change="goToPage"
            />
        </div>
    </div>
</template>