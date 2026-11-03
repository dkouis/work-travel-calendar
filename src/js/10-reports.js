/* ============================================================
   Reports: daily agenda, end of day, weekly review, project,
   time allocation, trips. Print, CSV, copy, email, .ics.
   ============================================================ */
const REPORTS = {daily:'Daily agenda', eod:'End-of-day summary', weekly:'Weekly review', project:'Project report', time:'Time allocation', trips:'✈ Trips'};
let rep = {kind:'daily', date:null, from:null, to:null, week:null, projectId:'', cal:'', tag:''};
let repOut = null;

/* ---------- charts (inline SVG, no library) ---------- */
function barChart(items, unit, opts={}){
  items=items.filter(x=>x.v>0||opts.keepZero); if(!items.length) return '<div class="hint">No data.</div>';
  const W=640, lh=24, lw=Math.min(220,Math.max(90,...items.map(x=>x.label.length*6.6))), H=items.length*lh+8, max=Math.max(...items.map(x=>x.v),0.01);
  return `<svg class="chart" viewBox="0 0 ${W} ${H}" width="100%" role="img" aria-label="${esc(opts.title||'Bar chart')}" style="max-width:${W}px">${items.map((x,i)=>{ const y=i*lh+4, w=(W-lw-70)*x.v/max;
    return `<text x="${lw-6}" y="${y+15}" text-anchor="end">${esc(x.label.length>34?x.label.slice(0,33)+'…':x.label)}</text><rect x="${lw}" y="${y+3}" width="${Math.max(1,w)}" height="${lh-8}" rx="3" fill="${esc(x.c||'var(--accent)')}"/><text x="${lw+w+6}" y="${y+15}" class="q">${unit==='h'?(Math.round(x.v*10)/10)+' h':x.v}</text>`; }).join('')}</svg>`;
}
function lineChart(points, opts={}){
  const W=640, H=170, p=30, max=Math.max(1,...points.map(x=>x.v)), step=(W-2*p)/Math.max(1,points.length-1);
  const xy=points.map((x,i)=>[p+i*step, H-p-(H-2*p)*x.v/max]);
  return `<svg class="chart" viewBox="0 0 ${W} ${H}" width="100%" role="img" aria-label="${esc(opts.title||'Line chart')}" style="max-width:${W}px">
    <line class="ax" x1="${p}" y1="${H-p}" x2="${W-p}" y2="${H-p}"/>${[0,.5,1].map(f=>`<text x="${p-6}" y="${H-p-(H-2*p)*f+4}" text-anchor="end" class="q">${Math.round(max*f)}</text>`).join('')}
    <polyline points="${xy.map(a=>a.join(',')).join(' ')}" fill="none" stroke="var(--accent)" stroke-width="2.5"/>
    ${xy.map((a,i)=>`<circle cx="${a[0]}" cy="${a[1]}" r="4" fill="var(--accent)"/><text x="${a[0]}" y="${a[1]-9}" text-anchor="middle">${points[i].v}</text><text x="${a[0]}" y="${H-p+16}" text-anchor="middle" class="q">${esc(points[i].label)}</text>`).join('')}</svg>`;
}

/* ---------- time accounting ---------- */
function repFilter(it){
  if(rep.cal && it.calendarId!==rep.cal) return false;
  if(rep.projectId && it.projectId!==rep.projectId && (proj(it.projectId)||{}).parentId!==rep.projectId) return false;
  if(rep.tag && !(it.tags||[]).some(t=>t.toLowerCase()===rep.tag.toLowerCase())) return false;
  if(!privKey && isPrivate(it)) return false;
  return true;
}
function timeEntries(from, to){
  const out=[];
  const pool=archive?{events:[...data.events,...arr(archive.events)], tasks:[...data.tasks,...arr(archive.tasks)]}:data;
  for(const e of pool.events){ if(!repFilter(e) || e.allDay || e.showAs==='free') continue;
    for(const o of expandEvent(e,from,to)){ if(o.cancelled) continue; const s=Math.max(o.s,from), en=Math.min(o.e,to); out.push({kind:e.category==='meeting'?'meeting':'event', it:e, s, min:(en-s)/60000, spent:(en-s)/60000*(o.e<=NOW()?1:0), k:key(new Date(s))}); } }
  for(const t of pool.tasks){ if(!repFilter(t)) continue;
    for(const b of t.blocks){ const s=zonedToMs(b.start,b.tz), en=zonedToMs(b.end,b.tz); if(en<=from||s>=to) continue; out.push({kind:'task', it:t, s, min:(en-s)/60000, spent:b.done?(b.actualMin??(en-s)/60000):0, done:!!b.done, missed:!b.done&&en<NOW()&&isOpen(t), b, k:key(new Date(s))}); } }
  return out;
}
function sumBy(list, keyFn, val='min'){ const m=new Map(); for(const x of list){ const k=keyFn(x); m.set(k,(m.get(k)||0)+x[val]); } return [...m].sort((a,b)=>b[1]-a[1]); }

