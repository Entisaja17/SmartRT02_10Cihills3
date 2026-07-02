-- MySQL setup script for SmartRT02
-- Create database and tables for manual migration

CREATE DATABASE IF NOT EXISTS `db_smart_rt` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_smart_rt`;

DROP TABLE IF EXISTS `iuran`;
DROP TABLE IF EXISTS `keuangan`;
DROP TABLE IF EXISTS `surat`;
DROP TABLE IF EXISTS `keluhan`;
DROP TABLE IF EXISTS `pengumuman`;
DROP TABLE IF EXISTS `warga`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `hp` VARCHAR(50) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `foto` LONGTEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `warga` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `no_kk` VARCHAR(50) DEFAULT NULL,
  `nik` VARCHAR(50) DEFAULT NULL,
  `nama` VARCHAR(255) DEFAULT NULL,
  `jk` VARCHAR(2) DEFAULT NULL,
  `kerja` VARCHAR(100) DEFAULT NULL,
  `hp` VARCHAR(50) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `username` VARCHAR(100) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pengumuman` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tgl` VARCHAR(50) DEFAULT NULL,
  `judul` VARCHAR(255) DEFAULT NULL,
  `konten` TEXT DEFAULT NULL,
  `penulis` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `keluhan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tgl` VARCHAR(50) DEFAULT NULL,
  `user` VARCHAR(100) DEFAULT NULL,
  `nama` VARCHAR(255) DEFAULT NULL,
  `kategori` VARCHAR(100) DEFAULT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `foto` LONGTEXT DEFAULT NULL,
  `foto_admin` LONGTEXT DEFAULT NULL,
  `status` VARCHAR(100) DEFAULT NULL,
  `tanggapan` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `surat` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tgl` VARCHAR(50) DEFAULT NULL,
  `user` VARCHAR(100) DEFAULT NULL,
  `nama` VARCHAR(255) DEFAULT NULL,
  `jenis` VARCHAR(255) DEFAULT NULL,
  `ket` TEXT DEFAULT NULL,
  `status` VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `keuangan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tgl` VARCHAR(50) DEFAULT NULL,
  `jenis` VARCHAR(100) DEFAULT NULL,
  `kategori` VARCHAR(100) DEFAULT NULL,
  `ket` TEXT DEFAULT NULL,
  `masuk` INT DEFAULT 0,
  `keluar` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `iuran` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_warga` INT DEFAULT NULL,
  `user` VARCHAR(100) DEFAULT NULL,
  `nama` VARCHAR(255) DEFAULT NULL,
  `bulan` VARCHAR(50) DEFAULT NULL,
  `nominal` INT DEFAULT 0,
  `status` VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data
INSERT INTO `users` (`username`, `password`, `nama`, `role`) VALUES
('admin', 'edudigital', 'Bpk. Budi (Ketua RT)', 'admin'),
('peserta', 'edudigital', 'Sdr. Andi (Warga)', 'warga');

INSERT INTO `warga` (`no_kk`, `nik`, `nama`, `jk`, `kerja`, `hp`, `alamat`) VALUES
('320101010101', '320101010102', 'Andi S', 'L', 'Karyawan', '0811', 'Blok A/1'),
('320101010101', '320101010103', 'Siti M', 'P', 'IRT', '0812', 'Blok A/1');

INSERT INTO `pengumuman` (`tgl`, `judul`, `konten`, `penulis`) VALUES
('26/05/2026', 'Kerja Bakti Minggu Depan', 'Mohon kehadiran seluruh bapak-bapak untuk membersihkan selokan di blok A.', 'Admin RT');

INSERT INTO `keluhan` (`tgl`, `user`, `nama`, `kategori`, `deskripsi`, `status`, `tanggapan`) VALUES
('25/05/2026', 'peserta', 'Sdr. Andi', 'Infrastruktur', 'Lampu jalan depan Blok A mati.', 'Diproses', 'Tukang sedang dipanggil.');

INSERT INTO `iuran` (`user`, `nama`, `bulan`, `nominal`, `status`) VALUES
('peserta', 'Sdr. Andi', '05-2026', 50000, 'Belum Lunas'),
('peserta', 'Sdr. Andi', '04-2026', 50000, 'Lunas');

INSERT INTO `keuangan` (`tgl`, `jenis`, `kategori`, `ket`, `masuk`, `keluar`) VALUES
('01/05/2026', 'Pemasukan', 'Iuran', 'Iuran April Blok B', 1500000, 0),
('05/05/2026', 'Pengeluaran', 'Keamanan', 'Gaji Satpam', 0, 1000000);

INSERT INTO `surat` (`tgl`, `user`, `nama`, `jenis`, `ket`, `status`) VALUES
('24/05/2026', 'peserta', 'Sdr. Andi', 'Surat Pengantar KTP', 'Perpanjang', 'Selesai');
