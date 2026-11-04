/* ============================================================
   Editors: event, task, project; project page
   ============================================================ */
function calOptions(sel){ return data.calendars.map(c=>`<option value="${c.id}" ${c.id===sel?'selected':''} ${c.private&&privLocked?'disabled':''}>${esc(c.name)}${c.private?' 🔒':''}</option>`).join(''); }
function projOptions(calId, sel, exclude){
  const ps=data.projects.filter(p=>p.calendarId===calId && p.id!==exclude && (p.status!=='archived'||p.id===sel));
  return `<option value="">📥 Inbox (no project)</option>`+ps.map(p=>`<option value="${p.id}" ${p.id===sel?'selected':''}>${esc(p.icon||'')} ${esc(p.parentId?'  ↳ ':'')}${esc(p.name)}</option>`).join('');
}
function tzOptions(sel){ const l=[...new Set([...TZ_LIST, sel].filter(Boolean))]; return l.map(z=>`<option ${z===sel?'selected':''}>${z}</option>`).join(''); }
function bindCalProj(root){
  const c=$('[name=calendarId]',root), p=$('[name=projectId]',root);
  if(!c || !p) return;               // never break the dialog if a field is missing
  c.onchange=()=>{ p.innerHTML=projOptions(c.value, null); };
}
/* recurrence sub-form */
function rrFormHTML(rr, baseK){
  rr=rr||{}; const f=rr.freq||'';
  const preset = !f?'':(f==='weekly' && (rr.byDay||[]).slice().sort().join()==='0,1,2,3,4' && (rr.interval||1)===1 ? 'weekdays' : f);
  const bd=new Set(rr.byDay||[dowKey(baseK)]);
  return `<div class="rr" style="display:flex;flex-direction:column;gap:6px;width:100%">
    <div class="row"><select name="rrf"><option value="">Does not repeat</option><option value="daily" ${preset==='daily'?'selected':''}>Daily</option><option value="weekdays" ${preset==='weekdays'?'selected':''}>Every weekday (Mon–Fri)</option><option value="weekly" ${preset==='weekly'?'selected':''}>Weekly</option><option value="monthly" ${preset==='monthly'?'selected':''}>Monthly</option><option value="yearly" ${preset==='yearly'?'selected':''}>Yearly</option></select>
      <span data-rrx>every <input type="number" name="rri" min="1" max="99" value="${rr.interval||1}" style="width:60px"> <span data-rrunit></span></span></div>
    <div class="row daychips" data-rrw>${DOW3.map((d,i)=>`<button type="button" data-rd="${i}" class="${bd.has(i)?'on':''}">${d}</button>`).join('')}</div>
    <div class="row" data-rrm><label><input type="radio" name="rrmm" value="date" ${rr.monthMode!=='nth'?'checked':''}> on day ${fromKey(baseK).getDate()}</label>
      <label><input type="radio" name="rrmm" value="nth" ${rr.monthMode==='nth'?'checked':''}> on the <select name="rrn">${[[1,'1st'],[2,'2nd'],[3,'3rd'],[4,'4th'],[-1,'last']].map(([v,l])=>`<option value="${v}" ${(rr.nth||Math.min(4,Math.ceil(fromKey(baseK).getDate()/7)))==v?'selected':''}>${l}</option>`).join('')}</select> ${DAY_NAMES[dowKey(baseK)]}</label></div>
    <div class="row" data-rre>Ends <select name="rre"><option value="never">never</option><option value="count" ${rr.count?'selected':''}>after</option><option value="until" ${rr.until?'selected':''}>on</option></select>
      <input type="number" name="rrc" min="1" max="999" value="${rr.count||10}" style="width:70px"> <input type="date" name="rru" value="${rr.until||keyAdd(baseK,90)}"></div>
  </div>`;
}
function bindRR(root){
  const f=$('[name=rrf]',root);
  const upd=()=>{
    const v=f.value;
    $('[data-rrx]',root).style.display = v&&v!=='weekdays'?'':'none';
    $('[data-rrunit]',root).textContent = {daily:'day(s)',weekly:'week(s)',monthly:'month(s)',yearly:'year(s)'}[v]||'';
    $('[data-rrw]',root).style.display = v==='weekly'?'':'none';
    $('[data-rrm]',root).style.display = v==='monthly'?'':'none';
    $('[data-rre]',root).style.display = v?'':'none';
    const e=$('[name=rre]',root).value; $('[name=rrc]',root).style.display=e==='count'?'':'none'; $('[name=rru]',root).style.display=e==='until'?'':'none';
  };
  f.onchange=upd; $('[name=rre]',root).onchange=upd;
  $$('[data-rd]',root).forEach(b=>b.onclick=()=>b.classList.toggle('on'));
  upd();
}
function readRR(root){
  const v=$('[name=rrf]',root).value; if(!v) return null;
  const rr={freq:v==='weekdays'?'weekly':v, interval:v==='weekdays'?1:Math.max(1,+$('[name=rri]',root).value||1)};
  if(v==='weekdays') rr.byDay=[0,1,2,3,4];
  if(v==='weekly') rr.byDay=$$('[data-rd].on',root).map(b=>+b.dataset.rd);
  if(v==='monthly'){ rr.monthMode=$('[name=rrmm]:checked',root).value; if(rr.monthMode==='nth') rr.nth=+$('[name=rrn]',root).value; }
  const e=$('[name=rre]',root).value; if(e==='count') rr.count=Math.max(1,+$('[name=rrc]',root).value||1); if(e==='until') rr.until=$('[name=rru]',root).value||null;
  return rr;
}
/* reminders sub-form */
function remFormHTML(list, anchors){
  const row=(r,i)=>`<div class="li" data-ri="${i}">${anchors?`<select data-r="anchor">${anchors.map(([v,l])=>`<option value="${v}" ${r.anchor===v?'selected':''}>${l}</option>`).join('')}</select>`:''}
    <select data-r="offsetMin">${OFFSETS.map(([v,l])=>`<option value="${v}" ${+r.offsetMin===v?'selected':''}>${l}</option>`).join('')}${OFFSETS.some(o=>o[0]===+r.offsetMin)?'':`<option value="${r.offsetMin}" selected>${fmtDur(r.offsetMin)} before</option>`}<option value="custom">Custom…</option></select>
    <select data-r="channel">${[['popup','Popup'],['desktop','Popup + desktop'],['sound','Popup + desktop + sound']].map(([v,l])=>`<option value="${v}" ${r.channel===v?'selected':''}>${l}</option>`).join('')}</select>
    <button type="button" class="ghost" data-rdel="${i}" aria-label="Remove reminder">✕</button></div>`;
  return `<div class="list-edit" data-rems>${list.map(row).join('')}<button type="button" data-radd style="align-self:flex-start">＋ Reminder</button></div>`;
}
function bindRems(root, list, anchors, rerender){
  $$('[data-ri]',root).forEach(li=>{ const i=+li.dataset.ri; $$('[data-r]',li).forEach(s=>s.onchange=async()=>{
    if(s.dataset.r==='offsetMin' && s.value==='custom'){ const v=await promptDlg('Custom reminder','Minutes before (e.g. 45, or 180 for 3 hours):','45',{type:'number'}); if(v!=null) list[i].offsetMin=Math.max(0,+v||0); rerender(); return; }
    list[i][s.dataset.r]= s.dataset.r==='offsetMin'?+s.value:s.value; }); });
  $$('[data-rdel]',root).forEach(b=>b.onclick=()=>{ list.splice(+b.dataset.rdel,1); rerender(); });
  $('[data-radd]',root).onclick=()=>{ list.push(anchors?{anchor:anchors[0][0],offsetMin:15,channel:'popup'}:{offsetMin:15,channel:'popup'}); rerender(); };
}
function splitList(s){ return String(s||'').split(',').map(x=>x.trim()).filter(Boolean); }

