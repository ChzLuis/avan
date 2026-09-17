<x-app-layout>
<x-slot name="slot">
<style>
:root {
    --av-bg:    #0f1117;
    --av-surf:  #1a1d27;
    --av-bord:  #2a2d3e;
    --av-txt:   #e2e8f0;
    --av-muted: #64748b;
    --av-green: #00b26b;
    --av-blue:  #3b82f6;
    --av-amber: #f59e0b;
    --av-red:   #ef4444;
    --av-purple:#8b5cf6;
    --av-orange:#f97316;
}
*{box-sizing:border-box;}

/* ─── SHELL ─────────────────────────────────────────── */
.av-shell{display:flex;flex-direction:column;width:100%;height:100%;background:var(--av-bg);overflow:hidden;font-family:-apple-system,BlinkMacSystemFont,'Inter','Segoe UI',sans-serif;}

/* ─── FIRMA ──────────────────────────────────────────── */
.av-firma{display:flex;align-items:center;background:var(--av-surf);border-bottom:1px solid var(--av-bord);height:46px;padding:0 8px;gap:0;flex-shrink:0;overflow-x:auto;overflow-y:hidden;}
.av-firma::-webkit-scrollbar{display:none;}
.av-fi{display:flex;align-items:center;gap:7px;padding:0 14px;border-right:1px solid var(--av-bord);height:100%;white-space:nowrap;flex-shrink:0;}
.av-fi:last-child{border-right:none;}
.av-fi-lbl{font-size:9px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--av-muted);line-height:1;}
.av-fi-val{font-size:13px;font-weight:800;color:var(--av-txt);line-height:1;}
.av-fi-val.g{color:var(--av-green);}
.av-fi-val.b{color:var(--av-blue);}
.av-fi-val.r{color:var(--av-red);}
.av-fi-val.am{color:var(--av-amber);}
.av-score-ring{width:30px;height:30px;border-radius:50%;border:2px solid var(--av-green);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;color:var(--av-green);flex-shrink:0;}
.av-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;}
.av-dot.g{background:var(--av-green);box-shadow:0 0 5px var(--av-green);}
.av-dot.am{background:var(--av-amber);box-shadow:0 0 5px var(--av-amber);animation:av-pulse 1.8s infinite;}
.av-dot.r{background:var(--av-red);box-shadow:0 0 5px var(--av-red);animation:av-pulse 1s infinite;}
@keyframes av-pulse{0%,100%{opacity:1}50%{opacity:.3}}
.av-mode-btn{padding:4px 11px;border-radius:6px;border:1px solid var(--av-bord);background:transparent;color:var(--av-muted);font-size:10px;font-weight:700;cursor:pointer;letter-spacing:.4px;transition:all .15s;}
.av-mode-btn.on-op{background:rgba(59,130,246,.15);border-color:rgba(59,130,246,.5);color:#60a5fa;}
.av-mode-btn.on-ds{background:rgba(139,92,246,.15);border-color:rgba(139,92,246,.5);color:#a78bfa;}
.av-icon-btn{padding:4px 9px;border-radius:6px;border:1px solid var(--av-bord);background:transparent;color:var(--av-muted);font-size:14px;cursor:pointer;transition:all .15s;}
.av-icon-btn:hover{color:var(--av-txt);border-color:rgba(255,255,255,.2);}
.av-select{background:var(--av-surf);border:1px solid var(--av-bord);border-radius:6px;padding:4px 8px;color:var(--av-txt);font-size:11px;font-weight:600;outline:none;cursor:pointer;}

/* ─── CUERPO ─────────────────────────────────────────── */
.av-body{display:flex;flex:1;overflow:hidden;}

/* ─── PALETA ─────────────────────────────────────────── */
.av-palette{width:0;overflow:hidden;background:var(--av-surf);border-right:1px solid var(--av-bord);transition:width .2s;flex-shrink:0;display:flex;flex-direction:column;}
.av-palette.open{width:190px;overflow-y:auto;}
.av-pal-hd{padding:8px 12px;font-size:9px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--av-muted);border-bottom:1px solid var(--av-bord);flex-shrink:0;background:rgba(0,0,0,.15);}
.av-pal-section{padding:6px 8px 3px;font-size:8px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:#3d4558;}
.av-pal-item{padding:7px 10px;border-bottom:1px solid rgba(255,255,255,.03);cursor:grab;display:flex;align-items:center;gap:8px;font-size:11px;color:var(--av-txt);transition:background .12s;user-select:none;}
.av-pal-item:hover{background:rgba(59,130,246,.08);color:#60a5fa;}
.av-pal-item:active{cursor:grabbing;}
/* Preview de forma en la paleta */
.av-pal-shape{width:28px;height:20px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.av-pal-shape .sq{width:20px;height:16px;border-radius:3px;border:2px solid #475569;}
.av-pal-shape .sq-sm{width:14px;height:14px;border-radius:3px;border:2px solid #475569;}
.av-pal-shape .rect{width:26px;height:14px;border-radius:3px;border:2px solid #475569;}
.av-pal-shape .rect-lg{width:26px;height:12px;border-radius:2px;border:2px solid #475569;}
.av-pal-shape .circle{width:18px;height:18px;border-radius:50%;border:2px solid #475569;}
.av-pal-shape .circle-lg{width:22px;height:22px;border-radius:50%;border:2px solid #475569;}
.av-pal-shape .bar{width:26px;height:10px;border-radius:2px;border:2px solid #475569;}
.av-pal-shape .zone{width:26px;height:18px;border-radius:4px;border:2px dashed #475569;opacity:.7;}
.av-pal-shape .star{font-size:14px;color:#f59e0b;}

/* ─── TOOLBAR ────────────────────────────────────────── */
.av-toolbar{width:44px;background:var(--av-surf);border-right:1px solid var(--av-bord);display:flex;flex-direction:column;align-items:center;padding:10px 0;gap:2px;flex-shrink:0;}
.av-tool{width:34px;height:34px;border-radius:8px;border:1px solid transparent;background:transparent;color:var(--av-muted);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:15px;transition:all .12s;position:relative;}
.av-tool:hover,.av-tool.on{background:rgba(255,255,255,.06);color:var(--av-txt);border-color:var(--av-bord);}
.av-tool.on{background:rgba(59,130,246,.12);border-color:var(--av-blue);color:var(--av-blue);}
.av-tool.merge-on{background:rgba(139,92,246,.15);border-color:var(--av-purple);color:var(--av-purple);}
.av-tool-tip{position:absolute;left:42px;background:#1e2130;color:var(--av-txt);font-size:10px;font-weight:600;padding:3px 7px;border-radius:5px;white-space:nowrap;border:1px solid var(--av-bord);pointer-events:none;opacity:0;transition:opacity .1s;z-index:200;}
.av-tool:hover .av-tool-tip{opacity:1;}
.av-tool-sep{width:20px;height:1px;background:var(--av-bord);margin:3px 0;}

/* ─── CANVAS ─────────────────────────────────────────── */
.av-canvas-wrap{flex:1;overflow:auto;position:relative;background:radial-gradient(circle at 1px 1px,rgba(255,255,255,.035) 1px,transparent 0) 0 0/22px 22px;}
.av-canvas{position:relative;min-width:1600px;min-height:1000px;transform-origin:0 0;}

/* ─── ZONA (contenedor) ──────────────────────────────── */
.av-zone-obj{
    position:absolute;
    border-radius:12px;
    border:2px dashed rgba(255,255,255,.15);
    background:rgba(255,255,255,.025);
    cursor:default;
    display:flex;
    flex-direction:column;
    box-sizing:border-box;
    user-select:none;
    transition:border-color .15s;
}
.av-zone-obj:hover{border-color:rgba(255,255,255,.28);}
.av-zone-obj.sel{border-color:rgba(139,92,246,.7);background:rgba(139,92,246,.05);}
.av-zone-lbl{padding:8px 12px;font-size:10px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;color:rgba(255,255,255,.4);}
.av-zone-obj.drag{opacity:.6;z-index:50;}
.av-zone-resize{position:absolute;bottom:3px;right:3px;width:10px;height:10px;border-radius:2px;background:rgba(139,92,246,.6);cursor:se-resize;opacity:0;transition:opacity .15s;}
.av-zone-obj.sel .av-zone-resize{opacity:1;}

/* ─── OBJETO OPERATIVO ───────────────────────────────── */
.av-obj{
    position:absolute;
    border:2px solid rgba(255,255,255,.1);
    background:var(--av-surf);
    cursor:pointer;
    user-select:none;
    transition:border-color .12s,box-shadow .12s;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    box-sizing:border-box;
}
/* Formas */
.av-obj.shape-circle{border-radius:50%!important;}
.av-obj.shape-square{border-radius:8px!important;}
.av-obj.shape-rect{border-radius:6px!important;}
.av-obj.shape-rect-lg{border-radius:6px!important;}
.av-obj.shape-bar{border-radius:4px!important;}
.av-obj.shape-default{border-radius:10px!important;}

.av-obj:hover{border-color:rgba(255,255,255,.3);box-shadow:0 4px 18px rgba(0,0,0,.4);z-index:5;}
.av-obj.sel{border-color:var(--av-blue)!important;box-shadow:0 0 0 2px rgba(59,130,246,.3),0 4px 18px rgba(0,0,0,.4);z-index:15;}
.av-obj.drag{opacity:.75;box-shadow:0 12px 36px rgba(0,0,0,.6);z-index:50;cursor:grabbing;}
.av-obj.merge-sel{border-color:var(--av-purple)!important;box-shadow:0 0 0 3px rgba(139,92,246,.4)!important;}
.av-obj.merged{border-style:dashed!important;}

/* Barra de estado top */
.av-obj-sbar{height:3px;width:100%;flex-shrink:0;}
.av-obj.shape-circle .av-obj-sbar{display:none;}

/* Body */
.av-obj-body{flex:1;padding:5px 7px 4px;display:flex;flex-direction:column;gap:2px;overflow:hidden;}
.av-obj.shape-circle .av-obj-body{padding:4px;align-items:center;justify-content:center;text-align:center;}

/* Header */
.av-obj-hdr{display:flex;align-items:flex-start;gap:4px;}
.av-obj.shape-circle .av-obj-hdr{flex-direction:column;align-items:center;gap:2px;}
.av-obj-icon{font-size:13px;line-height:1;flex-shrink:0;margin-top:1px;}
.av-obj.shape-circle .av-obj-icon{font-size:16px;margin-top:0;}
.av-obj-lbl{font-size:10px;font-weight:800;color:var(--av-txt);line-height:1.2;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;letter-spacing:-.1px;}
.av-obj.shape-circle .av-obj-lbl{font-size:9px;white-space:normal;line-height:1.2;}
.av-obj-alrt{background:var(--av-red);color:#fff;font-size:8px;font-weight:900;border-radius:99px;padding:1px 4px;flex-shrink:0;}

/* Info rows */
.av-obj-timer{font-size:9px;font-weight:700;color:var(--av-amber);font-variant-numeric:tabular-nums;}
.av-obj-resp{font-size:9px;color:var(--av-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.av-obj-amount{font-size:9px;font-weight:800;color:var(--av-green);}
.av-obj.shape-circle .av-obj-timer,.av-obj.shape-circle .av-obj-resp,.av-obj.shape-circle .av-obj-amount{font-size:8px;}

/* Footer */
.av-obj-foot{border-top:1px solid rgba(255,255,255,.05);padding:3px 7px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.av-obj.shape-circle .av-obj-foot{display:none;}
.av-obj-pill{font-size:7px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;padding:2px 5px;border-radius:99px;background:rgba(255,255,255,.05);}
.av-obj-cap{font-size:8px;color:#334155;}

/* Dot de estado para mesas circulares */
.av-obj-status-dot{width:8px;height:8px;border-radius:50%;margin:0 auto;flex-shrink:0;}

/* Resize handle */
.av-resize-handle{position:absolute;bottom:2px;right:2px;width:8px;height:8px;background:var(--av-blue);border-radius:2px;cursor:se-resize;opacity:0;transition:opacity .15s;}
.av-obj.sel .av-resize-handle{opacity:1;}

/* Badge "unido con" */
.av-merge-badge{position:absolute;top:-8px;left:50%;transform:translateX(-50%);background:var(--av-purple);color:#fff;font-size:8px;font-weight:900;padding:1px 6px;border-radius:99px;white-space:nowrap;pointer-events:none;z-index:20;}

/* ─── PANEL LATERAL ──────────────────────────────────── */
.av-panel{width:0;overflow:hidden;background:var(--av-surf);border-left:1px solid var(--av-bord);display:flex;flex-direction:column;transition:width .18s ease;flex-shrink:0;}
.av-panel.open{width:300px;}
.av-panel-hdr{padding:12px 14px;border-bottom:1px solid var(--av-bord);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.av-panel-title{font-size:12px;font-weight:800;color:var(--av-txt);}
.av-panel-sub{font-size:9px;color:var(--av-muted);margin-top:1px;}
.av-close-btn{width:26px;height:26px;border-radius:7px;border:1px solid var(--av-bord);background:transparent;color:var(--av-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;transition:all .12s;}
.av-close-btn:hover{background:rgba(255,255,255,.06);color:var(--av-txt);}
.av-panel-tabs{display:flex;border-bottom:1px solid var(--av-bord);flex-shrink:0;}
.av-ptab{flex:1;padding:9px 6px;font-size:10px;font-weight:700;color:var(--av-muted);cursor:pointer;text-align:center;border-bottom:2px solid transparent;transition:all .12s;letter-spacing:.2px;}
.av-ptab.on{color:var(--av-blue);border-bottom-color:var(--av-blue);}
.av-panel-body{flex:1;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:12px;}
.av-panel-body::-webkit-scrollbar{width:4px;}
.av-panel-body::-webkit-scrollbar-thumb{background:#2a2d3e;border-radius:99px;}

/* Panel propiedades diseño */
.av-prop-row{margin-bottom:10px;}
.av-prop-lbl{font-size:9px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:var(--av-muted);margin-bottom:5px;}

/* Selector de forma */
.av-shape-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:5px;}
.av-shape-chip{padding:7px 4px;border-radius:8px;border:1px solid var(--av-bord);background:transparent;color:var(--av-muted);font-size:9px;font-weight:700;cursor:pointer;text-align:center;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .12s;}
.av-shape-chip:hover{border-color:rgba(255,255,255,.2);color:var(--av-txt);}
.av-shape-chip.on{border-color:var(--av-blue);background:rgba(59,130,246,.12);color:#60a5fa;}

/* Selector capacidad */
.av-cap-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:4px;}
.av-cap-chip{padding:5px 2px;border-radius:7px;border:1px solid var(--av-bord);background:transparent;color:var(--av-muted);font-size:10px;font-weight:800;cursor:pointer;text-align:center;transition:all .12s;}
.av-cap-chip:hover{border-color:rgba(255,255,255,.2);color:var(--av-txt);}
.av-cap-chip.on{border-color:var(--av-green);background:rgba(0,178,107,.12);color:#00b26b;}

/* Selector color */
.av-color-grid{display:flex;gap:6px;flex-wrap:wrap;}
.av-color-dot{width:22px;height:22px;border-radius:50%;cursor:pointer;transition:transform .12s;border:2px solid transparent;}
.av-color-dot:hover{transform:scale(1.2);}
.av-color-dot.on{border-color:#fff;transform:scale(1.15);}

/* Acciones diseño */
.av-sec-ttl{font-size:9px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;color:var(--av-muted);margin-bottom:6px;}
.av-st-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:5px;}
.av-st-chip{padding:6px 4px;border-radius:7px;border:1px solid var(--av-bord);background:transparent;color:var(--av-muted);font-size:9px;font-weight:700;cursor:pointer;text-align:center;line-height:1.4;transition:all .12s;}
.av-st-chip:hover{border-color:rgba(255,255,255,.2);color:var(--av-txt);}
.av-st-chip.on{color:#fff!important;border-color:transparent;}
.av-info-row{display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:11px;}
.av-info-row:last-child{border-bottom:none;}
.av-info-k{color:var(--av-muted);}
.av-info-v{font-weight:700;color:var(--av-txt);}
.av-info-v.g{color:var(--av-green);}
.av-alert-item{display:flex;align-items:flex-start;gap:6px;padding:7px 9px;background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.18);border-radius:7px;font-size:10px;color:#fca5a5;margin-bottom:5px;}
.av-ev-item{display:flex;gap:8px;align-items:flex-start;margin-bottom:7px;}
.av-ev-dot{width:7px;height:7px;border-radius:50%;background:var(--av-muted);flex-shrink:0;margin-top:3px;}
.av-ev-lbl{font-size:10px;color:var(--av-txt);line-height:1.4;}
.av-ev-time{font-size:9px;color:var(--av-muted);margin-top:1px;}
.av-panel-foot{padding:10px 14px;border-top:1px solid var(--av-bord);flex-shrink:0;}
.av-act-btn{width:100%;padding:8px 12px;border-radius:8px;border:1px solid var(--av-bord);background:rgba(255,255,255,.03);color:var(--av-txt);font-size:11px;font-weight:700;cursor:pointer;text-align:left;display:flex;align-items:center;gap:7px;margin-bottom:5px;transition:all .12s;}
.av-act-btn:hover{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.15);}
.av-act-btn.danger{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.25);color:#fca5a5;}
.av-act-btn.ok{background:rgba(0,178,107,.08);border-color:rgba(0,178,107,.25);color:#00b26b;}
.av-input{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--av-bord);border-radius:7px;padding:7px 10px;color:var(--av-txt);font-size:11px;outline:none;transition:border-color .12s;font-family:inherit;}
.av-input:focus{border-color:var(--av-blue);}
.av-input::placeholder{color:var(--av-muted);}
.av-select2{width:100%;background:#1a1d27;border:1px solid var(--av-bord);border-radius:7px;padding:7px 10px;color:var(--av-txt);font-size:11px;outline:none;font-family:inherit;}
.av-qr-grid{display:grid;grid-template-columns:1fr 1fr;gap:5px;}
.av-qr-btn{padding:7px 8px;border-radius:7px;border:1px solid var(--av-bord);background:rgba(255,255,255,.02);color:var(--av-muted);font-size:9px;font-weight:700;cursor:pointer;text-align:left;display:flex;align-items:center;gap:5px;transition:all .12s;}
.av-qr-btn:hover{border-color:var(--av-blue);color:#93c5fd;background:rgba(59,130,246,.08);}
.av-row{display:flex;align-items:center;gap:6px;}
.av-ml-auto{margin-left:auto;}

/* ─── MODAL ──────────────────────────────────────────── */
.av-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:9000;align-items:center;justify-content:center;}
.av-modal-bg.open{display:flex;}
.av-modal{background:#1a1d27;border:1px solid var(--av-bord);border-radius:14px;padding:22px;width:400px;max-width:92vw;box-shadow:0 24px 60px rgba(0,0,0,.6);}
.av-modal-ttl{font-size:14px;font-weight:800;color:var(--av-txt);margin:0 0 14px;}
.av-modal-lbl{font-size:10px;font-weight:700;color:var(--av-muted);margin-bottom:4px;letter-spacing:.3px;}
.av-modal-row{margin-top:10px;}
.av-modal-ft{display:flex;gap:7px;margin-top:14px;}
.av-btn-cancel{flex:1;padding:8px;border-radius:7px;border:1px solid var(--av-bord);background:transparent;color:var(--av-muted);font-size:11px;font-weight:700;cursor:pointer;}
.av-btn-confirm{flex:2;padding:8px;border-radius:7px;border:none;background:var(--av-blue);color:#fff;font-size:11px;font-weight:800;cursor:pointer;}

/* ─── EMPTY / TOAST ──────────────────────────────────── */
.av-empty{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;pointer-events:none;}
.av-empty-icon{font-size:44px;opacity:.15;}
.av-empty-txt{font-size:12px;color:var(--av-muted);text-align:center;line-height:1.6;}
.av-toast-c{position:fixed;bottom:18px;right:18px;z-index:9999;display:flex;flex-direction:column;gap:6px;}
.av-toast{background:#1e2130;border:1px solid var(--av-bord);border-radius:9px;padding:8px 14px;font-size:11px;font-weight:700;color:var(--av-txt);display:flex;align-items:center;gap:7px;box-shadow:0 8px 24px rgba(0,0,0,.45);animation:av-ti .18s ease;min-width:180px;}
@keyframes av-ti{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.av-toast.ok{border-left:3px solid var(--av-green);}
.av-toast.err{border-left:3px solid var(--av-red);}

/* ─── MERGE BANNER ───────────────────────────────────── */
.av-merge-banner{position:fixed;bottom:70px;left:50%;transform:translateX(-50%);background:#7c3aed;color:#fff;padding:8px 20px;border-radius:12px;font-size:12px;font-weight:700;display:none;align-items:center;gap:10px;z-index:8000;box-shadow:0 8px 24px rgba(124,58,237,.4);}
.av-merge-banner.open{display:flex;}
</style>

<div class="av-shell" x-data="avanMapa()" x-init="init()">

{{-- ══ FIRMA AVAN ══ --}}
<div class="av-firma">
    <div class="av-fi">
        <div class="av-score-ring" :style="'border-color:'+scoreColor(score)+';color:'+scoreColor(score)">
            <span x-text="score"></span>
        </div>
        <div><div class="av-fi-lbl">AVAN Score</div></div>
    </div>
    <div class="av-fi">
        <div class="av-dot" :class="score>=80?'g':(score>=50?'am':'r')"></div>
        <div>
            <div class="av-fi-lbl">Estado</div>
            <div class="av-fi-val" x-text="score>=80?'Operando bien':(score>=50?'Con alertas':'Crítico')"></div>
        </div>
    </div>
    <div class="av-fi">
        <div><div class="av-fi-lbl">Activos</div><div class="av-fi-val b" x-text="sum.ocupado+' / '+sum.total"></div></div>
    </div>
    <div class="av-fi">
        <div><div class="av-fi-lbl">Disponibles</div><div class="av-fi-val g" x-text="sum.libre"></div></div>
    </div>
    <div class="av-fi">
        <div><div class="av-fi-lbl">Alertas</div><div class="av-fi-val" :class="sum.alerta>0?'r':''" x-text="sum.alerta"></div></div>
    </div>
    <div class="av-fi">
        <div><div class="av-fi-lbl">Consumo activo</div><div class="av-fi-val g" x-text="'S/ '+(sum.total_amount||0).toFixed(2)"></div></div>
    </div>
    <div class="av-fi av-ml-auto" style="gap:6px;">
        @if($maps->isNotEmpty())
        <select class="av-select" @change="switchMap($event.target.value)">
            @foreach($maps as $m)
            <option value="{{ $m->id }}" {{ $map && $map->id===$m->id?'selected':'' }}>{{ $m->name }}</option>
            @endforeach
        </select>
        @endif
        <button @click="showMapModal=true" style="padding:4px 9px;border-radius:6px;border:1px solid rgba(59,130,246,.4);background:rgba(59,130,246,.1);color:#60a5fa;font-size:10px;font-weight:800;cursor:pointer;">+ Mapa</button>
    </div>
    <div class="av-fi" style="gap:4px;">
        <button class="av-mode-btn" :class="mode==='operate'?'on-op':''" @click="setMode('operate')">OPERAR</button>
        <button class="av-mode-btn" :class="mode==='design'?'on-ds':''" @click="setMode('design')">DISEÑAR</button>
    </div>
    <div class="av-fi">
        <button class="av-icon-btn" @click="load()" :style="loading?'animation:av-pulse 1s infinite':''">↻</button>
    </div>
</div>

{{-- ══ CUERPO ══ --}}
<div class="av-body">

    {{-- PALETA modo diseño --}}
    <div class="av-palette" :class="mode==='design'?'open':''">
        <div class="av-pal-hd">Constructor de plano</div>

        {{-- Mesas --}}
        <div class="av-pal-section">MESAS</div>
        <template x-for="t in tiposMesas" :key="t.type+'_'+t.shape">
            <div class="av-pal-item" draggable="true" @dragstart="palDrag($event,t)">
                <div class="av-pal-shape">
                    <div :class="t.shapeClass"></div>
                </div>
                <div>
                    <div style="font-size:10px;font-weight:700;" x-text="t.label"></div>
                    <div style="font-size:9px;color:#475569;" x-text="t.sub"></div>
                </div>
            </div>
        </template>

        {{-- Zonas --}}
        <div class="av-pal-section" style="margin-top:4px;">ZONAS</div>
        <template x-for="t in tiposZonas" :key="t.type+'_'+t.shape">
            <div class="av-pal-item" draggable="true" @dragstart="palDrag($event,t)">
                <div class="av-pal-shape">
                    <div :class="t.shapeClass"></div>
                </div>
                <div>
                    <div style="font-size:10px;font-weight:700;" x-text="t.label"></div>
                    <div style="font-size:9px;color:#475569;" x-text="t.sub"></div>
                </div>
            </div>
        </template>

        {{-- Otros --}}
        <div class="av-pal-section" style="margin-top:4px;">OTROS OBJETOS</div>
        <template x-for="t in tiposOtros" :key="t.type">
            <div class="av-pal-item" draggable="true" @dragstart="palDrag($event,t)">
                <span x-text="t.icon" style="font-size:14px;width:28px;text-align:center;flex-shrink:0;"></span>
                <div>
                    <div style="font-size:10px;font-weight:700;" x-text="t.label"></div>
                    <div style="font-size:9px;color:#475569;" x-text="t.sub"></div>
                </div>
            </div>
        </template>
    </div>

    {{-- TOOLBAR --}}
    <div class="av-toolbar">
        <button class="av-tool" :class="tool==='sel'?'on':''" @click="tool='sel'">
            ↖<span class="av-tool-tip">Seleccionar</span>
        </button>
        <div class="av-tool-sep"></div>
        <button class="av-tool" :class="mergeMode?'merge-on':''" @click="toggleMerge()" x-show="mode==='design'">
            ⊞<span class="av-tool-tip">Unir mesas</span>
        </button>
        <button class="av-tool" @click="splitSelected()" x-show="mode==='design' && sel && sel.config?.merged_with?.length>0" style="color:#f97316;">
            ⊟<span class="av-tool-tip">Separar mesa</span>
        </button>
        <div class="av-tool-sep"></div>
        <button class="av-tool" @click="zoom=Math.min(2,+(zoom+.1).toFixed(1))">+<span class="av-tool-tip">Zoom +</span></button>
        <button class="av-tool" @click="zoom=Math.max(.3,+(zoom-.1).toFixed(1))">−<span class="av-tool-tip">Zoom −</span></button>
        <button class="av-tool" @click="zoom=1" style="font-size:9px;font-weight:800;">1:1<span class="av-tool-tip">Reset zoom</span></button>
        <div class="av-tool-sep"></div>
        <button class="av-tool" :class="onlyAlerts?'on':''" @click="onlyAlerts=!onlyAlerts">⚠<span class="av-tool-tip">Solo alertas</span></button>
    </div>

    {{-- CANVAS --}}
    <div class="av-canvas-wrap" @dragover.prevent @drop="canvasDrop($event)">
        <div class="av-canvas" id="avCanvas" :style="'transform:scale('+zoom+');transform-origin:0 0'">

            <div class="av-empty" x-show="objs.length===0 && !loading">
                <div class="av-empty-icon">🗺️</div>
                <div class="av-empty-txt">
                    Mapa vacío<br>
                    <template x-if="mode==='design'"><span>Arrastra objetos desde el panel izquierdo</span></template>
                    <template x-if="mode!=='design'"><span>Activa <strong style="color:#a78bfa">DISEÑAR</strong> para construir el plano</span></template>
                </div>
            </div>

            {{-- ZONAS primero (detrás) --}}
            <template x-for="o in zonaObjs" :key="'z'+o.id">
                <div class="av-zone-obj"
                     :class="(selId===o.id?'sel ':'') + (o.id===dragId?'drag':'')"
                     :style="zoneStyle(o)"
                     @click.stop="selObj(o)"
                     @mousedown="mode==='design'?startDrag($event,o):null">
                    <div class="av-zone-lbl" x-text="o.label"></div>
                    <div class="av-zone-resize" x-show="mode==='design' && selId===o.id"
                         @mousedown.stop="startResize($event,o)"></div>
                </div>
            </template>

            {{-- OBJETOS operativos encima --}}
            <template x-for="o in mesaObjs" :key="o.id">
                <div class="av-obj"
                     :class="objClasses(o)"
                     :style="objStyle(o)"
                     @click.stop="clickObj(o)"
                     @mousedown="startDrag($event,o)">

                    {{-- Badge merge --}}
                    <template x-if="o.config?.merged_with?.length>0">
                        <div class="av-merge-badge" x-text="'+ '+(o.config.merged_with.join(', '))"></div>
                    </template>

                    {{-- Barra de estado (no en círculos) --}}
                    <div class="av-obj-sbar" :style="'background:'+objColor(o)"></div>

                    <div class="av-obj-body">
                        {{-- Dot de estado para circulares --}}
                        <template x-if="o.shape==='circle'">
                            <div class="av-obj-status-dot" :style="'background:'+objColor(o)+';box-shadow:0 0 5px '+objColor(o)"></div>
                        </template>

                        <div class="av-obj-hdr">
                            <span class="av-obj-icon" x-text="o.icon"></span>
                            <span class="av-obj-lbl"  x-text="o.label"></span>
                            <span class="av-obj-alrt" x-show="o.alerts_count>0" x-text="o.alerts_count"></span>
                        </div>

                        {{-- Timer con color urgencia --}}
                        <div class="av-obj-timer" x-show="o.elapsed_minutes>0"
                             :style="'color:'+timerColor(o.elapsed_minutes)"
                             x-text="'⏱ '+o.elapsed_label"></div>

                        <div class="av-obj-resp"   x-show="o.responsible"        x-text="'👤 '+(o.responsible?.name||'')"></div>
                        <div class="av-obj-amount" x-show="o.current_amount>0"   x-text="'S/ '+o.current_amount.toFixed(2)"></div>
                    </div>

                    <div class="av-obj-foot">
                        <span class="av-obj-pill" :style="'color:'+objColor(o)" x-text="stLbl(o.status)"></span>
                        <span class="av-obj-cap"  x-show="o.capacity" x-text="o.capacity+'p'"></span>
                    </div>

                    <div class="av-resize-handle" x-show="mode==='design' && selId===o.id && o.type!=='zona'"
                         @mousedown.stop="startResize($event,o)"></div>
                </div>
            </template>
        </div>
    </div>

    {{-- ══ PANEL LATERAL ══ --}}
    <div class="av-panel" :class="sel?'open':''">
        <template x-if="sel">
            <div style="display:flex;flex-direction:column;height:100%;overflow:hidden;">

                {{-- Header --}}
                <div class="av-panel-hdr">
                    <div class="av-row" style="gap:9px;">
                        <span style="font-size:22px;" x-text="sel.icon"></span>
                        <div>
                            <div class="av-panel-title" x-text="sel.label"></div>
                            <div class="av-panel-sub" x-text="(sel.zone||tipoLbl(sel.type)) + (sel.capacity?' · '+sel.capacity+'p':'')"></div>
                        </div>
                    </div>
                    <button class="av-close-btn" @click="closePanel()">✕</button>
                </div>

                {{-- Tabs: diferentes según modo --}}
                <div class="av-panel-tabs">
                    <template x-if="mode==='design'">
                        <div style="display:contents;">
                            <div class="av-ptab" :class="ptab==='props'?'on':''" @click="ptab='props'">Propiedades</div>
                            <div class="av-ptab" :class="ptab==='acciones'?'on':''" @click="ptab='acciones'">Acciones</div>
                        </div>
                    </template>
                    <template x-if="mode!=='design'">
                        <div style="display:contents;">
                            <div class="av-ptab" :class="ptab==='info'?'on':''"    @click="ptab='info'">Info</div>
                            <div class="av-ptab" :class="ptab==='alertas'?'on':''" @click="ptab='alertas'">
                                Alertas <span x-show="sel.alerts_count>0" style="background:#ef4444;color:#fff;font-size:7px;border-radius:99px;padding:1px 3px;margin-left:2px;" x-text="sel.alerts_count"></span>
                            </div>
                            <div class="av-ptab" :class="ptab==='hist'?'on':''"    @click="ptab='hist';loadHist()">Historial</div>
                        </div>
                    </template>
                </div>

                <div class="av-panel-body">

                    {{-- ═══ TAB PROPIEDADES (modo diseño) ═══ --}}
                    <div x-show="ptab==='props'">

                        {{-- Nombre --}}
                        <div class="av-prop-row">
                            <div class="av-prop-lbl">Nombre / ID</div>
                            <div class="av-row">
                                <input type="text" class="av-input" x-model="propLabel" @keyup.enter="saveProp()" placeholder="Ej: Mesa 4">
                                <button @click="saveProp()" style="padding:7px 10px;border-radius:7px;border:none;background:#3b82f6;color:#fff;font-size:10px;font-weight:800;cursor:pointer;flex-shrink:0;">OK</button>
                            </div>
                        </div>

                        {{-- Forma (solo para mesas) --}}
                        <div class="av-prop-row" x-show="sel.type==='mesa'">
                            <div class="av-prop-lbl">Forma de la mesa</div>
                            <div class="av-shape-grid">
                                <button class="av-shape-chip" :class="propShape==='square'?'on':''" @click="propShape='square';applyShape()">
                                    <div style="width:18px;height:16px;border-radius:3px;border:2px solid currentColor;"></div>
                                    <span>Cuadrada</span>
                                </button>
                                <button class="av-shape-chip" :class="propShape==='circle'?'on':''" @click="propShape='circle';applyShape()">
                                    <div style="width:18px;height:18px;border-radius:50%;border:2px solid currentColor;"></div>
                                    <span>Redonda</span>
                                </button>
                                <button class="av-shape-chip" :class="propShape==='rect'?'on':''" @click="propShape='rect';applyShape()">
                                    <div style="width:26px;height:14px;border-radius:3px;border:2px solid currentColor;"></div>
                                    <span>Rect.</span>
                                </button>
                                <button class="av-shape-chip" :class="propShape==='rect-lg'?'on':''" @click="propShape='rect-lg';applyShape()">
                                    <div style="width:26px;height:12px;border-radius:2px;border:2px solid currentColor;"></div>
                                    <span>Rect. lg</span>
                                </button>
                                <button class="av-shape-chip" :class="propShape==='bar'?'on':''" @click="propShape='bar';applyShape()">
                                    <div style="width:26px;height:8px;border-radius:2px;border:2px solid currentColor;"></div>
                                    <span>Barra</span>
                                </button>
                                <button class="av-shape-chip" :class="propShape==='default'?'on':''" @click="propShape='default';applyShape()">
                                    <div style="width:18px;height:18px;border-radius:6px;border:2px solid currentColor;"></div>
                                    <span>Normal</span>
                                </button>
                            </div>
                        </div>

                        {{-- Capacidad --}}
                        <div class="av-prop-row" x-show="sel.type!=='zona'">
                            <div class="av-prop-lbl">Capacidad (personas)</div>
                            <div class="av-cap-grid">
                                <template x-for="c in [2,4,6,8]" :key="c">
                                    <button class="av-cap-chip" :class="propCap===c?'on':''" @click="propCap=c;saveProp()">
                                        <span x-text="c+'p'"></span>
                                    </button>
                                </template>
                                <template x-for="c in [10,12,'VIP','∞']" :key="c">
                                    <button class="av-cap-chip" :class="propCap===c?'on':''" @click="propCap=c;saveProp()">
                                        <span x-text="c"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Zona a la que pertenece --}}
                        <div class="av-prop-row">
                            <div class="av-prop-lbl">Zona / Sector</div>
                            <input type="text" class="av-input" x-model="propZone" @keyup.enter="saveProp()" placeholder="Ej: Salón, Terraza, Barra">
                        </div>

                        {{-- Color de identificación --}}
                        <div class="av-prop-row">
                            <div class="av-prop-lbl">Color identificador</div>
                            <div class="av-color-grid">
                                <template x-for="c in palColors" :key="c.val">
                                    <div class="av-color-dot"
                                         :class="propColor===c.val?'on':''"
                                         :style="'background:'+c.val+';border-color:'+(propColor===c.val?'#fff':'transparent')+(propColor===c.val?';transform:scale(1.2)':'')"
                                         @click="propColor=c.val;saveProp()">
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Orientación --}}
                        <div class="av-prop-row" x-show="sel.type==='mesa' && propShape==='rect'">
                            <div class="av-prop-lbl">Orientación</div>
                            <div class="av-row">
                                <button @click="rotateObj(0)"  style="flex:1;padding:6px;border-radius:7px;border:1px solid var(--av-bord);background:transparent;color:var(--av-muted);font-size:10px;font-weight:700;cursor:pointer;">↔ Horizontal</button>
                                <button @click="rotateObj(90)" style="flex:1;padding:6px;border-radius:7px;border:1px solid var(--av-bord);background:transparent;color:var(--av-muted);font-size:10px;font-weight:700;cursor:pointer;">↕ Vertical</button>
                            </div>
                        </div>

                        {{-- Guardar --}}
                        <button class="av-act-btn ok" @click="saveProp()">💾 Guardar cambios</button>
                    </div>

                    {{-- ═══ TAB ACCIONES (modo diseño) ═══ --}}
                    <div x-show="ptab==='acciones'">
                        <div class="av-sec-ttl">Acciones</div>
                        <button class="av-act-btn" @click="duplicateObj()">📋 Duplicar objeto</button>
                        <button class="av-act-btn danger" @click="delObj()">🗑 Eliminar objeto</button>

                        <template x-if="sel.type==='mesa'">
                            <div style="margin-top:10px;">
                                <div class="av-sec-ttl">Unión de mesas</div>
                                <template x-if="sel.config?.merged_with?.length>0">
                                    <div>
                                        <div style="font-size:10px;color:#a78bfa;margin-bottom:8px;" x-text="'Unida con: '+sel.config.merged_with.join(', ')"></div>
                                        <button class="av-act-btn" @click="splitSelected()" style="border-color:rgba(249,115,22,.3);color:#fb923c;">⊟ Separar mesas</button>
                                    </div>
                                </template>
                                <template x-if="!sel.config?.merged_with?.length">
                                    <div style="font-size:10px;color:var(--av-muted);">Usa el botón ⊞ de la toolbar para unir esta mesa con otra.</div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- ═══ TAB INFO (modo operar) ═══ --}}
                    <div x-show="ptab==='info'">
                        <div class="av-sec-ttl">Estado</div>
                        <div class="av-st-grid">
                            <template x-for="s in stOpts(sel.type)" :key="s.v">
                                <button class="av-st-chip" :class="sel.status===s.v?'on':''"
                                        :style="sel.status===s.v?'background:'+s.c+';border-color:'+s.c:''"
                                        @click="chgStatus(s.v)">
                                    <span x-text="s.ic"></span><br><span x-text="s.l"></span>
                                </button>
                            </template>
                        </div>

                        <div style="margin-top:10px;">
                            <div class="av-sec-ttl">Detalles</div>
                            <div class="av-info-row"><span class="av-info-k">⏱ Tiempo activo</span><span class="av-info-v" :style="'color:'+timerColor(sel.elapsed_minutes||0)" x-text="sel.elapsed_label||'—'"></span></div>
                            <div class="av-info-row"><span class="av-info-k">💰 Consumo</span><span class="av-info-v g" x-text="'S/ '+(sel.current_amount||0).toFixed(2)"></span></div>
                            <div class="av-info-row"><span class="av-info-k">👤 Responsable</span><span class="av-info-v" x-text="sel.responsible?.name||'—'"></span></div>
                            <div class="av-info-row"><span class="av-info-k">📍 Zona</span><span class="av-info-v" x-text="sel.zone||'—'"></span></div>
                            <div class="av-info-row"><span class="av-info-k">👥 Capacidad</span><span class="av-info-v" x-text="sel.capacity||'—'"></span></div>
                            <template x-if="sel.config?.merged_with?.length>0">
                                <div class="av-info-row"><span class="av-info-k">⊞ Unida con</span><span class="av-info-v" style="color:#a78bfa;" x-text="sel.config.merged_with.join(', ')"></span></div>
                            </template>
                        </div>

                        <div style="margin-top:10px;">
                            <div class="av-sec-ttl">Asignar responsable</div>
                            <select class="av-select2" @change="asgnResp($event.target.value)">
                                <option value="">— Sin asignar —</option>
                                @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" :selected="sel.responsible?.id=={{ $emp->id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div style="margin-top:10px;">
                            <div class="av-sec-ttl">Actualizar consumo (S/)</div>
                            <div class="av-row">
                                <input type="number" step="0.01" min="0" class="av-input" x-model="amtInput" placeholder="0.00" @keyup.enter="updAmt()">
                                <button @click="updAmt()" style="padding:7px 12px;border-radius:7px;border:none;background:#00b26b;color:#fff;font-size:11px;font-weight:800;cursor:pointer;flex-shrink:0;">OK</button>
                            </div>
                        </div>
                    </div>

                    {{-- ═══ TAB ALERTAS ═══ --}}
                    <div x-show="ptab==='alertas'">
                        <div class="av-sec-ttl">Alertas activas</div>
                        <template x-if="sel.alerts.length===0">
                            <div style="font-size:11px;color:#475569;padding:8px 0;">Sin alertas ✓</div>
                        </template>
                        <template x-for="(a,i) in sel.alerts" :key="i">
                            <div class="av-alert-item">
                                <span>⚠</span>
                                <div style="flex:1;">
                                    <div x-text="a.text"></div>
                                    <div style="font-size:8px;color:#94a3b8;margin-top:2px;" x-text="a.at?new Date(a.at).toLocaleTimeString('es',{hour:'2-digit',minute:'2-digit'}):''"></div>
                                </div>
                            </div>
                        </template>
                        <button x-show="sel.alerts.length>0" @click="clearAlerts()" style="width:100%;margin-top:6px;padding:6px;border-radius:7px;border:1px solid rgba(239,68,68,.25);background:rgba(239,68,68,.07);color:#fca5a5;font-size:10px;font-weight:700;cursor:pointer;">Limpiar todas</button>
                        <div style="margin-top:12px;">
                            <div class="av-sec-ttl">Agregar alerta</div>
                            <div class="av-row">
                                <input type="text" class="av-input" x-model="alrtInput" placeholder="Ej: Cliente espera cuenta" @keyup.enter="addAlert()">
                                <button @click="addAlert()" style="padding:7px 11px;border-radius:7px;border:none;background:#ef4444;color:#fff;font-size:12px;font-weight:800;cursor:pointer;flex-shrink:0;">+</button>
                            </div>
                        </div>
                        <div style="margin-top:12px;">
                            <div class="av-sec-ttl">Solicitudes rápidas</div>
                            <div class="av-qr-grid">
                                <template x-for="q in quickReqs" :key="q.t">
                                    <button class="av-qr-btn" @click="crtReq(q.t,q.l)"><span x-text="q.ic"></span><span x-text="q.l"></span></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- ═══ TAB HISTORIAL ═══ --}}
                    <div x-show="ptab==='hist'">
                        <div class="av-sec-ttl">Eventos recientes</div>
                        <template x-if="hist.length===0"><div style="font-size:11px;color:#475569;padding:8px 0;">Sin eventos.</div></template>
                        <template x-for="ev in hist" :key="ev.id">
                            <div class="av-ev-item">
                                <div class="av-ev-dot"></div>
                                <div>
                                    <div class="av-ev-lbl" x-text="ev.type_label+(ev.status_to?' → '+ev.status_to:'')+(ev.note?': '+ev.note:'')"></div>
                                    <div class="av-ev-time" x-text="(ev.user||'Sistema')+' · '+ev.occurred_at"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>{{-- /panel-body --}}
            </div>
        </template>
    </div>
</div>{{-- /av-body --}}

{{-- Merge banner --}}
<div class="av-merge-banner" :class="mergeMode?'open':''">
    ⊞ Modo unión — seleccioná la segunda mesa
    <span x-show="mergeTarget" style="font-weight:900;color:#e9d5ff;" x-text="'+ '+mergeTarget?.label"></span>
    <button @click="mergeMode=false;mergeTarget=null" style="background:rgba(255,255,255,.2);border:none;color:#fff;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">Cancelar</button>
</div>

{{-- Modal nuevo mapa --}}
<div class="av-modal-bg" :class="showMapModal?'open':''">
    <div class="av-modal">
        <div class="av-modal-ttl">Nuevo mapa</div>
        <div class="av-modal-lbl">Nombre</div>
        <input type="text" class="av-input" x-model="newMapName" placeholder="Ej: Planta baja, Terraza" @keyup.enter="createMap()">
        <div class="av-modal-ft">
            <button class="av-btn-cancel" @click="showMapModal=false">Cancelar</button>
            <button class="av-btn-confirm" @click="createMap()">Crear</button>
        </div>
    </div>
</div>

{{-- Modal nuevo objeto --}}
<div class="av-modal-bg" :class="showObjModal?'open':''">
    <div class="av-modal">
        <div class="av-modal-ttl" x-text="'Nuevo ' + (newObjTipo?.label||'objeto')"></div>
        <div class="av-modal-lbl">Nombre / Número</div>
        <input type="text" class="av-input" x-model="newObjLbl" :placeholder="newObjTipo?.placeholder||'Ej: Mesa 12'" @keyup.enter="confirmObj()">
        <div class="av-modal-row">
            <div class="av-modal-lbl">Zona / Sector</div>
            <input type="text" class="av-input" x-model="newObjZone" placeholder="Ej: Salón, Terraza, Barra">
        </div>
        <div class="av-modal-row" x-show="newObjTipo?.type!=='zona'">
            <div class="av-modal-lbl">Capacidad</div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px;">
                <template x-for="c in [2,4,6,8,'VIP']" :key="c">
                    <button :style="newObjCap==c?'background:#00b26b;color:#fff;border-color:#00b26b;':'background:transparent;color:#64748b;border-color:#2a2d3e;'"
                            style="padding:4px 10px;border-radius:6px;border:1px solid;font-size:11px;font-weight:700;cursor:pointer;transition:all .12s;"
                            @click="newObjCap=c">
                        <span x-text="c=='VIP'?'VIP':c+'p'"></span>
                    </button>
                </template>
            </div>
        </div>
        <div class="av-modal-ft">
            <button class="av-btn-cancel" @click="showObjModal=false">Cancelar</button>
            <button class="av-btn-confirm" @click="confirmObj()">Agregar al plano</button>
        </div>
    </div>
</div>

<div class="av-toast-c" id="avToasts"></div>

</div>{{-- /av-shell --}}

<script>
function avanMapa() {
    return {
        mode: 'operate',
        tool: 'sel',
        zoom: 1,
        loading: false,
        mapId: {{ $map?->id ?? 'null' }},
        objs: [],
        sum:  { total:0, libre:0, ocupado:0, alerta:0, total_amount:0 },
        score: 100,
        selId: null,
        sel: null,
        ptab: 'info',
        hist: [],
        alrtInput: '',
        amtInput: '',
        onlyAlerts: false,
        dragId: null,

        // Merge
        mergeMode: false,
        mergeSource: null,
        mergeTarget: null,

        // Modals
        showMapModal: false,
        newMapName: '',
        showObjModal: false,
        newObjTipo: null,
        newObjLbl: '',
        newObjZone: '',
        newObjCap: '',
        newObjX: 0,
        newObjY: 0,

        // Propiedades del objeto seleccionado (modo diseño)
        propLabel: '',
        propShape: 'default',
        propCap: '',
        propZone: '',
        propColor: '',

        CSRF: document.querySelector('meta[name="csrf-token"]')?.content || '',

        // Paleta de objetos
        tiposMesas: [
            { type:'mesa', label:'Mesa 2p',   sub:'Cuadrada peq.',  icon:'🪑', shape:'square',   shapeClass:'sq-sm', placeholder:'Ej: Mesa 1',  capacity:2, w:80,  h:80  },
            { type:'mesa', label:'Mesa 4p',   sub:'Cuadrada',       icon:'🪑', shape:'square',   shapeClass:'sq',    placeholder:'Ej: Mesa 4',  capacity:4, w:100, h:100 },
            { type:'mesa', label:'Mesa 6p',   sub:'Rectangular',    icon:'🪑', shape:'rect',     shapeClass:'rect',  placeholder:'Ej: Mesa 6',  capacity:6, w:150, h:90  },
            { type:'mesa', label:'Mesa 8p',   sub:'Rect. grande',   icon:'🪑', shape:'rect-lg',  shapeClass:'rect-lg',placeholder:'Ej: Mesa 8', capacity:8, w:200, h:90  },
            { type:'mesa', label:'Redonda 2p',sub:'Círculo peq.',   icon:'🪑', shape:'circle',   shapeClass:'circle', placeholder:'Ej: T-1',   capacity:2, w:80,  h:80  },
            { type:'mesa', label:'Redonda 4p',sub:'Círculo',        icon:'🪑', shape:'circle',   shapeClass:'circle-lg',placeholder:'Ej: T-2',  capacity:4, w:100, h:100 },
            { type:'mesa', label:'Redonda 6p',sub:'Círculo lg.',    icon:'🪑', shape:'circle',   shapeClass:'circle-lg',placeholder:'Ej: T-3',  capacity:6, w:120, h:120 },
            { type:'mesa', label:'Mesa VIP',  sub:'Especial',       icon:'⭐', shape:'rect',     shapeClass:'rect',  placeholder:'Mesa VIP',    capacity:'VIP',w:180,h:100},
            { type:'mesa', label:'Barra',     sub:'Mostrador',      icon:'🍸', shape:'bar',      shapeClass:'bar',   placeholder:'Ej: Barra 1', capacity:1,  w:200, h:60  },
        ],
        tiposZonas: [
            { type:'zona', label:'Salón',     sub:'Área principal',  icon:'📍', shape:'default', shapeClass:'zone', placeholder:'Salón Principal', w:400, h:280 },
            { type:'zona', label:'Terraza',   sub:'Área exterior',   icon:'🌿', shape:'default', shapeClass:'zone', placeholder:'Terraza',         w:350, h:250 },
            { type:'zona', label:'Barra',     sub:'Zona de barra',   icon:'🍸', shape:'default', shapeClass:'zone', placeholder:'Zona Barra',      w:300, h:180 },
            { type:'zona', label:'Cocina',    sub:'Área de cocina',  icon:'🍳', shape:'default', shapeClass:'zone', placeholder:'Cocina',           w:280, h:200 },
        ],
        tiposOtros: [
            { type:'sala',        label:'Sala reuniones', sub:'Sala privada',   icon:'🚪', shape:'default', placeholder:'Sala 1',        w:200, h:150 },
            { type:'consultorio', label:'Consultorio',    sub:'Área clínica',   icon:'🏥', shape:'default', placeholder:'Consultorio 1', w:120, h:100 },
            { type:'habitacion',  label:'Habitación',     sub:'Hotel/hostal',   icon:'🛏',  shape:'default', placeholder:'Hab. 101',      w:120, h:100 },
            { type:'escritorio',  label:'Escritorio',     sub:'Coworking',      icon:'💻', shape:'square',  placeholder:'Desk 01',       w:100, h:90  },
            { type:'maquina',     label:'Máquina',        sub:'Producción',     icon:'⚙️', shape:'default', placeholder:'Máquina 1',     w:120, h:100 },
            { type:'estante',     label:'Estante',        sub:'Almacén',        icon:'📦', shape:'rect-lg', placeholder:'Estante A-1',   w:160, h:60  },
            { type:'vehiculo',    label:'Vehículo',       sub:'Logística',      icon:'🚚', shape:'rect',    placeholder:'Unidad A-01',   w:180, h:90  },
        ],

        palColors: [
            {val:'#3b82f6'},{val:'#10b981'},{val:'#f59e0b'},{val:'#ef4444'},
            {val:'#8b5cf6'},{val:'#f97316'},{val:'#06b6d4'},{val:'#ec4899'},
            {val:'#64748b'},{val:'#1e293b'},
        ],

        quickReqs: [
            {t:'cuenta',l:'Cuenta',ic:'🧾'},{t:'limpieza',l:'Limpieza',ic:'🧹'},
            {t:'mantenimiento',l:'Mantenim.',ic:'🔧'},{t:'reposicion',l:'Reposición',ic:'📦'},
            {t:'traslado',l:'Traslado',ic:'↔️'},{t:'urgente',l:'Urgente',ic:'🚨'},
        ],

        init() {
            if (this.mapId) this.load();
            setInterval(() => { if (!this.dragId) this.load(); }, 30000);
        },

        async load() {
            if (!this.mapId) return;
            this.loading = true;
            try {
                const r = await fetch(`/bixosales/mapa/maps/${this.mapId}/objects`);
                const d = await r.json();
                this.objs  = d.objects;
                this.sum   = d.summary;
                this.score = d.avan_score;
                if (this.selId) this.sel = this.objs.find(o=>o.id===this.selId)||null;
            } catch { this.toast('Error al cargar','err'); }
            finally  { this.loading = false; }
        },

        setMode(m) {
            this.mode = m;
            this.ptab = m === 'design' ? 'props' : 'info';
            this.mergeMode = false;
            this.mergeSource = null;
            this.mergeTarget = null;
        },

        // ─── Filtros de objetos ────────────────────────────────
        get zonaObjs() {
            return this.objs.filter(o => o.type === 'zona');
        },
        get mesaObjs() {
            let list = this.objs.filter(o => o.type !== 'zona');
            if (this.onlyAlerts) list = list.filter(o => o.alerts_count > 0);
            return list;
        },

        // ─── Estilos ───────────────────────────────────────────
        objClasses(o) {
            const shape = o.shape || o.config?.shape || 'default';
            let cls = `shape-${shape}`;
            if (this.selId === o.id) cls += ' sel';
            if (o.id === this.dragId) cls += ' drag';
            if (this.mergeMode && this.mergeSource?.id === o.id) cls += ' merge-sel';
            if (o.config?.merged_with?.length > 0) cls += ' merged';
            return cls;
        },

        objStyle(o) {
            const shape = o.shape || o.config?.shape || 'default';
            let s = `left:${o.pos_x}px;top:${o.pos_y}px;width:${o.width}px;height:${o.height}px;`;
            s += `cursor:${this.mode==='design'?'grab':'pointer'};`;
            // Color identificador del objeto
            if (o.config?.color) {
                s += `border-color:${o.config.color}40;`;
            }
            return s;
        },

        zoneStyle(o) {
            let s = `left:${o.pos_x}px;top:${o.pos_y}px;width:${o.width}px;height:${o.height}px;`;
            if (o.config?.color) s += `border-color:${o.config.color}60;background:${o.config.color}10;`;
            s += `cursor:${this.mode==='design'?'grab':'default'};`;
            return s;
        },

        objColor(o) {
            const colors = {
                libre:'#00b26b', disponible:'#00b26b', ocupado:'#3b82f6',
                reservado:'#8b5cf6', alerta:'#ef4444', bloqueado:'#6b7280',
                mantenimiento:'#f59e0b', en_ruta:'#06b6d4', entregado:'#10b981',
                en_consulta:'#3b82f6', housekeeping:'#f97316', limpieza:'#f97316',
            };
            return colors[o.status] || '#64748b';
        },

        timerColor(min) {
            if (!min || min < 20) return '#64748b';
            if (min < 40) return '#f59e0b';
            return '#ef4444';
        },

        // ─── Selección ─────────────────────────────────────────
        clickObj(o) {
            if (this.mergeMode) { this.doMerge(o); return; }
            this.selObj(o);
        },

        selObj(o) {
            this.selId  = o.id;
            this.sel    = o;
            this.ptab   = this.mode === 'design' ? 'props' : 'info';
            this.amtInput = o.current_amount || '';
            // cargar propiedades actuales en el formulario
            this.propLabel = o.label;
            this.propShape = o.shape || o.config?.shape || 'default';
            this.propCap   = o.capacity || '';
            this.propZone  = o.zone || '';
            this.propColor = o.config?.color || '';
        },

        closePanel() { this.selId=null; this.sel=null; },

        // ─── Drag (mover objeto) ───────────────────────────────
        startDrag(e, o) {
            if (this.mode !== 'design') return;
            e.preventDefault();
            const sx=e.clientX, sy=e.clientY, ox=o.pos_x, oy=o.pos_y;
            this.dragId = o.id;
            const mv = ev => {
                o.pos_x = Math.max(0, Math.round(ox+(ev.clientX-sx)/this.zoom));
                o.pos_y = Math.max(0, Math.round(oy+(ev.clientY-sy)/this.zoom));
            };
            const up = async () => {
                document.removeEventListener('mousemove',mv);
                document.removeEventListener('mouseup',up);
                this.dragId = null;
                await this.patch(`/bixosales/mapa/objects/${o.id}/move`, {pos_x:o.pos_x, pos_y:o.pos_y});
            };
            document.addEventListener('mousemove',mv);
            document.addEventListener('mouseup',up);
        },

        startResize(e, o) {
            e.preventDefault();
            const sx=e.clientX, sy=e.clientY, ow=o.width, oh=o.height;
            const mv = ev => {
                o.width  = Math.max(50, Math.round(ow+(ev.clientX-sx)/this.zoom));
                o.height = Math.max(40, Math.round(oh+(ev.clientY-sy)/this.zoom));
            };
            const up = async () => {
                document.removeEventListener('mousemove',mv);
                document.removeEventListener('mouseup',up);
                await this.put(`/bixosales/mapa/objects/${o.id}`, {width:o.width, height:o.height});
            };
            document.addEventListener('mousemove',mv);
            document.addEventListener('mouseup',up);
        },

        // ─── Drag desde paleta ─────────────────────────────────
        palDrag(e, t) { e.dataTransfer.setData('tipo', JSON.stringify(t)); },

        canvasDrop(e) {
            const data = e.dataTransfer.getData('tipo');
            if (!data) return;
            const t = JSON.parse(data);
            const rect = document.getElementById('avCanvas').getBoundingClientRect();
            this.newObjTipo = t;
            this.newObjLbl  = '';
            this.newObjZone = '';
            this.newObjCap  = t.capacity || '';
            this.newObjX    = Math.round((e.clientX-rect.left)/this.zoom);
            this.newObjY    = Math.round((e.clientY-rect.top)/this.zoom);
            this.showObjModal = true;
            this.$nextTick(() => document.querySelector('.av-modal input')?.focus());
        },

        async confirmObj() {
            if (!this.newObjLbl.trim() || !this.mapId) return;
            const t = this.newObjTipo;
            const r = await this.post(`/bixosales/mapa/maps/${this.mapId}/objects`, {
                type:     t.type,
                label:    this.newObjLbl.trim(),
                zone:     this.newObjZone || null,
                capacity: this.newObjCap  || null,
                pos_x:    this.newObjX,
                pos_y:    this.newObjY,
                shape:    t.shape || 'default',
                width:    t.w || 110,
                height:   t.h || 100,
                color:    t.color || null,
            });
            if (r?.ok) {
                this.objs.push(r.object);
                this.showObjModal = false;
                this.toast(this.newObjLbl+' agregado','ok');
            }
        },

        // ─── Propiedades (modo diseño) ─────────────────────────
        async saveProp() {
            if (!this.sel) return;
            const cfg = { ...(this.sel.config||{}), color: this.propColor, shape: this.propShape };
            const r = await this.put(`/bixosales/mapa/objects/${this.sel.id}`, {
                label:    this.propLabel || this.sel.label,
                capacity: this.propCap   || null,
                zone:     this.propZone  || null,
                shape:    this.propShape,
                config:   cfg,
            });
            if (r?.ok) { this.updList(r.object); this.toast('Guardado','ok'); }
        },

        async applyShape() {
            if (!this.sel) return;
            // Ajustar dimensiones según forma
            const dims = {
                square:   [100,100], circle:[100,100], rect:[150,90],
                'rect-lg':[200,90],  bar:[200,60],     default:[110,100],
            };
            const [w,h] = dims[this.propShape] || [110,100];
            this.sel.width  = w;
            this.sel.height = h;
            const cfg = { ...(this.sel.config||{}), shape: this.propShape };
            await this.put(`/bixosales/mapa/objects/${this.sel.id}`, {
                shape: this.propShape, width: w, height: h, config: cfg,
            });
            this.sel.shape = this.propShape;
        },

        async rotateObj(deg) {
            if (!this.sel) return;
            const [w, h] = [this.sel.height, this.sel.width]; // intercambiar
            this.sel.width  = w;
            this.sel.height = h;
            await this.put(`/bixosales/mapa/objects/${this.sel.id}`, {width:w, height:h});
            this.toast('Rotado','ok');
        },

        async duplicateObj() {
            if (!this.sel || !this.mapId) return;
            const r = await this.post(`/bixosales/mapa/maps/${this.mapId}/objects`, {
                type:     this.sel.type,
                label:    this.sel.label + ' (copia)',
                zone:     this.sel.zone,
                capacity: this.sel.capacity,
                pos_x:    this.sel.pos_x + 20,
                pos_y:    this.sel.pos_y + 20,
                shape:    this.sel.shape || this.sel.config?.shape || 'default',
                width:    this.sel.width,
                height:   this.sel.height,
                config:   this.sel.config,
            });
            if (r?.ok) { this.objs.push(r.object); this.toast('Duplicado','ok'); }
        },

        // ─── MERGE / SPLIT ──────────────────────────────────────
        toggleMerge() {
            this.mergeMode   = !this.mergeMode;
            this.mergeSource = this.sel || null;
            this.mergeTarget = null;
            if (this.mergeMode && !this.mergeSource) {
                this.toast('Primero seleccioná una mesa','err');
                this.mergeMode = false;
            }
        },

        async doMerge(o) {
            if (!this.mergeSource) { this.mergeSource = o; return; }
            if (o.id === this.mergeSource.id) { this.toast('Seleccioná una mesa diferente','err'); return; }
            this.mergeTarget = o;
            // Guardar unión en config del objeto principal
            const cfg = { ...(this.mergeSource.config||{}),
                merged_with: [...(this.mergeSource.config?.merged_with||[]), o.label]
            };
            const r = await this.put(`/bixosales/mapa/objects/${this.mergeSource.id}`, { config: cfg });
            if (r?.ok) {
                this.updList(r.object);
                this.toast(this.mergeSource.label+' + '+o.label+' unidas','ok');
            }
            this.mergeMode   = false;
            this.mergeSource = null;
            this.mergeTarget = null;
        },

        async splitSelected() {
            if (!this.sel) return;
            const cfg = { ...(this.sel.config||{}), merged_with: [] };
            const r = await this.put(`/bixosales/mapa/objects/${this.sel.id}`, { config: cfg });
            if (r?.ok) { this.updList(r.object); this.toast('Mesas separadas','ok'); }
        },

        // ─── Acciones operativas ───────────────────────────────
        async chgStatus(status) {
            if (!this.sel) return;
            const r = await this.patch(`/bixosales/mapa/objects/${this.sel.id}/status`, {status});
            if (r?.ok) { this.updList(r.object); this.toast('Estado actualizado','ok'); }
        },

        async asgnResp(eid) {
            if (!this.sel) return;
            const r = await this.patch(`/bixosales/mapa/objects/${this.sel.id}/responsible`, {employee_id:eid||null});
            if (r?.ok) this.updList(r.object);
        },

        async updAmt() {
            if (!this.sel || this.amtInput==='') return;
            const r = await this.patch(`/bixosales/mapa/objects/${this.sel.id}/amount`, {amount:parseFloat(this.amtInput)});
            if (r?.ok) { this.sel.current_amount=parseFloat(this.amtInput); this.toast('Consumo actualizado','ok'); }
        },

        async addAlert() {
            if (!this.sel || !this.alrtInput.trim()) return;
            const r = await this.post(`/bixosales/mapa/objects/${this.sel.id}/alerts`, {text:this.alrtInput.trim()});
            if (r?.ok) { this.sel.alerts=r.alerts; this.sel.alerts_count=r.alerts.length; this.alrtInput=''; this.toast('Alerta agregada','ok'); await this.load(); }
        },

        async clearAlerts() {
            if (!this.sel) return;
            const r = await this.del(`/bixosales/mapa/objects/${this.sel.id}/alerts`);
            if (r?.ok) { this.sel.alerts=[]; this.sel.alerts_count=0; this.toast('Alertas limpiadas','ok'); await this.load(); }
        },

        async crtReq(type, title) {
            if (!this.sel) return;
            const r = await this.post(`/bixosales/mapa/objects/${this.sel.id}/requests`, {type,title,priority:2});
            if (r?.ok) { this.toast(title+' solicitado','ok'); }
        },

        async loadHist() {
            if (!this.sel) return;
            const d = await (await fetch(`/bixosales/mapa/objects/${this.sel.id}/history`)).json();
            this.hist = d.events||[];
        },

        async delObj() {
            if (!this.sel) return;
            if (! await bxConfirmar({ descripcion: '¿Eliminar ' + this.sel.label + '?' })) return;
            const r = await this.del(`/bixosales/mapa/objects/${this.sel.id}`);
            if (r?.ok) { this.objs=this.objs.filter(o=>o.id!==this.sel.id); this.closePanel(); this.toast('Eliminado','ok'); }
        },

        // ─── Mapas ─────────────────────────────────────────────
        async createMap() {
            if (!this.newMapName.trim()) return;
            const r = await this.post('/bixosales/mapa/maps', {name:this.newMapName.trim()});
            if (r?.ok) { this.showMapModal=false; this.toast('Mapa creado','ok'); window.location.reload(); }
        },

        switchMap(id) { this.mapId=parseInt(id); this.objs=[]; this.closePanel(); this.load(); },

        // ─── Labels ────────────────────────────────────────────
        stLbl(s) {
            return {libre:'Libre',disponible:'Disponible',ocupado:'Ocupado',reservado:'Reservado',
                    alerta:'Alerta',bloqueado:'Bloqueado',mantenimiento:'Mantenim.',
                    en_ruta:'En ruta',entregado:'Entregado',en_consulta:'Consulta',
                    housekeeping:'Housekeeping',limpieza:'Limpieza'}[s]||s;
        },

        tipoLbl(t) {
            return {mesa:'Mesa',consultorio:'Consultorio',habitacion:'Habitación',
                    vehiculo:'Vehículo',maquina:'Máquina',estante:'Estante',
                    escritorio:'Escritorio',zona:'Zona',sala:'Sala'}[t]||t;
        },

        stOpts(type) {
            const base = [
                {v:'libre',l:'Libre',ic:'🟢',c:'#00b26b'},{v:'ocupado',l:'Ocupado',ic:'🔵',c:'#3b82f6'},
                {v:'reservado',l:'Reservado',ic:'🟣',c:'#8b5cf6'},{v:'alerta',l:'Alerta',ic:'🔴',c:'#ef4444'},
                {v:'mantenimiento',l:'Mantenim.',ic:'🟡',c:'#f59e0b'},{v:'bloqueado',l:'Bloqueado',ic:'⚫',c:'#6b7280'},
            ];
            const custom = {
                habitacion:[{v:'disponible',l:'Disponible',ic:'🟢',c:'#00b26b'},{v:'ocupado',l:'Ocupado',ic:'🔵',c:'#3b82f6'},{v:'reservado',l:'Reservado',ic:'🟣',c:'#8b5cf6'},{v:'housekeeping',l:'Limpieza',ic:'🟠',c:'#f97316'},{v:'mantenimiento',l:'Mantenim.',ic:'🟡',c:'#f59e0b'},{v:'bloqueado',l:'Bloqueado',ic:'⚫',c:'#6b7280'}],
                consultorio:[{v:'libre',l:'Libre',ic:'🟢',c:'#00b26b'},{v:'en_consulta',l:'Consulta',ic:'🔵',c:'#3b82f6'},{v:'limpieza',l:'Limpieza',ic:'🟠',c:'#f97316'},{v:'reservado',l:'Reservado',ic:'🟣',c:'#8b5cf6'},{v:'mantenimiento',l:'Mantenim.',ic:'🟡',c:'#f59e0b'},{v:'bloqueado',l:'Bloqueado',ic:'⚫',c:'#6b7280'}],
                vehiculo:[{v:'disponible',l:'Disponible',ic:'🟢',c:'#00b26b'},{v:'en_ruta',l:'En ruta',ic:'🔵',c:'#06b6d4'},{v:'entregado',l:'Entregado',ic:'✅',c:'#10b981'},{v:'mantenimiento',l:'Mantenim.',ic:'🟡',c:'#f59e0b'},{v:'bloqueado',l:'Bloqueado',ic:'⚫',c:'#6b7280'},{v:'alerta',l:'Alerta',ic:'🔴',c:'#ef4444'}],
            };
            return custom[type]||base;
        },

        scoreColor(s) { return s>=80?'#00b26b':(s>=50?'#f59e0b':'#ef4444'); },

        updList(u) {
            const i = this.objs.findIndex(o=>o.id===u.id);
            if (i!==-1) this.objs[i]={...this.objs[i],...u};
            if (this.selId===u.id) { this.sel={...this.sel,...u}; }
        },

        // ─── HTTP ──────────────────────────────────────────────
        async post(url,body){ try{ const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':this.CSRF},body:JSON.stringify(body)}); return await r.json(); }catch{ this.toast('Error de red','err'); return null; } },
        async patch(url,body){ try{ const r=await fetch(url,{method:'PATCH',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':this.CSRF},body:JSON.stringify(body)}); return await r.json(); }catch{ this.toast('Error de red','err'); return null; } },
        async put(url,body){ try{ const r=await fetch(url,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':this.CSRF},body:JSON.stringify(body)}); return await r.json(); }catch{ this.toast('Error de red','err'); return null; } },
        async del(url){ try{ const r=await fetch(url,{method:'DELETE',headers:{'X-CSRF-TOKEN':this.CSRF}}); return await r.json(); }catch{ this.toast('Error de red','err'); return null; } },

        toast(msg,type='ok'){
            const c=document.getElementById('avToasts');
            const t=document.createElement('div');
            t.className='av-toast '+(type==='ok'?'ok':'err');
            t.textContent=(type==='ok'?'✓ ':'✕ ')+msg;
            c.appendChild(t);
            setTimeout(()=>t.remove(),3000);
        },
    };
}
</script>
</x-slot>
</x-app-layout>
