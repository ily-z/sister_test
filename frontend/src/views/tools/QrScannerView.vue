<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

/*
 * ============================================================================
 *  PEMINDAI QR
 * ============================================================================
 *
 *  Memindahkan backend/resources/js/qr-scanner.js ke dalam SPA. Alurnya sama:
 *  kamera dinyalakan, QR yang terbaca ditampilkan, dan kalau yang terbaca
 *  adalah QR invoice, pemindai dihentikan supaya tidak membaca kode yang
 *  sama terus-menerus.
 *
 *  --- PUSTAKA SCANNER DIMUAT SAAT TOMBOL DIKLIK ----------------------------
 *
 *  qr-scanner membawa dekoder QR-nya sendiri beserta worker dan kamera, dan
 *  tidak ada satu pun yang dibutuhkan sebelum operator menekan "Mulai Scan".
 *  Kalau diimpor di atas, bobotnya masuk ke bundel awal yang diunduh setiap
 *  kali siapa pun membuka aplikasi -- termasuk operator yang cuma mau membuka
 *  daftar booking. import() dinamis menaruhnya di berkas terpisah yang diambil
 *  saat itu juga.
 *
 *  --- KAMERA MATI SAAT PINDAH HALAMAN ---------------------------------------
 *
 *  onBeforeUnmount() memanggil destroy(). Tanpa itu, begitu operator menekan
 *  menu lain di sidebar, kamera tetap menyala dan ikon kamera masih terlihat
 *  di bilah atas sistem. Halaman ini bisa ditinggalkan kapan saja, jadi ini
 *  bukan kasus yang jarang terjadi.
 *
 *  --- HASIL SCAN TIDAK PERNAH DI-RENDER SEBAGAI HTML ------------------------
 *
 *  Versi Blade menyusun hasil scan dengan innerHTML. Apa pun yang tertulis di
 *  QR ikut ter-parse, dan bisa jadi berisi <script> atau atribut onerror.
 *  Di sini isinya hanya masuk lewat interpolasi {{ }}, jadi peramban
 *  memperlakukannya sebagai teks biasa. Untuk URL verifikasi pun begitu: teks
 *  aslinya ditampilkan apa adanya, dan tautannya dipasang lewat :href -- bukan
 *  lewat HTML yang dirakit sendiri.
 *
 *  --- VERIFIKASI MEMBUKA HALAMAN PUBLIK, BUKAN HALAMAN INTERNAL --------------
 *
 *  QR di PDF invoice berisi {FRONTEND_URL}/verify/{token}, dan halaman itu
 *  sengaja tidak butuh login: pihak yang memindai biasanya orang luar yang
 *  tidak punya akun. Karena itu tautannya membuka tab baru.
 *
 *  --- KAMERA HANYA BOLEH DIJALANKAN DI https:// ATAU localhost --------------
 *
 *  Peramban hanya mengizinkan getUserMedia pada origin yang dianggap aman.
 *  http:// selain localhost termasuk origin yang tidak aman, dan nama host
 *  yang muncul lewat USB atau tethering sering ikut disalahkan.
 *
 *  Konsekuensinya untuk halaman ini: pemindaian dari berkas gambar tetap
 *  bekerja di http://, karena tidak ada yang perlu izin kamera. Tombol Mulai
 *  Scan akan ditolak peramban, dan pesannya ditampilkan apa adanya supaya
 *  penyebabnya kelihatan, bukan sekadar "Gagal".
 */

const video = ref(null)
const inputGambar = ref(null)

const memindai = ref(false)
const memuatPustaka = ref(false)
const punyaFlash = ref(false)
const flashNyala = ref(false)

const hasil = ref('')
const verifikasi = ref('')
const galat = ref('')

/* Daftar kamera: { deviceId: label }. Disimpan sebagai ref supaya pilihan
 * operator ikut hilang bersama halaman -- tidak ada yang perlu bertahan. */
const daftarKamera = ref({})

