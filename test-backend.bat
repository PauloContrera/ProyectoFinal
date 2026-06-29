@echo off
setlocal EnableExtensions

cd /d "%~dp0"

if "%BACKEND_TEST_BASE_URL%"=="" set "BACKEND_TEST_BASE_URL=http://127.0.0.1:8000/api"
if "%ESP_TEST_BASE_URL%"=="" set "ESP_TEST_BASE_URL=%BACKEND_TEST_BASE_URL%"

echo.
echo ========================================
echo TEMP SEGURA - Suite backend integral
echo ========================================
echo.
echo Base API: %BACKEND_TEST_BASE_URL%
echo.
echo Requisito: el backend debe estar corriendo.
echo Para levantarlo:
echo   cd Backend
echo   php -S 127.0.0.1:8000 -t public
echo.

where php >nul 2>&1
if errorlevel 1 (
  echo ERROR: PHP no esta en PATH.
  exit /b 1
)

pushd "%~dp0Backend"
php tests\run_backend_suite.php
set "SUITE_EXIT=%ERRORLEVEL%"
popd

if not "%SUITE_EXIT%"=="0" (
  echo.
  echo ERROR: la suite backend fallo.
  exit /b %SUITE_EXIT%
)

echo.
echo OK: suite backend completa.
exit /b 0
