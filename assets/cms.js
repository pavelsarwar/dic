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
  const links=Array.isArray(s.navigation)?s.navigation:[{label:'প্রচ্ছদ',url:'index.html'},{label:'কলেজ পরিচিতি',url:'about.html'},{label:'অধ্যক্ষের বাণী',url:'principal.html'},{label:'প্রশাসন',url:'administration.html'},{label:'শিক্ষকবৃন্দ',url:'faculty.html'},{label:'ভর্তি',url:'admission.html'},{label:'একাডেমিক',url:'academic.html'},{label:'রুটিন',url:'routine.html'},{label:'পরীক্ষা',url:'exam.html'},{label:'ফলাফল',url:'results.html'},{label:'নোটিশ',url:'notices.html'},{label:'সংবাদ',url:'news.html'},{label:'অনুষ্ঠান',url:'events.html'},{label:'গ্যালারি',url:'gallery.html'},{label:'নীতিমালা',url:'policies.html'},{label:'যোগাযোগ',url:'contact.html'}];
  if(header)header.innerHTML='<header class="cms-header"><div class="cms-shell"><a class="cms-brand" href="index.html">'+(logo?'<img src="'+escape(logo)+'" alt="কলেজ লোগো">':'<span class="cms-seal">দি</span>')+'<span>'+brand+'<small>Demra Ideal College</small></span></a><button class="cms-menu-btn" type="button" aria-expanded="false" aria-controls="cms-navigation">☰ মেনু</button><nav id="cms-navigation" class="cms-navigation">'+links.filter(x=>x&&typeof x.label==='string'&&safeUrl(x.url||'')).map(x=>'<a href="'+escape(safeUrl(x.url))+'">'+escape(x.label)+'</a>').join('')+'</nav></div></header>';
  if(header){const btn=header.querySelector('.cms-menu-btn');btn.addEventListener('click',()=>{let expanded=btn.getAttribute('aria-expanded')==='true';btn.setAttribute('aria-expanded',String(!expanded));header.querySelector('nav').classList.toggle('open',!expanded)})}
  if(footer)footer.innerHTML='<footer class="cms-footer"><div class="cms-shell"><strong>'+brand+'</strong><span>'+escape(s.contact?.address||'ঢাকা, বাংলাদেশ')+'</span><span>© '+new Date().getFullYear()+' | সর্বস্বত্ব সংরক্ষিত</span></div></footer>';
 }
 window.DIC_CMS={configured,publicList,settings,shared,escape,safeUrl};
 document.addEventListener('DOMContentLoaded',shared);
})();