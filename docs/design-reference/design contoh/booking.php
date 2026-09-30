<!DOCTYPE html>

<html lang="id"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Buat Pesanan - DriveFlow ERP</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "tertiary-container": "#a44100",
                        "surface-container-lowest": "#ffffff",
                        "on-surface": "#1b1b24",
                        "tertiary-fixed-dim": "#ffb695",
                        "surface": "#fcf8ff",
                        "error": "#ba1a1a",
                        "tertiary": "#7e3000",
                        "tertiary-fixed": "#ffdbcc",
                        "outline": "#777587",
                        "on-primary-fixed": "#0f0069",
                        "on-tertiary-fixed": "#351000",
                        "outline-variant": "#c7c4d8",
                        "surface-variant": "#e4e1ee",
                        "surface-container": "#f0ecf9",
                        "surface-tint": "#4d44e3",
                        "on-secondary-fixed-variant": "#2f2ebe",
                        "secondary-container": "#6063ee",
                        "primary-container": "#4f46e5",
                        "inverse-surface": "#302f39",
                        "on-secondary-fixed": "#07006c",
                        "on-tertiary-fixed-variant": "#7b2f00",
                        "surface-dim": "#dcd8e5",
                        "primary-fixed-dim": "#c3c0ff",
                        "primary-fixed": "#e2dfff",
                        "on-background": "#1b1b24",
                        "secondary-fixed": "#e1e0ff",
                        "secondary": "#4648d4",
                        "inverse-on-surface": "#f3effc",
                        "secondary-fixed-dim": "#c0c1ff",
                        "on-tertiary": "#ffffff",
                        "on-error-container": "#93000a",
                        "on-surface-variant": "#464555",
                        "on-error": "#ffffff",
                        "on-primary-container": "#dad7ff",
                        "primary": "#3525cd",
                        "on-secondary": "#ffffff",
                        "on-primary-fixed-variant": "#3323cc",
                        "surface-bright": "#fcf8ff",
                        "on-primary": "#ffffff",
                        "inverse-primary": "#c3c0ff",
                        "surface-container-highest": "#e4e1ee",
                        "on-tertiary-container": "#ffd2be",
                        "background": "#fcf8ff",
                        "on-secondary-container": "#fffbff",
                        "surface-container-high": "#eae6f4",
                        "error-container": "#ffdad6",
                        "surface-container-low": "#f5f2ff"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "sm": "8px",
                        "margin": "32px",
                        "xs": "4px",
                        "xl": "32px",
                        "md": "16px",
                        "lg": "24px",
                        "gutter": "24px",
                        "base": "4px"
                    },
                    "fontFamily": {
                        "label-md": ["Inter"],
                        "body-lg": ["Inter"],
                        "body-md": ["Inter"],
                        "display-xl": ["Inter"],
                        "body-sm": ["Inter"],
                        "h1": ["Inter"],
                        "h2": ["Inter"],
                        "label-sm": ["Inter"],
                        "h3": ["Inter"]
                    },
                    "fontSize": {
                        "label-md": ["14px", { "lineHeight": "20px", "fontWeight": "500" }],
                        "body-lg": ["18px", { "lineHeight": "28px", "fontWeight": "400" }],
                        "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                        "display-xl": ["36px", { "lineHeight": "44px", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                        "body-sm": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "h1": ["30px", { "lineHeight": "38px", "letterSpacing": "-0.01em", "fontWeight": "600" }],
                        "h2": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                        "label-sm": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }],
                        "h3": ["20px", { "lineHeight": "28px", "fontWeight": "600" }]
                    }
                }
            }
        }
    </script>
<style>
        .ambient-shadow {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
        }
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
    </style>
</head>
<body class="bg-[#F8F9FA] text-on-surface font-body-md min-h-screen flex">
<!-- SideNavBar -->
<nav class="hidden lg:flex flex-col h-screen p-4 gap-2 fixed left-0 top-0 w-64 border-r bg-gray-50 dark:bg-gray-950 border-r border-gray-200 dark:border-gray-800 z-40">
<div class="px-4 py-6 mb-4">
<h1 class="text-xl font-black text-indigo-600 dark:text-indigo-400 font-h2 text-h2 tracking-tight">DriveFlow</h1>
<p class="text-xs text-gray-500 font-label-sm text-label-sm mt-1 uppercase">Backoffice Admin</p>
</div>
<button class="w-full bg-primary-container text-on-primary-container font-label-md text-label-md py-3 px-4 rounded-lg flex items-center justify-center gap-2 mb-6 hover:opacity-90 transition-opacity">
<span class="material-symbols-outlined" data-icon="add">add</span>
            New Booking
        </button>
