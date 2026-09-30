<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Invoice Detail - RentalCore</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "surface-container-lowest": "#ffffff",
                        "secondary-fixed": "#e1e0ff",
                        "secondary-fixed-dim": "#c0c1ff",
                        "background": "#fcf8ff",
                        "surface": "#fcf8ff",
                        "inverse-primary": "#c3c0ff",
                        "error": "#ba1a1a",
                        "on-background": "#1b1b24",
                        "surface-container-high": "#eae6f4",
                        "on-secondary-fixed-variant": "#2f2ebe",
                        "on-error-container": "#93000a",
                        "outline": "#777587",
                        "surface-container-low": "#f5f2ff",
                        "on-tertiary": "#ffffff",
                        "tertiary": "#7e3000",
                        "outline-variant": "#c7c4d8",
                        "on-surface-variant": "#464555",
                        "surface-tint": "#4d44e3",
                        "surface-container": "#f0ecf9",
                        "surface-bright": "#fcf8ff",
                        "surface-dim": "#dcd8e5",
                        "on-primary-fixed-variant": "#3323cc",
                        "on-error": "#ffffff",
                        "surface-container-highest": "#e4e1ee",
                        "surface-variant": "#e4e1ee",
                        "error-container": "#ffdad6",
                        "inverse-on-surface": "#f3effc",
                        "on-tertiary-container": "#ffd2be",
                        "on-secondary-fixed": "#07006c",
                        "primary-fixed-dim": "#c3c0ff",
                        "on-primary-container": "#dad7ff",
                        "on-primary-fixed": "#0f0069",
                        "on-secondary-container": "#fffbff",
                        "primary-container": "#4f46e5",
                        "on-tertiary-fixed": "#351000",
                        "secondary-container": "#6063ee",
                        "primary": "#3525cd",
                        "secondary": "#4648d4",
                        "inverse-surface": "#302f39",
                        "tertiary-fixed": "#ffdbcc",
                        "on-surface": "#1b1b24",
                        "on-primary": "#ffffff",
                        "on-tertiary-fixed-variant": "#7b2f00",
                        "on-secondary": "#ffffff",
                        "tertiary-fixed-dim": "#ffb695",
                        "tertiary-container": "#a44100",
                        "primary-fixed": "#e2dfff"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "base": "4px",
                        "md": "16px",
                        "xl": "32px",
                        "sm": "8px",
                        "xs": "4px",
                        "margin": "32px",
                        "gutter": "24px",
                        "lg": "24px"
                    },
                    "fontFamily": {
                        "label-md": ["Inter"],
                        "body-sm": ["Inter"],
                        "body-lg": ["Inter"],
                        "h1": ["Inter"],
                        "h2": ["Inter"],
                        "body-md": ["Inter"],
                        "label-sm": ["Inter"],
                        "h3": ["Inter"],
                        "display-xl": ["Inter"]
                    },
                    "fontSize": {
                        "label-md": ["14px", { "lineHeight": "20px", "fontWeight": "500" }],
                        "body-sm": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "body-lg": ["18px", { "lineHeight": "28px", "fontWeight": "400" }],
                        "h1": ["30px", { "lineHeight": "38px", "letterSpacing": "-0.01em", "fontWeight": "600" }],
                        "h2": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                        "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                        "label-sm": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }],
                        "h3": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                        "display-xl": ["36px", { "lineHeight": "44px", "letterSpacing": "-0.02em", "fontWeight": "700" }]
                    }
                }
            }
        }
    </script>
