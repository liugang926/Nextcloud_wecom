# Windows 11 Docker 开发环境快速设置指南

## 🎯 目标

在你的 Windows 11 电脑上使用 Docker 快速搭建 NextCloud 开发环境，并安装测试企业微信插件。

## 📋 第一步：安装必要软件

### 1. 安装 Docker Desktop

1. 访问 https://www.docker.com/products/docker-desktop/
2. 下载 Windows 版本
3. 运行安装程序
4. 安装完成后**重启电脑**
5. 启动 Docker Desktop（会在系统托盘显示图标）
6. 等待 Docker 完全启动（图标变为绿色）

### 2. 安装 Node.js

1. 访问 https://nodejs.org/
2. 下载 LTS 版本（推荐）
3. 运行安装程序，使用默认选项
4. 打开 PowerShell 验证安装：
   ```powershell
   node --version
   npm --version
   ```

## 🚀 第二步：启动开发环境

### 方法 1: 使用自动化脚本（推荐）

打开 PowerShell，导航到项目目录：

```powershell
# 切换到项目目录
cd C:\Users\ND\Desktop\Notting_Project\NextCloud

# 创建 .env 文件（首次需要）
Copy-Item docker-compose.yml docker-compose.yml.bak
(Get-Content docker-compose.yml.bak) | ForEach-Object {$_ -replace '- MYSQL_HOST=db','- MYSQL_HOST=db'} | Set-Content docker-compose.yml

# 运行自动化脚本（这会完成所有设置）
.\dev-setup.ps1
```

**等待 2-3 分钟**，脚本会自动完成：
- ✅ 启动 Docker 容器（NextCloud + MySQL + Redis）
- ✅ 安装 npm 依赖
- ✅ 构建前端资源
- ✅ 在 NextCloud 中启用插件
- ✅ 显示访问地址

### 方法 2: 手动逐步执行

如果自动脚本出现问题，可以手动执行：

```powershell
# 1. 启动 Docker 容器
docker-compose up -d

# 2. 等待 NextCloud 启动（约 1-2 分钟）
Start-Sleep -Seconds 60

# 3. 安装 npm 依赖
npm install

# 4. 构建前端
npm run build

# 5. 进入容器并启用插件
docker exec -u www-data nextcloud-dev php occ app:enable oauthwecom
```

## 🌐 第三步：访问和测试

### 访问 NextCloud

打开浏览器访问：http://localhost:8080

**管理员账号**：
- 用户名：`admin`
- 密码：`admin123`

### 访问插件设置页面

登录后访问：http://localhost:8080/settings/admin/security

在页面中找到"企业微信认证"部分。

### 测试配置保存

1. 填写测试数据：
   - 企业 ID：`test123`
   - 应用 ID：`1000001`
   - 应用 Secret：`test_secret`

2. 点击"保存配置"按钮

3. 刷新页面，检查配置是否保存成功

## 🔍 第四步：验证和调试

### 检查插件状态

```powershell
# 查看插件列表
docker exec -u www-data nextcloud-dev php occ app:list | Select-String "oauthwecom"

# 应该看到：
#   - oauthwecom: 1.0.0 (enabled)
```

### 查看前端文件

```powershell
# 检查构建产物
dir js\oauthwecom-*.mjs
dir css\oauthwecom-*.css

# 应该看到：
#   oauthwecom-adminSettings.mjs
#   oauthwecom-main.mjs
```

### 查看日志

```powershell
# 实时查看 NextCloud 日志
docker-compose logs -f nextcloud

# 按 Ctrl+C 停止查看
```

### 浏览器开发者工具

1. 在设置页面按 `F12` 打开开发者工具
2. 切换到 **Console** 标签
3. 查看是否有 JavaScript 错误
4. 切换到 **Network** 标签
5. 点击"保存配置"，查看 API 请求是否成功

## 🛠️ 常用命令

### 查看状态

```powershell
# 使用快速测试脚本
.\docker-test.ps1

# 或使用开发脚本
.\dev-setup.ps1 -Status
```

### 重新构建前端

修改前端代码后：

```powershell
npm run build
```

然后刷新浏览器（可能需要 Ctrl+F5 强制刷新）。

### 查看日志

