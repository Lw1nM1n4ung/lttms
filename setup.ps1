<#
.SYNOPSIS
    LTTMS -- Local Tourism and Travel Management System
    One-step XAMPP + MySQL setup for Windows

.DESCRIPTION
    Automatically detects XAMPP or downloads + installs it if missing.
    Starts Apache + MySQL, creates the database, imports the schema
    with demo data, creates upload directories, and opens the app.

.PARAMETER XamppPath
    Where XAMPP is (or should be) installed. Defaults to C:\xampp.

.PARAMETER ProjectPort
    Apache port for the project. Defaults to 80.

.PARAMETER SkipInstall
    If XAMPP is missing, ask before downloading. Skips auto-install.

.PARAMETER SkipBrowser
    Don't open the browser at the end.

.EXAMPLE
    .\setup.ps1

.EXAMPLE
    .\setup.ps1 -XamppPath D:\xampp -ProjectPort 8080

.EXAMPLE
    .\setup.ps1 -SkipInstall -SkipBrowser
#>

param(
    [string]$XamppPath = "C:\xampp",
    [int]$ProjectPort = 80,
    [switch]$SkipInstall,
    [switch]$SkipBrowser
)

$ErrorActionPreference = "Stop"
$ProjectRoot  = $PSScriptRoot
$InstallerExe = "$env:TEMP\xampp-installer.exe"

