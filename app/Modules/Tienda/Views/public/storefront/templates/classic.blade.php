@extends('tienda::layouts.storefront')
@section('title', ($settings['seo_title'] ?? $project->name))
@section('content')
    {{-- V2 adaptation of resources/views/public/catalog.blade.php. --}}
    <div class="storefront-layout storefront-layout-default" data-storefront-layout="default">
        @include('tienda::public.storefront.partials.home-content')
    </div>
@endsection
