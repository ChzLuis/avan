<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\Order;

class DashboardController extends Controller
{
    public function index(string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        $hoy = today();

        $ventasHoy     = $project->orders()->whereDate('created_at', $hoy)->sum('total');
        $pedidosHoy    = $project->orders()->whereDate('created_at', $hoy)->count();
        $facturasMes   = $project->invoices()->whereMonth('issue_date', $hoy->month)->whereYear('issue_date', $hoy->year)->count();
        $totalFacturado = $project->invoices()->whereMonth('issue_date', $hoy->month)->whereYear('issue_date', $hoy->year)->sum('total');

        $ultimasFacturas = $project->invoices()->with('client')->latest()->limit(5)->get();
        $ultimosPedidos  = $project->orders()->latest()->limit(5)->get();

        return view('facturacion.dashboard', compact(
            'project', 'ventasHoy', 'pedidosHoy',
            'facturasMes', 'totalFacturado',
            'ultimasFacturas', 'ultimosPedidos'
        ));
    }
}