<div class="flex flex-col gap-1 flex-1">
<a class="flex items-center gap-3 px-4 py-3 rounded-lg font-label-md text-label-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 active:scale-95" href="#">
<span class="material-symbols-outlined text-[20px]" data-icon="dashboard">dashboard</span>
                Dashboard
            </a>
<a class="flex items-center gap-3 px-4 py-3 rounded-lg font-label-md text-label-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 active:scale-95" href="#">
<span class="material-symbols-outlined text-[20px]" data-icon="directions_car">directions_car</span>
                Fleet
            </a>
<a class="flex items-center gap-3 px-4 py-3 rounded-lg font-label-md text-label-md bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 transition-all duration-200 active:scale-95" href="#">
<span class="material-symbols-outlined text-[20px]" data-icon="calendar_today">calendar_today</span>
                Bookings
            </a>
<a class="flex items-center gap-3 px-4 py-3 rounded-lg font-label-md text-label-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 active:scale-95" href="#">
<span class="material-symbols-outlined text-[20px]" data-icon="group">group</span>
                Customers
            </a>
<a class="flex items-center gap-3 px-4 py-3 rounded-lg font-label-md text-label-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 active:scale-95" href="#">
<span class="material-symbols-outlined text-[20px]" data-icon="analytics">analytics</span>
                Reports
            </a>
<a class="flex items-center gap-3 px-4 py-3 rounded-lg font-label-md text-label-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 active:scale-95 mt-auto" href="#">
<span class="material-symbols-outlined text-[20px]" data-icon="settings">settings</span>
                Settings
            </a>
