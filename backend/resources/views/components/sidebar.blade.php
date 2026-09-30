<aside id="sidebar"
    class="bg-white h-screen w-64 fixed left-0 top-0 border-r border-slate-200 flex flex-col z-[45] hidden md:flex -translate-x-full md:translate-x-0 pb-16 md:pb-0 transition-transform duration-300 ease-in-out"
    style="overflow-y: auto; overflow-x: hidden; scrollbar-gutter: stable;">

    {{-- Logo / Brand --}}
    <div class="p-6 pt-16 md:pt-6 flex items-center justify-between gap-3 w-full">
        <div id="sidebarBrand" class="flex items-center gap-3 min-w-0">
            <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-white text-sm">directions_car</span>
            </div>
            <div class="sidebar-text overflow-hidden whitespace-nowrap">
                <h1 class="text-slate-900 font-bold tracking-tight text-lg leading-none">Precision Fleet</h1>
                <p class="text-[10px] text-slate-500 font-semibold tracking-wider uppercase mt-1">Enterprise ERP</p>
            </div>
        </div>
        <button onclick="sidebarToggleClick()" id="sidebarToggleBtn"
            class="p-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors shrink-0">
            <span id="sidebarToggleIcon" class="material-symbols-outlined">menu</span>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="mt-4 flex-grow px-3 space-y-1">

        {{-- Dashboard --}}
        <a href="{{ route('admin.dashboard') }}"
            class="sidebar-link flex items-center px-4 py-2.5 rounded-lg font-medium transition-all duration-200
            {{ request()->routeIs('admin.dashboard') ? 'text-brand-700 bg-brand-50' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
            <span class="material-symbols-outlined mr-3 text-[20px] shrink-0">grid_view</span>
            <span class="sidebar-text text-sm whitespace-nowrap">Dashboard</span>
        </a>

        {{-- Penyewaan (dengan sub-menu) --}}
        <div class="relative">
            <div
                class="sidebar-link w-full flex items-center justify-between rounded-lg font-medium transition-all duration-200
                {{ request()->routeIs('admin.bookings*') ? 'text-brand-700 bg-brand-50' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <a href="{{ route('admin.bookings') }}" class="flex items-center min-w-0 flex-1 pl-4 py-2.5">
                    <span class="material-symbols-outlined mr-3 text-[20px] shrink-0">event_note</span>
                    <span class="sidebar-text text-sm whitespace-nowrap">Penyewaan</span>
                </a>
                <button type="button" onclick="toggleSubMenu('bookingMenu', 'bookingChevron')"
                    class="sidebar-text flex items-center pr-4 py-2.5 shrink-0">
                    <span id="bookingChevron"
                        class="material-symbols-outlined text-[20px] transition-transform duration-200 {{ request()->routeIs('admin.bookings*') ? 'rotate-180' : '' }}">
                        expand_more
                    </span>
                </button>
            </div>

            <div id="bookingMenu"
                class="sidebar-submenu flex flex-col gap-1 mt-1 overflow-hidden transition-all duration-300 {{ request()->routeIs('admin.bookings*') ? 'block' : 'hidden' }}">
                <a href="{{ route('admin.bookings') }}"
                    class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs('admin.bookings', 'admin.bookings.show', 'admin.bookings.edit') ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    Daftar Penyewaan
                </a>
                <a href="{{ route('admin.bookings.create') }}"
                    class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs('admin.bookings.create') ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    Buat Pesanan
                </a>
                <a href="{{ route('admin.bookings.calendar') }}"
                    class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs('admin.bookings.calendar') ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    Kalender Penyewaan
                </a>
            </div>
        </div>

        {{-- Manajemen Armada (dengan sub-menu) --}}
        <div class="relative">
            <div
                class="sidebar-link w-full flex items-center justify-between rounded-lg font-medium transition-all duration-200
        {{ request()->routeIs('admin.armada*') ? 'text-brand-700 bg-brand-50' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <a href="{{ route('admin.armada') }}" class="flex items-center min-w-0 flex-1 pl-4 py-2.5">
                    <span class="material-symbols-outlined mr-3 text-[20px] shrink-0">directions_car</span>
                    <span class="sidebar-text text-sm whitespace-nowrap">Manajemen Armada</span>
                </a>
                <button type="button" onclick="toggleSubMenu('armadaMenu', 'armadaChevron')"
                    class="sidebar-text flex items-center pr-4 py-2.5 shrink-0">
                    <span id="armadaChevron"
                        class="material-symbols-outlined text-[20px] transition-transform duration-200 {{ request()->routeIs('admin.armada*') ? 'rotate-180' : '' }}">
                        expand_more
                    </span>
                </button>
            </div>

            <div id="armadaMenu"
                class="sidebar-submenu flex flex-col gap-1 mt-1 overflow-hidden transition-all duration-300 {{ request()->routeIs('admin.armada*') ? 'block' : 'hidden' }}">

                {{-- Daftar Kendaraan --}}
                <a href="{{ route('admin.armada') }}"
                    class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs('admin.armada') || request()->routeIs('admin.armada.edit') || request()->routeIs('admin.armada.show') ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    Daftar Kendaraan
                </a>

                {{-- Tambah Mobil --}}
                <a href="{{ route('admin.armada.create') }}"
                    class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs('admin.armada.create') ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    Tambah Mobil
                </a>

                {{-- Servis & Bengkel --}}
                <a href="{{ route('admin.armada.bengkel') }}"
                    class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs('admin.armada.bengkel') ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    Servis & Bengkel
                </a>
            </div>
        </div>

        {{-- Keuangan (dengan sub-menu) --}}
        <div class="relative">
            <div
                class="sidebar-link w-full flex items-center justify-between rounded-lg font-medium transition-all duration-200
                {{ request()->routeIs('admin.keuangan*') ? 'text-brand-700 bg-brand-50' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <a href="{{ route('admin.keuangan') }}" class="flex items-center min-w-0 flex-1 pl-4 py-2.5">
                    <span class="material-symbols-outlined mr-3 text-[20px] shrink-0">payments</span>
                    <span class="sidebar-text text-sm whitespace-nowrap">Keuangan</span>
                </a>
                <button type="button" onclick="toggleSubMenu('keuanganMenu', 'keuanganChevron')"
                    class="sidebar-text flex items-center pr-4 py-2.5 shrink-0">
                    <span id="keuanganChevron"
                        class="material-symbols-outlined text-[20px] transition-transform duration-200 {{ request()->routeIs('admin.keuangan*') ? 'rotate-180' : '' }}">
                        expand_more
                    </span>
                </button>
            </div>

            <div id="keuanganMenu"
                class="sidebar-submenu flex flex-col gap-1 mt-1 overflow-hidden transition-all duration-300 {{ request()->routeIs('admin.keuangan*') ? 'block' : 'hidden' }}">
                <a href="{{ route('admin.keuangan') }}"
                    class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs('admin.keuangan') && !request()->routeIs('admin.keuangan.per-mobil') && !request()->routeIs('admin.keuangan.pengeluaran-kantor') ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    Laporan Performa Bisnis
                </a>
                <a href="{{ route('admin.keuangan.per-mobil') }}"
                    class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs('admin.keuangan.per-mobil') ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    Laporan Keuangan Per Mobil
                </a>
                <a href="{{ route('admin.keuangan.pengeluaran-kantor') }}"
                    class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs('admin.keuangan.pengeluaran-kantor') ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    Pengeluaran Kantor
                </a>
            </div>
        </div>

        {{-- Manajemen User --}}
        <a href="{{ route('admin.users.index') }}"
            class="sidebar-link flex items-center px-4 py-2.5 rounded-lg font-medium transition-all duration-200
            {{ request()->routeIs('admin.users*') ? 'text-brand-700 bg-brand-50' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
            <span class="material-symbols-outlined mr-3 text-[20px] shrink-0">person</span>
            <span class="sidebar-text text-sm whitespace-nowrap">Manajemen User</span>
        </a>

        {{-- Surat Menyurat (dengan sub-menu) --}}
        <div class="relative">
            <div
                class="sidebar-link w-full flex items-center justify-between rounded-lg font-medium transition-all duration-200
                {{ request()->routeIs('admin.surat-menyurat*') ? 'text-brand-700 bg-brand-50' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <a href="{{ route('admin.surat-menyurat') }}" class="flex items-center min-w-0 flex-1 pl-4 py-2.5">
                    <span class="material-symbols-outlined mr-3 text-[20px] shrink-0">description</span>
                    <span class="sidebar-text text-sm whitespace-nowrap">Surat Menyurat</span>
                </a>
                <button type="button" onclick="toggleSubMenu('suratMenu', 'suratChevron')"
                    class="sidebar-text flex items-center pr-4 py-2.5 shrink-0">
                    <span id="suratChevron"
                        class="material-symbols-outlined text-[20px] transition-transform duration-200 {{ request()->routeIs('admin.surat-menyurat*') ? 'rotate-180' : '' }}">
                        expand_more
                    </span>
                </button>
            </div>

            <div id="suratMenu"
                class="sidebar-submenu flex flex-col gap-1 mt-1 overflow-hidden transition-all duration-300 {{ request()->routeIs('admin.surat-menyurat*') ? 'block' : 'hidden' }}">
                <a href="{{ route('admin.surat-menyurat') }}"
                    class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs('admin.surat-menyurat') && !request()->routeIs('admin.surat-menyurat.template*') && !request()->routeIs('admin.surat-menyurat.arsip') ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    Nomor Surat
                </a>
                <a href="{{ route('admin.surat-menyurat.arsip') }}"
                    class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs('admin.surat-menyurat.arsip') ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    Arsip
                </a>
                <a href="{{ route('admin.surat-menyurat.template') }}"
                    class="flex items-center pl-12 pr-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs('admin.surat-menyurat.template*') ? 'text-brand-700 bg-brand-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    Template Surat
                </a>
            </div>
        </div>

        <div class="pt-4 pb-2 border-t border-slate-100 mt-2 space-y-2">
            <a href="{{ route('admin.qr-scanner') }}"
                class="sidebar-link w-full flex items-center justify-center px-4 py-2.5 rounded-lg font-medium transition-all duration-200 shadow-sm
                {{ request()->routeIs('admin.qr-scanner') ? 'text-brand-700 bg-brand-50' : 'text-slate-700 bg-white border border-slate-300 hover:bg-slate-50' }}">
                <span class="material-symbols-outlined mr-2 text-[20px] shrink-0">qr_code_scanner</span>
                <span class="sidebar-text text-sm whitespace-nowrap">QR Scanner</span>
            </a>
        </div>
    </nav>

    {{-- CTA New Booking --}}
    <div class="px-3 mb-4 mt-auto">
        <a href="{{ route('admin.bookings.create') }}"
            class="sidebar-link w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 rounded-lg flex items-center justify-center transition-colors">
            <span class="material-symbols-outlined mr-2 text-[20px] shrink-0">add</span>
            <span class="sidebar-text text-sm whitespace-nowrap">New Booking</span>
        </a>
    </div>

    {{-- Footer nav --}}
    <div class="px-3 pb-6 space-y-1">
        <a href="#"
            class="sidebar-link flex items-center px-4 py-2.5 rounded-lg font-medium transition-all duration-200
            text-slate-600 hover:bg-slate-100 hover:text-slate-900">
            <span class="material-symbols-outlined mr-3 text-[20px] shrink-0">settings</span>
            <span class="sidebar-text text-sm whitespace-nowrap">Settings</span>
        </a>
        <a href="#"
            class="sidebar-link flex items-center px-4 py-2.5 rounded-lg font-medium transition-all duration-200
            text-slate-600 hover:bg-slate-100 hover:text-slate-900">
            <span class="material-symbols-outlined mr-3 text-[20px] shrink-0">help</span>
            <span class="sidebar-text text-sm whitespace-nowrap">Support</span>
        </a>
    </div>
</aside>