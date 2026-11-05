/* ============================================================
   Reminders & notifications
   Fire while the app is open in any tab (one tab fires, the others
   follow). Reminders missed while closed appear on the next open.
   State (fired, snoozed, notification list) is kept per browser.
   ============================================================ */
const TAB_ID = uid('tab');
const remK = s => `wtc-${s}:${session?session.user.toLowerCase():'-'}`;
let _bc=null; try{ _bc=new BroadcastChannel('wtc'); _bc.onmessage=e=>{ const m=e.data;
  if(m==='notif') renderBell(); if(m==='dismiss') syncToasts();
  if(m && m.type==='saved' && session) reloadData(false);                 // another tab saved: pick it up now
  if(m && m.type==='goto' && !DETAIL && session) gotoDay(m.k);             // a details tab asked to show a day
}; }catch(e){}
function isLeader(){
  const l=jget('wtc-leader',null), now=Date.now();
  // details tabs only take over if no calendar tab is running
  if(!l || l.id===TAB_ID || now-l.ts>(DETAIL?70000:25000)){ jset('wtc-leader',{id:TAB_ID,ts:now}); return jget('wtc-leader',{}).id===TAB_ID; }
  return false;
}
addEventListener('beforeunload',()=>{ const l=jget('wtc-leader',null); if(l && l.id===TAB_ID) safeDel('wtc-leader'); });

/* every reminder that should fire in [from, to) */
function anchorTime(k){ return zonedToMs(k+'T'+(S().allDayRemTime||'09:00'), ACTIVE_TZ); }
function dueReminders(from, to){ // from/to: real time; returns display times
  from=toDisp(from); to=toDisp(to);
  const out=[], look=Math.max(10080, ...data.events.flatMap(e=>e.reminders.map(r=>+r.offsetMin||0)), ...data.tasks.flatMap(t=>t.reminders.map(r=>+r.offsetMin||0)))*60000;
  const occs=occurrences(from-864e5, to+look+864e5, {all:true,noCache:true});
  for(const o of occs){
    if(o.kind==='event'){
      if(o.cancelled) continue;
      const at = o.allDay ? anchorTime(o.dateKey) : o.s;
      for(const r of o.item.reminders){ const f=at-(+r.offsetMin||0)*60000; if(f>=from && f<to) out.push({key:`${o.id}|${o.occ}|start|${r.offsetMin}`, fireAt:f, at, kind:'event', o, r}); }
    } else if(o.kind==='block'){
      if(o.done || !isOpen(o.item)) continue;
      for(const r of o.item.reminders.filter(r=>r.anchor==='block')){ const f=o.s-(+r.offsetMin||0)*60000; if(f>=from && f<to) out.push({key:`${o.id}|${o.occ}|block|${r.offsetMin}`, fireAt:f, at:o.s, kind:'block', o, r}); }
    }
  }
  for(const t of data.tasks){
    if(!isOpen(t) || !t.dueDate) continue;
    const at=t.dueTime?dueMs(t):anchorTime(t.dueDate);
    for(const r of t.reminders.filter(r=>r.anchor==='due')){ const f=at-(+r.offsetMin||0)*60000; if(f>=from && f<to) out.push({key:`${t.id}|${t.dueDate}|due|${r.offsetMin}`, fireAt:f, at, kind:'due', t, r}); }
  }
  for(const p of data.projects){
    if(!p.dueDate || p.status!=='active') continue;
    const at=anchorTime(p.dueDate);
    for(const r of arr(p.reminders)){ const f=at-(+r.offsetMin||0)*60000; if(f>=from && f<to) out.push({key:`${p.id}|${p.dueDate}|pdue|${r.offsetMin}`, fireAt:f, at, kind:'pdue', p, r}); }
  }
  // flight alarms: Morning 06:00, Noon 14:00, Night 17:30 (local time of departure), editable per flight
  if(S().travelReminders){
    for(let d=addDays(startOfDay(new Date(from)),-1); d.getTime()<to+864e5; d=addDays(d,1)){
      const k=key(d); flightWindows(k).forEach((w,i)=>{
        const am=flightAlarmMin(w.f); if(am===null) return;
        const f=zonedToMs(k+'T'+minToHM(am), w.tz); if(f<from || f>=to) return;
        const evs=occurrences(w.s-12*3600000, w.s+18*3600000,{all:true,noCache:true}).filter(o=>o.kind==='event' && !o.allDay && !o.cancelled);
        out.push({key:`flight|${k}|${i}|${w.f.from}|${w.f.to}|${am}`, fireAt:f, at:w.s, kind:'flight', w, evs, k, r:{channel:'sound'}});
      });
    }
  }
  return out.sort((a,b)=>a.fireAt-b.fireAt);
}
function remTitle(x){
  if(x.kind==='event') return x.o.title;
  if(x.kind==='block') return '☑ '+x.o.title;
  if(x.kind==='due') return '⚑ Due: '+x.t.title;
  if(x.kind==='pdue') return '📁 Project due: '+x.p.name;
  return `✈ Flight ${code(x.w.f.from)} → ${code(x.w.f.to)}`;
}
function remWhen(x){
  const at=x.at, rel=Math.round((at-NOW())/60000);
  const relTxt = rel>1 ? `in ${fmtDur(rel)}` : rel>=-1 ? 'now' : `${fmtDur(-rel)} ago`;
  if(x.kind==='flight') return `departs ${hm(x.w.s)} (${relTxt})${x.w.f.note?' · '+x.w.f.note:''}${x.evs.length?' · around it: '+x.evs.map(o=>hm(o.s)+' '+o.title).join(', '):''}`;
  if(x.kind==='due'||x.kind==='pdue') return `${fmtShort(new Date(at))}${x.t&&x.t.dueTime?' '+x.t.dueTime:''} (${relTxt})`;
  return `${fmtShort(new Date(at))} ${hm(at)}${x.o.e?'–'+hm(x.o.e):''} (${relTxt})`;
}
function remItemId(x){ return x.o?x.o.id:x.t?x.t.id:x.p?x.p.id:x.kind==='flight'?'day:'+x.k:null; }
function muted(x){ // work items on leave/sick days are muted (listed in the next daily agenda)
  const it=x.o?x.o.item:x.t; if(!it || it.calendarId!=='work') return false;
  return !!offAt(x.at);
}
function inQuiet(){ const [qe,qs]=quietWin(); const n=new Date(NOW()), m=n.getHours()*60+n.getMinutes(); return qs>qe ? (m>=qs || m<qe) : (m>=qs && m<qe); }

