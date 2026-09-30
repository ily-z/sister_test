<script setup>
import { computed, reactive } from 'vue'
import { useRoute } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import { visibleNavigation } from '@/config/navigation'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  SIDEBAR
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Dua mode, sama seperti di Blade:
 *
 *    - LEBAR (16rem)   : teks dan sub-menu tampil.
 *    - SEMPIT (4.5rem) : hanya ikon. Teks dan sub-menu muncul kembali saat
 *                        kursor masuk ke area sidebar (hover-open).
 *
 *  Bedanya dengan versi Blade: mode disimpan sebagai state Vue di store,
 *  bukan ditoggle lewat classList.add() dari seven terpisah. Di Blade, tiga
 *  berkas harus menjaga class tetap sinkron; kalau tidak, lebar sidebar dan
 *  padding konten jadi berbeda sehingga konten tergeser ke samping.
 *
 *  Di layar kecil, sidebar menjadi drawer. Mode sempit tidak berlaku di
 *  sana: drawer selalu tampil penuh.
 */

const auth = useAuthStore()
const route = useRoute()

const props = defineProps({
    collapsed: { type: Boolean, default: false },
    open: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

const groups = computed(() => visibleNavigation(auth))

/*
 * Sub-menu yang dibuka manual.
 *
 * Default-nya semua tertutup, dan sub-menu terbuka otomatis hanya untuk grup
 * yang sedang berisi route aktif. Tanpa itu, pengguna yang mendarat langsung
 * di /armada/12 dari tautan atau tombol "kembali" peramban akan melihat
 * "Manajemen Armada" tertutup padahal isinya halaman armada.
 */
const openMenus = reactive({})

/*
 * Lebar sidebar ditulis sebagai SATU kelas, bukan dua kelas yang saling
 * meniadakan, misalnya w-64 bersama w-[4.5rem] sekaligus.
 *
 * Kalau keduanya ada sekaligus, yang menang ditentukan urutan di dalam
 * stylesheet Tailwind, bukan urutan di atribut class -- jadi perubahan kecil
 * pada konfigurasi Tailwind bisa membuat sidebar melebar atau sempit tanpa
 * ada error yang terlihat.
 */
const widthClass = computed(() => (props.collapsed ? 'w-[4.5rem]' : 'w-64'))

function isGroupActive(group) {
    return (group.activeNames || []).includes(route.name)
}

function isOpen(group) {
    if (isGroupActive(group)) return true
    return openMenus[group.key] === true
}

function toggleGroup(group) {
    openMenus[group.key] = !isOpen(group)
}

function isChildActive(child) {
    return (child.names || []).includes(route.name)
}

function linkClass(active, emphasized = false) {
    if (emphasized) {
        return active
            ? 'text-brand-700 bg-brand-50 border-brand-200'
            : 'text-slate-700 bg-white border border-slate-300 hover:bg-slate-50'
    }

    return active
        ? 'text-brand-700 bg-brand-50'
        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
}

/* Tutup drawer setiap kali pengguna memilih menu di layar kecil. */
function onNavigate() {
    if (window.innerWidth < 768) emit('close')
}
</script>

<template>
    <aside
        class="bg-white h-screen fixed left-0 top-0 border-r border-slate-200 flex flex-col z-[45] hidden md:flex -translate-x-full md:translate-x-0 pb-16 md:pb-0 transition-[width,transform] duration-300 ease-in-out"
        :class="[
            widthClass,
            props.open ? '!translate-x-0 shadow-2xl' : '',
            props.collapsed ? 'sidebar-collapsed' : '',
        ]"
        style="overflow-y: auto; overflow-x: hidden; scrollbar-gutter: stable;"
        aria-label="Navigasi utama"
    >
        <!-- Brand -->
        <div class="p-6 pt-16 md:pt-6 flex items-center justify-between gap-3 w-full sidebar-brand-row">
            <RouterLink
                :to="{ name: 'dashboard' }"
                id="sidebarBrand"
                class="flex items-center gap-3 min-w-0"
                @click="onNavigate"
            >
                <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-white text-sm">directions_car</span>
                </div>
                <div class="sidebar-text overflow-hidden whitespace-nowrap">
                    <h1 class="text-slate-900 font-bold tracking-tight text-lg leading-none">
                        Precision Fleet
                    </h1>
                    <p class="text-[10px] text-slate-500 font-semibold tracking-wider uppercase mt-1">
                        Enterprise ERP
                    </p>
                </div>
            </RouterLink>
        </div>

        <nav class="mt-4 flex-grow px-3 space-y-1">
            <template v-for="group in groups" :key="group.key">
                <div v-if="group.separator" class="pt-4 pb-2 border-t border-slate-100 mt-2" />

                <!-- Grup tanpa sub-menu -->
                <RouterLink
                    v-else-if="!group.children"
                    :to="group.to"
                    class="sidebar-link flex items-center px-4 py-2.5 rounded-lg font-medium transition-all duration-200"
                    :class="linkClass(isGroupActive(group), group.emphasized)"
                    :title="props.collapsed ? group.label : null"
                    @click="onNavigate"
                >
                    <span class="material-symbols-outlined mr-3 text-[20px] shrink-0">
                        {{ group.icon }}
                    </span>
                    <span class="sidebar-text text-sm whitespace-nowrap">{{ group.label }}</span>
                </RouterLink>

                <!-- Grup dengan sub-menu -->
                <div v-else class="relative">
                    <div
                        class="sidebar-link w-full flex items-center justify-between rounded-lg font-medium transition-all duration-200"
                        :class="linkClass(isGroupActive(group))"
                    >
                        <RouterLink
                            :to="group.to"
                            class="flex items-center min-w-0 flex-1 pl-4 py-2.5"
                            :title="props.collapsed ? group.label : null"
                            @click="onNavigate"
                        >
                            <span class="material-symbols-outlined mr-3 text-[20px] shrink-0">
                                {{ group.icon }}
                            </span>
                            <span class="sidebar-text text-sm whitespace-nowrap">{{ group.label }}</span>
                        </RouterLink>

                        <button
                            type="button"
                            class="sidebar-text flex items-center pr-4 py-2.5 shrink-0"
                            :aria-expanded="isOpen(group)"
                            :aria-label="(isOpen(group) ? 'Tutup' : 'Buka') + ' submenu ' + group.label"
                            @click="toggleGroup(group)"
                        >
                            <span
                                class="material-symbols-outlined text-[20px] transition-transform duration-200"
                                :class="isOpen(group) ? 'rotate-180' : ''"
                            >
                                expand_more
                            </span>
                        </button>
                    </div>

                    <div
                        class="sidebar-submenu flex flex-col gap-1 mt-1 overflow-hidden transition-all duration-300"
                        :class="isOpen(group) ? 'flex' : 'hidden'"
                    >
                        <RouterLink
                            v-for="child in group.children"
                            :key="child.label"
                            :to="child.to"
                            class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                            :class="
                                isChildActive(child)
                                    ? 'text-brand-700 bg-brand-50'
                                    : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'
                            "
                            @click="onNavigate"
                        >
                            {{ child.label }}
                        </RouterLink>
                    </div>
                </div>
            </template>
        </nav>

        <!-- CTA buat pesanan baru -->
        <div v-if="auth.can('bookings.create')" class="px-3 mb-4 mt-auto">
            <RouterLink
                :to="{ name: 'bookings.create' }"
                class="sidebar-link w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 rounded-lg flex items-center justify-center transition-colors"
                @click="onNavigate"
            >
                <span class="material-symbols-outlined mr-2 text-[20px] shrink-0">add</span>
                <span class="sidebar-text text-sm whitespace-nowrap">Pesanan Baru</span>
            </RouterLink>
        </div>

        <!-- Akun -->
        <div class="px-3 pb-6 space-y-1">
            <RouterLink
                :to="{ name: 'account' }"
                class="sidebar-link flex items-center px-4 py-2.5 rounded-lg font-medium transition-all duration-200"
                :class="
                    route.name === 'account'
                        ? 'text-brand-700 bg-brand-50'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                "
                @click="onNavigate"
            >
                <span class="material-symbols-outlined mr-3 text-[20px] shrink-0">settings</span>
                <span class="sidebar-text text-sm whitespace-nowrap">Akun Saya</span>
            </RouterLink>
        </div>
    </aside>

    <!--
        Lapisan gelap di belakang drawer mobile.

        Hanya dirender saat drawer terbuka. Kalau elemen setinggi layar penuh
        tetap ada di DOM dengan opacity-0, dia akan tetap menangkap klik --
        gejalanya tombol di halaman tidak merespons, dan penyebabnya sangat
        sulit ditemukan.
    -->
    <div
        v-if="props.open"
        class="fixed inset-0 z-[44] bg-slate-900/40 backdrop-blur-sm md:hidden"
        @click="emit('close')"
    />
</template>