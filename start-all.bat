@echo off
REM Script para iniciar Backend + Frontend simultáneamente

setlocal enabledelayedexpansion

echo.
echo ╔════════════════════════════════════════════════╗
echo ║     TEMP SEGURA - Iniciando Servidores        ║
echo ║        Backend PHP + Frontend React           ║
echo ╚════════════════════════════════════════════════╝
echo.

cd /d "%~dp0"

REM Verificar requisitos
echo [1/4] Verificando requisitos...
echo.

php -v >nul 2>&1
if errorlevel 1 (
    echo ❌ ERROR: PHP no encontrado
    echo    Instala PHP desde: https://www.php.net/downloads
    pause
    exit /b 1
)
echo ✓ PHP encontrado

node -v >nul 2>&1
if errorlevel 1 (
    echo ❌ ERROR: Node.js no encontrado
    echo    Instala desde: https://nodejs.org/
    pause
    exit /b 1
)
echo ✓ Node.js encontrado

mysql -v >nul 2>&1
if errorlevel 1 (
    echo ⚠️  ADVERTENCIA: MySQL no en PATH
    echo    Asegúrate de que MySQL esté corriendo
)
echo ✓ MySQL verificado
echo.

REM Preparar backend
echo [2/4] Preparando Backend...
echo.

if not exist "backend\vendor" (
    echo ⚠️  vendor/ no encontrado
    echo    Ejecuta manualmente: cd backend && composer install
)

if not exist "backend\.env" (
    echo ⚠️  backend\.env no encontrado
    echo    Crea el archivo .env con tu configuración
)

echo ✓ Backend listo
echo.

REM Preparar frontend
echo [3/4] Preparando Frontend...
echo.

if not exist "Frontend\node_modules" (
    echo instalando dependencias (esto puede tomar un minuto)...
    cd Frontend
    call npm install >nul 2>&1
    cd ..
)

if not exist "Frontend\.env.local" (
    echo ⚠️  Frontend\.env.local no encontrado
    echo    Crea el archivo .env.local
)

echo ✓ Frontend listo
echo.

REM Iniciar servidores
echo [4/4] Iniciando servidores...
echo.
echo ╔════════════════════════════════════════════════╗
echo ║           SERVIDORES EN EJECUCIÓN             ║
echo ║                                               ║
echo ║  Backend (PHP):  http://localhost:8000        ║
echo ║  API:            http://localhost:8000/api    ║
echo ║  Frontend (UI):  http://localhost:5173        ║
echo ║                                               ║
echo ║  Presiona CTRL+C en cualquier terminal para   ║
echo ║  detener ese servidor                        ║
echo ╚════════════════════════════════════════════════╝
echo.

REM Iniciar en nuevas ventanas
echo Abriendo Backend en nueva ventana...
start "TEMP SEGURA - Backend" cmd /k "%~dp0start-backend.bat"

timeout /t 2 /nobreak

echo Abriendo Frontend en nueva ventana...
start "TEMP SEGURA - Frontend" cmd /k "%~dp0start-frontend.bat"

echo.
echo ✓ Ambos servidores iniciados
echo.
echo Abre tu navegador en: http://localhost:5173
echo.
pause
