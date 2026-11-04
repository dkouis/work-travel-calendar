/* ============================================================
   Views: Day, Week (time grid or compact), Month, Agenda, Board
   ============================================================ */
let agendaDays = 14;
function setView(v){
  const prev=view; view=v; safeSet('wtc-view',v);
  if(prev==='month' && (v==='week'||v==='day'||v==='3day')){ const t=TODAY(); if(t.getMonth()!==cursor.getMonth()||t.getFullYear()!==cursor.getFullYear()) cursor=new Date(cursor.getFullYear(),cursor.getMonth(),1); else cursor=t; }
  renderAll();
}
/* Hide Saturday & Sunday (per device) — applies to 3 days, Week and Month */
function hideWE(){ return !!prefs.hideWeekends; }
function nextWorkday(d, dir=1){ while(isWeekend(d)) d=addDays(d,dir); return d; }
function threeDays(){ // the 3 days shown from cursor
  if(!hideWE()) return [0,1,2].map(i=>addDays(cursor,i));
  const out=[]; let d=nextWorkday(cursor); while(out.length<3){ if(!isWeekend(d)) out.push(d); d=addDays(d,1); } return out;
}
function weekDays(){ const mon=mondayOf(cursor); return [0,1,2,3,4,5,6].map(i=>addDays(mon,i)).filter(d=>!hideWE()||!isWeekend(d)); }
function visibleRange(){
  if(view==='day') return [cursor, addDays(cursor,1)];
  if(view==='3day'){ const ds=threeDays(); return [ds[0], addDays(ds[ds.length-1],1)]; }
  if(view==='week') return [mondayOf(cursor), addDays(mondayOf(cursor),7)];
  if(view==='agenda') return [cursor, addDays(cursor,agendaDays)];
  if(view==='board') return [cursor, addDays(cursor,1)];
  const f=new Date(cursor.getFullYear(),cursor.getMonth(),1); return [mondayOf(f), addDays(mondayOf(new Date(cursor.getFullYear(),cursor.getMonth()+1,0)),7)];
}
function move(n){
  if(view==='day') cursor=addDays(cursor,n);
  else if(view==='3day'){ if(!hideWE()) cursor=addDays(cursor,3*n); else { let d=nextWorkday(cursor), k=0; while(k<3){ d=addDays(d,n); if(!isWeekend(d)) k++; } cursor=d; } }
  else if(view==='week') cursor=addDays(cursor,7*n);
  else if(view==='agenda') cursor=addDays(cursor,agendaDays*n);
  else if(view==='board') return;
  else cursor=new Date(cursor.getFullYear(), cursor.getMonth()+n, 1);
  miniCursor=new Date(cursor.getFullYear(),cursor.getMonth(),1);
  render(); renderSide();
}
function hourPx(){ return prefs.density==='compact'?Math.round(prefs.hourPx*.75):prefs.hourPx; }

/* ---------- day header pieces (status, location, flights) ---------- */
function statusHTML(e, compact){
  const am=e.am, pm=e.pm;
  if(!am && !pm) return compact ? '' : '<div class="st none">No status</div>';
  if(am===pm) return `<div class="st ${am}" title="${STATUSES[am].label} (whole day)">${esc(STATUSES[am][compact?'short':'label'])}</div>`;
  const one=(s,h)=> s ? `<div class="st ${s}" title="${h==='AM'?'Morning':'Evening'}: ${STATUSES[s].label}"><span class="h">${h}</span>${esc(STATUSES[s][compact?'short':'label'])}</div>`
                      : `<div class="st none"><span class="h">${h}</span>—</div>`;
  return `<div class="halves">${one(am,'AM')}${one(pm,'PM')}</div>`;
}
/* Day-filling rules (unchanged): Athens + Telework → dark red; Athens + Telework abroad → light green */
function fillClass(k, e, we){
  if(we || selected.has(k) || !filt.showStatus) return '';
  const l = locationOf(k);
  if(!l || l.name.trim().toLowerCase()!=='athens') return '';
  if(e.am==='tele' || e.pm==='tele') return 'fill-tele';
  if(e.am==='abroad' || e.pm==='abroad') return 'fill-abroad';
  return '';
}
function locHTML(k){
  const l=locationOf(k); if(!l) return '';
  return `<span class="loc${l.auto?' auto':''}" title="${l.auto?'Inferred from last flight':'Location'}">📍 ${esc(l.name)}</span>`;
}
function flightDisp(k,f){ // display (active zone) times of a flight
  const w=flightWindows(k).find(x=>x.f===f); if(!w) return flightTimeLabel(f);
  const t=hm(w.s)+'–'+(key(new Date(w.e))!==key(new Date(w.s))&&hm(w.e)==='00:00'?'24:00':hm(w.e));
  return t+(w.tz!==ACTIVE_TZ?` (${minToHM(flightMins(f).a)} ${tzShort(w.tz)})`:'');
}
function flightsHTML(e,k){
  if(!filt.showFlights) return '';
  return (e.flights||[]).map(f=>`<div class="fl" title="${esc(f.from)} → ${esc(f.to)}${f.note?' · '+esc(f.note):''}">✈ ${esc(code(f.from))} → ${esc(code(f.to))} <span class="t">${esc(FLIGHT_TIMES[flightKind(f)])} ${esc(k?flightDisp(k,f):flightTimeLabel(f))}</span></div>`).join('');
}

