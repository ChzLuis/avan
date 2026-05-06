<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Quote;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    private function getProject(string $slug): Project
    {
        return Project::where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    // GET /b/{slug} — home del portal (resumen del negocio + acceso a cotizaciones)
    public function home(string $slug)
    {
        $project = $this->getProject($slug);
        $settings = $project->settings()->pluck('value', 'key');
        return view('public.portal', compact('project', 'settings'));
    }

    // GET /b/{slug}/c/{token} — vista de cotización individual
    public function quote(string $slug, string $token)
    {
        $project = $this->getProject($slug);
        $quote   = Quote::allProjects()
                        ->where('token', $token)
                        ->where('project_id', $project->id)
                        ->with('items')
                        ->firstOrFail();

        $settings = $project->settings()->pluck('value', 'key');
        return view('public.portal-quote', compact('project', 'quote', 'settings'));
    }

    // POST /b/{slug}/c/{token}/accept — cliente acepta la cotización
    public function accept(Request $request, string $slug, string $token)
    {
        $project = $this->getProject($slug);
        $quote   = Quote::allProjects()
                        ->where('token', $token)
                        ->where('project_id', $project->id)
                        ->firstOrFail();

        if (!in_array($quote->status, ['sent', 'draft'])) {
            return response()->json(['ok' => false, 'message' => 'Esta cotización ya fue procesada.'], 422);
        }

        $quote->update([
            'status' => 'accepted',
            'notes'  => $quote->notes . ($request->input('notes') ? "\n[Cliente]: " . $request->input('notes') : ''),
        ]);

        return response()->json(['ok' => true]);
    }
}
