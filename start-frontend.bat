@echo off
REM Script para iniciar Frontend React

echo ========================================
echo TEMP SEGURA - Frontend React
echo ========================================
echo.

REM Cambiar a carpeta Frontend
cd /d "%~dp0Frontend"

REM Verificar que Node está instalado
node -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: Node.js no encontrado
    echo Descarga e instala Node.js desde: https://nodejs.org/
    pause
    exit /b 1
)

echo ✓ Node.js encontrado:
node -v
echo.

REM Verificar que npm está instalado
npm -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: npm no encontrado
    pause
    exit /b 1
)

echo ✓ npm encontrado:
npm -v
echo.

REM Verificar que node_modules existe
if not exist "node_modules" (
    echo instalando dependencias (primera vez)...
    call npm install
    echo.
)

echo ✓ Dependencias instaladas
echo.

REM Verificar .env.local
if not exist ".env.local" (
    echo ADVERTENCIA: Archivo .env.local no encontrado
    echo Asegúrate de crear .env.local con:
    echo   VITE_API_URL=http://localhost/ProyectoFinal/backend/public/api
    echo   VITE_APP_NAME=Temp Segura
    echo.
)

echo ✓ Iniciando Vite Dev Server...
echo.
echo URL: http://localhost:5173
echo Presiona CTRL+C para detener
echo.

call npm run dev

pause
