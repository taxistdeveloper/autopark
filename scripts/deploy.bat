@echo off
setlocal
cd /d "%~dp0.."

echo ==^> git pull
git pull --ff-only
if errorlevel 1 (
  echo git pull не удался
  exit /b 1
)

set "PHP_BIN="
where php >nul 2>&1 && set "PHP_BIN=php"

if not defined PHP_BIN (
  for %%V in (8.3.1 8.3.0 8.2.14 8.1.0 8.0.1 7.4.16) do (
    if exist "C:\MAMP\bin\php\php%%V\php.exe" (
      set "PHP_BIN=C:\MAMP\bin\php\php%%V\php.exe"
      goto :havephp
    )
  )
)

:havephp
if not defined PHP_BIN (
  echo PHP не найден. Changelog соберётся при первом заходе на главную.
  exit /b 0
)

echo ==^> rebuild changelog
"%PHP_BIN%" "%CD%\scripts\rebuild_changelog.php"
if errorlevel 1 exit /b 1
echo ==^> готово
exit /b 0
