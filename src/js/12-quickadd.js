/* ============================================================
   Quick add (natural language, English + Greek)
   #project  @location  !calendar  ~estimate  p1–p4  *repeat
   "by"/"μέχρι"/"έως" = due date of a task
   ============================================================ */
const fold1 = c => { const f=c.normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase(); return f.length===1?f:(f[0]||' '); };
const WB = p => `(?<![\\p{L}\\p{N}])(?:${p})(?![\\p{L}\\p{N}])`;
const DAY_WORDS = [
  ['monday|mon|δευτερα|δευ'], ['tuesday|tues|tue|τριτη|τρι'], ['wednesday|wed|τεταρτη|τετ'], ['thursday|thurs|thu|πεμπτη|πεμ'],
  ['friday|fri|παρασκευη|παρ'], ['saturday|sat|σαββατο|σαβ'], ['sunday|sun|κυριακη|κυρ']];
const MONTH_WORDS = ['jan|january|ιαν','feb|february|φεβ','mar|march|μαρ','apr|april|απρ','may|μαι|μαΐ','jun|june|ιουν','jul|july|ιουλ','aug|august|αυγ','sep|sept|september|σεπ','oct|october|οκτ','nov|november|νοε','dec|december|δεκ'];
const MONTH_RE = MONTH_WORDS.map(m=>WB(m.split('|').map(x=>/[a-z]/.test(x)?x:x+'[\\p{L}]*').join('|')));

