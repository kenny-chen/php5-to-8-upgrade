#!/usr/bin/env pwsh
# Step 5: Check types with PHPStan

$repoRoot = Get-Location
Set-Location $repoRoot

Write-Host "Running composer stan:report..."
composer stan:report

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: composer stan:report failed"
    exit 1
}

# Find stan report folder
$reportsFolder = "$repoRoot\reports"
$latestStan = Get-ChildItem "$reportsFolder\*_stan" -Directory | Sort-Object Name -Descending | Select-Object -First 1

if ($latestStan) {
    Write-Host "PHPStan report folder: $($latestStan.FullName)"
    
    # Look for _by_identifier.json
    $byIdentifier = Get-ChildItem "$($latestStan.FullName)\*_by_identifier.json" | Sort-Object Name -Descending | Select-Object -First 1
    if ($byIdentifier) {
        Write-Host "By identifier: $($byIdentifier.FullName)"
        $stanData = Get-Content $byIdentifier.FullName | ConvertFrom-Json
        Write-Host "Total issues: $($stanData.counts.total)"
        
        # Show top identifiers
        if ($stanData.top) {
            Write-Host "Top identifiers:"
            $stanData.top | ForEach-Object { Write-Host "  $_" }
        }
    }
    
    # List report files
    @(
        "$reportsFolder\$($latestStan.Name).json",
        "$reportsFolder\$($latestStan.Name).txt",
        "$($latestStan.FullName)\by_folder\"
    ) | ForEach-Object {
        if (Test-Path $_) { Write-Host "Artifact: $_" }
    }
} else {
    Write-Host "Warning: No PHPStan report folder found"
}