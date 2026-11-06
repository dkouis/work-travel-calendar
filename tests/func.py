import os
import asyncio, json, sys, time, re, datetime
from playwright.async_api import async_playwright
BASE='http://127.0.0.1:8099/'
SH=os.path.join(os.path.dirname(os.path.abspath(__file__)),'shots')+'/'
os.makedirs(SH, exist_ok=True)
SRV=os.environ.get('WTC_SRV','./testsrv')+'/data/calendar.php'
R=[]
def ok(name, cond, info=''):
    R.append((name, bool(cond))); print(('PASS ' if cond else 'FAIL ')+name+(('  ['+str(info)+']') if info!='' else ''), flush=True)
def server():
    s=open(SRV).read(); return json.loads(s[s.index('\n')+1:])
async def login(page,u,p):
    await page.goto(BASE); await page.fill('#lUser',u); await page.fill('#lPass',p); await page.click('#lBtn')
    await page.wait_for_selector('body:not(.locked)',timeout=8000); await page.wait_for_timeout(600)
    for _ in range(3):
        if await page.locator('.dlg').count(): await page.keyboard.press('Escape'); await page.wait_for_timeout(200)
async def saved(page):
    for _ in range(60):
        if (await page.inner_text('#saveState')).startswith('Saved'): return True
        await page.wait_for_timeout(150)
    return False
async def ev(page, js, arg=None): return await page.evaluate(js, arg)

