# NextCloud 企业微信 OAuth 认证插件

这是一个 NextCloud 插件，支持通过企业微信 OAuth 2.0 进行用户认证和同步。

## 🚀 快速开始

### Windows 11 本地开发环境（推荐）

**双击即可启动**：`start-dev.bat`

或查看完整文档：[Docker 开发环境设置指南](./DOCKER_SETUP.md)

### 服务器部署

查看详细文档：[安装指南](./INSTALL.md)

## 功能特性

### 1. OAuth 2.0 认证
- 支持企业微信扫码登录
- 支持企业微信 APP 内免登
- 自动识别设备类型（PC、移动端、企业微信APP）
- 安全的 OAuth 2.0 授权流程

### 2. 用户管理
- 自动创建用户账号
- 支持用户信息同步
- 智能用户匹配（通过邮箱、手机号、用户名）
- 用户组映射（部门对应用户组）

### 3. 组织架构同步
- 定时同步企业微信组织架构
- 全量和增量同步支持
- 部门映射为 NextCloud 用户组
- 可配置同步频率和范围

### 4. 安全特性
- OAuth 2.0 状态验证（防CSRF）
- 完整的审计日志
- 敏感数据加密存储
- 支持强制企业微信登录

### 5. 管理功能
- 可视化配置界面
- 连接测试功能
- 手动触发同步
- 同步状态监控

## 安装要求

- NextCloud 30 或更高版本
- PHP 8.1 或更高版本
- 企业微信账号和应用

## 安装步骤

1. 将插件下载到 NextCloud 的 `apps` 目录：
   ```bash
   cd /path/to/nextcloud/apps
   git clone <repository-url> oauthwecom
   ```

2. 安装 PHP 依赖：
   ```bash
   cd oauthwecom
   composer install
   ```

3. 安装前端依赖并构建：
   ```bash
   npm install
   npm run build
   ```

4. 在 NextCloud 管理界面中启用插件

5. 配置企业微信应用参数

## 配置说明

### 企业微信应用配置

1. 登录[企业微信管理后台](https://work.weixin.qq.com/)
2. 进入"应用管理" -> "自建" -> "创建应用"
3. 记录以下信息：
   - 企业ID (CorpID)
   - 应用AgentID
   - 应用Secret

4. 配置授权回调域：
   - 在应用详情中设置"网页授权及JS-SDK"
   - 添加您的 NextCloud 域名

### NextCloud 插件配置

1. 进入 NextCloud 设置 -> 管理 -> 企业微信OAuth认证
2. 填写企业微信应用信息：
   - 企业ID
   - 应用AgentID
   - 应用Secret

3. 配置登录选项：
   - ☑ 启用企业微信登录
   - ☑ 强制使用企业微信登录（可选）
   - ☑ 自动创建用户（推荐）

4. 配置用户同步（可选）：
   - ☑ 启用自动同步
   - 设置同步频率（小时）
   - 选择用户匹配字段
   - 设置默认用户配额

5. 点击"测试连接"验证配置
6. 点击"保存设置"

## 使用方法

### 用户登录

#### PC 端登录
1. 访问 NextCloud 登录页面
2. 点击"企业微信登录"按钮
3. 使用企业微信扫码完成登录

#### 移动端登录
1. 在企业微信APP中打开 NextCloud 链接
2. 自动跳转到企业微信授权
3. 授权后自动登录

### 管理员操作

#### 手动同步用户
1. 进入管理后台
2. 点击"立即同步"按钮
3. 查看同步结果

#### 查看审计日志
1. 进入管理后台
2. 查看登录和同步日志
3. 可按时间、用户、状态筛选

## 开发说明

### 项目结构

```
oauthwecom/
├── appinfo/
│   ├── info.xml          # 插件信息
│   └── routes.php        # 路由定义
├── lib/
│   ├── AppInfo/          # 应用初始化
│   ├── Controller/       # 控制器
│   ├── Db/               # 数据库实体和映射
│   ├── Login/            # 登录提供者
│   ├── Migration/        # 数据库迁移
│   ├── Service/          # 业务逻辑服务
│   ├── Settings/         # 设置页面
│   └── BackgroundJob/    # 后台任务
├── src/                  # 前端源码
│   ├── components/       # Vue 组件
│   ├── admin-settings.js # 管理设置
│   └── admin-settings.css# 样式
├── templates/            # PHP 模板
└── tests/                # 测试文件
```

### 核心类说明

- `WeComApiService`: 企业微信 API 调用封装
- `OAuthController`: OAuth 认证流程处理
- `SyncService`: 用户和组织架构同步
- `ConfigService`: 配置管理
- `AuditService`: 审计日志记录
- `DeviceDetectService`: 设备类型识别

### 数据库表

- `wecom_user_mapping`: 用户映射关系
- `wecom_audit_logs`: 审计日志

### 开发命令

```bash
# 安装依赖
composer install
npm install

# 代码检查
composer lint
composer cs:check

# 代码格式化
composer cs:fix

# 运行测试
composer test:unit

# 构建前端
npm run build

# 开发模式（监听文件变化）
npm run watch
```

## 常见问题

### Q: 用户无法登录？
A: 
1. 检查企业微信应用配置是否正确
2. 确认回调域名已在企业微信后台配置
3. 查看 NextCloud 日志文件
4. 使用"测试连接"功能验证配置

### Q: 用户同步失败？
A:
1. 确认企业微信应用有通讯录权限
2. 检查同步的部门ID是否正确
3. 查看同步日志了解详细错误

### Q: 如何禁用本地密码登录？
A:
1. 在设置中启用"强制使用企业微信登录"
2. 确保管理员账号已绑定企业微信
3. 建议先测试后再强制所有用户

## 安全建议

1. 始终使用 HTTPS 部署 NextCloud
2. 定期更新 NextCloud 和插件版本
3. 启用审计日志并定期检查
4. 为敏感操作设置权限限制
5. 定期备份数据库

## 许可证

AGPL-3.0-or-later

## 支持

如有问题或建议，请提交 Issue 或 Pull Request。

## 更新日志

### v1.0.0 (2024-11-12)
- 初始版本发布
- 支持企业微信 OAuth 2.0 认证
- 支持用户和组织架构同步
- 完整的审计日志功能
- 可视化配置界面
