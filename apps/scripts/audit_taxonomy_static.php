<?php

$taxonomy = require __DIR__.'/../admin-laravel/config/yfd_taxonomy.php';
$defaults = require __DIR__.'/../admin-laravel/config/category_bucket_mappings_defaults.php';

$expense = $taxonomy['expense_categories'];
$income = $taxonomy['income_categories'];
$aliases = $taxonomy['aliases'];
$ok = true;

echo "=== Static taxonomy audit ===\n";

foreach (['Affiliate', 'Gaji', 'Freelance', 'Dividen', 'Bunga Investasi', 'Cashback', 'Refund', 'Penjualan', 'Sewa Masuk', 'Transfer Masuk'] as $must) {
    if (! in_array($must, $income, true)) {
        echo "FAIL missing income: {$must}\n";
        $ok = false;
    } else {
        echo "OK income: {$must}\n";
    }
}

foreach ([
    'Makanan & Minuman', 'Tempat Tinggal', 'Transportasi', 'Komunikasi',
    'Kesehatan & Kebersihan Diri', 'Pendidikan', 'Investasi & Tabungan', 'Proteksi',
    'Lifestyle & Hiburan', 'Traveling', 'Sosial & Keluarga', 'Bisnis & Karir',
    'Hadiah', 'Cicilan & Hutang', 'Pakaian & Aksesoris', 'Lain-lain',
] as $must) {
    if (! in_array($must, $expense, true)) {
        echo "FAIL missing expense: {$must}\n";
        $ok = false;
    } else {
        echo "OK expense: {$must}\n";
    }
}

foreach ([
    'jajan' => 'Makanan & Minuman',
    'affiliate' => 'Affiliate',
    'afiliasi' => 'Affiliate',
    'transport' => 'Transportasi',
    'social' => 'Sosial & Keluarga',
    'asuransi' => 'Proteksi',
    'subscription' => 'Lifestyle & Hiburan',
    'laundry' => 'Kesehatan & Kebersihan Diri',
    'skincare' => 'Kesehatan & Kebersihan Diri',
    'fashion' => 'Pakaian & Aksesoris',
] as $from => $to) {
    $got = $aliases[$from] ?? null;
    if ($got !== $to) {
        echo "FAIL alias {$from}: expected {$to}, got ".json_encode($got)."\n";
        $ok = false;
    } else {
        echo "OK alias: {$from} → {$to}\n";
    }
}

$mapped = [];
foreach ($defaults as $row) {
    $cat = $row['category'] ?? '';
    if ($cat !== '' && $cat !== '*') {
        $mapped[mb_strtolower($cat)] = true;
    }
}

foreach (array_merge($expense, $income) as $cat) {
    if ($cat === 'Lain-lain') {
        continue;
    }
    if (! isset($mapped[mb_strtolower($cat)])) {
        echo "FAIL no default mapping for: {$cat}\n";
        $ok = false;
    }
}

$affiliateMap = null;
foreach ($defaults as $row) {
    if (($row['category'] ?? '') === 'Affiliate') {
        $affiliateMap = $row;
        break;
    }
}
if ($affiliateMap === null || ($affiliateMap['bucket'] ?? '') !== 'Income' || ($affiliateMap['transaction_type'] ?? '') !== 'income') {
    echo "FAIL Affiliate mapping must be Income/income\n";
    $ok = false;
} else {
    echo "OK Affiliate mapping → Income (excluded from prescription buckets)\n";
}

echo $ok ? "\nSTATIC AUDIT PASS\n" : "\nSTATIC AUDIT ISSUES\n";
exit($ok ? 0 : 1);
