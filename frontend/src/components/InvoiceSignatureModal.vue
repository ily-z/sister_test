<script setup>
import { computed, reactive, ref, watch } from 'vue'

import SignaturePad from '@/components/SignaturePad.vue'
import { api, readError } from '@/lib/api'
import { useToastStore } from '@/stores/toast'

/*
 * ============================================================================
 *  MODAL TANDA TANGAN INVOICE
 * ============================================================================
 *
 *  Dipakai oleh halaman cetak invoice sewa maupun invoice bengkel. Keduanya
 *  punya aturan tanda tangan yang sama persis, jadi logikanya ditulis sekali
 *  di sini -- bukan disalin dua kali lalu dibiarkan berbeda.
 *
 *  Aturan yang ditegakkan backend:
 *    - blok tanpa gambar diabaikan, jadi boleh mengirim keduanya walau hanya
 *      satu yang digambar
 *    - menimpa tanda tangan yang sudah ada butuh confirm_replace, kalau tidak
 *      backend membalas 422
 *    - PDF di-generate ulang setiap kali penyimpanan berhasil
 *
 *  Saat ini blok yang ada adalah pelanggan dan finance. Daftar
 *  ini dibaca dari signatures.blok, bukan dari konstanta lokal, supaya
 * Judul dan urutan blok mengikuti InvoiceSignature::ROLES di backend.
 */

