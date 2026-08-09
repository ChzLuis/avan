<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
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
            'price'             => 'nullable|numeric|min:0',
            'price_renewal'     => 'nullable|numeric|min:0',
            'products_included' => 'nullable|integer|min:0',
            'valid_days'        => 'nullable|integer|min:1|max:365',
            'extras'            => 'nullable|array',
            'extras.*.nombre'   => 'required_with:extras|string|max:150',
            'extras.*.precio'   => 'required_with:extras|numeric|min:0',
            'extras.*.periodo'  => 'nullable|string|max:20',   // unico|mensual|sesion
            'extra_notes'       => 'nullable|string',
            'status'            => 'nullable|in:borrador,enviada,aceptada,rechazada',
        ];
    }

    public function index()
    {
        /** @var Project $project */
        $project   = app('active_project');
        $proposals = $project->proposals()->latest()->get();

        return view('proposals.index', compact('project', 'proposals'));
    }

    public function store(Request $request)
    {
        /** @var Project $project */
        $project = app('active_project');
        $data = $request->validate($this->rules());

        $n = $project->proposals()->count() + 1;
        $data['project_id'] = $project->id;
        $data['number']     = 'PRO-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        $data['token']      = Str::random(40);
        $data['status']     = 'borrador';
        $data['price']         = $data['price'] ?? 490;
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
        $proposal->update($data);

        return response()->json([
            'proposal' => $proposal->fresh(),
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

        return view('proposals.publica', compact('proposal', 'project', 'settings'));
    }
}
