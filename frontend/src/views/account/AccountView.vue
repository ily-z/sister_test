<script setup>
import { computed, ref } from 'vue'

import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useConfirmStore } from '@/stores/confirm'
import { readError } from '@/lib/api'
import { formatSisaWaktu } from '@/lib/format'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  AKUN SAYA
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Tiga hal yang bisa dilakukan pengguna atas akunnya sendiri:
 *
 *    1. Melihat daftar izin yang dimilikinya. Segera setelah permission
 *       berubah di sisi server, daftar ini ikut berubah -- karena diambil
 *       dari /api/auth/me, bukan dari cache lokal.
 *
 *    2. Mengubah password.
 *
 *    3. Mematikan token di perangkat lain.
 *
 *  ── SOAL MENGUBAH PASSWORD ──────────────────────────────────────────────────
 *
 *  Setelah password diganti, backend mencabut SEMUA token kecuali yang
 *  sedang dipakai. Itu disengaja: kalau password bocor, sesi yang sudah aktif
 *  ikut harus mati.
 *
 *  Token yang sedang dipakai sengaja TIDAK dicabut. Kalau ikut dicabut,
 *  pengguna langsung terlogout di perangkat yang sedang ia pakai dan
 *  kehilangan form yang belum tersimpan -- belum sempat menyalin apa pun.
 *  Konsekuensinya: setelah mengganti password, pengguna tetap login di
 *  perangkat ini dan langsung dikeluarkan dari semua perangkat lain.
 *  Perilaku ini yang benar, dan dijelaskan di sini supaya tidak ada yang
 *  mengira sistemnya salah.
 */

const auth = useAuthStore()
const toast = useToastStore()
const confirm = useConfirmStore()

const form = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const fieldErrors = ref({})
const submitting = ref(false)

const hakAkses = computed(() => auth.user?.permissions || [])

async function gantiPassword() {
    if (submitting.value) return

    submitting.value = true
    fieldErrors.value = {}

    try {
        const pesan = await auth.changePassword(
            form.value.current_password,
            form.value.password,
            form.value.password_confirmation
        )

        toast.success(pesan || 'Password berhasil diperbarui.')

        form.value = { current_password: '', password: '', password_confirmation: '' }
    } catch (e) {
        const detail = readError(e)
        fieldErrors.value = detail.fieldErrors
        toast.error(detail.message)
    } finally {
        submitting.value = false
    }
}

async function logoutSemua() {
    const ok = await confirm.ask(
        'Keluar dari semua perangkat lain? Anda tetap login di perangkat ini.',
        {
            confirmLabel: 'Ya, keluarkan',
            tone: 'danger',
        }
    )

    if (!ok) return

    await auth.logoutAll()
    toast.success('Semua perangkat lain sudah dikeluarkan.')
}
</script>

<template>
    <div class="max-w-3xl mx-auto px-4 md:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Akun Saya</h1>
            <p class="text-sm text-slate-500 mt-1">Informasi akun, hak akses, dan keamanan.</p>
        </div>

        <!-- Profil -->
        <section class="pf-card p-5 mb-5">
            <div class="flex items-start gap-4">
                <div
                    class="w-14 h-14 rounded-full bg-brand-600 text-white flex items-center justify-center text-xl font-bold shrink-0"
                >
                    {{ (auth.displayName || '?').slice(0, 1).toUpperCase() }}
                </div>

                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-bold text-slate-800">{{ auth.displayName }}</h2>
                    <p class="text-sm text-slate-500">{{ auth.user?.email }}</p>

                    <div class="flex flex-wrap gap-2 mt-3">
                        <span class="badge-netral">{{ auth.roleLabel }}</span>
                        <span v-if="auth.isOwner" class="badge-lunas">Owner</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Hak akses -->
        <section class="pf-card p-5 mb-5">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                        Hak Akses
                    </h2>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Daftar ini dibaca dari server setiap kali halaman ini dibuka.
                        Menu dan tombol di aplikasi menyesuaikan dengan daftar ini.
                    </p>
                </div>
            </div>

            <div v-if="!hakAkses.length" class="text-sm text-slate-400 py-4 text-center">
                Akun ini belum punya izin khusus. Akses dibatasi oleh perannya.
            </div>

            <div v-else class="flex flex-wrap gap-1.5">
                <span
                    v-for="izin in hakAkses"
                    :key="izin"
                    class="px-2 py-1 rounded-md bg-slate-100 text-slate-700 text-[11px] font-mono"
                >
                    {{ izin }}
                </span>
            </div>
        </section>

        <!-- Ganti password -->
        <section class="pf-card p-5 mb-5">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-1">
                Ganti Password
            </h2>
            <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                Setelah diganti, semua perangkat lain akan dikeluarkan dari sistem.
                Perangkat ini tetap login.
            </p>

            <form class="space-y-4 max-w-md" @submit.prevent="gantiPassword">
                <div>
                    <label class="form-label" for="current_password">Password Saat Ini</label>
                    <input
                        id="current_password"
                        v-model="form.current_password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="form-input"
                        :class="{ 'form-input-error': fieldErrors.current_password }"
                    />
                    <p v-if="fieldErrors.current_password" class="text-xs text-rose-600 mt-1">
                        {{ fieldErrors.current_password }}
                    </p>
                </div>

                <div>
                    <label class="form-label" for="new_password">Password Baru</label>
                    <input
                        id="new_password"
                        v-model="form.password"
                        type="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        class="form-input"
                        :class="{ 'form-input-error': fieldErrors.password }"
                    />
                    <p v-if="fieldErrors.password" class="text-xs text-rose-600 mt-1">
                        {{ fieldErrors.password }}
                    </p>
                    <p v-else class="text-[11px] text-slate-400 mt-1">Minimal 8 karakter.</p>
                </div>

                <div>
                    <label class="form-label" for="password_confirmation">Ulangi Password Baru</label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="form-input"
                        :class="{ 'form-input-error': fieldErrors.password_confirmation }"
                    />
                    <p v-if="fieldErrors.password_confirmation" class="text-xs text-rose-600 mt-1">
                        {{ fieldErrors.password_confirmation }}
                    </p>
                </div>

                <button
                    type="submit"
                    class="btn-primary"
                    :disabled="submitting || form.password !== form.password_confirmation"
                >
                    <span
                        v-if="submitting"
                        class="material-symbols-outlined ms-icon text-[18px] animate-spin"
                    >
                        progress_activity
                    </span>
                    Simpan Password
                </button>

                <p
                    v-if="form.password && form.password !== form.password_confirmation"
                    class="text-xs text-amber-600"
                >
                    Password baru dan pengulangannya harus sama.
                </p>
            </form>
        </section>

        <!-- Perangkat -->
        <section class="pf-card p-5">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-1">
                Perangkat
            </h2>
            <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                Setiap browser yang Anda pakai untuk masuk punya token sendiri. Token
                berlaku 8 jam sejak login.
            </p>

            <button type="button" class="btn-danger" @click="logoutSemua">
                <span class="material-symbols-outlined ms-icon text-[18px]">logout</span>
                Keluar dari Semua Perangkat Lain
            </button>
        </section>
    </div>
</template>