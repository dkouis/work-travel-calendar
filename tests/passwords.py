"""Password reset by file, forced change at sign-in, change-own-password, accounts flag.
Usage: WTC_SRV=<test server folder> python3 tests/passwords.py   (accounts dkouis/secret123, viewer/viewer123)"""
import asyncio, os, json, glob
from playwright.async_api import async_playwright
BASE='http://127.0.0.1:8099/'; SRV=os.environ.get('WTC_SRV','./testsrv'); D=SRV+'/data/'
R=[]
def ok(n,c,i=''): R.append(c); print(('PASS ' if c else 'FAIL ')+n+(f'  [{i}]' if i!='' else ''), flush=True)
def users(): s=open(D+'users.php').read(); return json.loads(s[s.index('\n')+1:])['users']
async def login(p,u,pw,expect_ok=True):
    await p.goto(BASE); await p.wait_for_selector('#lUser'); await p.fill('#lUser',u); await p.fill('#lPass',pw); await p.click('#lBtn'); await p.wait_for_timeout(900)
    return not await p.evaluate("document.body.classList.contains('locked')")
async def main():
    async with async_playwright() as pw:
        b=await pw.chromium.launch()
        for f in ('throttle.php','reset-passwords.txt'):
            if os.path.exists(D+f): os.remove(D+f)
        # a signed-in session that must be ended by the reset
        c0=await b.new_context(); p0=await c0.new_page(); ok('signs in before reset', await login(p0,'dkouis','secret123'))
        # Windows Notepad style file: BOM + CRLF, a comment, a bad line, a new editor, a too-short password
        open(D+'reset-passwords.txt','w',encoding='utf-8-sig',newline='\r\n').write('# reset\nDKOUIS  Temp-2026\nviewer short\nghost\nanna  Anna-temp1  editor\nbob pass123 admin\n')
        c=await b.new_context(); p=await c.new_page()
        await p.goto(BASE); await p.wait_for_timeout(800)     # any visit applies the file
        ok('reset file is deleted after use', not os.path.exists(D+'reset-passwords.txt'))
        log=open(D+'reset-passwords-log.txt').read()
        ok('log lists results without passwords', 'RESET    dkouis (editor)' in log and 'CREATED  anna (editor)' in log and 'SKIPPED  viewer' in log and 'SKIPPED  ghost' in log and 'SKIPPED  bob' in log and 'Temp-2026' not in log and 'Anna-temp1' not in log, log.strip().replace('\n',' | ')[:300])
        raw=''.join(open(f).read() for f in glob.glob(D+'*.php'))+''.join(open(f).read() for f in glob.glob(D+'*.txt'))
        ok('no plain-text password left in the data folder', 'Temp-2026' not in raw and 'Anna-temp1' not in raw)
        u={x['user']:x for x in users()}
        ok('dkouis and anna must change; viewer unchanged', u['dkouis'].get('must_change') and u['anna'].get('must_change') and u['anna']['role']=='editor' and not u['viewer'].get('must_change'))
        await p0.reload(); await p0.wait_for_timeout(1000)
        ok('existing sessions of a reset account are signed out', await p0.evaluate("document.body.classList.contains('locked')"))
        ok('old password no longer works', not await login(p,'dkouis','secret123') and 'Wrong' in await p.inner_text('#lErr'))
        ok('temporary password asks for a new one', not await login(p,'dkouis','Temp-2026') and await p.locator('#lNewBox').is_visible())
        await p.fill('#lNew','new-pass-1'); await p.fill('#lNew2','other-pass'); await p.click('#lBtn'); await p.wait_for_timeout(300)
        ok('mismatch is refused', 'do not match' in await p.inner_text('#lErr'))
        await p.fill('#lNew','Temp-2026'); await p.fill('#lNew2','Temp-2026'); await p.click('#lBtn'); await p.wait_for_timeout(800)
        ok('reusing the temporary password is refused', 'different from the temporary' in await p.inner_text('#lErr'))
        await p.fill('#lNew','new-pass-1'); await p.fill('#lNew2','new-pass-1'); await p.click('#lBtn'); await p.wait_for_timeout(1500)
        ok('new password saved and signed in', not await p.evaluate("document.body.classList.contains('locked')") and not {x['user']:x for x in users()}['dkouis'].get('must_change'))
        for _ in range(3):
            if await p.locator('.dlg').count(): await p.keyboard.press('Escape'); await p.wait_for_timeout(150)
        # change own password from the menu
        await p.click('#menuBtn'); await p.click('#menu [data-act=password]'); await p.wait_for_timeout(200)
        await p.fill('#cpOld','wrong-one'); await p.fill('#cpNew','secret123'); await p.fill('#cpNew2','secret123'); await p.click('.dlg button[type=submit]'); await p.wait_for_timeout(1200)
        ok('change password: wrong current password refused', 'not correct' in await p.inner_text('#cpErr'))
        await p.fill('#cpOld','new-pass-1'); await p.click('.dlg button[type=submit]'); await p.wait_for_timeout(800)
        ok('change password works and stays signed in', await p.locator('.dlg').count()==0 and not await p.evaluate("document.body.classList.contains('locked')"))
        # accounts dialog: flag shown and settable
        await p.click('#menuBtn'); await p.click('#menu [data-act=accounts]'); await p.wait_for_timeout(500)
        ok('accounts window shows Must change for anna', await p.locator('.dlg tr:has(input[value=anna]) [data-must]').is_checked())
        await p.locator('.dlg tr:has(input[value=viewer]) [data-must]').check()
        await p.locator('.dlg tr:has(input[value=anna]) [data-del]').click()
        await p.click('#accSave'); await p.wait_for_timeout(800)
        u={x['user']:x for x in users()}
        ok('editor can require a new password; removed account is gone', u['viewer'].get('must_change') and 'anna' not in u and not u['dkouis'].get('must_change'), list(u))
        c2=await b.new_context(); p2=await c2.new_page()
        ok('viewer then asked for a new password', not await login(p2,'viewer','viewer123') and await p2.locator('#lNewBox').is_visible())
        await p2.fill('#lNew','viewer123b'); await p2.fill('#lNew2','viewer123b'); await p2.click('#lBtn'); await p2.wait_for_timeout(1200)
        ok('viewer signed in with the new password', not await p2.evaluate("document.body.classList.contains('locked')"))
        # all accounts at once with *
        open(D+'reset-passwords.txt','w').write('*  Reset-all-1\n')
        c3=await b.new_context(); p3=await c3.new_page(); await p3.goto(BASE); await p3.wait_for_timeout(700)
        ok('* resets every account', all(x.get('must_change') for x in users()) and not os.path.exists(D+'reset-passwords.txt'))
        # restore the test accounts
        open(D+'reset-passwords.txt','w').write('dkouis restore-tmp1 editor\nviewer restore-tmp1 viewer\n')
        await p3.goto(BASE); await p3.wait_for_timeout(600)
        for name,old,new in [('dkouis','restore-tmp1','secret123'),('viewer','restore-tmp1','viewer123')]:
            await login(p3,name,old); await p3.fill('#lNew',new); await p3.fill('#lNew2',new); await p3.click('#lBtn'); await p3.wait_for_timeout(1000)
            await p3.evaluate("()=>{ dirty=false; }"); await p3.evaluate("signOut()"); await p3.wait_for_timeout(800)
        c4=await b.new_context(); p4=await c4.new_page()
        ok('test accounts restored', all(not x.get('must_change') for x in users()) and await login(p4,'dkouis','secret123'))
        print(f'\n{sum(R)}/{len(R)} passed'); await b.close()
asyncio.run(main())
