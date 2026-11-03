/* ============================================================
   Time grid interaction: create by drag, move, resize, drop tasks,
   keyboard navigation
   ============================================================ */
function minFromY(col, clientY, alt){
  const r=col.getBoundingClientRect(), hp=hourPx();
  const m=(clientY-r.top)/hp*60, step=alt?5:15;
  return clamp(Math.round(m/step)*step,0,24*60);
}
function colAt(x){
  const cols=$$('.tg-col'); for(const c of cols){ const r=c.getBoundingClientRect(); if(x>=r.left && x<r.right) return c; }
  return null;
}
function ghost(col, a, b, label){
  let g=$('.tg-ghost'); if(!g){ g=document.createElement('div'); g.className='tg-ghost'; }
  if(g.parentNode!==col) col.appendChild(g);
  const hp=hourPx(); g.style.top=(a/60*hp)+'px'; g.style.height=Math.max(10,(b-a)/60*hp)+'px'; g.textContent=label||(minToHM(a)+'–'+minToHM(b));
}
function clearGhost(){ const g=$('.tg-ghost'); if(g) g.remove(); }
function scrollGridToWork(){
  const b=$('#tgBody'); if(!b) return;
  if(b.dataset.scrolled) return;
  const ws=hmToMin(S().workStart)||540; const now=new Date(NOW()); const nowMin=now.getHours()*60+now.getMinutes();
  let target=ws-60; const visMin=b.clientHeight/hourPx()*60;
  if(gridDays.includes(todayKey()) && nowMin>target+visMin-60) target=nowMin-visMin+120;
  b.scrollTop=Math.max(0,target/60*hourPx());
}
let _gridScroll=null;
function bindGrid(){
  const body=$('#tgBody'); if(!body) return;
  if(_gridScroll!==null && _gridScroll.view===view+cursor.getTime()) body.scrollTop=_gridScroll.top; else scrollGridToWork();
  body.addEventListener('scroll',()=>{ _gridScroll={view:view+cursor.getTime(), top:body.scrollTop}; });
  // create by drag on empty column space
  $$('.tg-col',body).forEach(col=>{
    col.addEventListener('pointerdown',e=>{
      if(!editable || e.button!==0 || e.target.closest('.ev')) return;
      e.preventDefault();
      const k=col.dataset.col, a0=minFromY(col,e.clientY,e.altKey); let a=a0, b=a0+15, moved=false;
      col.setPointerCapture(e.pointerId);
      ghost(col,a,b);
      const mv=ev2=>{ const m=minFromY(col,ev2.clientY,ev2.altKey); if(Math.abs(m-a0)>=5) moved=true; a=Math.min(a0,m); b=Math.max(a0+ (m>=a0?0:0), m); if(b-a<15) b=a+15; ghost(col,a,b); };
      const up=()=>{ col.removeEventListener('pointermove',mv); col.removeEventListener('pointerup',up); col.removeEventListener('pointercancel',up);
        if(!moved){ a=a0; b=a0+(S().defaultEventMin||60); }
        ghost(col,a,b);
        quickCreateAt(k,a,b,col);
      };
      col.addEventListener('pointermove',mv); col.addEventListener('pointerup',up); col.addEventListener('pointercancel',up);
    });
    // HTML5 drop of a task from the tasks panel → new time block
    col.addEventListener('dragover',e=>{ if(!editable) return; const types=[...e.dataTransfer.types]; if(!types.includes('text/x-task')&&!types.includes('text/x-occ')) return; e.preventDefault(); const m=minFromY(col,e.clientY,e.altKey); ghost(col,m,m+(S().defaultBlockMin||60),'Drop to schedule at '+minToHM(m)); });
    col.addEventListener('dragleave',e=>{ if(!col.contains(e.relatedTarget)) clearGhost(); });
    col.addEventListener('drop',e=>{
      clearGhost(); if(!editable) return; e.preventDefault();
      const m=minFromY(col,e.clientY,e.altKey), k=col.dataset.col;
      const tid=e.dataTransfer.getData('text/x-task');
      if(tid){ scheduleTaskAt(task(tid), k, m); return; }
      const occ=e.dataTransfer.getData('text/x-occ');
      if(occ){ const [id,oc,kind]=occ.split('|'); const it=findItem(id); if(!it) return;
        if(kind==='event' && it.allDay){ // all-day → timed at drop time
          const tz=ACTIVE_TZ; it.allDay=false; it.tz=tz; it.start=k+'T'+minToHM(m); it.end=localAddMin(it.start,S().defaultEventMin||60); touch(it); changed(); renderAll(); }
      }
    });
  });
  // flights: click opens the day to edit times
  $$('.ev.flt',body).forEach(el=>{ el.onclick=()=>openDrawer(el.dataset.flk); el.onkeydown=e=>{ if(e.key==='Enter') openDrawer(el.dataset.flk); }; el.addEventListener('pointerdown',e=>e.stopPropagation()); });
  // items: click, move, resize
  $$('.ev[data-occ]',body).forEach(el=>{
    el.addEventListener('keydown',e=>{
      if(e.key==='Enter'){ e.preventDefault(); openOccPopover(el.dataset.occ, el.dataset.oc, el.dataset.ok, el); }
      if(e.key===' ' && el.dataset.ok==='block' && editable){ e.preventDefault(); toggleBlockDone(el.dataset.occ, el.dataset.oc); }
      if(e.key==='Delete' && editable){ e.preventDefault(); deleteOcc(el.dataset.occ, el.dataset.oc, el.dataset.ok); }
    });
    el.addEventListener('focus',()=>{ selItem={kind:el.dataset.ok,id:el.dataset.occ,occ:el.dataset.oc}; });
    const bd=$('[data-bdone]',el); if(bd){ bd.addEventListener('pointerdown',e=>e.stopPropagation()); bd.onclick=e=>{ e.stopPropagation(); const [id,oc]=bd.dataset.bdone.split('|'); toggleBlockDone(id,oc); }; }
    el.addEventListener('pointerdown',e=>{
      if(e.button!==0 || e.target.closest('[data-bdone]')) return;
      const resize=!!e.target.closest('[data-rz]');
      const id=el.dataset.occ, oc=el.dataset.oc, kind=el.dataset.ok;
      const o=findOcc(id,oc,kind); if(!o) return;
      const startCol=el.closest('.tg-col'), x0=e.clientX, y0=e.clientY;
      const d0=fromKey(startCol.dataset.col).getTime();
      const sMin=(o.s-d0)/60000, eMin=(o.e-d0)/60000, dur=eMin-sMin;
      const grab=minFromY(startCol,e.clientY,true)-sMin;
      let dragging=false, tCol=startCol, ns=sMin, ne=eMin;
      el.setPointerCapture(e.pointerId);
      const mv=ev2=>{
        if(!dragging && Math.hypot(ev2.clientX-x0, ev2.clientY-y0)<5) return;
        if(!editable || o.cancelled) return;
        if(!dragging){ dragging=true; el.classList.add('dragging'); }
        if(resize){ ne=Math.max(sMin+15, minFromY(startCol,ev2.clientY,ev2.altKey)); ns=sMin; ghost(startCol,ns,ne); }
        else { tCol=colAt(ev2.clientX)||tCol; const m=minFromY(tCol,ev2.clientY,true)-grab; const step=ev2.altKey?5:15; ns=clamp(Math.round(m/step)*step,0,24*60-15); ne=ns+dur; ghost(tCol,ns,ne); }
      };
      const up=()=>{
        el.removeEventListener('pointermove',mv); el.removeEventListener('pointerup',up); el.removeEventListener('pointercancel',up);
        el.classList.remove('dragging');
        if(!dragging){ clearGhost(); selItem={kind,id,occ:oc}; openOccPopover(id,oc,kind,el); return; }
        clearGhost();
        const nd0=fromKey(tCol.dataset.col).getTime();
        moveOcc(o, nd0+ns*60000, nd0+ne*60000);
      };
      el.addEventListener('pointermove',mv); el.addEventListener('pointerup',up); el.addEventListener('pointercancel',up);
    });
  });
  // keyboard navigation over slots
  body.addEventListener('keydown',e=>{
    if(e.target!==body) return;
    if(!gridFocus || !gridDays.includes(gridFocus.k)) gridFocus={k:gridDays.includes(todayKey())?todayKey():gridDays[0], m:hmToMin(S().workStart)||540};
    let {k,m}=gridFocus, hit=true;
    if(e.key==='ArrowUp') m=Math.max(0,m-30); else if(e.key==='ArrowDown') m=Math.min(23*60+30,m+30);
    else if(e.key==='ArrowLeft'){ const i=gridDays.indexOf(k); if(i>0) k=gridDays[i-1]; else { move(-1); return; } }
    else if(e.key==='ArrowRight'){ const i=gridDays.indexOf(k); if(i<gridDays.length-1) k=gridDays[i+1]; else { move(1); return; } }
    else if(e.key==='Enter'){ if(editable){ const col=$(`.tg-col[data-col="${k}"]`); quickCreateAt(k,m,m+(S().defaultEventMin||60),col); } }
    else hit=false;
    if(!hit) return;
    e.preventDefault(); e.stopPropagation();
    gridFocus={k,m}; drawGridFocus();
  });
  body.addEventListener('focus',()=>drawGridFocus());
  body.addEventListener('blur',()=>{ const f=$('.tg-focus'); if(f) f.remove(); });
}
function drawGridFocus(){
  if(!gridFocus) return; const col=$(`.tg-col[data-col="${gridFocus.k}"]`); if(!col) return;
  let f=$('.tg-focus'); if(!f){ f=document.createElement('div'); f.className='tg-focus'; }
  col.appendChild(f); f.style.top=(gridFocus.m/60*hourPx())+'px';
  const live=$('#gridLive')||Object.assign(document.createElement('div'),{id:'gridLive',className:'sr'}); live.setAttribute('aria-live','polite'); document.body.appendChild(live);
  live.textContent=fmtShort(fromKey(gridFocus.k))+' '+minToHM(gridFocus.m);
  const body=$('#tgBody'), y=gridFocus.m/60*hourPx(); if(y<body.scrollTop || y>body.scrollTop+body.clientHeight-40) body.scrollTop=y-80;
}
function findOcc(id, oc, kind){
  const it=findItem(id); if(!it) return null;
  if(kind==='block') return blockOccs(it,-1e14,1e14).find(o=>o.occ===oc)||null;
  if(!it.rrule){ return expandEvent(it, -1e14, 1e14)[0]; }
  const dk=oc; const occs=expandEvent(it, fromKey(dk).getTime()-40*864e5, fromKey(dk).getTime()+40*864e5);
  return occs.find(o=>o.occ===dk)||null;
}
/* move/resize an occurrence to new absolute times */
function moveOcc(o, s, e){
  const it=o.item;
  if(o.kind==='block'){ const b=o.block||it.blocks.find(x=>x.id===o.occ); const tz=b.tz||BROWSER_TZ; b.start=msToZoned(s,tz); b.end=msToZoned(e,tz);
    const dep=blockedBy(it).find(d=>d.blocks.some(x=>zonedToMs(x.end,x.tz)>s)); touch(it); changed(); renderAll();
    if(dep) toast(`⚠ “${it.title}” depends on “${dep.title}”, which has time scheduled after this block.`);
    return; }
  const tz=it.tz||BROWSER_TZ;
  if(it.rrule){
    it.exceptions[o.occ]=Object.assign({}, it.exceptions[o.occ]||{}, {start:msToZoned(s,tz), end:msToZoned(e,tz)});
    touch(it); changed(); renderAll(); toast('Moved this occurrence only. Open the event to change the whole series.'); return;
  }
  it.start=msToZoned(s,tz); it.end=msToZoned(e,tz); touch(it); changed(); renderAll();
}
function scheduleTaskAt(t, k, m){
  if(!t) return;
  const rem=remainingMin(t)-futurePlannedMin(t);
  const len = t.estimateMin && rem>0 ? Math.min(rem, (S().defaultBlockMin||60)*2) : (S().defaultBlockMin||60);
  const tz=ACTIVE_TZ, start=k+'T'+minToHM(m);
  const b={id:uid('b'), start, end:localAddMin(start, Math.max(15,Math.round(len/15)*15)), tz, done:false};
  const dep=blockedBy(t).find(d=>d.blocks.some(x=>zonedToMs(x.end,x.tz)>zonedToMs(b.start,tz)));
  t.blocks.push(b); if(t.status==='todo') t.status='todo'; touch(t); changed(); renderAll();
  toast(`Scheduled “${t.title}” ${fmtShort(fromKey(k))} ${minToHM(m)}–${b.end.slice(11)}`+(dep?` · ⚠ depends on “${dep.title}”`:''), {actions:[['Undo',undo]]});
}

