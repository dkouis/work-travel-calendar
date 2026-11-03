/* ============================================================
   Server: load, save (auto), merge on conflict, sign-in
   ============================================================ */
async function api(action, body, query){
  const opt = {method: body===undefined ? 'GET' : 'POST', credentials:'same-origin', cache:'no-store', headers:{'Accept':'application/json'}};
  if(body!==undefined){ opt.headers['Content-Type']='application/json'; opt.headers['X-WTC-Token']=token; opt.body=JSON.stringify(body); }
  let r;
  try{ r = await fetch(API+'?a='+action+(query||''), opt); }
  catch(e){ const err=new Error('Cannot reach the server. Check your internet connection.'); err.status=0; throw err; }
  let j=null; try{ j=await r.json(); }catch(e){}
  if(!r.ok){
    const err=new Error((j && j.error) || ('The server answered with an error ('+r.status+'). Is PHP enabled on this server?'));
    err.status=r.status; err.body=j;
    if(r.status===401 && !['login','me'].includes(action)) sessionLost('Your session has ended. Please sign in again.');
    throw err;
  }
  if(j===null) throw new Error('The server did not answer correctly. Is PHP enabled on this server?');
  return j;
}

/* ---------- saving ---------- */
function snapshotState(){ return JSON.stringify({days:data.days, monthNotes:data.monthNotes, owner:data.owner, settings:data.settings, calendars:data.calendars, projects:data.projects, events:data.events, tasks:data.tasks, templates:data.templates, trash:data.trash}); }
function restoreState(s){ const o=JSON.parse(s); Object.assign(data,o); normalizeItems(); }
/* Call after every change. label groups rapid changes (typing) into one undo step. */
function changed(label){
  if(!editable) return;
  const now=Date.now();
  if(lastSnap && !(label && label===lastSnapLabel && now-lastSnapAt<1500)){
    undoStack.push(lastSnap); if(undoStack.length>50) undoStack.shift(); redoStack=[];
  }
  lastSnapLabel=label||''; lastSnapAt=now;
  lastSnap = snapshotState();
  data.updated = nowISO();
  invalidateCaches();
  dirty = true; changeSeq++;
  if(!conflict) setSave('Saving…');
  clearTimeout(saveTimer); saveTimer = setTimeout(saveNow, 700);
}
function undo(){ if(!editable || !undoStack.length) return toast('Nothing to undo'); redoStack.push(snapshotState()); restoreState(undoStack.pop()); lastSnap=snapshotState(); lastSnapLabel=''; invalidateCaches(); dirty=true; changeSeq++; clearTimeout(saveTimer); saveTimer=setTimeout(saveNow,700); setSave('Saving…'); renderAll(); toast('Undone'); }
function redo(){ if(!editable || !redoStack.length) return toast('Nothing to redo'); undoStack.push(snapshotState()); restoreState(redoStack.pop()); lastSnap=snapshotState(); lastSnapLabel=''; invalidateCaches(); dirty=true; changeSeq++; clearTimeout(saveTimer); saveTimer=setTimeout(saveNow,700); setSave('Saving…'); renderAll(); toast('Redone'); }

