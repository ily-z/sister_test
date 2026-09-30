/**
 * ══════════════════════════════════════════════════════════════════════════
 *  FORMAT TAMPILAN
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Semua angka dan tanggal di aplikasi ini melewati file ini, supaya format
 *  di 50+ halaman tidak berbeda-beda.
 *
 *  ══════════════════════════════════════════════════════════════════════════
 *  SOAL TANGGAL BERBAHASA INDONESIA
 *  ══════════════════════════════════════════════════════════════════════════
 *
 *  Backend sengaja memakai APP_LOCALE=en, dan tabel nama bulan ditulis di
 *  sini dengan tangan.
 *
 *  Catatan: ini BUKAN karena Carbon gagal memuat locale "id". Carbon 3.13
 *  memuatnya dengan benar -- translatedFormat("d F Y") hasilnya
 *  "01 Oktober 2026". Yang bermasalah adalah penerjemah Laravel: paket ini
 *  hanya menyertakan terjemahan "en" dan proyek tidak punya folder lang/.
 *  Kalau APP_LOCALE dan APP_FALLBACK_LOCALE sama-sama "id", pesan validasi
 *  tampil sebagai kunci mentah ("validation.email").
 *
 *  Jadi nama bulan ditulis di sini supaya: (1) tampilan tidak bergantung
 *  pada konfigurasi locale backend, dan (2) semua halaman memakai satu
 *  sumber label yang sama. Detailnya di docs/KNOWN_ISSUES.md bagian 4.
 */

const BULAN_PENDEK = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
]

const BULAN_PANJANG = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']

/**
 * Ubah input apa pun menjadi Date, atau null kalau tidak bisa.
 *
 * Input yang ditangani:
 *   - Date
 *   - string ISO 8601 dari API ("2026-10-01T00:00:00.000000Z")
 *   - string tanggal saja ("2026-10-01")
 *   - angka timestamp (detik atau milidetik)
 *
 * Catatan penting soal string tanggal saja: `new Date("2026-10-01")` di
 * JavaScript dibaca sebagai UTC tengah malam, lalu ditampilkan sebagai
 * tanggal sebelumnya bagi pengguna di negatif offset (mis. WIB, UTC+7
 * aman, tetapi pengguna di UTC-5 akan melihat 30 September). Karena itu
 * string tanggal saja diparse manual, bukan lewat Date.
 */
export function toDate(value) {
    if (value === null || value === undefined || value === '') return null

    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value
    }

    if (typeof value === 'number') {
        // Backend mengirim timestamp dalam detik. Nilai di atas 1e11
        // sudah pasti milidetik.
        const ms = value > 1e11 ? value : value * 1000
        const d = new Date(ms)
        return Number.isNaN(d.getTime()) ? null : d
    }

    const raw = String(value).trim()

    const tanggalSaja = /^(\d{4})-(\d{2})-(\d{2})$/.exec(raw)
    if (tanggalSaja) {
        const [, y, m, d] = tanggalSaja
        return new Date(Number(y), Number(m) - 1, Number(d))
    }

    const withTime = /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/.exec(raw)
    if (withTime) {
        const [, y, m, d, hh, mm] = withTime
        return new Date(Number(y), Number(m) - 1, Number(d), Number(hh), Number(mm))
    }

    const parsed = new Date(raw)
    return Number.isNaN(parsed.getTime()) ? null : parsed
}

const pad = (n) => String(n).padStart(2, '0')

/** "01/10/2026" */
export function formatTanggal(value) {
    const d = toDate(value)
    if (!d) return '-'

    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`
}

/** "01 Okt 2026" */
export function formatTanggalPendek(value) {
    const d = toDate(value)
    if (!d) return '-'

    return `${pad(d.getDate())} ${BULAN_PENDEK[d.getMonth()]} ${d.getFullYear()}`
}

/** "1 Oktober 2026" */
export function formatTanggalPanjang(value) {
    const d = toDate(value)
    if (!d) return '-'

    return `${d.getDate()} ${BULAN_PANJANG[d.getMonth()]} ${d.getFullYear()}`
}

/** "Senin, 1 Oktober 2026" */
export function formatTanggalLengkap(value) {
    const d = toDate(value)
    if (!d) return '-'

    return `${HARI[d.getDay()]}, ${formatTanggalPanjang(d)}`
}

/** "01/10/2026 14:30" */
export function formatTanggalWaktu(value) {
    const d = toDate(value)
    if (!d) return '-'

    return `${formatTanggal(d)} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}

