<script setup>
import { computed, onMounted } from 'vue'

import CarPhoto from '@/components/CarPhoto.vue'
import DataState from '@/components/DataState.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'

import { api } from '@/lib/api'
import { useResource } from '@/composables/useResource'
import { useAuthStore } from '@/stores/auth'
import { formatAngka, formatRupiah, formatTanggal } from '@/lib/format'

/*
 * ============================================================================
 *  DASHBOARD BENGKEL
 * ============================================================================
 *
 *  Halaman ringkasan. Yang ditampilkan:
 *    - kendaraan yang statusnya "Masuk Bengkel"
 *    - catatan servis yang masih terbuka
 *    - catatan servis yang selesai bulan ini
 *    - servis yang tanggal keluarnya sudah diisi tapi belum ditutup
 *
 *  --- TIDAK ADA TOMBOL KIRIM KE BENGKEL DI SINI ----------------------------
 *
 *  Endpoint send-to-workshop hanya menerima kendaraan yang saat ini
 *  "Tersedia". Di halaman ini semua kendaraan sudah berstatus "Masuk
 *  Bengkel", jadi tombol itu tidak akan pernah berhasil dari sini. Tombolnya
 *  ada di halaman detail kendaraan, di situ persyaratannya terpenuhi.
 *
 *  --- CARA MENGHITUNG BIAYA ------------------------------------------------
 *
 *  Total biaya bulan ini dihitung dari service_log_details, bukan dari
 *  service_logs. service_logs tidak punya kolom total; biayanya ada di tiap
 *  baris rincian, jadi penjumlahannya harus di level detail.
 */

const auth = useAuthStore()

const data = useResource(() => api.get('/armada/bengkel'))

const mobilDiBengkel = computed(() => data.data?.carsInWorkshop || [])
const servisTerbaru = computed(() => data.data?.recentServiceLogs || [])
const akanSelesai = computed(() => data.data?.upcomingFinish || [])

const totalBiayaBulanIni = computed(() => Number(data.data?.totalBiayaBulanIni) || 0)
const servisTerbuka = computed(() => data.data?.openServiceLogs || 0)
const selesaiBulanIni = computed(() => data.data?.closedThisMonth || 0)

/* Service log yang tanggal keluarnya sudah diisi tapi status_bengkel masih
 * open. Kondisi ini jarang disadari: operator mengisi tanggal keluar lalu
 * lupa menutup servis, sehingga kendaraan tetap terkunci di bengkel padahal
 * pekerjaannya sudah selesai. */
const belumDitutup = computed(() =>
    akanSelesai.value.filter((s) => s.status_bengkel === 'open')
)

/* Invoice bengkel dikunci ke service_log_id, jadi tombol TTD/Unduh hanya
 * muncul di baris yang memang sudah punya invoice. */
function invoiceUntuk(serviceLogId) {
    return data.data?.bengkelInvoices?.[serviceLogId] || null
}

function sudahDitandatangani(invoice) {
    return invoice?.signatures?.length >= 2
}

function detailServis(log) {
    return log.service_logs || log.serviceLogDetails || log.details || []
}