/* ---------------- EVENT EDITOR ---------------- */
function openEventEditor(id, draft, occKey){
  const orig = id ? ev(id) : null;
  if(id && !orig) return;
  const e = clone(orig || draft || newEvent());
  const isNew = !orig;
  const ro = !editable;
  // when opened from one occurrence of a series, show that occurrence's date/time
  let occStart=null, occEnd=null;
  if(orig && orig.rrule && occKey){ const o=findOcc(id,occKey,'event'); if(o){ const ex=orig.exceptions[occKey]||{}; occStart=ex.start||(occKey+(orig.allDay?'':'T'+orig.start.slice(11,16))); occEnd=ex.end||(orig.allDay?keyAdd(occKey,Math.round((uparse(orig.end.slice(0,10))-uparse(orig.start.slice(0,10)))/864e5)):localAddMin(occStart,localDiffMin(orig.start,orig.end))); } }
  const sv=occStart||e.start, en=occEnd||e.end||e.start;
  // the form shows times in the active zone; they are saved back in the item's own zone
  const storeTz = (!e.allDay && e.tz) ? e.tz : ACTIVE_TZ;
  const svD = e.allDay ? sv : tzConvert(sv, storeTz, ACTIVE_TZ), enD = e.allDay ? en : tzConvert(en, storeTz, ACTIVE_TZ);
  const d=openDialog(`<div class="dhd"><h2>${isNew?'New event':ro?'Event':'Edit event'}</h2>${!isNew&&orig.rrule?'<span class="tag">🔁 repeating</span>':''}<button class="ghost" data-close aria-label="Close">✕</button></div>
  <form class="frm" autocomplete="off">
    <input class="titlein redtitle full" name="title" value="${esc(e.title)}" placeholder="Event title" ${ro?'disabled':''} required aria-label="Title">
    <label>Calendar</label><div class="row"><select name="calendarId" ${ro?'disabled':''}>${calOptions(e.calendarId)}</select><select name="projectId" ${ro?'disabled':''} aria-label="Project">${projOptions(e.calendarId,e.projectId)}</select></div>
    <label>Type</label><div class="row"><select name="category" ${ro?'disabled':''}>${Object.entries(CATEGORIES).map(([k,v])=>`<option value="${k}" ${e.category===k?'selected':''}>${v}</option>`).join('')}</select>
      <label class="hint"><input type="checkbox" name="allDay" ${e.allDay?'checked':''} ${ro?'disabled':''}> All day</label>
      <select name="showAs" ${ro?'disabled':''} aria-label="Show as">${Object.entries(SHOW_AS).map(([k,v])=>`<option value="${k}" ${e.showAs===k?'selected':''}>${v}</option>`).join('')}</select>
      <select name="status" ${ro?'disabled':''} aria-label="Status"><option value="confirmed">Confirmed</option><option value="cancelled" ${e.status==='cancelled'||(occKey&&e.exceptions[occKey]&&e.exceptions[occKey].status==='cancelled')?'selected':''}>Cancelled</option></select></div>
    <label>Starts</label><div class="row"><input type="date" name="sd" value="${svD.slice(0,10)}" ${ro?'disabled':''} required><input type="time" name="st" value="${svD.slice(11,16)||S().workStart}" data-timed ${ro?'disabled':''} step="300"></div>
    <label>Ends</label><div class="row"><input type="date" name="ed" value="${enD.slice(0,10)}" ${ro?'disabled':''}><input type="time" name="et" value="${enD.slice(11,16)||''}" data-timed ${ro?'disabled':''} step="300">
      <input type="hidden" name="tz" value="${ACTIVE_TZ}"><span class="hint" data-timed title="Change it with the 🌍 menu at the top">🌍 ${esc(tzShort(ACTIVE_TZ))} time${storeTz!==ACTIVE_TZ&&!isNew?` · entered in ${esc(tzShort(storeTz))} time (${esc(sv.slice(11,16))})`:''}</span></div>
    <div class="full" id="evConf"></div>
    <label>Repeat</label>${rrFormHTML(e.rrule, e.start.slice(0,10))}
    <label>Location</label><input type="text" name="location" value="${esc(e.location)}" placeholder="Room, address or city" ${ro?'disabled':''}>
    <label>Online link</label><input type="text" name="onlineLink" value="${esc(e.onlineLink)}" placeholder="https://…" ${ro?'disabled':''}>
    <label>Attendees</label><input type="text" name="attendees" value="${esc(e.attendees.join(', '))}" placeholder="Names, separated by commas" ${ro?'disabled':''}>
    <div class="sec">Reminders</div><div class="full" id="evRem"></div>
    <div class="sec">Notes &amp; links</div>
    ${mdEditorHTML('notes', e.notes, {ro, rows:7, placeholder:'Agenda, meeting notes… Markdown: **bold**, *italic*, # heading, - list, [link](https://…)'})}
    <label>Links</label><div class="list-edit" id="evLinks"></div>
    <label>Linked tasks</label><div class="list-edit" id="evTasks"></div>
    <label>Tags</label><input type="text" name="tags" value="${esc(e.tags.join(', '))}" placeholder="e.g. council, budget" ${ro?'disabled':''}>
    <label>Privacy</label><label class="hint"><input type="checkbox" name="private" ${e.private?'checked':''} ${ro?'disabled':''}> Private — hide from viewers even in the Work calendar (needs the Personal passphrase)</label>
  </form>
  <div class="dacts">
    ${!isNew&&editable?'<button data-a="del" class="danger">Delete</button><button data-a="dup">Duplicate</button><button data-a="conv">Convert to task</button><button data-a="tpl">Save as template</button><button data-a="ics">⬇ .ics</button>':''}
    <span class="sp"></span><button data-close>${ro?'Close':'Cancel'}</button>${editable?'<button class="primary" data-a="save">Save</button>':''}
  </div>`, {size:'mid', focus:'[name=title]'});
  const F=$('form',d);
  bindCalProj(F); bindRR(F);
  const allDay=$('[name=allDay]',F);
  const syncAD=()=>$$('[data-timed]',F).forEach(x=>x.style.display=allDay.checked?'none':''); allDay.onchange=()=>{ syncAD(); checkConf(); }; syncAD();
  // keep end after start when the start moves
  let lastStart = svD.slice(0,16);
  const shiftEnd=()=>{
    const ns=$('[name=sd]',F).value+'T'+($('[name=st]',F).value||'00:00'); const oe=$('[name=ed]',F).value+'T'+($('[name=et]',F).value||'00:00');
    if(parseLocal(lastStart) && parseLocal(ns) && parseLocal(oe)){ const dur=localDiffMin(lastStart,oe); const ne=localAddMin(ns, Math.max(allDay.checked?0:15,dur)); $('[name=ed]',F).value=ne.slice(0,10); $('[name=et]',F).value=ne.slice(11,16); }
    lastStart=ns; checkConf();
  };
  $('[name=sd]',F).onchange=shiftEnd; $('[name=st]',F).onchange=shiftEnd; $('[name=ed]',F).onchange=checkConf; $('[name=et]',F).onchange=checkConf;
  function checkConf(){
    if(allDay.checked){ $('#evConf',d).innerHTML=''; return; }
    const tz=$('[name=tz]',F).value, s=zonedToMs($('[name=sd]',F).value+'T'+$('[name=st]',F).value,tz), en2=zonedToMs($('[name=ed]',F).value+'T'+$('[name=et]',F).value,tz);
    if(!(en2>s)){ $('#evConf',d).innerHTML='<div class="warnbox">The end is before the start.</div>'; return; }
    const occ=occurrences(s-864e5,en2+864e5,{all:true,noCache:true}).filter(o=>!o.allDay && o.id!==e.id && !o.cancelled && !o.done && o.e>s && o.s<en2 && o.kind!=='busy');
    const k=key(new Date(s)); const fl=flightWindows(k).filter(w=>w.s<en2 && s<w.e);
    const msgs=[...occ.map(o=>`Overlaps “${esc(o.title)}” ${hm(o.s)}–${hm(o.e)}`), ...fl.map(w=>`During flight ${esc(code(w.f.from))}→${esc(code(w.f.to))}`), ...($('[name=calendarId]',F).value==='work'?offOverlap(k,s,en2).map(x=>'During '+esc(offLabel(x))):[])];
    $('#evConf',d).innerHTML = msgs.length?`<div class="warnbox">⚠ ${msgs.join('<br>')}</div>`:'';
  }
  checkConf();
  const rems=clone(e.reminders);
  const drawRems=()=>{ $('#evRem',d).innerHTML=ro?(rems.map(r=>`<div class="hint">🔔 ${esc((OFFSETS.find(o=>o[0]===+r.offsetMin)||[0,fmtDur(r.offsetMin)+' before'])[1])}</div>`).join('')||'<span class="hint">None</span>'):remFormHTML(rems); if(!ro) bindRems($('#evRem',d),rems,null,drawRems); };
  drawRems();
  bindMdEditor(d);
  const links=clone(e.links);
  const drawLinks=()=>{ $('#evLinks',d).innerHTML=links.map((l,i)=>ro?`<a href="${esc(l.url)}" target="_blank" rel="noopener">${esc(l.label||l.url)}</a>`:`<div class="li"><input type="text" data-l="label" data-i="${i}" value="${esc(l.label)}" placeholder="Label" style="max-width:150px"><input type="text" data-l="url" data-i="${i}" value="${esc(l.url)}" placeholder="https://…"><button type="button" class="ghost" data-ldel="${i}">✕</button></div>`).join('')+(ro?'':'<button type="button" data-ladd style="align-self:flex-start">＋ Link</button>');
    if(ro) return; $$('[data-l]',d).forEach(x=>x.oninput=()=>links[+x.dataset.i][x.dataset.l]=x.value); $$('[data-ldel]',d).forEach(b=>b.onclick=()=>{ links.splice(+b.dataset.ldel,1); drawLinks(); }); $('[data-ladd]',d).onclick=()=>{ links.push({label:'',url:''}); drawLinks(); }; };
  drawLinks();
  const linked=[...e.linkedTaskIds];
  const drawLinked=()=>{
    const open=data.tasks.filter(t=>isOpen(t)&&!linked.includes(t.id));
    $('#evTasks',d).innerHTML=linked.map(tid=>{ const t=task(tid); return t?`<div class="li"><span class="tag" style="cursor:pointer" data-topen="${t.id}">${isOpen(t)?'☐':'✓'} ${esc(t.title)}</span>${ro?'':`<button type="button" class="ghost" data-tdel="${t.id}">✕</button>`}</div>`:''; }).join('')
      +(ro?'':`<div class="li"><select data-tadd><option value="">Link a task…</option>${open.map(t=>`<option value="${t.id}">${esc(t.title)}</option>`).join('')}</select><button type="button" data-tnew>＋ New follow-up task</button></div>`);
    $$('[data-topen]',d).forEach(x=>x.onclick=()=>openTaskEditor(x.dataset.topen));
    if(ro) return;
    $$('[data-tdel]',d).forEach(b=>b.onclick=()=>{ linked.splice(linked.indexOf(b.dataset.tdel),1); drawLinked(); });
    $('[data-tadd]',d).onchange=ev2=>{ if(ev2.target.value){ linked.push(ev2.target.value); drawLinked(); } };
    $('[data-tnew]',d).onclick=()=>{ const title=$('[name=title]',F).value||'event'; const t=newTask({title:'Follow up: '+title, calendarId:$('[name=calendarId]',F).value, projectId:$('[name=projectId]',F).value||null, dueDate:keyAdd($('[name=sd]',F).value,2)});
      if(isPrivate(t)&&!ensurePrivReadySync()) return; touch(t); data.tasks.push(t); linked.push(t.id); changed(); drawLinked(); toast('Follow-up task created',{actions:[['Open',()=>openTaskEditor(t.id)]]}); };
  };
  drawLinked();

  function collect(){
    const v=n=>$(`[name=${n}]`,F).value;
    const o=clone(e);
    o.title=v('title').trim()||'Untitled'; o.calendarId=v('calendarId'); o.projectId=v('projectId')||null; o.category=v('category');
    o.allDay=allDay.checked; o.showAs=v('showAs'); o.status=v('status');
    if(o.allDay){ o.start=v('sd'); o.end=v('ed')&&v('ed')>=v('sd')?v('ed'):v('sd'); delete o.tz; }
    else { let s1=v('sd')+'T'+(v('st')||'09:00'), e1=(v('ed')||v('sd'))+'T'+(v('et')||v('st')||'10:00'); if(localDiffMin(s1,e1)<=0) e1=localAddMin(s1,S().defaultEventMin||60);
      o.tz=storeTz; o.start=tzConvert(s1,ACTIVE_TZ,storeTz); o.end=tzConvert(e1,ACTIVE_TZ,storeTz); }
    o.rrule=readRR(F); o.location=v('location').trim(); o.onlineLink=v('onlineLink').trim(); o.attendees=splitList(v('attendees')); o.notes=v('notes');
    o.tags=splitList(v('tags')); o.private=$('[name=private]',F).checked; o.reminders=rems; o.links=links.filter(l=>l.url); o.linkedTaskIds=linked;
    return o;
  }
  async function save(){
    if(!F.reportValidity()) return;
    const o=collect();
    if(isPrivate(o) && !ensurePrivReadySync()) return;
    if(isNew){ touch(o); data.events.push(o); changed(); closeDialog(d); renderAll(); toast(`Saved “${o.title}”`,{actions:[['Undo',undo]]}); return; }
    let scope='all';
    if(orig.rrule && occKey){
      scope=await confirmDlg({title:'Change repeating event', html:'Apply your changes to:'},[['Only this one','this'],['This and following','following'],['All of them','all','primary'],['Cancel',null]]);
      if(!scope) return;
    }
    const tgt=ev(id);
    if(scope==='this'){
      const ex=Object.assign({},tgt.exceptions[occKey]||{});
      if(o.start!==sv || o.end!==en) { ex.start=o.start; ex.end=o.end; }
      if(o.title!==tgt.title) ex.title=o.title; if(o.location!==tgt.location) ex.location=o.location;
      if(o.status==='cancelled' && tgt.status!=='cancelled') ex.status='cancelled'; else if(o.status!=='cancelled') delete ex.status;
      tgt.exceptions[occKey]=ex; touch(tgt);
    } else if(scope==='following' && occKey>tgt.start.slice(0,10)){
      const nu=clone(o); nu.id=uid('e'); delete nu.uid; nu.exceptions={}; for(const [k,x] of Object.entries(tgt.exceptions)) if(k>occKey) nu.exceptions[k]=x;
      tgt.rrule=Object.assign({},tgt.rrule,{until:keyAdd(occKey,-1),count:null}); for(const k of Object.keys(tgt.exceptions)) if(k>=occKey) delete tgt.exceptions[k];
      touch(tgt); touch(nu); nu.created=nu.updated; data.events.push(nu);
    } else {
      if(occKey && orig.rrule){ // shift the series by the change made on this occurrence
        const dd=Math.round((uparse(o.start.slice(0,10))-uparse(sv.slice(0,10)))/864e5);
        const span=o.allDay?Math.round((uparse(o.end.slice(0,10))-uparse(o.start.slice(0,10)))/864e5):localDiffMin(o.start,o.end);
        const bs=keyAdd(orig.start.slice(0,10),dd);
        o.start=o.allDay?bs:bs+'T'+o.start.slice(11,16); o.end=o.allDay?keyAdd(bs,span):localAddMin(o.start,span);
        delete (o.exceptions[occKey]||{}).start; delete (o.exceptions[occKey]||{}).end;
      }
      Object.assign(tgt,o); touch(tgt);
    }
    changed(); closeDialog(d); renderAll(); toast('Saved',{actions:[['Undo',undo]]});
  }
  const a=(n,fn)=>{ const b=$(`[data-a="${n}"]`,d); if(b) b.onclick=fn; };
  a('save',save); F.onsubmit=ev2=>{ ev2.preventDefault(); save(); };
  a('del',()=>{ closeDialog(d); deleteOcc(id, occKey||orig.start.slice(0,10), 'event'); });
  a('dup',()=>{ closeDialog(d); const c=duplicateItem(orig); openEventEditor(c.id); });
  a('conv',()=>{ closeDialog(d); const t=convertItem(orig); openTaskEditor(t.id); });
  a('tpl',()=>saveAsTemplate([collect()]));
  a('ics',()=>exportIcs({items:[orig], name:orig.title}));
  return d;
}

