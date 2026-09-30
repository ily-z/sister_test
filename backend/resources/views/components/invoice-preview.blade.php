<!-- Overlay Transparan (Fungsinya hanya agar kalau user klik di luar panel, panelnya tertutup, tanpa efek blur) -->
<div id="invoiceOverlay" onclick="closePreview()" class="fixed inset-0 z-[60] hidden"></div>

<!-- Invoice Preview Panel (Melayang di kanan layar) -->
<div id="invoicePreviewPanel" class="fixed top-0 right-0 h-screen w-full md:w-[460px] bg-slate-50 z-[70] shadow-[-15px_0_30px_rgba(0,0,0,0.05)] border-l border-slate-200 flex flex-col transition-transform duration-300 ease-in-out translate-x-full">
    
    <div class="p-6 h-full flex flex-col gap-6 overflow-y-auto">
        <!-- Header Action -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <!-- Tombol Close (X) -->
                <button onclick="closePreview()" class="p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-800 rounded-full transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined">close</span>
                </button>
                <h3 class="text-lg font-bold text-slate-900">Live Preview</h3>
            </div>
            <div class="flex gap-2">
                <button class="p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-800 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">print</span>
                </button>
                <button class="p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-800 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">share</span>
                </button>
            </div>
        </div>

        <!-- Invoice Document View -->
        <div class="flex-1 bg-white rounded-xl shadow-sm border border-slate-200 p-8 flex flex-col">
            <div class="flex flex-wrap justify-between items-start gap-4 mb-10">
                <div>
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white mb-4 shadow-sm">
                        <span class="material-symbols-outlined text-2xl">local_shipping</span>
                    </div>
                    <h4 class="text-xl font-extrabold tracking-tighter text-slate-900">ELITE DRIVE</h4>
                    <p class="text-[10px] text-slate-500 leading-relaxed uppercase font-bold tracking-widest mt-1">Fleet Operations</p>
                </div>
                <div class="text-right">
                    <h5 class="text-2xl font-bold text-slate-900">INVOICE</h5>
                    <p class="text-sm font-semibold text-blue-600 mt-1">#ORD-24-0891</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-10">
                <div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-2">Billed To</p>
                    <p class="text-sm font-bold text-slate-900">PT. Adhi Karya</p>
                    <p class="text-xs text-slate-500 leading-relaxed">Gedung MTH,<br />Jakarta Timur, 13330</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-2">Invoice Details</p>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-500">Issue Date:</span>
                        <span class="font-bold text-slate-900">Oct 12, 2024</span>
                    </div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-500">Due Date:</span>
                        <span class="font-bold text-slate-900">Nov 12, 2024</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-b border-slate-100 py-4 mb-10 flex-grow">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-slate-500 font-bold border-b border-slate-100">
                            <th class="text-left pb-3">Service Description</th>
                            <th class="text-right pb-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-800 font-medium space-y-2">
                        <tr>
                            <td class="py-2 pt-4">30-Day Contract: Toyota Avanza</td>
                            <td class="text-right py-2 pt-4">Rp 7.500.000</td>
                        </tr>
                        <tr>
                            <td class="py-2">Corporate Driver Add-on</td>
                            <td class="text-right py-2">Rp 4.000.000</td>
                        </tr>
                        <tr>
                            <td class="py-2">Toll & Fuel Deposit</td>
                            <td class="text-right py-2">Rp 1.500.000</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-auto pt-6 flex flex-wrap justify-between items-end gap-4">
                <div class="flex flex-col items-center gap-2">
                    <div class="w-24 h-24 bg-white p-1 border border-slate-200 rounded-lg shadow-sm">
                        <img alt="QR Code" class="w-full h-full object-contain mix-blend-multiply opacity-80"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuAJXrueZRBcCKxrIYlM2l_BfhfFbkbzXR82QpyF1vFrah61CuS836WWkyFIjRlDAbKywaCRAVlyNn14rebAeG2y_OyPcoi3W3My8wjoy8LvtVsEkDIh7_xOhuSj9J_Dy3O1j2mwkZOn_-E40EVle8OL_HBN-9VWTXRjbfTs1zYyRKb7nPS7Z9WH1oxterLOJw1yoPn5c-c8cnwGg83bcqYYkCQPTNDZA7-g1jHGWpKpqtpV0-mzGd6sSQM7luyNifbE2l2nM2VZHiLY" />
                    </div>
                    <span class="text-[9px] font-bold text-slate-500 tracking-wider uppercase">Scan to Pay</span>
                </div>
                <div class="text-right w-full sm:w-48">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-500">Subtotal:</span>
                        <span class="font-bold text-slate-900">Rp 13.000.000</span>
                    </div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-500">Tax (11%):</span>
                        <span class="font-bold text-slate-900">Rp 1.430.000</span>
                    </div>
                    <div class="flex justify-between text-base font-extrabold text-blue-600 mt-2 border-t border-slate-200 pt-2">
                        <span>TOTAL</span>
                        <span>Rp 14.430.000</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Summary Metric -->
        <div class="bg-blue-50 rounded-xl p-5 border border-blue-100 flex items-center gap-4 mt-auto">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                <span class="material-symbols-outlined">account_balance_wallet</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-blue-800/60 uppercase tracking-wide">Status</p>
                <p class="text-xl font-bold text-blue-700">LUNAS (Paid)</p>
            </div>
        </div>
    </div>
</div>