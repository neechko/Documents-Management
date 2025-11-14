<#
start-ngrok.ps1
Helper script to create a local ngrok config and start a named tunnel.

Usage examples:
  # Start tunnel for default port 80 with basic auth
  .\start-ngrok.ps1 -Port 80 -Auth "admin:strongpass"

  # Use a custom ngrok executable path
  .\start-ngrok.ps1 -NgrokPath "C:\tools\ngrok.exe" -Port 8080

Notes:
- This script writes a config to $env:USERPROFILE\.ngrok2\ngrok.yml (overwrites if exists)
- It will prompt for your ngrok authtoken if not present in environment variable NGROK_AUTHTOKEN
- Do NOT commit your authtoken into the repository
#>

param(
    [int]$Port = 80,
    [string]$NgrokPath = "ngrok",
    [string]$TunnelName = "document-management",
    [string]$Auth = ""   # optional basic auth in form user:pass
)

function Write-Config {
    param($Path, $Lines)
    $Lines -join "`n" | Out-File -Encoding utf8 -FilePath $Path
}

# Resolve ngrok path (allow ngrok in PATH or full path)
if ($NgrokPath -ne "ngrok" -and -not (Test-Path $NgrokPath)) {
    Write-Error "ngrok executable not found at: $NgrokPath"
    exit 1
}

$home = $env:USERPROFILE
$configDir = Join-Path $home ".ngrok2"
if (-not (Test-Path $configDir)) { New-Item -ItemType Directory -Path $configDir | Out-Null }
$configFile = Join-Path $configDir "ngrok.yml"

$token = $env:NGROK_AUTHTOKEN
if (-not $token -or $token -eq "") {
    $token = Read-Host -AsSecureString "Enter your ngrok authtoken (input hidden)"
    # convert secure string to plain for writing to config
    $bstr = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($token)
    $tokenPlain = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
    [System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    $token = $tokenPlain
}

# Build config content
$config = @()
$config += "authtoken: $token"
$config += "tunnels:"
$config += "  $TunnelName:"
$config += "    proto: http"
$config += "    addr: $Port"
if ($Auth -and $Auth -ne "") {
    # ensure quotes around auth value
    $config += "    auth: \"$Auth\""
}

# Write config
Write-Config -Path $configFile -Lines $config
Write-Host "Wrote ngrok config to $configFile" -ForegroundColor Green

# Resolve ngrok executable
$ngrokExe = $NgrokPath
if ($NgrokPath -eq 'ngrok') {
    $cmd = Get-Command ngrok -ErrorAction SilentlyContinue
    if ($cmd) { $ngrokExe = $cmd.Source } else { $ngrokExe = 'ngrok' }
}

# Start ngrok process
Write-Host "Starting ngrok tunnel '$TunnelName' -> http://localhost:$Port" -ForegroundColor Cyan
try {
    $proc = Start-Process -FilePath $ngrokExe -ArgumentList "start $TunnelName" -WindowStyle Minimized -PassThru
} catch {
    Write-Error "Failed to start ngrok. Ensure ngrok is installed and accessible (NgrokPath=$NgrokExe).";
    exit 1
}

# Poll local ngrok API for public URL
$api = 'http://127.0.0.1:4040/api/tunnels'
$publicUrl = $null
$wait = 0
Write-Host "Waiting for ngrok to become available (web API at $api)..." -ForegroundColor Yellow
while ($wait -lt 30 -and -not $publicUrl) {
    Start-Sleep -Seconds 1
    try {
        $resp = Invoke-RestMethod -Uri $api -Method Get -ErrorAction Stop
        if ($resp.tunnels -and $resp.tunnels.Count -gt 0) {
            # prefer https tunnel
            $t = $resp.tunnels | Where-Object { $_.public_url -like 'https:*' } | Select-Object -First 1
            if (-not $t) { $t = $resp.tunnels[0] }
            $publicUrl = $t.public_url
        }
    } catch {
        # ignore until API ready
    }
    $wait++
}

if ($publicUrl) {
    Write-Host "ngrok public URL: $publicUrl" -ForegroundColor Green
    # Open public URL and ngrok web UI
    Start-Process $publicUrl
    Start-Process 'http://127.0.0.1:4040'
} else {
    Write-Warning "ngrok did not report a public URL within the timeout. Check ngrok console or logs.";
    Start-Process 'http://127.0.0.1:4040'
}
