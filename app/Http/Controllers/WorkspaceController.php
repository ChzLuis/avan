<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class WorkspaceController extends Controller
{
    public function index()
    {
        $projects = Project::where('owner_id', auth()->id())
            ->orWhereHas('members', fn($q) => $q->where('user_id', auth()->id()))
            ->withCount(['products', 'orders', 'clients'])
            ->latest()
            ->get();

        return view('workspace.index', compact('projects'));
    }
}
