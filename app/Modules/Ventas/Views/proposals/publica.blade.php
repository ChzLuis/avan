@php
    $emp      = $settings['business_name'] ?? $settings['razon_social'] ?? $project->name;
    $logoRaw  = $settings['logo_url'] ?? null;
    $logo     = $logoRaw
        ? (str_starts_with((string) $logoRaw, 'http') ? $logoRaw : asset('storage/' . ltrim((string) $logoRaw, '/')))
        : null;
    $tel      = $settings['contact_phone'] ?? $project->whatsapp ?? '';
    $mail     = $settings['contact_email'] ?? '';
    $web      = $settings['contact_web']   ?? url('/' . $project->slug);
    $ruc      = $settings['ruc']           ?? '';
    $dir      = $settings['contact_address'] ?? '';
    // El setting guarda el código ISO (PEN/USD): lo mostramos como símbolo.
    $simbolos = ['PEN' => 'S/', 'USD' => '$', 'EUR' => '€', 'CLP' => '$', 'COP' => '$', 'MXN' => '$'];
    $codMoneda = $settings['currency'] ?? 'PEN';
    $moneda    = $simbolos[strtoupper($codMoneda)] ?? $codMoneda;
    $extras   = $proposal->extras ?? [];
    $periodos = ['unico' => 'pago único', 'mensual' => 'mensual', 'sesion' => 'por sesión', 'anual' => 'anual'];
