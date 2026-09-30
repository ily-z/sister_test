@extends('layouts.app')

@section('content')
<div class="max-w-[700px] mx-auto px-4 md:px-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Edit Pengeluaran Kantor</h1>
            <p class="text-sm text-slate-500">Perbarui tanggal, jumlah, atau deskripsi pengeluaran.</p>
        </div>
        <a href="{{ route('admin.keuangan.pengeluaran-kantor') }}" class="text-sm text-slate-600 hover:underline">Kembali</a>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <form action="{{ route('admin.keuangan.pengeluaran-kantor.update', $pengeluaran->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-2">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', $pengeluaran->tanggal) }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-2">Jumlah (Rp)</label>
                    <input type="number" name="jumlah" value="{{ old('jumlah', $pengeluaran->jumlah) }}" min="0.01" step="0.01"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-2">Deskripsi</label>
                    <input type="text" name="deskripsi" value="{{ old('deskripsi', $pengeluaran->deskripsi) }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm" placeholder="Contoh: Pulsa kantor, ATK, dsb." />
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg">Simpan Perubahan</button>
                <a href="{{ route('admin.keuangan.pengeluaran-kantor') }}" class="px-4 py-2 border border-slate-200 rounded-lg">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection