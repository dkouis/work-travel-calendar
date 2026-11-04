/* ============================================================
   Search palette, keyboard shortcuts, top bar and menus
   ============================================================ */
function openSearch(initial){
  let act=0, results=[];
  const d=openDialog(`<div class="pal"><input type="search" id="palQ" placeholder="Search events, tasks, projects, days, flights…   filters: project: tag: is:overdue is:done type:task before:2026-12-01 after:" value="${esc(initial||'')}" aria-label="Search"><div class="res" id="palR" role="listbox"></div></div>`,{size:'mid',focus:'#palQ'});
  const q=$('#palQ',d), out=$('#palR',d);
  const run=()=>{
    let text=q.value.trim(); const fl={};
    text=text.replace(/(project|tag|is|type|before|after|cal):("[^"]+"|\S+)/gi,(m,k,v)=>{ fl[k.toLowerCase()]=v.replace(/"/g,'').toLowerCase(); return ''; }).trim().toLowerCase();
    const words=text.split(/\s+/).filter(Boolean).map(w=>[...w].map(fold1).join(''));
    const hit=s=>{ const f=[...(s||'')].map(fold1).join(''); return words.every(w=>f.includes(w)); };
    const pmatch=it=>!fl.project || ((proj(it.projectId)||{name:'inbox'}).name.toLowerCase().includes(fl.project));
    const tmatch=it=>!fl.tag || (it.tags||[]).some(t=>t.toLowerCase().includes(fl.tag));
    const cmatch=it=>!fl.cal || cal(it.calendarId).name.toLowerCase().startsWith(fl.cal);
    const dkey=it=>it.type==='event'?it.start.slice(0,10):(it.dueDate||(it.blocks[0]&&it.blocks[0].start.slice(0,10))||'');
    const dmatch=it=>{ const k=dkey(it); return (!fl.before || (k && k<fl.before)) && (!fl.after || (k && k>fl.after)); };
    const ismatch=it=>!fl.is || (fl.is==='overdue'?it.type==='task'&&isOverdue(it):fl.is==='done'?it.type==='task'&&!isOpen(it):fl.is==='open'?it.type==='task'&&isOpen(it):fl.is==='recurring'?!!it.rrule:fl.is==='private'?isPrivate(it):true);
    const okType=t=>!fl.type || fl.type.startsWith(t.slice(0,2));
    const pool={events:[...data.events,...(archive?archive.events.map(x=>({...x,_arch:true})):[])], tasks:[...data.tasks,...(archive?archive.tasks.map(x=>({...x,_arch:true})):[])]};
    results=[];
    if(!words.length && !Object.keys(fl).length){ out.innerHTML='<div class="hint" style="padding:8px">Type to search. Try <kbd>is:overdue</kbd>, <kbd>project:council</kbd>, <kbd>tag:budget</kbd>, <kbd>type:event after:2026-11-01</kbd>.</div>'; return; }
    const filt2=it=>pmatch(it)&&tmatch(it)&&cmatch(it)&&dmatch(it)&&ismatch(it);
    if(okType('events')) for(const e of pool.events) if(filt2(e) && hit([e.title,e.notes,e.location,e.tags.join(' '),e.attendees.join(' '),Object.values(e.exceptions).map(x=>x.title||'').join(' ')].join(' '))) results.push({g:'Events', label:(e.rrule?'🔁 ':'')+e.title, d:(e.allDay?fmtShort(fromKey(e.start.slice(0,10))):fmtShort(fromKey(e.start.slice(0,10)))+' '+e.start.slice(11,16))+(e._arch?' · archive':''), c:itemColor(e), go:()=>{ if(e._arch) return; openEventEditor(e.id); }, k:e.start});
    if(okType('tasks')) for(const t of pool.tasks) if(filt2(t) && hit([t.title,t.notes,t.tags.join(' '),t.checklist.map(c=>c.text).join(' ')].join(' '))) results.push({g:'Tasks', label:(isOpen(t)?'☐ ':'✓ ')+t.title, d:(t.dueDate?'due '+fmtShort(fromKey(t.dueDate)):'')+(t._arch?' · archive':''), c:itemColor(t), go:()=>{ if(t._arch) return; openTaskEditor(t.id); }, k:t.dueDate||''});
    if(!fl.type && !fl.is && !fl.tag) for(const p of data.projects) if(hit(p.name+' '+p.description)) results.push({g:'Projects', label:(p.icon||'')+' '+p.name, d:PROJ_STATUS[p.status], c:p.color, go:()=>openProjectPage(p.id), k:''});
    if(!Object.keys(fl).length) for(const [k,e] of Object.entries(data.days)){
      if(e.comment && hit(e.comment)) results.push({g:'Day comments', label:e.comment.slice(0,80), d:fmtShort(fromKey(k))+' '+fromKey(k).getFullYear(), go:()=>{ closeDialog(d); cursor=fromKey(k); render(); openDrawer(k); }, k});
      for(const f of e.flights||[]) if(hit(`${f.from} ${f.to} ${code(f.from)} ${code(f.to)} ${f.note||''} flight πτήση`)) results.push({g:'Flights', label:`✈ ${f.from} → ${f.to}`, d:fmtShort(fromKey(k))+' '+fromKey(k).getFullYear(), go:()=>{ closeDialog(d); cursor=fromKey(k); render(); openDrawer(k); }, k});
    }
    const order=['Events','Tasks','Projects','Flights','Day comments'];
    results.sort((a,b)=>order.indexOf(a.g)-order.indexOf(b.g)||String(b.k).localeCompare(String(a.k)));
    results=results.slice(0,120); act=0;
    let g='', html='';
    results.forEach((r,i)=>{ if(r.g!==g){ g=r.g; html+=`<div class="grp">${esc(g)} (${results.filter(x=>x.g===g).length})</div>`; } html+=`<div class="r ${i===act?'act':''}" data-r="${i}" role="option"><i class="dotc" style="background:${esc(r.c||'var(--line2)')}"></i><span>${esc(r.label)}</span><span class="d">${esc(r.d||'')}</span></div>`; });
    out.innerHTML=html||'<div class="rempty">Nothing found.</div>';
    $$('[data-r]',out).forEach(el=>el.onclick=()=>{ const r=results[+el.dataset.r]; closeDialog(d); r.go(); });
  };
  q.oninput=run;
  q.onkeydown=e=>{
    if(e.key==='ArrowDown'||e.key==='ArrowUp'){ e.preventDefault(); act=clamp(act+(e.key==='ArrowDown'?1:-1),0,results.length-1); $$('[data-r]',out).forEach((el,i)=>{ el.classList.toggle('act',i===act); if(i===act) el.scrollIntoView({block:'nearest'}); }); }
    if(e.key==='Enter' && results[act]){ e.preventDefault(); const r=results[act]; closeDialog(d); r.go(); }
  };
  run();
}

