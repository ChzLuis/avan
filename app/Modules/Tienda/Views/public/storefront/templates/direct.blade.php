@extends('tienda::layouts.storefront')
@section('title', ($settings['seo_title'] ?? $project->name))
@section('content')
    {{-- V2 adaptation of resources/views/public/templates/direct.blade.php. --}}
    <div class="storefront-layout storefront-layout-direct" data-storefront-layout="direct">
        @include('tienda::public.storefront.partials.home-content')
    </div>
@endsection
