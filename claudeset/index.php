<?php
// ============================================================
//  AUDIOLOGY PORTAL — index.php
//  Single-file PHP/PDO occupational health web application
//  v2 — with ICF Referral & Declaration form generation
// ============================================================

session_start();

// ─── DB CREDENTIALS ─────────────────────────────────────────
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_NAME = 'audiology_portal';
$DB_USER = 'root';
$DB_PASS = 'Chhatreshisnotafool_123';

// ─── LOCAL FALLBACK DATASET ──────────────────────────────────
$FALLBACK_DATA = [
    'OP-101' => [
        'op_number'            => 'OP-101',
        'employee_id'          => 'EMP-4471',
        'full_name'            => 'Rajeshwari Subramaniam',
        'age'                  => 38,
        'gender'               => 'Female',
        'department'           => 'Manufacturing — Floor B',
        'designation'          => 'Senior Process Technician',
        'official_email'       => 'r.subramaniam@corpmail.internal',
        'test_id'              => 'AUD-2024-0041',
        'test_date'            => '2024-11-14',
        'examining_audiologist'=> 'Dr. Priya Nair, M.Sc Audiology',
        'clinical_history'     => 'Prolonged noise exposure (>85 dB) reported over 6-year tenure on manufacturing floor. No prior audiological intervention. Mild tinnitus in right ear noted since 2022.',
        'right_ear_air'        => '25 dB HL',
        'right_ear_bone'       => '20 dB HL',
        'right_ear_assessment' => 'Mild Conductive Loss',
        'left_ear_air'         => '15 dB HL',
        'left_ear_bone'        => '10 dB HL',
        'left_ear_assessment'  => 'Normal',
        // Referral extra fields
        'relationship'         => 'Self',
        'emp_number'           => 'EMP-4471',
        'relhs_number'         => '',
        'basic_pay'            => 'Rs. 45,000/-',
        'provisional_diagnosis'=> 'Mild Conductive Hearing Loss',
        'pta_re'               => '25 dB HL',
        'pta_le'               => '15 dB HL',
        'date_of_last_procurement' => 'NIL',
        'recommendation'       => 'Referral for hearing aid evaluation',
    ],
    'OP-102' => [
        'op_number'            => 'OP-102',
        'employee_id'          => 'EMP-5290',
        'full_name'            => 'Aravind Krishnaswamy',
        'age'                  => 45,
        'gender'               => 'Male',
        'department'           => 'Engineering — Acoustic Systems',
        'designation'          => 'Lead Systems Engineer',
        'official_email'       => 'a.krishnaswamy@corpmail.internal',
        'test_id'              => 'AUD-2024-0042',
        'test_date'            => '2024-11-15',
        'examining_audiologist'=> 'Dr. Priya Nair, M.Sc Audiology',
        'clinical_history'     => 'Routine annual screening. History of bilateral hearing loss flagged during 2023 assessment. Wears hearing protection inconsistently. No tinnitus reported.',
        'right_ear_air'        => '40 dB HL',
        'right_ear_bone'       => '38 dB HL',
        'right_ear_assessment' => 'Moderate Sensorineural Trauma',
        'left_ear_air'         => '35 dB HL',
        'left_ear_bone'        => '30 dB HL',
        'left_ear_assessment'  => 'Moderate Sensorineural Trauma',
        // Referral extra fields
        'relationship'         => 'Self',
        'emp_number'           => 'EMP-5290',
        'relhs_number'         => '',
        'basic_pay'            => 'Rs. 62,000/-',
        'provisional_diagnosis'=> 'Moderate Bilateral Sensorineural Hearing Loss',
        'pta_re'               => '40 dB HL',
        'pta_le'               => '35 dB HL',
        'date_of_last_procurement' => 'NIL',
        'recommendation'       => 'Bilateral hearing aid procurement recommended',
    ],
];

// ─── DATABASE CONNECTION ─────────────────────────────────────
$db_live  = false;
$pdo      = null;

