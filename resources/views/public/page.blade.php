<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>{{ $page->title }} · {{ $project->name }}</title>
</head>
<body class="bg-slate-50 text-slate-800">
<main class="mx-auto max-w-4xl px-5 py-14">
    <a href="/{{ $project->slug }}" class="text-sm font-semibold text-indigo-600">← Volver a la tienda</a>
    <article class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm">
        @if(data_get($page->content,'image'))
            <img src="{{ asset('storage/'.data_get($page->content,'image')) }}" class="h-72 w-full object-cover">
        @endif
        <div class="p-7">
            <h1 class="text-3xl font-black">{{ $page->title }}</h1>
            <div class="mt-6 whitespace-pre-line leading-7 text-slate-600">{{ data_get($page->content, 'body', '') }}</div>
            @foreach(['history'=>'Nuestra historia','mission'=>'Misión','vision'=>'Visión','values'=>'Valores','team'=>'Equipo'] as $key => $label)
                @if(data_get($page->content,$key))
                    <section class="mt-8 border-t pt-6">
                        <h2 class="text-xl font-bold">{{ $label }}</h2>
                        <p class="mt-2 whitespace-pre-line leading-7 text-slate-600">{{ data_get($page->content,$key) }}</p>
                    </section>
                @endif
            @endforeach
            @if(data_get($page->content,'button_url'))
                <a href="{{ data_get($page->content,'button_url') }}" class="mt-8 inline-flex rounded-lg bg-indigo-600 px-5 py-3 font-bold text-white">{{ data_get($page->content,'button_text','Contáctanos') }}</a>
            @endif
        </div>
    </article>
</main>
</body>
</html>
