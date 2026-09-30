<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

/*
 * ══════════════════════════════════════════════════════════════════════════
 *  MASUK
 * ══════════════════════════════════════════════════════════════════════════
 *
 *  Field yang dikirim adalah `nama`, bukan `email` -- sama seperti versi
 *  Blade. Backend mencari user berdasarkan kolom nama
 *  (App\Http\Controllers\Api\AuthController::login).
 *
 *  "Ingat saya" tidak ada di sini. Token sudah disimpan di localStorage dan
 *  berlaku 8 jam (AUTH_API_TOKEN_TTL di backend/.env), jadi checkbox tersebut
 *  hanya akan berbohong tentang sesuatu yang sebenarnya sudah terjadi.
 *
 *  Mengubah dari Blade ke sini, perilaku yang benar-benar berbeda:
 *
 *    1. Tidak ada halaman yang dimuat ulang, jadi pesan error dari server
 *       tetap tampil di tempatnya -- bukan hilang begitu halaman berikutnya
 *       dimuat.
 *    2. "Ingat saya" dihapus, lihat catatan di atas.
 *    3. Tombol bisa diklik berulang selama request berjalan. Di Blade, submit
 *       kedua tidak mungkin terjadi karena halaman sudah berganti. Di sini
 *       perlu dikunci eksplisit, kalau tidak backend menerima dua permintaan
 *       login dan menerbitkan dua token untuk satu akun.
 */

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const nama = ref('')
const password = ref('')
const showPassword = ref(false)

const canSubmit = computed(
    () => nama.value.trim() !== '' && password.value !== '' && !auth.loggingIn
)

async function onSubmit() {
    if (!canSubmit.value) return

    try {
        await auth.login({ nama: nama.value.trim(), password: password.value })

        /*
         * Kembali ke halaman yang tadi dicoba, bukan selalu ke dashboard.
         *
         * Query `redirect` diisi router guard sebelum mengarahkan ke sini
         * (lihat src/router/index.js). Kalau value ini berasal dari
         * address bar, isinya bisa apa saja -- jadi harus diperiksa dulu
         * sebelum dipakai sebagai tujuan. Tanpa pemeriksaan itu, orang bisa
         * membuat tautan yang mengirim pengguna ke situs lain begitu ia
         * menekan tombol masuk.
         */
        const tujuan = route.query.redirect

        if (typeof tujuan === 'string' && tujuan.startsWith('/') && !tujuan.startsWith('//')) {
            await router.push(tujuan)
        } else {
            await router.push({ name: 'dashboard' })
        }
    } catch {
        // Pesan sudah disimpan di auth.loginError oleh store, jadi
        // halaman ini menampilkannya sendiri di bawah tombol dan tidak
        // perlu melempar ulang error-nya.
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-xl shadow-lg border border-slate-200 p-8">
            <div class="text-center mb-8">
                <div
                    class="w-12 h-12 bg-brand-600 rounded-xl flex items-center justify-center mx-auto mb-4"
                >
                    <span class="material-symbols-outlined text-white text-[22px]">directions_car</span>
                </div>

                <h1 class="text-2xl font-bold text-slate-800">Precision Fleet</h1>
                <p class="text-sm text-slate-500 mt-1">Masukkan kredensial Anda untuk masuk</p>
            </div>

            <!--
                Pesan error dari server.

                auth.loginError dipakai, bukan pesan lokal. Backend sengaja
                memakai satu pesan yang sama untuk "password salah" dan
                "akun tidak aktif", jadi tidak ada informasi yang bocor
                tentang akun mana yang ada.
            -->
            <div
                v-if="auth.loginError"
                class="bg-rose-50 text-rose-600 p-3 rounded-lg text-sm mb-5 border border-rose-200 flex items-start gap-2"
                role="alert"
            >
                <span class="material-symbols-outlined ms-icon text-[18px] mt-px shrink-0">error</span>
                <span>{{ auth.loginError }}</span>
            </div>

            <form novalidate @submit.prevent="onSubmit">
                <div class="mb-5">
                    <label for="nama" class="form-label">Nama</label>
                    <input
                        id="nama"
                        v-model="nama"
                        type="text"
                        required
                        autofocus
                        autocomplete="username"
                        autocapitalize="none"
                        spellcheck="false"
                        class="form-input"
                        placeholder="Masukkan nama Anda"
                        :class="{ 'form-input-error': auth.loginError }"
                    />
                </div>

                <div class="mb-6">
                    <label for="password" class="form-label">Password</label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            class="form-input pr-11"
                            placeholder="Kata sandi Anda"
                            :class="{ 'form-input-error': auth.loginError }"
                        />
                        <button
                            type="button"
                            class="absolute right-1 top-1/2 -translate-y-1/2 p-2 rounded-lg text-slate-400 hover:text-slate-600 transition-colors"
                            :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            @click="showPassword = !showPassword"
                        >
                            <span class="material-symbols-outlined ms-icon text-[20px]">
                                {{ showPassword ? 'visibility_off' : 'visibility' }}
                            </span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full py-2.5" :disabled="!canSubmit">
                    <span
                        v-if="auth.loggingIn"
                        class="material-symbols-outlined ms-icon text-[18px] animate-spin"
                    >
                        progress_activity
                    </span>
                    {{ auth.loggingIn ? 'Memproses...' : 'Masuk Sistem' }}
                </button>
            </form>
        </div>
    </div>
</template>