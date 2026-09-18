<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Tienda\Storefront\StorefrontContextBuilder;
use App\Modules\Tienda\Support\StorefrontNavigation;
use App\Modules\Tienda\Support\StorefrontSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StorefrontQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_loads_settings_once_and_does_not_repeat_identical_queries(): void
    {
        $project = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Query Store', 'slug' => 'query-store', 'is_active' => true]);
        $project->settings()->createMany([
            ['key' => 'catalog_template', 'value' => 'computienda'],
            ['key' => 'primary_color', 'value' => '#123456'],
        ]);
        StorefrontSections::ensure($project);
        StorefrontNavigation::ensure($project);

        $queries = [];
        DB::listen(function ($query) use (&$queries) { $queries[] = preg_replace('/\s+/', ' ', strtolower($query->sql)); });
        app(StorefrontContextBuilder::class)->forProject($project->fresh(), ['include_catalog' => true]);

        $settingsQueries = array_values(array_filter($queries, fn ($sql) => str_contains($sql, 'project_settings')));
        $this->assertCount(1, $settingsQueries, implode("\n", $settingsQueries));
        $duplicates = collect($queries)->countBy()->filter(fn ($count) => $count > 1);
        $this->assertCount(0, $duplicates, $duplicates->toJson());
        $this->assertLessThanOrEqual(16, count($queries), implode("\n", $queries));
    }
}
