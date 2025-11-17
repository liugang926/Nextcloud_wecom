# NextCloud 企业微信插件 - 本地开发指南

## 📋 前置要求

### Windows 11 环境

1. **Docker Desktop**
   - 下载安装: https://www.docker.com/products/docker-desktop/
   - 安装后启动 Docker Desktop
   - 确保 WSL 2 已启用（Docker Desktop 会提示）

2. **Node.js**
   - 下载安装: https://nodejs.org/ (推荐 LTS 版本)
   - 验证安装: `node --version` 和 `npm --version`

3. **Git**
   - 下载安装: https://git-scm.com/download/win
   - 或使用 GitHub Desktop

## 🚀 快速启动

### 方法 1: 一键启动（推荐）

打开 PowerShell，在项目根目录执行：

```powershell
# 完整启动（启动容器 + 安装依赖 + 构建 + 启用插件）
.\dev-setup.ps1
```

等待 2-3 分钟后，访问 http://localhost:8080

- **管理员账号**: `admin`
- **管理员密码**: `admin123`

### 方法 2: 分步执行

```powershell
# 1. 启动 Docker 容器
docker-compose up -d

# 2. 安装 npm 依赖
npm install

# 3. 构建前端
npm run build

# 4. 在容器内启用插件
docker exec -u www-data nextcloud-dev php occ app:enable oauthwecom
```

## 🔧 常用命令

### PowerShell 脚本命令

```powershell
# 查看服务状态
.\dev-setup.ps1 -Status

# 查看日志（实时）
.\dev-setup.ps1 -Logs

# 进入容器 Shell
.\dev-setup.ps1 -Shell

# 重新构建前端
.\dev-setup.ps1 -Build

# 清理并重新开始
.\dev-setup.ps1 -Clean
docker-compose up -d
.\dev-setup.ps1 -Install
```

### Docker Compose 命令

```powershell
# 启动所有服务
docker-compose up -d

# 停止所有服务
docker-compose down

# 查看日志
docker-compose logs -f nextcloud

# 重启服务
docker-compose restart nextcloud

# 完全清理（删除数据）
docker-compose down -v
```

### 容器内命令

```bash
# 进入容器
docker exec -it nextcloud-dev bash

# 以 www-data 用户执行 occ 命令
docker exec -u www-data nextcloud-dev php occ app:list
docker exec -u www-data nextcloud-dev php occ app:enable oauthwecom
docker exec -u www-data nextcloud-dev php occ app:disable oauthwecom

# 查看插件列表
docker exec -u www-data nextcloud-dev php occ app:list | grep oauthwecom

# 清理缓存
docker exec -u www-data nextcloud-dev php occ maintenance:repair
```

## 📝 开发工作流

### 1. 修改后端代码（PHP）

后端代码修改后会立即生效（PHP 是解释性语言）：

```powershell
# 1. 修改 lib/ 下的 PHP 文件
# 2. 刷新浏览器即可看到效果
# 3. 如果不生效，清理缓存：
docker exec -u www-data nextcloud-dev php occ maintenance:repair
```

### 2. 修改前端代码（JavaScript/CSS）

前端代码需要重新构建：

```powershell
# 1. 修改 src/ 下的 JS/CSS 文件
# 2. 重新构建
npm run build

# 3. 刷新浏览器（可能需要强制刷新 Ctrl+F5）
```

### 3. 修改模板（PHP 模板）

模板修改后立即生效：

```powershell
# 1. 修改 templates/ 下的 PHP 文件
# 2. 刷新浏览器即可
```

### 4. 修改数据库结构

```bash
# 1. 创建新的 Migration 文件
# 2. 在容器内运行迁移
docker exec -u www-data nextcloud-dev php occ migrations:execute oauthwecom latest
```

## 🔍 调试技巧

### 1. 查看 NextCloud 日志

```powershell
# 在容器内
docker exec nextcloud-dev tail -f /var/www/html/data/nextcloud.log

# 或通过 Web 界面
# http://localhost:8080/settings/admin/logging
```

### 2. 查看 PHP 错误

```powershell
# 查看 Apache 错误日志
docker exec nextcloud-dev tail -f /var/log/apache2/error.log
```

### 3. 浏览器开发者工具

- 打开 F12 开发者工具
- 查看 Console 标签的错误信息
- 查看 Network 标签的 API 请求

### 4. 数据库管理

访问 http://localhost:8081 使用 Adminer：

