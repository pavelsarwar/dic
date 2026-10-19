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
  // Homepage copy is managed in the CMS, never injected as HTML.
  if(document.querySelector('[data-home-key]')){
   const homepage=s.homepage||{};
   document.querySelectorAll('[data-home-key]').forEach(el=>{const value=homepage[el.dataset.homeKey];if(typeof value==='string'&&value.trim())el.textContent=value});
   // Replace demonstration cards with approved published records when available.
   Promise.allSettled([publicList('notice'),publicList('news'),publicList('slide')]).then(results=>{
    const notices=results[0].status==='fulfilled'?results[0].value:[];
    const news=results[1].status==='fulfilled'?results[1].value:[];
    const slides=results[2].status==='fulfilled'?results[2].value:[];
    const panel=document.querySelector('#notice .panel');
    if(panel&&notices.length){
     panel.replaceChildren();
     notices.slice(0,5).forEach(n=>{const row=document.createElement('div');row.className='notice';const title=document.createElement('strong');title.textContent=n.title;row.append(title);panel.append(row)});
     const more=document.createElement('a');more.href='/notices';more.textContent='সব নোটিশ দেখুন →';more.style.display='block';more.style.marginTop='16px';panel.append(more);
    }
    const cards=document.querySelector('#news .newsCards');
    if(cards&&news.length){cards.replaceChildren();news.slice(0,3).forEach(n=>{const article=document.createElement('article');article.className='newsCard';const inner=document.createElement('div');inner.className='inner';const title=document.createElement('h3');title.textContent=n.title;const body=document.createElement('p');body.textContent=n.body||'';inner.append(title,body);article.append(inner);cards.append(article)})}
    const banners=document.querySelectorAll('.heroSlider .slide');
    slides.slice(0,banners.length).forEach((slide,i)=>{const h=banners[i].querySelector('h1'),p=banners[i].querySelector('p');if(h&&slide.title)h.textContent=slide.title;if(p&&slide.body)p.textContent=slide.body;if(slide.image_url&&safeUrl(slide.image_url))banners[i].style.backgroundImage='url("'+safeUrl(slide.image_url).replace(/["\\]/g,'')+'")'});
   });
  }
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
  const customNav=Array.isArray(s.navigation)&&s.navigation.length?s.navigation:null;
  if(customNav){
   const arranged=[],parents=new Map();
   customNav.filter(x=>x&&x.active!==false).forEach(x=>{
    const group=String(x.group||'').trim();
    if(group){
     if(!parents.has(group)){const entry={label:group,children:[]};parents.set(group,entry);arranged.push(entry)}
     parents.get(group).children.push([x.label,x.url]);
    }else arranged.push({label:x.label,url:x.url});
   });
   groups.splice(0,groups.length,...arranged);
  }
  const clean=path=>{const s=String(path||'').trim();if(s==='/'||s==='index.html'||s==='/index.html')return '/';const m=s.match(/^\/?([a-z0-9-]+)(?:\.html)?(#[a-z0-9_-]+)?$/i);return m?'/'+m[1]+(m[2]||''):''};
  // Existing CMS navigation labels can override the default label for matching pages.
  const overrides=new Map((Array.isArray(s.navigation)?s.navigation:[]).map(x=>[clean(x?.url),String(x?.label||'').trim()]).filter(x=>x[0]&&x[1]));
  const current=location.pathname.replace(/\.html$/i,'').replace(/\/$/,'')||'/';
  const item=(label,url)=>'<a href="'+escape(url)+'"'+(current===url?' aria-current="page"':'')+'>'+escape(overrides.get(url)||label)+'</a>';
  const nav=groups.map(g=>g.children?'<div class="cms-dropdown"><button class="cms-drop-trigger" type="button" aria-expanded="false">'+escape(g.label)+' <svg class="cms-chevron" aria-hidden="true" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></button><div class="cms-submenu">'+g.children.map(([l,u])=>item(l,u)).join('')+'</div></div>':item(g.label,g.url)).join('');
  const layout=s.layout||{};
  if(header)header.innerHTML=(layout.topbar_active===false?'':'<div class="cms-topbar"><div class="cms-shell"><span>'+escape(layout.top_motto??'শিক্ষা • শৃঙ্খলা • মানবিক মূল্যবোধ')+'</span><span>'+escape(layout.top_eiin??'ইআইআইএন: ১৩৭৬০৪')+(layout.top_language===null||layout.top_language===undefined?'　 |　 বাংলা / English':layout.top_language?'　 |　 '+escape(layout.top_language):'')+'</span></div></div>')+(layout.header_notice_active&&layout.header_notice?'<div class="cms-announcement">'+escape(layout.header_notice)+'</div>':'')+'<header class="cms-header"><div class="cms-shell"><a class="cms-brand" href="/" aria-label="ডেমরা আইডিয়াল কলেজ — প্রচ্ছদ">'+(logo?'<img src="'+escape(logo)+'" alt="কলেজ লোগো">':'<span class="cms-seal">দি</span>')+'</a><button class="cms-menu-btn" type="button" aria-expanded="false" aria-controls="cms-navigation" aria-label="মেনু খুলুন">☰ মেনু</button><nav id="cms-navigation" class="cms-navigation" aria-label="প্রধান মেনু">'+nav+'</nav></div></header>';
  if(header){
   const btn=header.querySelector('.cms-menu-btn'),navigation=header.querySelector('nav');
   const closeDrops=except=>header.querySelectorAll('.cms-dropdown').forEach(d=>{if(d!==except){d.classList.remove('expanded');d.querySelector('button').setAttribute('aria-expanded','false')}});
   btn.addEventListener('click',()=>{const open=btn.getAttribute('aria-expanded')!=='true';btn.setAttribute('aria-expanded',String(open));navigation.classList.toggle('open',open);if(!open)closeDrops()});
   header.querySelectorAll('.cms-drop-trigger').forEach(trigger=>trigger.addEventListener('click',()=>{const parent=trigger.parentElement;const open=!parent.classList.contains('expanded');closeDrops(parent);parent.classList.toggle('expanded',open);trigger.setAttribute('aria-expanded',String(open))}));
   header.addEventListener('keydown',e=>{if(e.key==='Escape'){closeDrops();navigation.classList.remove('open');btn.setAttribute('aria-expanded','false');btn.focus()}});
   document.addEventListener('click',e=>{if(!header.contains(e.target))closeDrops()});
  }
  if(footer){const contact=s.contact||{};const email=contact.email||'';const phone=contact.phone||'';footer.innerHTML='<footer class="cms-footer"><div class="cms-shell cms-footer-grid"><div>'+(layout.footer_logo&&safeUrl(layout.footer_logo)?'<img class="cms-footer-logo" src="'+escape(safeUrl(layout.footer_logo))+'" alt="কলেজ লোগো">':'')+'<strong>'+brand+'</strong><p>'+escape(layout.footer_about||'মানসম্মত শিক্ষা ও মানবিক মূল্যবোধে প্রতিশ্রুতিবদ্ধ।')+'</p></div><div><strong>যোগাযোগ</strong><p>'+escape(contact.address||'ঢাকা, বাংলাদেশ')+'</p>'+(email?'<p><a href="mailto:'+encodeURIComponent(email)+'">'+escape(email)+'</a></p>':'')+(phone?'<p>'+escape(phone)+'</p>':'')+'</div><div><strong>'+escape(layout.footer_links_title||'দ্রুত লিংক')+'</strong><p><a href="/about">কলেজ পরিচিতি</a></p><p><a href="/notices">নোটিশ বোর্ড</a></p><p><a href="/contact">যোগাযোগ</a></p></div></div><div class="cms-shell cms-footer-bottom">'+escape((layout.footer_copyright||'© {year} ডেমরা আইডিয়াল কলেজ | সর্বস্বত্ব সংরক্ষিত').replace('{year}',String(new Date().getFullYear())))+'</div></footer>'}
 }
 window.DIC_CMS={configured,publicList,settings,shared,escape,safeUrl};
 document.addEventListener('DOMContentLoaded',shared);
})();