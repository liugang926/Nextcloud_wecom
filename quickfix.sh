#!/bin/bash

# 快速修复脚本 - 解决设置页面不显示的问题

echo "========================================"
echo "NextCloud 企业微信插件 - 快速修复"
echo "========================================"
echo ""

# 检查是否提供了 NextCloud 路径
if [ -z "$1" ]; then
    echo "请指定 NextCloud 安装路径"
    echo "用法: ./quickfix.sh /var/www/nextcloud"
    exit 1
fi

NEXTCLOUD_PATH="$1"
APP_PATH="$NEXTCLOUD_PATH/apps/oauthwecom"

echo "NextCloud 路径: $NEXTCLOUD_PATH"
echo "插件路径: $APP_PATH"
echo ""

# 检查路径
if [ ! -d "$NEXTCLOUD_PATH" ] || [ ! -f "$NEXTCLOUD_PATH/occ" ]; then
    echo "错误: 无效的 NextCloud 路径"
    exit 1
fi

if [ ! -d "$APP_PATH" ]; then
    echo "错误: 插件目录不存在"
    exit 1
fi

echo "步骤 1: 构建前端资源..."
cd "$APP_PATH"
if [ -f "package.json" ]; then
    npm install 2>&1 | tail -3
    npm run build 2>&1 | tail -3
    echo "✓ 前端资源构建完成"
else
    echo "⚠ 未找到 package.json，跳过前端构建"
fi

echo ""
echo "步骤 2: 修复文件权限..."
cd "$NEXTCLOUD_PATH/apps"
sudo chown -R www-data:www-data oauthwecom 2>/dev/null || sudo chown -R nginx:nginx oauthwecom 2>/dev/null
sudo chmod -R 755 oauthwecom
echo "✓ 权限已修复"

echo ""
echo "步骤 3: 禁用插件..."
cd "$NEXTCLOUD_PATH"
sudo -u www-data php occ app:disable oauthwecom
echo "✓ 插件已禁用"

echo ""
echo "步骤 4: 清除缓存..."
sudo -u www-data php occ maintenance:repair
echo "✓ 缓存已清除"

echo ""
echo "步骤 5: 重新启用插件..."
sudo -u www-data php occ app:enable oauthwecom
echo "✓ 插件已启用"

echo ""
echo "步骤 6: 运行数据库迁移..."
sudo -u www-data php occ migrations:execute oauthwecom
echo "✓ 数据库迁移完成"

echo ""
echo "========================================"
echo "修复完成！"
echo "========================================"
echo ""
echo "现在请访问以下地址查看设置页面："
echo "https://your-domain.com/settings/admin/security"
echo ""
echo "如果还是看不到，请："
echo "1. 刷新浏览器（Ctrl+F5 强制刷新）"
echo "2. 清除浏览器缓存"
echo "3. 退出登录后重新登录"
echo ""

