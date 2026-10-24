@props(['title' => 'Todavía no hay registros', 'description' => 'Los registros aparecerán aquí cuando los agregues.', 'icon' => 'book'])
<div {{ $attributes->merge(['class' => 'empty-state']) }}><span class="empty-icon"><x-icon :name="$icon" /></span><h3>{{ $title }}</h3><p>{{ $description }}</p>{{ $slot }}</div>
