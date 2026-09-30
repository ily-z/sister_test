import SignaturePad from 'signature_pad';

/**
 * Modal tanda tangan invoice (pakai library signature_pad).
 *
 * Alur:
 *   JRBSignature.open({ actionUrl, subtitle, focusRole, blocks })
 *   -> user menggambar di kanvas Pelanggan dan/atau Finance
 *   -> klik "Simpan & Terapkan", form POST ke actionUrl
 *   -> server menyimpan PNG tiap blok yang digambar + render ulang PDF invoice
 *
 * Data URL PNG dikirim lewat input tersembunyi
 * signatures[role][signature_data]. Canvas sengaja dibiarkan transparan supaya
 * server bisa memangkas ruang kosongnya.
 *
 * Dua blok (pelanggan + finance) sengaja dikumpulkan dalam satu submission
 * supaya tidak pernah ada keadaan "sudah separuh" yang ikut terarsip: PDF hanya
 * digenerate ulang sekali, setelah semua blok yang digambar berhasil disimpan.
 */

const PEN_COLOR = '#1e293b';
const MAX_DPR = 2;
const ROLES = ['pelanggan', 'finance'];

const state = {
    modal: null,
    form: null,
    error: null,
    saveBtn: null,
    saveLabel: null,
    title: null,
    subtitle: null,
    blocks: {},
    config: null,
    ready: false,
};

function collect() {
    state.modal = document.getElementById('jrb-sig-modal');
    if (!state.modal) return false;

    state.form = state.modal.querySelector('#jrb-sig-form');
    state.error = state.modal.querySelector('#jrb-sig-error');
    state.saveBtn = state.modal.querySelector('#jrb-sig-save');
    state.saveLabel = state.modal.querySelector('[data-sig-save-label]');
    state.title = state.modal.querySelector('#jrb-sig-title');
    state.subtitle = state.modal.querySelector('#jrb-sig-subtitle');

    state.blocks = {};

    for (const role of ROLES) {
        const root = state.modal.querySelector(`[data-sig-block="${role}"]`);
        if (!root) continue;

        state.blocks[role] = {
            role,
            root,
            label: root.querySelector('[data-sig-title]'),
            badge: root.querySelector('[data-sig-badge]'),
            existingTag: root.querySelector('[data-sig-existing-tag]'),
            previewRow: root.querySelector('[data-sig-preview-row]'),
            preview: root.querySelector('[data-sig-preview]'),
            previewMeta: root.querySelector('[data-sig-preview-meta]'),
            name: root.querySelector('[data-sig-name]'),
            canvas: root.querySelector('[data-sig-canvas]'),
            hidden: state.modal.querySelector(`input[data-sig-data][name="signatures[${role}][signature_data]"]`),
            clearBtn: root.querySelector('[data-sig-clear]'),
            error: root.querySelector('[data-sig-block-error]'),
            replaceRow: root.querySelector('[data-sig-replace-row]'),
            replace: root.querySelector('[data-sig-replace]'),
            pad: null,
        };
    }

    return Object.keys(state.blocks).length > 0;
}

/**
 * Samakan ukuran bitmap canvas dengan ukuran CSS-nya dikali devicePixelRatio,
 * lalu skalakan konteks gambar.
 *
 * signature_pad menghitung koordinat coretan dalam piksel CSS (dari
 * getBoundingClientRect), bukan piksel device. Karena itu konteks 2D diskalakan
 * agar coretan tetap presisi di layar retina tanpa koordinatnya bergeser.
 * Bitmap yang lebih besar ini juga membuat PNG yang dikirim ke server lebih tajam.
 *
 * Penting: canvas di dalam modal tersembunyi punya getBoundingClientRect() 0x0,
 * jadi sizeCanvas() hanya boleh dipanggil setelah modal terlihat.
 */
function sizeCanvas(canvas) {
    const ratio = Math.min(Math.max(window.devicePixelRatio || 1, 1), MAX_DPR);
    const rect = canvas.getBoundingClientRect();

    if (!rect.width || !rect.height) return false;

    canvas.width = Math.round(rect.width * ratio);
    canvas.height = Math.round(rect.height * ratio);

    // Menulis canvas.width/height me-reset state konteks, termasuk transform.
    canvas.getContext('2d').setTransform(ratio, 0, 0, ratio, 0, 0);

    return true;
}

function ensurePad(block) {
    if (block.pad) return;

    sizeCanvas(block.canvas);

    block.pad = new SignaturePad(block.canvas, {
        minWidth: 0.7,
        maxWidth: 2.4,
        penColor: PEN_COLOR,
        throttle: 16,
    });
}

function showError(message) {
    if (!state.error) return;
    state.error.textContent = message;
    state.error.classList.toggle('hidden', !message);
}

function showBlockError(block, message) {
    if (!block.error) return;
    block.error.textContent = message;
    block.error.classList.toggle('hidden', !message);
}

function clearBlockErrors() {
    showError('');
    for (const role of ROLES) {
        if (state.blocks[role]) showBlockError(state.blocks[role], '');
    }
}

/**
 * Terapkan config invoice yang sedang dibuka ke kedua blok.
 */
