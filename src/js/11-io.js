/* ============================================================
   .ics export/import, CSV import, backup, archive, templates,
   privacy dialogs, settings, accounts, history
   ============================================================ */
/* ---------- .ics export ---------- */
const icsEsc=s=>String(s||'').replace(/\\/g,'\\\\').replace(/\n/g,'\\n').replace(/,/g,'\\,').replace(/;/g,'\\;');
const icsFold=l=>{ const out=[]; let s=l; while(s.length>73){ out.push(s.slice(0,73)); s=' '+s.slice(73); } out.push(s); return out.join('\r\n'); };
const icsLocal=l=>l.replace(/[-:]/g,'').slice(0,13)+'00';
const icsDate=k=>k.replace(/-/g,'');
const icsUTC=ms=>new Date(fromDisp(ms)).toISOString().replace(/[-:]/g,'').replace(/\.\d{3}/,'');
function vtimezone(tz){
  const y=new Date().getFullYear(), jan=tzOffsetMin(tz,Date.UTC(y,0,15)), jul=tzOffsetMin(tz,Date.UTC(y,6,15));
  const off=m=>(m<0?'-':'+')+String(Math.floor(Math.abs(m)/60)).padStart(2,'0')+String(Math.abs(m)%60).padStart(2,'0');
  if(jan===jul) return ['BEGIN:VTIMEZONE','TZID:'+tz,'BEGIN:STANDARD','DTSTART:19700101T000000','TZOFFSETFROM:'+off(jan),'TZOFFSETTO:'+off(jan),'END:STANDARD','END:VTIMEZONE'];
  const std=Math.min(jan,jul), dst=Math.max(jan,jul), hh=m=>String(Math.floor((60+m)/60)%24).padStart(2,'0');
  // EU rule (change at 01:00 UTC on the last Sunday of March / October)
  return ['BEGIN:VTIMEZONE','TZID:'+tz,'BEGIN:DAYLIGHT','DTSTART:19700329T'+hh(std)+'0000','RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU','TZOFFSETFROM:'+off(std),'TZOFFSETTO:'+off(dst),'END:DAYLIGHT',
    'BEGIN:STANDARD','DTSTART:19701025T'+hh(dst)+'0000','RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU','TZOFFSETFROM:'+off(dst),'TZOFFSETTO:'+off(std),'END:STANDARD','END:VTIMEZONE'];
}
function rruleStr(rr, allDay, tz){
  const p=['FREQ='+rr.freq.toUpperCase()]; if(rr.interval>1) p.push('INTERVAL='+rr.interval);
  const D=['MO','TU','WE','TH','FR','SA','SU'];
  if(rr.freq==='weekly' && rr.byDay&&rr.byDay.length) p.push('BYDAY='+rr.byDay.map(d=>D[d]).join(','));
  if(rr.count) p.push('COUNT='+rr.count);
  if(rr.until) p.push('UNTIL='+(allDay?icsDate(rr.until):icsUTC(zonedToMs(rr.until+'T23:59',tz||BROWSER_TZ))));
  return p;
}
function valarm(r, title){ return ['BEGIN:VALARM','ACTION:DISPLAY','DESCRIPTION:'+icsEsc(title),'TRIGGER:-PT'+(+r.offsetMin||0)+'M','END:VALARM']; }
function exportIcs(o={}){
  const L=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Work & Travel Calendar//EN','CALSCALE:GREGORIAN','X-WR-CALNAME:'+icsEsc(o.name||data.owner||'Work & Travel Calendar')];
  const tzs=new Set(), body=[];
  const f=o.filter||(()=>true);
  const from=o.from?fromKey(o.from).getTime():-1e14, to=o.to?addDays(fromKey(o.to),1).getTime():1e14;
  const evs=(o.items||data.events.filter(e=>(privKey||!isPrivate(e)) && f(e))).filter(e=>e.type==='event');
  const tks=(o.items?o.items.filter(x=>x.type==='task'):data.tasks.filter(t=>(privKey||!isPrivate(t)) && f(t)));
  const stamp=icsUTC(NOW());
  const dt=(name,local,tz,allDay)=> allDay ? `${name};VALUE=DATE:${icsDate(local.slice(0,10))}` : `${name};TZID=${tz}:${icsLocal(local)}`;
  for(const e of evs){
    if(!o.items && !(e.rrule || expandEvent(e,from,to).length)) continue;
    if(o.items===undefined && e.rrule && !expandEvent(e,from,to).length) continue;
    const tz=e.tz||BROWSER_TZ; if(!e.allDay) tzs.add(tz);
    const ev=['BEGIN:VEVENT','UID:'+(e.uid||e.id+'@wtc'),'DTSTAMP:'+stamp,'SUMMARY:'+icsEsc(e.title)];
    if(e.allDay){ ev.push(dt('DTSTART',e.start,tz,true), 'DTEND;VALUE=DATE:'+icsDate(keyAdd((e.end||e.start).slice(0,10),1))); }
    else ev.push(dt('DTSTART',e.start,tz), dt('DTEND',e.end,tz));
    if(e.location) ev.push('LOCATION:'+icsEsc(e.location));
    const desc=[e.notes, e.onlineLink, ...(e.links||[]).map(l=>l.label+': '+l.url)].filter(Boolean).join('\n'); if(desc) ev.push('DESCRIPTION:'+icsEsc(desc));
    if(e.onlineLink) ev.push('URL:'+e.onlineLink);
    if(e.status==='cancelled') ev.push('STATUS:CANCELLED');
    if(e.showAs==='free') ev.push('TRANSP:TRANSPARENT');
    const cats=[cal(e.calendarId).name, proj(e.projectId)&&proj(e.projectId).name, ...e.tags].filter(Boolean); ev.push('CATEGORIES:'+cats.map(icsEsc).join(','));
    if(e.rrule){ ev.push('RRULE:'+rruleStr(e.rrule,e.allDay,tz).join(';'));
      const ex=Object.entries(e.exceptions).filter(([k,x])=>x.deleted).map(([k])=>e.allDay?icsDate(k):icsLocal(k+'T'+e.start.slice(11,16)));
      if(ex.length) ev.push((e.allDay?'EXDATE;VALUE=DATE:':'EXDATE;TZID='+tz+':')+ex.join(',')); }
    for(const r of e.reminders) ev.push(...valarm(r,e.title));
    ev.push('END:VEVENT'); body.push(...ev);
    // changed occurrences
    for(const [k,x] of Object.entries(e.exceptions)){ if(x.deleted) continue;
      const s=x.start||(e.allDay?k:k+'T'+e.start.slice(11,16)), en=x.end||(e.allDay?keyAdd(k,Math.round((uparse(e.end.slice(0,10))-uparse(e.start.slice(0,10)))/864e5)):localAddMin(s,localDiffMin(e.start,e.end)));
      const ov=['BEGIN:VEVENT','UID:'+(e.uid||e.id+'@wtc'),'DTSTAMP:'+stamp,(e.allDay?'RECURRENCE-ID;VALUE=DATE:'+icsDate(k):'RECURRENCE-ID;TZID='+tz+':'+icsLocal(k+'T'+e.start.slice(11,16))),'SUMMARY:'+icsEsc(x.title||e.title)];
      if(e.allDay) ov.push(dt('DTSTART',s,tz,true),'DTEND;VALUE=DATE:'+icsDate(keyAdd(en.slice(0,10),1))); else ov.push(dt('DTSTART',s,tz),dt('DTEND',en,tz));
      if(x.location||e.location) ov.push('LOCATION:'+icsEsc(x.location||e.location)); if(x.status==='cancelled') ov.push('STATUS:CANCELLED');
      ov.push('END:VEVENT'); body.push(...ov); }
  }
  for(const t of tks){
    for(const b of t.blocks){ const s=zonedToMs(b.start,b.tz); if(s<from||s>=to) continue; tzs.add(b.tz||BROWSER_TZ);
      body.push('BEGIN:VEVENT','UID:'+b.id+'@wtc','DTSTAMP:'+stamp,'SUMMARY:'+icsEsc('[Task] '+t.title),dt('DTSTART',b.start,b.tz||BROWSER_TZ),dt('DTEND',b.end,b.tz||BROWSER_TZ),...(t.notes?['DESCRIPTION:'+icsEsc(t.notes)]:[]),...t.reminders.filter(r=>r.anchor==='block').flatMap(r=>valarm(r,t.title)),'END:VEVENT'); }
    if(t.dueDate && (o.items || (fromKey(t.dueDate).getTime()>=from && fromKey(t.dueDate).getTime()<to))){
      body.push('BEGIN:VTODO','UID:'+t.id+'@wtc','DTSTAMP:'+stamp,'SUMMARY:'+icsEsc(t.title), t.dueTime?`DUE;TZID=${t.tz||S().homeTz}:${icsLocal(t.dueDate+'T'+t.dueTime)}`:'DUE;VALUE=DATE:'+icsDate(t.dueDate),'PRIORITY:'+({1:1,2:3,3:5,4:9})[t.priority||3],'STATUS:'+(t.status==='done'?'COMPLETED':t.status==='cancelled'?'CANCELLED':'NEEDS-ACTION'),...t.reminders.filter(r=>r.anchor==='due').flatMap(r=>valarm(r,t.title)),'END:VTODO');
      if(t.dueTime) tzs.add(t.tz||S().homeTz); }
  }
  if(o.flights){ for(const k of flightKeys()){ if(fromKey(k).getTime()<from||fromKey(k).getTime()>=to) continue; for(const w of flightWindows(k)) body.push('BEGIN:VEVENT','UID:fl-'+k+'-'+code(w.f.from)+code(w.f.to)+'@wtc','DTSTAMP:'+stamp,'SUMMARY:'+icsEsc('✈ '+w.f.from+' → '+w.f.to),'DTSTART:'+icsUTC(w.s),'DTEND:'+icsUTC(w.e),...(w.f.note?['DESCRIPTION:'+icsEsc(w.f.note)]:[]),'END:VEVENT'); } }
  for(const tz of tzs) if(validTz(tz)) L.push(...vtimezone(tz));
  L.push(...body,'END:VCALENDAR');
  download((o.name||'calendar').replace(/[^\w\-]+/g,'_').slice(0,60)+'.ics', L.map(icsFold).join('\r\n')+'\r\n', 'text/calendar;charset=utf-8');
}
function openIcsExport(){
  const [a,b]=visibleRange();
  const d=openDialog(`<div class="dhd"><h2>📤 Export calendar file (.ics)</h2><button class="ghost" data-close>✕</button></div>
    <p class="hint">Import the file into Outlook, Google Calendar or your phone. Reminders are included, so they also alert there when this app is closed.</p>
    <form class="frm"><label>From</label><input type="date" name="f" value="${key(a)}"><label>To</label><input type="date" name="t" value="${key(addDays(b,-1))}">
      <label>Calendar</label><select name="c"><option value="">All</option>${data.calendars.map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('')}</select>
      <label>Project</label><select name="p"><option value="">All</option>${data.projects.map(p=>`<option value="${p.id}">${esc(p.name)}</option>`).join('')}</select>
      <label></label><label class="hint"><input type="checkbox" name="fl"> Include flights</label></form>
    <div class="dacts"><button data-q="all">Everything</button><span class="sp"></span><button data-close>Cancel</button><button class="primary" data-go>Download</button></div>`,{size:'narrow'});
  const F=$('form',d), v=n=>$(`[name=${n}]`,F).value;
  const go=all=>{ const c=v('c'), p=v('p'); exportIcs({from:all?null:v('f'), to:all?null:v('t'), name:'calendar-'+(all?'all':v('f')+'_'+v('t')), filter:it=>(!c||it.calendarId===c)&&(!p||it.projectId===p), flights:$('[name=fl]',F).checked}); closeDialog(d); };
  $('[data-go]',d).onclick=()=>go(false); $('[data-q]',d).onclick=()=>go(true);
}