const props = defineProps({
    invoiceId: { type: [Number, String], required: true },
    signatures: { type: Object, default: null },
    /* Nama default untuk blok yang belum ditandatangani. */
    defaultNames: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['saved', 'close'])

const toast = useToastStore()

const blok = reactive({
    pelanggan: { data: '', nama: '', ganti: false },
    finance: { data: '', nama: '', ganti: false },
})

const simpan = ref(false)
const errors = ref({})
const errorUmum = ref('')

const daftarBlok = computed(() => props.signatures?.blok || [])

/*
 * Daftar role diambil dari respons backend. Kalau backend menambah blok
 * ketiga di masa depan, modal ini otomatis ikut menampilkannya tanpa perlu
 * diubah -- selama namanya bukan "pelanggan"/"finance", blok itu akan
 * tersimpan di reactive() sebagai properti baru dan tetap terkirim.
 */
const roles = computed(() => daftarBlok.value.map((b) => b.role))

const blokTerisi = computed(() =>
    Object.values(blok).filter((b) => b.data).length
)

const sudahLengkap = computed(() => Boolean(props.signatures?.lengkap))

function cariBlok(role) {
    return daftarBlok.value.find((b) => b.role === role) || null
}

function namaBawaan(role) {
    return props.defaultNames[role] || ''
}

function inisialisasi() {
    errors.value = {}
    errorUmum.value = ''

    for (const role of roles.value) {
        if (!blok[role]) {
            blok[role] = { data: '', nama: '', ganti: false }
        }

        const ada = cariBlok(role)

        blok[role].data = ''
        blok[role].nama = ada?.signer_name || namaBawaan(role)
        blok[role].ganti = Boolean(ada?.ada)
    }
}

watch(() => props.signatures, inisialisasi, { immediate: true })

async function simpanTandaTangan() {
    simpan.value = true
    errors.value = {}
    errorUmum.value = ''

    if (blokTerisi.value === 0) {
        errorUmum.value = 'Coret tanda tangan minimal satu blok sebelum menyimpan.'
        simpan.value = false
        return
    }

    const payload = { signatures: {} }

    for (const role of roles.value) {
        if (!blok[role].data) continue

        payload.signatures[role] = {
            signer_name: blok[role].nama,
            signature_data: blok[role].data,
            confirm_replace: blok[role].ganti,
        }
    }

    try {
        const { data } = await api.post(
            `/invoices/${props.invoiceId}/signature`,
            payload
        )

        /*
         * pdf_error true berarti tanda tangan tersimpan tapi dompdf gagal.
         * Status tanda tangan sudah benar di database, jadi pemanggil cukup
         * memuat ulang -- yang perlu ditambahkan cuma penjelasan masalahnya.
         */
        if (data.pdf_error) {
            toast.error(data.message)
        } else {
            toast.success(data.message || 'Tanda tangan tersimpan.')
        }

        emit('saved')
    } catch (e) {
        const parsed = readError(e)

        errors.value = parsed.fieldErrors
        errorUmum.value = Object.keys(parsed.fieldErrors).length
            ? ''
            : parsed.message
    } finally {
        simpan.value = false
    }
}
</script>

<template>
    <div
        class="inv-modal-backdrop inv-hide-print"
        @click.self="emit('close')"
    >
        <div class="inv-modal">
            <h3 class="text-base font-bold text-slate-800">Tanda Tangan</h3>
            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                Backend membuat ulang PDF setiap kali tanda tangan disimpan. Blok
                yang sudah terisi hanya ikut terkirim kalau coretannya digambar
                ulang.
            </p>

            <p v-if="errorUmum" class="inv-alert inv-alert-error mt-3">
                {{ errorUmum }}
            </p>

            <div class="space-y-5 mt-4">
                <div
                    v-for="role in roles"
                    :key="role"
                >
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <span class="inv-field-label mb-0">
                            {{ cariBlok(role)?.judul || role }}
                        </span>

                        <span
                            v-if="cariBlok(role)?.ada"
                            class="text-[11px] font-semibold text-emerald-600"
                        >
                            Sudah oleh {{ cariBlok(role).signer_name }}
                        </span>
                    </div>

                    <!-- Ganti tanda tangan yang sudah ada -->
                    <template v-if="cariBlok(role)?.ada">
                        <div class="inv-alert inv-alert-warn mb-2.5">
                            Menggambar ulang akan MENGGANTI tanda tangan lama. Centang
                            dulu untuk mengonfirmasi.
                        </div>

                        <label class="flex items-center gap-2 text-xs text-slate-700 mb-2.5">
                            <input type="checkbox" v-model="blok[role].ganti" />
                            Saya yakin ingin mengganti tanda tangan ini
                        </label>
                    </template>

                    <div class="mb-2">
                        <label class="inv-field-label" :for="`ttd-nama-${role}`">
                            Nama Penanda Tangan
                        </label>
                        <input
                            :id="`ttd-nama-${role}`"
                            v-model="blok[role].nama"
                            type="text"
                            maxlength="100"
                            class="inv-field"
                            :class="{ 'inv-field-error': errors[`signatures.${role}.signer_name`] }"
                            :disabled="cariBlok(role)?.ada && !blok[role].ganti"
                        />
                        <p
                            v-if="errors[`signatures.${role}.signer_name`]"
                            class="text-xs text-rose-600 mt-1"
                        >
                            {{ errors[`signatures.${role}.signer_name`] }}
                        </p>
                    </div>

                    <SignaturePad
                        v-model="blok[role].data"
                        :disabled="cariBlok(role)?.ada && !blok[role].ganti"
                    />

                    <p
                        v-if="errors[`signatures.${role}.signature_data`]"
                        class="text-xs text-rose-600 mt-1"
                    >
                        {{ errors[`signatures.${role}.signature_data`] }}
                    </p>
                    <p
                        v-if="errors[`signatures.${role}.confirm_replace`]"
                        class="text-xs text-rose-600 mt-1"
                    >
                        {{ errors[`signatures.${role}.confirm_replace`] }}
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button
                    type="button"
                    class="inv-btn inv-btn-ghost"
                    @click="emit('close')"
                >
                    Batal
                </button>

                <button
                    type="button"
                    class="inv-btn inv-btn-primary"
                    :disabled="simpan || blokTerisi === 0"
                    @click="simpanTandaTangan"
                >
                    {{ simpan ? 'Menyimpan...' : 'Simpan Tanda Tangan' }}
                </button>
            </div>

            <p class="text-[11px] text-slate-400 mt-3">
                {{ sudahLengkap
                    ? 'Semua blok sudah terisi. Menyimpan lagi akan menimpa yang ada.'
                    : 'Unduh PDF baru tersedia setelah semua blok terisi.' }}
            </p>
        </div>
    </div>
</template>