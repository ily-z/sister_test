<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DataState from '@/components/DataState.vue'
import Pagination from '@/components/Pagination.vue'

import { useAuthStore } from '@/stores/auth'
import { useConfirmStore } from '@/stores/confirm'
import { useToastStore } from '@/stores/toast'

import { api, readError } from '@/lib/api'
import { formatSejak } from '@/lib/format'
import { warnaAktivitas } from '@/lib/warna'

/*
 * ============================================================================
 *  MANAJEMEN USER
 * ============================================================================
 *
 *  Meniru backend/resources/views/users/index.blade.php.
 *
 *  --- SATU PANGGILAN, BUKAN LIMA ------------------------------------------
 *
 *  GET /users mengembalikan lima hal sekaligus: daftar user (sudah dipaginate),
 *  nilai pencarian `q`, seluruh role, role yang boleh dipilih actor, dan
 *  delapan aktivitas terakhir. Tidak ada request tambahan saat halaman dibuka,
 *  termasuk untuk daftar role di form tambah.
 *
 *  --- `role` PADA DAFTAR OBJEK, PADA FORM IYA STRING -----------------------
 *
 *  Dua endpoint ini memakai dua bentuk data yang berbeda, dan bedanya mudah
 *  terlewat:
 *
 *    - Daftar (index) mengembalikan model User apa adanya. User punya
 *      getRoleAttribute() yang mengembalikan model Role, jadi user.role berisi
 *      objek { slug, nama, warna, ... }, bukan teks "admin".
 *    - Form (edit) memakai userPayload() yang sengaja dirapikan: role di sana
 *      berupa slug teks, dan muncul bersama role_name, role_label, is_owner,
 *      serta permissions.
 *
 *  Jadi baris tabel selalu dibaca lewat user.role?.slug, sedangkan nilai
 *  dropdown form selalu user.role. Owner baris tabel dikenali dari slug-nya,
 *  sedangkan status owner milik orang yang sedang login dibaca dari
 *  auth.isOwner -- field is_owner tidak ikut diserialisasi pada koleksi hasil
 *  paginate.
 *
 *  --- ROLE OWNER TIDAK BISA DIPILIH --------------------------------------
 *
 *  store() menolak role owner dengan 403, dan update() menolak owner baru
 *  juga dengan 403. Jadi owner tidak pernah muncul sebagai pilihan; yang
 *  tampil hanyalah baris owner sendiri, apa adanya.
 *
 *  --- TRANSFER OWNER ADALAH JALUR KHUSUS ----------------------------------
 *
 *  Kalau actor yang sedang login adalah owner dan memilih role lain untuk
 *  dirinya sendiri, updateRole() menolak dengan 422 sampai `new_owner_id`
 *  diisi. Halaman ini membuka dialog transfer, bukan mengirim permintaan
 *  yang pasti ditolak. Sistem wajib punya tepat satu owner, jadi pengganti
 *  harus dipilih lebih dulu.
 *
 *  --- `is_active` SELALU DIKIRIM ------------------------------------------
 *
 *  Ini jebakan yang mudah terlewat. Di store(), is_active yang tidak dikirim
 *  di-default true. Di update(), yang tidak dikirim di-default FALSE. Jadi
 *  form edit tidak boleh menggantungkan diri pada default server --
 *  checkboxnya selalu ikut dikirim, walaupun operator tidak menyentuhnya.
 *
 *  --- MENGHAPUS DIRI SENDIRI -----------------------------------------------
 *
 *  UserPolicy::delete() menolak actor menghapus dirinya sendiri, jadi tombol
 *  Hapus tidak dirender untuk baris itu. Backend juga menolak, jadi
 *  menyembunyikan tombolnya cuma supaya operator tidak melihat tombol yang
 *  pasti gagal.
 *
 *  --- MENONAKTIFKAN DIRI SENDIRI ------------------------------------------
 *
 *  Kasus lain: actor menonaktifkan dirinya sendiri lewat form edit.
 *  Responsnya tetap 200, jadi interceptor 401 tidak ikut bekerja. Padahal
 *  backend sudah mencabut semua token. Halaman ini karena itu membaca flag
 *  self_deactivated lalu mengeluarkan pengguna secara manual.
 */

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const confirm = useConfirmStore()
const toast = useToastStore()

