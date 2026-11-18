#!/usr/bin/env pwsh
# Main orchestration script for PHP 5 to 8 upgrade v2 hook

param(
    [string]$Task = "all"
)

$repoRoot = Get-Location

# Parse task
$taskLower = $Task.ToLower().Trim()

# Precondition checks
function Test-Preconditions {
    param(
        [string]$CheckIndex = $false
    )
    
    # Check composer.json
    if (!(Test-Path "$repoRoot\composer.json")) {
        Write-Host "Error: composer.json not found at repo root"
        return $false
    }
    
    # Check vendor directory (except for index-only task)
    if ($CheckIndex -ne "index" -and !(Test-Path "$repoRoot\vendor")) {
        Write-Host "Error: vendor/ not found. Run 'composer install' first"
        return $false
    }
    
    # Check PHP CLI
    $phpVersion = php -v 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Error: PHP CLI not available"
        return $false
    }
    
    return $true
}

# Git diff validation gate
function Invoke-GitGate {
    param(
        [string]$Task
    )
    
    if ($Task -eq "index") {
        Write-Host "Git gate skipped (index-only task)"
        return
    }
    
    Write-Host "`n=== Git Diff Validation Gate ==="
    Write-Host "Running git diff HEAD..."
    
    $diffOutput = git diff HEAD -- "*.php" "*.inc" 2>&1
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Error: git diff failed"
        return
    }
    
    # Check if any PHP files were modified
    $modifiedFiles = git diff HEAD --name-only -- "*.php" "*.inc"
    
    if ($null -eq $modifiedFiles -or $modifiedFiles.Count -eq 0) {
        Write-Host "Git gate skipped (no PHP/INC changes)"
        return
    }
    
    Write-Host "Modified PHP/INC files detected. Running gate steps..."
    # Gate steps would be implemented here per git-diff-validation.md
    Write-Host "Gate validation pending implementation"
}

# Execute steps
$stepsRun = @()
$stepsToSkip = @()

Write-Host "=== PHP 5 to 8 Upgrade v2 ==="
Write-Host "Task: $Task"

if (!(Test-Preconditions -CheckIndex $taskLower)) {
    exit 1
}

switch ($taskLower) {
    "all" {
        # Run all steps
        & "$repoRoot\.kiro\hooks\php5-to-8-step-1-index.ps1"
        $stepsRun += "Step 1"
        
        & "$repoRoot\.kiro\hooks\php5-to-8-step-2-find-problems.ps1"
        $stepsRun += "Step 2"
        
        & "$repoRoot\.kiro\hooks\php5-to-8-step-3-phpcbf.ps1"
        $stepsRun += "Step 3"
        
        & "$repoRoot\.kiro\hooks\php5-to-8-step-4-rector.ps1"
        $stepsRun += "Step 4"
        
        & "$repoRoot\.kiro\hooks\php5-to-8-step-5-phpstan.ps1"
        $stepsRun += "Step 5"
        
        Invoke-GitGate -Task "all"
    }
    
    { $_ -in @("1","2","3","4","5") } {
        $startStep = [int]$taskLower
        
        if ($startStep -le 1) {
            & "$repoRoot\.kiro\hooks\php5-to-8-step-1-index.ps1"
            $stepsRun += "Step 1"
        }
        
        if ($startStep -le 2) {
            & "$repoRoot\.kiro\hooks\php5-to-8-step-2-find-problems.ps1"
            $stepsRun += "Step 2"
        }
        
        if ($startStep -le 3) {
            & "$repoRoot\.kiro\hooks\php5-to-8-step-3-phpcbf.ps1"
            $stepsRun += "Step 3"
        }
        
        if ($startStep -le 4) {
            & "$repoRoot\.kiro\hooks\php5-to-8-step-4-rector.ps1"
            $stepsRun += "Step 4"
        }
        
        if ($startStep -le 5) {
            & "$repoRoot\.kiro\hooks\php5-to-8-step-5-phpstan.ps1"
            $stepsRun += "Step 5"
        }
        
        Invoke-GitGate -Task $taskLower
    }
    
    "reports-only" {
        # Steps 2, 4, 5 only (no PHPCBF, no Rector apply)
        & "$repoRoot\.kiro\hooks\php5-to-8-step-2-find-problems.ps1"
        $stepsRun += "Step 2"
        
        & "$repoRoot\.kiro\hooks\php5-to-8-step-4-rector.ps1"
        $stepsRun += "Step 4"
        
        & "$repoRoot\.kiro\hooks\php5-to-8-step-5-phpstan.ps1"
        $stepsRun += "Step 5"
        
        Invoke-GitGate -Task "reports-only"
    }
    
    "index" {
        & "$repoRoot\.kiro\hooks\php5-to-8-step-1-index.ps1"
        $stepsRun += "Step 1"
        Write-Host "Git gate skipped (index-only task)"
    }
    
    default {
        Write-Host "Unrecognized task: $Task. Assuming 'all'."
        # Default to all
        & "$repoRoot\.kiro\hooks\php5-to-8-step-1-index.ps1"
        $stepsRun += "Step 1"
        
        & "$repoRoot\.kiro\hooks\php5-to-8-step-2-find-problems.ps1"
        $stepsRun += "Step 2"
        
        & "$repoRoot\.kiro\hooks\php5-to-8-step-3-phpcbf.ps1"
        $stepsRun += "Step 3"
        
        & "$repoRoot\.kiro\hooks\php5-to-8-step-4-rector.ps1"
        $stepsRun += "Step 4"
        
        & "$repoRoot\.kiro\hooks\php5-to-8-step-5-phpstan.ps1"
        $stepsRun += "Step 5"
        
        Invoke-GitGate -Task "all"
    }
}

Write-Host "`n=== Summary ==="
Write-Host "Steps run: $($stepsRun -join ', ')"