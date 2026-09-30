// ─── Data dari Laravel (Via Global Window) ──────────────────────────────────
const ARMADA_DATA = window.CalendarData?.armada || [];
const BOOKING_DATA = window.CalendarData?.bookings || [];
const BASE_URL = window.CalendarData?.baseUrl || '';

// ─── Konstanta ──────────────────────────────────────────────────────────────
const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const HARI_PENDEK = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

// ─── State ──────────────────────────────────────────────────────────────────
const today = new Date();
let bulanAktif = today.getMonth();
let tahunAktif = today.getFullYear();
let filterAktif = new Set(['lunas', 'dp', 'belum_bayar', 'belum_lunas']);

// ─── Helpers ────────────────────────────────────────────────────────────────
function keDate(str) {
    if (!str) return new Date();
    const cleanStr = str.substring(0, 10);
    const [y, m, d] = cleanStr.split('-').map(Number);
    return new Date(y, m - 1, d);
}

function formatTgl(str) {
    if (!str) return '-';
    const d = keDate(str);
    return `${d.getDate()} ${NAMA_BULAN[d.getMonth()].slice(0, 3)} ${d.getFullYear()}`;
}

function konfig(status) {
    const map = {
        lunas: { bg: 'bg-emerald-100', text: 'text-emerald-800', border: 'border-emerald-200', dot: 'bg-emerald-500', label: 'Lunas', badgeBg: 'bg-emerald-100', badgeText: 'text-emerald-700' },
        dp: { bg: 'bg-blue-100', text: 'text-blue-800', border: 'border-blue-200', dot: 'bg-blue-500', label: 'DP', badgeBg: 'bg-blue-100', badgeText: 'text-blue-700' },
        belum_bayar: { bg: 'bg-amber-100', text: 'text-amber-800', border: 'border-amber-200', dot: 'bg-amber-500', label: 'Belum Bayar', badgeBg: 'bg-amber-100', badgeText: 'text-amber-700' },
        belum_lunas: { bg: 'bg-orange-100', text: 'text-orange-800', border: 'border-orange-300', dot: 'bg-orange-500', label: 'Belum Lunas', badgeBg: 'bg-orange-100', badgeText: 'text-orange-700' },
    };
    return map[status] || map['belum_bayar'];
}

