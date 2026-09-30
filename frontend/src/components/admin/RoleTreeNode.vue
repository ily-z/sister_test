<script setup>
import { computed } from 'vue'

/*
 * ============================================================================
 *  SIMULAN ROLE (SATU KARTU PADA POHON HIERARKI)
 * ============================================================================
 *
 *  Dipakai berulang-ulang oleh dirinya sendiri, jadi harus lewat nama -- bukan
 *  `<RoleTreeNode>` di dalam template, karena di situ hanya akan menghasilkan
 *  satu tingkat. Panggilan rekursifnya ada di blok bawah.
 *
 *  --- BENTUK POHONNY DIPBANGUN DI SISI PENGGUNA, BUKAN DI SERVER -----------
 *
 *  Endpoint /permissions mengembalikan `allRoles` sebagai daftar datar:
 *  [{ slug, nama, parent_slug, sort_order, warna, is_system }, ...].
 *  Versi Blade memakai include rekursif _role-tree.blade.php yang bercabang
 *  dengan $allRoles->where('parent_slug', $role->slug). Di sini filternya
 *  dibuat sekali di PermissionView dan diteruskan sebagai prop anakDari,
 *  supaya setiap kartu tidak menyaring ulang seluruh daftar.
 *
 *  --- WARNA ROLE DATANG DARI DATABASE --------------------------------------
 *
 *  Kolom roles.warna menyimpan kode hex dan bisa disunting owner lewat
 *  endpoint color. Nilainya dipakai sebagai --role-color di dalam gaya
 *  kartu, karena itu NodeList yang sama juga menyorot kartu sesuai warna itu.
 *  Warna tidak pernah dirakit dari nama warna Tailwind, jadi warna milik role
 *  bebas -- bukan terbatas pada palet.
 *
 *  --- MENARIK ROLE -------------------------------------------------------
 *
 *  Itemnya sendiri draggable="false"; yang bisa dipegang hanya ikon
 *  drag_indicator. Alasannya: tanpa itu, seluruh kartu ikut bisa diseret --
 *  termasuk teks di dalamnya, yang membuat pemilihan teks di dalam kartu jadi
 *  mustahil. Handle menentukan juga role mana yang boleh digerakkan: role
 *  sistem tidak punya handle sama sekali.
 *
 *  --- ATURAN PEMBERITAAN ---------------------------------------------------
 *
 * Setiap kartu memanggil emit() yang sama seperti kartunya sendiri, lalu
 * PermissionView yang memutuskan boleh atau tidak. Jadi komponen ini tidak
 * perlu tahu apa itu permission maupun apakah actor boleh mengubah role.
 */

const props = defineProps({
    /** Objek role: { slug, nama, parent_slug, warna, is_system }. */
    role: { type: Object, required: true },

    /** Anak-anak role ini, sudah difilter dan diurutkan oleh induknya. */
    anak: { type: Array, default: () => [] },

    /**
     * Fungsi pengembalian anak dari sebuah role.
     *
     * Dibuat di PermissionView dari daftar datar `allRoles`, lalu diteruskan
     * apa adanya ke setiap tingkat pohon. Dipakai untuk menggambar anak dari
     * kartu anak -- kalau tidak, kartu tingkat ketiga tidak akan tahu harus
     * mencari apa di dalam `anak` miliknya sendiri.
     */
    anakDari: { type: Function, required: true },

    /** Nama parent untuk ditampilkan pada baris kecil di dalam kartu. */
    namaParent: { type: String, default: 'Root' },

    /** Slug role yang sedang diedit permission-nya. */
    slugAktif: { type: String, default: '' },

    /** Hanya owner yang boleh menyusun ulang hierarki dan mengubah role. */
    canCreate: { type: Boolean, default: false },

    /** Slug role yang sedang diseret, untuk menandai sumbernya. */
    sedangDiseret: { type: String, default: '' },

    /** Slug role yang sedang ditunjuk sebagai tujuan jatuh. */
    sedangDituju: { type: String, default: '' },
})

const emit = defineEmits([
    'pilih',
    'ubah',
    'hapus',
    'seret-mulai',
    'seret-selesai',
    'seret-lewat',
    'jatuh',
])

/* Warna kartu. Nilai kosong dijernihkan ke biru brand supaya role yang belum
 * pernah diberi warna tetap terlihat, bukan transparan. */
