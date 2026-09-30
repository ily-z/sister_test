<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import CarPhoto from '@/components/CarPhoto.vue'
import DataState from '@/components/DataState.vue'
import { api, readError } from '@/lib/api'
import { useResource } from '@/composables/useResource'
import { useToastStore } from '@/stores/toast'
import { formatAngka, formatRupiah, formatTanggal } from '@/lib/format'

/*
 * ============================================================================
 *  CATAT SERVIS BENGKEL
 * ============================================================================
 *
 *  Satu form untuk servis yang baru mulai maupun yang sedang berjalan.
 *
 *  --- MENGAPA TIDAK ADA TOMBOL "BATALKAN SERVIS" ---------------------------
 *
 *  storeBengkel() tidak pernah menghapus service log. Menyimpan dengan
 *  status_bengkel = closed menutup servis; menyimpan dengan open melanjutkan
 *  draft yang sama. Jadi "membatalkan" tidak ada padanannya di server, dan
 *  tombol seperti itu di layar hanya akan menipu operator.
 *
 *  --- MENGAPA RINCIANNYA DINPUT MANUAL, BUKAN DARI DAFTAR PART ------------
 *
 *  Kolom car_part_id boleh null. Servis yang tidak memakai komponen --
 *  cuci mobil, ganti ban, tune up -- tidak punya part yang cocok, dan
 *  memaksa operator memilih komponen yang keliru demi bisa menyimpan.
 *  Karena itu part adalah pilihan opsional per baris.
 *
 *  --- EFEK MENYIMPAN -------------------------------------------------------
 *
 *  Menutup servis (status closed) melakukan tiga hal sekaligus:
 *    1. status kendaraan kembali jadi "Tersedia"
 *    2. km_saat_ini kendaraan disamakan dengan km_saat_servis
 *    3. last_service_km tiap part yang dipilih di-reset ke km_saat_servis
 *
 *  Poin 3 penting: inilah satu-satunya tempat umur komponen dihitung ulang.
 *  Kalau servis ditutup tanpa memilih part yang diganti, keausan komponen
 *  tidak berkurang -- dan peringatan servis di dashboard akan terus muncul.
 */

const route = useRoute()
const router = useRouter()
const toast = useToastStore()

const carId = computed(() => route.params.id)

const data = useResource(() => api.get(`/armada/${carId.value}/bengkel/create`))

const car = computed(() => data.data?.car || null)
const parts = computed(() => car.value?.parts || [])
const openLog = computed(() => data.data?.openLog || null)

/* ---------------------------------------------------------------------------
 *  Form
 * --------------------------------------------------------------------------- */

const form = reactive({
    km_saat_servis: 0,
    nama_bengkel: '',
    status_bengkel: 'open',
    tanggal_masuk: '',
    tanggal_keluar: '',
    catatan_umum: '',
})

/* Satu baris rincian. Jumlah baris tidak dibatasi, tapi tiap baris hanya
 * ikut tersimpan kalau deskripsi tindakannya tidak kosong. */
const baris = reactive([])
const foto = ref(null)
const namaFoto = ref('')

const errors = ref({})
const submitting = ref(false)

function tambahBaris() {
    baris.push({
        car_part_id: '',
        deskripsi_tindakan: '',
        biaya: 0,
        reset_part: false,
    })
}

function hapusBaris(index) {
    baris.splice(index, 1)
}

/*
 * Kolom Part tidak diisi otomatis. Memilih komponen bermakna "komponen ini
 * diganti atau diperbaiki", dan hanya baris seperti itu yang me-reset
 * last_service_km. Untuk servis umum seperti cuci mobil atau tune up,
 * kolom Part sengaja dibiarkan kosong.
 */
function partTerpilih(index) {
    return Boolean(baris[index]?.car_part_id)
}

const totalBiaya = computed(() =>
    baris.reduce((total, b) => total + (Number(b.biaya) || 0), 0)
)

