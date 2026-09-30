<script setup>
import { computed } from 'vue'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  BADGE STATUS
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Warna badge ditentukan dari teks status, bukan dari kelas yang ditulis di
 *  pemanggil. Alasannya: status pembayaran dan status rental muncul di
 *  sidebar, daftar, detail, invoice, dan surat. Kalau warna ditentukan di tiap
 *  pemanggil, pasti akan ada satu tempat yang berbeda warna, dan pengguna
 *  akan membaca "Lunas" dengan warna merah sebagai tanda ada masalah.
 *
 *  Peta warna di bawah PENTING koleksi dengan warna yang dipakai Blade.
 *  Melihat merah sebagai "kondisi baik" adalah cara tercepat membuat operator
 *  ragu terhadap semua angka di aplikasi.
 */

const props = defineProps({
    label: { type: String, required: true },
    size: { type: String, default: 'sm' },
})

/*
 * Pencocokan dilakukan tanpa membedakan huruf besar-kecil dan spasi, karena
 * data dari database tidak konsisten: "Lunas", "lunas", dan " LUNAS" adalah
 * hal yang berbeda di SQL dan hal yang sama bagi pengguna.
 */
const key = computed(() => props.label.trim().toLowerCase())

const WARNA = {
    /* Pembayaran */
    lunas: 'bg-emerald-50 text-emerald-600',
    dp: 'bg-amber-50 text-amber-600',
    'belum bayar': 'bg-rose-50 text-rose-600',
    'belum lunas': 'bg-orange-50 text-orange-600',
    'refund pending': 'bg-purple-50 text-purple-600',
    refunded: 'bg-purple-50 text-purple-600',

    /* Rental */
    aktif: 'bg-emerald-50 text-emerald-600',
    menunggu: 'bg-slate-100 text-slate-600',
    selesai: 'bg-brand-50 text-brand-600',
    batal: 'bg-rose-50 text-rose-600',
    dikembalikan: 'bg-slate-100 text-slate-600',

    /* Kendaraan */
    tersedia: 'bg-emerald-50 text-emerald-600',
    'sedang disewa': 'bg-brand-50 text-brand-600',
    'masuk bengkel': 'bg-orange-50 text-orange-600',
    maintenance: 'bg-amber-50 text-amber-600',
    rusak: 'bg-rose-50 text-rose-600',

    /* Penting */
    aktif_lokal: 'bg-emerald-50 text-emerald-600',
    nonaktif: 'bg-slate-100 text-slate-500',
    'tidak aktif': 'bg-slate-100 text-slate-500',

    /* Surat */
    draft: 'bg-slate-100 text-slate-600',
    terbit: 'bg-emerald-50 text-emerald-600',
    pending: 'bg-amber-50 text-amber-600',
}

const classes = computed(() => WARNA[key.value] || 'bg-slate-100 text-slate-600')

const ukuran = computed(() =>
    props.size === 'md'
        ? 'px-2.5 py-1 text-xs'
        : 'px-2 py-1 text-[10px]'
)
</script>

<template>
    <span
        class="inline-flex items-center rounded-md font-bold uppercase whitespace-nowrap"
        :class="[classes, ukuran]"
    >
        {{ label }}
    </span>
</template>