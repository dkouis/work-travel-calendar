/* ============================================================
   Items: create, complete, delete, duplicate, convert; popover;
   day drawer; bulk actions
   ============================================================ */
function newEvent(o={}){
  const k=o.start?o.start.slice(0,10):key(cursor);
  const tz=o.tz||ACTIVE_TZ;
  return Object.assign({id:uid('e'), type:'event', calendarId:'work', projectId:null, title:'', category:'meeting', allDay:false,
    start:k+'T'+(S().workStart||'09:00'), end:localAddMin(k+'T'+(S().workStart||'09:00'), S().defaultEventMin||60), tz,
    location:'', onlineLink:'', attendees:[], notes:'', tags:[], showAs:'busy', status:'confirmed', rrule:null, exceptions:{},
    reminders:clone(S().defaultEventRem), linkedTaskIds:[], links:[], private:false}, o);
}
function newTask(o={}){
  return Object.assign({id:uid('t'), type:'task', calendarId:'work', projectId:null, title:'', notes:'', tags:[], status:'todo', priority:3,
    dueDate:null, dueTime:null, tz:ACTIVE_TZ, estimateMin:null, checklist:[], blocks:[], dependsOn:[], rrule:null,
    reminders:[...clone(S().defaultDueRem).map(r=>({anchor:'due',...r})), ...clone(S().defaultBlockRem).map(r=>({anchor:'block',...r}))], completedAt:null, private:false}, o);
}
function newProject(o={}){
  const used=new Set(data.projects.map(p=>p.color));
  return Object.assign({id:uid('p'), calendarId:'work', name:'', color:PALETTE.find(c=>!used.has(c))||PALETTE[data.projects.length%PALETTE.length], icon:'📁', description:'', status:'active',
    startDate:todayKey(), dueDate:null, budgetHours:null, parentId:null, pinned:false, order:data.projects.length, tripFrom:null, tripTo:null, reminders:[]}, o);
}
/* Personal (private) items need the passphrase set up / unlocked first */
function ensurePrivReadySync(){
  if(privKey) return true;
  if(privLocked){ openUnlock(); toast('Unlock Personal items first.'); return false; }
  openPrivSetup(); toast('Set a passphrase for Personal items first.'); return false;
}
function saveNewItem(it){
  if(isPrivate(it) && !ensurePrivReadySync()) return false;
  touch(it);
  if(it.type==='event') data.events.push(it); else if(it.type==='task') data.tasks.push(it); else data.projects.push(it);
  changed(); renderAll(); return true;
}
function removeItem(it, quiet){
  const col = it.type==='event'?'events':it.type==='task'?'tasks':'projects';
  data[col]=data[col].filter(x=>x.id!==it.id);
  data.trash.push({id:it.id, deletedAt:nowISO(), kind:it.type||'project', item:it});
  if(it.type==='task'){ for(const t of data.tasks) if(t.dependsOn.includes(it.id)){ t.dependsOn=t.dependsOn.filter(x=>x!==it.id); } for(const e of data.events) e.linkedTaskIds=e.linkedTaskIds.filter(x=>x!==it.id); }
  if(!quiet){ changed(); renderAll(); toast(`Deleted “${it.title||it.name}”`, {actions:[['Undo',undo]]}); }
}
function restoreFromTrash(id){
  const x=data.trash.find(t=>t.id===id); if(!x) return;
  const col=x.kind==='event'?'events':x.kind==='task'?'tasks':'projects';
  data[col].push(x.item); data.trash=data.trash.filter(t=>t.id!==id); normalizeItems(); changed(); renderAll();
}
function duplicateItem(it){
  const c=clone(it); c.id=uid(it.type==='event'?'e':'t'); c.title=(c.title||'')+' (copy)'; delete c.uid;
  if(c.type==='task'){ c.blocks=c.blocks.map(b=>({...b,id:uid('b'),done:false,actualMin:undefined})); c.status='todo'; c.completedAt=null; }
  touch(c); c.created=c.updated;
  if(c.type==='event') data.events.push(c); else data.tasks.push(c);
  changed(); renderAll(); return c;
}
function toggleTaskDone(id){
  const t=task(id); if(!t || !editable) return;
  setTaskStatus(t, isOpen(t)?'done':'todo');
}
function setTaskStatus(t, st){
  const was=isOpen(t);
  t.status=st; t.completedAt = st==='done' ? nowISO() : null; touch(t);
  let next=null;
  if(st==='done' && was && t.rrule && t.dueDate){ // recurring task: create the next one
    const nk=nextSeriesDate(t.rrStart||t.dueDate, t.rrule, t.dueDate);
    if(nk){ next=clone(t); next.id=uid('t'); next.status='todo'; next.completedAt=null; next.dueDate=nk; next.blocks=[]; next.checklist=next.checklist.map(c=>({...c,done:false})); next.rrStart=t.rrStart||t.dueDate; touch(next); next.created=next.updated; data.tasks.push(next); }
  }
  changed(); renderAll();
  if(st==='done' && was){
    toast(`✓ “${t.title}” done`+(next?` · next one due ${fmtShort(fromKey(next.dueDate))}`:''), {actions:[['Undo',undo]], ms:10000});
    budgetCheck(proj(t.projectId));
  }
}
function toggleBlockDone(id, bid){
  const t=task(id); if(!t || !editable) return; const b=t.blocks.find(x=>x.id===bid); if(!b) return;
  if(b.done){ b.done=false; delete b.actualMin; touch(t); changed(); renderAll(); return; }
  const planned=localDiffMin(b.start,b.end);
  askTimeSpent(planned, t.title).then(min=>{
    if(min===null) return;
    b.done=true; b.actualMin=min; if(t.status==='todo') t.status='in-progress'; touch(t);
    changed(); renderAll(); budgetCheck(proj(t.projectId));
    const left=t.estimateMin?remainingMin(t):null;
    toast(`Block done · ${fmtDur(min)} spent`+(left!==null?` · ${fmtDur(left)} left of the estimate`:''), {actions:[['Undo',undo], ...(isOpen(t)?[['Mark task done',()=>setTaskStatus(t,'done')]]:[])]});
  });
}
function askTimeSpent(planned, title){
  return new Promise(res=>{
    const d=openDialog(`<div class="dhd"><h2>Time spent</h2></div><form><p class="hint">How long did you work on “${esc(title)}”?</p>
      <div class="row" style="display:flex;gap:8px;align-items:center"><input type="number" id="tsH" min="0" max="24" step="1" value="${Math.floor(planned/60)}" style="width:70px"> h <input type="number" id="tsM" min="0" max="59" step="5" value="${planned%60}" style="width:70px"> min</div>
      <div class="dacts"><span class="sp"></span><button type="button" data-close>Cancel</button><button class="primary" type="submit">Mark done</button></div></form>`,{size:'narrow',onclose:()=>res(null)});
    $('form',d).onsubmit=e=>{ e.preventDefault(); const m=(+$('#tsH',d).value||0)*60+(+$('#tsM',d).value||0); d._onclose=null; closeDialog(d); res(m); };
  });
}
function budgetCheck(p){
  if(!p || !p.budgetHours) return;
  const st=projStats(p), pct=st.spent/60/p.budgetHours;
  const mark=pct>=1?100:pct>=.8?80:0; const keyb='wtc-budget:'+p.id;
  if(mark && (+safeGet(keyb)||0)<mark){ safeSet(keyb,mark); toast(`💶 “${p.name}”: ${fmtH(st.spent)} spent of the ${p.budgetHours} h budget (${Math.round(pct*100)}%).`,{ms:9000}); }
}
/* delete an occurrence with scope choice for repeating events */
async function deleteOcc(id, oc, kind){
  const it=findItem(id); if(!it||!editable) return;
  if(kind==='block'){ it.blocks=it.blocks.filter(b=>b.id!==oc); touch(it); changed(); renderAll(); toast('Time block removed',{actions:[['Undo',undo]]}); return; }
  if(it.rrule){
    const scope=await confirmDlg({title:'Delete repeating event', html:`“${esc(it.title)}” repeats. What do you want to delete?`}, [['Only this one','this'],['This and following','following'],['All of them','all','danger'],['Cancel',null]]);
    if(!scope) return;
    if(scope==='this'){ it.exceptions[oc]={deleted:true}; touch(it); changed(); renderAll(); toast('Occurrence deleted',{actions:[['Undo',undo]]}); return; }
    if(scope==='following'){ if(oc<=it.start.slice(0,10)) return removeItem(it); it.rrule=Object.assign({},it.rrule,{until:keyAdd(oc,-1),count:null}); for(const k of Object.keys(it.exceptions)) if(k>=oc) delete it.exceptions[k]; touch(it); changed(); renderAll(); toast('Following occurrences deleted',{actions:[['Undo',undo]]}); return; }
  } else if(!await confirmDlg({title:'Delete', html:`Delete “${esc(it.title)}”? You can restore it from ⚙ Settings → Trash for 30 days.`},[['Delete','y','danger'],['Cancel',null]])) return;
  removeItem(it);
}
function convertItem(it){
  if(it.type==='event'){
    const t=newTask({title:it.title, calendarId:it.calendarId, projectId:it.projectId, notes:it.notes, tags:it.tags, private:it.private});
    if(!it.allDay){ t.blocks=[{id:uid('b'), start:it.start, end:it.end, tz:it.tz, done:false}]; t.estimateMin=localDiffMin(it.start,it.end); t.dueDate=it.start.slice(0,10); }
    else t.dueDate=it.start.slice(0,10);
    removeItem(it,true); touch(t); data.tasks.push(t); changed(); renderAll(); toast('Converted to a task',{actions:[['Undo',undo]]}); return t;
  }
  const b=it.blocks[0]; const k=b?b.start.slice(0,10):(it.dueDate||todayKey());
  const e=newEvent({title:it.title, calendarId:it.calendarId, projectId:it.projectId, notes:it.notes, tags:it.tags, private:it.private, category:'other'});
  if(b){ e.start=b.start; e.end=b.end; e.tz=b.tz; } else if(it.dueDate){ e.allDay=true; e.start=k; e.end=k; }
  removeItem(it,true); touch(e); data.events.push(e); changed(); renderAll(); toast('Converted to an event',{actions:[['Undo',undo]]}); return e;
}

