<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DataState from '@/components/DataState.vue'
import RoleTreeNode from '@/components/admin/RoleTreeNode.vue'

import { useAuthStore } from '@/stores/auth'
import { useConfirmStore } from '@/stores/confirm'
import { useToastStore } from '@/stores/toast'

import { api, readError } from '@/lib/api'

/*
 * ============================================================================
 *  ROLE & PERMISSION
 * ============================================================================
 *
 *  Meniru backend/resources/views/permissions/index.blade.php.
 *
 *  --- DUA ENDPOINT UNTUK DAFTAR ROLE --------------------------------------
 *
 *  GET /permissions mengembalikan segalanya sekaligus, termasuk daftar lengkap
 *  role beserta permission yang sedang tercentang. Tapi endpoint itu ikut
 *  menjalankan beberapa query pengelompokan yang tidak murah, dan menekan
 *  tab role shouldn't memanggilnya berulang-ulang. Karena itu perpindahan tab memakai
 *  GET /permissions/data?role=... yang jauh lebih ringan: satu role, satu
 *  daftar id permission. Pindah tab tidak pernah memuat ulang daftar role.
 *
 *  --- ROLE YANG SEDANG DIEDIT DIBAWA DI URL -------------------------------
 *
 *  Tab role disimpan sebagai ?role=slug. Alasannya sama seperti halaman lain
 *  yang memakai query string: operator sering membagikan tautan
 *  "permission untuk role Finance" ke rekan kerja, dan tautan itu harus
 *  membuka tampilan yang sama persis. Efek sampingnya, tombol maju dan
 *  mundur peramban ikut bekerja tanpa handler tambahan.
 *
 *  --- POHON HIERARKI SELALU MEMBACA DAFTAR TUNGGAL ------------------------
 *
 *  allRoles datang sebagai daftar datar. Pohon yang ditampilkan
 *  diturunkan dari situ, dan `petaRole` dipakai sebagai satu-satunya sumber
 *  kebenaran. Kalau ada satu tempat lagi yang menyimpan bentuk pohon selain
 *  daftar datar itu, keduanya pasti akan menyimpang begitu ada role yang
 *  digeser.
 *
 *  --- ATURAN PRASYARAT "LIHAT" ---------------------------------------------
 *
 *  Menghapus satu izin create/edit/delete tanpa izin view akan membuat menu
 *  menampilkan tombol yang pasti berakhir 403. Aturannya diterapkan dua kali:
 *  di sini supaya centangnya langsung terasa, dan lagi di backend supaya
 *  tidak bisa dilewati dengan permintaan buatan. Bentuk aturannyalooked
 *  otomatis -- setiap permission bernama "<resource>.<aksi>" diperlakukan
 *  tanpa daftar per modul, jadi modul baru ikut terlindungi.
 *
 *  --- WARNA ROLE -----------------------------------------------------------
 *
 *  Warna disimpan terpisah dari permission, lewat PATCH .../color, dan hanya
 *  owner yang boleh mengubahnya. Dipanggil saat input warna selesai
 *  disentuh, bukan saat diketik: kalau dikirim per ketikan, satu gerakan
 *  pen_cursor sudah menghasilkan puluhan request.
 */

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const confirm = useConfirmStore()
const toast = useToastStore()

const memuat = ref(true)
const error = ref('')

/* Daftar datar role. Targetnya bukan server: daftar inilah yang diubah
 * optimistis saat ada role yang digeser, lalu disinkronkan ulang dari server
 * setelah reorder berhasil. */
const daftarRole = ref([])

/* Tab role: role yang boleh diedit permission-nya. Tidak memuat owner. */
const tabRole = ref([])

/* Grup permission, sudah diurutkan sesuai urutan yang dikehendaki backend. */
const groups = ref([])

/* Id permission yang boleh diberikan oleh actor. Berlaku untuk semua role,
 * jadi tidak ikut berubah saat tab berpindah. */
const bolehDiberi = ref([])

/* Bentuk akhir: hanya owner. Menyatkannya di canCreate membuat semua
 * pemeriksaan di halaman ini cukup satu variabel. */
const canCreate = ref(false)

/* -------------------------------------------------------------------------
 *  ROLE YANG SEDANG DIEDIT
 * ---------------------------------------------------------------------- */

const slugAktif = ref('')
const namaAktif = ref('')
const namaParentAktif = ref('Root')
const warnaAktif = ref('#2563eb')
const tercentang = ref([])

/* Dipakai saat tab berpindah supaya editor kelihatan nonaktif. Di Versi
 * Blade ini menambah kelas is-loading pada form. */
