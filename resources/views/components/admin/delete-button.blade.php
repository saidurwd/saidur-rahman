@props(['action', 'confirm' => 'Delete this item? You can restore it from Trash.', 'label' => 'Delete', 'size' => 'sm'])

<form method="POST" action="{{ $action }}" data-confirm="{{ $confirm }}" class="d-inline">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->class(["btn btn-{$size} btn-outline-danger"]) }}>
        <i class="bi bi-trash" aria-hidden="true"></i> {{ $label }}
    </button>
</form>