async function saveNow(){
  saveTimer = null;
  if(!editable || !dirty || conflict || !session || offlineMode) return;
  if(saving){ saveTimer = setTimeout(saveNow, 400); return; }
  saving = true;
  const seq = changeSeq;
  try{
    const out = await serialize();
    const size = JSON.stringify(out).length;
    sizeWarn = size > 2400000;
    let j;
    try{ j = await api('save', {rev, data:out}); }
    catch(e){
      if(e.status!==409) throw e;
      // someone else saved first: merge their changes with ours, then save again
      const merged = await mergeWithServer();
      if(!merged){ conflict = e.body; setSave('Not saved','err'); return; }
      j = await api('save', {rev, data: await serialize()});
    }
    rev = j.rev; lastSaved = {by:session.user, saved:j.saved};
    base = await serialize(); basePriv = privKey ? clone(privPart()) : basePriv;
    cacheOffline();
    netError = false;
    if(seq===changeSeq){ dirty=false; setSave('Saved ✓','ok'); }
    else saveTimer = setTimeout(saveNow, 300);
  }catch(e){
    if(e.status===409){ conflict = e.body; setSave('Not saved','err'); }
    else if(e.status===401){ /* sign-in screen shown; save is retried after signing in */ }
    else if(e.status===403){ setSave('Not saved','err'); alert(e.message); }
    else if(e.status===413){ setSave('Not saved','err'); alert('The calendar has become too large to save (over 3 MB). Use ⚙ Settings → Data → Archive to move old items out.'); }
    else { netError = true; setSave('Not saved · retrying…','err'); saveTimer = setTimeout(saveNow, 5000); }
  }finally{ saving=false; renderBanner(); }
}
function setSave(t,cls){ const s=$('#saveState'); if(s){ s.textContent=t; s.className=cls||''; } }

/* 3-way merge: base (what we loaded) vs mine (now) vs theirs (server). */
const MERGE_COLS = ['calendars','projects','events','tasks','templates','trash'];
function mergeCollections(b, m, t, conflicts, label){
  const bm=new Map(arr(b).map(x=>[x.id,x])), mm=new Map(arr(m).map(x=>[x.id,x])), tm=new Map(arr(t).map(x=>[x.id,x]));
  const ids=new Set([...mm.keys(),...tm.keys()]); const out=[];
  const same=(x,y)=>JSON.stringify(x)===JSON.stringify(y);
  for(const id of ids){
    const bx=bm.get(id), mx=mm.get(id), tx=tm.get(id);
    if(same(mx,bx)){ if(tx) out.push(tx); continue; }          // I didn't touch it: take theirs (or their deletion)
    if(same(tx,bx)){ if(mx) out.push(mx); continue; }          // they didn't touch it: take mine
    if(!mx){ out.push(tx); continue; }                          // I deleted, they changed: keep theirs
    if(!tx){ out.push(mx); continue; }                          // they deleted, I changed: keep mine
    const keepMine = (mx.updated||'') >= (tx.updated||'');
    out.push(keepMine?mx:tx);
    if(!same(mx,tx)) conflicts.push((label||'')+(mx.title||mx.name||id)+' — kept '+(keepMine?'your':'their')+' version');
  }
  // keep order of mine, new ones from theirs appended
  const order=new Map(arr(m).map((x,i)=>[x.id,i]));
  return out.sort((x,y)=>(order.has(x.id)?order.get(x.id):1e9)-(order.has(y.id)?order.get(y.id):1e9));
}
function mergeMaps(b, m, t, conflicts, label){
  b=obj(b); m=obj(m); t=obj(t);
  const out={}; const same=(x,y)=>JSON.stringify(x)===JSON.stringify(y);
  for(const k of new Set([...Object.keys(m),...Object.keys(t)])){
    if(same(m[k],b[k])){ if(t[k]!==undefined) out[k]=t[k]; continue; }
    if(same(t[k],b[k])){ if(m[k]!==undefined) out[k]=m[k]; continue; }
    out[k]= m[k]!==undefined ? m[k] : t[k];
    if(m[k]!==undefined && t[k]!==undefined && !same(m[k],t[k])) conflicts.push(label+k+' — kept your version');
  }
  return out;
}
async function mergeWithServer(){
  if(!base) return false;
  let j; try{ j = await api('load', undefined, ''); }catch(e){ return false; }
  const {d:theirs} = normalize(j.data);
  const mine = clone(data), conflicts=[];
  const mineSer = await serialize();
  const res = clone(theirs);
  // public collections
  for(const c of MERGE_COLS) res[c] = mergeCollections(base[c], mineSer[c], theirs[c], conflicts, '');
  res.days = mergeMaps(base.days, mine.days, theirs.days, conflicts, 'Day ');
  res.monthNotes = mergeMaps(base.monthNotes, mine.monthNotes, theirs.monthNotes, conflicts, 'Month notes ');
  res.owner = (mine.owner===base.owner) ? theirs.owner : mine.owner;
  res.settings = JSON.stringify(mine.settings)===JSON.stringify(base.settings) ? theirs.settings : mine.settings;
  // private part
  let privMerged=null;
  if(privKey && theirs.private){
    let tp=null; try{ tp = await decPriv(privKey, theirs.private); }catch(e){}
    if(tp){ const mp=privPart(); privMerged={}; for(const c of PRIV_COLS) privMerged[c]=mergeCollections(basePriv?basePriv[c]:[], mp[c], tp[c], conflicts, '🔒 '); }
  }
  data = normalize(res).d;
  if(privMerged) mergePriv(privMerged);
  else if(!privKey) data.private = theirs.private;
  rev = j.rev; lastSaved={by:j.by||'', saved:j.saved||''};
  base = await serialize(); basePriv = privKey?clone(privPart()):null;
  mergeNote = {by:j.by||'someone', conflicts};
  invalidateCaches(); lastSnap=snapshotState();
  renderAll();
  return true;
}

