@extends('layouts.app')

@section('content')
<div class="max-w-[1400px] mx-auto px-4 md:px-8">

    <div class="flex flex-col md:flex-row md:justify-between md:items-end mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1">Ringkasan operasional armada dan penyewaan.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-600 shadow-sm">
                <span class="material-symbols-outlined text-[18px] mr-2 text-slate-400">calendar_today</span>
                <span class="font-medium">Hari ini: {{ now()->translatedFormat('d F Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Mobile Tab Bar -->
    <div class="md:hidden flex gap-4 mb-6 border-b border-slate-200 overflow-x-auto whitespace-nowrap">
        <button onclick="switchTab('overview', this)"
            class="tab-btn pb-3 text-sm font-semibold text-brand-600 border-b-2 border-brand-600 transition-colors">Overview</button>
        <button onclick="switchTab('analytics', this)"
            class="tab-btn pb-3 text-sm font-semibold text-slate-500 border-b-2 border-transparent hover:text-slate-900 transition-colors">Analytics</button>
        <button onclick="switchTab('activity', this)"
            class="tab-btn pb-3 text-sm font-semibold text-slate-500 border-b-2 border-transparent hover:text-slate-900 transition-colors">Activity</button>
    </div>

    <!-- Overview Tab -->
    <div id="tab-overview" class="tab-content">

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-32 sm:h-36">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Jumlah Disewa</p>
                        <h3 class="text-3xl font-bold text-slate-900 tracking-tight">{{ number_format($jumlahDisewa) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">key</span>
                    </div>
                </div>
                <div class="flex items-center text-xs mt-4">
                    <span class="text-slate-500">Jumlah armada yang sedang disewa</span>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-32 sm:h-36">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Jumlah Dikontrak</p>
                        <h3 class="text-3xl font-bold text-slate-900 tracking-tight">{{ number_format($jumlahKontrak) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">assignment</span>
                    </div>
                </div>
                <div class="flex items-center text-xs mt-4">
                    <span class="text-slate-500">Sewa kontrak aktif hari ini</span>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-32 sm:h-36">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tersedia / Nganggur</p>
                        <h3 class="text-3xl font-bold text-slate-900 tracking-tight">{{ number_format($tersedia) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">check_circle</span>
                    </div>
                </div>
                <div class="flex items-center text-xs mt-4">
                    <span class="bg-emerald-100 text-emerald-700 font-bold px-2 py-0.5 rounded mr-2">Ready</span>
                    <span class="text-slate-500">Armada siap disewakan</span>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-32 sm:h-36">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Masuk Bengkel</p>
                        <h3 class="text-3xl font-bold text-slate-900 tracking-tight">{{ number_format($bengkel) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">build</span>
                    </div>
                </div>
                <div class="flex items-center text-xs mt-4">
                    <span class="text-slate-500">Armada yang sedang dalam perbaikan</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-3 bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6 sm:mb-8">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 tracking-tight">Chart Pendapatan Perbulan</h3>
                        <p class="text-sm text-slate-500 mt-1">Analisis performa pendapatan rental</p>
                    </div>
                    <div class="flex bg-slate-100 rounded-lg p-1 self-start sm:self-auto">
                        <button class="px-4 py-1.5 bg-white shadow-sm rounded-md text-xs font-semibold text-brand-700">Tahun Ini</button>
                    </div>
                </div>
                <div class="h-64 flex items-end justify-between relative pl-8 pb-6">
                    <div class="absolute left-0 top-0 bottom-6 flex flex-col justify-between text-[11px] font-medium text-slate-400">
                        <span>100%</span><span>75%</span><span>50%</span><span>25%</span><span>0</span>
                    </div>
                    <div class="absolute left-8 right-0 top-2 bottom-6 flex flex-col justify-between pointer-events-none">
                        <div class="w-full border-t border-slate-100 border-dashed"></div>
                        <div class="w-full border-t border-slate-100 border-dashed"></div>
                        <div class="w-full border-t border-slate-100 border-dashed"></div>
                        <div class="w-full border-t border-slate-100 border-dashed"></div>
                        <div class="w-full border-t border-slate-100"></div>
                    </div>
                    @foreach($revenueChart as $bar)
                        <div class="w-10 flex flex-col justify-end items-center">
                            <div class="w-full bg-brand-600 rounded-t-md relative z-10" style="height: {{ max(12, $bar['pct']) }}%;"></div>
                            <span class="text-[10px] mt-2">{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-around pl-8 mt-2 text-[11px] font-semibold text-slate-500 uppercase">
                    @foreach($revenueChart as $bar)
                        <span>{{ $bar['label'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6 mb-8">
            <div class="xl:col-span-3 bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 flex justify-between items-center border-b border-slate-50">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 tracking-tight">List Transaksi Terakhir</h3>
                        <p class="text-xs text-slate-500 mt-1">Daftar penyewaan yang baru saja ditambahkan</p>
                    </div>
                    <button class="text-xs font-bold text-brand-600 hover:bg-brand-50 px-3 py-1.5 rounded-lg transition-colors">Lihat Semua</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-slate-400 font-semibold border-b border-slate-50">
                                <th class="px-3 sm:px-6 py-3 sm:py-4">Kendaraan</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4">Penyewa</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4">Total Biaya</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($latestBookings as $booking)
                                @php
                                    $firstCar = $booking->bookingCars->first();
                                    $plate = $firstCar?->car?->plat_nomor ?? '-';
                                    $carName = $firstCar?->car?->merk && $firstCar?->car?->model ? $firstCar->car->merk . ' ' . $firstCar->car->model : 'Kendaraan';
                                    $paymentStatus = $booking->status_pembayaran ?? 'Belum Bayar';
                                    $statusClass = match($paymentStatus) {
                                        'Lunas' => 'bg-emerald-50 text-emerald-600',
                                        'DP' => 'bg-amber-50 text-amber-600',
                                        'Belum Bayar' => 'bg-rose-50 text-rose-600',
                                        default => 'bg-slate-50 text-slate-600',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-3 sm:px-6 py-3 sm:py-4">
                                        <p class="font-bold text-slate-900">{{ $carName }}</p>
                                        <p class="text-[10px] text-slate-500 font-mono">{{ $plate }}</p>
                                    </td>
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 text-slate-600 font-medium">{{ $booking->nama_pelanggan }}</td>
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 font-bold text-brand-600">Rp {{ number_format($booking->total_harga ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 text-right">
                                        <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase {{ $statusClass }}">{{ $paymentStatus }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-6 text-center text-slate-500">Belum ada transaksi terbaru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="xl:col-span-1 bg-white p-6 rounded-xl border border-slate-100 shadow-sm flex flex-col">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-slate-900 tracking-tight">Peringatan Service</h3>
                    <p class="text-xs text-slate-500 mt-1">Status keausan komponen armada</p>
                </div>
                <div class="space-y-4 flex-grow">
                    @forelse($serviceAlerts as $alert)
                        <div class="p-4 rounded-lg border border-slate-100 border-l-4 {{ $alert['level'] === 'critical' ? 'bg-rose-50/30 border-l-rose-500' : 'bg-amber-50 border-l-amber-500' }}">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-[10px] font-bold uppercase {{ $alert['level'] === 'critical' ? 'text-rose-600' : 'text-amber-600' }}">
                                    {{ $alert['level'] === 'critical' ? 'Waktunya Servis' : 'Hampir Habis' }}
                                </span>
                                <span class="material-symbols-outlined {{ $alert['level'] === 'critical' ? 'text-rose-500' : 'text-amber-500' }} text-sm">
                                    {{ $alert['level'] === 'critical' ? 'build' : 'warning' }}
                                </span>
                            </div>
                            <p class="text-sm font-bold text-slate-900">{{ $alert['name'] }}</p>
                            
                            @if($alert['level'] === 'critical')
                                <p class="text-[10px] text-slate-500 mt-1">Keausan {{ number_format($alert['current'], 0, ',', '.') }} KM — target {{ number_format($alert['threshold'], 0, ',', '.') }} KM (<span class="text-rose-600 font-bold">Lebih {{ number_format($alert['diff'], 0, ',', '.') }} KM</span>)</p>
                            @else
                                <p class="text-[10px] text-slate-500 mt-1">Keausan {{ number_format($alert['current'], 0, ',', '.') }} KM — target {{ number_format($alert['threshold'], 0, ',', '.') }} KM (<span class="text-amber-600 font-bold">Sisa {{ number_format($alert['diff'], 0, ',', '.') }} KM</span>)</p>
                            @endif
                        </div>
                    @empty
                        <div class="p-4 rounded-lg border border-slate-100 bg-slate-50 text-slate-500 text-sm text-center">
                            Tidak ada komponen yang perlu diservis saat ini.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <!-- Analytics Tab -->
    <div id="tab-analytics" class="tab-content hidden">

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-32 sm:h-36">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Total Revenue</p>
                        <h3 class="text-2xl font-bold text-slate-900 tracking-tight">Rp {{ number_format($analyticsData['totalRevenue'], 0, ',', '.') }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">attach_money</span>
                    </div>
                </div>
                <div class="flex items-center text-xs mt-4">
                    <span class="text-slate-500">Cumulative earnings</span>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-32 sm:h-36">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Total Bookings</p>
                        <h3 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $analyticsData['totalBookings'] }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">assignment</span>
                    </div>
                </div>
                <div class="flex items-center text-xs mt-4">
                    <span class="text-slate-500">All-time bookings</span>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-32 sm:h-36">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Avg Duration</p>
                        <h3 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $analyticsData['avgBookingDuration'] }} days</h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">schedule</span>
                    </div>
                </div>
                <div class="flex items-center text-xs mt-4">
                    <span class="text-slate-500">Average rental period</span>
                </div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-32 sm:h-36">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Utilization</p>
                        <h3 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $analyticsData['vehicleUtilization'] }}%</h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">trending_up</span>
                    </div>
                </div>
                <div class="flex items-center text-xs mt-4">
                    <span class="text-slate-500">Fleet utilization rate</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 tracking-tight">Revenue by Month</h3>
                        <p class="text-sm text-slate-500 mt-1">Monthly revenue trends</p>
                    </div>
                </div>
                <div class="h-64 flex items-end justify-around relative pl-8 pb-6">
                    <div class="absolute left-0 top-0 bottom-6 flex flex-col justify-between text-[11px] font-medium text-slate-400">
                        <span>25M</span><span>20M</span><span>15M</span><span>10M</span><span>5M</span><span>0</span>
                    </div>
                    <div class="absolute left-8 right-0 top-2 bottom-6 flex flex-col justify-between pointer-events-none">
                        <div class="w-full border-t border-slate-100 border-dashed"></div>
                        <div class="w-full border-t border-slate-100 border-dashed"></div>
                        <div class="w-full border-t border-slate-100 border-dashed"></div>
                        <div class="w-full border-t border-slate-100 border-dashed"></div>
                        <div class="w-full border-t border-slate-100 border-dashed"></div>
                        <div class="w-full border-t border-slate-100"></div>
                    </div>
                    @foreach($analyticsData['revenueByMonth'] as $data)
                        <div class="w-8 bg-blue-600 rounded-t-md relative z-10" style="height: {{ ($data['revenue'] / 25000000) * 100 }}%"></div>
                    @endforeach
                </div>
                <div class="flex justify-around pl-8 mt-2 text-[11px] font-semibold text-slate-500 uppercase">
                    @foreach($analyticsData['revenueByMonth'] as $data)
                        <span>{{ $data['month'] }}</span>
                    @endforeach
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 tracking-tight">Vehicle Utilization</h3>
                        <p class="text-sm text-slate-500 mt-1">Top 5 vehicles by utilization</p>
                    </div>
                </div>
                <div class="space-y-4">
                    @foreach($analyticsData['vehicleUtilizationData'] as $vehicle)
                        <div class="flex items-center gap-4">
                            <div class="w-32 text-sm font-medium text-slate-700 truncate">{{ $vehicle['vehicle'] }}</div>
                            <div class="flex-1">
                                <div class="w-full bg-slate-200 rounded-full h-2">
                                    <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $vehicle['utilization'] }}%"></div>
                                </div>
                            </div>
                            <div class="w-12 text-sm font-bold text-slate-900 text-right">{{ $vehicle['utilization'] }}%</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-50">
                    <h3 class="text-lg font-bold text-slate-900 tracking-tight">Booking Trends</h3>
                    <p class="text-xs text-slate-500 mt-1">Weekly booking comparison</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($analyticsData['bookingTrends'] as $trend)
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">{{ $trend['period'] }}</span>
                                <span class="text-sm font-bold text-slate-900">{{ $trend['bookings'] }} bookings</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-50">
                    <h3 class="text-lg font-bold text-slate-900 tracking-tight">Top Performing Vehicles</h3>
                    <p class="text-xs text-slate-500 mt-1">By revenue generated</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-slate-400 font-semibold border-b border-slate-50">
                                <th class="px-3 sm:px-6 py-3 sm:py-4">Vehicle</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4">Bookings</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($analyticsData['topVehicles'] as $vehicle)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 font-medium text-slate-900">{{ $vehicle['name'] }}</td>
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 text-slate-600">{{ $vehicle['bookings'] }}</td>
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 font-bold text-emerald-600 text-right">Rp {{ number_format($vehicle['revenue'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Activity Tab -->
    <div id="tab-activity" class="tab-content hidden">

        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm mb-6">
            <div class="flex flex-wrap gap-2">
                @foreach($activityFilters as $key => $label)
                    <button onclick="filterActivity('{{ $key }}', this)"
                        class="activity-filter px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $loop->first ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-50">
                <h3 class="text-lg font-bold text-slate-900 tracking-tight">Recent Activities</h3>
                <p class="text-xs text-slate-500 mt-1">Timeline of system events and user actions</p>
            </div>

            <div id="activity-list" class="divide-y divide-slate-50">
                @forelse($activities as $activity)
                    <div class="p-6 hover:bg-slate-50/50 transition-colors activity-item" data-type="{{ $activity->type }}">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-{{ $activity->color }}-50 text-{{ $activity->color }}-600 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-sm">{{ $activity->icon }}</span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4">
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-bold text-slate-900">{{ $activity->title }}</h4>
                                        <p class="text-sm text-slate-600 mt-1">{{ $activity->description }}</p>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-slate-500">
                                            <span>by {{ $activity->user }}</span>
                                            <span>{{ $activity->created_at->format('d M Y, H:i') }} • {{ $activity->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>

                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($activity->type === 'booking') bg-blue-100 text-blue-800
                                            @elseif($activity->type === 'payment') bg-emerald-100 text-emerald-800
                                            @elseif($activity->type === 'return') bg-amber-100 text-amber-800
                                            @elseif($activity->type === 'service') bg-rose-100 text-rose-800
                                            @elseif($activity->type === 'user') bg-purple-100 text-purple-800
                                            @elseif($activity->type === 'maintenance') bg-orange-100 text-orange-800
                                            @else bg-slate-100 text-slate-800
                                            @endif">
                                            {{ ucfirst($activity->type) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-slate-500">No activities recorded yet.</div>
                @endforelse
            </div>

            <div class="p-6 border-t border-slate-50 bg-slate-50/50">
                <button class="w-full text-center text-sm font-semibold text-blue-600 hover:text-blue-700 transition-colors">
                    Load More Activities
                </button>
            </div>
        </div>

    </div>

</div>

<script>
    function switchTab(tab, btn) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('text-brand-600', 'border-brand-600');
            el.classList.add('text-slate-500', 'border-transparent');
        });
        document.getElementById('tab-' + tab).classList.remove('hidden');
        btn.classList.remove('text-slate-500', 'border-transparent');
        btn.classList.add('text-brand-600', 'border-brand-600');
    }

    function filterActivity(type, btn) {
        document.querySelectorAll('.activity-filter').forEach(el => {
            el.classList.remove('bg-blue-600', 'text-white');
            el.classList.add('bg-slate-100', 'text-slate-700');
        });
        btn.classList.remove('bg-slate-100', 'text-slate-700');
        btn.classList.add('bg-blue-600', 'text-white');

        document.querySelectorAll('.activity-item').forEach(el => {
            if (type === 'all' || el.dataset.type === type) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    }
</script>
@endsection