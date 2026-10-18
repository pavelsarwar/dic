(function(){
 const configured=true;
 async function publicList(type){
  const r=await fetch('api/index.php?action=public&type='+encodeURIComponent(type),{credentials:'same-origin'});
  if(!r.ok)throw Error('Content loading failed');return (await r.json()).items||[];
 }
 async function settings(){
  const r=await fetch('api/index.php?action=settings',{credentials:'same-origin'});
  if(!r.ok)throw Error('Settings loading failed');return (await r.json()).settings||{};
 }
 const escape=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 function safeUrl(s){try{let u=new URL(s,location.href);return ['https:','http:'].includes(u.protocol)?u.href:''}catch{return ''}}
 async function shared(){
  const header=document.querySelector('[data-cms-header]'),footer=document.querySelector('[data-cms-footer]');
  if(!header&&!footer)return;
  let s={};try{s=await settings()}catch(e){console.warn(e)}
  const brand=escape(s.identity?.name||'ডেমরা আইডিয়াল কলেজ');
  const logo=s.identity?.logo_url?safeUrl(s.identity.logo_url):'';
  // One navigation model for every page; new HTML pages automatically use clean URLs.
  const groups=[
   {label:'প্রচ্ছদ',url:'/'},
   {label:'আমাদের কলেজ',children:[['কলেজ পরিচিতি','/about'],['অধ্যক্ষের বাণী','/principal'],['প্রশাসন','/administration'],['শিক্ষকবৃন্দ','/faculty']]},
   {label:'একাডেমিক',children:[['শিক্ষা কার্যক্রম','/academic'],['ক্লাস রুটিন','/routine'],['পরীক্ষার সময়সূচি','/exam'],['ফলাফল','/results']]},
   {label:'ভর্তি',url:'/admission'},
   {label:'নোটিশ ও সংবাদ',children:[['নোটিশ বোর্ড','/notices'],['কলেজ সংবাদ','/news'],['অনুষ্ঠান ও কার্যক্রম','/events']]},
   {label:'গ্যালারি',url:'/gallery'},
   {label:'আরও',children:[['নীতিমালা','/policies'],['যোগাযোগ','/contact']]}
  ];
  const clean=path=>{const s=String(path||'').trim();if(s==='/'||s==='index.html'||s==='/index.html')return '/';const m=s.match(/^\/?([a-z0-9-]+)(?:\.html)?(#[a-z0-9_-]+)?$/i);return m?'/'+m[1]+(m[2]||''):''};
  // Existing CMS navigation labels can override the default label for matching pages.
  const overrides=new Map((Array.isArray(s.navigation)?s.navigation:[]).map(x=>[clean(x?.url),String(x?.label||'').trim()]).filter(x=>x[0]&&x[1]));
  const current=location.pathname.replace(/\.html$/i,'').replace(/\/$/,'')||'/';
  const item=(label,url)=>'<a href="'+escape(url)+'"'+(current===url?' aria-current="page"':'')+'>'+escape(overrides.get(url)||label)+'</a>';
  const nav=groups.map(g=>g.children?'<div class="cms-dropdown"><button class="cms-drop-trigger" type="button" aria-expanded="false">'+escape(g.label)+' <span aria-hidden="true">⌄</span></button><div class="cms-submenu">'+g.children.map(([l,u])=>item(l,u)).join('')+'</div></div>':item(g.label,g.url)).join('');
  if(header)header.innerHTML='<header class="cms-header"><div class="cms-shell"><a class="cms-brand" href="/">'+(logo?'<img src="'+escape(logo)+'" alt="কলেজ লোগো">':'<span class="cms-seal">দি</span>')+'<span>'+brand+'<small>Demra Ideal College</small></span></a><button class="cms-menu-btn" type="button" aria-expanded="false" aria-controls="cms-navigation" aria-label="মেনু খুলুন">☰ মেনু</button><nav id="cms-navigation" class="cms-navigation" aria-label="প্রধান মেনু">'+nav+'</nav></div></header>';
  if(header){
   const btn=header.querySelector('.cms-menu-btn'),navigation=header.querySelector('nav');
   const closeDrops=except=>header.querySelectorAll('.cms-dropdown').forEach(d=>{if(d!==except){d.classList.remove('expanded');d.querySelector('button').setAttribute('aria-expanded','false')}});
   btn.addEventListener('click',()=>{const open=btn.getAttribute('aria-expanded')!=='true';btn.setAttribute('aria-expanded',String(open));navigation.classList.toggle('open',open);if(!open)closeDrops()});
   header.querySelectorAll('.cms-drop-trigger').forEach(trigger=>trigger.addEventListener('click',()=>{const parent=trigger.parentElement;const open=!parent.classList.contains('expanded');closeDrops(parent);parent.classList.toggle('expanded',open);trigger.setAttribute('aria-expanded',String(open))}));
   header.addEventListener('keydown',e=>{if(e.key==='Escape'){closeDrops();navigation.classList.remove('open');btn.setAttribute('aria-expanded','false');btn.focus()}});
   document.addEventListener('click',e=>{if(!header.contains(e.target))closeDrops()});
  }
  if(footer)footer.innerHTML='<footer class="cms-footer"><div class="cms-shell"><strong>'+brand+'</strong><span>'+escape(s.contact?.address||'ঢাকা, বাংলাদেশ')+'</span><span>© '+new Date().getFullYear()+' | সর্বস্বত্ব সংরক্ষিত</span></div></footer>';
 }
 window.DIC_CMS={configured,publicList,settings,shared,escape,safeUrl};
 document.addEventListener('DOMContentLoaded',shared);
})();