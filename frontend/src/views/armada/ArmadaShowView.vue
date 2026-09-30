<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import CarPhoto from '@/components/CarPhoto.vue'
import DataState from '@/components/DataState.vue'
import StatusBadge from '@/components/StatusBadge.vue'

import { api, readError } from '@/lib/api'
import { useResource } from '@/composables/useResource'
import { useToastStore } from '@/stores/toast'
import { useConfirmStore } from '@/stores/confirm'
import { useAuthStore } from '@/stores/auth'
import {
    formatAngka,
    formatRupiah,
    formatTanggal,
} from '@/lib/format'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  DETAIL KENDARAAN
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Satu halaman berisi empat hal: identitas kendaraan, komponen servis, riwayat
 *  penyewaan, dan riwayat odometer.
 *
 *  ── KOMPONEN SERVIS ───────────────────────────────────────────────────────
 *
 *  Kolom "keausan" dihitung dari jarak sejak servis terakhir, bukan dari
 *  tanggal. Inilah yang membuat peringatan servis di dashboard berguna:
 *  kendaraan yang jarang dipakai mungkin sudah lewat masa servis kilometresnya
 *  walau tidak pernah menyala lebih dari sebulan.
 *
 *  Ambang peringatan: 80% keausan = amber, 100% atau lebih = merah. Angka ini
 *  harus sama dengan yang dipakai DashboardController, supaya daftar peringatan
 *  di dashboard dan tabel di halaman ini tidak pernah berbeda jawabannya.
 */

const route = useRoute()
const toast = useToastStore()
const confirm = useConfirmStore()
const auth = useAuthStore()

const carId = computed(() => route.params.id)

const detail = useResource(() => api.get(`/armada/${carId.value}`))

const car = computed(() => detail.data?.car || null)
const parts = computed(() => car.value?.parts || [])
const riwayatSewa = computed(() => detail.data?.latestBookings?.data || [])
const riwayatOdometer = computed(() => detail.data?.latestOdometerLogs || [])
const totalSewa = computed(() => detail.data?.totalBookings || 0)
const masterParts = computed(() => detail.data?.masterParts || [])

/* ── Update odometer ──────────────────────────────────────────────────── */

const modalOdometer = ref(false)
const kmBaru = ref(null)
const submitKm = ref(false)
const kmError = ref('')

function bukaModalOdometer() {
    kmBaru.value = car.value?.km_saat_ini ?? 0
    kmError.value = ''
    modalOdometer.value = true
}

async function simpanOdometer() {
    submitKm.value = true
    kmError.value = ''

    try {
        const { data } = await api.post(`/armada/${carId.value}/km`, {
            km_saat_ini: Number(kmBaru.value),
        })

        toast.success(data.message || 'Odometer berhasil diperbarui.')
        modalOdometer.value = false

        await detail.reloadQuietly()
    } catch (e) {
        const parsed = readError(e)
        kmError.value = parsed.fieldErrors.km_saat_ini || parsed.message
    } finally {
        submitKm.value = false
    }
}

/* ── Tambah komponen servis ───────────────────────────────────────────── */

const modalPart = ref(false)
const partForm = reactive({
    master_part_id: '',
    last_service_km: null,
    lifespan_km: null,
})
const partErrors = ref({})
const submitPart = ref(false)

function bukaModalPart() {
    partForm.master_part_id = ''
    partForm.last_service_km = car.value?.km_saat_ini ?? 0
    partForm.lifespan_km = null
    partErrors.value = {}
    modalPart.value = true
}

async function simpanPart() {
    submitPart.value = true
    partErrors.value = {}

    try {
        await api.post(`/armada/${carId.value}/part`, {
            master_part_id: partForm.master_part_id,
            last_service_km: Number(partForm.last_service_km),
            lifespan_km: Number(partForm.lifespan_km),
        })

        toast.success('Komponen servis berhasil ditambahkan.')
        modalPart.value = false

        await detail.reloadQuietly()
    } catch (e) {
        partErrors.value = readError(e).fieldErrors
    } finally {
        submitPart.value = false
    }
}

