@props(['route', 'icon', 'label'])
@php($active = request()->routeIs($route === 'dashboard' ? 'dashboard' : explode('.', $route)[0].'.*'))
<a href="{{ route($route) }}" @class(['nav-link', 'is-active' => $active]) @if($active) aria-current="page" @endif><x-icon :name="$icon" /><span>{{ $label }}</span>@if($active)<span class="active-dot"></span>@endif</a>