try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 3,
    ]);
    $db_live = true;
} catch (PDOException $e) {
    $pdo     = null;
    $db_live = false;
}

// ─── ROUTING ─────────────────────────────────────────────────
$view        = 'login';
$error       = '';
$result      = null;
$form_status = '';

$ADMIN_USER = 'admin';
$ADMIN_PASS = 'OccHealth@2024';

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $view = 'search';
}

// ── POST HANDLING ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ── Login ──
    if ($_POST['action'] === 'login') {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        if ($u === $ADMIN_USER && $p === $ADMIN_PASS) {
            $_SESSION['authenticated'] = true;
            $view = 'search';
        } else {
            $error = 'Invalid credentials. Please verify your username and password.';
            $view  = 'login';
        }
    }

    // ── Logout ──
    if ($_POST['action'] === 'logout') {
        session_destroy();
        header('Location: index.php');
        exit;
    }

    // ── Search ──
    if ($_POST['action'] === 'search' && isset($_SESSION['authenticated'])) {
        $op = strtoupper(trim($_POST['op_number'] ?? ''));

        if ($db_live && $pdo) {
            $sql = "
                SELECT
                    e.op_number, e.employee_id, e.full_name, e.age, e.gender,
                    e.department, e.designation, e.official_email,
                    h.test_id, h.test_date, h.examining_audiologist,
                    h.clinical_history,
                    h.right_ear_air, h.right_ear_bone, h.right_ear_assessment,
                    h.left_ear_air,  h.left_ear_bone,  h.left_ear_assessment,
                    h.relationship, h.emp_number, h.relhs_number, h.basic_pay,
                    h.provisional_diagnosis, h.pta_re, h.pta_le,
                    h.date_of_last_procurement, h.recommendation
                FROM employees e
                LEFT JOIN hearing_tests h ON e.op_number = h.op_number
                WHERE e.op_number = :op
                LIMIT 1
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':op' => $op]);
            $row = $stmt->fetch();
            $result = $row ?: null;
        } else {
            $result = $FALLBACK_DATA[$op] ?? null;
        }

        if ($result) {
            $_SESSION['last_result'] = $result;
            $view = 'dossier';
        } else {
            $error = "No record found for identifier <strong>{$op}</strong>. Verify the OP number and retry.";
            $view  = 'search';
        }
    }

    // ── Clear ──
    if ($_POST['action'] === 'clear' && isset($_SESSION['authenticated'])) {
        $view = 'search';
    }

    // ── Generate Forms (Referral + Declaration DOCX) ──
    if ($_POST['action'] === 'generate_forms' && isset($_SESSION['authenticated'])) {
        $result = $_SESSION['last_result'] ?? null;
        if ($result) {
            $view = 'dossier';

            // Build data payload for node script
            $gender_short = strtoupper(substr($result['gender'] ?? 'M', 0, 1));
            $payload = json_encode([
                'patient_name'          => $result['full_name'] ?? '',
                'patient_age'           => (string)($result['age'] ?? ''),
                'patient_gender'        => $gender_short,
                'op_number'             => $result['op_number'] ?? '',
                'relationship'          => $result['relationship'] ?? 'Self',
                'emp_number'            => $result['emp_number'] ?? ($result['employee_id'] ?? ''),
                'relhs_number'          => $result['relhs_number'] ?? '',
                'basic_pay'             => $result['basic_pay'] ?? 'Rs. /-',
                'provisional_diagnosis' => $result['provisional_diagnosis'] ?? '',
                'pta_re'                => $result['pta_re'] ?? ($result['right_ear_air'] ?? ''),
                'pta_le'                => $result['pta_le'] ?? ($result['left_ear_air'] ?? ''),
                'date_of_last_procurement' => $result['date_of_last_procurement'] ?? 'NIL',
                'recommendation'        => $result['recommendation'] ?? '',
                'date'                  => date('d-m-Y'),
            ]);

            // Sanitize for shell — write to temp file to avoid quoting issues
            $tmpJson = sys_get_temp_dir() . '/aud_payload_' . uniqid() . '.json';
            file_put_contents($tmpJson, $payload);

            $op_safe  = preg_replace('/[^A-Z0-9\-]/', '', strtoupper($result['op_number'] ?? 'OP'));
            $outFile  = sys_get_temp_dir() . '/ICF_Forms_' . $op_safe . '_' . date('Ymd_His') . '.docx';
            $script   = __DIR__ . '/generate_forms.js';

            $cmd = escapeshellcmd('node') . ' ' . escapeshellarg($script)
                 . ' "$(cat ' . escapeshellarg($tmpJson) . ')"'
                 . ' ' . escapeshellarg($outFile)
                 . ' 2>&1';

            // Use proc_open for safer execution
            $descriptorspec = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
            $proc = proc_open(
                'node ' . escapeshellarg($script) . ' ' . escapeshellarg($payload) . ' ' . escapeshellarg($outFile),
                $descriptorspec, $pipes
            );

            if (is_resource($proc)) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
                $exitCode = proc_close($proc);
            }

            @unlink($tmpJson);

            if ($exitCode === 0 && file_exists($outFile)) {
                // Serve download
                $filename = 'ICF_Hearing_Aid_Forms_' . $op_safe . '.docx';
                header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($outFile));
                header('Cache-Control: no-cache');
                readfile($outFile);
                @unlink($outFile);
                exit;
            } else {
                $form_status = 'error';
                $error = 'Form generation failed. Please ensure Node.js and the generate_forms.js script are present on the server.';
            }
        } else {
            $view = 'search';
        }
    }
}