@endphp
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Propuesta {{ $proposal->number }} — {{ $proposal->client_name }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --tinta:#0F172A; --gris:#475569; --gris2:#64748B; --linea:#E2E8F0;
    --acento:#1E3A8A; --acento2:#2563EB; --suave:#F8FAFC; --ok:#059669;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  body{font-family:'Inter',-apple-system,"Segoe UI",sans-serif; color:var(--tinta);
       background:#EEF2F6; line-height:1.55; -webkit-print-color-adjust:exact; print-color-adjust:exact;}

  .hoja{max-width:820px; margin:26px auto; background:#fff; box-shadow:0 6px 28px rgba(15,23,42,.10);}
  .pad{padding:44px 54px;}

  /* Encabezado */
  .cab{display:flex; justify-content:space-between; align-items:flex-start; gap:26px;
       border-bottom:3px solid var(--acento); padding-bottom:18px;}
  .marca{display:flex; align-items:center; gap:12px;}
  .marca img{height:46px; width:auto;}
  .marca .nom{font-size:19px; font-weight:800; letter-spacing:-.3px;}
  .marca .sub{font-size:11px; color:var(--gris2); margin-top:1px;}
  .doc{text-align:right;}
  .doc .tipo{font-size:10px; letter-spacing:2.2px; color:var(--acento); font-weight:700;}
  .doc .num{font-size:15px; font-weight:800; margin-top:3px;}
  .doc .fec{font-size:11px; color:var(--gris2); margin-top:2px;}

  /* Título */
  .titulo{margin:34px 0 6px; font-size:29px; font-weight:800; letter-spacing:-.7px; line-height:1.2;}
  .bajada{font-size:14px; color:var(--gris); max-width:640px;}

  /* Datos del cliente */
  .datos{margin-top:26px; border:1px solid var(--linea); border-radius:8px; overflow:hidden;}
  .datos .fila{display:flex; border-bottom:1px solid var(--linea);}
  .datos .fila:last-child{border-bottom:none;}
  .datos .cel{flex:1; padding:11px 16px; font-size:13px; border-right:1px solid var(--linea);}
  .datos .cel:last-child{border-right:none;}
  .datos .et{font-size:10px; letter-spacing:.7px; color:var(--gris2); text-transform:uppercase; font-weight:600; display:block; margin-bottom:2px;}
  .datos .va{font-weight:600;}

  /* Secciones */
  h2.sec{margin:34px 0 14px; font-size:12px; letter-spacing:2px; text-transform:uppercase;
         color:var(--acento); font-weight:700; padding-bottom:7px; border-bottom:1px solid var(--linea);}

  /* Inversión */
  .inv{display:flex; align-items:stretch; gap:0; border:1px solid var(--linea); border-radius:8px; overflow:hidden;}
  .inv .desc{flex:1; padding:20px 22px;}
  .inv .desc h3{font-size:15px; font-weight:700; margin-bottom:3px;}
  .inv .desc p{font-size:12.5px; color:var(--gris);}
  .inv .monto{background:var(--acento); color:#fff; padding:20px 26px; display:flex;
              flex-direction:column; justify-content:center; align-items:flex-end; min-width:190px;}
  .inv .monto .lab{font-size:10px; letter-spacing:1.4px; opacity:.85; text-transform:uppercase;}
  .inv .monto .val{font-size:31px; font-weight:800; letter-spacing:-1px; line-height:1.1;}
  .inv .monto .nota{font-size:10.5px; opacity:.85;}

  /* Incluye */
  .grid{display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:4px;}
  .bloque{border:1px solid var(--linea); border-radius:8px; padding:15px 17px;}
  .bloque h4{font-size:12.5px; font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:7px;}
  .bloque h4 .ic{width:22px;height:22px;border-radius:5px;background:var(--suave);
                 display:inline-flex;align-items:center;justify-content:center;font-size:12px;}
  .bloque ul{list-style:none;}
  .bloque li{font-size:12.5px; color:var(--gris); padding-left:15px; position:relative; margin-bottom:4px;}
  .bloque li::before{content:''; position:absolute; left:0; top:8px; width:5px; height:5px;
                     border-radius:50%; background:var(--acento2);}

  /* Tablas */
  table{width:100%; border-collapse:collapse; font-size:13px;}
  thead th{background:var(--suave); text-align:left; padding:10px 14px; font-size:10px;
           letter-spacing:1.1px; text-transform:uppercase; color:var(--gris2); font-weight:700;
           border-bottom:1px solid var(--linea);}
  thead th.der{text-align:right;}
  tbody td{padding:11px 14px; border-bottom:1px solid var(--linea);}
  tbody td.der{text-align:right; font-weight:600; white-space:nowrap;}
  tbody tr:last-child td{border-bottom:none;}
  .tabla{border:1px solid var(--linea); border-radius:8px; overflow:hidden;}

  /* Renovación */
  .renov{display:flex; justify-content:space-between; align-items:center; gap:20px;
         background:var(--suave); border:1px solid var(--linea); border-radius:8px; padding:16px 20px;}
  .renov .t{font-size:13.5px; font-weight:700;}
  .renov .d{font-size:12.5px; color:var(--gris); margin-top:2px;}
  .renov .m{font-size:20px; font-weight:800; white-space:nowrap;}

  /* Ventajas */
  .vent{display:grid; grid-template-columns:1fr 1fr; gap:8px 22px;}
  .vent div{font-size:12.5px; color:var(--gris); padding-left:22px; position:relative;}
  .vent div::before{content:'✓'; position:absolute; left:0; top:0; color:var(--ok); font-weight:800;}

  /* Notas y validez */
  .notas{margin-top:16px; border-left:3px solid var(--acento2); background:var(--suave);
         padding:13px 17px; font-size:12.5px; color:var(--gris); white-space:pre-line;}
  .validez{margin-top:26px; display:flex; justify-content:space-between; align-items:center;
           border-top:1px solid var(--linea); padding-top:14px; font-size:12px; color:var(--gris2);}

  /* Firmas */
  .firmas{display:grid; grid-template-columns:1fr 1fr; gap:60px; margin-top:52px;}
  .firma{text-align:center;}
  .firma .linea{border-top:1px solid var(--tinta); margin-bottom:6px;}
  .firma .rol{font-size:11px; color:var(--gris2);}
  .firma .nom{font-size:12.5px; font-weight:600;}

  /* Pie */
  .pie{background:var(--tinta); color:#CBD5E1; padding:20px 54px; font-size:11.5px;
       display:flex; justify-content:space-between; gap:20px; flex-wrap:wrap;}
  .pie strong{color:#fff;}

  /* Barra de acciones (no se imprime) */
  .acciones{max-width:820px; margin:0 auto; display:flex; gap:9px; justify-content:flex-end; padding:0 4px;}
  .btn{border:none; border-radius:7px; padding:9px 16px; font-size:12.5px; font-weight:600;
       cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:6px;}
  .btn-p{background:var(--acento); color:#fff;}
  .btn-s{background:#fff; color:var(--tinta); border:1px solid var(--linea);}

  @media (max-width:760px){
    .pad{padding:28px 22px;} .grid,.vent,.firmas{grid-template-columns:1fr;}
    .inv{flex-direction:column;} .inv .monto{align-items:flex-start;}
    .cab{flex-direction:column;} .doc{text-align:left;} .titulo{font-size:23px;}
    .pie{padding:18px 22px;}
  }
  @media print{
    body{background:#fff;}
    .acciones{display:none !important;}
    .hoja{margin:0; box-shadow:none; max-width:100%;}
    .pad{padding:24px 30px;}
    h2.sec{page-break-after:avoid;} .bloque,.tabla,.renov,.inv{page-break-inside:avoid;}
    .firmas{page-break-inside:avoid;}
  }
</style>
</head>
<body>

<div class="acciones" style="margin:16px auto 8px;">
  <a class="btn btn-s" href="https://wa.me/{{ preg_replace('/\D/','',$proposal->client_phone) }}?text={{ urlencode('Le comparto nuestra propuesta comercial: '.url()->current()) }}" target="_blank">Compartir</a>
  <button class="btn btn-p" onclick="window.print()">Descargar PDF</button>
</div>

<div class="hoja">
  <div class="pad">

    {{-- ENCABEZADO --}}
    <div class="cab">
      <div class="marca">
        @if($logo)<img src="{{ $logo }}" alt="{{ $emp }}">@endif
        <div>
          <div class="nom">{{ $emp }}</div>
          @if($ruc)<div class="sub">RUC {{ $ruc }}</div>@endif
        </div>
      </div>
      <div class="doc">
        <div class="tipo">PROPUESTA COMERCIAL</div>
        <div class="num">N° {{ $proposal->number }}</div>
        <div class="fec">{{ ($proposal->created_at ?? now())->format('d/m/Y') }}</div>
      </div>
    </div>

    {{-- TÍTULO --}}
    <h1 class="titulo">Desarrollo de Tienda Virtual</h1>
    <p class="bajada">
      Ponemos su negocio en internet con una plataforma moderna, segura y fácil de administrar,
      que le permitirá recibir pedidos, gestionar sus productos y aumentar sus ventas desde cualquier lugar.
    </p>

    {{-- DATOS DEL CLIENTE --}}
    <div class="datos">
      <div class="fila">
        <div class="cel"><span class="et">Cliente</span><span class="va">{{ $proposal->client_name }}</span></div>
        <div class="cel"><span class="et">Negocio</span><span class="va">{{ $proposal->business_name ?: '—' }}</span></div>
      </div>
      <div class="fila">
        <div class="cel"><span class="et">Rubro</span><span class="va">{{ $proposal->rubro ?: '—' }}</span></div>
        <div class="cel"><span class="et">Ciudad</span><span class="va">{{ $proposal->city ?: '—' }}</span></div>
      </div>
      @if($proposal->client_phone || $proposal->client_email)
      <div class="fila">
        <div class="cel"><span class="et">Teléfono</span><span class="va">{{ $proposal->client_phone ?: '—' }}</span></div>
        <div class="cel"><span class="et">Correo</span><span class="va">{{ $proposal->client_email ?: '—' }}</span></div>
      </div>
      @endif
    </div>

    {{-- APERTURA: por que le escribimos a ESTE negocio. --}}
    @if(filled($proposal->apertura))
    <h2 class="sec">Por qué le escribimos</h2>
    <p style="margin:0 0 22px;line-height:1.65;color:#444">{{ $proposal->apertura }}</p>
    @endif

    {{-- DEMOSTRACION: que vea su tienda antes de leer el precio. --}}
    @if(filled($proposal->demo_url))
    <h2 class="sec">Vea primero, decida después</h2>
    <p style="margin:0 0 14px;line-height:1.65;color:#444">
      Preparamos una demostración con productos parecidos a los suyos, para que
      vea cómo quedaría su tienda en lugar de imaginársela.
    </p>
    <a href="{{ $proposal->demo_url }}" target="_blank" rel="noopener"
       style="display:block;margin:0 0 14px;padding:16px 18px;background:#f4f7fb;border:1px solid #dde4ec;border-radius:9px;text-decoration:none">
      <span style="display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#7b8794;margin-bottom:5px">Su demostración</span>
      <span style="font-size:18px;font-weight:700;color:#1c4e80;word-break:break-word">{{ preg_replace('#^https?://#', '', $proposal->demo_url) }}</span>
    </a>
    <p style="margin:0 0 22px;line-height:1.65;color:#444">
      Ábrala desde el celular: entre a un producto, use el buscador y pida una
      cotización por WhatsApp. Funciona de verdad; es el mismo sistema que
      quedaría operando, con otro nombre y otros productos.
    </p>
    @endif

    {{-- Otras tiendas como muestra de trabajo. Van DESPUES y en un bloque mas
         sobrio: la demo de su rubro es la que tiene que llevarse la atencion. --}}
    @php $demosExtra = array_values(array_filter((array) $proposal->demos_extra, fn ($u) => filled($u) && $u !== $proposal->demo_url)); @endphp
    @if(count($demosExtra))
    <h2 class="sec">Otras tiendas que hemos hecho</h2>
    <p style="margin:0 0 14px;line-height:1.65;color:#444">
      Todas están funcionando. Ábralas si quiere ver cómo resolvemos otros giros.
    </p>
    <div style="margin:0 0 22px">
      @foreach($demosExtra as $url)
      <a href="{{ $url }}" target="_blank" rel="noopener"
         style="display:block;margin-bottom:7px;padding:11px 14px;background:#fafbfc;border:1px solid #e6eaef;border-radius:8px;text-decoration:none;font-size:14px;color:#1c4e80;word-break:break-word">
        {{ preg_replace('#^https?://#', '', $url) }}
      </a>
      @endforeach
    </div>
    @endif

    {{-- INVERSIÓN --}}
    <h2 class="sec">Inversión</h2>
    <div class="inv">
      <div class="desc">
        <h3>Desarrollo e implementación</h3>
        <p>Pago único. Incluye todo el alcance detallado a continuación, configuración inicial y capacitación.</p>
      </div>
      <div class="monto">
        <span class="lab">Total</span>
        <span class="val">{{ $moneda }} {{ number_format($proposal->price, 0) }}</span>
        <span class="nota">IGV incluido</span>
      </div>
    </div>
    @if(filled($proposal->plan_motivo))
    <div style="margin:0 0 22px;padding:15px 17px;border-left:3px solid #1c4e80;background:#f7f9fb;border-radius:0 7px 7px 0">
      <p style="margin:0;line-height:1.6;color:#333">
        <strong>Le recomendamos el plan {{ ucfirst($proposal->plan_recomendado ?: 'Pro') }}.</strong>
        {{ $proposal->plan_motivo }}
      </p>
    </div>
    @endif

    {{-- ALCANCE --}}
    <h2 class="sec">Alcance del servicio</h2>
    <div class="grid">
      <div class="bloque">
        <h4><span class="ic">◈</span> Implementación</h4>
        <ul>
          <li>Desarrollo adaptado a su rubro de negocio</li>
          <li>Diseño responsive (PC, tablet y celular)</li>
          <li>Configuración inicial de la plataforma</li>
        </ul>
      </div>
      <div class="bloque">
        <h4><span class="ic">▤</span> Catálogo de productos</h4>
        <ul>
          <li>Carga inicial de hasta {{ number_format($proposal->products_included) }} productos</li>
          <li>Categorías y subcategorías</li>
          <li>Gestión de inventario</li>
        </ul>
      </div>
      <div class="bloque">
        <h4><span class="ic">▶</span> Ventas</h4>
        <ul>
          <li>Carrito de compras</li>
          <li>Sistema de cotizaciones</li>
          <li>Botón de WhatsApp</li>
          <li>Integración con pasarela de pagos</li>
          <li>Configuración básica de envíos</li>
        </ul>
      </div>
      <div class="bloque">
        <h4><span class="ic">▦</span> Panel administrativo</h4>
        <ul>
          <li>Gestión de ventas y pedidos</li>
          <li>Gestión de productos y proveedores</li>
          <li>Gestión de usuarios y permisos</li>
          <li>Precios para minoristas y mayoristas</li>
        </ul>
      </div>
      <div class="bloque">
        <h4><span class="ic">↗</span> Marketing</h4>
        <ul>
          <li>Optimización SEO</li>
          <li>Código QR exclusivo de su tienda</li>
        </ul>
      </div>
      <div class="bloque">
        <h4><span class="ic">✎</span> Cumplimiento y capacitación</h4>
        <ul>
          <li>Libro de Reclamaciones</li>
          <li>Capacitación para el uso de la plataforma</li>
        </ul>
      </div>
    </div>

    {{-- RENOVACIÓN --}}
    @if($proposal->price_renewal > 0)
    <h2 class="sec">Renovación anual</h2>
    <div class="renov">
      <div>
        <div class="t">Hosting, mantenimiento y continuidad del servicio</div>
        <div class="d">A partir del segundo año. Mantiene su plataforma disponible y operativa.</div>
      </div>
      <div class="m">{{ $moneda }} {{ number_format($proposal->price_renewal, 0) }} <span style="font-size:12px;font-weight:600;color:var(--gris2)">/ año</span></div>
    </div>
    @endif

    {{-- SERVICIOS ADICIONALES --}}
    @if(count($extras))
    <h2 class="sec">Servicios adicionales (opcionales)</h2>
    <div class="tabla">
      <table>
        <thead><tr><th>Servicio</th><th class="der">Inversión</th></tr></thead>
        <tbody>
          @foreach($extras as $ex)
          <tr>
            <td>{{ $ex['nombre'] ?? '' }}</td>
            <td class="der">
              {{ $moneda }} {{ number_format((float)($ex['precio'] ?? 0), 0) }}
              <span style="font-weight:500;color:var(--gris2);font-size:11.5px">
                {{ $periodos[$ex['periodo'] ?? 'unico'] ?? $ex['periodo'] }}
              </span>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif

    {{-- NOTAS --}}
    @if($proposal->extra_notes)
    <h2 class="sec">Consideraciones</h2>
    <div class="notas">{{ $proposal->extra_notes }}</div>
    @endif

    {{-- VENTAJAS --}}
    <h2 class="sec">Por qué trabajar con nosotros</h2>
    <div class="vent">
      <div>Diseño adaptado a su negocio</div>
      <div>Plataforma administrable y fácil de usar</div>
      <div>Compatible con celulares, tablets y PC</div>
      <div>Optimizada para aparecer en Google</div>
      <div>Capacitación incluida</div>
      <div>Escalable para nuevas funcionalidades</div>
      <div>Soporte y mantenimiento</div>
      <div>Entrega en plazos acordados</div>
    </div>

    {{-- VALIDEZ --}}
    <div class="validez">
      <span>Propuesta válida hasta el <strong style="color:var(--tinta)">{{ $proposal->valida_hasta->format('d/m/Y') }}</strong></span>
      <span>Documento N° {{ $proposal->number }}</span>
    </div>

    {{-- FIRMAS --}}
    <div class="firmas">
      <div class="firma">
        <div class="linea"></div>
        <div class="nom">{{ $emp }}</div>
        <div class="rol">Por el proveedor</div>
      </div>
      <div class="firma">
        <div class="linea"></div>
        <div class="nom">{{ $proposal->business_name ?: $proposal->client_name }}</div>
        <div class="rol">Por el cliente — Conformidad</div>
      </div>
    </div>

  </div>

  {{-- PIE --}}
  <div class="pie">
    <div><strong>{{ $emp }}</strong>@if($dir) · {{ $dir }}@endif</div>
    <div>
      @if($tel){{ $tel }}@endif
      @if($mail) · {{ $mail }}@endif
      @if($web) · {{ preg_replace('#^https?://#','',$web) }}@endif
    </div>
  </div>
</div>

<div class="acciones" style="margin:10px auto 30px;">
  <button class="btn btn-p" onclick="window.print()">Descargar PDF</button>
</div>

</body>
</html>
