@extends('layouts.storefront')
@section('title', ($settings['seo_title'] ?? $project->name))
@section('content')
    {{-- V2 adaptation of resources/views/public/templates/computienda.blade.php. --}}
    <div class="storefront-layout storefront-layout-computienda" data-storefront-layout="computienda">
        @include('public.storefront.partials.home-content')
    </div>
@endsection