/* ---------- popover for a calendar entry ---------- */
function openOccPopover(id, oc, kind, anchorEl){
  closePopover();
  const it=findItem(id); if(!it) return;
  const o=findOcc(id,oc,kind); if(!o) return;
  selItem={kind,id,occ:oc};
  const p=document.createElement('div'); p.className='pop'; p.id='pop';
  const pr=proj(it.projectId), c=cal(it.calendarId);
  const when = o.allDay ? (o.dateKey===o.endKey?fmtLong(fromKey(o.dateKey)):fmtShort(fromKey(o.dateKey))+' – '+fmtShort(fromKey(o.endKey))) : `${fmtShort(new Date(o.s))} · ${hm(o.s)}–${hm(o.e)}${(it.tz||(o.block&&o.block.tz))&&(it.tz||o.block.tz)!==ACTIVE_TZ?` (${esc(tzShort(ACTIVE_TZ))} time; entered as ${esc(msToZoned(o.s,it.tz||o.block.tz).slice(11))} ${esc(tzShort(it.tz||o.block.tz))})`:''}`;
  const conf=conflictsIn(occurrences(o.s-864e5,o.e+864e5,{all:true})).get(id+'|'+oc);
  const isTask=kind==='block';
  const missed=o.missed;
  p.innerHTML=`<h3 class="redtitle"><i class="dotc" style="background:${esc(itemColor(it))};margin-top:5px"></i><span style="flex:1">${esc(CAT_ICON[it.category]||'')}${o.cancelled?'<s>':''}${esc(o.title)}${o.cancelled?'</s>':''}</span><button class="ghost" data-x style="padding:0 4px">✕</button></h3>
    <div class="ln">🕒 ${esc(when)}</div>
    ${isTask?`<div class="ln">☑ Task block${o.done?' · done'+(o.block.actualMin!=null?' ('+fmtDur(o.block.actualMin)+')':''):''}${missed?' · <b style="color:var(--missed)">missed</b>':''}</div>`:''}
    ${isTask&&it.dueDate?`<div class="ln">⚑ Due ${esc(dueLabel(it))}${isOverdue(it)?' <b style="color:var(--sick)">overdue</b>':''}</div>`:''}
    ${isTask&&it.estimateMin?`<div class="ln">⏱ ${fmtH(plannedMin(it))} scheduled of ${fmtH(it.estimateMin)} · ${fmtH(spentMin(it))} spent</div>`:''}
    ${it.rrule?`<div class="ln">🔁 ${esc(rrText(it.rrule))}</div>`:''}
    ${o.location||it.location?`<div class="ln">📍 ${esc(o.location||it.location)}</div>`:''}
    ${it.onlineLink?`<div class="ln">🔗 <a href="${esc(it.onlineLink)}" target="_blank" rel="noopener">Join online</a></div>`:''}
    <div class="ln">🗂 ${esc(c.name)}${pr?' · '+esc(pr.icon||'')+' '+esc(pr.name):''}${isPrivate(it)?' · 🔒':''}</div>
    ${it.attendees&&it.attendees.length?`<div class="ln">👥 ${esc(it.attendees.join(', '))}</div>`:''}
    ${conf?`<div class="warnbox" style="margin-top:6px">⚠ ${conf.map(esc).join('<br>')}</div>`:''}
    ${it.notes?`<div class="md" style="margin-top:6px;max-height:140px;overflow:auto">${md(it.notes)}</div>`:''}
    <div class="acts">
      ${editable?`<button class="primary" data-a="edit">✎ Edit</button>`:`<button data-a="edit">Details</button>`}
      ${editable&&isTask?`<button data-a="bdone">${o.done?'Not done':'✓ Block done'}</button>${isOpen(it)?'<button data-a="tdone">Task done</button>':''}`:''}
      ${editable&&isTask&&missed?'<button data-a="resched">↻ Reschedule</button>':''}
      ${isTask&&!o.done&&editable?'<button data-a="timer">⏱ Start timer</button><button data-a="focus">🎯 Focus</button>':''}
      ${editable&&!isTask?'<button data-a="followup">＋ Follow-up task</button>':''}
      ${editable?'<button data-a="dup">Duplicate</button><button data-a="del" class="danger">Delete</button>':''}
    </div>`;
  document.body.appendChild(p);
  const r=anchorEl?anchorEl.getBoundingClientRect():{left:innerWidth/2,right:innerWidth/2,top:100};
  placePop(p,{left:r.right+6,right:r.left-6,top:r.top});
  $('[data-x]',p).onclick=closePopover;
  const act=(a,fn)=>{ const b=$(`[data-a="${a}"]`,p); if(b) b.onclick=()=>{ closePopover(); fn(); }; };
  act('edit',()=> it.type==='event' ? openEventEditor(id,null,oc) : openTaskEditor(id));
  act('bdone',()=>toggleBlockDone(id,oc));
  act('tdone',()=>setTaskStatus(it,'done'));
  act('resched',()=>rescheduleBlock(it,o.block));
  act('timer',()=>startTimer(it.id,o.block.id));
  act('focus',()=>startFocus(it.id,o.block.id));
  act('followup',()=>{ const t=newTask({title:'Follow up: '+it.title, calendarId:it.calendarId, projectId:it.projectId, dueDate:keyAdd(o.dateKey||key(new Date(o.s)),2), private:it.private}); openTaskEditor(null,t,{linkEvent:it.id}); });
  act('dup',()=>{ const c=duplicateItem(it); toast('Duplicated',{actions:[['Edit',()=> c.type==='event'?openEventEditor(c.id):openTaskEditor(c.id)]]}); });
  act('del',()=>deleteOcc(id,oc,kind));
}
/* tiny Markdown: **bold**, *italic*, - lists, links */
function md(s){
  const inl=t=>esc(t).replace(/\*\*(.+?)\*\*/g,'<b>$1</b>').replace(/\*(.+?)\*/g,'<i>$1</i>').replace(/\[(.+?)\]\((https?:\/\/[^\s)]+)\)/g,'<a href="$2" target="_blank" rel="noopener">$1</a>').replace(/(^|\s)(https?:\/\/[^\s<]+)/g,'$1<a href="$2" target="_blank" rel="noopener">$2</a>');
  const out=[]; let ul=false;
  for(const line of String(s).split('\n')){
    const m=/^\s*[-*]\s+(.*)/.exec(line);
    if(m){ if(!ul){ out.push('<ul>'); ul=true; } out.push('<li>'+inl(m[1])+'</li>'); }
    else { if(ul){ out.push('</ul>'); ul=false; } if(line.trim()) out.push('<p>'+inl(line)+'</p>'); }
  }
  if(ul) out.push('</ul>');
  return out.join('');
}

