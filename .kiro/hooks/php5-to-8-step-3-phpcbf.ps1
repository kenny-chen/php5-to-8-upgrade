#!/usr/bin/env pwsh
# Step 3: Try auto-fixes with PHPCBF

$repoRoot = Get-Location
Set-Location $repoRoot

Write-Host "PHPCBF may change source files. Continuing..."

# Find latest compat report
$reportsFolder = "$repoRoot\reports"
$lastReportFile = "$reportsFolder\.last_compat_report.txt"

if (!(Test-Path $lastReportFile)) {
    Write-Host "Error: No previous compat report found. Run step 2 first."
    exit 1
}

$lastReportBaseName = Get-Content $lastReportFile
Write-Host "Running composer compat:phpcbf with $lastReportBaseName..."
composer compat:phpcbf

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: composer compat:phpcbf failed"
    exit 1
}

# List new artifacts
$artifactPrefix = "$reportsFolder\$lastReportBaseName"
@(
    "$artifactPrefix*_phpcbf_files.txt",
    "$artifactPrefix*_phpcbf_before_summary.txt",
    "$artifactPrefix*_phpcbf_fix.log",
    "$artifactPrefix*_phpcbf_after_summary.txt"
) | ForEach-Object {
    Get-ChildItem $_ -ErrorAction SilentlyContinue | ForEach-Object { Write-Host "Artifact: $($_.FullName)" }
}

# Show git status
Write-Host "Git status after PHPCBF:"
git status --short
Write-Host "`nGit diff --stat after PHPCBF:"
git diff --stat