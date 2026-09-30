@extends('layouts.app')

@section('content')
    <div class="px-4 sm:px-6 lg:px-8 max-w-3xl">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">Edit User</h2>
                <p class="text-sm text-slate-500 mt-1">Perbarui detail user dan role akses.</p>
            </div>
            <a href="{{ route('admin.users.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800 text-sm">
                <ul class="list-disc ml-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-6 bg-white border border-slate-200 rounded-xl shadow-sm p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-semibold text-slate-700" for="nama">Nama</label>
                <input id="nama" name="nama" value="{{ old('nama', $user->nama) }}"
                    class="mt-1 w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500" />
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                    class="mt-1 w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500" />
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <button id="toggle-password-section" type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <span class="material-symbols-outlined">edit</span>
                        Ubah Password (Opsional)
                    </button>
                    <p class="mt-1 text-xs text-slate-500">Password hanya perlu diisi jika Anda ingin mengganti password akun.</p>
                </div>

                <div id="password-section" class="hidden space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700" for="password">Password Baru</label>
                        <input id="password" name="password" type="password" disabled
                            class="mt-1 w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700" for="password_confirmation">Konfirmasi Password Baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" disabled
                            class="mt-1 w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const toggle = document.getElementById('toggle-password-section');
                    const section = document.getElementById('password-section');
                    const inputs = section.querySelectorAll('input');

                    toggle.addEventListener('click', function () {
                        const isHidden = section.classList.contains('hidden');
                        section.classList.toggle('hidden');
                        inputs.forEach(input => input.disabled = !isHidden);
                    });
                });
            </script>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700" for="role">Role</label>
                    
                    {{-- Jika user yang sedang diedit adalah Owner --}}
                    @if ($user->isOwner())
                        <div class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-600 text-sm">
                            {{ $user->role->label() }}
                        </div>
                        <p class="mt-1 text-xs text-amber-600">Gunakan dropdown di halaman List User untuk mentransfer kepemilikan.</p>
                        <input type="hidden" name="role" value="{{ $user->role->slug }}">
                        
                    {{-- Jika yang login BUKAN Owner, mereka tidak boleh mengedit role --}}
                    @elseif (!auth()->user()->isOwner())
                        <div class="mt-1 w-full px-3 py-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-600 text-sm">
                            {{ $user->role->label() }}
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Hanya Owner yang dapat mengubah role akses.</p>
                        <input type="hidden" name="role" value="{{ $user->role->slug }}">
                        
                    {{-- Owner sedang mengedit user biasa --}}
                    @else
                        <select id="role" name="role"
                            class="mt-1 w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                            @foreach ($roleOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', $user->role->slug) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                
                <div class="flex items-center gap-2 pt-6">
                    <input type="hidden" name="is_active" value="0" />
                    <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $user->is_active))
                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                    <label for="is_active" class="text-sm font-semibold text-slate-700">Aktif</label>
                </div>
            </div>

            <div class="pt-2 flex items-center gap-3">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    Simpan Perubahan
                </button>
                <a href="{{ route('admin.users.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Batal
                </a>
            </div>
        </form>
    </div>
@endsection