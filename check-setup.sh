#!/bin/bash

# 检查当前用户是否是管理员的脚本

echo "====================================="
echo "NextCloud 企业微信插件 - 设置检查"
echo "====================================="
echo ""

# 检查是否提供了 NextCloud 路径和用户名
if [ -z "$1" ] || [ -z "$2" ]; then
    echo "用法: ./check-setup.sh /path/to/nextcloud username"
    echo "示例: ./check-setup.sh /var/www/nextcloud admin"
    exit 1
fi

NEXTCLOUD_PATH="$1"
USERNAME="$2"

cd "$NEXTCLOUD_PATH"

echo "检查用户信息..."
echo "================================"
sudo -u www-data php occ user:info "$USERNAME"
echo ""

echo "检查用户所属组..."
echo "================================"
USER_GROUPS=$(sudo -u www-data php occ user:list-groups "$USERNAME")
echo "$USER_GROUPS"
echo ""

if echo "$USER_GROUPS" | grep -q "admin"; then
    echo "✓ 用户 $USERNAME 是管理员"
else
    echo "✗ 用户 $USERNAME 不是管理员"
    echo ""
    echo "添加为管理员："
    echo "  sudo -u www-data php occ group:adduser admin $USERNAME"
fi

echo ""
echo "检查插件状态..."
echo "================================"
PLUGIN_STATUS=$(sudo -u www-data php occ app:list | grep oauthwecom)
echo "$PLUGIN_STATUS"
echo ""

echo "检查设置注册..."
echo "================================"
sudo -u www-data php occ config:app:get oauthwecom installed_version
echo ""

echo "访问URL："
echo "================================"
echo "正确的URL是: https://cloud.top-leading.com/settings/admin/oauthwecom"
echo "注意: 是 oauth 不是 oatuh"
echo ""

echo "如果用户不是管理员，执行："
echo "  sudo -u www-data php occ group:adduser admin $USERNAME"
echo ""

echo "如果插件未启用，执行："
echo "  sudo -u www-data php occ app:disable oauthwecom"
echo "  sudo -u www-data php occ maintenance:repair"
echo "  sudo -u www-data php occ app:enable oauthwecom"
echo ""

