@props(['name', 'label' => null, 'value' => null, 'rows' => 14, 'help' => null])

{{-- Rich-text field. TinyMCE attaches to [data-editor] in Phase 4; until then it is a plain textarea. HTML is purified on save. --}}
<x-admin.form.textarea :name="$name" :label="$label" :value="$value" :rows="$rows" :help="$help" data-editor="rich" {{ $attributes }} />