/* ---------- report builders: return {title, html, head, rows, text} ---------- */
function repDaily(){
  const k=rep.date||todayKey(), d=fromKey(k), d0=d.getTime(), d1=d0+864e5, e=day(k);
  const occ=occurrences(d0,d1,{all:true}).filter(o=>o.kind==='busy'||repFilter(o.item));
  const evs=occ.filter(o=>o.kind==='event'&&!o.cancelled), bl=occ.filter(o=>o.kind==='block');
  const due=data.tasks.filter(t=>repFilter(t)&&isOpen(t)&&t.dueDate===k), od=data.tasks.filter(t=>repFilter(t)&&isOverdue(t)&&t.dueDate<k);
  const conf=conflictsIn(occ); const free=k===todayKey()?freeWorkMinutes(k):null;
  const muted=jget(remK('muted'),[]).filter(m=>m.day===keyAdd(k,-1)||m.day===k);
  const st=e.am||e.pm?(e.am===e.pm?STATUSES[e.am].label:`AM ${e.am?STATUSES[e.am].label:'—'} / PM ${e.pm?STATUSES[e.pm].label:'—'}`):'—';
  const loc=locationOf(k);
  const rows=[...evs.map(o=>[o.allDay?'All day':hm(o.s)+'–'+hm(o.e),'Event',o.title,(proj(o.item.projectId)||{}).name||'',o.location||'']),...bl.map(o=>[hm(o.s)+'–'+hm(o.e),'Task block',o.title,(proj(o.item.projectId)||{}).name||'',o.done?'done':o.missed?'missed':'']),...due.map(t=>[t.dueTime||'','Due',t.title,(proj(t.projectId)||{}).name||'','P'+t.priority]),...od.map(t=>['','Overdue',t.title,(proj(t.projectId)||{}).name||'','due '+fmtDMY(fromKey(t.dueDate))])];
  const html=`<div class="kpis"><div class="kpi"><div class="v" style="font-size:15px">${esc(st)}</div><div class="l">work status</div></div><div class="kpi"><div class="v" style="font-size:15px">${esc(loc?loc.name:'—')}</div><div class="l">location</div></div>
    <div class="kpi"><div class="v">${evs.length}</div><div class="l">events</div></div><div class="kpi"><div class="v">${fmtH(bl.reduce((a,o)=>a+(o.e-o.s)/60000,0))}</div><div class="l">task blocks</div></div>
    <div class="kpi"><div class="v">${due.length}</div><div class="l">due today</div></div><div class="kpi"><div class="v" style="${od.length?'color:var(--sick)':''}">${od.length}</div><div class="l">overdue</div></div>${free!==null?`<div class="kpi"><div class="v">${fmtH(free)}</div><div class="l">free work time left</div></div>`:''}</div>
    ${(e.flights||[]).length?`<div class="rsec">Flights</div>${e.flights.map(f=>`<div>✈ ${esc(f.from)} → ${esc(f.to)} · ${esc(flightLabel(f))}${f.note?' · '+esc(f.note):''}</div>`).join('')}`:''}
    <div class="rsec">Schedule</div>${[...evs,...bl].sort((a,b)=>a.s-b.s).map(o=>agOccRow(o,o.allDay?'All day':hm(o.s)+'–'+hm(o.e))).join('')||'<div class="hint">Nothing scheduled.</div>'}
    <div class="rsec">Tasks due</div>${due.map(t=>taskRowHTML(t)).join('')||'<div class="hint">None.</div>'}
    ${od.length?`<div class="rsec" style="color:var(--sick)">Overdue</div>${od.map(t=>taskRowHTML(t)).join('')}`:''}
    ${conf.size?`<div class="rsec">Conflicts</div><div class="warnbox">${[...conf].map(([kk,w])=>{ const o=occ.find(x=>x.id+'|'+x.occ===kk); return `⚠ ${esc(o?o.title:'')}: ${w.map(esc).join('; ')}`; }).join('<br>')}</div>`:''}
    ${muted.length?`<div class="rsec">Muted (leave/sick day)</div>${muted.map(m=>`<div class="hint">🔕 ${esc(m.title)} — ${esc(m.when)}</div>`).join('')}`:''}
    ${e.comment?`<div class="rsec">Comment</div><div>${esc(e.comment)}</div>`:''}`;
  const text=`Daily agenda ${fmtLong(d)}\nStatus: ${st} · Location: ${loc?loc.name:'—'}\n`+(e.flights||[]).map(f=>`Flight ${f.from} → ${f.to} (${flightLabel(f)})`).join('\n')+'\n'+rows.map(r=>`${r[0]}  ${r[1]}: ${r[2]}${r[3]?' ['+r[3]+']':''}${r[4]?' · '+r[4]:''}`).join('\n');
  return {title:'Daily agenda · '+fmtLong(d), html, head:['Time','Type','Title','Project','Note'], rows, text};
}
function repEod(){
  const k=rep.date||todayKey(), d0=fromKey(k).getTime(), d1=d0+864e5;
  const doneT=data.tasks.filter(t=>repFilter(t)&&t.completedAt&&isoDay(t.completedAt)===k);
  const te=timeEntries(d0,d1); const bl=te.filter(x=>x.kind==='task');
  const missed=bl.filter(x=>x.missed), doneB=bl.filter(x=>x.done);
  const planned=te.reduce((a,x)=>a+x.min,0), actual=te.reduce((a,x)=>a+(x.kind==='task'?x.spent:(x.s+x.min*60000<=NOW()?x.min:0)),0);
  const roll=data.tasks.filter(t=>repFilter(t)&&isOpen(t)&&t.dueDate&&t.dueDate<=k);
  const html=`<div class="kpis"><div class="kpi"><div class="v">${doneT.length}</div><div class="l">tasks completed</div></div><div class="kpi"><div class="v">${doneB.length}</div><div class="l">blocks done</div></div>
    <div class="kpi"><div class="v" style="${missed.length?'color:var(--missed)':''}">${missed.length}</div><div class="l">blocks missed</div></div><div class="kpi"><div class="v">${fmtH(planned)}</div><div class="l">planned</div></div><div class="kpi"><div class="v">${fmtH(actual)}</div><div class="l">actual</div></div></div>
    <div class="rsec">Done today</div>${doneT.map(t=>taskRowHTML(t)).join('')||'<div class="hint">No tasks completed.</div>'}
    <div class="rsec">Missed blocks</div>${missed.map(x=>`<div class="slot"><span>${hm(x.s)} · ${esc(x.it.title)} (${fmtDur(x.min)})</span>${editable?`<button data-rs-tom="${x.it.id}|${x.b.id}">→ tomorrow, same time</button><button data-rs-next="${x.it.id}|${x.b.id}">→ next free slot</button>`:''}</div>`).join('')||'<div class="hint">None. 👍</div>'}
    <div class="rsec">Rolls over (due today or earlier, still open)</div>${roll.map(t=>taskRowHTML(t)).join('')||'<div class="hint">Nothing.</div>'}
    ${roll.length&&editable?'<button data-roll>Move all due dates to tomorrow</button>':''}`;
  const rows=[...doneT.map(t=>['Done',t.title,'']),...missed.map(x=>['Missed block',x.it.title,hm(x.s)]),...roll.map(t=>['Rolls over',t.title,t.dueDate])];
  return {title:'End of day · '+fmtLong(fromKey(k)), html, head:['Type','Title','Note'], rows, text:`End of day ${fmtLong(fromKey(k))}\nCompleted: ${doneT.map(t=>t.title).join(', ')||'—'}\nMissed blocks: ${missed.map(x=>x.it.title).join(', ')||'—'}\nPlanned ${fmtH(planned)}, actual ${fmtH(actual)}\nRolls over: ${roll.map(t=>t.title).join(', ')||'—'}`,
    bind:B=>{
      $$('[data-rs-tom]',B).forEach(b=>b.onclick=()=>{ const [tid,bid]=b.dataset.rsTom.split('|'); const t=task(tid), bl2=t.blocks.find(x=>x.id===bid); bl2.start=localAddMin(bl2.start,1440); bl2.end=localAddMin(bl2.end,1440); touch(t); changed(); renderAll(); });
      $$('[data-rs-next]',B).forEach(b=>b.onclick=()=>{ const [tid,bid]=b.dataset.rsNext.split('|'); const t=task(tid); rescheduleBlock(t,t.blocks.find(x=>x.id===bid)); });
      const r=$('[data-roll]',B); if(r) r.onclick=()=>{ for(const t of roll){ t.dueDate=keyAdd(todayKey(),1); touch(t); } changed(); renderAll(); toast(`${roll.length} tasks moved to tomorrow`,{actions:[['Undo',undo]]}); };
    }};
}
function repWeekly(){
  const mon=rep.week?fromKey(rep.week):mondayOf(TODAY()), w0=mon.getTime(), w1=addDays(mon,7).getTime();
  const te=timeEntries(w0,w1);
  const byCal=sumBy(te,x=>x.it.calendarId), byProj=sumBy(te,x=>x.it.projectId||'inbox:'+x.it.calendarId);
  const wk=[0,1,2,3,4,5,6].map(i=>key(addDays(mon,i)));
  const tasksIn=data.tasks.filter(repFilter);
  const completed=tasksIn.filter(t=>t.completedAt && toDisp(Date.parse(t.completedAt))>=w0 && toDisp(Date.parse(t.completedAt))<w1);
  const created=tasksIn.filter(t=>t.created && toDisp(Date.parse(t.created))>=w0 && toDisp(Date.parse(t.created))<w1);
  const overdue=tasksIn.filter(t=>isOpen(t)&&t.dueDate&&t.dueDate<key(new Date(Math.min(NOW(),w1))));
  const meet=te.filter(x=>x.kind==='meeting'), missed=te.filter(x=>x.missed);
  const perDay=wk.map((k,i)=>({label:DOW3[i], v:completed.filter(t=>isoDay(t.completedAt)===k).length}));
  const n0=w1, n1=w1+7*864e5;
  const nextEv=occurrences(n0,n1,{all:true}).filter(o=>o.kind==='event'&&!o.cancelled&&repFilter(o.item));
  const nextDue=tasksIn.filter(t=>isOpen(t)&&t.dueDate&&t.dueDate>=key(new Date(n0))&&t.dueDate<key(new Date(n1)));
  const needSlots=nextDue.filter(t=>!t.blocks.some(b=>!b.done)||underScheduled(t));
  const pname=id=>id.startsWith('inbox:')?cal(id.slice(6)).name+' · Inbox':(proj(id)?proj(id).name:'?');
  const pcolor=id=>id.startsWith('inbox:')?'var(--muted)':(proj(id)||{}).color;
  const glance=wk.map((k,i)=>{ const e=day(k), l=locationOf(k); const dd=te.filter(x=>x.k===k);
    return `<tr data-k="${k}" style="cursor:pointer"><td>${DOW3[i]} ${fromKey(k).getDate()}</td><td>${i<5?esc(e.am===e.pm?(e.am?STATUSES[e.am].short:'—'):(e.am?STATUSES[e.am].short:'—')+' / '+(e.pm?STATUSES[e.pm].short:'—')):''}</td><td>${esc(l?l.name:'')}</td><td>${(e.flights||[]).map(f=>esc(code(f.from)+'→'+code(f.to))).join(', ')}</td><td class="num">${dd.filter(x=>x.kind!=='task').length}</td><td class="num">${fmtH(dd.filter(x=>x.kind==='task').reduce((a,x)=>a+x.min,0))}</td></tr>`; }).join('');
  const html=`<div class="kpis"><div class="kpi"><div class="v">${completed.length}</div><div class="l">tasks completed</div></div><div class="kpi"><div class="v">${created.length}</div><div class="l">tasks created</div></div>
    <div class="kpi"><div class="v" style="${overdue.length?'color:var(--sick)':''}">${overdue.length}</div><div class="l">overdue now</div></div><div class="kpi"><div class="v">${meet.length}</div><div class="l">meetings · ${fmtH(meet.reduce((a,x)=>a+x.min,0))}</div></div>
    <div class="kpi"><div class="v">${fmtH(te.filter(x=>x.kind==='task').reduce((a,x)=>a+x.spent,0))}</div><div class="l">task time spent</div></div><div class="kpi"><div class="v" style="${missed.length?'color:var(--missed)':''}">${missed.length}</div><div class="l">missed blocks</div></div></div>
    <div class="rsec">Week at a glance</div><table class="rt"><thead><tr><th>Day</th><th>Status</th><th>Location</th><th>Flights</th><th>Events</th><th>Task time</th></tr></thead><tbody>${glance}</tbody></table>
    <div class="rsec">Hours by calendar</div>${barChart(byCal.map(([c,m])=>({label:cal(c).name,v:m/60,c:cal(c).color})),'h',{title:'Hours by calendar'})}
    <div class="rsec">Hours by project</div>${barChart(byProj.map(([p,m])=>({label:pname(p),v:m/60,c:pcolor(p)})),'h',{title:'Hours by project'})}
    <div class="rsec">Tasks completed per day</div>${lineChart(perDay,{title:'Tasks completed per day'})}
    ${missed.length?`<div class="rsec">Missed blocks</div>${missed.map(x=>`<div class="hint">• ${esc(fmtShort(new Date(x.s)))} ${hm(x.s)} ${esc(x.it.title)}</div>`).join('')}`:''}
    <div class="rsec">Next week · ${nextEv.length} events, ${nextDue.length} tasks due</div>
    ${nextEv.slice(0,25).map(o=>agOccRow(o,`${DOW3[dow(new Date(o.s))]} ${o.allDay?'all day':hm(o.s)}`)).join('')}
    ${nextDue.map(t=>agTaskRow(t,DOW3[dowKey(t.dueDate)]+' due')).join('')}
    ${needSlots.length?`<div class="warnbox" style="margin-top:8px">${needSlots.length} task${needSlots.length>1?'s':''} due next week still need time: ${needSlots.map(t=>esc(t.title)).join(', ')} ${editable?'<button data-wsched>⚡ Schedule them</button>':''}</div>`:''}`;
  const rows=[...byProj.map(([p,m])=>['Hours',pname(p),(Math.round(m/6)/10)]),...completed.map(t=>['Completed',t.title,isoDay(t.completedAt)]),...overdue.map(t=>['Overdue',t.title,t.dueDate]),...nextDue.map(t=>['Due next week',t.title,t.dueDate])];
  const text=`Weekly review — week ${isoWeek(mon)} (${fmtDM(mon)}–${fmtDM(addDays(mon,6))})\n\nCompleted (${completed.length}): ${completed.map(t=>t.title).join('; ')||'—'}\nOverdue (${overdue.length}): ${overdue.map(t=>t.title).join('; ')||'—'}\nMeetings: ${meet.length} (${fmtH(meet.reduce((a,x)=>a+x.min,0))})\n\nHours by project:\n${byProj.map(([p,m])=>`  ${pname(p)}: ${fmtH(m)}`).join('\n')}\n\nNext week: ${nextEv.length} events; due: ${nextDue.map(t=>t.title+' ('+DOW3[dowKey(t.dueDate)]+')').join('; ')||'—'}`;
  return {title:`Weekly review · week ${isoWeek(mon)}, ${fmtDM(mon)} – ${fmtDM(addDays(mon,6))} ${mon.getFullYear()}`, html, head:['Section','Item','Value'], rows, text,
    bind:B=>{ const s=$('[data-wsched]',B); if(s) s.onclick=()=>openAutoSchedule(needSlots); $$('tr[data-k]',B).forEach(tr=>tr.onclick=()=>{ closeDialog(); cursor=fromKey(tr.dataset.k); setView('day'); }); }};
}
function repProject(){
  const ps = rep.projectId ? [proj(rep.projectId)].filter(Boolean) : data.projects.filter(p=>p.status!=='archived' && (!rep.cal||p.calendarId===rep.cal) && (privKey||!isPrivCal(p.calendarId)));
  const rows=[]; let html='';
  for(const p of ps){
    const st=projStats(p);
    rows.push([p.name, PROJ_STATUS[p.status], Math.round(st.progress*100)+'%', st.open, st.done, st.overdue, Math.round(st.est/6)/10, Math.round(st.planned/6)/10, Math.round(st.spent/6)/10, p.budgetHours??'', p.dueDate||'']);
    html+=`<div style="border:1px solid var(--line);border-radius:12px;padding:10px 12px;margin-bottom:10px;border-left:5px solid ${esc(p.color)}">
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><b style="font-size:15px">${esc(p.icon||'')} ${esc(p.name)}</b><span class="tag">${PROJ_STATUS[p.status]}</span>${p.dueDate?`<span class="hint">due ${esc(fmtShort(fromKey(p.dueDate)))}</span>`:''}<span class="sp" style="flex:1"></span><button data-popen="${p.id}">Open</button></div>
      <div class="progress" style="margin:6px 0"><i style="width:${Math.round(st.progress*100)}%"></i></div>
      <div class="hint">${Math.round(st.progress*100)}% · ${st.open} open · ${st.done} done · <span style="${st.overdue?'color:var(--sick);font-weight:700':''}">${st.overdue} overdue</span> · ${fmtH(st.planned)} planned · ${fmtH(st.spent)} spent${p.budgetHours?` · budget ${p.budgetHours} h (${Math.round(st.spent/60/p.budgetHours*100)}% used${st.planned/60>p.budgetHours?', <b style="color:var(--sick)">planned exceeds budget</b>':''})`:''}</div>
      ${ps.length===1?`<div class="rsec">Upcoming deadlines</div>${st.ts.filter(t=>isOpen(t)&&t.dueDate).sort((a,b)=>a.dueDate.localeCompare(b.dueDate)).map(t=>taskRowHTML(t)).join('')||'<div class="hint">None.</div>'}
        <div class="rsec">Completed</div>${st.ts.filter(t=>t.status==='done').map(t=>taskRowHTML(t)).join('')||'<div class="hint">None yet.</div>'}`:''}
    </div>`;
  }
  return {title:rep.projectId&&ps[0]?'Project report · '+ps[0].name:'Project report · all projects', html:html||'<div class="rempty">No projects.</div>', head:['Project','Status','Progress','Open','Done','Overdue','Estimated h','Planned h','Spent h','Budget h','Due'], rows,
    text:ps.map(p=>{ const st=projStats(p); return `${p.name}: ${Math.round(st.progress*100)}% · ${st.open} open, ${st.done} done, ${st.overdue} overdue · ${fmtH(st.planned)} planned, ${fmtH(st.spent)} spent${p.budgetHours?' of '+p.budgetHours+' h':''}`; }).join('\n'),
    bind:B=>$$('[data-popen]',B).forEach(b=>b.onclick=()=>openProjectPage(b.dataset.popen))};
}
function repTime(){
  const f=fromKey(rep.from).getTime(), t=addDays(fromKey(rep.to),1).getTime();
  const te=timeEntries(f,t);
  const total=te.reduce((a,x)=>a+x.min,0);
  const byCal=sumBy(te,x=>x.it.calendarId), byProj=sumBy(te,x=>x.it.projectId||'inbox:'+x.it.calendarId);
  const byCat=sumBy(te,x=>x.kind==='task'?'Task work (focus)':(CATEGORIES[x.it.category]||'Other').replace(/^\S+\s/,''));
  const byTag=sumBy(te.flatMap(x=>(x.it.tags.length?x.it.tags:['(no tag)']).map(tg=>({...x,tg}))),x=>x.tg);
  const byLoc=sumBy(te.filter(x=>x.it.calendarId==='work'),x=>{ const l=locationOf(x.k); return l?l.name:'Unknown'; });
  const bySt=sumBy(te.filter(x=>x.it.calendarId==='work'&&!isWeekend(fromKey(x.k))),x=>{ const e=day(x.k), h=new Date(x.s).getHours(); const s=h<13?e.am:e.pm; return s?STATUSES[s].label:'No status'; });
  const meet=te.filter(x=>x.kind==='meeting').reduce((a,x)=>a+x.min,0), focus=te.filter(x=>x.kind==='task').reduce((a,x)=>a+x.min,0);
  const pname=id=>id.startsWith('inbox:')?cal(id.slice(6)).name+' · Inbox':(proj(id)?proj(id).name:'?');
  const html=`<div class="kpis"><div class="kpi"><div class="v">${fmtH(total)}</div><div class="l">total scheduled</div></div><div class="kpi"><div class="v">${fmtH(meet)}</div><div class="l">meetings</div></div>
    <div class="kpi"><div class="v">${fmtH(focus)}</div><div class="l">focus (task blocks)</div></div><div class="kpi"><div class="v">${fmtH(te.filter(x=>x.kind==='task').reduce((a,x)=>a+x.spent,0))}</div><div class="l">task time spent</div></div></div>
    <div class="rsec">By calendar</div>${barChart(byCal.map(([c,m])=>({label:cal(c).name,v:m/60,c:cal(c).color})),'h')}
    <div class="rsec">By project</div>${barChart(byProj.map(([p,m])=>({label:pname(p),v:m/60,c:p.startsWith('inbox:')?'var(--muted)':proj(p)&&proj(p).color})),'h')}
    <div class="rsec">By type</div>${barChart(byCat.map(([c,m])=>({label:c,v:m/60})),'h')}
    <div class="rsec">By tag</div>${barChart(byTag.map(([c,m])=>({label:'#'+c,v:m/60,c:'var(--abroad)'})),'h')}
    <div class="rsec">Work time by location</div>${barChart(byLoc.map(([c,m])=>({label:c,v:m/60,c:'var(--office)'})),'h')}
    <div class="rsec">Work time by work status</div>${barChart(bySt.map(([c,m])=>({label:c,v:m/60,c:'var(--tele)'})),'h')}`;
  const rows=[...byCal.map(([c,m])=>['Calendar',cal(c).name,Math.round(m/6)/10]),...byProj.map(([p,m])=>['Project',pname(p),Math.round(m/6)/10]),...byCat.map(([c,m])=>['Type',c,Math.round(m/6)/10]),...byTag.map(([c,m])=>['Tag',c,Math.round(m/6)/10]),...byLoc.map(([c,m])=>['Location',c,Math.round(m/6)/10]),...bySt.map(([c,m])=>['Work status',c,Math.round(m/6)/10])];
  return {title:`Time allocation · ${fmtDMY(fromKey(rep.from))} – ${fmtDMY(fromKey(rep.to))}`, html, head:['Dimension','Value','Hours'], rows, text:`Time allocation ${fmtDMY(fromKey(rep.from))} – ${fmtDMY(fromKey(rep.to))}\nTotal ${fmtH(total)}, meetings ${fmtH(meet)}, focus ${fmtH(focus)}\n`+byProj.map(([p,m])=>`  ${pname(p)}: ${fmtH(m)}`).join('\n')};
}
/* Trips (kept from v1) + events during each trip and linked project */
function tripsBetween(fromK, toK){
  const rows=[], fk=flightKeys();
  Object.keys(data.days).filter(k=>k>=fromK && k<=toK).sort().forEach(k=>{
    const d=fromKey(k);
    (data.days[k].flights||[]).forEach(f=>{
      const nextK=fk.find(x=>x>k)||keyAdd(k,1);
      const evCount=occurrences(d.getTime(), fromKey(nextK).getTime(),{all:true}).filter(o=>o.kind==='event'&&!o.cancelled&&(privKey||!isPrivate(o.item))).length;
      const pj=data.projects.find(p=>p.tripFrom && p.tripFrom<=k && (p.tripTo||p.tripFrom)>=k);
      rows.push({k, date:fmtDMY(d), day:DAY_NAMES[dow(d)], from:f.from||'', to:f.to||'', period:flightLabel(f), note:f.note||'', events:evCount, project:pj?pj.name:'', pid:pj?pj.id:''});
    });
  });
  return rows;
}
function repTrips(){
  const rows=tripsBetween(rep.from, rep.to); const rangeTxt=fmtDMY(fromKey(rep.from))+' – '+fmtDMY(fromKey(rep.to));
  if(!rows.length) return {title:'Trips '+rangeTxt, html:`<div class="rempty">No trips between ${rangeTxt}.</div>`, head:[], rows:[], text:''};
  const count={}; rows.forEach(r=>{ const kk=r.from+' → '+r.to; count[kk]=(count[kk]||0)+1; });
  const by=Object.entries(count).sort((a,b)=>b[1]-a[1]).map(([kk,n])=>`${esc(kk)}: <b>${n}</b>`).join(' · ');
  const html=`<p class="rsum"><b>${rows.length}</b> trip${rows.length===1?'':'s'} from ${rangeTxt} &nbsp;·&nbsp; ${by}</p>
    <table class="rt"><thead><tr><th>#</th><th>Date</th><th>Day</th><th>From</th><th>To</th><th>Period of day</th><th>Events during trip</th><th>Project</th></tr></thead><tbody>
    ${rows.map((r,i)=>`<tr data-k="${r.k}" style="cursor:pointer" title="Open this day"><td class="num">${i+1}</td><td class="num">${r.date}</td><td>${r.day}</td>
      <td class="route">${esc(r.from)}</td><td class="route">${esc(r.to)}</td><td>${esc(r.period)}${r.note?` <span class="note">· ${esc(r.note)}</span>`:''}</td><td class="num">${r.events}</td><td>${r.pid?`<a href="#" data-popen="${r.pid}">${esc(r.project)}</a>`:''}</td></tr>`).join('')}
    </tbody></table>`;
  return {title:(data.owner?data.owner+' · ':'')+'Trips '+rangeTxt, html, head:['Date','Day','From','To','Period of day','Note','Events during trip','Project'], rows:rows.map(r=>[r.date,r.day,r.from,r.to,r.period,r.note,r.events,r.project]),
    text:`Trips ${rangeTxt}\n`+rows.map(r=>`${r.date} ${r.day}: ${r.from} → ${r.to} (${r.period})${r.note?' '+r.note:''}`).join('\n'),
    bind:B=>{ $$('tr[data-k]',B).forEach(tr=>tr.onclick=e=>{ if(e.target.closest('[data-popen]')) return; closeDialog(); cursor=fromKey(tr.dataset.k); render(); openDrawer(tr.dataset.k); }); $$('[data-popen]',B).forEach(a=>a.onclick=e=>{ e.preventDefault(); openProjectPage(a.dataset.popen); }); }};
}

