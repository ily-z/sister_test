<script setup>
import { useRoute } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

/*
 * Halaman 403.
 *
 * Muncul ketika pengguna sudah masuk tapi tidak punya permission untuk
 * halaman yang dituju. Dua penyebab yang paling sering:
 *
 *   1. Menu masih terlihat karena daftar permission di frontend belum
 *      diperbarui, sementara backend sudah menolak.
 *   2. Pengguna tadi membuka tautan yang tidak lagi diizinkan untuknya
 *      -- misalnya setelah role-nya diubah oleh administrator.
 *
 * Karena itu halaman ini menampilkan permission yang memang dimiliki,
 * bukan hanya permintaan. Tanpa itu, orang tidak punya cara untuk mencari
 * jalan keluar selain mencoba-tebak URL lain.
 */

const route = useRoute()
const auth = useAuthStore()

const permissionDiminta = route.meta.permission || null
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-xl shadow-lg border border-slate-200 p-8 text-center">
            <div
                class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-5"
            >
                <span class="material-symbols-outlined text-[28px]">lock</span>
            </div>

            <h1 class="text-xl font-bold text-slate-800">Akses Ditolak</h1>

            <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                Akun Anda ({{ auth.displayName }}) tidak punya izin untuk membuka
                halaman ini.
            </p>

            <div
                v-if="permissionDiminta"
                class="mt-4 py-3 px-4 rounded-lg bg-slate-50 border border-slate-100 text-left"
            >
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">
                    Izin yang dibutuhkan
                </p>
                <p class="text-sm font-mono text-slate-700 mt-1">{{ permissionDiminta }}</p>
            </div>

            <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                Hubungi administrator bila Anda merasa ini keliru. Izin dapat diubah
                dari halaman Permission &amp; Role.
            </p>

            <RouterLink :to="{ name: 'dashboard' }" class="btn-primary w-full mt-6">
                <span class="material-symbols-outlined ms-icon text-[18px]">home</span>
                Kembali ke Dashboard
            </RouterLink>
        </div>
    </div>
</template>