/* stored notification list (bell) */
function notifs(){ return jget(remK('notifs'),[]); }
function addNotif(x){
  const l=notifs(); l.unshift({key:x.key, title:remTitle(x), when:remWhen(x), at:Date.now(), itemId:remItemId(x), kind:x.kind, occ:x.o?x.o.occ:null, ok:x.o?x.o.kind:null, read:false});
  jset(remK('notifs'), l.slice(0,50)); renderBell(); try{ _bc&&_bc.postMessage('notif'); }catch(e){}
}
function renderBell(){ const n=notifs().filter(x=>!x.read).length; const c=$('#bellCnt'); if(!c) return; c.hidden=!n; c.textContent=n>9?'9+':n; }
function openBell(){
  const l=notifs();
  const d=openDialog(`<div class="dhd"><h2>🔔 Notifications</h2>${l.length?'<button data-all>Mark all read</button>':''}<button class="ghost" data-close>✕</button></div>
    ${!('Notification' in window)?'':Notification.permission!=='granted'?`<div class="warnbox" style="margin-bottom:10px">Desktop notifications are off. <button data-perm>Turn on desktop notifications</button></div>`:''}
    ${l.map((n,i)=>`<div class="notif ${n.read?'':'unread'}" data-n="${i}"><div style="flex:1"><b>${esc(n.title)}</b><div class="w">${esc(n.when)}</div></div><div class="w">${esc(fmtWhen(new Date(n.at).toISOString()))}</div></div>`).join('')||'<div class="rempty">No notifications yet.</div>'}
    <div class="dacts"><button data-set>⚙ Notification settings</button><span class="sp"></span><button data-close>Close</button></div>`,{size:'mid'});
  const all=$('[data-all]',d); if(all) all.onclick=()=>{ jset(remK('notifs'),l.map(n=>({...n,read:true}))); renderBell(); closeDialog(d); };
  const pm=$('[data-perm]',d); if(pm) pm.onclick=()=>askDesktopPermission().then(()=>{ closeDialog(d); openBell(); });
  $('[data-set]',d).onclick=()=>{ closeDialog(d); openSettings('notify'); };
  $$('[data-n]',d).forEach(r=>r.onclick=()=>{ const n=l[+r.dataset.n]; n.read=true; jset(remK('notifs'),l); renderBell(); closeDialog(d); openNotifTarget(n); });
}
function openNotifTarget(n){
  if(!n.itemId) return;
  if(String(n.itemId).startsWith('day:')){ const k=n.itemId.slice(4); cursor=fromKey(k); render(); return openDrawer(k); }
  const it=findItem(n.itemId);
  if(it && it.type==='event') return openEvent(it.id,n.occ);
  if(it && it.type==='task') return openTaskEditor(it.id);
  if(proj(n.itemId)) return openProjectPage(n.itemId);
}
async function askDesktopPermission(){
  if(!('Notification' in window)){ alert('This browser does not support desktop notifications.'); return false; }
  const p=await Notification.requestPermission(); prefs.desktop=p==='granted'; savePrefs();
  if(p!=='granted') alert('Desktop notifications were not allowed. You can allow them in your browser’s site settings.');
  return p==='granted';
}
let _audio=null;
function chime(){
  try{ _audio=_audio||new (window.AudioContext||window.webkitAudioContext)(); const t=_audio.currentTime;
    [880,1320].forEach((f,i)=>{ const o=_audio.createOscillator(), g=_audio.createGain(); o.frequency.value=f; o.type='sine'; g.gain.setValueAtTime(0.0001,t+i*.18); g.gain.exponentialRampToValueAtTime(0.18,t+i*.18+.02); g.gain.exponentialRampToValueAtTime(0.0001,t+i*.18+.35); o.connect(g).connect(_audio.destination); o.start(t+i*.18); o.stop(t+i*.18+.4); });
  }catch(e){}
}

