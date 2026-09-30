<!DOCTYPE html>

<html lang="id"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>DriveOps ERP - Dashboard</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                            "on-error-container": "#93000a",
                            "primary-container": "#4f46e5",
                            "primary-fixed-dim": "#c3c0ff",
                            "surface-dim": "#dcd8e5",
                            "on-tertiary-fixed": "#351000",
                            "on-secondary-container": "#fffbff",
                            "on-primary-fixed-variant": "#3323cc",
                            "tertiary-container": "#a44100",
                            "on-primary-container": "#dad7ff",
                            "tertiary": "#7e3000",
                            "secondary-fixed-dim": "#c0c1ff",
                            "on-primary-fixed": "#0f0069",
                            "on-tertiary-container": "#ffd2be",
                            "inverse-on-surface": "#f3effc",
                            "surface-container": "#f0ecf9",
                            "surface-container-high": "#eae6f4",
                            "surface-variant": "#e4e1ee",
                            "surface-tint": "#4d44e3",
                            "on-background": "#1b1b24",
                            "primary-fixed": "#e2dfff",
                            "error": "#ba1a1a",
                            "background": "#fcf8ff",
                            "on-secondary-fixed": "#07006c",
                            "secondary": "#4648d4",
                            "surface-container-highest": "#e4e1ee",
                            "error-container": "#ffdad6",
                            "outline": "#777587",
                            "on-surface": "#1b1b24",
                            "surface-container-low": "#f5f2ff",
                            "tertiary-fixed-dim": "#ffb695",
                            "secondary-fixed": "#e1e0ff",
                            "inverse-surface": "#302f39",
                            "surface-bright": "#fcf8ff",
                            "on-secondary-fixed-variant": "#2f2ebe",
                            "inverse-primary": "#c3c0ff",
                            "on-primary": "#ffffff",
                            "on-error": "#ffffff",
                            "on-surface-variant": "#464555",
                            "tertiary-fixed": "#ffdbcc",
                            "on-secondary": "#ffffff",
                            "primary": "#3525cd",
                            "surface-container-lowest": "#ffffff",
                            "outline-variant": "#c7c4d8",
                            "on-tertiary-fixed-variant": "#7b2f00",
                            "secondary-container": "#6063ee",
                            "surface": "#fcf8ff",
                            "on-tertiary": "#ffffff"
                    },
                    "borderRadius": {
                            "DEFAULT": "0.25rem",
                            "lg": "0.5rem",
                            "xl": "0.75rem",
                            "full": "9999px"
                    },
                    "spacing": {
                            "md": "16px",
                            "gutter": "24px",
                            "lg": "24px",
                            "xs": "4px",
                            "margin": "32px",
                            "sm": "8px",
                            "base": "4px",
                            "xl": "32px"
                    },
                    "fontFamily": {
                            "h3": ["Inter"],
                            "body-md": ["Inter"],
                            "body-sm": ["Inter"],
                            "label-md": ["Inter"],
                            "display-xl": ["Inter"],
                            "h1": ["Inter"],
                            "body-lg": ["Inter"],
                            "h2": ["Inter"],
                            "label-sm": ["Inter"]
                    },
                    "fontSize": {
                            "h3": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                            "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                            "body-sm": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                            "label-md": ["14px", {"lineHeight": "20px", "fontWeight": "500"}],
                            "display-xl": ["36px", {"lineHeight": "44px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                            "h1": ["30px", {"lineHeight": "38px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                            "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                            "h2": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                            "label-sm": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}]
                    }
                }
            }
        }
    </script>
