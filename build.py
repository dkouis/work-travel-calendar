import glob, os, subprocess, sys
root=os.path.dirname(os.path.abspath(__file__)); src=os.path.join(root,'src')
js=''.join(open(f,encoding='utf-8').read()+'\n' for f in sorted(glob.glob(os.path.join(src,'js','*.js'))))
# third-party libraries (src/vendor/*.min.js), each checked like the app code
vendor=''
for f in sorted(glob.glob(os.path.join(src,'vendor','*.min.js'))):
    v=open(f,encoding='utf-8').read()
    if '</script' in v.lower() or '<?' in v: print('ERROR: unsafe sequence in', f); sys.exit(1)
    vendor+='<script>\n'+v+'\n</script>\n'
open(os.path.join(root,'build-app.js'),'w',encoding='utf-8').write(js)
r=subprocess.run(['node','--check',os.path.join(root,'build-app.js')],capture_output=True,text=True)
if r.returncode: print(r.stderr); sys.exit(1)
if '</script' in js.lower(): print('ERROR: </script in JS'); sys.exit(1)
if '<?' in js: print('ERROR: <? in JS'); sys.exit(1)
out=open(os.path.join(src,'head.php'),encoding='utf-8').read()+open(os.path.join(src,'base.css'),encoding='utf-8').read()+open(os.path.join(src,'app.css'),encoding='utf-8').read()+'</style>\n</head>\n'+open(os.path.join(src,'body.html'),encoding='utf-8').read()+'\n'+vendor+'<script>\n'+js+'</script>\n</body>\n</html>\n'
open(os.path.join(root,'site','index.php'),'w',encoding='utf-8').write(out)
print('ok', len(out)//1024, 'KB', js.count('\n'), 'JS lines')
