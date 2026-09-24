<?php

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Ventas\Models\Proposal;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * PROPUESTAS COMERCIALES (proformas).
 *
 * El vendedor registra a quién va la propuesta y su precio; el sistema genera
 * un documento profesional con enlace propio que el cliente abre y puede
 * guardar como PDF desde el navegador.
 */
class ProposalController extends Controller
{
    /** La numeracion de propuestas arranca aqui (antes era PRO-0001). */
    private const CORRELATIVO_INICIAL = 1101;

    /** Reglas comunes de validación. */
    private function rules(bool $creando = true): array
    {
        return [
            'client_name'       => ($creando ? 'required' : 'sometimes|required') . '|string|max:150',
            'business_name'     => 'nullable|string|max:150',
            'rubro'             => 'nullable|string|max:100',
            'client_phone'      => 'nullable|string|max:50',
            'client_email'      => 'nullable|email|max:150',
            'city'              => 'nullable|string|max:100',
            'demo_url'          => 'nullable|url|max:255',
            // Demos adicionales que se adjuntan como muestra de trabajo.
            'demos_extra'       => 'nullable|array|max:6',
            'demos_extra.*'     => 'url|max:255',
            'apertura'          => 'nullable|string|max:2000',
            'plan_recomendado'  => 'nullable|in:start,pro,business',
            'plan_motivo'       => 'nullable|string|max:1000',
            'lost_reason'       => 'nullable|string|max:255',
            'price'             => 'nullable|numeric|min:0',
            'price_renewal'     => 'nullable|numeric|min:0',
            'products_included' => 'nullable|integer|min:0',
            'valid_days'        => 'nullable|integer|min:1|max:365',
            'extras'            => 'nullable|array',
            'extras.*.nombre'   => 'required_with:extras|string|max:150',
            'extras.*.precio'   => 'required_with:extras|numeric|min:0',
            'extras.*.periodo'  => 'nullable|string|max:20',   // unico|mensual|sesion
            'extra_notes'       => 'nullable|string',
            'status'            => 'nullable|in:borrador,enviada,conversando,aceptada,rechazada',
        ];
    }

    public function index()
    {
        /** @var Project $project */
        $project   = app('active_project');
        $proposals = $project->proposals()->latest()->get();

        // Catalogo de demos y textos por rubro: un solo sitio, para que la lista
        // no se quede vieja como paso antes (tenia 3 demos y ya habia 13).
        $demos = \App\Modules\Ventas\Support\DemosPorRubro::opciones();
        $textos = [
            'aperturas' => \App\Modules\Ventas\Support\DemosPorRubro::APERTURAS,
            'motivos' => \App\Modules\Ventas\Support\DemosPorRubro::MOTIVOS,
            'rubros' => array_map(
                fn ($d) => $d['etiqueta'],
                \App\Modules\Ventas\Support\DemosPorRubro::DEMOS
            ),
        ];

        // Mensajes listos para copiar. Se arman en el servidor y viajan como
        // JSON al componente: en un atributo HTML los saltos de linea rompen
        // el marcado y el boton deja de responder.
        $mensajes = [];
        foreach ($proposals as $p) {
            $negocio = $p->business_name ?: 'su negocio';
            $mensajes[$p->id] = [
                'envio' => "Hola {$p->client_name}, ¿cómo está?

"
                    ."Le comparto la propuesta para la tienda en línea de {$negocio}. "
                    ."Ahí encontrará todo lo que incluye, la inversión y ejemplos de tiendas que ya trabajamos.

"
                    .route('proposal.publica', $p->token)."

"
                    ."Se abre desde el celular. Cualquier duda me escribe por aquí.",
                'lista' => "¡Excelente, {$p->client_name}! Gracias por la confianza.

"
                    ."Para empezar con su tienda, por favor respóndame estos datos:

"
                    ."1. Nombre del negocio (como quiere que aparezca)
"
                    ."2. ¿Cómo quiere que se llame su página? (ej: minegocio.com)
"
                    ."3. RUC
"
                    ."4. Razón social (como figura en SUNAT)
"
                    ."5. Rubro
"
                    ."6. Dirección del local y distrito
"
                    ."7. Persona de contacto
"
                    ."8. Celular / WhatsApp
"
                    ."9. Correo (ahí enviamos los accesos)
"
                    ."10. Redes sociales

"
                    ."Y si los tiene a mano, envíeme por aquí:
"
                    ."• Su logo
"
                    ."• Fotos de sus productos

"
                    ."Puede responder todo en un solo mensaje. ¡Gracias!",
            ];
        }

        return view('ventas::proposals.index', compact('project', 'proposals', 'demos', 'textos', 'mensajes'));
    }

