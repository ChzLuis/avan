<?php

namespace App\Modules\Bots\Commands;

use App\Modules\Bots\Models\BotFlow;
use App\Models\Project;
use App\Modules\Bots\Support\FlowEngine\PlantillaComercial;
use Illuminate\Console\Command;

/**
 * Da de alta el Bot Comercial en las empresas que ya existían.
 *
 * Las nuevas lo reciben solas (ProjectObserver); esto es para el resto. Es
 * idempotente: se puede correr las veces que haga falta y nunca duplica ni
 * pisa un bot existente. Todos nacen desactivados.
 */
class BotComercialParaTodos extends Command
{
    protected $signature = 'bot:comercial-todos {--project= : Solo este proyecto}';

    protected $description = 'Crea el Bot Comercial predeterminado en las empresas que no lo tengan';

    public function handle(): int
    {
        $proyectos = Project::query()
            ->when($this->option('project'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('id')
            ->get();

        $creados = $existentes = 0;

        foreach ($proyectos as $p) {
            $antes = BotFlow::where('project_id', $p->id)
                ->where('plantilla', BotFlow::COMERCIAL)->exists();

            $flow = BotFlow::comercialDe($p);
            \App\Observers\ProjectObserver::asegurarToken($p);

            $antes ? $existentes++ : $creados++;

            $listo = collect(PlantillaComercial::checklist($p))->where('ok', true)->count();
            $total = count(PlantillaComercial::checklist($p));

            $this->line(sprintf(
                '  %-38s %s  datos %d/%d%s',
                mb_strimwidth($p->name, 0, 36, '…'),
                $antes ? 'ya tenía ' : 'CREADO   ',
                $listo,
                $total,
                $flow->activo ? '  [ENCENDIDO]' : ''
            ));
        }

        $this->newLine();
        $this->info("Bots creados: {$creados} · ya existían: {$existentes}");
        $this->comment('Todos quedan DESACTIVADOS: cada dueño lo enciende desde Panel → Bots.');

        return self::SUCCESS;
    }
}
