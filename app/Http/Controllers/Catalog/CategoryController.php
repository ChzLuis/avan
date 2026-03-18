<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Project $project)
    {
        $categories = $project->categories()->orderBy('sort_order')->get();
        return response()->json($categories);
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate(['name' => 'required|string|max:100']);
        $data['project_id'] = $project->id;
        $data['is_active']  = true;
        $data['sort_order'] = $project->categories()->max('sort_order') + 1;
        $category = Category::create($data);
        return response()->json($category);
    }

    public function update(Request $request, Project $project, Category $category)
    {
        abort_unless($category->project_id === $project->id, 403);
        $category->update($request->validate(['name' => 'required|string|max:100', 'is_active' => 'boolean']));
        return response()->json($category);
    }

    public function destroy(Project $project, Category $category)
    {
        abort_unless($category->project_id === $project->id, 403);
        $category->delete();
        return response()->json(['ok' => true]);
    }
}
