<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Quote;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    private function getProject(string $slug): Project
    {
        return Project::where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    // GET /b/{slug} — home del portal (resumen del negocio + acceso a cotizaciones)
    public function home(string $slug)
    {
        $project = $this->getProject($slug);
        $settings = $project->settings()->pluck('value', 'key');
        return view('public.portal', compact('project', 'settings'));
    }

    // GET /b/{slug}/c/{token} — vista de cotización individual
    public function quote(string $slug, string $token)
    {
        $project = $this->getProject($slug);
        $quote   = Quote::allProjects()
                        ->where('token', $token)
                        ->where('project_id', $project->id)
                        ->with('items')
                        ->firstOrFail();

        $settings = $project->settings()->pluck('value', 'key');
        return view('public.portal-quote', compact('project', 'quote', 'settings'));
    }

    // POST /b/{slug}/c/{token}/accept — cliente acepta la cotización
    public function accept(Request $request, string $slug, string $token)
    {
        $project = $this->getProject($slug);
        $quote   = Quote::allProjects()
                        ->where('token', $token)
                        ->where('project_id', $project->id)
                        ->firstOrFail();

        if (!in_array(\App\Support\QuoteStatus::comercial($quote->status), ['sent', 'draft'])) { // normaliza 'borrador' legacy
            return response()->json(['ok' => false, 'message' => 'Esta cotización ya fue procesada.'], 422);
        }

        // La vigencia es derivada, no un estado: una vencida no puede aceptarse
        // desde el enlace publico. El vendedor puede extender valid_until o
        // duplicarla; rechazar sigue permitido (por eso este bloqueo NO va en
        // reject, que comparte el guard de arriba).
        if (\App\Support\QuoteStatus::vencida($quote->status, $quote->valid_until)) {
            return response()->json([
                'ok' => false, 'vencida' => true,
                'message' => 'La vigencia de esta cotización terminó el '
                    . $quote->valid_until->format('d/m/Y')
                    . '. Pide una actualización al vendedor.',
            ], 422);
        }

        // Transicion ATOMICA (F1c). La comprobacion de arriba y la escritura
        // eran dos pasos: dos clics simultaneos podian pasar ambos y dejar la
        // cotizacion aceptada Y rechazada. Ahora todo ocurre bajo un mismo
        // lock: releer, revalidar, componer las notas DESDE LA FILA ACTUAL
        // (para no pisar una nota que el vendedor haya escrito entre medias),
        // actualizar y registrar el unico evento. Si algo falla, no queda ni
        // transicion ni evento a medias.
        $resultado = \Illuminate\Support\Facades\DB::transaction(function () use ($quote, $project, $request) {
            $fresca = Quote::allProjects()->whereKey($quote->id)->lockForUpdate()->first();

            if (! $fresca || ! in_array(\App\Support\QuoteStatus::comercial($fresca->status), ['sent', 'draft'], true)) {
                return false;   // otra peticion llego primero
            }
            if (\App\Support\QuoteStatus::vencida($fresca->status, $fresca->valid_until)) {
                return false;
            }

            $nota = trim((string) $request->input('notes'));
            $fresca->forceFill([
                'status' => 'accepted',
                'notes'  => $fresca->notes . ($nota !== '' ? "\n[Cliente]: " . $nota : ''),
            ])->save();

            \App\Models\OrderEvent::log($project->id, 'accepted_by_client', [], null, $fresca->id);

            return true;
        });

        if (! $resultado) {
            return response()->json(['ok' => false, 'message' => 'Esta cotización ya fue procesada.'], 422);
        }

        return response()->json(['ok' => true]);
    }

    // POST /b/{slug}/c/{token}/reject — cliente rechaza la cotización
    public function reject(Request $request, string $slug, string $token)
    {
        $project = $this->getProject($slug);
        $quote   = Quote::allProjects()
                        ->where('token', $token)
                        ->where('project_id', $project->id)
                        ->firstOrFail();

        if (!in_array(\App\Support\QuoteStatus::comercial($quote->status), ['sent', 'draft'])) { // normaliza 'borrador' legacy
            return response()->json(['ok' => false, 'message' => 'Esta cotización ya fue procesada.'], 422);
        }

        $reason = trim((string) $request->input('reason'));

        // Misma transicion atomica que accept(): un solo ganador, notas
        // compuestas desde la fila actual y evento dentro de la transaccion.
        $resultado = \Illuminate\Support\Facades\DB::transaction(function () use ($quote, $project, $reason) {
            $fresca = Quote::allProjects()->whereKey($quote->id)->lockForUpdate()->first();

            if (! $fresca || ! in_array(\App\Support\QuoteStatus::comercial($fresca->status), ['sent', 'draft'], true)) {
                return false;
            }

            $fresca->forceFill([
                'status'        => 'rejected',
                'rejected_at'   => now(),
                'reject_reason' => $reason !== '' ? \Illuminate\Support\Str::limit($reason, 300) : null,
                'notes'         => $fresca->notes . ($reason !== '' ? "\n[Cliente rechazó]: " . $reason : ''),
            ])->save();

            \App\Models\OrderEvent::log($project->id, 'rejected_by_client', ['reason' => $reason ?: null], null, $fresca->id);

            return true;
        });

        if (! $resultado) {
            return response()->json(['ok' => false, 'message' => 'Esta cotización ya fue procesada.'], 422);
        }

        return response()->json(['ok' => true]);
    }

    // POST /b/{slug}/c/{token}/proof — cliente sube su comprobante de pago (voucher/captura)
    public function proof(Request $request, string $slug, string $token)
    {
        $project = $this->getProject($slug);
        $quote   = Quote::allProjects()
                        ->where('token', $token)
                        ->where('project_id', $project->id)
                        ->firstOrFail();

        // Continuidad del comprobante (F1c): se admite 'accepted' y tambien
        // 'converted'. Que el vendedor genere el pedido no puede quitarle al
        // cliente la posibilidad, ya prometida, de subir su comprobante.
        // Se compara por estado CANONICO: la comparacion cruda anterior dejaba
        // fuera los alias del historial. Esto no toca pedido ni pago contable
        // (sigue siendo F3): solo guarda el archivo y su evento.
        if (! in_array(\App\Support\QuoteStatus::comercial($quote->status), ['accepted', 'converted'], true)) {
            return response()->json(['ok' => false, 'message' => 'Solo se puede subir comprobante de una cotización aceptada o ya convertida en pedido.'], 422);
        }

        $request->validate(['proof' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);

        $file = $request->file('proof');
        $dir  = public_path('uploads/quote-proofs/' . $quote->id);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $filename);
        $url = asset('uploads/quote-proofs/' . $quote->id . '/' . $filename);

        $quote->update([
            'payment_proof_url' => $url,
            'payment_proof_at'  => now(),
            // No se marca "pagado" solo: es el negocio quien confirma que el
            // comprobante corresponde al monto correcto antes de darlo por
            // cobrado (evita marcar pagado con una captura equivocada o falsa).
            //
            // F3c: subir un comprobante NO es haber cobrado. Antes esto dejaba
            // la cotizacion en 'partial' —"pagada a medias"— sin que hubiera
            // entrado un solo sol y sin importe alguno: la misma dolencia que
            // dejo huerfanos a los pedidos 34 y 35, pero generada por el propio
            // cliente. El hecho real es "hay comprobante", y eso ya queda en
            // `payment_proof_at`; el estado de pago no se toca hasta que el
            // negocio confirme y registre el cobro, que sera un asiento.
            'payment_status'    => \App\Support\QuoteStatus::pago($quote->payment_status),
        ]);
        \App\Models\OrderEvent::log($project->id, 'proof_uploaded', [], null, $quote->id);

        return response()->json(['ok' => true, 'url' => $url]);
    }
}