/* ---------- day drawer (status, location, flights, comments + events & tasks) ---------- */
function openDrawer(k){ openDay=k; renderDrawer(); $('#drawer').classList.add('open'); $('#scrim').classList.add('open'); }
function closeDrawer(){ openDay=null; $('#drawer').classList.remove('open'); $('#scrim').classList.remove('open'); }
function stepDrawer(n){ if(!openDay) return; const d=addDays(fromKey(openDay),n); openDay=key(d); ensureVisible(d); renderDrawer(); }
function ensureVisible(d){
  const [a,b]=visibleRange(); if(d<a || d>=b){ cursor=d; render(); }
}
function renderDrawer(){
  const k=openDay, d=fromKey(k), e=day(k), we=isWeekend(d), dis=editable?'':'disabled';
  const chipRow=(half)=>Object.entries(STATUSES).map(([s,v])=>{
    const on = half==='both' ? (e.am===s && e.pm===s) : e[half]===s;
    return `<button class="chip ${on?'on':''}" style="${on?`background:var(--${s})`:''}" data-half="${half}" data-s="${s}" ${dis}><i class="sw" style="background:var(--${s})"></i>${v.label}</button>`;
  }).join('') + `<button class="chip" data-half="${half}" data-s="" ${dis} title="Clear">✕</button>`;
  const l=locationOf(k), loc=e.location||'';
  const isOther = loc && !['athens','brussels'].includes(loc.toLowerCase());
  const flights=(e.flights||[]).map((f,i)=>`
    <div class="flight" data-i="${i}">
      <div class="route">✈
        ${editable?`<input type="text" data-ff="from" value="${esc(f.from)}" aria-label="From"> → <input type="text" data-ff="to" value="${esc(f.to)}" aria-label="To">`
                  :`${esc(f.from)} → ${esc(f.to)}`}
      </div>
      ${editable?`<button class="ghost" data-fdel="${i}" title="Remove flight">🗑</button>`:'<span></span>'}
      <div class="row2">
        ${Object.entries(FLIGHT_TIMES).map(([t,lab])=>`<button class="chip ${flightKind(f)===t?'loc-on':''}" data-ft="${t}" ${dis} title="${minToHM(FLIGHT_WINDOWS[t][0])}–${FLIGHT_WINDOWS[t][1]>=1440?'24:00':minToHM(FLIGHT_WINDOWS[t][1])}, alarm ${minToHM(FLIGHT_ALARM[t])}">${lab} <span class="hint">${minToHM(FLIGHT_WINDOWS[t][0])}–${FLIGHT_WINDOWS[t][1]>=1440?'24':minToHM(FLIGHT_WINDOWS[t][1]).replace(':00','')}</span></button>`).join('')}
      </div>
      <div class="row2">
        ${editable?`<label class="hint">Departs <input type="time" data-fx="dep" value="${minToHM(flightMins(f).a)}" step="300" style="flex:0 0 auto;width:auto"></label><label class="hint">Arrives <input type="time" data-fx="arr" value="${flightMins(f).b>=1440?'23:59':minToHM(flightMins(f).b)}" step="300" style="flex:0 0 auto;width:auto"></label><label class="hint">⏰ Alarm <input type="time" data-fx="alarm" value="${flightAlarmMin(f)===null?'':minToHM(flightAlarmMin(f))}" step="300" style="flex:0 0 auto;width:auto"></label><label class="hint"><input type="checkbox" data-falarm ${flightAlarmMin(f)===null?'':'checked'}> alarm on</label>${flightMins(f).custom||f.alarm?`<button class="chip" data-freset title="Back to the default ${esc(FLIGHT_TIMES[flightKind(f)])} hours and alarm">↺ default</button>`:'<span class="hint">default hours</span>'}<span class="hint" style="flex-basis:100%">Times are local to ${esc(tzShort(flightTz(f)))} (departure)${flightTz(f)!==ACTIVE_TZ?' · in the calendar: '+esc(flightDisp(k,f).replace(/ \(.*$/,''))+' '+esc(tzShort(ACTIVE_TZ)):''}</span>`
                  :`<span class="hint">${esc(flightTimeLabel(f))}</span>`}
      </div>
      <div class="row2">
        ${editable?`<input type="text" data-ff="note" value="${esc(f.note||'')}" placeholder="Flight number, e.g. A3 610">`:(f.note?`<span class="hint">${esc(f.note)}</span>`:'')}
      </div>
    </div>`).join('');
  const d0=d.getTime(), d1=addDays(d,1).getTime();
  const occ=occurrences(d0,d1,{all:true}).filter(o=>o.kind!=='busy' && (privKey||!isPrivate(o.item)));
  const evs=occ.filter(o=>o.kind==='event'), blocks=occ.filter(o=>o.kind==='block');
  const due=dueTasksOn(k,{all:true});
  const evRows=evs.map(o=>`<div class="trow" style="--c:${esc(itemColor(o.item))};grid-template-columns:1fr auto" data-occ="${esc(o.id)}" data-oc="${esc(o.occ)}" data-ok="event"><span class="tt">${o.cancelled?'<s>':''}${esc(o.title)}${o.cancelled?'</s>':''}</span><span class="hint">${o.allDay?'All day':hm(o.s)+'–'+hm(o.e)}</span>${o.location?`<div class="meta">📍 ${esc(o.location)}</div>`:''}</div>`).join('');
  const blRows=blocks.map(o=>`<div class="trow ${o.done?'done':''}" style="--c:${esc(itemColor(o.item))};grid-template-columns:auto 1fr auto" data-occ="${esc(o.id)}" data-oc="${esc(o.occ)}" data-ok="block"><span>☐</span><span class="tt">${esc(o.title)}</span><span class="hint">${hm(o.s)}–${hm(o.e)}</span></div>`).join('');
  $('#drawer').innerHTML = `
    <div class="dhead">
      <button class="ghost" id="dPrev" title="Previous day">‹</button>
      <h2>${esc(fmtLong(d))}<small>Week ${isoWeek(d)}${we?' · weekend':''}</small></h2>
      <button class="ghost" id="dNext" title="Next day">›</button>
      <button class="ghost" id="dClose" title="Close (Esc)">✕</button>
    </div>
    <div class="dbody">
      ${editable?'':'<div class="ro-note">You are signed in with a read-only account.</div>'}
      ${we?'<div class="hint">Weekend — no work status recorded.</div>':`
      <div class="sec"><h4>Work status <span style="text-transform:none;letter-spacing:0;font-weight:500">morning ${esc(S().workStart)}–${esc(S().midday)} · evening ${esc(S().midday)}–${esc(S().workEnd)}</span></h4>
        <div class="half"><span>Whole day</span><div class="chips">${chipRow('both')}</div></div>
        <div class="half"><span>Morning</span><div class="chips">${chipRow('am')}</div></div>
        <div class="half"><span>Evening</span><div class="chips">${chipRow('pm')}</div></div>
      </div>`}
      <div class="sec"><h4>Location <span style="text-transform:none;letter-spacing:0;font-weight:500">${!loc && l?`auto: ${esc(l.name)} (from last flight)`:''}</span></h4>
        <div class="chips" style="margin-bottom:8px">
          <button class="chip ${!loc?'loc-on':''}" data-loc="" ${dis} title="Use the destination of the latest flight">Auto</button>
          <button class="chip ${loc.toLowerCase()==='athens'?'loc-on':''}" data-loc="Athens" ${dis}>Athens</button>
          <button class="chip ${loc.toLowerCase()==='brussels'?'loc-on':''}" data-loc="Brussels" ${dis}>Brussels</button>
          <button class="chip ${isOther?'loc-on':''}" data-loc="?" ${dis}>Other…</button>
        </div>
        ${(isOther || $('#drawer').dataset.other===k) ? `<input type="text" id="locOther" value="${esc(isOther?loc:'')}" placeholder="Type a location, e.g. London" ${dis}>`:''}
      </div>
      <div class="sec"><h4>Travel</h4>
        ${flights || '<div class="hint" style="margin-bottom:8px">No flights this day.</div>'}
        ${editable?`<div class="chips">
          <button class="chip" data-add="Athens|Brussels">＋ Athens → Brussels</button>
          <button class="chip" data-add="Brussels|Athens">＋ Brussels → Athens</button>
          <button class="chip" data-add="|">＋ Other flight</button></div>`:''}
      </div>
      <div class="sec"><h4>Comments</h4>
        <textarea id="dComment" placeholder="${editable?'Anything to note for this day…':'—'}" ${dis}>${esc(e.comment||'')}</textarea>
      </div>
      <div class="sec"><h4><span>Events</span>${editable?'<button class="chip" data-dnew="event">＋ Event</button>':''}</h4>
        <div class="tl">${evRows||'<div class="hint">No events.</div>'}</div></div>
      <div class="sec"><h4><span>Tasks</span>${editable?'<button class="chip" data-dnew="task">＋ Task due this day</button>':''}</h4>
        <div class="tl">${blRows}${due.map(t=>taskRowHTML(t)).join('')||(!blRows?'<div class="hint">No tasks.</div>':'')}</div></div>
    </div>`;

  const D=$('#drawer');
  $('#dPrev').onclick=()=>stepDrawer(-1); $('#dNext').onclick=()=>stepDrawer(1); $('#dClose').onclick=closeDrawer;
  $$('[data-occ]',D).forEach(r=>r.onclick=()=>openOccPopover(r.dataset.occ,r.dataset.oc,r.dataset.ok,r));
  bindTaskRows(D);
  if(!editable) return;
  $$('[data-dnew]',D).forEach(b=>b.onclick=()=>{ if(b.dataset.dnew==='event'){ const tz=ACTIVE_TZ; openEventEditor(null,newEvent({start:k+'T'+S().workStart, end:localAddMin(k+'T'+S().workStart,S().defaultEventMin||60), tz})); } else openTaskEditor(null,newTask({dueDate:k})); });
  const commit=(rerenderDrawer=true)=>{ prune(k); changed(); render(); renderSide(); if(rerenderDrawer) renderDrawer(); };
  $$('[data-half]',D).forEach(b=>b.onclick=()=>{
    const h=b.dataset.half, s=b.dataset.s, m=dayMut(k);
    if(h==='both'){ m.am=s; m.pm=s; } else m[h]=s;
    commit();
    if(OFF_STATUSES.includes(s)) offerMoveWorkBlocks(k);
  });
  $$('[data-loc]',D).forEach(b=>b.onclick=()=>{
    const v=b.dataset.loc, m=dayMut(k);
    if(v==='?'){ D.dataset.other=k; renderDrawer(); const i=$('#locOther'); i&&i.focus(); return; }
    D.dataset.other=''; m.location=v; commit();
  });
  const lo=$('#locOther');
  if(lo) lo.oninput=()=>{ dayMut(k).location=lo.value.trim(); prune(k); changed('loc'+k); render(); };
  $$('[data-add]',D).forEach(b=>b.onclick=()=>{
    const [from,to]=b.dataset.add.split('|'); const m=dayMut(k);
    (m.flights=m.flights||[]).push({from,to,time:'morning'});
    commit();
    if(!from){ const ins=$$('.flight input[data-ff="from"]',D); ins.length && ins[ins.length-1].focus(); }
  });
  $$('.flight',D).forEach(fe=>{
    const i=+fe.dataset.i;
    $$('[data-ft]',fe).forEach(b=>b.onclick=()=>{ const fl=day(k).flights[i]; fl.time=b.dataset.ft; delete fl.dep; delete fl.arr; delete fl.alarm; commit(); });
    $$('[data-fx]',fe).forEach(inp=>inp.onchange=()=>{ const fl=day(k).flights[i]; const v=inp.value, n=inp.dataset.fx;
      if(n==='alarm'){ if(!v) fl.alarm='off'; else fl.alarm=v; } else if(!v) delete fl[n]; else fl[n]=v;
      const kd=flightKind(fl), d=FLIGHT_WINDOWS[kd];
      if(fl.dep===minToHM(d[0]) && (fl.arr===minToHM(d[1])||(d[1]>=1440&&fl.arr==='23:59')||!fl.arr)){ delete fl.dep; delete fl.arr; }
      if(fl.alarm && fl.alarm!=='off' && fl.alarm===minToHM(flightAlarmMin({...fl,alarm:undefined}))) delete fl.alarm;
      commit(); });
    const fa=$('[data-falarm]',fe); if(fa) fa.onchange=()=>{ const fl=day(k).flights[i]; if(fa.checked) delete fl.alarm; else fl.alarm='off'; commit(); };
    const fr=$('[data-freset]',fe); if(fr) fr.onclick=()=>{ const fl=day(k).flights[i]; delete fl.dep; delete fl.arr; delete fl.alarm; commit(); };
    $$('[data-ff]',fe).forEach(inp=>inp.oninput=()=>{ day(k).flights[i][inp.dataset.ff]=inp.value; changed('fl'+k+i); render(); });
    const del=$('[data-fdel]',fe); if(del) del.onclick=()=>{ day(k).flights.splice(i,1); commit(); };
  });
  const c=$('#dComment');
  c.oninput=()=>{ dayMut(k).comment=c.value; prune(k); changed('cm'+k); render(); };
}
/* setting a day to Leave/Sick offers to move that day's work blocks */
function offerMoveWorkBlocks(k){
  const bl=data.tasks.filter(t=>t.calendarId==='work' && isOpen(t)).flatMap(t=>t.blocks.filter(b=>!b.done && b.start.slice(0,10)===k && offOverlap(k,zonedToMs(b.start,b.tz),zonedToMs(b.end,b.tz)).length).map(b=>({t,b})));
  if(!bl.length) return;
  toast(`${bl.length} work task block${bl.length>1?'s are':' is'} planned during the time off.`,{actions:[['Move to next free slots',()=>{ for(const {t,b} of bl) rescheduleBlock(t,b,true); changed(); renderAll(); toast('Moved.',{actions:[['Undo',undo]]}); }]]});
}

/* ---------- selection bar: bulk work status (days) or bulk task actions ---------- */
function renderSelbar(){
  const bar=$('#selbar');
  if(editable && selTasks.size){
    bar.innerHTML=`<b>${selTasks.size} task${selTasks.size>1?'s':''} selected</b>
      <button data-bt="project">Project…</button><button data-bt="priority">Priority…</button><button data-bt="due">Due date…</button><button data-bt="status">Status…</button>
      <button data-bt="schedule">Auto-schedule</button><button data-bt="delete">Delete</button><span class="sep"></span><button data-bt="clear">Done</button>`;
    bar.classList.add('open');
    $$('[data-bt]',bar).forEach(b=>b.onclick=()=>bulkTasks(b.dataset.bt));
    return;
  }
  if(!editable || !selected.size){ bar.classList.remove('open'); return; }
  const wd=[...selected].filter(k=>!isWeekend(fromKey(k))).length;
  bar.innerHTML = `<b>${selected.size} day${selected.size>1?'s':''} selected</b>`
    + Object.entries(STATUSES).map(([s,v])=>`<button data-bs="${s}" ${wd?'':'disabled'}><i class="sw" style="background:var(--${s});width:9px;height:9px"></i> ${v.short}</button>`).join('')
    + `<button data-bs="" ${wd?'':'disabled'}>Clear status</button><span class="sep"></span>`
    + `<button data-bl="Athens">📍 Athens</button><button data-bl="Brussels">📍 Brussels</button><button data-bl="?">📍 Other…</button><button data-bl="">📍 Auto</button><span class="sep"></span>`
    + `<button data-bx="1">Done</button>`;
  bar.classList.add('open');
  $$('[data-bs]',bar).forEach(b=>b.onclick=()=>{ const s=b.dataset.bs; selected.forEach(k=>{ if(isWeekend(fromKey(k))) return; const e=dayMut(k); e.am=s; e.pm=s; prune(k); }); changed(); render(); });
  $$('[data-bl]',bar).forEach(b=>b.onclick=async()=>{ let v=b.dataset.bl; if(v==='?'){ v=await promptDlg('Location','Location for the selected days:',''); if(v==null) return; v=v.trim(); } selected.forEach(k=>{ dayMut(k).location=v; prune(k); }); changed(); render(); });
  $('[data-bx]',bar).onclick=()=>{ selected.clear(); render(); };
}
async function bulkTasks(a){
  const ts=[...selTasks].map(task).filter(Boolean);
  const done=()=>{ ts.forEach(touch); changed(); renderAll(); toast(`Updated ${ts.length} tasks`,{actions:[['Undo',undo]]}); };
  if(a==='clear'){ selTasks.clear(); renderTasksPanel(); return; }
  if(a==='delete'){ if(!await confirmDlg(`Delete ${ts.length} tasks?`,[['Delete','y','danger'],['Cancel',null]])) return; ts.forEach(t=>removeItem(t,true)); selTasks.clear(); changed(); renderAll(); toast('Deleted',{actions:[['Undo',undo]]}); return; }
  if(a==='schedule'){ openAutoSchedule(ts); return; }
  const opts={
    project:{label:'Project', html:`<select id="bv"><option value="">Inbox</option>${data.projects.filter(p=>p.status!=='archived').map(p=>`<option value="${p.id}">${esc(cal(p.calendarId).name)} · ${esc(p.name)}</option>`).join('')}</select>`},
    priority:{label:'Priority', html:`<select id="bv">${[1,2,3,4].map(p=>`<option value="${p}">P${p}</option>`).join('')}</select>`},
    due:{label:'Due date', html:`<input type="date" id="bv" value="${todayKey()}">`},
    status:{label:'Status', html:`<select id="bv">${Object.entries(TASK_STATUS).map(([k,v])=>`<option value="${k}">${v}</option>`).join('')}</select>`},
  }[a];
  const d=openDialog(`<div class="dhd"><h2>${opts.label} for ${ts.length} tasks</h2></div>${opts.html}<div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" id="bok">Apply</button></div>`,{size:'narrow'});
  $('#bok',d).onclick=()=>{
    const v=$('#bv',d).value; closeDialog(d);
    for(const t of ts){
      if(a==='project'){ if(v){ const p=proj(v); if(isPrivCal(p.calendarId)&&!privKey) continue; t.projectId=v; t.calendarId=p.calendarId; } else t.projectId=null; }
      if(a==='priority') t.priority=+v; if(a==='due') t.dueDate=v||null;
      if(a==='status'){ t.status=v; t.completedAt=v==='done'?nowISO():null; }
    }
    done();
  };
}
