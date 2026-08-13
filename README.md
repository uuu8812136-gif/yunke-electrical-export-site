# 云科电气出口独立站

面向海外采购经理、EPC 团队与区域经销商的双语 WordPress 独立站。项目围绕电力设备选型、项目案例、出口能力与询价转化组织内容，以英文为主要商业路径，并提供完整中文辅助页面。

> 本仓库是脱敏后的项目源码与资料副本。数据库、媒体上传、缓存、备份、浏览器状态、SMTP 配置和其他运行时凭据均不纳入版本控制。

## 核心功能

- 英文主站与 `/zh/` 中文页面，包含语言切换和 `hreflang` 支持。
- 变压器、高低压开关柜、箱式变电站与新能源配套等产品内容。
- 项目案例、企业能力、认证/验货信息与全球市场展示。
- 询价联系表单、反垃圾校验和经销商合作入口。
- SEO 基础设施、站点地图、重定向及结构化导航。
- 桌面端与移动端响应式布局、键盘焦点、减少动态效果等可访问性设计。
- 烟雾测试、页面结构约束测试、浏览器渲染测试与表单测试。

## 技术栈

- WordPress + MySQL 8
- Docker Compose
- 自定义区块主题 `yke-global`
- 自定义站点插件 `yke-site-core`
- Polylang、Contact Form 7、CFDB7、Redirection、Yoast SEO、WP Mail SMTP（按下方版本清单安装）
- PowerShell、Node.js 与 Playwright 测试脚本

## 快速启动

### 前置条件

- Docker Desktop（支持 Docker Compose v2）
- 本机端口 `8081` 可用

### 本地运行

```bash
docker compose up -d
```

启动后访问 <http://localhost:8081>。首次启动需要等待 WordPress 和 MySQL 完成初始化，然后在 WordPress 后台启用仓库内的主题与自定义插件，并按“第三方插件”章节安装其余依赖。

查看容器状态与日志：

```bash
docker compose ps
docker compose logs --tail=100 wordpress
```

停止服务：

```bash
docker compose down
```

若需清空本地数据库和上传卷并重新初始化，可在确认不再需要本地数据后执行 `docker compose down -v`。该操作会删除本项目的本地 Docker 卷数据。

## 测试

基础烟雾与页面约束检查（Windows PowerShell）：

```powershell
powershell -File tests/e2e/smoke-core.ps1
```

浏览器渲染与联系表单测试需要本地安装 Node.js 和 Playwright；依赖应安装在被忽略的 `.runtime/` 目录中：

```powershell
New-Item -ItemType Directory -Force .runtime | Out-Null
Push-Location .runtime
npm install playwright --no-save
Pop-Location
$env:NODE_PATH=(Resolve-Path ".\.runtime\node_modules").Path
node tests/e2e/render-tests.js
node tests/e2e/form-tests.js
```

更完整的用例与不可破坏约束见 [`AGENTS.md`](AGENTS.md) 和 [`tests/playwright-mcp/cases.md`](tests/playwright-mcp/cases.md)。

## 目录结构

```text
.
├── wordpress/wp-content/
│   ├── themes/yke-global/       # 自定义区块主题
│   └── plugins/yke-site-core/   # 自定义业务与内容初始化插件
├── content/
│   ├── site-copy/               # 中英文结构化站点文案
│   └── assets-placeholders/     # 设计阶段占位素材与清单
├── docs/                        # 需求、架构、开发和部署说明
├── tests/                       # E2E 与 Playwright 测试
├── PRODUCT.md                   # 产品定位与设计原则
├── preview-index.html           # 静态审阅入口
└── docker-compose.yml           # 本地 WordPress/MySQL 环境
```

## 第三方插件

为了避免复制供应商源码、缩小仓库并保持依赖可更新，脱敏副本不提交可从 WordPress 插件目录重新安装的第三方插件。原开发副本使用的版本如下：

| 插件 | 目录/slug | 原使用版本 |
| --- | --- | --- |
| Contact Form 7 | `contact-form-7` | 6.1.6 |
| Contact Form CFDB7 | `contact-form-cfdb7` | 1.3.6 |
| Polylang | `polylang` | 3.8.5 |
| Redirection | `redirection` | 5.8.0 |
| Yoast SEO | `wordpress-seo` | 27.9 |
| WP Mail SMTP | `wp-mail-smtp` | 4.8.0 |

在 WordPress 后台的“插件 → 安装插件”中按名称安装并启用这些插件；需要严格复现时选择上表版本。安装前应确认版本仍受支持且无已知安全问题，生产部署优先使用经兼容性测试的最新安全版本。

当前 `docker-compose.yml` 仍保留原开发环境的目录挂载定义。首次启动前可先通过 WordPress CLI 下载对应插件到上述目录，或移除第三方插件的本地挂载行、让 WordPress 使用容器/卷内安装的插件。示例（WordPress 容器已启动后）：

```bash
docker compose exec wordpress wp plugin install contact-form-7 contact-form-cfdb7 polylang redirection wordpress-seo wp-mail-smtp --activate --allow-root
```

若基础镜像未包含 WP-CLI，请改用后台安装或临时 WP-CLI 容器。插件版权与许可证遵循各上游项目声明；升级后务必运行完整测试。

## 内容与开发约束

- 网站陈述必须以 `PRODUCT.md`、项目文档和原始企业资料为依据，遵循“先展示证据，再提出主张”。
- 标准化出口数据、认证范围和联系信息需跨页面保持一致。
- 联系页、产品总览、案例网格、语言导航等硬约束记录在 `AGENTS.md`。
- 修改 `yke-site-core` 后同步更新其版本号，并执行相关烟雾、渲染和表单测试。
- 生产环境的域名、SMTP、数据库账号、最终联系信息和正式图片必须通过部署环境单独配置。

## 安全与发布边界

本仓库有意不包含：

- `.env`、`wp-config.php`、SMTP 密钥及云服务令牌；
- WordPress 数据库、导出文件、备份、缓存和 `uploads` 运行时媒体；
- 浏览器 cookies、登录状态、测试录屏/截图和网络/控制台采集；
- 本机 IDE/代理配置、依赖缓存与构建产物。

`docker-compose.yml` 仅用于隔离的本地开发。部署到公网前，应使用独立强凭据、秘密管理服务、HTTPS、最小权限数据库账号、备份策略和受支持的 WordPress/插件版本，且不要把生产秘密提交到 Git。

## 项目资料

产品说明、需求与技术方案位于 `PRODUCT.md` 和 `docs/`；仓库根目录还保留了项目输入用的企业介绍、竞品调研和独立站资料文件，便于追溯站点内容来源。
