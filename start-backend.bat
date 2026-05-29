@echo off
REM Script para iniciar Backend PHP

echo ========================================
echo TEMP SEGURA - Backend PHP
echo ========================================
echo.

REM Cambiar a carpeta backend
cd /d "%~dp0backend"

REM Verificar que PHP existe
php -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP no encontrado
    echo Asegúrate de tener PHP instalado y en PATH
    pause
    exit /b 1
)

echo ✓ PHP encontrado
echo.

REM Verificar que Composer instaló dependencias
if not exist "vendor" (
    echo ERROR: vendor/ no encontrado
    echo Ejecuta: composer install
    pause
    exit /b 1
)

echo ✓ Dependencias instaladas
echo.

REM Verificar .env
if not exist ".env" (
    echo ADVERTENCIA: Archivo .env no encontrado
    echo Copia de .env.example o crea uno manualmente
    echo.
)

echo ✓ Iniciando servidor PHP...
echo.
echo URL: http://localhost:8000/api
echo Presiona CTRL+C para detener
echo.

php -S localhost:8000 -t public

pause
