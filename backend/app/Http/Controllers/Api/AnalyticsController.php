<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Halaman Analytics.
 *
 * Catatan: seluruh angka di bawah masih DATA DUMMY (hardcoded), sama
 * seperti di versi Blade-nya. Diisi dengan agregasi query asli saat
 * modul Analytics dikerjakan -- sengaja tidak diubah di sini supaya
 * pemisahan 3-server tidak ikut mengubah hasil bisnis.
 */
class AnalyticsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
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
        ]);
    }
}