/* ---------- reminder popups (grouped) ---------- */
let _remQueue=[];
function showReminders(list){
  _remQueue.push(...list);
  const quiet=inQuiet();
  const wantDesk=list.some(x=>x.r.channel==='desktop'||x.r.channel==='sound') || prefs.desktop;
  const wantSound=list.some(x=>x.r.channel==='sound') || prefs.sound;
  if(!quiet && wantDesk && 'Notification' in window && Notification.permission==='granted'){
    try{
      const x=list[0];
      const n=new Notification(list.length>1?`${list.length} reminders`:remTitle(x), {body:list.length>1?list.map(remTitle).join('\n'):remWhen(x), tag:x.key, silent:!wantSound});
      n.onclick=()=>{ window.focus(); n.close(); if(list.length===1) openNotifTarget({itemId:remItemId(x), occ:x.o?x.o.occ:null}); };
    }catch(e){}
  }
  if(!quiet && wantSound) chime();
  drawRemToast();
}
function drawRemToast(){
  let t=$('#remToast');
  if(!_remQueue.length){ if(t) t.remove(); return; }
  if(!t){ t=document.createElement('div'); t.className='toast'; t.id='remToast'; t.setAttribute('role','alert'); $('#toasts').prepend(t); }
  const many=_remQueue.length>1;
  const row=(x,i)=>{ const it=x.o?x.o.item:x.t; const link=it&&it.onlineLink;
    return `<div class="${i?'sub':''}" style="--c:${esc(it?itemColor(it):'var(--accent)')}"><div class="th"><b>🔔 ${esc(remTitle(x))}</b></div><div class="ts">${esc(remWhen(x))}${x.o&&x.o.location?' · 📍 '+esc(x.o.location):''}</div>
      <div class="ta"><button data-ro="${i}">Open</button><select data-rs="${i}" aria-label="Snooze"><option value="">Snooze…</option>${prefs.snooze.map(s=>`<option value="${s}">${s==='tomorrow'?'Tomorrow 09:00':fmtDur(s)}</option>`).join('')}</select>
      ${(x.kind==='block'||x.kind==='due')&&editable?`<button data-rd="${i}">✓ Done</button>`:''}${link?`<button data-rj="${i}">Join</button>`:''}<button data-rx="${i}">Dismiss</button></div></div>`; };
  t.style.setProperty('--c', 'var(--today)');
  t.innerHTML = many ? `<div class="th"><b>${_remQueue.length} reminders</b><button class="ghost" data-rall>Dismiss all</button></div>`+_remQueue.map(row).join('') : row(_remQueue[0],0);
  const take=i=>{ const x=_remQueue[i]; _remQueue.splice(i,1); drawRemToast(); markRead(x.key); return x; };
  $$('[data-ro]',t).forEach(b=>b.onclick=()=>{ const x=take(+b.dataset.ro); openNotifTarget({itemId:remItemId(x), occ:x.o?x.o.occ:null}); });
  $$('[data-rx]',t).forEach(b=>b.onclick=()=>take(+b.dataset.rx));
  $$('[data-rj]',t).forEach(b=>b.onclick=()=>{ const x=take(+b.dataset.rj); window.open((x.o?x.o.item:x.t).onlineLink,'_blank','noopener'); });
  $$('[data-rd]',t).forEach(b=>b.onclick=()=>{ const x=take(+b.dataset.rd); if(x.kind==='block') toggleBlockDone(x.o.id,x.o.occ); else setTaskStatus(x.t,'done'); });
  $$('[data-rs]',t).forEach(s=>s.onchange=()=>{ if(!s.value) return; const x=take(+s.dataset.rs); snooze(x, s.value); });
  const all=$('[data-rall]',t); if(all) all.onclick=()=>{ _remQueue.forEach(x=>markRead(x.key)); _remQueue=[]; drawRemToast(); };
}
function syncToasts(){ /* other tab dismissed: nothing to do; each tab only shows what it fired */ }
function markRead(k){ const l=notifs(); l.forEach(n=>{ if(n.key===k) n.read=true; }); jset(remK('notifs'),l); renderBell(); }
function snooze(x, v){
  let at; if(v==='tomorrow'){ const d=addDays(TODAY(),1); d.setHours(9); at=fromDisp(d.getTime()); } else at=Date.now()+(+v)*60000;
  const s=jget(remK('snoozed'),[]); s.push({key:x.key, at, title:remTitle(x), when:remWhen(x), itemId:remItemId(x), occ:x.o?x.o.occ:null, kind:x.kind}); jset(remK('snoozed'),s);
  toast(`Snoozed until ${v==='tomorrow'?'tomorrow 09:00':hm(at)}`);
}

