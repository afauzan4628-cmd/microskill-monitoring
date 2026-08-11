<?php
require_once 'config.php';

$dbConnected = false;
$totalPendaftar = 0;
$totalResponses = 0;
$sudah = 0;
$belum = 0;
$progress = 0;
$lastImportName = '-';
$lastImportDate = '-';

try {
    if (file_exists(__DIR__ . '/config/database.php')) {
        @include_once __DIR__ . '/config/database.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            $dbConnected = true;

            $stmtP = $pdo->query("SELECT COUNT(*) FROM tb_pendaftar");
            $totalPendaftar = (int) $stmtP->fetchColumn();

            $stmtR = $pdo->query("SELECT COUNT(*) FROM tb_responses");
            $totalResponses = (int) $stmtR->fetchColumn();

            $stmtS = $pdo->query("
                SELECT COUNT(DISTINCT p.id)
                FROM tb_pendaftar p
                INNER JOIN tb_responses r ON LOWER(TRIM(p.email_user)) = LOWER(TRIM(r.email_peserta))
            ");
            $sudah = (int) $stmtS->fetchColumn();

            $belum = max(0, $totalPendaftar - $sudah);
            $progress = $totalPendaftar > 0 ? round(($sudah / $totalPendaftar) * 100, 1) : 0;

            $stmtL = $pdo->query("SELECT * FROM tb_import_log ORDER BY created_at DESC LIMIT 1");
            $lastImport = $stmtL->fetch();
            if ($lastImport) {
                $lastImportName = $lastImport['nama_file'] ?? 'File Excel';
                $lastImportDate = isset($lastImport['created_at']) ? date('d M Y, H:i', strtotime($lastImport['created_at'])) : '-';
            }
        }
    }
} catch (Exception $e) {

    $dbConnected = false;
}

