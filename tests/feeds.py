"""Calendar links (subscriptions). Usage: WTC_SRV=<test server folder> python3 tests/feeds.py
(fresh tests/seed.py data; accounts dkouis/secret123, viewer/viewer123; pip install icalendar)"""
import asyncio, os, json, datetime, re, urllib.request, urllib.error
from playwright.async_api import async_playwright
from icalendar import Calendar
BASE='http://127.0.0.1:8099/'; SRV=os.environ.get('WTC_SRV','./testsrv')
R=[]
def ok(n,c,i=''): R.append(bool(c)); print(('PASS ' if c else 'FAIL ')+n+(f'  [{i}]' if i!='' else ''), flush=True)
def guarded(path):
    s=open(path,'rb').read(); return s[s.index(b'\n')+1:]
def get(url, ua='iOS/26.1 (23B85) dataaccessd/1.0'):
    try:
        r=urllib.request.urlopen(urllib.request.Request(url, headers={'User-Agent':ua}), timeout=10); return r.status, r.headers.get('Content-Type',''), r.read().decode('utf-8')
    except urllib.error.HTTPError as e: return e.code, e.headers.get('Content-Type',''), e.read().decode('utf-8','replace')
def events(txt):
    cal=Calendar.from_ical(txt); return cal, [c for c in cal.walk('VEVENT')]
def titles(txt): return [str(e.get('SUMMARY')) for e in events(txt)[1]]
async def saved(p):
    for _ in range(80):
        if (await p.inner_text('#saveState')).startswith('Saved'): return
        await p.wait_for_timeout(150)
async def synced(p):
    await saved(p)
    await p.evaluate("()=>new Promise(r=>{ clearTimeout(_feedTimer); const go=async()=>{ if(_feedBusy) return setTimeout(go,100); await feedSync(false); r(); }; go(); })")
async def ctx(b):
    c=await b.new_context(viewport={'width':1300,'height':900}, timezone_id='Europe/Athens', locale='en-GB')
    await c.clock.install(time=datetime.datetime(2026,11,1,12,0,tzinfo=datetime.timezone.utc)); return c
async def login(p,u='dkouis',pw='secret123'):
    await p.goto(BASE); await p.fill('#lUser',u); await p.fill('#lPass',pw); await p.click('#lBtn'); await p.wait_for_selector('body:not(.locked)'); await p.wait_for_timeout(700)
    for _ in range(3):
        if await p.locator('.dlg').count(): await p.keyboard.press('Escape'); await p.wait_for_timeout(150)