async def main():
    async with async_playwright() as pw:
        b=await pw.chromium.launch()
        async def mk(fixed=True):
            c=await b.new_context(viewport={'width':1440,'height':900}, timezone_id='Europe/Athens', locale='en-GB')
            # the suite is written for Sunday 1 Nov 2026 14:00 Athens; time then runs normally
            if fixed: await c.clock.install(time=datetime.datetime(2026,11,1,12,0,tzinfo=datetime.timezone.utc))
            return c
        ctx=await mk(); await ctx.grant_permissions(['notifications'],origin=BASE.rstrip('/'))
        page=await ctx.new_page(); errs=[]
        page.on('pageerror',lambda e:errs.append('PAGEERR '+str(e)))
        page.on('console',lambda m: errs.append('CONSOLE '+m.text) if m.type=='error' else None)
        await login(page,'dkouis','secret123')
        await page.evaluate("()=>{ prefs.openEditors='popup'; savePrefs(); }")   # editor internals tested here; edit tabs: tests/edit.py
        seed_days=server()['data']['days']

        # ---- migration ----
        sd=server()['data']
        ok('migration: schema 2, days unchanged', sd.get('schema')==2 and sd['days']==seed_days and sd['monthNotes']=={'2026-10':'October notes'}, len(sd['days']))

        # ---- project via UI ----
        await page.click('#addBtn'); await page.click('#addMenu [data-new=project]')
        await page.fill('.dlg [name=name]','Council WG'); await page.fill('.dlg [name=budgetHours]','10')
        await page.click('.dlg [data-a=save]'); await saved(page)
        pid=await ev(page,"data.projects.find(p=>p.name==='Council WG').id")
        ok('project created', bool(pid))

        # ---- quick add parser: 20 phrases ----
        cases=await ev(page,"""()=>{ const t=todayKey(), plus=n=>keyAdd(t,n), wd=i=>key(addDays(fromKey(t),(i-dowKey(t)+7)%7));
          const P=s=>parseQuick(s);
          const C=[
           ['Meeting WG tue 10-12:30 #Council @Brussels !work', q=>q.type==='event'&&q.date===wd(1)&&q.start===600&&q.end===750&&q.projectId&&q.location==='Brussels'&&q.title==='Meeting WG'],
           ['Draft compromise text by fri 17:00 ~4h p1 #Council', q=>q.type==='task'&&q.date===wd(4)&&q.start===1020&&q.estimateMin===240&&q.priority===1&&q.title==='Draft compromise text'],
           ['Call plumber tomorrow', q=>q.type==='task'&&q.date===plus(1)&&q.title==='Call plumber'],
           ['Dentist 5/11 16:30 !personal', q=>q.type==='event'&&q.date==='2026-11-05'&&q.start===990&&q.calendarId==='personal'&&q.title==='Dentist'],
           ['Team standup 9:30-9:45 *weekdays', q=>q.type==='event'&&q.start===570&&q.end===585&&q.rrule&&q.rrule.byDay.join()==='0,1,2,3,4'],
           ['Σύσκεψη αύριο 11:00-12:00', q=>q.type==='event'&&q.date===plus(1)&&q.start===660&&q.end===720&&q.title==='Σύσκεψη'],
           ['Υποβολή εξόδων μέχρι Παρασκευή ~30m', q=>q.type==='task'&&q.date===wd(4)&&q.estimateMin===30&&q.title==='Υποβολή εξόδων'],
           ['Lunch with Maria next wed 13-14:30 @Athens', q=>q.type==='event'&&q.date===key(addDays(fromKey(wd(2)),7))&&q.start===780&&q.end===870&&q.title==='Lunch with Maria'],
           ['Pay rent 1/12 *monthly !personal', q=>q.type==='task'&&q.date==='2026-12-01'&&q.rrule&&q.rrule.freq==='monthly'&&q.calendarId==='personal'],
           ['Read report ~1h30 p2', q=>q.type==='task'&&q.estimateMin===90&&q.priority===2&&!q.date&&q.title==='Read report'],
           ['task: Book hotel in Brussels by mon', q=>q.type==='task'&&q.date===wd(0)&&q.title==='Book hotel in Brussels'],
           ['Flight prep today 18:00', q=>q.type==='event'&&q.date===t&&q.start===1080&&q.title==='Flight prep'],
           ['Review minutes in 3 days', q=>q.type==='task'&&q.date===plus(3)&&q.title==='Review minutes'],
           ['Conference 2026-11-20 9am-5pm @Lisbon', q=>q.type==='event'&&q.date==='2026-11-20'&&q.start===540&&q.end===1020&&q.location==='Lisbon'],
           ['Γιορτή Κυριακή 20:00 !personal', q=>q.type==='event'&&q.date===wd(6)&&q.start===1200&&q.calendarId==='personal'],
           ['Weekly report fri 15-16 *weekly #Council', q=>q.type==='event'&&q.rrule.freq==='weekly'&&q.rrule.byDay.join()==='4'&&q.start===900],
           ['Prepare slides 12 nov p3 ~2h', q=>q.type==='task'&&q.date==='2026-11-12'&&q.estimateMin===120&&q.priority===3&&q.title==='Prepare slides'],
           ['Doctor 10 Δεκεμβρίου 10:30', q=>q.type==='event'&&q.date==='2026-12-10'&&q.start===630&&q.title==='Doctor'],
           ['Gym σε 2 μέρες 19:00-20:00 !personal', q=>q.type==='event'&&q.date===plus(2)&&q.start===1140&&q.end===1200&&q.title==='Gym'],
           ['Send agenda by tomorrow 12:00 p1', q=>q.type==='task'&&q.date===plus(1)&&q.start===720&&q.priority===1&&q.title==='Send agenda'],
          ];
          return C.map(([s,f])=>{ const q=P(s); let r=false; try{ r=!!f(q); }catch(e){} return [s,r,JSON.stringify({type:q.type,title:q.title,date:q.date,start:q.start,end:q.end,est:q.estimateMin,p:q.priority,cal:q.calendarId,proj:!!q.projectId,loc:q.location,rr:q.rrule})]; }); }""")
        bad=[c for c in cases if not c[1]]
        ok(f'quick add parses {len(cases)-len(bad)}/{len(cases)} phrases', not bad, '; '.join(c[0]+' -> '+c[2] for c in bad))

        # ---- quick add via UI ----
        await page.click('#addBtn'); await page.wait_for_selector('#qa:focus'); await page.keyboard.type('Meeting WG tue 10-12:30 #Council @Brussels'); await page.wait_for_timeout(200)
        ok('quick add preview shows', await page.locator('#qaPrev.open').count()==1)
        await page.keyboard.press('Enter'); await saved(page)
        e=await ev(page,"data.events.find(e=>e.title==='Meeting WG')")
        ok('quick add created event in the active zone', e and e['tz']=='Europe/Athens' and e['projectId']==pid and e['start'].endswith('T10:00') and e['location']=='Brussels', e and (e['start'],e['tz']))
        await page.click('#addBtn'); await page.wait_for_selector('#qa:focus'); await page.keyboard.type('Draft compromise text by fri 17:00 ~4h p1 #Council'); await page.keyboard.press('Enter'); await saved(page)
        t=await ev(page,"data.tasks.find(t=>t.title==='Draft compromise text')")
        ok('quick add created task', t and t['estimateMin']==240 and t['priority']==1 and t['dueTime']=='17:00')
        tid=t['id']; due0=t['dueDate']

        # ---- go to the week with Tuesday 3 Nov ----
        await ev(page,"()=>{ cursor=fromKey('2026-11-03'); setView('week'); }")
        await page.wait_for_timeout(300)
        ok('event shows in week grid', await page.locator('.ev[data-ok=event]', has_text='Meeting WG').count()==1)
        # drag task from panel → Wed 4 Nov 14:00
        await ev(page,"()=>{ tpTab='all'; renderTasksPanel(); }")
        await ev(page,"()=>{ const b=$('#tgBody'); b.scrollTop=8*hourPx(); }")
        col=page.locator('.tg-col[data-col="2026-11-04"]'); bb=await col.bounding_box()
        hp=await ev(page,"hourPx()"); top=await ev(page,"$('#tgBody').scrollTop")
        body=await page.locator('#tgBody').bounding_box()
        y=body['y']+(14*hp-top)+2
        await page.locator(f'.trow[data-task="{tid}"]').drag_to(col, target_position={'x':bb['width']/2,'y':y-bb['y']})
        await saved(page)
        t=await ev(page,f"task('{tid}')")
        ok('drag task onto grid creates 2 h block at 14:00', len(t['blocks'])==1 and t['blocks'][0]['start']=='2026-11-04T14:00' and t['blocks'][0]['end']=='2026-11-04T16:00', t['blocks'])
        ok('block drawn hatched in grid', await page.locator('.ev.task', has_text='Draft compromise text').count()==1)
        # fill estimate in editor
        await ev(page,f"openTaskEditor('{tid}')"); await page.click('.dlg [data-bauto]'); await page.wait_for_timeout(200)
        plan=await page.inner_text('.dlg #tkPlan')
        ok('task editor: fill the estimate → 4 h of 4 h', '4 h scheduled of 4 h' in plan, plan)
        await page.click('.dlg [data-a=save]'); await saved(page)
        t=await ev(page,f"task('{tid}')")
        ok('blocks saved, due date unchanged', len(t['blocks'])>=2 and t['dueDate']==due0, len(t['blocks']))
        await page.screenshot(path=SH+'week-items.png')
        # move block by dragging (pointer)
        blk=page.locator(f'.ev[data-occ="{tid}"]').first
        bid=await blk.get_attribute('data-oc'); bx=await blk.bounding_box()
        before=await ev(page,f"task('{tid}').blocks.find(b=>b.id==='{bid}').start")
        await page.mouse.move(bx['x']+bx['width']/2, bx['y']+10); await page.mouse.down()
        await page.mouse.move(bx['x']+bx['width']/2, bx['y']+10+hp*1, steps=6); await page.mouse.up(); await saved(page)
        after=await ev(page,f"task('{tid}').blocks.find(b=>b.id==='{bid}').start")
        ok('drag block moves it 1 hour, due unchanged', localdiff(before,after)==60 and (await ev(page,f"task('{tid}').dueDate"))==due0, (before,after))
        # resize event by its lower edge
        evl=page.locator('.ev[data-ok=event]', has_text='Meeting WG'); eb=await evl.bounding_box()
        await page.mouse.move(eb['x']+eb['width']/2, eb['y']+eb['height']-3); await page.mouse.down(); await page.mouse.move(eb['x']+eb['width']/2, eb['y']+eb['height']-3+hp/2, steps=5); await page.mouse.up(); await saved(page)
        e=await ev(page,"data.events.find(e=>e.title==='Meeting WG')")
        ok('resize event by lower edge (+30 min)', e['end']=='2026-11-03T13:00', e['end'])
        # mark block done with 1 h 30
        bk=page.locator(f'.ev[data-occ="{tid}"] [data-bdone]').first
        await bk.click(); await page.fill('.dlg #tsH','1'); await page.fill('.dlg #tsM','30'); await page.click('.dlg button[type=submit]'); await saved(page)
        st=await ev(page,f"projStats(proj('{pid}'))")
        ok('block done with 1 h 30 → project spent 90 min', st['spent']==90, st['spent'])

        # ---- find a slot skips events, flights, leave ----
        await ev(page,"""()=>{ dayMut('2026-11-05').am='leave'; dayMut('2026-11-05').pm='leave'; (dayMut('2026-11-02').flights=[]).push({from:'Athens',to:'Brussels',time:'morning',note:'A3 610 07:10'}); changed(); }""")
        sl=await ev(page,"""()=>{ const s=findSlots({len:60, calendarId:'work', fromMs:fromKey('2026-11-02').getTime(), dueDate:'2026-11-06', n:40}); const busy=busyIntervals(fromKey('2026-11-02').getTime(), fromKey('2026-11-07').getTime());
           return {n:s.length, leave:s.filter(x=>x.k==='2026-11-05').length, overlap:s.filter(x=>busy.some(([a,b])=>x.s<b&&a<x.e)).length, flight:s.filter(x=>x.k==='2026-11-02'&&new Date(x.s).getHours()<10).length, outside:s.filter(x=>{const h=new Date(x.s).getHours()*60+new Date(x.s).getMinutes(); return h<510||h+60>1050;}).length}; }""")
        ok('find a slot: none on leave day, none overlapping, none during flight, inside work hours', sl['n']>3 and sl['leave']==0 and sl['overlap']==0 and sl['flight']==0 and sl['outside']==0, sl)
        await saved(page)

        # ---- conflict detection ----
        await page.click('#addBtn'); await page.wait_for_selector('#qa:focus'); await page.keyboard.type('Overlap test tue 11-12'); await page.keyboard.press('Enter'); await saved(page)
        await ev(page,"render()")
        ok('conflict badge on overlapping events', await page.locator('.ev.conflict').count()>=2)

        # ---- recurring event: this / following ----
        await page.click('#addBtn'); await page.wait_for_selector('#qa:focus'); await page.keyboard.type('Weekly sync fri 15-16 *weekly'); await page.keyboard.press('Enter'); await saved(page)
        rid=await ev(page,"data.events.find(e=>e.title==='Weekly sync').id")
        s0=await ev(page,"data.events.find(e=>e.title==='Weekly sync').start.slice(0,10)")
        k2=await ev(page,f"keyAdd('{s0}',7)"); k3=await ev(page,f"keyAdd('{s0}',14)")
        await ev(page,f"openEventEditor('{rid}',null,'{k2}')"); await page.fill('.dlg [name=title]','Weekly sync (moved)'); await page.fill('.dlg [name=st]','16:00')
        await page.click('.dlg [data-a=save]'); await page.click('.dlg button:has-text("Only this one")'); await saved(page)
        e=await ev(page,f"ev('{rid}')")
        ok('repeating: edit only this occurrence', e['exceptions'].get(k2,{}).get('title')=='Weekly sync (moved)' and e['exceptions'][k2]['start']==k2+'T16:00' and e['title']=='Weekly sync', e['exceptions'])
        await ev(page,f"openEventEditor('{rid}',null,'{k3}')"); await page.fill('.dlg [name=st]','09:00')
        await page.click('.dlg [data-a=save]'); await page.click('.dlg button:has-text("This and following")'); await saved(page)
        evs=await ev(page,"data.events.filter(e=>e.title==='Weekly sync')")
        ok('repeating: this and following splits the series', len(evs)==2 and any(x['rrule'].get('until')==daybefore(k3) for x in evs) and any(x['start']==k3+'T09:00' for x in evs), [(x['start'],x['rrule']) for x in evs])
        # ics round trip
        rt=await ev(page,"""()=>{ let txt=''; const d0=download; download=(n,t)=>{ txt=t; }; exportIcs({}); download=d0;
            const items=parseIcs(txt); const from=fromKey('2026-11-01').getTime(), to=fromKey('2027-01-31').getTime();
            const orig=data.events.flatMap(e=>expandEvent(e,from,to)).map(o=>o.title+'@'+o.s).sort();
            const back=items.filter(x=>x.kind==='event').map(x=>({...newEvent({}),...x,tz:x.tz,exceptions:x.exceptions||{},reminders:[]})).flatMap(e=>{ if(e.allDay) delete e.tz; return expandEvent(e,from,to); }).filter(o=>!o.title.startsWith('[Task]')).map(o=>o.title+'@'+o.s).sort();
            return {n:orig.length, same:JSON.stringify(orig)===JSON.stringify(back), diff:orig.filter(x=>!back.includes(x)).concat(back.filter(x=>!orig.includes(x))).slice(0,6), valarm:(txt.match(/BEGIN:VALARM/g)||[]).length, vtz:txt.includes('BEGIN:VTIMEZONE'), todo:txt.includes('BEGIN:VTODO')}; }""")
        ok('.ics export → import gives the same occurrences', rt['same'] and rt['n']>10 and rt['vtz'] and rt['todo'] and rt['valarm']>0, rt)

        # ---- reminders: popup + desktop, one tab only ----
        page2=await ctx.new_page(); page2.on('pageerror',lambda e:errs.append('P2 '+str(e)))
        await page2.goto(BASE); await page2.wait_for_selector('body:not(.locked)'); await page2.wait_for_timeout(500)
        if await page2.locator('.dlg').count(): await page2.keyboard.press('Escape')
        await page.bring_to_front()
        await ev(page,"""()=>{ const tz=BROWSER_TZ, s=Math.ceil((Date.now()+35000)/60000)*60000; const e=newEvent({title:'Reminder test', start:msToZoned(s,tz), end:msToZoned(s+30*60000,tz), tz, reminders:[{offsetMin:0,channel:'desktop'}]}); saveNewItem(e); }""")
        await saved(page)
        await page2.evaluate("reloadData(false)")
        t0=time.time(); got=False
        while time.time()-t0<130:
            c1=await page.locator('#remToast').count(); c2=await page2.locator('#remToast').count()
            if c1 or c2: got=True; break
            await page.wait_for_timeout(1000)
        await page.wait_for_timeout(1500)
        c1=await page.locator('#remToast').count(); c2=await page2.locator('#remToast').count()
        ok('reminder popup fires once across two tabs', got and c1+c2==1, (c1,c2, round(time.time()-t0)))
        await page.screenshot(path=SH+'reminder.png')
        holder=page if c1 else page2
        ok('bell shows unread notification', (await holder.inner_text('#bellCnt')).strip()!='')
        await holder.select_option('#remToast [data-rs="0"]','5'); await holder.wait_for_timeout(300)
        sn=await holder.evaluate("jget(remK('snoozed'),[]).length")
        ok('snooze 5 min stores it', sn==1 and await holder.locator('#remToast').count()==0)
        await page2.close()
        # catch-up while away
        await ev(page,"""()=>{ const tz=BROWSER_TZ, s=Date.now()-30*60000; const e=newEvent({title:'Missed while away', start:msToZoned(s,tz), end:msToZoned(s+15*60000,tz), tz, reminders:[{offsetMin:0,channel:'popup'}]}); saveNewItem(e); }""")
        await saved(page)
        await ev(page,"safeSet(remK('lastcheck'), Date.now()-3600000)")
        await page.reload(); await page.wait_for_selector('body:not(.locked)'); await page.wait_for_timeout(1500)
        txt=await page.inner_text('body')
        ok('missed reminders shown on next open ("While you were away")', 'While you were away' in txt and 'Missed while away' in txt)
        for _ in range(3):
            if await page.locator('.dlg').count(): await page.keyboard.press('Escape'); await page.wait_for_timeout(200)
        q=await ev(page,"()=>{ prefs.quietStart='00:00'; prefs.quietEnd='23:59'; const a=inQuiet(); prefs.quietStart='22:00'; prefs.quietEnd='07:30'; return a; }")
        ok('quiet hours detection', q)

        # ---- privacy ----
        await ev(page,"openPrivSetup()"); await page.fill('.dlg #pp1','my-pass-phrase'); await page.fill('.dlg #pp2','my-pass-phrase'); await page.click('.dlg button[type=submit]'); await saved(page)
        await page.click('#addBtn'); await page.wait_for_selector('#qa:focus'); await page.keyboard.type('Dentist 5/11 16:30 !personal'); await page.keyboard.press('Enter'); await saved(page)
        raw=open(SRV).read()
        sd=server()['data']
        ok('Personal item encrypted on the server', 'Dentist' not in raw and sd.get('private') and len(sd.get('busy',[]))>=1)
        ctxv=await mk(); pv=await ctxv.new_page(); pv.on('pageerror',lambda e:errs.append('VIEWER '+str(e)))
        await login(pv,'viewer','viewer123')
        await pv.evaluate("()=>{ cursor=fromKey('2026-11-05'); setView('day'); }"); await pv.wait_for_timeout(400)
        html=await pv.content()
        ok('viewer cannot see Personal titles; sees Busy', 'Dentist' not in html and await pv.locator('.ev.busy').count()>=1 and 'Meeting WG' in (await pv.evaluate("JSON.stringify(data.events.map(e=>e.title))")))
        ok('viewer has no editing', await pv.locator('#addBtn').is_hidden() and await pv.evaluate("editable")==False)
        await pv.screenshot(path=SH+'viewer-day.png')
        # viewer unlock with passphrase sees it
        await pv.evaluate("openUnlock()"); await pv.fill('.dlg #up','my-pass-phrase'); await pv.click('.dlg button[type=submit]'); await pv.wait_for_timeout(800)
        ok('viewer with the passphrase can read Personal items', 'Dentist' in await pv.evaluate("JSON.stringify(data.events.map(e=>e.title))"))
        await ctxv.close()

        # ---- reports ----
        for k in ['daily','eod','weekly','project','time','trips']:
            await ev(page,f"()=>{{ closeDialog(); openReports('{k}'); }}"); await page.wait_for_timeout(250)
            ok(f'report {k} renders', await page.locator('#repBody').count()==1)
            if k=='weekly':
                await page.fill('.dlg [data-rw]','2026-11-02'); await page.dispatch_event('.dlg [data-rw]','change'); await page.wait_for_timeout(200)
                await page.screenshot(path=SH+'report-weekly.png', full_page=False)
            if k=='trips':
                await page.click('.dlg [data-q=all]'); await page.wait_for_timeout(200)
                n=await page.locator('#repBody tr[data-k]').count()
                exp=sum(len(v.get('flights',[])) for v in server()['data']['days'].values())
                ok('trips report lists every flight', n==exp, (n,exp))
            if k=='time':
                await page.fill('.dlg [data-rf]','2026-11-02'); await page.dispatch_event('.dlg [data-rf]','change'); await page.fill('.dlg [data-rt]','2026-11-08'); await page.dispatch_event('.dlg [data-rt]','change'); await page.wait_for_timeout(200)
                tot=await ev(page,"timeEntries(fromKey('2026-11-02').getTime(), fromKey('2026-11-09').getTime()).reduce((a,x)=>a+x.min,0)")
                hand=await ev(page,"""()=>{ let m=0; const f=fromKey('2026-11-02').getTime(), t=fromKey('2026-11-09').getTime(); for(const e of data.events){ if(e.allDay||e.showAs==='free') continue; for(const o of expandEvent(e,f,t)) if(!o.cancelled) m+=(Math.min(o.e,t)-Math.max(o.s,f))/60000; } for(const x of data.tasks) for(const b of x.blocks){ const s=zonedToMs(b.start,b.tz), e=zonedToMs(b.end,b.tz); if(e>f&&s<t) m+=(e-s)/60000; } return m; }""")
                ok('time allocation total matches hand count', abs(tot-hand)<0.01 and tot>0, (tot,hand))
                await page.screenshot(path=SH+'report-time.png')
        await page.keyboard.press('Escape')

        # ---- merge when two editors save at once ----
        cA=await mk(False); pA=await cA.new_page(); await login(pA,'dkouis','secret123')
        cB=await mk(False); pB=await cB.new_page(); await login(pB,'dkouis','secret123')
        await pA.evaluate("()=>{ const e=newEvent({title:'From A', start:'2026-11-10T10:00', end:'2026-11-10T11:00', tz:'Europe/Athens'}); saveNewItem(e); }"); await saved(pA)
        await pB.evaluate("()=>{ const t=newTask({title:'From B', dueDate:'2026-11-11'}); saveNewItem(t); }"); await saved(pB)
        sd=server()['data']
        ok('two editors: both changes merged on the server', any(x['title']=='From A' for x in sd['events']) and any(x['title']=='From B' for x in sd['tasks']))
        await pA.evaluate("()=>{ const e=data.events.find(x=>x.title==='From A'); e.location='Room A'; touch(e); changed(); }"); await saved(pA)
        await pB.evaluate("()=>{ const e=data.events.find(x=>x.title==='From A'); e.location='Room B'; touch(e); changed(); }"); await saved(pB)
        note=await pB.inner_text('#banner')
        ok('same item edited by both: newer kept and reported', 'Room B' in json.dumps(server()['data']['events']) and 'changed by both' in note, note[:120])
        await cA.close(); await cB.close()
        await page.reload(); await page.wait_for_selector('body:not(.locked)'); await page.wait_for_timeout(800)
        for _ in range(3):
            if await page.locator('.dlg').count(): await page.keyboard.press('Escape'); await page.wait_for_timeout(200)

        # ---- keyboard only: create, complete ----
        await page.keyboard.press('q'); await page.keyboard.type('Keyboard task tomorrow p2'); await page.keyboard.press('Enter'); await saved(page)
        kt=await ev(page,"data.tasks.find(t=>t.title==='Keyboard task')")
        await ev(page,"()=>{ tpTab='upcoming'; renderTasksPanel(); }")
        await page.focus(f'.trow[data-task="{kt["id"]}"]'); await page.keyboard.press(' '); await saved(page)
        ok('keyboard only: quick add + Space completes task', await ev(page,f"task('{kt['id']}').status")=='done')
        # undo
        await page.locator('body').click(position={'x':5,'y':890})
        await page.keyboard.press('Control+z'); await saved(page)
        ok('undo restores previous state', await ev(page,f"task('{kt['id']}').status")!='done')

        # ---- recurring task ----
        await ev(page,"()=>{ const t=newTask({title:'Expense claim', dueDate:'2026-11-30', rrule:{freq:'monthly',interval:1}}); t.rrStart='2026-11-30'; saveNewItem(t); setTaskStatus(t,'done'); }"); await saved(page)
        nx=await ev(page,"data.tasks.filter(t=>t.title==='Expense claim').map(t=>[t.dueDate,t.status])")
        ok('recurring task creates the next one on completion', ['2026-12-30','todo'] in nx, nx)

        # ---- templates ----
        await ev(page,"()=>{ openTemplates(); }"); await page.click('.dlg [data-use="0"]'); await page.fill('.dlg [name=d]','2026-11-16'); await page.click('.dlg [data-go]'); await saved(page)
        sd=server()['data']
        ok('template creates flights, event and task', any(f['to']=='Brussels' for f in sd['days'].get('2026-11-16',{}).get('flights',[])) and any(t['title']=='Mission expense claim' and t['dueDate']=='2026-11-21' for t in sd['tasks']))

        # ---- auto-schedule preview ----
        await ev(page,"()=>{ ['A1','A2','A3'].forEach((n,i)=>saveNewItem(newTask({title:'Auto '+n, dueDate:keyAdd(todayKey(),5+i), estimateMin:90, priority:2}))); }"); await saved(page)
        await ev(page,"openAutoSchedule(data.tasks.filter(t=>t.title.startsWith('Auto ')))"); await page.wait_for_timeout(200)
        rows=await page.locator('.dlg tbody tr').count()
        await page.click('.dlg #asOk'); await saved(page)
        ab=await ev(page,"data.tasks.filter(t=>t.title.startsWith('Auto ')).map(t=>plannedMin(t))")
        ok('auto-schedule preview then accept fills estimates', rows>=3 and all(x>=90 for x in ab), (rows,ab))


        # ---- v2.1: flights in the grid, editable times, half-day leave ----
        await ev(page,"()=>{ closeDialog(); const m=dayMut('2026-11-09'); m.flights=[{from:'Athens',to:'Brussels',time:'noon'}]; m.am='leave'; delete m.pm; changed(); cursor=fromKey('2026-11-09'); setView('week'); }"); await saved(page)
        fl=page.locator('.tg-col[data-col="2026-11-09"] .ev.flt')
        ok('flight drawn in time grid with default Noon hours 16:00–20:00', await fl.count()==1 and '16:00–20:00' in await fl.inner_text(), await fl.inner_text() if await fl.count() else '')
        dflt=await ev(page,"Object.entries(FLIGHT_WINDOWS).map(([k,[a,b]])=>k+' '+minToHM(a)+'-'+minToHM(b%1440)).join(',')")
        ok('default flight hours: morning 08–12, noon 16–20, night 20–24', dflt=='morning 08:00-12:00,noon 16:00-20:00,night 20:00-00:00', dflt)
        al=await ev(page,"Object.keys(FLIGHT_ALARM).map(k=>k+' '+minToHM(FLIGHT_ALARM[k])).join(',')")
        ok('default flight alarms: 06:00, 14:00, 17:30', al=='morning 06:00,noon 14:00,night 17:30', al)
        await ev(page,"()=>{ prefs.openDetails='popup'; savePrefs(); }")   # the day panel path (new-tab path: tests/detail.py)
        await fl.click(); await page.wait_for_timeout(300)
        await page.fill('#drawer [data-fx=dep]','13:40'); await page.dispatch_event('#drawer [data-fx=dep]','change'); await page.wait_for_timeout(200)
        await page.fill('#drawer [data-fx=arr]','16:05'); await page.dispatch_event('#drawer [data-fx=arr]','change'); await saved(page)
        f=server()['data']['days']['2026-11-09']['flights'][0]
        ok('flight times editable and saved', f.get('dep')=='13:40' and f.get('arr')=='16:05', f)
        ok('grid shows the edited flight time', '13:40–16:05' in await page.locator('.tg-col[data-col="2026-11-09"] .ev.flt').inner_text())
        await page.click('#drawer [data-freset]'); await saved(page)
        await ev(page,"()=>{ prefs.openDetails='tab'; savePrefs(); }")
        f=server()['data']['days']['2026-11-09']['flights'][0]
        ok('reset flight to default hours', 'dep' not in f and 'arr' not in f, f)
        await page.keyboard.press('Escape')
        ok('morning leave band drawn 08:30–13:00 only', await page.locator('.tg-col[data-col="2026-11-09"] .tg-leave').count()==1 and 'morning' in (await page.locator('.tg-col[data-col="2026-11-09"] .tg-leave').inner_text()).lower())
        hl=await ev(page,"""()=>{ const k='2026-11-09'; const w=allowedWindows(k,'work').map(([a,b])=>minToHM(a)+'-'+minToHM(b));
            const c=(a,b)=>{ const e=newEvent({title:'chk', start:k+'T'+a, end:k+'T'+b, tz:BROWSER_TZ}); const o=expandEvent(e,-1e14,1e14); return [...conflictsIn(o).values()].flat().filter(x=>x.includes('Leave')); };
            const t1=(a)=>{ const x=newEvent({start:'2026-11-09T'+a,end:'2026-11-09T'+a,tz:BROWSER_TZ}); return !!offAt(zonedToMs(x.start,BROWSER_TZ)); };
            return {wins:w, morningConf:c('10:00','11:00'), eveningConf:c('14:00','15:00'), mutedMorning:t1('09:00'), mutedEvening:t1('14:00')}; }""")
        ok('morning leave: free slots only in the evening half', hl['wins']==['13:00-17:30'], hl['wins'])
        ok('morning leave: warning for a 10:00 work event, none for 14:00', len(hl['morningConf'])==1 and 'morning, 08:30–13:00' in hl['morningConf'][0] and not hl['eveningConf'], hl)
        ok('reminders muted only during the leave half', hl['mutedMorning'] and not hl['mutedEvening'])
        await ev(page,"()=>{ const m=dayMut('2026-11-09'); delete m.am; m.pm='leave'; changed(); }"); await saved(page)
        hl2=await ev(page,"allowedWindows('2026-11-09','work').map(([a,b])=>minToHM(a)+'-'+minToHM(b))")
        ok('evening leave: free slots only 08:30–13:00', hl2==['08:30-13:00'], hl2)
        await ev(page,"openEventEditor(data.events[0].id)"); await page.wait_for_timeout(200)
        col=await page.eval_on_selector('.dlg [name=title]','e=>getComputedStyle(e).color')
        await page.keyboard.press('Escape')
        await ev(page,"openTaskEditor(data.tasks[0].id)"); await page.wait_for_timeout(200)
        col2=await page.eval_on_selector('.dlg [name=title]','e=>getComputedStyle(e).color')
        await page.keyboard.press('Escape')
        ok('event and task titles shown in red', col=='rgb(201, 42, 42)' and col2=='rgb(201, 42, 42)', (col,col2))
        await page.screenshot(path=SH+'flight-leave-week.png')


        # ---- v2.2: active time zone ----
        await ev(page,"()=>{ closeDialog(); const d=data.days['2026-11-24']||(data.days['2026-11-24']={}); d.flights=[{from:'Athens',to:'Brussels',time:'morning'}]; changed(); }"); await saved(page)
        await page.click('#addBtn'); await page.wait_for_selector('#qa:focus'); await page.keyboard.type('TZ check 25/11 10:00-11:00'); await page.keyboard.press('Enter'); await saved(page)
        await page.click('#addBtn'); await page.wait_for_selector('#qa:focus'); await page.keyboard.type('Late call 25/11 00:30-01:00'); await page.keyboard.press('Enter'); await saved(page)
        e=await ev(page,"data.events.find(e=>e.title==='TZ check')")
        ok('new event entered in the active zone (Athens)', e['tz']=='Europe/Athens' and e['start']=='2026-11-25T10:00', (e['tz'],e['start']))
        await page.click('#tzBtn'); await page.wait_for_timeout(150)
        await page.click('#tzMenu [data-tz="Europe/Brussels"]'); await page.wait_for_timeout(300)
        ok('top bar shows the active zone', 'Brussels' in await page.inner_text('#tzBtn'))
        await ev(page,"()=>{ cursor=fromKey('2026-11-24'); setView('week'); }"); await page.wait_for_timeout(300)
        t=await page.locator('.ev[data-ok=event]', has_text='TZ check').inner_text()
        ok('after switching to Brussels the event shows at 09:00–10:00', '09:00–10:00' in t, t[:40])
        late=await ev(page,"()=>{ const o=occurrences(fromKey('2026-11-24').getTime(), fromKey('2026-11-26').getTime(),{all:true}).find(o=>o.title==='Late call'); return key(new Date(o.s))+' '+hm(o.s); }")
        ok('an event moves to the previous day when the zone changes (00:30 Athens → 23:30 Brussels)', late=='2026-11-24 23:30', late)
        ft=await page.locator('.tg-col[data-col="2026-11-24"] .ev.flt').inner_text()
        ok('flight from Athens 08:00–12:00 shows 07:00–11:00 in Brussels time', '07:00–11:00' in ft, ft[:60])
        e=await ev(page,"data.events.find(e=>e.title==='TZ check')")
        ok('stored time unchanged by switching zone', e['tz']=='Europe/Athens' and e['start']=='2026-11-25T10:00')
        # editor shows active-zone times and saves back without drift
        await ev(page,"openEventEditor(data.events.find(e=>e.title==='TZ check').id)"); await page.wait_for_timeout(200)
        st=await page.input_value('.dlg [name=st]')
        await page.click('.dlg [data-a=save]'); await saved(page)
        e=await ev(page,"data.events.find(e=>e.title==='TZ check')")
        ok('event editor shows 09:00 (Brussels) and saving keeps 10:00 Athens', st=='09:00' and e['start']=='2026-11-25T10:00' and e['end']=='2026-11-25T11:00', (st,e['start']))
        await ev(page,"openEventEditor(data.events.find(e=>e.title==='TZ check').id)"); await page.fill('.dlg [name=st]','14:00'); await page.dispatch_event('.dlg [name=st]','change'); await page.click('.dlg [data-a=save]'); await saved(page)
        e=await ev(page,"data.events.find(e=>e.title==='TZ check')")
        ok('moving it to 14:00 Brussels stores 15:00 Athens', e['start']=='2026-11-25T15:00' and e['end']=='2026-11-25T16:00', (e['start'],e['end']))
        await page.click('#addBtn'); await page.wait_for_selector('#qa:focus'); await page.keyboard.type('Brussels lunch 26/11 12:00-13:00'); await page.keyboard.press('Enter'); await saved(page)
        e=await ev(page,"data.events.find(e=>e.title==='Brussels lunch')")
        ok('new item while in Brussels is entered in Brussels time', e['tz']=='Europe/Brussels' and e['start']=='2026-11-26T12:00')
        # task due time
        await ev(page,"()=>{ const t=newTask({title:'Due TZ', dueDate:'2026-11-27', dueTime:'17:00', tz:'Europe/Athens'}); saveNewItem(t); }"); await saved(page)
        await ev(page,"openTaskEditor(data.tasks.find(t=>t.title==='Due TZ').id)"); await page.wait_for_timeout(200)
        dt=await page.input_value('.dlg [name=dueTime]'); await page.click('.dlg [data-a=save]'); await saved(page)
        t=await ev(page,"data.tasks.find(t=>t.title==='Due TZ')")
        ok('task due 17:00 Athens shows 16:00 in Brussels and saves unchanged', dt=='16:00' and t['dueTime']=='17:00' and t['tz']=='Europe/Athens', (dt,t['dueTime'],t['tz']))
        # flight alarms
        fa=await ev(page,"""()=>{ const from=zonedToTrue('2026-11-24T00:00','Europe/Athens'), to=zonedToTrue('2026-11-25T00:00','Europe/Athens');
            return dueReminders(from,to).filter(x=>x.kind==='flight').map(x=>hm(x.fireAt)+' '+msToZoned(x.fireAt,'Europe/Athens').slice(11)); }""")
        ok('flight alarm at 06:00 Athens (05:00 on the Brussels-time screen)', fa==['05:00 06:00'], fa)
        await ev(page,"()=>{ data.days['2026-11-24'].flights[0].alarm='05:15'; changed(); }"); await saved(page)
        fa=await ev(page,"""()=>{ const from=zonedToTrue('2026-11-24T00:00','Europe/Athens'), to=zonedToTrue('2026-11-25T00:00','Europe/Athens'); return dueReminders(from,to).filter(x=>x.kind==='flight').map(x=>msToZoned(x.fireAt,'Europe/Athens').slice(11)); }""")
        ok('flight alarm editable (05:15)', fa==['05:15'], fa)
        await ev(page,"openDrawer('2026-11-24')"); await page.wait_for_timeout(200)
        await page.uncheck('#drawer [data-falarm]'); await saved(page)
        ok('flight alarm can be switched off', server()['data']['days']['2026-11-24']['flights'][0].get('alarm')=='off')
        await page.keyboard.press('Escape')
        await page.screenshot(path=SH+'tz-brussels-week.png')
        # back to Athens
        await page.click('#tzBtn'); await page.click('#tzMenu [data-tz="Europe/Athens"]'); await page.wait_for_timeout(200)
        t=await page.locator('.ev[data-ok=event]', has_text='Brussels lunch').inner_text()
        ok('back in Athens, the Brussels lunch shows at 13:00–14:00', '13:00–14:00' in t, t[:40])


        # ---- 3-day view ----
        await ev(page,"()=>{ closeDialog(); closeDrawer(); cursor=fromKey('2026-11-03'); }")
        await page.click('#viewBtn'); await page.click('#viewSeg [data-view=\"3day\"]'); await page.wait_for_timeout(300)
        cols=await page.eval_on_selector_all('.tg-col','els=>els.map(e=>e.dataset.col)')
        ok('3-day view shows 3 days from the selected day', cols==['2026-11-03','2026-11-04','2026-11-05'], cols)
        ok('3-day view shows the items of those days', await page.locator('.ev[data-ok=event]', has_text='Meeting WG').count()==1)
        await page.click('#next'); await page.wait_for_timeout(200)
        cols=await page.eval_on_selector_all('.tg-col','els=>els.map(e=>e.dataset.col)')
        ok('next moves by 3 days', cols[0]=='2026-11-06', cols)
        await page.keyboard.press('w'); await page.keyboard.press('3'); await page.wait_for_timeout(200)
        ok('key 3 opens the 3-day view', await ev(page,"view")=='3day' and await page.eval_on_selector_all('.tg-col','els=>els.length')==3)
        await page.screenshot(path=SH+'3day.png')


        # ---- .ics import through the menu (Outlook + Google files) ----
        await ev(page,"()=>{ closeDialog(); closeDialog(); closeDrawer(); }")
        for fn,exp in [(os.path.join(os.path.dirname(os.path.abspath(__file__)),'ics','')+'outlook.ics',3),(os.path.join(os.path.dirname(os.path.abspath(__file__)),'ics','')+'google.ics',3)]:
            n0=await ev(page,"data.events.length")
            await page.click('#menuBtn')
            async with page.expect_file_chooser() as fc: await page.click('#menu [data-act=icsimp]')
            await (await fc.value).set_files(fn); await page.wait_for_timeout(400)
            await page.click('.dlg [data-go]'); await saved(page)
            n1=await ev(page,"data.events.length")
            ok(f'import {fn.split("/")[-1]} via menu adds {exp} events and closes', n1-n0==exp and await page.locator('.dlg').count()==0, n1-n0)
        imp=await ev(page,"""()=>{ const g=n=>data.events.find(e=>e.title===n); const wp=g('Working party'), st=g('Standup'), m=g('Σύσκεψη ομάδας');
            const occ=expandEvent(st,fromKey('2026-11-01').getTime(),fromKey('2026-11-12').getTime()).map(o=>o.occ+(o.ex?'*':''));
            return {wp:[wp.start,wp.end,wp.tz], occ, loc:m.location, tz:m.tz, rem:m.reminders.map(r=>r.offsetMin)}; }""")
        ok('imported times, repeats, exceptions, DURATION and location are right', imp['wp']==['2026-11-18T14:00','2026-11-18T16:00','Europe/Brussels'] and imp['occ']==['2026-11-02','2026-11-09*','2026-11-11'] and imp['loc']=='Room 3, Ministry' and imp['tz']=='Europe/Athens' and imp['rem']==[15], imp)
        await page.click('#menuBtn')
        async with page.expect_file_chooser() as fc: await page.click('#menu [data-act=icsimp]')
        await (await fc.value).set_files(os.path.join(os.path.dirname(os.path.abspath(__file__)),'ics','')+'google.ics'); await page.wait_for_timeout(400)
        dup=await page.locator('.dlg tbody input[data-i]:not(:checked)').count()
        await page.keyboard.press('Escape')
        ok('importing the same file again marks all as duplicates', dup==3, dup)


        # ---- v2.3: no 2nd zone, hide weekends, Markdown notes ----
        await ev(page,"()=>{ closeDialog(); closeDialog(); closeDrawer(); prefs.hideWeekends=false; savePrefs(); cursor=fromKey('2026-11-04'); setView('week'); weekCompact=false; render(); }")
        ok('2nd time zone option removed', await page.locator('[data-tz2]').count()==0 and 'secondTz' not in await ev(page,"JSON.stringify(DEFAULT_PREFS)"))
        await page.check('[data-hidewe]'); await page.wait_for_timeout(200)
        cols=await page.eval_on_selector_all('.tg-col','els=>els.map(e=>e.dataset.col)')
        ok('hide weekends: week grid shows Mon–Fri', cols==['2026-11-02','2026-11-03','2026-11-04','2026-11-05','2026-11-06'], cols)
        await page.click('[data-wc="1"]'); await page.wait_for_timeout(200)
        ok('hide weekends: compact week shows 5 days', await page.locator('.week .wcol').count()==5)
        await page.click('[data-wc="0"]')
        await page.click('#viewBtn'); await page.click('#viewSeg [data-view=\"month\"]'); await page.wait_for_timeout(200)
        mk=await page.eval_on_selector_all('.month .cell','els=>els.map(e=>e.dataset.k)')
        wd=await ev(page,"ks=>ks.filter(k=>isWeekend(fromKey(k))).length", mk)
        ok('hide weekends: month shows only Mon–Fri columns', await page.locator('.month .mhead div').count()==5 and len(mk)%5==0 and wd==0, (len(mk),wd))
        await ev(page,"()=>{ setView('3day'); cursor=fromKey('2026-11-05'); render(); }"); await page.wait_for_timeout(200)
        c3=await page.eval_on_selector_all('.tg-col','els=>els.map(e=>e.dataset.col)')
        await page.click('#next'); await page.wait_for_timeout(150)
        c3n=await page.eval_on_selector_all('.tg-col','els=>els.map(e=>e.dataset.col)')
        ok('hide weekends: 3 days skips Sat/Sun (Thu, Fri, Mon) and › moves 3 working days', c3==['2026-11-05','2026-11-06','2026-11-09'] and c3n==['2026-11-10','2026-11-11','2026-11-12'], (c3,c3n))
        await page.uncheck('[data-hidewe]'); await page.wait_for_timeout(150)
        ok('weekends shown again when unticked', await page.eval_on_selector_all('.tg-col','els=>els.map(e=>e.dataset.col)')==['2026-11-10','2026-11-11','2026-11-12'] and await ev(page,"!prefs.hideWeekends"))
        # Markdown rendering (CommonMark, safe)
        r=await ev(page,"""()=>md('# Title\\n\\nSome **bold** and *it* text with `code`.\\n\\n- one\\n- two\\n\\n1. first\\n2. second\\n\\n> quote\\n\\n[site](https://example.com) and https://example.org\\n\\n<script>alert(1)</script><img src=x onerror=alert(2)>\\n\\n[bad](javascript:alert(3))\\n\\n| a | b |\\n|---|---|\\n| 1 | 2 |\\n\\n~~gone~~')""")
        checks={'h1':'<h1>Title</h1>' in r,'bold':'<strong>bold</strong>' in r,'em':'<em>it</em>' in r,'code':'<code>code</code>' in r,'ul':'<ul>' in r,'ol':'<ol>' in r,'quote':'<blockquote>' in r,
                'link new tab':'href="https://example.com" target="_blank" rel="noopener noreferrer"' in r,'autolink':'href="https://example.org"' in r,
                'no script':'<script' not in r and '&lt;script&gt;' in r,'no img onerror':'<img' not in r,'no javascript: link':'href="javascript' not in r,'table':'<table>' in r,'strike':'<s>gone</s>' in r}
        ok('Markdown renders CommonMark safely (raw HTML and javascript: links blocked)', all(checks.values()), {k:v for k,v in checks.items() if not v})
        # editor toolbar
        await ev(page,"()=>{ closeDialog(); const t=newTask({title:'MD task', dueDate:'2026-11-12'}); saveNewItem(t); openTaskEditor(t.id); }"); await page.wait_for_timeout(300)
        ta='.dlg .mde textarea[name=notes]'
        ok('task notes have a formatting toolbar', await page.locator('.dlg .mde-bar button[data-md]').count()>=12)
        await page.fill(ta,'Plan for the week'); await page.eval_on_selector(ta,'e=>{e.focus(); e.setSelectionRange(9,17);}')
        await page.click('.dlg [data-md=bold]')
        v1=await page.input_value(ta)
        await page.eval_on_selector(ta,'e=>{e.setSelectionRange(e.value.length,e.value.length);}')
        await page.keyboard.press('Enter'); await page.keyboard.press('Enter')
        await page.click('.dlg [data-md=ul]'); await page.keyboard.type('first item'); await page.keyboard.press('Enter'); await page.keyboard.type('second item')
        await page.keyboard.press('Enter'); await page.keyboard.press('Enter'); await page.keyboard.type('Text ')
        await page.keyboard.press('Control+i'); await page.keyboard.type('x')
        v2=await page.input_value(ta)
        ok('toolbar bold wraps selection; lists continue on Enter; Ctrl+I italic', v1=='Plan for **the week**' and '- first item\n- second item\n\nText *x' in v2, (v1,v2))
        await page.click('.dlg [data-mdtab=preview]'); await page.wait_for_timeout(150)
        pv=await page.inner_html('.dlg .mdprev')
        ok('Preview tab renders the notes', '<strong>the week</strong>' in pv and '<li>first item</li>' in pv and await page.locator('.dlg .mde textarea').is_hidden())
        await page.click('.dlg [data-mdtab=write]'); await page.click('.dlg [data-a=save]'); await saved(page)
        n=[t for t in server()['data']['tasks'] if t['title']=='MD task'][0]['notes']
        ok('Markdown notes saved as text', n.startswith('Plan for **the week**') and '- second item' in n, n[:60])
        await ev(page,"()=>{ const t=data.tasks.find(t=>t.title==='MD task'); t.blocks=[{id:uid('b'),start:'2026-11-12T10:00',end:'2026-11-12T11:00',tz:ACTIVE_TZ,done:false}]; changed(); cursor=fromKey('2026-11-12'); setView('day'); }")
        await saved(page); await page.locator('.ev[data-ok=block]', has_text='MD task').click(); await page.wait_for_timeout(200)
        ok('popover shows rendered notes', '<strong>the week</strong>' in await page.inner_html('#pop .md'))
        await page.keyboard.press('Escape')
        await ev(page,"()=>{ const e=newEvent({title:'MD event', start:'2026-11-12T12:00', end:'2026-11-12T13:00', tz:ACTIVE_TZ}); saveNewItem(e); openEventEditor(e.id); }"); await page.wait_for_timeout(250)
        ok('event notes have the same editor', await page.locator('.dlg .mde-bar').count()==1)
        await page.screenshot(path=SH+'md-editor.png'); await page.keyboard.press('Escape')


        # ---- v2.7: legend in a dropdown ----
        await ev(page,"()=>{ closeDialog(); closeDialog(); closeDrawer(); closePopover(); }")
        res={}
        for v in ['day','3day','week','month','agenda']:
            await ev(page,f"setView('{v}')"); await page.wait_for_timeout(150)
            res[v]=(await page.locator('#main > .legend').count(), await page.locator('#main .vtools .legend-dd').count())
        ok('legend row removed; Legend dropdown in the toolbar of every calendar view', all(r==(0,1) for r in res.values()), res)
        await ev(page,"setView('week')"); await page.wait_for_timeout(150)
        ok('legend is closed until clicked', not await page.locator('.legend-menu').is_visible())
        await page.click('.legend-dd > summary'); await page.wait_for_timeout(150)
        txt=await page.inner_text('.legend-menu')
        need=['Workplace','Telework','Telework abroad','Leave','Sick','Public holiday','Location from the last flight','Flight','Task block','Task due','Conflict','Athens + Telework','Athens + Telework abroad']
        ok('legend dropdown lists all items', await page.locator('.legend-menu').is_visible() and all(n in txt for n in need), [n for n in need if n not in txt])
        await page.mouse.click(700,600); await page.wait_for_timeout(150)
        ok('legend closes when clicking elsewhere', not await page.locator('.legend-menu').is_visible())
        ok('board view has no legend', (await ev(page,"()=>{ setView('board'); return document.querySelectorAll('.legend-dd').length; }"))==0)
        await ev(page,"setView('week')")


        # ---- v2.8: Add menu and View menu ----
        await ev(page,"()=>{ closeDialog(); closeDialog(); closeDrawer(); closePopover(); setView('week'); }")
        ok('quick-add box and view buttons are now menus (closed)', await page.locator('#qaWrap').count()==0 and not await page.locator('#addMenu').is_visible() and not await page.locator('#viewSeg').is_visible())
        await page.click('#viewBtn'); await page.click('#viewSeg [data-view="month"]'); await page.wait_for_timeout(150)
        ok('View menu switches view and shows its name', await ev(page,"view")=='month' and (await page.inner_text('#viewLbl'))=='Month' and not await page.locator('#viewSeg').is_visible())
        ok('full title fits in the top bar', await page.eval_on_selector('#title','e=>e.scrollWidth<=e.clientWidth'))
        await page.click('#addBtn'); await page.wait_for_timeout(150)
        items=await page.eval_on_selector_all('#addMenu [data-new]','els=>els.map(e=>e.dataset.new+(e.dataset.cat?":"+e.dataset.cat:""))')
        ok('Add menu: quick add field focused, all add options listed', await page.evaluate("document.activeElement.id")=='qa' and items==['event:meeting','event:appointment','event:deadline','event:travel','event:social','event:other','task','project','flight','day','template','plan','icsimp','csvimp'], items)
        await page.keyboard.type('Menu quick add 9/11 10:00-11:00'); await page.wait_for_timeout(150)
        ok('quick-add preview shows inside the menu', await page.locator('#addMenu #qaPrev.open').is_visible())
        await page.keyboard.press('Enter'); await saved(page)
        ok('Enter creates it and closes the menu', any(e['title']=='Menu quick add' for e in server()['data']['events']) and not await page.locator('#addMenu').is_visible())
        await page.click('#addBtn'); await page.click('#addMenu [data-cat=travel]'); await page.wait_for_timeout(250)
        ok('event types preselect the type (Travel)', await page.input_value('.dlg [name=category]')=='travel')
        await page.keyboard.press('Escape')
        await page.click('#addBtn'); await page.click('#addMenu [data-new=flight]'); await page.wait_for_timeout(200)
        await page.fill('.dlg [name=d]','2026-11-19'); await page.fill('.dlg [name=from]','Athens'); await page.fill('.dlg [name=to]','Brussels'); await page.check('.dlg [name=t][value=noon]'); await page.fill('.dlg [name=note]','SN 3402')
        await page.click('.dlg [data-go]'); await saved(page)
        f=server()['data']['days'].get('2026-11-19',{}).get('flights',[])
        ok('Add → Flight adds a flight', f and f[-1]=={'from':'Athens','to':'Brussels','time':'noon','note':'SN 3402'}, f)
        await page.click('#addBtn'); await page.click('#addMenu [data-new=day]'); await page.wait_for_timeout(250)
        ok('Add → Work status opens the day panel', await page.locator('#drawer.open').count()==1)
        await page.keyboard.press('Escape')
        await page.keyboard.press('q'); await page.wait_for_timeout(100)
        ok('Q opens the Add menu with the quick-add field focused', await page.locator('#addMenu').is_visible() and await page.evaluate("document.activeElement.id")=='qa')
        await page.keyboard.press('Escape'); await page.wait_for_timeout(100)
        ok('Escape closes the menu', not await page.locator('#addMenu').is_visible())
        await ev(page,"setView('week')")

        # ---- nothing marked hidden is shown ----
        shown=await page.evaluate("[...document.querySelectorAll('[hidden]')].filter(e=>e.getClientRects().length>0).map(e=>e.id||e.className||e.tagName)")
        ok('no element marked hidden is visible', shown==[], shown)
        # ---- performance with 5,000 items ----
        perf=await ev(page,"""()=>{ const base=fromKey('2026-11-02'); for(let i=0;i<5000;i++){ const d=addDays(base,(i%120)-60), k=key(d), h=8+(i%10); if(i%2) data.events.push(touch(newEvent({title:'Load '+i, start:k+'T'+String(h).padStart(2,'0')+':00', end:k+'T'+String(h).padStart(2,'0')+':45', tz:'Europe/Athens'}))); else data.tasks.push(touch(newTask({title:'Load task '+i, dueDate:k, blocks:[{id:uid('b'),start:k+'T'+String(h).padStart(2,'0')+':00',end:k+'T'+String(h).padStart(2,'0')+':30',tz:'Europe/Athens',done:false}]}))); }
            invalidateCaches(); changeSeq++; cursor=fromKey('2026-11-02'); view='week'; weekCompact=false;
            const t0=performance.now(); render(); const t1=performance.now(); renderAll(); const t2=performance.now();
            const s=JSON.stringify(data).length; return {week:Math.round(t1-t0), all:Math.round(t2-t1), kb:Math.round(s/1024), items:data.events.length+data.tasks.length}; }""")
        ok('week view with 5,000 items renders under 600 ms (spec target 200 ms for 150 items in view)', perf['week']<600, perf)
        await page.screenshot(path=SH+'week-load.png')

        print('ERRORS:', errs[:30])
        print(f"\n{sum(1 for _,x in R if x)}/{len(R)} passed")
        await b.close()

def localdiff(a,b):
    import datetime
    f=lambda s: datetime.datetime.strptime(s,'%Y-%m-%dT%H:%M')
    return int((f(b)-f(a)).total_seconds()//60)
def daybefore(k):
    import datetime
    return (datetime.date.fromisoformat(k)-datetime.timedelta(days=1)).isoformat()
asyncio.run(main())
