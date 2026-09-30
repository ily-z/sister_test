<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DataState from '@/components/DataState.vue'
import EmptyState from '@/components/EmptyState.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useAuthStore } from '@/stores/auth'
import { useConfirmStore } from '@/stores/confirm'
import { useToastStore } from '@/stores/toast'
import { useToast } from '@/stores/toast'

import { api, readError } from '@/lib/api'
import { formatBulanTahun, formatRupiah, formatTanggal } from '@/lib/format'

/*
 * ============================================================================
 *  PENGELUARAN KANTOR
 * ============================================================================
 *
 *  Meniru backend/resources/views/keuangan/pengeluaran-kantor.blade.php.
 *
 *  --- SATU HALAMAN, DUA MODE --------------------------------------------
 *
 *  Backend menerima satu POST untuk dua bentuk data:
 *    - satu baris  -> { tanggal, jumlah, deskripsi }
 *    - banyak baris-> { tanggal: [], jumlah: [], deskripsi: [] }
 *
 *  Bentuk kedua ditentukan server dari `$request->has('jumlah') &&
 *  is_array($request->input('jumlah'))`. Jadi frontend tidak perlu endpoint
 *  terpisah; ia cukup memilih bentuk yang dikirim. Tidak ada field "mode"
 *  yang perlu dikirim, dan server tidak akan salah baca satu baris sebagai
 *  banyak baris karena jumlah pada satu baris selalu string atau angka.
 *
 *  --- MENGAPA DI SATU HALAMAN, BUKAN HALAMAN FORM TERPISAH --------------------
 *
 *  Endpoint create/-edit hanya mengembalikan currentMonth dan objek
 *  pengeluaran, jadi formnya sangat pendek. Melewati halaman daftar untuk
 *  menambah satu pengeluaran membuat pekerjaan rutin harian terasa berat.
 *
 *  --- BARIS KOSONG DIABAIKAN SERVER ---------------------------------------
 *
 *  Pada mode banyak, baris dengan tanggal atau jumlah kosong di-skip diam-diam
 *  dan tidak dihitung dalam `created`. Jadi jumlah baris di UI boleh lebih
 *  dari angka di pesan sukses -- itu perilaku yang diharapkan, bukan bug.
 *  Baris kosong sengaja dibiarkan ada supaya pengguna tidak kehilangan isi
 *  baris lain yang sudah diketik.
 */

const route = useRoute()
const auth = useAuthStore()
const toast = useToastStore()
const confirm = useConfirmStore()

/* -------------------------------------------------------------------------
 *  STATE
 * ---------------------------------------------------------------------- */

const month = ref(String(route.query.month || bulanSekarang()))
const memuat = ref(true)
const error = ref(null)
const laporan = ref(null)

const dialogTerbuka = ref(false)
const mode = ref('tambah')
const menyimpan = ref(false)
const galatForm = ref({})

/*
 * Satu objek per baris. Bentuknya sengaja dibuat seragam supaya template
 * tidak perlu branching antara baris pertama dan baris tambahan.
 */
function barisKosong(tanggal = tanggalSekarang()) {
    return { tanggal, jumlah: '', deskripsi: '' }
}

const form = ref({
    id: null,
    tanggal: tanggalSekarang(),
    jumlah: '',
    deskripsi: '',
})

const baris = ref([barisKosong()])