/* ---------- .ics import ---------- */
const WIN_TZ={'GTB Standard Time':'Europe/Athens','Romance Standard Time':'Europe/Brussels','W. Europe Standard Time':'Europe/Berlin','GMT Standard Time':'Europe/London','UTC':'UTC','Central Europe Standard Time':'Europe/Budapest','Eastern Standard Time':'America/New_York','FLE Standard Time':'Europe/Helsinki','E. Europe Standard Time':'Europe/Chisinau'};
function parseIcs(text){
  const lines=text.replace(/\r\n[ \t]/g,'').replace(/\n[ \t]/g,'').split(/\r?\n/);
  const comps=[]; let cur=null, stack=[];
  for(const ln of lines){
    if(/^BEGIN:/i.test(ln)){ const t=ln.slice(6).toUpperCase(); const c={type:t,props:[],children:[]}; if(cur) cur.children.push(c); stack.push(cur); cur=c; if(t==='VEVENT'||t==='VTODO') comps.push(c); continue; }
    if(/^END:/i.test(ln)){ cur=stack.pop(); continue; }
    if(!cur) continue;
    const i=ln.indexOf(':'); if(i<0) continue;
    const left=ln.slice(0,i), val=ln.slice(i+1); const [name,...params]=left.split(';');
    const pm={}; params.forEach(p=>{ const [k,v]=p.split('='); pm[k.toUpperCase()]=(v||'').replace(/^"|"$/g,''); });
    cur.props.push({name:name.toUpperCase(), params:pm, val});
  }
  const un=s=>s.replace(/\\n/gi,'\n').replace(/\\,/g,',').replace(/\\;/g,';').replace(/\\\\/g,'\\');
  const get=(c,n)=>c.props.find(p=>p.name===n);
  const toLocal=p=>{ // → {allDay, local, tz}
    if(!p) return null; const v=p.val;
    if(p.params.VALUE==='DATE' || /^\d{8}$/.test(v)) return {allDay:true, local:v.slice(0,4)+'-'+v.slice(4,6)+'-'+v.slice(6,8)};
    const m=/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})?(Z)?$/.exec(v); if(!m) return null;
    const loc=`${m[1]}-${m[2]}-${m[3]}T${m[4]}:${m[5]}`;
    if(m[7]){ const tz=S().homeTz||BROWSER_TZ; return {allDay:false, local:msToZoned(toDisp(Date.UTC(+m[1],m[2]-1,+m[3],+m[4],+m[5])),tz), tz}; }
    let tz=p.params.TZID||S().homeTz||BROWSER_TZ; tz=WIN_TZ[tz]||tz; if(!validTz(tz)) tz=S().homeTz||BROWSER_TZ;
    return {allDay:false, local:loc, tz};
  };
  const out=[];
  const D={MO:0,TU:1,WE:2,TH:3,FR:4,SA:5,SU:6};
  const overrides=[];
  for(const c of comps){
    const uidp=get(c,'UID'), sum=get(c,'SUMMARY'), st=toLocal(get(c,'DTSTART')), en=toLocal(get(c,'DTEND')), rid=get(c,'RECURRENCE-ID');
    const dm=/^P(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:\d+S)?)?$/.exec(((get(c,'DURATION')||{}).val||'').trim());
    const durP = dm ? (+dm[1]||0)*10080+(+dm[2]||0)*1440+(+dm[3]||0)*60+(+dm[4]||0) : 0;  // DURATION (Google, Apple) when there is no DTEND
    const alarms=c.children.filter(x=>x.type==='VALARM').map(a=>{ const t=(a.props.find(p=>p.name==='TRIGGER')||{}).val||''; const m=/-?P(?:(\d+)W)?(?:(\d+)D)?T?(?:(\d+)H)?(?:(\d+)M)?/.exec(t); return m?{offsetMin:(+m[1]||0)*10080+(+m[2]||0)*1440+(+m[3]||0)*60+(+m[4]||0), channel:'popup'}:null; }).filter(Boolean);
    if(c.type==='VTODO'){ const due=toLocal(get(c,'DUE')); out.push({kind:'task', uid:uidp&&uidp.val, title:sum?un(sum.val):'(no title)', dueDate:due?due.local.slice(0,10):null, dueTime:due&&!due.allDay?due.local.slice(11,16):null, notes:get(c,'DESCRIPTION')?un(get(c,'DESCRIPTION').val):'', done:/COMPLETED/i.test((get(c,'STATUS')||{}).val||''), reminders:alarms.map(a=>({anchor:'due',...a}))}); continue; }
    if(!st) continue;
    const e={kind:'event', uid:uidp&&uidp.val, title:sum?un(sum.val):'(no title)', allDay:st.allDay, start:st.local, tz:st.tz,
      end: en ? (st.allDay ? keyAdd(en.local.slice(0,10),-1) : en.local)
              : durP ? (st.allDay ? keyAdd(st.local.slice(0,10), Math.max(0,Math.round(durP/1440)-1)) : localAddMin(st.local, durP))
              : (st.allDay?st.local:localAddMin(st.local,60)),
      location:get(c,'LOCATION')?un(get(c,'LOCATION').val):'', notes:get(c,'DESCRIPTION')?un(get(c,'DESCRIPTION').val):'', cancelled:/CANCELLED/i.test((get(c,'STATUS')||{}).val||''), reminders:alarms, exceptions:{}, rrule:null};
    if(en && !st.allDay && en.tz && en.tz!==st.tz) e.end=msToZoned(zonedToMs(en.local,en.tz),st.tz);
    const rr=get(c,'RRULE');
    if(rr){ const p=Object.fromEntries(rr.val.split(';').map(x=>x.split('='))); const f=(p.FREQ||'').toLowerCase();
      if(['daily','weekly','monthly','yearly'].includes(f)){ e.rrule={freq:f, interval:+p.INTERVAL||1};
        if(p.BYDAY){ const days=p.BYDAY.split(',');
          if(f==='weekly') e.rrule.byDay=days.map(x=>D[x.slice(-2)]).filter(x=>x!=null);
          if(f==='monthly'){ const m=/^(-?\d)(\w\w)$/.exec(days[0]); if(m){ e.rrule.monthMode='nth'; e.rrule.nth=+m[1]; } } }
        if(p.COUNT) e.rrule.count=+p.COUNT;
        if(p.UNTIL){ const u=p.UNTIL; e.rrule.until=u.slice(0,4)+'-'+u.slice(4,6)+'-'+u.slice(6,8); } } }
    for(const exd of c.props.filter(p=>p.name==='EXDATE')) for(const v of exd.val.split(',')){ const l=toLocal({val:v,params:exd.params}); if(l) e.exceptions[l.local.slice(0,10)]={deleted:true}; }
    if(rid){ const r=toLocal(rid); overrides.push({uid:e.uid, k:r?r.local.slice(0,10):null, e}); continue; }
    out.push(e);
  }
  for(const o of overrides){ const m=out.find(x=>x.uid===o.uid && x.rrule); if(m && o.k) m.exceptions[o.k]={start:o.e.start, end:o.e.end, ...(o.e.title!==m.title?{title:o.e.title}:{}), ...(o.e.location&&o.e.location!==m.location?{location:o.e.location}:{}), ...(o.e.cancelled?{status:'cancelled'}:{})}; else out.push(o.e); }
  return out;
}
async function importIcsFile(file){
  const items=parseIcs(await file.text());
  if(!items.length) return alert('No events or tasks found in that file.');
  const known=new Set([...data.events,...data.tasks].map(x=>x.uid).filter(Boolean));
  const d=openDialog(`<div class="dhd"><h2>📥 Import “${esc(file.name)}”</h2><button class="ghost" data-close>✕</button></div>
    <form class="frm"><label>Calendar</label><select name="calendarId">${calOptions('work')}</select><label>Project</label><select name="projectId">${projOptions('work',null)}</select></form>
    <div class="hint" style="margin:8px 0">${items.length} item(s). Items already imported before (same UID) are unticked.</div>
    <div style="max-height:46vh;overflow:auto"><table class="rt"><thead><tr><th><input type="checkbox" data-all checked aria-label="All"></th><th>Type</th><th>When</th><th>Title</th></tr></thead><tbody>
    ${items.map((x,i)=>`<tr><td><input type="checkbox" data-i="${i}" ${x.uid&&known.has(x.uid)?'':'checked'}></td><td>${x.kind==='task'?'Task':x.rrule?'🔁 Event':'Event'}${x.uid&&known.has(x.uid)?' <span class="tag">duplicate</span>':''}</td><td class="num">${x.kind==='task'?(x.dueDate||''):esc(x.allDay?x.start:x.start.replace('T',' '))}</td><td>${esc(x.title)}</td></tr>`).join('')}</tbody></table></div>
    <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" data-go>Import selected</button></div>`,{size:'mid'});
  const F=$('form',d); bindCalProj(F);
  $('[data-all]',d).onchange=e=>$$('[data-i]',d).forEach(c=>c.checked=e.target.checked);
  $('[data-go]',d).onclick=()=>{
    const calendarId=$('[name=calendarId]',F).value, projectId=$('[name=projectId]',F).value||null;
    if(isPrivCal(calendarId) && !ensurePrivReadySync()) return;
    let n=0;
    for(const c of $$('[data-i]:checked',d)){ const x=items[+c.dataset.i];
      if(x.kind==='task'){ const t=newTask({title:x.title, calendarId, projectId, dueDate:x.dueDate, dueTime:x.dueTime, notes:x.notes, status:x.done?'done':'todo', uid:x.uid}); if(x.reminders.length) t.reminders=x.reminders; touch(t); data.tasks.push(t); }
      else { const e=newEvent({title:x.title, calendarId, projectId, allDay:x.allDay, start:x.start, end:x.end, tz:x.tz, location:x.location, notes:x.notes, status:x.cancelled?'cancelled':'confirmed', rrule:x.rrule, exceptions:x.exceptions, uid:x.uid, category:'other'}); e.reminders=x.reminders.length?x.reminders:e.reminders; if(e.allDay) delete e.tz; touch(e); data.events.push(e); }
      n++; }
    changed(); closeDialog(d); renderAll(); toast(`Imported ${n} item${n===1?'':'s'}`,{actions:[['Undo',undo]]});
  };
}

