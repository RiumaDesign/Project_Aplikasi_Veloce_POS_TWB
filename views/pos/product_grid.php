<!-- Grid Katalog Produk Kasir -->
<div class="flex-1 p-6 overflow-y-auto min-h-0 h-full custom-scroll pb-28">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Katalog Produk</h2>
            <p class="text-xs text-slate-400">Pilih item untuk ditambahkan ke keranjang belanja</p>
        </div>
        <div class="relative w-64">
            <input type="text" id="cari-produk" onkeyup="filterProduk(this.value)" placeholder="Cari nama produk..." class="w-full bg-slate-900 border border-white/10 rounded-2xl px-4 py-2.5 text-xs text-white placeholder-slate-500 outline-none focus:border-blue-500 transition">
            <span class="absolute right-3.5 top-2.5 text-slate-500 text-xs">🔍</span>
        </div>
    </div>

    <!-- Product Grid -->
    <div id="product-list-container" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php if ($produk_res && $produk_res->num_rows > 0): 
            while($p = $produk_res->fetch_assoc()):
                $stok_tersedia = intval($p['stok_lokasi'] ?? 0);
                $is_habis = ($stok_tersedia <= 0);
                $img_src = !empty($p['gambar']) && file_exists('uploads/' . $p['gambar']) ? 'uploads/' . $p['gambar'] : 'https://placehold.co/200x200?text=No+Image';
        ?>
        <div class="item-produk glass-card-dark rounded-3xl p-4 border border-white/5 flex flex-col justify-between hover:border-blue-500/40 transition duration-200 group relative <?= $is_habis ? 'opacity-50' : '' ?>"
             data-nama="<?= strtolower(htmlspecialchars($p['nama'])) ?>">
            
            <div>
                <div class="product-img-wrapper relative w-full aspect-square rounded-2xl overflow-hidden mb-3 bg-slate-900 border border-white/5">
                    <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($p['nama']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <?php if ($is_habis): ?>
                        <div class="product-empty-overlay absolute inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center">
                            <span class="badge-stok-habis bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[10px] font-black uppercase px-2.5 py-1 rounded-full tracking-wider">Stok Habis</span>
                        </div>
                    <?php else: ?>
                        <span class="badge-sisa-stok absolute top-2 right-2 bg-slate-950/80 backdrop-blur-sm border border-white/10 text-emerald-400 text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-sm">
                            Sisa: <?= $stok_tersedia ?>
                        </span>
                    <?php endif; ?>
                </div>

                <h3 class="text-sm font-bold text-white leading-snug line-clamp-1"><?= htmlspecialchars($p['nama']) ?></h3>
                <p class="text-xs font-black text-blue-400 mt-1">Rp <?= number_format($p['harga'], 0, ',', '.') ?></p>
            </div>

            <div class="mt-4">
                <?php if (!$is_habis): ?>
                    <button onclick="tambahKeKeranjang(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama'], ENT_QUOTES) ?>', <?= $p['harga'] ?>, <?= $stok_tersedia ?>)" 
                            class="w-full bg-blue-600/20 hover:bg-blue-600 text-blue-400 hover:text-white border border-blue-500/30 hover:border-blue-600 font-bold py-2.5 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
                        <span>＋</span> <span>Beli</span>
                    </button>
                <?php else: ?>
                    <button disabled class="w-full bg-slate-800 text-slate-500 font-bold py-2 rounded-xl text-xs cursor-not-allowed">
                        Kosong
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; else: ?>
        <div class="col-span-full py-16 text-center text-slate-400 glass-card-dark rounded-3xl border border-white/5 p-8">
            <span class="text-4xl block mb-2">📦</span>
            <p class="text-sm font-bold text-white">Belum ada produk tersedia untuk outlet ini.</p>
            <p class="text-xs mt-1 text-slate-400">Atur ketersediaan produk pada menu Dashboard Admin Khusus atau beralih ke terminal lain.</p>
            <div class="mt-4 flex justify-center gap-3">
                <button type="button" onclick="bukaModal('modal-ganti-terminal')" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-4 py-2.5 rounded-2xl text-xs transition shadow-lg shadow-blue-600/30 flex items-center gap-2">
                    <span>🔄</span> <span>Ganti Terminal POS</span>
                </button>
                <a href="logout.php" onclick="return confirmLogoutKasir(event)" class="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-white/10 px-4 py-2.5 rounded-2xl text-xs font-bold transition">
                    Keluar Sesi
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