```powershell
# 查看所有容器日志
docker-compose logs -f

# 只查看 NextCloud 日志
docker-compose logs -f nextcloud

# 进入容器查看详细日志
docker exec nextcloud-dev tail -f /var/www/html/data/nextcloud.log
```

### 进入容器调试

```powershell
# 进入容器 Shell
docker exec -it nextcloud-dev bash

# 在容器内可以执行：
ls -la /var/www/html/apps/oauthwecom/
cat /var/www/html/data/nextcloud.log
php occ app:list
```

### 重启服务

```powershell
# 重启 NextCloud 容器
docker-compose restart nextcloud

# 重启所有容器
docker-compose restart

# 停止所有容器
docker-compose down

# 启动所有容器
docker-compose up -d
```

## 🔄 完全重置环境

如果环境出现问题，完全重置：

```powershell
# 1. 停止并删除所有容器和数据
docker-compose down -v

# 2. 删除本地构建文件
Remove-Item -Recurse -Force node_modules -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force js -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force css -ErrorAction SilentlyContinue

# 3. 重新开始
.\dev-setup.ps1
```

## ❓ 常见问题

### Docker 未运行

**错误**：`error during connect: This error may indicate that the docker daemon is not running`

**解决**：
1. 启动 Docker Desktop
2. 等待图标变为绿色
3. 重新运行命令

### 端口被占用

**错误**：`Bind for 0.0.0.0:8080 failed: port is already allocated`

**解决**：
1. 修改 `docker-compose.yml` 的端口：
   ```yaml
   ports:
     - "9080:80"  # 改为 9080 或其他未使用的端口
   ```
2. 访问 http://localhost:9080

### 插件未显示

**解决**：
```powershell
# 手动启用插件
docker exec -u www-data nextcloud-dev php occ app:enable oauthwecom

# 检查文件权限
docker exec nextcloud-dev chown -R www-data:www-data /var/www/html/apps/oauthwecom

# 清理缓存
docker exec -u www-data nextcloud-dev php occ maintenance:repair
```

### 配置无法保存

**检查步骤**：

1. **前端构建是否成功**：
   ```powershell
   dir js\oauthwecom-adminSettings.mjs
   ```

2. **浏览器控制台是否有错误**：
   - 按 F12 打开开发者工具
   - 查看 Console 和 Network 标签

3. **后端 API 是否正常**：
   ```powershell
   docker-compose logs nextcloud | Select-String "AdminController"
   ```

4. **重新构建并重启**：
   ```powershell
   npm run build
   docker-compose restart nextcloud
   ```

### npm install 失败

**错误**：网络问题或依赖安装失败

**解决**：
```powershell
# 使用淘宝镜像
npm config set registry https://registry.npmmirror.com

# 重新安装
Remove-Item -Recurse -Force node_modules
npm install
```

## 📊 数据库管理

访问 http://localhost:8081 使用 Adminer：

- **系统**: MySQL
- **服务器**: db
- **用户名**: nextcloud
- **密码**: nextcloud
- **数据库**: nextcloud

可以在这里查看：
- `oc_wecom_user_mapping` - 用户映射表
- `oc_wecom_audit_logs` - 审计日志表
- `oc_appconfig` - 应用配置（过滤 `oauthwecom`）

## 🎯 测试清单

- [ ] NextCloud 可以访问（http://localhost:8080）
- [ ] 管理员可以登录（admin / admin123）
- [ ] 插件设置页面可以打开
- [ ] 配置可以保存和读取
- [ ] 浏览器控制台没有错误
- [ ] API 请求返回正常
- [ ] 前端文件已正确构建
- [ ] 插件在应用列表中显示为已启用

## 📚 更多信息

详细的开发指南请查看：[DEV_GUIDE.md](./DEV_GUIDE.md)

## 🆘 需要帮助？

如果遇到问题：

1. 运行诊断脚本：
   ```powershell
   .\docker-test.ps1
   ```

2. 查看日志：
   ```powershell
   docker-compose logs nextcloud
   ```

3. 检查文件：
   ```powershell
   docker exec nextcloud-dev ls -la /var/www/html/apps/oauthwecom/
   ```

---

**祝测试顺利！** 🎉

有任何问题随时查看本文档或相关日志文件。

