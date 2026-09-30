<header id="topbar"
    class="fixed top-0 right-0 w-full md:w-[calc(100%-16rem)] z-50 bg-white/80 backdrop-blur flex justify-between items-center h-16 px-4 md:px-8 border-b border-slate-200">
    <div class="flex items-center flex-1 max-w-xl min-w-0">
        <button onclick="toggleSidebar()" id="mobileSidebarToggle"
            class="text-slate-400 mr-2 md:mr-3 p-2 rounded-lg hover:bg-slate-100 transition-colors duration-200 md:hidden">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="relative w-full min-w-0">
            <span
                class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
            <input
                class="w-full min-w-0 bg-slate-100/50 border border-slate-200 rounded-lg py-2 pl-10 pr-4 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all placeholder-slate-400"
                placeholder="Search vehicle, booking, or customer..." type="text" />
        </div>
    </div>

    @if(request()->routeIs('admin.dashboard'))
    <div class="hidden md:flex ml-8 space-x-6 text-sm font-medium text-slate-500">
        <button onclick="window.switchTab('overview', this)"
            class="tab-btn pb-3 text-brand-600 border-b-2 border-brand-600 transition-colors">Overview</button>
        <button onclick="window.switchTab('analytics', this)"
            class="tab-btn pb-3 text-slate-500 border-b-2 border-transparent hover:text-slate-900 transition-colors">Analytics</button>
        <button onclick="window.switchTab('activity', this)"
            class="tab-btn pb-3 text-slate-500 border-b-2 border-transparent hover:text-slate-900 transition-colors">Activity</button>
    </div>
    @endif

    <div class="flex items-center gap-2 md:gap-5 ml-auto">

        {{-- ── Notification Bell ─────────────────────────────────── --}}
        <div class="relative" id="notifWrapper">
            <button onclick="toggleNotif(event)"
                class="relative text-slate-400 hover:text-slate-600 transition-colors focus:outline-none">
                <span class="material-symbols-outlined">notifications</span>
                {{-- Badge --}}
                <span id="notifBadge"
                    class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
            </button>

            {{-- Dropdown Panel --}}
            <div id="notifDropdown"
                class="hidden absolute right-0 mt-3 w-[min(360px,calc(100vw-2rem))] bg-white rounded-xl shadow-2xl shadow-slate-200/80 border border-slate-100 overflow-hidden z-50">

                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-3.5 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-800">Notifikasi</span>
                        <span
                            class="bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none">3</span>
                    </div>
                    <button onclick="markAllRead()"
                        class="text-xs text-brand-600 hover:text-brand-700 font-medium transition-colors">
                        Tandai semua dibaca
                    </button>
                </div>

                {{-- Tabs --}}
                <div class="flex border-b border-slate-100 px-4">
                    <button onclick="switchTab('semua', this)"
                        class="notif-tab text-xs font-medium py-2.5 mr-5 text-brand-600 border-b-2 border-brand-600 transition-colors">
                        Semua
                    </button>
                    <button onclick="switchTab('servis', this)"
                        class="notif-tab text-xs font-medium py-2.5 mr-5 text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition-colors">
                        Servis
                    </button>
                    <button onclick="switchTab('pembayaran', this)"
                        class="notif-tab text-xs font-medium py-2.5 text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition-colors">
                        Pembayaran
                    </button>
                </div>

                {{-- Notification Items --}}
                <div id="notifList" class="max-h-[340px] overflow-y-auto divide-y divide-slate-50">

                    {{-- Item 1: Unread – Servis Kritis --}}
                    <div
                        class="notif-item notif-servis flex items-start gap-3 px-4 py-3.5 bg-blue-50/40 hover:bg-slate-50 transition-colors cursor-pointer">
                        <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-red-500 text-[18px]">warning</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800">Servis Segera – Toyota Camry</p>
                            <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                Odometer mencapai <span class="font-medium text-slate-700">49.982 KM</span>, batas
                                servis 50.000 KM.
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1.5">5 menit yang lalu</p>
                        </div>
                        <span class="w-2 h-2 bg-brand-500 rounded-full shrink-0 mt-2"></span>
                    </div>

                    {{-- Item 2: Unread – Pembayaran --}}
                    <div
                        class="notif-item notif-pembayaran flex items-start gap-3 px-4 py-3.5 bg-blue-50/40 hover:bg-slate-50 transition-colors cursor-pointer">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-emerald-500 text-[18px]">payments</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800">Invoice #1042 Lunas</p>
                            <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                Pembayaran dari <span class="font-medium text-slate-700">PT Maju Bersama</span> sebesar
                                Rp 4.500.000 diterima.
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1.5">32 menit yang lalu</p>
                        </div>
                        <span class="w-2 h-2 bg-brand-500 rounded-full shrink-0 mt-2"></span>
                    </div>

                    {{-- Item 3: Unread – Pengembalian --}}
                    <div
                        class="notif-item notif-servis flex items-start gap-3 px-4 py-3.5 bg-blue-50/40 hover:bg-slate-50 transition-colors cursor-pointer">
                        <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-amber-500 text-[18px]">directions_car</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800">Pengembalian Terlambat</p>
                            <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                <span class="font-medium text-slate-700">Honda Odyssey – B 9900 VIP</span> belum
                                dikembalikan sejak kemarin.
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1.5">1 jam yang lalu</p>
                        </div>
                        <span class="w-2 h-2 bg-brand-500 rounded-full shrink-0 mt-2"></span>
                    </div>

                    {{-- Item 4: Read --}}
                    <div
                        class="notif-item notif-pembayaran flex items-start gap-3 px-4 py-3.5 hover:bg-slate-50 transition-colors cursor-pointer">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-slate-400 text-[18px]">receipt_long</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-600">Invoice #1038 Digenerate</p>
                            <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">
                                Invoice untuk pesanan <span class="font-medium">Jonathan W.</span> berhasil dibuat.
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1.5">Kemarin, 14:22</p>
                        </div>
                    </div>

                    {{-- Item 5: Read --}}
                    <div
                        class="notif-item notif-servis flex items-start gap-3 px-4 py-3.5 hover:bg-slate-50 transition-colors cursor-pointer">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-slate-400 text-[18px]">build</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-600">Mercedes E300 – Mendekati Servis</p>
                            <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">
                                14.200 dari 15.000 KM threshold servis.
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1.5">Kemarin, 09:05</p>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/50">
                    <a href="#"
                        class="flex items-center justify-center gap-1.5 text-xs text-brand-600 hover:text-brand-700 font-medium transition-colors">
                        Lihat semua notifikasi
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
        {{-- ── End Notification Bell ──────────────────────────────── --}}

        <button class="hidden md:flex items-center text-slate-400 hover:text-slate-600 transition-colors">
            <span class="material-symbols-outlined">qr_code_scanner</span>
        </button>

        <div class="h-8 w-px bg-slate-200 mx-2 hidden md:block"></div>

        {{-- Profile & Logout --}}
        <div class="flex items-center gap-4">
            {{-- Profile Info --}}
            <div class="flex items-center gap-3">
                <div class="hidden md:block text-right">
                    <p class="text-sm font-semibold text-slate-800 leading-none">{{ auth()->user()->nama }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ auth()->user()->role?->nama ?? 'Tanpa role' }}</p>
                </div>
                <img alt="User Avatar" class="w-9 h-9 rounded-full object-cover border border-slate-200 shadow-sm"
                    src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->nama) }}&background=0D8ABC&color=fff" />
            </div>

            {{-- Logout Button --}}
            <form action="{{ route('logout') }}" method="POST" class="m-0 flex items-center">
                @csrf
                <button type="submit" title="Keluar"
                    class="flex items-center justify-center w-8 h-8 rounded-full text-red-600 hover:bg-red-50 transition-colors focus:outline-none">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                </button>
            </form>
        </div>

    </div>
