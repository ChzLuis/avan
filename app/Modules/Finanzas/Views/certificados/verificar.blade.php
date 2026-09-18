<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Certificado</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Inter, sans-serif; background: #f5f5f3; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 20px; max-width: 520px; width: 100%; padding: 40px; box-shadow: 0 4px 30px rgba(0,0,0,.08); }
        .logo { font-size: 20px; font-weight: 900; color: #0f1117; margin-bottom: 32px; }
        .logo em { color: #1D9E75; font-style: normal; }
        .status-valid   { background: #EAF3DE; border: 1.5px solid #97C459; border-radius: 14px; padding: 20px 24px; margin-bottom: 28px; }
        .status-invalid { background: #fef2f2; border: 1.5px solid #fca5a5; border-radius: 14px; padding: 20px 24px; margin-bottom: 28px; }
        .status-icon { font-size: 32px; margin-bottom: 8px; }
        .status-title { font-size: 16px; font-weight: 800; margin-bottom: 4px; }
        .valid   .status-title { color: #0F6E56; }
        .invalid .status-title { color: #dc2626; }
        .status-sub { font-size: 13px; color: #666; }
        .field { margin-bottom: 16px; }
        .field-label { font-size: 10px; font-weight: 700; color: #aaa; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 4px; }
        .field-value { font-size: 15px; font-weight: 600; color: #1a1a1a; }
        .field-value.big { font-size: 20px; font-weight: 800; }
        .code-box { background: #f8f8f6; border: 1px solid #e5e5e5; border-radius: 10px; padding: 12px 16px; font-family: monospace; font-size: 15px; color: #444; letter-spacing: .05em; margin-bottom: 24px; }
        .badge { display: inline-flex; align-items: center; gap: 5px; background: #EAF3DE; border: 1px solid #97C459; border-radius: 20px; padding: 4px 12px; font-size: 11px; font-weight: 700; color: #0F6E56; margin-right: 6px; margin-bottom: 6px; }
        .footer { margin-top: 28px; padding-top: 20px; border-top: 1px solid #f0f0f0; font-size: 11px; color: #bbb; text-align: center; }
        .not-found { text-align: center; }
        .not-found h1 { font-size: 24px; font-weight: 800; color: #1a1a1a; margin-bottom: 8px; }
        .not-found p { font-size: 14px; color: #888; }
        .revocado { background: #fff7ed; border: 1.5px solid #fed7aa; border-radius: 14px; padding: 20px 24px; margin-bottom: 28px; }
        .revocado .status-title { color: #ea580c; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">BI<em>XO</em> · Verificación de Certificados</div>

    @if(!$cert)
        <div class="not-found">
            <div style="font-size:48px;margin-bottom:16px;">❌</div>
            <h1>Certificado no encontrado</h1>
            <p>El código ingresado no corresponde a ningún certificado emitido en nuestra plataforma.</p>
            <p style="margin-top:8px;font-size:12px;color:#bbb;">Si crees que es un error, contacta a la institución que lo emitió.</p>
        </div>
    @elseif($cert->estado === 'revocado')
        <div class="revocado">
            <div class="status-icon">⚠️</div>
            <div class="status-title" style="color:#ea580c;">Certificado Revocado</div>
            <div class="status-sub">Este certificado fue emitido pero posteriormente anulado por la institución.</div>
        </div>
        <div class="field">
            <div class="field-label">Código</div>
            <div class="code-box">{{ $cert->codigo }}</div>
        </div>
    @else
        @php $valido = $cert->isValido(); @endphp
        <div class="{{ $valido ? 'status-valid' : 'status-invalid' }}">
            <div class="status-icon">{{ $valido ? '✅' : '⏰' }}</div>
            <div class="status-title" style="color:{{ $valido ? '#0F6E56' : '#dc2626' }}">
                {{ $valido ? 'Certificado válido y auténtico' : 'Certificado vencido' }}
            </div>
            <div class="status-sub">
                {{ $valido
                    ? 'Este certificado fue emitido por ' . ($cert->institucion ?: $cert->project->name) . ' y su autenticidad está confirmada.'
                    : 'Este certificado superó su fecha de vencimiento.' }}
            </div>
        </div>

        <div class="field">
            <div class="field-label">Alumno</div>
            <div class="field-value big">{{ $cert->alumno_nombre }}</div>
            @if($cert->alumno_dni)
                <div style="font-size:12px;color:#aaa;margin-top:2px;">DNI: {{ $cert->alumno_dni }}</div>
            @endif
        </div>

        <div class="field">
            <div class="field-label">Programa / Curso</div>
            <div class="field-value">{{ $cert->curso_nombre }}</div>
        </div>

        <div style="margin-bottom:16px;">
            @if($cert->curso_horas)  <span class="badge">🕐 {{ $cert->curso_horas }}</span> @endif
            @if($cert->curso_nivel)  <span class="badge">⭐ {{ $cert->curso_nivel }}</span> @endif
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div class="field" style="margin-bottom:0">
                <div class="field-label">Fecha de emisión</div>
                <div class="field-value">{{ $cert->fecha_emision->format('d/m/Y') }}</div>
            </div>
            @if($cert->fecha_vencimiento)
            <div class="field" style="margin-bottom:0">
                <div class="field-label">Vence</div>
                <div class="field-value">{{ $cert->fecha_vencimiento->format('d/m/Y') }}</div>
            </div>
            @endif
        </div>

        @if($cert->instructor)
        <div class="field">
            <div class="field-label">Instructor</div>
            <div class="field-value">{{ $cert->instructor }}</div>
        </div>
        @endif

        <div class="field">
            <div class="field-label">Institución</div>
            <div class="field-value">{{ $cert->institucion ?: $cert->project->name }}</div>
        </div>

        <div class="field">
            <div class="field-label">Código de verificación</div>
            <div class="code-box">{{ $cert->codigo }}</div>
        </div>
    @endif

    <div class="footer">
        Certificados digitales verificables · BIXO by AVAN · avan.pe
    </div>
</div>
</body>
</html>
