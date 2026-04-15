<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project   = app('active_project');
        $proposals = $project->proposals()->latest()->get();

        return view('proposals.index', compact('project', 'proposals'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $data = $request->validate([
            'client_name'     => 'required|string|max:150',
            'client_phone'    => 'nullable|string|max:50',
            'client_email'    => 'nullable|email|max:150',
            'business_name'   => 'nullable|string|max:150',
            'rubro'           => 'nullable|string|max:100',
            'city'            => 'nullable|string|max:100',
            'price'           => 'nullable|numeric|min:0',
            'price_first'     => 'nullable|numeric|min:0',
            'price_second'    => 'nullable|numeric|min:0',
            'valid_days'      => 'nullable|integer|min:1',
            'plat3_name'      => 'nullable|string|max:150',
            'plat3_features'  => 'nullable|array',
            'plat4_name'      => 'nullable|string|max:150',
            'plat4_features'  => 'nullable|array',
            'extra_notes'     => 'nullable|string',
        ]);

        $count  = $project->proposals()->count() + 1;
        $data['number']     = 'BIXO-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        $data['project_id'] = $project->id;
        $data['status']     = 'draft';
        $data['price']      = $data['price'] ?? 490;
        $data['price_first']  = $data['price_first']  ?? round(($data['price'] ?? 490) / 2, 2);
        $data['price_second'] = $data['price_second'] ?? round(($data['price'] ?? 490) / 2, 2);

        $proposal = Proposal::create($data);

        return response()->json(['proposal' => $proposal]);
    }

    public function update(Request $request, Proposal $proposal)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($proposal->project_id === $project->id, 403);

        $data = $request->validate([
            'client_name'     => 'sometimes|required|string|max:150',
            'client_phone'    => 'nullable|string|max:50',
            'client_email'    => 'nullable|email|max:150',
            'business_name'   => 'nullable|string|max:150',
            'rubro'           => 'nullable|string|max:100',
            'city'            => 'nullable|string|max:100',
            'price'           => 'nullable|numeric|min:0',
            'price_first'     => 'nullable|numeric|min:0',
            'price_second'    => 'nullable|numeric|min:0',
            'valid_days'      => 'nullable|integer|min:1',
            'plat3_name'      => 'nullable|string|max:150',
            'plat3_features'  => 'nullable|array',
            'plat4_name'      => 'nullable|string|max:150',
            'plat4_features'  => 'nullable|array',
            'extra_notes'     => 'nullable|string',
            'status'          => 'nullable|in:draft,sent,accepted,rejected',
        ]);

        $proposal->update($data);

        return response()->json(['proposal' => $proposal->fresh()]);
    }

    public function destroy(Proposal $proposal)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($proposal->project_id === $project->id, 403);

        $proposal->delete();

        return response()->json(['ok' => true]);
    }
}
