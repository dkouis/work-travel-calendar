/* ============================================================
   Data model v2, migration, private (Personal) encryption
   ============================================================ */
function defaultCalendars(){
  return [{id:'work',name:'Work',color:'#3b5bdb',private:false,order:0},{id:'personal',name:'Personal',color:'#2f9e44',private:true,order:1}];
}
function seedTemplates(){
  return [{id:'tpl-mission',name:'Brussels mission (3 days)',created:nowISO(),items:[
    {kind:'flight',offsetDays:0,from:'Athens',to:'Brussels',time:'morning'},
    {kind:'event',offsetDays:0,title:'Working group meeting',startTime:'14:00',endTime:'17:00',tz:'Europe/Brussels',category:'meeting',location:'Brussels'},
    {kind:'flight',offsetDays:2,from:'Brussels',to:'Athens',time:'evening'},
    {kind:'task',offsetDays:5,title:'Mission expense claim',estimateMin:30,priority:2}
  ]}];
}
/* Turn whatever the server sent (v1 or v2) into the v2 shape. Never touches
   days / monthNotes / owner beyond what v1 already did. */
function normalize(raw){
  const d = (raw && typeof raw==='object') ? JSON.parse(JSON.stringify(raw)) : {};
  d.version = 1; d.owner = d.owner || '';
  d.days = obj(d.days); d.monthNotes = obj(d.monthNotes);
  delete d.accounts;
  const wasV1 = d.schema !== SCHEMA;
  d.schema = SCHEMA;
  d.settings = Object.assign({}, DEFAULT_SETTINGS, obj(d.settings));
  for(const e of Object.values(d.days)) for(const f of arr(e.flights)) if(f.time==='evening') f.time='noon'; // v1 "evening" flights → Noon
  if(!obj(raw&&raw.settings).dayHalves){ // working day = morning 08:30–13:00 + evening 13:00–17:30
    if(d.settings.workStart==='09:00' && d.settings.workEnd==='18:00'){ d.settings.workStart='08:30'; d.settings.workEnd='17:30'; }
    d.settings.midday=d.settings.midday||'13:00'; d.settings.dayHalves=1;
  }
  d.calendars = arr(d.calendars).length ? arr(d.calendars) : defaultCalendars();
  d.projects = arr(d.projects); d.events = arr(d.events); d.tasks = arr(d.tasks);
  d.templates = arr(d.templates); d.trash = arr(d.trash);
  if(wasV1 && !d.templates.length) d.templates = seedTemplates();
  if(!d.private || typeof d.private!=='object' || Array.isArray(d.private)) d.private = null;
  d.busy = arr(d.busy);
  for(const e of d.events){ e.exceptions = obj(e.exceptions); e.reminders = arr(e.reminders); e.tags = arr(e.tags); e.attendees=arr(e.attendees); e.links=arr(e.links); e.linkedTaskIds=arr(e.linkedTaskIds); e.files=arr(e.files); }
  for(const t of d.tasks){ t.blocks = arr(t.blocks); t.checklist = arr(t.checklist); t.reminders = arr(t.reminders); t.tags = arr(t.tags); t.dependsOn = arr(t.dependsOn); }
  // purge trash older than 30 days
  const cut = Date.now()-30*864e5;
  d.trash = d.trash.filter(x=>Date.parse(x.deletedAt||0)>cut);
  return {d, wasV1};
}

/* ---------- lookups ---------- */
function cal(id){ return data.calendars.find(c=>c.id===id) || data.calendars[0]; }
function proj(id){ return id ? data.projects.find(p=>p.id===id) : null; }
function findItem(id){ return data.events.find(x=>x.id===id) || data.tasks.find(x=>x.id===id) || null; }
function task(id){ return data.tasks.find(x=>x.id===id); }
function ev(id){ return data.events.find(x=>x.id===id); }
function isPrivCal(id){ const c=data.calendars.find(c=>c.id===id); return !!(c && c.private); }
function isPrivate(it){ return !!(it && (it.private || isPrivCal(it.calendarId))); }
function itemColor(it){
  if(prefs.colorBy==='priority' && it.type==='task') return ({1:'#c92a2a',2:'#e8590c',3:'#3b5bdb',4:'#868e96'})[it.priority||3];
  if(prefs.colorBy!=='calendar'){ const p=proj(it.projectId); if(p && p.color) return p.color; }
  return cal(it.calendarId).color;
}
function projectsOf(calId){ return data.projects.filter(p=>p.calendarId===calId); }
function locTz(k){ const l=locationOf(k); const t=l && CITY_TZ[l.name.trim().toLowerCase()]; return t || S().homeTz || BROWSER_TZ; }
function touch(it){ it.updated = nowISO(); if(!it.created) it.created = it.updated; return it; }

