<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $categories = $project->categories()->withCount(['products', 'services'])->orderBy('sort_order')->get();

        if (request()->expectsJson()) {
            return response()->json($categories);
        }

        return view('catalog.categories.index', compact('project', 'categories'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'image_url' => 'nullable|string|max:500',
            'color'     => 'nullable|string|max:20',
        ]);
        $data['project_id'] = $project->id;
        $data['is_active']  = true;
        $data['sort_order'] = $project->categories()->max('sort_order') + 1;
        $category = Category::create($data);
        return response()->json($category->loadCount(['products', 'services']));
    }

    public function update(Request $request, Category $category)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($category->project_id === $project->id, 403);
        $category->update($request->validate([
            'name'      => 'required|string|max:100',
            'image_url' => 'nullable|string|max:500',
            'color'     => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]));
        return response()->json($category->loadCount(['products', 'services']));
    }

    public function destroy(Category $category)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($category->project_id === $project->id, 403);
        $category->delete();
        return response()->json(['ok' => true]);
    }
}
