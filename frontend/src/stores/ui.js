import { defineStore } from 'pinia'

const COLLAPSED_KEY = 'pf.sidebar.collapsed'

function readCollapsed() {
    try {
        return window.localStorage.getItem(COLLAPSED_KEY) === '1'
    } catch {
        return false
    }
}

function writeCollapsed(value) {
    try {
        window.localStorage.setItem(COLLAPSED_KEY, value ? '1' : '0')
    } catch {
        // Diabaikan dengan sengaja.
    }
}

/**
 * ══════════════════════════════════════════════════════════════════════════
 *  STATE TAMPILAN
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Berisi hal-hal yang bukan data bisnis, tapi tetap dipakai lebih dari satu
 *  komponen:
 *
 *    - collapsed: sidebar sempit atau lebar (desktop)
 *    - open:      drawer sidebar (mobile)
 *    - hoverOpen: sidebar sedang dilewati kursor (desktop, mode sempit)
 *
 *  Ditempatkan di store, bukan di AppLayout, karena Topbar punya tombol
 *  untuk sempitkan sidebar dan Sidebar punya tombol buka-tutup drawer. Kalau
 *  tidak, state-nya harus dioper sebagai props, AppLayout menjadi tempat
 *  pembaruan state, dan mudah sekali terbentuk lingkaran pembaruan.
 *
 *  Preferensi "collapsed" disimpan di localStorage karena merupakan pilihan
 *  pengguna, bukan kondisi sementara. Drawer dan hover tidak disimpan --
 *  keduanya selalu kembali ke kondisi awal setelah reload, dan itu memang
 *  yang diinginkan.
 */
export const useUiStore = defineStore('ui', {
    state: () => ({
        collapsed: readCollapsed(),
        open: false,
        hoverOpen: false,
    }),

    actions: {
        toggleCollapsed() {
            this.collapsed = !this.collapsed
            writeCollapsed(this.collapsed)
        },

        openDrawer() {
            this.open = true
        },

        closeDrawer() {
            this.open = false
        },

        toggleDrawer() {
            this.open = !this.open
        },

        setHoverOpen(value) {
            this.hoverOpen = value
        },
    },
})