/* Location: explicit value, otherwise inferred from the destination of the latest flight on/before that day */
function day(k){ return data.days[k] || {}; }
function dayMut(k){ return data.days[k] || (data.days[k] = {}); }
function prune(k){
  const e = data.days[k]; if(!e) return;
  if(e.flights && !e.flights.length) delete e.flights;
  for(const f of ['am','pm','location','comment']) if(!e[f]) delete e[f];
  if(!Object.keys(e).length) delete data.days[k];
}
let _flightKeys = null;
function flightKeys(){ return _flightKeys || (_flightKeys = Object.keys(data.days).filter(x=>data.days[x].flights && data.days[x].flights.length).sort()); }
function invalidateCaches(){ _flightKeys=null; _occCache.clear(); }
function locationOf(k){
  const e = day(k);
  if(e.location) return {name:e.location, auto:false};
  const all = flightKeys();
  let lo=0, hi=all.length-1, best=-1;
  while(lo<=hi){ const m=(lo+hi)>>1; if(all[m]<=k){ best=m; lo=m+1; } else hi=m-1; }
  if(best<0){
    if(!all.length) return null;
    const from = data.days[all[0]].flights[0].from;
    return from ? {name:from, auto:true} : null;
  }
  const fl = data.days[all[best]].flights;
  const to = fl[fl.length-1].to;
  return to ? {name:to, auto:true} : null;
}
/* Leave / sick / holiday per half day: morning = work start → midday, evening = midday → work end */
const OFF_STATUSES=['leave','sick','holiday'];
function halfBounds(){ const s=S(); return {ws:hmToMin(s.workStart)??510, mid:hmToMin(s.midday)??780, we:hmToMin(s.workEnd)??1050}; }
function offIntervals(k){
  const e=day(k), d0=fromKey(k).getTime(), {ws,mid,we}=halfBounds(), out=[];
  if(OFF_STATUSES.includes(e.am)) out.push({half:'morning', st:e.am, a:ws, b:mid, s:d0+ws*60000, e:d0+mid*60000});
  if(OFF_STATUSES.includes(e.pm)) out.push({half:'evening', st:e.pm, a:mid, b:we, s:d0+mid*60000, e:d0+we*60000});
  return out;
}
function offLabel(x){ return `${STATUSES[x.st].label} (${x.half}, ${minToHM(x.a)}–${minToHM(x.b)})`; }
function dayOff(k){ return offIntervals(k).length===2; }               // whole working day off
function offOverlap(k,s,e){ return offIntervals(k).filter(x=>s<x.e && x.s<e); }
function offAt(ms){ return offIntervals(key(new Date(ms))).find(x=>ms>=x.s && ms<x.e) || null; }
function flightWindows(k){
  return (day(k).flights||[]).map((f,i)=>{
    const {a,b}=flightMins(f), tz=flightTz(f);
    const s=zonedToMs(k+'T'+minToHM(a),tz), e=b>=1440?zonedToMs(keyAdd(k,1)+'T00:00',tz):zonedToMs(k+'T'+minToHM(b),tz);
    return {s, e, f, k, tz, i};
  });
}

/* ============================================================
   Private (Personal) items: AES-GCM, key from a passphrase (PBKDF2)
   ============================================================ */
