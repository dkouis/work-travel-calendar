/* ============================================================
   Details in a new browser tab (events of every type, projects, flights)
   URL:  index.php#open=event&id=…&occ=YYYY-MM-DD
         index.php#open=project&id=…
         index.php#open=flight&day=YYYY-MM-DD&n=0
   The tab signs in with the same session, shows the details full-page,
   lets editors change them, and stays in sync with the calendar tab
   (every save is announced to the other tabs).
   Settings → General → "Open details" switches back to the popup.
   ============================================================ */
const DETAIL = (()=>{
  const h=new URLSearchParams(location.hash.slice(1)), t=h.get('open');
  if(!['event','project','flight','edit'].includes(t)) return null;
  return {t, id:h.get('id')||'', occ:h.get('occ')||'', k:h.get('day')||'', i:+(h.get('n')||0), from:h.get('from')||'', to:h.get('to')||'',
          type:h.get('type')||'', draft:h.get('draft')||''};
})();
if(DETAIL) document.body.classList.add('detail'); else { try{ if(!window.name || !window.name.startsWith('wtc-')) window.name='wtc-main'; }catch(e){} }

function useTabs(){ return !!DETAIL || (prefs.openDetails||'tab')!=='popup'; }
function detailUrl(o){
  const p=new URLSearchParams({open:o.t});
  if(o.id) p.set('id',o.id); if(o.occ) p.set('occ',o.occ);
  if(o.t==='edit'){ p.set('type',o.type); if(o.draft) p.set('draft',o.draft); }
  if(o.t==='flight'){ p.set('day',o.k); p.set('n',o.i); const f=(day(o.k).flights||[])[o.i]; if(f){ p.set('from',f.from||''); p.set('to',f.to||''); } }
  return location.pathname+location.search+'#'+p.toString();
}
function openDetailTab(o){
  const name='wtc-'+o.t+'-'+(o.t==='flight'?o.k+'-'+o.i:o.id)+(o.occ?'-'+o.occ:'');   // same item → same tab
  let w=null; try{ w=window.open(detailUrl(o), name); }catch(e){}
  if(!w){   // pop-ups blocked: show it here instead
    toast('Your browser blocked the new tab. Allow pop-ups for this site, or choose “popup” in ⚙ Settings → General → Open details.',{ms:9000});
    if(o.t==='event') return openEventEditor(o.id,null,o.occ);
    if(o.t==='project') return showProjectPage(o.id,null);
    return openDrawer(o.k);
  }
  try{ w.focus(); }catch(e){}
}
function openEvent(id, occ){ if(useTabs()) return openDetailTab({t:'event', id, occ:occ||''}); openEventEditor(id,null,occ); }
function openFlight(k, i){ if(useTabs()) return openDetailTab({t:'flight', k, i:+i||0}); openDrawer(k); }
function closeDetailTab(){
  window.close();                       // works for tabs opened by the calendar
  setTimeout(()=>{ location.href=location.pathname+location.search; },300);   // otherwise: go to the calendar
}
/* show a day in the calendar tab (reuses it, or opens one) */
function showInCalendar(k){
  let w=null; try{ w=window.open('', 'wtc-main'); }catch(e){}
  let blank=false; try{ blank=!w || w.location.href==='about:blank'; }catch(e){ blank=false; }
  if(w && blank){ w.location.href=location.pathname+location.search+'#day='+k; return; }
  if(!w){ location.href=location.pathname+location.search+'#day='+k; return; }
  try{ _bc && _bc.postMessage({type:'goto', k}); w.focus(); }catch(e){}
}
function gotoDay(k){ if(!/^\d{4}-\d{2}-\d{2}$/.test(k||'')) return; cursor=fromKey(k); miniCursor=null; if(view==='month'||view==='board'||view==='agenda') view='day'; renderAll(); try{ window.focus(); }catch(e){} }
function routeMainHash(){
  const h=new URLSearchParams(location.hash.slice(1)), k=h.get('day');
  if(k){ gotoDay(k); history.replaceState(null,'',location.pathname+location.search); }
}
window.addEventListener('hashchange',()=>{ if(DETAIL) location.reload(); else if(session) routeMainHash(); });

