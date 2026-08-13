// YKE Playwright render tests — covers cases.md (S01/S04, C01/C05/C06, K01-K08)
// Uses system Chrome via channel:'chrome' to avoid chromium download
// Run: node tests/e2e/render-tests.js  (requires: npm i playwright, or npx playwright)
const { chromium } = require('playwright');

const BASE = 'http://localhost:8081';
const results = [];
let pass = 0, fail = 0;

function check(id, ok, detail) {
  const status = ok ? 'PASS' : 'FAIL';
  results.push(`${status}: ${id} ${detail}`);
  if (ok) pass++; else fail++;
}

(async () => {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const ctx = await browser.newContext();

  // ---------- Desktop 1440x900 ----------
  const desktop = await ctx.newPage();
  await desktop.setViewportSize({ width: 1440, height: 900 });

  // S01: home title
  await desktop.goto(BASE + '/');
  const title = await desktop.title();
  check('S01', title && /YKE|Yunke/i.test(title), `title="${title.slice(0,50)}"`);

  // S04: mv-switchgear structure (hero + multiple page-sections + cta)
  await desktop.goto(BASE + '/products/mv-switchgear/');
  const s4 = await desktop.evaluate(() => {
    const hero = document.querySelector('.yke-page-hero');
    const sections = document.querySelectorAll('.yke-page-section');
    const cta = document.querySelector('.yke-cta-band, .yke-form-shell');
    return { hasHero: !!hero, sectionCount: sections.length, hasCta: !!cta };
  });
  check('S04', s4.hasHero && s4.sectionCount >= 6 && s4.hasCta, `hero=${s4.hasHero} sections=${s4.sectionCount} cta=${s4.hasCta}`);

  // C01: contact minimal (no marketing fluff)
  await desktop.goto(BASE + '/contact/');
  const c01 = await desktop.evaluate(() => {
    const text = document.body.innerText;
    const fluff = ['We typically respond', 'Fill out the form below', 'reach our team', 'Get a quote today', 'limited time'];
    const hits = fluff.filter(p => text.includes(p));
    return { fluffHits: hits.length, ok: hits.length === 0 };
  });
  check('C01', c01.ok, `fluffHits=${c01.fluffHits}`);

  // C05: no CTA banner
  const c05 = await desktop.evaluate(() => {
    const cta = document.querySelector('.shared-inquiry-cta, .yke-cta-banner, [class*="cta-banner"]');
    return { hasCta: !!cta };
  });
  check('C05', !c05.hasCta, `hasCta=${c05.hasCta}`);

  // C06: no placeholder values
  const c06 = await desktop.evaluate(() => {
    const text = document.body.innerText;
    const patterns = ['+1 555','123 Main St','placeholder@example.com','000-000','555-555'];
    const hits = patterns.filter(p => text.includes(p));
    return { hits: hits.length };
  });
  check('C06', c06.hits === 0, `placeholderHits=${c06.hits}`);

  // ---------- Case studies (desktop) ----------
  await desktop.goto(BASE + '/case-studies/');

  // K01: desktop 2x2 grid
  const k01 = await desktop.evaluate(() => {
    const grid = document.querySelector('.yke-case-archive-grid');
    if (!grid) return { ok: false, reason: 'grid not found' };
    const cs = getComputedStyle(grid);
    const cols = cs.gridTemplateColumns.split(' ').filter(s=>s).length;
    return { display: cs.display, columns: cols, childCount: grid.children.length,
             ok: cs.display === 'grid' && cols === 2 && grid.children.length >= 4 };
  });
  check('K01', k01.ok, `display=${k01.display} cols=${k01.columns} children=${k01.childCount}`);

  // K03: card height consistency
  const k03 = await desktop.evaluate(() => {
    const cards = document.querySelectorAll('.yke-case-card');
    const heights = Array.from(cards).map(c => c.getBoundingClientRect().height);
    if (heights.length === 0) return { ok: false, reason: 'no cards' };
    const maxDiff = Math.max(...heights) - Math.min(...heights);
    return { heights: heights.map(h=>Math.round(h)), maxDiff: Math.round(maxDiff), ok: maxDiff <= 8 };
  });
  check('K03', k03.ok, `maxDiff=${k03.maxDiff}px heights=[${k03.heights}]`);

  // K04: excerpt 2-line clamp
  const k04 = await desktop.evaluate(() => {
    const excerpts = document.querySelectorAll('.yke-case-card-excerpt');
    if (excerpts.length === 0) return { ok: false, reason: 'no excerpts' };
    const results = Array.from(excerpts).map(e => {
      const cs = getComputedStyle(e);
      return { lineClamp: cs.webkitLineClamp, display: cs.display };
    });
    const allClamped = results.every(r => r.lineClamp === '2' || r.display === '-webkit-box');
    return { sample: results[0], ok: allClamped };
  });
  check('K04', k04.ok, `lineClamp=${k04.sample?.lineClamp} display=${k04.sample?.display}`);

  // K05: card structure (6 elements)
  const k05 = await desktop.evaluate(() => {
    const card = document.querySelector('.yke-case-card');
    if (!card) return { ok: false };
    return {
      hasRegion: !!card.querySelector('.yke-case-card-region'),
      hasTitle: !!card.querySelector('.yke-case-card-title'),
      hasExcerpt: !!card.querySelector('.yke-case-card-excerpt'),
      hasSupply: !!card.querySelector('.yke-case-card-supply'),
      hasResult: !!card.querySelector('.yke-case-card-result'),
      hasFooter: !!card.querySelector('.yke-case-card-footer')
    };
  });
  const k05All = k05.hasRegion && k05.hasTitle && k05.hasExcerpt && k05.hasSupply && k05.hasResult && k05.hasFooter;
  check('K05', k05All, `region=${k05.hasRegion} title=${k05.hasTitle} excerpt=${k05.hasExcerpt} supply=${k05.hasSupply} result=${k05.hasResult} footer=${k05.hasFooter}`);

  // K06: hover effects (transition includes transform)
  const k06 = await desktop.evaluate(() => {
    const card = document.querySelector('.yke-case-card');
    if (!card) return { ok: false };
    const cs = getComputedStyle(card);
    return { transition: cs.transition, ok: cs.transition.includes('transform') || cs.transition.includes('all') };
  });
  check('K06', k06.ok, `transition="${k06.transition?.slice(0,60)}"`);

  // K07: no 3D tilt (preserve-3d / perspective)
  const k07 = await desktop.evaluate(() => {
    const card = document.querySelector('.yke-case-card');
    if (!card) return { ok: false };
    const cs = getComputedStyle(card);
    return { transformStyle: cs.transformStyle, perspective: cs.perspective,
             ok: cs.transformStyle !== 'preserve-3d' && cs.perspective === 'none' };
  });
  check('K07', k07.ok, `transformStyle=${k07.transformStyle} perspective=${k07.perspective}`);

  // K08: no wpautop pollution
  const k08 = await desktop.evaluate(() => {
    const grid = document.querySelector('.yke-case-archive-grid');
    if (!grid) return { ok: false };
    const html = grid.innerHTML;
    const emptyP = (html.match(/<p>\s*<\/p>/g) || []).length;
    const pWrappingBlock = (html.match(/<p>\s*<(article|div|h3)/g) || []).length;
    return { emptyP, pWrappingBlock, ok: emptyP === 0 && pWrappingBlock === 0 };
  });
  check('K08', k08.ok, `emptyP=${k08.emptyP} pWrappingBlock=${k08.pWrappingBlock}`);

  await desktop.close();

  // ---------- Mobile 375x812 ----------
  const mobile = await ctx.newPage();
  await mobile.setViewportSize({ width: 375, height: 812 });
  await mobile.goto(BASE + '/case-studies/');

  // K02: mobile 1 column
  const k02 = await mobile.evaluate(() => {
    const grid = document.querySelector('.yke-case-archive-grid');
    if (!grid) return { ok: false };
    const cs = getComputedStyle(grid);
    const cols = cs.gridTemplateColumns.split(' ').filter(s=>s).length;
    return { columns: cols, ok: cols === 1 };
  });
  check('K02', k02.ok, `columns=${k02.columns}`);

  await mobile.close();

  // ---------- E. Navigation & language ----------
  const navPage = await ctx.newPage();
  await navPage.setViewportSize({ width: 1440, height: 900 });
  await navPage.goto(BASE + '/');
  await navPage.waitForTimeout(500);

  // E01: nav cluster exists
  const e01 = await navPage.evaluate(() => !!document.querySelector('.yke-header__navcluster'));
  check('E01', e01, `navCluster=${e01}`);

  // E02: Products dropdown has sub-menu with >= 5 links (product lines + models)
  const e02 = await navPage.evaluate(() => {
    const items = [...document.querySelectorAll('.menu-item, li')];
    const productsItem = items.find(el => /products/i.test(el.querySelector('a')?.innerText || ''));
    if (!productsItem) return { ok: false, reason: 'no Products menu item' };
    const sub = productsItem.querySelector('.sub-menu, ul');
    if (!sub) return { ok: false, reason: 'no sub-menu' };
    return { linkCount: sub.querySelectorAll('a').length, ok: sub.querySelectorAll('a').length >= 5 };
  });
  check('E02', e02.ok, `productsSubmenuLinks=${e02.linkCount}`);

  // E03: language switcher exists
  const e03 = await navPage.evaluate(() => !!document.querySelector('.yke-lang-switch, .polylang-switcher, .pll-parent-menu-item'));
  check('E03', e03, `langSwitcher=${e03}`);

  // E04: "中文" link points to /zh/
  const e04 = await navPage.evaluate(() => {
    const link = [...document.querySelectorAll('a')].find(a => /中文/.test(a.innerText));
    return { found: !!link, href: link?.getAttribute('href') || '', ok: !!link && /\/zh\/?$/.test(link.getAttribute('href') || '') };
  });
  check('E04', e04.ok, `zhLink=${e04.href}`);

  // E05: clicking "中文" navigates to /zh/
  await navPage.evaluate(() => {
    const link = [...document.querySelectorAll('a')].find(a => /中文/.test(a.innerText));
    link?.click();
  });
  await navPage.waitForLoadState('networkidle');
  const e05 = await navPage.evaluate(() => window.location.pathname);
  check('E05', /\/zh\/?/.test(e05), `navigatedTo=${e05}`);

  // E06: zh page has lang="zh" attribute
  const e06 = await navPage.evaluate(() => document.documentElement.lang);
  check('E06', /^zh/.test(e06), `lang=${e06}`);

  // E07: hreflang alternate tags exist
  const e07 = await navPage.evaluate(() => {
    const alts = document.querySelectorAll('link[rel=alternate][hreflang]');
    return { count: alts.length, ok: alts.length >= 2 };
  });
  check('E07', e07.ok, `hreflangCount=${e07.count}`);

  await navPage.close();

  // ---------- F. Case study detail page structure ----------
  const casePage = await ctx.newPage();
  await casePage.setViewportSize({ width: 1440, height: 900 });
  await casePage.goto(BASE + '/case-studies/saudi-arabia-industrial-park-power-distribution-project/');

  // F01: case detail page structure (sidebar + columns + cta)
  const f01 = await casePage.evaluate(() => ({
    hasSidebar: !!document.querySelector('.yke-case-study-details, [class*=sidebar]'),
    hasColumns: !!document.querySelector('.yke-case-single-columns'),
    hasCta: !!document.querySelector('.yke-cta-band, .yke-form-shell'),
    hasH1: !!document.querySelector('h1')
  }));
  check('F01', f01.hasSidebar && f01.hasColumns && f01.hasCta && f01.hasH1, `sidebar=${f01.hasSidebar} columns=${f01.hasColumns} cta=${f01.hasCta} h1=${f01.hasH1}`);

  await casePage.close();

  // ---------- G. Other product line pages structure ----------
  const productPage = await ctx.newPage();
  await productPage.setViewportSize({ width: 1440, height: 900 });
  const productLines = [
    { id: 'G01', path: '/products/lv-switchgear/', name: 'LV' },
    { id: 'G02', path: '/products/transformers/', name: 'Transformers' },
    { id: 'G03', path: '/products/new-energy-electrical/', name: 'NewEnergy' },
    { id: 'G04', path: '/products/prefabricated-substations/', name: 'Prefab' }
  ];
  for (const pl of productLines) {
    await productPage.goto(BASE + pl.path);
    const g = await productPage.evaluate(() => ({
      hasHero: !!document.querySelector('.yke-page-hero'),
      sectionCount: document.querySelectorAll('.yke-page-section').length,
      hasCta: !!document.querySelector('.yke-cta-band, .yke-form-shell')
    }));
    check(pl.id, g.hasHero && g.sectionCount >= 3 && g.hasCta, `${pl.name} hero=${g.hasHero} sections=${g.sectionCount} cta=${g.hasCta}`);
  }

  // G05: About page structure
  await productPage.goto(BASE + '/about-yke/');
  const g05 = await productPage.evaluate(() => ({
    hasHero: !!document.querySelector('.yke-page-hero'),
    sectionCount: document.querySelectorAll('.yke-page-section').length,
    hasCta: !!document.querySelector('.yke-cta-band, .yke-form-shell'),
    hasH1: !!document.querySelector('h1')
  }));
  check('G05', g05.hasHero && g05.sectionCount >= 1 && g05.hasCta && g05.hasH1, `about hero=${g05.hasHero} sections=${g05.sectionCount} cta=${g05.hasCta}`);

  await productPage.close();
  await browser.close();

  // ---------- Summary ----------
  console.log('=== YKE Playwright Render Tests ===');
  console.log(`Target: ${BASE}`);
  console.log('');
  results.forEach(r => console.log(r));
  console.log('');
  console.log('=== Summary ===');
  console.log(`PASS: ${pass}`);
  console.log(`FAIL: ${fail}`);
  console.log(`Total: ${pass + fail}`);
  process.exit(fail > 0 ? 1 : 0);
})();
