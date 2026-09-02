# Veloce POS - PT Taman Wisata Borobudur, Prambanan & Ratu Boko (TWB)

![TWB Logo](assets/images/logo_twb.png)

Sistem Point of Sale (POS), Inventori Multi-Lokasi, dan Pengawasan Distribusi Stok Terpadu untuk operasional **PT Taman Wisata Candi Borobudur, Prambanan & Ratu Boko**.

Aplikasi ini mengintegrasikan seluruh operasional kasir retail fisik, mesin otomatis (*Vending Machine* VM 1 s/d VM 9+), serta logistik Gudang Pusat Borobudur ke dalam satu ekosistem terpadu secara *real-time*.

---

## 🌟 Fitur Utama Sistem

### 1. Terminal Kasir POS Modern (*Cashier Interface*)
- Antarmuka berkecepatan tinggi berbasis *Glassmorphism UI* dengan performa instan tanpa reload halaman.
- Mendukung multi-terminal/outlet kasir fisik (Outlet Museum, Outlet Refreshment Barat, dsb.).
- Perhitungan total transaksi, diskon, pembayaran Tunai (*Cash*), dan QRIS dinamis secara otomatis.
- Cetak struk belanja thermal 58mm / 80mm standar retail dengan logo resmi PT TWB.
- Pengalihan tema ganda instan: **Mode Gelap (*Dark Mode*)** dan **Mode Terang (*Light Mode*)** berstandar kontras tinggi (WCAG AAA).

### 2. Dashboard Admin Khusus & Analisis Eksekutif
- **Grafik & Analisis Pendapatan:**
  - Grafik donat interaktif proporsi **Omzet Penjualan per Outlet & Vending Machine**.
  - Diagram batang kuantitas produk terlaris (*Top-Selling Products*).
  - Monitoring visual perbandingan ketersediaan stok fisik Gudang vs Outlet Museum vs Outlet Barat.
- **Buku Mutasi & Kontrol Persediaan Barang (*Audit Trail*):**
  - **4 Kartu Metrik Ringkasan Eksekutif (*KPI Cards*):** Pasokan Masuk (*Inbound*), Mutasi DO (*Transfer*), Penjualan Retail (*Outbound*), dan Retur & Kerusakan (*Karantina*).
  - Pencatatan log mutasi kronologis 1-to-1: Waktu, Nomor Dokumen/Nota, Kode SKU Produk (`#PRD-xxx`), Lokasi Asal-Tujuan, Volume (+/-), Penanggung Jawab, dan Catatan Audit.
  - Filter multi-kriteria: Kategori mutasi, produk, titik lokasi, dan rentang tanggal.
- **Ekspor Dokumen Resmi (BUMN / Instansi):**
  - Cetak laporan PDF resmi lengkap dengan Kop Surat PT Taman Wisata Candi Borobudur, Prambanan & Ratu Boko, tabel Ringkasan Eksekutif, dan tanda tangan Supervisor Logistik.
  - Unduh rekapitulasi data dalam format Microsoft Excel (.xls) dan CSV yang kompatibel dengan sistem ERP.
  - Audit log pencatatan riwayat pengunduhan berkas (*who, when, what, ip address*).

### 3. Matriks Stok Multi-Lokasi Dinamis
- Visualisasi matriks stok tabel silang (*cross-table*) seluruh produk terhadap seluruh titik lokasi (Gudang Pusat, Outlet Fisik, dan Vending Machine VM 1 s/d VM 9+).
- Penambahan stok masuk gudang (*Inbound Supply*) dari vendor/distributor.
- Penerbitan *Delivery Order (DO)* otomatis untuk pengiriman barang antar-titik.
- Modul Retur & Barang Rusak untuk karantina produk kedaluwarsa atau cacat kemasan.