/* ---------- chips (all-day row, month cells, compact week) ---------- */
function occChip(o, dayK){
  if(o.kind==='busy') return `<div class="chipi busy" title="Busy (Personal, locked)">Busy</div>`;
  const it=o.item, c=itemColor(it);
  const cont = o.allDay && o.dateKey<dayK;
  const t = o.allDay ? '' : `<span class="t">${hm(o.s)}</span>`;
  const cls=[o.kind==='block'?'task':'', o.done?'done':'', o.cancelled?'cancelled':'', cont?'cont':''].join(' ');
  return `<div class="chipi ${cls}" style="--c:${esc(c)}" data-occ="${esc(o.id)}" data-oc="${esc(o.occ)}" data-ok="${o.kind}" draggable="${editable && !it.rrule}" title="${esc(o.title)}">${o.kind==='block'?(o.done?'✓':'☐'):''}${cont?'↳ ':''}${t} ${esc(CAT_ICON[it.category]||'')}${esc(o.title)}</div>`;
}
function dueChip(t, overdue){
  const dd=dueDisp(t)||{};
  return `<div class="chipi due ${overdue?'overdue':''} ${isOpen(t)?'':'done'}" style="--c:${esc(itemColor(t))}" data-task="${t.id}" draggable="${editable}" title="Due${dd.time?' '+dd.time:''}: ${esc(t.title)}">⚑ ${dd.time?`<span class="t">${dd.time}</span> `:''}${esc(t.title)}</div>`;
}
function flightChips(k){
  if(!filt.showFlights) return '';
  return (day(k).flights||[]).map(f=>`<div class="chipi flight" title="${esc(f.from)} → ${esc(f.to)} · ${esc(flightLabel(f))}${f.note?' · '+esc(f.note):''}">✈ ${esc(code(f.from))}→${esc(code(f.to))} <span class="t">${esc(flightDisp(k,f))}</span></div>`).join('');
}
function dayItems(k){ // all items of a day for chips, in display order
  const d0=fromKey(k).getTime(), d1=addDays(fromKey(k),1).getTime();
  const occ=occurrences(d0,d1);
  const ad=occ.filter(o=>o.allDay), tm=occ.filter(o=>!o.allDay);
  const due=filt.showDue?dueTasksOn(k):[];
  const tk=todayKey();
  const overdue = (k===tk && filt.showDue) ? data.tasks.filter(t=>passFilter(t) && isOverdue(t) && t.dueDate<tk) : [];
  return {ad, tm, due, overdue};
}

