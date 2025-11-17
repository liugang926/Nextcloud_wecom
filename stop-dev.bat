@echo off
REM NextCloud 企业微信插件 - 停止开发环境

echo ========================================
echo   停止 NextCloud 开发环境
echo ========================================
echo.

docker-compose down

if errorlevel 1 (
    echo [失败] 停止容器失败
    pause
    exit /b 1
)

echo.
echo [成功] 所有容器已停止
echo.
pause

