@props(['layout' => 'panel', 'project' => null, 'pageTitle' => ''])

@if(($layout ?? 'panel') === 'comercial')
<x-comercial-layout :project="$project" :pageTitle="$pageTitle">
<x-slot name="slot">{!! $slot !!}</x-slot>
</x-comercial-layout>
@else
<x-app-layout>
<x-slot name="slot">{!! $slot !!}</x-slot>
</x-app-layout>
@endif
