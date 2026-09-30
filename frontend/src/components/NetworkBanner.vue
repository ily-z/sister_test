<script setup>
import { ref } from 'vue'

import { onNetworkError, networkDiagnostics } from '@/lib/api'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  PITA PERINGATAN JARINGAN
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Ada satu kelas kegagalan yang tidak terbaca dari response HTTP mana pun:
 *  request-nya tidak pernah sampai.
 *
 *  Di setup ini penyebabnya ada beberapa, dan gejalanya dari sisi pengguna
 *  hampir sama -- spinner berhenti, atau muncul "Network Error":
 *
 *    1. Backend mati, atau belum selesai start.
 *    2. IP atau port backend salah di VITE_API_BASE_URL, atau blok proxy di
 *       nginx server frontend belum diatur benar.
 *    3. Firewall membuang paket, sehingga koneksi menggantung sampai timeout.
 *    4. CORS: browser memblokir response karena header CORS tidak ada.
 *       Ini yang paling sering terjadi saat berpindah dari mode A ke mode B.
 *
 *  Tanpa pita ini, semuanya tampil sebagai "Network Error" dan operator
 *  akan melaporkan "internet bermasalah" padahal masalahnya konfigurasi
 *  server. Detail teknisnya ditampilkan agar orang yang sedang menangani
 *  masalah tahu harus mengecek ke mana, tanpa perlu membuka DevTools.
 */

const lastError = ref(null)

onNetworkError((detail) => {
    lastError.value = detail
})

/*
 * Tombol tutup hanya menyembunyikan pita, bukan menyelesaikan masalahnya.
 *
 * Sengaja tidak ada tombol "coba lagi" yang memanggil API: halaman-halaman
 * di aplikasi ini sudah memuat ulang datanya sendiri saat berpindah route.
 * Memaksa seluruh SPA untuk mount ulang akan membuang filter dan posisi
 * scroll yang sedang dipakai pengguna, hanya untuk satu request.
 */

function ringkasan() {
    const e = lastError.value
    if (!e) return ''

    if (e.code === 'ECONNABORTED') {
        return 'Server tidak menjawab dalam 30 detik.'
    }

    if (e.message === 'Network Error') {
        return (
            'Tidak ada respons dari server. Periksa apakah backend masih hidup, ' +
            'dan apakah alamat API pada konfigurasi build sudah benar.'
        )
    }

    return e.message
}

function waktuLocale(iso) {
    try {
        return new Date(iso).toLocaleString('id-ID')
    } catch {
        return iso
    }
}
</script>

<template>
    <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="-translate-y-full"
        leave-active-class="transition-transform duration-200 ease-in"
        leave-to-class="-translate-y-full"
    >
        <div
            v-if="lastError"
            class="fixed top-0 inset-x-0 z-[120] bg-rose-600 text-white shadow-lg"
            role="alert"
        >
            <div class="max-w-[1400px] mx-auto px-4 md:px-8 py-3 flex items-start gap-3">
                <span class="material-symbols-outlined ms-icon text-[20px] mt-px shrink-0">
                    cloud_off
                </span>

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold leading-snug">
                        Koneksi ke backend terputus
                    </p>
                    <p class="text-[13px] leading-relaxed text-rose-50 opacity-95 mt-0.5 break-words">
                        {{ ringkasan() }}
                    </p>

                    <!--
                        Detail teknis disembunyikan di balik <details>.
                        Operator harian tidak membutuhkannya, tapi orang yang
                        sedang menangani masalah membutuhkannya.
                    -->
                    <details class="mt-1.5">
                        <summary
                            class="text-[12px] cursor-pointer select-none text-rose-50/80 hover:text-white transition-colors"
                        >
                            Detail teknis
                        </summary>
                        <dl
                            class="mt-2 grid grid-cols-[auto,1fr] gap-x-3 gap-y-1 text-[12px] font-mono text-rose-50/90"
                        >
                            <dt class="opacity-70">url</dt>
                            <dd class="break-all">{{ networkDiagnostics.last.url }}</dd>
                            <dt class="opacity-70">kode</dt>
                            <dd>{{ networkDiagnostics.last.code }}</dd>
                            <dt class="opacity-70">waktu</dt>
                            <dd>{{ waktuLocale(networkDiagnostics.last.at) }}</dd>
                        </dl>
                    </details>
                </div>

                <button
                    type="button"
                    class="shrink-0 p-1 rounded hover:bg-rose-700 transition-colors"
                    aria-label="Tutup peringatan"
                    @click="lastError = null"
                >
                    <span class="material-symbols-outlined ms-icon text-[18px]">close</span>
                </button>
            </div>
        </div>
    </Transition>
</template>