function tanggalSekarang() {
    const d = new Date()

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

function bulanSekarang() {
    return tanggalSekarang().slice(0, 7)
}

/* -------------------------------------------------------------------------
 *  MUAT DATA
 * ---------------------------------------------------------------------- */

async function muat() {
    memuat.value = true
    error.value = null

    try {
        const { data } = await api.get('/keuangan/pengeluaran-kantor/data', {
            params: { month: month.value },
        })

        laporan.value = data

        if (data.month && data.month !== month.value) month.value = data.month
    } catch (e) {
        error.value = readError(e).message
    } finally {
        memuat.value = false
    }
}

watch(month, (nilai) => {
    router.replace({ query: { month: nilai } })
    muat()
})

watch(
    () => route.query.month,
    (nilai) => {
        if (nilai && String(nilai) !== month.value) month.value = String(nilai)
    }
)

/* -------------------------------------------------------------------------
 *  NILAI
 * ---------------------------------------------------------------------- */

const pengeluaran = computed(() => laporan.value?.pengeluaran || [])
const total = computed(() => Number(laporan.value?.total) || 0)
const bulanSebelumnya = computed(() => laporan.value?.prevMonth || null)
const bulanDepan = computed(() => laporan.value?.nextMonth || null)
const bisaMajukan = computed(() => Boolean(laporan.value?.canGoNext))
const monthLabel = computed(() => formatBulanTahun(month.value))

 /* Rata-rata harian dihitung dari jumlah hari yang punya transaksi.
 * Membagi dengan seluruh jumlah hari bulan membuat angka terlihat janggal
 * di bulan yang baru berjalan, padahal yang dibutuhkan adalah "berapa
 * biasanya per hari?". */
const jumlahHari = computed(() => {
    const unik = new Set(pengeluaran.value.map((p) => String(p.tanggal).slice(0, 10)))

    return unik.size
})

const rataPerHari = computed(() =>
    jumlahHari.value > 0 ? total.value / jumlahHari.value : 0
)

const rataPerTransaksi = computed(() =>
    pengeluaran.value.length ? total.value / pengeluaran.value.length : 0
)

const terbesar = computed(() =>
    pengeluaran.value.reduce((t, p) => Math.max(t, Number(p.jumlah) || 0), 0)
)

function keBulanLalu() {
    if (bulanSebelumnya.value) month.value = bulanSebelumnya.value
}

function keBulanDepan() {
    if (bulanDepan.value && bisaMajukan.value) month.value = bulanDepan.value
}

/* -------------------------------------------------------------------------
 *  DIALOG
 * ---------------------------------------------------------------------- */

function bukaTambah() {
    mode.value = 'tambah'
    galatForm.value = {}

    form.value = {
        id: null,
        tanggal: tanggalSekarang(),
        jumlah: '',
        deskripsi: '',
    }

    baris.value = [barisKosong(tanggalDalamBulan())]

    dialogTerbuka.value = true
}

function bukaEdit(item) {
    mode.value = 'edit'
    galatForm.value = {}

    form.value = {
        id: item.id,
        tanggal: String(item.tanggal).slice(0, 10),
        jumlah: item.jumlah,
        deskripsi: item.deskripsi || '',
    }

    dialogTerbuka.value = true
}

function tutup() {
    if (menyimpan.value) return

    dialogTerbuka.value = false
    galatForm.value = {}
}

/* Tanggal default baris baru mengikuti bulan yang sedang ditonton. Kalau tidak,
 * pengguna yang mengetik pengeluaran untuk Januari lalu dan terakhir pada
 * Februari akan melihat tanggalnya terisi bulan yang salah. */
function tanggalDalamBulan() {
    const d = new Date()

    return `${month.value}-${String(d.getDate()).padStart(2, '0')}`
}

function tambahBaris() {
    baris.value.push(barisKosong(tanggalDalamBulan()))
}

function hapusBaris(index) {
    baris.value.splice(index, 1)
}

function barisTerisi(b) {
    return Boolean(b.tanggal && b.jumlah)
}

const jumlahBarisTerisi = computed(() => baris.value.filter(barisTerisi).length)

/* -------------------------------------------------------------------------
 *  SIMPAN
 * ---------------------------------------------------------------------- */

/*
 * Galat form diisi apa adanya dari payload 422. validate() mengembalikan objek
 * dengan nama field sebagai kunci, dan api.js sudah meneruskan field errors
 * ke sini lewat error.response.data.errors. Baris yang salah ditampilkan
 * tepat di bawah input-nya.
 */
function simpan() {
    if (menyimpan.value) return

    menyimpan.value = true
    galatForm.value = {}

    const permintaan = jalankanSimpan()

    permintaan.finally(() => {
        menyimpan.value = false
    })
}

async function jalankanSimpan() {
    try {
        if (mode.value === 'edit') {
            const { data } = await api.put(`/keuangan/pengeluaran-kantor/${form.value.id}`, {
                tanggal: form.value.tanggal,
                jumlah: form.value.jumlah,
                deskripsi: form.value.deskripsi,
            })

            toast.success(data.message || 'Pengeluaran kantor diperbarui.')
        } else {
            /*
             * Bentuk payload ditentukan di sini, sesuai yang dibaca backend:
             * hanya mode banyak yang mengirim array. Mengirim array kosong
             * untuk tanggal/jumlah pada mode satu baris akan membuat server
             * salah membaca permintaan sebagai bulk.
             */
            const isi = baris.value.filter(barisTerisi)

            const body =
                isi.length > 1
                    ? {
                          tanggal: isi.map((b) => b.tanggal),
                          jumlah: isi.map((b) => b.jumlah),
                          deskripsi: isi.map((b) => b.deskripsi),
                      }
                    : {
                          tanggal: (isi[0] || form).tanggal,
                          jumlah: (isi[0] || form).jumlah,
                          deskripsi: (isi[0] || form).deskripsi,
                      }

            const { data } = await api.post('/keuangan/pengeluaran-kantor', body)

            toast.success(data.message || 'Pengeluaran kantor ditambahkan.')
        }

        dialogTerbuka.value = false
        muat()
    } catch (e) {
        const errors = e?.response?.data?.errors || {}

        if (Object.keys(errors).length) {
            galatForm.value = errors

            return
        }

        toast.error(readError(e).message)
    }
}

/* -------------------------------------------------------------------------
 *  HAPUS
 * ---------------------------------------------------------------------- */

async function hapus(item) {
    const ok = await confirm.ask(
        `Hapus pengeluaran ${formatRupiah(item.jumlah)} tanggal ${formatTanggal(item.tanggal)}?`,
        { confirmLabel: 'Ya, hapus', tone: 'danger' }
    )

    if (!ok) return

    try {
        const { data } = await api.delete(`/keuangan/pengeluaran-kantor/${item.id}`)

        toast.success(data.message || 'Pengeluaran kantor dihapus.')
        muat()
    } catch (e) {
        toast.error(readError(e).message)
    }
}

/* Galat per baris dari server, kalau ada. Kunci field dari validate() pada
 * mode banyak berbentuk "jumlah.0" dan "tanggal.1", jadi index diambil dari
 * nama field itu, bukan dari urutan baris yang sedang tampil. */
function galatBaris(index, field) {
    return galatForm.value[`${field}.${index}`] || ''
}

function galatSatu(field) {
    return galatForm.value[field] || ''
}

const bisaUbah = computed(() => auth.can('finance.edit'))
const bisaHapus = computed(() => auth.can('finance.delete'))
const bisaTambah = computed(() => auth.can('finance.create'))

muat()
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <PageHeader
            title="Pengeluaran Kantor"
            description="Biaya operasional yang tidak menempel ke satu kendaraan."
        >
            <template #actions>
                <div class="flex items-center gap-1 shrink-0">
                    <button
                        type="button"
                        class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-brand-600 hover:border-brand-300 transition-colors flex items-center justify-center disabled:opacity-40 disabled:hover:text-slate-500 disabled:hover:border-slate-200"
                        :disabled="!bulanSebelumnya"
                        aria-label="Bulan sebelumnya"
                        @click="keBulanLalu"
                    >
                        <span class="material-symbols-outlined ms-icon text-[18px]">chevron_left</span>
                    </button>

                    <span
                        class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 min-w-[130px] text-center"
                    >
                        {{ monthLabel }}
                    </span>

                    <button
                        type="button"
                        class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-brand-600 hover:border-brand-300 transition-colors flex items-center justify-center disabled:opacity-40 disabled:hover:text-slate-500 disabled:hover:border-slate-200"
                        :disabled="!bisaMajukan"
                        aria-label="Bulan berikutnya"
                        @click="keBulanDepan"
                    >
                        <span class="material-symbols-outlined ms-icon text-[18px]">chevron_right</span>
                    </button>
                </div>
            </template>
        </PageHeader>

        <DataState
            :loading="memuat"
            :error="error"
            :is-empty="false"
            @retry="muat"
        >
            <template v-if="laporan">
                <!-- Ringkasan -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                    <div class="pf-card p-3.5">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Total Bulan Ini
                        </p>
                        <p class="text-base font-bold text-rose-600 mt-1 tabular-nums">
                            {{ formatRupiah(total) }}
                        </p>
                        <p class="text-[10px] text-slate-500 mt-0.5">
                            {{ pengeluaran.length }} catatan
                        </p>
                    </div>

                    <div class="pf-card p-3.5">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Rata-rata / Catatan
                        </p>
                        <p class="text-base font-bold text-slate-900 mt-1 tabular-nums">
                            {{ formatRupiah(rataPerTransaksi) }}
                        </p>
                        <p class="text-[10px] text-slate-500 mt-0.5">
                            Dari {{ pengeluaran.length }} transaksi
                        </p>
                    </div>

                    <div class="pf-card p-3.5">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Rata-rata / Hari
                        </p>
                        <p class="text-base font-bold text-slate-900 mt-1 tabular-nums">
                            {{ formatRupiah(rataPerHari) }}
                        </p>
                        <p class="text-[10px] text-slate-500 mt-0.5">
                            {{ jumlahHari }} hari yang ada transaksinya
                        </p>
                    </div>

                    <div class="pf-card p-3.5">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Catatan Terbesar
                        </p>
                        <p class="text-base font-bold text-slate-900 mt-1 tabular-nums">
                            {{ formatRupiah(terbesar) }}
                        </p>
                        <p class="text-[10px] text-slate-500 mt-0.5">
                            {{ terbesar && total ? Math.round((terbesar / total) * 100) : 0 }}% dari
                            total bulan ini
                        </p>
                    </div>
                </div>

                <!-- Daftar -->
                <section class="pf-card overflow-hidden">
                    <div
                        class="flex items-center justify-between gap-3 px-5 py-3 border-b border-slate-100"
                    >
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Rincian</h2>
                            <p class="text-[11px] text-slate-500">
                                Diurutkan dari tanggal terbaru.
                            </p>
                        </div>

                        <button
                            v-if="bisaTambah"
                            type="button"
                            class="btn-primary text-xs"
                            @click="bukaTambah"
                        >
                            <span class="material-symbols-outlined ms-icon text-[16px]">add</span>
                            Tambah Pengeluaran
                        </button>
                    </div>

                    <div v-if="pengeluaran.length" class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[640px]">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th
                                        class="px-5 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider"
                                    >
                                        Tanggal
                                    </th>
                                    <th
                                        class="px-5 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider"
                                    >
                                        Deskripsi
                                    </th>
                                    <th
                                        class="px-5 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider"
                                    >
                                        Jumlah
                                    </th>
                                    <th
                                        class="px-5 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider"
                                    >
                                        Porsi
                                    </th>
                                    <th
                                        v-if="bisaUbah || bisaHapus"
                                        class="px-5 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider"
                                    >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-50">
                                <tr
                                    v-for="item in pengeluaran"
                                    :key="item.id"
                                    class="hover:bg-slate-50/60 transition-colors"
                                >
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        <span class="font-medium text-slate-800">
                                            {{ formatTanggal(item.tanggal) }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-3 text-slate-600">
                                        {{ item.deskripsi || '-' }}
                                    </td>

                                    <td
                                        class="px-5 py-3 text-right font-semibold text-rose-600 tabular-nums"
                                    >
                                        {{ formatRupiah(item.jumlah) }}
                                    </td>

                                    <td class="px-5 py-3 text-right tabular-nums">
                                        <div
                                            v-if="total"
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div
                                                    class="h-full bg-rose-400 rounded-full"
                                                    :style="{
                                                        width:
                                                            Math.min(
                                                                100,
                                                                ((Number(item.jumlah) || 0) / total) *
                                                                    100
                                                            ) + '%',
                                                    }"
                                                ></div>
                                            </div>

                                            <span class="text-[10px] text-slate-400 w-8 text-right">
                                                {{
                                                    Math.round(
                                                        ((Number(item.jumlah) || 0) / total) * 100
                                                    )
                                                }}%
                                            </span>
                                        </div>

                                        <span v-else class="text-[10px] text-slate-300">-</span>
                                    </td>

                                    <td
                                        v-if="bisaUbah || bisaHapus"
                                        class="px-5 py-3 text-right whitespace-nowrap"
                                    >
                                        <button
                                            v-if="bisaUbah"
                                            type="button"
                                            class="p-1 rounded text-slate-400 hover:text-brand-600 transition-colors"
                                            title="Ubah pengeluaran"
                                            @click="bukaEdit(item)"
                                        >
                                            <span class="material-symbols-outlined ms-icon text-[16px]">
                                                edit
                                            </span>
                                        </button>

                                        <button
                                            v-if="bisaHapus"
                                            type="button"
                                            class="p-1 rounded text-slate-400 hover:text-rose-600 transition-colors"
                                            title="Hapus pengeluaran"
                                            @click="hapus(item)"
                                        >
                                            <span class="material-symbols-outlined ms-icon text-[16px]">
                                                delete
                                            </span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>

                            <tfoot class="border-t border-slate-200 bg-slate-50">
                                <tr>
                                    <td
                                        colspan="2"
                                        class="px-5 py-3 text-xs font-bold text-slate-600 uppercase tracking-wider"
                                    >
                                        Total {{ monthLabel }}
                                    </td>
                                    <td
                                        class="px-5 py-3 text-right font-bold text-rose-600 tabular-nums"
                                    >
                                        {{ formatRupiah(total) }}
                                    </td>
                                    <td class="px-5 py-3"></td>
                                    <td v-if="bisaUbah || bisaHapus" class="px-5 py-3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <EmptyState
                        v-else
                        icon="receipt_long"
                        title="Belum ada pengeluaran kantor"
                        :description="`Tidak ada pengeluaran kantor tercatat pada ${monthLabel}.`"
                    >
                        <button
                            v-if="bisaTambah"
                            type="button"
                            class="btn-primary text-xs"
                            @click="bukaTambah"
                        >
                            <span class="material-symbols-outlined ms-icon text-[16px]">add</span>
                            Tambah Pengeluaran
                        </button>
                    </EmptyState>
                </section>
            </template>
        </DataState>

        <!-- Dialog tambah / ubah -->
        <div
            v-if="dialogTerbuka"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
            @click.self="tutup"
        >
            <div
                class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
            >
                <div
                    class="flex items-center justify-between px-5 py-4 border-b border-slate-100 sticky top-0 bg-white rounded-t-xl"
                >
                    <h3 class="text-sm font-bold text-slate-900">
                        {{ mode === 'edit' ? 'Ubah Pengeluaran Kantor' : 'Tambah Pengeluaran Kantor' }}
                    </h3>

                    <button
                        type="button"
                        class="p-1 rounded text-slate-400 hover:text-slate-700 transition-colors"
                        aria-label="Tutup"
                        @click="tutup"
                    >
                        <span class="material-symbols-outlined ms-icon text-[18px]">close</span>
                    </button>
                </div>

                <div class="px-5 py-4 space-y-3.5">
                    <p
                        v-if="galatSatu('jumlah')"
                        class="text-xs text-rose-600"
                    >
                        {{ galatSatu('jumlah') }}
                    </p>

                    <!-- Mode ubah: satu form -->
                    <template v-if="mode === 'edit'">
                        <div>
                            <label for="editTanggal" class="form-label">Tanggal</label>
                            <input
                                id="editTanggal"
                                v-model="form.tanggal"
                                type="date"
                                class="form-input"
                                :class="galatSatu('tanggal') ? 'form-input-error' : ''"
                            />
                            <p v-if="galatSatu('tanggal')" class="text-xs text-rose-600 mt-1">
                                {{ galatSatu('tanggal') }}
                            </p>
                        </div>

                        <div>
                            <label for="editJumlah" class="form-label">Jumlah (Rp)</label>
                            <input
                                id="editJumlah"
                                v-model="form.jumlah"
                                type="number"
                                min="0"
                                step="0.01"
                                class="form-input"
                                :class="galatSatu('jumlah') ? 'form-input-error' : ''"
                            />
                            <p v-if="galatSatu('jumlah')" class="text-xs text-rose-600 mt-1">
                                {{ galatSatu('jumlah') }}
                            </p>
                        </div>

                        <div>
                            <label for="editDeskripsi" class="form-label">
                                Deskripsi
                                <span class="text-slate-400 font-normal">(opsional)</span>
                            </label>
                            <input
                                id="editDeskripsi"
                                v-model="form.deskripsi"
                                type="text"
                                maxlength="255"
                                class="form-input"
                                :class="galatSatu('deskripsi') ? 'form-input-error' : ''"
                            />
                            <p v-if="galatSatu('deskripsi')" class="text-xs text-rose-600 mt-1">
                                {{ galatSatu('deskripsi') }}
                            </p>
                        </div>
                    </template>

                    <!-- Mode tambah: satu atau banyak baris -->
                    <template v-else>
                        <p class="text-[11px] text-slate-500">
                            Isi satu baris untuk satu pengeluaran. Tambah baris untuk mencatat
                            beberapa pengeluaran sekaligus.
                        </p>

                        <div
                            v-for="(b, i) in baris"
                            :key="i"
                            class="border border-slate-200 rounded-lg p-3 space-y-2.5 bg-slate-50/60"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                    {{ baris.length > 1 ? `Baris ${i + 1}` : 'Pengeluaran' }}
                                </span>

                                <button
                                    v-if="baris.length > 1"
                                    type="button"
                                    class="p-1 rounded text-slate-300 hover:text-rose-600 transition-colors"
                                    title="Hapus baris"
                                    @click="hapusBaris(i)"
                                >
                                    <span class="material-symbols-outlined ms-icon text-[16px]">
                                        delete
                                    </span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label :for="`tanggal-${i}`" class="form-label">Tanggal</label>
                                    <input
                                        :id="`tanggal-${i}`"
                                        v-model="b.tanggal"
                                        type="date"
                                        class="form-input bg-white"
                                        :class="galatBaris(i, 'tanggal') ? 'form-input-error' : ''"
                                    />
                                    <p v-if="galatBaris(i, 'tanggal')" class="text-xs text-rose-600 mt-1">
                                        {{ galatBaris(i, 'tanggal') }}
                                    </p>
                                </div>

                                <div>
                                    <label :for="`jumlah-${i}`" class="form-label">Jumlah (Rp)</label>
                                    <input
                                        :id="`jumlah-${i}`"
                                        v-model="b.jumlah"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-input bg-white"
                                        :class="galatBaris(i, 'jumlah') ? 'form-input-error' : ''"
                                    />
                                    <p v-if="galatBaris(i, 'jumlah')" class="text-xs text-rose-600 mt-1">
                                        {{ galatBaris(i, 'jumlah') }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label :for="`deskripsi-${i}`" class="form-label">
                                    Deskripsi
                                    <span class="text-slate-400 font-normal">(opsional)</span>
                                </label>
                                <input
                                    :id="`deskripsi-${i}`"
                                    v-model="b.deskripsi"
                                    type="text"
                                    maxlength="255"
                                    class="form-input bg-white"
                                    :class="galatBaris(i, 'deskripsi') ? 'form-input-error' : ''"
                                />
                                <p v-if="galatBaris(i, 'deskripsi')" class="text-xs text-rose-600 mt-1">
                                    {{ galatBaris(i, 'deskripsi') }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn-secondary text-xs w-full"
                            @click="tambahBaris"
                        >
                            <span class="material-symbols-outlined ms-icon text-[16px]">add</span>
                            Tambah Baris
                        </button>

                        <p class="text-[11px] text-slate-500">
                            {{ jumlahBarisTerisi }} baris akan dikirim.
                            <span v-if="baris.length > jumlahBarisTerisi">
                                Baris kosong diabaikan server.
                            </span>
                        </p>
                    </template>
                </div>

                <div
                    class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-100 bg-slate-50 rounded-b-xl"
                >
                    <button
                        type="button"
                        class="btn-secondary text-xs"
                        :disabled="menyimpan"
                        @click="tutup"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        class="btn-primary text-xs"
                        :disabled="menyimpan"
                        @click="simpan"
                    >
                        <span
                            v-if="menyimpan"
                            class="material-symbols-outlined ms-icon text-[16px] animate-spin"
                        >
                            progress_activity
                        </span>
                        <span
                            v-else
                            class="material-symbols-outlined ms-icon text-[16px]"
                        >
                            save
                        </span>
                        {{ menyimpan ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>