/* ---------- range presets ---------- */
function setRange(q){
  const t=TODAY(); let a,b;
  if(q==='month'){ a=new Date(t.getFullYear(),t.getMonth(),1); b=new Date(t.getFullYear(),t.getMonth()+1,0); }
  else if(q==='prevmonth'){ a=new Date(t.getFullYear(),t.getMonth()-1,1); b=new Date(t.getFullYear(),t.getMonth(),0); }
  else if(q==='week'){ a=mondayOf(t); b=addDays(a,6); }
  else if(q==='prevweek'){ a=addDays(mondayOf(t),-7); b=addDays(a,6); }
  else if(q==='year'){ a=new Date(t.getFullYear(),0,1); b=new Date(t.getFullYear(),11,31); }
  else if(q==='all'){ const ks=rep.kind==='trips'?flightKeys():[...Object.keys(data.days), ...data.events.map(e=>e.start.slice(0,10)), ...data.tasks.flatMap(x=>x.blocks.map(b=>b.start.slice(0,10)))].sort(); if(ks.length){ a=fromKey(ks[0]); b=fromKey(ks[ks.length-1]); } else a=b=t; }
  else { const [x,y]=visibleRange(); a=x; b=addDays(y,-1); }
  rep.from=key(a); rep.to=key(b);
}

