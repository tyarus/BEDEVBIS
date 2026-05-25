param(
    [Parameter(Mandatory = $true)]
    [string]$FrontendUrl,
    [string]$Domain = "web-production-d5197.up.railway.app",
    [string]$WebService = "web",
    [string]$DbService = "MySQL",
    [switch]$AddMySqlService,
    [switch]$RotateAppKey,
    [switch]$RunSeed
)

$ErrorActionPreference = "Stop"
$RailwayCli = $null

function Invoke-NativeRailway {
    param(
        [Parameter(ValueFromRemainingArguments = $true)]
        [string[]]$Args
    )

    $previousErrorPreference = $ErrorActionPreference
    $ErrorActionPreference = "Continue"

    try {
        $output = & $RailwayCli @Args 2>&1
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $previousErrorPreference
    }

    return @{
        Output = $output
        ExitCode = $exitCode
    }
}

function Invoke-Railway {
    param(
        [Parameter(ValueFromRemainingArguments = $true)]
        [string[]]$Args
    )

    $result = Invoke-NativeRailway @Args
    if ($result.Output) {
        $result.Output | ForEach-Object { Write-Host $_ }
    }

    if ($result.ExitCode -ne 0) {
        throw "Railway command failed: railway $($Args -join ' ')"
    }
}

function Assert-Command {
    param([string]$Name)
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Command '$Name' tidak ditemukan. Install dulu lalu coba lagi."
    }
}

Assert-Command -Name "php"

$railwayCmd = Get-Command railway.cmd -ErrorAction SilentlyContinue
if ($null -ne $railwayCmd) {
    $RailwayCli = $railwayCmd.Source
} else {
    Assert-Command -Name "railway"
    $RailwayCli = "railway"
}

Write-Host "Memeriksa autentikasi Railway..."
$whoamiResult = Invoke-NativeRailway whoami
if ($whoamiResult.ExitCode -ne 0) {
    throw "Belum login Railway. Jalankan 'railway login' di terminal interaktif kamu lalu ulangi script ini."
}

Write-Host "Memeriksa link project Railway..."
$statusResult = Invoke-NativeRailway status
$statusText = ($statusResult.Output | Out-String)
if ($statusResult.ExitCode -ne 0 -or $statusText -match "Project not found") {
    throw "Project Railway belum ter-link. Jalankan 'railway link' ke project backend kamu lalu ulangi script ini."
}

if ($AddMySqlService) {
    Write-Host "Menambahkan service MySQL..."
    Invoke-Railway add --database mysql
}

Write-Host "Membaca APP_KEY saat ini..."
$appKey = $null
$currentVarsResult = Invoke-NativeRailway variable list --service $WebService -k
if ($currentVarsResult.ExitCode -eq 0 -and $currentVarsResult.Output) {
    $currentAppKeyLine = $currentVarsResult.Output | Where-Object { $_ -match '^APP_KEY=' } | Select-Object -First 1
    if ($currentAppKeyLine) {
        $appKey = ($currentAppKeyLine -replace '^APP_KEY=', '').Trim()
    }
}

if ($RotateAppKey -or [string]::IsNullOrWhiteSpace($appKey)) {
    Write-Host "Generate APP_KEY baru dari Laravel..."
    $appKey = (& php artisan key:generate --show).Trim()
    if ([string]::IsNullOrWhiteSpace($appKey)) {
        throw "Gagal generate APP_KEY."
    }
}

$dbHostRef = '${{' + $DbService + '.MYSQLHOST}}'
$dbPortRef = '${{' + $DbService + '.MYSQLPORT}}'
$dbNameRef = '${{' + $DbService + '.MYSQLDATABASE}}'
$dbUserRef = '${{' + $DbService + '.MYSQLUSER}}'
$dbPassRef = '${{' + $DbService + '.MYSQLPASSWORD}}'
$dbUrlRef = '${{' + $DbService + '.MYSQL_URL}}'

$variables = @(
    "APP_NAME=BeDevbis Marketplace",
    "APP_ENV=production",
    "APP_DEBUG=false",
    "APP_KEY=$appKey",
    "APP_URL=https://$Domain",
    "LOG_CHANNEL=stderr",
    "LOG_LEVEL=info",
    "DB_CONNECTION=mysql",
    "DB_HOST=$dbHostRef",
    "DB_PORT=$dbPortRef",
    "DB_DATABASE=$dbNameRef",
    "DB_USERNAME=$dbUserRef",
    "DB_PASSWORD=$dbPassRef",
    "DB_URL=$dbUrlRef",
    "SESSION_DRIVER=database",
    "CACHE_STORE=database",
    "QUEUE_CONNECTION=database",
    "SANCTUM_TOKEN_EXPIRY_DAYS=7",
    "FRONTEND_URL=$FrontendUrl"
)

Write-Host "Set environment variables di service '$WebService'..."
foreach ($item in $variables) {
    Invoke-Railway variable set --service $WebService --skip-deploys $item
}

Write-Host "Redeploy service..."
Invoke-Railway redeploy --service $WebService --yes

Write-Host "Menampilkan log deploy terbaru..."
$logResult = Invoke-NativeRailway service logs --service $WebService --latest -n 120
if ($logResult.ExitCode -eq 0 -and $logResult.Output) {
    $logResult.Output | ForEach-Object { Write-Host $_ }
} else {
    Write-Host "Gagal ambil log terbaru (timeout jaringan Railway). Lanjut tanpa menghentikan setup."
}

if ($RunSeed) {
    Write-Host "RunSeed dilewati. Untuk host private Railway, jalankan seed dari startup command atau Railway shell di service."
}

Write-Host "Selesai. Verifikasi health endpoint:"
Write-Host "https://$Domain/api/health"
