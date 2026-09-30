<script setup>
import { computed, ref } from 'vue'

import { formatAngka, formatRupiah, formatRupiahRingkas } from '@/lib/format'

/*
 * ============================================================================
 *  GRAFIK BATANG & GARIS
 * ============================================================================
 *
 *  SVG murni, tanpa pustaka grafik. Alasan sama seperti ChartDonut: build
 *  harus jalan tanpa akses internet ke registry npm.
 *
 *  Dua mode yang dibutuhkan laporan keuangan:
 *    mode "bar"  -- cash flow per bulan, dua seri (pemasukan, pengeluaran)
 *    mode "line" -- pengeluaran kantor per bulan, satu seri
 *
 *  Lebar dan tinggi memakai viewBox, bukan atribut px, supaya grafik ikut
 *  menyusut bersama container tanpa perlu menghitung ulang.
 *
 *  props:
 *    labels      string[]            sumbu horizontal
 *    series      [{ name, color, values:number[] }]
 *    mode        "bar" | "line"
 *    format      "rp" | "rp-ringkas" | "angka"
 *    ticks       jumlah garis bantu horizontal
 */

const props = defineProps({
    labels: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    mode: { type: String, default: 'bar' },
    format: { type: String, default: 'rp-ringkas' },
    ticks: { type: Number, default: 4 },
})

/* Area gambar dalam viewBox. Semua koordinat dihitung terhadap nilai ini. */
const LEBAR = 480
const TINGGI = 200
const MARGIN_KIRI = 52
const MARGIN_KANAN = 8
const MARGIN_ATAS = 10
const MARGIN_BAWAH = 22

const LebarPlot = LEBAR - MARGIN_KIRI - MARGIN_KANAN
const TinggiPlot = TINGGI - MARGIN_ATAS - MARGIN_BAWAH

const aktif = ref(null)

/*
 * Nilai tertinggi di seluruh seri, bukan per titik. Kalau tiap batang punya
 * skala sendiri, tinggi batang tidak bisa dibandingkan satu sama lain --
 * persis seperti yang perlu dilihat pada grafik arus kas.
 *
 * Pembulatan ke atas dipakai supaya batang tertinggi tidak menyentuh tepi
 * atas, dan minimum 1 supaya grafik tidak membuat pembagian dengan nol saat
 * semua data bernilai 0.
 */
const maks = computed(() => {
    const semua = props.series.flatMap((s) => s.values.map((v) => Number(v) || 0))

    return Math.max(1, ...semua)
})

/* Tick dengan angka "bulat" yang enak dibaca: 1, 2, 5, atau 10 kali pangkat
 * sepuluh, setinggi maks. Pembulatan ke atas dipakai supaya nilai tertinggi
 * selalu berada di dalam area plot, bukan tepat di tepi. */
const langkahTick = computed(() => {
    const kasar = Math.max(maks.value / props.ticks, 1e-9)
    const pangkat = Math.pow(10, Math.floor(Math.log10(kasar)))
    const sisa = kasar / pangkat

    let faktor

    if (sisa <= 1) faktor = 1
    else if (sisa <= 2) faktor = 2
    else if (sisa <= 5) faktor = 5
    else faktor = 10

    return faktor * pangkat
})

const garisGrid = computed(() => {
    const hasil = []
    const langkah = langkahTick.value

    /* Berhenti begitu nilai berikutnya melewati maks, supaya garis bantu tidak
     * pernah menggambar sesuatu yang tidak punya data. */
    for (let i = 0; i <= props.ticks; i++) {
        const nilai = langkah * (i + 1)

        if (nilai > maks.value) break

        hasil.push({
            nilai,
            y: MARGIN_ATAS + TinggiPlot * (1 - nilai / maks.value),
            label: teksSumbu(nilai),
        })
    }

    return hasil
})

const lebarSlot = computed(() =>
    props.labels.length ? LebarPlot / props.labels.length : LebarPlot
)

/*
 * Lebar batang di dalam satu slot. Jumlah seri menentukan pembagiannya, dan
 * ada ruang kosong antar batang supaya dua seri tidak terlihat menempel.
 */
const lebarBatang = computed(() => {
    const n = Math.max(1, props.series.length)

    return Math.max(2, (lebarSlot.value * 0.62) / n)
})

function tinggiBatang(nilai) {
    const n = Math.max(0, Number(nilai) || 0)

    return (n / maks.value) * TinggiPlot
}

function titikSlot(index, seriIndex) {
    const x = MARGIN_KIRI + lebarSlot.value * (index + 0.5)

    return x - (lebarBatang.value * props.series.length) / 2 + lebarBatang.value * seriIndex
}

