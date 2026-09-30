<script setup>
import { ref, watch } from 'vue'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  FOTO KENDARAAN
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Menampilkan foto kendaraan dengan tiga kondisi yang harus ditangani:
 *
 *    1. Ada foto        -> ditampilkan.
 *    2. Tidak ada foto  -> ikon placeholder.
 *    3. Gagal dimuat    -> placeholder.
 *
 *  Kasus 3 PENTING dan sering terlewat. `foto_url` adalah signed URL yang
 *  berlaku 10 menit (FILE_URL_TTL). Kalau halaman ini dibuka lebih dari 10
 *  menit tanpa reload -- dan dashboard biasanya dibiarkan terbuka seharian
 *  -- semua foto akan berhenti dimuat. Tanpa penanganan, hasilnya kotak rusak
 *  di setiap kartu, dan pengguna mengira foto kendaraannya hilang.
 *
 *  Foto juga tidak pernah diambil langsung dari /storage. Semua file lewat
 *  /api/files/{path} dengan signature, jadi URL-nya tidak bisa dipakai di luar
 *  sesi login. Lihat App\Support\FileUrl.
 */

const props = defineProps({
    url: { type: String, default: '' },
    alt: { type: String, default: '' },
    icon: { type: String, default: 'directions_car' },
})

const gagal = ref(false)

/*
 * Reset status gagal ketika URL berganti.
 *
 * Tanpa ini, foto berikutnya yang lebih baru akan langsung dianggap gagal
 * begitu URL-nya berubah -- komponen akan langsung menampilkan placeholder
 * tanpa pernah mencoba memuat foto yang sebenarnya ada.
 */
watch(() => props.url, () => { gagal.value = false })
</script>

<template>
    <img
        v-if="props.url && !gagal"
        :src="props.url"
        :alt="props.alt"
        loading="lazy"
        decoding="async"
        class="h-full w-full object-cover"
        @error="gagal = true"
    />

    <div
        v-else
        class="h-full w-full flex items-center justify-center bg-slate-100"
        :aria-label="gagal ? 'Foto tidak dapat dimuat' : 'Tidak ada foto'"
    >
        <span
            class="material-symbols-outlined text-slate-300"
            :class="gagal ? 'text-[22px]' : 'text-[32px]'"
        >
            {{ gagal ? 'broken_image' : props.icon }}
        </span>
    </div>
</template>