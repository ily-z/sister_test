<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Daftar filter yang dipakai frontend untuk dropdown/tab.
     */
    public const FILTERS = [
        'all' => 'All Activities',
        'booking' => 'Bookings',
        'payment' => 'Payments',
        'service' => 'Service',
        'maintenance' => 'Maintenance',
        'user' => 'Users',
    ];

    public function index(Request $request): JsonResponse
    {
        $type = (string) $request->query('type', 'all');

        // Guard: type dari query string tidak boleh bebas, hanya 6 nilai
        // yang dikenal. Tanpa ini, attacker bisa menebak nama kolom lain.
        $validated = array_key_exists($type, self::FILTERS) ? $type : 'all';

        $limit = min(max((int) $request->query('limit', 50), 1), 200);

        $activities = Activity::when($validated !== 'all', fn ($query) => $query->where('type', $validated))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'activities' => $activities,
            'filters' => self::FILTERS,
            'type' => $validated,
        ]);
    }
}