/* ---------------- TASK EDITOR ---------------- */
function openTaskEditor(id, draft, opts={}){
  const orig = id ? task(id) : null;
  if(id && !orig) return;
  const t = clone(orig || draft || newTask());
  const dueD = t.dueDate&&t.dueTime ? tzConvert(t.dueDate+'T'+t.dueTime, t.tz||S().homeTz, ACTIVE_TZ) : null; // due shown in the active zone
  const isNew=!orig, ro=!editable;
  const d=openDialog(`<div class="dhd"><h2>${isNew?'New task':ro?'Task':'Edit task'}</h2>${!isNew&&blockedBy(orig).length?'<span class="tag">🔒 blocked</span>':''}<button class="ghost" data-close aria-label="Close">✕</button></div>
  <form class="frm" autocomplete="off">
    <input class="titlein redtitle full" name="title" value="${esc(t.title)}" placeholder="What needs doing?" ${ro?'disabled':''} required aria-label="Title">
    <label>Calendar</label><div class="row"><select name="calendarId" ${ro?'disabled':''}>${calOptions(t.calendarId)}</select><select name="projectId" ${ro?'disabled':''} aria-label="Project">${projOptions(t.calendarId,t.projectId)}</select></div>
    <label>Status</label><div class="row"><select name="status" ${ro?'disabled':''}>${Object.entries(TASK_STATUS).map(([k,v])=>`<option value="${k}" ${t.status===k?'selected':''}>${v}</option>`).join('')}</select>
      <select name="priority" ${ro?'disabled':''} aria-label="Priority">${[[1,'P1 urgent'],[2,'P2 high'],[3,'P3 normal'],[4,'P4 low']].map(([v,l])=>`<option value="${v}" ${+t.priority===v?'selected':''}>${l}</option>`).join('')}</select></div>
    <label>Due</label><div class="row"><input type="date" name="dueDate" value="${dueD?dueD.slice(0,10):(t.dueDate||'')}" ${ro?'disabled':''}><input type="time" name="dueTime" value="${dueD?dueD.slice(11,16):''}" ${ro?'disabled':''} step="300"><span class="hint" id="tkDue"></span></div>
    <label>Estimate</label><div class="row"><input type="number" name="estH" min="0" max="999" value="${t.estimateMin?Math.floor(t.estimateMin/60):''}" style="width:70px" ${ro?'disabled':''} aria-label="Hours"> h <input type="number" name="estM" min="0" max="59" step="5" value="${t.estimateMin?t.estimateMin%60:''}" style="width:70px" ${ro?'disabled':''} aria-label="Minutes"> min <span class="hint" id="tkPlan"></span></div>
    <div class="sec">Time blocks on the calendar</div>
    <div class="full list-edit" id="tkBlocks"></div>
    <div class="full" id="tkSlots"></div>
    <div class="sec">Checklist</div><div class="full list-edit" id="tkCheck"></div>
    <label>Depends on</label><div class="list-edit" id="tkDeps"></div>
    <label>Repeat</label>${rrFormHTML(t.rrule, t.dueDate||todayKey())}
    <div class="full hint">Repeating tasks need a due date; when you complete one, the next one is created.</div>
    <div class="sec">Reminders</div><div class="full" id="tkRem"></div>
    <div class="sec">Notes</div>${mdEditorHTML('notes', t.notes, {ro, rows:5, placeholder:'Details, links, steps… Markdown: **bold**, *italic*, - list, [link](https://…)'})}
    <label>Tags</label><input type="text" name="tags" value="${esc(t.tags.join(', '))}" placeholder="e.g. drafting" ${ro?'disabled':''}>
    <label>Privacy</label><label class="hint"><input type="checkbox" name="private" ${t.private?'checked':''} ${ro?'disabled':''}> Private — hide from viewers (needs the Personal passphrase)</label>
  </form>
  <div class="dacts">
    ${!isNew&&editable?'<button data-a="del" class="danger">Delete</button><button data-a="dup">Duplicate</button><button data-a="conv">Convert to event</button><button data-a="tpl">Save as template</button>':''}
    ${!isNew&&editable&&isOpen(orig)?'<button data-a="done">✓ Mark done</button>':''}
    <span class="sp"></span><button data-close>${ro?'Close':'Cancel'}</button>${editable?'<button class="primary" data-a="save">Save</button>':''}
  </div>`, {size:'mid', focus:'[name=title]'});
  const F=$('form',d);
  bindCalProj(F); bindRR(F); bindMdEditor(d);
  const v=n=>$(`[name=${n}]`,F).value;
  const est=()=>((+v('estH')||0)*60+(+v('estM')||0))||null;
  // blocks
  const blocks=clone(t.blocks).map(b=>({...b, start:tzConvert(b.start,b.tz||ACTIVE_TZ,ACTIVE_TZ), end:tzConvert(b.end,b.tz||ACTIVE_TZ,ACTIVE_TZ), tz:ACTIVE_TZ})); // shown and saved in the active zone
  const drawPlan=()=>{
    const pl=blocks.reduce((a,b)=>a+Math.max(0,localDiffMin(b.start,b.end)),0), e2=est();
    $('#tkPlan',d).innerHTML = e2?`${fmtH(pl)} scheduled of ${fmtH(e2)}${pl<e2?` · <b style="color:var(--leave)">${fmtH(e2-pl)} still to schedule</b>`:' ✓'}`:(pl?fmtH(pl)+' scheduled':'');
    $('#tkDue',d).textContent = v('dueDate') && v('dueDate')<todayKey() && v('status')!=='done' ? 'overdue' : '';
  };
  const drawBlocks=()=>{
    $('#tkBlocks',d).innerHTML = blocks.map((b,i)=>{ const past=!b.done && zonedToMs(b.end,b.tz)<NOW();
      return `<div class="li" data-bi="${i}">${ro?`<span>${esc(fmtShort(fromKey(b.start.slice(0,10))))} ${b.start.slice(11)}–${b.end.slice(11)} ${b.done?'✓':''}</span>`:
      `<input type="date" data-b="d" value="${b.start.slice(0,10)}"><input type="time" data-b="s" value="${b.start.slice(11,16)}" step="300"> – <input type="time" data-b="e" value="${b.end.slice(11,16)}" step="300">
       <span class="hint">${esc(tzShort(ACTIVE_TZ))}</span>
       <label class="hint"><input type="checkbox" data-b="done" ${b.done?'checked':''}> done</label>${b.done?`<input type="number" data-b="act" value="${b.actualMin!=null?b.actualMin:localDiffMin(b.start,b.end)}" style="width:70px" title="Minutes spent" aria-label="Minutes spent"> min`:''}
       ${past?'<span class="tag" style="color:var(--missed)">missed</span><button type="button" data-bres="'+i+'">↻ Next free slot</button>':''}
       <button type="button" class="ghost" data-bdel="${i}" aria-label="Remove block">✕</button>`}</div>`; }).join('')
      + (ro?'':'<div class="li"><button type="button" data-badd>＋ Add a time block</button><button type="button" data-bfind>🔎 Find a slot</button><button type="button" data-bauto>⚡ Fill the estimate</button></div>')
      + (!blocks.length&&ro?'<span class="hint">Not scheduled.</span>':'');
    drawPlan(); if(ro) return;
    $$('[data-bi]',d).forEach(li=>{ const b=blocks[+li.dataset.bi];
      $$('[data-b]',li).forEach(x=>x.onchange=()=>{
        const f=x.dataset.b, dur=localDiffMin(b.start,b.end);
        if(f==='d'){ b.start=x.value+'T'+b.start.slice(11,16); b.end=localAddMin(b.start,dur); }
        if(f==='s'){ b.start=b.start.slice(0,10)+'T'+x.value; b.end=localAddMin(b.start,dur); }
        if(f==='e'){ b.end=b.start.slice(0,10)+'T'+x.value; if(localDiffMin(b.start,b.end)<=0) b.end=localAddMin(b.start,15); }
        if(f==='tz') b.tz=x.value; if(f==='done'){ b.done=x.checked; if(!b.done) delete b.actualMin; } if(f==='act') b.actualMin=Math.max(0,+x.value||0);
        drawBlocks();
      }); });
    $$('[data-bdel]',d).forEach(x=>x.onclick=()=>{ blocks.splice(+x.dataset.bdel,1); drawBlocks(); });
    $$('[data-bres]',d).forEach(x=>x.onclick=()=>{ const b=blocks[+x.dataset.bres]; const len=localDiffMin(b.start,b.end); const sl=findSlots({len, calendarId:v('calendarId'), dueDate:null, exclude:{id:t.id}, n:1})[0]; if(!sl) return toast('No free slot found in the next 14 days.'); Object.assign(b,slotToBlock(sl)); drawBlocks(); });
    $('[data-badd]',d).onclick=()=>{ const n=new Date(NOW()); n.setMinutes(0,0,0); n.setHours(n.getHours()+1); const k=key(n), tz=ACTIVE_TZ; const st=k+'T'+hm(n.getTime()); blocks.push({id:uid('b'),start:st,end:localAddMin(st,S().defaultBlockMin||60),tz,done:false}); drawBlocks(); };
    $('[data-bfind]',d).onclick=()=>showSlots();
    $('[data-bauto]',d).onclick=()=>{ const e2=est(); if(!e2) return toast('Set an estimate first.'); let need=e2-blocks.reduce((a,b)=>a+(b.done?(b.actualMin??localDiffMin(b.start,b.end)):Math.max(0,localDiffMin(b.start,b.end))),0); if(need<=0) return toast('The estimate is already fully scheduled.');
      const extra=[...blocks]; let guard=0; while(need>0 && guard++<20){ const len=Math.min(need,120); const sl=findSlots({len:Math.max(15,len), calendarId:v('calendarId'), dueDate:v('dueDate')||null, dueTime:v('dueTime')||null, exclude:{id:t.id, blocks:extra}, n:1})[0]; if(!sl) break; const nb={id:uid('b'),...slotToBlock(sl),done:false}; blocks.push(nb); extra.push(nb); need-=len; }
      drawBlocks(); if(need>0) toast('Not enough free time before the due date for the whole estimate.'); };
  };
  function showSlots(){
    const e2=est(), pl=blocks.reduce((a,b)=>a+localDiffMin(b.start,b.end),0);
    const len=Math.max(15, Math.min(e2&&e2>pl?e2-pl:(S().defaultBlockMin||60), 120));
    const sl=findSlots({len, calendarId:v('calendarId'), dueDate:v('dueDate')||null, dueTime:v('dueTime')||null, exclude:{id:t.id, blocks}, n:3});
    $('#tkSlots',d).innerHTML = sl.length ? `<div class="hint" style="margin-bottom:4px">Free slots of ${fmtDur(len)}${v('dueDate')?' before the due date':''}:</div>`+sl.map((s,i)=>`<div class="slot"><span>${esc(fmtShort(new Date(s.s)))} · ${hm(s.s)}–${hm(s.e)}</span><button type="button" data-slot="${i}">Use</button></div>`).join('')
      : '<div class="warnbox">No free slot found'+(v('dueDate')?' before the due date':' in the next 14 days')+'.</div>';
    $$('[data-slot]',d).forEach(b=>b.onclick=()=>{ blocks.push({id:uid('b'),...slotToBlock(sl[+b.dataset.slot]),done:false}); $('#tkSlots',d).innerHTML=''; drawBlocks(); });
  }
  drawBlocks();
  ['estH','estM','dueDate','status'].forEach(n=>$(`[name=${n}]`,F).addEventListener('input',drawPlan));
  // checklist
  const cl=clone(t.checklist);
  const drawCheck=()=>{
    $('#tkCheck',d).innerHTML=cl.map((c,i)=>`<div class="li"><input type="checkbox" data-ci="${i}" ${c.done?'checked':''} ${ro?'disabled':''} aria-label="Done"><input type="text" data-ct="${i}" value="${esc(c.text)}" ${ro?'disabled':''} aria-label="Checklist item">${ro?'':`<button type="button" class="ghost" data-cprom="${i}" title="Turn into a separate task">↗ task</button><button type="button" class="ghost" data-cdel="${i}" aria-label="Remove">✕</button>`}</div>`).join('')
      +(ro?'':'<div class="li"><input type="text" data-cnew placeholder="Add a checklist item and press Enter"></div>');
    if(ro) return;
    $$('[data-ci]',d).forEach(x=>x.onchange=()=>cl[+x.dataset.ci].done=x.checked);
    $$('[data-ct]',d).forEach(x=>x.oninput=()=>cl[+x.dataset.ct].text=x.value);
    $$('[data-cdel]',d).forEach(x=>x.onclick=()=>{ cl.splice(+x.dataset.cdel,1); drawCheck(); });
    $$('[data-cprom]',d).forEach(x=>x.onclick=()=>{ const c=cl.splice(+x.dataset.cprom,1)[0]; const nt=newTask({title:c.text, calendarId:v('calendarId'), projectId:v('projectId')||null, dueDate:v('dueDate')||null}); if(isPrivate(nt)&&!ensurePrivReadySync()) return; touch(nt); data.tasks.push(nt); changed(); drawCheck(); toast(`Created task “${c.text}”`); });
    const n=$('[data-cnew]',d); n.onkeydown=ev2=>{ if(ev2.key==='Enter'){ ev2.preventDefault(); if(n.value.trim()){ cl.push({id:uid('c'),text:n.value.trim(),done:false}); drawCheck(); $('[data-cnew]',d).focus(); } } };
  };
  drawCheck();
  // dependencies
  const deps=[...t.dependsOn];
  const drawDeps=()=>{
    const cand=data.tasks.filter(x=>x.id!==t.id && isOpen(x) && !deps.includes(x.id));
    $('#tkDeps',d).innerHTML=deps.map(x=>{ const dt=task(x); return dt?`<div class="li"><span class="tag">${isOpen(dt)?'🔒':'✓'} ${esc(dt.title)}</span>${ro?'':`<button type="button" class="ghost" data-ddel="${x}">✕</button>`}</div>`:''; }).join('')
      +(ro?(deps.length?'':'<span class="hint">None</span>'):`<select data-dadd><option value="">Add a task this one waits for…</option>${cand.map(x=>`<option value="${x.id}">${esc(x.title)}</option>`).join('')}</select>`);
    if(ro) return;
    $$('[data-ddel]',d).forEach(b=>b.onclick=()=>{ deps.splice(deps.indexOf(b.dataset.ddel),1); drawDeps(); });
    $('[data-dadd]',d).onchange=ev2=>{ if(ev2.target.value){ deps.push(ev2.target.value); drawDeps(); } };
  };
  drawDeps();
  // reminders
  const rems=clone(t.reminders);
  const anchors=[['due','Before due'],['block','Before each block']];
  const drawRems=()=>{ $('#tkRem',d).innerHTML=ro?(rems.map(r=>`<div class="hint">🔔 ${r.anchor==='block'?'Block':'Due'}: ${esc((OFFSETS.find(o=>o[0]===+r.offsetMin)||[0,fmtDur(r.offsetMin)+' before'])[1])}</div>`).join('')||'<span class="hint">None</span>'):remFormHTML(rems,anchors); if(!ro) bindRems($('#tkRem',d),rems,anchors,drawRems); };
  drawRems();

  function collect(){
    const o=clone(t);
    o.title=v('title').trim()||'Untitled'; o.calendarId=v('calendarId'); o.projectId=v('projectId')||null;
    const st=v('status'); if(st!==o.status){ o.completedAt=st==='done'?nowISO():null; } o.status=st;
    o.priority=+v('priority'); o.dueDate=v('dueDate')||null; o.dueTime=o.dueDate?(v('dueTime')||null):null;
    if(o.dueTime){ const ttz=t.dueTime?(t.tz||ACTIVE_TZ):ACTIVE_TZ; const w=tzConvert(o.dueDate+'T'+o.dueTime,ACTIVE_TZ,ttz); o.tz=ttz; o.dueDate=w.slice(0,10); o.dueTime=w.slice(11,16); } o.estimateMin=est();
    o.blocks=blocks; o.checklist=cl.filter(c=>c.text.trim()); o.dependsOn=deps; o.rrule=o.dueDate?readRR(F):null; if(o.rrule && !o.rrStart) o.rrStart=o.dueDate;
    o.reminders=rems; o.notes=v('notes'); o.tags=splitList(v('tags')); o.private=$('[name=private]',F).checked;
    return o;
  }
  function save(){
    if(!F.reportValidity()) return false;
    const o=collect();
    if(isPrivate(o) && !ensurePrivReadySync()) return false;
    const becameDone = o.status==='done' && (isNew || isOpen(orig));
    if(isNew){ if(becameDone) o.status='todo'; touch(o); data.tasks.push(o); if(opts.linkEvent){ const e=ev(opts.linkEvent); if(e){ e.linkedTaskIds.push(o.id); touch(e); } } }
    else { if(becameDone) o.status=orig.status; Object.assign(orig,o); touch(orig); }
    changed(); closeDialog(d);
    if(becameDone) setTaskStatus(task(o.id),'done'); else { renderAll(); toast(isNew?`Created “${o.title}”`:'Saved',{actions:[['Undo',undo]]}); }
    const dep=o.dependsOn.map(task).filter(x=>x&&isOpen(x)).find(x=>x.blocks.some(b=>o.blocks.some(ob=>zonedToMs(ob.start,ob.tz)<zonedToMs(b.end,b.tz))));
    if(dep) toast(`⚠ “${o.title}” is scheduled before “${dep.title}”, which it depends on, is finished.`);
    return true;
  }
  const a=(n,fn)=>{ const b=$(`[data-a="${n}"]`,d); if(b) b.onclick=fn; };
  a('save',save); F.onsubmit=ev2=>{ ev2.preventDefault(); save(); };
  a('done',()=>{ $('[name=status]',F).value='done'; save(); });
  a('del',async()=>{ if(!await confirmDlg({title:'Delete task',html:`Delete “${esc(orig.title)}”? You can restore it from ⚙ Settings → Trash for 30 days.`},[['Delete','y','danger'],['Cancel',null]])) return; closeDialog(d); removeItem(orig); });
  a('dup',()=>{ closeDialog(d); const c=duplicateItem(orig); openTaskEditor(c.id); });
  a('conv',()=>{ closeDialog(d); const e=convertItem(orig); openEventEditor(e.id); });
  a('tpl',()=>saveAsTemplate([collect()]));
  return d;
}

