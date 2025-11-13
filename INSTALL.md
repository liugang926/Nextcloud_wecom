# NextCloud 企业微信 OAuth 认证插件 - 安装指南

## 安装步骤

### 1. 下载插件

将插件放置到 NextCloud 的 apps 目录：

```bash
cd /path/to/nextcloud/apps
# 如果是从 git 克隆
git clone <repository-url> oauthwecom

# 或者解压下载的 zip 文件到 oauthwecom 目录
```

### 2. 安装依赖

```bash
cd oauthwecom

# 安装 PHP 依赖
composer install --no-dev

# 安装前端依赖
npm install

# 构建前端资源
npm run build
```

### 3. 设置权限

确保 NextCloud 的 Web 服务器用户（通常是 www-data 或 nginx）有权限访问插件目录：

```bash
# 对于 Apache/www-data
sudo chown -R www-data:www-data /path/to/nextcloud/apps/oauthwecom

# 对于 Nginx
sudo chown -R nginx:nginx /path/to/nextcloud/apps/oauthwecom
```

### 4. 启用插件

方式一：通过 NextCloud Web 界面
1. 登录 NextCloud 管理员账号
2. 进入 "应用" 页面
3. 在 "已禁用的应用" 中找到 "企业微信OAuth认证"
4. 点击 "启用"

方式二：通过命令行（推荐）
```bash
cd /path/to/nextcloud
sudo -u www-data php occ app:enable oauthwecom
```

### 5. 运行数据库迁移

插件启用后会自动运行数据库迁移。如果没有自动运行，可以手动执行：

```bash
sudo -u www-data php occ migrations:execute oauthwecom
```

### 6. 清除缓存

```bash
cd /path/to/nextcloud
sudo -u www-data php occ maintenance:repair
sudo -u www-data php occ db:add-missing-indices
```

## 故障排查

### 问题 1: 管理后台找不到设置页面

**症状**：启用插件后，在管理后台 "设置" 中找不到企业微信配置选项。

**解决方案**：

1. **清除 NextCloud 缓存**
   ```bash
   cd /path/to/nextcloud
   sudo -u www-data php occ maintenance:repair
   sudo -u www-data php occ config:list
   ```

2. **检查插件是否正确启用**
   ```bash
   sudo -u www-data php occ app:list | grep oauthwecom
   ```
   
   应该显示在 "Enabled" 列表中。

3. **检查日志文件**
   ```bash
   tail -f /path/to/nextcloud/data/nextcloud.log
   ```

4. **重新构建前端资源**
   ```bash
   cd /path/to/nextcloud/apps/oauthwecom
   npm run build
   ```

5. **检查文件权限**
   ```bash
   ls -la /path/to/nextcloud/apps/oauthwecom
   ```
   
   确保所有文件属于 Web 服务器用户。

6. **禁用并重新启用插件**
   ```bash
   sudo -u www-data php occ app:disable oauthwecom
   sudo -u www-data php occ app:enable oauthwecom
   ```

7. **查看设置页面位置**
   
   设置页面应该出现在：
   - **路径**：管理 → 安全 → 企业微信OAuth认证
   - **URL**：`https://your-domain.com/settings/admin/security`

### 问题 2: 前端资源加载失败

**症状**：设置页面显示但样式错乱或功能不正常。

**解决方案**：

1. **检查构建产物**
   ```bash
   ls -la /path/to/nextcloud/apps/oauthwecom/js/
   ```
   
   应该包含 `adminSettings.js` 文件。

2. **重新构建**
   ```bash
   cd /path/to/nextcloud/apps/oauthwecom
   rm -rf node_modules package-lock.json
   npm install
   npm run build
   ```

3. **检查浏览器控制台**
   
   按 F12 打开开发者工具，查看是否有 JavaScript 错误。

### 问题 3: 数据库表不存在

**症状**：启用插件后出现数据库错误。

**解决方案**：

1. **手动运行迁移**
   ```bash
   sudo -u www-data php occ migrations:status oauthwecom
   sudo -u www-data php occ migrations:execute oauthwecom
   ```

