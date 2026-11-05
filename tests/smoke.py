import os
import asyncio, json, re, sys
from playwright.async_api import async_playwright
BASE='http://127.0.0.1:8099/'
SH=os.path.join(os.path.dirname(os.path.abspath(__file__)),'shots')+'/'
os.makedirs(SH, exist_ok=True)
async def login(page,u,p):
    await page.goto(BASE); await page.fill('#lUser',u); await page.fill('#lPass',p); await page.click('#lBtn')
    await page.wait_for_selector('body:not(.locked)',timeout=8000)
async def main():
    async with async_playwright() as pw:
        b=await pw.chromium.launch()
        ctx=await b.new_context(viewport={'width':1440,'height':900}, timezone_id='Europe/Athens', locale='en-GB')
        page=await ctx.new_page(); errs=[]
        page.on('pageerror',lambda e:errs.append('PAGEERR '+str(e)))
        page.on('console',lambda m: errs.append('CONSOLE '+m.text) if m.type=='error' else None)
        await login(page,'dkouis','secret123')
        await page.wait_for_timeout(2500)
        print('save state:', await page.inner_text('#saveState'))
        await page.screenshot(path=SH+'smoke-digest.png')
        print('dialog:', await page.locator('.dlg h3').first.inner_text() if await page.locator('.dlg').count() else None)
        await page.keyboard.press('Escape'); await page.wait_for_timeout(300)
        for v in ['week','month','day','agenda','board']:
            await page.click('#viewBtn'); await page.click(f'#viewSeg [data-view="{v}"]'); await page.wait_for_timeout(400)
            await page.screenshot(path=SH+f'smoke-{v}.png')
        print('ERRORS:', errs[:20])
        await b.close()
asyncio.run(main())
