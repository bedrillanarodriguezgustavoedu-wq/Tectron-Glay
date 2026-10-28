<#
  Arma la carpeta (y el ZIP) de la tienda lista para subir a un servidor.

  Qué deja fuera y por qué:
    .env, .env.copia-*  llevan el Access Token de Mercado Pago. Nunca salen de tu PC.
    vendor/             79 MB; con -ConVendor viaja y el servidor no necesita Composer.
    node_modules/       solo sirve para compilar; el CSS/JS ya va en public/build.
    public/storage      es un enlace: en el servidor se recrea con artisan storage:link.
    storage/framework/* caché y vistas compiladas de esta PC.
    public/galy_api     en tu PC es un JUNCTION de Windows. Aquí se copia como archivos
                        reales, porque un enlace no sobrevive a un FTP.

  Ejemplos:
    powershell -File deploy\paquetar.ps1 -SoloListar
    powershell -File deploy\paquetar.ps1 -ConVendor
#>
param(
    [switch]$ConVendor,
    [switch]$SoloListar,
    [switch]$SinZip
)

$ErrorActionPreference = 'Stop'

$proyecto = Split-Path -Parent $PSScriptRoot
$destino = Join-Path $PSScriptRoot 'paquete'
$zip = Join-Path $PSScriptRoot ("galy-web-{0}.zip" -f (Get-Date -Format 'yyyyMMdd-HHmm'))

if (-not (Test-Path (Join-Path $proyecto 'artisan'))) {
    throw "No se encontró artisan en $proyecto"
}

if (-not (Test-Path (Join-Path $proyecto 'public\build\manifest.json'))) {
    throw 'Falta public/build/manifest.json. Corre "npm run build" antes de paquetrizar.'
}

if (-not $ConVendor -and -not (Test-Path (Join-Path $proyecto 'vendor\autoload.php'))) {
    Write-Warning 'vendor/ no existe en esta PC: el servidor va a necesitar Composer sí o sí.'
}

$excluirRutas = @(
    (Join-Path $proyecto '.git'),
    (Join-Path $proyecto 'node_modules'),
    (Join-Path $proyecto 'storage\logs'),
    (Join-Path $proyecto 'storage\framework\cache'),
    (Join-Path $proyecto 'storage\framework\sessions'),
    (Join-Path $proyecto 'storage\framework\views'),
    (Join-Path $proyecto 'bootstrap\cache'),
    (Join-Path $proyecto 'tests'),
    (Join-Path $proyecto '.phpunit.cache'),
    (Join-Path $proyecto 'public\storage'),
    (Join-Path $proyecto 'public\hot'),
    (Join-Path $proyecto 'deploy\paquete')
)
if (-not $ConVendor) { $excluirRutas += (Join-Path $proyecto 'vendor') }

$excluirArchivos = @(
    '.env',
    '.env.copia-*',
    '.env.backup',
    'cookies.txt',
    '*.log',
    'auth.json',
    '.phpunit.result.cache',
    '*.sqlite'
)

$robocopyArgs = @($proyecto, $destino, '/MIR', '/R:1', '/W:1', '/NFL', '/NDL', '/NJH', '/NP', '/XF') +
    $excluirArchivos + @('/XD') + $excluirRutas

if ($SoloListar) {
    $robocopyArgs += '/L'
    Write-Host '─ Lo que saldría en el paquete (sin copiar nada) ─' -ForegroundColor Cyan
} else {
    if (Test-Path $destino) { Remove-Item $destino -Recurse -Force }
    Write-Host "─ Copiando a $destino ─" -ForegroundColor Cyan
}

robocopy @robocopyArgs
# Robocopy devuelve 0..7 como éxito; 8 o más es error real.
if ($LASTEXITCODE -ge 8) { throw "robocopy falló con código $LASTEXITCODE" }

if ($SoloListar) { return }

$archivos = Get-ChildItem $destino -Recurse -File -Force
$peso = [math]::Round((($archivos | Measure-Object -Sum Length).Sum / 1MB), 1)
$fugas = $archivos | Where-Object { $_.Name -eq '.env' -or $_.Name -like '.env.copia-*' }
if ($fugas) { throw "Hay archivos con credenciales en el paquete: $($fugas.Name -join ', ')" }

$imagenes = (Get-ChildItem (Join-Path $destino 'public\galy_api\img') -File -ErrorAction SilentlyContinue).Count
if ($imagenes -eq 0) {
    Write-Warning 'El paquete salió SIN fotos en public/galy_api/img. Revisa el junction de C:\xampp\htdocs\galy_api\img.'
}

Write-Host ''
Write-Host ("Archivos: {0} · Peso: {1} MB · fotos compartidas: {2}" -f $archivos.Count, $peso, $imagenes) -ForegroundColor Green

if ($SinZip) { return }

Write-Host "─ Comprimiendo en $zip ─" -ForegroundColor Cyan
Compress-Archive -Path (Join-Path $destino '*') -DestinationPath $zip -CompressionLevel Optimal -Force

$zipPeso = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host "Listo: $zip ($zipPeso MB). Súbelo al servidor y descomprímelo dentro del document root." -ForegroundColor Green