/* ---------- CSV import of tasks ---------- */
function parseCsv(text){
  const first=text.split(/\r?\n/)[0]||''; const delim=(first.match(/;/g)||[]).length>(first.match(/,/g)||[]).length?';':(first.includes('\t')?'\t':',');
  const rows=[]; let row=[], cell='', q=false;
  for(let i=0;i<text.length;i++){ const c=text[i];
    if(q){ if(c==='"'){ if(text[i+1]==='"'){ cell+='"'; i++; } else q=false; } else cell+=c; continue; }
    if(c==='"') q=true; else if(c===delim){ row.push(cell); cell=''; } else if(c==='\n'||c==='\r'){ if(c==='\r'&&text[i+1]==='\n') i++; row.push(cell); rows.push(row); row=[]; cell=''; } else cell+=c; }
  if(cell||row.length){ row.push(cell); rows.push(row); }
  return rows.filter(r=>r.some(c=>c.trim()));
}
function parseEstimate(s){ s=String(s||'').trim().toLowerCase(); if(!s) return null; let m=/^(\d+(?:[.,]\d+)?)\s*h(?:\s*(\d+)\s*m?)?$/.exec(s); if(m) return Math.round(parseFloat(m[1].replace(',','.'))*60)+(+m[2]||0); m=/^(\d+)\s*m(in)?$/.exec(s); if(m) return +m[1]; m=/^(\d+):(\d{2})$/.exec(s); if(m) return (+m[1])*60+(+m[2]); if(/^\d+$/.test(s)) return +s; return null; }
function parseDateLoose(s){ s=String(s||'').trim(); let m=/^(\d{4})-(\d{1,2})-(\d{1,2})/.exec(s); if(m) return `${m[1]}-${m[2].padStart(2,'0')}-${m[3].padStart(2,'0')}`; m=/^(\d{1,2})[\/.](\d{1,2})[\/.](\d{2,4})/.exec(s); if(m){ const y=m[3].length===2?'20'+m[3]:m[3]; return `${y}-${m[2].padStart(2,'0')}-${m[1].padStart(2,'0')}`; } return null; }
async function importCsvFile(file){
  const rows=parseCsv((await file.text()).replace(/^\ufeff/,''));
  if(rows.length<2) return alert('The file needs a header row and at least one task.');
  const h=rows[0].map(x=>x.trim().toLowerCase());
  const col=n=>h.findIndex(x=>n.some(y=>x.startsWith(y)));
  const ci={title:col(['title','task','name','τίτλος']), due:col(['due','date','deadline']), project:col(['project']), priority:col(['prio']), estimate:col(['est','duration','hours'])};
  if(ci.title<0) return alert('Could not find a "title" column. Columns needed: title, due, project, priority, estimate.');
  const tasks=rows.slice(1).map(r=>({title:(r[ci.title]||'').trim(), due:ci.due>=0?parseDateLoose(r[ci.due]):null, project:ci.project>=0?(r[ci.project]||'').trim():'', priority:ci.priority>=0?clamp(parseInt(String(r[ci.priority]).replace(/\D/g,''))||3,1,4):3, est:ci.estimate>=0?parseEstimate(r[ci.estimate]):null})).filter(t=>t.title);
  const d=openDialog(`<div class="dhd"><h2>📥 Import tasks from “${esc(file.name)}”</h2><button class="ghost" data-close>✕</button></div>
    <div class="hint">${tasks.length} task(s). Projects are matched by name; unknown ones are created in the Work calendar.</div>
    <div style="max-height:50vh;overflow:auto;margin-top:8px"><table class="rt"><thead><tr><th>Title</th><th>Due</th><th>Project</th><th>Priority</th><th>Estimate</th></tr></thead><tbody>${tasks.map(t=>`<tr><td>${esc(t.title)}</td><td class="num">${t.due||''}</td><td>${esc(t.project)}${t.project&&!data.projects.some(p=>p.name.toLowerCase()===t.project.toLowerCase())?' <span class="tag">new</span>':''}</td><td>P${t.priority}</td><td>${t.est?fmtDur(t.est):''}</td></tr>`).join('')}</tbody></table></div>
    <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" data-go>Import</button></div>`,{size:'mid'});
  $('[data-go]',d).onclick=()=>{
    for(const x of tasks){ let p=null; if(x.project){ p=data.projects.find(q=>q.name.toLowerCase()===x.project.toLowerCase()); if(!p){ p=newProject({name:x.project}); touch(p); data.projects.push(p); } }
      const t=newTask({title:x.title, dueDate:x.due, projectId:p?p.id:null, calendarId:p?p.calendarId:'work', priority:x.priority, estimateMin:x.est}); if(isPrivate(t)&&!privKey) continue; touch(t); data.tasks.push(t); }
    changed(); closeDialog(d); renderAll(); toast(`Imported ${tasks.length} tasks`,{actions:[['Undo',undo]]});
  };
}

