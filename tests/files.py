"""Attachments, Outlook .msg import, .ics imports as Meetings.
Usage: WTC_SRV=<test server folder> WTC_MSG=<folder with sample .msg files> python3 tests/files.py
(fresh tests/seed.py data; accounts dkouis/secret123, viewer/viewer123)"""
import asyncio, os, json, datetime, glob, zlib, struct, hashlib
from playwright.async_api import async_playwright
BASE='http://127.0.0.1:8099/'; SRV=os.environ.get('WTC_SRV','./testsrv'); MSG=os.environ.get('WTC_MSG','./tests/msg')
HERE=os.path.dirname(os.path.abspath(__file__)); TMP=os.path.join(HERE,'shots'); os.makedirs(TMP,exist_ok=True)
R=[]
def ok(n,c,i=''): R.append(bool(c)); print(('PASS ' if c else 'FAIL ')+n+(f'  [{i}]' if i!='' else ''), flush=True)
def guarded(path):
    s=open(path,'rb').read(); return s[s.index(b'\n')+1:]
def server(): return json.loads(guarded(SRV+'/data/calendar.php'))['data']
def onserver(fid): return os.path.exists(f'{SRV}/data/files/{fid}.php')
def stored(fid): return guarded(f'{SRV}/data/files/{fid}.php')
def png(path, w=40, h=30):
    raw=b''.join(b'\0'+bytes([200,60,60])*w for _ in range(h))
    ch=lambda t,d: struct.pack('>I',len(d))+t+d+struct.pack('>I',zlib.crc32(t+d)&0xffffffff)
    open(path,'wb').write(b'\x89PNG\r\n\x1a\n'+ch(b'IHDR',struct.pack('>IIBBBBB',w,h,8,2,0,0,0))+ch(b'IDAT',zlib.compress(raw))+ch(b'IEND',b''))
async def saved(p):
    for _ in range(80):
        if (await p.inner_text('#saveState')).startswith('Saved'): return
        await p.wait_for_timeout(150)
async def ctx(b):
    c=await b.new_context(viewport={'width':1360,'height':900}, timezone_id='Europe/Athens', locale='en-GB', accept_downloads=True)
    await c.clock.install(time=datetime.datetime(2026,11,1,12,0,tzinfo=datetime.timezone.utc)); return c
async def login(p,u='dkouis',pw='secret123'):
    await p.goto(BASE); await p.fill('#lUser',u); await p.fill('#lPass',pw); await p.click('#lBtn'); await p.wait_for_selector('body:not(.locked)'); await p.wait_for_timeout(700)
    for _ in range(3):
        if await p.locator('.dlg').count(): await p.keyboard.press('Escape'); await p.wait_for_timeout(150)
async def pick(p, click_sel, files):
    async with p.expect_file_chooser() as fc: await p.click(click_sel)
    await (await fc.value).set_files(files)
async def uploads_done(p, root='.dlg'):
    for _ in range(100):
        if await p.locator(root+' .fitem.up').count()==0: return
        await p.wait_for_timeout(100)

