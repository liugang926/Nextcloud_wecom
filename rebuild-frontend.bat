@echo off
REM NextCloud 企业微信插件 - 重新构建前端

echo ========================================
echo   重新构建前端资源
echo ========================================
echo.

REM 检查 Node.js
node --version >nul 2>&1
if errorlevel 1 (
    echo [错误] Node.js 未安装
    pause
    exit /b 1
)

echo [1/2] 构建前端...
call npm run build

if errorlevel 1 (
    echo [失败] 构建失败
    pause
    exit /b 1
)

echo.
echo [2/2] 重启 NextCloud 容器...
docker-compose restart nextcloud

if errorlevel 1 (
    echo [失败] 重启容器失败
    pause
    exit /b 1
)

echo.
echo [成功] 前端重新构建完成
echo.
echo 请在浏览器中强制刷新页面 (Ctrl+F5)
echo.
pause