</div>
</nav>
<!-- Main Content Area -->
<div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
<!-- TopNavBar -->
<header class="sticky top-0 z-50 flex items-center justify-between px-8 h-16 w-full bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none">
<div class="flex items-center gap-4">
<div class="relative hidden md:block">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" data-icon="search">search</span>
<input class="pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container w-64 input-glow transition-all" placeholder="Search bookings..." type="text"/>
</div>
</div>
<div class="flex items-center gap-2">
<button class="p-2 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-full transition-colors cursor-pointer active:opacity-80 flex items-center justify-center">
<span class="material-symbols-outlined" data-icon="notifications">notifications</span>
</button>
<button class="p-2 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-full transition-colors cursor-pointer active:opacity-80 flex items-center justify-center">
<span class="material-symbols-outlined" data-icon="help">help</span>
</button>
<button class="p-2 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-full transition-colors cursor-pointer active:opacity-80 flex items-center justify-center">
<span class="material-symbols-outlined" data-icon="settings">settings</span>
</button>
<div class="ml-4 h-8 w-8 rounded-full bg-surface-container-high overflow-hidden border border-outline-variant cursor-pointer">
<img alt="User profile" class="h-full w-full object-cover" data-alt="close up professional headshot of man with subtle smile on neutral background" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCtDUdHzekTd9Rpj0aMHWJmUngxkkZxR-gj37RugMkDzlOUtZdOnDy_5hG5b5sKKsfsI5-FjhIA7Zyym4lH1mQ1Ild-RigdGg9gHzZZ93vXP7NQRpZRo4uuLr5HkAFbY_n2gEX50zNQvJ4LkZMmZHKaOhqH_S_BZFy0QFymRRSO67FwHjEk3ueTE0UbsiQe1qY8Zbu91xdNA_eUTCG_bEKNUJ9LovnxOMgE5332WI46mLWPrEy1RMMF5aELs161gS1YYcPIRjyVnNA"/>
</div>
</div>
</header>
<!-- Page Content -->
<main class="flex-1 p-margin lg:p-xl max-w-7xl mx-auto w-full">
<div class="mb-lg">
<div class="flex items-center gap-2 text-gray-500 font-label-md text-label-md mb-2">
<a class="hover:text-primary-container transition-colors" href="#">Bookings</a>
<span class="material-symbols-outlined text-sm">chevron_right</span>
<span class="text-on-surface">Buat Pesanan</span>
</div>
<h2 class="font-h1 text-h1 text-on-surface">Buat Pesanan Baru</h2>
<p class="font-body-md text-body-md text-outline mt-1">Masukkan detail pelanggan dan informasi kendaraan untuk membuat reservasi.</p>
</div>
<div class="grid grid-cols-1 xl:grid-cols-12 gap-gutter items-start">
<!-- Left Column (Form) -->
<div class="xl:col-span-8 flex flex-col gap-lg">
<!-- Section 1: Data Pelanggan -->
<section class="bg-surface-container-lowest rounded-xl p-lg ambient-shadow border border-surface-container-highest/50">
<div class="flex items-center gap-2 mb-md pb-sm border-b border-surface-container">
<span class="material-symbols-outlined text-primary-container" data-icon="person">person</span>
<h3 class="font-h3 text-h3 text-on-surface">Data Pelanggan</h3>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-md">
<div class="col-span-1 md:col-span-2">
<label class="block font-label-md text-label-md text-on-surface-variant mb-sm">Nama Lengkap</label>
<input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-4 py-2.5 font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary-container input-glow transition-all" placeholder="Masukkan nama pelanggan" type="text"/>
</div>
<div>
<label class="block font-label-md text-label-md text-on-surface-variant mb-sm">No. Telepon</label>
<input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-4 py-2.5 font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary-container input-glow transition-all" placeholder="Contoh: 081234567890" type="tel"/>
</div>
<div>
<label class="block font-label-md text-label-md text-on-surface-variant mb-sm">Email (Opsional)</label>
<input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-4 py-2.5 font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary-container input-glow transition-all" placeholder="email@domain.com" type="email"/>
</div>
<div class="col-span-1 md:col-span-2">
<label class="block font-label-md text-label-md text-on-surface-variant mb-sm">Alamat</label>
<textarea class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-4 py-2.5 font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary-container input-glow transition-all resize-none" placeholder="Alamat lengkap pengiriman/penjemputan" rows="2"></textarea>
</div>
</div>
</section>
<!-- Section 2: Data Sewa -->
<section class="bg-surface-container-lowest rounded-xl p-lg ambient-shadow border border-surface-container-highest/50">
<div class="flex items-center gap-2 mb-md pb-sm border-b border-surface-container">
<span class="material-symbols-outlined text-primary-container" data-icon="car_rental">car_rental</span>
<h3 class="font-h3 text-h3 text-on-surface">Data Sewa</h3>
</div>
<div class="mb-md">
<label class="block font-label-md text-label-md text-on-surface-variant mb-sm">Jenis Sewa</label>
<div class="flex p-1 bg-surface-container-high rounded-lg inline-flex">
<button class="px-6 py-2 rounded-md bg-surface-container-lowest shadow-sm font-label-md text-label-md text-on-surface transition-all">Harian</button>
<button class="px-6 py-2 rounded-md font-label-md text-label-md text-on-surface-variant hover:text-on-surface transition-all">Kontrak</button>
</div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-md mb-md">
<div>
<label class="block font-label-md text-label-md text-on-surface-variant mb-sm">Tanggal Mulai</label>
<div class="relative">
<input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg pl-10 pr-4 py-2.5 font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary-container input-glow transition-all" placeholder="Pilih tanggal" type="text"/>
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant pointer-events-none" data-icon="calendar_month">calendar_month</span>
</div>
</div>
<div>
<label class="block font-label-md text-label-md text-on-surface-variant mb-sm">Tanggal Selesai</label>
<div class="relative">
<input class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg pl-10 pr-4 py-2.5 font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary-container input-glow transition-all" placeholder="Pilih tanggal" type="text"/>
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant pointer-events-none" data-icon="calendar_month">calendar_month</span>
</div>
</div>
</div>
<div>
<label class="block font-label-md text-label-md text-on-surface-variant mb-sm">Pilih Mobil</label>
<div class="relative">
<select class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-4 py-2.5 font-body-md text-body-md text-on-surface focus:outline-none focus:border-primary-container input-glow transition-all appearance-none cursor-pointer">
<option disabled="" selected="" value="">Pilih kendaraan yang tersedia...</option>
<option value="1">Toyota Avanza (B 1234 XYZ) - Rp 350.000/hari</option>
<option value="2">Honda CR-V (B 5678 ABC) - Rp 750.000/hari</option>
<option value="3">Mitsubishi Xpander (D 9012 DEF) - Rp 400.000/hari</option>
<option value="4">Toyota Innova Reborn (B 3456 GHI) - Rp 650.000/hari</option>
</select>
<span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline-variant pointer-events-none" data-icon="expand_more">expand_more</span>
</div>
</div>
</section>
<!-- Section 3: Biaya Tambahan -->
<section class="bg-surface-container-lowest rounded-xl p-lg ambient-shadow border border-surface-container-highest/50">
<div class="flex items-center justify-between mb-md pb-sm border-b border-surface-container">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary-container" data-icon="payments">payments</span>
<h3 class="font-h3 text-h3 text-on-surface">Biaya Tambahan (Add-ons)</h3>
</div>
<button class="flex items-center gap-1 font-label-md text-label-md text-primary-container hover:text-primary transition-colors">
<span class="material-symbols-outlined text-[18px]" data-icon="add_circle">add_circle</span>
                                Tambah Biaya
                            </button>
