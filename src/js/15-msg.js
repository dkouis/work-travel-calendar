/* ============================================================
   Outlook .msg reader (Compound File Binary + MAPI properties).
   Pure functions on a Uint8Array; nothing leaves the browser.
   ============================================================ */
function cfbRead(buf){
  const u8 = buf instanceof Uint8Array ? buf : new Uint8Array(buf);
  const dv = new DataView(u8.buffer, u8.byteOffset, u8.byteLength);
  const sig=[0xD0,0xCF,0x11,0xE0,0xA1,0xB1,0x1A,0xE1];
  if(u8.length<512 || sig.some((b,i)=>u8[i]!==b)) throw new Error('This is not an Outlook message (.msg) file.');
  const ssz = 1<<dv.getUint16(0x1E,true), msz = 1<<dv.getUint16(0x20,true);
  const nFat = dv.getUint32(0x2C,true), dirStart = dv.getUint32(0x30,true), cutoff = dv.getUint32(0x38,true);
  const miniFatStart = dv.getUint32(0x3C,true), difStart = dv.getUint32(0x44,true);
  const END=0xFFFFFFFE, FREE=0xFFFFFFFF;
  const off = s => (s+1)*ssz;
  const u32 = o => o+4<=u8.length ? dv.getUint32(o,true) : FREE;
  // FAT sector list from the header DIFAT (109 entries) and any DIFAT sectors
  const fatSecs=[];
  for(let i=0;i<109 && fatSecs.length<nFat;i++){ const s=u32(0x4C+i*4); if(s<0xFFFFFFFA) fatSecs.push(s); }
  for(let s=difStart, guard=0; s<0xFFFFFFFA && fatSecs.length<nFat && guard<10000; guard++){
    const per=ssz/4-1; for(let i=0;i<per && fatSecs.length<nFat;i++){ const x=u32(off(s)+i*4); if(x<0xFFFFFFFA) fatSecs.push(x); }
    s=u32(off(s)+per*4);
  }
  const fat=new Uint32Array(fatSecs.length*ssz/4);
  fatSecs.forEach((s,j)=>{ for(let i=0;i<ssz/4;i++) fat[j*ssz/4+i]=u32(off(s)+i*4); });
  const chain=(start, table)=>{ const out=[]; const seen=new Set(); for(let s=start; s!==END && s<table.length && !seen.has(s); s=table[s]){ seen.add(s); out.push(s); } return out; };
  const readBig=(start,size)=>{ const out=new Uint8Array(size); let p=0; for(const s of chain(start,fat)){ const o=off(s), n=Math.min(ssz,size-p); if(n<=0) break; out.set(u8.subarray(o,Math.min(o+n,u8.length)),p); p+=n; } return out; };
  // directory
  const dirBytes=(()=>{ const ss=chain(dirStart,fat); const out=new Uint8Array(ss.length*ssz); ss.forEach((s,i)=>out.set(u8.subarray(off(s),off(s)+ssz),i*ssz)); return out; })();
  const ddv=new DataView(dirBytes.buffer);
  const ents=[];
  for(let o=0;o+128<=dirBytes.length;o+=128){
    const nl=ddv.getUint16(o+64,true), type=dirBytes[o+66];
    let name=''; for(let i=0;i<Math.max(0,nl-2);i+=2) name+=String.fromCharCode(ddv.getUint16(o+i,true));
    ents.push({name, type, left:ddv.getUint32(o+68,true), right:ddv.getUint32(o+72,true), child:ddv.getUint32(o+76,true), start:ddv.getUint32(o+116,true), size:ddv.getUint32(o+120,true)});
  }
  const root=ents[0]; if(!root || root.type!==5) throw new Error('The .msg file is damaged (no root).');
  const miniStream=readBig(root.start, root.size);
  const miniFat=(()=>{ const ss=chain(miniFatStart,fat); const t=new Uint32Array(ss.length*ssz/4); ss.forEach((s,j)=>{ for(let i=0;i<ssz/4;i++) t[j*ssz/4+i]=u32(off(s)+i*4); }); return t; })();
  const readMini=(start,size)=>{ const out=new Uint8Array(size); let p=0; for(const s of chain(start,miniFat)){ const o=s*msz, n=Math.min(msz,size-p); if(n<=0) break; out.set(miniStream.subarray(o,o+n),p); p+=n; } return out; };
  const children=e=>{ const out=[]; const stack=[e.child]; const seen=new Set(); while(stack.length){ const i=stack.pop(); if(i>=ents.length||seen.has(i)) continue; seen.add(i); const c=ents[i]; out.push(c); stack.push(c.left, c.right); } return out; };
  const node=e=>{ const n={name:e.name, streams:{}, storages:{}};
    for(const c of children(e)){
      if(c.type===2) n.streams[c.name]=()=> c.size<cutoff ? readMini(c.start,c.size) : readBig(c.start,c.size);
      else if(c.type===1) n.storages[c.name]=node(c);
    }
    return n; };
  return node(root);
}
const _u16 = b => { let s=''; for(let i=0;i+1<b.length;i+=2) s+=String.fromCharCode(b[i]|(b[i+1]<<8)); return s.replace(/\0+$/,''); };
function _ansi(b, cp){ try{ return new TextDecoder(cp||'windows-1252').decode(b).replace(/\0+$/,''); }catch(e){ return Array.from(b,c=>String.fromCharCode(c)).join('').replace(/\0+$/,''); } }
const MSG_CP = new Proxy({}, {get:(_,n)=>cpLabel(+n)});
/* Windows code page number -> TextDecoder label (null if unknown) */
function cpLabel(n){
  if(n>=1250 && n<=1258) return 'windows-'+n;
  if(n>=28591 && n<=28606 && n!==28600 && n!==28602) return 'iso-8859-'+(n-28590);
  return {65001:'utf-8',20127:'us-ascii',874:'windows-874',932:'shift_jis',936:'gbk',949:'euc-kr',950:'big5',54936:'gb18030',20866:'koi8-r',21866:'koi8-u',1200:'utf-16le',1201:'utf-16be',737:'ibm866',866:'ibm866',51932:'euc-jp',50220:'iso-2022-jp'}[n]||null;
}
/* RTF \fcharset -> code page (Greek fonts are 161 -> 1253) */
const RTF_CHARSET_CP = {161:1253,162:1254,177:1255,178:1256,186:1257,163:1258,204:1251,238:1250,222:874,128:932,134:936,136:950,129:949,0:1252};
/* HTML body bytes: the charset the HTML declares, then the message's code pages, then UTF-8 if valid */
function decodeHtmlBytes(b, P, cp){
  const head=Array.from(b.subarray(0,4096),c=>String.fromCharCode(c)).join('');
  const m=/<meta[^>]+charset\s*=\s*["']?([\w:.-]+)/i.exec(head);
  const tries=[m&&m[1], cpLabel(P['3FDE']), cpLabel(P['3FFD'])];
  for(const t of tries){ if(!t) continue; try{ return new TextDecoder(t).decode(b).replace(/\0+$/,''); }catch(e){} }
  try{ return new TextDecoder('utf-8',{fatal:true}).decode(b).replace(/\0+$/,''); }catch(e){}
  return _ansi(b, cp);
}
/* properties of one storage: {id(hex4): value}; variable-size values from __substg1.0_ streams,
   fixed-size ones (numbers, dates, flags) from __properties_version1.0 */
function msgProps(st, headerLen, cp){
  const p={};
  const fixed=st.streams['__properties_version1.0'];
  if(fixed){ const b=fixed(); const dv=new DataView(b.buffer,b.byteOffset,b.byteLength);
    for(let o=headerLen;o+16<=b.length;o+=16){
      const type=dv.getUint16(o,true), id=dv.getUint16(o+2,true).toString(16).toUpperCase().padStart(4,'0');
      if(type===0x0003) p[id]=dv.getInt32(o+8,true);
      else if(type===0x000B) p[id]=dv.getUint16(o+8,true)!==0;
      else if(type===0x0040){ const lo=dv.getUint32(o+8,true), hi=dv.getUint32(o+12,true); const ms=(hi*4294967296+lo)/1e4-11644473600000; if(ms>0) p[id]=new Date(ms); }
      else if(type===0x0014) p[id]=dv.getUint32(o+8,true)+dv.getUint32(o+12,true)*4294967296;
    } }
  for(const [n,fn] of Object.entries(st.streams)){
    const m=/^__substg1\.0_([0-9A-F]{4})([0-9A-F]{4})$/i.exec(n); if(!m) continue;
    const id=m[1].toUpperCase(), type=m[2].toUpperCase();
    if(type==='001F') p[id]=_u16(fn());
    else if(type==='001E') { if(p[id]===undefined) p[id]=_ansi(fn(), cp); }
    else if(type==='0102') p[id]=fn();
  }
  return p;
}
function parseMsg(buf){
  const root=cfbRead(buf);
  const pre=msgProps(root, 32);                               // first pass for the code page
  const cp=MSG_CP[pre['3FDE']]||MSG_CP[pre['3FFD']]||'windows-1252';
  const P=msgProps(root, 32, cp);
  const str=id=>typeof P[id]==='string'?P[id]:'';
  const isAddr=s=>/^[^@\s<>]+@[^@\s<>]+$/.test(s||'');
  // recipients
  const recips=Object.entries(root.storages).filter(([n])=>/^__recip_version1\.0_/i.test(n)).sort((a,b)=>a[0].localeCompare(b[0])).map(([,s])=>{
    const r=msgProps(s,8,cp); const addr=[r['39FE'],r['5FF6'],r['3003']].find(isAddr)||'';
    return {name:r['3001']||r['5FF6']||addr, email:addr, type:r['0C15']===2?'cc':r['0C15']===3?'bcc':'to'}; });
  const hdrs=str('007D');
  const hdr=n=>{ const m=new RegExp('^'+n+':[ \\t]*((?:.*)(?:\\r?\\n[ \\t].*)*)','im').exec(hdrs); return m?m[1].replace(/\r?\n[ \t]+/g,' ').trim():''; };
  const fromHdr=hdr('From');
  const fromEmail=[str('5D01'),str('0C1F'),str('5D02'),str('0065')].find(isAddr) || ((/<([^>]+@[^>]+)>/.exec(fromHdr)||[])[1]) || (isAddr(fromHdr)?fromHdr:'');
  const fromName=str('0C1A')||str('0042')||fromHdr.replace(/<[^>]*>/,'').replace(/"/g,'').trim()||fromEmail;
  // body: plain text, HTML, or compressed RTF (which may carry HTML)
  let html=null; if(P['1013']!==undefined) html = P['1013'] instanceof Uint8Array ? decodeHtmlBytes(P['1013'], P, cp) : String(P['1013']);
  let rtf=null; if(P['1009'] instanceof Uint8Array){ try{ rtf=rtfDecompress(P['1009']); }catch(e){} }
  let text=str('1000');
  if(!html && rtf && /\\fromhtml1/.test(rtf)) html=rtfToText(rtf,{html:true});
  if(!text && !html && rtf) text=rtfToText(rtf);
  // attachments
  const atts=Object.entries(root.storages).filter(([n])=>/^__attach_version1\.0_/i.test(n)).sort((a,b)=>a[0].localeCompare(b[0])).map(([,s])=>{
    const a=msgProps(s,8,cp); const method=a['3705']||1;
    const emb=s.storages['__substg1.0_3701000D'];
    let data=a['3701'] instanceof Uint8Array ? a['3701'] : null;
    let name=a['3707']||a['3704']||a['3001']||'';
    let embedded=null; if(emb){ try{ const sub=msgProps(emb,24,cp); embedded={subject:sub['0037']||''}; }catch(e){} if(!name) name=(embedded&&embedded.subject||'Attached email')+'.msg'; }
    if(!name) name='attachment'+(data?'.bin':'');
    const cid=(a['3712']||'').replace(/^<|>$/g,'');
    return {name, mime:a['370E']||'', size:data?data.length:0, data, method, cid, hidden:!!a['7FFE'] || (!!cid && /^image\//i.test(a['370E']||'') && !!html && html.includes(cid)), embedded:!!emb||method===5, ole:method===6};
  });
  const date=P['0039']||P['0E06']||P['3007']||(hdr('Date')?new Date(hdr('Date')):null);
  const to=str('0E04')||recips.filter(r=>r.type==='to').map(r=>r.name).join('; ');
  const cc=str('0E03')||recips.filter(r=>r.type==='cc').map(r=>r.name).join('; ');
  return {subject:str('0037')||hdr('Subject'), fromName, fromEmail, to, cc, recips, date:(date instanceof Date && !isNaN(date))?date:null,
    importance:P['0017']===undefined?1:P['0017'], cls:str('001A'), text, html, hasRtf:!!rtf, atts};
}
/* MS-OXRTFCP: compressed RTF */
function rtfDecompress(b){
  const dv=new DataView(b.buffer,b.byteOffset,b.byteLength);
  const comp=dv.getUint32(0,true), raw=dv.getUint32(4,true), magic=dv.getUint32(8,true);
  if(magic===0x414c454d){ let s=''; for(const c of b.subarray(16,16+raw)) s+=String.fromCharCode(c); return s; }          // "MELA": stored uncompressed
  if(magic!==0x75465a4c) throw new Error('unknown RTF compression');   // "LZFu"
  const pre='{\\rtf1\\ansi\\mac\\deff0\\deftab720{\\fonttbl;}{\\f0\\fnil \\froman \\fswiss \\fmodern \\fscript \\fdecor MS Sans SerifSymbolArialTimes New RomanCourier{\\colortbl\\red0\\green0\\blue0\r\n\\par \\pard\\plain\\f0\\fs20\\b\\i\\u\\tab\\tx';
  const dict=new Uint8Array(4096); for(let i=0;i<pre.length;i++) dict[i]=pre.charCodeAt(i);
  let wp=pre.length; const out=[]; let p=16; const end=Math.min(b.length, comp+4);
  outer: while(p<end){
    const ctl=b[p++];
    for(let bit=0;bit<8 && p<end;bit++){
      if(ctl & (1<<bit)){
        const w=(b[p]<<8)|b[p+1]; p+=2; const o=w>>4, len=(w&15)+2;
        if(o===wp) break outer;
        for(let i=0;i<len;i++){ const c=dict[(o+i)&4095]; out.push(c); dict[wp]=c; wp=(wp+1)&4095; }
      } else { const c=b[p++]; out.push(c); dict[wp]=c; wp=(wp+1)&4095; }
    }
  }
  const r=out.slice(0,raw||out.length); let s=''; for(let i=0;i<r.length;i+=8192) s+=String.fromCharCode.apply(null, r.slice(i,i+8192));
  return s;                                                               // one character per byte; rtfToText decodes the code page
}
/* RTF -> plain text; with {html:true} returns the HTML that Outlook wrapped in RTF (\fromhtml1) */
function rtfToText(rtf, opts={}){
  const SKIP=new Set(['fonttbl','colortbl','stylesheet','info','pict','object','header','footer','headerl','headerr','footerl','footerr','xmlnstbl','listtable','listoverridetable','rsidtbl','generator','themedata','colorschememapping','latentstyles','datastore','pgdsctbl','mmathPr','fldinst']);
  let out='', i=0, uc=1, skipBytes=0, cp='windows-1252'; const stack=[]; let st={skip:false, htmlrtf:false, tag:false, font:null};
  // font number -> code page, from the font table (e.g. {\f1\fswiss\fcharset161 Arial;} = Greek)
  const fontCp={}; { const ft=rtf.indexOf('{\\fonttbl'); if(ft>=0){ const re=/\{\\f(\d+)[^{};]*?\\fcharset(\d+)/g; re.lastIndex=ft; const end=ft+200000; let m; while((m=re.exec(rtf)) && m.index<end){ const c=RTF_CHARSET_CP[+m[2]]; if(c) fontCp[m[1]]=cpLabel(c); } } }
  const curCp=()=> (st.font!=null && fontCp[st.font]) || cp;
  const emit=s=>{ if(skipBytes>0){ skipBytes--; return; } if(st.skip) return; if(opts.html){ if(st.htmlrtf && !st.tag) return; } out+=s; };
  const bytes=[]; const flush=()=>{ if(bytes.length){ const s=_ansi(Uint8Array.from(bytes), curCp()); bytes.length=0; if(!st.skip && !(opts.html&&st.htmlrtf&&!st.tag)) out+=s; } };
  while(i<rtf.length){
    const c=rtf[i];
    if(c==='{'){ flush(); stack.push({...st, uc}); i++; continue; }
    if(c==='}'){ flush(); const s=stack.pop(); if(s){ uc=s.uc; delete s.uc; st=s; } i++; continue; }
    if(c==='\\'){
      const n=rtf[i+1];
      if(n==="'"){ const h=parseInt(rtf.substr(i+2,2),16); i+=4; if(skipBytes>0){ skipBytes--; continue; } if(!isNaN(h)) bytes.push(h); continue; }
      flush();
      if(n==='\\'||n==='{'||n==='}'){ emit(n); i+=2; continue; }
      if(n==='*'){ i+=2; // {\* destination}: ignored unless it is an HTML tag Outlook kept
        const m=/^\\([a-z]+)/i.exec(rtf.slice(i,i+40)); if(m && m[1]==='htmltag' && opts.html){ st.tag=true; } else st.skip=true; continue; }
      if(n==='~'){ emit(' '); i+=2; continue; }
      if(n==='-'||n==='_'){ i+=2; continue; }
      if(n==='\r'||n==='\n'){ emit('\n'); i+=2; continue; }
      const m=/^\\([a-z]{1,32})(-?\d{1,10})? ?/i.exec(rtf.slice(i,i+48));
      if(!m){ i+=2; continue; }
      i+=m[0].length; const w=m[1], arg=m[2]!==undefined?+m[2]:null;
      if(SKIP.has(w)){ st.skip=true; continue; }
      if(w==='htmlrtf'){ st.htmlrtf = arg!==0; continue; }
      if(w==='htmltag'){ st.tag=true; continue; }
      if(w==='uc'){ uc=arg||0; continue; }
      if(w==='ansicpg'){ cp=cpLabel(arg)||cp; continue; }
      if(w==='f' && arg!=null){ st.font=String(arg); continue; }
      if(w==='u'){ let cc=arg; if(cc<0) cc+=65536; emit(String.fromCharCode(cc)); skipBytes=uc; continue; }
      if(opts.html && st.htmlrtf) continue;
      if(w==='par'||w==='line'||w==='sect'||w==='page') emit('\n');
      else if(w==='tab') emit('\t');
      else if(w==='emdash') emit('—'); else if(w==='endash') emit('–');
      else if(w==='lquote') emit('‘'); else if(w==='rquote') emit('’'); else if(w==='ldblquote') emit('“'); else if(w==='rdblquote') emit('”'); else if(w==='bullet') emit('•');
      continue;
    }
    if(c==='\r'||c==='\n'){ i++; continue; }
    if(skipBytes>0){ skipBytes--; i++; continue; }
    bytes.push(c.charCodeAt(0)&255); i++;
  }
  flush();
  return opts.html ? out : out.replace(/[ \t]+\n/g,'\n').replace(/\n{3,}/g,'\n\n').trim();
}
