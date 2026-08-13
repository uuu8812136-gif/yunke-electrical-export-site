# AGENTS.md — 云科电气项目 Codex 指南

## 项目栈
- WordPress (latest) + Docker: `yke-wp`(8081→80) + `yke-db`(3306)
- 自定义插件: `wordpress/wp-content/plugins/yke-site-core` (单文件 450KB)
- 自定义主题: `wordpress/wp-content/themes/yke-global`
- Polylang 多语言 (英文为主, `/zh/` 中文)
- 站点地址: http://localhost:8081

## 启动
```bash
docker compose up -d
# 验证: curl.exe -s -o NUL -w "%{http_code}" http://localhost:8081/  # 期望 200
```

## 测试约定
- E2E 脚本: `tests/e2e/*.ps1` — 退出 0=PASS, 1=FAIL, 打印 `PASS:`/`FAIL:` 行
- Playwright MCP 用例: `tests/playwright-mcp/cases.md` — 由 Codex 通过 MCP 执行
- 截图归档: `.runtime/screenshots/`
- 版本号: 每次改动 bump `YKE_SITE_CORE_VERSION` (class-yke-site-core.php:16)

## 执行测试
```powershell
# 1. 烟雾 + 硬约束 + 全页面覆盖 (无浏览器依赖, curl + HTML 检查, 31 项)
powershell -File tests/e2e/smoke-core.ps1

# 2. 浏览器渲染类检查 (需 Node + playwright, 用系统 Chrome, 26 项)
#    首次安装: cd .runtime; npm i playwright --no-save
$env:NODE_PATH=(Resolve-Path ".\.runtime\node_modules").Path; node tests/e2e/render-tests.js

# 3. 联系表单测试 (CF7 字段 + 反垃圾 + 验证, 5 项)
$env:NODE_PATH=(Resolve-Path ".\.runtime\node_modules").Path; node tests/e2e/form-tests.js

# 4. 或通过 Playwright MCP (Codex): 把 tests/playwright-mcp/cases.md 末尾提示词粘贴给 Codex
```

## 测试范围
- A 烟雾 (S01-S07): 状态码 + 基本可达性
- B 联系页硬约束 (C01-C06): 极简字段、公司名差异化、无 CTA banner
- C 案例页硬约束 (K01-K08): 2×2 网格、卡片一致性、无 3D tilt、无 wpautop 污染
- D 全页面覆盖 (D01-D16): 4 产品线 + About + 4 案例详情 + 5 中文页 + sitemap
- E 导航与语言 (E01-E07): nav cluster、Products 下拉(16 链接: 5 产品线 + 11 型号)、语言切换器、中文跳转、hreflang
- F 案例详情页结构 (F01): sidebar + columns + cta + h1
- G 产品线页面结构 (G01-G05): hero + sections + cta
- H 联系表单 (H01-H05): CF7 字段、反垃圾 token、空提交验证

## 硬约束清单 (Codex 改动后必须守恒)
1. 联系页仅含: 公司名/联系人/邮箱/工作时间 + 询盘表单 (无多余引导文案)
2. 联系页公司名: 中文页="广州云科电气设备有限公司", 英文页="Guangzhou Yunke Electrical Equipment Co., Ltd."
3. 联系页用专用模板 `page-contact.html` (无共享 CTA banner)
4. `/distributor-partnership/` 301 重定向到 `/contact/` (代码 class-yke-site-core.php:1242-1245)
5. `/products/` 是产品总览页 (返回 200, 非跳转; 代码 build_products_overview_content)
6. 案例归档页桌面 2×2 网格, 移动 1 列堆叠
7. 案例卡片高度一致 (flex + `margin-top:auto` + 2 行截断)
8. `.yke-case-card*` 不在 3D tilt CSS 选择器组 (JS 选择器含但 CSS 无 transform 规则)
9. `yke-` 标记 HTML 渲染时绕过 wpautop (pre_render_block 过滤器)

## 关键 CSS class
- 案例归档页: `.yke-case-archive-grid` (容器), `.yke-case-card` (卡片), `.yke-case-card-{region,title,excerpt,supply,result,footer}`
- 联系页: `.yke-contact-details`, `.yke-contact-details__{company,person,list}`, `.yke-inquiry-shell`, `.yke-form-grid`

## 内容规则
- 所有文案必须基于项目文档 (PRODUCT.md + PPT), 禁止 AI 生成无依据内容
- PRODUCT.md 原则: "Show proof before claims"
- 标准化数据: "100+ countries" (跨页一致)
- 认证仅含: IEC 标准 + SGS/BV/Intertek 验货 (无 ISO 9001/14001/45001 自称)