// ─── Render Kalender ────────────────────────────────────────────────────────
function renderKalender() {
    const jumlahHari = new Date(tahunAktif, bulanAktif + 1, 0).getDate();
    document.getElementById('label_bulan').textContent = `${NAMA_BULAN[bulanAktif]} ${tahunAktif}`;

    // ── 1. PRE-PASS: Deteksi Hari Mana Saja yang Ada Pesanan ──
    // Array ini untuk menyimpan status (true/false) per tanggal
    let dayHasBooking = new Array(jumlahHari + 1).fill(false);
    let statTotal = 0, statLunas = 0, statDp = 0;
    
    // Kita simpan hasil filter di array ini agar tidak kerja dua kali
    const validRows = [];

    ARMADA_DATA.forEach((armada) => {
        const sembunyikanMasaLalu = document.getElementById('toggle_mobil_kosong')?.checked;

        // Filter pesanan sesuai dropdown
        const bknArmada = BOOKING_DATA.filter(b => {
            if (b.plat_nomor !== armada.plat_nomor) return false;
            if (b.status_rental === 'Batal') return false;
            if (!filterAktif.has(b.status)) return false;

            const bMulai = keDate(b.tanggal_mulai);
            const bSelesai = keDate(b.tanggal_selesai);

            if (sembunyikanMasaLalu) {
                const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                if (bSelesai < todayStart) return false;
            }

            const mAwal = new Date(tahunAktif, bulanAktif, 1);
            const mAkhir = new Date(tahunAktif, bulanAktif, jumlahHari, 23, 59, 59);
            return bSelesai >= mAwal && bMulai <= mAkhir;
        });

        if (sembunyikanMasaLalu && bknArmada.length === 0) return;

        // Tandai tanggal-tanggal yang dilewati oleh pesanan ini menjadi 'true'
        bknArmada.forEach(b => {
            const mAwal = new Date(tahunAktif, bulanAktif, 1);
            const mAkhir = new Date(tahunAktif, bulanAktif, jumlahHari);
            const bMulai = keDate(b.tanggal_mulai);
            const bSelesai = keDate(b.tanggal_selesai);

            const visStart = bMulai < mAwal ? 1 : bMulai.getDate();
            const visEnd = bSelesai > mAkhir ? jumlahHari : bSelesai.getDate();

            for (let i = visStart; i <= visEnd; i++) {
                dayHasBooking[i] = true;
            }
            
            // Hitung statistik (hanya untuk pesanan di bulan aktif)
            if (bMulai.getFullYear() === tahunAktif && bMulai.getMonth() === bulanAktif) {
                statTotal++;
                if (b.status === 'lunas') statLunas++;
                if (b.status === 'dp' || b.status === 'belum_bayar' || b.status === 'belum_lunas') statDp++;
            }
        });

        validRows.push({ armada, bknArmada });
    });

    // ── 2. THEAD (Header Kalender) ──
    let thead = '<tr>';
    thead += `<th class="sticky left-0 z-10 bg-slate-50 border-b border-r border-slate-200 px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[220px] w-[220px]">Armada</th>`;
    
    for (let d = 1; d <= jumlahHari; d++) {
        const dow = new Date(tahunAktif, bulanAktif, d).getDay();
        const isToday = tahunAktif === today.getFullYear() && bulanAktif === today.getMonth() && d === today.getDate();
        const isSun = dow === 0;

        const thClass = isToday ? 'bg-blue-50 border-x-2 border-x-blue-400 relative z-10' : (isSun ? 'bg-rose-50/40 border-slate-200' : 'bg-slate-50 border-slate-200');

        // 🟢 2 KATEGORI LEBAR: 140px jika ada hijau-hijau (pesanan), dan menciut 48px jika kosong
        const widthClass = dayHasBooking[d] ? 'min-w-[80px] w-[80px]' : 'min-w-[40px] w-[40px]';

        thead += `<th class="border-b border-r px-0 py-2 text-center transition-all duration-300 ${widthClass} ${thClass}">
            <div class="text-[11px] font-bold ${isToday ? 'text-brand-600' : isSun ? 'text-rose-400' : 'text-slate-500'}">${d}</div>
            <div class="text-[9px] font-medium ${isToday ? 'text-brand-400' : isSun ? 'text-rose-300' : 'text-slate-400'}">${HARI_PENDEK[dow]}</div>
        </th>`;
    }
    thead += '</tr>';
    document.getElementById('thead_kalender').innerHTML = thead;

    // ── 3. TBODY (Isi Kalender) ──
    let tbody = '';

    validRows.forEach(({ armada, bknArmada }, rowIdx) => {
        const isEven = rowIdx % 2 === 1;
        const stMap = {
            'Tersedia': { cls: 'bg-emerald-50 text-emerald-700 border border-emerald-200', label: 'Tersedia' },
            'Sedang Disewa': { cls: 'bg-blue-50 text-blue-700 border border-blue-200', label: 'Disewa' },
        };
        const stInfo = stMap[armada.status] || stMap['Tersedia'];

        tbody += `<tr class="group ${isEven ? 'bg-slate-50/50' : 'bg-white'} hover:bg-brand-50/30 transition-colors">`;
        tbody += `<td class="sticky left-0 z-10 ${isEven ? 'bg-slate-50' : 'bg-white'} group-hover:bg-brand-50/30 border-r border-b border-slate-200 px-4 py-3 transition-colors">
            <p class="text-xs font-bold text-slate-700 tracking-wide">${armada.plat_nomor}</p>
            <p class="text-xs text-slate-500 mt-0.5">${armada.nama}</p>
            <span class="inline-block mt-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-full ${stInfo.cls}">${stInfo.label}</span>
        </td>`;

        const sel = [];
        for (let d = 1; d <= jumlahHari; d++) sel.push({ d, konten: null, skip: false });

        bknArmada.forEach(b => {
            const mAwal = new Date(tahunAktif, bulanAktif, 1);
            const mAkhir = new Date(tahunAktif, bulanAktif, jumlahHari);
            const bMulai = keDate(b.tanggal_mulai);
            const bSelesai = keDate(b.tanggal_selesai);

            const visStart = bMulai < mAwal ? 1 : bMulai.getDate();
            const visEnd = bSelesai > mAkhir ? jumlahHari : bSelesai.getDate();
            const span = visEnd - visStart + 1;
            
            const namaLabel = b.nama_pelanggan || 'Tidak Diketahui';
            
            // 🟢 PANJANG TEKS MENGIKUTI LEBAR (1 hari = 14 huruf, kalau 2 hari = 30 huruf, dst)
            const maxChar = 7 + ((span - 1) * 9); 
            const label = namaLabel.length > maxChar ? namaLabel.slice(0, maxChar) + '…' : namaLabel;

            if (sel[visStart - 1]) sel[visStart - 1].konten = { span, label, booking: b };
            for (let i = visStart; i < visEnd; i++) if (sel[i]) sel[i].skip = true;
        });

        sel.forEach(s => {
            if (s.skip) return;

            const isToday = tahunAktif === today.getFullYear() && bulanAktif === today.getMonth() && s.d === today.getDate();
            const dow = new Date(tahunAktif, bulanAktif, s.d).getDay();
            const isSun = dow === 0;
            
            // Set 2 kategori lebar ke kotak-kotak yang kosong
            const widthClass = dayHasBooking[s.d] ? 'min-w-[80px] w-[80px]' : 'min-w-[40px] w-[40px]';

            if (s.konten) {
                const { span, label, booking: bk } = s.konten;
                const cfg = konfig(bk.status);
                const dashedClass = cfg.dashed ? 'border-dashed' : 'border-solid';

                let ruteHtml = '';
                let tooltipRute = '';

                if (bk.lokasi_jemput || bk.lokasi_antar) {
                    let barisLokasi = '';
                    let tooltipArr = [];

                    if (bk.lokasi_jemput) {
                        barisLokasi += `<div class="flex items-center gap-1 truncate"><span class="material-symbols-outlined text-[10px]">pin_drop</span><span class="truncate">Jemput: ${bk.lokasi_jemput}</span></div>`;
                        tooltipArr.push(`Jemput: ${bk.lokasi_jemput}`);
                    }
                    if (bk.lokasi_antar) {
                        barisLokasi += `<div class="flex items-center gap-1 truncate"><span class="material-symbols-outlined text-[10px]">flag</span><span class="truncate">Antar: ${bk.lokasi_antar}</span></div>`;
                        tooltipArr.push(`Antar: ${bk.lokasi_antar}`);
                    }

                    tooltipRute = ` | 📍 ${tooltipArr.join(' • ')}`;
                    ruteHtml = `
                    <div class="text-[9px] font-medium opacity-75 mt-1 flex flex-col gap-[2px]">
                        ${barisLokasi}
                    </div>`;
                }

                // Tampilkan lencana (badge) hanya jika lebar kotaknya lebih dari 1 hari
                const showBadge = span > 1 ? `<span class="ml-1 flex-shrink-0 px-1.5 py-0.5 rounded text-[9px] font-bold bg-white/60 border ${cfg.border} hidden md:inline-block shadow-sm">${cfg.label}</span>` : '';
                const tdIsiClass = isToday ? 'bg-blue-50/50 border-l-2 border-l-blue-400 relative z-10' : 'border-slate-200';

                tbody += `<td class="border-b border-r px-1 py-1 ${tdIsiClass}" colspan="${span}">
                    <div onclick="bukaModal(${JSON.stringify(bk).replace(/"/g, '&quot;')})"
                        class="w-full h-full group relative cursor-pointer flex items-center justify-between gap-2 overflow-hidden rounded-lg px-2.5 py-1.5 border ${dashedClass} ${cfg.bg} ${cfg.text} ${cfg.border} hover:shadow-md hover:-translate-y-[1px] transition-all duration-200"
                        title="Pesanan: ${bk.nama_pelanggan} | Status: ${cfg.label} | ${formatTgl(bk.tanggal_mulai)} → ${formatTgl(bk.tanggal_selesai)}${tooltipRute}">
                        
                        <div class="flex flex-col truncate min-w-0">
                            <div class="flex items-center gap-1.5 truncate">
                                <span class="w-2 h-2 rounded-full ${cfg.dot} flex-shrink-0 shadow-sm"></span>
                                <span class="text-xs font-bold truncate tracking-tight">${label}</span>
                            </div>
                            ${ruteHtml}
                        </div>

                        ${showBadge}
                    </div>
                </td>`;
            } else {
                const tdKosongClass = isToday ? 'bg-blue-50/50 border-x-2 border-x-blue-400 relative z-10' : (isSun ? 'bg-rose-50/30 border-slate-200' : 'border-slate-200');
                
                // Tempelkan kategori lebarnya di sel yang kosong
                tbody += `<td class="border-b border-r transition-all duration-300 ${widthClass} ${tdKosongClass}"></td>`;
            }
        });

        tbody += '</tr>';
    });

    document.getElementById('tbody_kalender').innerHTML = tbody;

    // Update Angka Statistik (Cards)
    const statTotalEl = document.getElementById('stat_total');
    if (statTotalEl) statTotalEl.textContent = statTotal;

    const statLunasEl = document.getElementById('stat_lunas');
    if (statLunasEl) statLunasEl.textContent = statLunas;

    const statLunasSubEl = document.getElementById('stat_lunas_sub');
    if (statLunasSubEl) statLunasSubEl.textContent = `dari ${statTotal} booking`;

    const statDpEl = document.getElementById('stat_dp');
    if (statDpEl) statDpEl.textContent = statDp;
}

