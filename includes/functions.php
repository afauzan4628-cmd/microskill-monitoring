<?php
/**
 * Kumpulan fungsi bantu yang dipakai di banyak halaman
 */

function e($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}

function formatTanggalIndo($tanggal) {
    if (empty($tanggal) || $tanggal === '0000-00-00') return '-';
    $bulan = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $ts = strtotime($tanggal);
    if (!$ts) return '-';
    return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/**
 * Bikin komponen pagination sederhana (return HTML string)
 */
function renderPagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    $html = '<div class="pagination">';

    $prev = max(1, $currentPage - 1);
    $next = min($totalPages, $currentPage + 1);

    $html .= '<a href="' . $baseUrl . '&page=1" class="' . ($currentPage==1?'disabled':'') . '">&laquo;&laquo;</a>';
    $html .= '<a href="' . $baseUrl . '&page=' . $prev . '" class="' . ($currentPage==1?'disabled':'') . '">&laquo;</a>';

    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);

    for ($i = $start; $i <= $end; $i++) {
        $html .= '<a href="' . $baseUrl . '&page=' . $i . '" class="' . ($i==$currentPage?'active':'') . '">' . $i . '</a>';
    }

    $html .= '<a href="' . $baseUrl . '&page=' . $next . '" class="' . ($currentPage==$totalPages?'disabled':'') . '">&raquo;</a>';
    $html .= '<a href="' . $baseUrl . '&page=' . $totalPages . '" class="' . ($currentPage==$totalPages?'disabled':'') . '">&raquo;&raquo;</a>';

    $html .= '</div>';
    return $html;
}

function generateBatchId() {
    return 'BATCH-' . date('YmdHis');
}

/**
 * Ubah teks jadi nama file yang aman dipakai di header Content-Disposition
 * maupun sistem file (buang karakter ilegal, batasi panjang, buang ekstensi
 * .pdf kalau user sudah mengetiknya sendiri supaya tidak dobel).
 */
function sanitizeFileName($name, $fallback = 'Laporan') {
    $name = trim((string) $name);
    if ($name === '') $name = $fallback;

    // buang ekstensi .pdf kalau user sudah menuliskannya sendiri
    $name = preg_replace('/\.pdf$/i', '', $name);

    // buang karakter yang tidak aman untuk nama file (Windows & Unix)
    $name = preg_replace('/[\\\\\/:*?"<>|]+/', '-', $name);

    // buang whitespace berlebih di awal/akhir & rapikan spasi ganda
    $name = trim(preg_replace('/\s+/', ' ', $name));

    // batasi panjang supaya tidak kepanjangan untuk nama file
    $name = mb_substr($name, 0, 100);

    if ($name === '' || $name === '.' || $name === '..') $name = $fallback;

    return $name;
}

/**
 * Normalisasi nama header supaya perbandingan tidak peduli spasi ganda,
 * huruf besar/kecil, atau spasi di sekitar tanda '/'.
 * "Kota / Kabupaten" dan "kota/kabupaten" akan dianggap sama.
 */
function normalizeHeader($h) {
    $h = (string) $h;
    $h = preg_replace('/\s+/', '', $h); // buang semua whitespace
    return mb_strtoupper($h, 'UTF-8');
}

/**
 * Baca baris header (baris 1) sebuah sheet dan cocokkan dengan daftar kolom
 * yang dibutuhkan (bisa punya beberapa alias nama). Return array
 * [fieldKey => nomor_kolom (1-based)].
 *
 * $expectedColumns format:
 *   'nip' => ['NIP']                          // 1 nama valid
 *   'kota_kabupaten' => ['KOTA/KABUPATEN']     // dst
 *
 * Alias boleh lebih dari satu string dalam array kalau mentor kadang
 * menulis header itu dengan variasi nama.
 *
 * @throws Exception kalau ada kolom wajib yang tidak ditemukan di header.
 */
function mapHeaderColumns($sheet, array $expectedColumns, $sheetLabel) {
    $highestCol = $sheet->getHighestColumn();
    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

    // Baca semua header di baris 1
    $headerFound = []; // normalized_header => nomor_kolom
    for ($c = 1; $c <= $highestColIndex; $c++) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
        $val = $sheet->getCell($colLetter . '1')->getValue();
        $val = trim((string) $val);
        if ($val === '') continue;
        $headerFound[normalizeHeader($val)] = $c;
    }

    $colMap = [];
    $missing = [];

    foreach ($expectedColumns as $fieldKey => $aliases) {
        $found = null;
        foreach ($aliases as $alias) {
            $normAlias = normalizeHeader($alias);
            if (isset($headerFound[$normAlias])) {
                $found = $headerFound[$normAlias];
                break;
            }
        }
        if ($found === null) {
            $missing[] = $aliases[0]; // tampilkan nama alias utama di pesan error
        } else {
            $colMap[$fieldKey] = $found;
        }
    }

    if (!empty($missing)) {
        throw new Exception(
            "Sheet $sheetLabel: kolom wajib tidak ditemukan di baris header (baris 1): " .
            implode(', ', $missing) .
            '. Header yang terbaca di file: ' .
            implode(', ', array_keys($headerFound)) .
            '. Pastikan nama kolom sesuai ketentuan (urutan boleh berbeda, tapi nama header harus ada).'
        );
    }

    return $colMap;
}

/**
 * Kolom PENDAFTAR yang dibutuhkan sistem, key = nama field internal,
 * value = daftar alias nama header yang diterima (case/spasi-insensitive).
 */
function pendaftarExpectedColumns() {
    return [
        'id_batch'              => ['ID_BATCH', 'ID BATCH'],
        'nama_lengkap'          => ['Nama Lengkap'],
        'nip'                   => ['NIP'],
        'jenis_kelamin'         => ['Jenis Kelamin'],
        'usia'                  => ['Usia'],
        'jenjang_pendidikan'    => ['Jenjang Pendidikan'],
        'provinsi'              => ['Provinsi'],
        'kota_kabupaten'        => ['Kota/Kabupaten', 'Kota Kabupaten'],
        'kecamatan'             => ['Kecamatan'],
        'kelurahan'             => ['Kelurahan'],
        'pekerjaan'             => ['Pekerjaan'],
        'instansi_pemerintahan' => ['Instansi Pemerintahan'],
        'upt'                   => ['UPT'],
        'jabatan'               => ['Jabatan'],
        'pangkat_golongan'      => ['Pangkat Golongan', 'Pangkat/Golongan'],
        'email_user'            => ['Email User'],
    ];
}

/**
 * Kolom RESPONSES yang dibutuhkan sistem.
 */
function responsesExpectedColumns() {
    return [
        'submit_form'          => ['Submit Form'],
        'nama_peserta'         => ['Nama Peserta'],
        'email_peserta'        => ['Email Peserta'],
        'asal_instansi'        => ['Asal Instansi/OPD', 'Asal Instansi', 'Asal Instansi / OPD'],
        'tema_microskill'      => ['Tema Micro Skill', 'Tema Microskill'],
        'tanggal_penyelesaian' => ['Tanggal Penyelesaian'],
        'keterangan'           => ['Keterangan'],
        'sertifikat'           => ['Sertifikat'],
    ];
}

/**
 * Ubah string kosong/whitespace jadi NULL. Dipakai khusus untuk kolom email
 * supaya UNIQUE KEY di database tidak menganggap banyak baris ber-email
 * kosong sebagai "duplikat" satu sama lain (MySQL: NULL boleh berulang,
 * string kosong '' tidak).
 */
function emptyToNull($val) {
    $val = trim((string) $val);
    return $val === '' ? null : $val;
}
