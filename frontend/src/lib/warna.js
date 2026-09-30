/*
 * ============================================================================
 *  PETA WARNA AKTIVITAS
 * ============================================================================
 *
 *  Tabel activities menyimpan warna sebagai teks bebas, misalnya "purple" atau
 *  "emerald". Versi Blade membentuk kelasnya di templat:
 *
 *      bg-{{ $activity->color }}-50 text-{{ $activity->color }}-600
 *
 *  Pola itu tidak bisa dipakai di sini. Tailwind memindai berkas sumber untuk
 *  mencari nama kelas yang utuh, dan `bg-${warna}-50` tidak akan pernah
 *  ditemukan -- hasil buildnya CSS-nya kosong, jadi ikon aktivitas jadi tak
 *  berwarna tanpa error apa pun yang terlihat.
 *
 *  Karena itu nama kelasnya ditulis lengkap di sini. Berkas ini yang dibaca
 *  Tailwind, bukan template-nya, dan daftar warna di bawah harus mencakup
 *  semua nilai yang mungkin disimpan di kolom activities.color.
 *
 *  Warna yang tidak ada di sini tetap aman: warnaAktivitas() mengembalikan
 *  warna slate sebagai cadangan, bukan null.
 */

export const WARNA_AKTIVITAS = {
    purple: { bg: 'bg-purple-50', teks: 'text-purple-600' },
    blue: { bg: 'bg-blue-50', teks: 'text-blue-600' },
    emerald: { bg: 'bg-emerald-50', teks: 'text-emerald-600' },
    red: { bg: 'bg-red-50', teks: 'text-red-600' },
    teal: { bg: 'bg-teal-50', teks: 'text-teal-600' },
    amber: { bg: 'bg-amber-50', teks: 'text-amber-600' },
    green: { bg: 'bg-green-50', teks: 'text-green-600' },
    slate: { bg: 'bg-slate-50', teks: 'text-slate-600' },
}

/**
 * Pasangan kelas untuk satu slug warna.
 *
 * Nilai balik selalu objek lengkap, jadi pemanggil tidak perlu memeriksa
 * hasilnya sebelum dipakai di :class.
 */
export function warnaAktivitas(slug) {
    return WARNA_AKTIVITAS[slug] || WARNA_AKTIVITAS.slate
}
