# Railway 部署指南

将 ACG 发卡系统部署到 Railway 的完整步骤。

---

## 一、前置准备

- GitHub 账号，项目已推送到 https://github.com/lhaiming65-lang/fantuan-and-heihei
- Railway 账号：https://railway.app （可用 GitHub 登录）

---

## 二、部署步骤

### 1. 创建 Railway 项目

1. 登录 https://railway.app
2. 点击 **New Project**
3. 选择 **Deploy from GitHub repo**
4. 选择并连接 `lhaiming65-lang/fantuan-and-heihei` 仓库
5. 选择 **main** 分支
6. 若项目在子目录，在服务 **Settings** → **Root Directory** 填写 `lhm-acg-faka-main`（否则留空）

### 2. 添加 MySQL 数据库

1. 在项目内点击 **+ New**
2. 选择 **Database** → **MySQL**
3. 等待 MySQL 服务创建完成

### 3. 配置 Web 服务

1. 点击你的 **Web 服务**（从 GitHub 部署的那个）
2. 进入 **Variables** 标签
3. 点击 **Add Variable** 或 **Reference**
4. 添加以下变量（从 MySQL 服务引用）：

| 变量名 | 值 | 说明 |
|--------|-----|------|
| MYSQLHOST | `${{MySQL.MYSQLHOST}}` | 引用 MySQL 服务 |
| MYSQLPORT | `${{MySQL.MYSQLPORT}}` | |
| MYSQLUSER | `${{MySQL.MYSQLUSER}}` | |
| MYSQLPASSWORD | `${{MySQL.MYSQLPASSWORD}}` | |
| MYSQLDATABASE | `${{MySQL.MYSQLDATABASE}}` | |

或直接添加 **MYSQL_URL**：
| 变量名 | 值 |
|--------|-----|
| MYSQL_URL | `${{MySQL.MYSQL_URL}}` |

### 4. 生成域名

1. 在 Web 服务中点击 **Settings**
2. 找到 **Networking** → **Generate Domain**
3. 生成后得到类似 `xxx.up.railway.app` 的地址

### 5. 等待部署

- Railway 会自动检测 Dockerfile 并构建
- 首次部署会自动初始化数据库
- 默认管理员：`admin@admin.com` / `admin123`

---

## 三、修改管理员密码

Railway 部署后无法使用 `docker exec`，需在后台修改：

1. 访问 `https://你的域名/admin`
2. 用 `admin@admin.com` / `admin123` 登录
3. 进入 **系统设置** 或 **个人中心** 修改密码

---

## 四、配置站点域名

在后台 **系统设置** 中：

- **站点域名**：`https://你的railway域名.up.railway.app`
- **回调域名**：同上

---

## 五、费用说明

- Railway 提供约 **$5 免费额度/月**
- 超出后按需计费
- 可设置用量上限避免意外扣费

---

## 六、常用操作

| 操作 | 位置 |
|------|------|
| 查看日志 | 服务 → Deployments → 点击部署 |
| 重启服务 | 服务 → ⋮ → Restart |
| 更新代码 | 推送 GitHub 后自动重新部署 |

---

## 故障排查

| 问题 | 处理 |
|------|------|
| 502 错误 | 检查 MySQL 变量是否配置，查看构建日志 |
| 数据库连接失败 | 确认 MYSQLHOST 等变量已引用 MySQL 服务 |
| 无法访问 | 检查是否已生成域名 |