/* ---------- rendering ---------- */
let _detailProjectDraw=null;
function renderDetail(){
  const el=$('#detail'); if(!el || !data) return;
  if(DETAIL.t==='project'){ if(!_detailProjectDraw || !el.firstChild) _detailProjectDraw=showProjectPage(DETAIL.id, el); else _detailProjectDraw(); el.classList.add('box'); return; }
  el.classList.remove('box');
  if(DETAIL.t==='event') return renderEventDetail(el);
  if(DETAIL.t==='flight') return renderFlightDetail(el);
  if(DETAIL.t==='edit') return renderEditPage(el);
}
function renderDetailMissing(kind){
  const el=$('#detail'); document.title='Not found · Work & Travel';
  el.innerHTML=`<div class="dt-card"><h1 class="dt-title">This ${kind} is not available</h1>
    <p class="hint">It may have been deleted${privLocked?', or it is a Personal item and Personal items are locked on this device':''}.</p>
    <div class="dacts">${privLocked?'<button data-unl class="primary">🔑 Unlock Personal items</button>':''}<button data-cal>📅 Open the calendar</button><button data-x>Close tab</button></div></div>`;
  const u=$('[data-unl]',el); if(u) u.onclick=openUnlock;
  $('[data-cal]',el).onclick=()=>{ location.href=location.pathname+location.search; };
  $('[data-x]',el).onclick=closeDetailTab;
}
const dtRow=(label,html)=>html?`<dt>${esc(label)}</dt><dd>${html}</dd>`:'';
function remText(r){ return esc((OFFSETS.find(o=>o[0]===+r.offsetMin)||[0,fmtDur(r.offsetMin)+' before'])[1])+(r.channel&&r.channel!=='popup'?' · desktop'+(r.channel==='sound'?' + sound':''):''); }

