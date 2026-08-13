# Playwright MCP 测试用例清单 — 云科电气

> 范围: A 烟雾 + B 联系页硬约束 + C 案例页硬约束（浏览器渲染部分）
> 配套: `tests/e2e/smoke-core.ps1` 覆盖状态码与 HTML 内容检查；本文件覆盖需浏览器渲染的检查点
> 站点: http://localhost:8080
> MCP 配置: [~/.codex/config.toml#L51-L53](../../C:/Users/zo/.codex/config.toml)

## 调用约定

- 每个用例输出一行: `PASS: <ID> <detail>` 或 `FAIL: <ID> <actual> (expected <expected>)`
- 失败项截图到 `.runtime/screenshots/`，文件名: `<ID>-<timestamp>.png`
- 视口: 桌面 1440×900，移动 375×812
- 全部完成后输出汇总表

---

## A. 烟雾测试（浏览器补充）

### S01-home-title
```
browser_navigate(url="http://localhost:8080/")
browser_evaluate(function="() => document.title")
# 期望: title 非空且含 "YKE" 或 "Yunke"
```

### S02-zh-lang-attr
```
browser_navigate(url="http://localhost:8080/zh/")
browser_evaluate(function="() => document.documentElement.lang")
# 期望: "zh-CN" 或 "zh"
```

### S04-mv-switchgear-structure
```
browser_navigate(url="http://localhost:8080/products/mv-switchgear/")
browser_evaluate(function="""() => {
  const hero = document.querySelector('.yke-page-hero');
  const sections = document.querySelectorAll('.yke-page-section');
  const cta = document.querySelector('.yke-cta-band, .yke-form-shell');
  return {hasHero: !!hero, sectionCount: sections.length, hasCta: !!cta};
}""")
# 期望: hasHero=true, sectionCount>=6, hasCta=true (10 节结构用 class 而非 id)
```

---

## B. 联系页硬约束

### C01-contact-minimal-fields
```
browser_navigate(url="http://localhost:8080/contact/")
browser_snapshot()
# 期望 A11y 树仅含: 公司名/联系人/邮箱/工作时间 + 询盘表单
# 不应出现: 多余引导文案、多个 CTA、营销 banner
```

### C05-no-cta-banner
```
browser_evaluate(function="""() => {
  const cta = document.querySelector('.shared-inquiry-cta, .yke-cta-banner, [class*="cta-banner"]');
  return {hasCta: !!cta, ctaClass: cta ? cta.className : null};
}""")
# 期望: hasCta=false
```

### C06-no-placeholder-values
```
browser_evaluate(function="""() => {
  const text = document.body.innerText;
  const patterns = ['+1 555','123 Main St','placeholder@example.com','000-000','555-555'];
  const hits = patterns.filter(p => text.includes(p));
  return {hasPlaceholder: hits.length > 0, patterns: hits};
}""")
# 期望: hasPlaceholder=false
```

---

## C. 案例页硬约束（核心 — 需浏览器渲染）

### K01-desktop-2x2-grid
```
browser_resize(width=1440, height=900)
browser_navigate(url="http://localhost:8080/case-studies/")
browser_evaluate(function="""() => {
  const grid = document.querySelector('.yke-case-archive-grid');
  if(!grid) return {ok:false, reason:'grid not found'};
  const cs = getComputedStyle(grid);
  const cols = cs.gridTemplateColumns.split(' ').length;
  return {
    display: cs.display,
    columns: cols,
    childCount: grid.children.length,
    ok: cs.display === 'grid' && cols === 2 && grid.children.length >= 4
  };
}""")
# 期望: display='grid', columns=2, childCount>=4, ok=true
```

### K02-mobile-1-column
```
browser_resize(width=375, height=812)
browser_navigate(url="http://localhost:8080/case-studies/")
browser_evaluate(function="""() => {
  const grid = document.querySelector('.yke-case-archive-grid');
  const cs = getComputedStyle(grid);
  return {
    columns: cs.gridTemplateColumns.split(' ').length,
    ok: cs.gridTemplateColumns.split(' ').length === 1
  };
}""")
# 期望: columns=1, ok=true
```

### K03-card-height-consistency
```
browser_resize(width=1440, height=900)
browser_navigate(url="http://localhost:8080/case-studies/")
browser_evaluate(function="""() => {
  const cards = document.querySelectorAll('.yke-case-card');
  const heights = Array.from(cards).map(c => c.getBoundingClientRect().height);
  const maxDiff = Math.max(...heights) - Math.min(...heights);
  return {heights: heights, maxDiff: maxDiff, ok: maxDiff <= 8};
}""")
# 期望: maxDiff<=8px, ok=true (flex + margin-top:auto 对齐)
```

### K04-excerpt-2-line-clamp
```
browser_evaluate(function="""() => {
  const excerpts = document.querySelectorAll('.yke-case-card-excerpt');
  const results = Array.from(excerpts).map(e => {
    const cs = getComputedStyle(e);
    return {
      lineClamp: cs.webkitLineClamp || cs.lineClamp,
      display: cs.display,
      maxHeight: cs.maxHeight
    };
  });
  return {samples: results.slice(0,2), ok: results.every(r => r.lineClamp === '2' || r.display === '-webkit-box')};
}""")
# 期望: lineClamp='2', ok=true
```

### K05-card-structure-visual
```
browser_evaluate(function="""() => {
  const card = document.querySelector('.yke-case-card');
  if(!card) return {ok:false};
  return {
    hasRegion: !!card.querySelector('.yke-case-card-region'),
    hasTitle: !!card.querySelector('.yke-case-card-title'),
    hasExcerpt: !!card.querySelector('.yke-case-card-excerpt'),
    hasSupply: !!card.querySelector('.yke-case-card-supply'),
    hasResult: !!card.querySelector('.yke-case-card-result'),
    hasFooter: !!card.querySelector('.yke-case-card-footer')
  };
}""")
# 期望: 六项全 true
```

### K06-hover-effects
```
browser_evaluate(function="""() => {
  const card = document.querySelector('.yke-case-card');
  if(!card) return {ok:false};
  const cs = getComputedStyle(card);
  const img = card.querySelector('.yke-case-card-img');
  const imgCs = img ? getComputedStyle(img.querySelector('img, .yke-case-card-figure') || img) : null;
  const footer = card.querySelector('.yke-case-card-footer');
  const footerCs = footer ? getComputedStyle(footer) : null;
  return {
    cardTransition: cs.transition,
    imgTransition: imgCs ? imgCs.transition : null,
    footerBorderTop: footerCs ? footerCs.borderTopColor : null,
    ok: cs.transition.includes('transform') || cs.transition.includes('all')
  };
}""")
# 期望: cardTransition 含 transform, ok=true (悬停上移 6px + 图片 scale 1.06 + footer 变色)
```

### K07-no-3d-tilt-on-cards
```
browser_evaluate(function="""() => {
  const card = document.querySelector('.yke-case-card');
  if(!card) return {ok:false};
  const cs = getComputedStyle(card);
  return {
    transform: cs.transform,
    transformStyle: cs.transformStyle,
    perspective: cs.perspective,
    ok: cs.transformStyle !== 'preserve-3d' && cs.perspective === 'none'
  };
}""")
# 期望: transformStyle!='preserve-3d', perspective='none', ok=true
# (JS setupPointerTilt 选择器虽含 .yke-case-card，但 CSS 无 tilt transform 规则，视觉应无 3D 倾斜)
```

### K08-no-wpautop-pollution
```
browser_evaluate(function="""() => {
  const grid = document.querySelector('.yke-case-archive-grid');
  if(!grid) return {ok:false};
  const html = grid.innerHTML;
  // wpautop 污染特征: 空段落、标签间多余 <br>、<p> 包裹块级元素
  const emptyP = (html.match(/<p>\s*<\/p>/g) || []).length;
  const pWrappingArticle = (html.match(/<p>\s*<(article|div|h3)/g) || []).length;
  return {emptyP: emptyP, pWrappingBlock: pWrappingArticle, ok: emptyP === 0 && pWrappingArticle === 0};
}""")
# 期望: emptyP=0, pWrappingBlock=0, ok=true
```

---

## 视觉回归（可选）

### V01-cases-desktop-screenshot
```
browser_resize(width=1440, height=900)
browser_navigate(url="http://localhost:8080/case-studies/")
browser_take_screenshot(filename="cases-desktop-baseline.png", fullPage=true)
# 保存到 .runtime/screenshots/baseline/
```

### V02-contact-mobile-screenshot
```
browser_resize(width=375, height=812)
browser_navigate(url="http://localhost:8080/contact/")
browser_take_screenshot(filename="contact-mobile-baseline.png", fullPage=true)
```

---

## 执行提示词模板（粘贴给 Codex）

```
请使用 Playwright MCP 对云科电气网站执行测试:
- 站点: http://localhost:8080
- 视口: 先 1440x900 跑桌面, 再 375x812 跑移动
- 执行 tests/playwright-mcp/cases.md 中所有用例 (S01-S04, C01-C06, K01-K08)
- 每个用例输出 PASS:/FAIL: 行 + 实际值
- 失败项截图到 .runtime/screenshots/, 文件名含用例 ID
- 全部完成后输出汇总表 (PASS/FAIL 计数)
- 关键检查点:
  - K01: 桌面 2×2 网格 (gridTemplateColumns=2 列)
  - K02: 移动 1 列堆叠
  - K03: 卡片高度差 <=8px
  - K07: .yke-case-card 无 preserve-3d/perspective
```
