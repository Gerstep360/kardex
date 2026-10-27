@props(['name', 'label', 'type' => 'text', 'value' => '', 'options' => null, 'hint' => null, 'required' => true])
@php($fieldKey = preg_replace('/\[([^\]]+)\]/', '.$1', $name))
@php($invalid = $errors->has($fieldKey))
<div {{ $attributes->only('class')->merge(['class' => 'field']) }}><label for="{{ $name }}">{{ $label }} @if($required)<span aria-hidden="true">*</span>@endif</label>
@if($options !== null)
<select id="{{ $name }}" name="{{ $name }}" @required($required) @if($invalid) aria-invalid="true" @endif aria-describedby="{{ $name }}-help" {{ $attributes->except('class') }}><option value="">Elige una opción</option>@foreach($options as $key => $text)<option value="{{ $key }}" @selected((string) old($fieldKey, $value) === (string) $key)>{{ $text }}</option>@endforeach</select>
@else
<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($fieldKey, $value) }}" @required($required) @if($invalid) aria-invalid="true" @endif aria-describedby="{{ $name }}-help" {{ $attributes->except('class') }}>
@endif
<small id="{{ $name }}-help" @class(['field-error' => $invalid])>{{ $invalid ? $errors->first($fieldKey) : $hint }}</small></div>
