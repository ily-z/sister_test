@extends('layouts.app')

@section('content')
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-start sm:items-center justify-between gap-4 flex-col sm:flex-row">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Manajement User</h2>
                <p class="text-sm text-slate-500 mt-1">Kelola akun pengguna dan role akses.</p>
            </div>

            <div class="flex items-center gap-2">
                @can('managePermissions')
                    <a href="{{ route('admin.permissions.index') }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <span class="material-symbols-outlined text-[20px]">tune</span>
                        Permission Role
                    </a>
                @endcan
                <a href="{{ route('admin.users.create') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <span class="material-symbols-outlined text-[20px]">person_add</span>
                    Add User
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
                {{ session('status') }}
            </div>
        @endif
        
        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800 text-sm">
                <ul class="list-disc ml-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-6 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <label class="sr-only" for="q">Cari</label>
                        <input id="q" name="q" value="{{ $q }}" placeholder="Cari nama / email..."
                            class="w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cari
                    </button>
                    @if ($q !== '')
                        <a href="{{ route('admin.users.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $user->nama }}</div>
                                    <div class="text-xs text-slate-500">ID: {{ $user->id }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    {{-- Hanya yang login sebagai Owner yang bisa mengubah Role --}}
                                    @can('changeRole', $user)
                                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex items-center gap-2" id="role-form-{{ $user->id }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="role" id="role-select-{{ $user->id }}"
                                                class="rounded-lg border-slate-300 py-1.5 text-sm focus:border-brand-500 focus:ring-brand-500">
                                                
                                                {{-- Jika baris ini adalah akun si Owner sendiri, tampilkan label Owner saat ini --}}
                                                @if($user->isOwner())
                                                    <option value="owner" selected>Master/Owner</option>
                                                @endif
                                                
                                                {{-- Tampilkan opsi role lainnya --}}
                                                @foreach ($selectableRoles as $value => $label)
                                                    <option value="{{ $value }}" @selected((string) $user->role->slug === (string) $value)>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit"
                                                class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                                Simpan
                                            </button>
                                        </form>
                                    @else
                                        {{-- Tampilan text biasa untuk yang tidak berhak mengubah role --}}
                                        <span class="text-sm font-medium text-slate-700">{{ $user->role->label() }}</span>
                                    @endcan
                                </td>
                                <td class="px-4 py-3">
                                    @if ($user->is_active)
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aktif</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                            Edit
                                        </a>
                                        @can('delete', $user)
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                onsubmit="return confirm('Hapus user ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                                                    Delete
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                                    Tidak ada user.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-4 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        </div>

        <div class="mt-8 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Activity Log User</h3>
                    <p class="text-sm text-slate-500 mt-1">Riwayat aksi manajemen akun di halaman ini.</p>
                </div>
                <a href="{{ route('admin.activity', ['type' => 'user']) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition-all">
                    Lihat semua aktivitas user
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($userActivities as $activity)
                    <div class="p-4 hover:bg-slate-50/50 transition-colors">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-{{ $activity->color }}-50 text-{{ $activity->color }}-600 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-sm">{{ $activity->icon }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $activity->title }}</p>
                                    <span class="text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-sm text-slate-600 mt-2 space-y-2">
                                    @foreach(explode("\n", $activity->description) as $line)
                                        @php
                                            $trimmed = trim($line);
                                            $badge = null;

                                            if (str_starts_with($trimmed, '• Nama:')) {
                                                $badge = ['label' => 'Nama', 'color' => 'bg-blue-100 text-blue-700'];
                                            } elseif (str_starts_with($trimmed, '• Email:')) {
                                                $badge = ['label' => 'Email', 'color' => 'bg-emerald-100 text-emerald-700'];
                                            } elseif (str_starts_with($trimmed, '• Status:')) {
                                                $badge = ['label' => 'Status', 'color' => 'bg-amber-100 text-amber-700'];
                                            } elseif (str_starts_with($trimmed, '• Role:')) {
                                                $badge = ['label' => 'Role', 'color' => 'bg-violet-100 text-violet-700'];
                                            } elseif (str_starts_with($trimmed, '• Password:')) {
                                                $badge = ['label' => 'Password', 'color' => 'bg-rose-100 text-rose-700'];
                                            }
                                        @endphp

                                        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                            @if($badge)
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-[0.08em] {{ $badge['color'] }}">
                                                    {{ $badge['label'] }}
                                                </span>
                                            @endif
                                            <p class="text-sm text-slate-700">{{ ltrim($trimmed, '• ') }}</p>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-2 text-xs text-slate-500">by {{ $activity->user }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-sm text-slate-500">Belum ada aktivitas user di halaman ini.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div id="owner-modal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl border border-slate-100">
                <div class="mb-5 flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined">swap_horiz</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Transfer Kepemilikan</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Sistem wajib memiliki 1 orang Owner.</p>
                    </div>
                </div>

                <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg mb-4 text-sm text-slate-700">
                    Anda akan mengubah role Anda sendiri. Sebelum melepaskan hak akses Owner, silakan pilih user lain yang akan menerima <b>Hak Kepemilikan (Owner)</b> yang baru.
                </div>

                <form id="owner-change-form" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="role" id="modal-role-input">

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih Owner Penerus</label>
                        <select name="new_owner_id" id="new-owner-select" class="w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500" required>
                            <option value="">-- Pilih User --</option>
                            @foreach($users as $u)
                                @if($u->id !== auth()->id() && ! $u->isOwner())
                                    <option value="{{ $u->id }}">{{ $u->nama }} ({{ $u->email }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                        <button type="button" onclick="closeOwnerModal()"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-700 shadow-sm">
                            Konfirmasi Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentForm = null;

        // Tangkap event submit di semua form dropdown role
        document.querySelectorAll('form[id^="role-form-"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                const userId = this.id.replace('role-form-', '');
                const roleSelect = document.getElementById('role-select-' + userId);
                const selectedRole = roleSelect.value;

                // Cek khusus jika Owner merubah rolenya SEDIRI
                @if(auth()->user()->isOwner())
                    if (userId == {{ auth()->id() }} && selectedRole !== 'owner') {
                        e.preventDefault(); // Jangan langsung submit ke server!
                        currentForm = this;
                        
                        // Simpan role baru pilihan owner ke input hidden di dalam modal
                        document.getElementById('modal-role-input').value = selectedRole;
                        // Munculkan Modal Transfer
                        document.getElementById('owner-modal').classList.remove('hidden');
                        return false;
                    }
                @endif
            });
        });

        function closeOwnerModal() {
            document.getElementById('owner-modal').classList.add('hidden');
            document.getElementById('new-owner-select').value = '';
            currentForm = null;
        }

        // Tangkap konfirmasi dari dalam modal
        document.getElementById('owner-change-form').addEventListener('submit', function(e) {
            e.preventDefault(); 
            
            const newOwnerId = document.getElementById('new-owner-select').value;
            
            if (!newOwnerId) {
                alert('Silakan pilih User pengganti yang akan dijadikan Owner terlebih dahulu.');
                return false;
            }

            // Jika valid, kita sisipkan ID Owner baru ke form aslinya, lalu submit
            if (currentForm) {
                const newOwnerInput = document.createElement('input');
                newOwnerInput.type = 'hidden';
                newOwnerInput.name = 'new_owner_id';
                newOwnerInput.value = newOwnerId;
                currentForm.appendChild(newOwnerInput);

                currentForm.submit(); // Lanjutkan request ke Laravel
            }
        });
    </script>
@endsection