const warnaKartu = computed(() => props.role.warna || '#2563eb')

/* Role sistem tidak bisa diedit maupun dihapus, dan owner tidak boleh
 * disentuh sama sekali. Keduanya juga tidak punya handle seret. */
const bisaDiatur = computed(
    () => props.canCreate && !props.role.is_system && props.role.slug !== 'owner'
)

const bisaDiseret = computed(
    () => props.canCreate && props.role.slug !== 'owner' && !props.role.is_system
)

/* Role yang sedang aktif ditandai dengan warna brand, bukan dengan kelas
 * tambahan, supaya tidak perlu melapisi gaya border yang sudah ada. */
const gayaKartu = computed(() => ({
    '--role-color': warnaKartu.value,
    borderColor: props.slugAktif === props.role.slug ? '#93c5fd' : undefined,
    backgroundColor: props.slugAktif === props.role.slug ? '#eff6ff' : undefined,
}))

function mulaiSeret(event) {
    /*
     * Firefox menolak memulai seret kalau dataTransfer kosong, jadi teks
     * slug ikut ditaruh di sana. Chrome dan Safari tidak membutuhkannya, tapi
     * tidak ada salahnya juga.
     */
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', props.role.slug)

    emit('seret-mulai', props.role.slug)
}

function lewatSeret(event) {
    if (!props.sedangDiseret) return

    /*
     * Mencegah peristiwa bawaan itu berarti "menerima" seret. Tanpa
     * preventDefault, elemen tidak akan pernah menjadi target dan event
     * drop tidak pernah sampai.
     */
    event.preventDefault()

    event.dataTransfer.dropEffect = 'move'

    emit('seret-lewat', props.role.slug)
}

function terimaJatuh(event) {
    event.preventDefault()
    event.stopPropagation()

    emit('jatuh', props.role.slug)
}
</script>

<template>
    <div class="role-node" :data-role-node="role.slug">
        <!-- Kartu role -->
        <div
            class="role-item"
            :class="{
                'is-dragging': sedangDiseret === role.slug,
                'is-drop-target': sedangDituju === role.slug && sedangDiseret !== role.slug,
            }"
            :style="gayaKartu"
            :data-role="role.slug"
            :data-parent="role.parent_slug || ''"
            @dragover="lewatSeret"
            @drop="terimaJatuh"
        >
            <!-- Handle seret, atau lencana untuk owner / role sistem -->
            <span
                v-if="bisaDiseret"
                class="material-symbols-outlined ms-icon role-drag-handle text-slate-400"
                draggable="true"
                title="Tarik untuk memindahkan role"
                @dragstart="mulaiSeret"
                @dragend="emit('seret-selesai')"
            >
                drag_indicator
            </span>

            <span
                v-else
                class="material-symbols-outlined ms-icon"
                :class="role.slug === 'owner' ? 'text-amber-500' : 'text-slate-300'"
            >
                {{ role.slug === 'owner' ? 'workspace_premium' : 'lock' }}
            </span>

            <!-- Nama + parent: diklik untuk pindah ke editor permission -->
            <button
                type="button"
                class="role-link min-w-0 flex-1 text-left"
                :aria-current="slugAktif === role.slug ? 'true' : undefined"
                @click="emit('pilih', role.slug)"
            >
                <span class="role-name block truncate">{{ role.nama }}</span>
                <span class="role-parent block truncate">Parent: {{ namaParent }}</span>
            </button>

            <!-- Aksi role -->
            <template v-if="bisaDiatur">
                <button
                    type="button"
                    class="material-symbols-outlined ms-icon text-[18px] text-slate-400 hover:text-brand-600 transition-colors"
                    title="Edit role"
                    @click="emit('ubah', role)"
                >
                    edit
                </button>

                <button
                    type="button"
                    class="material-symbols-outlined ms-icon text-[18px] text-slate-400 hover:text-rose-600 transition-colors"
                    title="Hapus role"
                    @click="emit('hapus', role)"
                >
                    delete
                </button>
            </template>
        </div>

        <!-- Anak-anak, digambar rekursif -->
        <div
            v-if="anak.length"
            class="role-children"
            :data-parent-container="role.slug"
        >
            <RoleTreeNode
                v-for="sibling in anak"
                :key="sibling.slug"
                :role="sibling"
                :anak="anakDari(sibling)"
                :nama-parent="role.nama"
                :slug-aktif="slugAktif"
                :can-create="canCreate"
                :sedang-diseret="sedangDiseret"
                :sedang-dituju="sedangDituju"
                @pilih="(slug) => emit('pilih', slug)"
                @ubah="(r) => emit('ubah', r)"
                @hapus="(r) => emit('hapus', r)"
                @seret-mulai="(slug) => emit('seret-mulai', slug)"
                @seret-selesai="emit('seret-selesai')"
                @seret-lewat="(slug) => emit('seret-lewat', slug)"
                @jatuh="(slug) => emit('jatuh', slug)"
            />
        </div>
    </div>
