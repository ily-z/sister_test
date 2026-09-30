<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useToastStore } from '@/stores/toast'

import { api, readError } from '@/lib/api'

/*
 * ============================================================================
 *  TAMBAH NOMOR SURAT
 * ============================================================================
 *
 *  Meniru backend/resources/views/surat-menyurat/create.blade.php.
 *
 *  --- TIDAK ADA INPUT NOMOR SURAT ----------------------------------------
 *
 *  Nomor surat dibuat server dari tiga bagian: no_urut, bulan romawi, dan
 *  tahun berjalan. Yang dikirim halaman ini hanya jenis dokumen, kode, dan
 *  subkategori. Menampilkan pratinjau format -- seperti `[001]/SRT/[Kode]/JRB/
 *  [Bulan]/[Tahun]` -- tetap berguna supaya operator paham urutannya, tapi
 *  angka aslinya hanya ada setelah disimpan.
 *
 *  --- PILIH ATAU KETIK ---------------------------------------------------
 *
 *  GET /surat-menyurat/create mengembalikan daftar jenis dokumen yang sudah
 *  ada. Memilih "Jenis Baru" memunculkan kolom teks, karena jenis dokumen
 *  baru tidak bisa diketahui dari daftar itu. Nilainya sendiri dikirim lewat
 *  field `jenis_dokumen` yang sama dalam dua-duanya -- backend tidak tahu
 *  maupun perlu tahu dari mana asalnya.
 *
 *  --- SUBKATEGORI OPSIONAL, TAPI BOLEH DIISI NANTI -------------------------
 *
 *  Subkategori di halaman ini opsional (store()), sedangkan dari halaman
 *  daftar harus diisi (storeSub()). Perbedaannya disengaja di backend:
 *  storeSub() menolak duplikat pada satu jenis dokumen, jadi jalurnya hanya
 *  bisa dipakai untuk menambah, bukan membuat entri pertama.
 */

const router = useRouter()
const toast = useToastStore()

const memuat = ref(true)
const mengirim = ref(false)
const galat = ref({})

const jenisTersedia = ref([])
const form = ref({
    /* null = pilih dari daftar, string = sedang mengetik yang baru. */
    modeJenis: 'pilih',
    jenisTerpilih: '',
    jenisBaru: '',
    kode: '',
    subkategori: '',
})

onMounted(async () => {
    try {
        const { data } = await api.get('/surat-menyurat/create')

        jenisTersedia.value = data.jenisDokumen || []
    } catch (e) {
        galat.value = { jenis_dokumen: readError(e).message }
    } finally {
        memuat.value = false
    }
})

/* Nilai yang benar-benar dikirim. Dipisah dari state form supaya aturan
 * pemilihan jenis tidak tersebar di beberapa tempat. */
const jenisDiminta = computed(() =>
    form.value.modeJenis === 'baru' ? form.value.jenisBaru.trim() : form.value.jenisTerpilih
)

/*
 * Pratinjau format. no_urut selalu tiga digit, dan bulan memakai angka romawi
 * supaya bentuknya sama persis dengan yang dihasilkan generateNomorSurat().
 */
const ROMAWI = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII']

const pratinjauNomor = computed(() => {
    const kode = form.value.kode.trim() || '[Kode]'
    const sekarang = new Date()
    const bulan = ROMAWI[sekarang.getMonth()]

    return `[001]/SRT/${kode}/JRB/${bulan}/${sekarang.getFullYear()}`
})

const bisaSimpan = computed(
    () => Boolean(jenisDiminta.value) && Boolean(form.value.kode.trim())
)

async function kirim() {
    if (mengirim.value) return

    mengirim.value = true
    galat.value = {}

    try {
        const { data } = await api.post('/surat-menyurat', {
            jenis_dokumen: jenisDiminta.value,
            kode: form.value.kode.trim(),
            subkategori: form.value.subkategori.trim() || null,
        })

        toast.success(data.message || 'Nomor surat berhasil dibuat.')

        router.push('/surat-menyurat')
    } catch (e) {
        const errors = e?.response?.data?.errors

        if (errors && Object.keys(errors).length) {
            galat.value = errors
        } else {
            toast.error(readError(e).message)
        }
    } finally {
        mengirim.value = false
    }
}
</script>

