'use strict';
/**
 * Real disposable WordPress + Chromium public learning landing smoke.
 * Not a claim of WCAG 2.2 AA audit or Hostinger staging acceptance.
 */
const assert = require('assert');
const { chromium } = require('playwright');
const url = process.env.F05_TEST_URL;
assert.ok(/^http:\/\/127\.0\.0\.1:8080\/file05-wp\/\?page_id=\d+$/.test(url || ''), 'Expected disposable local WordPress URL');
(async()=>{
  const browser = await chromium.launch({headless:true});
  let cases=0;
  try {
    for (const viewport of [320,375,390,768,1024,1440,1920]) {
      for (const dir of ['ltr','rtl']) {
        const context=await browser.newContext({viewport:{width:viewport,height:850},reducedMotion:'reduce'});
        const page=await context.newPage();
        const errors=[];
        page.on('pageerror',e=>errors.push(e.message));
        const failedRequests=[];
        page.on('requestfailed',req=>failedRequests.push({url:req.url(),reason:req.failure()?.errorText}));
        const response=await page.goto(url,{waitUntil:'domcontentloaded',timeout:25000});
        assert.strictEqual(response.status(),200,'Public page must load without server error');
        await page.evaluate(dir=>{document.documentElement.setAttribute('dir',dir);},dir);
        const layout=await page.evaluate(()=>({
          documentWidth:document.documentElement.scrollWidth,
          viewport:window.innerWidth,
          shell:!!document.querySelector('.lsch-shell'),
          mainHeading:(document.querySelector('.lsch-shell h1')?.textContent||'').trim(),
          bodyText:document.body.innerText.length,
          styleRegistered:!!document.querySelector('link[href*="assets/css/learning.css"]'),
          sheetHref:document.querySelector('link[href*="assets/css/learning.css"]')?.href || '',
          loadedSheets:Array.from(document.styleSheets).map(sh=>sh.href).filter(Boolean),
          levelsGrid:getComputedStyle(document.querySelector('.lsch-grid-four')).display,
          cardDisplay:getComputedStyle(document.querySelector('.lsch-level-card')).display,
          overflowNodes:Array.from(document.querySelectorAll('body *')).filter(e=>{
            const box=e.getBoundingClientRect();
            return box.width>0&&box.height>0&&(box.right>innerWidth+2||box.left< -2);
          }).slice(0,18).map(e=>{
            const box=e.getBoundingClientRect(),cs=getComputedStyle(e);
            return {tag:e.tagName,classes:(e.className||'').toString().slice(0,100),
              text:(e.textContent||'').trim().slice(0,55),
              box:{x:box.x,w:box.width,right:box.right},minWidth:cs.minWidth,
              overflowX:cs.overflowX,display:cs.display};
          })
        }));
        assert.ok(layout.shell,'Native File05 shell is present at '+viewport+'/'+dir);
        assert.ok(layout.bodyText>40,'Non-empty accessible textual page at '+viewport+'/'+dir);
        assert.ok(layout.mainHeading.length>2,'Missing public learning h1 at '+viewport+'/'+dir);
        assert.strictEqual(layout.styleRegistered,true,
          'File05 learning stylesheet missing from File01-managed Learn page');
        assert.strictEqual(layout.levelsGrid,'grid',
          'Four-level cards lack CSS grid '+viewport+'/'+dir+': '+JSON.stringify({layout,failedRequests}));
        assert.strictEqual(layout.cardDisplay,'flex',
          'Level cards must use accessible responsive flex layout '+viewport+'/'+dir);
        assert.ok(layout.documentWidth<=layout.viewport+2,
          'Horizontal overflow '+viewport+'/'+dir+': '+JSON.stringify(layout));
        assert.deepStrictEqual(errors,[],'No uncaught JS errors on public landing');
        // CSS text resize is a *limited* automated reflow smoke, not real 200% screen-reader
        // or browser 400% zoom acceptance; retain manual G4/G5 gates separately.
        if ([320,390].includes(viewport)) {
          await page.evaluate(()=>{document.documentElement.style.fontSize='200%';});
          const magnified=await page.evaluate(()=>({
            width:document.documentElement.scrollWidth,
            viewport:window.innerWidth,
            roots:['html','body','.wp-site-blocks','.lsch-shell','.lsch-hero','.lsch-grid-four']
              .map(sel=>{const el=document.querySelector(sel);if(!el)return {sel,missing:true};
                const r=el.getBoundingClientRect(),cs=getComputedStyle(el);
                return {sel,rect:{x:r.x,w:r.width,right:r.right},scrollWidth:el.scrollWidth,
                  clientWidth:el.clientWidth,minWidth:cs.minWidth,maxWidth:cs.maxWidth,
                  display:cs.display,overflowX:cs.overflowX};}),
            internalOverflow:Array.from(document.querySelectorAll('.lsch-shell, .lsch-shell *')).filter(e=>e.scrollWidth>e.clientWidth+4).slice(0,30).map(e=>{
              const r=e.getBoundingClientRect(),cs=getComputedStyle(e);
              return {tag:e.tagName,cl:(e.className||'').toString().slice(0,90),text:(e.textContent||'').slice(0,50),
                scroll:e.scrollWidth,client:e.clientWidth,rect:{x:r.x,w:r.width,right:r.right},display:cs.display,
                direction:cs.direction,whiteSpace:cs.whiteSpace,overflowWrap:cs.overflowWrap,flexBasis:cs.flexBasis};
            }),
            overflowing:Array.from(document.querySelectorAll('body *')).filter(e=>{
              const r=e.getBoundingClientRect();
              return r.width>0&&r.height>0&&(r.right>innerWidth+2||r.left< -2);
            }).slice(0,16).map(e=>{
              const r=e.getBoundingClientRect(),cs=getComputedStyle(e);
              return {tag:e.tagName,cls:(e.className||'').toString().slice(0,80),
                text:(e.textContent||'').trim().slice(0,40),
                x:r.x,w:r.width,right:r.right,display:cs.display,minWidth:cs.minWidth};
            })
          }));
          assert.ok(magnified.width<=magnified.viewport+2,
            '200% text-size horizontal overflow '+viewport+'/'+dir+': '+JSON.stringify(magnified));
          await page.evaluate(()=>{document.documentElement.style.fontSize='';});
        }

        // Automated scoped axe checks complement, but never replace, assistive-tech/manual WCAG review.
        await page.addScriptTag({path:require.resolve('axe-core/axe.min.js')});
        const audit=await page.evaluate(async()=>window.axe.run(
          document.querySelector('.lsch-shell'),
          {runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21a','wcag21aa','wcag22aa']}}
        ));
        if (audit.violations.length) {
          const diagnostics=await page.evaluate(()=>{
            const item=document.querySelector('.lsch-level-card:nth-child(1)');
            if(!item)return null;
            const rect=item.getBoundingClientRect(),style=getComputedStyle(item);
            return {
              rect:{x:rect.x,y:rect.y,width:rect.width,height:rect.height,right:rect.right,bottom:rect.bottom},
              gridGap:getComputedStyle(item.parentElement).gap,
              direction:style.direction,
              clientRects:Array.from(item.getClientRects()).map(r=>({x:r.x,y:r.y,w:r.width,h:r.height})),
              neighbors:Array.from(item.parentElement.children).map(a=>{const r=a.getBoundingClientRect();return {x:r.x,y:r.y,w:r.width,h:r.height,link:a.href};})
            };
          });
          console.error('FILE05_AXE_DIAGNOSTIC',JSON.stringify({
            viewport,dir,
            violations:audit.violations.map(v=>({
              id:v.id,help:v.help,impact:v.impact,
              nodes:v.nodes.map(n=>({target:n.target,summary:n.failureSummary,html:n.html,related:n.any}))
            })),
            diagnostics
          }));
        }
        assert.deepStrictEqual(
          audit.violations.map(v=>({id:v.id,impact:v.impact,targets:v.nodes.map(n=>n.target)})),[],
          'Scoped File05 public landing axe issues at '+viewport+'/'+dir
        );

        // Synthetic RTL long-copy reflow stress only: not proof of approved Urdu translation.
        if (dir==='rtl' && [320,390].includes(viewport)) {
          await page.evaluate(()=>{
            document.querySelector('.lsch-hero h1').textContent=
              'کلاسیکی ہومیوپیتھی کی جامع تعلیم، تحقیقی مہارت اور تمام تعلیمی درجات';
            document.querySelector('.lsch-hero .lsch-kicker').textContent=
              'صابری کلاسیکی ہومیوپیتھی کا تعلیمی مرکز';
          });
          const urdu=await page.evaluate(()=>({
            pageWidth:document.documentElement.scrollWidth,viewport:innerWidth,
            direction:getComputedStyle(document.querySelector('.lsch-shell')).direction
          }));
          assert.ok(urdu.pageWidth<=urdu.viewport+2,
            'Synthetic long Urdu RTL headings overflow narrow screen: '+JSON.stringify(urdu));
        }
        await page.keyboard.press('Tab');
        const focused=await page.evaluate(()=>document.activeElement?.tagName||'');
        assert.ok(focused && focused!=='BODY','Keyboard navigation must acquire interactive focus at '+viewport+'/'+dir);
        cases++;
        await context.close();
      }
    }
    console.log('PASS: Real disposable File05 landing in headless Chromium, '+cases+
       ' combinations across seven widths and RTL/LTR with reduced motion, no overflow or uncaught JS errors. NOT WCAG/staging.');
  }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
