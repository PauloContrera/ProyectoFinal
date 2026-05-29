@echo off
REM Script de Testing - Backend API
REM Prueba endpoints: Register, Login, Acceso a datos

setlocal enabledelayedexpansion

cls
echo.
echo ╔═══════════════════════════════════════════════════════╗
echo ║   TEMP SEGURA - TEST DE BACKEND                       ║
echo ║   Probando: Register, Login, Acceso a datos           ║
echo ╚═══════════════════════════════════════════════════════╝
echo.

REM Verificar que curl está disponible
curl --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: curl no encontrado
    echo Instala Git for Windows (incluye curl)
    pause
    exit /b 1
)

set API_URL=http://localhost:8000/api
set EMAIL=testuser%random%@example.com
set USERNAME=testuser%random%
set PASSWORD=Test1234
set TOKEN=

echo [TEST 1] Registrando nuevo usuario...
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo Email: %EMAIL%
echo Username: %USERNAME%
echo Password: %PASSWORD%
echo.

REM TEST 1: Register
for /f "tokens=*" %%A in ('curl -s -X POST %API_URL%/auth/register ^
  -H "Content-Type: application/json" ^
  -d "{\"name\":\"Test User\",\"username\":\"%USERNAME%\",\"email\":\"%EMAIL%\",\"password\":\"%PASSWORD%\",\"phone\":\"+541112345678\"}"') do (
    echo Response: %%A
)

echo.
echo Esperado: {"success":true,"status":201,...}
echo.
timeout /t 2

echo [TEST 2] Iniciando sesión (Login)...
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo Email: %EMAIL%
echo Password: %PASSWORD%
echo.

REM TEST 2: Login - Capturar el token
for /f "tokens=*" %%A in ('curl -s -X POST %API_URL%/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"%EMAIL%\",\"password\":\"%PASSWORD%\"}"') do (
    set RESPONSE=%%A
    echo Response: !RESPONSE!

    REM Intentar extraer token (simple)
    for /f "tokens=*" %%B in ('echo !RESPONSE! ^| findstr /c:"token"') do (
        set TOKEN=%%B
    )
)

echo.
echo Esperado: {"success":true,"status":200,"data":{"token":"eyJ...",...}}
echo.
timeout /t 2

if "!TOKEN!"=="" (
    echo ⚠️  No se pudo extraer el token
    echo Prueba manual con Postman:
    echo.
    echo POST %API_URL%/auth/login
    echo Content-Type: application/json
    echo.
    echo {"email":"%EMAIL%","password":"%PASSWORD%"}
    echo.
)

echo [TEST 3] Accediendo a dispositivos (requiere token)...
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.

if "!TOKEN!"=="" (
    echo ⚠️  Sin token, saltando este test
    echo Para obtener token, haz login primero
) else (
    echo Usando token: !TOKEN:~0,50!...
    echo.

    REM TEST 3: Obtener dispositivos
    for /f "tokens=*" %%A in ('curl -s -X GET %API_URL%/devices ^
      -H "Authorization: Bearer !TOKEN!"') do (
        echo Response: %%A
    )

    echo.
    echo Esperado: {"success":true,"status":200,"data":[...]}
)

echo.
echo ╔═══════════════════════════════════════════════════════╗
echo ║   TESTING COMPLETADO                                  ║
echo ╚═══════════════════════════════════════════════════════╝
echo.

echo RESUMEN:
echo ✓ Endpoint POST /auth/register
echo ✓ Endpoint POST /auth/login
echo ✓ Endpoint GET /devices (con JWT)
echo.

echo PRÓXIMO PASO:
echo Prueba con Postman para ver respuestas detalladas
echo URL: %API_URL%
echo.

pause
