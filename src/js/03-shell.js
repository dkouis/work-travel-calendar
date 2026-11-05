/* ============================================================
   Shell: render all, side panel, tasks panel, dialogs, toasts, banner
   ============================================================ */
function renderAll(){
  if(!data || !session) return;
  if(DETAIL){ renderDetail(); renderTimerSlot(); renderTzLabel(); refreshOpenPanels(); return; }
  render(); renderSide(); renderTasksPanel(); renderBell(); renderTimerSlot(); renderTzLabel();
  if(openDay) renderDrawer();
  refreshOpenPanels();
}
const _panelRefreshers = new Set();
function refreshOpenPanels(){ for(const f of [..._panelRefreshers]) try{ f(); }catch(e){ console.error(e); } }

/* ---------- dialogs ---------- */
let _dlgStack=[];
function openDialog(html, opts={}){
  const d=document.createElement('div'); d.className='dlg'; d.setAttribute('role','dialog'); d.setAttribute('aria-modal','true');
  d.innerHTML=`<div class="box ${opts.size||''}">${html}</div>`;
  document.body.appendChild(d); _dlgStack.push(d);
  d.addEventListener('mousedown', e=>{ if(e.target===d && !opts.sticky) closeDialog(d); });
  $$('[data-close]',d).forEach(b=>b.onclick=()=>closeDialog(d));
  d._onclose = opts.onclose;
  if(opts.refresh){ d._refresh=opts.refresh; _panelRefreshers.add(opts.refresh); }
  setTimeout(()=>{ const f=$(opts.focus||'[autofocus],input:not([type=hidden]):not([disabled]),select,textarea,button.primary',d); f&&f.focus(); },0);
  return d;
}
function closeDialog(d){
  d = d || _dlgStack[_dlgStack.length-1]; if(!d) return;
  _dlgStack=_dlgStack.filter(x=>x!==d); if(d._refresh) _panelRefreshers.delete(d._refresh);
  d.remove(); if(d._onclose) d._onclose();
}
function topDialog(){ return _dlgStack[_dlgStack.length-1]; }
function confirmDlg(msg, buttons){ // buttons: [[label,value,cls]]
  return new Promise(res=>{
    const d=openDialog(`<div class="dhd"><h2>${msg.title?esc(msg.title):'Please confirm'}</h2></div><p>${msg.html||esc(msg)}</p>
      <div class="dacts"><span class="sp"></span>${buttons.map((b,i)=>`<button data-i="${i}" class="${b[2]||''}">${esc(b[0])}</button>`).join('')}</div>`, {size:'narrow', onclose:()=>res(null)});
    $$('[data-i]',d).forEach(b=>b.onclick=()=>{ d._onclose=null; closeDialog(d); res(buttons[+b.dataset.i][1]); });
  });
}
function promptDlg(title, label, value, opts={}){
  return new Promise(res=>{
    const d=openDialog(`<div class="dhd"><h2>${esc(title)}</h2></div>
      <form><label class="hint">${esc(label)}</label><input type="${opts.type||'text'}" id="pdv" value="${esc(value||'')}" style="margin-top:6px" ${opts.attrs||''}>
      ${opts.extra||''}<div class="lerr" id="pde" style="margin-top:6px"></div>
      <div class="dacts"><span class="sp"></span><button type="button" data-close>Cancel</button><button class="primary" type="submit">${esc(opts.ok||'OK')}</button></div></form>`, {size:'narrow', onclose:()=>res(null)});
    $('form',d).onsubmit=async e=>{ e.preventDefault(); const v=$('#pdv',d).value; if(opts.validate){ const err=await opts.validate(v,d); if(err){ $('#pde',d).textContent=err; return; } } d._onclose=null; closeDialog(d); res(opts.full?{v,d}:v); };
  });
}

