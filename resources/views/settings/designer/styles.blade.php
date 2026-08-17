<style>
/* ═══ Nuevo Diseñador visual — Fase A ═══ */
.dz-root{--dz-ink:#1a1d24;--dz-soft:#64748b;--dz-line:#e4e7ec;--dz-bg:#f5f7fb;--dz-card:#fff;--dz-accent:#4f46e5;--dz-accent-soft:#eef0fe;--dz-ok:#0f9d6b;--dz-warn:#c2410c;
  position:fixed;inset:0;top:0;display:flex;flex-direction:column;background:var(--dz-bg);color:var(--dz-ink);font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;z-index:50}
.dz-root [x-cloak]{display:none!important}

/* Topbar */
.dz-topbar{flex:0 0 auto;display:flex;align-items:center;justify-content:space-between;gap:16px;height:56px;padding:0 16px;background:var(--dz-card);border-bottom:1px solid var(--dz-line)}
.dz-topbar-left,.dz-topbar-right{display:flex;align-items:center;gap:12px}
.dz-legacy-link{display:inline-flex;align-items:center;gap:5px;color:var(--dz-soft);font-size:13px;font-weight:600;text-decoration:none;padding:6px 10px;border-radius:8px}
.dz-legacy-link:hover{background:var(--dz-bg);color:var(--dz-ink)}
.dz-store-meta{display:flex;align-items:center;gap:9px}
.dz-store-meta strong{font-size:14px;font-weight:800}
.dz-template-badge{padding:3px 9px;background:var(--dz-accent-soft);color:var(--dz-accent);border-radius:999px;font-size:11px;font-weight:700;text-transform:capitalize}
.dz-device-switch{display:inline-flex;background:var(--dz-bg);border:1px solid var(--dz-line);border-radius:10px;padding:3px}
.dz-device-switch button{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:0;background:none;color:var(--dz-soft);font-size:12px;font-weight:700;border-radius:7px;cursor:pointer}
.dz-device-switch button.is-active{background:var(--dz-card);color:var(--dz-accent);box-shadow:0 1px 3px rgba(16,24,40,.1)}
.dz-undo-redo{display:inline-flex;gap:2px}
.dz-undo-redo button{width:34px;height:34px;display:grid;place-items:center;border:1px solid var(--dz-line);background:var(--dz-card);color:var(--dz-soft);border-radius:8px;cursor:pointer}
.dz-undo-redo button:disabled{opacity:.4;cursor:not-allowed}
.dz-undo-redo button:hover:not(:disabled){color:var(--dz-accent);border-color:var(--dz-accent)}
.dz-status{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--dz-soft)}
.dz-status-dot{width:8px;height:8px;border-radius:50%;background:#cbd5e1}
.dz-status--dirty .dz-status-dot{background:var(--dz-warn)}.dz-status--dirty{color:var(--dz-warn)}
.dz-status--saving .dz-status-dot{background:var(--dz-accent);animation:dzpulse 1s infinite}
.dz-status--draft .dz-status-dot,.dz-status--published .dz-status-dot{background:var(--dz-ok)}.dz-status--draft,.dz-status--published{color:var(--dz-ok)}
.dz-status--error .dz-status-dot{background:#dc2626}.dz-status--error{color:#dc2626}
@keyframes dzpulse{50%{opacity:.4}}
.dz-btn{display:inline-flex;align-items:center;justify-content:center;padding:8px 14px;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;border:1px solid transparent;text-decoration:none}
.dz-btn-ghost{background:var(--dz-card);border-color:var(--dz-line);color:var(--dz-ink)}.dz-btn-ghost:hover{border-color:var(--dz-accent);color:var(--dz-accent)}
.dz-btn-primary{background:var(--dz-accent);color:#fff}.dz-btn-primary:hover{filter:brightness(.94)}
.dz-btn:disabled{opacity:.6;cursor:not-allowed}
.dz-btn-sm{padding:6px 10px;font-size:12px}
.dz-btn-danger{background:#fef2f2;color:#dc2626;border-color:#fecaca}

/* Cuerpo: 3 columnas */
.dz-body{flex:1;display:grid;grid-template-columns:264px 1fr 340px;min-height:0}
.dz-structure,.dz-inspector{background:var(--dz-card);overflow-y:auto}
.dz-structure{border-right:1px solid var(--dz-line)}
.dz-inspector{border-left:1px solid var(--dz-line)}

/* Panel estructura */
.dz-structure-head{padding:14px 16px 8px;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--dz-soft)}
.dz-tree{padding:0 8px 16px;display:flex;flex-direction:column;gap:2px}
.dz-node{display:flex;align-items:center;gap:8px;width:100%;min-height:40px;padding:8px 10px;border:0;background:none;border-radius:8px;cursor:pointer;text-align:left;font-size:13px;color:var(--dz-ink);transition:background .15s,color .15s}
.dz-node:hover{background:var(--dz-bg)}
.dz-node:focus-visible{outline:2px solid var(--dz-accent);outline-offset:-2px}
.dz-node.is-selected{background:var(--dz-accent-soft);color:var(--dz-accent);font-weight:700}
.dz-node.is-disabled .dz-node-label{opacity:.5;text-decoration:line-through}
.dz-node-move{display:flex;flex-direction:column;gap:1px;flex:0 0 auto}
.dz-node-move button{width:18px;height:14px;display:grid;place-items:center;padding:0;border:0;background:none;color:#cbd5e1;border-radius:4px;cursor:pointer;transition:color .12s,background .12s}
.dz-node-move button:hover:not(:disabled){color:var(--dz-accent);background:var(--dz-accent-soft)}
.dz-node-move button:disabled{opacity:.3;cursor:default}
.dz-node-move button:focus-visible{outline:2px solid var(--dz-accent);outline-offset:1px}
.dz-node-ico{flex:0 0 auto;display:grid;place-items:center;color:#64748b}
.dz-node.is-selected .dz-node-ico{color:var(--dz-accent)}
.dz-node-ico svg{display:block}
.dz-node-label{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dz-node-devices{display:flex;gap:3px;color:#94a3b8}
.dz-node-devices svg{width:12px;height:12px}
.dz-node-devices .off{opacity:.25}
.dz-node-toggle{width:34px;height:19px;flex:0 0 auto;border-radius:999px;background:#cbd5e1;position:relative;cursor:pointer;transition:background .2s}
.dz-node-toggle::after{content:"";position:absolute;top:2px;left:2px;width:15px;height:15px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(16,24,40,.2);transition:transform .2s}
.dz-node-toggle.on{background:var(--dz-ok)}.dz-node-toggle.on::after{transform:translateX(15px)}
.dz-node-toggle:focus-visible{outline:2px solid var(--dz-accent);outline-offset:2px}
.dz-group{margin:6px 0}
.dz-group-head{padding:8px 12px 4px;font-size:11px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--dz-accent)}
.dz-group-body{display:flex;flex-direction:column;gap:2px;padding-left:6px;border-left:2px solid var(--dz-accent-soft);margin-left:12px}
.dz-group-body--plain{border-left-color:var(--dz-line)}
.dz-btn-ico{gap:7px}.dz-btn-ico svg{width:15px;height:15px}
.dz-btn:focus-visible,.dz-device-switch button:focus-visible,.dz-undo-redo button:focus-visible,.dz-seg button:focus-visible,.dz-tabs-nav button:focus-visible{outline:2px solid var(--dz-accent);outline-offset:2px}
.dz-inspector-empty-ico svg{width:34px;height:34px;color:#94a3b8}
.dz-mock-cart svg{width:19px;height:19px;color:var(--dz-soft)}
.dz-rubro-ico svg{width:28px;height:28px}
@media(prefers-reduced-motion:reduce){.dz-root *{transition-duration:.01ms!important;animation-duration:.01ms!important}}

/* Preview central */
.dz-preview{overflow-y:auto;padding:28px 20px;display:flex;flex-direction:column;align-items:center;gap:12px}
.dz-preview-frame{width:100%;max-width:920px;transition:max-width .25s}
.dz-preview--mobile .dz-preview-frame{max-width:380px}
.dz-canvas{background:var(--dz-card);border:1px solid var(--dz-line);border-radius:14px;overflow:hidden;box-shadow:0 8px 30px -12px rgba(16,24,40,.18)}
.dz-blk{position:relative;padding:14px;border:2px solid transparent;cursor:pointer;transition:border-color .15s,background .15s}
.dz-blk:hover{background:var(--dz-bg)}
.dz-blk.is-selected{border-color:var(--dz-accent);background:var(--dz-accent-soft)}
.dz-blk-tag{position:absolute;top:6px;left:8px;font-size:10px;font-weight:800;letter-spacing:.03em;text-transform:uppercase;color:var(--dz-accent);opacity:0;transition:opacity .15s}
.dz-blk:hover .dz-blk-tag,.dz-blk.is-selected .dz-blk-tag{opacity:1}
.dz-mock-header{display:flex;align-items:center;gap:12px;padding:8px 4px}
.dz-mock-logo{font-weight:800;font-size:15px;color:var(--dz-primary,#4f46e5)}
.dz-mock-search{flex:1;height:30px;background:var(--dz-bg);border:1px solid var(--dz-line);border-radius:8px}
.dz-mock-cart{font-size:18px}
.dz-mock-hero{height:150px;display:grid;place-items:center;color:#fff;font-weight:800;font-size:18px;background:linear-gradient(135deg,var(--dz-primary,#4f46e5),#8b5cf6);border-radius:10px}
.dz-mock-row{display:flex;gap:10px;padding:6px 0}
.dz-mock-chip{flex:1;height:44px;background:var(--dz-bg);border:1px solid var(--dz-line);border-radius:8px;display:grid;place-items:center;color:var(--dz-ok);font-weight:800}
.dz-mock-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
.dz-mock-grid>div{aspect-ratio:1;background:var(--dz-bg);border:1px solid var(--dz-line);border-radius:10px}
.dz-mock-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
.dz-mock-cards>div{height:120px;background:var(--dz-bg);border:1px solid var(--dz-line);border-radius:10px}
.dz-mock-band{height:60px;background:var(--dz-bg);border:1px dashed var(--dz-line);border-radius:8px}
.dz-mock-footer{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:10px;background:#0f172a;border-radius:8px}
.dz-mock-footer>div{height:40px;background:rgba(255,255,255,.08);border-radius:6px}
.dz-preview-hint{font-size:12px;color:var(--dz-soft)}
.dz-preview-hint strong{color:var(--dz-accent)}

/* Inspector */
.dz-inspector-empty{padding:56px 24px;text-align:center;color:var(--dz-soft)}
.dz-inspector-empty-ico{font-size:32px;margin-bottom:10px}
.dz-inspector-head{padding:16px;border-bottom:1px solid var(--dz-line);font-size:15px;font-weight:800}
.dz-inspector-body .dz-inspector-head strong{font-size:15px}
.dz-insp-section-controls{padding:14px 16px;border-bottom:1px solid var(--dz-line);display:flex;flex-direction:column;gap:10px}
.dz-switch-row{display:flex;align-items:center;justify-content:space-between;font-size:13px;font-weight:600}
.dz-device-visibility{display:flex;gap:16px}
.dz-chk{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--dz-soft)}
.dz-chk-inline{align-self:flex-end;padding-bottom:8px}
.dz-warn{font-size:12px;color:var(--dz-warn);margin:0}
.dz-tabs-nav{display:flex;gap:2px;padding:10px 12px 0;border-bottom:1px solid var(--dz-line)}
.dz-tabs-nav button{padding:8px 12px;border:0;background:none;color:var(--dz-soft);font-size:12px;font-weight:700;cursor:pointer;border-bottom:2px solid transparent}
.dz-tabs-nav button.on{color:var(--dz-accent);border-bottom-color:var(--dz-accent)}
.dz-tab-body{padding:16px;display:flex;flex-direction:column;gap:14px}
.dz-field{display:flex;flex-direction:column;gap:5px}
.dz-field>label{font-size:12px;font-weight:700;color:#475569}
.dz-field input[type=text],.dz-field input[type=number],.dz-field textarea,.dz-field select{width:100%;min-height:40px;padding:8px 11px;border:1px solid #cbd5e1;border-radius:9px;font-size:13px;background:#fff;color:var(--dz-ink)}
.dz-field textarea{min-height:70px;resize:vertical}
.dz-field input:focus,.dz-field textarea:focus,.dz-field select:focus{outline:0;border-color:var(--dz-accent);box-shadow:0 0 0 3px var(--dz-accent-soft)}
.dz-field input[type=color]{width:100%;height:40px;padding:3px;border:1px solid #cbd5e1;border-radius:9px;cursor:pointer}
.dz-field input[type=range]{width:100%}
.dz-field small{color:var(--dz-soft);font-size:11px}
.dz-row-2{display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:end}
.dz-field-group{padding:12px;border:1px solid var(--dz-line);border-radius:10px;display:flex;flex-direction:column;gap:10px}
.dz-field-group-title{font-size:12px;font-weight:800;color:var(--dz-ink)}
.dz-seg{display:inline-flex;background:var(--dz-bg);border:1px solid var(--dz-line);border-radius:9px;padding:3px;gap:2px;flex-wrap:wrap}
.dz-seg button{padding:6px 11px;border:0;background:none;color:var(--dz-soft);font-size:12px;font-weight:700;border-radius:7px;cursor:pointer}
.dz-seg button.on{background:#fff;color:var(--dz-accent);box-shadow:0 1px 2px rgba(16,24,40,.08)}
.dz-check-list{display:flex;flex-direction:column;gap:8px}
.dz-media-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.dz-media{display:flex;flex-direction:column;gap:8px}
.dz-media-label{font-size:12px;font-weight:700;color:#475569}
.dz-media-drop{aspect-ratio:16/6;background:var(--dz-bg) center/cover no-repeat;border:2px dashed #cbd5e1;border-radius:10px;display:grid;place-items:center;color:#94a3b8;font-size:11px}
.dz-media-drop--mobile{aspect-ratio:4/5;max-height:180px}
.dz-media-drop--logo{aspect-ratio:auto;height:80px;background-size:contain}
.dz-media-actions{display:flex;gap:8px}
.dz-hidden{display:none}
.dz-insp-note{font-size:12px;color:var(--dz-soft);background:var(--dz-bg);border:1px solid var(--dz-line);border-radius:9px;padding:12px;margin:0}
.dz-link{color:var(--dz-accent);font-size:12px;font-weight:700;text-decoration:none}

@media(max-width:1024px){
  .dz-body{grid-template-columns:200px 1fr 300px}
}

/* ── Configuración rápida (wizard) ── */
.dz-quick{position:fixed;inset:0;z-index:60;display:grid;place-items:center;padding:20px}
.dz-quick-overlay{position:absolute;inset:0;background:rgba(15,23,42,.55)}
.dz-quick-modal{position:relative;width:min(680px,100%);max-height:90vh;display:flex;flex-direction:column;background:var(--dz-card);border-radius:18px;box-shadow:0 30px 80px -20px rgba(16,24,40,.4);overflow:hidden}
.dz-quick-head{display:flex;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid var(--dz-line)}
.dz-quick-steps{flex:1;display:flex;gap:8px;flex-wrap:wrap}
.dz-quick-step{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:700;color:var(--dz-soft)}
.dz-quick-step.is-active{color:var(--dz-accent)}
.dz-quick-step.is-done{color:var(--dz-ok)}
.dz-quick-step-n{width:22px;height:22px;display:grid;place-items:center;border-radius:50%;background:var(--dz-bg);border:1px solid var(--dz-line);font-size:11px}
.dz-quick-step.is-active .dz-quick-step-n{background:var(--dz-accent);color:#fff;border-color:var(--dz-accent)}
.dz-quick-step.is-done .dz-quick-step-n{background:var(--dz-ok);color:#fff;border-color:var(--dz-ok)}
.dz-quick-close{width:32px;height:32px;border:0;background:var(--dz-bg);border-radius:8px;cursor:pointer;color:var(--dz-soft)}
.dz-quick-body{flex:1;overflow-y:auto;padding:26px 24px}
.dz-quick-pane h2{margin:0 0 4px;font-size:22px;font-weight:800}
.dz-quick-pane>p{margin:0 0 20px;color:var(--dz-soft);font-size:14px}
.dz-rubro-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.dz-rubro{display:flex;flex-direction:column;align-items:center;gap:8px;padding:18px 10px;background:var(--dz-card);border:2px solid var(--dz-line);border-radius:12px;cursor:pointer;font-size:13px;font-weight:700;color:var(--dz-ink)}
.dz-rubro:hover{border-color:#cbd5e1}
.dz-rubro.is-active{border-color:var(--dz-accent);background:var(--dz-accent-soft);color:var(--dz-accent)}
.dz-rubro-ico{font-size:26px}
.dz-quick-palettes{margin-top:20px}
.dz-palette-row{display:flex;gap:10px;margin-top:8px;flex-wrap:wrap}
.dz-palette{display:flex;border:2px solid var(--dz-line);border-radius:10px;overflow:hidden;cursor:pointer;width:64px;height:40px}
.dz-palette.is-active{border-color:var(--dz-accent)}
.dz-palette span{flex:1}
.dz-quick-sections,.dz-quick-pane .dz-check-list{display:flex;flex-direction:column;gap:10px}
.dz-quick-foot{display:flex;align-items:center;gap:10px;padding:16px 20px;border-top:1px solid var(--dz-line);background:var(--dz-bg)}
@media(max-width:640px){ .dz-rubro-grid{grid-template-columns:repeat(2,1fr)} }
</style>
