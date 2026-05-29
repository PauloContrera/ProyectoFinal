@echo off
setlocal EnableExtensions EnableDelayedExpansion

cd /d "%~dp0"

set "ROOT=%~dp0"
set "BACKEND_DIR=%ROOT%Backend"
set "FRONTEND_DIR=%ROOT%Frontend"
set "DB_NAME=temp_segura"
set "DB_DUMP=%ROOT%Database\schema.sql"
set "DB_PROTOCOL=%ROOT%Database\protocol_http_sms.sql"
set "DB_SEED=%ROOT%Database\seed_devtest.sql"
set "DB_AUDIT=%ROOT%Database\security_audit.sql"

echo.
echo ========================================
echo TEMP SEGURA - Setup + Servidores
echo ========================================
echo.

echo [1/5] Verificando herramientas...
where php >nul 2>&1
if errorlevel 1 (
  echo ERROR: PHP no esta en PATH.
  pause
  exit /b 1
)

where node >nul 2>&1
if errorlevel 1 (
  echo ERROR: Node.js no esta en PATH.
  pause
  exit /b 1
)

where npm >nul 2>&1
if errorlevel 1 (
  echo ERROR: npm no esta en PATH.
  pause
  exit /b 1
)

where mysql >nul 2>&1
if errorlevel 1 (
  echo ERROR: mysql no esta en PATH. Inicia XAMPP/MySQL o agrega mysql.exe al PATH.
  pause
  exit /b 1
)

echo OK: PHP, Node, npm y MySQL encontrados.
echo.

echo [2/5] Preparando dependencias...
if not exist "%BACKEND_DIR%\vendor\autoload.php" (
  where composer >nul 2>&1
  if errorlevel 1 (
    echo ERROR: Falta Backend\vendor y composer no esta en PATH.
    pause
    exit /b 1
  )
  echo Instalando dependencias del backend...
  pushd "%BACKEND_DIR%"
  call composer install
  if errorlevel 1 (
    popd
    echo ERROR: composer install fallo.
    pause
    exit /b 1
  )
  popd
) else (
  echo OK: Dependencias del backend listas.
)

if not exist "%FRONTEND_DIR%\node_modules" (
  echo Instalando dependencias del frontend...
  pushd "%FRONTEND_DIR%"
  call npm install
  if errorlevel 1 (
    popd
    echo ERROR: npm install fallo.
    pause
    exit /b 1
  )
  popd
) else (
  echo OK: Dependencias del frontend listas.
)
echo.

echo [3/5] Verificando base de datos...
mysql -u root -e "SELECT 1" >nul 2>&1
if errorlevel 1 (
  echo ERROR: No pude conectar a MySQL con usuario root sin password.
  echo Revisa que MySQL este corriendo y que Backend\.env use DB_USER=root DB_PASS=.
  pause
  exit /b 1
)

for /f "usebackq tokens=*" %%A in (`mysql -u root -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='%DB_NAME%' AND table_name='users';"`) do set "USERS_TABLE_EXISTS=%%A"

if "%USERS_TABLE_EXISTS%"=="0" (
  echo Base %DB_NAME% incompleta o inexistente. Creando e importando dump...
  if not exist "%DB_DUMP%" (
    echo ERROR: No existe el dump "%DB_DUMP%".
    pause
    exit /b 1
  )
  mysql -u root -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  if errorlevel 1 (
    echo ERROR: No se pudo crear la base %DB_NAME%.
    pause
    exit /b 1
  )
  mysql -u root %DB_NAME% < "%DB_DUMP%"
  if errorlevel 1 (
    echo ERROR: No se pudo importar el dump.
    pause
    exit /b 1
  )
) else (
  echo OK: Base %DB_NAME% encontrada.
)

if exist "%DB_PROTOCOL%" (
  echo Aplicando migracion protocolo HTTP/SMS...
  mysql -u root %DB_NAME% < "%DB_PROTOCOL%"
  if errorlevel 1 (
    echo ERROR: No se pudo aplicar "%DB_PROTOCOL%".
    pause
    exit /b 1
  )
) else (
  echo ADVERTENCIA: No existe "%DB_PROTOCOL%". Se saltea migracion HTTP/SMS.
)

if exist "%DB_SEED%" (
  echo Aplicando datos de desarrollo devtest...
  mysql -u root %DB_NAME% < "%DB_SEED%"
  if errorlevel 1 (
    echo ERROR: No se pudo aplicar "%DB_SEED%".
    pause
    exit /b 1
  )
) else (
  echo ADVERTENCIA: No existe "%DB_SEED%". Se saltea seed devtest.
)

if exist "%DB_AUDIT%" (
  echo Aplicando auditoria de seguridad y stock...
  mysql -u root %DB_NAME% < "%DB_AUDIT%"
  if errorlevel 1 (
    echo ERROR: No se pudo aplicar "%DB_AUDIT%".
    pause
    exit /b 1
  )
) else (
  echo ADVERTENCIA: No existe "%DB_AUDIT%". Se saltea auditoria de seguridad.
)
echo.

echo [4/5] Verificando puertos...
powershell -NoProfile -Command "if (Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue) { exit 0 } else { exit 1 }"
if errorlevel 1 (
  echo Abriendo consola Backend en http://localhost:8000/api ...
  start "TEMP SEGURA - Backend" cmd /k "cd /d ""%BACKEND_DIR%"" && php -S localhost:8000 -t public"
) else (
  echo OK: Backend ya esta usando el puerto 8000.
)

powershell -NoProfile -Command "if (Get-NetTCPConnection -LocalPort 5173 -State Listen -ErrorAction SilentlyContinue) { exit 0 } else { exit 1 }"
if errorlevel 1 (
  echo Abriendo consola Frontend en http://localhost:5173 ...
  start "TEMP SEGURA - Frontend" cmd /k "cd /d ""%FRONTEND_DIR%"" && npm run dev -- --host 127.0.0.1"
) else (
  echo OK: Frontend ya esta usando el puerto 5173.
)
echo.

echo [5/5] Listo.
echo.
echo URLs:
echo   Frontend: http://localhost:5173
echo   Backend:  http://localhost:8000/api
echo.
echo Usuario de prueba:
echo   usuario:  devtest
echo   password: TempSegura123
echo.
pause
