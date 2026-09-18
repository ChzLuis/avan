<?php

use App\Models\Project;
use App\Modules\Tienda\Support\StorefrontSections;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Project::query()->select('id')->orderBy('id')->each(function (Project $project) {
            foreach (array_keys(StorefrontSections::COMPONENTS) as $component) {
                $duplicates = $project->storeSections()->where('page', 'home')->where('component', $component)->orderBy('id')->get();
                foreach ($duplicates->skip(1) as $duplicate) {
                    $duplicate->update([
                        'page' => 'home_archive',
                        'component' => $component.'_archived_'.$duplicate->id,
                        'is_enabled' => false,
                    ]);
                }
            }
            StorefrontSections::ensure($project);
        });
    }

    public function down(): void
    {
        // No se eliminan configuraciones publicadas por los usuarios.
    }
};
