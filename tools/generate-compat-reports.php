<?php

declare(strict_types=1);

/**
 * Run PHPCompatibility (PHPCS) and write three timestamped reports:
 *   <YYYYMMDD_HHMMSS>_compat_full.txt   — --report=full
 *   <YYYYMMDD_HHMMSS>_compat_full.json  — --report=json
 *   <YYYYMMDD_HHMMSS>_compat_source.txt — --report=source
 */

$root = dirname(__DIR__);
$reportDir = $root . DIRECTORY_SEPARATOR . 'reports';
$standard = __DIR__ . DIRECTORY_SEPARATOR . 'config_phpcs.xml';
$phpcs = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'squizlabs'
    . DIRECTORY_SEPARATOR . 'php_codesniffer' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpcs';

if (!is_file($phpcs)) {
    fwrite(STDERR, "PHPCS not found. Run: composer install\n");
    exit(1);
}

if (!is_file($standard)) {
    fwrite(STDERR, "Missing config: {$standard}\n");
    exit(1);
}

if (!is_dir($reportDir) && !mkdir($reportDir, 0775, true) && !is_dir($reportDir)) {
    fwrite(STDERR, "Cannot create directory: {$reportDir}\n");
    exit(1);
}

chdir($root);

$timestamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Hong_Kong')))->format('Ymd_His');
$prefix = $reportDir . DIRECTORY_SEPARATOR . $timestamp . '_compat';

$reports = [
    ['full', $prefix . '_full.txt'],
    ['json', $prefix . '_full.json'],
    ['source', $prefix . '_source.txt'],
];

$runPhpcs = static function (string $report, string $reportFile) use ($phpcs, $standard): int {
    $args = [
        PHP_BINARY,
        $phpcs,
        '--standard=' . $standard,
        '--report=' . $report,
        '--report-file=' . $reportFile,
    ];
    $escaped = array_map(static function (string $part): string {
        if (preg_match('/[\s"]/', $part) === 1) {
            return '"' . str_replace('"', '\\"', $part) . '"';
        }

        return $part;
    }, $args);
    passthru(implode(' ', $escaped), $exitCode);

    return (int) $exitCode;
};

echo "Writing {$timestamp}_compat reports\n";

$exitCodes = [];
foreach ($reports as [$report, $path]) {
    $exitCodes[$report] = $runPhpcs($report, $path);
    if (!is_file($path)) {
        fwrite(STDERR, "Report not created: {$path} (phpcs exit {$exitCodes[$report]})\n");
        exit(1);
    }
    echo "  {$path}\n";
}

echo 'PHPCS exit codes: full=' . $exitCodes['full']
    . ', json=' . $exitCodes['json']
    . ', source=' . $exitCodes['source'] . "\n";

exit(0);