const PRIV_COLS = ['projects','events','tasks','templates','trash'];
const b64 = buf => btoa(String.fromCharCode(...new Uint8Array(buf)));
const unb64 = s => Uint8Array.from(atob(s), c=>c.charCodeAt(0));
async function deriveKey(pass, salt, iter){
  const base = await crypto.subtle.importKey('raw', new TextEncoder().encode(pass), 'PBKDF2', false, ['deriveKey']);
  return crypto.subtle.deriveKey({name:'PBKDF2', salt, iterations:iter, hash:'SHA-256'}, base, {name:'AES-GCM', length:256}, false, ['encrypt','decrypt']);
}
async function encPriv(key, payload, salt, iter){
  const iv = crypto.getRandomValues(new Uint8Array(12));
  const ct = await crypto.subtle.encrypt({name:'AES-GCM', iv}, key, new TextEncoder().encode(JSON.stringify(payload)));
  return {v:1, salt, iter, iv:b64(iv), ct:b64(ct)};
}
async function decPriv(key, blob){
  const pt = await crypto.subtle.decrypt({name:'AES-GCM', iv:unb64(blob.iv)}, key, unb64(blob.ct));
  return JSON.parse(new TextDecoder().decode(pt));
}
/* remember the (non-extractable) key on this device */
function idb(){ return new Promise((res,rej)=>{ const r=indexedDB.open('wtc-keys',1); r.onupgradeneeded=()=>r.result.createObjectStore('k'); r.onsuccess=()=>res(r.result); r.onerror=()=>rej(r.error); }); }
async function keyStore(id, val){ try{ const db=await idb(); await new Promise((res,rej)=>{ const tx=db.transaction('k','readwrite'); val===undefined?tx.objectStore('k').delete(id):tx.objectStore('k').put(val,id); tx.oncomplete=res; tx.onerror=()=>rej(tx.error); }); }catch(e){} }
async function keyLoad(id){ try{ const db=await idb(); return await new Promise((res)=>{ const tx=db.transaction('k'); const r=tx.objectStore('k').get(id); r.onsuccess=()=>res(r.result||null); r.onerror=()=>res(null); }); }catch(e){ return null; } }
function keyId(){ return 'k:'+(session?session.user.toLowerCase():'-')+':'+(data.private?data.private.salt:''); }

function emptyPriv(){ const o={}; PRIV_COLS.forEach(c=>o[c]=[]); return o; }
function privPart(){ // the private items currently in memory
  const o=emptyPriv();
  o.projects=data.projects.filter(p=>isPrivCal(p.calendarId)||p.private);
  o.events=data.events.filter(isPrivate); o.tasks=data.tasks.filter(isPrivate);
  o.templates=data.templates.filter(t=>t.private); o.trash=data.trash.filter(x=>isPrivate(x.item||{}));
  return o;
}
function mergePriv(p){
  for(const c of PRIV_COLS){
    const have=new Set(data[c].map(x=>x.id));
    for(const x of arr(p[c])) if(!have.has(x.id)) data[c].push(x);
  }
  normalizeItems();
}
function normalizeItems(){
  for(const e of data.events){ e.exceptions=obj(e.exceptions); e.reminders=arr(e.reminders); e.tags=arr(e.tags); e.attendees=arr(e.attendees); e.links=arr(e.links); e.linkedTaskIds=arr(e.linkedTaskIds); }
  for(const t of data.tasks){ t.blocks=arr(t.blocks); t.checklist=arr(t.checklist); t.reminders=arr(t.reminders); t.tags=arr(t.tags); t.dependsOn=arr(t.dependsOn); t.files=arr(t.files); }
  for(const p of data.projects) p.files=arr(p.files);
}
async function tryAutoUnlock(){
  if(!data.private){ privLocked=false; return; }
  privLocked = true;
  const k = await keyLoad(keyId());
  if(k){ try{ const p=await decPriv(k, data.private); privKey=k; privLocked=false; basePriv=clone(p); mergePriv(p); }catch(e){ keyStore(keyId()); } }
}
async function unlockPriv(pass, remember){
  const blob=data.private;
  const k=await deriveKey(pass, unb64(blob.salt), blob.iter||250000);
  let p; try{ p=await decPriv(k, blob); }catch(e){ throw new Error('That passphrase is not correct.'); }
  privKey=k; privLocked=false; basePriv=clone(p); mergePriv(p);
  if(remember) await keyStore(keyId(), k);
  invalidateCaches();
}
async function setPassphrase(pass, remember){
  const salt=crypto.getRandomValues(new Uint8Array(16));
  const k=await deriveKey(pass, salt, 250000);
  if(data.private && privLocked) throw new Error('Unlock Personal items first.');
  const old=keyId();
  privKey=k; privLocked=false;
  data.private = await encPriv(k, privPart(), b64(salt), 250000);
  await keyStore(old);
  if(remember) await keyStore(keyId(), k);
}
async function lockPriv(){
  await keyStore(keyId());
  if(!data.private) return;
  // drop private items from memory
  for(const c of PRIV_COLS){
    if(c==='projects') data.projects=data.projects.filter(p=>!(isPrivCal(p.calendarId)||p.private));
    else if(c==='templates') data.templates=data.templates.filter(t=>!t.private);
    else if(c==='trash') data.trash=data.trash.filter(x=>!isPrivate(x.item||{}));
    else data[c]=data[c].filter(x=>!isPrivate(x));
  }
  privKey=null; privLocked=true; invalidateCaches();
}
function needsPrivSetup(){ return !data.private && !privKey; }