/* ---------- loading ---------- */
async function loadData(force){
  const j = await api('load', undefined, force ? '' : '&since='+rev);
  if(j.unchanged) return false;
  const {d, wasV1} = normalize(j.data);
  const prevPrivKey = privKey;
  data = d; rev = j.rev; lastSaved = {by:j.by||'', saved:j.saved||''};
  base = normalize(j.data).d;
  privKey = null; privLocked = false; basePriv = null;
  if(data.private){
    if(prevPrivKey){ try{ const p=await decPriv(prevPrivKey, data.private); privKey=prevPrivKey; basePriv=clone(p); mergePriv(p); }catch(e){ await tryAutoUnlock(); } }
    else await tryAutoUnlock();
    if(data.private && !privKey) privLocked = true;
  }
  invalidateCaches();
  lastSnap = snapshotState(); undoStack=[]; redoStack=[];
  cacheOffline();
  if(wasV1 && editable){
    // first open with the new app: upgrade the stored calendar (days, flights, comments stay as they are)
    const days=Object.keys(data.days).length;
    console.info('Upgraded calendar to v2;', days, 'days kept');
    changed('migrate');
    setTimeout(()=>toast('Calendar upgraded: projects, events, tasks and reminders are now available. Your '+days+' days of entries are unchanged.', {sticky:false, ms:9000}), 600);
  }
  return true;
}
async function reloadData(manual){
  if(!session || offlineMode) return;
  if(editable && (dirty || saving || conflict)){ if(manual) alert(conflict ? 'Choose what to do with your change first (see the message at the top).' : 'Your latest change is still being saved. Try again in a moment.'); return; }
  try{
    const changedOnServer = await loadData(false);
    if(changedOnServer){ renderAll(); }
    if(manual) setSave(editable?'Saved ✓':'Up to date','ok');
  }catch(e){ if(manual && e.status!==401) alert(e.message); }
}
setInterval(()=>{ if(session && !document.hidden) reloadData(false); }, POLL_MS);
document.addEventListener('visibilitychange', ()=>{
  if(!document.hidden && session) reloadData(false);
  if(document.hidden && editable && dirty){ clearTimeout(saveTimer); saveNow(); }
});

/* ---------- offline copy (optional, per device) ---------- */
function cacheOffline(){
  if(!session || !prefs.offlineCopy) return;
  serialize().then(d=>{ try{ localStorage.setItem('wtc-offline', JSON.stringify({user:session.user, role:session.role, at:nowISO(), rev, data:d})); }catch(e){} });
}
function clearOffline(){ safeDel('wtc-offline'); }
async function enterOffline(){
  const c=jget('wtc-offline',null); if(!c) return false;
  session={user:c.user, role:c.role}; editable=false; offlineMode=true; loadPrefs();
  data=normalize(c.data).d; rev=c.rev; await tryAutoUnlock();
  document.body.classList.remove('locked'); afterModeChange();
  showBanner(`⚠ Offline: showing the copy saved on this device at ${esc(fmtWhen(c.at))}. Changes are not possible until the server can be reached.`,'warn',[['Try again',()=>location.reload()]]);
  return true;
}

