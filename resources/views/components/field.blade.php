@props(['name', 'label', 'type' => 'text', 'value' => '', 'options' => null, 'hint' => null, 'required' => true])
@php($invalid = $errors->has($name))
<div {{ $attributes->only('class')->merge(['class' => 'field']) }}><label for="{{ $name }}">{{ $label }} @if($required)<span aria-hidden="true">*</span>@endif</label>
@if($options !== null)
<select id="{{ $name }}" name="{{ $name }}" @required($required) @if($invalid) aria-invalid="true" @endif aria-describedby="{{ $name }}-help" {{ $attributes->except('class') }}><option value="">Seleccionar {{ mb_strtolower($label) }}</option>@foreach($options as $key => $text)<option value="{{ $key }}" @selected((string) old($name, $value) === (string) $key)>{{ $text }}</option>@endforeach</select>
@else
<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" @required($required) @if($invalid) aria-invalid="true" @endif aria-describedby="{{ $name }}-help" {{ $attributes->except('class') }}>
@endif
<small id="{{ $name }}-help" @class(['field-error' => $invalid])>{{ $invalid ? $errors->first($name) : $hint }}</small></div>