</div>
<div class="space-y-3">
<div class="flex items-center justify-between p-3 border border-surface-container-highest rounded-lg bg-[#F8F9FA]/50 group hover:bg-white transition-colors">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-on-surface">Supir (Driver)</span>
<span class="font-label-sm text-label-sm text-outline-variant font-normal">Per hari</span>
</div>
<div class="flex items-center gap-4">
<span class="font-body-md text-body-md text-on-surface">Rp 200.000</span>
<button class="text-outline-variant hover:text-error transition-colors p-1 rounded-md">
<span class="material-symbols-outlined text-[20px]" data-icon="delete">delete</span>
</button>
</div>
</div>
<div class="flex items-center justify-between p-3 border border-surface-container-highest rounded-lg bg-[#F8F9FA]/50 group hover:bg-white transition-colors">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-on-surface">Antar Jemput</span>
<span class="font-label-sm text-label-sm text-outline-variant font-normal">Satu kali bayar</span>
</div>
<div class="flex items-center gap-4">
<span class="font-body-md text-body-md text-on-surface">Rp 150.000</span>
<button class="text-outline-variant hover:text-error transition-colors p-1 rounded-md">
<span class="material-symbols-outlined text-[20px]" data-icon="delete">delete</span>
</button>
</div>
</div>
</div>
</section>
</div>
<!-- Right Column (Summary) -->
<div class="xl:col-span-4">
<div class="sticky top-24 bg-surface-container-lowest rounded-xl p-lg ambient-shadow border border-surface-container-highest/50">
<h3 class="font-h3 text-h3 text-on-surface mb-md pb-sm border-b border-surface-container">Ringkasan Pesanan</h3>
<div class="space-y-4 mb-6">
<div class="flex justify-between items-start">
<div class="flex flex-col">
<span class="font-label-sm text-label-sm text-outline uppercase">Kendaraan</span>
<span class="font-label-md text-label-md text-on-surface">Toyota Avanza</span>
<span class="font-body-sm text-body-sm text-outline-variant">B 1234 XYZ</span>
</div>
</div>
<div class="flex justify-between items-start">
<div class="flex flex-col">
<span class="font-label-sm text-label-sm text-outline uppercase">Durasi Sewa</span>
<span class="font-label-md text-label-md text-on-surface">3 Hari</span>
<span class="font-body-sm text-body-sm text-outline-variant">12 Okt - 15 Okt 2023</span>
</div>
</div>
</div>
<div class="border-t border-surface-container-highest pt-4 space-y-3 mb-6">
<div class="flex justify-between items-center font-body-sm text-body-sm text-on-surface-variant">
<span>Harga Dasar (3 x Rp 350.000)</span>
<span>Rp 1.050.000</span>
</div>
<div class="flex justify-between items-center font-body-sm text-body-sm text-on-surface-variant">
<span>Supir (3 x Rp 200.000)</span>
<span>Rp 600.000</span>
</div>
<div class="flex justify-between items-center font-body-sm text-body-sm text-on-surface-variant">
<span>Antar Jemput</span>
<span>Rp 150.000</span>
</div>
</div>
<div class="border-t border-surface-container-highest pt-4 mb-6">
<div class="flex justify-between items-end">
<span class="font-label-md text-label-md text-on-surface-variant">TOTAL</span>
<span class="font-h2 text-h2 text-primary-container">Rp 1.800.000</span>
</div>
</div>
<button class="w-full bg-primary-container hover:bg-primary text-on-primary-container font-label-md text-label-md py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition-colors shadow-sm">
<span class="material-symbols-outlined text-[20px]" data-icon="save">save</span>
                            Simpan Pesanan
                        </button>
</div>
</div>
</div>
</main>
</div>
</body></html>