// ─── Interaksi & Event ──────────────────────────────────────────────────────
function toggleFilter(el, status) {
    const isActive = el.dataset.active === 'true';
    if (isActive) {
        filterAktif.delete(status);
        el.dataset.active = 'false';

        // FIX: Jangan hapus 'text-xs' dan 'border' dasar dari Tailwind
        el.classList.remove(...el.className.split(' ').filter(c =>
            c.includes('bg-') ||
            (c.includes('text-') && c !== 'text-xs') ||
            (c.includes('border-') && c !== 'border-solid' && c !== 'border-dashed')
        ));

        el.classList.add('bg-slate-50', 'border-slate-200', 'text-slate-400');
    } else {
        filterAktif.add(status);
        el.dataset.active = 'true';
        const cfg = konfig(status);

        el.classList.remove('bg-slate-50', 'border-slate-200', 'text-slate-400');
        el.classList.add(cfg.bg, cfg.border, cfg.text);
    }
    renderKalender();
}

function gantibulan(delta) {
    bulanAktif += delta;
    if (bulanAktif > 11) { bulanAktif = 0; tahunAktif++; }
    if (bulanAktif < 0) { bulanAktif = 11; tahunAktif--; }
    renderKalender();
}

function keBulanIni() {
    bulanAktif = today.getMonth();
    tahunAktif = today.getFullYear();
    renderKalender();
}