const memuat = ref(true)
const error = ref('')

const users = ref(null)
const q = ref(route.query.q ? String(route.query.q) : '')
const roleOptions = ref({})
const selectableRoles = ref({})
const userActivities = ref([])

const PER_PAGE = 10

/* -------------------------------------------------------------------------
 *  DAFTAR
 * ---------------------------------------------------------------------- */

const daftar = computed(() => users.value?.data || [])
const total = computed(() => users.value?.total || 0)

/* Selectable roles datang sebagai objek { slug: nama }, jadi urutannya diambil
 * dari Object.entries, bukan dari kunci saja. */
const opsiRole = computed(() => Object.entries(selectableRoles.value))

const semuaRole = computed(() => Object.entries(roleOptions.value))

async function muat() {
    memuat.value = true
    error.value = ''

    try {
        const { data } = await api.get('/users', {
            params: {
                q: route.query.q || undefined,
                page: route.query.page > 1 ? route.query.page : undefined,
            },
        })

        users.value = data.users
        q.value = data.q || ''
        roleOptions.value = data.roleOptions || {}
        selectableRoles.value = data.selectableRoles || {}
        userActivities.value = data.userActivities || []
    } catch (e) {
        error.value = readError(e).message
    } finally {
        memuat.value = false
    }
}

/* Halaman dan pencarian disimpan di URL supaya tautan ke "halaman 3 user
 * aktif" bisa dibagikan. */
watch(
    () => [route.query.q, route.query.page],
    () => muat()
)

onMounted(muat)

function cari() {
    router.replace({ query: { q: q.value.trim() || undefined } })
}

function resetCari() {
    q.value = ''
    router.replace({ query: {} })
}

function gantiHalaman(page) {
    router.replace({
        query: {
            ...(route.query.q ? { q: route.query.q } : {}),
            page: page > 1 ? page : undefined,
        },
    })
}

/* -------------------------------------------------------------------------
 *  GANTI ROLE DI BARIS
 * ---------------------------------------------------------------------- */

/* Nilai dropdown per user, terpisah dari data server supaya memilih role
 * baru tidak langsung mengubah tampilan sebelum Simpan ditekan. */
const pilihanRole = ref({})

/* Actor hanya boleh mengubah role yang bisa dia kelola; backend sudah
 * menyaring selectableRoles. Yang perlu dijaga di sini: user yang sedang
 * login tidak boleh mengubah role-nya kecuali dia owner. */
function bolehUbahRole(user) {
    if (!auth.can('users.edit')) return false
    if (auth.user?.id !== user.id) return true

    return auth.isOwner
}

function ensurePilihan(user) {
    if (pilihanRole.value[user.id] === undefined) {
        pilihanRole.value = { ...pilihanRole.value, [user.id]: user.role?.slug || '' }
    }
}

function berubahRole(user) {
    return (pilihanRole.value[user.id] ?? user.role?.slug) !== (user.role?.slug ?? '')
}

const sedangSimpanRole = ref(0)

/* Dialog transfer kepemilikan. */
const transfer = ref(null)

