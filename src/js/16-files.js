/* ============================================================
   Attachments: upload (in pieces, with progress), preview, download,
   rename, replace, delete; files of Personal items are encrypted in
   the browser. Items keep a list: files:[{id,name,size,type,added,by,key?,iv?}]
   ============================================================ */
let FILES_INFO=null;
async function filesInfo(force){ if(!FILES_INFO || force){ try{ FILES_INFO=await api('files_info'); }catch(e){ if(!FILES_INFO) FILES_INFO={max:50*1048576, chunk:1048576, graceDays:7}; } } return FILES_INFO; }
function fmtSize(n){ n=+n||0; return n<1024?n+' B':n<1048576?(n/1024).toFixed(n<10240?1:0)+' KB':n<1073741824?(n/1048576).toFixed(n<10485760?1:0)+' MB':(n/1073741824).toFixed(1)+' GB'; }
const fileExt = f => ((/\.([a-z0-9]{1,8})$/i.exec(f.name||'')||[])[1]||'').toLowerCase();
const IMG_EXT={png:'image/png',jpg:'image/jpeg',jpeg:'image/jpeg',gif:'image/gif',webp:'image/webp',bmp:'image/bmp',svg:'image/svg+xml',avif:'image/avif'};
const TEXT_EXT=new Set(['txt','csv','tsv','md','json','log','ics','vcf','xml','yaml','yml','ini','eml']);
const MEDIA_EXT={mp3:'audio/mpeg',m4a:'audio/mp4',wav:'audio/wav',ogg:'audio/ogg',mp4:'video/mp4',webm:'video/webm',mov:'video/quicktime'};
function fileKind(f){ const x=fileExt(f);
  if(IMG_EXT[x]) return 'image'; if(x==='pdf') return 'pdf'; if(x==='msg') return 'msg'; if(TEXT_EXT.has(x)) return 'text';
  if(MEDIA_EXT[x]) return MEDIA_EXT[x].startsWith('audio')?'audio':'video'; return 'other'; }
function fileIcon(f){ const x=fileExt(f), k=fileKind(f);
  return k==='image'?'🖼':k==='pdf'?'📕':k==='msg'?'📧':k==='audio'?'🎵':k==='video'?'🎬':/^(docx?|odt|rtf)$/.test(x)?'📝':/^(xlsx?|ods|csv)$/.test(x)?'📊':/^(pptx?|odp)$/.test(x)?'📽':/^(zip|7z|rar|gz|tar)$/.test(x)?'🗜':k==='text'?'📄':'📎'; }
/* only these types are ever given to the browser to display; everything else is a plain download */
function safeType(f){ const x=fileExt(f), k=fileKind(f);
  return k==='image'?IMG_EXT[x]:k==='pdf'?'application/pdf':k==='audio'||k==='video'?MEDIA_EXT[x]:k==='text'?'text/plain;charset=utf-8':'application/octet-stream'; }
const fileRefs = it => arr(it && it.files);