const editorMemuat = ref(false)

/* -------------------------------------------------------------------------
 *  PENCARIAN ROLE
 * ---------------------------------------------------------------------- */

const petaRole = computed(() => {
    const peta = new Map()

    daftarRole.value.forEach((r) => peta.set(r.slug, r))

    return peta
})

const rootRole = computed(() => daftarRole.value.filter((r) => !r.parent_slug))

/**
 * Anak langsung dari sebuah role, terurut sort_order.
 *
 * Dipakai juga oleh RoleTreeNode untuk menggambar tingkat berikutnya, lewat
 * prop `anakDari`.
 */
function anakDari(role) {
    return daftarRole.value
        .filter((r) => r.parent_slug === role.slug)
        .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
}

function namaDari(slug) {
    if (!slug) return 'Root'

    return petaRole.value.get(slug)?.nama || slug
}

/* Urutan tampilan pohon, dari akar ke daun. Backend memakai indeks larik
 * sebagai sort_order yang baru, jadi urutan pengiriman harus sama dengan
 * urutan yang dilihat operator -- bukan urutan alfabetis. */
function urutanTampil() {
    const hasil = []
    const sudah = new Set()

    function turun(slug) {
        if (sudah.has(slug)) return

        sudah.add(slug)
        hasil.push(slug)

        anakDari(petaRole.value.get(slug) || { slug }).forEach((child) =>
            turun(child.slug)
        )
    }

    rootRole.value.forEach((r) => turun(r.slug))

    /* Role yang parent-nya menunjuk slug yang tidak ada tetap ikut dikirim.
     * Backend menolak setiap role selain owner yang parent-nya kosong, jadi
     * DON'T sampai ada yang tertinggal dari daftar. */
    daftarRole.value.forEach((r) => {
        if (!sudah.has(r.slug)) hasil.push(r.slug)
    })

    return hasil
}

/* -------------------------------------------------------------------------
 *  MUAT
 * ---------------------------------------------------------------------- */

async function muat() {
    memuat.value = true
    error.value = ''

    try {
        const { data } = await api.get('/permissions', {
            params: { role: route.query.role || undefined },
        })

        daftarRole.value = data.allRoles || []
        tabRole.value = data.roles || []
        groups.value = data.permissionGroups || []
        bolehDiberi.value = data.assignable || []
        canCreate.value = Boolean(data.canCreate)

        terapkanRoleAktif(data.selectedRole, data.assigned)
    } catch (e) {
        error.value = readError(e).message
    } finally {
        memuat.value = false
    }
}

/**
 * Menaruh hasil load ke dalam state editor.
 *
 * Dipakai oleh dua jalur: muat() untuk render pertama, dan pemindah tab untuk
 * GET /permissions/data. Bentuk datanya sedikit berbeda -- selectedRole dari
 * index() adalah model Role mentah tanpa relasi parent, sedangkan data()
 * merapikannya jadi { slug, nama, parent, warna }. Yang dipakai di sini
 * sengaja field yang ada di keduanya, dan nama parent dicari sendiri lewat
 * petaRole supaya hasilnya sama persis di kedua jalur.
 */
function terapkanRoleAktif(role, assigned) {
    if (!role) return

    slugAktif.value = role.slug
    namaAktif.value = role.nama
    namaParentAktif.value = namaDari(role.parent_slug)
    warnaAktif.value = role.warna || '#2563eb'
    tercentang.value = [...(assigned || [])]
    asli.value = [...(assigned || [])]

    /* Samakan URL dengan role yang benar-benar terpasang. Kalau ?role= menunjuk
     * slug yang tidak dikenal actor, backend diam-diam memakai role pertama. */
    if (route.query.role !== role.slug) {
        router.replace({ query: { role: role.slug } })
    }
}

/* Controller untuk membatalkan permintaan tab yang belum selesai. Tanpa ini,
 * menekan tab dengan cepat bisa membuat jawaban yang lebih lambat menimpa
 * jawaban yang lebih cepat -- tab yang ditampilkan jadi bukan yang diklik
 * terakhir. */
let controllerTab = null
let nomorTab = 0

