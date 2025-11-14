<#
install-ngrok-schtask.ps1
Create a Scheduled Task that runs the repository's start-ngrok.ps1 at user logon.

Usage (from repo root):
    .\scripts\install-ngrok-schtask.ps1 -Port 80 -NgrokPath "C:\tools\ngrok.exe"

Notes:
- This registers a task for the current user at logon.
- The task action runs PowerShell to execute start-ngrok.ps1 with provided arguments.
- Prefer setting your ngrok authtoken beforehand (see README) or set NGROK_AUTHTOKEN env var.
- To remove the task later, use:
    Unregister-ScheduledTask -TaskName "StartNgrokDocumentManagement" -Confirm:$false
#>

param(
    [int]$Port = 80,
    [string]$StartScriptPath = "$PSScriptRoot\start-ngrok.ps1",
    [string]$NgrokPath = "ngrok",
    [string]$Auth = "",
    [string]$TaskName = "StartNgrokDocumentManagement"
)

function Show-ErrorAndExit($msg) {
    Write-Error $msg
    exit 1
}

# Resolve paths
if (-not (Test-Path $StartScriptPath)) {
    Show-ErrorAndExit "Start script not found at: $StartScriptPath`nRun this script from repository root or pass -StartScriptPath explicitly."
}

$startScript = (Resolve-Path $StartScriptPath).Path

# Build argument string for powershell.exe
$args = @()
$args += "-NgrokPath `"$NgrokPath`""
$args += "-Port $Port"
if ($Auth -and $Auth -ne '') { $args += "-Auth `"$Auth`"" }

$psArgs = "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$startScript`" " + ($args -join ' ')

Write-Host "Will create scheduled task '$TaskName' that runs:`n    powershell.exe $psArgs`n" -ForegroundColor Cyan

# Create scheduled task trigger and action
try {
    $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument $psArgs
    $trigger = New-ScheduledTaskTrigger -AtLogOn

    # Register for current user without requiring password
    Register-ScheduledTask -TaskName $TaskName -Trigger $trigger -Action $action -User $env:USERNAME -RunLevel Limited -Force
    Write-Host "Scheduled task '$TaskName' created for user $env:USERNAME." -ForegroundColor Green
    Write-Host "You can verify in Task Scheduler (Task Scheduler Library) or run:`nschtasks /Query /TN $TaskName`n" -ForegroundColor Green
} catch {
    Write-Warning "Register-ScheduledTask failed: $_.Exception.Message"
    Write-Host "Falling back to schtasks.exe command (may require different privileges)." -ForegroundColor Yellow

    # Build schtasks command
    $escaped = $psArgs -replace '"','\"'
    $command = "schtasks /Create /SC ONLOGON /TN \"$TaskName\" /TR \"powershell.exe $escaped\" /F"
    Write-Host "Run the following command (in an elevated PowerShell if required):`n`n$command`n" -ForegroundColor Cyan
}

Write-Host "If the scheduled task runs but ngrok still prompts for authtoken, set your authtoken once locally using:`n  C:\tools\ngrok.exe authtoken <YOUR_NGROK_AUTHTOKEN>`nOr set environment variable NGROK_AUTHTOKEN for your user." -ForegroundColor Yellow