/* ---------- transfer ---------- */
async function rawPost(action, query, bytes){
  let r; try{ r=await fetch(API+'?a='+action+query, {method:'POST', credentials:'same-origin', cache:'no-store', headers:{'Content-Type':'application/octet-stream','X-WTC-Token':token,'Accept':'application/json'}, body:bytes}); }
  catch(e){ const err=new Error('Cannot reach the server. Check your internet connection.'); err.status=0; throw err; }
  let j=null; try{ j=await r.json(); }catch(e){}
  if(!r.ok){ const err=new Error((j&&j.error)||('The server answered with an error ('+r.status+').')); err.status=r.status; err.body=j; throw err; }
  return j;
}
/* upload one file (File, Blob or bytes) -> attachment record */
async function uploadAttachment(src, {name, encrypt, onProgress}={}){
  const info=await filesInfo();
  name = name || src.name || 'file';
  let bytes = src instanceof Uint8Array ? src : new Uint8Array(await src.arrayBuffer());
  const rec={id:'', name, size:bytes.length, type:(src.type||'').slice(0,100), added:nowISO(), by:session?session.user:''};
  if(bytes.length>info.max) throw new Error(`“${name}” is larger than ${fmtSize(info.max)}.`);
  if(encrypt){
    const k=await crypto.subtle.generateKey({name:'AES-GCM', length:256}, true, ['encrypt','decrypt']);
    const iv=crypto.getRandomValues(new Uint8Array(12));
    bytes=new Uint8Array(await crypto.subtle.encrypt({name:'AES-GCM', iv}, k, bytes));
    rec.key=b64(await crypto.subtle.exportKey('raw',k)); rec.iv=b64(iv);
  }
  const st=await api('upload_start',{name, size:bytes.length});
  const chunk=st.chunk||info.chunk||1048576;
  for(let off=0; off<bytes.length || (off===0 && !bytes.length); off+=chunk){
    let tries=0;
    for(;;){ try{ await rawPost('upload_chunk', `&id=${st.id}&off=${off}`, bytes.subarray(off, off+chunk)); break; }
      catch(e){ if(e.status===409 && e.body && e.body.have===off+Math.min(chunk,bytes.length-off)) break;   // that piece had arrived
        if(++tries>=3 || (e.status && e.status!==409 && e.status<500)) throw e; await new Promise(r=>setTimeout(r,800*tries)); } }
    if(onProgress) onProgress(Math.min(1,(off+chunk)/Math.max(1,bytes.length)));
    if(!bytes.length) break;
  }
  await api('upload_end',{id:st.id});
  rec.id=st.id; return rec;
}
const _fileCache=new Map(); let _fileCacheBytes=0;
async function fetchAttachment(f){
  if(_fileCache.has(f.id)) return _fileCache.get(f.id);
  let r; try{ r=await fetch(API+'?a=file&id='+encodeURIComponent(f.id),{credentials:'same-origin', cache:'no-store'}); }
  catch(e){ throw new Error('Cannot reach the server. Check your internet connection.'); }
  if(!r.ok){ let j=null; try{ j=await r.json(); }catch(e){} if(r.status===401) sessionLost('Your session has ended. Please sign in again.'); throw new Error((j&&j.error)||'The file could not be downloaded ('+r.status+').'); }
  let bytes=new Uint8Array(await r.arrayBuffer());
  if(f.key){ try{ const k=await crypto.subtle.importKey('raw', unb64(f.key), 'AES-GCM', false, ['decrypt']); bytes=new Uint8Array(await crypto.subtle.decrypt({name:'AES-GCM', iv:unb64(f.iv)}, k, bytes)); }
    catch(e){ throw new Error('The file could not be decrypted.'); } }
  if(bytes.length<20*1048576){ _fileCache.set(f.id, bytes); _fileCacheBytes+=bytes.length;
    while(_fileCacheBytes>60*1048576 && _fileCache.size>1){ const [k,v]=_fileCache.entries().next().value; _fileCache.delete(k); _fileCacheBytes-=v.length; } }
  return bytes;
}
function saveBytes(bytes, name){
  const u=URL.createObjectURL(new Blob([bytes],{type:'application/octet-stream'}));
  const a=document.createElement('a'); a.href=u; a.download=name||'file'; document.body.appendChild(a); a.click(); a.remove();
  setTimeout(()=>URL.revokeObjectURL(u), 30000);
}
async function downloadAttachment(f){ try{ saveBytes(await fetchAttachment(f), f.name); }catch(e){ alert(e.message); } }

