// YKE Contact Form (CF7) tests — H01-H05
// Run: $env:NODE_PATH="f:\云科电气\.runtime\node_modules"; node tests/e2e/form-tests.js
const { chromium } = require('playwright');

const BASE = 'http://localhost:8081';
const results = [];
let pass = 0, fail = 0;

function check(id, ok, detail) {
  results.push(`${ok ? 'PASS' : 'FAIL'}: ${id} ${detail}`);
  if (ok) pass++; else fail++;
}

(async () => {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(BASE + '/contact/');
  await page.waitForTimeout(500);

  // H01: form exists with .yke-inquiry-shell
  const h01 = await page.evaluate(() => !!document.querySelector('form.yke-inquiry-shell, .yke-inquiry-shell form'));
  check('H01', h01, `formPresent=${h01}`);

  // H02: required fields present (your-name, your-email, your-requirement)
  const h02 = await page.evaluate(() => {
    const form = document.querySelector('form');
    if (!form) return { ok: false };
    const names = [...form.querySelectorAll('input, textarea, select')].map(i => i.name);
    return {
      hasName: names.includes('your-name'),
      hasEmail: names.includes('your-email'),
      hasRequirement: names.includes('your-requirement'),
      names: names.filter(n => n && !n.startsWith('_'))
    };
  });
  check('H02', h02.hasName && h02.hasEmail && h02.hasRequirement, `name=${h02.hasName} email=${h02.hasEmail} requirement=${h02.hasRequirement} fields=[${h02.names}]`);

  // H03: submit button exists
  const h03 = await page.evaluate(() => !!document.querySelector('form [type=submit], form button'));
  check('H03', h03, `submitBtn=${h03}`);

  // H04: CF7 anti-spam tokens present (_wpcf7_* hidden fields)
  const h04 = await page.evaluate(() => {
    const form = document.querySelector('form');
    if (!form) return { ok: false };
    const hiddenInputs = [...form.querySelectorAll('input[type=hidden]')];
    const hasWpcf7Token = hiddenInputs.some(i => i.name === '_wpcf7');
    const hasPostedDataHash = hiddenInputs.some(i => i.name === '_wpcf7_posted_data_hash');
    return { ok: hasWpcf7Token && hasPostedDataHash, tokenCount: hiddenInputs.length };
  });
  check('H04', h04.ok, `cf7tokens=${h04.ok} (hiddenInputs=${h04.tokenCount})`);

  // H05: empty submission shows validation feedback (no crash)
  // Click submit without filling required fields, expect CF7 validation
  const validationShown = await page.evaluate(() => {
    return new Promise(resolve => {
      const form = document.querySelector('form');
      if (!form) return resolve({ ok: false, reason: 'no form' });
      // Listen for CF7 events
      let validationResponse = false;
      document.addEventListener('wpcf7invalid', () => { validationResponse = true; }, { once: true });
      document.addEventListener('wpcf7submit', () => { validationResponse = true; }, { once: true });
      // Also check for any AJAX response
      const observer = new MutationObserver(() => {
        const response = document.querySelector('.wpcf7-response-output, .yke-inquiry-notice');
        if (response && response.offsetParent !== null) {
          validationResponse = true;
          observer.disconnect();
        }
      });
      const responseEl = document.querySelector('.wpcf7-response-output, .yke-inquiry-notice, .yke-form-shell');
      if (responseEl) {
        observer.observe(responseEl.parentElement, { childList: true, subtree: true, characterData: true });
      }
      // Submit empty form
      form.requestSubmit ? form.requestSubmit() : form.submit();
      setTimeout(() => {
        observer.disconnect();
        resolve({ ok: validationResponse, reason: validationResponse ? 'validation triggered' : 'no response' });
      }, 3000);
    });
  });
  check('H05', validationShown.ok, `validation=${validationShown.ok} (${validationShown.reason})`);

  await page.close();
  await browser.close();

  console.log('=== YKE Contact Form Tests ===');
  console.log(`Target: ${BASE}/contact/`);
  console.log('');
  results.forEach(r => console.log(r));
  console.log('');
  console.log('=== Summary ===');
  console.log(`PASS: ${pass}`);
  console.log(`FAIL: ${fail}`);
  console.log(`Total: ${pass + fail}`);
  process.exit(fail > 0 ? 1 : 0);
})();
