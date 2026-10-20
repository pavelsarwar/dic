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
   // One source of truth: the published /principal page drives the homepage preview.
   const principalPreview=document.getElementById('principalPreview');
   if(principalPreview){
    publicList('page').then(items=>{
     const record=items.find(x=>x.slug==='principal');
     if(!record)return;
     const plain=String(record.body||'').replace(/<[^>]*>/g,'').replace(/\s+/g,' ').trim();
     const excerpt=Array.from(plain).slice(0,290).join('');
     const photo=record.image_url&&safeUrl(record.image_url)?'<img class="principal-preview-photo" src="'+escape(safeUrl(record.image_url))+'" alt="অধ্যক্ষের ছবি" loading="lazy">':'';
     principalPreview.innerHTML=photo+'<div class="principal-preview-body"><p>'+escape(excerpt)+(Array.from(plain).length>290?'…':'')+'</p><a class="principal-read-more" href="/principal">বিস্তারিত পড়ুন →</a></div>';
    }).catch(e=>console.warn('Principal preview unavailable',e));
   }
   const galleryGrid=document.getElementById('homeGalleryGrid');
   if(galleryGrid){
    if(window.DICLightbox)window.DICLightbox.init(galleryGrid);
    publicList('gallery').then(items=>{
     const photos=items.filter(x=>x.image_url&&safeUrl(x.image_url)).slice(0,6);
     galleryGrid.innerHTML=photos.length?photos.map(x=>'<button type="button" class="dic-home-gallery-item" data-lightbox-src="'+escape(safeUrl(x.image_url))+'" aria-label="ছবি বড় করে দেখুন"><img src="'+escape(safeUrl(x.image_url))+'" alt="কলেজের ছবি" loading="lazy"></button>').join(''):'<p class="dic-gallery-loading">এখনো কোনো ছবি প্রকাশিত হয়নি। <a href="/gallery">গ্যালারি দেখুন →</a></p>';
    }).catch(()=>{galleryGrid.innerHTML='<p class="dic-gallery-loading">গ্যালারির ছবি এই মুহূর্তে লোড করা যাচ্ছে না।</p>';});
   }
   // Replace demonstration cards with approved published records when available.
   Promise.allSettled([publicList('notice'),publicList('news'),publicList('slide')]).then(results=>{
    const notices=results[0].status==='fulfilled'?results[0].value:[];
    const news=results[1].status==='fulfilled'?results[1].value:[];
    const slides=results[2].status==='fulfilled'?results[2].value:[];
    const urgent=document.querySelector('[data-urgent-notice]');
    if(urgent){
      const label=urgent.querySelector('[data-urgent-title]'),link=urgent.querySelector('[data-urgent-link]');
      if(label)label.textContent=s.layout?.urgent_title||'জরুরি ঘোষণা';
      urgent.hidden=s.layout?.urgent_active===false;
      if(link){if(notices.length){link.textContent=notices[0].title;link.href='/notices?notice='+encodeURIComponent(notices[0].id)}else{link.textContent='সর্বশেষ নোটিশ দেখতে ক্লিক করুন';link.href='/notices'}}
    }
    const panel=document.querySelector('#notice .panel');
    if(panel&&notices.length){
     panel.replaceChildren();
     notices.slice(0,5).forEach(n=>{const row=document.createElement('div');row.className='notice';const title=document.createElement('a');title.href='/notices?notice='+encodeURIComponent(n.id);title.textContent=n.title;title.className='cms-notice-link';row.append(title);panel.append(row)});
     const more=document.createElement('a');more.href='/notices';more.textContent='সব নোটিশ দেখুন →';more.style.display='block';more.style.marginTop='16px';panel.append(more);
    }
    const cards=document.querySelector('#news .newsCards');
    if(cards){
     cards.replaceChildren();
     const photos=news.filter(n=>n.image_url&&safeUrl(n.image_url)).slice(0,5);
     if(!photos.length){cards.innerHTML='<p class="dic-news-empty">এখনো কোনো সংবাদ ছবি প্রকাশিত হয়নি।</p>';}
     else{
      cards.classList.add('dic-news-slideshow');
      const viewport=document.createElement('div');viewport.className='dic-news-slide-viewport';
      const dots=document.createElement('div');dots.className='dic-news-slide-dots';
      let current=0;
      photos.forEach((n,i)=>{
       const slide=document.createElement('a');slide.className='dic-news-slide';slide.href='/news';slide.hidden=i!==0;
       const img=document.createElement('img');img.src=safeUrl(n.image_url);img.alt=n.title||'কলেজ সংবাদ';img.loading=i===0?'eager':'lazy';
       const caption=document.createElement('span');caption.className='dic-news-slide-caption';caption.textContent=n.title||'কলেজ সংবাদ';
       slide.append(img,caption);viewport.append(slide);
       const dot=document.createElement('button');dot.type='button';dot.className='dic-news-slide-dot';dot.setAttribute('aria-label','ছবি '+(i+1));dot.setAttribute('aria-current',i===0?'true':'false');dots.append(dot);
       dot.onclick=()=>show(i);
      });
      const slidesEls=Array.from(viewport.children),dotEls=Array.from(dots.children);
      function show(i){current=(i+photos.length)%photos.length;slidesEls.forEach((el,j)=>el.hidden=j!==current);dotEls.forEach((el,j)=>el.setAttribute('aria-current',j===current?'true':'false'))}
      const controls=document.createElement('div');controls.className='dic-news-slide-controls';
      const prev=document.createElement('button');prev.type='button';prev.textContent='‹';prev.setAttribute('aria-label','আগের ছবি');prev.onclick=()=>show(current-1);
      const next=document.createElement('button');next.type='button';next.textContent='›';next.setAttribute('aria-label','পরের ছবি');next.onclick=()=>show(current+1);
      if(photos.length>1){controls.append(prev,dots,next);cards.addEventListener('mouseenter',()=>clearInterval(timer));cards.addEventListener('mouseleave',startTimer)}
      cards.append(viewport,controls);
      let timer;function startTimer(){if(photos.length>1){clearInterval(timer);timer=setInterval(()=>{if(!document.hidden)show(current+1)},4500)}}
      if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches)startTimer();
     }
    }
    const banners=document.querySelectorAll('.heroSlider .slide');
    slides.slice(0,banners.length).forEach((slide,i)=>{const h=banners[i].querySelector('h1'),p=banners[i].querySelector('p');if(h&&slide.title)h.textContent=slide.title;if(p&&slide.body)p.textContent=slide.body;if(slide.image_url&&safeUrl(slide.image_url))banners[i].style.backgroundImage='url("'+safeUrl(slide.image_url).replace(/["\\]/g,'')+'")'});
   });
  }
  const fbFeed=document.getElementById('facebookFeed');
  if(fbFeed){
   const embed=String(s.layout?.facebook_embed_code||'');
   let extracted='';
   const hrefParam=embed.match(/[?&]href=([^&"'<>\s]+)/i);
   if(hrefParam){try{extracted=decodeURIComponent(hrefParam[1].replace(/&amp;/g,'&'))}catch(e){}}
   if(!extracted){const match=embed.match(/https:\/\/(?:www\.)?facebook\.com\/[^"'<>\s&]+/i);if(match)extracted=match[0];}
   const fbUrl=String(extracted||s.layout?.social_facebook||'https://www.facebook.com/demraidealcollege').trim();
   const fbLink=document.getElementById('facebookPageLink');
   if(/^https:\/\/(?:[a-z0-9-]+\.)*(?:facebook\.com|fb\.com)\//i.test(fbUrl)){
    if(fbLink){fbLink.href=fbUrl;fbLink.hidden=false}
    const card=document.createElement('div');card.className='dic-facebook-page-card';
    const emblem=document.createElement('div');emblem.className='dic-facebook-page-emblem';emblem.textContent='f';emblem.setAttribute('aria-hidden','true');
    const content=document.createElement('div');content.className='dic-facebook-page-info';
    const name=document.createElement('h4');name.textContent='ডেমরা আইডিয়াল কলেজ';
    const desc=document.createElement('p');desc.textContent='সর্বশেষ আপডেট ও কলেজের কার্যক্রম';
    const action=document.createElement('a');action.className='dic-facebook-page-button';action.href=fbUrl;action.target='_blank';action.rel='noopener noreferrer';action.textContent='ফেইসবুক পেজ দেখুন ↗';
    content.append(name,desc,action);card.append(emblem,content);fbFeed.replaceChildren(card);
   }
  }
  const youtubeRoot=document.getElementById('youtubeVideo');
  if(youtubeRoot){
   const channel=String(s.layout?.social_youtube||'').trim();
   const channelLink=document.getElementById('youtubeChannelLink');
   if(/^https:\/\/(?:www\.)?(?:youtube\.com|youtu\.be)\//i.test(channel)&&channelLink){channelLink.href=channel;channelLink.hidden=false}
   const video=String(s.layout?.homepage_youtube_video||'').trim();
   let videoId='';
   try{const url=new URL(video);const host=url.hostname.toLowerCase();if(host==='youtu.be')videoId=url.pathname.split('/')[1]||'';else if(['youtube.com','www.youtube.com','m.youtube.com'].includes(host)){videoId=url.searchParams.get('v')||'';if(!videoId&&url.pathname.startsWith('/shorts/'))videoId=url.pathname.split('/')[2]||'';if(!videoId&&url.pathname.startsWith('/embed/'))videoId=url.pathname.split('/')[2]||'';}}catch(e){}
   if(/^[A-Za-z0-9_-]{11}$/.test(videoId)){
    const frame=document.createElement('iframe');frame.title='ডেমরা আইডিয়াল কলেজ — ইউটিউব ভিডিও';frame.src='https://www.youtube-nocookie.com/embed/'+videoId;frame.loading='lazy';frame.allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';frame.allowFullscreen=true;youtubeRoot.replaceChildren(frame);
   }else{
    youtubeRoot.replaceChildren();
    const icon=document.createElement('span');icon.className='dic-youtube-play';icon.textContent='▶';icon.setAttribute('aria-hidden','true');
    const hint=document.createElement('p');hint.textContent='কলেজের ভিডিও দেখতে আমাদের ইউটিউব চ্যানেল ভিজিট করুন।';
    youtubeRoot.append(icon,hint);
    if(/^https:\/\/(?:www\.)?youtube\.com\//i.test(channel)){const link=document.createElement('a');link.href=channel;link.target='_blank';link.rel='noopener noreferrer';link.textContent='ইউটিউব চ্যানেল দেখুন ↗';youtubeRoot.append(link)}
   }
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
   {label:'গ্যালারি',url:'/gallery'}
  ];
  const customNav=Array.isArray(s.navigation)?s.navigation:null;
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
  if(header){
   const contact=s.contact||{};
   const topContact=(contact.phone?'<a href="tel:'+escape(String(contact.phone).replace(/[^+0-9]/g,''))+'"><span aria-hidden="true">☎</span> '+escape(contact.phone)+'</a>':'')+(contact.email?'<a href="mailto:'+escape(contact.email)+'"><span aria-hidden="true">✉</span> '+escape(contact.email)+'</a>':'');
   const topMeta=(layout.top_eiin?'<span>EIIN: '+escape(layout.top_eiin.replace(/^.*?:\\s*/,''))+'</span>':'<span>EIIN: ১৩৭৬০৪</span>')+(layout.top_college_code?'<span>♙ College Code: '+escape(layout.top_college_code)+'</span>':'');
   const admissionUrl=layout.admission_button_url&&safeUrl(layout.admission_button_url);
   const admissionButton=layout.admission_button_active!==false&&admissionUrl?'<a class="cms-admission-btn" href="'+escape(admissionUrl)+'" target="_blank" rel="noopener noreferrer">'+escape(layout.admission_button_label||'Online Admission')+' ↗</a>':'';
   const socialIcons={
    social_facebook:'<path d="M15 21v-8h3l.5-4H15V7c0-1.2.4-2 2-2h2V1.4A23 23 0 0 0 16 1c-3 0-5 1.8-5 5v3H8v4h3v8z"/>',
    social_instagram:'<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1"/>',
    social_youtube:'<rect x="2" y="5" width="20" height="14" rx="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="m10 9 6 3-6 3z"/>'
   };
   const socialLinks=[['social_facebook','Facebook'],['social_instagram','Instagram'],['social_youtube','YouTube']].map(([key,label])=>{
    const url=String(layout[key]||(key==='social_facebook'?'https://www.facebook.com/demraidealcollege':'')).trim();if(!/^https:\/\//i.test(url))return '';
    return '<a href="'+escape(url)+'" target="_blank" rel="noopener noreferrer" aria-label="'+label+'" title="'+label+'"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'+socialIcons[key]+'</svg></a>';
   }).join('');
   const socialBlock=socialLinks?'<div class="cms-social-links">'+socialLinks+'</div>':'';
   header.innerHTML=(layout.topbar_active===false?'':'<div class="cms-topbar"><div class="cms-shell"><div class="cms-top-contact">'+topContact+'</div><div class="cms-top-meta">'+topMeta+socialBlock+'</div></div></div>')+'<header class="cms-header"><div class="cms-shell"><a class="cms-brand" href="/" aria-label="ডেমরা আইডিয়াল কলেজ — প্রচ্ছদ">'+(logo?'<img src="'+escape(logo)+'" alt="কলেজ লোগো">':'<span class="cms-seal">দি</span>')+'</a><button class="cms-menu-btn" type="button" aria-expanded="false" aria-controls="cms-navigation" aria-label="মেনু খুলুন">☰ মেনু</button><nav id="cms-navigation" class="cms-navigation" aria-label="প্রধান মেনু">'+nav+'</nav>'+admissionButton+'</div></header>';
  }
  if(header){
   const btn=header.querySelector('.cms-menu-btn'),navigation=header.querySelector('nav');
   const closeDrops=except=>header.querySelectorAll('.cms-dropdown').forEach(d=>{if(d!==except){d.classList.remove('expanded');d.querySelector('button').setAttribute('aria-expanded','false')}});
   btn.addEventListener('click',()=>{const open=btn.getAttribute('aria-expanded')!=='true';btn.setAttribute('aria-expanded',String(open));navigation.classList.toggle('open',open);if(!open)closeDrops()});
   header.querySelectorAll('.cms-drop-trigger').forEach(trigger=>trigger.addEventListener('click',()=>{const parent=trigger.parentElement;const open=!parent.classList.contains('expanded');closeDrops(parent);parent.classList.toggle('expanded',open);trigger.setAttribute('aria-expanded',String(open))}));
   header.addEventListener('keydown',e=>{if(e.key==='Escape'){closeDrops();navigation.classList.remove('open');btn.setAttribute('aria-expanded','false');btn.focus()}});
   document.addEventListener('click',e=>{if(!header.contains(e.target))closeDrops()});
  }
  if(footer){const contact=s.contact||{};const email=contact.email||'';const phone=contact.phone||'';footer.innerHTML='<footer class="cms-footer"><div class="cms-shell cms-footer-grid"><div>'+(layout.footer_logo&&safeUrl(layout.footer_logo)?'<img class="cms-footer-logo" src="'+escape(safeUrl(layout.footer_logo))+'" alt="কলেজ লোগো">':'')+(String(layout.footer_about||'').trim()?'<p>'+escape(layout.footer_about)+'</p>':'')+'</div><div><strong>যোগাযোগ</strong><p class="cms-contact-line"><span class="cms-contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span><span>'+escape(contact.address||'ঢাকা, বাংলাদেশ')+'</span></p>'+(email?'<p class="cms-contact-line"><span class="cms-contact-icon" aria-hidden="true">✉</span><a href="mailto:'+encodeURIComponent(email)+'">'+escape(email)+'</a></p>':'')+(phone?'<p class="cms-contact-line"><span class="cms-contact-icon" aria-hidden="true">☎</span><a href="tel:'+escape(String(phone).replace(/[^+0-9]/g,''))+'">'+escape(phone)+'</a></p>':'')+'</div><div><strong>'+escape(layout.footer_links_title||'দ্রুত লিংক')+'</strong><p><a href="/about">কলেজ পরিচিতি</a></p><p><a href="/notices">নোটিশ বোর্ড</a></p><p><a href="/contact">যোগাযোগ</a></p></div></div><div class="cms-shell cms-footer-bottom">'+escape((layout.footer_copyright||'© {year} ডেমরা আইডিয়াল কলেজ | সর্বস্বত্ব সংরক্ষিত').replace('{year}',String(new Date().getFullYear())))+'</div></footer>'}
 }
 // Shared, accessible photo viewer for homepage and gallery page.
 let lightbox=null,previousFocus=null;
 function closeLightbox(){
  if(!lightbox)return;
  lightbox.hidden=true;document.body.style.overflow='';previousFocus?.focus();previousFocus=null;
 }
 function openLightbox(src){
  if(!lightbox){
   lightbox=document.createElement('div');lightbox.className='dic-lightbox';lightbox.hidden=true;
   lightbox.innerHTML='<div class="dic-lightbox-backdrop"></div><div class="dic-lightbox-content" role="dialog" aria-modal="true" aria-label="বড় ছবি"><button type="button" class="dic-lightbox-close" aria-label="বন্ধ করুন">×</button><img alt="কলেজের ছবি"></div>';
   document.body.append(lightbox);
   lightbox.querySelector('.dic-lightbox-close').onclick=closeLightbox;
   lightbox.querySelector('.dic-lightbox-backdrop').onclick=closeLightbox;
  }
  previousFocus=document.activeElement;lightbox.querySelector('img').src=src;lightbox.hidden=false;document.body.style.overflow='hidden';lightbox.querySelector('.dic-lightbox-close').focus();
 }
 document.addEventListener('keydown',e=>{if(!lightbox||lightbox.hidden)return;if(e.key==='Escape')closeLightbox();if(e.key==='Tab'){e.preventDefault();lightbox.querySelector('.dic-lightbox-close').focus()}});
 window.DICLightbox={init(container){container.addEventListener('click',e=>{const trigger=e.target.closest('[data-lightbox-src]');if(!trigger||!container.contains(trigger))return;const src=trigger.getAttribute('data-lightbox-src');if(src&&safeUrl(src))openLightbox(src)})}};
 window.DIC_CMS={configured,publicList,settings,shared,escape,safeUrl};
 document.addEventListener('DOMContentLoaded',shared);
})();