# -- Helpers ------------------------------------------------------------
function Write-Step   { Write-Host "`n>> $args" -ForegroundColor Cyan }
function Write-OK     { Write-Host "   OK   $args" -ForegroundColor Green }
function Write-Warn   { Write-Host "   WARN $args" -ForegroundColor Yellow }
function Write-Fail   { Write-Host "   FAIL $args" -ForegroundColor Red; Pause; exit 1 }
function Write-Info   { Write-Host "   INFO $args" -ForegroundColor Gray }
function Pause        { if (-not $SkipBrowser) { Write-Host "`n  Press any key to exit..." -ForegroundColor DarkGray; $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") } }

function Test-Admin {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

# -- Header -------------------------------------------------------------
Clear-Host
Write-Host "==============================================================" -ForegroundColor Magenta
Write-Host "  LTTMS -- Local Tourism & Travel Management System" -ForegroundColor Magenta
Write-Host "  XAMPP + MySQL one-step setup for Windows" -ForegroundColor Magenta
Write-Host "==============================================================" -ForegroundColor Magenta

if (-not (Test-Admin)) {
    Write-Warn "Not running as Administrator. XAMPP install and Apache/MySQL"
    Write-Info "service starts may fail. Right-click PowerShell -> Run as Administrator"
    Write-Info "for best results. Continuing anyway..."
    Start-Sleep -Seconds 2
}

# ======================================================================
# STEP 0 -- Find or install XAMPP
# ======================================================================
Write-Step "0/7  Locating XAMPP..."

$HttpdExe  = "$XamppPath\apache\bin\httpd.exe"
$PhpExe    = "$XamppPath\php\php.exe"
$MysqlExe  = "$XamppPath\mysql\bin\mysql.exe"
$MysqlDir  = "$XamppPath\mysql\bin"
$XamppFound = Test-Path $HttpdExe

if (-not $XamppFound) {
    Write-Warn "XAMPP not found at $XamppPath"

    if ($SkipInstall) {
        Write-Fail "XAMPP is required. Install it manually from https://www.apachefriends.org/`n         or re-run without -SkipInstall to auto-install."
    }

    # -- Download -------------------------------------------------------
    Write-Step "0a   Downloading XAMPP..."

    $XamppVersion = "8.2.12"
    $XamppFileName = "xampp-windows-x64-$XamppVersion-0-VS16-installer.exe"
    $SourceForgeUrl = "https://downloads.sourceforge.net/project/xampp/XAMPP%20Windows/$XamppVersion/$XamppFileName"
    $ApacheFriendsUrl = "https://www.apachefriends.org/xampp-files/$XamppVersion/$XamppFileName"

    Write-Info "File: $XamppFileName (~150 MB)"
    Write-Info "This is the official XAMPP installer from Apache Friends."

    # Remove stale partial download
    if (Test-Path $InstallerExe) { Remove-Item $InstallerExe -Force }

    $DownloadOk = $false

    # curl.exe -- ships with Windows 10+, trusted system binary
    if (Get-Command curl.exe -ErrorAction SilentlyContinue) {
        try {
            Write-Info "Downloading via curl..."
            curl.exe -L -o "$InstallerExe" "$SourceForgeUrl" --progress-bar
            $DownloadOk = $true
        } catch {
            Write-Warn "curl download failed, trying mirror..."
            try {
                curl.exe -L -o "$InstallerExe" "$ApacheFriendsUrl" --progress-bar
                $DownloadOk = $true
            } catch {
                Write-Warn "curl mirror also failed: $_"
            }
        }
    }

    # Fallback: Invoke-WebRequest
    if (-not $DownloadOk) {
        try {
            Write-Info "Downloading via Invoke-WebRequest..."
            Invoke-WebRequest -Uri $SourceForgeUrl -OutFile $InstallerExe -UseBasicParsing
            $DownloadOk = $true
        } catch {
            Write-Warn "SourceForge failed, trying Apache Friends direct..."
            try {
                Invoke-WebRequest -Uri $ApacheFriendsUrl -OutFile $InstallerExe -UseBasicParsing
                $DownloadOk = $true
            } catch {
                Write-Fail "All download methods failed.`n         Please install XAMPP manually from https://www.apachefriends.org/"
            }
        }
    }

    # Verify the download
    if (-not (Test-Path $InstallerExe)) {
        Write-Fail "Download failed -- file not found.`n         Please install XAMPP manually from https://www.apachefriends.org/"
    }
    $FileSize = (Get-Item $InstallerExe).Length
    if ($FileSize -lt 50MB) {
        $SizeMB = [math]::Round($FileSize / 1MB)
        Write-Fail "Download incomplete (only $SizeMB MB).`n         Please install XAMPP manually from https://www.apachefriends.org/"
    }
    $SizeMB = [math]::Round($FileSize / 1MB)
    Write-OK "Downloaded -- $SizeMB MB"

    # -- Install --------------------------------------------------------
    Write-Step "0b   Installing XAMPP to $XamppPath (unattended)..."

    Write-Info "This will take 2-5 minutes. Please wait..."
    Write-Info "If Windows SmartScreen pops up, click 'More info' -> 'Run anyway'"

    $InstallArgs = @(
        "--mode", "unattended",
        "--disable-components", "xampp_mercury,xampp_tomcat,xampp_filezilla,xampp_perl,xampp_webalizer",
        "--launchapps", "0"
    )

    $InstallProc = Start-Process -FilePath $InstallerExe `
                                 -ArgumentList $InstallArgs `
                                 -Wait -NoNewWindow -PassThru

    if ($InstallProc.ExitCode -ne 0) {
        Write-Warn "Unattended install returned code $($InstallProc.ExitCode)"
        Write-Info "Trying with GUI installer..."
        Start-Process -FilePath $InstallerExe -Wait
    }

    # Clean up installer
    Remove-Item $InstallerExe -Force -ErrorAction SilentlyContinue

    if (-not (Test-Path $HttpdExe)) {
        Write-Fail "Installation did not produce $HttpdExe`n         Please install XAMPP manually from https://www.apachefriends.org/"
    }
    Write-OK "XAMPP installed successfully"
}

# -- Verify paths -------------------------------------------------------
Write-OK "Apache -- $HttpdExe"

if (-not (Test-Path $PhpExe))   { Write-Fail "PHP CLI not found at $PhpExe" }
if (-not (Test-Path $MysqlExe)) { Write-Fail "MySQL CLI not found at $MysqlExe" }

$PhpVersion = & $PhpExe -v 2>&1 | Select-Object -First 1
Write-OK "PHP    -- $PhpVersion"

# ======================================================================
# STEP 1 -- Stop stale services (clean slate)
# ======================================================================
Write-Step "1/7  Stopping any stale Apache / MySQL instances..."

# Kill any existing httpd / mysqld that might conflict
Get-Process -Name httpd -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Get-Process -Name mysqld -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 1

# XAMPP's own stop scripts, if they exist
if (Test-Path "$XamppPath\apache_stop.bat")  { cmd /c """$XamppPath\apache_stop.bat"""  2>&1 | Out-Null }
if (Test-Path "$XamppPath\mysql_stop.bat")   { cmd /c """$XamppPath\mysql_stop.bat"""   2>&1 | Out-Null }
Write-OK "Services stopped"

# ======================================================================
# STEP 2 -- Start Apache + MySQL
# ======================================================================
Write-Step "2/7  Starting Apache + MySQL..."

# Apache
cmd /c """$HttpdExe"" -k start" 2>&1 | Out-Null
Start-Sleep -Seconds 1
Write-OK "Apache started"

# MySQL
$MysqlBat = "$XamppPath\mysql_start.bat"
if (Test-Path $MysqlBat) {
    Start-Process cmd -ArgumentList "/c ""$MysqlBat""" -WindowStyle Hidden -Wait
} else {
    Start-Process "$MysqlDir\mysqld.exe" `
        -ArgumentList "--defaults-file=""$MysqlDir\my.ini""" `
        -WindowStyle Hidden
}
Start-Sleep -Seconds 4

$MysqlRunning = Get-Process -Name mysqld -ErrorAction SilentlyContinue
if ($MysqlRunning) {
    Write-OK "MySQL started (PID $($MysqlRunning.Id))"
} else {
    # Last resort: open XAMPP control panel
    Write-Warn "MySQL may not have started automatically."
    Write-Info "Opening XAMPP Control Panel -- click 'Start' for Apache & MySQL"
    Start-Process "$XamppPath\xampp-control.exe"
    Write-Info "Waiting 10 seconds for you to click Start..."
    Start-Sleep -Seconds 10
}

# ======================================================================
# STEP 3 -- Verify connectivity
# ======================================================================
Write-Step "3/7  Verifying connectivity..."

# Apache
try {
    $null = Invoke-WebRequest -Uri "http://localhost:$ProjectPort" -TimeoutSec 5 -ErrorAction Stop
    Write-OK "Apache responding on port $ProjectPort"
} catch {
    try {
        $null = Invoke-WebRequest -Uri "http://localhost:8080" -TimeoutSec 5 -ErrorAction Stop
        Write-OK "Apache responding on port 8080"
        $ProjectPort = 8080
    } catch {
        Write-Warn "Apache not reachable. Check XAMPP Control Panel."
    }
}

# MySQL
$MysqlArgs = @("-u", "root")
$Test = cmd /c """$MysqlExe"" -u root -e ""SELECT 1"" 2>&1"
if ($LASTEXITCODE -ne 0) {
    $MysqlArgs = @("-u", "root", "--password=")
    $Test = cmd /c """$MysqlExe"" -u root --password= -e ""SELECT 1"" 2>&1"
}
if ($LASTEXITCODE -eq 0) {
    Write-OK "MySQL accessible as root"
} else {
    Write-Warn "Cannot connect to MySQL as root (password-protected?)"
    Write-Info "Skipping database import. Run sql\schema.sql manually:"
    Write-Info "  mysql -u root -p < sql\schema.sql"
}

# ======================================================================
# STEP 4 -- Create database + import schema
# ======================================================================
if ($LASTEXITCODE -eq 0) {
    Write-Step "4/7  Creating database lttms_db..."

    cmd /c """$MysqlExe"" $MysqlArgs -e ""DROP DATABASE IF EXISTS lttms_db"" 2>&1" | Out-Null
    cmd /c """$MysqlExe"" $MysqlArgs -e ""CREATE DATABASE lttms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"" 2>&1" | Out-Null
    if ($LASTEXITCODE -ne 0) { Write-Fail "Failed to create database." }
    Write-OK "Database lttms_db created"

    # -- Import schema --------------------------------------------------
    Write-Step "5/7  Importing schema + demo data..."

    $SchemaFile = "$ProjectRoot\sql\schema.sql"
    $ImportResult = cmd /c """$MysqlExe"" $MysqlArgs lttms_db < ""$SchemaFile"" 2>&1"
    if ($LASTEXITCODE -ne 0) {
        Write-Warn "Schema import had warnings (may be normal):"
        Write-Info ($ImportResult -split "`n" | Select-Object -First 3)
    }
    $SchemaLines = (Get-Content $SchemaFile | Measure-Object -Line).Lines
    Write-OK "Schema imported  -- $SchemaLines lines"
    Write-OK "Demo data loaded -- 1 admin, 1 agent, 10 hotels, 8 transport, 8 packages, 6 destinations"
}

# ======================================================================
# STEP 5 -- PHP extensions check
# ======================================================================
Write-Step "6/7  Checking PHP extensions..."

$RequiredExt = @("pdo_mysql", "mysqli", "mbstring", "curl", "gd", "fileinfo")
$MissingExt = @()
foreach ($ext in $RequiredExt) {
    $check = & $PhpExe -r "exit(extension_loaded('$ext') ? 0 : 1);" 2>&1
    if ($LASTEXITCODE -ne 0) { $MissingExt += $ext }
}
if ($MissingExt) {
    Write-Warn "Missing PHP extensions: $($MissingExt -join ', ')"
    Write-Info "Edit $XamppPath\php\php.ini and uncomment the extension= lines,"
    Write-Info "then restart Apache (XAMPP Control Panel -> Apache -> Stop -> Start)"
}
if (-not $MissingExt) { Write-OK "All required extensions loaded" }

# ======================================================================
# STEP 6 -- Create upload directories
# ======================================================================
Write-Step "7/7  Creating upload directories..."

$UploadDirs = @(
    "$ProjectRoot\uploads\packages",
    "$ProjectRoot\uploads\nrc",
    "$ProjectRoot\uploads\destinations"
)

# Root uploads .htaccess
@"
Deny from all
php_flag engine off
"@ | Out-File -FilePath "$ProjectRoot\uploads\.htaccess" -Encoding ASCII -Force

foreach ($Dir in $UploadDirs) {
    if (-not (Test-Path $Dir)) {
        New-Item -ItemType Directory -Path $Dir -Force | Out-Null
    }
    $HtContent = if ($Dir -match "\\nrc$") {
        "Deny from all"
    } else {
@"
Allow from all
php_flag engine off
"@
    }
    $HtContent | Out-File -FilePath "$Dir\.htaccess" -Encoding ASCII -Force
    Write-OK "$($Dir.Replace($ProjectRoot, '').TrimStart('\')) created"
}

# ======================================================================
# DONE
# ======================================================================
$HtdocsPath  = "$XamppPath\htdocs"
$ProjectName = "lttms"
$TargetPath  = "$HtdocsPath\$ProjectName"
$InHtdocs    = Test-Path "$TargetPath\index.php"

Write-Host "`n==============================================================" -ForegroundColor Green
Write-Host   "  [OK]  Setup Complete!" -ForegroundColor Green
Write-Host   "==============================================================" -ForegroundColor Green

Write-Host "`n  Demo Accounts (password for all: password)" -ForegroundColor White
Write-Host "    Admin    -- admin   / password"
Write-Host "    Agent    -- agent1  / password"
Write-Host "    Customer -- register at /register.php"

Write-Host "`n  Access:" -ForegroundColor White

if ($InHtdocs) {
    $Url = "http://localhost:$ProjectPort/$ProjectName"
    Write-Host "    $Url" -ForegroundColor Cyan
    Write-Host "    DB Setup: $Url/setup.php" -ForegroundColor Cyan

    if (-not $SkipBrowser) {
        Start-Process $Url
    }
} else {
    Write-Host "    Project not under XAMPP htdocs. Options:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "    A) Copy project to htdocs:" -ForegroundColor White
    Write-Host "       Copy-Item -Recurse ""$ProjectRoot"" ""$TargetPath""" -ForegroundColor Gray
    Write-Host ""
    Write-Host "    B) Use PHP built-in server (quick test):" -ForegroundColor White
    Write-Host "       cd ""$ProjectRoot""" -ForegroundColor Gray
    Write-Host "       & ""$PhpExe"" -S localhost:8000" -ForegroundColor Gray
    Write-Host "       # Then open http://localhost:8000" -ForegroundColor Gray
    Write-Host ""

    if (-not $SkipBrowser) {
        Write-Info "Starting PHP built-in server on port 8000..."
        Start-Process $PhpExe -ArgumentList "-S", "localhost:8000", "-t", $ProjectRoot
        Start-Sleep -Seconds 1
        Start-Process "http://localhost:8000"
    }
}

Pause
