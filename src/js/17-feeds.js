/* ============================================================
   Calendar links: private addresses that the iPhone Calendar app,
   Outlook or Google Calendar subscribe to. The browser writes each
   calendar's events (with reminders) to the server after every save;
   api.php?a=ics&k=<secret> joins the parts a link asks for.
   ============================================================ */
let FEEDS=null, FEED_HASHES={}, _feedTimer=null, _feedBusy=false;
const FEED_PAST_DAYS=60, FEED_DAYS=550;
function feedUrl(id, webcal){ const u=new URL('api.php?a=ics&k='+encodeURIComponent(id), location.href.split('#')[0]); return webcal ? u.href.replace(/^https?:/,'webcal:') : u.href; }
function feedHash(s){ let h1=0x811c9dc5, h2=0x01000193^s.length; for(let i=0;i<s.length;i++){ const c=s.charCodeAt(i); h1=Math.imul(h1^c,16777619); h2=Math.imul(h2^c,2246822507); } return (h1>>>0).toString(36)+(h2>>>0).toString(36)+s.length.toString(36); }
const icsStampOf = it => { const t=Date.parse(it&&(it.updated||it.created)||''); return new Date(isNaN(t)?Date.UTC(2026,0,1):t).toISOString().replace(/[-:]/g,'').replace(/\.\d{3}/,''); };
const icsAbs = ms => new Date(ms).toISOString().replace(/[-:]/g,'').replace(/\.\d{3}/,'');
const icsTrig = min => (min>0?'-PT':'PT')+Math.abs(Math.round(min))+'M';     // minutes before the start
function feedAlarm(title, beforeMin){ return ['BEGIN:VALARM','ACTION:DISPLAY','DESCRIPTION:'+icsEsc(title),'TRIGGER:'+icsTrig(beforeMin),'END:VALARM']; }
function feedAllDayAlarm(title, r){ const at=hmToMin(S().allDayRemTime||'09:00')||540; return feedAlarm(title, (+r.offsetMin||0)-at); }   // like the app: at 09:00 on the day
function feedText(md){ return String(md||'').replace(/\*\*|__|`/g,'').replace(/^#+\s*/gm,'').replace(/\[([^\]]*)\]\(([^)]*)\)/g,'$1 ($2)').replace(/\\([\\`*_\[\]<>#+\-.!])/g,'$1').replace(/ {2}\n/g,'\n').slice(0,4000); }
const FEED_CAT_ICON={meeting:'',appointment:'',deadline:'⚑ ',travel:'',social:'',other:''};

/* events of one calendar -> {tzs:{id:text}, body:text} */
function feedEvents(evs){
  const tzs=new Set(), out=[];
  const from=NOW()-FEED_PAST_DAYS*864e5, to=NOW()+FEED_DAYS*864e5;
  const dt=(name,local,tz,allDay)=> allDay ? `${name};VALUE=DATE:${icsDate(local.slice(0,10))}` : `${name};TZID=${tz}:${icsLocal(local)}`;
  for(const e of evs){
    if(!expandEvent(e,from,to).length) continue;
    const tz=validTz(e.tz)?e.tz:(S().homeTz||BROWSER_TZ); if(!e.allDay) tzs.add(tz);
    const stamp=icsStampOf(e), uid=(e.uid||e.id+'@wtc'), title=(FEED_CAT_ICON[e.category]||'')+e.title;
    const ev=['BEGIN:VEVENT','UID:'+uid,'DTSTAMP:'+stamp,'SUMMARY:'+icsEsc(title)];
    if(e.allDay) ev.push(dt('DTSTART',e.start,tz,true),'DTEND;VALUE=DATE:'+icsDate(keyAdd((e.end||e.start).slice(0,10),1)));
    else ev.push(dt('DTSTART',e.start,tz),dt('DTEND',e.end,tz));
    if(e.location) ev.push('LOCATION:'+icsEsc(e.location));
    const desc=[feedText(e.notes), e.onlineLink, ...(e.links||[]).map(l=>(l.label?l.label+': ':'')+l.url), (e.files||[]).length?'📎 '+e.files.map(f=>f.name).join(', '):''].filter(Boolean).join('\n\n'); if(desc) ev.push('DESCRIPTION:'+icsEsc(desc));
    if(e.onlineLink && /^https?:/i.test(e.onlineLink)) ev.push('URL:'+e.onlineLink);
    if(e.status==='cancelled') ev.push('STATUS:CANCELLED');
    ev.push('TRANSP:'+(e.showAs==='free'||e.allDay?'TRANSPARENT':'OPAQUE'));
    const cats=[CATEGORIES[e.category]?CATEGORIES[e.category].replace(/^\S+\s/,''):'', proj(e.projectId)&&proj(e.projectId).name, ...e.tags].filter(Boolean); if(cats.length) ev.push('CATEGORIES:'+cats.map(icsEsc).join(','));
    if(e.rrule){ ev.push('RRULE:'+rruleStr(e.rrule,e.allDay,tz).join(';'));
      const ex=Object.entries(e.exceptions).filter(([k,x])=>x.deleted).map(([k])=>e.allDay?icsDate(k):icsLocal(k+'T'+e.start.slice(11,16)));
      if(ex.length) ev.push((e.allDay?'EXDATE;VALUE=DATE:':'EXDATE;TZID='+tz+':')+ex.join(',')); }
    if(e.status!=='cancelled') for(const r of e.reminders) ev.push(...(e.allDay?feedAllDayAlarm(e.title,r):feedAlarm(e.title,+r.offsetMin||0)));
    ev.push('END:VEVENT'); out.push(...ev);
    for(const [k,x] of Object.entries(e.exceptions)){ if(x.deleted) continue;
      const s=x.start||(e.allDay?k:k+'T'+e.start.slice(11,16)), en=x.end||(e.allDay?keyAdd(k,Math.round((uparse(e.end.slice(0,10))-uparse(e.start.slice(0,10)))/864e5)):localAddMin(s,localDiffMin(e.start,e.end)));
      const ov=['BEGIN:VEVENT','UID:'+uid,'DTSTAMP:'+stamp,(e.allDay?'RECURRENCE-ID;VALUE=DATE:'+icsDate(k):'RECURRENCE-ID;TZID='+tz+':'+icsLocal(k+'T'+e.start.slice(11,16))),'SUMMARY:'+icsEsc((FEED_CAT_ICON[e.category]||'')+(x.title||e.title))];
      if(e.allDay) ov.push(dt('DTSTART',s,tz,true),'DTEND;VALUE=DATE:'+icsDate(keyAdd(en.slice(0,10),1))); else ov.push(dt('DTSTART',s,tz),dt('DTEND',en,tz));
      if(x.location||e.location) ov.push('LOCATION:'+icsEsc(x.location||e.location));
      if(x.status==='cancelled'||e.status==='cancelled') ov.push('STATUS:CANCELLED');
      else for(const r of e.reminders) ov.push(...(e.allDay?feedAllDayAlarm(x.title||e.title,r):feedAlarm(x.title||e.title,+r.offsetMin||0)));
      ov.push('END:VEVENT'); out.push(...ov); }
  }
  return {tzs, lines:out};
}
/* tasks of one calendar: work blocks as events, due dates as events (phones do not show to-dos from subscriptions) */
function feedTasks(tks){
  const tzs=new Set(), out=[];
  const from=NOW()-FEED_PAST_DAYS*864e5, to=NOW()+FEED_DAYS*864e5;
  for(const t of tks){
    const stamp=icsStampOf(t), open=isOpen(t), mark=t.status==='done'?'✓ ':'☐ ';
    for(const b of t.blocks){ const s=zonedToMs(b.start,b.tz); if(s<from||s>=to) continue; const tz=validTz(b.tz)?b.tz:(S().homeTz||BROWSER_TZ); tzs.add(tz);
      out.push('BEGIN:VEVENT','UID:'+b.id+'@wtc','DTSTAMP:'+stamp,'SUMMARY:'+icsEsc(mark+t.title),`DTSTART;TZID=${tz}:${icsLocal(b.start)}`,`DTEND;TZID=${tz}:${icsLocal(b.end)}`,
        ...(t.notes?['DESCRIPTION:'+icsEsc(feedText(t.notes))]:[]),'CATEGORIES:Task',
        ...(open&&!b.done?t.reminders.filter(r=>r.anchor==='block').flatMap(r=>feedAlarm(t.title,+r.offsetMin||0)):[]),'END:VEVENT'); }
    if(!t.dueDate || !open) continue;
    const dk=fromKey(t.dueDate).getTime(); if(dk<from||dk>=to) continue;
    const ev=['BEGIN:VEVENT','UID:due-'+t.id+'@wtc','DTSTAMP:'+stamp,'SUMMARY:'+icsEsc('⚑ '+t.title)];
    const alarms=t.reminders.filter(r=>r.anchor==='due');
    if(t.dueTime){ const tz=validTz(t.tz)?t.tz:(S().homeTz||BROWSER_TZ); tzs.add(tz); const l=t.dueDate+'T'+t.dueTime;
      ev.push(`DTSTART;TZID=${tz}:${icsLocal(l)}`,`DTEND;TZID=${tz}:${icsLocal(localAddMin(l,15))}`, ...alarms.flatMap(r=>feedAlarm(t.title,+r.offsetMin||0))); }
    else ev.push('DTSTART;VALUE=DATE:'+icsDate(t.dueDate),'DTEND;VALUE=DATE:'+icsDate(keyAdd(t.dueDate,1)), ...alarms.flatMap(r=>feedAllDayAlarm(t.title,r)));
    ev.push('TRANSP:TRANSPARENT','CATEGORIES:Task due',...(t.notes?['DESCRIPTION:'+icsEsc(feedText(t.notes))]:[]),'END:VEVENT'); out.push(...ev);
  }
  return {tzs, lines:out};
}
function feedFlights(){
  const out=[]; const from=NOW()-FEED_PAST_DAYS*864e5, to=NOW()+FEED_DAYS*864e5;
  for(const k of flightKeys()){ const d=fromKey(k).getTime(); if(d<from||d>=to) continue;
    for(const w of flightWindows(k)){ const f=w.f; if(!f.from && !f.to) continue;
      const al=flightAlarmMin(f), alarmAt=al==null?null:fromDisp(zonedToMs(k+'T'+minToHM(al),w.tz));
      out.push('BEGIN:VEVENT','UID:fl-'+k+'-'+w.i+'@wtc','DTSTAMP:20260101T000000Z','SUMMARY:'+icsEsc('✈ '+(f.from||'?')+' → '+(f.to||'?')+(f.note?' · '+f.note:'')),
        'DTSTART:'+icsAbs(fromDisp(w.s)),'DTEND:'+icsAbs(fromDisp(w.e)),...(f.from?['LOCATION:'+icsEsc(f.from)]:[]),'CATEGORIES:Flight',
        'DESCRIPTION:'+icsEsc(`${FLIGHT_TIMES[flightKind(f)]||''} flight${f.note?' '+f.note:''}`+(al!=null?`\nAlarm ${minToHM(al)} (${f.from||'departure'} time)`:'')),
        ...(alarmAt!=null?['BEGIN:VALARM','ACTION:DISPLAY','DESCRIPTION:'+icsEsc('✈ '+(f.from||'')+' → '+(f.to||'')),'TRIGGER;VALUE=DATE-TIME:'+icsAbs(alarmAt),'END:VALARM']:[]),'END:VEVENT'); } }
  return {tzs:new Set(), lines:out};
}
function feedStatus(){
  const out=[]; const a=key(new Date(NOW()-FEED_PAST_DAYS*864e5)), b=key(new Date(NOW()+366*864e5));
  for(const k of Object.keys(data.days).sort()){ if(k<a||k>b) continue; const d=data.days[k];
    const am=d.am&&STATUSES[d.am]?STATUSES[d.am].label:'', pm=d.pm&&STATUSES[d.pm]?STATUSES[d.pm].label:'';
    const st=am&&pm&&am!==pm?`${am} (morning) · ${pm} (afternoon)`:(am||pm);
    const loc=d.location||''; if(!st && !loc && !d.comment) continue;
    out.push('BEGIN:VEVENT','UID:day-'+k+'@wtc','DTSTAMP:20260101T000000Z','SUMMARY:'+icsEsc([st,loc&&'📍 '+loc].filter(Boolean).join(' · ')||'📝 Note'),
      'DTSTART;VALUE=DATE:'+icsDate(k),'DTEND;VALUE=DATE:'+icsDate(keyAdd(k,1)),'TRANSP:TRANSPARENT','CATEGORIES:Work status',...(d.comment?['DESCRIPTION:'+icsEsc(d.comment)]:[]),'END:VEVENT'); }
  return {tzs:new Set(), lines:out};
}
function feedPart(x){ const tzs={}; for(const tz of x.tzs) if(validTz(tz)) tzs[tz]=vtimezone(tz).map(icsFold).join('\r\n')+'\r\n';
  const body=x.lines.length?x.lines.map(icsFold).join('\r\n')+'\r\n':''; return {tzs, body, h:feedHash(JSON.stringify(tzs)+body)}; }

/* which parts the links need, built from what this browser can see */
function feedNeededParts(){
  const need=new Set();
  for(const f of FEEDS){
    const cals=f.cals==='*'?data.calendars.map(c=>c.id):f.cals;
    for(const c of cals){ need.add('e:'+c); if(f.tasks) need.add('t:'+c); if(f.personal){ need.add('pe:'+c); if(f.tasks) need.add('pt:'+c); } }
    if(f.flights) need.add('flights'); if(f.status) need.add('status');
  }
  return need;
}
function feedBuild(k){
  const m=/^(p?)([et]):(.+)$/.exec(k);
  if(m){ const priv=!!m[1], c=m[3];
    if(priv && data.private && !privKey) return undefined;                 // Personal items are locked here: leave the server copy as it is
    const inCal=it=>it.calendarId===c && isPrivate(it)===priv;
    return feedPart(m[2]==='e' ? feedEvents(data.events.filter(e=>e.type==='event' && inCal(e))) : feedTasks(data.tasks.filter(inCal))); }
  if(k==='flights') return feedPart(feedFlights());
  if(k==='status') return feedPart(feedStatus());
  return null;
}
async function feedSync(force){
  if(!editable || !session || DETAIL || offlineMode || _feedBusy) return;
  _feedBusy=true;
  try{
    if(!FEEDS || force){ const r=await api('feeds'); FEEDS=r.feeds; FEED_HASHES=r.hashes||{}; }
    if(!FEEDS.length) return;
    const need=feedNeededParts(), parts={};
    for(const k of need){ const p=feedBuild(k); if(p===undefined) continue; if(FEED_HASHES[k]!==p.h) parts[k]=p; }
    for(const k of Object.keys(FEED_HASHES)) if(!need.has(k) && !(/^p/.test(k) && data.private && !privKey)) parts[k]=null;
    if(!Object.keys(parts).length) return;
    // send in groups that stay under the request limit
    let batch={}, size=0;
    const send=async()=>{ if(!Object.keys(batch).length) return; await api('feed_parts',{parts:batch}); for(const [k,p] of Object.entries(batch)){ if(p) FEED_HASHES[k]=p.h; else delete FEED_HASHES[k]; } batch={}; size=0; };
    for(const [k,p] of Object.entries(parts)){ const n=p?p.body.length*1.2+2000:50; if(size+n>2500000) await send(); batch[k]=p; size+=n; }
    await send();
  }catch(e){ /* tried again after the next save */ }
  finally{ _feedBusy=false; }
}
function scheduleFeedSync(ms=2500){ if(!editable || DETAIL) return; clearTimeout(_feedTimer); _feedTimer=setTimeout(()=>feedSync(false), ms); }
setInterval(()=>{ if(session && editable && !document.hidden) feedSync(false); }, 10*60e3);   // also after unlocking Personal items and as days pass

/* ---------- the "Calendar links" window ---------- */
function feedWhat(f){
  const cals=f.cals==='*'?'All calendars':f.cals.map(c=>{ const x=data.calendars.find(y=>y.id===c); return x?x.name:'(deleted calendar)'; }).join(', ');
  return [cals, f.tasks&&'tasks', f.flights&&'flights', f.status&&'work status', f.personal&&'🔓 Personal details', !f.alarms&&'no reminders'].filter(Boolean).join(' · ');
}
function qrSvg(text){
  try{ const q=qrcode(0,'M'); q.addData(text); q.make(); return q.createSvgTag({cellSize:4, margin:2, scalable:true}); }catch(e){ return ''; }
}
async function openFeeds(){
  if(!editable) return;
  const d=openDialog(`<div class="dhd"><h2>🔗 Calendar links</h2><button class="ghost" data-close aria-label="Close">✕</button></div>
    <p class="hint">Private addresses that the <b>iPhone Calendar</b> app, <b>Outlook</b> or <b>Google Calendar</b> can subscribe to. They show your events with their reminders and update by themselves (the app decides how often: iPhone about every 15–60 minutes, Outlook every few hours). Anyone who has a link can read that calendar, so keep links private; “New address” stops an old link from working.</p>
    <div data-list><div class="hint">Loading…</div></div>
    <div class="dacts"><button data-a="std">Create a link for each calendar + one for all</button><span class="sp"></span><button data-close>Close</button><button class="primary" data-a="new">＋ New link</button></div>`,{size:'wide'});
  const L=$('[data-list]',d);
  const draw=async(reload)=>{
    try{ if(reload||!FEEDS){ const r=await api('feeds'); FEEDS=r.feeds; FEED_HASHES=r.hashes||{}; } }catch(e){ L.innerHTML=`<div class="warnbox">${esc(e.message)}</div>`; return; }
    L.innerHTML = FEEDS.length ? FEEDS.map((f,i)=>`<div class="feed" data-i="${i}">
        <div class="feedhd"><b>${esc(f.name)}</b><span class="hint">${esc(feedWhat(f))}</span></div>
        <div class="feedurl"><input type="text" readonly value="${esc(feedUrl(f.id))}" aria-label="Link for ${esc(f.name)}" data-sel></div>
        <div class="feedbtns">
          <button data-f="copy">📋 Copy link</button><a class="btn" href="${esc(feedUrl(f.id,true))}" data-f="webcal">📱 Subscribe on this device</a><button data-f="qr">▦ QR code</button>
          <button data-f="edit">✎ Edit</button><button data-f="renew" title="Give the link a new address; the old address stops working">↻ New address</button><button data-f="del" class="danger">🗑 Delete</button>
        </div>
        <div class="hint">${f.used?`Last read by a calendar app ${esc(fmtWhen(f.used))}${f.ua?` (${esc(feedAgent(f.ua))})`:''}`:'Not used yet'} · created ${esc(fmtWhen(f.created||''))}${f.by?' by '+esc(f.by):''}</div>
        <div class="feedqr" hidden></div>
      </div>`).join('') : `<p class="hint">No links yet. Use <b>Create a link for each calendar + one for all</b>, or <b>＋ New link</b> to choose what a link shows.</p>`;
    L.insertAdjacentHTML('beforeend',`<details class="feedhow"><summary>How to subscribe</summary>
      <p><b>iPhone Calendar:</b> open this window on the iPhone and tap <b>📱 Subscribe on this device</b>, or scan the <b>▦ QR code</b> with the iPhone camera, then tap <i>Subscribe</i> and <i>Add</i>. Or: Settings → Calendar → Accounts → Add Account → Other → <i>Add Subscribed Calendar</i>, and paste the link. If reminders do not sound, open that subscription in Settings → Calendar → Accounts and turn <i>Remove Alerts</i> off. For quicker updates: Settings → Calendar → Accounts → Fetch New Data.</p>
      <p><b>Outlook (Microsoft 365):</b> in Outlook on the web: Calendar → <i>Add calendar</i> → <i>Subscribe from web</i>, paste the link, give it a name, <i>Import</i>. It then also appears in the Outlook app on the iPhone and on the computer. Outlook refreshes it every few hours and may not use the reminders.</p>
      <p><b>Google Calendar:</b> on a computer: Other calendars ＋ → <i>From URL</i>.</p>
      <p>The links are read-only: add and change items here. Items are included from ${FEED_PAST_DAYS} days ago; repeating events always.</p></details>`);
    $$('[data-sel]',L).forEach(i=>i.onclick=()=>i.select());
    $$('.feed',L).forEach(row=>{ const f=FEEDS[+row.dataset.i]; const b=n=>$(`[data-f="${n}"]`,row);
      b('copy').onclick=async()=>{ try{ await navigator.clipboard.writeText(feedUrl(f.id)); toast('Link copied'); }catch(e){ const i=$('[data-sel]',row); i.select(); document.execCommand('copy'); toast('Link copied'); } };
      b('qr').onclick=()=>{ const q=$('.feedqr',row); if(q.hidden){ q.innerHTML=`<div class="qrbox">${qrSvg(feedUrl(f.id,true))}</div><div class="hint">Scan with the iPhone camera, then tap <i>Subscribe</i>.</div>`; } q.hidden=!q.hidden; };
      b('edit').onclick=()=>feedEditor(f, ()=>draw(true));
      b('renew').onclick=async()=>{ if(!await feedConfirm(`Give “${f.name}” a new address?`,'Phones and apps subscribed with the old address stop receiving updates; subscribe them again with the new link.','New address')) return;
        try{ await api('feed_renew',{id:f.id}); toast('New address created'); draw(true); }catch(e){ alert(e.message); } };
      b('del').onclick=async()=>{ if(!await feedConfirm(`Delete the link “${f.name}”?`,'Phones and apps subscribed to it stop receiving updates (they keep showing the last copy until you remove the subscription there).','Delete','danger')) return;
        try{ await api('feed_delete',{id:f.id}); FEEDS=null; toast('Link deleted'); await draw(true); feedSync(true); }catch(e){ alert(e.message); } };
    });
  };
  $('[data-a="new"]',d).onclick=()=>feedEditor(null, ()=>draw(true));
  $('[data-a="std"]',d).onclick=async()=>{
    const have=new Set((FEEDS||[]).map(f=>f.cals==='*'?'*':f.cals.join(',')));
    const want=[{name:(data.owner?data.owner+' – ':'')+'All calendars', cals:'*', tasks:true, flights:true, status:false, alarms:true, personal:false},
      ...data.calendars.map(c=>({name:c.name, cals:[c.id], tasks:true, flights:false, status:false, alarms:true, personal:false}))].filter(w=>!have.has(w.cals==='*'?'*':w.cals.join(',')));
    if(!want.length) return toast('Every calendar already has a link.');
    const priv=data.calendars.filter(c=>c.private);
    if(priv.length && !await feedConfirm('Create the links?',`${want.length} link${want.length===1?'':'s'}: ${want.map(w=>'“'+esc(w.name)+'”').join(', ')}.<br><br>Personal calendars (${priv.map(c=>esc(c.name)).join(', ')}) are encrypted, so their items are not included until you allow it on a link with ✎ Edit → “Include Personal details”.`,'Create')) return;
    try{ for(const w of want) await api('feed_save',w); FEEDS=null; await draw(true); feedSync(true); toast(`${want.length} link${want.length===1?'':'s'} created`); }catch(e){ alert(e.message); }
  };
  draw(true);
}
const feedConfirm=(title, html, ok, cls)=>confirmDlg({title, html}, [['Cancel',false],[ok,true,cls||'primary']]);
function feedAgent(ua){ return /iPhone|iPad|iOS/i.test(ua)?'iPhone/iPad':/Macintosh|Mac OS|CalendarAgent|dataaccessd/i.test(ua)?'Apple':/Microsoft|Outlook|Exchange/i.test(ua)?'Outlook':/Google/i.test(ua)?'Google':/Android/i.test(ua)?'Android':'app'; }
function feedEditor(f, done){
  const isNew=!f; f=f||{name:'', cals:'*', tasks:true, flights:true, status:false, alarms:true, personal:false};
  const all=f.cals==='*';
  const d=openDialog(`<div class="dhd"><h2>${isNew?'＋ New calendar link':'✎ Edit calendar link'}</h2><button class="ghost" data-close aria-label="Close">✕</button></div>
    <form class="frm" autocomplete="off">
      <label>Name</label><input type="text" name="name" value="${esc(f.name)}" placeholder="e.g. Work calendar" required maxlength="80">
      <label>Calendars</label><div class="list-edit">
        <label class="hint"><input type="radio" name="scope" value="all" ${all?'checked':''}> All calendars (also ones added later)</label>
        <label class="hint"><input type="radio" name="scope" value="some" ${all?'':'checked'}> Only:</label>
        ${data.calendars.map(c=>`<label class="hint" style="margin-left:22px"><input type="checkbox" data-cal="${esc(c.id)}" ${!all&&f.cals.includes(c.id)?'checked':''}> <span class="caldot" style="background:${esc(c.color||'#888')}"></span> ${esc(c.name)}${c.private?' 🔒':''}</label>`).join('')}
      </div>
      <label>Include</label><div class="list-edit">
        <label class="hint"><input type="checkbox" name="tasks" ${f.tasks?'checked':''}> Tasks: work blocks and due dates (⚑)</label>
        <label class="hint"><input type="checkbox" name="flights" ${f.flights?'checked':''}> Flights ✈ with their alarm (06:00 / 14:00 / 17:30 or your own)</label>
        <label class="hint"><input type="checkbox" name="status" ${f.status?'checked':''}> Work status and location of each day (all-day)</label>
        <label class="hint"><input type="checkbox" name="alarms" ${f.alarms?'checked':''}> Reminders (alerts on the phone)</label>
        <label class="hint"><input type="checkbox" name="personal" ${f.personal?'checked':''}> 🔓 Include Personal details</label>
        <div class="warnbox" data-pw ${f.personal?'':'hidden'}>Personal items are encrypted on the server. To put them in this link, this browser writes them <b>unencrypted</b> to the server's data folder (only for links with this option), and anyone with the link can read them. They are updated only while Personal items are unlocked.</div>
      </div>
    </form>
    <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" data-a="save">${isNew?'Create link':'Save'}</button></div>`,{size:'mid', focus:'[name=name]'});
  const F=$('form',d);
  $('[name=personal]',F).onchange=e=>{ $('[data-pw]',d).hidden=!e.target.checked; };
  $$('[data-cal]',F).forEach(c=>c.onchange=()=>{ $('[name=scope][value=some]',F).checked=true; });
  $('[data-a="save"]',d).onclick=async()=>{
    if(!F.reportValidity()) return;
    const some=$('[name=scope][value=some]',F).checked, cals=some?$$('[data-cal]:checked',F).map(c=>c.dataset.cal):'*';
    if(some && !cals.length) return alert('Choose at least one calendar.');
    const ck=n=>$(`[name=${n}]`,F).checked;
    const o={id:isNew?'':f.id, name:$('[name=name]',F).value.trim(), cals, tasks:ck('tasks'), flights:ck('flights'), status:ck('status'), alarms:ck('alarms'), personal:ck('personal')};
    if(o.personal && data.private && !privKey && !ensurePrivReadySync()) return;
    try{ await api('feed_save',o); closeDialog(d); FEEDS=null; toast(isNew?'Link created':'Link saved'); done&&done(); feedSync(true); }catch(e){ alert(e.message); }
  };
}
