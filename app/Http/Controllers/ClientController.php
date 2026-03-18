<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Project $project)
    {
        $clients     = $project->clients()->withCount(['orders','appointments'])->latest()->get();
        $clientTypes = $this->catValues($project, 'client_type');
        $leadSources = $this->catValues($project, 'lead_source');
        return view('clients.index', compact('project', 'clients', 'clientTypes', 'leadSources'));
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'phone'       => 'nullable|string|max:30',
            'email'       => 'nullable|email|max:100',
            'notes'       => 'nullable|string',
            'client_type' => 'nullable|string|max:80',
            'lead_source' => 'nullable|string|max:80',
        ]);
        $data['project_id'] = $project->id;
        $client = Client::create($data);
        $client->loadCount(['orders','appointments']);
        return response()->json(['client' => $client]);
    }

    public function update(Request $request, Project $project, Client $client)
    {
        abort_unless($client->project_id === $project->id, 403);
        $client->update($request->validate([
            'name'        => 'required|string|max:100',
            'phone'       => 'nullable|string|max:30',
            'email'       => 'nullable|email|max:100',
            'notes'       => 'nullable|string',
            'client_type' => 'nullable|string|max:80',
            'lead_source' => 'nullable|string|max:80',
        ]));
        $client->loadCount(['orders','appointments']);
        return response()->json(['client' => $client->fresh()]);
    }

    public function destroy(Project $project, Client $client)
    {
        abort_unless($client->project_id === $project->id, 403);
        $client->delete();
        return response()->json(['ok' => true]);
    }
}
