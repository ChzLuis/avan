@extends('layouts.storefront')
@section('title', ($settings['seo_title'] ?? $project->name))
@section('content')
    <div class="storefront-layout storefront-layout-{{ $storefrontTheme['key'] }}" data-storefront-layout="{{ $storefrontTheme['key'] }}">
        @include('public.storefront.partials.home-content')
    </div>
@endsection
