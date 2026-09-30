<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import { useConfirmStore } from '@/stores/confirm'
import { useUiStore } from '@/stores/ui'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  TOPBAR
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Berisi: tombol buka drawer (mobile), pencarian, menu akun.
 *
 *  ── SOAL KOLOM NOTIFIKASI ──────────────────────────────────────────────────
 *
 *  Di versi Blade ada dropdown notifikasi yang isinya data contoh keras:
 *  "Toyota Camry", "Invoice #1042", "Honda Odyssey" -- tidak pernah
 *  berubah, tidak pernah berasal dari database.
 *
 *  Kolom itu SENGAJA TIDAK dipindahkan ke sini.
 *
 *  Alasannya bukan sekadar "sudah tua". Notifikasi palsu di produksi
 *  berbahaya: operator melihat pengingat servis kendaraan fiktif dan mulai
 *  menelusuri, atau menganggap sistemnya sudah memberi tahu padahal tidak
 *  ada yang memantau. UI yang menampilkan data dengan benar lebih baik
 *  daripada UI yang menampilkan angka placeholder tanpa tanda bahwa itu
 *  placeholder.
 *
 *  Kalau notifikasi sungguhan dibutuhkan, itu fitur baru: butuh endpoint
 *  /api/notifications, tabelnya sendiri, dan aturan kapan sebuah notifikasi
 *  dibuat. Lihat docs/KNOWN_ISSUES.md.
 */

const auth = useAuthStore()
const confirm = useConfirmStore()
const ui = useUiStore()
const route = useRoute()
const router = useRouter()

const menuOpen = ref(false)
const search = ref('')

const inisial = computed(() => (auth.displayName || '?').slice(0, 1).toUpperCase())

/* Judul halaman aktif, untuk ditampilkan di layar kecil. */
const pageTitle = computed(() => route.meta.title || 'Precision Fleet')

async function onLogout() {
    menuOpen.value = false

    const ok = await confirm.ask('Keluar dari aplikasi?', {
        confirmLabel: 'Ya, keluar',
        cancelLabel: 'Batal',
    })

    if (!ok) return

    await auth.logout()
    router.push({ name: 'login' })
}

/* Tutup dropdown ketika klik di luar. */
const rootEl = ref(null)

function onDocumentClick(event) {
    if (menuOpen.value && rootEl.value && !rootEl.value.contains(event.target)) {
        menuOpen.value = false
    }
}

onMounted(() => document.addEventListener('click', onDocumentClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick))
</script>

<template>
    <header
        class="fixed top-0 right-0 w-full md:w-[calc(100%-var(--sidebar-lebar))] z-50 bg-white/80 backdrop-blur flex justify-between items-center h-16 px-4 md:px-8 border-b border-slate-200 transition-[left] duration-300"
        :style="{ left: ui.open ? '0' : undefined }"
    >
        <div class="flex items-center flex-1 max-w-xl min-w-0">
            <button
                type="button"
                class="text-slate-400 mr-2 md:mr-3 p-2 rounded-lg hover:bg-slate-100 transition-colors duration-200 md:hidden"
                aria-label="Buka menu"
                @click="ui.openDrawer()"
            >
                <span class="material-symbols-outlined">menu</span>
            </button>

            <!--
                Kolom pencarian.

                Belum punya backend: kolom ini masih murni visual, sama
                seperti di versi Blade. hooked up ke /api/... yang harus
                smelling dulu endpoint gabungan (armada + booking + pelanggan)
                dan deveria punya batas hasil supaya tidak mengembalikan
                ribuan baris.
            -->
            <div class="relative w-full min-w-0 hidden sm:block">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"
                >
                    search
                </span>
                <input
                    v-model="search"
                    class="w-full min-w-0 bg-slate-100/50 border border-slate-200 rounded-lg py-2 pl-10 pr-4 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all placeholder-slate-400"
                    placeholder="Cari kendaraan, pesanan, atau pelanggan..."
                    type="search"
                    aria-label="Pencarian"
                />
            </div>

            <!-- Judul hanya muncul di layar kecil, tempat tidak ada sidebar. -->
            <h1 class="sm:hidden font-bold text-slate-900 text-base truncate">{{ pageTitle }}</h1>
        </div>

        <!-- Menu akun -->
        <div ref="rootEl" class="relative shrink-0">
            <button
                type="button"
                class="flex items-center gap-2.5 pl-1.5 pr-2 py-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                :aria-expanded="menuOpen"
                aria-haspopup="menu"
                @click="menuOpen = !menuOpen"
            >
                <span
                    class="w-8 h-8 rounded-full bg-brand-600 text-white flex items-center justify-center text-xs font-bold shrink-0"
                >
                    {{ inisial }}
                </span>
                <span class="hidden md:flex flex-col items-start leading-tight min-w-0">
                    <span class="text-sm font-semibold text-slate-800 truncate max-w-[10rem]">
                        {{ auth.displayName }}
                    </span>
                    <span class="text-[11px] text-slate-500 truncate max-w-[10rem]">
                        {{ auth.roleLabel }}
                    </span>
                </span>
                <span class="material-symbols-outlined text-slate-400 text-[20px] hidden md:block">
                    expand_more
                </span>
            </button>

            <Transition
                enter-active-class="transition-all duration-150 ease-out"
                enter-from-class="opacity-0 scale-95 -translate-y-1"
                leave-active-class="transition-all duration-100 ease-in"
                leave-to-class="opacity-0 scale-95 -translate-y-1"
            >
                <div
                    v-if="menuOpen"
                    class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl border border-slate-200 shadow-xl py-1.5 z-50"
                    role="menu"
                >
                    <div class="px-4 py-2.5 border-b border-slate-100">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ auth.displayName }}</p>
                        <p class="text-[11px] text-slate-500 truncate">{{ auth.user?.email }}</p>
                    </div>

                    <RouterLink
                        :to="{ name: 'account' }"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                        role="menuitem"
                        @click="menuOpen = false"
                    >
                        <span class="material-symbols-outlined ms-icon text-[18px] text-slate-400">settings</span>
                        Akun Saya
                    </RouterLink>

                    <button
                        type="button"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 transition-colors"
                        role="menuitem"
                        @click="onLogout"
                    >
                        <span class="material-symbols-outlined ms-icon text-[18px]">logout</span>
                        Keluar
                    </button>
                </div>
            </Transition>
        </div>
    </header>
</template>