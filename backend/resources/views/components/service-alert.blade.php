{{-- Service Alert Item Component --}}
{{-- Usage: @include('components.service-alert', ['level' => 'critical', 'name' => '...', 'current' => 49982, 'threshold' => 50000]) --}}

@php
    $pct = round(($current / $threshold) * 100);
    $isCritical = ($level ?? '') === 'critical';
@endphp

<div class="p-4 rounded-xl border {{ $isCritical ? 'bg-rose-50/50 border-rose-100' : 'bg-gray-50/50 border-gray-100' }}">
    <div class="flex justify-between items-center mb-2">
        <span class="text-[10px] font-semibold uppercase tracking-wide {{ $isCritical ? 'text-rose-600' : 'text-gray-500' }}">
            {{ $isCritical ? 'Segera' : 'Terjadwal' }}
        </span>
        <span class="material-symbols-outlined text-base {{ $isCritical ? 'text-rose-400' : 'text-gray-400' }}">
            {{ $isCritical ? 'warning' : 'schedule' }}
        </span>
    </div>
    <p class="text-sm font-medium text-gray-900">{{ $name }}</p>
    <p class="text-[11px] text-gray-400 mt-1 mb-3">{{ number_format($current, 0, ',', '.') }} / {{ number_format($threshold, 0, ',', '.') }} KM</p>
    <div class="w-full h-1.5 rounded-full overflow-hidden {{ $isCritical ? 'bg-rose-100' : 'bg-gray-200' }}">
        <div class="h-full rounded-full {{ $isCritical ? 'bg-rose-500' : 'bg-gray-400' }}" style="width: {{ min($pct, 100) }}%"></div>
    </div>
</div>
