(function(){
 const cfg=window.DIC_CMS_CONFIG||{};
 const configured=!!(cfg.supabaseUrl&&cfg.supabaseAnonKey);
 const base=String(cfg.supabaseUrl||'').replace(/\/$/,'');
 const headers=()=>({'apikey':cfg.supabaseAnonKey,'Content-Type':'application/json'});
 async function publicList(type){
  if(!configured)return [];
  const url=base+'/rest/v1/cms_content?type=eq.'+encodeURIComponent(type)+'&published=eq.true&select=*&order=sort_order.asc,created_at.desc';
  const r=await fetch(url,{headers:headers()});if(!r.ok)throw Error('Content loading failed');return r.json();
 }
 async function settings(){
  if(!configured)return {};
  const r=await fetch(base+'/rest/v1/site_settings?select=key,value',{headers:headers()});
  if(!r.ok)throw Error('Settings loading failed');
  return Object.fromEntries((await r.json()).map(x=>[x.key,x.value]));
 }
 const escape=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 function safeUrl(s){try{let u=new URL(s,location.href);return ['https:','http:'].includes(u.protocol)?u.href:''}catch{return ''}}
 async function shared(){
  const header=document.querySelector('[data-cms-header]'),footer=document.querySelector('[data-cms-footer]');
  if(!header&&!footer)return;
  let s={};try{s=await settings()}catch(e){console.warn(e)}
  const brand=escape(s.identity?.name||'ডেমরা আইডিয়াল কলেজ');
  const logo=s.identity?.logo_url?safeUrl(s.identity.logo_url):'';
  const links=Array.isArray(s.navigation)?s.navigation:[{label:'প্রচ্ছদ',url:'index.html'},{label:'শিক্ষকবৃন্দ',url:'faculty.html'},{label:'নোটিশ বোর্ড',url:'index.html#notice'},{label:'যোগাযোগ',url:'index.html#contact'}];
  if(header)header.innerHTML='<header class="cms-header"><div class="cms-shell"><a class="cms-brand" href="index.html">'+(logo?'<img src="'+escape(logo)+'" alt="কলেজ লোগো">':'<span class="cms-seal">দি</span>')+'<span>'+brand+'<small>Demra Ideal College</small></span></a><button class="cms-menu-btn" type="button" aria-expanded="false" aria-controls="cms-navigation">☰ মেনু</button><nav id="cms-navigation" class="cms-navigation">'+links.filter(x=>x&&typeof x.label==='string'&&safeUrl(x.url||'')).map(x=>'<a href="'+escape(safeUrl(x.url))+'">'+escape(x.label)+'</a>').join('')+'</nav></div></header>';
  if(header){const btn=header.querySelector('.cms-menu-btn');btn.addEventListener('click',()=>{let expanded=btn.getAttribute('aria-expanded')==='true';btn.setAttribute('aria-expanded',String(!expanded));header.querySelector('nav').classList.toggle('open',!expanded)})}
  if(footer)footer.innerHTML='<footer class="cms-footer"><div class="cms-shell"><strong>'+brand+'</strong><span>'+escape(s.contact?.address||'ঢাকা, বাংলাদেশ')+'</span><span>© '+new Date().getFullYear()+' | সর্বস্বত্ব সংরক্ষিত</span></div></footer>';
 }
 window.DIC_CMS={configured,publicList,settings,shared,escape,safeUrl};
 document.addEventListener('DOMContentLoaded',shared);
})();