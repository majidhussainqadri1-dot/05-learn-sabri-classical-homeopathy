'use strict';
/** Learning buttons must announce persisted progress/bookmark outcomes beside the control. */
const fs=require('fs'),vm=require('vm'),assert=require('assert');
const script=fs.readFileSync('05-learn-sabri-classical-homeopathy/assets/js/learning.js','utf8');
const notices={progress:{textContent:'',dataset:{}},bookmark:{textContent:'',dataset:{}}};
const handlers={};
const make=(kind,attrib)=>({
  dataset:attrib,disabled:false,attrs:{},
  closest(selector){
    assert.ok(selector.includes('.lsch-learning-tools'));
    return {querySelector(select){return select==='[data-lsch-status]'?notices[kind]:null;}};
  },
  setAttribute(k,v){this.attrs[k]=v;},
  addEventListener(event,handler){handlers[kind+':'+event]=handler;}
});
const progress=make('progress',{lschProgress:'17'});
const bookmark=make('bookmark',{lschType:'lsch_lesson',lschBookmark:'17'});
const document={
  querySelectorAll(selector){
    if(selector==='[data-lsch-progress]')return [progress];
    if(selector==='[data-lsch-bookmark]')return [bookmark];
    return [];
  },
  querySelector(){return null;}
};
const calls=[];
let failBookmark=false;
async function fetch(url,opts={}){
  const method=opts.method||'GET';calls.push({url,method});
  let data;
  if(url.includes('progress')&&method==='GET')data={state:'in_progress',version:12,percent:45};
  else if(url.includes('progress')&&method==='PUT'){
    assert.strictEqual(JSON.parse(opts.body).version,12,'Exact progress revision required');
    data={state:'completed',version:13,percent:100};
  }else if(url.includes('bookmark')&&method==='PUT')data={active:true};
  else throw Error('Unexpected request '+url);
  if (url.includes('bookmark')&&failBookmark) {
    return {ok:false,json:async()=>({message:'Bookmark service unavailable'}),headers:{get:()=>null}};
  }
  return {ok:true,json:async()=>data,headers:{get:()=>null}};
}
const window={LSCH_APP:{loggedIn:true,root:'/wp-json/file05/v2/',nonce:'dummy',strings:{saved:'Saved'}},crypto:{randomUUID:()=> 'test-idempotency-00017'},location:{href:''}};
vm.runInNewContext(script,{document,window,fetch,console,setTimeout,clearTimeout});
(async()=>{
  await handlers['progress:click']();
  assert.match(notices.progress.textContent,/Saved.*100%/);
  assert.strictEqual(notices.progress.dataset.state,'success');
  assert.strictEqual(progress.attrs['aria-pressed'],'true');
  await handlers['bookmark:click']();
  assert.match(notices.bookmark.textContent,/Saved/);
  assert.strictEqual(notices.bookmark.dataset.state,'success');
  assert.strictEqual(bookmark.attrs['aria-pressed'],'true');
  assert.strictEqual(calls.filter(x=>x.method==='PUT').length,2);
  failBookmark=true;
  await handlers['bookmark:click']();
  assert.strictEqual(notices.bookmark.dataset.state,'error',
    'A failed protected mutation is announced separately as error');
  assert.match(notices.bookmark.textContent,/service unavailable/i);
  assert.strictEqual(bookmark.disabled,false,'Retry is permitted after backend failure');
  assert.strictEqual(bookmark.attrs['aria-pressed'],'true',
    'Last confirmed state must not be changed by failed mutation');
  console.log('PASS: Progress/bookmark success/error announced beside controls without changing verified state on failure.');
})().catch(e=>{console.error(e);process.exit(1);});