<style>
        .shadow-ambient {
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-[#F8F9FA] text-on-surface min-h-screen flex flex-col font-body-md">
<!-- TopNavBar -->
<nav class="bg-white dark:bg-gray-900 font-inter text-sm antialiased docked full-width top-0 border-b border-gray-100 dark:border-gray-800 shadow-sm flex justify-between items-center px-8 h-16 w-full sticky top-0 z-50">
<div class="flex items-center gap-8">
<span class="text-xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400">RentalCore</span>
<div class="hidden md:flex gap-6">
<a class="text-gray-500 dark:text-gray-400 hover:text-gray-700 pb-4 mt-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer active:opacity-80" href="#">Dashboard</a>
<a class="text-gray-500 dark:text-gray-400 hover:text-gray-700 pb-4 mt-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer active:opacity-80" href="#">Fleet</a>
<a class="text-gray-500 dark:text-gray-400 hover:text-gray-700 pb-4 mt-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer active:opacity-80" href="#">Bookings</a>
<a class="text-indigo-600 dark:text-indigo-400 font-semibold border-b-2 border-indigo-600 pb-4 mt-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer active:opacity-80" href="#">Invoices</a>
<a class="text-gray-500 dark:text-gray-400 hover:text-gray-700 pb-4 mt-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer active:opacity-80" href="#">Customers</a>
<a class="text-gray-500 dark:text-gray-400 hover:text-gray-700 pb-4 mt-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer active:opacity-80" href="#">Reports</a>
</div>
</div>
<div class="flex items-center gap-6">
<div class="flex items-center gap-4">
<button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
<span class="material-symbols-outlined">notifications</span>
</button>
<button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
<span class="material-symbols-outlined">help</span>
</button>
<button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
<span class="material-symbols-outlined">settings</span>
</button>
</div>
<img alt="Administrator Profile" class="w-8 h-8 rounded-full border border-gray-200 dark:border-gray-700" data-alt="Administrator Profile avatar with initials" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDQUA3RLd7BtKA3ykCgTATv4-M5C83meTTlzUNOHL7NuXlxE9wLzzg2u5fRvSx-5Zo15RMNLg8kdS2TN-XbQdmf0ZKIo5Opw_VmkfTaapJTVpZ_8BLhF97wZy__pGxELJw0_1NcyrDqGJ3hva8eFf9T-6xhS3fJaQEoodgib0B8TaLAnl9wDPO-r3wL7LklLHth9PJNfoJ0z0fuU2RNu7uavzVmU_Wc58mR5IraLA0G0L9mBCFu5mYbWGfOBWyqSatflSJdq7rbWQY"/>
</div>
</nav>
<div class="flex flex-1">
<!-- SideNavBar -->
<aside class="hidden md:flex flex-col gap-1 p-4 w-64 bg-gray-50 dark:bg-gray-950 font-inter text-sm font-medium fixed left-0 top-16 h-[calc(100vh-64px)] border-r border-gray-200 dark:border-gray-800 flat no-shadow z-40">
<div class="px-4 py-3 mb-4">
<h2 class="text-gray-900 dark:text-white font-semibold">Billing</h2>
<p class="text-gray-500 dark:text-gray-400 text-xs mt-1">Manage financial records</p>
</div>
<a class="flex items-center gap-3 text-gray-600 dark:text-gray-400 px-4 py-2.5 hover:bg-gray-100 dark:hover:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all duration-200 ease-in-out" href="#">
<span class="material-symbols-outlined">dashboard</span>
<span>Overview</span>
</a>
<a class="flex items-center gap-3 bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 rounded-lg px-4 py-2.5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 ease-in-out" href="#">
<span class="material-symbols-outlined">receipt_long</span>
<span>All Invoices</span>
</a>
<a class="flex items-center gap-3 text-gray-600 dark:text-gray-400 px-4 py-2.5 hover:bg-gray-100 dark:hover:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all duration-200 ease-in-out" href="#">
<span class="material-symbols-outlined">pending_actions</span>
<span>Pending Payments</span>
</a>
<a class="flex items-center gap-3 text-gray-600 dark:text-gray-400 px-4 py-2.5 hover:bg-gray-100 dark:hover:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all duration-200 ease-in-out" href="#">
<span class="material-symbols-outlined">archive</span>
<span>Archive</span>
</a>
<a class="flex items-center gap-3 text-gray-600 dark:text-gray-400 px-4 py-2.5 hover:bg-gray-100 dark:hover:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all duration-200 ease-in-out" href="#">
<span class="material-symbols-outlined">settings</span>
<span>Tax Settings</span>
</a>
<div class="mt-auto px-4 py-4">
<button class="w-full bg-primary text-on-primary py-2 px-4 rounded-lg font-label-md hover:opacity-90 transition-opacity">
                    Create New Invoice
                </button>
</div>
</aside>
<!-- Main Content Canvas -->
<main class="flex-1 ml-0 md:ml-64 p-xl max-w-7xl mx-auto w-full">
<!-- Breadcrumbs -->
<div class="flex items-center gap-2 text-on-surface-variant font-body-sm mb-lg">
<a class="hover:text-primary transition-colors" href="#">Home</a>
<span class="material-symbols-outlined text-sm">chevron_right</span>
<a class="hover:text-primary transition-colors" href="#">Invoice</a>
<span class="material-symbols-outlined text-sm">chevron_right</span>
<span class="text-on-surface font-label-md">INV-2023-089</span>
</div>
<!-- Header Section -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-xl">
<div class="flex items-center gap-4">
<h1 class="font-h1 text-on-surface">INV-2023-089</h1>
<span class="bg-error-container text-on-error-container px-3 py-1 rounded-full font-label-sm uppercase tracking-wider">Unpaid</span>
</div>
<div class="flex items-center gap-3">
<button class="flex items-center gap-2 px-4 py-2 border border-outline-variant rounded-lg text-on-surface font-label-md hover:bg-surface-variant transition-colors bg-surface-container-lowest shadow-ambient">
<span class="material-symbols-outlined" style="font-size: 20px;">download</span>
                        Download PDF
                    </button>
<button class="flex items-center gap-2 px-4 py-2 border border-outline-variant rounded-lg text-on-surface font-label-md hover:bg-surface-variant transition-colors bg-surface-container-lowest shadow-ambient">
<span class="material-symbols-outlined" style="font-size: 20px;">print</span>
                        Print
                    </button>
<button class="flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-lg font-label-md hover:opacity-90 transition-opacity shadow-ambient">
<span class="material-symbols-outlined" style="font-size: 20px;">share</span>
                        Share
                    </button>
</div>
</div>
<!-- Two Column Layout -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-xl">
<!-- Left Column: Invoice Preview (Paper Style) -->
<div class="lg:col-span-2">
<div class="bg-surface-container-lowest rounded-xl shadow-ambient p-xl border border-outline-variant/30 min-h-[800px] relative">
<!-- Invoice Header -->
<div class="flex justify-between items-start mb-xl border-b border-outline-variant/30 pb-xl">
<div>
<h2 class="font-h2 text-primary mb-2">Lumina Velocity</h2>
<p class="font-body-sm text-on-surface-variant">123 Corporate Blvd, Suite 400<br/>Metropolis, NY 10001<br/>billing@luminavelocity.com</p>
</div>
<div class="text-right">
<h1 class="font-display-xl text-on-surface/20 uppercase tracking-widest mb-2">Invoice</h1>
<p class="font-label-md text-on-surface">Date: Oct 24, 2023</p>
<p class="font-label-md text-on-surface">Due: Nov 07, 2023</p>
</div>
</div>
<!-- Billing Details -->
<div class="grid grid-cols-2 gap-xl mb-xl">
<div>
<h3 class="font-label-sm text-on-surface-variant uppercase mb-2">Billed To:</h3>
<p class="font-label-md text-on-surface">Acme Corp Logistics</p>
<p class="font-body-sm text-on-surface-variant">Attn: Sarah Jenkins<br/>456 Industrial Way<br/>Gotham, NJ 07001</p>
</div>
<div class="text-right flex justify-end">
<!-- Simple QR Code Placeholder -->
<div class="w-24 h-24 bg-surface-variant rounded border border-outline-variant/30 p-2 flex items-center justify-center">
<span class="material-symbols-outlined text-4xl text-on-surface-variant">qr_code_2</span>
</div>
</div>
</div>
<!-- Invoice Items Table -->
<div class="mb-xl">
<table class="w-full text-left border-collapse">
<thead>
<tr class="border-b-2 border-outline-variant/50">
<th class="py-3 font-label-sm text-on-surface-variant uppercase w-1/2">Description</th>
<th class="py-3 font-label-sm text-on-surface-variant uppercase text-right">Qty</th>
<th class="py-3 font-label-sm text-on-surface-variant uppercase text-right">Rate</th>
<th class="py-3 font-label-sm text-on-surface-variant uppercase text-right">Amount</th>
</tr>
</thead>
<tbody class="font-body-md text-on-surface">
<tr class="border-b border-outline-variant/20 hover:bg-surface-variant/30 transition-colors">
<td class="py-4">
<div class="font-label-md">Rental: Toyota Avanza (5 Days)</div>
<div class="font-body-sm text-on-surface-variant">Oct 10 - Oct 15</div>
</td>
<td class="py-4 text-right">5</td>
<td class="py-4 text-right">$45.00</td>
<td class="py-4 text-right font-label-md">$225.00</td>
</tr>
<tr class="border-b border-outline-variant/20 hover:bg-surface-variant/30 transition-colors">
<td class="py-4">
<div class="font-label-md">Add-on: Professional Driver</div>
<div class="font-body-sm text-on-surface-variant">Standard Rate</div>
</td>
<td class="py-4 text-right">5</td>
<td class="py-4 text-right">$20.00</td>
<td class="py-4 text-right font-label-md">$100.00</td>
</tr>
<tr class="border-b border-outline-variant/20 hover:bg-surface-variant/30 transition-colors">
<td class="py-4">
<div class="font-label-md">Add-on: Fuel Surcharge</div>
<div class="font-body-sm text-on-surface-variant">Fixed Fee</div>
</td>
<td class="py-4 text-right">1</td>
<td class="py-4 text-right">$35.00</td>
<td class="py-4 text-right font-label-md">$35.00</td>
</tr>
</tbody>
</table>
</div>
<!-- Totals -->
<div class="flex justify-end">
<div class="w-64">
<div class="flex justify-between py-2 font-body-md text-on-surface-variant">
<span>Subtotal</span>
<span>$360.00</span>
</div>
<div class="flex justify-between py-2 font-body-md text-on-surface-variant">
<span>Tax (10%)</span>
<span>$36.00</span>
</div>
<div class="flex justify-between py-4 border-t-2 border-outline-variant/50 mt-2 font-h3 text-on-surface">
<span>Grand Total</span>
<span>$396.00</span>
</div>
</div>
</div>
<!-- Footer Note -->
<div class="mt-xl pt-lg border-t border-outline-variant/30 text-center">
<p class="font-body-sm text-on-surface-variant italic">Thank you for your business. Payment is due within 14 days.</p>
</div>
</div>
</div>
<!-- Right Column: Metadata Sidebar -->
<div class="lg:col-span-1 flex flex-col gap-lg">
<!-- Quick Actions Card -->
<div class="bg-surface-container-lowest rounded-xl shadow-ambient p-md border border-outline-variant/30">
<h3 class="font-h3 text-on-surface mb-md">Quick Actions</h3>
<div class="flex flex-col gap-3">
<button class="w-full py-2 bg-primary-container text-on-primary-container rounded-lg font-label-md hover:bg-primary hover:text-on-primary transition-colors flex items-center justify-center gap-2">
<span class="material-symbols-outlined" style="font-size: 20px;">check_circle</span>
                                Mark as Paid
                            </button>
<button class="w-full py-2 border border-outline-variant text-on-surface rounded-lg font-label-md hover:bg-surface-variant transition-colors flex items-center justify-center gap-2">
<span class="material-symbols-outlined" style="font-size: 20px;">send</span>
                                Send Reminder
                            </button>
<button class="w-full py-2 border border-error/50 text-error rounded-lg font-label-md hover:bg-error-container hover:border-error transition-colors flex items-center justify-center gap-2 mt-2">
<span class="material-symbols-outlined" style="font-size: 20px;">cancel</span>
                                Cancel Invoice
                            </button>
</div>
</div>
<!-- Payment History & Timeline -->
<div class="bg-surface-container-lowest rounded-xl shadow-ambient p-md border border-outline-variant/30">
<h3 class="font-h3 text-on-surface mb-md">Timeline</h3>
<div class="relative pl-6 border-l border-outline-variant/50 flex flex-col gap-6 py-2">
<!-- Timeline Item: Created -->
<div class="relative">
<div class="absolute -left-[31px] w-3 h-3 bg-primary rounded-full ring-4 ring-surface-container-lowest"></div>
<p class="font-label-md text-on-surface">Invoice Created</p>
<p class="font-body-sm text-on-surface-variant">Oct 24, 2023 at 10:42 AM</p>
</div>
<!-- Timeline Item: Sent -->
<div class="relative">
<div class="absolute -left-[31px] w-3 h-3 bg-surface-variant border border-outline-variant rounded-full ring-4 ring-surface-container-lowest"></div>
<p class="font-label-md text-on-surface">Sent to Customer</p>
<p class="font-body-sm text-on-surface-variant">Oct 24, 2023 at 10:45 AM</p>
</div>
<!-- Timeline Item: Viewed (Optional/Ghost state) -->
<div class="relative opacity-60">
<div class="absolute -left-[31px] w-3 h-3 bg-surface-container-lowest border-2 border-outline-variant/50 rounded-full ring-4 ring-surface-container-lowest"></div>
<p class="font-label-md text-on-surface-variant">Viewed</p>
<p class="font-body-sm text-on-surface-variant">Pending</p>
</div>
</div>
</div>
<!-- Client Info Card -->
<div class="bg-surface-container-lowest rounded-xl shadow-ambient p-md border border-outline-variant/30">
<h3 class="font-h3 text-on-surface mb-md">Client Details</h3>
<div class="flex items-center gap-3 mb-4">
<div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container font-h3">
                                 A
                             </div>
<div>
<p class="font-label-md text-on-surface">Acme Corp Logistics</p>
<p class="font-body-sm text-on-surface-variant">Corporate Account</p>
</div>
</div>
<div class="space-y-2 font-body-sm text-on-surface">
<div class="flex items-center gap-2 text-on-surface-variant">
<span class="material-symbols-outlined text-sm">mail</span>
<a class="hover:text-primary transition-colors" href="mailto:billing@acmecorp.com">billing@acmecorp.com</a>
</div>
<div class="flex items-center gap-2 text-on-surface-variant">
<span class="material-symbols-outlined text-sm">phone</span>
<span>(555) 123-4567</span>
</div>
</div>
</div>
</div>
</div>
</main>
</div>
</body></html>