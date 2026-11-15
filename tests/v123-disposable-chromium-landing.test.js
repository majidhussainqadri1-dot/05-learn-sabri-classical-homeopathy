'use strict';
/**
 * Real disposable WordPress + Chromium public learning landing smoke.
 * Not a claim of WCAG 2.2 AA audit or Hostinger staging acceptance.
 */
const assert = require('assert');
const { chromium } = require('playwright');
const url = process.env.F05_TEST_URL;
assert.ok(/^http:\/\/127\.0\.0\.1:8080\/\?page_id=\d+$/.test(url || ''), 'Expected disposable local WordPress URL');
(async()=>{
  const browser = await chromium.launch({headless:true});
  let cases=0;
  try {
    for (const viewport of [320,375,768,1024,1440]) {
      for (const dir of ['ltr','rtl']) {
        const context=await browser.newContext({viewport:{width:viewport,height:850},reducedMotion:'reduce'});
        const page=await context.newPage();
        const errors=[];
        page.on('pageerror',e=>errors.push(e.message));
        const response=await page.goto(url,{waitUntil:'domcontentloaded',timeout:25000});
        assert.strictEqual(response.status(),200,'Public page must load without server error');
        await page.evaluate(dir=>{document.documentElement.setAttribute('dir',dir);},dir);
        const layout=await page.evaluate(()=>({
          documentWidth:document.documentElement.scrollWidth,
          viewport:window.innerWidth,
          shell:!!document.querySelector('.lsch-shell'),
          mainHeading:(document.querySelector('.lsch-shell h1')?.textContent||'').trim(),
          bodyText:document.body.innerText.length
        }));
        assert.ok(layout.shell,'Native File05 shell is present at '+viewport+'/'+dir);
        assert.ok(layout.bodyText>40,'Non-empty accessible textual page at '+viewport+'/'+dir);
        assert.ok(layout.mainHeading.length>2,'Missing public learning h1 at '+viewport+'/'+dir);
        assert.ok(layout.documentWidth<=layout.viewport+2,
          'Horizontal overflow '+viewport+'/'+dir+': '+JSON.stringify(layout));
        assert.deepStrictEqual(errors,[],'No uncaught JS errors on public landing');
        await page.keyboard.press('Tab');
        const focused=await page.evaluate(()=>document.activeElement?.tagName||'');
        assert.ok(focused && focused!=='BODY','Keyboard navigation must acquire interactive focus at '+viewport+'/'+dir);
        cases++;
        await context.close();
      }
    }
    console.log('PASS: Real disposable File05 landing in headless Chromium, '+cases+
       ' combinations across five widths and RTL/LTR with reduced motion, no overflow or uncaught JS errors. NOT WCAG/staging.');
  }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
