#!/bin/bash

# 一键修复前端资源问题

echo "========================================="
echo "企业微信 OAuth 插件 - 前端资源修复"
echo "========================================="
echo ""

# 检查是否提供了 NextCloud 路径
if [ -z "$1" ]; then
    echo "用法: ./fix-frontend.sh /path/to/nextcloud"
    echo "示例: ./fix-frontend.sh /var/www/nextcloud"
    exit 1
fi

NEXTCLOUD_PATH="$1"
APP_PATH="$NEXTCLOUD_PATH/apps/oauthwecom"

echo "NextCloud 路径: $NEXTCLOUD_PATH"
echo "插件路径: $APP_PATH"
echo ""

# 检查目录
if [ ! -d "$APP_PATH" ]; then
    echo "❌ 错误: 插件目录不存在"
    exit 1
fi

# 步骤 1: 进入插件目录
echo "步骤 1: 进入插件目录..."
cd "$APP_PATH" || exit 1
echo "✅ 当前目录: $(pwd)"
echo ""

# 步骤 2: 检查 Node.js
echo "步骤 2: 检查 Node.js 环境..."
if ! command -v node &> /dev/null; then
    echo "❌ Node.js 未安装，请先安装 Node.js"
    echo "   Ubuntu/Debian: sudo apt install nodejs npm"
    echo "   CentOS/RHEL: sudo yum install nodejs npm"
    exit 1
fi
echo "✅ Node.js: $(node -v)"
echo "✅ npm: $(npm -v)"
echo ""

# 步骤 3: 安装依赖
echo "步骤 3: 安装 npm 依赖..."
if [ ! -d "node_modules" ]; then
    echo "正在安装依赖..."
    npm install
    if [ $? -ne 0 ]; then
        echo "❌ npm install 失败"
        exit 1
    fi
    echo "✅ 依赖安装完成"
else
    echo "✅ node_modules 已存在"
fi
echo ""

# 步骤 4: 构建前端资源
echo "步骤 4: 构建前端资源..."
echo "运行: npm run build"
npm run build
if [ $? -ne 0 ]; then
    echo "❌ 构建失败"
    exit 1
fi
echo "✅ 构建完成"
echo ""

# 步骤 5: 验证构建产物
echo "步骤 5: 验证构建产物..."
if [ -f "js/adminSettings.js" ]; then
    echo "✅ adminSettings.js 已创建"
    ls -lh "js/adminSettings.js"
else
    echo "❌ adminSettings.js 未创建"
    echo ""
    echo "可能的原因："
    echo "1. 构建配置有问题"
    echo "2. 源文件不存在：src/admin-settings.js"
    exit 1
fi

if [ -f "css/adminSettings.css" ]; then
    echo "✅ adminSettings.css 已创建"
    ls -lh "css/adminSettings.css"
fi
echo ""

# 步骤 6: 修复权限
echo "步骤 6: 修复文件权限..."
cd "$NEXTCLOUD_PATH/apps"
sudo chown -R www-data:www-data oauthwecom 2>/dev/null || sudo chown -R nginx:nginx oauthwecom 2>/dev/null
sudo chmod -R 755 oauthwecom
echo "✅ 权限已修复"
echo ""

# 步骤 7: 重新启用插件
echo "步骤 7: 重新启用插件..."
cd "$NEXTCLOUD_PATH"
echo "禁用插件..."
sudo -u www-data php occ app:disable oauthwecom

echo "清除缓存..."
sudo -u www-data php occ maintenance:repair

echo "启用插件..."
sudo -u www-data php occ app:enable oauthwecom

echo "✅ 插件已重新启用"
echo ""

# 步骤 8: 重启服务
echo "步骤 8: 重启 PHP-FPM..."
if systemctl list-units --type=service | grep -q "php.*fpm"; then
    sudo systemctl restart php-fpm 2>/dev/null || \
    sudo systemctl restart php8.1-fpm 2>/dev/null || \
    sudo systemctl restart php8.2-fpm 2>/dev/null || \
    sudo systemctl restart php7.4-fpm 2>/dev/null
    echo "✅ PHP-FPM 已重启"
else
    echo "⚠️  未找到 PHP-FPM 服务，可能需要手动重启"
fi
echo ""

# 最终验证
echo "========================================="
echo "最终验证"
echo "========================================="
echo ""

cd "$APP_PATH"
echo "✅ 构建产物："
ls -lh js/adminSettings.js 2>/dev/null && echo "   - adminSettings.js 存在" || echo "   ❌ adminSettings.js 不存在"
ls -lh css/adminSettings.css 2>/dev/null && echo "   - adminSettings.css 存在" || echo "   ❌ adminSettings.css 不存在"

echo ""
echo "✅ 插件状态："
cd "$NEXTCLOUD_PATH"
sudo -u www-data php occ app:list | grep oauthwecom

echo ""
echo "========================================="
echo "修复完成！"
echo "========================================="
echo ""
echo "下一步："
echo "1. 打开浏览器"
echo "2. 按 Ctrl+Shift+Del 清除所有缓存"
echo "3. 访问: https://cloud.top-leading.com/settings/admin/security"
echo "4. 按 F12 打开开发者工具"
echo "5. 查看 Console 标签 - 应该显示 '企业微信OAuth认证设置页面已加载'"
echo "6. 查看 Network 标签 - adminSettings.js 应该返回 200"
echo "7. 尝试保存配置"
echo ""
echo "如果还有问题，查看浏览器控制台的错误信息"
echo ""

