import asyncio
from playwright.async_api import async_playwright
BASE='http://127.0.0.1:8099/'
async def run(remember):
    async with async_playwright() as pw:
        b=await pw.chromium.launch(); c=await b.new_context(); p=await c.new_page()
        await p.goto(BASE); await p.fill('#lUser','dkouis'); await p.fill('#lPass','secret123')
        if remember: await p.check('#lRemember')
        await p.click('#lBtn'); await p.wait_for_timeout(2000)
        locked=await p.evaluate("document.body.classList.contains('locked')"); err=await p.inner_text('#lErr')
        cookies=[ck for ck in await c.cookies() if ck['name']=='WTCSESS']
        print(f"remember={remember}: signed in={not locked} error='{err}' cookie expires={'session' if cookies[0]['expires']==-1 else 'in %.0f days'%((cookies[0]['expires']-__import__('time').time())/86400)}")
        await p.evaluate("()=>{ dirty=false; }"); await p.evaluate("signOut()"); await p.wait_for_timeout(1500)
        print('   after sign out: login form shown =', await p.locator('#loginForm').is_visible(), '| cookies left:', [ck['name'] for ck in await c.cookies()])
        await b.close()
async def main():
    await run(True); await run(False)
asyncio.run(main())