async function simpanRole(user) {
    if (sedangSimpanRole.value) return

    const slugBaru = pilihanRole.value[user.id]

    if (!slugBaru || slugBaru === user.role?.slug) return

    /* Owner yang menurunkan dirinya sendiri harus menunjuk pengganti dulu. */
    if (auth.isOwner && auth.user?.id === user.id && slugBaru !== 'owner') {
        transfer.value = { user, slugBaru, ownerBaru: null }
        return
    }

    sedangSimpanRole.value = user.id

    try {
        const { data } = await api.patch(`/users/${user.id}/role`, { role: slugBaru })

        toast.success(data.message || 'Role berhasil diubah.')
        muat()
    } catch (e) {
        /* Kembalikan pilihan ke nilai server supaya layar tidak menampilkan
         * role yang ternyata gagal disimpan. */
        ensurePilihan(user)
        pilihanRole.value = { ...pilihanRole.value, [user.id]: user.role?.slug || '' }

        toast.error(readError(e).message)
    } finally {
        sedangSimpanRole.value = 0
    }
}

function tutupTransfer() {
    if (sedangSimpanRole.value) return

    transfer.value = null
}

/* Kandidat owner pengganti: semua user selain actor dan selain owner lama. */
const kandidatOwner = computed(() =>
    daftar.value
        .filter(
            (u) => u.id !== auth.user?.id && u.role?.slug !== 'owner'
        )
        .map((u) => ({ id: u.id, nama: u.nama, email: u.email }))
)

async function konfirmasiTransfer() {
    if (!transfer.value) return

    const { user, slugBaru, ownerBaru } = transfer.value

    if (!ownerBaru) {
        toast.error('Pilih Owner pengganti terlebih dahulu.')
        return
    }

    sedangSimpanRole.value = user.id

    try {
        const { data } = await api.patch(`/users/${user.id}/role`, {
            role: slugBaru,
            new_owner_id: ownerBaru,
        })

        toast.success(data.message || 'Kepemilikan berhasil ditransfer.')

        transfer.value = null

        /* Actor yang baru saja kehilangan status owner harus ambil ulang
         * profilnya. Kalau tidak, permission di menu dan pemblokiran route
         * masih memakai izin lama -- dan baginya backend juga sudah berubah,
         * jadi setiap klik berikutnya akan berakhir 403. */
        await auth.refresh()
        muat()
    } catch (e) {
        toast.error(readError(e).message)
    } finally {
        sedangSimpanRole.value = 0
    }
}

/* -------------------------------------------------------------------------
 *  FORM TAMBAH / UBAH
 * ---------------------------------------------------------------------- */

const dialog = ref(null)
const menyimpan = ref(false)
const galatForm = ref({})

/* Bentuk form yang sama dipakai tambah dan ubah; `id` menentukan endpoint. */
function formKosong() {
    return {
        id: null,
        nama: '',
        email: '',
        role: '',
        role_name: '',
        is_owner: false,
        is_active: true,
        password: '',
        password_confirmation: '',
    }
}

const form = ref(formKosong())

/* Password wajib saat menambah, opsional saat mengubah. */
const passwordWajib = computed(() => !form.value.id)

const bisaSimpan = computed(() => {
    if (!form.value.nama.trim() || !form.value.email.trim() || !form.value.role) return false
    if (passwordWajib.value && form.value.password.length < 6) return false

    return true
})

function bukaTambah() {
    form.value = formKosong()
    galatForm.value = {}

    /* Role pertama dari daftar yang boleh dipilih -- supaya pilihan pertama
     * langsung terisi, dan di sisi server memang tidak ada nilai bawaan. */
    form.value.role = opsiRole.value[0]?.[0] || ''
    form.value.role_name = opsiRole.value[0]?.[1] || ''

    dialog.value = 'tambah'
}

async function bukaUbah(user) {
    form.value = formKosong()
    galatForm.value = {}
    dialog.value = 'ubah'

    try {
        /* Endpoint edit dipakai, bukan baris dari daftar: daftar tidak
         * menyertakan daftar role yang boleh dipilih actor. */
        const { data } = await api.get(`/users/${user.id}/edit`)

        form.value = {
            ...formKosong(),
            id: data.user.id,
            nama: data.user.nama,
            email: data.user.email,
            role: data.user.role,
            role_name: data.user.role_name || '',
            is_owner: Boolean(data.user.is_owner),
            is_active: data.user.is_active,
        }

        selectableRoles.value = data.roleOptions || selectableRoles.value
    } catch (e) {
        toast.error(readError(e).message)
        dialog.value = null
    }
}

