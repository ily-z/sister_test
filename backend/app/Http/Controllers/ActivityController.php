<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');

        $filters = [
            'all' => 'All Activities',
            'booking' => 'Bookings',
            'payment' => 'Payments',
            'service' => 'Service',
            'maintenance' => 'Maintenance',
            'user' => 'Users',
        ];

        $activities = Activity::when($type !== 'all', function ($query) use ($type) {
            $query->where('type', $type);
        })
            ->orderByDesc('created_at')
            ->take(50)
            ->get();

        return view('activity.index', compact('activities', 'filters', 'type'));
    }
}