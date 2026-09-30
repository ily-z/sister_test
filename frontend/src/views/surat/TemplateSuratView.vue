<script setup>
import { computed, onMounted, ref } from 'vue'

import DataState from '@/components/DataState.vue'
import EmptyState from '@/components/EmptyState.vue'

import { useAuthStore } from '@/stores/auth'
import { useConfirmStore } from '@/stores/confirm'
import { useToastStore } from '@/stores/toast'

import { api, readError } from '@/lib/api'
import { formatTanggalWaktu } from '@/lib/format'

/*
 * ============================================================================
 *  TEMPLATE SURAT
 * ============================================================================
 *
 *  Boilerplate isi surat yang bisa dipakai ulang. Versi Blade menyimpan
 *  perubahan lewat Quill; di sini penyusun teksnya adalah textarea HTML biasa
 *  dengan pratinjau di sampingnya.
 *
 *  --- APAKAH QUILL DIPAKAI? ----------------------------------------------
 *
 *  Tidak. Isi template masuk ke PDF lewat dompdf, dan dompdf hanya mengenal
 *  subset CSS yang sederhana. Editor visual yang kaya justru sering
 *  menghasilkan markup yang tidak bisa dirender -- font web, flexbox, dan
 *  animasi diabaikan tanpa pesan apa pun. Melihat HTML sumbernya membuat
 *  batasannya jelas. Halaman Template Brosur memakai alasan yang sama.
 *
 *  --- SATU HALAMAN, BUKAN HALAMAN EDIT TERPISAH ---------------------------
 *
 *  Endpoint create dan edit mengembalikan { template, jenisSurat }. Isinya
 *  pendek, jadi formnya juga pendek. Memisahkan daftar dan editor hanya menambah
 *  satu klik tanpa menambah kejelasan, dan daftar template biasanya berisi
 *  beberapa baris saja.
 *
 *  --- ISI TIDAK PERNAH DI-RENDER SEBAGAI HTML DI HALAMAN INI --------------
 *
 *  Pratinjau memakai <iframe srcdoc> supaya HTML milik pengguna tidak ikut
 *  masuk ke DOM halaman ini. Kalau kontennya dipasang dengan v-html, satu
 *  <script> di dalam template akan berjalan dengan hak akses penuh di
 *  browser operator. srcdoc dengan sandbox menutup jalan itu. Alasan yang sama
 *  membuat backend tidak pernah mengembalikan konten template sebagai HTML;
 *  bandingkan dengan FileController yang memaksa attachment untuk berkas HTML.
 */

const auth = useAuthStore()
const confirm = useConfirmStore()
const toast = useToastStore()

const memuat = ref(true)
const error = ref('')
const templates = ref([])
const jenisSurat = ref([])

/* Template yang sedang disunting. null = daftar saja. */
const aktif = ref(null)
const galat = ref({})
const mengirim = ref(false)

/* Konten asli saat editor dibuka, untuk menentukan ada perubahan atau tidak. */
const asli = ref('')

onMounted(muat)

async function muat() {
    memuat.value = true
    error.value = ''

    try {
        const { data } = await api.get('/surat-menyurat/template')

        templates.value = data.templates || []
    } catch (e) {
        error.value = readError(e).message
    } finally {
        memuat.value = false
    }
}

/* -------------------------------------------------------------------------
 *  MODE KERJA
 * ---------------------------------------------------------------------- */

/* Semua opsi jenis diambil sekali dari endpoint create, lalu dipakai ulang
 * baik saat membuat baru maupun menyunting. Dua request ke endpoint yang
 * sama hanya untuk daftar dropdown tidak sepadan dengan hasilnya. */
async function muatJenisSurat() {
    if (jenisSurat.value.length) return

    try {
        const { data } = await api.get('/surat-menyurat/template/create')

        jenisSurat.value = data.jenisSurat || []
    } catch (e) {
        toast.error(readError(e).message)
    }
}

const bisaUbah = computed(() => auth.can('bookings.edit'))
const bisaHapus = computed(() => auth.can('bookings.delete'))

function buatBaru() {
    muatJenisSurat()

    aktif.value = {
        id: null,
        judul: '',
        jenis_surat: jenisSurat.value[0] || '',
        konten: '',
    }

    asli.value = ''
    galat.value = {}
}

