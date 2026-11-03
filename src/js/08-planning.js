/* ============================================================
   Planning: free slots, reschedule, auto-schedule, plan my day,
   timer, focus mode
   ============================================================ */
function busyIntervals(fromMs, toMs, exclude){
  const out=[];
  for(const o of occurrences(fromMs,toMs,{all:true,noCache:true})){
    if(o.allDay || o.cancelled) continue;
    if(o.kind==='event' && o.item.showAs==='free') continue;
    if(o.kind==='block' && o.done) continue;
    if(exclude && exclude.id && o.id===exclude.id && o.kind==='block') continue;
    out.push([o.s,o.e]);
  }
  if(privLocked) for(const b of arr(data.busy)) if(b.s){ const bs=toDisp(b.s), be=toDisp(b.e); if(be>fromMs && bs<toMs) out.push([bs,be]); }
  if(exclude && exclude.blocks) for(const b of exclude.blocks){ if(b.done) continue; const s=zonedToMs(b.start,b.tz), e=zonedToMs(b.end,b.tz); if(e>fromMs && s<toMs) out.push([s,e]); }
  for(let d=startOfDay(new Date(fromMs)); d.getTime()<toMs; d=addDays(d,1)) for(const w of flightWindows(key(d))) out.push([w.s,w.e]);
  for(const w of flightWindows(key(addDays(startOfDay(new Date(fromMs)),-1)))) out.push([w.s,w.e]);
  return out.sort((a,b)=>a[0]-b[0]);
}
function quietWin(){ return [hmToMin(prefs.quietEnd)??450, hmToMin(prefs.quietStart)??1320]; }
/* windows (minutes of the day) where a task of this calendar may be scheduled */
function allowedWindows(k, calendarId){
  const personal = calendarId!=='work';
  const wd=dow(fromKey(k)), work=S().workDays.includes(wd);
  const ws=hmToMin(S().workStart)||540, we=hmToMin(S().workEnd)||1080; const [qe,qs]=quietWin();
  if(!personal){ if(!work) return []; let wins=[[ws,we]];
    for(const x of offIntervals(k)) wins=wins.flatMap(([a,b])=>[[a,Math.min(b,x.a)],[Math.max(a,x.b),b]]).filter(([a,b])=>b-a>=15);
    return wins; }
  return work ? [[qe,ws],[we,qs]].filter(([a,b])=>b-a>=15) : [[Math.max(qe,540),qs]];
}
/* opts: {len, calendarId, dueDate, dueTime, exclude:{id,blocks}, n, fromMs, days, onlyDay} */
function findSlots(opts){
  const len=Math.max(15,opts.len||60), n=opts.n||3, out=[];
  const now=NOW(), from=Math.max(now, opts.fromMs||now);
  const fromRounded=Math.ceil(from/(15*60000))*15*60000;
  const cutoff = opts.dueDate ? zonedToMs(opts.dueDate+'T'+(opts.dueTime||S().workEnd||'18:00'), S().homeTz) : null;
  const days = opts.onlyDay ? 1 : (opts.days || (cutoff ? Math.max(1, Math.ceil((cutoff-from)/864e5)+1) : 14));
  const start = opts.onlyDay ? fromKey(opts.onlyDay) : startOfDay(new Date(from));
  const end = addDays(start, Math.min(days,60)).getTime();
  const busy=busyIntervals(start.getTime(), end, opts.exclude);
  for(let i=0;i<Math.min(days,60) && out.length<n;i++){
    const d=addDays(start,i), k=key(d), d0=d.getTime();
    for(const [a,b] of allowedWindows(k, opts.calendarId||'work')){
      let ws=Math.max(d0+a*60000, fromRounded), we=d0+b*60000;
      if(cutoff) we=Math.min(we,cutoff);
      let t=ws;
      for(const [bs,be] of busy){
        if(be<=t) continue; if(bs>=we) break;
        if(bs-t>=len*60000){ out.push({s:t,e:t+len*60000,k}); if(out.length>=n) return out; }
        t=Math.max(t,be);
      }
      if(we-t>=len*60000 && out.length<n) out.push({s:t,e:t+len*60000,k});
      if(out.length>=n) return out;
    }
  }
  return out;
}
function slotToBlock(sl){ const tz=ACTIVE_TZ; return {start:msToZoned(sl.s,tz), end:msToZoned(sl.e,tz), tz}; }
function rescheduleBlock(t, b, quiet){
  const len=localDiffMin(b.start,b.end);
  const sl=findSlots({len, calendarId:t.calendarId, exclude:{id:t.id, blocks:t.blocks.filter(x=>x!==b)}, n:1})[0];
  if(!sl){ if(!quiet) toast('No free slot found in the next 14 days.'); return false; }
  Object.assign(b, slotToBlock(sl)); touch(t);
  if(!quiet){ changed(); renderAll(); toast(`Moved to ${fmtShort(new Date(sl.s))} ${hm(sl.s)}`,{actions:[['Undo',undo]]}); }
  return true;
}
function freeWorkMinutes(k){
  const d0=fromKey(k).getTime(); let free=0;
  const busy=busyIntervals(d0, d0+864e5);
  for(const [a,b] of allowedWindows(k,'work')){
    let s=Math.max(d0+a*60000, NOW()), e=d0+b*60000; if(e<=s) continue;
    let tot=e-s; for(const [bs,be] of busy){ const o=Math.min(e,be)-Math.max(s,bs); if(o>0) tot-=o; }
    free+=Math.max(0,tot);
  }
  return free/60000;
}