/* ---------- main render ---------- */
function render(){
  if(!data || !session) return;
  $$('#viewSeg button').forEach(b=>{ b.classList.toggle('on', b.dataset.view===view); b.setAttribute('aria-selected', b.dataset.view===view); });
  $('#ownerLbl').textContent = data.owner ? '· '+data.owner : '';
  document.title = 'Work & Travel Calendar' + (data.owner?' — '+data.owner:'');
  let html='';
  if(view==='day') html=renderGrid([cursor]);
  else if(view==='3day') html=renderGrid(threeDays());
  else if(view==='week') html = weekCompact ? renderWeekCompact() : renderGrid(weekDays());
  else if(view==='agenda') html=renderAgenda();
  else if(view==='board') html=renderBoard();
  else html=renderMonth();
  $('#main').innerHTML = toolsHTML() + legendHTML() + html + helpLine();
  bindTools(); bindMain();
  if(view==='day'||view==='3day'||(view==='week'&&!weekCompact)) bindGrid();
  renderSelbar();
}
function helpLine(){
  if(view==='board' || view==='agenda') return '';
  return `<div class="help">${editable
    ? (view==='month'||weekCompact ? 'Click a day to edit status, location, flights and its events and tasks. <kbd>Ctrl</kbd>/<kbd>⌘</kbd>/<kbd>Shift</kbd>-click selects several days. Drag items to another day.' : 'Drag on empty time to create an event or task. Drag items to move them, drag their lower edge to resize (<kbd>Alt</kbd> = 5-minute steps). Drop tasks from the right panel to schedule them. Click a day header for status, location and flights.')+' Press <kbd>?</kbd> for shortcuts.'
    : 'Read-only view — updates by itself every minute.'}</div>`;
}
function toolsHTML(){
  const grid = view==='day'||view==='3day'||view==='week';
  const tog=(k,l)=>`<label><input type="checkbox" data-ft="${k}" ${filt[k]?'checked':''}> ${l}</label>`;
  return `<div class="vtools">
    ${view==='week'?`<div class="seg"><button data-wc="0" class="${!weekCompact?'on':''}">Time grid</button><button data-wc="1" class="${weekCompact?'on':''}">Compact</button></div>`:''}
    ${grid&&!weekCompact?`<button data-zoom="-1" title="Smaller hours" aria-label="Zoom out">－</button><button data-zoom="1" title="Taller hours" aria-label="Zoom in">＋</button>`:''}
    ${['3day','week','month'].includes(view)?`<label title="Hide Saturday and Sunday in 3 days, Week and Month"><input type="checkbox" data-hidewe ${hideWE()?'checked':''}> Hide weekends</label>`:''}
    <label>Colour by <select data-colorby>${['project','calendar','priority'].map(c=>`<option ${prefs.colorBy===c?'selected':''}>${c}</option>`).join('')}</select></label>
    <label>Density <select data-density>${['comfortable','compact'].map(c=>`<option ${prefs.density===c?'selected':''}>${c}</option>`).join('')}</select></label>
    <details class="dropdown"><summary style="cursor:pointer;color:var(--muted)">Show ▾</summary><div class="menu open" style="display:block;padding:10px;min-width:210px">
      ${tog('showStatus','Work status')}<br>${tog('showFlights','Flights')}<br>${tog('showBlocks','Task blocks')}<br>${tog('showDue','Due dates')}<br>${tog('showDone','Completed tasks')}<br>${tog('showCancelled','Cancelled events')}<br>
      <label><input type="checkbox" data-legend ${prefs.showLegend?'checked':''}> Legend</label></div></details>
    ${filt.projects.length||filt.tag||filt.hiddenCals.length?`<span class="tag k">Filtered${filt.projects.length?': '+filt.projects.map(p=>p.startsWith('inbox:')?'Inbox':(proj(p)||{}).name).join(', '):''}${filt.tag?' #'+esc(filt.tag):''} <button class="ghost" data-clearf style="padding:0 3px">✕</button></span>`:''}
  </div>`;
}
function legendHTML(){
  if(!prefs.showLegend || view==='board') return '';
  return '<div class="legend">'+(filt.showStatus?Object.entries(STATUSES).map(([k,s])=>`<span><i class="sw" style="background:var(--${k})"></i>${s.label}</span>`).join(''):'')
    + '<span>📍 location (dashed = from last flight)</span><span>✈ flight</span>'
    + '<span><i class="sw" style="background:repeating-linear-gradient(135deg,var(--accent) 0 3px,transparent 3px 6px);border:1px solid var(--line2)"></i>task block</span><span>⚑ task due</span><span>⚠ conflict</span>'
    + (filt.showStatus?'<span><i class="sw" style="background:var(--fill-ath-tele);border:1px solid var(--line2)"></i>Athens + Telework</span><span><i class="sw" style="background:var(--fill-ath-abroad);border:1px solid var(--line2)"></i>Athens + Telework abroad</span>':'')+'</div>';
}
function bindTools(){
  const m=$('#main');
  $$('[data-wc]',m).forEach(b=>b.onclick=()=>{ weekCompact=b.dataset.wc==='1'; safeSet('wtc-weekc',weekCompact?'1':'0'); render(); });
  $$('[data-zoom]',m).forEach(b=>b.onclick=()=>{ prefs.hourPx=clamp(prefs.hourPx+(+b.dataset.zoom)*8,24,96); savePrefs(); render(); });
  const hw=$('[data-hidewe]',m); if(hw) hw.onchange=()=>{ prefs.hideWeekends=hw.checked; savePrefs(); _gridScroll=null; render(); renderSide(); };
  $('[data-colorby]',m).onchange=e=>{ prefs.colorBy=e.target.value; savePrefs(); renderAll(); };
  $('[data-density]',m).onchange=e=>{ prefs.density=e.target.value; savePrefs(); render(); };
  $$('[data-ft]',m).forEach(c=>c.onchange=()=>{ filt[c.dataset.ft]=c.checked; saveFilter(); renderAll(); });
  const lg=$('[data-legend]',m); if(lg) lg.onchange=()=>{ prefs.showLegend=lg.checked; savePrefs(); render(); };
  const cf=$('[data-clearf]',m); if(cf) cf.onclick=()=>{ filt.projects=[]; filt.tag=''; filt.hiddenCals=[]; saveFilter(); renderAll(); };
}