const barisTerisi = computed(() =>
    baris.filter((b) => String(b.deskripsi_tindakan || '').trim() !== '').length
)

const servisDitutup = computed(() => form.status_bengkel === 'closed')

/* K supervisorstodosutz: km_saat_servis tidak boleh lebih kecil dari odometer
 * sekarang. Validasi yang sama di server (min:$armada->km_saat_ini). */
const kmDitolak = computed(
    () => form.km_saat_servis !== '' && Number(form.km_saat_servis) < Number(car.value?.km_saat_ini || 0)
)

const tanggalKeluarAwal = computed(() => form.tanggal_masuk)

/* ---------------------------------------------------------------------------
 *  Muat
 * --------------------------------------------------------------------------- */

function isiDariLog(log) {
    if (!log) return

    form.km_saat_servis = log.km_saat_servis ?? car.value?.km_saat_ini ?? 0
    form.nama_bengkel = log.nama_bengkel || ''
    form.status_bengkel = log.status_bengkel || 'open'
    form.tanggal_masuk = log.tanggal_masuk || ''
    form.tanggal_keluar = log.tanggal_keluar || ''
    form.catatan_umum = log.catatan || ''

    const detail = log.service_log_details || log.serviceLogDetails || log.details || []

    if (detail.length) {
        detail.forEach((d) => {
            baris.push({
                car_part_id: d.car_part_id || '',
                deskripsi_tindakan: d.deskripsi_tindakan || '',
                biaya: Number(d.biaya) || 0,
                reset_part: false,
            })
        })
    } else {
        tambahBaris()
    }
}

async function muat() {
    await data.load()

    Object.assign(form, {
        km_saat_servis: car.value?.km_saat_ini ?? 0,
        nama_bengkel: '',
        status_bengkel: 'open',
        tanggal_masuk: baru(),
        tanggal_keluar: '',
        catatan_umum: '',
    })

    baris.length = 0
    tambahBaris()

    isiDariLog(openLog.value)
}

/* Tanggal lokal, bukan toISOString(). toISOString() mengonversi ke UTC, dan
 * untuk zona waktu Indonesia itu bisa mundur satu hari -- tanggal servis
 * jadi salah sehari tanpa ada yang menyadarinya. */
function baru() {
    const d = new Date()
    const bulan = String(d.getMonth() + 1).padStart(2, '0')
    const hari = String(d.getDate()).padStart(2, '0')

    return `${d.getFullYear()}-${bulan}-${hari}`
}

function onPilihFoto(event) {
    const file = event.target.files?.[0] || null

    foto.value = file
    namaFoto.value = file ? file.name : ''
}

/* ---------------------------------------------------------------------------
 *  Simpan
 * --------------------------------------------------------------------------- */

async function simpan() {
    if (submitting.value) return

    submitting.value = true
    errors.value = {}

    const fd = new FormData()

    fd.append('km_saat_servis', String(form.km_saat_servis))
    fd.append('status_bengkel', form.status_bengkel)
    fd.append('tanggal_masuk', form.tanggal_masuk)

    if (form.nama_bengkel) fd.append('nama_bengkel', form.nama_bengkel)
    if (form.tanggal_keluar) fd.append('tanggal_keluar', form.tanggal_keluar)
    if (form.catatan_umum) fd.append('catatan_umum', form.catatan_umum)

    if (foto.value) fd.append('foto_pendukung', foto.value)

    /* tiga array dikirim parallel dengan index yang sama: car_part_id[0],
     * deskripsi_tindakan[0], biaya[0], dst. PHP menyusunnya kembali jadi
     * array yang sejajar. */
    const isi = baris.filter((b) => String(b.deskripsi_tindakan || '').trim() !== '')

    isi.forEach((b) => {
        fd.append('car_part_id[]', b.car_part_id ? String(b.car_part_id) : '')
        fd.append('deskripsi_tindakan[]', b.deskripsi_tindakan)
        fd.append('biaya[]', String(Number(b.biaya) || 0))
    })

    try {
        const { data: hasil } = await api.post(
            `/armada/${carId.value}/bengkel/store`,
            fd
        )

        toast.success(hasil.message || 'Servis tersimpan.')

        /* closed -> /armada/{id}, open -> /armada/{id}/bengkel/create.
         * Server yang memilih tujuannya, frontend tidak menebak. */
        router.push(hasil.redirect_path || `/armada/${carId.value}`)
    } catch (e) {
        const parsed = readError(e)

        errors.value = parsed.fieldErrors
        toast.error(parsed.message)

        /* Error per-baris pada tiga array paralel datang sebagai
         * "deskripsi_tindakan.1" -- dipetakan ke indeks baris yang sama
         * supaya pesan muncul di baris yang benar, bukan di atas form. */
        window.scrollTo({ top: 0, behavior: 'smooth' })
    } finally {
        submitting.value = false
    }
}

