<script setup>
import { useRoute, useRouter } from 'vue-router'

/*
 * Halaman 404.
 *
 * Di SPA, halaman ini muncul dari dua kondisi yang sangat berbeda:
 *
 *   1. URL-nya memang salah (tautan lama, ketik salah).
 *   2. Datanya sudah dihapus, dan sebelumnya pengguna menekan tombol "kembali"
 *      peramban ke halaman detail yang sudah tidak ada.
 *
 * Untuk (2), selalu tunjukkan ID yang diminta kalau ada. "Pesanan #42 tidak
 * ditemukan" jauh lebih berguna daripada "halaman tidak ditemukan" -- orang
 * tahu barang yang hilang, dan bisa memberitahukan ke rekannya.
 */

const route = useRoute()
const router = useRouter()

const detailPesanan = /^\/bookings\/(\d+)/.exec(route.path)
const detailArmada = /^\/armada\/(\d+)/.exec(route.path)

const konteks = detailPesanan
    ? { jenis: 'pesanan', id: detailPesanan[1] }
    : detailArmada
      ? { jenis: 'kendaraan', id: detailArmada[1] }
      : null
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-xl shadow-lg border border-slate-200 p-8 text-center">
            <div
                class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-5"
            >
                <span class="material-symbols-outlined text-[28px]">search_off</span>
            </div>

            <h1 class="text-xl font-bold text-slate-800">Halaman tidak ditemukan</h1>

            <p
                v-if="konteks"
                class="text-sm text-slate-500 mt-2 leading-relaxed"
            >
                {{ konteks.jenis === 'pesanan' ? 'Pesanan' : 'Kendaraan' }}
                dengan nomor {{ konteks.id }} tidak ditemukan. Datanya mungkin sudah
                dihapus.
            </p>

            <p v-else class="text-sm text-slate-500 mt-2 leading-relaxed">
                Alamat yang Anda buka tidak dikenali oleh aplikasi.
            </p>

            <p class="mt-3 py-2 px-3 rounded-md bg-slate-50 text-xs font-mono text-slate-400 break-all">
                {{ route.fullPath }}
            </p>

            <div class="flex gap-2 mt-6">
                <button type="button" class="btn-secondary flex-1" @click="router.back()">
                    <span class="material-symbols-outlined ms-icon text-[18px]">arrow_back</span>
                    Kembali
                </button>

                <RouterLink :to="{ name: 'dashboard' }" class="btn-primary flex-1">
                    Dashboard
                </RouterLink>
            </div>
        </div>
    </div>
</template>