function renderEventDetail(el){
  const it=ev(DETAIL.id); if(!it) return renderDetailMissing('event');
  let o=null;
  if(it.rrule && DETAIL.occ && it.exceptions[DETAIL.occ] && it.exceptions[DETAIL.occ].deleted) return renderDetailMissing('occurrence');   // that occurrence was deleted
  if(it.rrule && DETAIL.occ) o=findOcc(it.id, DETAIL.occ, 'event');
  if(!o) o=it.rrule ? (expandEvent(it, NOW()-864e5, NOW()+400*864e5)[0] || expandEvent(it,-1e14,1e14)[0]) : expandEvent(it,-1e14,1e14)[0];
  if(!o) return renderDetailMissing('event');
  const p=proj(it.projectId), c=cal(it.calendarId), tz=it.tz;
  document.title=(o.cancelled?'✕ ':'')+o.title+' · Work & Travel';
  const when = o.allDay
    ? (o.dateKey===o.endKey ? fmtLong(fromKey(o.dateKey)) : fmtLong(fromKey(o.dateKey))+' – '+fmtLong(fromKey(o.endKey)))+' · all day'
    : `${fmtLong(new Date(o.s))}<br><b>${hm(o.s)} – ${hm(o.e)}</b> <span class="hint">(${esc(tzShort(ACTIVE_TZ))} time · ${fmtDur((o.e-o.s)/60000)})</span>`
      +(tz && tz!==ACTIVE_TZ?`<div class="hint">Entered in ${esc(tzShort(tz))} time: ${esc(msToZoned(o.s,tz).slice(11))} – ${esc(msToZoned(o.e,tz).slice(11))}</div>`:'');
  const conf=o.allDay?null:conflictsIn(occurrences(o.s-864e5,o.e+864e5,{all:true})).get(it.id+'|'+o.occ);
  const linked=it.linkedTaskIds.map(task).filter(Boolean);
  const k=o.dateKey||key(new Date(o.s));
  el.innerHTML=`<div class="dt-card">
    <div class="dt-head"><span class="dotc" style="background:${esc(itemColor(it))};width:14px;height:14px"></span>
      <span class="tag k">${esc(CATEGORIES[it.category]||'Event')}</span>${it.rrule?'<span class="tag">🔁 repeating</span>':''}${o.cancelled?'<span class="tag" style="color:var(--sick)">Cancelled</span>':''}${isPrivate(it)?'<span class="tag">🔒 private</span>':''}</div>
    <h1 class="dt-title redtitle">${o.cancelled?'<s>':''}${esc(o.title)}${o.cancelled?'</s>':''}</h1>
    <dl class="dt-grid">
      ${dtRow('When', when)}
      ${dtRow('Repeats', it.rrule?esc(rrText(it.rrule))+(o.ex?' · <i>this occurrence was changed</i>':''):'')}
      ${dtRow('Calendar', `<i class="dotc" style="display:inline-block;background:${esc(c.color)}"></i> ${esc(c.name)}`)}
      ${dtRow('Project', p?`<a href="#" data-proj="${esc(p.id)}">${esc(p.icon||'')} ${esc(p.name)}</a>`:'')}
      ${dtRow('Location', (o.location||it.location)?'📍 '+esc(o.location||it.location):'')}
      ${dtRow('Online', it.onlineLink?`<a href="${esc(it.onlineLink)}" target="_blank" rel="noopener noreferrer">${esc(it.onlineLink)}</a>`:'')}
      ${dtRow('Attendees', it.attendees.length?esc(it.attendees.join(', ')):'')}
      ${dtRow('Show as', esc(SHOW_AS[it.showAs]||''))}
      ${dtRow('Reminders', it.reminders.map(remText).join('<br>'))}
      ${dtRow('Tags', it.tags.map(t=>`<span class="tag">#${esc(t)}</span>`).join(' '))}
      ${dtRow('Links', it.links.map(l=>`<a href="${esc(l.url)}" target="_blank" rel="noopener noreferrer">${esc(l.label||l.url)}</a>`).join('<br>'))}
      ${dtRow('Tasks', linked.map(t=>`<a href="#" data-task="${esc(t.id)}">${isOpen(t)?'☐':'✓'} ${esc(t.title)}</a>`).join('<br>'))}
    </dl>
    ${conf?`<div class="warnbox">⚠ ${conf.map(esc).join('<br>')}</div>`:''}
    ${it.notes?`<h2 class="dt-sub">Notes</h2><div class="md dt-notes">${md(it.notes)}</div>`:''}
    <div class="dacts dt-acts">
      ${editable?'<button class="primary" data-a="edit">✎ Edit</button><button data-a="follow">＋ Follow-up task</button><button data-a="dup">Duplicate</button>':''}
      ${it.onlineLink?'<button data-a="join">🔗 Join</button>':''}
      <button data-a="cal">📅 Show in calendar</button><button data-a="ics">⬇ .ics</button>
      ${editable?'<button data-a="del" class="danger">Delete</button>':''}
      <span class="sp"></span><button data-a="close">Close tab</button>
    </div>
    <div class="hint dt-foot">Updated ${esc(fmtWhen(it.updated))}. This tab stays up to date with the calendar.</div></div>`;
  const a=(n,fn)=>{ const b=$(`[data-a="${n}"]`,el); if(b) b.onclick=fn; };
  a('edit',()=>openEventEditor(it.id,null,o.occ));
  a('follow',()=>{ const t=newTask({title:'Follow up: '+it.title, calendarId:it.calendarId, projectId:it.projectId, dueDate:keyAdd(k,2), private:it.private}); openTaskEditor(null,t,{linkEvent:it.id}); });
  a('dup',()=>{ const c2=duplicateItem(it); openDetailTab({t:'event', id:c2.id}); });
  a('join',()=>window.open(it.onlineLink,'_blank','noopener'));
  a('cal',()=>showInCalendar(k));
  a('ics',()=>exportIcs({items:[it], name:it.title}));
  a('del',()=>deleteOcc(it.id, o.occ, 'event'));
  a('close',closeDetailTab);
  $$('[data-proj]',el).forEach(x=>x.onclick=e=>{ e.preventDefault(); openDetailTab({t:'project', id:x.dataset.proj}); });
  $$('[data-task]',el).forEach(x=>x.onclick=e=>{ e.preventDefault(); openTaskEditor(x.dataset.task); });
}

function detailFlight(){
  const fl=day(DETAIL.k).flights||[];
  let i=DETAIL.i;
  if(DETAIL.from||DETAIL.to){   // flights of that day changed order: find it by its route
    const ok=f=>f && (f.from||'')===DETAIL.from && (f.to||'')===DETAIL.to;
    if(!ok(fl[i])){ const j=fl.findIndex(ok); if(j>=0) i=j; }
  }
  return fl[i] ? {f:fl[i], i} : null;
}
function renderFlightDetail(el){
  const hit=detailFlight(); if(!hit) return renderDetailMissing('flight');
  const {f,i}=hit, k=DETAIL.k, ro=!editable, dis=ro?'disabled':'';
  const m=flightMins(f), ftz=flightTz(f), w=flightWindows(k).find(x=>x.i===i), al=flightAlarmMin(f);
  const ttz=CITY_TZ[(f.to||'').trim().toLowerCase()];
  document.title='✈ '+code(f.from)+' → '+code(f.to)+' · '+fmtShort(fromKey(k));
  const evs=w?occurrences(w.s-12*3600000, w.e+12*3600000,{all:true}).filter(o=>o.kind==='event'&&!o.allDay&&!o.cancelled&&(privKey||!isPrivate(o.item))):[];
  const e=day(k), l=locationOf(keyAdd(k,1));
  el.innerHTML=`<div class="dt-card">
    <div class="dt-head"><span class="tag k">✈ Flight</span><span class="tag">${esc(FLIGHT_TIMES[flightKind(f)])}</span>${al===null?'<span class="tag">⏰ alarm off</span>':`<span class="tag">⏰ ${minToHM(al)}</span>`}</div>
    <h1 class="dt-title redtitle">${esc(f.from||'?')} → ${esc(f.to||'?')}</h1>
    <div class="hint" style="font-size:15px;margin:-4px 0 12px">${esc(fmtLong(fromKey(k)))}</div>
    <dl class="dt-grid">
      ${dtRow('Departs', `<b>${minToHM(m.a)}</b> ${esc(tzShort(ftz))} time${w&&ftz!==ACTIVE_TZ?` <span class="hint">(${hm(w.s)} ${esc(tzShort(ACTIVE_TZ))})</span>`:''}`)}
      ${dtRow('Arrives', `<b>${m.b>=1440?'24:00':minToHM(m.b)}</b> ${esc(tzShort(ftz))} time${w&&ftz!==ACTIVE_TZ?` <span class="hint">(${hm(w.e)} ${esc(tzShort(ACTIVE_TZ))})</span>`:''}${ttz&&ttz!==ftz&&w?` <span class="hint">· ${esc(msToZoned(w.e,ttz).slice(11))} local in ${esc(tzShort(ttz))}</span>`:''}`)}
      ${dtRow('Flight', f.note?esc(f.note):'')}
      ${dtRow('Work status', !isWeekend(fromKey(k))&&(e.am||e.pm)?esc(e.am===e.pm?STATUSES[e.am].label:'Morning '+(e.am?STATUSES[e.am].label:'—')+' · Evening '+(e.pm?STATUSES[e.pm].label:'—')):'')}
      ${dtRow('Next day in', l?'📍 '+esc(l.name):'')}
      ${dtRow('Around it', evs.map(o=>`<a href="#" data-occ="${esc(o.id)}" data-oc="${esc(o.occ)}">${esc(fmtShort(new Date(o.s)))} ${hm(o.s)} · ${esc(o.title)}</a>`).join('<br>'))}
    </dl>
    ${ro?'':`<h2 class="dt-sub">Edit</h2>
    <div class="flight" style="max-width:640px">
      <div class="route">✈ <input type="text" data-ff="from" value="${esc(f.from)}" aria-label="From"> → <input type="text" data-ff="to" value="${esc(f.to)}" aria-label="To"></div><span></span>
      <div class="row2">${Object.entries(FLIGHT_TIMES).map(([t,lab])=>`<button class="chip ${flightKind(f)===t?'loc-on':''}" data-ft="${t}" ${dis}>${lab} <span class="hint">${minToHM(FLIGHT_WINDOWS[t][0])}–${FLIGHT_WINDOWS[t][1]>=1440?'24':minToHM(FLIGHT_WINDOWS[t][1]).replace(':00','')}</span></button>`).join('')}</div>
      <div class="row2"><label class="hint">Departs <input type="time" data-fx="dep" value="${minToHM(m.a)}" step="300"></label><label class="hint">Arrives <input type="time" data-fx="arr" value="${m.b>=1440?'23:59':minToHM(m.b)}" step="300"></label>
        <label class="hint">⏰ Alarm <input type="time" data-fx="alarm" value="${al===null?'':minToHM(al)}" step="300"></label><label class="hint"><input type="checkbox" data-falarm ${al===null?'':'checked'}> alarm on</label>
        ${m.custom||f.alarm?`<button class="chip" data-freset>↺ default</button>`:''}</div>
      <div class="row2"><input type="text" data-ff="note" value="${esc(f.note||'')}" placeholder="Flight number, e.g. A3 610"></div>
    </div>`}
    <div class="dacts dt-acts"><button data-a="cal">📅 Show day in calendar</button>${ro?'':'<button data-a="del" class="danger">Delete flight</button>'}<span class="sp"></span><button data-a="close">Close tab</button></div>
    <div class="hint dt-foot">Times are local to the departure city (${esc(tzShort(ftz))}). This tab stays up to date with the calendar.</div></div>`;
  $('[data-a=cal]',el).onclick=()=>showInCalendar(k);
  $('[data-a=close]',el).onclick=closeDetailTab;
  $$('[data-occ]',el).forEach(x=>x.onclick=ev2=>{ ev2.preventDefault(); openDetailTab({t:'event', id:x.dataset.occ, occ:x.dataset.oc}); });
  if(ro) return;
  const cur=()=>day(k).flights[detailFlight().i];
  const commit=()=>{ const g=cur(); DETAIL.from=g.from||''; DETAIL.to=g.to||''; history.replaceState(null,'',detailUrl({t:'flight',k,i:detailFlight().i})); prune(k); changed(); renderAll(); };
  $$('[data-ff]',el).forEach(inp=>inp.onchange=()=>{ cur()[inp.dataset.ff]=inp.value.trim(); commit(); });
  $$('[data-ft]',el).forEach(b=>b.onclick=()=>{ const g=cur(); g.time=b.dataset.ft; delete g.dep; delete g.arr; delete g.alarm; commit(); });
  $$('[data-fx]',el).forEach(inp=>inp.onchange=()=>{ const g=cur(), v=inp.value, n=inp.dataset.fx;
    if(n==='alarm'){ g.alarm=v||'off'; } else if(!v) delete g[n]; else g[n]=v;
    const d=FLIGHT_WINDOWS[flightKind(g)];
    if(g.dep===minToHM(d[0]) && (g.arr===minToHM(d[1])||(d[1]>=1440&&g.arr==='23:59')||!g.arr)){ delete g.dep; delete g.arr; }
    if(g.alarm && g.alarm!=='off' && g.alarm===minToHM(flightAlarmMin({...g,alarm:undefined}))) delete g.alarm;
    commit(); });
  const fa=$('[data-falarm]',el); if(fa) fa.onchange=()=>{ const g=cur(); if(fa.checked) delete g.alarm; else g.alarm='off'; commit(); };
  const fr=$('[data-freset]',el); if(fr) fr.onclick=()=>{ const g=cur(); delete g.dep; delete g.arr; delete g.alarm; commit(); };
  $('[data-a=del]',el).onclick=async()=>{
    if(!await confirmDlg({title:'Delete flight', html:`Delete the flight ${esc(f.from)} → ${esc(f.to)} on ${esc(fmtLong(fromKey(k)))}?`},[['Delete','y','danger'],['Cancel',null]])) return;
    day(k).flights.splice(detailFlight().i,1); prune(k); changed(); renderAll(); toast('Flight deleted',{actions:[['Undo',undo]]});
  };
}

/* ============================================================
   Edit windows in their own tab (events, tasks, projects)
   URL:  index.php#open=edit&type=event|task|project&id=…[&occ=…]
         new items: …&draft=<key>  (the draft is handed over through this
         browser's storage and removed as soon as the tab reads it)
   The editor fills the page; Save or Cancel closes the tab once the
   change has reached the server. Setting: ⚙ General → Edit windows.
   ============================================================ */
let FORCE_DIALOG=false;
function editInTab(){ return !FORCE_DIALOG && !EDIT_PAGE_PENDING && (prefs.openEditors||'tab')!=='popup' && !!session; }
function openEditTab(type, id, draft, occ, opts){
  const o={t:'edit', type, id:id||'', occ:occ||''};
  if(!id){ o.draft='wtc-draft:'+uid(''); jset(o.draft,{draft:clone(draft||null), opts:clone(opts||{}), at:Date.now()}); }
  const name='wtc-edit-'+type+'-'+(id||o.draft.slice(10))+(occ?'-'+occ:'');
  let w=null; try{ w=window.open(detailUrl(o), name); }catch(e){}
  if(!w){   // pop-ups blocked: edit here instead
    if(o.draft) safeDel(o.draft);
    toast('Your browser blocked the new tab. Allow pop-ups for this site, or choose “popup” in ⚙ Settings → General → Edit windows.',{ms:9000});
    FORCE_DIALOG=true;
    try{ if(type==='event') openEventEditor(id,draft,occ); else if(type==='task') openTaskEditor(id,draft,opts||{}); else openProjectEditor(id,draft); }
    finally{ FORCE_DIALOG=false; }
    return;
  }
  try{ w.focus(); }catch(e){}
}
let _editOpened=false;
function renderEditPage(el){
  if(_editOpened) return;                       // the editor keeps its own state once open
  const D=DETAIL, label={event:'event',task:'task',project:'project'}[D.type];
  if(!label) return renderDetailMissing('item');
  let draft=null, opts={};
  if(!D.id){
    const h=D.draft?jget(D.draft,null):null; if(D.draft) safeDel(D.draft);
    if(!h){ el.innerHTML=`<div class="dt-card"><h1 class="dt-title">This new ${label} is no longer available</h1><p class="hint">It was not saved; the window was probably reloaded. Start it again from the calendar.</p><div class="dacts"><button data-x>Close tab</button></div></div>`; $('[data-x]',el).onclick=closeDetailTab; return; }
    draft=h.draft; opts=h.opts||{};
    if(D.type==='event' && draft) draft=newEvent(draft);
    if(D.type==='task' && draft) draft=newTask(draft);
  } else {
    const it = D.type==='project' ? proj(D.id) : D.type==='task' ? task(D.id) : ev(D.id);
    if(!it) return renderDetailMissing(label);
  }
  _editOpened=true; el.innerHTML='';
  EDIT_PAGE_PENDING=true;
  try{
    if(D.type==='event') openEventEditor(D.id||null, draft, D.occ||undefined);
    else if(D.type==='task') openTaskEditor(D.id||null, draft, opts);
    else openProjectEditor(D.id||null, draft);
  } finally { EDIT_PAGE_PENDING=false; }
  const t=$('.dlg.page input[name=title],.dlg.page input[name=name]',el);
  const nm=()=>(t&&t.value.trim())||('New '+label);
  document.title=(D.id?'Edit: ':'')+nm()+' · Work & Travel';
  if(t) t.addEventListener('input',()=>{ document.title=(D.id?'Edit: ':'')+nm()+' · Work & Travel'; });
}
/* Save/Cancel/Delete in the edit page: wait for nested dialogs and for the save to reach the server, then close the tab */
async function closeEditPage(d){
  if(d._closing) return;
  const saved = changeSeq!==d._seq;
  if(!saved && d._cancel && d._modified && !await confirmDlg({title:'Discard changes?', html:'Your changes in this window have not been saved.'},[['Discard','y','danger'],['Keep editing',null]])){ d._cancel=false; return; }
  d._closing=true; d.style.display='none';
  const el=$('#detail'); const msg=document.createElement('div'); msg.className='dt-card'; msg.id='closingMsg';
  msg.innerHTML='<h1 class="dt-title">Closing…</h1><p class="hint" id="closingWhy">Saving your changes.</p><div class="dacts"><button data-cal>📅 Open the calendar</button></div>';
  el.appendChild(msg); $('[data-cal]',msg).onclick=()=>{ location.href=location.pathname+location.search; };
  await new Promise(r=>setTimeout(r,50));          // let the action that closed the page open its own confirmation first (e.g. Delete)
  const t0=Date.now();
  while(Date.now()-t0<20000){
    if(!_dlgStack.some(x=>x!==d) && !dirty && !saving && !saveTimer && !conflict) break;
    if(conflict || (netError && dirty)){ $('#closingWhy',msg).textContent='Not saved yet: see the message at the top. This tab stays open until it is saved.'; }
    await new Promise(r=>setTimeout(r,150));
  }
  if(dirty || saving || conflict) return;        // keep the tab open; the banner explains what is wrong
  if(changeSeq===d._seq && !d._cancel){           // e.g. Delete was cancelled: show the editor again
    msg.remove(); d.style.display=''; d._closing=false; return; }
  _dlgStack=_dlgStack.filter(x=>x!==d); d.remove();
  const did=changeSeq!==d._seq;
  $('#closingWhy',msg).textContent = did ? 'Saved. You can close this tab.' : 'You can close this tab.';
  $('h1',msg).textContent = did ? 'Saved ✓' : 'Closed';
  window.close();                                 // works for tabs the calendar opened
}
window.addEventListener('beforeunload',e=>{ const p=$('.dlg.page'); if(p && p._modified && !p._closing && changeSeq===p._seq){ e.preventDefault(); e.returnValue=''; } });