function kirimKeBengkel() {
    router.push(`/armada/${carId.value}/bengkel/create`)
}

onMounted(muat)
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8">
        <RouterLink
            :to="`/armada/${carId}`"
            class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-brand-600 transition-colors mb-1.5"
        >
            <span class="material-symbols-outlined ms-icon text-[16px]">arrow_back</span>
            Kembali ke detail kendaraan
        </RouterLink>

        <h1 class="text-2xl font-bold text-slate-900">Catat Servis Bengkel</h1>
        <p class="text-sm text-slate-500 mt-1 mb-6">
            Menyimpan dengan status "Selesai" mengembalikan kendaraan ke pool dan
            mereset umur komponen yang dipilih.
        </p>

        <DataState
            :loading="data.loading"
            :error="data.error"
            :is-empty="false"
            @retry="muat"
        >
            <template v-if="car">
                <form class="space-y-5" @submit.prevent="simpan">
                    <!-- Error umum -->
                    <div
                        v-if="errors.general"
                        class="pf-card px-4 py-3 border-rose-200 bg-rose-50"
                    >
                        <p class="text-sm text-rose-700">{{ errors.general }}</p>
                    </div>

                    <!-- Kendaraan -->
                    <section class="pf-card p-5 flex items-center gap-4">
                        <div class="w-20 h-14 rounded-lg overflow-hidden bg-slate-100 shrink-0">
                            <CarPhoto
                                :url="car.foto_url"
                                :alt="`${car.merk} ${car.model}`"
                            />
                        </div>

                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800">
                                {{ car.merk }} {{ car.model }}
                            </p>
                            <p class="text-xs text-slate-500 font-mono">
                                {{ car.plat_nomor }} &middot; {{ formatAngka(car.km_saat_ini) }} km
                            </p>
                        </div>
                    </section>

                    <!-- Informasi servis -->
                    <section class="pf-card p-5">
                        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                            Informasi Servis
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label" for="km_saat_servis">
                                    Kilometer Saat Servis *
                                </label>
                                <input
                                    id="km_saat_servis"
                                    v-model.number="form.km_saat_servis"
                                    type="number"
                                    min="0"
                                    required
                                    class="form-input text-right tabular-nums"
                                    :class="{
                                        'form-input-error': kmDitolak || errors.km_saat_servis,
                                    }"
                                />
                                <p
                                    v-if="kmDitolak"
                                    class="text-xs text-rose-600 mt-1"
                                >
                                    Tidak boleh lebih kecil dari {{ formatAngka(car.km_saat_ini) }} km.
                                </p>
                                <p
                                    v-else-if="errors.km_saat_servis"
                                    class="text-xs text-rose-600 mt-1"
                                >
                                    {{ errors.km_saat_servis }}
                                </p>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Angka ini yang dipakai untuk mereset umur komponen.
                                </p>
                            </div>

                            <div>
                                <label class="form-label" for="nama_bengkel">
                                    Nama Bengkel
                                </label>
                                <input
                                    id="nama_bengkel"
                                    v-model="form.nama_bengkel"
                                    type="text"
                                    class="form-input"
                                    placeholder="Bengkel Jaya Abadi"
                                />
                            </div>

                            <div>
                                <label class="form-label" for="tanggal_masuk">
                                    Tanggal Masuk *
                                </label>
                                <input
                                    id="tanggal_masuk"
                                    v-model="form.tanggal_masuk"
                                    type="date"
                                    required
                                    class="form-input"
                                    :class="{ 'form-input-error': errors.tanggal_masuk }"
                                />
                                <p
                                    v-if="errors.tanggal_masuk"
                                    class="text-xs text-rose-600 mt-1"
                                >
                                    {{ errors.tanggal_masuk }}
                                </p>
                            </div>

                            <div>
                                <label class="form-label" for="tanggal_keluar">
                                    Tanggal Keluar
                                </label>
                                <input
                                    id="tanggal_keluar"
                                    v-model="form.tanggal_keluar"
                                    type="date"
                                    :min="tanggalKeluarAwal"
                                    class="form-input"
                                    :class="{ 'form-input-error': errors.tanggal_keluar }"
                                />
                                <p
                                    v-if="errors.tanggal_keluar"
                                    class="text-xs text-rose-600 mt-1"
                                >
                                    {{ errors.tanggal_keluar }}
                                </p>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Wajib diisi bila servis ditutup.
                                </p>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <span class="form-label">Status Servis *</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label
                                    class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer transition-colors"
                                    :class="
                                        form.status_bengkel === 'open'
                                            ? 'border-brand-500 bg-brand-50/50'
                                            : 'border-slate-200'
                                    "
                                >
                                    <input
                                        v-model="form.status_bengkel"
                                        type="radio"
                                        value="open"
                                        class="mt-0.5"
                                    />
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-800">
                                            Sedang Dikerjakan
                                        </span>
                                        <span class="block text-[11px] text-slate-500 mt-0.5">
                                            Kendaraan tetap berstatus Masuk Bengkel. Boleh
                                            disimpan berkali-kali untuk melanjutkan.
                                        </span>
                                    </span>
                                </label>

                                <label
                                    class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer transition-colors"
                                    :class="
                                        servisDitutup
                                            ? 'border-emerald-500 bg-emerald-50/50'
                                            : 'border-slate-200'
                                    "
                                >
                                    <input
                                        v-model="form.status_bengkel"
                                        type="radio"
                                        value="closed"
                                        class="mt-0.5"
                                    />
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-800">
                                            Selesai
                                        </span>
                                        <span class="block text-[11px] text-slate-500 mt-0.5">
                                            Kendaraan kembali Tersedia dan umur komponen
                                            terpilih di-reset.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="form-label" for="catatan_umum">Catatan Umum</label>
                            <textarea
                                id="catatan_umum"
                                v-model="form.catatan_umum"
                                rows="2"
                                class="form-input resize-none"
                            />
                        </div>

                        <div class="mt-4">
                            <label class="form-label" for="foto_pendukung">
                                Foto Nota / Bukti
                            </label>
                            <input
                                id="foto_pendukung"
                                type="file"
                                accept="image/*,.pdf"
                                class="text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
                                @change="onPilihFoto"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">
                                {{ namaFoto || 'JPG, PNG, atau PDF. Maksimal 5 MB.' }}
                            </p>
                            <p v-if="errors.foto_pendukung" class="text-xs text-rose-600 mt-1">
                                {{ errors.foto_pendukung }}
                            </p>
                        </div>
                    </section>

                    <!-- Rincian -->
                    <section class="pf-card overflow-hidden">
                        <div
                            class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3"
                        >
                            <div>
                                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                                    Rincian Pekerjaan
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Kolom Part opsional -- biarkan kosong untuk servis umum.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition-colors shrink-0"
                                @click="tambahBaris"
                            >
                                + Tambah Baris
                            </button>
                        </div>

                        <div class="divide-y divide-slate-100">
                            <div
                                v-for="(b, i) in baris"
                                :key="i"
                                class="p-5 space-y-3"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                    <div class="sm:col-span-5">
                                        <label class="form-label" :for="`desc-${i}`">
                                            Tindakan / Jasa
                                        </label>
                                        <input
                                            :id="`desc-${i}`"
                                            v-model="b.deskripsi_tindakan"
                                            type="text"
                                            class="form-input"
                                            :class="{
                                                'form-input-error': errors[`deskripsi_tindakan.${i}`],
                                            }"
                                            placeholder="Ganti Oli Shell 10W-40"
                                        />
                                        <p
                                            v-if="errors[`deskripsi_tindakan.${i}`]"
                                            class="text-xs text-rose-600 mt-1"
                                        >
                                            {{ errors[`deskripsi_tindakan.${i}`] }}
                                        </p>
                                    </div>

                                    <div class="sm:col-span-4">
                                        <label class="form-label" :for="`part-${i}`">
                                            Komponen
                                        </label>
                                        <select :id="`part-${i}`" v-model="b.car_part_id" class="form-input">
                                            <option value="">-- Umum (bukan komponen) --</option>
                                            <option
                                                v-for="p in parts"
                                                :key="p.id"
                                                :value="p.id"
                                            >
                                                {{ p.nama_part }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label class="form-label" :for="`biaya-${i}`">
                                            Biaya (Rp)
                                        </label>
                                        <input
                                            :id="`biaya-${i}`"
                                            v-model.number="b.biaya"
                                            type="number"
                                            min="0"
                                            step="1000"
                                            class="form-input text-right tabular-nums"
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <p
                                        v-if="partTerpilih(i) && servisDitutup"
                                        class="text-[11px] text-emerald-700 flex items-center gap-1"
                                    >
                                        <span class="material-symbols-outlined ms-icon text-[14px]">
                                            check_circle
                                        </span>
                                        Umur komponen ini akan di-reset ke
                                        {{ formatAngka(form.km_saat_servis) }} km.
                                    </p>
                                    <p v-else-if="partTerpilih(i)" class="text-[11px] text-slate-400">
                                        Umur komponen hanya di-reset bila servis ditutup.
                                    </p>
                                    <span v-else></span>

                                    <button
                                        type="button"
                                        class="p-1 rounded text-slate-300 hover:text-rose-600 transition-colors"
                                        :aria-label="`Hapus baris ${i + 1}`"
                                        @click="hapusBaris(i)"
                                    >
                                        <span class="material-symbols-outlined ms-icon text-[16px]">
                                            delete
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            class="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between"
                        >
                            <span class="text-xs text-slate-500">
                                {{ barisTerisi }} baris akan disimpan
                            </span>
                            <span class="text-sm font-bold text-brand-600 tabular-nums">
                                {{ formatRupiah(totalBiaya) }}
                            </span>
                        </div>
                    </section>

                    <!-- Aksi -->
                    <div class="flex items-center justify-end gap-2 pb-6">
                        <RouterLink :to="`/armada/${carId}`" class="btn-secondary">
                            Batal
                        </RouterLink>

                        <button
                            type="submit"
                            class="btn-primary"
                            :disabled="submitting || kmDitolak"
                        >
                            <span
                                v-if="submitting"
                                class="material-symbols-outlined ms-icon text-[18px] animate-spin"
                            >
                                progress_activity
                            </span>
                            {{ servisDitutup ? 'Tutup Servis' : 'Simpan Draft' }}
                        </button>
                    </div>
                </form>
            </template>
        </DataState>
    </div>
</template>