<style>
        body { background-color: #F8F9FA; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0; }
        .fill-icon { font-variation-settings: 'FILL' 1; }
        .ambient-shadow { box-shadow: 0 4px 24px -4px rgba(0, 0, 0, 0.04), 0 2px 8px -2px rgba(0, 0, 0, 0.02); }
    </style>
</head>
<body class="font-body-md text-body-md text-on-surface antialiased">
<!-- SideNavBar -->
<nav class="fixed h-screen w-64 left-0 top-0 border-r border-gray-100 bg-white shadow-sm flex flex-col h-full py-6 z-50">
<div class="px-6 mb-8">
<div class="text-xl font-bold text-indigo-600">DriveOps</div>
<div class="text-sm text-gray-500 font-inter">Fleet Management</div>
</div>
<div class="flex-1 overflow-y-auto">
<ul class="space-y-1">
<li>
<a class="flex items-center gap-3 px-4 py-3 text-indigo-600 bg-indigo-50/50 border-r-4 border-indigo-600 font-semibold active:opacity-80 transition-all duration-200 hover:bg-gray-50 transition-colors" href="#">
<span class="material-symbols-outlined fill-icon" data-icon="dashboard">dashboard</span>
<span>Dashboard</span>
</a>
</li>
<li>
<a class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:text-gray-900 active:opacity-80 transition-all duration-200 hover:bg-gray-50 transition-colors" href="#">
<span class="material-symbols-outlined" data-icon="directions_car">directions_car</span>
<span>Armada</span>
</a>
</li>
<li>
<a class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:text-gray-900 active:opacity-80 transition-all duration-200 hover:bg-gray-50 transition-colors" href="#">
<span class="material-symbols-outlined" data-icon="history_edu">history_edu</span>
<span>Penyewaan</span>
</a>
</li>
<li>
<a class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:text-gray-900 active:opacity-80 transition-all duration-200 hover:bg-gray-50 transition-colors" href="#">
<span class="material-symbols-outlined" data-icon="payments">payments</span>
<span>Keuangan</span>
</a>
</li>
<li>
<a class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:text-gray-900 active:opacity-80 transition-all duration-200 hover:bg-gray-50 transition-colors" href="#">
<span class="material-symbols-outlined" data-icon="description">description</span>
<span>Invoice</span>
</a>
</li>
<li>
<a class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:text-gray-900 active:opacity-80 transition-all duration-200 hover:bg-gray-50 transition-colors" href="#">
<span class="material-symbols-outlined" data-icon="settings">settings</span>
<span>Pengaturan</span>
</a>
</li>
</ul>
</div>
</nav>
<!-- TopNavBar -->
<header class="fixed top-0 right-0 w-[calc(100%-16rem)] h-16 border-b border-gray-100 bg-white/80 backdrop-blur-md z-40 flex justify-between items-center px-8">
<div class="flex items-center text-lg font-semibold text-gray-900">
            DriveOps ERP
        </div>
<div class="flex items-center gap-4">
<button class="p-2 text-gray-500 hover:text-indigo-600 transition-colors rounded-full focus-within:ring-2 focus-within:ring-indigo-500/20">
<span class="material-symbols-outlined" data-icon="notifications">notifications</span>
</button>
<button class="p-2 text-gray-500 hover:text-indigo-600 transition-colors rounded-full focus-within:ring-2 focus-within:ring-indigo-500/20">
<span class="material-symbols-outlined" data-icon="help_outline">help_outline</span>
</button>
<div class="h-8 w-8 rounded-full bg-gray-200 overflow-hidden">
<img alt="User Profile Avatar" class="h-full w-full object-cover" data-alt="Close up portrait of a smiling professional man in a modern office environment with soft lighting" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA_SH2bKUK_zWk-TW4BRYIpMP-_XeJMxiQzj0NuhaUlnFKhsGck2_JbwxSeKjrQYTxCkI-7ASRLx6u-pkg7h4F1hX374Tv-AvSSp7TtexHdqnlieZZoQIwVv9G3tUconq9FDzN_1_j5XO_vRyloctMZ5QT3-oycW0CsbkZRcW3IBdN6Nqvy7EUE51nHyQi_MRXkkzEkFUWONdoNB3NIIf-XADVSUvyJYKXSPFOMPQQaL5HQQNOfhDkZdmMNBOL9w_3gQxEyD8KUxTo"/>
</div>
</div>
</header>
<!-- Main Content -->
<main class="ml-64 pt-16 p-margin min-h-screen">
<!-- Header -->
<div class="mb-gutter flex justify-between items-end">
<div>
<h1 class="font-h1 text-h1 text-on-surface mb-sm">Dashboard Overview</h1>
<p class="font-body-sm text-body-sm text-outline">Kamis, 24 Oktober 2023</p>
</div>
<button class="bg-primary text-on-primary font-label-md text-label-md px-md py-sm rounded-lg hover:bg-secondary transition-colors shadow-sm">
                + Sewa Baru
            </button>
</div>
<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter mb-gutter">
<!-- Card 1 -->
<div class="bg-surface-container-lowest rounded-xl p-md ambient-shadow border border-surface-variant">
<div class="flex justify-between items-start mb-sm">
<p class="font-label-sm text-label-sm text-outline uppercase tracking-wider">Total Mobil</p>
<span class="material-symbols-outlined text-primary" data-icon="directions_car">directions_car</span>
</div>
<div class="flex items-baseline gap-2">
<h2 class="font-display-xl text-display-xl text-on-surface">42</h2>
<span class="font-label-sm text-label-sm text-green-600 bg-green-50 px-2 py-0.5 rounded-full">+3</span>
</div>
</div>
<!-- Card 2 -->
<div class="bg-surface-container-lowest rounded-xl p-md ambient-shadow border border-surface-variant">
<div class="flex justify-between items-start mb-sm">
<p class="font-label-sm text-label-sm text-outline uppercase tracking-wider">Sewa Aktif</p>
<span class="material-symbols-outlined text-secondary" data-icon="key">key</span>
</div>
<div class="flex items-baseline gap-2">
<h2 class="font-display-xl text-display-xl text-on-surface">18</h2>
<span class="font-label-sm text-label-sm text-red-600 bg-red-50 px-2 py-0.5 rounded-full">-2</span>
</div>
</div>
<!-- Card 3 -->
<div class="bg-surface-container-lowest rounded-xl p-md ambient-shadow border border-surface-variant">
<div class="flex justify-between items-start mb-sm">
<p class="font-label-sm text-label-sm text-outline uppercase tracking-wider">Pendapatan Bulan Ini</p>
<span class="material-symbols-outlined text-green-600" data-icon="account_balance_wallet">account_balance_wallet</span>
</div>
<div class="flex items-baseline gap-2">
<h2 class="font-h2 text-h2 text-on-surface mt-1">Rp 124.5M</h2>
</div>
</div>
<!-- Card 4 -->
<div class="bg-surface-container-lowest rounded-xl p-md ambient-shadow border border-surface-variant">
<div class="flex justify-between items-start mb-sm">
<p class="font-label-sm text-label-sm text-outline uppercase tracking-wider">Pengembalian Hari Ini</p>
<span class="material-symbols-outlined text-orange-500" data-icon="event_available">event_available</span>
</div>
<div class="flex items-baseline gap-2">
<h2 class="font-display-xl text-display-xl text-on-surface">5</h2>
<span class="font-label-sm text-label-sm text-outline">Mobil</span>
</div>
</div>
</div>
<!-- Main Row -->
<div class="grid grid-cols-12 gap-gutter mb-gutter">
<!-- Revenue Trends (8 cols) -->
<div class="col-span-12 lg:col-span-8 bg-surface-container-lowest rounded-xl p-lg ambient-shadow border border-surface-variant">
<h3 class="font-h3 text-h3 text-on-surface mb-lg">Revenue Trends</h3>
<div class="w-full h-64 bg-surface-container flex items-end px-4 pt-4 rounded-lg relative overflow-hidden" data-alt="abstract area chart representation showing upward trend in varying shades of indigo on light background">
<!-- Placeholder for Chart -->
<div class="w-1/6 h-[30%] bg-indigo-100 rounded-t-sm mx-1"></div>
<div class="w-1/6 h-[45%] bg-indigo-200 rounded-t-sm mx-1"></div>
<div class="w-1/6 h-[40%] bg-indigo-300 rounded-t-sm mx-1"></div>
<div class="w-1/6 h-[60%] bg-indigo-400 rounded-t-sm mx-1"></div>
<div class="w-1/6 h-[80%] bg-indigo-500 rounded-t-sm mx-1"></div>
<div class="w-1/6 h-[100%] bg-primary rounded-t-sm mx-1"></div>
</div>
</div>
<!-- Status Armada (4 cols) -->
<div class="col-span-12 lg:col-span-4 bg-surface-container-lowest rounded-xl p-lg ambient-shadow border border-surface-variant flex flex-col">
<h3 class="font-h3 text-h3 text-on-surface mb-lg">Status Armada</h3>
<div class="flex-1 flex justify-center items-center relative">
<!-- Placeholder for Donut Chart -->
<div class="w-40 h-40 rounded-full border-[16px] border-surface-container relative">
<div class="absolute inset-0 rounded-full border-[16px] border-primary" style="clip-path: polygon(50% 50%, 100% 0, 100% 100%, 0 100%, 0 50%); transform: rotate(45deg);"></div>
<div class="absolute inset-0 rounded-full border-[16px] border-green-500" style="clip-path: polygon(50% 50%, 0 50%, 0 0, 50% 0);"></div>
</div>
<div class="absolute inset-0 flex items-center justify-center flex-col">
<span class="font-h2 text-h2 text-on-surface">42</span>
<span class="font-label-sm text-label-sm text-outline">Total</span>
</div>
</div>
<div class="mt-lg space-y-3">
<div class="flex justify-between items-center">
<div class="flex items-center gap-2">
<div class="w-3 h-3 rounded-full bg-green-500"></div>
<span class="font-body-sm text-body-sm text-on-surface">Tersedia</span>
</div>
<span class="font-label-md text-label-md text-on-surface">20</span>
</div>
<div class="flex justify-between items-center">
<div class="flex items-center gap-2">
<div class="w-3 h-3 rounded-full bg-primary"></div>
<span class="font-body-sm text-body-sm text-on-surface">Disewa</span>
</div>
<span class="font-label-md text-label-md text-on-surface">18</span>
</div>
<div class="flex justify-between items-center">
<div class="flex items-center gap-2">
<div class="w-3 h-3 rounded-full bg-surface-container-highest"></div>
<span class="font-body-sm text-body-sm text-on-surface">Bengkel</span>
</div>
<span class="font-label-md text-label-md text-on-surface">4</span>
</div>
</div>
</div>
</div>
<!-- Bottom Row -->
<div class="grid grid-cols-12 gap-gutter">
<!-- Pesanan Terbaru Table (8 cols) -->
<div class="col-span-12 lg:col-span-8 bg-surface-container-lowest rounded-xl ambient-shadow border border-surface-variant overflow-hidden">
<div class="p-md border-b border-surface-variant flex justify-between items-center bg-white">
<h3 class="font-h3 text-h3 text-on-surface">Pesanan Terbaru</h3>
<button class="text-primary font-label-md text-label-md hover:text-secondary">Lihat Semua</button>
</div>
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-surface-container-low border-b border-surface-variant">
<th class="py-sm px-md font-label-sm text-label-sm text-outline uppercase tracking-wider">ID Pesanan</th>
<th class="py-sm px-md font-label-sm text-label-sm text-outline uppercase tracking-wider">Pelanggan</th>
<th class="py-sm px-md font-label-sm text-label-sm text-outline uppercase tracking-wider">Mobil</th>
<th class="py-sm px-md font-label-sm text-label-sm text-outline uppercase tracking-wider">Status</th>
<th class="py-sm px-md font-label-sm text-label-sm text-outline uppercase tracking-wider text-right">Total</th>
</tr>
</thead>
<tbody class="divide-y divide-surface-variant bg-white">
<tr class="hover:bg-surface-container-low transition-colors group">
<td class="py-md px-md font-body-sm text-body-sm text-on-surface">#TRX-0921</td>
<td class="py-md px-md font-body-sm text-body-sm text-on-surface">Budi Santoso</td>
<td class="py-md px-md font-body-sm text-body-sm text-on-surface">Avanza (B 1234 CD)</td>
<td class="py-md px-md">
<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">Aktif</span>
</td>
<td class="py-md px-md font-body-sm text-body-sm text-on-surface text-right">Rp 450.000</td>
</tr>
<tr class="hover:bg-surface-container-low transition-colors group">
<td class="py-md px-md font-body-sm text-body-sm text-on-surface">#TRX-0920</td>
<td class="py-md px-md font-body-sm text-body-sm text-on-surface">Siti Aminah</td>
<td class="py-md px-md font-body-sm text-body-sm text-on-surface">Innova (D 5678 EF)</td>
<td class="py-md px-md">
<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">Booking</span>
</td>
<td class="py-md px-md font-body-sm text-body-sm text-on-surface text-right">Rp 800.000</td>
</tr>
<tr class="hover:bg-surface-container-low transition-colors group">
<td class="py-md px-md font-body-sm text-body-sm text-on-surface">#TRX-0919</td>
<td class="py-md px-md font-body-sm text-body-sm text-on-surface">PT. Maju Jaya</td>
<td class="py-md px-md font-body-sm text-body-sm text-on-surface">Hiace (B 9012 GH)</td>
<td class="py-md px-md">
<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-200">Selesai</span>
</td>
<td class="py-md px-md font-body-sm text-body-sm text-on-surface text-right">Rp 1.200.000</td>
</tr>
</tbody>
</table>
</div>
</div>
<!-- Notifikasi Penting (4 cols) -->
<div class="col-span-12 lg:col-span-4 bg-surface-container-lowest rounded-xl p-md ambient-shadow border border-surface-variant">
<div class="flex justify-between items-center mb-md">
<h3 class="font-h3 text-h3 text-on-surface">Notifikasi Penting</h3>
<span class="material-symbols-outlined text-outline" data-icon="notification_important">notification_important</span>
</div>
<div class="space-y-4">
<div class="flex gap-3 p-3 rounded-lg bg-orange-50 border border-orange-100">
<span class="material-symbols-outlined text-orange-500 mt-0.5" data-icon="build">build</span>
<div>
<p class="font-label-md text-label-md text-on-surface mb-1">Servis Berkala</p>
<p class="font-body-sm text-body-sm text-on-surface-variant">Mobil B 1234 ABC mendekati batas KM servis (100km lagi).</p>
</div>
</div>
<div class="flex gap-3 p-3 rounded-lg bg-red-50 border border-red-100">
<span class="material-symbols-outlined text-red-500 mt-0.5" data-icon="warning">warning</span>
<div>
<p class="font-label-md text-label-md text-on-surface mb-1">Keterlambatan Pengembalian</p>
<p class="font-body-sm text-body-sm text-on-surface-variant">TRX-0915 (D 3456 JK) terlambat 2 jam dari jadwal.</p>
</div>
</div>
<div class="flex gap-3 p-3 rounded-lg bg-surface-container-low border border-surface-variant">
<span class="material-symbols-outlined text-primary mt-0.5" data-icon="info">info</span>
<div>
<p class="font-label-md text-label-md text-on-surface mb-1">Pajak Kendaraan</p>
<p class="font-body-sm text-body-sm text-on-surface-variant">3 armada perlu perpanjangan STNK bulan depan.</p>
</div>
</div>
</div>
</div>
</div>
</main>
</body></html>