async function pilihTab(slug) {
    if (slug === slugAktif.value || memuat.value) return

    controllerTab?.abort()
    controllerTab = new AbortController()

    const nomor = ++nomorTab

    editorMemuat.value = true

    try {
        const { data } = await api.get('/permissions/data', {
            params: { role: slug },
            signal: controllerTab.signal,
        })

        /* Abort tidak selalu menggagalkan promise axios di versi lama, jadi
         * penandanya tetap diperiksa. */
        if (nomor !== nomorTab) return

        terapkanRoleAktif(
            {
                slug: data.role.slug,
                nama: data.role.nama,
                parent_slug: data.role.parent,
                warna: data.role.warna,
            },
            data.assigned
        )

        /* Nama parent dari endpoint data sudah berupa teks, jadi jangan
         * menimpanya dengan pencarian peta. */
        namaParentAktif.value = data.role.parent || 'Root'
    } catch (e) {
        if (e?.code === 'ERR_CANCELED' || e?.name === 'CanceledError' || e?.name === 'AbortError') {
            return
        }

        toast.error(readError(e).message)
    } finally {
        if (nomor === nomorTab) editorMemuat.value = false
    }
}

/* Tab yang dibuka lewat tombol maju/mundur peramban. */
watch(
    () => route.query.role,
    (slug) => {
        if (slug && slug !== slugAktif.value) pilihTab(String(slug))
    }
)

onMounted(muat)

/* -------------------------------------------------------------------------
 *  KOTAK CENTANG PERMISSION
 * ---------------------------------------------------------------------- */

/* Permission Manager tidak punya view, jadi tidak ada kotak view-nya. */
function aksiPermission(nama) {
    const titik = nama.lastIndexOf('.')

    return titik === -1 ? null : nama.slice(titik + 1)
}

function bolehDipasang(id) {
    return bolehDiberi.value.includes(id)
}

function tercentangId(id) {
    return tercentang.value.includes(id)
}

/* Snapshot centangan tepat setelah role dipasang. Dipakai dua hal: mengaktifkan
 * tombol Simpan, dan menyimpan nilai yang dikembalikan tombol Batalkan.
 * Karena snapshot diambil saat role terpasang, bukan saat checkbox berubah,
 * `sudahBerubah` selalu berarti ada perbedaan terhadap apa yang ada di
 * server -- bukan terhadap apa yang terlihat beberapa ketikan lalu. */
const asli = ref([])

const sudahBerubah = computed(() => beda(tercentang.value, asli.value))

/* Membandingkan dua daftar id tanpa kehilangan urutannya. Panjang harus sama
 * dulu; kalau sudah sama, satu id yang berbeda sudah cukup untuk memastikan
 * isinya beda. */
function beda(a, b) {
    if (!a || !b) return false
    if (a.length !== b.length) return true

    const set = new Set(b)

    return a.some((id) => !set.has(id))
}

/**
 * Memindahkan centang mengikuti aturan prasyarat view.
 *
 * Dipanggil setiap kali satu kotak berubah, baik oleh klik biasa maupun oleh
 *Checkbox lain yang baru saja kita ubah -- jadi harus idempoten.
 */
function ubahCentang(item) {
    const aksi = aksiPermission(item.nama_permission)

    if (!aksi) return

    const viewId = groups.value
        .flatMap((g) => g.items)
        .find((p) => aksiPermission(p.nama_permission) === 'view' && resource(p) === resource(item))
        ?.id

    if (!viewId) return

    const tercentangSekarang = new Set(tercentang.value)

    if (aksi === 'view') {
        /* Melepas "lihat"means melepas create/edit/delete di resource yang sama
         * -- selama kotak itu boleh dipasang. */
        if (!tercentangSekarang.has(viewId)) {
            groups.value
                .flatMap((g) => g.items)
                .forEach((p) => {
                    if (
                        resource(p) === resource(item) &&
                        aksiPermission(p.nama_permission) &&
                        aksiPermission(p.nama_permission) !== 'view' &&
                        bolehDipasang(p.id)
                    ) {
                        tercentangSekarang.delete(p.id)
                    }
                })
        }
    } else if (tercentangSekarang.has(item.id) && !tercentangSekarang.has(viewId)) {
        /* Mencentang create/edit/delete otomatis ikut centang "lihat". */
        if (bolehDipasang(viewId)) tercentangSekarang.add(viewId)
    }

    tercentang.value = [...tercentangSekarang]
}

function resource(permission) {
    const nama = permission.nama_permission
    const titik = nama.lastIndexOf('.')

    return titik === -1 ? nama : nama.slice(0, titik)
}

const kelompokTerbuka = ref({})

function bukaTutupGrup(nama) {
    kelompokTerbuka.value = { ...kelompokTerbuka.value, [nama]: !kelompokTerbuka.value[nama] }
}

/* -------------------------------------------------------------------------
 *  SIMPAN PERMISSION
 * ---------------------------------------------------------------------- */

