<script setup>
import { computed, onMounted, onBeforeUnmount } from 'vue'

import Sidebar from '@/components/Sidebar.vue'
import Topbar from '@/components/Topbar.vue'
import BottomNav from '@/components/BottomNav.vue'

import { useUiStore } from '@/stores/ui'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  KERANGKA APLIKASI
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Sidebar + topbar + navigasi bawah + area konten.
 *
 *  ── SOAL LEBAR SIDEBAR ─────────────────────────────────────────────────────
 *
 *  Lebar sidebar (16rem saat lebar, 4.5rem saat sempit) diteruskan ke
 *  topbar dan ke area konten lewat CSS custom property --sidebar-lebar,
 *  bukan lewat kelas yang ditulis manual di masing-masing tempat.
 *
 *  Di Blade, lebar ini ditulis tiga kali: di CSS sidebar, di kelas topbar
 *  (md:w-[calc(100%-16rem)]) dan di padding konten (md:pl-64). Kalau satu
 *  lupa diubah, sidebar menutupi sebagian konten atau konten menyempit
 *  sendiri. Dengan satu variabel, tidak ada tempat untuk lupa.
 */

const ui = useUiStore()

const sidebarWidth = computed(() => (ui.collapsed ? '4.5rem' : '16rem'))

/*
 * Hover-open untuk sidebar sempit.
 *
 * Dipantau di level window, bukan hanya di sidebar, supaya sidebar ikut
 * terbuka ketika kursor masuk dari luar dan melewati areanya. Dipantau di
 * element sidebar saja, kursor yang masuk dari sisi sebaliknya tidak akan
 * memicu apa pun.
 */
function onMouseMove(event) {
    if (window.innerWidth < 768) return

    const diDalamSidebar = event.clientX <= 90

    if (diDalamSidebar && ui.collapsed) {
        ui.setHoverOpen(true)
    } else if (!diDalamSidebar) {
        ui.setHoverOpen(false)
    }
}

onMounted(() => window.addEventListener('mousemove', onMouseMove))
onBeforeUnmount(() => window.removeEventListener('mousemove', onMouseMove))
</script>

<template>
    <div
        class="min-h-screen bg-slate-50"
        :style="{ '--sidebar-lebar': sidebarWidth }"
    >
        <Sidebar
            :collapsed="ui.collapsed"
            :open="ui.open"
            :class="{ 'sidebar-hover-open': ui.hoverOpen }"
            @close="ui.closeDrawer()"
        />

        <Topbar />

        <!--
            Padding konten memakai variabel yang sama, bukan md:pl-64.

            Angka lebar sidebar muncul tepat sekali, di style di atas. Kalau
            nanti diubah, hanya style itu yang perlu ikut diubah.
        -->
        <main
            class="pt-16 pb-20 md:pb-8 transition-[padding] duration-300"
            :class="ui.open ? '' : 'md:pl-[var(--sidebar-lebar)]'"
        >
            <RouterView />
        </main>

        <BottomNav />
    </div>
</template>