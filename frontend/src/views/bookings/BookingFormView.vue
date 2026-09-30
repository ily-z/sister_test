<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import StatusBadge from '@/components/StatusBadge.vue'

import { api, readError } from '@/lib/api'
import { useToastStore } from '@/stores/toast'
import { useConfirmStore } from '@/stores/confirm'
import { formatRupiah, formatTanggalPendek } from '@/lib/format'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  BUAT / UBAH PESANAN
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Satu komponen untuk dua route: /bookings/create dan /bookings/:id/edit.
 *
 *  Alasannya keduanya identik dari sisi field. Versi Blade punya dua file
 *  terpisah yang isinya nyaris sama; setiap perbaikan harus diterapkan dua
 *  kali, dan tidak lama kemudian keduanya berbeda.
 *
 *  ── PERHITUNGAN HARGA DI SINI ───────────────────────────────────────────────
 *
 *  Total dihitung ulang di browser untuk memberi umpan balik langsung
 *  (setiap ketikan mengubah angka). Angka resmi tetap yang dihitung backend:
 *  form ini tidak pernah mengirim total, server yang menjumlahkan.
 *
 *  Kalau perhitungannya menyimpang, efeknya hanya umpan balik yang meleset
 *  -- bukan data yang salah tersimpan. Itu urutan yang benar: authoritative
 *  harus di server.
 */

const route = useRoute()
const router = useRouter()
const toast = useToastStore()
const confirm = useConfirmStore()

const isEdit = computed(() => Boolean(route.params.id))

const form = reactive({
    nama_pelanggan: '',
    kontak_pelanggan: '',
    email: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
    lokasi_jemput: '',
    lokasi_antar: '',
    tipe_sewa: 'harian',
    status_pembayaran: 'Belum Bayar',
    status_rental: 'Menunggu',
    total_harga: null,
    dp_amount: 0,
    catatan: '',
    cars: [],
    harga_per_hari: [],
    addon_nama: [],
    addon_nominal: [],
    foto_ktp: [],
    foto_penyewa: [],
})

const fieldErrors = ref({})
const submitting = ref(false)
const locked = ref(false)
const lockMessage = ref('')

const availableCars = ref([])

/* ── Perhitungan lokal ────────────────────────────────────────────────── */

const jumlahHari = computed(() => {
    if (!form.tanggal_mulai || !form.tanggal_selesai) return 0

    const mulai = new Date(form.tanggal_mulai)
    const selesai = new Date(form.tanggal_selesai)

    if (Number.isNaN(mulai.getTime()) || Number.isNaN(selesai.getTime())) return 0

    const selisih = Math.round((selesai - mulai) / 86400000) + 1

    return selisih > 0 ? selisih : 0
})

const totalSubtotal = computed(() => {
    return form.harga_per_hari.reduce(
        (total, harga, index) => total + (Number(harga) || 0) * jumlahHari.value,
        0
    )
})

const totalAddon = computed(() => {
    return form.addon_nominal.reduce((total, nilai) => total + (Number(nilai) || 0), 0)
})

const totalAkhir = computed(() => {
    if (form.tipe_sewa === 'kontrak' && form.total_harga !== null && form.total_harga !== '') {
        return Number(form.total_harga) || 0
    }

    return totalSubtotal.value + totalAddon.value
})

/* ── Kendaraan ─────────────────────────────────────────────────────────── */

function toggleCar(car, checked) {
    const index = form.cars.indexOf(car.id)

    if (checked && index === -1) {
        form.cars.push(car.id)
        form.harga_per_hari.push(Number(car.harga_default) || 0)
    } else if (!checked && index !== -1) {
        form.cars.splice(index, 1)
        form.harga_per_hari.splice(index, 1)
    }
}

function isSelected(id) {
    return form.cars.includes(id)
}

/* ── Biaya tambahan ───────────────────────────────────────────────────── */

function tambahAddon() {
    form.addon_nama.push('')
    form.addon_nominal.push(0)
}

function hapusAddon(index) {
    form.addon_nama.splice(index, 1)
    form.addon_nominal.splice(index, 1)
}

/* ── Berkas ───────────────────────────────────────────────────────────── */

