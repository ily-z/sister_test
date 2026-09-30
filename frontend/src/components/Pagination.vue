<script setup>
import { computed } from 'vue'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  PAGINASI
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Backend memakai offset/limit (bukan cursor), jadi halaman bisa dilewati
 *  langsung. Skema URL memakai `?page=2` -- disimpan di query string supaya
 *  tautan halaman bisa dibagikan dan tombol "kembali" peramban bekerja.
 *
 *  Kenapa tidak lompat ke halaman pertama saat jumlah data berubah:
 *  pengguna yang sedang membaca halaman 3 akan mendapat dirinya terlempar ke
 *  halaman 1 setiap kali ada data baru masuk. Itu lebih mengganggu daripada
 *  melihat baris yang sedikit kosong di halaman terakhir.
 */

const props = defineProps({
    currentPage: { type: Number, required: true },
    total: { type: Number, required: true },
    perPage: { type: Number, default: 10 },
})

const emit = defineEmits(['change'])

const lastPage = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)))

/*
 * Jendela halaman yang ditampilkan.
 *
 * Selalu menyertakan halaman pertama, halaman terakhir, dan satu halaman di
 * kedua sisinya, dengan "..." di sela-selanya. Daftar angka yang lengkap
 * akan membuat bilah paginasi melebar sendiri di layar sempit.
 */
const items = computed(() => {
    const last = lastPage.value
    const now = props.currentPage

    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1)
    }

    const result = [1]
    const from = Math.max(2, now - 1)
    const to = Math.min(last - 1, now + 1)

    if (from > 2) result.push('...')
    for (let i = from; i <= to; i++) result.push(i)
    if (to < last - 1) result.push('...')

    result.push(last)

    return result
})

const shownFrom = computed(() =>
    props.total === 0 ? 0 : (props.currentPage - 1) * props.perPage + 1
)

const shownTo = computed(() =>
    Math.min(props.currentPage * props.perPage, props.total)
)

function go(page) {
    if (page === '...' || page === props.currentPage) return
    if (page < 1 || page > lastPage.value) return

    emit('change', page)
}
</script>

<template>
    <div
        v-if="props.total > props.perPage"
        class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 md:px-6 py-4 border-t border-slate-100"
    >
        <p class="text-xs text-slate-500">
            Menampilkan {{ shownFrom }}&ndash;{{ shownTo }} dari {{ props.total }}
        </p>

        <nav class="flex items-center gap-1" aria-label="Navigasi halaman">
            <button
                type="button"
                class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                :disabled="props.currentPage <= 1"
                aria-label="Halaman sebelumnya"
                @click="go(props.currentPage - 1)"
            >
                <span class="material-symbols-outlined ms-icon text-[20px]">chevron_left</span>
            </button>

            <template v-for="(item, index) in items" :key="`${item}-${index}`">
                <span v-if="item === '...'" class="px-2 text-slate-400 text-sm select-none">&hellip;</span>
                <button
                    v-else
                    type="button"
                    class="min-w-[2.25rem] h-9 px-2 rounded-lg text-sm font-medium transition-colors"
                    :class="
                        item === props.currentPage
                            ? 'bg-brand-600 text-white'
                            : 'text-slate-600 hover:bg-slate-100'
                    "
                    :aria-current="item === props.currentPage ? 'page' : undefined"
                    @click="go(item)"
                >
                    {{ item }}
                </button>
            </template>

            <button
                type="button"
                class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                :disabled="props.currentPage >= lastPage"
                aria-label="Halaman berikutnya"
                @click="go(props.currentPage + 1)"
            >
                <span class="material-symbols-outlined ms-icon text-[20px]">chevron_right</span>
            </button>
        </nav>
    </div>
</template>