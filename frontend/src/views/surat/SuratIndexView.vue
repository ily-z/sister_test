<script setup>
import { computed, ref } from 'vue'

import DataState from '@/components/DataState.vue'
import EmptyState from '@/components/EmptyState.vue'

import { useAuthStore } from '@/stores/auth'
import { useConfirmStore } from '@/stores/confirm'
import { useToastStore } from '@/stores/toast'

import { api, readError } from '@/lib/api'

/*
 * ============================================================================
 *  SURAT MENYURAT
 * ============================================================================
 *
 *  Meniru backend/resources/views/surat-menyurat/index.blade.php.
 *
 *  --- BENTUK RESPONSNYA BUKAN DAFTAR FLAT -------------------------------
 *
 *  GET /surat-menyurat mengembalikan `grouped`, yaitu objek yang dikunci jenis
 *  dokumen. Tiap nilai punya dua anggota: `main` (surat tanpa subkategori)
 *  dan `children` (surat dengan subkategori). Bentuk ini bukan pilihan
 *  frontend -- ia sudah dibangun di controller supaya urutan dan pengelompokan
 *  tidak diulang di dua tempat.
 *
 *  Karena `main` bisa null (jenis dokumen yang semua nomornya ber-subkategori),
 *  setiap akses ke sana wajib aman. Mengambil `grouped[jenis].main.nomor_surat`
 *  langsung akan meledak di satu jenis dokumen saja, dan itu sulit dilacak.
 *
 *  --- HAPUS BANYAK SEKALI JALAN -------------------------------------------
 *
 *  POST /surat-menyurat/bulk-delete menerima { ids: [] }. Ada jadi tidak ada
 *  endpoint hapus satuan; satu item dikirim sebagai array satu elemen.
 *
 *  --- NOMOR SURAT DIBUAT SERVER ------------------------------------------
 *
 *  Frontend hanya mengirim jenis_dokumen, kode, dan subkategori. Nomor
 *  suratnya ({urut}/SRT/{kode}/JRB/{bulan}/{tahun}) dibuat di dalam
 *  DB::transaction() supaya lockForUpdate() benar-benar mengunci baris
 *  terakhir -- lihat generateNomorSurat() di backend. Jadi form ini tidak
 *  punya kolom nomor, dan tidak akan ada kolom itu.
 */

const auth = useAuthStore()
const confirm = useConfirmStore()
const toast = useToastStore()

const memuat = ref(true)
const error = ref('')
const grouped = ref({})
const subkategorisByJenis = ref({})

/* Jenis dokumen yang subkategorinya sedang ditambahkan. null = dialog tutup. */
const dialogSub = ref(null)
const simpanSub = ref(false)
const galatSub = ref({})
const formSub = ref({ jenis_dokumen: '', kode: '', subkategori: '' })

/* Id yang dicentang, untuk bilah aksi di bawah layar. */
const terpilih = ref([])

/* Jenis dokumen yang subnya dibuka. Disimpan terpisah dari `grouped` supaya
 * state tampilan tidak ikut hilang saat data dimuat ulang. */
const terbuka = ref({})

/* -------------------------------------------------------------------------
 *  MUAT
 * ---------------------------------------------------------------------- */

async function muat() {
    memuat.value = true
    error.value = ''

    try {
        const { data } = await api.get('/surat-menyurat')

        grouped.value = data.grouped || {}
        subkategorisByJenis.value = data.subkategorisByJenis || {}

        /* Id yang dicentang dibuang kalau barisnya sudah hilang, supaya
         * Refresh setelah penghapusan tidak menyisakan checkbox nyala untuk
         * data yang tidak ada. */
        const ada = new Set(semuaId.value)

        terpilih.value = terpilih.value.filter((id) => ada.has(id))
    } catch (e) {
        error.value = readError(e).message
    } finally {
        memuat.value = false
    }
}

/* -------------------------------------------------------------------------
 *  NILAI
 * ---------------------------------------------------------------------- */

/*
 * `grouped` adalah objek dengan urutan kunci seperti yang dikirim server.
 * Object.entries mempertahankan urutan itu, dan urutannya memang bermakna:
 * pengelompokan dibuat dengan orderBy di controller.
 */
const daftarKelompok = computed(() =>
    Object.entries(grouped.value).map(([jenis, isi]) => ({
        jenis,
        main: isi.main || null,
        children: isi.children || [],
    }))
)

const semuaId = computed(() =>
    daftarKelompok.value.flatMap((g) => [
        ...(g.main ? [g.main.id] : []),
        ...g.children.map((c) => c.id),
    ])
)