function parseQuick(text){
  let s=text.normalize('NFC'); let f=[...s].map(fold1).join('');
  const r={title:'', type:null, date:null, dateFromBy:false, start:null, end:null, projectId:null, calendarId:null, location:'', priority:null, estimateMin:null, rrule:null, tags:[], notes:[]};
  const blank=(a,b)=>{ s=s.slice(0,a)+' '.repeat(b-a)+s.slice(b); f=f.slice(0,a)+' '.repeat(b-a)+f.slice(b); };
  const take=(re, fn)=>{ re.lastIndex=0; let m; const found=[]; while((m=re.exec(f))){ found.push(m); if(!re.global) break; }
    for(const m2 of found.reverse()){ const orig=s.slice(m2.index, m2.index+m2[0].length); if(fn(m2, orig)!==false) blank(m2.index, m2.index+m2[0].length); } };
  const today=TODAY();
  // explicit type
  take(/^\s*(task|todo|εργασια)\s*:?\s+/iu, ()=>{ r.type='task'; });
  take(/^\s*(event|γεγονος|ραντεβου)\s*:\s*/iu, ()=>{ r.type='event'; });
  // quoted or single-word project / location / calendar
  take(/#"([^"]+)"|#([\p{L}\p{N}_\-.]+)/gu, (m,o)=>{ const name=(m[1]||m[2]); const q=name.replace(/[\s_\-]/g,''); const raw=o.replace(/^#"?|"$/g,'');
    const p=data.projects.find(p=>[...p.name].map(fold1).join('').replace(/[\s_\-]/g,'')===q) || data.projects.find(p=>[...p.name].map(fold1).join('').replace(/[\s_\-]/g,'').startsWith(q));
    if(p){ r.projectId=p.id; if(!r.calendarId) r.calendarId=p.calendarId; } else r.tags.push(raw.replace(/^"|"$/g,'')); });
  take(/@"([^"]+)"|@([\p{L}\p{N}_\-.]+)/gu, (m,o)=>{ r.location=(o.match(/@"([^"]+)"/)||[])[1]||o.slice(1); });
  take(/!([\p{L}]+)/gu, m=>{ const w=m[1]; const c=data.calendars.find(c=>[...c.name].map(fold1).join('').startsWith(w)) || (/^(δουλ|εργ|work)/.test(w)?cal('work'):/^(προσ|pers|priv)/.test(w)?data.calendars.find(c=>c.id==='personal'):null); if(!c) return false; r.calendarId=c.id; });
  take(/~\s*(\d+(?:[.,]\d+)?)\s*(h|ω|ωρ[\p{L}]*|hours?)?\s*(\d+)?\s*(m|min|λ|λεπτ[\p{L}]*)?/gu, m=>{ const n=parseFloat(m[1].replace(',','.')); r.estimateMin = m[2] ? Math.round(n*60)+(+m[3]||0) : (m[4]?Math.round(n):Math.round(n<=12&&!m[4]?n*60:n)); });
  take(new RegExp(WB('p([1-4])'),'gu'), m=>{ r.priority=+m[1]; });
  take(/\*([\p{L}]+)/gu, m=>{ const w=m[1];
    if(/^(daily|καθημεριν)/.test(w)) r.rrule={freq:'daily',interval:1};
    else if(/^(weekdays|εργασιμ)/.test(w)) r.rrule={freq:'weekly',interval:1,byDay:[0,1,2,3,4]};
    else if(/^(weekly|εβδομαδιαι|καθε εβδομαδα)/.test(w)) r.rrule={freq:'weekly',interval:1};
    else if(/^(biweekly)/.test(w)) r.rrule={freq:'weekly',interval:2};
    else if(/^(monthly|μηνιαι)/.test(w)) r.rrule={freq:'monthly',interval:1};
    else if(/^(yearly|annual|ετησι)/.test(w)) r.rrule={freq:'yearly',interval:1};
    else return false; });
  // "by" (task due) marker — remembered, removed with its date below
  const byRe = WB('by|due|until|μεχρι|εως|ως|προθεσμια');
  // dates
  const setDate=(k)=>{ if(!r.date) r.date=k; };
  const dateTok = (re, fn) => take(new RegExp('(?:('+byRe+')\\s+)?(?:(?:on|την|τη|στις)\\s+)?'+re,'giu'), (m,o)=>{ const k=fn(m); if(!k) return false; if(r.date) return false; setDate(k); if(m[1]) r.dateFromBy=true; });
  dateTok('(\\d{4})-(\\d{1,2})-(\\d{1,2})', m=>`${m[2]}-${m[3].padStart(2,'0')}-${m[4].padStart(2,'0')}`);
  dateTok('(?<![\\d:~])(\\d{1,2})[\\/.](\\d{1,2})(?:[\\/.](\\d{2,4}))?(?![\\d:])', m=>{ const d=+m[2], mo=+m[3]; if(mo<1||mo>12||d<1||d>31) return null; let y=m[4]?(m[4].length===2?2000+(+m[4]):+m[4]):today.getFullYear(); let k=`${y}-${String(mo).padStart(2,'0')}-${String(d).padStart(2,'0')}`; if(!m[4] && k<key(today)) k=`${y+1}-${k.slice(5)}`; return k; });
  for(let i=0;i<12;i++){
    dateTok(`(\\d{1,2})\\s+(?:${MONTH_RE[i]})(?:\\s+(\\d{4}))?`, m=>{ let y=m[3]?+m[3]:today.getFullYear(); let k=`${y}-${String(i+1).padStart(2,'0')}-${String(+m[2]).padStart(2,'0')}`; if(!m[3]&&k<key(today)) k=`${y+1}-${k.slice(5)}`; return k; });
    dateTok(`(?:${MONTH_RE[i]})\\s+(\\d{1,2})(?![:\\d])`, m=>{ let y=today.getFullYear(); let k=`${y}-${String(i+1).padStart(2,'0')}-${String(+m[2]).padStart(2,'0')}`; if(k<key(today)) k=`${y+1}-${k.slice(5)}`; return k; });
  }
  dateTok(WB('today|tonight|σημερα|αποψε'), ()=>key(today));
  dateTok(WB('day after tomorrow|μεθαυριο'), ()=>key(addDays(today,2)));
  dateTok(WB('tomorrow|tmrw|αυριο'), ()=>key(addDays(today,1)));
  dateTok('(?:in|σε)\\s+(\\d{1,3})\\s+(?:days?|μερες|ημερες|μερα)', m=>key(addDays(today,+m[2])));
  dateTok('(?:in|σε)\\s+(\\d{1,2})\\s+(?:weeks?|εβδομαδες|εβδομαδα)', m=>key(addDays(today,7*(+m[2]))));
  DAY_WORDS.forEach(([w],i)=>{
    dateTok('(next|this|επομενη|επομενο|αλλη|αυτη|αυτο)?\\s*'+WB(w), m=>{ let k=addDays(today,(i-dow(today)+7)%7); if(m[2] && /next|επομεν|αλλη/.test(m[2])) k=addDays(k,7); return key(k); });
  });
  // times
  const ampm=(h,suf)=>{ suf=(suf||'').replace(/\./g,''); if(/pm|μμ/.test(suf) && h<12) return h+12; if(/am|πμ/.test(suf) && h===12) return 0; return h; };
  take(/(?:(?:from|απο|στις|at)\s+)?(?<![\d\/.\-~])(\d{1,2})(?:[:.](\d{2}))?\s*(am|pm|h|π\.?μ\.?|μ\.?μ\.?)?\s*(?:-|–|to|μεχρι|εως)\s*(\d{1,2})(?:[:.](\d{2}))?\s*(am|pm|h|π\.?μ\.?|μ\.?μ\.?)?(?![\d\/])/iu, m=>{
    let h1=+m[1], h2=+m[4]; if(h1>24||h2>24) return false; const suf2=m[6]||'', suf1=m[3]||suf2;
    h1=ampm(h1,suf1); h2=ampm(h2,suf2);
    const a=h1*60+(+m[2]||0), b=h2*60+(+m[5]||0); if(b<=a) return false; r.start=a; r.end=b; });
  if(r.start===null) take(/(?:(at|στις|@)\s+)?(?<![\d\/.\-~])(\d{1,2})(?:([:.])(\d{2}))?\s*(am|pm|h|π\.?μ\.?|μ\.?μ\.?)?(?![\d\/\p{L}])/iu, m=>{
    if(!m[1] && !m[3] && !m[5]) return false; const h=ampm(+m[2],m[5]||''); if(h>23) return false; r.start=h*60+(+m[4]||0); });
  // leftover "by" right before a time (e.g. "by 17:00")
  take(new RegExp(byRe,'iu'), ()=>{ r.dateFromBy=true; });
  // title
  r.title=s.replace(/\s+/g,' ').trim().replace(/^(on|at|in|the|την|στις)\s+/i,'').replace(/\s+(on|at|in|for|στις|την)$/i,'').replace(/[,;:\-–]+$/,'').trim();
  // decide type
  if(!r.type){ r.type = (r.dateFromBy || r.estimateMin || r.priority) ? 'task' : (r.start!==null ? 'event' : 'task'); }
  if(r.type==='event' && !r.date) r.date=key(today);
  if(r.rrule && r.rrule.freq==='weekly' && !r.rrule.byDay && r.date) r.rrule.byDay=[dowKey(r.date)];
  if(r.rrule && r.type==='task' && !r.date) r.date=key(today);
  if(r.location && !r.calendarId) r.calendarId='work';
  if(!r.calendarId) r.calendarId='work';
  return r;
}
function quickToItem(q){
  const tz = ACTIVE_TZ;   // entered in the active time zone
  if(q.type==='event'){
    const allDay=q.start===null;
    const e=newEvent({title:q.title||'New event', calendarId:q.calendarId, projectId:q.projectId, location:q.location, tags:q.tags, rrule:q.rrule, allDay, tz});
    if(allDay){ e.start=q.date; e.end=q.date; delete e.tz; }
    else { e.start=q.date+'T'+minToHM(q.start); e.end=q.end!==null?q.date+'T'+minToHM(q.end):localAddMin(e.start,S().defaultEventMin||60); }
    if(q.priority||q.estimateMin) e.notes='';
    return e;
  }
  const t=newTask({title:q.title||'New task', calendarId:q.calendarId, projectId:q.projectId, tags:q.tags, priority:q.priority||3, estimateMin:q.estimateMin, rrule:q.rrule});
  if(q.date){ t.dueDate=q.date; if(q.rrule) t.rrStart=q.date; }
  if(q.start!==null && (q.dateFromBy || q.end===null)) t.dueTime=minToHM(q.start);
  else if(q.start!==null && q.end!==null){ const k=q.date||todayKey(); t.blocks=[{id:uid('b'), start:k+'T'+minToHM(q.start), end:k+'T'+minToHM(q.end), tz, done:false}]; if(!t.estimateMin) t.estimateMin=q.end-q.start; }
  if(q.location) t.notes='📍 '+q.location;
  return t;
}
let qaOverride=null;
function quickPreviewHTML(q){
  const p=proj(q.projectId), c=cal(q.calendarId);
  const when = q.type==='event'
    ? (q.date?fmtShort(fromKey(q.date)):'')+(q.start!==null?' '+minToHM(q.start)+(q.end!==null?'–'+minToHM(q.end):''):' (all day)')
    : (q.date?'due '+fmtShort(fromKey(q.date))+(q.start!==null&&(q.dateFromBy||q.end===null)?' '+minToHM(q.start):''):'no due date')+(q.start!==null&&q.end!==null&&!q.dateFromBy?' · block '+minToHM(q.start)+'–'+minToHM(q.end):'');
  return `<div><b>${esc(q.title||'(no title)')}</b></div><div class="qrow">
    <button type="button" class="tag k" data-qtype title="Switch between event and task">${q.type==='event'?'📅 Event':'☑ Task'} ⇄</button>
    <span class="tag">🕒 ${esc(when)}</span><span class="tag"><i class="dotc" style="background:${esc(c.color)}"></i>${esc(c.name)}</span>
    ${p?`<span class="tag">${esc(p.icon||'')} ${esc(p.name)}</span>`:''}${q.location?`<span class="tag">📍 ${esc(q.location)}</span>`:''}
    ${q.priority?`<span class="tag">P${q.priority}</span>`:''}${q.estimateMin?`<span class="tag">⏱ ${fmtDur(q.estimateMin)}</span>`:''}${q.rrule?`<span class="tag">🔁 ${esc(rrText(q.rrule))}</span>`:''}
    ${q.tags.map(t=>`<span class="tag">#${esc(t)}</span>`).join('')}</div>
    <div class="hint" style="margin-top:6px"><kbd>Enter</kbd> create · <kbd>Ctrl</kbd>+<kbd>Enter</kbd> open full editor · <kbd>Esc</kbd> cancel</div>`;
}
function bindQuickAdd(){
  const inp=$('#qa'), pv=$('#qaPrev');
  const parse=()=>{ const q=parseQuick(inp.value); if(qaOverride) q.type=qaOverride; if(q.type==='event'&&!q.date) q.date=todayKey(); return q; };
  const upd=()=>{
    if(!inp.value.trim() || !editable){ pv.classList.remove('open'); return; }
    pv.innerHTML=quickPreviewHTML(parse()); pv.classList.add('open');
    const tb=$('[data-qtype]',pv); tb.onmousedown=e=>{ e.preventDefault(); const q=parse(); qaOverride=q.type==='event'?'task':'event'; upd(); };
  };
  inp.addEventListener('input',()=>{ if(!inp.value.trim()) qaOverride=null; upd(); });
  inp.addEventListener('focus',upd);
  inp.addEventListener('blur',()=>setTimeout(()=>{ if(!inp.value.trim()) pv.classList.remove('open'); },150));
  inp.addEventListener('keydown',e=>{
    if(e.key==='Escape'){ inp.value=''; qaOverride=null; pv.classList.remove('open'); inp.blur(); $('#addMenu').classList.remove('open'); return; }
    if(e.key!=='Enter' || !inp.value.trim() || !editable) return;
    e.preventDefault();
    const it=quickToItem(parse());
    const full=e.ctrlKey||e.metaKey;
    inp.value=''; qaOverride=null; pv.classList.remove('open'); $('#addMenu').classList.remove('open');
    if(full) return it.type==='event'?openEventEditor(null,it):openTaskEditor(null,it);
    if(!saveNewItem(it)) return;
    toast(`${it.type==='event'?'📅':'☑'} Created “${it.title}”`,{actions:[['Undo',undo],['Edit',()=>it.type==='event'?openEventEditor(it.id):openTaskEditor(it.id)]]});
  });
}
