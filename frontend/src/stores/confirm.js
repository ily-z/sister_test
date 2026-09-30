import { defineStore } from 'pinia'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  DIALOG KONFIRMASI
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Dialog konfirmasi ini menggantikan window.confirm() bawaan peramban.
 *
 *  Alasannya: window.confirm() memblokir thread JavaScript, sementara
 *  pemanggilnya di sini adalah handler async yang sedang menunggu respons
 *  HTTP. Selama dialog native tampil, response yang sedang ditunggu itu
 *  tidak akan diproses -- dan di browser modern perintahnya sering diabaikan
 *  sama sekali.
 *
 *  Callback pending disimpan di luar state Pinia (resolveFn), bukan di
 *  dalam state. Kalau disimpan di state, objek Promise akan ikut masuk ke
 *  reactive proxy Vue, dan setiap akses ke dialog akan menjadi jauh lebih
 *  lambat dari yang perlu. Untuk satu dialog selisihnya tidak terasa,
 *  tapi jalur ini dieksekusi setiap kali operator menekan tombol.
 *
 *  Pemakaian:
 *
 *      import { useConfirmStore } from '@/stores/confirm'
 *
 *      const confirm = useConfirmStore()
 *      if (!(await confirm.ask('Hapus pesanan ini?', { tone: 'danger' }))) return
 */

let resolveFn = null

export const useConfirmStore = defineStore('confirm', {
    state: () => ({
        open: false,
        message: '',
        confirmLabel: 'Ya, lanjutkan',
        cancelLabel: 'Batal',
        tone: 'default',
    }),

    actions: {
        /**
         * @returns {Promise<boolean>} true kalau pengguna menekan tombol konfirmasi.
         */
        ask(message, options = {}) {
            // Jawab dengan false kalau ada dialog yang masih terbuka.
            //
            // Tanpa ini, permintaan kedua akan menimpa resolveFn yang lama
            // dan membuat promise pertama menggantung selamanya -- pemanggil
            // pertama tidak akan pernah melanjutkan, sehingga tidak ada
            // yang terjadi setelah users menekan "Hapus".
            if (resolveFn) {
                resolveFn(false)
            }

            this.message = message
            this.confirmLabel = options.confirmLabel || 'Ya, lanjutkan'
            this.cancelLabel = options.cancelLabel || 'Batal'
            this.tone = options.tone === 'danger' ? 'danger' : 'default'
            this.open = true

            return new Promise((resolve) => {
                resolveFn = resolve
            })
        },

        /** Dipanggil komponen ConfirmHost saat tombol ditekan. */
        answer(jawabannya) {
            this.open = false

            const resolve = resolveFn
            resolveFn = null

            if (resolve) resolve(jawabannya)
        },

        /** Dipanggil saat pengguna menekan Escape atau klik area gelap. */
        cancel() {
            this.answer(false)
        },
    },
})