    public function store(Request $request)
    {
        /** @var Project $project */
        $project = app('active_project');
        $data = $request->validate($this->rules());

        $ultimo = (int) $project->proposals()
            ->where('number', 'like', 'PRO-%')
            ->selectRaw('MAX(CAST(SUBSTRING(number, 5) AS UNSIGNED)) AS n')
            ->value('n');
        $n = max($ultimo + 1, self::CORRELATIVO_INICIAL);
        $data['project_id'] = $project->id;
        $data['number']     = 'PRO-' . $n;
        $data['token']      = Str::random(40);
        $data['status']     = 'borrador';
        $data['plan_recomendado'] = $data['plan_recomendado'] ?? 'pro';
        // El precio sigue al plan recomendado salvo que se escriba otro.
        $data['price']         = $data['price']
            ?? ['start' => 490, 'pro' => 590, 'business' => 690][$data['plan_recomendado']] ?? 490;
        $data['price_renewal'] = $data['price_renewal'] ?? 100;
        $data['products_included'] = $data['products_included'] ?? 200;
        $data['valid_days']    = $data['valid_days'] ?? 15;

        $proposal = Proposal::create($data);

        return response()->json([
            'proposal' => $proposal,
            'url'      => route('proposal.publica', $proposal->token),
        ]);
    }

    public function update(Request $request, Proposal $proposal)
    {
        /** @var Project $project */
        $project = app('active_project');
        abort_unless($proposal->project_id === $project->id, 403);

        $data = $request->validate($this->rules(false));
        if (($data['status'] ?? null) === 'enviada' && !$proposal->sent_at) {
            $data['sent_at'] = now();
        }
        // Una propuesta cerrada deja fecha: sin esto no se sabe cuanto tardo
        // el cliente en decidir, ni cuales llevan semanas sin respuesta.
        if (in_array($data['status'] ?? null, ['aceptada', 'rechazada'], true)) {
            $data['closed_at'] = $proposal->closed_at ?? now();
        }
        /* Una propuesta sin token (creada antes de que existiera el enlace
           publico, o importada) tumbaba la edicion entera con un 500 al
           generar la URL. Se le da uno al vuelo: editarla no puede fallar por
           algo que el usuario no sabe ni que existe. */
        if (blank($proposal->token)) {
            $data['token'] = Str::random(40);
        }

        $proposal->update($data);
        $proposal->refresh();

        return response()->json([
            'proposal' => $proposal,
            'url'      => route('proposal.publica', $proposal->token),
        ]);
    }

