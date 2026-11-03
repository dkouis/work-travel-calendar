import asyncio, sys
from playwright.async_api import async_playwright
async def main():
    async with async_playwright() as pw:
        b=await pw.chromium.launch(); c=await b.new_context(viewport={'width':1300,'height':900},timezone_id='Europe/Athens'); p=await c.new_page(); errs=[]
        p.on('pageerror',lambda e:errs.append(str(e))); p.on('console',lambda m: errs.append(m.text) if m.type=='error' else None)
        await p.goto('http://127.0.0.1:8099/'); await p.fill('#lUser','dkouis'); await p.fill('#lPass','secret123'); await p.click('#lBtn'); await p.wait_for_selector('body:not(.locked)'); await p.wait_for_timeout(800)
        for _ in range(3):
            if await p.locator('.dlg').count(): await p.keyboard.press('Escape'); await p.wait_for_timeout(150)
        for f in sys.argv[1:]:
            n0=await p.evaluate("data.events.length+data.tasks.length")
            await p.click('#menuBtn')
            async with p.expect_file_chooser() as fc: await p.click('#menu [data-act=icsimp]')
            ch=await fc.value; await ch.set_files(f); await p.wait_for_timeout(600)
            rows=await p.locator('.dlg tbody tr').count()
            await p.click('.dlg [data-go]'); await p.wait_for_timeout(800)
            n1=await p.evaluate("data.events.length+data.tasks.length")
            print(f, 'rows', rows, 'added', n1-n0, 'dialog still open', await p.locator('.dlg').count()>0)
        r=await p.evaluate('''()=>{ const f=fromKey('2026-11-01').getTime(), t=fromKey('2027-01-01').getTime(), o=[];
          for(const e of data.events.filter(e=>e.uid)) for(const x of expandEvent(e,f,t)) o.push(x.title+' | '+(x.allDay?x.dateKey+' all day':key(new Date(x.s))+' '+hm(x.s)+'-'+hm(x.e)));
          return o.slice(0,12); }''')
        print('\n'.join(r))
        loc=await p.evaluate("(data.events.find(e=>e.title==='Σύσκεψη ομάδας')||{}).location"); print('location:',loc, '| reminders:', await p.evaluate("JSON.stringify((data.events.find(e=>e.title==='Σύσκεψη ομάδας')||{}).reminders)"))
        await p.wait_for_timeout(1500); print('saved:', await p.inner_text('#saveState'))
        print('errors:', errs)
        await b.close()
asyncio.run(main())