/* ---------------- PROJECT EDITOR ---------------- */
function openProjectEditor(id, draft){
  const orig=id?proj(id):null; if(id&&!orig) return;
  const p=clone(orig||newProject(draft||{}));
  const isNew=!orig, ro=!editable;
  const parents=data.projects.filter(x=>x.calendarId===p.calendarId && !x.parentId && x.id!==p.id && x.status!=='archived');
  const d=openDialog(`<div class="dhd"><h2>${isNew?'New project':ro?'Project':'Edit project'}</h2><button class="ghost" data-close aria-label="Close">✕</button></div>
  <form class="frm" autocomplete="off">
    <input class="titlein full" name="name" value="${esc(p.name)}" placeholder="Project name" required ${ro?'disabled':''} aria-label="Name">
    <label>Calendar</label><select name="calendarId" ${ro?'disabled':''}>${calOptions(p.calendarId)}</select>
    <label>Colour &amp; icon</label><div class="row"><input type="color" name="color" value="${esc(p.color)}" ${ro?'disabled':''} aria-label="Colour"><span class="daychips">${PALETTE.map(c=>`<button type="button" data-col="${c}" style="background:${c};width:22px;height:22px;padding:0;border-radius:6px" aria-label="Colour ${c}"></button>`).join(' ')}</span></div>
    <label></label><div class="row daychips">${PROJ_ICONS.map(i=>`<button type="button" data-ic="${i}" class="${p.icon===i?'on':''}">${i}</button>`).join('')}</div>
    <label>Status</label><select name="status" ${ro?'disabled':''}>${Object.entries(PROJ_STATUS).map(([k,v])=>`<option value="${k}" ${p.status===k?'selected':''}>${v}</option>`).join('')}</select>
    <label>Dates</label><div class="row">Start <input type="date" name="startDate" value="${p.startDate||''}" ${ro?'disabled':''}> Due <input type="date" name="dueDate" value="${p.dueDate||''}" ${ro?'disabled':''}></div>
    <label>Time budget</label><div class="row"><input type="number" name="budgetHours" min="0" step="0.5" value="${p.budgetHours??''}" style="width:90px" ${ro?'disabled':''}> hours <span class="hint">warns at 80% and 100%</span></div>
    <label>Sub-project of</label><select name="parentId" ${ro?'disabled':''}><option value="">— none —</option>${parents.map(x=>`<option value="${x.id}" ${p.parentId===x.id?'selected':''}>${esc(x.name)}</option>`).join('')}</select>
    <label>Mission / trip</label><div class="row">from <input type="date" name="tripFrom" value="${p.tripFrom||''}" ${ro?'disabled':''}> to <input type="date" name="tripTo" value="${p.tripTo||''}" ${ro?'disabled':''}><span class="hint">links the project to flights in that period (Trips report)</span></div>
    <label>Remind</label><label class="hint"><input type="checkbox" name="remind" ${p.reminders&&p.reminders.length?'checked':''} ${ro?'disabled':''}> 1 day before the project due date</label>
    <label></label><label class="hint"><input type="checkbox" name="pinned" ${p.pinned?'checked':''} ${ro?'disabled':''}> Pin to the top of the list</label>
    <label>Description</label><textarea name="description" rows="3" ${ro?'disabled':''}>${esc(p.description)}</textarea>
  </form>
  <div class="dacts">${!isNew&&editable?'<button data-a="del" class="danger">Delete…</button><button data-a="dup">Duplicate</button><button data-a="tpl">Save as template</button><button data-a="arch">'+(p.status==='archived'?'Unarchive':'Archive')+'</button>':''}
    <span class="sp"></span><button data-close>${ro?'Close':'Cancel'}</button>${editable?'<button class="primary" data-a="save">Save</button>':''}</div>`,{size:'mid',focus:'[name=name]'});
  const F=$('form',d), v=n=>$(`[name=${n}]`,F).value;
  $$('[data-col]',d).forEach(b=>b.onclick=()=>{ $('[name=color]',F).value=b.dataset.col; });
  let icon=p.icon; $$('[data-ic]',d).forEach(b=>b.onclick=()=>{ icon=b.dataset.ic; $$('[data-ic]',d).forEach(x=>x.classList.toggle('on',x===b)); });
  function collect(){ return Object.assign(clone(p),{name:v('name').trim()||'Untitled project', calendarId:v('calendarId'), color:v('color'), icon, status:v('status'), startDate:v('startDate')||null, dueDate:v('dueDate')||null, budgetHours:v('budgetHours')?+v('budgetHours'):null, parentId:v('parentId')||null, tripFrom:v('tripFrom')||null, tripTo:v('tripTo')||null, pinned:$('[name=pinned]',F).checked, description:v('description'), reminders:$('[name=remind]',F).checked?[{offsetMin:1440,channel:'popup'}]:[]}); }
  function save(){
    if(!F.reportValidity()) return;
    const o=collect();
    if(isPrivCal(o.calendarId) && !ensurePrivReadySync()) return;
    if(isNew){ touch(o); data.projects.push(o); }
    else { const calChanged=orig.calendarId!==o.calendarId; Object.assign(orig,o); touch(orig);
      if(calChanged) for(const it of [...data.events,...data.tasks]) if(it.projectId===orig.id){ it.calendarId=o.calendarId; touch(it); } }
    changed(); closeDialog(d); renderAll(); toast(isNew?`Created project “${o.name}”`:'Saved');
  }
  const a=(n,fn)=>{ const b=$(`[data-a="${n}"]`,d); if(b) b.onclick=fn; };
  a('save',save); F.onsubmit=e=>{ e.preventDefault(); save(); };
  a('arch',()=>{ orig.status=orig.status==='archived'?'active':'archived'; touch(orig); changed(); closeDialog(d); renderAll(); });
  a('dup',()=>{ const c=clone(orig); c.id=uid('p'); c.name+=' (copy)'; touch(c); c.created=c.updated; data.projects.push(c);
    for(const t of data.tasks.filter(t=>t.projectId===orig.id)){ const ct=clone(t); ct.id=uid('t'); ct.projectId=c.id; ct.blocks=[]; ct.status='todo'; ct.completedAt=null; touch(ct); data.tasks.push(ct); }
    changed(); closeDialog(d); renderAll(); toast(`Duplicated “${orig.name}” with its tasks`); });
  a('tpl',()=>saveProjectAsTemplate(orig));
  a('del',async()=>{
    const items=[...data.events,...data.tasks].filter(x=>x.projectId===orig.id);
    const choice=await confirmDlg({title:'Delete project', html:`Delete “${esc(orig.name)}”? It has ${items.length} event${items.length===1?'':'s'}/task${items.length===1?'':'s'}. Everything goes to the Trash for 30 days.`},[['Move items to Inbox','move'],['Delete items too','del','danger'],['Cancel',null]]);
    if(!choice) return;
    for(const it of items){ if(choice==='move'){ it.projectId=null; touch(it); } else removeItem(it,true); }
    for(const sp of data.projects.filter(x=>x.parentId===orig.id)){ sp.parentId=null; touch(sp); }
    removeItem(orig,true); changed(); closeDialog(d); closeDialog(); renderAll(); toast('Project deleted',{actions:[['Undo',undo]]});
  });
}