/*
 * Yang disimpan adalah objek File-nya, bukan nama file.
 *
 * Menyimpan nama file terlihat lebih ringkas, tapi FormData butuh objek File
 * yang sebenarnya -- nama file saja tidak bisa diunggah. Memakai referensi
 * ke elemen <input> sebagai gantinya juga rapuh: begitu Vue me-render ulang
 * elemen itu, referensinya bisa menunjuk ke input yang lain, dan berkas
 * yang dipilih pengguna hilang tanpa pesan apa pun.
 */
const berkasKtp = ref([])
const berkasPenyewa = ref([])

function onFile(event, target) {
    const files = Array.from(event.target.files || [])

    if (target === 'ktp') {
        berkasKtp.value = files
    } else {
        berkasPenyewa.value = files
    }
}

function jumlahBerkas(list) {
    return list.length ? `${list.length} berkas dipilih` : 'Belum ada berkas'
}

/* ── Kirim ────────────────────────────────────────────────────────────── */

const formData = computed(() => {
    const fd = new FormData()

    fd.append('nama_pelanggan', form.nama_pelanggan)
    fd.append('kontak_pelanggan', form.kontak_pelanggan)
    fd.append('tanggal_mulai', form.tanggal_mulai)
    fd.append('tanggal_selesai', form.tanggal_selesai)
    fd.append('tipe_sewa', form.tipe_sewa)
    fd.append('status_pembayaran', form.status_pembayaran)
    fd.append('status_rental', form.status_rental)
    fd.append('dp_amount', String(form.dp_amount || 0))

    form.cars.forEach((id) => fd.append('cars[]', String(id)))
    form.harga_per_hari.forEach((harga) => fd.append('harga_per_hari[]', String(harga)))

    form.addon_nama.forEach((nama, i) => {
        if (String(nama).trim() !== '') {
            fd.append('addon_nama[]', String(nama).trim())
            fd.append('addon_nominal[]', String(form.addon_nominal[i] || 0))
        }
    })

    if (form.email) fd.append('email', form.email)
    if (form.lokasi_jemput) fd.append('lokasi_jemput', form.lokasi_jemput)
    if (form.lokasi_antar) fd.append('lokasi_antar', form.lokasi_antar)
    if (form.catatan) fd.append('catatan', form.catatan)
    if (form.tipe_sewa === 'kontrak' && form.total_harga !== null && form.total_harga !== '') {
        fd.append('total_harga', String(form.total_harga))
    }

    return fd
})

async function onSubmit() {
    if (submitting.value) return

    submitting.value = true
    fieldErrors.value = {}

    const fd = formData.value

    berkasKtp.value.forEach((file) => fd.append('foto_ktp', file))
    berkasPenyewa.value.forEach((file) => fd.append('foto_penyewa', file))

    try {
        if (isEdit.value) {
            /*
             * PUT dengan FormData.
             *
             * Laravel tidak bisa membedakan "field tidak dikirim" dari
             * "field dikosongkan" pada PUT lewat $_POST, dan ini bukan
             * FormRequest biasa. Karena itu untuk edition memakai POST dengan
             * _method=PUT, yang membuat PHP preencher $_POST dengan benar.
             * Lihat backend/app/Http/Controllers/Api/BookingController::update.
             */
            fd.append('_method', 'PUT')

            const { data } = await api.post(`/bookings/${route.params.id}`, fd)
            toast.success(data.message || 'Pesanan berhasil diperbarui.')
            router.push(data.redirect_path || `/bookings/${route.params.id}`)
        } else {
            const { data } = await api.post('/bookings', fd)
            toast.success(data.message || 'Pesanan berhasil dibuat.')
            router.push(data.redirect_path || '/bookings')
        }
    } catch (e) {
        const detail = readError(e)
        fieldErrors.value = detail.fieldErrors

        if (detail.status === 422) {
            toast.error('Periksa kembali kolom yang ditandai merah.')
        } else if (detail.status === 423) {
            /*
             * 423 Locked: pesanan yang sedang aktif tidak boleh diedit.
             * Ini bukan kesalahan validasi -- pesanannya sendiri tidak salah,
             * cuma sudah berjalan. Karena itu pesannya ditampilkan penuh,
             * bukan lewat toast yang biasa.
             */
            locked.value = true
            lockMessage.value = detail.message
            toast.warning(detail.message)
        } else {
            toast.error(detail.message)
        }
    } finally {
        submitting.value = false
    }
}