2. **检查数据库表**
   ```bash
   # 进入数据库
   mysql -u nextcloud_user -p nextcloud_db
   
   # 查看表
   SHOW TABLES LIKE 'wecom%';
   ```
   
   应该看到：
   - `wecom_user_mapping`
   - `wecom_audit_logs`

### 问题 4: 权限错误

**症状**：出现 "Permission denied" 错误。

**解决方案**：

```bash
cd /path/to/nextcloud/apps
sudo chown -R www-data:www-data oauthwecom
sudo chmod -R 755 oauthwecom
```

### 问题 5: PHP 依赖缺失

**症状**：出现类找不到的错误。

**解决方案**：

```bash
cd /path/to/nextcloud/apps/oauthwecom
composer install --no-dev
composer dump-autoload
```

## 验证安装

### 1. 检查插件状态

```bash
sudo -u www-data php occ app:list | grep oauthwecom
```

输出应该显示：
```
oauthwecom: 1.0.0 (enabled)
```

### 2. 检查数据库表

```bash
sudo -u www-data php occ db:table-schema wecom_user_mapping
sudo -u www-data php occ db:table-schema wecom_audit_logs
```

### 3. 检查路由

```bash
sudo -u www-data php occ app:routes oauthwecom
```

应该显示所有已注册的路由。

### 4. 访问设置页面

1. 登录 NextCloud 管理员账号
2. 访问：`https://your-domain.com/settings/admin/security`
3. 向下滚动，应该能看到 "企业微信OAuth认证" 设置部分

## 配置企业微信

安装完成后，请按照以下步骤配置：

### 1. 在企业微信后台创建应用

1. 登录 [企业微信管理后台](https://work.weixin.qq.com/)
2. 进入 "应用管理" → "自建" → "创建应用"
3. 记录：
   - 企业ID (CorpID)
   - 应用AgentID
   - 应用Secret

### 2. 配置回调域名

1. 在应用详情中找到 "网页授权及JS-SDK"
2. 设置 "授权回调域"：`your-domain.com`（不包含 https://）

### 3. 在 NextCloud 中配置

1. 进入 NextCloud 管理后台
2. 导航到：设置 → 管理 → 安全 → 企业微信OAuth认证
3. 填写企业微信信息
4. 点击 "测试连接" 验证配置
5. 点击 "保存设置"

## 卸载插件

如果需要卸载插件：

```bash
# 禁用插件
sudo -u www-data php occ app:disable oauthwecom

# 删除插件文件
rm -rf /path/to/nextcloud/apps/oauthwecom

# 如果需要删除数据库表
sudo -u www-data php occ migrations:execute oauthwecom --down
```

## 日志位置

- **NextCloud 日志**：`/path/to/nextcloud/data/nextcloud.log`
- **Web 服务器日志**：
  - Apache: `/var/log/apache2/error.log`
  - Nginx: `/var/log/nginx/error.log`

## 获取帮助

如果以上方法都无法解决问题，请：

1. 查看完整的日志文件
2. 检查 PHP 版本是否符合要求（>= 8.1）
3. 检查 NextCloud 版本是否符合要求（>= 30）
4. 在 GitHub 上提交 Issue，并附上：
   - NextCloud 版本
   - PHP 版本
   - 错误日志
   - 安装步骤

## 性能优化

对于生产环境，建议：

1. **启用 APCu 缓存**
   ```bash
   sudo apt-get install php-apcu
   ```

2. **配置 Redis**
   ```bash
   sudo apt-get install redis-server php-redis
   ```

3. **启用 OpCache**
   
   在 `php.ini` 中：
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=10000
   ```

4. **定期清理审计日志**
   
   在 NextCloud 的 cron 任务中添加：
   ```bash
   # 每月清理 90 天前的日志
   0 0 1 * * sudo -u www-data php /path/to/nextcloud/occ app:custom-command oauthwecom:clean-logs --days=90
   ```

