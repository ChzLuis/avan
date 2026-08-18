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

        $payload = escapeshellarg(json_encode(['key' => self::API_KEY, 'codigo' => $codigo]));
        exec("curl -s -m 5 -X POST -H 'Content-Type: application/json' -d {$payload} https://pruebatusuerte.com.pe/wp-json/bixo/v1/eliminar > /dev/null 2>&1 &");

        return response()->json(['ok' => true]);
    }
}
