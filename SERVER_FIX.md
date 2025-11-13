# 服务器端修复指南

## 🔧 当前问题

1. ✅ 页面可以访问
2. ❌ 保存配置没有反应
3. ❌ 左侧菜单没有企业微信选项

## 📋 解决步骤

### 第1步：在服务器上拉取最新代码

```bash
# SSH 登录服务器
ssh user@cloud.top-leading.com

# 进入插件目录
cd /var/www/nextcloud/apps/oauthwecom

# 拉取最新代码
git pull origin main
```

### 第2步：构建前端资源（重要！）

```bash
# 确保在插件目录中
cd /var/www/nextcloud/apps/oauthwecom

# 安装依赖（如果还没安装）
npm install

# 构建前端资源
npm run build

# 检查构建产物
ls -la js/adminSettings.js
ls -la css/adminSettings.css
```

### 第3步：修复权限

```bash
# 确保 Web 服务器用户拥有正确的权限
cd /var/www/nextcloud/apps
sudo chown -R www-data:www-data oauthwecom
# 或者如果是 nginx
# sudo chown -R nginx:nginx oauthwecom

# 设置正确的权限
chmod -R 755 oauthwecom
```

### 第4步：重新启用插件

```bash
# 进入 NextCloud 根目录
cd /var/www/nextcloud

# 禁用插件
sudo -u www-data php occ app:disable oauthwecom

# 清除所有缓存
sudo -u www-data php occ maintenance:repair
sudo -u www-data php occ maintenance:mode --on
sudo -u www-data php occ maintenance:mode --off

# 重新启用插件
sudo -u www-data php occ app:enable oauthwecom
```

### 第5步：重启服务

```bash
# 重启 PHP-FPM
sudo systemctl restart php-fpm
# 或者根据您的 PHP 版本
# sudo systemctl restart php8.1-fpm
# sudo systemctl restart php8.2-fpm

# 如果使用 Apache
sudo systemctl restart apache2

# 如果使用 Nginx
sudo systemctl restart nginx
```

### 第6步：清除浏览器缓存

1. 在浏览器中按 `Ctrl + Shift + Del`
2. 选择"清除所有缓存"
3. 关闭并重新打开浏览器
4. 重新登录 NextCloud

### 第7步：验证修复

访问设置页面：
```
https://cloud.top-leading.com/settings/admin/security
```

向下滚动，应该能看到"企业微信OAuth认证"部分。

在浏览器中按 `F12` 打开开发者工具，查看：

1. **Console 标签页**
   
   应该看到：
   ```
   企业微信OAuth认证设置页面已加载
   保存按钮: <button id="save-settings" ...>
   测试按钮: <button id="test-connection" ...>
   ```

2. **Network 标签页**
   
   刷新页面，检查：
   - `adminSettings.js` 是否成功加载（状态码 200）
   - `adminSettings.css` 是否成功加载（状态码 200）

3. **尝试保存配置**
   
   填写配置后点击"保存设置"，在 Network 标签页应该看到：
   - 请求到 `/apps/oauthwecom/admin/config`
   - 状态码应该是 200
   - 响应应该包含 `{"status":"success"}`

## 🔍 故障排查

### 问题1：npm run build 失败

```bash
# 删除 node_modules 和 package-lock.json
rm -rf node_modules package-lock.json

# 重新安装
npm install

# 再次构建
npm run build
```

### 问题2：JS 文件不存在

```bash
# 检查 js 目录
ls -la js/

# 如果 adminSettings.js 不存在，手动构建
npm run build

# 如果还不存在，检查 vite.config.js
cat vite.config.js
```

### 问题3：浏览器控制台显示 404 错误

```bash
# 检查文件路径
find /var/www/nextcloud/apps/oauthwecom -name "adminSettings.js"

# 检查文件权限
ls -la /var/www/nextcloud/apps/oauthwecom/js/adminSettings.js
```

### 问题4：保存时显示权限错误

```bash
# 检查当前用户是否是管理员
sudo -u www-data php occ user:info YOUR_USERNAME

# 如果不是管理员，添加到 admin 组
sudo -u www-data php occ group:adduser admin YOUR_USERNAME
```

### 问题5：保存没有反应但没有错误

1. 打开浏览器开发者工具（F12）
2. 查看 Console 标签页的错误信息
3. 查看 Network 标签页，点击保存时的请求详情
4. 把错误信息提供给开发者

## 📝 检查清单

完成以下步骤后，勾选复选框：

- [ ] 已在服务器上执行 `git pull`
- [ ] 已运行 `npm install`
- [ ] 已运行 `npm run build`
- [ ] `js/adminSettings.js` 文件存在
- [ ] `css/adminSettings.css` 文件存在
- [ ] 文件权限正确（www-data 或 nginx 拥有）
- [ ] 已重新启用插件
- [ ] 已重启 PHP-FPM
- [ ] 已清除浏览器缓存
- [ ] 浏览器控制台没有错误
- [ ] Network 标签页显示 JS/CSS 加载成功（200）
- [ ] 点击保存时能看到网络请求

## 🎯 快速验证命令

运行这个一键检查脚本：

```bash
cd /var/www/nextcloud/apps/oauthwecom

cat << 'EOF' > quick-check.sh
#!/bin/bash
echo "=== 检查前端构建产物 ==="
if [ -f "js/adminSettings.js" ]; then
    echo "✓ adminSettings.js 存在"
    ls -lh js/adminSettings.js
else
    echo "✗ adminSettings.js 不存在，需要运行 npm run build"
fi

if [ -f "css/adminSettings.css" ]; then
    echo "✓ adminSettings.css 存在"
    ls -lh css/adminSettings.css
else
    echo "✗ adminSettings.css 不存在"
fi

echo ""
echo "=== 检查文件权限 ==="
ls -la js/ | head -5

echo ""
echo "=== 检查插件状态 ==="
cd /var/www/nextcloud
sudo -u www-data php occ app:list | grep oauthwecom

echo ""
echo "=== 如果文件不存在，运行 ==="
echo "npm install && npm run build"
EOF

chmod +x quick-check.sh
./quick-check.sh
```

## 📞 获取帮助

如果以上步骤都完成了但问题仍然存在，请提供以下信息：

1. **浏览器控制台截图**（按 F12）
2. **Network 标签页截图**（显示 JS/CSS 加载状态）
3. **服务器日志**：
   ```bash
   tail -50 /var/www/nextcloud/data/nextcloud.log | grep -i oauth
   ```
4. **quick-check.sh 输出**

## 💡 期望结果

完成所有步骤后：

1. ✅ 在"安全"分类下能看到"企业微信OAuth认证"
2. ✅ 填写配置后点击"保存设置"有响应
3. ✅ 保存成功后显示绿色提示"配置保存成功"
4. ✅ 刷新页面后配置内容保留
5. ✅ 浏览器控制台没有红色错误

---

**更新时间：** 2024-11-13  
**最新提交：** 0a556a6