const jumlahTerpilih = computed(() => terpilih.value.length)
const semuaTercentang = computed(
    () => semuaId.value.length > 0 && terpilih.value.length === semuaId.value.length
)

/* Nomor surat kosong berarti entri itu belum dipakai untuk membuat dokumen
 * apa pun. Angka urutnya sudah dialokasikan, tapi belum ada invoice yang
 * mengambilnya. */
function tampilNomor(surat) {
    return surat?.nomor_surat || null
}

function adalahDraft(surat) {
    return !tampilNomor(surat)
}

/*
 * Kunci untuk state `terbuka`. Nama jenis dokumen bisa mengandung spasi,
 * tanda baca, dan karakter non-ASCII, jadi tidak aman dipakai langsung
 * sebagai kunci objek.
 */
function kunciTerbuka(jenis) {
    return Boolean(terbuka.value[encodeURIComponent(jenis)])
}

function alihkanTerbuka(jenis) {
    const kunci = encodeURIComponent(jenis)

    terbuka.value = { ...terbuka.value, [kunci]: !terbuka.value[kunci] }
}

/* -------------------------------------------------------------------------
 *  CENTANG
 * ---------------------------------------------------------------------- */

function alihkanSatu(id, checked) {
    if (checked) {
        if (!terpilih.value.includes(id)) terpilih.value = [...terpilih.value, id]
    } else {
        terpilih.value = terpilih.value.filter((x) => x !== id)
    }
}

function alihkanSemua() {
    terpilih.value = semuaTercentang.value ? [] : [...semuaId.value]
}

async function hapusTerpilih() {
    if (!terpilih.value.length) return

    const ok = await confirm.ask(
        `Hapus ${terpilih.value.length} entri nomor surat? ` +
            'Invoice yang sudah memakai nomor ini tidak ikut terhapus, tapi nomornya ' +
            'akan hilang dari daftar.',
        { confirmLabel: 'Ya, hapus', tone: 'danger' }
    )

    if (!ok) return

    try {
        const { data } = await api.post('/surat-menyurat/bulk-delete', {
            ids: terpilih.value,
        })

        toast.success(data.message || 'Nomor surat berhasil dihapus.')

        terpilih.value = []
        muat()
    } catch (e) {
        toast.error(readError(e).message)
    }
}

/* -------------------------------------------------------------------------
 *  TAMBAH SUBKATEGORI
 * ---------------------------------------------------------------------- */

function bukaDialogSub(jenis) {
    const utama = grouped.value[jenis]?.main

    dialogSub.value = jenis
    galatSub.value = {}

    /* Kode diambil dari surat utama jenis itu. Kalau belum ada surat utama,
     * field dibiarkan kosong supaya operator mengetiknya sendiri. */
    formSub.value = {
        jenis_dokumen: jenis,
        kode: utama?.kode || '',
        subkategori: '',
    }
}

function tutupDialogSub() {
    if (simpanSub.value) return

    dialogSub.value = null
    galatSub.value = {}
}

const daftarSubkategori = computed(() => {
    if (!dialogSub.value) return []

    return subkategorisByJenis.value[dialogSub.value] || []
})

async function simpanSubkategori() {
    if (simpanSub.value) return

    simpanSub.value = true
    galatSub.value = {}

    try {
        const { data } = await api.post('/surat-menyurat/store-sub', {
            jenis_dokumen: formSub.value.jenis_dokumen,
            kode: formSub.value.kode,
            subkategori: formSub.value.subkategori,
        })

        toast.success(data.message || 'Subkategori berhasil ditambahkan.')

        dialogSub.value = null

        /* Sub baru langsung dibuka supaya terlihat hasilnya tanpa klik lagi. */
        terbuka.value = { ...terbuka.value, [encodeURIComponent(data.surat.jenis_dokumen)]: true }

        muat()
    } catch (e) {
        const errors = e?.response?.data?.errors

        if (errors && Object.keys(errors).length) {
            galatSub.value = errors
        } else {
            toast.error(readError(e).message)
        }
    } finally {
        simpanSub.value = false
    }
}

/* -------------------------------------------------------------------------
 *  KE ARSIP
 * ---------------------------------------------------------------------- */

/*
 * Arsip difilter lewat query string, bukan path. Endpoint arsip() membaca
 * jenis_dokumen, subkategori, bulan, dan search sebagai query, jadi
 * meneruskannya sebagai query menjaga satu sumber kebenaran.
 */
function keArsip(jenis, subkategori) {
    const query = { jenis_dokumen: jenis }

    if (subkategori) query.subkategori = subkategori

    return { path: '/surat-menyurat/arsip', query }
}

