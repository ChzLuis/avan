<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ContactMessage;
use App\Models\Project;
use App\Models\StorePage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\StoreSection;
use Illuminate\Support\Arr;
use App\Storefront\StorefrontContext;
use App\Storefront\StorefrontContextBuilder;

class StorePageController extends Controller
{
    public function __construct(
        private readonly StorefrontContextBuilder $storefrontContexts,
        private readonly PublicController $publicController,
    ) {
    }

    private function project(string $slug): Project { return Project::where('slug', $slug)->where('is_active', true)->firstOrFail(); }

    public function page(string $slug, string $key) {
        $project=$this->project($slug); $context=$this->context($project); $page=$context->page($key); abort_unless($page?->is_enabled,404);
        if ($context->setting('storefront_structure_v2', '0') !== '1') return view('public.page', $context->toViewData()+compact('page'));
        return view('public.storefront.page', $context->toViewData() + compact('page'));
    }
    public function about(string $slug) {
        $project=$this->project($slug);
        $context=$this->context($project, 'nosotros');
        if ($view = $this->productionPageView($project, 'nosotros', $context)) return $view;
        $page=$context->page('nosotros'); abort_unless($page?->is_enabled,404);
        if ($context->setting('storefront_structure_v2', '0') !== '1') return view('public.page', $context->toViewData()+compact('page'));
        return view('public.storefront.page', $context->toViewData()+compact('page'));
    }
    public function contact(string $slug) {
        $project=$this->project($slug);
        $context=$this->context($project, 'contacto');
        if ($view = $this->productionPageView($project, 'contacto', $context)) return $view;
        if ($context->setting('storefront_structure_v2', '0') !== '1') return view('public.contact', $context->toViewData());
        $page=$context->page('contacto'); abort_unless($page?->is_enabled,404);
        return view('public.storefront.contact', $context->toViewData() + compact('page'));
    }

    // Renderiza Nosotros/Contacto DENTRO de la plantilla de producción (computienda,
    // etc.) para que compartan encabezado, menú y footer de la tienda.
    private function productionPageView(Project $project, string $key, ?StorefrontContext $context = null) {
        $context ??= $this->context($project, $key);
        $template = $context->templateKey();
        $view = \App\Http\Controllers\PublicController::PRODUCTION_TEMPLATE_VIEWS[$template] ?? null;
        if (!$view || !view()->exists($view)) return null;
        $storeView = $key === 'nosotros' ? 'nosotros' : 'contacto';
        [$tplView, $data] = $this->publicController->prepararCatalogo($project, false, $storeView, $context);
        $storePage = $context->page($key);
        return view($tplView, $data + compact('storePage'));
    }
    public function sendContact(Request $request, string $slug) {
        $project=$this->project($slug); $context=$this->context($project, 'contacto', false); $contactPage=$context->page('contacto');
        $phoneRule=data_get($contactPage?->content,'require_phone',false)?'required':'nullable';
        $emailRule=data_get($contactPage?->content,'require_email',false)?'required':'nullable';
        $data=$request->validate(['name'=>'required|string|max:120','phone'=>$phoneRule.'|string|max:40','email'=>$emailRule.'|email|max:160','subject'=>'nullable|string|max:180','message'=>'required|string|max:4000','privacy'=>'accepted']);
        $message=ContactMessage::create(['project_id'=>$project->id,'name'=>$data['name'],'phone'=>$data['phone']??null,'email'=>$data['email']??null,'subject'=>$data['subject']??null,'message'=>$data['message'],'privacy_accepted'=>true]);
        $recipient=$context->setting('contact_email', $project->owner?->email);
        try { if ($recipient) Mail::raw("Nuevo mensaje de {$message->name}\n\n{$message->message}", fn($mail)=>$mail->to($recipient)->subject($message->subject ?: 'Nuevo mensaje desde la tienda')); } catch (\Throwable $e) { report($e); }
        return back()->with('success',data_get($contactPage?->content,'confirmation_message','Recibimos tu mensaje. Te responderemos pronto.'));
    }
    public function blog(string $slug) {
        $project=$this->project($slug); $context=$this->context($project,'blog'); abort_unless($context->setting('storefront_structure_v2','0')==='1',404); $section=$this->blogSection($context);
        return view('public.storefront.blog', $context->toViewData() + compact('section'));
    }
    public function blogPost(string $slug, string $key) {
        $project=$this->project($slug); $context=$this->context($project,'blog'); abort_unless($context->setting('storefront_structure_v2','0')==='1',404); $section=$this->blogSection($context);
        $article=collect(data_get($section,'content.items',[]))->first(fn($item)=>($item['enabled']??false)&&($item['key']??'')===$key);
        abort_unless($article,404);
        return view('public.storefront.blog-post', $context->toViewData() + compact('article'));
    }
    public function complaints(string $slug) { $project=$this->project($slug); return view('public.complaints', compact('project')); }
    public function storeComplaint(Request $request, string $slug) {
        $project=$this->project($slug); $data=$request->validate(['consumer_name'=>'required|string|max:160','document_type'=>'required|string|max:30','document_number'=>'required|string|max:40','address'=>'nullable|string|max:255','phone'=>'nullable|string|max:40','email'=>'required|email|max:160','product_or_service'=>'required|string|max:255','amount'=>'nullable|numeric|min:0','type'=>'required|in:reclamo,queja','detail'=>'required|string|max:5000','request'=>'required|string|max:5000','terms'=>'accepted']);
        $complaint=Complaint::create($data+['project_id'=>$project->id,'code'=>'REC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),'terms_accepted'=>true]);
        try { Mail::raw("Registro {$complaint->code}\n\nHemos recibido su {$complaint->type}.", fn($mail)=>$mail->to($complaint->email)->subject('Constancia de Libro de Reclamaciones')); $recipient=$this->context($project,'complaints',false)->setting('contact_email', $project->owner?->email); if ($recipient) Mail::raw("Nuevo {$complaint->type}: {$complaint->code}", fn($mail)=>$mail->to($recipient)->subject('Libro de Reclamaciones')); } catch (\Throwable $e) { report($e); }
        return back()->with('success','Registro enviado. Tu código de seguimiento es '.$complaint->code.'.');
    }
    private function blogSection(StorefrontContext $context) {
        return $context->section('blog') ?? abort(404);
    }

    private function context(Project $project, string $storeView = 'home', bool $includeCatalog = true): StorefrontContext
    {
        return $this->storefrontContexts->forProject($project, ['include_catalog'=>$includeCatalog,'store_view'=>$storeView]);
    }
}