async def main():
    if os.path.exists(SRV+'/data/throttle.php'): os.remove(SRV+'/data/throttle.php')
    img=os.path.join(TMP,'pic.png'); png(img)
    txt=os.path.join(TMP,'notes.txt'); open(txt,'w').write('Hello attachments\nΓειά σου κόσμε\n')
    big=os.path.join(TMP,'big.bin'); open(big,'wb').write(os.urandom(2_600_000))
    msgs={os.path.basename(f):f for f in glob.glob(MSG+'/*.msg')}
    ics=os.path.join(TMP,'imp.ics'); open(ics,'w').write('BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:files-test-1\r\nDTSTART:20261110T080000Z\r\nDTEND:20261110T090000Z\r\nSUMMARY:Imported call\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nUID:files-test-2\r\nDTSTART;VALUE=DATE:20261111\r\nSUMMARY:Imported all-day\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n')
    async with async_playwright() as pw:
        b=await pw.chromium.launch(); c=await ctx(b); m=await c.new_page(); errs=[]
        c.on('page', lambda pg: pg.on('pageerror', lambda e: errs.append('TAB '+str(e))))
        m.on('pageerror', lambda e: errs.append('MAIN '+str(e)))
        await login(m)
        await m.evaluate("()=>{ prefs.openEditors='popup'; prefs.openDetails='popup'; savePrefs(); }")

        # ---------- .ics imports are Meetings
        await m.evaluate("()=>{ const e=newEvent({title:'Old import', start:'2026-11-09T10:00', end:'2026-11-09T11:00', uid:'old-uid-1', category:'other'}); saveNewItem(e); }")
        await saved(m)
        await m.click('#addBtn'); await pick(m, '#addMenu [data-new=icsimp]', [ics])
        await m.wait_for_selector('.dlg [data-go]')
        ok('import dialog says events become Meetings', 'Meetings' in await m.inner_text('.dlg .box') and await m.locator('.dlg [data-oldimp]').count()==1)
        await m.click('.dlg [data-go]'); await saved(m)
        ev=[e for e in server()['events'] if e.get('uid','').startswith('files-test')]
        ok('imported events are Meetings (timed and all-day)', len(ev)==2 and all(e['category']=='meeting' for e in ev), [e['category'] for e in ev])
        ok('earlier imports changed to Meeting when ticked', [e for e in server()['events'] if e.get('uid')=='old-uid-1'][0]['category']=='meeting')

        # ---------- attachments in the event editor
        eid=await m.evaluate("()=>{ const e=newEvent({title:'With files', start:'2026-11-03T10:00', end:'2026-11-03T11:00', tz:'Europe/Athens'}); saveNewItem(e); openEventEditor(e.id); return e.id; }")
        await m.wait_for_selector('.dlg [data-fadd]')
        ok('event editor has an Attachments section with Add files', await m.locator('.dlg .sec', has_text='Attachments').count()==1)
        await pick(m, '.dlg [data-fadd]', [img, txt, big]); await uploads_done(m)
        ok('several files added at once', await m.locator('.dlg .flist .fitem').count()==3, await m.locator('.dlg .flist .fitem').count())
        await m.click('.dlg [data-a=save]'); await saved(m)
        e=[x for x in server()['events'] if x['id']==eid][0]; F=e.get('files',[])
        ok('files saved with the event', [f['name'] for f in F]==['pic.png','notes.txt','big.bin'] and all(onserver(f['id']) for f in F), [f['name'] for f in F])
        ok('stored bytes equal the uploaded file (multi-piece upload)', stored(F[2]['id'])==open(big,'rb').read() and F[2]['size']==2_600_000)
        ok('files of Work items are not encrypted', 'key' not in F[0])
        idx=json.loads(guarded(SRV+'/data/files.php')); ok('server index lists the files', all(f['id'] in idx and not idx[f['id']].get('part') for f in F))
        # preview & download
        await m.evaluate(f"()=>openEventEditor('{eid}')"); await m.wait_for_selector('.dlg .flist')
        await m.locator('.dlg .flist [data-fopen="0"]').click(); await m.wait_for_selector('.dlg .fprev img')
        nat=await m.evaluate("()=>{ const i=$$('.dlg .fprev img').pop(); return new Promise(r=>{ if(i.complete) r(i.naturalWidth); else i.onload=()=>r(i.naturalWidth); }); }")
        ok('image preview shows the picture', nat==40, nat)
        async with m.expect_download() as dl: await m.locator('.dlg .fprev').locator('xpath=..').locator('[data-a=dl]').click()
        d=await dl.value; p=await d.path()
        ok('download gives the file with its name', d.suggested_filename=='pic.png' and open(p,'rb').read()==open(img,'rb').read())
        await m.locator('.dlg .fprev').locator('xpath=..').locator('[data-close]').last.click()
        await m.locator('.dlg .flist [data-fopen="1"]').click(); await m.wait_for_selector('.dlg .fprev .ftext')
        ok('text preview (UTF-8)', 'Γειά σου κόσμε' in await m.inner_text('.dlg .fprev .ftext'))
        await m.locator('.dlg .fprev').locator('xpath=..').locator('[data-close]').last.click()
        # rename, replace, remove
        await m.locator('.dlg [data-fren="1"]').click(); await m.fill('#pdv','renamed'); await m.keyboard.press('Enter'); await m.wait_for_timeout(200)
        ok('rename keeps the extension', 'renamed.txt' in await m.inner_text('.dlg .flist'))
        old0=F[0]['id']
        await pick(m, '.dlg [data-frep="0"]', [txt]); await uploads_done(m)
        await m.locator('.dlg [data-fdel="2"]').click()
        await m.click('.dlg [data-a=save]'); await saved(m)
        F2=[x for x in server()['events'] if x['id']==eid][0]['files']
        ok('replace, rename and remove are saved', [f['name'] for f in F2]==['notes.txt','renamed.txt'] and F2[0]['id']!=old0, [f['name'] for f in F2])
        ok('replaced/removed files stay on the server for Undo (7-day clean-up)', onserver(old0) and onserver(F[2]['id']))
        # cancel deletes what was uploaded in that editor
        await m.evaluate(f"()=>openEventEditor('{eid}')"); await m.wait_for_selector('.dlg [data-fadd]')
        await pick(m, '.dlg [data-fadd]', [img]); await uploads_done(m)
        newid=await m.evaluate("()=>topDialog()._filesCtl.list.slice(-1)[0].id")
        ok('upload is on the server before saving', onserver(newid))
        await m.locator('.dlg [data-close]').last.click(); await m.wait_for_timeout(800)
        ok('Cancel deletes files uploaded in that editor', not onserver(newid) and len([x for x in server()['events'] if x['id']==eid][0]['files'])==2)
        # popover + details tab show attachments
        await m.evaluate("()=>{ setView('week'); cursor=fromKey('2026-11-02'); render(); $('#tgBody').scrollTop=8*hourPx(); }")
        await m.locator('.ev[data-ok=event]', has_text='With files').first.click(); await m.wait_for_selector('#pop')
        ok('popover lists the attachments', await m.locator('#pop .fitem').count()==2)
        await m.evaluate("()=>closePopover()")
        dt=await c.new_page(); await dt.goto(BASE+'#open=event&id='+eid); await dt.wait_for_selector('.dt-title')
        ok('details tab shows Attachments', await dt.locator('.dt-grid .fitem').count()==2 and 'Attachments' in await dt.inner_text('.dt-grid'))
        await dt.locator('.dt-grid [data-fopen="0"]').click(); await dt.wait_for_selector('.dlg .fprev .ftext')
        ok('attachment opens from the details tab', 'Hello attachments' in await dt.inner_text('.dlg .fprev'))
        await dt.close()
        # duplicate / convert keep the attachments
        await m.evaluate(f"()=>{{ const c=duplicateItem(ev('{eid}')); window._dup=c.id; }}"); await saved(m)
        ok('duplicate keeps the attachments', len([x for x in server()['events'] if x['id']==await m.evaluate('()=>window._dup')][0].get('files',[]))==2)

        # ---------- tasks and projects
        tid=await m.evaluate("()=>{ const t=newTask({title:'Task with file', dueDate:'2026-11-05'}); saveNewItem(t); openTaskEditor(t.id); return t.id; }")
        await m.wait_for_selector('.dlg [data-fdrop]')
        # drop a file on the editor (DataTransfer from the page)
        await m.evaluate("""()=>{ const dt=new DataTransfer(); dt.items.add(new File(['dropped text'],'dropped.txt',{type:'text/plain'}));
            const z=$('.dlg [data-fdrop]'); z.dispatchEvent(new DragEvent('drop',{dataTransfer:dt,bubbles:true,cancelable:true})); }""")
        await m.wait_for_timeout(300); await uploads_done(m)
        ok('drag and drop onto the editor attaches the file', 'dropped.txt' in await m.inner_text('.dlg .flist'))
        await m.click('.dlg [data-a=save]'); await saved(m)
        ok('task attachments saved', [f['name'] for f in [x for x in server()['tasks'] if x['id']==tid][0]['files']]==['dropped.txt'])
        await m.evaluate("()=>{ tpTab='all'; renderTasksPanel(); }")
        ok('task row shows 📎', '📎 1' in await m.locator('.trow', has_text='Task with file').first.inner_text())
        pid=await m.evaluate("()=>{ const p=newProject({name:'Proj with file'}); saveNewItem(p); openProjectEditor(p.id); return p.id; }")
        await m.wait_for_selector('.dlg [data-fadd]'); await pick(m, '.dlg [data-fadd]', [txt]); await uploads_done(m)
        await m.click('.dlg [data-a=save]'); await saved(m)
        ok('project attachments saved', len([x for x in server()['projects'] if x['id']==pid][0].get('files',[]))==1)

        # ---------- Personal (private) items: encrypted files
        await m.evaluate("async()=>{ await setPassphrase('correct horse battery', false); changed(); }"); await saved(m)
        prid=await m.evaluate("()=>{ const e=newEvent({title:'Secret doc', calendarId:'personal', start:'2026-11-04T18:00', end:'2026-11-04T19:00'}); saveNewItem(e); openEventEditor(e.id); return e.id; }")
        await m.wait_for_selector('.dlg [data-fadd]'); await pick(m, '.dlg [data-fadd]', [txt]); await uploads_done(m)
        await m.click('.dlg [data-a=save]'); await saved(m)
        pf=await m.evaluate(f"()=>ev('{prid}').files[0]")
        ok('Personal item file is encrypted on the server', 'key' in pf and b'Hello attachments' not in stored(pf['id']) and len(stored(pf['id']))==pf['size']+16)
        ok('Personal item (and its file key) not in the clear in calendar.php', 'Secret doc' not in json.dumps(server()) and pf['key'] not in json.dumps(server()))
        back=await m.evaluate(f"async()=>new TextDecoder().decode(await fetchAttachment(ev('{prid}').files[0]))")
        ok('encrypted file opens again in the browser', back.startswith('Hello attachments'))

        # ---------- clean-up of unused files
        r=await m.evaluate("()=>filesGc(true)"); idx=json.loads(guarded(SRV+'/data/files.php'))
        ok('clean-up marks unused files (replaced/removed) without deleting them yet', r and r['deleted']==0 and onserver(old0) and idx[old0].get('unused') and idx[F[2]['id']].get('unused') and not idx[F2[0]['id']].get('unused'), r)
        idx=json.loads(guarded(SRV+'/data/files.php'))
        for k in idx:
            if idx[k].get('unused'): idx[k]['unused']='2026-01-01T00:00:00+00:00'
        open(SRV+'/data/files.php','w').write(open(SRV+'/data/files.php').read().split('\n')[0]+'\n'+json.dumps(idx))
        r=await m.evaluate("()=>filesGc(true)")
        ok('files unused for over 7 days are deleted; files in use are kept', r['deleted']>=2 and not onserver(old0) and all(onserver(f['id']) for f in F2) and onserver(pf['id']), r)

        # ---------- Outlook .msg import
        if msgs:
            await m.click('#addBtn'); await pick(m, '#addMenu [data-new=msgimp]', [msgs['EmailWithAttachments.msg']])
            await m.wait_for_selector('.dlg .mailimp')
            hd=await m.inner_text('.dlg .mailhd')
            ok('email preview: subject, from, to, cc', 'This is the subject' in hd and 'peterpan@neverland.com' in hd and 'crocodile@neverland.com' in hd and 'tinkerbel@neverland.com' in hd, hd.replace('\n',' | '))
            fr=m.frame_locator('.dlg .mailframe')
            ok('formatted body shown in a sandboxed frame', 'Hello Neverland' in await fr.locator('body').inner_text() and await m.get_attribute('.dlg .mailframe','sandbox')=='allow-popups allow-popups-to-escape-sandbox')
            await m.click('.dlg [data-mv=text]'); ok('plain-text view', 'Hello Neverland text' in await m.inner_text('.dlg [data-mbody]'))
            ok('default type is Task, title from the subject', await m.input_value('.dlg [name=kind]')=='task' and await m.input_value('.dlg [name=title]')=='This is the subject')
            boxes=await m.evaluate("()=>$$('.dlg [data-ai]').map(c=>[c.parentNode.textContent.trim().slice(0,40), c.checked])")
            ok('attachments listed; picture inside the text unticked', len(boxes)==2 and boxes[0][1] and not boxes[1][1], boxes)
            # change the type to Meeting and continue to all details
            await m.select_option('.dlg [name=kind]','meeting'); await m.fill('.dlg [name=title]','Meeting about Neverland')
            ok('event fields shown for a Meeting', await m.locator('.dlg [name=sd]').is_visible() and not await m.locator('.dlg [name=due]').is_visible())
            await m.click('.dlg [data-a=edit]'); await m.wait_for_selector('.dlg [name=category]', timeout=15000)
            ok('full editor opens with the chosen type and title', await m.input_value('.dlg [name=category]')=='meeting' and await m.input_value('.dlg [name=title]')=='Meeting about Neverland')
            ok('editor has the email and its attachment', [x.strip() for x in (await m.locator('.dlg .flist .fitem a').all_inner_texts())]==['📧 EmailWithAttachments.msg','🖼 peterpan.jpg'])
            notes=await m.input_value('.dlg [name=notes]')
            ok('notes hold sender, recipients and the text', '**From:** Kees' in notes and 'crocodile@neverland.com' in notes and 'Hello Neverland' in notes, notes[:120])
            await m.select_option('.dlg [name=category]','appointment')
            await m.click('.dlg [data-a=save]'); await saved(m)
            it=[x for x in server()['events'] if x['title']=='Meeting about Neverland']
            ok('saved as the type chosen in the editor, with 2 attachments', len(it)==1 and it[0]['category']=='appointment' and len(it[0]['files'])==2 and all(onserver(f['id']) for f in it[0]['files']))
            jpg=[f for f in it[0]['files'] if f['name']=='peterpan.jpg'][0]
            ok('attachment bytes are exact', len(stored(jpg['id']))==39150 and stored(jpg['id'])[:3]==b'\xff\xd8\xff')
            orig=[f for f in it[0]['files'] if f['name'].endswith('.msg')][0]
            ok('original email stored unchanged', hashlib.sha256(stored(orig['id'])).hexdigest()==hashlib.sha256(open(msgs['EmailWithAttachments.msg'],'rb').read()).hexdigest())
            # cancelled editor after import deletes the uploads
            await m.click('#addBtn'); await pick(m, '#addMenu [data-new=msgimp]', [msgs['TxtSampleEmailWithAttachment.msg']])
            await m.wait_for_selector('.dlg .mailimp'); await m.click('.dlg [data-a=edit]'); await m.wait_for_selector('.dlg [name=dueDate]', timeout=15000)
            ids=await m.evaluate("()=>topDialog()._filesCtl.list.map(f=>f.id)")
            ok('task editor opened (default type) with the email + PDF', len(ids)==2 and all(onserver(i) for i in ids))
            await m.locator('.dlg [data-close]').last.click(); await m.wait_for_timeout(1000)
            ok('cancelling that editor deletes the uploaded email files', not any(onserver(i) for i in ids))
            # Add now (task) from the ⋯ menu, RTF and HTML samples
            for name, want in [('RtfSampleEmailWithAttachment.msg','sample RTF email'), ('HtmlSampleEmailWithAttachment.msg','sample HTML email'), ('EmailWith2Attachments.msg','Met vriendelijke groet')]:
                await m.click('#menuBtn'); await pick(m, '#menu [data-act=msgimp]', [msgs[name]])
                await m.wait_for_selector('.dlg .mailimp'); await m.click('.dlg [data-a=now]')
                subj='E-mail with 2 attachments' if name.startswith('EmailWith2') else name[:-4]
                for _ in range(80):
                    t=[x for x in server()['tasks'] if x['title']==subj and 'email' in x.get('tags',[])]
                    if t: break
                    await m.wait_for_timeout(150)
                ok(f'Add now creates a task: {name}', len(t)==1 and want in t[0]['notes'] and len(t[0]['files'])==(3 if name.startswith('EmailWith2') else 2) and t[0]['dueDate']=='2026-11-03', [x['name'] for x in (t[0]['files'] if t else [])])
            # drop a .msg on the calendar
            data_b64=__import__('base64').b64encode(open(msgs['TxtSampleEmail.msg'],'rb').read()).decode()
            await m.evaluate("b=>{ const bin=Uint8Array.from(atob(b),c=>c.charCodeAt(0)); const dt=new DataTransfer(); dt.items.add(new File([bin],'TxtSampleEmail.msg')); document.querySelector('#main, .layout, body').dispatchEvent(new DragEvent('drop',{dataTransfer:dt,bubbles:true,cancelable:true})); }", data_b64)
            await m.wait_for_selector('.dlg .mailimp', timeout=5000)
            ok('dropping a .msg on the calendar opens the import', 'TxtSampleEmail' in await m.inner_text('.dlg .mailhd'))
            await m.locator('.dlg [data-close]').last.click()
            # a stored .msg attachment opens as an email preview
            await m.evaluate("()=>{ const t=data.tasks.find(x=>x.title==='E-mail with 2 attachments'); openTaskEditor(t.id); }"); await m.wait_for_selector('.dlg .flist')
            await m.locator('.dlg .flist a', has_text='.msg').click(); await m.wait_for_selector('.dlg .fprev .mailhd')
            ok('.msg attachment previews as an email', 'E-mail with 2 attachments' in await m.inner_text('.dlg .fprev .mailhd'))
            await m.locator('.dlg .fprev').locator('xpath=..').locator('[data-close]').last.click(); await m.locator('.dlg [data-close]').last.click()
            # edit-tab mode: continue to the editor in a new tab
            await m.evaluate("()=>{ prefs.openEditors='tab'; savePrefs(); }")
            await m.click('#addBtn'); await pick(m, '#addMenu [data-new=msgimp]', [msgs['HtmlSampleEmail.msg']]); await m.wait_for_selector('.dlg .mailimp')
            await m.select_option('.dlg [name=kind]','social')
            async with c.expect_page() as np: await m.click('.dlg [data-a=edit]')
            et=await np.value; await et.wait_for_selector('.dlg.page [name=category]', timeout=15000)
            ok('edit tab gets the imported email draft', await et.input_value('.dlg.page [name=category]')=='social' and await et.locator('.dlg.page .flist .fitem').count()==1)
            await et.click('.dlg.page [data-a=save]'); await m.wait_for_timeout(2500)
            s=[x for x in server()['events'] if x['title']=='HtmlSampleEmail']
            ok('saved from the edit tab with the email attached', len(s)==1 and s[0]['category']=='social' and len(s[0]['files'])==1)
            await m.evaluate("()=>{ prefs.openEditors='popup'; savePrefs(); }")
        else: ok('sample .msg files found (set WTC_MSG)', False)

        # ---------- viewer
        v=await (await ctx(b)).new_page(); v.on('pageerror', lambda e: errs.append('VIEWER '+str(e)))
        await login(v,'viewer','viewer123')
        await v.evaluate(f"()=>{{ prefs.openEditors='popup'; openEventEditor('{eid}'); }}"); await v.wait_for_selector('.dlg .flist')
        ok('viewer sees attachments read-only (no add/remove)', await v.locator('.dlg [data-fadd]').count()==0 and await v.locator('.dlg [data-fdel]').count()==0 and await v.locator('.dlg [data-fdl]').count()==2)
        r=await v.evaluate("async()=>{ try{ await api('upload_start',{name:'x',size:1}); return 'allowed'; }catch(e){ return e.status; } }")
        ok('viewer cannot upload', r==403, r)
        ok('no page errors', not errs, errs[:3])
        await b.close()
    print(f'\n{sum(R)}/{len(R)} passed'); raise SystemExit(0 if all(R) else 1)
if __name__=="__main__": asyncio.run(main())