/* ---------- preview ---------- */
async function openAttachment(f, opts={}){
  const k=fileKind(f);
  const d=openDialog(`<div class="dhd"><h2 style="word-break:break-all">${fileIcon(f)} ${esc(f.name)}</h2><span class="hint">${fmtSize(f.size)}</span><button class="ghost" data-close aria-label="Close">✕</button></div>
    <div class="fprev" data-body><div class="hint">Loading…</div></div>
    <div class="dacts"><button data-a="dl">⬇ Download</button>${k==='pdf'?'<button data-a="tab">↗ Open in a new tab</button>':''}${k==='msg'&&editable?'<button data-a="imp">📧 Add as a new task or event…</button>':''}<span class="sp"></span><button data-close>Close</button></div>`,{size:k==='msg'||k==='pdf'?'wide':'mid'});
  const B=$('[data-body]',d);
  $('[data-a="dl"]',d).onclick=()=>downloadAttachment(f);
  let bytes;
  try{ bytes=await fetchAttachment(f); }catch(e){ B.innerHTML=`<div class="warnbox">${esc(e.message)}</div>`; return d; }
  const url=()=>{ const u=URL.createObjectURL(new Blob([bytes],{type:safeType(f)})); d._urls=(d._urls||[]).concat(u); return u; };
  const prevClose=d._onclose; d._onclose=()=>{ (d._urls||[]).forEach(u=>setTimeout(()=>URL.revokeObjectURL(u),60000)); prevClose&&prevClose(); };
  if(k==='image') B.innerHTML=`<img src="${url()}" alt="${esc(f.name)}" style="max-width:100%;max-height:70vh;display:block;margin:auto">`;
  else if(k==='pdf'){ const u=url(); B.innerHTML=`<iframe class="fframe" src="${u}" title="${esc(f.name)}"></iframe><div class="hint">If the PDF does not show here, use “Open in a new tab” or Download.</div>`; $('[data-a="tab"]',d).onclick=()=>window.open(u,'_blank','noopener'); }
  else if(k==='audio'||k==='video') B.innerHTML=`<${k} controls src="${url()}" style="max-width:100%;display:block;margin:auto"></${k}>`;
  else if(k==='text') B.innerHTML=`<pre class="ftext">${esc(new TextDecoder().decode(bytes.subarray(0,1048576)))}${bytes.length>1048576?'\n…':''}</pre>`;
  else if(k==='msg'){
    let m; try{ m=readMsg(bytes); }catch(e){ B.innerHTML=`<div class="warnbox">${esc(e.message)}</div>`; return d; }
    B.innerHTML=emailPreviewHTML(m); bindEmailPreview(B, m);
    const ib=$('[data-a="imp"]',d); if(ib) ib.onclick=()=>{ closeDialog(d); openEmailImport(bytes, f.name); };
  }
  else B.innerHTML=`<p class="hint">This type of file cannot be shown here. Download it to open it with its own program.</p>`;
  return d;
}

/* ---------- list shown in details, popovers ---------- */
function filesListHTML(files){
  return files.map((f,i)=>`<div class="fitem"><a href="#" data-fopen="${i}" title="Open">${fileIcon(f)} ${esc(f.name)}</a><span class="hint">${fmtSize(f.size)}</span><button class="ghost fbtn" data-fdl="${i}" title="Download" aria-label="Download ${esc(f.name)}">⬇</button></div>`).join('');
}
function bindFilesList(root, files){
  $$('[data-fopen]',root).forEach(a=>a.onclick=e=>{ e.preventDefault(); openAttachment(files[+a.dataset.fopen]); });
  $$('[data-fdl]',root).forEach(b=>b.onclick=e=>{ e.preventDefault(); downloadAttachment(files[+b.dataset.fdl]); });
}

/* ---------- editor section ----------
   Files uploaded while the editor is open are deleted again if it is cancelled.
   Files removed from a saved item stay on the server until they have been unused for 7 days
   (so Undo and the Trash still work), then the automatic clean-up deletes them. */
