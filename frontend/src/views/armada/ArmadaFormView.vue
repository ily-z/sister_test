<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { api, readError } from '@/lib/api'
import { useToastStore } from '@/stores/toast'
import { formatTanggalPanjang } from '@/lib/format'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  TAMBAH / UBAH KENDARAAN
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  ── SOAL SURAT PELEPASAN HAK KENDARAAN ────────────────────────────────────
 *
 *  Menyimpan kendaraan mewajibkan dua hal sekaligus: nomor surat pelepasan
 *  hak kendaraan (PHK) dan berkas dokumennya. Keduanya dibuat dari panel
 *  dokumen di halaman ini, lewat /api/armada/create-dokumen.
 *
 *  Endpoint itu idempoten per pengguna: tombol ditekan dua kali tidak
 *  menghasilkan dua nomor surat. Di balik layar, draft disimpan di cache
 *  dengan key yang mengandung id user, jadi draft satu operator tidak
 *  pernah muncul di form operator lain. Ini pengganti session() yang
 *  dipakai versi Blade.
 *
 *  Urutan yang benar di layar ini: buat nomor surat dulu, unggah dokumen,
 *  baru simpan kendaraan. Backend menolak menyimpan tanpa surat_id dan
 *  tanpa foto_dokumen, jadi urutan lain hanya menghasilkan pesan galat.
 *
 *  ── SOAL FOTO CROP ────────────────────────────────────────────────────────
 *
 *  Kolom foto_cropped berisi data URL dari canvas, bukan nama file. Backend
 *  mengubahnya menjadi file dan menyimpannya sebagai foto utama kendaraan.
 *  Field ini tidak ada di form; ia dipakai lewat tombol potong di bawah.
 */

const route = useRoute()
const router = useRouter()
const toast = useToastStore()

const isEdit = computed(() => Boolean(route.params.id))

const form = reactive({
    plat_nomor: '',
    merk: '',
    model: '',
    warna: '',
    tahun: new Date().getFullYear(),
    kapasitas: 7,
    harga_default: 0,
    harga_kontrak: 0,
    no_mesin: '',
    no_rangka: '',
    km_saat_ini: 0,
    status: 'Tersedia',
})

const fieldErrors = ref({})
const submitting = ref(false)
const loading = ref(false)

/* Backend menerima empat nilai. "Sedang Disewa" sengaja ikut ditampilkan:
 * itulah status yang dipakai updateStatus() ketika pesanan aktif, jadi
 * operator harus bisa mengembalikannya kalau status kendaraan tidak sinkron dengan reality. */
const OPSI_STATUS = ['Tersedia', 'Masuk Bengkel', 'Sedang Disewa', 'Nonaktif']

/* ── Surat + dokumen ──────────────────────────────────────────────────── */

const draftSurat = ref(null)
const dokumen = ref(null)
const namaDokumen = ref('')
const membuatSurat = ref(false)

/* Tombol nonaktif begitu surat sudah ada: endpoint-nya idempoten, jadi
 * menekan dua kali tidak menghasilkan nomor baru -- tapi melihat nomor
 *surat yang sama dua kali tetap membingungkan. */
const buatDokumenDisabled = computed(() => Boolean(draftSurat.value) || membuatSurat.value)

async function buatNomorSurat() {
    membuatSurat.value = true

    try {
        const { data } = await api.post('/armada/create-dokumen')

        draftSurat.value = {
            id: data.surat_id,
            nomor_surat: data.nomor_surat,
        }

        toast.success(`Nomor surat ${data.nomor_surat} dibuat.`)
    } catch (e) {
        toast.error(readError(e).message)
    } finally {
        membuatSurat.value = false
    }
}

function onPickDokumen(event) {
    const file = event.target.files?.[0] || null

    dokumen.value = file
    namaDokumen.value = file ? file.name : ''
}

/* ── Kirim ────────────────────────────────────────────────────────────── */

async function onSubmit() {
    if (submitting.value) return

    submitting.value = true
    fieldErrors.value = {}

    const fd = new FormData()

    for (const [key, value] of Object.entries(form)) {
        if (value === '' || value === null || value === undefined) continue
        fd.append(key, String(value))
    }

    if (draftSurat.value) {
        fd.append('surat_id', String(draftSurat.value.id))
    }

    if (dokumen.value) {
        fd.append('foto_dokumen', dokumen.value)
    }

    try {
        if (isEdit.value) {
            /* Sama seperti di form pesanan: PUT lewat FormData tidak bisa
             * membedakan field yang tidak dikirim dari field yang
             * dikosongkan, jadi method override dipakai. */
            fd.append('_method', 'PUT')

            const { data } = await api.post(`/armada/${route.params.id}`, fd)
            toast.success(data.message || 'Data kendaraan berhasil diperbarui.')

            router.push(`/armada/${route.params.id}`)
        } else {
            const { data } = await api.post('/armada', fd)
            toast.success(data.message || 'Data kendaraan berhasil ditambahkan.')

            /* store() mengembalikan objek mobil, bukan redirect_path, jadi
             * tujuan dibangun dari id yang baru dibuat. */
            router.push(`/armada/${data.car?.id}`)
        }
    } catch (e) {
        const detail = readError(e)

        fieldErrors.value = detail.fieldErrors
        toast.error(detail.message)
    } finally {
        submitting.value = false
    }
}

