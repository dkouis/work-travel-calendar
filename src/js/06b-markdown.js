/* ============================================================
   Notes in Markdown (CommonMark, via markdown-it 14.1.0)
   - Raw HTML is disabled, so notes cannot inject markup or scripts.
   - Unsafe link schemes (javascript:, vbscript:, file:) are rejected
     by markdown-it; links open in a new tab.
   - Extras on top of CommonMark: tables, ~~strikethrough~~ and
     automatic links for bare URLs.
   ============================================================ */
const MD = (()=>{
  if(typeof markdownit!=='function') return null;
  const m = markdownit({html:false, linkify:true, typographer:false, breaks:false});
  const def = m.renderer.rules.link_open || ((t,i,o,e,self)=>self.renderToken(t,i,o));
  m.renderer.rules.link_open = (t,i,o,e,self)=>{ t[i].attrSet('target','_blank'); t[i].attrSet('rel','noopener noreferrer'); return def(t,i,o,e,self); };
  return m;
})();
function md(s){
  s=String(s||''); if(!s.trim()) return '';
  if(MD) try{ return MD.render(s); }catch(e){}
  return '<p>'+esc(s).replace(/\n/g,'<br>')+'</p>';   // fallback: plain text
}

/* ---------- editor: toolbar + Write / Preview ---------- */
const MD_TOOLS = [
  ['bold','B','Bold (Ctrl+B)','font-weight:800'], ['italic','I','Italic (Ctrl+I)','font-style:italic;font-family:serif'], ['strike','S','Strikethrough','text-decoration:line-through'],
  ['|'], ['h','H','Heading (click again for a smaller one)','font-weight:800'], ['quote','❝','Quote',''], ['code','‹›','Inline code','font-family:monospace'], ['codeblock','{ }','Code block','font-family:monospace'],
  ['|'], ['ul','•','Bulleted list',''], ['ol','1.','Numbered list',''], ['link','🔗','Link (Ctrl+K)',''], ['table','▦','Table',''], ['hr','―','Horizontal line','']
];
function mdEditorHTML(name, value, opts={}){
  const ro=!!opts.ro, id='mde'+uid('');
  if(ro) return `<div class="mde ro"><div class="md mdprev">${md(value)||'<span class="hint">No notes.</span>'}</div><textarea name="${name}" hidden>${esc(value||'')}</textarea></div>`;
  return `<div class="mde" id="${id}">
    <div class="mde-bar" role="toolbar" aria-label="Formatting">
      ${MD_TOOLS.map(t=>t[0]==='|'?'<span class="mde-sep"></span>':`<button type="button" data-md="${t[0]}" title="${esc(t[2])}" aria-label="${esc(t[2])}" style="${t[3]}">${t[1]}</button>`).join('')}
      <span class="sp"></span>
      <div class="seg mde-tabs"><button type="button" data-mdtab="write" class="on">Write</button><button type="button" data-mdtab="preview">Preview</button></div>
    </div>
    <textarea name="${name}" rows="${opts.rows||6}" placeholder="${esc(opts.placeholder||'Notes… Markdown is supported: **bold**, *italic*, # heading, - list, [link](https://…)')}" spellcheck="true">${esc(value||'')}</textarea>
    <div class="md mdprev" hidden></div>
    <div class="mde-foot hint">Markdown (CommonMark). A blank line starts a new paragraph. <a href="https://commonmark.org/help/" target="_blank" rel="noopener noreferrer">Syntax help</a></div>
  </div>`;
}
function bindMdEditor(root){
  for(const box of $$('.mde:not(.ro)',root)){
    const ta=$('textarea',box), prev=$('.mdprev',box);
    const showTab=t=>{ $$('[data-mdtab]',box).forEach(b=>b.classList.toggle('on',b.dataset.mdtab===t));
      if(t==='preview'){ prev.innerHTML=md(ta.value)||'<span class="hint">Nothing to preview.</span>'; prev.style.minHeight=ta.offsetHeight+'px'; }
      prev.hidden=t!=='preview'; ta.hidden=t==='preview'; $$('[data-md]',box).forEach(b=>b.disabled=t==='preview'); if(t==='write') ta.focus(); };
    $$('[data-mdtab]',box).forEach(b=>b.onclick=()=>showTab(b.dataset.mdtab));
    $$('[data-md]',box).forEach(b=>{ b.onmousedown=e=>e.preventDefault(); b.onclick=()=>mdAction(ta,b.dataset.md); });
    ta.addEventListener('keydown',e=>{
      if(!(e.ctrlKey||e.metaKey) || e.altKey) return;
      const k=e.key.toLowerCase(), a={b:'bold',i:'italic',k:'link'}[k];
      if(a && !e.shiftKey){ e.preventDefault(); e.stopPropagation(); mdAction(ta,a); }
    });
    // continue lists on Enter
    ta.addEventListener('keydown',e=>{
      if(e.key!=='Enter' || e.shiftKey || e.ctrlKey || e.metaKey || ta.selectionStart!==ta.selectionEnd) return;
      const v=ta.value, p=ta.selectionStart, ls=v.lastIndexOf('\n',p-1)+1, line=v.slice(ls,p);
      const m=/^(\s*)(?:([-*+])|(\d+)([.)]))\s+(.*)$/.exec(line); if(!m) return;
      e.preventDefault();
      if(!m[5].trim()){ ta.setRangeText('\n', ls, p, 'end');   /* blank line, so the next text is not part of the list */ ta.dispatchEvent(new Event('input',{bubbles:true})); return; }   // Enter on an empty item ends the list
      mdInsert(ta,'\n'+m[1]+(m[2]?m[2]:(+m[3]+1)+m[4])+' ');
    });
  }
}
/* insert text keeping the browser's undo history where possible */
function mdInsert(ta, text, selFrom, selTo){
  const s=ta.selectionStart; ta.focus();
  if(text===''){ ta.setRangeText('', ta.selectionStart, ta.selectionEnd, 'end'); ta.dispatchEvent(new Event('input',{bubbles:true})); return; }
  let ok=false; try{ ok=document.execCommand('insertText', false, text); }catch(e){}
  if(!ok || ta.value.slice(s, s+text.length)!==text){ ta.setRangeText(text, ta.selectionStart, ta.selectionEnd, 'end'); }
  if(selFrom!=null) ta.setSelectionRange(s+selFrom, s+(selTo??selFrom));
  ta.dispatchEvent(new Event('input',{bubbles:true}));
}
function mdAction(ta, a){
  const v=ta.value; let s=ta.selectionStart, e=ta.selectionEnd; const sel=v.slice(s,e);
  const wrap=(l,r,ph)=>{
    if(v.slice(s-l.length,s)===l && v.slice(e,e+r.length)===r){ ta.setSelectionRange(s-l.length,e+r.length); mdInsert(ta,sel,0,sel.length); return; } // toggle off
    if(sel.startsWith(l) && sel.endsWith(r) && sel.length>=l.length+r.length){ const inner=sel.slice(l.length,sel.length-r.length); mdInsert(ta,inner,0,inner.length); return; }
    const t=sel||ph; mdInsert(ta,l+t+r,l.length,l.length+t.length);
  };
  // whole lines touched by the selection
  const lines=()=>{ const ls=v.lastIndexOf('\n',s-1)+1; let le=v.indexOf('\n',e>s&&v[e-1]==='\n'?e-1:e); if(le<0) le=v.length; return [ls,le]; };
  const prefixLines=(fn,test,strip)=>{
    const [ls,le]=lines(); const L=v.slice(ls,le).split('\n'); const filled=L.filter(x=>x.trim()); const all=filled.length>0 && filled.every(x=>test.test(x));   // toggle off only if every non-empty line already has it
    const out=L.map((x,i)=>all?x.replace(strip,''):(x.trim()||L.length===1?fn(x.replace(strip,''),i):x)).join('\n');
    ta.setSelectionRange(ls,le);
    if(L.length===1) mdInsert(ta,out,out.length); else mdInsert(ta,out,0,out.length);   // one line: cursor at the end, ready to type
  };
  const block=t=>{ const before=s>0&&v[s-1]!=='\n'?'\n\n':(s>1&&v[s-2]!=='\n'&&s>0?'\n':''); const after=v[e]&&v[e]!=='\n'?'\n\n':(v[e]==='\n'&&v[e+1]!=='\n'?'\n':''); return [before,after]; };
  switch(a){
    case 'bold': return wrap('**','**','bold text');
    case 'italic': return wrap('*','*','italic text');
    case 'strike': return wrap('~~','~~','text');
    case 'code': return sel.includes('\n') ? mdAction(ta,'codeblock') : wrap('`','`','code');
    case 'h': { const [ls,le]=lines(); const line=v.slice(ls,le); const m=/^(#{1,6})\s+/.exec(line); const lvl=m?m[1].length:0;
      const body=line.replace(/^#{1,6}\s+/,''), nl=lvl>=3?0:lvl+1, out=(nl?'#'.repeat(nl)+' ':'')+(body||'Heading');
      ta.setSelectionRange(ls,le); mdInsert(ta,out,out.length-(body||'Heading').length,out.length); return; }
    case 'quote': return prefixLines(x=>'> '+x,/^>\s?/,/^>\s?/);
    case 'ul': return prefixLines(x=>'- '+x,/^\s*[-*+]\s+/,/^\s*(?:[-*+]|\d+[.)])\s+/);
    case 'ol': return prefixLines((x,i)=>(i+1)+'. '+x,/^\s*\d+[.)]\s+/,/^\s*(?:[-*+]|\d+[.)])\s+/);
    case 'link': { const isUrl=/^(https?:\/\/|mailto:)\S+$/i.test(sel.trim());
      if(isUrl){ const t='[link text]('+sel.trim()+')'; return mdInsert(ta,t,1,10); }
      const t='['+(sel||'link text')+'](https://)'; const u=t.indexOf('(https://')+1; return mdInsert(ta,t,u,u+8); }
    case 'codeblock': { const [b,af]=block(); const body=sel||'code'; const t=b+'```\n'+body+'\n```'+af; return mdInsert(ta,t,b.length+4,b.length+4+body.length); }
    case 'hr': { const [b,af]=block(); const t=b+'---'+(af||'\n\n'); return mdInsert(ta,t,t.length); }
    case 'table': { const [b,af]=block(); const t=b+'| Column 1 | Column 2 |\n| --- | --- |\n| Text | Text |'+af; return mdInsert(ta,t,b.length+2,b.length+10); }
  }
}
