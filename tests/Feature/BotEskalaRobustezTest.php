<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Bots\Models\BotFlow;
use App\Modules\Crm\Models\WaCanal;
use App\Support\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Robustez del flujo REAL de Eskala: (1) analisis estatico del grafo (destinos que existen,
 * ningun ciclo sin un bloque que espere al cliente, botones validos); (2) recorrido exhaustivo:
 * desde cada bloque que espera, cada boton y una bateria de textos, sin que un turno dispare
 * mas de 4 mensajes, repita el mismo texto ni devuelva error.
 */
class BotEskalaRobustezTest extends TestCase
{
    use RefreshDatabase;

    private Project $proyecto;
    private array $def;
    private int $nro = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->proyecto = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Eskala', 'slug' => 'eskala-' . uniqid(), 'is_active' => true]);
        Productos::activar($this->proyecto, 'crm');
        WaCanal::create(['project_id' => $this->proyecto->id, 'nombre' => 'Eskala', 'tipo' => 'bixo', 'phone_number_id' => '777', 'access_token' => 't', 'app_secret' => 'sec', 'verify_token' => 'v', 'activo' => true]);
        $this->def = json_decode(file_get_contents(base_path('tests/Fixtures/flujo_eskala_meta.json')), true);
        BotFlow::comercialDe($this->proyecto)->update(['activo' => true, 'definicion' => $this->def]);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200),
            '*' => Http::response(['choices' => [['message' => ['content' => 'Respuesta IA']]]], 200),
        ]);
    }

    /** Envia por el webhook de Meta y devuelve los textos/tipos que salieron en ESE turno. */
    private function turno(string $de, array $mensaje): array
    {
        $antes = $this->contarEnviados();
        $cuerpo = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['field' => 'messages', 'value' => [
            'messaging_product' => 'whatsapp', 'metadata' => ['phone_number_id' => '777'],
            'contacts' => [['profile' => ['name' => 'Cliente'], 'wa_id' => $de]],
            'messages' => [array_merge(['from' => $de, 'id' => 'wamid.' . uniqid()], $mensaje)],
        ]]]]]]);
        $r = $this->call('POST', '/api/whatsapp/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256=' . hash_hmac('sha256', $cuerpo, 'sec')], $cuerpo);
        $this->assertSame(200, $r->status(), 'El webhook no debe fallar nunca. Mensaje: ' . json_encode($mensaje, JSON_UNESCAPED_UNICODE));

        return array_slice($this->enviados(), $antes);
    }

    private function enviados(): array
    {
        $out = [];
        Http::recorded(function ($req) use (&$out) {
            if ($req->method() === 'POST' && str_contains($req->url(), 'graph.facebook.com') && ($req->data()['status'] ?? '') !== 'read') {
                $d = $req->data();
                $out[] = [
                    'tipo'    => $d['type'],
                    'texto'   => $d['text']['body'] ?? $d['interactive']['body']['text'] ?? $d['image']['caption'] ?? '',
                    'botones' => array_map(fn ($b) => $b['reply']['id'], $d['interactive']['action']['buttons'] ?? []),
                ];
            }
        });

        return $out;
    }

    private function contarEnviados(): int
    {
        return count($this->enviados());
    }

    private function texto(string $t): array
    {
        return ['type' => 'text', 'text' => ['body' => $t]];
    }

    private function toque(string $id): array
    {
        return ['type' => 'interactive', 'interactive' => ['type' => 'button_reply', 'button_reply' => ['id' => $id, 'title' => 'x']]];
    }

    private function nuevoNumero(): string
    {
        return '5190000' . str_pad((string) (++$this->nro), 4, '0', STR_PAD_LEFT);
    }

    /** Un turno sano: como maximo 4 mensajes, sin textos repetidos, sin textos vacios. */
    private function turnoSano(array $env, string $contexto): void
    {
        $this->assertLessThanOrEqual(4, count($env), "Demasiados mensajes en un turno ({$contexto}): " . json_encode(array_column($env, 'texto'), JSON_UNESCAPED_UNICODE));
        $textos = array_filter(array_column($env, 'texto'));
        $this->assertSame(count($textos), count(array_unique($textos)), "Texto repetido en un turno ({$contexto}): " . json_encode($textos, JSON_UNESCAPED_UNICODE));
        foreach ($env as $e) {
            if ($e['tipo'] === 'text') $this->assertNotSame('', trim($e['texto']), "Mensaje vacio ({$contexto})");
        }
    }

    public function test_el_grafo_del_flujo_no_tiene_destinos_rotos_ni_ciclos_sin_espera(): void
    {
        $b = $this->def['bloques'];
        $espera = fn ($k) => in_array($b[$k]['tipo'], ['intencion', 'asistente', 'opciones', 'pregunta', 'lista'], true);
        $salidas = function ($k) use ($b) {
            $x = $b[$k];
            $d = array_filter([$x['siguiente'] ?? null, $x['confirmacion'] ?? null, $x['negacion'] ?? null, $x['si_no'] ?? null]);
            foreach ($x['botones'] ?? [] as $bt) $d[] = $bt['siguiente'];
            foreach ($x['reglas'] ?? [] as $r) $d[] = $r['siguiente'];

            return array_values(array_unique($d));
        };
        $this->assertArrayHasKey($this->def['inicio'], $b, 'El bloque de inicio existe');
        foreach ($b as $k => $x) {
            foreach ($salidas($k) as $d) {
                $this->assertArrayHasKey($d, $b, "El bloque '{$k}' apunta a '{$d}', que no existe");
            }
            $this->assertLessThanOrEqual(3, count($x['botones'] ?? []), "'{$k}' tiene mas de 3 botones (Meta los rechaza)");
            foreach ($x['botones'] ?? [] as $bt) {
                $this->assertLessThanOrEqual(20, mb_strlen($bt['titulo']), "Boton de '{$k}' con mas de 20 caracteres: {$bt['titulo']}");
            }
        }
        // Ciclos sin espera: seguir solo las salidas "automaticas" (siguiente de bloques que no esperan, si_no y reglas de condicion).
        $auto = function ($k) use ($b, $espera) {
            if ($espera($k)) return [];
            $x = $b[$k];
            $d = array_filter([$x['siguiente'] ?? null, $x['si_no'] ?? null]);
            foreach ($x['reglas'] ?? [] as $r) $d[] = $r['siguiente'];

            return array_values(array_unique($d));
        };
        $visitar = function ($k, $camino) use (&$visitar, $auto) {
            if (in_array($k, $camino, true)) {
                $this->fail('Ciclo sin bloque de espera: ' . implode(' -> ', array_merge($camino, [$k])));
            }
            foreach ($auto($k) as $d) $visitar($d, array_merge($camino, [$k]));
        };
        foreach (array_keys($b) as $k) $visitar($k, []);
        $this->assertTrue(true);
    }

    public function test_cada_boton_de_cada_bloque_lleva_a_un_turno_sano(): void
    {
        $b = $this->def['bloques'];
        foreach ($b as $k => $x) {
            if (empty($x['botones'])) continue;
            foreach ($x['botones'] as $bt) {
                $de = $this->nuevoNumero();
                $this->turno($de, $this->texto('hola'));
                // Colocar la conversacion en el bloque $k tocando su id directamente (el motor acepta cualquier bloque existente).
                if ($k !== 'menu') $this->turno($de, $this->toque('btn:' . $k));
                $env = $this->turno($de, $this->toque('btn:' . $bt['siguiente']));
                $this->turnoSano($env, "{$k} → {$bt['titulo']}");
            }
        }
    }

    /** Tarda ~5 min: corre aparte con `artisan test --group lento`. */
    #[\PHPUnit\Framework\Attributes\Group('lento')]
    public function test_una_bateria_de_textos_desde_cada_bloque_que_espera_no_rompe_ni_repite(): void
    {
        $textos = ['hola', 'precio?', 'ok', 'si', 'no', 'gracias', '👍', '.', '¿?', 'asdf qwerty', str_repeat('muy largo ', 60),
            'quiero mi tienda virtual y deseo más información', 'ya tengo dominio, sirve?', 'vendo ropa', '1', 'menu', 'asesor', 'cancelar'];
        $b = $this->def['bloques'];
        $esperan = array_keys(array_filter($b, fn ($x) => $x['tipo'] === 'intencion' && ! empty($x['esperar']) && ! empty($x['texto'])));
        foreach ($esperan as $k) {
            foreach ($textos as $t) {
                $de = $this->nuevoNumero();
                $this->turno($de, $this->texto('hola'));
                if ($k !== 'menu') $this->turno($de, $this->toque('btn:' . $k));
                $env = $this->turno($de, $this->texto($t));
                $this->turnoSano($env, "{$k} ← '" . mb_substr($t, 0, 30) . "'");
            }
        }
    }

    public function test_diez_mensajes_seguidos_del_mismo_cliente_nunca_disparan_una_avalancha(): void
    {
        $de = $this->nuevoNumero();
        $total = 0;
        foreach (['hola', 'hola', 'precio', 'precio', '?', '?', 'hola', 'quiero empezar', 'x', 'y'] as $t) {
            $env = $this->turno($de, $this->texto($t));
            $this->turnoSano($env, "seguidilla '{$t}'");
            $total += count($env);
        }
        $this->assertLessThanOrEqual(30, $total);
    }
}
