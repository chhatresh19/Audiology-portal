<?php
// ================= ERROR REPORTING =================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ================= LOCAL BYPASS PAYLOAD =================
$count = 1; 
$rowcount = 1;

// Capture the incoming data passed from your button
$IBR_DESC  = $_REQUEST["IBR_DESC"] ?? '';
$BILL_DT   = $_REQUEST["BILL_DT"] ?? '';
$SUPCD     = $_REQUEST["SUPCD"] ?? '';
$IBR_REM   = $_REQUEST["IBR_REM"] ?? '';
$IBR_AMT   = $_REQUEST["IBR_AMT"] ?? '';
$IBR_NET   = $_REQUEST["IBR_NET"] ?? '';
$VR_NO     = $_REQUEST["VR_NO"] ?? '';
$VR_DT     = $_REQUEST["VR_DT"] ?? '';
$IBR_ALL   = $_REQUEST["IBR_ALL"] ?? '';
$PAY_CODE  = $_REQUEST["PAY_CODE"] ?? '';
$FORM_TYPE = $_REQUEST["FORM_TYPE"] ?? 'declaration';

// File naming setup
$fname = 'VRNO_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $VR_NO);
$fn    = $fname . ".pdf";

// Target path construction
$base_path = realpath("VR_ORDER/unsigned/");
if ($base_path === false) {
    die("Error: The folder 'VR_ORDER/unsigned/' does not exist. Please create it inside your project directory.");
}
$file_path = $base_path . DIRECTORY_SEPARATOR . $fn;

// Base url configuration (Local XAMPP context)
$current_url = "http://localhost/hearing-portal/";

// Build URL parameters for your PDF layout template
$pdf_url = $current_url . "claudeindex.php"
    . "?IBR_DESC=" . urlencode($IBR_DESC)
    . "&BILL_DT=" . urlencode($BILL_DT)
    . "&SUPCD=" . urlencode($SUPCD)
    . "&IBR_REM=" . urlencode($IBR_REM)
    . "&IBR_AMT=" . urlencode($IBR_AMT)
    . "&IBR_NET=" . urlencode($IBR_NET)
    . "&VR_NO=" . urlencode($VR_NO)
    . "&VR_DT=" . urlencode($VR_DT)
    . "&IBR_ALL=" . urlencode($IBR_ALL)
    . "&PAY_CODE=" . urlencode($PAY_CODE);

$escaped_url    = escapeshellarg($pdf_url);
$escaped_output = escapeshellarg($file_path);

// Run wkhtmltopdf with diagnostic forced visibility
$exe_str = "\"C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe\" $escaped_url $escaped_output";
$output = shell_exec($exe_str . " 2>&1");

// Force diagnostic log view to find out exactly why the PDF won't create
if (!file_exists($file_path)) {
    echo "<h2>PDF Engine Debug Log</h2>";
    echo "<strong>Target File Destination:</strong> " . htmlspecialchars($file_path) . "<br>";
    echo "<strong>Attempting to convert URL:</strong> " . htmlspecialchars($pdf_url) . "<br><br>";
    echo "<strong>Engine Execution Output:</strong><br><pre style='background:#222;color:#fff;padding:15px;border-radius:6px;'>$output</pre>";
    exit;
} else {
    // If successful, head straight to signature portal
    header("Location: digi_sign_vr.php?docno=" . urlencode($VR_NO) . "&rows=" . urlencode($rowcount));
    exit;
}
?>