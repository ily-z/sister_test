<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

/*
 * ============================================================================
 *  KOLOM TANDATANGAN
 * ============================================================================
 *
 *  Kanvas tempat pengguna menandatangani dengan jari, mouse, atau pena.
 *
 *  Backend menerima data URL PNG (App\Support\SignatureImage::fromDataUrl)
 *  lalu memangkasnya sendiri supaya ruang kosong di sekeliling coretan hilang.
 *  Karena itu yang dikirim harus PNG transparan: JPEG tidak diterima, dan
 *  gambar berlatar putih membuat pemangkasan tidak bisa membedakan coretan
 *  dari latar.
 *
 *  Kanvas sengaja kecil (320x120 CSS px, dikalikan devicePixelRatio). Server
 *  menolak data URL di atas 1.600.000 byte; kanvas besar tidak menambah
 *  ketepatan tanda tangan, hanya ukuran file.
 *
 *  v-model berisi data URL, atau string kosong kalau belum ada coretan.
 */

const props = defineProps({
    modelValue: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    label: { type: String, default: 'Tanda tangan' },
})

const emit = defineEmits(['update:modelValue', 'change'])

const canvasEl = ref(null)
const adaIsian = ref(false)
const error = ref('')

let ctx = null
let sedangGambar = false
let titikTerakhir = null

/* Pointer Event menyatukan mouse, pena, dan sentuhan jadi satu jalur kode.
 * Kalau tidak tersedia, template jatuh ke handler mousedown/mousemove. */
const SUPPORTS_POINTER = typeof window !== 'undefined' && 'PointerEvent' in window

/* Tanpa pengalikan ini, coretan di layar high-DPI jadi kabur. Yang
 * dikalikan adalah backing store; ctx.scale yang menjembatani ke ukuran CSS. */
const dpr = SUPPORTS_POINTER ? window.devicePixelRatio || 1 : 1

function ukuranKanvas() {
    const el = canvasEl.value
    if (!el) return

    const lebar = el.clientWidth || 320
    const tinggi = el.clientHeight || 120

    el.width = Math.round(lebar * dpr)
    el.height = Math.round(tinggi * dpr)

    ctx = el.getContext('2d')
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0)
    ctx.lineWidth = 1.6
    ctx.lineCap = 'round'
    ctx.lineJoin = 'round'
    ctx.strokeStyle = '#0f172a'
}

function kosongkan() {
    const el = canvasEl.value
    if (!el || !ctx) return

    ctx.clearRect(0, 0, el.width, el.height)
    adaIsian.value = false
    error.value = ''

    emit('update:modelValue', '')
    emit('change', '')
}

function titikDariEvent(clientX, clientY) {
    const rect = canvasEl.value.getBoundingClientRect()

    return { x: clientX - rect.left, y: clientY - rect.top }
}

function mulaiGambar(event) {
    if (props.disabled) return

    event.preventDefault()

    sedangGambar = true
    titikTerakhir = titikDariEvent(event.clientX, event.clientY)
    error.value = ''
}

function lanjutGambar(event) {
    if (!sedangGambar || props.disabled) return

    event.preventDefault()

    const titik = titikDariEvent(event.clientX, event.clientY)

    /* Satu titik tidak menghasilkan apa-apa: moveTo lalu lineTo ke titik
     * yang sama hanya menggambar garis yang tidak terlihat. */
    ctx.beginPath()
    ctx.moveTo(titikTerakhir.x, titikTerakhir.y)
    ctx.lineTo(titik.x, titik.y)
    ctx.stroke()

    titikTerakhir = titik
    adaIsian.value = true
}

function selesaiGambar() {
    if (!sedangGambar) return

    sedangGambar = false
    titikTerakhir = null

    if (adaIsian.value) {
        const dataUrl = canvasEl.value.toDataURL('image/png')

        emit('update:modelValue', dataUrl)
        emit('change', dataUrl)
    }
}

/* Fallback untuk browser tanpa Pointer Event. Bentuk argumen dibuat sama
 * supaya fungsi di atas tidak perlu tahu jalur mana yang dipanggil. */
function mulaiMouse(event) {
    mulaiGambar({
        preventDefault: () => {},
        clientX: event.clientX,
        clientY: event.clientY,
    })
}

function lanjutMouse(event) {
    lanjutGambar({
        preventDefault: () => {},
        clientX: event.clientX,
        clientY: event.clientY,
    })
}

/* Safari membiarkan halaman ikut ter-scroll saat jari digerakkan di atas
 * kanvas. CSS touch-action sudah menutupnya; ini pengaman tambahan. */
function cegahScroll(event) {
    event.preventDefault()
}

function toggleKosong() {
    if (props.disabled || !adaIsian.value) return

    kosongkan()
}

onMounted(() => {
    ukuranKanvas()

    window.addEventListener('resize', ukuranKanvas)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', ukuranKanvas)
})
</script>

<template>
    <div>
        <div class="flex items-center justify-between gap-2 mb-1.5">
            <label class="form-label mb-0">{{ label }}</label>

            <button
                type="button"
                class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition-colors disabled:text-slate-300"
                :disabled="disabled || !adaIsian"
                @click="toggleKosong"
            >
                {{ adaIsian ? 'Hapus & ulang' : 'Kosongkan' }}
            </button>
        </div>

        <div class="relative">
            <canvas
                ref="canvasEl"
                class="w-full h-28 rounded-lg border-2 border-dashed border-slate-300 bg-white touch-none block"
                :class="{ 'opacity-60': disabled }"
                @pointerdown="mulaiGambar"
                @pointermove="lanjutGambar"
                @pointerup="selesaiGambar"
                @pointerleave="selesaiGambar"
                @mousedown="!SUPPORTS_POINTER && mulaiMouse($event)"
                @mousemove="!SUPPORTS_POINTER && lanjutMouse($event)"
                @mouseup="!SUPPORTS_POINTER && selesaiGambar()"
                @touchmove="!SUPPORTS_POINTER && cegahScroll($event)"
            />

            <p
                v-if="!adaIsian"
                class="absolute inset-0 flex items-center justify-center text-xs text-slate-400 pointer-events-none"
            >
                Coret di sini
            </p>
        </div>

        <p v-if="error" class="text-xs text-rose-600 mt-1">{{ error }}</p>

        <p class="text-[11px] text-slate-400 mt-1">
            Backend memangkas ruang kosong di sekitar coretan secara otomatis.
        </p>
    </div>
</template>