/* ---------- month ---------- */
function renderMonth(){
  const y=cursor.getFullYear(), m=cursor.getMonth();
  $('#title').textContent = MONTHS[m]+' '+y;
  const first=new Date(y,m,1), last=new Date(y,m+1,0);
  let d=mondayOf(first); const end=addDays(mondayOf(last),6);
  const today=todayKey();
  const conf=conflictsIn(occurrences(d.getTime(), addDays(end,1).getTime()));
  const nd=hideWE()?5:7;
  let html=`<div class="month ${hideWE()?'nowe':''}"><div class="mhead">`+DOW.slice(0,nd).map(x=>`<div>${x}</div>`).join('')+'</div>';
  while(d<=end){
    html+='<div class="mrow">';
    for(let i=0;i<7;i++){
      if(i>=nd){ d=addDays(d,1); continue; }
      const k=key(d), e=day(k), we=isWeekend(d);
      const cls=['cell', we?'we':'', d.getMonth()!==m?'out':'', k===today?'today':'', selected.has(k)?'sel':''].join(' ');
      const it=dayItems(k);
      const chips=[...it.overdue.map(t=>dueChip(t,true)), ...it.ad.map(o=>occChip(o,k)), ...it.tm.map(o=>occChip(o,k).replace('class="chipi','class="chipi'+(conf.has(o.id+'|'+o.occ)?' conflict':''))), ...it.due.map(t=>dueChip(t,false))];
      const max=innerWidth<900?2:3;
      html+=`<div class="${cls} ${fillClass(k,e,we)}" data-k="${k}">
        <div class="num"><b>${d.getDate()}</b>${i===0?`<span class="wk">W${isoWeek(d)}</span>`:''}</div>
        ${locHTML(k)}
        ${we||!filt.showStatus?'':statusHTML(e,true)}
        ${flightsHTML(e,k)}
        ${chips.slice(0,max).join('')}${chips.length>max?`<div class="more" data-moreday="${k}">+${chips.length-max} more</div>`:''}
        ${e.comment?`<div class="cm">${esc(e.comment)}</div>`:''}
      </div>`;
      d=addDays(d,1);
    }
    html+='</div>';
  }
  html+='</div>';
  // month notes + summary (unchanged)
  const mk=y+'-'+String(m+1).padStart(2,'0');
  const counts={}; let flights=0; const locs={}; let wd=0, filled=0;
  for(let x=new Date(first); x<=last; x=addDays(x,1)){
    const k=key(x), e=day(k);
    flights+=(e.flights||[]).length;
    const l=locationOf(k); if(l) locs[l.name]=(locs[l.name]||0)+1;
    if(isWeekend(x)) continue;
    wd++;
    for(const h of ['am','pm']) if(e[h]){ counts[e[h]]=(counts[e[h]]||0)+0.5; filled+=0.5; }
  }
  const fmt=n=>(Math.round(n*2)/2).toString().replace('.5','½').replace(/^0½$/,'½');
  const stats=Object.keys(STATUSES).map(s=>{ const n=counts[s]||0; return `<span><i class="sw" style="background:var(--${s})"></i> ${STATUSES[s].label}</span><div class="bar"><i style="width:${wd?n/wd*100:0}%;background:var(--${s})"></i></div><span class="n">${n?fmt(n):'–'}</span>`; }).join('');
  const locTxt=Object.entries(locs).sort((a,b)=>b[1]-a[1]).map(([n,c])=>`${esc(n)} ${c}`).join(' · ') || '—';
  html+=`<div class="below">
    <div class="panel"><h3>Notes for ${MONTHS[m]}</h3>
      <textarea id="monthNotes" data-mk="${mk}" placeholder="${editable?'Notes for this month…':'No notes'}" ${editable?'':'disabled'}>${esc(data.monthNotes[mk]||'')}</textarea></div>
    <div class="panel"><h3>${MONTHS[m]} summary · working days</h3>
      <div class="stats">${stats}</div>
      <div class="sub">${fmt(filled)} of ${wd} working days recorded · ${flights} flight${flights===1?'':'s'}<br>Days by location: ${locTxt}</div></div>
  </div>`;
  return html;
}

/* ---------- week, compact (the original card layout + items) ---------- */
function renderWeekCompact(){
  const mon=mondayOf(cursor), sun=addDays(mon,6);
  $('#title').textContent = weekTitle(mon,sun);
  const today=todayKey();
  let html=`<div class="week ${hideWE()?'nowe':''}">`;
  for(let i=0;i<(hideWE()?5:7);i++){
    const d=addDays(mon,i), k=key(d), e=day(k), we=i>=5;
    const fl=flightsHTML(e,k); const it=dayItems(k);
    const chips=[...it.overdue.map(t=>dueChip(t,true)), ...it.ad.map(o=>occChip(o,k)), ...it.tm.map(o=>occChip(o,k)), ...it.due.map(t=>dueChip(t,false))].join('');
    html+=`<div class="wcol ${we?'we':''} ${k===today?'today':''} ${selected.has(k)?'sel':''} ${fillClass(k,e,we)}" data-k="${k}">
      <div class="dh"><span class="dn">${DOW[i]}</span><span class="dd">${d.getDate()}</span></div>
      <div><div class="lbl">Location</div>${locHTML(k)||'<span class="empty">—</span>'}</div>
      ${we||!filt.showStatus?'':`<div><div class="lbl">Morning</div>${e.am?`<div class="st ${e.am}">${STATUSES[e.am].label}</div>`:'<div class="st none">—</div>'}</div>
               <div><div class="lbl">Evening</div>${e.pm?`<div class="st ${e.pm}">${STATUSES[e.pm].label}</div>`:'<div class="st none">—</div>'}</div>`}
      <div><div class="lbl">Travel</div>${fl||'<span class="empty">No flights</span>'}</div>
      <div><div class="lbl">Events &amp; tasks</div><div style="display:flex;flex-direction:column;gap:3px">${chips||'<span class="empty">—</span>'}</div></div>
      <div style="flex:1"><div class="lbl">Comments</div>${e.comment?`<div class="cm">${esc(e.comment)}</div>`:'<span class="empty">—</span>'}</div>
    </div>`;
  }
  return html+'</div>';
}
function weekTitle(mon,sun){ const sameM=mon.getMonth()===sun.getMonth(); return `Week ${isoWeek(mon)} · ${mon.getDate()}${sameM?'':' '+MONTHS[mon.getMonth()].slice(0,3)}–${sun.getDate()} ${MONTHS[sun.getMonth()].slice(0,3)} ${sun.getFullYear()}`; }

