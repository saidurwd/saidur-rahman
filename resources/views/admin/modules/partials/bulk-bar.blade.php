{{-- Bulk action bar. Row checkboxes elsewhere on the page use form="bulk". Pass $routeBase, optional $publishable, $reorderable. --}}
<form id="bulk" method="POST" action="{{ route("{$routeBase}.bulk") }}" class="p-3 border-bottom d-flex flex-wrap gap-2 align-items-center">
    @csrf
    <select name="action" class="form-select form-select-sm w-auto" aria-label="Bulk action">
        @if ($publishable ?? true)
            <option value="publish">Publish</option>
            <option value="unpublish">Unpublish</option>
        @endif
        <option value="delete">Move to Trash</option>
    </select>
    <button type="submit" class="btn btn-sm btn-outline-secondary">Apply to selected</button>
    @error('ids')<span class="text-danger small">{{ $message }}</span>@enderror
    @if ($reorderable ?? false)<span class="small text-body-secondary ms-auto"><i class="bi bi-grip-vertical"></i> Drag rows to reorder</span>@endif
</form>