/* ---------- auto-schedule (preview, then accept) ---------- */
function planTasks(ts, opts={}){
  const order=[...ts].filter(isOpen).sort((a,b)=>(a.dueDate||'9999').localeCompare(b.dueDate||'9999')||(a.priority||3)-(b.priority||3));
  const extra=[], plan=[], skipped=[];
  for(const t of order){
    let need = t.estimateMin ? remainingMin(t)-futurePlannedMin(t) : (t.blocks.some(b=>!b.done)?0:(S().defaultBlockMin||60));
    if(need<=0){ skipped.push({t,why:'already scheduled'}); continue; }
    let guard=0, got=0;
    while(need>0 && guard++<12){
      const len=Math.min(need,120);
      const sl=findSlots({len:Math.max(15,Math.round(len/15)*15), calendarId:t.calendarId, dueDate:opts.ignoreDue?null:t.dueDate, dueTime:t.dueTime, exclude:{blocks:[...extra]}, n:1, onlyDay:opts.onlyDay})[0];
      if(!sl) break;
      const b={id:uid('b'),...slotToBlock(sl),done:false};
      extra.push(b); plan.push({t,b,s:sl.s,e:sl.e}); need-=len; got+=len;
    }
    if(need>0) skipped.push({t,why:got?`${fmtDur(need)} could not be placed`:(opts.onlyDay?'no free time today':'no free time before the due date')});
  }
  return {plan, skipped};
}
function openAutoSchedule(ts, opts={}){
  if(!ts || !ts.length){ ts=data.tasks.filter(t=>isOpen(t) && passFilter(t) && SMART.unscheduled.f(t)); }
  if(!ts.length) return toast('No unscheduled open tasks.');
  const {plan, skipped}=planTasks(ts, opts);
  const d=openDialog(`<div class="dhd"><h2>⚡ Auto-schedule${opts.onlyDay?' today':''}</h2><button class="ghost" data-close>✕</button></div>
    <p class="hint">Free slots are filled in order of due date, then priority, inside your work hours (Personal tasks: outside work hours), skipping events, flights, leave/sick days and quiet hours. Nothing changes until you accept.</p>
    ${plan.length?`<table class="rt"><thead><tr><th>Task</th><th>When</th><th>Length</th></tr></thead><tbody>${plan.map(p=>`<tr><td><i class="dotc" style="display:inline-block;background:${esc(itemColor(p.t))}"></i> ${esc(p.t.title)}</td><td class="num">${esc(fmtShort(new Date(p.s)))} ${hm(p.s)}–${hm(p.e)}</td><td class="num">${fmtDur((p.e-p.s)/60000)}</td></tr>`).join('')}</tbody></table>`:'<div class="rempty">No free slots found.</div>'}
    ${skipped.length?`<div class="rsec">Not placed</div>${skipped.map(x=>`<div class="hint">• ${esc(x.t.title)} — ${esc(x.why)}</div>`).join('')}`:''}
    <div class="dacts"><span class="sp"></span><button data-close>Discard</button>${plan.length&&editable?'<button class="primary" id="asOk">Accept</button>':''}</div>`,{size:'mid'});
  const ok=$('#asOk',d); if(ok) ok.onclick=()=>{ for(const p of plan){ p.t.blocks.push(p.b); touch(p.t); } changed(); closeDialog(d); selTasks.clear(); renderAll(); toast(`Scheduled ${plan.length} block${plan.length>1?'s':''}`,{actions:[['Undo',undo]]}); };
}