function openHelp(){
  const k=[['N','New event or task (menu)'],['Q','Quick add'],['/ or Ctrl+K','Search'],['T','Today'],['D 3 W M A B','Day, 3 days, Week, Month, Agenda, Board'],['← →','Previous / next (or day, when a day is open)'],['E','Edit the selected item'],['Space','Complete the selected task / block'],['Delete','Delete the selected item'],['Ctrl+Z / Ctrl+Shift+Z','Undo / redo (last 50 changes)'],['P','Plan my day'],['Esc','Close'],['?','This list'],
    ['In the time grid','Tab to the grid, arrows move by 30 min / day, Enter creates an item. Drag empty time to create, drag items to move, drag the lower edge to resize, Alt for 5-minute steps.']];
  openDialog(`<div class="dhd"><h2>⌨ Keyboard shortcuts</h2><button class="ghost" data-close>✕</button></div><div class="keys">${k.map(([a,b])=>`<span><kbd>${esc(a)}</kbd></span><span>${esc(b)}</span>`).join('')}</div>
    <div class="rsec">Quick add</div><div class="hint">Examples: <kbd>Meeting WG tue 10-12:30 #Council @Brussels</kbd> · <kbd>Draft text by fri 17:00 ~4h p1</kbd> · <kbd>Σύσκεψη αύριο 11:00</kbd> · <kbd>Pay rent 1/12 *monthly !personal</kbd>.<br># project, @ location, ! calendar, ~ estimate, p1–p4 priority, * repeat (daily, weekdays, weekly, monthly, yearly), “by” makes it a task with a due date.</div>
    <div class="dacts"><span class="sp"></span><button class="primary" data-close>OK</button></div>`,{size:'mid'});
}