/* quick create popover at a slot: title + Event / Task */
function quickCreateAt(k, a, b, col){
  closePopover();
  const r=col?col.getBoundingClientRect():{left:innerWidth/2-170,right:innerWidth/2+170,top:120};
  const hp=hourPx(); const y=(col?r.top:0)+a/60*hp;
  const p=document.createElement('div'); p.className='pop'; p.id='pop';
  p.innerHTML=`<form><div class="hint" style="margin-bottom:6px">${esc(fmtShort(fromKey(k)))} · ${minToHM(a)}–${minToHM(b)}</div>
    <input type="text" id="qcT" placeholder="Title" autocomplete="off" aria-label="Title">
    <div class="row" style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap">
      <select id="qcC" aria-label="Calendar">${data.calendars.map(c=>`<option value="${c.id}" ${c.id==='work'?'selected':''}>${esc(c.name)}</option>`).join('')}</select>
      <select id="qcP" aria-label="Project"></select></div>
    <div class="acts"><button class="primary" type="submit" data-k="event">📅 Event</button><button type="button" data-qt="task">☑ Task block</button><button type="button" data-more>More options…</button><button type="button" class="ghost" data-x>Cancel</button></div></form>`;
  document.body.appendChild(p); placePop(p, {left:r.right+6, right:r.left-6, top:y});
  const fillP=()=>{ const c=$('#qcC',p).value; $('#qcP',p).innerHTML=`<option value="">Inbox</option>`+projectsOf(c).filter(x=>x.status==='active').map(x=>`<option value="${x.id}">${esc(x.name)}</option>`).join(''); };
  fillP(); $('#qcC',p).onchange=fillP;
  const tz=ACTIVE_TZ, st=k+'T'+minToHM(a), en=b>=24*60?keyAdd(k,1)+'T00:00':k+'T'+minToHM(b);
  const make=(type, more)=>{
    const title=$('#qcT',p).value.trim(), calendarId=$('#qcC',p).value, projectId=$('#qcP',p).value||null;
    closePopover();
    if(type==='event'){
      const e=newEvent({title:title||'New event', calendarId, projectId, start:st, end:en, tz});
      if(more || !title) return openEventEditor(null, e);
      if(!saveNewItem(e)) return;
      toast(`Created “${e.title}”`, {actions:[['Undo',undo],['Edit',()=>openEventEditor(e.id)]]});
    } else {
      const t=newTask({title:title||'New task', calendarId, projectId, estimateMin:b-a});
      t.blocks.push({id:uid('b'), start:st, end:en, tz, done:false});
      if(more || !title) return openTaskEditor(null, t);
      if(!saveNewItem(t)) return;
      toast(`Created task “${t.title}”`, {actions:[['Undo',undo],['Edit',()=>openTaskEditor(t.id)]]});
    }
  };
  $('form',p).onsubmit=e=>{ e.preventDefault(); make('event'); };
  $('[data-qt]',p).onclick=()=>make('task');
  $('[data-more]',p).onclick=()=>make('event',true);
  $('[data-x]',p).onclick=()=>{ closePopover(); };
  p._onclose=clearGhost;
  setTimeout(()=>$('#qcT',p).focus(),0);
}
function placePop(p, at){
  const w=p.offsetWidth, h=p.offsetHeight;
  let left = at.left+w<innerWidth-8 ? at.left : Math.max(8, at.right-w);
  let top = clamp(at.top, 8, innerHeight-h-8);
  p.style.left=left+'px'; p.style.top=top+'px';
}
function closePopover(){ const p=$('#pop'); if(p){ p.remove(); if(p._onclose) p._onclose(); } }
document.addEventListener('mousedown',e=>{ const p=$('#pop'); if(p && !p.contains(e.target) && !e.target.closest('.ev,[data-occ]')) closePopover(); });
