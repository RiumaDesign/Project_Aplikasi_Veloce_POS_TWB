/**
 * Veloce POS - Cashier Cart & Transaction JS
 */

let cart = [];
let metodeTerpilih = 'Cash';

function formatRupiah(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function filterProduk(keyword) {
    keyword = keyword.toLowerCase().trim();
    const items = document.querySelectorAll('.item-produk');
    items.forEach(el => {
        const nama = el.getAttribute('data-nama') || '';
        if (nama.includes(keyword)) {
            el.style.display = 'flex';
        } else {
            el.style.display = 'none';
        }
    });
}

function tambahKeKeranjang(id, nama, harga, stokMax) {
    const existing = cart.find(i => i.id === id);
    if (existing) {
        if (existing.qty + 1 > stokMax) {
            window.VeloceApp.showToast(`Stok ${nama} tidak mencukupi! Maksimal: ${stokMax}`, 'warning');
            return;
        }
        existing.qty += 1;
    } else {
        cart.push({ id: id, nama: nama, harga: harga, qty: 1, stokMax: stokMax });
    }
    renderCart();
}

function kurangiDariKeranjang(id) {
    const existing = cart.find(i => i.id === id);
    if (existing) {
        existing.qty -= 1;
        if (existing.qty <= 0) {
            cart = cart.filter(i => i.id !== id);
        }
    }
    renderCart();
}

function hapusItem(id) {
    cart = cart.filter(i => i.id !== id);
    renderCart();
}

function kosongkanKeranjang() {
    if (cart.length === 0) return;
    VeloceApp.confirm({
        title: 'Kosongkan Keranjang?',
        message: 'Semua produk yang telah dimasukkan ke dalam keranjang pesanan akan dibersihkan.',
        icon: '🛒',
        type: 'danger',
        confirmText: 'Ya, Kosongkan',
        cancelText: 'Batal'
    }).then(confirmed => {
        if (confirmed) {
            cart = [];
            renderCart();
        }
    });
}

function hitungTotal() {
    return cart.reduce((acc, i) => acc + (i.harga * i.qty), 0);
}

function renderCart() {
    const container = document.getElementById('cart-items-wrapper');
    const totalEl = document.getElementById('cart-grand-total');
    const subtotalEl = document.getElementById('cart-subtotal');
    const countEl = document.getElementById('cart-item-count');
    const btnCheckout = document.getElementById('btn-checkout');

    if (!container) return;

    if (cart.length === 0) {
        container.innerHTML = `
            <div id="empty-cart-state" class="py-20 text-center text-slate-500">
                <span class="text-4xl block mb-2 opacity-50">🛒</span>
                <p class="text-xs font-semibold text-slate-400">Keranjang masih kosong</p>
                <p class="text-[10px] text-slate-600 mt-0.5">Klik salah satu produk untuk menambahkan</p>
            </div>
        `;
        totalEl.innerText = 'Rp 0';
        subtotalEl.innerText = 'Rp 0';
        countEl.innerText = '0 Item Dipilih';
        btnCheckout.setAttribute('disabled', 'disabled');
        return;
    }

    let totalPcs = 0;
    let html = '';

    cart.forEach(item => {
        totalPcs += item.qty;
        const sub = item.harga * item.qty;
        html += `
            <div class="py-3 flex items-center justify-between gap-3 group">
                <div class="flex-1 min-w-0">
                    <h4 class="text-xs font-bold text-white truncate leading-tight">${item.nama}</h4>
                    <p class="text-[11px] font-medium text-slate-400 mt-0.5">${formatRupiah(item.harga)} × ${item.qty}</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="cart-stepper flex items-center bg-slate-950 border border-white/10 rounded-xl p-0.5">
                        <button onclick="kurangiDariKeranjang(${item.id})" class="w-6 h-6 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 flex items-center justify-center font-bold text-xs">－</button>
                        <span class="w-6 text-center text-xs font-bold text-white">${item.qty}</span>
                        <button onclick="tambahKeKeranjang(${item.id}, '${item.nama}', ${item.harga}, ${item.stokMax})" class="w-6 h-6 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 flex items-center justify-center font-bold text-xs">＋</button>
                    </div>
                    <span class="text-xs font-bold text-emerald-400 w-20 text-right">${formatRupiah(sub)}</span>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    const grand = hitungTotal();
    totalEl.innerText = formatRupiah(grand);
    subtotalEl.innerText = formatRupiah(grand);
    countEl.innerText = `${totalPcs} Item Dipilih`;
    btnCheckout.removeAttribute('disabled');
}

function bukaModalBayar() {
    if (cart.length === 0) return;
    const total = hitungTotal();
    document.getElementById('modal-total-display').innerText = formatRupiah(total);
    document.getElementById('nominal-bayar').value = total;
    hitungKembalian();
    bukaModal('modal-bayar');
}

function pilihMetode(metode) {
    metodeTerpilih = metode;
    const btnCash = document.getElementById('btn-metode-cash');
    const btnQris = document.getElementById('btn-metode-qris');
    const tunaiWrapper = document.getElementById('input-tunai-wrapper');

    if (metode === 'Cash') {
        btnCash.className = "p-3 rounded-xl border border-blue-500 bg-blue-500/20 text-blue-300 font-bold text-xs flex items-center justify-center gap-2";
        btnQris.className = "p-3 rounded-xl border border-white/10 bg-slate-900 text-slate-400 font-bold text-xs flex items-center justify-center gap-2";
        tunaiWrapper.classList.remove('hidden');
    } else {
        btnQris.className = "p-3 rounded-xl border border-blue-500 bg-blue-500/20 text-blue-300 font-bold text-xs flex items-center justify-center gap-2";
        btnCash.className = "p-3 rounded-xl border border-white/10 bg-slate-900 text-slate-400 font-bold text-xs flex items-center justify-center gap-2";
        tunaiWrapper.classList.add('hidden');
    }
}

function hitungKembalian() {
    const total = hitungTotal();
    const bayar = Number(document.getElementById('nominal-bayar').value) || 0;
    const kembalian = bayar - total;
    const kembalianEl = document.getElementById('kembalian-display');

    if (kembalian < 0) {
        kembalianEl.innerText = 'Kurang ' + formatRupiah(Math.abs(kembalian));
        kembalianEl.className = 'font-bold text-rose-400';
    } else {
        kembalianEl.innerText = formatRupiah(kembalian);
        kembalianEl.className = 'font-bold text-emerald-400';
    }
}

function prosesSimpanTransaksi() {
    const total = hitungTotal();
    const bayar = Number(document.getElementById('nominal-bayar').value) || 0;

    if (metodeTerpilih === 'Cash' && bayar < total) {
        window.VeloceApp.showToast('Uang pembayaran masih kurang!', 'warning');
        return;
    }

    const itemsRingkas = cart.map(i => `${i.qty}x ${i.nama}`).join(', ');

    const payload = new FormData();
    payload.append('action', 'simpan_transaksi');
    payload.append('items', itemsRingkas);
    payload.append('total_harga', total);
    payload.append('metode', metodeTerpilih);
    payload.append('detail_items', JSON.stringify(cart));

    const btn = document.getElementById('btn-proses-transaksi');
    btn.disabled = true;
    btn.innerHTML = 'Memproses Transaksi...';

    fetch('index.php', {
        method: 'POST',
        body: payload
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<span>Selesaikan & Cetak Nota</span> <span>✓</span>';

        if (res.status === 'success') {
            tutupModal('modal-bayar');
            tampilkanStruk(res.id_transaksi, res.tanggal, res.waktu, total, metodeTerpilih);
            cart = [];
            renderCart();
            window.VeloceApp.showToast('Transaksi berhasil disimpan!', 'success');
        } else {
            VeloceApp.alert(res.message, 'Gagal Transaksi', 'error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<span>Selesaikan & Cetak Nota</span> <span>✓</span>';
        VeloceApp.alert('Terjadi kesalahan koneksi jaringan atau server!', 'Kesalahan Sistem', 'error');
    });
}

function tampilkanStruk(noId, tgl, wkt, total, metode) {
    document.getElementById('nota-id').innerText = noId;
    document.getElementById('nota-date-time').innerText = `${tgl} ${wkt}`;
    document.getElementById('nota-total').innerText = formatRupiah(total);
    document.getElementById('nota-metode').innerText = metode;

    let itemsHtml = '';
    cart.forEach(i => {
        itemsHtml += `
            <div class="flex justify-between">
                <span>${i.qty}x ${i.nama}</span>
                <span>${formatRupiah(i.harga * i.qty)}</span>
            </div>
        `;
    });
    document.getElementById('nota-items').innerHTML = itemsHtml;
    bukaModal('modal-struk');
}

function tutupModalStruk() {
    tutupModal('modal-struk');
    window.location.reload();
}