async function sunting(template) {
    memuat.value = true

    try {
        /* Endpoint edit dipakai, bukan objek dari daftar: daftar ini tidak
         * menyertakan kontennya -- respons index sengaja hanya metadata. */
        const { data } = await api.get(`/surat-menyurat/template/${template.id}/edit`)

        jenisSurat.value = data.jenisSurat || jenisSurat.value
        aktif.value = { ...data.template }
        asli.value = data.template.konten || ''
        galat.value = {}
    } catch (e) {
        toast.error(readError(e).message)
    } finally {
        memuat.value = false
    }
}

function tutupEditor() {
    aktif.value = null
    galat.value = {}
}

const berubah = computed(() => (aktif.value?.konten || '') !== asli.value)

/* -------------------------------------------------------------------------
 *  SIMPAN
 * ---------------------------------------------------------------------- */

async function simpan() {
    if (mengirim.value || !aktif.value) return

    mengirim.value = true
    galat.value = {}

    const body = {
        judul: aktif.value.judul.trim(),
        jenis_surat: aktif.value.jenis_surat,
        konten: aktif.value.konten,
    }

    try {
        if (aktif.value.id) {
            const { data } = await api.put(`/surat-menyurat/template/${aktif.value.id}`, body)

            toast.success(data.message || 'Template diperbarui.')
        } else {
            const { data } = await api.post('/surat-menyurat/template', body)

            toast.success(data.message || 'Template dibuat.')

            /* Template baru langsung dibuka supaya bisa langsung disunting lagi. */
            aktif.value = data.template
        }

        asli.value = body.konten
        muat()
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

async function hapus(template) {
    const ok = await confirm.ask(`Hapus template "${template.judul}"?`, {
        confirmLabel: 'Ya, hapus',
        tone: 'danger',
    })

    if (!ok) return

    try {
        const { data } = await api.delete(`/surat-menyurat/template/${template.id}`)

        toast.success(data.message || 'Template dihapus.')

        if (aktif.value?.id === template.id) tutupEditor()

        muat()
    } catch (e) {
        toast.error(readError(e).message)
    }
}

/* Ringkasan isi untuk kartu di daftar: HTML-nya dibuang supaya tidak ada
 * markup yang ikut ter-render di daftar. */
function ringkas(konten) {
    const teks = String(konten || '')
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()

    if (!teks) return 'Belum ada isi.'

    return teks.length > 120 ? teks.slice(0, 120) + '...' : teks
}
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Kepala -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Template Surat</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Boilerplate isi surat yang bisa dipakai ulang.
                </p>
            </div>

            <button
                v-if="bisaUbah && !aktif"
                type="button"
                class="btn-primary text-xs shrink-0"
                @click="buatBaru"
            >
                <span class="material-symbols-outlined ms-icon text-[16px]">add</span>
                Template Baru
            </button>
        </div>

        <DataState
            :loading="memuat"
            :error="error"
            :is-empty="false"
            @retry="muat"
        >
            <!-- Editor -->
            <section v-if="aktif" class="pf-card overflow-hidden mb-4">
                <div
                    class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 border-b border-slate-100"
                >
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-slate-900">
                            {{ aktif.id ? 'Sunting Template' : 'Template Baru' }}
                        </h2>
                        <p
                            v-if="berubah"
                            class="text-[11px] text-amber-600 font-medium"
                        >
                            Belum disimpan
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" class="btn-secondary text-xs" @click="tutupEditor">
                            Tutup
                        </button>

                        <button
                            type="button"
                            class="btn-primary text-xs"
                            :disabled="mengirim"
                            @click="simpan"
                        >
                            <span
                                v-if="mengirim"
                                class="material-symbols-outlined ms-icon text-[16px] animate-spin"
                            >
                                progress_activity
                            </span>
                            <span v-else class="material-symbols-outlined ms-icon text-[16px]">
                                save
                            </span>
                            {{ mengirim ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>

                <div class="px-5 py-4 space-y-3.5">
                    <!-- Judul -->
                    <div>
                        <label for="tmplJudul" class="form-label">
                            Judul <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="tmplJudul"
                            v-model="aktif.judul"
                            type="text"
                            maxlength="255"
                            class="form-input"
                            :class="galat.judul ? 'form-input-error' : ''"
                            placeholder="Contoh: Surat Pernyataanondisi Mobil"
                        />
                        <p v-if="galat.judul" class="text-xs text-rose-600 mt-1">
                            {{ galat.judul }}
                        </p>
                    </div>

                    <!-- Jenis -->
                    <div>
                        <label for="tmplJenis" class="form-label">
                            Jenis Surat <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="tmplJenis"
                            v-model="aktif.jenis_surat"
                            type="text"
                            list="daftarJenisSurat"
                            maxlength="255"
                            class="form-input"
                            :class="galat.jenis_surat ? 'form-input-error' : ''"
                            placeholder="Pilih atau ketik jenis surat"
                        />

                        <datalist id="daftarJenisSurat">
                            <option v-for="j in jenisSurat" :key="j" :value="j" />
                        </datalist>

                        <p v-if="galat.jenis_surat" class="text-xs text-rose-600 mt-1">
                            {{ galat.jenis_surat }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            Daftar diambil dari jenis dokumen yang sudah punya nomor surat.
                        </p>
                    </div>

                    <!-- Isi -->
                    <div>
                        <label for="tmplKonten" class="form-label">Isi Surat</label>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                            <textarea
                                id="tmplKonten"
                                v-model="aktif.konten"
                                spellcheck="false"
                                class="w-full min-h-[320px] px-3.5 py-2.5 font-mono text-xs text-slate-700 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 resize-y"
                                placeholder="&lt;p&gt;Isi surat di sini...&lt;/p&gt;"
                            ></textarea>

                            <div
                                class="min-h-[320px] px-4 py-3 bg-white border border-slate-200 rounded-lg overflow-auto"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                    >
                                        Pratinjau
                                    </span>
                                </div>

                                <!-- sandbox="" mematikan skrip, form, dan navigasi
                                     di dalam pratinjau. -->
                                <iframe
                                    v-if="aktif.konten.trim()"
                                    :srcdoc="aktif.konten"
                                    class="w-full h-[280px] border-0"
                                    title="Pratinjau template surat"
                                    sandbox=""
                                ></iframe>

                                <p
                                    v-else
                                    class="text-xs text-slate-400 py-16 text-center"
                                >
                                    Isi surat akan muncul di sini.
                                </p>
                            </div>
                        </div>

                        <p v-if="galat.konten" class="text-xs text-rose-600 mt-1">
                            {{ galat.konten }}
                        </p>
                    </div>

                    <!-- Bantuan -->
                    <div
                        class="px-3 py-2.5 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900"
                    >
                        <p class="font-medium mb-1">Batasan saat dicetak ke PDF</p>
                        <p>
                            Template dirender oleh dompdf di server. Yang aman: HTML dasar,
                            tabel, dan gaya sederhana lewat atribut
                            <code>style</code>. Yang tidak akan muncul: font web,
                            flexbox/grid, animasi, dan CSS eksternal.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Daftar -->
            <div v-if="templates.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                <article
                    v-for="t in templates"
                    :key="t.id"
                    class="pf-card p-4 flex flex-col"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-slate-800 truncate">{{ t.judul }}</h3>
                            <span
                                class="inline-block mt-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600"
                            >
                                {{ t.jenis_surat }}
                            </span>
                        </div>

                        <div v-if="bisaUbah || bisaHapus" class="flex items-center gap-1 shrink-0">
                            <button
                                v-if="bisaUbah"
                                type="button"
                                class="p-1 rounded text-slate-300 hover:text-brand-600 transition-colors"
                                :aria-label="`Sunting ${t.judul}`"
                                @click="sunting(t)"
                            >
                                <span class="material-symbols-outlined ms-icon text-[16px]">edit</span>
                            </button>

                            <button
                                v-if="bisaHapus"
                                type="button"
                                class="p-1 rounded text-slate-300 hover:text-rose-600 transition-colors"
                                :aria-label="`Hapus ${t.judul}`"
                                @click="hapus(t)"
                            >
                                <span class="material-symbols-outlined ms-icon text-[16px]">
                                    delete
                                </span>
                            </button>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 mt-2.5 flex-1">
                        {{ ringkas(t.konten) }}
                    </p>

                    <p
                        v-if="t.updated_at"
                        class="text-[10px] text-slate-400 mt-2 pt-2 border-t border-slate-50"
                    >
                        Diperbarui {{ formatTanggalWaktu(t.updated_at) }}
                    </p>
                </article>
            </div>

            <EmptyState
                v-else-if="!aktif"
                icon="article"
                title="Belum ada template surat"
                :description="
                    bisaUbah
                        ? 'Buat boilerplate agar surat yang sering dibuat tidak perlu diketik ulang.'
                        : 'Belum ada template yang dibuat oleh operator.'
                "
            >
                <button v-if="bisaUbah" type="button" class="btn-primary text-xs" @click="buatBaru">
                    <span class="material-symbols-outlined ms-icon text-[16px]">add</span>
                    Template Baru
                </button>
            </EmptyState>
        </DataState>
    </div>
</template>