const menyimpan = ref(false)

/* Kembalikan centangan ke snapshot sebelum role terakhir dipasang. Server
 * belum pernah melihat perubahan ini, jadi membatalkannya tidak perlu
 * request apa pun. */
function batalkanPerubahan() {
    if (menyimpan.value) return

    tercentang.value = [...asli.value]
}

async function simpanPermission() {
    if (menyimpan.value || !slugAktif.value) return

    menyimpan.value = true

    try {
        const body = { role: slugAktif.value, permissions: tercentang.value }

        /* Warna hanya diproses backend kalau pengirimnya owner. Mengirimnya
         * dari actor lain tidak merusak apa pun, tapi Diam saja sedikit
         * lebih jelas soal siapa yang berwenang. */
        if (canCreate.value) body.warna = warnaAktif.value

        const { data } = await api.put('/permissions', body)

        toast.success(data.message || 'Permission role berhasil diperbarui.')

        /* Backend boleh menambah atau membuang permission sendiri karena aturan
         * prasyarat view. Yang dikembalikan adalah keadaan yang benar-benar
         * tersimpan, jadi snapshot ikut mengambilnya -- kalau tidak, tombol
         * Simpan akan tetap aktif padahal sudah sama dengan server. */
        const tersimpan = data.assigned || tercentang.value

        tercentang.value = [...tersimpan]
        asli.value = [...tersimpan]

        /* Backend bisa menambah atau membuang permission sendiri karena aturan
         * prasyarat view. Terapkan hasilnya supaya checkbox di layar sama
         * dengan yang benar-benar tersimpan. */
        if (data.role !== slugAktif.value) slugAktif.value = data.role
    } catch (e) {
        toast.error(readError(e).message)
    } finally {
        menyimpan.value = false
    }
}

/* -------------------------------------------------------------------------
 *  WARNA ROLE
 * ---------------------------------------------------------------------- */

const warnaHex = ref('#2563eb')
const menyimpanWarna = ref(false)
const warnaTersimpan = ref(false)

watch(warnaAktif, (nilai) => {
    warnaHex.value = String(nilai || '#2563eb').toUpperCase()
})

