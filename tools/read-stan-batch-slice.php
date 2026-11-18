<?php

declare(strict_types=1);

/**
 * Pre-slice per-identifier PHPStan JSON into fixed batches for Cursor (default 500).
 *
 *   php tools/read-stan-batch-slice.php --slice-all --id variable.undefined
 *   php tools/read-stan-batch-slice.php --current --id variable.undefined --stan-dir reports/<run>_stan
 *   php tools/read-stan-batch-slice.php --next --id variable.undefined --stan-dir reports/<run>_stan
 *   composer stan:batch -- --current --id variable.undefined
 */

$root = dirname(__DIR__);
$reportsRoot = $root . DIRECTORY_SEPARATOR . 'reports';

function usage(): void
{
    fwrite(STDERR, <<<'TXT'
Usage: php tools/read-stan-batch-slice.php [options] [reports/<run>_stan/<identifier>.json]

Modes (pick one; default: --current if progress.json exists, else require --slice-all):
  --slice-all           Write every batch_NNNN.json/.txt up front
  --current             Show current batch to fix and paths
  --next                Advance current_batch after fixes (does not re-slice)

Options:
  --id <identifier>     PHPStan identifier (e.g. variable.undefined)
  --stan-dir <path>     Stan split folder (default: newest reports/*_stan)
  --size <n>            Occurrences per batch (default: 500)
  -h, --help            This help

Outputs under: <stan-dir>/batches/<identifier>/batch_NNNN.json and batch_NNNN.txt
Progress: <stan-dir>/batches/<identifier>/progress.json (current_batch pointer)

TXT);
}

/** @return array<string, string|bool> */
function parseCli(array $argv): array
{
    $out = [
        'identifier' => '',
        'stan_dir' => '',
        'source' => '',
        'size' => '500',
        'slice_all' => false,
        'current' => false,
        'next' => false,
    ];

    for ($i = 1, $n = count($argv); $i < $n; $i++) {
        $a = $argv[$i];
        if ($a === '-h' || $a === '--help') {
            usage();
            exit(0);
        }
        if ($a === '--slice-all') {
            $out['slice_all'] = true;
            continue;
        }
        if ($a === '--current') {
            $out['current'] = true;
            continue;
        }
        if ($a === '--next') {
            $out['next'] = true;
            continue;
        }
        if ($a === '--id' && isset($argv[$i + 1])) {
            $out['identifier'] = $argv[++$i];
            continue;
        }
        if ($a === '--stan-dir' && isset($argv[$i + 1])) {
            $out['stan_dir'] = $argv[++$i];
            continue;
        }
        if ($a === '--size' && isset($argv[$i + 1])) {
            $out['size'] = $argv[++$i];
            continue;
        }
        if (!str_starts_with($a, '-')) {
            $out['source'] = $a;
            continue;
        }
        fwrite(STDERR, "Unknown option: {$a}\n");
        usage();
        exit(1);
    }

    return $out;
}

function resolvePath(string $root, string $path): string
{
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    if (preg_match('/^[A-Za-z]:/', $path) || str_starts_with($path, DIRECTORY_SEPARATOR)) {
        return $path;
    }

    return $root . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function latestStanDir(string $reportsRoot): ?string
{
    $pattern = $reportsRoot . DIRECTORY_SEPARATOR . '*_stan';
    $dirs = glob($pattern, GLOB_ONLYDIR) ?: [];
    if ($dirs === []) {
        return null;
    }
    rsort($dirs, SORT_STRING);

    return $dirs[0];
}

/** @return array{identifier: string, count: int, occurrences: list<array<string, mixed>>} */
function loadIdentifierJson(string $file): array
{
    $raw = file_get_contents($file);
    if ($raw === false) {
        throw new RuntimeException("Cannot read: {$file}");
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['occurrences']) || !is_array($data['occurrences'])) {
        throw new RuntimeException("Invalid identifier JSON (need occurrences[]): {$file}");
    }
    $id = isset($data['identifier']) && is_string($data['identifier']) ? $data['identifier'] : '';

    return [
        'identifier' => $id,
        'count' => (int) ($data['count'] ?? count($data['occurrences'])),
        'occurrences' => $data['occurrences'],
    ];
}

/** @param list<array<string, mixed>> $slice */
function writeBatchTxt(string $path, array $slice): void
{
    $lines = [];
    foreach ($slice as $row) {
        $file = (string) ($row['file'] ?? '');
        $line = (int) ($row['line'] ?? 0);
        $message = (string) ($row['message'] ?? '');
        $lines[] = "{$file}:{$line} — {$message}";
    }
    file_put_contents($path, implode("\n", $lines) . ($lines === [] ? '' : "\n"));
}

/**
 * @param list<array<string, mixed>> $occurrences
 */
function writeOneBatch(
    string $batchDir,
    int $batchNum,
    int $from,
    int $batchSize,
    int $total,
    array $occurrences,
    string $identifier,
    string $stanDir,
    string $sourceFile
): void {
    $slice = array_slice($occurrences, $from, $batchSize);
    $sliceCount = count($slice);
    $batchBase = sprintf('batch_%04d', $batchNum);
    $jsonOut = $batchDir . DIRECTORY_SEPARATOR . $batchBase . '.json';
    $txtOut = $batchDir . DIRECTORY_SEPARATOR . $batchBase . '.txt';

    $batchPayload = [
        'identifier' => $identifier,
        'stan_dir' => str_replace('\\', '/', $stanDir),
        'source' => str_replace('\\', '/', $sourceFile),
        'batch' => $batchNum,
        'from' => $from,
        'size' => $batchSize,
        'slice_count' => $sliceCount,
        'total' => $total,
        'occurrences' => $slice,
    ];

    file_put_contents(
        $jsonOut,
        json_encode($batchPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n"
    );
    writeBatchTxt($txtOut, $slice);
}

/**
 * @param list<array<string, mixed>> $occurrences
 * @return array{identifier: string, total: int, batch_size: int, total_batches: int, current_batch: int, source: string, stan_dir: string, source_mtime: int}
 */
function sliceAllBatches(
    string $batchDir,
    string $identifier,
    string $stanDir,
    string $sourceFile,
    int $batchSize,
    array $occurrences
): array {
    $total = count($occurrences);
    $totalBatches = max(1, (int) ceil($total / $batchSize));

    for ($batchNum = 1; $batchNum <= $totalBatches; $batchNum++) {
        $from = ($batchNum - 1) * $batchSize;
        writeOneBatch($batchDir, $batchNum, $from, $batchSize, $total, $occurrences, $identifier, $stanDir, $sourceFile);
    }

    $mtime = filemtime($sourceFile);
    if ($mtime === false) {
        $mtime = 0;
    }

    return [
        'identifier' => $identifier,
        'total' => $total,
        'batch_size' => $batchSize,
        'total_batches' => $totalBatches,
        'current_batch' => 1,
        'source' => str_replace('\\', '/', $sourceFile),
        'stan_dir' => str_replace('\\', '/', $stanDir),
        'source_mtime' => $mtime,
    ];
}

/** @param array<string, mixed> $progress */
function saveProgress(string $progressFile, array $progress): void
{
    file_put_contents(
        $progressFile,
        json_encode($progress, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n"
    );
}

function batchPaths(string $batchDir, int $batchNum): array
{
    $base = sprintf('batch_%04d', $batchNum);

    return [
        'txt' => $batchDir . DIRECTORY_SEPARATOR . $base . '.txt',
        'json' => $batchDir . DIRECTORY_SEPARATOR . $base . '.json',
    ];
}

/** @param array<string, mixed> $progress */
function printListStatus(array $progress, string $progressFile): void
{
    $identifier = (string) ($progress['identifier'] ?? '');
    $total = (int) ($progress['total'] ?? 0);
    $batchSize = (int) ($progress['batch_size'] ?? 500);
    $totalBatches = (int) ($progress['total_batches'] ?? 0);
    $current = (int) ($progress['current_batch'] ?? 1);
    $batchDir = dirname($progressFile);

    if ($current > $totalBatches && $totalBatches > 0) {
        echo "identifier: {$identifier}\n";
        echo "total: {$total}\n";
        echo "batch_size: {$batchSize}\n";
        echo "total_batches: {$totalBatches}\n";
        echo "current_batch: done (past batch {$totalBatches})\n";
        echo "batches_remaining: 0\n";
        echo "progress: {$progressFile}\n";
        return;
    }

    $paths = batchPaths($batchDir, $current);
    $remaining = max(0, $totalBatches - $current + 1);

    echo "identifier: {$identifier}\n";
    echo "total: {$total}\n";
    echo "batch_size: {$batchSize}\n";
    echo "total_batches: {$totalBatches}\n";
    echo "current_batch: {$current}\n";
    echo "fix_txt: {$paths['txt']}\n";
    echo "fix_json: {$paths['json']}\n";
    echo "batches_remaining: {$remaining}\n";
    echo "progress: {$progressFile}\n";
}

$cli = parseCli($argv);
$batchSize = max(1, (int) $cli['size']);

$modeCount = ($cli['slice_all'] ? 1 : 0) + ($cli['current'] ? 1 : 0) + ($cli['next'] ? 1 : 0);

$sourceFile = $cli['source'] !== '' ? resolvePath($root, $cli['source']) : '';
$stanDir = $cli['stan_dir'] !== '' ? resolvePath($root, $cli['stan_dir']) : '';

if ($sourceFile !== '' && is_file($sourceFile)) {
    $stanDir = dirname($sourceFile);
    if ($cli['identifier'] === '') {
        $cli['identifier'] = basename($sourceFile, '.json');
    }
} else {
    if ($stanDir === '') {
        $found = latestStanDir($reportsRoot);
        if ($found === null) {
            fwrite(STDERR, "No reports/*_stan folder. Run: composer stan:report\n");
            exit(1);
        }
        $stanDir = $found;
    }
    if ($cli['identifier'] === '') {
        fwrite(STDERR, "Missing --id <identifier> or path to <identifier>.json\n");
        usage();
        exit(1);
    }
    $sourceFile = $stanDir . DIRECTORY_SEPARATOR . $cli['identifier'] . '.json';
}

if (!is_file($sourceFile)) {
    fwrite(STDERR, "Missing: {$sourceFile}\n");
    exit(1);
}

$payload = loadIdentifierJson($sourceFile);
$identifier = $cli['identifier'] !== '' ? $cli['identifier'] : $payload['identifier'];
if ($identifier === '') {
    $identifier = basename($sourceFile, '.json');
}

$batchDir = $stanDir . DIRECTORY_SEPARATOR . 'batches' . DIRECTORY_SEPARATOR . $identifier;
$progressFile = $batchDir . DIRECTORY_SEPARATOR . 'progress.json';

if (!is_dir($batchDir) && !mkdir($batchDir, 0775, true) && !is_dir($batchDir)) {
    fwrite(STDERR, "Cannot create: {$batchDir}\n");
    exit(1);
}

if ($modeCount === 0) {
    if (is_file($progressFile)) {
        $cli['current'] = true;
    } else {
        fwrite(STDERR, "No progress yet. Run: --slice-all --id {$identifier}\n");
        usage();
        exit(1);
    }
} elseif ($modeCount > 1) {
    fwrite(STDERR, "Use only one of: --slice-all, --current, --next\n");
    exit(1);
}

/** @var array<string, mixed>|null $progress */
$progress = is_file($progressFile) ? json_decode((string) file_get_contents($progressFile), true) : null;

if ($cli['slice_all']) {
    $total = count($payload['occurrences']);
    $expectedBatches = max(1, (int) ceil($total / $batchSize));
    $firstBatch = $batchDir . DIRECTORY_SEPARATOR . 'batch_0001.json';
    $sourceNorm = str_replace('\\', '/', $sourceFile);
    $staleProgress = is_array($progress)
        && (
            (int) ($progress['total'] ?? -1) !== $total
            || (int) ($progress['batch_size'] ?? -1) !== $batchSize
            || (string) ($progress['source'] ?? '') !== $sourceNorm
        );

    if ($staleProgress) {
        fwrite(STDERR, "Source JSON changed vs progress.json. Run: composer stan:report, then --slice-all on the new reports/<run>_stan\n");
        exit(1);
    }

    if (is_file($firstBatch)) {
        $existing = glob($batchDir . DIRECTORY_SEPARATOR . 'batch_*.json') ?: [];
        if (count($existing) >= $expectedBatches) {
            if (!is_array($progress)) {
                $mtime = filemtime($sourceFile);
                $progress = [
                    'identifier' => $identifier,
                    'total' => $total,
                    'batch_size' => $batchSize,
                    'total_batches' => $expectedBatches,
                    'current_batch' => 1,
                    'source' => $sourceNorm,
                    'stan_dir' => str_replace('\\', '/', $stanDir),
                    'source_mtime' => $mtime !== false ? $mtime : 0,
                ];
                saveProgress($progressFile, $progress);
            }
            echo "Batches already sliced ({$expectedBatches} batches). For a fresh list: composer stan:report, then --slice-all on the new reports/<run>_stan.\n";
            printListStatus($progress, $progressFile);
            exit(0);
        }
    }

    $progress = sliceAllBatches(
        $batchDir,
        $identifier,
        $stanDir,
        $sourceFile,
        $batchSize,
        $payload['occurrences']
    );
    saveProgress($progressFile, $progress);

    $totalBatches = (int) $progress['total_batches'];
    echo "Sliced {$totalBatches} batch(es) for {$identifier} (size {$batchSize}, total {$total})\n";
    printListStatus($progress, $progressFile);
    exit(0);
}

if ($cli['current']) {
    if (!is_array($progress)) {
        fwrite(STDERR, "Missing progress.json. Run: --slice-all --id {$identifier}\n");
        exit(1);
    }
    printListStatus($progress, $progressFile);
    exit(0);
}

if ($cli['next']) {
    if (!is_array($progress)) {
        fwrite(STDERR, "Missing progress.json. Run: --slice-all --id {$identifier}\n");
        exit(1);
    }

    $totalBatches = (int) ($progress['total_batches'] ?? 0);
    $current = (int) ($progress['current_batch'] ?? 1);

    if ($current >= $totalBatches) {
        echo "All batches finished for {$identifier} ({$totalBatches} batches). Re-run composer stan:report.\n";
        $progress['current_batch'] = $totalBatches + 1;
        saveProgress($progressFile, $progress);
        exit(0);
    }

    $progress['current_batch'] = $current + 1;
    saveProgress($progressFile, $progress);

    echo "Advanced to batch {$progress['current_batch']} of {$totalBatches}.\n";
    printListStatus($progress, $progressFile);
    exit(0);
}

usage();
exit(1);