function attachEditorHTML(){ return `<div class="sec">Attachments</div><div class="full att" data-att></div>`; }
function attachEditor(d, files, {ro, encrypt}={}){
  const el=$('[data-att]',d);
  const list=clone(arr(files)).map(f=>{ if(f.pending){ const g={...f}; delete g.pending; return g; } return f; });
  const mine=new Set(arr(files).filter(f=>f.pending).map(f=>f.id));    // uploaded for this editor (e.g. from an imported email)
  const up=[];                                                         // uploads in progress {name, p}
  let committed=false;
  const touchD=()=>{ d._modified=true; };
  const draw=()=>{
    el.innerHTML = (list.length?`<div class="flist">${list.map((f,i)=>`<div class="fitem" data-fi="${i}">
        <a href="#" data-fopen="${i}" title="Open">${fileIcon(f)} ${esc(f.name)}</a>${f.key?'<span title="Encrypted">🔒</span>':''}
        <span class="hint">${fmtSize(f.size)}${f.by?' · '+esc(f.by):''}${f.added?' · '+esc(fmtWhen(f.added)):''}</span>
        <span class="fbtns"><button type="button" class="ghost fbtn" data-fdl="${i}" title="Download" aria-label="Download">⬇</button>${ro?'':`<button type="button" class="ghost fbtn" data-fren="${i}" title="Rename" aria-label="Rename">✎</button><button type="button" class="ghost fbtn" data-frep="${i}" title="Replace with another file" aria-label="Replace">⇄</button><button type="button" class="ghost fbtn" data-fdel="${i}" title="Remove" aria-label="Remove">✕</button>`}</span></div>`).join('')}</div>`:(ro?'<span class="hint">None</span>':''))
      + up.map(u=>`<div class="fitem up"><span>⏫ ${esc(u.name)}</span><span class="fbar"><i style="width:${Math.round(u.p*100)}%"></i></span><span class="hint">${Math.round(u.p*100)}%</span></div>`).join('')
      + (ro?'':`<div class="fdrop" data-fdrop tabindex="0">Drop files here or <button type="button" data-fadd>📎 Add files…</button> <span class="hint">several at once · up to ${fmtSize((FILES_INFO&&FILES_INFO.max)||50*1048576)} each${encrypt&&encrypt()?' · encrypted (Personal)':''}</span></div>`);
    bindFilesList(el, list);
    if(ro) return;
    $('[data-fadd]',el).onclick=()=>pickFiles('', fl=>addFiles(fl));
    $$('[data-fdel]',el).forEach(b=>b.onclick=()=>{ list.splice(+b.dataset.fdel,1); touchD(); draw(); });
    $$('[data-fren]',el).forEach(b=>b.onclick=async()=>{ const f=list[+b.dataset.fren]; const x=fileExt(f);
      const n=await promptDlg('Rename attachment','New name', f.name); if(n==null||!n.trim()) return;
      let nn=n.trim().replace(/[\\/\x00-\x1f]+/g,'_').slice(0,200); if(x && !/\.[a-z0-9]{1,8}$/i.test(nn)) nn+='.'+x;
      f.name=nn; touchD(); draw(); });
    $$('[data-frep]',el).forEach(b=>b.onclick=()=>pickFiles('', async fl=>{ const i=+b.dataset.frep; const r=await uploadMany([fl[0]]); if(r[0]){ list.splice(i,1,r[0]); touchD(); draw(); } }, false));
    const z=$('[data-fdrop]',el);
    z.addEventListener('dragover',e=>{ if([...e.dataTransfer.types].includes('Files')){ e.preventDefault(); z.classList.add('over'); } });
    z.addEventListener('dragleave',()=>z.classList.remove('over'));
    z.addEventListener('drop',e=>{ e.preventDefault(); e.stopPropagation(); z.classList.remove('over'); if(e.dataTransfer.files.length) addFiles([...e.dataTransfer.files]); });
  };
  async function uploadMany(fl){
    const out=[];
    for(const file of fl){
      const u={name:file.name, p:0}; up.push(u); draw();
      try{ const r=await uploadAttachment(file,{encrypt:!!(encrypt&&encrypt()), onProgress:p=>{ u.p=p; draw(); }}); mine.add(r.id); out.push(r); }
      catch(e){ toast(`“${file.name}” was not added: ${e.message}`,{ms:9000}); }
      finally{ up.splice(up.indexOf(u),1); draw(); }
    }
    return out;
  }
  async function addFiles(fl){ const r=await uploadMany(fl); if(r.length){ list.push(...r); touchD(); draw(); } }
  // drop files anywhere on the editor
  d.addEventListener('dragover',e=>{ if(!ro && e.dataTransfer && [...e.dataTransfer.types].includes('Files')) e.preventDefault(); });
  d.addEventListener('drop',e=>{ if(ro || !e.dataTransfer || !e.dataTransfer.files.length) return; e.preventDefault(); addFiles([...e.dataTransfer.files]); });
  filesInfo().then(draw);
  draw();
  const ctl={
    get list(){ return list.map(f=>({...f})); },
    busy:()=>up.length>0,
    commit(){ committed=true; },
    finish(){ // dialog closed: delete what was uploaded here and not kept
      const keep=committed?new Set(list.map(f=>f.id)):new Set();
      const ids=[...mine].filter(id=>!keep.has(id) && !allFileIds().has(id));
      if(ids.length && editable) api('file_delete',{ids}).catch(()=>{});
    }
  };
  d._filesCtl=ctl;
  return ctl;
}
function pickFiles(accept, fn, multiple=true){ const i=$('#fileInput'); i.accept=accept||''; i.multiple=multiple; i.value=''; i.onchange=()=>{ const fl=[...i.files]; i.multiple=false; if(fl.length) fn(fl); }; i.click(); }

