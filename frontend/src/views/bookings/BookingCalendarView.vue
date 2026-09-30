<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DataState from '@/components/DataState.vue'

import { api, readError } from '@/lib/api'
import {
    formatBulanTahun,
    formatRupiah,
    formatTanggal,
    geserBulan,
    toDate,
} from '@/lib/format'

/*
 * ============================================================================
 *  KALENDER JADWAL
 * ============================================================================
 *
 *  Meniru backend/resources/views/bookings/calendar.blade.php.
 *
 *  --- TABEL GANTIS DIAGRAM GANTT ------------------------------------------
 *
 *  Grid-nya baris = kendaraan, kolom = hari. Setiap booking digambar sebagai
 *  kotak yang membentang dari tanggal mulai sampai tanggal selesai. Ini bukan
 *  kalender bulanan pada umumnya, dan itu memang yang paling berguna di sini:
 *  pertanyaannya bukan "apa yang hari ini", tapi "kendaraan mana yang sudah
 *  dipesan bulan depan".
 *
 *  --- SATU REQUEST, SEMUA BULAN ------------------------------------------
 *
 *  GET /bookings/calendar mengembalikan seluruh armada dan seluruh booking
 *  tanpa filter tanggal. Navigasi bulan karena itu tidak menembak API lagi --
 *  hanya menghitung ulang di memori. Ini yang membuat/month bisa geser cepat
 *  dan tidak berkedip saat-pressed.
 *
 *  --- BENDING YANG BERTAHAN PADA SATU MOBIL --------------------------------
 *
 *  Dua booking pada kendaraan yang sama bisa saling tumpang tindih, misalnya
 *  satu Salah tanggal 1-5 dan yang lain 4-10. Versi lama menimpa kotak yang
 *  lebih awal, sehingga salah satu pesanan hilang tanpa jejak. Di sini setiap
 *  booking ditugaskan ke "lane" (jalur) paling awal yang masih kosong, dan satu
 *  baris kendaraan bisa punya beberapa jalur. Hasilnya tidak ada pesanan yang
 *  hilang, dan jumlah jalur yang terpakai menjadi tinggi baris.
 *
 *  --- TANGGAL SELESAI TERMASUK --------------------------------------------
 *
 *  Kotak booking memakai Rentang inklusif: 1 sampai 3 berarti tiga kolom. Ini
 *  mengikuti perhitungan di kolom "Perhitungan lokal" pada halaman buat
 *  pesanan, jadi angka hari sewa yang terlihat di kalender sama dengan angka
 *  yang ditagih.
 */

const route = useRoute()
const router = useRouter()

/* -------------------------------------------------------------------------
 *  STATE
 * ---------------------------------------------------------------------- */