const hexValid = computed(() => /^#[0-9A-Fa-f]{6}$/.test(warnaHex.value.trim()))

/* Dua input warna (pemilih native dan teks hex) disatukan di satu state, lalu
 * dikirim saat yang mana pun selesai disentuh -- bukan saat diketik. */
function ubahWarnaDariPemilih(event) {
    warnaAktif.value = event.target.value
}

function ubahWarnaDariTeks(event) {
    const nilai = event.target.value.trim()

    warnaHex.value = nilai.toUpperCase()

    if (/^#[0-9a-f]{6}$/i.test(nilai)) warnaAktif.value = nilai
}

async function simpanWarna() {
    if (!canCreate.value || menyimpanWarna.value) return

    if (!hexValid.value) return

    menyimpanWarna.value = true
    warnaTersimpan.value = false

    try {
        const { data } = await api.patch(`/permissions/roles/${slugAktif.value}/color`, {
            warna: warnaHex.value,
        })

        warnaAktif.value = data.warna || warnaAktif.value
        warnaTersimpan.value = true

        setTimeout(() => {
            warnaTersimpan.value = false
        }, 1800)
    } catch (e) {
        toast.error(readError(e).message)
    } finally {
        menyimpanWarna.value = false
    }
}

/* -------------------------------------------------------------------------
 *  SERET-LEPAS HIERARKI
 * ---------------------------------------------------------------------- */

const sedangDiseret = ref('')
const sedangDituju = ref('')

function mulaiSeret(slug) {
    sedangDiseret.value = slug
    sedangDituju.value = ''
}

function selesaiSeret() {
    sedangDiseret.value = ''
    sedangDituju.value = ''
}

function lewatSeret(slug) {
    if (!sedangDiseret.value || slug === sedangDiseret.value) return

    sedangDituju.value = slug
}

/* Apakah `kandidat` berada di bawah `ancestor` di pohon saat ini. Dipakai
 * untuk menolak menjatuhkan role ke dalam cabangnya sendiri, yang akan
 * membentuk siklus. */
function adalahDescendant(kandidat, ancestor) {
    let parent = petaRole.value.get(kandidat)?.parent_slug
    const dilihat = new Set()

    while (parent && !dilihat.has(parent)) {
        if (parent === ancestor) return true

        dilihat.add(parent)
        parent = petaRole.value.get(parent)?.parent_slug
    }

    return false
}

const sedangMenyusun = ref(false)

async function jatuh(slugTujuan) {
    const asal = sedangDiseret.value

    sedangDituju.value = ''

    if (!asal || asal === slugTujuan) return
    if (sedangMenyusun.value) return

    /* Menjatuhkan role ke dalam subtree-nya sendiri membuat siklus, dan
     * backend akan menolak. Ditolak lebih awal supaya pohon tidak sempat
     * tampil rusak. */
    if (adalahDescendant(slugTujuan, asal)) {
        toast.error('Role tidak bisa dipindahkan ke dalam cabangnya sendiri.')
        selesaiSeret()

        return
    }

    const roleAsal = petaRole.value.get(asal)
    const roleTujuan = petaRole.value.get(slugTujuan)

    if (!roleAsal || !roleTujuan) {
        selesaiSeret()

        return
    }

    /* Kalau yang ditunjuk adalah anak langsung dari role yang diseret,
     * keduanya ditukar posisinya, bukan simply dipindahkan. */
    const tukarAnakLangsung = roleTujuan.parent_slug === asal

    const parentAsli = roleAsal.parent_slug

    /* Optimis dulu supaya pohon langsung bergerak mengikuti kursor, baru
     * dikirim ke server. Kalau ditolak, muat() di akhir mengembalikan tudo. */
    daftarRole.value = daftarRole.value.map((r) => {
        if (r.slug === asal) return { ...r, parent_slug: slugTujuan }
        if (tukarAnakLangsung && r.slug === slugTujuan) return { ...r, parent_slug: parentAsli }

        return r
    })

    sedangMenyusun.value = true

    /*
     * Dua bentuk permintaan, dipilih sesuai jenis gerakannya.
     *
     * Saat `swap` ada, backend berhenti sebelum memproses daftar `roles` --
     * ia hanya menukar parent keduanya. Jadi tidak ada gunanya mengirim
     * `roles` juga; kita biarkan sort_order ditentukan oleh muat() di akhir.
     *
     * Saat bukan tukar, `roles` justru satu-satunya yang diproses, dan
     * indeks lariknya menjadi sort_order yang baru. Karena itu urutannya harus
     * urutan tampilan pohon, bukan urutan alfabetis.
     */
    const body = tukarAnakLangsung
        ? { swap: [asal, slugTujuan] }
        : {
              roles: urutanTampil().map((slug) => ({
                  slug,
                  parent_slug: petaRole.value.get(slug)?.parent_slug || null,
              })),
          }

    try {
        await api.post('/permissions/reorder', body)

        toast.success('Struktur role diperbarui.')
    } catch (e) {
        toast.error(readError(e).message)
    } finally {
        sedangMenyusun.value = false
        selesaiSeret()

        /* sort_order di server berubah mengikuti urutan yang baru, jadi
         * daftar ditajaarui penuh. selectedRole ikut terambil dari sini,
         * jadi tab yang aktif tidak berubah. */
        muat()
    }
}

/* -------------------------------------------------------------------------
 *  DIALOG ROLE
 * ---------------------------------------------------------------------- */

const dialog = ref(null)
const menyimpanRole = ref(false)
const galatRole = ref({})

const WARNA_AWAL = '#8b5cf6'

function formRole() {
    return { slug: '', nama: '', parent_slug: 'owner', warna: WARNA_AWAL }
}

const form = ref(formRole())
const formHex = ref(WARNA_AWAL.toUpperCase())

const formHexValid = computed(() => /^#[0-9A-Fa-f]{6}$/.test(formHex.value.trim()))

/* Role yang tidak boleh jadi parent saat mengubah sebuah role: dirinya
 * sendiri, dan semua keturunannya. Memilih salah satunya akan membuat
 * hierarki membentuk siklus, dan backend menolak dengan pesan validasi. */
const opsiParent = computed(() => {
    if (!form.value.slug) {
        return daftarRole.value.map((r) => ({ slug: r.slug, nama: r.nama }))
    }

    return daftarRole.value
        .filter((r) => r.slug !== form.value.slug && !adalahDescendant(r.slug, form.value.slug))
        .map((r) => ({ slug: r.slug, nama: r.nama }))
})

const formRoleBisaSimpan = computed(
    () => form.value.nama.trim().length > 0 && Boolean(form.value.parent_slug) && formHexValid.value
)

function bukaBuatRole() {
    form.value = formRole()
    formHex.value = WARNA_AWAL.toUpperCase()
    galatRole.value = {}
    dialog.value = 'buat'
}

function bukaUbahRole(role) {
    form.value = {
        slug: role.slug,
        nama: role.nama,
        parent_slug: role.parent_slug || 'owner',
        warna: role.warna || WARNA_AWAL,
    }
    formHex.value = String(role.warna || WARNA_AWAL).toUpperCase()
    galatRole.value = {}
    dialog.value = 'ubah'
}

function tutupDialogRole() {
    if (menyimpanRole.value) return

    dialog.value = null
    galatRole.value = {}
}

function ubahWarnaRoleDariPemilih(event) {
    form.value = { ...form.value, warna: event.target.value }
    formHex.value = event.target.value.toUpperCase()
}

function ubahWarnaRoleDariTeks(event) {
    const nilai = event.target.value.trim()

    formHex.value = nilai.toUpperCase()

    if (/^#[0-9a-f]{6}$/i.test(nilai)) form.value = { ...form.value, warna: nilai }
}

async function simpanRole() {
    if (menyimpanRole.value || !formRoleBisaSimpan.value) return

    menyimpanRole.value = true
    galatRole.value = {}

    const body = {
        nama: form.value.nama.trim(),
        parent_slug: form.value.parent_slug,
        warna: form.value.warna,
    }

    try {
        if (form.value.slug) {
            await api.patch(`/permissions/roles/${form.value.slug}`, body)
        } else {
            await api.post('/permissions/roles', body)
        }

        dialog.value = null
        toast.success(form.value.slug ? 'Role berhasil diperbarui.' : 'Role baru berhasil dibuat.')

        await muat()
    } catch (e) {
        const errors = e?.response?.data?.errors

        if (errors && Object.keys(errors).length) {
            galatRole.value = errors
        } else {
            toast.error(readError(e).message)
        }
    } finally {
        menyimpanRole.value = false
    }
}

/* -------------------------------------------------------------------------
 *  HAPUS ROLE
 * ---------------------------------------------------------------------- */

async function hapusRole(role) {
    const ok = await confirm.ask(`Hapus role "${role.nama}"?`, {
        confirmLabel: 'Ya, hapus',
        tone: 'danger',
    })

    if (!ok) return

    try {
        const { data } = await api.delete(`/permissions/roles/${role.slug}`)

        toast.success(data.message || 'Role berhasil dihapus.')
        muat()
    } catch (e) {
        /* 422 di sini berarti masih ada user memakai role tersebut. */
        toast.error(readError(e).message)
    }
}

/* -------------------------------------------------------------------------
 *  KETERSIMPULAN YANG BENER-BENER DIPAKAI
 * ---------------------------------------------------------------------- */

const bolehAturRole = computed(() => canCreate.value && auth.isOwner)
</script>

<template>
    <div class="max-w-[1400px] mx-auto px-4 md:px-8">
        <!-- Kepala -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Role &amp; Permissions</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Susun hierarki role, lalu atur permission tiap role.
                </p>
            </div>

            <button
                v-if="bolehAturRole"
                type="button"
                class="btn-primary self-start"
                @click="bukaBuatRole"
            >
                <span class="material-symbols-outlined ms-icon text-[18px]">add</span>
                Role Baru
            </button>
        </div>

        <DataState :loading="memuat" :error="error" :is-empty="false" @retry="muat">
            <!-- Tab role -->
            <div
                class="sticky top-16 z-30 mt-5 rounded-xl border border-slate-200 bg-white/95 p-2 shadow-sm backdrop-blur"
            >
                <div class="flex items-center gap-2 overflow-x-auto">
                    <span
                        class="shrink-0 px-2 text-xs font-bold uppercase tracking-wide text-slate-400"
                    >
                        Role
                    </span>

                    <button
                        v-for="r in tabRole"
                        :key="r.slug"
                        type="button"
                        class="role-link shrink-0 rounded-lg border px-3 py-2 text-sm font-semibold transition-colors"
                        :class="
                            slugAktif === r.slug
                                ? 'border-brand-300 bg-brand-50 text-brand-700'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-brand-200 hover:text-brand-700'
                        "
                        @click="pilihTab(r.slug)"
                    >
                        {{ r.nama }}
                    </button>
                </div>
            </div>

            <div class="mt-6 space-y-6">
                <!-- Pohon hierarki -->
                <section class="pf-card overflow-hidden">
                    <div class="border-b border-slate-200 p-4">
                        <h2 class="font-bold text-slate-900">Pohon Hierarki Role</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Tarik role ke role lain untuk menjadikannya child.
                        </p>
                    </div>

                    <div class="role-genealogy">
                        <div class="role-tree-root">
                            <RoleTreeNode
                                v-for="r in rootRole"
                                :key="r.slug"
                                :role="r"
                                :anak="anakDari(r)"
                                :anak-dari="anakDari"
                                :nama-parent="'Root'"
                                :slug-aktif="slugAktif"
                                :can-create="bolehAturRole"
                                :sedang-diseret="sedangDiseret"
                                :sedang-dituju="sedangDituju"
                                @pilih="pilihTab"
                                @ubah="bukaUbahRole"
                                @hapus="hapusRole"
                                @seret-mulai="mulaiSeret"
                                @seret-selesai="selesaiSeret"
                                @seret-lewat="lewatSeret"
                                @jatuh="jatuh"
                            />
                        </div>
                    </div>
                </section>

                <!-- Editor permission -->
                <section
                    class="pf-card overflow-hidden transition-opacity duration-200"
                    :class="editorMemuat ? 'opacity-45 pointer-events-none' : ''"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b border-slate-200 p-4"
                    >
                        <div>
                            <h2 class="font-bold text-slate-900">{{ namaAktif }}</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Permission berlaku untuk semua user dengan role
                                ini.
                            </p>
                        </div>

                        <span
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 shrink-0"
                        >
                            Parent {{ namaParentAktif }}
                        </span>
                    </div>

                    <!-- Warna role -->
                    <div
                        v-if="bolehAturRole"
                        class="flex flex-wrap items-center gap-3 border-b border-slate-200 px-4 py-3"
                    >
                        <label for="warna" class="text-sm font-semibold text-slate-700">
                            Warna outline role
                        </label>

                        <input
                            id="warna"
                            type="color"
                            class="h-9 w-14 cursor-pointer rounded border border-slate-300 bg-white p-1"
                            :value="warnaAktif"
                            @input="ubahWarnaDariPemilih"
                            @change="simpanWarna"
                        />

                        <input
                            v-model="warnaHex"
                            type="text"
                            maxlength="7"
                            class="form-input w-28 py-2 font-mono uppercase"
                            :class="hexValid ? '' : 'form-input-error'"
                            aria-label="Kode warna HEX"
                            @change="simpanWarna"
                        />

                        <span class="text-xs text-slate-500">HEX, contoh #2563EB</span>

                        <span
                            v-if="warnaTersimpan"
                            class="text-xs font-semibold text-emerald-600"
                        >
                            Tersimpan
                        </span>
                    </div>

                    <!-- Grup permission -->
                    <div class="space-y-4 p-4">
                        <section
                            v-for="g in groups"
                            :key="g.name"
                            class="overflow-hidden rounded-xl border border-slate-200"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 bg-slate-50 px-4 py-3 text-left"
                                :aria-expanded="kelompokTerbuka[g.name] ? 'true' : 'false'"
                                @click="bukaTutupGrup(g.name)"
                            >
                                <span
                                    class="material-symbols-outlined ms-icon text-slate-400 transition-transform duration-200"
                                    :class="kelompokTerbuka[g.name] ? 'rotate-90' : ''"
                                >
                                    chevron_right
                                </span>

                                <span
                                    class="material-symbols-outlined ms-icon text-brand-600"
                                >
                                    {{ g.icon }}
                                </span>

                                <span class="text-sm font-bold text-slate-800">
                                    {{ g.name }}
                                </span>

                                <span class="ms-auto text-xs text-slate-500">
                                    {{ g.items.length }} aksi
                                </span>
                            </button>

                            <!--
                                Lipat-t unfolds lewat grid-template-rows, bukan
                                v-if. Baris grid yang dikecilkan ke 0fr hanya
                                memampit kalau anaknya punya min-height: 0 dan
                                overflow: hidden -- itu sebabnya ada dua
                                pembungkus di sini: yang luar mengatur tinggi,
                                yang dalam memotong isinya.
                            -->
                            <div
                                class="grid transition-[grid-template-rows] duration-300"
                                :class="
                                    kelompokTerbuka[g.name]
                                        ? 'grid-rows-[1fr]'
                                        : 'grid-rows-[0fr]'
                                "
                            >
                                <div class="min-h-0 overflow-hidden">
                                    <div
                                        class="grid gap-2 p-3 sm:grid-cols-2 lg:grid-cols-4"
                                    >
                                        <label
                                            v-for="p in g.items"
                                            :key="p.id"
                                            class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-3"
                                            :class="
                                                bolehDipasang(p.id)
                                                    ? 'hover:border-brand-300 hover:bg-brand-50/40'
                                                    : 'bg-slate-50 opacity-60'
                                            "
                                        >
                                            <input
                                                type="checkbox"
                                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                                :checked="tercentangId(p.id)"
                                                :disabled="!bolehDipasang(p.id)"
                                                @change="ubahCentang(p)"
                                            />

                                            <span class="text-sm font-semibold text-slate-700">
                                                {{
                                                    g.permissions[p.nama_permission] ||
                                                    p.nama_permission
                                                }}
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Simpan -->
                    <div
                        class="flex items-center justify-end gap-3 border-t border-slate-200 p-4"
                    >
                        <span
                            v-if="sudahBerubah"
                            class="text-xs font-medium text-amber-600"
                        >
                            Ada perubahan yang belum disimpan
                        </span>

                        <button
                            type="button"
                            class="btn-secondary text-xs"
                            :disabled="!sudahBerubah || menyimpan"
                            @click="batalkanPerubahan"
                        >
                            Batalkan
                        </button>

                        <button
                            type="button"
                            class="btn-primary text-xs"
                            :disabled="!sudahBerubah || menyimpan"
                            @click="simpanPermission"
                        >
                            <span
                                v-if="menyimpan"
                                class="material-symbols-outlined ms-icon text-[16px] animate-spin"
                            >
                                progress_activity
                            </span>
                            {{ menyimpan ? 'Menyimpan...' : 'Simpan Permission' }}
                        </button>
                    </div>
                </section>
            </div>
        </DataState>

        <!-- Dialog buat / ubah role -->
        <div
            v-if="dialog"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
            @click.self="tutupDialogRole"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
                <h2 class="text-lg font-bold text-slate-900">
                    {{ dialog === 'buat' ? 'Buat Role Baru' : 'Edit Role' }}
                </h2>

                <form class="mt-4 space-y-3" @submit.prevent="simpanRole">
                    <!-- Nama -->
                    <div>
                        <label for="roleNama" class="form-label">Nama role</label>
                        <input
                            id="roleNama"
                            v-model="form.nama"
                            type="text"
                            maxlength="100"
                            class="form-input"
                            :class="galatRole.nama ? 'form-input-error' : ''"
                        />
                        <p
                            v-if="galatRole.nama"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ galatRole.nama }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Slug dibuat otomatis dari nama ini, jadi nama yang
                            dipakai sudah harus unik.
                        </p>
                    </div>

                    <!-- Parent -->
                    <div>
                        <label for="roleParent" class="form-label">
                            Parent
                        </label>
                        <select
                            id="roleParent"
                            v-model="form.parent_slug"
                            class="form-input"
                            :class="galatRole.parent_slug ? 'form-input-error' : ''"
                        >
                            <option
                                v-for="p in opsiParent"
                                :key="p.slug"
                                :value="p.slug"
                            >
                                Di bawah {{ p.nama }}
                            </option>
                        </select>
                        <p
                            v-if="galatRole.parent_slug"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ galatRole.parent_slug }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Role ini sendiri dan seluruh keturunannya tidak
                            muncul sebagai pilihan.
                        </p>
                    </div>

                    <!-- Warna -->
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <label
                                for="roleWarna"
                                class="text-sm font-semibold text-slate-700"
                            >
                                Warna
                            </label>

                            <input
                                id="roleWarna"
                                type="color"
                                class="h-9 w-14 cursor-pointer rounded border border-slate-300 bg-white p-1"
                                :value="form.warna"
                                @input="ubahWarnaRoleDariPemilih"
                            />

                            <input
                                v-model="formHex"
                                type="text"
                                maxlength="7"
                                class="form-input w-28 py-2 font-mono uppercase"
                                :class="formHexValid ? '' : 'form-input-error'"
                                aria-label="Kode warna HEX"
                                @input="ubahWarnaRoleDariTeks"
                            />
                        </div>

                        <p
                            v-if="galatRole.warna"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ galatRole.warna }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button
                            type="button"
                            class="btn-secondary text-xs"
                            :disabled="menyimpanRole"
                            @click="tutupDialogRole"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="btn-primary text-xs"
                            :disabled="menyimpanRole || !formRoleBisaSimpan"
                        >
                            <span
                                v-if="menyimpanRole"
                                class="material-symbols-outlined ms-icon text-[16px] animate-spin"
                            >
                                progress_activity
                            </span>
                            {{
                                menyimpanRole
                                    ? 'Menyimpan...'
                                    : dialog === 'buat'
                                      ? 'Buat Role'
                                      : 'Simpan Perubahan'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
