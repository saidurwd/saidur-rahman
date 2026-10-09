@props(['name', 'label' => null, 'current' => null, 'help' => 'JPG, PNG or WebP.', 'altName' => null, 'altValue' => null])

@php $id = $attributes->get('id', $name); @endphp

<div class="mb-3">
    @if ($label)<label for="{{ $id }}" class="form-label">{{ $label }}</label>@endif
    <div class="mb-2">
        <img id="{{ $id }}-preview" src="{{ $current }}" alt="" @class(['image-upload-preview', 'd-none' => ! $current])>
    </div>
    <input type="file" name="{{ $name }}" id="{{ $id }}" accept="image/jpeg,image/png,image/webp"
        data-preview="{{ $id }}-preview" {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @if ($altName)
        <x-admin.form.input :name="$altName" label="Alt text" :value="$altValue" help="Describe the image for screen readers." class="mt-2" />
    @endif
</div>
