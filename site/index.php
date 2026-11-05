<?php
// Work & Travel Calendar (PHP version, v2 planner). The page itself holds no data:
// it signs in and loads/saves the calendar through api.php.
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; manifest-src 'self'; worker-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Work &amp; Travel Calendar</title>
<link rel="manifest" href="manifest.webmanifest">
<link rel="icon" href="icon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="icon-192.png">
<meta name="theme-color" content="#1f3a5f">
<style>

:root{
  --bg:#f4f1ea; --paper:#fffdf8; --ink:#1d1f24; --muted:#6b6f78; --line:#e2ddd2; --line2:#d3ccbe;
  --weekend:#efeadf; --accent:#1f3a5f; --today:#d9480f; --sel:#ffe8a3;
  --fill-ath-tele:#8b1a1a; --fill-ath-abroad:#b2f2bb;
  --office:#3b5bdb; --tele:#2f9e44; --abroad:#7048e8; --leave:#e8590c; --sick:#c92a2a; --holiday:#868e96;
  --shadow:0 1px 2px rgba(0,0,0,.05),0 6px 24px rgba(0,0,0,.06);
}
@media (prefers-color-scheme: dark){
  :root{ --bg:#16181d; --paper:#1f2228; --ink:#e9e6df; --muted:#9aa0aa; --line:#2e323a; --line2:#3a3f48;
    --weekend:#1a1d22; --accent:#9cc0ef; --today:#ff8a4c; --sel:#5a4a14; --fill-ath-tele:#7a1616; --fill-ath-abroad:#1f4d2a; --shadow:0 1px 2px rgba(0,0,0,.3),0 6px 24px rgba(0,0,0,.3);}
}
*{box-sizing:border-box}
html,body{margin:0;background:var(--bg);color:var(--ink);font:14px/1.4 "Segoe UI",system-ui,-apple-system,"Helvetica Neue",Arial,sans-serif}
button,input,textarea,select{font:inherit;color:inherit}
button{cursor:pointer;border:1px solid var(--line2);background:var(--paper);border-radius:8px;padding:6px 11px}
button:hover:not(:disabled){border-color:var(--ink)}
button:disabled{opacity:.45;cursor:default}
button.primary{background:var(--accent);color:var(--paper);border-color:var(--accent);font-weight:600}
button.ghost{border-color:transparent;background:transparent}
input[type=text],input[type=password],select,textarea{border:1px solid var(--line2);background:var(--paper);border-radius:8px;padding:7px 9px;width:100%}
textarea{resize:vertical;min-height:80px}
input:disabled,textarea:disabled{opacity:.8;background:transparent}

/* top bar */
.topbar{position:sticky;top:0;z-index:20;display:flex;flex-wrap:wrap;gap:10px 16px;align-items:center;padding:12px 20px;background:var(--bg);border-bottom:1px solid var(--line)}
.brand{font-weight:700;letter-spacing:.2px;font-size:15px;display:flex;align-items:center;gap:8px}
.brand .dot{width:10px;height:10px;border-radius:3px;background:var(--accent);display:inline-block}
.brand small{font-weight:400;color:var(--muted)}
.nav{display:flex;align-items:center;gap:6px}
.nav h1{font-size:20px;margin:0 0 0 8px;font-weight:650;letter-spacing:-.2px;white-space:nowrap}
.seg{display:inline-flex;border:1px solid var(--line2);border-radius:9px;overflow:hidden}
.seg button{border:0;border-radius:0;padding:6px 14px;background:var(--paper)}
.seg button.on{background:var(--ink);color:var(--paper)}
.spacer{flex:1}
.filebox{display:flex;align-items:center;gap:8px;position:relative}
#saveState{font-size:12px;color:var(--muted);white-space:nowrap}
#saveState.ok{color:var(--tele)} #saveState.err{color:var(--sick);font-weight:600}
.menu{position:absolute;right:0;top:40px;background:var(--paper);border:1px solid var(--line2);border-radius:10px;box-shadow:var(--shadow);padding:6px;min-width:250px;display:none;z-index:40}
.menu.open{display:block}
.menu button{display:block;width:100%;text-align:left;border:0;background:transparent;padding:8px 10px;border-radius:6px}
.menu button:hover:not(:disabled){background:var(--weekend)}
.menu hr{border:0;border-top:1px solid var(--line);margin:4px 0}

#banner{margin:12px 20px 0;padding:10px 14px;border-radius:10px;background:var(--paper);border:1px solid var(--line);display:none;font-size:13px}
#banner.show{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
#banner.warn{border-color:var(--leave)}

main{padding:16px 20px 90px}
.legend{display:flex;flex-wrap:wrap;gap:6px 14px;font-size:12px;color:var(--muted);margin:0 0 12px}
.legend span{display:inline-flex;align-items:center;gap:6px}
.sw{width:12px;height:12px;border-radius:3px;display:inline-block}

/* status pill */
.st{display:flex;align-items:center;gap:6px;border-radius:6px;padding:2px 7px;color:#fff;font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-height:20px}
.st .h{opacity:.8;font-weight:500;font-size:10.5px;letter-spacing:.3px}
.st.office{background:var(--office)} .st.tele{background:var(--tele)} .st.abroad{background:var(--abroad)}
.st.leave{background:var(--leave)} .st.sick{background:var(--sick)} .st.holiday{background:var(--holiday)}
.st.none{background:transparent;color:var(--muted);border:1px dashed var(--line2);font-weight:500}
.loc{display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:600;color:var(--accent);background:color-mix(in srgb,var(--office) 15%,transparent);border-radius:20px;padding:1px 8px;max-width:100%;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.loc.auto{font-weight:500;opacity:.75;background:transparent;border:1px dashed color-mix(in srgb,var(--accent) 40%,transparent)}
.fl{display:flex;align-items:center;gap:5px;font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fl .t{font-weight:500;color:var(--muted)}
.cm{font-size:12px;color:var(--muted);overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;white-space:pre-wrap;word-break:break-word}

/* month */
.month{background:var(--paper);border:1px solid var(--line);border-radius:14px;box-shadow:var(--shadow);overflow:hidden}
.mhead,.mrow{display:grid;grid-template-columns:repeat(5,1fr) .8fr .8fr}
.mhead div{padding:9px 10px;font-size:11px;font-weight:700;letter-spacing:.8px;color:var(--muted);border-bottom:1px solid var(--line)}
.mhead div:nth-child(n+6){background:var(--weekend)}
.cell{position:relative;min-height:122px;border-right:1px solid var(--line);border-bottom:1px solid var(--line);padding:6px 7px 7px;display:flex;flex-direction:column;gap:4px;cursor:pointer;min-width:0}
.mrow .cell:nth-child(7){border-right:0}
.mrow:last-child .cell{border-bottom:0}
.cell.we{background:var(--weekend)}
.cell.out{opacity:.38}
.cell:hover{outline:2px solid var(--line2);outline-offset:-2px}
.cell.fill-tele,.wcol.fill-tele{background:var(--fill-ath-tele)!important}
.cell.fill-abroad,.wcol.fill-abroad{background:var(--fill-ath-abroad)!important}
.fill-tele .st.tele,.fill-tele .st.abroad,.fill-abroad .st.tele,.fill-abroad .st.abroad,
.fill-tele .st.none,.fill-abroad .st.none,.fill-tele .fl,.fill-abroad .fl{background:rgba(255,255,255,.35);color:var(--ink);border-color:rgba(0,0,0,.12)}
.fill-tele .loc,.fill-abroad .loc{background:rgba(255,255,255,.45)}
/* dark red fill: light text for readability */
.fill-tele,.fill-tele .num,.fill-tele .wk,.fill-tele .cm,.fill-tele .lbl,.fill-tele .dn,.fill-tele .empty,.fill-tele .fl .t{color:#fff!important}
.fill-tele .st.tele,.fill-tele .st.abroad,.fill-tele .st.none,.fill-tele .fl{background:rgba(255,255,255,.16);color:#fff;border-color:rgba(255,255,255,.3)}
.fill-tele .loc{background:rgba(255,255,255,.18);color:#fff;border-color:rgba(255,255,255,.45)}
.cell.sel{background:var(--sel)}
.cell .num{display:flex;align-items:center;justify-content:space-between;font-size:13px;font-weight:650}
.cell .num .wk{font-size:10px;color:var(--muted);font-weight:500}
.cell.today .num b{background:var(--today);color:#fff;border-radius:20px;padding:0 7px}
.halves{display:flex;flex-direction:column;gap:2px}

.below{display:grid;grid-template-columns:1.4fr 1fr;gap:16px;margin-top:16px}
.panel{background:var(--paper);border:1px solid var(--line);border-radius:14px;padding:14px 16px;box-shadow:var(--shadow)}
.panel h3{margin:0 0 10px;font-size:13px;letter-spacing:.6px;text-transform:uppercase;color:var(--muted)}
.stats{display:grid;grid-template-columns:auto 1fr auto;gap:6px 10px;align-items:center;font-size:13px}
.bar{height:8px;border-radius:5px;background:var(--line)}
.bar i{display:block;height:100%;border-radius:5px}
.stats .n{font-variant-numeric:tabular-nums;font-weight:650;text-align:right}
.sub{font-size:12px;color:var(--muted);margin-top:10px}

/* week */
.week{display:grid;grid-template-columns:repeat(5,1fr) .85fr .85fr;gap:10px}
.wcol{background:var(--paper);border:1px solid var(--line);border-radius:14px;box-shadow:var(--shadow);padding:12px;display:flex;flex-direction:column;gap:10px;min-height:420px;cursor:pointer;min-width:0}
.wcol.we{background:var(--weekend)}
.wcol.sel{background:var(--sel)}
.wcol:hover{border-color:var(--line2)}
.wcol.today{border-color:var(--today);border-width:2px}
.wcol .dh{display:flex;align-items:baseline;justify-content:space-between;gap:6px}
.wcol .dh .dn{font-size:12px;font-weight:700;letter-spacing:.8px;color:var(--muted)}
.wcol .dh .dd{font-size:24px;font-weight:700;letter-spacing:-.5px}
.wcol .lbl{font-size:10.5px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-transform:uppercase;margin-bottom:3px}
.wcol .st{font-size:13px;padding:6px 9px;min-height:30px}
.wcol .fl{font-size:13px;padding:6px 8px;border:1px solid var(--line);border-radius:8px;background:var(--paper)}
.wcol .cm{-webkit-line-clamp:8;font-size:13px;color:var(--ink)}
.wcol .empty{font-size:12px;color:var(--muted);font-style:italic}

/* drawer */
#scrim{position:fixed;inset:0;background:rgba(0,0,0,.22);opacity:0;pointer-events:none;transition:.15s;z-index:50}
#scrim.open{opacity:1;pointer-events:auto}
#drawer{position:fixed;top:0;right:0;bottom:0;width:min(440px,100vw);background:var(--paper);border-left:1px solid var(--line);box-shadow:-10px 0 40px rgba(0,0,0,.12);transform:translateX(105%);transition:transform .18s ease;z-index:60;display:flex;flex-direction:column}
#drawer.open{transform:none}
.dhead{display:flex;align-items:center;gap:6px;padding:14px 16px;border-bottom:1px solid var(--line)}
.dhead h2{flex:1;margin:0;font-size:17px;font-weight:650}
.dhead h2 small{display:block;font-size:12px;font-weight:500;color:var(--muted)}
.dbody{overflow:auto;padding:14px 16px 30px;display:flex;flex-direction:column;gap:18px}
.sec h4{margin:0 0 8px;font-size:11.5px;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);display:flex;justify-content:space-between}
.half{display:grid;grid-template-columns:78px 1fr;gap:8px;align-items:start;margin-bottom:8px}
.half > span{font-size:12.5px;font-weight:600;padding-top:6px}
.chips{display:flex;flex-wrap:wrap;gap:5px}
.chip{padding:5px 9px;font-size:12.5px;border-radius:7px;display:inline-flex;align-items:center;gap:6px}
.chip .sw{width:9px;height:9px}
.chip.on{color:#fff;font-weight:600;border-color:transparent}
.chip.on .sw{background:#fff!important}
.chip.loc-on{background:var(--accent);color:var(--paper);border-color:var(--accent);font-weight:600}
.flight{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:6px;border:1px solid var(--line);border-radius:10px;padding:9px;margin-bottom:8px}
.flight .route{font-weight:650;display:flex;align-items:center;gap:6px}
.flight .route input{width:0;flex:1;min-width:0;padding:4px 6px}
.flight .row2{grid-column:1/3;display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.flight .row2 input{flex:1 1 140px;width:0;min-width:0;padding:4px 7px;font-size:12.5px}
.hint{font-size:12px;color:var(--muted)}
.ro-note{font-size:12px;background:var(--weekend);border-radius:8px;padding:8px 10px;color:var(--muted)}

/* selection bar */
#selbar{position:fixed;left:50%;bottom:16px;transform:translateX(-50%) translateY(140%);background:var(--ink);color:var(--paper);border-radius:14px;padding:10px 12px;display:flex;flex-wrap:wrap;gap:6px;align-items:center;box-shadow:0 10px 30px rgba(0,0,0,.3);transition:transform .18s,visibility .18s;visibility:hidden;z-index:45;max-width:calc(100vw - 24px)}
#selbar.open{transform:translateX(-50%);visibility:visible}
#selbar button{background:transparent;color:var(--paper);border-color:rgba(255,255,255,.25);font-size:12.5px;padding:5px 9px}
#selbar button:hover{border-color:#fff}
#selbar b{margin-right:6px}
#selbar .sep{width:1px;height:22px;background:rgba(255,255,255,.25);margin:0 4px}

.help{font-size:12px;color:var(--muted);margin-top:14px}
kbd{font:11px ui-monospace,Consolas,monospace;border:1px solid var(--line2);border-bottom-width:2px;border-radius:4px;padding:0 4px;background:var(--paper)}

/* login */
.lbox fieldset{border:1px solid var(--line);border-radius:12px;padding:10px 12px 12px;display:flex;flex-direction:column;gap:10px;margin:0}
.lbox legend{font-size:12.5px;font-weight:700;padding:0 6px}
#bootMsg{text-align:center;color:var(--muted)}
body.locked > *:not(#login):not(script){display:none!important}
#login{display:none;min-height:100vh;align-items:center;justify-content:center;padding:24px 16px}
body.locked #login{display:flex}
.lbox{width:min(380px,100%);background:var(--paper);border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow);padding:26px 24px;display:flex;flex-direction:column;gap:12px}
.lbox .brand{font-size:17px;margin-bottom:2px}
.lbox label{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)}
.lbox label.chk{flex-direction:row;align-items:center;gap:8px;text-transform:none;letter-spacing:0;font-weight:500;font-size:13px}
.lbox input[type=text],.lbox input[type=password]{font-size:15px;padding:9px 11px;text-transform:none;letter-spacing:0;font-weight:400;color:var(--ink)}
.lbox button[type=submit]{padding:10px;font-size:15px}
.lerr{color:var(--sick);font-size:13px;font-weight:600;min-height:18px}
.badge{font-size:12px;border:1px solid var(--line2);border-radius:20px;padding:3px 10px;white-space:nowrap;color:var(--muted)}
.badge b{color:var(--ink)}

/* generic modal (accounts, publish help) */
#modal{position:fixed;inset:0;z-index:80;display:none;align-items:flex-start;justify-content:center;padding:50px 16px;background:rgba(0,0,0,.3);overflow:auto}
#modal.open{display:flex}
#modal .rbox{width:min(640px,100%)}
#modal ol{padding-left:20px;line-height:1.6}
#modal code{background:var(--weekend);border-radius:5px;padding:1px 6px}
.acc{width:100%;border-collapse:collapse;margin:6px 0 10px}
.acc th{text-align:left;font-size:11px;letter-spacing:.6px;text-transform:uppercase;color:var(--muted);padding:6px 6px;border-bottom:1px solid var(--line2)}
.acc td{padding:6px;border-bottom:1px solid var(--line);vertical-align:middle}
.acc input,.acc select{padding:6px 8px}
.acc select{border:1px solid var(--line2);border-radius:8px;background:var(--paper);width:100%}

@media (max-width:900px){
  .week{grid-template-columns:1fr}
  .wcol{min-height:0}
  .below{grid-template-columns:1fr}
  .mhead,.mrow{grid-template-columns:repeat(7,minmax(0,1fr))}
  .cell{min-height:86px;padding:4px}
  .cell .st .h,.cell .cm{display:none}
  .nav h1{font-size:17px}
}
/* trips report */
#report{position:fixed;inset:0;z-index:70;display:none;align-items:flex-start;justify-content:center;padding:40px 16px;background:rgba(0,0,0,.28);overflow:auto}
#report.open{display:flex}
.rbox{background:var(--paper);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);width:min(860px,100%);padding:18px 20px 22px}
.rhead{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.rhead h2{flex:1;margin:0;font-size:18px}
.rctrl{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;padding:12px;border:1px solid var(--line);border-radius:12px;background:var(--bg);margin-bottom:14px}
.rctrl label{display:flex;flex-direction:column;gap:4px;font-size:11.5px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--muted)}
.rctrl input[type=date]{border:1px solid var(--line2);background:var(--paper);border-radius:8px;padding:6px 8px;font-size:14px}
.rctrl .quick{display:flex;flex-wrap:wrap;gap:5px}
.rctrl .quick button{font-size:12px;padding:5px 9px}
.rsum{font-size:13px;color:var(--muted);margin:0 0 10px}
.rsum b{color:var(--ink)}
table.rt{width:100%;border-collapse:collapse;font-size:13.5px}
table.rt th{text-align:left;font-size:11px;letter-spacing:.7px;text-transform:uppercase;color:var(--muted);padding:8px 10px;border-bottom:2px solid var(--line2)}
table.rt td{padding:8px 10px;border-bottom:1px solid var(--line);vertical-align:top}
table.rt tr:hover td{background:var(--weekend)}
table.rt td.num{font-variant-numeric:tabular-nums;white-space:nowrap}
table.rt td.route{font-weight:600}
table.rt .note{color:var(--muted);font-size:12.5px}
.rempty{padding:30px;text-align:center;color:var(--muted);border:1px dashed var(--line2);border-radius:12px}
.ractions{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;justify-content:flex-end}
.print-title{display:none}

@media print{
  body.report-open > *:not(#report){display:none!important}
  body.report-open #report{position:static;display:block;padding:0;background:none;overflow:visible}
  body.report-open .rbox{border:0;box-shadow:none;width:100%;padding:0;background:#fff;color:#000}
  body.report-open table.rt tr:hover td{background:none}
  body.report-open .rctrl,body.report-open .ractions,body.report-open .rhead button{display:none!important}
  body.report-open .print-title{display:block;font-size:12px;color:#555;margin:0 0 8px}
  table.rt tr{break-inside:avoid}
  .fill-tele,.fill-abroad{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .topbar .filebox,.topbar .seg,#prev,#next,#today,#banner,#selbar,#drawer,#scrim,.help{display:none!important}
  body,html{background:#fff}
  .topbar{position:static;border:0}
  main{padding:0}
  .month,.panel,.wcol{box-shadow:none}
  .st{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
/* ================= v2 additions: planner ================= */
:root{
  --work:#3b5bdb; --personal:#2f9e44; --hour:44px; --side:236px; --tasksw:300px;
  --p1:#c92a2a; --p2:#e8590c; --p3:#3b5bdb; --p4:#868e96; --missed:#c92a2a; --busy:#868e96;
}
.topbar{gap:8px 12px;padding:10px 16px}
.topbar .iconbtn{padding:6px 9px;min-width:34px}
.topbar .grow{flex:1 1 220px;min-width:180px;max-width:460px;position:relative}
#qa{width:100%;padding:7px 10px 7px 30px;border-radius:20px;background:var(--paper)}
.qa-ico{position:absolute;left:11px;top:7px;color:var(--muted);pointer-events:none}
#qaPrev{position:absolute;left:0;right:0;top:40px;background:var(--paper);border:1px solid var(--line2);border-radius:12px;box-shadow:var(--shadow);padding:10px 12px;z-index:45;display:none;font-size:13px}
#qaPrev.open{display:block}
#qaPrev .qrow{display:flex;flex-wrap:wrap;gap:5px;align-items:center;margin-top:6px}
.tag{display:inline-flex;align-items:center;gap:4px;border-radius:20px;padding:1px 8px;font-size:12px;background:var(--weekend);border:1px solid var(--line);white-space:nowrap}
.tag.k{font-weight:700}
.bell{position:relative}
.bell .cnt{position:absolute;top:-5px;right:-5px;background:var(--today);color:#fff;border-radius:20px;font-size:10.5px;font-weight:700;min-width:17px;height:17px;line-height:17px;text-align:center;padding:0 4px}
.dropdown{position:relative}
.dropdown .menu{top:38px}

/* layout */
.layout{display:grid;grid-template-columns:var(--side) minmax(0,1fr) var(--tasksw);gap:0;align-items:start}
body.noside .layout{grid-template-columns:0 minmax(0,1fr) var(--tasksw)}
body.notasks .layout{grid-template-columns:var(--side) minmax(0,1fr) 0}
body.noside.notasks .layout{grid-template-columns:0 minmax(0,1fr) 0}
body.noside #side, body.notasks #tasks{display:none}
#side,#tasks{position:sticky;top:var(--topH,58px);max-height:calc(100vh - var(--topH,58px));overflow:auto;padding:14px 12px 40px}
#side{border-right:1px solid var(--line)}
#tasks{border-left:1px solid var(--line)}
main{padding:14px 16px 90px;min-width:0}
.sideh{font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);margin:16px 4px 6px;display:flex;align-items:center;justify-content:space-between;gap:6px}
.sideh button{font-size:11px;padding:1px 7px;text-transform:none;letter-spacing:0}
.srow{display:flex;align-items:center;gap:7px;padding:5px 6px;border-radius:7px;cursor:pointer;font-size:13px;user-select:none;min-width:0}
.srow:hover{background:var(--weekend)}
.srow.on{background:color-mix(in srgb,var(--accent) 14%,transparent);font-weight:600}
.srow.off{opacity:.45}
.srow .nm{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.srow .c{font-size:11px;color:var(--muted);font-variant-numeric:tabular-nums}
.srow .od{font-size:10.5px;background:var(--sick);color:#fff;border-radius:20px;padding:0 6px;font-weight:700}
.srow .bw{font-size:10.5px;background:var(--leave);color:#fff;border-radius:20px;padding:0 6px;font-weight:700}
.srow.sub{padding-left:22px}
.srow.dragover{outline:2px dashed var(--accent)}
.dotc{width:10px;height:10px;border-radius:3px;flex:none}
.cbx{width:15px;height:15px;border-radius:4px;border:1.5px solid currentColor;flex:none;display:inline-flex;align-items:center;justify-content:center;font-size:11px;line-height:1}
.mini{font-size:11.5px;user-select:none}
.mini .mh{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;font-weight:650;font-size:12.5px}
.mini .mh button{padding:0 7px;border:0;background:transparent}
.mini .g{display:grid;grid-template-columns:repeat(7,1fr);gap:1px;text-align:center}
.mini .g span{padding:3px 0;border-radius:5px;cursor:pointer;position:relative}
.mini .g span.h{color:var(--muted);font-weight:700;cursor:default;font-size:10px}
.mini .g span.o{opacity:.35}
.mini .g span.t{background:var(--today);color:#fff;font-weight:700}
.mini .g span.inv{outline:1.5px solid var(--accent)}
.mini .g span.has::after{content:"";position:absolute;bottom:1px;left:50%;width:4px;height:4px;margin-left:-2px;border-radius:2px;background:var(--accent);opacity:.6}
.lockbtn{font-size:11.5px;padding:3px 8px}

/* tasks panel */
.tp-tabs{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px}
.tp-tabs button{font-size:12px;padding:3px 8px;border-radius:20px}
.tp-tabs button.on{background:var(--ink);color:var(--paper);border-color:var(--ink)}
.tp-tabs .n{opacity:.7;margin-left:3px;font-size:11px}
.tp-ctrl{display:flex;gap:6px;margin-bottom:8px}
.tp-ctrl select{padding:4px 6px;font-size:12px}
.tl{display:flex;flex-direction:column;gap:5px}
.tgroup{font-size:11px;font-weight:700;letter-spacing:.6px;color:var(--muted);text-transform:uppercase;margin:8px 2px 2px}
.trow{display:grid;grid-template-columns:auto 1fr auto;gap:2px 8px;align-items:start;border:1px solid var(--line);border-left:4px solid var(--c,var(--line2));background:var(--paper);border-radius:9px;padding:7px 8px;cursor:pointer;font-size:13px}
.trow:hover{border-color:var(--line2)}
.trow.sel{background:var(--sel)}
.trow.done .tt{text-decoration:line-through;opacity:.6}
.trow .tt{font-weight:600;min-width:0;overflow:hidden;text-overflow:ellipsis}
.trow .meta{grid-column:2/4;display:flex;flex-wrap:wrap;gap:4px 8px;font-size:11.5px;color:var(--muted)}
.trow .meta .od{color:var(--sick);font-weight:700}
.trow .meta .warn{color:var(--leave);font-weight:700}
.trow .pk{margin-top:1px}
.check{width:17px;height:17px;border-radius:50%;border:2px solid var(--c,var(--muted));display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex:none;font-size:10px;color:#fff;background:transparent;padding:0}
.check.on{background:var(--c,var(--tele))}
.pri{font-size:10px;font-weight:800;border-radius:4px;padding:0 4px;color:#fff}
.pri.p1{background:var(--p1)} .pri.p2{background:var(--p2)} .pri.p3{background:var(--p3)} .pri.p4{background:var(--p4)}
.today-sum{border:1px solid var(--line);border-radius:10px;background:var(--paper);padding:10px;margin-bottom:12px;font-size:12.5px}
.today-sum b{font-size:13px}
.today-sum .row{display:flex;justify-content:space-between;gap:6px;margin-top:3px;color:var(--muted)}

/* view toolbar */
.vtools{display:flex;flex-wrap:wrap;gap:6px 10px;align-items:center;margin:0 0 10px;font-size:12px}
.vtools .seg button{padding:4px 10px;font-size:12px}
.vtools label{display:inline-flex;align-items:center;gap:5px;color:var(--muted)}
.vtools select{padding:3px 6px;font-size:12px;width:auto}
.legend{margin:0}
.legend.hide{display:none}

/* time grid */
.tg{background:var(--paper);border:1px solid var(--line);border-radius:14px;box-shadow:var(--shadow);overflow:hidden}
.tg-head{display:grid;border-bottom:1px solid var(--line);position:relative}
.tg-hcell{padding:6px 6px 6px;border-left:1px solid var(--line);min-width:0;display:flex;flex-direction:column;gap:3px;cursor:pointer}
.tg-hcell.we{background:var(--weekend)}
.tg-hcell .dh{display:flex;align-items:baseline;gap:6px}
.tg-hcell .dn{font-size:11px;font-weight:700;letter-spacing:.8px;color:var(--muted)}
.tg-hcell .dd{font-size:19px;font-weight:700}
.tg-hcell.today .dd{background:var(--today);color:#fff;border-radius:20px;padding:0 8px}
.tg-hcell .st{font-size:10.5px;padding:1px 5px;min-height:16px}
.tg-hcell .halves{flex-direction:row}
.tg-hcell .halves .st{flex:1;min-width:0}
.tg-gut{font-size:10.5px;color:var(--muted);padding:4px;text-align:right}
.tg-all{display:grid;border-bottom:1px solid var(--line2);min-height:28px}
.tg-allc{border-left:1px solid var(--line);padding:3px;display:flex;flex-direction:column;gap:2px;min-width:0}
.tg-allc.we{background:var(--weekend)}
.tg-body{position:relative;overflow-y:auto;max-height:calc(100vh - var(--topH,58px) - 210px);min-height:360px}
.tg-grid{display:grid;position:relative}
.tg-hours{position:relative}
.tg-hours div{height:var(--hour);font-size:10.5px;color:var(--muted);text-align:right;padding-right:6px;transform:translateY(-7px);white-space:nowrap}
.tg-hours div span{display:block;font-size:9.5px;opacity:.75}
.tg-col{position:relative;border-left:1px solid var(--line);background-image:linear-gradient(var(--line) 1px,transparent 1px);background-size:100% var(--hour);touch-action:pan-y}
.tg-col.we{background-color:var(--weekend)}
.tg-off{position:absolute;left:0;right:0;background:color-mix(in srgb,var(--ink) 4%,transparent);pointer-events:none}
.tg-col.leave .tg-off,.tg-col.leave{background-color:color-mix(in srgb,var(--leave) 6%,transparent)}
.tg-now{position:absolute;left:0;right:0;height:2px;background:var(--today);z-index:6;pointer-events:none}
.tg-now::before{content:"";position:absolute;left:-5px;top:-4px;width:10px;height:10px;border-radius:5px;background:var(--today)}
.tg-ghost{position:absolute;left:3px;right:3px;background:color-mix(in srgb,var(--accent) 22%,transparent);border:1.5px dashed var(--accent);border-radius:7px;z-index:7;pointer-events:none;font-size:11px;padding:2px 5px;font-weight:600}
.tg-focus{position:absolute;left:2px;right:2px;height:calc(var(--hour) / 2);outline:2px solid var(--accent);border-radius:4px;pointer-events:none;z-index:8}
.ev{position:absolute;border-radius:7px;padding:2px 5px 2px 6px;font-size:11.5px;line-height:1.25;overflow:hidden;cursor:pointer;z-index:4;
  background:color-mix(in srgb,var(--c) 20%,var(--paper));border:1px solid color-mix(in srgb,var(--c) 45%,transparent);border-left:3px solid var(--c);color:var(--ink);user-select:none}
.ev:hover{z-index:9;box-shadow:var(--shadow)}
.ev:focus-visible{outline:2px solid var(--accent);outline-offset:1px}
.ev .tm{font-size:10.5px;opacity:.8;font-variant-numeric:tabular-nums}
.ev .ti{font-weight:650}
.ev .lo{opacity:.75}
.ev.task{background:repeating-linear-gradient(135deg,color-mix(in srgb,var(--c) 22%,var(--paper)) 0 6px,color-mix(in srgb,var(--c) 10%,var(--paper)) 6px 12px)}
.ev.task.p1{border-left-width:6px}
.ev.task.p2{border-left-width:4.5px}
.ev.done{opacity:.55}
.ev.done .ti{text-decoration:line-through}
.ev.missed{border-color:var(--missed);border-style:dashed;border-left-style:solid}
.ev.cancelled .ti{text-decoration:line-through}
.ev.cancelled{opacity:.5}
.ev.tentative{background:repeating-linear-gradient(90deg,color-mix(in srgb,var(--c) 14%,var(--paper)) 0 4px,var(--paper) 4px 8px)}
.ev.busy{--c:var(--busy);cursor:default;background:repeating-linear-gradient(45deg,color-mix(in srgb,var(--busy) 18%,var(--paper)) 0 5px,var(--paper) 5px 10px)}
.ev.conflict::after{content:"⚠";position:absolute;top:1px;right:3px;font-size:11px}
.ev .rz{position:absolute;left:0;right:0;bottom:0;height:7px;cursor:ns-resize}
.ev .check{width:13px;height:13px;border-width:1.5px;vertical-align:-2px;margin-right:3px;font-size:8px}
.ev.dragging{opacity:.85;box-shadow:0 8px 24px rgba(0,0,0,.25);z-index:20}
.chipi{display:flex;align-items:center;gap:4px;font-size:11px;border-radius:5px;padding:1px 5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer;
  background:color-mix(in srgb,var(--c,var(--accent)) 18%,var(--paper));border-left:3px solid var(--c,var(--accent));min-width:0}
.chipi.task{background:repeating-linear-gradient(135deg,color-mix(in srgb,var(--c) 20%,var(--paper)) 0 5px,color-mix(in srgb,var(--c) 8%,var(--paper)) 5px 10px)}
.chipi.due{background:transparent;border:1px solid color-mix(in srgb,var(--c) 50%,transparent);border-left:3px solid var(--c)}
.chipi.overdue{border-color:var(--sick);color:var(--sick);font-weight:700}
.chipi.flight{--c:var(--accent);background:transparent;border:1px solid var(--line2);border-left:3px solid var(--accent);font-weight:600;cursor:default}
.chipi.done{opacity:.55;text-decoration:line-through}
.chipi.cancelled{text-decoration:line-through;opacity:.5}
.chipi.busy{--c:var(--busy);cursor:default}
.chipi.cont{border-top-left-radius:0;border-bottom-left-radius:0}
.chipi .t{font-variant-numeric:tabular-nums;opacity:.8}
.cell .more{font-size:11px;color:var(--muted);font-weight:600;padding-left:3px}
.cell.dragover,.tg-col.dragover,.wcol.dragover{outline:2px dashed var(--accent);outline-offset:-3px}

/* agenda */
.ag{display:flex;flex-direction:column;gap:12px;max-width:900px}
.agd{background:var(--paper);border:1px solid var(--line);border-radius:14px;box-shadow:var(--shadow);padding:10px 14px}
.agd.today{border-color:var(--today)}
.agd h3{margin:0 0 6px;font-size:14px;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.agd h3 .w{color:var(--muted);font-weight:500;font-size:12px}
.agi{display:grid;grid-template-columns:96px 1fr;gap:8px;padding:6px 0;border-top:1px solid var(--line);font-size:13px;cursor:pointer;align-items:start}
.agi:first-of-type{border-top:0}
.agi .t{font-variant-numeric:tabular-nums;color:var(--muted);font-size:12px}
.agi .b{border-left:3px solid var(--c);padding-left:8px;min-width:0}
.agi .b .ti{font-weight:650}
.agi.task .b{border-left-style:dotted;border-left-width:4px}
.agi.done .ti{text-decoration:line-through;opacity:.6}
.agi .s{font-size:12px;color:var(--muted)}

/* board */
.board{display:grid;grid-template-columns:repeat(5,minmax(200px,1fr));gap:10px;overflow-x:auto;padding-bottom:10px}
.bcol{background:var(--weekend);border:1px solid var(--line);border-radius:12px;padding:8px;min-height:240px;display:flex;flex-direction:column;gap:6px}
.bcol h4{margin:2px 4px 4px;font-size:12px;letter-spacing:.6px;text-transform:uppercase;color:var(--muted);display:flex;justify-content:space-between}
.bcol.dragover{outline:2px dashed var(--accent)}
.lane{font-size:11.5px;font-weight:700;color:var(--muted);margin:6px 2px 0;display:flex;align-items:center;gap:6px}

/* dialogs */
.dlg{position:fixed;inset:0;z-index:90;display:flex;align-items:flex-start;justify-content:center;padding:40px 14px;background:rgba(0,0,0,.3);overflow:auto}
.dlg .box{background:var(--paper);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);width:min(620px,100%);padding:16px 18px 18px;position:relative}
.dlg .box.wide{width:min(980px,100%)}
.dlg .box.mid{width:min(760px,100%)}
.dlg .box.narrow{width:min(440px,100%)}
.dlg h2{margin:0;font-size:17px;flex:1}
.dhd{display:flex;align-items:center;gap:8px;margin-bottom:12px}
.frm{display:grid;grid-template-columns:120px minmax(0,1fr);gap:9px 12px;align-items:center}
.frm > label{font-size:12px;font-weight:700;color:var(--muted);letter-spacing:.3px}
.frm .full{grid-column:1/3}
.frm .row{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.frm input[type=date],.frm input[type=time],.frm input[type=number],.frm input[type=color],input[type=date],input[type=time],input[type=number],input[type=search],input[type=url]{border:1px solid var(--line2);background:var(--paper);border-radius:8px;padding:6px 8px}
input[type=color]{padding:1px 3px;height:32px;width:46px}
.frm .row input[type=text]{flex:1;min-width:120px;width:auto}
.frm select{width:auto;min-width:120px;max-width:100%}
.frm .titlein{font-size:17px;font-weight:600;padding:8px 10px}
.frm .hint{grid-column:2/3}
.frm .sec{grid-column:1/3;border-top:1px solid var(--line);padding-top:10px;margin-top:2px;font-size:11.5px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--muted)}
.list-edit{display:flex;flex-direction:column;gap:5px;width:100%}
.list-edit .li{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.list-edit .li input[type=text]{flex:1;min-width:120px;width:auto}
.dacts{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;align-items:center}
.dacts .sp{flex:1}
.danger{color:var(--sick)}
button.danger:hover{border-color:var(--sick)}
.warnbox{border:1px solid var(--leave);background:color-mix(in srgb,var(--leave) 10%,transparent);border-radius:9px;padding:7px 10px;font-size:12.5px}
.okbox{border:1px solid var(--tele);background:color-mix(in srgb,var(--tele) 10%,transparent);border-radius:9px;padding:7px 10px;font-size:12.5px}
.daychips button{padding:4px 8px;font-size:12px;border-radius:7px}
.daychips button.on{background:var(--accent);color:var(--paper);border-color:var(--accent)}
.tabs{display:flex;gap:4px;border-bottom:1px solid var(--line);margin:0 0 12px;flex-wrap:wrap}
.tabs button{border:0;border-bottom:2px solid transparent;border-radius:0;background:transparent;padding:7px 10px;color:var(--muted);font-weight:600}
.tabs button.on{color:var(--ink);border-bottom-color:var(--accent)}
.md p{margin:.3em 0} .md ul{margin:.3em 0;padding-left:20px}
.slot{display:flex;align-items:center;justify-content:space-between;gap:8px;border:1px solid var(--line);border-radius:8px;padding:5px 8px;font-size:12.5px}

/* popover */
.pop{position:fixed;z-index:85;background:var(--paper);border:1px solid var(--line2);border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,.2);width:min(340px,calc(100vw - 20px));padding:12px 14px;font-size:13px}
.pop h3{margin:0 0 4px;font-size:15px;display:flex;gap:6px;align-items:flex-start}
.pop .ln{color:var(--muted);margin:2px 0;display:flex;gap:6px}
.pop .acts{display:flex;flex-wrap:wrap;gap:5px;margin-top:10px}
.pop .acts button{font-size:12px;padding:4px 8px}

/* toasts / notifications */
#toasts{position:fixed;right:16px;bottom:16px;z-index:95;display:flex;flex-direction:column;gap:8px;align-items:flex-end;max-width:calc(100vw - 32px)}
.toast{background:var(--paper);border:1px solid var(--line2);border-left:4px solid var(--c,var(--accent));border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.18);padding:10px 12px;width:min(370px,100%);font-size:13px;animation:tin .18s ease}
@keyframes tin{from{transform:translateY(12px);opacity:0}to{transform:none;opacity:1}}
.toast .th{display:flex;gap:8px;align-items:flex-start}
.toast .th b{flex:1}
.toast .ts{color:var(--muted);font-size:12px;margin-top:2px}
.toast .ta{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
.toast .ta button{font-size:12px;padding:3px 8px}
.toast .sub{border-top:1px solid var(--line);margin-top:6px;padding-top:6px}
.toast.simple{border-left-color:var(--ink)}
.notif{display:flex;gap:10px;padding:8px;border-radius:8px;cursor:pointer;border-bottom:1px solid var(--line)}
.notif:hover{background:var(--weekend)}
.notif.unread b::before{content:"● ";color:var(--today)}
.notif .w{font-size:11.5px;color:var(--muted)}

/* palette */
.pal{width:min(680px,100%)}
.pal input{font-size:16px;padding:10px 12px}
.pal .res{max-height:60vh;overflow:auto;margin-top:8px}
.pal .grp{font-size:11px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-transform:uppercase;margin:10px 4px 4px}
.pal .r{display:flex;gap:10px;align-items:center;padding:7px 8px;border-radius:8px;cursor:pointer;font-size:13.5px}
.pal .r.act,.pal .r:hover{background:var(--weekend)}
.pal .r .d{margin-left:auto;color:var(--muted);font-size:12px;white-space:nowrap}

/* reports */
.rep{display:grid;grid-template-columns:190px minmax(0,1fr);gap:16px}
.repnav{display:flex;flex-direction:column;gap:3px}
.repnav button{text-align:left;border:0;background:transparent;padding:8px 10px;border-radius:8px}
.repnav button.on{background:var(--ink);color:var(--paper)}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:8px;margin:6px 0 14px}
.kpi{border:1px solid var(--line);border-radius:10px;padding:8px 10px;background:var(--bg)}
.kpi .v{font-size:20px;font-weight:700;font-variant-numeric:tabular-nums}
.kpi .l{font-size:11.5px;color:var(--muted)}
.rsec{margin:16px 0 6px;font-size:12px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--muted)}
.chart text{fill:var(--ink);font-size:11px}
.chart .ax{stroke:var(--line2)}
.chart .q{fill:var(--muted)}
.progress{height:8px;border-radius:5px;background:var(--line);overflow:hidden}
.progress i{display:block;height:100%;background:var(--tele)}
.progress.over i{background:var(--sick)}

/* project page */
.pp-head{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.pp-head .big{font-size:20px;font-weight:700}

/* focus mode */
#focus{position:fixed;inset:0;z-index:99;background:var(--bg);display:none;align-items:center;justify-content:center;flex-direction:column;gap:18px;text-align:center;padding:20px}
#focus.open{display:flex}
#focus .clock{font-size:72px;font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:-1px}
#focus .ft{font-size:24px;font-weight:650}
#focus .next{color:var(--muted)}
.timerbadge{background:var(--today);color:#fff;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:700;cursor:pointer;border:0;font-variant-numeric:tabular-nums}

/* help */
.keys{display:grid;grid-template-columns:auto 1fr;gap:6px 14px;font-size:13px}

/* responsive */
@media (max-width:1100px){
  :root{--tasksw:270px;--side:210px}
}
@media (max-width:1023px){
  .layout{grid-template-columns:minmax(0,1fr)!important}
  #side,#tasks{position:fixed;top:var(--topH,58px);bottom:0;z-index:48;background:var(--bg);width:min(320px,90vw);box-shadow:var(--shadow);display:none}
  #side{left:0} #tasks{right:0}
  body.showside #side{display:block}
  body.showtasks #tasks{display:block}
  body.noside #side,body.notasks #tasks{display:none}
}
@media (max-width:700px){
  .topbar .grow{order:10;flex-basis:100%;max-width:none}
  .topbar .hide-s{display:none}
  .frm{grid-template-columns:1fr}
  .frm .full,.frm .sec,.frm .hint{grid-column:1}
  .dlg{padding:0}
  .dlg .box{border-radius:0;min-height:100vh}
  .rep{grid-template-columns:1fr}
  .repnav{flex-direction:row;flex-wrap:wrap}
  .board{grid-template-columns:repeat(5,minmax(220px,80vw))}
  .agi{grid-template-columns:70px 1fr}
}
@media print{
  #side,#tasks,#toasts,.vtools,.topbar .grow,.topbar .dropdown,.bell,#timerSlot{display:none!important}
  .layout{grid-template-columns:1fr!important}
  .tg-body{max-height:none;overflow:visible}
  body.report-open .dlg{display:none!important}
  body.print-rep > *:not(#printArea){display:none!important}
  body.print-rep #printArea{display:block!important}
  .chipi,.ev,.pri{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
#printArea{display:none}
@media print{ #printArea .ractions,#printArea .rctrl{display:none} }
.sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}
/* fixes after first look */
.tg-hcell.fill-tele{background:var(--fill-ath-tele)!important}
.tg-hcell.fill-abroad{background:var(--fill-ath-abroad)!important}
.fill-tele .dd,.fill-tele .hint{color:#fff!important}
.chipi.conflict::after{content:" ⚠";color:var(--leave)}
.topbar{flex-wrap:nowrap}
.topbar .nav h1{font-size:18px;overflow:hidden;text-overflow:ellipsis}
.topbar .nav{min-width:0;flex:0 1 auto}
.topbar .grow{flex:1 1 160px;min-width:120px;max-width:380px}
.topbar .lbl-l{display:inline}
@media (max-width:1600px){ #userBadge{display:none} }
@media (max-width:1450px){ .topbar .brand small,.topbar .lbl-l{display:none} .seg#viewSeg button{padding:6px 10px} }
@media (max-width:1250px){ .topbar .brand{display:none} .topbar .nav h1{font-size:16px} }
@media (max-width:1023px){ .topbar{flex-wrap:wrap} }
.topbar button,.topbar .brand,.topbar .badge{white-space:nowrap;flex-shrink:0}
.toast .ta select{width:auto;padding:3px 6px;font-size:12px}
@media (max-width:700px){
  .topbar{gap:6px;padding:8px 10px}
  .topbar .nav h1{font-size:14px}
  #viewSeg button{padding:5px 8px;font-size:13px}
  .vtools label,.vtools [data-zoom],.help{display:none}
  .legend-menu{position:fixed!important;left:8px!important;right:8px!important;top:auto!important;max-height:70vh}
}

/* flights in the time grid, half-day leave bands, red titles */
.ev.flt{--c:var(--accent);background:repeating-linear-gradient(90deg,color-mix(in srgb,var(--accent) 16%,var(--paper)) 0 10px,color-mix(in srgb,var(--accent) 8%,var(--paper)) 10px 20px);border-style:solid;border-left-width:4px;font-weight:600}
.tg-leave{position:absolute;left:0;right:0;background:repeating-linear-gradient(-45deg,color-mix(in srgb,var(--leave) 16%,transparent) 0 8px,color-mix(in srgb,var(--leave) 6%,transparent) 8px 16px);border-top:2px solid color-mix(in srgb,var(--leave) 60%,transparent);border-bottom:2px solid color-mix(in srgb,var(--leave) 60%,transparent);pointer-events:none;z-index:1}
.tg-leave span{position:absolute;right:4px;top:2px;font-size:10px;font-weight:700;color:var(--leave);text-transform:uppercase;letter-spacing:.4px}
.redtitle,.frm .titlein.redtitle,.pop h3.redtitle{color:#c92a2a}
@media (prefers-color-scheme: dark){ .redtitle,.frm .titlein.redtitle,.pop h3.redtitle{color:#ff6b6b} }

#tzBtn{white-space:nowrap}
#tzBtn.tzsug{box-shadow:0 0 0 2px var(--leave)}
#tzMenu{min-width:330px;max-height:70vh;overflow:auto;right:auto;left:0}
#tzMenu button.on{font-weight:600}

#drawer.open ~ #toasts{right:min(456px,100vw)}
@media (max-width:700px){ #drawer.open ~ #toasts{right:16px;bottom:auto;top:70px} }
.flight .row2 input[type=checkbox]{flex:none;width:auto;min-width:0}
.flight .row2 input[type=time]{flex:none;width:auto}
/* hide weekends */
.month.nowe .mhead,.month.nowe .mrow{grid-template-columns:repeat(5,minmax(0,1fr))}
.month.nowe .mrow .cell:nth-child(5){border-right:0}
.week.nowe{grid-template-columns:repeat(5,minmax(0,1fr))}
@media (max-width:700px){ .week.nowe{grid-template-columns:1fr} }
/* Markdown notes: editor and rendered text */
.mde{border:1px solid var(--line2);border-radius:10px;background:var(--paper);overflow:hidden;grid-column:1/3;width:100%}
.mde.ro{border:0;background:transparent}
.mde-bar{display:flex;flex-wrap:wrap;align-items:center;gap:2px;padding:4px 6px;border-bottom:1px solid var(--line);background:var(--bg)}
.mde-bar button[data-md]{border:0;background:transparent;min-width:30px;height:30px;padding:0 6px;border-radius:6px;font-size:14px;color:var(--ink)}
.mde-bar button[data-md]:hover:not(:disabled){background:var(--line)}
.mde-bar button:disabled{opacity:.35}
.mde-bar .sp{flex:1}
.mde-sep{width:1px;height:20px;background:var(--line2);margin:0 4px}
.mde-tabs button{padding:3px 10px;font-size:12px}
.mde textarea{border:0;border-radius:0;width:100%;resize:vertical;min-height:110px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;line-height:1.5;padding:10px 12px;background:var(--paper)}
.mde textarea:focus{outline:none;box-shadow:inset 0 0 0 2px color-mix(in srgb,var(--accent) 35%,transparent)}
.mde .mdprev{padding:10px 14px;min-height:110px;overflow:auto}
.mde-foot{padding:4px 10px 6px;border-top:1px solid var(--line);font-size:11.5px}
.md{line-height:1.5;overflow-wrap:anywhere}
.md>:first-child{margin-top:0}.md>:last-child{margin-bottom:0}
.md p{margin:.45em 0}
.md h1,.md h2,.md h3,.md h4,.md h5,.md h6{margin:.7em 0 .35em;line-height:1.25}
.md h1{font-size:1.3em}.md h2{font-size:1.15em}.md h3{font-size:1.02em}.md h4,.md h5,.md h6{font-size:.95em}
.md ul,.md ol{margin:.4em 0;padding-left:1.5em}
.md li{margin:.15em 0}
.md blockquote{margin:.5em 0;padding:.2em .9em;border-left:3px solid var(--line2);color:var(--muted)}
.md code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.9em;background:color-mix(in srgb,var(--ink) 7%,transparent);padding:.1em .35em;border-radius:4px}
.md pre{background:color-mix(in srgb,var(--ink) 6%,transparent);padding:.6em .8em;border-radius:8px;overflow:auto}
.md pre code{background:none;padding:0}
.md hr{border:0;border-top:1px solid var(--line2);margin:.8em 0}
.md table{border-collapse:collapse;margin:.5em 0;font-size:.95em;display:block;overflow:auto}
.md th,.md td{border:1px solid var(--line2);padding:.25em .6em;text-align:left}
.md th{background:var(--bg)}
.md img{max-width:100%}
.md a{color:var(--accent)}
.pop .md{font-size:12.5px}
@media (max-width:700px){ .frm .mde{grid-column:1} .mde-bar button[data-md]{min-width:34px;height:34px} }

/* details opened in their own browser tab */
#detail{display:none}
body.detail #detail{display:block;max-width:980px;margin:18px auto 60px;padding:0 16px}
body.detail #detail.box{background:var(--paper);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:16px 18px 18px}
body.detail .layout,body.detail #selbar,body.detail #drawer,body.detail #scrim,
body.detail .topbar .nav,body.detail #qaWrap,body.detail #searchBtn,body.detail #viewSeg,body.detail #newBtn,body.detail #repBtn,
body.detail #sideBtn,body.detail #tasksBtn,body.detail #bellBtn,body.detail #menuBtn,body.detail .topbar .dropdown:not(#tzWrap){display:none!important}
body.detail .topbar{justify-content:flex-start}
body.detail .topbar .brand{display:flex!important}
.dt-card{background:var(--paper);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:20px 24px 16px}
.dt-head{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.dt-title{font-size:26px;line-height:1.2;margin:10px 0 14px}
.dt-sub{font-size:13px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--muted);margin:18px 0 8px;border-top:1px solid var(--line);padding-top:14px}
.dt-grid{display:grid;grid-template-columns:130px minmax(0,1fr);gap:10px 16px;margin:0}
.dt-grid dt{font-size:12px;font-weight:700;color:var(--muted);letter-spacing:.3px;padding-top:2px}
.dt-grid dd{margin:0;min-width:0;overflow-wrap:anywhere}
.dt-grid a{color:var(--accent)}
.dt-notes{font-size:14.5px}
.dt-acts{border-top:1px solid var(--line);padding-top:14px}
.dt-foot{margin-top:10px}
[data-flk][role=button],.agi[data-flk]{cursor:pointer}
@media (max-width:700px){ .dt-grid{grid-template-columns:1fr;gap:2px} .dt-grid dd{margin-bottom:8px} .dt-card{padding:16px} .dt-title{font-size:22px} body.detail #detail{margin-top:10px;padding:0 8px} }
@media print{ body.detail .topbar,body.detail .dt-acts{display:none!important} }
.dt-card .warnbox{margin-top:12px}
.chip.loc-on .hint{color:inherit;opacity:.85}
/* edit windows as a full page in their own tab */
.dlg.page{position:static;inset:auto;background:none;padding:18px 16px 60px;display:block;overflow:visible;z-index:auto}
.dlg.page .box{width:min(1000px,100%);margin:0 auto;border-radius:16px;min-height:0}
.dlg.page .box .frm{grid-template-columns:150px minmax(0,1fr)}
.dlg.page .mde textarea{min-height:240px}
.dlg.page .dacts{position:sticky;bottom:0;background:var(--paper);padding:12px 0 4px;border-top:1px solid var(--line);margin-top:18px;z-index:2}
body.detail #detail:has(.dlg.page){max-width:none;margin:0;padding:0}
@media (max-width:700px){ .dlg.page{padding:0 0 40px} .dlg.page .box{border-radius:0} .dlg.page .box .frm{grid-template-columns:1fr} }

/* legend dropdown */
.legend-dd{display:inline-block}
.legend-dd>summary{cursor:pointer;color:var(--muted);list-style:none;user-select:none}
.legend-dd>summary::-webkit-details-marker{display:none}
.legend-dd[open]>summary{color:var(--ink)}
.legend-menu{display:block;padding:8px 12px 10px;min-width:260px;max-height:72vh;overflow:auto;font-size:12.5px;z-index:46}
.lg-h{font-size:10.5px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--muted);margin:10px 0 4px}
.lg-h:first-child{margin-top:2px}
.lg-row{display:flex;align-items:center;gap:8px;padding:2px 0}
.lg-ico{width:26px;display:inline-flex;justify-content:center;flex:none}
.lg-ico .sw{width:14px;height:14px;border-radius:4px}
.lg-ico .loc{padding:0 4px;font-size:11px}
.vtools details.dropdown>summary{list-style:none}
.vtools details.dropdown>summary::-webkit-details-marker{display:none}
</style>
</head>
<body class="locked">

<div id="login">
  <div class="lbox" id="bootMsg">Loading…</div>
  <form class="lbox" id="loginForm" autocomplete="on" style="display:none">
    <div class="brand"><span class="dot"></span>Work &amp; Travel Calendar</div>
    <div class="hint">Please sign in to see the calendar.</div>
    <label>Username <input type="text" id="lUser" autocomplete="username" autocapitalize="off" spellcheck="false" required></label>
    <label>Password <input type="password" id="lPass" autocomplete="current-password" required></label>
    <fieldset id="lNewBox" hidden>
      <legend>Choose a new password</legend>
      <div class="hint">Your password was reset. Choose a new one (at least 6 characters) to continue.</div>
      <label>New password <input type="password" id="lNew" autocomplete="new-password" minlength="6"></label>
      <label>Repeat new password <input type="password" id="lNew2" autocomplete="new-password" minlength="6"></label>
    </fieldset>
    <label class="chk"><input type="checkbox" id="lRemember"> Keep me signed in on this device</label>
    <div class="lerr" id="lErr"></div>
    <button class="primary" type="submit" id="lBtn">Sign in</button>
    <div class="hint" style="text-align:center">Forgot your password? Ask the calendar owner to reset it.</div>
  </form>
  <form class="lbox" id="setupForm" autocomplete="off" style="display:none">
    <div class="brand"><span class="dot"></span>Work &amp; Travel Calendar</div>
    <div class="hint"><b>First start:</b> create the two accounts. You can change them or add more later (⋯ → User accounts). Passwords need at least 6 characters.</div>
    <fieldset><legend>Editor (can read and change)</legend>
      <label>Username <input type="text" id="sEdUser" value="dkouis" autocapitalize="off" spellcheck="false" required></label>
      <label>Password <input type="password" id="sEdPass" autocomplete="new-password" minlength="6" required></label>
      <label>Repeat password <input type="password" id="sEdPass2" autocomplete="new-password" minlength="6" required></label>
    </fieldset>
    <fieldset><legend>Viewer (can only read)</legend>
      <label>Username <input type="text" id="sVwUser" value="viewer" autocapitalize="off" spellcheck="false" required></label>
      <label>Password <input type="password" id="sVwPass" autocomplete="new-password" minlength="6" required></label>
      <label>Repeat password <input type="password" id="sVwPass2" autocomplete="new-password" minlength="6" required></label>
    </fieldset>
    <div class="lerr" id="sErr"></div>
    <button class="primary" type="submit" id="sBtn">Create accounts and open the calendar</button>
  </form>
</div>

<header class="topbar" id="topbar">
  <button class="iconbtn ghost" id="sideBtn" title="Show or hide the left panel" aria-label="Toggle left panel">☰</button>
  <div class="brand hide-s"><span class="dot"></span>Work &amp; Travel <small id="ownerLbl"></small></div>
  <div class="nav">
    <button id="prev" title="Previous (←)" aria-label="Previous">‹</button>
    <button id="today" title="Today (T)">Today</button>
    <button id="next" title="Next (→)" aria-label="Next">›</button>
    <h1 id="title"></h1>
  </div>
  <div class="dropdown" id="tzWrap">
    <button id="tzBtn" title="Active time zone: times are entered and shown in it" aria-haspopup="true">🌍 <span id="tzLbl"></span> ▾</button>
    <div class="menu" id="tzMenu" role="menu"></div>
  </div>
  <div class="grow" id="qaWrap">
    <span class="qa-ico">＋</span>
    <input type="text" id="qa" placeholder="Quick add: Meeting tue 10-12 #Project @Brussels  ·  Draft text by fri ~2h p1" autocomplete="off" aria-label="Quick add">
    <div id="qaPrev" role="status"></div>
  </div>
  <div class="spacer"></div>
  <button class="iconbtn" id="searchBtn" title="Search (Ctrl+K or /)" aria-label="Search">🔍</button>
  <div class="seg" id="viewSeg" role="tablist" aria-label="View">
    <button data-view="day" title="Day (D)">Day</button>
    <button data-view="3day" title="3 days (3)">3 days</button>
    <button data-view="week" title="Week (W)">Week</button>
    <button data-view="month" title="Month (M)">Month</button>
    <button data-view="agenda" title="Agenda (A)">Agenda</button>
    <button data-view="board" title="Tasks board (B)">Board</button>
  </div>
  <div class="dropdown" data-ed>
    <button class="primary" id="newBtn" title="New (N)">＋<span class="lbl-l"> New</span> ▾</button>
    <div class="menu" id="newMenu">
      <button data-new="event">📅 Event</button>
      <button data-new="task">☑ Task</button>
      <button data-new="project">📁 Project</button>
      <hr>
      <button data-new="template">🧩 From a template…</button>
      <button data-new="plan">🗓 Plan my day…</button>
    </div>
  </div>
  <div class="dropdown">
    <button id="repBtn" title="Reports">📊<span class="lbl-l"> Reports</span> ▾</button>
    <div class="menu" id="repMenu">
      <button data-rep="daily">Daily agenda</button>
      <button data-rep="eod">End-of-day summary</button>
      <button data-rep="weekly">Weekly review</button>
      <button data-rep="project">Project report</button>
      <button data-rep="time">Time allocation</button>
      <button data-rep="trips">✈ Trips</button>
    </div>
  </div>
  <span id="timerSlot"></span>
  <button class="iconbtn bell" id="bellBtn" title="Notifications" aria-label="Notifications">🔔<span class="cnt" id="bellCnt" hidden></span></button>
  <div class="filebox">
    <span id="saveState"></span>
    <span id="userBadge" class="badge hide-s"></span>
    <button class="iconbtn ghost" id="tasksBtn" title="Show or hide the tasks panel" aria-label="Toggle tasks panel">☑</button>
    <button id="menuBtn" title="More">⋯</button>
    <div class="menu" id="menu">
      <button data-act="settings">⚙ Settings…</button>
      <button data-act="refresh">↻ Reload latest</button>
      <button data-act="print">🖨 Print this view</button>
      <button data-act="help">⌨ Keyboard shortcuts (?)</button>
      <hr>
      <button data-act="icsexp">📤 Export calendar file (.ics)…</button>
      <button data-act="icsimp" data-ed>📥 Import calendar file (.ics)…</button>
      <button data-act="csvimp" data-ed>📥 Import tasks from CSV…</button>
      <hr data-ed>
      <button data-act="owner" data-ed>✎ Set calendar owner name</button>
      <button data-act="password">🔑 Change my password…</button>
      <button data-act="accounts" data-ed>👥 User accounts &amp; passwords…</button>
      <button data-act="history" data-ed>🕘 Earlier versions…</button>
      <button data-act="export">⬇ Export backup (.json)…</button>
      <button data-act="import" data-ed>⬆ Restore / import backup…</button>
      <hr>
      <button data-act="signout">⎋ Sign out</button>
    </div>
  </div>
</header>

<div id="banner"></div>
<main id="detail" aria-live="polite"></main>
<div class="layout">
  <aside id="side" aria-label="Calendars and projects"></aside>
  <main id="main"></main>
  <aside id="tasks" aria-label="Tasks"></aside>
</div>

<div id="selbar"></div>
<div id="scrim"></div>
<aside id="drawer" aria-label="Day details"></aside>
<div id="toasts" aria-live="polite"></div>
<div id="focus" role="dialog" aria-label="Focus mode"></div>
<div id="printArea"></div>
<input type="file" id="fileInput" hidden>

<script>
/*! markdown-it 14.1.0 https://github.com/markdown-it/markdown-it @license MIT */
!function(t,e){"object"==typeof exports&&"undefined"!=typeof module?module.exports=e():"function"==typeof define&&define.amd?define(e):(t="undefined"!=typeof globalThis?globalThis:t||self).markdownit=e()}(this,(function(){"use strict";const t={};function e(r,n){"string"!=typeof n&&(n=e.defaultChars);const s=function(e){let r=t[e];if(r)return r;r=t[e]=[];for(let t=0;t<128;t++){const e=String.fromCharCode(t);r.push(e)}for(let t=0;t<e.length;t++){const n=e.charCodeAt(t);r[n]="%"+("0"+n.toString(16).toUpperCase()).slice(-2)}return r}(n);return r.replace(/(%[a-f0-9]{2})+/gi,(function(t){let e="";for(let r=0,n=t.length;r<n;r+=3){const i=parseInt(t.slice(r+1,r+3),16);if(i<128)e+=s[i];else{if(192==(224&i)&&r+3<n){const n=parseInt(t.slice(r+4,r+6),16);if(128==(192&n)){const t=i<<6&1984|63&n;e+=t<128?"\ufffd\ufffd":String.fromCharCode(t),r+=3;continue}}if(224==(240&i)&&r+6<n){const n=parseInt(t.slice(r+4,r+6),16),s=parseInt(t.slice(r+7,r+9),16);if(128==(192&n)&&128==(192&s)){const t=i<<12&61440|n<<6&4032|63&s;e+=t<2048||t>=55296&&t<=57343?"\ufffd\ufffd\ufffd":String.fromCharCode(t),r+=6;continue}}if(240==(248&i)&&r+9<n){const n=parseInt(t.slice(r+4,r+6),16),s=parseInt(t.slice(r+7,r+9),16),o=parseInt(t.slice(r+10,r+12),16);if(128==(192&n)&&128==(192&s)&&128==(192&o)){let t=i<<18&1835008|n<<12&258048|s<<6&4032|63&o;t<65536||t>1114111?e+="\ufffd\ufffd\ufffd\ufffd":(t-=65536,e+=String.fromCharCode(55296+(t>>10),56320+(1023&t))),r+=9;continue}}e+="\ufffd"}}return e}))}e.defaultChars=";/?:@&=+$,#",e.componentChars="";const r={};function n(t,e,s){"string"!=typeof e&&(s=e,e=n.defaultChars),void 0===s&&(s=!0);const i=function(t){let e=r[t];if(e)return e;e=r[t]=[];for(let t=0;t<128;t++){const r=String.fromCharCode(t);/^[0-9a-z]$/i.test(r)?e.push(r):e.push("%"+("0"+t.toString(16).toUpperCase()).slice(-2))}for(let r=0;r<t.length;r++)e[t.charCodeAt(r)]=t[r];return e}(e);let o="";for(let e=0,r=t.length;e<r;e++){const n=t.charCodeAt(e);if(s&&37===n&&e+2<r&&/^[0-9a-f]{2}$/i.test(t.slice(e+1,e+3)))o+=t.slice(e,e+3),e+=2;else if(n<128)o+=i[n];else if(n>=55296&&n<=57343){if(n>=55296&&n<=56319&&e+1<r){const r=t.charCodeAt(e+1);if(r>=56320&&r<=57343){o+=encodeURIComponent(t[e]+t[e+1]),e++;continue}}o+="%EF%BF%BD"}else o+=encodeURIComponent(t[e])}return o}function s(t){let e="";return e+=t.protocol||"",e+=t.slashes?"//":"",e+=t.auth?t.auth+"@":"",t.hostname&&-1!==t.hostname.indexOf(":")?e+="["+t.hostname+"]":e+=t.hostname||"",e+=t.port?":"+t.port:"",e+=t.pathname||"",e+=t.search||"",e+=t.hash||"",e}function i(){this.protocol=null,this.slashes=null,this.auth=null,this.port=null,this.hostname=null,this.hash=null,this.search=null,this.pathname=null}n.defaultChars=";/?:@&=+$,-_.!~*'()#",n.componentChars="-_.!~*'()";const o=/^([a-z0-9.+-]+:)/i,u=/:[0-9]*$/,c=/^(\/\/?(?!\/)[^\?\s]*)(\?[^\s]*)?$/,a=["{","}","|","\\","^","`"].concat(["<",">",'"',"`"," ","\r","\n","\t"]),l=["'"].concat(a),h=["%","/","?",";","#"].concat(l),p=["/","?","#"],f=/^[+a-z0-9A-Z_-]{0,63}$/,d=/^([+a-z0-9A-Z_-]{0,63})(.*)$/,_={javascript:!0,"javascript:":!0},m={http:!0,https:!0,ftp:!0,gopher:!0,file:!0,"http:":!0,"https:":!0,"ftp:":!0,"gopher:":!0,"file:":!0};function g(t,e){if(t&&t instanceof i)return t;const r=new i;return r.parse(t,e),r}i.prototype.parse=function(t,e){let r,n,s,i=t;if(i=i.trim(),!e&&1===t.split("#").length){const t=c.exec(i);if(t)return this.pathname=t[1],t[2]&&(this.search=t[2]),this}let u=o.exec(i);if(u&&(u=u[0],r=u.toLowerCase(),this.protocol=u,i=i.substr(u.length)),(e||u||i.match(/^\/\/[^@\/]+@[^@\/]+/))&&(s="//"===i.substr(0,2),!s||u&&_[u]||(i=i.substr(2),this.slashes=!0)),!_[u]&&(s||u&&!m[u])){let t,e,r=-1;for(let t=0;t<p.length;t++)n=i.indexOf(p[t]),-1!==n&&(-1===r||n<r)&&(r=n);e=-1===r?i.lastIndexOf("@"):i.lastIndexOf("@",r),-1!==e&&(t=i.slice(0,e),i=i.slice(e+1),this.auth=t),r=-1;for(let t=0;t<h.length;t++)n=i.indexOf(h[t]),-1!==n&&(-1===r||n<r)&&(r=n);-1===r&&(r=i.length),":"===i[r-1]&&r--;const s=i.slice(0,r);i=i.slice(r),this.parseHost(s),this.hostname=this.hostname||"";const o="["===this.hostname[0]&&"]"===this.hostname[this.hostname.length-1];if(!o){const t=this.hostname.split(/\./);for(let e=0,r=t.length;e<r;e++){const r=t[e];if(r&&!r.match(f)){let n="";for(let t=0,e=r.length;t<e;t++)r.charCodeAt(t)>127?n+="x":n+=r[t];if(!n.match(f)){const n=t.slice(0,e),s=t.slice(e+1),o=r.match(d);o&&(n.push(o[1]),s.unshift(o[2])),s.length&&(i=s.join(".")+i),this.hostname=n.join(".");break}}}}this.hostname.length>255&&(this.hostname=""),o&&(this.hostname=this.hostname.substr(1,this.hostname.length-2))}const a=i.indexOf("#");-1!==a&&(this.hash=i.substr(a),i=i.slice(0,a));const l=i.indexOf("?");return-1!==l&&(this.search=i.substr(l),i=i.slice(0,l)),i&&(this.pathname=i),m[r]&&this.hostname&&!this.pathname&&(this.pathname=""),this},i.prototype.parseHost=function(t){let e=u.exec(t);e&&(e=e[0],":"!==e&&(this.port=e.substr(1)),t=t.substr(0,t.length-e.length)),t&&(this.hostname=t)};var k,D=Object.freeze({__proto__:null,decode:e,encode:n,format:s,parse:g}),C=/[\0-\uD7FF\uE000-\uFFFF]|[\uD800-\uDBFF][\uDC00-\uDFFF]|[\uD800-\uDBFF](?![\uDC00-\uDFFF])|(?:[^\uD800-\uDBFF]|^)[\uDC00-\uDFFF]/,y=/[\0-\x1F\x7F-\x9F]/,E=/[!-#%-\*,-\/:;\?@\[-\]_\{\}\xA1\xA7\xAB\xB6\xB7\xBB\xBF\u037E\u0387\u055A-\u055F\u0589\u058A\u05BE\u05C0\u05C3\u05C6\u05F3\u05F4\u0609\u060A\u060C\u060D\u061B\u061D-\u061F\u066A-\u066D\u06D4\u0700-\u070D\u07F7-\u07F9\u0830-\u083E\u085E\u0964\u0965\u0970\u09FD\u0A76\u0AF0\u0C77\u0C84\u0DF4\u0E4F\u0E5A\u0E5B\u0F04-\u0F12\u0F14\u0F3A-\u0F3D\u0F85\u0FD0-\u0FD4\u0FD9\u0FDA\u104A-\u104F\u10FB\u1360-\u1368\u1400\u166E\u169B\u169C\u16EB-\u16ED\u1735\u1736\u17D4-\u17D6\u17D8-\u17DA\u1800-\u180A\u1944\u1945\u1A1E\u1A1F\u1AA0-\u1AA6\u1AA8-\u1AAD\u1B5A-\u1B60\u1B7D\u1B7E\u1BFC-\u1BFF\u1C3B-\u1C3F\u1C7E\u1C7F\u1CC0-\u1CC7\u1CD3\u2010-\u2027\u2030-\u2043\u2045-\u2051\u2053-\u205E\u207D\u207E\u208D\u208E\u2308-\u230B\u2329\u232A\u2768-\u2775\u27C5\u27C6\u27E6-\u27EF\u2983-\u2998\u29D8-\u29DB\u29FC\u29FD\u2CF9-\u2CFC\u2CFE\u2CFF\u2D70\u2E00-\u2E2E\u2E30-\u2E4F\u2E52-\u2E5D\u3001-\u3003\u3008-\u3011\u3014-\u301F\u3030\u303D\u30A0\u30FB\uA4FE\uA4FF\uA60D-\uA60F\uA673\uA67E\uA6F2-\uA6F7\uA874-\uA877\uA8CE\uA8CF\uA8F8-\uA8FA\uA8FC\uA92E\uA92F\uA95F\uA9C1-\uA9CD\uA9DE\uA9DF\uAA5C-\uAA5F\uAADE\uAADF\uAAF0\uAAF1\uABEB\uFD3E\uFD3F\uFE10-\uFE19\uFE30-\uFE52\uFE54-\uFE61\uFE63\uFE68\uFE6A\uFE6B\uFF01-\uFF03\uFF05-\uFF0A\uFF0C-\uFF0F\uFF1A\uFF1B\uFF1F\uFF20\uFF3B-\uFF3D\uFF3F\uFF5B\uFF5D\uFF5F-\uFF65]|\uD800[\uDD00-\uDD02\uDF9F\uDFD0]|\uD801\uDD6F|\uD802[\uDC57\uDD1F\uDD3F\uDE50-\uDE58\uDE7F\uDEF0-\uDEF6\uDF39-\uDF3F\uDF99-\uDF9C]|\uD803[\uDEAD\uDF55-\uDF59\uDF86-\uDF89]|\uD804[\uDC47-\uDC4D\uDCBB\uDCBC\uDCBE-\uDCC1\uDD40-\uDD43\uDD74\uDD75\uDDC5-\uDDC8\uDDCD\uDDDB\uDDDD-\uDDDF\uDE38-\uDE3D\uDEA9]|\uD805[\uDC4B-\uDC4F\uDC5A\uDC5B\uDC5D\uDCC6\uDDC1-\uDDD7\uDE41-\uDE43\uDE60-\uDE6C\uDEB9\uDF3C-\uDF3E]|\uD806[\uDC3B\uDD44-\uDD46\uDDE2\uDE3F-\uDE46\uDE9A-\uDE9C\uDE9E-\uDEA2\uDF00-\uDF09]|\uD807[\uDC41-\uDC45\uDC70\uDC71\uDEF7\uDEF8\uDF43-\uDF4F\uDFFF]|\uD809[\uDC70-\uDC74]|\uD80B[\uDFF1\uDFF2]|\uD81A[\uDE6E\uDE6F\uDEF5\uDF37-\uDF3B\uDF44]|\uD81B[\uDE97-\uDE9A\uDFE2]|\uD82F\uDC9F|\uD836[\uDE87-\uDE8B]|\uD83A[\uDD5E\uDD5F]/,A=/[\$\+<->\^`\|~\xA2-\xA6\xA8\xA9\xAC\xAE-\xB1\xB4\xB8\xD7\xF7\u02C2-\u02C5\u02D2-\u02DF\u02E5-\u02EB\u02ED\u02EF-\u02FF\u0375\u0384\u0385\u03F6\u0482\u058D-\u058F\u0606-\u0608\u060B\u060E\u060F\u06DE\u06E9\u06FD\u06FE\u07F6\u07FE\u07FF\u0888\u09F2\u09F3\u09FA\u09FB\u0AF1\u0B70\u0BF3-\u0BFA\u0C7F\u0D4F\u0D79\u0E3F\u0F01-\u0F03\u0F13\u0F15-\u0F17\u0F1A-\u0F1F\u0F34\u0F36\u0F38\u0FBE-\u0FC5\u0FC7-\u0FCC\u0FCE\u0FCF\u0FD5-\u0FD8\u109E\u109F\u1390-\u1399\u166D\u17DB\u1940\u19DE-\u19FF\u1B61-\u1B6A\u1B74-\u1B7C\u1FBD\u1FBF-\u1FC1\u1FCD-\u1FCF\u1FDD-\u1FDF\u1FED-\u1FEF\u1FFD\u1FFE\u2044\u2052\u207A-\u207C\u208A-\u208C\u20A0-\u20C0\u2100\u2101\u2103-\u2106\u2108\u2109\u2114\u2116-\u2118\u211E-\u2123\u2125\u2127\u2129\u212E\u213A\u213B\u2140-\u2144\u214A-\u214D\u214F\u218A\u218B\u2190-\u2307\u230C-\u2328\u232B-\u2426\u2440-\u244A\u249C-\u24E9\u2500-\u2767\u2794-\u27C4\u27C7-\u27E5\u27F0-\u2982\u2999-\u29D7\u29DC-\u29FB\u29FE-\u2B73\u2B76-\u2B95\u2B97-\u2BFF\u2CE5-\u2CEA\u2E50\u2E51\u2E80-\u2E99\u2E9B-\u2EF3\u2F00-\u2FD5\u2FF0-\u2FFF\u3004\u3012\u3013\u3020\u3036\u3037\u303E\u303F\u309B\u309C\u3190\u3191\u3196-\u319F\u31C0-\u31E3\u31EF\u3200-\u321E\u322A-\u3247\u3250\u3260-\u327F\u328A-\u32B0\u32C0-\u33FF\u4DC0-\u4DFF\uA490-\uA4C6\uA700-\uA716\uA720\uA721\uA789\uA78A\uA828-\uA82B\uA836-\uA839\uAA77-\uAA79\uAB5B\uAB6A\uAB6B\uFB29\uFBB2-\uFBC2\uFD40-\uFD4F\uFDCF\uFDFC-\uFDFF\uFE62\uFE64-\uFE66\uFE69\uFF04\uFF0B\uFF1C-\uFF1E\uFF3E\uFF40\uFF5C\uFF5E\uFFE0-\uFFE6\uFFE8-\uFFEE\uFFFC\uFFFD]|\uD800[\uDD37-\uDD3F\uDD79-\uDD89\uDD8C-\uDD8E\uDD90-\uDD9C\uDDA0\uDDD0-\uDDFC]|\uD802[\uDC77\uDC78\uDEC8]|\uD805\uDF3F|\uD807[\uDFD5-\uDFF1]|\uD81A[\uDF3C-\uDF3F\uDF45]|\uD82F\uDC9C|\uD833[\uDF50-\uDFC3]|\uD834[\uDC00-\uDCF5\uDD00-\uDD26\uDD29-\uDD64\uDD6A-\uDD6C\uDD83\uDD84\uDD8C-\uDDA9\uDDAE-\uDDEA\uDE00-\uDE41\uDE45\uDF00-\uDF56]|\uD835[\uDEC1\uDEDB\uDEFB\uDF15\uDF35\uDF4F\uDF6F\uDF89\uDFA9\uDFC3]|\uD836[\uDC00-\uDDFF\uDE37-\uDE3A\uDE6D-\uDE74\uDE76-\uDE83\uDE85\uDE86]|\uD838[\uDD4F\uDEFF]|\uD83B[\uDCAC\uDCB0\uDD2E\uDEF0\uDEF1]|\uD83C[\uDC00-\uDC2B\uDC30-\uDC93\uDCA0-\uDCAE\uDCB1-\uDCBF\uDCC1-\uDCCF\uDCD1-\uDCF5\uDD0D-\uDDAD\uDDE6-\uDE02\uDE10-\uDE3B\uDE40-\uDE48\uDE50\uDE51\uDE60-\uDE65\uDF00-\uDFFF]|\uD83D[\uDC00-\uDED7\uDEDC-\uDEEC\uDEF0-\uDEFC\uDF00-\uDF76\uDF7B-\uDFD9\uDFE0-\uDFEB\uDFF0]|\uD83E[\uDC00-\uDC0B\uDC10-\uDC47\uDC50-\uDC59\uDC60-\uDC87\uDC90-\uDCAD\uDCB0\uDCB1\uDD00-\uDE53\uDE60-\uDE6D\uDE70-\uDE7C\uDE80-\uDE88\uDE90-\uDEBD\uDEBF-\uDEC5\uDECE-\uDEDB\uDEE0-\uDEE8\uDEF0-\uDEF8\uDF00-\uDF92\uDF94-\uDFCA]/,b=/[ \xA0\u1680\u2000-\u200A\u2028\u2029\u202F\u205F\u3000]/,F=Object.freeze({__proto__:null,Any:C,Cc:y,Cf:/[\xAD\u0600-\u0605\u061C\u06DD\u070F\u0890\u0891\u08E2\u180E\u200B-\u200F\u202A-\u202E\u2060-\u2064\u2066-\u206F\uFEFF\uFFF9-\uFFFB]|\uD804[\uDCBD\uDCCD]|\uD80D[\uDC30-\uDC3F]|\uD82F[\uDCA0-\uDCA3]|\uD834[\uDD73-\uDD7A]|\uDB40[\uDC01\uDC20-\uDC7F]/,P:E,S:A,Z:b}),x=new Uint16Array('\u1d41<\xd5\u0131\u028a\u049d\u057b\u05d0\u0675\u06de\u07a2\u07d6\u080f\u0a4a\u0a91\u0da1\u0e6d\u0f09\u0f26\u10ca\u1228\u12e1\u1415\u149d\u14c3\u14df\u1525\0\0\0\0\0\0\u156b\u16cd\u198d\u1c12\u1ddd\u1f7e\u2060\u21b0\u228d\u23c0\u23fb\u2442\u2824\u2912\u2d08\u2e48\u2fce\u3016\u32ba\u3639\u37ac\u38fe\u3a28\u3a71\u3ae0\u3b2e\u0800EMabcfglmnoprstu\\bfms\x7f\x84\x8b\x90\x95\x98\xa6\xb3\xb9\xc8\xcflig\u803b\xc6\u40c6P\u803b&\u4026cute\u803b\xc1\u40c1reve;\u4102\u0100iyx}rc\u803b\xc2\u40c2;\u4410r;\uc000\ud835\udd04rave\u803b\xc0\u40c0pha;\u4391acr;\u4100d;\u6a53\u0100gp\x9d\xa1on;\u4104f;\uc000\ud835\udd38plyFunction;\u6061ing\u803b\xc5\u40c5\u0100cs\xbe\xc3r;\uc000\ud835\udc9cign;\u6254ilde\u803b\xc3\u40c3ml\u803b\xc4\u40c4\u0400aceforsu\xe5\xfb\xfe\u0117\u011c\u0122\u0127\u012a\u0100cr\xea\xf2kslash;\u6216\u0176\xf6\xf8;\u6ae7ed;\u6306y;\u4411\u0180crt\u0105\u010b\u0114ause;\u6235noullis;\u612ca;\u4392r;\uc000\ud835\udd05pf;\uc000\ud835\udd39eve;\u42d8c\xf2\u0113mpeq;\u624e\u0700HOacdefhilorsu\u014d\u0151\u0156\u0180\u019e\u01a2\u01b5\u01b7\u01ba\u01dc\u0215\u0273\u0278\u027ecy;\u4427PY\u803b\xa9\u40a9\u0180cpy\u015d\u0162\u017aute;\u4106\u0100;i\u0167\u0168\u62d2talDifferentialD;\u6145leys;\u612d\u0200aeio\u0189\u018e\u0194\u0198ron;\u410cdil\u803b\xc7\u40c7rc;\u4108nint;\u6230ot;\u410a\u0100dn\u01a7\u01adilla;\u40b8terDot;\u40b7\xf2\u017fi;\u43a7rcle\u0200DMPT\u01c7\u01cb\u01d1\u01d6ot;\u6299inus;\u6296lus;\u6295imes;\u6297o\u0100cs\u01e2\u01f8kwiseContourIntegral;\u6232eCurly\u0100DQ\u0203\u020foubleQuote;\u601duote;\u6019\u0200lnpu\u021e\u0228\u0247\u0255on\u0100;e\u0225\u0226\u6237;\u6a74\u0180git\u022f\u0236\u023aruent;\u6261nt;\u622fourIntegral;\u622e\u0100fr\u024c\u024e;\u6102oduct;\u6210nterClockwiseContourIntegral;\u6233oss;\u6a2fcr;\uc000\ud835\udc9ep\u0100;C\u0284\u0285\u62d3ap;\u624d\u0580DJSZacefios\u02a0\u02ac\u02b0\u02b4\u02b8\u02cb\u02d7\u02e1\u02e6\u0333\u048d\u0100;o\u0179\u02a5trahd;\u6911cy;\u4402cy;\u4405cy;\u440f\u0180grs\u02bf\u02c4\u02c7ger;\u6021r;\u61a1hv;\u6ae4\u0100ay\u02d0\u02d5ron;\u410e;\u4414l\u0100;t\u02dd\u02de\u6207a;\u4394r;\uc000\ud835\udd07\u0100af\u02eb\u0327\u0100cm\u02f0\u0322ritical\u0200ADGT\u0300\u0306\u0316\u031ccute;\u40b4o\u0174\u030b\u030d;\u42d9bleAcute;\u42ddrave;\u4060ilde;\u42dcond;\u62c4ferentialD;\u6146\u0470\u033d\0\0\0\u0342\u0354\0\u0405f;\uc000\ud835\udd3b\u0180;DE\u0348\u0349\u034d\u40a8ot;\u60dcqual;\u6250ble\u0300CDLRUV\u0363\u0372\u0382\u03cf\u03e2\u03f8ontourIntegra\xec\u0239o\u0274\u0379\0\0\u037b\xbb\u0349nArrow;\u61d3\u0100eo\u0387\u03a4ft\u0180ART\u0390\u0396\u03a1rrow;\u61d0ightArrow;\u61d4e\xe5\u02cang\u0100LR\u03ab\u03c4eft\u0100AR\u03b3\u03b9rrow;\u67f8ightArrow;\u67faightArrow;\u67f9ight\u0100AT\u03d8\u03derrow;\u61d2ee;\u62a8p\u0241\u03e9\0\0\u03efrrow;\u61d1ownArrow;\u61d5erticalBar;\u6225n\u0300ABLRTa\u0412\u042a\u0430\u045e\u047f\u037crrow\u0180;BU\u041d\u041e\u0422\u6193ar;\u6913pArrow;\u61f5reve;\u4311eft\u02d2\u043a\0\u0446\0\u0450ightVector;\u6950eeVector;\u695eector\u0100;B\u0459\u045a\u61bdar;\u6956ight\u01d4\u0467\0\u0471eeVector;\u695fector\u0100;B\u047a\u047b\u61c1ar;\u6957ee\u0100;A\u0486\u0487\u62a4rrow;\u61a7\u0100ct\u0492\u0497r;\uc000\ud835\udc9frok;\u4110\u0800NTacdfglmopqstux\u04bd\u04c0\u04c4\u04cb\u04de\u04e2\u04e7\u04ee\u04f5\u0521\u052f\u0536\u0552\u055d\u0560\u0565G;\u414aH\u803b\xd0\u40d0cute\u803b\xc9\u40c9\u0180aiy\u04d2\u04d7\u04dcron;\u411arc\u803b\xca\u40ca;\u442dot;\u4116r;\uc000\ud835\udd08rave\u803b\xc8\u40c8ement;\u6208\u0100ap\u04fa\u04fecr;\u4112ty\u0253\u0506\0\0\u0512mallSquare;\u65fberySmallSquare;\u65ab\u0100gp\u0526\u052aon;\u4118f;\uc000\ud835\udd3csilon;\u4395u\u0100ai\u053c\u0549l\u0100;T\u0542\u0543\u6a75ilde;\u6242librium;\u61cc\u0100ci\u0557\u055ar;\u6130m;\u6a73a;\u4397ml\u803b\xcb\u40cb\u0100ip\u056a\u056fsts;\u6203onentialE;\u6147\u0280cfios\u0585\u0588\u058d\u05b2\u05ccy;\u4424r;\uc000\ud835\udd09lled\u0253\u0597\0\0\u05a3mallSquare;\u65fcerySmallSquare;\u65aa\u0370\u05ba\0\u05bf\0\0\u05c4f;\uc000\ud835\udd3dAll;\u6200riertrf;\u6131c\xf2\u05cb\u0600JTabcdfgorst\u05e8\u05ec\u05ef\u05fa\u0600\u0612\u0616\u061b\u061d\u0623\u066c\u0672cy;\u4403\u803b>\u403emma\u0100;d\u05f7\u05f8\u4393;\u43dcreve;\u411e\u0180eiy\u0607\u060c\u0610dil;\u4122rc;\u411c;\u4413ot;\u4120r;\uc000\ud835\udd0a;\u62d9pf;\uc000\ud835\udd3eeater\u0300EFGLST\u0635\u0644\u064e\u0656\u065b\u0666qual\u0100;L\u063e\u063f\u6265ess;\u62dbullEqual;\u6267reater;\u6aa2ess;\u6277lantEqual;\u6a7eilde;\u6273cr;\uc000\ud835\udca2;\u626b\u0400Aacfiosu\u0685\u068b\u0696\u069b\u069e\u06aa\u06be\u06caRDcy;\u442a\u0100ct\u0690\u0694ek;\u42c7;\u405eirc;\u4124r;\u610clbertSpace;\u610b\u01f0\u06af\0\u06b2f;\u610dizontalLine;\u6500\u0100ct\u06c3\u06c5\xf2\u06a9rok;\u4126mp\u0144\u06d0\u06d8ownHum\xf0\u012fqual;\u624f\u0700EJOacdfgmnostu\u06fa\u06fe\u0703\u0707\u070e\u071a\u071e\u0721\u0728\u0744\u0778\u078b\u078f\u0795cy;\u4415lig;\u4132cy;\u4401cute\u803b\xcd\u40cd\u0100iy\u0713\u0718rc\u803b\xce\u40ce;\u4418ot;\u4130r;\u6111rave\u803b\xcc\u40cc\u0180;ap\u0720\u072f\u073f\u0100cg\u0734\u0737r;\u412ainaryI;\u6148lie\xf3\u03dd\u01f4\u0749\0\u0762\u0100;e\u074d\u074e\u622c\u0100gr\u0753\u0758ral;\u622bsection;\u62c2isible\u0100CT\u076c\u0772omma;\u6063imes;\u6062\u0180gpt\u077f\u0783\u0788on;\u412ef;\uc000\ud835\udd40a;\u4399cr;\u6110ilde;\u4128\u01eb\u079a\0\u079ecy;\u4406l\u803b\xcf\u40cf\u0280cfosu\u07ac\u07b7\u07bc\u07c2\u07d0\u0100iy\u07b1\u07b5rc;\u4134;\u4419r;\uc000\ud835\udd0dpf;\uc000\ud835\udd41\u01e3\u07c7\0\u07ccr;\uc000\ud835\udca5rcy;\u4408kcy;\u4404\u0380HJacfos\u07e4\u07e8\u07ec\u07f1\u07fd\u0802\u0808cy;\u4425cy;\u440cppa;\u439a\u0100ey\u07f6\u07fbdil;\u4136;\u441ar;\uc000\ud835\udd0epf;\uc000\ud835\udd42cr;\uc000\ud835\udca6\u0580JTaceflmost\u0825\u0829\u082c\u0850\u0863\u09b3\u09b8\u09c7\u09cd\u0a37\u0a47cy;\u4409\u803b<\u403c\u0280cmnpr\u0837\u083c\u0841\u0844\u084dute;\u4139bda;\u439bg;\u67ealacetrf;\u6112r;\u619e\u0180aey\u0857\u085c\u0861ron;\u413ddil;\u413b;\u441b\u0100fs\u0868\u0970t\u0500ACDFRTUVar\u087e\u08a9\u08b1\u08e0\u08e6\u08fc\u092f\u095b\u0390\u096a\u0100nr\u0883\u088fgleBracket;\u67e8row\u0180;BR\u0899\u089a\u089e\u6190ar;\u61e4ightArrow;\u61c6eiling;\u6308o\u01f5\u08b7\0\u08c3bleBracket;\u67e6n\u01d4\u08c8\0\u08d2eeVector;\u6961ector\u0100;B\u08db\u08dc\u61c3ar;\u6959loor;\u630aight\u0100AV\u08ef\u08f5rrow;\u6194ector;\u694e\u0100er\u0901\u0917e\u0180;AV\u0909\u090a\u0910\u62a3rrow;\u61a4ector;\u695aiangle\u0180;BE\u0924\u0925\u0929\u62b2ar;\u69cfqual;\u62b4p\u0180DTV\u0937\u0942\u094cownVector;\u6951eeVector;\u6960ector\u0100;B\u0956\u0957\u61bfar;\u6958ector\u0100;B\u0965\u0966\u61bcar;\u6952ight\xe1\u039cs\u0300EFGLST\u097e\u098b\u0995\u099d\u09a2\u09adqualGreater;\u62daullEqual;\u6266reater;\u6276ess;\u6aa1lantEqual;\u6a7dilde;\u6272r;\uc000\ud835\udd0f\u0100;e\u09bd\u09be\u62d8ftarrow;\u61daidot;\u413f\u0180npw\u09d4\u0a16\u0a1bg\u0200LRlr\u09de\u09f7\u0a02\u0a10eft\u0100AR\u09e6\u09ecrrow;\u67f5ightArrow;\u67f7ightArrow;\u67f6eft\u0100ar\u03b3\u0a0aight\xe1\u03bfight\xe1\u03caf;\uc000\ud835\udd43er\u0100LR\u0a22\u0a2ceftArrow;\u6199ightArrow;\u6198\u0180cht\u0a3e\u0a40\u0a42\xf2\u084c;\u61b0rok;\u4141;\u626a\u0400acefiosu\u0a5a\u0a5d\u0a60\u0a77\u0a7c\u0a85\u0a8b\u0a8ep;\u6905y;\u441c\u0100dl\u0a65\u0a6fiumSpace;\u605flintrf;\u6133r;\uc000\ud835\udd10nusPlus;\u6213pf;\uc000\ud835\udd44c\xf2\u0a76;\u439c\u0480Jacefostu\u0aa3\u0aa7\u0aad\u0ac0\u0b14\u0b19\u0d91\u0d97\u0d9ecy;\u440acute;\u4143\u0180aey\u0ab4\u0ab9\u0aberon;\u4147dil;\u4145;\u441d\u0180gsw\u0ac7\u0af0\u0b0eative\u0180MTV\u0ad3\u0adf\u0ae8ediumSpace;\u600bhi\u0100cn\u0ae6\u0ad8\xeb\u0ad9eryThi\xee\u0ad9ted\u0100GL\u0af8\u0b06reaterGreate\xf2\u0673essLes\xf3\u0a48Line;\u400ar;\uc000\ud835\udd11\u0200Bnpt\u0b22\u0b28\u0b37\u0b3areak;\u6060BreakingSpace;\u40a0f;\u6115\u0680;CDEGHLNPRSTV\u0b55\u0b56\u0b6a\u0b7c\u0ba1\u0beb\u0c04\u0c5e\u0c84\u0ca6\u0cd8\u0d61\u0d85\u6aec\u0100ou\u0b5b\u0b64ngruent;\u6262pCap;\u626doubleVerticalBar;\u6226\u0180lqx\u0b83\u0b8a\u0b9bement;\u6209ual\u0100;T\u0b92\u0b93\u6260ilde;\uc000\u2242\u0338ists;\u6204reater\u0380;EFGLST\u0bb6\u0bb7\u0bbd\u0bc9\u0bd3\u0bd8\u0be5\u626fqual;\u6271ullEqual;\uc000\u2267\u0338reater;\uc000\u226b\u0338ess;\u6279lantEqual;\uc000\u2a7e\u0338ilde;\u6275ump\u0144\u0bf2\u0bfdownHump;\uc000\u224e\u0338qual;\uc000\u224f\u0338e\u0100fs\u0c0a\u0c27tTriangle\u0180;BE\u0c1a\u0c1b\u0c21\u62eaar;\uc000\u29cf\u0338qual;\u62ecs\u0300;EGLST\u0c35\u0c36\u0c3c\u0c44\u0c4b\u0c58\u626equal;\u6270reater;\u6278ess;\uc000\u226a\u0338lantEqual;\uc000\u2a7d\u0338ilde;\u6274ested\u0100GL\u0c68\u0c79reaterGreater;\uc000\u2aa2\u0338essLess;\uc000\u2aa1\u0338recedes\u0180;ES\u0c92\u0c93\u0c9b\u6280qual;\uc000\u2aaf\u0338lantEqual;\u62e0\u0100ei\u0cab\u0cb9verseElement;\u620cghtTriangle\u0180;BE\u0ccb\u0ccc\u0cd2\u62ebar;\uc000\u29d0\u0338qual;\u62ed\u0100qu\u0cdd\u0d0cuareSu\u0100bp\u0ce8\u0cf9set\u0100;E\u0cf0\u0cf3\uc000\u228f\u0338qual;\u62e2erset\u0100;E\u0d03\u0d06\uc000\u2290\u0338qual;\u62e3\u0180bcp\u0d13\u0d24\u0d4eset\u0100;E\u0d1b\u0d1e\uc000\u2282\u20d2qual;\u6288ceeds\u0200;EST\u0d32\u0d33\u0d3b\u0d46\u6281qual;\uc000\u2ab0\u0338lantEqual;\u62e1ilde;\uc000\u227f\u0338erset\u0100;E\u0d58\u0d5b\uc000\u2283\u20d2qual;\u6289ilde\u0200;EFT\u0d6e\u0d6f\u0d75\u0d7f\u6241qual;\u6244ullEqual;\u6247ilde;\u6249erticalBar;\u6224cr;\uc000\ud835\udca9ilde\u803b\xd1\u40d1;\u439d\u0700Eacdfgmoprstuv\u0dbd\u0dc2\u0dc9\u0dd5\u0ddb\u0de0\u0de7\u0dfc\u0e02\u0e20\u0e22\u0e32\u0e3f\u0e44lig;\u4152cute\u803b\xd3\u40d3\u0100iy\u0dce\u0dd3rc\u803b\xd4\u40d4;\u441eblac;\u4150r;\uc000\ud835\udd12rave\u803b\xd2\u40d2\u0180aei\u0dee\u0df2\u0df6cr;\u414cga;\u43a9cron;\u439fpf;\uc000\ud835\udd46enCurly\u0100DQ\u0e0e\u0e1aoubleQuote;\u601cuote;\u6018;\u6a54\u0100cl\u0e27\u0e2cr;\uc000\ud835\udcaaash\u803b\xd8\u40d8i\u016c\u0e37\u0e3cde\u803b\xd5\u40d5es;\u6a37ml\u803b\xd6\u40d6er\u0100BP\u0e4b\u0e60\u0100ar\u0e50\u0e53r;\u603eac\u0100ek\u0e5a\u0e5c;\u63deet;\u63b4arenthesis;\u63dc\u0480acfhilors\u0e7f\u0e87\u0e8a\u0e8f\u0e92\u0e94\u0e9d\u0eb0\u0efcrtialD;\u6202y;\u441fr;\uc000\ud835\udd13i;\u43a6;\u43a0usMinus;\u40b1\u0100ip\u0ea2\u0eadncareplan\xe5\u069df;\u6119\u0200;eio\u0eb9\u0eba\u0ee0\u0ee4\u6abbcedes\u0200;EST\u0ec8\u0ec9\u0ecf\u0eda\u627aqual;\u6aaflantEqual;\u627cilde;\u627eme;\u6033\u0100dp\u0ee9\u0eeeuct;\u620fortion\u0100;a\u0225\u0ef9l;\u621d\u0100ci\u0f01\u0f06r;\uc000\ud835\udcab;\u43a8\u0200Ufos\u0f11\u0f16\u0f1b\u0f1fOT\u803b"\u4022r;\uc000\ud835\udd14pf;\u611acr;\uc000\ud835\udcac\u0600BEacefhiorsu\u0f3e\u0f43\u0f47\u0f60\u0f73\u0fa7\u0faa\u0fad\u1096\u10a9\u10b4\u10bearr;\u6910G\u803b\xae\u40ae\u0180cnr\u0f4e\u0f53\u0f56ute;\u4154g;\u67ebr\u0100;t\u0f5c\u0f5d\u61a0l;\u6916\u0180aey\u0f67\u0f6c\u0f71ron;\u4158dil;\u4156;\u4420\u0100;v\u0f78\u0f79\u611cerse\u0100EU\u0f82\u0f99\u0100lq\u0f87\u0f8eement;\u620builibrium;\u61cbpEquilibrium;\u696fr\xbb\u0f79o;\u43a1ght\u0400ACDFTUVa\u0fc1\u0feb\u0ff3\u1022\u1028\u105b\u1087\u03d8\u0100nr\u0fc6\u0fd2gleBracket;\u67e9row\u0180;BL\u0fdc\u0fdd\u0fe1\u6192ar;\u61e5eftArrow;\u61c4eiling;\u6309o\u01f5\u0ff9\0\u1005bleBracket;\u67e7n\u01d4\u100a\0\u1014eeVector;\u695dector\u0100;B\u101d\u101e\u61c2ar;\u6955loor;\u630b\u0100er\u102d\u1043e\u0180;AV\u1035\u1036\u103c\u62a2rrow;\u61a6ector;\u695biangle\u0180;BE\u1050\u1051\u1055\u62b3ar;\u69d0qual;\u62b5p\u0180DTV\u1063\u106e\u1078ownVector;\u694feeVector;\u695cector\u0100;B\u1082\u1083\u61bear;\u6954ector\u0100;B\u1091\u1092\u61c0ar;\u6953\u0100pu\u109b\u109ef;\u611dndImplies;\u6970ightarrow;\u61db\u0100ch\u10b9\u10bcr;\u611b;\u61b1leDelayed;\u69f4\u0680HOacfhimoqstu\u10e4\u10f1\u10f7\u10fd\u1119\u111e\u1151\u1156\u1161\u1167\u11b5\u11bb\u11bf\u0100Cc\u10e9\u10eeHcy;\u4429y;\u4428FTcy;\u442ccute;\u415a\u0280;aeiy\u1108\u1109\u110e\u1113\u1117\u6abcron;\u4160dil;\u415erc;\u415c;\u4421r;\uc000\ud835\udd16ort\u0200DLRU\u112a\u1134\u113e\u1149ownArrow\xbb\u041eeftArrow\xbb\u089aightArrow\xbb\u0fddpArrow;\u6191gma;\u43a3allCircle;\u6218pf;\uc000\ud835\udd4a\u0272\u116d\0\0\u1170t;\u621aare\u0200;ISU\u117b\u117c\u1189\u11af\u65a1ntersection;\u6293u\u0100bp\u118f\u119eset\u0100;E\u1197\u1198\u628fqual;\u6291erset\u0100;E\u11a8\u11a9\u6290qual;\u6292nion;\u6294cr;\uc000\ud835\udcaear;\u62c6\u0200bcmp\u11c8\u11db\u1209\u120b\u0100;s\u11cd\u11ce\u62d0et\u0100;E\u11cd\u11d5qual;\u6286\u0100ch\u11e0\u1205eeds\u0200;EST\u11ed\u11ee\u11f4\u11ff\u627bqual;\u6ab0lantEqual;\u627dilde;\u627fTh\xe1\u0f8c;\u6211\u0180;es\u1212\u1213\u1223\u62d1rset\u0100;E\u121c\u121d\u6283qual;\u6287et\xbb\u1213\u0580HRSacfhiors\u123e\u1244\u1249\u1255\u125e\u1271\u1276\u129f\u12c2\u12c8\u12d1ORN\u803b\xde\u40deADE;\u6122\u0100Hc\u124e\u1252cy;\u440by;\u4426\u0100bu\u125a\u125c;\u4009;\u43a4\u0180aey\u1265\u126a\u126fron;\u4164dil;\u4162;\u4422r;\uc000\ud835\udd17\u0100ei\u127b\u1289\u01f2\u1280\0\u1287efore;\u6234a;\u4398\u0100cn\u128e\u1298kSpace;\uc000\u205f\u200aSpace;\u6009lde\u0200;EFT\u12ab\u12ac\u12b2\u12bc\u623cqual;\u6243ullEqual;\u6245ilde;\u6248pf;\uc000\ud835\udd4bipleDot;\u60db\u0100ct\u12d6\u12dbr;\uc000\ud835\udcafrok;\u4166\u0ae1\u12f7\u130e\u131a\u1326\0\u132c\u1331\0\0\0\0\0\u1338\u133d\u1377\u1385\0\u13ff\u1404\u140a\u1410\u0100cr\u12fb\u1301ute\u803b\xda\u40dar\u0100;o\u1307\u1308\u619fcir;\u6949r\u01e3\u1313\0\u1316y;\u440eve;\u416c\u0100iy\u131e\u1323rc\u803b\xdb\u40db;\u4423blac;\u4170r;\uc000\ud835\udd18rave\u803b\xd9\u40d9acr;\u416a\u0100di\u1341\u1369er\u0100BP\u1348\u135d\u0100ar\u134d\u1350r;\u405fac\u0100ek\u1357\u1359;\u63dfet;\u63b5arenthesis;\u63ddon\u0100;P\u1370\u1371\u62c3lus;\u628e\u0100gp\u137b\u137fon;\u4172f;\uc000\ud835\udd4c\u0400ADETadps\u1395\u13ae\u13b8\u13c4\u03e8\u13d2\u13d7\u13f3rrow\u0180;BD\u1150\u13a0\u13a4ar;\u6912ownArrow;\u61c5ownArrow;\u6195quilibrium;\u696eee\u0100;A\u13cb\u13cc\u62a5rrow;\u61a5own\xe1\u03f3er\u0100LR\u13de\u13e8eftArrow;\u6196ightArrow;\u6197i\u0100;l\u13f9\u13fa\u43d2on;\u43a5ing;\u416ecr;\uc000\ud835\udcb0ilde;\u4168ml\u803b\xdc\u40dc\u0480Dbcdefosv\u1427\u142c\u1430\u1433\u143e\u1485\u148a\u1490\u1496ash;\u62abar;\u6aeby;\u4412ash\u0100;l\u143b\u143c\u62a9;\u6ae6\u0100er\u1443\u1445;\u62c1\u0180bty\u144c\u1450\u147aar;\u6016\u0100;i\u144f\u1455cal\u0200BLST\u1461\u1465\u146a\u1474ar;\u6223ine;\u407ceparator;\u6758ilde;\u6240ThinSpace;\u600ar;\uc000\ud835\udd19pf;\uc000\ud835\udd4dcr;\uc000\ud835\udcb1dash;\u62aa\u0280cefos\u14a7\u14ac\u14b1\u14b6\u14bcirc;\u4174dge;\u62c0r;\uc000\ud835\udd1apf;\uc000\ud835\udd4ecr;\uc000\ud835\udcb2\u0200fios\u14cb\u14d0\u14d2\u14d8r;\uc000\ud835\udd1b;\u439epf;\uc000\ud835\udd4fcr;\uc000\ud835\udcb3\u0480AIUacfosu\u14f1\u14f5\u14f9\u14fd\u1504\u150f\u1514\u151a\u1520cy;\u442fcy;\u4407cy;\u442ecute\u803b\xdd\u40dd\u0100iy\u1509\u150drc;\u4176;\u442br;\uc000\ud835\udd1cpf;\uc000\ud835\udd50cr;\uc000\ud835\udcb4ml;\u4178\u0400Hacdefos\u1535\u1539\u153f\u154b\u154f\u155d\u1560\u1564cy;\u4416cute;\u4179\u0100ay\u1544\u1549ron;\u417d;\u4417ot;\u417b\u01f2\u1554\0\u155boWidt\xe8\u0ad9a;\u4396r;\u6128pf;\u6124cr;\uc000\ud835\udcb5\u0be1\u1583\u158a\u1590\0\u15b0\u15b6\u15bf\0\0\0\0\u15c6\u15db\u15eb\u165f\u166d\0\u1695\u169b\u16b2\u16b9\0\u16becute\u803b\xe1\u40e1reve;\u4103\u0300;Ediuy\u159c\u159d\u15a1\u15a3\u15a8\u15ad\u623e;\uc000\u223e\u0333;\u623frc\u803b\xe2\u40e2te\u80bb\xb4\u0306;\u4430lig\u803b\xe6\u40e6\u0100;r\xb2\u15ba;\uc000\ud835\udd1erave\u803b\xe0\u40e0\u0100ep\u15ca\u15d6\u0100fp\u15cf\u15d4sym;\u6135\xe8\u15d3ha;\u43b1\u0100ap\u15dfc\u0100cl\u15e4\u15e7r;\u4101g;\u6a3f\u0264\u15f0\0\0\u160a\u0280;adsv\u15fa\u15fb\u15ff\u1601\u1607\u6227nd;\u6a55;\u6a5clope;\u6a58;\u6a5a\u0380;elmrsz\u1618\u1619\u161b\u161e\u163f\u164f\u1659\u6220;\u69a4e\xbb\u1619sd\u0100;a\u1625\u1626\u6221\u0461\u1630\u1632\u1634\u1636\u1638\u163a\u163c\u163e;\u69a8;\u69a9;\u69aa;\u69ab;\u69ac;\u69ad;\u69ae;\u69aft\u0100;v\u1645\u1646\u621fb\u0100;d\u164c\u164d\u62be;\u699d\u0100pt\u1654\u1657h;\u6222\xbb\xb9arr;\u637c\u0100gp\u1663\u1667on;\u4105f;\uc000\ud835\udd52\u0380;Eaeiop\u12c1\u167b\u167d\u1682\u1684\u1687\u168a;\u6a70cir;\u6a6f;\u624ad;\u624bs;\u4027rox\u0100;e\u12c1\u1692\xf1\u1683ing\u803b\xe5\u40e5\u0180cty\u16a1\u16a6\u16a8r;\uc000\ud835\udcb6;\u402amp\u0100;e\u12c1\u16af\xf1\u0288ilde\u803b\xe3\u40e3ml\u803b\xe4\u40e4\u0100ci\u16c2\u16c8onin\xf4\u0272nt;\u6a11\u0800Nabcdefiklnoprsu\u16ed\u16f1\u1730\u173c\u1743\u1748\u1778\u177d\u17e0\u17e6\u1839\u1850\u170d\u193d\u1948\u1970ot;\u6aed\u0100cr\u16f6\u171ek\u0200ceps\u1700\u1705\u170d\u1713ong;\u624cpsilon;\u43f6rime;\u6035im\u0100;e\u171a\u171b\u623dq;\u62cd\u0176\u1722\u1726ee;\u62bded\u0100;g\u172c\u172d\u6305e\xbb\u172drk\u0100;t\u135c\u1737brk;\u63b6\u0100oy\u1701\u1741;\u4431quo;\u601e\u0280cmprt\u1753\u175b\u1761\u1764\u1768aus\u0100;e\u010a\u0109ptyv;\u69b0s\xe9\u170cno\xf5\u0113\u0180ahw\u176f\u1771\u1773;\u43b2;\u6136een;\u626cr;\uc000\ud835\udd1fg\u0380costuvw\u178d\u179d\u17b3\u17c1\u17d5\u17db\u17de\u0180aiu\u1794\u1796\u179a\xf0\u0760rc;\u65efp\xbb\u1371\u0180dpt\u17a4\u17a8\u17adot;\u6a00lus;\u6a01imes;\u6a02\u0271\u17b9\0\0\u17becup;\u6a06ar;\u6605riangle\u0100du\u17cd\u17d2own;\u65bdp;\u65b3plus;\u6a04e\xe5\u1444\xe5\u14adarow;\u690d\u0180ako\u17ed\u1826\u1835\u0100cn\u17f2\u1823k\u0180lst\u17fa\u05ab\u1802ozenge;\u69ebriangle\u0200;dlr\u1812\u1813\u1818\u181d\u65b4own;\u65beeft;\u65c2ight;\u65b8k;\u6423\u01b1\u182b\0\u1833\u01b2\u182f\0\u1831;\u6592;\u65914;\u6593ck;\u6588\u0100eo\u183e\u184d\u0100;q\u1843\u1846\uc000=\u20e5uiv;\uc000\u2261\u20e5t;\u6310\u0200ptwx\u1859\u185e\u1867\u186cf;\uc000\ud835\udd53\u0100;t\u13cb\u1863om\xbb\u13cctie;\u62c8\u0600DHUVbdhmptuv\u1885\u1896\u18aa\u18bb\u18d7\u18db\u18ec\u18ff\u1905\u190a\u1910\u1921\u0200LRlr\u188e\u1890\u1892\u1894;\u6557;\u6554;\u6556;\u6553\u0280;DUdu\u18a1\u18a2\u18a4\u18a6\u18a8\u6550;\u6566;\u6569;\u6564;\u6567\u0200LRlr\u18b3\u18b5\u18b7\u18b9;\u655d;\u655a;\u655c;\u6559\u0380;HLRhlr\u18ca\u18cb\u18cd\u18cf\u18d1\u18d3\u18d5\u6551;\u656c;\u6563;\u6560;\u656b;\u6562;\u655fox;\u69c9\u0200LRlr\u18e4\u18e6\u18e8\u18ea;\u6555;\u6552;\u6510;\u650c\u0280;DUdu\u06bd\u18f7\u18f9\u18fb\u18fd;\u6565;\u6568;\u652c;\u6534inus;\u629flus;\u629eimes;\u62a0\u0200LRlr\u1919\u191b\u191d\u191f;\u655b;\u6558;\u6518;\u6514\u0380;HLRhlr\u1930\u1931\u1933\u1935\u1937\u1939\u193b\u6502;\u656a;\u6561;\u655e;\u653c;\u6524;\u651c\u0100ev\u0123\u1942bar\u803b\xa6\u40a6\u0200ceio\u1951\u1956\u195a\u1960r;\uc000\ud835\udcb7mi;\u604fm\u0100;e\u171a\u171cl\u0180;bh\u1968\u1969\u196b\u405c;\u69c5sub;\u67c8\u016c\u1974\u197el\u0100;e\u1979\u197a\u6022t\xbb\u197ap\u0180;Ee\u012f\u1985\u1987;\u6aae\u0100;q\u06dc\u06db\u0ce1\u19a7\0\u19e8\u1a11\u1a15\u1a32\0\u1a37\u1a50\0\0\u1ab4\0\0\u1ac1\0\0\u1b21\u1b2e\u1b4d\u1b52\0\u1bfd\0\u1c0c\u0180cpr\u19ad\u19b2\u19ddute;\u4107\u0300;abcds\u19bf\u19c0\u19c4\u19ca\u19d5\u19d9\u6229nd;\u6a44rcup;\u6a49\u0100au\u19cf\u19d2p;\u6a4bp;\u6a47ot;\u6a40;\uc000\u2229\ufe00\u0100eo\u19e2\u19e5t;\u6041\xee\u0693\u0200aeiu\u19f0\u19fb\u1a01\u1a05\u01f0\u19f5\0\u19f8s;\u6a4don;\u410ddil\u803b\xe7\u40e7rc;\u4109ps\u0100;s\u1a0c\u1a0d\u6a4cm;\u6a50ot;\u410b\u0180dmn\u1a1b\u1a20\u1a26il\u80bb\xb8\u01adptyv;\u69b2t\u8100\xa2;e\u1a2d\u1a2e\u40a2r\xe4\u01b2r;\uc000\ud835\udd20\u0180cei\u1a3d\u1a40\u1a4dy;\u4447ck\u0100;m\u1a47\u1a48\u6713ark\xbb\u1a48;\u43c7r\u0380;Ecefms\u1a5f\u1a60\u1a62\u1a6b\u1aa4\u1aaa\u1aae\u65cb;\u69c3\u0180;el\u1a69\u1a6a\u1a6d\u42c6q;\u6257e\u0261\u1a74\0\0\u1a88rrow\u0100lr\u1a7c\u1a81eft;\u61baight;\u61bb\u0280RSacd\u1a92\u1a94\u1a96\u1a9a\u1a9f\xbb\u0f47;\u64c8st;\u629birc;\u629aash;\u629dnint;\u6a10id;\u6aefcir;\u69c2ubs\u0100;u\u1abb\u1abc\u6663it\xbb\u1abc\u02ec\u1ac7\u1ad4\u1afa\0\u1b0aon\u0100;e\u1acd\u1ace\u403a\u0100;q\xc7\xc6\u026d\u1ad9\0\0\u1ae2a\u0100;t\u1ade\u1adf\u402c;\u4040\u0180;fl\u1ae8\u1ae9\u1aeb\u6201\xee\u1160e\u0100mx\u1af1\u1af6ent\xbb\u1ae9e\xf3\u024d\u01e7\u1afe\0\u1b07\u0100;d\u12bb\u1b02ot;\u6a6dn\xf4\u0246\u0180fry\u1b10\u1b14\u1b17;\uc000\ud835\udd54o\xe4\u0254\u8100\xa9;s\u0155\u1b1dr;\u6117\u0100ao\u1b25\u1b29rr;\u61b5ss;\u6717\u0100cu\u1b32\u1b37r;\uc000\ud835\udcb8\u0100bp\u1b3c\u1b44\u0100;e\u1b41\u1b42\u6acf;\u6ad1\u0100;e\u1b49\u1b4a\u6ad0;\u6ad2dot;\u62ef\u0380delprvw\u1b60\u1b6c\u1b77\u1b82\u1bac\u1bd4\u1bf9arr\u0100lr\u1b68\u1b6a;\u6938;\u6935\u0270\u1b72\0\0\u1b75r;\u62dec;\u62dfarr\u0100;p\u1b7f\u1b80\u61b6;\u693d\u0300;bcdos\u1b8f\u1b90\u1b96\u1ba1\u1ba5\u1ba8\u622arcap;\u6a48\u0100au\u1b9b\u1b9ep;\u6a46p;\u6a4aot;\u628dr;\u6a45;\uc000\u222a\ufe00\u0200alrv\u1bb5\u1bbf\u1bde\u1be3rr\u0100;m\u1bbc\u1bbd\u61b7;\u693cy\u0180evw\u1bc7\u1bd4\u1bd8q\u0270\u1bce\0\0\u1bd2re\xe3\u1b73u\xe3\u1b75ee;\u62ceedge;\u62cfen\u803b\xa4\u40a4earrow\u0100lr\u1bee\u1bf3eft\xbb\u1b80ight\xbb\u1bbde\xe4\u1bdd\u0100ci\u1c01\u1c07onin\xf4\u01f7nt;\u6231lcty;\u632d\u0980AHabcdefhijlorstuwz\u1c38\u1c3b\u1c3f\u1c5d\u1c69\u1c75\u1c8a\u1c9e\u1cac\u1cb7\u1cfb\u1cff\u1d0d\u1d7b\u1d91\u1dab\u1dbb\u1dc6\u1dcdr\xf2\u0381ar;\u6965\u0200glrs\u1c48\u1c4d\u1c52\u1c54ger;\u6020eth;\u6138\xf2\u1133h\u0100;v\u1c5a\u1c5b\u6010\xbb\u090a\u016b\u1c61\u1c67arow;\u690fa\xe3\u0315\u0100ay\u1c6e\u1c73ron;\u410f;\u4434\u0180;ao\u0332\u1c7c\u1c84\u0100gr\u02bf\u1c81r;\u61catseq;\u6a77\u0180glm\u1c91\u1c94\u1c98\u803b\xb0\u40b0ta;\u43b4ptyv;\u69b1\u0100ir\u1ca3\u1ca8sht;\u697f;\uc000\ud835\udd21ar\u0100lr\u1cb3\u1cb5\xbb\u08dc\xbb\u101e\u0280aegsv\u1cc2\u0378\u1cd6\u1cdc\u1ce0m\u0180;os\u0326\u1cca\u1cd4nd\u0100;s\u0326\u1cd1uit;\u6666amma;\u43ddin;\u62f2\u0180;io\u1ce7\u1ce8\u1cf8\u40f7de\u8100\xf7;o\u1ce7\u1cf0ntimes;\u62c7n\xf8\u1cf7cy;\u4452c\u026f\u1d06\0\0\u1d0arn;\u631eop;\u630d\u0280lptuw\u1d18\u1d1d\u1d22\u1d49\u1d55lar;\u4024f;\uc000\ud835\udd55\u0280;emps\u030b\u1d2d\u1d37\u1d3d\u1d42q\u0100;d\u0352\u1d33ot;\u6251inus;\u6238lus;\u6214quare;\u62a1blebarwedg\xe5\xfan\u0180adh\u112e\u1d5d\u1d67ownarrow\xf3\u1c83arpoon\u0100lr\u1d72\u1d76ef\xf4\u1cb4igh\xf4\u1cb6\u0162\u1d7f\u1d85karo\xf7\u0f42\u026f\u1d8a\0\0\u1d8ern;\u631fop;\u630c\u0180cot\u1d98\u1da3\u1da6\u0100ry\u1d9d\u1da1;\uc000\ud835\udcb9;\u4455l;\u69f6rok;\u4111\u0100dr\u1db0\u1db4ot;\u62f1i\u0100;f\u1dba\u1816\u65bf\u0100ah\u1dc0\u1dc3r\xf2\u0429a\xf2\u0fa6angle;\u69a6\u0100ci\u1dd2\u1dd5y;\u445fgrarr;\u67ff\u0900Dacdefglmnopqrstux\u1e01\u1e09\u1e19\u1e38\u0578\u1e3c\u1e49\u1e61\u1e7e\u1ea5\u1eaf\u1ebd\u1ee1\u1f2a\u1f37\u1f44\u1f4e\u1f5a\u0100Do\u1e06\u1d34o\xf4\u1c89\u0100cs\u1e0e\u1e14ute\u803b\xe9\u40e9ter;\u6a6e\u0200aioy\u1e22\u1e27\u1e31\u1e36ron;\u411br\u0100;c\u1e2d\u1e2e\u6256\u803b\xea\u40ealon;\u6255;\u444dot;\u4117\u0100Dr\u1e41\u1e45ot;\u6252;\uc000\ud835\udd22\u0180;rs\u1e50\u1e51\u1e57\u6a9aave\u803b\xe8\u40e8\u0100;d\u1e5c\u1e5d\u6a96ot;\u6a98\u0200;ils\u1e6a\u1e6b\u1e72\u1e74\u6a99nters;\u63e7;\u6113\u0100;d\u1e79\u1e7a\u6a95ot;\u6a97\u0180aps\u1e85\u1e89\u1e97cr;\u4113ty\u0180;sv\u1e92\u1e93\u1e95\u6205et\xbb\u1e93p\u01001;\u1e9d\u1ea4\u0133\u1ea1\u1ea3;\u6004;\u6005\u6003\u0100gs\u1eaa\u1eac;\u414bp;\u6002\u0100gp\u1eb4\u1eb8on;\u4119f;\uc000\ud835\udd56\u0180als\u1ec4\u1ece\u1ed2r\u0100;s\u1eca\u1ecb\u62d5l;\u69e3us;\u6a71i\u0180;lv\u1eda\u1edb\u1edf\u43b5on\xbb\u1edb;\u43f5\u0200csuv\u1eea\u1ef3\u1f0b\u1f23\u0100io\u1eef\u1e31rc\xbb\u1e2e\u0269\u1ef9\0\0\u1efb\xed\u0548ant\u0100gl\u1f02\u1f06tr\xbb\u1e5dess\xbb\u1e7a\u0180aei\u1f12\u1f16\u1f1als;\u403dst;\u625fv\u0100;D\u0235\u1f20D;\u6a78parsl;\u69e5\u0100Da\u1f2f\u1f33ot;\u6253rr;\u6971\u0180cdi\u1f3e\u1f41\u1ef8r;\u612fo\xf4\u0352\u0100ah\u1f49\u1f4b;\u43b7\u803b\xf0\u40f0\u0100mr\u1f53\u1f57l\u803b\xeb\u40ebo;\u60ac\u0180cip\u1f61\u1f64\u1f67l;\u4021s\xf4\u056e\u0100eo\u1f6c\u1f74ctatio\xee\u0559nential\xe5\u0579\u09e1\u1f92\0\u1f9e\0\u1fa1\u1fa7\0\0\u1fc6\u1fcc\0\u1fd3\0\u1fe6\u1fea\u2000\0\u2008\u205allingdotse\xf1\u1e44y;\u4444male;\u6640\u0180ilr\u1fad\u1fb3\u1fc1lig;\u8000\ufb03\u0269\u1fb9\0\0\u1fbdg;\u8000\ufb00ig;\u8000\ufb04;\uc000\ud835\udd23lig;\u8000\ufb01lig;\uc000fj\u0180alt\u1fd9\u1fdc\u1fe1t;\u666dig;\u8000\ufb02ns;\u65b1of;\u4192\u01f0\u1fee\0\u1ff3f;\uc000\ud835\udd57\u0100ak\u05bf\u1ff7\u0100;v\u1ffc\u1ffd\u62d4;\u6ad9artint;\u6a0d\u0100ao\u200c\u2055\u0100cs\u2011\u2052\u03b1\u201a\u2030\u2038\u2045\u2048\0\u2050\u03b2\u2022\u2025\u2027\u202a\u202c\0\u202e\u803b\xbd\u40bd;\u6153\u803b\xbc\u40bc;\u6155;\u6159;\u615b\u01b3\u2034\0\u2036;\u6154;\u6156\u02b4\u203e\u2041\0\0\u2043\u803b\xbe\u40be;\u6157;\u615c5;\u6158\u01b6\u204c\0\u204e;\u615a;\u615d8;\u615el;\u6044wn;\u6322cr;\uc000\ud835\udcbb\u0880Eabcdefgijlnorstv\u2082\u2089\u209f\u20a5\u20b0\u20b4\u20f0\u20f5\u20fa\u20ff\u2103\u2112\u2138\u0317\u213e\u2152\u219e\u0100;l\u064d\u2087;\u6a8c\u0180cmp\u2090\u2095\u209dute;\u41f5ma\u0100;d\u209c\u1cda\u43b3;\u6a86reve;\u411f\u0100iy\u20aa\u20aerc;\u411d;\u4433ot;\u4121\u0200;lqs\u063e\u0642\u20bd\u20c9\u0180;qs\u063e\u064c\u20c4lan\xf4\u0665\u0200;cdl\u0665\u20d2\u20d5\u20e5c;\u6aa9ot\u0100;o\u20dc\u20dd\u6a80\u0100;l\u20e2\u20e3\u6a82;\u6a84\u0100;e\u20ea\u20ed\uc000\u22db\ufe00s;\u6a94r;\uc000\ud835\udd24\u0100;g\u0673\u061bmel;\u6137cy;\u4453\u0200;Eaj\u065a\u210c\u210e\u2110;\u6a92;\u6aa5;\u6aa4\u0200Eaes\u211b\u211d\u2129\u2134;\u6269p\u0100;p\u2123\u2124\u6a8arox\xbb\u2124\u0100;q\u212e\u212f\u6a88\u0100;q\u212e\u211bim;\u62e7pf;\uc000\ud835\udd58\u0100ci\u2143\u2146r;\u610am\u0180;el\u066b\u214e\u2150;\u6a8e;\u6a90\u8300>;cdlqr\u05ee\u2160\u216a\u216e\u2173\u2179\u0100ci\u2165\u2167;\u6aa7r;\u6a7aot;\u62d7Par;\u6995uest;\u6a7c\u0280adels\u2184\u216a\u2190\u0656\u219b\u01f0\u2189\0\u218epro\xf8\u209er;\u6978q\u0100lq\u063f\u2196les\xf3\u2088i\xed\u066b\u0100en\u21a3\u21adrtneqq;\uc000\u2269\ufe00\xc5\u21aa\u0500Aabcefkosy\u21c4\u21c7\u21f1\u21f5\u21fa\u2218\u221d\u222f\u2268\u227dr\xf2\u03a0\u0200ilmr\u21d0\u21d4\u21d7\u21dbrs\xf0\u1484f\xbb\u2024il\xf4\u06a9\u0100dr\u21e0\u21e4cy;\u444a\u0180;cw\u08f4\u21eb\u21efir;\u6948;\u61adar;\u610firc;\u4125\u0180alr\u2201\u220e\u2213rts\u0100;u\u2209\u220a\u6665it\xbb\u220alip;\u6026con;\u62b9r;\uc000\ud835\udd25s\u0100ew\u2223\u2229arow;\u6925arow;\u6926\u0280amopr\u223a\u223e\u2243\u225e\u2263rr;\u61fftht;\u623bk\u0100lr\u2249\u2253eftarrow;\u61a9ightarrow;\u61aaf;\uc000\ud835\udd59bar;\u6015\u0180clt\u226f\u2274\u2278r;\uc000\ud835\udcbdas\xe8\u21f4rok;\u4127\u0100bp\u2282\u2287ull;\u6043hen\xbb\u1c5b\u0ae1\u22a3\0\u22aa\0\u22b8\u22c5\u22ce\0\u22d5\u22f3\0\0\u22f8\u2322\u2367\u2362\u237f\0\u2386\u23aa\u23b4cute\u803b\xed\u40ed\u0180;iy\u0771\u22b0\u22b5rc\u803b\xee\u40ee;\u4438\u0100cx\u22bc\u22bfy;\u4435cl\u803b\xa1\u40a1\u0100fr\u039f\u22c9;\uc000\ud835\udd26rave\u803b\xec\u40ec\u0200;ino\u073e\u22dd\u22e9\u22ee\u0100in\u22e2\u22e6nt;\u6a0ct;\u622dfin;\u69dcta;\u6129lig;\u4133\u0180aop\u22fe\u231a\u231d\u0180cgt\u2305\u2308\u2317r;\u412b\u0180elp\u071f\u230f\u2313in\xe5\u078ear\xf4\u0720h;\u4131f;\u62b7ed;\u41b5\u0280;cfot\u04f4\u232c\u2331\u233d\u2341are;\u6105in\u0100;t\u2338\u2339\u621eie;\u69dddo\xf4\u2319\u0280;celp\u0757\u234c\u2350\u235b\u2361al;\u62ba\u0100gr\u2355\u2359er\xf3\u1563\xe3\u234darhk;\u6a17rod;\u6a3c\u0200cgpt\u236f\u2372\u2376\u237by;\u4451on;\u412ff;\uc000\ud835\udd5aa;\u43b9uest\u803b\xbf\u40bf\u0100ci\u238a\u238fr;\uc000\ud835\udcben\u0280;Edsv\u04f4\u239b\u239d\u23a1\u04f3;\u62f9ot;\u62f5\u0100;v\u23a6\u23a7\u62f4;\u62f3\u0100;i\u0777\u23aelde;\u4129\u01eb\u23b8\0\u23bccy;\u4456l\u803b\xef\u40ef\u0300cfmosu\u23cc\u23d7\u23dc\u23e1\u23e7\u23f5\u0100iy\u23d1\u23d5rc;\u4135;\u4439r;\uc000\ud835\udd27ath;\u4237pf;\uc000\ud835\udd5b\u01e3\u23ec\0\u23f1r;\uc000\ud835\udcbfrcy;\u4458kcy;\u4454\u0400acfghjos\u240b\u2416\u2422\u2427\u242d\u2431\u2435\u243bppa\u0100;v\u2413\u2414\u43ba;\u43f0\u0100ey\u241b\u2420dil;\u4137;\u443ar;\uc000\ud835\udd28reen;\u4138cy;\u4445cy;\u445cpf;\uc000\ud835\udd5ccr;\uc000\ud835\udcc0\u0b80ABEHabcdefghjlmnoprstuv\u2470\u2481\u2486\u248d\u2491\u250e\u253d\u255a\u2580\u264e\u265e\u2665\u2679\u267d\u269a\u26b2\u26d8\u275d\u2768\u278b\u27c0\u2801\u2812\u0180art\u2477\u247a\u247cr\xf2\u09c6\xf2\u0395ail;\u691barr;\u690e\u0100;g\u0994\u248b;\u6a8bar;\u6962\u0963\u24a5\0\u24aa\0\u24b1\0\0\0\0\0\u24b5\u24ba\0\u24c6\u24c8\u24cd\0\u24f9ute;\u413amptyv;\u69b4ra\xee\u084cbda;\u43bbg\u0180;dl\u088e\u24c1\u24c3;\u6991\xe5\u088e;\u6a85uo\u803b\xab\u40abr\u0400;bfhlpst\u0899\u24de\u24e6\u24e9\u24eb\u24ee\u24f1\u24f5\u0100;f\u089d\u24e3s;\u691fs;\u691d\xeb\u2252p;\u61abl;\u6939im;\u6973l;\u61a2\u0180;ae\u24ff\u2500\u2504\u6aabil;\u6919\u0100;s\u2509\u250a\u6aad;\uc000\u2aad\ufe00\u0180abr\u2515\u2519\u251drr;\u690crk;\u6772\u0100ak\u2522\u252cc\u0100ek\u2528\u252a;\u407b;\u405b\u0100es\u2531\u2533;\u698bl\u0100du\u2539\u253b;\u698f;\u698d\u0200aeuy\u2546\u254b\u2556\u2558ron;\u413e\u0100di\u2550\u2554il;\u413c\xec\u08b0\xe2\u2529;\u443b\u0200cqrs\u2563\u2566\u256d\u257da;\u6936uo\u0100;r\u0e19\u1746\u0100du\u2572\u2577har;\u6967shar;\u694bh;\u61b2\u0280;fgqs\u258b\u258c\u0989\u25f3\u25ff\u6264t\u0280ahlrt\u2598\u25a4\u25b7\u25c2\u25e8rrow\u0100;t\u0899\u25a1a\xe9\u24f6arpoon\u0100du\u25af\u25b4own\xbb\u045ap\xbb\u0966eftarrows;\u61c7ight\u0180ahs\u25cd\u25d6\u25derrow\u0100;s\u08f4\u08a7arpoon\xf3\u0f98quigarro\xf7\u21f0hreetimes;\u62cb\u0180;qs\u258b\u0993\u25falan\xf4\u09ac\u0280;cdgs\u09ac\u260a\u260d\u261d\u2628c;\u6aa8ot\u0100;o\u2614\u2615\u6a7f\u0100;r\u261a\u261b\u6a81;\u6a83\u0100;e\u2622\u2625\uc000\u22da\ufe00s;\u6a93\u0280adegs\u2633\u2639\u263d\u2649\u264bppro\xf8\u24c6ot;\u62d6q\u0100gq\u2643\u2645\xf4\u0989gt\xf2\u248c\xf4\u099bi\xed\u09b2\u0180ilr\u2655\u08e1\u265asht;\u697c;\uc000\ud835\udd29\u0100;E\u099c\u2663;\u6a91\u0161\u2669\u2676r\u0100du\u25b2\u266e\u0100;l\u0965\u2673;\u696alk;\u6584cy;\u4459\u0280;acht\u0a48\u2688\u268b\u2691\u2696r\xf2\u25c1orne\xf2\u1d08ard;\u696bri;\u65fa\u0100io\u269f\u26a4dot;\u4140ust\u0100;a\u26ac\u26ad\u63b0che\xbb\u26ad\u0200Eaes\u26bb\u26bd\u26c9\u26d4;\u6268p\u0100;p\u26c3\u26c4\u6a89rox\xbb\u26c4\u0100;q\u26ce\u26cf\u6a87\u0100;q\u26ce\u26bbim;\u62e6\u0400abnoptwz\u26e9\u26f4\u26f7\u271a\u272f\u2741\u2747\u2750\u0100nr\u26ee\u26f1g;\u67ecr;\u61fdr\xeb\u08c1g\u0180lmr\u26ff\u270d\u2714eft\u0100ar\u09e6\u2707ight\xe1\u09f2apsto;\u67fcight\xe1\u09fdparrow\u0100lr\u2725\u2729ef\xf4\u24edight;\u61ac\u0180afl\u2736\u2739\u273dr;\u6985;\uc000\ud835\udd5dus;\u6a2dimes;\u6a34\u0161\u274b\u274fst;\u6217\xe1\u134e\u0180;ef\u2757\u2758\u1800\u65cange\xbb\u2758ar\u0100;l\u2764\u2765\u4028t;\u6993\u0280achmt\u2773\u2776\u277c\u2785\u2787r\xf2\u08a8orne\xf2\u1d8car\u0100;d\u0f98\u2783;\u696d;\u600eri;\u62bf\u0300achiqt\u2798\u279d\u0a40\u27a2\u27ae\u27bbquo;\u6039r;\uc000\ud835\udcc1m\u0180;eg\u09b2\u27aa\u27ac;\u6a8d;\u6a8f\u0100bu\u252a\u27b3o\u0100;r\u0e1f\u27b9;\u601arok;\u4142\u8400<;cdhilqr\u082b\u27d2\u2639\u27dc\u27e0\u27e5\u27ea\u27f0\u0100ci\u27d7\u27d9;\u6aa6r;\u6a79re\xe5\u25f2mes;\u62c9arr;\u6976uest;\u6a7b\u0100Pi\u27f5\u27f9ar;\u6996\u0180;ef\u2800\u092d\u181b\u65c3r\u0100du\u2807\u280dshar;\u694ahar;\u6966\u0100en\u2817\u2821rtneqq;\uc000\u2268\ufe00\xc5\u281e\u0700Dacdefhilnopsu\u2840\u2845\u2882\u288e\u2893\u28a0\u28a5\u28a8\u28da\u28e2\u28e4\u0a83\u28f3\u2902Dot;\u623a\u0200clpr\u284e\u2852\u2863\u287dr\u803b\xaf\u40af\u0100et\u2857\u2859;\u6642\u0100;e\u285e\u285f\u6720se\xbb\u285f\u0100;s\u103b\u2868to\u0200;dlu\u103b\u2873\u2877\u287bow\xee\u048cef\xf4\u090f\xf0\u13d1ker;\u65ae\u0100oy\u2887\u288cmma;\u6a29;\u443cash;\u6014asuredangle\xbb\u1626r;\uc000\ud835\udd2ao;\u6127\u0180cdn\u28af\u28b4\u28c9ro\u803b\xb5\u40b5\u0200;acd\u1464\u28bd\u28c0\u28c4s\xf4\u16a7ir;\u6af0ot\u80bb\xb7\u01b5us\u0180;bd\u28d2\u1903\u28d3\u6212\u0100;u\u1d3c\u28d8;\u6a2a\u0163\u28de\u28e1p;\u6adb\xf2\u2212\xf0\u0a81\u0100dp\u28e9\u28eeels;\u62a7f;\uc000\ud835\udd5e\u0100ct\u28f8\u28fdr;\uc000\ud835\udcc2pos\xbb\u159d\u0180;lm\u2909\u290a\u290d\u43bctimap;\u62b8\u0c00GLRVabcdefghijlmoprstuvw\u2942\u2953\u297e\u2989\u2998\u29da\u29e9\u2a15\u2a1a\u2a58\u2a5d\u2a83\u2a95\u2aa4\u2aa8\u2b04\u2b07\u2b44\u2b7f\u2bae\u2c34\u2c67\u2c7c\u2ce9\u0100gt\u2947\u294b;\uc000\u22d9\u0338\u0100;v\u2950\u0bcf\uc000\u226b\u20d2\u0180elt\u295a\u2972\u2976ft\u0100ar\u2961\u2967rrow;\u61cdightarrow;\u61ce;\uc000\u22d8\u0338\u0100;v\u297b\u0c47\uc000\u226a\u20d2ightarrow;\u61cf\u0100Dd\u298e\u2993ash;\u62afash;\u62ae\u0280bcnpt\u29a3\u29a7\u29ac\u29b1\u29ccla\xbb\u02deute;\u4144g;\uc000\u2220\u20d2\u0280;Eiop\u0d84\u29bc\u29c0\u29c5\u29c8;\uc000\u2a70\u0338d;\uc000\u224b\u0338s;\u4149ro\xf8\u0d84ur\u0100;a\u29d3\u29d4\u666el\u0100;s\u29d3\u0b38\u01f3\u29df\0\u29e3p\u80bb\xa0\u0b37mp\u0100;e\u0bf9\u0c00\u0280aeouy\u29f4\u29fe\u2a03\u2a10\u2a13\u01f0\u29f9\0\u29fb;\u6a43on;\u4148dil;\u4146ng\u0100;d\u0d7e\u2a0aot;\uc000\u2a6d\u0338p;\u6a42;\u443dash;\u6013\u0380;Aadqsx\u0b92\u2a29\u2a2d\u2a3b\u2a41\u2a45\u2a50rr;\u61d7r\u0100hr\u2a33\u2a36k;\u6924\u0100;o\u13f2\u13f0ot;\uc000\u2250\u0338ui\xf6\u0b63\u0100ei\u2a4a\u2a4ear;\u6928\xed\u0b98ist\u0100;s\u0ba0\u0b9fr;\uc000\ud835\udd2b\u0200Eest\u0bc5\u2a66\u2a79\u2a7c\u0180;qs\u0bbc\u2a6d\u0be1\u0180;qs\u0bbc\u0bc5\u2a74lan\xf4\u0be2i\xed\u0bea\u0100;r\u0bb6\u2a81\xbb\u0bb7\u0180Aap\u2a8a\u2a8d\u2a91r\xf2\u2971rr;\u61aear;\u6af2\u0180;sv\u0f8d\u2a9c\u0f8c\u0100;d\u2aa1\u2aa2\u62fc;\u62facy;\u445a\u0380AEadest\u2ab7\u2aba\u2abe\u2ac2\u2ac5\u2af6\u2af9r\xf2\u2966;\uc000\u2266\u0338rr;\u619ar;\u6025\u0200;fqs\u0c3b\u2ace\u2ae3\u2aeft\u0100ar\u2ad4\u2ad9rro\xf7\u2ac1ightarro\xf7\u2a90\u0180;qs\u0c3b\u2aba\u2aealan\xf4\u0c55\u0100;s\u0c55\u2af4\xbb\u0c36i\xed\u0c5d\u0100;r\u0c35\u2afei\u0100;e\u0c1a\u0c25i\xe4\u0d90\u0100pt\u2b0c\u2b11f;\uc000\ud835\udd5f\u8180\xac;in\u2b19\u2b1a\u2b36\u40acn\u0200;Edv\u0b89\u2b24\u2b28\u2b2e;\uc000\u22f9\u0338ot;\uc000\u22f5\u0338\u01e1\u0b89\u2b33\u2b35;\u62f7;\u62f6i\u0100;v\u0cb8\u2b3c\u01e1\u0cb8\u2b41\u2b43;\u62fe;\u62fd\u0180aor\u2b4b\u2b63\u2b69r\u0200;ast\u0b7b\u2b55\u2b5a\u2b5flle\xec\u0b7bl;\uc000\u2afd\u20e5;\uc000\u2202\u0338lint;\u6a14\u0180;ce\u0c92\u2b70\u2b73u\xe5\u0ca5\u0100;c\u0c98\u2b78\u0100;e\u0c92\u2b7d\xf1\u0c98\u0200Aait\u2b88\u2b8b\u2b9d\u2ba7r\xf2\u2988rr\u0180;cw\u2b94\u2b95\u2b99\u619b;\uc000\u2933\u0338;\uc000\u219d\u0338ghtarrow\xbb\u2b95ri\u0100;e\u0ccb\u0cd6\u0380chimpqu\u2bbd\u2bcd\u2bd9\u2b04\u0b78\u2be4\u2bef\u0200;cer\u0d32\u2bc6\u0d37\u2bc9u\xe5\u0d45;\uc000\ud835\udcc3ort\u026d\u2b05\0\0\u2bd6ar\xe1\u2b56m\u0100;e\u0d6e\u2bdf\u0100;q\u0d74\u0d73su\u0100bp\u2beb\u2bed\xe5\u0cf8\xe5\u0d0b\u0180bcp\u2bf6\u2c11\u2c19\u0200;Ees\u2bff\u2c00\u0d22\u2c04\u6284;\uc000\u2ac5\u0338et\u0100;e\u0d1b\u2c0bq\u0100;q\u0d23\u2c00c\u0100;e\u0d32\u2c17\xf1\u0d38\u0200;Ees\u2c22\u2c23\u0d5f\u2c27\u6285;\uc000\u2ac6\u0338et\u0100;e\u0d58\u2c2eq\u0100;q\u0d60\u2c23\u0200gilr\u2c3d\u2c3f\u2c45\u2c47\xec\u0bd7lde\u803b\xf1\u40f1\xe7\u0c43iangle\u0100lr\u2c52\u2c5ceft\u0100;e\u0c1a\u2c5a\xf1\u0c26ight\u0100;e\u0ccb\u2c65\xf1\u0cd7\u0100;m\u2c6c\u2c6d\u43bd\u0180;es\u2c74\u2c75\u2c79\u4023ro;\u6116p;\u6007\u0480DHadgilrs\u2c8f\u2c94\u2c99\u2c9e\u2ca3\u2cb0\u2cb6\u2cd3\u2ce3ash;\u62adarr;\u6904p;\uc000\u224d\u20d2ash;\u62ac\u0100et\u2ca8\u2cac;\uc000\u2265\u20d2;\uc000>\u20d2nfin;\u69de\u0180Aet\u2cbd\u2cc1\u2cc5rr;\u6902;\uc000\u2264\u20d2\u0100;r\u2cca\u2ccd\uc000<\u20d2ie;\uc000\u22b4\u20d2\u0100At\u2cd8\u2cdcrr;\u6903rie;\uc000\u22b5\u20d2im;\uc000\u223c\u20d2\u0180Aan\u2cf0\u2cf4\u2d02rr;\u61d6r\u0100hr\u2cfa\u2cfdk;\u6923\u0100;o\u13e7\u13e5ear;\u6927\u1253\u1a95\0\0\0\0\0\0\0\0\0\0\0\0\0\u2d2d\0\u2d38\u2d48\u2d60\u2d65\u2d72\u2d84\u1b07\0\0\u2d8d\u2dab\0\u2dc8\u2dce\0\u2ddc\u2e19\u2e2b\u2e3e\u2e43\u0100cs\u2d31\u1a97ute\u803b\xf3\u40f3\u0100iy\u2d3c\u2d45r\u0100;c\u1a9e\u2d42\u803b\xf4\u40f4;\u443e\u0280abios\u1aa0\u2d52\u2d57\u01c8\u2d5alac;\u4151v;\u6a38old;\u69bclig;\u4153\u0100cr\u2d69\u2d6dir;\u69bf;\uc000\ud835\udd2c\u036f\u2d79\0\0\u2d7c\0\u2d82n;\u42dbave\u803b\xf2\u40f2;\u69c1\u0100bm\u2d88\u0df4ar;\u69b5\u0200acit\u2d95\u2d98\u2da5\u2da8r\xf2\u1a80\u0100ir\u2d9d\u2da0r;\u69beoss;\u69bbn\xe5\u0e52;\u69c0\u0180aei\u2db1\u2db5\u2db9cr;\u414dga;\u43c9\u0180cdn\u2dc0\u2dc5\u01cdron;\u43bf;\u69b6pf;\uc000\ud835\udd60\u0180ael\u2dd4\u2dd7\u01d2r;\u69b7rp;\u69b9\u0380;adiosv\u2dea\u2deb\u2dee\u2e08\u2e0d\u2e10\u2e16\u6228r\xf2\u1a86\u0200;efm\u2df7\u2df8\u2e02\u2e05\u6a5dr\u0100;o\u2dfe\u2dff\u6134f\xbb\u2dff\u803b\xaa\u40aa\u803b\xba\u40bagof;\u62b6r;\u6a56lope;\u6a57;\u6a5b\u0180clo\u2e1f\u2e21\u2e27\xf2\u2e01ash\u803b\xf8\u40f8l;\u6298i\u016c\u2e2f\u2e34de\u803b\xf5\u40f5es\u0100;a\u01db\u2e3as;\u6a36ml\u803b\xf6\u40f6bar;\u633d\u0ae1\u2e5e\0\u2e7d\0\u2e80\u2e9d\0\u2ea2\u2eb9\0\0\u2ecb\u0e9c\0\u2f13\0\0\u2f2b\u2fbc\0\u2fc8r\u0200;ast\u0403\u2e67\u2e72\u0e85\u8100\xb6;l\u2e6d\u2e6e\u40b6le\xec\u0403\u0269\u2e78\0\0\u2e7bm;\u6af3;\u6afdy;\u443fr\u0280cimpt\u2e8b\u2e8f\u2e93\u1865\u2e97nt;\u4025od;\u402eil;\u6030enk;\u6031r;\uc000\ud835\udd2d\u0180imo\u2ea8\u2eb0\u2eb4\u0100;v\u2ead\u2eae\u43c6;\u43d5ma\xf4\u0a76ne;\u660e\u0180;tv\u2ebf\u2ec0\u2ec8\u43c0chfork\xbb\u1ffd;\u43d6\u0100au\u2ecf\u2edfn\u0100ck\u2ed5\u2eddk\u0100;h\u21f4\u2edb;\u610e\xf6\u21f4s\u0480;abcdemst\u2ef3\u2ef4\u1908\u2ef9\u2efd\u2f04\u2f06\u2f0a\u2f0e\u402bcir;\u6a23ir;\u6a22\u0100ou\u1d40\u2f02;\u6a25;\u6a72n\u80bb\xb1\u0e9dim;\u6a26wo;\u6a27\u0180ipu\u2f19\u2f20\u2f25ntint;\u6a15f;\uc000\ud835\udd61nd\u803b\xa3\u40a3\u0500;Eaceinosu\u0ec8\u2f3f\u2f41\u2f44\u2f47\u2f81\u2f89\u2f92\u2f7e\u2fb6;\u6ab3p;\u6ab7u\xe5\u0ed9\u0100;c\u0ece\u2f4c\u0300;acens\u0ec8\u2f59\u2f5f\u2f66\u2f68\u2f7eppro\xf8\u2f43urlye\xf1\u0ed9\xf1\u0ece\u0180aes\u2f6f\u2f76\u2f7approx;\u6ab9qq;\u6ab5im;\u62e8i\xed\u0edfme\u0100;s\u2f88\u0eae\u6032\u0180Eas\u2f78\u2f90\u2f7a\xf0\u2f75\u0180dfp\u0eec\u2f99\u2faf\u0180als\u2fa0\u2fa5\u2faalar;\u632eine;\u6312urf;\u6313\u0100;t\u0efb\u2fb4\xef\u0efbrel;\u62b0\u0100ci\u2fc0\u2fc5r;\uc000\ud835\udcc5;\u43c8ncsp;\u6008\u0300fiopsu\u2fda\u22e2\u2fdf\u2fe5\u2feb\u2ff1r;\uc000\ud835\udd2epf;\uc000\ud835\udd62rime;\u6057cr;\uc000\ud835\udcc6\u0180aeo\u2ff8\u3009\u3013t\u0100ei\u2ffe\u3005rnion\xf3\u06b0nt;\u6a16st\u0100;e\u3010\u3011\u403f\xf1\u1f19\xf4\u0f14\u0a80ABHabcdefhilmnoprstux\u3040\u3051\u3055\u3059\u30e0\u310e\u312b\u3147\u3162\u3172\u318e\u3206\u3215\u3224\u3229\u3258\u326e\u3272\u3290\u32b0\u32b7\u0180art\u3047\u304a\u304cr\xf2\u10b3\xf2\u03ddail;\u691car\xf2\u1c65ar;\u6964\u0380cdenqrt\u3068\u3075\u3078\u307f\u308f\u3094\u30cc\u0100eu\u306d\u3071;\uc000\u223d\u0331te;\u4155i\xe3\u116emptyv;\u69b3g\u0200;del\u0fd1\u3089\u308b\u308d;\u6992;\u69a5\xe5\u0fd1uo\u803b\xbb\u40bbr\u0580;abcfhlpstw\u0fdc\u30ac\u30af\u30b7\u30b9\u30bc\u30be\u30c0\u30c3\u30c7\u30cap;\u6975\u0100;f\u0fe0\u30b4s;\u6920;\u6933s;\u691e\xeb\u225d\xf0\u272el;\u6945im;\u6974l;\u61a3;\u619d\u0100ai\u30d1\u30d5il;\u691ao\u0100;n\u30db\u30dc\u6236al\xf3\u0f1e\u0180abr\u30e7\u30ea\u30eer\xf2\u17e5rk;\u6773\u0100ak\u30f3\u30fdc\u0100ek\u30f9\u30fb;\u407d;\u405d\u0100es\u3102\u3104;\u698cl\u0100du\u310a\u310c;\u698e;\u6990\u0200aeuy\u3117\u311c\u3127\u3129ron;\u4159\u0100di\u3121\u3125il;\u4157\xec\u0ff2\xe2\u30fa;\u4440\u0200clqs\u3134\u3137\u313d\u3144a;\u6937dhar;\u6969uo\u0100;r\u020e\u020dh;\u61b3\u0180acg\u314e\u315f\u0f44l\u0200;ips\u0f78\u3158\u315b\u109cn\xe5\u10bbar\xf4\u0fa9t;\u65ad\u0180ilr\u3169\u1023\u316esht;\u697d;\uc000\ud835\udd2f\u0100ao\u3177\u3186r\u0100du\u317d\u317f\xbb\u047b\u0100;l\u1091\u3184;\u696c\u0100;v\u318b\u318c\u43c1;\u43f1\u0180gns\u3195\u31f9\u31fcht\u0300ahlrst\u31a4\u31b0\u31c2\u31d8\u31e4\u31eerrow\u0100;t\u0fdc\u31ada\xe9\u30c8arpoon\u0100du\u31bb\u31bfow\xee\u317ep\xbb\u1092eft\u0100ah\u31ca\u31d0rrow\xf3\u0feaarpoon\xf3\u0551ightarrows;\u61c9quigarro\xf7\u30cbhreetimes;\u62ccg;\u42daingdotse\xf1\u1f32\u0180ahm\u320d\u3210\u3213r\xf2\u0feaa\xf2\u0551;\u600foust\u0100;a\u321e\u321f\u63b1che\xbb\u321fmid;\u6aee\u0200abpt\u3232\u323d\u3240\u3252\u0100nr\u3237\u323ag;\u67edr;\u61fer\xeb\u1003\u0180afl\u3247\u324a\u324er;\u6986;\uc000\ud835\udd63us;\u6a2eimes;\u6a35\u0100ap\u325d\u3267r\u0100;g\u3263\u3264\u4029t;\u6994olint;\u6a12ar\xf2\u31e3\u0200achq\u327b\u3280\u10bc\u3285quo;\u603ar;\uc000\ud835\udcc7\u0100bu\u30fb\u328ao\u0100;r\u0214\u0213\u0180hir\u3297\u329b\u32a0re\xe5\u31f8mes;\u62cai\u0200;efl\u32aa\u1059\u1821\u32ab\u65b9tri;\u69celuhar;\u6968;\u611e\u0d61\u32d5\u32db\u32df\u332c\u3338\u3371\0\u337a\u33a4\0\0\u33ec\u33f0\0\u3428\u3448\u345a\u34ad\u34b1\u34ca\u34f1\0\u3616\0\0\u3633cute;\u415bqu\xef\u27ba\u0500;Eaceinpsy\u11ed\u32f3\u32f5\u32ff\u3302\u330b\u330f\u331f\u3326\u3329;\u6ab4\u01f0\u32fa\0\u32fc;\u6ab8on;\u4161u\xe5\u11fe\u0100;d\u11f3\u3307il;\u415frc;\u415d\u0180Eas\u3316\u3318\u331b;\u6ab6p;\u6abaim;\u62e9olint;\u6a13i\xed\u1204;\u4441ot\u0180;be\u3334\u1d47\u3335\u62c5;\u6a66\u0380Aacmstx\u3346\u334a\u3357\u335b\u335e\u3363\u336drr;\u61d8r\u0100hr\u3350\u3352\xeb\u2228\u0100;o\u0a36\u0a34t\u803b\xa7\u40a7i;\u403bwar;\u6929m\u0100in\u3369\xf0nu\xf3\xf1t;\u6736r\u0100;o\u3376\u2055\uc000\ud835\udd30\u0200acoy\u3382\u3386\u3391\u33a0rp;\u666f\u0100hy\u338b\u338fcy;\u4449;\u4448rt\u026d\u3399\0\0\u339ci\xe4\u1464ara\xec\u2e6f\u803b\xad\u40ad\u0100gm\u33a8\u33b4ma\u0180;fv\u33b1\u33b2\u33b2\u43c3;\u43c2\u0400;deglnpr\u12ab\u33c5\u33c9\u33ce\u33d6\u33de\u33e1\u33e6ot;\u6a6a\u0100;q\u12b1\u12b0\u0100;E\u33d3\u33d4\u6a9e;\u6aa0\u0100;E\u33db\u33dc\u6a9d;\u6a9fe;\u6246lus;\u6a24arr;\u6972ar\xf2\u113d\u0200aeit\u33f8\u3408\u340f\u3417\u0100ls\u33fd\u3404lsetm\xe9\u336ahp;\u6a33parsl;\u69e4\u0100dl\u1463\u3414e;\u6323\u0100;e\u341c\u341d\u6aaa\u0100;s\u3422\u3423\u6aac;\uc000\u2aac\ufe00\u0180flp\u342e\u3433\u3442tcy;\u444c\u0100;b\u3438\u3439\u402f\u0100;a\u343e\u343f\u69c4r;\u633ff;\uc000\ud835\udd64a\u0100dr\u344d\u0402es\u0100;u\u3454\u3455\u6660it\xbb\u3455\u0180csu\u3460\u3479\u349f\u0100au\u3465\u346fp\u0100;s\u1188\u346b;\uc000\u2293\ufe00p\u0100;s\u11b4\u3475;\uc000\u2294\ufe00u\u0100bp\u347f\u348f\u0180;es\u1197\u119c\u3486et\u0100;e\u1197\u348d\xf1\u119d\u0180;es\u11a8\u11ad\u3496et\u0100;e\u11a8\u349d\xf1\u11ae\u0180;af\u117b\u34a6\u05b0r\u0165\u34ab\u05b1\xbb\u117car\xf2\u1148\u0200cemt\u34b9\u34be\u34c2\u34c5r;\uc000\ud835\udcc8tm\xee\xf1i\xec\u3415ar\xe6\u11be\u0100ar\u34ce\u34d5r\u0100;f\u34d4\u17bf\u6606\u0100an\u34da\u34edight\u0100ep\u34e3\u34eapsilo\xee\u1ee0h\xe9\u2eafs\xbb\u2852\u0280bcmnp\u34fb\u355e\u1209\u358b\u358e\u0480;Edemnprs\u350e\u350f\u3511\u3515\u351e\u3523\u352c\u3531\u3536\u6282;\u6ac5ot;\u6abd\u0100;d\u11da\u351aot;\u6ac3ult;\u6ac1\u0100Ee\u3528\u352a;\u6acb;\u628alus;\u6abfarr;\u6979\u0180eiu\u353d\u3552\u3555t\u0180;en\u350e\u3545\u354bq\u0100;q\u11da\u350feq\u0100;q\u352b\u3528m;\u6ac7\u0100bp\u355a\u355c;\u6ad5;\u6ad3c\u0300;acens\u11ed\u356c\u3572\u3579\u357b\u3326ppro\xf8\u32faurlye\xf1\u11fe\xf1\u11f3\u0180aes\u3582\u3588\u331bppro\xf8\u331aq\xf1\u3317g;\u666a\u0680123;Edehlmnps\u35a9\u35ac\u35af\u121c\u35b2\u35b4\u35c0\u35c9\u35d5\u35da\u35df\u35e8\u35ed\u803b\xb9\u40b9\u803b\xb2\u40b2\u803b\xb3\u40b3;\u6ac6\u0100os\u35b9\u35bct;\u6abeub;\u6ad8\u0100;d\u1222\u35c5ot;\u6ac4s\u0100ou\u35cf\u35d2l;\u67c9b;\u6ad7arr;\u697bult;\u6ac2\u0100Ee\u35e4\u35e6;\u6acc;\u628blus;\u6ac0\u0180eiu\u35f4\u3609\u360ct\u0180;en\u121c\u35fc\u3602q\u0100;q\u1222\u35b2eq\u0100;q\u35e7\u35e4m;\u6ac8\u0100bp\u3611\u3613;\u6ad4;\u6ad6\u0180Aan\u361c\u3620\u362drr;\u61d9r\u0100hr\u3626\u3628\xeb\u222e\u0100;o\u0a2b\u0a29war;\u692alig\u803b\xdf\u40df\u0be1\u3651\u365d\u3660\u12ce\u3673\u3679\0\u367e\u36c2\0\0\0\0\0\u36db\u3703\0\u3709\u376c\0\0\0\u3787\u0272\u3656\0\0\u365bget;\u6316;\u43c4r\xeb\u0e5f\u0180aey\u3666\u366b\u3670ron;\u4165dil;\u4163;\u4442lrec;\u6315r;\uc000\ud835\udd31\u0200eiko\u3686\u369d\u36b5\u36bc\u01f2\u368b\0\u3691e\u01004f\u1284\u1281a\u0180;sv\u3698\u3699\u369b\u43b8ym;\u43d1\u0100cn\u36a2\u36b2k\u0100as\u36a8\u36aeppro\xf8\u12c1im\xbb\u12acs\xf0\u129e\u0100as\u36ba\u36ae\xf0\u12c1rn\u803b\xfe\u40fe\u01ec\u031f\u36c6\u22e7es\u8180\xd7;bd\u36cf\u36d0\u36d8\u40d7\u0100;a\u190f\u36d5r;\u6a31;\u6a30\u0180eps\u36e1\u36e3\u3700\xe1\u2a4d\u0200;bcf\u0486\u36ec\u36f0\u36f4ot;\u6336ir;\u6af1\u0100;o\u36f9\u36fc\uc000\ud835\udd65rk;\u6ada\xe1\u3362rime;\u6034\u0180aip\u370f\u3712\u3764d\xe5\u1248\u0380adempst\u3721\u374d\u3740\u3751\u3757\u375c\u375fngle\u0280;dlqr\u3730\u3731\u3736\u3740\u3742\u65b5own\xbb\u1dbbeft\u0100;e\u2800\u373e\xf1\u092e;\u625cight\u0100;e\u32aa\u374b\xf1\u105aot;\u65ecinus;\u6a3alus;\u6a39b;\u69cdime;\u6a3bezium;\u63e2\u0180cht\u3772\u377d\u3781\u0100ry\u3777\u377b;\uc000\ud835\udcc9;\u4446cy;\u445brok;\u4167\u0100io\u378b\u378ex\xf4\u1777head\u0100lr\u3797\u37a0eftarro\xf7\u084fightarrow\xbb\u0f5d\u0900AHabcdfghlmoprstuw\u37d0\u37d3\u37d7\u37e4\u37f0\u37fc\u380e\u381c\u3823\u3834\u3851\u385d\u386b\u38a9\u38cc\u38d2\u38ea\u38f6r\xf2\u03edar;\u6963\u0100cr\u37dc\u37e2ute\u803b\xfa\u40fa\xf2\u1150r\u01e3\u37ea\0\u37edy;\u445eve;\u416d\u0100iy\u37f5\u37farc\u803b\xfb\u40fb;\u4443\u0180abh\u3803\u3806\u380br\xf2\u13adlac;\u4171a\xf2\u13c3\u0100ir\u3813\u3818sht;\u697e;\uc000\ud835\udd32rave\u803b\xf9\u40f9\u0161\u3827\u3831r\u0100lr\u382c\u382e\xbb\u0957\xbb\u1083lk;\u6580\u0100ct\u3839\u384d\u026f\u383f\0\0\u384arn\u0100;e\u3845\u3846\u631cr\xbb\u3846op;\u630fri;\u65f8\u0100al\u3856\u385acr;\u416b\u80bb\xa8\u0349\u0100gp\u3862\u3866on;\u4173f;\uc000\ud835\udd66\u0300adhlsu\u114b\u3878\u387d\u1372\u3891\u38a0own\xe1\u13b3arpoon\u0100lr\u3888\u388cef\xf4\u382digh\xf4\u382fi\u0180;hl\u3899\u389a\u389c\u43c5\xbb\u13faon\xbb\u389aparrows;\u61c8\u0180cit\u38b0\u38c4\u38c8\u026f\u38b6\0\0\u38c1rn\u0100;e\u38bc\u38bd\u631dr\xbb\u38bdop;\u630eng;\u416fri;\u65f9cr;\uc000\ud835\udcca\u0180dir\u38d9\u38dd\u38e2ot;\u62f0lde;\u4169i\u0100;f\u3730\u38e8\xbb\u1813\u0100am\u38ef\u38f2r\xf2\u38a8l\u803b\xfc\u40fcangle;\u69a7\u0780ABDacdeflnoprsz\u391c\u391f\u3929\u392d\u39b5\u39b8\u39bd\u39df\u39e4\u39e8\u39f3\u39f9\u39fd\u3a01\u3a20r\xf2\u03f7ar\u0100;v\u3926\u3927\u6ae8;\u6ae9as\xe8\u03e1\u0100nr\u3932\u3937grt;\u699c\u0380eknprst\u34e3\u3946\u394b\u3952\u395d\u3964\u3996app\xe1\u2415othin\xe7\u1e96\u0180hir\u34eb\u2ec8\u3959op\xf4\u2fb5\u0100;h\u13b7\u3962\xef\u318d\u0100iu\u3969\u396dgm\xe1\u33b3\u0100bp\u3972\u3984setneq\u0100;q\u397d\u3980\uc000\u228a\ufe00;\uc000\u2acb\ufe00setneq\u0100;q\u398f\u3992\uc000\u228b\ufe00;\uc000\u2acc\ufe00\u0100hr\u399b\u399fet\xe1\u369ciangle\u0100lr\u39aa\u39afeft\xbb\u0925ight\xbb\u1051y;\u4432ash\xbb\u1036\u0180elr\u39c4\u39d2\u39d7\u0180;be\u2dea\u39cb\u39cfar;\u62bbq;\u625alip;\u62ee\u0100bt\u39dc\u1468a\xf2\u1469r;\uc000\ud835\udd33tr\xe9\u39aesu\u0100bp\u39ef\u39f1\xbb\u0d1c\xbb\u0d59pf;\uc000\ud835\udd67ro\xf0\u0efbtr\xe9\u39b4\u0100cu\u3a06\u3a0br;\uc000\ud835\udccb\u0100bp\u3a10\u3a18n\u0100Ee\u3980\u3a16\xbb\u397en\u0100Ee\u3992\u3a1e\xbb\u3990igzag;\u699a\u0380cefoprs\u3a36\u3a3b\u3a56\u3a5b\u3a54\u3a61\u3a6airc;\u4175\u0100di\u3a40\u3a51\u0100bg\u3a45\u3a49ar;\u6a5fe\u0100;q\u15fa\u3a4f;\u6259erp;\u6118r;\uc000\ud835\udd34pf;\uc000\ud835\udd68\u0100;e\u1479\u3a66at\xe8\u1479cr;\uc000\ud835\udccc\u0ae3\u178e\u3a87\0\u3a8b\0\u3a90\u3a9b\0\0\u3a9d\u3aa8\u3aab\u3aaf\0\0\u3ac3\u3ace\0\u3ad8\u17dc\u17dftr\xe9\u17d1r;\uc000\ud835\udd35\u0100Aa\u3a94\u3a97r\xf2\u03c3r\xf2\u09f6;\u43be\u0100Aa\u3aa1\u3aa4r\xf2\u03b8r\xf2\u09eba\xf0\u2713is;\u62fb\u0180dpt\u17a4\u3ab5\u3abe\u0100fl\u3aba\u17a9;\uc000\ud835\udd69im\xe5\u17b2\u0100Aa\u3ac7\u3acar\xf2\u03cer\xf2\u0a01\u0100cq\u3ad2\u17b8r;\uc000\ud835\udccd\u0100pt\u17d6\u3adcr\xe9\u17d4\u0400acefiosu\u3af0\u3afd\u3b08\u3b0c\u3b11\u3b15\u3b1b\u3b21c\u0100uy\u3af6\u3afbte\u803b\xfd\u40fd;\u444f\u0100iy\u3b02\u3b06rc;\u4177;\u444bn\u803b\xa5\u40a5r;\uc000\ud835\udd36cy;\u4457pf;\uc000\ud835\udd6acr;\uc000\ud835\udcce\u0100cm\u3b26\u3b29y;\u444el\u803b\xff\u40ff\u0500acdefhiosw\u3b42\u3b48\u3b54\u3b58\u3b64\u3b69\u3b6d\u3b74\u3b7a\u3b80cute;\u417a\u0100ay\u3b4d\u3b52ron;\u417e;\u4437ot;\u417c\u0100et\u3b5d\u3b61tr\xe6\u155fa;\u43b6r;\uc000\ud835\udd37cy;\u4436grarr;\u61ddpf;\uc000\ud835\udd6bcr;\uc000\ud835\udccf\u0100jn\u3b85\u3b87;\u600dj;\u600c'.split("").map((t=>t.charCodeAt(0)))),w=new Uint16Array("\u0200aglq\t\x15\x18\x1b\u026d\x0f\0\0\x12p;\u4026os;\u4027t;\u403et;\u403cuot;\u4022".split("").map((t=>t.charCodeAt(0))));const v=new Map([[0,65533],[128,8364],[130,8218],[131,402],[132,8222],[133,8230],[134,8224],[135,8225],[136,710],[137,8240],[138,352],[139,8249],[140,338],[142,381],[145,8216],[146,8217],[147,8220],[148,8221],[149,8226],[150,8211],[151,8212],[152,732],[153,8482],[154,353],[155,8250],[156,339],[158,382],[159,376]]),z=null!==(k=String.fromCodePoint)&&void 0!==k?k:function(t){let e="";return t>65535&&(t-=65536,e+=String.fromCharCode(t>>>10&1023|55296),t=56320|1023&t),e+=String.fromCharCode(t),e};var S;!function(t){t[t.NUM=35]="NUM",t[t.SEMI=59]="SEMI",t[t.EQUALS=61]="EQUALS",t[t.ZERO=48]="ZERO",t[t.NINE=57]="NINE",t[t.LOWER_A=97]="LOWER_A",t[t.LOWER_F=102]="LOWER_F",t[t.LOWER_X=120]="LOWER_X",t[t.LOWER_Z=122]="LOWER_Z",t[t.UPPER_A=65]="UPPER_A",t[t.UPPER_F=70]="UPPER_F",t[t.UPPER_Z=90]="UPPER_Z"}(S||(S={}));var q,B,L;function I(t){return t>=S.ZERO&&t<=S.NINE}function M(t){return t>=S.UPPER_A&&t<=S.UPPER_F||t>=S.LOWER_A&&t<=S.LOWER_F}function T(t){return t===S.EQUALS||function(t){return t>=S.UPPER_A&&t<=S.UPPER_Z||t>=S.LOWER_A&&t<=S.LOWER_Z||I(t)}(t)}!function(t){t[t.VALUE_LENGTH=49152]="VALUE_LENGTH",t[t.BRANCH_LENGTH=16256]="BRANCH_LENGTH",t[t.JUMP_TABLE=127]="JUMP_TABLE"}(q||(q={})),function(t){t[t.EntityStart=0]="EntityStart",t[t.NumericStart=1]="NumericStart",t[t.NumericDecimal=2]="NumericDecimal",t[t.NumericHex=3]="NumericHex",t[t.NamedEntity=4]="NamedEntity"}(B||(B={})),function(t){t[t.Legacy=0]="Legacy",t[t.Strict=1]="Strict",t[t.Attribute=2]="Attribute"}(L||(L={}));class R{constructor(t,e,r){this.decodeTree=t,this.emitCodePoint=e,this.errors=r,this.state=B.EntityStart,this.consumed=1,this.result=0,this.treeIndex=0,this.excess=1,this.decodeMode=L.Strict}startEntity(t){this.decodeMode=t,this.state=B.EntityStart,this.result=0,this.treeIndex=0,this.excess=1,this.consumed=1}write(t,e){switch(this.state){case B.EntityStart:return t.charCodeAt(e)===S.NUM?(this.state=B.NumericStart,this.consumed+=1,this.stateNumericStart(t,e+1)):(this.state=B.NamedEntity,this.stateNamedEntity(t,e));case B.NumericStart:return this.stateNumericStart(t,e);case B.NumericDecimal:return this.stateNumericDecimal(t,e);case B.NumericHex:return this.stateNumericHex(t,e);case B.NamedEntity:return this.stateNamedEntity(t,e)}}stateNumericStart(t,e){return e>=t.length?-1:(32|t.charCodeAt(e))===S.LOWER_X?(this.state=B.NumericHex,this.consumed+=1,this.stateNumericHex(t,e+1)):(this.state=B.NumericDecimal,this.stateNumericDecimal(t,e))}addToNumericResult(t,e,r,n){if(e!==r){const s=r-e;this.result=this.result*Math.pow(n,s)+parseInt(t.substr(e,s),n),this.consumed+=s}}stateNumericHex(t,e){const r=e;for(;e<t.length;){const n=t.charCodeAt(e);if(!I(n)&&!M(n))return this.addToNumericResult(t,r,e,16),this.emitNumericEntity(n,3);e+=1}return this.addToNumericResult(t,r,e,16),-1}stateNumericDecimal(t,e){const r=e;for(;e<t.length;){const n=t.charCodeAt(e);if(!I(n))return this.addToNumericResult(t,r,e,10),this.emitNumericEntity(n,2);e+=1}return this.addToNumericResult(t,r,e,10),-1}emitNumericEntity(t,e){var r;if(this.consumed<=e)return null===(r=this.errors)||void 0===r||r.absenceOfDigitsInNumericCharacterReference(this.consumed),0;if(t===S.SEMI)this.consumed+=1;else if(this.decodeMode===L.Strict)return 0;return this.emitCodePoint(function(t){var e;return t>=55296&&t<=57343||t>1114111?65533:null!==(e=v.get(t))&&void 0!==e?e:t}(this.result),this.consumed),this.errors&&(t!==S.SEMI&&this.errors.missingSemicolonAfterCharacterReference(),this.errors.validateNumericCharacterReference(this.result)),this.consumed}stateNamedEntity(t,e){const{decodeTree:r}=this;let n=r[this.treeIndex],s=(n&q.VALUE_LENGTH)>>14;for(;e<t.length;e++,this.excess++){const i=t.charCodeAt(e);if(this.treeIndex=P(r,n,this.treeIndex+Math.max(1,s),i),this.treeIndex<0)return 0===this.result||this.decodeMode===L.Attribute&&(0===s||T(i))?0:this.emitNotTerminatedNamedEntity();if(n=r[this.treeIndex],s=(n&q.VALUE_LENGTH)>>14,0!==s){if(i===S.SEMI)return this.emitNamedEntityData(this.treeIndex,s,this.consumed+this.excess);this.decodeMode!==L.Strict&&(this.result=this.treeIndex,this.consumed+=this.excess,this.excess=0)}}return-1}emitNotTerminatedNamedEntity(){var t;const{result:e,decodeTree:r}=this,n=(r[e]&q.VALUE_LENGTH)>>14;return this.emitNamedEntityData(e,n,this.consumed),null===(t=this.errors)||void 0===t||t.missingSemicolonAfterCharacterReference(),this.consumed}emitNamedEntityData(t,e,r){const{decodeTree:n}=this;return this.emitCodePoint(1===e?n[t]&~q.VALUE_LENGTH:n[t+1],r),3===e&&this.emitCodePoint(n[t+2],r),r}end(){var t;switch(this.state){case B.NamedEntity:return 0===this.result||this.decodeMode===L.Attribute&&this.result!==this.treeIndex?0:this.emitNotTerminatedNamedEntity();case B.NumericDecimal:return this.emitNumericEntity(0,2);case B.NumericHex:return this.emitNumericEntity(0,3);case B.NumericStart:return null===(t=this.errors)||void 0===t||t.absenceOfDigitsInNumericCharacterReference(this.consumed),0;case B.EntityStart:return 0}}}function N(t){let e="";const r=new R(t,(t=>e+=z(t)));return function(t,n){let s=0,i=0;for(;(i=t.indexOf("&",i))>=0;){e+=t.slice(s,i),r.startEntity(n);const o=r.write(t,i+1);if(o<0){s=i+r.end();break}s=i+o,i=0===o?s+1:s}const o=e+t.slice(s);return e="",o}}function P(t,e,r,n){const s=(e&q.BRANCH_LENGTH)>>7,i=e&q.JUMP_TABLE;if(0===s)return 0!==i&&n===i?r:-1;if(i){const e=n-i;return e<0||e>=s?-1:t[r+e]-1}let o=r,u=o+s-1;for(;o<=u;){const e=o+u>>>1,r=t[e];if(r<n)o=e+1;else{if(!(r>n))return t[e+s];u=e-1}}return-1}const O=N(x);function j(t,e=L.Legacy){return O(t,e)}function Z(t){return"[object String]"===function(t){return Object.prototype.toString.call(t)}(t)}N(w);const $=Object.prototype.hasOwnProperty;function U(t){return Array.prototype.slice.call(arguments,1).forEach((function(e){if(e){if("object"!=typeof e)throw new TypeError(e+"must be object");Object.keys(e).forEach((function(r){t[r]=e[r]}))}})),t}function H(t,e,r){return[].concat(t.slice(0,e),r,t.slice(e+1))}function V(t){return!(t>=55296&&t<=57343)&&(!(t>=64976&&t<=65007)&&(!!(65535&~t&&65534!=(65535&t))&&(!(t>=0&&t<=8)&&(11!==t&&(!(t>=14&&t<=31)&&(!(t>=127&&t<=159)&&!(t>1114111)))))))}function G(t){if(t>65535){const e=55296+((t-=65536)>>10),r=56320+(1023&t);return String.fromCharCode(e,r)}return String.fromCharCode(t)}const W=/\\([!"#$%&'()*+,\-./:;<=>?@[\\\]^_`{|}~])/g,J=new RegExp(W.source+"|"+/&([a-z#][a-z0-9]{1,31});/gi.source,"gi"),Q=/^#((?:x[a-f0-9]{1,8}|[0-9]{1,8}))$/i;function X(t){return t.indexOf("\\")<0&&t.indexOf("&")<0?t:t.replace(J,(function(t,e,r){return e||function(t,e){if(35===e.charCodeAt(0)&&Q.test(e)){const r="x"===e[1].toLowerCase()?parseInt(e.slice(2),16):parseInt(e.slice(1),10);return V(r)?G(r):t}const r=j(t);return r!==t?r:t}(t,r)}))}const Y=/[&<>"]/,K=/[&<>"]/g,tt={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"};function et(t){return tt[t]}function rt(t){return Y.test(t)?t.replace(K,et):t}const nt=/[.?*+^$[\]\\(){}|-]/g;function st(t){switch(t){case 9:case 32:return!0}return!1}function it(t){if(t>=8192&&t<=8202)return!0;switch(t){case 9:case 10:case 11:case 12:case 13:case 32:case 160:case 5760:case 8239:case 8287:case 12288:return!0}return!1}function ot(t){return E.test(t)||A.test(t)}function ut(t){switch(t){case 33:case 34:case 35:case 36:case 37:case 38:case 39:case 40:case 41:case 42:case 43:case 44:case 45:case 46:case 47:case 58:case 59:case 60:case 61:case 62:case 63:case 64:case 91:case 92:case 93:case 94:case 95:case 96:case 123:case 124:case 125:case 126:return!0;default:return!1}}function ct(t){return t=t.trim().replace(/\s+/g," "),"\u1e7e"==="\u1e9e".toLowerCase()&&(t=t.replace(/\u1e9e/g,"\xdf")),t.toLowerCase().toUpperCase()}const at={mdurl:D,ucmicro:F};var lt=Object.freeze({__proto__:null,arrayReplaceAt:H,assign:U,escapeHtml:rt,escapeRE:function(t){return t.replace(nt,"\\$&")},fromCodePoint:G,has:function(t,e){return $.call(t,e)},isMdAsciiPunct:ut,isPunctChar:ot,isSpace:st,isString:Z,isValidEntityCode:V,isWhiteSpace:it,lib:at,normalizeReference:ct,unescapeAll:X,unescapeMd:function(t){return t.indexOf("\\")<0?t:t.replace(W,"$1")}});var ht=Object.freeze({__proto__:null,parseLinkDestination:function(t,e,r){let n,s=e;const i={ok:!1,pos:0,str:""};if(60===t.charCodeAt(s)){for(s++;s<r;){if(n=t.charCodeAt(s),10===n)return i;if(60===n)return i;if(62===n)return i.pos=s+1,i.str=X(t.slice(e+1,s)),i.ok=!0,i;92===n&&s+1<r?s+=2:s++}return i}let o=0;for(;s<r&&(n=t.charCodeAt(s),32!==n)&&!(n<32||127===n);)if(92===n&&s+1<r){if(32===t.charCodeAt(s+1))break;s+=2}else{if(40===n&&(o++,o>32))return i;if(41===n){if(0===o)break;o--}s++}return e===s||0!==o||(i.str=X(t.slice(e,s)),i.pos=s,i.ok=!0),i},parseLinkLabel:function(t,e,r){let n,s,i,o;const u=t.posMax,c=t.pos;for(t.pos=e+1,n=1;t.pos<u;){if(i=t.src.charCodeAt(t.pos),93===i&&(n--,0===n)){s=!0;break}if(o=t.pos,t.md.inline.skipToken(t),91===i)if(o===t.pos-1)n++;else if(r)return t.pos=c,-1}let a=-1;return s&&(a=t.pos),t.pos=c,a},parseLinkTitle:function(t,e,r,n){let s,i=e;const o={ok:!1,can_continue:!1,pos:0,str:"",marker:0};if(n)o.str=n.str,o.marker=n.marker;else{if(i>=r)return o;let n=t.charCodeAt(i);if(34!==n&&39!==n&&40!==n)return o;e++,i++,40===n&&(n=41),o.marker=n}for(;i<r;){if(s=t.charCodeAt(i),s===o.marker)return o.pos=i+1,o.str+=X(t.slice(e,i)),o.ok=!0,o;if(40===s&&41===o.marker)return o;92===s&&i+1<r&&i++,i++}return o.can_continue=!0,o.str+=X(t.slice(e,i)),o}});const pt={};function ft(){this.rules=U({},pt)}function dt(){this.__rules__=[],this.__cache__=null}function _t(t,e,r){this.type=t,this.tag=e,this.attrs=null,this.map=null,this.nesting=r,this.level=0,this.children=null,this.content="",this.markup="",this.info="",this.meta=null,this.block=!1,this.hidden=!1}function mt(t,e,r){this.src=t,this.env=r,this.tokens=[],this.inlineMode=!1,this.md=e}pt.code_inline=function(t,e,r,n,s){const i=t[e];return"<code"+s.renderAttrs(i)+">"+rt(i.content)+"</code>"},pt.code_block=function(t,e,r,n,s){const i=t[e];return"<pre"+s.renderAttrs(i)+"><code>"+rt(t[e].content)+"</code></pre>\n"},pt.fence=function(t,e,r,n,s){const i=t[e],o=i.info?X(i.info).trim():"";let u,c="",a="";if(o){const t=o.split(/(\s+)/g);c=t[0],a=t.slice(2).join("")}if(u=r.highlight&&r.highlight(i.content,c,a)||rt(i.content),0===u.indexOf("<pre"))return u+"\n";if(o){const t=i.attrIndex("class"),e=i.attrs?i.attrs.slice():[];t<0?e.push(["class",r.langPrefix+c]):(e[t]=e[t].slice(),e[t][1]+=" "+r.langPrefix+c);const n={attrs:e};return`<pre><code${s.renderAttrs(n)}>${u}</code></pre>\n`}return`<pre><code${s.renderAttrs(i)}>${u}</code></pre>\n`},pt.image=function(t,e,r,n,s){const i=t[e];return i.attrs[i.attrIndex("alt")][1]=s.renderInlineAsText(i.children,r,n),s.renderToken(t,e,r)},pt.hardbreak=function(t,e,r){return r.xhtmlOut?"<br />\n":"<br>\n"},pt.softbreak=function(t,e,r){return r.breaks?r.xhtmlOut?"<br />\n":"<br>\n":"\n"},pt.text=function(t,e){return rt(t[e].content)},pt.html_block=function(t,e){return t[e].content},pt.html_inline=function(t,e){return t[e].content},ft.prototype.renderAttrs=function(t){let e,r,n;if(!t.attrs)return"";for(n="",e=0,r=t.attrs.length;e<r;e++)n+=" "+rt(t.attrs[e][0])+'="'+rt(t.attrs[e][1])+'"';return n},ft.prototype.renderToken=function(t,e,r){const n=t[e];let s="";if(n.hidden)return"";n.block&&-1!==n.nesting&&e&&t[e-1].hidden&&(s+="\n"),s+=(-1===n.nesting?"</":"<")+n.tag,s+=this.renderAttrs(n),0===n.nesting&&r.xhtmlOut&&(s+=" /");let i=!1;if(n.block&&(i=!0,1===n.nesting&&e+1<t.length)){const r=t[e+1];("inline"===r.type||r.hidden||-1===r.nesting&&r.tag===n.tag)&&(i=!1)}return s+=i?">\n":">",s},ft.prototype.renderInline=function(t,e,r){let n="";const s=this.rules;for(let i=0,o=t.length;i<o;i++){const o=t[i].type;void 0!==s[o]?n+=s[o](t,i,e,r,this):n+=this.renderToken(t,i,e)}return n},ft.prototype.renderInlineAsText=function(t,e,r){let n="";for(let s=0,i=t.length;s<i;s++)switch(t[s].type){case"text":case"html_inline":case"html_block":n+=t[s].content;break;case"image":n+=this.renderInlineAsText(t[s].children,e,r);break;case"softbreak":case"hardbreak":n+="\n"}return n},ft.prototype.render=function(t,e,r){let n="";const s=this.rules;for(let i=0,o=t.length;i<o;i++){const o=t[i].type;"inline"===o?n+=this.renderInline(t[i].children,e,r):void 0!==s[o]?n+=s[o](t,i,e,r,this):n+=this.renderToken(t,i,e,r)}return n},dt.prototype.__find__=function(t){for(let e=0;e<this.__rules__.length;e++)if(this.__rules__[e].name===t)return e;return-1},dt.prototype.__compile__=function(){const t=this,e=[""];t.__rules__.forEach((function(t){t.enabled&&t.alt.forEach((function(t){e.indexOf(t)<0&&e.push(t)}))})),t.__cache__={},e.forEach((function(e){t.__cache__[e]=[],t.__rules__.forEach((function(r){r.enabled&&(e&&r.alt.indexOf(e)<0||t.__cache__[e].push(r.fn))}))}))},dt.prototype.at=function(t,e,r){const n=this.__find__(t),s=r||{};if(-1===n)throw new Error("Parser rule not found: "+t);this.__rules__[n].fn=e,this.__rules__[n].alt=s.alt||[],this.__cache__=null},dt.prototype.before=function(t,e,r,n){const s=this.__find__(t),i=n||{};if(-1===s)throw new Error("Parser rule not found: "+t);this.__rules__.splice(s,0,{name:e,enabled:!0,fn:r,alt:i.alt||[]}),this.__cache__=null},dt.prototype.after=function(t,e,r,n){const s=this.__find__(t),i=n||{};if(-1===s)throw new Error("Parser rule not found: "+t);this.__rules__.splice(s+1,0,{name:e,enabled:!0,fn:r,alt:i.alt||[]}),this.__cache__=null},dt.prototype.push=function(t,e,r){const n=r||{};this.__rules__.push({name:t,enabled:!0,fn:e,alt:n.alt||[]}),this.__cache__=null},dt.prototype.enable=function(t,e){Array.isArray(t)||(t=[t]);const r=[];return t.forEach((function(t){const n=this.__find__(t);if(n<0){if(e)return;throw new Error("Rules manager: invalid rule name "+t)}this.__rules__[n].enabled=!0,r.push(t)}),this),this.__cache__=null,r},dt.prototype.enableOnly=function(t,e){Array.isArray(t)||(t=[t]),this.__rules__.forEach((function(t){t.enabled=!1})),this.enable(t,e)},dt.prototype.disable=function(t,e){Array.isArray(t)||(t=[t]);const r=[];return t.forEach((function(t){const n=this.__find__(t);if(n<0){if(e)return;throw new Error("Rules manager: invalid rule name "+t)}this.__rules__[n].enabled=!1,r.push(t)}),this),this.__cache__=null,r},dt.prototype.getRules=function(t){return null===this.__cache__&&this.__compile__(),this.__cache__[t]||[]},_t.prototype.attrIndex=function(t){if(!this.attrs)return-1;const e=this.attrs;for(let r=0,n=e.length;r<n;r++)if(e[r][0]===t)return r;return-1},_t.prototype.attrPush=function(t){this.attrs?this.attrs.push(t):this.attrs=[t]},_t.prototype.attrSet=function(t,e){const r=this.attrIndex(t),n=[t,e];r<0?this.attrPush(n):this.attrs[r]=n},_t.prototype.attrGet=function(t){const e=this.attrIndex(t);let r=null;return e>=0&&(r=this.attrs[e][1]),r},_t.prototype.attrJoin=function(t,e){const r=this.attrIndex(t);r<0?this.attrPush([t,e]):this.attrs[r][1]=this.attrs[r][1]+" "+e},mt.prototype.Token=_t;const gt=/\r\n?|\n/g,kt=/\0/g;function Dt(t){return/^<\/a\s*>/i.test(t)}const Ct=/\+-|\.\.|\?\?\?\?|!!!!|,,|--/,yt=/\((c|tm|r)\)/i,Et=/\((c|tm|r)\)/gi,At={c:"\xa9",r:"\xae",tm:"\u2122"};function bt(t,e){return At[e.toLowerCase()]}function Ft(t){let e=0;for(let r=t.length-1;r>=0;r--){const n=t[r];"text"!==n.type||e||(n.content=n.content.replace(Et,bt)),"link_open"===n.type&&"auto"===n.info&&e--,"link_close"===n.type&&"auto"===n.info&&e++}}function xt(t){let e=0;for(let r=t.length-1;r>=0;r--){const n=t[r];"text"!==n.type||e||Ct.test(n.content)&&(n.content=n.content.replace(/\+-/g,"\xb1").replace(/\.{2,}/g,"\u2026").replace(/([?!])\u2026/g,"$1..").replace(/([?!]){4,}/g,"$1$1$1").replace(/,{2,}/g,",").replace(/(^|[^-])---(?=[^-]|$)/gm,"$1\u2014").replace(/(^|\s)--(?=\s|$)/gm,"$1\u2013").replace(/(^|[^-\s])--(?=[^-\s]|$)/gm,"$1\u2013")),"link_open"===n.type&&"auto"===n.info&&e--,"link_close"===n.type&&"auto"===n.info&&e++}}const wt=/['"]/,vt=/['"]/g,zt="\u2019";function St(t,e,r){return t.slice(0,e)+r+t.slice(e+1)}function qt(t,e){let r;const n=[];for(let s=0;s<t.length;s++){const i=t[s],o=t[s].level;for(r=n.length-1;r>=0&&!(n[r].level<=o);r--);if(n.length=r+1,"text"!==i.type)continue;let u=i.content,c=0,a=u.length;t:for(;c<a;){vt.lastIndex=c;const l=vt.exec(u);if(!l)break;let h=!0,p=!0;c=l.index+1;const f="'"===l[0];let d=32;if(l.index-1>=0)d=u.charCodeAt(l.index-1);else for(r=s-1;r>=0&&("softbreak"!==t[r].type&&"hardbreak"!==t[r].type);r--)if(t[r].content){d=t[r].content.charCodeAt(t[r].content.length-1);break}let _=32;if(c<a)_=u.charCodeAt(c);else for(r=s+1;r<t.length&&("softbreak"!==t[r].type&&"hardbreak"!==t[r].type);r++)if(t[r].content){_=t[r].content.charCodeAt(0);break}const m=ut(d)||ot(String.fromCharCode(d)),g=ut(_)||ot(String.fromCharCode(_)),k=it(d),D=it(_);if(D?h=!1:g&&(k||m||(h=!1)),k?p=!1:m&&(D||g||(p=!1)),34===_&&'"'===l[0]&&d>=48&&d<=57&&(p=h=!1),h&&p&&(h=m,p=g),h||p){if(p)for(r=n.length-1;r>=0;r--){let h=n[r];if(n[r].level<o)break;if(h.single===f&&n[r].level===o){let o,p;h=n[r],f?(o=e.md.options.quotes[2],p=e.md.options.quotes[3]):(o=e.md.options.quotes[0],p=e.md.options.quotes[1]),i.content=St(i.content,l.index,p),t[h.token].content=St(t[h.token].content,h.pos,o),c+=p.length-1,h.token===s&&(c+=o.length-1),u=i.content,a=u.length,n.length=r;continue t}}h?n.push({token:s,pos:l.index,single:f,level:o}):p&&f&&(i.content=St(i.content,l.index,zt))}else f&&(i.content=St(i.content,l.index,zt))}}}const Bt=[["normalize",function(t){let e;e=t.src.replace(gt,"\n"),e=e.replace(kt,"\ufffd"),t.src=e}],["block",function(t){let e;t.inlineMode?(e=new t.Token("inline","",0),e.content=t.src,e.map=[0,1],e.children=[],t.tokens.push(e)):t.md.block.parse(t.src,t.md,t.env,t.tokens)}],["inline",function(t){const e=t.tokens;for(let r=0,n=e.length;r<n;r++){const n=e[r];"inline"===n.type&&t.md.inline.parse(n.content,t.md,t.env,n.children)}}],["linkify",function(t){const e=t.tokens;var r;if(t.md.options.linkify)for(let n=0,s=e.length;n<s;n++){if("inline"!==e[n].type||!t.md.linkify.pretest(e[n].content))continue;let s=e[n].children,i=0;for(let o=s.length-1;o>=0;o--){const u=s[o];if("link_close"!==u.type){if("html_inline"===u.type&&(r=u.content,/^<a[>\s]/i.test(r)&&i>0&&i--,Dt(u.content)&&i++),!(i>0)&&"text"===u.type&&t.md.linkify.test(u.content)){const r=u.content;let i=t.md.linkify.match(r);const c=[];let a=u.level,l=0;i.length>0&&0===i[0].index&&o>0&&"text_special"===s[o-1].type&&(i=i.slice(1));for(let e=0;e<i.length;e++){const n=i[e].url,s=t.md.normalizeLink(n);if(!t.md.validateLink(s))continue;let o=i[e].text;o=i[e].schema?"mailto:"!==i[e].schema||/^mailto:/i.test(o)?t.md.normalizeLinkText(o):t.md.normalizeLinkText("mailto:"+o).replace(/^mailto:/,""):t.md.normalizeLinkText("http://"+o).replace(/^http:\/\//,"");const u=i[e].index;if(u>l){const e=new t.Token("text","",0);e.content=r.slice(l,u),e.level=a,c.push(e)}const h=new t.Token("link_open","a",1);h.attrs=[["href",s]],h.level=a++,h.markup="linkify",h.info="auto",c.push(h);const p=new t.Token("text","",0);p.content=o,p.level=a,c.push(p);const f=new t.Token("link_close","a",-1);f.level=--a,f.markup="linkify",f.info="auto",c.push(f),l=i[e].lastIndex}if(l<r.length){const e=new t.Token("text","",0);e.content=r.slice(l),e.level=a,c.push(e)}e[n].children=s=H(s,o,c)}}else for(o--;s[o].level!==u.level&&"link_open"!==s[o].type;)o--}}}],["replacements",function(t){let e;if(t.md.options.typographer)for(e=t.tokens.length-1;e>=0;e--)"inline"===t.tokens[e].type&&(yt.test(t.tokens[e].content)&&Ft(t.tokens[e].children),Ct.test(t.tokens[e].content)&&xt(t.tokens[e].children))}],["smartquotes",function(t){if(t.md.options.typographer)for(let e=t.tokens.length-1;e>=0;e--)"inline"===t.tokens[e].type&&wt.test(t.tokens[e].content)&&qt(t.tokens[e].children,t)}],["text_join",function(t){let e,r;const n=t.tokens,s=n.length;for(let t=0;t<s;t++){if("inline"!==n[t].type)continue;const s=n[t].children,i=s.length;for(e=0;e<i;e++)"text_special"===s[e].type&&(s[e].type="text");for(e=r=0;e<i;e++)"text"===s[e].type&&e+1<i&&"text"===s[e+1].type?s[e+1].content=s[e].content+s[e+1].content:(e!==r&&(s[r]=s[e]),r++);e!==r&&(s.length=r)}}]];function Lt(){this.ruler=new dt;for(let t=0;t<Bt.length;t++)this.ruler.push(Bt[t][0],Bt[t][1])}function It(t,e,r,n){this.src=t,this.md=e,this.env=r,this.tokens=n,this.bMarks=[],this.eMarks=[],this.tShift=[],this.sCount=[],this.bsCount=[],this.blkIndent=0,this.line=0,this.lineMax=0,this.tight=!1,this.ddIndent=-1,this.listIndent=-1,this.parentType="root",this.level=0;const s=this.src;for(let t=0,e=0,r=0,n=0,i=s.length,o=!1;e<i;e++){const u=s.charCodeAt(e);if(!o){if(st(u)){r++,9===u?n+=4-n%4:n++;continue}o=!0}10!==u&&e!==i-1||(10!==u&&e++,this.bMarks.push(t),this.eMarks.push(e),this.tShift.push(r),this.sCount.push(n),this.bsCount.push(0),o=!1,r=0,n=0,t=e+1)}this.bMarks.push(s.length),this.eMarks.push(s.length),this.tShift.push(0),this.sCount.push(0),this.bsCount.push(0),this.lineMax=this.bMarks.length-1}Lt.prototype.process=function(t){const e=this.ruler.getRules("");for(let r=0,n=e.length;r<n;r++)e[r](t)},Lt.prototype.State=mt,It.prototype.push=function(t,e,r){const n=new _t(t,e,r);return n.block=!0,r<0&&this.level--,n.level=this.level,r>0&&this.level++,this.tokens.push(n),n},It.prototype.isEmpty=function(t){return this.bMarks[t]+this.tShift[t]>=this.eMarks[t]},It.prototype.skipEmptyLines=function(t){for(let e=this.lineMax;t<e&&!(this.bMarks[t]+this.tShift[t]<this.eMarks[t]);t++);return t},It.prototype.skipSpaces=function(t){for(let e=this.src.length;t<e;t++){if(!st(this.src.charCodeAt(t)))break}return t},It.prototype.skipSpacesBack=function(t,e){if(t<=e)return t;for(;t>e;)if(!st(this.src.charCodeAt(--t)))return t+1;return t},It.prototype.skipChars=function(t,e){for(let r=this.src.length;t<r&&this.src.charCodeAt(t)===e;t++);return t},It.prototype.skipCharsBack=function(t,e,r){if(t<=r)return t;for(;t>r;)if(e!==this.src.charCodeAt(--t))return t+1;return t},It.prototype.getLines=function(t,e,r,n){if(t>=e)return"";const s=new Array(e-t);for(let i=0,o=t;o<e;o++,i++){let t=0;const u=this.bMarks[o];let c,a=u;for(c=o+1<e||n?this.eMarks[o]+1:this.eMarks[o];a<c&&t<r;){const e=this.src.charCodeAt(a);if(st(e))9===e?t+=4-(t+this.bsCount[o])%4:t++;else{if(!(a-u<this.tShift[o]))break;t++}a++}s[i]=t>r?new Array(t-r+1).join(" ")+this.src.slice(a,c):this.src.slice(a,c)}return s.join("")},It.prototype.Token=_t;function Mt(t,e){const r=t.bMarks[e]+t.tShift[e],n=t.eMarks[e];return t.src.slice(r,n)}function Tt(t){const e=[],r=t.length;let n=0,s=t.charCodeAt(n),i=!1,o=0,u="";for(;n<r;)124===s&&(i?(u+=t.substring(o,n-1),o=n):(e.push(u+t.substring(o,n)),u="",o=n+1)),i=92===s,n++,s=t.charCodeAt(n);return e.push(u+t.substring(o)),e}function Rt(t,e){const r=t.eMarks[e];let n=t.bMarks[e]+t.tShift[e];const s=t.src.charCodeAt(n++);if(42!==s&&45!==s&&43!==s)return-1;if(n<r){if(!st(t.src.charCodeAt(n)))return-1}return n}function Nt(t,e){const r=t.bMarks[e]+t.tShift[e],n=t.eMarks[e];let s=r;if(s+1>=n)return-1;let i=t.src.charCodeAt(s++);if(i<48||i>57)return-1;for(;;){if(s>=n)return-1;if(i=t.src.charCodeAt(s++),!(i>=48&&i<=57)){if(41===i||46===i)break;return-1}if(s-r>=10)return-1}return s<n&&(i=t.src.charCodeAt(s),!st(i))?-1:s}const Pt="<[A-Za-z][A-Za-z0-9\\-]*(?:\\s+[a-zA-Z_:][a-zA-Z0-9:._-]*(?:\\s*=\\s*(?:[^\"'=<>`\\x00-\\x20]+|'[^']*'|\"[^\"]*\"))?)*\\s*\\/?>",Ot="<\\/[A-Za-z][A-Za-z0-9\\-]*\\s*>",jt=new RegExp("^(?:"+Pt+"|"+Ot+"|\x3c!---?>|\x3c!--(?:[^-]|-[^-]|--[^>])*--\x3e|<[?][\\s\\S]*?[?]>|<![A-Za-z][^>]*>|<!\\[CDATA\\[[\\s\\S]*?\\]\\]>)"),Zt=new RegExp("^(?:"+Pt+"|"+Ot+")"),$t=[[/^<(script|pre|style|textarea)(?=(\s|>|$))/i,/<\/(script|pre|style|textarea)>/i,!0],[/^<!--/,/-->/,!0],[/^<\?/,/\?>/,!0],[/^<![A-Z]/,/>/,!0],[/^<!\[CDATA\[/,/\]\]>/,!0],[new RegExp("^</?("+["address","article","aside","base","basefont","blockquote","body","caption","center","col","colgroup","dd","details","dialog","dir","div","dl","dt","fieldset","figcaption","figure","footer","form","frame","frameset","h1","h2","h3","h4","h5","h6","head","header","hr","html","iframe","legend","li","link","main","menu","menuitem","nav","noframes","ol","optgroup","option","p","param","search","section","summary","table","tbody","td","tfoot","th","thead","title","tr","track","ul"].join("|")+")(?=(\\s|/?>|$))","i"),/^$/,!0],[new RegExp(Zt.source+"\\s*$"),/^$/,!1]];const Ut=[["table",function(t,e,r,n){if(e+2>r)return!1;let s=e+1;if(t.sCount[s]<t.blkIndent)return!1;if(t.sCount[s]-t.blkIndent>=4)return!1;let i=t.bMarks[s]+t.tShift[s];if(i>=t.eMarks[s])return!1;const o=t.src.charCodeAt(i++);if(124!==o&&45!==o&&58!==o)return!1;if(i>=t.eMarks[s])return!1;const u=t.src.charCodeAt(i++);if(124!==u&&45!==u&&58!==u&&!st(u))return!1;if(45===o&&st(u))return!1;for(;i<t.eMarks[s];){const e=t.src.charCodeAt(i);if(124!==e&&45!==e&&58!==e&&!st(e))return!1;i++}let c=Mt(t,e+1),a=c.split("|");const l=[];for(let t=0;t<a.length;t++){const e=a[t].trim();if(!e){if(0===t||t===a.length-1)continue;return!1}if(!/^:?-+:?$/.test(e))return!1;58===e.charCodeAt(e.length-1)?l.push(58===e.charCodeAt(0)?"center":"right"):58===e.charCodeAt(0)?l.push("left"):l.push("")}if(c=Mt(t,e).trim(),-1===c.indexOf("|"))return!1;if(t.sCount[e]-t.blkIndent>=4)return!1;a=Tt(c),a.length&&""===a[0]&&a.shift(),a.length&&""===a[a.length-1]&&a.pop();const h=a.length;if(0===h||h!==l.length)return!1;if(n)return!0;const p=t.parentType;t.parentType="table";const f=t.md.block.ruler.getRules("blockquote"),d=[e,0];t.push("table_open","table",1).map=d,t.push("thead_open","thead",1).map=[e,e+1],t.push("tr_open","tr",1).map=[e,e+1];for(let e=0;e<a.length;e++){const r=t.push("th_open","th",1);l[e]&&(r.attrs=[["style","text-align:"+l[e]]]);const n=t.push("inline","",0);n.content=a[e].trim(),n.children=[],t.push("th_close","th",-1)}let _;t.push("tr_close","tr",-1),t.push("thead_close","thead",-1);let m=0;for(s=e+2;s<r&&!(t.sCount[s]<t.blkIndent);s++){let n=!1;for(let e=0,i=f.length;e<i;e++)if(f[e](t,s,r,!0)){n=!0;break}if(n)break;if(c=Mt(t,s).trim(),!c)break;if(t.sCount[s]-t.blkIndent>=4)break;if(a=Tt(c),a.length&&""===a[0]&&a.shift(),a.length&&""===a[a.length-1]&&a.pop(),m+=h-a.length,m>65536)break;if(s===e+2){t.push("tbody_open","tbody",1).map=_=[e+2,0]}t.push("tr_open","tr",1).map=[s,s+1];for(let e=0;e<h;e++){const r=t.push("td_open","td",1);l[e]&&(r.attrs=[["style","text-align:"+l[e]]]);const n=t.push("inline","",0);n.content=a[e]?a[e].trim():"",n.children=[],t.push("td_close","td",-1)}t.push("tr_close","tr",-1)}return _&&(t.push("tbody_close","tbody",-1),_[1]=s),t.push("table_close","table",-1),d[1]=s,t.parentType=p,t.line=s,!0},["paragraph","reference"]],["code",function(t,e,r){if(t.sCount[e]-t.blkIndent<4)return!1;let n=e+1,s=n;for(;n<r;)if(t.isEmpty(n))n++;else{if(!(t.sCount[n]-t.blkIndent>=4))break;n++,s=n}t.line=s;const i=t.push("code_block","code",0);return i.content=t.getLines(e,s,4+t.blkIndent,!1)+"\n",i.map=[e,t.line],!0}],["fence",function(t,e,r,n){let s=t.bMarks[e]+t.tShift[e],i=t.eMarks[e];if(t.sCount[e]-t.blkIndent>=4)return!1;if(s+3>i)return!1;const o=t.src.charCodeAt(s);if(126!==o&&96!==o)return!1;let u=s;s=t.skipChars(s,o);let c=s-u;if(c<3)return!1;const a=t.src.slice(u,s),l=t.src.slice(s,i);if(96===o&&l.indexOf(String.fromCharCode(o))>=0)return!1;if(n)return!0;let h=e,p=!1;for(;(h++,!(h>=r))&&(s=u=t.bMarks[h]+t.tShift[h],i=t.eMarks[h],!(s<i&&t.sCount[h]<t.blkIndent));)if(t.src.charCodeAt(s)===o&&!(t.sCount[h]-t.blkIndent>=4||(s=t.skipChars(s,o),s-u<c||(s=t.skipSpaces(s),s<i)))){p=!0;break}c=t.sCount[e],t.line=h+(p?1:0);const f=t.push("fence","code",0);return f.info=l,f.content=t.getLines(e+1,h,c,!0),f.markup=a,f.map=[e,t.line],!0},["paragraph","reference","blockquote","list"]],["blockquote",function(t,e,r,n){let s=t.bMarks[e]+t.tShift[e],i=t.eMarks[e];const o=t.lineMax;if(t.sCount[e]-t.blkIndent>=4)return!1;if(62!==t.src.charCodeAt(s))return!1;if(n)return!0;const u=[],c=[],a=[],l=[],h=t.md.block.ruler.getRules("blockquote"),p=t.parentType;t.parentType="blockquote";let f,d=!1;for(f=e;f<r;f++){const e=t.sCount[f]<t.blkIndent;if(s=t.bMarks[f]+t.tShift[f],i=t.eMarks[f],s>=i)break;if(62===t.src.charCodeAt(s++)&&!e){let e,r,n=t.sCount[f]+1;32===t.src.charCodeAt(s)?(s++,n++,r=!1,e=!0):9===t.src.charCodeAt(s)?(e=!0,(t.bsCount[f]+n)%4==3?(s++,n++,r=!1):r=!0):e=!1;let o=n;for(u.push(t.bMarks[f]),t.bMarks[f]=s;s<i;){const e=t.src.charCodeAt(s);if(!st(e))break;9===e?o+=4-(o+t.bsCount[f]+(r?1:0))%4:o++,s++}d=s>=i,c.push(t.bsCount[f]),t.bsCount[f]=t.sCount[f]+1+(e?1:0),a.push(t.sCount[f]),t.sCount[f]=o-n,l.push(t.tShift[f]),t.tShift[f]=s-t.bMarks[f];continue}if(d)break;let n=!1;for(let e=0,s=h.length;e<s;e++)if(h[e](t,f,r,!0)){n=!0;break}if(n){t.lineMax=f,0!==t.blkIndent&&(u.push(t.bMarks[f]),c.push(t.bsCount[f]),l.push(t.tShift[f]),a.push(t.sCount[f]),t.sCount[f]-=t.blkIndent);break}u.push(t.bMarks[f]),c.push(t.bsCount[f]),l.push(t.tShift[f]),a.push(t.sCount[f]),t.sCount[f]=-1}const _=t.blkIndent;t.blkIndent=0;const m=t.push("blockquote_open","blockquote",1);m.markup=">";const g=[e,0];m.map=g,t.md.block.tokenize(t,e,f),t.push("blockquote_close","blockquote",-1).markup=">",t.lineMax=o,t.parentType=p,g[1]=t.line;for(let r=0;r<l.length;r++)t.bMarks[r+e]=u[r],t.tShift[r+e]=l[r],t.sCount[r+e]=a[r],t.bsCount[r+e]=c[r];return t.blkIndent=_,!0},["paragraph","reference","blockquote","list"]],["hr",function(t,e,r,n){const s=t.eMarks[e];if(t.sCount[e]-t.blkIndent>=4)return!1;let i=t.bMarks[e]+t.tShift[e];const o=t.src.charCodeAt(i++);if(42!==o&&45!==o&&95!==o)return!1;let u=1;for(;i<s;){const e=t.src.charCodeAt(i++);if(e!==o&&!st(e))return!1;e===o&&u++}if(u<3)return!1;if(n)return!0;t.line=e+1;const c=t.push("hr","hr",0);return c.map=[e,t.line],c.markup=Array(u+1).join(String.fromCharCode(o)),!0},["paragraph","reference","blockquote","list"]],["list",function(t,e,r,n){let s,i,o,u,c=e,a=!0;if(t.sCount[c]-t.blkIndent>=4)return!1;if(t.listIndent>=0&&t.sCount[c]-t.listIndent>=4&&t.sCount[c]<t.blkIndent)return!1;let l,h,p,f=!1;if(n&&"paragraph"===t.parentType&&t.sCount[c]>=t.blkIndent&&(f=!0),(p=Nt(t,c))>=0){if(l=!0,o=t.bMarks[c]+t.tShift[c],h=Number(t.src.slice(o,p-1)),f&&1!==h)return!1}else{if(!((p=Rt(t,c))>=0))return!1;l=!1}if(f&&t.skipSpaces(p)>=t.eMarks[c])return!1;if(n)return!0;const d=t.src.charCodeAt(p-1),_=t.tokens.length;l?(u=t.push("ordered_list_open","ol",1),1!==h&&(u.attrs=[["start",h]])):u=t.push("bullet_list_open","ul",1);const m=[c,0];u.map=m,u.markup=String.fromCharCode(d);let g=!1;const k=t.md.block.ruler.getRules("list"),D=t.parentType;for(t.parentType="list";c<r;){i=p,s=t.eMarks[c];const e=t.sCount[c]+p-(t.bMarks[c]+t.tShift[c]);let n=e;for(;i<s;){const e=t.src.charCodeAt(i);if(9===e)n+=4-(n+t.bsCount[c])%4;else{if(32!==e)break;n++}i++}const h=i;let f;f=h>=s?1:n-e,f>4&&(f=1);const _=e+f;u=t.push("list_item_open","li",1),u.markup=String.fromCharCode(d);const m=[c,0];u.map=m,l&&(u.info=t.src.slice(o,p-1));const D=t.tight,C=t.tShift[c],y=t.sCount[c],E=t.listIndent;if(t.listIndent=t.blkIndent,t.blkIndent=_,t.tight=!0,t.tShift[c]=h-t.bMarks[c],t.sCount[c]=n,h>=s&&t.isEmpty(c+1)?t.line=Math.min(t.line+2,r):t.md.block.tokenize(t,c,r,!0),t.tight&&!g||(a=!1),g=t.line-c>1&&t.isEmpty(t.line-1),t.blkIndent=t.listIndent,t.listIndent=E,t.tShift[c]=C,t.sCount[c]=y,t.tight=D,u=t.push("list_item_close","li",-1),u.markup=String.fromCharCode(d),c=t.line,m[1]=c,c>=r)break;if(t.sCount[c]<t.blkIndent)break;if(t.sCount[c]-t.blkIndent>=4)break;let A=!1;for(let e=0,n=k.length;e<n;e++)if(k[e](t,c,r,!0)){A=!0;break}if(A)break;if(l){if(p=Nt(t,c),p<0)break;o=t.bMarks[c]+t.tShift[c]}else if(p=Rt(t,c),p<0)break;if(d!==t.src.charCodeAt(p-1))break}return u=l?t.push("ordered_list_close","ol",-1):t.push("bullet_list_close","ul",-1),u.markup=String.fromCharCode(d),m[1]=c,t.line=c,t.parentType=D,a&&function(t,e){const r=t.level+2;for(let n=e+2,s=t.tokens.length-2;n<s;n++)t.tokens[n].level===r&&"paragraph_open"===t.tokens[n].type&&(t.tokens[n+2].hidden=!0,t.tokens[n].hidden=!0,n+=2)}(t,_),!0},["paragraph","reference","blockquote"]],["reference",function(t,e,r,n){let s=t.bMarks[e]+t.tShift[e],i=t.eMarks[e],o=e+1;if(t.sCount[e]-t.blkIndent>=4)return!1;if(91!==t.src.charCodeAt(s))return!1;function u(e){const r=t.lineMax;if(e>=r||t.isEmpty(e))return null;let n=!1;if(t.sCount[e]-t.blkIndent>3&&(n=!0),t.sCount[e]<0&&(n=!0),!n){const n=t.md.block.ruler.getRules("reference"),s=t.parentType;t.parentType="reference";let i=!1;for(let s=0,o=n.length;s<o;s++)if(n[s](t,e,r,!0)){i=!0;break}if(t.parentType=s,i)return null}const s=t.bMarks[e]+t.tShift[e],i=t.eMarks[e];return t.src.slice(s,i+1)}let c=t.src.slice(s,i+1);i=c.length;let a=-1;for(s=1;s<i;s++){const t=c.charCodeAt(s);if(91===t)return!1;if(93===t){a=s;break}if(10===t){const t=u(o);null!==t&&(c+=t,i=c.length,o++)}else if(92===t&&(s++,s<i&&10===c.charCodeAt(s))){const t=u(o);null!==t&&(c+=t,i=c.length,o++)}}if(a<0||58!==c.charCodeAt(a+1))return!1;for(s=a+2;s<i;s++){const t=c.charCodeAt(s);if(10===t){const t=u(o);null!==t&&(c+=t,i=c.length,o++)}else if(!st(t))break}const l=t.md.helpers.parseLinkDestination(c,s,i);if(!l.ok)return!1;const h=t.md.normalizeLink(l.str);if(!t.md.validateLink(h))return!1;s=l.pos;const p=s,f=o,d=s;for(;s<i;s++){const t=c.charCodeAt(s);if(10===t){const t=u(o);null!==t&&(c+=t,i=c.length,o++)}else if(!st(t))break}let _,m=t.md.helpers.parseLinkTitle(c,s,i);for(;m.can_continue;){const e=u(o);if(null===e)break;c+=e,s=i,i=c.length,o++,m=t.md.helpers.parseLinkTitle(c,s,i,m)}for(s<i&&d!==s&&m.ok?(_=m.str,s=m.pos):(_="",s=p,o=f);s<i;){if(!st(c.charCodeAt(s)))break;s++}if(s<i&&10!==c.charCodeAt(s)&&_)for(_="",s=p,o=f;s<i;){if(!st(c.charCodeAt(s)))break;s++}if(s<i&&10!==c.charCodeAt(s))return!1;const g=ct(c.slice(1,a));return!!g&&(n||(void 0===t.env.references&&(t.env.references={}),void 0===t.env.references[g]&&(t.env.references[g]={title:_,href:h}),t.line=o),!0)}],["html_block",function(t,e,r,n){let s=t.bMarks[e]+t.tShift[e],i=t.eMarks[e];if(t.sCount[e]-t.blkIndent>=4)return!1;if(!t.md.options.html)return!1;if(60!==t.src.charCodeAt(s))return!1;let o=t.src.slice(s,i),u=0;for(;u<$t.length&&!$t[u][0].test(o);u++);if(u===$t.length)return!1;if(n)return $t[u][2];let c=e+1;if(!$t[u][1].test(o))for(;c<r&&!(t.sCount[c]<t.blkIndent);c++)if(s=t.bMarks[c]+t.tShift[c],i=t.eMarks[c],o=t.src.slice(s,i),$t[u][1].test(o)){0!==o.length&&c++;break}t.line=c;const a=t.push("html_block","",0);return a.map=[e,c],a.content=t.getLines(e,c,t.blkIndent,!0),!0},["paragraph","reference","blockquote"]],["heading",function(t,e,r,n){let s=t.bMarks[e]+t.tShift[e],i=t.eMarks[e];if(t.sCount[e]-t.blkIndent>=4)return!1;let o=t.src.charCodeAt(s);if(35!==o||s>=i)return!1;let u=1;for(o=t.src.charCodeAt(++s);35===o&&s<i&&u<=6;)u++,o=t.src.charCodeAt(++s);if(u>6||s<i&&!st(o))return!1;if(n)return!0;i=t.skipSpacesBack(i,s);const c=t.skipCharsBack(i,35,s);c>s&&st(t.src.charCodeAt(c-1))&&(i=c),t.line=e+1;const a=t.push("heading_open","h"+String(u),1);a.markup="########".slice(0,u),a.map=[e,t.line];const l=t.push("inline","",0);return l.content=t.src.slice(s,i).trim(),l.map=[e,t.line],l.children=[],t.push("heading_close","h"+String(u),-1).markup="########".slice(0,u),!0},["paragraph","reference","blockquote"]],["lheading",function(t,e,r){const n=t.md.block.ruler.getRules("paragraph");if(t.sCount[e]-t.blkIndent>=4)return!1;const s=t.parentType;t.parentType="paragraph";let i,o=0,u=e+1;for(;u<r&&!t.isEmpty(u);u++){if(t.sCount[u]-t.blkIndent>3)continue;if(t.sCount[u]>=t.blkIndent){let e=t.bMarks[u]+t.tShift[u];const r=t.eMarks[u];if(e<r&&(i=t.src.charCodeAt(e),(45===i||61===i)&&(e=t.skipChars(e,i),e=t.skipSpaces(e),e>=r))){o=61===i?1:2;break}}if(t.sCount[u]<0)continue;let e=!1;for(let s=0,i=n.length;s<i;s++)if(n[s](t,u,r,!0)){e=!0;break}if(e)break}if(!o)return!1;const c=t.getLines(e,u,t.blkIndent,!1).trim();t.line=u+1;const a=t.push("heading_open","h"+String(o),1);a.markup=String.fromCharCode(i),a.map=[e,t.line];const l=t.push("inline","",0);return l.content=c,l.map=[e,t.line-1],l.children=[],t.push("heading_close","h"+String(o),-1).markup=String.fromCharCode(i),t.parentType=s,!0}],["paragraph",function(t,e,r){const n=t.md.block.ruler.getRules("paragraph"),s=t.parentType;let i=e+1;for(t.parentType="paragraph";i<r&&!t.isEmpty(i);i++){if(t.sCount[i]-t.blkIndent>3)continue;if(t.sCount[i]<0)continue;let e=!1;for(let s=0,o=n.length;s<o;s++)if(n[s](t,i,r,!0)){e=!0;break}if(e)break}const o=t.getLines(e,i,t.blkIndent,!1).trim();t.line=i,t.push("paragraph_open","p",1).map=[e,t.line];const u=t.push("inline","",0);return u.content=o,u.map=[e,t.line],u.children=[],t.push("paragraph_close","p",-1),t.parentType=s,!0}]];function Ht(){this.ruler=new dt;for(let t=0;t<Ut.length;t++)this.ruler.push(Ut[t][0],Ut[t][1],{alt:(Ut[t][2]||[]).slice()})}function Vt(t,e,r,n){this.src=t,this.env=r,this.md=e,this.tokens=n,this.tokens_meta=Array(n.length),this.pos=0,this.posMax=this.src.length,this.level=0,this.pending="",this.pendingLevel=0,this.cache={},this.delimiters=[],this._prev_delimiters=[],this.backticks={},this.backticksScanned=!1,this.linkLevel=0}function Gt(t){switch(t){case 10:case 33:case 35:case 36:case 37:case 38:case 42:case 43:case 45:case 58:case 60:case 61:case 62:case 64:case 91:case 92:case 93:case 94:case 95:case 96:case 123:case 125:case 126:return!0;default:return!1}}Ht.prototype.tokenize=function(t,e,r){const n=this.ruler.getRules(""),s=n.length,i=t.md.options.maxNesting;let o=e,u=!1;for(;o<r&&(t.line=o=t.skipEmptyLines(o),!(o>=r))&&!(t.sCount[o]<t.blkIndent);){if(t.level>=i){t.line=r;break}const e=t.line;let c=!1;for(let i=0;i<s;i++)if(c=n[i](t,o,r,!1),c){if(e>=t.line)throw new Error("block rule didn't increment state.line");break}if(!c)throw new Error("none of the block rules matched");t.tight=!u,t.isEmpty(t.line-1)&&(u=!0),o=t.line,o<r&&t.isEmpty(o)&&(u=!0,o++,t.line=o)}},Ht.prototype.parse=function(t,e,r,n){if(!t)return;const s=new this.State(t,e,r,n);this.tokenize(s,s.line,s.lineMax)},Ht.prototype.State=It,Vt.prototype.pushPending=function(){const t=new _t("text","",0);return t.content=this.pending,t.level=this.pendingLevel,this.tokens.push(t),this.pending="",t},Vt.prototype.push=function(t,e,r){this.pending&&this.pushPending();const n=new _t(t,e,r);let s=null;return r<0&&(this.level--,this.delimiters=this._prev_delimiters.pop()),n.level=this.level,r>0&&(this.level++,this._prev_delimiters.push(this.delimiters),this.delimiters=[],s={delimiters:this.delimiters}),this.pendingLevel=this.level,this.tokens.push(n),this.tokens_meta.push(s),n},Vt.prototype.scanDelims=function(t,e){const r=this.posMax,n=this.src.charCodeAt(t),s=t>0?this.src.charCodeAt(t-1):32;let i=t;for(;i<r&&this.src.charCodeAt(i)===n;)i++;const o=i-t,u=i<r?this.src.charCodeAt(i):32,c=ut(s)||ot(String.fromCharCode(s)),a=ut(u)||ot(String.fromCharCode(u)),l=it(s),h=it(u),p=!h&&(!a||l||c),f=!l&&(!c||h||a);return{can_open:p&&(e||!f||c),can_close:f&&(e||!p||a),length:o}},Vt.prototype.Token=_t;const Wt=/(?:^|[^a-z0-9.+-])([a-z][a-z0-9.+-]*)$/i;const Jt=[];for(let t=0;t<256;t++)Jt.push(0);function Qt(t,e){let r;const n=[],s=e.length;for(let i=0;i<s;i++){const s=e[i];if(126!==s.marker)continue;if(-1===s.end)continue;const o=e[s.end];r=t.tokens[s.token],r.type="s_open",r.tag="s",r.nesting=1,r.markup="~~",r.content="",r=t.tokens[o.token],r.type="s_close",r.tag="s",r.nesting=-1,r.markup="~~",r.content="","text"===t.tokens[o.token-1].type&&"~"===t.tokens[o.token-1].content&&n.push(o.token-1)}for(;n.length;){const e=n.pop();let s=e+1;for(;s<t.tokens.length&&"s_close"===t.tokens[s].type;)s++;s--,e!==s&&(r=t.tokens[s],t.tokens[s]=t.tokens[e],t.tokens[e]=r)}}"\\!\"#$%&'()*+,./:;<=>?@[]^_`{|}~-".split("").forEach((function(t){Jt[t.charCodeAt(0)]=1}));var Xt={tokenize:function(t,e){const r=t.pos,n=t.src.charCodeAt(r);if(e)return!1;if(126!==n)return!1;const s=t.scanDelims(t.pos,!0);let i=s.length;const o=String.fromCharCode(n);if(i<2)return!1;let u;i%2&&(u=t.push("text","",0),u.content=o,i--);for(let e=0;e<i;e+=2)u=t.push("text","",0),u.content=o+o,t.delimiters.push({marker:n,length:0,token:t.tokens.length-1,end:-1,open:s.can_open,close:s.can_close});return t.pos+=s.length,!0},postProcess:function(t){const e=t.tokens_meta,r=t.tokens_meta.length;Qt(t,t.delimiters);for(let n=0;n<r;n++)e[n]&&e[n].delimiters&&Qt(t,e[n].delimiters)}};function Yt(t,e){for(let r=e.length-1;r>=0;r--){const n=e[r];if(95!==n.marker&&42!==n.marker)continue;if(-1===n.end)continue;const s=e[n.end],i=r>0&&e[r-1].end===n.end+1&&e[r-1].marker===n.marker&&e[r-1].token===n.token-1&&e[n.end+1].token===s.token+1,o=String.fromCharCode(n.marker),u=t.tokens[n.token];u.type=i?"strong_open":"em_open",u.tag=i?"strong":"em",u.nesting=1,u.markup=i?o+o:o,u.content="";const c=t.tokens[s.token];c.type=i?"strong_close":"em_close",c.tag=i?"strong":"em",c.nesting=-1,c.markup=i?o+o:o,c.content="",i&&(t.tokens[e[r-1].token].content="",t.tokens[e[n.end+1].token].content="",r--)}}var Kt={tokenize:function(t,e){const r=t.pos,n=t.src.charCodeAt(r);if(e)return!1;if(95!==n&&42!==n)return!1;const s=t.scanDelims(t.pos,42===n);for(let e=0;e<s.length;e++){t.push("text","",0).content=String.fromCharCode(n),t.delimiters.push({marker:n,length:s.length,token:t.tokens.length-1,end:-1,open:s.can_open,close:s.can_close})}return t.pos+=s.length,!0},postProcess:function(t){const e=t.tokens_meta,r=t.tokens_meta.length;Yt(t,t.delimiters);for(let n=0;n<r;n++)e[n]&&e[n].delimiters&&Yt(t,e[n].delimiters)}};const te=/^([a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*)$/,ee=/^([a-zA-Z][a-zA-Z0-9+.-]{1,31}):([^<>\x00-\x20]*)$/;const re=/^&#((?:x[a-f0-9]{1,6}|[0-9]{1,7}));/i,ne=/^&([a-z][a-z0-9]{1,31});/i;function se(t){const e={},r=t.length;if(!r)return;let n=0,s=-2;const i=[];for(let o=0;o<r;o++){const r=t[o];if(i.push(0),t[n].marker===r.marker&&s===r.token-1||(n=o),s=r.token,r.length=r.length||0,!r.close)continue;e.hasOwnProperty(r.marker)||(e[r.marker]=[-1,-1,-1,-1,-1,-1]);const u=e[r.marker][(r.open?3:0)+r.length%3];let c=n-i[n]-1,a=c;for(;c>u;c-=i[c]+1){const e=t[c];if(e.marker===r.marker&&(e.open&&e.end<0)){let n=!1;if((e.close||r.open)&&(e.length+r.length)%3==0&&(e.length%3==0&&r.length%3==0||(n=!0)),!n){const n=c>0&&!t[c-1].open?i[c-1]+1:0;i[o]=o-c+n,i[c]=n,r.open=!1,e.end=o,e.close=!1,a=-1,s=-2;break}}}-1!==a&&(e[r.marker][(r.open?3:0)+(r.length||0)%3]=a)}}const ie=[["text",function(t,e){let r=t.pos;for(;r<t.posMax&&!Gt(t.src.charCodeAt(r));)r++;return r!==t.pos&&(e||(t.pending+=t.src.slice(t.pos,r)),t.pos=r,!0)}],["linkify",function(t,e){if(!t.md.options.linkify)return!1;if(t.linkLevel>0)return!1;const r=t.pos;if(r+3>t.posMax)return!1;if(58!==t.src.charCodeAt(r))return!1;if(47!==t.src.charCodeAt(r+1))return!1;if(47!==t.src.charCodeAt(r+2))return!1;const n=t.pending.match(Wt);if(!n)return!1;const s=n[1],i=t.md.linkify.matchAtStart(t.src.slice(r-s.length));if(!i)return!1;let o=i.url;if(o.length<=s.length)return!1;o=o.replace(/\*+$/,"");const u=t.md.normalizeLink(o);if(!t.md.validateLink(u))return!1;if(!e){t.pending=t.pending.slice(0,-s.length);const e=t.push("link_open","a",1);e.attrs=[["href",u]],e.markup="linkify",e.info="auto";t.push("text","",0).content=t.md.normalizeLinkText(o);const r=t.push("link_close","a",-1);r.markup="linkify",r.info="auto"}return t.pos+=o.length-s.length,!0}],["newline",function(t,e){let r=t.pos;if(10!==t.src.charCodeAt(r))return!1;const n=t.pending.length-1,s=t.posMax;if(!e)if(n>=0&&32===t.pending.charCodeAt(n))if(n>=1&&32===t.pending.charCodeAt(n-1)){let e=n-1;for(;e>=1&&32===t.pending.charCodeAt(e-1);)e--;t.pending=t.pending.slice(0,e),t.push("hardbreak","br",0)}else t.pending=t.pending.slice(0,-1),t.push("softbreak","br",0);else t.push("softbreak","br",0);for(r++;r<s&&st(t.src.charCodeAt(r));)r++;return t.pos=r,!0}],["escape",function(t,e){let r=t.pos;const n=t.posMax;if(92!==t.src.charCodeAt(r))return!1;if(r++,r>=n)return!1;let s=t.src.charCodeAt(r);if(10===s){for(e||t.push("hardbreak","br",0),r++;r<n&&(s=t.src.charCodeAt(r),st(s));)r++;return t.pos=r,!0}let i=t.src[r];if(s>=55296&&s<=56319&&r+1<n){const e=t.src.charCodeAt(r+1);e>=56320&&e<=57343&&(i+=t.src[r+1],r++)}const o="\\"+i;if(!e){const e=t.push("text_special","",0);s<256&&0!==Jt[s]?e.content=i:e.content=o,e.markup=o,e.info="escape"}return t.pos=r+1,!0}],["backticks",function(t,e){let r=t.pos;if(96!==t.src.charCodeAt(r))return!1;const n=r;r++;const s=t.posMax;for(;r<s&&96===t.src.charCodeAt(r);)r++;const i=t.src.slice(n,r),o=i.length;if(t.backticksScanned&&(t.backticks[o]||0)<=n)return e||(t.pending+=i),t.pos+=o,!0;let u,c=r;for(;-1!==(u=t.src.indexOf("`",c));){for(c=u+1;c<s&&96===t.src.charCodeAt(c);)c++;const n=c-u;if(n===o){if(!e){const e=t.push("code_inline","code",0);e.markup=i,e.content=t.src.slice(r,u).replace(/\n/g," ").replace(/^ (.+) $/,"$1")}return t.pos=c,!0}t.backticks[n]=u}return t.backticksScanned=!0,e||(t.pending+=i),t.pos+=o,!0}],["strikethrough",Xt.tokenize],["emphasis",Kt.tokenize],["link",function(t,e){let r,n,s,i,o="",u="",c=t.pos,a=!0;if(91!==t.src.charCodeAt(t.pos))return!1;const l=t.pos,h=t.posMax,p=t.pos+1,f=t.md.helpers.parseLinkLabel(t,t.pos,!0);if(f<0)return!1;let d=f+1;if(d<h&&40===t.src.charCodeAt(d)){for(a=!1,d++;d<h&&(r=t.src.charCodeAt(d),st(r)||10===r);d++);if(d>=h)return!1;if(c=d,s=t.md.helpers.parseLinkDestination(t.src,d,t.posMax),s.ok){for(o=t.md.normalizeLink(s.str),t.md.validateLink(o)?d=s.pos:o="",c=d;d<h&&(r=t.src.charCodeAt(d),st(r)||10===r);d++);if(s=t.md.helpers.parseLinkTitle(t.src,d,t.posMax),d<h&&c!==d&&s.ok)for(u=s.str,d=s.pos;d<h&&(r=t.src.charCodeAt(d),st(r)||10===r);d++);}(d>=h||41!==t.src.charCodeAt(d))&&(a=!0),d++}if(a){if(void 0===t.env.references)return!1;if(d<h&&91===t.src.charCodeAt(d)?(c=d+1,d=t.md.helpers.parseLinkLabel(t,d),d>=0?n=t.src.slice(c,d++):d=f+1):d=f+1,n||(n=t.src.slice(p,f)),i=t.env.references[ct(n)],!i)return t.pos=l,!1;o=i.href,u=i.title}if(!e){t.pos=p,t.posMax=f;const e=[["href",o]];t.push("link_open","a",1).attrs=e,u&&e.push(["title",u]),t.linkLevel++,t.md.inline.tokenize(t),t.linkLevel--,t.push("link_close","a",-1)}return t.pos=d,t.posMax=h,!0}],["image",function(t,e){let r,n,s,i,o,u,c,a,l="";const h=t.pos,p=t.posMax;if(33!==t.src.charCodeAt(t.pos))return!1;if(91!==t.src.charCodeAt(t.pos+1))return!1;const f=t.pos+2,d=t.md.helpers.parseLinkLabel(t,t.pos+1,!1);if(d<0)return!1;if(i=d+1,i<p&&40===t.src.charCodeAt(i)){for(i++;i<p&&(r=t.src.charCodeAt(i),st(r)||10===r);i++);if(i>=p)return!1;for(a=i,u=t.md.helpers.parseLinkDestination(t.src,i,t.posMax),u.ok&&(l=t.md.normalizeLink(u.str),t.md.validateLink(l)?i=u.pos:l=""),a=i;i<p&&(r=t.src.charCodeAt(i),st(r)||10===r);i++);if(u=t.md.helpers.parseLinkTitle(t.src,i,t.posMax),i<p&&a!==i&&u.ok)for(c=u.str,i=u.pos;i<p&&(r=t.src.charCodeAt(i),st(r)||10===r);i++);else c="";if(i>=p||41!==t.src.charCodeAt(i))return t.pos=h,!1;i++}else{if(void 0===t.env.references)return!1;if(i<p&&91===t.src.charCodeAt(i)?(a=i+1,i=t.md.helpers.parseLinkLabel(t,i),i>=0?s=t.src.slice(a,i++):i=d+1):i=d+1,s||(s=t.src.slice(f,d)),o=t.env.references[ct(s)],!o)return t.pos=h,!1;l=o.href,c=o.title}if(!e){n=t.src.slice(f,d);const e=[];t.md.inline.parse(n,t.md,t.env,e);const r=t.push("image","img",0),s=[["src",l],["alt",""]];r.attrs=s,r.children=e,r.content=n,c&&s.push(["title",c])}return t.pos=i,t.posMax=p,!0}],["autolink",function(t,e){let r=t.pos;if(60!==t.src.charCodeAt(r))return!1;const n=t.pos,s=t.posMax;for(;;){if(++r>=s)return!1;const e=t.src.charCodeAt(r);if(60===e)return!1;if(62===e)break}const i=t.src.slice(n+1,r);if(ee.test(i)){const r=t.md.normalizeLink(i);if(!t.md.validateLink(r))return!1;if(!e){const e=t.push("link_open","a",1);e.attrs=[["href",r]],e.markup="autolink",e.info="auto";t.push("text","",0).content=t.md.normalizeLinkText(i);const n=t.push("link_close","a",-1);n.markup="autolink",n.info="auto"}return t.pos+=i.length+2,!0}if(te.test(i)){const r=t.md.normalizeLink("mailto:"+i);if(!t.md.validateLink(r))return!1;if(!e){const e=t.push("link_open","a",1);e.attrs=[["href",r]],e.markup="autolink",e.info="auto";t.push("text","",0).content=t.md.normalizeLinkText(i);const n=t.push("link_close","a",-1);n.markup="autolink",n.info="auto"}return t.pos+=i.length+2,!0}return!1}],["html_inline",function(t,e){if(!t.md.options.html)return!1;const r=t.posMax,n=t.pos;if(60!==t.src.charCodeAt(n)||n+2>=r)return!1;const s=t.src.charCodeAt(n+1);if(33!==s&&63!==s&&47!==s&&!function(t){const e=32|t;return e>=97&&e<=122}(s))return!1;const i=t.src.slice(n).match(jt);if(!i)return!1;if(!e){const e=t.push("html_inline","",0);e.content=i[0],o=e.content,/^<a[>\s]/i.test(o)&&t.linkLevel++,function(t){return/^<\/a\s*>/i.test(t)}(e.content)&&t.linkLevel--}var o;return t.pos+=i[0].length,!0}],["entity",function(t,e){const r=t.pos,n=t.posMax;if(38!==t.src.charCodeAt(r))return!1;if(r+1>=n)return!1;if(35===t.src.charCodeAt(r+1)){const n=t.src.slice(r).match(re);if(n){if(!e){const e="x"===n[1][0].toLowerCase()?parseInt(n[1].slice(1),16):parseInt(n[1],10),r=t.push("text_special","",0);r.content=V(e)?G(e):G(65533),r.markup=n[0],r.info="entity"}return t.pos+=n[0].length,!0}}else{const n=t.src.slice(r).match(ne);if(n){const r=j(n[0]);if(r!==n[0]){if(!e){const e=t.push("text_special","",0);e.content=r,e.markup=n[0],e.info="entity"}return t.pos+=n[0].length,!0}}}return!1}]],oe=[["balance_pairs",function(t){const e=t.tokens_meta,r=t.tokens_meta.length;se(t.delimiters);for(let t=0;t<r;t++)e[t]&&e[t].delimiters&&se(e[t].delimiters)}],["strikethrough",Xt.postProcess],["emphasis",Kt.postProcess],["fragments_join",function(t){let e,r,n=0;const s=t.tokens,i=t.tokens.length;for(e=r=0;e<i;e++)s[e].nesting<0&&n--,s[e].level=n,s[e].nesting>0&&n++,"text"===s[e].type&&e+1<i&&"text"===s[e+1].type?s[e+1].content=s[e].content+s[e+1].content:(e!==r&&(s[r]=s[e]),r++);e!==r&&(s.length=r)}]];function ue(){this.ruler=new dt;for(let t=0;t<ie.length;t++)this.ruler.push(ie[t][0],ie[t][1]);this.ruler2=new dt;for(let t=0;t<oe.length;t++)this.ruler2.push(oe[t][0],oe[t][1])}function ce(t){return Array.prototype.slice.call(arguments,1).forEach((function(e){e&&Object.keys(e).forEach((function(r){t[r]=e[r]}))})),t}function ae(t){return Object.prototype.toString.call(t)}function le(t){return"[object Function]"===ae(t)}function he(t){return t.replace(/[.?*+^$[\]\\(){}|-]/g,"\\$&")}ue.prototype.skipToken=function(t){const e=t.pos,r=this.ruler.getRules(""),n=r.length,s=t.md.options.maxNesting,i=t.cache;if(void 0!==i[e])return void(t.pos=i[e]);let o=!1;if(t.level<s){for(let s=0;s<n;s++)if(t.level++,o=r[s](t,!0),t.level--,o){if(e>=t.pos)throw new Error("inline rule didn't increment state.pos");break}}else t.pos=t.posMax;o||t.pos++,i[e]=t.pos},ue.prototype.tokenize=function(t){const e=this.ruler.getRules(""),r=e.length,n=t.posMax,s=t.md.options.maxNesting;for(;t.pos<n;){const i=t.pos;let o=!1;if(t.level<s)for(let n=0;n<r;n++)if(o=e[n](t,!1),o){if(i>=t.pos)throw new Error("inline rule didn't increment state.pos");break}if(o){if(t.pos>=n)break}else t.pending+=t.src[t.pos++]}t.pending&&t.pushPending()},ue.prototype.parse=function(t,e,r,n){const s=new this.State(t,e,r,n);this.tokenize(s);const i=this.ruler2.getRules(""),o=i.length;for(let t=0;t<o;t++)i[t](s)},ue.prototype.State=Vt;const pe={fuzzyLink:!0,fuzzyEmail:!0,fuzzyIP:!1};const fe={"http:":{validate:function(t,e,r){const n=t.slice(e);return r.re.http||(r.re.http=new RegExp("^\\/\\/"+r.re.src_auth+r.re.src_host_port_strict+r.re.src_path,"i")),r.re.http.test(n)?n.match(r.re.http)[0].length:0}},"https:":"http:","ftp:":"http:","//":{validate:function(t,e,r){const n=t.slice(e);return r.re.no_http||(r.re.no_http=new RegExp("^"+r.re.src_auth+"(?:localhost|(?:(?:"+r.re.src_domain+")\\.)+"+r.re.src_domain_root+")"+r.re.src_port+r.re.src_host_terminator+r.re.src_path,"i")),r.re.no_http.test(n)?e>=3&&":"===t[e-3]||e>=3&&"/"===t[e-3]?0:n.match(r.re.no_http)[0].length:0}},"mailto:":{validate:function(t,e,r){const n=t.slice(e);return r.re.mailto||(r.re.mailto=new RegExp("^"+r.re.src_email_name+"@"+r.re.src_host_strict,"i")),r.re.mailto.test(n)?n.match(r.re.mailto)[0].length:0}}},de="a[cdefgilmnoqrstuwxz]|b[abdefghijmnorstvwyz]|c[acdfghiklmnoruvwxyz]|d[ejkmoz]|e[cegrstu]|f[ijkmor]|g[abdefghilmnpqrstuwy]|h[kmnrtu]|i[delmnoqrst]|j[emop]|k[eghimnprwyz]|l[abcikrstuvy]|m[acdeghklmnopqrstuvwxyz]|n[acefgilopruz]|om|p[aefghklmnrstwy]|qa|r[eosuw]|s[abcdeghijklmnortuvxyz]|t[cdfghjklmnortvwz]|u[agksyz]|v[aceginu]|w[fs]|y[et]|z[amw]",_e="biz|com|edu|gov|net|org|pro|web|xxx|aero|asia|coop|info|museum|name|shop|\u0440\u0444".split("|");function me(t){const e=t.re=function(t){const e={};t=t||{},e.src_Any=C.source,e.src_Cc=y.source,e.src_Z=b.source,e.src_P=E.source,e.src_ZPCc=[e.src_Z,e.src_P,e.src_Cc].join("|"),e.src_ZCc=[e.src_Z,e.src_Cc].join("|");const r="[><\uff5c]";return e.src_pseudo_letter="(?:(?![><\uff5c]|"+e.src_ZPCc+")"+e.src_Any+")",e.src_ip4="(?:(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)",e.src_auth="(?:(?:(?!"+e.src_ZCc+"|[@/\\[\\]()]).)+@)?",e.src_port="(?::(?:6(?:[0-4]\\d{3}|5(?:[0-4]\\d{2}|5(?:[0-2]\\d|3[0-5])))|[1-5]?\\d{1,4}))?",e.src_host_terminator="(?=$|[><\uff5c]|"+e.src_ZPCc+")(?!"+(t["---"]?"-(?!--)|":"-|")+"_|:\\d|\\.-|\\.(?!$|"+e.src_ZPCc+"))",e.src_path="(?:[/?#](?:(?!"+e.src_ZCc+"|"+r+"|[()[\\]{}.,\"'?!\\-;]).|\\[(?:(?!"+e.src_ZCc+"|\\]).)*\\]|\\((?:(?!"+e.src_ZCc+"|[)]).)*\\)|\\{(?:(?!"+e.src_ZCc+'|[}]).)*\\}|\\"(?:(?!'+e.src_ZCc+'|["]).)+\\"|\\\'(?:(?!'+e.src_ZCc+"|[']).)+\\'|\\'(?="+e.src_pseudo_letter+"|[-])|\\.{2,}[a-zA-Z0-9%/&]|\\.(?!"+e.src_ZCc+"|[.]|$)|"+(t["---"]?"\\-(?!--(?:[^-]|$))(?:-*)|":"\\-+|")+",(?!"+e.src_ZCc+"|$)|;(?!"+e.src_ZCc+"|$)|\\!+(?!"+e.src_ZCc+"|[!]|$)|\\?(?!"+e.src_ZCc+"|[?]|$))+|\\/)?",e.src_email_name='[\\-;:&=\\+\\$,\\.a-zA-Z0-9_][\\-;:&=\\+\\$,\\"\\.a-zA-Z0-9_]*',e.src_xn="xn--[a-z0-9\\-]{1,59}",e.src_domain_root="(?:"+e.src_xn+"|"+e.src_pseudo_letter+"{1,63})",e.src_domain="(?:"+e.src_xn+"|(?:"+e.src_pseudo_letter+")|(?:"+e.src_pseudo_letter+"(?:-|"+e.src_pseudo_letter+"){0,61}"+e.src_pseudo_letter+"))",e.src_host="(?:(?:(?:(?:"+e.src_domain+")\\.)*"+e.src_domain+"))",e.tpl_host_fuzzy="(?:"+e.src_ip4+"|(?:(?:(?:"+e.src_domain+")\\.)+(?:%TLDS%)))",e.tpl_host_no_ip_fuzzy="(?:(?:(?:"+e.src_domain+")\\.)+(?:%TLDS%))",e.src_host_strict=e.src_host+e.src_host_terminator,e.tpl_host_fuzzy_strict=e.tpl_host_fuzzy+e.src_host_terminator,e.src_host_port_strict=e.src_host+e.src_port+e.src_host_terminator,e.tpl_host_port_fuzzy_strict=e.tpl_host_fuzzy+e.src_port+e.src_host_terminator,e.tpl_host_port_no_ip_fuzzy_strict=e.tpl_host_no_ip_fuzzy+e.src_port+e.src_host_terminator,e.tpl_host_fuzzy_test="localhost|www\\.|\\.\\d{1,3}\\.|(?:\\.(?:%TLDS%)(?:"+e.src_ZPCc+"|>|$))",e.tpl_email_fuzzy='(^|[><\uff5c]|"|\\(|'+e.src_ZCc+")("+e.src_email_name+"@"+e.tpl_host_fuzzy_strict+")",e.tpl_link_fuzzy="(^|(?![.:/\\-_@])(?:[$+<=>^`|\uff5c]|"+e.src_ZPCc+"))((?![$+<=>^`|\uff5c])"+e.tpl_host_port_fuzzy_strict+e.src_path+")",e.tpl_link_no_ip_fuzzy="(^|(?![.:/\\-_@])(?:[$+<=>^`|\uff5c]|"+e.src_ZPCc+"))((?![$+<=>^`|\uff5c])"+e.tpl_host_port_no_ip_fuzzy_strict+e.src_path+")",e}(t.__opts__),r=t.__tlds__.slice();function n(t){return t.replace("%TLDS%",e.src_tlds)}t.onCompile(),t.__tlds_replaced__||r.push(de),r.push(e.src_xn),e.src_tlds=r.join("|"),e.email_fuzzy=RegExp(n(e.tpl_email_fuzzy),"i"),e.link_fuzzy=RegExp(n(e.tpl_link_fuzzy),"i"),e.link_no_ip_fuzzy=RegExp(n(e.tpl_link_no_ip_fuzzy),"i"),e.host_fuzzy_test=RegExp(n(e.tpl_host_fuzzy_test),"i");const s=[];function i(t,e){throw new Error('(LinkifyIt) Invalid schema "'+t+'": '+e)}t.__compiled__={},Object.keys(t.__schemas__).forEach((function(e){const r=t.__schemas__[e];if(null===r)return;const n={validate:null,link:null};if(t.__compiled__[e]=n,"[object Object]"===ae(r))return!function(t){return"[object RegExp]"===ae(t)}(r.validate)?le(r.validate)?n.validate=r.validate:i(e,r):n.validate=function(t){return function(e,r){const n=e.slice(r);return t.test(n)?n.match(t)[0].length:0}}(r.validate),void(le(r.normalize)?n.normalize=r.normalize:r.normalize?i(e,r):n.normalize=function(t,e){e.normalize(t)});!function(t){return"[object String]"===ae(t)}(r)?i(e,r):s.push(e)})),s.forEach((function(e){t.__compiled__[t.__schemas__[e]]&&(t.__compiled__[e].validate=t.__compiled__[t.__schemas__[e]].validate,t.__compiled__[e].normalize=t.__compiled__[t.__schemas__[e]].normalize)})),t.__compiled__[""]={validate:null,normalize:function(t,e){e.normalize(t)}};const o=Object.keys(t.__compiled__).filter((function(e){return e.length>0&&t.__compiled__[e]})).map(he).join("|");t.re.schema_test=RegExp("(^|(?!_)(?:[><\uff5c]|"+e.src_ZPCc+"))("+o+")","i"),t.re.schema_search=RegExp("(^|(?!_)(?:[><\uff5c]|"+e.src_ZPCc+"))("+o+")","ig"),t.re.schema_at_start=RegExp("^"+t.re.schema_search.source,"i"),t.re.pretest=RegExp("("+t.re.schema_test.source+")|("+t.re.host_fuzzy_test.source+")|@","i"),function(t){t.__index__=-1,t.__text_cache__=""}(t)}function ge(t,e){const r=t.__index__,n=t.__last_index__,s=t.__text_cache__.slice(r,n);this.schema=t.__schema__.toLowerCase(),this.index=r+e,this.lastIndex=n+e,this.raw=s,this.text=s,this.url=s}function ke(t,e){const r=new ge(t,e);return t.__compiled__[r.schema].normalize(r,t),r}function De(t,e){if(!(this instanceof De))return new De(t,e);var r;e||(r=t,Object.keys(r||{}).reduce((function(t,e){return t||pe.hasOwnProperty(e)}),!1)&&(e=t,t={})),this.__opts__=ce({},pe,e),this.__index__=-1,this.__last_index__=-1,this.__schema__="",this.__text_cache__="",this.__schemas__=ce({},fe,t),this.__compiled__={},this.__tlds__=_e,this.__tlds_replaced__=!1,this.re={},me(this)}De.prototype.add=function(t,e){return this.__schemas__[t]=e,me(this),this},De.prototype.set=function(t){return this.__opts__=ce(this.__opts__,t),this},De.prototype.test=function(t){if(this.__text_cache__=t,this.__index__=-1,!t.length)return!1;let e,r,n,s,i,o,u,c,a;if(this.re.schema_test.test(t))for(u=this.re.schema_search,u.lastIndex=0;null!==(e=u.exec(t));)if(s=this.testSchemaAt(t,e[2],u.lastIndex),s){this.__schema__=e[2],this.__index__=e.index+e[1].length,this.__last_index__=e.index+e[0].length+s;break}return this.__opts__.fuzzyLink&&this.__compiled__["http:"]&&(c=t.search(this.re.host_fuzzy_test),c>=0&&(this.__index__<0||c<this.__index__)&&null!==(r=t.match(this.__opts__.fuzzyIP?this.re.link_fuzzy:this.re.link_no_ip_fuzzy))&&(i=r.index+r[1].length,(this.__index__<0||i<this.__index__)&&(this.__schema__="",this.__index__=i,this.__last_index__=r.index+r[0].length))),this.__opts__.fuzzyEmail&&this.__compiled__["mailto:"]&&(a=t.indexOf("@"),a>=0&&null!==(n=t.match(this.re.email_fuzzy))&&(i=n.index+n[1].length,o=n.index+n[0].length,(this.__index__<0||i<this.__index__||i===this.__index__&&o>this.__last_index__)&&(this.__schema__="mailto:",this.__index__=i,this.__last_index__=o))),this.__index__>=0},De.prototype.pretest=function(t){return this.re.pretest.test(t)},De.prototype.testSchemaAt=function(t,e,r){return this.__compiled__[e.toLowerCase()]?this.__compiled__[e.toLowerCase()].validate(t,r,this):0},De.prototype.match=function(t){const e=[];let r=0;this.__index__>=0&&this.__text_cache__===t&&(e.push(ke(this,r)),r=this.__last_index__);let n=r?t.slice(r):t;for(;this.test(n);)e.push(ke(this,r)),n=n.slice(this.__last_index__),r+=this.__last_index__;return e.length?e:null},De.prototype.matchAtStart=function(t){if(this.__text_cache__=t,this.__index__=-1,!t.length)return null;const e=this.re.schema_at_start.exec(t);if(!e)return null;const r=this.testSchemaAt(t,e[2],e[0].length);return r?(this.__schema__=e[2],this.__index__=e.index+e[1].length,this.__last_index__=e.index+e[0].length+r,ke(this,0)):null},De.prototype.tlds=function(t,e){return t=Array.isArray(t)?t:[t],e?(this.__tlds__=this.__tlds__.concat(t).sort().filter((function(t,e,r){return t!==r[e-1]})).reverse(),me(this),this):(this.__tlds__=t.slice(),this.__tlds_replaced__=!0,me(this),this)},De.prototype.normalize=function(t){t.schema||(t.url="http://"+t.url),"mailto:"!==t.schema||/^mailto:/i.test(t.url)||(t.url="mailto:"+t.url)},De.prototype.onCompile=function(){};const Ce=2147483647,ye=36,Ee=/^xn--/,Ae=/[^\0-\x7F]/,be=/[\x2E\u3002\uFF0E\uFF61]/g,Fe={overflow:"Overflow: input needs wider integers to process","not-basic":"Illegal input >= 0x80 (not a basic code point)","invalid-input":"Invalid input"},xe=Math.floor,we=String.fromCharCode;function ve(t){throw new RangeError(Fe[t])}function ze(t,e){const r=t.split("@");let n="";r.length>1&&(n=r[0]+"@",t=r[1]);const s=function(t,e){const r=[];let n=t.length;for(;n--;)r[n]=e(t[n]);return r}((t=t.replace(be,".")).split("."),e).join(".");return n+s}function Se(t){const e=[];let r=0;const n=t.length;for(;r<n;){const s=t.charCodeAt(r++);if(s>=55296&&s<=56319&&r<n){const n=t.charCodeAt(r++);56320==(64512&n)?e.push(((1023&s)<<10)+(1023&n)+65536):(e.push(s),r--)}else e.push(s)}return e}const qe=function(t,e){return t+22+75*(t<26)-((0!=e)<<5)},Be=function(t,e,r){let n=0;for(t=r?xe(t/700):t>>1,t+=xe(t/e);t>455;n+=ye)t=xe(t/35);return xe(n+36*t/(t+38))},Le=function(t){const e=[],r=t.length;let n=0,s=128,i=72,o=t.lastIndexOf("-");o<0&&(o=0);for(let r=0;r<o;++r)t.charCodeAt(r)>=128&&ve("not-basic"),e.push(t.charCodeAt(r));for(let c=o>0?o+1:0;c<r;){const o=n;for(let e=1,s=ye;;s+=ye){c>=r&&ve("invalid-input");const o=(u=t.charCodeAt(c++))>=48&&u<58?u-48+26:u>=65&&u<91?u-65:u>=97&&u<123?u-97:ye;o>=ye&&ve("invalid-input"),o>xe((Ce-n)/e)&&ve("overflow"),n+=o*e;const a=s<=i?1:s>=i+26?26:s-i;if(o<a)break;const l=ye-a;e>xe(Ce/l)&&ve("overflow"),e*=l}const a=e.length+1;i=Be(n-o,a,0==o),xe(n/a)>Ce-s&&ve("overflow"),s+=xe(n/a),n%=a,e.splice(n++,0,s)}var u;return String.fromCodePoint(...e)},Ie=function(t){const e=[],r=(t=Se(t)).length;let n=128,s=0,i=72;for(const r of t)r<128&&e.push(we(r));const o=e.length;let u=o;for(o&&e.push("-");u<r;){let r=Ce;for(const e of t)e>=n&&e<r&&(r=e);const c=u+1;r-n>xe((Ce-s)/c)&&ve("overflow"),s+=(r-n)*c,n=r;for(const r of t)if(r<n&&++s>Ce&&ve("overflow"),r===n){let t=s;for(let r=ye;;r+=ye){const n=r<=i?1:r>=i+26?26:r-i;if(t<n)break;const s=t-n,o=ye-n;e.push(we(qe(n+s%o,0))),t=xe(s/o)}e.push(we(qe(t,0))),i=Be(s,c,u===o),s=0,++u}++s,++n}return e.join("")},Me={version:"2.3.1",ucs2:{decode:Se,encode:t=>String.fromCodePoint(...t)},decode:Le,encode:Ie,toASCII:function(t){return ze(t,(function(t){return Ae.test(t)?"xn--"+Ie(t):t}))},toUnicode:function(t){return ze(t,(function(t){return Ee.test(t)?Le(t.slice(4).toLowerCase()):t}))}};const Te={default:{options:{html:!1,xhtmlOut:!1,breaks:!1,langPrefix:"language-",linkify:!1,typographer:!1,quotes:"\u201c\u201d\u2018\u2019",highlight:null,maxNesting:100},components:{core:{},block:{},inline:{}}},zero:{options:{html:!1,xhtmlOut:!1,breaks:!1,langPrefix:"language-",linkify:!1,typographer:!1,quotes:"\u201c\u201d\u2018\u2019",highlight:null,maxNesting:20},components:{core:{rules:["normalize","block","inline","text_join"]},block:{rules:["paragraph"]},inline:{rules:["text"],rules2:["balance_pairs","fragments_join"]}}},commonmark:{options:{html:!0,xhtmlOut:!0,breaks:!1,langPrefix:"language-",linkify:!1,typographer:!1,quotes:"\u201c\u201d\u2018\u2019",highlight:null,maxNesting:20},components:{core:{rules:["normalize","block","inline","text_join"]},block:{rules:["blockquote","code","fence","heading","hr","html_block","lheading","list","reference","paragraph"]},inline:{rules:["autolink","backticks","emphasis","entity","escape","html_inline","image","link","newline","text"],rules2:["balance_pairs","emphasis","fragments_join"]}}}},Re=/^(vbscript|javascript|file|data):/,Ne=/^data:image\/(gif|png|jpeg|webp);/;function Pe(t){const e=t.trim().toLowerCase();return!Re.test(e)||Ne.test(e)}const Oe=["http:","https:","mailto:"];function je(t){const e=g(t,!0);if(e.hostname&&(!e.protocol||Oe.indexOf(e.protocol)>=0))try{e.hostname=Me.toASCII(e.hostname)}catch(t){}return n(s(e))}function Ze(t){const r=g(t,!0);if(r.hostname&&(!r.protocol||Oe.indexOf(r.protocol)>=0))try{r.hostname=Me.toUnicode(r.hostname)}catch(t){}return e(s(r),e.defaultChars+"%")}function $e(t,e){if(!(this instanceof $e))return new $e(t,e);e||Z(t)||(e=t||{},t="default"),this.inline=new ue,this.block=new Ht,this.core=new Lt,this.renderer=new ft,this.linkify=new De,this.validateLink=Pe,this.normalizeLink=je,this.normalizeLinkText=Ze,this.utils=lt,this.helpers=U({},ht),this.options={},this.configure(t),e&&this.set(e)}return $e.prototype.set=function(t){return U(this.options,t),this},$e.prototype.configure=function(t){const e=this;if(Z(t)){const e=t;if(!(t=Te[e]))throw new Error('Wrong `markdown-it` preset "'+e+'", check name')}if(!t)throw new Error("Wrong `markdown-it` preset, can't be empty");return t.options&&e.set(t.options),t.components&&Object.keys(t.components).forEach((function(r){t.components[r].rules&&e[r].ruler.enableOnly(t.components[r].rules),t.components[r].rules2&&e[r].ruler2.enableOnly(t.components[r].rules2)})),this},$e.prototype.enable=function(t,e){let r=[];Array.isArray(t)||(t=[t]),["core","block","inline"].forEach((function(e){r=r.concat(this[e].ruler.enable(t,!0))}),this),r=r.concat(this.inline.ruler2.enable(t,!0));const n=t.filter((function(t){return r.indexOf(t)<0}));if(n.length&&!e)throw new Error("MarkdownIt. Failed to enable unknown rule(s): "+n);return this},$e.prototype.disable=function(t,e){let r=[];Array.isArray(t)||(t=[t]),["core","block","inline"].forEach((function(e){r=r.concat(this[e].ruler.disable(t,!0))}),this),r=r.concat(this.inline.ruler2.disable(t,!0));const n=t.filter((function(t){return r.indexOf(t)<0}));if(n.length&&!e)throw new Error("MarkdownIt. Failed to disable unknown rule(s): "+n);return this},$e.prototype.use=function(t){const e=[this].concat(Array.prototype.slice.call(arguments,1));return t.apply(t,e),this},$e.prototype.parse=function(t,e){if("string"!=typeof t)throw new Error("Input data should be a String");const r=new this.core.State(t,this,e);return this.core.process(r),r.tokens},$e.prototype.render=function(t,e){return e=e||{},this.renderer.render(this.parse(t,e),this.options,e)},$e.prototype.parseInline=function(t,e){const r=new this.core.State(t,this,e);return r.inlineMode=!0,this.core.process(r),r.tokens},$e.prototype.renderInline=function(t,e){return e=e||{},this.renderer.render(this.parseInline(t,e),this.options,e)},$e}));

</script>
<script>
"use strict";
/* ============================================================
   Work & Travel Calendar — v2 (planner)
   index.php shows the app; api.php (unchanged) signs people in and
   stores the calendar JSON in data/. Everything else runs here.
   Data: v1 fields (days, monthNotes, owner) are kept exactly as they
   were. v2 adds settings, calendars, projects, events, tasks, templates,
   trash, a "private" block (Personal items, encrypted in the browser
   with a passphrase) and "busy" (free/busy times of private items).
   ============================================================ */

const STATUSES = {
  office:  {label:'Workplace',       short:'Workplace'},
  tele:    {label:'Telework',        short:'Telework'},
  abroad:  {label:'Telework abroad', short:'TW abroad'},
  leave:   {label:'Leave',           short:'Leave'},
  sick:    {label:'Sick',            short:'Sick'},
  holiday: {label:'Public holiday',  short:'Holiday'},
};
const FLIGHT_TIMES = {morning:'Morning', noon:'Noon', night:'Night'};
/* default flight hours and alarm (minutes of the day, local time of the departure city);
   each flight can override its departure, arrival and alarm times */
const FLIGHT_WINDOWS = {morning:[8*60,12*60], noon:[16*60,20*60], night:[20*60,24*60]};
const FLIGHT_ALARM = {morning:6*60, noon:14*60, night:17*60+30};
function flightKind(f){ return f.time==='evening' ? 'noon' : (FLIGHT_WINDOWS[f.time] ? f.time : 'morning'); } // v1 "evening" = Noon 16–20
function flightTz(f){ return CITY_TZ[(f.from||'').trim().toLowerCase()] || (data&&data.settings&&data.settings.homeTz) || BROWSER_TZ; }
function flightAlarmMin(f){
  if(f.alarm==='off') return null;
  const a=hmToMin(f.alarm||''); if(a!==null) return a;
  const k=flightKind(f), d=FLIGHT_WINDOWS[k], dep=hmToMin(f.dep||'');
  return dep!==null ? Math.max(0, dep-(d[0]-FLIGHT_ALARM[k])) : FLIGHT_ALARM[k];
}
function flightMins(f){
  const d=FLIGHT_WINDOWS[flightKind(f)];
  let a=hmToMin(f.dep||''), b=hmToMin(f.arr||'');
  const custom = a!==null || b!==null;
  if(a===null) a=d[0]; if(b===null) b=(hmToMin(f.dep||'')!==null)?Math.min(24*60,a+(d[1]-d[0])):d[1];
  if(b<=a) b=24*60;
  return {a, b, custom};
}
function flightTimeLabel(f){ const m=flightMins(f); return minToHM(m.a)+'–'+(m.b>=1440?'24:00':minToHM(m.b)); }
function flightLabel(f){ return FLIGHT_TIMES[flightKind(f)]+' '+flightTimeLabel(f); }
const CITY_CODES = {athens:'ATH', brussels:'BRU'};
const CITY_TZ = {athens:'Europe/Athens', brussels:'Europe/Brussels', london:'Europe/London', paris:'Europe/Paris', luxembourg:'Europe/Luxembourg',
  strasbourg:'Europe/Paris', berlin:'Europe/Berlin', rome:'Europe/Rome', madrid:'Europe/Madrid', lisbon:'Europe/Lisbon', vienna:'Europe/Vienna',
  amsterdam:'Europe/Amsterdam', thessaloniki:'Europe/Athens', nicosia:'Asia/Nicosia', 'new york':'America/New_York', geneva:'Europe/Zurich'};
const DOW = ['MON','TUE','WED','THU','FRI','SAT','SUN'];
const DOW3 = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
const DAY_NAMES = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const API = 'api.php';
const POLL_MS = 60000;
const SCHEMA = 2;
const APP_VERSION = '2.7.0';   // see CHANGELOG.md
const CATEGORIES = {meeting:'👥 Meeting', appointment:'📌 Appointment', deadline:'⏰ Deadline', travel:'🧳 Travel', social:'🎉 Social', other:'• Other'};
const CAT_ICON = {meeting:'👥', appointment:'📌', deadline:'⏰', travel:'🧳', social:'🎉', other:''};
const TASK_STATUS = {todo:'To do', 'in-progress':'In progress', waiting:'Waiting', done:'Done', cancelled:'Cancelled'};
const PROJ_STATUS = {active:'Active', 'on-hold':'On hold', completed:'Completed', archived:'Archived'};
const SHOW_AS = {busy:'Busy', free:'Free', tentative:'Tentative', ooo:'Out of office'};
const PROJ_ICONS = ['📁','💼','🏛','✈','🏠','📚','🧾','⚖','🛠','❤','🎯','🌍','📝','🎓','💡'];
const PALETTE = ['#3b5bdb','#2f9e44','#7048e8','#e8590c','#c92a2a','#0b7285','#d6336c','#5c940d','#9c36b5','#1971c2','#f08c00','#495057'];
const OFFSETS = [[0,'At the time'],[5,'5 minutes before'],[10,'10 minutes before'],[15,'15 minutes before'],[30,'30 minutes before'],[60,'1 hour before'],[120,'2 hours before'],[1440,'1 day before'],[2880,'2 days before'],[10080,'1 week before']];
const BROWSER_TZ = (()=>{ try{ return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'; }catch(e){ return 'UTC'; } })();
const TZ_LIST = [...new Set(['Europe/Athens','Europe/Brussels','Europe/London','Europe/Paris','Europe/Berlin','Europe/Luxembourg','Europe/Lisbon','Asia/Nicosia','America/New_York','UTC',BROWSER_TZ])];

/* ---------- small utils ---------- */
function safeGet(k){ try{ return localStorage.getItem(k); }catch(e){ return null; } }
function safeSet(k,v){ try{ localStorage.setItem(k,v); }catch(e){} }
function safeDel(k){ try{ localStorage.removeItem(k); }catch(e){} }
function jget(k,def){ try{ const v=safeGet(k); return v?JSON.parse(v):def; }catch(e){ return def; } }
function jset(k,v){ safeSet(k, JSON.stringify(v)); }
function startOfDay(d){ return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }
function addDays(d,n){ return new Date(d.getFullYear(), d.getMonth(), d.getDate()+n); }
function key(d){ return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }
function fromKey(k){ const [y,m,d]=k.split('-').map(Number); return new Date(y,m-1,d); }
function keyAdd(k,n){ return key(addDays(fromKey(k),n)); }
function dow(d){ return (d.getDay()+6)%7; } // 0 = Monday
function dowKey(k){ return dow(fromKey(k)); }
function isWeekend(d){ return dow(d)>=5; }
function mondayOf(d){ return addDays(d,-dow(d)); }
function isoWeek(d){ const t=new Date(Date.UTC(d.getFullYear(),d.getMonth(),d.getDate())); const n=t.getUTCDay()||7; t.setUTCDate(t.getUTCDate()+4-n); const y0=new Date(Date.UTC(t.getUTCFullYear(),0,1)); return Math.ceil(((t-y0)/864e5+1)/7); }
function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function code(city){ const c=CITY_CODES[(city||'').trim().toLowerCase()]; return c || (city||'?'); }
function fmtLong(d){ return d.toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'}); }
function fmtShort(d){ return d.toLocaleDateString('en-GB',{weekday:'short',day:'numeric',month:'short'}); }
function fmtDM(d){ return d.getDate()+' '+MONTHS[d.getMonth()].slice(0,3); }
function fmtDMY(d){ return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+d.getFullYear(); }
function fmtWhen(iso){ if(!iso) return ''; const d=new Date(iso); return isNaN(d)?'':d.toLocaleString('en-GB',{day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}); }
function hm(ms){ const d=new Date(ms); return String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0'); }
function minToHM(m){ m=Math.round(m); return String(Math.floor(m/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0'); }
function hmToMin(s){ if(!s) return null; const m=/^(\d{1,2}):(\d{2})$/.exec(s.trim()); return m? (+m[1])*60+(+m[2]) : null; }
function fmtDur(min){ min=Math.round(min||0); if(min<60) return min+' min'; const h=Math.floor(min/60), m=min%60; return h+' h'+(m?' '+m+' min':''); }
function fmtH(min){ const h=(min||0)/60; return (Math.round(h*10)/10).toString().replace(/\.0$/,'')+' h'; }
function $(s,el=document){ return el.querySelector(s); }
function $$(s,el=document){ return [...el.querySelectorAll(s)]; }
function uid(p){ return (p||'')+(crypto.randomUUID ? crypto.randomUUID().replace(/-/g,'').slice(0,12) : Math.random().toString(36).slice(2,14)); }
function nowISO(){ return new Date().toISOString(); }
function clone(o){ return o==null ? o : JSON.parse(JSON.stringify(o)); }
function clamp(v,a,b){ return Math.max(a,Math.min(b,v)); }
function debounce(fn,ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; }
function obj(v){ return (v && typeof v==='object' && !Array.isArray(v)) ? v : {}; }   // PHP turns {} into []
function arr(v){ return Array.isArray(v) ? v : []; }
function download(name, text, type){
  const a=document.createElement('a'); a.href=URL.createObjectURL(text instanceof Blob?text:new Blob([text],{type})); a.download=name;
  document.body.appendChild(a); a.click(); setTimeout(()=>{ URL.revokeObjectURL(a.href); a.remove(); },500);
}
function contrastText(hex){
  const m=/^#?([0-9a-f]{6})$/i.exec(hex||''); if(!m) return '#fff';
  const n=parseInt(m[1],16), r=n>>16, g=(n>>8)&255, b=n&255;
  return (r*299+g*587+b*114)/1000 > 150 ? '#1d1f24' : '#fff';
}

/* ---------- time zones ----------
   Timed items store local wall-clock time ("2026-11-04T10:00") plus the IANA
   time zone they were entered in. The screen shows everything in the ACTIVE
   time zone (top-bar 🌍 menu). Internally all times are "display" ms: a
   timestamp shifted so this browser's local clock reads the active zone's
   wall time. Switching the active zone moves every item to its local time. */
let ACTIVE_TZ = BROWSER_TZ;
const _dtf = {};
function dtf(tz){
  return _dtf[tz] || (_dtf[tz] = new Intl.DateTimeFormat('en-US',{timeZone:tz,hourCycle:'h23',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit'}));
}
function validTz(tz){ try{ dtf(tz); return true; }catch(e){ return false; } }
const _offCache=new Map();
function tzOffsetMin(tz, ms){
  if(!tz || tz===BROWSER_TZ) return -new Date(ms).getTimezoneOffset();
  const ck=tz+'|'+Math.floor(ms/900000); const c=_offCache.get(ck); if(c!==undefined) return c;
  const v=tzOffsetMinRaw(tz,ms); if(_offCache.size>20000) _offCache.clear(); _offCache.set(ck,v); return v;
}
function tzOffsetMinRaw(tz, ms){
  const p={}; for(const x of dtf(tz).formatToParts(new Date(ms))) p[x.type]=x.value;
  const asUTC=Date.UTC(+p.year,+p.month-1,+p.day,+p.hour%24,+p.minute,+p.second);
  return Math.round((asUTC - (ms - ms%1000))/60000);
}
function parseLocal(local){ const m=/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?/.exec(local||''); return m ? {y:+m[1],mo:+m[2],d:+m[3],h:+(m[4]||0),mi:+(m[5]||0)} : null; }
function toDisp(t){ return ACTIVE_TZ===BROWSER_TZ ? t : t+(tzOffsetMin(ACTIVE_TZ,t)-tzOffsetMin(BROWSER_TZ,t))*60000; }
function fromDisp(d){
  if(ACTIVE_TZ===BROWSER_TZ) return d;
  let t=d-(tzOffsetMin(ACTIVE_TZ,d)-tzOffsetMin(BROWSER_TZ,d))*60000;
  t=d-(tzOffsetMin(ACTIVE_TZ,t)-tzOffsetMin(BROWSER_TZ,t))*60000; return t;
}
function NOW(){ return toDisp(Date.now()); }
function TODAY(){ return startOfDay(new Date(NOW())); }
function isoDay(iso){ return key(new Date(toDisp(Date.parse(iso)))); }
/* wall time in tz → display ms, and back */
function zonedToMs(local, tz){ return toDisp(zonedToTrue(local, tz)); }
function msToZoned(ms, tz){ return trueToZoned(fromDisp(ms), tz); }
/* convert a wall time from one zone to another */
function tzConvert(local, fromTz, toTz){ return (!local || fromTz===toTz) ? local : msToZoned(zonedToMs(local, fromTz), toTz); }
function tzOffsetLabel(tz, ms){ const o=tzOffsetMin(tz, ms==null?Date.now():ms); return 'UTC'+(o<0?'−':'+')+String(Math.floor(Math.abs(o)/60)).padStart(2,'0')+':'+String(Math.abs(o)%60).padStart(2,'0'); }
function zonedToTrue(local, tz){
  const p=parseLocal(local); if(!p) return NaN;
  if(!tz || tz===BROWSER_TZ || !validTz(tz)) return new Date(p.y,p.mo-1,p.d,p.h,p.mi).getTime();
  const guess=Date.UTC(p.y,p.mo-1,p.d,p.h,p.mi);
  let ms=guess-tzOffsetMin(tz,guess)*60000;
  const o2=tzOffsetMin(tz,ms); ms=guess-o2*60000;
  return ms;
}
function trueToZoned(ms, tz){
  if(!tz || tz===BROWSER_TZ || !validTz(tz)){ const d=new Date(ms); return key(d)+'T'+hm(ms); }
  const p={}; for(const x of dtf(tz).formatToParts(new Date(ms))) p[x.type]=x.value;
  return `${p.year}-${p.month}-${p.day}T${String(+p.hour%24).padStart(2,'0')}:${p.minute}`;
}
function localAddMin(local, min){ // wall-clock arithmetic (no zone)
  const p=parseLocal(local); const d=new Date(Date.UTC(p.y,p.mo-1,p.d,p.h,p.mi)+min*60000);
  return d.getUTCFullYear()+'-'+String(d.getUTCMonth()+1).padStart(2,'0')+'-'+String(d.getUTCDate()).padStart(2,'0')+'T'+String(d.getUTCHours()).padStart(2,'0')+':'+String(d.getUTCMinutes()).padStart(2,'0');
}
function localDiffMin(a,b){ const p=parseLocal(a), q=parseLocal(b); return (Date.UTC(q.y,q.mo-1,q.d,q.h,q.mi)-Date.UTC(p.y,p.mo-1,p.d,p.h,p.mi))/60000; }
function tzShort(tz){ if(!tz) return ''; const c=tz.split('/').pop().replace(/_/g,' '); return c; }

/* ---------- state ---------- */
let data = null;            // the calendar (normalized, private items merged in when unlocked)
let view = safeGet('wtc-view') || (innerWidth<600 ? 'agenda' : 'week');
let weekCompact = safeGet('wtc-weekc')==='1';
let cursor = startOfDay(new Date());
let editable = false;
let session = null;         // {user, role}
let token = '';
let rev = 0;
let lastSaved = {by:'', saved:''};
let selected = new Set();   // selected day keys (bulk edit of work status)
let selTasks = new Set();   // selected task ids (bulk task actions)
let selItem = null;         // last clicked item {kind,id,occ}
let lastClicked = null;
let openDay = null;
let saveTimer = null;
let dirty = false, changeSeq = 0, saving = false, conflict = null, netError = false;
let base = null;            // last data as on the server (for merging)
let basePriv = null;        // last decrypted private part (for merging)
let mergeNote = null;
let privKey = null;         // CryptoKey for the Personal (private) part, when unlocked
let privLocked = false;     // a private block exists but is not unlocked here
let archive = null;         // archive file opened for this session (read-only)
let offlineMode = false;
let sizeWarn = false;
let prefs = {};             // per browser + user preferences
let filt = null;            // view filters (per browser + user)
let tpTab = 'today';        // tasks panel list
let undoStack = [], redoStack = [], lastSnap = null, lastSnapAt = 0, lastSnapLabel = '';

const DEFAULT_SETTINGS = {
  workStart:'08:30', midday:'13:00', workEnd:'17:30', dayHalves:1, workDays:[0,1,2,3,4], homeTz:'Europe/Athens',
  defaultEventRem:[{offsetMin:15,channel:'popup'}], defaultDueRem:[{offsetMin:1440,channel:'popup'}], defaultBlockRem:[{offsetMin:5,channel:'popup'}],
  defaultEventMin:60, defaultBlockMin:60, showBusy:true, allDayRemTime:'09:00', travelReminders:true, archiveMonths:12
};
const DEFAULT_PREFS = {
  hourPx:44, density:'comfortable', colorBy:'project', hideWeekends:false, showLegend:true, sidePanel:true, tasksPanel:true,
  desktop:false, sound:true, quietStart:'22:00', quietEnd:'07:30', digestTime:'08:30', digest:true,
  reviewDay:4, reviewTime:'16:00', review:true, eod:false, eodTime:'18:00', snooze:[5,10,30,60,'tomorrow'],
  activeTz:'', openDetails:'tab', openEditors:'tab', offlineCopy:false, tpSort:'due', tpGroup:'none', savedFilters:[]
};
const DEFAULT_FILTER = {hiddenCals:[], projects:[], showDone:true, showCancelled:true, showBlocks:true, showDue:true, showFlights:true, showStatus:true, tag:''};
function prefKey(){ return 'wtc-prefs:'+(session?session.user.toLowerCase():'-'); }
function loadPrefs(){ prefs=Object.assign({},DEFAULT_PREFS,jget(prefKey(),{})); ACTIVE_TZ=(prefs.activeTz&&validTz(prefs.activeTz))?prefs.activeTz:BROWSER_TZ; cursor=TODAY(); filt=Object.assign({},DEFAULT_FILTER,jget('wtc-filter:'+(session?session.user.toLowerCase():'-'),{})); applyPrefsCss(); }
function savePrefs(){ jset(prefKey(),prefs); applyPrefsCss(); }
function saveFilter(){ jset('wtc-filter:'+(session?session.user.toLowerCase():'-'),filt); }
function applyPrefsCss(){
  document.documentElement.style.setProperty('--hour', (prefs.density==='compact'?Math.round(prefs.hourPx*.75):prefs.hourPx)+'px');
  document.body.classList.toggle('noside', !prefs.sidePanel);
  document.body.classList.toggle('notasks', !prefs.tasksPanel);
}
function S(){ return data.settings; }

/* ============================================================
   Data model v2, migration, private (Personal) encryption
   ============================================================ */
function defaultCalendars(){
  return [{id:'work',name:'Work',color:'#3b5bdb',private:false,order:0},{id:'personal',name:'Personal',color:'#2f9e44',private:true,order:1}];
}
function seedTemplates(){
  return [{id:'tpl-mission',name:'Brussels mission (3 days)',created:nowISO(),items:[
    {kind:'flight',offsetDays:0,from:'Athens',to:'Brussels',time:'morning'},
    {kind:'event',offsetDays:0,title:'Working group meeting',startTime:'14:00',endTime:'17:00',tz:'Europe/Brussels',category:'meeting',location:'Brussels'},
    {kind:'flight',offsetDays:2,from:'Brussels',to:'Athens',time:'evening'},
    {kind:'task',offsetDays:5,title:'Mission expense claim',estimateMin:30,priority:2}
  ]}];
}
/* Turn whatever the server sent (v1 or v2) into the v2 shape. Never touches
   days / monthNotes / owner beyond what v1 already did. */
function normalize(raw){
  const d = (raw && typeof raw==='object') ? JSON.parse(JSON.stringify(raw)) : {};
  d.version = 1; d.owner = d.owner || '';
  d.days = obj(d.days); d.monthNotes = obj(d.monthNotes);
  delete d.accounts;
  const wasV1 = d.schema !== SCHEMA;
  d.schema = SCHEMA;
  d.settings = Object.assign({}, DEFAULT_SETTINGS, obj(d.settings));
  for(const e of Object.values(d.days)) for(const f of arr(e.flights)) if(f.time==='evening') f.time='noon'; // v1 "evening" flights → Noon
  if(!obj(raw&&raw.settings).dayHalves){ // working day = morning 08:30–13:00 + evening 13:00–17:30
    if(d.settings.workStart==='09:00' && d.settings.workEnd==='18:00'){ d.settings.workStart='08:30'; d.settings.workEnd='17:30'; }
    d.settings.midday=d.settings.midday||'13:00'; d.settings.dayHalves=1;
  }
  d.calendars = arr(d.calendars).length ? arr(d.calendars) : defaultCalendars();
  d.projects = arr(d.projects); d.events = arr(d.events); d.tasks = arr(d.tasks);
  d.templates = arr(d.templates); d.trash = arr(d.trash);
  if(wasV1 && !d.templates.length) d.templates = seedTemplates();
  if(!d.private || typeof d.private!=='object' || Array.isArray(d.private)) d.private = null;
  d.busy = arr(d.busy);
  for(const e of d.events){ e.exceptions = obj(e.exceptions); e.reminders = arr(e.reminders); e.tags = arr(e.tags); e.attendees=arr(e.attendees); e.links=arr(e.links); e.linkedTaskIds=arr(e.linkedTaskIds); }
  for(const t of d.tasks){ t.blocks = arr(t.blocks); t.checklist = arr(t.checklist); t.reminders = arr(t.reminders); t.tags = arr(t.tags); t.dependsOn = arr(t.dependsOn); }
  // purge trash older than 30 days
  const cut = Date.now()-30*864e5;
  d.trash = d.trash.filter(x=>Date.parse(x.deletedAt||0)>cut);
  return {d, wasV1};
}

/* ---------- lookups ---------- */
function cal(id){ return data.calendars.find(c=>c.id===id) || data.calendars[0]; }
function proj(id){ return id ? data.projects.find(p=>p.id===id) : null; }
function findItem(id){ return data.events.find(x=>x.id===id) || data.tasks.find(x=>x.id===id) || null; }
function task(id){ return data.tasks.find(x=>x.id===id); }
function ev(id){ return data.events.find(x=>x.id===id); }
function isPrivCal(id){ const c=data.calendars.find(c=>c.id===id); return !!(c && c.private); }
function isPrivate(it){ return !!(it && (it.private || isPrivCal(it.calendarId))); }
function itemColor(it){
  if(prefs.colorBy==='priority' && it.type==='task') return ({1:'#c92a2a',2:'#e8590c',3:'#3b5bdb',4:'#868e96'})[it.priority||3];
  if(prefs.colorBy!=='calendar'){ const p=proj(it.projectId); if(p && p.color) return p.color; }
  return cal(it.calendarId).color;
}
function projectsOf(calId){ return data.projects.filter(p=>p.calendarId===calId); }
function locTz(k){ const l=locationOf(k); const t=l && CITY_TZ[l.name.trim().toLowerCase()]; return t || S().homeTz || BROWSER_TZ; }
function touch(it){ it.updated = nowISO(); if(!it.created) it.created = it.updated; return it; }

/* Location: explicit value, otherwise inferred from the destination of the latest flight on/before that day */
function day(k){ return data.days[k] || {}; }
function dayMut(k){ return data.days[k] || (data.days[k] = {}); }
function prune(k){
  const e = data.days[k]; if(!e) return;
  if(e.flights && !e.flights.length) delete e.flights;
  for(const f of ['am','pm','location','comment']) if(!e[f]) delete e[f];
  if(!Object.keys(e).length) delete data.days[k];
}
let _flightKeys = null;
function flightKeys(){ return _flightKeys || (_flightKeys = Object.keys(data.days).filter(x=>data.days[x].flights && data.days[x].flights.length).sort()); }
function invalidateCaches(){ _flightKeys=null; _occCache.clear(); }
function locationOf(k){
  const e = day(k);
  if(e.location) return {name:e.location, auto:false};
  const all = flightKeys();
  let lo=0, hi=all.length-1, best=-1;
  while(lo<=hi){ const m=(lo+hi)>>1; if(all[m]<=k){ best=m; lo=m+1; } else hi=m-1; }
  if(best<0){
    if(!all.length) return null;
    const from = data.days[all[0]].flights[0].from;
    return from ? {name:from, auto:true} : null;
  }
  const fl = data.days[all[best]].flights;
  const to = fl[fl.length-1].to;
  return to ? {name:to, auto:true} : null;
}
/* Leave / sick / holiday per half day: morning = work start → midday, evening = midday → work end */
const OFF_STATUSES=['leave','sick','holiday'];
function halfBounds(){ const s=S(); return {ws:hmToMin(s.workStart)??510, mid:hmToMin(s.midday)??780, we:hmToMin(s.workEnd)??1050}; }
function offIntervals(k){
  const e=day(k), d0=fromKey(k).getTime(), {ws,mid,we}=halfBounds(), out=[];
  if(OFF_STATUSES.includes(e.am)) out.push({half:'morning', st:e.am, a:ws, b:mid, s:d0+ws*60000, e:d0+mid*60000});
  if(OFF_STATUSES.includes(e.pm)) out.push({half:'evening', st:e.pm, a:mid, b:we, s:d0+mid*60000, e:d0+we*60000});
  return out;
}
function offLabel(x){ return `${STATUSES[x.st].label} (${x.half}, ${minToHM(x.a)}–${minToHM(x.b)})`; }
function dayOff(k){ return offIntervals(k).length===2; }               // whole working day off
function offOverlap(k,s,e){ return offIntervals(k).filter(x=>s<x.e && x.s<e); }
function offAt(ms){ return offIntervals(key(new Date(ms))).find(x=>ms>=x.s && ms<x.e) || null; }
function flightWindows(k){
  return (day(k).flights||[]).map((f,i)=>{
    const {a,b}=flightMins(f), tz=flightTz(f);
    const s=zonedToMs(k+'T'+minToHM(a),tz), e=b>=1440?zonedToMs(keyAdd(k,1)+'T00:00',tz):zonedToMs(k+'T'+minToHM(b),tz);
    return {s, e, f, k, tz, i};
  });
}

/* ============================================================
   Private (Personal) items: AES-GCM, key from a passphrase (PBKDF2)
   ============================================================ */
const PRIV_COLS = ['projects','events','tasks','templates','trash'];
const b64 = buf => btoa(String.fromCharCode(...new Uint8Array(buf)));
const unb64 = s => Uint8Array.from(atob(s), c=>c.charCodeAt(0));
async function deriveKey(pass, salt, iter){
  const base = await crypto.subtle.importKey('raw', new TextEncoder().encode(pass), 'PBKDF2', false, ['deriveKey']);
  return crypto.subtle.deriveKey({name:'PBKDF2', salt, iterations:iter, hash:'SHA-256'}, base, {name:'AES-GCM', length:256}, false, ['encrypt','decrypt']);
}
async function encPriv(key, payload, salt, iter){
  const iv = crypto.getRandomValues(new Uint8Array(12));
  const ct = await crypto.subtle.encrypt({name:'AES-GCM', iv}, key, new TextEncoder().encode(JSON.stringify(payload)));
  return {v:1, salt, iter, iv:b64(iv), ct:b64(ct)};
}
async function decPriv(key, blob){
  const pt = await crypto.subtle.decrypt({name:'AES-GCM', iv:unb64(blob.iv)}, key, unb64(blob.ct));
  return JSON.parse(new TextDecoder().decode(pt));
}
/* remember the (non-extractable) key on this device */
function idb(){ return new Promise((res,rej)=>{ const r=indexedDB.open('wtc-keys',1); r.onupgradeneeded=()=>r.result.createObjectStore('k'); r.onsuccess=()=>res(r.result); r.onerror=()=>rej(r.error); }); }
async function keyStore(id, val){ try{ const db=await idb(); await new Promise((res,rej)=>{ const tx=db.transaction('k','readwrite'); val===undefined?tx.objectStore('k').delete(id):tx.objectStore('k').put(val,id); tx.oncomplete=res; tx.onerror=()=>rej(tx.error); }); }catch(e){} }
async function keyLoad(id){ try{ const db=await idb(); return await new Promise((res)=>{ const tx=db.transaction('k'); const r=tx.objectStore('k').get(id); r.onsuccess=()=>res(r.result||null); r.onerror=()=>res(null); }); }catch(e){ return null; } }
function keyId(){ return 'k:'+(session?session.user.toLowerCase():'-')+':'+(data.private?data.private.salt:''); }

function emptyPriv(){ const o={}; PRIV_COLS.forEach(c=>o[c]=[]); return o; }
function privPart(){ // the private items currently in memory
  const o=emptyPriv();
  o.projects=data.projects.filter(p=>isPrivCal(p.calendarId)||p.private);
  o.events=data.events.filter(isPrivate); o.tasks=data.tasks.filter(isPrivate);
  o.templates=data.templates.filter(t=>t.private); o.trash=data.trash.filter(x=>isPrivate(x.item||{}));
  return o;
}
function mergePriv(p){
  for(const c of PRIV_COLS){
    const have=new Set(data[c].map(x=>x.id));
    for(const x of arr(p[c])) if(!have.has(x.id)) data[c].push(x);
  }
  normalizeItems();
}
function normalizeItems(){
  for(const e of data.events){ e.exceptions=obj(e.exceptions); e.reminders=arr(e.reminders); e.tags=arr(e.tags); e.attendees=arr(e.attendees); e.links=arr(e.links); e.linkedTaskIds=arr(e.linkedTaskIds); }
  for(const t of data.tasks){ t.blocks=arr(t.blocks); t.checklist=arr(t.checklist); t.reminders=arr(t.reminders); t.tags=arr(t.tags); t.dependsOn=arr(t.dependsOn); }
}
async function tryAutoUnlock(){
  if(!data.private){ privLocked=false; return; }
  privLocked = true;
  const k = await keyLoad(keyId());
  if(k){ try{ const p=await decPriv(k, data.private); privKey=k; privLocked=false; basePriv=clone(p); mergePriv(p); }catch(e){ keyStore(keyId()); } }
}
async function unlockPriv(pass, remember){
  const blob=data.private;
  const k=await deriveKey(pass, unb64(blob.salt), blob.iter||250000);
  let p; try{ p=await decPriv(k, blob); }catch(e){ throw new Error('That passphrase is not correct.'); }
  privKey=k; privLocked=false; basePriv=clone(p); mergePriv(p);
  if(remember) await keyStore(keyId(), k);
  invalidateCaches();
}
async function setPassphrase(pass, remember){
  const salt=crypto.getRandomValues(new Uint8Array(16));
  const k=await deriveKey(pass, salt, 250000);
  if(data.private && privLocked) throw new Error('Unlock Personal items first.');
  const old=keyId();
  privKey=k; privLocked=false;
  data.private = await encPriv(k, privPart(), b64(salt), 250000);
  await keyStore(old);
  if(remember) await keyStore(keyId(), k);
}
async function lockPriv(){
  await keyStore(keyId());
  if(!data.private) return;
  // drop private items from memory
  for(const c of PRIV_COLS){
    if(c==='projects') data.projects=data.projects.filter(p=>!(isPrivCal(p.calendarId)||p.private));
    else if(c==='templates') data.templates=data.templates.filter(t=>!t.private);
    else if(c==='trash') data.trash=data.trash.filter(x=>!isPrivate(x.item||{}));
    else data[c]=data[c].filter(x=>!isPrivate(x));
  }
  privKey=null; privLocked=true; invalidateCaches();
}
function needsPrivSetup(){ return !data.private && !privKey; }

/* What goes to the server: public items in the clear, private ones encrypted */
async function serialize(){
  const d = clone(data);
  delete d.busy;
  if(privKey){
    const p = privPart();
    const ids = new Set([...p.events,...p.tasks,...p.projects,...p.templates].map(x=>x.id));
    d.projects=d.projects.filter(x=>!ids.has(x.id)); d.events=d.events.filter(x=>!ids.has(x.id));
    d.tasks=d.tasks.filter(x=>!ids.has(x.id)); d.templates=d.templates.filter(x=>!ids.has(x.id));
    d.trash=d.trash.filter(x=>!isPrivate(x.item||{}));
    const salt = data.private ? data.private.salt : b64(crypto.getRandomValues(new Uint8Array(16)));
    d.private = await encPriv(privKey, p, salt, data.private?data.private.iter:250000);
    data.private = d.private;
    d.busy = S().showBusy ? computeBusy(p) : [];
  } else {
    // locked (or never set up): keep the encrypted block and busy list exactly as loaded
    d.busy = arr(data.busy);
  }
  return d;
}
function computeBusy(p){
  const from=NOW()-60*864e5, to=NOW()+400*864e5, out=[];
  for(const e of p.events){ if(e.showAs==='free' || e.status==='cancelled') continue; for(const o of expandEvent(e, from, to)){ if(!o.cancelled) out.push(o.allDay?{d:o.dateKey,e2:o.endKey}:{s:fromDisp(o.s),e:fromDisp(o.e)}); } }
  for(const t of p.tasks) for(const b of t.blocks) { const s=zonedToMs(b.start,b.tz), e=zonedToMs(b.end,b.tz); if(e>from && s<to) out.push({s:fromDisp(s),e:fromDisp(e)}); }
  return out.slice(0,3000);
}

/* ============================================================
   Recurrence (RFC 5545 subset) and occurrences
   rrule: {freq:'daily'|'weekly'|'monthly'|'yearly', interval, byDay:[0..6 Mon=0],
           monthMode:'date'|'nth', nth:1..4|-1, count, until:'YYYY-MM-DD'}
   ============================================================ */
function ukey(t){ const d=new Date(t); return d.getUTCFullYear()+'-'+String(d.getUTCMonth()+1).padStart(2,'0')+'-'+String(d.getUTCDate()).padStart(2,'0'); }
function uparse(k){ const [y,m,d]=k.split('-').map(Number); return Date.UTC(y,m-1,d); }
function nthWeekday(y, m, wd, n){ // wd 0=Mon; n 1..5 or -1
  if(n>0){ const first=new Date(Date.UTC(y,m,1)); const fw=(first.getUTCDay()+6)%7; const day=1+((wd-fw+7)%7)+(n-1)*7; const dim=new Date(Date.UTC(y,m+1,0)).getUTCDate(); return day<=dim?Date.UTC(y,m,day):null; }
  const last=new Date(Date.UTC(y,m+1,0)); const lw=(last.getUTCDay()+6)%7; return Date.UTC(y,m,last.getUTCDate()-((lw-wd+7)%7));
}
/* Dates (YYYY-MM-DD) of a series, from its first date, up to toKey (inclusive) */
function seriesDates(startKey, rr, toKey, limit=4000){
  const out=[]; if(!rr) return [startKey];
  const iv=Math.max(1,rr.interval||1), s=uparse(startKey), until=rr.until||null, maxN=rr.count||Infinity;
  const end = until && until<toKey ? until : toKey;
  const push=k=>{ if(k<startKey) return true; if(k>end || out.length>=maxN) return false; out.push(k); return out.length<limit; };
  const sd=new Date(s), sy=sd.getUTCFullYear(), sm=sd.getUTCMonth(), sdd=sd.getUTCDate(), swd=(sd.getUTCDay()+6)%7;
  if(rr.freq==='daily'){ for(let t=s;;t+=iv*864e5){ if(!push(ukey(t))) break; } }
  else if(rr.freq==='weekly'){
    const days=(rr.byDay&&rr.byDay.length?[...rr.byDay]:[swd]).sort((a,b)=>a-b);
    const mon=s-swd*864e5;
    outer: for(let w=0;;w+=iv){ for(const d of days){ const t=mon+(w*7+d)*864e5; if(t<s) continue; if(!push(ukey(t))) break outer; } if(mon+w*7*864e5>uparse(end)) break; }
  }
  else if(rr.freq==='monthly'){
    const nth = rr.nth || (Math.ceil(sdd/7)>4?-1:Math.ceil(sdd/7));
    for(let k=0;k<2400;k+=iv){
      const y=sy+Math.floor((sm+k)/12), m=(sm+k)%12;
      let t; if(rr.monthMode==='nth') t=nthWeekday(y,m,swd,nth); else { t=Date.UTC(y,m,sdd); if(new Date(t).getUTCMonth()!==m) t=null; }
      if(t===null) continue; const kk=ukey(t); if(kk>end) break; if(!push(kk)) break;
    }
  }
  else if(rr.freq==='yearly'){
    for(let k=0;k<200;k+=iv){ const t=Date.UTC(sy+k,sm,sdd); if(new Date(t).getUTCMonth()!==sm) continue; const kk=ukey(t); if(kk>end) break; if(!push(kk)) break; }
  }
  return out;
}
function nextSeriesDate(startKey, rr, afterKey){
  const ds=seriesDates(startKey, rr, keyAdd(afterKey, 800), 5000);
  return ds.find(k=>k>afterKey) || null;
}
function rrText(rr){
  if(!rr) return '';
  const iv=rr.interval>1?rr.interval:0;
  let t='';
  if(rr.freq==='daily') t=iv?`Every ${iv} days`:'Daily';
  if(rr.freq==='weekly'){ const d=(rr.byDay||[]).slice().sort(); t=(d.join()==='0,1,2,3,4'&&!iv)?'Every weekday':(iv?`Every ${iv} weeks`:'Weekly')+(d.length?' on '+d.map(x=>DOW3[x]).join(', '):''); }
  if(rr.freq==='monthly') t=(iv?`Every ${iv} months`:'Monthly')+(rr.monthMode==='nth'?' ('+(rr.nth===-1?'last':['','1st','2nd','3rd','4th'][rr.nth||1])+' weekday)':'');
  if(rr.freq==='yearly') t=iv?`Every ${iv} years`:'Yearly';
  if(rr.count) t+=`, ${rr.count} times`; if(rr.until) t+=`, until ${fmtDMY(fromKey(rr.until))}`;
  return t;
}

/* Expand one event into occurrences overlapping [fromMs, toMs) */
function expandEvent(e, fromMs, toMs){
  const out=[];
  if(e.allDay){
    const sk=e.start.slice(0,10), ek=(e.end||e.start).slice(0,10), span=Math.round((uparse(ek)-uparse(sk))/864e5);
    const fromK=key(new Date(fromMs-864e5*(span+1))), toK=key(new Date(toMs));
    const dates = e.rrule ? seriesDates(sk, e.rrule, toK) : [sk];
    for(const dk of dates){
      const ex=e.exceptions[dk]; if(ex && ex.deleted) continue;
      let s=dk, en=keyAdd(dk,span);
      if(ex && ex.start){ s=ex.start.slice(0,10); en=(ex.end||ex.start).slice(0,10); }
      if(en<fromK) continue; if(s>toK) continue;
      const sMs=fromKey(s).getTime(), eMs=addDays(fromKey(en),1).getTime();
      if(eMs<=fromMs || sMs>=toMs) continue;
      out.push({kind:'event', item:e, id:e.id, occ:dk, allDay:true, dateKey:s, endKey:en, s:sMs, e:eMs, title:(ex&&ex.title)||e.title, cancelled:e.status==='cancelled'||!!(ex&&ex.status==='cancelled'), ex:!!ex});
    }
    return out;
  }
  const dur = Math.max(5, localDiffMin(e.start, e.end||localAddMin(e.start,60)));
  const tz=e.tz||BROWSER_TZ, time=e.start.slice(11,16);
  const toK=ukey(toMs+2*864e5);
  const dates = e.rrule ? seriesDates(e.start.slice(0,10), e.rrule, toK) : [e.start.slice(0,10)];
  for(const dk of dates){
    const ex=e.exceptions[dk]; if(ex && ex.deleted) continue;
    let sl=dk+'T'+time, el=localAddMin(sl,dur);
    if(ex && ex.start){ sl=ex.start; el=ex.end||localAddMin(sl,dur); }
    const s=zonedToMs(sl,tz), en=zonedToMs(el,tz);
    if(en<=fromMs || s>=toMs) continue;
    out.push({kind:'event', item:e, id:e.id, occ:dk, allDay:false, s, e:en, title:(ex&&ex.title)||e.title, location:(ex&&ex.location)||e.location, cancelled:e.status==='cancelled'||!!(ex&&ex.status==='cancelled'), ex:!!ex});
  }
  return out;
}
function blockOccs(t, fromMs, toMs){
  const out=[];
  for(const b of t.blocks){
    const s=zonedToMs(b.start,b.tz||BROWSER_TZ), e=zonedToMs(b.end,b.tz||BROWSER_TZ);
    if(e<=fromMs || s>=toMs) continue;
    out.push({kind:'block', item:t, id:t.id, block:b, occ:b.id, allDay:false, s, e, title:t.title, done:!!b.done||t.status==='done', missed:!b.done && t.status!=='done' && t.status!=='cancelled' && e<NOW()});
  }
  return out;
}
function dueDisp(t){ // {k, time} of the due date in the active zone
  if(!t.dueDate) return null; if(!t.dueTime) return {k:t.dueDate, time:null};
  const ms=dueMs(t); return {k:key(new Date(ms)), time:hm(ms)};
}
function dueMs(t){ if(!t.dueDate) return null; return zonedToMs(t.dueDate+'T'+(t.dueTime||'23:59'), t.tz||S().homeTz||BROWSER_TZ); }
function isOpen(t){ return t.status!=='done' && t.status!=='cancelled'; }
function isOverdue(t){ const d=dueMs(t); return isOpen(t) && d!==null && d<NOW(); }
function plannedMin(t){ return t.blocks.reduce((a,b)=>a+Math.max(0,localDiffMin(b.start,b.end)),0); }
function spentMin(t){ return t.blocks.reduce((a,b)=>a+(b.done?(b.actualMin!=null?b.actualMin:localDiffMin(b.start,b.end)):0),0); }
function futurePlannedMin(t){ return t.blocks.reduce((a,b)=>a+(!b.done && zonedToMs(b.end,b.tz)>NOW()?localDiffMin(b.start,b.end):0),0); }
function remainingMin(t){ return Math.max(0,(t.estimateMin||0)-spentMin(t)); }
function underScheduled(t){
  if(!isOpen(t) || !t.estimateMin || !t.dueDate) return false;
  const d=dueMs(t); if(d-NOW()>3*864e5) return false;
  return spentMin(t)+futurePlannedMin(t) < t.estimateMin;
}
function blockedBy(t){ return t.dependsOn.map(task).filter(x=>x && isOpen(x)); }

/* ---------- filters ---------- */
function passFilter(it){
  if(filt.hiddenCals.includes(it.calendarId)) return false;
  if(filt.projects.length){ const tokn = it.projectId || ('inbox:'+it.calendarId); if(!filt.projects.includes(tokn)){ const p=proj(it.projectId); if(!(p && p.parentId && filt.projects.includes(p.parentId))) return false; } }
  if(filt.tag && !(it.tags||[]).some(t=>t.toLowerCase()===filt.tag.toLowerCase())) return false;
  return true;
}
/* All occurrences in a time range, filtered for display */
const _occCache = new Map();
function occurrences(fromMs, toMs, opts={}){
  const ck = fromMs+'|'+toMs+'|'+(opts.all?1:0)+'|'+changeSeq+'|'+JSON.stringify(filt)+'|'+(privLocked?1:0);
  if(!opts.noCache && _occCache.has(ck)) return _occCache.get(ck);
  const out=[];
  for(const e of data.events){
    if(!opts.all && !passFilter(e)) continue;
    for(const o of expandEvent(e, fromMs, toMs)){ if(o.cancelled && !opts.all && !filt.showCancelled) continue; out.push(o); }
  }
  if(opts.all || filt.showBlocks) for(const t of data.tasks){
    if(!opts.all && !passFilter(t)) continue;
    if(!opts.all && !filt.showDone && !isOpen(t)) continue;
    out.push(...blockOccs(t, fromMs, toMs));
  }
  if(privLocked && !opts.all && !filt.hiddenCals.some(isPrivCal)){
    for(const b of arr(data.busy)){
      if(b.d){ const s=fromKey(b.d).getTime(), e=addDays(fromKey(b.e2||b.d),1).getTime(); if(e>fromMs && s<toMs) out.push({kind:'busy', allDay:true, dateKey:b.d, endKey:b.e2||b.d, s, e, title:'Busy', id:'busy'}); }
      else { const bs=toDisp(b.s), be=toDisp(b.e); if(be>fromMs && bs<toMs) out.push({kind:'busy', allDay:false, s:bs, e:be, title:'Busy', id:'busy'}); }
    }
  }
  out.sort((a,b)=>a.s-b.s || (b.e-b.s)-(a.e-a.s));
  if(_occCache.size>60) _occCache.clear();
  _occCache.set(ck,out);
  return out;
}
function dueTasksOn(k, opts={}){
  return data.tasks.filter(t=>t.dueDate && dueDisp(t).k===k && (opts.all || passFilter(t)) && (opts.all || filt.showDone || isOpen(t)));
}
/* Conflicts: overlapping busy items; items during flights; work items on leave/sick days */
function conflictsIn(occs){
  const busy=occs.filter(o=>!o.allDay && o.kind!=='busy' && !o.cancelled && !o.done && !(o.kind==='event' && ['free'].includes(o.item.showAs)));
  const bad=new Map();
  const add=(o,why)=>{ const k=o.id+'|'+o.occ; if(!bad.has(k)) bad.set(k,[]); bad.get(k).push(why); };
  for(let i=0;i<busy.length;i++) for(let j=i+1;j<busy.length;j++){
    const a=busy[i], b=busy[j]; if(b.s>=a.e) break;
    if(b.s<a.e && a.s<b.e){ add(a,'Overlaps “'+b.title+'”'); add(b,'Overlaps “'+a.title+'”'); }
  }
  for(const o of busy){
    const k=key(new Date(o.s));
    for(const w of [...flightWindows(keyAdd(k,-1)),...flightWindows(k),...flightWindows(keyAdd(k,1))]) if(o.s<w.e && w.s<o.e) add(o,'During flight '+code(w.f.from)+'→'+code(w.f.to));
    if(o.item && o.item.calendarId==='work') for(const x of offOverlap(k,o.s,o.e)) add(o,'During '+offLabel(x));
  }
  return bad;
}

/* ============================================================
   Server: load, save (auto), merge on conflict, sign-in
   ============================================================ */
async function api(action, body, query){
  const opt = {method: body===undefined ? 'GET' : 'POST', credentials:'same-origin', cache:'no-store', headers:{'Accept':'application/json'}};
  if(body!==undefined){ opt.headers['Content-Type']='application/json'; opt.headers['X-WTC-Token']=token; opt.body=JSON.stringify(body); }
  let r;
  try{ r = await fetch(API+'?a='+action+(query||''), opt); }
  catch(e){ const err=new Error('Cannot reach the server. Check your internet connection.'); err.status=0; throw err; }
  let j=null; try{ j=await r.json(); }catch(e){}
  if(!r.ok){
    const err=new Error((j && j.error) || ('The server answered with an error ('+r.status+'). Is PHP enabled on this server?'));
    err.status=r.status; err.body=j;
    if(r.status===401 && !['login','me'].includes(action)) sessionLost('Your session has ended. Please sign in again.');
    throw err;
  }
  if(j===null) throw new Error('The server did not answer correctly. Is PHP enabled on this server?');
  return j;
}

/* ---------- saving ---------- */
function snapshotState(){ return JSON.stringify({days:data.days, monthNotes:data.monthNotes, owner:data.owner, settings:data.settings, calendars:data.calendars, projects:data.projects, events:data.events, tasks:data.tasks, templates:data.templates, trash:data.trash}); }
function restoreState(s){ const o=JSON.parse(s); Object.assign(data,o); normalizeItems(); }
/* Call after every change. label groups rapid changes (typing) into one undo step. */
function changed(label){
  if(!editable) return;
  const now=Date.now();
  if(lastSnap && !(label && label===lastSnapLabel && now-lastSnapAt<1500)){
    undoStack.push(lastSnap); if(undoStack.length>50) undoStack.shift(); redoStack=[];
  }
  lastSnapLabel=label||''; lastSnapAt=now;
  lastSnap = snapshotState();
  data.updated = nowISO();
  invalidateCaches();
  dirty = true; changeSeq++;
  if(!conflict) setSave('Saving…');
  clearTimeout(saveTimer); saveTimer = setTimeout(saveNow, 700);
}
function undo(){ if(!editable || !undoStack.length) return toast('Nothing to undo'); redoStack.push(snapshotState()); restoreState(undoStack.pop()); lastSnap=snapshotState(); lastSnapLabel=''; invalidateCaches(); dirty=true; changeSeq++; clearTimeout(saveTimer); saveTimer=setTimeout(saveNow,700); setSave('Saving…'); renderAll(); toast('Undone'); }
function redo(){ if(!editable || !redoStack.length) return toast('Nothing to redo'); undoStack.push(snapshotState()); restoreState(redoStack.pop()); lastSnap=snapshotState(); lastSnapLabel=''; invalidateCaches(); dirty=true; changeSeq++; clearTimeout(saveTimer); saveTimer=setTimeout(saveNow,700); setSave('Saving…'); renderAll(); toast('Redone'); }

async function saveNow(){
  saveTimer = null;
  if(!editable || !dirty || conflict || !session || offlineMode) return;
  if(saving){ saveTimer = setTimeout(saveNow, 400); return; }
  saving = true;
  const seq = changeSeq;
  try{
    const out = await serialize();
    const size = JSON.stringify(out).length;
    sizeWarn = size > 2400000;
    let j;
    try{ j = await api('save', {rev, data:out}); }
    catch(e){
      if(e.status!==409) throw e;
      // someone else saved first: merge their changes with ours, then save again
      const merged = await mergeWithServer();
      if(!merged){ conflict = e.body; setSave('Not saved','err'); return; }
      j = await api('save', {rev, data: await serialize()});
    }
    rev = j.rev; lastSaved = {by:session.user, saved:j.saved};
    try{ _bc && _bc.postMessage({type:'saved', rev}); }catch(e){}
    base = await serialize(); basePriv = privKey ? clone(privPart()) : basePriv;
    cacheOffline();
    netError = false;
    if(seq===changeSeq){ dirty=false; setSave('Saved ✓','ok'); }
    else saveTimer = setTimeout(saveNow, 300);
  }catch(e){
    if(e.status===409){ conflict = e.body; setSave('Not saved','err'); }
    else if(e.status===401){ /* sign-in screen shown; save is retried after signing in */ }
    else if(e.status===403){ setSave('Not saved','err'); alert(e.message); }
    else if(e.status===413){ setSave('Not saved','err'); alert('The calendar has become too large to save (over 3 MB). Use ⚙ Settings → Data → Archive to move old items out.'); }
    else { netError = true; setSave('Not saved · retrying…','err'); saveTimer = setTimeout(saveNow, 5000); }
  }finally{ saving=false; renderBanner(); }
}
function setSave(t,cls){ const s=$('#saveState'); if(s){ s.textContent=t; s.className=cls||''; } }

/* 3-way merge: base (what we loaded) vs mine (now) vs theirs (server). */
const MERGE_COLS = ['calendars','projects','events','tasks','templates','trash'];
function mergeCollections(b, m, t, conflicts, label){
  const bm=new Map(arr(b).map(x=>[x.id,x])), mm=new Map(arr(m).map(x=>[x.id,x])), tm=new Map(arr(t).map(x=>[x.id,x]));
  const ids=new Set([...mm.keys(),...tm.keys()]); const out=[];
  const same=(x,y)=>JSON.stringify(x)===JSON.stringify(y);
  for(const id of ids){
    const bx=bm.get(id), mx=mm.get(id), tx=tm.get(id);
    if(same(mx,bx)){ if(tx) out.push(tx); continue; }          // I didn't touch it: take theirs (or their deletion)
    if(same(tx,bx)){ if(mx) out.push(mx); continue; }          // they didn't touch it: take mine
    if(!mx){ out.push(tx); continue; }                          // I deleted, they changed: keep theirs
    if(!tx){ out.push(mx); continue; }                          // they deleted, I changed: keep mine
    const keepMine = (mx.updated||'') >= (tx.updated||'');
    out.push(keepMine?mx:tx);
    if(!same(mx,tx)) conflicts.push((label||'')+(mx.title||mx.name||id)+' — kept '+(keepMine?'your':'their')+' version');
  }
  // keep order of mine, new ones from theirs appended
  const order=new Map(arr(m).map((x,i)=>[x.id,i]));
  return out.sort((x,y)=>(order.has(x.id)?order.get(x.id):1e9)-(order.has(y.id)?order.get(y.id):1e9));
}
function mergeMaps(b, m, t, conflicts, label){
  b=obj(b); m=obj(m); t=obj(t);
  const out={}; const same=(x,y)=>JSON.stringify(x)===JSON.stringify(y);
  for(const k of new Set([...Object.keys(m),...Object.keys(t)])){
    if(same(m[k],b[k])){ if(t[k]!==undefined) out[k]=t[k]; continue; }
    if(same(t[k],b[k])){ if(m[k]!==undefined) out[k]=m[k]; continue; }
    out[k]= m[k]!==undefined ? m[k] : t[k];
    if(m[k]!==undefined && t[k]!==undefined && !same(m[k],t[k])) conflicts.push(label+k+' — kept your version');
  }
  return out;
}
async function mergeWithServer(){
  if(!base) return false;
  let j; try{ j = await api('load', undefined, ''); }catch(e){ return false; }
  const {d:theirs} = normalize(j.data);
  const mine = clone(data), conflicts=[];
  const mineSer = await serialize();
  const res = clone(theirs);
  // public collections
  for(const c of MERGE_COLS) res[c] = mergeCollections(base[c], mineSer[c], theirs[c], conflicts, '');
  res.days = mergeMaps(base.days, mine.days, theirs.days, conflicts, 'Day ');
  res.monthNotes = mergeMaps(base.monthNotes, mine.monthNotes, theirs.monthNotes, conflicts, 'Month notes ');
  res.owner = (mine.owner===base.owner) ? theirs.owner : mine.owner;
  res.settings = JSON.stringify(mine.settings)===JSON.stringify(base.settings) ? theirs.settings : mine.settings;
  // private part
  let privMerged=null;
  if(privKey && theirs.private){
    let tp=null; try{ tp = await decPriv(privKey, theirs.private); }catch(e){}
    if(tp){ const mp=privPart(); privMerged={}; for(const c of PRIV_COLS) privMerged[c]=mergeCollections(basePriv?basePriv[c]:[], mp[c], tp[c], conflicts, '🔒 '); }
  }
  data = normalize(res).d;
  if(privMerged) mergePriv(privMerged);
  else if(!privKey) data.private = theirs.private;
  rev = j.rev; lastSaved={by:j.by||'', saved:j.saved||''};
  base = await serialize(); basePriv = privKey?clone(privPart()):null;
  mergeNote = {by:j.by||'someone', conflicts};
  invalidateCaches(); lastSnap=snapshotState();
  renderAll();
  return true;
}

/* ---------- loading ---------- */
async function loadData(force){
  const j = await api('load', undefined, force ? '' : '&since='+rev);
  if(j.unchanged) return false;
  const {d, wasV1} = normalize(j.data);
  const prevPrivKey = privKey;
  data = d; rev = j.rev; lastSaved = {by:j.by||'', saved:j.saved||''};
  base = normalize(j.data).d;
  privKey = null; privLocked = false; basePriv = null;
  if(data.private){
    if(prevPrivKey){ try{ const p=await decPriv(prevPrivKey, data.private); privKey=prevPrivKey; basePriv=clone(p); mergePriv(p); }catch(e){ await tryAutoUnlock(); } }
    else await tryAutoUnlock();
    if(data.private && !privKey) privLocked = true;
  }
  invalidateCaches();
  lastSnap = snapshotState(); undoStack=[]; redoStack=[];
  cacheOffline();
  if(wasV1 && editable){
    // first open with the new app: upgrade the stored calendar (days, flights, comments stay as they are)
    const days=Object.keys(data.days).length;
    console.info('Upgraded calendar to v2;', days, 'days kept');
    changed('migrate');
    setTimeout(()=>toast('Calendar upgraded: projects, events, tasks and reminders are now available. Your '+days+' days of entries are unchanged.', {sticky:false, ms:9000}), 600);
  }
  return true;
}
async function reloadData(manual){
  if(!session || offlineMode) return;
  if(editable && (dirty || saving || conflict)){ if(manual) alert(conflict ? 'Choose what to do with your change first (see the message at the top).' : 'Your latest change is still being saved. Try again in a moment.'); return; }
  try{
    const changedOnServer = await loadData(false);
    if(changedOnServer){ renderAll(); }
    if(manual) setSave(editable?'Saved ✓':'Up to date','ok');
  }catch(e){ if(manual && e.status!==401) alert(e.message); }
}
setInterval(()=>{ if(session && !document.hidden) reloadData(false); }, POLL_MS);
document.addEventListener('visibilitychange', ()=>{
  if(!document.hidden && session) reloadData(false);
  if(document.hidden && editable && dirty){ clearTimeout(saveTimer); saveNow(); }
});

/* ---------- offline copy (optional, per device) ---------- */
function cacheOffline(){
  if(!session || !prefs.offlineCopy) return;
  serialize().then(d=>{ try{ localStorage.setItem('wtc-offline', JSON.stringify({user:session.user, role:session.role, at:nowISO(), rev, data:d})); }catch(e){} });
}
function clearOffline(){ safeDel('wtc-offline'); }
async function enterOffline(){
  const c=jget('wtc-offline',null); if(!c) return false;
  session={user:c.user, role:c.role}; editable=false; offlineMode=true; loadPrefs();
  data=normalize(c.data).d; rev=c.rev; await tryAutoUnlock();
  document.body.classList.remove('locked'); afterModeChange();
  showBanner(`⚠ Offline: showing the copy saved on this device at ${esc(fmtWhen(c.at))}. Changes are not possible until the server can be reached.`,'warn',[['Try again',()=>location.reload()]]);
  return true;
}

/* ---------- sign-in ---------- */
function showScreen(which, msg){
  document.body.classList.add('locked');
  $('#loginForm').style.display = which==='login' ? '' : 'none';
  $('#setupForm').style.display = which==='setup' ? '' : 'none';
  $('#bootMsg').style.display = which==='boot' ? '' : 'none';
  if(which==='login'){ $('#lErr').textContent = msg||''; setTimeout(()=>$('#lUser').focus(),0); }
  if(which==='setup'){ $('#sErr').textContent = msg||''; }
  if(which==='boot'){ $('#bootMsg').innerHTML = msg||''; }
}
function sessionLost(msg){
  const keep = editable && dirty;
  session = null;
  showScreen('login', msg + (keep ? ' Your last change is kept and will be saved after you sign in.' : ''));
}
async function enter(me){
  const sameUser = session===null && data && dirty && editable && me.role==='editor';
  session = {user:me.user, role:me.role}; token = me.token||''; editable = me.role==='editor';
  loadPrefs();
  if(sameUser){ document.body.classList.remove('locked'); afterModeChange(); saveNow(); return; }
  dirty=false; conflict=null;
  await loadData(true);
  document.body.classList.remove('locked');
  afterModeChange();
  startReminders();
  if(DETAIL) return;
  setTimeout(tzTravelPrompt, 2500);
  routeMainHash();
}
async function boot(){
  showScreen('boot', 'Loading…');
  try{
    const me = await api('me');
    if(me.setup) return showScreen('setup');
    if(me.user) return await enter(me);
    showScreen('login');
  }catch(e){
    if(e.status===0 && await enterOffline()) return;
    showScreen('boot', '<b>The calendar could not start.</b><br>'+esc(e.message));
  }
}
$('#loginForm').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const u=$('#lUser').value.trim(), p=$('#lPass').value, btn=$('#lBtn'), nb=$('#lNewBox');
  if(!u||!p) return;
  const body={user:u, pass:p, remember:$('#lRemember').checked};
  if(!nb.hidden){                         // second step after a password reset
    const n1=$('#lNew').value, n2=$('#lNew2').value;
    if(n1.length<6){ $('#lErr').textContent='The new password needs at least 6 characters.'; return; }
    if(n1!==n2){ $('#lErr').textContent='The two new passwords do not match.'; return; }
    body.newpass=n1;
  }
  btn.disabled=true; btn.textContent='Signing in…'; $('#lErr').textContent='';
  try{
    const me = await api('login', body);
    if(me.change){                        // temporary password accepted: ask for a new one
      nb.hidden=false; $('#lPass').closest('label').firstChild.textContent='Temporary password ';
      $('#lErr').textContent=''; setTimeout(()=>$('#lNew').focus(),0); return;
    }
    $('#lPass').value=''; $('#lNew').value=''; $('#lNew2').value='';
    if(!nb.hidden){ nb.hidden=true; setTimeout(()=>toast('Your new password is saved.'),800); }
    await enter(me);
  }catch(err){ $('#lErr').textContent = err.message; }
  finally{ btn.disabled=false; btn.textContent=nb.hidden?'Sign in':'Save new password and sign in'; }
});
$('#setupForm').addEventListener('submit', async ev=>{
  ev.preventDefault();
  const v=id=>$('#'+id).value, btn=$('#sBtn');
  if(v('sEdPass')!==v('sEdPass2') || v('sVwPass')!==v('sVwPass2')){ $('#sErr').textContent='The two passwords of an account do not match.'; return; }
  btn.disabled=true; $('#sErr').textContent='';
  try{
    const me = await api('setup', {editor:{user:v('sEdUser').trim(), pass:v('sEdPass')}, viewer:{user:v('sVwUser').trim(), pass:v('sVwPass')}});
    await enter(me);
  }catch(err){ $('#sErr').textContent = err.message; }
  finally{ btn.disabled=false; }
});
async function signOut(){
  if(editable && dirty && !confirm('Your latest change has not reached the server yet and will be lost.\n\nSign out anyway?')) return;
  clearOffline();
  try{ await api('logout', {}); }catch(e){}
  location.reload();
}
function afterModeChange(){
  $('#userBadge').innerHTML = session ? `👤 <b>${esc(session.user)}</b> · ${editable?'Editor':'Viewer'}` : '';
  if(offlineMode) setSave('Offline','err');
  else if(!editable) setSave('Read-only');
  else if(!dirty && !conflict) setSave('Saved ✓','ok');
  $$('[data-ed]').forEach(el=>el.style.display = editable ? '' : 'none');
  renderBanner(); renderAll();
}

/* ============================================================
   Shell: render all, side panel, tasks panel, dialogs, toasts, banner
   ============================================================ */
function renderAll(){
  if(!data || !session) return;
  if(DETAIL){ renderDetail(); renderTimerSlot(); renderTzLabel(); refreshOpenPanels(); return; }
  render(); renderSide(); renderTasksPanel(); renderBell(); renderTimerSlot(); renderTzLabel();
  if(openDay) renderDrawer();
  refreshOpenPanels();
}
const _panelRefreshers = new Set();
function refreshOpenPanels(){ for(const f of [..._panelRefreshers]) try{ f(); }catch(e){ console.error(e); } }

/* ---------- dialogs ---------- */
let _dlgStack=[];
let EDIT_PAGE_PENDING=false;   // set by an edit tab just before it opens its editor
function openDialog(html, opts={}){
  const page=EDIT_PAGE_PENDING; EDIT_PAGE_PENDING=false;
  const d=document.createElement('div'); d.className='dlg'+(page?' page':''); d.setAttribute('role',page?'region':'dialog'); if(!page) d.setAttribute('aria-modal','true');
  d.innerHTML=`<div class="box ${opts.size||''}">${html}</div>`;
  (page?$('#detail'):document.body).appendChild(d); _dlgStack.push(d);
  if(page){ d._page=true; d._seq=changeSeq; d.addEventListener('input',()=>{ d._modified=true; }); d.addEventListener('change',()=>{ d._modified=true; }); }
  else d.addEventListener('mousedown', e=>{ if(e.target===d && !opts.sticky) closeDialog(d); });
  $$('[data-close]',d).forEach(b=>b.onclick=()=>{ d._cancel=true; closeDialog(d); });
  d._onclose = opts.onclose;
  if(opts.refresh){ d._refresh=opts.refresh; _panelRefreshers.add(opts.refresh); }
  setTimeout(()=>{ const f=$(opts.focus||'[autofocus],input:not([type=hidden]):not([disabled]),select,textarea,button.primary',d); f&&f.focus(); },0);
  return d;
}
function closeDialog(d){
  d = d || _dlgStack[_dlgStack.length-1]; if(!d) return;
  if(d._page){ closeEditPage(d); return; }
  _dlgStack=_dlgStack.filter(x=>x!==d); if(d._refresh) _panelRefreshers.delete(d._refresh);
  d.remove(); if(d._onclose) d._onclose();
}
function topDialog(){ return _dlgStack[_dlgStack.length-1]; }
function confirmDlg(msg, buttons){ // buttons: [[label,value,cls]]
  return new Promise(res=>{
    const d=openDialog(`<div class="dhd"><h2>${msg.title?esc(msg.title):'Please confirm'}</h2></div><p>${msg.html||esc(msg)}</p>
      <div class="dacts"><span class="sp"></span>${buttons.map((b,i)=>`<button data-i="${i}" class="${b[2]||''}">${esc(b[0])}</button>`).join('')}</div>`, {size:'narrow', onclose:()=>res(null)});
    $$('[data-i]',d).forEach(b=>b.onclick=()=>{ d._onclose=null; closeDialog(d); res(buttons[+b.dataset.i][1]); });
  });
}
function promptDlg(title, label, value, opts={}){
  return new Promise(res=>{
    const d=openDialog(`<div class="dhd"><h2>${esc(title)}</h2></div>
      <form><label class="hint">${esc(label)}</label><input type="${opts.type||'text'}" id="pdv" value="${esc(value||'')}" style="margin-top:6px" ${opts.attrs||''}>
      ${opts.extra||''}<div class="lerr" id="pde" style="margin-top:6px"></div>
      <div class="dacts"><span class="sp"></span><button type="button" data-close>Cancel</button><button class="primary" type="submit">${esc(opts.ok||'OK')}</button></div></form>`, {size:'narrow', onclose:()=>res(null)});
    $('form',d).onsubmit=async e=>{ e.preventDefault(); const v=$('#pdv',d).value; if(opts.validate){ const err=await opts.validate(v,d); if(err){ $('#pde',d).textContent=err; return; } } d._onclose=null; closeDialog(d); res(opts.full?{v,d}:v); };
  });
}

/* ---------- toasts ---------- */
function toast(msg, opts={}){
  const t=document.createElement('div'); t.className='toast simple'; t.setAttribute('role','status');
  t.innerHTML=`<div class="th"><b style="font-weight:500">${opts.html?msg:esc(msg)}</b><button class="ghost" data-x style="padding:0 4px">✕</button></div>${opts.actions?`<div class="ta">${opts.actions.map((a,i)=>`<button data-a="${i}">${esc(a[0])}</button>`).join('')}</div>`:''}`;
  $('#toasts').appendChild(t);
  const simple=$$('#toasts .toast.simple'); simple.slice(0,Math.max(0,simple.length-3)).forEach(x=>x.remove()); // keep the 3 newest
  const kill=()=>t.remove();
  $('[data-x]',t).onclick=kill;
  (opts.actions||[]).forEach((a,i)=>$(`[data-a="${i}"]`,t).onclick=()=>{ kill(); a[1](); });
  if(!opts.sticky) setTimeout(kill, opts.ms||(opts.actions?10000:3500));
  return t;
}

/* ---------- banner ---------- */
let _banner=null;
function showBanner(html, cls, actions){ _banner={html,cls,actions}; renderBanner(); }
function renderBanner(){
  const b=$('#banner'); b.className=''; b.innerHTML='';
  if(editable && conflict){
    b.className='show warn';
    b.innerHTML = `<span>⚠ <b>${esc(conflict.by||'Someone')}</b> saved the calendar at ${esc(fmtWhen(conflict.saved))} and the changes could not be combined automatically. Your latest change was <b>not saved</b>.</span>`
      + '<button id="bTheirs">Load their version (drop my change)</button><button class="primary" id="bMine">Save mine over it</button>';
    $('#bTheirs').onclick=async()=>{ conflict=null; dirty=false; clearTimeout(saveTimer); try{ await loadData(true); }catch(e){ alert(e.message); } afterModeChange(); };
    $('#bMine').onclick=()=>{ rev=conflict.rev; conflict=null; dirty=true; setSave('Saving…'); renderBanner(); saveNow(); };
  } else if(editable && netError && dirty){
    b.className='show warn';
    b.innerHTML = '<span>⚠ The server cannot be reached, so your latest change is not saved yet. Keep this page open; it keeps trying.</span><button id="bRetry">Try now</button>';
    $('#bRetry').onclick=()=>{ clearTimeout(saveTimer); saveNow(); };
  } else if(mergeNote){
    b.className='show';
    b.innerHTML = `<span>🔀 Combined your changes with <b>${esc(mergeNote.by)}</b>’s, who saved at the same time.${mergeNote.conflicts.length?` ${mergeNote.conflicts.length} item${mergeNote.conflicts.length>1?'s were':' was'} changed by both of you; the newer version was kept: <i>${mergeNote.conflicts.slice(0,6).map(esc).join('; ')}${mergeNote.conflicts.length>6?'…':''}</i>`:''}</span><button id="bOk">OK</button>`;
    $('#bOk').onclick=()=>{ mergeNote=null; renderBanner(); };
  } else if(sizeWarn && editable){
    b.className='show warn';
    b.innerHTML = '<span>⚠ The calendar is getting close to the server’s 3 MB limit. Move old items to an archive file (⚙ Settings → Data).</span><button id="bArch">Open settings</button>';
    $('#bArch').onclick=()=>openSettings('data');
  } else if(_banner){
    b.className='show '+(_banner.cls||''); b.innerHTML=`<span>${_banner.html}</span>`+(_banner.actions||[]).map((a,i)=>`<button data-ba="${i}">${esc(a[0])}</button>`).join('');
    (_banner.actions||[]).forEach((a,i)=>$(`[data-ba="${i}"]`,b).onclick=a[1]);
  }
  setTopH();
}
function setTopH(){ const h=$('#topbar').offsetHeight; document.documentElement.style.setProperty('--topH', h+'px'); }
addEventListener('resize', debounce(()=>{ setTopH(); render(); },150));

/* ---------- side panel ---------- */
let miniCursor = null;
function renderSide(){
  const el=$('#side'); if(!el || !data) return;
  if(!miniCursor) miniCursor=new Date(cursor.getFullYear(),cursor.getMonth(),1);
  const y=miniCursor.getFullYear(), m=miniCursor.getMonth(), first=new Date(y,m,1), last=new Date(y,m+1,0);
  const tk=key(TODAY());
  const [vs,ve]=visibleRange();
  const busyDays=new Set(occurrences(first.getTime(), addDays(last,1).getTime()).map(o=>key(new Date(o.s))));
  let g=DOW.map(d=>`<span class="h">${d[0]}</span>`).join('');
  for(let d=mondayOf(first); d<=addDays(mondayOf(last),6); d=addDays(d,1)){
    const k=key(d), inv=d>=vs && d<ve;
    g+=`<span data-mk="${k}" class="${d.getMonth()!==m?'o':''} ${k===tk?'t':''} ${inv?'inv':''} ${busyDays.has(k)?'has':''}">${d.getDate()}</span>`;
  }
  const projRow=(p,sub)=>{
    const open=data.tasks.filter(t=>t.projectId===p.id && isOpen(t)).length;
    const od=data.tasks.filter(t=>t.projectId===p.id && isOverdue(t)).length;
    const st=projStats(p); const over=p.budgetHours && st.planned/60>p.budgetHours;
    return `<div class="srow ${sub?'sub':''} ${filt.projects.includes(p.id)?'on':''} ${p.status!=='active'?'off':''}" data-proj="${p.id}" draggable="${editable}" title="Click to show only this project; double-click to open it">
      <i class="dotc" style="background:${esc(p.color)}"></i><span class="nm">${esc(p.icon||'')} ${esc(p.name)}${p.pinned?' 📌':''}</span>
      ${over?'<span class="bw" title="Planned hours exceed the budget">€</span>':''}${od?`<span class="od" title="Overdue tasks">${od}</span>`:''}<span class="c">${open||''}</span>
      <button class="ghost" data-popen="${p.id}" title="Open project" style="padding:0 4px;font-size:12px">›</button></div>`;
  };
  let projHtml='';
  for(const c of [...data.calendars].sort((a,b)=>(a.order||0)-(b.order||0))){
    const ps=projectsOf(c.id).filter(p=>p.status!=='archived' && !p.parentId).sort((a,b)=>(b.pinned?1:0)-(a.pinned?1:0)||(a.order||0)-(b.order||0)||a.name.localeCompare(b.name));
    const locked = c.private && privLocked;
    const inboxOpen=data.tasks.filter(t=>!t.projectId && t.calendarId===c.id && isOpen(t)).length;
    projHtml+=`<div class="sideh"><span>${esc(c.name)}${c.private?' 🔒':''}</span>${editable&&!locked?`<button data-newproj="${c.id}" title="New project">＋</button>`:''}</div>`;
    if(locked){ projHtml+=`<div class="hint" style="padding:0 6px 6px">Personal items are locked on this device. <button class="lockbtn" data-unlock>🔑 Unlock</button></div>`; continue; }
    projHtml+=`<div class="srow ${filt.projects.includes('inbox:'+c.id)?'on':''}" data-proj="inbox:${c.id}"><i class="dotc" style="background:var(--line2)"></i><span class="nm">📥 Inbox</span><span class="c">${inboxOpen||''}</span></div>`;
    for(const p of ps){ projHtml+=projRow(p); for(const sp of data.projects.filter(x=>x.parentId===p.id && x.status!=='archived')) projHtml+=projRow(sp,true); }
  }
  const archived=data.projects.filter(p=>p.status==='archived');
  el.innerHTML=`
    <div class="mini"><div class="mh"><button data-mm="-1" aria-label="Previous month">‹</button><span>${MONTHS[m]} ${y}</span><button data-mm="1" aria-label="Next month">›</button></div><div class="g">${g}</div></div>
    <div class="sideh"><span>Calendars</span>${editable?'<button data-act2="cals" title="Manage calendars">⚙</button>':''}</div>
    ${data.calendars.map(c=>`<div class="srow ${filt.hiddenCals.includes(c.id)?'off':''}" data-cal="${c.id}" title="Show / hide"><span class="cbx" style="color:${esc(c.color)};background:${filt.hiddenCals.includes(c.id)?'transparent':esc(c.color)}">${filt.hiddenCals.includes(c.id)?'':'<span style="color:#fff">✓</span>'}</span><span class="nm">${esc(c.name)}</span>${c.private?(privLocked?'<span title="Locked">🔒</span>':(privKey?'<button class="ghost" data-lock title="Lock Personal items on this device" style="padding:0 4px">🔓</button>':'')):''}</div>`).join('')}
    <div class="sideh"><span>Projects</span>${filt.projects.length||filt.tag?'<button data-showall>Show all</button>':''}</div>
    ${projHtml}
    ${archived.length?`<details style="margin-top:8px"><summary class="hint" style="cursor:pointer">Archived projects (${archived.length})</summary>${archived.map(p=>projRow(p)).join('')}</details>`:''}
    <div class="sideh"><span>Saved filters</span><button data-savefilt title="Save the current filters">＋</button></div>
    ${prefs.savedFilters.map((f,i)=>`<div class="srow" data-sf="${i}"><span>⭐</span><span class="nm">${esc(f.name)}</span><button class="ghost" data-sfdel="${i}" title="Remove" style="padding:0 4px">✕</button></div>`).join('') || '<div class="hint" style="padding:0 6px">Save filter combinations here.</div>'}
    ${filt.tag?`<div class="sideh"><span>Tag</span></div><div class="srow on" data-cleartag>#${esc(filt.tag)} ✕</div>`:''}`;
  // events
  $$('[data-mm]',el).forEach(b=>b.onclick=()=>{ miniCursor=new Date(y,m+(+b.dataset.mm),1); renderSide(); });
  $$('[data-mk]',el).forEach(s=>s.onclick=()=>{ cursor=fromKey(s.dataset.mk); if(view==='month'||view==='board') setView('day'); else render(); renderSide(); });
  $$('[data-cal]',el).forEach(r=>r.onclick=e=>{ if(e.target.closest('[data-lock]')) return; const id=r.dataset.cal; filt.hiddenCals=filt.hiddenCals.includes(id)?filt.hiddenCals.filter(x=>x!==id):[...filt.hiddenCals,id]; saveFilter(); renderAll(); });
  $$('[data-proj]',el).forEach(r=>{
    r.onclick=e=>{ if(e.target.closest('[data-popen]')) return; const id=r.dataset.proj; if(e.ctrlKey||e.metaKey||e.shiftKey) filt.projects=filt.projects.includes(id)?filt.projects.filter(x=>x!==id):[...filt.projects,id]; else filt.projects=(filt.projects.length===1&&filt.projects[0]===id)?[]:[id]; saveFilter(); renderAll(); };
    r.ondblclick=()=>{ if(!r.dataset.proj.startsWith('inbox:')) openProjectPage(r.dataset.proj); };
    // drop items onto a project; drag projects to reorder
    r.addEventListener('dragover',e=>{ if(!editable) return; e.preventDefault(); r.classList.add('dragover'); });
    r.addEventListener('dragleave',()=>r.classList.remove('dragover'));
    r.addEventListener('drop',e=>{ r.classList.remove('dragover'); if(!editable) return; e.preventDefault(); const pid=r.dataset.proj; const dragged=e.dataTransfer.getData('text/x-proj'); const itemId=e.dataTransfer.getData('text/x-item')||e.dataTransfer.getData('text/x-task');
      if(dragged && !pid.startsWith('inbox:')) return reorderProject(dragged, pid);
      if(itemId) moveToProject([itemId], pid);
    });
    r.addEventListener('dragstart',e=>{ if(r.dataset.proj.startsWith('inbox:')) return e.preventDefault(); e.dataTransfer.setData('text/x-proj', r.dataset.proj); });
  });
  $$('[data-popen]',el).forEach(b=>b.onclick=()=>openProjectPage(b.dataset.popen));
  $$('[data-newproj]',el).forEach(b=>b.onclick=()=>openProjectEditor(null,{calendarId:b.dataset.newproj}));
  const sa=$('[data-showall]',el); if(sa) sa.onclick=()=>{ filt.projects=[]; filt.tag=''; saveFilter(); renderAll(); };
  const ct=$('[data-cleartag]',el); if(ct) ct.onclick=()=>{ filt.tag=''; saveFilter(); renderAll(); };
  $$('[data-unlock]',el).forEach(b=>b.onclick=()=>openUnlock());
  $$('[data-lock]',el).forEach(b=>b.onclick=async e=>{ e.stopPropagation(); if(dirty) await saveNow(); await lockPriv(); renderAll(); toast('Personal items locked on this device.'); });
  const ac=$('[data-act2=cals]',el); if(ac) ac.onclick=()=>openSettings('calendars');
  $('[data-savefilt]',el).onclick=async()=>{ const n=await promptDlg('Save filters','Name for this filter','',{ok:'Save'}); if(!n) return; prefs.savedFilters.push({name:n, filt:clone(filt), view}); savePrefs(); renderSide(); };
  $$('[data-sf]',el).forEach(r=>r.onclick=e=>{ if(e.target.closest('[data-sfdel]')) return; const f=prefs.savedFilters[+r.dataset.sf]; filt=Object.assign({},DEFAULT_FILTER,clone(f.filt)); saveFilter(); if(f.view) setView(f.view); else renderAll(); });
  $$('[data-sfdel]',el).forEach(b=>b.onclick=()=>{ prefs.savedFilters.splice(+b.dataset.sfdel,1); savePrefs(); renderSide(); });
}
function reorderProject(dragId, ontoId){
  const a=proj(dragId), b=proj(ontoId); if(!a||!b||a===b) return;
  const list=projectsOf(b.calendarId).filter(p=>!p.parentId).sort((x,y)=>(x.order||0)-(y.order||0));
  const rest=list.filter(p=>p!==a); rest.splice(rest.indexOf(b),0,a);
  if(a.calendarId!==b.calendarId){ a.calendarId=b.calendarId; }
  rest.forEach((p,i)=>{ p.order=i; }); touch(a); changed(); renderAll();
}
function moveToProject(ids, pid){
  const inbox=pid.startsWith('inbox:'), calId=inbox?pid.slice(6):proj(pid).calendarId;
  if(isPrivCal(calId) && !ensurePrivReadySync()) return;
  for(const id of ids){ const it=findItem(id); if(!it) continue; it.projectId=inbox?null:pid; it.calendarId=calId; touch(it); }
  changed(); renderAll(); toast(`Moved ${ids.length} item${ids.length>1?'s':''} to ${inbox?'Inbox':proj(pid).name}`);
}

/* ---------- tasks panel ---------- */
const SMART = {
  today:{label:'Today', f:t=>isOpen(t) && (t.dueDate===todayKey() || t.blocks.some(b=>b.start.slice(0,10)===todayKey() && !b.done))},
  overdue:{label:'Overdue', f:t=>isOverdue(t)},
  upcoming:{label:'Next 7 days', f:t=>isOpen(t) && t.dueDate && t.dueDate>todayKey() && t.dueDate<=keyAdd(todayKey(),7)},
  unscheduled:{label:'Unscheduled', f:t=>isOpen(t) && !t.blocks.some(b=>!b.done)},
  waiting:{label:'Waiting', f:t=>t.status==='waiting'},
  nodue:{label:'No due date', f:t=>isOpen(t) && !t.dueDate},
  all:{label:'All open', f:t=>isOpen(t)},
  done:{label:'Completed', f:t=>!isOpen(t)},
};
function todayKey(){ return key(TODAY()); }
function sortTasks(list, by){
  const pr=t=>t.priority||3, du=t=>t.dueDate?(t.dueDate+(t.dueTime||'99')):'9999';
  const cmp={due:(a,b)=>du(a).localeCompare(du(b))||pr(a)-pr(b), priority:(a,b)=>pr(a)-pr(b)||du(a).localeCompare(du(b)),
    project:(a,b)=>((proj(a.projectId)||{}).name||'~').localeCompare((proj(b.projectId)||{}).name||'~')||du(a).localeCompare(du(b)),
    estimate:(a,b)=>(b.estimateMin||0)-(a.estimateMin||0), title:(a,b)=>a.title.localeCompare(b.title)}[by]||(()=>0);
  return list.sort(cmp);
}
function taskRowHTML(t, opts={}){
  const c=itemColor(t), p=proj(t.projectId), od=isOverdue(t), bl=blockedBy(t).length, us=underScheduled(t);
  const plan=plannedMin(t);
  const due=t.dueDate?`<span class="${od?'od':''}">📅 ${esc(dueLabel(t))}</span>`:'';
  return `<div class="trow ${isOpen(t)?'':'done'} ${selTasks.has(t.id)?'sel':''}" style="--c:${esc(c)}" data-task="${t.id}" draggable="${editable}" tabindex="0" aria-label="Task ${esc(t.title)}">
    <button class="check ${isOpen(t)?'':'on'} pk" data-done="${t.id}" title="${isOpen(t)?'Mark done':'Mark not done'}" aria-label="Done" ${editable?'':'disabled'}>${isOpen(t)?'':'✓'}</button>
    <span class="tt">${bl?'🔒 ':''}${esc(t.title)}</span>
    <span>${t.priority&&t.priority<=2?`<span class="pri p${t.priority}">P${t.priority}</span>`:''}</span>
    <div class="meta">${due}${p?`<span>${esc(p.icon||'')} ${esc(p.name)}</span>`:''}${t.estimateMin?`<span class="${us?'warn':''}" title="${us?'Not enough time scheduled before the due date':''}">⏱ ${fmtH(plan)} of ${fmtH(t.estimateMin)}${us?' ⚠':''}</span>`:(plan?`<span>⏱ ${fmtH(plan)}</span>`:'')}${t.checklist.length?`<span>☑ ${t.checklist.filter(c=>c.done).length}/${t.checklist.length}</span>`:''}${t.rrule?'<span>🔁</span>':''}${isPrivate(t)?'<span>🔒</span>':''}${opts.sel&&editable?`<label style="margin-left:auto" onclick="event.stopPropagation()"><input type="checkbox" data-tsel="${t.id}" ${selTasks.has(t.id)?'checked':''}> select</label>`:''}</div>
  </div>`;
}
function dueLabel(t){
  if(!t.dueDate) return '';
  const dd=t.dueTime&&t.calendarId!==undefined?dueDisp(t):{k:t.dueDate,time:t.dueTime||null};
  const tk=todayKey(); let s;
  if(dd.k===tk) s='Today'; else if(dd.k===keyAdd(tk,1)) s='Tomorrow'; else if(dd.k===keyAdd(tk,-1)) s='Yesterday'; else s=fmtShort(fromKey(dd.k));
  return s+(dd.time?' '+dd.time:'');
}
function renderTasksPanel(){
  const el=$('#tasks'); if(!el || !data) return;
  const all=data.tasks.filter(passFilter);
  const counts=Object.fromEntries(Object.entries(SMART).map(([k,v])=>[k,all.filter(v.f).length]));
  let list=sortTasks(all.filter(SMART[tpTab].f), prefs.tpSort);
  if(tpTab==='done') list=list.sort((a,b)=>(b.completedAt||'').localeCompare(a.completedAt||''));
  const groups=new Map();
  for(const t of list){
    let g='';
    if(prefs.tpGroup==='project') g=(proj(t.projectId)||{name:'Inbox'}).name;
    else if(prefs.tpGroup==='status') g=TASK_STATUS[t.status||'todo'];
    else if(prefs.tpGroup==='due') g=t.dueDate?(isOverdue(t)?'Overdue':dueLabel({dueDate:t.dueDate})):'No due date';
    if(!groups.has(g)) groups.set(g,[]); groups.get(g).push(t);
  }
  // today summary
  const tk=todayKey(), now=NOW(), d0=fromKey(tk).getTime(), d1=d0+864e5;
  const occ=occurrences(d0,d1,{all:true}).filter(o=>!o.allDay && o.kind!=='busy');
  const next=occ.filter(o=>o.e>now && !o.cancelled && !o.done)[0];
  const freeMin=freeWorkMinutes(tk);
  el.innerHTML=`
    <div class="today-sum"><b>Today · ${esc(fmtShort(new Date(NOW())))}</b>
      <div class="row"><span>Events</span><span>${occ.filter(o=>o.kind==='event'&&!o.cancelled).length}</span></div>
      <div class="row"><span>Task blocks</span><span>${occ.filter(o=>o.kind==='block').length}</span></div>
      <div class="row"><span>Due / overdue tasks</span><span>${counts.today} / ${counts.overdue}</span></div>
      <div class="row"><span>Free work time left</span><span>${fmtH(freeMin)}</span></div>
      ${next?`<div class="row" style="color:var(--ink)"><span>Next: ${esc(next.title)}</span><span>${hm(next.s)}</span></div>`:''}
    </div>
    <div class="sideh" style="margin-top:0"><span>Tasks</span>${editable?'<button data-newtask title="New task">＋ Task</button>':''}</div>
    <div class="tp-tabs">${Object.entries(SMART).map(([k,v])=>`<button data-tp="${k}" class="${tpTab===k?'on':''}">${v.label}<span class="n">${counts[k]}</span></button>`).join('')}</div>
    <div class="tp-ctrl"><select data-tpsort aria-label="Sort">${['due','priority','project','estimate','title'].map(s=>`<option value="${s}" ${prefs.tpSort===s?'selected':''}>Sort: ${s}</option>`).join('')}</select>
      <select data-tpgroup aria-label="Group">${['none','project','status','due'].map(s=>`<option value="${s}" ${prefs.tpGroup===s?'selected':''}>Group: ${s}</option>`).join('')}</select></div>
    <div class="tl">${list.length?[...groups].map(([g,ts])=>(g?`<div class="tgroup">${esc(g)}</div>`:'')+ts.map(t=>taskRowHTML(t,{sel:true})).join('')).join(''):'<div class="hint" style="padding:10px 4px">No tasks here.</div>'}</div>
    ${editable?'<div class="hint" style="margin-top:10px">Drag a task onto the calendar to give it a time block.</div>':''}`;
  $$('[data-tp]',el).forEach(b=>b.onclick=()=>{ tpTab=b.dataset.tp; renderTasksPanel(); });
  $('[data-tpsort]',el).onchange=e=>{ prefs.tpSort=e.target.value; savePrefs(); renderTasksPanel(); };
  $('[data-tpgroup]',el).onchange=e=>{ prefs.tpGroup=e.target.value; savePrefs(); renderTasksPanel(); };
  const nt=$('[data-newtask]',el); if(nt) nt.onclick=()=>openTaskEditor(null);
  bindTaskRows(el);
  renderSelbar();
}
function bindTaskRows(el){
  $$('[data-task]',el).forEach(r=>{
    r.onclick=e=>{ if(e.target.closest('[data-done],[data-tsel],label')) return; selItem={kind:'task',id:r.dataset.task}; openTaskEditor(r.dataset.task); };
    r.onkeydown=e=>{ if(e.key==='Enter') r.click(); if(e.key===' '&&editable){ e.preventDefault(); toggleTaskDone(r.dataset.task); } };
    r.addEventListener('dragstart',e=>{ e.dataTransfer.setData('text/x-task', r.dataset.task); e.dataTransfer.effectAllowed='copyMove'; });
  });
  $$('[data-done]',el).forEach(b=>b.onclick=e=>{ e.stopPropagation(); toggleTaskDone(b.dataset.done); });
  $$('[data-tsel]',el).forEach(c=>c.onchange=()=>{ c.checked?selTasks.add(c.dataset.tsel):selTasks.delete(c.dataset.tsel); renderTasksPanel(); });
}

/* ============================================================
   Views: Day, Week (time grid or compact), Month, Agenda, Board
   ============================================================ */
let agendaDays = 14;
function setView(v){
  const prev=view; view=v; safeSet('wtc-view',v);
  if(prev==='month' && (v==='week'||v==='day'||v==='3day')){ const t=TODAY(); if(t.getMonth()!==cursor.getMonth()||t.getFullYear()!==cursor.getFullYear()) cursor=new Date(cursor.getFullYear(),cursor.getMonth(),1); else cursor=t; }
  renderAll();
}
/* Hide Saturday & Sunday (per device) — applies to 3 days, Week and Month */
function hideWE(){ return !!prefs.hideWeekends; }
function nextWorkday(d, dir=1){ while(isWeekend(d)) d=addDays(d,dir); return d; }
function threeDays(){ // the 3 days shown from cursor
  if(!hideWE()) return [0,1,2].map(i=>addDays(cursor,i));
  const out=[]; let d=nextWorkday(cursor); while(out.length<3){ if(!isWeekend(d)) out.push(d); d=addDays(d,1); } return out;
}
function weekDays(){ const mon=mondayOf(cursor); return [0,1,2,3,4,5,6].map(i=>addDays(mon,i)).filter(d=>!hideWE()||!isWeekend(d)); }
function visibleRange(){
  if(view==='day') return [cursor, addDays(cursor,1)];
  if(view==='3day'){ const ds=threeDays(); return [ds[0], addDays(ds[ds.length-1],1)]; }
  if(view==='week') return [mondayOf(cursor), addDays(mondayOf(cursor),7)];
  if(view==='agenda') return [cursor, addDays(cursor,agendaDays)];
  if(view==='board') return [cursor, addDays(cursor,1)];
  const f=new Date(cursor.getFullYear(),cursor.getMonth(),1); return [mondayOf(f), addDays(mondayOf(new Date(cursor.getFullYear(),cursor.getMonth()+1,0)),7)];
}
function move(n){
  if(view==='day') cursor=addDays(cursor,n);
  else if(view==='3day'){ if(!hideWE()) cursor=addDays(cursor,3*n); else { let d=nextWorkday(cursor), k=0; while(k<3){ d=addDays(d,n); if(!isWeekend(d)) k++; } cursor=d; } }
  else if(view==='week') cursor=addDays(cursor,7*n);
  else if(view==='agenda') cursor=addDays(cursor,agendaDays*n);
  else if(view==='board') return;
  else cursor=new Date(cursor.getFullYear(), cursor.getMonth()+n, 1);
  miniCursor=new Date(cursor.getFullYear(),cursor.getMonth(),1);
  render(); renderSide();
}
function hourPx(){ return prefs.density==='compact'?Math.round(prefs.hourPx*.75):prefs.hourPx; }

/* ---------- day header pieces (status, location, flights) ---------- */
function statusHTML(e, compact){
  const am=e.am, pm=e.pm;
  if(!am && !pm) return compact ? '' : '<div class="st none">No status</div>';
  if(am===pm) return `<div class="st ${am}" title="${STATUSES[am].label} (whole day)">${esc(STATUSES[am][compact?'short':'label'])}</div>`;
  const one=(s,h)=> s ? `<div class="st ${s}" title="${h==='AM'?'Morning':'Evening'}: ${STATUSES[s].label}"><span class="h">${h}</span>${esc(STATUSES[s][compact?'short':'label'])}</div>`
                      : `<div class="st none"><span class="h">${h}</span>—</div>`;
  return `<div class="halves">${one(am,'AM')}${one(pm,'PM')}</div>`;
}
/* Day-filling rules (unchanged): Athens + Telework → dark red; Athens + Telework abroad → light green */
function fillClass(k, e, we){
  if(we || selected.has(k) || !filt.showStatus) return '';
  const l = locationOf(k);
  if(!l || l.name.trim().toLowerCase()!=='athens') return '';
  if(e.am==='tele' || e.pm==='tele') return 'fill-tele';
  if(e.am==='abroad' || e.pm==='abroad') return 'fill-abroad';
  return '';
}
function locHTML(k){
  const l=locationOf(k); if(!l) return '';
  return `<span class="loc${l.auto?' auto':''}" title="${l.auto?'Inferred from last flight':'Location'}">📍 ${esc(l.name)}</span>`;
}
function flightDisp(k,f){ // display (active zone) times of a flight
  const w=flightWindows(k).find(x=>x.f===f); if(!w) return flightTimeLabel(f);
  const t=hm(w.s)+'–'+(key(new Date(w.e))!==key(new Date(w.s))&&hm(w.e)==='00:00'?'24:00':hm(w.e));
  return t+(w.tz!==ACTIVE_TZ?` (${minToHM(flightMins(f).a)} ${tzShort(w.tz)})`:'');
}
function flightsHTML(e,k){
  if(!filt.showFlights) return '';
  return (e.flights||[]).map((f,fi)=>`<div class="fl" data-flk="${k||''}" data-fli="${fi}" role="button" tabindex="0" title="Click for details · "${esc(f.from)} → ${esc(f.to)}${f.note?' · '+esc(f.note):''}">✈ ${esc(code(f.from))} → ${esc(code(f.to))} <span class="t">${esc(FLIGHT_TIMES[flightKind(f)])} ${esc(k?flightDisp(k,f):flightTimeLabel(f))}</span></div>`).join('');
}

/* ---------- chips (all-day row, month cells, compact week) ---------- */
function occChip(o, dayK){
  if(o.kind==='busy') return `<div class="chipi busy" title="Busy (Personal, locked)">Busy</div>`;
  const it=o.item, c=itemColor(it);
  const cont = o.allDay && o.dateKey<dayK;
  const t = o.allDay ? '' : `<span class="t">${hm(o.s)}</span>`;
  const cls=[o.kind==='block'?'task':'', o.done?'done':'', o.cancelled?'cancelled':'', cont?'cont':''].join(' ');
  return `<div class="chipi ${cls}" style="--c:${esc(c)}" data-occ="${esc(o.id)}" data-oc="${esc(o.occ)}" data-ok="${o.kind}" draggable="${editable && !it.rrule}" title="${esc(o.title)}">${o.kind==='block'?(o.done?'✓':'☐'):''}${cont?'↳ ':''}${t} ${esc(CAT_ICON[it.category]||'')}${esc(o.title)}</div>`;
}
function dueChip(t, overdue){
  const dd=dueDisp(t)||{};
  return `<div class="chipi due ${overdue?'overdue':''} ${isOpen(t)?'':'done'}" style="--c:${esc(itemColor(t))}" data-task="${t.id}" draggable="${editable}" title="Due${dd.time?' '+dd.time:''}: ${esc(t.title)}">⚑ ${dd.time?`<span class="t">${dd.time}</span> `:''}${esc(t.title)}</div>`;
}
function flightChips(k){
  if(!filt.showFlights) return '';
  return (day(k).flights||[]).map((f,fi)=>`<div class="chipi flight" data-flk="${k}" data-fli="${fi}" role="button" tabindex="0" title="Click for details · "${esc(f.from)} → ${esc(f.to)} · ${esc(flightLabel(f))}${f.note?' · '+esc(f.note):''}">✈ ${esc(code(f.from))}→${esc(code(f.to))} <span class="t">${esc(flightDisp(k,f))}</span></div>`).join('');
}
function dayItems(k){ // all items of a day for chips, in display order
  const d0=fromKey(k).getTime(), d1=addDays(fromKey(k),1).getTime();
  const occ=occurrences(d0,d1);
  const ad=occ.filter(o=>o.allDay), tm=occ.filter(o=>!o.allDay);
  const due=filt.showDue?dueTasksOn(k):[];
  const tk=todayKey();
  const overdue = (k===tk && filt.showDue) ? data.tasks.filter(t=>passFilter(t) && isOverdue(t) && t.dueDate<tk) : [];
  return {ad, tm, due, overdue};
}

/* ---------- main render ---------- */
function render(){
  if(!data || !session) return;
  $$('#viewSeg button').forEach(b=>{ b.classList.toggle('on', b.dataset.view===view); b.setAttribute('aria-selected', b.dataset.view===view); });
  $('#ownerLbl').textContent = data.owner ? '· '+data.owner : '';
  document.title = 'Work & Travel Calendar' + (data.owner?' — '+data.owner:'');
  let html='';
  if(view==='day') html=renderGrid([cursor]);
  else if(view==='3day') html=renderGrid(threeDays());
  else if(view==='week') html = weekCompact ? renderWeekCompact() : renderGrid(weekDays());
  else if(view==='agenda') html=renderAgenda();
  else if(view==='board') html=renderBoard();
  else html=renderMonth();
  $('#main').innerHTML = toolsHTML() + html + helpLine();
  bindTools(); bindMain();
  if(view==='day'||view==='3day'||(view==='week'&&!weekCompact)) bindGrid();
  renderSelbar();
}
function helpLine(){
  if(view==='board' || view==='agenda') return '';
  return `<div class="help">${editable
    ? (view==='month'||weekCompact ? 'Click a day to edit status, location, flights and its events and tasks. <kbd>Ctrl</kbd>/<kbd>⌘</kbd>/<kbd>Shift</kbd>-click selects several days. Drag items to another day.' : 'Drag on empty time to create an event or task. Drag items to move them, drag their lower edge to resize (<kbd>Alt</kbd> = 5-minute steps). Drop tasks from the right panel to schedule them. Click a day header for status, location and flights.')+' Press <kbd>?</kbd> for shortcuts.'
    : 'Read-only view — updates by itself every minute.'}</div>`;
}
function toolsHTML(){
  const grid = view==='day'||view==='3day'||view==='week';
  const tog=(k,l)=>`<label><input type="checkbox" data-ft="${k}" ${filt[k]?'checked':''}> ${l}</label>`;
  return `<div class="vtools">
    ${view==='week'?`<div class="seg"><button data-wc="0" class="${!weekCompact?'on':''}">Time grid</button><button data-wc="1" class="${weekCompact?'on':''}">Compact</button></div>`:''}
    ${grid&&!weekCompact?`<button data-zoom="-1" title="Smaller hours" aria-label="Zoom out">－</button><button data-zoom="1" title="Taller hours" aria-label="Zoom in">＋</button>`:''}
    ${['3day','week','month'].includes(view)?`<label title="Hide Saturday and Sunday in 3 days, Week and Month"><input type="checkbox" data-hidewe ${hideWE()?'checked':''}> Hide weekends</label>`:''}
    <label>Colour by <select data-colorby>${['project','calendar','priority'].map(c=>`<option ${prefs.colorBy===c?'selected':''}>${c}</option>`).join('')}</select></label>
    <label>Density <select data-density>${['comfortable','compact'].map(c=>`<option ${prefs.density===c?'selected':''}>${c}</option>`).join('')}</select></label>
    <details class="dropdown"><summary style="cursor:pointer;color:var(--muted)">Show ▾</summary><div class="menu open" style="display:block;padding:10px;min-width:210px">
      ${tog('showStatus','Work status')}<br>${tog('showFlights','Flights')}<br>${tog('showBlocks','Task blocks')}<br>${tog('showDue','Due dates')}<br>${tog('showDone','Completed tasks')}<br>${tog('showCancelled','Cancelled events')}</div></details>
    ${legendHTML()}
    ${filt.projects.length||filt.tag||filt.hiddenCals.length?`<span class="tag k">Filtered${filt.projects.length?': '+filt.projects.map(p=>p.startsWith('inbox:')?'Inbox':(proj(p)||{}).name).join(', '):''}${filt.tag?' #'+esc(filt.tag):''} <button class="ghost" data-clearf style="padding:0 3px">✕</button></span>`:''}
  </div>`;
}
/* Legend: a dropdown in the toolbar (was a row above the calendar) */
function legendHTML(){
  if(view==='board') return '';
  const sw=(bg,extra='')=>`<i class="sw" style="background:${bg};${extra}"></i>`;
  const row=(icon,label,hint)=>`<div class="lg-row"><span class="lg-ico">${icon}</span><span>${label}${hint?`<span class="hint"> · ${hint}</span>`:''}</span></div>`;
  return `<details class="dropdown legend-dd"><summary title="What the colours and symbols mean">ℹ Legend ▾</summary>
    <div class="menu open legend-menu" role="note" aria-label="Legend">
      <div class="lg-h">Work status</div>
      ${Object.entries(STATUSES).map(([k,s])=>row(sw(`var(--${k})`),esc(s.label))).join('')}
      <div class="lg-h">Day colours</div>
      ${row(sw('var(--fill-ath-tele)','border:1px solid var(--line2)'),'Athens + Telework')}
      ${row(sw('var(--fill-ath-abroad)','border:1px solid var(--line2)'),'Athens + Telework abroad')}
      ${row(sw('repeating-linear-gradient(-45deg,color-mix(in srgb,var(--leave) 30%,transparent) 0 3px,transparent 3px 6px)','border:1px solid var(--leave)'),'Leave / sick / holiday','shaded half of the day in the time grid')}
      <div class="lg-h">Location &amp; travel</div>
      ${row('<span class="loc">📍</span>','Location')}
      ${row('<span class="loc auto">📍</span>','Location from the last flight','dashed')}
      ${row('<span class="fl" style="padding:0 6px">✈</span>','Flight','click for details')}
      <div class="lg-h">Events &amp; tasks</div>
      ${row(sw('color-mix(in srgb,var(--accent) 25%,var(--paper))','border-left:3px solid var(--accent)'),'Event','colour of its project or calendar')}
      ${row(sw('repeating-linear-gradient(135deg,var(--accent) 0 3px,transparent 3px 6px)','border:1px solid var(--line2)'),'Task block','time planned for a task')}
      ${row('⚑','Task due')}
      ${row('<b style="color:var(--sick)">⚑</b>','Task overdue')}
      ${row('⚠','Conflict','overlap, during a flight, or during time off')}
      ${row('🔁','Repeating')}
      ${row('🔔','Has reminders')}
      ${row('🔒','Private (Personal)')}
      ${row(sw('repeating-linear-gradient(45deg,var(--busy) 0 3px,transparent 3px 6px)'),'Busy','Personal time, when Personal items are locked')}
    </div></details>`;
}
function bindTools(){
  const m=$('#main');
  $$('[data-wc]',m).forEach(b=>b.onclick=()=>{ weekCompact=b.dataset.wc==='1'; safeSet('wtc-weekc',weekCompact?'1':'0'); render(); });
  $$('[data-zoom]',m).forEach(b=>b.onclick=()=>{ prefs.hourPx=clamp(prefs.hourPx+(+b.dataset.zoom)*8,24,96); savePrefs(); render(); });
  const hw=$('[data-hidewe]',m); if(hw) hw.onchange=()=>{ prefs.hideWeekends=hw.checked; savePrefs(); _gridScroll=null; render(); renderSide(); };
  $('[data-colorby]',m).onchange=e=>{ prefs.colorBy=e.target.value; savePrefs(); renderAll(); };
  $('[data-density]',m).onchange=e=>{ prefs.density=e.target.value; savePrefs(); render(); };
  $$('[data-ft]',m).forEach(c=>c.onchange=()=>{ filt[c.dataset.ft]=c.checked; saveFilter(); renderAll(); });
  const cf=$('[data-clearf]',m); if(cf) cf.onclick=()=>{ filt.projects=[]; filt.tag=''; filt.hiddenCals=[]; saveFilter(); renderAll(); };
}

/* ---------- month ---------- */
function renderMonth(){
  const y=cursor.getFullYear(), m=cursor.getMonth();
  $('#title').textContent = MONTHS[m]+' '+y;
  const first=new Date(y,m,1), last=new Date(y,m+1,0);
  let d=mondayOf(first); const end=addDays(mondayOf(last),6);
  const today=todayKey();
  const conf=conflictsIn(occurrences(d.getTime(), addDays(end,1).getTime()));
  const nd=hideWE()?5:7;
  let html=`<div class="month ${hideWE()?'nowe':''}"><div class="mhead">`+DOW.slice(0,nd).map(x=>`<div>${x}</div>`).join('')+'</div>';
  while(d<=end){
    html+='<div class="mrow">';
    for(let i=0;i<7;i++){
      if(i>=nd){ d=addDays(d,1); continue; }
      const k=key(d), e=day(k), we=isWeekend(d);
      const cls=['cell', we?'we':'', d.getMonth()!==m?'out':'', k===today?'today':'', selected.has(k)?'sel':''].join(' ');
      const it=dayItems(k);
      const chips=[...it.overdue.map(t=>dueChip(t,true)), ...it.ad.map(o=>occChip(o,k)), ...it.tm.map(o=>occChip(o,k).replace('class="chipi','class="chipi'+(conf.has(o.id+'|'+o.occ)?' conflict':''))), ...it.due.map(t=>dueChip(t,false))];
      const max=innerWidth<900?2:3;
      html+=`<div class="${cls} ${fillClass(k,e,we)}" data-k="${k}">
        <div class="num"><b>${d.getDate()}</b>${i===0?`<span class="wk">W${isoWeek(d)}</span>`:''}</div>
        ${locHTML(k)}
        ${we||!filt.showStatus?'':statusHTML(e,true)}
        ${flightsHTML(e,k)}
        ${chips.slice(0,max).join('')}${chips.length>max?`<div class="more" data-moreday="${k}">+${chips.length-max} more</div>`:''}
        ${e.comment?`<div class="cm">${esc(e.comment)}</div>`:''}
      </div>`;
      d=addDays(d,1);
    }
    html+='</div>';
  }
  html+='</div>';
  // month notes + summary (unchanged)
  const mk=y+'-'+String(m+1).padStart(2,'0');
  const counts={}; let flights=0; const locs={}; let wd=0, filled=0;
  for(let x=new Date(first); x<=last; x=addDays(x,1)){
    const k=key(x), e=day(k);
    flights+=(e.flights||[]).length;
    const l=locationOf(k); if(l) locs[l.name]=(locs[l.name]||0)+1;
    if(isWeekend(x)) continue;
    wd++;
    for(const h of ['am','pm']) if(e[h]){ counts[e[h]]=(counts[e[h]]||0)+0.5; filled+=0.5; }
  }
  const fmt=n=>(Math.round(n*2)/2).toString().replace('.5','½').replace(/^0½$/,'½');
  const stats=Object.keys(STATUSES).map(s=>{ const n=counts[s]||0; return `<span><i class="sw" style="background:var(--${s})"></i> ${STATUSES[s].label}</span><div class="bar"><i style="width:${wd?n/wd*100:0}%;background:var(--${s})"></i></div><span class="n">${n?fmt(n):'–'}</span>`; }).join('');
  const locTxt=Object.entries(locs).sort((a,b)=>b[1]-a[1]).map(([n,c])=>`${esc(n)} ${c}`).join(' · ') || '—';
  html+=`<div class="below">
    <div class="panel"><h3>Notes for ${MONTHS[m]}</h3>
      <textarea id="monthNotes" data-mk="${mk}" placeholder="${editable?'Notes for this month…':'No notes'}" ${editable?'':'disabled'}>${esc(data.monthNotes[mk]||'')}</textarea></div>
    <div class="panel"><h3>${MONTHS[m]} summary · working days</h3>
      <div class="stats">${stats}</div>
      <div class="sub">${fmt(filled)} of ${wd} working days recorded · ${flights} flight${flights===1?'':'s'}<br>Days by location: ${locTxt}</div></div>
  </div>`;
  return html;
}

/* ---------- week, compact (the original card layout + items) ---------- */
function renderWeekCompact(){
  const mon=mondayOf(cursor), sun=addDays(mon,6);
  $('#title').textContent = weekTitle(mon,sun);
  const today=todayKey();
  let html=`<div class="week ${hideWE()?'nowe':''}">`;
  for(let i=0;i<(hideWE()?5:7);i++){
    const d=addDays(mon,i), k=key(d), e=day(k), we=i>=5;
    const fl=flightsHTML(e,k); const it=dayItems(k);
    const chips=[...it.overdue.map(t=>dueChip(t,true)), ...it.ad.map(o=>occChip(o,k)), ...it.tm.map(o=>occChip(o,k)), ...it.due.map(t=>dueChip(t,false))].join('');
    html+=`<div class="wcol ${we?'we':''} ${k===today?'today':''} ${selected.has(k)?'sel':''} ${fillClass(k,e,we)}" data-k="${k}">
      <div class="dh"><span class="dn">${DOW[i]}</span><span class="dd">${d.getDate()}</span></div>
      <div><div class="lbl">Location</div>${locHTML(k)||'<span class="empty">—</span>'}</div>
      ${we||!filt.showStatus?'':`<div><div class="lbl">Morning</div>${e.am?`<div class="st ${e.am}">${STATUSES[e.am].label}</div>`:'<div class="st none">—</div>'}</div>
               <div><div class="lbl">Evening</div>${e.pm?`<div class="st ${e.pm}">${STATUSES[e.pm].label}</div>`:'<div class="st none">—</div>'}</div>`}
      <div><div class="lbl">Travel</div>${fl||'<span class="empty">No flights</span>'}</div>
      <div><div class="lbl">Events &amp; tasks</div><div style="display:flex;flex-direction:column;gap:3px">${chips||'<span class="empty">—</span>'}</div></div>
      <div style="flex:1"><div class="lbl">Comments</div>${e.comment?`<div class="cm">${esc(e.comment)}</div>`:'<span class="empty">—</span>'}</div>
    </div>`;
  }
  return html+'</div>';
}
function weekTitle(mon,sun){ const sameM=mon.getMonth()===sun.getMonth(); return `Week ${isoWeek(mon)} · ${mon.getDate()}${sameM?'':' '+MONTHS[mon.getMonth()].slice(0,3)}–${sun.getDate()} ${MONTHS[sun.getMonth()].slice(0,3)} ${sun.getFullYear()}`; }

/* ---------- time grid (Day / Week) ---------- */
let gridDays=[], gridFocus=null;
function layoutLanes(items){ // classic overlap layout: returns item→{lane,lanes}
  const res=new Map(); let cluster=[], clusterEnd=-1;
  const flush=()=>{ const lanes=[]; for(const o of cluster){ let i=lanes.findIndex(e=>e<=o._s); if(i<0){ i=lanes.length; lanes.push(0); } lanes[i]=o._e; res.set(o,{lane:i}); } for(const o of cluster) res.get(o).lanes=lanes.length; cluster=[]; };
  for(const o of items){ if(o._s>=clusterEnd && cluster.length) flush(); cluster.push(o); clusterEnd=Math.max(cluster.length===1?o._e:clusterEnd,o._e); }
  if(cluster.length) flush();
  return res;
}
function renderGrid(days){
  gridDays=days.map(d=>key(d));
  const n=days.length, hp=hourPx(), tk=todayKey(), now=NOW();
  $('#title').textContent = n===1 ? fmtLong(days[0]) : view==='week' ? weekTitle(mondayOf(days[0]),addDays(mondayOf(days[0]),6)) : `${fmtDM(days[0])} – ${fmtDM(days[n-1])} ${days[n-1].getFullYear()}`;
  const cols=`52px repeat(${n},minmax(0,1fr))`;
  const ws=hmToMin(S().workStart)||540, we=hmToMin(S().workEnd)||1080;
  const r0=fromKey(gridDays[0]).getTime(), r1=addDays(fromKey(gridDays[n-1]),1).getTime();
  const allOcc=occurrences(r0,r1);
  const conf=conflictsIn(allOcc);
  // header
  let head=`<div class="tg-head" style="grid-template-columns:${cols}"><div class="tg-gut">W${isoWeek(days[0])}<br><span title="Active time zone ${esc(ACTIVE_TZ)}">${esc(tzShort(ACTIVE_TZ))}</span></div>`;
  let all=`<div class="tg-all" style="grid-template-columns:${cols}"><div class="tg-gut">all-day</div>`;
  let body=`<div class="tg-grid" style="grid-template-columns:${cols};height:${24*hp}px"><div class="tg-hours">`;
  for(let h=0;h<24;h++){
    body+=`<div>${h?String(h).padStart(2,'0')+':00':''}</div>`;
  }
  body+='</div>';
  days.forEach((d,i)=>{
    const k=gridDays[i], e=day(k), wk=isWeekend(d), fill=fillClass(k,e,wk);
    head+=`<div class="tg-hcell ${wk?'we':''} ${k===tk?'today':''} ${fill}" data-k="${k}" title="Status, location, flights, comments">
      <div class="dh"><span class="dn">${DOW[dow(d)]}</span><span class="dd">${d.getDate()}</span>${n>1?'':`<span class="hint">${MONTHS[d.getMonth()]}</span>`}</div>
      ${locHTML(k)}${wk||!filt.showStatus?'':statusHTML(e,true)}${e.comment&&n===1?`<div class="cm">${esc(e.comment)}</div>`:''}</div>`;
    const it=dayItems(k);
    all+=`<div class="tg-allc ${wk?'we':''}" data-allk="${k}">${it.overdue.map(t=>dueChip(t,true)).join('')}${it.ad.map(o=>occChip(o,k)).join('')}${it.due.map(t=>dueChip(t,false)).join('')}</div>`;
    // timed items in this column
    const d0=fromKey(k).getTime(), d1=addDays(fromKey(k),1).getTime();
    const fls=filt.showFlights?[keyAdd(k,-1),k,keyAdd(k,1)].flatMap(fk=>flightWindows(fk)).filter(w=>w.e>d0&&w.s<d1).map(w=>({kind:'flight', s:w.s, e:w.e, f:w.f, k:w.k, fi:w.i, title:'✈ '+code(w.f.from)+' → '+code(w.f.to)})):[];
    const items=[...it.tm,...fls].map(o=>Object.assign(Object.create(null),{o,_s:Math.max(o.s,d0),_e:Math.min(o.e,d1)})).filter(x=>x._e>x._s).sort((a,b)=>a._s-b._s||b._e-a._e);
    const lanes=layoutLanes(items);
    const workDay=S().workDays.includes(dow(d));
    const off=workDay?`<div class="tg-off" style="top:0;height:${ws/60*hp}px"></div><div class="tg-off" style="top:${we/60*hp}px;bottom:0"></div>`:`<div class="tg-off" style="top:0;bottom:0"></div>`;
    let colHtml='';
    for(const x of items){
      const o=x.o, L=lanes.get(x), top=(x._s-d0)/60000*hp/60, h=Math.max(16,(x._e-x._s)/60000*hp/60-2);
      const w=100/L.lanes, left=L.lane*w;
      colHtml+=evBlockHTML(o, top, h, left, w, conf.get(o.id+'|'+o.occ));
    }
    const nowLine = k===tk ? `<div class="tg-now" style="top:${(now-d0)/60000*hp/60}px"></div>` : '';
    const leave=offIntervals(k).map(x=>`<div class="tg-leave" style="top:${x.a/60*hp}px;height:${(x.b-x.a)/60*hp}px" title="${esc(offLabel(x))}"><span>${esc(STATUSES[x.st].label)} · ${x.half}</span></div>`).join('');
    body+=`<div class="tg-col ${wk?'we':''}" data-col="${k}" data-ci="${i}">${off}${leave}${colHtml}${nowLine}</div>`;
  });
  head+='</div>'; all+='</div>'; body+='</div>';
  return `<div class="tg" role="grid" aria-label="Calendar time grid">${head}${all}<div class="tg-body" id="tgBody" tabindex="0" aria-label="Time grid. Arrow keys move, Enter creates an item">${body}</div></div>`;
}
function evBlockHTML(o, top, h, left, w, conf){
  if(o.kind==='flight'){ const f=o.f;
    return `<div class="ev flt" data-fli="${o.fi}" style="top:${top}px;height:${h}px;left:calc(${left}% + 2px);width:calc(${w}% - 4px)" data-flk="${o.k}" tabindex="0" role="button" aria-label="Flight ${esc(f.from)} to ${esc(f.to)}, ${esc(flightLabel(f))}" title="${esc(f.from)} → ${esc(f.to)} · ${esc(flightLabel(f))}${f.note?' · '+esc(f.note):''} — click to edit">
      <div class="tm">${hm(o.s)}–${hm(o.e)==='00:00'?'24:00':hm(o.e)}${o.k!==key(new Date(o.s))||flightTz(f)!==ACTIVE_TZ?` · ${esc(minToHM(flightMins(f).a))} ${esc(tzShort(flightTz(f)))}`:''}${flightAlarmMin(f)!==null?' ⏰':''}</div><span class="ti">✈ ${esc(code(f.from))} → ${esc(code(f.to))}</span>${h>44&&f.note?`<div class="lo">${esc(f.note)}</div>`:''}</div>`; }
  if(o.kind==='busy') return `<div class="ev busy" style="top:${top}px;height:${h}px;left:calc(${left}% + 2px);width:calc(${w}% - 4px)" title="Busy (Personal items are locked)"><span class="ti">Busy</span></div>`;
  const it=o.item, c=itemColor(it), task=o.kind==='block';
  const cls=['ev', task?'task p'+(it.priority||3):'', o.done?'done':'', o.missed?'missed':'', o.cancelled?'cancelled':'', (!task&&it.showAs==='tentative')?'tentative':'', conf?'conflict':''].join(' ');
  const tm=`${hm(o.s)}–${hm(o.e)}`;
  const icons=[it.rrule?'🔁':'', isPrivate(it)?'🔒':'', (it.reminders||[]).length?'🔔':'', task&&blockedBy(it).length?'⛓':''].join('');
  const label=`${task?'Task':'Event'}${task&&it.priority?', P'+it.priority:''}, ${o.title}, ${tm}${conf?', conflict: '+conf.join('; '):''}`;
  const small=h<30;
  return `<div class="${cls}" style="--c:${esc(c)};top:${top}px;height:${h}px;left:calc(${left}% + 2px);width:calc(${w}% - 4px)" data-occ="${esc(o.id)}" data-oc="${esc(o.occ)}" data-ok="${o.kind}" tabindex="0" role="button" aria-label="${esc(label)}" title="${esc(label)}">
    ${task?`<button class="check ${o.done?'on':''}" data-bdone="${esc(o.id)}|${esc(o.occ)}" aria-label="Block done" ${editable?'':'disabled'}>${o.done?'✓':''}</button>`:''}${small?`<span class="tm">${hm(o.s)}</span> `:`<div class="tm">${tm} ${icons}</div>`}<span class="ti">${esc(CAT_ICON[it.category]||'')}${esc(o.title)}</span>${!small&&o.location?`<div class="lo">📍 ${esc(o.location)}</div>`:''}${!small&&task&&it.estimateMin?`<div class="lo">${fmtH(plannedMin(it))} of ${fmtH(it.estimateMin)}</div>`:''}
    ${editable && !o.cancelled?'<div class="rz" data-rz="1"></div>':''}</div>`;
}

/* ---------- agenda ---------- */
function renderAgenda(){
  const start=cursor, end=addDays(cursor,agendaDays-1);
  $('#title').textContent = `${fmtDM(start)} – ${fmtDM(end)} ${end.getFullYear()}`;
  const tk=todayKey();
  let html='<div class="ag">';
  for(let i=0;i<agendaDays;i++){
    const d=addDays(start,i), k=key(d), e=day(k), it=dayItems(k);
    const rows=[];
    for(const [fi,f] of (filt.showFlights?(e.flights||[]):[]).entries()) rows.push(`<div class="agi" data-flk="${k}" data-fli="${fi}" tabindex="0"><span class="t">${esc(flightDisp(k,f))}</span><div class="b" style="--c:var(--accent)"><span class="ti">✈ ${esc(f.from)} → ${esc(f.to)}</span>${f.note?`<div class="s">${esc(f.note)}</div>`:''}</div></div>`);
    for(const t of it.overdue) rows.push(agTaskRow(t,'Overdue'));
    for(const o of it.ad) rows.push(agOccRow(o,'All day'));
    for(const o of it.tm) rows.push(agOccRow(o,`${hm(o.s)}–${hm(o.e)}`));
    for(const t of it.due) rows.push(agTaskRow(t,'Due'+(t.dueTime?' '+t.dueTime:'')));
    if(!rows.length && !e.am && !e.comment && i>0 && !(k===tk)) continue;
    html+=`<div class="agd ${k===tk?'today':''} ${fillClass(k,e,isWeekend(d))?'':''}"><h3 data-k="${k}" style="cursor:pointer">${esc(fmtLong(d))} <span class="w">W${isoWeek(d)}</span>${locHTML(k)}${isWeekend(d)||!filt.showStatus?'':statusHTML(e,true).replace('class="halves"','class="halves" style="flex-direction:row;gap:4px"')}</h3>
      ${rows.join('')||'<div class="hint">Nothing planned.</div>'}${e.comment?`<div class="cm" style="margin-top:6px">💬 ${esc(e.comment)}</div>`:''}</div>`;
  }
  html+=`<button data-agmore>Show ${agendaDays} more days</button></div>`;
  return html;
}
function agOccRow(o,t){
  if(o.kind==='busy') return `<div class="agi" style="cursor:default"><span class="t">${t}</span><div class="b" style="--c:var(--busy)"><span class="ti">Busy</span></div></div>`;
  const it=o.item; const p=proj(it.projectId);
  return `<div class="agi ${o.kind==='block'?'task':''} ${o.done?'done':''}" data-occ="${esc(o.id)}" data-oc="${esc(o.occ)}" data-ok="${o.kind}" tabindex="0"><span class="t">${t}</span><div class="b" style="--c:${esc(itemColor(it))}"><span class="ti">${o.kind==='block'?'☐ ':''}${esc(CAT_ICON[it.category]||'')}${o.cancelled?'<s>':''}${esc(o.title)}${o.cancelled?'</s>':''}</span><div class="s">${[p?esc(p.name):'',o.location?'📍 '+esc(o.location):'',it.rrule?'🔁':'' ,o.missed?'<b style="color:var(--missed)">missed</b>':''].filter(Boolean).join(' · ')}</div></div></div>`;
}
function agTaskRow(t,label){
  const p=proj(t.projectId);
  return `<div class="agi task ${isOpen(t)?'':'done'}" data-task="${t.id}" tabindex="0"><span class="t" style="${label==='Overdue'?'color:var(--sick);font-weight:700':''}">⚑ ${esc(label)}</span><div class="b" style="--c:${esc(itemColor(t))}"><span class="ti">${esc(t.title)}</span><div class="s">${[p?esc(p.name):'', t.priority<=2?'P'+t.priority:''].filter(Boolean).join(' · ')}</div></div></div>`;
}

/* ---------- board ---------- */
function renderBoard(){
  $('#title').textContent='Tasks board';
  const lanes=prefs.boardLanes;
  const list=data.tasks.filter(passFilter);
  const cols=Object.keys(TASK_STATUS);
  const card=t=>taskRowHTML(t);
  let html=`<div class="vtools"><label><input type="checkbox" data-lanes ${lanes?'checked':''}> Swimlanes by project</label></div><div class="board">`;
  for(const s of cols){
    let inner='';
    const items=sortTasks(list.filter(t=>(t.status||'todo')===s), 'priority');
    if(lanes){ const g=new Map(); for(const t of items){ const p=proj(t.projectId); const n=p?p.name:'Inbox'; if(!g.has(n)) g.set(n,{p,ts:[]}); g.get(n).ts.push(t); }
      for(const [n,{p,ts}] of g) inner+=`<div class="lane"><i class="dotc" style="background:${esc(p?p.color:'var(--line2)')}"></i>${esc(n)}</div>`+ts.map(card).join(''); }
    else inner=items.slice(0, s==='done'||s==='cancelled'?40:500).map(card).join('');
    html+=`<div class="bcol" data-status="${s}"><h4><span>${TASK_STATUS[s]}</span><span>${items.length}</span></h4>${inner}${editable&&s!=='done'&&s!=='cancelled'?`<button class="ghost" data-bnew="${s}" style="text-align:left">＋ Add task</button>`:''}</div>`;
  }
  return html+'</div>';
}

/* ---------- main bindings ---------- */
function bindMain(){
  const M=$('#main');
  // day cells / headers → drawer (with multi-select for bulk status)
  $$('[data-k]',M).forEach(el=>el.addEventListener('click', ev=>{
    if(ev.target.closest('[data-occ],[data-task],[data-moreday]')) return;
    const k=el.dataset.k;
    if(editable && (ev.ctrlKey||ev.metaKey||ev.shiftKey)){
      ev.preventDefault();
      if(ev.shiftKey && lastClicked){ let a=fromKey(lastClicked), b=fromKey(k); if(a>b) [a,b]=[b,a]; for(let x=a;x<=b;x=addDays(x,1)) selected.add(key(x)); }
      else { selected.has(k)?selected.delete(k):selected.add(k); }
      lastClicked=k; render(); return;
    }
    lastClicked=k;
    if(selected.size){ selected.clear(); render(); }
    openDrawer(k);
  }));
  $$('[data-moreday]',M).forEach(el=>el.onclick=e=>{ e.stopPropagation(); cursor=fromKey(el.dataset.moreday); setView('day'); });
  // items
  $$('[data-occ]',M).forEach(el=>{
    if(el.classList.contains('ev')) return; // grid blocks: handled by pointer logic
    el.addEventListener('click',e=>{ e.stopPropagation(); openOccPopover(el.dataset.occ, el.dataset.oc, el.dataset.ok, el); });
    el.addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); openOccPopover(el.dataset.occ, el.dataset.oc, el.dataset.ok, el); } });
    el.addEventListener('dragstart',e=>{ e.dataTransfer.setData('text/x-item', el.dataset.occ); e.dataTransfer.setData('text/x-occ', el.dataset.occ+'|'+el.dataset.oc+'|'+el.dataset.ok); });
  });
  $$('[data-task]',M).forEach(el=>{
    if(el.classList.contains('trow')) return;
    el.addEventListener('click',e=>{ e.stopPropagation(); openTaskEditor(el.dataset.task); });
    el.addEventListener('keydown',e=>{ if(e.key==='Enter') openTaskEditor(el.dataset.task); });
    el.addEventListener('dragstart',e=>{ e.dataTransfer.setData('text/x-task', el.dataset.task); e.dataTransfer.setData('text/x-due', el.dataset.task); });
  });
  bindTaskRows(M);
  $$('[data-flk]',M).forEach(el=>{ if(!el.dataset.flk) return;
    const go=e=>{ e.stopPropagation(); openFlight(el.dataset.flk, +el.dataset.fli); };
    el.addEventListener('click',go); el.addEventListener('keydown',e=>{ if(e.key==='Enter') go(e); }); });
  // drop targets: month cells, compact week columns, all-day cells
  $$('.cell[data-k],.wcol[data-k],[data-allk]',M).forEach(c=>{
    const k=c.dataset.k||c.dataset.allk;
    c.addEventListener('dragover',e=>{ if(!editable) return; e.preventDefault(); c.classList.add('dragover'); });
    c.addEventListener('dragleave',()=>c.classList.remove('dragover'));
    c.addEventListener('drop',e=>{ c.classList.remove('dragover'); if(!editable) return; e.preventDefault(); dropOnDay(e.dataTransfer, k); });
  });
  // board
  $$('.bcol',M).forEach(c=>{
    c.addEventListener('dragover',e=>{ if(!editable) return; e.preventDefault(); c.classList.add('dragover'); });
    c.addEventListener('dragleave',()=>c.classList.remove('dragover'));
    c.addEventListener('drop',e=>{ c.classList.remove('dragover'); if(!editable) return; e.preventDefault(); const id=e.dataTransfer.getData('text/x-task'); const t=task(id); if(!t) return; setTaskStatus(t, c.dataset.status); });
  });
  $$('[data-bnew]',M).forEach(b=>b.onclick=()=>openTaskEditor(null,{status:b.dataset.bnew}));
  const lanes=$('[data-lanes]',M); if(lanes) lanes.onchange=()=>{ prefs.boardLanes=lanes.checked; savePrefs(); render(); };
  const more=$('[data-agmore]',M); if(more) more.onclick=()=>{ agendaDays+=14; render(); };
  const mn=$('#monthNotes');
  if(mn) mn.addEventListener('input',()=>{ const v=mn.value; if(v) data.monthNotes[mn.dataset.mk]=v; else delete data.monthNotes[mn.dataset.mk]; changed('mn'); });
}
/* drag & drop onto a whole day (month, compact week, all-day strip) */
function dropOnDay(dt, k){
  const occ=dt.getData('text/x-occ'), due=dt.getData('text/x-due'), tid=dt.getData('text/x-task');
  if(occ){
    const [id,oc,kind]=occ.split('|');
    if(kind==='block'){ const t=task(id), b=t&&t.blocks.find(x=>x.id===oc); if(!b) return; const dd=Math.round((fromKey(k)-fromKey(b.start.slice(0,10)))/864e5); b.start=localAddMin(b.start,dd*1440); b.end=localAddMin(b.end,dd*1440); touch(t); changed(); return renderAll(); }
    const e=ev(id); if(!e) return;
    if(e.rrule) return toast('Open the event to move a repeating event.');
    const dd=Math.round((fromKey(k)-fromKey(e.start.slice(0,10)))/864e5);
    if(e.allDay){ e.start=keyAdd(e.start.slice(0,10),dd); e.end=keyAdd((e.end||e.start).slice(0,10),dd); }
    else { e.start=localAddMin(e.start,dd*1440); e.end=localAddMin(e.end,dd*1440); }
    touch(e); changed(); renderAll(); return;
  }
  if(due || tid){
    const t=task(due||tid); if(!t) return;
    t.dueDate=k; touch(t); changed(); renderAll(); toast(`“${t.title}” is now due ${fmtShort(fromKey(k))}`);
  }
}

/* ============================================================
   Time grid interaction: create by drag, move, resize, drop tasks,
   keyboard navigation
   ============================================================ */
function minFromY(col, clientY, alt){
  const r=col.getBoundingClientRect(), hp=hourPx();
  const m=(clientY-r.top)/hp*60, step=alt?5:15;
  return clamp(Math.round(m/step)*step,0,24*60);
}
function colAt(x){
  const cols=$$('.tg-col'); for(const c of cols){ const r=c.getBoundingClientRect(); if(x>=r.left && x<r.right) return c; }
  return null;
}
function ghost(col, a, b, label){
  let g=$('.tg-ghost'); if(!g){ g=document.createElement('div'); g.className='tg-ghost'; }
  if(g.parentNode!==col) col.appendChild(g);
  const hp=hourPx(); g.style.top=(a/60*hp)+'px'; g.style.height=Math.max(10,(b-a)/60*hp)+'px'; g.textContent=label||(minToHM(a)+'–'+minToHM(b));
}
function clearGhost(){ const g=$('.tg-ghost'); if(g) g.remove(); }
function scrollGridToWork(){
  const b=$('#tgBody'); if(!b) return;
  if(b.dataset.scrolled) return;
  const ws=hmToMin(S().workStart)||540; const now=new Date(NOW()); const nowMin=now.getHours()*60+now.getMinutes();
  let target=ws-60; const visMin=b.clientHeight/hourPx()*60;
  if(gridDays.includes(todayKey()) && nowMin>target+visMin-60) target=nowMin-visMin+120;
  b.scrollTop=Math.max(0,target/60*hourPx());
}
let _gridScroll=null;
function bindGrid(){
  const body=$('#tgBody'); if(!body) return;
  if(_gridScroll!==null && _gridScroll.view===view+cursor.getTime()) body.scrollTop=_gridScroll.top; else scrollGridToWork();
  body.addEventListener('scroll',()=>{ _gridScroll={view:view+cursor.getTime(), top:body.scrollTop}; });
  // create by drag on empty column space
  $$('.tg-col',body).forEach(col=>{
    col.addEventListener('pointerdown',e=>{
      if(!editable || e.button!==0 || e.target.closest('.ev')) return;
      e.preventDefault();
      const k=col.dataset.col, a0=minFromY(col,e.clientY,e.altKey); let a=a0, b=a0+15, moved=false;
      col.setPointerCapture(e.pointerId);
      ghost(col,a,b);
      const mv=ev2=>{ const m=minFromY(col,ev2.clientY,ev2.altKey); if(Math.abs(m-a0)>=5) moved=true; a=Math.min(a0,m); b=Math.max(a0+ (m>=a0?0:0), m); if(b-a<15) b=a+15; ghost(col,a,b); };
      const up=()=>{ col.removeEventListener('pointermove',mv); col.removeEventListener('pointerup',up); col.removeEventListener('pointercancel',up);
        if(!moved){ a=a0; b=a0+(S().defaultEventMin||60); }
        ghost(col,a,b);
        quickCreateAt(k,a,b,col);
      };
      col.addEventListener('pointermove',mv); col.addEventListener('pointerup',up); col.addEventListener('pointercancel',up);
    });
    // HTML5 drop of a task from the tasks panel → new time block
    col.addEventListener('dragover',e=>{ if(!editable) return; const types=[...e.dataTransfer.types]; if(!types.includes('text/x-task')&&!types.includes('text/x-occ')) return; e.preventDefault(); const m=minFromY(col,e.clientY,e.altKey); ghost(col,m,m+(S().defaultBlockMin||60),'Drop to schedule at '+minToHM(m)); });
    col.addEventListener('dragleave',e=>{ if(!col.contains(e.relatedTarget)) clearGhost(); });
    col.addEventListener('drop',e=>{
      clearGhost(); if(!editable) return; e.preventDefault();
      const m=minFromY(col,e.clientY,e.altKey), k=col.dataset.col;
      const tid=e.dataTransfer.getData('text/x-task');
      if(tid){ scheduleTaskAt(task(tid), k, m); return; }
      const occ=e.dataTransfer.getData('text/x-occ');
      if(occ){ const [id,oc,kind]=occ.split('|'); const it=findItem(id); if(!it) return;
        if(kind==='event' && it.allDay){ // all-day → timed at drop time
          const tz=ACTIVE_TZ; it.allDay=false; it.tz=tz; it.start=k+'T'+minToHM(m); it.end=localAddMin(it.start,S().defaultEventMin||60); touch(it); changed(); renderAll(); }
      }
    });
  });
  // flights: click opens the day to edit times
  $$('.ev.flt',body).forEach(el=>{ el.onclick=()=>openFlight(el.dataset.flk,+el.dataset.fli); el.onkeydown=e=>{ if(e.key==='Enter') openFlight(el.dataset.flk,+el.dataset.fli); }; el.addEventListener('pointerdown',e=>e.stopPropagation()); });
  // items: click, move, resize
  $$('.ev[data-occ]',body).forEach(el=>{
    el.addEventListener('keydown',e=>{
      if(e.key==='Enter'){ e.preventDefault(); openOccPopover(el.dataset.occ, el.dataset.oc, el.dataset.ok, el); }
      if(e.key===' ' && el.dataset.ok==='block' && editable){ e.preventDefault(); toggleBlockDone(el.dataset.occ, el.dataset.oc); }
      if(e.key==='Delete' && editable){ e.preventDefault(); deleteOcc(el.dataset.occ, el.dataset.oc, el.dataset.ok); }
    });
    el.addEventListener('focus',()=>{ selItem={kind:el.dataset.ok,id:el.dataset.occ,occ:el.dataset.oc}; });
    const bd=$('[data-bdone]',el); if(bd){ bd.addEventListener('pointerdown',e=>e.stopPropagation()); bd.onclick=e=>{ e.stopPropagation(); const [id,oc]=bd.dataset.bdone.split('|'); toggleBlockDone(id,oc); }; }
    el.addEventListener('pointerdown',e=>{
      if(e.button!==0 || e.target.closest('[data-bdone]')) return;
      const resize=!!e.target.closest('[data-rz]');
      const id=el.dataset.occ, oc=el.dataset.oc, kind=el.dataset.ok;
      const o=findOcc(id,oc,kind); if(!o) return;
      const startCol=el.closest('.tg-col'), x0=e.clientX, y0=e.clientY;
      const d0=fromKey(startCol.dataset.col).getTime();
      const sMin=(o.s-d0)/60000, eMin=(o.e-d0)/60000, dur=eMin-sMin;
      const grab=minFromY(startCol,e.clientY,true)-sMin;
      let dragging=false, tCol=startCol, ns=sMin, ne=eMin;
      el.setPointerCapture(e.pointerId);
      const mv=ev2=>{
        if(!dragging && Math.hypot(ev2.clientX-x0, ev2.clientY-y0)<5) return;
        if(!editable || o.cancelled) return;
        if(!dragging){ dragging=true; el.classList.add('dragging'); }
        if(resize){ ne=Math.max(sMin+15, minFromY(startCol,ev2.clientY,ev2.altKey)); ns=sMin; ghost(startCol,ns,ne); }
        else { tCol=colAt(ev2.clientX)||tCol; const m=minFromY(tCol,ev2.clientY,true)-grab; const step=ev2.altKey?5:15; ns=clamp(Math.round(m/step)*step,0,24*60-15); ne=ns+dur; ghost(tCol,ns,ne); }
      };
      const up=()=>{
        el.removeEventListener('pointermove',mv); el.removeEventListener('pointerup',up); el.removeEventListener('pointercancel',up);
        el.classList.remove('dragging');
        if(!dragging){ clearGhost(); selItem={kind,id,occ:oc}; openOccPopover(id,oc,kind,el); return; }
        clearGhost();
        const nd0=fromKey(tCol.dataset.col).getTime();
        moveOcc(o, nd0+ns*60000, nd0+ne*60000);
      };
      el.addEventListener('pointermove',mv); el.addEventListener('pointerup',up); el.addEventListener('pointercancel',up);
    });
  });
  // keyboard navigation over slots
  body.addEventListener('keydown',e=>{
    if(e.target!==body) return;
    if(!gridFocus || !gridDays.includes(gridFocus.k)) gridFocus={k:gridDays.includes(todayKey())?todayKey():gridDays[0], m:hmToMin(S().workStart)||540};
    let {k,m}=gridFocus, hit=true;
    if(e.key==='ArrowUp') m=Math.max(0,m-30); else if(e.key==='ArrowDown') m=Math.min(23*60+30,m+30);
    else if(e.key==='ArrowLeft'){ const i=gridDays.indexOf(k); if(i>0) k=gridDays[i-1]; else { move(-1); return; } }
    else if(e.key==='ArrowRight'){ const i=gridDays.indexOf(k); if(i<gridDays.length-1) k=gridDays[i+1]; else { move(1); return; } }
    else if(e.key==='Enter'){ if(editable){ const col=$(`.tg-col[data-col="${k}"]`); quickCreateAt(k,m,m+(S().defaultEventMin||60),col); } }
    else hit=false;
    if(!hit) return;
    e.preventDefault(); e.stopPropagation();
    gridFocus={k,m}; drawGridFocus();
  });
  body.addEventListener('focus',()=>drawGridFocus());
  body.addEventListener('blur',()=>{ const f=$('.tg-focus'); if(f) f.remove(); });
}
function drawGridFocus(){
  if(!gridFocus) return; const col=$(`.tg-col[data-col="${gridFocus.k}"]`); if(!col) return;
  let f=$('.tg-focus'); if(!f){ f=document.createElement('div'); f.className='tg-focus'; }
  col.appendChild(f); f.style.top=(gridFocus.m/60*hourPx())+'px';
  const live=$('#gridLive')||Object.assign(document.createElement('div'),{id:'gridLive',className:'sr'}); live.setAttribute('aria-live','polite'); document.body.appendChild(live);
  live.textContent=fmtShort(fromKey(gridFocus.k))+' '+minToHM(gridFocus.m);
  const body=$('#tgBody'), y=gridFocus.m/60*hourPx(); if(y<body.scrollTop || y>body.scrollTop+body.clientHeight-40) body.scrollTop=y-80;
}
function findOcc(id, oc, kind){
  const it=findItem(id); if(!it) return null;
  if(kind==='block') return blockOccs(it,-1e14,1e14).find(o=>o.occ===oc)||null;
  if(!it.rrule){ return expandEvent(it, -1e14, 1e14)[0]; }
  const dk=oc; const occs=expandEvent(it, fromKey(dk).getTime()-40*864e5, fromKey(dk).getTime()+40*864e5);
  return occs.find(o=>o.occ===dk)||null;
}
/* move/resize an occurrence to new absolute times */
function moveOcc(o, s, e){
  const it=o.item;
  if(o.kind==='block'){ const b=o.block||it.blocks.find(x=>x.id===o.occ); const tz=b.tz||BROWSER_TZ; b.start=msToZoned(s,tz); b.end=msToZoned(e,tz);
    const dep=blockedBy(it).find(d=>d.blocks.some(x=>zonedToMs(x.end,x.tz)>s)); touch(it); changed(); renderAll();
    if(dep) toast(`⚠ “${it.title}” depends on “${dep.title}”, which has time scheduled after this block.`);
    return; }
  const tz=it.tz||BROWSER_TZ;
  if(it.rrule){
    it.exceptions[o.occ]=Object.assign({}, it.exceptions[o.occ]||{}, {start:msToZoned(s,tz), end:msToZoned(e,tz)});
    touch(it); changed(); renderAll(); toast('Moved this occurrence only. Open the event to change the whole series.'); return;
  }
  it.start=msToZoned(s,tz); it.end=msToZoned(e,tz); touch(it); changed(); renderAll();
}
function scheduleTaskAt(t, k, m){
  if(!t) return;
  const rem=remainingMin(t)-futurePlannedMin(t);
  const len = t.estimateMin && rem>0 ? Math.min(rem, (S().defaultBlockMin||60)*2) : (S().defaultBlockMin||60);
  const tz=ACTIVE_TZ, start=k+'T'+minToHM(m);
  const b={id:uid('b'), start, end:localAddMin(start, Math.max(15,Math.round(len/15)*15)), tz, done:false};
  const dep=blockedBy(t).find(d=>d.blocks.some(x=>zonedToMs(x.end,x.tz)>zonedToMs(b.start,tz)));
  t.blocks.push(b); if(t.status==='todo') t.status='todo'; touch(t); changed(); renderAll();
  toast(`Scheduled “${t.title}” ${fmtShort(fromKey(k))} ${minToHM(m)}–${b.end.slice(11)}`+(dep?` · ⚠ depends on “${dep.title}”`:''), {actions:[['Undo',undo]]});
}

/* quick create popover at a slot: title + Event / Task */
function quickCreateAt(k, a, b, col){
  closePopover();
  const r=col?col.getBoundingClientRect():{left:innerWidth/2-170,right:innerWidth/2+170,top:120};
  const hp=hourPx(); const y=(col?r.top:0)+a/60*hp;
  const p=document.createElement('div'); p.className='pop'; p.id='pop';
  p.innerHTML=`<form><div class="hint" style="margin-bottom:6px">${esc(fmtShort(fromKey(k)))} · ${minToHM(a)}–${minToHM(b)}</div>
    <input type="text" id="qcT" placeholder="Title" autocomplete="off" aria-label="Title">
    <div class="row" style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap">
      <select id="qcC" aria-label="Calendar">${data.calendars.map(c=>`<option value="${c.id}" ${c.id==='work'?'selected':''}>${esc(c.name)}</option>`).join('')}</select>
      <select id="qcP" aria-label="Project"></select></div>
    <div class="acts"><button class="primary" type="submit" data-k="event">📅 Event</button><button type="button" data-qt="task">☑ Task block</button><button type="button" data-more>More options…</button><button type="button" class="ghost" data-x>Cancel</button></div></form>`;
  document.body.appendChild(p); placePop(p, {left:r.right+6, right:r.left-6, top:y});
  const fillP=()=>{ const c=$('#qcC',p).value; $('#qcP',p).innerHTML=`<option value="">Inbox</option>`+projectsOf(c).filter(x=>x.status==='active').map(x=>`<option value="${x.id}">${esc(x.name)}</option>`).join(''); };
  fillP(); $('#qcC',p).onchange=fillP;
  const tz=ACTIVE_TZ, st=k+'T'+minToHM(a), en=b>=24*60?keyAdd(k,1)+'T00:00':k+'T'+minToHM(b);
  const make=(type, more)=>{
    const title=$('#qcT',p).value.trim(), calendarId=$('#qcC',p).value, projectId=$('#qcP',p).value||null;
    closePopover();
    if(type==='event'){
      const e=newEvent({title:title||'New event', calendarId, projectId, start:st, end:en, tz});
      if(more || !title) return openEventEditor(null, e);
      if(!saveNewItem(e)) return;
      toast(`Created “${e.title}”`, {actions:[['Undo',undo],['Edit',()=>openEventEditor(e.id)]]});
    } else {
      const t=newTask({title:title||'New task', calendarId, projectId, estimateMin:b-a});
      t.blocks.push({id:uid('b'), start:st, end:en, tz, done:false});
      if(more || !title) return openTaskEditor(null, t);
      if(!saveNewItem(t)) return;
      toast(`Created task “${t.title}”`, {actions:[['Undo',undo],['Edit',()=>openTaskEditor(t.id)]]});
    }
  };
  $('form',p).onsubmit=e=>{ e.preventDefault(); make('event'); };
  $('[data-qt]',p).onclick=()=>make('task');
  $('[data-more]',p).onclick=()=>make('event',true);
  $('[data-x]',p).onclick=()=>{ closePopover(); };
  p._onclose=clearGhost;
  setTimeout(()=>$('#qcT',p).focus(),0);
}
function placePop(p, at){
  const w=p.offsetWidth, h=p.offsetHeight;
  let left = at.left+w<innerWidth-8 ? at.left : Math.max(8, at.right-w);
  let top = clamp(at.top, 8, innerHeight-h-8);
  p.style.left=left+'px'; p.style.top=top+'px';
}
function closePopover(){ const p=$('#pop'); if(p){ p.remove(); if(p._onclose) p._onclose(); } }
document.addEventListener('mousedown',e=>{ const p=$('#pop'); if(p && !p.contains(e.target) && !e.target.closest('.ev,[data-occ]')) closePopover(); });

/* ============================================================
   Items: create, complete, delete, duplicate, convert; popover;
   day drawer; bulk actions
   ============================================================ */
function newEvent(o={}){
  const k=o.start?o.start.slice(0,10):key(cursor);
  const tz=o.tz||ACTIVE_TZ;
  return Object.assign({id:uid('e'), type:'event', calendarId:'work', projectId:null, title:'', category:'meeting', allDay:false,
    start:k+'T'+(S().workStart||'09:00'), end:localAddMin(k+'T'+(S().workStart||'09:00'), S().defaultEventMin||60), tz,
    location:'', onlineLink:'', attendees:[], notes:'', tags:[], showAs:'busy', status:'confirmed', rrule:null, exceptions:{},
    reminders:clone(S().defaultEventRem), linkedTaskIds:[], links:[], private:false}, o);
}
function newTask(o={}){
  return Object.assign({id:uid('t'), type:'task', calendarId:'work', projectId:null, title:'', notes:'', tags:[], status:'todo', priority:3,
    dueDate:null, dueTime:null, tz:ACTIVE_TZ, estimateMin:null, checklist:[], blocks:[], dependsOn:[], rrule:null,
    reminders:[...clone(S().defaultDueRem).map(r=>({anchor:'due',...r})), ...clone(S().defaultBlockRem).map(r=>({anchor:'block',...r}))], completedAt:null, private:false}, o);
}
function newProject(o={}){
  const used=new Set(data.projects.map(p=>p.color));
  return Object.assign({id:uid('p'), calendarId:'work', name:'', color:PALETTE.find(c=>!used.has(c))||PALETTE[data.projects.length%PALETTE.length], icon:'📁', description:'', status:'active',
    startDate:todayKey(), dueDate:null, budgetHours:null, parentId:null, pinned:false, order:data.projects.length, tripFrom:null, tripTo:null, reminders:[]}, o);
}
/* Personal (private) items need the passphrase set up / unlocked first */
function ensurePrivReadySync(){
  if(privKey) return true;
  if(privLocked){ openUnlock(); toast('Unlock Personal items first.'); return false; }
  openPrivSetup(); toast('Set a passphrase for Personal items first.'); return false;
}
function saveNewItem(it){
  if(isPrivate(it) && !ensurePrivReadySync()) return false;
  touch(it);
  if(it.type==='event') data.events.push(it); else if(it.type==='task') data.tasks.push(it); else data.projects.push(it);
  changed(); renderAll(); return true;
}
function removeItem(it, quiet){
  const col = it.type==='event'?'events':it.type==='task'?'tasks':'projects';
  data[col]=data[col].filter(x=>x.id!==it.id);
  data.trash.push({id:it.id, deletedAt:nowISO(), kind:it.type||'project', item:it});
  if(it.type==='task'){ for(const t of data.tasks) if(t.dependsOn.includes(it.id)){ t.dependsOn=t.dependsOn.filter(x=>x!==it.id); } for(const e of data.events) e.linkedTaskIds=e.linkedTaskIds.filter(x=>x!==it.id); }
  if(!quiet){ changed(); renderAll(); toast(`Deleted “${it.title||it.name}”`, {actions:[['Undo',undo]]}); }
}
function restoreFromTrash(id){
  const x=data.trash.find(t=>t.id===id); if(!x) return;
  const col=x.kind==='event'?'events':x.kind==='task'?'tasks':'projects';
  data[col].push(x.item); data.trash=data.trash.filter(t=>t.id!==id); normalizeItems(); changed(); renderAll();
}
function duplicateItem(it){
  const c=clone(it); c.id=uid(it.type==='event'?'e':'t'); c.title=(c.title||'')+' (copy)'; delete c.uid;
  if(c.type==='task'){ c.blocks=c.blocks.map(b=>({...b,id:uid('b'),done:false,actualMin:undefined})); c.status='todo'; c.completedAt=null; }
  touch(c); c.created=c.updated;
  if(c.type==='event') data.events.push(c); else data.tasks.push(c);
  changed(); renderAll(); return c;
}
function toggleTaskDone(id){
  const t=task(id); if(!t || !editable) return;
  setTaskStatus(t, isOpen(t)?'done':'todo');
}
function setTaskStatus(t, st){
  const was=isOpen(t);
  t.status=st; t.completedAt = st==='done' ? nowISO() : null; touch(t);
  let next=null;
  if(st==='done' && was && t.rrule && t.dueDate){ // recurring task: create the next one
    const nk=nextSeriesDate(t.rrStart||t.dueDate, t.rrule, t.dueDate);
    if(nk){ next=clone(t); next.id=uid('t'); next.status='todo'; next.completedAt=null; next.dueDate=nk; next.blocks=[]; next.checklist=next.checklist.map(c=>({...c,done:false})); next.rrStart=t.rrStart||t.dueDate; touch(next); next.created=next.updated; data.tasks.push(next); }
  }
  changed(); renderAll();
  if(st==='done' && was){
    toast(`✓ “${t.title}” done`+(next?` · next one due ${fmtShort(fromKey(next.dueDate))}`:''), {actions:[['Undo',undo]], ms:10000});
    budgetCheck(proj(t.projectId));
  }
}
function toggleBlockDone(id, bid){
  const t=task(id); if(!t || !editable) return; const b=t.blocks.find(x=>x.id===bid); if(!b) return;
  if(b.done){ b.done=false; delete b.actualMin; touch(t); changed(); renderAll(); return; }
  const planned=localDiffMin(b.start,b.end);
  askTimeSpent(planned, t.title).then(min=>{
    if(min===null) return;
    b.done=true; b.actualMin=min; if(t.status==='todo') t.status='in-progress'; touch(t);
    changed(); renderAll(); budgetCheck(proj(t.projectId));
    const left=t.estimateMin?remainingMin(t):null;
    toast(`Block done · ${fmtDur(min)} spent`+(left!==null?` · ${fmtDur(left)} left of the estimate`:''), {actions:[['Undo',undo], ...(isOpen(t)?[['Mark task done',()=>setTaskStatus(t,'done')]]:[])]});
  });
}
function askTimeSpent(planned, title){
  return new Promise(res=>{
    const d=openDialog(`<div class="dhd"><h2>Time spent</h2></div><form><p class="hint">How long did you work on “${esc(title)}”?</p>
      <div class="row" style="display:flex;gap:8px;align-items:center"><input type="number" id="tsH" min="0" max="24" step="1" value="${Math.floor(planned/60)}" style="width:70px"> h <input type="number" id="tsM" min="0" max="59" step="5" value="${planned%60}" style="width:70px"> min</div>
      <div class="dacts"><span class="sp"></span><button type="button" data-close>Cancel</button><button class="primary" type="submit">Mark done</button></div></form>`,{size:'narrow',onclose:()=>res(null)});
    $('form',d).onsubmit=e=>{ e.preventDefault(); const m=(+$('#tsH',d).value||0)*60+(+$('#tsM',d).value||0); d._onclose=null; closeDialog(d); res(m); };
  });
}
function budgetCheck(p){
  if(!p || !p.budgetHours) return;
  const st=projStats(p), pct=st.spent/60/p.budgetHours;
  const mark=pct>=1?100:pct>=.8?80:0; const keyb='wtc-budget:'+p.id;
  if(mark && (+safeGet(keyb)||0)<mark){ safeSet(keyb,mark); toast(`💶 “${p.name}”: ${fmtH(st.spent)} spent of the ${p.budgetHours} h budget (${Math.round(pct*100)}%).`,{ms:9000}); }
}
/* delete an occurrence with scope choice for repeating events */
async function deleteOcc(id, oc, kind){
  const it=findItem(id); if(!it||!editable) return;
  if(kind==='block'){ it.blocks=it.blocks.filter(b=>b.id!==oc); touch(it); changed(); renderAll(); toast('Time block removed',{actions:[['Undo',undo]]}); return; }
  if(it.rrule){
    const scope=await confirmDlg({title:'Delete repeating event', html:`“${esc(it.title)}” repeats. What do you want to delete?`}, [['Only this one','this'],['This and following','following'],['All of them','all','danger'],['Cancel',null]]);
    if(!scope) return;
    if(scope==='this'){ it.exceptions[oc]={deleted:true}; touch(it); changed(); renderAll(); toast('Occurrence deleted',{actions:[['Undo',undo]]}); return; }
    if(scope==='following'){ if(oc<=it.start.slice(0,10)) return removeItem(it); it.rrule=Object.assign({},it.rrule,{until:keyAdd(oc,-1),count:null}); for(const k of Object.keys(it.exceptions)) if(k>=oc) delete it.exceptions[k]; touch(it); changed(); renderAll(); toast('Following occurrences deleted',{actions:[['Undo',undo]]}); return; }
  } else if(!await confirmDlg({title:'Delete', html:`Delete “${esc(it.title)}”? You can restore it from ⚙ Settings → Trash for 30 days.`},[['Delete','y','danger'],['Cancel',null]])) return;
  removeItem(it);
}
function convertItem(it){
  if(it.type==='event'){
    const t=newTask({title:it.title, calendarId:it.calendarId, projectId:it.projectId, notes:it.notes, tags:it.tags, private:it.private});
    if(!it.allDay){ t.blocks=[{id:uid('b'), start:it.start, end:it.end, tz:it.tz, done:false}]; t.estimateMin=localDiffMin(it.start,it.end); t.dueDate=it.start.slice(0,10); }
    else t.dueDate=it.start.slice(0,10);
    removeItem(it,true); touch(t); data.tasks.push(t); changed(); renderAll(); toast('Converted to a task',{actions:[['Undo',undo]]}); return t;
  }
  const b=it.blocks[0]; const k=b?b.start.slice(0,10):(it.dueDate||todayKey());
  const e=newEvent({title:it.title, calendarId:it.calendarId, projectId:it.projectId, notes:it.notes, tags:it.tags, private:it.private, category:'other'});
  if(b){ e.start=b.start; e.end=b.end; e.tz=b.tz; } else if(it.dueDate){ e.allDay=true; e.start=k; e.end=k; }
  removeItem(it,true); touch(e); data.events.push(e); changed(); renderAll(); toast('Converted to an event',{actions:[['Undo',undo]]}); return e;
}

/* ---------- popover for a calendar entry ---------- */
function openOccPopover(id, oc, kind, anchorEl){
  closePopover();
  if(kind==='event' && useTabs()) return openDetailTab({t:'event', id, occ:oc});
  const it=findItem(id); if(!it) return;
  const o=findOcc(id,oc,kind); if(!o) return;
  selItem={kind,id,occ:oc};
  const p=document.createElement('div'); p.className='pop'; p.id='pop';
  const pr=proj(it.projectId), c=cal(it.calendarId);
  const when = o.allDay ? (o.dateKey===o.endKey?fmtLong(fromKey(o.dateKey)):fmtShort(fromKey(o.dateKey))+' – '+fmtShort(fromKey(o.endKey))) : `${fmtShort(new Date(o.s))} · ${hm(o.s)}–${hm(o.e)}${(it.tz||(o.block&&o.block.tz))&&(it.tz||o.block.tz)!==ACTIVE_TZ?` (${esc(tzShort(ACTIVE_TZ))} time; entered as ${esc(msToZoned(o.s,it.tz||o.block.tz).slice(11))} ${esc(tzShort(it.tz||o.block.tz))})`:''}`;
  const conf=conflictsIn(occurrences(o.s-864e5,o.e+864e5,{all:true})).get(id+'|'+oc);
  const isTask=kind==='block';
  const missed=o.missed;
  p.innerHTML=`<h3 class="redtitle"><i class="dotc" style="background:${esc(itemColor(it))};margin-top:5px"></i><span style="flex:1">${esc(CAT_ICON[it.category]||'')}${o.cancelled?'<s>':''}${esc(o.title)}${o.cancelled?'</s>':''}</span><button class="ghost" data-x style="padding:0 4px">✕</button></h3>
    <div class="ln">🕒 ${esc(when)}</div>
    ${isTask?`<div class="ln">☑ Task block${o.done?' · done'+(o.block.actualMin!=null?' ('+fmtDur(o.block.actualMin)+')':''):''}${missed?' · <b style="color:var(--missed)">missed</b>':''}</div>`:''}
    ${isTask&&it.dueDate?`<div class="ln">⚑ Due ${esc(dueLabel(it))}${isOverdue(it)?' <b style="color:var(--sick)">overdue</b>':''}</div>`:''}
    ${isTask&&it.estimateMin?`<div class="ln">⏱ ${fmtH(plannedMin(it))} scheduled of ${fmtH(it.estimateMin)} · ${fmtH(spentMin(it))} spent</div>`:''}
    ${it.rrule?`<div class="ln">🔁 ${esc(rrText(it.rrule))}</div>`:''}
    ${o.location||it.location?`<div class="ln">📍 ${esc(o.location||it.location)}</div>`:''}
    ${it.onlineLink?`<div class="ln">🔗 <a href="${esc(it.onlineLink)}" target="_blank" rel="noopener">Join online</a></div>`:''}
    <div class="ln">🗂 ${esc(c.name)}${pr?' · '+esc(pr.icon||'')+' '+esc(pr.name):''}${isPrivate(it)?' · 🔒':''}</div>
    ${it.attendees&&it.attendees.length?`<div class="ln">👥 ${esc(it.attendees.join(', '))}</div>`:''}
    ${conf?`<div class="warnbox" style="margin-top:6px">⚠ ${conf.map(esc).join('<br>')}</div>`:''}
    ${it.notes?`<div class="md" style="margin-top:6px;max-height:140px;overflow:auto">${md(it.notes)}</div>`:''}
    <div class="acts">
      ${editable?`<button class="primary" data-a="edit">✎ Edit</button>`:`<button data-a="edit">Details</button>`}
      ${editable&&isTask?`<button data-a="bdone">${o.done?'Not done':'✓ Block done'}</button>${isOpen(it)?'<button data-a="tdone">Task done</button>':''}`:''}
      ${editable&&isTask&&missed?'<button data-a="resched">↻ Reschedule</button>':''}
      ${isTask&&!o.done&&editable?'<button data-a="timer">⏱ Start timer</button><button data-a="focus">🎯 Focus</button>':''}
      ${editable&&!isTask?'<button data-a="followup">＋ Follow-up task</button>':''}
      ${editable?'<button data-a="dup">Duplicate</button><button data-a="del" class="danger">Delete</button>':''}
    </div>`;
  document.body.appendChild(p);
  const r=anchorEl?anchorEl.getBoundingClientRect():{left:innerWidth/2,right:innerWidth/2,top:100};
  placePop(p,{left:r.right+6,right:r.left-6,top:r.top});
  $('[data-x]',p).onclick=closePopover;
  const act=(a,fn)=>{ const b=$(`[data-a="${a}"]`,p); if(b) b.onclick=()=>{ closePopover(); fn(); }; };
  act('edit',()=> it.type==='event' ? openEventEditor(id,null,oc) : openTaskEditor(id));
  act('bdone',()=>toggleBlockDone(id,oc));
  act('tdone',()=>setTaskStatus(it,'done'));
  act('resched',()=>rescheduleBlock(it,o.block));
  act('timer',()=>startTimer(it.id,o.block.id));
  act('focus',()=>startFocus(it.id,o.block.id));
  act('followup',()=>{ const t=newTask({title:'Follow up: '+it.title, calendarId:it.calendarId, projectId:it.projectId, dueDate:keyAdd(o.dateKey||key(new Date(o.s)),2), private:it.private}); openTaskEditor(null,t,{linkEvent:it.id}); });
  act('dup',()=>{ const c=duplicateItem(it); toast('Duplicated',{actions:[['Edit',()=> c.type==='event'?openEventEditor(c.id):openTaskEditor(c.id)]]}); });
  act('del',()=>deleteOcc(id,oc,kind));
}

/* ---------- day drawer (status, location, flights, comments + events & tasks) ---------- */
function openDrawer(k){ openDay=k; renderDrawer(); $('#drawer').classList.add('open'); $('#scrim').classList.add('open'); }
function closeDrawer(){ openDay=null; $('#drawer').classList.remove('open'); $('#scrim').classList.remove('open'); }
function stepDrawer(n){ if(!openDay) return; const d=addDays(fromKey(openDay),n); openDay=key(d); ensureVisible(d); renderDrawer(); }
function ensureVisible(d){
  const [a,b]=visibleRange(); if(d<a || d>=b){ cursor=d; render(); }
}
function renderDrawer(){
  const k=openDay, d=fromKey(k), e=day(k), we=isWeekend(d), dis=editable?'':'disabled';
  const chipRow=(half)=>Object.entries(STATUSES).map(([s,v])=>{
    const on = half==='both' ? (e.am===s && e.pm===s) : e[half]===s;
    return `<button class="chip ${on?'on':''}" style="${on?`background:var(--${s})`:''}" data-half="${half}" data-s="${s}" ${dis}><i class="sw" style="background:var(--${s})"></i>${v.label}</button>`;
  }).join('') + `<button class="chip" data-half="${half}" data-s="" ${dis} title="Clear">✕</button>`;
  const l=locationOf(k), loc=e.location||'';
  const isOther = loc && !['athens','brussels'].includes(loc.toLowerCase());
  const flights=(e.flights||[]).map((f,i)=>`
    <div class="flight" data-i="${i}">
      <div class="route">✈
        ${editable?`<input type="text" data-ff="from" value="${esc(f.from)}" aria-label="From"> → <input type="text" data-ff="to" value="${esc(f.to)}" aria-label="To">`
                  :`${esc(f.from)} → ${esc(f.to)}`}
      </div>
      ${editable?`<button class="ghost" data-fdel="${i}" title="Remove flight">🗑</button>`:'<span></span>'}
      <div class="row2">
        ${Object.entries(FLIGHT_TIMES).map(([t,lab])=>`<button class="chip ${flightKind(f)===t?'loc-on':''}" data-ft="${t}" ${dis} title="${minToHM(FLIGHT_WINDOWS[t][0])}–${FLIGHT_WINDOWS[t][1]>=1440?'24:00':minToHM(FLIGHT_WINDOWS[t][1])}, alarm ${minToHM(FLIGHT_ALARM[t])}">${lab} <span class="hint">${minToHM(FLIGHT_WINDOWS[t][0])}–${FLIGHT_WINDOWS[t][1]>=1440?'24':minToHM(FLIGHT_WINDOWS[t][1]).replace(':00','')}</span></button>`).join('')}
      </div>
      <div class="row2">
        ${editable?`<label class="hint">Departs <input type="time" data-fx="dep" value="${minToHM(flightMins(f).a)}" step="300" style="flex:0 0 auto;width:auto"></label><label class="hint">Arrives <input type="time" data-fx="arr" value="${flightMins(f).b>=1440?'23:59':minToHM(flightMins(f).b)}" step="300" style="flex:0 0 auto;width:auto"></label><label class="hint">⏰ Alarm <input type="time" data-fx="alarm" value="${flightAlarmMin(f)===null?'':minToHM(flightAlarmMin(f))}" step="300" style="flex:0 0 auto;width:auto"></label><label class="hint"><input type="checkbox" data-falarm ${flightAlarmMin(f)===null?'':'checked'}> alarm on</label>${flightMins(f).custom||f.alarm?`<button class="chip" data-freset title="Back to the default ${esc(FLIGHT_TIMES[flightKind(f)])} hours and alarm">↺ default</button>`:'<span class="hint">default hours</span>'}<span class="hint" style="flex-basis:100%">Times are local to ${esc(tzShort(flightTz(f)))} (departure)${flightTz(f)!==ACTIVE_TZ?' · in the calendar: '+esc(flightDisp(k,f).replace(/ \(.*$/,''))+' '+esc(tzShort(ACTIVE_TZ)):''}</span>`
                  :`<span class="hint">${esc(flightTimeLabel(f))}</span>`}
      </div>
      <div class="row2">
        ${editable?`<input type="text" data-ff="note" value="${esc(f.note||'')}" placeholder="Flight number, e.g. A3 610">`:(f.note?`<span class="hint">${esc(f.note)}</span>`:'')}
      </div>
    </div>`).join('');
  const d0=d.getTime(), d1=addDays(d,1).getTime();
  const occ=occurrences(d0,d1,{all:true}).filter(o=>o.kind!=='busy' && (privKey||!isPrivate(o.item)));
  const evs=occ.filter(o=>o.kind==='event'), blocks=occ.filter(o=>o.kind==='block');
  const due=dueTasksOn(k,{all:true});
  const evRows=evs.map(o=>`<div class="trow" style="--c:${esc(itemColor(o.item))};grid-template-columns:1fr auto" data-occ="${esc(o.id)}" data-oc="${esc(o.occ)}" data-ok="event"><span class="tt">${o.cancelled?'<s>':''}${esc(o.title)}${o.cancelled?'</s>':''}</span><span class="hint">${o.allDay?'All day':hm(o.s)+'–'+hm(o.e)}</span>${o.location?`<div class="meta">📍 ${esc(o.location)}</div>`:''}</div>`).join('');
  const blRows=blocks.map(o=>`<div class="trow ${o.done?'done':''}" style="--c:${esc(itemColor(o.item))};grid-template-columns:auto 1fr auto" data-occ="${esc(o.id)}" data-oc="${esc(o.occ)}" data-ok="block"><span>☐</span><span class="tt">${esc(o.title)}</span><span class="hint">${hm(o.s)}–${hm(o.e)}</span></div>`).join('');
  $('#drawer').innerHTML = `
    <div class="dhead">
      <button class="ghost" id="dPrev" title="Previous day">‹</button>
      <h2>${esc(fmtLong(d))}<small>Week ${isoWeek(d)}${we?' · weekend':''}</small></h2>
      <button class="ghost" id="dNext" title="Next day">›</button>
      <button class="ghost" id="dClose" title="Close (Esc)">✕</button>
    </div>
    <div class="dbody">
      ${editable?'':'<div class="ro-note">You are signed in with a read-only account.</div>'}
      ${we?'<div class="hint">Weekend — no work status recorded.</div>':`
      <div class="sec"><h4>Work status <span style="text-transform:none;letter-spacing:0;font-weight:500">morning ${esc(S().workStart)}–${esc(S().midday)} · evening ${esc(S().midday)}–${esc(S().workEnd)}</span></h4>
        <div class="half"><span>Whole day</span><div class="chips">${chipRow('both')}</div></div>
        <div class="half"><span>Morning</span><div class="chips">${chipRow('am')}</div></div>
        <div class="half"><span>Evening</span><div class="chips">${chipRow('pm')}</div></div>
      </div>`}
      <div class="sec"><h4>Location <span style="text-transform:none;letter-spacing:0;font-weight:500">${!loc && l?`auto: ${esc(l.name)} (from last flight)`:''}</span></h4>
        <div class="chips" style="margin-bottom:8px">
          <button class="chip ${!loc?'loc-on':''}" data-loc="" ${dis} title="Use the destination of the latest flight">Auto</button>
          <button class="chip ${loc.toLowerCase()==='athens'?'loc-on':''}" data-loc="Athens" ${dis}>Athens</button>
          <button class="chip ${loc.toLowerCase()==='brussels'?'loc-on':''}" data-loc="Brussels" ${dis}>Brussels</button>
          <button class="chip ${isOther?'loc-on':''}" data-loc="?" ${dis}>Other…</button>
        </div>
        ${(isOther || $('#drawer').dataset.other===k) ? `<input type="text" id="locOther" value="${esc(isOther?loc:'')}" placeholder="Type a location, e.g. London" ${dis}>`:''}
      </div>
      <div class="sec"><h4>Travel</h4>
        ${flights || '<div class="hint" style="margin-bottom:8px">No flights this day.</div>'}
        ${editable?`<div class="chips">
          <button class="chip" data-add="Athens|Brussels">＋ Athens → Brussels</button>
          <button class="chip" data-add="Brussels|Athens">＋ Brussels → Athens</button>
          <button class="chip" data-add="|">＋ Other flight</button></div>`:''}
      </div>
      <div class="sec"><h4>Comments</h4>
        <textarea id="dComment" placeholder="${editable?'Anything to note for this day…':'—'}" ${dis}>${esc(e.comment||'')}</textarea>
      </div>
      <div class="sec"><h4><span>Events</span>${editable?'<button class="chip" data-dnew="event">＋ Event</button>':''}</h4>
        <div class="tl">${evRows||'<div class="hint">No events.</div>'}</div></div>
      <div class="sec"><h4><span>Tasks</span>${editable?'<button class="chip" data-dnew="task">＋ Task due this day</button>':''}</h4>
        <div class="tl">${blRows}${due.map(t=>taskRowHTML(t)).join('')||(!blRows?'<div class="hint">No tasks.</div>':'')}</div></div>
    </div>`;

  const D=$('#drawer');
  $('#dPrev').onclick=()=>stepDrawer(-1); $('#dNext').onclick=()=>stepDrawer(1); $('#dClose').onclick=closeDrawer;
  $$('[data-occ]',D).forEach(r=>r.onclick=()=>openOccPopover(r.dataset.occ,r.dataset.oc,r.dataset.ok,r));
  bindTaskRows(D);
  if(!editable) return;
  $$('[data-dnew]',D).forEach(b=>b.onclick=()=>{ if(b.dataset.dnew==='event'){ const tz=ACTIVE_TZ; openEventEditor(null,newEvent({start:k+'T'+S().workStart, end:localAddMin(k+'T'+S().workStart,S().defaultEventMin||60), tz})); } else openTaskEditor(null,newTask({dueDate:k})); });
  const commit=(rerenderDrawer=true)=>{ prune(k); changed(); render(); renderSide(); if(rerenderDrawer) renderDrawer(); };
  $$('[data-half]',D).forEach(b=>b.onclick=()=>{
    const h=b.dataset.half, s=b.dataset.s, m=dayMut(k);
    if(h==='both'){ m.am=s; m.pm=s; } else m[h]=s;
    commit();
    if(OFF_STATUSES.includes(s)) offerMoveWorkBlocks(k);
  });
  $$('[data-loc]',D).forEach(b=>b.onclick=()=>{
    const v=b.dataset.loc, m=dayMut(k);
    if(v==='?'){ D.dataset.other=k; renderDrawer(); const i=$('#locOther'); i&&i.focus(); return; }
    D.dataset.other=''; m.location=v; commit();
  });
  const lo=$('#locOther');
  if(lo) lo.oninput=()=>{ dayMut(k).location=lo.value.trim(); prune(k); changed('loc'+k); render(); };
  $$('[data-add]',D).forEach(b=>b.onclick=()=>{
    const [from,to]=b.dataset.add.split('|'); const m=dayMut(k);
    (m.flights=m.flights||[]).push({from,to,time:'morning'});
    commit();
    if(!from){ const ins=$$('.flight input[data-ff="from"]',D); ins.length && ins[ins.length-1].focus(); }
  });
  $$('.flight',D).forEach(fe=>{
    const i=+fe.dataset.i;
    $$('[data-ft]',fe).forEach(b=>b.onclick=()=>{ const fl=day(k).flights[i]; fl.time=b.dataset.ft; delete fl.dep; delete fl.arr; delete fl.alarm; commit(); });
    $$('[data-fx]',fe).forEach(inp=>inp.onchange=()=>{ const fl=day(k).flights[i]; const v=inp.value, n=inp.dataset.fx;
      if(n==='alarm'){ if(!v) fl.alarm='off'; else fl.alarm=v; } else if(!v) delete fl[n]; else fl[n]=v;
      const kd=flightKind(fl), d=FLIGHT_WINDOWS[kd];
      if(fl.dep===minToHM(d[0]) && (fl.arr===minToHM(d[1])||(d[1]>=1440&&fl.arr==='23:59')||!fl.arr)){ delete fl.dep; delete fl.arr; }
      if(fl.alarm && fl.alarm!=='off' && fl.alarm===minToHM(flightAlarmMin({...fl,alarm:undefined}))) delete fl.alarm;
      commit(); });
    const fa=$('[data-falarm]',fe); if(fa) fa.onchange=()=>{ const fl=day(k).flights[i]; if(fa.checked) delete fl.alarm; else fl.alarm='off'; commit(); };
    const fr=$('[data-freset]',fe); if(fr) fr.onclick=()=>{ const fl=day(k).flights[i]; delete fl.dep; delete fl.arr; delete fl.alarm; commit(); };
    $$('[data-ff]',fe).forEach(inp=>inp.oninput=()=>{ day(k).flights[i][inp.dataset.ff]=inp.value; changed('fl'+k+i); render(); });
    const del=$('[data-fdel]',fe); if(del) del.onclick=()=>{ day(k).flights.splice(i,1); commit(); };
  });
  const c=$('#dComment');
  c.oninput=()=>{ dayMut(k).comment=c.value; prune(k); changed('cm'+k); render(); };
}
/* setting a day to Leave/Sick offers to move that day's work blocks */
function offerMoveWorkBlocks(k){
  const bl=data.tasks.filter(t=>t.calendarId==='work' && isOpen(t)).flatMap(t=>t.blocks.filter(b=>!b.done && b.start.slice(0,10)===k && offOverlap(k,zonedToMs(b.start,b.tz),zonedToMs(b.end,b.tz)).length).map(b=>({t,b})));
  if(!bl.length) return;
  toast(`${bl.length} work task block${bl.length>1?'s are':' is'} planned during the time off.`,{actions:[['Move to next free slots',()=>{ for(const {t,b} of bl) rescheduleBlock(t,b,true); changed(); renderAll(); toast('Moved.',{actions:[['Undo',undo]]}); }]]});
}

/* ---------- selection bar: bulk work status (days) or bulk task actions ---------- */
function renderSelbar(){
  const bar=$('#selbar');
  if(editable && selTasks.size){
    bar.innerHTML=`<b>${selTasks.size} task${selTasks.size>1?'s':''} selected</b>
      <button data-bt="project">Project…</button><button data-bt="priority">Priority…</button><button data-bt="due">Due date…</button><button data-bt="status">Status…</button>
      <button data-bt="schedule">Auto-schedule</button><button data-bt="delete">Delete</button><span class="sep"></span><button data-bt="clear">Done</button>`;
    bar.classList.add('open');
    $$('[data-bt]',bar).forEach(b=>b.onclick=()=>bulkTasks(b.dataset.bt));
    return;
  }
  if(!editable || !selected.size){ bar.classList.remove('open'); return; }
  const wd=[...selected].filter(k=>!isWeekend(fromKey(k))).length;
  bar.innerHTML = `<b>${selected.size} day${selected.size>1?'s':''} selected</b>`
    + Object.entries(STATUSES).map(([s,v])=>`<button data-bs="${s}" ${wd?'':'disabled'}><i class="sw" style="background:var(--${s});width:9px;height:9px"></i> ${v.short}</button>`).join('')
    + `<button data-bs="" ${wd?'':'disabled'}>Clear status</button><span class="sep"></span>`
    + `<button data-bl="Athens">📍 Athens</button><button data-bl="Brussels">📍 Brussels</button><button data-bl="?">📍 Other…</button><button data-bl="">📍 Auto</button><span class="sep"></span>`
    + `<button data-bx="1">Done</button>`;
  bar.classList.add('open');
  $$('[data-bs]',bar).forEach(b=>b.onclick=()=>{ const s=b.dataset.bs; selected.forEach(k=>{ if(isWeekend(fromKey(k))) return; const e=dayMut(k); e.am=s; e.pm=s; prune(k); }); changed(); render(); });
  $$('[data-bl]',bar).forEach(b=>b.onclick=async()=>{ let v=b.dataset.bl; if(v==='?'){ v=await promptDlg('Location','Location for the selected days:',''); if(v==null) return; v=v.trim(); } selected.forEach(k=>{ dayMut(k).location=v; prune(k); }); changed(); render(); });
  $('[data-bx]',bar).onclick=()=>{ selected.clear(); render(); };
}
async function bulkTasks(a){
  const ts=[...selTasks].map(task).filter(Boolean);
  const done=()=>{ ts.forEach(touch); changed(); renderAll(); toast(`Updated ${ts.length} tasks`,{actions:[['Undo',undo]]}); };
  if(a==='clear'){ selTasks.clear(); renderTasksPanel(); return; }
  if(a==='delete'){ if(!await confirmDlg(`Delete ${ts.length} tasks?`,[['Delete','y','danger'],['Cancel',null]])) return; ts.forEach(t=>removeItem(t,true)); selTasks.clear(); changed(); renderAll(); toast('Deleted',{actions:[['Undo',undo]]}); return; }
  if(a==='schedule'){ openAutoSchedule(ts); return; }
  const opts={
    project:{label:'Project', html:`<select id="bv"><option value="">Inbox</option>${data.projects.filter(p=>p.status!=='archived').map(p=>`<option value="${p.id}">${esc(cal(p.calendarId).name)} · ${esc(p.name)}</option>`).join('')}</select>`},
    priority:{label:'Priority', html:`<select id="bv">${[1,2,3,4].map(p=>`<option value="${p}">P${p}</option>`).join('')}</select>`},
    due:{label:'Due date', html:`<input type="date" id="bv" value="${todayKey()}">`},
    status:{label:'Status', html:`<select id="bv">${Object.entries(TASK_STATUS).map(([k,v])=>`<option value="${k}">${v}</option>`).join('')}</select>`},
  }[a];
  const d=openDialog(`<div class="dhd"><h2>${opts.label} for ${ts.length} tasks</h2></div>${opts.html}<div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" id="bok">Apply</button></div>`,{size:'narrow'});
  $('#bok',d).onclick=()=>{
    const v=$('#bv',d).value; closeDialog(d);
    for(const t of ts){
      if(a==='project'){ if(v){ const p=proj(v); if(isPrivCal(p.calendarId)&&!privKey) continue; t.projectId=v; t.calendarId=p.calendarId; } else t.projectId=null; }
      if(a==='priority') t.priority=+v; if(a==='due') t.dueDate=v||null;
      if(a==='status'){ t.status=v; t.completedAt=v==='done'?nowISO():null; }
    }
    done();
  };
}

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

/* ============================================================
   Editors: event, task, project; project page
   ============================================================ */
function calOptions(sel){ return data.calendars.map(c=>`<option value="${c.id}" ${c.id===sel?'selected':''} ${c.private&&privLocked?'disabled':''}>${esc(c.name)}${c.private?' 🔒':''}</option>`).join(''); }
function projOptions(calId, sel, exclude){
  const ps=data.projects.filter(p=>p.calendarId===calId && p.id!==exclude && (p.status!=='archived'||p.id===sel));
  return `<option value="">📥 Inbox (no project)</option>`+ps.map(p=>`<option value="${p.id}" ${p.id===sel?'selected':''}>${esc(p.icon||'')} ${esc(p.parentId?'  ↳ ':'')}${esc(p.name)}</option>`).join('');
}
function tzOptions(sel){ const l=[...new Set([...TZ_LIST, sel].filter(Boolean))]; return l.map(z=>`<option ${z===sel?'selected':''}>${z}</option>`).join(''); }
function bindCalProj(root){
  const c=$('[name=calendarId]',root), p=$('[name=projectId]',root);
  if(!c || !p) return;               // never break the dialog if a field is missing
  c.onchange=()=>{ p.innerHTML=projOptions(c.value, null); };
}
/* recurrence sub-form */
function rrFormHTML(rr, baseK){
  rr=rr||{}; const f=rr.freq||'';
  const preset = !f?'':(f==='weekly' && (rr.byDay||[]).slice().sort().join()==='0,1,2,3,4' && (rr.interval||1)===1 ? 'weekdays' : f);
  const bd=new Set(rr.byDay||[dowKey(baseK)]);
  return `<div class="rr" style="display:flex;flex-direction:column;gap:6px;width:100%">
    <div class="row"><select name="rrf"><option value="">Does not repeat</option><option value="daily" ${preset==='daily'?'selected':''}>Daily</option><option value="weekdays" ${preset==='weekdays'?'selected':''}>Every weekday (Mon–Fri)</option><option value="weekly" ${preset==='weekly'?'selected':''}>Weekly</option><option value="monthly" ${preset==='monthly'?'selected':''}>Monthly</option><option value="yearly" ${preset==='yearly'?'selected':''}>Yearly</option></select>
      <span data-rrx>every <input type="number" name="rri" min="1" max="99" value="${rr.interval||1}" style="width:60px"> <span data-rrunit></span></span></div>
    <div class="row daychips" data-rrw>${DOW3.map((d,i)=>`<button type="button" data-rd="${i}" class="${bd.has(i)?'on':''}">${d}</button>`).join('')}</div>
    <div class="row" data-rrm><label><input type="radio" name="rrmm" value="date" ${rr.monthMode!=='nth'?'checked':''}> on day ${fromKey(baseK).getDate()}</label>
      <label><input type="radio" name="rrmm" value="nth" ${rr.monthMode==='nth'?'checked':''}> on the <select name="rrn">${[[1,'1st'],[2,'2nd'],[3,'3rd'],[4,'4th'],[-1,'last']].map(([v,l])=>`<option value="${v}" ${(rr.nth||Math.min(4,Math.ceil(fromKey(baseK).getDate()/7)))==v?'selected':''}>${l}</option>`).join('')}</select> ${DAY_NAMES[dowKey(baseK)]}</label></div>
    <div class="row" data-rre>Ends <select name="rre"><option value="never">never</option><option value="count" ${rr.count?'selected':''}>after</option><option value="until" ${rr.until?'selected':''}>on</option></select>
      <input type="number" name="rrc" min="1" max="999" value="${rr.count||10}" style="width:70px"> <input type="date" name="rru" value="${rr.until||keyAdd(baseK,90)}"></div>
  </div>`;
}
function bindRR(root){
  const f=$('[name=rrf]',root);
  const upd=()=>{
    const v=f.value;
    $('[data-rrx]',root).style.display = v&&v!=='weekdays'?'':'none';
    $('[data-rrunit]',root).textContent = {daily:'day(s)',weekly:'week(s)',monthly:'month(s)',yearly:'year(s)'}[v]||'';
    $('[data-rrw]',root).style.display = v==='weekly'?'':'none';
    $('[data-rrm]',root).style.display = v==='monthly'?'':'none';
    $('[data-rre]',root).style.display = v?'':'none';
    const e=$('[name=rre]',root).value; $('[name=rrc]',root).style.display=e==='count'?'':'none'; $('[name=rru]',root).style.display=e==='until'?'':'none';
  };
  f.onchange=upd; $('[name=rre]',root).onchange=upd;
  $$('[data-rd]',root).forEach(b=>b.onclick=()=>b.classList.toggle('on'));
  upd();
}
function readRR(root){
  const v=$('[name=rrf]',root).value; if(!v) return null;
  const rr={freq:v==='weekdays'?'weekly':v, interval:v==='weekdays'?1:Math.max(1,+$('[name=rri]',root).value||1)};
  if(v==='weekdays') rr.byDay=[0,1,2,3,4];
  if(v==='weekly') rr.byDay=$$('[data-rd].on',root).map(b=>+b.dataset.rd);
  if(v==='monthly'){ rr.monthMode=$('[name=rrmm]:checked',root).value; if(rr.monthMode==='nth') rr.nth=+$('[name=rrn]',root).value; }
  const e=$('[name=rre]',root).value; if(e==='count') rr.count=Math.max(1,+$('[name=rrc]',root).value||1); if(e==='until') rr.until=$('[name=rru]',root).value||null;
  return rr;
}
/* reminders sub-form */
function remFormHTML(list, anchors){
  const row=(r,i)=>`<div class="li" data-ri="${i}">${anchors?`<select data-r="anchor">${anchors.map(([v,l])=>`<option value="${v}" ${r.anchor===v?'selected':''}>${l}</option>`).join('')}</select>`:''}
    <select data-r="offsetMin">${OFFSETS.map(([v,l])=>`<option value="${v}" ${+r.offsetMin===v?'selected':''}>${l}</option>`).join('')}${OFFSETS.some(o=>o[0]===+r.offsetMin)?'':`<option value="${r.offsetMin}" selected>${fmtDur(r.offsetMin)} before</option>`}<option value="custom">Custom…</option></select>
    <select data-r="channel">${[['popup','Popup'],['desktop','Popup + desktop'],['sound','Popup + desktop + sound']].map(([v,l])=>`<option value="${v}" ${r.channel===v?'selected':''}>${l}</option>`).join('')}</select>
    <button type="button" class="ghost" data-rdel="${i}" aria-label="Remove reminder">✕</button></div>`;
  return `<div class="list-edit" data-rems>${list.map(row).join('')}<button type="button" data-radd style="align-self:flex-start">＋ Reminder</button></div>`;
}
function bindRems(root, list, anchors, rerender){
  $$('[data-ri]',root).forEach(li=>{ const i=+li.dataset.ri; $$('[data-r]',li).forEach(s=>s.onchange=async()=>{
    if(s.dataset.r==='offsetMin' && s.value==='custom'){ const v=await promptDlg('Custom reminder','Minutes before (e.g. 45, or 180 for 3 hours):','45',{type:'number'}); if(v!=null) list[i].offsetMin=Math.max(0,+v||0); rerender(); return; }
    list[i][s.dataset.r]= s.dataset.r==='offsetMin'?+s.value:s.value; }); });
  $$('[data-rdel]',root).forEach(b=>b.onclick=()=>{ list.splice(+b.dataset.rdel,1); rerender(); });
  $('[data-radd]',root).onclick=()=>{ list.push(anchors?{anchor:anchors[0][0],offsetMin:15,channel:'popup'}:{offsetMin:15,channel:'popup'}); rerender(); };
}
function splitList(s){ return String(s||'').split(',').map(x=>x.trim()).filter(Boolean); }

/* ---------------- EVENT EDITOR ---------------- */
function openEventEditor(id, draft, occKey){
  if(editInTab()) return openEditTab('event', id, draft, occKey);
  const orig = id ? ev(id) : null;
  if(id && !orig) return;
  const e = clone(orig || draft || newEvent());
  const isNew = !orig;
  const ro = !editable;
  // when opened from one occurrence of a series, show that occurrence's date/time
  let occStart=null, occEnd=null;
  if(orig && orig.rrule && occKey){ const o=findOcc(id,occKey,'event'); if(o){ const ex=orig.exceptions[occKey]||{}; occStart=ex.start||(occKey+(orig.allDay?'':'T'+orig.start.slice(11,16))); occEnd=ex.end||(orig.allDay?keyAdd(occKey,Math.round((uparse(orig.end.slice(0,10))-uparse(orig.start.slice(0,10)))/864e5)):localAddMin(occStart,localDiffMin(orig.start,orig.end))); } }
  const sv=occStart||e.start, en=occEnd||e.end||e.start;
  // the form shows times in the active zone; they are saved back in the item's own zone
  const storeTz = (!e.allDay && e.tz) ? e.tz : ACTIVE_TZ;
  const svD = e.allDay ? sv : tzConvert(sv, storeTz, ACTIVE_TZ), enD = e.allDay ? en : tzConvert(en, storeTz, ACTIVE_TZ);
  const d=openDialog(`<div class="dhd"><h2>${isNew?'New event':ro?'Event':'Edit event'}</h2>${!isNew&&orig.rrule?'<span class="tag">🔁 repeating</span>':''}<button class="ghost" data-close aria-label="Close">✕</button></div>
  <form class="frm" autocomplete="off">
    <input class="titlein redtitle full" name="title" value="${esc(e.title)}" placeholder="Event title" ${ro?'disabled':''} required aria-label="Title">
    <label>Calendar</label><div class="row"><select name="calendarId" ${ro?'disabled':''}>${calOptions(e.calendarId)}</select><select name="projectId" ${ro?'disabled':''} aria-label="Project">${projOptions(e.calendarId,e.projectId)}</select></div>
    <label>Type</label><div class="row"><select name="category" ${ro?'disabled':''}>${Object.entries(CATEGORIES).map(([k,v])=>`<option value="${k}" ${e.category===k?'selected':''}>${v}</option>`).join('')}</select>
      <label class="hint"><input type="checkbox" name="allDay" ${e.allDay?'checked':''} ${ro?'disabled':''}> All day</label>
      <select name="showAs" ${ro?'disabled':''} aria-label="Show as">${Object.entries(SHOW_AS).map(([k,v])=>`<option value="${k}" ${e.showAs===k?'selected':''}>${v}</option>`).join('')}</select>
      <select name="status" ${ro?'disabled':''} aria-label="Status"><option value="confirmed">Confirmed</option><option value="cancelled" ${e.status==='cancelled'||(occKey&&e.exceptions[occKey]&&e.exceptions[occKey].status==='cancelled')?'selected':''}>Cancelled</option></select></div>
    <label>Starts</label><div class="row"><input type="date" name="sd" value="${svD.slice(0,10)}" ${ro?'disabled':''} required><input type="time" name="st" value="${svD.slice(11,16)||S().workStart}" data-timed ${ro?'disabled':''} step="300"></div>
    <label>Ends</label><div class="row"><input type="date" name="ed" value="${enD.slice(0,10)}" ${ro?'disabled':''}><input type="time" name="et" value="${enD.slice(11,16)||''}" data-timed ${ro?'disabled':''} step="300">
      <input type="hidden" name="tz" value="${ACTIVE_TZ}"><span class="hint" data-timed title="Change it with the 🌍 menu at the top">🌍 ${esc(tzShort(ACTIVE_TZ))} time${storeTz!==ACTIVE_TZ&&!isNew?` · entered in ${esc(tzShort(storeTz))} time (${esc(sv.slice(11,16))})`:''}</span></div>
    <div class="full" id="evConf"></div>
    <label>Repeat</label>${rrFormHTML(e.rrule, e.start.slice(0,10))}
    <label>Location</label><input type="text" name="location" value="${esc(e.location)}" placeholder="Room, address or city" ${ro?'disabled':''}>
    <label>Online link</label><input type="text" name="onlineLink" value="${esc(e.onlineLink)}" placeholder="https://…" ${ro?'disabled':''}>
    <label>Attendees</label><input type="text" name="attendees" value="${esc(e.attendees.join(', '))}" placeholder="Names, separated by commas" ${ro?'disabled':''}>
    <div class="sec">Reminders</div><div class="full" id="evRem"></div>
    <div class="sec">Notes &amp; links</div>
    ${mdEditorHTML('notes', e.notes, {ro, rows:7, placeholder:'Agenda, meeting notes… Markdown: **bold**, *italic*, # heading, - list, [link](https://…)'})}
    <label>Links</label><div class="list-edit" id="evLinks"></div>
    <label>Linked tasks</label><div class="list-edit" id="evTasks"></div>
    <label>Tags</label><input type="text" name="tags" value="${esc(e.tags.join(', '))}" placeholder="e.g. council, budget" ${ro?'disabled':''}>
    <label>Privacy</label><label class="hint"><input type="checkbox" name="private" ${e.private?'checked':''} ${ro?'disabled':''}> Private — hide from viewers even in the Work calendar (needs the Personal passphrase)</label>
  </form>
  <div class="dacts">
    ${!isNew&&editable?'<button data-a="del" class="danger">Delete</button><button data-a="dup">Duplicate</button><button data-a="conv">Convert to task</button><button data-a="tpl">Save as template</button><button data-a="ics">⬇ .ics</button>':''}
    <span class="sp"></span><button data-close>${ro?'Close':'Cancel'}</button>${editable?'<button class="primary" data-a="save">Save</button>':''}
  </div>`, {size:'mid', focus:'[name=title]'});
  const F=$('form',d);
  bindCalProj(F); bindRR(F);
  const allDay=$('[name=allDay]',F);
  const syncAD=()=>$$('[data-timed]',F).forEach(x=>x.style.display=allDay.checked?'none':''); allDay.onchange=()=>{ syncAD(); checkConf(); }; syncAD();
  // keep end after start when the start moves
  let lastStart = svD.slice(0,16);
  const shiftEnd=()=>{
    const ns=$('[name=sd]',F).value+'T'+($('[name=st]',F).value||'00:00'); const oe=$('[name=ed]',F).value+'T'+($('[name=et]',F).value||'00:00');
    if(parseLocal(lastStart) && parseLocal(ns) && parseLocal(oe)){ const dur=localDiffMin(lastStart,oe); const ne=localAddMin(ns, Math.max(allDay.checked?0:15,dur)); $('[name=ed]',F).value=ne.slice(0,10); $('[name=et]',F).value=ne.slice(11,16); }
    lastStart=ns; checkConf();
  };
  $('[name=sd]',F).onchange=shiftEnd; $('[name=st]',F).onchange=shiftEnd; $('[name=ed]',F).onchange=checkConf; $('[name=et]',F).onchange=checkConf;
  function checkConf(){
    if(allDay.checked){ $('#evConf',d).innerHTML=''; return; }
    const tz=$('[name=tz]',F).value, s=zonedToMs($('[name=sd]',F).value+'T'+$('[name=st]',F).value,tz), en2=zonedToMs($('[name=ed]',F).value+'T'+$('[name=et]',F).value,tz);
    if(!(en2>s)){ $('#evConf',d).innerHTML='<div class="warnbox">The end is before the start.</div>'; return; }
    const occ=occurrences(s-864e5,en2+864e5,{all:true,noCache:true}).filter(o=>!o.allDay && o.id!==e.id && !o.cancelled && !o.done && o.e>s && o.s<en2 && o.kind!=='busy');
    const k=key(new Date(s)); const fl=flightWindows(k).filter(w=>w.s<en2 && s<w.e);
    const msgs=[...occ.map(o=>`Overlaps “${esc(o.title)}” ${hm(o.s)}–${hm(o.e)}`), ...fl.map(w=>`During flight ${esc(code(w.f.from))}→${esc(code(w.f.to))}`), ...($('[name=calendarId]',F).value==='work'?offOverlap(k,s,en2).map(x=>'During '+esc(offLabel(x))):[])];
    $('#evConf',d).innerHTML = msgs.length?`<div class="warnbox">⚠ ${msgs.join('<br>')}</div>`:'';
  }
  checkConf();
  const rems=clone(e.reminders);
  const drawRems=()=>{ $('#evRem',d).innerHTML=ro?(rems.map(r=>`<div class="hint">🔔 ${esc((OFFSETS.find(o=>o[0]===+r.offsetMin)||[0,fmtDur(r.offsetMin)+' before'])[1])}</div>`).join('')||'<span class="hint">None</span>'):remFormHTML(rems); if(!ro) bindRems($('#evRem',d),rems,null,drawRems); };
  drawRems();
  bindMdEditor(d);
  const links=clone(e.links);
  const drawLinks=()=>{ $('#evLinks',d).innerHTML=links.map((l,i)=>ro?`<a href="${esc(l.url)}" target="_blank" rel="noopener">${esc(l.label||l.url)}</a>`:`<div class="li"><input type="text" data-l="label" data-i="${i}" value="${esc(l.label)}" placeholder="Label" style="max-width:150px"><input type="text" data-l="url" data-i="${i}" value="${esc(l.url)}" placeholder="https://…"><button type="button" class="ghost" data-ldel="${i}">✕</button></div>`).join('')+(ro?'':'<button type="button" data-ladd style="align-self:flex-start">＋ Link</button>');
    if(ro) return; $$('[data-l]',d).forEach(x=>x.oninput=()=>links[+x.dataset.i][x.dataset.l]=x.value); $$('[data-ldel]',d).forEach(b=>b.onclick=()=>{ links.splice(+b.dataset.ldel,1); drawLinks(); }); $('[data-ladd]',d).onclick=()=>{ links.push({label:'',url:''}); drawLinks(); }; };
  drawLinks();
  const linked=[...e.linkedTaskIds];
  const drawLinked=()=>{
    const open=data.tasks.filter(t=>isOpen(t)&&!linked.includes(t.id));
    $('#evTasks',d).innerHTML=linked.map(tid=>{ const t=task(tid); return t?`<div class="li"><span class="tag" style="cursor:pointer" data-topen="${t.id}">${isOpen(t)?'☐':'✓'} ${esc(t.title)}</span>${ro?'':`<button type="button" class="ghost" data-tdel="${t.id}">✕</button>`}</div>`:''; }).join('')
      +(ro?'':`<div class="li"><select data-tadd><option value="">Link a task…</option>${open.map(t=>`<option value="${t.id}">${esc(t.title)}</option>`).join('')}</select><button type="button" data-tnew>＋ New follow-up task</button></div>`);
    $$('[data-topen]',d).forEach(x=>x.onclick=()=>openTaskEditor(x.dataset.topen));
    if(ro) return;
    $$('[data-tdel]',d).forEach(b=>b.onclick=()=>{ linked.splice(linked.indexOf(b.dataset.tdel),1); drawLinked(); });
    $('[data-tadd]',d).onchange=ev2=>{ if(ev2.target.value){ linked.push(ev2.target.value); drawLinked(); } };
    $('[data-tnew]',d).onclick=()=>{ const title=$('[name=title]',F).value||'event'; const t=newTask({title:'Follow up: '+title, calendarId:$('[name=calendarId]',F).value, projectId:$('[name=projectId]',F).value||null, dueDate:keyAdd($('[name=sd]',F).value,2)});
      if(isPrivate(t)&&!ensurePrivReadySync()) return; touch(t); data.tasks.push(t); linked.push(t.id); changed(); drawLinked(); toast('Follow-up task created',{actions:[['Open',()=>openTaskEditor(t.id)]]}); };
  };
  drawLinked();

  function collect(){
    const v=n=>$(`[name=${n}]`,F).value;
    const o=clone(e);
    o.title=v('title').trim()||'Untitled'; o.calendarId=v('calendarId'); o.projectId=v('projectId')||null; o.category=v('category');
    o.allDay=allDay.checked; o.showAs=v('showAs'); o.status=v('status');
    if(o.allDay){ o.start=v('sd'); o.end=v('ed')&&v('ed')>=v('sd')?v('ed'):v('sd'); delete o.tz; }
    else { let s1=v('sd')+'T'+(v('st')||'09:00'), e1=(v('ed')||v('sd'))+'T'+(v('et')||v('st')||'10:00'); if(localDiffMin(s1,e1)<=0) e1=localAddMin(s1,S().defaultEventMin||60);
      o.tz=storeTz; o.start=tzConvert(s1,ACTIVE_TZ,storeTz); o.end=tzConvert(e1,ACTIVE_TZ,storeTz); }
    o.rrule=readRR(F); o.location=v('location').trim(); o.onlineLink=v('onlineLink').trim(); o.attendees=splitList(v('attendees')); o.notes=v('notes');
    o.tags=splitList(v('tags')); o.private=$('[name=private]',F).checked; o.reminders=rems; o.links=links.filter(l=>l.url); o.linkedTaskIds=linked;
    return o;
  }
  async function save(){
    if(!F.reportValidity()) return;
    const o=collect();
    if(isPrivate(o) && !ensurePrivReadySync()) return;
    if(isNew){ touch(o); data.events.push(o); changed(); closeDialog(d); renderAll(); toast(`Saved “${o.title}”`,{actions:[['Undo',undo]]}); return; }
    let scope='all';
    if(orig.rrule && occKey){
      scope=await confirmDlg({title:'Change repeating event', html:'Apply your changes to:'},[['Only this one','this'],['This and following','following'],['All of them','all','primary'],['Cancel',null]]);
      if(!scope) return;
    }
    const tgt=ev(id);
    if(scope==='this'){
      const ex=Object.assign({},tgt.exceptions[occKey]||{});
      if(o.start!==sv || o.end!==en) { ex.start=o.start; ex.end=o.end; }
      if(o.title!==tgt.title) ex.title=o.title; if(o.location!==tgt.location) ex.location=o.location;
      if(o.status==='cancelled' && tgt.status!=='cancelled') ex.status='cancelled'; else if(o.status!=='cancelled') delete ex.status;
      tgt.exceptions[occKey]=ex; touch(tgt);
    } else if(scope==='following' && occKey>tgt.start.slice(0,10)){
      const nu=clone(o); nu.id=uid('e'); delete nu.uid; nu.exceptions={}; for(const [k,x] of Object.entries(tgt.exceptions)) if(k>occKey) nu.exceptions[k]=x;
      tgt.rrule=Object.assign({},tgt.rrule,{until:keyAdd(occKey,-1),count:null}); for(const k of Object.keys(tgt.exceptions)) if(k>=occKey) delete tgt.exceptions[k];
      touch(tgt); touch(nu); nu.created=nu.updated; data.events.push(nu);
    } else {
      if(occKey && orig.rrule){ // shift the series by the change made on this occurrence
        const dd=Math.round((uparse(o.start.slice(0,10))-uparse(sv.slice(0,10)))/864e5);
        const span=o.allDay?Math.round((uparse(o.end.slice(0,10))-uparse(o.start.slice(0,10)))/864e5):localDiffMin(o.start,o.end);
        const bs=keyAdd(orig.start.slice(0,10),dd);
        o.start=o.allDay?bs:bs+'T'+o.start.slice(11,16); o.end=o.allDay?keyAdd(bs,span):localAddMin(o.start,span);
        delete (o.exceptions[occKey]||{}).start; delete (o.exceptions[occKey]||{}).end;
      }
      Object.assign(tgt,o); touch(tgt);
    }
    changed(); closeDialog(d); renderAll(); toast('Saved',{actions:[['Undo',undo]]});
  }
  const a=(n,fn)=>{ const b=$(`[data-a="${n}"]`,d); if(b) b.onclick=fn; };
  a('save',save); F.onsubmit=ev2=>{ ev2.preventDefault(); save(); };
  a('del',()=>{ closeDialog(d); deleteOcc(id, occKey||orig.start.slice(0,10), 'event'); });
  a('dup',()=>{ closeDialog(d); const c=duplicateItem(orig); openEventEditor(c.id); });
  a('conv',()=>{ closeDialog(d); const t=convertItem(orig); openTaskEditor(t.id); });
  a('tpl',()=>saveAsTemplate([collect()]));
  a('ics',()=>exportIcs({items:[orig], name:orig.title}));
  return d;
}

/* ---------------- TASK EDITOR ---------------- */
function openTaskEditor(id, draft, opts={}){
  if(editInTab()) return openEditTab('task', id, draft, null, opts);
  const orig = id ? task(id) : null;
  if(id && !orig) return;
  const t = clone(orig || draft || newTask());
  const dueD = t.dueDate&&t.dueTime ? tzConvert(t.dueDate+'T'+t.dueTime, t.tz||S().homeTz, ACTIVE_TZ) : null; // due shown in the active zone
  const isNew=!orig, ro=!editable;
  const d=openDialog(`<div class="dhd"><h2>${isNew?'New task':ro?'Task':'Edit task'}</h2>${!isNew&&blockedBy(orig).length?'<span class="tag">🔒 blocked</span>':''}<button class="ghost" data-close aria-label="Close">✕</button></div>
  <form class="frm" autocomplete="off">
    <input class="titlein redtitle full" name="title" value="${esc(t.title)}" placeholder="What needs doing?" ${ro?'disabled':''} required aria-label="Title">
    <label>Calendar</label><div class="row"><select name="calendarId" ${ro?'disabled':''}>${calOptions(t.calendarId)}</select><select name="projectId" ${ro?'disabled':''} aria-label="Project">${projOptions(t.calendarId,t.projectId)}</select></div>
    <label>Status</label><div class="row"><select name="status" ${ro?'disabled':''}>${Object.entries(TASK_STATUS).map(([k,v])=>`<option value="${k}" ${t.status===k?'selected':''}>${v}</option>`).join('')}</select>
      <select name="priority" ${ro?'disabled':''} aria-label="Priority">${[[1,'P1 urgent'],[2,'P2 high'],[3,'P3 normal'],[4,'P4 low']].map(([v,l])=>`<option value="${v}" ${+t.priority===v?'selected':''}>${l}</option>`).join('')}</select></div>
    <label>Due</label><div class="row"><input type="date" name="dueDate" value="${dueD?dueD.slice(0,10):(t.dueDate||'')}" ${ro?'disabled':''}><input type="time" name="dueTime" value="${dueD?dueD.slice(11,16):''}" ${ro?'disabled':''} step="300"><span class="hint" id="tkDue"></span></div>
    <label>Estimate</label><div class="row"><input type="number" name="estH" min="0" max="999" value="${t.estimateMin?Math.floor(t.estimateMin/60):''}" style="width:70px" ${ro?'disabled':''} aria-label="Hours"> h <input type="number" name="estM" min="0" max="59" step="5" value="${t.estimateMin?t.estimateMin%60:''}" style="width:70px" ${ro?'disabled':''} aria-label="Minutes"> min <span class="hint" id="tkPlan"></span></div>
    <div class="sec">Time blocks on the calendar</div>
    <div class="full list-edit" id="tkBlocks"></div>
    <div class="full" id="tkSlots"></div>
    <div class="sec">Checklist</div><div class="full list-edit" id="tkCheck"></div>
    <label>Depends on</label><div class="list-edit" id="tkDeps"></div>
    <label>Repeat</label>${rrFormHTML(t.rrule, t.dueDate||todayKey())}
    <div class="full hint">Repeating tasks need a due date; when you complete one, the next one is created.</div>
    <div class="sec">Reminders</div><div class="full" id="tkRem"></div>
    <div class="sec">Notes</div>${mdEditorHTML('notes', t.notes, {ro, rows:5, placeholder:'Details, links, steps… Markdown: **bold**, *italic*, - list, [link](https://…)'})}
    <label>Tags</label><input type="text" name="tags" value="${esc(t.tags.join(', '))}" placeholder="e.g. drafting" ${ro?'disabled':''}>
    <label>Privacy</label><label class="hint"><input type="checkbox" name="private" ${t.private?'checked':''} ${ro?'disabled':''}> Private — hide from viewers (needs the Personal passphrase)</label>
  </form>
  <div class="dacts">
    ${!isNew&&editable?'<button data-a="del" class="danger">Delete</button><button data-a="dup">Duplicate</button><button data-a="conv">Convert to event</button><button data-a="tpl">Save as template</button>':''}
    ${!isNew&&editable&&isOpen(orig)?'<button data-a="done">✓ Mark done</button>':''}
    <span class="sp"></span><button data-close>${ro?'Close':'Cancel'}</button>${editable?'<button class="primary" data-a="save">Save</button>':''}
  </div>`, {size:'mid', focus:'[name=title]'});
  const F=$('form',d);
  bindCalProj(F); bindRR(F); bindMdEditor(d);
  const v=n=>$(`[name=${n}]`,F).value;
  const est=()=>((+v('estH')||0)*60+(+v('estM')||0))||null;
  // blocks
  const blocks=clone(t.blocks).map(b=>({...b, start:tzConvert(b.start,b.tz||ACTIVE_TZ,ACTIVE_TZ), end:tzConvert(b.end,b.tz||ACTIVE_TZ,ACTIVE_TZ), tz:ACTIVE_TZ})); // shown and saved in the active zone
  const drawPlan=()=>{
    const pl=blocks.reduce((a,b)=>a+Math.max(0,localDiffMin(b.start,b.end)),0), e2=est();
    $('#tkPlan',d).innerHTML = e2?`${fmtH(pl)} scheduled of ${fmtH(e2)}${pl<e2?` · <b style="color:var(--leave)">${fmtH(e2-pl)} still to schedule</b>`:' ✓'}`:(pl?fmtH(pl)+' scheduled':'');
    $('#tkDue',d).textContent = v('dueDate') && v('dueDate')<todayKey() && v('status')!=='done' ? 'overdue' : '';
  };
  const drawBlocks=()=>{
    $('#tkBlocks',d).innerHTML = blocks.map((b,i)=>{ const past=!b.done && zonedToMs(b.end,b.tz)<NOW();
      return `<div class="li" data-bi="${i}">${ro?`<span>${esc(fmtShort(fromKey(b.start.slice(0,10))))} ${b.start.slice(11)}–${b.end.slice(11)} ${b.done?'✓':''}</span>`:
      `<input type="date" data-b="d" value="${b.start.slice(0,10)}"><input type="time" data-b="s" value="${b.start.slice(11,16)}" step="300"> – <input type="time" data-b="e" value="${b.end.slice(11,16)}" step="300">
       <span class="hint">${esc(tzShort(ACTIVE_TZ))}</span>
       <label class="hint"><input type="checkbox" data-b="done" ${b.done?'checked':''}> done</label>${b.done?`<input type="number" data-b="act" value="${b.actualMin!=null?b.actualMin:localDiffMin(b.start,b.end)}" style="width:70px" title="Minutes spent" aria-label="Minutes spent"> min`:''}
       ${past?'<span class="tag" style="color:var(--missed)">missed</span><button type="button" data-bres="'+i+'">↻ Next free slot</button>':''}
       <button type="button" class="ghost" data-bdel="${i}" aria-label="Remove block">✕</button>`}</div>`; }).join('')
      + (ro?'':'<div class="li"><button type="button" data-badd>＋ Add a time block</button><button type="button" data-bfind>🔎 Find a slot</button><button type="button" data-bauto>⚡ Fill the estimate</button></div>')
      + (!blocks.length&&ro?'<span class="hint">Not scheduled.</span>':'');
    drawPlan(); if(ro) return;
    $$('[data-bi]',d).forEach(li=>{ const b=blocks[+li.dataset.bi];
      $$('[data-b]',li).forEach(x=>x.onchange=()=>{
        const f=x.dataset.b, dur=localDiffMin(b.start,b.end);
        if(f==='d'){ b.start=x.value+'T'+b.start.slice(11,16); b.end=localAddMin(b.start,dur); }
        if(f==='s'){ b.start=b.start.slice(0,10)+'T'+x.value; b.end=localAddMin(b.start,dur); }
        if(f==='e'){ b.end=b.start.slice(0,10)+'T'+x.value; if(localDiffMin(b.start,b.end)<=0) b.end=localAddMin(b.start,15); }
        if(f==='tz') b.tz=x.value; if(f==='done'){ b.done=x.checked; if(!b.done) delete b.actualMin; } if(f==='act') b.actualMin=Math.max(0,+x.value||0);
        drawBlocks();
      }); });
    $$('[data-bdel]',d).forEach(x=>x.onclick=()=>{ blocks.splice(+x.dataset.bdel,1); drawBlocks(); });
    $$('[data-bres]',d).forEach(x=>x.onclick=()=>{ const b=blocks[+x.dataset.bres]; const len=localDiffMin(b.start,b.end); const sl=findSlots({len, calendarId:v('calendarId'), dueDate:null, exclude:{id:t.id}, n:1})[0]; if(!sl) return toast('No free slot found in the next 14 days.'); Object.assign(b,slotToBlock(sl)); drawBlocks(); });
    $('[data-badd]',d).onclick=()=>{ const n=new Date(NOW()); n.setMinutes(0,0,0); n.setHours(n.getHours()+1); const k=key(n), tz=ACTIVE_TZ; const st=k+'T'+hm(n.getTime()); blocks.push({id:uid('b'),start:st,end:localAddMin(st,S().defaultBlockMin||60),tz,done:false}); drawBlocks(); };
    $('[data-bfind]',d).onclick=()=>showSlots();
    $('[data-bauto]',d).onclick=()=>{ const e2=est(); if(!e2) return toast('Set an estimate first.'); let need=e2-blocks.reduce((a,b)=>a+(b.done?(b.actualMin??localDiffMin(b.start,b.end)):Math.max(0,localDiffMin(b.start,b.end))),0); if(need<=0) return toast('The estimate is already fully scheduled.');
      const extra=[...blocks]; let guard=0; while(need>0 && guard++<20){ const len=Math.min(need,120); const sl=findSlots({len:Math.max(15,len), calendarId:v('calendarId'), dueDate:v('dueDate')||null, dueTime:v('dueTime')||null, exclude:{id:t.id, blocks:extra}, n:1})[0]; if(!sl) break; const nb={id:uid('b'),...slotToBlock(sl),done:false}; blocks.push(nb); extra.push(nb); need-=len; }
      drawBlocks(); if(need>0) toast('Not enough free time before the due date for the whole estimate.'); };
  };
  function showSlots(){
    const e2=est(), pl=blocks.reduce((a,b)=>a+localDiffMin(b.start,b.end),0);
    const len=Math.max(15, Math.min(e2&&e2>pl?e2-pl:(S().defaultBlockMin||60), 120));
    const sl=findSlots({len, calendarId:v('calendarId'), dueDate:v('dueDate')||null, dueTime:v('dueTime')||null, exclude:{id:t.id, blocks}, n:3});
    $('#tkSlots',d).innerHTML = sl.length ? `<div class="hint" style="margin-bottom:4px">Free slots of ${fmtDur(len)}${v('dueDate')?' before the due date':''}:</div>`+sl.map((s,i)=>`<div class="slot"><span>${esc(fmtShort(new Date(s.s)))} · ${hm(s.s)}–${hm(s.e)}</span><button type="button" data-slot="${i}">Use</button></div>`).join('')
      : '<div class="warnbox">No free slot found'+(v('dueDate')?' before the due date':' in the next 14 days')+'.</div>';
    $$('[data-slot]',d).forEach(b=>b.onclick=()=>{ blocks.push({id:uid('b'),...slotToBlock(sl[+b.dataset.slot]),done:false}); $('#tkSlots',d).innerHTML=''; drawBlocks(); });
  }
  drawBlocks();
  ['estH','estM','dueDate','status'].forEach(n=>$(`[name=${n}]`,F).addEventListener('input',drawPlan));
  // checklist
  const cl=clone(t.checklist);
  const drawCheck=()=>{
    $('#tkCheck',d).innerHTML=cl.map((c,i)=>`<div class="li"><input type="checkbox" data-ci="${i}" ${c.done?'checked':''} ${ro?'disabled':''} aria-label="Done"><input type="text" data-ct="${i}" value="${esc(c.text)}" ${ro?'disabled':''} aria-label="Checklist item">${ro?'':`<button type="button" class="ghost" data-cprom="${i}" title="Turn into a separate task">↗ task</button><button type="button" class="ghost" data-cdel="${i}" aria-label="Remove">✕</button>`}</div>`).join('')
      +(ro?'':'<div class="li"><input type="text" data-cnew placeholder="Add a checklist item and press Enter"></div>');
    if(ro) return;
    $$('[data-ci]',d).forEach(x=>x.onchange=()=>cl[+x.dataset.ci].done=x.checked);
    $$('[data-ct]',d).forEach(x=>x.oninput=()=>cl[+x.dataset.ct].text=x.value);
    $$('[data-cdel]',d).forEach(x=>x.onclick=()=>{ cl.splice(+x.dataset.cdel,1); drawCheck(); });
    $$('[data-cprom]',d).forEach(x=>x.onclick=()=>{ const c=cl.splice(+x.dataset.cprom,1)[0]; const nt=newTask({title:c.text, calendarId:v('calendarId'), projectId:v('projectId')||null, dueDate:v('dueDate')||null}); if(isPrivate(nt)&&!ensurePrivReadySync()) return; touch(nt); data.tasks.push(nt); changed(); drawCheck(); toast(`Created task “${c.text}”`); });
    const n=$('[data-cnew]',d); n.onkeydown=ev2=>{ if(ev2.key==='Enter'){ ev2.preventDefault(); if(n.value.trim()){ cl.push({id:uid('c'),text:n.value.trim(),done:false}); drawCheck(); $('[data-cnew]',d).focus(); } } };
  };
  drawCheck();
  // dependencies
  const deps=[...t.dependsOn];
  const drawDeps=()=>{
    const cand=data.tasks.filter(x=>x.id!==t.id && isOpen(x) && !deps.includes(x.id));
    $('#tkDeps',d).innerHTML=deps.map(x=>{ const dt=task(x); return dt?`<div class="li"><span class="tag">${isOpen(dt)?'🔒':'✓'} ${esc(dt.title)}</span>${ro?'':`<button type="button" class="ghost" data-ddel="${x}">✕</button>`}</div>`:''; }).join('')
      +(ro?(deps.length?'':'<span class="hint">None</span>'):`<select data-dadd><option value="">Add a task this one waits for…</option>${cand.map(x=>`<option value="${x.id}">${esc(x.title)}</option>`).join('')}</select>`);
    if(ro) return;
    $$('[data-ddel]',d).forEach(b=>b.onclick=()=>{ deps.splice(deps.indexOf(b.dataset.ddel),1); drawDeps(); });
    $('[data-dadd]',d).onchange=ev2=>{ if(ev2.target.value){ deps.push(ev2.target.value); drawDeps(); } };
  };
  drawDeps();
  // reminders
  const rems=clone(t.reminders);
  const anchors=[['due','Before due'],['block','Before each block']];
  const drawRems=()=>{ $('#tkRem',d).innerHTML=ro?(rems.map(r=>`<div class="hint">🔔 ${r.anchor==='block'?'Block':'Due'}: ${esc((OFFSETS.find(o=>o[0]===+r.offsetMin)||[0,fmtDur(r.offsetMin)+' before'])[1])}</div>`).join('')||'<span class="hint">None</span>'):remFormHTML(rems,anchors); if(!ro) bindRems($('#tkRem',d),rems,anchors,drawRems); };
  drawRems();

  function collect(){
    const o=clone(t);
    o.title=v('title').trim()||'Untitled'; o.calendarId=v('calendarId'); o.projectId=v('projectId')||null;
    const st=v('status'); if(st!==o.status){ o.completedAt=st==='done'?nowISO():null; } o.status=st;
    o.priority=+v('priority'); o.dueDate=v('dueDate')||null; o.dueTime=o.dueDate?(v('dueTime')||null):null;
    if(o.dueTime){ const ttz=t.dueTime?(t.tz||ACTIVE_TZ):ACTIVE_TZ; const w=tzConvert(o.dueDate+'T'+o.dueTime,ACTIVE_TZ,ttz); o.tz=ttz; o.dueDate=w.slice(0,10); o.dueTime=w.slice(11,16); } o.estimateMin=est();
    o.blocks=blocks; o.checklist=cl.filter(c=>c.text.trim()); o.dependsOn=deps; o.rrule=o.dueDate?readRR(F):null; if(o.rrule && !o.rrStart) o.rrStart=o.dueDate;
    o.reminders=rems; o.notes=v('notes'); o.tags=splitList(v('tags')); o.private=$('[name=private]',F).checked;
    return o;
  }
  function save(){
    if(!F.reportValidity()) return false;
    const o=collect();
    if(isPrivate(o) && !ensurePrivReadySync()) return false;
    const becameDone = o.status==='done' && (isNew || isOpen(orig));
    if(isNew){ if(becameDone) o.status='todo'; touch(o); data.tasks.push(o); if(opts.linkEvent){ const e=ev(opts.linkEvent); if(e){ e.linkedTaskIds.push(o.id); touch(e); } } }
    else { if(becameDone) o.status=orig.status; Object.assign(orig,o); touch(orig); }
    changed(); closeDialog(d);
    if(becameDone) setTaskStatus(task(o.id),'done'); else { renderAll(); toast(isNew?`Created “${o.title}”`:'Saved',{actions:[['Undo',undo]]}); }
    const dep=o.dependsOn.map(task).filter(x=>x&&isOpen(x)).find(x=>x.blocks.some(b=>o.blocks.some(ob=>zonedToMs(ob.start,ob.tz)<zonedToMs(b.end,b.tz))));
    if(dep) toast(`⚠ “${o.title}” is scheduled before “${dep.title}”, which it depends on, is finished.`);
    return true;
  }
  const a=(n,fn)=>{ const b=$(`[data-a="${n}"]`,d); if(b) b.onclick=fn; };
  a('save',save); F.onsubmit=ev2=>{ ev2.preventDefault(); save(); };
  a('done',()=>{ $('[name=status]',F).value='done'; save(); });
  a('del',async()=>{ if(!await confirmDlg({title:'Delete task',html:`Delete “${esc(orig.title)}”? You can restore it from ⚙ Settings → Trash for 30 days.`},[['Delete','y','danger'],['Cancel',null]])) return; closeDialog(d); removeItem(orig); });
  a('dup',()=>{ closeDialog(d); const c=duplicateItem(orig); openTaskEditor(c.id); });
  a('conv',()=>{ closeDialog(d); const e=convertItem(orig); openEventEditor(e.id); });
  a('tpl',()=>saveAsTemplate([collect()]));
  return d;
}

/* ---------------- PROJECT EDITOR ---------------- */
function openProjectEditor(id, draft){
  if(editInTab()) return openEditTab('project', id, draft);
  const orig=id?proj(id):null; if(id&&!orig) return;
  const p=clone(orig||newProject(draft||{}));
  const isNew=!orig, ro=!editable;
  const parents=data.projects.filter(x=>x.calendarId===p.calendarId && !x.parentId && x.id!==p.id && x.status!=='archived');
  const d=openDialog(`<div class="dhd"><h2>${isNew?'New project':ro?'Project':'Edit project'}</h2><button class="ghost" data-close aria-label="Close">✕</button></div>
  <form class="frm" autocomplete="off">
    <input class="titlein full" name="name" value="${esc(p.name)}" placeholder="Project name" required ${ro?'disabled':''} aria-label="Name">
    <label>Calendar</label><select name="calendarId" ${ro?'disabled':''}>${calOptions(p.calendarId)}</select>
    <label>Colour &amp; icon</label><div class="row"><input type="color" name="color" value="${esc(p.color)}" ${ro?'disabled':''} aria-label="Colour"><span class="daychips">${PALETTE.map(c=>`<button type="button" data-col="${c}" style="background:${c};width:22px;height:22px;padding:0;border-radius:6px" aria-label="Colour ${c}"></button>`).join(' ')}</span></div>
    <label></label><div class="row daychips">${PROJ_ICONS.map(i=>`<button type="button" data-ic="${i}" class="${p.icon===i?'on':''}">${i}</button>`).join('')}</div>
    <label>Status</label><select name="status" ${ro?'disabled':''}>${Object.entries(PROJ_STATUS).map(([k,v])=>`<option value="${k}" ${p.status===k?'selected':''}>${v}</option>`).join('')}</select>
    <label>Dates</label><div class="row">Start <input type="date" name="startDate" value="${p.startDate||''}" ${ro?'disabled':''}> Due <input type="date" name="dueDate" value="${p.dueDate||''}" ${ro?'disabled':''}></div>
    <label>Time budget</label><div class="row"><input type="number" name="budgetHours" min="0" step="0.5" value="${p.budgetHours??''}" style="width:90px" ${ro?'disabled':''}> hours <span class="hint">warns at 80% and 100%</span></div>
    <label>Sub-project of</label><select name="parentId" ${ro?'disabled':''}><option value="">— none —</option>${parents.map(x=>`<option value="${x.id}" ${p.parentId===x.id?'selected':''}>${esc(x.name)}</option>`).join('')}</select>
    <label>Mission / trip</label><div class="row">from <input type="date" name="tripFrom" value="${p.tripFrom||''}" ${ro?'disabled':''}> to <input type="date" name="tripTo" value="${p.tripTo||''}" ${ro?'disabled':''}><span class="hint">links the project to flights in that period (Trips report)</span></div>
    <label>Remind</label><label class="hint"><input type="checkbox" name="remind" ${p.reminders&&p.reminders.length?'checked':''} ${ro?'disabled':''}> 1 day before the project due date</label>
    <label></label><label class="hint"><input type="checkbox" name="pinned" ${p.pinned?'checked':''} ${ro?'disabled':''}> Pin to the top of the list</label>
    <label>Description</label><textarea name="description" rows="3" ${ro?'disabled':''}>${esc(p.description)}</textarea>
  </form>
  <div class="dacts">${!isNew&&editable?'<button data-a="del" class="danger">Delete…</button><button data-a="dup">Duplicate</button><button data-a="tpl">Save as template</button><button data-a="arch">'+(p.status==='archived'?'Unarchive':'Archive')+'</button>':''}
    <span class="sp"></span><button data-close>${ro?'Close':'Cancel'}</button>${editable?'<button class="primary" data-a="save">Save</button>':''}</div>`,{size:'mid',focus:'[name=name]'});
  const F=$('form',d), v=n=>$(`[name=${n}]`,F).value;
  $$('[data-col]',d).forEach(b=>b.onclick=()=>{ $('[name=color]',F).value=b.dataset.col; });
  let icon=p.icon; $$('[data-ic]',d).forEach(b=>b.onclick=()=>{ icon=b.dataset.ic; $$('[data-ic]',d).forEach(x=>x.classList.toggle('on',x===b)); });
  function collect(){ return Object.assign(clone(p),{name:v('name').trim()||'Untitled project', calendarId:v('calendarId'), color:v('color'), icon, status:v('status'), startDate:v('startDate')||null, dueDate:v('dueDate')||null, budgetHours:v('budgetHours')?+v('budgetHours'):null, parentId:v('parentId')||null, tripFrom:v('tripFrom')||null, tripTo:v('tripTo')||null, pinned:$('[name=pinned]',F).checked, description:v('description'), reminders:$('[name=remind]',F).checked?[{offsetMin:1440,channel:'popup'}]:[]}); }
  function save(){
    if(!F.reportValidity()) return;
    const o=collect();
    if(isPrivCal(o.calendarId) && !ensurePrivReadySync()) return;
    if(isNew){ touch(o); data.projects.push(o); }
    else { const calChanged=orig.calendarId!==o.calendarId; Object.assign(orig,o); touch(orig);
      if(calChanged) for(const it of [...data.events,...data.tasks]) if(it.projectId===orig.id){ it.calendarId=o.calendarId; touch(it); } }
    changed(); closeDialog(d); renderAll(); toast(isNew?`Created project “${o.name}”`:'Saved');
  }
  const a=(n,fn)=>{ const b=$(`[data-a="${n}"]`,d); if(b) b.onclick=fn; };
  a('save',save); F.onsubmit=e=>{ e.preventDefault(); save(); };
  a('arch',()=>{ orig.status=orig.status==='archived'?'active':'archived'; touch(orig); changed(); closeDialog(d); renderAll(); });
  a('dup',()=>{ const c=clone(orig); c.id=uid('p'); c.name+=' (copy)'; touch(c); c.created=c.updated; data.projects.push(c);
    for(const t of data.tasks.filter(t=>t.projectId===orig.id)){ const ct=clone(t); ct.id=uid('t'); ct.projectId=c.id; ct.blocks=[]; ct.status='todo'; ct.completedAt=null; touch(ct); data.tasks.push(ct); }
    changed(); closeDialog(d); renderAll(); toast(`Duplicated “${orig.name}” with its tasks`); });
  a('tpl',()=>saveProjectAsTemplate(orig));
  a('del',async()=>{
    const items=[...data.events,...data.tasks].filter(x=>x.projectId===orig.id);
    const choice=await confirmDlg({title:'Delete project', html:`Delete “${esc(orig.name)}”? It has ${items.length} event${items.length===1?'':'s'}/task${items.length===1?'':'s'}. Everything goes to the Trash for 30 days.`},[['Move items to Inbox','move'],['Delete items too','del','danger'],['Cancel',null]]);
    if(!choice) return;
    for(const it of items){ if(choice==='move'){ it.projectId=null; touch(it); } else removeItem(it,true); }
    for(const sp of data.projects.filter(x=>x.parentId===orig.id)){ sp.parentId=null; touch(sp); }
    removeItem(orig,true); changed(); closeDialog(d); closeDialog(); renderAll(); toast('Project deleted',{actions:[['Undo',undo]]});
  });
}

/* ---------------- PROJECT PAGE ---------------- */
function projStats(p){
  const ids=new Set([p.id, ...data.projects.filter(x=>x.parentId===p.id).map(x=>x.id)]);
  const ts=data.tasks.filter(t=>ids.has(t.projectId)), es=data.events.filter(e=>ids.has(e.projectId));
  const done=ts.filter(t=>t.status==='done').length, open=ts.filter(isOpen).length;
  const est=ts.reduce((a,t)=>a+(t.estimateMin||0),0), estDone=ts.filter(t=>t.status==='done').reduce((a,t)=>a+(t.estimateMin||0),0);
  const planned=ts.reduce((a,t)=>a+plannedMin(t),0)+es.filter(e=>!e.allDay&&e.status!=='cancelled'&&!e.rrule).reduce((a,e)=>a+localDiffMin(e.start,e.end),0);
  const spent=ts.reduce((a,t)=>a+spentMin(t),0);
  const progress = est && ts.every(t=>t.estimateMin) ? estDone/est : (ts.length? done/(done+open||1) : 0);
  return {ts, es, done, open, overdue:ts.filter(isOverdue).length, est, planned, spent, progress};
}
let ppTab='tasks', ppView='list';
function openProjectPage(id){ if(useTabs()) return openDetailTab({t:'project', id}); showProjectPage(id, null); }
/* page: element to draw into (details tab), or null to show it in a dialog */
function showProjectPage(id, page){
  const box=()=>page || dd.querySelector('.box'), close=()=>page ? closeDetailTab() : closeDialog(dd);
  const draw=()=>{
    const p=proj(id); if(!p){ if(page) return renderDetailMissing('project'); closeDialog(dd); return; }
    if(page) document.title=(p.icon||'')+' '+p.name+' · Work & Travel';
    const st=projStats(p), c=cal(p.calendarId);
    const budget=p.budgetHours?`<div style="min-width:220px"><div class="hint">Hours: ${fmtH(st.planned)} planned · ${fmtH(st.spent)} spent · budget ${p.budgetHours} h</div><div class="progress ${st.planned/60>p.budgetHours?'over':''}"><i style="width:${Math.min(100,st.spent/60/p.budgetHours*100)}%"></i></div></div>`:'';
    let body='';
    if(ppTab==='tasks'){
      const list=sortTasks([...st.ts],'due');
      body=`<div class="vtools"><div class="seg"><button data-pv="list" class="${ppView==='list'?'on':''}">List</button><button data-pv="board" class="${ppView==='board'?'on':''}">Board</button></div>${editable?'<button data-pnew="task">＋ Task</button><button data-pnew="event">＋ Event</button>':''}</div>`;
      if(ppView==='list') body+=`<div class="tl">${list.filter(isOpen).map(t=>taskRowHTML(t)).join('')||'<div class="hint">No open tasks.</div>'}${list.some(t=>!isOpen(t))?`<div class="tgroup">Completed</div>${list.filter(t=>!isOpen(t)).map(t=>taskRowHTML(t)).join('')}`:''}</div>`;
      else body+=`<div class="board">${Object.keys(TASK_STATUS).map(s=>`<div class="bcol" data-status="${s}"><h4><span>${TASK_STATUS[s]}</span><span>${list.filter(t=>(t.status||'todo')===s).length}</span></h4>${list.filter(t=>(t.status||'todo')===s).map(t=>taskRowHTML(t)).join('')}</div>`).join('')}</div>`;
    } else if(ppTab==='timeline'){
      const from=NOW()-60*864e5, to=NOW()+180*864e5;
      const items=[];
      for(const e of st.es) for(const o of expandEvent(e,from,to)) items.push(o);
      for(const t of st.ts) for(const o of blockOccs(t,from,to)) items.push(o);
      for(const t of st.ts) if(t.dueDate) items.push({kind:'due', s:fromKey(t.dueDate).getTime()+86399000, title:t.title, item:t, id:t.id});
      items.sort((a,b)=>a.s-b.s);
      let lastDay='';
      body='<div class="ag">'+items.map(o=>{ const k=key(new Date(o.s)); const hdr=k!==lastDay?`<div class="tgroup">${esc(fmtLong(fromKey(k)))}</div>`:''; lastDay=k;
        if(o.kind==='due') return hdr+agTaskRow(o.item, 'Due');
        return hdr+agOccRow(o, o.allDay?'All day':hm(o.s)+'–'+hm(o.e)); }).join('')+(items.length?'':'<div class="hint">Nothing in the last 60 or next 180 days.</div>')+'</div>';
    } else {
      body=`<div class="kpis">
        <div class="kpi"><div class="v">${Math.round(st.progress*100)}%</div><div class="l">progress</div></div>
        <div class="kpi"><div class="v">${st.open}</div><div class="l">open tasks</div></div>
        <div class="kpi"><div class="v">${st.done}</div><div class="l">done</div></div>
        <div class="kpi"><div class="v" style="${st.overdue?'color:var(--sick)':''}">${st.overdue}</div><div class="l">overdue</div></div>
        <div class="kpi"><div class="v">${fmtH(st.est)}</div><div class="l">estimated</div></div>
        <div class="kpi"><div class="v">${fmtH(st.planned)}</div><div class="l">planned</div></div>
        <div class="kpi"><div class="v">${fmtH(st.spent)}</div><div class="l">spent</div></div>
        ${p.budgetHours?`<div class="kpi"><div class="v" style="${st.spent/60>p.budgetHours?'color:var(--sick)':''}">${Math.round(st.spent/60/p.budgetHours*100)}%</div><div class="l">of ${p.budgetHours} h budget</div></div>`:''}
      </div>
      ${barChart([{label:'Estimated',v:st.est/60,c:'var(--muted)'},{label:'Planned',v:st.planned/60,c:p.color},{label:'Spent',v:st.spent/60,c:'var(--tele)'},...(p.budgetHours?[{label:'Budget',v:p.budgetHours,c:'var(--leave)'}]:[])],'h')}
      <div class="rsec">Upcoming deadlines</div>${st.ts.filter(t=>isOpen(t)&&t.dueDate).sort((a,b)=>a.dueDate.localeCompare(b.dueDate)).slice(0,8).map(t=>taskRowHTML(t)).join('')||'<div class="hint">None.</div>'}`;
    }
    box().innerHTML=`<div class="dhd"><span class="dotc" style="background:${esc(p.color)};width:16px;height:16px"></span><h2>${esc(p.icon||'')} ${esc(p.name)}</h2>
      <span class="tag">${esc(c.name)}</span><span class="tag">${PROJ_STATUS[p.status]}</span>${editable?'<button data-pedit>✎ Edit</button>':''}<button data-prep>📊 Report</button><button class="ghost" data-close>✕</button></div>
      <div class="pp-head">${p.dueDate?`<span class="hint">Due ${esc(fmtShort(fromKey(p.dueDate)))}</span>`:''}<div style="flex:1;min-width:180px"><div class="hint">${st.done} of ${st.done+st.open} tasks done</div><div class="progress"><i style="width:${Math.round(st.progress*100)}%"></i></div></div>${budget}</div>
      ${p.description?`<p class="hint">${esc(p.description)}</p>`:''}
      <div class="tabs">${[['tasks','Tasks'],['timeline','Timeline'],['stats','Stats']].map(([k,l])=>`<button data-ptab="${k}" class="${ppTab===k?'on':''}">${l}</button>`).join('')}</div>${body}`;
    const B=box();
    $$('[data-close]',B).forEach(b=>b.onclick=close);
    $$('[data-ptab]',B).forEach(b=>b.onclick=()=>{ ppTab=b.dataset.ptab; draw(); });
    $$('[data-pv]',B).forEach(b=>b.onclick=()=>{ ppView=b.dataset.pv; draw(); });
    const pe=$('[data-pedit]',B); if(pe) pe.onclick=()=>openProjectEditor(id);
    $('[data-prep]',B).onclick=()=>openReports('project',{projectId:id});
    $$('[data-pnew]',B).forEach(b=>b.onclick=()=> b.dataset.pnew==='task'?openTaskEditor(null,newTask({projectId:id,calendarId:p.calendarId})):openEventEditor(null,newEvent({projectId:id,calendarId:p.calendarId})));
    bindTaskRows(B);
    $$('[data-occ]',B).forEach(r=>r.onclick=()=>openOccPopover(r.dataset.occ,r.dataset.oc,r.dataset.ok,r));
    $$('.bcol',B).forEach(c=>{ c.addEventListener('dragover',e=>{ if(editable){ e.preventDefault(); c.classList.add('dragover'); } }); c.addEventListener('dragleave',()=>c.classList.remove('dragover'));
      c.addEventListener('drop',e=>{ c.classList.remove('dragover'); e.preventDefault(); const t=task(e.dataTransfer.getData('text/x-task')); if(t) setTaskStatus(t,c.dataset.status); }); });
  };
  const dd=page ? null : openDialog('',{size:'wide', refresh:()=>draw()});
  draw();
  return draw;
}

/* ============================================================
   Planning: free slots, reschedule, auto-schedule, plan my day,
   timer, focus mode
   ============================================================ */
function busyIntervals(fromMs, toMs, exclude){
  const out=[];
  for(const o of occurrences(fromMs,toMs,{all:true,noCache:true})){
    if(o.allDay || o.cancelled) continue;
    if(o.kind==='event' && o.item.showAs==='free') continue;
    if(o.kind==='block' && o.done) continue;
    if(exclude && exclude.id && o.id===exclude.id && o.kind==='block') continue;
    out.push([o.s,o.e]);
  }
  if(privLocked) for(const b of arr(data.busy)) if(b.s){ const bs=toDisp(b.s), be=toDisp(b.e); if(be>fromMs && bs<toMs) out.push([bs,be]); }
  if(exclude && exclude.blocks) for(const b of exclude.blocks){ if(b.done) continue; const s=zonedToMs(b.start,b.tz), e=zonedToMs(b.end,b.tz); if(e>fromMs && s<toMs) out.push([s,e]); }
  for(let d=startOfDay(new Date(fromMs)); d.getTime()<toMs; d=addDays(d,1)) for(const w of flightWindows(key(d))) out.push([w.s,w.e]);
  for(const w of flightWindows(key(addDays(startOfDay(new Date(fromMs)),-1)))) out.push([w.s,w.e]);
  return out.sort((a,b)=>a[0]-b[0]);
}
function quietWin(){ return [hmToMin(prefs.quietEnd)??450, hmToMin(prefs.quietStart)??1320]; }
/* windows (minutes of the day) where a task of this calendar may be scheduled */
function allowedWindows(k, calendarId){
  const personal = calendarId!=='work';
  const wd=dow(fromKey(k)), work=S().workDays.includes(wd);
  const ws=hmToMin(S().workStart)||540, we=hmToMin(S().workEnd)||1080; const [qe,qs]=quietWin();
  if(!personal){ if(!work) return []; let wins=[[ws,we]];
    for(const x of offIntervals(k)) wins=wins.flatMap(([a,b])=>[[a,Math.min(b,x.a)],[Math.max(a,x.b),b]]).filter(([a,b])=>b-a>=15);
    return wins; }
  return work ? [[qe,ws],[we,qs]].filter(([a,b])=>b-a>=15) : [[Math.max(qe,540),qs]];
}
/* opts: {len, calendarId, dueDate, dueTime, exclude:{id,blocks}, n, fromMs, days, onlyDay} */
function findSlots(opts){
  const len=Math.max(15,opts.len||60), n=opts.n||3, out=[];
  const now=NOW(), from=Math.max(now, opts.fromMs||now);
  const fromRounded=Math.ceil(from/(15*60000))*15*60000;
  const cutoff = opts.dueDate ? zonedToMs(opts.dueDate+'T'+(opts.dueTime||S().workEnd||'18:00'), S().homeTz) : null;
  const days = opts.onlyDay ? 1 : (opts.days || (cutoff ? Math.max(1, Math.ceil((cutoff-from)/864e5)+1) : 14));
  const start = opts.onlyDay ? fromKey(opts.onlyDay) : startOfDay(new Date(from));
  const end = addDays(start, Math.min(days,60)).getTime();
  const busy=busyIntervals(start.getTime(), end, opts.exclude);
  for(let i=0;i<Math.min(days,60) && out.length<n;i++){
    const d=addDays(start,i), k=key(d), d0=d.getTime();
    for(const [a,b] of allowedWindows(k, opts.calendarId||'work')){
      let ws=Math.max(d0+a*60000, fromRounded), we=d0+b*60000;
      if(cutoff) we=Math.min(we,cutoff);
      let t=ws;
      for(const [bs,be] of busy){
        if(be<=t) continue; if(bs>=we) break;
        if(bs-t>=len*60000){ out.push({s:t,e:t+len*60000,k}); if(out.length>=n) return out; }
        t=Math.max(t,be);
      }
      if(we-t>=len*60000 && out.length<n) out.push({s:t,e:t+len*60000,k});
      if(out.length>=n) return out;
    }
  }
  return out;
}
function slotToBlock(sl){ const tz=ACTIVE_TZ; return {start:msToZoned(sl.s,tz), end:msToZoned(sl.e,tz), tz}; }
function rescheduleBlock(t, b, quiet){
  const len=localDiffMin(b.start,b.end);
  const sl=findSlots({len, calendarId:t.calendarId, exclude:{id:t.id, blocks:t.blocks.filter(x=>x!==b)}, n:1})[0];
  if(!sl){ if(!quiet) toast('No free slot found in the next 14 days.'); return false; }
  Object.assign(b, slotToBlock(sl)); touch(t);
  if(!quiet){ changed(); renderAll(); toast(`Moved to ${fmtShort(new Date(sl.s))} ${hm(sl.s)}`,{actions:[['Undo',undo]]}); }
  return true;
}
function freeWorkMinutes(k){
  const d0=fromKey(k).getTime(); let free=0;
  const busy=busyIntervals(d0, d0+864e5);
  for(const [a,b] of allowedWindows(k,'work')){
    let s=Math.max(d0+a*60000, NOW()), e=d0+b*60000; if(e<=s) continue;
    let tot=e-s; for(const [bs,be] of busy){ const o=Math.min(e,be)-Math.max(s,bs); if(o>0) tot-=o; }
    free+=Math.max(0,tot);
  }
  return free/60000;
}

/* ---------- auto-schedule (preview, then accept) ---------- */
function planTasks(ts, opts={}){
  const order=[...ts].filter(isOpen).sort((a,b)=>(a.dueDate||'9999').localeCompare(b.dueDate||'9999')||(a.priority||3)-(b.priority||3));
  const extra=[], plan=[], skipped=[];
  for(const t of order){
    let need = t.estimateMin ? remainingMin(t)-futurePlannedMin(t) : (t.blocks.some(b=>!b.done)?0:(S().defaultBlockMin||60));
    if(need<=0){ skipped.push({t,why:'already scheduled'}); continue; }
    let guard=0, got=0;
    while(need>0 && guard++<12){
      const len=Math.min(need,120);
      const sl=findSlots({len:Math.max(15,Math.round(len/15)*15), calendarId:t.calendarId, dueDate:opts.ignoreDue?null:t.dueDate, dueTime:t.dueTime, exclude:{blocks:[...extra]}, n:1, onlyDay:opts.onlyDay})[0];
      if(!sl) break;
      const b={id:uid('b'),...slotToBlock(sl),done:false};
      extra.push(b); plan.push({t,b,s:sl.s,e:sl.e}); need-=len; got+=len;
    }
    if(need>0) skipped.push({t,why:got?`${fmtDur(need)} could not be placed`:(opts.onlyDay?'no free time today':'no free time before the due date')});
  }
  return {plan, skipped};
}
function openAutoSchedule(ts, opts={}){
  if(!ts || !ts.length){ ts=data.tasks.filter(t=>isOpen(t) && passFilter(t) && SMART.unscheduled.f(t)); }
  if(!ts.length) return toast('No unscheduled open tasks.');
  const {plan, skipped}=planTasks(ts, opts);
  const d=openDialog(`<div class="dhd"><h2>⚡ Auto-schedule${opts.onlyDay?' today':''}</h2><button class="ghost" data-close>✕</button></div>
    <p class="hint">Free slots are filled in order of due date, then priority, inside your work hours (Personal tasks: outside work hours), skipping events, flights, leave/sick days and quiet hours. Nothing changes until you accept.</p>
    ${plan.length?`<table class="rt"><thead><tr><th>Task</th><th>When</th><th>Length</th></tr></thead><tbody>${plan.map(p=>`<tr><td><i class="dotc" style="display:inline-block;background:${esc(itemColor(p.t))}"></i> ${esc(p.t.title)}</td><td class="num">${esc(fmtShort(new Date(p.s)))} ${hm(p.s)}–${hm(p.e)}</td><td class="num">${fmtDur((p.e-p.s)/60000)}</td></tr>`).join('')}</tbody></table>`:'<div class="rempty">No free slots found.</div>'}
    ${skipped.length?`<div class="rsec">Not placed</div>${skipped.map(x=>`<div class="hint">• ${esc(x.t.title)} — ${esc(x.why)}</div>`).join('')}`:''}
    <div class="dacts"><span class="sp"></span><button data-close>Discard</button>${plan.length&&editable?'<button class="primary" id="asOk">Accept</button>':''}</div>`,{size:'mid'});
  const ok=$('#asOk',d); if(ok) ok.onclick=()=>{ for(const p of plan){ p.t.blocks.push(p.b); touch(p.t); } changed(); closeDialog(d); selTasks.clear(); renderAll(); toast(`Scheduled ${plan.length} block${plan.length>1?'s':''}`,{actions:[['Undo',undo]]}); };
}

/* ---------- plan my day ---------- */
function openPlanDay(){
  const draw=()=>{
    const tk=todayKey();
    const cand=data.tasks.filter(t=>isOpen(t) && passFilter(t) && (isOverdue(t) || t.dueDate===tk || (t.priority<=2 && !t.blocks.some(b=>!b.done && zonedToMs(b.end,b.tz)>NOW()))))
      .sort((a,b)=>(a.dueDate||'9999').localeCompare(b.dueDate||'9999')||(a.priority||3)-(b.priority||3));
    const free=findSlots({len:30,calendarId:'work',onlyDay:tk,n:12});
    const d0=fromKey(tk).getTime(), occ=occurrences(d0,d0+864e5,{all:true}).filter(o=>!o.allDay&&!o.cancelled&&o.kind!=='busy');
    dd.querySelector('.box').innerHTML=`<div class="dhd"><h2>🗓 Plan my day · ${esc(fmtShort(new Date(NOW())))}</h2><button class="ghost" data-close>✕</button></div>
      <div class="rep" style="grid-template-columns:1fr 1fr">
        <div><div class="rsec" style="margin-top:0">Tasks to place (${cand.length})</div>
          ${cand.map(t=>`<div style="display:flex;gap:6px;align-items:flex-start;margin-bottom:5px"><div style="flex:1">${taskRowHTML(t)}</div>${editable?`<button data-pslot="${t.id}" title="Put it in the next free slot today">→ today</button>`:''}</div>`).join('')||'<div class="hint">Nothing urgent. 🎉</div>'}
        </div>
        <div><div class="rsec" style="margin-top:0">Today</div>
          ${occ.map(o=>`<div class="hint">${hm(o.s)}–${hm(o.e)} · ${esc(o.title)}${o.kind==='block'?' (task)':''}</div>`).join('')||'<div class="hint">No events or blocks yet.</div>'}
          <div class="rsec">Free work time left: ${fmtH(freeWorkMinutes(tk))}</div>
          ${free.slice(0,6).map(s=>`<div class="hint">${hm(s.s)} onwards</div>`).join('')}
        </div></div>
      <div class="dacts"><span class="sp"></span><button data-close>Close</button>${editable&&cand.length?'<button class="primary" id="pdAuto">⚡ Auto-fill today (preview)</button>':''}</div>`;
    const B=dd.querySelector('.box');
    $$('[data-close]',B).forEach(b=>b.onclick=()=>closeDialog(dd));
    bindTaskRows(B);
    $$('[data-pslot]',B).forEach(b=>b.onclick=()=>{ const t=task(b.dataset.pslot); const len=Math.min(120,Math.max(15,(t.estimateMin?remainingMin(t)-futurePlannedMin(t):0)||(S().defaultBlockMin||60)));
      const sl=findSlots({len, calendarId:t.calendarId, onlyDay:tk, n:1})[0]; if(!sl) return toast('No free slot left today.'); t.blocks.push({id:uid('b'),...slotToBlock(sl),done:false}); touch(t); changed(); renderAll(); toast(`“${t.title}” at ${hm(sl.s)}`,{actions:[['Undo',undo]]}); });
    const au=$('#pdAuto',B); if(au) au.onclick=()=>openAutoSchedule(cand,{onlyDay:tk, ignoreDue:true});
  };
  const dd=openDialog('',{size:'wide',refresh:()=>draw()}); draw();
}

/* ---------- timer on a block ---------- */
function timerKey(){ return 'wtc-timer:'+(session?session.user.toLowerCase():'-'); }
function getTimer(){ return jget(timerKey(),null); }
function startTimer(taskId, blockId){
  const cur=getTimer(); if(cur && !confirm('Another timer is running. Stop it and start this one?')) return;
  if(cur) stopTimer(true);
  jset(timerKey(),{taskId, blockId, start:Date.now()}); renderTimerSlot(); toast('Timer started');
}
async function stopTimer(silent){
  const tm=getTimer(); if(!tm) return; safeDel(timerKey()); renderTimerSlot();
  const mins=Math.max(1,Math.round((Date.now()-tm.start)/60000));
  const t=task(tm.taskId); if(!t || silent) return;
  const b=t.blocks.find(x=>x.id===tm.blockId);
  const m=await askTimeSpent(mins, t.title); if(m===null) return;
  if(b){ b.done=true; b.actualMin=m; } if(t.status==='todo') t.status='in-progress';
  touch(t); changed(); renderAll(); budgetCheck(proj(t.projectId)); toast(`Logged ${fmtDur(m)} on “${t.title}”`,{actions:[['Undo',undo]]});
}
let _timerTick=null;
function renderTimerSlot(){
  const el=$('#timerSlot'); if(!el) return; const tm=getTimer();
  clearInterval(_timerTick);
  if(!tm){ el.innerHTML=''; return; }
  const t=task(tm.taskId);
  const upd=()=>{ const s=Math.floor((Date.now()-tm.start)/1000); el.innerHTML=`<button class="timerbadge" title="Timer for “${esc(t?t.title:'')}” — click to stop and log the time">⏱ ${Math.floor(s/3600)?Math.floor(s/3600)+':':''}${String(Math.floor(s/60)%60).padStart(2,'0')}:${String(s%60).padStart(2,'0')}</button>`; $('button',el).onclick=()=>stopTimer(); };
  upd(); _timerTick=setInterval(upd,1000);
}

/* ---------- focus mode ---------- */
let _focusTick=null;
function startFocus(taskId, blockId){
  const t=task(taskId); if(!t) return;
  const b=t.blocks.find(x=>x.id===blockId);
  if(!getTimer()) jset(timerKey(),{taskId, blockId, start:Date.now()});
  const F=$('#focus'); F.classList.add('open');
  const endMs=b?zonedToMs(b.end,b.tz):null;
  const next=occurrences(NOW(),NOW()+864e5,{all:true}).find(o=>o.s>NOW() && !o.allDay && o.id!==taskId && !o.cancelled && o.kind!=='busy');
  const draw=()=>{
    const tm=getTimer(); const el=Math.floor((Date.now()-(tm?tm.start:Date.now()))/1000);
    const left=endMs?Math.max(0,Math.floor((endMs-NOW())/1000)):null;
    F.innerHTML=`<div class="hint">🎯 Focus${proj(t.projectId)?' · '+esc(proj(t.projectId).name):''}</div>
      <div class="ft">${esc(t.title)}</div>
      <div class="clock" aria-live="off">${String(Math.floor(el/60)).padStart(2,'0')}:${String(el%60).padStart(2,'0')}</div>
      ${left!==null?`<div class="hint">${left>0?`${Math.floor(left/60)} min left in this block (ends ${hm(endMs)})`:'This block has ended'}</div>`:''}
      ${t.checklist.length?`<div style="text-align:left;max-width:420px">${t.checklist.map((c,i)=>`<label style="display:block;margin:3px 0"><input type="checkbox" data-fc="${i}" ${c.done?'checked':''} ${editable?'':'disabled'}> ${esc(c.text)}</label>`).join('')}</div>`:''}
      ${next?`<div class="next">Next: ${esc(next.title)} at ${hm(next.s)}</div>`:''}
      <div class="dacts" style="justify-content:center"><button class="primary" id="fStop">■ Stop and log time</button><button id="fExit">Leave focus (timer keeps running)</button></div>`;
    $$('[data-fc]',F).forEach(c=>c.onchange=()=>{ t.checklist[+c.dataset.fc].done=c.checked; touch(t); changed(); });
    $('#fStop').onclick=()=>{ endFocus(); stopTimer(); };
    $('#fExit').onclick=()=>{ endFocus(); renderTimerSlot(); };
  };
  draw(); clearInterval(_focusTick); _focusTick=setInterval(()=>{ const c=$('#focus .clock'); if(!c) return; const tm=getTimer(); const el=Math.floor((Date.now()-(tm?tm.start:Date.now()))/1000); c.textContent=String(Math.floor(el/60)).padStart(2,'0')+':'+String(el%60).padStart(2,'0'); },1000);
  renderTimerSlot();
}
function endFocus(){ clearInterval(_focusTick); $('#focus').classList.remove('open'); $('#focus').innerHTML=''; }

/* ============================================================
   Reminders & notifications
   Fire while the app is open in any tab (one tab fires, the others
   follow). Reminders missed while closed appear on the next open.
   State (fired, snoozed, notification list) is kept per browser.
   ============================================================ */
const TAB_ID = uid('tab');
const remK = s => `wtc-${s}:${session?session.user.toLowerCase():'-'}`;
let _bc=null; try{ _bc=new BroadcastChannel('wtc'); _bc.onmessage=e=>{ const m=e.data;
  if(m==='notif') renderBell(); if(m==='dismiss') syncToasts();
  if(m && m.type==='saved' && session) reloadData(false);                 // another tab saved: pick it up now
  if(m && m.type==='goto' && !DETAIL && session) gotoDay(m.k);             // a details tab asked to show a day
}; }catch(e){}
function isLeader(){
  const l=jget('wtc-leader',null), now=Date.now();
  // details tabs only take over if no calendar tab is running
  if(!l || l.id===TAB_ID || now-l.ts>(DETAIL?70000:25000)){ jset('wtc-leader',{id:TAB_ID,ts:now}); return jget('wtc-leader',{}).id===TAB_ID; }
  return false;
}
addEventListener('beforeunload',()=>{ const l=jget('wtc-leader',null); if(l && l.id===TAB_ID) safeDel('wtc-leader'); });

/* every reminder that should fire in [from, to) */
function anchorTime(k){ return zonedToMs(k+'T'+(S().allDayRemTime||'09:00'), ACTIVE_TZ); }
function dueReminders(from, to){ // from/to: real time; returns display times
  from=toDisp(from); to=toDisp(to);
  const out=[], look=Math.max(10080, ...data.events.flatMap(e=>e.reminders.map(r=>+r.offsetMin||0)), ...data.tasks.flatMap(t=>t.reminders.map(r=>+r.offsetMin||0)))*60000;
  const occs=occurrences(from-864e5, to+look+864e5, {all:true,noCache:true});
  for(const o of occs){
    if(o.kind==='event'){
      if(o.cancelled) continue;
      const at = o.allDay ? anchorTime(o.dateKey) : o.s;
      for(const r of o.item.reminders){ const f=at-(+r.offsetMin||0)*60000; if(f>=from && f<to) out.push({key:`${o.id}|${o.occ}|start|${r.offsetMin}`, fireAt:f, at, kind:'event', o, r}); }
    } else if(o.kind==='block'){
      if(o.done || !isOpen(o.item)) continue;
      for(const r of o.item.reminders.filter(r=>r.anchor==='block')){ const f=o.s-(+r.offsetMin||0)*60000; if(f>=from && f<to) out.push({key:`${o.id}|${o.occ}|block|${r.offsetMin}`, fireAt:f, at:o.s, kind:'block', o, r}); }
    }
  }
  for(const t of data.tasks){
    if(!isOpen(t) || !t.dueDate) continue;
    const at=t.dueTime?dueMs(t):anchorTime(t.dueDate);
    for(const r of t.reminders.filter(r=>r.anchor==='due')){ const f=at-(+r.offsetMin||0)*60000; if(f>=from && f<to) out.push({key:`${t.id}|${t.dueDate}|due|${r.offsetMin}`, fireAt:f, at, kind:'due', t, r}); }
  }
  for(const p of data.projects){
    if(!p.dueDate || p.status!=='active') continue;
    const at=anchorTime(p.dueDate);
    for(const r of arr(p.reminders)){ const f=at-(+r.offsetMin||0)*60000; if(f>=from && f<to) out.push({key:`${p.id}|${p.dueDate}|pdue|${r.offsetMin}`, fireAt:f, at, kind:'pdue', p, r}); }
  }
  // flight alarms: Morning 06:00, Noon 14:00, Night 17:30 (local time of departure), editable per flight
  if(S().travelReminders){
    for(let d=addDays(startOfDay(new Date(from)),-1); d.getTime()<to+864e5; d=addDays(d,1)){
      const k=key(d); flightWindows(k).forEach((w,i)=>{
        const am=flightAlarmMin(w.f); if(am===null) return;
        const f=zonedToMs(k+'T'+minToHM(am), w.tz); if(f<from || f>=to) return;
        const evs=occurrences(w.s-12*3600000, w.s+18*3600000,{all:true,noCache:true}).filter(o=>o.kind==='event' && !o.allDay && !o.cancelled);
        out.push({key:`flight|${k}|${i}|${w.f.from}|${w.f.to}|${am}`, fireAt:f, at:w.s, kind:'flight', w, evs, k, r:{channel:'sound'}});
      });
    }
  }
  return out.sort((a,b)=>a.fireAt-b.fireAt);
}
function remTitle(x){
  if(x.kind==='event') return x.o.title;
  if(x.kind==='block') return '☑ '+x.o.title;
  if(x.kind==='due') return '⚑ Due: '+x.t.title;
  if(x.kind==='pdue') return '📁 Project due: '+x.p.name;
  return `✈ Flight ${code(x.w.f.from)} → ${code(x.w.f.to)}`;
}
function remWhen(x){
  const at=x.at, rel=Math.round((at-NOW())/60000);
  const relTxt = rel>1 ? `in ${fmtDur(rel)}` : rel>=-1 ? 'now' : `${fmtDur(-rel)} ago`;
  if(x.kind==='flight') return `departs ${hm(x.w.s)} (${relTxt})${x.w.f.note?' · '+x.w.f.note:''}${x.evs.length?' · around it: '+x.evs.map(o=>hm(o.s)+' '+o.title).join(', '):''}`;
  if(x.kind==='due'||x.kind==='pdue') return `${fmtShort(new Date(at))}${x.t&&x.t.dueTime?' '+x.t.dueTime:''} (${relTxt})`;
  return `${fmtShort(new Date(at))} ${hm(at)}${x.o.e?'–'+hm(x.o.e):''} (${relTxt})`;
}
function remItemId(x){ return x.o?x.o.id:x.t?x.t.id:x.p?x.p.id:x.kind==='flight'?'day:'+x.k:null; }
function muted(x){ // work items on leave/sick days are muted (listed in the next daily agenda)
  const it=x.o?x.o.item:x.t; if(!it || it.calendarId!=='work') return false;
  return !!offAt(x.at);
}
function inQuiet(){ const [qe,qs]=quietWin(); const n=new Date(NOW()), m=n.getHours()*60+n.getMinutes(); return qs>qe ? (m>=qs || m<qe) : (m>=qs && m<qe); }

/* stored notification list (bell) */
function notifs(){ return jget(remK('notifs'),[]); }
function addNotif(x){
  const l=notifs(); l.unshift({key:x.key, title:remTitle(x), when:remWhen(x), at:Date.now(), itemId:remItemId(x), kind:x.kind, occ:x.o?x.o.occ:null, ok:x.o?x.o.kind:null, read:false});
  jset(remK('notifs'), l.slice(0,50)); renderBell(); try{ _bc&&_bc.postMessage('notif'); }catch(e){}
}
function renderBell(){ const n=notifs().filter(x=>!x.read).length; const c=$('#bellCnt'); if(!c) return; c.hidden=!n; c.textContent=n>9?'9+':n; }
function openBell(){
  const l=notifs();
  const d=openDialog(`<div class="dhd"><h2>🔔 Notifications</h2>${l.length?'<button data-all>Mark all read</button>':''}<button class="ghost" data-close>✕</button></div>
    ${!('Notification' in window)?'':Notification.permission!=='granted'?`<div class="warnbox" style="margin-bottom:10px">Desktop notifications are off. <button data-perm>Turn on desktop notifications</button></div>`:''}
    ${l.map((n,i)=>`<div class="notif ${n.read?'':'unread'}" data-n="${i}"><div style="flex:1"><b>${esc(n.title)}</b><div class="w">${esc(n.when)}</div></div><div class="w">${esc(fmtWhen(new Date(n.at).toISOString()))}</div></div>`).join('')||'<div class="rempty">No notifications yet.</div>'}
    <div class="dacts"><button data-set>⚙ Notification settings</button><span class="sp"></span><button data-close>Close</button></div>`,{size:'mid'});
  const all=$('[data-all]',d); if(all) all.onclick=()=>{ jset(remK('notifs'),l.map(n=>({...n,read:true}))); renderBell(); closeDialog(d); };
  const pm=$('[data-perm]',d); if(pm) pm.onclick=()=>askDesktopPermission().then(()=>{ closeDialog(d); openBell(); });
  $('[data-set]',d).onclick=()=>{ closeDialog(d); openSettings('notify'); };
  $$('[data-n]',d).forEach(r=>r.onclick=()=>{ const n=l[+r.dataset.n]; n.read=true; jset(remK('notifs'),l); renderBell(); closeDialog(d); openNotifTarget(n); });
}
function openNotifTarget(n){
  if(!n.itemId) return;
  if(String(n.itemId).startsWith('day:')){ const k=n.itemId.slice(4); cursor=fromKey(k); render(); return openDrawer(k); }
  const it=findItem(n.itemId);
  if(it && it.type==='event') return openEvent(it.id,n.occ);
  if(it && it.type==='task') return openTaskEditor(it.id);
  if(proj(n.itemId)) return openProjectPage(n.itemId);
}
async function askDesktopPermission(){
  if(!('Notification' in window)){ alert('This browser does not support desktop notifications.'); return false; }
  const p=await Notification.requestPermission(); prefs.desktop=p==='granted'; savePrefs();
  if(p!=='granted') alert('Desktop notifications were not allowed. You can allow them in your browser’s site settings.');
  return p==='granted';
}
let _audio=null;
function chime(){
  try{ _audio=_audio||new (window.AudioContext||window.webkitAudioContext)(); const t=_audio.currentTime;
    [880,1320].forEach((f,i)=>{ const o=_audio.createOscillator(), g=_audio.createGain(); o.frequency.value=f; o.type='sine'; g.gain.setValueAtTime(0.0001,t+i*.18); g.gain.exponentialRampToValueAtTime(0.18,t+i*.18+.02); g.gain.exponentialRampToValueAtTime(0.0001,t+i*.18+.35); o.connect(g).connect(_audio.destination); o.start(t+i*.18); o.stop(t+i*.18+.4); });
  }catch(e){}
}

/* ---------- reminder popups (grouped) ---------- */
let _remQueue=[];
function showReminders(list){
  _remQueue.push(...list);
  const quiet=inQuiet();
  const wantDesk=list.some(x=>x.r.channel==='desktop'||x.r.channel==='sound') || prefs.desktop;
  const wantSound=list.some(x=>x.r.channel==='sound') || prefs.sound;
  if(!quiet && wantDesk && 'Notification' in window && Notification.permission==='granted'){
    try{
      const x=list[0];
      const n=new Notification(list.length>1?`${list.length} reminders`:remTitle(x), {body:list.length>1?list.map(remTitle).join('\n'):remWhen(x), tag:x.key, silent:!wantSound});
      n.onclick=()=>{ window.focus(); n.close(); if(list.length===1) openNotifTarget({itemId:remItemId(x), occ:x.o?x.o.occ:null}); };
    }catch(e){}
  }
  if(!quiet && wantSound) chime();
  drawRemToast();
}
function drawRemToast(){
  let t=$('#remToast');
  if(!_remQueue.length){ if(t) t.remove(); return; }
  if(!t){ t=document.createElement('div'); t.className='toast'; t.id='remToast'; t.setAttribute('role','alert'); $('#toasts').prepend(t); }
  const many=_remQueue.length>1;
  const row=(x,i)=>{ const it=x.o?x.o.item:x.t; const link=it&&it.onlineLink;
    return `<div class="${i?'sub':''}" style="--c:${esc(it?itemColor(it):'var(--accent)')}"><div class="th"><b>🔔 ${esc(remTitle(x))}</b></div><div class="ts">${esc(remWhen(x))}${x.o&&x.o.location?' · 📍 '+esc(x.o.location):''}</div>
      <div class="ta"><button data-ro="${i}">Open</button><select data-rs="${i}" aria-label="Snooze"><option value="">Snooze…</option>${prefs.snooze.map(s=>`<option value="${s}">${s==='tomorrow'?'Tomorrow 09:00':fmtDur(s)}</option>`).join('')}</select>
      ${(x.kind==='block'||x.kind==='due')&&editable?`<button data-rd="${i}">✓ Done</button>`:''}${link?`<button data-rj="${i}">Join</button>`:''}<button data-rx="${i}">Dismiss</button></div></div>`; };
  t.style.setProperty('--c', 'var(--today)');
  t.innerHTML = many ? `<div class="th"><b>${_remQueue.length} reminders</b><button class="ghost" data-rall>Dismiss all</button></div>`+_remQueue.map(row).join('') : row(_remQueue[0],0);
  const take=i=>{ const x=_remQueue[i]; _remQueue.splice(i,1); drawRemToast(); markRead(x.key); return x; };
  $$('[data-ro]',t).forEach(b=>b.onclick=()=>{ const x=take(+b.dataset.ro); openNotifTarget({itemId:remItemId(x), occ:x.o?x.o.occ:null}); });
  $$('[data-rx]',t).forEach(b=>b.onclick=()=>take(+b.dataset.rx));
  $$('[data-rj]',t).forEach(b=>b.onclick=()=>{ const x=take(+b.dataset.rj); window.open((x.o?x.o.item:x.t).onlineLink,'_blank','noopener'); });
  $$('[data-rd]',t).forEach(b=>b.onclick=()=>{ const x=take(+b.dataset.rd); if(x.kind==='block') toggleBlockDone(x.o.id,x.o.occ); else setTaskStatus(x.t,'done'); });
  $$('[data-rs]',t).forEach(s=>s.onchange=()=>{ if(!s.value) return; const x=take(+s.dataset.rs); snooze(x, s.value); });
  const all=$('[data-rall]',t); if(all) all.onclick=()=>{ _remQueue.forEach(x=>markRead(x.key)); _remQueue=[]; drawRemToast(); };
}
function syncToasts(){ /* other tab dismissed: nothing to do; each tab only shows what it fired */ }
function markRead(k){ const l=notifs(); l.forEach(n=>{ if(n.key===k) n.read=true; }); jset(remK('notifs'),l); renderBell(); }
function snooze(x, v){
  let at; if(v==='tomorrow'){ const d=addDays(TODAY(),1); d.setHours(9); at=fromDisp(d.getTime()); } else at=Date.now()+(+v)*60000;
  const s=jget(remK('snoozed'),[]); s.push({key:x.key, at, title:remTitle(x), when:remWhen(x), itemId:remItemId(x), occ:x.o?x.o.occ:null, kind:x.kind}); jset(remK('snoozed'),s);
  toast(`Snoozed until ${v==='tomorrow'?'tomorrow 09:00':hm(at)}`);
}

/* ---------- scheduler ---------- */
let _remTimer=null;
function startReminders(){
  clearInterval(_remTimer);
  const last=+safeGet(remK('lastcheck'))||0, now=Date.now();
  // catch-up: reminders that fell due while no tab was open (up to 7 days back)
  if(last && now-last>90000){
    const missed=dueReminders(Math.max(last, now-7*864e5), now-30000).filter(x=>!fired().has(x.key) && !muted(x));
    if(missed.length) setTimeout(()=>showAway(missed),800);
    missed.forEach(x=>markFired(x.key));
  }
  if(!last || now-last>90000) safeSet(remK('lastcheck'), now-30000);
  tickReminders(); _remTimer=setInterval(tickReminders,30000);
  setTimeout(dailyPrompts,1500);
}
function fired(){ return new Set(jget(remK('fired'),[]).map(x=>x[0])); }
function markFired(k){ const l=jget(remK('fired'),[]).filter(x=>x[1]>Date.now()-14*864e5); l.push([k,Date.now()]); jset(remK('fired'),l.slice(-3000)); }
function tickReminders(){
  if(!session || !data) return;
  if(!isLeader()) return;
  const now=Date.now(), last=+safeGet(remK('lastcheck'))||now-30000;
  const due=dueReminders(last, now+1000), f=fired(), fire=[], mutedNow=[];
  for(const x of due){ if(f.has(x.key)) continue; markFired(x.key); if(muted(x)){ mutedNow.push(x); continue; } fire.push(x); addNotif(x); }
  if(mutedNow.length){ const m=jget(remK('muted'),[]); m.push(...mutedNow.map(x=>({title:remTitle(x), when:remWhen(x), day:key(new Date(x.at))}))); jset(remK('muted'),m.slice(-50)); }
  // snoozed ones
  const sn=jget(remK('snoozed'),[]), back=sn.filter(s=>s.at<=now), keep=sn.filter(s=>s.at>now);
  if(back.length){ jset(remK('snoozed'),keep); for(const s of back){ const x=rebuildSnoozed(s); if(x){ fire.push(x); addNotif(x); } } }
  safeSet(remK('lastcheck'), now+1000);
  if(fire.length) showReminders(fire);
  dailyPrompts();
}
function rebuildSnoozed(s){
  const it=findItem(s.itemId);
  if(it && it.type==='event'){ const o=findOcc(it.id,s.occ,'event'); if(o) return {key:s.key+'|sn'+Date.now(), at:o.s, kind:'event', o, r:{channel:'popup'}}; }
  if(it && it.type==='task'){ if(s.kind==='block'){ const o=findOcc(it.id,s.occ,'block'); if(o) return {key:s.key+'|sn'+Date.now(), at:o.s, kind:'block', o, r:{channel:'popup'}}; } return {key:s.key+'|sn'+Date.now(), at:dueMs(it)||Date.now(), kind:'due', t:it, r:{channel:'popup'}}; }
  return null;
}
function showAway(list){
  const d=openDialog(`<div class="dhd"><h2>While you were away</h2><button class="ghost" data-close>✕</button></div>
    <p class="hint">${list.length} reminder${list.length>1?'s':''} fell due while the calendar was closed.</p>
    ${list.map((x,i)=>`<div class="notif" data-w="${i}"><div style="flex:1"><b>${esc(remTitle(x))}</b><div class="w">${esc(remWhen(x))}</div></div><div class="w">due ${esc(fmtShort(new Date(x.fireAt)))} ${hm(x.fireAt)}</div></div>`).join('')}
    <div class="dacts"><span class="sp"></span><button class="primary" data-close>OK</button></div>`,{size:'mid'});
  list.forEach(x=>{ const l=notifs(); l.unshift({key:x.key,title:remTitle(x),when:remWhen(x),at:x.fireAt,itemId:remItemId(x),kind:x.kind,occ:x.o?x.o.occ:null,read:true}); jset(remK('notifs'),l.slice(0,50)); });
  renderBell();
  $$('[data-w]',d).forEach(r=>r.onclick=()=>{ const x=list[+r.dataset.w]; closeDialog(d); openNotifTarget({itemId:remItemId(x),occ:x.o?x.o.occ:null}); });
}
/* daily agenda (first open after the digest time), weekly review and end-of-day prompts */
function dailyPrompts(){
  if(!session || !data || DETAIL || !isLeader()) return;
  const n=new Date(NOW()), m=n.getHours()*60+n.getMinutes(), tk=todayKey();
  if(prefs.digest && m>=(hmToMin(prefs.digestTime)??510) && safeGet(remK('digest'))!==tk && !_dlgStack.length){
    safeSet(remK('digest'),tk); openReports('daily',{date:tk, digest:true});
  }
  if(prefs.review && dow(n)===+prefs.reviewDay && m>=(hmToMin(prefs.reviewTime)??960) && safeGet(remK('review'))!==tk){
    safeSet(remK('review'),tk); toast('🗓 Time for your weekly review.',{actions:[['Open weekly review',()=>openReports('weekly')]],sticky:true});
  }
  if(prefs.eod && m>=(hmToMin(prefs.eodTime)??1080) && safeGet(remK('eod'))!==tk){
    safeSet(remK('eod'),tk); toast('🌇 End of the day — review what got done.',{actions:[['Open summary',()=>openReports('eod')]],sticky:true});
  }
}

/* ============================================================
   Reports: daily agenda, end of day, weekly review, project,
   time allocation, trips. Print, CSV, copy, email, .ics.
   ============================================================ */
const REPORTS = {daily:'Daily agenda', eod:'End-of-day summary', weekly:'Weekly review', project:'Project report', time:'Time allocation', trips:'✈ Trips'};
let rep = {kind:'daily', date:null, from:null, to:null, week:null, projectId:'', cal:'', tag:''};
let repOut = null;

/* ---------- charts (inline SVG, no library) ---------- */
function barChart(items, unit, opts={}){
  items=items.filter(x=>x.v>0||opts.keepZero); if(!items.length) return '<div class="hint">No data.</div>';
  const W=640, lh=24, lw=Math.min(220,Math.max(90,...items.map(x=>x.label.length*6.6))), H=items.length*lh+8, max=Math.max(...items.map(x=>x.v),0.01);
  return `<svg class="chart" viewBox="0 0 ${W} ${H}" width="100%" role="img" aria-label="${esc(opts.title||'Bar chart')}" style="max-width:${W}px">${items.map((x,i)=>{ const y=i*lh+4, w=(W-lw-70)*x.v/max;
    return `<text x="${lw-6}" y="${y+15}" text-anchor="end">${esc(x.label.length>34?x.label.slice(0,33)+'…':x.label)}</text><rect x="${lw}" y="${y+3}" width="${Math.max(1,w)}" height="${lh-8}" rx="3" fill="${esc(x.c||'var(--accent)')}"/><text x="${lw+w+6}" y="${y+15}" class="q">${unit==='h'?(Math.round(x.v*10)/10)+' h':x.v}</text>`; }).join('')}</svg>`;
}
function lineChart(points, opts={}){
  const W=640, H=170, p=30, max=Math.max(1,...points.map(x=>x.v)), step=(W-2*p)/Math.max(1,points.length-1);
  const xy=points.map((x,i)=>[p+i*step, H-p-(H-2*p)*x.v/max]);
  return `<svg class="chart" viewBox="0 0 ${W} ${H}" width="100%" role="img" aria-label="${esc(opts.title||'Line chart')}" style="max-width:${W}px">
    <line class="ax" x1="${p}" y1="${H-p}" x2="${W-p}" y2="${H-p}"/>${[0,.5,1].map(f=>`<text x="${p-6}" y="${H-p-(H-2*p)*f+4}" text-anchor="end" class="q">${Math.round(max*f)}</text>`).join('')}
    <polyline points="${xy.map(a=>a.join(',')).join(' ')}" fill="none" stroke="var(--accent)" stroke-width="2.5"/>
    ${xy.map((a,i)=>`<circle cx="${a[0]}" cy="${a[1]}" r="4" fill="var(--accent)"/><text x="${a[0]}" y="${a[1]-9}" text-anchor="middle">${points[i].v}</text><text x="${a[0]}" y="${H-p+16}" text-anchor="middle" class="q">${esc(points[i].label)}</text>`).join('')}</svg>`;
}

/* ---------- time accounting ---------- */
function repFilter(it){
  if(rep.cal && it.calendarId!==rep.cal) return false;
  if(rep.projectId && it.projectId!==rep.projectId && (proj(it.projectId)||{}).parentId!==rep.projectId) return false;
  if(rep.tag && !(it.tags||[]).some(t=>t.toLowerCase()===rep.tag.toLowerCase())) return false;
  if(!privKey && isPrivate(it)) return false;
  return true;
}
function timeEntries(from, to){
  const out=[];
  const pool=archive?{events:[...data.events,...arr(archive.events)], tasks:[...data.tasks,...arr(archive.tasks)]}:data;
  for(const e of pool.events){ if(!repFilter(e) || e.allDay || e.showAs==='free') continue;
    for(const o of expandEvent(e,from,to)){ if(o.cancelled) continue; const s=Math.max(o.s,from), en=Math.min(o.e,to); out.push({kind:e.category==='meeting'?'meeting':'event', it:e, s, min:(en-s)/60000, spent:(en-s)/60000*(o.e<=NOW()?1:0), k:key(new Date(s))}); } }
  for(const t of pool.tasks){ if(!repFilter(t)) continue;
    for(const b of t.blocks){ const s=zonedToMs(b.start,b.tz), en=zonedToMs(b.end,b.tz); if(en<=from||s>=to) continue; out.push({kind:'task', it:t, s, min:(en-s)/60000, spent:b.done?(b.actualMin??(en-s)/60000):0, done:!!b.done, missed:!b.done&&en<NOW()&&isOpen(t), b, k:key(new Date(s))}); } }
  return out;
}
function sumBy(list, keyFn, val='min'){ const m=new Map(); for(const x of list){ const k=keyFn(x); m.set(k,(m.get(k)||0)+x[val]); } return [...m].sort((a,b)=>b[1]-a[1]); }

/* ---------- report builders: return {title, html, head, rows, text} ---------- */
function repDaily(){
  const k=rep.date||todayKey(), d=fromKey(k), d0=d.getTime(), d1=d0+864e5, e=day(k);
  const occ=occurrences(d0,d1,{all:true}).filter(o=>o.kind==='busy'||repFilter(o.item));
  const evs=occ.filter(o=>o.kind==='event'&&!o.cancelled), bl=occ.filter(o=>o.kind==='block');
  const due=data.tasks.filter(t=>repFilter(t)&&isOpen(t)&&t.dueDate===k), od=data.tasks.filter(t=>repFilter(t)&&isOverdue(t)&&t.dueDate<k);
  const conf=conflictsIn(occ); const free=k===todayKey()?freeWorkMinutes(k):null;
  const muted=jget(remK('muted'),[]).filter(m=>m.day===keyAdd(k,-1)||m.day===k);
  const st=e.am||e.pm?(e.am===e.pm?STATUSES[e.am].label:`AM ${e.am?STATUSES[e.am].label:'—'} / PM ${e.pm?STATUSES[e.pm].label:'—'}`):'—';
  const loc=locationOf(k);
  const rows=[...evs.map(o=>[o.allDay?'All day':hm(o.s)+'–'+hm(o.e),'Event',o.title,(proj(o.item.projectId)||{}).name||'',o.location||'']),...bl.map(o=>[hm(o.s)+'–'+hm(o.e),'Task block',o.title,(proj(o.item.projectId)||{}).name||'',o.done?'done':o.missed?'missed':'']),...due.map(t=>[t.dueTime||'','Due',t.title,(proj(t.projectId)||{}).name||'','P'+t.priority]),...od.map(t=>['','Overdue',t.title,(proj(t.projectId)||{}).name||'','due '+fmtDMY(fromKey(t.dueDate))])];
  const html=`<div class="kpis"><div class="kpi"><div class="v" style="font-size:15px">${esc(st)}</div><div class="l">work status</div></div><div class="kpi"><div class="v" style="font-size:15px">${esc(loc?loc.name:'—')}</div><div class="l">location</div></div>
    <div class="kpi"><div class="v">${evs.length}</div><div class="l">events</div></div><div class="kpi"><div class="v">${fmtH(bl.reduce((a,o)=>a+(o.e-o.s)/60000,0))}</div><div class="l">task blocks</div></div>
    <div class="kpi"><div class="v">${due.length}</div><div class="l">due today</div></div><div class="kpi"><div class="v" style="${od.length?'color:var(--sick)':''}">${od.length}</div><div class="l">overdue</div></div>${free!==null?`<div class="kpi"><div class="v">${fmtH(free)}</div><div class="l">free work time left</div></div>`:''}</div>
    ${(e.flights||[]).length?`<div class="rsec">Flights</div>${e.flights.map(f=>`<div>✈ ${esc(f.from)} → ${esc(f.to)} · ${esc(flightLabel(f))}${f.note?' · '+esc(f.note):''}</div>`).join('')}`:''}
    <div class="rsec">Schedule</div>${[...evs,...bl].sort((a,b)=>a.s-b.s).map(o=>agOccRow(o,o.allDay?'All day':hm(o.s)+'–'+hm(o.e))).join('')||'<div class="hint">Nothing scheduled.</div>'}
    <div class="rsec">Tasks due</div>${due.map(t=>taskRowHTML(t)).join('')||'<div class="hint">None.</div>'}
    ${od.length?`<div class="rsec" style="color:var(--sick)">Overdue</div>${od.map(t=>taskRowHTML(t)).join('')}`:''}
    ${conf.size?`<div class="rsec">Conflicts</div><div class="warnbox">${[...conf].map(([kk,w])=>{ const o=occ.find(x=>x.id+'|'+x.occ===kk); return `⚠ ${esc(o?o.title:'')}: ${w.map(esc).join('; ')}`; }).join('<br>')}</div>`:''}
    ${muted.length?`<div class="rsec">Muted (leave/sick day)</div>${muted.map(m=>`<div class="hint">🔕 ${esc(m.title)} — ${esc(m.when)}</div>`).join('')}`:''}
    ${e.comment?`<div class="rsec">Comment</div><div>${esc(e.comment)}</div>`:''}`;
  const text=`Daily agenda ${fmtLong(d)}\nStatus: ${st} · Location: ${loc?loc.name:'—'}\n`+(e.flights||[]).map(f=>`Flight ${f.from} → ${f.to} (${flightLabel(f)})`).join('\n')+'\n'+rows.map(r=>`${r[0]}  ${r[1]}: ${r[2]}${r[3]?' ['+r[3]+']':''}${r[4]?' · '+r[4]:''}`).join('\n');
  return {title:'Daily agenda · '+fmtLong(d), html, head:['Time','Type','Title','Project','Note'], rows, text};
}
function repEod(){
  const k=rep.date||todayKey(), d0=fromKey(k).getTime(), d1=d0+864e5;
  const doneT=data.tasks.filter(t=>repFilter(t)&&t.completedAt&&isoDay(t.completedAt)===k);
  const te=timeEntries(d0,d1); const bl=te.filter(x=>x.kind==='task');
  const missed=bl.filter(x=>x.missed), doneB=bl.filter(x=>x.done);
  const planned=te.reduce((a,x)=>a+x.min,0), actual=te.reduce((a,x)=>a+(x.kind==='task'?x.spent:(x.s+x.min*60000<=NOW()?x.min:0)),0);
  const roll=data.tasks.filter(t=>repFilter(t)&&isOpen(t)&&t.dueDate&&t.dueDate<=k);
  const html=`<div class="kpis"><div class="kpi"><div class="v">${doneT.length}</div><div class="l">tasks completed</div></div><div class="kpi"><div class="v">${doneB.length}</div><div class="l">blocks done</div></div>
    <div class="kpi"><div class="v" style="${missed.length?'color:var(--missed)':''}">${missed.length}</div><div class="l">blocks missed</div></div><div class="kpi"><div class="v">${fmtH(planned)}</div><div class="l">planned</div></div><div class="kpi"><div class="v">${fmtH(actual)}</div><div class="l">actual</div></div></div>
    <div class="rsec">Done today</div>${doneT.map(t=>taskRowHTML(t)).join('')||'<div class="hint">No tasks completed.</div>'}
    <div class="rsec">Missed blocks</div>${missed.map(x=>`<div class="slot"><span>${hm(x.s)} · ${esc(x.it.title)} (${fmtDur(x.min)})</span>${editable?`<button data-rs-tom="${x.it.id}|${x.b.id}">→ tomorrow, same time</button><button data-rs-next="${x.it.id}|${x.b.id}">→ next free slot</button>`:''}</div>`).join('')||'<div class="hint">None. 👍</div>'}
    <div class="rsec">Rolls over (due today or earlier, still open)</div>${roll.map(t=>taskRowHTML(t)).join('')||'<div class="hint">Nothing.</div>'}
    ${roll.length&&editable?'<button data-roll>Move all due dates to tomorrow</button>':''}`;
  const rows=[...doneT.map(t=>['Done',t.title,'']),...missed.map(x=>['Missed block',x.it.title,hm(x.s)]),...roll.map(t=>['Rolls over',t.title,t.dueDate])];
  return {title:'End of day · '+fmtLong(fromKey(k)), html, head:['Type','Title','Note'], rows, text:`End of day ${fmtLong(fromKey(k))}\nCompleted: ${doneT.map(t=>t.title).join(', ')||'—'}\nMissed blocks: ${missed.map(x=>x.it.title).join(', ')||'—'}\nPlanned ${fmtH(planned)}, actual ${fmtH(actual)}\nRolls over: ${roll.map(t=>t.title).join(', ')||'—'}`,
    bind:B=>{
      $$('[data-rs-tom]',B).forEach(b=>b.onclick=()=>{ const [tid,bid]=b.dataset.rsTom.split('|'); const t=task(tid), bl2=t.blocks.find(x=>x.id===bid); bl2.start=localAddMin(bl2.start,1440); bl2.end=localAddMin(bl2.end,1440); touch(t); changed(); renderAll(); });
      $$('[data-rs-next]',B).forEach(b=>b.onclick=()=>{ const [tid,bid]=b.dataset.rsNext.split('|'); const t=task(tid); rescheduleBlock(t,t.blocks.find(x=>x.id===bid)); });
      const r=$('[data-roll]',B); if(r) r.onclick=()=>{ for(const t of roll){ t.dueDate=keyAdd(todayKey(),1); touch(t); } changed(); renderAll(); toast(`${roll.length} tasks moved to tomorrow`,{actions:[['Undo',undo]]}); };
    }};
}
function repWeekly(){
  const mon=rep.week?fromKey(rep.week):mondayOf(TODAY()), w0=mon.getTime(), w1=addDays(mon,7).getTime();
  const te=timeEntries(w0,w1);
  const byCal=sumBy(te,x=>x.it.calendarId), byProj=sumBy(te,x=>x.it.projectId||'inbox:'+x.it.calendarId);
  const wk=[0,1,2,3,4,5,6].map(i=>key(addDays(mon,i)));
  const tasksIn=data.tasks.filter(repFilter);
  const completed=tasksIn.filter(t=>t.completedAt && toDisp(Date.parse(t.completedAt))>=w0 && toDisp(Date.parse(t.completedAt))<w1);
  const created=tasksIn.filter(t=>t.created && toDisp(Date.parse(t.created))>=w0 && toDisp(Date.parse(t.created))<w1);
  const overdue=tasksIn.filter(t=>isOpen(t)&&t.dueDate&&t.dueDate<key(new Date(Math.min(NOW(),w1))));
  const meet=te.filter(x=>x.kind==='meeting'), missed=te.filter(x=>x.missed);
  const perDay=wk.map((k,i)=>({label:DOW3[i], v:completed.filter(t=>isoDay(t.completedAt)===k).length}));
  const n0=w1, n1=w1+7*864e5;
  const nextEv=occurrences(n0,n1,{all:true}).filter(o=>o.kind==='event'&&!o.cancelled&&repFilter(o.item));
  const nextDue=tasksIn.filter(t=>isOpen(t)&&t.dueDate&&t.dueDate>=key(new Date(n0))&&t.dueDate<key(new Date(n1)));
  const needSlots=nextDue.filter(t=>!t.blocks.some(b=>!b.done)||underScheduled(t));
  const pname=id=>id.startsWith('inbox:')?cal(id.slice(6)).name+' · Inbox':(proj(id)?proj(id).name:'?');
  const pcolor=id=>id.startsWith('inbox:')?'var(--muted)':(proj(id)||{}).color;
  const glance=wk.map((k,i)=>{ const e=day(k), l=locationOf(k); const dd=te.filter(x=>x.k===k);
    return `<tr data-k="${k}" style="cursor:pointer"><td>${DOW3[i]} ${fromKey(k).getDate()}</td><td>${i<5?esc(e.am===e.pm?(e.am?STATUSES[e.am].short:'—'):(e.am?STATUSES[e.am].short:'—')+' / '+(e.pm?STATUSES[e.pm].short:'—')):''}</td><td>${esc(l?l.name:'')}</td><td>${(e.flights||[]).map(f=>esc(code(f.from)+'→'+code(f.to))).join(', ')}</td><td class="num">${dd.filter(x=>x.kind!=='task').length}</td><td class="num">${fmtH(dd.filter(x=>x.kind==='task').reduce((a,x)=>a+x.min,0))}</td></tr>`; }).join('');
  const html=`<div class="kpis"><div class="kpi"><div class="v">${completed.length}</div><div class="l">tasks completed</div></div><div class="kpi"><div class="v">${created.length}</div><div class="l">tasks created</div></div>
    <div class="kpi"><div class="v" style="${overdue.length?'color:var(--sick)':''}">${overdue.length}</div><div class="l">overdue now</div></div><div class="kpi"><div class="v">${meet.length}</div><div class="l">meetings · ${fmtH(meet.reduce((a,x)=>a+x.min,0))}</div></div>
    <div class="kpi"><div class="v">${fmtH(te.filter(x=>x.kind==='task').reduce((a,x)=>a+x.spent,0))}</div><div class="l">task time spent</div></div><div class="kpi"><div class="v" style="${missed.length?'color:var(--missed)':''}">${missed.length}</div><div class="l">missed blocks</div></div></div>
    <div class="rsec">Week at a glance</div><table class="rt"><thead><tr><th>Day</th><th>Status</th><th>Location</th><th>Flights</th><th>Events</th><th>Task time</th></tr></thead><tbody>${glance}</tbody></table>
    <div class="rsec">Hours by calendar</div>${barChart(byCal.map(([c,m])=>({label:cal(c).name,v:m/60,c:cal(c).color})),'h',{title:'Hours by calendar'})}
    <div class="rsec">Hours by project</div>${barChart(byProj.map(([p,m])=>({label:pname(p),v:m/60,c:pcolor(p)})),'h',{title:'Hours by project'})}
    <div class="rsec">Tasks completed per day</div>${lineChart(perDay,{title:'Tasks completed per day'})}
    ${missed.length?`<div class="rsec">Missed blocks</div>${missed.map(x=>`<div class="hint">• ${esc(fmtShort(new Date(x.s)))} ${hm(x.s)} ${esc(x.it.title)}</div>`).join('')}`:''}
    <div class="rsec">Next week · ${nextEv.length} events, ${nextDue.length} tasks due</div>
    ${nextEv.slice(0,25).map(o=>agOccRow(o,`${DOW3[dow(new Date(o.s))]} ${o.allDay?'all day':hm(o.s)}`)).join('')}
    ${nextDue.map(t=>agTaskRow(t,DOW3[dowKey(t.dueDate)]+' due')).join('')}
    ${needSlots.length?`<div class="warnbox" style="margin-top:8px">${needSlots.length} task${needSlots.length>1?'s':''} due next week still need time: ${needSlots.map(t=>esc(t.title)).join(', ')} ${editable?'<button data-wsched>⚡ Schedule them</button>':''}</div>`:''}`;
  const rows=[...byProj.map(([p,m])=>['Hours',pname(p),(Math.round(m/6)/10)]),...completed.map(t=>['Completed',t.title,isoDay(t.completedAt)]),...overdue.map(t=>['Overdue',t.title,t.dueDate]),...nextDue.map(t=>['Due next week',t.title,t.dueDate])];
  const text=`Weekly review — week ${isoWeek(mon)} (${fmtDM(mon)}–${fmtDM(addDays(mon,6))})\n\nCompleted (${completed.length}): ${completed.map(t=>t.title).join('; ')||'—'}\nOverdue (${overdue.length}): ${overdue.map(t=>t.title).join('; ')||'—'}\nMeetings: ${meet.length} (${fmtH(meet.reduce((a,x)=>a+x.min,0))})\n\nHours by project:\n${byProj.map(([p,m])=>`  ${pname(p)}: ${fmtH(m)}`).join('\n')}\n\nNext week: ${nextEv.length} events; due: ${nextDue.map(t=>t.title+' ('+DOW3[dowKey(t.dueDate)]+')').join('; ')||'—'}`;
  return {title:`Weekly review · week ${isoWeek(mon)}, ${fmtDM(mon)} – ${fmtDM(addDays(mon,6))} ${mon.getFullYear()}`, html, head:['Section','Item','Value'], rows, text,
    bind:B=>{ const s=$('[data-wsched]',B); if(s) s.onclick=()=>openAutoSchedule(needSlots); $$('tr[data-k]',B).forEach(tr=>tr.onclick=()=>{ closeDialog(); cursor=fromKey(tr.dataset.k); setView('day'); }); }};
}
function repProject(){
  const ps = rep.projectId ? [proj(rep.projectId)].filter(Boolean) : data.projects.filter(p=>p.status!=='archived' && (!rep.cal||p.calendarId===rep.cal) && (privKey||!isPrivCal(p.calendarId)));
  const rows=[]; let html='';
  for(const p of ps){
    const st=projStats(p);
    rows.push([p.name, PROJ_STATUS[p.status], Math.round(st.progress*100)+'%', st.open, st.done, st.overdue, Math.round(st.est/6)/10, Math.round(st.planned/6)/10, Math.round(st.spent/6)/10, p.budgetHours??'', p.dueDate||'']);
    html+=`<div style="border:1px solid var(--line);border-radius:12px;padding:10px 12px;margin-bottom:10px;border-left:5px solid ${esc(p.color)}">
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><b style="font-size:15px">${esc(p.icon||'')} ${esc(p.name)}</b><span class="tag">${PROJ_STATUS[p.status]}</span>${p.dueDate?`<span class="hint">due ${esc(fmtShort(fromKey(p.dueDate)))}</span>`:''}<span class="sp" style="flex:1"></span><button data-popen="${p.id}">Open</button></div>
      <div class="progress" style="margin:6px 0"><i style="width:${Math.round(st.progress*100)}%"></i></div>
      <div class="hint">${Math.round(st.progress*100)}% · ${st.open} open · ${st.done} done · <span style="${st.overdue?'color:var(--sick);font-weight:700':''}">${st.overdue} overdue</span> · ${fmtH(st.planned)} planned · ${fmtH(st.spent)} spent${p.budgetHours?` · budget ${p.budgetHours} h (${Math.round(st.spent/60/p.budgetHours*100)}% used${st.planned/60>p.budgetHours?', <b style="color:var(--sick)">planned exceeds budget</b>':''})`:''}</div>
      ${ps.length===1?`<div class="rsec">Upcoming deadlines</div>${st.ts.filter(t=>isOpen(t)&&t.dueDate).sort((a,b)=>a.dueDate.localeCompare(b.dueDate)).map(t=>taskRowHTML(t)).join('')||'<div class="hint">None.</div>'}
        <div class="rsec">Completed</div>${st.ts.filter(t=>t.status==='done').map(t=>taskRowHTML(t)).join('')||'<div class="hint">None yet.</div>'}`:''}
    </div>`;
  }
  return {title:rep.projectId&&ps[0]?'Project report · '+ps[0].name:'Project report · all projects', html:html||'<div class="rempty">No projects.</div>', head:['Project','Status','Progress','Open','Done','Overdue','Estimated h','Planned h','Spent h','Budget h','Due'], rows,
    text:ps.map(p=>{ const st=projStats(p); return `${p.name}: ${Math.round(st.progress*100)}% · ${st.open} open, ${st.done} done, ${st.overdue} overdue · ${fmtH(st.planned)} planned, ${fmtH(st.spent)} spent${p.budgetHours?' of '+p.budgetHours+' h':''}`; }).join('\n'),
    bind:B=>$$('[data-popen]',B).forEach(b=>b.onclick=()=>openProjectPage(b.dataset.popen))};
}
function repTime(){
  const f=fromKey(rep.from).getTime(), t=addDays(fromKey(rep.to),1).getTime();
  const te=timeEntries(f,t);
  const total=te.reduce((a,x)=>a+x.min,0);
  const byCal=sumBy(te,x=>x.it.calendarId), byProj=sumBy(te,x=>x.it.projectId||'inbox:'+x.it.calendarId);
  const byCat=sumBy(te,x=>x.kind==='task'?'Task work (focus)':(CATEGORIES[x.it.category]||'Other').replace(/^\S+\s/,''));
  const byTag=sumBy(te.flatMap(x=>(x.it.tags.length?x.it.tags:['(no tag)']).map(tg=>({...x,tg}))),x=>x.tg);
  const byLoc=sumBy(te.filter(x=>x.it.calendarId==='work'),x=>{ const l=locationOf(x.k); return l?l.name:'Unknown'; });
  const bySt=sumBy(te.filter(x=>x.it.calendarId==='work'&&!isWeekend(fromKey(x.k))),x=>{ const e=day(x.k), h=new Date(x.s).getHours(); const s=h<13?e.am:e.pm; return s?STATUSES[s].label:'No status'; });
  const meet=te.filter(x=>x.kind==='meeting').reduce((a,x)=>a+x.min,0), focus=te.filter(x=>x.kind==='task').reduce((a,x)=>a+x.min,0);
  const pname=id=>id.startsWith('inbox:')?cal(id.slice(6)).name+' · Inbox':(proj(id)?proj(id).name:'?');
  const html=`<div class="kpis"><div class="kpi"><div class="v">${fmtH(total)}</div><div class="l">total scheduled</div></div><div class="kpi"><div class="v">${fmtH(meet)}</div><div class="l">meetings</div></div>
    <div class="kpi"><div class="v">${fmtH(focus)}</div><div class="l">focus (task blocks)</div></div><div class="kpi"><div class="v">${fmtH(te.filter(x=>x.kind==='task').reduce((a,x)=>a+x.spent,0))}</div><div class="l">task time spent</div></div></div>
    <div class="rsec">By calendar</div>${barChart(byCal.map(([c,m])=>({label:cal(c).name,v:m/60,c:cal(c).color})),'h')}
    <div class="rsec">By project</div>${barChart(byProj.map(([p,m])=>({label:pname(p),v:m/60,c:p.startsWith('inbox:')?'var(--muted)':proj(p)&&proj(p).color})),'h')}
    <div class="rsec">By type</div>${barChart(byCat.map(([c,m])=>({label:c,v:m/60})),'h')}
    <div class="rsec">By tag</div>${barChart(byTag.map(([c,m])=>({label:'#'+c,v:m/60,c:'var(--abroad)'})),'h')}
    <div class="rsec">Work time by location</div>${barChart(byLoc.map(([c,m])=>({label:c,v:m/60,c:'var(--office)'})),'h')}
    <div class="rsec">Work time by work status</div>${barChart(bySt.map(([c,m])=>({label:c,v:m/60,c:'var(--tele)'})),'h')}`;
  const rows=[...byCal.map(([c,m])=>['Calendar',cal(c).name,Math.round(m/6)/10]),...byProj.map(([p,m])=>['Project',pname(p),Math.round(m/6)/10]),...byCat.map(([c,m])=>['Type',c,Math.round(m/6)/10]),...byTag.map(([c,m])=>['Tag',c,Math.round(m/6)/10]),...byLoc.map(([c,m])=>['Location',c,Math.round(m/6)/10]),...bySt.map(([c,m])=>['Work status',c,Math.round(m/6)/10])];
  return {title:`Time allocation · ${fmtDMY(fromKey(rep.from))} – ${fmtDMY(fromKey(rep.to))}`, html, head:['Dimension','Value','Hours'], rows, text:`Time allocation ${fmtDMY(fromKey(rep.from))} – ${fmtDMY(fromKey(rep.to))}\nTotal ${fmtH(total)}, meetings ${fmtH(meet)}, focus ${fmtH(focus)}\n`+byProj.map(([p,m])=>`  ${pname(p)}: ${fmtH(m)}`).join('\n')};
}
/* Trips (kept from v1) + events during each trip and linked project */
function tripsBetween(fromK, toK){
  const rows=[], fk=flightKeys();
  Object.keys(data.days).filter(k=>k>=fromK && k<=toK).sort().forEach(k=>{
    const d=fromKey(k);
    (data.days[k].flights||[]).forEach(f=>{
      const nextK=fk.find(x=>x>k)||keyAdd(k,1);
      const evCount=occurrences(d.getTime(), fromKey(nextK).getTime(),{all:true}).filter(o=>o.kind==='event'&&!o.cancelled&&(privKey||!isPrivate(o.item))).length;
      const pj=data.projects.find(p=>p.tripFrom && p.tripFrom<=k && (p.tripTo||p.tripFrom)>=k);
      rows.push({k, date:fmtDMY(d), day:DAY_NAMES[dow(d)], from:f.from||'', to:f.to||'', period:flightLabel(f), note:f.note||'', events:evCount, project:pj?pj.name:'', pid:pj?pj.id:''});
    });
  });
  return rows;
}
function repTrips(){
  const rows=tripsBetween(rep.from, rep.to); const rangeTxt=fmtDMY(fromKey(rep.from))+' – '+fmtDMY(fromKey(rep.to));
  if(!rows.length) return {title:'Trips '+rangeTxt, html:`<div class="rempty">No trips between ${rangeTxt}.</div>`, head:[], rows:[], text:''};
  const count={}; rows.forEach(r=>{ const kk=r.from+' → '+r.to; count[kk]=(count[kk]||0)+1; });
  const by=Object.entries(count).sort((a,b)=>b[1]-a[1]).map(([kk,n])=>`${esc(kk)}: <b>${n}</b>`).join(' · ');
  const html=`<p class="rsum"><b>${rows.length}</b> trip${rows.length===1?'':'s'} from ${rangeTxt} &nbsp;·&nbsp; ${by}</p>
    <table class="rt"><thead><tr><th>#</th><th>Date</th><th>Day</th><th>From</th><th>To</th><th>Period of day</th><th>Events during trip</th><th>Project</th></tr></thead><tbody>
    ${rows.map((r,i)=>`<tr data-k="${r.k}" style="cursor:pointer" title="Open this day"><td class="num">${i+1}</td><td class="num">${r.date}</td><td>${r.day}</td>
      <td class="route">${esc(r.from)}</td><td class="route">${esc(r.to)}</td><td>${esc(r.period)}${r.note?` <span class="note">· ${esc(r.note)}</span>`:''}</td><td class="num">${r.events}</td><td>${r.pid?`<a href="#" data-popen="${r.pid}">${esc(r.project)}</a>`:''}</td></tr>`).join('')}
    </tbody></table>`;
  return {title:(data.owner?data.owner+' · ':'')+'Trips '+rangeTxt, html, head:['Date','Day','From','To','Period of day','Note','Events during trip','Project'], rows:rows.map(r=>[r.date,r.day,r.from,r.to,r.period,r.note,r.events,r.project]),
    text:`Trips ${rangeTxt}\n`+rows.map(r=>`${r.date} ${r.day}: ${r.from} → ${r.to} (${r.period})${r.note?' '+r.note:''}`).join('\n'),
    bind:B=>{ $$('tr[data-k]',B).forEach(tr=>tr.onclick=e=>{ if(e.target.closest('[data-popen]')) return; closeDialog(); cursor=fromKey(tr.dataset.k); render(); openDrawer(tr.dataset.k); }); $$('[data-popen]',B).forEach(a=>a.onclick=e=>{ e.preventDefault(); openProjectPage(a.dataset.popen); }); }};
}

/* ---------- range presets ---------- */
function setRange(q){
  const t=TODAY(); let a,b;
  if(q==='month'){ a=new Date(t.getFullYear(),t.getMonth(),1); b=new Date(t.getFullYear(),t.getMonth()+1,0); }
  else if(q==='prevmonth'){ a=new Date(t.getFullYear(),t.getMonth()-1,1); b=new Date(t.getFullYear(),t.getMonth(),0); }
  else if(q==='week'){ a=mondayOf(t); b=addDays(a,6); }
  else if(q==='prevweek'){ a=addDays(mondayOf(t),-7); b=addDays(a,6); }
  else if(q==='year'){ a=new Date(t.getFullYear(),0,1); b=new Date(t.getFullYear(),11,31); }
  else if(q==='all'){ const ks=rep.kind==='trips'?flightKeys():[...Object.keys(data.days), ...data.events.map(e=>e.start.slice(0,10)), ...data.tasks.flatMap(x=>x.blocks.map(b=>b.start.slice(0,10)))].sort(); if(ks.length){ a=fromKey(ks[0]); b=fromKey(ks[ks.length-1]); } else a=b=t; }
  else { const [x,y]=visibleRange(); a=x; b=addDays(y,-1); }
  rep.from=key(a); rep.to=key(b);
}

/* ---------- dialog ---------- */
function openReports(kind, opts={}){
  rep.kind=kind||rep.kind;
  if(opts.date) rep.date=opts.date;
  if(opts.projectId!==undefined) rep.projectId=opts.projectId;
  if(!rep.from) setRange('month');
  if(kind==='trips' && !opts.keep){ setRange('view'); }
  const draw=()=>{
    const r = {daily:repDaily, eod:repEod, weekly:repWeekly, project:repProject, time:repTime, trips:repTrips}[rep.kind]();
    repOut=r;
    const ctrl={
      daily:`<label>Date <input type="date" data-rd value="${rep.date||todayKey()}"></label><div class="quick"><button data-day="-1">‹ Previous day</button><button data-day="0">Today</button><button data-day="1">Next day ›</button></div>`,
      eod:`<label>Date <input type="date" data-rd value="${rep.date||todayKey()}"></label><div class="quick"><button data-day="0">Today</button></div>`,
      weekly:`<label>Week starting <input type="date" data-rw value="${rep.week||key(mondayOf(TODAY()))}"></label><div class="quick"><button data-wk="-1">‹ Previous week</button><button data-wk="0">This week</button><button data-wk="1">Next week ›</button></div>`,
      project:`<label>Project <select data-rp><option value="">All projects</option>${data.projects.filter(p=>p.status!=='archived'&&(privKey||!isPrivCal(p.calendarId))).map(p=>`<option value="${p.id}" ${rep.projectId===p.id?'selected':''}>${esc(p.name)}</option>`).join('')}</select></label>`,
      time:'', trips:''}[rep.kind];
    const range = rep.kind==='time'||rep.kind==='trips' ? `<label>From <input type="date" data-rf value="${rep.from}"></label><label>To <input type="date" data-rt value="${rep.to}"></label><div class="quick">${[['view','Shown on calendar'],['week','This week'],['prevweek','Last week'],['month','This month'],['prevmonth','Last month'],['year','This year'],['all','All']].map(([q,l])=>`<button data-q="${q}">${l}</button>`).join('')}</div>` : '';
    const filters = ['weekly','time','daily','eod'].includes(rep.kind) ? `<label>Calendar <select data-rc><option value="">All</option>${data.calendars.map(c=>`<option value="${c.id}" ${rep.cal===c.id?'selected':''}>${esc(c.name)}</option>`).join('')}</select></label>${rep.kind!=='project'?`<label>Project <select data-rp><option value="">All</option>${data.projects.filter(p=>p.status!=='archived').map(p=>`<option value="${p.id}" ${rep.projectId===p.id?'selected':''}>${esc(p.name)}</option>`).join('')}</select></label>`:''}<label>Tag <input type="text" data-rtag value="${esc(rep.tag)}" placeholder="any" style="width:100px"></label>` : '';
    dd.querySelector('.box').innerHTML=`<div class="dhd"><h2>📊 Reports</h2><button class="ghost" data-close>✕</button></div>
      <div class="rep"><nav class="repnav">${Object.entries(REPORTS).map(([k,l])=>`<button data-rk="${k}" class="${rep.kind===k?'on':''}">${l}</button>`).join('')}</nav>
      <div style="min-width:0"><h3 style="margin:0 0 10px">${esc(r.title)}</h3>
        <div class="rctrl">${ctrl}${range}${filters}</div>
        <div id="repBody">${r.html}</div>
        <div class="ractions"><button data-ra="copy" title="Copy as text to paste into an email">📋 Copy as text</button><button data-ra="mail">✉ Email</button>${r.rows.length?'<button data-ra="csv">⬇ CSV (Excel)</button>':''}${['time','trips','weekly','daily'].includes(rep.kind)?'<button data-ra="ics">📤 .ics</button>':''}<button data-ra="print" class="primary">🖨 Print / PDF</button></div>
      </div></div>`;
    const B=dd.querySelector('.box');
    $$('[data-close]',B).forEach(b=>b.onclick=()=>closeDialog(dd));
    $$('[data-rk]',B).forEach(b=>b.onclick=()=>{ rep.kind=b.dataset.rk; if(rep.kind==='trips'||rep.kind==='time') { if(!rep.from) setRange('month'); } draw(); });
    const on=(s,fn)=>{ const el=$(s,B); if(el) el.onchange=()=>{ fn(el.value); draw(); }; };
    on('[data-rd]',v=>rep.date=v); on('[data-rw]',v=>rep.week=key(mondayOf(fromKey(v)))); on('[data-rp]',v=>rep.projectId=v); on('[data-rf]',v=>rep.from=v); on('[data-rt]',v=>rep.to=v); on('[data-rc]',v=>rep.cal=v); on('[data-rtag]',v=>rep.tag=v.trim().replace(/^#/,''));
    if(rep.from>rep.to){ [rep.from,rep.to]=[rep.to,rep.from]; }
    $$('[data-day]',B).forEach(b=>b.onclick=()=>{ const n=+b.dataset.day; rep.date=n?keyAdd(rep.date||todayKey(),n):todayKey(); draw(); });
    $$('[data-wk]',B).forEach(b=>b.onclick=()=>{ const n=+b.dataset.wk; rep.week=n?key(addDays(fromKey(rep.week||key(mondayOf(TODAY()))),7*n)):key(mondayOf(TODAY())); draw(); });
    $$('[data-q]',B).forEach(b=>b.onclick=()=>{ setRange(b.dataset.q); draw(); });
    bindTaskRows(B); $$('#repBody [data-occ]',B).forEach(x=>x.onclick=()=>openOccPopover(x.dataset.occ,x.dataset.oc,x.dataset.ok,x));
    if(r.bind) r.bind(B);
    $$('[data-ra]',B).forEach(b=>b.onclick=()=>repAction(b.dataset.ra, r));
  };
  if(opts.digest && !opts.date) rep.date=todayKey();
  if(kind==='daily'||kind==='eod') rep.date=opts.date||todayKey();
  const dd=openDialog('',{size:'wide', refresh:()=>draw()});
  draw();
}
async function repAction(a, r){
  if(a==='print'){
    const pa=$('#printArea'); pa.innerHTML=`<h2 style="margin:0 0 4px">${esc(r.title)}</h2><div class="print-title" style="display:block;color:#555;font-size:12px;margin-bottom:10px">${esc(data.owner||'')} · printed ${esc(fmtWhen(nowISO()))}</div>${r.html}`;
    document.body.classList.add('print-rep'); setTimeout(()=>{ window.print(); document.body.classList.remove('print-rep'); pa.innerHTML=''; },50); return;
  }
  if(a==='csv'){
    const q=v=>'"'+String(v??'').replace(/"/g,'""')+'"';
    const csv='﻿'+[r.head,...r.rows].map(x=>x.map(q).join(';')).join('\r\n');
    download(r.title.replace(/[^\w\-]+/g,'_').replace(/_+/g,'_').slice(0,80)+'.csv', csv, 'text/csv;charset=utf-8'); return;
  }
  if(a==='copy'){
    try{ await navigator.clipboard.writeText(r.text); }catch(e){ const ta=document.createElement('textarea'); ta.value=r.text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); }
    toast('Copied to the clipboard'); return;
  }
  if(a==='mail'){ const body=r.text.length>1800?r.text.slice(0,1790)+'\n…':r.text; location.href='mailto:?subject='+encodeURIComponent(r.title)+'&body='+encodeURIComponent(body); return; }
  if(a==='ics'){
    let from,to; if(rep.kind==='daily'){ from=to=rep.date||todayKey(); } else if(rep.kind==='weekly'){ from=rep.week||key(mondayOf(TODAY())); to=keyAdd(from,6); } else { from=rep.from; to=rep.to; }
    exportIcs({from, to, name:r.title, filter:repFilter, flights:rep.kind==='trips'});
  }
}

/* ============================================================
   .ics export/import, CSV import, backup, archive, templates,
   privacy dialogs, settings, accounts, history
   ============================================================ */
/* ---------- .ics export ---------- */
const icsEsc=s=>String(s||'').replace(/\\/g,'\\\\').replace(/\n/g,'\\n').replace(/,/g,'\\,').replace(/;/g,'\\;');
const icsFold=l=>{ const out=[]; let s=l; while(s.length>73){ out.push(s.slice(0,73)); s=' '+s.slice(73); } out.push(s); return out.join('\r\n'); };
const icsLocal=l=>l.replace(/[-:]/g,'').slice(0,13)+'00';
const icsDate=k=>k.replace(/-/g,'');
const icsUTC=ms=>new Date(fromDisp(ms)).toISOString().replace(/[-:]/g,'').replace(/\.\d{3}/,'');
function vtimezone(tz){
  const y=new Date().getFullYear(), jan=tzOffsetMin(tz,Date.UTC(y,0,15)), jul=tzOffsetMin(tz,Date.UTC(y,6,15));
  const off=m=>(m<0?'-':'+')+String(Math.floor(Math.abs(m)/60)).padStart(2,'0')+String(Math.abs(m)%60).padStart(2,'0');
  if(jan===jul) return ['BEGIN:VTIMEZONE','TZID:'+tz,'BEGIN:STANDARD','DTSTART:19700101T000000','TZOFFSETFROM:'+off(jan),'TZOFFSETTO:'+off(jan),'END:STANDARD','END:VTIMEZONE'];
  const std=Math.min(jan,jul), dst=Math.max(jan,jul), hh=m=>String(Math.floor((60+m)/60)%24).padStart(2,'0');
  // EU rule (change at 01:00 UTC on the last Sunday of March / October)
  return ['BEGIN:VTIMEZONE','TZID:'+tz,'BEGIN:DAYLIGHT','DTSTART:19700329T'+hh(std)+'0000','RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU','TZOFFSETFROM:'+off(std),'TZOFFSETTO:'+off(dst),'END:DAYLIGHT',
    'BEGIN:STANDARD','DTSTART:19701025T'+hh(dst)+'0000','RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU','TZOFFSETFROM:'+off(dst),'TZOFFSETTO:'+off(std),'END:STANDARD','END:VTIMEZONE'];
}
function rruleStr(rr, allDay, tz){
  const p=['FREQ='+rr.freq.toUpperCase()]; if(rr.interval>1) p.push('INTERVAL='+rr.interval);
  const D=['MO','TU','WE','TH','FR','SA','SU'];
  if(rr.freq==='weekly' && rr.byDay&&rr.byDay.length) p.push('BYDAY='+rr.byDay.map(d=>D[d]).join(','));
  if(rr.count) p.push('COUNT='+rr.count);
  if(rr.until) p.push('UNTIL='+(allDay?icsDate(rr.until):icsUTC(zonedToMs(rr.until+'T23:59',tz||BROWSER_TZ))));
  return p;
}
function valarm(r, title){ return ['BEGIN:VALARM','ACTION:DISPLAY','DESCRIPTION:'+icsEsc(title),'TRIGGER:-PT'+(+r.offsetMin||0)+'M','END:VALARM']; }
function exportIcs(o={}){
  const L=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Work & Travel Calendar//EN','CALSCALE:GREGORIAN','X-WR-CALNAME:'+icsEsc(o.name||data.owner||'Work & Travel Calendar')];
  const tzs=new Set(), body=[];
  const f=o.filter||(()=>true);
  const from=o.from?fromKey(o.from).getTime():-1e14, to=o.to?addDays(fromKey(o.to),1).getTime():1e14;
  const evs=(o.items||data.events.filter(e=>(privKey||!isPrivate(e)) && f(e))).filter(e=>e.type==='event');
  const tks=(o.items?o.items.filter(x=>x.type==='task'):data.tasks.filter(t=>(privKey||!isPrivate(t)) && f(t)));
  const stamp=icsUTC(NOW());
  const dt=(name,local,tz,allDay)=> allDay ? `${name};VALUE=DATE:${icsDate(local.slice(0,10))}` : `${name};TZID=${tz}:${icsLocal(local)}`;
  for(const e of evs){
    if(!o.items && !(e.rrule || expandEvent(e,from,to).length)) continue;
    if(o.items===undefined && e.rrule && !expandEvent(e,from,to).length) continue;
    const tz=e.tz||BROWSER_TZ; if(!e.allDay) tzs.add(tz);
    const ev=['BEGIN:VEVENT','UID:'+(e.uid||e.id+'@wtc'),'DTSTAMP:'+stamp,'SUMMARY:'+icsEsc(e.title)];
    if(e.allDay){ ev.push(dt('DTSTART',e.start,tz,true), 'DTEND;VALUE=DATE:'+icsDate(keyAdd((e.end||e.start).slice(0,10),1))); }
    else ev.push(dt('DTSTART',e.start,tz), dt('DTEND',e.end,tz));
    if(e.location) ev.push('LOCATION:'+icsEsc(e.location));
    const desc=[e.notes, e.onlineLink, ...(e.links||[]).map(l=>l.label+': '+l.url)].filter(Boolean).join('\n'); if(desc) ev.push('DESCRIPTION:'+icsEsc(desc));
    if(e.onlineLink) ev.push('URL:'+e.onlineLink);
    if(e.status==='cancelled') ev.push('STATUS:CANCELLED');
    if(e.showAs==='free') ev.push('TRANSP:TRANSPARENT');
    const cats=[cal(e.calendarId).name, proj(e.projectId)&&proj(e.projectId).name, ...e.tags].filter(Boolean); ev.push('CATEGORIES:'+cats.map(icsEsc).join(','));
    if(e.rrule){ ev.push('RRULE:'+rruleStr(e.rrule,e.allDay,tz).join(';'));
      const ex=Object.entries(e.exceptions).filter(([k,x])=>x.deleted).map(([k])=>e.allDay?icsDate(k):icsLocal(k+'T'+e.start.slice(11,16)));
      if(ex.length) ev.push((e.allDay?'EXDATE;VALUE=DATE:':'EXDATE;TZID='+tz+':')+ex.join(',')); }
    for(const r of e.reminders) ev.push(...valarm(r,e.title));
    ev.push('END:VEVENT'); body.push(...ev);
    // changed occurrences
    for(const [k,x] of Object.entries(e.exceptions)){ if(x.deleted) continue;
      const s=x.start||(e.allDay?k:k+'T'+e.start.slice(11,16)), en=x.end||(e.allDay?keyAdd(k,Math.round((uparse(e.end.slice(0,10))-uparse(e.start.slice(0,10)))/864e5)):localAddMin(s,localDiffMin(e.start,e.end)));
      const ov=['BEGIN:VEVENT','UID:'+(e.uid||e.id+'@wtc'),'DTSTAMP:'+stamp,(e.allDay?'RECURRENCE-ID;VALUE=DATE:'+icsDate(k):'RECURRENCE-ID;TZID='+tz+':'+icsLocal(k+'T'+e.start.slice(11,16))),'SUMMARY:'+icsEsc(x.title||e.title)];
      if(e.allDay) ov.push(dt('DTSTART',s,tz,true),'DTEND;VALUE=DATE:'+icsDate(keyAdd(en.slice(0,10),1))); else ov.push(dt('DTSTART',s,tz),dt('DTEND',en,tz));
      if(x.location||e.location) ov.push('LOCATION:'+icsEsc(x.location||e.location)); if(x.status==='cancelled') ov.push('STATUS:CANCELLED');
      ov.push('END:VEVENT'); body.push(...ov); }
  }
  for(const t of tks){
    for(const b of t.blocks){ const s=zonedToMs(b.start,b.tz); if(s<from||s>=to) continue; tzs.add(b.tz||BROWSER_TZ);
      body.push('BEGIN:VEVENT','UID:'+b.id+'@wtc','DTSTAMP:'+stamp,'SUMMARY:'+icsEsc('[Task] '+t.title),dt('DTSTART',b.start,b.tz||BROWSER_TZ),dt('DTEND',b.end,b.tz||BROWSER_TZ),...(t.notes?['DESCRIPTION:'+icsEsc(t.notes)]:[]),...t.reminders.filter(r=>r.anchor==='block').flatMap(r=>valarm(r,t.title)),'END:VEVENT'); }
    if(t.dueDate && (o.items || (fromKey(t.dueDate).getTime()>=from && fromKey(t.dueDate).getTime()<to))){
      body.push('BEGIN:VTODO','UID:'+t.id+'@wtc','DTSTAMP:'+stamp,'SUMMARY:'+icsEsc(t.title), t.dueTime?`DUE;TZID=${t.tz||S().homeTz}:${icsLocal(t.dueDate+'T'+t.dueTime)}`:'DUE;VALUE=DATE:'+icsDate(t.dueDate),'PRIORITY:'+({1:1,2:3,3:5,4:9})[t.priority||3],'STATUS:'+(t.status==='done'?'COMPLETED':t.status==='cancelled'?'CANCELLED':'NEEDS-ACTION'),...t.reminders.filter(r=>r.anchor==='due').flatMap(r=>valarm(r,t.title)),'END:VTODO');
      if(t.dueTime) tzs.add(t.tz||S().homeTz); }
  }
  if(o.flights){ for(const k of flightKeys()){ if(fromKey(k).getTime()<from||fromKey(k).getTime()>=to) continue; for(const w of flightWindows(k)) body.push('BEGIN:VEVENT','UID:fl-'+k+'-'+code(w.f.from)+code(w.f.to)+'@wtc','DTSTAMP:'+stamp,'SUMMARY:'+icsEsc('✈ '+w.f.from+' → '+w.f.to),'DTSTART:'+icsUTC(w.s),'DTEND:'+icsUTC(w.e),...(w.f.note?['DESCRIPTION:'+icsEsc(w.f.note)]:[]),'END:VEVENT'); } }
  for(const tz of tzs) if(validTz(tz)) L.push(...vtimezone(tz));
  L.push(...body,'END:VCALENDAR');
  download((o.name||'calendar').replace(/[^\w\-]+/g,'_').slice(0,60)+'.ics', L.map(icsFold).join('\r\n')+'\r\n', 'text/calendar;charset=utf-8');
}
function openIcsExport(){
  const [a,b]=visibleRange();
  const d=openDialog(`<div class="dhd"><h2>📤 Export calendar file (.ics)</h2><button class="ghost" data-close>✕</button></div>
    <p class="hint">Import the file into Outlook, Google Calendar or your phone. Reminders are included, so they also alert there when this app is closed.</p>
    <form class="frm"><label>From</label><input type="date" name="f" value="${key(a)}"><label>To</label><input type="date" name="t" value="${key(addDays(b,-1))}">
      <label>Calendar</label><select name="c"><option value="">All</option>${data.calendars.map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('')}</select>
      <label>Project</label><select name="p"><option value="">All</option>${data.projects.map(p=>`<option value="${p.id}">${esc(p.name)}</option>`).join('')}</select>
      <label></label><label class="hint"><input type="checkbox" name="fl"> Include flights</label></form>
    <div class="dacts"><button data-q="all">Everything</button><span class="sp"></span><button data-close>Cancel</button><button class="primary" data-go>Download</button></div>`,{size:'narrow'});
  const F=$('form',d), v=n=>$(`[name=${n}]`,F).value;
  const go=all=>{ const c=v('c'), p=v('p'); exportIcs({from:all?null:v('f'), to:all?null:v('t'), name:'calendar-'+(all?'all':v('f')+'_'+v('t')), filter:it=>(!c||it.calendarId===c)&&(!p||it.projectId===p), flights:$('[name=fl]',F).checked}); closeDialog(d); };
  $('[data-go]',d).onclick=()=>go(false); $('[data-q]',d).onclick=()=>go(true);
}

/* ---------- .ics import ---------- */
const WIN_TZ={'GTB Standard Time':'Europe/Athens','Romance Standard Time':'Europe/Brussels','W. Europe Standard Time':'Europe/Berlin','GMT Standard Time':'Europe/London','UTC':'UTC','Central Europe Standard Time':'Europe/Budapest','Eastern Standard Time':'America/New_York','FLE Standard Time':'Europe/Helsinki','E. Europe Standard Time':'Europe/Chisinau'};
function parseIcs(text){
  const lines=text.replace(/\r\n[ \t]/g,'').replace(/\n[ \t]/g,'').split(/\r?\n/);
  const comps=[]; let cur=null, stack=[];
  for(const ln of lines){
    if(/^BEGIN:/i.test(ln)){ const t=ln.slice(6).toUpperCase(); const c={type:t,props:[],children:[]}; if(cur) cur.children.push(c); stack.push(cur); cur=c; if(t==='VEVENT'||t==='VTODO') comps.push(c); continue; }
    if(/^END:/i.test(ln)){ cur=stack.pop(); continue; }
    if(!cur) continue;
    const i=ln.indexOf(':'); if(i<0) continue;
    const left=ln.slice(0,i), val=ln.slice(i+1); const [name,...params]=left.split(';');
    const pm={}; params.forEach(p=>{ const [k,v]=p.split('='); pm[k.toUpperCase()]=(v||'').replace(/^"|"$/g,''); });
    cur.props.push({name:name.toUpperCase(), params:pm, val});
  }
  const un=s=>s.replace(/\\n/gi,'\n').replace(/\\,/g,',').replace(/\\;/g,';').replace(/\\\\/g,'\\');
  const get=(c,n)=>c.props.find(p=>p.name===n);
  const toLocal=p=>{ // → {allDay, local, tz}
    if(!p) return null; const v=p.val;
    if(p.params.VALUE==='DATE' || /^\d{8}$/.test(v)) return {allDay:true, local:v.slice(0,4)+'-'+v.slice(4,6)+'-'+v.slice(6,8)};
    const m=/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})?(Z)?$/.exec(v); if(!m) return null;
    const loc=`${m[1]}-${m[2]}-${m[3]}T${m[4]}:${m[5]}`;
    if(m[7]){ const tz=S().homeTz||BROWSER_TZ; return {allDay:false, local:msToZoned(toDisp(Date.UTC(+m[1],m[2]-1,+m[3],+m[4],+m[5])),tz), tz}; }
    let tz=p.params.TZID||S().homeTz||BROWSER_TZ; tz=WIN_TZ[tz]||tz; if(!validTz(tz)) tz=S().homeTz||BROWSER_TZ;
    return {allDay:false, local:loc, tz};
  };
  const out=[];
  const D={MO:0,TU:1,WE:2,TH:3,FR:4,SA:5,SU:6};
  const overrides=[];
  for(const c of comps){
    const uidp=get(c,'UID'), sum=get(c,'SUMMARY'), st=toLocal(get(c,'DTSTART')), en=toLocal(get(c,'DTEND')), rid=get(c,'RECURRENCE-ID');
    const dm=/^P(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:\d+S)?)?$/.exec(((get(c,'DURATION')||{}).val||'').trim());
    const durP = dm ? (+dm[1]||0)*10080+(+dm[2]||0)*1440+(+dm[3]||0)*60+(+dm[4]||0) : 0;  // DURATION (Google, Apple) when there is no DTEND
    const alarms=c.children.filter(x=>x.type==='VALARM').map(a=>{ const t=(a.props.find(p=>p.name==='TRIGGER')||{}).val||''; const m=/-?P(?:(\d+)W)?(?:(\d+)D)?T?(?:(\d+)H)?(?:(\d+)M)?/.exec(t); return m?{offsetMin:(+m[1]||0)*10080+(+m[2]||0)*1440+(+m[3]||0)*60+(+m[4]||0), channel:'popup'}:null; }).filter(Boolean);
    if(c.type==='VTODO'){ const due=toLocal(get(c,'DUE')); out.push({kind:'task', uid:uidp&&uidp.val, title:sum?un(sum.val):'(no title)', dueDate:due?due.local.slice(0,10):null, dueTime:due&&!due.allDay?due.local.slice(11,16):null, notes:get(c,'DESCRIPTION')?un(get(c,'DESCRIPTION').val):'', done:/COMPLETED/i.test((get(c,'STATUS')||{}).val||''), reminders:alarms.map(a=>({anchor:'due',...a}))}); continue; }
    if(!st) continue;
    const e={kind:'event', uid:uidp&&uidp.val, title:sum?un(sum.val):'(no title)', allDay:st.allDay, start:st.local, tz:st.tz,
      end: en ? (st.allDay ? keyAdd(en.local.slice(0,10),-1) : en.local)
              : durP ? (st.allDay ? keyAdd(st.local.slice(0,10), Math.max(0,Math.round(durP/1440)-1)) : localAddMin(st.local, durP))
              : (st.allDay?st.local:localAddMin(st.local,60)),
      location:get(c,'LOCATION')?un(get(c,'LOCATION').val):'', notes:get(c,'DESCRIPTION')?un(get(c,'DESCRIPTION').val):'', cancelled:/CANCELLED/i.test((get(c,'STATUS')||{}).val||''), reminders:alarms, exceptions:{}, rrule:null};
    if(en && !st.allDay && en.tz && en.tz!==st.tz) e.end=msToZoned(zonedToMs(en.local,en.tz),st.tz);
    const rr=get(c,'RRULE');
    if(rr){ const p=Object.fromEntries(rr.val.split(';').map(x=>x.split('='))); const f=(p.FREQ||'').toLowerCase();
      if(['daily','weekly','monthly','yearly'].includes(f)){ e.rrule={freq:f, interval:+p.INTERVAL||1};
        if(p.BYDAY){ const days=p.BYDAY.split(',');
          if(f==='weekly') e.rrule.byDay=days.map(x=>D[x.slice(-2)]).filter(x=>x!=null);
          if(f==='monthly'){ const m=/^(-?\d)(\w\w)$/.exec(days[0]); if(m){ e.rrule.monthMode='nth'; e.rrule.nth=+m[1]; } } }
        if(p.COUNT) e.rrule.count=+p.COUNT;
        if(p.UNTIL){ const u=p.UNTIL; e.rrule.until=u.slice(0,4)+'-'+u.slice(4,6)+'-'+u.slice(6,8); } } }
    for(const exd of c.props.filter(p=>p.name==='EXDATE')) for(const v of exd.val.split(',')){ const l=toLocal({val:v,params:exd.params}); if(l) e.exceptions[l.local.slice(0,10)]={deleted:true}; }
    if(rid){ const r=toLocal(rid); overrides.push({uid:e.uid, k:r?r.local.slice(0,10):null, e}); continue; }
    out.push(e);
  }
  for(const o of overrides){ const m=out.find(x=>x.uid===o.uid && x.rrule); if(m && o.k) m.exceptions[o.k]={start:o.e.start, end:o.e.end, ...(o.e.title!==m.title?{title:o.e.title}:{}), ...(o.e.location&&o.e.location!==m.location?{location:o.e.location}:{}), ...(o.e.cancelled?{status:'cancelled'}:{})}; else out.push(o.e); }
  return out;
}
async function importIcsFile(file){
  const items=parseIcs(await file.text());
  if(!items.length) return alert('No events or tasks found in that file.');
  const known=new Set([...data.events,...data.tasks].map(x=>x.uid).filter(Boolean));
  const d=openDialog(`<div class="dhd"><h2>📥 Import “${esc(file.name)}”</h2><button class="ghost" data-close>✕</button></div>
    <form class="frm"><label>Calendar</label><select name="calendarId">${calOptions('work')}</select><label>Project</label><select name="projectId">${projOptions('work',null)}</select></form>
    <div class="hint" style="margin:8px 0">${items.length} item(s). Items already imported before (same UID) are unticked.</div>
    <div style="max-height:46vh;overflow:auto"><table class="rt"><thead><tr><th><input type="checkbox" data-all checked aria-label="All"></th><th>Type</th><th>When</th><th>Title</th></tr></thead><tbody>
    ${items.map((x,i)=>`<tr><td><input type="checkbox" data-i="${i}" ${x.uid&&known.has(x.uid)?'':'checked'}></td><td>${x.kind==='task'?'Task':x.rrule?'🔁 Event':'Event'}${x.uid&&known.has(x.uid)?' <span class="tag">duplicate</span>':''}</td><td class="num">${x.kind==='task'?(x.dueDate||''):esc(x.allDay?x.start:x.start.replace('T',' '))}</td><td>${esc(x.title)}</td></tr>`).join('')}</tbody></table></div>
    <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" data-go>Import selected</button></div>`,{size:'mid'});
  const F=$('form',d); bindCalProj(F);
  $('[data-all]',d).onchange=e=>$$('[data-i]',d).forEach(c=>c.checked=e.target.checked);
  $('[data-go]',d).onclick=()=>{
    const calendarId=$('[name=calendarId]',F).value, projectId=$('[name=projectId]',F).value||null;
    if(isPrivCal(calendarId) && !ensurePrivReadySync()) return;
    let n=0;
    for(const c of $$('[data-i]:checked',d)){ const x=items[+c.dataset.i];
      if(x.kind==='task'){ const t=newTask({title:x.title, calendarId, projectId, dueDate:x.dueDate, dueTime:x.dueTime, notes:x.notes, status:x.done?'done':'todo', uid:x.uid}); if(x.reminders.length) t.reminders=x.reminders; touch(t); data.tasks.push(t); }
      else { const e=newEvent({title:x.title, calendarId, projectId, allDay:x.allDay, start:x.start, end:x.end, tz:x.tz, location:x.location, notes:x.notes, status:x.cancelled?'cancelled':'confirmed', rrule:x.rrule, exceptions:x.exceptions, uid:x.uid, category:'other'}); e.reminders=x.reminders.length?x.reminders:e.reminders; if(e.allDay) delete e.tz; touch(e); data.events.push(e); }
      n++; }
    changed(); closeDialog(d); renderAll(); toast(`Imported ${n} item${n===1?'':'s'}`,{actions:[['Undo',undo]]});
  };
}

/* ---------- CSV import of tasks ---------- */
function parseCsv(text){
  const first=text.split(/\r?\n/)[0]||''; const delim=(first.match(/;/g)||[]).length>(first.match(/,/g)||[]).length?';':(first.includes('\t')?'\t':',');
  const rows=[]; let row=[], cell='', q=false;
  for(let i=0;i<text.length;i++){ const c=text[i];
    if(q){ if(c==='"'){ if(text[i+1]==='"'){ cell+='"'; i++; } else q=false; } else cell+=c; continue; }
    if(c==='"') q=true; else if(c===delim){ row.push(cell); cell=''; } else if(c==='\n'||c==='\r'){ if(c==='\r'&&text[i+1]==='\n') i++; row.push(cell); rows.push(row); row=[]; cell=''; } else cell+=c; }
  if(cell||row.length){ row.push(cell); rows.push(row); }
  return rows.filter(r=>r.some(c=>c.trim()));
}
function parseEstimate(s){ s=String(s||'').trim().toLowerCase(); if(!s) return null; let m=/^(\d+(?:[.,]\d+)?)\s*h(?:\s*(\d+)\s*m?)?$/.exec(s); if(m) return Math.round(parseFloat(m[1].replace(',','.'))*60)+(+m[2]||0); m=/^(\d+)\s*m(in)?$/.exec(s); if(m) return +m[1]; m=/^(\d+):(\d{2})$/.exec(s); if(m) return (+m[1])*60+(+m[2]); if(/^\d+$/.test(s)) return +s; return null; }
function parseDateLoose(s){ s=String(s||'').trim(); let m=/^(\d{4})-(\d{1,2})-(\d{1,2})/.exec(s); if(m) return `${m[1]}-${m[2].padStart(2,'0')}-${m[3].padStart(2,'0')}`; m=/^(\d{1,2})[\/.](\d{1,2})[\/.](\d{2,4})/.exec(s); if(m){ const y=m[3].length===2?'20'+m[3]:m[3]; return `${y}-${m[2].padStart(2,'0')}-${m[1].padStart(2,'0')}`; } return null; }
async function importCsvFile(file){
  const rows=parseCsv((await file.text()).replace(/^\ufeff/,''));
  if(rows.length<2) return alert('The file needs a header row and at least one task.');
  const h=rows[0].map(x=>x.trim().toLowerCase());
  const col=n=>h.findIndex(x=>n.some(y=>x.startsWith(y)));
  const ci={title:col(['title','task','name','τίτλος']), due:col(['due','date','deadline']), project:col(['project']), priority:col(['prio']), estimate:col(['est','duration','hours'])};
  if(ci.title<0) return alert('Could not find a "title" column. Columns needed: title, due, project, priority, estimate.');
  const tasks=rows.slice(1).map(r=>({title:(r[ci.title]||'').trim(), due:ci.due>=0?parseDateLoose(r[ci.due]):null, project:ci.project>=0?(r[ci.project]||'').trim():'', priority:ci.priority>=0?clamp(parseInt(String(r[ci.priority]).replace(/\D/g,''))||3,1,4):3, est:ci.estimate>=0?parseEstimate(r[ci.estimate]):null})).filter(t=>t.title);
  const d=openDialog(`<div class="dhd"><h2>📥 Import tasks from “${esc(file.name)}”</h2><button class="ghost" data-close>✕</button></div>
    <div class="hint">${tasks.length} task(s). Projects are matched by name; unknown ones are created in the Work calendar.</div>
    <div style="max-height:50vh;overflow:auto;margin-top:8px"><table class="rt"><thead><tr><th>Title</th><th>Due</th><th>Project</th><th>Priority</th><th>Estimate</th></tr></thead><tbody>${tasks.map(t=>`<tr><td>${esc(t.title)}</td><td class="num">${t.due||''}</td><td>${esc(t.project)}${t.project&&!data.projects.some(p=>p.name.toLowerCase()===t.project.toLowerCase())?' <span class="tag">new</span>':''}</td><td>P${t.priority}</td><td>${t.est?fmtDur(t.est):''}</td></tr>`).join('')}</tbody></table></div>
    <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" data-go>Import</button></div>`,{size:'mid'});
  $('[data-go]',d).onclick=()=>{
    for(const x of tasks){ let p=null; if(x.project){ p=data.projects.find(q=>q.name.toLowerCase()===x.project.toLowerCase()); if(!p){ p=newProject({name:x.project}); touch(p); data.projects.push(p); } }
      const t=newTask({title:x.title, dueDate:x.due, projectId:p?p.id:null, calendarId:p?p.calendarId:'work', priority:x.priority, estimateMin:x.est}); if(isPrivate(t)&&!privKey) continue; touch(t); data.tasks.push(t); }
    changed(); closeDialog(d); renderAll(); toast(`Imported ${tasks.length} tasks`,{actions:[['Undo',undo]]});
  };
}

/* ---------- backup (export / restore with preview) ---------- */
async function exportBackup(){
  const plain = privKey && await confirmDlg({title:'Export backup', html:'Include Personal items <b>unencrypted</b> in the file? Choose “Keep encrypted” if the file may be stored where others can read it.'},[['Keep encrypted','enc','primary'],['Include unencrypted','plain'],['Cancel',null]]);
  if(privKey && !plain) return;
  let out = await serialize();
  if(plain==='plain'){ out=clone(data); delete out.private; delete out.busy; }
  download('calendar-backup-'+todayKey()+'.json', JSON.stringify(out,null,1), 'application/json');
}
function parseDataText(text){
  const json = text.replace(/^[\s\S]*?window\.CALENDAR_DATA\s*=\s*/,'').replace(/;\s*$/,'').trim();
  return normalize(json ? JSON.parse(json) : {}).d;
}
async function importBackupFile(f){
  try{
    const txt=await f.text();
    const dd = /CALENDAR_DATA/.test(txt) ? parseDataText(txt) : normalize(JSON.parse(txt)).d;
    const cnt=x=>`${Object.keys(x.days).length} days · ${x.events.length} events · ${x.tasks.length} tasks · ${x.projects.length} projects${x.private?' · encrypted Personal block':''}`;
    const d=openDialog(`<div class="dhd"><h2>Restore / import backup</h2><button class="ghost" data-close>✕</button></div>
      <table class="rt"><tbody><tr><td>Now</td><td>${cnt(data)}</td></tr><tr><td>In the file</td><td>${cnt(dd)}</td></tr></tbody></table>
      <p class="hint">“Replace” makes the calendar exactly like the file. “Add missing” keeps everything you have and adds days/items that are only in the file. User accounts are not changed; the server keeps the current version under ⋯ → Earlier versions.</p>
      <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button data-m="add">Add missing</button><button class="primary" data-m="replace">Replace</button></div>`,{size:'mid'});
    $$('[data-m]',d).forEach(b=>b.onclick=async()=>{
      closeDialog(d);
      if(b.dataset.m==='replace'){ const keepKey=privKey; data=dd; privKey=null; privLocked=!!dd.private; if(dd.private && keepKey){ try{ const p=await decPriv(keepKey,dd.private); privKey=keepKey; privLocked=false; mergePriv(p); }catch(e){} } }
      else { for(const [k,v] of Object.entries(dd.days)) if(!data.days[k]) data.days[k]=v; for(const c of ['projects','events','tasks','templates']){ const have=new Set(data[c].map(x=>x.id)); for(const x of dd[c]) if(!have.has(x.id)) data[c].push(x); } normalizeItems(); }
      changed(); renderAll(); toast('Backup loaded',{actions:[['Undo',undo]]});
    });
  }catch(err){ alert('Could not read that file: '+err.message); }
}

/* ---------- archive (old items to a file, opened on demand) ---------- */
async function archiveOld(months){
  const cut=key(addDays(TODAY(),-Math.round(months*30.4)));
  const oldT=data.tasks.filter(t=>!isOpen(t) && (t.completedAt?t.completedAt.slice(0,10):(t.dueDate||'9999'))<cut);
  const oldE=data.events.filter(e=>!e.rrule && (e.end||e.start).slice(0,10)<cut || (e.rrule && e.rrule.until && e.rrule.until<cut));
  if(!oldT.length && !oldE.length) return toast('Nothing older than that to archive.');
  if(!await confirmDlg({title:'Archive',html:`Move ${oldE.length} past events and ${oldT.length} finished tasks (before ${fmtDMY(fromKey(cut))}) to an archive file? The file downloads now; open it later from Settings → Data to include it in reports and search.`},[['Archive','y','primary'],['Cancel',null]])) return;
  const file={archive:true, created:nowISO(), before:cut, events:oldE.filter(x=>!isPrivate(x)||privKey), tasks:oldT.filter(x=>!isPrivate(x)||privKey), projects:data.projects};
  download('calendar-archive-before-'+cut+'.json', JSON.stringify(file), 'application/json');
  const ids=new Set([...oldE,...oldT].map(x=>x.id));
  data.events=data.events.filter(x=>!ids.has(x.id)); data.tasks=data.tasks.filter(x=>!ids.has(x.id));
  changed(); renderAll(); toast('Archived. Keep the downloaded file somewhere safe.');
}
async function openArchiveFile(f){ try{ const a=JSON.parse(await f.text()); if(!a.archive) throw new Error('This is not an archive file.'); archive={events:arr(a.events), tasks:arr(a.tasks), name:f.name}; normalizeArchive(); toast(`Archive “${f.name}” opened for this session (reports and search).`); }catch(e){ alert(e.message); } }
function normalizeArchive(){ for(const e of archive.events){ e.exceptions=obj(e.exceptions); e.reminders=arr(e.reminders); e.tags=arr(e.tags); } for(const t of archive.tasks){ t.blocks=arr(t.blocks); t.tags=arr(t.tags); t.reminders=arr(t.reminders); t.checklist=arr(t.checklist); t.dependsOn=arr(t.dependsOn); } }
function pickFile(accept, fn){ const i=$('#fileInput'); i.accept=accept; i.value=''; i.onchange=()=>{ const f=i.files[0]; if(f) fn(f); }; i.click(); }

/* ---------- templates ---------- */
function saveAsTemplate(items){
  promptDlg('Save as template','Template name', items[0].title||'',{ok:'Save'}).then(name=>{
    if(!name) return;
    const base=items.map(x=>x.type==='event'?(x.start||'').slice(0,10):(x.dueDate||(x.blocks[0]&&x.blocks[0].start.slice(0,10))||todayKey())).sort()[0];
    const tpl={id:uid('tpl'), name, created:nowISO(), private:items.some(isPrivate), items:items.map(x=>{
      if(x.type==='event'){ const off=Math.round((uparse(x.start.slice(0,10))-uparse(base))/864e5); return {kind:'event', offsetDays:off, title:x.title, allDay:x.allDay, startTime:x.allDay?null:x.start.slice(11,16), durationMin:x.allDay?null:localDiffMin(x.start,x.end), spanDays:x.allDay?Math.round((uparse(x.end.slice(0,10))-uparse(x.start.slice(0,10)))/864e5):0, tz:x.tz, category:x.category, location:x.location, notes:x.notes, reminders:x.reminders, calendarId:x.calendarId, tags:x.tags}; }
      const off=x.dueDate?Math.round((uparse(x.dueDate)-uparse(base))/864e5):0; return {kind:'task', offsetDays:off, title:x.title, estimateMin:x.estimateMin, priority:x.priority, checklist:x.checklist.map(c=>({text:c.text})), notes:x.notes, calendarId:x.calendarId, tags:x.tags, hasDue:!!x.dueDate}; })};
    data.templates.push(tpl); changed(); toast(`Template “${name}” saved`);
  });
}
function saveProjectAsTemplate(p){
  const ts=data.tasks.filter(t=>t.projectId===p.id), es=data.events.filter(e=>e.projectId===p.id && !e.rrule);
  const base=p.startDate||todayKey();
  promptDlg('Save project as template','Template name', p.name,{ok:'Save'}).then(name=>{
    if(!name) return;
    data.templates.push({id:uid('tpl'), name, created:nowISO(), private:isPrivCal(p.calendarId), project:{name:p.name, color:p.color, icon:p.icon, calendarId:p.calendarId, budgetHours:p.budgetHours, durationDays:p.dueDate?Math.round((uparse(p.dueDate)-uparse(base))/864e5):null},
      items:[...es.map(e=>({kind:'event', offsetDays:Math.round((uparse(e.start.slice(0,10))-uparse(base))/864e5), title:e.title, allDay:e.allDay, startTime:e.allDay?null:e.start.slice(11,16), durationMin:e.allDay?null:localDiffMin(e.start,e.end), spanDays:0, tz:e.tz, category:e.category, location:e.location, reminders:e.reminders})),
             ...ts.map(t=>({kind:'task', offsetDays:t.dueDate?Math.round((uparse(t.dueDate)-uparse(base))/864e5):0, hasDue:!!t.dueDate, title:t.title, estimateMin:t.estimateMin, priority:t.priority, checklist:t.checklist.map(c=>({text:c.text})), notes:t.notes}))]});
    changed(); toast(`Template “${name}” saved (${ts.length} tasks, ${es.length} events)`);
  });
}
function openTemplates(){
  const draw=()=>{
    const list=data.templates;
    dd.querySelector('.box').innerHTML=`<div class="dhd"><h2>🧩 Templates</h2><button class="ghost" data-close>✕</button></div>
      ${list.length?list.map((t,i)=>`<div class="slot" style="margin-bottom:6px"><div><b>${esc(t.name)}</b>${t.private?' 🔒':''}<div class="hint">${t.project?'Project · ':''}${t.items.map(x=>x.kind==='flight'?'✈ '+code(x.from)+'→'+code(x.to):x.kind==='event'?'📅 '+x.title:'☑ '+x.title).join(' · ')}</div></div>
        <div style="display:flex;gap:6px">${editable?`<button class="primary" data-use="${i}">Use…</button><button class="ghost danger" data-tdel="${i}" aria-label="Delete template">🗑</button>`:''}</div></div>`).join(''):'<div class="rempty">No templates yet. Use “Save as template” in an event, task or project.</div>'}
      <div class="dacts"><span class="sp"></span><button data-close>Close</button></div>`;
    const B=dd.querySelector('.box'); $$('[data-close]',B).forEach(b=>b.onclick=()=>closeDialog(dd));
    $$('[data-tdel]',B).forEach(b=>b.onclick=async()=>{ const t=list[+b.dataset.tdel]; if(!await confirmDlg(`Delete template “${t.name}”?`,[['Delete','y','danger'],['Cancel',null]])) return; data.templates.splice(+b.dataset.tdel,1); changed(); draw(); });
    $$('[data-use]',B).forEach(b=>b.onclick=()=>applyTemplate(list[+b.dataset.use]));
  };
  const dd=openDialog('',{size:'mid'}); draw();
}
function applyTemplate(tpl){
  const d=openDialog(`<div class="dhd"><h2>Use “${esc(tpl.name)}”</h2><button class="ghost" data-close>✕</button></div>
    <form class="frm"><label>Start date</label><input type="date" name="d" value="${key(cursor)}" required>
    ${tpl.project?`<label>Project name</label><input type="text" name="pn" value="${esc(tpl.project.name)}">`:`<label>Add to project</label><select name="p">${projOptions('work',null)}</select>`}</form>
    <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" data-go>Create</button></div>`,{size:'narrow'});
  $('[data-go]',d).onclick=()=>{
    const base=$('[name=d]',d).value; if(!base) return;
    let projectId=null, calendarId='work';
    if(tpl.project){ const p=newProject({name:$('[name=pn]',d).value||tpl.project.name, color:tpl.project.color, icon:tpl.project.icon, calendarId:tpl.project.calendarId||'work', budgetHours:tpl.project.budgetHours, startDate:base, dueDate:tpl.project.durationDays!=null?keyAdd(base,tpl.project.durationDays):null});
      if(isPrivCal(p.calendarId)&&!ensurePrivReadySync()) return; touch(p); data.projects.push(p); projectId=p.id; calendarId=p.calendarId; }
    else { projectId=$('[name=p]',d).value||null; if(projectId) calendarId=proj(projectId).calendarId; }
    let n=0;
    for(const x of tpl.items){
      const k=keyAdd(base,x.offsetDays||0);
      if(x.kind==='flight'){ const m=dayMut(k); (m.flights=m.flights||[]).push({from:x.from,to:x.to,time:x.time||'morning'}); n++; continue; }
      if(x.kind==='event'){ const tz=x.tz||ACTIVE_TZ; const e=newEvent({title:x.title, calendarId:x.calendarId&&!tpl.project?x.calendarId:calendarId, projectId, category:x.category||'meeting', allDay:!!x.allDay, tz, location:x.location||'', notes:x.notes||'', tags:x.tags||[]});
        if(x.allDay){ e.start=k; e.end=keyAdd(k,x.spanDays||0); delete e.tz; } else { e.start=k+'T'+(x.startTime||'09:00'); e.end=x.endTime?k+'T'+x.endTime:localAddMin(e.start,x.durationMin||60); }
        if(x.reminders) e.reminders=clone(x.reminders); if(isPrivate(e)&&!privKey) continue; touch(e); data.events.push(e); n++; continue; }
      const t=newTask({title:x.title, calendarId:x.calendarId&&!tpl.project?x.calendarId:calendarId, projectId, estimateMin:x.estimateMin||null, priority:x.priority||3, dueDate:x.hasDue===false?null:k, notes:x.notes||'', tags:x.tags||[], checklist:(x.checklist||[]).map(c=>({id:uid('c'),text:c.text,done:false}))});
      if(isPrivate(t)&&!privKey) continue; touch(t); data.tasks.push(t); n++;
    }
    changed(); closeDialog(d); closeDialog(); renderAll(); toast(`Created ${n} item${n===1?'':'s'} from “${tpl.name}”`,{actions:[['Undo',undo]]});
  };
}

/* ---------- privacy dialogs ---------- */
function openPrivSetup(){
  const d=openDialog(`<div class="dhd"><h2>🔒 Protect Personal items</h2><button class="ghost" data-close>✕</button></div>
    <p class="hint">Personal items are encrypted in your browser with a passphrase before they are saved, so viewers (and the server) cannot read them. Viewers see that time only as “Busy” (you can turn that off in Settings). Anyone you tell the passphrase to can unlock them.</p>
    <p class="hint"><b>Keep the passphrase safe:</b> it cannot be recovered. Without it, Personal items cannot be opened.</p>
    <form><label class="hint">Passphrase (at least 8 characters)</label><input type="password" id="pp1" minlength="8" required autocomplete="new-password" style="margin:4px 0 8px">
      <label class="hint">Repeat</label><input type="password" id="pp2" minlength="8" required autocomplete="new-password" style="margin:4px 0 8px">
      <label class="hint"><input type="checkbox" id="ppr" checked> Remember on this device</label><div class="lerr" id="ppe"></div>
      <div class="dacts"><span class="sp"></span><button type="button" data-close>Cancel</button><button class="primary" type="submit">Set passphrase</button></div></form>`,{size:'narrow'});
  $('form',d).onsubmit=async e=>{ e.preventDefault(); const a=$('#pp1',d).value, b=$('#pp2',d).value; if(a!==b) return $('#ppe',d).textContent='The two passphrases do not match.';
    try{ await setPassphrase(a,$('#ppr',d).checked); changed(); closeDialog(d); renderAll(); toast('Personal items are now protected.'); }catch(err){ $('#ppe',d).textContent=err.message; } };
}
function openUnlock(){
  if(!data.private) return openPrivSetup();
  const d=openDialog(`<div class="dhd"><h2>🔑 Unlock Personal items</h2><button class="ghost" data-close>✕</button></div>
    <form><input type="password" id="up" placeholder="Passphrase" required autocomplete="current-password"><label class="hint" style="display:block;margin-top:8px"><input type="checkbox" id="ur" checked> Remember on this device</label>
    <div class="lerr" id="ue"></div><div class="dacts"><span class="sp"></span><button type="button" data-close>Cancel</button><button class="primary" type="submit">Unlock</button></div></form>`,{size:'narrow'});
  $('form',d).onsubmit=async e=>{ e.preventDefault(); const btn=$('[type=submit]',d); btn.disabled=true; try{ await unlockPriv($('#up',d).value,$('#ur',d).checked); lastSnap=snapshotState(); closeDialog(d); renderAll(); toast('Personal items unlocked.'); }catch(err){ $('#ue',d).textContent=err.message; btn.disabled=false; } };
}
async function changePassphrase(){
  if(privLocked) return openUnlock();
  const r=await promptDlg('Change passphrase','New passphrase (at least 8 characters)','',{type:'password', attrs:'minlength="8" required autocomplete="new-password"', ok:'Change'});
  if(!r) return; await setPassphrase(r,true); changed(); toast('Passphrase changed. Tell anyone else who uses it.');
}

/* ---------- settings ---------- */
let setTab='general';
function openSettings(tab){
  if(tab) setTab=tab;
  const draw=()=>{
    const s=S(), ro=!editable?'disabled':'';
    const tabs=[['general','General'],['notify','Reminders & notifications'],['calendars','Calendars & privacy'],['templates','Templates'],['trash','Trash'],['data','Data']];
    let body='';
    if(setTab==='general') body=`<form class="frm" data-sf>
      <label>Work hours</label><div class="row"><input type="time" name="workStart" value="${s.workStart}" ${ro} aria-label="Work starts"> – <input type="time" name="workEnd" value="${s.workEnd}" ${ro} aria-label="Work ends"></div>
      <label>Morning / evening</label><div class="row">split at <input type="time" name="midday" value="${s.midday}" ${ro}> <span class="hint">morning ${esc(s.workStart)}–${esc(s.midday)}, evening ${esc(s.midday)}–${esc(s.workEnd)}; used for half-day leave warnings</span></div>
      <label>Flights</label><div class="hint">Default times (local to the departure city): ${Object.entries(FLIGHT_TIMES).map(([k,l])=>`${l} ${minToHM(FLIGHT_WINDOWS[k][0])}–${FLIGHT_WINDOWS[k][1]>=1440?'24:00':minToHM(FLIGHT_WINDOWS[k][1])} (alarm ${minToHM(FLIGHT_ALARM[k])})`).join(' · ')}. Change a single flight’s times or alarm in its day.</div>
      <label>Work days</label><div class="row daychips">${DOW3.map((d,i)=>`<button type="button" data-wd="${i}" class="${s.workDays.includes(i)?'on':''}" ${ro}>${d}</button>`).join('')}</div>
      <label>Home time zone</label><select name="homeTz" ${ro}>${tzOptions(s.homeTz)}</select>
      <div class="hint full">The 🌍 menu at the top sets the <b>active time zone</b> for this device. New items are entered in it, and every event, task and flight is shown in it, so when you travel and switch zone, the calendar moves to your local times. The home time zone is used for due dates without a time and for flights from unknown cities.</div>
      <label>Default lengths</label><div class="row">event <input type="number" name="defaultEventMin" min="5" step="5" value="${s.defaultEventMin}" style="width:80px" ${ro}> min · task block <input type="number" name="defaultBlockMin" min="15" step="15" value="${s.defaultBlockMin}" style="width:80px" ${ro}> min</div>
      <label>All-day reminders at</label><input type="time" name="allDayRemTime" value="${s.allDayRemTime}" ${ro}>
      <div class="sec">This device</div>
      <label>Hour height</label><div class="row"><input type="range" min="24" max="96" step="4" data-p="hourPx" value="${prefs.hourPx}"> <span class="hint">${prefs.hourPx}px</span></div>
      <label>Open details</label><div class="row"><select data-p="openDetails"><option value="tab" ${(prefs.openDetails||'tab')!=='popup'?'selected':''}>In a new browser tab</option><option value="popup" ${prefs.openDetails==='popup'?'selected':''}>In a popup on the calendar</option></select><span class="hint">when you click an event, a project or a flight</span></div>
      <label>Edit windows</label><div class="row"><select data-p="openEditors"><option value="tab" ${(prefs.openEditors||'tab')!=='popup'?'selected':''}>In a new browser tab (more space)</option><option value="popup" ${prefs.openEditors==='popup'?'selected':''}>In a popup on the calendar</option></select><span class="hint">events, tasks and projects</span></div>
      <label>Offline copy</label><label class="hint"><input type="checkbox" data-p="offlineCopy" ${prefs.offlineCopy?'checked':''}> Keep a copy on this device so the calendar opens without internet (read-only). Don’t use on shared computers.</label>
      <label>Install</label><div class="hint">${installHint()}</div>
      <label>Version</label><div class="hint">Work &amp; Travel Calendar ${APP_VERSION}</div></form>`;
    if(setTab==='notify') body=`<form class="frm" data-sf>
      <div class="sec" style="border:0;padding-top:0">Defaults for new items</div>
      <label>Events</label>${remPreview(s.defaultEventRem)}<label>Task due</label>${remPreview(s.defaultDueRem)}<label>Task blocks</label>${remPreview(s.defaultBlockRem)}
      <div class="full hint">Change these defaults with the buttons below; each item can have its own reminders.</div>
      <label></label><div class="row"><button type="button" data-edrem="defaultEventRem" ${ro}>Edit event defaults</button><button type="button" data-edrem="defaultDueRem" ${ro}>Edit due defaults</button><button type="button" data-edrem="defaultBlockRem" ${ro}>Edit block defaults</button></div>
      <label>Flight alarms</label><label class="hint"><input type="checkbox" name="travelReminders" ${s.travelReminders?'checked':''} ${ro}> Alarm before each flight (popup, desktop notification and sound), with the events around it</label>
      <div class="sec">This device</div>
      <label>Desktop</label><div class="row">${'Notification' in window?(Notification.permission==='granted'?'<span class="okbox">Allowed ✓</span>':`<button type="button" data-perm>Turn on desktop notifications</button>`):'<span class="hint">Not supported by this browser</span>'}<label class="hint"><input type="checkbox" data-p="desktop" ${prefs.desktop?'checked':''}> for every reminder</label></div>
      <label>Sound</label><label class="hint"><input type="checkbox" data-p="sound" ${prefs.sound?'checked':''}> Play a chime <button type="button" data-chime>Test</button></label>
      <label>Quiet hours</label><div class="row"><input type="time" data-p="quietStart" value="${prefs.quietStart}"> – <input type="time" data-p="quietEnd" value="${prefs.quietEnd}"> <span class="hint">no desktop notifications or sounds; popups still show</span></div>
      <label>Daily agenda</label><div class="row"><label class="hint"><input type="checkbox" data-p="digest" ${prefs.digest?'checked':''}> show on first open after</label><input type="time" data-p="digestTime" value="${prefs.digestTime}"></div>
      <label>Weekly review</label><div class="row"><label class="hint"><input type="checkbox" data-p="review" ${prefs.review?'checked':''}> prompt on</label><select data-p="reviewDay">${DAY_NAMES.map((n,i)=>`<option value="${i}" ${+prefs.reviewDay===i?'selected':''}>${n}</option>`).join('')}</select><input type="time" data-p="reviewTime" value="${prefs.reviewTime}"></div>
      <label>End of day</label><div class="row"><label class="hint"><input type="checkbox" data-p="eod" ${prefs.eod?'checked':''}> prompt at</label><input type="time" data-p="eodTime" value="${prefs.eodTime}"></div>
      <div class="full warnbox">Reminders fire while this calendar is open in a browser tab (it can be in the background). Install it as an app (General tab) to keep it running, and export a .ics file to your phone for alerts when it is closed. Missed reminders are shown the next time you open it.</div></form>`;
    if(setTab==='calendars') body=`<div class="list-edit">${data.calendars.map((c,i)=>`<div class="li" data-ci="${i}"><input type="color" data-c="color" value="${esc(c.color)}" ${ro} aria-label="Colour"><input type="text" data-c="name" value="${esc(c.name)}" ${ro} aria-label="Name"><label class="hint"><input type="checkbox" data-c="private" ${c.private?'checked':''} ${ro||(c.id==='work'?'disabled':'')}> private</label>${editable&&!['work','personal'].includes(c.id)?`<button class="ghost danger" data-cdel="${i}">🗑</button>`:''}</div>`).join('')}
      ${editable?'<button data-cadd style="align-self:flex-start">＋ Add a calendar (e.g. Family)</button>':''}</div>
      <div class="rsec">Privacy of Personal items</div>
      <p class="hint">${data.private?(privKey?'🔓 Personal items are encrypted and unlocked on this device.':'🔒 Personal items are encrypted and locked on this device.'):'No passphrase set yet. Personal items need one.'}</p>
      <div class="row" style="display:flex;gap:8px;flex-wrap:wrap">${!data.private&&editable?'<button data-pset>Set passphrase…</button>':''}${data.private&&privLocked?'<button data-punl>Unlock…</button>':''}${data.private&&privKey&&editable?'<button data-pchg>Change passphrase…</button><button data-plock>Lock on this device</button>':''}</div>
      <label class="hint" style="display:block;margin-top:10px"><input type="checkbox" data-showbusy ${s.showBusy?'checked':''} ${ro}> Viewers see Personal time as “Busy” blocks (no titles). Untick to hide it completely.</label>
      <table class="rt" style="margin-top:12px"><thead><tr><th></th><th>Editor</th><th>Viewer</th><th>Anyone with the passphrase</th></tr></thead><tbody>
        <tr><td>Status, location, flights, Work items</td><td>see + edit</td><td>see</td><td>—</td></tr><tr><td>Personal / private items</td><td>see + edit when unlocked</td><td>${s.showBusy?'“Busy” only':'hidden'}</td><td>see (edit if editor)</td></tr><tr><td>Accounts</td><td>manage</td><td>—</td><td>—</td></tr><tr><td>Reminders</td><td>own, per device</td><td>own, per device</td><td>—</td></tr></tbody></table>`;
    if(setTab==='templates'){ body='<div class="hint">Templates create several events, tasks and flights at once from a start date.</div><button data-otpl style="margin-top:10px">Open templates…</button>'; }
    if(setTab==='trash') body=data.trash.length?`<table class="rt"><thead><tr><th>Deleted</th><th>Type</th><th>Title</th><th></th></tr></thead><tbody>${[...data.trash].reverse().map(x=>`<tr><td class="num">${esc(fmtWhen(x.deletedAt))}</td><td>${esc(x.kind)}</td><td>${esc(x.item.title||x.item.name)}</td><td>${editable?`<button data-rest="${x.id}">Restore</button>`:''}</td></tr>`).join('')}</tbody></table>${editable?'<button data-empty class="danger" style="margin-top:10px">Empty trash</button>':''}`:'<div class="rempty">Trash is empty. Deleted items stay here for 30 days.</div>';
    if(setTab==='data') body=`<div class="list-edit">
      <div class="li"><button data-x="icsexp">📤 Export .ics…</button>${editable?'<button data-x="icsimp">📥 Import .ics…</button><button data-x="csvimp">📥 Import tasks from CSV…</button>':''}</div>
      <div class="li"><button data-x="backup">⬇ Export backup (.json)</button>${editable?'<button data-x="restore">⬆ Restore / import backup…</button>':''}</div>
      <div class="rsec">Archive</div><p class="hint">Saving is limited to 3 MB on the server. Move past events and finished tasks older than a number of months to a file. Open an archive file to include it in reports and search for this session.</p>
      <div class="li">${editable?`<input type="number" data-am value="${s.archiveMonths}" min="1" style="width:80px"> months <button data-x="archive">Archive now…</button>`:''}<button data-x="openarch">Open archive file…</button>${archive?`<span class="tag">Open: ${esc(archive.name)}</span>`:''}</div>
      <p class="hint">Current size: about ${Math.round(JSON.stringify(data).length/1024)} KB of 3,000 KB.</p></div>`;
    dd.querySelector('.box').innerHTML=`<div class="dhd"><h2>⚙ Settings</h2><button class="ghost" data-close>✕</button></div><div class="tabs">${tabs.map(([k,l])=>`<button data-st="${k}" class="${setTab===k?'on':''}">${l}</button>`).join('')}</div>${body}<div class="dacts"><span class="sp"></span><button class="primary" data-close>Done</button></div>`;
    const B=dd.querySelector('.box');
    $$('[data-close]',B).forEach(b=>b.onclick=()=>closeDialog(dd));
    $$('[data-st]',B).forEach(b=>b.onclick=()=>{ setTab=b.dataset.st; draw(); });
    // shared settings (editor)
    $$('[data-sf] [name]',B).forEach(x=>x.onchange=()=>{ if(!editable) return; const n=x.name; s[n]= x.type==='checkbox'?x.checked : x.type==='number'?+x.value : x.value; changed(); renderAll(); });
    $$('[data-wd]',B).forEach(b=>b.onclick=()=>{ if(!editable) return; const i=+b.dataset.wd; s.workDays=s.workDays.includes(i)?s.workDays.filter(x=>x!==i):[...s.workDays,i].sort(); changed(); draw(); renderAll(); });
    // device prefs
    $$('[data-p]',B).forEach(x=>x.onchange=x.oninput=()=>{ const n=x.dataset.p; prefs[n]= x.type==='checkbox'?x.checked : x.type==='range'?+x.value : x.value; savePrefs(); if(n==='offlineCopy'){ if(x.checked) cacheOffline(); else clearOffline(); } if(x.type==='range') render(); });
    const pm=$('[data-perm]',B); if(pm) pm.onclick=()=>askDesktopPermission().then(draw);
    const ch=$('[data-chime]',B); if(ch) ch.onclick=chime;
    $$('[data-edrem]',B).forEach(b=>b.onclick=()=>{ const n=b.dataset.edrem; const list=clone(s[n]).map(r=>{ delete r.anchor; return r; }); const ed=openDialog(`<div class="dhd"><h2>Default reminders</h2></div><div id="dr"></div><div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" data-ok>Save</button></div>`,{size:'narrow'});
      const rr=()=>{ $('#dr',ed).innerHTML=remFormHTML(list); bindRems($('#dr',ed),list,null,rr); }; rr(); $('[data-ok]',ed).onclick=()=>{ s[n]=list; changed(); closeDialog(ed); draw(); }; });
    // calendars
    $$('[data-ci]',B).forEach(li=>{ const c=data.calendars[+li.dataset.ci]; $$('[data-c]',li).forEach(x=>x.onchange=()=>{ if(!editable) return; const f=x.dataset.c;
      if(f==='private' && x.checked && !privKey){ x.checked=false; return ensurePrivReadySync(); }
      if(f==='private' && !x.checked && privLocked){ x.checked=true; return openUnlock(); }
      c[f]=x.type==='checkbox'?x.checked:x.value; touch(c); changed(); renderAll(); }); });
    const ca=$('[data-cadd]',B); if(ca) ca.onclick=async()=>{ const n=await promptDlg('New calendar','Name',''); if(!n) return; data.calendars.push({id:uid('cal'),name:n,color:PALETTE[data.calendars.length%PALETTE.length],private:false,order:data.calendars.length}); changed(); draw(); renderAll(); };
    $$('[data-cdel]',B).forEach(b=>b.onclick=async()=>{ const c=data.calendars[+b.dataset.cdel]; const n=[...data.events,...data.tasks,...data.projects].filter(x=>x.calendarId===c.id).length; if(!await confirmDlg(`Remove calendar “${c.name}”? Its ${n} items move to Work.`,[['Remove','y','danger'],['Cancel',null]])) return; for(const x of [...data.events,...data.tasks,...data.projects]) if(x.calendarId===c.id){ x.calendarId='work'; touch(x); } data.calendars.splice(+b.dataset.cdel,1); changed(); draw(); renderAll(); });
    const a=(s2,fn)=>{ const b=$(s2,B); if(b) b.onclick=fn; };
    a('[data-pset]',openPrivSetup); a('[data-punl]',openUnlock); a('[data-pchg]',changePassphrase); a('[data-plock]',async()=>{ if(dirty) await saveNow(); await lockPriv(); renderAll(); draw(); });
    const sb=$('[data-showbusy]',B); if(sb) sb.onchange=()=>{ s.showBusy=sb.checked; changed(); draw(); };
    a('[data-otpl]',openTemplates);
    $$('[data-rest]',B).forEach(b=>b.onclick=()=>{ restoreFromTrash(b.dataset.rest); draw(); });
    a('[data-empty]',async()=>{ if(!await confirmDlg('Permanently delete everything in the trash?',[['Empty trash','y','danger'],['Cancel',null]])) return; data.trash=[]; changed(); draw(); });
    $$('[data-x]',B).forEach(b=>b.onclick=()=>{ const x=b.dataset.x;
      if(x==='icsexp') openIcsExport(); if(x==='icsimp') pickFile('.ics,text/calendar',importIcsFile); if(x==='csvimp') pickFile('.csv,text/csv,.txt',importCsvFile);
      if(x==='backup') exportBackup(); if(x==='restore') pickFile('.json,.js,application/json',importBackupFile);
      if(x==='archive'){ const m=+$('[data-am]',B).value||12; s.archiveMonths=m; archiveOld(m); } if(x==='openarch') pickFile('.json',f=>openArchiveFile(f).then(draw)); });
  };
  const dd=openDialog('',{size:'mid',refresh:()=>{ if(!$('input:focus,select:focus',dd)) draw(); }}); draw();
}
function remPreview(list){ return `<div class="hint">${list.map(r=>esc((OFFSETS.find(o=>o[0]===+r.offsetMin)||[0,fmtDur(r.offsetMin)+' before'])[1])+(r.channel!=='popup'?' + desktop':'')).join(', ')||'none'}</div>`; }
let _installEvt=null; addEventListener('beforeinstallprompt',e=>{ e.preventDefault(); _installEvt=e; });
function installHint(){
  if(matchMedia('(display-mode: standalone)').matches) return 'Installed ✓';
  if(_installEvt) { setTimeout(()=>{ const b=$('#instBtn'); if(b) b.onclick=async()=>{ _installEvt.prompt(); await _installEvt.userChoice; _installEvt=null; }; },0); return '<button type="button" id="instBtn">Install as an app</button> so it keeps running in its own window.'; }
  return 'Use your browser’s “Install app” / “Add to Home Screen” option to run it in its own window (Chrome, Edge, Safari on iPhone).';
}

/* ---------- user accounts & history (unchanged from v1) ---------- */
async function openAccounts(){
  let rows;
  try{ rows = (await api('users')).users.map(a=>({orig:a.user, user:a.user, role:a.role, pass:'', must:!!a.must})); }
  catch(e){ if(e.status!==401) alert(e.message); return; }
  let d;
  const draw = ()=>{
    const html=`<div class="dhd"><h2>👥 User accounts</h2><button class="ghost" data-close>✕</button></div>
      <p class="hint"><b>Editors</b> can read and change the calendar; <b>viewers</b> can only read it. Leave the password empty to keep the current one. New accounts need a password (at least 6 characters). Tick <b>Must change</b> to make the person choose their own password at the next sign-in, for example after you give them a temporary one. Anyone whose password you change, or whose account you remove, is signed out.</p>
      <p class="hint">Locked out? Put a file named <code>reset-passwords.txt</code> in the server’s <code>data</code> folder with a line <code>username temporary-password</code> (or <code>*</code> for every account), then open the calendar. See HOW TO UPDATE.txt.</p>
      <table class="acc"><thead><tr><th>Username</th><th style="width:120px">Role</th><th>New password</th><th title="The person must choose a new password the next time they sign in">Must change</th><th></th></tr></thead><tbody>
      ${rows.map((a,i)=>`<tr data-i="${i}">
        <td><input type="text" data-f="user" value="${esc(a.user)}" autocomplete="off" autocapitalize="off" spellcheck="false" aria-label="Username"></td>
        <td><select data-f="role" aria-label="Role"><option value="editor" ${a.role==='editor'?'selected':''}>Editor</option><option value="viewer" ${a.role==='viewer'?'selected':''}>Viewer</option></select></td>
        <td><input type="password" data-f="pass" value="${esc(a.pass)}" autocomplete="new-password" placeholder="${a.orig?'unchanged':'password'}" aria-label="New password"></td>
        <td style="text-align:center">${a.orig&&a.orig.toLowerCase()===session.user.toLowerCase()?'<span class="hint">you</span>':`<input type="checkbox" data-must="${i}" ${a.must?'checked':''} aria-label="Must choose a new password at next sign-in">`}</td>
        <td><button class="ghost" data-del="${i}" title="Remove account">🗑</button></td></tr>`).join('')}
      </tbody></table>
      <button id="accAdd">＋ Add account</button>
      <label class="hint" style="display:flex;gap:6px;align-items:center;margin-top:10px"><input type="checkbox" id="accShow"> Show passwords</label>
      <div class="lerr" id="accErr" style="margin-top:8px"></div>
      <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" id="accSave">Save</button></div>`;
    if(!d) d=openDialog(html,{size:'mid'}); else { d.querySelector('.box').innerHTML=html; $$('[data-close]',d).forEach(b=>b.onclick=()=>closeDialog(d)); }
    $$('tr[data-i]',d).forEach(tr=>$$('[data-f]',tr).forEach(inp=>inp.oninput=inp.onchange=()=>{ rows[+tr.dataset.i][inp.dataset.f]=inp.value; }));
    $$('[data-must]',d).forEach(c=>c.onchange=()=>{ rows[+c.dataset.must].must=c.checked; });
    $$('[data-del]',d).forEach(b=>b.onclick=()=>{ rows.splice(+b.dataset.del,1); draw(); });
    $('#accAdd',d).onclick=()=>{ rows.push({user:'', role:'viewer', pass:'', must:true}); draw(); };
    $('#accShow',d).onchange=e=>$$('input[data-f=pass]',d).forEach(i=>i.type=e.target.checked?'text':'password');
    $('#accSave',d).onclick=()=>saveAccounts(rows,d);
  };
  draw();
}
/* change own password (any account) */
function openChangePassword(){
  const d=openDialog(`<div class="dhd"><h2>🔑 Change my password</h2><button class="ghost" data-close>✕</button></div>
    <form class="frm" autocomplete="on"><input type="text" autocomplete="username" value="${esc(session.user)}" hidden>
      <label>Current password</label><input type="password" id="cpOld" autocomplete="current-password" required>
      <label>New password</label><input type="password" id="cpNew" autocomplete="new-password" minlength="6" required>
      <label>Repeat</label><input type="password" id="cpNew2" autocomplete="new-password" minlength="6" required>
      <div class="full hint">At least 6 characters. You stay signed in here; other devices signed in to this account are signed out.</div>
      <div class="full lerr" id="cpErr"></div>
      <div class="full dacts" style="margin-top:4px"><span class="sp"></span><button type="button" data-close>Cancel</button><button class="primary" type="submit">Change password</button></div></form>`,{size:'narrow',focus:'#cpOld'});
  $('form',d).onsubmit=async e=>{ e.preventDefault();
    const o=$('#cpOld',d).value, n=$('#cpNew',d).value, n2=$('#cpNew2',d).value, err=$('#cpErr',d);
    if(n!==n2){ err.textContent='The two new passwords do not match.'; return; }
    const b=$('[type=submit]',d); b.disabled=true;
    try{ await api('password',{old:o,new:n}); closeDialog(d); toast('Your password was changed.'); }
    catch(x){ if(x.status!==401) err.textContent=x.message; b.disabled=false; } };
}
async function saveAccounts(rows,d){
  const err=t=>{ $('#accErr',d).textContent=t; };
  const mine=rows.find(r=>r.orig && r.orig.toLowerCase()===session.user.toLowerCase());
  if((!mine || mine.role!=='editor') && !confirm('You are removing your own editor access. You will not be able to change the calendar any more. Continue?')) return;
  const btn=$('#accSave',d); btn.disabled=true; btn.textContent='Saving…';
  try{
    const j = await api('users', {users:rows.map(r=>({orig:r.orig, user:(r.user||'').trim(), role:r.role, pass:r.pass||'', must:!!r.must}))});
    closeDialog(d);
    if(!j.me || !j.me.user){ alert('Accounts saved. You no longer have an account, so you will be signed out.'); location.reload(); return; }
    session={user:j.me.user, role:j.me.role}; editable = j.me.role==='editor';
    afterModeChange(); toast('Accounts saved.');
  }catch(e){ if(e.status!==401) err(e.message); btn.disabled=false; btn.textContent='Save'; }
}
async function openHistory(){
  let list;
  try{ list = (await api('history')).backups; }catch(e){ if(e.status!==401) alert(e.message); return; }
  const d=openDialog(`<div class="dhd"><h2>🕘 Earlier versions</h2><button class="ghost" data-close>✕</button></div>
    <p class="hint">The server keeps a copy of the calendar before changes are saved (at most one every 10 minutes while you edit). Restoring a version replaces the current calendar; the current one is kept as a version too, so you can undo.</p>
    <p class="hint">Current version: saved ${esc(fmtWhen(lastSaved.saved))||'—'}${lastSaved.by?' by <b>'+esc(lastSaved.by)+'</b>':''}.</p>
    ${list.length ? `<table class="rt"><thead><tr><th>Saved</th><th>By</th><th>Days with entries</th><th></th></tr></thead><tbody>
      ${list.map(b=>`<tr><td class="num">${esc(fmtWhen(b.saved))}</td><td>${esc(b.by)}</td><td class="num">${b.days}</td><td style="text-align:right"><button data-restore="${esc(b.id)}">Restore</button></td></tr>`).join('')}
    </tbody></table>` : '<div class="rempty">No earlier versions yet. They appear here after the calendar is changed.</div>'}
    <div class="dacts"><span class="sp"></span><button data-close>Close</button></div>`,{size:'mid'});
  $$('[data-restore]', d).forEach(b=>b.onclick=async()=>{
    if(!confirm('Replace the current calendar with this earlier version?')) return;
    try{
      clearTimeout(saveTimer); saveTimer=null; dirty=false; conflict=null;
      await api('restore', {id:b.dataset.restore});
      await loadData(true); closeDialog(d); afterModeChange();
    }catch(e){ if(e.status!==401) alert(e.message); }
  });
}

/* ============================================================
   Quick add (natural language, English + Greek)
   #project  @location  !calendar  ~estimate  p1–p4  *repeat
   "by"/"μέχρι"/"έως" = due date of a task
   ============================================================ */
const fold1 = c => { const f=c.normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase(); return f.length===1?f:(f[0]||' '); };
const WB = p => `(?<![\\p{L}\\p{N}])(?:${p})(?![\\p{L}\\p{N}])`;
const DAY_WORDS = [
  ['monday|mon|δευτερα|δευ'], ['tuesday|tues|tue|τριτη|τρι'], ['wednesday|wed|τεταρτη|τετ'], ['thursday|thurs|thu|πεμπτη|πεμ'],
  ['friday|fri|παρασκευη|παρ'], ['saturday|sat|σαββατο|σαβ'], ['sunday|sun|κυριακη|κυρ']];
const MONTH_WORDS = ['jan|january|ιαν','feb|february|φεβ','mar|march|μαρ','apr|april|απρ','may|μαι|μαΐ','jun|june|ιουν','jul|july|ιουλ','aug|august|αυγ','sep|sept|september|σεπ','oct|october|οκτ','nov|november|νοε','dec|december|δεκ'];
const MONTH_RE = MONTH_WORDS.map(m=>WB(m.split('|').map(x=>/[a-z]/.test(x)?x:x+'[\\p{L}]*').join('|')));

function parseQuick(text){
  let s=text.normalize('NFC'); let f=[...s].map(fold1).join('');
  const r={title:'', type:null, date:null, dateFromBy:false, start:null, end:null, projectId:null, calendarId:null, location:'', priority:null, estimateMin:null, rrule:null, tags:[], notes:[]};
  const blank=(a,b)=>{ s=s.slice(0,a)+' '.repeat(b-a)+s.slice(b); f=f.slice(0,a)+' '.repeat(b-a)+f.slice(b); };
  const take=(re, fn)=>{ re.lastIndex=0; let m; const found=[]; while((m=re.exec(f))){ found.push(m); if(!re.global) break; }
    for(const m2 of found.reverse()){ const orig=s.slice(m2.index, m2.index+m2[0].length); if(fn(m2, orig)!==false) blank(m2.index, m2.index+m2[0].length); } };
  const today=TODAY();
  // explicit type
  take(/^\s*(task|todo|εργασια)\s*:?\s+/iu, ()=>{ r.type='task'; });
  take(/^\s*(event|γεγονος|ραντεβου)\s*:\s*/iu, ()=>{ r.type='event'; });
  // quoted or single-word project / location / calendar
  take(/#"([^"]+)"|#([\p{L}\p{N}_\-.]+)/gu, (m,o)=>{ const name=(m[1]||m[2]); const q=name.replace(/[\s_\-]/g,''); const raw=o.replace(/^#"?|"$/g,'');
    const p=data.projects.find(p=>[...p.name].map(fold1).join('').replace(/[\s_\-]/g,'')===q) || data.projects.find(p=>[...p.name].map(fold1).join('').replace(/[\s_\-]/g,'').startsWith(q));
    if(p){ r.projectId=p.id; if(!r.calendarId) r.calendarId=p.calendarId; } else r.tags.push(raw.replace(/^"|"$/g,'')); });
  take(/@"([^"]+)"|@([\p{L}\p{N}_\-.]+)/gu, (m,o)=>{ r.location=(o.match(/@"([^"]+)"/)||[])[1]||o.slice(1); });
  take(/!([\p{L}]+)/gu, m=>{ const w=m[1]; const c=data.calendars.find(c=>[...c.name].map(fold1).join('').startsWith(w)) || (/^(δουλ|εργ|work)/.test(w)?cal('work'):/^(προσ|pers|priv)/.test(w)?data.calendars.find(c=>c.id==='personal'):null); if(!c) return false; r.calendarId=c.id; });
  take(/~\s*(\d+(?:[.,]\d+)?)\s*(h|ω|ωρ[\p{L}]*|hours?)?\s*(\d+)?\s*(m|min|λ|λεπτ[\p{L}]*)?/gu, m=>{ const n=parseFloat(m[1].replace(',','.')); r.estimateMin = m[2] ? Math.round(n*60)+(+m[3]||0) : (m[4]?Math.round(n):Math.round(n<=12&&!m[4]?n*60:n)); });
  take(new RegExp(WB('p([1-4])'),'gu'), m=>{ r.priority=+m[1]; });
  take(/\*([\p{L}]+)/gu, m=>{ const w=m[1];
    if(/^(daily|καθημεριν)/.test(w)) r.rrule={freq:'daily',interval:1};
    else if(/^(weekdays|εργασιμ)/.test(w)) r.rrule={freq:'weekly',interval:1,byDay:[0,1,2,3,4]};
    else if(/^(weekly|εβδομαδιαι|καθε εβδομαδα)/.test(w)) r.rrule={freq:'weekly',interval:1};
    else if(/^(biweekly)/.test(w)) r.rrule={freq:'weekly',interval:2};
    else if(/^(monthly|μηνιαι)/.test(w)) r.rrule={freq:'monthly',interval:1};
    else if(/^(yearly|annual|ετησι)/.test(w)) r.rrule={freq:'yearly',interval:1};
    else return false; });
  // "by" (task due) marker — remembered, removed with its date below
  const byRe = WB('by|due|until|μεχρι|εως|ως|προθεσμια');
  // dates
  const setDate=(k)=>{ if(!r.date) r.date=k; };
  const dateTok = (re, fn) => take(new RegExp('(?:('+byRe+')\\s+)?(?:(?:on|την|τη|στις)\\s+)?'+re,'giu'), (m,o)=>{ const k=fn(m); if(!k) return false; if(r.date) return false; setDate(k); if(m[1]) r.dateFromBy=true; });
  dateTok('(\\d{4})-(\\d{1,2})-(\\d{1,2})', m=>`${m[2]}-${m[3].padStart(2,'0')}-${m[4].padStart(2,'0')}`);
  dateTok('(?<![\\d:~])(\\d{1,2})[\\/.](\\d{1,2})(?:[\\/.](\\d{2,4}))?(?![\\d:])', m=>{ const d=+m[2], mo=+m[3]; if(mo<1||mo>12||d<1||d>31) return null; let y=m[4]?(m[4].length===2?2000+(+m[4]):+m[4]):today.getFullYear(); let k=`${y}-${String(mo).padStart(2,'0')}-${String(d).padStart(2,'0')}`; if(!m[4] && k<key(today)) k=`${y+1}-${k.slice(5)}`; return k; });
  for(let i=0;i<12;i++){
    dateTok(`(\\d{1,2})\\s+(?:${MONTH_RE[i]})(?:\\s+(\\d{4}))?`, m=>{ let y=m[3]?+m[3]:today.getFullYear(); let k=`${y}-${String(i+1).padStart(2,'0')}-${String(+m[2]).padStart(2,'0')}`; if(!m[3]&&k<key(today)) k=`${y+1}-${k.slice(5)}`; return k; });
    dateTok(`(?:${MONTH_RE[i]})\\s+(\\d{1,2})(?![:\\d])`, m=>{ let y=today.getFullYear(); let k=`${y}-${String(i+1).padStart(2,'0')}-${String(+m[2]).padStart(2,'0')}`; if(k<key(today)) k=`${y+1}-${k.slice(5)}`; return k; });
  }
  dateTok(WB('today|tonight|σημερα|αποψε'), ()=>key(today));
  dateTok(WB('day after tomorrow|μεθαυριο'), ()=>key(addDays(today,2)));
  dateTok(WB('tomorrow|tmrw|αυριο'), ()=>key(addDays(today,1)));
  dateTok('(?:in|σε)\\s+(\\d{1,3})\\s+(?:days?|μερες|ημερες|μερα)', m=>key(addDays(today,+m[2])));
  dateTok('(?:in|σε)\\s+(\\d{1,2})\\s+(?:weeks?|εβδομαδες|εβδομαδα)', m=>key(addDays(today,7*(+m[2]))));
  DAY_WORDS.forEach(([w],i)=>{
    dateTok('(next|this|επομενη|επομενο|αλλη|αυτη|αυτο)?\\s*'+WB(w), m=>{ let k=addDays(today,(i-dow(today)+7)%7); if(m[2] && /next|επομεν|αλλη/.test(m[2])) k=addDays(k,7); return key(k); });
  });
  // times
  const ampm=(h,suf)=>{ suf=(suf||'').replace(/\./g,''); if(/pm|μμ/.test(suf) && h<12) return h+12; if(/am|πμ/.test(suf) && h===12) return 0; return h; };
  take(/(?:(?:from|απο|στις|at)\s+)?(?<![\d\/.\-~])(\d{1,2})(?:[:.](\d{2}))?\s*(am|pm|h|π\.?μ\.?|μ\.?μ\.?)?\s*(?:-|–|to|μεχρι|εως)\s*(\d{1,2})(?:[:.](\d{2}))?\s*(am|pm|h|π\.?μ\.?|μ\.?μ\.?)?(?![\d\/])/iu, m=>{
    let h1=+m[1], h2=+m[4]; if(h1>24||h2>24) return false; const suf2=m[6]||'', suf1=m[3]||suf2;
    h1=ampm(h1,suf1); h2=ampm(h2,suf2);
    const a=h1*60+(+m[2]||0), b=h2*60+(+m[5]||0); if(b<=a) return false; r.start=a; r.end=b; });
  if(r.start===null) take(/(?:(at|στις|@)\s+)?(?<![\d\/.\-~])(\d{1,2})(?:([:.])(\d{2}))?\s*(am|pm|h|π\.?μ\.?|μ\.?μ\.?)?(?![\d\/\p{L}])/iu, m=>{
    if(!m[1] && !m[3] && !m[5]) return false; const h=ampm(+m[2],m[5]||''); if(h>23) return false; r.start=h*60+(+m[4]||0); });
  // leftover "by" right before a time (e.g. "by 17:00")
  take(new RegExp(byRe,'iu'), ()=>{ r.dateFromBy=true; });
  // title
  r.title=s.replace(/\s+/g,' ').trim().replace(/^(on|at|in|the|την|στις)\s+/i,'').replace(/\s+(on|at|in|for|στις|την)$/i,'').replace(/[,;:\-–]+$/,'').trim();
  // decide type
  if(!r.type){ r.type = (r.dateFromBy || r.estimateMin || r.priority) ? 'task' : (r.start!==null ? 'event' : 'task'); }
  if(r.type==='event' && !r.date) r.date=key(today);
  if(r.rrule && r.rrule.freq==='weekly' && !r.rrule.byDay && r.date) r.rrule.byDay=[dowKey(r.date)];
  if(r.rrule && r.type==='task' && !r.date) r.date=key(today);
  if(r.location && !r.calendarId) r.calendarId='work';
  if(!r.calendarId) r.calendarId='work';
  return r;
}
function quickToItem(q){
  const tz = ACTIVE_TZ;   // entered in the active time zone
  if(q.type==='event'){
    const allDay=q.start===null;
    const e=newEvent({title:q.title||'New event', calendarId:q.calendarId, projectId:q.projectId, location:q.location, tags:q.tags, rrule:q.rrule, allDay, tz});
    if(allDay){ e.start=q.date; e.end=q.date; delete e.tz; }
    else { e.start=q.date+'T'+minToHM(q.start); e.end=q.end!==null?q.date+'T'+minToHM(q.end):localAddMin(e.start,S().defaultEventMin||60); }
    if(q.priority||q.estimateMin) e.notes='';
    return e;
  }
  const t=newTask({title:q.title||'New task', calendarId:q.calendarId, projectId:q.projectId, tags:q.tags, priority:q.priority||3, estimateMin:q.estimateMin, rrule:q.rrule});
  if(q.date){ t.dueDate=q.date; if(q.rrule) t.rrStart=q.date; }
  if(q.start!==null && (q.dateFromBy || q.end===null)) t.dueTime=minToHM(q.start);
  else if(q.start!==null && q.end!==null){ const k=q.date||todayKey(); t.blocks=[{id:uid('b'), start:k+'T'+minToHM(q.start), end:k+'T'+minToHM(q.end), tz, done:false}]; if(!t.estimateMin) t.estimateMin=q.end-q.start; }
  if(q.location) t.notes='📍 '+q.location;
  return t;
}
let qaOverride=null;
function quickPreviewHTML(q){
  const p=proj(q.projectId), c=cal(q.calendarId);
  const when = q.type==='event'
    ? (q.date?fmtShort(fromKey(q.date)):'')+(q.start!==null?' '+minToHM(q.start)+(q.end!==null?'–'+minToHM(q.end):''):' (all day)')
    : (q.date?'due '+fmtShort(fromKey(q.date))+(q.start!==null&&(q.dateFromBy||q.end===null)?' '+minToHM(q.start):''):'no due date')+(q.start!==null&&q.end!==null&&!q.dateFromBy?' · block '+minToHM(q.start)+'–'+minToHM(q.end):'');
  return `<div><b>${esc(q.title||'(no title)')}</b></div><div class="qrow">
    <button type="button" class="tag k" data-qtype title="Switch between event and task">${q.type==='event'?'📅 Event':'☑ Task'} ⇄</button>
    <span class="tag">🕒 ${esc(when)}</span><span class="tag"><i class="dotc" style="background:${esc(c.color)}"></i>${esc(c.name)}</span>
    ${p?`<span class="tag">${esc(p.icon||'')} ${esc(p.name)}</span>`:''}${q.location?`<span class="tag">📍 ${esc(q.location)}</span>`:''}
    ${q.priority?`<span class="tag">P${q.priority}</span>`:''}${q.estimateMin?`<span class="tag">⏱ ${fmtDur(q.estimateMin)}</span>`:''}${q.rrule?`<span class="tag">🔁 ${esc(rrText(q.rrule))}</span>`:''}
    ${q.tags.map(t=>`<span class="tag">#${esc(t)}</span>`).join('')}</div>
    <div class="hint" style="margin-top:6px"><kbd>Enter</kbd> create · <kbd>Ctrl</kbd>+<kbd>Enter</kbd> open full editor · <kbd>Esc</kbd> cancel</div>`;
}
function bindQuickAdd(){
  const inp=$('#qa'), pv=$('#qaPrev');
  const parse=()=>{ const q=parseQuick(inp.value); if(qaOverride) q.type=qaOverride; if(q.type==='event'&&!q.date) q.date=todayKey(); return q; };
  const upd=()=>{
    if(!inp.value.trim() || !editable){ pv.classList.remove('open'); return; }
    pv.innerHTML=quickPreviewHTML(parse()); pv.classList.add('open');
    const tb=$('[data-qtype]',pv); tb.onmousedown=e=>{ e.preventDefault(); const q=parse(); qaOverride=q.type==='event'?'task':'event'; upd(); };
  };
  inp.addEventListener('input',()=>{ if(!inp.value.trim()) qaOverride=null; upd(); });
  inp.addEventListener('focus',upd);
  inp.addEventListener('blur',()=>setTimeout(()=>pv.classList.remove('open'),150));
  inp.addEventListener('keydown',e=>{
    if(e.key==='Escape'){ inp.value=''; qaOverride=null; pv.classList.remove('open'); inp.blur(); return; }
    if(e.key!=='Enter' || !inp.value.trim() || !editable) return;
    e.preventDefault();
    const it=quickToItem(parse());
    const full=e.ctrlKey||e.metaKey;
    inp.value=''; qaOverride=null; pv.classList.remove('open');
    if(full) return it.type==='event'?openEventEditor(null,it):openTaskEditor(null,it);
    if(!saveNewItem(it)) return;
    toast(`${it.type==='event'?'📅':'☑'} Created “${it.title}”`,{actions:[['Undo',undo],['Edit',()=>it.type==='event'?openEventEditor(it.id):openTaskEditor(it.id)]]});
  });
}

/* ============================================================
   Search palette, keyboard shortcuts, top bar and menus
   ============================================================ */
function openSearch(initial){
  let act=0, results=[];
  const d=openDialog(`<div class="pal"><input type="search" id="palQ" placeholder="Search events, tasks, projects, days, flights…   filters: project: tag: is:overdue is:done type:task before:2026-12-01 after:" value="${esc(initial||'')}" aria-label="Search"><div class="res" id="palR" role="listbox"></div></div>`,{size:'mid',focus:'#palQ'});
  const q=$('#palQ',d), out=$('#palR',d);
  const run=()=>{
    let text=q.value.trim(); const fl={};
    text=text.replace(/(project|tag|is|type|before|after|cal):("[^"]+"|\S+)/gi,(m,k,v)=>{ fl[k.toLowerCase()]=v.replace(/"/g,'').toLowerCase(); return ''; }).trim().toLowerCase();
    const words=text.split(/\s+/).filter(Boolean).map(w=>[...w].map(fold1).join(''));
    const hit=s=>{ const f=[...(s||'')].map(fold1).join(''); return words.every(w=>f.includes(w)); };
    const pmatch=it=>!fl.project || ((proj(it.projectId)||{name:'inbox'}).name.toLowerCase().includes(fl.project));
    const tmatch=it=>!fl.tag || (it.tags||[]).some(t=>t.toLowerCase().includes(fl.tag));
    const cmatch=it=>!fl.cal || cal(it.calendarId).name.toLowerCase().startsWith(fl.cal);
    const dkey=it=>it.type==='event'?it.start.slice(0,10):(it.dueDate||(it.blocks[0]&&it.blocks[0].start.slice(0,10))||'');
    const dmatch=it=>{ const k=dkey(it); return (!fl.before || (k && k<fl.before)) && (!fl.after || (k && k>fl.after)); };
    const ismatch=it=>!fl.is || (fl.is==='overdue'?it.type==='task'&&isOverdue(it):fl.is==='done'?it.type==='task'&&!isOpen(it):fl.is==='open'?it.type==='task'&&isOpen(it):fl.is==='recurring'?!!it.rrule:fl.is==='private'?isPrivate(it):true);
    const okType=t=>!fl.type || fl.type.startsWith(t.slice(0,2));
    const pool={events:[...data.events,...(archive?archive.events.map(x=>({...x,_arch:true})):[])], tasks:[...data.tasks,...(archive?archive.tasks.map(x=>({...x,_arch:true})):[])]};
    results=[];
    if(!words.length && !Object.keys(fl).length){ out.innerHTML='<div class="hint" style="padding:8px">Type to search. Try <kbd>is:overdue</kbd>, <kbd>project:council</kbd>, <kbd>tag:budget</kbd>, <kbd>type:event after:2026-11-01</kbd>.</div>'; return; }
    const filt2=it=>pmatch(it)&&tmatch(it)&&cmatch(it)&&dmatch(it)&&ismatch(it);
    if(okType('events')) for(const e of pool.events) if(filt2(e) && hit([e.title,e.notes,e.location,e.tags.join(' '),e.attendees.join(' '),Object.values(e.exceptions).map(x=>x.title||'').join(' ')].join(' '))) results.push({g:'Events', label:(e.rrule?'🔁 ':'')+e.title, d:(e.allDay?fmtShort(fromKey(e.start.slice(0,10))):fmtShort(fromKey(e.start.slice(0,10)))+' '+e.start.slice(11,16))+(e._arch?' · archive':''), c:itemColor(e), go:()=>{ if(e._arch) return; openEvent(e.id); }, k:e.start});
    if(okType('tasks')) for(const t of pool.tasks) if(filt2(t) && hit([t.title,t.notes,t.tags.join(' '),t.checklist.map(c=>c.text).join(' ')].join(' '))) results.push({g:'Tasks', label:(isOpen(t)?'☐ ':'✓ ')+t.title, d:(t.dueDate?'due '+fmtShort(fromKey(t.dueDate)):'')+(t._arch?' · archive':''), c:itemColor(t), go:()=>{ if(t._arch) return; openTaskEditor(t.id); }, k:t.dueDate||''});
    if(!fl.type && !fl.is && !fl.tag) for(const p of data.projects) if(hit(p.name+' '+p.description)) results.push({g:'Projects', label:(p.icon||'')+' '+p.name, d:PROJ_STATUS[p.status], c:p.color, go:()=>openProjectPage(p.id), k:''});
    if(!Object.keys(fl).length) for(const [k,e] of Object.entries(data.days)){
      if(e.comment && hit(e.comment)) results.push({g:'Day comments', label:e.comment.slice(0,80), d:fmtShort(fromKey(k))+' '+fromKey(k).getFullYear(), go:()=>{ closeDialog(d); cursor=fromKey(k); render(); openDrawer(k); }, k});
      for(const f of e.flights||[]) if(hit(`${f.from} ${f.to} ${code(f.from)} ${code(f.to)} ${f.note||''} flight πτήση`)) results.push({g:'Flights', label:`✈ ${f.from} → ${f.to}`, d:fmtShort(fromKey(k))+' '+fromKey(k).getFullYear(), go:()=>{ closeDialog(d); cursor=fromKey(k); render(); openDrawer(k); }, k});
    }
    const order=['Events','Tasks','Projects','Flights','Day comments'];
    results.sort((a,b)=>order.indexOf(a.g)-order.indexOf(b.g)||String(b.k).localeCompare(String(a.k)));
    results=results.slice(0,120); act=0;
    let g='', html='';
    results.forEach((r,i)=>{ if(r.g!==g){ g=r.g; html+=`<div class="grp">${esc(g)} (${results.filter(x=>x.g===g).length})</div>`; } html+=`<div class="r ${i===act?'act':''}" data-r="${i}" role="option"><i class="dotc" style="background:${esc(r.c||'var(--line2)')}"></i><span>${esc(r.label)}</span><span class="d">${esc(r.d||'')}</span></div>`; });
    out.innerHTML=html||'<div class="rempty">Nothing found.</div>';
    $$('[data-r]',out).forEach(el=>el.onclick=()=>{ const r=results[+el.dataset.r]; closeDialog(d); r.go(); });
  };
  q.oninput=run;
  q.onkeydown=e=>{
    if(e.key==='ArrowDown'||e.key==='ArrowUp'){ e.preventDefault(); act=clamp(act+(e.key==='ArrowDown'?1:-1),0,results.length-1); $$('[data-r]',out).forEach((el,i)=>{ el.classList.toggle('act',i===act); if(i===act) el.scrollIntoView({block:'nearest'}); }); }
    if(e.key==='Enter' && results[act]){ e.preventDefault(); const r=results[act]; closeDialog(d); r.go(); }
  };
  run();
}

function openHelp(){
  const k=[['N','New event or task (menu)'],['Q','Quick add'],['/ or Ctrl+K','Search'],['T','Today'],['D 3 W M A B','Day, 3 days, Week, Month, Agenda, Board'],['← →','Previous / next (or day, when a day is open)'],['E','Edit the selected item'],['Space','Complete the selected task / block'],['Delete','Delete the selected item'],['Ctrl+Z / Ctrl+Shift+Z','Undo / redo (last 50 changes)'],['P','Plan my day'],['Esc','Close'],['?','This list'],
    ['In the time grid','Tab to the grid, arrows move by 30 min / day, Enter creates an item. Drag empty time to create, drag items to move, drag the lower edge to resize, Alt for 5-minute steps.']];
  openDialog(`<div class="dhd"><h2>⌨ Keyboard shortcuts</h2><button class="ghost" data-close>✕</button></div><div class="keys">${k.map(([a,b])=>`<span><kbd>${esc(a)}</kbd></span><span>${esc(b)}</span>`).join('')}</div>
    <div class="rsec">Quick add</div><div class="hint">Examples: <kbd>Meeting WG tue 10-12:30 #Council @Brussels</kbd> · <kbd>Draft text by fri 17:00 ~4h p1</kbd> · <kbd>Σύσκεψη αύριο 11:00</kbd> · <kbd>Pay rent 1/12 *monthly !personal</kbd>.<br># project, @ location, ! calendar, ~ estimate, p1–p4 priority, * repeat (daily, weekdays, weekly, monthly, yearly), “by” makes it a task with a due date.</div>
    <div class="dacts"><span class="sp"></span><button class="primary" data-close>OK</button></div>`,{size:'mid'});
}

/* ---------- top bar ---------- */
function bindTop(){
  $('#prev').onclick=()=>move(-1);
  $('#next').onclick=()=>move(1);
  $('#today').onclick=()=>{ cursor=TODAY(); miniCursor=null; render(); renderSide(); };
  $$('#viewSeg button').forEach(b=>b.onclick=()=>setView(b.dataset.view));
  $('#scrim').onclick=closeDrawer;
  $('#searchBtn').onclick=()=>openSearch();
  $('#bellBtn').onclick=openBell;
  const small=()=>innerWidth<1024;
  $('#sideBtn').onclick=()=>{ if(small()){ document.body.classList.toggle('showside'); document.body.classList.remove('showtasks'); } else { prefs.sidePanel=!prefs.sidePanel; savePrefs(); render(); } };
  $('#tasksBtn').onclick=()=>{ if(small()){ document.body.classList.toggle('showtasks'); document.body.classList.remove('showside'); } else { prefs.tasksPanel=!prefs.tasksPanel; savePrefs(); render(); } };
  const menus=[['#menuBtn','#menu'],['#newBtn','#newMenu'],['#repBtn','#repMenu'],['#tzBtn','#tzMenu']];
  for(const [b,m] of menus) $(b).onclick=e=>{ e.stopPropagation(); for(const [,o] of menus) if(o!==m) $(o).classList.remove('open'); $(m).classList.toggle('open'); };
  document.addEventListener('click',e=>{ for(const [,m] of menus) if(!e.target.closest(m)) $(m).classList.remove('open'); $$('details.dropdown[open]').forEach(dt=>{ if(!dt.contains(e.target)) dt.open=false; }); });
  $('#tzBtn').addEventListener('click',renderTzMenu,true);
  $('#tzMenu').onclick=async e=>{ const b=e.target.closest('[data-tz]'); if(!b) return; $('#tzMenu').classList.remove('open');
    let tz=b.dataset.tz;
    if(tz==='?'){ tz=await promptDlg('Other time zone','IANA time zone name, e.g. Europe/Madrid, America/New_York, Asia/Tokyo', ACTIVE_TZ,{validate:v=>validTz(v.trim())?null:'Unknown time zone name.'}); if(!tz) return; tz=tz.trim(); }
    setActiveTz(tz); };
  $('#newMenu').onclick=e=>{ const b=e.target.closest('[data-new]'); if(!b) return; $('#newMenu').classList.remove('open'); newThing(b.dataset.new); };
  $('#repMenu').onclick=e=>{ const b=e.target.closest('[data-rep]'); if(!b) return; $('#repMenu').classList.remove('open'); openReports(b.dataset.rep); };
  $('#menu').onclick=e=>{
    const b=e.target.closest('[data-act]'); if(!b||b.disabled) return;
    $('#menu').classList.remove('open');
    const a=b.dataset.act;
    if(a==='settings') openSettings();
    if(a==='refresh') reloadData(true);
    if(a==='print') window.print();
    if(a==='help') openHelp();
    if(a==='icsexp') openIcsExport();
    if(a==='export') exportBackup();
    if(a==='signout') signOut();
    if(a==='password') openChangePassword();
    if(!editable) return;
    if(a==='icsimp') pickFile('.ics,text/calendar',importIcsFile);
    if(a==='csvimp') pickFile('.csv,text/csv,.txt',importCsvFile);
    if(a==='owner') promptDlg('Calendar owner','Name shown in the header', data.owner||'').then(v=>{ if(v!=null){ data.owner=v.trim(); changed(); render(); } });
    if(a==='accounts') openAccounts();
    if(a==='history') openHistory();
    if(a==='import') pickFile('.json,.js,application/json,text/javascript',importBackupFile);
  };
  bindQuickAdd();
}
/* ---------- active time zone ---------- */
function setActiveTz(tz){
  if(!validTz(tz)) return;
  const prev=ACTIVE_TZ; if(tz===prev) return;
  ACTIVE_TZ=tz; prefs.activeTz = tz===BROWSER_TZ ? '' : tz; savePrefs();
  invalidateCaches(); _gridScroll=null; miniCursor=null; cursor=startOfDay(new Date(toDisp(fromDispWith(cursor.getTime()+12*3600000, prev))));
  renderAll(); renderTzLabel();
  toast(`🌍 Now showing ${tzShort(tz)} time (${tzOffsetLabel(tz)}). Events, tasks and flights moved to local times; new items are entered in ${tzShort(tz)} time.`,{ms:6000});
}
function fromDispWith(d, tz){ const keep=ACTIVE_TZ; ACTIVE_TZ=tz; const t=fromDisp(d); ACTIVE_TZ=keep; return t; }
function renderTzLabel(){ const l=$('#tzLbl'); if(!l) return; l.textContent=tzShort(ACTIVE_TZ); $('#tzBtn').title=`Active time zone: ${ACTIVE_TZ} (${tzOffsetLabel(ACTIVE_TZ)}). Times are entered and shown in it.`;
  const sug=tzSuggestion(); $('#tzBtn').classList.toggle('tzsug',!!sug); }
function tzSuggestion(){ if(!data) return null; const k=todayKey(), l=locationOf(k); const t=l && CITY_TZ[l.name.trim().toLowerCase()]; return t && t!==ACTIVE_TZ && tzOffsetMin(t,Date.now())!==tzOffsetMin(ACTIVE_TZ,Date.now()) ? {tz:t, city:l.name} : null; }
function renderTzMenu(){
  const now=Date.now(), sug=tzSuggestion();
  const clock=tz=>{ try{ return new Intl.DateTimeFormat('en-GB',{timeZone:tz,hour:'2-digit',minute:'2-digit'}).format(now); }catch(e){ return ''; } };
  const row=(tz,label)=>`<button data-tz="${esc(tz)}" class="${tz===ACTIVE_TZ?'on':''}" role="menuitemradio" aria-checked="${tz===ACTIVE_TZ}">${tz===ACTIVE_TZ?'✓ ':''}${esc(label||tz)} <span class="hint">· ${esc(tzOffsetLabel(tz,now))} · ${clock(tz)}</span></button>`;
  $('#tzMenu').innerHTML=`<div class="hint" style="padding:6px 10px">Times are entered and shown in the active time zone. Switch it when you travel.</div>
    ${sug?`<button data-tz="${esc(sug.tz)}" style="font-weight:600">📍 You are in ${esc(sug.city)} today → ${esc(sug.tz)} <span class="hint">· ${clock(sug.tz)}</span></button><hr>`:''}
    ${row(BROWSER_TZ,'This device: '+BROWSER_TZ)}<hr>
    ${TZ_LIST.filter(z=>z!==BROWSER_TZ).map(z=>row(z)).join('')}
    ${!TZ_LIST.includes(ACTIVE_TZ)?row(ACTIVE_TZ):''}<hr><button data-tz="?">Other time zone…</button>`;
}
function tzTravelPrompt(){
  const sug=tzSuggestion(); if(!sug) return;
  const k='wtc-tzsug:'+todayKey()+':'+sug.tz; if(safeGet(k)) return; safeSet(k,'1');
  toast(`📍 Your flights show you in ${sug.city} today. Show times in ${tzShort(sug.tz)} time?`,{actions:[['Switch to '+tzShort(sug.tz),()=>setActiveTz(sug.tz)]],ms:15000});
}
function newThing(kind){
  if(!editable) return;
  if(kind==='event'){ const k=view==='month'?key(cursor):(gridDays.includes(todayKey())?todayKey():key(cursor)); const n=new Date(NOW()); const st=k===todayKey()?minToHM(Math.min(23*60,Math.ceil((n.getHours()*60+n.getMinutes())/30)*30)):S().workStart; return openEventEditor(null,newEvent({start:k+'T'+st,end:localAddMin(k+'T'+st,S().defaultEventMin||60),tz:locTz(k)})); }
  if(kind==='task') return openTaskEditor(null);
  if(kind==='project') return openProjectEditor(null);
  if(kind==='template') return openTemplates();
  if(kind==='plan') return openPlanDay();
}

/* ---------- keyboard ---------- */
document.addEventListener('keydown',e=>{
  if(document.body.classList.contains('locked')) return;
  const typing=/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) || document.activeElement.isContentEditable;
  if(e.key==='Escape'){
    if($('#focus').classList.contains('open')){ endFocus(); return; }
    if($('#pop')){ closePopover(); return; }
    if(_dlgStack.length){ if(!topDialog()._page) closeDialog(); return; }
    if(openDay){ closeDrawer(); return; }
    if(selected.size||selTasks.size){ selected.clear(); selTasks.clear(); renderAll(); return; }
    return;
  }
  if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='k'){ e.preventDefault(); openSearch(); return; }
  if(typing) return;
  if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='z'){ e.preventDefault(); e.shiftKey?redo():undo(); return; }
  if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='y'){ e.preventDefault(); redo(); return; }
  if(e.ctrlKey||e.metaKey||e.altKey) return;
  if(_dlgStack.length) return;
  const k=e.key;
  if(k==='ArrowLeft'){ openDay ? stepDrawer(-1) : move(-1); return; }
  if(k==='ArrowRight'){ openDay ? stepDrawer(1) : move(1); return; }
  const lk=k.toLowerCase();
  if(lk==='t') $('#today').click();
  else if(lk==='d') setView('day'); else if(k==='3') setView('3day'); else if(lk==='w') setView('week'); else if(lk==='m') setView('month'); else if(lk==='a') setView('agenda'); else if(lk==='b') setView('board');
  else if(k==='/'){ e.preventDefault(); openSearch(); }
  else if(lk==='q'){ e.preventDefault(); $('#qa').focus(); }
  else if(lk==='n' && editable){ e.preventDefault(); $('#newMenu').classList.add('open'); $('#newMenu button').focus(); }
  else if(lk==='p') openPlanDay();
  else if(k==='?') openHelp();
  else if(lk==='e' && selItem){ const it=findItem(selItem.id); if(it) it.type==='event'?openEventEditor(it.id,null,selItem.occ):openTaskEditor(it.id); }
  else if(k===' ' && selItem && editable){ e.preventDefault(); if(selItem.kind==='block') toggleBlockDone(selItem.id,selItem.occ); else if(selItem.kind==='task') toggleTaskDone(selItem.id); }
  else if((k==='Delete'||k==='Backspace') && selItem && editable){ e.preventDefault(); const it=findItem(selItem.id); if(!it) return; if(it.type==='task' && selItem.kind==='task') { confirmDlg({title:'Delete task',html:`Delete “${esc(it.title)}”?`},[['Delete','y','danger'],['Cancel',null]]).then(y=>{ if(y) removeItem(it); }); } else deleteOcc(it.id, selItem.occ||it.start.slice(0,10), selItem.kind); }
});
window.addEventListener('beforeunload',e=>{ if(editable && (dirty || saving)){ e.preventDefault(); e.returnValue=''; } });

/* ============================================================
   Details in a new browser tab (events of every type, projects, flights)
   URL:  index.php#open=event&id=…&occ=YYYY-MM-DD
         index.php#open=project&id=…
         index.php#open=flight&day=YYYY-MM-DD&n=0
   The tab signs in with the same session, shows the details full-page,
   lets editors change them, and stays in sync with the calendar tab
   (every save is announced to the other tabs).
   Settings → General → "Open details" switches back to the popup.
   ============================================================ */
const DETAIL = (()=>{
  const h=new URLSearchParams(location.hash.slice(1)), t=h.get('open');
  if(!['event','project','flight','edit'].includes(t)) return null;
  return {t, id:h.get('id')||'', occ:h.get('occ')||'', k:h.get('day')||'', i:+(h.get('n')||0), from:h.get('from')||'', to:h.get('to')||'',
          type:h.get('type')||'', draft:h.get('draft')||''};
})();
if(DETAIL) document.body.classList.add('detail'); else { try{ if(!window.name || !window.name.startsWith('wtc-')) window.name='wtc-main'; }catch(e){} }

function useTabs(){ return !!DETAIL || (prefs.openDetails||'tab')!=='popup'; }
function detailUrl(o){
  const p=new URLSearchParams({open:o.t});
  if(o.id) p.set('id',o.id); if(o.occ) p.set('occ',o.occ);
  if(o.t==='edit'){ p.set('type',o.type); if(o.draft) p.set('draft',o.draft); }
  if(o.t==='flight'){ p.set('day',o.k); p.set('n',o.i); const f=(day(o.k).flights||[])[o.i]; if(f){ p.set('from',f.from||''); p.set('to',f.to||''); } }
  return location.pathname+location.search+'#'+p.toString();
}
function openDetailTab(o){
  const name='wtc-'+o.t+'-'+(o.t==='flight'?o.k+'-'+o.i:o.id)+(o.occ?'-'+o.occ:'');   // same item → same tab
  let w=null; try{ w=window.open(detailUrl(o), name); }catch(e){}
  if(!w){   // pop-ups blocked: show it here instead
    toast('Your browser blocked the new tab. Allow pop-ups for this site, or choose “popup” in ⚙ Settings → General → Open details.',{ms:9000});
    if(o.t==='event') return openEventEditor(o.id,null,o.occ);
    if(o.t==='project') return showProjectPage(o.id,null);
    return openDrawer(o.k);
  }
  try{ w.focus(); }catch(e){}
}
function openEvent(id, occ){ if(useTabs()) return openDetailTab({t:'event', id, occ:occ||''}); openEventEditor(id,null,occ); }
function openFlight(k, i){ if(useTabs()) return openDetailTab({t:'flight', k, i:+i||0}); openDrawer(k); }
function closeDetailTab(){
  window.close();                       // works for tabs opened by the calendar
  setTimeout(()=>{ location.href=location.pathname+location.search; },300);   // otherwise: go to the calendar
}
/* show a day in the calendar tab (reuses it, or opens one) */
function showInCalendar(k){
  let w=null; try{ w=window.open('', 'wtc-main'); }catch(e){}
  let blank=false; try{ blank=!w || w.location.href==='about:blank'; }catch(e){ blank=false; }
  if(w && blank){ w.location.href=location.pathname+location.search+'#day='+k; return; }
  if(!w){ location.href=location.pathname+location.search+'#day='+k; return; }
  try{ _bc && _bc.postMessage({type:'goto', k}); w.focus(); }catch(e){}
}
function gotoDay(k){ if(!/^\d{4}-\d{2}-\d{2}$/.test(k||'')) return; cursor=fromKey(k); miniCursor=null; if(view==='month'||view==='board'||view==='agenda') view='day'; renderAll(); try{ window.focus(); }catch(e){} }
function routeMainHash(){
  const h=new URLSearchParams(location.hash.slice(1)), k=h.get('day');
  if(k){ gotoDay(k); history.replaceState(null,'',location.pathname+location.search); }
}
window.addEventListener('hashchange',()=>{ if(DETAIL) location.reload(); else if(session) routeMainHash(); });

/* ---------- rendering ---------- */
let _detailProjectDraw=null;
function renderDetail(){
  const el=$('#detail'); if(!el || !data) return;
  if(DETAIL.t==='project'){ if(!_detailProjectDraw || !el.firstChild) _detailProjectDraw=showProjectPage(DETAIL.id, el); else _detailProjectDraw(); el.classList.add('box'); return; }
  el.classList.remove('box');
  if(DETAIL.t==='event') return renderEventDetail(el);
  if(DETAIL.t==='flight') return renderFlightDetail(el);
  if(DETAIL.t==='edit') return renderEditPage(el);
}
function renderDetailMissing(kind){
  const el=$('#detail'); document.title='Not found · Work & Travel';
  el.innerHTML=`<div class="dt-card"><h1 class="dt-title">This ${kind} is not available</h1>
    <p class="hint">It may have been deleted${privLocked?', or it is a Personal item and Personal items are locked on this device':''}.</p>
    <div class="dacts">${privLocked?'<button data-unl class="primary">🔑 Unlock Personal items</button>':''}<button data-cal>📅 Open the calendar</button><button data-x>Close tab</button></div></div>`;
  const u=$('[data-unl]',el); if(u) u.onclick=openUnlock;
  $('[data-cal]',el).onclick=()=>{ location.href=location.pathname+location.search; };
  $('[data-x]',el).onclick=closeDetailTab;
}
const dtRow=(label,html)=>html?`<dt>${esc(label)}</dt><dd>${html}</dd>`:'';
function remText(r){ return esc((OFFSETS.find(o=>o[0]===+r.offsetMin)||[0,fmtDur(r.offsetMin)+' before'])[1])+(r.channel&&r.channel!=='popup'?' · desktop'+(r.channel==='sound'?' + sound':''):''); }

function renderEventDetail(el){
  const it=ev(DETAIL.id); if(!it) return renderDetailMissing('event');
  let o=null;
  if(it.rrule && DETAIL.occ && it.exceptions[DETAIL.occ] && it.exceptions[DETAIL.occ].deleted) return renderDetailMissing('occurrence');   // that occurrence was deleted
  if(it.rrule && DETAIL.occ) o=findOcc(it.id, DETAIL.occ, 'event');
  if(!o) o=it.rrule ? (expandEvent(it, NOW()-864e5, NOW()+400*864e5)[0] || expandEvent(it,-1e14,1e14)[0]) : expandEvent(it,-1e14,1e14)[0];
  if(!o) return renderDetailMissing('event');
  const p=proj(it.projectId), c=cal(it.calendarId), tz=it.tz;
  document.title=(o.cancelled?'✕ ':'')+o.title+' · Work & Travel';
  const when = o.allDay
    ? (o.dateKey===o.endKey ? fmtLong(fromKey(o.dateKey)) : fmtLong(fromKey(o.dateKey))+' – '+fmtLong(fromKey(o.endKey)))+' · all day'
    : `${fmtLong(new Date(o.s))}<br><b>${hm(o.s)} – ${hm(o.e)}</b> <span class="hint">(${esc(tzShort(ACTIVE_TZ))} time · ${fmtDur((o.e-o.s)/60000)})</span>`
      +(tz && tz!==ACTIVE_TZ?`<div class="hint">Entered in ${esc(tzShort(tz))} time: ${esc(msToZoned(o.s,tz).slice(11))} – ${esc(msToZoned(o.e,tz).slice(11))}</div>`:'');
  const conf=o.allDay?null:conflictsIn(occurrences(o.s-864e5,o.e+864e5,{all:true})).get(it.id+'|'+o.occ);
  const linked=it.linkedTaskIds.map(task).filter(Boolean);
  const k=o.dateKey||key(new Date(o.s));
  el.innerHTML=`<div class="dt-card">
    <div class="dt-head"><span class="dotc" style="background:${esc(itemColor(it))};width:14px;height:14px"></span>
      <span class="tag k">${esc(CATEGORIES[it.category]||'Event')}</span>${it.rrule?'<span class="tag">🔁 repeating</span>':''}${o.cancelled?'<span class="tag" style="color:var(--sick)">Cancelled</span>':''}${isPrivate(it)?'<span class="tag">🔒 private</span>':''}</div>
    <h1 class="dt-title redtitle">${o.cancelled?'<s>':''}${esc(o.title)}${o.cancelled?'</s>':''}</h1>
    <dl class="dt-grid">
      ${dtRow('When', when)}
      ${dtRow('Repeats', it.rrule?esc(rrText(it.rrule))+(o.ex?' · <i>this occurrence was changed</i>':''):'')}
      ${dtRow('Calendar', `<i class="dotc" style="display:inline-block;background:${esc(c.color)}"></i> ${esc(c.name)}`)}
      ${dtRow('Project', p?`<a href="#" data-proj="${esc(p.id)}">${esc(p.icon||'')} ${esc(p.name)}</a>`:'')}
      ${dtRow('Location', (o.location||it.location)?'📍 '+esc(o.location||it.location):'')}
      ${dtRow('Online', it.onlineLink?`<a href="${esc(it.onlineLink)}" target="_blank" rel="noopener noreferrer">${esc(it.onlineLink)}</a>`:'')}
      ${dtRow('Attendees', it.attendees.length?esc(it.attendees.join(', ')):'')}
      ${dtRow('Show as', esc(SHOW_AS[it.showAs]||''))}
      ${dtRow('Reminders', it.reminders.map(remText).join('<br>'))}
      ${dtRow('Tags', it.tags.map(t=>`<span class="tag">#${esc(t)}</span>`).join(' '))}
      ${dtRow('Links', it.links.map(l=>`<a href="${esc(l.url)}" target="_blank" rel="noopener noreferrer">${esc(l.label||l.url)}</a>`).join('<br>'))}
      ${dtRow('Tasks', linked.map(t=>`<a href="#" data-task="${esc(t.id)}">${isOpen(t)?'☐':'✓'} ${esc(t.title)}</a>`).join('<br>'))}
    </dl>
    ${conf?`<div class="warnbox">⚠ ${conf.map(esc).join('<br>')}</div>`:''}
    ${it.notes?`<h2 class="dt-sub">Notes</h2><div class="md dt-notes">${md(it.notes)}</div>`:''}
    <div class="dacts dt-acts">
      ${editable?'<button class="primary" data-a="edit">✎ Edit</button><button data-a="follow">＋ Follow-up task</button><button data-a="dup">Duplicate</button>':''}
      ${it.onlineLink?'<button data-a="join">🔗 Join</button>':''}
      <button data-a="cal">📅 Show in calendar</button><button data-a="ics">⬇ .ics</button>
      ${editable?'<button data-a="del" class="danger">Delete</button>':''}
      <span class="sp"></span><button data-a="close">Close tab</button>
    </div>
    <div class="hint dt-foot">Updated ${esc(fmtWhen(it.updated))}. This tab stays up to date with the calendar.</div></div>`;
  const a=(n,fn)=>{ const b=$(`[data-a="${n}"]`,el); if(b) b.onclick=fn; };
  a('edit',()=>openEventEditor(it.id,null,o.occ));
  a('follow',()=>{ const t=newTask({title:'Follow up: '+it.title, calendarId:it.calendarId, projectId:it.projectId, dueDate:keyAdd(k,2), private:it.private}); openTaskEditor(null,t,{linkEvent:it.id}); });
  a('dup',()=>{ const c2=duplicateItem(it); openDetailTab({t:'event', id:c2.id}); });
  a('join',()=>window.open(it.onlineLink,'_blank','noopener'));
  a('cal',()=>showInCalendar(k));
  a('ics',()=>exportIcs({items:[it], name:it.title}));
  a('del',()=>deleteOcc(it.id, o.occ, 'event'));
  a('close',closeDetailTab);
  $$('[data-proj]',el).forEach(x=>x.onclick=e=>{ e.preventDefault(); openDetailTab({t:'project', id:x.dataset.proj}); });
  $$('[data-task]',el).forEach(x=>x.onclick=e=>{ e.preventDefault(); openTaskEditor(x.dataset.task); });
}

function detailFlight(){
  const fl=day(DETAIL.k).flights||[];
  let i=DETAIL.i;
  if(DETAIL.from||DETAIL.to){   // flights of that day changed order: find it by its route
    const ok=f=>f && (f.from||'')===DETAIL.from && (f.to||'')===DETAIL.to;
    if(!ok(fl[i])){ const j=fl.findIndex(ok); if(j>=0) i=j; }
  }
  return fl[i] ? {f:fl[i], i} : null;
}
function renderFlightDetail(el){
  const hit=detailFlight(); if(!hit) return renderDetailMissing('flight');
  const {f,i}=hit, k=DETAIL.k, ro=!editable, dis=ro?'disabled':'';
  const m=flightMins(f), ftz=flightTz(f), w=flightWindows(k).find(x=>x.i===i), al=flightAlarmMin(f);
  const ttz=CITY_TZ[(f.to||'').trim().toLowerCase()];
  document.title='✈ '+code(f.from)+' → '+code(f.to)+' · '+fmtShort(fromKey(k));
  const evs=w?occurrences(w.s-12*3600000, w.e+12*3600000,{all:true}).filter(o=>o.kind==='event'&&!o.allDay&&!o.cancelled&&(privKey||!isPrivate(o.item))):[];
  const e=day(k), l=locationOf(keyAdd(k,1));
  el.innerHTML=`<div class="dt-card">
    <div class="dt-head"><span class="tag k">✈ Flight</span><span class="tag">${esc(FLIGHT_TIMES[flightKind(f)])}</span>${al===null?'<span class="tag">⏰ alarm off</span>':`<span class="tag">⏰ ${minToHM(al)}</span>`}</div>
    <h1 class="dt-title redtitle">${esc(f.from||'?')} → ${esc(f.to||'?')}</h1>
    <div class="hint" style="font-size:15px;margin:-4px 0 12px">${esc(fmtLong(fromKey(k)))}</div>
    <dl class="dt-grid">
      ${dtRow('Departs', `<b>${minToHM(m.a)}</b> ${esc(tzShort(ftz))} time${w&&ftz!==ACTIVE_TZ?` <span class="hint">(${hm(w.s)} ${esc(tzShort(ACTIVE_TZ))})</span>`:''}`)}
      ${dtRow('Arrives', `<b>${m.b>=1440?'24:00':minToHM(m.b)}</b> ${esc(tzShort(ftz))} time${w&&ftz!==ACTIVE_TZ?` <span class="hint">(${hm(w.e)} ${esc(tzShort(ACTIVE_TZ))})</span>`:''}${ttz&&ttz!==ftz&&w?` <span class="hint">· ${esc(msToZoned(w.e,ttz).slice(11))} local in ${esc(tzShort(ttz))}</span>`:''}`)}
      ${dtRow('Flight', f.note?esc(f.note):'')}
      ${dtRow('Work status', !isWeekend(fromKey(k))&&(e.am||e.pm)?esc(e.am===e.pm?STATUSES[e.am].label:'Morning '+(e.am?STATUSES[e.am].label:'—')+' · Evening '+(e.pm?STATUSES[e.pm].label:'—')):'')}
      ${dtRow('Next day in', l?'📍 '+esc(l.name):'')}
      ${dtRow('Around it', evs.map(o=>`<a href="#" data-occ="${esc(o.id)}" data-oc="${esc(o.occ)}">${esc(fmtShort(new Date(o.s)))} ${hm(o.s)} · ${esc(o.title)}</a>`).join('<br>'))}
    </dl>
    ${ro?'':`<h2 class="dt-sub">Edit</h2>
    <div class="flight" style="max-width:640px">
      <div class="route">✈ <input type="text" data-ff="from" value="${esc(f.from)}" aria-label="From"> → <input type="text" data-ff="to" value="${esc(f.to)}" aria-label="To"></div><span></span>
      <div class="row2">${Object.entries(FLIGHT_TIMES).map(([t,lab])=>`<button class="chip ${flightKind(f)===t?'loc-on':''}" data-ft="${t}" ${dis}>${lab} <span class="hint">${minToHM(FLIGHT_WINDOWS[t][0])}–${FLIGHT_WINDOWS[t][1]>=1440?'24':minToHM(FLIGHT_WINDOWS[t][1]).replace(':00','')}</span></button>`).join('')}</div>
      <div class="row2"><label class="hint">Departs <input type="time" data-fx="dep" value="${minToHM(m.a)}" step="300"></label><label class="hint">Arrives <input type="time" data-fx="arr" value="${m.b>=1440?'23:59':minToHM(m.b)}" step="300"></label>
        <label class="hint">⏰ Alarm <input type="time" data-fx="alarm" value="${al===null?'':minToHM(al)}" step="300"></label><label class="hint"><input type="checkbox" data-falarm ${al===null?'':'checked'}> alarm on</label>
        ${m.custom||f.alarm?`<button class="chip" data-freset>↺ default</button>`:''}</div>
      <div class="row2"><input type="text" data-ff="note" value="${esc(f.note||'')}" placeholder="Flight number, e.g. A3 610"></div>
    </div>`}
    <div class="dacts dt-acts"><button data-a="cal">📅 Show day in calendar</button>${ro?'':'<button data-a="del" class="danger">Delete flight</button>'}<span class="sp"></span><button data-a="close">Close tab</button></div>
    <div class="hint dt-foot">Times are local to the departure city (${esc(tzShort(ftz))}). This tab stays up to date with the calendar.</div></div>`;
  $('[data-a=cal]',el).onclick=()=>showInCalendar(k);
  $('[data-a=close]',el).onclick=closeDetailTab;
  $$('[data-occ]',el).forEach(x=>x.onclick=ev2=>{ ev2.preventDefault(); openDetailTab({t:'event', id:x.dataset.occ, occ:x.dataset.oc}); });
  if(ro) return;
  const cur=()=>day(k).flights[detailFlight().i];
  const commit=()=>{ const g=cur(); DETAIL.from=g.from||''; DETAIL.to=g.to||''; history.replaceState(null,'',detailUrl({t:'flight',k,i:detailFlight().i})); prune(k); changed(); renderAll(); };
  $$('[data-ff]',el).forEach(inp=>inp.onchange=()=>{ cur()[inp.dataset.ff]=inp.value.trim(); commit(); });
  $$('[data-ft]',el).forEach(b=>b.onclick=()=>{ const g=cur(); g.time=b.dataset.ft; delete g.dep; delete g.arr; delete g.alarm; commit(); });
  $$('[data-fx]',el).forEach(inp=>inp.onchange=()=>{ const g=cur(), v=inp.value, n=inp.dataset.fx;
    if(n==='alarm'){ g.alarm=v||'off'; } else if(!v) delete g[n]; else g[n]=v;
    const d=FLIGHT_WINDOWS[flightKind(g)];
    if(g.dep===minToHM(d[0]) && (g.arr===minToHM(d[1])||(d[1]>=1440&&g.arr==='23:59')||!g.arr)){ delete g.dep; delete g.arr; }
    if(g.alarm && g.alarm!=='off' && g.alarm===minToHM(flightAlarmMin({...g,alarm:undefined}))) delete g.alarm;
    commit(); });
  const fa=$('[data-falarm]',el); if(fa) fa.onchange=()=>{ const g=cur(); if(fa.checked) delete g.alarm; else g.alarm='off'; commit(); };
  const fr=$('[data-freset]',el); if(fr) fr.onclick=()=>{ const g=cur(); delete g.dep; delete g.arr; delete g.alarm; commit(); };
  $('[data-a=del]',el).onclick=async()=>{
    if(!await confirmDlg({title:'Delete flight', html:`Delete the flight ${esc(f.from)} → ${esc(f.to)} on ${esc(fmtLong(fromKey(k)))}?`},[['Delete','y','danger'],['Cancel',null]])) return;
    day(k).flights.splice(detailFlight().i,1); prune(k); changed(); renderAll(); toast('Flight deleted',{actions:[['Undo',undo]]});
  };
}

/* ============================================================
   Edit windows in their own tab (events, tasks, projects)
   URL:  index.php#open=edit&type=event|task|project&id=…[&occ=…]
         new items: …&draft=<key>  (the draft is handed over through this
         browser's storage and removed as soon as the tab reads it)
   The editor fills the page; Save or Cancel closes the tab once the
   change has reached the server. Setting: ⚙ General → Edit windows.
   ============================================================ */
let FORCE_DIALOG=false;
function editInTab(){ return !FORCE_DIALOG && !EDIT_PAGE_PENDING && (prefs.openEditors||'tab')!=='popup' && !!session; }
function openEditTab(type, id, draft, occ, opts){
  const o={t:'edit', type, id:id||'', occ:occ||''};
  if(!id){ o.draft='wtc-draft:'+uid(''); jset(o.draft,{draft:clone(draft||null), opts:clone(opts||{}), at:Date.now()}); }
  const name='wtc-edit-'+type+'-'+(id||o.draft.slice(10))+(occ?'-'+occ:'');
  let w=null; try{ w=window.open(detailUrl(o), name); }catch(e){}
  if(!w){   // pop-ups blocked: edit here instead
    if(o.draft) safeDel(o.draft);
    toast('Your browser blocked the new tab. Allow pop-ups for this site, or choose “popup” in ⚙ Settings → General → Edit windows.',{ms:9000});
    FORCE_DIALOG=true;
    try{ if(type==='event') openEventEditor(id,draft,occ); else if(type==='task') openTaskEditor(id,draft,opts||{}); else openProjectEditor(id,draft); }
    finally{ FORCE_DIALOG=false; }
    return;
  }
  try{ w.focus(); }catch(e){}
}
let _editOpened=false;
function renderEditPage(el){
  if(_editOpened) return;                       // the editor keeps its own state once open
  const D=DETAIL, label={event:'event',task:'task',project:'project'}[D.type];
  if(!label) return renderDetailMissing('item');
  let draft=null, opts={};
  if(!D.id){
    const h=D.draft?jget(D.draft,null):null; if(D.draft) safeDel(D.draft);
    if(!h){ el.innerHTML=`<div class="dt-card"><h1 class="dt-title">This new ${label} is no longer available</h1><p class="hint">It was not saved; the window was probably reloaded. Start it again from the calendar.</p><div class="dacts"><button data-x>Close tab</button></div></div>`; $('[data-x]',el).onclick=closeDetailTab; return; }
    draft=h.draft; opts=h.opts||{};
    if(D.type==='event' && draft) draft=newEvent(draft);
    if(D.type==='task' && draft) draft=newTask(draft);
  } else {
    const it = D.type==='project' ? proj(D.id) : D.type==='task' ? task(D.id) : ev(D.id);
    if(!it) return renderDetailMissing(label);
  }
  _editOpened=true; el.innerHTML='';
  EDIT_PAGE_PENDING=true;
  try{
    if(D.type==='event') openEventEditor(D.id||null, draft, D.occ||undefined);
    else if(D.type==='task') openTaskEditor(D.id||null, draft, opts);
    else openProjectEditor(D.id||null, draft);
  } finally { EDIT_PAGE_PENDING=false; }
  const t=$('.dlg.page input[name=title],.dlg.page input[name=name]',el);
  const nm=()=>(t&&t.value.trim())||('New '+label);
  document.title=(D.id?'Edit: ':'')+nm()+' · Work & Travel';
  if(t) t.addEventListener('input',()=>{ document.title=(D.id?'Edit: ':'')+nm()+' · Work & Travel'; });
}
/* Save/Cancel/Delete in the edit page: wait for nested dialogs and for the save to reach the server, then close the tab */
async function closeEditPage(d){
  if(d._closing) return;
  const saved = changeSeq!==d._seq;
  if(!saved && d._cancel && d._modified && !await confirmDlg({title:'Discard changes?', html:'Your changes in this window have not been saved.'},[['Discard','y','danger'],['Keep editing',null]])){ d._cancel=false; return; }
  d._closing=true; d.style.display='none';
  const el=$('#detail'); const msg=document.createElement('div'); msg.className='dt-card'; msg.id='closingMsg';
  msg.innerHTML='<h1 class="dt-title">Closing…</h1><p class="hint" id="closingWhy">Saving your changes.</p><div class="dacts"><button data-cal>📅 Open the calendar</button></div>';
  el.appendChild(msg); $('[data-cal]',msg).onclick=()=>{ location.href=location.pathname+location.search; };
  await new Promise(r=>setTimeout(r,50));          // let the action that closed the page open its own confirmation first (e.g. Delete)
  const t0=Date.now();
  while(Date.now()-t0<20000){
    if(!_dlgStack.some(x=>x!==d) && !dirty && !saving && !saveTimer && !conflict) break;
    if(conflict || (netError && dirty)){ $('#closingWhy',msg).textContent='Not saved yet: see the message at the top. This tab stays open until it is saved.'; }
    await new Promise(r=>setTimeout(r,150));
  }
  if(dirty || saving || conflict) return;        // keep the tab open; the banner explains what is wrong
  if(changeSeq===d._seq && !d._cancel){           // e.g. Delete was cancelled: show the editor again
    msg.remove(); d.style.display=''; d._closing=false; return; }
  _dlgStack=_dlgStack.filter(x=>x!==d); d.remove();
  const did=changeSeq!==d._seq;
  $('#closingWhy',msg).textContent = did ? 'Saved. You can close this tab.' : 'You can close this tab.';
  $('h1',msg).textContent = did ? 'Saved ✓' : 'Closed';
  window.close();                                 // works for tabs the calendar opened
}
window.addEventListener('beforeunload',e=>{ const p=$('.dlg.page'); if(p && p._modified && !p._closing && changeSeq===p._seq){ e.preventDefault(); e.returnValue=''; } });

/* ---------- start ---------- */
bindTop();
if('serviceWorker' in navigator && location.protocol==='https:') navigator.serviceWorker.register('sw.js').catch(()=>{});
boot();

</script>
</body>
</html>