onMounted(() => data.load())
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Kepala -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Bengkel</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Kendaraan yang sedang diperbaiki dan catatan serviced-nya.
                </p>
            </div>

            <RouterLink
                v-if="auth.can('fleet.view')"
                :to="{ name: 'armada.index' }"
                class="btn-secondary shrink-0"
            >
                <span class="material-symbols-outlined ms-icon text-[18px]">directions_car</span>
                Daftar Armada
            </RouterLink>
        </div>

        <DataState
            :loading="data.loading"
            :error="data.error"
            :is-empty="false"
            @retry="data.load()"
        >
            <template v-if="data.data">
                <!-- Angka -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="pf-card p-4">
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">
                            Biaya Servis Bulan Ini
                        </p>
                        <p class="text-xl font-bold text-brand-600 mt-1.5 tabular-nums">
                            {{ formatRupiah(totalBiayaBulanIni) }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Dihitung dari {{ servisTerbuka }} servis yang masih terbuka.
                        </p>
                    </div>

                    <div class="pf-card p-4">
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">
                            Servis Terbuka
                        </p>
                        <p class="text-xl font-bold text-amber-600 mt-1.5 tabular-nums">
                            {{ servisTerbuka }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Kendaraan masih terkunci di bengkel.
                        </p>
                    </div>

                    <div class="pf-card p-4">
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">
                            Selesai Bulan Ini
                        </p>
                        <p class="text-xl font-bold text-emerald-600 mt-1.5 tabular-nums">
                            {{ selesaiBulanIni }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Dihitung dari tanggal keluar, bukan tanggal masuk.
                        </p>
                    </div>
                </div>

                <!-- Peringatan -->
                <div
                    v-if="belumDitutup.length"
                    class="pf-card px-4 py-3 mb-6 border-amber-200 bg-amber-50/70 flex items-start gap-3"
                >
                    <span
                        class="material-symbols-outlined ms-icon text-amber-600 text-[20px] shrink-0 mt-0.5"
                    >
                        warning
                    </span>
                    <div>
                        <p class="text-sm text-amber-900 font-semibold">
                            {{ belumDitutup.length }} servis punya tanggal keluar tapi belum
                            ditutup
                        </p>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Kendaraan seperti ini tetap berstatus Masuk Bengkel sampai servis
                            ditutup dari form catat servis.
                        </p>
                        <ul class="mt-2 space-y-1">
                            <li v-for="log in belumDitutup" :key="log.id" class="text-xs">
                                <RouterLink
                                    :to="`/armada/${log.car_id}/bengkel/create`"
                                    class="font-semibold text-amber-900 underline"
                                >
                                    {{ log.car?.merk }} {{ log.car?.model }}
                                    ({{ log.car?.plat_nomor }})
                                </RouterLink>
                                <span class="text-amber-700">
                                    &mdash; selesai {{ formatTanggal(log.tanggal_keluar) }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- Kendaraan di bengkel -->
                    <section class="pf-card overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100">
                            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                                Kendaraan di Bengkel ({{ mobilDiBengkel.length }})
                            </h2>
                        </div>

                        <EmptyState
                            v-if="!mobilDiBengkel.length"
                            icon="build"
                            title="Tidak ada kendaraan di bengkel"
                            description="Semua armada sedang tersedia untuk disewakan."
                        />

                        <ul v-else class="divide-y divide-slate-50">
                            <li
                                v-for="car in mobilDiBengkel"
                                :key="car.id"
                                class="px-5 py-4"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-16 h-11 rounded-lg overflow-hidden bg-slate-100 shrink-0"
                                    >
                                        <CarPhoto
                                            :url="car.foto_url"
                                            :alt="`${car.merk} ${car.model}`"
                                        />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <RouterLink
                                                    :to="`/armada/${car.id}`"
                                                    class="text-sm font-semibold text-slate-800 hover:text-brand-600 transition-colors"
                                                >
                                                    {{ car.merk }} {{ car.model }}
                                                </RouterLink>
                                                <p class="text-xs text-slate-500 font-mono">
                                                    {{ car.plat_nomor }}
                                                </p>
                                            </div>

                                            <span class="text-xs text-slate-500 tabular-nums shrink-0">
                                                {{ formatAngka(car.km_saat_ini) }} km
                                            </span>
                                        </div>

                                        <!-- Servis yang sedang berjalan -->
                                        <div
                                            v-for="log in car.service_logs || []"
                                            :key="log.id"
                                            class="mt-2.5 text-xs bg-slate-50 rounded-lg px-3 py-2"
                                        >
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-semibold text-slate-700">
                                                    {{ log.nama_bengkel || 'Tanpa nama bengkel' }}
                                                </span>
                                                <StatusBadge
                                                    :label="log.status_bengkel === 'closed' ? 'Selesai' : 'Proses'"
                                                    size="sm"
                                                />
                                            </div>

                                            <p class="text-slate-500 mt-1">
                                                Masuk {{ formatTanggal(log.tanggal_masuk) }}
                                                <template v-if="log.tanggal_keluar">
                                                    &middot; keluar
                                                    {{ formatTanggal(log.tanggal_keluar) }}
                                                </template>
                                            </p>

                                            <ul
                                                v-if="detailServis(log).length"
                                                class="mt-1.5 space-y-0.5"
                                            >
                                                <li
                                                    v-for="d in detailServis(log)"
                                                    :key="d.id"
                                                    class="flex justify-between gap-2 text-slate-600"
                                                >
                                                    <span class="truncate">
                                                        {{ d.deskripsi_tindakan }}
                                                    </span>
                                                    <span class="tabular-nums shrink-0">
                                                        {{ formatRupiah(d.biaya) }}
                                                    </span>
                                                </li>
                                            </ul>

                                            <RouterLink
                                                v-if="auth.can('service.edit')"
                                                :to="`/armada/${car.id}/bengkel/create`"
                                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand-600 hover:text-brand-700 mt-2 transition-colors"
                                            >
                                                <span class="material-symbols-outlined ms-icon text-[13px]">
                                                    edit
                                                </span>
                                                {{ log.status_bengkel === 'closed'
                                                    ? 'Lihat / Ubah'
                                                    : 'Lanjutkan Servis' }}
                                            </RouterLink>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </section>

                    <div class="space-y-5">
                        <!-- Riwayat servis terbaru -->
                        <section class="pf-card overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                                    Servis Terbaru
                                </h2>
                            </div>

                            <EmptyState
                                v-if="!servisTerbaru.length"
                                icon="history"
                                title="Belum ada riwayat servis"
                            />

                            <ul v-else class="divide-y divide-slate-50">
                                <li
                                    v-for="log in servisTerbaru"
                                    :key="log.id"
                                    class="px-5 py-3 flex items-center justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <RouterLink
                                            :to="`/armada/${log.car_id}`"
                                            class="text-sm font-medium text-slate-800 hover:text-brand-600 transition-colors"
                                        >
                                            {{ log.car?.merk }} {{ log.car?.model }}
                                        </RouterLink>
                                        <p class="text-xs text-slate-500">
                                            {{ formatTanggal(log.tanggal_masuk) }}
                                            <template v-if="log.nama_bengkel">
                                                &middot; {{ log.nama_bengkel }}
                                            </template>
                                        </p>
                                    </div>

                                    <StatusBadge
                                        :label="log.status_bengkel === 'closed' ? 'Selesai' : 'Proses'"
                                    />
                                </li>
                            </ul>
                        </section>

                        <!-- Invoice bengkel -->
                        <section class="pf-card overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                                    Invoice Bengkel
                                </h2>
                            </div>

                            <EmptyState
                                v-if="!Object.keys(data.data.bengkelInvoices || {}).length"
                                icon="receipt_long"
                                title="Belum ada invoice bengkel"
                                description="Invoice dibuat dari catatan servis yang sudah ditutup."
                            />

                            <ul v-else class="divide-y divide-slate-50">
                                <li
                                    v-for="(inv, serviceLogId) in data.data.bengkelInvoices"
                                    :key="serviceLogId"
                                    class="px-5 py-3 flex items-center justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-800">
                                            {{ inv.surat?.nomor_surat || inv.nomor_seri }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            {{ formatTanggal(inv.issued_at) }}
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <StatusBadge
                                            :label="sudahDitandatangani(inv) ? 'Lunas' : 'Belum TTD'"
                                        />

                                        <RouterLink
                                            :to="`/invoices/${inv.id}/print-bengkel`"
                                            class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition-colors"
                                        >
                                            Buka
                                        </RouterLink>
                                    </div>
                                </li>
                            </ul>
                        </section>
                    </div>
                </div>
            </template>
        </DataState>
    </div>
</template>