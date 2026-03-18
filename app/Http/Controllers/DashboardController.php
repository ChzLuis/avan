<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class DashboardController extends Controller
{
    public function index(Project $project)
    {
        $activeProject = $project->load('activeModules');

        $stats = [
            'orders_today'        => $project->orders()->whereDate('created_at', today())->count(),
            'quotes_pending'      => $project->quotes()->where('status', 'draft')->count(),
            'appointments_today'  => $project->appointments()->whereDate('date', today())->count(),
            'total_clients'       => $project->clients()->count(),
        ];

        $recentActivity = collect()
            ->merge($project->orders()->latest()->take(3)->get()->map(fn($o) => [
                'text' => "Nuevo pedido de {$o->client_name}",
                'time' => $o->created_at->diffForHumans(),
            ]))
            ->merge($project->appointments()->latest()->take(2)->get()->map(fn($a) => [
                'text' => "{$a->client_name} reservó una cita",
                'time' => $a->created_at->diffForHumans(),
            ]))
            ->sortByDesc('time')
            ->take(8)
            ->values();

        $pendingOrders       = $project->orders()->where('status', 'pending')->latest()->take(5)->get();
        $pendingAppointments = $project->appointments()->whereDate('date', today())->where('status', 'pending')->get();

        return view('dashboard.index', compact('activeProject', 'stats', 'recentActivity', 'pendingOrders', 'pendingAppointments'));
    }
}
