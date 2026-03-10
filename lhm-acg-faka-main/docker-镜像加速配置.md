# Docker 镜像加速配置

当遇到「服务器返回无效或不可识别的响应」或拉取镜像很慢时，按以下方式配置。

## 方式一：Docker Desktop 图形界面（推荐）

1. 打开 **Docker Desktop**
2. 点击右上角 **设置（齿轮图标）**
3. 进入 **Docker Engine**
4. 在 JSON 配置中添加或修改 `registry-mirrors`，例如：

```json
{
  "registry-mirrors": [
    "https://docker.m.daocloud.io",
    "https://docker.xuanyuan.me",
    "https://hub-mirror.c.163.com"
  ],
  "insecure-registries": [],
  "debug": false,
  "experimental": false
}
```

5. 点击 **Apply & Restart** 重启 Docker

## 方式二：本项目已使用镜像源

`docker-compose.yml` 和 `Dockerfile` 已改为从 DaoCloud 镜像拉取：
- `docker.m.daocloud.io/library/mysql:8.0`
- `docker.m.daocloud.io/library/php:8.2-apache`

若 DaoCloud 不可用，可改回官方源：
- `mysql:8.0`
- `php:8.2-apache`

## 方式三：重置网络（若仍失败）

以**管理员**身份打开 CMD，执行：

```
netsh winsock reset
```

然后**重启电脑**，再尝试 `docker compose up -d --build`。

## 方式四：使用代理

若使用 Clash、V2Ray 等代理，可开启 **TUN 模式** 或 **系统代理**，让 Docker 走代理访问外网。