/* What goes to the server: public items in the clear, private ones encrypted */
async function serialize(){
  const d = clone(data);
  delete d.busy;
  if(privKey){
    const p = privPart();
    const ids = new Set([...p.events,...p.tasks,...p.projects,...p.templates].map(x=>x.id));
    d.projects=d.projects.filter(x=>!ids.has(x.id)); d.events=d.events.filter(x=>!ids.has(x.id));
    d.tasks=d.tasks.filter(x=>!ids.has(x.id)); d.templates=d.templates.filter(x=>!ids.has(x.id));
    d.trash=d.trash.filter(x=>!isPrivate(x.item||{}));
    const salt = data.private ? data.private.salt : b64(crypto.getRandomValues(new Uint8Array(16)));
    d.private = await encPriv(privKey, p, salt, data.private?data.private.iter:250000);
    data.private = d.private;
    d.busy = S().showBusy ? computeBusy(p) : [];
  } else {
    // locked (or never set up): keep the encrypted block and busy list exactly as loaded
    d.busy = arr(data.busy);
  }
  return d;
}
function computeBusy(p){
  const from=NOW()-60*864e5, to=NOW()+400*864e5, out=[];
  for(const e of p.events){ if(e.showAs==='free' || e.status==='cancelled') continue; for(const o of expandEvent(e, from, to)){ if(!o.cancelled) out.push(o.allDay?{d:o.dateKey,e2:o.endKey}:{s:fromDisp(o.s),e:fromDisp(o.e)}); } }
  for(const t of p.tasks) for(const b of t.blocks) { const s=zonedToMs(b.start,b.tz), e=zonedToMs(b.end,b.tz); if(e>from && s<to) out.push({s:fromDisp(s),e:fromDisp(e)}); }
  return out.slice(0,3000);
}

/* ============================================================
   Recurrence (RFC 5545 subset) and occurrences
   rrule: {freq:'daily'|'weekly'|'monthly'|'yearly', interval, byDay:[0..6 Mon=0],
           monthMode:'date'|'nth', nth:1..4|-1, count, until:'YYYY-MM-DD'}
   ============================================================ */
