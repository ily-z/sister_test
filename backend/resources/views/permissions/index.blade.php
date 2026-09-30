@extends('layouts.app')

@section('content')
<style>
    .role-genealogy { overflow: auto; min-height: 390px; padding: 3rem 3rem 3.5rem; background-color: #f8fafc; background-image: linear-gradient(#e2e8f0 1px, transparent 1px), linear-gradient(90deg, #e2e8f0 1px, transparent 1px); background-size: 24px 24px; }
    .role-genealogy > .role-tree-root { display: flex; justify-content: center; min-width: max-content; }
    .role-genealogy .role-children { display: flex; justify-content: center; gap: 2rem; position: relative; padding-top: 3.5rem; }
    .role-genealogy .role-children::before { content: ''; position: absolute; top: 1.25rem; left: 10%; right: 10%; border-top: 2px solid #94a3b8; }
    .role-genealogy .role-node { position: relative; display: flex; flex-direction: column; align-items: center; }
    .role-genealogy .role-node > .role-item { position: relative; width: 220px; min-height: 86px; z-index: 1; border-width: 2px; border-color: var(--role-color, #2563eb); border-left-width: 6px; background: rgba(255,255,255,.98); box-shadow: 0 8px 18px rgba(15, 23, 42, .1); transition: border-color .2s, transform .2s, box-shadow .2s; }
    .role-genealogy .role-node > .role-item::before { content: ''; position: absolute; top: -2.35rem; left: 50%; height: 2.35rem; border-left: 2px solid #94a3b8; }
    .role-genealogy .role-node > .role-item::after { content: ''; position: absolute; top: -2.5rem; left: calc(50% - 4px); width: 8px; height: 8px; border-right: 2px solid #64748b; border-bottom: 2px solid #64748b; transform: rotate(45deg); background: #f8fafc; }
    .role-genealogy > .role-tree-root > .role-node > .role-item::before, .role-genealogy > .role-tree-root > .role-node > .role-item::after { display: none; }
    .role-genealogy .role-node > .role-item:hover { border-color: #3b82f6; transform: translateY(-3px); box-shadow: 0 12px 24px rgba(37, 99, 235, .18); }
    .role-genealogy .role-node > .role-item .role-name { color: #0f172a; font-size: .9rem; font-weight: 700; }
    .role-genealogy .role-node > .role-item .role-parent { color: #64748b; font-size: .72rem; }
    .role-genealogy .role-children:empty { display: none; }
    .role-genealogy .role-children > .role-node { flex: 0 0 auto; }
    .role-genealogy .role-item .material-symbols-outlined { font-size: 20px; }
    .role-drag-handle { cursor: grab; user-select: none; }
    .role-drag-handle:active { cursor: grabbing; }
    .role-item.is-dragging { opacity: .45; transform: scale(.98); }
    .role-item.is-drop-target { border-style: dashed; border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37, 99, 235, .12); }
    .role-drop-preview { width: 220px; min-height: 86px; border: 2px dashed #93c5fd; border-left: 6px solid #93c5fd; border-radius: .5rem; background: rgba(219, 234, 254, .38); box-shadow: inset 0 0 0 1px rgba(147, 197, 253, .25); animation: role-preview-pulse 1.2s ease-in-out infinite; }
    @keyframes role-preview-pulse { 0%, 100% { opacity: .5; } 50% { opacity: .95; } }
    .role-genealogy.is-dragging .role-link { pointer-events: none; }
    .permission-group-toggle { width: 100%; cursor: pointer; text-align: left; }
    .permission-group-toggle .group-arrow { transition: transform .25s ease; }
    .permission-group.is-open .group-arrow { transform: rotate(90deg); }
    .permission-group-content { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .3s ease; }
    .permission-group.is-open .permission-group-content { grid-template-rows: 1fr; }
    .permission-group-content > div { min-height: 0; overflow: hidden; }
    .permission-editor { transition: opacity .2s ease, transform .2s ease; }
    .permission-editor.is-loading { opacity: .45; transform: translateY(4px); pointer-events: none; }
    @media (max-width: 640px) {
        .role-genealogy { min-height: 360px; padding-inline: 1rem; }
        .role-genealogy .role-children { gap: .75rem; }
        .role-genealogy .role-node > .role-item { width: 180px; }
    }
</style>
<div class="px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Role & Permissions</h2>
            <p class="mt-1 text-sm text-slate-500">Susun hirarki role, lalu atur permission tiap role.</p>
        </div>
        @if ($canCreate)
            <div class="flex flex-wrap gap-2">
                <button type="button" data-modal-open="role-modal" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"><span class="material-symbols-outlined text-[18px]">add</span>Role Baru</button>
            </div>
        @endif
    </div>
    @if (session('status'))<div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif

    <div class="sticky top-16 z-30 mt-5 rounded-xl border border-slate-200 bg-white/95 p-2 shadow-sm backdrop-blur">
        <div class="flex items-center gap-2 overflow-x-auto">
            <span class="shrink-0 px-2 text-xs font-bold uppercase tracking-wide text-slate-400">Role</span>
            @foreach ($roles as $role)
                <a href="{{ route('admin.permissions.index', ['role' => $role->slug]) }}" data-role-switch="{{ $role->slug }}" class="role-link shrink-0 rounded-lg border px-3 py-2 text-sm font-semibold transition-colors {{ $selectedRole->slug === $role->slug ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-slate-200 bg-white text-slate-600 hover:border-brand-200 hover:text-brand-700' }}">{{ $role->nama }}</a>
            @endforeach
        </div>
    </div>

    <div class="mt-6 space-y-6">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-4"><h3 class="font-bold text-slate-900">Pohon Hirarki Role</h3><p class="mt-1 text-xs text-slate-500">Tarik role ke role lain untuk menjadikannya child.</p></div>
            <div id="role-list" class="role-genealogy">
                <div class="role-tree-root">
                @include('permissions._role-tree', ['treeRoles' => $roleTree, 'allRoles' => $allRoles, 'selectedRole' => $selectedRole, 'canCreate' => $canCreate])
                </div>
            </div>
        </section>

        <form id="permission-editor" method="POST" action="{{ route('admin.permissions.update') }}" data-color-url="{{ route('admin.permissions.roles.color', $selectedRole->slug) }}" class="permission-editor rounded-xl border border-slate-200 bg-white shadow-sm">
            @csrf @method('PUT') <input type="hidden" name="role" value="{{ $selectedRole->slug }}">
            <div class="flex items-start justify-between border-b border-slate-200 p-4"><div><h3 id="permission-role-name" class="font-bold text-slate-900">{{ $selectedRole->nama }}</h3><p class="mt-1 text-sm text-slate-500">Permission berlaku untuk semua user dengan role ini.</p></div><span id="permission-role-parent" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Parent {{ $selectedRole->parent?->nama ?? 'Root' }}</span></div>
            @if ($canCreate)
                <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 px-4 py-3"><label for="warna" class="text-sm font-semibold text-slate-700">Warna outline role</label><input id="warna" type="color" value="{{ $selectedRole->warna ?: '#2563eb' }}" class="h-9 w-14 cursor-pointer rounded border border-slate-300 bg-white p-1"><input id="warna-hex" name="warna" type="text" value="{{ $selectedRole->warna ?: '#2563eb' }}" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7" class="w-28 rounded-lg border-slate-300 px-3 py-2 font-mono text-sm uppercase" aria-label="Kode warna HEX"><span class="text-xs text-slate-500">HEX, contoh #2563EB</span><span id="warna-save-state" class="text-xs font-semibold text-emerald-600"></span></div>
            @endif
            <div class="space-y-4 p-4">
                @foreach ($permissionGroups as $group)
                    <section class="permission-group overflow-hidden rounded-xl border border-slate-200">
                        <button type="button" class="permission-group-toggle flex items-center gap-3 bg-slate-50 px-4 py-3" aria-expanded="false"><span class="group-arrow material-symbols-outlined text-slate-400">chevron_right</span><span class="material-symbols-outlined text-brand-600">{{ $group['icon'] }}</span><h4 class="text-sm font-bold text-slate-800">{{ $group['name'] }}</h4><span class="ml-auto text-xs text-slate-500">{{ $group['items']->count() }} aksi</span></button>
                        <div class="permission-group-content"><div class="grid gap-2 p-3 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($group['items'] as $permission)
                                @php($canAssign = in_array($permission->id, $assignable, true))
                                @php($permAction = \Illuminate\Support\Str::contains($permission->nama_permission, '.') ? \Illuminate\Support\Str::afterLast($permission->nama_permission, '.') : null)
                                <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-3 {{ $canAssign ? 'hover:border-brand-300 hover:bg-brand-50/40' : 'bg-slate-50 opacity-60' }}"><input type="checkbox" data-permission="{{ $permission->id }}" data-perm-action="{{ $permAction }}" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $assigned, true)) @disabled(! $canAssign) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"><span class="text-sm font-semibold text-slate-700">{{ $group['permissions'][$permission->nama_permission] }}</span></label>
                            @endforeach
                        </div></div>
                    </section>
                @endforeach
            </div>
            <div class="flex justify-end border-t border-slate-200 p-4"><button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan Permission</button></div>
        </form>
    </div>

    @if ($canCreate)
        <div id="role-modal" hidden class="permission-modal fixed inset-0 z-50 items-center justify-center bg-slate-900/40 p-4"><div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl"><h3 id="role-modal-title" class="text-lg font-bold">Buat Role Baru</h3><form id="role-form" method="POST" action="{{ route('admin.permissions.roles.store') }}" class="mt-4 space-y-3">@csrf<input id="role-form-method" type="hidden" name="_method" value="POST"><input id="role-name-input" name="nama" required placeholder="Nama role" class="w-full rounded-lg border-slate-300"><select id="role-parent-input" name="parent_slug" required class="w-full rounded-lg border-slate-300">@foreach($allRoles as $role)<option value="{{ $role->slug }}">Di bawah {{ $role->nama }}</option>@endforeach</select><div class="flex flex-wrap items-center gap-3"><label for="role-warna" class="text-sm font-semibold text-slate-700">Warna</label><input id="role-warna" type="color" value="#8b5cf6" class="h-9 w-14 cursor-pointer rounded border border-slate-300 bg-white p-1"><input id="role-warna-hex" name="warna" type="text" value="#8b5cf6" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7" class="w-28 rounded-lg border-slate-300 px-3 py-2 font-mono text-sm uppercase" aria-label="Kode warna HEX"></div><div class="flex justify-end gap-2"><button type="button" data-modal-close class="rounded-lg border px-4 py-2 text-sm">Batal</button><button id="role-form-submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Buat Role</button></div></form></div></div>
        </div>
    @endif
</div>

<script>
const roleParentMap = Object.fromEntries([...document.querySelectorAll('[data-role]')].map(card => [card.dataset.role, card.dataset.parent || null]));
const isRoleDescendant = (candidate, ancestor) => {
    const visited = new Set();
    let parent = roleParentMap[candidate];
    while (parent && !visited.has(parent)) {
        if (parent === ancestor) return true;
        visited.add(parent);
        parent = roleParentMap[parent];
    }
    return false;
};
document.querySelectorAll('[data-modal-open]').forEach(button => button.addEventListener('click', () => { const modal = document.getElementById(button.dataset.modalOpen); if (button.dataset.modalOpen === 'role-modal') { document.getElementById('role-modal-title').textContent = 'Buat Role Baru'; document.getElementById('role-form-submit').textContent = 'Buat Role'; document.getElementById('role-form-method').value = 'POST'; document.getElementById('role-form').action = '{{ route('admin.permissions.roles.store') }}'; document.querySelectorAll('#role-parent-input option').forEach(option => option.disabled = false); } modal.hidden = false; modal.classList.add('flex'); }));
document.querySelectorAll('[data-modal-close]').forEach(button => button.addEventListener('click', () => { const modal = button.closest('.permission-modal'); modal.hidden = true; modal.classList.remove('flex'); }));
document.querySelectorAll('[data-role-edit]').forEach(button => button.addEventListener('click', event => {
    event.preventDefault();
    const card = button.closest('[data-role]');
    const slug = button.dataset.roleEdit;
    const form = document.getElementById('role-form');
    document.getElementById('role-modal-title').textContent = 'Edit Role';
    document.getElementById('role-form-submit').textContent = 'Simpan Perubahan';
    document.getElementById('role-name-input').value = card.querySelector('.role-name')?.textContent.trim() || '';
    const parentInput = document.getElementById('role-parent-input');
    [...parentInput.options].forEach(option => { option.disabled = option.value === slug || isRoleDescendant(option.value, slug); });
    parentInput.value = card.dataset.parent || 'owner';
    document.getElementById('role-warna').value = getComputedStyle(card).getPropertyValue('--role-color').trim() || '#2563eb';
    document.getElementById('role-warna-hex').value = document.getElementById('role-warna').value.toUpperCase();
    document.getElementById('role-form-method').value = 'PATCH';
    form.action = `{{ url('/permissions/roles') }}/${slug}`;
    const modal = document.getElementById('role-modal');
    modal.hidden = false;
    modal.classList.add('flex');
}));
document.addEventListener('click', event => {
    const groupButton = event.target.closest('.permission-group-toggle');
    if (groupButton) {
        const group = groupButton.closest('.permission-group');
        const open = group.classList.toggle('is-open');
        groupButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
});
document.addEventListener('input', event => {
    const editor = document.getElementById('permission-editor');
    const card = document.querySelector(`[data-role="${editor?.querySelector('[name=role]')?.value}"]`);
    if (event.target.id === 'warna' && card) { document.getElementById('warna-hex').value = event.target.value.toUpperCase(); card.style.setProperty('--role-color', event.target.value); }
    if (event.target.id === 'warna-hex' && /^#[0-9a-f]{6}$/i.test(event.target.value.trim()) && card) { document.getElementById('warna').value = event.target.value; card.style.setProperty('--role-color', event.target.value); }
});
document.addEventListener('change', event => {
    if (!['warna', 'warna-hex'].includes(event.target.id)) return;
    const value = event.target.value.trim();
    if (!/^#[0-9a-f]{6}$/i.test(value)) return;
    const editor = document.getElementById('permission-editor');
    fetch(editor.dataset.colorUrl, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: JSON.stringify({ warna: value }) }).then(response => { if (!response.ok) throw new Error('save failed'); document.getElementById('warna-save-state').textContent = 'Tersimpan'; setTimeout(() => document.getElementById('warna-save-state').textContent = '', 1800); }).catch(() => document.getElementById('warna-save-state').textContent = 'Gagal menyimpan');
});
let roleRequestController = null;
let roleRequestId = 0;
document.querySelectorAll('.role-link').forEach(link => link.addEventListener('click', async event => {
    event.preventDefault();
    const editor = document.getElementById('permission-editor');
    const role = new URL(link.href).searchParams.get('role');
    if (editor.querySelector('[name="role"]')?.value === role) return;
    roleRequestController?.abort();
    roleRequestController = new AbortController();
    const requestId = ++roleRequestId;
    editor.classList.add('is-loading');
    try {
        const response = await fetch(`{{ route('admin.permissions.data') }}?role=${encodeURIComponent(role)}`, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) throw new Error('role load failed');
        const payload = await response.json();
        if (requestId !== roleRequestId) return;
        document.getElementById('permission-role-name').textContent = payload.role.nama;
        document.getElementById('permission-role-parent').textContent = `Parent ${payload.role.parent}`;
        editor.querySelector('[name="role"]').value = payload.role.slug;
        editor.dataset.colorUrl = `{{ url('/permissions/roles') }}/${payload.role.slug}/color`;
        const colorInput = editor.querySelector('#warna');
        const hexInput = editor.querySelector('#warna-hex');
        if (colorInput && hexInput) { colorInput.value = payload.role.warna; hexInput.value = payload.role.warna.toUpperCase(); }
        document.querySelectorAll('[data-permission]').forEach(input => { input.checked = payload.assigned.includes(Number(input.dataset.permission)); });
        document.querySelectorAll('[data-role]').forEach(card => { const active = card.dataset.role === role; card.classList.toggle('border-brand-300', active); card.classList.toggle('bg-brand-50', active); });
        document.querySelectorAll('[data-role-switch]').forEach(button => { const active = button.dataset.roleSwitch === role; button.classList.toggle('border-brand-300', active); button.classList.toggle('bg-brand-50', active); button.classList.toggle('text-brand-700', active); button.classList.toggle('border-slate-200', !active); button.classList.toggle('bg-white', !active); button.classList.toggle('text-slate-600', !active); });
        history.pushState({}, '', link.href);
        editor.classList.remove('is-loading');
    } catch (error) {
        if (error.name === 'AbortError') return;
        window.location.href = link.href;
    } finally {
        if (requestId === roleRequestId) editor.classList.remove('is-loading');
    }
}));
const newRoleColor = document.getElementById('role-warna');
const newRoleHex = document.getElementById('role-warna-hex');
if (newRoleColor && newRoleHex) {
    newRoleColor.addEventListener('input', event => newRoleHex.value = event.target.value.toUpperCase());
    newRoleHex.addEventListener('input', event => { const value = event.target.value.trim(); if (/^#[0-9a-f]{6}$/i.test(value)) newRoleColor.value = value; });
}
let draggedRole = null;
let draggedNode = null;
let previewNode = null;
const roleDiagram = document.getElementById('role-list');

function clearRolePreview() {
    document.querySelectorAll('.is-drop-target').forEach(card => card.classList.remove('is-drop-target'));
    document.querySelectorAll('.is-dragging').forEach(card => card.classList.remove('is-dragging'));
    previewNode?.remove();
    previewNode = null;
    roleDiagram?.classList.remove('is-dragging');
}

document.querySelectorAll('[data-drag-handle]').forEach(handle => {
    handle.addEventListener('dragstart', event => {
        draggedRole = handle.closest('[data-role]');
        draggedNode = handle.closest('[data-role-node]');
        draggedRole.classList.add('is-dragging');
        roleDiagram.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', draggedRole.dataset.role);
    });
    handle.addEventListener('dragend', clearRolePreview);
});

document.querySelectorAll('[data-role]').forEach(item => {
    item.addEventListener('dragover', event => {
        event.preventDefault();
        if (!draggedRole || draggedRole === item) return;
        const targetNode = item.closest('[data-role-node]');
        if (targetNode === draggedNode || draggedNode.contains(targetNode)) return;
        document.querySelectorAll('.is-drop-target').forEach(card => card.classList.remove('is-drop-target'));
        item.classList.add('is-drop-target');
        if (!previewNode || previewNode.dataset.target !== item.dataset.role) {
            previewNode?.remove();
            previewNode = document.createElement('div');
            previewNode.className = 'role-drop-preview';
            previewNode.dataset.target = item.dataset.role;
            targetNode.querySelector(':scope > .role-children').appendChild(previewNode);
        }
    });
    item.addEventListener('drop', event => {
        event.preventDefault();
        if (!draggedRole || draggedRole === item) return clearRolePreview();
        const targetNode = item.closest('[data-role-node]');
        if (targetNode === draggedNode || draggedNode.contains(targetNode)) return clearRolePreview();
        const targetChildren = targetNode.querySelector(':scope > .role-children');
        const isDirectChildSwap = item.dataset.parent === draggedRole.dataset.role;
        let body;

        previewNode?.remove();
        if (isDirectChildSwap) {
            const draggedParent = draggedNode.parentElement;
            targetNode.remove();
            draggedParent.appendChild(targetNode);
            targetChildren.appendChild(draggedNode);
            targetNode.querySelector(':scope > .role-item').dataset.parent = draggedRole.dataset.parent || '';
            draggedRole.dataset.parent = item.dataset.role;
        } else {
            targetChildren.appendChild(draggedNode);
            draggedRole.dataset.parent = item.dataset.role;
        }

        const roles = [...document.querySelectorAll('[data-role]')].map(role => ({ slug: role.dataset.role, parent_slug: role.dataset.parent || null }));
        body = isDirectChildSwap
            ? { roles, swap: [draggedRole.dataset.role, item.dataset.role] }
            : { roles };

        const targetName = item.querySelector('.role-name')?.textContent.trim() || item.dataset.role;
        const parentLabel = draggedNode.querySelector('.role-parent');
        if (parentLabel) parentLabel.textContent = `Parent: ${targetName}`;
        fetch('{{ route('admin.permissions.reorder') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: JSON.stringify(body) }).then(response => { if (!response.ok) window.location.reload(); });
        clearRolePreview();
    });
});

// ── Aturan: "Lihat" (view) adalah prasyarat untuk Tambah/Edit/Hapus ──
// - Centang salah satu dari Tambah/Edit/Hapus -> "Lihat" otomatis ikut tercentang.
// - Lepas centang "Lihat" -> semua Tambah/Edit/Hapus di grup yang sama ikut lepas
//   (termasuk saat semuanya sudah menyala lalu "Lihat" dimatikan).
// Berlaku otomatis untuk semua grup permission, tidak perlu di-hardcode per modul.
function initPermissionCascade() {
    document.querySelectorAll('.permission-group').forEach(groupEl => {
        const boxes = [...groupEl.querySelectorAll('[data-permission]')];
        const viewBox = boxes.find(cb => cb.dataset.permAction === 'view');
        if (!viewBox) return;

        const crudBoxes = boxes.filter(cb => cb !== viewBox && cb.dataset.permAction);

        crudBoxes.forEach(cb => {
            cb.addEventListener('change', () => {
                if (cb.checked && !viewBox.checked && !viewBox.disabled) {
                    viewBox.checked = true;
                }
            });
        });

        viewBox.addEventListener('change', () => {
            if (!viewBox.checked) {
                crudBoxes.forEach(cb => {
                    if (!cb.disabled) cb.checked = false;
                });
            }
        });
    });
}
initPermissionCascade();
</script>
@endsection