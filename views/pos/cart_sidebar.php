<!-- Sidebar Keranjang Belanja (Mobile Cart View) -->
<aside id="mobile-cart-view" class="w-full lg:w-96 bg-slate-900 border-l border-white/10 hidden lg:flex flex-col h-full shadow-2xl relative z-10 cart-sidebar-panel overflow-hidden">
    <div class="p-4 sm:p-5 border-b border-white/10 flex items-center justify-between cart-header-panel shrink-0">
        <div class="flex items-center gap-2.5">
            <!-- Tombol Cepat Kembali ke Menu (Khusus Layar HP/Tablet lg:hidden) -->
            <button type="button" onclick="switchMobileTab('catalog')" class="lg:hidden px-2.5 py-1.5 -ml-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition text-xs font-bold flex items-center gap-1 cursor-pointer border border-white/10" title="Kembali ke Katalog Menu">
                <span>⬅</span> <span class="text-[11px]">Menu</span>
            </button>
            <div>
                <h2 class="text-base font-bold text-white leading-none">Keranjang Transaksi</h2>
                <span id="cart-item-count" class="text-[10px] text-slate-400 font-mono">0 Item Dipilih</span>
            </div>
        </div>
        <button onclick="kosongkanKeranjang()" class="text-[11px] font-semibold text-rose-400 hover:text-rose-300 transition cursor-pointer">
            Reset Cart
        </button>
    </div>

    <!-- List Items in Cart (Native Smooth Scrollable) -->
    <div id="cart-items-wrapper" class="flex-1 p-5 overflow-y-auto min-h-0 custom-scroll divide-y divide-white/5 select-text" style="-webkit-overflow-scrolling: touch; touch-action: pan-y; overscroll-behavior: contain;">
        <div id="empty-cart-state" class="py-20 text-center text-slate-500">
            <span class="text-4xl block mb-2 opacity-50">🛒</span>
            <p class="text-xs font-semibold text-slate-400">Keranjang masih kosong</p>
            <p class="text-[10px] text-slate-600 mt-0.5">Klik salah satu produk untuk menambahkan</p>
        </div>
    </div>

    <!-- Summary & Checkout Action -->
    <div id="cart-checkout-box" class="p-5 border-t border-white/10 bg-slate-950/60 space-y-4 cart-checkout-panel pb-8 lg:pb-5 shrink-0">
        <div class="space-y-2">
            <div class="flex justify-between text-xs text-slate-400">
                <span class="font-medium text-slate-400 label-subtotal">Subtotal</span>
                <span id="cart-subtotal" class="font-bold text-white">Rp 0</span>
            </div>
            <div class="flex justify-between items-center text-sm font-black pt-2 border-t border-white/5 border-divider-subtotal">
                <span class="text-white label-total-tagihan">Total Tagihan</span>
                <span id="cart-grand-total" class="text-emerald-400 text-lg font-black font-mono">Rp 0</span>
            </div>
        </div>

        <button id="btn-checkout" onclick="bukaModalBayar()" disabled
                class="w-full bg-blue-600 hover:bg-blue-500 disabled:bg-slate-800 disabled:text-slate-500 text-white font-bold py-4 rounded-2xl text-sm transition duration-200 shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2 cursor-pointer">
            <span>Bayar Sekarang</span>
            <span>➔</span>
        </button>
    </div>
</aside>
