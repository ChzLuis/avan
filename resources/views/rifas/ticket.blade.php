<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=1200">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { width:1200px; height:480px; font-family:'Arial',sans-serif; display:flex; overflow:hidden; }

/* Panel izquierdo morado */
.left {
    width: 300px; min-width:300px; height:480px;
    background: linear-gradient(160deg, #7b2fbf 0%, #5a1a9a 100%);
    display: flex; flex-direction: column; align-items: center;
    padding: 20px 24px; color: white; position: relative;
}
.logo-box {
    width: 110px; height: 110px; margin-bottom: 10px;
    display: flex; align-items: center; justify-content: center;
}
.logo-box img { width: 100%; height: 100%; object-fit: contain; border-radius: 12px; }
.logo-text {
    font-size: 22px; font-weight: 900; text-align: center;
    line-height: 1.1; text-shadow: 2px 2px 0 rgba(0,0,0,.3);
    color: #FFD700; text-transform: uppercase; letter-spacing: 1px;
    margin-bottom: 4px;
}
.logo-sub { font-size: 13px; color: rgba(255,255,255,.8); margin-bottom: 14px; }

.data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
.data-table tr td { padding: 3px 0; font-size: 13px; }
.data-table tr td:first-child { color: rgba(255,255,255,.7); width: 68px; font-size: 12px; }
.data-table tr td:last-child {
    color: white; font-weight: 600;
    border-bottom: 1px solid rgba(255,255,255,.25);
    padding-bottom: 5px; padding-left: 4px;
    max-width: 160px; overflow: hidden; white-space: nowrap;
}

.ticket-num {
    margin-top: auto; background: white;
    color: #5a1a9a; font-size: 17px; font-weight: 900;
    padding: 7px 22px; border-radius: 8px; letter-spacing: 2px;
    box-shadow: 0 3px 10px rgba(0,0,0,.3);
}

/* Panel derecho — banner */
.right {
    flex: 1; position: relative; overflow: hidden;
}
.right img.banner {
    width: 100%; height: 100%; object-fit: cover;
}
.right .no-banner {
    width:100%; height:100%;
    background: linear-gradient(135deg, #f5a623 0%, #f7c948 100%);
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; font-weight: 900; color: white;
    text-shadow: 2px 2px 4px rgba(0,0,0,.3);
}

/* Número de ticket overlay en el banner */
.ticket-badge {
    position: absolute; bottom: 24px; right: 24px;
    background: white; border: 3px solid #e0272d;
    padding: 8px 20px; border-radius: 8px;
    font-size: 20px; font-weight: 900; color: #1a1a1a;
    letter-spacing: 2px; box-shadow: 0 3px 12px rgba(0,0,0,.2);
}
.price-badge {
    position: absolute; bottom: 24px; right: 230px;
    background: #e0272d; padding: 8px 16px; border-radius: 8px;
    font-size: 20px; font-weight: 900; color: white;
    box-shadow: 0 3px 12px rgba(0,0,0,.2);
}
</style>
</head>
<body>

{{-- Panel izquierdo --}}
<div class="left">
    <div class="logo-box">
        @if($negocio_logo)
        <img src="{{ $negocio_logo }}" alt="logo">
        @else
        <div style="width:100%;height:100%;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:32px;">🎟️</div>
        @endif
    </div>
    <div class="logo-text">{{ $negocio }}</div>
    <div class="logo-sub">{{ $evento ?? '' }}</div>

    <table class="data-table">
        <tr><td>Nombre:</td><td>{{ $nombre }}</td></tr>
        <tr><td>DNI/C.E.:</td><td>{{ $dni }}</td></tr>
        <tr><td>Celular:</td><td>{{ $celular }}</td></tr>
        <tr><td>Ciudad:</td><td>{{ $ciudad }}</td></tr>
    </table>

    <div class="ticket-num">N° {{ $ticket_code }}</div>
</div>

{{-- Panel derecho — banner de la rifa --}}
<div class="right">
    @if($banner_url)
    <img class="banner" src="{{ $banner_url }}" alt="banner">
    @else
    <div class="no-banner">🏆 {{ $rifa_nombre }}</div>
    @endif

    @if($precio)
    <div class="price-badge">S/{{ $precio }}</div>
    @endif
    <div class="ticket-badge">N° {{ $ticket_code }}</div>
</div>

</body>
</html>