/* Device id kamera yang dipilih. String kosong berarti "kamera bawaan",
 * yang juga keadaan paling sering dipakai pada laptop dengan kamera depan
 * saja. */
const kameraTerpilih = ref('')

/*
 * Objek scanner sengaja tidak dibungkus ref(). Pustaka ini sudah mengelola
 * stream-nya sendiri; membuatnya reaktif hanya menambah pekerjaan tanpa
 * manfaat apa pun.
 */
let scanner = null

/* -------------------------------------------------------------------------
 *  HASIL SCAN
 * ---------------------------------------------------------------------- */

/*
 * Hasil scan dianggap QR invoice kalau bisa diurai jadi URL yang jalannya
 * berakhir dengan /verify/{token}. Polanya sengaja ketat -- hanya huruf dan
 * angka setelah "verify" -- supaya teks sembarang yang kebetulan memuat kata
 * itu tidak ikut dianggap QR invoice.
 */
function cariUrlVerifikasi(teks) {
    try {
        const url = new URL(teks)

        if (/\/verify\/[A-Za-z0-9]+$/.test(url.pathname)) return url.href
    } catch {
        // Bukan URL yang valid. Diperlakukan sebagai teks biasa.
    }

    return null
}

function terapkanHasil(teks) {
    hasil.value = String(teks ?? '')
    verifikasi.value = cariUrlVerifikasi(hasil.value)
    galat.value = ''
}

/*
 * Denial dari getUserMedia punya nama yang jauh lebih berguna daripada
 * pesan bawaannya, jadi dipetakan satu per satu. Menampilkan
 * NotAllowedError apa adanya memang jujur, tapi tidak menjelaskan apa yang
 * harus diperbaiki.
 */
function jelaskanKegagalan(e) {
    switch (e?.name) {
        case 'NotAllowedError':
        case 'SecurityError':
            return 'Izin kamera ditolak. Izinkan akses kamera di pengaturan peramban lalu coba lagi. Kamera hanya bisa dinyalakan lewat https:// atau di localhost.'

        case 'NotFoundError':
        case 'DevicesNotFoundError':
            return 'Tidak ada kamera yang ditemukan di perangkat ini.'

        case 'OverconstrainedError':
            return 'Kamera yang dipilih tidak tersedia. Pilih kamera lain atau kembali ke kamera bawaan.'

        case 'NotReadableError':
        case 'TrackStartError':
            return 'Kamera tidak bisa dibuka. Kemungkinan sedang dipakai aplikasi lain.'

        default:
            return e?.message || 'Kamera tidak bisa dinyalakan.'
    }
}

async function muatPustaka() {
    const { default: QrScanner } = await import('qr-scanner')

    return QrScanner
}

/* -------------------------------------------------------------------------
 *  KAMERA
 * ---------------------------------------------------------------------- */

async function mulaiScan() {
    if (memindai.value || memuatPustaka.value) return

    memuatPustaka.value = true
    galat.value = ''

    try {
        const QrScanner = await muatPustaka()

        if (scanner) {
            scanner.destroy()
            scanner = null
        }

        scanner = new QrScanner(
            video.value,
            (result) => {
                terapkanHasil(result.data)

                /* Berhenti begitu QR invoice terbaca. Kalau tidak, kamera
                 * tetap membaca kode yang sama dan menimpa hasil di setiap
                 * frame -- pengguna tidak pernah sempat menekan tombol yang
                 * baru saja muncul. */
                if (verifikasi.value) berhenti()
            },
            {
                returnDetailedScanResult: true,
                highlightScanRegion: true,
                highlightCodeOutline: true,
            }
        )

        if (kameraTerpilih.value) await scanner.setCamera(kameraTerpilih.value)

        await scanner.start()

        memindai.value = true
        flashNyala.value = false

        /* Flash hanya ada di sebagian perangkat. Tombolnya disembunyikan kalau
         * memang tidak ada -- lebih baik daripada menampilkan tombol yang
         * pasti gagal saat diklik. */
        try {
            punyaFlash.value = await scanner.hasFlash()
        } catch {
            punyaFlash.value = false
        }
    } catch (e) {
        berhenti()
        terapkanHasil('')
        galat.value = jelaskanKegagalan(e)
    } finally {
        memuatPustaka.value = false
    }
}

