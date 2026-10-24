@props(['title', 'description' => '', 'eyebrow' => 'GESTIÓN ACADÉMICA'])
<div {{ $attributes->merge(['class' => 'page-heading']) }}><div><p class="eyebrow">{{ $eyebrow }}</p><h1>{{ $title }}</h1>@if($description)<p class="page-description">{{ $description }}</p>@endif</div><div class="heading-actions">{{ $slot }}</div></div>
