<?php
/**
 * Skrip Pelaksana Skema & Seeder Pangkalan Data SCRS PMU
 * Boleh dijalankan melalui CLI (php database/seed.php) atau pelayar web (http://localhost/.../database/seed.php)
 */

require_once __DIR__ . '/../includes/db.php';

$is_cli = (php_sapi_name() === 'cli');

function output_msg($msg, $type = 'info') {
    global $is_cli;
    if ($is_cli) {
        $prefix = ($type === 'success') ? "[OK] " : (($type === 'error') ? "[RALAT] " : "[INFO] ");
        echo $prefix . $msg . PHP_EOL;
    } else {
        $color = ($type === 'success') ? '#007700' : (($type === 'error') ? '#cc0000' : '#333333');
        echo "<div style='font-family: monospace; padding: 4px 8px; color: {$color};'>{$msg}</div>";
    }
}

if (!$is_cli) {
    echo "<!DOCTYPE html><html><head><title>Database Seeder - SCRS PMU</title></head><body style='background:#f4f4f0; padding:20px; font-family:Arial,sans-serif;'>";
    echo "<div style='max-width:800px; margin:0 auto; background:#fff; border:3px solid #000; box-shadow:5px 5px 0px #000; padding:20px;'>";
    echo "<h2>Sistem Pengkalan Data SCRS PMU: Skema & Seeder</h2><hr>";
}

output_msg("Memulakan proses tetapan semula skema dan seeder pangkalan data...", "info");

// 1. Laksana Schema SQL
$schema_file = __DIR__ . '/schema.sql';
if (!file_exists($schema_file)) {
    output_msg("Fail schema.sql tidak dijumpai!", "error");
    exit(1);
}

$schema_sql = file_get_contents($schema_file);
if ($conn->multi_query($schema_sql)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    output_msg("Skema pangkalan data berjaya diselaraskan (schema.sql).", "success");
} else {
    output_msg("Gagal melaksanakan schema.sql: " . $conn->error, "error");
    exit(1);
}

// Jalankan Seeder Baharu SCRS PMU
require_once __DIR__ . '/seed_new.php';