/* ---------------- PROJECT PAGE ---------------- */
function projStats(p){
  const ids=new Set([p.id, ...data.projects.filter(x=>x.parentId===p.id).map(x=>x.id)]);
  const ts=data.tasks.filter(t=>ids.has(t.projectId)), es=data.events.filter(e=>ids.has(e.projectId));
  const done=ts.filter(t=>t.status==='done').length, open=ts.filter(isOpen).length;
  const est=ts.reduce((a,t)=>a+(t.estimateMin||0),0), estDone=ts.filter(t=>t.status==='done').reduce((a,t)=>a+(t.estimateMin||0),0);
  const planned=ts.reduce((a,t)=>a+plannedMin(t),0)+es.filter(e=>!e.allDay&&e.status!=='cancelled'&&!e.rrule).reduce((a,e)=>a+localDiffMin(e.start,e.end),0);
  const spent=ts.reduce((a,t)=>a+spentMin(t),0);
  const progress = est && ts.every(t=>t.estimateMin) ? estDone/est : (ts.length? done/(done+open||1) : 0);
  return {ts, es, done, open, overdue:ts.filter(isOverdue).length, est, planned, spent, progress};
}
let ppTab='tasks', ppView='list';
function openProjectPage(id){
  const draw=()=>{
    const p=proj(id); if(!p){ closeDialog(dd); return; }
    const st=projStats(p), c=cal(p.calendarId);
    const budget=p.budgetHours?`<div style="min-width:220px"><div class="hint">Hours: ${fmtH(st.planned)} planned · ${fmtH(st.spent)} spent · budget ${p.budgetHours} h</div><div class="progress ${st.planned/60>p.budgetHours?'over':''}"><i style="width:${Math.min(100,st.spent/60/p.budgetHours*100)}%"></i></div></div>`:'';
    let body='';
    if(ppTab==='tasks'){
      const list=sortTasks([...st.ts],'due');
      body=`<div class="vtools"><div class="seg"><button data-pv="list" class="${ppView==='list'?'on':''}">List</button><button data-pv="board" class="${ppView==='board'?'on':''}">Board</button></div>${editable?'<button data-pnew="task">＋ Task</button><button data-pnew="event">＋ Event</button>':''}</div>`;
      if(ppView==='list') body+=`<div class="tl">${list.filter(isOpen).map(t=>taskRowHTML(t)).join('')||'<div class="hint">No open tasks.</div>'}${list.some(t=>!isOpen(t))?`<div class="tgroup">Completed</div>${list.filter(t=>!isOpen(t)).map(t=>taskRowHTML(t)).join('')}`:''}</div>`;
      else body+=`<div class="board">${Object.keys(TASK_STATUS).map(s=>`<div class="bcol" data-status="${s}"><h4><span>${TASK_STATUS[s]}</span><span>${list.filter(t=>(t.status||'todo')===s).length}</span></h4>${list.filter(t=>(t.status||'todo')===s).map(t=>taskRowHTML(t)).join('')}</div>`).join('')}</div>`;
    } else if(ppTab==='timeline'){
      const from=NOW()-60*864e5, to=NOW()+180*864e5;
      const items=[];
      for(const e of st.es) for(const o of expandEvent(e,from,to)) items.push(o);
      for(const t of st.ts) for(const o of blockOccs(t,from,to)) items.push(o);
      for(const t of st.ts) if(t.dueDate) items.push({kind:'due', s:fromKey(t.dueDate).getTime()+86399000, title:t.title, item:t, id:t.id});
      items.sort((a,b)=>a.s-b.s);
      let lastDay='';
      body='<div class="ag">'+items.map(o=>{ const k=key(new Date(o.s)); const hdr=k!==lastDay?`<div class="tgroup">${esc(fmtLong(fromKey(k)))}</div>`:''; lastDay=k;
        if(o.kind==='due') return hdr+agTaskRow(o.item, 'Due');
        return hdr+agOccRow(o, o.allDay?'All day':hm(o.s)+'–'+hm(o.e)); }).join('')+(items.length?'':'<div class="hint">Nothing in the last 60 or next 180 days.</div>')+'</div>';
    } else {
      body=`<div class="kpis">
        <div class="kpi"><div class="v">${Math.round(st.progress*100)}%</div><div class="l">progress</div></div>
        <div class="kpi"><div class="v">${st.open}</div><div class="l">open tasks</div></div>
        <div class="kpi"><div class="v">${st.done}</div><div class="l">done</div></div>
        <div class="kpi"><div class="v" style="${st.overdue?'color:var(--sick)':''}">${st.overdue}</div><div class="l">overdue</div></div>
        <div class="kpi"><div class="v">${fmtH(st.est)}</div><div class="l">estimated</div></div>
        <div class="kpi"><div class="v">${fmtH(st.planned)}</div><div class="l">planned</div></div>
        <div class="kpi"><div class="v">${fmtH(st.spent)}</div><div class="l">spent</div></div>
        ${p.budgetHours?`<div class="kpi"><div class="v" style="${st.spent/60>p.budgetHours?'color:var(--sick)':''}">${Math.round(st.spent/60/p.budgetHours*100)}%</div><div class="l">of ${p.budgetHours} h budget</div></div>`:''}
      </div>
      ${barChart([{label:'Estimated',v:st.est/60,c:'var(--muted)'},{label:'Planned',v:st.planned/60,c:p.color},{label:'Spent',v:st.spent/60,c:'var(--tele)'},...(p.budgetHours?[{label:'Budget',v:p.budgetHours,c:'var(--leave)'}]:[])],'h')}
      <div class="rsec">Upcoming deadlines</div>${st.ts.filter(t=>isOpen(t)&&t.dueDate).sort((a,b)=>a.dueDate.localeCompare(b.dueDate)).slice(0,8).map(t=>taskRowHTML(t)).join('')||'<div class="hint">None.</div>'}`;
    }
    dd.querySelector('.box').innerHTML=`<div class="dhd"><span class="dotc" style="background:${esc(p.color)};width:16px;height:16px"></span><h2>${esc(p.icon||'')} ${esc(p.name)}</h2>
      <span class="tag">${esc(c.name)}</span><span class="tag">${PROJ_STATUS[p.status]}</span>${editable?'<button data-pedit>✎ Edit</button>':''}<button data-prep>📊 Report</button><button class="ghost" data-close>✕</button></div>
      <div class="pp-head">${p.dueDate?`<span class="hint">Due ${esc(fmtShort(fromKey(p.dueDate)))}</span>`:''}<div style="flex:1;min-width:180px"><div class="hint">${st.done} of ${st.done+st.open} tasks done</div><div class="progress"><i style="width:${Math.round(st.progress*100)}%"></i></div></div>${budget}</div>
      ${p.description?`<p class="hint">${esc(p.description)}</p>`:''}
      <div class="tabs">${[['tasks','Tasks'],['timeline','Timeline'],['stats','Stats']].map(([k,l])=>`<button data-ptab="${k}" class="${ppTab===k?'on':''}">${l}</button>`).join('')}</div>${body}`;
    const B=dd.querySelector('.box');
    $$('[data-close]',B).forEach(b=>b.onclick=()=>closeDialog(dd));
    $$('[data-ptab]',B).forEach(b=>b.onclick=()=>{ ppTab=b.dataset.ptab; draw(); });
    $$('[data-pv]',B).forEach(b=>b.onclick=()=>{ ppView=b.dataset.pv; draw(); });
    const pe=$('[data-pedit]',B); if(pe) pe.onclick=()=>openProjectEditor(id);
    $('[data-prep]',B).onclick=()=>openReports('project',{projectId:id});
    $$('[data-pnew]',B).forEach(b=>b.onclick=()=> b.dataset.pnew==='task'?openTaskEditor(null,newTask({projectId:id,calendarId:p.calendarId})):openEventEditor(null,newEvent({projectId:id,calendarId:p.calendarId})));
    bindTaskRows(B);
    $$('[data-occ]',B).forEach(r=>r.onclick=()=>openOccPopover(r.dataset.occ,r.dataset.oc,r.dataset.ok,r));
    $$('.bcol',B).forEach(c=>{ c.addEventListener('dragover',e=>{ if(editable){ e.preventDefault(); c.classList.add('dragover'); } }); c.addEventListener('dragleave',()=>c.classList.remove('dragover'));
      c.addEventListener('drop',e=>{ c.classList.remove('dragover'); e.preventDefault(); const t=task(e.dataTransfer.getData('text/x-task')); if(t) setTaskStatus(t,c.dataset.status); }); });
  };
  const dd=openDialog('',{size:'wide', refresh:()=>draw()});
  draw();
}