/* ---------- time grid (Day / Week) ---------- */
let gridDays=[], gridFocus=null;
function layoutLanes(items){ // classic overlap layout: returns item→{lane,lanes}
  const res=new Map(); let cluster=[], clusterEnd=-1;
  const flush=()=>{ const lanes=[]; for(const o of cluster){ let i=lanes.findIndex(e=>e<=o._s); if(i<0){ i=lanes.length; lanes.push(0); } lanes[i]=o._e; res.set(o,{lane:i}); } for(const o of cluster) res.get(o).lanes=lanes.length; cluster=[]; };
  for(const o of items){ if(o._s>=clusterEnd && cluster.length) flush(); cluster.push(o); clusterEnd=Math.max(cluster.length===1?o._e:clusterEnd,o._e); }
  if(cluster.length) flush();
  return res;
}
function renderGrid(days){
  gridDays=days.map(d=>key(d));
  const n=days.length, hp=hourPx(), tk=todayKey(), now=NOW();
  $('#title').textContent = n===1 ? fmtLong(days[0]) : view==='week' ? weekTitle(mondayOf(days[0]),addDays(mondayOf(days[0]),6)) : `${fmtDM(days[0])} – ${fmtDM(days[n-1])} ${days[n-1].getFullYear()}`;
  const cols=`52px repeat(${n},minmax(0,1fr))`;
  const ws=hmToMin(S().workStart)||540, we=hmToMin(S().workEnd)||1080;
  const r0=fromKey(gridDays[0]).getTime(), r1=addDays(fromKey(gridDays[n-1]),1).getTime();
  const allOcc=occurrences(r0,r1);
  const conf=conflictsIn(allOcc);
  // header
  let head=`<div class="tg-head" style="grid-template-columns:${cols}"><div class="tg-gut">W${isoWeek(days[0])}<br><span title="Active time zone ${esc(ACTIVE_TZ)}">${esc(tzShort(ACTIVE_TZ))}</span></div>`;
  let all=`<div class="tg-all" style="grid-template-columns:${cols}"><div class="tg-gut">all-day</div>`;
  let body=`<div class="tg-grid" style="grid-template-columns:${cols};height:${24*hp}px"><div class="tg-hours">`;
  for(let h=0;h<24;h++){
    body+=`<div>${h?String(h).padStart(2,'0')+':00':''}</div>`;
  }
  body+='</div>';
  days.forEach((d,i)=>{
    const k=gridDays[i], e=day(k), wk=isWeekend(d), fill=fillClass(k,e,wk);
    head+=`<div class="tg-hcell ${wk?'we':''} ${k===tk?'today':''} ${fill}" data-k="${k}" title="Status, location, flights, comments">
      <div class="dh"><span class="dn">${DOW[dow(d)]}</span><span class="dd">${d.getDate()}</span>${n>1?'':`<span class="hint">${MONTHS[d.getMonth()]}</span>`}</div>
      ${locHTML(k)}${wk||!filt.showStatus?'':statusHTML(e,true)}${e.comment&&n===1?`<div class="cm">${esc(e.comment)}</div>`:''}</div>`;
    const it=dayItems(k);
    all+=`<div class="tg-allc ${wk?'we':''}" data-allk="${k}">${it.overdue.map(t=>dueChip(t,true)).join('')}${it.ad.map(o=>occChip(o,k)).join('')}${it.due.map(t=>dueChip(t,false)).join('')}</div>`;
    // timed items in this column
    const d0=fromKey(k).getTime(), d1=addDays(fromKey(k),1).getTime();
    const fls=filt.showFlights?[keyAdd(k,-1),k,keyAdd(k,1)].flatMap(fk=>flightWindows(fk)).filter(w=>w.e>d0&&w.s<d1).map(w=>({kind:'flight', s:w.s, e:w.e, f:w.f, k:w.k, title:'✈ '+code(w.f.from)+' → '+code(w.f.to)})):[];
    const items=[...it.tm,...fls].map(o=>Object.assign(Object.create(null),{o,_s:Math.max(o.s,d0),_e:Math.min(o.e,d1)})).filter(x=>x._e>x._s).sort((a,b)=>a._s-b._s||b._e-a._e);
    const lanes=layoutLanes(items);
    const workDay=S().workDays.includes(dow(d));
    const off=workDay?`<div class="tg-off" style="top:0;height:${ws/60*hp}px"></div><div class="tg-off" style="top:${we/60*hp}px;bottom:0"></div>`:`<div class="tg-off" style="top:0;bottom:0"></div>`;
    let colHtml='';
    for(const x of items){
      const o=x.o, L=lanes.get(x), top=(x._s-d0)/60000*hp/60, h=Math.max(16,(x._e-x._s)/60000*hp/60-2);
      const w=100/L.lanes, left=L.lane*w;
      colHtml+=evBlockHTML(o, top, h, left, w, conf.get(o.id+'|'+o.occ));
    }
    const nowLine = k===tk ? `<div class="tg-now" style="top:${(now-d0)/60000*hp/60}px"></div>` : '';
    const leave=offIntervals(k).map(x=>`<div class="tg-leave" style="top:${x.a/60*hp}px;height:${(x.b-x.a)/60*hp}px" title="${esc(offLabel(x))}"><span>${esc(STATUSES[x.st].label)} · ${x.half}</span></div>`).join('');
    body+=`<div class="tg-col ${wk?'we':''}" data-col="${k}" data-ci="${i}">${off}${leave}${colHtml}${nowLine}</div>`;
  });
  head+='</div>'; all+='</div>'; body+='</div>';
  return `<div class="tg" role="grid" aria-label="Calendar time grid">${head}${all}<div class="tg-body" id="tgBody" tabindex="0" aria-label="Time grid. Arrow keys move, Enter creates an item">${body}</div></div>`;
}
function evBlockHTML(o, top, h, left, w, conf){
  if(o.kind==='flight'){ const f=o.f;
    return `<div class="ev flt" style="top:${top}px;height:${h}px;left:calc(${left}% + 2px);width:calc(${w}% - 4px)" data-flk="${o.k}" tabindex="0" role="button" aria-label="Flight ${esc(f.from)} to ${esc(f.to)}, ${esc(flightLabel(f))}" title="${esc(f.from)} → ${esc(f.to)} · ${esc(flightLabel(f))}${f.note?' · '+esc(f.note):''} — click to edit">
      <div class="tm">${hm(o.s)}–${hm(o.e)==='00:00'?'24:00':hm(o.e)}${o.k!==key(new Date(o.s))||flightTz(f)!==ACTIVE_TZ?` · ${esc(minToHM(flightMins(f).a))} ${esc(tzShort(flightTz(f)))}`:''}${flightAlarmMin(f)!==null?' ⏰':''}</div><span class="ti">✈ ${esc(code(f.from))} → ${esc(code(f.to))}</span>${h>44&&f.note?`<div class="lo">${esc(f.note)}</div>`:''}</div>`; }
  if(o.kind==='busy') return `<div class="ev busy" style="top:${top}px;height:${h}px;left:calc(${left}% + 2px);width:calc(${w}% - 4px)" title="Busy (Personal items are locked)"><span class="ti">Busy</span></div>`;
  const it=o.item, c=itemColor(it), task=o.kind==='block';
  const cls=['ev', task?'task p'+(it.priority||3):'', o.done?'done':'', o.missed?'missed':'', o.cancelled?'cancelled':'', (!task&&it.showAs==='tentative')?'tentative':'', conf?'conflict':''].join(' ');
  const tm=`${hm(o.s)}–${hm(o.e)}`;
  const icons=[it.rrule?'🔁':'', isPrivate(it)?'🔒':'', (it.reminders||[]).length?'🔔':'', task&&blockedBy(it).length?'⛓':''].join('');
  const label=`${task?'Task':'Event'}${task&&it.priority?', P'+it.priority:''}, ${o.title}, ${tm}${conf?', conflict: '+conf.join('; '):''}`;
  const small=h<30;
  return `<div class="${cls}" style="--c:${esc(c)};top:${top}px;height:${h}px;left:calc(${left}% + 2px);width:calc(${w}% - 4px)" data-occ="${esc(o.id)}" data-oc="${esc(o.occ)}" data-ok="${o.kind}" tabindex="0" role="button" aria-label="${esc(label)}" title="${esc(label)}">
    ${task?`<button class="check ${o.done?'on':''}" data-bdone="${esc(o.id)}|${esc(o.occ)}" aria-label="Block done" ${editable?'':'disabled'}>${o.done?'✓':''}</button>`:''}${small?`<span class="tm">${hm(o.s)}</span> `:`<div class="tm">${tm} ${icons}</div>`}<span class="ti">${esc(CAT_ICON[it.category]||'')}${esc(o.title)}</span>${!small&&o.location?`<div class="lo">📍 ${esc(o.location)}</div>`:''}${!small&&task&&it.estimateMin?`<div class="lo">${fmtH(plannedMin(it))} of ${fmtH(it.estimateMin)}</div>`:''}
    ${editable && !o.cancelled?'<div class="rz" data-rz="1"></div>':''}</div>`;
}

