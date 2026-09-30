<script setup>
import EmptyState from '@/components/EmptyState.vue'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  KONDISI LOADING / GAGAL / KOSONG
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Tiga kondisi yang selalu muncul bersama di halaman mana pun yang memuat
 *  data dari backend. Menggabungkannya di satu komponen memastikan tidak ada
 *  halaman yang lupa menampilkan salah satu -- terutama kondisi gagal, yang
 *  paling sering terlewat dan paling merusak kepercayaan pengguna.
 *
 *  Pemakaian:
 *
 *      <DataState
 *          :loading="bookings.loading"
 *          :error="bookings.error"
 *          :is-empty="!bookings.loading && list.length === 0"
 *          empty-title="Belum ada pesanan"
 *          empty-description="Pesanan yang dibuat akan muncul di sini."
 *          @retry="bookings.load()"
 *      >
 *          <table>...</table>
 *      </DataState>
 */

const props = defineProps({
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    isEmpty: { type: Boolean, default: false },

    emptyIcon: { type: String, default: 'inbox' },
    emptyTitle: { type: String, default: 'Belum ada data' },
    emptyDescription: { type: String, default: '' },
})

const emit = defineEmits(['retry'])

/*
 * Jumlah kerangka yang ditampilkan saat loading.
 *
 * Stateless: halaman tidak punya data lama untuk ditampilkan sambil
 * menunggu. Kerangka tiga baris memberi kesan halaman sedang bekerja; tanpa
 * itu layar kosong selama beberapa detik dan pengguna cenderung menekan
 * tombol repeatedly.
 */
const skeletonRows = 5
</script>

<template>
    <!-- Loading -->
    <div v-if="props.loading" class="p-4 md:p-6" aria-busy="true">
        <div v-for="row in skeletonRows" :key="row" class="flex items-center gap-4 py-3.5">
            <div class="h-10 w-10 rounded-lg bg-slate-100 shrink-0 animate-pulse" />
            <div class="flex-1 space-y-2 min-w-0">
                <div class="h-3.5 rounded bg-slate-100 animate-pulse" :style="{ width: 40 + row * 8 + '%' }" />
                <div class="h-3 rounded bg-slate-100/70 animate-pulse" :style="{ width: 20 + row * 5 + '%' }" />
            </div>
            <div class="h-7 w-20 rounded-md bg-slate-100 shrink-0 animate-pulse" />
        </div>
    </div>

    <!-- Gagal -->
    <div v-else-if="props.error" class="px-6 py-12 text-center">
        <div
            class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4"
        >
            <span class="material-symbols-outlined text-[26px]">error</span>
        </div>

        <h3 class="text-base font-semibold text-slate-700">Gagal memuat data</h3>
        <p class="text-sm text-slate-500 mt-1.5 max-w-md mx-auto leading-relaxed">
            {{ props.error }}
        </p>

        <button type="button" class="btn-secondary mt-5" @click="emit('retry')">
            <span class="material-symbols-outlined ms-icon text-[18px]">refresh</span>
            Coba lagi
        </button>
    </div>

    <!-- Kosong -->
    <EmptyState
        v-else-if="props.isEmpty"
        :icon="props.emptyIcon"
        :title="props.emptyTitle"
        :description="props.emptyDescription"
    >
        <slot name="empty-action" />
    </EmptyState>

    <!-- Isi -->
    <slot v-else />
</template>