/* ---------- backup (export / restore with preview) ---------- */
async function exportBackup(){
  const plain = privKey && await confirmDlg({title:'Export backup', html:'Include Personal items <b>unencrypted</b> in the file? Choose “Keep encrypted” if the file may be stored where others can read it.'},[['Keep encrypted','enc','primary'],['Include unencrypted','plain'],['Cancel',null]]);
  if(privKey && !plain) return;
  let out = await serialize();
  if(plain==='plain'){ out=clone(data); delete out.private; delete out.busy; }
  download('calendar-backup-'+todayKey()+'.json', JSON.stringify(out,null,1), 'application/json');
}
function parseDataText(text){
  const json = text.replace(/^[\s\S]*?window\.CALENDAR_DATA\s*=\s*/,'').replace(/;\s*$/,'').trim();
  return normalize(json ? JSON.parse(json) : {}).d;
}
async function importBackupFile(f){
  try{
    const txt=await f.text();
    const dd = /CALENDAR_DATA/.test(txt) ? parseDataText(txt) : normalize(JSON.parse(txt)).d;
    const cnt=x=>`${Object.keys(x.days).length} days · ${x.events.length} events · ${x.tasks.length} tasks · ${x.projects.length} projects${x.private?' · encrypted Personal block':''}`;
    const d=openDialog(`<div class="dhd"><h2>Restore / import backup</h2><button class="ghost" data-close>✕</button></div>
      <table class="rt"><tbody><tr><td>Now</td><td>${cnt(data)}</td></tr><tr><td>In the file</td><td>${cnt(dd)}</td></tr></tbody></table>
      <p class="hint">“Replace” makes the calendar exactly like the file. “Add missing” keeps everything you have and adds days/items that are only in the file. User accounts are not changed; the server keeps the current version under ⋯ → Earlier versions.</p>
      <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button data-m="add">Add missing</button><button class="primary" data-m="replace">Replace</button></div>`,{size:'mid'});
    $$('[data-m]',d).forEach(b=>b.onclick=async()=>{
      closeDialog(d);
      if(b.dataset.m==='replace'){ const keepKey=privKey; data=dd; privKey=null; privLocked=!!dd.private; if(dd.private && keepKey){ try{ const p=await decPriv(keepKey,dd.private); privKey=keepKey; privLocked=false; mergePriv(p); }catch(e){} } }
      else { for(const [k,v] of Object.entries(dd.days)) if(!data.days[k]) data.days[k]=v; for(const c of ['projects','events','tasks','templates']){ const have=new Set(data[c].map(x=>x.id)); for(const x of dd[c]) if(!have.has(x.id)) data[c].push(x); } normalizeItems(); }
      changed(); renderAll(); toast('Backup loaded',{actions:[['Undo',undo]]});
    });
  }catch(err){ alert('Could not read that file: '+err.message); }
}

/* ---------- archive (old items to a file, opened on demand) ---------- */
async function archiveOld(months){
  const cut=key(addDays(TODAY(),-Math.round(months*30.4)));
  const oldT=data.tasks.filter(t=>!isOpen(t) && (t.completedAt?t.completedAt.slice(0,10):(t.dueDate||'9999'))<cut);
  const oldE=data.events.filter(e=>!e.rrule && (e.end||e.start).slice(0,10)<cut || (e.rrule && e.rrule.until && e.rrule.until<cut));
  if(!oldT.length && !oldE.length) return toast('Nothing older than that to archive.');
  if(!await confirmDlg({title:'Archive',html:`Move ${oldE.length} past events and ${oldT.length} finished tasks (before ${fmtDMY(fromKey(cut))}) to an archive file? The file downloads now; open it later from Settings → Data to include it in reports and search.`},[['Archive','y','primary'],['Cancel',null]])) return;
  const file={archive:true, created:nowISO(), before:cut, events:oldE.filter(x=>!isPrivate(x)||privKey), tasks:oldT.filter(x=>!isPrivate(x)||privKey), projects:data.projects};
  download('calendar-archive-before-'+cut+'.json', JSON.stringify(file), 'application/json');
  const ids=new Set([...oldE,...oldT].map(x=>x.id));
  data.events=data.events.filter(x=>!ids.has(x.id)); data.tasks=data.tasks.filter(x=>!ids.has(x.id));
  changed(); renderAll(); toast('Archived. Keep the downloaded file somewhere safe.');
}
async function openArchiveFile(f){ try{ const a=JSON.parse(await f.text()); if(!a.archive) throw new Error('This is not an archive file.'); archive={events:arr(a.events), tasks:arr(a.tasks), name:f.name}; normalizeArchive(); toast(`Archive “${f.name}” opened for this session (reports and search).`); }catch(e){ alert(e.message); } }
function normalizeArchive(){ for(const e of archive.events){ e.exceptions=obj(e.exceptions); e.reminders=arr(e.reminders); e.tags=arr(e.tags); } for(const t of archive.tasks){ t.blocks=arr(t.blocks); t.tags=arr(t.tags); t.reminders=arr(t.reminders); t.checklist=arr(t.checklist); t.dependsOn=arr(t.dependsOn); } }
function pickFile(accept, fn){ const i=$('#fileInput'); i.accept=accept; i.value=''; i.onchange=()=>{ const f=i.files[0]; if(f) fn(f); }; i.click(); }