</template>

<style>
/*
 * Gaya pohon genealogi. Sengaja tidak di-`scoped`: aturan ini menyasar
 * kartu di dalam komponen anak (RoleTreeNode dipanggil dari dirinya
 * sendiri), dan `scoped` hanya menempelkan atributnya ke elemen milik
 * komponen ini dan turunannya satu tingkat.
 *
 * Semua aturan dibatasi di bawah .role-genealogy, yang hanya ada di halaman
 * ini, jadi tidak ada yang bocor ke halaman lain meski bloknya global.
 */
.role-genealogy {
    overflow: auto;
    min-height: 390px;
    padding: 3rem 3rem 3.5rem;
    background-color: #f8fafc;
    background-image: linear-gradient(#e2e8f0 1px, transparent 1px),
        linear-gradient(90deg, #e2e8f0 1px, transparent 1px);
    background-size: 24px 24px;
}

.role-genealogy > .role-tree-root {
    display: flex;
    justify-content: center;
    min-width: max-content;
}

.role-genealogy .role-children {
    display: flex;
    justify-content: center;
    gap: 2rem;
    position: relative;
    padding-top: 3.5rem;
}

.role-genealogy .role-children::before {
    content: '';
    position: absolute;
    top: 1.25rem;
    left: 10%;
    right: 10%;
    border-top: 2px solid #94a3b8;
}

.role-genealogy .role-node {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.role-genealogy .role-node > .role-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 220px;
    min-height: 86px;
    z-index: 1;
    padding: 0.75rem;
    border: 2px solid var(--role-color, #2563eb);
    border-left-width: 6px;
    border-radius: 0.5rem;
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.1);
    transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s;
}

.role-genealogy .role-node > .role-item::before {
    content: '';
    position: absolute;
    top: -2.35rem;
    left: 50%;
    height: 2.35rem;
    border-left: 2px solid #94a3b8;
}

.role-genealogy .role-node > .role-item::after {
    content: '';
    position: absolute;
    top: -2.5rem;
    left: calc(50% - 4px);
    width: 8px;
    height: 8px;
    border-right: 2px solid #64748b;
    border-bottom: 2px solid #64748b;
    transform: rotate(45deg);
    background: #f8fafc;
}

/* Root tidak punya garis penghubung ke atas -- tidak ada yang di atasnya. */
.role-genealogy > .role-tree-root > .role-node > .role-item::before,
.role-genealogy > .role-tree-root > .role-node > .role-item::after {
    display: none;
}

.role-genealogy .role-node > .role-item:hover {
    border-color: #3b82f6;
    transform: translateY(-3px);
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.18);
}

.role-genealogy .role-item .role-name {
    color: #0f172a;
    font-size: 0.9rem;
    font-weight: 700;
}

.role-genealogy .role-item .role-parent {
    color: #64748b;
    font-size: 0.72rem;
}

.role-genealogy .role-children:empty {
    display: none;
}

.role-genealogy .role-children > .role-node {
    flex: 0 0 auto;
}

.role-genealogy .role-item .material-symbols-outlined {
    font-size: 20px;
}

.role-genealogy .role-item .role-link:hover .role-name {
    color: #1d4ed8;
}

.role-genealogy .role-item.is-dragging {
    opacity: 0.45;
    transform: scale(0.98);
}

.role-genealogy .role-item.is-drop-target {
    border-style: dashed;
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.role-drag-handle {
    cursor: grab;
    user-select: none;
}

.role-drag-handle:active {
    cursor: grabbing;
}

@media (max-width: 640px) {
    .role-genealogy {
        min-height: 360px;
        padding-inline: 1rem;
    }

    .role-genealogy .role-children {
        gap: 0.75rem;
    }

    .role-genealogy .role-node > .role-item {
        width: 180px;
    }
}
</style>
