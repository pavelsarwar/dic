<!doctype html><html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>DIC Admin — PHP/MySQL</title><link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet"><style>*{box-sizing:border-box}body{margin:0;background:#f4f8f2;color:#224632;font-family:'Hind Siliguri',sans-serif}button,input,textarea,select{font:inherit}button{cursor:pointer}.wrap{max-width:1150px;margin:auto;padding:25px}.top{display:flex;justify-content:space-between;align-items:center;gap:16px}.top h1{margin:0}.card{background:white;border:1px solid #dce7d8;border-radius:14px;padding:22px;margin:15px 0}.layout{display:grid;grid-template-columns:230px 1fr;gap:20px}.menu{display:grid;gap:8px}.menu button{border:0;border-radius:9px;padding:10px;background:#eef5eb;text-align:left}.menu button.active,.primary{background:#2c7941;color:white}.primary,.secondary{border:0;border-radius:8px;padding:10px 18px}.secondary{background:#e8eee6}.form{display:grid;grid-template-columns:1fr 1fr;gap:14px}label{display:block;font-weight:600}input,textarea{display:block;width:100%;padding:10px;border:1px solid #ccd9c7;border-radius:8px;margin-top:5px}textarea{min-height:90px}.full{grid-column:1/-1}.row{padding:12px 0;border-bottom:1px solid #e7eee3;display:flex;align-items:center;justify-content:space-between;gap:12px}.hidden{display:none!important}#status{background:#fff3d6;padding:12px;border-radius:9px;margin:16px 0}@media(max-width:750px){.layout,.form{grid-template-columns:1fr}.menu{display:flex;overflow:auto}.menu button{white-space:nowrap}.top{flex-wrap:wrap}}
:root{--forest:#145b3c;--leaf:#238454;--line:#e5ece8;--muted:#64776e;--bg:#f5f8f6}
body{background:var(--bg);color:#1b3328}
.wrap{max-width:1440px;padding:26px clamp(16px,3vw,44px)}
.top{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px 24px;margin-bottom:20px;box-shadow:0 3px 18px #12351f08}
.top h1{font-size:23px;color:var(--forest)}
.top a{display:inline-block;text-decoration:none;font-weight:600;color:var(--forest);padding:10px}
.card{border:1px solid var(--line);border-radius:16px;box-shadow:0 4px 20px #153b2408;padding:25px;margin:0 0 18px}
.layout{grid-template-columns:255px minmax(0,1fr);gap:22px;align-items:start}
.layout>aside{position:sticky;top:16px;background:#fff}
.menu{gap:5px}
.menu button{background:transparent;color:#435e4c;font-weight:600;padding:12px 15px;transition:background .15s;border-radius:10px}
.menu button:hover{background:#eef7f0}
.menu button.active{background:#e4f3e8;color:#16643d;border-left:3px solid #258c52}
#heading{margin:0 0 19px;font-size:25px}
#status{background:#e8f4ed;color:#145b3c;border:1px solid #cce8d5;border-radius:10px;margin:0 0 18px;padding:12px 17px;font-weight:600}
.primary{background:#197347;color:#fff;font-weight:700;box-shadow:0 3px 8px #156d3822}
.primary:hover{background:#105c36}
.secondary{background:#eef4ef;color:#255b3b}
input,textarea,select{border:1px solid #d7e2db;background:#fff;padding:11px 13px}
input:focus,textarea:focus{outline:2px solid #82bba0;outline-offset:1px}
label{color:#345a43;font-size:14px}
label input,label textarea{font-weight:400;font-size:15px}
.row{border-bottom:1px solid var(--line);padding:15px 0}
.row>span{font-weight:600}
#items:empty:before{content:'এখনো কোনো তথ্য নেই';color:var(--muted)}
#settingsPanel h2{margin-top:0}
#settingsPanel form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
#settingsPanel form p{grid-column:1/-1}
#settingsPanel form label:nth-child(2){grid-column:1/-1}
#settingsPanel form label:nth-child(3){grid-column:1/-1}
#logoPreview{border:1px solid var(--line);padding:8px;border-radius:10px;background:#fafafa}
.panel-hint{font-size:14px;color:var(--muted);margin:-8px 0 20px}
#login{max-width:470px;margin:60px auto}
@media(max-width:860px){.layout{grid-template-columns:1fr}.layout>aside{position:static}.menu{display:flex;overflow-x:auto}.menu button{flex-shrink:0}.top{padding:16px}.card{padding:17px}#settingsPanel form{grid-template-columns:1fr}}
@media(max-width:550px){.form{grid-template-columns:1fr}.row{align-items:flex-start;flex-direction:column}.top h1{font-size:19px}}
.nav-edit-row{display:grid;grid-template-columns:1.1fr .85fr 1fr auto 34px 34px 34px;gap:8px;align-items:center;border-bottom:1px solid #e5ece8;padding:10px 0}.nav-edit-row input{margin:0;min-width:0}.nav-edit-row .nav-active{white-space:nowrap;font-size:12px}.nav-edit-row .nav-active input{width:auto;display:inline}.nav-edit-row button{padding:7px}.editor-toolbar{display:flex;gap:5px;flex-wrap:wrap;margin:8px 0 0;padding:8px;background:#f3f7f3;border:1px solid #d7e2db;border-radius:8px 8px 0 0}.editor-toolbar button{border:1px solid #d5e2d7;background:#fff;border-radius:6px;padding:5px 12px}.editor-toolbar+textarea{margin:0;border-radius:0 0 8px 8px;min-height:220px}.form small{font-size:12px;color:#728477}@media(max-width:1000px){.nav-edit-row{grid-template-columns:1fr 1fr}.nav-edit-row input:nth-child(3){grid-column:1/-1}}@media(max-width:540px){.nav-edit-row{grid-template-columns:1fr 1fr}.nav-edit-row input:first-child{grid-column:1/-1}}</style></head><body><div class="wrap"><header class="top"><div><h1>ডেমরা আইডিয়াল কলেজ <span style="font-size:13px;color:#6d8977;font-weight:500">/ Admin Workspace</span></h1><div style="font-size:13px;color:#6a8173">Website Content Management</div></div><div><a href="../index.html">← Website</a> <button id="logout" class="secondary hidden">Logout</button></div></header><div id="status" role="status">Checking backend…</div><section id="login" class="card hidden"><h2>Admin Login</h2><form id="loginForm"><label>Email<input id="email" type="email" autocomplete="username" required></label><label>Password<input id="password" type="password" autocomplete="current-password" required></label><p><button class="primary">Login</button></p></form></section><div id="dashboard" class="layout hidden"><aside class="card"><h3 style="margin:0 0 16px;color:#145b3c">ম্যানেজমেন্ট</h3><div id="menu" class="menu"></div><hr style="border:0;border-top:1px solid #e5ece8;margin:16px 0"><button id="homepageTab" type="button" class="secondary" style="width:100%;text-align:left;margin-bottom:7px">◈ সাইট সেটআপ</button><button id="navigationTab" type="button" class="secondary" style="width:100%;text-align:left;margin-bottom:7px">☷ মেনু ম্যানেজার</button><button id="settingsTab" type="button" class="secondary" style="width:100%;text-align:left">⚙ ওয়েবসাইট সেটিংস</button></aside><main><section class="card"><h2 id="heading">Posts & Pages</h2><p class="panel-hint">তথ্য পরিবর্তন করে Save করুন। Published নির্বাচন করলে Website-এ প্রকাশিত হবে।</p><form id="form" class="form"><input id="id" type="hidden"><label>নাম / Title<input id="title" required maxlength="255"></label><label>Subject<input id="subject" maxlength="190"></label><label>Designation<input id="designation" maxlength="190"></label><label>Department<input id="department" maxlength="190"></label><label>Featured Image / Slider Image<input id="image_url" type="url" placeholder="Image URL"><input id="contentImageFile" type="file" accept="image/png,image/jpeg,image/webp"><small>Upload an image (up to 5 MB), or paste HTTPS URL</small><img id="contentImagePreview" alt="" style="display:none;max-width:240px;max-height:145px;object-fit:cover;margin-top:10px;border-radius:10px"></label><label>PDF / Attachment URL<input id="attachment_url" type="url"></label><label>Page Slug / URL<input id="slug" maxlength="190" placeholder="e.g. about"></label><label>Sort Order<input id="sort_order" type="number" value="0"></label><label class="full">Details / Post Content<div class="editor-toolbar"><button type="button" data-format="bold"><b>B</b></button><button type="button" data-format="italic"><i>I</i></button><button type="button" data-format="heading">H2</button><button type="button" data-format="list">• List</button><button type="button" data-format="link">Link</button></div><textarea id="body" rows="12" placeholder="Write your post or page content..."></textarea><small>Simple Markdown formatting supported in the editor.</small></label><label><input type="checkbox" id="published" style="width:auto;display:inline"> Published / Active</label><div class="full"><button class="primary">Save / Update</button> <button type="button" id="new" class="secondary">New Entry</button></div></form></section><section id="settingsPanel" class="card"><h2>ওয়েবসাইট সেটিংস ও লোগো</h2><p>শুধু Super Admin পরিবর্তন করতে পারবেন। Logo PNG/JPG/WebP, সর্বোচ্চ 2 MB।</p><form id="settingsForm"><label>কলেজের নাম<input id="siteName" required></label><label>লোগো আপলোড<input id="logoFile" type="file" accept="image/png,image/jpeg,image/webp"></label><img id="logoPreview" alt="Logo preview" style="max-height:85px;max-width:160px;display:none;margin:12px 0"><label>Logo URL (optional)<input id="logoUrl" type="url"></label><label>ঠিকানা<input id="siteAddress"></label><label>ইমেইল<input id="siteEmail" type="email"></label><label>ফোন<input id="sitePhone"></label><p><button class="primary" type="submit">Save Website Settings</button></p></form></section><section id="homepagePanel" class="card"><h2>সাইট সেটআপ · Homepage Content</h2><p class="panel-hint">প্রধান ব্যানার, কলেজ পরিচিতি, অধ্যক্ষের বাণী ও গুরুত্বপূর্ণ অংশের লেখা পরিবর্তন করুন।</p><form id="homepageForm"><div id="homepageFields" class="form"></div><p><button class="primary" type="submit">Save Homepage</button></p></form></section><section id="navigationPanel" class="card"><h2>Menu Manager</h2><p class="panel-hint">মেনুর নাম, URL, Dropdown Group ও Active/Inactive পরিবর্তন করুন। সারি উপরে-নিচে সরিয়ে ক্রম সাজান।</p><div id="navigationItems"></div><p><button id="addNav" class="secondary" type="button">+ Add Menu Item</button> <button id="saveNav" class="primary" type="button">Save Menu</button></p></section><section class="card"><h2>প্রকাশিত ও সংরক্ষিত তথ্য</h2><div id="items"></div></section></main></div></div><script>
const $=id=>document.getElementById(id),api='../api/index.php?action=',types={teacher:'শিক্ষকবৃন্দ',notice:'নোটিশ',slide:'স্লাইডশো',page:'পেজ',news:'সংবাদ',event:'অনুষ্ঠান',gallery:'গ্যালারি',admission:'ভর্তি',academic:'একাডেমিক'};let token='',type='teacher',records=[],activeView='content';
function show(id,yes){$(id).classList.toggle('hidden',!yes)}function status(s){$('status').textContent=s}
async function call(action,body){const opts={credentials:'same-origin',headers:{'Accept':'application/json'}};if(body!==undefined){opts.method='POST';opts.headers['Content-Type']='application/json';if(token)opts.headers['X-CSRF-Token']=token;opts.body=JSON.stringify(body)}const res=await fetch(api+action,opts);let data;const raw=await res.text();try{data=JSON.parse(raw)}catch{throw Error('Server returned an empty or invalid response (HTTP '+res.status+'). Check server PHP logs or upload limits.')}if(!res.ok)throw Error(data.error||'Request failed');return data}
function clear(){ $('form').reset();$('id').value='';$('sort_order').value=0;previewContentImage()}
function menu(){ $('menu').replaceChildren();Object.entries(types).forEach(([key,label])=>{const b=document.createElement('button');b.type='button';b.textContent=label;b.className=key===type?'active':'';b.onclick=()=>{type=key;activeView='content';setView();clear();menu();load()};$('menu').append(b)})}
const homepageLabels={hero1_title:'ব্যানার ১ — শিরোনাম',hero1_text:'ব্যানার ১ — বিবরণ',hero2_title:'ব্যানার ২ — শিরোনাম',hero2_text:'ব্যানার ২ — বিবরণ',hero3_title:'ব্যানার ৩ — শিরোনাম',hero3_text:'ব্যানার ৩ — বিবরণ',about_title:'কলেজ পরিচিতি — শিরোনাম',about_text:'কলেজ পরিচিতি — বিবরণ',principal_title:'অধ্যক্ষের বাণী — শিরোনাম',principal_text:'অধ্যক্ষের বাণী — বিবরণ',admission_text:'ভর্তি — বিবরণ',contact_text:'যোগাযোগ — বিবরণ'};
function setView(){
 document.querySelectorAll('#dashboard main>.card').forEach((card,i)=>{
  const visible=activeView==='settings'?i===1:activeView==='homepage'?i===2:activeView==='navigation'?i===3:i===0||i===4;
  card.classList.toggle('hidden',!visible);
 });
 for(const [id,view] of [['settingsTab','settings'],['homepageTab','homepage'],['navigationTab','navigation']]){$(id).classList.toggle('primary',activeView===view);$(id).classList.toggle('secondary',activeView!==view)}
}
$('settingsTab').onclick=()=>{activeView='settings';setView();loadSettings()};
$('homepageTab').onclick=()=>{activeView='homepage';setView();loadHomepage()};
$('navigationTab').onclick=()=>{activeView='navigation';setView();loadNavigation()};
const defaultNavigation=[
 ['প্রচ্ছদ','/',''],['কলেজ পরিচিতি','/about','আমাদের কলেজ'],['অধ্যক্ষের বাণী','/principal','আমাদের কলেজ'],['প্রশাসন','/administration','আমাদের কলেজ'],['শিক্ষকবৃন্দ','/faculty','আমাদের কলেজ'],['একাডেমিক কার্যক্রম','/academic','একাডেমিক'],['ক্লাস রুটিন','/routine','একাডেমিক'],['পরীক্ষা','/exam','একাডেমিক'],['ফলাফল','/results','একাডেমিক'],['ভর্তি','/admission',''],['নোটিশ','/notices','নোটিশ ও সংবাদ'],['সংবাদ','/news','নোটিশ ও সংবাদ'],['অনুষ্ঠান','/events','নোটিশ ও সংবাদ'],['গ্যালারি','/gallery',''],['নীতিমালা','/policies','আরও'],['যোগাযোগ','/contact','আরও']
].map(([label,url,group])=>({label,url,group,active:true}));
let navigationItems=[];
function renderNavigation(){
 const root=$('navigationItems');root.replaceChildren();
 navigationItems.forEach((entry,i)=>{
  const row=document.createElement('div');row.className='nav-edit-row';
  for(const [key,placeholder] of [['label','Menu label'],['url','/page-url'],['group','Dropdown group (optional)']]){
   const input=document.createElement('input');input.placeholder=placeholder;input.value=entry[key]||'';input.setAttribute('aria-label',placeholder);
   input.addEventListener('input',()=>entry[key]=input.value);row.append(input);
  }
  const label=document.createElement('label');label.className='nav-active';const checkbox=document.createElement('input');checkbox.type='checkbox';checkbox.checked=entry.active!==false;checkbox.addEventListener('change',()=>entry.active=checkbox.checked);label.append(checkbox,' Active');row.append(label);
  for(const [symbol,delta] of [['↑',-1],['↓',1]]){
   const btn=document.createElement('button');btn.type='button';btn.className='secondary';btn.textContent=symbol;btn.disabled=i+delta<0||i+delta>=navigationItems.length;btn.onclick=()=>{[navigationItems[i],navigationItems[i+delta]]=[navigationItems[i+delta],navigationItems[i]];renderNavigation()};row.append(btn);
  }
  const del=document.createElement('button');del.type='button';del.className='secondary';del.textContent='×';del.setAttribute('aria-label','Remove menu item');del.onclick=()=>{navigationItems.splice(i,1);renderNavigation()};row.append(del);root.append(row);
 });
}
async function loadNavigation(){try{const d=await call('settings');navigationItems=(Array.isArray(d.settings?.navigation)&&d.settings.navigation.length?d.settings.navigation:defaultNavigation).map(x=>({...x}));renderNavigation()}catch(e){status(e.message)}}
$('addNav').onclick=()=>{navigationItems.push({label:'',url:'/',group:'',active:true});renderNavigation()};
$('saveNav').onclick=async()=>{try{await call('save_settings',{key:'navigation',value:navigationItems});status('Menu updated. Refresh the website to see changes.')}catch(e){status(e.message)}};
async function uploadMedia(file){
 if(!file)return null;
 if(file.size>5242880)throw Error('Image must be smaller than 5 MB');
 const fd=new FormData();fd.append('image',file);
 const res=await fetch(api+'upload_image',{method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':token},body:fd});
 const raw=await res.text();let data;try{data=JSON.parse(raw)}catch{throw Error('Image upload failed (HTTP '+res.status+'). Check server upload limits and PHP logs.')}
 if(!res.ok)throw Error(data.error||'Image upload failed');return data.url;
}
$('contentImageFile').addEventListener('change',async()=>{try{
 const file=$('contentImageFile').files[0];if(!file)return;
 status('Uploading image…');const url=await uploadMedia(file);$('image_url').value=url;previewContentImage();status('Image uploaded. Click Save / Update to publish it.');
}catch(e){status(e.message)}});
function previewContentImage(){const u=$('image_url').value.trim(),img=$('contentImagePreview');if(/^https:\/\//i.test(u)){img.src=u;img.style.display='block'}else{img.removeAttribute('src');img.style.display='none'}}
$('image_url').addEventListener('input',previewContentImage);
document.querySelectorAll('[data-format]').forEach(button=>button.addEventListener('click',()=>{
 const editor=$('body'),start=editor.selectionStart,end=editor.selectionEnd,selected=editor.value.slice(start,end)||'text';
 const mode=button.dataset.format;let replacement=selected;
 if(mode==='bold')replacement='**'+selected+'**';
 if(mode==='italic')replacement='*'+selected+'*';
 if(mode==='heading')replacement='## '+selected;
 if(mode==='list')replacement='- '+selected;
 if(mode==='link')replacement='['+selected+'](https://example.com)';
 editor.setRangeText(replacement,start,end,'select');editor.focus();
}));
async function loadHomepage(){try{
 const d=await call('settings'),v=d.settings?.homepage||{},container=$('homepageFields');container.replaceChildren();
 for(const [key,label] of Object.entries(homepageLabels)){
  const el=document.createElement('label');el.textContent=label;
  const field=key.endsWith('_text')?document.createElement('textarea'):document.createElement('input');
  field.name=key;field.maxLength=1500;field.value=v[key]||'';field.placeholder='এখানে নতুন লেখা লিখুন';el.append(field);container.append(el);
 }
}catch(e){status(e.message)}}
$('homepageForm').onsubmit=async e=>{e.preventDefault();try{
 const value={};for(const field of $('homepageFields').querySelectorAll('[name]'))value[field.name]=field.value;
 await call('save_settings',{key:'homepage',value});status('Homepage updated successfully');
}catch(e){status(e.message)}};
async function session(){try{const s=await call('session');if(!s.authenticated){show('login',true);show('dashboard',false);show('logout',false);status('Admin Login required');return}token=s.csrf;show('login',false);show('dashboard',true);show('logout',true);status('Logged in: '+s.user.email);menu();setView();await load();await loadSettings()}catch(e){status('Backend setup: '+e.message)}}
async function load(){try{const d=await call('list&type='+type);records=d.items;$('heading').textContent=types[type]+' Manager';$('items').replaceChildren();if(!records.length)$('items').textContent='No records yet';records.forEach(r=>{const div=document.createElement('div');div.className='row';const label=document.createElement('span');label.textContent=r.title+' — '+(Number(r.published)?'Published':'Draft');const actions=document.createElement('div');const edit=document.createElement('button');edit.className='secondary';edit.textContent='Edit';edit.onclick=()=>{for(const k of ['id','title','subject','designation','department','image_url','attachment_url','slug','sort_order','body'])$(k).value=r[k]??'';$('published').checked=!!Number(r.published);previewContentImage();window.scrollTo({top:0,behavior:'smooth'})};const del=document.createElement('button');del.className='secondary';del.textContent='Delete';del.onclick=async()=>{if(!confirm('Delete this record?'))return;try{await call('delete',{id:r.id});await load()}catch(e){status(e.message)}};actions.append(edit,' ',del);div.append(label,actions);$('items').append(div)})}catch(e){status(e.message)}}
$('loginForm').onsubmit=async e=>{e.preventDefault();try{const d=await call('login',{email:$('email').value,password:$('password').value});token=d.csrf;$('password').value='';await session()}catch(e){status(e.message)}};
$('form').onsubmit=async e=>{e.preventDefault();const data={type};for(const k of ['id','title','subject','designation','department','image_url','attachment_url','slug','sort_order','body'])data[k]=$(k).value;data.published=$('published').checked;try{await call('save',data);status('Saved');clear();await load()}catch(e){status(e.message)}};
async function loadSettings(){try{const d=await call('settings');const s=d.settings||{};$('siteName').value=s.identity?.name||'ডেমরা আইডিয়াল কলেজ';$('logoUrl').value=s.identity?.logo_url||'';$('siteAddress').value=s.contact?.address||'';$('siteEmail').value=s.contact?.email||'';$('sitePhone').value=s.contact?.phone||'';previewLogo()}catch(e){status(e.message)}}
function previewLogo(){const u=$('logoUrl').value.trim();const img=$('logoPreview');if(/^https:\/\//i.test(u)){img.src=u;img.style.display='block'}else{img.removeAttribute('src');img.style.display='none'}}
$('logoUrl').addEventListener('input',previewLogo);
$('settingsForm').onsubmit=async e=>{e.preventDefault();try{const file=$('logoFile').files[0];if(file){if(file.size>2097152)throw Error('Logo must be smaller than 2 MB');if(!['image/png','image/jpeg','image/webp'].includes(file.type))throw Error('Choose PNG, JPG or WebP');const fd=new FormData();fd.append('logo',file);const r=await fetch(api+'upload_logo',{method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':token},body:fd});const raw=await r.text();let d;try{d=JSON.parse(raw)}catch{throw Error('Logo upload returned no JSON (HTTP '+r.status+'). Check PHP upload limits, permissions or server error logs.')}if(!r.ok)throw Error(d.error||'Upload failed');$('logoUrl').value=d.url;}await call('save_settings',{key:'identity',value:{name:$('siteName').value,logo_url:$('logoUrl').value}});await call('save_settings',{key:'contact',value:{address:$('siteAddress').value,email:$('siteEmail').value,phone:$('sitePhone').value}});$('logoFile').value='';previewLogo();status('Website settings saved. Refresh public website to view changes.')}catch(e){status(e.message)}};
$('new').onclick=clear;$('logout').onclick=async()=>{try{await call('logout',{});token='';await session()}catch(e){status(e.message)}};session();
</script></body></html>