$displayTotalPendaftar = $dbConnected && $totalPendaftar > 0 ? $totalPendaftar : 1250;
$displayTotalResponses = $dbConnected && $totalResponses > 0 ? $totalResponses : 980;
$displaySudah = $dbConnected && $sudah > 0 ? $sudah : 975;
$displayBelum = $dbConnected && $totalPendaftar > 0 ? $belum : 275;
$displayProgress = $dbConnected && $totalPendaftar > 0 ? $progress : 78.0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MicroSkill Monitoring - Sistem Monitoring Penyelesaian Peserta Digital Talent</title>
  <meta name="description" content="Platform modern untuk mentor memantau penyelesaian microskill peserta Digital Talent secara otomatis, akurat, dan real-time.">

  <!-- CSS Utama Landing Page -->
  <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>

  <!-- Header / Navigation Bar -->
  <header class="navbar" id="navbar">
    <div class="container nav-container">
      <a href="index.php" class="brand-logo">
        <div class="brand-logo-icon">MS</div>
        <div class="brand-logo-text">
          MicroSkill <span>Monitoring Platform</span>
        </div>
      </a>

      <nav>
        <ul class="nav-links" id="navLinks">
          <li><a href="#fitur">Fitur Unggulan</a></li>
          <li><a href="#cara-kerja">Cara Kerja</a></li>
          <li><a href="#demo">Preview Live</a></li>
          <li><a href="#faq">FAQ</a></li>
        </ul>
      </nav>

      <div class="nav-actions">
        <?php if (isLoggedIn()): ?>
          <div class="nav-user-chip">
            <span class="nav-user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></span>
            <span class="nav-user-name"><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <a href="dashboard.php" class="btn-nav btn-primary-glow">
            <span>Buka Dashboard</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
          </a>
          <a href="logout.php" class="btn-nav btn-outline-dark">Logout</a>
        <?php else: ?>
          <a href="login.php">Login</a>
          <a href="register.php" class="btn-nav btn-secondary-glow">Register</a>
        <?php endif; ?>
        <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle Menu">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="hero-glow-1"></div>
    <div class="hero-glow-2"></div>

    <div class="container hero-grid">
      <div class="hero-content">
        <div class="hero-badge">
          <span class="badge-dot"></span>
          <span>Sistem Monitoring Peserta Digital Talent #1</span>
        </div>

        <h1 class="hero-title">
          Pantau Progress Microskill <span class="text-gradient">Lebih Cepat & Akurat</span>
        </h1>

        <p class="hero-description">
          Tinggalkan pemantauan manual via Excel. Cocokkan email peserta secara otomatis, lacak status penyelesaian secara real-time, dan hasilkan laporan PDF siap cetak dalam hitungan detik.
        </p>

        <div class="hero-buttons">
          <a href="dashboard.php" class="btn-nav btn-primary-glow btn-hero-lg">
            <span>Masuk ke Dashboard</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
          </a>
          <a href="import_monitoring.php" class="btn-nav btn-outline-dark btn-hero-lg">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            <span>Import Excel Data</span>
          </a>
        </div>

        <div class="hero-features-strip">
          <div class="strip-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span>Auto-Matching Email</span>
          </div>
          <div class="strip-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span>Export Laporan PDF</span>
          </div>
          <div class="strip-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span>100% PHP Native & PDO</span>
          </div>
        </div>
      </div>

      <!-- Hero Visual Mockup -->
      <div class="hero-visual">
        <!-- Floating Glass Card 1 -->
        <div class="float-card float-card-1">
          <div class="float-icon emerald">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
          </div>
          <div class="float-info">
            <div>Peserta Selesai</div>
            <div><?= number_format($displaySudah) ?> Peserta</div>
          </div>
        </div>

        <!-- Floating Glass Card 2 -->
        <div class="float-card float-card-2">
          <div class="float-icon cyan">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
          </div>
          <div class="float-info">
            <div>Completion Rate</div>
            <div><?= $displayProgress ?>% Achieved</div>
          </div>
        </div>

        <div class="mockup-wrapper">
          <div class="mockup-header">
            <div class="mockup-dots">
              <span class="mockup-dot dot-red"></span>
              <span class="mockup-dot dot-yellow"></span>
              <span class="mockup-dot dot-green"></span>
            </div>
            <div class="mockup-title">Monitoring System Live Preview</div>
            <div style="width: 40px;"></div>
          </div>

          <div class="mockup-body">
            <div class="mockup-tabs">
              <button class="mock-tab active" onclick="switchMockTab('stats')">Ringkasan Stat</button>
              <button class="mock-tab" onclick="switchMockTab('table')">Preview Peserta</button>
            </div>

            <div id="tabStats">
              <div class="mockup-stats-mini">
                <div class="mini-stat">
                  <div class="mini-stat-label">Total Pendaftar</div>
                  <div class="mini-stat-val"><?= number_format($displayTotalPendaftar) ?></div>
                </div>
                <div class="mini-stat">
                  <div class="mini-stat-label">Response Masuk</div>
                  <div class="mini-stat-val"><?= number_format($displayTotalResponses) ?></div>
                </div>
                <div class="mini-stat">
                  <div class="mini-stat-label">Status Database</div>
                  <div class="mini-stat-val" style="font-size: 13px; color: <?= $dbConnected ? '#10b981' : '#f59e0b' ?>;">
                    <?= $dbConnected ? '● Online (Connected)' : '⚡ Demo Mode' ?>
                  </div>
                </div>
              </div>

              <div class="mock-progress-container">
                <div class="mock-progress-head">
                  <span>Persentase Kelulusan</span>
                  <span style="font-weight:700; color:var(--primary-light);"><?= $displayProgress ?>%</span>
                </div>
                <div class="mock-bar">
                  <div class="mock-bar-fill" style="width: <?= $displayProgress ?>%;"></div>
                </div>
              </div>
            </div>

            <div id="tabTable" style="display: none;">
              <table class="mock-table">
                <thead>
                  <tr>
                    <th>Nama Peserta</th>
                    <th>Email</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Budi Santoso</td>
                    <td>budi@gmail.com</td>
                    <td><span class="status-tag sudah">Sudah Selesai</span></td>
                  </tr>
                  <tr>
                    <td>Siti Rahmawati</td>
                    <td>siti.rahma@yahoo.com</td>
                    <td><span class="status-tag sudah">Sudah Selesai</span></td>
                  </tr>
                  <tr>
                    <td>Ahmad Fauzi</td>
                    <td>ahmad.fauzi@gmail.com</td>
                    <td><span class="status-tag belum">Belum Selesai</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats Counter Bar -->
  <section class="stats-section">
    <div class="container">
      <div class="stats-card-wrapper">
        <div class="stats-grid">
          <div class="stat-item">
            <div class="stat-icon-bg purple">
              👥
            </div>
            <div>
              <div class="stat-num"><?= number_format($displayTotalPendaftar) ?></div>
              <div class="stat-lbl">Total Peserta Pendaftar</div>
            </div>
          </div>

          <div class="stat-item">
            <div class="stat-icon-bg cyan">
              📥
            </div>
            <div>
              <div class="stat-num"><?= number_format($displayTotalResponses) ?></div>
              <div class="stat-lbl">Total Responses Masuk</div>
            </div>
          </div>

          <div class="stat-item">
            <div class="stat-icon-bg emerald">
              ✅
            </div>
            <div>
              <div class="stat-num"><?= number_format($displaySudah) ?></div>
              <div class="stat-lbl">Peserta Lulus / Selesai</div>
            </div>
          </div>

          <div class="stat-item">
            <div class="stat-icon-bg amber">
              📊
            </div>
            <div>
              <div class="stat-num"><?= $displayProgress ?>%</div>
              <div class="stat-lbl">Rasio Penyelesaian</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Fitur Unggulan -->
  <section class="features-section" id="fitur">
    <div class="container">
      <div class="section-header">
        <span class="section-tag">Fitur Unggulan</span>
        <h2 class="section-title">Solusi Terpadu untuk Monitoring Peserta</h2>
        <p class="section-subtitle">Dirancang khusus untuk memudahkan mentor Digital Talent dalam mengolah ribuan data microskill tanpa repot.</p>
      </div>

      <div class="features-grid">
        <!-- Feature 1 -->
        <div class="feature-card">
          <div class="feature-icon-wrapper">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
          </div>
          <h3 class="feature-title">Import Excel Otomatis</h3>
          <p class="feature-desc">Mendukung import langsung file Excel (.xlsx / .xls) dari Google Forms maupun LMS dengan PhpSpreadsheet engine.</p>
        </div>

        <!-- Feature 2 -->
        <div class="feature-card">
          <div class="feature-icon-wrapper">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
          </div>
          <h3 class="feature-title">Pencocokan Email Presisi</h3>
          <p class="feature-desc">Sistem melakukan pencocokan email secara otomatis (LEFT JOIN) untuk mengidentifikasi siapa yang sudah dan belum mengerjakan.</p>
        </div>

        <!-- Feature 3 -->
        <div class="feature-card">
          <div class="feature-icon-wrapper">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
          </div>
          <h3 class="feature-title">Export Laporan PDF Instant</h3>
          <p class="feature-desc">Cetak laporan rekapitulasi lengkap berformat PDF resmi menggunakan Dompdf engine hanya dengan sekali klik.</p>
        </div>

        <!-- Feature 4 -->
        <div class="feature-card">
          <div class="feature-icon-wrapper">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
          </div>
          <h3 class="feature-title">Filter & Pagination Cepat</h3>
          <p class="feature-desc">Cari peserta berdasarkan nama, email, atau status 'Sudah' vs 'Belum' dengan opsi pagination data yang sangat cepat.</p>
        </div>

        <!-- Feature 5 -->
        <div class="feature-card">
          <div class="feature-icon-wrapper">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
          </div>
          <h3 class="feature-title">Dashboard Visual Ringkas</h3>
          <p class="feature-desc">Tampilan persentase progress visual, statistik kartu berwarna, dan riwayat log import terbaru secara transparan.</p>
        </div>

        <!-- Feature 6 -->
        <div class="feature-card">
          <div class="feature-icon-wrapper">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          </div>
          <h3 class="feature-title">Aman & Terpusat</h3>
          <p class="feature-desc">Data tersimpan aman dalam database MySQL PDO, menghindari risiko kehilangan data atau salah rumus di spreadsheet manual.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Cara Kerja Section -->
  <section class="workflow-section" id="cara-kerja">
    <div class="container">
      <div class="section-header">
        <span class="section-tag">Alur Kerja</span>
        <h2 class="section-title">3 Langkah Mudah Menggunakan Sistem</h2>
        <p class="section-subtitle">Proses monitoring yang ringkas dan bebas dari kendala manual.</p>
      </div>

      <div class="workflow-steps">
        <!-- Step 1 -->
        <div class="step-card">
          <span class="step-number">Langkah 01</span>
          <h3 class="step-title">Upload File Excel</h3>
          <p class="step-desc">Upload file Excel daftar pendaftar dan file Excel hasil response penugasan ke dalam menu Import Data.</p>
        </div>

        <!-- Step 2 -->
        <div class="step-card">
          <span class="step-number">Langkah 02</span>
          <h3 class="step-title">Validasi & Preview</h3>
          <p class="step-desc">Sistem memverifikasi nama sheet dan mencocokkan email peserta secara otomatis dengan algoritma PDO JOIN.</p>
        </div>

        <!-- Step 3 -->
        <div class="step-card">
          <span class="step-number">Langkah 03</span>
          <h3 class="step-title">Pantau & Cetak PDF</h3>
          <p class="step-desc">Lihat statistik penyelesaian di Dashboard dan download laporan rekap PDF untuk keperluan dokumentasi mentor.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Interactive Live Demo Section -->
  <section class="demo-section" id="demo">
    <div class="container">
      <div class="section-header" style="color: white;">
        <span class="section-tag" style="background: rgba(99,102,241,0.2); color: var(--primary-light);">Interactive Demo</span>
        <h2 class="section-title" style="color: white;">Uji Coba Pencarian & Status Demo</h2>
        <p class="section-subtitle" style="color: var(--text-dark-muted);">Simulasikan bagaimana sistem memfilter data pendaftar dan status kelulusannya.</p>
      </div>

      <div class="demo-box">
        <div class="demo-controls">
          <input type="text" id="demoSearchInput" class="demo-input" placeholder="Cari nama atau email (contoh: Budi, siti@yahoo.com)...">
          <button class="demo-filter-btn active" onclick="filterDemoStatus('all', this)">Semua Data</button>
          <button class="demo-filter-btn" onclick="filterDemoStatus('sudah', this)">Sudah Selesai</button>
          <button class="demo-filter-btn" onclick="filterDemoStatus('belum', this)">Belum Selesai</button>
        </div>

        <div class="demo-table-wrapper">
          <table class="demo-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama Peserta</th>
                <th>Email Registered</th>
                <th>Status Microskill</th>
                <th>Aksi Fast Check</th>
              </tr>
            </thead>
            <tbody id="demoTableBody">
              <!-- Render via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="faq-section" id="faq">
    <div class="container">
      <div class="section-header">
        <span class="section-tag">FAQ</span>
        <h2 class="section-title">Pertanyaan Sering Diajukan</h2>
        <p class="section-subtitle">Jawaban cepat atas pertanyaan seputar pemakaian sistem monitoring ini.</p>
      </div>

      <div class="faq-container">
        <!-- FAQ 1 -->
        <div class="faq-item active">
          <div class="faq-question" onclick="toggleFaq(this)">
            <span>Bagaimana sistem tahu peserta sudah menyelesaikan Microskill?</span>
            <svg class="faq-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </div>
          <div class="faq-answer">
            Sistem membandingkan kolom email pendaftar (`tb_pendaftar`) dengan data email peserta di file response penugasan (`tb_responses`) menggunakan query matching otomatis.
          </div>
        </div>

        <!-- FAQ 2 -->
        <div class="faq-item">
          <div class="faq-question" onclick="toggleFaq(this)">
            <span>Format file Excel seperti apa yang didukung?</span>
            <svg class="faq-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </div>
          <div class="faq-answer">
            Sistem mendukung format `.xlsx` dan `.xls`. Anda dapat mengunggah file export resmi dari Google Sheets maupun Google Forms.
          </div>
        </div>

        <!-- FAQ 3 -->
        <div class="faq-item">
          <div class="faq-question" onclick="toggleFaq(this)">
            <span>Apakah laporan PDF bisa langsung di-download dan dicetak?</span>
            <svg class="faq-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </div>
          <div class="faq-answer">
            Ya! Melalui menu Laporan, mentor dapat menyaring data berdasarkan status dan langsung men-generate file PDF berformat rapi menggunakan Dompdf.
          </div>
        </div>

        <!-- FAQ 4 -->
        <div class="faq-item">
          <div class="faq-question" onclick="toggleFaq(this)">
            <span>Apakah aplikasi ini memerlukan koneksi internet khusus?</span>
            <svg class="faq-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </div>
          <div class="faq-answer">
            Tidak. Aplikasi berjalan secara lokal di server XAMPP (Apache & MySQL) sehingga dapat diakses secara cepat melalui localhost.
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Banner Section -->
  <section class="cta-section">
    <div class="container">
      <div class="cta-banner">
        <h2 class="cta-title">Siap Memulai Monitoring Peserta?</h2>
        <p class="cta-desc">Kelola data pendaftar dan respons microskill secara otomatis dan lebih efisien sekarang juga.</p>

        <div class="cta-buttons">
          <a href="dashboard.php" class="btn-white">Masuk ke Dashboard Utama</a>
          <a href="import_monitoring.php" class="btn-glass">Import File Excel Baru</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <a href="index.php" class="brand-logo">
            <div class="brand-logo-icon">MS</div>
            <div class="brand-logo-text">
              MicroSkill <span>Monitoring Platform</span>
            </div>
          </a>
          <p class="footer-brand-desc">
            Sistem monitoring penyelesaian microskill bagi mentor peserta Digital Talent. Menggantikan proses pencocokan manual dengan otomatisasi handal.
          </p>
        </div>

        <div>
          <h4 class="footer-col-title">Navigasi Utama</h4>
          <ul class="footer-links">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="import_monitoring.php">⬆️ Import Data</a></li>
            <li><a href="monitoring.php">🧭 Monitoring Status</a></li>
            <li><a href="laporan.php">📄 Laporan & PDF</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-col-title">Tautan landing</h4>
          <ul class="footer-links">
            <li><a href="#fitur">Fitur Unggulan</a></li>
            <li><a href="#cara-kerja">Cara Kerja</a></li>
            <li><a href="#demo">Preview Live</a></li>
            <li><a href="#faq">FAQ System</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-col-title">Teknologi Stack</h4>
          <div style="display:flex; flex-direction:column; gap:8px;">
            <span class="tech-pill">PHP Native + PDO</span>
            <span class="tech-pill">MySQL / MariaDB</span>
            <span class="tech-pill">PhpSpreadsheet</span>
            <span class="tech-pill">Dompdf PDF Generator</span>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <div>
          &copy; <?= date('Y') ?> MicroSkill Monitoring Platform - Digital Talent. All rights reserved.
        </div>
        <div class="badge-tech">
          <span class="tech-pill">Modern Web Standard</span>
          <span class="tech-pill">Responsive Design</span>
        </div>
      </div>
    </div>
  </footer>
  <!-- Script Interaktif Landing Page -->
  <script>
    // Header Navbar Scroll Effect
    window.addEventListener('scroll', function() {
      const navbar = document.getElementById('navbar');
      if (window.scrollY > 40) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });

    // Mobile Menu Toggle
    document.getElementById('mobileToggle').addEventListener('click', function() {
      document.getElementById('navLinks').classList.toggle('mobile-open');
    });

    // Hero Mockup Tab Switcher
    function switchMockTab(tab) {
      document.querySelectorAll('.mock-tab').forEach(btn => btn.classList.remove('active'));
      if (tab === 'stats') {
        document.getElementById('tabStats').style.display = 'block';
        document.getElementById('tabTable').style.display = 'none';
        event.target.classList.add('active');
      } else {
        document.getElementById('tabStats').style.display = 'none';
        document.getElementById('tabTable').style.display = 'block';
        event.target.classList.add('active');
      }
    }

    // FAQ Accordion Toggle
    function toggleFaq(element) {
      const item = element.parentElement;
      const isActive = item.classList.contains('active');

      document.querySelectorAll('.faq-item').forEach(el => el.classList.remove('active'));

      if (!isActive) {
        item.classList.add('active');
      }
    }

    // Interactive Demo Table Data
    const demoData = [
      { no: 1, nama: 'Budi Santoso', email: 'budi.santoso@gmail.com', status: 'sudah' },
      { no: 2, nama: 'Siti Rahmawati', email: 'siti.rahma@yahoo.com', status: 'sudah' },
      { no: 3, nama: 'Ahmad Fauzi', email: 'ahmad.fauzi@hotmail.com', status: 'belum' },
      { no: 4, nama: 'Dewi Lestari', email: 'dewi.lestari@gmail.com', status: 'sudah' },
      { no: 5, nama: 'Rian Hidayat', email: 'rian.hidayat@outlook.com', status: 'belum' }
    ];

    let currentFilter = 'all';

    function renderDemoTable() {
      const tbody = document.getElementById('demoTableBody');
      const searchQuery = document.getElementById('demoSearchInput').value.toLowerCase();

      const filtered = demoData.filter(item => {
        const matchesFilter = currentFilter === 'all' || item.status === currentFilter;
        const matchesSearch = item.nama.toLowerCase().includes(searchQuery) || item.email.toLowerCase().includes(searchQuery);
        return matchesFilter && matchesSearch;
      });

      if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color: var(--text-dark-muted); padding:20px;">Tidak ada data yang sesuai pencarian.</td></tr>`;
        return;
      }

      tbody.innerHTML = filtered.map(item => `
        <tr>
          <td>${item.no}</td>
          <td style="font-weight:600;">${item.nama}</td>
          <td style="color:var(--text-dark-muted);">${item.email}</td>
          <td>
            <span class="status-tag ${item.status}">
              ${item.status === 'sudah' ? '✓ Sudah Selesai' : '✕ Belum Selesai'}
            </span>
          </td>
          <td>
            <a href="dashboard.php" style="color:var(--primary-light); text-decoration:none; font-size:13px; font-weight:600;">Detail Dashboard →</a>
          </td>
        </tr>
      `).join('');
    }

    function filterDemoStatus(status, btn) {
      currentFilter = status;
      document.querySelectorAll('.demo-filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      renderDemoTable();
    }

    document.getElementById('demoSearchInput').addEventListener('input', renderDemoTable);

    // Initial render
    renderDemoTable();
  </script>
</body>
</html>