function bukaModal(bk) {
    const cfg = konfig(bk.status);
    document.getElementById('modal_nama').textContent = bk.nama_pelanggan || 'Tidak Diketahui';
    document.getElementById('modal_nomor').textContent = bk.nomor_pesanan || '—';
    document.getElementById('modal_kendaraan').textContent = `${bk.plat_nomor} – ${bk.nama_mobil || ''}`;
    document.getElementById('modal_tanggal').textContent = `${formatTgl(bk.tanggal_mulai)} → ${formatTgl(bk.tanggal_selesai)}`;
    document.getElementById('modal_tipe').textContent = bk.tipe_sewa || '—';
    document.getElementById('modal_nominal').textContent = bk.total_harga ? 'Rp ' + Number(bk.total_harga).toLocaleString('id-ID') : '—';

    const badge = document.getElementById('modal_badge');
    badge.textContent = cfg.label;
    badge.className = `text-xs font-semibold px-2.5 py-1 rounded-full flex-shrink-0 ${cfg.badgeBg} ${cfg.badgeText}`;

    const linkDetail = document.getElementById('modal_link_detail');
    if (bk.booking_id) {
        linkDetail.href = `${BASE_URL}/bookings/${bk.booking_id}`;
        linkDetail.classList.remove('hidden');
    } else {
        linkDetail.classList.add('hidden');
    }
    document.getElementById('modal_overlay').classList.remove('hidden');
}