function tutupDialog() {
    if (menyimpan.value) return

    dialog.value = null
    galatForm.value = {}
}

async function simpanUser() {
    if (menyimpan.value) return

    menyimpan.value = true
    galatForm.value = {}

    /* is_active selalu ikut dikirim. Lihat catatan "is_active WAJIB DIKIRIM"
     * di kepala file: default-nya berbeda antara store dan update. */
    const body = {
        nama: form.value.nama.trim(),
        email: form.value.email.trim(),
        role: form.value.role,
        is_active: form.value.is_active,
    }

    /* Password kosong saat mengubah berarti "biarkan yang lama". */
    if (form.value.password) {
        body.password = form.value.password
        body.password_confirmation = form.value.password_confirmation
    }

    try {
        if (form.value.id) {
            const { data } = await api.put(`/users/${form.value.id}`, body)

            toast.success(data.message || 'User berhasil diperbarui.')

            /* Kalau actor menonaktifkan dirinya sendiri, backend sudah
             * mencabut semua tokennya. Respons ini masih sukses, jadi
             * keluarkan secara manual -- request berikutnya pasti 401. */
            if (data.self_deactivated) {
                auth.clear()
                router.push('/login')
            }
        } else {
            const { data } = await api.post('/users', {
                ...body,
                password: form.value.password,
                password_confirmation: form.value.password_confirmation,
            })

            toast.success(data.message || 'User berhasil ditambahkan.')
        }

        dialog.value = null
        muat()
    } catch (e) {
        const errors = e?.response?.data?.errors

        if (errors && Object.keys(errors).length) {
            galatForm.value = errors
        } else {
            toast.error(readError(e).message)
        }
    } finally {
        menyimpan.value = false
    }
}

/* -------------------------------------------------------------------------
 *  HAPUS
 * ---------------------------------------------------------------------- */

async function hapus(user) {
    const ok = await confirm.ask(`Hapus akun "${user.nama}"?`, {
        confirmLabel: 'Ya, hapus',
        tone: 'danger',
    })

    if (!ok) return

    try {
        const { data } = await api.delete(`/users/${user.id}`)

        toast.success(data.message || 'User berhasil dihapus.')
        muat()
    } catch (e) {
        toast.error(readError(e).message)
    }
}

/* -------------------------------------------------------------------------
 *  AKTIVITAS
 * ---------------------------------------------------------------------- */

/*
 * Deskripsi aktivitas dibuat backend sebagai beberapa baris diawali bullet,
 * satu baris per field yang berubah. Prefix-nya dipetakan ke lencana warna
 * supaya mudah dipindai.
 */
const BADGE_AKTIVITAS = [
    { prefix: '• Nama:', label: 'Nama', cls: 'bg-blue-100 text-blue-700' },
    { prefix: '• Email:', label: 'Email', cls: 'bg-emerald-100 text-emerald-700' },
    { prefix: '• Status:', label: 'Status', cls: 'bg-amber-100 text-amber-700' },
    { prefix: '• Role:', label: 'Role', cls: 'bg-violet-100 text-violet-700' },
    { prefix: '• Password:', label: 'Password', cls: 'bg-rose-100 text-rose-700' },
]

function barisAktivitas(teks) {
    return String(teks || '')
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean)
        .map((line) => {
            const badge = BADGE_AKTIVITAS.find((b) => line.startsWith(b.prefix))

            return {
                badge: badge || null,
                teks: badge ? line.slice(badge.prefix.length).trim() : line.replace(/^•\s*/, ''),
            }
        })
}

