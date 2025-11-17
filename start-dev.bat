@echo off
REM NextCloud 企业微信插件 - 一键启动开发环境
REM 双击此文件即可启动

echo ========================================
echo   NextCloud 企业微信插件开发环境
echo ========================================
echo.

REM 检查 Docker
echo [1/3] 检查 Docker...
docker --version >nul 2>&1
if errorlevel 1 (
    echo [错误] Docker 未安装或未运行
    echo 请先安装 Docker Desktop: https://www.docker.com/products/docker-desktop/
    pause
    exit /b 1
)

docker ps >nul 2>&1
if errorlevel 1 (
    echo [错误] Docker 未运行
    echo 请启动 Docker Desktop 后重试
    pause
    exit /b 1
)

echo [成功] Docker 正在运行
echo.

REM 检查 Node.js
echo [2/3] 检查 Node.js...
node --version >nul 2>&1
if errorlevel 1 (
    echo [错误] Node.js 未安装
    echo 请先安装 Node.js: https://nodejs.org/
    pause
    exit /b 1
)

echo [成功] Node.js 已安装
echo.

REM 启动环境
echo [3/3] 启动开发环境...
echo.
echo 这将需要 2-3 分钟，请耐心等待...
echo.

powershell -ExecutionPolicy Bypass -File dev-setup.ps1

if errorlevel 1 (
    echo.
    echo [失败] 启动过程出现错误
    echo 请查看上面的错误信息
    pause
    exit /b 1
)

echo.
echo ========================================
echo   启动完成！
echo ========================================
echo.
echo 访问地址: http://localhost:8080
echo 管理账号: admin / admin123
echo.
echo 浏览器应该会自动打开，如果没有请手动访问上面的地址
echo.

REM 尝试打开浏览器
start http://localhost:8080

pause

