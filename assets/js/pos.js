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

/**
 * Tombol Cepat Pecahan Uang Tunai (Quick Cash Buttons)
 */
function isiNominalCepat(val) {
    const total = hitungTotal();
    if (val === 'pas') {
        document.getElementById('nominal-bayar').value = total;
    } else {
        document.getElementById('nominal-bayar').value = Number(val);
    }
    hitungKembalian();
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
        kembalianEl.className = 'font-mono font-black text-sm text-rose-400';
    } else {
        kembalianEl.innerText = formatRupiah(kembalian);
        kembalianEl.className = 'font-mono font-black text-sm text-emerald-400';
    }
}

function prosesSimpanTransaksi() {
    const total = hitungTotal();
    let bayar = Number(document.getElementById('nominal-bayar').value) || 0;

    if (metodeTerpilih === 'Cash' && bayar < total) {
        window.VeloceApp.showToast('Uang pembayaran masih kurang!', 'warning');
        return;
    }

    if (metodeTerpilih === 'QRIS') {
        bayar = total;
    }

    const kembalian = Math.max(0, bayar - total);
    const purchasedItems = cart.map(i => ({ ...i }));
    const itemsRingkas = purchasedItems.map(i => `${i.qty}x ${i.nama}`).join(', ');

    const payload = new FormData();
    payload.append('action', 'simpan_transaksi');
    payload.append('items', itemsRingkas);
    payload.append('total_harga', total);
    payload.append('uang_diterima', bayar);
    payload.append('kembalian', kembalian);
    payload.append('metode', metodeTerpilih);
    payload.append('detail_items', JSON.stringify(purchasedItems));

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
            // 1. Kurangi stok visual di product grid secara lokal tanpa reload layar
            purchasedItems.forEach(item => {
                const btnItem = document.querySelector(`.item-produk button[onclick*="tambahKeKeranjang(${item.id},"]`);
                if (btnItem) {
                    const card = btnItem.closest('.item-produk');
                    if (card) {
                        const badgeSisa = card.querySelector('.badge-sisa-stok');
                        if (badgeSisa) {
                            const cur = parseInt(badgeSisa.innerText.replace(/\D/g, '')) || 0;
                            const nextVal = Math.max(0, cur - item.qty);
                            badgeSisa.innerText = 'Sisa: ' + nextVal;
                            if (nextVal === 0) {
                                badgeSisa.remove();
                                const imgWrapper = card.querySelector('.product-img-wrapper');
                                if (imgWrapper) {
                                    const overlay = document.createElement('div');
                                    overlay.className = 'product-empty-overlay absolute inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center';
                                    overlay.innerHTML = '<span class="badge-stok-habis bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[10px] font-black uppercase px-2.5 py-1 rounded-full tracking-wider">Stok Habis</span>';
                                    imgWrapper.appendChild(overlay);
                                }
                                btnItem.disabled = true;
                                btnItem.className = 'w-full bg-slate-800 text-slate-500 font-bold py-2 rounded-xl text-xs cursor-not-allowed';
                                btnItem.innerText = 'Kosong';
                            }
                        }
                    }
                }
            });

            // 2. Simpan nota ke localStorage untuk fitur Cetak Ulang Nota Terakhir
            const receiptData = {
                id_transaksi: res.id_transaksi,
                tanggal: res.tanggal,
                waktu: res.waktu,
                total: total,
                metode: metodeTerpilih,
                uang_diterima: bayar,
                kembalian: kembalian,
                items: purchasedItems
            };
            try {
                localStorage.setItem('twb_last_receipt', JSON.stringify(receiptData));
            } catch (e) {}

            tutupModal('modal-bayar');
            tampilkanStruk(res.id_transaksi, res.tanggal, res.waktu, total, metodeTerpilih, bayar, kembalian, purchasedItems);
            
            // 3. Kosongkan keranjang untuk pembeli berikutnya
            cart = [];
            renderCart();
            window.VeloceApp.showToast('Transaksi berhasil disimpan & stok diperbarui!', 'success');
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

/**
 * Tampilkan Struk Nota Belanja Lengkap dengan Rincian Tunai & Kembalian
 */
function tampilkanStruk(noId, tgl, wkt, total, metode, bayar, kembalian, items) {
    document.getElementById('nota-id').innerText = noId;
    document.getElementById('nota-date-time').innerText = `${tgl} ${wkt}`;
    document.getElementById('nota-total').innerText = formatRupiah(total);
    document.getElementById('nota-metode').innerText = metode;

    const rowTunai = document.getElementById('nota-row-tunai');
    const rowKembalian = document.getElementById('nota-row-kembalian');
    const elTunai = document.getElementById('nota-uang-diterima');
    const elKembalian = document.getElementById('nota-kembalian');

    if (metode === 'Cash') {
        if (rowTunai) rowTunai.style.display = 'flex';
        if (rowKembalian) rowKembalian.style.display = 'flex';
        if (elTunai) elTunai.innerText = formatRupiah(bayar || total);
        if (elKembalian) elKembalian.innerText = formatRupiah(kembalian || 0);
    } else {
        // Non-tunai (QRIS)
        if (rowTunai) rowTunai.style.display = 'none';
        if (rowKembalian) {
            rowKembalian.style.display = 'flex';
            if (elKembalian) elKembalian.innerText = 'Rp 0 (LUNAS)';
        }
    }

    let itemsHtml = '';
    const list = (items && items.length > 0) ? items : cart;
    list.forEach(i => {
        itemsHtml += `
            <div class="flex justify-between">
                <span>${i.qty}x ${i.nama}</span>
                <span class="font-mono">${formatRupiah(i.harga * i.qty)}</span>
            </div>
        `;
    });
    document.getElementById('nota-items').innerHTML = itemsHtml;
    bukaModal('modal-struk');
}

/**
 * Cetak Nota Belanja Pembeli (Thermal 58mm/80mm)
 */
function cetakNotaBelanja() {
    document.body.classList.add('printing-nota');
    document.body.classList.remove('printing-zreport');

    const areaNota = document.getElementById('area-cetak-nota');
    const areaZreport = document.getElementById('area-cetak-zreport');
    if (areaZreport) {
        areaZreport.classList.add('hidden');
        areaZreport.style.display = 'none';
    }
    if (areaNota) {
        areaNota.classList.remove('hidden');
        areaNota.style.display = 'block';
    }

    window.print();

    setTimeout(() => {
        document.body.classList.remove('printing-nota');
    }, 1500);
}

/**
 * Tutup Modal Struk Instan Tanpa Page Reload
 */
function tutupModalStruk() {
    tutupModal('modal-struk');
    if (window.VeloceApp && window.VeloceApp.showToast) {
        window.VeloceApp.showToast('Siap melayani transaksi berikutnya.', 'info');
    }
}

/**
 * Fitur Cetak Ulang Nota Terakhir (Jika Printer Macet / Pembeli Minta Nota Lagi)
 */
function cetakUlangNotaTerakhir() {
    let lastReceipt = null;
    try {
        const stored = localStorage.getItem('twb_last_receipt');
        if (stored) lastReceipt = JSON.parse(stored);
    } catch (e) {}

    if (!lastReceipt || !lastReceipt.id_transaksi) {
        if (window.VeloceApp && window.VeloceApp.alert) {
            window.VeloceApp.alert('Belum ada transaksi yang dicatat pada sesi kasir ini.', 'Struk Terakhir Kosong', 'info');
        } else {
            alert('Belum ada riwayat transaksi pada sesi ini.');
        }
        return;
    }

    tampilkanStruk(
        lastReceipt.id_transaksi,
        lastReceipt.tanggal,
        lastReceipt.waktu,
        lastReceipt.total,
        lastReceipt.metode,
        lastReceipt.uang_diterima,
        lastReceipt.kembalian,
        lastReceipt.items
    );
}
