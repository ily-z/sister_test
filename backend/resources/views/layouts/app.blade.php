<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Precision Fleet - Enterprise ERP</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <!-- Cropper.js JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6',
                            600: '#2563eb', 700: '#1d4ed8', 900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        * { scrollbar-width: thin; scrollbar-color: rgba(0,0,0,0.12) transparent; scrollbar-gutter: stable; }
        *::-webkit-scrollbar { width: 6px; height: 6px; }
        *::-webkit-scrollbar-track { background: transparent; }
        *::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); border-radius: 3px; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        /* ── Two-mode Sidebar (open / close) ────────────────────── */
        #sidebar {
            transition: width 0.3s ease-in-out, transform 0.3s ease-in-out;
        }

        /* Mobile: drawer position is handled by Tailwind -translate-x-full / md:translate-x-0 */

        /* Desktop close mode: icon strip only */
        @media (min-width: 768px) {
            .sidebar-collapsed .sidebar-text {
                display: none;
            }
            .sidebar-collapsed .sidebar-submenu {
                display: none !important;
            }
            .sidebar-collapsed #sidebarBrand {
                display: none;
            }
            .sidebar-collapsed > div:first-child {
                padding: 1.5rem 0.75rem;
                justify-content: center;
            }
            /* Hover-open: restore text and open submenus */
            .sidebar-collapsed.sidebar-hover-open .sidebar-text {
                display: revert;
            }
            .sidebar-collapsed.sidebar-hover-open .sidebar-submenu:not(.hidden) {
                display: flex !important;
            }
            .sidebar-collapsed.sidebar-hover-open #sidebarBrand {
                display: flex;
            }
            .sidebar-collapsed.sidebar-hover-open > div:first-child {
                justify-content: space-between;
                padding: 1.5rem;
            }
            .sidebar-collapsed.sidebar-hover-open {
                box-shadow: 4px 0 24px rgba(0, 0, 0, 0.1);
                z-index: 60;
            }
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 min-h-screen overflow-x-hidden selection:bg-brand-100">

    <!-- Memanggil Komponen -->
    @include('components.sidebar')
    <div id="mobile-sidebar-overlay" class="fixed inset-0 bg-black/40 z-40 hidden opacity-0 transition-opacity duration-300 md:hidden"></div>
    @include('components.topbar')

    <!-- Pembungkus Konten Utama -->
    <main class="pt-24 pb-24 md:pb-8 pl-0 md:pl-64 w-full">
        <!-- Fitur Yield ini akan digantikan oleh isi dari index.blade.php -->
        @yield('content')
    </main>

    <!-- Memanggil Navigasi Bawah Khusus Mobile -->
    @include('components.bottom-nav')

    <!-- Global Toast Notification -->
    <div id="global-toast-wrapper" class="fixed top-5 right-5 z-[9999] flex flex-col gap-2 items-end pointer-events-none"></div>

    <!-- Script Global -->
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownMenu');
            if(dropdown) dropdown.classList.toggle('hidden');
        }

        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('dropdownMenu');
            if (dropdown) { // Pengecekan keamanan agar tidak error di halaman lain
                const button = dropdown.previousElementSibling; 
                if (!button.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            }
        });

        function showGlobalToast(message, type = 'success') {
            if (!message) return;

            const wrapper = document.getElementById('global-toast-wrapper');
            if (!wrapper) return;

            const colors = {
                success: {
                    bg: 'bg-emerald-600',
                    icon: 'text-emerald-100',
                    text: 'text-white',
                },
                error: {
                    bg: 'bg-rose-600',
                    icon: 'text-rose-100',
                    text: 'text-white',
                }
            };

            const palette = colors[type] || colors.success;
            const toast = document.createElement('div');
            toast.className = `pointer-events-auto w-full max-w-xs overflow-hidden rounded-xl shadow-lg shadow-slate-900/10 ${palette.bg} transform transition-all duration-300 ease-out translate-x-6 opacity-0`;
            toast.innerHTML = `
                <div class="flex items-center gap-2.5 py-3 px-3.5">
                    <span class="material-symbols-outlined ${palette.icon} text-[20px] shrink-0 leading-none">${type === 'error' ? 'error' : 'check_circle'}</span>
                    <p class="flex-1 min-w-0 text-sm font-medium leading-snug ${palette.text} line-clamp-2">${message}</p>
                    <button type="button" aria-label="Tutup notifikasi" class="shrink-0 flex items-center justify-center rounded-full w-5 h-5 text-white/70 hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-[16px] leading-none">close</span>
                    </button>
                </div>
            `;

            const closeButton = toast.querySelector('button');
            closeButton?.addEventListener('click', () => {
                toast.classList.remove('translate-x-0', 'opacity-100');
                toast.classList.add('translate-x-6', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            });

            wrapper.appendChild(toast);
            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-6');
                toast.classList.add('translate-x-0', 'opacity-100');
            });

            setTimeout(() => {
                if (!toast.parentElement) return;
                toast.classList.remove('translate-x-0', 'opacity-100');
                toast.classList.add('translate-x-6', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        const globalSuccessMessage = @json(session('success'));
        const globalErrorMessage = @json(session('error') ?? ($errors->any() ? $errors->first() : null));

        if (globalSuccessMessage) {
            showGlobalToast(globalSuccessMessage, 'success');
        } else if (globalErrorMessage) {
            showGlobalToast(globalErrorMessage, 'error');
        }

        // Fungsi untuk buka-tutup sub-menu sidebar
        function toggleSubMenu(menuId, chevronId) {
            const menu = document.getElementById(menuId);
            const chevron = document.getElementById(chevronId);
            
            menu.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180'); // Memutar ikon panah
        }

        function openPreview() {
                const panel = document.getElementById('invoicePreviewPanel');
                const overlay = document.getElementById('invoiceOverlay');

                if (panel && overlay) {
                    // Munculkan overlay transparan
                    overlay.classList.remove('hidden');

                    // Gunakan timeout kecil agar animasi geser (translate) terlihat mulus
                    setTimeout(() => {
                        panel.classList.remove('translate-x-full');
                    }, 10);
                }
            }

            function closePreview() {
                const panel = document.getElementById('invoicePreviewPanel');
                const overlay = document.getElementById('invoiceOverlay');

                if (panel && overlay) {
                    // Geser panel kembali ke kanan (sembunyi)
                    panel.classList.add('translate-x-full');

                    // Tunggu animasi geser selesai (300ms) baru sembunyikan overlay
                    setTimeout(() => {
                        overlay.classList.add('hidden');
                    }, 300);
                }
            }

            function openSidebar() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('mobile-sidebar-overlay');
                if (!sidebar || !overlay) return;
                // On mobile, always show as a full-width drawer regardless of collapse state
                sidebar.classList.add('is-expanded-layout');
                sidebar.style.width = '16rem';
                sidebar.classList.remove('hidden');
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                overlay.classList.remove('hidden');
                requestAnimationFrame(() => {
                    overlay.classList.add('opacity-100');
                });
                document.body.classList.add('overflow-hidden');
            }

            function closeSidebar() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('mobile-sidebar-overlay');
                if (!sidebar || !overlay) return;
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
                overlay.classList.remove('opacity-100');
                setTimeout(() => {
                    sidebar.classList.add('hidden');
                    overlay.classList.add('hidden');
                }, 300);
                document.body.classList.remove('overflow-hidden');
            }

            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                if (!sidebar) return;
                if (window.innerWidth >= 768) {
                    // Desktop: toggle collapse instead
                    toggleSidebarCollapse();
                    return;
                }
                if (sidebar.classList.contains('hidden')) {
                    openSidebar();
                } else {
                    closeSidebar();
                }
            }

            function sidebarToggleClick() {
                if (window.innerWidth >= 768) {
                    toggleSidebarCollapse();
                } else {
                    toggleSidebar();
                }
            }

            // ── Collapsible sidebar (desktop) ──────────────────────────

            function isSidebarCollapsed() {
                const sidebar = document.getElementById('sidebar');
                return sidebar && sidebar.classList.contains('sidebar-collapsed');
            }

            // Update the width + expanded-layout class based on current state
            function renderSidebarLayout() {
                const sidebar = document.getElementById('sidebar');
                if (!sidebar) return;

                const collapsed = sidebar.classList.contains('sidebar-collapsed');

                const expandedLayout = !collapsed;
                sidebar.classList.toggle('is-expanded-layout', expandedLayout);
                sidebar.style.width = expandedLayout ? '16rem' : '4.5rem';

                const main = document.querySelector('main');
                const header = document.getElementById('topbar');
                const narrow = collapsed;

                if (main) {
                    main.classList.toggle('md:pl-20', narrow);
                    main.classList.toggle('md:pl-64', !narrow);
                }
                if (header) {
                    header.classList.toggle('md:w-[calc(100%-4.5rem)]', narrow);
                    header.classList.toggle('md:w-[calc(100%-16rem)]', !narrow);
                }
            }

            // ── Desktop hover behavior for close mode ──────────────
            function setupDesktopHover() {
                const sidebar = document.getElementById('sidebar');
                if (!sidebar) return;

                let hoverTimeout;

                // Open on hover, EXCEPT when the pointer is over the toggle button area
                sidebar.addEventListener('mouseover', function(e) {
                    if (window.innerWidth >= 768 && sidebar.classList.contains('sidebar-collapsed') && !sidebar.classList.contains('sidebar-hover-open')) {
                        const toggleBtn = document.getElementById('sidebarToggleBtn');
                        const brandRow = sidebar.firstElementChild;
                        // Ignore hover over the toggle button / brand row region
                        if (toggleBtn && toggleBtn.contains(e.target)) return;
                        if (brandRow && brandRow.contains(e.target)) return;
                        clearTimeout(hoverTimeout);
                        sidebar.classList.add('sidebar-hover-open');
                        sidebar.style.width = '16rem';
                    }
                });

                sidebar.addEventListener('mouseleave', function() {
                    if (window.innerWidth >= 768 && sidebar.classList.contains('sidebar-collapsed') && sidebar.classList.contains('sidebar-hover-open')) {
                        hoverTimeout = setTimeout(function() {
                            sidebar.classList.remove('sidebar-hover-open');
                            sidebar.style.width = '4.5rem';
                        }, 100);
                    }
                });

                // Close hover when clicking a navigation link (not submenu toggle)
                sidebar.querySelectorAll('a').forEach(function(link) {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 768) {
                            closeSidebar();
                            return;
                        }
                        if (window.innerWidth >= 768 && sidebar.classList.contains('sidebar-collapsed') && sidebar.classList.contains('sidebar-hover-open')) {
                            sidebar.classList.remove('sidebar-hover-open');
                            sidebar.style.width = '4.5rem';
                        }
                    });
                });
            }

            function toggleSidebarCollapse() {
                const sidebar = document.getElementById('sidebar');
                if (!sidebar) return;
                sidebar.classList.remove('sidebar-hover-open');
                sidebar.classList.toggle('sidebar-collapsed');
                updateSidebarIcon();
                renderSidebarLayout();
                try {
                    localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('sidebar-collapsed') ? '1' : '0');
                } catch (e) {}
            }

            window.toggleSidebarCollapse = toggleSidebarCollapse;

            function updateSidebarIcon() {
                const sidebar = document.getElementById('sidebar');
                const icon = document.getElementById('sidebarToggleIcon');
                if (!icon || !sidebar) return;
                icon.textContent = sidebar.classList.contains('sidebar-collapsed') ? 'menu' : 'close';
            }

            function applyPersistedSidebarState() {
                const sidebar = document.getElementById('sidebar');
                if (!sidebar || window.innerWidth < 768) return;
                let collapsed = false;
                try {
                    collapsed = localStorage.getItem('sidebar-collapsed') === '1';
                } catch (e) {}
                sidebar.classList.toggle('sidebar-collapsed', collapsed);
            }

            // Initialize on load
            document.addEventListener('DOMContentLoaded', function() {
                const sidebar = document.getElementById('sidebar');
                if (!sidebar) return;

                applyPersistedSidebarState();
                updateSidebarIcon();
                renderSidebarLayout();
                setupDesktopHover();
            });

            // Keep layout in sync on window resize
            window.addEventListener('resize', renderSidebarLayout);

            // Guard against the DOMContentLoaded not firing (script is inline at end of body)
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                const sidebar = document.getElementById('sidebar');
                if (sidebar) {
                    applyPersistedSidebarState();
                    updateSidebarIcon();
                    renderSidebarLayout();
                    setupDesktopHover();
                }
            }

            document.getElementById('mobile-sidebar-overlay')?.addEventListener('click', closeSidebar);
    </script>
    @vite('resources/js/app.js')
    @stack('scripts')
</body>
</html>