### 4. Sistem Pop-up Modal Kustom Modern
- Dialog konfirmasi mandiri (*Pure Standalone CSS & JS - Zero Dependency*) menggantikan pop-up native browser `alert()` dan `confirm()`.
- Animasi halus, penataan terpusat z-index tinggi, dukungan navigasi keyboard (`Escape` untuk batal), dan tampilan adaptif di Dark/Light Mode.

### 5. Pusat Notifikasi & Sistem Peringatan Real-time Admin
- **Top Navigation Bar:** Navigasi atas Glassmorphism modern dengan tombol toggle sidebar, breadcrumbs, dan indikator status online.
- **Peringatan Stok Otomatis:** Deteksi cerdas stok menipis ($\le 10$ pcs) dan stok kritis/habis ($0 - 3$ pcs) di seluruh gudang, outlet museum, outlet barat, dan vending machine VM 1-9+.
- **Dropdown Lonceng Interaktif:** Dilengkapi lencana merah menyala (*pulse badge counter*), tab filter (*Semua, Stok Kritis, Retur, Sistem*), waktu lampau ramah (*time ago*), tombol tautan aksi langsung, serta fungsi *Tandai Semua Dibaca*.
- **Polling Real-time:** Polling otomatis di latar belakang setiap 30 detik tanpa perlu memuat ulang (*reload*) halaman.

---

## 🛠️ Arsitektur & Teknologi

- **Backend:** PHP 8.x (Native Object-Oriented & Procedural Hybrid, Clean Architecture).
- **Database:** MySQL / MariaDB dengan *Foreign Key Constraints*, *Triggers*, dan *Transactions (ACID)*.
- **Frontend Styling:** Vanilla CSS3, Custom Glassmorphism Theme Engine, Tailwind CSS Utility, Google Fonts (Inter).
- **Visualisasi Grafik:** Chart.js Library.
- **Dokumentasi & Ekspor:** Native HTML-to-PDF Print Engine & Excel BIFF-Compatible XML.

---

## 🚀 Panduan Instalasi & Menjalankan Lokal

### Prasyarat:
- Web Server Apache & Database MySQL (direkomendasikan menggunakan [XAMPP](https://www.apachefriends.org/)).
- PHP versi 8.0 ke atas.

### Langkah-Langkah:
1. **Clone Repositori:**
   ```bash
   git clone https://github.com/RiumaDesign/Project_Aplikasi_Veloce_POS_TWB.git
   ```
2. Pindahkan folder proyek ke direktori web server Anda (misalnya: `C:/xampp/htdocs/pos`).
3. **Import Database:**
   - Buka `http://localhost/phpmyadmin/`.
   - Buat database baru bernama: `veloce_pos`.
   - Import berkas SQL terbaru yang berada di:
     `database/veloce_pos_latest.sql`.
4. **Konfigurasi Database (Jika Diperlukan):**
   - Buka file `config/database.php`:
     ```php
     $db_host = "localhost";
     $db_user = "root";
     $db_pass = "";
     $db_name = "veloce_pos";
     ```
5. **Jalankan Aplikasi:**
   - **Terminal Kasir POS:** `http://localhost/pos/index.php`
   - **Dashboard Admin Khusus:** `http://localhost/pos/dashboard.php?page=analytics`
   - **Login Sesi:** `http://localhost/pos/login.php`

---

## 👥 Hak Akses & Akun Bawaan

| Role | Username | Password | Akses Halaman |
| :--- | :--- | :--- | :--- |
| **Admin Khusus** | `admin` | `admin123` | Dashboard Admin, Analisis Omzet, Kelola Produk, Kelola Stok, Kelola Kasir, Kelola Outlet |
| **Petugas Kasir** | `budi` | `kasir123` | Terminal Kasir POS & Transaksi Pembayaran |
| **Petugas Kasir** | `siti` | `kasir123` | Terminal Kasir POS & Transaksi Pembayaran |

---

## 📄 Lisensi & Hak Cipta
Hak Cipta © 2026 PT Taman Wisata Candi Borobudur, Prambanan & Ratu Boko (TWB). Seluruh hak cipta dilindungi undang-undang.
