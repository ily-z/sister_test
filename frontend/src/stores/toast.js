import { defineStore } from 'pinia'

let nextId = 1

/**
 * ══════════════════════════════════════════════════════════════════════════
 *  NOTIFIKASI SINGKAT
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Pengganti dari showGlobalToast() yang dulu dipanggil dari Blade lewat
 *  session flash.
 *
 *  Perbedaan yang penting: di Blade, pesan muncul hanya pada request
 *  berikutnya karena flash session sudah habis setelah dibaca. Di SPA,
 *  tidak ada navigasi halaman, jadi notifikasi harus dibuat eksplisit oleh
 *  pemanggil setiap kali ada yang terjadi. Tidak ada pesan yang muncul
 *  "dengan sendirinya".
 */
export const useToastStore = defineStore('toast', {
    state: () => ({
        items: [],
    }),

    actions: {
        /**
         * @param {string} message
         * @param {'success'|'error'|'info'|'warning'} type
         */
        push(message, type = 'success', timeout = 4000) {
            if (!message) return

            const id = nextId++

            this.items.push({ id, message, type })

            if (timeout > 0) {
                window.setTimeout(() => this.dismiss(id), timeout)
            }
        },

        success(message, timeout) {
            this.push(message, 'success', timeout)
        },

        error(message, timeout = 6000) {
            this.push(message, 'error', timeout)
        },

        info(message, timeout) {
            this.push(message, 'info', timeout)
        },

        warning(message, timeout) {
            this.push(message, 'warning', timeout)
        },

        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id)
        },

        clear() {
            this.items = []
        },
    },
})