/* ---------- top bar ---------- */
function bindTop(){
  $('#prev').onclick=()=>move(-1);
  $('#next').onclick=()=>move(1);
  $('#today').onclick=()=>{ cursor=TODAY(); miniCursor=null; render(); renderSide(); };
  $$('#viewSeg button').forEach(b=>b.onclick=()=>setView(b.dataset.view));
  $('#scrim').onclick=closeDrawer;
  $('#searchBtn').onclick=()=>openSearch();
  $('#bellBtn').onclick=openBell;
  const small=()=>innerWidth<1024;
  $('#sideBtn').onclick=()=>{ if(small()){ document.body.classList.toggle('showside'); document.body.classList.remove('showtasks'); } else { prefs.sidePanel=!prefs.sidePanel; savePrefs(); render(); } };
  $('#tasksBtn').onclick=()=>{ if(small()){ document.body.classList.toggle('showtasks'); document.body.classList.remove('showside'); } else { prefs.tasksPanel=!prefs.tasksPanel; savePrefs(); render(); } };
  const menus=[['#menuBtn','#menu'],['#newBtn','#newMenu'],['#repBtn','#repMenu'],['#tzBtn','#tzMenu']];
  for(const [b,m] of menus) $(b).onclick=e=>{ e.stopPropagation(); for(const [,o] of menus) if(o!==m) $(o).classList.remove('open'); $(m).classList.toggle('open'); };
  document.addEventListener('click',e=>{ for(const [,m] of menus) if(!e.target.closest(m)) $(m).classList.remove('open'); $$('details.dropdown[open]').forEach(dt=>{ if(!dt.contains(e.target)) dt.open=false; }); });
  $('#tzBtn').addEventListener('click',renderTzMenu,true);
  $('#tzMenu').onclick=async e=>{ const b=e.target.closest('[data-tz]'); if(!b) return; $('#tzMenu').classList.remove('open');
    let tz=b.dataset.tz;
    if(tz==='?'){ tz=await promptDlg('Other time zone','IANA time zone name, e.g. Europe/Madrid, America/New_York, Asia/Tokyo', ACTIVE_TZ,{validate:v=>validTz(v.trim())?null:'Unknown time zone name.'}); if(!tz) return; tz=tz.trim(); }
    setActiveTz(tz); };
  $('#newMenu').onclick=e=>{ const b=e.target.closest('[data-new]'); if(!b) return; $('#newMenu').classList.remove('open'); newThing(b.dataset.new); };
  $('#repMenu').onclick=e=>{ const b=e.target.closest('[data-rep]'); if(!b) return; $('#repMenu').classList.remove('open'); openReports(b.dataset.rep); };
  $('#menu').onclick=e=>{
    const b=e.target.closest('[data-act]'); if(!b||b.disabled) return;
    $('#menu').classList.remove('open');
    const a=b.dataset.act;
    if(a==='settings') openSettings();
    if(a==='refresh') reloadData(true);
    if(a==='print') window.print();
    if(a==='help') openHelp();
    if(a==='icsexp') openIcsExport();
    if(a==='export') exportBackup();
    if(a==='signout') signOut();
    if(a==='password') openChangePassword();
    if(!editable) return;
    if(a==='icsimp') pickFile('.ics,text/calendar',importIcsFile);
    if(a==='csvimp') pickFile('.csv,text/csv,.txt',importCsvFile);
    if(a==='owner') promptDlg('Calendar owner','Name shown in the header', data.owner||'').then(v=>{ if(v!=null){ data.owner=v.trim(); changed(); render(); } });
    if(a==='accounts') openAccounts();
    if(a==='history') openHistory();
    if(a==='import') pickFile('.json,.js,application/json,text/javascript',importBackupFile);
  };
  bindQuickAdd();
}
/* ---------- active time zone ---------- */
function setActiveTz(tz){
  if(!validTz(tz)) return;
  const prev=ACTIVE_TZ; if(tz===prev) return;
  ACTIVE_TZ=tz; prefs.activeTz = tz===BROWSER_TZ ? '' : tz; savePrefs();
  invalidateCaches(); _gridScroll=null; miniCursor=null; cursor=startOfDay(new Date(toDisp(fromDispWith(cursor.getTime()+12*3600000, prev))));
  renderAll(); renderTzLabel();
  toast(`🌍 Now showing ${tzShort(tz)} time (${tzOffsetLabel(tz)}). Events, tasks and flights moved to local times; new items are entered in ${tzShort(tz)} time.`,{ms:6000});
}
function fromDispWith(d, tz){ const keep=ACTIVE_TZ; ACTIVE_TZ=tz; const t=fromDisp(d); ACTIVE_TZ=keep; return t; }
function renderTzLabel(){ const l=$('#tzLbl'); if(!l) return; l.textContent=tzShort(ACTIVE_TZ); $('#tzBtn').title=`Active time zone: ${ACTIVE_TZ} (${tzOffsetLabel(ACTIVE_TZ)}). Times are entered and shown in it.`;
  const sug=tzSuggestion(); $('#tzBtn').classList.toggle('tzsug',!!sug); }
