<?php
namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Project;

class MesaController extends Controller
{
    private function project(): Project
    {
        return Project::findOrFail(session('comercial_project_id'));
    }

    private function s(Project $project, string $key, $default = null)
    {
        return $project->settings()->where('key', $key)->value('value') ?? $default;
    }

    public function index()
    {
        $project    = $this->project();
        $catalogUrl = $project->custom_domain
            ? 'https://' . $project->custom_domain
            : url('/' . $project->slug);

        $tableCount = (int) ($this->s($project, 'qr_table_count', 10));
        $sectores   = json_decode($this->s($project, 'qr_sectores', '["Salón"]'), true) ?? ['Salón'];

        // Construir lista de mesas estáticas
        $mesas = [];
        for ($i = 1; $i <= $tableCount; $i++) {
            $mesas[] = ['numero' => (string)$i, 'sector' => $sectores[0] ?? 'Salón'];
        }
        // Agregar mesas adicionales desde pedidos activos (VIP, T1..., B1...)
        $pedidosActivos = $project->orders()
            ->whereNotNull('table_number')
            ->whereIn('status', ['pending', 'process'])
            ->whereNotIn('kitchen_status', ['served'])
            ->pluck('table_number')
            ->unique()
            ->values();
        $numerosEstaticos = array_column($mesas, 'numero');
        foreach ($pedidosActivos as $num) {
            if (in_array((string)$num, $numerosEstaticos)) continue;
            // Detectar sector por prefijo
            if (str_starts_with($num, 'T'))   $sec = 'Terraza';
            elseif (str_starts_with($num, 'B')) $sec = 'Barra';
            else $sec = $sectores[0] ?? 'Salón';
            $mesas[] = ['numero' => (string)$num, 'sector' => $sec];
        }

        // Pedidos activos (no cancelados, no entregados)
        $pedidosRaw = $project->orders()
            ->whereNotNull('table_number')
            ->whereIn('status', ['pending', 'process'])
            ->whereNotIn('kitchen_status', ['served'])
            ->with('items')
            ->orderBy('created_at')
            ->get();

        $pedidos = $pedidosRaw->map(function ($o) {
            return [
                'id'             => $o->id,
                'table_number'   => $o->table_number,
                'client_name'    => $o->client_name,
                'status'         => $o->status,
                'kitchen_status' => $o->kitchen_status ?? 'pending',
                'total'          => $o->total,
                'notes'          => $o->notes,
                'created_at'     => $o->created_at->toISOString(),
                'items'          => $o->items->map(function ($i) {
                    return [
                        'id'       => $i->id,
                        'name'     => $i->name,
                        'quantity' => $i->quantity,
                        'price'    => (float) $i->price,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $mesasData = [
            'mesas'   => $mesas,
            'sectores'=> $sectores,
            'pedidos' => $pedidos,
        ];

        /* Estado del salon guardado en el servidor: llega ya resuelto a la
           vista, para que las dos tablets arranquen viendo lo mismo. */
        $salon = [
            'mozos'   => json_decode((string) $project->setting('salon_mozos', '{}'), true)   ?: (object) [],
            'uniones' => json_decode((string) $project->setting('salon_uniones', '{}'), true) ?: (object) [],
            'espera'  => json_decode((string) $project->setting('salon_espera', '[]'), true)  ?: [],
        ];

        return view('comercial.mesas', compact('project', 'mesasData', 'catalogUrl', 'salon'));
    }

    // GET /bixosales/mesas/data — polling JSON
    /**
     * El estado "blando" del salon: quien atiende cada mesa, que mesas estan
     * unidas y quien espera sitio.
     *
     * Vivia en `localStorage`, o sea en UN navegador: la tablet de la puerta
     * apuntaba a alguien en la lista de espera y la de la barra no lo veia, y
     * todo se perdia al limpiar el navegador. No son datos fiscales ni piden
     * tabla propia, asi que se guardan en los ajustes del proyecto, que ya
     * existen y ya estan aislados por negocio.
     */
    public function guardarEstado(Request $request)
    {
        $project = $this->project();

        $datos = $request->validate([
            'mozos'    => ['nullable', 'array'],
            'uniones'  => ['nullable', 'array'],
            'espera'   => ['nullable', 'array'],
        ]);

        foreach (['mozos', 'uniones', 'espera'] as $clave) {
            if (array_key_exists($clave, $datos)) {
                $project->settings()->updateOrCreate(
                    ['key'   => 'salon_'.$clave],
                    ['value' => json_encode($datos[$clave], JSON_UNESCAPED_UNICODE)]
                );
            }
        }

        return response()->json(['ok' => true]);
    }

    public function data()
    {
        $project = $this->project();

        $pedidos = $project->orders()
            ->whereNotNull('table_number')
            ->whereIn('status', ['pending', 'process'])
            ->whereNotIn('kitchen_status', ['served'])
            ->with('items')
            ->orderBy('created_at')
            ->get()
            ->map(function ($o) {
                return [
                    'id'             => $o->id,
                    'table_number'   => $o->table_number,
                    'client_name'    => $o->client_name,
                    'status'         => $o->status,
                    'kitchen_status' => $o->kitchen_status ?? 'pending',
                    'total'          => $o->total,
                    'notes'          => $o->notes,
                    'created_at'     => $o->created_at->toISOString(),
                    'items'          => $o->items->map(function ($i) {
                        return [
                            'id'       => $i->id,
                            'name'     => $i->name,
                            'quantity' => $i->quantity,
                            'price'    => (float) $i->price,
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

        return response()->json(['pedidos' => $pedidos]);
    }
}
