@extends('layouts.app')

@section('content')
<div class="max-w-[1400px] mx-auto px-4 md:px-8">

    <!-- Header area -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-end mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Analytics</h2>
            <p class="text-sm text-slate-500 mt-1">Detailed performance metrics and insights.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center bg-white border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600">
                <span class="material-symbols-outlined text-[18px] mr-2">calendar_today</span>
                <span class="font-medium">Last 7 months</span>
            </div>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Revenue -->
        <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-36">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Total Revenue</p>
                    <h3 class="text-2xl font-bold text-slate-900 tracking-tight">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined">attach_money</span>
                </div>
            </div>
            <div class="flex items-center text-xs mt-4">
                <span class="text-slate-500">Cumulative earnings</span>
            </div>
        </div>

        <!-- Total Bookings -->
        <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-36">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Total Bookings</p>
                    <h3 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $totalBookings }}</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined">assignment</span>
                </div>
            </div>
            <div class="flex items-center text-xs mt-4">
                <span class="text-slate-500">All-time bookings</span>
            </div>
        </div>

        <!-- Avg Booking Duration -->
        <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-36">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Avg Duration</p>
                    <h3 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $avgBookingDuration }} days</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <span class="material-symbols-outlined">schedule</span>
                </div>
            </div>
            <div class="flex items-center text-xs mt-4">
                <span class="text-slate-500">Average rental period</span>
            </div>
        </div>

        <!-- Vehicle Utilization -->
        <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between h-36">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Utilization</p>
                    <h3 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $vehicleUtilization }}%</h3>
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

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Revenue Chart -->
        <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 tracking-tight">Revenue by Month</h3>
                    <p class="text-sm text-slate-500 mt-1">Monthly revenue trends</p>
                </div>
            </div>
            <div class="h-64 overflow-x-auto">
                <div class="h-full flex items-end justify-around relative pl-8 pb-6 min-w-[420px]">
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
                    @foreach($revenueByMonth as $data)
                        <div class="w-8 bg-blue-600 rounded-t-md relative z-10 flex-shrink-0" style="height: {{ ($data['revenue'] / 25000000) * 100 }}%"></div>
                    @endforeach
                </div>
            </div>
            <div class="overflow-x-auto">
                <div class="flex justify-around pl-8 mt-2 text-[11px] font-semibold text-slate-500 uppercase min-w-[420px]">
                    @foreach($revenueByMonth as $data)
                        <span>{{ $data['month'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Vehicle Utilization Chart -->
        <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 tracking-tight">Vehicle Utilization</h3>
                    <p class="text-sm text-slate-500 mt-1">Top 5 vehicles by utilization</p>
                </div>
            </div>
            <div class="space-y-4">
                @foreach($vehicleUtilizationData as $vehicle)
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

    <!-- Bottom Section -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        <!-- Booking Trends -->
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-50">
                <h3 class="text-lg font-bold text-slate-900 tracking-tight">Booking Trends</h3>
                <p class="text-xs text-slate-500 mt-1">Weekly booking comparison</p>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($bookingTrends as $trend)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700">{{ $trend['period'] }}</span>
                            <span class="text-sm font-bold text-slate-900">{{ $trend['bookings'] }} bookings</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Top Vehicles -->
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-50">
                <h3 class="text-lg font-bold text-slate-900 tracking-tight">Top Performing Vehicles</h3>
                <p class="text-xs text-slate-500 mt-1">By revenue generated</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-slate-400 font-semibold border-b border-slate-50">
                            <th class="px-6 py-4">Vehicle</th>
                            <th class="px-6 py-4">Bookings</th>
                            <th class="px-6 py-4 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($topVehicles as $vehicle)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $vehicle['name'] }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $vehicle['bookings'] }}</td>
                                <td class="px-6 py-4 font-bold text-emerald-600 text-right">Rp {{ number_format($vehicle['revenue'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection