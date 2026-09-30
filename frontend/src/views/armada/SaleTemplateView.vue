<script setup>
import { computed, ref } from 'vue'

import { useAuthStore } from '@/stores/auth'
import { useConfirmStore } from '@/stores/confirm'
import { useToastStore } from '@/stores/toast'

import { api, readError } from '@/lib/api'

/*
 * ============================================================================
 *  TEMPLATE BROCHURE
 * ============================================================================
 *
 *  Menyunting satu berkas HTML di storage/app/sale-template/content.html.
 *
 *  --- BERKAS DISIMPAN DI SERVER, BUKAN DI REPO ---------------------------
 *
 *  Endpoint GET /armada/sale-template membaca disk "local" dan mengembalikan
 *  { content: string | null }. null berarti berkas belum pernah dibuat --
 *  itu kondisi awal yang wajar, bukan error, jadi layar menampilkan penyusun
 *  teks kosong dengan tombol Simpan yang langsung aktif.
 *
 *  --- MENGAPA BUKAN RICH TEXT EDITOR --------------------------------------
 *
 *  Isi berkas dipakai untuk membuat PDF brosur lewat dompdf di backend, dan
 *  dompdf hanya mengenal CSS sederhana. Editor visual yang kaya bisa
 *  menghasilkan markup yang tidak bisa dirender -- font web, flexbox, dan
 *  CSS modern lain diabaikan diam-diam oleh dompdf. Jadi yang diedit di sini
 *  adalah HTML sumbernya, dan pengguna perlu tahu batasan itu sebelum
 *  mulai menulis. Lihat docs/KNOWN_ISSUES.md untuk catatan lengkapnya.
 *
 *  --- PENYIMPANAN SELURUH BERKAS, BUKAN PATCH ----------------------------
 *
 *  POST /armada/sale-template/save menimpa seluruh isi. Tidak ada endpoint
 *  parse-and-patch, jadi frontend tidak pernah mengirim potongan. Isi
 *  yang tampil persis seperti yang akan tersimpan, jadi tidak ada keadaan
 *  setengah jalan di mana layar berbeda dari berkas. Karena itu perubahan
 *  dibandingkan dengan konten yang dimuat, dan tombol Simpan nonaktif selama
 *  tidak ada perbedaan.
 */

const auth = useAuthStore()
const confirm = useConfirmStore()
const toast = useToastStore()

const memuat = ref(true)
const menyimpan = ref(false)
const galat = ref('')

/* Konten asli dari server. Disimpan terpisah supaya bisa dibandingkan dengan
 * isi editor dan menentukan apakah tombol Simpan perlu aktif. */
const asli = ref('')
const konten = ref('')

const bisaUbah = computed(() => auth.can('fleet.edit'))

async function muat() {
    memuat.value = true
    galat.value = ''

    try {
        const { data } = await api.get('/armada/sale-template')

        asli.value = data.content || ''
        konten.value = data.content || ''
    } catch (e) {
        galat.value = readError(e).message
    } finally {
        memuat.value = false
    }
}

const berubah = () => konten.value !== asli.value

const panjang = () => konten.value.length

async function simpan() {
    if (menyimpan.value || !berubah()) return

    /*
     * Divalidasi di sisi klien juga, bukan hanya oleh validate() di server.
     * Aturan "harus diisi" itu status quo dari validate() di backend, jadi
     * ini tidak menambah aturan baru: pesan yang sama muncul lebih cepat
     * tanpa satu request yang tidak perlu dikirim.
     */
    if (!konten.value.trim()) {
        toast.error('Template tidak boleh kosong.')
        return
    }

    menyimpan.value = true

    try {
        const { data } = await api.post('/armada/sale-template/save', {
            content: konten.value,
        })

        toast.success(data.message || 'Template berhasil disimpan.')

        /*
         * Konten lokal disalin ke `asli` supaya tombol Simpan langsung
         * nonaktif. Tidak ada request ulang: isi yang dikirim persis sama
         * dengan isi yang sedang tampil, jadi apa pun yang dikembalikan server
         * pasti sama. */
        asli.value = konten.value
    } catch (e) {
        const errors = e?.response?.data?.errors

        if (errors?.content) {
            toast.error(
                Array.isArray(errors.content) ? errors.content[0] : errors.content
            )
        } else {
            toast.error(readError(e).message)
        }
    } finally {
        menyimpan.value = false
    }
}

