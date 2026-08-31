<style>
/* ═══ Constructor guiado — design system (tokens --dz-* heredados) ═══ */
.bx-builder{--dz-ink:#1a1d24;--dz-soft:#64748b;--dz-line:#e4e7ec;--dz-bg:#f5f7fb;--dz-card:#fff;--dz-accent:#4f46e5;--dz-accent-soft:#eef0fe;--dz-ok:#0f9d6b;--dz-warn:#c2410c;--dz-danger:#dc2626;
  position:fixed;inset:0;z-index:60;display:flex;flex-direction:column;background:var(--dz-bg);color:var(--dz-ink);font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
.bx-builder [x-cloak]{display:none!important}
.bx-builder a{color:inherit;text-decoration:none}
.bx-builder button{font:inherit;cursor:pointer}
.bx-builder :focus-visible{outline:2px solid var(--dz-accent);outline-offset:2px}

/* Topbar */
.bxb-topbar{flex:0 0 auto;display:flex;align-items:center;gap:10px;min-height:56px;padding:6px 14px;background:var(--dz-card);border-bottom:1px solid var(--dz-line);flex-wrap:wrap}
.bxb-back{display:inline-flex;align-items:center;gap:4px;min-height:44px;padding:0 10px;border-radius:9px;color:var(--dz-soft);font-size:13px;font-weight:700}
.bxb-back:hover{background:var(--dz-bg);color:var(--dz-ink)}
.bxb-name{font-size:14px;font-weight:800;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bxb-progressbar{width:120px;height:8px;background:var(--dz-bg);border:1px solid var(--dz-line);border-radius:999px;overflow:hidden}
.bxb-progressbar-fill{height:100%;background:var(--dz-accent);border-radius:999px;transition:width .3s ease}
.bxb-percent{font-size:12px;font-weight:800;color:var(--dz-accent)}
.bxb-draft-chip{display:inline-flex;align-items:center;gap:5px;min-height:34px;padding:0 12px;background:#fef3c7;border:1px solid #fcd34d;border-radius:999px;color:#92400e;font-size:12px;font-weight:800;cursor:pointer}
.bxb-draft-chip:hover{background:#fde68a}
.bxb-save{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--dz-soft)}
.bxb-save-dot{width:8px;height:8px;border-radius:50%;background:#cbd5e1}
.bxb-save[data-state=dirty] .bxb-save-dot{background:var(--dz-warn)}
.bxb-save[data-state=saving] .bxb-save-dot{background:var(--dz-accent);animation:bxbPulse 1s infinite}
.bxb-save[data-state=saved] .bxb-save-dot{background:var(--dz-ok)}
.bxb-save[data-state=error] .bxb-save-dot,.bxb-save[data-state=offline] .bxb-save-dot{background:var(--dz-danger)}
@keyframes bxbPulse{50%{opacity:.35}}
.bxb-undo,.bxb-device{display:inline-flex;gap:2px;background:var(--dz-bg);border:1px solid var(--dz-line);border-radius:9px;padding:2px}
.bxb-undo button,.bxb-device button{min-width:34px;min-height:34px;display:grid;place-items:center;padding:0 8px;border:0;background:none;color:var(--dz-soft);border-radius:7px;font-size:12px;font-weight:700}
.bxb-undo button:disabled{opacity:.35;cursor:default}
.bxb-undo button:hover:not(:disabled),.bxb-device button:hover{color:var(--dz-accent)}
.bxb-device button.on{background:var(--dz-card);color:var(--dz-accent);box-shadow:0 1px 3px rgba(16,24,40,.12)}
.bxb-btn{display:inline-flex;align-items:center;gap:6px;min-height:40px;padding:0 14px;background:var(--dz-card);border:1px solid var(--dz-line);border-radius:9px;font-size:13px;font-weight:700;color:var(--dz-ink)}
.bxb-btn:hover{border-color:var(--dz-accent);color:var(--dz-accent)}
.bxb-btn-primary{background:var(--dz-accent);border-color:var(--dz-accent);color:#fff}
.bxb-btn-primary:hover{filter:brightness(.94);color:#fff}
.bxb-btn-publish{background:var(--dz-ok);border-color:var(--dz-ok);color:#fff;margin-left:auto}
.bxb-btn-publish:hover{filter:brightness(.94);color:#fff}
.bxb-btn-publish:disabled{background:#cbd5e1;border-color:#cbd5e1;color:#64748b;cursor:not-allowed;filter:none}
.bxb-badge{margin-left:6px;min-width:19px;height:19px;display:inline-grid;place-items:center;padding:0 5px;background:var(--dz-danger);color:#fff;border-radius:999px;font-size:11px}
.bxb-link{background:none;border:0;color:var(--dz-accent);font-size:12px;font-weight:700;cursor:pointer;min-height:34px;display:inline-flex;align-items:center;padding:0 4px}

/* Cuerpo: etapas + panel + preview */
.bxb-body{flex:1;display:grid;grid-template-columns:250px minmax(0,1fr) var(--bxb-pw,430px);min-height:0}
.bxb-stages{background:var(--dz-card);border-right:1px solid var(--dz-line);overflow-y:auto;display:flex;flex-direction:column}
.bxb-stages-head{display:flex;flex-direction:column;gap:3px;padding:18px 18px 10px;border-bottom:1px solid var(--dz-line)}
.bxb-stages-head strong{font-size:12px;letter-spacing:.12em;color:var(--dz-ink)}
.bxb-stages-head small{font-size:11px;color:var(--dz-soft)}
.bxb-stages ol{list-style:none;margin:0;padding:10px 8px;display:flex;flex-direction:column;gap:2px;flex:1}
.bxb-stage-item{display:flex;align-items:center;gap:10px;width:100%;min-height:54px;padding:8px 10px;border:0;background:none;border-radius:10px;text-align:left;transition:background .15s}
.bxb-stage-item:hover{background:var(--dz-bg)}
.bxb-stage-item.is-active{background:var(--dz-accent-soft)}
.bxb-stage-n{width:26px;height:26px;flex:0 0 26px;display:grid;place-items:center;background:var(--dz-bg);border:1px solid var(--dz-line);border-radius:50%;font-size:12px;font-weight:800;color:var(--dz-soft)}
.bxb-stage-item.is-active .bxb-stage-n{background:var(--dz-accent);border-color:var(--dz-accent);color:#fff}
.bxb-stage-item[data-state=complete] .bxb-stage-n{background:var(--dz-ok);border-color:var(--dz-ok);color:#fff}
.bxb-stage-copy{flex:1;min-width:0}
.bxb-stage-copy strong{display:block;font-size:13px;font-weight:800}
.bxb-stage-copy small{display:block;color:var(--dz-soft);font-size:11px}
.bxb-stage-dot{width:9px;height:9px;flex:0 0 9px;border-radius:50%;background:#cbd5e1}
.bxb-stage-dot[data-sev=critical]{background:var(--dz-danger)}
.bxb-stage-dot[data-sev=warning]{background:var(--dz-warn)}
.bxb-stage-dot[data-sev=recommendation]{background:#eab308}
.bxb-stage-dot[data-sev=complete]{background:var(--dz-ok)}
.bxb-stages-foot{padding:12px;border-top:1px solid var(--dz-line)}

.bxb-panel{overflow-y:auto;padding:26px clamp(16px,3vw,36px);display:flex;flex-direction:column}
.bxb-local-nav{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 18px;padding:6px;background:#eef2f7;border:1px solid var(--dz-line);border-radius:12px}
.bxb-local-nav button{min-height:42px;padding:0 14px;border:0;border-radius:8px;background:transparent;color:var(--dz-soft);font-size:13px;font-weight:800}
.bxb-local-nav button:hover{color:var(--dz-accent)}
.bxb-local-nav button.is-active{background:var(--dz-card);color:var(--dz-accent);box-shadow:0 1px 4px rgba(15,23,42,.1)}
.bxb-stage h2{margin:0 0 4px;font-size:22px;font-weight:800;letter-spacing:-.02em}
.bxb-stage-sub{margin:0 0 18px;color:var(--dz-soft);font-size:14px;line-height:1.6}
/* Sucursales dentro de la etapa 01 (revision 01) */
.bxb-sedes{display:flex;flex-direction:column;gap:8px;margin-bottom:12px}
.bxb-sede{display:flex;flex-wrap:wrap;gap:4px 10px;align-items:baseline;padding:10px 12px;border:1px solid var(--dz-line);border-radius:10px;font-size:13px}
.bxb-sede strong{font-size:13.5px}
.bxb-sede span{color:var(--dz-muted,#64748b)}
.bxb-sede-off{opacity:.55}
.bxb-sede em{margin-left:auto;font-style:normal;font-size:11px;font-weight:700;text-transform:uppercase;color:#b45309}
/* ── Plantilla de imagenes de producto ── */
.bxb-it-head{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-start;justify-content:space-between}
.bxb-it-switch{flex-shrink:0;font-weight:700}
.bxb-it-stats{display:flex;flex-wrap:wrap;align-items:center;gap:14px;padding:10px 12px;border:1px solid var(--dz-line);border-radius:10px;font-size:12.5px}
.bxb-it-stats b{font-size:15px}
.bxb-it-stats span[data-warn="true"]{color:#b45309}
.bxb-it-bar{flex:1;min-width:120px;height:6px;border-radius:99px;background:var(--dz-line);overflow:hidden}
.bxb-it-bar i{display:block;height:100%;background:#4f46e5;transition:width .4s ease}
.bxb-it-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px;margin-top:14px}
.bxb-it-preview{display:flex;flex-direction:column;gap:10px;min-width:0}
.bxb-it-canvas{position:relative;aspect-ratio:1/1;border:1px solid var(--dz-line);border-radius:12px;overflow:hidden;
  background:repeating-conic-gradient(#f1f5f9 0 25%,#fff 0 50%) 50%/18px 18px;display:grid;place-items:center;touch-action:none}
.bxb-it-canvas img{width:100%;height:100%;object-fit:contain;user-select:none}
.bxb-it-vacio{padding:16px;text-align:center;font-size:12.5px;color:var(--dz-muted,#64748b)}
.bxb-it-handle{position:absolute;transform:translate(-50%,-50%);padding:3px 8px;border:1px dashed #4f46e5;border-radius:99px;
  background:rgba(255,255,255,.92);font-size:10.5px;font-weight:700;color:#4f46e5;cursor:grab;white-space:nowrap}
.bxb-it-handle:active{cursor:grabbing}
.bxb-it-handle-wm{border-color:#0891b2;color:#0891b2}
.bxb-it-cargando{position:absolute;inset:0;display:grid;place-items:center;background:rgba(255,255,255,.55)}
.bxb-it-cargando span{width:22px;height:22px;border:2px solid #cbd5e1;border-top-color:#4f46e5;border-radius:50%;animation:bxbIt .7s linear infinite}
@keyframes bxbIt{to{transform:rotate(360deg)}}
.bxb-it-config{display:flex;flex-direction:column;gap:8px;min-width:0}
.bxb-it-config details{border:1px solid var(--dz-line);border-radius:10px;overflow:hidden}
.bxb-it-config summary{padding:10px 12px;font-weight:700;font-size:13px;cursor:pointer;list-style:none}
.bxb-it-config summary::-webkit-details-marker{display:none}
.bxb-it-config summary::after{content:"›";float:right;transform:rotate(90deg);opacity:.5}
.bxb-it-config details[open] summary::after{transform:rotate(-90deg)}
.bxb-it-bloque{display:flex;flex-direction:column;gap:10px;padding:0 12px 12px}
.bxb-it-rejilla{display:grid;grid-template-columns:repeat(3,1fr);gap:5px;max-width:132px}
.bxb-it-rejilla button{aspect-ratio:1;border:1px solid var(--dz-line);border-radius:6px;background:#fff;cursor:pointer}
.bxb-it-rejilla button.on{border-color:#4f46e5;background:#eef2ff;box-shadow:inset 0 0 0 2px #c7d2fe}
.bxb-it-acciones{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:14px;padding-top:12px;border-top:1px solid var(--dz-line)}
.bxb-it-acciones small{color:var(--dz-muted,#64748b);font-size:12px}
.bxb-it-confirm{margin-top:12px;padding:12px;border:1px solid #fcd34d;background:#fffbeb;border-radius:10px;font-size:13px}
.bxb-btn-primary{background:#4f46e5;color:#fff;border-color:#4f46e5}
@media (max-width:860px){
  .bxb-it-grid{grid-template-columns:1fr}
  .bxb-it-preview{order:1}
  .bxb-it-config{order:2}
}
.bxb-card{display:flex;flex-direction:column;gap:14px;padding:20px;background:var(--dz-card);border:1px solid var(--dz-line);border-radius:14px;margin-bottom:14px}
.bxb-field{display:flex;flex-direction:column;gap:6px;font-size:12.5px;font-weight:700;color:#475569}
.bxb-field input[type=text],.bxb-field input[type=email]{min-height:44px;padding:10px 12px;border:1px solid #cbd5e1;border-radius:9px;font:inherit;font-weight:400;color:var(--dz-ink)}
.bxb-field input:focus{outline:0;border-color:var(--dz-accent);box-shadow:0 0 0 3px var(--dz-accent-soft)}
.bxb-color-row{display:flex;align-items:center;gap:10px}
/* ═══ Campo de color de una sola pieza ═══
   Sustituye la fila de tres elementos (selector + <code> + botón "Auto"). La
   muestra abre el selector nativo; el campo acepta que se escriba o se pegue el
   código, que es lo que hace falta cuando lo traes de una guía de marca. */
.bxb-color-one{display:inline-flex;align-items:center;gap:0;
    border:1px solid #d7dbe3;border-radius:9px;background:#fff;overflow:hidden;max-width:180px}
.bxb-color-one:focus-within{border-color:var(--sb-purple,#5B21B6);box-shadow:0 0 0 3px rgba(91,33,182,.12)}
.bxb-color-one .bxb-color-dot{flex:0 0 34px;width:34px;height:36px;padding:0;margin:0;
    border:0;border-right:1px solid #e5e8ee;background:none;cursor:pointer;appearance:none}
.bxb-color-one .bxb-color-dot::-webkit-color-swatch-wrapper{padding:4px}
.bxb-color-one .bxb-color-dot::-webkit-color-swatch{border:0;border-radius:5px}
.bxb-color-one .bxb-color-hex{flex:1 1 auto;min-width:0;width:100%;height:36px;padding:0 10px;
    border:0;outline:0;background:none;font:600 12.5px/1 ui-monospace,SFMono-Regular,Menlo,monospace;
    letter-spacing:.03em;text-transform:lowercase;color:#0f172a}
.bxb-color-one .bxb-color-hex::placeholder{color:#9aa3b2;font-weight:500;text-transform:none;letter-spacing:0}
.bxb-color-row input[type=color]{width:52px;height:44px;padding:3px;border:1px solid #cbd5e1;border-radius:9px;cursor:pointer;background:#fff}
.bxb-color-row code{font-size:12px;color:var(--dz-soft)}
.bxb-note{color:var(--dz-soft);font-size:12.5px;line-height:1.6}
.bxb-card-title{font-size:13px;font-weight:800;letter-spacing:.02em;text-transform:uppercase;color:var(--dz-soft)}
.bxb-grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.bxb-field select{min-height:44px;padding:10px 12px;border:1px solid #cbd5e1;border-radius:9px;font:inherit;font-weight:400;color:var(--dz-ink);background:#fff}
.bxb-field select:focus{outline:0;border-color:var(--dz-accent);box-shadow:0 0 0 3px var(--dz-accent-soft)}
.bxb-field input[type=number]{min-height:44px;padding:10px 12px;border:1px solid #cbd5e1;border-radius:9px;font:inherit;font-weight:400}
.bxb-seg{display:inline-flex;flex-wrap:wrap;gap:2px;background:var(--dz-bg);border:1px solid var(--dz-line);border-radius:9px;padding:3px}
.bxb-seg button{min-height:38px;padding:0 13px;border:0;background:none;color:var(--dz-soft);font-size:12.5px;font-weight:700;border-radius:7px}
.bxb-seg button.on{background:#fff;color:var(--dz-accent);box-shadow:0 1px 3px rgba(16,24,40,.1)}
.bxb-switch{display:flex;align-items:center;gap:9px;min-height:44px;font-size:13px;font-weight:600;color:#475569;cursor:pointer}
.bxb-switch input{width:18px;height:18px;accent-color:var(--dz-accent)}
.bxb-hidden{display:none}
.bxb-media{display:flex;align-items:center;gap:12px;border-radius:12px;transition:background-color .15s}
.bxb-media--dragging{background:var(--dz-accent-soft);outline:2px dashed var(--dz-accent);outline-offset:2px}
.bxb-media-box{width:150px;height:72px;display:grid;place-items:center;background:var(--dz-bg) center/contain no-repeat;background-origin:content-box;padding:6px;border:2px dashed #cbd5e1;border-radius:10px;color:#94a3b8;font-size:11px}
.bxb-media-box--sq{width:72px}
.bxb-recommend{padding:12px 14px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;color:#065f46;font-size:12.5px;line-height:1.6}
.bxb-tpl-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}
.bxb-tpl{display:flex;flex-direction:column;gap:6px;padding:10px;background:#fff;border:2px solid var(--dz-line);border-radius:12px;text-align:left;transition:border-color .15s}
.bxb-tpl:hover{border-color:#c7d2fe}
.bxb-tpl.is-active{border-color:var(--dz-accent);background:var(--dz-accent-soft)}
.bxb-tpl-preview{height:56px;display:flex;align-items:flex-end;gap:5px;padding:8px;border-radius:8px}
.bxb-tpl-preview span{flex:1;height:60%;border-radius:4px;opacity:.85}
.bxb-tpl strong{font-size:12.5px}
.bxb-tpl small{color:var(--dz-soft);font-size:10.5px;line-height:1.4}
.bxb-palettes{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.bxb-palette{display:flex;width:56px;height:38px;border:2px solid var(--dz-line);border-radius:9px;overflow:hidden;padding:0}
.bxb-palette.is-active{border-color:var(--dz-accent)}
.bxb-palette span{flex:1}
.bxb-advanced{margin-top:4px}
/* Bloques de la Página de inicio */
.bxb-blocks{list-style:none;margin:0 0 12px;padding:0;display:flex;flex-direction:column;gap:8px}
.bxb-block{display:flex;align-items:center;gap:12px;min-height:60px;padding:10px 14px;background:#fff;border:1px solid var(--dz-line);border-radius:12px}
.bxb-block.is-off{opacity:.55}
.bxb-block[draggable=true]{cursor:grab}
.bxb-block.is-dragging{opacity:.45;border-style:dashed;border-color:var(--dz-accent);cursor:grabbing}
.bxb-block-handle{color:#cbd5e1;font-size:13px;letter-spacing:-2px;cursor:grab;padding:0 2px;user-select:none}
.bxb-block:hover .bxb-block-handle{color:var(--dz-accent)}
.bxb-block--fixed{background:var(--dz-bg);border-style:dashed;margin-bottom:10px}
.bxb-block-ico{font-size:16px;color:var(--dz-soft)}
.bxb-block-move{display:flex;flex-direction:column;gap:2px}
.bxb-block-move button{width:22px;height:18px;display:grid;place-items:center;padding:0;border:0;background:none;color:#cbd5e1;font-size:9px;border-radius:4px}
.bxb-block-move button:hover:not(:disabled){color:var(--dz-accent);background:var(--dz-accent-soft)}
.bxb-block-move button:disabled{opacity:.3;cursor:default}
.bxb-block-copy{flex:1;min-width:0}
.bxb-block-copy strong{display:block;font-size:13.5px;font-weight:800}
.bxb-block-copy small{display:flex;align-items:center;gap:6px;margin-top:3px;color:var(--dz-soft);font-size:11px}
.bxb-draft-tag{padding:2px 7px;background:#fef3c7;color:#92400e;border-radius:999px;font-size:10px;font-weight:800}
.bxb-mini{min-height:24px;padding:2px 8px;border:1px solid var(--dz-line);background:#fff;color:#94a3b8;border-radius:999px;font-size:10px;font-weight:800}
.bxb-mini.on{border-color:var(--dz-ok);color:var(--dz-ok);background:#ecfdf5}
.bxb-toggle{width:38px;height:21px;flex:0 0 38px;position:relative;border:0;background:#cbd5e1;border-radius:999px;transition:background .2s}
.bxb-toggle::after{content:'';position:absolute;top:2px;left:2px;width:17px;height:17px;background:#fff;border-radius:50%;box-shadow:0 1px 2px rgba(16,24,40,.25);transition:transform .2s}
.bxb-toggle.on{background:var(--dz-ok)}
.bxb-toggle.on::after{transform:translateX(17px)}
.bxb-actions-row{display:flex;flex-wrap:wrap;gap:10px;margin-top:6px;margin-bottom:14px}
/* Catálogo (B4) */
.bxb-metrics{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:16px}
.bxb-metric{display:flex;flex-direction:column;gap:2px;min-height:64px;padding:10px 12px;background:#fff;border:1px solid var(--dz-line);border-radius:12px;text-align:left}
.bxb-metric:hover{border-color:var(--dz-accent)}
.bxb-metric b{font-size:20px;font-weight:800}
.bxb-metric span{color:var(--dz-soft);font-size:11px;font-weight:700}
.bxb-metric[data-warn=true] b{color:var(--dz-warn)}
.bxb-fix-head{display:flex;align-items:center;gap:12px}
.bxb-bulkbar{display:flex;flex-wrap:wrap;align-items:center;gap:10px;padding:10px 12px;background:var(--dz-accent-soft);border:1px solid #c7d2fe;border-radius:10px}
.bxb-bulk-set{display:inline-flex;gap:6px;align-items:center}
.bxb-bulk-set input{width:100px;min-height:38px;padding:6px 10px;border:1px solid #cbd5e1;border-radius:8px;font:inherit}
.bxb-fixlist{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px}
.bxb-fixlist li{display:flex;align-items:center;gap:10px;min-height:52px;padding:6px 10px;border:1px solid var(--dz-line);border-radius:10px}
.bxb-fixlist input[type=checkbox]{width:17px;height:17px;accent-color:var(--dz-accent)}
.bxb-fix-thumb{width:40px;height:40px;flex:0 0 40px;background:var(--dz-bg) center/cover;border:1px solid var(--dz-line);border-radius:8px}
.bxb-fix-copy{flex:1;min-width:0}
.bxb-fix-copy strong{display:block;overflow:hidden;font-size:12.5px;text-overflow:ellipsis;white-space:nowrap}
.bxb-fix-copy small{color:var(--dz-soft);font-size:10.5px}
.bxb-full{grid-column:1/-1}
.bxb-check-inline{display:flex;flex-wrap:wrap;gap:4px 16px}
.bxb-iconlist{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px}
.bxb-iconlist li{display:flex;align-items:center;gap:10px;min-height:48px;padding:5px 10px;border:1px solid var(--dz-line);border-radius:10px}
.bxb-iconlist-svg{width:34px;height:34px;display:grid;place-items:center;background:var(--dz-bg);border-radius:8px;color:var(--dz-ink)}
.bxb-iconlist-svg svg{width:22px;height:22px}
.bxb-iconlist-empty{color:#cbd5e1}
.bxb-cat-photo{width:34px;height:34px;flex:0 0 34px;display:grid;place-items:center;background:#f8fafc center/cover no-repeat;border:1px solid var(--dz-line);border-radius:8px;color:#cbd5e1;overflow:hidden}
.bxb-picksearch{display:flex;align-items:center;gap:10px;margin-bottom:6px}
.bxb-picksearch input{flex:1 1 auto;min-width:0;height:36px;padding:0 12px;border:1px solid var(--dz-line);border-radius:9px;font-size:13px}
.bxb-pickcount{flex:0 0 auto;display:inline-flex;align-items:center;gap:7px;color:#475569;font-size:12px;white-space:nowrap}
.bxb-pickcount b{color:#0f172a}
.bxb-pickcount button{padding:3px 9px;border:1px solid var(--dz-line);border-radius:7px;background:#fff;color:#475569;font-size:11px;font-weight:700;cursor:pointer}
.bxb-pickcount button:hover{border-color:#cbd5e1;color:#0f172a}
.bxb-picklist{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:2px;max-height:260px;overflow-y:auto;border:1px solid var(--dz-line);border-radius:10px}
.bxb-picklist li{display:flex;align-items:center;justify-content:space-between;gap:10px;min-height:40px;padding:2px 12px}
.bxb-picklist li:nth-child(even){background:var(--dz-bg)}
.bxb-picklist small{flex:0 0 auto;color:#64748b}
.bxb-iconlist-name{flex:1;font-size:13px;font-weight:700}
.bxb-icon-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(44px,1fr));gap:6px;max-height:240px;overflow-y:auto;margin-top:8px}
.bxb-icon-grid button{display:grid;place-items:center;min-height:44px;background:var(--dz-bg);border:1px solid var(--dz-line);border-radius:9px}
.bxb-icon-grid button:hover{border-color:var(--dz-accent);background:var(--dz-accent-soft)}
.bxb-icon-grid img{width:24px;height:24px}
.bxb-embed{padding:0;overflow:hidden}
.bxb-embed>section,.bxb-embed>div{margin:0!important;box-shadow:none!important}
.bxb-trustlist{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px}
.bxb-trustlist li{display:flex;align-items:center;gap:10px;min-height:52px;padding:8px 12px;border:1px solid var(--dz-line);border-radius:10px}
.bxb-trustlist li span{font-size:13px;font-weight:700}
.bxb-trustlist li small{flex:1;color:var(--dz-soft);font-size:11px}
.bxb-checklist{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px}
.bxb-checklist li{display:flex;align-items:center;gap:10px;min-height:46px;padding:6px 10px;border:1px solid var(--dz-line);border-radius:9px;font-size:13px}
.bxb-checklist li span{flex:1}
.bxb-check[data-sev=critical]{border-color:#fecaca;background:#fff5f5}
.bxb-check[data-sev=warning]{border-color:#fde68a;background:#fffbeb}
.bxb-advanced summary{min-height:44px;display:flex;align-items:center;color:var(--dz-accent);font-size:13px;font-weight:800;cursor:pointer;list-style:none}
.bxb-advanced summary::before{content:'▸';margin-right:7px;transition:transform .15s}
.bxb-advanced[open] summary::before{transform:rotate(90deg)}
@media(max-width:640px){.bxb-grid2{grid-template-columns:1fr}}
.bxb-note a,.bxb-stage-sub a{color:var(--dz-accent);font-weight:700;text-decoration:underline}
.bxb-danger{color:var(--dz-danger)}
.bxb-pending{margin:8px 0 0;padding-left:18px;color:#475569;font-size:13px;line-height:1.7}
.bxb-panel-foot{margin-top:auto;display:flex;gap:10px;padding-top:20px}

/* Preview */
.bxb-preview{position:relative;display:flex;flex-direction:column;background:#eceff5;border-left:1px solid var(--dz-line);min-width:0}
.bxb-preview-resizer{position:absolute;left:-4px;top:0;bottom:0;width:9px;cursor:col-resize;z-index:5}
.bxb-preview-resizer:hover{background:linear-gradient(90deg,transparent 2px,var(--dz-accent) 2px,var(--dz-accent) 5px,transparent 5px);opacity:.6}
.bxb-preview-scaler{flex:none}
.bxb-preview-scaler iframe{width:100%;height:100%;min-height:520px;background:#fff;border:1px solid var(--dz-line);border-radius:10px;display:block}
.bxb-preview-head{display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--dz-card);border-bottom:1px solid var(--dz-line)}
.bxb-preview-title{flex:1;font-size:12px;font-weight:800;color:var(--dz-soft)}
.bxb-preview-title em{font-style:normal;color:var(--dz-warn)}
.bxb-preview-close{display:none;border:0;background:none;color:var(--dz-soft);min-width:34px;min-height:34px}
.bxb-preview-frame{flex:1;display:block;padding:12px;overflow:auto;position:relative}
.bxb-preview-frame.is-mobile{display:flex;justify-content:center}
.bxb-preview-frame.is-tablet{display:flex;justify-content:center}
.bxb-preview-error{position:absolute;inset:auto 14px 14px;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:var(--dz-danger);font-size:13px}
.bxb-preview-fab{display:none;position:fixed;right:16px;bottom:76px;z-index:70;min-height:46px;padding:0 16px;background:var(--dz-accent);color:#fff;border:0;border-radius:999px;font-size:13px;font-weight:800;box-shadow:0 12px 30px rgba(79,70,229,.4)}

/* Modales */
.bxb-modal{position:fixed;inset:0;z-index:80;display:grid;place-items:center;padding:16px;background:rgba(15,23,42,.55)}
.bxb-modal-box{width:min(440px,100%);padding:24px;background:#fff;border-radius:16px;box-shadow:0 30px 80px -20px rgba(16,24,40,.4)}
.bxb-modal-box h3{margin:0 0 8px;font-size:18px;font-weight:800}
.bxb-modal-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}

/* Responsive */
@media(max-width:1279px){
  .bxb-body{grid-template-columns:64px minmax(0,1fr)}
  .bxb-stage-copy,.bxb-stages-foot{display:none}
  .bxb-stages-head{display:none}
  .bxb-stage-item{justify-content:center;min-width:0;padding:8px 4px}
  .bxb-preview{position:fixed;inset:0 0 0 auto;width:min(480px,100%);z-index:75;transform:translateX(100%);transition:transform .25s ease;box-shadow:-20px 0 60px rgba(15,23,42,.25)}
  .bxb-preview.is-open{transform:none}
  .bxb-preview-close{display:grid;place-items:center}
  .bxb-preview-fab{display:inline-flex;align-items:center}
  .bxb-hide-md{display:none!important}
}
@media(max-width:767px){
  .bxb-body{grid-template-columns:minmax(0,1fr)}
  .bxb-stages{flex-direction:row;border-right:0;border-bottom:1px solid var(--dz-line)}
  .bxb-stages{overflow-x:auto;overflow-y:hidden;scrollbar-width:none}
  .bxb-stages::-webkit-scrollbar{display:none}
  .bxb-stages ol{flex-direction:row;justify-content:flex-start;padding:6px;min-width:max-content}
  .bxb-stage-item{min-height:44px}
  .bxb-stage-dot{display:none}
  .bxb-hide-sm{display:none!important}
  .bxb-name{max-width:110px}
  .bxb-preview{width:100%}
  .bxb-panel{padding:18px 14px 90px}
  .bxb-panel-foot{position:fixed;left:0;right:0;bottom:0;z-index:65;margin:0;padding:10px 14px;background:var(--dz-card);border-top:1px solid var(--dz-line)}
}
@media(prefers-reduced-motion:reduce){.bx-builder *{transition:none!important;animation:none!important}}
</style>
