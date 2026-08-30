<style>
/* ============================================================
   Estudio de QR — usa los tokens del panel (--radius-*, --primary…)
   y solo define lo propio de esta pantalla.
   ============================================================ */
.qrx{
  --qx-ink:#0f172a; --qx-soft:#64748b; --qx-tenue:#94a3b8;
  --qx-line:#e2e8f0; --qx-bg:#f6f8fb; --qx-card:#fff;
  --qx-acc:var(--primary,#4f46e5); --qx-acc-soft:#eef2ff;
  --qx-ok:#0f9d6b; --qx-warn:#b45309; --qx-warn-bg:#fffbeb; --qx-mal:#b42318;
  background:var(--qx-bg); color:var(--qx-ink); font-family:inherit;
  display:flex; flex-direction:column;
  flex:1 1 auto; width:100%; min-width:0; min-height:100%;
  overflow-y:auto;
}
[x-cloak]{display:none!important}

/* ---- Cabecera ---- */
.qrx-top{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;
  padding:1rem 1.5rem;background:var(--qx-card);border-bottom:1px solid var(--qx-line);position:sticky;top:0;z-index:20}
.qrx-top h1{font-size:1.05rem;font-weight:700;margin:0;letter-spacing:-.01em}
.qrx-top p{font-size:.8rem;color:var(--qx-soft);margin:.15rem 0 0}
.qrx-estado{display:flex;align-items:center;gap:.35rem;font-size:.78rem;font-weight:600}
.qrx-estado .es-gris{color:var(--qx-soft)} .qrx-estado .es-tenue{color:var(--qx-tenue);font-weight:500}
.qrx-estado .es-ok{color:var(--qx-ok);display:flex;align-items:center;gap:.25rem}
.qrx-estado .es-mal{color:var(--qx-mal)}

/* ---- Rejilla ---- */
.qrx-main{display:grid;grid-template-columns:minmax(340px,.95fr) minmax(420px,1.05fr);
  gap:1.5rem;padding:1.25rem 1.75rem 3rem;align-items:start;max-width:96rem;width:100%;margin:0 auto}

/* ---- Vista previa ---- */
.qrx-sticky{position:sticky;top:5.5rem;display:flex;flex-direction:column;gap:.85rem}
.qrx-seg{display:inline-flex;background:#eef1f6;border-radius:var(--radius-sm,10px);padding:3px;gap:2px;align-self:flex-start}
.qrx-seg button{border:0;background:none;cursor:pointer;padding:.45rem .9rem;border-radius:8px;
  font-size:.82rem;font-weight:600;color:var(--qx-soft);font-family:inherit;transition:.15s}
.qrx-seg button.on{background:var(--qx-card);color:var(--qx-ink);box-shadow:0 1px 3px rgba(15,23,42,.1)}
.qrx-seg.chico button{padding:.35rem .7rem;font-size:.76rem}

.qrx-lienzo{position:relative;background:var(--qx-card);border:1px solid var(--qx-line);
  border-radius:var(--radius-lg,16px);padding:1rem;display:flex;align-items:center;justify-content:center;min-height:16rem}
.qrx-lienzo canvas{max-width:100%;height:auto;border-radius:10px;display:block;
  box-shadow:0 6px 24px -12px rgba(15,23,42,.35)}
.qrx-lienzo.es-qr canvas{max-height:26rem} .qrx-lienzo.es-flyer canvas{max-height:32rem}
.qrx-cargando{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.7);border-radius:inherit}
.qrx-cargando span{width:22px;height:22px;border:2.5px solid var(--qx-line);border-top-color:var(--qx-acc);
  border-radius:50%;animation:qxgira .8s linear infinite}
@keyframes qxgira{to{transform:rotate(360deg)}}

.qrx-alerta{margin:0;padding:.6rem .8rem;background:var(--qx-warn-bg);border:1px solid #fde68a;
  border-radius:10px;font-size:.78rem;color:var(--qx-warn);line-height:1.45}
.qrx-rapidos{display:flex;gap:.5rem;flex-wrap:wrap}
.qrx-rapidos .qrx-btn{flex:1;min-width:9rem}

/* ---- Botones ---- */
.qrx-btn{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;border:0;cursor:pointer;
  padding:.6rem 1rem;border-radius:var(--radius-sm,10px);font-family:inherit;font-size:.85rem;font-weight:700;
  color:#fff;background:var(--qx-acc);transition:filter .15s,transform .1s}
.qrx-btn:hover:not(:disabled){filter:brightness(1.07)} .qrx-btn:active:not(:disabled){transform:translateY(1px)}
.qrx-btn:disabled,.qrx-btn-sec:disabled{opacity:.45;cursor:not-allowed}
.qrx-btn-sec{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;cursor:pointer;
  padding:.6rem 1rem;border:1px solid var(--qx-line);background:var(--qx-card);border-radius:var(--radius-sm,10px);
  font-family:inherit;font-size:.85rem;font-weight:600;color:#334155;transition:.15s}
.qrx-btn-sec:hover:not(:disabled){border-color:#cbd5e1;background:#f8fafc}
.qrx-link{border:0;background:none;cursor:pointer;padding:0;font-family:inherit;font-size:.8rem;
  font-weight:600;color:var(--qx-acc);text-align:left}
.qrx-link:hover{text-decoration:underline}

/* ---- Tarjetas de configuración ---- */
.qrx-config{display:flex;flex-direction:column;gap:.9rem}
.qrx-card{background:var(--qx-card);border:1px solid var(--qx-line);border-radius:var(--radius-lg,16px);
  padding:1.1rem 1.2rem;display:flex;flex-direction:column;gap:.7rem}
.qrx-card h2{font-size:.92rem;font-weight:700;margin:0}
.qrx-nota{font-size:.76rem;color:var(--qx-soft);margin:0;line-height:1.5}
.qrx-lab{display:block;font-size:.75rem;font-weight:600;color:#475569;margin-bottom:.3rem}
.qrx-lab b{font-weight:700;color:var(--qx-soft);margin-left:.3rem}
.qrx-campo{display:block}
.qrx-campo input[type=text],.qrx-campo input[type=number],.qrx-campo select,.qrx-campo textarea{
  width:100%;box-sizing:border-box;padding:.55rem .7rem;font-family:inherit;font-size:.86rem;color:var(--qx-ink);
  background:#f8fafc;border:1px solid var(--qx-line);border-radius:var(--radius-sm,10px);outline:none;transition:.15s}
.qrx-campo input:focus,.qrx-campo select:focus,.qrx-campo textarea:focus{
  background:#fff;border-color:var(--qx-acc);box-shadow:0 0 0 3px var(--qx-acc-soft)}
.qrx-campo input[type=range]{width:100%;accent-color:var(--qx-acc);margin:.2rem 0}

.qrx-url{display:flex;gap:.4rem;flex-wrap:wrap}
.qrx-url input{flex:1;min-width:11rem;box-sizing:border-box;padding:.55rem .7rem;font-family:ui-monospace,Menlo,monospace;
  font-size:.8rem;background:#f8fafc;border:1px solid var(--qx-line);border-radius:var(--radius-sm,10px);color:#334155}

.qrx-check{display:flex;align-items:flex-start;gap:.5rem;font-size:.84rem;color:#334155;cursor:pointer}
.qrx-check input{margin-top:.15rem;accent-color:var(--qx-acc);cursor:pointer;flex-shrink:0}
.qrx-checks{display:flex;flex-direction:column;gap:.45rem}
.qrx-sub{padding:.7rem .8rem;background:#f8fafc;border-radius:var(--radius-sm,10px);display:flex;flex-direction:column;gap:.5rem}
.qrx-benef{width:100%;box-sizing:border-box;padding:.45rem .6rem;font-family:inherit;font-size:.82rem;
  border:1px solid var(--qx-line);border-radius:8px;background:#fff;outline:none}
.qrx-benef:focus{border-color:var(--qx-acc)}

/* ---- Color: selector + hex ---- */
.qrx-colores{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
.qrx-color{display:flex;gap:5px;align-items:center}
.qrx-color input[type=color]{flex:0 0 42px;height:36px;padding:0;border:1px solid var(--qx-line);
  border-radius:9px;cursor:pointer;background:#fff}
.qrx-color input[type=text]{flex:1;min-width:0;height:36px;padding:0 .55rem;border:1px solid var(--qx-line);
  border-radius:9px;font-family:ui-monospace,Menlo,monospace;font-size:.78rem;text-transform:uppercase;
  color:var(--qx-ink);background:#fff;outline:none}
.qrx-color input[type=text]:focus{border-color:var(--qx-acc);box-shadow:0 0 0 3px var(--qx-acc-soft)}

/* ---- Plantillas ---- */
.qrx-plantillas{display:grid;grid-template-columns:repeat(auto-fill,minmax(5.6rem,1fr));gap:.5rem}
.qrx-tpl{display:flex;flex-direction:column;align-items:center;gap:.35rem;padding:.5rem .3rem;cursor:pointer;
  border:1.5px solid var(--qx-line);border-radius:12px;background:var(--qx-card);font-family:inherit;
  font-size:.72rem;font-weight:600;color:#475569;transition:.15s}
.qrx-tpl:hover{border-color:#cbd5e1}
.qrx-tpl.on{border-color:var(--qx-acc);background:var(--qx-acc-soft);color:var(--qx-acc)}
.qrx-fallo{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:.75rem;padding:1.25rem;text-align:center;background:rgba(248,250,252,.96);border-radius:12px}
.qrx-fallo p{margin:0;max-width:24rem;color:#7f1d1d;font-size:.875rem;line-height:1.45}
.qrx-fallo button{padding:.5rem 1.1rem;border:1px solid #cbd5e1;border-radius:8px;background:#fff;
  font:inherit;font-weight:600;font-size:.8125rem;color:#0f172a;cursor:pointer}
.qrx-fallo button:hover{background:#f1f5f9}
.qrx-fallo button:focus-visible{outline:2px solid #4f46e5;outline-offset:2px}
.qrx-tpl .mini{width:100%;height:6.4rem;border-radius:7px;border:1px solid rgba(15,23,42,.08);
  display:flex;align-items:center;justify-content:center;overflow:hidden;background:#eef1f6}
.qrx-tpl .mini canvas{max-width:100%;max-height:100%;display:block;border-radius:4px}

/* ---- Formatos ---- */
.qrx-formatos{display:grid;grid-template-columns:repeat(auto-fill,minmax(5.2rem,1fr));gap:.5rem}
.qrx-fmt{display:flex;flex-direction:column;align-items:center;gap:.2rem;padding:.55rem .3rem;cursor:pointer;
  border:1.5px solid var(--qx-line);border-radius:12px;background:var(--qx-card);font-family:inherit;transition:.15s}
.qrx-fmt:hover{border-color:#cbd5e1}
.qrx-fmt.on{border-color:var(--qx-acc);background:var(--qx-acc-soft)}
.qrx-fmt .marco{width:1.8rem;background:#e2e8f0;border-radius:3px;margin-bottom:.15rem}
.qrx-fmt.on .marco{background:var(--qx-acc)}
.qrx-fmt b{font-size:.73rem;font-weight:700;color:#334155} .qrx-fmt.on b{color:var(--qx-acc)}
.qrx-fmt small{font-size:.62rem;color:var(--qx-tenue);font-variant-numeric:tabular-nums}

.qrx-desc{display:grid;grid-template-columns:1fr 1fr;gap:.5rem}

/* ---- Avanzado ---- */
.qrx-avanzado summary{display:flex;align-items:center;justify-content:space-between;gap:.5rem;cursor:pointer;list-style:none}
.qrx-avanzado summary::-webkit-details-marker{display:none}
.qrx-avanzado summary span{font-size:.74rem;color:var(--qx-tenue)}
.qrx-avanzado summary::after{content:'';width:7px;height:7px;border-right:2px solid var(--qx-tenue);
  border-bottom:2px solid var(--qx-tenue);transform:rotate(45deg);margin-left:auto;transition:transform .2s}
.qrx-avanzado[open] summary::after{transform:rotate(-135deg)}
.qrx-avanzado[open]{gap:.75rem}
.qrx-avanzado summary + *{margin-top:.4rem}

/* ---- Horario de mesas ---- */
.qrx-horario{display:flex;flex-direction:column;gap:.35rem}
.qrx-horario .fila{display:flex;align-items:center;gap:.5rem;font-size:.78rem;background:#f8fafc;
  padding:.4rem .6rem;border-radius:8px;flex-wrap:wrap}
.qrx-horario .fila label{display:flex;align-items:center;gap:.35rem;font-weight:600;min-width:6.5rem;color:#475569}
.qrx-horario .fila input[type=time]{padding:.25rem .4rem;border:1px solid var(--qx-line);border-radius:6px;
  font-family:inherit;font-size:.76rem;background:#fff}

@media (min-width:1600px){
  .qrx-main{grid-template-columns:minmax(420px,1fr) minmax(520px,1fr);gap:2rem}
  .qrx-lienzo.es-qr canvas{max-height:32rem} .qrx-lienzo.es-flyer canvas{max-height:38rem}
}

/* ---- Responsive ---- */
@media (max-width:1023px){
  .qrx-main{grid-template-columns:1fr;padding:1rem}
  .qrx-sticky{position:static}
  .qrx-lienzo.es-qr canvas,.qrx-lienzo.es-flyer canvas{max-height:20rem}
}
@media (max-width:560px){
  .qrx-top{padding:.85rem 1rem}
  .qrx-colores,.qrx-desc{grid-template-columns:1fr}
  .qrx-rapidos .qrx-btn{min-width:100%}
  .qrx-lienzo.es-qr canvas,.qrx-lienzo.es-flyer canvas{max-height:17rem}
}
.qrx-benef-fila{display:flex;gap:.4rem;align-items:center}
.qrx-icono{flex:0 0 3.2rem;padding:.45rem .3rem;font-family:inherit;font-size:.9rem;text-align:center;
  border:1px solid var(--qx-line);border-radius:8px;background:#fff;cursor:pointer;outline:none}
.qrx-icono:focus{border-color:var(--qx-acc)}
</style>
