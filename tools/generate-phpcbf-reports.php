<?php

declare(strict_types=1);

/**
 * Read file list from the latest compat:report JSON (or a given *_compat_full.json),
 * then PHPCS summary → PHPCBF → PHPCS summary on those paths:
 *   <YYYYMMDD_HHMMSS>_phpcbf_files.txt
 *   <YYYYMMDD_HHMMSS>_phpcbf_before_summary.txt
 *   <YYYYMMDD_HHMMSS>_phpcbf_fix.log
 *   <YYYYMMDD_HHMMSS>_phpcbf_after_summary.txt
 */

$root = dirname(__DIR__);
$reportDir = $root . DIRECTORY_SEPARATOR . 'reports';
$standard = __DIR__ . DIRECTORY_SEPARATOR . 'config_phpcs.xml';
$binDir = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'squizlabs'
    . DIRECTORY_SEPARATOR . 'php_codesniffer' . DIRECTORY_SEPARATOR . 'bin';
$phpcs = $binDir . DIRECTORY_SEPARATOR . 'phpcs';
$phpcbf = $binDir . DIRECTORY_SEPARATOR . 'phpcbf';

if (!is_file($phpcs) || !is_file($phpcbf)) {
    fwrite(STDERR, "PHPCS/PHPCBF not found. Run: composer install\n");
    exit(1);
}

$compatJson = $argv[1] ?? null;
if ($compatJson !== null && !is_file($compatJson)) {
    fwrite(STDERR, "Compat JSON not found: {$compatJson}\n");
    exit(1);
}

if ($compatJson === null) {
    $matches = glob($reportDir . DIRECTORY_SEPARATOR . '*_compat_full.json') ?: [];
    if ($matches === []) {
        fwrite(STDERR, "No *_compat_full.json in reports/. Run: composer compat:report\n");
        exit(1);
    }
    rsort($matches);
    $compatJson = $matches[0];
}

$payload = json_decode((string) file_get_contents($compatJson), true);
if (!is_array($payload) || !isset($payload['files']) || !is_array($payload['files'])) {
    fwrite(STDERR, "Invalid compat JSON: {$compatJson}\n");
    exit(1);
}

$rootReal = realpath($root) ?: $root;
$files = [];
foreach ($payload['files'] as $path => $meta) {
    if (!is_array($meta)) {
        continue;
    }
    $errors = (int) ($meta['errors'] ?? 0);
    $warnings = (int) ($meta['warnings'] ?? 0);
    if ($errors + $warnings === 0) {
        continue;
    }
    $real = realpath((string) $path);
    if ($real === false || !is_file($real)) {
        continue;
    }
    if (!str_starts_with($real, $rootReal . DIRECTORY_SEPARATOR) && $real !== $rootReal) {
        continue;
    }
    $rel = substr($real, strlen($rootReal) + 1);
    $files[$rel] = true;
}
$files = array_keys($files);
sort($files);

if ($files === []) {
    echo "No fixable targets in {$compatJson}\n";
    exit(0);
}

if (!is_dir($reportDir) && !mkdir($reportDir, 0775, true) && !is_dir($reportDir)) {
    fwrite(STDERR, "Cannot create directory: {$reportDir}\n");
    exit(1);
}

chdir($root);

$timestamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Hong_Kong')))->format('Ymd_His');
$fileListPath = $reportDir . DIRECTORY_SEPARATOR . $timestamp . '_phpcbf_files.txt';
$beforeSummary = $reportDir . DIRECTORY_SEPARATOR . $timestamp . '_phpcbf_before_summary.txt';
$fixLog = $reportDir . DIRECTORY_SEPARATOR . $timestamp . '_phpcbf_fix.log';
$afterSummary = $reportDir . DIRECTORY_SEPARATOR . $timestamp . '_phpcbf_after_summary.txt';

file_put_contents($fileListPath, implode("\n", $files) . "\n");

$escapeArg = static function (string $part): string {
    if (preg_match('/[\s"]/', $part) === 1) {
        return '"' . str_replace('"', '\\"', $part) . '"';
    }

    return $part;
};

$runPhpcsSummary = static function (string $reportFile, array $paths) use ($phpcs, $standard, $escapeArg): int {
    $args = [
        PHP_BINARY,
        $phpcs,
        '--standard=' . $standard,
        '--report=summary',
        '--report-file=' . $reportFile,
        ...$paths,
    ];
    passthru(implode(' ', array_map($escapeArg, $args)), $exitCode);

    return (int) $exitCode;
};

echo "PHPCBF batch ({$timestamp}), " . count($files) . " files from {$compatJson}\n";
echo "  {$fileListPath}\n";

$beforeExit = $runPhpcsSummary($beforeSummary, $files);
if (!is_file($beforeSummary)) {
    fwrite(STDERR, "Report not created: {$beforeSummary} (phpcs exit {$beforeExit})\n");
    exit(1);
}
echo "  {$beforeSummary}\n";

$phpcbfCmd = [PHP_BINARY, $phpcbf, '--standard=' . $standard, '-p', ...$files];
$proc = proc_open(
    $phpcbfCmd,
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    $root
);
if (!is_resource($proc)) {
    fwrite(STDERR, "Failed to start phpcbf\n");
    exit(1);
}
fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]) ?: '';
$stderr = stream_get_contents($pipes[2]) ?: '';
fclose($pipes[1]);
fclose($pipes[2]);
$phpcbfExit = proc_close($proc);

$logBody = "Source compat JSON: {$compatJson}\n\n" . $stdout . $stderr;
file_put_contents($fixLog, $logBody);
echo $stdout . $stderr;
echo "  {$fixLog}\n";

$afterExit = $runPhpcsSummary($afterSummary, $files);
if (!is_file($afterSummary)) {
    fwrite(STDERR, "Report not created: {$afterSummary} (phpcs exit {$afterExit})\n");
    exit(1);
}
echo "  {$afterSummary}\n";

echo "Exit codes: before={$beforeExit}, phpcbf={$phpcbfExit}, after={$afterExit}\n";

exit(0);
