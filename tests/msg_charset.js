// Greek / code page decoding of .msg email bodies. Usage: node tests/msg_charset.js src/js/15-msg.js [folder with sample .msg files]
const fs=require('fs'); eval(fs.readFileSync(process.argv[2],'utf8')+';global.X={decodeHtmlBytes,rtfToText,parseMsg};');
const enc=(s,map)=>Uint8Array.from([...s].map(c=>c.charCodeAt(0)<128?c.charCodeAt(0):map[c]));
const g1253={'Κ':0xca,'α':0xe1,'λ':0xeb,'η':0xe7,'μ':0xec,'έ':0xdd,'ρ':0xf1,'ε':0xe5};   // same bytes in iso-8859-7 for these letters except έ
const word='Καλημέρα';
let ok=0,n=0; const t=(name,got,want)=>{ n++; const p=got.includes(want); ok+=p; console.log((p?'PASS ':'FAIL ')+name, p?'':JSON.stringify(got.slice(0,200))); };
t('meta windows-1253', X.decodeHtmlBytes(enc(`<html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1253"></head><body>${word}</body>`,g1253),{},'windows-1252'), word);
t('meta iso-8859-7 (Καλημερα)', X.decodeHtmlBytes(enc('<meta charset="iso-8859-7"><p>Καλημερα</p>',g1253),{},'windows-1252'), 'Καλημερα');
t('no meta, internet code page 1253', X.decodeHtmlBytes(enc(`<p>${word}</p>`,g1253),{'3FDE':1253},'windows-1252'), word);
t('no meta, message code page 1253', X.decodeHtmlBytes(enc(`<p>${word}</p>`,g1253),{'3FFD':1253},'windows-1252'), word);
t('no meta, UTF-8', X.decodeHtmlBytes(new TextEncoder().encode(`<p>${word}</p>`),{},'windows-1252'), word);
t('meta utf-8', X.decodeHtmlBytes(new TextEncoder().encode(`<meta charset="utf-8"><p>${word}</p>`),{'3FDE':1252},'windows-1252'), word);
const hex=s=>[...s].map(c=>"\\'"+g1253[c].toString(16)).join('');
t('RTF-wrapped HTML, Greek font (fcharset161) with ansicpg1252', X.rtfToText(`{\\rtf1\\ansi\\ansicpg1252\\fromhtml1 \\deff0{\\fonttbl{\\f0\\fswiss\\fcharset0 Arial;}{\\f1\\fswiss\\fcharset161 Arial;}}{\\*\\htmltag19 <body>}\\htmlrtf {\\htmlrtf0 \\f1 ${hex(word)}}\\htmlrtf0 {\\*\\htmltag3 </body>}}`,{html:true}), word);
t('RTF text, ansicpg1253', X.rtfToText(`{\\rtf1\\ansi\\ansicpg1253\\deff0{\\fonttbl{\\f0\\fnil Arial;}}\\f0 ${hex(word)}\\par}`), word);
t('RTF text, Greek font only', X.rtfToText(`{\\rtf1\\ansi\\ansicpg1252{\\fonttbl{\\f0\\fnil\\fcharset0 Arial;}{\\f2\\fnil\\fcharset161 Arial Greek;}}\\f0 Hi {\\f2 ${hex(word)}} \\f0 end\\par}`), 'Hi '+word+' end');
t('RTF \\u escapes', X.rtfToText('{\\rtf1\\ansi\\uc1 \\u922?\\u945?\\u955?\\par}'), 'Καλ');
if(process.argv[3]) for(const f of fs.readdirSync(process.argv[3]).filter(f=>f.endsWith('.msg'))){ const m=X.parseMsg(new Uint8Array(fs.readFileSync(process.argv[3]+'/'+f))); n++; const p=m.subject && (m.text||m.html) && !/[ÃÂ]\W/.test(m.html||''); ok+=!!p; if(!p) console.log('FAIL sample',f); }
console.log(`${ok}/${n} passed`); process.exit(ok===n?0:1);
