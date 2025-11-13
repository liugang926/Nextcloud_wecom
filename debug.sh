#!/bin/bash

# NextCloud 企业微信插件调试脚本
# 用于诊断安装和配置问题

echo "======================================"
echo "NextCloud 企业微信插件诊断工具"
echo "======================================"
echo ""

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 检测 NextCloud 路径
if [ -z "$1" ]; then
    echo -e "${YELLOW}请指定 NextCloud 安装路径${NC}"
    echo "用法: ./debug.sh /path/to/nextcloud"
    exit 1
fi

NEXTCLOUD_PATH="$1"
APP_PATH="$NEXTCLOUD_PATH/apps/oauthwecom"

echo "NextCloud 路径: $NEXTCLOUD_PATH"
echo "插件路径: $APP_PATH"
echo ""

# 检查 NextCloud 路径
if [ ! -d "$NEXTCLOUD_PATH" ]; then
    echo -e "${RED}✗ NextCloud 路径不存在${NC}"
    exit 1
fi

if [ ! -f "$NEXTCLOUD_PATH/occ" ]; then
    echo -e "${RED}✗ 在指定路径找不到 occ 命令${NC}"
    exit 1
fi

echo -e "${GREEN}✓ NextCloud 路径正确${NC}"

# 检查插件目录
if [ ! -d "$APP_PATH" ]; then
    echo -e "${RED}✗ 插件目录不存在: $APP_PATH${NC}"
    exit 1
fi

echo -e "${GREEN}✓ 插件目录存在${NC}"

# 检查关键文件
echo ""
echo "检查关键文件..."

check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $2"
    else
        echo -e "${RED}✗${NC} $2 (缺失)"
    fi
}

check_file "$APP_PATH/appinfo/info.xml" "appinfo/info.xml"
check_file "$APP_PATH/lib/AppInfo/Application.php" "lib/AppInfo/Application.php"
check_file "$APP_PATH/lib/Settings/AdminSettings.php" "lib/Settings/AdminSettings.php"
check_file "$APP_PATH/templates/settings/admin.php" "templates/settings/admin.php"
check_file "$APP_PATH/lib/Service/ConfigService.php" "lib/Service/ConfigService.php"
check_file "$APP_PATH/lib/Controller/OAuthController.php" "lib/Controller/OAuthController.php"

# 检查构建产物
echo ""
echo "检查前端构建产物..."

check_file "$APP_PATH/js/adminSettings.js" "js/adminSettings.js"
check_file "$APP_PATH/css/adminSettings.css" "css/adminSettings.css"

# 检查权限
echo ""
echo "检查文件权限..."

OWNER=$(stat -c '%U' "$APP_PATH" 2>/dev/null || stat -f '%Su' "$APP_PATH" 2>/dev/null)
echo "目录所有者: $OWNER"

if [ "$OWNER" = "www-data" ] || [ "$OWNER" = "nginx" ] || [ "$OWNER" = "apache" ]; then
    echo -e "${GREEN}✓ 权限正确${NC}"
else
    echo -e "${YELLOW}⚠ 权限可能不正确，应该是 www-data 或 nginx${NC}"
fi

# 检查插件状态
echo ""
echo "检查插件状态..."

cd "$NEXTCLOUD_PATH"
PLUGIN_STATUS=$(sudo -u www-data php occ app:list | grep oauthwecom)

if echo "$PLUGIN_STATUS" | grep -q "oauthwecom"; then
    if echo "$PLUGIN_STATUS" | grep -q "enabled"; then
        echo -e "${GREEN}✓ 插件已启用${NC}"
    else
        echo -e "${YELLOW}⚠ 插件已安装但未启用${NC}"
        echo "运行以下命令启用："
        echo "  sudo -u www-data php $NEXTCLOUD_PATH/occ app:enable oauthwecom"
    fi
else
    echo -e "${RED}✗ 插件未安装${NC}"
fi

# 检查数据库表
echo ""
echo "检查数据库表..."

DB_CHECK=$(sudo -u www-data php occ db:table-schema wecom_user_mapping 2>&1)
if echo "$DB_CHECK" | grep -q "Table"; then
    echo -e "${GREEN}✓ wecom_user_mapping 表存在${NC}"
else
    echo -e "${RED}✗ wecom_user_mapping 表不存在${NC}"
    echo "运行以下命令创建表："
    echo "  sudo -u www-data php $NEXTCLOUD_PATH/occ migrations:execute oauthwecom"
fi

DB_CHECK=$(sudo -u www-data php occ db:table-schema wecom_audit_logs 2>&1)
if echo "$DB_CHECK" | grep -q "Table"; then
    echo -e "${GREEN}✓ wecom_audit_logs 表存在${NC}"
else
    echo -e "${RED}✗ wecom_audit_logs 表不存在${NC}"
fi

# 检查路由
echo ""
echo "检查路由注册..."

ROUTES=$(sudo -u www-data php occ app:routes oauthwecom 2>&1)
if echo "$ROUTES" | grep -q "oauth.authorize\|admin.getConfig"; then
    echo -e "${GREEN}✓ 路由已注册${NC}"
else
    echo -e "${RED}✗ 路由未注册${NC}"
fi

# 检查日志
echo ""
echo "最近的错误日志..."

if [ -f "$NEXTCLOUD_PATH/data/nextcloud.log" ]; then
    echo "最近 10 条包含 'oauthwecom' 的日志:"
    grep -i "oauthwecom" "$NEXTCLOUD_PATH/data/nextcloud.log" | tail -10
else
    echo -e "${YELLOW}未找到日志文件${NC}"
fi

# 提供建议
echo ""
echo "======================================"
echo "诊断建议"
echo "======================================"
echo ""

if [ ! -f "$APP_PATH/js/adminSettings.js" ]; then
    echo -e "${YELLOW}1. 需要构建前端资源：${NC}"
    echo "   cd $APP_PATH"
    echo "   npm install"
    echo "   npm run build"
    echo ""
fi

echo -e "${YELLOW}2. 清除 NextCloud 缓存：${NC}"
echo "   cd $NEXTCLOUD_PATH"
echo "   sudo -u www-data php occ maintenance:repair"
echo ""

echo -e "${YELLOW}3. 重新启用插件：${NC}"
echo "   sudo -u www-data php occ app:disable oauthwecom"
echo "   sudo -u www-data php occ app:enable oauthwecom"
echo ""

echo -e "${YELLOW}4. 访问设置页面：${NC}"
echo "   https://your-domain.com/settings/admin/security"
echo ""

echo "======================================"
echo "诊断完成"
echo "======================================"

