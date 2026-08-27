USE db_microskill;

CREATE TABLE IF NOT EXISTS tb_activity_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT DEFAULT NULL,
    username    VARCHAR(50) DEFAULT NULL,
    aksi        VARCHAR(100) NOT NULL,
    keterangan  VARCHAR(255) DEFAULT NULL,
    ip_address  VARCHAR(45) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tb_settings (
    setting_key   VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO tb_settings (setting_key, setting_value) VALUES
    ('app_name', 'Monitoring Microskill Digital Talent'),
    ('kontak_email', ''),
    ('kontak_wa', ''),
    ('items_per_page', '10');

CREATE TABLE IF NOT EXISTS tb_reminder_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    email_user  VARCHAR(150) NOT NULL,
    nama_lengkap VARCHAR(150) DEFAULT NULL,
    sent_by     VARCHAR(50) DEFAULT NULL,
    status      ENUM('sukses','gagal') NOT NULL DEFAULT 'sukses',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_email (email_user)
) ENGINE=InnoDB;
