{{-- resources/views/armada/tabs/umum.blade.php --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- KIRI: KPI & Riwayat Bisnis --}}
    <div class="lg:col-span-2 space-y-5">
        {{-- KPI Cards --}}
        @php
            $totalIncome = \App\Models\BookingCar::where('car_id', $car->id)
                ->join('bookings', 'booking_car.booking_id', '=', 'bookings.id')
                ->sum('bookings.total_harga');
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Odometer</p>
                <p class="text-lg font-bold text-gray-900">{{ number_format($car->km_saat_ini, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400">km</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Total Booking</p>
                <p class="text-lg font-bold text-blue-600">{{ $totalBookings }}</p>
                <p class="text-xs text-gray-400">kali disewa</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Pendapatan</p>
                <p class="text-lg font-bold text-emerald-600">Rp {{ number_format($totalIncome, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400">total</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Biaya Servis</p>
                @php
                    // Menjumlahkan semua 'biaya' dari detail servis yang terkait dengan mobil ini
                    $totalBiayaServis = $car->serviceLogs
                        ? $car->serviceLogs->flatMap->serviceLogDetails->sum('biaya')
                        : 0;
                @endphp
                <p class="text-lg font-bold text-orange-500">Rp {{ number_format($totalBiayaServis, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400">total pengeluaran</p>
            </div>
        </div>

        {{-- BARU: Banner Pesanan Aktif (hanya tampil jika mobil sedang disewa) --}}
        @if ($car->status === 'Sedang Disewa' && $car->activeBooking)
            <a href="{{ route('admin.bookings.show', $car->activeBooking->id) }}"
                class="flex items-center justify-between gap-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-2xl p-4 transition-colors group">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[18px] text-white">car_rental</span>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-blue-500 uppercase tracking-wide">Sedang Dipakai di Pesanan</p>
                        <p class="text-sm font-bold text-blue-800">
                            #{{ $car->activeBooking->nomor_pesanan }} — {{ $car->activeBooking->nama_pelanggan }}
                        </p>
                    </div>
                </div>
                <span
                    class="material-symbols-outlined text-[20px] text-blue-400 group-hover:text-blue-700 group-hover:translate-x-0.5 transition-all">arrow_forward</span>
            </a>
        @endif

        {{-- Riwayat Booking --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-[16px] text-blue-600">history</span>
                    </div>
                    <h2 class="text-sm font-bold text-gray-800">Riwayat Penyewaan</h2>
                </div>
                <span class="text-xs text-gray-400">{{ $totalBookings }} booking</span>
            </div>

            <div class="space-y-2">
                @forelse($latestBookings as $bookingCar)
                    @php $booking = $bookingCar->booking; @endphp
                    @if ($booking)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center text-gray-500">
                                    <span class="material-symbols-outlined text-[18px]">person</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ $booking->nama_pelanggan }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($booking->tanggal_mulai)->isoFormat('D MMM') }} –
                                        {{ \Carbon\Carbon::parse($booking->tanggal_selesai)->isoFormat('D MMM YYYY') }}
                                        · {{ ucfirst($booking->tipe_sewa) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-gray-700">Rp
                                    {{ number_format($booking->total_harga, 0, ',', '.') }}</p>
                                <span
                                    class="text-xs {{ $booking->status_pembayaran == 'Lunas' ? 'bg-emerald-100 text-emerald-700' : ($booking->status_pembayaran == 'DP' ? 'bg-blue-200 text-blue-800' : 'bg-amber-100 text-amber-700') }} px-2 py-0.5 rounded-full font-medium">{{ $booking->status_pembayaran }}</span>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="p-4 text-sm text-gray-500 bg-gray-50 rounded-xl text-center">Belum ada riwayat penyewaan
                        untuk mobil ini.</div>
                @endforelse
            </div>
            <button
                class="w-full mt-3 text-xs font-semibold text-gray-500 hover:text-blue-600 py-2 rounded-xl border border-dashed border-gray-200 hover:border-blue-300 transition-colors">Lihat
                Semua Riwayat →</button>
        </div>
    </div>

    {{-- KANAN: Foto & Spesifikasi --}}
    <div class="space-y-5">
        {{-- Foto Mobil --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="h-52 bg-slate-100 flex items-center justify-center relative">
                @if ($car->foto)
                    <img src="{{ asset('storage/' . $car->foto) }}" alt="Foto" class="h-full w-full object-cover">
                @else
                    <div class="text-center text-slate-400">
                        <span class="material-symbols-outlined text-[48px] opacity-40 mb-2">no_photography</span>
                        <p class="text-[10px] font-bold uppercase tracking-widest">Belum ada foto</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Info Kendaraan --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Informasi Kendaraan</h2>
            <div class="space-y-2.5">
                @foreach ([['Plat Nomor', $car->plat_nomor], ['Merk', $car->merk], ['Model', $car->model], ['Tahun', $car->tahun], ['Kapasitas', $car->kapasitas . ' Kursi'], ['Warna', $car->warna ?? '-']] as $info)
                    <div class="flex justify-between">
                        <span class="text-xs text-gray-500">{{ $info[0] }}</span>
                        <span class="text-xs font-semibold text-gray-800">{{ $info[1] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Identitas & Tarif --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Identitas & Tarif</h2>
            <div class="space-y-3 mb-4">
                <div>
                    <p class="text-[10px] text-gray-500 mb-0.5">Nomor Mesin</p>
                    <p class="text-xs font-mono font-semibold text-gray-800 bg-gray-50 px-2 py-1 rounded-md">
                        {{ $car->no_mesin }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 mb-0.5">Nomor Rangka</p>
                    <p class="text-xs font-mono font-semibold text-gray-800 bg-gray-50 px-2 py-1 rounded-md">
                        {{ $car->no_rangka }}</p>
                </div>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between items-center bg-blue-50 px-3 py-2 rounded-xl">
                    <div>
                        <span class="text-xs font-bold text-blue-700">Harian</span>
                    </div>
                    <span class="text-sm font-bold text-blue-900">Rp
                        {{ number_format($car->harga_default, 0, ',', '.') }} / hari</span>
                </div>
                <div class="flex justify-between items-center bg-purple-50 px-3 py-2 rounded-xl">
                    <div>
                        <span class="text-xs font-bold text-purple-700">Kontrak</span>
                    </div>
                    <span class="text-sm font-bold text-purple-900">Rp
                        {{ number_format($car->harga_kontrak ?? 0, 0, ',', '.') }} / hari</span>
                </div>
            </div>
        </div>
    </div>
</div>