<?php

declare(strict_types=1);

/**
 * Rector dry-run JSON → apply (modifies source) → dry-run JSON:
 *   <YYYYMMDD_HHMMSS>_rector_before.json
 *   <YYYYMMDD_HHMMSS>_rector_apply.log
 *   <YYYYMMDD_HHMMSS>_rector_after.json
 */

$root = dirname(__DIR__);
$reportDir = $root . DIRECTORY_SEPARATOR . 'reports';
$config = __DIR__ . DIRECTORY_SEPARATOR . 'config_rector.php';
$rector = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'rector';

if (!is_file($rector)) {
    fwrite(STDERR, "Rector not found. Run: composer install\n");
    exit(1);
}

if (!is_file($config)) {
    fwrite(STDERR, "Missing config: {$config}\n");
    exit(1);
}

if (!is_dir($reportDir) && !mkdir($reportDir, 0775, true) && !is_dir($reportDir)) {
    fwrite(STDERR, "Cannot create directory: {$reportDir}\n");
    exit(1);
}

chdir($root);

$baseCmd = [
    PHP_BINARY,
    $rector,
    'process',
    '-c',
    $config,
    '--no-progress-bar',
];

$runRectorDryRunJson = static function (string $jsonFile) use ($baseCmd, $root): int {
    $stderrPipe = fopen('php://temp', 'wb+');
    if ($stderrPipe === false) {
        return 1;
    }

    $proc = proc_open(
        [...$baseCmd, '--dry-run', '--output-format=json'],
        [
            0 => ['pipe', 'r'],
            1 => ['file', $jsonFile, 'w'],
            2 => $stderrPipe,
        ],
        $pipes,
        $root
    );

    if (!is_resource($proc)) {
        fclose($stderrPipe);

        return 1;
    }

    fclose($pipes[0]);
    $exitCode = proc_close($proc);

    rewind($stderrPipe);
    $stderr = stream_get_contents($stderrPipe) ?: '';
    fclose($stderrPipe);

    if ($stderr !== '') {
        fwrite(STDERR, $stderr);
    }

    return $exitCode;
};

$timestamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Hong_Kong')))->format('Ymd_His');
$beforeJson = $reportDir . DIRECTORY_SEPARATOR . $timestamp . '_rector_before.json';
$applyLog = $reportDir . DIRECTORY_SEPARATOR . $timestamp . '_rector_apply.log';
$afterJson = $reportDir . DIRECTORY_SEPARATOR . $timestamp . '_rector_after.json';

echo "Rector batch ({$timestamp})\n";

$beforeExit = $runRectorDryRunJson($beforeJson);
if (!is_file($beforeJson) || filesize($beforeJson) === 0) {
    fwrite(STDERR, "Report not created: {$beforeJson} (rector exit {$beforeExit})\n");
    exit(1);
}
echo "  {$beforeJson}\n";

$beforePayload = json_decode((string) file_get_contents($beforeJson), true);
$changedFiles = is_array($beforePayload) ? (int) ($beforePayload['totals']['changed_files'] ?? 0) : 0;

if ($changedFiles > 0) {
    $proc = proc_open(
        $baseCmd,
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root
    );
    if (!is_resource($proc)) {
        fwrite(STDERR, "Failed to start rector apply\n");
        exit(1);
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]) ?: '';
    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    $applyExit = proc_close($proc);

    $logBody = $stdout . $stderr;
    file_put_contents($applyLog, $logBody);
    echo $stdout . $stderr;
} else {
    $applyExit = 0;
    $logBody = "No changed_files in before JSON; skipped apply.\n";
    file_put_contents($applyLog, $logBody);
    echo $logBody;
}
echo "  {$applyLog}\n";

$afterExit = $runRectorDryRunJson($afterJson);
if (!is_file($afterJson) || filesize($afterJson) === 0) {
    fwrite(STDERR, "Report not created: {$afterJson} (rector exit {$afterExit})\n");
    exit(1);
}
echo "  {$afterJson}\n";

echo "Exit codes: before={$beforeExit}, apply={$applyExit}, after={$afterExit}\n";
echo "Before changed_files={$changedFiles}\n";

exit(0);
