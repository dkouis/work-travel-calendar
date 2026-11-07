"use strict";
/* ============================================================
   Work & Travel Calendar — v2 (planner)
   index.php shows the app; api.php (unchanged) signs people in and
   stores the calendar JSON in data/. Everything else runs here.
   Data: v1 fields (days, monthNotes, owner) are kept exactly as they
   were. v2 adds settings, calendars, projects, events, tasks, templates,
   trash, a "private" block (Personal items, encrypted in the browser
   with a passphrase) and "busy" (free/busy times of private items).
   ============================================================ */

const STATUSES = {
  office:  {label:'Workplace',       short:'Workplace'},
  tele:    {label:'Telework',        short:'Telework'},
  abroad:  {label:'Telework abroad', short:'TW abroad'},
  leave:   {label:'Leave',           short:'Leave'},
  sick:    {label:'Sick',            short:'Sick'},
  holiday: {label:'Public holiday',  short:'Holiday'},
};
const FLIGHT_TIMES = {morning:'Morning', noon:'Noon', night:'Night'};
/* default flight hours and alarm (minutes of the day, local time of the departure city);
   each flight can override its departure, arrival and alarm times */
const FLIGHT_WINDOWS = {morning:[8*60,12*60], noon:[16*60,20*60], night:[20*60,24*60]};
const FLIGHT_ALARM = {morning:6*60, noon:14*60, night:17*60+30};
function flightKind(f){ return f.time==='evening' ? 'noon' : (FLIGHT_WINDOWS[f.time] ? f.time : 'morning'); } // v1 "evening" = Noon 16–20
function flightTz(f){ return CITY_TZ[(f.from||'').trim().toLowerCase()] || (data&&data.settings&&data.settings.homeTz) || BROWSER_TZ; }
function flightAlarmMin(f){
  if(f.alarm==='off') return null;
  const a=hmToMin(f.alarm||''); if(a!==null) return a;
  const k=flightKind(f), d=FLIGHT_WINDOWS[k], dep=hmToMin(f.dep||'');
  return dep!==null ? Math.max(0, dep-(d[0]-FLIGHT_ALARM[k])) : FLIGHT_ALARM[k];
}
function flightMins(f){
  const d=FLIGHT_WINDOWS[flightKind(f)];
  let a=hmToMin(f.dep||''), b=hmToMin(f.arr||'');
  const custom = a!==null || b!==null;
  if(a===null) a=d[0]; if(b===null) b=(hmToMin(f.dep||'')!==null)?Math.min(24*60,a+(d[1]-d[0])):d[1];
  if(b<=a) b=24*60;
  return {a, b, custom};
}
function flightTimeLabel(f){ const m=flightMins(f); return minToHM(m.a)+'–'+(m.b>=1440?'24:00':minToHM(m.b)); }
function flightLabel(f){ return FLIGHT_TIMES[flightKind(f)]+' '+flightTimeLabel(f); }
const CITY_CODES = {athens:'ATH', brussels:'BRU'};
const CITY_TZ = {athens:'Europe/Athens', brussels:'Europe/Brussels', london:'Europe/London', paris:'Europe/Paris', luxembourg:'Europe/Luxembourg',
  strasbourg:'Europe/Paris', berlin:'Europe/Berlin', rome:'Europe/Rome', madrid:'Europe/Madrid', lisbon:'Europe/Lisbon', vienna:'Europe/Vienna',
  amsterdam:'Europe/Amsterdam', thessaloniki:'Europe/Athens', nicosia:'Asia/Nicosia', 'new york':'America/New_York', geneva:'Europe/Zurich'};