- **系统**: MySQL
- **服务器**: db
- **用户名**: nextcloud
- **密码**: nextcloud
- **数据库**: nextcloud

### 5. 调试前端 JavaScript

在 `src/admin-settings.js` 中添加：

```javascript
console.log('调试信息:', someVariable);
debugger; // 设置断点
```

然后重新构建：

```powershell
npm run build
```

## 🌐 访问地址

| 服务 | 地址 | 说明 |
|------|------|------|
| NextCloud | http://localhost:8080 | 主应用 |
| 管理设置 | http://localhost:8080/settings/admin | 管理后台 |
| 插件设置 | http://localhost:8080/settings/admin/security | 插件配置页面 |
| Adminer | http://localhost:8081 | 数据库管理 |

## 📦 目录结构

```
NextCloud/
├── docker-compose.yml          # Docker 配置
├── dev-setup.ps1              # Windows 开发环境脚本
├── .env                       # 环境变量（自动生成）
├── lib/                       # PHP 后端代码
├── src/                       # 前端源码
├── js/                        # 构建后的 JS（自动生成）
├── css/                       # 构建后的 CSS（自动生成）
├── templates/                 # PHP 模板
├── appinfo/                   # 插件元数据
└── node_modules/              # npm 依赖（自动生成）
```

## ❓ 常见问题

### 1. 容器启动失败

```powershell
# 查看详细错误
docker-compose logs nextcloud

# 检查端口占用
netstat -ano | findstr :8080

# 更换端口（修改 docker-compose.yml）
ports:
  - "9080:80"  # 改为 9080
```

### 2. 插件未显示

```powershell
# 检查插件是否启用
docker exec -u www-data nextcloud-dev php occ app:list | grep oauthwecom

# 手动启用
docker exec -u www-data nextcloud-dev php occ app:enable oauthwecom

# 检查文件权限
docker exec nextcloud-dev ls -la /var/www/html/apps/oauthwecom
```

### 3. 前端资源 404

```powershell
# 确认构建成功
ls js/
ls css/

# 检查文件是否存在
dir js\oauthwecom-adminSettings.mjs

# 重新构建
npm run build
```

### 4. 配置无法保存

```powershell
# 检查后端 API 是否正常
docker-compose logs nextcloud | Select-String "AdminController"

# 测试 API 端点
# 在浏览器 F12 Console 中：
fetch('/apps/oauthwecom/admin/config')
  .then(r => r.json())
  .then(console.log)
```

### 5. 数据库连接失败

```powershell
# 检查数据库容器
docker-compose ps

# 重启数据库
docker-compose restart db

# 查看数据库日志
docker-compose logs db
```

## 🔄 重置环境

如果环境出现问题，可以完全重置：

```powershell
# 1. 停止并删除所有容器和数据
docker-compose down -v

# 2. 删除构建产物
Remove-Item -Recurse -Force node_modules, js, css -ErrorAction SilentlyContinue

# 3. 重新开始
.\dev-setup.ps1
```

## 📚 相关文档

- [NextCloud 插件开发文档](https://docs.nextcloud.com/server/latest/developer_manual/)
- [企业微信 API 文档](https://developer.work.weixin.qq.com/document/)
- [Docker 官方文档](https://docs.docker.com/)
- [Vite 构建工具文档](https://vitejs.dev/)

## 💡 开发技巧

### 热重载开发模式

修改前端代码时使用 Vite 开发服务器：

```powershell
# 启动开发服务器（带热重载）
npm run dev

# 在另一个终端监听文件变化并自动构建
npm run watch
```

### 快速测试 API

使用 curl 或 Postman 测试 API：

```bash
# 测试获取配置
curl http://localhost:8080/apps/oauthwecom/admin/config \
  -H "Cookie: YOUR_SESSION_COOKIE"
```

### 代码格式化

```powershell
# 安装 PHP CodeSniffer（可选）
composer require --dev squizlabs/php_codesniffer

# 检查代码风格
./vendor/bin/phpcs --standard=PSR12 lib/
```

## 🎯 下一步

1. ✅ 完成本地环境搭建
2. 📝 配置企业微信应用
3. 🧪 测试 OAuth 登录流程
4. 🔄 测试用户同步功能
5. 📊 查看审计日志
6. 🚀 部署到生产环境

---

**祝开发顺利！** 🎉

有问题随时查看本文档或运行 `.\dev-setup.ps1 -Status` 检查状态。