function tutupModal() {
    document.getElementById('modal_overlay').classList.add('hidden');
}

function cekKetersediaan() {
    const dari = document.getElementById('cek_dari').value;
    const sampai = document.getElementById('cek_sampai').value;
    if (!dari || !sampai) { alert('Isi tanggal dari dan sampai terlebih dahulu.'); return; }
    if (dari > sampai) { alert('Tanggal "Dari" tidak boleh lebih besar dari "Sampai".'); return; }

    const qMulai = keDate(dari);
    const qSelesai = keDate(sampai);

    const hasil = ARMADA_DATA.map(a => {
        const konflik = BOOKING_DATA.find(b => {
            if (b.plat_nomor !== a.plat_nomor || b.status_rental === 'Batal') return false;
            return keDate(b.tanggal_mulai) <= qSelesai && keDate(b.tanggal_selesai) >= qMulai;
        });
        return { ...a, konflik: !!konflik, konflikDengan: konflik?.nama_pelanggan || null };
    });

    hasil.sort((a, b) => {
        // Jika statusnya sama, biarkan posisinya (0)
        // Jika 'a' konflik (true) tapi 'b' tidak, lempar 'a' ke bawah (1)
        // Jika 'a' tidak konflik (false), tarik 'a' ke atas (-1)
        return (a.konflik === b.konflik) ? 0 : a.konflik ? 1 : -1;
    });

    document.getElementById('hasil_cek_range').textContent = `${formatTgl(dari)} – ${formatTgl(sampai)}`;
    let html = '';
    hasil.forEach(r => {
        if (r.konflik) {
            html += `<div class="flex items-center gap-3 px-4 py-3"><span class="w-2 h-2 rounded-full bg-rose-400 flex-shrink-0"></span><span class="text-sm font-semibold text-slate-700">${r.plat_nomor}</span><span class="text-sm text-slate-400">${r.nama}</span><span class="ml-auto text-xs font-medium text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">✗ ${r.konflikDengan}</span></div>`;
        } else {
            html += `<div class="flex items-center gap-3 px-4 py-3"><span class="w-2 h-2 rounded-full bg-emerald-400 flex-shrink-0"></span><span class="text-sm font-semibold text-slate-700">${r.plat_nomor}</span><span class="text-sm text-slate-400">${r.nama}</span><span class="ml-auto text-xs font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">✓ Tersedia</span></div>`;
        }
    });

    document.getElementById('hasil_cek_list').innerHTML = html;
    document.getElementById('hasil_cek').classList.remove('hidden');
    document.getElementById('btn_tutup_cek').classList.remove('hidden');
}

function tutupHasilCek() {
    document.getElementById('hasil_cek').classList.add('hidden');
    document.getElementById('btn_tutup_cek').classList.add('hidden');
}

// ─── Expose to Global Scope (Wajib untuk module bundler seperti Vite) ──────
window.renderKalender = renderKalender;
window.toggleFilter = toggleFilter;
window.gantibulan = gantibulan;
window.keBulanIni = keBulanIni;
window.bukaModal = bukaModal;
window.tutupModal = tutupModal;
window.cekKetersediaan = cekKetersediaan;
window.tutupHasilCek = tutupHasilCek;

// ─── Init ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('tabel_kalender')) {
        renderKalender();
    }
});