function ukey(t){ const d=new Date(t); return d.getUTCFullYear()+'-'+String(d.getUTCMonth()+1).padStart(2,'0')+'-'+String(d.getUTCDate()).padStart(2,'0'); }
function uparse(k){ const [y,m,d]=k.split('-').map(Number); return Date.UTC(y,m-1,d); }
function nthWeekday(y, m, wd, n){ // wd 0=Mon; n 1..5 or -1
  if(n>0){ const first=new Date(Date.UTC(y,m,1)); const fw=(first.getUTCDay()+6)%7; const day=1+((wd-fw+7)%7)+(n-1)*7; const dim=new Date(Date.UTC(y,m+1,0)).getUTCDate(); return day<=dim?Date.UTC(y,m,day):null; }
  const last=new Date(Date.UTC(y,m+1,0)); const lw=(last.getUTCDay()+6)%7; return Date.UTC(y,m,last.getUTCDate()-((lw-wd+7)%7));
}
/* Dates (YYYY-MM-DD) of a series, from its first date, up to toKey (inclusive) */
function seriesDates(startKey, rr, toKey, limit=4000){
  const out=[]; if(!rr) return [startKey];
  const iv=Math.max(1,rr.interval||1), s=uparse(startKey), until=rr.until||null, maxN=rr.count||Infinity;
  const end = until && until<toKey ? until : toKey;
  const push=k=>{ if(k<startKey) return true; if(k>end || out.length>=maxN) return false; out.push(k); return out.length<limit; };
  const sd=new Date(s), sy=sd.getUTCFullYear(), sm=sd.getUTCMonth(), sdd=sd.getUTCDate(), swd=(sd.getUTCDay()+6)%7;
  if(rr.freq==='daily'){ for(let t=s;;t+=iv*864e5){ if(!push(ukey(t))) break; } }
  else if(rr.freq==='weekly'){
    const days=(rr.byDay&&rr.byDay.length?[...rr.byDay]:[swd]).sort((a,b)=>a-b);
    const mon=s-swd*864e5;
    outer: for(let w=0;;w+=iv){ for(const d of days){ const t=mon+(w*7+d)*864e5; if(t<s) continue; if(!push(ukey(t))) break outer; } if(mon+w*7*864e5>uparse(end)) break; }
  }
  else if(rr.freq==='monthly'){
    const nth = rr.nth || (Math.ceil(sdd/7)>4?-1:Math.ceil(sdd/7));
    for(let k=0;k<2400;k+=iv){
      const y=sy+Math.floor((sm+k)/12), m=(sm+k)%12;
      let t; if(rr.monthMode==='nth') t=nthWeekday(y,m,swd,nth); else { t=Date.UTC(y,m,sdd); if(new Date(t).getUTCMonth()!==m) t=null; }
      if(t===null) continue; const kk=ukey(t); if(kk>end) break; if(!push(kk)) break;
    }
  }
  else if(rr.freq==='yearly'){
    for(let k=0;k<200;k+=iv){ const t=Date.UTC(sy+k,sm,sdd); if(new Date(t).getUTCMonth()!==sm) continue; const kk=ukey(t); if(kk>end) break; if(!push(kk)) break; }
  }
  return out;
}
function nextSeriesDate(startKey, rr, afterKey){
  const ds=seriesDates(startKey, rr, keyAdd(afterKey, 800), 5000);
  return ds.find(k=>k>afterKey) || null;
}
function rrText(rr){
  if(!rr) return '';
  const iv=rr.interval>1?rr.interval:0;
  let t='';
  if(rr.freq==='daily') t=iv?`Every ${iv} days`:'Daily';
  if(rr.freq==='weekly'){ const d=(rr.byDay||[]).slice().sort(); t=(d.join()==='0,1,2,3,4'&&!iv)?'Every weekday':(iv?`Every ${iv} weeks`:'Weekly')+(d.length?' on '+d.map(x=>DOW3[x]).join(', '):''); }
  if(rr.freq==='monthly') t=(iv?`Every ${iv} months`:'Monthly')+(rr.monthMode==='nth'?' ('+(rr.nth===-1?'last':['','1st','2nd','3rd','4th'][rr.nth||1])+' weekday)':'');
  if(rr.freq==='yearly') t=iv?`Every ${iv} years`:'Yearly';
  if(rr.count) t+=`, ${rr.count} times`; if(rr.until) t+=`, until ${fmtDMY(fromKey(rr.until))}`;
  return t;
}