async def main():
    if os.path.exists(SRV+'/data/throttle.php'): os.remove(SRV+'/data/throttle.php')
    for f in ('feeds.php','feedparts.php'):
        if os.path.exists(SRV+'/data/'+f): os.remove(SRV+'/data/'+f)
    async with async_playwright() as pw:
        b=await pw.chromium.launch(); c=await ctx(b); m=await c.new_page(); errs=[]
        m.on('pageerror', lambda e: errs.append(str(e)))
        await login(m)
        await m.evaluate("""()=>{
          const a=newEvent({title:'Συνάντηση εργασίας, Βρυξέλλες; long title '+'x'.repeat(60), start:'2026-11-03T10:00', end:'2026-11-03T11:30', tz:'Europe/Brussels', location:'Justus Lipsius', notes:'**Agenda**\\n\\n1. Item'}); a.reminders=[{offsetMin:15,channel:'popup'}]; saveNewItem(a);
          const r=newEvent({title:'Weekly team call', start:'2026-11-02T09:00', end:'2026-11-02T09:30', tz:'Europe/Athens'}); r.rrule={freq:'weekly', interval:1, byDay:[0]}; r.exceptions={'2026-11-09':{deleted:true}, '2026-11-16':{start:'2026-11-16T10:00', end:'2026-11-16T10:30'}}; saveNewItem(r);
          const ad=newEvent({title:'Conference day', start:'2026-11-05', end:'2026-11-05', allDay:true}); delete ad.tz; ad.reminders=[{offsetMin:0}]; saveNewItem(ad);
          const old=newEvent({title:'Old event', start:'2026-06-01T10:00', end:'2026-06-01T11:00'}); saveNewItem(old);
          const t=newTask({title:'Draft the report', dueDate:'2026-11-06', priority:2}); t.blocks=[{id:uid('b'), start:'2026-11-04T14:00', end:'2026-11-04T16:00', tz:'Europe/Athens'}]; t.reminders=[{anchor:'due', offsetMin:0}]; saveNewItem(t);
          const dn=newTask({title:'Done task', dueDate:'2026-11-04', status:'done'}); saveNewItem(dn);
          const d=dayMut('2026-11-10'); d.flights=[{from:'Athens', to:'Brussels', time:'morning', note:'A3 610'}]; d.am='office'; d.pm='office'; d.location='Brussels';
          const d2=dayMut('2026-11-11'); d2.am='leave'; d2.pm='tele';
          changed(); }""")
        await saved(m)

        # ---------- create the standard links
        await m.click('#menuBtn'); await m.click('#menu [data-act=feeds]'); await m.wait_for_selector('.dlg [data-a=std]'); await m.wait_for_selector('.dlg [data-list] p.hint')
        ok('Calendar links window opens (empty)', 'No links yet' in await m.inner_text('.dlg [data-list]'))
        await m.click('.dlg [data-a=std]'); await m.wait_for_selector('.dlg:last-of-type [data-i="1"]')
        ok('confirm explains Personal is not included', 'not included' in await m.locator('.dlg').last.inner_text())
        await m.locator('.dlg').last.locator('[data-i="1"]').click()
        await m.wait_for_selector('.dlg .feed >> nth=2')
        names=await m.locator('.dlg .feedhd b').all_inner_texts()
        ok('links for All + each calendar created', len(names)==3 and names[1:]==['Work','Personal'], names)
        feeds=(await m.evaluate("()=>api('feeds')"))['feeds']
        F={f['name'].split(' – ')[-1]:f for f in feeds}
        ok('secret address is long and random', all(len(f['id'])>=32 for f in feeds))
        urls={k:await m.evaluate(f"()=>feedUrl('{v['id']}')") for k,v in F.items()}
        await synced(m)

        st,ct,txt=get(urls['All calendars'])
        ok('link answers without signing in, as text/calendar', st==200 and ct.startswith('text/calendar'), (st,ct))
        cal,evs=events(txt); T=[str(e.get('SUMMARY')) for e in evs]
        ok('valid iCalendar (parsed by icalendar)', cal.get('X-WR-CALNAME')=='dkouis – All calendars' or 'All calendars' in str(cal.get('X-WR-CALNAME')), cal.get('X-WR-CALNAME'))
        ok('lines folded at 75 bytes', all(len(l.encode())<=75 for l in txt.split('\r\n')), max(len(l.encode()) for l in txt.split('\r\n')))
        gr=[e for e in evs if 'Συνάντηση' in str(e.get('SUMMARY'))]
        ok('Greek title, time zone and location kept', gr and str(gr[0]['DTSTART'].dt)=='2026-11-03 10:00:00+01:00' and str(gr[0].get('LOCATION'))=='Justus Lipsius' and gr[0]['DTSTART'].dt.tzinfo is not None, gr and gr[0]['DTSTART'].dt)
        al=gr[0].walk('VALARM') if gr else []
        ok('reminder included as an alert (15 min before)', al and str(al[0]['TRIGGER'].dt)=='-1 day, 23:45:00')
        ok('VTIMEZONE for Brussels and Athens', 'TZID:Europe/Brussels' in txt and 'TZID:Europe/Athens' in txt and txt.count('BEGIN:VTIMEZONE')==len(set(re.findall(r'TZID:(\S+)\r\n',txt))))
        wk=[e for e in evs if str(e.get('SUMMARY'))=='Weekly team call']
        ok('repeating event: rule, deleted and moved occurrence', len(wk)==2 and 'FREQ=WEEKLY' in txt and 'EXDATE;TZID=Europe/Athens:20261109T090000' in txt and any(e.get('RECURRENCE-ID') for e in wk))
        cd=[e for e in evs if str(e.get('SUMMARY'))=='Conference day'][0]
        ok('all-day event with alert at 09:00 on the day', str(cd['DTSTART'].dt)=='2026-11-05' and str(cd.walk('VALARM')[0]['TRIGGER'].dt)=='9:00:00')
        ok('items older than 60 days are left out', 'Old event' not in T)
        ok('task block and due date shown as events', '☐ Draft the report' in T and '⚑ Draft the report' in T and '⚑ Done task' not in T, [t for t in T if 'task' in t.lower() or 'report' in t])
        fl=[e for e in evs if str(e.get('SUMMARY'))=='✈ Athens → Brussels · A3 610']
        ok('flight with its alarm (06:00 Athens = 04:00 UTC)', fl and str(fl[0]['DTSTART'].dt)=='2026-11-10 06:00:00+00:00' and fl[0].walk('VALARM')[0]['TRIGGER'].dt.isoformat()=='2026-11-10T04:00:00+00:00', fl and fl[0]['DTSTART'].dt)
        ok('notes as plain text', any('Agenda' in str(e.get('DESCRIPTION','')) and '**' not in str(e.get('DESCRIPTION','')) for e in gr))
        st,_,wt=get(urls['Work']); wT=titles(wt)
        ok('Work link: work items, no flights', any('Συνάντηση' in t for t in wT) and not any(t.startswith('✈') for t in wT))
        st,_,pt=get(urls['Personal'])
        ok('Personal link valid but empty until Personal details are allowed', st==200 and titles(pt)==[])

        # ---------- changes reach the links
        await m.evaluate("()=>{ const e=data.events.find(x=>x.title==='Weekly team call'); e.title='Weekly team call (moved)'; touch(e); changed(); }")
        await m.evaluate("()=>{ const e=data.events.find(x=>x.title==='Conference day'); removeItem(e); }")
        await synced(m)
        T2=titles(get(urls['All calendars'])[2])
        ok('edits and deletions appear in the link after saving', 'Weekly team call (moved)' in T2 and 'Conference day' not in T2)
        n=await m.evaluate("async()=>{ let n=0; const o=api; window.api=async(a,b)=>{ if(a==='feed_parts') n++; return o(a,b); }; await feedSync(false); window.api=o; return n; }")
        ok('nothing is sent when nothing changed', n==0, n)

        # ---------- edit a link: status on, alarms off
        fid=F['All calendars']['id']
        await m.evaluate(f"()=>openFeeds()"); await m.wait_for_selector('.dlg .feed')
        await m.locator('.dlg').last.locator('.feed').first.locator('[data-f=edit]').click(); await m.wait_for_selector('.dlg [name=status]')
        await m.locator('.dlg').last.locator('[name=status]').check(); await m.locator('.dlg').last.locator('[name=alarms]').uncheck()
        await m.locator('.dlg').last.locator('[data-a=save]').click(); await m.wait_for_timeout(800); await synced(m)
        txt=get(urls['All calendars'])[2]; T=titles(txt)
        ok('work status and location as all-day items', 'Workplace · 📍 Brussels' in T and 'Leave (morning) · Telework (afternoon)' in T, [t for t in T if 'Work' in t or 'Leave' in t])
        ok('reminders off removes all alerts', 'BEGIN:VALARM' not in txt)

        # ---------- QR code and subscribe link
        await m.locator('.dlg').last.locator('.feed').first.locator('[data-f=qr]').click()
        ok('QR code drawn', await m.locator('.dlg .feedqr svg').count()==1)
        href=await m.locator('.dlg').last.locator('.feed').first.locator('[data-f=webcal]').get_attribute('href')
        ok('subscribe link uses webcal://', href.startswith('webcal://127.0.0.1:8099/api.php?a=ics&k='), href)
        await m.wait_for_timeout(300)
        await m.locator('.dlg').last.locator('[data-close]').last.click()

        # ---------- Personal details
        await m.evaluate("async()=>{ await setPassphrase('correct horse battery', false); changed(); }"); await saved(m)
        await m.evaluate("()=>{ const e=newEvent({title:'Doctor (private)', calendarId:'personal', start:'2026-11-04T18:00', end:'2026-11-04T19:00'}); saveNewItem(e); }"); await synced(m)
        ok('Personal items not in any link by default', 'Doctor (private)' not in get(urls['Personal'])[2] and 'Doctor' not in get(urls['All calendars'])[2])
        pid=F['Personal']['id']
        await m.evaluate(f"()=>{{ openFeeds(); }}"); await m.wait_for_selector('.dlg .feed >> nth=2')
        await m.locator('.dlg').last.locator('.feed').nth(2).locator('[data-f=edit]').click(); await m.wait_for_selector('.dlg [name=personal]')
        await m.locator('.dlg').last.locator('[name=personal]').check()
        ok('warning shown for Personal details', await m.locator('.dlg').last.locator('[data-pw]').is_visible())
        await m.locator('.dlg').last.locator('[data-a=save]').click(); await m.wait_for_timeout(800); await synced(m)
        ok('Personal link shows Personal items once allowed', 'Doctor (private)' in titles(get(urls['Personal'])[2]))
        ok('…but not the All link (not allowed there)', 'Doctor (private)' not in titles(get(urls['All calendars'])[2]))
        ok('calendar.php still encrypted', 'Doctor (private)' not in guarded(SRV+'/data/calendar.php').decode())
        await m.evaluate(f"async()=>{{ const f=FEEDS.find(x=>x.id==='{pid}'); await api('feed_save',{{...f, personal:false}}); FEEDS=null; await feedSync(true); }}")
        ok('switching it off removes Personal details from the server', 'Doctor' not in guarded(SRV+'/data/feedparts.php').decode())

        # ---------- new address, delete, bad links
        old=urls['Work']; wid=F['Work']['id']
        new=(await m.evaluate(f"()=>api('feed_renew',{{id:'{wid}'}})"))['id']
        ok('New address: old link stops, new one works', get(old)[0]==404 and get(await m.evaluate(f"()=>feedUrl('{new}')"))[0]==200)
        await m.evaluate(f"()=>api('feed_delete',{{id:'{new}'}})")
        ok('deleted link answers 404', get(await m.evaluate(f"()=>feedUrl('{new}')"))[0]==404)
        ok('made-up link answers 404', get(BASE+'api.php?a=ics&k=AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA')[0]==404 and get(BASE+'api.php?a=ics&k=../users')[0]==404)
        fl=(await m.evaluate("()=>api('feeds')"))['feeds']
        ok('last use and app recorded', any(f.get('used') and 'iOS' in f.get('ua','') for f in fl))
        await m.evaluate("()=>{ openFeeds(); }"); await m.wait_for_selector('.dlg .feed')
        ok('window shows when an iPhone last read the link', 'iPhone/iPad' in await m.locator('.dlg').last.inner_text())

        # ---------- viewer
        v=await (await ctx(b)).new_page(); await login(v,'viewer','viewer123')
        ok('viewer has no Calendar links menu', not await v.locator('#menu [data-act=feeds]').is_visible())
        r=await v.evaluate("async()=>{ try{ await api('feeds'); return 'allowed'; }catch(e){ return e.status; } }")
        ok('viewer cannot list links', r==403, r)
        ok('no page errors', not errs, errs[:3])
        await b.close()
    print(f'\n{sum(R)}/{len(R)} passed'); raise SystemExit(0 if all(R) else 1)
if __name__=='__main__': asyncio.run(main())