<template>
    <div class="max-w-2xl mx-auto px-4 md:px-8 py-6">
        <!-- Kepala -->
        <div class="flex items-center gap-3 mb-6">
            <RouterLink
                to="/surat-menyurat"
                class="w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors shrink-0"
                aria-label="Kembali ke daftar surat"
            >
                <span class="material-symbols-outlined ms-icon text-[20px] text-slate-600">
                    arrow_back
                </span>
            </RouterLink>

            <div class="min-w-0">
                <h1 class="text-xl font-bold text-slate-900">Tambah Nomor Surat</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Buat entri nomor surat baru. Nomor akan dibuat otomatis.
                </p>
            </div>
        </div>

        <!-- Muat daftar jenis -->
        <div
            v-if="memuat"
            class="pf-card px-4 py-10 text-center"
        >
            <span class="material-symbols-outlined ms-icon text-[24px] animate-spin text-slate-400">
                progress_activity
            </span>
            <p class="text-sm text-slate-500 mt-2">Memuat jenis dokumen...</p>
        </div>

        <form v-else class="pf-card p-5 space-y-5" @submit.prevent="kirim">
            <!-- Jenis dokumen -->
            <div>
                <label class="form-label" for="jenisDokumen">
                    Jenis Dokumen <span class="text-rose-500">*</span>
                </label>

                <div class="flex flex-col sm:flex-row gap-2">
                    <select
                        v-if="form.modeJenis === 'pilih'"
                        id="jenisDokumen"
                        v-model="form.jenisTerpilih"
                        class="form-input"
                        :class="galat.jenis_dokumen ? 'form-input-error' : ''"
                    >
                        <option value="">Pilih jenis dokumen...</option>
                        <option v-for="j in jenisTersedia" :key="j" :value="j">{{ j }}</option>
                    </select>

                    <input
                        v-else
                        id="jenisDokumen"
                        v-model="form.jenisBaru"
                        type="text"
                        maxlength="255"
                        class="form-input"
                        :class="galat.jenis_dokumen ? 'form-input-error' : ''"
                        placeholder="Ketik jenis dokumen baru"
                    />

                    <button
                        type="button"
                        class="btn-secondary text-xs shrink-0"
                        @click="form.modeJenis = form.modeJenis === 'pilih' ? 'baru' : 'pilih'"
                    >
                        <span class="material-symbols-outlined ms-icon text-[16px]">
                            {{ form.modeJenis === 'pilih' ? 'add' : 'list' }}
                        </span>
                        {{ form.modeJenis === 'pilih' ? 'Jenis Baru' : 'Dari Daftar' }}
                    </button>
                </div>

                <p v-if="galat.jenis_dokumen" class="text-xs text-rose-600 mt-1">
                    {{ galat.jenis_dokumen }}
                </p>
            </div>

            <!-- Kode -->
            <div>
                <label class="form-label" for="kode">
                    Kode Dokumen <span class="text-rose-500">*</span>
                </label>

                <input
                    id="kode"
                    v-model="form.kode"
                    type="text"
                    maxlength="255"
                    class="form-input font-mono"
                    :class="galat.kode ? 'form-input-error' : ''"
                    placeholder="Contoh: BN, ST, INV"
                />

                <p v-if="galat.kode" class="text-xs text-rose-600 mt-1">{{ galat.kode }}</p>
                <p class="text-xs text-slate-400 mt-1">
                    Kode singkat untuk jenis dokumen. Kode yang sama menghasilkan nomor
                    surat yang berurutan, jadi jangan pakai kode berbeda untuk satu jenis.
                </p>
            </div>

            <!-- Subkategori -->
            <div>
                <label class="form-label" for="subkategori">
                    Subkategori <span class="text-slate-400 font-normal normal-case">(opsional)</span>
                </label>

                <input
                    id="subkategori"
                    v-model="form.subkategori"
                    type="text"
                    maxlength="255"
                    class="form-input"
                    :class="galat.subkategori ? 'form-input-error' : ''"
                    placeholder="Contoh: Perlengkapan, Operasional"
                />

                <p v-if="galat.subkategori" class="text-xs text-rose-600 mt-1">
                    {{ galat.subkategori }}
                </p>
                <p class="text-xs text-slate-400 mt-1">
                    Boleh dikosongkan dan ditambahkan nanti dari halaman daftar.
                </p>
            </div>

            <!-- Pratinjau -->
            <div class="bg-slate-50 rounded-xl p-4">
                <p class="text-xs font-semibold text-slate-500 mb-2">Format Nomor Surat:</p>
                <p class="text-sm font-mono text-slate-700 break-all">{{ pratinjauNomor }}</p>
                <p class="text-xs text-slate-400 mt-2">
                    No urut, bulan, dan tahun diisi otomatis oleh server saat disimpan.
                </p>
            </div>

            <!-- Aksi -->
            <div class="flex gap-2 pt-1">
                <button type="submit" class="btn-primary flex-1" :disabled="mengirim || !bisaSimpan">
                    <span
                        v-if="mengirim"
                        class="material-symbols-outlined ms-icon text-[18px] animate-spin"
                    >
                        progress_activity
                    </span>
                    <span v-else class="material-symbols-outlined ms-icon text-[18px]">save</span>
                    {{ mengirim ? 'Menyimpan...' : 'Simpan Nomor Surat' }}
                </button>

                <RouterLink to="/surat-menyurat" class="btn-secondary">Batal</RouterLink>
            </div>
        </form>
    </div>
</template>