/* ---------- plan my day ---------- */
function openPlanDay(){
  const draw=()=>{
    const tk=todayKey();
    const cand=data.tasks.filter(t=>isOpen(t) && passFilter(t) && (isOverdue(t) || t.dueDate===tk || (t.priority<=2 && !t.blocks.some(b=>!b.done && zonedToMs(b.end,b.tz)>NOW()))))
      .sort((a,b)=>(a.dueDate||'9999').localeCompare(b.dueDate||'9999')||(a.priority||3)-(b.priority||3));
    const free=findSlots({len:30,calendarId:'work',onlyDay:tk,n:12});
    const d0=fromKey(tk).getTime(), occ=occurrences(d0,d0+864e5,{all:true}).filter(o=>!o.allDay&&!o.cancelled&&o.kind!=='busy');
    dd.querySelector('.box').innerHTML=`<div class="dhd"><h2>🗓 Plan my day · ${esc(fmtShort(new Date(NOW())))}</h2><button class="ghost" data-close>✕</button></div>
      <div class="rep" style="grid-template-columns:1fr 1fr">
        <div><div class="rsec" style="margin-top:0">Tasks to place (${cand.length})</div>
          ${cand.map(t=>`<div style="display:flex;gap:6px;align-items:flex-start;margin-bottom:5px"><div style="flex:1">${taskRowHTML(t)}</div>${editable?`<button data-pslot="${t.id}" title="Put it in the next free slot today">→ today</button>`:''}</div>`).join('')||'<div class="hint">Nothing urgent. 🎉</div>'}
        </div>
        <div><div class="rsec" style="margin-top:0">Today</div>
          ${occ.map(o=>`<div class="hint">${hm(o.s)}–${hm(o.e)} · ${esc(o.title)}${o.kind==='block'?' (task)':''}</div>`).join('')||'<div class="hint">No events or blocks yet.</div>'}
          <div class="rsec">Free work time left: ${fmtH(freeWorkMinutes(tk))}</div>
          ${free.slice(0,6).map(s=>`<div class="hint">${hm(s.s)} onwards</div>`).join('')}
        </div></div>
      <div class="dacts"><span class="sp"></span><button data-close>Close</button>${editable&&cand.length?'<button class="primary" id="pdAuto">⚡ Auto-fill today (preview)</button>':''}</div>`;
    const B=dd.querySelector('.box');
    $$('[data-close]',B).forEach(b=>b.onclick=()=>closeDialog(dd));
    bindTaskRows(B);
    $$('[data-pslot]',B).forEach(b=>b.onclick=()=>{ const t=task(b.dataset.pslot); const len=Math.min(120,Math.max(15,(t.estimateMin?remainingMin(t)-futurePlannedMin(t):0)||(S().defaultBlockMin||60)));
      const sl=findSlots({len, calendarId:t.calendarId, onlyDay:tk, n:1})[0]; if(!sl) return toast('No free slot left today.'); t.blocks.push({id:uid('b'),...slotToBlock(sl),done:false}); touch(t); changed(); renderAll(); toast(`“${t.title}” at ${hm(sl.s)}`,{actions:[['Undo',undo]]}); });
    const au=$('#pdAuto',B); if(au) au.onclick=()=>openAutoSchedule(cand,{onlyDay:tk, ignoreDue:true});
  };
  const dd=openDialog('',{size:'wide',refresh:()=>draw()}); draw();
}

