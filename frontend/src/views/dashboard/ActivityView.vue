<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DataState from '@/components/DataState.vue'

import { api, readError } from '@/lib/api'
import { formatWaktu, sejakDetik } from '@/lib/format'
import { warnaAktivitas } from '@/lib/warna'

/*
 * ============================================================================
 *  LOG AKTIVITAS
 * ============================================================================
 *
 *  Meniru backend/resources/views/activity/index.blade.php.
 *
 *  --- FILTER DATANG DARI BACKEND, BUKAN DITULIS ULANG DI SINI ------------
 *
 *  ActivityController::FILTERS adalah satu-satunya daftar tab yang sah, dan
 *  halaman ini mengambilnya dari respons. Menyalin daftar itu ke sini berarti
 *  ada dua tempat yang harus ikut berubah kalau suatu saat ada kategori
 *  baru ditambahkan.
 *
 * /type dari query string tidak dipercaya begitu saja: backend hanya
 *  menerima enam nilai yang ada di FILTERS, dan nilai lain dipaksa menjadi
 *  "all". Jadi dropdown di layar dan isi tabel selalu cocok, bahkan kalau
 *  ada orang yang mengedit URL-nya secara manual.
 *
 *  --- BATASAN 200 BARIS, BUKAN PAGINASI ------------------------------------
 *
 *  Endpoint ini mengembalikan daftar terpotong, bukan paginator. Default-nya
 *  50 baris, dan server membatasi maksimum 200. Jadi "muat lebih banyak" di
 *  sini berarti menaikkan limit -- bukan mengambil halaman berikutnya.
 *
 *  Tombol itu sengaja tidak ada kalau sudah sampai batas server. Menambah
 *  limit di atas 200 tidak akan menambah apa pun, karena backend akan
 *  memotongnya kembali; tombolnya yang dinonaktifkan, bukan permintaannya
 *  yang gagal.
 *
 *  --- WARNA DAN IKON BUKAN KELAS TAILWIND ---------------------------------
 *
 *  Sama seperti halaman user, warnanya diambil dari peta di lib/warna.js.
 *  Nama warna tidak pernah dirakit jadi kelas `bg-${warna}-50` di templat,
 *  karena Tailwind hanya menulis kelas yang ditemukan utuh di berkas sumber.
 *
 *  --- LENCANA KATEGORI BUKAN BAGIAN DARI WARNA -----------------------------
 *
 *  Warna di activities.color menggambarkan jenis perubahannya (tambah, ubah,
 *  hapus, transfer kepemilikan). Lencana di kanan menandai modulnya -- booking,
 *  payment, service, user, dan seterusnya. Keduanya tidak selalu sama, jadi
 *  tidak boleh digabung.
 */

const route = useRoute()
const router = useRouter()

const memuat = ref(true)
const error = ref('')

const aktivitas = ref([])
const filters = ref({})
const typeAktif = ref('all')
const limit = ref(50)

/* Batas atas yang dipaksakan server. Lihat catatan di kepala file. */
const BATAS_LIMIT = 200

const BATAS_PER_PAGE = [50, 100, 200]

/* -------------------------------------------------------------------------
 *  MUAT
 * ---------------------------------------------------------------------- */

async function muat() {
    memuat.value = true
    error.value = ''

    try {
        const { data } = await api.get('/activity', {
            params: { type: route.query.type || undefined, limit: limit.value },
        })

        aktivitas.value = data.activities || []
        filters.value = data.filters || {}
        typeAktif.value = data.type || 'all'
    } catch (e) {
        error.value = readError(e).message
    } finally {
        memuat.value = false
    }
}

watch(
    () => [route.query.type, limit.value],
    () => muat()
)

onMounted(muat)

/* Tab ditulis ke URL, bukan disimpan di state saja. Halaman ini sering dipakai
 * sebagai rujukan -- "coba lihat log pembayaran kemarin" -- dan tautan seperti
 * itu harus tetap membuka tampilan yang sama kalau dikirim ke rekan kerja. */
function pilihType(type) {
    router.replace({ query: type === 'all' ? {} : { type } })
}

/* -------------------------------------------------------------------------
 *  TAMPILAN
 * ---------------------------------------------------------------------- */

/* Daftar tab dibaca dari objek filters yang dikirim backend, bukan dari larik
 * yang ditulis tangan di sini. Urutannya mengikuti urutan server. */
const tabFilters = computed(() => Object.entries(filters.value))

/*
 * Deskripsi aktivitas bisa berisi beberapa baris -- satu per field yang
 * berubah, seperti yang ditulis UserController saat user di-edit. Ditampilkan
 * sebagai daftar supaya tiap perubahan mudah dipindai, bukan menyatu jadi
 * satu paragraf.
 */
function barisDeskripsi(teks) {
    return String(teks || '')
        .split('\n')
        .map((b) => b.trim())
        .filter(Boolean)
}

/* Lencana kategori modul. Terpisah dari warna ikon, lihat catatan di kepala
 * file. Return-nya selalu objek lengkap supaya tidak perlu diperiksa lagi
 * di templat. */
const LENCANA_TYPE = {
    booking: 'bg-blue-100 text-blue-800',
    payment: 'bg-emerald-100 text-emerald-800',
    return: 'bg-amber-100 text-amber-800',
    service: 'bg-rose-100 text-rose-800',
    user: 'bg-purple-100 text-purple-800',
    maintenance: 'bg-orange-100 text-orange-800',
}