// ─── Re-hydrate result from session when returning to dossier ────
if ($view === 'dossier' && !$result && isset($_SESSION['last_result'])) {
    $result = $_SESSION['last_result'];
}

// ─── HELPERS ────────────────────────────────────────────────
function assessment_badge(string $val): string {
    $v = strtolower($val);
    if (str_contains($v, 'normal')) {
        return '<span class="badge-normal">' . htmlspecialchars($val) . '</span>';
    }
    return '<span class="badge-warn">' . htmlspecialchars($val) . '</span>';
}

function safe(mixed $v): string {
    return htmlspecialchars((string)($v ?? '—'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OccHealth Audiology Portal</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body {
    font-family: 'Inter', system-ui, sans-serif;
    background: #f8fafc;
    color: #1e293b;
    min-height: 100vh;
    margin: 0;
  }
  .status-live {
    display: inline-flex; align-items: center; gap: 6px;
    background: #ecfdf5; color: #065f46;
    border: 1px solid #a7f3d0;
    font-size: 11px; font-weight: 600; letter-spacing: .04em;
    padding: 3px 10px; border-radius: 99px;
  }
  .status-live::before { content:'●'; color:#10b981; }
  .status-fallback {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fffbeb; color: #92400e;
    border: 1px solid #fde68a;
    font-size: 11px; font-weight: 600; letter-spacing: .04em;
    padding: 3px 10px; border-radius: 99px;
  }
  .status-fallback::before { content:'●'; color:#f59e0b; }
  .badge-normal {
    display: inline-block;
    background: #ecfdf5; color: #065f46;
    border: 1px solid #6ee7b7;
    font-size: 12px; font-weight: 600;
    padding: 2px 10px; border-radius: 6px;
  }
  .badge-warn {
    display: inline-block;
    background: #fffbeb; color: #92400e;
    border: 1px solid #fcd34d;
    font-size: 12px; font-weight: 600;
    padding: 2px 10px; border-radius: 6px;
  }
  .login-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(99,102,241,.07), 0 1px 3px rgba(0,0,0,.05);
  }
  .field {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 14px;
    color: #1e293b;
    background: #f8fafc;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
  }
  .field:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
    background: #fff;
  }
  .btn-primary {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 11px 28px;
    font-size: 14px; font-weight: 600;
    cursor: pointer;
    transition: opacity .15s, transform .1s;
    display: inline-flex; align-items: center; gap: 8px;
  }
  .btn-primary:hover { opacity: .9; transform: translateY(-1px); }
  .btn-primary:active { transform: translateY(0); }
  .btn-secondary {
    background: #fff;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 10px 22px;
    font-size: 14px; font-weight: 500;
    cursor: pointer;
    transition: background .15s, border-color .15s;
    display: inline-flex; align-items: center; gap: 8px;
  }
  .btn-secondary:hover { background: #f1f5f9; border-color: #94a3b8; }
  /* ── NEW: Generate Forms button ── */
  .btn-forms {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    font-size: 14px; font-weight: 600;
    cursor: pointer;
    transition: opacity .15s, transform .1s;
    display: inline-flex; align-items: center; gap: 8px;
  }
  .btn-forms:hover { opacity: .9; transform: translateY(-1px); }
  .btn-forms:active { transform: translateY(0); }
  .dossier-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.04);
  }
  .dossier-section-title {
    font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    color: #6366f1; padding: 16px 24px 8px;
    border-bottom: 1px solid #f1f5f9;
    background: #fafbff;
  }
  .kv-row {
    display: grid;
    grid-template-columns: 260px 1fr;
    border-bottom: 1px solid #f1f5f9;
    align-items: start;
  }
  .kv-row:last-child { border-bottom: none; }
  .kv-key {
    font-family: 'JetBrains Mono', monospace;
    font-size: 12.5px; font-weight: 500;
    color: #64748b;
    padding: 14px 20px 14px 24px;
    background: #f8fafc;
    border-right: 1px solid #f1f5f9;
    white-space: nowrap;
    letter-spacing: .01em;
  }
  .kv-val { padding: 14px 24px; }
  .kv-val-text {
    font-size: 14px; font-weight: 500; color: #1e293b;
    margin-bottom: 4px; line-height: 1.5;
  }
  .kv-val-sub {
    font-size: 11px; color: #94a3b8; font-weight: 400; letter-spacing: .02em;
  }
  .site-header {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 32px;
    height: 60px;
    display: flex; align-items: center; justify-content: space-between;
  }
  .logo-mark {
    font-size: 15px; font-weight: 700; color: #1e293b;
    display: flex; align-items: center; gap: 10px;
  }
  .logo-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 16px;
  }
  .search-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(99,102,241,.06);
  }
  /* ── Forms generation banner ── */
  .forms-banner {
    background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 16px 22px;
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    margin-bottom: 20px;
  }
  .forms-banner-info { display: flex; align-items: center; gap: 12px; }
  .forms-banner-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .toast-error {
    background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;
    padding: 12px 16px; margin-bottom: 16px;
    display: flex; gap: 10px; align-items: flex-start;
  }
  @media print {
    .no-print { display: none !important; }
    body { background: #fff; }
    .site-header { display: none; }
    .dossier-card { box-shadow: none; border: 1px solid #ccc; }
    .forms-banner { display: none; }
  }
  .fade-in { animation: fadeIn .3s ease; }
  @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }
</style>
</head>
<body>

<?php if ($view !== 'login'): ?>
<header class="site-header no-print">
  <div class="logo-mark">
    <div class="logo-icon">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
        <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/>
        <path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
      </svg>
    </div>
    OccHealth <span style="font-weight:300;color:#94a3b8;margin:0 4px;">/</span> Audiology Portal
  </div>
  <div style="display:flex;align-items:center;gap:16px;">
    <?php if ($db_live): ?>
      <span class="status-live">SQL_LIVE</span>
    <?php else: ?>
      <span class="status-fallback">LOCAL_FALLBACK</span>
    <?php endif; ?>
    <form method="POST" style="margin:0;">
      <input type="hidden" name="action" value="logout">
      <button type="submit" class="btn-secondary" style="padding:7px 16px;font-size:13px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Sign Out
      </button>
    </form>
  </div>
</header>
<?php endif; ?>


<?php if ($view === 'login'): ?>
<!-- ═══ PHASE 1 — LOGIN ════════════════════════════════════ -->
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:linear-gradient(135deg,#f0f4ff 0%,#f8fafc 60%);">
  <div class="login-card fade-in" style="width:100%;max-width:420px;padding:40px;">
    <div style="text-align:center;margin-bottom:36px;">
      <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
          <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/>
          <path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
        </svg>
      </div>
      <h1 style="font-size:20px;font-weight:700;color:#1e293b;margin:0 0 6px;">Audiology Portal</h1>
      <p style="font-size:13px;color:#94a3b8;margin:0;">Occupational Health Management System</p>
    </div>
    <?php if ($error): ?>
    <div class="toast-error">
      <svg style="flex-shrink:0;margin-top:1px;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span style="font-size:13px;color:#b91c1c;"><?= $error ?></span>
    </div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="action" value="login">
      <div style="margin-bottom:16px;">
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;letter-spacing:.03em;">USERNAME</label>
        <input type="text" name="username" class="field" placeholder="admin" autocomplete="username" required>
      </div>
      <div style="margin-bottom:28px;">
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;letter-spacing:.03em;">PASSWORD</label>
        <input type="password" name="password" class="field" placeholder="••••••••••" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Authenticate &amp; Enter
      </button>
    </form>
    <div style="margin-top:24px;padding-top:20px;border-top:1px solid #f1f5f9;display:flex;justify-content:center;">
      <?php if ($db_live): ?><span class="status-live">SQL_LIVE</span><?php else: ?><span class="status-fallback">LOCAL_FALLBACK</span><?php endif; ?>
    </div>
    <p style="text-align:center;font-size:11px;color:#cbd5e1;margin-top:16px;">Authorized personnel only &nbsp;·&nbsp; All access is logged</p>
  </div>
</div>


<?php elseif ($view === 'search'): ?>
<!-- ═══ PHASE 2 — SEARCH ═══════════════════════════════════ -->
<main style="max-width:640px;margin:60px auto;padding:0 24px;">
  <div class="fade-in">
    <p style="font-size:12px;font-weight:600;color:#6366f1;letter-spacing:.08em;text-transform:uppercase;margin-bottom:8px;">Patient Lookup</p>
    <h2 style="font-size:26px;font-weight:700;color:#1e293b;margin:0 0 6px;">Search by OP Number</h2>
    <p style="font-size:14px;color:#64748b;margin:0 0 32px;">Enter a patient identifier token to retrieve the occupational audiological dossier.</p>
    <?php if ($error): ?>
    <div class="toast-error" style="margin-bottom:20px;">
      <svg style="flex-shrink:0;margin-top:1px;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span style="font-size:13px;color:#b91c1c;"><?= $error ?></span>
    </div>
    <?php endif; ?>
    <div class="search-card" style="padding:28px;">
      <form method="POST" style="display:flex;gap:12px;align-items:flex-end;">
        <input type="hidden" name="action" value="search">
        <div style="flex:1;">
          <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:7px;letter-spacing:.03em;">OP NUMBER</label>
          <input type="text" name="op_number" class="field" placeholder="e.g. OP-101"
            style="font-family:'JetBrains Mono',monospace;font-size:15px;letter-spacing:.05em;"
            autocomplete="off" required>
        </div>
        <button type="submit" class="btn-primary" style="white-space:nowrap;height:42px;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Retrieve Record
        </button>
      </form>
      <p style="margin:16px 0 0;font-size:12px;color:#94a3b8;">
        Try <code style="background:#f1f5f9;padding:1px 6px;border-radius:4px;font-family:'JetBrains Mono',monospace;color:#6366f1;">OP-101</code> or
        <code style="background:#f1f5f9;padding:1px 6px;border-radius:4px;font-family:'JetBrains Mono',monospace;color:#6366f1;">OP-102</code> with the fallback dataset.
      </p>
    </div>
  </div>
</main>


<?php elseif ($view === 'dossier' && $result): ?>
<!-- ═══ PHASE 3 — DOSSIER ══════════════════════════════════ -->
<main style="max-width:860px;margin:40px auto 60px;padding:0 24px;" class="fade-in">

  <!-- Top action bar -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;" class="no-print">
    <div>
      <p style="font-size:11px;font-weight:600;color:#6366f1;letter-spacing:.09em;text-transform:uppercase;margin:0 0 4px;">Audiological Dossier</p>
      <h2 style="font-size:22px;font-weight:700;color:#1e293b;margin:0;">
        <?= safe($result['full_name']) ?>
        <span style="font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:400;color:#94a3b8;margin-left:10px;"><?= safe($result['op_number']) ?></span>
      </h2>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
      <!-- Print -->
      <button onclick="window.print()" class="btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print Report
      </button>
      <!-- Back -->
      <form method="POST" style="margin:0;">
        <input type="hidden" name="action" value="clear">
        <button type="submit" class="btn-secondary" style="color:#6366f1;border-color:#c7d2fe;background:#eef2ff;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Back to Search
        </button>
      </form>
    </div>
  </div>

  <!-- ── ICF Forms Generation Banner ─────────────────────── -->
  <div class="forms-banner no-print">
    <div class="forms-banner-info">
      <div class="forms-banner-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
          <polyline points="10 9 9 9 8 9"/>
        </svg>
      </div>
      <div>
        <div style="font-size:14px;font-weight:600;color:#0c4a6e;">ICF Hospital Forms</div>
        <div style="font-size:12px;color:#0369a1;margin-top:2px;">Generate the official Referral Form &amp; Declaration for Hearing Aid (ICF, Chennai-38) as a Word document</div>
      </div>
    </div>
    <form method="POST" style="margin:0;flex-shrink:0;">
      <input type="hidden" name="action" value="generate_forms">
      <button type="submit" class="btn-forms">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/>
          <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Download Forms (.docx)
      </button>
    </form>
  </div>

  <?php if ($error && $form_status === 'error'): ?>
  <div class="toast-error no-print" style="margin-bottom:16px;">
    <svg style="flex-shrink:0;margin-top:1px;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span style="font-size:13px;color:#b91c1c;"><?= $error ?></span>
  </div>
  <?php endif; ?>

  <!-- Print header -->
  <div style="display:none;" class="print-only">
    <h2 style="font-size:18px;font-weight:700;margin:0 0 4px;">Audiological Health Dossier</h2>
    <p style="font-size:12px;color:#64748b;margin:0 0 20px;">Generated: <?= date('d M Y, H:i') ?> &nbsp;|&nbsp; <?= $db_live ? 'SQL_LIVE' : 'LOCAL_FALLBACK' ?></p>
  </div>
  <style>@media print { .print-only { display:block !important; } }</style>

  <!-- ── SECTION A: Employee Profile ─────────────────────── -->
  <div class="dossier-card" style="margin-bottom:20px;">
    <div class="dossier-section-title"><span>&#9632;</span> Section A &nbsp;—&nbsp; Employee Profile</div>
    <div class="kv-row">
      <div class="kv-key">op_number &nbsp;&nbsp;&nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text" style="font-family:'JetBrains Mono',monospace;color:#6366f1;"><?= safe($result['op_number']) ?></div><div class="kv-val-sub">[Occupational Patient Identifier Token]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">employee_id &nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text" style="font-family:'JetBrains Mono',monospace;"><?= safe($result['employee_id']) ?></div><div class="kv-val-sub">[HR Personnel Reference Code]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">full_name &nbsp;&nbsp;&nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text" style="font-weight:600;"><?= safe($result['full_name']) ?></div><div class="kv-val-sub">[Registered Legal Name of Employee]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">age &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text"><?= safe($result['age']) ?> years</div><div class="kv-val-sub">[Current Age at Time of Assessment]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">gender &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text"><?= safe($result['gender']) ?></div><div class="kv-val-sub">[Biological Sex / Gender Identity]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">department &nbsp;&nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text"><?= safe($result['department']) ?></div><div class="kv-val-sub">[Operational Department Assignment]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">designation &nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text"><?= safe($result['designation']) ?></div><div class="kv-val-sub">[Job Title &amp; Role Classification]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">email &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text" style="color:#6366f1;"><?= safe($result['official_email']) ?></div><div class="kv-val-sub">[Official Corporate Email Address]</div></div>
    </div>
  </div>

  <!-- ── SECTION B: Test Metadata ─────────────────────────── -->
  <div class="dossier-card" style="margin-bottom:20px;">
    <div class="dossier-section-title"><span>&#9632;</span> Section B &nbsp;—&nbsp; Examination Metadata</div>
    <div class="kv-row">
      <div class="kv-key">test_id &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text" style="font-family:'JetBrains Mono',monospace;"><?= safe($result['test_id']) ?></div><div class="kv-val-sub">[Audiological Test Session Identifier]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">test_date &nbsp;&nbsp;&nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text"><?= safe($result['test_date']) ?></div><div class="kv-val-sub">[Date of Audiometric Evaluation]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">audiologist &nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text"><?= safe($result['examining_audiologist']) ?></div><div class="kv-val-sub">[Registered Examining Clinician]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">clinical_hx &nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text" style="font-size:13px;line-height:1.6;color:#374151;"><?= safe($result['clinical_history']) ?></div><div class="kv-val-sub">[Clinical History Dossier — Narrative]</div></div>
    </div>
  </div>

  <!-- ── SECTION C: Right Ear ──────────────────────────────── -->
  <div class="dossier-card" style="margin-bottom:20px;">
    <div class="dossier-section-title"><span>&#9632;</span> Section C &nbsp;—&nbsp; Right Ear (RE) Audiometric Results</div>
    <div class="kv-row">
      <div class="kv-key">re_air &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text" style="font-family:'JetBrains Mono',monospace;"><?= safe($result['right_ear_air']) ?></div><div class="kv-val-sub">[Right Ear — Air Conduction Threshold]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">re_bone &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text" style="font-family:'JetBrains Mono',monospace;"><?= safe($result['right_ear_bone']) ?></div><div class="kv-val-sub">[Right Ear — Bone Conduction Threshold]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">re_assessment:</div>
      <div class="kv-val"><div class="kv-val-text"><?= assessment_badge($result['right_ear_assessment'] ?? '') ?></div><div class="kv-val-sub" style="margin-top:6px;">[Right Ear — Clinical Audiological Assessment]</div></div>
    </div>
  </div>

  <!-- ── SECTION D: Left Ear ───────────────────────────────── -->
  <div class="dossier-card" style="margin-bottom:28px;">
    <div class="dossier-section-title"><span>&#9632;</span> Section D &nbsp;—&nbsp; Left Ear (LE) Audiometric Results</div>
    <div class="kv-row">
      <div class="kv-key">le_air &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text" style="font-family:'JetBrains Mono',monospace;"><?= safe($result['left_ear_air']) ?></div><div class="kv-val-sub">[Left Ear — Air Conduction Threshold]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">le_bone &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</div>
      <div class="kv-val"><div class="kv-val-text" style="font-family:'JetBrains Mono',monospace;"><?= safe($result['left_ear_bone']) ?></div><div class="kv-val-sub">[Left Ear — Bone Conduction Threshold]</div></div>
    </div>
    <div class="kv-row">
      <div class="kv-key">le_assessment:</div>
      <div class="kv-val"><div class="kv-val-text"><?= assessment_badge($result['left_ear_assessment'] ?? '') ?></div><div class="kv-val-sub" style="margin-top:6px;">[Left Ear — Clinical Audiological Assessment]</div></div>
    </div>
  </div>

  <p style="font-size:11px;color:#cbd5e1;text-align:center;margin:0;" class="no-print">
    This record is confidential and intended solely for authorized occupational health personnel.
  </p>
</main>

<?php endif; ?>

</body>
</html>
