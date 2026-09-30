<script setup>
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()

/*
 * Pemetaan ikon per jenis notifikasi.
 *
 * Ikon Material Symbols yang dipakai seluruh aplikasi ada di sini supaya
 * konsisten. Kalau ditambah jenis baru, tambahkan juga ikonnya -- kalau
 * tidak, notifikasi akan muncul tanpa ikon dan terlihat seperti elemen
 * yang belum selesai dibuat.
 */
const IKON = {
    success: 'check_circle',
    error: 'error',
    warning: 'warning',
    info: 'info',
}

const WARNA = {
    success: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    error: 'bg-rose-50 text-rose-700 border-rose-200',
    warning: 'bg-amber-50 text-amber-800 border-amber-200',
    info: 'bg-brand-50 text-brand-700 border-brand-200',
}

const IKON_WARNA = {
    success: 'text-emerald-500',
    error: 'text-rose-500',
    warning: 'text-amber-500',
    info: 'text-brand-500',
}
</script>

<template>
    <!--
        Toast bisa muncul banyak sekaligus, jadi posisinya menumpuk ke bawah.

        `pointer-events-none` pada wadah, lalu dikembalikan pada tiap kartu:
        tanpa itu, area kosong di bawah notifikasi akan menutupi klik ke
        tombol di halaman -- dan gejalanya (tombol tidak merespons) sangat
        sulit dikaitkan dengan notifikasi.
    -->
    <div
        class="fixed top-20 right-4 z-[100] flex flex-col gap-2 pointer-events-none max-w-[calc(100vw-2rem)] sm:max-w-sm"
        role="status"
        aria-live="polite"
    >
        <TransitionGroup
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-x-6"
            leave-active-class="transition-all duration-200 ease-in absolute"
            leave-to-class="opacity-0 translate-x-6"
            move-class="transition-transform duration-200"
        >
            <div
                v-for="item in toast.items"
                :key="item.id"
                class="pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg backdrop-blur bg-white/95"
                :class="WARNA[item.type] || WARNA.info"
            >
                <span class="material-symbols-outlined ms-icon text-[20px] mt-px" :class="IKON_WARNA[item.type]">
                    {{ IKON[item.type] || IKON.info }}
                </span>

                <p class="text-sm font-medium leading-relaxed flex-1 min-w-0 break-words">
                    {{ item.message }}
                </p>

                <button
                    type="button"
                    class="shrink-0 opacity-50 hover:opacity-100 transition-opacity"
                    aria-label="Tutup notifikasi"
                    @click="toast.dismiss(item.id)"
                >
                    <span class="material-symbols-outlined ms-icon text-[18px]">close</span>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>