async function onDelete() {
    const ok = await confirm.ask('Hapus pesanan ini secara permanen?', {
        confirmLabel: 'Ya, hapus',
        tone: 'danger',
    })

    if (!ok) return

    try {
        const { data } = await api.delete(`/bookings/${route.params.id}`)
        toast.success(data.message || 'Pesanan dihapus.')
        router.push(data.redirect_path || '/bookings')
    } catch (e) {
        toast.error(readError(e).message)
    }
}

/* ── Muat ─────────────────────────────────────────────────────────────── */

async function loadAvailableCars() {
    const { data } = await api.get('/bookings/create')
    availableCars.value = data.availableCars || []
}

async function loadBooking() {
    try {
        const { data } = await api.get(`/bookings/${route.params.id}/edit`)

        if (data.locked) {
            locked.value = true
            lockMessage.value = data.message || ''
            return
        }

        const b = data.booking

        Object.assign(form, {
            nama_pelanggan: b.nama_pelanggan || '',
            kontak_pelanggan: b.kontak_pelanggan || '',
            email: b.email || '',
            tanggal_mulai: b.tanggal_mulai?.slice(0, 10) || '',
            tanggal_selesai: b.tanggal_selesai?.slice(0, 10) || '',
            lokasi_jemput: b.lokasi_jemput || '',
            lokasi_antar: b.lokasi_antar || '',
            tipe_sewa: b.tipe_sewa || 'harian',
            status_pembayaran: b.status_pembayaran || 'Belum Bayar',
            status_rental: b.status_rental || 'Menunggu',
            total_harga: b.total_harga ?? null,
            dp_amount: b.dp_amount ?? 0,
            catatan: b.catatan || '',
            cars: (b.booking_cars || []).map((bc) => bc.car_id),
            harga_per_hari: (b.booking_cars || []).map((bc) => Number(bc.harga_per_hari) || 0),
            addon_nama: (b.addons || []).map((a) => a.nama),
            addon_nominal: (b.addons || []).map((a) => Number(a.nominal)),
        })

        /*
         * Kendaraan yang sedang disewa pesanan ini sendiri tidak muncul di
         * /bookings/create, karena endpoint itu hanya mengembalikan mobil
         * berstatus "Tersedia". Kalau tidak ditambahkan secara manual,
         * memuat form edit akan membuat mobil pesanan ini hilang dari daftar
         * terpilih -- dan pennyimpanan form tanpa mengubah apa pun akan
         * melepas mobilnya dari pesanan.
         */
        const carsDalamPesanan = (b.booking_cars || [])
            .map((bc) => bc.car)
            .filter(Boolean)

        const sudahAda = new Set(availableCars.value.map((c) => c.id))
        const tambahan = carsDalamPesanan.filter((c) => !sudahAda.has(c.id))

        availableCars.value = [...availableCars.value, ...tambahan]
    } catch (e) {
        const detail = readError(e)

        if (detail.status === 423) {
            locked.value = true
            lockMessage.value = detail.message
        } else {
            toast.error(detail.message)
        }
    }
}

onMounted(async () => {
    await loadAvailableCars()

    if (isEdit.value) {
        await loadBooking()
    } else {
        // Tanggal mulai defaultnya hari ini: pesanan yang baru dibuat
        // hampir selalu dimulai hari ini juga, jadi mengurangi satu
        // langkah tanpa membatasi pilihan lain.
        const hariIni = new Date()
        const iso = hariIni.toISOString().slice(0, 10)
        form.tanggal_mulai = iso
        form.tanggal_selesai = iso
    }
})

