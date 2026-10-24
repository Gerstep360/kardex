@props(['tone' => 'neutral'])
<span {{ $attributes->merge(['class' => 'badge badge-'.$tone]) }}><span class="badge-dot"></span>{{ $slot }}</span>
