@props(['name', 'label', 'checked' => false, 'help' => null])

@php $id = $attributes->get('id', $name); @endphp

<div class="mb-3">
    <div class="form-check form-switch">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1" role="switch"
            @checked((bool) old($name, $checked)) {{ $attributes->except('id')->class(['form-check-input']) }}>
        <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
    </div>
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