/* ---------- agenda ---------- */
function renderAgenda(){
  const start=cursor, end=addDays(cursor,agendaDays-1);
  $('#title').textContent = `${fmtDM(start)} – ${fmtDM(end)} ${end.getFullYear()}`;
  const tk=todayKey();
  let html='<div class="ag">';
  for(let i=0;i<agendaDays;i++){
    const d=addDays(start,i), k=key(d), e=day(k), it=dayItems(k);
    const rows=[];
    for(const f of (filt.showFlights?(e.flights||[]):[])) rows.push(`<div class="agi" style="cursor:default"><span class="t">${esc(flightDisp(k,f))}</span><div class="b" style="--c:var(--accent)"><span class="ti">✈ ${esc(f.from)} → ${esc(f.to)}</span>${f.note?`<div class="s">${esc(f.note)}</div>`:''}</div></div>`);
    for(const t of it.overdue) rows.push(agTaskRow(t,'Overdue'));
    for(const o of it.ad) rows.push(agOccRow(o,'All day'));
    for(const o of it.tm) rows.push(agOccRow(o,`${hm(o.s)}–${hm(o.e)}`));
    for(const t of it.due) rows.push(agTaskRow(t,'Due'+(t.dueTime?' '+t.dueTime:'')));
    if(!rows.length && !e.am && !e.comment && i>0 && !(k===tk)) continue;
    html+=`<div class="agd ${k===tk?'today':''} ${fillClass(k,e,isWeekend(d))?'':''}"><h3 data-k="${k}" style="cursor:pointer">${esc(fmtLong(d))} <span class="w">W${isoWeek(d)}</span>${locHTML(k)}${isWeekend(d)||!filt.showStatus?'':statusHTML(e,true).replace('class="halves"','class="halves" style="flex-direction:row;gap:4px"')}</h3>
      ${rows.join('')||'<div class="hint">Nothing planned.</div>'}${e.comment?`<div class="cm" style="margin-top:6px">💬 ${esc(e.comment)}</div>`:''}</div>`;
  }
  html+=`<button data-agmore>Show ${agendaDays} more days</button></div>`;
  return html;
}
function agOccRow(o,t){
  if(o.kind==='busy') return `<div class="agi" style="cursor:default"><span class="t">${t}</span><div class="b" style="--c:var(--busy)"><span class="ti">Busy</span></div></div>`;
  const it=o.item; const p=proj(it.projectId);
  return `<div class="agi ${o.kind==='block'?'task':''} ${o.done?'done':''}" data-occ="${esc(o.id)}" data-oc="${esc(o.occ)}" data-ok="${o.kind}" tabindex="0"><span class="t">${t}</span><div class="b" style="--c:${esc(itemColor(it))}"><span class="ti">${o.kind==='block'?'☐ ':''}${esc(CAT_ICON[it.category]||'')}${o.cancelled?'<s>':''}${esc(o.title)}${o.cancelled?'</s>':''}</span><div class="s">${[p?esc(p.name):'',o.location?'📍 '+esc(o.location):'',it.rrule?'🔁':'' ,o.missed?'<b style="color:var(--missed)">missed</b>':''].filter(Boolean).join(' · ')}</div></div></div>`;
}
function agTaskRow(t,label){
  const p=proj(t.projectId);
  return `<div class="agi task ${isOpen(t)?'':'done'}" data-task="${t.id}" tabindex="0"><span class="t" style="${label==='Overdue'?'color:var(--sick);font-weight:700':''}">⚑ ${esc(label)}</span><div class="b" style="--c:${esc(itemColor(t))}"><span class="ti">${esc(t.title)}</span><div class="s">${[p?esc(p.name):'', t.priority<=2?'P'+t.priority:''].filter(Boolean).join(' · ')}</div></div></div>`;
}

