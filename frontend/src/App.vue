<script setup>
import ToastHost from '@/components/ToastHost.vue'
import ConfirmHost from '@/components/ConfirmHost.vue'
import NetworkBanner from '@/components/NetworkBanner.vue'
</script>

<template>
    <!--
        Hanya ada satu RouterView di sini.

        Halaman yang punya sidebar (dashboard, armada, bookings, ...) adalah
        route anak dari AppLayout, jadi route itulah yang dirender. Halaman
        tanpa sidebar (login, verifikasi invoice, error) adalah route
        tingkat atas dan dirender langsung.

        Sengaja TIDAK memakai :key="$route.fullPath". Dengan key, setiap
        perpindahan query string akan membuat ulang komponen dan
        menghilangkan filter yang sudah diisi pengguna. Perpindahan antar
        halaman tetap menukar komponen secara normal karena nama route-nya
        memang berbeda.

        Overlay global -- toast, dialog konfirmasi, dan pita peringatan
        jaringan -- diletakkan di sini, di luar RouterView, supaya tidak
        ikut di-destroy setiap kali halaman berganti.
    -->
    <RouterView />

    <NetworkBanner />
    <ToastHost />
    <ConfirmHost />
</template>