const DOW = ['MON','TUE','WED','THU','FRI','SAT','SUN'];
const DOW3 = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
const DAY_NAMES = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const API = 'api.php';
const POLL_MS = 60000;
const SCHEMA = 2;
const APP_VERSION = '2.10.0';   // see CHANGELOG.md
const CATEGORIES = {meeting:'👥 Meeting', appointment:'📌 Appointment', deadline:'⏰ Deadline', travel:'🧳 Travel', social:'🎉 Social', other:'• Other'};
const CAT_ICON = {meeting:'👥', appointment:'📌', deadline:'⏰', travel:'🧳', social:'🎉', other:''};
const TASK_STATUS = {todo:'To do', 'in-progress':'In progress', waiting:'Waiting', done:'Done', cancelled:'Cancelled'};
const PROJ_STATUS = {active:'Active', 'on-hold':'On hold', completed:'Completed', archived:'Archived'};
const SHOW_AS = {busy:'Busy', free:'Free', tentative:'Tentative', ooo:'Out of office'};
const PROJ_ICONS = ['📁','💼','🏛','✈','🏠','📚','🧾','⚖','🛠','❤','🎯','🌍','📝','🎓','💡'];
const PALETTE = ['#3b5bdb','#2f9e44','#7048e8','#e8590c','#c92a2a','#0b7285','#d6336c','#5c940d','#9c36b5','#1971c2','#f08c00','#495057'];
const OFFSETS = [[0,'At the time'],[5,'5 minutes before'],[10,'10 minutes before'],[15,'15 minutes before'],[30,'30 minutes before'],[60,'1 hour before'],[120,'2 hours before'],[1440,'1 day before'],[2880,'2 days before'],[10080,'1 week before']];
const BROWSER_TZ = (()=>{ try{ return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'; }catch(e){ return 'UTC'; } })();
const TZ_LIST = [...new Set(['Europe/Athens','Europe/Brussels','Europe/London','Europe/Paris','Europe/Berlin','Europe/Luxembourg','Europe/Lisbon','Asia/Nicosia','America/New_York','UTC',BROWSER_TZ])];

/* ---------- small utils ---------- */
function safeGet(k){ try{ return localStorage.getItem(k); }catch(e){ return null; } }
function safeSet(k,v){ try{ localStorage.setItem(k,v); }catch(e){} }
function safeDel(k){ try{ localStorage.removeItem(k); }catch(e){} }
function jget(k,def){ try{ const v=safeGet(k); return v?JSON.parse(v):def; }catch(e){ return def; } }
function jset(k,v){ safeSet(k, JSON.stringify(v)); }
function startOfDay(d){ return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }
function addDays(d,n){ return new Date(d.getFullYear(), d.getMonth(), d.getDate()+n); }
function key(d){ return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }
function fromKey(k){ const [y,m,d]=k.split('-').map(Number); return new Date(y,m-1,d); }
function keyAdd(k,n){ return key(addDays(fromKey(k),n)); }
function dow(d){ return (d.getDay()+6)%7; } // 0 = Monday
function dowKey(k){ return dow(fromKey(k)); }
function isWeekend(d){ return dow(d)>=5; }
function mondayOf(d){ return addDays(d,-dow(d)); }
function isoWeek(d){ const t=new Date(Date.UTC(d.getFullYear(),d.getMonth(),d.getDate())); const n=t.getUTCDay()||7; t.setUTCDate(t.getUTCDate()+4-n); const y0=new Date(Date.UTC(t.getUTCFullYear(),0,1)); return Math.ceil(((t-y0)/864e5+1)/7); }
function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function code(city){ const c=CITY_CODES[(city||'').trim().toLowerCase()]; return c || (city||'?'); }
function fmtLong(d){ return d.toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'}); }
function fmtShort(d){ return d.toLocaleDateString('en-GB',{weekday:'short',day:'numeric',month:'short'}); }
function fmtDM(d){ return d.getDate()+' '+MONTHS[d.getMonth()].slice(0,3); }
function fmtDMY(d){ return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+d.getFullYear(); }
function fmtWhen(iso){ if(!iso) return ''; const d=new Date(iso); return isNaN(d)?'':d.toLocaleString('en-GB',{day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}); }
function hm(ms){ const d=new Date(ms); return String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0'); }
function minToHM(m){ m=Math.round(m); return String(Math.floor(m/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0'); }
function hmToMin(s){ if(!s) return null; const m=/^(\d{1,2}):(\d{2})$/.exec(s.trim()); return m? (+m[1])*60+(+m[2]) : null; }
function fmtDur(min){ min=Math.round(min||0); if(min<60) return min+' min'; const h=Math.floor(min/60), m=min%60; return h+' h'+(m?' '+m+' min':''); }
function fmtH(min){ const h=(min||0)/60; return (Math.round(h*10)/10).toString().replace(/\.0$/,'')+' h'; }
function $(s,el=document){ return el.querySelector(s); }
function $$(s,el=document){ return [...el.querySelectorAll(s)]; }
function uid(p){ return (p||'')+(crypto.randomUUID ? crypto.randomUUID().replace(/-/g,'').slice(0,12) : Math.random().toString(36).slice(2,14)); }
function nowISO(){ return new Date().toISOString(); }
function clone(o){ return o==null ? o : JSON.parse(JSON.stringify(o)); }
function clamp(v,a,b){ return Math.max(a,Math.min(b,v)); }
function debounce(fn,ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; }
function obj(v){ return (v && typeof v==='object' && !Array.isArray(v)) ? v : {}; }   // PHP turns {} into []
function arr(v){ return Array.isArray(v) ? v : []; }
function download(name, text, type){
  const a=document.createElement('a'); a.href=URL.createObjectURL(text instanceof Blob?text:new Blob([text],{type})); a.download=name;
  document.body.appendChild(a); a.click(); setTimeout(()=>{ URL.revokeObjectURL(a.href); a.remove(); },500);
}
function contrastText(hex){
  const m=/^#?([0-9a-f]{6})$/i.exec(hex||''); if(!m) return '#fff';
  const n=parseInt(m[1],16), r=n>>16, g=(n>>8)&255, b=n&255;
  return (r*299+g*587+b*114)/1000 > 150 ? '#1d1f24' : '#fff';
}

/* ---------- time zones ----------
   Timed items store local wall-clock time ("2026-11-04T10:00") plus the IANA
   time zone they were entered in. The screen shows everything in the ACTIVE
   time zone (top-bar 🌍 menu). Internally all times are "display" ms: a
   timestamp shifted so this browser's local clock reads the active zone's
   wall time. Switching the active zone moves every item to its local time. */
let ACTIVE_TZ = BROWSER_TZ;
const _dtf = {};
function dtf(tz){
  return _dtf[tz] || (_dtf[tz] = new Intl.DateTimeFormat('en-US',{timeZone:tz,hourCycle:'h23',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit'}));
}
function validTz(tz){ try{ dtf(tz); return true; }catch(e){ return false; } }
const _offCache=new Map();
function tzOffsetMin(tz, ms){
  if(!tz || tz===BROWSER_TZ) return -new Date(ms).getTimezoneOffset();
  const ck=tz+'|'+Math.floor(ms/900000); const c=_offCache.get(ck); if(c!==undefined) return c;
  const v=tzOffsetMinRaw(tz,ms); if(_offCache.size>20000) _offCache.clear(); _offCache.set(ck,v); return v;
}
function tzOffsetMinRaw(tz, ms){
  const p={}; for(const x of dtf(tz).formatToParts(new Date(ms))) p[x.type]=x.value;
  const asUTC=Date.UTC(+p.year,+p.month-1,+p.day,+p.hour%24,+p.minute,+p.second);
  return Math.round((asUTC - (ms - ms%1000))/60000);
}
function parseLocal(local){ const m=/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?/.exec(local||''); return m ? {y:+m[1],mo:+m[2],d:+m[3],h:+(m[4]||0),mi:+(m[5]||0)} : null; }
function toDisp(t){ return ACTIVE_TZ===BROWSER_TZ ? t : t+(tzOffsetMin(ACTIVE_TZ,t)-tzOffsetMin(BROWSER_TZ,t))*60000; }
function fromDisp(d){
  if(ACTIVE_TZ===BROWSER_TZ) return d;
  let t=d-(tzOffsetMin(ACTIVE_TZ,d)-tzOffsetMin(BROWSER_TZ,d))*60000;
  t=d-(tzOffsetMin(ACTIVE_TZ,t)-tzOffsetMin(BROWSER_TZ,t))*60000; return t;
}
function NOW(){ return toDisp(Date.now()); }
function TODAY(){ return startOfDay(new Date(NOW())); }
function isoDay(iso){ return key(new Date(toDisp(Date.parse(iso)))); }
/* wall time in tz → display ms, and back */
function zonedToMs(local, tz){ return toDisp(zonedToTrue(local, tz)); }
function msToZoned(ms, tz){ return trueToZoned(fromDisp(ms), tz); }
/* convert a wall time from one zone to another */
function tzConvert(local, fromTz, toTz){ return (!local || fromTz===toTz) ? local : msToZoned(zonedToMs(local, fromTz), toTz); }
function tzOffsetLabel(tz, ms){ const o=tzOffsetMin(tz, ms==null?Date.now():ms); return 'UTC'+(o<0?'−':'+')+String(Math.floor(Math.abs(o)/60)).padStart(2,'0')+':'+String(Math.abs(o)%60).padStart(2,'0'); }
function zonedToTrue(local, tz){
  const p=parseLocal(local); if(!p) return NaN;
  if(!tz || tz===BROWSER_TZ || !validTz(tz)) return new Date(p.y,p.mo-1,p.d,p.h,p.mi).getTime();
  const guess=Date.UTC(p.y,p.mo-1,p.d,p.h,p.mi);
  let ms=guess-tzOffsetMin(tz,guess)*60000;
  const o2=tzOffsetMin(tz,ms); ms=guess-o2*60000;
  return ms;
}
function trueToZoned(ms, tz){
  if(!tz || tz===BROWSER_TZ || !validTz(tz)){ const d=new Date(ms); return key(d)+'T'+hm(ms); }
  const p={}; for(const x of dtf(tz).formatToParts(new Date(ms))) p[x.type]=x.value;
  return `${p.year}-${p.month}-${p.day}T${String(+p.hour%24).padStart(2,'0')}:${p.minute}`;
}
function localAddMin(local, min){ // wall-clock arithmetic (no zone)
  const p=parseLocal(local); const d=new Date(Date.UTC(p.y,p.mo-1,p.d,p.h,p.mi)+min*60000);
  return d.getUTCFullYear()+'-'+String(d.getUTCMonth()+1).padStart(2,'0')+'-'+String(d.getUTCDate()).padStart(2,'0')+'T'+String(d.getUTCHours()).padStart(2,'0')+':'+String(d.getUTCMinutes()).padStart(2,'0');
}
function localDiffMin(a,b){ const p=parseLocal(a), q=parseLocal(b); return (Date.UTC(q.y,q.mo-1,q.d,q.h,q.mi)-Date.UTC(p.y,p.mo-1,p.d,p.h,p.mi))/60000; }
function tzShort(tz){ if(!tz) return ''; const c=tz.split('/').pop().replace(/_/g,' '); return c; }

/* ---------- state ---------- */
let data = null;            // the calendar (normalized, private items merged in when unlocked)
let view = safeGet('wtc-view') || (innerWidth<600 ? 'agenda' : 'week');
let weekCompact = safeGet('wtc-weekc')==='1';
let cursor = startOfDay(new Date());
let editable = false;
let session = null;         // {user, role}
let token = '';
let rev = 0;
let lastSaved = {by:'', saved:''};
let selected = new Set();   // selected day keys (bulk edit of work status)
let selTasks = new Set();   // selected task ids (bulk task actions)
let selItem = null;         // last clicked item {kind,id,occ}
let lastClicked = null;
let openDay = null;
let saveTimer = null;
let dirty = false, changeSeq = 0, saving = false, conflict = null, netError = false;
let base = null;            // last data as on the server (for merging)
let basePriv = null;        // last decrypted private part (for merging)
let mergeNote = null;
let privKey = null;         // CryptoKey for the Personal (private) part, when unlocked
let privLocked = false;     // a private block exists but is not unlocked here
let archive = null;         // archive file opened for this session (read-only)
let offlineMode = false;
let sizeWarn = false;
let prefs = {};             // per browser + user preferences
let filt = null;            // view filters (per browser + user)
let tpTab = 'today';        // tasks panel list
let undoStack = [], redoStack = [], lastSnap = null, lastSnapAt = 0, lastSnapLabel = '';

const DEFAULT_SETTINGS = {
  workStart:'08:30', midday:'13:00', workEnd:'17:30', dayHalves:1, workDays:[0,1,2,3,4], homeTz:'Europe/Athens',
  defaultEventRem:[{offsetMin:15,channel:'popup'}], defaultDueRem:[{offsetMin:1440,channel:'popup'}], defaultBlockRem:[{offsetMin:5,channel:'popup'}],
  defaultEventMin:60, defaultBlockMin:60, showBusy:true, allDayRemTime:'09:00', travelReminders:true, archiveMonths:12
};
const DEFAULT_PREFS = {
  hourPx:44, density:'comfortable', colorBy:'project', hideWeekends:false, showLegend:true, sidePanel:true, tasksPanel:true,
  desktop:false, sound:true, quietStart:'22:00', quietEnd:'07:30', digestTime:'08:30', digest:true,
  reviewDay:4, reviewTime:'16:00', review:true, eod:false, eodTime:'18:00', snooze:[5,10,30,60,'tomorrow'],
  activeTz:'', openDetails:'tab', openEditors:'tab', offlineCopy:false, tpSort:'due', tpGroup:'none', savedFilters:[]
};
const DEFAULT_FILTER = {hiddenCals:[], projects:[], showDone:true, showCancelled:true, showBlocks:true, showDue:true, showFlights:true, showStatus:true, tag:''};
function prefKey(){ return 'wtc-prefs:'+(session?session.user.toLowerCase():'-'); }
function loadPrefs(){ prefs=Object.assign({},DEFAULT_PREFS,jget(prefKey(),{})); ACTIVE_TZ=(prefs.activeTz&&validTz(prefs.activeTz))?prefs.activeTz:BROWSER_TZ; cursor=TODAY(); filt=Object.assign({},DEFAULT_FILTER,jget('wtc-filter:'+(session?session.user.toLowerCase():'-'),{})); applyPrefsCss(); }
function savePrefs(){ jset(prefKey(),prefs); applyPrefsCss(); }
function saveFilter(){ jset('wtc-filter:'+(session?session.user.toLowerCase():'-'),filt); }
function applyPrefsCss(){
  document.documentElement.style.setProperty('--hour', (prefs.density==='compact'?Math.round(prefs.hourPx*.75):prefs.hourPx)+'px');
  document.body.classList.toggle('noside', !prefs.sidePanel);
  document.body.classList.toggle('notasks', !prefs.tasksPanel);
}
function S(){ return data.settings; }