function berhenti() {
    if (scanner) {
        try {
            scanner.stop()
        } catch {
            // Scanner mungkin sudah berhenti sendiri. Tidak masalah.
        }
    }

    memindai.value = false
    flashNyala.value = false
    punyaFlash.value = false
}

/* Ganti kamera berarti menyalakan ulang: qrs-scanner mengunci device yang
 * dipilih saat start(), dan setCamera() hanya bisa dipanggil dalam keadaan
 * sudah berhenti. */
async function gantiKamera() {
    if (!memindai.value) {
        await mulaiScan()

        return
    }

    berhenti()
    await mulaiScan()
}

async function toggleFlash() {
    if (!scanner) return

    try {
        if (flashNyala.value) {
            await scanner.turnFlashOff()
            flashNyala.value = false
        } else {
            await scanner.turnFlashOn()
            flashNyala.value = true
        }
    } catch (e) {
        flashNyala.value = false
        galat.value =
            e?.name === 'NotSupportedError'
                ? 'Perangkat ini tidak punya flash yang bisa dikendalikan.'
                : e?.message || 'Flash tidak bisa dinyalakan.'
    }
}

/* -------------------------------------------------------------------------
 *  SCAN DARI GAMBAR
 * ---------------------------------------------------------------------- */

async function pilihGambar(event) {
    const file = event.target.files?.[0]

    /* Kosongkan input lebih dulu, supaya memilih berkas yang sama dua kali
     * tetap memicu peristiwa change. Tanpa ini, kedua pilihan itu diabaikan
     * dan operator mengira tombolnya rusak. */
    event.target.value = ''

    if (!file) return

    galat.value = ''

    try {
        const QrScanner = await muatPustaka()

        const hasilScan = await QrScanner.scanImage(file, {
            returnDetailedScanResult: true,
        })

        terapkanHasil(hasilScan.data)
    } catch {
        terapkanHasil('')
        galat.value = 'Tidak ada QR code yang terbaca di gambar ini.'
    }
}

/* -------------------------------------------------------------------------
 *  DAFTAR KAMERA
 * ---------------------------------------------------------------------- */

onMounted(async () => {
    /*
     * Daftar kamera diambil begitu halaman dibuka, bukan setelah tombol
     * ditekan, supaya dropdown sudah terisi waktu operator memilih. Kalau
     * gagal, dropdown disembunyikan dan tombol Mulai Scan tetap bekerja:
     * pemindai akan menebak sendiri kamera yang layak dipakai.
     */
    try {
        const QrScanner = await muatPustaka()

        const daftar = await QrScanner.listCameras(true)

        if (daftar?.length) {
            const peta = {}

            daftar.forEach((kamera) => {
                peta[kamera.id] = kamera.label || `Kamera ${kamera.id}`
            })

            daftarKamera.value = peta
        }
    } catch {
        daftarKamera.value = {}
    }
})

onBeforeUnmount(() => {
    if (!scanner) return

    try {
        scanner.destroy()
    } catch {
        // Sudah tidak aktif. Tidak masalah.
    }

    scanner = null
})
</script>