/* Warna ikon datang dari kolom activities.color sebagai teks ("purple",
 * "blue", ...). Kelasnya tidak boleh dirakit di sini: Tailwind hanya
 * menulis kelas yang ditemukan utuh di berkas sumber, jadi bg-${warna}-50
 * akan hilang diam-diam dari hasil build. Peta lengkapnya ada di
 * lib/warna.js. */
function kelasAktivitas(warna) {
    return warnaAktivitas(warna)
}

/* -------------------------------------------------------------------------
 *  HAK AKSES TOMBOL
 * ---------------------------------------------------------------------- */

const bolehBuat = computed(() => auth.can('users.create'))

/*
 * Slug permission apa adanya, bukan nama policy. UserPolicy memang
 * mengizinkan jalur alternatif lewat manage_users, tapi slug itu tidak pernah
 * ada di tabel permissions -- mencantumkannya hanya menambah kode tanpa
 * menambah akses. Owner lolos dari semua pemeriksaan karena hasPermission()
 * selalu mengembalikan true untuknya.
 */
const bolehUbah = computed(() => auth.can('users.edit'))

const bolehHapus = computed(() => auth.can('users.delete'))
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Kepala -->
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
        >
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Manajemen User</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Kelola akun pengguna dan role akses.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <RouterLink
                    v-if="auth.can('manage_permissions')"
                    to="/permissions"
                    class="btn-secondary"
                >
                    <span class="material-symbols-outlined ms-icon text-[20px]">tune</span>
                    Permission Role
                </RouterLink>

                <button
                    v-if="bolehBuat"
                    type="button"
                    class="btn-primary"
                    @click="bukaTambah"
                >
                    <span class="material-symbols-outlined ms-icon text-[20px]">
                        person_add
                    </span>
                    Tambah User
                </button>
            </div>
        </div>

        <DataState
            :loading="memuat"
            :error="error"
            :is-empty="false"
            @retry="muat"
        >
            <template v-if="users">
                <!-- Tabel user -->
                <section class="mt-6 pf-card overflow-hidden">
                    <div class="p-4 border-b border-slate-100">
                        <form
                            class="flex flex-col sm:flex-row gap-3"
                            @submit.prevent="cari"
                        >
                            <div class="flex-1">
                                <label class="sr-only" for="q">Cari</label>
                                <input
                                    id="q"
                                    v-model="q"
                                    type="search"
                                    placeholder="Cari nama / email..."
                                    class="form-input"
                                />
                            </div>

                            <button type="submit" class="btn-secondary text-sm">
                                Cari
                            </button>

                            <button
                                v-if="q"
                                type="button"
                                class="btn-secondary text-sm"
                                @click="resetCari"
                            >
                                Reset
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider"
                                    >
                                        Nama
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider"
                                    >
                                        Email
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider"
                                    >
                                        Role
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider"
                                    >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-slate-100">
                                <tr
                                    v-for="u in daftar"
                                    :key="u.id"
                                    class="hover:bg-slate-50/50 transition-colors"
                                >
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900">
                                            {{ u.nama }}
                                            <span
                                                v-if="u.id === auth.user?.id"
                                                class="ml-1 text-[10px] font-medium text-slate-400"
                                            >
                                                (Anda)
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            ID: {{ u.id }}
                                            <span v-if="u.created_at" class="ml-2">
                                                &middot; {{ formatSejak(u.created_at) }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ u.email }}
                                    </td>

                                    <!-- Role: dropdown bila boleh diubah, teks bila tidak -->
                                    <td class="px-4 py-3">
                                        <div
                                            v-if="bolehUbahRole(u)"
                                            class="flex items-center gap-2"
                                        >
                                            <select
                                                v-model="pilihanRole[u.id]"
                                                :class="[
                                                    'rounded-lg border-slate-300 text-sm py-1.5',
                                                    berubahRole(u)
                                                        ? 'border-amber-300 bg-amber-50'
                                                        : '',
                                                ]"
                                                @focus="ensurePilihan(u)"
                                                @change="simpanRole(u)"
                                            >
                                                <!-- Baris owner sendiri: role owner
                                                     tidak bisa dipilih, tapi tetap
                                                     ditampilkan sebagai nilai saat ini. -->
                                                <option
                                                    v-if="u.role?.slug === 'owner'"
                                                    value="owner"
                                                >
                                                    {{ u.role.nama }}
                                                </option>

                                                <option
                                                    v-for="[slug, nama] in opsiRole"
                                                    :key="slug"
                                                    :value="slug"
                                                >
                                                    {{ nama }}
                                                </option>
                                            </select>

                                            <span
                                                v-if="sedangSimpanRole === u.id"
                                                class="material-symbols-outlined ms-icon text-[16px] animate-spin text-slate-400"
                                            >
                                                progress_activity
                                            </span>
                                        </div>

                                        <span
                                            v-else
                                            class="inline-flex items-center text-sm font-medium text-slate-700"
                                        >
                                            {{ u.role?.nama || '-' }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="u.is_active"
                                            class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                        >
                                            Aktif
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"
                                        >
                                            Nonaktif
                                        </span>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <button
                                                v-if="bolehUbah && (u.id !== auth.user?.id || auth.isOwner)"
                                                type="button"
                                                class="btn-secondary text-xs px-3 py-1.5"
                                                @click="bukaUbah(u)"
                                            >
                                                Edit
                                            </button>

                                            <button
                                                v-if="
                                                    bolehHapus &&
                                                    u.id !== auth.user?.id &&
                                                    u.role?.slug !== 'owner'
                                                "
                                                type="button"
                                                class="btn-danger text-xs px-3 py-1.5"
                                                @click="hapus(u)"
                                            >
                                                Hapus
                                            </button>

                                            <span
                                                v-else-if="u.role?.slug === 'owner'"
                                                class="text-[11px] text-amber-600"
                                            >
                                                Owner tidak bisa dihapus
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!daftar.length">
                                    <td
                                        colspan="5"
                                        class="px-4 py-10 text-center text-sm text-slate-500"
                                    >
                                        {{ q ? `Tidak ada user yang cocok dengan "${q}".` : 'Tidak ada user.' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="users.last_page > 1"
                        class="px-4 py-4 border-t border-slate-100"
                    >
                        <Pagination
                            :current-page="users.current_page"
                            :total="total"
                            :per-page="PER_PAGE"
                            @change="gantiHalaman"
                        />
                    </div>
                </section>

                <!-- Log aktivitas user -->
                <section class="mt-8 pf-card overflow-hidden">
                    <div
                        class="p-4 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
                    >
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">
                                Log Aktivitas User
                            </h2>
                            <p class="text-sm text-slate-500 mt-1">
                                Riwayat aksi manajemen akun.
                            </p>
                        </div>

                        <RouterLink
                            to="/activity?type=user"
                            class="text-sm font-semibold text-brand-600 hover:text-brand-700 transition-colors"
                        >
                            Lihat semua aktivitas user
                        </RouterLink>
                    </div>

                    <div v-if="userActivities.length" class="divide-y divide-slate-100">
                        <div
                            v-for="a in userActivities"
                            :key="a.id"
                            class="p-4 hover:bg-slate-50/50 transition-colors"
                        >
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                    :class="kelasAktivitas(a.color)"
                                >
                                    <span class="material-symbols-outlined ms-icon text-sm">
                                        {{ a.icon }}
                                    </span>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ a.title }}
                                        </p>
                                        <span class="text-xs text-slate-500 shrink-0">
                                            {{ formatSejak(a.created_at) }}
                                        </span>
                                    </div>

                                    <div class="text-sm text-slate-600 mt-2 space-y-2">
                                        <div
                                            v-for="(b, i) in barisAktivitas(a.description)"
                                            :key="i"
                                            class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
                                        >
                                            <span
                                                v-if="b.badge"
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider"
                                                :class="b.badge.cls"
                                            >
                                                {{ b.badge.label }}
                                            </span>

                                            <p class="text-sm text-slate-700">{{ b.teks }}</p>
                                        </div>
                                    </div>

                                    <p class="mt-2 text-xs text-slate-500">
                                        oleh {{ a.user || 'System' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-else class="p-4 text-sm text-slate-500">
                        Belum ada aktivitas user.
                    </p>
                </section>
            </template>
        </DataState>

        <!-- Dialog tambah / ubah -->
        <div
            v-if="dialog"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
            @click.self="tutupDialog"
        >
            <div
                class="bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto"
            >
                <div
                    class="flex items-center justify-between px-5 py-4 border-b border-slate-100 sticky top-0 bg-white rounded-t-xl"
                >
                    <h2 class="text-sm font-bold text-slate-900">
                        {{ dialog === 'tambah' ? 'Tambah User' : 'Edit User' }}
                    </h2>

                    <button
                        type="button"
                        class="p-1 rounded text-slate-400 hover:text-slate-700 transition-colors"
                        aria-label="Tutup"
                        @click="tutupDialog"
                    >
                        <span class="material-symbols-outlined ms-icon text-[18px]">close</span>
                    </button>
                </div>

                <form class="px-5 py-4 space-y-3.5" @submit.prevent="simpanUser">
                    <!-- Nama -->
                    <div>
                        <label for="uNama" class="form-label">
                            Nama <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="uNama"
                            v-model="form.nama"
                            type="text"
                            maxlength="100"
                            class="form-input"
                            :class="galatForm.nama ? 'form-input-error' : ''"
                        />
                        <p v-if="galatForm.nama" class="text-xs text-rose-600 mt-1">
                            {{ galatForm.nama }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="uEmail" class="form-label">
                            Email <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="uEmail"
                            v-model="form.email"
                            type="email"
                            maxlength="100"
                            class="form-input"
                            :class="galatForm.email ? 'form-input-error' : ''"
                        />
                        <p v-if="galatForm.email" class="text-xs text-rose-600 mt-1">
                            {{ galatForm.email }}
                        </p>
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="uRole" class="form-label">
                            Role <span class="text-rose-500">*</span>
                        </label>
                        <select
                            id="uRole"
                            v-model="form.role"
                            class="form-input"
                            :class="galatForm.role ? 'form-input-error' : ''"
                        >
                            <option value="">Pilih role...</option>

                            <!--
                                Role owner tidak ada di selectableRoles, jadi
                                kalau user yang sedang diedit adalah owner,
                                nilainya perlu ditampilkan sendiri. Tanpa ini
                                dropdown akan tampak kosong padahal formnya
                                berisi "owner".
                            -->
                            <option v-if="form.is_owner" value="owner">
                                {{ form.role_name || 'Owner' }}
                            </option>

                            <option
                                v-for="[slug, nama] in opsiRole"
                                :key="slug"
                                :value="slug"
                            >
                                {{ nama }}
                            </option>
                        </select>
                        <p v-if="galatForm.role" class="text-xs text-rose-600 mt-1">
                            {{ galatForm.role }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            Hanya role di bawah hierarki Anda yang muncul di sini.
                        </p>
                    </div>

                    <!-- Password -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label for="uPass" class="form-label">
                                Password
                                <span
                                    v-if="passwordWajib"
                                    class="text-rose-500"
                                >*</span>
                            </label>
                            <input
                                id="uPass"
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                                class="form-input"
                                :class="galatForm.password ? 'form-input-error' : ''"
                            />
                            <p
                                v-if="galatForm.password"
                                class="text-xs text-rose-600 mt-1"
                            >
                                {{ galatForm.password }}
                            </p>
                        </div>

                        <div>
                            <label for="uPass2" class="form-label">
                                Ulangi Password
                                <span v-if="passwordWajib" class="text-rose-500">*</span>
                            </label>
                            <input
                                id="uPass2"
                                v-model="form.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                class="form-input"
                            />
                            <p
                                v-if="galatForm.password_confirmation"
                                class="text-xs text-rose-600 mt-1"
                            >
                                {{ galatForm.password_confirmation }}
                            </p>
                        </div>
                    </div>

                    <p v-if="!passwordWajib" class="text-xs text-slate-400">
                        Kosongkan kedua kolom bila tidak ingin mengganti password.
                    </p>

                    <!-- Status -->
                    <label
                        class="flex items-center gap-2.5 cursor-pointer select-none"
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                        />
                        <span class="text-sm text-slate-700">Akun aktif</span>
                    </label>

                    <p
                        v-if="galatForm.is_active"
                        class="text-xs text-rose-600"
                    >
                        {{ galatForm.is_active }}
                    </p>

                    <div class="flex justify-end gap-2 pt-1">
                        <button
                            type="button"
                            class="btn-secondary text-xs"
                            :disabled="menyimpan"
                            @click="tutupDialog"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="btn-primary text-xs"
                            :disabled="menyimpan || !bisaSimpan"
                        >
                            <span
                                v-if="menyimpan"
                                class="material-symbols-outlined ms-icon text-[16px] animate-spin"
                            >
                                progress_activity
                            </span>
                            {{ menyimpan ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Dialog transfer kepemilikan -->
        <div
            v-if="transfer"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
            @click.self="tutupTransfer"
        >
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                <div class="px-6 py-5">
                    <div class="mb-5 flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center shrink-0"
                        >
                            <span class="material-symbols-outlined">swap_horiz</span>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-slate-900">
                                Transfer Kepemilikan
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Sistem wajib memiliki satu orang Owner.
                            </p>
                        </div>
                    </div>

                    <div
                        class="bg-slate-50 border border-slate-200 p-3 rounded-lg mb-4 text-sm text-slate-700"
                    >
                        Anda akan mengubah role Anda sendiri. Sebelum melepaskan
                        hak akses Owner, pilih user lain yang akan menerima
                        <strong>Hak Kepemilikan (Owner)</strong> yang baru.
                    </div>

                    <div class="mb-6">
                        <label for="ownerBaru" class="form-label">
                            Pilih Owner Penerus
                        </label>

                        <select
                            id="ownerBaru"
                            v-model="transfer.ownerBaru"
                            class="form-input"
                        >
                            <option :value="null">-- Pilih User --</option>
                            <option
                                v-for="k in kandidatOwner"
                                :key="k.id"
                                :value="k.id"
                            >
                                {{ k.nama }} ({{ k.email }})
                            </option>
                        </select>

                        <p
                            v-if="!kandidatOwner.length"
                            class="text-xs text-amber-600 mt-1"
                        >
                            Tidak ada user lain yang bisa dijadikan Owner dari
                            halaman ini. Tambahkan user baru lebih dulu.
                        </p>
                    </div>

                    <div
                        class="flex justify-end gap-2 pt-3 border-t border-slate-100"
                    >
                        <button
                            type="button"
                            class="btn-secondary text-xs"
                            :disabled="sedangSimpanRole > 0"
                            @click="tutupTransfer"
                        >
                            Batal
                        </button>

                        <button
                            type="button"
                            class="btn-primary text-xs"
                            :disabled="sedangSimpanRole > 0 || !transfer.ownerBaru"
                            @click="konfirmasiTransfer"
                        >
                            <span
                                v-if="sedangSimpanRole > 0"
                                class="material-symbols-outlined ms-icon text-[16px] animate-spin"
                            >
                                progress_activity
                            </span>
                            Konfirmasi Transfer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>