/* ---------- scheduler ---------- */
let _remTimer=null;
function startReminders(){
  clearInterval(_remTimer);
  const last=+safeGet(remK('lastcheck'))||0, now=Date.now();
  // catch-up: reminders that fell due while no tab was open (up to 7 days back)
  if(last && now-last>90000){
    const missed=dueReminders(Math.max(last, now-7*864e5), now-30000).filter(x=>!fired().has(x.key) && !muted(x));
    if(missed.length) setTimeout(()=>showAway(missed),800);
    missed.forEach(x=>markFired(x.key));
  }
  if(!last || now-last>90000) safeSet(remK('lastcheck'), now-30000);
  tickReminders(); _remTimer=setInterval(tickReminders,30000);
  setTimeout(dailyPrompts,1500);
}
function fired(){ return new Set(jget(remK('fired'),[]).map(x=>x[0])); }
function markFired(k){ const l=jget(remK('fired'),[]).filter(x=>x[1]>Date.now()-14*864e5); l.push([k,Date.now()]); jset(remK('fired'),l.slice(-3000)); }
function tickReminders(){
  if(!session || !data) return;
  if(!isLeader()) return;
  const now=Date.now(), last=+safeGet(remK('lastcheck'))||now-30000;
  const due=dueReminders(last, now+1000), f=fired(), fire=[], mutedNow=[];
  for(const x of due){ if(f.has(x.key)) continue; markFired(x.key); if(muted(x)){ mutedNow.push(x); continue; } fire.push(x); addNotif(x); }
  if(mutedNow.length){ const m=jget(remK('muted'),[]); m.push(...mutedNow.map(x=>({title:remTitle(x), when:remWhen(x), day:key(new Date(x.at))}))); jset(remK('muted'),m.slice(-50)); }
  // snoozed ones
  const sn=jget(remK('snoozed'),[]), back=sn.filter(s=>s.at<=now), keep=sn.filter(s=>s.at>now);
  if(back.length){ jset(remK('snoozed'),keep); for(const s of back){ const x=rebuildSnoozed(s); if(x){ fire.push(x); addNotif(x); } } }
  safeSet(remK('lastcheck'), now+1000);
  if(fire.length) showReminders(fire);
  dailyPrompts();
}
function rebuildSnoozed(s){
  const it=findItem(s.itemId);
  if(it && it.type==='event'){ const o=findOcc(it.id,s.occ,'event'); if(o) return {key:s.key+'|sn'+Date.now(), at:o.s, kind:'event', o, r:{channel:'popup'}}; }
  if(it && it.type==='task'){ if(s.kind==='block'){ const o=findOcc(it.id,s.occ,'block'); if(o) return {key:s.key+'|sn'+Date.now(), at:o.s, kind:'block', o, r:{channel:'popup'}}; } return {key:s.key+'|sn'+Date.now(), at:dueMs(it)||Date.now(), kind:'due', t:it, r:{channel:'popup'}}; }
  return null;
}
function showAway(list){
  const d=openDialog(`<div class="dhd"><h2>While you were away</h2><button class="ghost" data-close>✕</button></div>
    <p class="hint">${list.length} reminder${list.length>1?'s':''} fell due while the calendar was closed.</p>
    ${list.map((x,i)=>`<div class="notif" data-w="${i}"><div style="flex:1"><b>${esc(remTitle(x))}</b><div class="w">${esc(remWhen(x))}</div></div><div class="w">due ${esc(fmtShort(new Date(x.fireAt)))} ${hm(x.fireAt)}</div></div>`).join('')}
    <div class="dacts"><span class="sp"></span><button class="primary" data-close>OK</button></div>`,{size:'mid'});
  list.forEach(x=>{ const l=notifs(); l.unshift({key:x.key,title:remTitle(x),when:remWhen(x),at:x.fireAt,itemId:remItemId(x),kind:x.kind,occ:x.o?x.o.occ:null,read:true}); jset(remK('notifs'),l.slice(0,50)); });
  renderBell();
  $$('[data-w]',d).forEach(r=>r.onclick=()=>{ const x=list[+r.dataset.w]; closeDialog(d); openNotifTarget({itemId:remItemId(x),occ:x.o?x.o.occ:null}); });
}
/* daily agenda (first open after the digest time), weekly review and end-of-day prompts */
function dailyPrompts(){
  if(!session || !data || DETAIL || !isLeader()) return;
  const n=new Date(NOW()), m=n.getHours()*60+n.getMinutes(), tk=todayKey();
  if(prefs.digest && m>=(hmToMin(prefs.digestTime)??510) && safeGet(remK('digest'))!==tk && !_dlgStack.length){
    safeSet(remK('digest'),tk); openReports('daily',{date:tk, digest:true});
  }
  if(prefs.review && dow(n)===+prefs.reviewDay && m>=(hmToMin(prefs.reviewTime)??960) && safeGet(remK('review'))!==tk){
    safeSet(remK('review'),tk); toast('🗓 Time for your weekly review.',{actions:[['Open weekly review',()=>openReports('weekly')]],sticky:true});
  }
  if(prefs.eod && m>=(hmToMin(prefs.eodTime)??1080) && safeGet(remK('eod'))!==tk){
    safeSet(remK('eod'),tk); toast('🌇 End of the day — review what got done.',{actions:[['Open summary',()=>openReports('eod')]],sticky:true});
  }
}