async function hapusPart(part) {
    const ok = await confirm.ask(`Hapus komponen "${part.nama ?? part.nama_komponen}"?`, {
        confirmLabel: 'Ya, hapus',
        tone: 'danger',
    })

    if (!ok) return

    try {
        await api.delete(`/armada/part/${part.id}`)
        toast.success('Komponen dihapus.')
        await detail.reloadQuietly()
    } catch (e) {
        toast.error(readError(e).message)
    }
}

/* ── Status Changed ────────────────────────────────────────────────────── */

const ubahStatusKe = ref('')
const alasanStatus = ref('')
const submitStatus = ref(false)

async function simpanStatus() {
    submitStatus.value = true

    try {
        const { data } = await api.put(`/armada/${carId.value}`, {
            status: ubahStatusKe.value,
            alasan: alasanStatus.value,
        })

        toast.success(data.message || 'Status kendaraan diperbarui.')
        alasanStatus.value = ''

        await detail.reloadQuietly()
    } catch (e) {
        toast.error(readError(e).message)
    } finally {
        submitStatus.value = false
    }
}

const adaBookingAktif = computed(() => Boolean(car.value?.active_booking))

watch(adaBookingAktif, (ada) => {
    // Kendaraan yang sedang disewa tidak boleh dipindahkan status bebas,
    // karena pesanan aktif mengaitkan statusnya sendiri.
    if (ada) ubahStatusKe.value = ''
})

watch(
    () => detail.data?.car?.status,
    (nilai) => {
        if (!adaBookingAktif.value) ubahStatusKe.value = nilai
    },
    { immediate: true }
)