/* ---------- timer on a block ---------- */
function timerKey(){ return 'wtc-timer:'+(session?session.user.toLowerCase():'-'); }
function getTimer(){ return jget(timerKey(),null); }
function startTimer(taskId, blockId){
  const cur=getTimer(); if(cur && !confirm('Another timer is running. Stop it and start this one?')) return;
  if(cur) stopTimer(true);
  jset(timerKey(),{taskId, blockId, start:Date.now()}); renderTimerSlot(); toast('Timer started');
}
async function stopTimer(silent){
  const tm=getTimer(); if(!tm) return; safeDel(timerKey()); renderTimerSlot();
  const mins=Math.max(1,Math.round((Date.now()-tm.start)/60000));
  const t=task(tm.taskId); if(!t || silent) return;
  const b=t.blocks.find(x=>x.id===tm.blockId);
  const m=await askTimeSpent(mins, t.title); if(m===null) return;
  if(b){ b.done=true; b.actualMin=m; } if(t.status==='todo') t.status='in-progress';
  touch(t); changed(); renderAll(); budgetCheck(proj(t.projectId)); toast(`Logged ${fmtDur(m)} on “${t.title}”`,{actions:[['Undo',undo]]});
}
let _timerTick=null;
function renderTimerSlot(){
  const el=$('#timerSlot'); if(!el) return; const tm=getTimer();
  clearInterval(_timerTick);
  if(!tm){ el.innerHTML=''; return; }
  const t=task(tm.taskId);
  const upd=()=>{ const s=Math.floor((Date.now()-tm.start)/1000); el.innerHTML=`<button class="timerbadge" title="Timer for “${esc(t?t.title:'')}” — click to stop and log the time">⏱ ${Math.floor(s/3600)?Math.floor(s/3600)+':':''}${String(Math.floor(s/60)%60).padStart(2,'0')}:${String(s%60).padStart(2,'0')}</button>`; $('button',el).onclick=()=>stopTimer(); };
  upd(); _timerTick=setInterval(upd,1000);
}

/* ---------- focus mode ---------- */
let _focusTick=null;
function startFocus(taskId, blockId){
  const t=task(taskId); if(!t) return;
  const b=t.blocks.find(x=>x.id===blockId);
  if(!getTimer()) jset(timerKey(),{taskId, blockId, start:Date.now()});
  const F=$('#focus'); F.classList.add('open');
  const endMs=b?zonedToMs(b.end,b.tz):null;
  const next=occurrences(NOW(),NOW()+864e5,{all:true}).find(o=>o.s>NOW() && !o.allDay && o.id!==taskId && !o.cancelled && o.kind!=='busy');
  const draw=()=>{
    const tm=getTimer(); const el=Math.floor((Date.now()-(tm?tm.start:Date.now()))/1000);
    const left=endMs?Math.max(0,Math.floor((endMs-NOW())/1000)):null;
    F.innerHTML=`<div class="hint">🎯 Focus${proj(t.projectId)?' · '+esc(proj(t.projectId).name):''}</div>
      <div class="ft">${esc(t.title)}</div>
      <div class="clock" aria-live="off">${String(Math.floor(el/60)).padStart(2,'0')}:${String(el%60).padStart(2,'0')}</div>
      ${left!==null?`<div class="hint">${left>0?`${Math.floor(left/60)} min left in this block (ends ${hm(endMs)})`:'This block has ended'}</div>`:''}
      ${t.checklist.length?`<div style="text-align:left;max-width:420px">${t.checklist.map((c,i)=>`<label style="display:block;margin:3px 0"><input type="checkbox" data-fc="${i}" ${c.done?'checked':''} ${editable?'':'disabled'}> ${esc(c.text)}</label>`).join('')}</div>`:''}
      ${next?`<div class="next">Next: ${esc(next.title)} at ${hm(next.s)}</div>`:''}
      <div class="dacts" style="justify-content:center"><button class="primary" id="fStop">■ Stop and log time</button><button id="fExit">Leave focus (timer keeps running)</button></div>`;
    $$('[data-fc]',F).forEach(c=>c.onchange=()=>{ t.checklist[+c.dataset.fc].done=c.checked; touch(t); changed(); });
    $('#fStop').onclick=()=>{ endFocus(); stopTimer(); };
    $('#fExit').onclick=()=>{ endFocus(); renderTimerSlot(); };
  };
  draw(); clearInterval(_focusTick); _focusTick=setInterval(()=>{ const c=$('#focus .clock'); if(!c) return; const tm=getTimer(); const el=Math.floor((Date.now()-(tm?tm.start:Date.now()))/1000); c.textContent=String(Math.floor(el/60)).padStart(2,'0')+':'+String(el%60).padStart(2,'0'); },1000);
  renderTimerSlot();
}
function endFocus(){ clearInterval(_focusTick); $('#focus').classList.remove('open'); $('#focus').innerHTML=''; }