/** "01 Okt 2026, 14:30" */
export function formatWaktuPendek(value) {
    const d = toDate(value)
    if (!d) return '-'

    return `${formatTanggalPendek(d)}, ${pad(d.getHours())}:${pad(d.getMinutes())}`
}

/** "14:30" */
export function formatWaktu(value) {
    const d = toDate(value)
    if (!d) return '-'

    return `${pad(d.getHours())}:${pad(d.getMinutes())}`
}

/**
 * Selisih waktu dalam bahasa manusia, untuk kolom "terakhir aktif".
 *
 * Nilai dikembalikan dalam detik agar pemanggil bebas memilih unitnya.
 * Timeout bukan kasus di sini: backend sudah membatasi jangkauan lewat
 * limit dan offset.
 */
export function sejakDetik(value) {
    const d = toDate(value)
    if (!d) return null

    return Math.floor((Date.now() - d.getTime()) / 1000)
}

/** "baru saja", "5 menit lalu", "3 jam lalu", "2 hari lalu" */
export function formatSejak(value) {
    const detik = sejakDetik(value)
    if (detik === null) return '-'

    if (detik < 45) return 'baru saja'

    const menit = Math.floor(detik / 60)
    if (menit < 60) return `${menit} menit lalu`

    const jam = Math.floor(menit / 60)
    if (jam < 24) return `${jam} jam lalu`

    const hari = Math.floor(jam / 24)
    if (hari < 30) return `${hari} hari lalu`

    const bulan = Math.floor(hari / 30)
    if (bulan < 12) return `${bulan} bulan lalu`

    return `${Math.floor(bulan / 12)} tahun lalu`
}

/** ---------------------------------------------------------------------- */

/**
 * Angka -&gt; "Rp 1.500.000"
 *
 * Dipakai untuk nominal uang. Pecahan dua digit ("Rp 1.500.000,00")
 * sengaja TIDAK dipakai di tampilan daftar: kolom jadi jauh lebih lebar
 * dan informasinya tidak bertambah. Kalau diperlukan (mis. di invoice),
 * pakai formatRuangPenuh().
 */
export function formatRupiah(value, { prefix = 'Rp ' } = {}) {
    const angka = Number(value)

    if (!Number.isFinite(angka)) return '-'

    return prefix + new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(Math.round(angka))
}

/** "Rp 1.500.000,00" */
export function formatRupiahPenuh(value, { prefix = 'Rp ' } = {}) {
    const angka = Number(value)

    if (!Number.isFinite(angka)) return '-'

    return prefix + new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(angka)
}

/**
 * Angka besar disingkat supaya muat di kartu statistik.
 *
 * "Rp 1,2 M", "Rp 850 jt", "Rp 750 rb"
 */
export function formatRupiahRingkas(value) {
    const angka = Number(value)

    if (!Number.isFinite(angka)) return '-'

    const abs = Math.abs(angka)
    const tanda = angka < 0 ? '-' : ''

    if (abs >= 1e12) return `${tanda}Rp ${(abs / 1e12).toFixed(1).replace('.', ',')} T`
    if (abs >= 1e9) return `${tanda}Rp ${(abs / 1e9).toFixed(1).replace('.', ',')} M`
    if (abs >= 1e6) return `${tanda}Rp ${Math.round(abs / 1e6)} jt`
    if (abs >= 1e3) return `${tanda}Rp ${Math.round(abs / 1e3)} rb`

    return formatRupiah(angka)
}

/** "1.250" -- untuk angka biasa seperti jumlah unit,/km. */
export function formatAngka(value) {
    const angka = Number(value)

    if (!Number.isFinite(angka)) return '-'

    return new Intl.NumberFormat('id-ID').format(angka)
}

/** ---------------------------------------------------------------------- */

/**
 * Berapa lama lagi / sudah berapa lama sebuah masa berlaku.
 *
 * Dipakai untuk status masa berlaku token. Nilai negatif berarti sudah
 * lewat.
 */
export function formatSisaWaktu(value) {
    const detik = sejakDetik(value)
    if (detik === null) return '-'

    if (detik < 0) return 'telah kedaluwarsa'

    const jam = Math.floor(detik / 3600)
    const menit = Math.floor((detik % 3600) / 60)

    if (jam >= 24) return `${Math.floor(jam / 24)} hari ${jam % 24} jam`
    if (jam >= 1) return `${jam} jam ${menit} menit`

    return `${menit} menit`
}

/** ---------------------------------------------------------------------- */

/**
 * Samakan nama file supaya aman dipakai sebagai nama path.
 *
 * Dipakai di sisi klien sebelum upload, supaya nama file dengan karakter
 * aneh tidak sampai ke server. Yang menentukan nama akhir tetap server;
 * fungsi ini hanya mengurangi kejutan.
 */
export function bersihkanNamaFile(name) {
    return String(name || '')
        .replace(/[^\w.\- ]+/g, '')
        .replace(/\s+/g, '-')
        .slice(0, 120)
}

/** ---------------------------------------------------------------------- */

/**
 * Ubah "2026-01" atau "2026-01-15" menjadi nama bulan berbahasa Indonesia.
 *
 * Endpoint keuangan menerima dan mengembalikan bulan dalam format Y-m, dan
 * backend juga mengirim `monthLabel` hasil translatedFormat(). Karena
 * APP_LOCALE sengaja `en`, monthLabel itu berbahasa Inggris -- jadi label
 * dihitung di sini agar konsisten dengan halaman lain.
 *
 * Nilai yang tidak cocok pola Y-m dikembalikan apa adanya, bukan diubah jadi
 * "-", supaya keliru pada data tetap terlihat saat diuji.
 */
export function formatBulan(value, { panjang = false } = {}) {
    if (!value) return '-'

    const cocok = String(value).match(/^(\d{4})-(\d{2})/)

    if (!cocok) return String(value)

    return namaBulanDariIndex(cocok[2], panjang)
}

function namaBulanDariIndex(teks, panjang) {
    const daftar = panjang ? BULAN_PANJANG : BULAN_PENDEK
    const index = Number(teks) - 1

    if (index < 0 || index > 11) return '-'

    return daftar[index]
}

/**
 * Gabungan bulan dan tahun, untuk judul halaman keuangan.
 *
 * "2026-01" menjadi "Januari 2026". Nilai yang bukan Y-m dikembalikan
 * apa adanya.
 */
export function formatBulanTahun(value, { panjang = true } = {}) {
    if (!value) return '-'

    const cocok = String(value).match(/^(\d{4})-(\d{2})/)

    if (!cocok) return String(value)

    return `${namaBulanDariIndex(cocok[2], panjang)} ${cocok[1]}`
}

/**
 * Geser bulan pada format Y-m.
 *
 * Dipakai tombol navigasi bulan di laporan keuangan. Sengaja tidak memakai
 * Date: new Date("2026-01") dihitung sebagai UTC, dan di zona waktu negatif
 * hasilnya bisa bergeser ke Desember tahun sebelumnya.
 */
export function geserBulan(value, jumlah) {
    if (!value) return '-'

    const cocok = String(value).match(/^(\d{4})-(\d{2})/)

    if (!cocok) return String(value)

    const tahun = Number(cocok[1])
    const bulan = Number(cocok[2]) - 1 + jumlah

    /* bulanTotal sengaja dihitung penuh supaya perpindahan dari Desember ke
     * Januari, atau Februari ke Januari tahun sebelumnya, tidak perlu
     * ditangani terpisah. */
    const bulanTotal = tahun * 12 + bulan
    const tahunBaru = Math.floor(bulanTotal / 12)
    const bulanBaru = bulanTotal - tahunBaru * 12

    return `${tahunBaru}-${String(bulanBaru + 1).padStart(2, '0')}`
}