function hydrateBlocks(config) {
    const blocks = config.blocks || {};

    for (const role of ROLES) {
        const block = state.blocks[role];
        if (!block) continue;

        const info = blocks[role] || {};

        if (block.label) block.label.textContent = info.roleLabel || 'Penanda tangan';
        if (block.name) block.name.value = info.signerName || '';

        const existingName = info.existingName || '';
        const hasExisting = Boolean(existingName);

        if (block.badge) {
            block.badge.textContent = hasExisting
                ? `Saat ini: ${existingName}`
                : 'Belum ditandatangani';
        }

        if (block.existingTag) block.existingTag.classList.toggle('hidden', !hasExisting);

        if (block.previewRow && block.preview && block.previewMeta) {
            if (hasExisting && info.existingUrl) {
                block.preview.src = info.existingUrl;
                block.previewMeta.textContent = info.existingAt
                    ? `${existingName} · ${info.existingAt}`
                    : existingName;
                block.previewRow.classList.remove('hidden');
            } else {
                block.preview.removeAttribute('src');
                block.previewMeta.textContent = '';
                block.previewRow.classList.add('hidden');
            }
        }

        if (block.replaceRow) block.replaceRow.classList.toggle('hidden', !hasExisting);
        if (block.replace) block.replace.checked = false;
        if (block.hidden) block.hidden.value = '';
    }
}

function open(options) {
    if (!state.ready && !collect()) return;

    state.config = options || {};

    state.form.action = state.config.actionUrl || state.form.action;
    if (state.subtitle) state.subtitle.textContent = state.config.subtitle || '';

    clearBlockErrors();
    hydrateBlocks(state.config);

    state.modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Kanvas baru bisa diukur setelah modal tampil.
    const focusRole = state.config.focusRole || ROLES[0];

    for (const role of ROLES) {
        const block = state.blocks[role];
        if (!block) continue;

        ensurePad(block);
        sizeCanvas(block.canvas);
        block.pad.clear();
    }

    const focusBlock = state.blocks[focusRole] || state.blocks[ROLES[0]];
    if (focusBlock?.name) focusBlock.name.focus();
}

function close() {
    if (!state.modal) return;

    state.modal.classList.add('hidden');
    document.body.style.overflow = '';

    for (const role of ROLES) {
        const block = state.blocks[role];
        if (!block) continue;
        block.pad && block.pad.clear();
        if (block.hidden) block.hidden.value = '';
    }

    clearBlockErrors();
}

/**
 * Kumpulkan blok yang benar-benar digambar, lalu isi input tersembunyi.
 * Blok yang dikosongkan sengaja tidak dikirim agar tanda tangan lamanya utuh.
 */
function collectDrawings() {
    const drawn = [];

    for (const role of ROLES) {
        const block = state.blocks[role];
        if (!block) continue;

        if (block.pad && !block.pad.isEmpty()) {
            if (block.hidden) block.hidden.value = block.pad.toDataURL('image/png');
            drawn.push(block);
        } else if (block.hidden) {
            block.hidden.value = '';
        }
    }

    return drawn;
}

function handleSubmit(event) {
    clearBlockErrors();

    const drawn = collectDrawings();

    if (!drawn.length) {
        showError('Isi minimal satu blok tanda tangan sebelum menyimpan.');
        return;
    }

    let firstInvalid = null;

    for (const block of drawn) {
        const name = (block.name.value || '').trim();

        if (!name) {
            showBlockError(block, 'Nama penanda tangan wajib diisi.');
            firstInvalid = firstInvalid || block;
            continue;
        }

        // Mengganti blok yang sudah terisi butuh konfirmasi eksplisit
        // (server juga memaksa hal ini lewat confirm_replace).
        if (!block.replaceRow.classList.contains('hidden') && !block.replace.checked) {
            showBlockError(block, 'Centang konfirmasi untuk mengganti tanda tangan yang sudah ada.');
            firstInvalid = firstInvalid || block;
        }
    }

    if (firstInvalid) {
        event.preventDefault();
        firstInvalid.name.focus();
        return;
    }

    showError('');
    state.saveBtn.disabled = true;
    if (state.saveLabel) state.saveLabel.textContent = 'Menyimpan…';
}

function handleKeydown(event) {
    if (event.key === 'Escape' && state.modal && !state.modal.classList.contains('hidden')) {
        close();
    }
}

function init() {
    if (!collect()) return;
    state.ready = true;

    for (const role of ROLES) {
        const block = state.blocks[role];
        if (!block) continue;

        block.clearBtn.addEventListener('click', () => {
            block.pad && block.pad.clear();
            if (block.hidden) block.hidden.value = '';
            showBlockError(block, '');
        });

        // Menggambar pada blok yang sudah terisi berarti intendsToReplace,
        // jadi checkbox konfirmasi langsung aktifkan supaya tidak lupa.
        block.canvas.addEventListener('pointerdown', () => {
            if (!block.replaceRow.classList.contains('hidden') && !block.replace.checked) {
                block.replace.checked = true;
            }
        });
    }

    state.modal.querySelectorAll('[data-sig-cancel]').forEach((btn) => btn.addEventListener('click', close));
    state.modal.querySelector('#jrb-sig-backdrop').addEventListener('click', close);
    state.form.addEventListener('submit', handleSubmit);
    document.addEventListener('keydown', handleKeydown);

    // Set ulang ukuran canvas hanya saat modal terbuka dan blok itu masih kosong:
    // mengubah canvas.width menghapus seluruh coretan, jadi jangan dilakukan
    // setelah user menggambar.
    window.addEventListener('resize', () => {
        if (!state.modal || state.modal.classList.contains('hidden')) return;

        for (const role of ROLES) {
            const block = state.blocks[role];
            if (!block || !block.pad) continue;
            if (!block.pad.isEmpty()) continue;
            sizeCanvas(block.canvas);
        }
    });
}

window.JRBSignature = { open, close, init };

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