/* Expand one event into occurrences overlapping [fromMs, toMs) */
function expandEvent(e, fromMs, toMs){
  const out=[];
  if(e.allDay){
    const sk=e.start.slice(0,10), ek=(e.end||e.start).slice(0,10), span=Math.round((uparse(ek)-uparse(sk))/864e5);
    const fromK=key(new Date(fromMs-864e5*(span+1))), toK=key(new Date(toMs));
    const dates = e.rrule ? seriesDates(sk, e.rrule, toK) : [sk];
    for(const dk of dates){
      const ex=e.exceptions[dk]; if(ex && ex.deleted) continue;
      let s=dk, en=keyAdd(dk,span);
      if(ex && ex.start){ s=ex.start.slice(0,10); en=(ex.end||ex.start).slice(0,10); }
      if(en<fromK) continue; if(s>toK) continue;
      const sMs=fromKey(s).getTime(), eMs=addDays(fromKey(en),1).getTime();
      if(eMs<=fromMs || sMs>=toMs) continue;
      out.push({kind:'event', item:e, id:e.id, occ:dk, allDay:true, dateKey:s, endKey:en, s:sMs, e:eMs, title:(ex&&ex.title)||e.title, cancelled:e.status==='cancelled'||!!(ex&&ex.status==='cancelled'), ex:!!ex});
    }
    return out;
  }
  const dur = Math.max(5, localDiffMin(e.start, e.end||localAddMin(e.start,60)));
  const tz=e.tz||BROWSER_TZ, time=e.start.slice(11,16);
  const toK=ukey(toMs+2*864e5);
  const dates = e.rrule ? seriesDates(e.start.slice(0,10), e.rrule, toK) : [e.start.slice(0,10)];
  for(const dk of dates){
    const ex=e.exceptions[dk]; if(ex && ex.deleted) continue;
    let sl=dk+'T'+time, el=localAddMin(sl,dur);
    if(ex && ex.start){ sl=ex.start; el=ex.end||localAddMin(sl,dur); }
    const s=zonedToMs(sl,tz), en=zonedToMs(el,tz);
    if(en<=fromMs || s>=toMs) continue;
    out.push({kind:'event', item:e, id:e.id, occ:dk, allDay:false, s, e:en, title:(ex&&ex.title)||e.title, location:(ex&&ex.location)||e.location, cancelled:e.status==='cancelled'||!!(ex&&ex.status==='cancelled'), ex:!!ex});
  }
  return out;
}
function blockOccs(t, fromMs, toMs){
  const out=[];
  for(const b of t.blocks){
    const s=zonedToMs(b.start,b.tz||BROWSER_TZ), e=zonedToMs(b.end,b.tz||BROWSER_TZ);
    if(e<=fromMs || s>=toMs) continue;
    out.push({kind:'block', item:t, id:t.id, block:b, occ:b.id, allDay:false, s, e, title:t.title, done:!!b.done||t.status==='done', missed:!b.done && t.status!=='done' && t.status!=='cancelled' && e<NOW()});
  }
  return out;
}
function dueDisp(t){ // {k, time} of the due date in the active zone
  if(!t.dueDate) return null; if(!t.dueTime) return {k:t.dueDate, time:null};
  const ms=dueMs(t); return {k:key(new Date(ms)), time:hm(ms)};
}
function dueMs(t){ if(!t.dueDate) return null; return zonedToMs(t.dueDate+'T'+(t.dueTime||'23:59'), t.tz||S().homeTz||BROWSER_TZ); }
function isOpen(t){ return t.status!=='done' && t.status!=='cancelled'; }
function isOverdue(t){ const d=dueMs(t); return isOpen(t) && d!==null && d<NOW(); }
function plannedMin(t){ return t.blocks.reduce((a,b)=>a+Math.max(0,localDiffMin(b.start,b.end)),0); }
function spentMin(t){ return t.blocks.reduce((a,b)=>a+(b.done?(b.actualMin!=null?b.actualMin:localDiffMin(b.start,b.end)):0),0); }
function futurePlannedMin(t){ return t.blocks.reduce((a,b)=>a+(!b.done && zonedToMs(b.end,b.tz)>NOW()?localDiffMin(b.start,b.end):0),0); }
function remainingMin(t){ return Math.max(0,(t.estimateMin||0)-spentMin(t)); }
function underScheduled(t){
  if(!isOpen(t) || !t.estimateMin || !t.dueDate) return false;
  const d=dueMs(t); if(d-NOW()>3*864e5) return false;
  return spentMin(t)+futurePlannedMin(t) < t.estimateMin;
}
function blockedBy(t){ return t.dependsOn.map(task).filter(x=>x && isOpen(x)); }

