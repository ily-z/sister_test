<!DOCTYPE html>

<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Manajemen Armada - DriveOps ERP</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
                        "h3": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                        "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                        "body-sm": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "label-md": ["14px", { "lineHeight": "20px", "fontWeight": "500" }],
                        "display-xl": ["36px", { "lineHeight": "44px", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                        "h1": ["30px", { "lineHeight": "38px", "letterSpacing": "-0.01em", "fontWeight": "600" }],
                        "body-lg": ["18px", { "lineHeight": "28px", "fontWeight": "400" }],
                        "h2": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                        "label-sm": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }]
                    }
                }
            }
        }
    </script>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
        }
        .icon-fill {
            font-variation-settings: 'FILL' 1, 'wght' 300, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="bg-surface-container-low text-on-surface font-body-md antialiased min-h-screen flex">
<aside class="bg-white dark:bg-gray-900 font-inter antialiased fixed h-screen w-64 left-0 top-0 border-r border-gray-100 dark:border-gray-800 shadow-sm flex flex-col h-full py-6 z-50">
<div class="px-6 mb-8 flex flex-col">
<span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">DriveOps</span>
<span class="text-xs text-gray-500 uppercase tracking-wider font-semibold mt-1">Fleet Management</span>
</div>
<nav class="flex-1 flex flex-col gap-1">
<a class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors active:opacity-80 transition-all duration-200" href="#">
<span class="material-symbols-outlined text-[20px]">dashboard</span>
<span class="font-label-md text-label-md">Dashboard</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-indigo-600 bg-indigo-50/50 dark:bg-indigo-900/20 border-r-4 border-indigo-600 font-semibold active:opacity-80 transition-all duration-200" href="#">
<span class="material-symbols-outlined text-[20px] icon-fill">directions_car</span>
<span class="font-label-md text-label-md">Armada</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors active:opacity-80 transition-all duration-200" href="#">
<span class="material-symbols-outlined text-[20px]">history_edu</span>
<span class="font-label-md text-label-md">Penyewaan</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors active:opacity-80 transition-all duration-200" href="#">
<span class="material-symbols-outlined text-[20px]">payments</span>
<span class="font-label-md text-label-md">Keuangan</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors active:opacity-80 transition-all duration-200" href="#">
<span class="material-symbols-outlined text-[20px]">description</span>
<span class="font-label-md text-label-md">Invoice</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors active:opacity-80 transition-all duration-200" href="#">
<span class="material-symbols-outlined text-[20px]">settings</span>
<span class="font-label-md text-label-md">Pengaturan</span>
</a>
</nav>
</aside>
<div class="flex-1 ml-64 flex flex-col min-h-screen">
<header class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md text-indigo-600 dark:text-indigo-400 font-inter text-sm fixed top-0 right-0 w-[calc(100%-16rem)] h-16 border-b border-gray-100 dark:border-gray-800 z-40 flex justify-between items-center px-8">
<div class="flex items-center gap-md w-1/3">
<span class="text-lg font-semibold text-gray-900 dark:text-white mr-4">DriveOps ERP</span>
<div class="relative w-full max-w-xs flex items-center focus-within:ring-2 focus-within:ring-indigo-500/20 rounded-lg">
<span class="material-symbols-outlined absolute left-3 text-gray-400 text-[18px]">search</span>
<input class="w-full pl-9 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border-none rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-0" placeholder="Search..." type="text"/>
</div>
</div>
<div class="flex items-center gap-4">
<button class="text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors p-2 rounded-full hover:bg-gray-50 dark:hover:bg-gray-800">
<span class="material-symbols-outlined">notifications</span>
</button>
<button class="text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors p-2 rounded-full hover:bg-gray-50 dark:hover:bg-gray-800">
<span class="material-symbols-outlined">help_outline</span>
</button>
<div class="h-8 w-8 rounded-full bg-indigo-100 border border-indigo-200 overflow-hidden ml-2 cursor-pointer">
<img alt="User Profile Avatar" class="w-full h-full object-cover" data-alt="minimalist corporate avatar default profile image with light blue background" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDyOGBccfnJlhkmL1V9S-208M1K1tzNuL-U7cZZfpg2sjReYGPlSnNXxnZP13mRQU4OybsYwpkYzOGEYUGmCDDb2bSJe_JMMw8rs3EBk2qryEBpSaLimjqIaqOMfk2TbHtyaNyj-TQyBmTd9BRffgZyH82LSDWj8-tDNYDrHEr4JKHYUsNPPpzBR7RBF5peT11BSHC1BXpbY0RtdCf-tmMskNw2zlqJm7lxkbZoMp5yZGaCYMue8penMr9Hu-w0dJuuBjyfDcXb5FI"/>
</div>
</div>
</header>
<main class="flex-1 pt-16 p-margin w-full max-w-[1600px] mx-auto">
<div class="flex flex-col md:flex-row md:items-center justify-between mb-lg gap-md mt-sm">
<div>
<h1 class="font-h1 text-h1 text-on-background">Daftar Armada</h1>
<p class="font-body-md text-body-md text-on-surface-variant mt-1">Kelola dan monitor status operasional seluruh kendaraan</p>
</div>
<div class="flex items-center gap-sm">
<div class="relative border border-outline-variant bg-surface-container-lowest rounded-lg px-3 py-2 flex items-center gap-2 hover:border-outline transition-colors">
<span class="material-symbols-outlined text-[18px] text-on-surface-variant">filter_list</span>
<select class="bg-transparent border-none p-0 text-sm font-label-md text-on-surface focus:ring-0 cursor-pointer pr-6 appearance-none outline-none">
<option value="all">Status: Semua</option>
<option value="tersedia">Tersedia</option>
<option value="disewa">Disewa</option>
<option value="bengkel">Bengkel</option>
</select>
<span class="material-symbols-outlined absolute right-2 pointer-events-none text-[18px] text-on-surface-variant">expand_more</span>
</div>
<button class="bg-primary hover:bg-primary/90 text-on-primary font-label-md text-label-md px-4 py-2 rounded-lg flex items-center gap-2 shadow-sm transition-colors">
<span class="material-symbols-outlined text-[18px]">add</span>
                        Tambah Mobil
                    </button>
</div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-gutter">
<article class="bg-surface-container-lowest rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.04)] border border-surface-variant overflow-hidden flex flex-col transition-transform hover:-translate-y-1 duration-300">
<div class="relative h-48 bg-surface-variant w-full overflow-hidden">
<img class="w-full h-full object-cover" data-alt="white modern suv parked in a clean minimalist concrete garage setting with soft diffuse lighting" src="https://lh3.googleusercontent.com/aida-public/AB6AXuADV82Pkgsg17aMwxreyu8tnDWN5bU4DPJncQDSW82a6mdfKx_2fS2Z5gv-jUBtWqjt-eoaKwclVu2VPCP1ntyeDfzGgB-ihMd14SKru544fcA0MCn6KV3d8SoBXeSoeMCjGKJRWbLIllHOK7iqbQxqg79ctpkkBszJ3AhpU3e6XDkB3UE7k1Y-xfGjQa3j0JG_cGFuESZqaoYhBCO7raTLibhUi-DZdbKw_8sbL5w6K1rcm-3fhA3OHCoRQgV7CaHwFh5J3o4lbqk"/>
<div class="absolute top-3 right-3">
<span class="bg-secondary-container text-secondary font-label-sm text-label-sm px-2 py-1 rounded-full shadow-sm flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">check_circle</span>
                                Tersedia
                            </span>
</div>
</div>
<div class="p-md flex flex-col flex-1">
<div class="flex justify-between items-start mb-1">
<h2 class="font-h3 text-h3 text-on-surface uppercase tracking-wide">B 1234 XYZ</h2>
<span class="font-label-md text-label-md text-on-surface-variant bg-surface px-2 py-0.5 rounded">2023</span>
</div>
<p class="font-body-md text-body-md text-on-surface-variant mb-4">Toyota Innova Zenix Hybrid</p>
<div class="mt-auto pt-4 border-t border-outline-variant/30">
<div class="flex justify-between items-end mb-2">
<span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">KM Saat Ini</span>
<span class="font-label-md text-label-md text-on-surface">12,450 <span class="text-on-surface-variant font-normal">/ 15,000</span></span>
</div>
<div class="w-full h-1.5 bg-surface-variant rounded-full overflow-hidden">
<div class="h-full bg-secondary rounded-full" style="width: 83%"></div>
</div>
</div>
<div class="flex gap-sm mt-lg">
<button class="flex-1 bg-surface-container hover:bg-surface-variant text-on-surface font-label-md text-label-md py-2 rounded-lg transition-colors border border-outline-variant/50">Detail</button>
<button class="w-10 h-10 flex items-center justify-center bg-surface-container hover:bg-surface-variant text-on-surface-variant rounded-lg transition-colors border border-outline-variant/50 group" title="Riwayat Servis">
<span class="material-symbols-outlined text-[20px] group-hover:text-primary transition-colors">build_circle</span>
</button>
<button class="w-10 h-10 flex items-center justify-center bg-surface-container hover:bg-surface-variant text-on-surface-variant rounded-lg transition-colors border border-outline-variant/50 group" title="Update KM">
<span class="material-symbols-outlined text-[20px] group-hover:text-primary transition-colors">speed</span>
</button>
</div>
</div>
</article>
<article class="bg-surface-container-lowest rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.04)] border border-surface-variant overflow-hidden flex flex-col transition-transform hover:-translate-y-1 duration-300">
<div class="relative h-48 bg-surface-variant w-full overflow-hidden">
<img class="w-full h-full object-cover" data-alt="sleek silver sedan parked on an urban street at dusk with cool blue ambient lighting" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCvFr0TTlOE5V1GhR-cXgzF_q3yMQ0RF6k4B1NpohMcwjL2QJinxUm0iODL_UxzXsOQextcpls_MmsAiW5HKLel9glxh1F1b4l7iVCNmmsmYzoYQJIiOwTffBhq3JLQhDBNYmO0hdctO8ojAjT7QmndwvsSKFolZzujrRDU8epZGwMz4VAN-p0_1k73z5D5Sh5BfjrIWWWRTz7l-rBEpwMOoIid1C_QSTFKCR_cjHcLpqsbgS857LOd5RF783uMxCi-eyzcXqTqO-k"/>
<div class="absolute top-3 right-3">
<span class="bg-primary-container text-on-primary-container font-label-sm text-label-sm px-2 py-1 rounded-full shadow-sm flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">key</span>
                                Disewa
                            </span>
</div>
</div>
<div class="p-md flex flex-col flex-1">
<div class="flex justify-between items-start mb-1">
<h2 class="font-h3 text-h3 text-on-surface uppercase tracking-wide">D 8899 AB</h2>
<span class="font-label-md text-label-md text-on-surface-variant bg-surface px-2 py-0.5 rounded">2022</span>
</div>
<p class="font-body-md text-body-md text-on-surface-variant mb-4">Honda CR-V Turbo</p>
<div class="mt-auto pt-4 border-t border-outline-variant/30">
<div class="flex justify-between items-end mb-2">
<span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">KM Saat Ini</span>
<span class="font-label-md text-label-md text-on-surface">34,100 <span class="text-on-surface-variant font-normal">/ 40,000</span></span>
</div>
<div class="w-full h-1.5 bg-surface-variant rounded-full overflow-hidden">
<div class="h-full bg-primary rounded-full" style="width: 85%"></div>
</div>
</div>
<div class="flex gap-sm mt-lg">
<button class="flex-1 bg-surface-container hover:bg-surface-variant text-on-surface font-label-md text-label-md py-2 rounded-lg transition-colors border border-outline-variant/50">Detail</button>
<button class="w-10 h-10 flex items-center justify-center bg-surface-container hover:bg-surface-variant text-on-surface-variant rounded-lg transition-colors border border-outline-variant/50 group" title="Riwayat Servis">
<span class="material-symbols-outlined text-[20px] group-hover:text-primary transition-colors">build_circle</span>
</button>
<button class="w-10 h-10 flex items-center justify-center bg-surface-container hover:bg-surface-variant text-on-surface-variant rounded-lg transition-colors border border-outline-variant/50 group" title="Update KM">
<span class="material-symbols-outlined text-[20px] group-hover:text-primary transition-colors">speed</span>
</button>
</div>
</div>
</article>
<article class="bg-surface-container-lowest rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.04)] border border-error-container overflow-hidden flex flex-col transition-transform hover:-translate-y-1 duration-300">
<div class="relative h-48 bg-surface-variant w-full overflow-hidden">
<div class="absolute inset-0 bg-error/10 z-10 mix-blend-multiply"></div>
<img class="w-full h-full object-cover grayscale-[30%]" data-alt="car undergoing maintenance in a brightly lit professional auto repair shop bay" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDMq9IOUX-WuEzSQTJHaqUk1ejTuIHqDxj1YQ5cbr1OeznrvlvpHFlu04nz3xEawuZ6GynpLHXHf9LdflO_49ll7LYZU9Y7ZYCV799TR7RxAk7HnE3aHXgYENeh7NM2imDxxyOV0zn4xPTQ3OGtGjHekvlKjNsghz1dmRRnJCKzh1sNifXQiclqGKFIS2YU0r-whIXKM7aqPNCUuplBQVFr9RehOsFgHRVqOeGLi7b1slw_E6Oz3A3XuaQ12-4TWUnG7Huo_EIC9rs"/>
<div class="absolute top-3 right-3 z-20">
<span class="bg-error-container text-on-error-container font-label-sm text-label-sm px-2 py-1 rounded-full shadow-sm flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">engineering</span>
                                Bengkel
                            </span>
</div>
</div>
<div class="p-md flex flex-col flex-1">
<div class="flex justify-between items-start mb-1">
<h2 class="font-h3 text-h3 text-on-surface uppercase tracking-wide">L 5566 JKL</h2>
<span class="font-label-md text-label-md text-on-surface-variant bg-surface px-2 py-0.5 rounded">2020</span>
</div>
<p class="font-body-md text-body-md text-on-surface-variant mb-4">Mitsubishi Xpander Cross</p>
<div class="mt-auto pt-4 border-t border-outline-variant/30">
<div class="flex justify-between items-end mb-2">
<span class="font-label-sm text-label-sm text-error uppercase tracking-wider font-bold">Servis Melewati Batas</span>
<span class="font-label-md text-label-md text-error">51,200 <span class="text-error/70 font-normal">/ 50,000</span></span>
</div>
<div class="w-full h-1.5 bg-error-container rounded-full overflow-hidden">
<div class="h-full bg-error rounded-full" style="width: 100%"></div>
</div>
</div>
<div class="flex gap-sm mt-lg">
<button class="flex-1 bg-surface-container hover:bg-surface-variant text-on-surface font-label-md text-label-md py-2 rounded-lg transition-colors border border-outline-variant/50">Detail</button>
<button class="w-10 h-10 flex items-center justify-center bg-surface-container hover:bg-surface-variant text-on-surface-variant rounded-lg transition-colors border border-outline-variant/50 group" title="Riwayat Servis">
<span class="material-symbols-outlined text-[20px] group-hover:text-primary transition-colors">build_circle</span>
</button>
<button class="w-10 h-10 flex items-center justify-center bg-surface-container hover:bg-surface-variant text-on-surface-variant rounded-lg transition-colors border border-outline-variant/50 group" title="Update KM">
<span class="material-symbols-outlined text-[20px] group-hover:text-primary transition-colors">speed</span>
</button>
</div>
</div>
</article>
<article class="bg-surface-container-lowest rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.04)] border border-surface-variant overflow-hidden flex flex-col transition-transform hover:-translate-y-1 duration-300">
<div class="relative h-48 bg-surface-variant w-full overflow-hidden">
<img class="w-full h-full object-cover" data-alt="compact dark blue hatchback parked in a modern geometric parking structure with leading lines" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCDfoVZJXVh7ZG_19q-5hLCM_Eh7gDMvoaEN4EHY9UBvnQ0_Xt10K7zeOBORPxqsS6OfmjQaUsfqKFZgkvh5PPmC5dALx7sBXBaWM6wthjODtiubtj_bU-8t0FJ7LmQxsraxbm0tfTwxhd0P4f1I0RPoOhPaKokYWo_-F7eKQMMpm5pwFShDsEWJnxhlevSOaX3hZoDa9qlYsT1-pSut6p9Xh99m1JJElSgMv5OeewT_6b3QcFwR43Pqwg7KG8GEOt8gEAU-iVmTRo"/>
<div class="absolute top-3 right-3">
<span class="bg-secondary-container text-secondary font-label-sm text-label-sm px-2 py-1 rounded-full shadow-sm flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">check_circle</span>
                                Tersedia
                            </span>
</div>
</div>
<div class="p-md flex flex-col flex-1">
<div class="flex justify-between items-start mb-1">
<h2 class="font-h3 text-h3 text-on-surface uppercase tracking-wide">B 9012 PQR</h2>
<span class="font-label-md text-label-md text-on-surface-variant bg-surface px-2 py-0.5 rounded">2024</span>
</div>
<p class="font-body-md text-body-md text-on-surface-variant mb-4">Honda Brio RS</p>
<div class="mt-auto pt-4 border-t border-outline-variant/30">
<div class="flex justify-between items-end mb-2">
<span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">KM Saat Ini</span>
<span class="font-label-md text-label-md text-on-surface">3,200 <span class="text-on-surface-variant font-normal">/ 10,000</span></span>
</div>
<div class="w-full h-1.5 bg-surface-variant rounded-full overflow-hidden">
<div class="h-full bg-secondary rounded-full" style="width: 32%"></div>
</div>
</div>
<div class="flex gap-sm mt-lg">
<button class="flex-1 bg-surface-container hover:bg-surface-variant text-on-surface font-label-md text-label-md py-2 rounded-lg transition-colors border border-outline-variant/50">Detail</button>
<button class="w-10 h-10 flex items-center justify-center bg-surface-container hover:bg-surface-variant text-on-surface-variant rounded-lg transition-colors border border-outline-variant/50 group" title="Riwayat Servis">
<span class="material-symbols-outlined text-[20px] group-hover:text-primary transition-colors">build_circle</span>
</button>
<button class="w-10 h-10 flex items-center justify-center bg-surface-container hover:bg-surface-variant text-on-surface-variant rounded-lg transition-colors border border-outline-variant/50 group" title="Update KM">
<span class="material-symbols-outlined text-[20px] group-hover:text-primary transition-colors">speed</span>
</button>
</div>
</div>
</article>
</div>
</main>
</div>
</body></html>