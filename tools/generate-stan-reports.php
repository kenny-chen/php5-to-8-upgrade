<?php

declare(strict_types=1);

/**
 * Run PHPStan and write timestamped reports under reports/:
 *   <YYYYMMDD_HHMMSS>_stan.txt  — --error-format=table
 *   <YYYYMMDD_HHMMSS>_stan.json — --error-format=json
 *   <YYYYMMDD_HHMMSS>_stan.log  — stderr + summary
 *   <YYYYMMDD_HHMMSS>_stan/     — per-identifier JSON (by_identifier.json + one file per type)
 */

$root = dirname(__DIR__);
$reportDir = $root . DIRECTORY_SEPARATOR . 'reports';
$config = __DIR__ . DIRECTORY_SEPARATOR . 'config_phpstan.neon';
$phpstan = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpstan';

if (!is_file($phpstan)) {
    fwrite(STDERR, "PHPStan not found. Run: composer install\n");
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

$timestamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Hong_Kong')))->format('Ymd_His');
$prefix = $reportDir . DIRECTORY_SEPARATOR . $timestamp . '_stan';

$runs = [
    ['table', $prefix . '.txt'],
    ['json', $prefix . '.json'],
];

$logFile = $prefix . '.log';
$log = fopen($logFile, 'wb');
if ($log === false) {
    fwrite(STDERR, "Cannot write: {$logFile}\n");
    exit(1);
}
fwrite($log, "PHPStan reports {$timestamp} (Asia/Hong_Kong)\n\n");

$runStan = static function (string $format, string $outFile) use ($root, $phpstan, $config, $log): int {
    $stderrPipe = fopen('php://temp', 'wb+');
    if ($stderrPipe === false) {
        return 1;
    }

    $forceAllTableErrors = $format === 'table';
    if ($forceAllTableErrors) {
        // Avoid "Result is limited to the first 1000 errors" in *_stan.txt
        putenv('PHPSTAN_TABLE_ERROR_FORMATTER_FORCE_SHOW_ALL_ERRORS=1');
    }

    $proc = proc_open(
        [
            PHP_BINARY,
            $phpstan,
            'analyse',
            '-c',
            $config,
            '--memory-limit=512M',
            '--no-progress',
            '--error-format=' . $format,
        ],
        [
            0 => ['pipe', 'r'],
            1 => ['file', $outFile, 'w'],
            2 => $stderrPipe,
        ],
        $pipes,
        $root
    );

    if ($forceAllTableErrors) {
        putenv('PHPSTAN_TABLE_ERROR_FORMATTER_FORCE_SHOW_ALL_ERRORS');
    }

    if (!is_resource($proc)) {
        fclose($stderrPipe);

        return 1;
    }

    fclose($pipes[0]);
    $exitCode = proc_close($proc);

    rewind($stderrPipe);
    $stderr = stream_get_contents($stderrPipe) ?: '';
    fclose($stderrPipe);

    fwrite($log, "=== format={$format} exit={$exitCode} → {$outFile} ===\n");
    if ($stderr !== '') {
        fwrite($log, $stderr);
        if ($stderr !== '' && substr($stderr, -1) !== "\n") {
            fwrite($log, "\n");
        }
    }
    fwrite($log, "\n");

    return $exitCode;
};

$splitStanJsonByIdentifier = static function (
    string $jsonFile,
    string $splitDir,
    string $projectRoot,
    $log
): void {
    if (!is_dir($splitDir) && !mkdir($splitDir, 0775, true) && !is_dir($splitDir)) {
        fwrite(STDERR, "Cannot create directory: {$splitDir}\n");

        return;
    }
    $raw = file_get_contents($jsonFile);
    if ($raw === false) {
        fwrite(STDERR, "Cannot read: {$jsonFile}\n");

        return;
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload) || !isset($payload['files']) || !is_array($payload['files'])) {
        fwrite(STDERR, "Invalid PHPStan JSON: {$jsonFile}\n");

        return;
    }

    $rootReal = realpath($projectRoot) ?: $projectRoot;
    $rootNorm = rtrim(str_replace('\\', '/', $rootReal), '/') . '/';

    /** @var array<string, list<array{file: string, line: int, message: string}>> $buckets */
    $buckets = [];

    foreach ($payload['files'] as $filePath => $fileData) {
        if (!is_array($fileData) || !isset($fileData['messages']) || !is_array($fileData['messages'])) {
            continue;
        }

        $fileNorm = str_replace('\\', '/', (string) $filePath);
        if (str_starts_with($fileNorm, $rootNorm)) {
            $fileNorm = substr($fileNorm, strlen($rootNorm));
        }

        foreach ($fileData['messages'] as $message) {
            if (!is_array($message)) {
                continue;
            }

            $identifier = isset($message['identifier']) && is_string($message['identifier'])
                ? $message['identifier']
                : 'unknown';

            $buckets[$identifier][] = [
                'file' => $fileNorm,
                'line' => (int) ($message['line'] ?? 0),
                'message' => (string) ($message['message'] ?? ''),
            ];
        }
    }

    $counts = [];
    foreach ($buckets as $identifier => $occurrences) {
        $counts[$identifier] = count($occurrences);
    }

    arsort($counts, SORT_NUMERIC);

    $summaryFile = $splitDir . DIRECTORY_SEPARATOR . '_by_identifier.json';
    $summaryJson = json_encode(['counts' => $counts], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    file_put_contents($summaryFile, $summaryJson . "\n");

    fwrite($log, "=== split by identifier → {$splitDir} (" . count($counts) . " types) ===\n");

    foreach ($counts as $identifier => $count) {
        $typeFile = $splitDir . DIRECTORY_SEPARATOR . $identifier . '.json';
        $typeJson = json_encode(
            [
                'identifier' => $identifier,
                'count' => $count,
                'occurrences' => $buckets[$identifier],
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        file_put_contents($typeFile, $typeJson . "\n");
    }

    fwrite($log, 'Per-identifier files: ' . $splitDir . DIRECTORY_SEPARATOR . '<identifier>.json' . "\n\n");
};

echo "Writing {$timestamp}_stan reports\n";

$exitCodes = [];
foreach ($runs as [$format, $path]) {
    $exitCodes[$format] = $runStan($format, $path);
    if (!is_file($path)) {
        fclose($log);
        fwrite(STDERR, "Report not created: {$path} (phpstan exit {$exitCodes[$format]})\n");
        exit(1);
    }
    echo "  {$path}\n";
}

$jsonPath = $prefix . '.json';
$splitDir = $prefix;
try {
    $splitStanJsonByIdentifier($jsonPath, $splitDir, $root, $log);
    echo "  {$splitDir}" . DIRECTORY_SEPARATOR . "_by_identifier.json (+ per-type JSON)\n";
} catch (JsonException $e) {
    fwrite(STDERR, 'Split stan JSON failed: ' . $e->getMessage() . "\n");
}

fwrite($log, 'PHPStan exit codes: table=' . $exitCodes['table'] . ', json=' . $exitCodes['json'] . "\n");
fclose($log);
echo "  {$logFile}\n";

echo 'PHPStan exit codes: table=' . $exitCodes['table']
    . ', json=' . $exitCodes['json'] . "\n";

exit(0);
