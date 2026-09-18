<?php

namespace App\Modules\Tienda\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConsolidarMotoresTienda extends Command
{
    protected $signature = 'storefront:consolidate-engines
        {--project= : ID o slug de un único proyecto}
        {--apply : Ejecutar los cambios; sin esta opción solo informa}';

    protected $description = 'Convierte CompuTienda al motor Ecommerce y conserva su identidad como preset tecnológico';

    public function handle(): int
    {
        $query = Project::query()->whereHas('settings', fn ($settings) => $settings
            ->where('key', 'catalog_template')->where('value', 'computienda'));

        if ($project = trim((string) $this->option('project'))) {
            $query->where(fn ($builder) => $builder->where('id', ctype_digit($project) ? (int) $project : 0)
                ->orWhere('slug', $project));
        }

        $projects = $query->orderBy('id')->get();
        if ($projects->isEmpty()) {
            $this->info('No hay proyectos CompuTienda pendientes de consolidar.');
            return self::SUCCESS;
        }

        $this->table(['ID', 'Proyecto', 'Slug', 'Cambio'], $projects->map(fn (Project $project) => [
            $project->id, $project->name, $project->slug, 'computienda → ecommerce + tech-dark',
        ]));

        if (! $this->option('apply')) {
            $this->warn('Simulación: no se modificó información. Repite con --apply para ejecutar.');
            return self::SUCCESS;
        }

        foreach ($projects as $project) {
            DB::transaction(function () use ($project) {
                $settings = $project->settings()->pluck('value', 'key');

                // Respaldo idempotente: nunca se pisa el origen guardado.
                if (! filled($settings->get('catalog_template_before_engine_consolidation'))) {
                    $project->settings()->updateOrCreate(
                        ['key' => 'catalog_template_before_engine_consolidation'],
                        ['value' => 'computienda']
                    );
                }

                $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => 'ecommerce']);

                // Solo completar huecos: los ajustes explícitos del negocio ganan.
                foreach ([
                    'theme_preset' => 'tech-dark',
                    'product_card_style' => 'tech',
                    'section_style_preset' => 'modern',
                ] as $key => $value) {
                    if (! filled($settings->get($key))) {
                        $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
                    }
                }
            });

            $this->line("Proyecto {$project->id} ({$project->slug}) consolidado.");
        }

        $this->info($projects->count().' proyecto(s) convertido(s).');
        return self::SUCCESS;
    }
}
