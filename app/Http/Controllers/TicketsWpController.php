<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TicketsWpController extends Controller
{
    const API_URL = 'https://pruebatusuerte.com.pe/wp-json/bixo/v1/tickets';
    const API_KEY = 'bixo-tickets-2024';

    /**
     * Este modulo habla con el WordPress de UN cliente concreto
     * (pruebatusuerte.com.pe). Estaba abierto a cualquier proyecto cuyo
     * usuario tuviera `tickets.ver`, asi que desde otro negocio se listaban
     * y se borraban tickets ajenos. Esconder el enlace del menu no es una
     * barrera: la URL sigue ahi. Lo decide el proyecto.
     */
    private function proyectoDelModulo(): \App\Models\Project
    {
        $project = \App\Models\Project::findOrFail(session('comercial_project_id', 1));
        abort_unless((int) $project->setting('modulo_tickets_wp', 0) === 1, 404);

        return $project;
    }

    public function index(Request $request)
    {
        $project = $this->proyectoDelModulo();
        $buscar  = trim($request->get('buscar', ''));
        $offset  = (int)$request->get('offset', 0);
        $limit   = 100;

        $res = Http::timeout(10)->get(self::API_URL, [
            'key'    => self::API_KEY,
            'action' => 'listar',
            'limit'  => $limit,
            'offset' => $offset,
            'buscar' => $buscar,
        ]);

        $data    = $res->successful() ? $res->json() : ['ok' => false, 'tickets' => [], 'total' => 0];
        $tickets = $data['tickets'] ?? [];
        $total   = $data['total'] ?? 0;

        // Stats
        $stats = Http::timeout(10)->get(self::API_URL, [
            'key'    => self::API_KEY,
            'action' => 'stats',
        ])->json();

        return view('comercial.tickets-wp', compact('project', 'tickets', 'total', 'buscar', 'offset', 'limit', 'stats'));
    }

    public function buscar(Request $request)
    {
        $this->proyectoDelModulo();

        $dni = trim($request->get('dni', ''));
        if (!$dni) return response()->json(['ok' => false, 'error' => 'dni requerido']);

        $res = Http::timeout(10)->get(self::API_URL, [
            'key'    => self::API_KEY,
            'action' => 'buscar',
            'dni'    => $dni,
        ]);

        return response()->json($res->successful() ? $res->json() : ['ok' => false, 'tickets' => []]);
    }

    public function eliminar(Request $request)
    {
        $this->proyectoDelModulo();

        $codigo = trim($request->input('codigo', ''));
        if (!$codigo) return response()->json(['ok' => false, 'error' => 'codigo requerido']);

        /* Antes esto era `exec(curl ... &)`: disparaba y olvidaba, devolviendo
           siempre `ok:true` sin mirar el resultado. Si el WordPress estaba
           caido, el usuario leia "borrado" y el ticket seguia vivo. Ademas el
           `&` y `/dev/null` no funcionan en Windows, asi que en local no
           borraba nada en absoluto. Se usa el mismo cliente Http que el resto
           del archivo y se dice la verdad de lo que paso. */
        $res = Http::timeout(10)
            ->acceptJson()
            ->post('https://pruebatusuerte.com.pe/wp-json/bixo/v1/eliminar', [
                'key'    => self::API_KEY,
                'codigo' => $codigo,
            ]);

        if (! $res->successful()) {
            return response()->json([
                'ok'    => false,
                'error' => 'No se pudo eliminar el ticket: el servidor respondió '.$res->status().'.',
            ], 502);
        }

        return response()->json(['ok' => true] + (is_array($res->json()) ? $res->json() : []));
    }
}
