@extends('layouts.storefront')
@section('title', ($settings['seo_title'] ?? $project->name))
@section('content')
    {{-- V2 adaptation of resources/views/public/templates/ecommerce.blade.php. --}}
    <div class="storefront-layout storefront-layout-ecommerce" data-storefront-layout="ecommerce">
        @include('public.storefront.partials.home-content')
    </div>
@endsection
