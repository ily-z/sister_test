<!-- BottomNavBar khusus layar kecil (Mobile) -->
<nav class="fixed bottom-0 inset-x-0 z-50 bg-white border-t border-slate-200 flex md:hidden justify-between items-end h-16 px-2 pb-[env(safe-area-inset-bottom)]">
    <a href="{{ route('admin.dashboard') }}"
        class="flex flex-col items-center justify-center flex-1 py-2 text-slate-400 transition-colors {{ request()->routeIs('admin.dashboard') ? 'text-brand-600' : 'hover:text-slate-600' }}">
        <span class="material-symbols-outlined text-[22px]">grid_view</span>
        <span class="text-[10px] font-medium mt-0.5 {{ request()->routeIs('admin.dashboard') ? 'text-brand-600' : '' }}">Home</span>
    </a>
    <a href="{{ route('admin.bookings') }}"
        class="flex flex-col items-center justify-center flex-1 py-2 text-slate-400 transition-colors {{ request()->routeIs('admin.bookings*') ? 'text-brand-600' : 'hover:text-slate-600' }}">
        <span class="material-symbols-outlined text-[22px]">event_note</span>
        <span class="text-[10px] font-medium mt-0.5 {{ request()->routeIs('admin.bookings*') ? 'text-brand-600' : '' }}">Sewa</span>
    </a>
    <a href="{{ route('admin.bookings.create') }}"
        class="flex flex-col items-center justify-center flex-1 py-2 text-brand-600">
        <span class="bg-brand-600 text-white rounded-full p-3 -mb-6 shadow-lg active:scale-95 transition-transform flex items-center justify-center">
            <span class="material-symbols-outlined text-[24px]">add</span>
        </span>
    </a>
    <a href="{{ route('admin.armada') }}"
        class="flex flex-col items-center justify-center flex-1 py-2 text-slate-400 transition-colors {{ request()->routeIs('admin.armada*') ? 'text-brand-600' : 'hover:text-slate-600' }}">
        <span class="material-symbols-outlined text-[22px]">directions_car</span>
        <span class="text-[10px] font-medium mt-0.5 {{ request()->routeIs('admin.armada*') ? 'text-brand-600' : '' }}">Armada</span>
    </a>
    <a href="{{ route('admin.keuangan') }}"
        class="flex flex-col items-center justify-center flex-1 py-2 text-slate-400 transition-colors {{ request()->routeIs('admin.keuangan*') ? 'text-brand-600' : 'hover:text-slate-600' }}">
        <span class="material-symbols-outlined text-[22px]">payments</span>
        <span class="text-[10px] font-medium mt-0.5 {{ request()->routeIs('admin.keuangan*') ? 'text-brand-600' : '' }}">Keuangan</span>
    </a>
    <a href="{{ route('admin.users.index') }}"
        class="flex flex-col items-center justify-center flex-1 py-2 text-slate-400 transition-colors {{ request()->routeIs('admin.users*') ? 'text-brand-600' : 'hover:text-slate-600' }}">
        <span class="material-symbols-outlined text-[22px]">person</span>
        <span class="text-[10px] font-medium mt-0.5 {{ request()->routeIs('admin.users*') ? 'text-brand-600' : '' }}">User</span>
    </a>
</nav>