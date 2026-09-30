<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Booking;
use App\Models\Car;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $totalMobil = Car::count();
        $jumlahDisewa = Car::where('status', 'Sedang Disewa')->count();
        $tersedia = Car::where('status', 'Tersedia')->count();
        $bengkel = Car::where('status', 'Masuk Bengkel')->count();
        $jumlahKontrak = Booking::where('tipe_sewa', 'kontrak')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->count();

        $revenueChartData = collect(range(1, 6))->map(function ($month) use ($today) {
            $label = Carbon::create($today->year, $month, 1)->translatedFormat('M');
            $total = Booking::whereYear('created_at', $today->year)
                ->whereMonth('created_at', $month)
                ->sum('total_harga');

            return [
                'label' => $label,
                'total' => $total,
            ];
        });

        $maxRevenue = $revenueChartData->max('total') ?: 1;
        $revenueChart = $revenueChartData->map(function ($item) use ($maxRevenue) {
            return [
                'label' => $item['label'], 
                'total' => $item['total'],
                'pct' => round($item['total'] / $maxRevenue * 100),
            ];
        })->toArray();

        $latestBookings = Booking::with(['bookingCars.car'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // LOGIKA BARU: Peringatan Servis berdasarkan Komponen (Car Parts)
        $serviceAlerts = [];
        // Ambil mobil yang tidak di bengkel beserta komponennya
        $carsForAlerts = \App\Models\Car::with('parts')->where('status', '!=', 'Masuk Bengkel')->get();

        foreach ($carsForAlerts as $c) {
            foreach ($c->parts as $part) {
                if ($part->is_track_km && $part->lifespan_km > 0) {
                    $km_berjalan = max(0, $c->km_saat_ini - $part->last_service_km);
                    $persentase = ($km_berjalan / $part->lifespan_km) * 100;

                    if ($persentase >= 100) {
                        // Kritis: Sudah lewat batas
                        $serviceAlerts[] = [
                            'level'     => 'critical',
                            'name'      => "{$part->nama_part} ({$c->plat_nomor})",
                            'current'   => $km_berjalan,
                            'threshold' => $part->lifespan_km,
                            'diff'      => $km_berjalan - $part->lifespan_km, // Kelebihan KM
                        ];
                    } elseif ($persentase >= 85) {
                        // Peringatan: Sudah mencapai 85% keausan
                        $serviceAlerts[] = [
                            'level'     => 'warning',
                            'name'      => "{$part->nama_part} ({$c->plat_nomor})",
                            'current'   => $km_berjalan,
                            'threshold' => $part->lifespan_km,
                            'diff'      => $part->lifespan_km - $km_berjalan, // Sisa KM
                        ];
                    }
                }
            }
        }

        // Urutkan: Yang critical di atas, lalu ambil 5 teratas
        $serviceAlerts = collect($serviceAlerts)->sortByDesc(function ($alert) {
            return $alert['level'] === 'critical' ? 1 : 0;
        })->take(5)->toArray();

        // ── Analytics Data ────────────────────────────────────────────────
        $analyticsData = $this->getAnalyticsData();

        // ── Activity Data ──────────────────────────────────────────────────
        $activities = Activity::orderByDesc('created_at')->take(50)->get();
        $activityFilters = [
            'all' => 'All Activities',
            'booking' => 'Bookings',
            'payment' => 'Payments',
            'service' => 'Service',
            'maintenance' => 'Maintenance',
            'user' => 'Users',
        ];
        $activityType = 'all';

        return view('dashboard.index', compact(
            'totalMobil',
            'jumlahDisewa',
            'jumlahKontrak',
            'tersedia',
            'bengkel',
            'revenueChart',
            'latestBookings',
            'serviceAlerts',
            'analyticsData',
            'activities',
            'activityFilters',
            'activityType'
        ));
    }

    private function getAnalyticsData()
    {
        return [
            'totalRevenue' => 125000000,
            'totalBookings' => 156,
            'avgBookingDuration' => 4.2,
            'vehicleUtilization' => 78.5,

            'revenueByMonth' => [
                ['month' => 'Jan', 'revenue' => 8500000],
                ['month' => 'Feb', 'revenue' => 12000000],
                ['month' => 'Mar', 'revenue' => 9500000],
                ['month' => 'Apr', 'revenue' => 15000000],
                ['month' => 'May', 'revenue' => 18000000],
                ['month' => 'Jun', 'revenue' => 22000000],
                ['month' => 'Jul', 'revenue' => 25000000],
            ],

            'vehicleUtilizationData' => [
                ['vehicle' => 'Toyota Innova', 'utilization' => 85],
                ['vehicle' => 'Honda CR-V', 'utilization' => 72],
                ['vehicle' => 'BMW M4', 'utilization' => 65],
                ['vehicle' => 'Tesla Model S', 'utilization' => 58],
                ['vehicle' => 'Mercedes E300', 'utilization' => 45],
            ],

            'bookingTrends' => [
                ['period' => 'This Week', 'bookings' => 23],
                ['period' => 'Last Week', 'bookings' => 18],
                ['period' => '2 Weeks Ago', 'bookings' => 21],
                ['period' => '3 Weeks Ago', 'bookings' => 19],
            ],

            'topVehicles' => [
                ['name' => 'Toyota Innova Reborn', 'bookings' => 45, 'revenue' => 67500000],
                ['name' => 'Honda CR-V Turbo', 'bookings' => 38, 'revenue' => 53200000],
                ['name' => 'BMW M4 Competition', 'bookings' => 32, 'revenue' => 48000000],
                ['name' => 'Tesla Model S', 'bookings' => 28, 'revenue' => 40600000],
                ['name' => 'Mercedes Benz E300', 'bookings' => 13, 'revenue' => 19500000],
            ],
        ];
    }
}