<template>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Kepala -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">QR Scanner</h1>
            <p class="text-sm text-slate-500 mt-1">
                Scan QR code untuk mencari data booking atau armada
            </p>
        </div>

        <div class="pf-card overflow-hidden">
            <div class="p-4 sm:p-6">
                <!-- Area video -->
                <div
                    class="relative bg-slate-900 rounded-lg overflow-hidden"
                    style="min-height: 360px"
                >
                    <video
                        ref="video"
                        class="w-full h-full object-cover"
                        style="min-height: 360px"
                        playsinline
                        muted
                    ></video>

                    <!-- Tombol Mulai, hanya sebelum kamera menyala -->
                    <div
                        v-if="!memindai"
                        class="absolute inset-0 flex items-center justify-center"
                    >
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-white/90 hover:bg-white text-slate-900 rounded-xl font-semibold shadow-lg transition-all hover:scale-105"
                            :disabled="memuatPustaka"
                            @click="mulaiScan"
                        >
                            <span
                                class="material-symbols-outlined ms-icon"
                                :class="memuatPustaka ? 'animate-spin' : ''"
                            >
                                {{
                                    memuatPustaka
                                        ? 'progress_activity'
                                        : 'videocam'
                                }}
                            </span>
                            {{
                                memuatPustaka ? 'Menyiapkan...' : 'Mulai Scan'
                            }}
                        </button>
                    </div>

                    <!-- Kilau -->
                    <div
                        v-if="memindai && punyaFlash"
                        class="absolute top-3 right-3 flex gap-2"
                    >
                        <button
                            type="button"
                            class="p-2 bg-white/80 hover:bg-white rounded-lg text-slate-700 shadow-sm transition-colors text-sm"
                            @click="toggleFlash"
                        >
                            {{ flashNyala ? 'Flash Off' : 'Flash On' }}
                        </button>
                    </div>

                    <!-- Galat -->
                    <div
                        v-if="galat"
                        class="absolute inset-x-3 bottom-3 rounded-lg bg-rose-500/95 px-4 py-3 text-sm text-white shadow-lg"
                    >
                        {{ galat }}
                    </div>
                </div>

                <!-- Kontrol -->
                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button
                        v-if="memindai"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-lg font-medium transition-colors text-sm"
                        @click="berhenti"
                    >
                        <span
                            class="material-symbols-outlined ms-icon text-[18px]"
                        >
                            stop
                        </span>
                        Stop
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg font-medium transition-colors text-sm"
                        @click="inputGambar?.click()"
                    >
                        <span
                            class="material-symbols-outlined ms-icon text-[18px]"
                        >
                            image
                        </span>
                        Scan dari Gambar
                    </button>

                    <input
                        ref="inputGambar"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="pilihGambar"
                    />

                    <!-- Pilihan kamera -->
                    <select
                        v-if="Object.keys(daftarKamera).length"
                        v-model="kameraTerpilih"
                        class="ms-auto px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                        aria-label="Pilih kamera"
                        @change="gantiKamera"
                    >
                        <option value="">Kamera Default</option>
                        <option
                            v-for="(label, id) in daftarKamera"
                            :key="id"
                            :value="id"
                        >
                            {{ label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Hasil scan -->
            <div
                v-if="hasil || galat"
                class="border-t border-slate-200 p-4 sm:p-6"
            >
                <h2
                    class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2"
                >
                    Hasil Scan
                </h2>

                <!-- QR invoice: kartu hijau dengan tombol verifikasi -->
                <div
                    v-if="verifikasi"
                    class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 flex items-start gap-3"
                >
                    <span
                        class="material-symbols-outlined ms-icon text-emerald-600 shrink-0"
                    >
                        qr_code_2
                    </span>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-emerald-800">
                            QR Code Invoice terdeteksi
                        </p>
                        <p class="text-xs text-emerald-600 mt-0.5 break-all">
                            {{ verifikasi }}
                        </p>

                        <a
                            :href="verifikasi"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1.5 mt-3 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors"
                        >
                            <span
                                class="material-symbols-outlined ms-icon text-[16px]"
                            >
                                verified
                            </span>
                            Buka Halaman Verifikasi
                        </a>
                    </div>
                </div>

                <!-- Teks biasa -->
                <div v-else-if="hasil" class="bg-slate-50 rounded-lg p-4">
                    <p class="text-slate-900 font-mono text-sm break-all">
                        {{ hasil }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
