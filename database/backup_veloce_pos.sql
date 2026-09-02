-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 01 Sep 2026 pada 04.21
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `veloce_pos`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `kasir`
--

CREATE TABLE `kasir` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL DEFAULT 'kasir123',
  `role` varchar(20) NOT NULL DEFAULT 'kasir'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kasir`
--

INSERT INTO `kasir` (`id`, `nama`, `password`, `role`) VALUES
(1, 'Budi Santoso', 'kasir123', 'kasir'),
(2, 'Siti Rahma', 'kasir123', 'kasir'),
(3, 'Andi Wijaya', 'kasir123', 'kasir'),
(4, 'admin', 'admin123', 'admin');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_stok`
--

CREATE TABLE `log_stok` (
  `id` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `tipe_aktivitas` enum('tambah_gudang','kirim_pos_a','kirim_pos_b') NOT NULL,
  `jumlah` int(11) NOT NULL,
  `tanggal_log` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `log_stok`
--

INSERT INTO `log_stok` (`id`, `id_produk`, `tipe_aktivitas`, `jumlah`, `tanggal_log`) VALUES
(1, 4, 'tambah_gudang', 1000, '2026-07-14 04:43:04'),
(2, 1, 'tambah_gudang', 5000, '2026-07-14 04:43:11'),
(3, 3, 'tambah_gudang', 1000, '2026-07-14 04:43:26'),
(4, 5, 'tambah_gudang', 1000, '2026-07-14 04:43:35'),
(5, 4, 'kirim_pos_a', 100, '2026-07-14 04:59:53'),
(6, 4, 'kirim_pos_b', 100, '2026-07-14 05:00:07'),
(7, 4, 'kirim_pos_a', 100, '2026-07-16 03:09:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `harga` int(11) NOT NULL,
  `stok_pos_a` int(11) DEFAULT 0,
  `stok_pos_b` int(11) DEFAULT 0,
  `custom_type` varchar(50) NOT NULL DEFAULT 'opsi-suhu',
  `gambar` varchar(255) DEFAULT NULL,
  `stok_gudang` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id`, `nama`, `harga`, `stok_pos_a`, `stok_pos_b`, `custom_type`, `gambar`, `stok_gudang`) VALUES
(1, 'Coca Cola', 6000, 0, 0, 'opsi-suhu', NULL, 5000),
(2, 'Sprite', 6000, 0, 0, 'opsi-suhu', NULL, 0),
(3, 'Fanta', 6000, 0, 0, 'opsi-suhu', NULL, 1000),
(4, 'Aqua Botol', 4000, 145, 97, 'opsi-suhu', NULL, 700),
(5, 'Frestea', 6500, 0, 0, 'opsi-suhu', NULL, 1000),
(6, 'Teh Pucuk Harum', 4500, 0, 0, 'opsi-suhu', NULL, 0),
(7, 'Pulpy Orange', 7000, 0, 0, 'opsi-suhu', '1783996278_6a559f7611999.png', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `id_transaksi` varchar(20) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `petugas` varchar(50) NOT NULL,
  `pos_aktif` varchar(10) DEFAULT 'POS A',
  `metode_pembayaran` varchar(20) NOT NULL,
  `item_singkat` text NOT NULL,
  `total_harga` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `metode` varchar(50) NOT NULL DEFAULT 'Cash'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id`, `id_transaksi`, `tanggal`, `waktu`, `petugas`, `pos_aktif`, `metode_pembayaran`, `item_singkat`, `total_harga`, `created_at`, `metode`) VALUES
(1, 'TRX-498487', '2026-07-13', '16:21:38', 'Budi Santoso', 'POS A', 'Cash', '4x Aqua Botol (Dingin)', 16000, '2026-07-13 09:21:38', 'Cash'),
(2, 'TRX-509817', '2026-07-14', '08:28:29', 'Budi Santoso', 'POS A', 'QRIS', '2x Aqua Botol (Dingin), 4x Es Teh (Dingin)', 28000, '2026-07-14 01:28:29', 'Cash'),
(3, '#TRX-1783998318', '2026-07-14', '05:05:29', 'Andi Wijaya', 'POS A', '', '3x Fanta (Dingin)', 18000, '2026-07-14 03:05:29', 'Cash'),
(4, '#TRX-1783998375', '2026-07-14', '05:06:24', 'Andi Wijaya', 'POS A', '', '2x Freastea (Dingin)', 13000, '2026-07-14 03:06:24', 'QRIS'),
(5, 'TRX-1783998449', '2026-07-14', '05:07:29', 'Andi Wijaya', 'POS A', '', '4x Freastea (Dingin)', 26000, '2026-07-14 03:07:29', 'QRIS'),
(6, 'TRX-1783998459', '2026-07-14', '05:07:39', 'Andi Wijaya', 'POS A', '', '4x Freastea (Dingin)', 26000, '2026-07-14 03:07:39', 'Cash'),
(7, 'TRX-1784001101', '2026-07-14', '05:51:41', 'Andi Wijaya', 'POS A', '', '3x Frestea (Dingin)', 19500, '2026-07-14 03:51:41', 'QRIS'),
(8, 'TRX-1784005308', '2026-07-14', '07:01:48', 'andi', 'POS B', '', '3x Aqua Botol', 12000, '2026-07-14 05:01:48', 'Cash'),
(9, 'TRX-1784018562', '2026-07-14', '10:42:42', 'andi wijaya', 'POS A', '', '4x Aqua Botol', 16000, '2026-07-14 08:42:42', 'Cash'),
(10, 'TRX-1784019093', '2026-07-14', '10:51:33', 'andi wijaya', 'POS A', '', '5x Aqua Botol', 20000, '2026-07-14 08:51:33', 'QRIS'),
(11, 'TRX-1784019334', '2026-07-14', '10:55:34', 'andi wijaya', 'POS A', '', '3x Aqua Botol', 12000, '2026-07-14 08:55:34', 'QRIS'),
(12, 'TRX-1784019515', '2026-07-14', '10:58:35', 'andi wijaya', 'POS A', '', '8x Aqua Botol', 32000, '2026-07-14 08:58:35', 'QRIS'),
(13, 'TRX-1784019864', '2026-07-14', '11:04:24', 'Andi Wijaya', 'POS A', '', '10x Aqua Botol', 40000, '2026-07-14 09:04:24', 'Cash'),
(14, 'TRX-1784020140', '2026-07-14', '11:09:00', 'Siti Rahma', 'POS A', '', '11x Aqua Botol', 44000, '2026-07-14 09:09:00', 'QRIS'),
(15, 'TRX-1784164472', '2026-07-16', '03:14:32', 'Andi Wijaya', 'POS A', '', '4x Aqua Botol', 16000, '2026-07-16 01:14:32', 'QRIS'),
(16, 'TRX-1784171328', '2026-07-16', '05:08:48', 'Andi Wijaya', 'POS A', '', '5x Aqua Botol', 20000, '2026-07-16 03:08:48', 'QRIS'),
(17, 'TRX-1784862821', '2026-07-24', '05:13:41', 'Andi Wijaya', 'POS A', '', '5x Aqua Botol', 20000, '2026-07-24 03:13:41', 'Cash');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `kasir`
--
ALTER TABLE `kasir`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `log_stok`
--
ALTER TABLE `log_stok`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_transaksi` (`id_transaksi`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `kasir`
--
ALTER TABLE `kasir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `log_stok`
--
ALTER TABLE `log_stok`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `log_stok`
--
ALTER TABLE `log_stok`
  ADD CONSTRAINT `log_stok_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
