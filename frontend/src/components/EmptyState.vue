<script setup>
/*
 * ══════════════════════════════════════════════════════════════════════════
 *  KONDISI KOSONG
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Ditampilkan saat tidak ada data, dengan alasan yang menjelaskan kenapa.
 *
 *  Bagian importance: property `description` bukan hiasan. "Belum ada data"
 *  tanpa penjelasan membuat pengguna mengira sistemnya rusak. Menyatakan
 *  apa yang perlu dilakukan (coba ubah filter, atau buat yang baru) mengubah
 *  pengalaman dari "ada yang salah" menjadi "saya harus melakukan ini".
 */
defineProps({
    icon: { type: String, default: 'inbox' },
    title: { type: String, default: 'Belum ada data' },
    description: { type: String, default: '' },
})
</script>

<template>
    <div class="text-center py-14 px-6">
        <div
            class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4"
        >
            <span class="material-symbols-outlined text-[26px]">{{ icon }}</span>
        </div>

        <h3 class="text-base font-semibold text-slate-700">{{ title }}</h3>

        <p v-if="description" class="text-sm text-slate-500 mt-1.5 max-w-sm mx-auto leading-relaxed">
            {{ description }}
        </p>

        <div v-if="$slots.default" class="mt-5">
            <slot />
        </div>
    </div>
</template>