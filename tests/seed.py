# Build a v1 calendar like the live one (days with status, location, flights, comments)
import json, datetime, random, sys, os
random.seed(4)
days={}
d=datetime.date(2026,7,1)
loc='Athens'
while d<=datetime.date(2026,11,1):
    k=d.isoformat(); e={}
    if d.weekday()<5:
        s=random.choice(['office','tele','tele','abroad','office','leave']) ; e['am']=s; e['pm']=s if random.random()<.8 else random.choice(['office','tele'])
    if d.weekday()==0 and random.random()<.5:
        e['flights']=[{'from':'Athens','to':'Brussels','time':'morning','note':'A3 610 07:10'}]
    if d.weekday()==3 and random.random()<.5:
        e['flights']=[{'from':'Brussels','to':'Athens','time':'evening'}]
    if random.random()<.15: e['comment']='Note for '+k
    if e: days[k]=e
    d+=datetime.timedelta(days=1)
days['2026-09-30']={'am':'office','pm':'office','flights':[{'from':'','to':'','time':'morning'}]}
cal={'rev':1,'by':'import','saved':'2026-11-01T10:00:00+00:00','data':{'version':1,'owner':'D. Kouis','days':days,'monthNotes':{'2026-10':'October notes'}}}
guard="<?php http_response_code(404); exit; __halt_compiler();\n"
os.makedirs(sys.argv[1]+'/data',exist_ok=True)
open(sys.argv[1]+'/data/calendar.php','w').write(guard+json.dumps(cal,ensure_ascii=False,indent=1))
print(len(days),'days')