/* ---------- dialog ---------- */
function openReports(kind, opts={}){
  rep.kind=kind||rep.kind;
  if(opts.date) rep.date=opts.date;
  if(opts.projectId!==undefined) rep.projectId=opts.projectId;
  if(!rep.from) setRange('month');
  if(kind==='trips' && !opts.keep){ setRange('view'); }
  const draw=()=>{
    const r = {daily:repDaily, eod:repEod, weekly:repWeekly, project:repProject, time:repTime, trips:repTrips}[rep.kind]();
    repOut=r;
    const ctrl={
      daily:`<label>Date <input type="date" data-rd value="${rep.date||todayKey()}"></label><div class="quick"><button data-day="-1">‹ Previous day</button><button data-day="0">Today</button><button data-day="1">Next day ›</button></div>`,
      eod:`<label>Date <input type="date" data-rd value="${rep.date||todayKey()}"></label><div class="quick"><button data-day="0">Today</button></div>`,
      weekly:`<label>Week starting <input type="date" data-rw value="${rep.week||key(mondayOf(TODAY()))}"></label><div class="quick"><button data-wk="-1">‹ Previous week</button><button data-wk="0">This week</button><button data-wk="1">Next week ›</button></div>`,
      project:`<label>Project <select data-rp><option value="">All projects</option>${data.projects.filter(p=>p.status!=='archived'&&(privKey||!isPrivCal(p.calendarId))).map(p=>`<option value="${p.id}" ${rep.projectId===p.id?'selected':''}>${esc(p.name)}</option>`).join('')}</select></label>`,
      time:'', trips:''}[rep.kind];
    const range = rep.kind==='time'||rep.kind==='trips' ? `<label>From <input type="date" data-rf value="${rep.from}"></label><label>To <input type="date" data-rt value="${rep.to}"></label><div class="quick">${[['view','Shown on calendar'],['week','This week'],['prevweek','Last week'],['month','This month'],['prevmonth','Last month'],['year','This year'],['all','All']].map(([q,l])=>`<button data-q="${q}">${l}</button>`).join('')}</div>` : '';
    const filters = ['weekly','time','daily','eod'].includes(rep.kind) ? `<label>Calendar <select data-rc><option value="">All</option>${data.calendars.map(c=>`<option value="${c.id}" ${rep.cal===c.id?'selected':''}>${esc(c.name)}</option>`).join('')}</select></label>${rep.kind!=='project'?`<label>Project <select data-rp><option value="">All</option>${data.projects.filter(p=>p.status!=='archived').map(p=>`<option value="${p.id}" ${rep.projectId===p.id?'selected':''}>${esc(p.name)}</option>`).join('')}</select></label>`:''}<label>Tag <input type="text" data-rtag value="${esc(rep.tag)}" placeholder="any" style="width:100px"></label>` : '';
    dd.querySelector('.box').innerHTML=`<div class="dhd"><h2>📊 Reports</h2><button class="ghost" data-close>✕</button></div>
      <div class="rep"><nav class="repnav">${Object.entries(REPORTS).map(([k,l])=>`<button data-rk="${k}" class="${rep.kind===k?'on':''}">${l}</button>`).join('')}</nav>
      <div style="min-width:0"><h3 style="margin:0 0 10px">${esc(r.title)}</h3>
        <div class="rctrl">${ctrl}${range}${filters}</div>
        <div id="repBody">${r.html}</div>
        <div class="ractions"><button data-ra="copy" title="Copy as text to paste into an email">📋 Copy as text</button><button data-ra="mail">✉ Email</button>${r.rows.length?'<button data-ra="csv">⬇ CSV (Excel)</button>':''}${['time','trips','weekly','daily'].includes(rep.kind)?'<button data-ra="ics">📤 .ics</button>':''}<button data-ra="print" class="primary">🖨 Print / PDF</button></div>
      </div></div>`;
    const B=dd.querySelector('.box');
    $$('[data-close]',B).forEach(b=>b.onclick=()=>closeDialog(dd));
    $$('[data-rk]',B).forEach(b=>b.onclick=()=>{ rep.kind=b.dataset.rk; if(rep.kind==='trips'||rep.kind==='time') { if(!rep.from) setRange('month'); } draw(); });
    const on=(s,fn)=>{ const el=$(s,B); if(el) el.onchange=()=>{ fn(el.value); draw(); }; };
    on('[data-rd]',v=>rep.date=v); on('[data-rw]',v=>rep.week=key(mondayOf(fromKey(v)))); on('[data-rp]',v=>rep.projectId=v); on('[data-rf]',v=>rep.from=v); on('[data-rt]',v=>rep.to=v); on('[data-rc]',v=>rep.cal=v); on('[data-rtag]',v=>rep.tag=v.trim().replace(/^#/,''));
    if(rep.from>rep.to){ [rep.from,rep.to]=[rep.to,rep.from]; }
    $$('[data-day]',B).forEach(b=>b.onclick=()=>{ const n=+b.dataset.day; rep.date=n?keyAdd(rep.date||todayKey(),n):todayKey(); draw(); });
    $$('[data-wk]',B).forEach(b=>b.onclick=()=>{ const n=+b.dataset.wk; rep.week=n?key(addDays(fromKey(rep.week||key(mondayOf(TODAY()))),7*n)):key(mondayOf(TODAY())); draw(); });
    $$('[data-q]',B).forEach(b=>b.onclick=()=>{ setRange(b.dataset.q); draw(); });
    bindTaskRows(B); $$('#repBody [data-occ]',B).forEach(x=>x.onclick=()=>openOccPopover(x.dataset.occ,x.dataset.oc,x.dataset.ok,x));
    if(r.bind) r.bind(B);
    $$('[data-ra]',B).forEach(b=>b.onclick=()=>repAction(b.dataset.ra, r));
  };
  if(opts.digest && !opts.date) rep.date=todayKey();
  if(kind==='daily'||kind==='eod') rep.date=opts.date||todayKey();
  const dd=openDialog('',{size:'wide', refresh:()=>draw()});
  draw();
}
async function repAction(a, r){
  if(a==='print'){
    const pa=$('#printArea'); pa.innerHTML=`<h2 style="margin:0 0 4px">${esc(r.title)}</h2><div class="print-title" style="display:block;color:#555;font-size:12px;margin-bottom:10px">${esc(data.owner||'')} · printed ${esc(fmtWhen(nowISO()))}</div>${r.html}`;
    document.body.classList.add('print-rep'); setTimeout(()=>{ window.print(); document.body.classList.remove('print-rep'); pa.innerHTML=''; },50); return;
  }
  if(a==='csv'){
    const q=v=>'"'+String(v??'').replace(/"/g,'""')+'"';
    const csv='﻿'+[r.head,...r.rows].map(x=>x.map(q).join(';')).join('\r\n');
    download(r.title.replace(/[^\w\-]+/g,'_').replace(/_+/g,'_').slice(0,80)+'.csv', csv, 'text/csv;charset=utf-8'); return;
  }
  if(a==='copy'){
    try{ await navigator.clipboard.writeText(r.text); }catch(e){ const ta=document.createElement('textarea'); ta.value=r.text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); }
    toast('Copied to the clipboard'); return;
  }
  if(a==='mail'){ const body=r.text.length>1800?r.text.slice(0,1790)+'\n…':r.text; location.href='mailto:?subject='+encodeURIComponent(r.title)+'&body='+encodeURIComponent(body); return; }
  if(a==='ics'){
    let from,to; if(rep.kind==='daily'){ from=to=rep.date||todayKey(); } else if(rep.kind==='weekly'){ from=rep.week||key(mondayOf(TODAY())); to=keyAdd(from,6); } else { from=rep.from; to=rep.to; }
    exportIcs({from, to, name:r.title, filter:repFilter, flights:rep.kind==='trips'});
  }
}