/* ---------- toasts ---------- */
function toast(msg, opts={}){
  const t=document.createElement('div'); t.className='toast simple'; t.setAttribute('role','status');
  t.innerHTML=`<div class="th"><b style="font-weight:500">${opts.html?msg:esc(msg)}</b><button class="ghost" data-x style="padding:0 4px">✕</button></div>${opts.actions?`<div class="ta">${opts.actions.map((a,i)=>`<button data-a="${i}">${esc(a[0])}</button>`).join('')}</div>`:''}`;
  $('#toasts').appendChild(t);
  const simple=$$('#toasts .toast.simple'); simple.slice(0,Math.max(0,simple.length-3)).forEach(x=>x.remove()); // keep the 3 newest
  const kill=()=>t.remove();
  $('[data-x]',t).onclick=kill;
  (opts.actions||[]).forEach((a,i)=>$(`[data-a="${i}"]`,t).onclick=()=>{ kill(); a[1](); });
  if(!opts.sticky) setTimeout(kill, opts.ms||(opts.actions?10000:3500));
  return t;
}

/* ---------- banner ---------- */
let _banner=null;
function showBanner(html, cls, actions){ _banner={html,cls,actions}; renderBanner(); }
function renderBanner(){
  const b=$('#banner'); b.className=''; b.innerHTML='';
  if(editable && conflict){
    b.className='show warn';
    b.innerHTML = `<span>⚠ <b>${esc(conflict.by||'Someone')}</b> saved the calendar at ${esc(fmtWhen(conflict.saved))} and the changes could not be combined automatically. Your latest change was <b>not saved</b>.</span>`
      + '<button id="bTheirs">Load their version (drop my change)</button><button class="primary" id="bMine">Save mine over it</button>';
    $('#bTheirs').onclick=async()=>{ conflict=null; dirty=false; clearTimeout(saveTimer); try{ await loadData(true); }catch(e){ alert(e.message); } afterModeChange(); };
    $('#bMine').onclick=()=>{ rev=conflict.rev; conflict=null; dirty=true; setSave('Saving…'); renderBanner(); saveNow(); };
  } else if(editable && netError && dirty){
    b.className='show warn';
    b.innerHTML = '<span>⚠ The server cannot be reached, so your latest change is not saved yet. Keep this page open; it keeps trying.</span><button id="bRetry">Try now</button>';
    $('#bRetry').onclick=()=>{ clearTimeout(saveTimer); saveNow(); };
  } else if(mergeNote){
    b.className='show';
    b.innerHTML = `<span>🔀 Combined your changes with <b>${esc(mergeNote.by)}</b>’s, who saved at the same time.${mergeNote.conflicts.length?` ${mergeNote.conflicts.length} item${mergeNote.conflicts.length>1?'s were':' was'} changed by both of you; the newer version was kept: <i>${mergeNote.conflicts.slice(0,6).map(esc).join('; ')}${mergeNote.conflicts.length>6?'…':''}</i>`:''}</span><button id="bOk">OK</button>`;
    $('#bOk').onclick=()=>{ mergeNote=null; renderBanner(); };
  } else if(sizeWarn && editable){
    b.className='show warn';
    b.innerHTML = '<span>⚠ The calendar is getting close to the server’s 3 MB limit. Move old items to an archive file (⚙ Settings → Data).</span><button id="bArch">Open settings</button>';
    $('#bArch').onclick=()=>openSettings('data');
  } else if(_banner){
    b.className='show '+(_banner.cls||''); b.innerHTML=`<span>${_banner.html}</span>`+(_banner.actions||[]).map((a,i)=>`<button data-ba="${i}">${esc(a[0])}</button>`).join('');
    (_banner.actions||[]).forEach((a,i)=>$(`[data-ba="${i}"]`,b).onclick=a[1]);
  }
  setTopH();
}
function setTopH(){ const h=$('#topbar').offsetHeight; document.documentElement.style.setProperty('--topH', h+'px'); }
addEventListener('resize', debounce(()=>{ setTopH(); render(); },150));

