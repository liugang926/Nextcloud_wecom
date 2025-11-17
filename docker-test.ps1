# 简化的测试脚本 - 用于快速验证插件功能

Write-Host "=== NextCloud 企业微信插件测试脚本 ===" -ForegroundColor Cyan
Write-Host ""

# 1. 检查容器状态
Write-Host "1. 检查容器状态..." -ForegroundColor Yellow
docker ps --filter "name=nextcloud-dev"
Write-Host ""

# 2. 检查插件文件
Write-Host "2. 检查插件文件..." -ForegroundColor Yellow
docker exec nextcloud-dev ls -la /var/www/html/apps/oauthwecom
Write-Host ""

# 3. 检查前端构建产物
Write-Host "3. 检查前端构建产物..." -ForegroundColor Yellow
docker exec nextcloud-dev ls -la /var/www/html/apps/oauthwecom/js/
Write-Host ""

# 4. 检查插件状态
Write-Host "4. 检查插件状态..." -ForegroundColor Yellow
docker exec -u www-data nextcloud-dev php occ app:list | Select-String "oauthwecom"
Write-Host ""

# 5. 检查应用配置
Write-Host "5. 检查应用配置..." -ForegroundColor Yellow
docker exec -u www-data nextcloud-dev php occ config:list oauthwecom
Write-Host ""

# 6. 测试访问
Write-Host "6. 测试 NextCloud 访问..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8080/status.php" -UseBasicParsing
    Write-Host "✅ NextCloud 可访问，状态码: $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "❌ NextCloud 不可访问: $_" -ForegroundColor Red
}
Write-Host ""

# 7. 提供有用的链接
Write-Host "=== 访问链接 ===" -ForegroundColor Cyan
Write-Host "NextCloud:     http://localhost:8080"
Write-Host "管理员登录:    admin / admin123"
Write-Host "插件设置:      http://localhost:8080/settings/admin/security"
Write-Host "数据库管理:    http://localhost:8081"
Write-Host ""

Write-Host "=== 有用的命令 ===" -ForegroundColor Cyan
Write-Host "查看日志:      docker-compose logs -f nextcloud"
Write-Host "进入容器:      docker exec -it nextcloud-dev bash"
Write-Host "重新构建:      npm run build"
Write-Host "启用插件:      docker exec -u www-data nextcloud-dev php occ app:enable oauthwecom"

