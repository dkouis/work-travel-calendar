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
  .vtools label,.vtools [data-zoom],.legend,.help{display:none}
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
    <label class="chk"><input type="checkbox" id="lRemember"> Keep me signed in on this device</label>
    <div class="lerr" id="lErr"></div>
    <button class="primary" type="submit" id="lBtn">Sign in</button>
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
  hourPx:44, density:'comfortable', colorBy:'project', secondTz:'', showLegend:true, sidePanel:true, tasksPanel:true,
  desktop:false, sound:true, quietStart:'22:00', quietEnd:'07:30', digestTime:'08:30', digest:true,
  reviewDay:4, reviewTime:'16:00', review:true, eod:false, eodTime:'18:00', snooze:[5,10,30,60,'tomorrow'],
  activeTz:'', offlineCopy:false, tpSort:'due', tpGroup:'none', savedFilters:[]
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
  return (day(k).flights||[]).map(f=>{
    const {a,b}=flightMins(f), tz=flightTz(f);
    const s=zonedToMs(k+'T'+minToHM(a),tz), e=b>=1440?zonedToMs(keyAdd(k,1)+'T00:00',tz):zonedToMs(k+'T'+minToHM(b),tz);
    return {s, e, f, k, tz};
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
  setTimeout(tzTravelPrompt, 2500);
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
  const u=$('#lUser').value.trim(), p=$('#lPass').value, btn=$('#lBtn');
  if(!u||!p) return;
  btn.disabled=true; btn.textContent='Signing in…'; $('#lErr').textContent='';
  try{
    const me = await api('login', {user:u, pass:p, remember:$('#lRemember').checked});
    $('#lPass').value='';
    await enter(me);
  }catch(err){ $('#lErr').textContent = err.message; }
  finally{ btn.disabled=false; btn.textContent='Sign in'; }
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
  render(); renderSide(); renderTasksPanel(); renderBell(); renderTimerSlot(); renderTzLabel();
  if(openDay) renderDrawer();
  refreshOpenPanels();
}
const _panelRefreshers = new Set();
function refreshOpenPanels(){ for(const f of [..._panelRefreshers]) try{ f(); }catch(e){ console.error(e); } }

/* ---------- dialogs ---------- */
let _dlgStack=[];
function openDialog(html, opts={}){
  const d=document.createElement('div'); d.className='dlg'; d.setAttribute('role','dialog'); d.setAttribute('aria-modal','true');
  d.innerHTML=`<div class="box ${opts.size||''}">${html}</div>`;
  document.body.appendChild(d); _dlgStack.push(d);
  d.addEventListener('mousedown', e=>{ if(e.target===d && !opts.sticky) closeDialog(d); });
  $$('[data-close]',d).forEach(b=>b.onclick=()=>closeDialog(d));
  d._onclose = opts.onclose;
  if(opts.refresh){ d._refresh=opts.refresh; _panelRefreshers.add(opts.refresh); }
  setTimeout(()=>{ const f=$(opts.focus||'[autofocus],input:not([type=hidden]):not([disabled]),select,textarea,button.primary',d); f&&f.focus(); },0);
  return d;
}
function closeDialog(d){
  d = d || _dlgStack[_dlgStack.length-1]; if(!d) return;
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
function visibleRange(){
  if(view==='day') return [cursor, addDays(cursor,1)];
  if(view==='3day') return [cursor, addDays(cursor,3)];
  if(view==='week') return [mondayOf(cursor), addDays(mondayOf(cursor),7)];
  if(view==='agenda') return [cursor, addDays(cursor,agendaDays)];
  if(view==='board') return [cursor, addDays(cursor,1)];
  const f=new Date(cursor.getFullYear(),cursor.getMonth(),1); return [mondayOf(f), addDays(mondayOf(new Date(cursor.getFullYear(),cursor.getMonth()+1,0)),7)];
}
function move(n){
  if(view==='day') cursor=addDays(cursor,n);
  else if(view==='3day') cursor=addDays(cursor,3*n);
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
  return (e.flights||[]).map(f=>`<div class="fl" title="${esc(f.from)} → ${esc(f.to)}${f.note?' · '+esc(f.note):''}">✈ ${esc(code(f.from))} → ${esc(code(f.to))} <span class="t">${esc(FLIGHT_TIMES[flightKind(f)])} ${esc(k?flightDisp(k,f):flightTimeLabel(f))}</span></div>`).join('');
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
  return (day(k).flights||[]).map(f=>`<div class="chipi flight" title="${esc(f.from)} → ${esc(f.to)} · ${esc(flightLabel(f))}${f.note?' · '+esc(f.note):''}">✈ ${esc(code(f.from))}→${esc(code(f.to))} <span class="t">${esc(flightDisp(k,f))}</span></div>`).join('');
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
  else if(view==='3day') html=renderGrid([0,1,2].map(i=>addDays(cursor,i)));
  else if(view==='week') html = weekCompact ? renderWeekCompact() : renderGrid([0,1,2,3,4,5,6].map(i=>addDays(mondayOf(cursor),i)));
  else if(view==='agenda') html=renderAgenda();
  else if(view==='board') html=renderBoard();
  else html=renderMonth();
  $('#main').innerHTML = toolsHTML() + legendHTML() + html + helpLine();
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
    ${grid&&!weekCompact?`<button data-zoom="-1" title="Smaller hours" aria-label="Zoom out">－</button><button data-zoom="1" title="Taller hours" aria-label="Zoom in">＋</button>
      <label>2nd zone <select data-tz2><option value="">none</option>${TZ_LIST.map(z=>`<option ${prefs.secondTz===z?'selected':''}>${z}</option>`).join('')}</select></label>`:''}
    <label>Colour by <select data-colorby>${['project','calendar','priority'].map(c=>`<option ${prefs.colorBy===c?'selected':''}>${c}</option>`).join('')}</select></label>
    <label>Density <select data-density>${['comfortable','compact'].map(c=>`<option ${prefs.density===c?'selected':''}>${c}</option>`).join('')}</select></label>
    <details class="dropdown"><summary style="cursor:pointer;color:var(--muted)">Show ▾</summary><div class="menu open" style="display:block;padding:10px;min-width:210px">
      ${tog('showStatus','Work status')}<br>${tog('showFlights','Flights')}<br>${tog('showBlocks','Task blocks')}<br>${tog('showDue','Due dates')}<br>${tog('showDone','Completed tasks')}<br>${tog('showCancelled','Cancelled events')}<br>
      <label><input type="checkbox" data-legend ${prefs.showLegend?'checked':''}> Legend</label></div></details>
    ${filt.projects.length||filt.tag||filt.hiddenCals.length?`<span class="tag k">Filtered${filt.projects.length?': '+filt.projects.map(p=>p.startsWith('inbox:')?'Inbox':(proj(p)||{}).name).join(', '):''}${filt.tag?' #'+esc(filt.tag):''} <button class="ghost" data-clearf style="padding:0 3px">✕</button></span>`:''}
  </div>`;
}
function legendHTML(){
  if(!prefs.showLegend || view==='board') return '';
  return '<div class="legend">'+(filt.showStatus?Object.entries(STATUSES).map(([k,s])=>`<span><i class="sw" style="background:var(--${k})"></i>${s.label}</span>`).join(''):'')
    + '<span>📍 location (dashed = from last flight)</span><span>✈ flight</span>'
    + '<span><i class="sw" style="background:repeating-linear-gradient(135deg,var(--accent) 0 3px,transparent 3px 6px);border:1px solid var(--line2)"></i>task block</span><span>⚑ task due</span><span>⚠ conflict</span>'
    + (filt.showStatus?'<span><i class="sw" style="background:var(--fill-ath-tele);border:1px solid var(--line2)"></i>Athens + Telework</span><span><i class="sw" style="background:var(--fill-ath-abroad);border:1px solid var(--line2)"></i>Athens + Telework abroad</span>':'')+'</div>';
}
function bindTools(){
  const m=$('#main');
  $$('[data-wc]',m).forEach(b=>b.onclick=()=>{ weekCompact=b.dataset.wc==='1'; safeSet('wtc-weekc',weekCompact?'1':'0'); render(); });
  $$('[data-zoom]',m).forEach(b=>b.onclick=()=>{ prefs.hourPx=clamp(prefs.hourPx+(+b.dataset.zoom)*8,24,96); savePrefs(); render(); });
  const z=$('[data-tz2]',m); if(z) z.onchange=()=>{ prefs.secondTz=z.value; savePrefs(); render(); };
  $('[data-colorby]',m).onchange=e=>{ prefs.colorBy=e.target.value; savePrefs(); renderAll(); };
  $('[data-density]',m).onchange=e=>{ prefs.density=e.target.value; savePrefs(); render(); };
  $$('[data-ft]',m).forEach(c=>c.onchange=()=>{ filt[c.dataset.ft]=c.checked; saveFilter(); renderAll(); });
  const lg=$('[data-legend]',m); if(lg) lg.onchange=()=>{ prefs.showLegend=lg.checked; savePrefs(); render(); };
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
  let html='<div class="month"><div class="mhead">'+DOW.map(x=>`<div>${x}</div>`).join('')+'</div>';
  while(d<=end){
    html+='<div class="mrow">';
    for(let i=0;i<7;i++){
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
  let html='<div class="week">';
  for(let i=0;i<7;i++){
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
  $('#title').textContent = n===1 ? fmtLong(days[0]) : n===7 ? weekTitle(days[0],days[n-1]) : `${fmtDM(days[0])} – ${fmtDM(days[n-1])} ${days[n-1].getFullYear()}`;
  const cols=`${prefs.secondTz?'78px':'52px'} repeat(${n},minmax(0,1fr))`;
  const ws=hmToMin(S().workStart)||540, we=hmToMin(S().workEnd)||1080;
  const r0=fromKey(gridDays[0]).getTime(), r1=addDays(fromKey(gridDays[n-1]),1).getTime();
  const allOcc=occurrences(r0,r1);
  const conf=conflictsIn(allOcc);
  // header
  let head=`<div class="tg-head" style="grid-template-columns:${cols}"><div class="tg-gut">W${isoWeek(days[0])}<br><span title="Active time zone ${esc(ACTIVE_TZ)}">${esc(tzShort(ACTIVE_TZ))}</span></div>`;
  let all=`<div class="tg-all" style="grid-template-columns:${cols}"><div class="tg-gut">all-day</div>`;
  let body=`<div class="tg-grid" style="grid-template-columns:${cols};height:${24*hp}px"><div class="tg-hours">`;
  for(let h=0;h<24;h++){
    let second='';
    if(prefs.secondTz){ const ms=fromKey(gridDays[0]).getTime()+h*3600000; second=`<span>${msToZoned(ms,prefs.secondTz).slice(11,16)} ${esc(tzShort(prefs.secondTz).slice(0,3))}</span>`; }
    body+=`<div>${h?String(h).padStart(2,'0')+':00':''}${h?second:''}</div>`;
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
    const fls=filt.showFlights?[keyAdd(k,-1),k,keyAdd(k,1)].flatMap(fk=>flightWindows(fk)).filter(w=>w.e>d0&&w.s<d1).map(w=>({kind:'flight', s:w.s, e:w.e, f:w.f, k:w.k, title:'✈ '+code(w.f.from)+' → '+code(w.f.to)})):[];
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
    return `<div class="ev flt" style="top:${top}px;height:${h}px;left:calc(${left}% + 2px);width:calc(${w}% - 4px)" data-flk="${o.k}" tabindex="0" role="button" aria-label="Flight ${esc(f.from)} to ${esc(f.to)}, ${esc(flightLabel(f))}" title="${esc(f.from)} → ${esc(f.to)} · ${esc(flightLabel(f))}${f.note?' · '+esc(f.note):''} — click to edit">
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
    for(const f of (filt.showFlights?(e.flights||[]):[])) rows.push(`<div class="agi" style="cursor:default"><span class="t">${esc(flightDisp(k,f))}</span><div class="b" style="--c:var(--accent)"><span class="ti">✈ ${esc(f.from)} → ${esc(f.to)}</span>${f.note?`<div class="s">${esc(f.note)}</div>`:''}</div></div>`);
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
  $$('.ev.flt',body).forEach(el=>{ el.onclick=()=>openDrawer(el.dataset.flk); el.onkeydown=e=>{ if(e.key==='Enter') openDrawer(el.dataset.flk); }; el.addEventListener('pointerdown',e=>e.stopPropagation()); });
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
/* tiny Markdown: **bold**, *italic*, - lists, links */
function md(s){
  const inl=t=>esc(t).replace(/\*\*(.+?)\*\*/g,'<b>$1</b>').replace(/\*(.+?)\*/g,'<i>$1</i>').replace(/\[(.+?)\]\((https?:\/\/[^\s)]+)\)/g,'<a href="$2" target="_blank" rel="noopener">$1</a>').replace(/(^|\s)(https?:\/\/[^\s<]+)/g,'$1<a href="$2" target="_blank" rel="noopener">$2</a>');
  const out=[]; let ul=false;
  for(const line of String(s).split('\n')){
    const m=/^\s*[-*]\s+(.*)/.exec(line);
    if(m){ if(!ul){ out.push('<ul>'); ul=true; } out.push('<li>'+inl(m[1])+'</li>'); }
    else { if(ul){ out.push('</ul>'); ul=false; } if(line.trim()) out.push('<p>'+inl(line)+'</p>'); }
  }
  if(ul) out.push('</ul>');
  return out.join('');
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
    <textarea class="full" name="notes" rows="4" placeholder="Agenda, meeting notes… (supports **bold**, *italic*, - lists, links)" ${ro?'disabled':''}>${esc(e.notes)}</textarea>
    <div class="full md hint" id="evMd"></div>
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
  const notes=$('[name=notes]',F); const drawMd=()=>{ $('#evMd',d).innerHTML=notes.value?md(notes.value):''; }; notes.oninput=drawMd; drawMd();
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
    <label>Notes</label><textarea name="notes" rows="3" ${ro?'disabled':''}>${esc(t.notes)}</textarea>
    <label>Tags</label><input type="text" name="tags" value="${esc(t.tags.join(', '))}" placeholder="e.g. drafting" ${ro?'disabled':''}>
    <label>Privacy</label><label class="hint"><input type="checkbox" name="private" ${t.private?'checked':''} ${ro?'disabled':''}> Private — hide from viewers (needs the Personal passphrase)</label>
  </form>
  <div class="dacts">
    ${!isNew&&editable?'<button data-a="del" class="danger">Delete</button><button data-a="dup">Duplicate</button><button data-a="conv">Convert to event</button><button data-a="tpl">Save as template</button>':''}
    ${!isNew&&editable&&isOpen(orig)?'<button data-a="done">✓ Mark done</button>':''}
    <span class="sp"></span><button data-close>${ro?'Close':'Cancel'}</button>${editable?'<button class="primary" data-a="save">Save</button>':''}
  </div>`, {size:'mid', focus:'[name=title]'});
  const F=$('form',d);
  bindCalProj(F); bindRR(F);
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
function openProjectPage(id){
  const draw=()=>{
    const p=proj(id); if(!p){ closeDialog(dd); return; }
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
    dd.querySelector('.box').innerHTML=`<div class="dhd"><span class="dotc" style="background:${esc(p.color)};width:16px;height:16px"></span><h2>${esc(p.icon||'')} ${esc(p.name)}</h2>
      <span class="tag">${esc(c.name)}</span><span class="tag">${PROJ_STATUS[p.status]}</span>${editable?'<button data-pedit>✎ Edit</button>':''}<button data-prep>📊 Report</button><button class="ghost" data-close>✕</button></div>
      <div class="pp-head">${p.dueDate?`<span class="hint">Due ${esc(fmtShort(fromKey(p.dueDate)))}</span>`:''}<div style="flex:1;min-width:180px"><div class="hint">${st.done} of ${st.done+st.open} tasks done</div><div class="progress"><i style="width:${Math.round(st.progress*100)}%"></i></div></div>${budget}</div>
      ${p.description?`<p class="hint">${esc(p.description)}</p>`:''}
      <div class="tabs">${[['tasks','Tasks'],['timeline','Timeline'],['stats','Stats']].map(([k,l])=>`<button data-ptab="${k}" class="${ppTab===k?'on':''}">${l}</button>`).join('')}</div>${body}`;
    const B=dd.querySelector('.box');
    $$('[data-close]',B).forEach(b=>b.onclick=()=>closeDialog(dd));
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
  const dd=openDialog('',{size:'wide', refresh:()=>draw()});
  draw();
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
let _bc=null; try{ _bc=new BroadcastChannel('wtc'); _bc.onmessage=e=>{ if(e.data==='notif') renderBell(); if(e.data==='dismiss') syncToasts(); }; }catch(e){}
function isLeader(){
  const l=jget('wtc-leader',null), now=Date.now();
  if(!l || l.id===TAB_ID || now-l.ts>25000){ jset('wtc-leader',{id:TAB_ID,ts:now}); return jget('wtc-leader',{}).id===TAB_ID; }
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
  if(it && it.type==='event') return openEventEditor(it.id,null,n.occ);
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
  if(!session || !data || !isLeader()) return;
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
      <label>Second time zone</label><select data-p="secondTz"><option value="">none</option>${TZ_LIST.map(z=>`<option ${prefs.secondTz===z?'selected':''}>${z}</option>`).join('')}</select>
      <label>Offline copy</label><label class="hint"><input type="checkbox" data-p="offlineCopy" ${prefs.offlineCopy?'checked':''}> Keep a copy on this device so the calendar opens without internet (read-only). Don’t use on shared computers.</label>
      <label>Install</label><div class="hint">${installHint()}</div></form>`;
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
  try{ rows = (await api('users')).users.map(a=>({orig:a.user, user:a.user, role:a.role, pass:''})); }
  catch(e){ if(e.status!==401) alert(e.message); return; }
  let d;
  const draw = ()=>{
    const html=`<div class="dhd"><h2>👥 User accounts</h2><button class="ghost" data-close>✕</button></div>
      <p class="hint"><b>Editors</b> can read and change the calendar; <b>viewers</b> can only read it. Leave the password empty to keep the current one. New accounts need a password (at least 6 characters). Anyone whose password you change, or whose account you remove, is signed out. Personal items are protected separately by the passphrase (⚙ Settings → Calendars &amp; privacy).</p>
      <table class="acc"><thead><tr><th>Username</th><th style="width:120px">Role</th><th>New password</th><th></th></tr></thead><tbody>
      ${rows.map((a,i)=>`<tr data-i="${i}">
        <td><input type="text" data-f="user" value="${esc(a.user)}" autocomplete="off" autocapitalize="off" spellcheck="false" aria-label="Username"></td>
        <td><select data-f="role" aria-label="Role"><option value="editor" ${a.role==='editor'?'selected':''}>Editor</option><option value="viewer" ${a.role==='viewer'?'selected':''}>Viewer</option></select></td>
        <td><input type="password" data-f="pass" value="${esc(a.pass)}" autocomplete="new-password" placeholder="${a.orig?'unchanged':'password'}" aria-label="New password"></td>
        <td><button class="ghost" data-del="${i}" title="Remove account">🗑</button></td></tr>`).join('')}
      </tbody></table>
      <button id="accAdd">＋ Add account</button>
      <label class="hint" style="display:flex;gap:6px;align-items:center;margin-top:10px"><input type="checkbox" id="accShow"> Show passwords</label>
      <div class="lerr" id="accErr" style="margin-top:8px"></div>
      <div class="dacts"><span class="sp"></span><button data-close>Cancel</button><button class="primary" id="accSave">Save</button></div>`;
    if(!d) d=openDialog(html,{size:'mid'}); else { d.querySelector('.box').innerHTML=html; $$('[data-close]',d).forEach(b=>b.onclick=()=>closeDialog(d)); }
    $$('tr[data-i]',d).forEach(tr=>$$('[data-f]',tr).forEach(inp=>inp.oninput=inp.onchange=()=>{ rows[+tr.dataset.i][inp.dataset.f]=inp.value; }));
    $$('[data-del]',d).forEach(b=>b.onclick=()=>{ rows.splice(+b.dataset.del,1); draw(); });
    $('#accAdd',d).onclick=()=>{ rows.push({user:'', role:'viewer', pass:''}); draw(); };
    $('#accShow',d).onchange=e=>$$('input[data-f=pass]',d).forEach(i=>i.type=e.target.checked?'text':'password');
    $('#accSave',d).onclick=()=>saveAccounts(rows,d);
  };
  draw();
}
async function saveAccounts(rows,d){
  const err=t=>{ $('#accErr',d).textContent=t; };
  const mine=rows.find(r=>r.orig && r.orig.toLowerCase()===session.user.toLowerCase());
  if((!mine || mine.role!=='editor') && !confirm('You are removing your own editor access. You will not be able to change the calendar any more. Continue?')) return;
  const btn=$('#accSave',d); btn.disabled=true; btn.textContent='Saving…';
  try{
    const j = await api('users', {users:rows.map(r=>({orig:r.orig, user:(r.user||'').trim(), role:r.role, pass:r.pass||''}))});
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
    if(okType('events')) for(const e of pool.events) if(filt2(e) && hit([e.title,e.notes,e.location,e.tags.join(' '),e.attendees.join(' '),Object.values(e.exceptions).map(x=>x.title||'').join(' ')].join(' '))) results.push({g:'Events', label:(e.rrule?'🔁 ':'')+e.title, d:(e.allDay?fmtShort(fromKey(e.start.slice(0,10))):fmtShort(fromKey(e.start.slice(0,10)))+' '+e.start.slice(11,16))+(e._arch?' · archive':''), c:itemColor(e), go:()=>{ if(e._arch) return; openEventEditor(e.id); }, k:e.start});
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
    if(_dlgStack.length){ closeDialog(); return; }
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

/* ---------- start ---------- */
bindTop();
if('serviceWorker' in navigator && location.protocol==='https:') navigator.serviceWorker.register('sw.js').catch(()=>{});
boot();

</script>
</body>
</html>