    public function destroy(Proposal $proposal)
    {
        /** @var Project $project */
        $project = app('active_project');
        abort_unless($proposal->project_id === $project->id, 403);
        $proposal->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * El MISMO formulario de alta, en pagina propia.
     *
     * Se manda por WhatsApp cuando el cliente ya dijo que si: asi no tiene que
     * volver a recorrer la propuesta entera para llegar al formulario.
     */
    public function formularioAlta(string $token)
    {
        $proposal = Proposal::where('token', $token)->firstOrFail();
        $project  = $proposal->project;

        return view('ventas::proposals.alta', [
            'proposal' => $proposal,
            'project'  => $project,
            'settings' => $project->settings()->pluck('value', 'key'),
        ]);
    }

    /**
     * El cliente ACEPTA la propuesta y deja los datos de su negocio.
     *
     * Dos cosas en un solo acto: el si queda con fecha y rastro, y los datos
     * que antes se pedian por WhatsApp uno a uno (RUC, razon social, logo)
     * llegan completos. Sin login: la llave es el token del enlace.
     */
    public function aceptar(Request $request, string $token)
    {
        $proposal = Proposal::where('token', $token)->firstOrFail();

        // Ya aceptada: no se repisa la fecha ni los datos originales si el
        // cliente vuelve a abrir el enlace y pulsa otra vez.
        if ($proposal->accepted_at) {
            return back()->with('success', 'Ya recibimos tu aceptación. Nos comunicamos contigo muy pronto.');
        }

        $datos = $request->validate([
            'onb_negocio'      => ['required', 'string', 'max:150'],
            'onb_ruc'          => ['nullable', 'string', 'max:15'],
            'onb_razon_social' => ['nullable', 'string', 'max:200'],
            'onb_rubro'        => ['nullable', 'string', 'max:100'],
            'onb_direccion'    => ['nullable', 'string', 'max:220'],
            'onb_distrito'     => ['nullable', 'string', 'max:100'],
            'onb_contacto'     => ['required', 'string', 'max:150'],
            'onb_celular'      => ['required', 'string', 'max:40'],
            'onb_email'        => ['required', 'email', 'max:150'],
            'onb_dominio'      => ['required', 'string', 'max:150'],
            'onb_redes'        => ['nullable', 'string', 'max:300'],
            'onb_notas'        => ['nullable', 'string', 'max:2000'],
            'archivos'         => ['nullable', 'array', 'max:8'],
            'archivos.*'       => ['file', 'mimes:jpg,jpeg,png,webp,pdf,zip', 'max:10240'],
        ], [
            'onb_negocio.required'  => 'Necesitamos el nombre de tu negocio.',
            'onb_contacto.required' => 'Dinos con quién coordinamos.',
            'onb_celular.required'  => 'Necesitamos un celular para contactarte.',
            'onb_email.required'    => 'Necesitamos un correo para enviarte los accesos.',
            'onb_dominio.required'  => 'Dinos cómo quieres que se llame tu página.',
        ]);

        $guardados = [];
        foreach ((array) $request->file('archivos', []) as $archivo) {
            if (! $archivo) {
                continue;
            }
            $guardados[] = [
                'path' => $archivo->store("propuestas/{$proposal->id}", 'public'),
                'nombre' => $archivo->getClientOriginalName(),
            ];
        }

        $proposal->update($datos + [
            'onb_archivos' => $guardados ?: null,
            'status' => 'aceptada',
            'accepted_at' => now(),
            'accepted_ip' => $request->ip(),
            'accepted_by' => $datos['onb_contacto'],
            'closed_at' => $proposal->closed_at ?? now(),
        ]);

        return back()->with('success', '¡Gracias! Recibimos tu aceptación y tus datos. Nos comunicamos contigo dentro de las próximas horas.');
    }

    /**
     * Vista PÚBLICA de la propuesta (la que ve el cliente por enlace).
     * Diseñada para verse bien en pantalla y al imprimir/guardar como PDF.
     */
    public function publica(string $token)
    {
        $proposal = Proposal::where('token', $token)->firstOrFail();
        $project  = $proposal->project;
        $settings = $project->settings()->pluck('value', 'key');

        // Marcar como vista/enviada la primera vez que el cliente la abre.
        if ($proposal->status === 'borrador') {
            $proposal->update(['status' => 'enviada', 'sent_at' => $proposal->sent_at ?? now()]);
        }

        return view('ventas::proposals.publica', compact('proposal', 'project', 'settings'));
    }
}
