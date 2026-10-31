@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])

@php
    $id = $attributes->get('id', $name);
    // Array fields (e.g. tech_stack.0) report under "name.*", so their errors belong to this field too.
    $messages = array_merge($errors->get($name), ...array_values($errors->get($name.'.*')));
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}@if ($required) <span class="text-danger">*</span>@endif</label>
    @endif
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @required($required)
        {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $messages !== []]) }}
    >
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
    @if ($messages !== [])<div class="invalid-feedback d-block">{{ $messages[0] }}</div>@endif
</div>
