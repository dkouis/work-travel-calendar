"""Edit windows in their own browser tab (events, tasks, projects).
Usage: WTC_SRV=<test server folder> python3 tests/edit.py   (fresh tests/seed.py data; accounts dkouis/secret123, viewer/viewer123)"""
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
async def closed(pg, ms=8000):
    for _ in range(ms//100):
        if pg.is_closed(): return True
        await asyncio.sleep(0.1)
    return pg.is_closed()
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
        await m.evaluate("""()=>{ saveNewItem(newTask({title:'Edit me task', dueDate:'2026-11-06', estimateMin:60}));
            saveNewItem(newEvent({title:'Edit me event', start:'2026-11-03T10:00', end:'2026-11-03T11:00', tz:'Europe/Athens', category:'travel'}));
            saveNewItem(newEvent({title:'Delete me event', start:'2026-11-03T14:00', end:'2026-11-03T15:00', tz:'Europe/Athens'}));
            tpTab='all'; renderTasksPanel(); }""")
        await saved(m)
        # ---- task: click in the tasks panel → edit tab
        async with c.expect_page() as np: await m.locator('.trow', has_text='Edit me task').locator('.tt').click()
        t=await np.value; await t.wait_for_selector('.dlg.page [name=title]', timeout=10000)
        ok('task click opens the editor in a new tab', 'open=edit' in t.url and 'type=task' in t.url and await t.input_value('.dlg.page [name=title]')=='Edit me task', t.url)
        ok('editor fills the page (not a popup over the calendar)', await t.locator('.dlg.page').count()==1 and await t.locator('.dlg:not(.page)').count()==0 and await t.locator('.layout').is_hidden() and (await t.locator('.dlg.page .box').bounding_box())['width']>900)
        ok('tab title names the item', (await t.title()).startswith('Edit: Edit me task'))
        await t.keyboard.press('Escape'); await t.wait_for_timeout(300)
        ok('Escape does not close the edit page', not t.is_closed() and await t.locator('.dlg.page').is_visible())
        await t.fill('.dlg.page [name=title]','Edited task'); await t.click('.dlg.page [data-a=save]')
        ok('Save closes the tab after saving', await closed(t))
        ok('task change is on the server', any(x['title']=='Edited task' for x in server()['tasks']))
        await m.wait_for_timeout(2000)
        ok('calendar tab updates by itself', await m.locator('.trow', has_text='Edited task').count()==1)
        # ---- event: details tab → Edit → edit tab
        await m.evaluate("()=>{ setView('week'); cursor=fromKey('2026-11-02'); render(); $('#tgBody').scrollTop=8*hourPx(); }")
        async with c.expect_page() as np: await m.locator('.ev[data-ok=event]', has_text='Edit me event').click()
        dtab=await np.value; await dtab.wait_for_selector('.dt-title', timeout=10000)
        async with c.expect_page() as np: await dtab.click('[data-a=edit]')
        et=await np.value; await et.wait_for_selector('.dlg.page [name=title]', timeout=10000)
        ok('Edit in a details tab opens the editor in its own tab', 'type=event' in et.url and await et.input_value('.dlg.page [name=st]')=='10:00')
        ok('Markdown notes editor has room (taller)', (await et.locator('.dlg.page .mde textarea').bounding_box())['height']>=230)
        await et.fill('.dlg.page [name=st]','12:00'); await et.dispatch_event('.dlg.page [name=st]','change')
        await et.fill('.dlg.page [name=location]','Airport'); await et.click('.dlg.page [data-a=save]')
        ok('event saved and edit tab closed', await closed(et))
        e=[x for x in server()['events'] if x['title']=='Edit me event'][0]
        ok('event change is on the server', e['start']=='2026-11-03T12:00' and e['end']=='2026-11-03T13:00' and e['location']=='Airport', (e['start'],e['end']))
        await dtab.wait_for_timeout(2000)
        ok('details tab shows the change', 'Airport' in await dtab.inner_text('#detail') and '12:00 – 13:00' in await dtab.inner_text('#detail'))
        n=len(c.pages)
        await dtab.click('[data-a=edit]'); await dtab.wait_for_timeout(500); await dtab.click('[data-a=edit]'); await dtab.wait_for_timeout(800)
        ok('editing the same item again reuses its edit tab', len(c.pages)==n+1, len(c.pages))
        et2=[p for p in c.pages if 'open=edit' in p.url][-1]
        await et2.fill('.dlg.page [name=title]','Changed but not saved'); await et2.click('.dlg.page .dacts [data-close]'); await et2.wait_for_timeout(300)
        ok('Cancel with unsaved changes asks first', await et2.locator('.dlg:not(.page)', has_text='Discard changes?').count()==1)
        await et2.click('.dlg:not(.page) button:has-text("Keep editing")'); await et2.wait_for_timeout(200)
        ok('Keep editing returns to the editor', not et2.is_closed() and await et2.input_value('.dlg.page [name=title]')=='Changed but not saved')
        await et2.click('.dlg.page .dacts [data-close]'); await et2.click('.dlg:not(.page) button:has-text("Discard")')
        ok('Discard closes the tab without saving', await closed(et2) and not any(x['title']=='Changed but not saved' for x in server()['events']))
        await dtab.close()
        # ---- new event from the ＋ New menu (draft handed to the tab)
        await m.bring_to_front()
        async with c.expect_page() as np: await m.click('#addBtn'); await m.click('#addMenu [data-cat=meeting]')
        nt=await np.value; await nt.wait_for_selector('.dlg.page [name=title]', timeout=10000)
        ok('New event opens an empty editor in a new tab', 'draft=' in nt.url and await nt.input_value('.dlg.page [name=title]')=='')
        ok('draft hand-over is removed from browser storage', await nt.evaluate("Object.keys(localStorage).filter(k=>k.startsWith('wtc-draft:')).length")==0)
        await nt.fill('.dlg.page [name=title]','Brand new event'); await nt.click('.dlg.page [data-a=save]')
        ok('new event saved, tab closed', await closed(nt) and any(x['title']=='Brand new event' for x in server()['events']))
        # ---- quick create in the grid → More options
        await m.bring_to_front(); await m.evaluate("()=>{ $('#tgBody').scrollTop=8*hourPx(); }")
        col=m.locator('.tg-col[data-col="2026-11-05"]'); bb=await col.bounding_box(); body=await m.locator('#tgBody').bounding_box()
        hp=await m.evaluate("hourPx()"); top=await m.evaluate("$('#tgBody').scrollTop")
        await m.mouse.click(bb['x']+bb['width']/2, body['y']+(16*hp-top)+3); await m.wait_for_timeout(300)
        await m.fill('#qcT','From the grid')
        async with c.expect_page() as np: await m.click('#pop [data-more]')
        gt=await np.value; await gt.wait_for_selector('.dlg.page [name=title]', timeout=10000)
        ok('grid "More options…" opens the new event in a tab with the chosen time', await gt.input_value('.dlg.page [name=title]')=='From the grid' and await gt.input_value('.dlg.page [name=sd]')=='2026-11-05' and await gt.input_value('.dlg.page [name=st]')=='16:00')
        await gt.click('.dlg.page [data-a=save]'); ok('saved', await closed(gt) and any(x['title']=='From the grid' for x in server()['events']))
        # ---- project
        await m.bring_to_front()
        async with c.expect_page() as np: await m.locator('[data-newproj=work]').click()
        pt=await np.value; await pt.wait_for_selector('.dlg.page [name=name]', timeout=10000)
        await pt.fill('.dlg.page [name=name]','Project from a tab'); await pt.click('.dlg.page [data-a=save]')
        ok('new project created in its own tab', await closed(pt) and any(x['name']=='Project from a tab' for x in server()['projects']))
        await m.wait_for_timeout(2000)
        ok('side panel shows the new project', await m.locator('.srow', has_text='Project from a tab').count()==1)
        # ---- delete from the edit tab, with a cancelled attempt first
        await m.evaluate("()=>{ prefs.openDetails='popup'; savePrefs(); render(); $('#tgBody').scrollTop=12*hourPx(); }")
        await m.locator('.ev[data-ok=event]', has_text='Delete me event').click(); await m.wait_for_timeout(200)
        async with c.expect_page() as np: await m.click('#pop [data-a=edit]')
        xt=await np.value; await xt.wait_for_selector('.dlg.page [data-a=del]', timeout=10000)
        await xt.click('.dlg.page [data-a=del]'); await xt.click('.dlg:not(.page) button:has-text("Cancel")'); await xt.wait_for_timeout(800)
        ok('cancelled Delete brings the editor back', not xt.is_closed() and await xt.locator('.dlg.page').is_visible())
        await xt.click('.dlg.page [data-a=del]'); await xt.click('.dlg:not(.page) button:has-text("Delete")')
        ok('Delete from the edit tab deletes and closes it', await closed(xt) and not any(x['title']=='Delete me event' for x in server()['events']))
        await m.evaluate("()=>{ prefs.openDetails='tab'; savePrefs(); }")
        # ---- setting: popup
        await m.evaluate("()=>{ prefs.openEditors='popup'; savePrefs(); }")
        n=len(c.pages); await m.locator('.trow', has_text='Edited task').locator('.tt').click(); await m.wait_for_timeout(500)
        ok('setting "popup": editor opens over the calendar, no new tab', len(c.pages)==n and await m.locator('.dlg:not(.page) [name=title]').count()==1)
        await m.keyboard.press('Escape'); await m.evaluate("()=>{ prefs.openEditors='tab'; savePrefs(); }")
        # ---- viewer
        cv=await ctx(b); v=await cv.new_page(); await login(v,'viewer','viewer123')
        await v.evaluate("()=>{ tpTab='all'; renderTasksPanel(); }")
        async with cv.expect_page() as np: await v.locator('.trow', has_text='Edited task').locator('.tt').click()
        vt=await np.value; await vt.wait_for_selector('.dlg.page', timeout=10000)
        ok('viewer gets a read-only page in the tab', await vt.locator('.dlg.page [data-a=save]').count()==0 and await vt.locator('.dlg.page [name=title]').is_disabled())
        await vt.click('.dlg.page .dacts [data-close]')
        ok('viewer Close closes the tab', await closed(vt))
        print('errors:', errs)
        print(f'\n{sum(R)}/{len(R)} passed'); await b.close()
asyncio.run(main())
