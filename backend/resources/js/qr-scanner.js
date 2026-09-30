import QrScanner from 'qr-scanner';

document.addEventListener('DOMContentLoaded', function () {
    const video = document.getElementById('qr-video');
    const scanResult = document.getElementById('scan-result');
    const scanResultContainer = document.getElementById('scan-result-container');
    const startBtn = document.getElementById('btn-start');
    const stopBtn = document.getElementById('btn-stop');
    const flashBtn = document.getElementById('btn-flash');
    const cameraSelect = document.getElementById('camera-select');
    const fileInput = document.getElementById('file-input');
    const fileBtn = document.getElementById('btn-file');

    if (!video) return;

    let qrScanner = null;
    let flashOn = false;

    // Cek apakah teks hasil scan adalah URL verifikasi invoice (/verify/{token})
    function getVerifyUrl(text) {
        try {
            const url = new URL(text);
            if (/\/verify\/[A-Za-z0-9]+$/.test(url.pathname)) {
                return url.href;
            }
        } catch (e) {
            // bukan URL yang valid, abaikan
        }
        return null;
    }

    function setResult(text) {
        if (!scanResultContainer) return;
        scanResultContainer.classList.remove('hidden');

        const verifyUrl = getVerifyUrl(text);

        if (verifyUrl) {
            // Hasil scan adalah QR invoice -> tampilkan card khusus + tombol buka verifikasi
            scanResultContainer.innerHTML = `
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2">Hasil Scan</h2>
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 flex items-start gap-3">
                    <span class="material-symbols-outlined text-emerald-600 shrink-0">qr_code_2</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-emerald-800">QR Code Invoice terdeteksi</p>
                        <p class="text-xs text-emerald-600 mt-0.5 break-all">${verifyUrl}</p>
                        <a href="${verifyUrl}"
                            class="inline-flex items-center gap-1.5 mt-3 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
                            <span class="material-symbols-outlined text-[16px]">verified</span>
                            Buka Halaman Verifikasi
                        </a>
                    </div>
                </div>
            `;
            // Otomatis hentikan kamera begitu QR invoice terdeteksi, biar tidak scan berulang
            if (qrScanner && qrScanner.isScanning) {
                qrScanner.stop();
                updateButtons(false);
            }
        } else {
            // Hasil scan bukan QR invoice -> tampilkan mentah seperti biasa (misal untuk keperluan lain)
            scanResultContainer.innerHTML = `
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2">Hasil Scan</h2>
                <div class="bg-slate-50 rounded-lg p-4">
                    <p class="text-slate-900 font-mono text-sm break-all">${text}</p>
                </div>
            `;
        }
    }

    function updateButtons(scannerRunning) {
        if (startBtn) startBtn.classList.toggle('hidden', scannerRunning);
        if (stopBtn) stopBtn.classList.toggle('hidden', !scannerRunning);
    }

    async function startScanner(cameraId) {
        if (qrScanner) qrScanner.destroy();

        qrScanner = new QrScanner(
            video,
            (result) => setResult(result.data),
            {
                returnDetailedScanResult: true,
                highlightScanRegion: true,
                highlightCodeOutline: true,
            }
        );

        if (cameraId) {
            await qrScanner.setCamera(cameraId);
        }

        await qrScanner.start();
        updateButtons(true);

        if (flashBtn) {
            flashBtn.classList.toggle('hidden', !(await qrScanner.hasFlash()));
        }
    }

    if (startBtn) {
        startBtn.addEventListener('click', async () => {
            try {
                await startScanner();
            } catch (err) {
                setResult('Error: ' + err.message);
            }
        });
    }

    if (stopBtn) {
        stopBtn.addEventListener('click', () => {
            if (qrScanner) qrScanner.stop();
            updateButtons(false);
        });
    }

    if (flashBtn) {
        flashBtn.addEventListener('click', async () => {
            if (!qrScanner) return;
            if (flashOn) {
                await qrScanner.turnFlashOff();
                flashOn = false;
                flashBtn.textContent = 'Flash On';
            } else {
                await qrScanner.turnFlashOn();
                flashOn = true;
                flashBtn.textContent = 'Flash Off';
            }
        });
    }

    if (fileInput && fileBtn) {
        fileBtn.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            try {
                const result = await QrScanner.scanImage(file, {
                    returnDetailedScanResult: true,
                });
                setResult(result.data);
            } catch (err) {
                setResult('No QR code found in image');
            }
        });
    }

    if (cameraSelect) {
        async function populateCameras() {
            const cameras = await QrScanner.listCameras(true);
            cameraSelect.innerHTML = '<option value="">Default Camera</option>';
            cameras.forEach((cam) => {
                const opt = document.createElement('option');
                opt.value = cam.id;
                opt.textContent = cam.label || `Camera ${cam.id}`;
                cameraSelect.appendChild(opt);
            });
        }

        cameraSelect.addEventListener('change', () => {
            const val = cameraSelect.value;
            if (qrScanner && qrScanner.isScanning) {
                qrScanner.stop();
                startScanner(val || undefined);
            }
        });

        if (startBtn) {
            populateCameras();
        }
    }

    window.addEventListener('beforeunload', () => {
        if (qrScanner) qrScanner.destroy();
    });
});