/* ---------- clean-up of unused files ---------- */
function allFileIds(){
  const s=new Set();
  for(const it of [...data.events, ...data.tasks, ...data.projects]) for(const f of fileRefs(it)) s.add(f.id);
  for(const x of data.trash) for(const f of fileRefs(x.item)) s.add(f.id);
  return s;
}
function canCleanFiles(){ return editable && session && !(data.private && privLocked); }   // a locked Personal block may hold references we cannot see
async function filesGc(manual){
  if(!canCleanFiles()){ if(manual) alert('Unlock Personal items first: their attachments must be counted too.'); return null; }
  try{ const r=await api('files_gc',{keep:[...allFileIds()]}); safeSet('wtc-filesgc', String(Date.now())); FILES_INFO=null; return r; }
  catch(e){ if(manual) alert(e.message); return null; }
}
function maybeFilesGc(){ if(DETAIL || !canCleanFiles()) return; const last=+safeGet('wtc-filesgc')||0; if(Date.now()-last>20*3600e3) setTimeout(()=>filesGc(false), 8000); }
/* Settings → Data */
async function filesUsageHTML(){
  const i=await filesInfo(true);
  return `${i.count!=null?`${i.count} file${i.count===1?'':'s'} · ${fmtSize(i.bytes)}`:'—'}${i.free!=null?` · ${fmtSize(i.free)} free on the server`:''} · up to ${fmtSize(i.max)} per file`;
}

/* ============================================================
   Import an Outlook email (.msg): preview, choose what to create,
   then the full editor opens with the email and its attachments
   ============================================================ */
