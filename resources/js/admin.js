import * as bootstrap from 'bootstrap';
import 'admin-lte/dist/js/adminlte.esm.js';

window.bootstrap = bootstrap;

// Flash toasts
document.querySelectorAll('.toast').forEach((el) => bootstrap.Toast.getOrCreateInstance(el).show());

// Confirm before destructive submits (x-admin.delete-button)
document.addEventListener('submit', (event) => {
    const message = event.target.dataset?.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

// Image upload preview (x-admin.form.image-upload)
document.addEventListener('change', (event) => {
    const input = event.target;
    if (!input.matches?.('input[type="file"][data-preview]')) {
        return;
    }
    const preview = document.getElementById(input.dataset.preview);
    const file = input.files?.[0];
    if (preview && file?.type.startsWith('image/')) {
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
    }
});

// Unsaved-changes warning on forms marked data-unsaved-warning
document.querySelectorAll('form[data-unsaved-warning]').forEach((form) => {
    let dirty = false;
    form.addEventListener('input', () => (dirty = true));
    form.addEventListener('submit', () => (dirty = false));
    window.addEventListener('beforeunload', (event) => {
        if (dirty) {
            event.preventDefault();
        }
    });
});

// Rich-text editor, loaded only on pages that have one.
if (document.querySelector('textarea[data-editor]')) {
    import('./editor.js').then(({ initEditors }) => initEditors());
}

// Drag-and-drop reordering for tables marked data-sortable (posts the new order as ids).
const sortable = document.querySelector('[data-sortable]');
if (sortable) {
    import('sortablejs').then(({ default: Sortable }) => {
        Sortable.create(sortable, {
            handle: '[data-drag-handle]',
            animation: 150,
            onEnd() {
                const ids = [...sortable.querySelectorAll('[data-id]')].map((row) => row.dataset.id);
                fetch(sortable.dataset.reorderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ ids }),
                }).then((response) => {
                    if (!response.ok) {
                        window.alert('Could not save the new order. Reload and try again.');
                    }
                });
            },
        });
    });
}

// Bulk actions: select-all checkbox and enabling the action bar.
document.querySelectorAll('[data-select-all]').forEach((all) => {
    const boxes = () => document.querySelectorAll('input[name="ids[]"]');
    all.addEventListener('change', () => boxes().forEach((box) => (box.checked = all.checked)));
});

// Live slug preview from the title while the slug field is untouched.
document.querySelectorAll('[data-slug-source]').forEach((title) => {
    const slug = document.getElementById(title.dataset.slugSource);
    if (!slug || slug.dataset.locked === 'true') {
        return;
    }
    let touched = slug.value !== '';
    slug.addEventListener('input', () => (touched = slug.value !== ''));
    title.addEventListener('input', () => {
        if (!touched) {
            slug.value = title.value
                .toLowerCase()
                .normalize('NFKD')
                .replace(/[^\w\s-]/g, '')
                .trim()
                .replace(/[\s_-]+/g, '-');
        }
    });
});