/* ---------- side panel ---------- */
let miniCursor = null;
function renderSide(){
  const el=$('#side'); if(!el || !data) return;
  if(!miniCursor) miniCursor=new Date(cursor.getFullYear(),cursor.getMonth(),1);
  const y=miniCursor.getFullYear(), m=miniCursor.getMonth(), first=new Date(y,m,1), last=new Date(y,m+1,0);
  const tk=key(TODAY());
  const [vs,ve]=visibleRange();
  const busyDays=new Set(occurrences(first.getTime(), addDays(last,1).getTime()).map(o=>key(new Date(o.s))));
  let g=DOW.map(d=>`<span class="h">${d[0]}</span>`).join('');
  for(let d=mondayOf(first); d<=addDays(mondayOf(last),6); d=addDays(d,1)){
    const k=key(d), inv=d>=vs && d<ve;
    g+=`<span data-mk="${k}" class="${d.getMonth()!==m?'o':''} ${k===tk?'t':''} ${inv?'inv':''} ${busyDays.has(k)?'has':''}">${d.getDate()}</span>`;
  }
  const projRow=(p,sub)=>{
    const open=data.tasks.filter(t=>t.projectId===p.id && isOpen(t)).length;
    const od=data.tasks.filter(t=>t.projectId===p.id && isOverdue(t)).length;
    const st=projStats(p); const over=p.budgetHours && st.planned/60>p.budgetHours;
    return `<div class="srow ${sub?'sub':''} ${filt.projects.includes(p.id)?'on':''} ${p.status!=='active'?'off':''}" data-proj="${p.id}" draggable="${editable}" title="Click to show only this project; double-click to open it">
      <i class="dotc" style="background:${esc(p.color)}"></i><span class="nm">${esc(p.icon||'')} ${esc(p.name)}${p.pinned?' 📌':''}</span>
      ${over?'<span class="bw" title="Planned hours exceed the budget">€</span>':''}${od?`<span class="od" title="Overdue tasks">${od}</span>`:''}<span class="c">${open||''}</span>
      <button class="ghost" data-popen="${p.id}" title="Open project" style="padding:0 4px;font-size:12px">›</button></div>`;
  };
  let projHtml='';
  for(const c of [...data.calendars].sort((a,b)=>(a.order||0)-(b.order||0))){
    const ps=projectsOf(c.id).filter(p=>p.status!=='archived' && !p.parentId).sort((a,b)=>(b.pinned?1:0)-(a.pinned?1:0)||(a.order||0)-(b.order||0)||a.name.localeCompare(b.name));
    const locked = c.private && privLocked;
    const inboxOpen=data.tasks.filter(t=>!t.projectId && t.calendarId===c.id && isOpen(t)).length;
    projHtml+=`<div class="sideh"><span>${esc(c.name)}${c.private?' 🔒':''}</span>${editable&&!locked?`<button data-newproj="${c.id}" title="New project">＋</button>`:''}</div>`;
    if(locked){ projHtml+=`<div class="hint" style="padding:0 6px 6px">Personal items are locked on this device. <button class="lockbtn" data-unlock>🔑 Unlock</button></div>`; continue; }
    projHtml+=`<div class="srow ${filt.projects.includes('inbox:'+c.id)?'on':''}" data-proj="inbox:${c.id}"><i class="dotc" style="background:var(--line2)"></i><span class="nm">📥 Inbox</span><span class="c">${inboxOpen||''}</span></div>`;
    for(const p of ps){ projHtml+=projRow(p); for(const sp of data.projects.filter(x=>x.parentId===p.id && x.status!=='archived')) projHtml+=projRow(sp,true); }
  }
  const archived=data.projects.filter(p=>p.status==='archived');
  el.innerHTML=`
    <div class="mini"><div class="mh"><button data-mm="-1" aria-label="Previous month">‹</button><span>${MONTHS[m]} ${y}</span><button data-mm="1" aria-label="Next month">›</button></div><div class="g">${g}</div></div>
    <div class="sideh"><span>Calendars</span>${editable?'<button data-act2="cals" title="Manage calendars">⚙</button>':''}</div>
    ${data.calendars.map(c=>`<div class="srow ${filt.hiddenCals.includes(c.id)?'off':''}" data-cal="${c.id}" title="Show / hide"><span class="cbx" style="color:${esc(c.color)};background:${filt.hiddenCals.includes(c.id)?'transparent':esc(c.color)}">${filt.hiddenCals.includes(c.id)?'':'<span style="color:#fff">✓</span>'}</span><span class="nm">${esc(c.name)}</span>${c.private?(privLocked?'<span title="Locked">🔒</span>':(privKey?'<button class="ghost" data-lock title="Lock Personal items on this device" style="padding:0 4px">🔓</button>':'')):''}</div>`).join('')}
    <div class="sideh"><span>Projects</span>${filt.projects.length||filt.tag?'<button data-showall>Show all</button>':''}</div>
    ${projHtml}
    ${archived.length?`<details style="margin-top:8px"><summary class="hint" style="cursor:pointer">Archived projects (${archived.length})</summary>${archived.map(p=>projRow(p)).join('')}</details>`:''}
    <div class="sideh"><span>Saved filters</span><button data-savefilt title="Save the current filters">＋</button></div>
    ${prefs.savedFilters.map((f,i)=>`<div class="srow" data-sf="${i}"><span>⭐</span><span class="nm">${esc(f.name)}</span><button class="ghost" data-sfdel="${i}" title="Remove" style="padding:0 4px">✕</button></div>`).join('') || '<div class="hint" style="padding:0 6px">Save filter combinations here.</div>'}
    ${filt.tag?`<div class="sideh"><span>Tag</span></div><div class="srow on" data-cleartag>#${esc(filt.tag)} ✕</div>`:''}`;
  // events
  $$('[data-mm]',el).forEach(b=>b.onclick=()=>{ miniCursor=new Date(y,m+(+b.dataset.mm),1); renderSide(); });
  $$('[data-mk]',el).forEach(s=>s.onclick=()=>{ cursor=fromKey(s.dataset.mk); if(view==='month'||view==='board') setView('day'); else render(); renderSide(); });
  $$('[data-cal]',el).forEach(r=>r.onclick=e=>{ if(e.target.closest('[data-lock]')) return; const id=r.dataset.cal; filt.hiddenCals=filt.hiddenCals.includes(id)?filt.hiddenCals.filter(x=>x!==id):[...filt.hiddenCals,id]; saveFilter(); renderAll(); });
  $$('[data-proj]',el).forEach(r=>{
    r.onclick=e=>{ if(e.target.closest('[data-popen]')) return; const id=r.dataset.proj; if(e.ctrlKey||e.metaKey||e.shiftKey) filt.projects=filt.projects.includes(id)?filt.projects.filter(x=>x!==id):[...filt.projects,id]; else filt.projects=(filt.projects.length===1&&filt.projects[0]===id)?[]:[id]; saveFilter(); renderAll(); };
    r.ondblclick=()=>{ if(!r.dataset.proj.startsWith('inbox:')) openProjectPage(r.dataset.proj); };
    // drop items onto a project; drag projects to reorder
    r.addEventListener('dragover',e=>{ if(!editable) return; e.preventDefault(); r.classList.add('dragover'); });
    r.addEventListener('dragleave',()=>r.classList.remove('dragover'));
    r.addEventListener('drop',e=>{ r.classList.remove('dragover'); if(!editable) return; e.preventDefault(); const pid=r.dataset.proj; const dragged=e.dataTransfer.getData('text/x-proj'); const itemId=e.dataTransfer.getData('text/x-item')||e.dataTransfer.getData('text/x-task');
      if(dragged && !pid.startsWith('inbox:')) return reorderProject(dragged, pid);
      if(itemId) moveToProject([itemId], pid);
    });
    r.addEventListener('dragstart',e=>{ if(r.dataset.proj.startsWith('inbox:')) return e.preventDefault(); e.dataTransfer.setData('text/x-proj', r.dataset.proj); });
  });
  $$('[data-popen]',el).forEach(b=>b.onclick=()=>openProjectPage(b.dataset.popen));
  $$('[data-newproj]',el).forEach(b=>b.onclick=()=>openProjectEditor(null,{calendarId:b.dataset.newproj}));
  const sa=$('[data-showall]',el); if(sa) sa.onclick=()=>{ filt.projects=[]; filt.tag=''; saveFilter(); renderAll(); };
  const ct=$('[data-cleartag]',el); if(ct) ct.onclick=()=>{ filt.tag=''; saveFilter(); renderAll(); };
  $$('[data-unlock]',el).forEach(b=>b.onclick=()=>openUnlock());
  $$('[data-lock]',el).forEach(b=>b.onclick=async e=>{ e.stopPropagation(); if(dirty) await saveNow(); await lockPriv(); renderAll(); toast('Personal items locked on this device.'); });
  const ac=$('[data-act2=cals]',el); if(ac) ac.onclick=()=>openSettings('calendars');
  $('[data-savefilt]',el).onclick=async()=>{ const n=await promptDlg('Save filters','Name for this filter','',{ok:'Save'}); if(!n) return; prefs.savedFilters.push({name:n, filt:clone(filt), view}); savePrefs(); renderSide(); };
  $$('[data-sf]',el).forEach(r=>r.onclick=e=>{ if(e.target.closest('[data-sfdel]')) return; const f=prefs.savedFilters[+r.dataset.sf]; filt=Object.assign({},DEFAULT_FILTER,clone(f.filt)); saveFilter(); if(f.view) setView(f.view); else renderAll(); });
  $$('[data-sfdel]',el).forEach(b=>b.onclick=()=>{ prefs.savedFilters.splice(+b.dataset.sfdel,1); savePrefs(); renderSide(); });
}
function reorderProject(dragId, ontoId){
  const a=proj(dragId), b=proj(ontoId); if(!a||!b||a===b) return;
  const list=projectsOf(b.calendarId).filter(p=>!p.parentId).sort((x,y)=>(x.order||0)-(y.order||0));
  const rest=list.filter(p=>p!==a); rest.splice(rest.indexOf(b),0,a);
  if(a.calendarId!==b.calendarId){ a.calendarId=b.calendarId; }
  rest.forEach((p,i)=>{ p.order=i; }); touch(a); changed(); renderAll();
}
function moveToProject(ids, pid){
  const inbox=pid.startsWith('inbox:'), calId=inbox?pid.slice(6):proj(pid).calendarId;
  if(isPrivCal(calId) && !ensurePrivReadySync()) return;
  for(const id of ids){ const it=findItem(id); if(!it) continue; it.projectId=inbox?null:pid; it.calendarId=calId; touch(it); }
  changed(); renderAll(); toast(`Moved ${ids.length} item${ids.length>1?'s':''} to ${inbox?'Inbox':proj(pid).name}`);
}

