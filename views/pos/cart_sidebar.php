<!-- Sidebar Keranjang Belanja -->
<aside class="w-96 bg-slate-900 border-l border-white/10 flex flex-col h-full shadow-2xl relative z-10 cart-sidebar-panel">
    <div class="p-5 border-b border-white/10 flex items-center justify-between cart-header-panel">
        <div>
            <h2 class="text-base font-bold text-white leading-none">Keranjang Transaksi</h2>
            <span id="cart-item-count" class="text-[10px] text-slate-400 font-mono">0 Item Dipilih</span>
        </div>
        <button onclick="kosongkanKeranjang()" class="text-[11px] font-semibold text-rose-400 hover:text-rose-300 transition">
            Reset Cart
        </button>
    </div>

    <!-- List Items in Cart -->
    <div id="cart-items-wrapper" class="flex-1 p-5 overflow-y-auto min-h-0 custom-scroll divide-y divide-white/5">
        <div id="empty-cart-state" class="py-20 text-center text-slate-500">
            <span class="text-4xl block mb-2 opacity-50">🛒</span>
            <p class="text-xs font-semibold text-slate-400">Keranjang masih kosong</p>
            <p class="text-[10px] text-slate-600 mt-0.5">Klik salah satu produk untuk menambahkan</p>
        </div>
    </div>

    <!-- Summary & Checkout Action -->
    <div id="cart-checkout-box" class="p-5 border-t border-white/10 bg-slate-950/60 space-y-4 cart-checkout-panel">
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
                class="w-full bg-blue-600 hover:bg-blue-500 disabled:bg-slate-800 disabled:text-slate-500 text-white font-bold py-4 rounded-2xl text-sm transition duration-200 shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2">
            <span>Bayar Sekarang</span>
            <span>➔</span>
        </button>
    </div>
</aside>