function tzSuggestion(){ if(!data) return null; const k=todayKey(), l=locationOf(k); const t=l && CITY_TZ[l.name.trim().toLowerCase()]; return t && t!==ACTIVE_TZ && tzOffsetMin(t,Date.now())!==tzOffsetMin(ACTIVE_TZ,Date.now()) ? {tz:t, city:l.name} : null; }
function renderTzMenu(){
  const now=Date.now(), sug=tzSuggestion();
  const clock=tz=>{ try{ return new Intl.DateTimeFormat('en-GB',{timeZone:tz,hour:'2-digit',minute:'2-digit'}).format(now); }catch(e){ return ''; } };
  const row=(tz,label)=>`<button data-tz="${esc(tz)}" class="${tz===ACTIVE_TZ?'on':''}" role="menuitemradio" aria-checked="${tz===ACTIVE_TZ}">${tz===ACTIVE_TZ?'✓ ':''}${esc(label||tz)} <span class="hint">· ${esc(tzOffsetLabel(tz,now))} · ${clock(tz)}</span></button>`;
  $('#tzMenu').innerHTML=`<div class="hint" style="padding:6px 10px">Times are entered and shown in the active time zone. Switch it when you travel.</div>
    ${sug?`<button data-tz="${esc(sug.tz)}" style="font-weight:600">📍 You are in ${esc(sug.city)} today → ${esc(sug.tz)} <span class="hint">· ${clock(sug.tz)}</span></button><hr>`:''}
    ${row(BROWSER_TZ,'This device: '+BROWSER_TZ)}<hr>
    ${TZ_LIST.filter(z=>z!==BROWSER_TZ).map(z=>row(z)).join('')}
    ${!TZ_LIST.includes(ACTIVE_TZ)?row(ACTIVE_TZ):''}<hr><button data-tz="?">Other time zone…</button>`;
}
function tzTravelPrompt(){
  const sug=tzSuggestion(); if(!sug) return;
  const k='wtc-tzsug:'+todayKey()+':'+sug.tz; if(safeGet(k)) return; safeSet(k,'1');
  toast(`📍 Your flights show you in ${sug.city} today. Show times in ${tzShort(sug.tz)} time?`,{actions:[['Switch to '+tzShort(sug.tz),()=>setActiveTz(sug.tz)]],ms:15000});
}
function newThing(kind){
  if(!editable) return;
  if(kind==='event'){ const k=view==='month'?key(cursor):(gridDays.includes(todayKey())?todayKey():key(cursor)); const n=new Date(NOW()); const st=k===todayKey()?minToHM(Math.min(23*60,Math.ceil((n.getHours()*60+n.getMinutes())/30)*30)):S().workStart; return openEventEditor(null,newEvent({start:k+'T'+st,end:localAddMin(k+'T'+st,S().defaultEventMin||60),tz:locTz(k)})); }
  if(kind==='task') return openTaskEditor(null);
  if(kind==='project') return openProjectEditor(null);
  if(kind==='template') return openTemplates();
  if(kind==='plan') return openPlanDay();
}