/* ---------- tasks panel ---------- */
const SMART = {
  today:{label:'Today', f:t=>isOpen(t) && (t.dueDate===todayKey() || t.blocks.some(b=>b.start.slice(0,10)===todayKey() && !b.done))},
  overdue:{label:'Overdue', f:t=>isOverdue(t)},
  upcoming:{label:'Next 7 days', f:t=>isOpen(t) && t.dueDate && t.dueDate>todayKey() && t.dueDate<=keyAdd(todayKey(),7)},
  unscheduled:{label:'Unscheduled', f:t=>isOpen(t) && !t.blocks.some(b=>!b.done)},
  waiting:{label:'Waiting', f:t=>t.status==='waiting'},
  nodue:{label:'No due date', f:t=>isOpen(t) && !t.dueDate},
  all:{label:'All open', f:t=>isOpen(t)},
  done:{label:'Completed', f:t=>!isOpen(t)},
};
function todayKey(){ return key(TODAY()); }
function sortTasks(list, by){
  const pr=t=>t.priority||3, du=t=>t.dueDate?(t.dueDate+(t.dueTime||'99')):'9999';
  const cmp={due:(a,b)=>du(a).localeCompare(du(b))||pr(a)-pr(b), priority:(a,b)=>pr(a)-pr(b)||du(a).localeCompare(du(b)),
    project:(a,b)=>((proj(a.projectId)||{}).name||'~').localeCompare((proj(b.projectId)||{}).name||'~')||du(a).localeCompare(du(b)),
    estimate:(a,b)=>(b.estimateMin||0)-(a.estimateMin||0), title:(a,b)=>a.title.localeCompare(b.title)}[by]||(()=>0);
  return list.sort(cmp);
}
function taskRowHTML(t, opts={}){
  const c=itemColor(t), p=proj(t.projectId), od=isOverdue(t), bl=blockedBy(t).length, us=underScheduled(t);
  const plan=plannedMin(t);
  const due=t.dueDate?`<span class="${od?'od':''}">📅 ${esc(dueLabel(t))}</span>`:'';
  return `<div class="trow ${isOpen(t)?'':'done'} ${selTasks.has(t.id)?'sel':''}" style="--c:${esc(c)}" data-task="${t.id}" draggable="${editable}" tabindex="0" aria-label="Task ${esc(t.title)}">
    <button class="check ${isOpen(t)?'':'on'} pk" data-done="${t.id}" title="${isOpen(t)?'Mark done':'Mark not done'}" aria-label="Done" ${editable?'':'disabled'}>${isOpen(t)?'':'✓'}</button>
    <span class="tt">${bl?'🔒 ':''}${esc(t.title)}</span>
    <span>${t.priority&&t.priority<=2?`<span class="pri p${t.priority}">P${t.priority}</span>`:''}</span>
    <div class="meta">${due}${p?`<span>${esc(p.icon||'')} ${esc(p.name)}</span>`:''}${t.estimateMin?`<span class="${us?'warn':''}" title="${us?'Not enough time scheduled before the due date':''}">⏱ ${fmtH(plan)} of ${fmtH(t.estimateMin)}${us?' ⚠':''}</span>`:(plan?`<span>⏱ ${fmtH(plan)}</span>`:'')}${t.checklist.length?`<span>☑ ${t.checklist.filter(c=>c.done).length}/${t.checklist.length}</span>`:''}${t.rrule?'<span>🔁</span>':''}${isPrivate(t)?'<span>🔒</span>':''}${opts.sel&&editable?`<label style="margin-left:auto" onclick="event.stopPropagation()"><input type="checkbox" data-tsel="${t.id}" ${selTasks.has(t.id)?'checked':''}> select</label>`:''}</div>
  </div>`;
}
function dueLabel(t){
  if(!t.dueDate) return '';
  const dd=t.dueTime&&t.calendarId!==undefined?dueDisp(t):{k:t.dueDate,time:t.dueTime||null};
  const tk=todayKey(); let s;
  if(dd.k===tk) s='Today'; else if(dd.k===keyAdd(tk,1)) s='Tomorrow'; else if(dd.k===keyAdd(tk,-1)) s='Yesterday'; else s=fmtShort(fromKey(dd.k));
  return s+(dd.time?' '+dd.time:'');
}
function renderTasksPanel(){
  const el=$('#tasks'); if(!el || !data) return;
  const all=data.tasks.filter(passFilter);
  const counts=Object.fromEntries(Object.entries(SMART).map(([k,v])=>[k,all.filter(v.f).length]));
  let list=sortTasks(all.filter(SMART[tpTab].f), prefs.tpSort);
  if(tpTab==='done') list=list.sort((a,b)=>(b.completedAt||'').localeCompare(a.completedAt||''));
  const groups=new Map();
  for(const t of list){
    let g='';
    if(prefs.tpGroup==='project') g=(proj(t.projectId)||{name:'Inbox'}).name;
    else if(prefs.tpGroup==='status') g=TASK_STATUS[t.status||'todo'];
    else if(prefs.tpGroup==='due') g=t.dueDate?(isOverdue(t)?'Overdue':dueLabel({dueDate:t.dueDate})):'No due date';
    if(!groups.has(g)) groups.set(g,[]); groups.get(g).push(t);
  }
  // today summary
  const tk=todayKey(), now=NOW(), d0=fromKey(tk).getTime(), d1=d0+864e5;
  const occ=occurrences(d0,d1,{all:true}).filter(o=>!o.allDay && o.kind!=='busy');
  const next=occ.filter(o=>o.e>now && !o.cancelled && !o.done)[0];
  const freeMin=freeWorkMinutes(tk);
  el.innerHTML=`
    <div class="today-sum"><b>Today · ${esc(fmtShort(new Date(NOW())))}</b>
      <div class="row"><span>Events</span><span>${occ.filter(o=>o.kind==='event'&&!o.cancelled).length}</span></div>
      <div class="row"><span>Task blocks</span><span>${occ.filter(o=>o.kind==='block').length}</span></div>
      <div class="row"><span>Due / overdue tasks</span><span>${counts.today} / ${counts.overdue}</span></div>
      <div class="row"><span>Free work time left</span><span>${fmtH(freeMin)}</span></div>
      ${next?`<div class="row" style="color:var(--ink)"><span>Next: ${esc(next.title)}</span><span>${hm(next.s)}</span></div>`:''}
    </div>
    <div class="sideh" style="margin-top:0"><span>Tasks</span>${editable?'<button data-newtask title="New task">＋ Task</button>':''}</div>
    <div class="tp-tabs">${Object.entries(SMART).map(([k,v])=>`<button data-tp="${k}" class="${tpTab===k?'on':''}">${v.label}<span class="n">${counts[k]}</span></button>`).join('')}</div>
    <div class="tp-ctrl"><select data-tpsort aria-label="Sort">${['due','priority','project','estimate','title'].map(s=>`<option value="${s}" ${prefs.tpSort===s?'selected':''}>Sort: ${s}</option>`).join('')}</select>
      <select data-tpgroup aria-label="Group">${['none','project','status','due'].map(s=>`<option value="${s}" ${prefs.tpGroup===s?'selected':''}>Group: ${s}</option>`).join('')}</select></div>
    <div class="tl">${list.length?[...groups].map(([g,ts])=>(g?`<div class="tgroup">${esc(g)}</div>`:'')+ts.map(t=>taskRowHTML(t,{sel:true})).join('')).join(''):'<div class="hint" style="padding:10px 4px">No tasks here.</div>'}</div>
    ${editable?'<div class="hint" style="margin-top:10px">Drag a task onto the calendar to give it a time block.</div>':''}`;
  $$('[data-tp]',el).forEach(b=>b.onclick=()=>{ tpTab=b.dataset.tp; renderTasksPanel(); });
  $('[data-tpsort]',el).onchange=e=>{ prefs.tpSort=e.target.value; savePrefs(); renderTasksPanel(); };
  $('[data-tpgroup]',el).onchange=e=>{ prefs.tpGroup=e.target.value; savePrefs(); renderTasksPanel(); };
  const nt=$('[data-newtask]',el); if(nt) nt.onclick=()=>openTaskEditor(null);
  bindTaskRows(el);
  renderSelbar();
}
function bindTaskRows(el){
  $$('[data-task]',el).forEach(r=>{
    r.onclick=e=>{ if(e.target.closest('[data-done],[data-tsel],label')) return; selItem={kind:'task',id:r.dataset.task}; openTaskEditor(r.dataset.task); };
    r.onkeydown=e=>{ if(e.key==='Enter') r.click(); if(e.key===' '&&editable){ e.preventDefault(); toggleTaskDone(r.dataset.task); } };
    r.addEventListener('dragstart',e=>{ e.dataTransfer.setData('text/x-task', r.dataset.task); e.dataTransfer.effectAllowed='copyMove'; });
  });
  $$('[data-done]',el).forEach(b=>b.onclick=e=>{ e.stopPropagation(); toggleTaskDone(b.dataset.done); });
  $$('[data-tsel]',el).forEach(c=>c.onchange=()=>{ c.checked?selTasks.add(c.dataset.tsel):selTasks.delete(c.dataset.tsel); renderTasksPanel(); });
}
