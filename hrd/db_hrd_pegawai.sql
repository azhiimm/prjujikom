-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 19 Bulan Mei 2025 pada 02.49
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_hrd_pegawai`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `eselon`
--

CREATE TABLE `eselon` (
  `id` int(11) NOT NULL,
  `nama_eselon` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `eselon`
--

INSERT INTO `eselon` (`id`, `nama_eselon`) VALUES
(1, 'II.b'),
(2, 'III.a'),
(3, 'III.b'),
(4, 'IV.a'),
(5, 'IV.b'),
(6, 'JF'),
(7, 'NE');

-- --------------------------------------------------------

--
-- Struktur dari tabel `history_pegawai`
--

CREATE TABLE `history_pegawai` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jabatan`
--

CREATE TABLE `jabatan` (
  `id` int(11) NOT NULL,
  `nama_jabatan` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jabatan`
--

INSERT INTO `jabatan` (`id`, `nama_jabatan`) VALUES
(1, 'Ahli Pertama - Analis Kebijakan'),
(2, 'Ahli Pertama - Analis Sumber Daya Manusia Aparatur'),
(3, 'Ahli Pertama - Arsiparis'),
(4, 'Ahli Pertama - Pranata Hubungan Masyarakat'),
(5, 'Ahli Pertama - Pranata Komputer'),
(6, 'Analis Kebijakan Ahli Muda'),
(7, 'Analis Sumber Daya Manusia Aparatur Ahli Muda'),
(8, 'Penata Kependudukan dan Keluarga Berencana Ahli Muda'),
(9, 'Analis Keluarga Berencana'),
(10, 'Analis Pemberdayaan Perempuan dan Anak'),
(11, 'Analis Rencana Program dan Kegiatan'),
(12, 'Penata Kelola Pemberdayaan Perempuan dan Perlindungan Anak'),
(13, 'Penata Layanan Operasional'),
(14, 'Penata Pameran'),
(15, 'Penelaah Teknis Kebijakan'),
(16, 'Pengelola Bina Kesejahteraan Keluarga'),
(17, 'Pengelola Layanan Kehumasan'),
(18, 'Pengelola Layanan Keluarga Berencana, Bina Keluarga Remaja dan Bina Keluarga Lansia'),
(19, 'Pengelola Pemanfaatan Barang Milik Daerah'),
(20, 'Pengelola Pemberdayaan, Perlindungan Perempuan dan Anak'),
(21, 'Pengelola Penguatan Pengarusutamaan Gender'),
(22, 'Pengelola Program dan Kegiatan'),
(23, 'Pengolah Data dan Informasi'),
(24, 'Pengadministrasi Alat dan Obat Kontrasepsi'),
(25, 'Pengadministrasi Kepegawaian'),
(26, 'Pengadministrasi Keuangan'),
(27, 'Pengadministrasi Sarana dan Prasarana'),
(28, 'Pengadministrasi Umum'),
(29, 'Pengadministrasi Perkantoran'),
(30, 'Penyuluh Peningkatan Kualitas Hidup Perempuan dan Anak'),
(31, 'Penyusun Pencatatan dan Pelaporan Data Kependudukan dan Keluarga Berencana'),
(32, 'Kepala Bidang Pengarustamaan Gender dan Pemenuhan Hak Anak'),
(33, 'Kepala Bidang Pengendalian Penduduk, Keluarga Berencana dan Keluarga Sejahtera'),
(34, 'Kepala Bidang Perlindungan Perempuan dan Anak'),
(35, 'Kepala Dinas Pemberdayaan Perempuan dan Perlindungan Anak serta Pengendalian Penduduk dan Keluarga Berencana'),
(36, 'Kepala Sub Bagian Keuangan'),
(37, 'Kepala Sub Bagian Tata Usaha UPTD Perlindungan Perempuan dan Anak'),
(38, 'Kepala UPTD Perlindungan Perempuan dan Anak'),
(39, 'Bendahara'),
(40, 'Pengawas Perempuan dan Anak'),
(41, 'Sekretaris'),
(42, 'Pelayan'),
(43, 'Penjaga Kantor'),
(44, 'Petugas Kebersihan'),
(45, 'Satgas Jamuan'),
(46, 'Satgas Keamanan'),
(47, 'Sopir'),
(48, 'Sopir VIP');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kelas_jabatan`
--

CREATE TABLE `kelas_jabatan` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kelas_jabatan`
--

INSERT INTO `kelas_jabatan` (`id`, `nama_kelas`) VALUES
(1, '3'),
(2, '4'),
(3, '4A'),
(4, '5'),
(5, '6'),
(6, '7');

-- --------------------------------------------------------

--
-- Struktur dari tabel `lokasi`
--

CREATE TABLE `lokasi` (
  `id` int(11) NOT NULL,
  `nama_lokasi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lokasi`
--

INSERT INTO `lokasi` (`id`, `nama_lokasi`) VALUES
(1, 'Dalam Kantor'),
(2, 'Luar Kantor'),
(3, 'GOW'),
(4, 'Shelter ABH'),
(5, 'Shelter PR Anak'),
(6, 'Shelter PR Dewasa');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pangkat_golongan`
--

CREATE TABLE `pangkat_golongan` (
  `id` int(11) NOT NULL,
  `nama_pangkat` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pangkat_golongan`
--

INSERT INTO `pangkat_golongan` (`id`, `nama_pangkat`) VALUES
(1, 'Pembina, IV/a'),
(2, 'Penata, III/c'),
(3, 'Penata Muda, III/a'),
(4, 'Pembina Tk. I, IV/b'),
(5, 'Penata Muda Tk. I, III/a'),
(6, 'Pengatur Tk. I, II/d'),
(7, 'Penata Tk. I, III/d'),
(8, 'Sembilan, IX');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int(11) NOT NULL,
  `nip` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `pangkat_golongan_id` int(11) DEFAULT NULL,
  `unit_kerja_id` int(11) DEFAULT NULL,
  `status` enum('PNS','PPPK','TENAGA KONTRAK') NOT NULL,
  `jabatan_id` int(11) DEFAULT NULL,
  `eselon_id` int(11) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `pendidikan` varchar(100) DEFAULT NULL,
  `lokasi_id` int(11) DEFAULT NULL,
  `kelas_jabatan_id` int(11) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `report_bulanan`
--

CREATE TABLE `report_bulanan` (
  `id` int(11) NOT NULL,
  `bulan` int(11) NOT NULL,
  `tahun` int(11) NOT NULL,
  `tanggal_generate` timestamp NOT NULL DEFAULT current_timestamp(),
  `generated_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `report_detail`
--

CREATE TABLE `report_detail` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `has_changes` tinyint(1) DEFAULT 0,
  `changes_detail` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `unit_kerja`
--

CREATE TABLE `unit_kerja` (
  `id` int(11) NOT NULL,
  `nama_unit` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `unit_kerja`
--

INSERT INTO `unit_kerja` (`id`, `nama_unit`) VALUES
(1, 'Bidang Pengendalian Penduduk, Keluarga Berencana dan Keluarga Sejahtera'),
(2, 'Bidang Pengarustamaan Gender dan Pemenuhan Hak Anak'),
(3, 'Bidang Perlindungan Perempuan dan Anak'),
(4, 'Sekretariat'),
(5, 'Sub Bagian Keuangan'),
(6, 'Sub Bagian Tata Usaha UPTD Perlindungan Perempuan dan Anak'),
(7, 'Sub Bagian Tata Usaha UPTD Perlindungan Perempuan dan Anak (Shelter Anak dan Perempuan)'),
(8, 'Sub Bagian Tata Usaha UPTD Perlindungan Perempuan dan Anak (Shelter ABH)'),
(9, 'Sub Bagian Tata Usaha UPTD Perlindungan Perempuan dan Anak (Shelter Perempuan Dewasa)'),
(10, 'UPTD Perlindungan Perempuan dan Anak'),
(11, 'UPTD Perlindungan Perempuan dan Anak (Shelter Anak dan Perempuan)'),
(12, 'UPTD Perlindungan Perempuan dan Anak (Shelter ABH)'),
(13, 'UPTD Perlindungan Perempuan dan Anak (Shelter Perempuan Dewasa)');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','user','manager') NOT NULL DEFAULT 'user',
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `email`, `created_at`) VALUES
(1, 'admin', 'admin123', 'Admin', 'admin', 'admin@example.com', '2025-05-02 03:19:29'),
(2, 'user1', 'user123', 'Ilham Azhiim', 'user', 'user1@example.com', '2025-05-02 03:19:29'),
(3, 'manager', 'manager123', 'Manager HRD', 'manager', 'manager@example.com', '2025-05-02 03:19:29');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `eselon`
--
ALTER TABLE `eselon`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `history_pegawai`
--
ALTER TABLE `history_pegawai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indeks untuk tabel `jabatan`
--
ALTER TABLE `jabatan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kelas_jabatan`
--
ALTER TABLE `kelas_jabatan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `lokasi`
--
ALTER TABLE `lokasi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pangkat_golongan`
--
ALTER TABLE `pangkat_golongan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD KEY `pangkat_golongan_id` (`pangkat_golongan_id`),
  ADD KEY `unit_kerja_id` (`unit_kerja_id`),
  ADD KEY `jabatan_id` (`jabatan_id`),
  ADD KEY `eselon_id` (`eselon_id`),
  ADD KEY `lokasi_id` (`lokasi_id`),
  ADD KEY `kelas_jabatan_id` (`kelas_jabatan_id`);

--
-- Indeks untuk tabel `report_bulanan`
--
ALTER TABLE `report_bulanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indeks untuk tabel `report_detail`
--
ALTER TABLE `report_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indeks untuk tabel `unit_kerja`
--
ALTER TABLE `unit_kerja`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `eselon`
--
ALTER TABLE `eselon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `history_pegawai`
--
ALTER TABLE `history_pegawai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `jabatan`
--
ALTER TABLE `jabatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT untuk tabel `kelas_jabatan`
--
ALTER TABLE `kelas_jabatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `lokasi`
--
ALTER TABLE `lokasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `pangkat_golongan`
--
ALTER TABLE `pangkat_golongan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `report_bulanan`
--
ALTER TABLE `report_bulanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `report_detail`
--
ALTER TABLE `report_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `unit_kerja`
--
ALTER TABLE `unit_kerja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `history_pegawai`
--
ALTER TABLE `history_pegawai`
  ADD CONSTRAINT `history_pegawai_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`),
  ADD CONSTRAINT `history_pegawai_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  ADD CONSTRAINT `pegawai_ibfk_1` FOREIGN KEY (`pangkat_golongan_id`) REFERENCES `pangkat_golongan` (`id`),
  ADD CONSTRAINT `pegawai_ibfk_2` FOREIGN KEY (`unit_kerja_id`) REFERENCES `unit_kerja` (`id`),
  ADD CONSTRAINT `pegawai_ibfk_3` FOREIGN KEY (`jabatan_id`) REFERENCES `jabatan` (`id`),
  ADD CONSTRAINT `pegawai_ibfk_4` FOREIGN KEY (`eselon_id`) REFERENCES `eselon` (`id`),
  ADD CONSTRAINT `pegawai_ibfk_5` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi` (`id`),
  ADD CONSTRAINT `pegawai_ibfk_6` FOREIGN KEY (`kelas_jabatan_id`) REFERENCES `kelas_jabatan` (`id`);

--
-- Ketidakleluasaan untuk tabel `report_bulanan`
--
ALTER TABLE `report_bulanan`
  ADD CONSTRAINT `report_bulanan_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `report_detail`
--
ALTER TABLE `report_detail`
  ADD CONSTRAINT `report_detail_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `report_bulanan` (`id`),
  ADD CONSTRAINT `report_detail_ibfk_2` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
