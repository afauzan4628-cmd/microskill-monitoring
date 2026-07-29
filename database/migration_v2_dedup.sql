-- =========================================================
-- Migrasi v2: tambah UNIQUE KEY untuk deteksi duplikat saat import
-- Jalankan ini SATU KALI kalau database kamu sudah dibuat dari schema.sql
-- versi lama (belum punya UNIQUE KEY di email_user / email_peserta+tema).
-- Kalau baru mau install dari nol, tidak perlu jalankan file ini --
-- cukup pakai schema.sql yang sudah versi terbaru.
-- =========================================================

USE db_microskill;

-- 1) Bersihkan dulu kalau sudah ada baris duplikat existing (opsional tapi
--    disarankan), supaya ALTER TABLE tidak gagal karena bentrok UNIQUE KEY.
--    Cek dulu manual sebelum jalankan DELETE ini kalau ragu:
--
--    SELECT email_user, COUNT(*) FROM tb_pendaftar
--    WHERE email_user IS NOT NULL AND email_user <> ''
--    GROUP BY email_user HAVING COUNT(*) > 1;
--
--    SELECT email_peserta, tema_microskill, COUNT(*) FROM tb_responses
--    WHERE email_peserta IS NOT NULL AND email_peserta <> ''
--    GROUP BY email_peserta, tema_microskill HAVING COUNT(*) > 1;

-- 2) Ubah string kosong jadi NULL dulu di email_user / email_peserta,
--    supaya tidak dianggap "duplikat" satu sama lain oleh UNIQUE KEY.
UPDATE tb_pendaftar SET email_user = NULL WHERE TRIM(email_user) = '';
UPDATE tb_responses SET email_peserta = NULL WHERE TRIM(email_peserta) = '';

-- 3) Drop index lama (non-unique) kalau masih ada.
ALTER TABLE tb_pendaftar DROP INDEX idx_email_user;
ALTER TABLE tb_responses DROP INDEX idx_email_peserta;

-- 4) Tambah UNIQUE KEY yang baru.
ALTER TABLE tb_pendaftar
  ADD UNIQUE KEY uq_pendaftar_email (email_user);

ALTER TABLE tb_responses
  ADD UNIQUE KEY uq_responses_email_tema (email_peserta, tema_microskill);
