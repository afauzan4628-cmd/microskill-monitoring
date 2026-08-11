-- =========================================================
-- Database: db_microskill
-- Sistem Monitoring Penyelesaian Microskill Peserta Digital Talent
-- =========================================================

CREATE DATABASE IF NOT EXISTS db_microskill
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE db_microskill;

-- ---------------------------------------------------------
-- Tabel master: seluruh data peserta (sheet PENDAFTAR)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS tb_pendaftar;
CREATE TABLE tb_pendaftar (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    id_batch              VARCHAR(50)  DEFAULT NULL,
    nama_lengkap          VARCHAR(150) DEFAULT NULL,
    nip                   VARCHAR(50)  DEFAULT NULL,
    jenis_kelamin         VARCHAR(20)  DEFAULT NULL,
    usia                  VARCHAR(10)  DEFAULT NULL,
    jenjang_pendidikan    VARCHAR(100) DEFAULT NULL,
    provinsi              VARCHAR(100) DEFAULT NULL,
    kota_kabupaten        VARCHAR(100) DEFAULT NULL,
    kecamatan             VARCHAR(100) DEFAULT NULL,
    kelurahan             VARCHAR(100) DEFAULT NULL,
    pekerjaan             VARCHAR(150) DEFAULT NULL,
    instansi_pemerintahan VARCHAR(200) DEFAULT NULL,
    upt                   VARCHAR(150) DEFAULT NULL,
    jabatan               VARCHAR(150) DEFAULT NULL,
    pangkat_golongan      VARCHAR(100) DEFAULT NULL,
    email_user            VARCHAR(150) DEFAULT NULL,
    batch_import          VARCHAR(50)  DEFAULT NULL,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pendaftar_email (email_user)
) ENGINE=InnoDB;
-- Catatan: UNIQUE KEY di email_user dipakai import_monitoring untuk
-- upsert (INSERT ... ON DUPLICATE KEY UPDATE), supaya file revisi dari
-- mentor yang berisi peserta lama tidak membuat data dobel.
-- Baris dengan email kosong disimpan sebagai NULL (bukan ''), karena
-- MySQL menganggap banyak NULL tetap unik, tapi banyak '' dianggap sama.

-- ---------------------------------------------------------
-- Tabel: peserta yang sudah menyelesaikan Microskill (sheet RESPONSES)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS tb_responses;
CREATE TABLE tb_responses (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    submit_form         DATETIME     DEFAULT NULL,
    nama_peserta        VARCHAR(150) DEFAULT NULL,
    email_peserta       VARCHAR(150) DEFAULT NULL,
    asal_instansi       VARCHAR(200) DEFAULT NULL,
    tema_microskill     VARCHAR(200) DEFAULT NULL,
    tanggal_penyelesaian DATE        DEFAULT NULL,
    keterangan          TEXT         DEFAULT NULL,
    sertifikat          VARCHAR(255) DEFAULT NULL,
    batch_import        VARCHAR(50)  DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_responses_email_tema (email_peserta, tema_microskill)
) ENGINE=InnoDB;
-- Kunci unik gabungan email + tema: satu peserta boleh punya beberapa
-- baris response (tema microskill berbeda), tapi kombinasi email+tema
-- yang sama akan di-update, bukan di-insert ulang.

-- ---------------------------------------------------------
-- Tabel log import (buat riwayat, opsional tapi berguna)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS tb_import_log;
CREATE TABLE tb_import_log (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    batch_import   VARCHAR(50) DEFAULT NULL,
    nama_file      VARCHAR(255) DEFAULT NULL,
    total_pendaftar INT DEFAULT 0,
    total_responses INT DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel: akun login (admin / operator) untuk login.php & register.php
-- CATATAN: sengaja pakai CREATE TABLE IF NOT EXISTS (bukan DROP+CREATE
-- seperti tabel di atas), supaya kalau schema.sql ini di-import ulang,
-- akun yang sudah terdaftar TIDAK ikut terhapus.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nama          VARCHAR(100) NOT NULL,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    role          ENUM('admin','operator','user') NOT NULL DEFAULT 'user',
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
