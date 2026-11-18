#!/usr/bin/env pwsh
# Step 4: Modernize code with Rector

$repoRoot = Get-Location
Set-Location $repoRoot

Write-Host "Rector may change source files. Running..."

composer rector:report

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: composer rector:report failed"
    exit 1
}

# Find rector report files
$reportsFolder = "$repoRoot\reports"
$latestRector = Get-ChildItem "$reportsFolder\*_rector_before.json" | Sort-Object Name -Descending | Select-Object -First 1

if ($latestRector) {
    Write-Host "Rector report: $($latestRector.FullName)"
    
    # Read changed_files count
    $beforeJson = Get-Content $latestRector.FullName | ConvertFrom-Json
    $changedFiles = $beforeJson.totals.changed_files
    Write-Host "Changed files: $changedFiles"
    
    # List artifacts
    $baseName = $latestRector.BaseName
    @(
        "$reportsFolder\$baseName.json",
        "$reportsFolder\$($baseName -replace '_before', '_apply').log",
        "$reportsFolder\$($baseName -replace '_before', '_after').json"
    ) | ForEach-Object {
        if (Test-Path $_) { Write-Host "Artifact: $_" }
    }
} else {
    Write-Host "Warning: No rector report found"
}

# Show git diff
Write-Host "`nGit diff --stat after Rector:"
git diff --stat