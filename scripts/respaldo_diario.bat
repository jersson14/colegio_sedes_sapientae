@echo off
REM Respaldo diario de la base del colegio (Windows / XAMPP). Conserva los últimos 30 días.
REM Programarlo en el Programador de tareas de Windows: diario, 23:00, ejecutar este archivo.
REM Ajuste las rutas, el puerto y el usuario si su instalación es distinta.
set MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe
set CARPETA=C:\xampp\htdocs\_public_html\respaldos
set BASE=colegio2
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmm"') do set FECHA=%%i
"%MYSQLDUMP%" --default-character-set=utf8mb4 -uroot -P3307 -h127.0.0.1 --routines --triggers --single-transaction %BASE% > "%CARPETA%\%BASE%_%FECHA%.sql"
if errorlevel 1 (
  echo %DATE% %TIME% ERROR en el respaldo >> "%CARPETA%\respaldos.log"
  exit /b 1
)
echo %DATE% %TIME% respaldo %BASE%_%FECHA%.sql >> "%CARPETA%\respaldos.log"
REM Borra los respaldos automáticos de más de 30 días (los "antes_0NN" de las migraciones no se tocan)
forfiles /p "%CARPETA%" /m "%BASE%_????????_????.sql" /d -30 /c "cmd /c del @path" 2>nul
exit /b 0
