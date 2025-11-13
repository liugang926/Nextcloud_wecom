#!/bin/bash

# 企业微信插件诊断脚本 - 用于诊断为什么保存按钮没有反应

echo "========================================="
echo "企业微信 OAuth 插件诊断工具"
echo "========================================="
echo ""

# 检查是否提供了 NextCloud 路径
if [ -z "$1" ]; then
    echo "用法: ./diagnose.sh /path/to/nextcloud"
    echo "示例: ./diagnose.sh /var/www/nextcloud"
    exit 1
fi

NEXTCLOUD_PATH="$1"
APP_PATH="$NEXTCLOUD_PATH/apps/oauthwecom"

echo "NextCloud 路径: $NEXTCLOUD_PATH"
echo "插件路径: $APP_PATH"
echo ""

# 检查插件目录
if [ ! -d "$APP_PATH" ]; then
    echo "❌ 错误: 插件目录不存在"
    exit 1
fi

echo "✅ 插件目录存在"
echo ""

# 检查前端构建产物
echo "检查前端构建产物..."
echo "========================================="

if [ -f "$APP_PATH/js/adminSettings.js" ]; then
    echo "✅ adminSettings.js 存在"
    ls -lh "$APP_PATH/js/adminSettings.js"
else
    echo "❌ adminSettings.js 不存在"
    echo ""
    echo "🔧 需要构建前端资源："
    echo "   cd $APP_PATH"
    echo "   npm install"
    echo "   npm run build"
    echo ""
fi

if [ -f "$APP_PATH/css/adminSettings.css" ]; then
    echo "✅ adminSettings.css 存在"
    ls -lh "$APP_PATH/css/adminSettings.css"
else
    echo "❌ adminSettings.css 不存在"
fi

echo ""

# 检查 js 和 css 目录
echo "检查目录结构..."
echo "========================================="
if [ -d "$APP_PATH/js" ]; then
    echo "✅ js/ 目录存在，内容："
    ls -la "$APP_PATH/js/" | head -10
else
    echo "❌ js/ 目录不存在"
fi

echo ""

if [ -d "$APP_PATH/css" ]; then
    echo "✅ css/ 目录存在，内容："
    ls -la "$APP_PATH/css/" | head -10
else
    echo "❌ css/ 目录不存在"
fi

echo ""

# 检查 Node.js 环境
echo "检查 Node.js 环境..."
echo "========================================="
cd "$APP_PATH"

if command -v node &> /dev/null; then
    echo "✅ Node.js 已安装: $(node -v)"
else
    echo "❌ Node.js 未安装"
fi

if command -v npm &> /dev/null; then
    echo "✅ npm 已安装: $(npm -v)"
else
    echo "❌ npm 未安装"
fi

echo ""

# 检查 package.json
if [ -f "$APP_PATH/package.json" ]; then
    echo "✅ package.json 存在"
else
    echo "❌ package.json 不存在"
fi

# 检查 node_modules
if [ -d "$APP_PATH/node_modules" ]; then
    echo "✅ node_modules 存在"
else
    echo "❌ node_modules 不存在（需要运行 npm install）"
fi

echo ""

# 检查插件状态
echo "检查插件状态..."
echo "========================================="
cd "$NEXTCLOUD_PATH"
PLUGIN_STATUS=$(sudo -u www-data php occ app:list 2>/dev/null | grep oauthwecom || echo "未找到")
echo "$PLUGIN_STATUS"

echo ""

# 检查模板文件
echo "检查模板文件..."
echo "========================================="
if [ -f "$APP_PATH/templates/settings/admin.php" ]; then
    echo "✅ 模板文件存在"
    echo ""
    echo "检查 JS/CSS 加载语句："
    grep -n "addScript\|addStyle" "$APP_PATH/templates/settings/admin.php"
else
    echo "❌ 模板文件不存在"
fi

echo ""
echo ""
echo "========================================="
echo "诊断总结"
echo "========================================="
echo ""

# 提供建议
if [ ! -f "$APP_PATH/js/adminSettings.js" ]; then
    echo "🔴 主要问题：前端资源未构建"
    echo ""
    echo "解决步骤："
    echo "1. cd $APP_PATH"
    echo "2. npm install"
    echo "3. npm run build"
    echo "4. ls -la js/adminSettings.js  # 验证文件已创建"
    echo "5. sudo chown -R www-data:www-data $APP_PATH"
    echo "6. cd $NEXTCLOUD_PATH && sudo -u www-data php occ app:disable oauthwecom"
    echo "7. sudo -u www-data php occ app:enable oauthwecom"
    echo "8. sudo systemctl restart php-fpm"
    echo ""
else
    echo "🟢 前端资源已构建"
    echo ""
    echo "可能的问题："
    echo "1. 浏览器缓存 - 按 Ctrl+Shift+Del 清除缓存"
    echo "2. PHP-FPM 需要重启 - sudo systemctl restart php-fpm"
    echo "3. 文件权限问题 - sudo chown -R www-data:www-data $APP_PATH"
    echo ""
    echo "检查浏览器控制台（F12）："
    echo "- 查看 Console 标签是否有 JavaScript 错误"
    echo "- 查看 Network 标签，adminSettings.js 是否返回 200"
    echo ""
fi

echo "========================================="
echo "完成诊断"
echo "========================================="

