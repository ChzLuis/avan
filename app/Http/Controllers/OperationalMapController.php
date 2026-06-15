<?php

namespace App\Http\Controllers;

use App\Models\OperationalMap;
use App\Models\OperationalObject;
use App\Models\OperationalEvent;
use App\Models\OperationalRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class OperationalMapController extends Controller
{
    private function project(): Project
    {
        return app('active_project');
    }

    // ── Vista principal del mapa ───────────────────────────────────────────────

    public function index()
    {
        $project = $this->project();

        $map = $project->operationalMaps()->where('is_default', true)->first()
            ?? $project->operationalMaps()->first();

        $maps      = $project->operationalMaps()->orderBy('sort_order')->get();
        $employees = $project->employees()->where('is_active', true)->orderBy('name')->get();

        return view('mapa.index', compact('project', 'map', 'maps', 'employees'));
    }

    // ── API: obtener objetos del mapa ─────────────────────────────────────────

    public function objects(OperationalMap $map): JsonResponse
    {
        $project = $this->project();
        abort_unless($map->project_id === $project->id, 403);

        $objects = $map->activeObjects()
            ->with('responsible')
            ->get()
            ->map(fn($o) => $o->toMapArray());

        return response()->json([
            'objects'    => $objects,
            'avan_score' => $this->calcAvanScore($project),
            'summary'    => $this->calcSummary($project),
        ]);
    }

    // ── API: mover objeto en el mapa ──────────────────────────────────────────

    public function move(Request $request, OperationalObject $object): JsonResponse
    {
        $this->authorizeObject($object);
        $request->validate(['pos_x' => 'required|integer', 'pos_y' => 'required|integer']);

        $object->update(['pos_x' => $request->pos_x, 'pos_y' => $request->pos_y]);

        return response()->json(['ok' => true]);
    }

    // ── API: cambiar estado ───────────────────────────────────────────────────

    public function changeStatus(Request $request, OperationalObject $object): JsonResponse
    {
        $this->authorizeObject($object);
        $request->validate(['status' => 'required|string']);

        $object->changeStatus($request->status, auth()->id());

        return response()->json(['ok' => true, 'object' => $object->fresh(['responsible'])->toMapArray()]);
    }

    // ── API: agregar alerta ───────────────────────────────────────────────────

    public function addAlert(Request $request, OperationalObject $object): JsonResponse
    {
        $this->authorizeObject($object);
        $request->validate(['text' => 'required|string|max:200']);

        $object->addAlert($request->text);

        OperationalEvent::create([
            'project_id'  => $object->project_id,
            'object_id'   => $object->id,
            'user_id'     => auth()->id(),
            'type'        => 'alert_added',
            'note'        => $request->text,
            'occurred_at' => now(),
        ]);

        return response()->json(['ok' => true, 'alerts' => $object->fresh()->alerts]);
    }

    // ── API: limpiar alertas ──────────────────────────────────────────────────

    public function clearAlerts(OperationalObject $object): JsonResponse
    {
        $this->authorizeObject($object);
        $object->clearAlerts();

        OperationalEvent::create([
            'project_id'  => $object->project_id,
            'object_id'   => $object->id,
            'user_id'     => auth()->id(),
            'type'        => 'alert_cleared',
            'occurred_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    // ── API: actualizar monto ─────────────────────────────────────────────────

    public function updateAmount(Request $request, OperationalObject $object): JsonResponse
    {
        $this->authorizeObject($object);
        $request->validate(['amount' => 'required|numeric|min:0']);

        $object->update(['current_amount' => $request->amount]);

        OperationalEvent::create([
            'project_id'  => $object->project_id,
            'object_id'   => $object->id,
            'user_id'     => auth()->id(),
            'type'        => 'amount_update',
            'payload'     => ['amount' => $request->amount],
            'occurred_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    // ── API: asignar responsable ──────────────────────────────────────────────

    public function assignResponsible(Request $request, OperationalObject $object): JsonResponse
    {
        $this->authorizeObject($object);
        $request->validate(['employee_id' => 'nullable|exists:employees,id']);

        $old = $object->responsible_id;
        $object->update(['responsible_id' => $request->employee_id]);

        OperationalEvent::create([
            'project_id'  => $object->project_id,
            'object_id'   => $object->id,
            'user_id'     => auth()->id(),
            'type'        => 'responsible_change',
            'payload'     => ['from' => $old, 'to' => $request->employee_id],
            'occurred_at' => now(),
        ]);

        return response()->json(['ok' => true, 'object' => $object->fresh(['responsible'])->toMapArray()]);
    }

    // ── API: crear solicitud ──────────────────────────────────────────────────

    public function createRequest(Request $request, OperationalObject $object): JsonResponse
    {
        $this->authorizeObject($object);
        $request->validate([
            'type'        => 'required|string',
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'priority'    => 'integer|min:1|max:5',
        ]);

        $req = OperationalRequest::create([
            'project_id'  => $object->project_id,
            'object_id'   => $object->id,
            'created_by'  => auth()->id(),
            'type'        => $request->type,
            'title'       => $request->title,
            'description' => $request->description,
            'priority'    => $request->priority ?? 1,
            'status'      => 'pending',
        ]);

        $object->addAlert($request->title);

        return response()->json(['ok' => true, 'request' => $req]);
    }

    // ── API: historial del objeto ─────────────────────────────────────────────

    public function history(OperationalObject $object): JsonResponse
    {
        $this->authorizeObject($object);

        $events = $object->events()->with('user')->limit(50)->get()->map(fn($e) => [
            'id'          => $e->id,
            'type'        => $e->type,
            'type_label'  => $e->typeLabel(),
            'status_from' => $e->status_from,
            'status_to'   => $e->status_to,
            'note'        => $e->note,
            'user'        => $e->user?->name,
            'occurred_at' => $e->occurred_at->format('d/m H:i'),
        ]);

        $requests = $object->requests()->with('assignedTo')->latest()->get();

        return response()->json([
            'object'   => $object->fresh(['responsible'])->toMapArray(),
            'events'   => $events,
            'requests' => $requests,
        ]);
    }

    // ── CRUD de objetos (modo diseño) ─────────────────────────────────────────

    public function storeObject(Request $request, OperationalMap $map): JsonResponse
    {
        $project = $this->project();
        abort_unless($map->project_id === $project->id, 403);

        $request->validate([
            'type'  => 'required|string',
            'label' => 'required|string|max:100',
        ]);

        $defaults = $this->defaultsForType($request->type);

        $extra = [];
        if ($request->filled('width'))    $extra['width']  = (int) $request->width;
        if ($request->filled('height'))   $extra['height'] = (int) $request->height;
        if ($request->filled('icon'))     $extra['icon']   = $request->icon;
        if ($request->has('config'))      $extra['config'] = is_array($request->config) ? $request->config : (json_decode($request->config, true) ?? []);

        $object = OperationalObject::create(array_merge($defaults, $extra, [
            'project_id' => $project->id,
            'map_id'     => $map->id,
            'type'       => $request->type,
            'label'      => $request->label,
            'pos_x'      => $request->pos_x ?? 100,
            'pos_y'      => $request->pos_y ?? 100,
            'zone'       => $request->zone,
            'capacity'   => $request->capacity,
            'shape'      => $request->shape ?? $defaults['shape'],
        ]));

        return response()->json(['ok' => true, 'object' => $object->toMapArray()]);
    }

    public function updateObject(Request $request, OperationalObject $object): JsonResponse
    {
        $this->authorizeObject($object);

        $object->update($request->only([
            'label', 'zone', 'capacity', 'shape', 'color',
            'width', 'height', 'rotation', 'config',
        ]));

        return response()->json(['ok' => true, 'object' => $object->fresh(['responsible'])->toMapArray()]);
    }

    public function destroyObject(OperationalObject $object): JsonResponse
    {
        $this->authorizeObject($object);
        abort_if(!in_array($object->status, ['libre', 'disponible']), 422, 'El objeto está en uso.');

        $object->delete();

        return response()->json(['ok' => true]);
    }

    // ── CRUD de mapas ─────────────────────────────────────────────────────────

    public function storemap(Request $request): JsonResponse
    {
        $project = $this->project();
        $request->validate(['name' => 'required|string|max:100']);

        $map = $project->operationalMaps()->create([
            'name'       => $request->name,
            'slug'       => Str::slug($request->name),
            'is_default' => $project->operationalMaps()->count() === 0,
        ]);

        return response()->json(['ok' => true, 'map' => $map]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authorizeObject(OperationalObject $object): void
    {
        abort_unless($object->project_id === $this->project()->id, 403);
    }

    private function calcAvanScore(Project $project): int
    {
        $objects = $project->operationalObjects()->where('is_active', true)->get();
        if ($objects->isEmpty()) return 100;

        $total   = $objects->count();
        $alerts  = $objects->filter(fn($o) => !empty($o->alerts))->count();
        $blocked = $objects->whereIn('status', ['bloqueado', 'mantenimiento'])->count();

        $penaltyAlerts  = ($alerts  / $total) * 40;
        $penaltyBlocked = ($blocked / $total) * 20;

        return max(0, (int) round(100 - $penaltyAlerts - $penaltyBlocked));
    }

    private function calcSummary(Project $project): array
    {
        $objects = $project->operationalObjects()->where('is_active', true)->get();

        return [
            'total'        => $objects->count(),
            'libre'        => $objects->whereIn('status', ['libre', 'disponible'])->count(),
            'ocupado'      => $objects->whereIn('status', ['ocupado', 'en_consulta', 'en_ruta'])->count(),
            'alerta'       => $objects->filter(fn($o) => !empty($o->alerts))->count(),
            'total_amount' => $objects->sum('current_amount'),
        ];
    }

    private function defaultsForType(string $type): array
    {
        return match($type) {
            'mesa'        => ['shape' => 'rect',   'width' => 80,  'height' => 60,  'status' => 'libre',     'capacity' => 4],
            'consultorio' => ['shape' => 'rect',   'width' => 100, 'height' => 80,  'status' => 'libre',     'capacity' => 1],
            'habitacion'  => ['shape' => 'rect',   'width' => 100, 'height' => 80,  'status' => 'disponible','capacity' => 2],
            'vehiculo'    => ['shape' => 'rect',   'width' => 90,  'height' => 50,  'status' => 'disponible','capacity' => null],
            'maquina'     => ['shape' => 'rect',   'width' => 80,  'height' => 80,  'status' => 'libre',     'capacity' => null],
            'estante'     => ['shape' => 'rect',   'width' => 40,  'height' => 120, 'status' => 'libre',     'capacity' => null],
            'escritorio'  => ['shape' => 'rect',   'width' => 70,  'height' => 50,  'status' => 'disponible','capacity' => 1],
            'zona'        => ['shape' => 'rect',   'width' => 200, 'height' => 150, 'status' => 'libre',     'capacity' => null],
            default       => ['shape' => 'rect',   'width' => 80,  'height' => 60,  'status' => 'libre',     'capacity' => null],
        };
    }
}
