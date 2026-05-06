<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
  .wrapper { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
  .header { background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 40px 40px 32px; text-align: center; }
  .header h1 { color: #fff; font-size: 26px; font-weight: 800; margin: 0 0 6px; }
  .header p { color: rgba(255,255,255,.75); margin: 0; font-size: 15px; }
  .body { padding: 36px 40px; }
  .body p { color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
  .credentials { background: #f8fafc; border: 2px solid #e0e7ff; border-radius: 12px; padding: 24px; margin: 24px 0; }
  .credentials .row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
  .credentials .row:last-child { border-bottom: none; padding-bottom: 0; }
  .credentials .label { color: #6b7280; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
  .credentials .value { color: #111827; font-size: 15px; font-weight: 700; font-family: monospace; }
  .btn { display: block; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff !important; text-decoration: none; text-align: center; padding: 16px 32px; border-radius: 10px; font-size: 16px; font-weight: 700; margin: 28px 0; }
  .features { margin: 24px 0; }
  .features h3 { color: #111827; font-size: 15px; font-weight: 700; margin: 0 0 12px; }
  .feature-tag { display: inline-block; background: #ede9fe; color: #5b21b6; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 20px; margin: 3px; }
  .expiry { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; padding: 14px 18px; color: #92400e; font-size: 14px; margin: 20px 0; }
  .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 24px 40px; text-align: center; color: #9ca3af; font-size: 13px; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div style="font-size:48px;margin-bottom:12px;">🎉</div>
    <h1>¡Tu demo está lista!</h1>
    <p>{{ $demo->business_name }} ya tiene su plataforma activa</p>
  </div>

  <div class="body">
    <p>Hola <strong>{{ $demo->contact_name }}</strong>,</p>
    <p>Creamos tu demo de <strong>BIXO</strong> para <strong>{{ $demo->business_name }}</strong>. Ya puedes ingresar con las siguientes credenciales:</p>

    <div class="credentials">
      <div class="row">
        <span class="label">Email</span>
        <span class="value">{{ $demo->email }}</span>
      </div>
      <div class="row">
        <span class="label">Contraseña</span>
        <span class="value">{{ $password }}</span>
      </div>
      <div class="row">
        <span class="label">Rubro</span>
        <span class="value">{{ ucfirst($demo->rubro) }}</span>
      </div>
    </div>

    <a href="{{ route('login') }}" class="btn">👉 Ir a mi demo ahora</a>

    <div class="features">
      <h3>Módulos activados para ti:</h3>
      @foreach($demo->features_selected as $feature)
        <span class="feature-tag">✅ {{ $feature }}</span>
      @endforeach
    </div>

    <div class="expiry">
      ⏰ <strong>Tu demo expira el {{ $demo->expires_at->format('d/m/Y') }}</strong> (14 días).
      Si quieres extender o contratar el plan completo, responde este correo.
    </div>

    <p style="color:#6b7280;font-size:14px;">Si tienes alguna duda o necesitas ayuda para configurar tu negocio, estamos disponibles para guiarte.</p>
  </div>

  <div class="footer">
    <p><strong>BIXO Platform</strong> — La plataforma todo-en-uno para tu negocio</p>
    <p>Este correo fue generado automáticamente. No respondas directamente.</p>
  </div>
</div>
</body>
</html>
