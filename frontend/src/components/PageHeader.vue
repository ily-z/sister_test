<script setup>
/*
 * ══════════════════════════════════════════════════════════════════════════
 *  JUDUL HALAMAN
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Judul + keterangan + tombol aksi. Dipakai di semua halaman.
 *
 *  Padding kiri-kanan dan lebar maksimum sengaja TIDAK diatur di sini, tapi
 *  diwrapping halaman. Alasannya: beberapa halaman (mis. daftar yang punya
 *  tabel lebar) butuh keluar dari batas lebar agar tidak terkepung di
 *  tengah. Kalau batasnya dipaksa di PageHeader, halaman yang tidak punya
 *  header tidak akan bisa melebar.
 */
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
})
</script>

<template>
    <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-4 mb-6">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-slate-900">{{ title }}</h1>
            <p v-if="description" class="text-sm text-slate-500 mt-1">{{ description }}</p>
            <slot name="subtitle" />
        </div>

        <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0">
            <slot name="actions" />
        </div>
    </div>
</template>