onMounted(() => detail.load())
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <RouterLink
            :to="{ name: 'armada.index' }"
            class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-brand-600 transition-colors mb-1.5"
        >
            <span class="material-symbols-outlined ms-icon text-[16px]">arrow_back</span>
            Kembali ke daftar armada
        </RouterLink>

        <DataState
            :loading="detail.loading"
            :error="detail.error"
            :is-empty="false"
            @retry="detail.load()"
        >
            <template v-if="car">
                <!-- Kepala -->
                <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-4 mb-6">
                    <div class="min-w-0">
                        <h1 class="text-2xl font-bold text-slate-900">
                            {{ car.merk }} {{ car.model }}
                        </h1>

                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <StatusBadge :label="car.status" size="md" />
                            <span class="text-sm text-slate-500 font-mono">
                                {{ car.plat_nomor }}
                            </span>
                            <span class="text-sm text-slate-400">
                                {{ car.tahun }} &middot; {{ car.kapasitas }} orang
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <button
                            v-if="auth.can('fleet.edit')"
                            type="button"
                            class="btn-secondary"
                            @click="bukaModalOdometer"
                        >
                            <span class="material-symbols-outlined ms-icon text-[18px]">speed</span>
                            Update Odometer
                        </button>

                        <RouterLink
                            v-if="auth.can('fleet.edit')"
                            :to="`/armada/${car.id}/edit`"
                            class="btn-secondary"
                        >
                            <span class="material-symbols-outlined ms-icon text-[18px]">edit</span>
                            Ubah
                        </RouterLink>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
                    <!-- Foto -->
                    <div class="pf-card overflow-hidden">
                        <div class="h-56 bg-slate-100">
                            <CarPhoto
                                :url="car.foto_url"
                                :alt="`${car.merk} ${car.model}`"
                            />
                        </div>

                        <!-- Dokumen -->
                        <a
                            v-if="car.foto_dokumen_url"
                            :href="car.foto_dokumen_url"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center gap-2 px-4 py-3 border-t border-slate-100 text-xs font-semibold text-brand-600 hover:bg-slate-50 transition-colors"
                        >
                            <span class="material-symbols-outlined ms-icon text-[16px]">description</span>
                            Lihat dokumen pelepasan hak
                            <span class="material-symbols-outlined ms-icon text-[14px] ml-auto">open_in_new</span>
                        </a>
                    </div>

                    <!-- Spesifikasi -->
                    <div class="lg:col-span-2 pf-card p-5">
                        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                            Spesifikasi
                        </h2>

                        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3.5 text-sm">
                            <div>
                                <dt class="text-xs text-slate-400">Merk / Model</dt>
                                <dd class="font-medium text-slate-800 mt-0.5">
                                    {{ car.merk }} {{ car.model }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-400">Warna</dt>
                                <dd class="font-medium text-slate-800 mt-0.5">{{ car.warna || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-400">Kode Mobil</dt>
                                <dd class="font-medium text-slate-800 mt-0.5 font-mono">
                                    {{ car.kode_mobil || '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-400">Nomor Mesin</dt>
                                <dd class="font-medium text-slate-800 mt-0.5 font-mono text-xs break-all">
                                    {{ car.no_mesin || '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-400">Nomor Rangka</dt>
                                <dd class="font-medium text-slate-800 mt-0.5 font-mono text-xs break-all">
                                    {{ car.no_rangka || '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-400">Odometer</dt>
                                <dd class="font-medium text-slate-800 mt-0.5 tabular-nums">
                                    {{ formatAngka(car.km_saat_ini) }} km
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-400">Harga Harian</dt>
                                <dd class="font-medium text-brand-600 mt-0.5">
                                    {{ formatRupiah(car.harga_default) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-400">Harga Kontrak</dt>
                                <dd class="font-medium text-brand-600 mt-0.5">
                                    {{ formatRupiah(car.harga_kontrak) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-400">Total Penyewaan</dt>
                                <dd class="font-medium text-slate-800 mt-0.5 tabular-nums">
                                    {{ totalSewa }} kali
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- Komponen servis -->
                    <div class="pf-card overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                                Komponen Servis
                            </h2>

                            <button
                                v-if="auth.can('fleet.edit')"
                                type="button"
                                class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition-colors"
                                @click="bukaModalPart"
                            >
                                + Tambah
                            </button>
                        </div>

                        <p v-if="!parts.length" class="text-sm text-slate-400 py-10 text-center">
                            Belum ada komponen yang dilacak untuk kendaraan ini.
                        </p>

                        <ul v-else class="divide-y divide-slate-50">
                            <li
                                v-for="part in parts"
                                :key="part.id"
                                class="px-5 py-3.5"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-800">
                                            {{ part.nama || part.nama_komponen || part.master_part?.nama }}
                                        </p>
                                        <p class="text-xs text-slate-500 mt-0.5 tabular-nums">
                                            {{ formatAngka(part.last_service_km) }} km
                                            &rarr;
                                            {{ formatAngka((part.last_service_km || 0) + (part.lifespan_km || 0)) }} km
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <span
                                            class="text-xs font-bold tabular-nums"
                                            :class="
                                                part.persentase_keausan >= 100
                                                    ? 'text-rose-600'
                                                    : part.persentase_keausan >= 80
                                                      ? 'text-amber-600'
                                                      : 'text-slate-500'
                                            "
                                        >
                                            {{ part.persentase_keausan }}%
                                        </span>

                                        <button
                                            v-if="auth.can('fleet.delete')"
                                            type="button"
                                            class="p-1 rounded text-slate-300 hover:text-rose-600 transition-colors"
                                            :aria-label="`Hapus komponen ${part.nama}`"
                                            @click="hapusPart(part)"
                                        >
                                            <span class="material-symbols-outlined ms-icon text-[16px]">delete</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="h-1.5 bg-slate-100 rounded-full mt-2 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-[width] duration-500"
                                        :class="
                                            part.persentase_keausan >= 100
                                                ? 'bg-rose-500'
                                                : part.persentase_keausan >= 80
                                                  ? 'bg-amber-500'
                                                  : 'bg-emerald-500'
                                        "
                                        :style="{
                                            width: Math.min(100, part.persentase_keausan) + '%',
                                        }"
                                    />
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="space-y-5">
                        <!-- Riwayat sewa -->
                        <div class="pf-card overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                                    Riwayat Penyewaan
                                </h2>
                            </div>

                            <p v-if="!riwayatSewa.length" class="text-sm text-slate-400 py-10 text-center">
                                Kendaraan ini belum pernah disewakan.
                            </p>

                            <ul v-else class="divide-y divide-slate-50">
                                <li
                                    v-for="row in riwayatSewa"
                                    :key="row.id"
                                    class="px-5 py-3 flex items-center justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <RouterLink
                                            :to="`/bookings/${row.booking_id}`"
                                            class="text-sm font-semibold text-brand-600 hover:underline"
                                        >
                                            {{ row.booking?.nomor_pesanan || `#${row.booking_id}` }}
                                        </RouterLink>
                                        <p class="text-xs text-slate-500 mt-0.5 truncate">
                                            {{ row.booking?.nama_pelanggan }}
                                        </p>
                                    </div>

                                    <StatusBadge
                                        :label="row.booking?.status_pembayaran || '-'"
                                    />
                                </li>
                            </ul>
                        </div>

                        <!-- Riwayat odometer -->
                        <div class="pf-card overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                                    Riwayat Odometer
                                </h2>
                            </div>

                            <p
                                v-if="!riwayatOdometer.length"
                                class="text-sm text-slate-400 py-10 text-center"
                            >
                                Belum ada pencatatan odometer.
                            </p>

                            <ul v-else class="divide-y divide-slate-50">
                                <li
                                    v-for="log in riwayatOdometer"
                                    :key="log.id"
                                    class="px-5 py-3 flex items-center justify-between gap-3 text-sm"
                                >
                                    <span class="text-slate-500 tabular-nums">
                                        {{ formatTanggal(log.created_at) }}
                                    </span>
                                    <span class="font-semibold text-slate-800 tabular-nums">
                                        {{ formatAngka(log.km_saat_ini) }} km
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </template>
        </DataState>

        <!-- Modal odometer -->
        <Teleport to="body">
            <div
                v-if="modalOdometer"
                class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
                @click.self="modalOdometer = false"
            >
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">
                    <h3 class="text-base font-bold text-slate-800">Update Odometer</h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                        Angka saat ini {{ formatAngka(car?.km_saat_ini) }} km. Isi
                        kilometer terbaru saat kendaraan diterima kembali.
                    </p>

                    <label class="form-label mt-4 block" for="km_baru">Odometer (km)</label>
                    <input
                        id="km_baru"
                        v-model.number="kmBaru"
                        type="number"
                        min="0"
                        class="form-input text-right tabular-nums"
                        :class="{ 'form-input-error': kmError }"
                    />
                    <p v-if="kmError" class="text-xs text-rose-600 mt-1">{{ kmError }}</p>

                    <div class="flex justify-end gap-2 mt-5">
                        <button type="button" class="btn-secondary" @click="modalOdometer = false">
                            Batal
                        </button>
                        <button type="button" class="btn-primary" :disabled="submitKm" @click="simpanOdometer">
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal komponen -->
        <Teleport to="body">
            <div
                v-if="modalPart"
                class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
                @click.self="modalPart = false"
            >
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">
                    <h3 class="text-base font-bold text-slate-800">Tambah Komponen Servis</h3>

                    <div class="space-y-4 mt-4">
                        <div>
                            <label class="form-label" for="master_part">Komponen</label>
                            <select
                                id="master_part"
                                v-model="partForm.master_part_id"
                                class="form-input"
                            >
                                <option value="">Pilih komponen</option>
                                <option
                                    v-for="mp in masterParts"
                                    :key="mp.id"
                                    :value="mp.id"
                                >
                                    {{ mp.nama }}
                                </option>
                            </select>
                            <p v-if="partErrors.master_part_id" class="text-xs text-rose-600 mt-1">
                                {{ partErrors.master_part_id }}
                            </p>
                        </div>

                        <div>
                            <label class="form-label" for="last_service_km">
                                Kilometer servis terakhir
                            </label>
                            <input
                                id="last_service_km"
                                v-model.number="partForm.last_service_km"
                                type="number"
                                min="0"
                                class="form-input text-right tabular-nums"
                            />
                        </div>

                        <div>
                            <label class="form-label" for="lifespan_km">
                                Jarak tempuh servis (km)
                            </label>
                            <input
                                id="lifespan_km"
                                v-model.number="partForm.lifespan_km"
                                type="number"
                                min="1"
                                class="form-input text-right tabular-nums"
                                placeholder="Contoh: 10000"
                            />
                            <p v-if="partErrors.lifespan_km" class="text-xs text-rose-600 mt-1">
                                {{ partErrors.lifespan_km }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-5">
                        <button type="button" class="btn-secondary" @click="modalPart = false">
                            Batal
                        </button>
                        <button
                            type="button"
                            class="btn-primary"
                            :disabled="submitPart || !partForm.master_part_id"
                            @click="simpanPart"
                        >
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