/* ---------- templates ---------- */
function saveAsTemplate(items){
  promptDlg('Save as template','Template name', items[0].title||'',{ok:'Save'}).then(name=>{
    if(!name) return;
    const base=items.map(x=>x.type==='event'?(x.start||'').slice(0,10):(x.dueDate||(x.blocks[0]&&x.blocks[0].start.slice(0,10))||todayKey())).sort()[0];
    const tpl={id:uid('tpl'), name, created:nowISO(), private:items.some(isPrivate), items:items.map(x=>{
      if(x.type==='event'){ const off=Math.round((uparse(x.start.slice(0,10))-uparse(base))/864e5); return {kind:'event', offsetDays:off, title:x.title, allDay:x.allDay, startTime:x.allDay?null:x.start.slice(11,16), durationMin:x.allDay?null:localDiffMin(x.start,x.end), spanDays:x.allDay?Math.round((uparse(x.end.slice(0,10))-uparse(x.start.slice(0,10)))/864e5):0, tz:x.tz, category:x.category, location:x.location, notes:x.notes, reminders:x.reminders, calendarId:x.calendarId, tags:x.tags}; }
      const off=x.dueDate?Math.round((uparse(x.dueDate)-uparse(base))/864e5):0; return {kind:'task', offsetDays:off, title:x.title, estimateMin:x.estimateMin, priority:x.priority, checklist:x.checklist.map(c=>({text:c.text})), notes:x.notes, calendarId:x.calendarId, tags:x.tags, hasDue:!!x.dueDate}; })};
    data.templates.push(tpl); changed(); toast(`Template “${name}” saved`);
  });
}
function saveProjectAsTemplate(p){
  const ts=data.tasks.filter(t=>t.projectId===p.id), es=data.events.filter(e=>e.projectId===p.id && !e.rrule);
  const base=p.startDate||todayKey();
  promptDlg('Save project as template','Template name', p.name,{ok:'Save'}).then(name=>{
    if(!name) return;
    data.templates.push({id:uid('tpl'), name, created:nowISO(), private:isPrivCal(p.calendarId), project:{name:p.name, color:p.color, icon:p.icon, calendarId:p.calendarId, budgetHours:p.budgetHours, durationDays:p.dueDate?Math.round((uparse(p.dueDate)-uparse(base))/864e5):null},
      items:[...es.map(e=>({kind:'event', offsetDays:Math.round((uparse(e.start.slice(0,10))-uparse(base))/864e5), title:e.title, allDay:e.allDay, startTime:e.allDay?null:e.start.slice(11,16), durationMin:e.allDay?null:localDiffMin(e.start,e.end), spanDays:0, tz:e.tz, category:e.category, location:e.location, reminders:e.reminders})),
             ...ts.map(t=>({kind:'task', offsetDays:t.dueDate?Math.round((uparse(t.dueDate)-uparse(base))/864e5):0, hasDue:!!t.dueDate, title:t.title, estimateMin:t.estimateMin, priority:t.priority, checklist:t.checklist.map(c=>({text:c.text})), notes:t.notes}))]});
    changed(); toast(`Template “${name}” saved (${ts.length} tasks, ${es.length} events)`);
  });
}
function openTemplates(){
  const draw=()=>{
    const list=data.templates;
    dd.querySelector('.box').innerHTML=`<div class="dhd"><h2>🧩 Templates</h2><button class="ghost" data-close>✕</button></div>
      ${list.length?list.map((t,i)=>`<div class="slot" style="margin-bottom:6px"><div><b>${esc(t.name)}</b>${t.private?' 🔒':''}<div class="hint">${t.project?'Project · ':''}${t.items.map(x=>x.kind==='flight'?'✈ '+code(x.from)+'→'+code(x.to):x.kind==='event'?'📅 '+x.title:'☑ '+x.title).join(' · ')}</div></div>
        <div style="display:flex;gap:6px">${editable?`<button class="primary" data-use="${i}">Use…</button><button class="ghost danger" data-tdel="${i}" aria-label="Delete template">🗑</button>`:''}</div></div>`).join(''):'<div class="rempty">No templates yet. Use “Save as template” in an event, task or project.</div>'}
      <div class="dacts"><span class="sp"></span><button data-close>Close</button></div>`;
    const B=dd.querySelector('.box'); $$('[data-close]',B).forEach(b=>b.onclick=()=>closeDialog(dd));
    $$('[data-tdel]',B).forEach(b=>b.onclick=async()=>{ const t=list[+b.dataset.tdel]; if(!await confirmDlg(`Delete template “${t.name}”?`,[['Delete','y','danger'],['Cancel',null]])) return; data.templates.splice(+b.dataset.tdel,1); changed(); draw(); });
    $$('[data-use]',B).forEach(b=>b.onclick=()=>applyTemplate(list[+b.dataset.use]));
  };
  const dd=openDialog('',{size:'mid'}); draw();
}
function applyTemplate(tpl){
  const d=openDialog(`<div class="dhd"><h2>Use “${esc(tpl.name)}”</h2><button class="ghost" data-close>✕</button></div>
    <form class="frm"><label>Start date</label><input type="date" name="d" value="${key(cursor)}" required>
    ${tpl.project?`<label>Project name</label><input type="text" name="pn" value="${esc(tpl.project.name)}">`:`<label>Add to project</label><select name="p">${projOptions('work',null)}</select>`}</form>
    <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" data-go>Create</button></div>`,{size:'narrow'});
  $('[data-go]',d).onclick=()=>{
    const base=$('[name=d]',d).value; if(!base) return;
    let projectId=null, calendarId='work';
    if(tpl.project){ const p=newProject({name:$('[name=pn]',d).value||tpl.project.name, color:tpl.project.color, icon:tpl.project.icon, calendarId:tpl.project.calendarId||'work', budgetHours:tpl.project.budgetHours, startDate:base, dueDate:tpl.project.durationDays!=null?keyAdd(base,tpl.project.durationDays):null});
      if(isPrivCal(p.calendarId)&&!ensurePrivReadySync()) return; touch(p); data.projects.push(p); projectId=p.id; calendarId=p.calendarId; }
    else { projectId=$('[name=p]',d).value||null; if(projectId) calendarId=proj(projectId).calendarId; }
    let n=0;
    for(const x of tpl.items){
      const k=keyAdd(base,x.offsetDays||0);
      if(x.kind==='flight'){ const m=dayMut(k); (m.flights=m.flights||[]).push({from:x.from,to:x.to,time:x.time||'morning'}); n++; continue; }
      if(x.kind==='event'){ const tz=x.tz||ACTIVE_TZ; const e=newEvent({title:x.title, calendarId:x.calendarId&&!tpl.project?x.calendarId:calendarId, projectId, category:x.category||'meeting', allDay:!!x.allDay, tz, location:x.location||'', notes:x.notes||'', tags:x.tags||[]});
        if(x.allDay){ e.start=k; e.end=keyAdd(k,x.spanDays||0); delete e.tz; } else { e.start=k+'T'+(x.startTime||'09:00'); e.end=x.endTime?k+'T'+x.endTime:localAddMin(e.start,x.durationMin||60); }
        if(x.reminders) e.reminders=clone(x.reminders); if(isPrivate(e)&&!privKey) continue; touch(e); data.events.push(e); n++; continue; }
      const t=newTask({title:x.title, calendarId:x.calendarId&&!tpl.project?x.calendarId:calendarId, projectId, estimateMin:x.estimateMin||null, priority:x.priority||3, dueDate:x.hasDue===false?null:k, notes:x.notes||'', tags:x.tags||[], checklist:(x.checklist||[]).map(c=>({id:uid('c'),text:c.text,done:false}))});
      if(isPrivate(t)&&!privKey) continue; touch(t); data.tasks.push(t); n++;
    }
    changed(); closeDialog(d); closeDialog(); renderAll(); toast(`Created ${n} item${n===1?'':'s'} from “${tpl.name}”`,{actions:[['Undo',undo]]});
  };
}

