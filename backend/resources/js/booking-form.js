/**
 * booking-form.js
 * Digunakan oleh: bookings/create.blade.php & bookings/edit.blade.php
 *
 * Mode ditentukan otomatis via data attribute pada <form id="bookingForm">:
 *   - data-mode="create"  → mode buat pesanan baru
 *   - data-mode="edit"    → mode edit, data awal diambil dari
 *                           data-prefill (JSON string) di elemen #booking-prefill
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Deteksi Mode ───────────────────────────────────────────────────
    const form = document.getElementById('bookingForm');
    const IS_EDIT = form?.dataset.mode === 'edit';

    // ── State ──────────────────────────────────────────────────────────
    let tipe = document.getElementById('input_tipe_sewa')?.value || 'harian';
    let durasi = 0;
    let kendaraanIdx = 0;

    // Semua mobil dari server (dari <script id="all-cars-json">)
    const allCars = JSON.parse(
        document.getElementById('all-cars-json')?.textContent || '[]'
    );

    // Set mobil yang sudah terpilih di form (key = car.id)
    const selectedCars = new Set();

    // Temporary selection saat modal terbuka (belum di-confirm)
    const tempSelected = new Set();

    // ── Elemen DOM ─────────────────────────────────────────────────────
    const inputTipe = document.getElementById('input_tipe_sewa');
    const inputMulai = document.getElementById('tanggal_mulai');
    const inputSelesai = document.getElementById('tanggal_selesai');
    const inputDurasiHari = document.getElementById('input_durasi_hari');
    const infoDurasi = document.getElementById('info-durasi');
    const teksDurasi = document.getElementById('teks-durasi');
    const sectionLokasi = document.getElementById('section-lokasi');
    const containerKendaraan = document.getElementById('container-kendaraan');
    const emptyKendaraan = document.getElementById('empty-kendaraan');
    const containerAddons = document.getElementById('container-addons');
    const emptyAddon = document.getElementById('empty-addon');

    // Modal
    const modal = document.getElementById('modal-picker');
    const modalOverlay = document.getElementById('modal-overlay');
    const modalSearch = document.getElementById('modal-search');
    const modalFilters = document.getElementById('modalFilterDropdown');
    const modalCarList = document.getElementById('modal-car-list');
    const modalCount = document.getElementById('modal-selected-count');
    const btnConfirm = document.getElementById('btn-modal-confirm');

    // ── Helper ─────────────────────────────────────────────────────────
    function formatRp(n) {
        return 'Rp ' + Number(n).toLocaleString('id-ID');
    }

    // Fungsi untuk memformat input menjadi ribuan
    function formatInputRibuan(value) {
        if (!value) return '';
        // Hapus semua karakter selain angka
        let number_string = value.toString().replace(/[^0-9]/g, '');
        // Tambahkan titik setiap 3 angka
        return number_string.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    window.hapusExistingFoto = function (btn, jenis, path) {
        if (!confirm('Apakah Anda yakin ingin menghapus foto ini saat pesanan disimpan?')) return;

        // 1. Sembunyikan card secara visual
        const card = btn.closest('.existing-file-card');
        card.style.display = 'none';

        // 2. Buat input hidden agar terbaca di Controller
        const form = document.getElementById('bookingForm');
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = jenis === 'ktp' ? 'hapus_foto_ktp[]' : 'hapus_foto_penyewa[]';
        hiddenInput.value = path;

        form.appendChild(hiddenInput);
    };

    // ── Tipe Sewa Toggle ────────────────────────────────────────────────
    function applyTipeUI() {
        document.querySelectorAll('.tipe-btn').forEach(b => {
            const active = b.dataset.tipe === tipe;
            b.classList.toggle('bg-white', active);
            b.classList.toggle('shadow-sm', active);
            b.classList.toggle('text-slate-800', active);
            b.classList.toggle('text-slate-500', !active);
        });

        document.querySelectorAll('.section-foto-kontrak').forEach(s => {
            s.classList.toggle('hidden', tipe !== 'kontrak');
        });

        if (sectionLokasi) sectionLokasi.classList.toggle('hidden', tipe === 'kontrak');
    }

    document.querySelectorAll('.tipe-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            tipe = this.dataset.tipe;
            inputTipe.value = tipe;
            applyTipeUI();

            // Update harga semua kendaraan yang sudah ditambahkan
            containerKendaraan.querySelectorAll('.kendaraan-item').forEach(item => {
                const carId = parseInt(item.dataset.carId);
                const car = allCars.find(c => c.id === carId);
                if (!car) return;
                const hargaBaru = tipe === 'kontrak' ? car.harga_kontrak : car.harga;
                item.querySelector('.harga-display').value = hargaBaru;
                item.querySelector('.harga-input').value = hargaBaru;
            });

            // Refresh harga di modal jika sedang terbuka
            if (modal && !modal.classList.contains('hidden')) applyModalFilter();

            updateSummary();
        });
    });

    // Init toggle visual sesuai nilai awal (penting untuk mode edit)
    applyTipeUI();

    // ── Kalkulasi Durasi ────────────────────────────────────────────────
    // Pada mode edit tidak membatasi tanggal minimum (pesanan lama boleh)
    if (!IS_EDIT) {
        const hariIni = new Date();
        const strHariIni = [
            hariIni.getFullYear(),
            String(hariIni.getMonth() + 1).padStart(2, '0'),
            String(hariIni.getDate()).padStart(2, '0'),
        ].join('-');
        if (inputMulai) inputMulai.min = strHariIni;
        if (inputSelesai && !inputSelesai.min) inputSelesai.min = strHariIni;
    }

    function updateUIInfo(d1, d2) {
        const fmt = d => d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        if (durasi > 0) {
            if (teksDurasi) teksDurasi.textContent = `${durasi} hari (${fmt(d1)} – ${fmt(d2)})`;
            infoDurasi?.classList.remove('hidden');
            document.getElementById('summary-durasi')?.classList.remove('hidden');
            document.getElementById('summary-teks-durasi').textContent = `${durasi} Hari`;
            document.getElementById('summary-teks-tanggal').textContent = `${fmt(d1)} – ${fmt(d2)}`;
        } else {
            infoDurasi?.classList.add('hidden');
            document.getElementById('summary-durasi')?.classList.add('hidden');
        }
    }

    function sinkronDariTanggal() {
        const mulai = inputMulai?.value;
        const selesai = inputSelesai?.value;
        if (mulai && selesai) {
            const d1 = new Date(mulai);
            const d2 = new Date(selesai);
            const diff = Math.round((d2 - d1) / 86400000);
            durasi = diff > 0 ? diff : 0;
            if (inputDurasiHari) {
                inputDurasiHari.value = durasi;
                inputDurasiHari.disabled = false;
            }
            updateUIInfo(d1, d2);
        } else {
            durasi = 0;
            if (inputDurasiHari) inputDurasiHari.value = '';
            infoDurasi?.classList.add('hidden');
            document.getElementById('summary-durasi')?.classList.add('hidden');
        }
        updateSummary();
    }

    function sinkronDariDurasi() {
        const mulai = inputMulai?.value;
        const durasiVal = parseInt(inputDurasiHari?.value);
        if (mulai && !isNaN(durasiVal) && durasiVal >= 0) {
            durasi = durasiVal;
            const d1 = new Date(mulai);
            const d2 = new Date(mulai);
            d2.setDate(d1.getDate() + durasiVal);
            const fmt = d => [
                d.getFullYear(),
                String(d.getMonth() + 1).padStart(2, '0'),
                String(d.getDate()).padStart(2, '0'),
            ].join('-');
            if (inputSelesai) inputSelesai.value = fmt(d2);
            updateUIInfo(d1, d2);
        } else {
            durasi = 0;
            if (inputSelesai) inputSelesai.value = '';
        }
        updateSummary();
    }

    inputMulai?.addEventListener('change', function () {
        if (this.value) {
            if (inputSelesai) inputSelesai.min = this.value;
            if (inputDurasiHari) inputDurasiHari.disabled = false;
            if (inputSelesai?.value && inputSelesai.value < this.value) inputSelesai.value = this.value;
        } else {
            if (inputDurasiHari) { inputDurasiHari.disabled = true; inputDurasiHari.value = ''; }
        }
        sinkronDariTanggal();
    });
    inputSelesai?.addEventListener('change', sinkronDariTanggal);
    inputDurasiHari?.addEventListener('input', sinkronDariDurasi);

    // ── Modal: Buka / Tutup ─────────────────────────────────────────────
    document.getElementById('btn-tambah-kendaraan')?.addEventListener('click', openModal);
    modalOverlay?.addEventListener('click', closeModal);
    document.getElementById('btn-modal-close')?.addEventListener('click', closeModal);
    document.getElementById('btn-modal-cancel')?.addEventListener('click', closeModal);

    function openModal() {
        tempSelected.clear();
        selectedCars.forEach(id => tempSelected.add(id));

        // 1. Kosongkan input pencarian jika ada
        if (modalSearch) modalSearch.value = '';

        // 2. Panggil fungsi filter (otomatis me-render list di dalamnya)
        applyModalFilter();

        // 3. Tampilkan Modal
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');

        if (modalSearch) modalSearch.focus();
        updateModalCount();
    }

    // 👇 TAMBAHKAN FUNGSI INI 👇
    function closeModal() {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    }

    // ── Modal: Multiple Filter Dropdown ─────────────────────────────────
    const btnToggleFilter = document.getElementById('btn-toggle-modal-filter');
    const filterDropdown = document.getElementById('modalFilterDropdown');
    const btnApplyFilter = document.getElementById('btn-apply-modal-filter');
    const btnResetFilter = document.getElementById('btn-reset-modal-filter');

    // Elemen Select
    const selectStatus = document.getElementById('modal-filter-status');
    const selectServis = document.getElementById('modal-filter-servis');
    const selectMerk = document.getElementById('modal-filter-merk');
    const selectTahun = document.getElementById('modal-filter-tahun');

    // Toggle dropdown
    btnToggleFilter?.addEventListener('click', () => {
        filterDropdown?.classList.toggle('hidden');
    });

    // Tutup saat klik di luar
    // Tutup saat klik di luar — pakai mousedown agar tidak bentrok dengan scroll
    document.addEventListener('mousedown', (e) => {
        const filterContainer = document.getElementById('modalFilterContainer');
        if (filterContainer && !filterContainer.contains(e.target)) {
            filterDropdown?.classList.add('hidden');
        }
    });

    // Cegah scroll di dalam dropdown menutup dropdown itu sendiri
    filterDropdown?.addEventListener('scroll', (e) => {
        e.stopPropagation();
    });

    // Tombol Apply
    btnApplyFilter?.addEventListener('click', () => {
        applyModalFilter();
        filterDropdown?.classList.add('hidden');

        // Aman dari error jika select tidak ditemukan di DOM
        const isFiltered = (selectStatus?.value) || (selectServis?.value) || (selectMerk?.value) || (selectTahun?.value);
        btnToggleFilter?.classList.toggle('bg-blue-50', !!isFiltered);
        btnToggleFilter?.classList.toggle('border-blue-200', !!isFiltered);
        btnToggleFilter?.classList.toggle('text-blue-700', !!isFiltered);
    });

    // Tombol Reset
    btnResetFilter?.addEventListener('click', () => {
        if (selectStatus) selectStatus.value = '';
        if (selectServis) selectServis.value = '';
        if (selectMerk) selectMerk.value = '';
        if (selectTahun) selectTahun.value = '';
        if (modalSearch) modalSearch.value = '';

        applyModalFilter();
        filterDropdown?.classList.add('hidden');

        btnToggleFilter?.classList.remove('bg-blue-50', 'border-blue-200', 'text-blue-700');
    });

    // Listener Live Search
    modalSearch?.addEventListener('input', applyModalFilter);

    function applyModalFilter() {
        // PERBAIKAN: Gunakan ternary agar tidak crash jika modalSearch null
        const q = modalSearch ? modalSearch.value.trim().toLowerCase() : '';

        const fStatus = selectStatus ? selectStatus.value : '';
        const fServis = selectServis ? selectServis.value : '';
        const fMerk = selectMerk ? selectMerk.value : '';
        const fTahun = selectTahun ? selectTahun.value : '';

        const filtered = allCars.filter(car => {
            const matchSearch = !q ||
                car.merk.toLowerCase().includes(q) ||
                car.model.toLowerCase().includes(q) ||
                car.plat.toLowerCase().includes(q);

            const matchStatus = !fStatus || car.status === fStatus;
            const matchMerk = !fMerk || car.merk === fMerk;
            const matchTahun = !fTahun || car.tahun == fTahun;
            const matchServis = !fServis || car.servis_due == fServis;

            return matchSearch && matchStatus && matchMerk && matchTahun && matchServis;
        });

        renderModalList(filtered);
    }

    // ── Modal: Render daftar mobil ──────────────────────────────────────
    function renderModalList(cars) {
        if (!modalCarList) return;
        modalCarList.innerHTML = '';

        if (cars.length === 0) {
            modalCarList.innerHTML = `
                <div class="flex flex-col items-center justify-center py-10 text-center">
                    <span class="material-symbols-outlined text-slate-300 text-4xl mb-2">search_off</span>
                    <p class="text-sm text-slate-400">Tidak ada kendaraan yang cocok</p>
                </div>`;
            return;
        }

        cars.forEach(car => {
            const isSelected = tempSelected.has(car.id);
            const hargaTampil = tipe === 'kontrak' ? car.harga_kontrak : car.harga;

            const div = document.createElement('div');
            div.className = [
                'car-option flex items-center gap-4 p-3.5 rounded-xl border cursor-pointer transition-all select-none',
                isSelected
                    ? 'border-blue-400 bg-blue-50 ring-1 ring-blue-300'
                    : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
            ].join(' ');
            div.dataset.carId = car.id;

            div.innerHTML = `
                <div class="w-10 h-10 rounded-xl ${isSelected ? 'bg-blue-600' : 'bg-slate-100'} flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined ${isSelected ? 'text-white' : 'text-slate-400'} text-[20px]">directions_car</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm text-slate-800">${car.merk} ${car.model}</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-600">${car.plat}</span>
                        <span class="mx-1.5 text-slate-300">·</span>
                        <span class="text-blue-600 font-medium">${formatRp(hargaTampil)}/hari</span>
                    </p>
                </div>
                <div class="w-6 h-6 rounded-full border-2 shrink-0 flex items-center justify-center transition-all ${isSelected ? 'bg-blue-600 border-blue-600' : 'border-slate-300 bg-white'}">
                    ${isSelected ? '<span class="material-symbols-outlined text-white text-[14px]">check</span>' : ''}
                </div>`;

            div.addEventListener('click', () => toggleTempSelect(car.id));
            modalCarList.appendChild(div);
        });
    }

    function toggleTempSelect(carId) {
        if (tempSelected.has(carId)) {
            tempSelected.delete(carId);
        } else {
            tempSelected.add(carId);
        }
        updateModalCount();
        applyModalFilter();
    }

    function updateModalCount() {
        const newCount = [...tempSelected].filter(id => !selectedCars.has(id)).length;
        if (modalCount) modalCount.textContent = tempSelected.size;
        if (btnConfirm) btnConfirm.disabled = newCount === 0;
    }

    // ── Modal: Konfirmasi ───────────────────────────────────────────────
    btnConfirm?.addEventListener('click', function () {
        const toAdd = [...tempSelected].filter(id => !selectedCars.has(id));
        toAdd.forEach(carId => {
            const car = allCars.find(c => c.id == carId);
            if (car) addKendaraanCard(car);
        });
        closeModal();
        checkEmptyKendaraan();
        updateSummary();
    });

    // ── Tambah Card Kendaraan ───────────────────────────────────────────
    /**
     * @param {object} car      - objek dari allCars
     * @param {number} [hargaOverride] - harga custom (khusus mode edit, ambil dari DB)
     */
    function addKendaraanCard(car, hargaOverride = null, existingPhotos = []) {
        if (selectedCars.has(car.id)) return;
        selectedCars.add(car.id);

        const tpl = document.getElementById('tpl-kendaraan').content.cloneNode(true);
        const item = tpl.querySelector('.kendaraan-item');
        const idx = ++kendaraanIdx;

        item.dataset.idx = idx;
        item.dataset.carId = car.id;

        // Harga: pakai override (dari DB) kalau ada, jika tidak pakai default tipe sewa
        const hargaAwal = hargaOverride !== null
            ? hargaOverride
            : (tipe === 'kontrak' ? car.harga_kontrak : car.harga);

        item.querySelector('.input-car-id').value = car.id;
        item.querySelector('.harga-input').value = hargaAwal;

        item.querySelector('.car-nama').textContent = `${car.merk} ${car.model}`;
        item.querySelector('.car-plat').textContent = car.plat;

        // Foto mobil
        if (car.foto) {
            const fotoEl = item.querySelector('.car-foto');
            const placeholder = item.querySelector('.car-foto-placeholder');
            fotoEl.src = car.foto;
            fotoEl.classList.remove('hidden');
            placeholder?.classList.add('hidden');
        }

        // Input harga editable
        const hargaDisplay = item.querySelector('.harga-display');
        // Format harga default saat card ditambahkan
        hargaDisplay.value = formatInputRibuan(hargaAwal);

        hargaDisplay.addEventListener('input', function () {
            // Terapkan format otomatis saat user mengetik
            this.value = formatInputRibuan(this.value);
            updateSummary();
        });

        // Foto kondisi (kontrak)
        const sectionFoto = item.querySelector('.section-foto-kontrak');
        sectionFoto?.classList.toggle('hidden', tipe !== 'kontrak');

        const inputFoto = item.querySelector('.input-foto');
        const previewContainer = item.querySelector('.preview-foto-container');

        if (existingPhotos.length > 0) {
            existingPhotos.forEach(path => {
                const div = document.createElement('div');
                div.className = 'relative w-16 h-16 rounded-lg border border-slate-200 overflow-hidden group/img existing-kondisi-card';
                div.innerHTML = `
                    <img src="/storage/${path}" class="w-full h-full object-cover">
                    <button type="button" class="absolute top-0.5 right-0.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover/img:opacity-100 transition-opacity" title="Hapus foto ini">
                        <span class="material-symbols-outlined text-[12px]">close</span>
                    </button>`;
                
                // Logika hapus foto lama
                div.querySelector('button').onclick = () => {
                    if (!confirm('Apakah Anda yakin ingin menghapus foto kondisi ini?')) return;
                    div.style.display = 'none'; // Sembunyikan visualnya
                    
                    // Buat input hidden agar backend tahu file ini harus dihapus
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = `hapus_foto_kondisi[${car.id}][]`;
                    hiddenInput.value = path;
                    item.appendChild(hiddenInput);
                };
                previewContainer.appendChild(div);
            });
        }

        if (inputFoto) {
            // Ubah nama input agar spesifik untuk ID mobil ini
            inputFoto.name = `foto_kondisi[${car.id}][]`;

            // Gunakan DataTransfer agar file tetap tersimpan dan bisa dikirim saat submit
            let dtKondisi = new DataTransfer();

            inputFoto.addEventListener('change', function () {
                // Tambahkan file yang baru dipilih ke memori DataTransfer
                Array.from(this.files).forEach(file => {
                    dtKondisi.items.add(file);
                });

                // Kosongkan preview visual untuk di-render ulang
                previewContainer.innerHTML = '';

                // Render ulang preview gambar
                Array.from(dtKondisi.files).forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = e => {
                        const div = document.createElement('div');
                        div.className = 'relative w-16 h-16 rounded-lg border border-slate-200 overflow-hidden group/img';
                        div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <button type="button" class="absolute top-0.5 right-0.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover/img:opacity-100 transition-opacity" title="Hapus foto ini">
                        <span class="material-symbols-outlined text-[12px]">close</span>
                    </button>`;

                        // Logika jika tombol "X" (hapus) pada foto diklik
                        div.querySelector('button').onclick = () => {
                            const newDt = new DataTransfer();
                            Array.from(dtKondisi.files).forEach((f, i) => {
                                if (i !== index) newDt.items.add(f); // Lewati file yang dihapus
                            });
                            dtKondisi = newDt;
                            inputFoto.files = dtKondisi.files; // Sinkronkan sisa file ke input
                            div.remove(); // Hapus visualnya
                        };

                        previewContainer.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });

                // SINKRONISASI PENTING: Pindahkan file dari memori ke elemen <input> asli
                inputFoto.files = dtKondisi.files;
            });
        }

        // Tombol hapus
        item.querySelector('.btn-hapus-kendaraan')?.addEventListener('click', function () {
            selectedCars.delete(car.id);
            item.remove();
            checkEmptyKendaraan();
            updateSummary();
        });

        containerKendaraan.appendChild(item);
    }

    function checkEmptyKendaraan() {
        const has = containerKendaraan.querySelectorAll('.kendaraan-item').length > 0;
        emptyKendaraan?.classList.toggle('hidden', has);
    }

    // ── Add-ons ─────────────────────────────────────────────────────────
    function tambahAddonBaru(namaPreset = '', nominalPreset = '') {
        const tpl = document.getElementById('tpl-addon').content.cloneNode(true);
        const item = tpl.querySelector('.addon-item');
        const inputNama = item.querySelector('[name="addon_nama[]"]');
        const inputNominal = item.querySelector('.addon-nominal');

        if (namaPreset) inputNama.value = namaPreset;
        // Format nilai awal jika ada
        if (nominalPreset) inputNominal.value = formatInputRibuan(nominalPreset);

        item.querySelector('.btn-hapus-addon')?.addEventListener('click', () => {
            item.remove();
            checkEmptyAddon();
            updateSummary();
        });

        // Terapkan format otomatis saat user mengetik
        inputNominal.addEventListener('input', function () {
            this.value = formatInputRibuan(this.value);
            updateSummary();
        });

        inputNama.addEventListener('input', updateSummary);

        // Tambahkan elemen ke dalam container di layar
        containerAddons.appendChild(item);

        // Perbarui tampilan status kosong & ringkasan
        checkEmptyAddon();
        updateSummary();

        // Otomatis arahkan fokus kursor agar user mudah mengetik
        if (namaPreset && !nominalPreset) inputNominal.focus();
        else if (!namaPreset) inputNama.focus();
    }

    document.getElementById('btn-tambah-addon')?.addEventListener('click', () => tambahAddonBaru());
    document.querySelectorAll('.btn-quick-addon').forEach(btn => {
        btn.addEventListener('click', () => tambahAddonBaru(btn.dataset.nama));
    });

    function checkEmptyAddon() {
        emptyAddon?.classList.toggle('hidden', containerAddons.querySelectorAll('.addon-item').length > 0);
    }

    // ── Summary ─────────────────────────────────────────────────────────
    function updateSummary() {
        // Pelanggan
        const inputNama = document.querySelector('input[name="nama_pelanggan"]');
        const inputTelp = document.querySelector('input[name="kontak_pelanggan"]');
        const inputCatatan = document.querySelector('input[name="catatan"]');

        document.getElementById('summary-nama').textContent = inputNama?.value.trim() || '-';
        document.getElementById('summary-telp').textContent = inputTelp?.value.trim() || '-';

        const elCatatan = document.getElementById('summary-catatan');
        if (inputCatatan?.value.trim()) {
            elCatatan.textContent = 'Catatan: ' + inputCatatan.value.trim();
            elCatatan.classList.remove('hidden');
        } else {
            elCatatan?.classList.add('hidden');
        }

        // Kendaraan
        const items = containerKendaraan.querySelectorAll('.kendaraan-item');
        let totalKendaraan = 0;
        let rincianHTML = '';
        let kendaraanHTML = '';
        let hasKendaraan = false;

        items.forEach((item, i) => {
            const carId = item.dataset.carId;
            const car = allCars.find(c => c.id == carId);

            // HAPUS TITIK SEBELUM DIHITUNG (Ubah 2 baris ini)
            const rawHarga = item.querySelector('.harga-display').value.replace(/\./g, '');
            const harga = parseFloat(rawHarga) || 0;

            // Sync hidden input (ini dikirim ke backend TANPA TITIK)
            item.querySelector('.harga-input').value = harga;

            if (!car) return;
            hasKendaraan = true;
            const sub = harga * durasi;
            totalKendaraan += sub;

            kendaraanHTML += `
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Kendaraan ${i + 1}</p>
                    <p class="font-medium text-sm text-slate-800">${car.merk} ${car.model}</p>
                    <p class="text-xs text-slate-500 font-mono mt-0.5">${car.plat}</p>
                </div>`;

            rincianHTML += `
                <div class="flex justify-between items-center text-sm text-slate-600">
                    <span>${car.merk} ${car.model} × ${durasi} hari</span>
                    <span class="font-medium">${formatRp(sub)}</span>
                </div>`;
        });

        const summaryKendaraanList = document.getElementById('summary-kendaraan-list');
        if (summaryKendaraanList) {
            summaryKendaraanList.innerHTML = hasKendaraan
                ? kendaraanHTML
                : '<p class="text-sm text-slate-400 italic" id="summary-kendaraan-empty">Belum ada kendaraan dipilih.</p>';
        }

        const elRincian = document.getElementById('summary-rincian');
        if (elRincian) {
            elRincian.innerHTML = rincianHTML || '<p class="text-sm text-slate-400 italic">Belum ada rincian.</p>';
        }

        // Add-ons
        let totalAddons = 0;
        let addonsHTML = '';
        document.querySelectorAll('.addon-item').forEach(addon => {
            const nama = addon.querySelector('[name="addon_nama[]"]').value.trim();

            // HAPUS TITIK SEBELUM DIHITUNG
            const rawNominal = addon.querySelector('.addon-nominal').value.replace(/\./g, '');
            const nominal = parseFloat(rawNominal) || 0;

            if (!nama) return;
            totalAddons += nominal;
            addonsHTML += `
                <div class="flex justify-between items-center text-sm text-slate-600">
                    <span>${nama}</span>
                    <span>${formatRp(nominal)}</span>
                </div>`;
        });

        const addonsWrap = document.getElementById('summary-addons-wrap');
        if (addonsHTML) {
            addonsWrap?.classList.remove('hidden');
            const elAddons = document.getElementById('summary-addons');
            if (elAddons) elAddons.innerHTML = addonsHTML;
        } else {
            addonsWrap?.classList.add('hidden');
        }

        const grand = totalKendaraan + totalAddons;
        const elTotalHarga = document.getElementById('input_total_harga');
        const elTotalAddons = document.getElementById('input_total_addons');
        if (elTotalHarga) elTotalHarga.value = grand;
        if (elTotalAddons) elTotalAddons.value = totalAddons;
        document.getElementById('summary-total').textContent = formatRp(grand);
    }

    // Realtime pelanggan
    document.querySelector('input[name="nama_pelanggan"]')?.addEventListener('input', updateSummary);
    document.querySelector('input[name="kontak_pelanggan"]')?.addEventListener('input', updateSummary);
    document.querySelector('input[name="catatan"]')?.addEventListener('input', updateSummary);

    // ── Preview File Baru (KTP & Penyewa) seperti di Gambar ─────────────────
    function handleFilePreview(inputId, previewContainerId) {
        const fileInput = document.getElementById(inputId);
        const previewContainer = document.getElementById(previewContainerId);

        if (!fileInput || !previewContainer) return;

        // Array penampung file yang sedang aktif dipilih
        let dtFiles = new DataTransfer();

        fileInput.addEventListener('change', function () {
            // Gabungkan file baru dimasukkan ke penampung lama (jika ingin akumulatif)
            // Jika ingin overwrite setiap pilih baru, matikan baris foreach di bawah ini dan langsung gunakan fileInput.files
            Array.from(this.files).forEach(file => {
                dtFiles.items.add(file);
            });

            renderPreviews();
        });

        function renderPreviews() {
            previewContainer.innerHTML = '';

            Array.from(dtFiles.files).forEach((file, index) => {
                // Membuat struktur card sesuai layout gambar yang diinginkan
                const card = document.createElement('div');
                card.className = "relative flex items-center gap-3 p-2 border border-slate-200 rounded-lg bg-white shadow-sm w-full transition-all";

                card.innerHTML = `
                    <div class="shrink-0 w-8 h-8 bg-red-50 text-red-500 rounded-md border border-red-100 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">image</span>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <p class="text-xs font-semibold text-slate-700 truncate" title="${file.name}">${file.name}</p>
                        <span class="text-[10px] text-slate-400 font-medium">${(file.size / 1024).toFixed(1)} KB</span>
                    </div>
                    <button type="button" class="btn-remove-new-file shrink-0 w-7 h-7 flex items-center justify-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors" title="Batal upload">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                `;

                // Event Listener tombol hapus file baru
                card.querySelector('.btn-remove-new-file').addEventListener('click', function () {
                    const newDt = new DataTransfer();
                    Array.from(dtFiles.files).forEach((f, i) => {
                        if (i !== index) newDt.items.add(f); // skip file yang dihapus
                    });
                    dtFiles = newDt;
                    fileInput.files = dtFiles.files; // Sinkronkan ke input file asli
                    renderPreviews(); // Render ulang visualnya
                });

                previewContainer.appendChild(card);
            });

            // Update input file dengan list file yang sudah disaring
            fileInput.files = dtFiles.files;
        }
    }

    // Jalankan fungsi untuk kedua field upload
    handleFilePreview('input-foto-ktp', 'new-preview-ktp');
    handleFilePreview('input-foto-penyewa', 'new-preview-penyewa');

    // ── Prefill mode edit ───────────────────────────────────────────────
    if (IS_EDIT) {
        const prefillEl = document.getElementById('booking-prefill');
        if (prefillEl) {
            const prefill = JSON.parse(prefillEl.textContent || '{}');

            // Kendaraan
            (prefill.cars || []).forEach(({ car_id, harga, foto_kondisi }) => {
                const car = allCars.find(c => c.id == car_id);
                // Tambahkan parameter ketiga: array foto_kondisi
                if (car) addKendaraanCard(car, parseFloat(harga), foto_kondisi || []);
            });

            // Add-ons
            (prefill.addons || []).forEach(({ nama, nominal }) => {
                tambahAddonBaru(nama, nominal);
            });
        }
    }

    // ── Init ────────────────────────────────────────────────────────────
    if (inputMulai?.value && inputSelesai?.value) sinkronDariTanggal();
    else if (inputMulai?.value) inputDurasiHari && (inputDurasiHari.disabled = false);

    checkEmptyKendaraan();
    checkEmptyAddon();
    updateSummary();

    // ── Submit guard ────────────────────────────────────────────────────
    form?.addEventListener('submit', function (e) {
        if (containerKendaraan.querySelectorAll('.kendaraan-item').length === 0) {
            e.preventDefault();
            alert('Tambahkan minimal satu kendaraan sebelum menyimpan pesanan!');
        } else {
            updateSummary();

            // 1. Bersihkan nilai Addons
            document.querySelectorAll('.addon-nominal').forEach(input => {
                input.value = input.value.replace(/\./g, '');
            });

            // 2. Bersihkan nilai DP
            if (inputDp) {
                inputDp.value = inputDp.value.replace(/\./g, '');
            }
        }
    });

    // ── Logika Menampilkan Input DP ──────────────────────────────────────
    const selectStatusPembayaran = document.getElementById('select_status_pembayaran');
    const wrapDp = document.getElementById('wrap-dp');
    const inputDp = document.getElementById('input_dp_amount');

    if (inputDp) {
        // Format saat halaman pertama kali load (menangani nilai old() dari Laravel)
        inputDp.value = formatInputRibuan(inputDp.value);

        // Format saat user mengetik
        inputDp.addEventListener('input', function () {
            this.value = formatInputRibuan(this.value);
        });
    }

    function toggleDpInput() {
        if (selectStatusPembayaran && wrapDp) {
            if (selectStatusPembayaran.value === 'DP') {
                wrapDp.classList.remove('hidden');
                // Jika mau langsung fokus ke input saat DP dipilih:
                inputDp.focus();
            } else {
                wrapDp.classList.add('hidden');
                inputDp.value = 0; // Reset ke 0 jika diganti ke Belum Bayar/Lunas
            }
        }
    }

    // Jalankan saat dropdown diganti
    selectStatusPembayaran?.addEventListener('change', toggleDpInput);

    // Jalankan sekali saat halaman pertama kali dimuat (untuk handle old input)
    toggleDpInput();
});