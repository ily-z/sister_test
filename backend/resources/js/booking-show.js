/**
 * booking-show.js
 * Script khusus untuk menangani interaksi di halaman show.blade.php (Detail Pesanan)
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── 1. Helper: Format Ribuan Otomatis ──────────────────────────────
    function formatRibuan(angka) {
        if (!angka) return '';
        let number_string = angka.toString().replace(/[^0-9]/g, '');
        return number_string.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Fungsi universal untuk menghubungkan input text (tampilan) & hidden (server)
    function setupRibuanInput(inputId, hiddenId) {
        const inputEl = document.getElementById(inputId);
        const hiddenEl = document.getElementById(hiddenId);

        if (inputEl && hiddenEl) {
            // Format saat load pertama kali
            inputEl.value = formatRibuan(inputEl.value);

            // Format saat user mengetik
            inputEl.addEventListener('input', function () {
                let rawValue = this.value.replace(/[^0-9]/g, '');
                hiddenEl.value = rawValue; // Simpan angka murni untuk dikirim ke backend
                this.value = formatRibuan(rawValue); // Tampilkan dengan titik
            });
        }
    }

    // ── 2. Toggle DP input di modal Update Status ──────────────────
    const modalSelectPembayaran = document.getElementById('modal-select-pembayaran');
    const modalWrapDp = document.getElementById('modal-wrap-dp');
    const modalWrapMetode = document.getElementById('modal-wrap-metode');
    const modalInputDp = document.getElementById('modal-input-dp');
    const realModalDp = document.getElementById('real-modal-dp'); // hidden input

    function syncModalFields() {
        if (!modalSelectPembayaran) return;

        const val = modalSelectPembayaran.value;
        const isDP = val === 'DP';
        const needPay = val === 'DP' || val === 'Lunas';

        modalWrapDp?.classList.toggle('hidden', !isDP);
        modalWrapMetode?.classList.toggle('hidden', !needPay);

        if (modalInputDp) {
            modalInputDp.required = isDP;
            if (!isDP) {
                modalInputDp.value = '';
                if (realModalDp) realModalDp.value = 0;
            }
            if (isDP) modalInputDp.focus();
        }
    }

    modalSelectPembayaran?.addEventListener('change', syncModalFields);

    // ── 3. Toggle form tambah add-on ───────────────────────────────
    document.getElementById('btn-toggle-addon-form')?.addEventListener('click', function() {
        const form = document.getElementById('form-tambah-addon');
        form.classList.toggle('hidden');
        if (!form.classList.contains('hidden')) {
            form.querySelector('input[name="addon_nama"]')?.focus();
        }
    });

    document.getElementById('btn-cancel-addon-form')?.addEventListener('click', function() {
        document.getElementById('form-tambah-addon')?.classList.add('hidden');
    });

    // ── 4. Toggle quick-pay DP input ───────────────────────────────
    const quickPayStatus = document.getElementById('quick-pay-status');
    const quickDpWrap = document.getElementById('quick-dp-wrap');
    const quickDpAmount = document.getElementById('quick-dp-amount');

    function syncQuickDp() {
        if (!quickPayStatus) return;
        const isDP = quickPayStatus.value === 'DP';
        if (quickDpWrap) quickDpWrap.classList.toggle('hidden', !isDP);
        if (quickDpAmount) quickDpAmount.required = isDP;
    }

    quickPayStatus?.addEventListener('change', syncQuickDp);
    syncQuickDp(); // Init saat halaman dimuat

    // ── 5. Logika Modal Refund (Limit Maksimal & Format) ───────────
    const formattedRefundInput = document.getElementById('formatted-refund-amount');
    const realRefundInput = document.getElementById('real-refund-amount');
    const refundErrorMsg = document.getElementById('refund-error-msg');

    if (formattedRefundInput && realRefundInput) {
        // Panggil setup dasar
        setupRibuanInput('formatted-refund-amount', 'real-refund-amount');

        // Tambahkan validasi batas maksimal khusus untuk refund
        const maxRefund = parseInt(formattedRefundInput.dataset.max) || 0;

        formattedRefundInput.addEventListener('input', function() {
            let rawValue = this.value.replace(/[^0-9]/g, '');
            if (rawValue !== '') {
                let numericValue = parseInt(rawValue, 10);

                if (numericValue > maxRefund) {
                    numericValue = maxRefund;
                    refundErrorMsg?.classList.remove('hidden');
                } else {
                    refundErrorMsg?.classList.add('hidden');
                }

                realRefundInput.value = numericValue;
                this.value = formatRibuan(numericValue.toString());
            } else {
                refundErrorMsg?.classList.add('hidden');
            }
        });
    }

    // ── 6. Inisialisasi Format Ribuan untuk Form Lainnya ───────────
    // Pastikan kamu menambahkan input text dan hidden input dengan ID ini di show.blade.php
    setupRibuanInput('modal-input-dp', 'real-modal-dp'); // Untuk modal Update Status
    setupRibuanInput('quick-dp-amount', 'real-quick-dp'); // Untuk Quick Pay
    setupRibuanInput('addon-nominal-text', 'addon-nominal-real'); // Untuk Tambah Addon
});