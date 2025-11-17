# NextCloud 企业微信插件 - Docker 开发环境设置脚本
# 适用于 Windows 11 + Docker Desktop

param(
    [switch]$Clean,
    [switch]$Build,
    [switch]$Logs,
    [switch]$Shell,
    [switch]$Install,
    [switch]$Enable,
    [switch]$Status
)

$ErrorActionPreference = "Stop"

# 颜色输出函数
function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Color
}

function Write-Step {
    param([string]$Message)
    Write-ColorOutput "`n==> $Message" "Cyan"
}

function Write-Success {
    param([string]$Message)
    Write-ColorOutput "✅ $Message" "Green"
}

function Write-Error {
    param([string]$Message)
    Write-ColorOutput "❌ $Message" "Red"
}

function Write-Warning {
    param([string]$Message)
    Write-ColorOutput "⚠️  $Message" "Yellow"
}

# 检查 Docker 是否运行
function Test-Docker {
    Write-Step "检查 Docker 环境..."
    try {
        docker --version | Out-Null
        docker-compose --version | Out-Null
        Write-Success "Docker 已安装"
    } catch {
        Write-Error "Docker 未安装或未运行，请先安装 Docker Desktop"
        exit 1
    }

    # 检查 Docker 是否运行
    try {
        docker ps | Out-Null
        Write-Success "Docker 正在运行"
    } catch {
        Write-Error "Docker 未运行，请启动 Docker Desktop"
        exit 1
    }
}

# 清理环境
function Clean-Environment {
    Write-Step "清理现有环境..."
    
    Write-Host "停止容器..."
    docker-compose down -v
    
    Write-Host "清理 Docker 卷..."
    docker volume prune -f
    
    Write-Success "环境清理完成"
}

# 启动服务
function Start-Services {
    Write-Step "启动 NextCloud 开发环境..."
    
    # 复制环境变量文件
    if (-not (Test-Path ".env")) {
        Copy-Item ".env.example" ".env"
        Write-Success "已创建 .env 文件"
    }
    
    # 启动容器
    Write-Host "启动 Docker 容器..."
    docker-compose up -d
    
    Write-Success "容器启动完成"
    Write-Host ""
    Write-Host "等待 NextCloud 初始化（这可能需要 1-2 分钟）..."
    Start-Sleep -Seconds 30
    
    # 等待 NextCloud 就绪
    $maxAttempts = 30
    $attempt = 0
    $ready = $false
    
    while ($attempt -lt $maxAttempts -and -not $ready) {
        try {
            $response = Invoke-WebRequest -Uri "http://localhost:8080/status.php" -UseBasicParsing -TimeoutSec 5
            if ($response.StatusCode -eq 200) {
                $ready = $true
            }
        } catch {
            $attempt++
            Write-Host "." -NoNewline
            Start-Sleep -Seconds 2
        }
    }
    
    Write-Host ""
    if ($ready) {
        Write-Success "NextCloud 已就绪"
    } else {
        Write-Warning "NextCloud 启动超时，请稍后手动检查"
    }
}

# 安装插件依赖
function Install-Plugin {
    Write-Step "安装插件依赖..."
    
    # 检查 Node.js
    if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
        Write-Error "Node.js 未安装，请先安装 Node.js (https://nodejs.org/)"
        exit 1
    }
    
    Write-Host "安装 npm 依赖..."
    npm install
    
    Write-Success "依赖安装完成"
}

# 构建前端
function Build-Frontend {
    Write-Step "构建前端资源..."
    
    Write-Host "运行 Vite 构建..."
    npm run build
    
    if (Test-Path "js/oauthwecom-adminSettings.mjs") {
        Write-Success "前端构建成功"
    } else {
        Write-Error "前端构建失败"
        exit 1
    }
}

# 启用插件
function Enable-Plugin {
    Write-Step "在 NextCloud 中启用插件..."
    
    # 在容器内执行命令
    Write-Host "设置文件权限..."
    docker exec -u www-data nextcloud-dev chown -R www-data:www-data /var/www/html/apps/oauthwecom
    
    Write-Host "启用插件..."
    docker exec -u www-data nextcloud-dev php occ app:enable oauthwecom
    
    Write-Success "插件已启用"
}

# 查看日志
function Show-Logs {
    Write-Step "查看容器日志..."
    docker-compose logs -f --tail=100
}

# 进入容器 Shell
function Enter-Shell {
    Write-Step "进入 NextCloud 容器..."
    docker exec -it nextcloud-dev bash
}

# 显示状态
function Show-Status {
    Write-Step "检查服务状态..."
    
    docker-compose ps
    
    Write-Host ""
    Write-ColorOutput "=== 访问地址 ===" "Yellow"
    Write-Host "NextCloud:    http://localhost:8080"
    Write-Host "管理员账号:   admin / admin123"
    Write-Host "数据库管理:   http://localhost:8081"
    Write-Host "MySQL 连接:   Server: db, Database: nextcloud, User: nextcloud, Password: nextcloud"
    Write-Host ""
    
    # 检查插件状态
    Write-ColorOutput "=== 插件状态 ===" "Yellow"
    try {
        $status = docker exec -u www-data nextcloud-dev php occ app:list | Select-String "oauthwecom"
        if ($status) {
            Write-Host $status
        } else {
            Write-Warning "插件未安装"
        }
    } catch {
        Write-Warning "无法获取插件状态"
    }
    
    Write-Host ""
    Write-ColorOutput "=== 有用的命令 ===" "Yellow"
    Write-Host "查看日志:     .\dev-setup.ps1 -Logs"
    Write-Host "进入容器:     .\dev-setup.ps1 -Shell"
    Write-Host "重新构建:     .\dev-setup.ps1 -Build"
    Write-Host "清理重启:     .\dev-setup.ps1 -Clean"
}

# 主函数
function Main {
    Write-ColorOutput @"
╔═══════════════════════════════════════════════════════╗
║   NextCloud 企业微信插件 - Docker 开发环境            ║
╚═══════════════════════════════════════════════════════╝
"@ "Cyan"

    Test-Docker
    
    if ($Clean) {
        Clean-Environment
        return
    }
    
    if ($Logs) {
        Show-Logs
        return
    }
    
    if ($Shell) {
        Enter-Shell
        return
    }
    
    if ($Status) {
        Show-Status
        return
    }
    
    if ($Build) {
        Install-Plugin
        Build-Frontend
        return
    }
    
    if ($Enable) {
        Enable-Plugin
        return
    }
    
    if ($Install) {
        Install-Plugin
        Build-Frontend
        Enable-Plugin
        return
    }
    
    # 默认：完整启动流程
    Start-Services
    Install-Plugin
    Build-Frontend
    Enable-Plugin
    Show-Status
    
    Write-Host ""
    Write-Success "开发环境设置完成！"
    Write-Host ""
    Write-ColorOutput "现在你可以访问: http://localhost:8080" "Green"
    Write-ColorOutput "管理员账号: admin / admin123" "Green"
    Write-Host ""
    Write-ColorOutput "提示: 修改代码后，运行 .\dev-setup.ps1 -Build 重新构建前端" "Yellow"
}

# 执行主函数
Main