/* ---------- privacy dialogs ---------- */
function openPrivSetup(){
  const d=openDialog(`<div class="dhd"><h2>🔒 Protect Personal items</h2><button class="ghost" data-close>✕</button></div>
    <p class="hint">Personal items are encrypted in your browser with a passphrase before they are saved, so viewers (and the server) cannot read them. Viewers see that time only as “Busy” (you can turn that off in Settings). Anyone you tell the passphrase to can unlock them.</p>
    <p class="hint"><b>Keep the passphrase safe:</b> it cannot be recovered. Without it, Personal items cannot be opened.</p>
    <form><label class="hint">Passphrase (at least 8 characters)</label><input type="password" id="pp1" minlength="8" required autocomplete="new-password" style="margin:4px 0 8px">
      <label class="hint">Repeat</label><input type="password" id="pp2" minlength="8" required autocomplete="new-password" style="margin:4px 0 8px">
      <label class="hint"><input type="checkbox" id="ppr" checked> Remember on this device</label><div class="lerr" id="ppe"></div>
      <div class="dacts"><span class="sp"></span><button type="button" data-close>Cancel</button><button class="primary" type="submit">Set passphrase</button></div></form>`,{size:'narrow'});
  $('form',d).onsubmit=async e=>{ e.preventDefault(); const a=$('#pp1',d).value, b=$('#pp2',d).value; if(a!==b) return $('#ppe',d).textContent='The two passphrases do not match.';
    try{ await setPassphrase(a,$('#ppr',d).checked); changed(); closeDialog(d); renderAll(); toast('Personal items are now protected.'); }catch(err){ $('#ppe',d).textContent=err.message; } };
}
function openUnlock(){
  if(!data.private) return openPrivSetup();
  const d=openDialog(`<div class="dhd"><h2>🔑 Unlock Personal items</h2><button class="ghost" data-close>✕</button></div>
    <form><input type="password" id="up" placeholder="Passphrase" required autocomplete="current-password"><label class="hint" style="display:block;margin-top:8px"><input type="checkbox" id="ur" checked> Remember on this device</label>
    <div class="lerr" id="ue"></div><div class="dacts"><span class="sp"></span><button type="button" data-close>Cancel</button><button class="primary" type="submit">Unlock</button></div></form>`,{size:'narrow'});
  $('form',d).onsubmit=async e=>{ e.preventDefault(); const btn=$('[type=submit]',d); btn.disabled=true; try{ await unlockPriv($('#up',d).value,$('#ur',d).checked); lastSnap=snapshotState(); closeDialog(d); renderAll(); toast('Personal items unlocked.'); }catch(err){ $('#ue',d).textContent=err.message; btn.disabled=false; } };
}
async function changePassphrase(){
  if(privLocked) return openUnlock();
  const r=await promptDlg('Change passphrase','New passphrase (at least 8 characters)','',{type:'password', attrs:'minlength="8" required autocomplete="new-password"', ok:'Change'});
  if(!r) return; await setPassphrase(r,true); changed(); toast('Passphrase changed. Tell anyone else who uses it.');
}