/* ── Muat ─────────────────────────────────────────────────────────────── */

async function load() {
    loading.value = true

    try {
        if (isEdit.value) {
            const { data } = await api.get(`/armada/${route.params.id}/edit`)

            Object.assign(form, {
                plat_nomor: data.car.plat_nomor || '',
                merk: data.car.merk || '',
                model: data.car.model || '',
                warna: data.car.warna || '',
                tahun: data.car.tahun || new Date().getFullYear(),
                kapasitas: data.car.kapasitas || 0,
                harga_default: data.car.harga_default || 0,
                harga_kontrak: data.car.harga_kontrak || 0,
                no_mesin: data.car.no_mesin || '',
                no_rangka: data.car.no_rangka || '',
                km_saat_ini: data.car.km_saat_ini || 0,
                status: data.car.status || 'Tersedia',
            })
        } else {
            const { data } = await api.get('/armada/create')
            draftSurat.value = data.draftSurat || null
        }
    } catch (e) {
        toast.error(readError(e).message)
    } finally {
        loading.value = false
    }
}

onMounted(load)
</script>

<template>
    <div class="max-w-4xl mx-auto px-4 md:px-8">
        <RouterLink
            :to="isEdit ? `/armada/${route.params.id}` : { name: 'armada.index' }"
            class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-brand-600 transition-colors mb-1.5"
        >
            <span class="material-symbols-outlined ms-icon text-[16px]">arrow_back</span>
            {{ isEdit ? 'Kembali ke detail kendaraan' : 'Kembali ke daftar armada' }}
        </RouterLink>

        <h1 class="text-2xl font-bold text-slate-900">
            {{ isEdit ? 'Ubah Kendaraan' : 'Tambah Kendaraan' }}
        </h1>
        <p class="text-sm text-slate-500 mt-1 mb-6">
            {{ isEdit
                ? 'Perubahan tidak akan mengubah riwayat pesanan yang sudah berjalan.'
                : 'Surat pelepasan hak kendaraan dan dokumennya wajib dibuat sebelum disimpan.' }}
        </p>

        <form class="space-y-5" @submit.prevent="onSubmit">
            <!-- Surat & dokumen -->
            <section class="pf-card p-5">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-1">
                    Surat Pelepasan Hak Kendaraan
                </h2>
                <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                    Nomor surat dibuat sekali dan langsung dipakai sebagai data kendaraan ini.
                    Pembuatan bersifat idempoten per pengguna -- menekan tombol berulang
                    tidak membuat nomor baru.
                </p>

                <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="flex-1">
                        <label class="form-label" for="nomor-surat">Nomor Surat (PHK)</label>
                        <input
                            id="nomor-surat"
                            type="text"
                            readonly
                            class="form-input bg-slate-50 font-mono"
                            :value="draftSurat?.nomor_surat || ''"
                            placeholder="Belum dibuat"
                        />
                    </div>

                    <button
                        type="button"
                        class="btn-secondary shrink-0"
                        :disabled="buatDokumenDisabled"
                        @click="buatNomorSurat"
                    >
                        <span class="material-symbols-outlined ms-icon text-[18px]">description</span>
                        {{ draftSurat ? 'Sudah dibuat' : 'Buat Nomor Surat' }}
                    </button>
                </div>

                <div v-if="draftSurat" class="mt-3 text-xs text-slate-500">
                    Dibuat {{ formatTanggalPanjang(draftSurat.created_at) }}
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100">
                    <label class="form-label" for="foto_dokumen">
                        Dokumen Pelepasan Hak Kendaraan
                    </label>
                    <input
                        id="foto_dokumen"
                        type="file"
                        accept="image/*,.pdf"
                        class="text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-medium file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
                        @change="onPickDokumen"
                    />
                    <p class="text-[11px] text-slate-400 mt-1">
                        {{ namaDokumen || 'JPG, PNG, atau PDF. Maksimal 5 MB.' }}
                    </p>
                    <p v-if="fieldErrors.foto_dokumen" class="text-xs text-rose-600 mt-1">
                        {{ fieldErrors.foto_dokumen }}
                    </p>
                </div>
            </section>

            <!-- Identitas kendaraan -->
            <section class="pf-card p-5">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                    Identitas Kendaraan
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="plat_nomor">Plat Nomor *</label>
                        <input
                            id="plat_nomor"
                            v-model="form.plat_nomor"
                            type="text"
                            required
                            maxlength="15"
                            class="form-input uppercase"
                            :class="{ 'form-input-error': fieldErrors.plat_nomor }"
                            placeholder="B 1234 XYZ"
                        />
                        <p v-if="fieldErrors.plat_nomor" class="text-xs text-rose-600 mt-1">
                            {{ fieldErrors.plat_nomor }}
                        </p>
                    </div>

                    <div>
                        <label class="form-label" for="merk">Merk *</label>
                        <input
                            id="merk"
                            v-model="form.merk"
                            type="text"
                            required
                            class="form-input"
                            :class="{ 'form-input-error': fieldErrors.merk }"
                            placeholder="Toyota"
                        />
                        <p v-if="fieldErrors.merk" class="text-xs text-rose-600 mt-1">
                            {{ fieldErrors.merk }}
                        </p>
                    </div>

                    <div>
                        <label class="form-label" for="model">Model *</label>
                        <input
                            id="model"
                            v-model="form.model"
                            type="text"
                            required
                            class="form-input"
                            :class="{ 'form-input-error': fieldErrors.model }"
                            placeholder="Innova Reborn"
                        />
                        <p v-if="fieldErrors.model" class="text-xs text-rose-600 mt-1">
                            {{ fieldErrors.model }}
                        </p>
                    </div>

                    <div>
                        <label class="form-label" for="warna">Warna</label>
                        <input id="warna" v-model="form.warna" type="text" class="form-input" />
                    </div>

                    <div>
                        <label class="form-label" for="tahun">Tahun *</label>
                        <input
                            id="tahun"
                            v-model.number="form.tahun"
                            type="number"
                            required
                            class="form-input"
                            :class="{ 'form-input-error': fieldErrors.tahun }"
                        />
                        <p v-if="fieldErrors.tahun" class="text-xs text-rose-600 mt-1">
                            {{ fieldErrors.tahun }}
                        </p>
                    </div>

                    <div>
                        <label class="form-label" for="kapasitas">Kapasitas (orang) *</label>
                        <input
                            id="kapasitas"
                            v-model.number="form.kapasitas"
                            type="number"
                            min="1"
                            required
                            class="form-input"
                            :class="{ 'form-input-error': fieldErrors.kapasitas }"
                        />
                    </div>

                    <div>
                        <label class="form-label" for="km_saat_ini">Odometer (km) *</label>
                        <input
                            id="km_saat_ini"
                            v-model.number="form.km_saat_ini"
                            type="number"
                            min="0"
                            required
                            class="form-input text-right tabular-nums"
                            :class="{ 'form-input-error': fieldErrors.km_saat_ini }"
                        />
                    </div>
                </div>
            </section>

            <!-- Nomor mesin & rangka -->
            <section class="pf-card p-5">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                    Nomor Mesin &amp; Rangka
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="no_mesin">Nomor Mesin *</label>
                        <input
                            id="no_mesin"
                            v-model="form.no_mesin"
                            type="text"
                            required
                            class="form-input font-mono text-sm"
                            :class="{ 'form-input-error': fieldErrors.no_mesin }"
                        />
                        <p v-if="fieldErrors.no_mesin" class="text-xs text-rose-600 mt-1">
                            {{ fieldErrors.no_mesin }}
                        </p>
                    </div>

                    <div>
                        <label class="form-label" for="no_rangka">Nomor Rangka *</label>
                        <input
                            id="no_rangka"
                            v-model="form.no_rangka"
                            type="text"
                            required
                            class="form-input font-mono text-sm"
                            :class="{ 'form-input-error': fieldErrors.no_rangka }"
                        />
                        <p v-if="fieldErrors.no_rangka" class="text-xs text-rose-600 mt-1">
                            {{ fieldErrors.no_rangka }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Harga & status -->
            <section class="pf-card p-5">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                    Harga &amp; Status
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label" for="harga_default">Harga Harian (Rp) *</label>
                        <input
                            id="harga_default"
                            v-model.number="form.harga_default"
                            type="number"
                            min="0"
                            step="1000"
                            required
                            class="form-input text-right tabular-nums"
                            :class="{ 'form-input-error': fieldErrors.harga_default }"
                        />
                    </div>

                    <div>
                        <label class="form-label" for="harga_kontrak">Harga Kontrak (Rp) *</label>
                        <input
                            id="harga_kontrak"
                            v-model.number="form.harga_kontrak"
                            type="number"
                            min="0"
                            step="1000"
                            required
                            class="form-input text-right tabular-nums"
                            :class="{ 'form-input-error': fieldErrors.harga_kontrak }"
                        />
                    </div>

                    <div>
                        <label class="form-label" for="status">Status *</label>
                        <select id="status" v-model="form.status" class="form-input">
                            <option v-for="s in OPSI_STATUS" :key="s" :value="s">
                                {{ s }}
                            </option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Status "Sedang Disewa" biasanya berubah sendiri mengikuti
                            status rental pesanan yang sedang berjalan.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Aksi -->
            <div class="flex items-center justify-end gap-2 pb-6">
                <RouterLink
                    :to="isEdit ? `/armada/${route.params.id}` : { name: 'armada.index' }"
                    class="btn-secondary"
                >
                    Batal
                </RouterLink>

                <button type="submit" class="btn-primary" :disabled="submitting || loading">
                    <span
                        v-if="submitting"
                        class="material-symbols-outlined ms-icon text-[18px] animate-spin"
                    >
                        progress_activity
                    </span>
                    {{ isEdit ? 'Simpan Perubahan' : 'Simpan Kendaraan' }}
                </button>
            </div>
        </form>
    </div>
</template>
