<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  NAVIGASI BAWAH (MOBILE)
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Empat item dengan tombol tambah di tengah. Hanya untuk layar kecil;
 *  di desktop sidebar sudah menampilkan semuanya.
 *
 *  Item di sini sengaja dibuat sedikit. Empat item per baris di layar 360px
 *  memberi setiap item sekitar 90px; menambah satu item lagi membuat label
 *  seperti "Penyewaan" tidak muat. Menu yang lengkap lebih baik dibuka dari
 *  sidebar.
 */

const auth = useAuthStore()
const route = useRoute()

/*
 * `permission` di sini disembunyikan dengan v-if. Bedanya dengan menu
 * desktop: di mobile, pengguna tidak bisa melihat bahwa akses ditolak --
 * jadi permission yang tidak terpenuhi harus benar-benar tidak dirender.
 */
const items = computed(() =>
    [
        { label: 'Home', icon: 'grid_view', to: { name: 'dashboard' }, names: ['dashboard'] },
        { label: 'Sewa', icon: 'event_note', to: { name: 'bookings.index' }, names: ['bookings.index', 'bookings.show', 'bookings.edit', 'bookings.create', 'bookings.calendar'] },
        {
            label: 'Armada',
            icon: 'directions_car',
            to: { name: 'armada.index' },
            names: ['armada.index', 'armada.show', 'armada.edit', 'armada.create'],
            permission: 'fleet.view',
        },
        {
            label: 'Akun',
            icon: 'person',
            to: { name: 'account' },
            names: ['account'],
        },
    ].filter((item) => !item.permission || auth.can(item.permission))
)

const showCreate = computed(() => auth.can('bookings.create'))

function isActive(names) {
    return names.includes(route.name)
}
</script>

<template>
    <nav
        class="fixed bottom-0 inset-x-0 z-50 bg-white border-t border-slate-200 flex md:hidden items-end h-16 px-2 pb-[env(safe-area-inset-bottom)]"
        aria-label="Navigasi cepat"
    >
        <RouterLink
            v-for="item in items.slice(0, 2)"
            :key="item.label"
            :to="item.to"
            class="flex flex-col items-center justify-center flex-1 py-2 transition-colors"
            :class="isActive(item.names) ? 'text-brand-600' : 'text-slate-400 hover:text-slate-600'"
        >
            <span class="material-symbols-outlined text-[22px]">{{ item.icon }}</span>
            <span class="text-[10px] font-medium mt-0.5">{{ item.label }}</span>
        </RouterLink>

        <!--
            Tombol tambah di tengah.

            Ruang kosongnya berasal dari `spacer` -- bukan dari item yang
            disembunyikan, karena kalau tombol plus hilang, dua item sisa
            akan bergeser ke tengah dan posisinya meleset.
        -->
        <div v-if="showCreate" class="flex-1" />

        <div v-if="showCreate" class="flex-1 flex items-start justify-center">
            <RouterLink
                :to="{ name: 'bookings.create' }"
                class="bg-brand-600 text-white rounded-full p-3 -mb-6 shadow-lg active:scale-95 transition-transform flex items-center justify-center"
                aria-label="Buat pesanan baru"
            >
                <span class="material-symbols-outlined text-[24px]">add</span>
            </RouterLink>
        </div>
        <div v-else class="flex-1" />

        <RouterLink
            v-for="item in items.slice(2)"
            :key="item.label"
            :to="item.to"
            class="flex flex-col items-center justify-center flex-1 py-2 transition-colors"
            :class="isActive(item.names) ? 'text-brand-600' : 'text-slate-400 hover:text-slate-600'"
        >
            <span class="material-symbols-outlined text-[22px]">{{ item.icon }}</span>
            <span class="text-[10px] font-medium mt-0.5">{{ item.label }}</span>
        </RouterLink>
    </nav>
</template>