</header>

<script>
    // ── Notification Dropdown ────────────────────────────────────────
    const notifWrapper = document.getElementById('notifWrapper');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifBadge = document.getElementById('notifBadge');

    function toggleNotif(e) {
        e.stopPropagation();
        const isOpen = !notifDropdown.classList.contains('hidden');

        // Tutup user dropdown jika terbuka
        document.getElementById('dropdownMenu')?.classList.add('hidden');

        if (isOpen) {
            closeNotif();
        } else {
            notifDropdown.classList.remove('hidden');
            // Animasi masuk
            notifDropdown.style.opacity = '0';
            notifDropdown.style.transform = 'translateY(-6px)';
            notifDropdown.style.transition = 'opacity 0.15s ease, transform 0.15s ease';
            requestAnimationFrame(() => {
                notifDropdown.style.opacity = '1';
                notifDropdown.style.transform = 'translateY(0)';
            });
        }
    }

    function closeNotif() {
        notifDropdown.style.opacity = '0';
        notifDropdown.style.transform = 'translateY(-6px)';
        setTimeout(() => notifDropdown.classList.add('hidden'), 150);
    }

    // Tandai semua dibaca
    function markAllRead() {
        // Hapus background biru & dot biru dari semua item
        document.querySelectorAll('#notifList .notif-item').forEach(item => {
            item.classList.remove('bg-blue-50/40');
            item.querySelector('.w-2.h-2.bg-brand-500')?.remove();

            // Buat teks jadi lebih redup (ubah font-semibold -> font-medium)
            item.querySelector('p.font-semibold')?.classList.replace('font-semibold', 'font-medium');
            item.querySelector('p.font-semibold')?.classList.replace('text-slate-800', 'text-slate-600');
        });

        // Sembunyikan badge
        notifBadge.classList.add('hidden');

        // Update counter di header dropdown
        const counter = notifDropdown.querySelector('.bg-red-500.text-white');
        if (counter) counter.remove();
    }

    // ── Tab Filter ───────────────────────────────────────────────────
    function switchTab(type, btn) {
        // Update style tab
        document.querySelectorAll('.notif-tab').forEach(t => {
            t.classList.remove('text-brand-600', 'border-brand-600');
            t.classList.add('text-slate-400', 'border-transparent');
        });
        btn.classList.remove('text-slate-400', 'border-transparent');
        btn.classList.add('text-brand-600', 'border-brand-600');

        // Filter items
        const items = document.querySelectorAll('#notifList .notif-item');
        items.forEach(item => {
            if (type === 'semua') {
                item.style.display = '';
            } else {
                item.style.display = item.classList.contains('notif-' + type) ? '' : 'none';
            }
        });
    }

    // ── User Dropdown ────────────────────────────────────────────────
    function toggleDropdown() {
        const menu = document.getElementById('dropdownMenu');
        closeNotif(); // Tutup notif jika terbuka
        menu.classList.toggle('hidden');
    }

    // ── Tutup semua dropdown jika klik di luar ───────────────────────
    document.addEventListener('click', (e) => {
        // Notif
        if (!notifWrapper.contains(e.target)) {
            if (!notifDropdown.classList.contains('hidden')) closeNotif();
        }
        // User dropdown
        const userDropdown = document.getElementById('dropdownMenu');
        if (!e.target.closest('#dropdownMenu') && !e.target.closest('[onclick="toggleDropdown()"]')) {
            userDropdown?.classList.add('hidden');
        }
    });
</script>