/* Status rental mengikuti status pembayaran untuk mengurangi input manual. */
watch(
    () => form.status_pembayaran,
    (nilai) => {
        if (nilai === 'Lunas') form.status_rental = 'Aktif'
    }
)
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-6 gap-4">
            <div>
                <RouterLink
                    :to="{ name: 'bookings.index' }"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-brand-600 transition-colors mb-1.5"
                >
                    <span class="material-symbols-outlined ms-icon text-[16px]">arrow_back</span>
                    Kembali ke daftar
                </RouterLink>

                <h1 class="text-2xl font-bold text-slate-900">
                    {{ isEdit ? 'Ubah Pesanan' : 'Buat Pesanan' }}
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Lengkapi data penyewa dan pilih kendaraan yang akan disewakan.
                </p>
            </div>

            <button v-if="isEdit" type="button" class="btn-danger shrink-0" @click="onDelete">
                <span class="material-symbols-outlined ms-icon text-[18px]">delete</span>
                Hapus
            </button>
        </div>

        <!-- Pesanan terkunci -->
        <div
            v-if="locked"
            class="pf-card p-5 mb-5 border-amber-200 bg-amber-50/60 flex items-start gap-3"
        >
            <span class="material-symbols-outlined ms-icon text-amber-500 text-[22px] shrink-0">
                lock
            </span>
            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-semibold text-amber-900">Pesanan sudah berjalan</h2>
                <p class="text-sm text-amber-800 mt-1 leading-relaxed">
                    {{ lockMessage || 'Pesanan yang sudah aktif tidak dapat diedit.' }}
                </p>
            </div>
            <RouterLink
                :to="{ name: 'bookings.show', params: { id: route.params.id } }"
                class="btn-secondary shrink-0"
            >
                Lihat detail
            </RouterLink>
        </div>

        <form v-else @submit.prevent="onSubmit">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Kolom kiri -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Data penyewa -->
                    <section class="pf-card p-5">
                        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                            Data Penyewa
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="form-label" for="nama_pelanggan">Nama Penyewa</label>
                                <input
                                    id="nama_pelanggan"
                                    v-model="form.nama_pelanggan"
                                    type="text"
                                    required
                                    class="form-input"
                                    :class="{ 'form-input-error': fieldErrors.nama_pelanggan }"
                                />
                                <p v-if="fieldErrors.nama_pelanggan" class="text-xs text-rose-600 mt-1">
                                    {{ fieldErrors.nama_pelanggan }}
                                </p>
                            </div>

                            <div>
                                <label class="form-label" for="kontak_pelanggan">Kontak</label>
                                <input
                                    id="kontak_pelanggan"
                                    v-model="form.kontak_pelanggan"
                                    type="tel"
                                    required
                                    class="form-input"
                                    :class="{ 'form-input-error': fieldErrors.kontak_pelanggan }"
                                />
                                <p v-if="fieldErrors.kontak_pelanggan" class="text-xs text-rose-600 mt-1">
                                    {{ fieldErrors.kontak_pelanggan }}
                                </p>
                            </div>

                            <div>
                                <label class="form-label" for="email">Email (opsional)</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="form-input"
                                    :class="{ 'form-input-error': fieldErrors.email }"
                                />
                                <p v-if="fieldErrors.email" class="text-xs text-rose-600 mt-1">
                                    {{ fieldErrors.email }}
                                </p>
                            </div>

                            <div>
                                <label class="form-label" for="lokasi_jemput">Lokasi Jemput</label>
                                <input id="lokasi_jemput" v-model="form.lokasi_jemput" type="text" class="form-input" />
                            </div>

                            <div>
                                <label class="form-label" for="lokasi_antar">Lokasi Antar</label>
                                <input id="lokasi_antar" v-model="form.lokasi_antar" type="text" class="form-input" />
                            </div>
                        </div>

                        <!-- Dokumen -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 pt-4 border-t border-slate-100">
                            <div>
                                <label class="form-label" for="foto_ktp">Foto KTP</label>
                                <input
                                    id="foto_ktp"
                                    type="file"
                                    accept="image/*"
                                    multiple
                                    class="text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
                                    @change="onFile($event, 'ktp')"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">
                                    {{ jumlahBerkas(berkasKtp) }}
                                </p>
                                <p v-if="fieldErrors.foto_ktp" class="text-xs text-rose-600 mt-1">
                                    {{ fieldErrors.foto_ktp }}
                                </p>
                            </div>

                            <div>
                                <label class="form-label" for="foto_penyewa">Foto Penyewa</label>
                                <input
                                    id="foto_penyewa"
                                    type="file"
                                    accept="image/*"
                                    multiple
                                    class="text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
                                    @change="onFile($event, 'penyewa')"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">
                                    {{ jumlahBerkas(berkasPenyewa) }}
                                </p>
                                <p v-if="fieldErrors.foto_penyewa" class="text-xs text-rose-600 mt-1">
                                    {{ fieldErrors.foto_penyewa }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Kendaraan -->
                    <section class="pf-card p-5">
                        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Kendaraan
                        </h2>
                        <p class="text-xs text-slate-500 mb-4">
                            Hanya kendaraan berstatus "Tersedia" yang bisa dipilih.
                        </p>

                        <p v-if="fieldErrors.cars" class="text-xs text-rose-600 mb-3">
                            {{ fieldErrors.cars }}
                        </p>

                        <p v-if="!availableCars.length" class="text-sm text-slate-400 py-6 text-center">
                            Tidak ada kendaraan tersedia saat ini.
                        </p>

                        <div v-else class="space-y-2.5">
                            <label
                                v-for="car in availableCars"
                                :key="car.id"
                                class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all"
                                :class="
                                    isSelected(car.id)
                                        ? 'border-brand-400 bg-brand-50/50'
                                        : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                "
                            >
                                <input
                                    type="checkbox"
                                    :checked="isSelected(car.id)"
                                    class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                    @change="toggleCar(car, $event.target.checked)"
                                />

                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-800 truncate">
                                        {{ car.merk }} {{ car.model }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ car.plat_nomor }} &middot; {{ car.kapasitas }} orang
                                    </p>
                                </div>

                                <div
                                    v-if="isSelected(car.id)"
                                    class="w-28 shrink-0"
                                >
                                    <label class="sr-only" :for="`harga-${car.id}`">
                                        Harga per hari {{ car.plat_nomor }}
                                    </label>
                                    <input
                                        :id="`harga-${car.id}`"
                                        :value="form.harga_per_hari[form.cars.indexOf(car.id)]"
                                        type="number"
                                        min="0"
                                        step="1000"
                                        class="form-input py-1.5 text-xs text-right tabular-nums"
                                        @input="form.harga_per_hari[form.cars.indexOf(car.id)] = Number($event.target.value)"
                                    />
                                </div>
                            </label>
                        </div>
                    </section>

                    <!-- Biaya tambahan -->
                    <section class="pf-card p-5">
                        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                            Biaya Tambahan
                        </h2>

                        <div v-if="form.addon_nama.length" class="space-y-2.5 mb-3">
                            <div
                                v-for="(nama, i) in form.addon_nama"
                                :key="i"
                                class="flex items-center gap-2"
                            >
                                <input
                                    v-model="form.addon_nama[i]"
                                    type="text"
                                    class="form-input flex-1"
                                    placeholder="Nama biaya (mis. BBM, Parkir Tol)"
                                />
                                <input
                                    v-model.number="form.addon_nominal[i]"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    class="form-input w-36 text-right tabular-nums"
                                    placeholder="Nominal"
                                />
                                <button
                                    type="button"
                                    class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors shrink-0"
                                    aria-label="Hapus biaya tambahan"
                                    @click="hapusAddon(i)"
                                >
                                    <span class="material-symbols-outlined ms-icon text-[18px]">close</span>
                                </button>
                            </div>
                        </div>

                        <button type="button" class="btn-secondary" @click="tambahAddon">
                            <span class="material-symbols-outlined ms-icon text-[18px]">add</span>
                            Tambah Biaya
                        </button>
                    </section>
                </div>

                <!-- Kolom kanan -->
                <div class="space-y-5">
                    <!-- Periode -->
                    <section class="pf-card p-5">
                        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                            Periode &amp; Status
                        </h2>

                        <div class="space-y-4">
                            <div>
                                <label class="form-label" for="tanggal_mulai">Tanggal Mulai</label>
                                <input
                                    id="tanggal_mulai"
                                    v-model="form.tanggal_mulai"
                                    type="date"
                                    required
                                    class="form-input"
                                    :class="{ 'form-input-error': fieldErrors.tanggal_mulai }"
                                />
                                <p v-if="fieldErrors.tanggal_mulai" class="text-xs text-rose-600 mt-1">
                                    {{ fieldErrors.tanggal_mulai }}
                                </p>
                            </div>

                            <div>
                                <label class="form-label" for="tanggal_selesai">Tanggal Selesai</label>
                                <input
                                    id="tanggal_selesai"
                                    v-model="form.tanggal_selesai"
                                    type="date"
                                    required
                                    class="form-input"
                                    :class="{ 'form-input-error': fieldErrors.tanggal_selesai }"
                                />
                                <p v-if="fieldErrors.tanggal_selesai" class="text-xs text-rose-600 mt-1">
                                    {{ fieldErrors.tanggal_selesai }}
                                </p>
                            </div>

                            <div>
                                <label class="form-label" for="tipe_sewa">Tipe Sewa</label>
                                <select id="tipe_sewa" v-model="form.tipe_sewa" class="form-input">
                                    <option value="harian">Harian</option>
                                    <option value="kontrak">Kontrak</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label" for="status_pembayaran">Status Pembayaran</label>
                                <select id="status_pembayaran" v-model="form.status_pembayaran" class="form-input">
                                    <option>Belum Bayar</option>
                                    <option>DP</option>
                                    <option>Belum Lunas</option>
                                    <option>Lunas</option>
                                </select>
                            </div>

                            <div v-if="form.status_pembayaran === 'DP'">
                                <label class="form-label" for="dp_amount">Nominal DP</label>
                                <input
                                    id="dp_amount"
                                    v-model.number="form.dp_amount"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    class="form-input text-right tabular-nums"
                                />
                            </div>

                            <div>
                                <label class="form-label" for="catatan">Catatan</label>
                                <textarea
                                    id="catatan"
                                    v-model="form.catatan"
                                    rows="3"
                                    class="form-input resize-none"
                                    placeholder="Catatan internal, mis. permintaan khusus dari pelanggan."
                                />
                            </div>
                        </div>
                    </section>

                    <!-- Ringkasan biaya -->
                    <section class="pf-card p-5 sticky bottom-20 md:bottom-5">
                        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                            Ringkasan Biaya
                        </h2>

                        <dl class="space-y-2.5 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Durasi</dt>
                                <dd class="font-medium text-slate-700 tabular-nums">
                                    {{ jumlahHari }} hari
                                </dd>
                            </div>

                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Kendaraan</dt>
                                <dd class="font-medium text-slate-700 tabular-nums">
                                    {{ form.cars.length }}
                                </dd>
                            </div>

                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Subtotal sewa</dt>
                                <dd class="font-medium text-slate-700 tabular-nums">
                                    {{ formatRupiah(totalSubtotal) }}
                                </dd>
                            </div>

                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Biaya tambahan</dt>
                                <dd class="font-medium text-slate-700 tabular-nums">
                                    {{ formatRupiah(totalAddon) }}
                                </dd>
                            </div>

                            <div
                                v-if="form.tipe_sewa === 'kontrak'"
                                class="pt-3 border-t border-slate-100"
                            >
                                <label class="form-label" for="total_harga">
                                    Override total kontrak
                                </label>
                                <input
                                    id="total_harga"
                                    v-model.number="form.total_harga"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    class="form-input text-right tabular-nums"
                                    placeholder="Kosongkan untuk memakai hasil penjumlahan"
                                />
                                <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                    Untuk sewa kontrak, isi kalau harga sudah disepakati tetap.
                                    Kosongkan untuk memakai hasil penjumlahan di atas.
                                </p>
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex justify-between gap-3 items-baseline">
                                <dt class="text-sm font-semibold text-slate-700">Total</dt>
                                <dd class="text-lg font-bold text-brand-600 tabular-nums">
                                    {{ formatRupiah(totalAkhir) }}
                                </dd>
                            </div>
                        </dl>

                        <p class="text-[11px] text-slate-400 mt-3 leading-relaxed">
                            Angka ini hanya perkiraan. Total resmi dihitung ulang oleh server
                            saat pesanan disimpan.
                        </p>

                        <button type="submit" class="btn-primary w-full mt-4" :disabled="submitting || !form.cars.length">
                            <span
                                v-if="submitting"
                                class="material-symbols-outlined ms-icon text-[18px] animate-spin"
                            >
                                progress_activity
                            </span>
                            {{ isEdit ? 'Simpan Perubahan' : 'Buat Pesanan' }}
                        </button>

                        <p
                            v-if="!form.cars.length"
                            class="text-[11px] text-amber-600 mt-2 text-center"
                        >
                            Pilih minimal satu kendaraan.
                        </p>
                    </section>
                </div>
            </div>
        </form>
    </div>
</template>