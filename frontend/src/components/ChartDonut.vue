<script setup>
import { computed } from 'vue'

import { formatAngka, formatRupiah, formatRupiahRingkas } from '@/lib/format'

/*
 * ============================================================================
 *  GRAFIK DONAT
 * ============================================================================
 *
 *  SVG murni tanpa pustaka grafik.
 *
 *  Alasannya build harus jalan tanpa akses internet. Menambah chart.js berarti
 *  satu paket npm yang harus terunduh saat build di server, dan kalau jaringan
 *  internal tidak punya jalan keluar, build gagal total. SVG yang digambar
 *  sendiri hanya berukuran beberapa ratus byte.
 *
 *  Lingkaran dibuat dengan stroke-dasharray pada satu circle, bukan dengan
 *  path busur. Cara itu menghasilkan potongan yang tepat rata besarannya,
 *  sedangkan memakai path memaksa menghitung sudut awal tiap potongan.
 *
 *  props.segments: [{ label, value, color, format }]
 *  format diisi 'rp' untuk rupiah; selain itu angka biasa.
 */

const props = defineProps({
    segments: { type: Array, default: () => [] },
    thickness: { type: Number, default: 22 },
    labelTotal: { type: String, default: 'Total' },
})

const UKURAN = 180
const JARI_JARI = computed(() => (UKURAN - props.thickness) / 2)
const KELILING = computed(() => 2 * Math.PI * JARI_JARI.value)
const TENGAH = UKURAN / 2

const total = computed(() =>
    props.segments.reduce((sum, s) => sum + (Number(s.value) || 0), 0)
)

/*
 * Offset tiap potongan ditumpangkan: potongan kedua dimulai tepat di akhir
 * potongan pertama. stroke-dashoffset negatif dipakai supaya arahnya searah
 * jarum jam, dimulai dari pukul 12 lewat rotate(-90).
 */
const potongan = computed(() => {
    let kursor = 0

    return props.segments
        .map((s) => {
            const nilai = Math.max(0, Number(s.value) || 0)
            const panjang =
                total.value > 0 ? (nilai / total.value) * KELILING.value : 0

            const item = {
                label: s.label,
                color: s.color,
                format: s.format,
                nilai,
                persen: total.value > 0 ? (nilai / total.value) * 100 : 0,
                panjang,
                dasharray: `${panjang} ${KELILING.value - panjang}`,
                dashoffset: -kursor,
            }

            kursor += panjang

            return item
        })
        .filter((p) => p.panjang > 0)
})

function teksNilai(nilai, format) {
    if (format === 'rp') return formatRupiah(nilai)
    if (format === 'rp-ringkas') return formatRupiahRingkas(nilai)

    return formatAngka(nilai)
}

const totalTeks = computed(() => teksNilai(total.value, props.segments[0]?.format))
</script>

<template>
    <div class="flex flex-col items-center">
        <div
            class="relative shrink-0"
            :style="{ width: UKURAN + 'px', height: UKURAN + 'px' }"
        >
            <svg
                :width="UKURAN"
                :height="UKURAN"
                :viewBox="`0 0 ${UKURAN} ${UKURAN}`"
                role="img"
                aria-label="Komposisi keuangan"
            >
                <!-- Lingkaran latar tetap digambar agar donut tidak terlihat
                     kosong Total saat belum ada transaksi. -->
                <circle
                    :cx="TENGAH"
                    :cy="TENGAH"
                    :r="JARI_JARI"
                    fill="none"
                    stroke="#e2e8f0"
                    :stroke-width="thickness"
                />

                <circle
                    v-for="(p, i) in potongan"
                    :key="i"
                    :cx="TENGAH"
                    :cy="TENGAH"
                    :r="JARI_JARI"
                    fill="none"
                    :stroke="p.color"
                    :stroke-width="thickness"
                    :stroke-dasharray="p.dasharray"
                    :stroke-dashoffset="p.dashoffset"
                    stroke-linecap="butt"
                    :transform="`rotate(-90 ${TENGAH} ${TENGAH})`"
                >
                    <title>
                        {{ p.label }}: {{ teksNilai(p.nilai, p.format) }}
                        ({{ p.persen.toFixed(1) }}%)
                    </title>
                </circle>
            </svg>

            <div
                class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none"
            >
                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">
                    {{ labelTotal }}
                </span>
                <span class="text-sm font-bold text-slate-800 tabular-nums">
                    {{ totalTeks }}
                </span>
            </div>
        </div>

        <ul v-if="total > 0" class="mt-4 w-full space-y-1.5">
            <li
                v-for="(p, i) in potongan"
                :key="i"
                class="flex items-center justify-between gap-3 text-[11px]"
            >
                <span class="flex items-center gap-1.5 min-w-0">
                    <span
                        class="w-2 h-2 rounded-full shrink-0"
                        :style="{ backgroundColor: p.color }"
                    />
                    <span class="text-slate-500 truncate">{{ p.label }}</span>
                </span>

                <span class="flex items-center gap-2 shrink-0">
                    <span class="font-semibold text-slate-700 tabular-nums">
                        {{ teksNilai(p.nilai, p.format) }}
                    </span>
                    <span class="text-slate-400 tabular-nums w-9 text-right">
                        {{ p.persen.toFixed(0) }}%
                    </span>
                </span>
            </li>
        </ul>
    </div>
</template>