/* ---------- settings ---------- */
let setTab='general';
function openSettings(tab){
  if(tab) setTab=tab;
  const draw=()=>{
    const s=S(), ro=!editable?'disabled':'';
    const tabs=[['general','General'],['notify','Reminders & notifications'],['calendars','Calendars & privacy'],['templates','Templates'],['trash','Trash'],['data','Data']];
    let body='';
    if(setTab==='general') body=`<form class="frm" data-sf>
      <label>Work hours</label><div class="row"><input type="time" name="workStart" value="${s.workStart}" ${ro} aria-label="Work starts"> – <input type="time" name="workEnd" value="${s.workEnd}" ${ro} aria-label="Work ends"></div>
      <label>Morning / evening</label><div class="row">split at <input type="time" name="midday" value="${s.midday}" ${ro}> <span class="hint">morning ${esc(s.workStart)}–${esc(s.midday)}, evening ${esc(s.midday)}–${esc(s.workEnd)}; used for half-day leave warnings</span></div>
      <label>Flights</label><div class="hint">Default times (local to the departure city): ${Object.entries(FLIGHT_TIMES).map(([k,l])=>`${l} ${minToHM(FLIGHT_WINDOWS[k][0])}–${FLIGHT_WINDOWS[k][1]>=1440?'24:00':minToHM(FLIGHT_WINDOWS[k][1])} (alarm ${minToHM(FLIGHT_ALARM[k])})`).join(' · ')}. Change a single flight’s times or alarm in its day.</div>
      <label>Work days</label><div class="row daychips">${DOW3.map((d,i)=>`<button type="button" data-wd="${i}" class="${s.workDays.includes(i)?'on':''}" ${ro}>${d}</button>`).join('')}</div>
      <label>Home time zone</label><select name="homeTz" ${ro}>${tzOptions(s.homeTz)}</select>
      <div class="hint full">The 🌍 menu at the top sets the <b>active time zone</b> for this device. New items are entered in it, and every event, task and flight is shown in it, so when you travel and switch zone, the calendar moves to your local times. The home time zone is used for due dates without a time and for flights from unknown cities.</div>
      <label>Default lengths</label><div class="row">event <input type="number" name="defaultEventMin" min="5" step="5" value="${s.defaultEventMin}" style="width:80px" ${ro}> min · task block <input type="number" name="defaultBlockMin" min="15" step="15" value="${s.defaultBlockMin}" style="width:80px" ${ro}> min</div>
      <label>All-day reminders at</label><input type="time" name="allDayRemTime" value="${s.allDayRemTime}" ${ro}>
      <div class="sec">This device</div>
      <label>Hour height</label><div class="row"><input type="range" min="24" max="96" step="4" data-p="hourPx" value="${prefs.hourPx}"> <span class="hint">${prefs.hourPx}px</span></div>
      <label>Offline copy</label><label class="hint"><input type="checkbox" data-p="offlineCopy" ${prefs.offlineCopy?'checked':''}> Keep a copy on this device so the calendar opens without internet (read-only). Don’t use on shared computers.</label>
      <label>Install</label><div class="hint">${installHint()}</div></form>`;
    if(setTab==='notify') body=`<form class="frm" data-sf>
      <div class="sec" style="border:0;padding-top:0">Defaults for new items</div>
      <label>Events</label>${remPreview(s.defaultEventRem)}<label>Task due</label>${remPreview(s.defaultDueRem)}<label>Task blocks</label>${remPreview(s.defaultBlockRem)}
      <div class="full hint">Change these defaults with the buttons below; each item can have its own reminders.</div>
      <label></label><div class="row"><button type="button" data-edrem="defaultEventRem" ${ro}>Edit event defaults</button><button type="button" data-edrem="defaultDueRem" ${ro}>Edit due defaults</button><button type="button" data-edrem="defaultBlockRem" ${ro}>Edit block defaults</button></div>
      <label>Flight alarms</label><label class="hint"><input type="checkbox" name="travelReminders" ${s.travelReminders?'checked':''} ${ro}> Alarm before each flight (popup, desktop notification and sound), with the events around it</label>
      <div class="sec">This device</div>
      <label>Desktop</label><div class="row">${'Notification' in window?(Notification.permission==='granted'?'<span class="okbox">Allowed ✓</span>':`<button type="button" data-perm>Turn on desktop notifications</button>`):'<span class="hint">Not supported by this browser</span>'}<label class="hint"><input type="checkbox" data-p="desktop" ${prefs.desktop?'checked':''}> for every reminder</label></div>
      <label>Sound</label><label class="hint"><input type="checkbox" data-p="sound" ${prefs.sound?'checked':''}> Play a chime <button type="button" data-chime>Test</button></label>
      <label>Quiet hours</label><div class="row"><input type="time" data-p="quietStart" value="${prefs.quietStart}"> – <input type="time" data-p="quietEnd" value="${prefs.quietEnd}"> <span class="hint">no desktop notifications or sounds; popups still show</span></div>
      <label>Daily agenda</label><div class="row"><label class="hint"><input type="checkbox" data-p="digest" ${prefs.digest?'checked':''}> show on first open after</label><input type="time" data-p="digestTime" value="${prefs.digestTime}"></div>
      <label>Weekly review</label><div class="row"><label class="hint"><input type="checkbox" data-p="review" ${prefs.review?'checked':''}> prompt on</label><select data-p="reviewDay">${DAY_NAMES.map((n,i)=>`<option value="${i}" ${+prefs.reviewDay===i?'selected':''}>${n}</option>`).join('')}</select><input type="time" data-p="reviewTime" value="${prefs.reviewTime}"></div>
      <label>End of day</label><div class="row"><label class="hint"><input type="checkbox" data-p="eod" ${prefs.eod?'checked':''}> prompt at</label><input type="time" data-p="eodTime" value="${prefs.eodTime}"></div>
      <div class="full warnbox">Reminders fire while this calendar is open in a browser tab (it can be in the background). Install it as an app (General tab) to keep it running, and export a .ics file to your phone for alerts when it is closed. Missed reminders are shown the next time you open it.</div></form>`;
    if(setTab==='calendars') body=`<div class="list-edit">${data.calendars.map((c,i)=>`<div class="li" data-ci="${i}"><input type="color" data-c="color" value="${esc(c.color)}" ${ro} aria-label="Colour"><input type="text" data-c="name" value="${esc(c.name)}" ${ro} aria-label="Name"><label class="hint"><input type="checkbox" data-c="private" ${c.private?'checked':''} ${ro||(c.id==='work'?'disabled':'')}> private</label>${editable&&!['work','personal'].includes(c.id)?`<button class="ghost danger" data-cdel="${i}">🗑</button>`:''}</div>`).join('')}
      ${editable?'<button data-cadd style="align-self:flex-start">＋ Add a calendar (e.g. Family)</button>':''}</div>
      <div class="rsec">Privacy of Personal items</div>
      <p class="hint">${data.private?(privKey?'🔓 Personal items are encrypted and unlocked on this device.':'🔒 Personal items are encrypted and locked on this device.'):'No passphrase set yet. Personal items need one.'}</p>
      <div class="row" style="display:flex;gap:8px;flex-wrap:wrap">${!data.private&&editable?'<button data-pset>Set passphrase…</button>':''}${data.private&&privLocked?'<button data-punl>Unlock…</button>':''}${data.private&&privKey&&editable?'<button data-pchg>Change passphrase…</button><button data-plock>Lock on this device</button>':''}</div>
      <label class="hint" style="display:block;margin-top:10px"><input type="checkbox" data-showbusy ${s.showBusy?'checked':''} ${ro}> Viewers see Personal time as “Busy” blocks (no titles). Untick to hide it completely.</label>
      <table class="rt" style="margin-top:12px"><thead><tr><th></th><th>Editor</th><th>Viewer</th><th>Anyone with the passphrase</th></tr></thead><tbody>
        <tr><td>Status, location, flights, Work items</td><td>see + edit</td><td>see</td><td>—</td></tr><tr><td>Personal / private items</td><td>see + edit when unlocked</td><td>${s.showBusy?'“Busy” only':'hidden'}</td><td>see (edit if editor)</td></tr><tr><td>Accounts</td><td>manage</td><td>—</td><td>—</td></tr><tr><td>Reminders</td><td>own, per device</td><td>own, per device</td><td>—</td></tr></tbody></table>`;
    if(setTab==='templates'){ body='<div class="hint">Templates create several events, tasks and flights at once from a start date.</div><button data-otpl style="margin-top:10px">Open templates…</button>'; }
    if(setTab==='trash') body=data.trash.length?`<table class="rt"><thead><tr><th>Deleted</th><th>Type</th><th>Title</th><th></th></tr></thead><tbody>${[...data.trash].reverse().map(x=>`<tr><td class="num">${esc(fmtWhen(x.deletedAt))}</td><td>${esc(x.kind)}</td><td>${esc(x.item.title||x.item.name)}</td><td>${editable?`<button data-rest="${x.id}">Restore</button>`:''}</td></tr>`).join('')}</tbody></table>${editable?'<button data-empty class="danger" style="margin-top:10px">Empty trash</button>':''}`:'<div class="rempty">Trash is empty. Deleted items stay here for 30 days.</div>';
    if(setTab==='data') body=`<div class="list-edit">
      <div class="li"><button data-x="icsexp">📤 Export .ics…</button>${editable?'<button data-x="icsimp">📥 Import .ics…</button><button data-x="csvimp">📥 Import tasks from CSV…</button>':''}</div>
      <div class="li"><button data-x="backup">⬇ Export backup (.json)</button>${editable?'<button data-x="restore">⬆ Restore / import backup…</button>':''}</div>
      <div class="rsec">Archive</div><p class="hint">Saving is limited to 3 MB on the server. Move past events and finished tasks older than a number of months to a file. Open an archive file to include it in reports and search for this session.</p>
      <div class="li">${editable?`<input type="number" data-am value="${s.archiveMonths}" min="1" style="width:80px"> months <button data-x="archive">Archive now…</button>`:''}<button data-x="openarch">Open archive file…</button>${archive?`<span class="tag">Open: ${esc(archive.name)}</span>`:''}</div>
      <p class="hint">Current size: about ${Math.round(JSON.stringify(data).length/1024)} KB of 3,000 KB.</p></div>`;
    dd.querySelector('.box').innerHTML=`<div class="dhd"><h2>⚙ Settings</h2><button class="ghost" data-close>✕</button></div><div class="tabs">${tabs.map(([k,l])=>`<button data-st="${k}" class="${setTab===k?'on':''}">${l}</button>`).join('')}</div>${body}<div class="dacts"><span class="sp"></span><button class="primary" data-close>Done</button></div>`;
    const B=dd.querySelector('.box');
    $$('[data-close]',B).forEach(b=>b.onclick=()=>closeDialog(dd));
    $$('[data-st]',B).forEach(b=>b.onclick=()=>{ setTab=b.dataset.st; draw(); });
    // shared settings (editor)
    $$('[data-sf] [name]',B).forEach(x=>x.onchange=()=>{ if(!editable) return; const n=x.name; s[n]= x.type==='checkbox'?x.checked : x.type==='number'?+x.value : x.value; changed(); renderAll(); });
    $$('[data-wd]',B).forEach(b=>b.onclick=()=>{ if(!editable) return; const i=+b.dataset.wd; s.workDays=s.workDays.includes(i)?s.workDays.filter(x=>x!==i):[...s.workDays,i].sort(); changed(); draw(); renderAll(); });
    // device prefs
    $$('[data-p]',B).forEach(x=>x.onchange=x.oninput=()=>{ const n=x.dataset.p; prefs[n]= x.type==='checkbox'?x.checked : x.type==='range'?+x.value : x.value; savePrefs(); if(n==='offlineCopy'){ if(x.checked) cacheOffline(); else clearOffline(); } if(x.type==='range') render(); });
    const pm=$('[data-perm]',B); if(pm) pm.onclick=()=>askDesktopPermission().then(draw);
    const ch=$('[data-chime]',B); if(ch) ch.onclick=chime;
    $$('[data-edrem]',B).forEach(b=>b.onclick=()=>{ const n=b.dataset.edrem; const list=clone(s[n]).map(r=>{ delete r.anchor; return r; }); const ed=openDialog(`<div class="dhd"><h2>Default reminders</h2></div><div id="dr"></div><div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" data-ok>Save</button></div>`,{size:'narrow'});
      const rr=()=>{ $('#dr',ed).innerHTML=remFormHTML(list); bindRems($('#dr',ed),list,null,rr); }; rr(); $('[data-ok]',ed).onclick=()=>{ s[n]=list; changed(); closeDialog(ed); draw(); }; });
    // calendars
    $$('[data-ci]',B).forEach(li=>{ const c=data.calendars[+li.dataset.ci]; $$('[data-c]',li).forEach(x=>x.onchange=()=>{ if(!editable) return; const f=x.dataset.c;
      if(f==='private' && x.checked && !privKey){ x.checked=false; return ensurePrivReadySync(); }
      if(f==='private' && !x.checked && privLocked){ x.checked=true; return openUnlock(); }
      c[f]=x.type==='checkbox'?x.checked:x.value; touch(c); changed(); renderAll(); }); });
    const ca=$('[data-cadd]',B); if(ca) ca.onclick=async()=>{ const n=await promptDlg('New calendar','Name',''); if(!n) return; data.calendars.push({id:uid('cal'),name:n,color:PALETTE[data.calendars.length%PALETTE.length],private:false,order:data.calendars.length}); changed(); draw(); renderAll(); };
    $$('[data-cdel]',B).forEach(b=>b.onclick=async()=>{ const c=data.calendars[+b.dataset.cdel]; const n=[...data.events,...data.tasks,...data.projects].filter(x=>x.calendarId===c.id).length; if(!await confirmDlg(`Remove calendar “${c.name}”? Its ${n} items move to Work.`,[['Remove','y','danger'],['Cancel',null]])) return; for(const x of [...data.events,...data.tasks,...data.projects]) if(x.calendarId===c.id){ x.calendarId='work'; touch(x); } data.calendars.splice(+b.dataset.cdel,1); changed(); draw(); renderAll(); });
    const a=(s2,fn)=>{ const b=$(s2,B); if(b) b.onclick=fn; };
    a('[data-pset]',openPrivSetup); a('[data-punl]',openUnlock); a('[data-pchg]',changePassphrase); a('[data-plock]',async()=>{ if(dirty) await saveNow(); await lockPriv(); renderAll(); draw(); });
    const sb=$('[data-showbusy]',B); if(sb) sb.onchange=()=>{ s.showBusy=sb.checked; changed(); draw(); };
    a('[data-otpl]',openTemplates);
    $$('[data-rest]',B).forEach(b=>b.onclick=()=>{ restoreFromTrash(b.dataset.rest); draw(); });
    a('[data-empty]',async()=>{ if(!await confirmDlg('Permanently delete everything in the trash?',[['Empty trash','y','danger'],['Cancel',null]])) return; data.trash=[]; changed(); draw(); });
    $$('[data-x]',B).forEach(b=>b.onclick=()=>{ const x=b.dataset.x;
      if(x==='icsexp') openIcsExport(); if(x==='icsimp') pickFile('.ics,text/calendar',importIcsFile); if(x==='csvimp') pickFile('.csv,text/csv,.txt',importCsvFile);
      if(x==='backup') exportBackup(); if(x==='restore') pickFile('.json,.js,application/json',importBackupFile);
      if(x==='archive'){ const m=+$('[data-am]',B).value||12; s.archiveMonths=m; archiveOld(m); } if(x==='openarch') pickFile('.json',f=>openArchiveFile(f).then(draw)); });
  };
  const dd=openDialog('',{size:'mid',refresh:()=>{ if(!$('input:focus,select:focus',dd)) draw(); }}); draw();
}
function remPreview(list){ return `<div class="hint">${list.map(r=>esc((OFFSETS.find(o=>o[0]===+r.offsetMin)||[0,fmtDur(r.offsetMin)+' before'])[1])+(r.channel!=='popup'?' + desktop':'')).join(', ')||'none'}</div>`; }
let _installEvt=null; addEventListener('beforeinstallprompt',e=>{ e.preventDefault(); _installEvt=e; });
function installHint(){
  if(matchMedia('(display-mode: standalone)').matches) return 'Installed ✓';
  if(_installEvt) { setTimeout(()=>{ const b=$('#instBtn'); if(b) b.onclick=async()=>{ _installEvt.prompt(); await _installEvt.userChoice; _installEvt=null; }; },0); return '<button type="button" id="instBtn">Install as an app</button> so it keeps running in its own window.'; }
  return 'Use your browser’s “Install app” / “Add to Home Screen” option to run it in its own window (Chrome, Edge, Safari on iPhone).';
}

