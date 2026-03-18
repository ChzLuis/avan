<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    public function index(Project $project)
    {
        $quotes            = $project->quotes()->with('client')->latest()->get();
        $paymentMethods    = $this->catValues($project, 'payment_method');
        $paymentConditions = $this->catValues($project, 'payment_condition');
        return view('quotes.index', compact('project', 'quotes', 'paymentMethods', 'paymentConditions'));
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'client_name'  => 'required|string|max:100',
            'client_phone' => 'nullable|string|max:30',
            'client_id'    => 'nullable|integer',
            'notes'             => 'nullable|string',
            'valid_until'       => 'nullable|date',
            'payment_method'    => 'nullable|string|max:80',
            'payment_condition' => 'nullable|string|max:80',
            'items'             => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.price'       => 'required|numeric',
            'items.*.quantity'    => 'required|integer|min:1',
        ]);

        $total = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);

        $quote = $project->quotes()->create([
            'client_id'    => $data['client_id'] ?? null,
            'client_name'  => $data['client_name'],
            'client_phone' => $data['client_phone'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'valid_until'       => $data['valid_until'] ?? null,
            'payment_method'    => $data['payment_method'] ?? null,
            'payment_condition' => $data['payment_condition'] ?? null,
            'total'             => $total,
            'status'            => 'draft',
        ]);

        foreach ($data['items'] as $item) {
            $quote->items()->create($item);
        }

        return response()->json(['quote' => $quote->load('items')]);
    }

    public function update(Request $request, Project $project, Quote $quote)
    {
        abort_unless($quote->project_id === $project->id, 403);
        $quote->update($request->validate(['status' => 'required|in:draft,sent,accepted,rejected', 'notes' => 'nullable|string']));
        return response()->json(['quote' => $quote]);
    }

    // Genera token único y marca como enviada — devuelve el link del portal
    public function send(Project $project, Quote $quote)
    {
        abort_unless($quote->project_id === $project->id, 403);
        if (!$quote->token) {
            $quote->token = Str::random(48);
        }
        $quote->sent_at = now();
        $quote->status  = 'sent';
        $quote->save();

        $portalUrl = url('/b/' . $project->slug . '/c/' . $quote->token);
        return response()->json(['ok' => true, 'url' => $portalUrl, 'quote' => $quote]);
    }

    public function destroy(Project $project, Quote $quote)
    {
        abort_unless($quote->project_id === $project->id, 403);
        $quote->delete();
        return response()->json(['ok' => true]);
    }
}