/* ---------- board ---------- */
function renderBoard(){
  $('#title').textContent='Tasks board';
  const lanes=prefs.boardLanes;
  const list=data.tasks.filter(passFilter);
  const cols=Object.keys(TASK_STATUS);
  const card=t=>taskRowHTML(t);
  let html=`<div class="vtools"><label><input type="checkbox" data-lanes ${lanes?'checked':''}> Swimlanes by project</label></div><div class="board">`;
  for(const s of cols){
    let inner='';
    const items=sortTasks(list.filter(t=>(t.status||'todo')===s), 'priority');
    if(lanes){ const g=new Map(); for(const t of items){ const p=proj(t.projectId); const n=p?p.name:'Inbox'; if(!g.has(n)) g.set(n,{p,ts:[]}); g.get(n).ts.push(t); }
      for(const [n,{p,ts}] of g) inner+=`<div class="lane"><i class="dotc" style="background:${esc(p?p.color:'var(--line2)')}"></i>${esc(n)}</div>`+ts.map(card).join(''); }
    else inner=items.slice(0, s==='done'||s==='cancelled'?40:500).map(card).join('');
    html+=`<div class="bcol" data-status="${s}"><h4><span>${TASK_STATUS[s]}</span><span>${items.length}</span></h4>${inner}${editable&&s!=='done'&&s!=='cancelled'?`<button class="ghost" data-bnew="${s}" style="text-align:left">＋ Add task</button>`:''}</div>`;
  }
  return html+'</div>';
}