/* ---------- user accounts & history (unchanged from v1) ---------- */
async function openAccounts(){
  let rows;
  try{ rows = (await api('users')).users.map(a=>({orig:a.user, user:a.user, role:a.role, pass:''})); }
  catch(e){ if(e.status!==401) alert(e.message); return; }
  let d;
  const draw = ()=>{
    const html=`<div class="dhd"><h2>👥 User accounts</h2><button class="ghost" data-close>✕</button></div>
      <p class="hint"><b>Editors</b> can read and change the calendar; <b>viewers</b> can only read it. Leave the password empty to keep the current one. New accounts need a password (at least 6 characters). Anyone whose password you change, or whose account you remove, is signed out. Personal items are protected separately by the passphrase (⚙ Settings → Calendars &amp; privacy).</p>
      <table class="acc"><thead><tr><th>Username</th><th style="width:120px">Role</th><th>New password</th><th></th></tr></thead><tbody>
      ${rows.map((a,i)=>`<tr data-i="${i}">
        <td><input type="text" data-f="user" value="${esc(a.user)}" autocomplete="off" autocapitalize="off" spellcheck="false" aria-label="Username"></td>
        <td><select data-f="role" aria-label="Role"><option value="editor" ${a.role==='editor'?'selected':''}>Editor</option><option value="viewer" ${a.role==='viewer'?'selected':''}>Viewer</option></select></td>
        <td><input type="password" data-f="pass" value="${esc(a.pass)}" autocomplete="new-password" placeholder="${a.orig?'unchanged':'password'}" aria-label="New password"></td>
        <td><button class="ghost" data-del="${i}" title="Remove account">🗑</button></td></tr>`).join('')}
      </tbody></table>
      <button id="accAdd">＋ Add account</button>
      <label class="hint" style="display:flex;gap:6px;align-items:center;margin-top:10px"><input type="checkbox" id="accShow"> Show passwords</label>
      <div class="lerr" id="accErr" style="margin-top:8px"></div>
      <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" id="accSave">Save</button></div>`;
    if(!d) d=openDialog(html,{size:'mid'}); else { d.querySelector('.box').innerHTML=html; $$('[data-close]',d).forEach(b=>b.onclick=()=>closeDialog(d)); }
    $$('tr[data-i]',d).forEach(tr=>$$('[data-f]',tr).forEach(inp=>inp.oninput=inp.onchange=()=>{ rows[+tr.dataset.i][inp.dataset.f]=inp.value; }));
    $$('[data-del]',d).forEach(b=>b.onclick=()=>{ rows.splice(+b.dataset.del,1); draw(); });
    $('#accAdd',d).onclick=()=>{ rows.push({user:'', role:'viewer', pass:''}); draw(); };
    $('#accShow',d).onchange=e=>$$('input[data-f=pass]',d).forEach(i=>i.type=e.target.checked?'text':'password');
    $('#accSave',d).onclick=()=>saveAccounts(rows,d);
  };
  draw();
}
async function saveAccounts(rows,d){
  const err=t=>{ $('#accErr',d).textContent=t; };
  const mine=rows.find(r=>r.orig && r.orig.toLowerCase()===session.user.toLowerCase());
  if((!mine || mine.role!=='editor') && !confirm('You are removing your own editor access. You will not be able to change the calendar any more. Continue?')) return;
  const btn=$('#accSave',d); btn.disabled=true; btn.textContent='Saving…';
  try{
    const j = await api('users', {users:rows.map(r=>({orig:r.orig, user:(r.user||'').trim(), role:r.role, pass:r.pass||''}))});
    closeDialog(d);
    if(!j.me || !j.me.user){ alert('Accounts saved. You no longer have an account, so you will be signed out.'); location.reload(); return; }
    session={user:j.me.user, role:j.me.role}; editable = j.me.role==='editor';
    afterModeChange(); toast('Accounts saved.');
  }catch(e){ if(e.status!==401) err(e.message); btn.disabled=false; btn.textContent='Save'; }
}
async function openHistory(){
  let list;
  try{ list = (await api('history')).backups; }catch(e){ if(e.status!==401) alert(e.message); return; }
  const d=openDialog(`<div class="dhd"><h2>🕘 Earlier versions</h2><button class="ghost" data-close>✕</button></div>
    <p class="hint">The server keeps a copy of the calendar before changes are saved (at most one every 10 minutes while you edit). Restoring a version replaces the current calendar; the current one is kept as a version too, so you can undo.</p>
    <p class="hint">Current version: saved ${esc(fmtWhen(lastSaved.saved))||'—'}${lastSaved.by?' by <b>'+esc(lastSaved.by)+'</b>':''}.</p>
    ${list.length ? `<table class="rt"><thead><tr><th>Saved</th><th>By</th><th>Days with entries</th><th></th></tr></thead><tbody>
      ${list.map(b=>`<tr><td class="num">${esc(fmtWhen(b.saved))}</td><td>${esc(b.by)}</td><td class="num">${b.days}</td><td style="text-align:right"><button data-restore="${esc(b.id)}">Restore</button></td></tr>`).join('')}
    </tbody></table>` : '<div class="rempty">No earlier versions yet. They appear here after the calendar is changed.</div>'}
    <div class="dacts"><span class="sp"></span><button data-close>Close</button></div>`,{size:'mid'});
  $$('[data-restore]', d).forEach(b=>b.onclick=async()=>{
    if(!confirm('Replace the current calendar with this earlier version?')) return;
    try{
      clearTimeout(saveTimer); saveTimer=null; dirty=false; conflict=null;
      await api('restore', {id:b.dataset.restore});
      await loadData(true); closeDialog(d); afterModeChange();
    }catch(e){ if(e.status!==401) alert(e.message); }
  });
}