const bisaTambah = computed(() => auth.can('bookings.edit'))
const bisaHapus = computed(() => auth.can('bookings.delete'))

muat()
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8 pb-28 md:pb-8">
        <!-- Kepala -->
        <div
            class="flex flex-col md:flex-row md:justify-between md:items-end gap-4 mb-6"
        >
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Surat Menyurat</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Kelola nomor surat dan dokumen administrasi.
                </p>
            </div>

            <RouterLink v-if="bisaTambah" to="/surat-menyurat/create" class="btn-primary">
                <span class="material-symbols-outlined ms-icon text-[18px]">add</span>
                Tambah Nomor Surat
            </RouterLink>
        </div>

        <DataState
            :loading="memuat"
            :error="error"
            :is-empty="false"
            @retry="muat"
        >
            <!-- Pohon jenis dokumen -->
            <div v-if="daftarKelompok.length" class="space-y-3">
                <section
                    v-for="g in daftarKelompok"
                    :key="g.jenis"
                    class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 transition-colors"
                        :class="g.children.length ? 'cursor-pointer hover:bg-slate-50' : ''"
                        @click="g.children.length && alihkanTerbuka(g.jenis)"
                    >
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <span
                                class="material-symbols-outlined ms-icon text-slate-400 transition-transform duration-200 text-[20px]"
                                :class="[
                                    g.children.length ? '' : 'invisible',
                                    kunciTerbuka(g.jenis) ? 'rotate-90' : '',
                                ]"
                            >
                                chevron_right
                            </span>

                            <input
                                v-if="g.main"
                                type="checkbox"
                                :checked="terpilih.includes(g.main.id)"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer shrink-0"
                                :aria-label="`Pilih ${g.jenis}`"
                                @click.stop
                                @change="alihkanSatu(g.main.id, $event.target.checked)"
                            />

                            <div class="flex flex-col min-w-0">
                                <span class="font-mono text-sm font-medium text-slate-800 truncate">
                                    <template v-if="tampilNomor(g.main)">
                                        {{ tampilNomor(g.main) }}
                                    </template>
                                    <template v-else>
                                        <span class="font-sans text-slate-700">{{ g.jenis }}</span>
                                    </template>
                                </span>

                                <span class="text-xs text-slate-400">
                                    {{ g.jenis }}

                                    <span
                                        v-if="adalahDraft(g.main)"
                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700 ml-1"
                                    >
                                        Draft
                                    </span>

                                    <span
                                        v-if="g.children.length"
                                        class="text-slate-300 ml-1.5"
                                    >
                                        {{ g.children.length }} subkategori
                                    </span>
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                v-if="bisaTambah"
                                type="button"
                                class="text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1"
                                @click.stop="bukaDialogSub(g.jenis)"
                            >
                                <span class="material-symbols-outlined ms-icon text-[16px]">add</span>
                                Sub
                            </button>

                            <RouterLink
                                :to="keArsip(g.jenis)"
                                class="text-xs font-medium text-slate-500 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1"
                                @click.stop
                            >
                                <span class="material-symbols-outlined ms-icon text-[16px]">archive</span>
                                Arsip
                            </RouterLink>
                        </div>
                    </div>

                    <!-- Subkategori -->
                    <div
                        v-if="g.children.length"
                        class="divide-y divide-slate-50 border-t border-slate-100"
                        :class="kunciTerbuka(g.jenis) ? '' : 'hidden'"
                    >
                        <div
                            v-for="c in g.children"
                            :key="c.id"
                            class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 transition-colors hover:bg-slate-50"
                        >
                            <div class="flex items-center gap-3 min-w-0 pl-8 flex-1">
                                <span
                                    class="material-symbols-outlined ms-icon text-slate-300 text-[16px] shrink-0"
                                >
                                    subdirectory_arrow_right
                                </span>

                                <input
                                    type="checkbox"
                                    :checked="terpilih.includes(c.id)"
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer shrink-0"
                                    :aria-label="`Pilih ${c.subkategori}`"
                                    @change="alihkanSatu(c.id, $event.target.checked)"
                                />

                                <div class="flex flex-col min-w-0">
                                    <span class="font-mono text-sm text-slate-700 truncate">
                                        <template v-if="tampilNomor(c)">{{ tampilNomor(c) }}</template>
                                        <template v-else>
                                            <span class="font-sans text-slate-500 italic">Draft</span>
                                        </template>
                                    </span>

                                    <span class="text-xs text-slate-400">{{ c.subkategori }}</span>
                                </div>
                            </div>

                            <RouterLink
                                :to="keArsip(c.jenis_dokumen, c.subkategori)"
                                class="text-xs font-medium text-slate-500 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1 shrink-0"
                            >
                                <span class="material-symbols-outlined ms-icon text-[16px]">archive</span>
                                Arsip
                            </RouterLink>
                        </div>
                    </div>
                </section>
            </div>

            <EmptyState
                v-else
                icon="description"
                title="Belum ada nomor surat"
                description="Klik Tambah Nomor Surat untuk membuat entri pertama."
            />
        </DataState>

        <!-- Bilah aksi manyak -->
        <div
            v-if="jumlahTerpilih && bisaHapus"
            class="fixed bottom-24 md:bottom-6 left-4 right-4 md:left-1/2 md:right-auto md:-translate-x-1/2 z-40
                   flex items-center justify-between md:justify-center gap-4 bg-slate-900 text-white
                   px-5 py-3 rounded-2xl shadow-2xl"
        >
            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input
                    type="checkbox"
                    :checked="semuaTercentang"
                    class="rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500 cursor-pointer"
                    @change="alihkanSemua"
                />
                <span class="font-medium">Pilih semua</span>
            </label>

            <span class="text-sm">
                {{ jumlahTerpilih }} entri dipilih
            </span>

            <button
                type="button"
                class="text-xs font-semibold bg-rose-600 hover:bg-rose-700 px-3 py-1.5 rounded-lg transition-colors"
                @click="hapusTerpilih"
            >
                Hapus yang Dipilih
            </button>

            <button
                type="button"
                class="text-slate-400 hover:text-white transition-colors md:hidden"
                aria-label="Tutup bilah aksi"
                @click="terpilih = []"
            >
                <span class="material-symbols-outlined ms-icon text-[18px]">close</span>
            </button>
        </div>

        <!-- Dialog tambah subkategori -->
        <div
            v-if="dialogSub"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
            @click.self="tutupDialogSub"
        >
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                <div
                    class="flex items-center justify-between px-5 py-4 border-b border-slate-100"
                >
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900">Tambah Subkategori</h3>
                        <p class="text-xs text-slate-500 mt-0.5 truncate">{{ dialogSub }}</p>
                    </div>

                    <button
                        type="button"
                        class="p-1 rounded text-slate-400 hover:text-slate-700 transition-colors"
                        aria-label="Tutup"
                        @click="tutupDialogSub"
                    >
                        <span class="material-symbols-outlined ms-icon text-[18px]">close</span>
                    </button>
                </div>

                <form class="px-5 py-4 space-y-3.5" @submit.prevent="simpanSubkategori">
                    <div>
                        <label for="subKode" class="form-label">
                            Kode Dokumen
                            <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="subKode"
                            v-model="formSub.kode"
                            type="text"
                            maxlength="255"
                            class="form-input"
                            :class="galatSub.kode ? 'form-input-error' : ''"
                            placeholder="Contoh: BN, ST, INV"
                        />
                        <p v-if="galatSub.kode" class="text-xs text-rose-600 mt-1">
                            {{ galatSub.kode }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            Kode harus sama dengan surat utama jenis ini, supaya nomor suratnya
                            berurutan.
                        </p>
                    </div>

                    <div>
                        <label for="subNama" class="form-label">
                            Nama Subkategori
                            <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="subNama"
                            v-model="formSub.subkategori"
                            type="text"
                            maxlength="255"
                            class="form-input"
                            :class="galatSub.subkategori ? 'form-input-error' : ''"
                            placeholder="Contoh: Perlengkapan, Operasional"
                        />
                        <p v-if="galatSub.subkategori" class="text-xs text-rose-600 mt-1">
                            {{ galatSub.subkategori }}
                        </p>
                    </div>

                    <p
                        v-if="daftarSubkategori.length"
                        class="text-xs text-slate-500 bg-slate-50 px-3 py-2 rounded-lg"
                    >
                        Sudah ada:
                        <span class="font-medium text-slate-600">
                            {{ daftarSubkategori.join(', ') }}
                        </span>
                    </p>

                    <div
                        class="flex items-center justify-end gap-2 pt-1"
                    >
                        <button
                            type="button"
                            class="btn-secondary text-xs"
                            :disabled="simpanSub"
                            @click="tutupDialogSub"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="btn-primary text-xs"
                            :disabled="simpanSub"
                        >
                            <span
                                v-if="simpanSub"
                                class="material-symbols-outlined ms-icon text-[16px] animate-spin"
                            >
                                progress_activity
                            </span>
                            {{ simpanSub ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>