/* ---------- main bindings ---------- */
function bindMain(){
  const M=$('#main');
  // day cells / headers → drawer (with multi-select for bulk status)
  $$('[data-k]',M).forEach(el=>el.addEventListener('click', ev=>{
    if(ev.target.closest('[data-occ],[data-task],[data-moreday]')) return;
    const k=el.dataset.k;
    if(editable && (ev.ctrlKey||ev.metaKey||ev.shiftKey)){
      ev.preventDefault();
      if(ev.shiftKey && lastClicked){ let a=fromKey(lastClicked), b=fromKey(k); if(a>b) [a,b]=[b,a]; for(let x=a;x<=b;x=addDays(x,1)) selected.add(key(x)); }
      else { selected.has(k)?selected.delete(k):selected.add(k); }
      lastClicked=k; render(); return;
    }
    lastClicked=k;
    if(selected.size){ selected.clear(); render(); }
    openDrawer(k);
  }));
  $$('[data-moreday]',M).forEach(el=>el.onclick=e=>{ e.stopPropagation(); cursor=fromKey(el.dataset.moreday); setView('day'); });
  // items
  $$('[data-occ]',M).forEach(el=>{
    if(el.classList.contains('ev')) return; // grid blocks: handled by pointer logic
    el.addEventListener('click',e=>{ e.stopPropagation(); openOccPopover(el.dataset.occ, el.dataset.oc, el.dataset.ok, el); });
    el.addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); openOccPopover(el.dataset.occ, el.dataset.oc, el.dataset.ok, el); } });
    el.addEventListener('dragstart',e=>{ e.dataTransfer.setData('text/x-item', el.dataset.occ); e.dataTransfer.setData('text/x-occ', el.dataset.occ+'|'+el.dataset.oc+'|'+el.dataset.ok); });
  });
  $$('[data-task]',M).forEach(el=>{
    if(el.classList.contains('trow')) return;
    el.addEventListener('click',e=>{ e.stopPropagation(); openTaskEditor(el.dataset.task); });
    el.addEventListener('keydown',e=>{ if(e.key==='Enter') openTaskEditor(el.dataset.task); });
    el.addEventListener('dragstart',e=>{ e.dataTransfer.setData('text/x-task', el.dataset.task); e.dataTransfer.setData('text/x-due', el.dataset.task); });
  });
  bindTaskRows(M);
  // drop targets: month cells, compact week columns, all-day cells
  $$('.cell[data-k],.wcol[data-k],[data-allk]',M).forEach(c=>{
    const k=c.dataset.k||c.dataset.allk;
    c.addEventListener('dragover',e=>{ if(!editable) return; e.preventDefault(); c.classList.add('dragover'); });
    c.addEventListener('dragleave',()=>c.classList.remove('dragover'));
    c.addEventListener('drop',e=>{ c.classList.remove('dragover'); if(!editable) return; e.preventDefault(); dropOnDay(e.dataTransfer, k); });
  });
  // board
  $$('.bcol',M).forEach(c=>{
    c.addEventListener('dragover',e=>{ if(!editable) return; e.preventDefault(); c.classList.add('dragover'); });
    c.addEventListener('dragleave',()=>c.classList.remove('dragover'));
    c.addEventListener('drop',e=>{ c.classList.remove('dragover'); if(!editable) return; e.preventDefault(); const id=e.dataTransfer.getData('text/x-task'); const t=task(id); if(!t) return; setTaskStatus(t, c.dataset.status); });
  });
  $$('[data-bnew]',M).forEach(b=>b.onclick=()=>openTaskEditor(null,{status:b.dataset.bnew}));
  const lanes=$('[data-lanes]',M); if(lanes) lanes.onchange=()=>{ prefs.boardLanes=lanes.checked; savePrefs(); render(); };
  const more=$('[data-agmore]',M); if(more) more.onclick=()=>{ agendaDays+=14; render(); };
  const mn=$('#monthNotes');
  if(mn) mn.addEventListener('input',()=>{ const v=mn.value; if(v) data.monthNotes[mn.dataset.mk]=v; else delete data.monthNotes[mn.dataset.mk]; changed('mn'); });
}
/* drag & drop onto a whole day (month, compact week, all-day strip) */
function dropOnDay(dt, k){
  const occ=dt.getData('text/x-occ'), due=dt.getData('text/x-due'), tid=dt.getData('text/x-task');
  if(occ){
    const [id,oc,kind]=occ.split('|');
    if(kind==='block'){ const t=task(id), b=t&&t.blocks.find(x=>x.id===oc); if(!b) return; const dd=Math.round((fromKey(k)-fromKey(b.start.slice(0,10)))/864e5); b.start=localAddMin(b.start,dd*1440); b.end=localAddMin(b.end,dd*1440); touch(t); changed(); return renderAll(); }
    const e=ev(id); if(!e) return;
    if(e.rrule) return toast('Open the event to move a repeating event.');
    const dd=Math.round((fromKey(k)-fromKey(e.start.slice(0,10)))/864e5);
    if(e.allDay){ e.start=keyAdd(e.start.slice(0,10),dd); e.end=keyAdd((e.end||e.start).slice(0,10),dd); }
    else { e.start=localAddMin(e.start,dd*1440); e.end=localAddMin(e.end,dd*1440); }
    touch(e); changed(); renderAll(); return;
  }
  if(due || tid){
    const t=task(due||tid); if(!t) return;
    t.dueDate=k; touch(t); changed(); renderAll(); toast(`“${t.title}” is now due ${fmtShort(fromKey(k))}`);
  }
}
