#!/usr/bin/env pwsh
# Step 2: Find problems with composer compat:report

$repoRoot = Get-Location
Set-Location $repoRoot

# Check composer.json exists
if (!(Test-Path "$repoRoot\composer.json")) {
    Write-Host "Error: composer.json not found at repo root"
    exit 1
}

# Check vendor directory exists
if (!(Test-Path "$repoRoot\vendor")) {
    Write-Host "Error: vendor/ not found. Run 'composer install' first"
    exit 1
}

# Check PHP CLI is available
$phpVersion = php -v 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: PHP CLI not available"
    exit 1
}

Write-Host "Running composer compat:report..."
composer compat:report

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: composer compat:report failed"
    exit 1
}

# Find newest report
$reportsFolder = "$repoRoot\reports"
$latestCompat = Get-ChildItem "$reportsFolder\*_compat_full.json" | Sort-Object Name -Descending | Select-Object -First 1

if ($latestCompat) {
    Write-Host "Latest compat report: $($latestCompat.FullName)"
    # Output report path for subsequent steps
    $latestCompat.BaseName | Out-File -FilePath "$reportsFolder\.last_compat_report.txt" -Encoding utf8
} else {
    Write-Host "Warning: No compat report found after running composer compat:report"
}