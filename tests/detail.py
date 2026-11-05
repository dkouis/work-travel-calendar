"""Details in a new browser tab (events, projects, flights).
Usage: WTC_SRV=<test server folder> python3 tests/detail.py   (accounts dkouis/secret123, viewer/viewer123)"""
import asyncio, os, json, datetime
from playwright.async_api import async_playwright
BASE='http://127.0.0.1:8099/'; SRV=os.environ.get('WTC_SRV','./testsrv')
R=[]
def ok(n,c,i=''): R.append(bool(c)); print(('PASS ' if c else 'FAIL ')+n+(f'  [{i}]' if i!='' else ''), flush=True)
def server():
    s=open(SRV+'/data/calendar.php').read(); return json.loads(s[s.index('\n')+1:])['data']
async def saved(p):
    for _ in range(60):
        if (await p.inner_text('#saveState')).startswith('Saved'): return
        await p.wait_for_timeout(150)
async def ctx(b):
    c=await b.new_context(viewport={'width':1360,'height':900}, timezone_id='Europe/Athens', locale='en-GB')
    await c.clock.install(time=datetime.datetime(2026,11,1,12,0,tzinfo=datetime.timezone.utc)); return c
async def login(p,u='dkouis',pw='secret123'):
    await p.goto(BASE); await p.fill('#lUser',u); await p.fill('#lPass',pw); await p.click('#lBtn'); await p.wait_for_selector('body:not(.locked)'); await p.wait_for_timeout(700)
    for _ in range(3):
        if await p.locator('.dlg').count(): await p.keyboard.press('Escape'); await p.wait_for_timeout(150)