function teksSumbu(nilai) {
    if (props.format === 'rp') return formatRupiah(nilai)
    if (props.format === 'rp-ringkas') return formatRupiahRingkas(nilai)

    return formatAngka(nilai)
}

function teksNilai(nilai) {
    if (props.format === 'rp') return formatRupiah(nilai)

    return formatRupiahRingkas(nilai)
}

/* Jalur garis untuk mode line. Titik yang{y, label} dihitung ulang
 * setiap render supaya ikut berubah saat data berubah. */
function jalurGaris(values) {
    if (!values.length) return ''

    return values
        .map((v, i) => {
            const x = MARGIN_KIRI + lebarSlot.value * (i + 0.5)
            const y = MARGIN_ATAS + TinggiPlot - tinggiBatang(v)

            return `${i === 0 ? 'M' : 'L'}${x.toFixed(2)},${y.toFixed(2)}`
        })
        .join(' ')
}

const jalur = computed(() => props.series.map((s) => jalurGaris(s.values)))

/* Label sumbu horizontal dijarangkan supaya tidak bertumpuk ketika bulannya
 * banyak. Yang dipakai hanya setiap Mathfceil(n/6) titik. */
const labelJarak = computed(() => Math.max(1, Math.ceil(props.labels.length / 6)))

function labelPendek(label) {
    const teks = String(label ?? '')

    return teks.length > 7 ? teks.slice(0, 7) : teks
}
</script>

<template>
    <div class="w-full">
        <!-- Legenda seri -->
        <div
            v-if="series.length > 1"
            class="flex flex-wrap items-center gap-x-4 gap-y-1 mb-2"
        >
            <span
                v-for="s in series"
                :key="s.name"
                class="flex items-center gap-1.5 text-[11px] text-slate-500"
            >
                <span
                    class="w-2.5 h-2.5 rounded-sm shrink-0"
                    :style="{ backgroundColor: s.color }"
                />
                {{ s.name }}
            </span>
        </div>

        <svg
            :viewBox="`0 0 ${LEBAR} ${TINGGI}`"
            class="w-full h-auto"
            role="img"
            aria-label="Grafik keuangan"
        >
            <!-- Garis bantu + label sumbu tegak -->
            <g v-for="(g, i) in garisGrid" :key="`g${i}`">
                <line
                    :x1="MARGIN_KIRI"
                    :x2="LEBAR - MARGIN_KANAN"
                    :y1="g.y"
                    :y2="g.y"
                    stroke="#e2e8f0"
                    stroke-width="1"
                />
                <text
                    :x="MARGIN_KIRI - 6"
                    :y="g.y + 3"
                    text-anchor="end"
                    font-size="9"
                    fill="#94a3b8"
                >
                    {{ g.label }}
                </text>
            </g>

            <!-- Label sumbu horizontal -->
            <text
                v-for="(l, i) in labels"
                v-show="i % labelJarak === 0"
                :key="`x${i}`"
                :x="MARGIN_KIRI + lebarSlot * (i + 0.5)"
                :y="TINGGI - 7"
                text-anchor="middle"
                font-size="9"
                fill="#94a3b8"
            >
                {{ labelPendek(l) }}
            </text>

            <!-- Batang -->
            <template v-if="mode === 'bar'">
                <template v-for="(s, si) in series" :key="`s${si}`">
                    <rect
                        v-for="(v, i) in s.values"
                        :key="i"
                        :x="titikSlot(i, si)"
                        :y="MARGIN_ATAS + TinggiPlot - tinggiBatang(v)"
                        :width="lebarBatang"
                        :height="Math.max(0, tinggiBatang(v))"
                        :fill="s.color"
                        rx="1"
                    >
                        <title>{{ labels[i] }} - {{ s.name }}: {{ teksNilai(v) }}</title>
                    </rect>
                </template>
            </template>

            <!-- Garis -->
            <template v-else>
                <path
                    v-for="(s, si) in series"
                    :key="`l${si}`"
                    :d="jalur[si]"
                    fill="none"
                    :stroke="s.color"
                    stroke-width="2"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                />

                <template v-for="(s, si) in series" :key="`p${si}`">
                    <circle
                        v-for="(v, i) in s.values"
                        :key="i"
                        :cx="MARGIN_KIRI + lebarSlot * (i + 0.5)"
                        :cy="MARGIN_ATAS + TinggiPlot - tinggiBatang(v)"
                        r="2.5"
                        :fill="s.color"
                    >
                        <title>{{ labels[i] }} - {{ s.name }}: {{ teksNilai(v) }}</title>
                    </circle>
                </template>
            </template>
        </svg>
    </div>
</template>
