#!/usr/bin/env pwsh
# Step 1: CodeGraph index

$repoRoot = Get-Location

# Check if .codegraph exists
if (Test-Path "$repoRoot\.codegraph") {
    Write-Host "Step 1 skipped (index present)"
    exit 0
}

# Check if codegraph is available
if (!(Get-Command "codegraph" -ErrorAction SilentlyContinue)) {
    Write-Host "Error: codegraph not found on PATH. Install per README § Setup"
    exit 1
}

# Run codegraph init
Write-Host "Running codegraph init --local-only..."
codegraph init --local-only

if ($LASTEXITCODE -eq 0) {
    Write-Host "Step 1 completed: CodeGraph index built"
} else {
    Write-Host "Step 1 failed: codegraph init exited with code $LASTEXITCODE"
    exit 1
}