/* ---------- filters ---------- */
function passFilter(it){
  if(filt.hiddenCals.includes(it.calendarId)) return false;
  if(filt.projects.length){ const tokn = it.projectId || ('inbox:'+it.calendarId); if(!filt.projects.includes(tokn)){ const p=proj(it.projectId); if(!(p && p.parentId && filt.projects.includes(p.parentId))) return false; } }
  if(filt.tag && !(it.tags||[]).some(t=>t.toLowerCase()===filt.tag.toLowerCase())) return false;
  return true;
}
/* All occurrences in a time range, filtered for display */
const _occCache = new Map();
function occurrences(fromMs, toMs, opts={}){
  const ck = fromMs+'|'+toMs+'|'+(opts.all?1:0)+'|'+changeSeq+'|'+JSON.stringify(filt)+'|'+(privLocked?1:0);
  if(!opts.noCache && _occCache.has(ck)) return _occCache.get(ck);
  const out=[];
  for(const e of data.events){
    if(!opts.all && !passFilter(e)) continue;
    for(const o of expandEvent(e, fromMs, toMs)){ if(o.cancelled && !opts.all && !filt.showCancelled) continue; out.push(o); }
  }
  if(opts.all || filt.showBlocks) for(const t of data.tasks){
    if(!opts.all && !passFilter(t)) continue;
    if(!opts.all && !filt.showDone && !isOpen(t)) continue;
    out.push(...blockOccs(t, fromMs, toMs));
  }
  if(privLocked && !opts.all && !filt.hiddenCals.some(isPrivCal)){
    for(const b of arr(data.busy)){
      if(b.d){ const s=fromKey(b.d).getTime(), e=addDays(fromKey(b.e2||b.d),1).getTime(); if(e>fromMs && s<toMs) out.push({kind:'busy', allDay:true, dateKey:b.d, endKey:b.e2||b.d, s, e, title:'Busy', id:'busy'}); }
      else { const bs=toDisp(b.s), be=toDisp(b.e); if(be>fromMs && bs<toMs) out.push({kind:'busy', allDay:false, s:bs, e:be, title:'Busy', id:'busy'}); }
    }
  }
  out.sort((a,b)=>a.s-b.s || (b.e-b.s)-(a.e-a.s));
  if(_occCache.size>60) _occCache.clear();
  _occCache.set(ck,out);
  return out;
}
function dueTasksOn(k, opts={}){
  return data.tasks.filter(t=>t.dueDate && dueDisp(t).k===k && (opts.all || passFilter(t)) && (opts.all || filt.showDone || isOpen(t)));
}
/* Conflicts: overlapping busy items; items during flights; work items on leave/sick days */
function conflictsIn(occs){
  const busy=occs.filter(o=>!o.allDay && o.kind!=='busy' && !o.cancelled && !o.done && !(o.kind==='event' && ['free'].includes(o.item.showAs)));
  const bad=new Map();
  const add=(o,why)=>{ const k=o.id+'|'+o.occ; if(!bad.has(k)) bad.set(k,[]); bad.get(k).push(why); };
  for(let i=0;i<busy.length;i++) for(let j=i+1;j<busy.length;j++){
    const a=busy[i], b=busy[j]; if(b.s>=a.e) break;
    if(b.s<a.e && a.s<b.e){ add(a,'Overlaps “'+b.title+'”'); add(b,'Overlaps “'+a.title+'”'); }
  }
  for(const o of busy){
    const k=key(new Date(o.s));
    for(const w of [...flightWindows(keyAdd(k,-1)),...flightWindows(k),...flightWindows(keyAdd(k,1))]) if(o.s<w.e && w.s<o.e) add(o,'During flight '+code(w.f.from)+'→'+code(w.f.to));
    if(o.item && o.item.calendarId==='work') for(const x of offOverlap(k,o.s,o.e)) add(o,'During '+offLabel(x));
  }
  return bad;
}