function lencanaType(type) {
    return LENCANA_TYPE[type] || 'bg-slate-100 text-slate-800'
}

function labelType(type) {
    if (!type) return '-'

    return type.charAt(0).toUpperCase() + type.slice(1)
}

const diBatas = computed(() => limit.value >= BATAS_LIMIT)

/* "Muat lebih banyak" menambah limit, bukan mengambil halaman berikutnya --
 * lihat catatan di kepala file. Dibatasi supaya jumlah yang diminta tidak
 * pernah melewati batas server. */
function muatLebih() {
    const langkah = limit.value < 100 ? 50 : 100

    limit.value = Math.min(limit.value + langkah, BATAS_LIMIT)
}
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Kepala -->
        <div
            class="flex flex-col md:flex-row md:justify-between md:items-end mb-8 gap-4"
        >
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Log Aktivitas</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Aksi terkini dan kejadian sistem.
                </p>
            </div>

            <div
                class="flex items-center gap-3"
            >
                <label
                    for="limit"
                    class="text-xs font-semibold text-slate-500 uppercase tracking-wider"
                >
                    Tampilkan
                </label>

                <select
                    id="limit"
                    v-model.number="limit"
                    class="px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                >
                    <option
                        v-for="n in BATAS_PER_PAGE"
                        :key="n"
                        :value="n"
                    >
                        {{ n }} terbaru
                    </option>
                </select>
            </div>
        </div>

        <!-- Tab filter -->
        <div class="pf-card p-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="[key, label] in tabFilters"
                    :key="key"
                    type="button"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                    :class="
                        typeAktif === key
                            ? 'bg-brand-600 text-white'
                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                    "
                    @click="pilihType(key)"
                >
                    {{ label }}
                </button>
            </div>
        </div>

        <!-- Timeline -->
        <section class="pf-card overflow-hidden">
            <div class="p-6 border-b border-slate-50">
                <h2 class="text-lg font-bold text-slate-900 tracking-tight">
                    Aktivitas Terkini
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Urutan kejadian sistem dan aksi pengguna
                </p>
            </div>

            <DataState
                :loading="memuat"
                :error="error"
                :is-empty="!memuat && !error && !aktivitas.length"
                empty-icon="history_toggle_off"
                empty-title="Belum ada aktivitas"
                empty-description="Aktivitas akan muncul di sini begitu ada yang terjadi."
                @retry="muat"
            >
                <div class="divide-y divide-slate-50">
                    <div
                        v-for="a in aktivitas"
                        :key="a.id"
                        class="p-6 hover:bg-slate-50/50 transition-colors"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Ikon -->
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                :class="warnaAktivitas(a.color)"
                            >
                                <span
                                    class="material-symbols-outlined ms-icon text-sm"
                                >
                                    {{ a.icon }}
                                </span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div class="min-w-0">
                                        <h3
                                            class="text-sm font-bold text-slate-900"
                                        >
                                            {{ a.title }}
                                        </h3>

                                        <!--
                                            Lebih dari satu baris jadi daftar,
                                            satu baris jadi paragraf biasa.
                                            Mengembalikan dua bentuk elemen
                                            dari sumber yang sama bikin
                                            memilih elemennya jadi merepotkan.
                                        -->
                                        <ul
                                            v-if="
                                                barisDeskripsi(a.description)
                                                    .length > 1
                                            "
                                            class="mt-1 space-y-0.5"
                                        >
                                            <li
                                                v-for="(baris, i) in barisDeskripsi(
                                                    a.description
                                                )"
                                                :key="i"
                                                class="text-sm text-slate-600 whitespace-pre-wrap"
                                            >
                                                {{ baris }}
                                            </li>
                                        </ul>

                                        <p
                                            v-else
                                            class="text-sm text-slate-600 mt-1 whitespace-pre-wrap"
                                        >
                                            {{ a.description }}
                                        </p>

                                        <div
                                            class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-slate-500"
                                        >
                                            <span>
                                                oleh
                                                {{ a.user || 'System' }}
                                            </span>

                                            <span
                                                v-if="a.created_at"
                                                :title="
                                                    formatWaktu(a.created_at)
                                                "
                                            >
                                                {{
                                                    formatWaktu(a.created_at)
                                                }}
                                                &middot;
                                                {{
                                                    sejakDetik(a.created_at)
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Kategori modul -->
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                                        :class="lencanaType(a.type)"
                                    >
                                        {{ labelType(a.type) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Muat lebih banyak -->
                <div
                    v-if="aktivitas.length"
                    class="p-6 border-t border-slate-50 bg-slate-50/50 flex flex-col sm:flex-row items-center gap-3"
                >
                    <button
                        type="button"
                        class="btn-secondary text-sm"
                        :disabled="diBatas"
                        @click="muatLebih"
                    >
                        <span
                            class="material-symbols-outlined ms-icon text-[16px]"
                        >
                            expand_more
                        </span>
                        {{ diBatas ? 'Batas 200 baris tercapai' : 'Muat lebih banyak' }}
                    </button>

                    <p class="text-xs text-slate-500 text-center sm:text-start sm:ms-auto">
                        Menampilkan {{ aktivitas.length }} aktivitas
                        terbaru<span v-if="diBatas"> (batas server 200 baris)</span>.
                    </p>
                </div>
            </DataState>
        </section>
    </div>
</template>
