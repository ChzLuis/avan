<?php

namespace App\Http\Controllers;

use App\Ia\IA;
use App\Support\LeadScoring;
use App\Support\ProjectContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * COPILOT EMPRESARIAL — el dueño le pregunta a su negocio en español.
 *
 * "¿Cuánto vendí este mes?" · "¿Qué productos se están agotando?" · "Dame un resumen"
 * La IA recibe la pregunta + los datos REALES del proyecto (ventas, stock, clientes)
 * y responde en lenguaje natural. Si no hay IA, responde con datos directos (reglas).
 */
class CopilotEmpresarialController extends Controller
{
    public function index()
    {
        $project = app('active_project');
        return view('copilot.index', compact('project'));
    }

    public function preguntar(Request $request): JsonResponse
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate(['pregunta' => 'required|string|min:1|max:500']);

        $ctx = ProjectContext::for($project);

        // Sin IA: respondemos con datos directos según la intención (reglas simples).
        if (!LeadScoring::hayIa()) {
            return response()->json(['respuesta' => $this->respuestaSinIa($ctx, $data['pregunta']), 'fuente' => 'datos']);
        }

        // Con IA: le damos los datos reales del negocio como contexto.
        try {
            $brief = $ctx->briefDatos();
            $system = <<<TXT
            Eres el asistente de gestión del negocio. Respondes preguntas del DUEÑO
            sobre su empresa usando SOLO los datos reales del contexto. Sé breve,
            concreto y en español. Si te preguntan algo que no está en los datos,
            dilo con claridad. No inventes cifras.

            --- DATOS ACTUALES DEL NEGOCIO ---
            $brief
            TXT;

            $respuesta = IA::provider()->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $data['pregunta']],
            ], ['temperature' => 0.3, 'max_tokens' => 500]);

            return response()->json(['respuesta' => $respuesta, 'fuente' => 'ia']);
        } catch (Throwable $e) {
            // Si la IA falla, degradamos a datos directos.
            return response()->json(['respuesta' => $this->respuestaSinIa($ctx, $data['pregunta']), 'fuente' => 'datos']);
        }
    }

    /** Respuesta por reglas cuando no hay IA: detecta la intención y da el dato. */
    private function respuestaSinIa(ProjectContext $ctx, string $pregunta): string
    {
        $p = mb_strtolower($pregunta);

        if (str_contains($p, 'agot') || str_contains($p, 'stock') || str_contains($p, 'falta')) {
            $bajo = $ctx->stockBajo();
            if ($bajo->isEmpty()) return 'No tienes productos con stock bajo por ahora. 👍';
            return "Productos por agotarse:\n" . $bajo->map(fn ($x) => "• {$x['nombre']}: {$x['stock']} uds")->implode("\n");
        }

        if (str_contains($p, 'vend') || str_contains($p, 'venta') || str_contains($p, 'ingreso')) {
            $periodo = str_contains($p, 'hoy') ? 'hoy' : (str_contains($p, 'año') ? 'año' : 'mes');
            $v = $ctx->ventas($periodo);
            return "Ventas del $periodo: {$v['cantidad']} ventas por un total de S/ " . number_format($v['total'], 2) . '.';
        }

        if (str_contains($p, 'resumen') || str_contains($p, 'como va') || str_contains($p, 'cómo va')) {
            $r = $ctx->resumen();
            return "Resumen del negocio:\n" .
                "• Ventas del mes: {$r['ventas_mes']['cantidad']} (S/ " . number_format($r['ventas_mes']['total'], 2) . ")\n" .
                "• Clientes: {$r['clientes']} · Leads calientes: {$r['leads_calientes']}\n" .
                "• Productos: {$r['productos']} · Con stock bajo: {$r['stock_bajo']}";
        }

        return "Puedo ayudarte con: ventas del mes, productos por agotarse, o un resumen del negocio. " .
               "Para preguntas más libres, activa la IA en configuración.";
    }
}