/* ---------- sign-in ---------- */
function showScreen(which, msg){
  document.body.classList.add('locked');
  $('#loginForm').style.display = which==='login' ? '' : 'none';
  $('#setupForm').style.display = which==='setup' ? '' : 'none';
  $('#bootMsg').style.display = which==='boot' ? '' : 'none';
  if(which==='login'){ $('#lErr').textContent = msg||''; setTimeout(()=>$('#lUser').focus(),0); }
  if(which==='setup'){ $('#sErr').textContent = msg||''; }
  if(which==='boot'){ $('#bootMsg').innerHTML = msg||''; }
}
function sessionLost(msg){
  const keep = editable && dirty;
  session = null;
  showScreen('login', msg + (keep ? ' Your last change is kept and will be saved after you sign in.' : ''));
}
async function enter(me){
  const sameUser = session===null && data && dirty && editable && me.role==='editor';
  session = {user:me.user, role:me.role}; token = me.token||''; editable = me.role==='editor';
  loadPrefs();
  if(sameUser){ document.body.classList.remove('locked'); afterModeChange(); saveNow(); return; }
  dirty=false; conflict=null;
  await loadData(true);
  document.body.classList.remove('locked');
  afterModeChange();
  startReminders();
  setTimeout(tzTravelPrompt, 2500);
}
async function boot(){
  showScreen('boot', 'Loading…');
  try{
    const me = await api('me');
    if(me.setup) return showScreen('setup');
    if(me.user) return await enter(me);
    showScreen('login');
  }catch(e){
    if(e.status===0 && await enterOffline()) return;
    showScreen('boot', '<b>The calendar could not start.</b><br>'+esc(e.message));
  }
}
$('#loginForm').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const u=$('#lUser').value.trim(), p=$('#lPass').value, btn=$('#lBtn');
  if(!u||!p) return;
  btn.disabled=true; btn.textContent='Signing in…'; $('#lErr').textContent='';
  try{
    const me = await api('login', {user:u, pass:p, remember:$('#lRemember').checked});
    $('#lPass').value='';
    await enter(me);
  }catch(err){ $('#lErr').textContent = err.message; }
  finally{ btn.disabled=false; btn.textContent='Sign in'; }
});
$('#setupForm').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const v=id=>$('#'+id).value, btn=$('#sBtn');
  if(v('sEdPass')!==v('sEdPass2') || v('sVwPass')!==v('sVwPass2')){ $('#sErr').textContent='The two passwords of an account do not match.'; return; }
  btn.disabled=true; $('#sErr').textContent='';
  try{
    const me = await api('setup', {editor:{user:v('sEdUser').trim(), pass:v('sEdPass')}, viewer:{user:v('sVwUser').trim(), pass:v('sVwPass')}});
    await enter(me);
  }catch(err){ $('#sErr').textContent = err.message; }
  finally{ btn.disabled=false; }
});
async function signOut(){
  if(editable && dirty && !confirm('Your latest change has not reached the server yet and will be lost.\n\nSign out anyway?')) return;
  clearOffline();
  try{ await api('logout', {}); }catch(e){}
  location.reload();
}
function afterModeChange(){
  $('#userBadge').innerHTML = session ? `👤 <b>${esc(session.user)}</b> · ${editable?'Editor':'Viewer'}` : '';
  if(offlineMode) setSave('Offline','err');
  else if(!editable) setSave('Read-only');
  else if(!dirty && !conflict) setSave('Saved ✓','ok');
  $$('[data-ed]').forEach(el=>el.style.display = editable ? '' : 'none');
  renderBanner(); renderAll();
}