function bulanSekarang() {
    const d = new Date()

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

const month = ref(String(route.query.month || bulanSekarang()))
const memuat = ref(true)
const error = ref('')
const armada = ref([])
const bookings = ref([])

/* Filter status pembayaran. Bentuknya array, bukan Set, supaya bisa dipakai
 * langsung oleh v-model pada checkbox tanpa perlu getter khusus. */
const filterStatus = ref(['lunas', 'dp', 'belum_bayar', 'belum_lunas'])

/* Hanya tampilkan kendaraan yang punya pesanan aktif. Ini default yang
 * dipakai versi lama, dan masuk akal: dari 40 kendaraan, biasanya cuma
 * beberapa yang relevan untuk bulan yang sedang dilihat. */
const hanyaDisewa = ref(true)

const detail = ref(null)
const cek = ref({ dari: '', sampai: '', hasil: null })

/* -------------------------------------------------------------------------
 *  MUAT DATA
 * ---------------------------------------------------------------------- */

async function muat() {
    memuat.value = true
    error.value = ''

    try {
        const { data } = await api.get('/bookings/calendar')

        armada.value = data.armada || []
        bookings.value = data.bookings || []
    } catch (e) {
        error.value = readError(e).message
    } finally {
        memuat.value = false
    }
}

watch(month, (nilai) => {
    router.replace({ query: { month: nilai } })
})

watch(
    () => route.query.month,
    (nilai) => {
        if (nilai && String(nilai) !== month.value) month.value = String(nilai)
    }
)

/* -------------------------------------------------------------------------
 *  KONSTANTA TAMPILAN
 * ---------------------------------------------------------------------- */

const HARI_PENDEK = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']

const KONFIG_STATUS = {
    lunas: {
        label: 'Lunas',
        chip: 'bg-emerald-100 text-emerald-700 border-emerald-300',
        bar: 'bg-emerald-100 text-emerald-800 border-emerald-300',
        dot: 'bg-emerald-500',
    },
    dp: {
        label: 'DP',
        chip: 'bg-blue-100 text-blue-700 border-blue-300',
        bar: 'bg-blue-100 text-blue-800 border-blue-300',
        dot: 'bg-blue-500',
    },
    belum_bayar: {
        label: 'Belum Bayar',
        chip: 'bg-amber-100 text-amber-700 border-amber-300',
        bar: 'bg-amber-100 text-amber-800 border-amber-300',
        dot: 'bg-amber-500',
    },
    belum_lunas: {
        label: 'Belum Lunas',
        chip: 'bg-orange-100 text-orange-700 border-orange-300',
        bar: 'bg-orange-100 text-orange-800 border-orange-300',
        dot: 'bg-orange-500',
    },
}

/* Status di luar empat nilai di atas tidak mungkin tampil sebagai booking,
 * tapi tetap diberi gaya supaya tidak undefined kalau data berubah. */
const gayaStatus = (status) => KONFIG_STATUS[status] || KONFIG_STATUS.belum_bayar

const LABEL_ARMADA = {
    Tersedia: { label: 'Tersedia', cls: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    'Sedang Disewa': { label: 'Disewa', cls: 'bg-blue-50 text-blue-700 border-blue-200' },
    Bengkel: { label: 'Bengkel', cls: 'bg-amber-50 text-amber-700 border-amber-200' },
}

const gayaArmada = (status) =>
    LABEL_ARMADA[status] || {
        label: status || '-',
        cls: 'bg-slate-50 text-slate-600 border-slate-200',
    }

/* -------------------------------------------------------------------------
 *  HITUNG MINGGUAN & BULANAN
 * ---------------------------------------------------------------------- */

const tahun = computed(() => Number(String(month.value).slice(0, 4)))
const bulan = computed(() => Number(String(month.value).slice(5, 7)) - 1)

const jumlahHari = computed(() => new Date(tahun.value, bulan.value + 1, 0).getDate())

const awalBulan = computed(() => new Date(tahun.value, bulan.value, 1))
const akhirBulan = computed(() => new Date(tahun.value, bulan.value, jumlahHari.value))

const hariIni = computed(() => {
    const d = new Date()

    return new Date(d.getFullYear(), d.getMonth(), d.getDate())
})

const bulanIniLabel = computed(() => formatBulanTahun(month.value))

/* -------------------------------------------------------------------------
 *  SELEKSI BOOKING
 * ---------------------------------------------------------------------- */

/*
 * Setiap booking yang lolos filter dipotong ke rentang bulan yang sedang
 * tampil. Pemotongan perlu karena booking bisa dimulai sebelum bulan ini
 * (mis. 28 Sep) dan berakhir di dalamnya, atau sebaliknya.
 */
const bookingsTerpilih = computed(() => {
    const hariIniAwal = hariIni.value

    return bookings.value
        .filter((b) => {
            if (b.status_rental === 'Batal') return false
            if (!filterStatus.value.includes(b.status)) return false

            const selesai = toDate(b.tanggal_selesai)

            /* Booking yang sudah selesai sebelum hari ini disembunyikan saat
             * mode "hanya yang disewa" menyala. Tanpa filter ini, kalender
             * bulan berjalan penuh dengan kotak bulan lalu. */
            if (hanyaDisewa.value && selesai < hariIniAwal) return false

            return selesai >= awalBulan.value && toDate(b.tanggal_mulai) <= akhirBulan.value
        })
        .map((b) => {
            const mulai = toDate(b.tanggal_mulai)
            const selesai = toDate(b.tanggal_selesai)

            return {
                ...b,
                mulai,
                selesai,
                /* Rentang inklusif, dipotong ke tepi bulan. */
                visStart: mulai < awalBulan.value ? 1 : mulai.getDate(),
                visEnd: selesai > akhirBulan.value ? jumlahHari.value : selesai.getDate(),
            }
        })
})

/* Hari yang dilewati setidaknya satu booking. Kolom seperti ini diberi lebar
 * lebih besar supaya nama pelanggan muat di dalam kotaknya. */
const hariAdaBooking = computed(() => {
    const set = new Set()

    bookingsTerpilih.value.forEach((b) => {
        for (let d = b.visStart; d <= b.visEnd; d++) set.add(d)
    })

    return set
})

const kolom = computed(() =>
    Array.from({ length: jumlahHari.value }, (_, i) => {
        const d = i + 1
        const tanggal = new Date(tahun.value, bulan.value, d)
        const dow = tanggal.getDay()

        return {
            d,
            dow,
            namaHari: HARI_PENDEK[dow],
            isHariIni: tanggal.getTime() === hariIni.value.getTime(),
            isMinggu: dow === 0,
            adaBooking: hariAdaBooking.value.has(d),
        }
    })
)

/* Lebar kolom: 80px kalau ada booking, 40px kalau kosong. */
const lebarKolom = (adaBooking) => (adaBooking ? 'w-20 min-w-20' : 'w-10 min-w-10')

/* -------------------------------------------------------------------------
 *  PENEMPATAN BOOKING KE JALUR
 * ---------------------------------------------------------------------- */

/*
 * Greedy interval packing. Setiap booking masuk ke jalur pertama yang booking
 * terakhir di jalur itu sudah selesai sebelum booking ini mulai.
 *
 * Kompleksitasnya linear terhadap jumlah booking per kendaraan, dan jumlah
 * kendaraan kecil, jadi tidak perlu struktur data yang lebih rumit.
 */
function susunJalur(list) {
    const urut = [...list].sort(
        (a, b) => a.visStart - b.visStart || a.visEnd - b.visEnd
    )

    const jalur = []

    urut.forEach((b) => {
        let masuk = false

        for (const isi of jalur) {
            const akhir = isi[isi.length - 1]

            if (b.visStart > akhir.visEnd) {
                isi.push(b)
                masuk = true
                break
            }
        }

        if (!masuk) jalur.push([b])
    })

    return jalur
}

/*
 * Satu jalur diubah jadi daftar sel siap render.
 *
 * Menjalankan pointer dari kiri ke kanan, bukan mengisi per indeks, karena
 * kotak booking memakan beberapa kolom sekaligus. Kalau pengisiannya per
 * indeks, hari-hari di tengah booking juga akan ikut membuat sel kosong --
 * dan itu memecah colspan sehingga barisnya jadi lebih lebar dari seharusnya.
 */
function selJalur(jalur) {
    const sel = []
    const urut = [...jalur].sort((a, b) => a.visStart - b.visStart)

    let d = 1
    let i = 0

    while (d <= jumlahHari.value) {
        const b = urut[i]

        if (b && b.visStart === d) {
            sel.push({ tipe: 'booking', span: b.visEnd - b.visStart + 1, booking: b })
            d = b.visEnd + 1
            i += 1
        } else {
            sel.push({ tipe: 'kosong' })
            d += 1
        }
    }

    return sel
}

const baris = computed(() =>
    armada.value
        .map((car) => {
            const milik = bookingsTerpilih.value.filter(
                (b) => b.car_id === car.id
            )

            const jalur = susunJalur(milik)

            return {
                car,
                booking: milik,
                jalur,
                /* Satu jalur kosong supaya mobil yang belum disewa tetap punya
                 * kotak tampilan. */
                sel: jalur.length ? jalur.map(selJalur) : [selJalur([])],
            }
        })
        .filter((r) => !hanyaDisewa.value || r.jalur.length > 0)
)

/* -------------------------------------------------------------------------
 *  STATISTIK
 * ---------------------------------------------------------------------- */

/*
 * Dihitung dari booking yang mulai bulan ini, bukan dari booking yang lewat
 * di bulan ini. Booking yang berjalan melintasi batas bulan dihitung di bulan
 * saat ia mulai saja, supaya tidak terhitung dua kali saat wandan ke bulan
 * berikutnya.
 */
const statistik = computed(() => {
    let total = 0
    let lunas = 0
    let proses = 0

    bookingsTerpilih.value.forEach((b) => {
        if (b.mulai.getFullYear() !== tahun.value || b.mulai.getMonth() !== bulan.value) {
            return
        }

        total++

        if (b.status === 'lunas') lunas++
        else proses++
    })

    return { total, lunas, proses }
})

/* -------------------------------------------------------------------------
 *  KETERSEDIAAN
 * ---------------------------------------------------------------------- */

/*
 * Cek ketersediaan dikerjakan di sisi klien karena backend sudah mengirim
 * seluruh booking tanpa filter tanggal. Konflik berarti ada booking yang
 * rentangnya beririsan dengan rentang yang ditanyakan.
 */
function cekKetersediaan() {
    if (!cek.value.dari || !cek.value.sampai) {
        cek.value = { dari: '', sampai: '', hasil: null }
        return
    }

    if (cek.value.dari > cek.value.sampai) {
        cek.value = { ...cek.value, hasil: { galat: true } }
        return
    }

    const dari = toDate(cek.value.dari)
    const sampai = toDate(cek.value.sampai)

    const hasil = armada.value
        .map((car) => {
            const konflik = bookings.value.find(
                (b) =>
                    b.car_id === car.id &&
                    b.status_rental !== 'Batal' &&
                    toDate(b.tanggal_mulai) <= sampai &&
                    toDate(b.tanggal_selesai) >= dari
            )

            return { car, konflik: konflik || null }
        })
        /* Yang tersedia didahulukan supaya jawaban yang dicari langsung terlihat
         * tanpa perlu menggulir. */
        .sort((a, b) => Number(Boolean(a.konflik)) - Number(Boolean(b.konflik)))

    cek.value = { ...cek.value, hasil }
}

function tutupCek() {
    cek.value = { dari: '', sampai: '', hasil: null }
}

/* -------------------------------------------------------------------------
 *  AKSI
 * ---------------------------------------------------------------------- */

function keBulan(jumlah) {
    month.value = geserBulan(month.value, jumlah)
}

function keBulanIni() {
    month.value = bulanSekarang()
}

function alihkanFilter(status) {
    const index = filterStatus.value.indexOf(status)

    if (index === -1) filterStatus.value.push(status)
    else filterStatus.value.splice(index, 1)
}

const semuaFilterNyala = computed(() => filterStatus.value.length === 4)

function alihkanSemuaFilter() {
    filterStatus.value = semuaFilterNyala.value ? [] : ['lunas', 'dp', 'belum_bayar', 'belum_lunas']
}

/* Panjang teks mengikuti lebar kotak: satu hari cukup untuk nama pendek,
 * setiap hari tambahan memberi ruang untuk sembilan huruf lagi. */
function labelPemotong(booking, span) {
    const nama = booking.nama_pelanggan || 'Tanpa Nama'
    const maks = 7 + (span - 1) * 9

    return nama.length > maks ? nama.slice(0, maks) + '...' : nama
}

/* Tooltip native, bukan komponen khusus: isinya sudah jelas dari kotak yang
 * diklik, jadi tooltip cukup sebagai pengingat tanpa harus bisa di-hover. */
function judulTooltip(booking) {
    const bagian = [
        booking.nama_pelanggan || 'Tanpa Nama',
        gayaStatus(booking.status).label,
        `${formatTanggal(booking.tanggal_mulai)} - ${formatTanggal(booking.tanggal_selesai)}`,
    ]

    if (booking.lokasi_jemput) bagian.push(`Jemput: ${booking.lokasi_jemput}`)
    if (booking.lokasi_antar) bagian.push(`Antar: ${booking.lokasi_antar}`)

    return bagian.join(' | ')
}

muat()
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Kepala -->
        <div
            class="flex flex-col md:flex-row md:justify-between md:items-start md:items-end gap-4 mb-6"
        >
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Kalender Jadwal</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Booking dan ketersediaan semua armada.
                </p>
            </div>

            <RouterLink to="/bookings/create" class="btn-primary">
                <span class="material-symbols-outlined ms-icon text-[18px]">add</span>
                Tambah Pesanan
            </RouterLink>
        </div>

        <!-- Cek ketersediaan -->
        <div
            class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 mb-5 flex flex-wrap items-center gap-3"
        >
            <span class="material-symbols-outlined ms-icon text-blue-500 text-[18px]">search</span>
            <span class="text-sm font-medium text-blue-800 shrink-0">Cek Ketersediaan:</span>

            <div class="flex flex-wrap items-center gap-2 flex-1">
                <div
                    class="flex items-center gap-1.5 bg-white border border-blue-200 rounded-lg px-3 py-1.5"
                >
                    <label for="cekDari" class="text-xs text-slate-500">Dari</label>
                    <input
                        id="cekDari"
                        v-model="cek.dari"
                        type="date"
                        class="text-sm text-slate-700 bg-transparent border-none outline-none font-medium focus:ring-0 p-0"
                    />
                </div>

                <span class="text-slate-400 text-sm">&mdash;</span>

                <div
                    class="flex items-center gap-1.5 bg-white border border-blue-200 rounded-lg px-3 py-1.5"
                >
                    <label for="cekSampai" class="text-xs text-slate-500">Sampai</label>
                    <input
                        id="cekSampai"
                        :min="cek.dari || undefined"
                        v-model="cek.sampai"
                        type="date"
                        class="text-sm text-slate-700 bg-transparent border-none outline-none font-medium focus:ring-0 p-0"
                    />
                </div>

                <button type="button" class="btn-primary text-xs py-1.5" @click="cekKetersediaan">
                    Cek Sekarang
                </button>
            </div>

            <button
                v-if="cek.hasil"
                type="button"
                class="text-slate-400 hover:text-slate-600 shrink-0"
                aria-label="Tutup hasil cek"
                @click="tutupCek"
            >
                <span class="material-symbols-outlined ms-icon text-[18px]">close</span>
            </button>
        </div>

        <!-- Hasil cek -->
        <section
            v-if="cek.hasil"
            class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-5"
        >
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-700">Hasil Cek Ketersediaan</h3>

                <span class="text-xs text-slate-500 font-medium">
                    <template v-if="cek.hasil.galat">Rentang tanggal tidak valid.</template>
                    <template v-else>
                        {{ formatTanggal(cek.dari) }} &ndash; {{ formatTanggal(cek.sampai) }}
                    </template>
                </span>
            </div>

            <p
                v-if="cek.hasil.galat"
                class="px-4 py-3 text-sm text-rose-600"
            >
                Tanggal &quot;Dari&quot; tidak boleh lebih besar dari &quot;Sampai&quot;.
            </p>

            <div v-else class="divide-y divide-slate-50 max-h-60 overflow-y-auto">
                <div
                    v-for="b in cek.hasil"
                    :key="b.car.id"
                    class="flex items-center gap-3 px-4 py-3"
                >
                    <span
                        class="w-2 h-2 rounded-full shrink-0"
                        :class="b.konflik ? 'bg-rose-400' : 'bg-emerald-400'"
                    ></span>

                    <RouterLink
                        :to="`/armada/${b.car.id}`"
                        class="text-sm font-semibold text-slate-700 hover:text-brand-600 transition-colors"
                    >
                        {{ b.car.plat_nomor }}
                    </RouterLink>

                    <span class="text-sm text-slate-400 truncate">{{ b.car.nama }}</span>

                    <span class="ml-auto shrink-0">
                        <span
                            v-if="b.konflik"
                            class="text-xs font-medium text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full inline-block"
                        >
                            Dipesan {{ b.konflik.nama_pelanggan || 'Tanpa Nama' }}
                        </span>
                        <span
                            v-else
                            class="text-xs font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full inline-block"
                        >
                            Tersedia
                        </span>
                    </span>
                </div>
            </div>
        </section>

        <!-- Navigasi bulan + filter -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
                    aria-label="Bulan sebelumnya"
                    @click="keBulan(-1)"
                >
                    <span class="material-symbols-outlined ms-icon text-[18px]">chevron_left</span>
                </button>

                <h2 class="text-base font-semibold text-slate-900 min-w-[150px] text-center">
                    {{ bulanIniLabel }}
                </h2>

                <button
                    type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
                    aria-label="Bulan berikutnya"
                    @click="keBulan(1)"
                >
                    <span class="material-symbols-outlined ms-icon text-[18px]">chevron_right</span>
                </button>

                <button
                    type="button"
                    class="ml-1 text-xs font-medium text-brand-600 hover:text-brand-700 border border-brand-200 hover:border-brand-300 px-3 py-1.5 rounded-lg transition-colors"
                    @click="keBulanIni"
                >
                    Bulan ini
                </button>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs text-slate-500 font-medium">Filter:</span>

                <button
                    v-for="(cfg, key) in KONFIG_STATUS"
                    :key="key"
                    type="button"
                    class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border transition-all"
                    :class="
                        filterStatus.includes(key)
                            ? cfg.chip
                            : 'bg-white text-slate-400 border-slate-200'
                    "
                    :aria-pressed="filterStatus.includes(key)"
                    @click="alihkanFilter(key)"
                >
                    <span
                        class="w-2 h-2 rounded-full"
                        :class="filterStatus.includes(key) ? cfg.dot : 'bg-slate-300'"
                    ></span>
                    {{ cfg.label }}
                </button>

                <button
                    type="button"
                    class="text-xs font-medium px-2 py-1.5 rounded-lg transition-colors"
                    :class="semuaFilterNyala ? 'text-slate-400 hover:text-slate-600' : 'text-brand-600'"
                    @click="alihkanSemuaFilter"
                >
                    {{ semuaFilterNyala ? 'Matikan semua' : 'Nyalakan semua' }}
                </button>

                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input v-model="hanyaDisewa" type="checkbox" class="pf-toggle" />
                    <span class="text-xs text-slate-500 font-medium">
                        Hanya tampilkan yang disewa
                    </span>
                </label>
            </div>
        </div>

        <DataState
            :loading="memuat"
            :error="error"
            :is-empty="false"
            @retry="muat"
        >
            <!-- Grid -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                <div v-if="baris.length" class="overflow-x-auto overflow-y-auto max-h-[65vh]">
                    <table class="border-collapse min-w-max table-fixed">
                        <thead class="sticky top-0 z-20 bg-white shadow-sm">
                            <tr>
                                <th
                                    class="sticky left-0 z-10 bg-slate-50 border-b border-r border-slate-200 px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-[220px] min-w-[220px]"
                                >
                                    Armada
                                </th>

                                <th
                                    v-for="k in kolom"
                                    :key="k.d"
                                    class="border-b border-r px-0 py-2 text-center transition-all duration-300"
                                    :class="[
                                        lebarKolom(k.adaBooking),
                                        k.isHariIni
                                            ? 'bg-blue-50 border-x-2 border-x-blue-400 relative z-10'
                                            : 'border-slate-200',
                                        !k.isHariIni && k.isMinggu ? 'bg-rose-50/40' : '',
                                    ]"
                                >
                                    <div
                                        class="text-[11px] font-bold"
                                        :class="
                                            k.isHariIni
                                                ? 'text-brand-600'
                                                : k.isMinggu
                                                  ? 'text-rose-400'
                                                  : 'text-slate-500'
                                        "
                                    >
                                        {{ k.d }}
                                    </div>
                                    <div
                                        class="text-[9px] font-medium"
                                        :class="
                                            k.isHariIni
                                                ? 'text-brand-400'
                                                : k.isMinggu
                                                  ? 'text-rose-300'
                                                  : 'text-slate-400'
                                        "
                                    >
                                        {{ k.namaHari }}
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <!-- Satu <tr> per jalur, agar tinggi tiap jalur tetap -->
                            <template
                                v-for="(r, rowIdx) in baris"
                                :key="r.car.id"
                            >
                                <tr
                                    v-for="(sel, laneIdx) in r.sel"
                                    :key="`${r.car.id}-${laneIdx}`"
                                    class="group transition-colors"
                                    :class="
                                        rowIdx % 2 === 1
                                            ? 'bg-slate-50/50'
                                            : 'bg-white'
                                    "
                                >
                                    <!-- Nama kendaraan hanya di jalur pertama -->
                                    <td
                                        v-if="laneIdx === 0"
                                        :rowspan="r.sel.length"
                                        class="sticky left-0 z-10 border-r border-b border-slate-200 px-4 py-3 align-top"
                                        :class="
                                            rowIdx % 2 === 1 ? 'bg-slate-50' : 'bg-white'
                                        "
                                    >
                                        <RouterLink
                                            :to="`/armada/${r.car.id}`"
                                            class="text-xs font-bold text-slate-700 tracking-wide hover:text-brand-600 transition-colors"
                                        >
                                            {{ r.car.plat_nomor }}
                                        </RouterLink>

                                        <p class="text-xs text-slate-500 mt-0.5">{{ r.car.nama }}</p>

                                        <span
                                            class="inline-block mt-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
                                            :class="gayaArmada(r.car.status).cls"
                                        >
                                            {{ gayaArmada(r.car.status).label }}
                                        </span>
                                    </td>

                                    <!-- Sel -->
                                    <td
                                        v-for="(cell, i) in sel"
                                        :key="i"
                                        class="border-b border-r px-1 py-1"
                                        :colspan="cell.tipe === 'booking' ? cell.span : 1"
                                        :class="
                                            kolom[i].isHariIni
                                                ? 'bg-blue-50/50 border-l-2 border-l-blue-400 relative z-[5]'
                                                : 'border-slate-200'
                                        "
                                    >
                                        <div
                                            v-if="cell.tipe === 'booking'"
                                            class="w-full h-full flex items-center gap-2 overflow-hidden rounded-lg px-2.5 py-1.5 border cursor-pointer transition-all duration-200 hover:shadow-md"
                                            :class="gayaStatus(cell.booking.status).bar"
                                            :title="judulTooltip(cell.booking)"
                                            @click="detail = cell.booking"
                                        >
                                            <span class="flex items-center gap-1.5 min-w-0 flex-1">
                                                <span
                                                    class="w-2 h-2 rounded-full shrink-0"
                                                    :class="
                                                        gayaStatus(cell.booking.status).dot
                                                    "
                                                ></span>
                                                <span class="text-xs font-bold truncate tracking-tight">
                                                    {{
                                                        labelPemotong(
                                                            cell.booking,
                                                            cell.span
                                                        )
                                                    }}
                                                </span>
                                            </span>

                                            <!-- Lencana status hanya berguna kalau
                                                 kotaknya cukup lebar untuk dibaca. -->
                                            <span
                                                v-if="cell.span > 1"
                                                class="shrink-0 px-1.5 py-0.5 rounded text-[9px] font-bold bg-white/60 border hidden md:inline-block"
                                                :class="
                                                    cell.booking.status === 'lunas'
                                                        ? 'border-emerald-300 text-emerald-700'
                                                        : cell.booking.status === 'dp'
                                                          ? 'border-blue-300 text-blue-700'
                                                          : cell.booking.status === 'belum_lunas'
                                                            ? 'border-orange-300 text-orange-700'
                                                            : 'border-amber-300 text-amber-700'
                                                "
                                            >
                                                {{ gayaStatus(cell.booking.status).label }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div v-else class="py-16 px-6 text-center">
                    <span class="material-symbols-outlined text-[36px] text-slate-300">
                        event_busy
                    </span>

                    <h3 class="text-base font-semibold text-slate-700 mt-3">
                        Tidak ada jadwal di {{ bulanIniLabel }}
                    </h3>

                    <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                        <template v-if="hanyaDisewa && filterStatus.length">
                            Coba matikan &quot;Hanya tampilkan yang disewa&quot; atau ubah filter
                            status pembayaran.
                        </template>
                        <template v-else-if="!filterStatus.length">
                            Semua filter status dimatikan, jadi tidak ada pesanan yang bisa
                            ditampilkan.
                        </template>
                        <template v-else>
                            Belum ada booking pada bulan ini.
                        </template>
                    </p>

                    <button
                        v-if="!filterStatus.length"
                        type="button"
                        class="btn-secondary text-xs mt-4"
                        @click="alihkanSemuaFilter"
                    >
                        Nyalakan semua filter
                    </button>
                </div>
            </div>

            <!-- Statistik -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-5">
                <div class="bg-white border border-slate-200 rounded-2xl px-5 py-4 shadow-sm">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="material-symbols-outlined ms-icon text-[18px] text-slate-500">
                            book_online
                        </span>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Total Booking
                        </p>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">{{ statistik.total }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">dimulai bulan ini</p>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl px-5 py-4 shadow-sm">
                    <div class="flex items-center gap-2 mb-1">
                        <span
                            class="material-symbols-outlined ms-icon text-[18px] text-emerald-500"
                        >
                            task_alt
                        </span>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Lunas
                        </p>
                    </div>
                    <p class="text-2xl font-bold text-emerald-600">{{ statistik.lunas }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">sudah pembayaran penuh</p>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl px-5 py-4 shadow-sm">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="material-symbols-outlined ms-icon text-[18px] text-blue-500">
                            pending_actions
                        </span>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            DP / Proses
                        </p>
                    </div>
                    <p class="text-2xl font-bold text-blue-600">{{ statistik.proses }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">perlu tindak lanjut</p>
                </div>
            </div>
        </DataState>

        <!-- Modal detail booking -->
        <div
            v-if="detail"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
            @click.self="detail = null"
        >
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-900 truncate">
                            {{ detail.nama_pelanggan || 'Tanpa Nama' }}
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ detail.nomor_pesanan }}</p>
                    </div>

                    <span
                        class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full"
                        :class="gayaStatus(detail.status).chip"
                    >
                        {{ gayaStatus(detail.status).label }}
                    </span>
                </div>

                <div class="px-5 py-4 space-y-3">
                    <div class="flex items-center gap-2 text-sm">
                        <span
                            class="material-symbols-outlined ms-icon text-slate-400 text-[18px]"
                        >
                            directions_car
                        </span>
                        <span class="text-slate-500">Kendaraan:</span>
                        <span class="font-medium text-slate-800 ml-auto text-right">
                            {{ detail.nama_mobil || '-' }}
                            <span v-if="detail.plat_nomor" class="text-slate-500">
                                ({{ detail.plat_nomor }})
                            </span>
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-sm">
                        <span
                            class="material-symbols-outlined ms-icon text-slate-400 text-[18px]"
                        >
                            calendar_month
                        </span>
                        <span class="text-slate-500">Tanggal:</span>
                        <span class="font-medium text-slate-800 ml-auto text-right">
                            {{ formatTanggal(detail.tanggal_mulai) }}
                            &ndash;
                            {{ formatTanggal(detail.tanggal_selesai) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-sm">
                        <span
                            class="material-symbols-outlined ms-icon text-slate-400 text-[18px]"
                        >
                            receipt
                        </span>
                        <span class="text-slate-500">Tipe Sewa:</span>
                        <span class="font-medium text-slate-800 ml-auto">{{ detail.tipe_sewa }}</span>
                    </div>

                    <div class="flex items-center gap-2 text-sm">
                        <span
                            class="material-symbols-outlined ms-icon text-slate-400 text-[18px]"
                        >
                            payments
                        </span>
                        <span class="text-slate-500">Nominal:</span>
                        <span class="font-bold text-slate-900 ml-auto">
                            {{ formatRupiah(detail.total_harga) }}
                        </span>
                    </div>

                    <div v-if="detail.lokasi_jemput || detail.lokasi_antar" class="pt-1">
                        <div
                            v-if="detail.lokasi_jemput"
                            class="flex items-start gap-2 text-sm"
                        >
                            <span
                                class="material-symbols-outlined ms-icon text-slate-400 text-[18px]"
                            >
                                pin_drop
                            </span>
                            <span class="text-slate-500">Jemput:</span>
                            <span class="font-medium text-slate-800 ml-auto text-right">
                                {{ detail.lokasi_jemput }}
                            </span>
                        </div>

                        <div
                            v-if="detail.lokasi_antar"
                            class="flex items-start gap-2 text-sm mt-2"
                        >
                            <span
                                class="material-symbols-outlined ms-icon text-slate-400 text-[18px]"
                            >
                                flag
                            </span>
                            <span class="text-slate-500">Antar:</span>
                            <span class="font-medium text-slate-800 ml-auto text-right">
                                {{ detail.lokasi_antar }}
                            </span>
                        </div>
                    </div>

                    <p
                        v-if="detail.status_rental === 'Batal'"
                        class="text-xs text-rose-600 bg-rose-50 px-2 py-1.5 rounded-lg"
                    >
                        Pesanan ini berstatus Batal.
                    </p>
                </div>

                <div class="px-5 pb-5 flex gap-2">
                    <RouterLink
                        :to="`/bookings/${detail.booking_id}`"
                        class="flex-1 text-center btn-primary text-sm py-2"
                    >
                        Lihat Detail
                    </RouterLink>

                    <button
                        type="button"
                        class="flex-1 text-center btn-secondary text-sm py-2"
                        @click="detail = null"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/*
 * Toggle kecil untuk "hanya tampilkan yang disewa". Bergaya checkbox biasa
 * yang dipoles jadi tombol geser; input disembunyikan supaya tetap bisa
 * dibaca screen reader dan tetap memakai perilaku checkbox bawaan browser.
 */
.pf-toggle {
    appearance: none;
    width: 2rem;
    height: 1.15rem;
    border-radius: 9999px;
    background-color: #cbd5e1;
    position: relative;
    cursor: pointer;
    transition: background-color 0.2s;
    flex-shrink: 0;
}

.pf-toggle::after {
    content: '';
    position: absolute;
    top: 0.15rem;
    left: 0.15rem;
    width: 0.85rem;
    height: 0.85rem;
    border-radius: 9999px;
    background-color: #fff;
    transition: transform 0.2s;
}

.pf-toggle:checked {
    background-color: #2563eb;
}

.pf-toggle:checked::after {
    transform: translateX(0.85rem);
}

.pf-toggle:focus-visible {
    outline: 2px solid #6366f1;
    outline-offset: 2px;
}
</style>