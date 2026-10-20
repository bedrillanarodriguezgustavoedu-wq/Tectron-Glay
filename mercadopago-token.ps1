# Pega tu Access Token de Mercado Pago y deja la tienda lista para usarlo.
#
# El token se escribe oculto, no se muestra en pantalla, no se guarda en el
# historial de PowerShell, no va a ningún chat ni a ningún sitio externo, y se
# hace una copia del .env antes de tocarlo.
#
# Uso:  .\mercadopago-token.ps1

$ErrorActionPreference = 'Stop'
$rutaEnv = Join-Path $PSScriptRoot '.env'

if (-not (Test-Path -LiteralPath $rutaEnv)) {
    Write-Host "No encuentro el archivo de configuración en $rutaEnv" -ForegroundColor Red
    exit 1
}

$secure = Read-Host 'Pega el Access Token (no se ve mientras escribes)' -AsSecureString
$bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
try {
    $token = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
}

$token = $token.Trim()

# Si en vez del token solo se pega la linea completa del .env, o algo con
# etiquetas delante, quedarse igualmente con el valor: es el error mas facil
# de cometer aqui y no hay porque hacerlo fallar por eso.
$token = $token -replace '^\s*(MERCADOPAGO_ACCESS_TOKEN\s*=|access\s*token\s*[:=]?)\s*', ''
$token = $token.Trim().Trim('"', "'")

if ($token -eq '') {
    Write-Host 'No has pegado nada.' -ForegroundColor Red
    exit 1
}

$actual = $null
foreach ($linea in [IO.File]::ReadAllLines($rutaEnv)) {
    if ($linea -match '^\s*MERCADOPAGO_ACCESS_TOKEN\s*=\s*(.*)$') { $actual = $Matches[1].Trim('"') }
}

if ($token -eq $actual) {
    Write-Host 'Ese token ya está puesto: repetirlo da exactamente el mismo resultado de siempre.' -ForegroundColor Yellow
    Write-Host 'Primero copia el que aparece en tu aplicación de Checkout Pro, no el que ya estaba.' -ForegroundColor Yellow
    exit 1
}

if ($token -notmatch '^(TEST|APP_USR)-\d{6,}-\d{4,}-[A-Za-z0-9]{12,}(-\d{4,})?$') {
    Write-Host 'Ese valor no tiene la forma de un Access Token.' -ForegroundColor Red
    Write-Host 'Lo habitual es haber copiado la Public Key (es más corta) o quedase parte del número' -ForegroundColor Red
    Write-Host 'al seleccionar. En la pantalla de credenciales hay un botón de copiar junto al token.' -ForegroundColor Red
    exit 1
}

if ($token -match '(\d)\1{9,}') {
    Write-Host 'Ese token lleva diez dígitos iguales seguidos: los reales son aleatorios.' -ForegroundColor Red
    Write-Host 'Parece escrito a mano. Copia el que te da Mercado Pago con su botón de copiar.' -ForegroundColor Red
    exit 1
}

$copia = "$rutaEnv.copia-" + (Get-Date -Format 'yyyyMMdd-HHmmss')
Copy-Item -LiteralPath $rutaEnv -Destination $copia

$escrito = $false
$lineas = [IO.File]::ReadAllLines($rutaEnv) | ForEach-Object {
    if ($_ -match '^\s*MERCADOPAGO_ACCESS_TOKEN\s*=') { $escrito = $true; "MERCADOPAGO_ACCESS_TOKEN=$token" }
    else { $_ }
}

if (-not $escrito) {
    $lineas = $lineas + "MERCADOPAGO_ACCESS_TOKEN=$token"
}

[IO.File]::WriteAllLines($rutaEnv, [string[]]$lineas)

Write-Host "Guardado. Copia de seguridad del .env anterior en: $copia" -ForegroundColor Green
Write-Host ''

Push-Location $PSScriptRoot
try {
    php artisan config:clear
    php artisan mercadopago:verificar --probar
} finally {
    Pop-Location
}