/* ---------- keyboard ---------- */
document.addEventListener('keydown',e=>{
  if(document.body.classList.contains('locked')) return;
  const typing=/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) || document.activeElement.isContentEditable;
  if(e.key==='Escape'){
    if($('#focus').classList.contains('open')){ endFocus(); return; }
    if($('#pop')){ closePopover(); return; }
    if(_dlgStack.length){ closeDialog(); return; }
    if(openDay){ closeDrawer(); return; }
    if(selected.size||selTasks.size){ selected.clear(); selTasks.clear(); renderAll(); return; }
    return;
  }
  if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='k'){ e.preventDefault(); openSearch(); return; }
  if(typing) return;
  if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='z'){ e.preventDefault(); e.shiftKey?redo():undo(); return; }
  if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='y'){ e.preventDefault(); redo(); return; }
  if(e.ctrlKey||e.metaKey||e.altKey) return;
  if(_dlgStack.length) return;
  const k=e.key;
  if(k==='ArrowLeft'){ openDay ? stepDrawer(-1) : move(-1); return; }
  if(k==='ArrowRight'){ openDay ? stepDrawer(1) : move(1); return; }
  const lk=k.toLowerCase();
  if(lk==='t') $('#today').click();
  else if(lk==='d') setView('day'); else if(k==='3') setView('3day'); else if(lk==='w') setView('week'); else if(lk==='m') setView('month'); else if(lk==='a') setView('agenda'); else if(lk==='b') setView('board');
  else if(k==='/'){ e.preventDefault(); openSearch(); }
  else if(lk==='q'){ e.preventDefault(); $('#qa').focus(); }
  else if(lk==='n' && editable){ e.preventDefault(); $('#newMenu').classList.add('open'); $('#newMenu button').focus(); }
  else if(lk==='p') openPlanDay();
  else if(k==='?') openHelp();
  else if(lk==='e' && selItem){ const it=findItem(selItem.id); if(it) it.type==='event'?openEventEditor(it.id,null,selItem.occ):openTaskEditor(it.id); }
  else if(k===' ' && selItem && editable){ e.preventDefault(); if(selItem.kind==='block') toggleBlockDone(selItem.id,selItem.occ); else if(selItem.kind==='task') toggleTaskDone(selItem.id); }
  else if((k==='Delete'||k==='Backspace') && selItem && editable){ e.preventDefault(); const it=findItem(selItem.id); if(!it) return; if(it.type==='task' && selItem.kind==='task') { confirmDlg({title:'Delete task',html:`Delete “${esc(it.title)}”?`},[['Delete','y','danger'],['Cancel',null]]).then(y=>{ if(y) removeItem(it); }); } else deleteOcc(it.id, selItem.occ||it.start.slice(0,10), selItem.kind); }
});
window.addEventListener('beforeunload',e=>{ if(editable && (dirty || saving)){ e.preventDefault(); e.returnValue=''; } });