async function buangPerubahan() {
    /*
     * Dipakai dialog konfirmasi milik aplikasi, bukan window.confirm().
     * Lihat catatan alasannya di stores/confirm.js.
     */
    if (berubah()) {
        const ok = await confirm.ask('Buang perubahan yang belum disimpan?', {
            confirmLabel: 'Ya, buang',
            tone: 'danger',
        })

        if (!ok) return
    }

    konten.value = asli.value
}

muat()
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Kepala -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Template Brosur</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    HTML yang dipakai untuk membuat PDF brosur penjualan armada.
                </p>
            </div>

            <div v-if="!memuat && !galat" class="flex items-center gap-2">
                <button
                    type="button"
                    class="btn-secondary text-xs"
                    :disabled="!berubah() || menyimpan"
                    @click="buangPerubahan"
                >
                    Buang Perubahan
                </button>

                <button
                    type="button"
                    class="btn-primary text-xs"
                    :disabled="!berubah() || menyimpan || !bisaUbah"
                    :title="bisaUbah ? '' : 'Anda tidak punya hak akses mengubah template.'"
                    @click="simpan"
                >
                    <span
                        v-if="menyimpan"
                        class="material-symbols-outlined ms-icon text-[16px] animate-spin"
                    >
                        progress_activity
                    </span>
                    <span v-else class="material-symbols-outlined ms-icon text-[16px]">save</span>
                    {{ menyimpan ? 'Menyimpan...' : 'Simpan Template' }}
                </button>
            </div>
        </div>

        <!-- Galat muat -->
        <div
            v-if="galat"
            class="pf-card px-4 py-3 border-rose-200 bg-rose-50/70 mb-4 flex items-center gap-3"
        >
            <span class="material-symbols-outlined ms-icon text-rose-600 text-[20px]">
                error
            </span>
            <p class="text-sm text-rose-900 flex-1">{{ galat }}</p>

            <button type="button" class="btn-secondary text-xs" @click="muat">Coba lagi</button>
        </div>

        <!-- Muat -->
        <div v-else-if="memuat" class="pf-card px-4 py-10 text-center">
            <span class="material-symbols-outlined ms-icon text-[24px] animate-spin text-slate-400">
                progress_activity
            </span>
            <p class="text-sm text-slate-500 mt-2">Memuat template...</p>
        </div>

        <template v-else>
            <!-- Catatan -->
            <div
                class="pf-card px-4 py-3 mb-3 border-amber-200 bg-amber-50/70 flex items-start gap-3"
            >
                <span class="material-symbols-outlined ms-icon text-amber-600 text-[20px] mt-0.5">
                    info
                </span>
                <div class="text-sm text-amber-900">
                    <p class="font-medium">
                        Berkas ini dirender jadi PDF oleh dompdf di server.
                    </p>
                    <p class="text-amber-800 mt-0.5">
                        Yang aman dipakai: HTML dasar, <code>&lt;table&gt;</code> untuk
                        layout, dan CSS sederhana. Font web, flexbox, grid, serta
                        animasi tidak akan muncul di PDF -- bukan karena template
                        salah, tapi karena dompdf tidak merendernya.
                    </p>
                </div>
            </div>

            <!-- Editor + pratinjau -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <section class="pf-card overflow-hidden flex flex-col">
                    <div
                        class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100"
                    >
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined ms-icon text-[16px] text-slate-400">
                                code
                            </span>
                            <h2 class="text-xs font-bold text-slate-700">HTML</h2>
                        </div>

                        <span
                            class="text-[10px] tabular-nums"
                            :class="berubah ? 'text-amber-600 font-medium' : 'text-slate-400'"
                        >
                            <template v-if="berubah">Belum disimpan &middot; {{ panjang() }} karakter</template>
                            <template v-else>{{ panjang() }} karakter</template>
                        </span>
                    </div>

                    <textarea
                        v-model="konten"
                        spellcheck="false"
                        :readonly="!bisaUbah"
                        class="w-full flex-1 min-h-[480px] px-4 py-3 font-mono text-xs text-slate-700 bg-slate-50 border-0 focus:outline-none focus:ring-0 resize-y"
                        placeholder="&lt;h1&gt;Judul Brosur&lt;/h1&gt;&#10;&lt;p&gt;Isi brosur di sini...&lt;/p&gt;"
                    ></textarea>
                </section>

                <section class="pf-card overflow-hidden flex flex-col">
                    <div
                        class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100"
                    >
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined ms-icon text-[16px] text-slate-400">
                                visibility
                            </span>
                            <h2 class="text-xs font-bold text-slate-700">Pratinjau</h2>
                        </div>

                        <span class="text-[10px] text-slate-400">Tampilan di browser</span>
                    </div>

                    <div class="flex-1 min-h-[480px] bg-white">
                        <iframe
                            v-if="konten.trim()"
                            :srcdoc="konten"
                            class="w-full h-full min-h-[480px] border-0"
                            title="Pratinjau template brosur"
                            sandbox=""
                        ></iframe>

                        <div v-else class="h-full min-h-[480px] flex flex-col items-center justify-center text-center px-6">
                            <span class="material-symbols-outlined text-[32px] text-slate-300">
                                description
                            </span>
                            <p class="text-sm font-medium text-slate-600 mt-3">Template masih kosong</p>
                            <p class="text-xs text-slate-400 mt-1 max-w-xs">
                                Isi HTML di panel sebelah kiri, pratinjaunya akan muncul di sini.
                            </p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Keterangan tambahan -->
            <div class="pf-card px-4 py-3 mt-3">
                <h3 class="text-xs font-bold text-slate-700 mb-2">Templat yang tersedia</h3>

                <ul class="text-xs text-slate-600 space-y-1.5">
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined ms-icon text-[14px] text-emerald-500 mt-0.5">
                            check_circle
                        </span>
                        <span>
                            <code>&lt;h1&gt;</code> sampai <code>&lt;h4&gt;</code>,
                            <code>&lt;p&gt;</code>, <code>&lt;ul&gt;</code>,
                            <code>&lt;ol&gt;</code>, <code>&lt;li&gt;</code>,
                            <code>&lt;strong&gt;</code>, <code>&lt;em&gt;</code>
                        </span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined ms-icon text-[14px] text-emerald-500 mt-0.5">
                            check_circle
                        </span>
                        <span>
                            <code>&lt;table&gt;</code> dengan
                            <code>border</code> dan <code>width</code> untuk susunan data
                        </span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined ms-icon text-[14px] text-emerald-500 mt-0.5">
                            check_circle
                        </span>
                        <span>
                            Gaya dasar lewat atribut <code>style</code>: warna, ukuran,
                            perataan, padding
                        </span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined ms-icon text-[14px] text-emerald-500 mt-0.5">
                            check_circle
                        </span>
                        <span>
                            <code>&lt;img src&gt;</code> -- syaratnya berkas gambar ada di
                            <code>storage/app/public</code>
                        </span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined ms-icon text-[14px] text-rose-500 mt-0.5">
                            cancel
                        </span>
                        <span>
                            CSS eksternal, <code>&lt;script&gt;</code>,
                            <code>flex</code>/<code>grid</code>, dan animasi
                        </span>
                    </li>
                </ul>
            </div>
        </template>
    </div>
</template>