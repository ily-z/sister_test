<script setup>
import { onBeforeUnmount, onMounted } from 'vue'

import { useConfirmStore } from '@/stores/confirm'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  DIALOG KONFIRMASI
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Render dialog global. Arahkan pengguna ke sini dengan:
 *
 *      const confirm = useConfirmStore()
 *      if (!(await confirm.ask('Hapus pesanan ini?', { tone: 'danger' }))) return
 *
 *  Dipasang di luar RouterView (lihat App.vue) supaya tidak ikut
 *  di-destroy ketika pengguna berpindah halaman.
 */

const confirm = useConfirmStore()

const TONE = {
    default: {
        icon: 'help',
        iconClass: 'text-brand-500',
        buttonClass: 'bg-brand-600 hover:bg-brand-700 focus-visible:ring-brand-500',
    },
    danger: {
        icon: 'warning',
        iconClass: 'text-rose-500',
        buttonClass: 'bg-rose-600 hover:bg-rose-700 focus-visible:ring-rose-500',
    },
}

/* Escape untuk batal. */
function onKeydown(event) {
    if (event.key === 'Escape' && confirm.open) {
        confirm.cancel()
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="confirm.open"
                class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
                @click.self="confirm.cancel()"
            >
                <div
                    class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6"
                    role="alertdialog"
                    aria-modal="true"
                    :aria-label="confirm.message"
                >
                    <div class="flex items-start gap-4">
                        <span
                            class="material-symbols-outlined ms-icon text-[28px] shrink-0"
                            :class="TONE[confirm.tone].iconClass"
                        >
                            {{ TONE[confirm.tone].icon }}
                        </span>

                        <p class="text-[15px] leading-relaxed text-slate-700 pt-1.5 flex-1">
                            {{ confirm.message }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" class="btn-secondary" @click="confirm.cancel()">
                            {{ confirm.cancelLabel }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors focus-visible:outline-none focus-visible:ring-2"
                            :class="TONE[confirm.tone].buttonClass"
                            @click="confirm.answer(true)"
                        >
                            {{ confirm.confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>