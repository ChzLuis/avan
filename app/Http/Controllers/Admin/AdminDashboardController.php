<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Models\Order;
use App\Models\Invoice;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_projects'  => Project::count(),
            'active_projects' => Project::where('is_active', true)->count(),
            'total_users'     => User::count(),
            'superadmins'     => User::where('is_superadmin', true)->count(),
            'total_orders'    => Order::count(),
            'total_invoices'  => Invoice::count(),
        ];

        $recentProjects = Project::with('owner')
            ->latest()
            ->take(5)
            ->get();

        $recentUsers = User::latest()->take(5)->get();

        return view('admin.dashboard.index', compact('stats', 'recentProjects', 'recentUsers'));
    }
}