async def main():
    if os.path.exists(SRV+'/data/throttle.php'): os.remove(SRV+'/data/throttle.php')
    async with async_playwright() as pw:
        b=await pw.chromium.launch(); c=await ctx(b); m=await c.new_page(); errs=[]
        c.on('page', lambda pg: pg.on('pageerror', lambda e: errs.append('TAB '+str(e))))
        m.on('pageerror', lambda e: errs.append('MAIN '+str(e)))
        await login(m)
        await m.evaluate("""()=>{ const p=newProject({name:'Tab Project', color:'#7048e8'}); saveNewItem(p);
            saveNewItem(newEvent({title:'Tab meeting', start:'2026-11-03T10:00', end:'2026-11-03T11:00', tz:'Europe/Athens', projectId:p.id, location:'Room 5', category:'appointment', notes:'## Agenda\\n\\n- **one**\\n- two'}));
            saveNewItem(newEvent({title:'Weekly tab', start:'2026-11-02T15:00', end:'2026-11-02T16:00', tz:'Europe/Athens', category:'social', rrule:{freq:'weekly',interval:1,byDay:[0]}}));
            const d=dayMut('2026-11-04'); d.flights=[{from:'Athens',to:'Brussels',time:'morning',note:'A3 610'}]; changed();
            cursor=fromKey('2026-11-02'); setView('week'); $('#tgBody').scrollTop=7*hourPx(); }""")
        await saved(m)
        ok('main tab is named for reuse', await m.evaluate("window.name")=='wtc-main')
        # ---- event from the grid
        async with c.expect_page() as np: await m.locator('.ev[data-ok=event]', has_text='Tab meeting').click()
        t=await np.value; await t.wait_for_selector('body:not(.locked) .dt-title', timeout=10000)
        ok('clicking an event opens a new tab with its details', 'open=event' in t.url and await t.inner_text('.dt-title')=='Tab meeting' and await t.title()=='Tab meeting · Work & Travel', t.url)
        col=await t.eval_on_selector('.dt-title','e=>getComputedStyle(e).color')
        body=await t.inner_text('#detail')
        ok('details show type, time, project, location and Markdown notes; title red', 'Appointment' in body and '10:00 – 11:00' in body and 'Tab Project' in body and 'Room 5' in body and await t.locator('.dt-notes strong').inner_text()=='one' and col=='rgb(201, 42, 42)', col)
        ok('details tab shows no daily-agenda popup and no calendar', await t.locator('.dlg').count()==0 and await t.locator('.layout').is_hidden())
        n0=len(c.pages); await m.bring_to_front()
        await m.locator('.ev[data-ok=event]', has_text='Tab meeting').click(); await m.wait_for_timeout(800)
        ok('clicking the same event again reuses its tab', len(c.pages)==n0, len(c.pages))
        # ---- edit in the tab → calendar updates
        await t.bring_to_front(); await t.click('[data-a=edit]'); await t.fill('.dlg [name=title]','Tab meeting edited'); await t.click('.dlg [data-a=save]'); await saved(t)
        await m.wait_for_timeout(2500)
        ok('edit in the details tab: calendar tab updates by itself', await m.locator('.ev[data-ok=event]', has_text='Tab meeting edited').count()==1 and await t.inner_text('.dt-title')=='Tab meeting edited')
        # ---- edit in the calendar → tab updates
        await m.evaluate("()=>{ const e=data.events.find(e=>e.title==='Tab meeting edited'); e.location='Room 9'; touch(e); changed(); }"); await saved(m)
        await t.wait_for_timeout(2500)
        ok('edit in the calendar: details tab updates by itself', 'Room 9' in await t.inner_text('#detail'))
        # ---- show in calendar
        await m.evaluate("()=>{ setView('month'); }")
        await t.click('[data-a=cal]'); await m.wait_for_timeout(800)
        ok('"Show in calendar" moves the calendar tab to that day', await m.evaluate("view")=='day' and await m.evaluate("key(cursor)")=='2026-11-03')
        # ---- repeating event occurrence
        await m.evaluate("()=>{ cursor=fromKey('2026-11-09'); setView('week'); $('#tgBody').scrollTop=12*hourPx(); }")
        async with c.expect_page() as np2: await m.locator('.ev[data-ok=event]', has_text='Weekly tab').click()
        t2=await np2.value; await t2.wait_for_selector('.dt-title', timeout=10000)
        b2=await t2.inner_text('#detail')
        ok('repeating event: tab shows that occurrence (9 Nov) and the rule', 'occ=2026-11-09' in t2.url and 'Monday, 9 November 2026' in b2 and 'Weekly' in b2 and 'Social' in b2, t2.url)
        # ---- project from the side panel
        await m.bring_to_front()
        async with c.expect_page() as np3: await m.locator('.srow', has_text='Tab Project').locator('[data-popen]').click()
        t3=await np3.value; await t3.wait_for_selector('#detail h2', timeout=10000)
        ok('clicking a project opens its page in a new tab', 'open=project' in t3.url and 'Tab Project' in await t3.inner_text('#detail h2'))
        await t3.click('[data-ptab=stats]'); await t3.wait_for_timeout(200)
        ok('project tab works (Stats)', await t3.locator('.kpi').count()>=6)
        await t3.click('[data-ptab=timeline]'); await t3.wait_for_timeout(200)
        n3=len(c.pages); await t3.locator('.agi', has_text='Tab meeting edited').click(); await t3.wait_for_timeout(800)
        ok('event inside the project tab goes to that event\'s tab (already open, so reused)', len(c.pages)==n3 and await t.inner_text('.dt-title')=='Tab meeting edited')
        # ---- flight
        await m.bring_to_front(); await m.evaluate("()=>{ setView('week'); cursor=fromKey('2026-11-02'); render(); $('#tgBody').scrollTop=7*hourPx(); }")
        async with c.expect_page() as np5: await m.locator('.tg-col[data-col="2026-11-04"] .ev.flt').click()
        t5=await np5.value; await t5.wait_for_selector('.dt-title', timeout=10000)
        ok('clicking a flight opens its details in a new tab', 'open=flight' in t5.url and 'Athens → Brussels' in await t5.inner_text('.dt-title') and '08:00' in await t5.inner_text('#detail') and 'A3 610' in await t5.inner_text('#detail'))
        await t5.fill('[data-fx=dep]','09:15'); await t5.dispatch_event('[data-fx=dep]','change'); await saved(t5)
        f=server()['days']['2026-11-04']['flights'][0]
        ok('flight edited in its tab is saved', f.get('dep')=='09:15', f)
        await m.wait_for_timeout(2500)
        ok('calendar tab shows the new flight time', '09:15' in await m.locator('.tg-col[data-col="2026-11-04"] .ev.flt').inner_text())
        await m.evaluate("setView('month')"); await m.wait_for_timeout(200)
        n1=len(c.pages)
        await m.locator('.cell[data-k="2026-11-04"] [data-flk]').first.click(); await m.wait_for_timeout(800)
        ok('flight chip in Month opens (reuses) the flight tab', len(c.pages)==n1)
        # ---- delete from the tab
        await t2.bring_to_front(); await t2.click('[data-a=del]'); await t2.click('.dlg button:has-text("Only this one")'); await saved(t2)
        ok('deleting the shown occurrence: tab says it is not available', 'not available' in await t2.inner_text('#detail'))
        # ---- popup setting
        await m.bring_to_front(); await m.evaluate("()=>{ prefs.openDetails='popup'; savePrefs(); setView('week'); cursor=fromKey('2026-11-02'); render(); $('#tgBody').scrollTop=7*hourPx(); }")
        n2=len(c.pages); await m.locator('.ev[data-ok=event]', has_text='Tab meeting edited').click(); await m.wait_for_timeout(600)
        ok('setting "popup": click shows the popup, no new tab', await m.locator('#pop').count()==1 and len(c.pages)==n2)
        await m.evaluate("()=>{ closePopover(); prefs.openDetails='tab'; savePrefs(); }")
        # ---- direct link before signing in, as viewer
        eid=await m.evaluate("data.events.find(e=>e.title==='Tab meeting edited').id")
        cv=await ctx(b); v=await cv.new_page(); await v.goto(BASE+'#open=event&id='+eid)
        await v.fill('#lUser','viewer'); await v.fill('#lPass','viewer123'); await v.click('#lBtn'); await v.wait_for_selector('.dt-title', timeout=10000)
        ok('details link opened before sign-in shows the details after signing in', await v.inner_text('.dt-title')=='Tab meeting edited')
        ok('viewer sees details without editing buttons', await v.locator('[data-a=edit],[data-a=del]').count()==0 and await v.locator('[data-a=cal]').count()==1)
        print('errors:', errs)
        print(f'\n{sum(R)}/{len(R)} passed'); await b.close()
asyncio.run(main())