function htmlToMarkdown(html){
  const doc=new DOMParser().parseFromString(html,'text/html');
  doc.querySelectorAll('script,style,head,title,meta,link,xml,o\\:p').forEach(x=>x.remove());
  const BLOCK=/^(P|DIV|H[1-6]|UL|OL|LI|TABLE|TR|BLOCKQUOTE|PRE|HR|SECTION|ARTICLE|HEADER|FOOTER)$/;
  let out='';
  const walk=(n, pre)=>{
    for(const c of n.childNodes){
      if(c.nodeType===3){ out+= pre ? c.nodeValue : c.nodeValue.replace(/[\s ]+/g,' '); continue; }
      if(c.nodeType!==1) continue;
      const t=c.tagName;
      if(t==='BR'){ out+='  \n'; continue; }
      if(t==='HR'){ out+='\n\n---\n\n'; continue; }
      if(t==='IMG'){ const a=c.getAttribute('alt'); if(a) out+='['+a+']'; continue; }
      if(BLOCK.test(t)) out+='\n\n';
      if(/^H[1-6]$/.test(t)) out+='#'.repeat(+t[1])+' ';
      if(t==='LI') out+= (c.parentNode&&c.parentNode.tagName==='OL'?'1. ':'- ');
      if(t==='TD'||t==='TH') out+=' ';
      if(t==='B'||t==='STRONG'||t==='I'||t==='EM'){ const mk=t==='B'||t==='STRONG'?'**':'*'; const s=out.length; walk(c, pre); const inner=out.slice(s);
        if(inner.trim() && !/\n\s*\n/.test(inner)){ const m=/^(\s*)([\s\S]*?)(\s*)$/.exec(inner); out=out.slice(0,s)+m[1]+mk+m[2]+mk+m[3]; } continue; }   // only within one paragraph
      if(t==='A'){ const h=c.getAttribute('href')||''; const s=out.length; walk(c, pre); const txt=out.slice(s).trim();
        if(/^https?:\/\//i.test(h) && txt && txt!==h) out=out.slice(0,s)+'['+txt.replace(/[\[\]]/g,'')+']('+h.replace(/[()\s]/g,encodeURIComponent)+')'; continue; }
      walk(c, pre || t==='PRE');
      if(BLOCK.test(t)) out+='\n\n';
    }
  };
  walk(doc.body||doc, false);
  return out.replace(/[ \t]+\n/g,m=>m.startsWith('  ')?'  \n':'\n').replace(/\n[ \t]+/g,'\n').replace(/\n{3,}/g,'\n\n').trim();
}
function textToMarkdown(t){
  return String(t||'').replace(/\r\n?/g,'\n').replace(/[ \t]+$/gm,'').replace(/\n{3,}/g,'\n\n').trim()
    .replace(/^([#>+\-*]|\d+[.)])(\s)/gm,'\\$1$2')                       // plain text: no accidental headings or lists
    .replace(/([^\n])\n(?!\n)/g,'$1  \n');                                // keep the email's line breaks
}
function emailBodyMd(m){ return m.html ? htmlToMarkdown(m.html) : textToMarkdown(m.text); }
function emailFmtDate(d){ return d ? d.toLocaleString('en-GB',{weekday:'short',day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : ''; }
function emailFrom(m){ return m.fromName && m.fromEmail && m.fromName!==m.fromEmail ? `${m.fromName} <${m.fromEmail}>` : (m.fromName||m.fromEmail||''); }
function emailNotes(m, withBody){
  const L=[];
  const mdx=s=>String(s).replace(/([\\`*_\[\]<>])/g,'\\$1');
  if(emailFrom(m)) L.push('**From:** '+mdx(emailFrom(m)));
  if(m.to) L.push('**To:** '+mdx(m.to));
  if(m.cc) L.push('**Cc:** '+mdx(m.cc));
  if(m.date) L.push('**Sent:** '+mdx(emailFmtDate(m.date)));
  if(m.subject) L.push('**Subject:** '+mdx(m.subject));
  let s=L.join('  \n');
  if(withBody){ let b=emailBodyMd(m); const MAX=30000; if(b.length>MAX) b=b.slice(0,MAX)+'\n\n*… shortened; the full email is attached.*'; if(b) s+='\n\n---\n\n'+b; }
  return s;
}
/* email shown in a sandboxed frame (no scripts, no remote images); cid: pictures come from the attachments */
function emailFrameDoc(m){
  let h=m.html||'';
  for(const a of m.atts) if(a.cid && a.data && /^image\//i.test(a.mime||'') && a.data.length<4*1048576){
    let bin=''; for(let i=0;i<a.data.length;i+=8192) bin+=String.fromCharCode.apply(null,a.data.subarray(i,i+8192));
    h=h.split('cid:'+a.cid).join('data:'+a.mime+';base64,'+btoa(bin)); }
  return '<!doctype html><meta charset="utf-8"><base target="_blank"><style>body{font:14px/1.45 system-ui,Segoe UI,Arial,sans-serif;margin:10px;color:#222;background:#fff;word-wrap:break-word}img{max-width:100%;height:auto}</style>'+h;
}
function emailPreviewHTML(m, opts={}){
  const att=m.atts.filter(a=>!a.hidden);
  return `<div class="mailhd">
      <div class="mailsubj">${esc(m.subject||'(no subject)')}${m.importance===2?' <span class="tag" style="color:var(--sick)">❗ High importance</span>':''}</div>
      <div><b>From</b> ${esc(emailFrom(m))}</div>${m.to?`<div><b>To</b> ${esc(m.to)}</div>`:''}${m.cc?`<div><b>Cc</b> ${esc(m.cc)}</div>`:''}
      ${m.date?`<div><b>Sent</b> ${esc(emailFmtDate(m.date))}</div>`:''}
      ${att.length&&!opts.noAtt?`<div class="mailatt">${att.map(a=>`<span class="tag" ${a.data?`data-matt="${m.atts.indexOf(a)}" style="cursor:pointer" title="Download"`:''}>📎 ${esc(a.name)}${a.size?' · '+fmtSize(a.size):''}</span>`).join(' ')}</div>`:''}
    </div>
    ${m.html&&m.text?`<div class="seg mailseg"><button type="button" data-mv="html" class="on">Formatted</button><button type="button" data-mv="text">Plain text</button></div>`:''}
    <div class="mailbody" data-mbody></div>`;
}
function bindEmailPreview(root, m){
  const body=$('[data-mbody]',root);
  const show=v=>{ if(v==='html' && m.html){ body.innerHTML='<iframe class="mailframe" sandbox="allow-popups allow-popups-to-escape-sandbox" referrerpolicy="no-referrer" title="Email"></iframe>'; $('iframe',body).srcdoc=emailFrameDoc(m); }
    else body.innerHTML=`<pre class="ftext">${esc((m.text||'').replace(/\r\n?/g,'\n').replace(/\n{3,}/g,'\n\n'))||'<span class="hint">(no text)</span>'}</pre>`;
    $$('[data-mv]',root).forEach(b=>b.classList.toggle('on',b.dataset.mv===v)); };
  $$('[data-mv]',root).forEach(b=>b.onclick=()=>show(b.dataset.mv));
  $$('[data-matt]',root).forEach(t=>t.onclick=()=>{ const a=m.atts[+t.dataset.matt]; if(a&&a.data) saveBytes(a.data,a.name); });
  show(m.html?'html':'text');
}
/* parse, then make the plain text from the HTML when the stored plain text lost its letters (ANSI .msg) */
function readMsg(bytes){ const m=parseMsg(bytes); if(m.textFromHtml && m.html){ try{ m.text=htmlToMarkdown(m.html).replace(/\*\*/g,'').replace(/ {2}\n/g,'\n'); }catch(e){} } return m; }
function msgFileOk(f){ return /\.msg$/i.test(f.name||''); }
async function importMsgFile(file){
  if(!editable) return;
  if(file.size>(await filesInfo()).max*2) return alert('That email is too large to import.');
  openEmailImport(new Uint8Array(await file.arrayBuffer()), file.name);
}
function openEmailImport(bytes, fname){
  let m; try{ m=readMsg(bytes); }catch(e){ return alert(`“${fname}” could not be read: ${e.message}`); }
  const k=todayKey();
  const n=new Date(NOW()); const st=minToHM(Math.min(23*60,Math.ceil((n.getHours()*60+n.getMinutes())/30)*30+30));
  const atts=m.atts.map((a,i)=>({...a,i})).filter(a=>a.data || a.embedded);
  const d=openDialog(`<div class="dhd"><h2>📧 Import email</h2><span class="hint" style="word-break:break-all">${esc(fname)}</span><button class="ghost" data-close aria-label="Close">✕</button></div>
    <div class="mailimp">
      <div class="mailprev">${emailPreviewHTML(m,{noAtt:true})}</div>
      <form class="frm mailform" autocomplete="off">
        <label>Add as</label><select name="kind"><option value="task">☑ Task</option>${Object.entries(CATEGORIES).map(([c,l])=>`<option value="${c}">${l}</option>`).join('')}</select>
        <label>Title</label><input type="text" name="title" value="${esc(m.subject||'Email from '+(m.fromName||m.fromEmail||''))}" required>
        <label>Calendar</label><div class="row"><select name="calendarId">${calOptions('work')}</select><select name="projectId" aria-label="Project">${projOptions('work',null)}</select></div>
        <label data-t>Due</label><div class="row" data-t><input type="date" name="due" value="${keyAdd(k,2)}"><select name="priority" aria-label="Priority">${[[1,'P1 urgent'],[2,'P2 high'],[3,'P3 normal'],[4,'P4 low']].map(([v,l])=>`<option value="${v}" ${(m.importance===2?2:m.importance===0?4:3)===v?'selected':''}>${l}</option>`).join('')}</select></div>
        <label data-e>When</label><div class="row" data-e><input type="date" name="sd" value="${k}"><input type="time" name="st" value="${st}" step="300" data-timed> – <input type="time" name="et" value="${localAddMin(k+'T'+st,S().defaultEventMin||60).slice(11,16)}" step="300" data-timed><label class="hint"><input type="checkbox" name="allDay"> All day</label></div>
        <label>Notes</label><label class="hint"><input type="checkbox" name="withBody" checked> Sender, recipients, date and the email text</label>
        <label>Attach</label><div class="list-edit">
          <label class="hint"><input type="checkbox" name="orig" checked> 📧 The original email (${esc(fname)}, ${fmtSize(bytes.length)})</label>
          ${atts.map(a=>`<label class="hint"><input type="checkbox" data-ai="${a.i}" ${a.embedded&&!a.data?'disabled':a.hidden?'':'checked'}> ${fileIcon(a)} ${esc(a.name)}${a.size?' · '+fmtSize(a.size):''}${a.hidden?' · <i>picture inside the text</i>':''}${a.embedded&&!a.data?' · <i>attached email: kept inside the original email</i>':''}</label>`).join('')||'<span class="hint">The email has no attachments.</span>'}
        </div>
        <div class="full" data-prog></div>
      </form>
    </div>
    <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button data-a="now">Add now</button><button class="primary" data-a="edit">Continue to all details…</button></div>`,{size:'wide', focus:'[name=title]'});
  const F=$('form',d); bindCalProj(F); bindEmailPreview($('.mailprev',d), m);
  const kind=$('[name=kind]',F), allDay=$('[name=allDay]',F);
  const sync=()=>{ const t=kind.value==='task'; $$('[data-t]',F).forEach(x=>x.style.display=t?'':'none'); $$('[data-e]',F).forEach(x=>x.style.display=t?'none':'');
    $$('[data-timed]',F).forEach(x=>x.style.display=allDay.checked?'none':''); };
  kind.onchange=()=>{ if(kind.value==='deadline') allDay.checked=true; sync(); }; allDay.onchange=sync; sync();
  let busy=false;
  const go=async(edit)=>{
    if(busy || !F.reportValidity()) return;
    const v=n=>$(`[name=${n}]`,F).value;
    const calendarId=v('calendarId'), projectId=v('projectId')||null, enc=isPrivCal(calendarId);
    if(enc && !ensurePrivReadySync()) return;
    // what to upload
    const jobs=[]; if($('[name=orig]',F).checked) jobs.push({name:/\.msg$/i.test(fname)?fname:fname+'.msg', bytes, type:'application/vnd.ms-outlook'});
    for(const c of $$('[data-ai]:checked',F)){ const a=m.atts[+c.dataset.ai]; if(a&&a.data) jobs.push({name:a.name, bytes:a.data, type:a.mime}); }
    busy=true; $$('.dacts button',d).forEach(b=>b.disabled=true);
    const P=$('[data-prog]',d); const files=[];
    try{
      for(const [j,x] of jobs.entries()){
        P.innerHTML=`<div class="fitem up"><span>⏫ ${esc(x.name)} (${j+1} of ${jobs.length})</span><span class="fbar"><i style="width:0"></i></span></div>`;
        const r=await uploadAttachment(new Blob([x.bytes],{type:x.type||''}),{name:x.name, encrypt:enc, onProgress:p=>{ const i=$('.fbar i',P); if(i) i.style.width=Math.round(p*100)+'%'; }});
        files.push(r);
      }
    }catch(e){ P.innerHTML=`<div class="warnbox">Upload failed: ${esc(e.message)}</div>`; busy=false; $$('.dacts button',d).forEach(b=>b.disabled=false);
      if(files.length) api('file_delete',{ids:files.map(f=>f.id)}).catch(()=>{}); return; }
    const notes=emailNotes(m, $('[name=withBody]',F).checked);
    const tags=['email'];
    let it;
    if(kind.value==='task'){
      it=newTask({title:v('title').trim(), calendarId, projectId, dueDate:v('due')||null, priority:+v('priority'), notes, tags});
    } else {
      const sd=v('sd')||k, ad=allDay.checked;
      let s1=sd+'T'+(v('st')||S().workStart), e1=sd+'T'+(v('et')||'');
      if(!(localDiffMin(s1,e1)>0)) e1=localAddMin(s1,S().defaultEventMin||60);
      it=newEvent({title:v('title').trim(), calendarId, projectId, category:kind.value, start:ad?sd:s1, end:ad?sd:e1, tz:ACTIVE_TZ, allDay:ad, notes, tags,
        attendees:[...new Set([m.fromName||m.fromEmail, ...m.recips.filter(r=>r.type!=='bcc').map(r=>r.name||r.email)].filter(Boolean))].slice(0,30)});
      if(ad) delete it.tz;
    }
    it.files=files;
    d._onclose=null; closeDialog(d);
    if(edit){
      it.files=files.map(f=>({...f, pending:true}));      // deleted again if the editor is cancelled
      return it.type==='task' ? openTaskEditor(null, it) : openEventEditor(null, it);
    }
    if(!saveNewItem(it)){ api('file_delete',{ids:files.map(f=>f.id)}).catch(()=>{}); return; }
    toast(`Added “${it.title}”${files.length?` with ${files.length} attachment${files.length===1?'':'s'}`:''}`,{actions:[['Open',()=>it.type==='task'?openTaskEditor(it.id):openEvent(it.id)],['Undo',undo]]});
  };
  $('[data-a="now"]',d).onclick=()=>go(false);
  $('[data-a="edit"]',d).onclick=()=>go(true);
  F.onsubmit=e=>{ e.preventDefault(); go(true); };
  return d;
}

/* drop an Outlook email anywhere on the calendar to import it; other files dropped outside an
   editor are ignored (instead of the browser leaving the calendar to show them) */
window.addEventListener('dragover',e=>{ if(e.dataTransfer && [...e.dataTransfer.types].includes('Files')) e.preventDefault(); });
window.addEventListener('drop',e=>{
  if(!e.dataTransfer || !e.dataTransfer.files.length) return;
  e.preventDefault();
  if(topDialog() || !session || DETAIL) return;
  const f=[...e.dataTransfer.files].find(msgFileOk);
  if(!f) return toast('Drop an Outlook email (.msg) here to import it, or open an item and drop files on it to attach them.');
  if(!editable) return toast('Your account can only view the calendar.');
  importMsgFile(f);
});
