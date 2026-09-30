{{-- KPI Card Component --}}
{{-- Usage: @include('components.kpi-card', ['label' => '...', 'value' => '...', 'icon' => '...', 'color' => 'blue', 'subtitle' => '...', 'trend' => '+12%']) --}}

@php
    $colorMap = [
        'blue'   => ['bg' => 'bg-blue-50',    'text' => 'text-blue-500'],
        'amber'  => ['bg' => 'bg-amber-50',   'text' => 'text-amber-500'],
        'emerald'=> ['bg' => 'bg-emerald-50',  'text' => 'text-emerald-500'],
        'violet' => ['bg' => 'bg-violet-50',   'text' => 'text-violet-500'],
        'rose'   => ['bg' => 'bg-rose-50',     'text' => 'text-rose-500'],
    ];
    $c = $colorMap[$color ?? 'blue'];
@endphp

<div class="bg-white rounded-2xl p-5 border border-gray-100">
    <div class="flex items-center justify-between mb-4">
        <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</span>
        <span class="w-8 h-8 rounded-lg {{ $c['bg'] }} {{ $c['text'] }} flex items-center justify-center">
            <span class="material-symbols-outlined text-lg">{{ $icon }}</span>
        </span>
    </div>
    <p class="text-2xl font-semibold text-gray-900">{!! $value !!}</p>
    @if (!empty($trend))
        <p class="text-xs text-emerald-500 mt-2 font-medium">{{ $trend }}</p>
    @elseif (!empty($subtitle))
        <p class="text-xs text-gray-400 mt-2">{{ $subtitle }}</p>
    @endif
</div>
