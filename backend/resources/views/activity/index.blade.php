@extends('layouts.app')

@section('content')
<div class="max-w-[1400px] mx-auto px-4 md:px-8">

    <!-- Header area -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-end mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Activity Log</h2>
            <p class="text-sm text-slate-500 mt-1">Recent actions and system events.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center bg-white border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600">
                <span class="material-symbols-outlined text-[18px] mr-2">history</span>
                <span class="font-medium">Last 7 days</span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm mb-6">
        <div class="flex flex-wrap gap-2">
            @foreach($filters as $key => $label)
                <a href="{{ route('admin.activity', ['type' => $key]) }}"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $type === $key ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Activity Timeline -->
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-50">
            <h3 class="text-lg font-bold text-slate-900 tracking-tight">Recent Activities</h3>
            <p class="text-xs text-slate-500 mt-1">Timeline of system events and user actions</p>
        </div>

        <div class="divide-y divide-slate-50">
            @foreach($activities as $activity)
                <div class="p-6 hover:bg-slate-50/50 transition-colors">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-{{ is_object($activity) ? $activity->color : $activity['color'] }}-50 text-{{ is_object($activity) ? $activity->color : $activity['color'] }}-600 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-sm">{{ is_object($activity) ? $activity->icon : $activity['icon'] }}</span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900">{{ is_object($activity) ? $activity->title : $activity['title'] }}</h4>
                                    <p class="text-sm text-slate-600 mt-1">{{ is_object($activity) ? $activity->description : $activity['description'] }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-500">
                                        <span>by {{ is_object($activity) ? $activity->user : $activity['user'] }}</span>
                                        <span>
                                            @if(is_object($activity) && $activity->created_at)
                                                {{ $activity->created_at->format('d M Y, H:i') }} • {{ $activity->created_at->diffForHumans() }}
                                            @else
                                                {{ \Carbon\Carbon::parse(is_object($activity) ? $activity->timestamp : $activity['timestamp'])->format('d M Y, H:i') }} • {{ \Carbon\Carbon::parse(is_object($activity) ? $activity->timestamp : $activity['timestamp'])->diffForHumans() }}
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @php $activityType = is_object($activity) ? $activity->type : $activity['type']; @endphp
                                        @if($activityType === 'booking') bg-blue-100 text-blue-800
                                        @elseif($activityType === 'payment') bg-emerald-100 text-emerald-800
                                        @elseif($activityType === 'return') bg-amber-100 text-amber-800
                                        @elseif($activityType === 'service') bg-rose-100 text-rose-800
                                        @elseif($activityType === 'user') bg-purple-100 text-purple-800
                                        @elseif($activityType === 'maintenance') bg-orange-100 text-orange-800
                                        @else bg-slate-100 text-slate-800
                                        @endif">
                                        {{ ucfirst($activityType) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Load More -->
        <div class="p-6 border-t border-slate-50 bg-slate-50/50">
            <button class="w-full text-center text-sm font-semibold text-blue-600 hover:text-blue-700 transition-colors">
                Load More Activities
            </button>
        </div>
    </div>
</div>
@endsection